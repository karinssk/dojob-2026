/**
 * Task List Inline Editing Module
 * Handles inline editing of task properties (title, description, assignee, etc.)
 */

// Initialize inline editing functionality
function initInlineEditing() {
  console.log("🔧 Initializing inline editing...");

  // Remove existing handlers
  $(document).off("click", ".task-title-display");
  $(document).off("blur keydown", ".task-title-editor");
  $(document).off("click", ".task-description-display"); // Add description handlers
  $(document).off("blur keydown", ".task-description-editor");

  // Click to edit task title - Simple version matching description editing
  $(document).on("click", ".task-title-display", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $display = $(this);
    var $editor = $display.siblings(".task-title-editor");
    var taskId = $display.data("task-id");

    console.log("📝 Starting title edit for task:", taskId);

    if ($editor.length === 0) {
      console.error("❌ No title editor element found");
      return;
    }

    // Mark as editing
    $editor.addClass("editing");

    // Hide display, show editor
    $display.css("display", "none");
    $editor.css("display", "block");

    // Set focus
    setTimeout(function () {
      $editor.focus();

      // Select all text for easy editing
      $editor.select();

      console.log("✅ Title editor focused and text selected");
    }, 50);
  });

  // Save title on blur or Enter
  $(document).on("blur keydown", ".task-title-editor", function (e) {
    // Only handle blur events OR Enter key
    if (e.type === "keydown" && e.which !== 13) {
      return; // Ignore other keys
    }

    var $editor = $(this);
    var $display = $editor.siblings(".task-title-display");
    var taskId = $editor.data("task-id");
    var newTitle = $editor.val().trim();
    var originalTitle = $display.text().trim();

    console.log("💾 Title save triggered:", {
      taskId: taskId,
      newTitle: newTitle,
      originalTitle: originalTitle,
      event: e.type,
    });

    // Always exit edit mode first to prevent getting stuck
    $editor.removeClass("editing");
    $display.css("display", "block");
    $editor.css("display", "none");

    if (newTitle === "" || newTitle === originalTitle) {
      // No changes or empty title
      console.log("🚫 No changes in title, canceling edit");
      return;
    }

    console.log("📤 Saving title changes...");

    // Disable editor during save
    $editor.prop("disabled", true);

    // Save to server
    saveTaskTitle(taskId, newTitle, function (success) {
      // Re-enable editor
      $editor.prop("disabled", false);

      if (success) {
        console.log("✅ Title saved successfully!");
        $display.text(newTitle);
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Task title saved successfully!',
          confirmButtonColor: '#0052CC',
          timer: 1500,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        });
      } else {
        console.error("❌ Failed to save title");
        Swal.fire({
          icon: 'error',
          title: 'Save Failed',
          text: 'Failed to save task title',
          confirmButtonColor: '#0052CC'
        });
        // Revert to original title
        $display.text(originalTitle);
        $editor.val(originalTitle);
      }
    });
  });

  // Handle Escape key separately to cancel title editing
  $(document).on("keydown", ".task-title-editor", function (e) {
    if (e.which === 27) {
      // Escape key
      console.log("🚫 Title edit canceled with Escape key");
      var $editor = $(this);
      var $display = $editor.siblings(".task-title-display");
      var originalTitle = $display.text().trim();

      $editor.removeClass("editing");
      $display.css("display", "block");
      $editor.css("display", "none");
      $editor.val(originalTitle);
      e.preventDefault();
      return false;
    }
  });

  // Handle click outside to save title
  $(document).on("click", function (e) {
    // Check if there are any visible title editors
    $(".task-title-editor.editing").each(function () {
      var $editor = $(this);
      var $display = $editor.siblings(".task-title-display");

      // If the click is not on the editor itself, save and exit edit mode
      if (!$editor.is(e.target) && !$.contains($editor[0], e.target)) {
        console.log("💾 Saving title due to click outside editor");

        // Immediately exit edit mode to prevent getting stuck
        $editor.removeClass("editing");
        $display.css("display", "block");
        $editor.css("display", "none");

        // Get values for saving
        var taskId = $editor.data("task-id");
        var newTitle = $editor.val().trim();
        var originalTitle = $display.text().trim();

        // Only save if there are actual changes
        if (newTitle && newTitle !== originalTitle) {
          console.log("📤 Saving changes via click outside...");
          saveTaskTitle(taskId, newTitle, function (success) {
            if (success) {
              console.log("✅ Title saved via click outside!");
              $display.text(newTitle);
            } else {
              console.error("❌ Failed to save title via click outside");
              // Revert to original
              $display.text(originalTitle);
              $editor.val(originalTitle);
            }
          });
        } else {
          console.log("🚫 No changes to save via click outside");
        }
      }
    });
  });

  // === ASSIGNEE EDITING FUNCTIONALITY ===
  // Click to assign user
  $(document).on("click", ".task-assignee-container", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentAssignee = $container.data("current-assignee") || 0;

    console.log("📝 Starting assignee edit for task:", taskId);

    // Remove any existing dropdown
    $(".assignee-dropdown").remove();

    // Create and show dropdown
    showAssigneeDropdown($container, taskId, currentAssignee);
  });

  // === COLLABORATORS EDITING FUNCTIONALITY ===
  // Click to manage collaborators
  $(document).on("click", ".task-collaborators-container", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentCollaborators = $container.data("current-collaborators") || "";

    console.log("🤝 Starting collaborators edit for task:", taskId);

    // Remove any existing dropdown
    $(".collaborators-dropdown").remove();

    // Create and show dropdown
    showCollaboratorsDropdown($container, taskId, currentCollaborators);
  });

  // === DEADLINE EDITING FUNCTIONALITY ===
  // Click to manage deadline
  $(document).on("click", ".task-deadline-container", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentDeadline = $container.data("current-deadline") || "";

    console.log("📅 Starting deadline edit for task:", taskId);

    // Remove any existing date picker to prevent duplicates
    $(".deadline-picker").remove();

    // Create and show date picker
    showDeadlinePicker($container, taskId, currentDeadline);
  });

  // === LABELS EDITING FUNCTIONALITY ===
  // Click to manage labels
  $(document).on("click", ".task-labels-container", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentLabels = $container.data("current-labels") || "";

    console.log("🏷️ Starting labels edit for task:", taskId, "Current labels:", currentLabels);

    // Remove any existing labels dropdown to prevent duplicates
    $(".labels-dropdown").remove();

    // Create and show labels dropdown
    showLabelsDropdown($container, taskId, currentLabels);
  });

  // Close dropdown when clicking outside
  $(document).on("click", function (e) {
    // Check if click is outside all dropdowns
    if (
      !$(e.target).closest(".assignee-dropdown, .task-assignee-container")
        .length
    ) {
      $(".assignee-dropdown").remove();
    }
    
    if (
      !$(e.target).closest(".collaborators-dropdown, .task-collaborators-container")
        .length
    ) {
      $(".collaborators-dropdown").remove();
    }
    
    if (
      !$(e.target).closest(".deadline-picker, .task-deadline-container")
        .length
    ) {
      $(".deadline-picker").remove();
    }
    
    if (
      !$(e.target).closest(".labels-dropdown, .task-labels-container")
        .length
    ) {
      $(".labels-dropdown").remove();
    }
  });

  // === DESCRIPTION EDITING FUNCTIONALITY ===
  // Single-click to edit task description (changed from double-click)
  $(document).on("click", ".task-description-display", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $display = $(this);
    var $editor = $display.siblings(".task-description-editor");
    var taskId = $display.data("task-id");

    console.log("📝 Starting description edit for task:", taskId);

    if ($editor.length === 0) {
      console.error("❌ No description editor element found");
      return;
    }

    // Get the full description from the data attribute or editor value
    var fullDescription = $display.data("full-description") || "";
    if (fullDescription === "Click to add description...") {
      fullDescription = "";
    }

    // Set the editor value to full description
    $editor.val(fullDescription);

    // Hide display, show editor
    $display.hide();
    $editor.show();

    // Focus the editor
    setTimeout(function () {
      $editor.focus();

      // Move cursor to end
      var textLength = $editor.val().length;
      $editor[0].setSelectionRange(textLength, textLength);

      console.log("✅ Description editor focused with full text");
    }, 50);
  });

  // Save description on blur or specific key events
  $(document).on("blur", ".task-description-editor", function (e) {
    var $editor = $(this);
    var $display = $editor.siblings(".task-description-display");
    var taskId = $editor.data("task-id");
    var newDescription = $editor.val().trim();
    var originalDescription = $display.text().trim();

    // Don't count placeholder text as original
    if (originalDescription === "Click to add description...") {
      originalDescription = "";
    }

    console.log("💾 Description blur save triggered:", {
      taskId: taskId,
      newDescription: newDescription,
      originalDescription: originalDescription,
      event: "blur",
    });

    if (newDescription === originalDescription) {
      // No changes
      console.log("🚫 No changes in description, canceling edit");
      $editor.hide();
      $display.show();
      return;
    }

    console.log("📤 Saving description changes...");

    // Disable editor during save
    $editor.prop("disabled", true);

    // Save to server
    saveTaskDescription(taskId, newDescription, function (success) {
      // Re-enable editor
      $editor.prop("disabled", false);

      if (success) {
        console.log("✅ Description saved successfully!");
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Task description saved successfully!',
          confirmButtonColor: '#0052CC',
          timer: 1500,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        });

        // Update display with truncated text but store full description
        if (newDescription === "") {
          $display
            .text("Click to add description...")
            .css("font-style", "italic");
          $display.removeData("full-description");
        } else {
          // Store full description in data attribute
          $display.data("full-description", newDescription);

          // Display truncated version
          var truncatedText = truncateDescription(newDescription);
          $display.text(truncatedText).css("font-style", "normal");

          // Add tooltip to show full text if truncated
          if (newDescription.length > 30) {
            $display.attr("title", newDescription);
          } else {
            $display.removeAttr("title");
          }
        }

        $editor.hide();
        $display.show();
      } else {
        console.error("❌ Failed to save description");
        Swal.fire({
          icon: 'error',
          title: 'Save Failed',
          text: 'Failed to save task description',
          confirmButtonColor: '#0052CC'
        });
        // Keep editor open for retry
      }
    });
  });

  // Handle Escape key separately to cancel editing
  $(document).on("keydown", ".task-description-editor", function (e) {
    if (e.which === 27) {
      // Escape key
      console.log("🚫 Description edit canceled with Escape key");
      var $editor = $(this);
      var $display = $editor.siblings(".task-description-display");
      var originalDescription = $display.text().trim();

      // Don't count placeholder text as original
      if (originalDescription === "Click to add description...") {
        originalDescription = "";
      }

      $editor.hide();
      $display.show();
      $editor.val(originalDescription);
      e.preventDefault();
      return false;
    }
  });

  console.log("✅ Inline editing initialized");
}

// Save task title to server
function saveTaskTitle(taskId, title, callback) {
  console.log("💾 Saving task title to server:", taskId, title);

  // Use the direct PHP endpoint to avoid CodeIgniter session conflicts
  // Get the base path without index.php
  var directUrl =
    baseUrl.replace("/index.php/", "/") + "update-new-feature/update_task_title_direct.php";
  console.log("🔗 Direct URL:", directUrl);
  console.log("🔗 Original baseUrl:", baseUrl);

  $.ajax({
    url: directUrl,
    method: "POST",
    data: {
      id: taskId,
      title: title,
    },
    dataType: "json",
    success: function (response) {
      console.log("✅ Task title saved successfully:", response);
      if (response && response.success) {
        callback(true);
      } else {
        console.error(
          "❌ Server returned error:",
          response.message || "Unknown error"
        );
        callback(false);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ Failed to save task title:", error);
      console.error("❌ Response:", xhr.responseText);
      callback(false);
    },
  });
}

// Helper function to truncate description text for display
function truncateDescription(text, maxLength = 30) {
  if (!text || text.trim() === "") {
    return "Click to add description...";
  }

  text = text.trim();
  if (text.length <= maxLength) {
    return text;
  }

  return text.substring(0, maxLength) + "...";
}

// Save task description to server
function saveTaskDescription(taskId, description, callback) {
  console.log("💾 Saving task description to server:", taskId, description);

  // Use the direct PHP endpoint
  var directUrl =
    baseUrl.replace("/index.php/", "/") + "update-new-feature/update_task_description_direct.php";
  console.log("🔗 Description Direct URL:", directUrl);

  $.ajax({
    url: directUrl,
    method: "POST",
    data: {
      id: taskId,
      description: description,
    },
    dataType: "json",
    success: function (response) {
      console.log("✅ Task description saved successfully:", response);
      if (response && response.success) {
        callback(true);
      } else {
        console.error(
          "❌ Server returned error:",
          response.message || "Unknown error"
        );
        callback(false);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ Failed to save task description:", error);
      console.error("❌ Response:", xhr.responseText);
      callback(false);
    },
  });
}

// Save task assignee function
function saveTaskAssignee(taskId, assigneeId, callback) {
  console.log("💾 Saving task assignee to server:", taskId, assigneeId);

  // Simple and reliable URL construction
  var directUrl = window.location.origin + '/update-new-feature/update_task_assignee_direct.php';

  console.log("🔗 Assignee Direct URL:", directUrl);

  $.ajax({
    url: directUrl,
    method: "POST",
    data: {
      id: taskId,
      assigned_to: assigneeId,
    },
    dataType: "json",
    success: function (response) {
      console.log("✅ Task assignee saved successfully:", response);
      if (response && response.success) {
        callback(true);
      } else {
        console.error(
          "❌ Server returned error:",
          response.message || "Unknown error"
        );
        callback(false);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ Failed to save task assignee:", error);
      console.error("❌ Response:", xhr.responseText);
      callback(false);
    },
  });
}

// Make functions globally accessible
window.initInlineEditing = initInlineEditing;
window.saveTaskTitle = saveTaskTitle;
window.saveTaskDescription = saveTaskDescription;
window.saveTaskAssignee = saveTaskAssignee;
window.truncateDescription = truncateDescription;
