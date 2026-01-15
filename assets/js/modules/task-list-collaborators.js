/**
 * Task List Collaborators Module
 * Handles collaborators assignment and management functionality
 */

// Get users list for assignee/collaborator dropdowns
function getUsersList(callback) {
  console.log("👥 Fetching users list...");

  // Get base URL - use working API with images
  var directUrl = + "https://dojob.rubyshop168.com/get_users_with_images.php";

  console.log("🔗 Users API URL:", directUrl);

  $.ajax({
    url: directUrl,
    type: "GET",
    dataType: "json",
    timeout: 10000, // 10 second timeout
    success: function (response) {
      console.log("✅ Users list response:", response);
      if (
        response &&
        response.success &&
        response.users &&
        Array.isArray(response.users)
      ) {
        // Add initials for display
        response.users.forEach(function (user) {
          if (user.name) {
            var nameParts = user.name.trim().split(" ");
            user.initials =
              nameParts.length >= 2
                ? nameParts[0].charAt(0) +
                  nameParts[nameParts.length - 1].charAt(0)
                : nameParts[0].charAt(0);
            user.initials = user.initials.toUpperCase();
          } else {
            user.initials = "U";
          }
        });
        console.log(
          "✅ Processed " + response.users.length + " users successfully"
        );
        callback(response.users);
      } else {
        console.error("❌ Invalid response format:", response);
        Swal.fire({
          icon: 'error',
          title: 'Load Error',
          text: 'Failed to load users: Invalid response format',
          confirmButtonColor: '#0052CC'
        });
        callback([]);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ AJAX error fetching users:", error);
      console.error("❌ Status:", status);
      console.error("❌ Response:", xhr.responseText);
      console.error("❌ XHR Status Code:", xhr.status);

      // Try to provide helpful error info
      var errorMessage = "Failed to load users: ";
      if (xhr.status === 0) {
        errorMessage += "Network error - check if server is running";
        console.error("❌ Network error - check if server is running");
      } else if (xhr.status === 404) {
        errorMessage += "Endpoint not found - check URL: " + directUrl;
        console.error("❌ Endpoint not found - check URL");
      } else if (xhr.status === 500) {
        errorMessage += "Server error - check database connection";
        console.error("❌ Server error - check database connection");
      } else {
        errorMessage += "HTTP " + xhr.status + " - " + error;
      }

      Swal.fire({
        icon: 'error',
        title: 'Connection Error',
        text: errorMessage,
        confirmButtonColor: '#0052CC'
      });
      callback([]);
    },
  });
}

// Show assignee dropdown
function showAssigneeDropdown($container, taskId, currentAssignee) {
  console.log("👤 Showing assignee dropdown for task:", taskId);

  // Get users list
  getUsersList(function (users) {
    if (users.length === 0) {
      console.error("❌ No users available for assignment");
      return;
    }

    // Create dropdown HTML
    var dropdownHtml =
      '<div class="assignee-dropdown" style="' +
      "position: absolute; " +
      "top: 100%; " +
      "left: 0; " +
      "background: white; " +
      "border: 1px solid #DFE1E6; " +
      "border-radius: 3px; " +
      "box-shadow: 0 4px 8px rgba(0,0,0,0.1); " +
      "z-index: 9999; " +
      "min-width: 200px; " +
      "max-height: 250px; " +
      "overflow-y: auto;" +
      '">';

    // Header
    dropdownHtml +=
      '<div style="padding: 8px 12px; border-bottom: 1px solid #F4F5F7; background: #FAFBFC;">' +
      '<strong style="font-size: 12px; color: #6B778C;">Assign to</strong>' +
      "</div>";

    // Unassign option
    dropdownHtml +=
      '<div class="assignee-option" data-user-id="0" style="' +
      "padding: 8px 12px; " +
      "cursor: pointer; " +
      "border-bottom: 1px solid #F4F5F7; " +
      "display: flex; " +
      "align-items: center; " +
      "font-size: 13px; " +
      (currentAssignee == 0 ? "background: #E3FCEF; font-weight: 600;" : "") +
      '" onmouseover="this.style.backgroundColor=\'#F4F5F7\'" onmouseout="this.style.backgroundColor=\'' +
      (currentAssignee == 0 ? "#E3FCEF" : "transparent") +
      "'\">" +
      '<span style="margin-right: 8px;">❌</span>' +
      "<span>Unassigned</span>" +
      "</div>";

    // Add users
    users.forEach(function (user) {
      var isSelected = currentAssignee == user.id;

      // Create avatar HTML - use profile image if available, otherwise initials
      var avatarHtml = "";
      if (user.profile_image) {
        avatarHtml =
          '<img src="' +
          user.profile_image +
          '" style="' +
          "width: 20px; " +
          "height: 20px; " +
          "border-radius: 50%; " +
          "object-fit: cover; " +
          "margin-right: 8px;" +
          '" alt="' +
          user.name +
          '">';
      } else {
        avatarHtml =
          '<span class="user-avatar" style="' +
          "background: #36B37E; " +
          "color: white; " +
          "border-radius: 50%; " +
          "width: 20px; " +
          "height: 20px; " +
          "display: inline-flex; " +
          "align-items: center; " +
          "justify-content: center; " +
          "font-size: 9px; " +
          "font-weight: 600; " +
          "margin-right: 8px;" +
          '">' +
          user.initials +
          "</span>";
      }

      dropdownHtml +=
        '<div class="assignee-option" data-user-id="' +
        user.id +
        '" style="' +
        "padding: 8px 12px; " +
        "cursor: pointer; " +
        "border-bottom: 1px solid #F4F5F7; " +
        "display: flex; " +
        "align-items: center; " +
        "font-size: 13px; " +
        (isSelected ? "background: #E3FCEF; font-weight: 600;" : "") +
        '" onmouseover="this.style.backgroundColor=\'#F4F5F7\'" onmouseout="this.style.backgroundColor=\'' +
        (isSelected ? "#E3FCEF" : "transparent") +
        "'\">" +
        avatarHtml +
        "<span>" +
        user.name +
        "</span>" +
        "</div>";
    });

    dropdownHtml += "</div>";

    // Position container relatively and add dropdown
    $container.css("position", "relative");
    $container.append(dropdownHtml);

    // Handle option clicks
    $(".assignee-option").on("click", function (e) {
      e.stopPropagation();
      var assigneeId = $(this).data("user-id");

      console.log("👤 Selected assignee:", assigneeId);

      // Save assignee
      saveTaskAssignee(taskId, assigneeId, function (success) {
        if (success) {
          console.log("✅ Assignee updated successfully");
          var assigneeData = null;
          var assigneeName = "Unassigned";
          if (assigneeId > 0) {
            assigneeData = users.find(function (u) {
              return u.id == assigneeId;
            });
            assigneeName = assigneeData ? assigneeData.first_name + " " + assigneeData.last_name : "Unknown User";
          }
          
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Task assigned to ' + assigneeName,
            confirmButtonColor: '#0052CC',
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
          
          updateAssigneeDisplay($container, assigneeId, assigneeData, users);
        } else {
          console.error("❌ Failed to update assignee");
          Swal.fire({
            icon: 'error',
            title: 'Update Failed',
            text: 'Failed to update assignee',
            confirmButtonColor: '#0052CC'
          });
        }

        // Remove dropdown
        $(".assignee-dropdown").remove();
        $(document).off("click.assignee-dropdown");
      });
    });

    // Handle clicks outside the assignee dropdown
    setTimeout(function () {
      $(document).on("click.assignee-dropdown", function (e) {
        if (
          !$(e.target).closest(".assignee-dropdown").length &&
          !$(e.target).closest(".task-assignee-container").length
        ) {
          console.log("🚫 Clicking outside assignee dropdown - closing");
          $(".assignee-dropdown").remove();
          $(document).off("click.assignee-dropdown");
        }
      });
    }, 100);
  });
}

// Update assignee display after successful save
function updateAssigneeDisplay($container, assigneeId, assigneeData, allUsers) {
  $container.data("current-assignee", assigneeId);

  var displayHtml = "";

  if (assigneeId == 0 || !assigneeData) {
    displayHtml =
      '<div class="assignee-placeholder" style="' +
      "color: #6B778C; " +
      "cursor: pointer; " +
      "padding: 4px 8px; " +
      "border-radius: 3px; " +
      "font-size: 11px; " +
      "border: 1px dashed #DFE1E6; " +
      "text-align: center; " +
      "background: #F4F5F7;" +
      '" title="Click to assign">Assign</div>';
  } else {
    // Create avatar for assignee display
    var assigneeAvatarHtml = "";
    if (assigneeData.profile_image) {
      assigneeAvatarHtml =
        '<img src="' +
        assigneeData.profile_image +
        '" style="' +
        "width: 24px; " +
        "height: 24px; " +
        "border-radius: 50%; " +
        "object-fit: cover;" +
        '" title="' +
        assigneeData.name +
        '" alt="' +
        assigneeData.initials +
        '">';
    } else {
      assigneeAvatarHtml =
        '<span class="assignee-avatar" style="' +
        "background: #36B37E; " +
        "color: white; " +
        "border-radius: 50%; " +
        "width: 24px; " +
        "height: 24px; " +
        "display: inline-flex; " +
        "align-items: center; " +
        "justify-content: center; " +
        "font-size: 10px; " +
        "font-weight: 600;" +
        '" title="' +
        assigneeData.name +
        '">' +
        assigneeData.initials +
        "</span>";
    }

    displayHtml =
      '<div class="assignee-display" style="display: flex; align-items: center; gap: 6px; cursor: pointer;">' +
      assigneeAvatarHtml +
      '<span style="font-size: 12px; color: #172B4D; font-weight: 500;">' +
      (assigneeData.name.length > 12
        ? assigneeData.name.substring(0, 12) + "..."
        : assigneeData.name) +
      "</span>" +
      "</div>";
  }

  // Update the container content
  $container.html(displayHtml);
  
  // Add error handling for assignee images after they're inserted into DOM
  $container.find('img').on('error', function() {
    var $img = $(this);
    var title = $img.attr('title');
    var initials = $img.attr('alt');
    
    console.log('❌ Assignee image failed to load for:', title);
    
    // Replace with initials avatar
    var fallbackHtml = '<span class="assignee-avatar" style="' +
      "background: #36B37E; " +
      "color: white; " +
      "border-radius: 50%; " +
      "width: 24px; " +
      "height: 24px; " +
      "display: inline-flex; " +
      "align-items: center; " +
      "justify-content: center; " +
      "font-size: 10px; " +
      "font-weight: 600;" +
      '" title="' + title + '">' + initials + '</span>';
    
    $img.replaceWith(fallbackHtml);
  });
}

// Show collaborators dropdown
function showCollaboratorsDropdown($container, taskId, currentCollaborators) {
  console.log("🤝 Showing collaborators dropdown for task:", taskId);
  console.log("🤝 Container:", $container);
  console.log("🤝 Current collaborators:", currentCollaborators);

  // Get users list
  getUsersList(function (users) {
    console.log("🤝 Retrieved users for collaborators:", users);
    if (users.length === 0) {
      console.error("❌ No users available for collaborators");
      Swal.fire({
        icon: 'warning',
        title: 'No Users Found',
        text: 'No users found. Please check the database connection.',
        confirmButtonColor: '#0052CC'
      });
      return;
    }

    // Parse current collaborators (comma-separated string)
    var currentCollaboratorIds = [];
    if (currentCollaborators && typeof currentCollaborators === "string") {
      currentCollaboratorIds = currentCollaborators
        .split(",")
        .map(function (id) {
          return parseInt(id.trim());
        })
        .filter(function (id) {
          return !isNaN(id) && id > 0;
        });
    }

    // Create dropdown HTML with fixed layout
    var dropdownHtml =
      '<div class="collaborators-dropdown" style="' +
      "position: absolute; " +
      "top: 100%; " +
      "left: 0; " +
      "background: white; " +
      "border: 1px solid #DFE1E6; " +
      "border-radius: 3px; " +
      "box-shadow: 0 4px 8px rgba(0,0,0,0.1); " +
      "z-index: 9999; " +
      "min-width: 250px; " +
      "max-width: 300px; " +
      "display: flex; " +
      "flex-direction: column;" +
      '">';

    // Header
    dropdownHtml +=
      '<div style="padding: 8px 12px; border-bottom: 1px solid #F4F5F7; background: #FAFBFC; flex-shrink: 0;">' +
      '<strong style="font-size: 12px; color: #6B778C;">Select Collaborators</strong>' +
      "</div>";

    // Scrollable users container
    dropdownHtml += '<div class="collaborators-list" style="' +
      "max-height: 200px; " +
      "overflow-y: auto; " +
      "flex: 1;" +
      '">';

    // Add users with checkboxes
    users.forEach(function (user) {
      var isSelected = currentCollaboratorIds.includes(user.id);

      // Create avatar HTML for collaborators
      var avatarHtml = "";
      if (user.profile_image) {
        avatarHtml =
          '<img src="' +
          user.profile_image +
          '" style="' +
          "width: 20px; " +
          "height: 20px; " +
          "border-radius: 50%; " +
          "object-fit: cover; " +
          "margin-right: 8px;" +
          '" alt="' +
          user.name +
          '">';
      } else {
        avatarHtml =
          '<span class="user-avatar" style="' +
          "background: #36B37E; " +
          "color: white; " +
          "border-radius: 50%; " +
          "width: 20px; " +
          "height: 20px; " +
          "display: inline-flex; " +
          "align-items: center; " +
          "justify-content: center; " +
          "font-size: 9px; " +
          "font-weight: 600; " +
          "margin-right: 8px;" +
          '">' +
          user.initials +
          "</span>";
      }

      dropdownHtml +=
        '<div class="collaborator-option" data-user-id="' +
        user.id +
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
        avatarHtml +
        "<span>" +
        user.name +
        "</span>" +
        "</div>";
    });

    // Close scrollable users container
    dropdownHtml += '</div>';

    // Action buttons - always visible at bottom
    dropdownHtml +=
      '<div style="padding: 8px 12px; border-top: 1px solid #F4F5F7; display: flex; gap: 8px; flex-shrink: 0; background: white;">' +
      '<button class="save-collaborators-btn" style="' +
      "background: #0052CC; color: white; border: none; padding: 6px 16px; border-radius: 3px; font-size: 12px; cursor: pointer; font-weight: 600;" +
      '">Save</button>' +
      '<button class="cancel-collaborators-btn" style="' +
      "background: #F4F5F7; color: #6B778C; border: none; padding: 6px 16px; border-radius: 3px; font-size: 12px; cursor: pointer;" +
      '">Cancel</button>' +
      "</div>";

    dropdownHtml += "</div>";

    // Position container relatively and add dropdown
    $container.css("position", "relative");
    $container.append(dropdownHtml);

    // Adjust dropdown position if it goes off-screen
    setTimeout(function() {
      var $dropdown = $(".collaborators-dropdown");
      if ($dropdown.length) {
        var dropdownRect = $dropdown[0].getBoundingClientRect();
        var windowHeight = window.innerHeight;
        
        // If dropdown goes below viewport, position it above the container
        if (dropdownRect.bottom > windowHeight - 20) {
          $dropdown.css({
            "top": "auto",
            "bottom": "100%"
          });
        }
      }
    }, 10);

    // Handle checkbox clicks
    $(".collaborator-option").on("click", function (e) {
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

    // Handle checkbox direct clicks
    $(".collaborator-option input[type='checkbox']").on("click", function (e) {
      e.stopPropagation();
      var $option = $(this).closest(".collaborator-option");

      // Update styling
      if ($(this).prop("checked")) {
        $option.css("background", "#E3FCEF");
        $option.attr("onmouseout", 'this.style.backgroundColor="#E3FCEF"');
      } else {
        $option.css("background", "transparent");
        $option.attr("onmouseout", 'this.style.backgroundColor="transparent"');
      }
    });

    // Handle Save button
    $(".save-collaborators-btn").on("click", function (e) {
      e.stopPropagation();

      // Get selected collaborators
      var selectedIds = [];
      $(".collaborator-option input[type='checkbox']:checked").each(
        function () {
          var userId = $(this).closest(".collaborator-option").data("user-id");
          selectedIds.push(userId);
        }
      );

      console.log("👥 Selected collaborators:", selectedIds);

      // Save collaborators
      saveTaskCollaborators(taskId, selectedIds, function (success) {
        if (success) {
          console.log("✅ Collaborators updated successfully");
          
          var collaboratorCount = selectedIds.length;
          var message = collaboratorCount === 0 ? 
            'All collaborators removed' : 
            collaboratorCount + ' collaborator' + (collaboratorCount > 1 ? 's' : '') + ' updated';
          
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: message,
            confirmButtonColor: '#0052CC',
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
          
          updateCollaboratorsDisplay($container, selectedIds, users);
        } else {
          console.error("❌ Failed to update collaborators");
          Swal.fire({
            icon: 'error',
            title: 'Update Failed',
            text: 'Failed to update collaborators',
            confirmButtonColor: '#0052CC'
          });
        }

        // Remove dropdown
        $(".collaborators-dropdown").remove();
        $(document).off("click.collaborators-dropdown");
      });
    });

    // Handle Cancel button
    $(".cancel-collaborators-btn").on("click", function (e) {
      e.stopPropagation();
      $(".collaborators-dropdown").remove();
      $(document).off("click.collaborators-dropdown");
    });

    // Handle clicks outside the collaborators dropdown
    setTimeout(function () {
      $(document).on("click.collaborators-dropdown", function (e) {
        if (
          !$(e.target).closest(".collaborators-dropdown").length &&
          !$(e.target).closest(".task-collaborators-container").length
        ) {
          console.log("🚫 Clicking outside collaborators dropdown - closing");
          $(".collaborators-dropdown").remove();
          $(document).off("click.collaborators-dropdown");
        }
      });
    }, 100);
  });
}

// Save task assignee to server
function saveTaskAssignee(taskId, assigneeId, callback) {
  console.log("💾 Saving task assignee to server:", taskId, assigneeId);

  // Simple and reliable URL construction
  var directUrl =
    window.location.origin + "/update-new-feature/update_task_assignee_direct.php";

  console.log("🔗 Assignee Direct URL:", directUrl);

  $.ajax({
    url: directUrl,
    method: "POST",
    data: {
      task_id: taskId,
      assignee_id: assigneeId, // Fixed field name to match PHP endpoint
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
      console.error("Response:", xhr.responseText);
      callback(false);
    },
  });
}

// Save task collaborators to server
function saveTaskCollaborators(taskId, collaboratorIds, callback) {
  console.log(
    "💾 Saving task collaborators to server:",
    taskId,
    collaboratorIds
  );

  // Convert array to comma-separated string
  var collaboratorsString = collaboratorIds.join(",");

  // Simple and reliable URL construction
  var directUrl =
    window.location.origin + "/update-new-feature/update_task_collaborators_direct.php";

  console.log("🔗 Collaborators Direct URL:", directUrl);

  $.ajax({
    url: directUrl,
    method: "POST",
    data: {
      task_id: taskId,
      collaborators: collaboratorsString,
    },
    dataType: "json",
    success: function (response) {
      console.log("✅ Task collaborators saved successfully:", response);
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
      console.error("❌ Failed to save task collaborators:", error);
      console.error("Response:", xhr.responseText);
      callback(false);
    },
  });
}

// Update collaborators display after successful save
function updateCollaboratorsDisplay($container, collaboratorIds, allUsers) {
  $container.data("current-collaborators", collaboratorIds.join(","));

  var displayHtml = "";

  if (collaboratorIds.length === 0) {
    displayHtml =
      '<div class="collaborators-placeholder" style="' +
      "color: #6B778C; " +
      "cursor: pointer; " +
      "padding: 4px 8px; " +
      "border-radius: 3px; " +
      "font-size: 11px; " +
      "border: 1px dashed #DFE1E6; " +
      "text-align: center; " +
      "background: #F4F5F7;" +
      '" title="Click to add collaborators">Add</div>';
  } else {
    displayHtml =
      '<div class="collaborators-avatars" style="display: flex; gap: 2px; align-items: center;">';

    // Show up to 3 collaborator avatars
    var showCount = Math.min(3, collaboratorIds.length);
    for (var i = 0; i < showCount; i++) {
      var userId = collaboratorIds[i];
      var user = allUsers.find(function (u) {
        return u.id == userId;
      });

      if (user) {
        console.log(
          "🖼️ Processing user for display:",
          user.name,
          "Image:",
          user.profile_image
        );

        if (user.profile_image) {
          displayHtml +=
            '<img src="' +
            user.profile_image +
            '" style="' +
            "width: 20px; " +
            "height: 20px; " +
            "border-radius: 50%; " +
            "object-fit: cover; " +
            "border: 1px solid white; " +
            "margin-left: -2px; " +
            "cursor: pointer;" +
            '" title="' +
            user.name +
            '" alt="' +
            user.initials +
            '">';
        } else {
          displayHtml +=
            '<span class="collaborator-avatar" style="' +
            "background: #36B37E; " +
            "color: white; " +
            "border-radius: 50%; " +
            "width: 20px; " +
            "height: 20px; " +
            "display: inline-flex; " +
            "align-items: center; " +
            "justify-content: center; " +
            "font-size: 9px; " +
            "font-weight: 600; " +
            "border: 1px solid white; " +
            "margin-left: -2px; " +
            "cursor: pointer;" +
            '" title="' +
            user.name +
            '">' +
            user.initials +
            "</span>";
        }
      } else {
        // User not found in allUsers array - show generic avatar
        displayHtml +=
          '<span class="collaborator-avatar" style="' +
          "background: #FF6B6B; " +
          "color: white; " +
          "border-radius: 50%; " +
          "width: 20px; " +
          "height: 20px; " +
          "display: inline-flex; " +
          "align-items: center; " +
          "justify-content: center; " +
          "font-size: 9px; " +
          "font-weight: 600; " +
          "border: 1px solid white; " +
          "margin-left: -2px; " +
          "cursor: pointer;" +
          '" title="User ID: ' +
          userId +
          '">?</span>';
      }
    }

    // Show count if more than 3
    if (collaboratorIds.length > 3) {
      var remaining = collaboratorIds.length - 3;
      displayHtml +=
        '<span style="' +
        "background: #DFE1E6; " +
        "color: #6B778C; " +
        "border-radius: 50%; " +
        "width: 20px; " +
        "height: 20px; " +
        "display: inline-flex; " +
        "align-items: center; " +
        "justify-content: center; " +
        "font-size: 8px; " +
        "font-weight: 600; " +
        "margin-left: -2px; " +
        "cursor: pointer;" +
        '" title="' +
        remaining +
        ' more collaborators">+' +
        remaining +
        "</span>";
    }

    displayHtml += "</div>";
  }

  // Update the container content
  $container.html(displayHtml);

  // Add error handling for images after they're inserted into DOM
  $container.find("img").on("error", function () {
    var $img = $(this);
    var title = $img.attr("title");
    var initials = $img.attr("alt");

    console.log("❌ Image failed to load for:", title);

    // Replace with initials avatar
    var fallbackHtml =
      '<span class="collaborator-avatar" style="' +
      "background: #36B37E; " +
      "color: white; " +
      "border-radius: 50%; " +
      "width: 20px; " +
      "height: 20px; " +
      "display: inline-flex; " +
      "align-items: center; " +
      "justify-content: center; " +
      "font-size: 9px; " +
      "font-weight: 600; " +
      "border: 1px solid white; " +
      "margin-left: -2px; " +
      "cursor: pointer;" +
      '" title="' +
      title +
      '">' +
      initials +
      "</span>";

    $img.replaceWith(fallbackHtml);
  });
}

// Initialize collaborators display on page load
function initCollaboratorsDisplay() {
  console.log("🤝 Initializing collaborators display...");

  // Find all collaborator containers that need initialization
  $(".task-collaborators-container").each(function () {
    var $container = $(this);
    var currentCollaborators = $container.data("current-collaborators");

    if (currentCollaborators && currentCollaborators.trim() !== "") {
      console.log(
        "🔄 Loading collaborators for container:",
        currentCollaborators
      );
      loadAndDisplayCollaborators($container, currentCollaborators);
    }
  });
}

// Initialize assignees display on page load
function initAssigneesDisplay() {
  console.log("👤 Initializing assignees display...");

  // Find all assignee containers that need initialization
  $(".task-assignee-container").each(function () {
    var $container = $(this);
    var currentAssignee = $container.data("current-assignee");

    if (currentAssignee && currentAssignee > 0) {
      console.log("🔄 Loading assignee for container:", currentAssignee);
      loadAndDisplayAssignee($container, currentAssignee);
    }
  });
}

// Load and display collaborators with profile images
function loadAndDisplayCollaborators($container, collaboratorIds) {
  console.log("📋 Loading collaborators with images:", collaboratorIds);

  // Parse collaborator IDs
  var collaboratorIdArray = [];
  if (collaboratorIds && typeof collaboratorIds === "string") {
    collaboratorIdArray = collaboratorIds
      .split(",")
      .map(function (id) {
        return parseInt(id.trim());
      })
      .filter(function (id) {
        return !isNaN(id) && id > 0;
      });
  }

  if (collaboratorIdArray.length === 0) {
    updateCollaboratorsDisplay($container, [], []);
    return;
  }

  // Fetch users with profile images
  getUsersList(function (users) {
    if (users && users.length > 0) {
      console.log(
        "✅ Loaded users with images for collaborators display:",
        users.length
      );
      console.log("🖼️ Sample user with image:", users[0]);
      updateCollaboratorsDisplay($container, collaboratorIdArray, users);
    } else {
      console.error("❌ Failed to load users for collaborators display");
      updateCollaboratorsDisplay($container, collaboratorIdArray, []);
    }
  });
}

// Load and display assignee with profile image
function loadAndDisplayAssignee($container, assigneeId) {
  console.log("👤 Loading assignee with image:", assigneeId);

  // Fetch users with profile images
  getUsersList(function (users) {
    if (users && users.length > 0) {
      var assigneeData = users.find(function (u) {
        return u.id == assigneeId;
      });

      if (assigneeData) {
        console.log("✅ Found assignee data with image:", assigneeData.name);
        updateAssigneeDisplay($container, assigneeId, assigneeData, users);
      } else {
        console.error("❌ Assignee not found in users list:", assigneeId);
        // Show placeholder for unknown assignee
        updateAssigneeDisplay($container, 0, null, users);
      }
    } else {
      console.error("❌ Failed to load users for assignee display");
      updateAssigneeDisplay($container, 0, null, []);
    }
  });
}

// Make functions globally accessible
window.getUsersList = getUsersList;
window.showAssigneeDropdown = showAssigneeDropdown;
window.saveTaskAssignee = saveTaskAssignee;
window.updateAssigneeDisplay = updateAssigneeDisplay;
window.showCollaboratorsDropdown = showCollaboratorsDropdown;
window.saveTaskCollaborators = saveTaskCollaborators;
window.updateCollaboratorsDisplay = updateCollaboratorsDisplay;
window.initCollaboratorsDisplay = initCollaboratorsDisplay;
window.loadAndDisplayCollaborators = loadAndDisplayCollaborators;
window.initAssigneesDisplay = initAssigneesDisplay;
window.loadAndDisplayAssignee = loadAndDisplayAssignee;

// Debug logging
console.log("🔧 Collaborators module loaded successfully");
console.log(
  "🔧 showCollaboratorsDropdown available:",
  typeof showCollaboratorsDropdown
);

// Auto-initialize on DOM ready
$(document).ready(function () {
  console.log("🚀 DOM ready - initializing collaborators and assignees display");
  setTimeout(function() {
    initCollaboratorsDisplay();
    initAssigneesDisplay();
  }, 100); // Small delay to ensure other scripts are loaded
});
