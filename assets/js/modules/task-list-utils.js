/**
 * Task List Utilities Module
 * Contains helper functions, test functions, and utility methods
 */

// Initialize task data display from database values
function initTaskDataDisplay() {
  console.log("🔄 Initializing task data display from database values...");
  
  // Get users list to populate assignee and collaborator displays
  getUsersList(function(users) {
    console.log("✅ Users loaded for data display:", users.length);
    
    // Update all assignee displays
    $('.task-assignee-container').each(function() {
      var $container = $(this);
      var assigneeId = parseInt($container.data('current-assignee')) || 0;
      var taskId = $container.data('task-id');
      
      console.log("📝 Updating assignee display for task:", taskId, "assignee:", assigneeId);
      
      if (assigneeId > 0) {
        updateAssigneeDisplay($container, assigneeId, null, users);
      }
    });
    
    // Update all collaborator displays
    $('.task-collaborators-container').each(function() {
      var $container = $(this);
      var collaboratorsString = $container.data('current-collaborators') || '';
      var taskId = $container.data('task-id');
      
      console.log("👥 Updating collaborators display for task:", taskId, "collaborators:", collaboratorsString);
      
      if (collaboratorsString) {
        var collaboratorIds = collaboratorsString.split(',').map(function(id) {
          return parseInt(id.trim());
        }).filter(function(id) {
          return id > 0;
        });
        
        if (collaboratorIds.length > 0) {
          updateCollaboratorsDisplay($container, collaboratorIds, users);
        }
      }
    });
  });
  
  // Update all labels displays
  $('.task-labels-container').each(function() {
    var $container = $(this);
    var labelsString = $container.data('current-labels') || '';
    var taskId = $container.data('task-id');
    
    console.log("🏷️ Updating labels display for task:", taskId, "labels:", labelsString);
    
    if (labelsString) {
      var labelIds = labelsString.split(',').map(function(id) {
        return parseInt(id.trim());
      }).filter(function(id) {
        return !isNaN(id) && id > 0;
      });
      
      if (labelIds.length > 0) {
        // Fetch label details from server
        var pathParts = window.location.pathname.split('/');
        var appIndex = pathParts.indexOf('dojob');
        var baseUrl = window.location.origin + '/' + pathParts.slice(1, appIndex + 1).join('/');
        
        $.ajax({
          url: baseUrl + '/update-new-feature/get_labels_direct.php',
          type: 'GET',
          success: function(response) {
            if (response.success && response.labels) {
              // Filter labels to only include the ones assigned to this task
              var taskLabels = response.labels.filter(function(label) {
                return labelIds.includes(parseInt(label.id));
              });
              
              if (taskLabels.length > 0) {
                updateLabelsDisplay($container, taskLabels);
              }
            }
          },
          error: function(xhr, status, error) {
            console.error("Failed to fetch labels for task:", taskId, error);
          }
        });
      }
    }
  });
  
  // Add click handlers for assignee dropdowns
  $(document).on('click', '.task-assignee-container', function(e) {
    e.stopPropagation();
    var $container = $(this);
    var taskId = $container.data('task-id');
    var currentAssignee = $container.data('current-assignee') || 0;
    
    // Remove any existing dropdowns
    $('.assignee-dropdown').remove();
    
    showAssigneeDropdown($container, taskId, currentAssignee);
  });
  
  // Add click handlers for collaborators dropdowns
  $(document).on('click', '.task-collaborators-container', function(e) {
    e.stopPropagation();
    var $container = $(this);
    var taskId = $container.data('task-id');
    var currentCollaborators = $container.data('current-collaborators') || '';
    
    // Remove any existing dropdowns
    $('.collaborators-dropdown').remove();
    
    showCollaboratorsDropdown($container, taskId, currentCollaborators);
  });
  
  // Add click handlers for deadline pickers
  $(document).on('click', '.task-deadline-container', function(e) {
    e.stopPropagation();
    var $container = $(this);
    var taskId = $container.data('task-id');
    var currentDeadline = $container.data('current-deadline') || '';
    
    // Remove any existing pickers
    $('.deadline-picker').remove();
    
    showDeadlinePicker($container, taskId, currentDeadline);
  });
  
  // Close dropdowns when clicking outside
  $(document).on('click', function(e) {
    if (!$(e.target).closest('.assignee-dropdown, .collaborators-dropdown, .deadline-picker').length) {
      $('.assignee-dropdown, .collaborators-dropdown, .deadline-picker').remove();
    }
  });
  
  console.log("✅ Task data display initialization complete");
}

// Initialize search functionality
function initSearch() {
  $(document).on("keyup", ".search-box input", function () {
    var searchTerm = $(this).val().toLowerCase();

    if (searchTerm === "") {
      // Reset to hierarchical view - show only main tasks
      initializeHierarchicalView();
    } else {
      // Show all matching tasks and their parents
      $(".task-row").each(function () {
        var $row = $(this);
        var taskTitle = $row
          .find(".task-title-text, .task-title-display")
          .text()
          .toLowerCase();
        var taskKey = $row.find(".task-key").text().toLowerCase();

        if (taskTitle.includes(searchTerm) || taskKey.includes(searchTerm)) {
          // Show matching task
          $row.show().removeClass("collapsed-subtask");

          // Also show all parent tasks up the hierarchy
          showParentHierarchy($row.data("task-id"));
        } else {
          // Hide non-matching tasks
          $row.hide().addClass("collapsed-subtask");
        }
      });
    }
  });

  console.log("✅ Search initialized");
}

// Show all parent tasks up the hierarchy for a given task
function showParentHierarchy(taskId) {
  $(".task-row").each(function () {
    var $row = $(this);
    var rowTaskId = $row.data("task-id");

    if (parseInt(rowTaskId) === parseInt(taskId)) {
      var parentId = parseInt($row.data("parent-id")) || 0;

      if (parentId > 0) {
        // Find and show parent
        $(".task-row").each(function () {
          var $parentRow = $(this);
          var parentTaskId = $parentRow.data("task-id");

          if (parseInt(parentTaskId) === parentId) {
            $parentRow.show().removeClass("collapsed-subtask");
            // Recursively show parent's parents
            showParentHierarchy(parentId);
            return false; // Break inner loop
          }
        });
      }
      return false; // Break outer loop
    }
  });
}

// Initialize filters
function initFilters() {
  $(document).on("click", ".filter-option", function (e) {
    e.preventDefault();
    var filterType = $(this).data("filter");

    if (filterType === "all") {
      // Reset to hierarchical view - show only main tasks
      initializeHierarchicalView();
    } else {
      // Apply filter while maintaining hierarchy
      $(".task-row").each(function () {
        var $row = $(this);
        var statusBadge = $row
          .find(".status-badge, .jira-status-badge")
          .text()
          .toLowerCase();
        var shouldShow = false;

        if (filterType === "todo" && statusBadge.includes("to do")) {
          shouldShow = true;
        } else if (
          filterType === "in_progress" &&
          statusBadge.includes("in progress")
        ) {
          shouldShow = true;
        } else if (filterType === "review" && statusBadge.includes("review")) {
          shouldShow = true;
        } else if (filterType === "done" && statusBadge.includes("done")) {
          shouldShow = true;
        }

        if (shouldShow) {
          $row.show().removeClass("collapsed-subtask");
          // Also show parent hierarchy for matching tasks
          showParentHierarchy($row.data("task-id"));
        } else {
          $row.hide().addClass("collapsed-subtask");
        }
      });
    }

    // Update filter button text
    var filterText = $(this).text();
    $(this)
      .closest(".dropdown")
      .find(".dropdown-toggle")
      .html('<i data-feather="filter" class="icon-16"></i> ' + filterText);
    if (typeof feather !== "undefined") {
      feather.replace();
    }
  });

  console.log("✅ Filters initialized");
}

// Initialize checkbox functionality
function initCheckboxes() {
  console.log("🔧 Initializing checkboxes...");

  // Select all checkbox
  $(document).on("change", "#select-all-tasks", function () {
    var isChecked = $(this).is(":checked");
    $(".task-checkbox").prop("checked", isChecked);

    console.log("Select all:", isChecked);
  });

  // Individual task checkboxes
  $(document).on("change", ".task-checkbox", function () {
    var $checkbox = $(this);
    var taskId = $checkbox.data("task-id");
    var isChecked = $checkbox.is(":checked");
    var $row = $checkbox.closest("tr");

    console.log("Task checkbox changed:", taskId, isChecked);

    // Add/remove selected class
    if (isChecked) {
      $row.addClass("task-selected");
    } else {
      $row.removeClass("task-selected");
    }

    // Update select all checkbox state
    updateSelectAllCheckbox();
  });

  function updateSelectAllCheckbox() {
    var totalCheckboxes = $(".task-checkbox").length;
    var checkedCheckboxes = $(".task-checkbox:checked").length;
    var $selectAll = $("#select-all-tasks");

    if (checkedCheckboxes === 0) {
      $selectAll.prop("checked", false).prop("indeterminate", false);
    } else if (checkedCheckboxes === totalCheckboxes) {
      $selectAll.prop("checked", true).prop("indeterminate", false);
    } else {
      $selectAll.prop("checked", false).prop("indeterminate", true);
    }
  }

  console.log("✅ Checkboxes initialized");
}

// Helper functions
function hideChildRows(parentId, parentLevel) {
  var $allRows = $(".task-row");
  var foundParent = false;
  var hiddenCount = 0;

  $allRows.each(function () {
    if (foundParent) {
      var currentLevel = parseInt($(this).data("level"));
      if (currentLevel > parentLevel) {
        $(this).hide().addClass("collapsed-child");
        hiddenCount++;
      } else {
        return false; // Stop when we reach same or higher level
      }
    }

    if ($(this).data("task-id") == parentId) {
      foundParent = true;
    }
  });

  console.log("Hidden", hiddenCount, "child rows for parent", parentId);
}

function showDirectChildRows(parentId, parentLevel) {
  var $allRows = $(".task-row");
  var foundParent = false;
  var shownCount = 0;

  $allRows.each(function () {
    if (foundParent) {
      var currentLevel = parseInt($(this).data("level"));
      var currentParentId = parseInt($(this).data("parent-id"));

      if (currentLevel > parentLevel) {
        // Only show direct children
        if (currentLevel === parentLevel + 1 && currentParentId == parentId) {
          $(this).show().removeClass("collapsed-child");
          shownCount++;
        }
      } else {
        return false; // Stop when we reach same or higher level
      }
    }

    if ($(this).data("task-id") == parentId) {
      foundParent = true;
    }
  });

  console.log("Shown", shownCount, "direct child rows for parent", parentId);
}

function updateTaskHierarchy(taskId, newIndex, oldIndex) {
  console.log("Updating hierarchy for task:", taskId);

  var $allRows = $(".task-row");
  var $movedRow = $('[data-task-id="' + taskId + '"]');
  var newParentId = 0;
  var newLevel = 0;

  // Determine new parent and level based on position
  if (newIndex > 0) {
    var $prevRow = $allRows.eq(newIndex - 1);
    var prevLevel = parseInt($prevRow.data("level")) || 0;

    // Simple logic: make it sibling of previous task
    newParentId = parseInt($prevRow.data("parent-id")) || 0;
    newLevel = prevLevel;
  }

  // Update the row data attributes
  $movedRow.attr("data-parent-id", newParentId);
  $movedRow.attr("data-level", newLevel);

  // Send AJAX request to update database
  var pathParts = window.location.pathname.split('/');
  var appIndex = pathParts.indexOf('dojob');
  var baseUrl = window.location.origin + '/' + pathParts.slice(1, appIndex + 1).join('/');

  $.ajax({
    url: baseUrl + "/tasks/save",
    type: "POST",
    dataType: "json",
    data: {
      id: taskId,
      parent_task_id: newParentId,
      project_id: projectId,
    },
    success: function (response) {
      if (response && response.success) {
        console.log("Task hierarchy updated successfully");
        showNotification("Task moved successfully!", "success");
      } else {
        console.error("Failed to update task hierarchy");
        setTimeout(function () {
          location.reload();
        }, 1500);
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX error:", error);
      setTimeout(function () {
        location.reload();
      }, 1500);
    },
  });
}

function showNotification(message, type) {
  var alertClass = type === "success" ? "alert-success" : "alert-danger";
  var icon = type === "success" ? "check-circle" : "x-circle";

  var notification = $(
    '<div class="alert ' +
      alertClass +
      ' alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
      '<i data-feather="' +
      icon +
      '" class="icon-16 me-2"></i>' +
      message +
      '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
      "</div>"
  );

  $("body").append(notification);

  // Initialize feather icons
  if (typeof feather !== "undefined") {
    feather.replace();
  }

  // Auto remove after 3 seconds
  setTimeout(function () {
    $(notification).fadeOut();
  }, 3000);
}

// Fix all existing add buttons to use proper styling and SVG icons
function fixExistingAddButtons() {
  console.log("🔧 Fixing existing add buttons - targeted approach...");

  // Find all existing add buttons but be more careful
  $(".add-subtask-jira, .add-subtask-btn, .btn-add-child, .add-root-task").each(
    function () {
      var $button = $(this);

      console.log("Fixing button:", $button.attr("class"));

      // Only remove green-related classes, keep functional classes
      $button.removeClass(
        "btn-success btn-primary btn-green bg-success bg-green"
      );

      // Only override background styling, preserve positioning and functionality
      $button.css({
        background: "transparent !important",
        "background-color": "transparent !important",
        "background-image": "none !important",
        border: "1px solid #DFE1E6 !important",
        color: "#6B778C !important",
        "box-shadow": "none !important",
      });

      // For subtask buttons only, update positioning
      if (
        $button.hasClass("add-subtask-jira") ||
        $button.hasClass("add-subtask-btn") ||
        $button.hasClass("btn-add-child")
      ) {
        $button.css({
          position: "absolute",
          right: "2px",
          top: "50%",
          transform: "translateY(-50%)",
          width: "18px",
          height: "18px",
          display: "flex",
          "align-items": "center",
          "justify-content": "center",
          "z-index": "10",
        });

        // Replace content with SVG for subtask buttons
        $button.html(`
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            `);
      }

      console.log("   ✅ Button styling fixed (preserved functionality)");
    }
  );

  console.log("✅ Fixed existing add buttons without breaking functionality");
}

// Save inline task
function saveInlineTask($input) {
  var title = $input.val().trim();
  if (!title) {
    $input.focus();
    return;
  }

  var $form = $input.closest(".inline-task-form");
  var parentId = $form.data("parent-id") || 0;
  var level = $form.data("level") || 0;

  // Show loading state
  $input.prop("disabled", true);
  $form.find(".save-inline-task").text("Saving...").prop("disabled", true);

  var pathParts = window.location.pathname.split('/');
  var appIndex = pathParts.indexOf('dojob');
  var baseUrl = window.location.origin + '/' + pathParts.slice(1, appIndex + 1).join('/');

  // AJAX call to save task
  $.ajax({
    url: baseUrl + "/tasks/save",
    type: "POST",
    dataType: "json",
    data: {
      title: title,
      project_id: projectId,
      parent_task_id: parentId > 0 ? parentId : 0,
      status_id: 1, // Default status
      description: "", // Empty description
      assigned_to: 0, // Unassigned
      inline_creation: 1, // Flag for inline creation
    },
    success: function (response) {
      console.log("✅ AJAX Response:", response);
      if (response && response.success) {
        console.log("✅ Task data:", response.data);
        // Create new task element
        var newTaskHtml = createTaskElement(response.data, level);
        $form.replaceWith(newTaskHtml);

        // Initialize feather icons for new element
        if (typeof feather !== "undefined") {
          feather.replace();
        }

        showNotification("Task created successfully!", "success");
      } else {
        showNotification(
          "Failed to create task: " + (response.message || "Unknown error"),
          "error"
        );
        $input.prop("disabled", false).focus();
        $form.find(".save-inline-task").text("Save").prop("disabled", false);
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX error:", error);
      showNotification("Error creating task: " + error, "error");
      $input.prop("disabled", false).focus();
      $form.find(".save-inline-task").text("Save").prop("disabled", false);
    },
  });
}

// Cancel inline task creation
function cancelInlineTask($input) {
  $input.closest(".inline-task-form").remove();
}

// Create task element HTML
function createTaskElement(task, level) {
  var levelColors = [
    "#22C55E",
    "#3B82F6",
    "#F59E0B",
    "#EF4444",
    "#8B5CF6",
    "#06B6D4",
    "#F97316",
    "#EC4899",
    "#84CC16",
    "#6366F1",
  ];
  var borderColor = levelColors[level] || "#6B7280";
  var marginLeft = level * 20;

  return `
        <div class="task-item level-${level}" 
             data-task-id="${task.id}" 
             data-level="${level}" 
             data-parent-id="${task.parent_task_id || 0}" 
             data-has-children="false"
             style="border-left: 4px solid ${borderColor}; margin-left: ${marginLeft}px;">
            <div class="task-content">
                <div class="task-header">
                    <div class="task-left">
                        <div class="jira-drag-handle" title="Drag to reorder">
                            <svg width="10" height="16" viewBox="0 0 10 16" fill="none">
                                <circle cx="2" cy="2" r="1" fill="#9CA3AF"/>
                                <circle cx="8" cy="2" r="1" fill="#9CA3AF"/>
                                <circle cx="2" cy="8" r="1" fill="#9CA3AF"/>
                                <circle cx="8" cy="8" r="1" fill="#9CA3AF"/>
                                <circle cx="2" cy="14" r="1" fill="#9CA3AF"/>
                                <circle cx="8" cy="14" r="1" fill="#9CA3AF"/>
                            </svg>
                        </div>
                        <span class="expand-placeholder"></span>
                        <div class="task-info">
                            <div class="task-title-row">
                                <span class="task-title">${task.title}</span>
                                <span class="task-key">C${level}-${
    task.id
  }</span>
                            </div>
                        </div>
                    </div>
                    <div class="task-right">
                        <span class="badge bg-primary status-badge" data-task-id="${
                          task.id
                        }">L${level + 1}</span>
                        <div class="task-actions">
                            <button class="btn btn-sm btn-link task-action-btn" title="View details">
                                <i data-feather="eye" class="icon-14"></i>
                            </button>
                            <button class="btn btn-sm btn-link task-action-btn" title="Edit">
                                <i data-feather="edit-2" class="icon-14"></i>
                            </button>
                            <button class="btn btn-sm btn-link task-action-btn add-subtask-btn" data-parent-id="${
                              task.id
                            }" title="Add subtask">
                                <i data-feather="plus" class="icon-14"></i>
                            </button>
                            <button class="btn btn-sm btn-link task-action-btn" title="Delete">
                                <i data-feather="trash-2" class="icon-14"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Global test function for debugging
window.testTaskListFunctions = function () {
  console.log("🧪 Testing task list functions...");

  // Test expand/collapse with both class names
  const expandButtons = $(".expand-toggle, .expand-toggle-jira");
  console.log(
    "Expand buttons found (.expand-toggle + .expand-toggle-jira):",
    expandButtons.length
  );

  // Test add buttons with all possible class names
  const addButtons = $(".btn-add-child, .add-subtask-btn, .add-subtask-jira");
  console.log(
    "Add buttons found (.btn-add-child + .add-subtask-btn + .add-subtask-jira):",
    addButtons.length
  );

  // Test event handlers
  if (expandButtons.length > 0) {
    console.log("✅ Testing expand/collapse click...");
    expandButtons.first().click();
  }

  if (addButtons.length > 0) {
    console.log("✅ Testing add button click...");
    addButtons.first().click();

    // Check if form was created
    setTimeout(function () {
      const forms = $(".inline-task-form");
      console.log("📝 Inline forms created:", forms.length);

      if (forms.length > 0) {
        console.log("✅ Form created successfully");

        // Test save button
        const saveButtons = $(".btn-save-task");
        const cancelButtons = $(".btn-cancel-task");
        const inputs = $(".new-task-title");

        console.log("Save buttons found:", saveButtons.length);
        console.log("Cancel buttons found:", cancelButtons.length);
        console.log("Input fields found:", inputs.length);

        if (inputs.length > 0) {
          inputs.first().val("Test Task Title");
          console.log("✅ Set test title in input");

          if (saveButtons.length > 0) {
            console.log("🔥 Testing save button click...");
            saveButtons.first().click();
          }
        }
      } else {
        console.error("❌ No forms created");
      }
    }, 200);
  }

  // Test inline editing
  const titleDisplays = $(".task-title-display");
  console.log("Title displays found:", titleDisplays.length);

  if (titleDisplays.length > 0) {
    console.log("✅ Testing inline editing click...");
    titleDisplays.first().click();

    // Check if editor appeared
    setTimeout(function () {
      const editors = $(".task-title-editor:visible");
      console.log("📝 Visible editors:", editors.length);
    }, 200);
  }

  return {
    expandButtons: expandButtons.length,
    addButtons: addButtons.length,
    titleDisplays: titleDisplays.length,
  };
};

// Add a simple test function to verify event handlers
window.testEventHandlers = function () {
  console.log("🔧 Testing event handlers...");

  // Test if event handlers are properly attached
  const handlers = {
    "expand-toggle": $._data(document, "events")
      ? $._data(document, "events").click
      : null,
    "btn-add-child": $._data(document, "events")
      ? $._data(document, "events").click
      : null,
    "btn-save-task": $._data(document, "events")
      ? $._data(document, "events").click
      : null,
    "btn-cancel-task": $._data(document, "events")
      ? $._data(document, "events").click
      : null,
    "task-title-display": $._data(document, "events")
      ? $._data(document, "events").click
      : null,
  };

  console.log("Event handlers:", handlers);

  // Test creating a form manually
  console.log("📝 Creating test form...");
  const testForm = `<tr class="inline-task-form test-form" data-parent-id="0" data-level="0" style="background: #F7F8F9; border-left: 3px solid #0052CC;">
        <td colspan="13">
            <input type="text" class="new-task-title" value="Test Task" style="margin: 10px; padding: 5px;">
            <button class="btn-save-task" style="margin: 5px; padding: 5px 10px; background: #0052CC; color: white; border: none;">Save</button>
            <button class="btn-cancel-task" style="margin: 5px; padding: 5px 10px;">Cancel</button>
        </td>
    </tr>`;

  $("#sortable-tasks").prepend(testForm);

  console.log("✅ Test form added. Try clicking Save/Cancel buttons.");
};

// Test function for comprehensive functionality check - run this in console
function testAllFunctionality() {
  console.log("🧪 === COMPREHENSIVE FUNCTIONALITY TEST ===");

  // Test 1: Check if all elements exist
  console.log("📊 Element Count Test:");
  console.log("- Total tasks:", $(".task-row").length);
  console.log(
    "- Expand/collapse buttons (.expand-toggle):",
    $(".expand-toggle").length
  );
  console.log(
    "- Expand/collapse buttons (.expand-toggle-jira):",
    $(".expand-toggle-jira").length
  );
  console.log(
    "- Add child buttons (.btn-add-child):",
    $(".btn-add-child").length
  );
  console.log(
    "- Add child buttons (.add-subtask-btn):",
    $(".add-subtask-btn").length
  );
  console.log(
    "- Add child buttons (.add-subtask-jira):",
    $(".add-subtask-jira").length
  );
  console.log(
    "- Add root buttons (.add-root-task):",
    $(".add-root-task").length
  );
  console.log("- Task title displays:", $(".task-title-display").length);
  console.log("- Task title editors:", $(".task-title-editor").length);
  console.log("- Status badges (.status-badge):", $(".status-badge").length);
  console.log(
    "- Status badges (.jira-status-badge):",
    $(".jira-status-badge").length
  );
  console.log(
    "- Drag handles (.jira-drag-handle):",
    $(".jira-drag-handle").length
  );

  // Test 2: Check event handlers
  console.log("\n🎯 Event Handler Test:");

  // Check expand/collapse handlers
  var expandButtons = $(".expand-toggle, .expand-toggle-jira");
  console.log("- Total expand buttons found:", expandButtons.length);

  // Check add button handlers
  var addButtons = $(".btn-add-child, .add-subtask-btn, .add-subtask-jira");
  console.log("- Total add buttons found:", addButtons.length);

  // Check inline editing handlers
  var titleDisplays = $(".task-title-display");
  console.log("- Title displays found:", titleDisplays.length);

  // Test 3: Simulate events (if any buttons exist)
  if (expandButtons.length > 0) {
    console.log("\n🔄 Testing expand/collapse...");
    var firstExpand = expandButtons.first();
    console.log("- First expand button:", firstExpand.length);
    console.log("- Class names:", firstExpand.attr("class"));
    // Don't actually click, just log
  }

  if (addButtons.length > 0) {
    console.log("\n➕ Testing add buttons...");
    var firstAdd = addButtons.first();
    console.log("- First add button:", firstAdd.length);
    console.log("- Class names:", firstAdd.attr("class"));
    console.log("- Data attributes:", firstAdd.data());
  }

  if (titleDisplays.length > 0) {
    console.log("\n📝 Testing title displays...");
    var firstTitle = titleDisplays.first();
    console.log("- First title display:", firstTitle.length);
    console.log("- Class names:", firstTitle.attr("class"));
    console.log("- Data attributes:", firstTitle.data());
    console.log("- Text content:", firstTitle.text().substring(0, 50) + "...");
  }

  // Test 4: Check for any existing inline forms
  var existingForms = $(".inline-task-form");
  console.log("\n📋 Existing inline forms:", existingForms.length);

  // Test 5: Check jQuery event delegation setup
  console.log("\n🔗 jQuery Event Setup:");
  var events = $._data(document, "events");
  if (events && events.click) {
    console.log("- Click handlers on document:", events.click.length);
    events.click.forEach(function (handler, index) {
      if (handler.selector) {
        console.log("  " + index + ":", handler.selector);
      }
    });
  } else {
    console.log("- No click events found on document");
  }

  console.log("\n✅ Test complete! Check above for any issues.");

  return {
    tasks: $(".task-row").length,
    expandButtons: expandButtons.length,
    addButtons: addButtons.length,
    titleDisplays: titleDisplays.length,
    inlineForms: existingForms.length,
    statusBadges: $(".status-badge, .jira-status-badge").length,
    dragHandles: $(".jira-drag-handle").length,
  };
}

// Test function for hierarchical functionality
function testHierarchicalView() {
  console.log("🧪 Testing hierarchical view...");

  // Count tasks by level
  var tasksByLevel = {};
  var visibleTasks = 0;
  var hiddenTasks = 0;

  $(".task-row").each(function () {
    var level = parseInt($(this).data("level")) || 0;
    var parentId = parseInt($(this).data("parent-id")) || 0;
    var isVisible = $(this).is(":visible");

    if (!tasksByLevel[level]) {
      tasksByLevel[level] = 0;
    }
    tasksByLevel[level]++;

    if (isVisible) {
      visibleTasks++;
    } else {
      hiddenTasks++;
    }

    console.log(
      "Task " +
        $(this).data("task-id") +
        ": Level=" +
        level +
        ", Parent=" +
        parentId +
        ", Visible=" +
        isVisible
    );
  });

  console.log("📊 Tasks by level:", tasksByLevel);
  console.log("👁️ Visible tasks:", visibleTasks);
  console.log("🙈 Hidden tasks:", hiddenTasks);

  // Test expand functionality
  var expandableButtons = $(".expand-toggle, .expand-toggle-jira").filter(
    function () {
      return $(this).closest("tr").is(":visible");
    }
  );

  console.log("🔄 Visible expand buttons:", expandableButtons.length);

  // Debug expand buttons in detail
  $(".expand-toggle, .expand-toggle-jira").each(function (index) {
    var $btn = $(this);
    var $row = $btn.closest("tr");
    var taskId = $btn.data("task-id");
    var isVisible = $btn.is(":visible");
    var rowVisible = $row.is(":visible");

    console.log(
      "Expand button " +
        index +
        ": TaskID=" +
        taskId +
        ", ButtonVisible=" +
        isVisible +
        ", RowVisible=" +
        rowVisible
    );
  });

  if (expandableButtons.length > 0) {
    console.log("✅ Hierarchical view is working properly");
    console.log("💡 Click an expand button to test showing subtasks");
  } else {
    console.log("⚠️ No expand buttons found in visible tasks");
    console.log("🔧 Let me try to fix this...");
    fixExpandButtons();
  }

  return {
    tasksByLevel: tasksByLevel,
    visibleTasks: visibleTasks,
    hiddenTasks: hiddenTasks,
    expandableButtons: expandableButtons.length,
  };
}

// Emergency function to fix missing expand buttons
function fixExpandButtons() {
  console.log("🚨 Emergency: Fixing expand buttons...");

  // Force show all expand buttons on visible main tasks
  $(".task-row:visible").each(function () {
    var $row = $(this);
    var taskId = $row.data("task-id");
    var parentId = parseInt($row.data("parent-id")) || 0;

    // Only for main tasks (level 0)
    if (parentId === 0) {
      var hasChildren = checkIfTaskHasChildren(taskId);
      if (hasChildren) {
        var $expandToggle = $row.find(".expand-toggle, .expand-toggle-jira");
        var $expandIcon = $row.find(".expand-icon");

        // Force show and reset
        $expandToggle.show().css("display", "inline-block");
        $expandIcon.css("transform", "rotate(0deg)");
        $row.removeClass("expanded");

        console.log("✅ Fixed expand button for task:", taskId);
      }
    }
  });

  // Check results
  var visibleExpandButtons = $(".expand-toggle, .expand-toggle-jira").filter(
    ":visible"
  ).length;
  console.log("🔧 Expand buttons now visible:", visibleExpandButtons);
}

// Check if a task has children
function checkIfTaskHasChildren(taskId) {
  var hasChildren = false;
  $(".task-row").each(function () {
    var parentId = parseInt($(this).data("parent-id")) || 0;
    if (parentId === parseInt(taskId)) {
      hasChildren = true;
      return false; // Break the loop
    }
  });
  return hasChildren;
}

// Truncate description helper
function truncateDescription(text) {
  if (text.length <= 30) {
    return text;
  }
  return text.substring(0, 30) + "...";
}




// Make functions globally accessible
window.initTaskDataDisplay = initTaskDataDisplay;
window.initSearch = initSearch;
window.initFilters = initFilters;
window.initCheckboxes = initCheckboxes;
window.showParentHierarchy = showParentHierarchy;
window.hideChildRows = hideChildRows;
window.showDirectChildRows = showDirectChildRows;
window.updateTaskHierarchy = updateTaskHierarchy;
window.showNotification = showNotification;
window.fixExistingAddButtons = fixExistingAddButtons;
window.saveInlineTask = saveInlineTask;
window.cancelInlineTask = cancelInlineTask;
window.createTaskElement = createTaskElement;
window.testHierarchicalView = testHierarchicalView;
window.fixExpandButtons = fixExpandButtons;
window.checkIfTaskHasChildren = checkIfTaskHasChildren;
window.testAllFunctionality = testAllFunctionality;
window.truncateDescription = truncateDescription;

window.addEventListener("DomContentLoaded", () => {
  console.log('initTaskDataDisplay starteddddddddddd----')
initTaskDataDisplay();
})

