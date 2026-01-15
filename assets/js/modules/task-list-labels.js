/**
 * Task List Labels Module
 * Handles labels assignment and management functionality
 */

// Initialize labels display on page load
function initLabelsDisplay() {
  // Find all containers with loading state
  $(".labels-loading").each(function () {
    var $container = $(this).closest(".task-labels-container");
    var taskId = $container.data("task-id");
    var currentLabels = $container.data("current-labels");

    console.log(
      "🔄 Loading labels for task:",
      taskId,
      "Labels:",
      currentLabels
    );

    if (currentLabels) {
      // Replace loading state with actual labels
      loadAndDisplayLabels($container, taskId, currentLabels);
    } else {
      // No labels, show Add placeholder
      updateLabelsDisplay($container, []);
    }
  });
}

// Load and display labels for a specific task
function loadAndDisplayLabels($container, taskId, labelIds) {
  console.log("📋 Loading labels for task:", taskId, "IDs:", labelIds);

  // Parse label IDs
  var labelIdArray = [];
  if (labelIds && typeof labelIds === "string") {
    labelIdArray = labelIds
      .split(",")
      .map(function (id) {
        return parseInt(id.trim());
      })
      .filter(function (id) {
        return !isNaN(id) && id > 0;
      });
  }

  if (labelIdArray.length === 0) {
    updateLabelsDisplay($container, []);
    return;
  }

  // Fetch all labels from server
  $.ajax({
    url:
      window.location.origin +
      "/update-new-feature/get_labels_direct.php",
    type: "GET",
    success: function (response) {
      if (response.success && response.labels) {
        // Filter and convert to display objects
        var taskLabels = [];
        labelIdArray.forEach(function (id) {
          var label = response.labels.find(function (l) {
            return parseInt(l.id) === id;
          });
          if (label) {
            taskLabels.push({
              id: label.id,
              title: label.title,
              color: label.color,
            });
          }
        });

        console.log("✅ Loaded labels for task", taskId, ":", taskLabels);
        updateLabelsDisplay($container, taskLabels);
      } else {
        console.error("Failed to load labels:", response);
        updateLabelsDisplay($container, []);
      }
    },
    error: function (xhr, status, error) {
      console.error("Failed to load labels:", error);
      updateLabelsDisplay($container, []);
    },
  });
}

// Show labels dropdown
function showLabelsDropdown($container, taskId, currentLabels) {
  // Parse current labels (comma-separated string of IDs)
  var currentLabelIds = [];
  if (currentLabels && typeof currentLabels === "string") {
    currentLabelIds = currentLabels
      .split(",")
      .map(function (id) {
        return parseInt(id.trim());
      })
      .filter(function (id) {
        return !isNaN(id) && id > 0;
      });
  }

  // Get base URL for API calls
  var baseUrl = "";

  // Fetch existing labels from database
  $.ajax({
    url:
      window.location.origin +
      baseUrl +
      "/update-new-feature/get_labels_direct.php",
    type: "GET",
    success: function (response) {
      console.log("📋 Labels response:", response);
      if (response.success && response.labels) {
        renderLabelsDropdown(
          $container,
          taskId,
          response.labels,
          currentLabelIds
        );
      } else {
        console.error("Failed to fetch labels:", response.message);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Failed to load labels",
          confirmButtonColor: "#0052CC",
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Failed to fetch labels:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Failed to load labels",
        confirmButtonColor: "#0052CC",
      });
    },
  });
}

// Render the labels dropdown with fetched data
function renderLabelsDropdown(
  $container,
  taskId,
  availableLabels,
  currentLabelIds
) {
  console.log("🎨 Rendering labels dropdown");
  console.log("🎨 Task ID:", taskId);
  console.log("🎨 Available labels:", availableLabels);
  console.log("🎨 Current label IDs:", currentLabelIds);

  // Create dropdown HTML with fixed layout
  var dropdownHtml =
    '<div class="labels-dropdown" style="' +
    "position: absolute; " +
    "top: 100%; " +
    "left: 0; " +
    "background: white; " +
    "border: 1px solid #DFE1E6; " +
    "border-radius: 3px; " +
    "box-shadow: 0 4px 8px rgba(0,0,0,0.1); " +
    "z-index: 9999; " +
    "min-width: 280px; " +
    "max-width: 320px; " +
    "display: flex; " +
    "flex-direction: column;" +
    '">';

  // Header
  dropdownHtml +=
    '<div style="padding: 8px 12px; border-bottom: 1px solid #F4F5F7; background: #FAFBFC; flex-shrink: 0;">' +
    '<strong style="font-size: 12px; color: #6B778C;">Select Labels (Task: ' +
    taskId +
    ")</strong>" +
    "</div>";

  // Scrollable labels container
  dropdownHtml +=
    '<div class="labels-list" style="' +
    "max-height: 250px; " +
    "overflow-y: auto; " +
    "flex: 1;" +
    '">';

  // Available labels with checkboxes
  availableLabels.forEach(function (label) {
    var isSelected = currentLabelIds.includes(parseInt(label.id));
    console.log(
      "🏷️ Label:",
      label.title,
      "ID:",
      label.id,
      "Selected:",
      isSelected
    );

    dropdownHtml +=
      '<div class="label-option" data-label-id="' +
      label.id +
      '" data-label-title="' +
      label.title +
      '" data-label-color="' +
      label.color +
      '" style="' +
      "padding: 8px 12px; " +
      "cursor: pointer; " +
      "border-bottom: 1px solid #F4F5F7; " +
      "display: flex; " +
      "align-items: center; " +
      "font-size: 13px; " +
      (isSelected ? "background: #E3FCEF;" : "") +
      '" onmouseover="this.style.backgroundColor=\'#F4F5F7\'" onmouseout="this.style.backgroundColor=\'' +
      (isSelected ? "#E3FCEF" : "transparent") +
      "'\">" +
      '<input type="checkbox" ' +
      (isSelected ? "checked" : "") +
      ' style="margin-right: 8px;">' +
      '<span class="label-preview" style="' +
      "background: " +
      label.color +
      "; " +
      "color: white; " +
      "padding: 2px 6px; " +
      "border-radius: 12px; " +
      "font-size: 10px; " +
      "font-weight: 600; " +
      "margin-right: 8px;" +
      '">' +
      label.title +
      "</span>" +
      "<span>" +
      label.title +
      "</span>" +
      "</div>";
  });

  // Close scrollable labels container
  dropdownHtml += "</div>";

  // Action buttons - always visible at bottom
  console.log("🔧 Creating buttons with taskId:", taskId);
  dropdownHtml +=
    '<div style="padding: 8px 12px; border-top: 1px solid #F4F5F7; display: flex; gap: 8px; flex-shrink: 0; background: white;">' +
    '<button class="save-labels-btn" data-task-id="' +
    taskId +
    '" style="' +
    "background: #0052CC; color: white; border: none; padding: 6px 16px; border-radius: 3px; font-size: 12px; cursor: pointer; font-weight: 600;" +
    '">Save</button>' +
    '<button class="clear-all-labels-btn" data-task-id="' +
    taskId +
    '" style="' +
    "background: #FF6B6B; color: white; border: none; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer;" +
    '">Clear All</button>' +
    '<button class="cancel-labels-btn" style="' +
    "background: #F4F5F7; color: #6B778C; border: none; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer;" +
    '">Cancel</button>' +
    "</div>";

  dropdownHtml += "</div>";

  console.log(
    "📝 Generated dropdown HTML preview:",
    dropdownHtml.substring(0, 200) + "..."
  );

  // Position container relatively and add dropdown
  $container.css("position", "relative");
  $container.append(dropdownHtml);

  console.log("✅ Dropdown appended to container");

  // Adjust dropdown position if it goes off-screen
  setTimeout(function () {
    var $dropdown = $(".labels-dropdown");
    if ($dropdown.length) {
      var dropdownRect = $dropdown[0].getBoundingClientRect();
      var windowHeight = window.innerHeight;

      // If dropdown goes below viewport, position it above the container
      if (dropdownRect.bottom > windowHeight - 20) {
        $dropdown.css({
          top: "auto",
          bottom: "100%",
        });
      }
    }
  }, 10);

  // Use setTimeout to ensure events are bound after DOM update
  setTimeout(function () {
    console.log("🎯 Setting up event handlers...");

    // Remove ALL existing event handlers for buttons to avoid conflicts
    $(
      ".labels-dropdown .save-labels-btn, .labels-dropdown .cancel-labels-btn, .labels-dropdown .clear-all-labels-btn"
    ).off();

    // Use a unique namespace for these events
    $(".labels-dropdown .save-labels-btn").on("click.labelsave", function (e) {
      console.log("🔥🔥🔥 SAVE BUTTON CLICKED WITH NAMESPACE!");
      e.preventDefault();
      e.stopImmediatePropagation();

      var buttonTaskId = $(this).data("task-id");
      console.log("📝 Task ID from button:", buttonTaskId);

      // Get selected label IDs
      var selectedLabelIds = [];
      $(".labels-dropdown .label-option input[type='checkbox']:checked").each(
        function () {
          var labelId = parseInt(
            $(this).closest(".label-option").data("label-id")
          );
          console.log("✅ Selected label ID:", labelId);
          if (!isNaN(labelId)) {
            selectedLabelIds.push(labelId);
          }
        }
      );

      console.log("📝 All selected IDs:", selectedLabelIds);

      if (selectedLabelIds.length === 0) {
        console.log(
          "⚠️ No labels selected - saving empty selection (clearing labels)"
        );
      }

      // Test direct AJAX call
      console.log("🚀 Making direct AJAX call...");

      var directUrl =
        window.location.origin +
        "/update-new-feature/update_task_labels_direct.php";

      console.log("🔗 Direct URL:", directUrl);

      $.ajax({
        url: directUrl,
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({
          task_id: buttonTaskId,
          label_ids: selectedLabelIds,
        }),
        beforeSend: function () {
          console.log("📤 Sending AJAX request...");
        },
        success: function (response) {
          console.log("✅ AJAX Success:", response);

          if (response && response.success) {
            if (selectedLabelIds.length === 0) {
              Swal.fire({
                icon: "success",
                title: "Success!",
                text: "Labels cleared successfully!",
                confirmButtonColor: "#0052CC",
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: "top-end",
              });
            } else {
              Swal.fire({
                icon: "success",
                title: "Success!",
                text: `${selectedLabelIds.length} label${
                  selectedLabelIds.length > 1 ? "s" : ""
                } saved successfully!`,
                confirmButtonColor: "#0052CC",
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: "top-end",
              });
            }

            // Update display - use the original container that was passed to the function
            console.log("🔄 Updating labels display for task:", buttonTaskId);

            // Convert IDs to label objects for display
            var selectedLabels = [];
            selectedLabelIds.forEach(function (id) {
              var label = availableLabels.find(function (l) {
                return parseInt(l.id) === id;
              });
              if (label) {
                selectedLabels.push({
                  id: label.id,
                  title: label.title,
                  color: label.color,
                });
              }
            });

            console.log("🏷️ Selected labels for display:", selectedLabels);

            // Find the container more reliably
            var $targetContainer = $container; // Use the original container
            if (!$targetContainer || $targetContainer.length === 0) {
              $targetContainer = $(
                '.task-labels-container[data-task-id="' + buttonTaskId + '"]'
              );
            }

            if ($targetContainer && $targetContainer.length > 0) {
              updateLabelsDisplay($targetContainer, selectedLabels);
              console.log("✅ Labels display updated successfully");
            } else {
              console.error(
                "❌ Could not find labels container for task:",
                buttonTaskId
              );
            }
          } else {
            console.error(
              "❌ Server error:",
              response ? response.message : "Unknown error"
            );
            Swal.fire({
              icon: "error",
              title: "Save Failed",
              text:
                "Failed to save: " +
                (response ? response.message : "Unknown error"),
              confirmButtonColor: "#0052CC",
            });
          }

          // Clean up and remove dropdown
          $(document).off("keydown.labels-esc");
          $(".labels-dropdown").remove();
        },
        error: function (xhr, status, error) {
          console.error("❌ AJAX Error:", error);
          console.error("❌ Status:", status);
          console.error("❌ Response:", xhr.responseText);
          Swal.fire({
            icon: "error",
            title: "Connection Error",
            text: "AJAX Error: " + error,
            confirmButtonColor: "#0052CC",
          });

          // Clean up and remove dropdown
          $(document).off("keydown.labels-esc");
          $(".labels-dropdown").remove();
        },
      });

      return false;
    });

    // Clear All button handler
    $(".labels-dropdown .clear-all-labels-btn").on(
      "click.labelclear",
      function (e) {
        console.log("🗑️ CLEAR ALL BUTTON CLICKED!");
        e.preventDefault();
        e.stopImmediatePropagation();

        var buttonTaskId = $(this).data("task-id");
        console.log("📝 Clearing all labels for task:", buttonTaskId);

        // Show confirmation dialog
        Swal.fire({
          title: "Clear All Labels?",
          text: "Are you sure you want to remove all labels from this task?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#FF6B6B",
          cancelButtonColor: "#6c757d",
          confirmButtonText: "Yes, clear all",
          cancelButtonText: "Cancel",
        }).then((result) => {
          if (result.isConfirmed) {
            // Uncheck all checkboxes
            $(".labels-dropdown .label-option input[type='checkbox']").prop(
              "checked",
              false
            );

            // Update visual styling
            $(".labels-dropdown .label-option")
              .css("background", "transparent")
              .attr("onmouseout", 'this.style.backgroundColor="transparent"');

            // Save empty selection immediately
            var directUrl =
              window.location.origin +
              "/update-new-feature/update_task_labels_direct.php";

            $.ajax({
              url: directUrl,
              type: "POST",
              contentType: "application/json",
              data: JSON.stringify({
                task_id: buttonTaskId,
                label_ids: [], // Empty array to clear all labels
              }),
              success: function (response) {
                console.log("✅ Labels cleared successfully:", response);

                if (response && response.success) {
                  Swal.fire({
                    icon: "success",
                    title: "Success!",
                    text: "All labels cleared successfully!",
                    confirmButtonColor: "#0052CC",
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: "top-end",
                  });

                  // Update display to show empty state
                  console.log(
                    "🔄 Clearing labels display for task:",
                    buttonTaskId
                  );

                  // Find the container more reliably
                  var $targetContainer = $container; // Use the original container
                  if (!$targetContainer || $targetContainer.length === 0) {
                    $targetContainer = $(
                      '.task-labels-container[data-task-id="' +
                        buttonTaskId +
                        '"]'
                    );
                  }

                  if ($targetContainer && $targetContainer.length > 0) {
                    updateLabelsDisplay($targetContainer, []); // Empty array
                    console.log("✅ Labels cleared and display updated");
                  } else {
                    console.error(
                      "❌ Could not find labels container for task:",
                      buttonTaskId
                    );
                  }
                } else {
                  console.error(
                    "❌ Server error:",
                    response ? response.message : "Unknown error"
                  );
                  Swal.fire({
                    icon: "error",
                    title: "Clear Failed",
                    text:
                      "Failed to clear labels: " +
                      (response ? response.message : "Unknown error"),
                    confirmButtonColor: "#0052CC",
                  });
                }

                // Clean up and remove dropdown
                $(document).off("keydown.labels-esc");
                $(".labels-dropdown").remove();
              },
              error: function (xhr, status, error) {
                console.error("❌ Failed to clear labels:", error);
                Swal.fire({
                  icon: "error",
                  title: "Connection Error",
                  text: "Failed to clear labels: " + error,
                  confirmButtonColor: "#0052CC",
                });

                // Clean up and remove dropdown
                $(document).off("keydown.labels-esc");
                $(".labels-dropdown").remove();
              },
            });
          }
        });

        return false;
      }
    );

    $(".labels-dropdown .cancel-labels-btn").on(
      "click.labelcancel",
      function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        // Clean up and remove dropdown
        $(document).off("keydown.labels-esc");
        $(".labels-dropdown").remove();
        return false;
      }
    );

    // Test if button exists and is clickable
    var saveBtn = $(".labels-dropdown .save-labels-btn");

    // DISABLE click-outside handler for now to prevent premature closing
    // Add a notice to the user about how to close the dropdown
    setTimeout(function () {
      var $header = $(".labels-dropdown").find("div").first();
      $header.html(
        '<strong style="font-size: 12px; color: #6B778C;">Select Labels (Task: ' +
          taskId +
          ") - Use Cancel button or ESC key to close</strong>"
      );

      // Add ESC key handler to close dropdown
      $(document).on("keydown.labels-esc", function (e) {
        if (e.keyCode === 27) {
          // ESC key

          $(document).off("keydown.labels-esc");
          $(".labels-dropdown").remove();
        }
      });
    }, 100);
  }, 200);

  // Handle checkbox clicks with event delegation
  $(document)
    .off("click", ".label-option")
    .on("click", ".label-option", function (e) {
      e.stopPropagation();
      var $checkbox = $(this).find('input[type="checkbox"]');
      var $option = $(this);

      // Toggle checkbox
      $checkbox.prop("checked", !$checkbox.prop("checked"));

      // Update styling
      if ($checkbox.prop("checked")) {
        $option.css("background", "#E3FCEF");
        $option.attr("onmouseout", 'this.style.backgroundColor="#E3FCEF"');
      } else {
        $option.css("background", "transparent");
        $option.attr("onmouseout", 'this.style.backgroundColor="transparent"');
      }
    });

  // Handle checkbox direct clicks with event delegation
  $(document)
    .off("click", '.label-option input[type="checkbox"]')
    .on("click", '.label-option input[type="checkbox"]', function (e) {
      e.stopPropagation();
      var $option = $(this).closest(".label-option");

      // Update styling
      if ($(this).prop("checked")) {
        $option.css("background", "#E3FCEF");
        $option.attr("onmouseout", 'this.style.backgroundColor="#E3FCEF"');
      } else {
        $option.css("background", "transparent");
        $option.attr("onmouseout", 'this.style.backgroundColor="transparent"');
      }
    });
}

// Global function for handling save labels (called by inline onclick)
window.handleSaveLabels = function (taskId) {
  // Get selected label IDs
  var selectedLabelIds = [];
  $(".label-option input[type='checkbox']:checked").each(function () {
    var labelId = parseInt($(this).closest(".label-option").data("label-id"));

    if (!isNaN(labelId)) {
      selectedLabelIds.push(labelId);
    }
  });

  console.log("🏷️ Total selected label IDs:", selectedLabelIds);

  // Get available labels for display conversion
  var $container = $('.task-labels-container[data-task-id="' + taskId + '"]');
  console.log("📦 Container found:", $container.length);

  // Fetch labels to get display data
  var labelsUrl =
    window.location.origin + "/update-new-feature/get_labels_direct.php";

  console.log("🔗 Fetching labels from:", labelsUrl);

  $.ajax({
    url: labelsUrl,
    type: "GET",
    success: function (response) {
      console.log("📋 Labels fetch response:", response);
      if (response.success && response.labels) {
        // Save labels
        console.log("🚀 Calling saveTaskLabels...");
        saveTaskLabels(taskId, selectedLabelIds, function (success) {
          console.log("💾 Save callback result:", success);
          if (success) {
            console.log("✅ Labels updated successfully");
            // Convert IDs back to display data
            var selectedLabels = [];
            selectedLabelIds.forEach(function (id) {
              var label = response.labels.find(function (l) {
                return parseInt(l.id) === id;
              });
              if (label) {
                selectedLabels.push({
                  id: label.id,
                  title: label.title,
                  color: label.color,
                });
              }
            });
            console.log("🎨 Display labels:", selectedLabels);
            updateLabelsDisplay($container, selectedLabels);
          } else {
            console.error("❌ Failed to update labels");
            Swal.fire({
              icon: "error",
              title: "Update Failed",
              text: "Failed to update labels",
              confirmButtonColor: "#0052CC",
            });
          }

          // Remove dropdown
          console.log("🗑️ Removing dropdown");
          $(".labels-dropdown").remove();
        });
      }
    },
    error: function () {
      console.error("❌ Failed to load labels data");
      Swal.fire({
        icon: "error",
        title: "Load Error",
        text: "Failed to load labels data",
        confirmButtonColor: "#0052CC",
      });
      $(".labels-dropdown").remove();
    },
  });
};

// Global function for handling cancel labels (called by inline onclick)
window.handleCancelLabels = function () {
  console.log("🔥 handleCancelLabels called");
  $(".labels-dropdown").remove();
};

// Save task labels to server
function saveTaskLabels(taskId, labelIds, callback) {
  console.log("💾 Saving task labels to server:", taskId, labelIds);

  // Simple and reliable URL construction
  var directUrl =
    window.location.origin +
    "/update-new-feature/update_task_labels_direct.php";

  console.log("🔗 Labels Direct URL:", directUrl);

  var requestData = {
    task_id: taskId,
    label_ids: labelIds,
  };

  console.log("📤 Request data:", requestData);

  $.ajax({
    url: directUrl,
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify(requestData),
    beforeSend: function () {
      console.log("🚀 Sending AJAX request...");
    },
    success: function (response) {
      console.log("✅ Task labels saved successfully:", response);
      if (response && response.success) {
        callback(true);
      } else {
        console.error(
          "❌ Server returned error:",
          response ? response.message : "Unknown error"
        );
        Swal.fire({
          icon: "error",
          title: "Save Failed",
          text:
            "Failed to save labels: " +
            (response ? response.message : "Unknown error"),
          confirmButtonColor: "#0052CC",
        });
        callback(false);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ Failed to save task labels:", error);
      console.error("❌ Status:", status);
      console.error("❌ Response:", xhr.responseText);
      console.error("❌ Status Code:", xhr.status);
      Swal.fire({
        icon: "error",
        title: "Connection Error",
        text:
          "Failed to save labels: " + error + " (Status: " + xhr.status + ")",
        confirmButtonColor: "#0052CC",
      });
      callback(false);
    },
  });
}

// Update labels display after successful save
function updateLabelsDisplay($container, labelObjects) {
  // Convert label objects to ID string for data storage
  var labelIds = labelObjects.map(function (label) {
    return label.id;
  });
  $container.data("current-labels", labelIds.join(","));

  var displayHtml = "";

  if (labelObjects.length === 0) {
    displayHtml =
      '<div class="labels-placeholder" style="' +
      "color: #6B778C; " +
      "cursor: pointer; " +
      "padding: 4px 8px; " +
      "border-radius: 3px; " +
      "font-size: 11px; " +
      "border: 1px dashed #DFE1E6; " +
      "text-align: center; " +
      "background: #F4F5F7;" +
      '" title="Click to add labels">Add</div>';
  } else {
    displayHtml =
      '<div class="labels-display" style="display: flex; flex-wrap: wrap; gap: 2px; align-items: center;">';

    // Show up to 3 labels directly
    var showCount = Math.min(3, labelObjects.length);
    for (var i = 0; i < showCount; i++) {
      var label = labelObjects[i];

      displayHtml +=
        '<span class="task-label" style="' +
        "background: " +
        label.color +
        "; " +
        "color: white; " +
        "padding: 2px 6px; " +
        "border-radius: 12px; " +
        "font-size: 10px; " +
        "font-weight: 600; " +
        "cursor: pointer; " +
        "white-space: nowrap; " +
        "max-width: 60px; " +
        "overflow: hidden; " +
        "text-overflow: ellipsis;" +
        '" title="' +
        label.title +
        '">' +
        (label.title.length > 8 ? label.title.substring(0, 8) : label.title) +
        "</span>";
    }

    // Show count if more than 3
    if (labelObjects.length > 3) {
      var remaining = labelObjects.length - 3;
      displayHtml +=
        '<span class="labels-more" style="' +
        "background: #BDC3C7; " +
        "color: #34495E; " +
        "padding: 2px 6px; " +
        "border-radius: 12px; " +
        "font-size: 10px; " +
        "font-weight: 600; " +
        "cursor: pointer;" +
        '" title="' +
        remaining +
        ' more labels">+' +
        remaining +
        "</span>";
    }

    displayHtml += "</div>";
  }

  // Update the container content
  $container.html(displayHtml);
}


window.addEventListener("DOMContentLoaded", () => {
  initLabelsDisplay();
})

// Make functions globally accessible
window.showLabelsDropdown = showLabelsDropdown;
window.renderLabelsDropdown = renderLabelsDropdown;
window.saveTaskLabels = saveTaskLabels;
window.updateLabelsDisplay = updateLabelsDisplay;
