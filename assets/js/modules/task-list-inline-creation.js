/**
 * Task List Inline Creation Module
 * Handles creating new tasks inline without page refresh
 */

// Initialize inline task creation
function initInlineTaskCreation() {
  console.log("🔧 Initializing inline task creation...");

  // Remove existing handlers
  $(document).off("click", ".add-root-task, .btn-add-child");
  $(document).off("click", ".btn-save-task, .btn-cancel-task");

  // Handle add root task button
  $(document).on("click", ".add-root-task", function (e) {
    e.preventDefault();
    console.log("✅ Add root task clicked!");
    var parentId = $(this).data("parent-id") || 0;
    showInlineTaskForm(parentId, 0);
  });

  // Handle add subtask buttons - Updated to match PHP class names
  $(document).on(
    "click",
    ".btn-add-child, .add-subtask-btn, .add-subtask-jira",
    function (e) {
      e.preventDefault();
      e.stopPropagation();
      console.log("✅ Add subtask clicked!");

      var parentId = $(this).data("parent-id");
      var $parentRow = $(this).closest("tr");
      var parentLevel = parseInt($parentRow.data("level")) || 0;
      var newLevel = parentLevel + 1;

      console.log("Parent ID:", parentId, "New Level:", newLevel);
      showInlineTaskForm(parentId, newLevel, $parentRow);
    }
  );

  // ADDITIONAL DEBUGGING - Catch ANY click on these buttons
  $(document).on(
    "click",
    '*[class*="add-subtask"], *[class*="btn-add-child"]',
    function (e) {
      console.log("🔥 ANY BUTTON WITH ADD CLASSES CLICKED!", this);
      console.log("Classes:", this.className);
      console.log("Data parent ID:", $(this).data("parent-id"));
    }
  );

  // Handle save task button - WITH DEBUG
  $(document).on("click", ".btn-save-task", function (e) {
    e.preventDefault();
    console.log("🔥 SAVE BUTTON CLICKED!"); // Debug

    var $form = $(this).closest(".inline-task-form");
    var $input = $form.find(".new-task-title");
    var title = $input.val().trim();
    var parentId = $form.data("parent-id");
    var level = $form.data("level");

    console.log("🔍 Form element:", $form[0]);
    console.log("🔍 Input element:", $input[0]);
    console.log("🔍 Form data:", { title, parentId, level }); // Debug

    if (title === "") {
      Swal.fire({
        icon: "warning",
        title: "Missing Title",
        text: "Please enter a task title",
        confirmButtonColor: "#0052CC",
      }).then(() => {
        $input.focus();
      });
      return;
    }

    console.log(
      "💾 Attempting to save new task:",
      title,
      "Parent:",
      parentId,
      "Level:",
      level
    );

    // Disable the save button during save
    $(this).prop("disabled", true).text("Saving...");

    // Save to server
    saveNewTask(title, parentId, function (success, taskData) {
      // Re-enable the save button
      $(".btn-save-task").prop("disabled", false).text("Save");

      if (success) {
        console.log("✅ Task saved successfully! Creating row dynamically...");

        // Create new task row dynamically instead of refreshing
        createNewTaskRow(taskData, parentId, level, $form);

        // Remove the form
        $form.remove();

        // Show success message
        console.log("✅ New task added to DOM without refresh!");
      } else {
        console.error("❌ Failed to save task");
        Swal.fire({
          icon: "error",
          title: "Save Failed",
          text: "Failed to save task",
          confirmButtonColor: "#0052CC",
        });
      }
    });
  });

  // Handle cancel task button - WITH DEBUG
  $(document).on("click", ".btn-cancel-task", function (e) {
    e.preventDefault();
    console.log("🔥 CANCEL BUTTON CLICKED!"); // Debug

    var $form = $(this).closest(".inline-task-form");
    $form.remove();
    console.log("✅ Form removed");
  });

  // Handle Enter key in task title input
  $(document).on("keydown", ".new-task-title", function (e) {
    if (e.which === 13) {
      e.preventDefault();
      console.log("🔥 ENTER KEY PRESSED!"); // Debug
      $(this).closest(".inline-task-form").find(".btn-save-task").click();
    } else if (e.which === 27) {
      e.preventDefault();
      console.log("🔥 ESCAPE KEY PRESSED!"); // Debug
      $(this).closest(".inline-task-form").find(".btn-cancel-task").click();
    }
  });

  console.log("✅ Inline task creation initialized");
}

// Show inline task form
function showInlineTaskForm(parentId, level, $insertAfter) {
  console.log(
    "📝 Showing inline task form for parent:",
    parentId,
    "level:",
    level
  );

  // Remove any existing forms first
  $(".inline-task-form").remove();

  // Create form HTML that matches what the event handlers expect
  var indentStyle = "margin-left: " + level * 20 + "px;";

  var formHtml =
    '<tr class="inline-task-form" data-parent-id="' +
    parentId +
    '" data-level="' +
    level +
    '" style="background: #F7F8F9; border-left: 3px solid #0052CC;">' +
    '<td style="width: 30px; padding: 8px 4px;"></td>' +
    '<td style="width: 40px; padding: 8px 4px; text-align: center;">' +
    '<div class="task-type-icon story-icon" title="New Task">' +
    '<svg width="16" height="16" viewBox="0 0 16 16" fill="none">' +
    '<rect x="1" y="1" width="14" height="14" rx="2" fill="#22C55E" stroke="#22C55E" stroke-width="1"/>' +
    '<path d="M4 8l2 2 6-6" stroke="white" stroke-width="2" fill="none"/>' +
    "</svg>" +
    "</div>" +
    "</td>" +
    '<td style="width: 80px; padding: 8px 12px; color: #6B778C;"><em>Auto-generated</em></td>' +
    '<td style="padding: 8px 12px;">' +
    '<div style="' +
    indentStyle +
    '">';

  if (level > 0) {
    formHtml +=
      '<span class="hierarchy-connector" style="margin-right: 4px; color: #8993a4;">└</span>';
  }

  formHtml +=
    '<input type="text" class="new-task-title" placeholder="Enter task title..." style="' +
    "border: 2px solid #0052CC; border-radius: 3px; padding: 6px 12px; font-size: 14px; width: 300px; outline: none;" +
    '" autofocus>' +
    '<div class="inline-form-actions" style="margin-top: 8px;">' +
    '<button class="btn-save-task" style="background: #0052CC; color: white; border: none; padding: 6px 12px; border-radius: 3px; margin-right: 8px; cursor: pointer; font-size: 12px;">Save</button>' +
    '<button class="btn-cancel-task" style="background: none; color: #6B778C; border: 1px solid #DFE1E6; padding: 6px 12px; border-radius: 3px; cursor: pointer; font-size: 12px;">Cancel</button>' +
    "</div>" +
    "</div>" +
    "</td>";

  // Add empty columns (updated for Description and Collaborators columns)
  for (var i = 0; i < 10; i++) {
    formHtml += "<td></td>";
  }

  formHtml += "</tr>";

  var $form = $(formHtml);

  if ($insertAfter && $insertAfter.length > 0) {
    // Insert after the parent task
    $insertAfter.after($form);
  } else {
    // Insert at the top for root tasks
    $("#sortable-tasks").prepend($form);
  }

  // Focus the input
  $form.find(".new-task-title").focus();

  console.log("✅ Inline form created and inserted");
}

// Save new task to server
function saveNewTask(title, parentId, callback) {
  console.log("💾 Saving new task to server:", title, parentId);
  console.log("🔧 Using baseUrl:", baseUrl);
  console.log("🔧 Using projectId:", projectId);

  var requestData = {
    title: title,
    parent_task_id: parentId || 0,
    project_id: projectId,
    status_id: 1, // Default status
    assigned_to: 0, // Unassigned
    description: "", // Empty description
    priority_id: 0, // Normal priority
    milestone_id: 0, // No milestone
    start_date: "", // No start date
    deadline: "", // No deadline
    labels: "", // No labels
    collaborators: "", // No collaborators
    inline_creation: 1, // Flag for inline creation
  };

  console.log("📤 Request data:", requestData);

  // Use the correct endpoint for the Rise CRM framework
  $.ajax({
    url: baseUrl + "tasks/save",
    method: "POST",
    data: requestData,
    dataType: "json",
    beforeSend: function () {
      console.log("📡 Sending AJAX request to:", baseUrl + "tasks/save");
    },
    success: function (response) {
      console.log("✅ AJAX Success - Raw response:", response);
      if (response && response.success) {
        console.log("✅ Task saved successfully:", response);
        callback(true, response.data);
      } else {
        console.error(
          "❌ Server returned error:",
          response.message || "Unknown error"
        );
        Swal.fire({
          icon: "error",
          title: "Save Error",
          text: "Error: " + (response.message || "Failed to save task"),
          confirmButtonColor: "#0052CC",
        });
        callback(false, null);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ AJAX Error - Status:", status);
      console.error("❌ AJAX Error - Error:", error);
      console.error("❌ AJAX Error - Response Text:", xhr.responseText);
      console.error("❌ AJAX Error - Status Code:", xhr.status);

      Swal.fire({
        icon: "error",
        title: "Connection Error",
        text: "Failed to save task. Check console for details.",
        confirmButtonColor: "#0052CC",
      });
      callback(false, null);
    },
  });
}

// Create new task row dynamically without page refresh
function createNewTaskRow(taskData, parentId, level, $form) {
  console.log("🆕 Creating new task row:", taskData);

  // Calculate the position where to insert the new row
  var $insertAfter;

  if (parentId && parentId > 0) {
    // Find the parent row
    var $parentRow = $('tr[data-task-id="' + parentId + '"]');

    if ($parentRow.length > 0) {
      // Find the last child of this parent or the parent itself
      var $lastChild = $parentRow;
      var parentLevel = parseInt($parentRow.data("level")) || 0;

      // Look for existing children and find the last one
      $parentRow
        .nextAll('tr[data-parent-id="' + parentId + '"]')
        .each(function () {
          $lastChild = $(this);
        });

      $insertAfter = $lastChild;
    } else {
      // If parent not found, insert after the form
      $insertAfter = $form;
    }
  } else {
    // Root level task - insert at the end of the table
    $insertAfter = $("#sortable-tasks tr").last();
  }

  // Create the new task row HTML (simplified version)
  var newTaskHtml = createTaskRowHtml(taskData, level);

  // Insert the new row
  if ($insertAfter && $insertAfter.length > 0) {
    $insertAfter.after(newTaskHtml);
  } else {
    $("#sortable-tasks").append(newTaskHtml);
  }

  // Re-initialize any JavaScript functionality for the new row
  initializeNewTaskRow(taskData.id);

  console.log("✅ New task row created and inserted!");
}

// Create HTML for a new task row
function createTaskRowHtml(taskData, level) {
  var taskId = taskData.id || taskData.task_id;
  var title = taskData.title || taskData.name || "New Task";
  var parentId = taskData.parent_task_id || 0;

  // Calculate indentation
  var indentStyle = "margin-left: " + level * 20 + "px;";

  // Hierarchy connector for child tasks
  var hierarchyConnector = "";
  if (level > 0) {
    hierarchyConnector =
      '<span class="hierarchy-connector" style="margin-right: 4px; color: #8993a4; font-family: monospace;">└</span>';
  }

  var html = `
        <tr class="jira-task-row task-row task-item level-${level}" 
            data-task-id="${taskId}" 
            data-level="${level}" 
            data-parent-id="${parentId}" 
            data-has-children="false">
            
            <!-- Checkbox column -->
            <td class="text-center" style="width: 30px; padding: 8px 4px;">
                <input type="checkbox" class="task-checkbox" data-task-id="${taskId}" style="transform: scale(1.1);">
            </td>
            
            <!-- Task type column -->
            <td class="task-type-cell" style="width: 40px; padding: 8px 4px; position: relative;">
                <div class="task-type-icon-wrapper" style="display: flex; justify-content: center; align-items: center; margin: 0 18px;">
                    ${
                      level == 0
                        ? `
                        <div class="task-type-icon epic-icon" title="Epic">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <rect x="1" y="1" width="14" height="14" rx="2" fill="#6B46C1" stroke="#6B46C1" stroke-width="1"/>
                                <path d="M4 8l2 2 6-6" stroke="white" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    `
                        : `
                        <div class="task-type-icon story-icon" title="Story">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <rect x="1" y="1" width="14" height="14" rx="2" fill="#22C55E" stroke="#22C55E" stroke-width="1"/>
                                <path d="M4 8l2 2 6-6" stroke="white" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    `
                    }
                </div>
                
                <!-- Add task button -->
                <span class="add-subtask-jira add-subtask-btn btn-add-child" data-parent-id="${taskId}" style="
                    position: absolute; 
                    right: 2px; 
                    top: 50%; 
                    transform: translateY(-50%); 
                    cursor: pointer; 
                    z-index: 10;
                    width: 18px;
                    height: 18px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0.9;
                    transition: all 0.2s;
                    background: transparent;
                    border-radius: 3px;
                    border: 1px solid transparent;
                    color: #6B778C;
                    font-size: 14px;
                    font-weight: bold;
                    line-height: 1;
                " title="Add subtask">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </span>
            </td>
            
            <!-- Task key column -->
            <td class="task-key-cell" style="width: 80px; padding: 8px 12px;">
                <a href="#" class="task-key-link" style="color: #0052CC; text-decoration: none; font-weight: 500;">${taskId}</a>
            </td>
            
            <!-- Summary column -->
            <td class="task-summary-cell" style="padding: 8px 12px;">
                <div class="task-summary-container" style="${indentStyle} display: flex; align-items: center;">
                    ${hierarchyConnector}
                    <div class="task-title-wrapper" style="flex: 1;">
                        <span class="task-title-display" data-task-id="${taskId}" style="
                            color: #172B4D; 
                            cursor: pointer; 
                            padding: 6px 8px; 
                            border-radius: 3px; 
                            transition: all 0.2s ease;
                            display: block;
                            min-height: 28px;
                            line-height: 16px;
                            font-size: 14px;
                            font-weight: 400;
                            border: 1px solid transparent;
                            background: transparent;
                        " 
                        onmouseover="this.style.backgroundColor='#F4F5F7'; this.style.borderColor='#DFE1E6';" 
                        onmouseout="this.style.backgroundColor='transparent'; this.style.borderColor='transparent';" 
                        title="Click to edit">${title}</span>
                        
                        <input type="text" class="task-title-editor" data-task-id="${taskId}" value="${title}" style="
                            display: none;
                            border: 2px solid #0052CC;
                            border-radius: 3px;
                            padding: 6px 8px;
                            font-size: 14px;
                            font-weight: 400;
                            width: 100%;
                            outline: none;
                            background: #FFFFFF;
                            color: #172B4D;
                            box-shadow: 0 0 0 2px rgba(0, 82, 204, 0.2);
                            transition: all 0.2s ease;
                            min-height: 28px;
                            line-height: 16px;
                        ">
                    </div>
                </div>
            </td>
            
            <!-- Description column - NEW -->
            <td class="task-description-cell" style="width: 200px; padding: 8px 12px;">
                <div class="task-description-container">
                    <div class="task-description-wrapper" style="flex: 1;">
                        <span class="task-description-display" data-task-id="${taskId}" style="
                            color: #6B778C; 
                            cursor: pointer; 
                            padding: 6px 8px; 
                            border-radius: 3px; 
                            transition: all 0.2s ease;
                            display: block;
                            min-height: 28px;
                            line-height: 16px;
                            font-size: 13px;
                            font-weight: 400;
                            border: 1px solid transparent;
                            background: transparent;
                            font-style: italic;
                        " 
                        onmouseover="this.style.backgroundColor='#F4F5F7'; this.style.borderColor='#DFE1E6';" 
                        onmouseout="this.style.backgroundColor='transparent'; this.style.borderColor='transparent';" 
                        title="Click to edit description">Click to add description...</span>
                        
                        <textarea class="task-description-editor" data-task-id="${taskId}" placeholder="Enter task description..." style="
                            display: none;
                            border: 2px solid #0052CC;
                            border-radius: 3px;
                            padding: 6px 8px;
                            font-size: 13px;
                            font-weight: 400;
                            width: 100%;
                            outline: none;
                            background: #FFFFFF;
                            color: #172B4D;
                            box-shadow: 0 0 0 2px rgba(0, 82, 204, 0.2);
                            transition: all 0.2s ease;
                            min-height: 60px;
                            resize: vertical;
                            font-family: inherit;
                        "></textarea>
                    </div>
                </div>
            </td>
            
            <!-- Additional columns with basic structure -->
            <td class="text-center" style="width: 120px; padding: 8px 12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span style="color: #42526E; font-size: 13px;">Add comment</span>
            </td>
            
            <td class="text-center" style="width: 100px; padding: 8px 12px;">
                <span class="jira-status-badge" style="
                    background: #DFE1E6;
                    color: #42526E;
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 11px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    display: inline-block;
                    min-width: 60px;
                ">TO DO</span>
            </td>
            
            <!-- Empty columns -->
            <td style="width: 100px; padding: 8px 12px;"></td> <!-- Category -->
            <td style="width: 100px; padding: 8px 12px;"></td> <!-- Assignee -->
            
            <!-- Collaborators column -->
            <td class="task-collaborators-cell" style="width: 120px; padding: 8px 12px;">
                <div class="task-collaborators-container" data-task-id="${taskId}">
                    <div class="collaborators-placeholder" style="
                        color: #6B778C;
                        cursor: pointer;
                        padding: 4px 8px;
                        border-radius: 3px;
                        font-size: 11px;
                        border: 1px dashed #DFE1E6;
                        text-align: center;
                        background: #F4F5F7;
                    " title="Click to add collaborators">Add</div>
                </div>
            </td>
            
            <!-- Deadline column -->
            <td class="task-deadline-cell" style="width: 120px; padding: 8px 12px;">
                <div class="task-deadline-container" data-task-id="${taskId}">
                    <div class="deadline-empty" style="
                        cursor: pointer;
                        padding: 8px;
                        text-align: center;
                        min-height: 28px;
                        border-radius: 3px;
                        transition: all 0.2s ease;
                        position: relative;
                    " title="Set deadline">&nbsp;</div>
                </div>
            </td>
            
            <!-- Priority column -->
            <td class="text-center" style="width: 80px; padding: 8px 12px;">
                <span class="jira-priority-badge" style="cursor: pointer; color: #FFAB00; display: inline-flex; align-items: center; gap: 4px;" title="Click to change priority">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                    </svg>
                    <span style="font-size: 11px; font-weight: 600;">MEDIUM</span>
                </span>
            </td>
            
            <!-- Labels column -->
            <td class="task-labels-cell" style="width: 100px; padding: 8px 12px;">
                <div class="task-labels-container" data-task-id="${taskId}" data-current-labels="">
                    <div class="labels-placeholder" style="
                        color: #6B778C;
                        cursor: pointer;
                        padding: 4px 8px;
                        border-radius: 3px;
                        font-size: 11px;
                        border: 1px dashed #DFE1E6;
                        text-align: center;
                        background: #F4F5F7;
                    " title="Click to add labels">Add</div>
                </div>
            </td>
            
            <!-- Level column -->
            <td class="task-level-cell" style="width: 80px; padding: 8px 12px; text-align: center;">
                ${
                  level === 0
                    ? `<div class="level-badge main-task" style="
                        background: #0052CC;
                        color: white;
                        padding: 3px 8px;
                        border-radius: 12px;
                        font-size: 11px;
                        font-weight: 600;
                        display: inline-block;
                    " title="Main Task">MAIN</div>`
                    : `<div class="level-badge sub-task" style="
                        background: #E3FCEF;
                        color: #006644;
                        padding: 3px 8px;
                        border-radius: 12px;
                        font-size: 11px;
                        font-weight: 600;
                        display: inline-block;
                    " title="Subtask - Level ${level}">L${level}</div>`
                }
            </td>
            
            <!-- Created column -->
            <td style="width: 120px; padding: 8px 12px; color: #6B778C; font-size: 13px;">
                ${new Date().toLocaleDateString()}
            </td>
            
            <td class="text-center" style="width: 40px; padding: 8px 4px;">
                <button class="task-menu-btn" style="background: none; border: none; cursor: pointer; padding: 4px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </td>
        </tr>
    `;

  return html;
}

// Initialize functionality for newly created task row
function initializeNewTaskRow(taskId) {
  // The event handlers are already bound using $(document).on(),
  // so they should work automatically for the new row

  // Add a subtle animation to highlight the new row
  var $newRow = $('tr[data-task-id="' + taskId + '"]');
  if ($newRow.length > 0) {
    $newRow.css("background-color", "#e3fcef");
    setTimeout(function () {
      $newRow.css("background-color", "");
    }, 2000);
  }
}

// Make functions globally accessible
window.initInlineTaskCreation = initInlineTaskCreation;
window.showInlineTaskForm = showInlineTaskForm;
window.saveNewTask = saveNewTask;
window.createNewTaskRow = createNewTaskRow;
window.createTaskRowHtml = createTaskRowHtml;
window.initializeNewTaskRow = initializeNewTaskRow;
