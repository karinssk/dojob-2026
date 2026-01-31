/**
 * Task List JavaScript Functionality
 * Clean, modular implementation
 */
console.log("🚀 TASK_LIST.JS IS LOADING!!!");

// Global variables
var taskListInitialized = false;

// Initialize task list functionality
function initTaskList() {
  if (taskListInitialized) {
    console.log("Task list already initialized");
    return;
  }

  console.log("🚀 Initializing Task List...");

  // Check dependencies
  if (typeof $ === "undefined") {
    console.error("❌ jQuery not loaded");
    return;
  }

  if (typeof Sortable === "undefined") {
    console.error("❌ SortableJS not loaded");
    return;
  }

  taskListInitialized = true;

  // Initial debug - check for buttons after DOM load
  setTimeout(function () {
    console.log("=== BUTTON DEBUG INFO ===");
    console.log("Sortable tasks:", $("#sortable-tasks").length);
    console.log("Expand toggles (.expand-toggle):", $(".expand-toggle").length);
    console.log(
      "Expand toggles (.expand-toggle-jira):",
      $(".expand-toggle-jira").length
    );
    console.log("Add buttons (.btn-add-child):", $(".btn-add-child").length);
    console.log(
      "Add buttons (.add-subtask-btn):",
      $(".add-subtask-btn").length
    );
    console.log(
      "Add buttons (.add-subtask-jira):",
      $(".add-subtask-jira").length
    );
    console.log("Task rows:", $(".jira-task-row").length);
    console.log("Status badges (.status-badge):", $(".status-badge").length);
    console.log(
      "Status badges (.jira-status-badge):",
      $(".jira-status-badge").length
    );
    console.log(
      "Drag handles (.jira-drag-handle):",
      $(".jira-drag-handle").length
    );
    console.log(
      "Collaborators containers (.task-collaborators-container):",
      $(".task-collaborators-container").length
    );
    console.log(
      "Assignee containers (.task-assignee-container):",
      $(".task-assignee-container").length
    );
    console.log(
      "Deadline containers (.task-deadline-container):",
      $(".task-deadline-container").length
    );

    // Check actual button HTML for first few buttons
    $(".btn-add-child, .add-subtask-btn, .add-subtask-jira").each(function (
      index
    ) {
      if (index < 3) {
        console.log("Button " + index + " HTML:", this.outerHTML);
        console.log("Button " + index + " classes:", this.className);
        console.log("Button " + index + " visible:", $(this).is(":visible"));
        console.log("Button " + index + " position:", $(this).position());
        console.log(
          "Button " + index + " dimensions:",
          $(this).width() + "x" + $(this).height()
        );
      }
    });
  }, 500);

  // Initialize all functionality
  initDragDrop();
  initStatusDropdowns();
  initPriorityDropdowns(); // Add priority dropdown functionality
  initExpandCollapse();
  initInlineTaskCreation();
  initInlineEditing();
  initCheckboxes();
  initSearch();
  initFilters();
  initTaskKeyModal(); // Add task key modal functionality
  initCommentModal(); // Add comment modal functionality
  initPagination(); // Add pagination functionality

  // Convert existing status badges to clickable dropdowns
  convertStatusBadgesToDropdowns();

  // Convert existing priority icons to clickable dropdowns
  convertPriorityIconsToDropdowns();

  // Fix all existing add buttons to match our new style
  fixExistingAddButtons();

  // Initialize task data display from database values
  initTaskDataDisplay();
  
  // Initialize labels display
  if (typeof initLabelsDisplay === 'function') {
    initLabelsDisplay();
  } else {
    console.warn("⚠️ initLabelsDisplay function not found");
  }

    // Add CSS for task key links
  addTaskKeyStyles();

  console.log(" Task List initialized successfully");
}

// Initialize drag and drop
function initDragDrop() {
  var sortableEl = document.getElementById("sortable-tasks");
  if (!sortableEl) {
    console.error("Sortable element not found");
    return;
  }

  var sortable = Sortable.create(sortableEl, {
    handle: ".jira-drag-handle",
    animation: 150,
    ghostClass: "dragging",
    dragClass: "dragging",
    chosenClass: "dragging",
    fallbackOnBody: true,
    swapThreshold: 0.65,
    direction: "vertical",

    onStart: function (evt) {
      console.log("Drag started");
      $(evt.item).addClass("dragging");
    },

    onEnd: function (evt) {
      console.log("Drag ended");
      $(evt.item).removeClass("dragging");

      var taskId = $(evt.item).data("task-id");
      var newIndex = evt.newIndex;
      var oldIndex = evt.oldIndex;

      if (newIndex !== oldIndex && taskId) {
        updateTaskHierarchy(taskId, newIndex, oldIndex);
      }
    },
  });

  console.log(" Drag & Drop initialized");
}

// Initialize inline task creation
function initInlineTaskCreation() {
  console.log("🔧 Initializing inline task creation...");

  // Remove existing handlers
  $(document).off("click", ".add-root-task, .btn-add-child");
  $(document).off("click", ".btn-save-task, .btn-cancel-task");

  // Handle add root task button
  $(document).on("click", ".add-root-task", function (e) {
    e.preventDefault();
    console.log(" Add root task clicked!");
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
      console.log(" Add subtask clicked!");

      // IMMEDIATE ALERT FOR DEBUGGING - Remove this in production
      console.log("BUTTON CLICKED! Classes: " + this.className);

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
        icon: 'warning',
        title: 'Missing Title',
        text: 'Please enter a task title',
        confirmButtonColor: '#0052CC'
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
        console.log(" Task saved successfully! Creating row dynamically...");

        // Create new task row dynamically instead of refreshing
        createNewTaskRow(taskData, parentId, level, $form);

        // Remove the form
        $form.remove();

        // Show success message
        console.log(" New task added to DOM without refresh!");
      } else {
        console.error("❌ Failed to save task");
        Swal.fire({
          icon: 'error',
          title: 'Save Failed',
          text: 'Failed to save task',
          confirmButtonColor: '#0052CC'
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
    console.log(" Form removed");
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

  console.log(" Inline task creation initialized");
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

  console.log(" Inline form created and inserted");
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

  // Get CSRF token
  var csrfToken = getCSRFToken();

  // Add CSRF token to request data
  if (csrfToken) {
    requestData.csrf_test_name = csrfToken;
  }

  // Use the correct endpoint for the Rise CRM framework
  $.ajax({
    url: baseUrl + "tasks/save",
    method: "POST",
    data: requestData,
    dataType: "json",
    headers: {
      "X-CSRF-TOKEN": csrfToken,
    },
    beforeSend: function () {
      console.log("📡 Sending AJAX request to:", baseUrl + "tasks/save");
    },
    success: function (response) {
      console.log(" AJAX Success - Raw response:", response);
      if (response && response.success) {
        console.log(" Task saved successfully:", response);
        callback(true, response.data);
      } else {
        console.error(
          "❌ Server returned error:",
          response.message || "Unknown error"
        );
        Swal.fire({
          icon: 'error',
          title: 'Save Error',
          text: "Error: " + (response.message || "Failed to save task"),
          confirmButtonColor: '#0052CC'
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
        icon: 'error',
        title: 'Connection Error',
        text: 'Failed to save task. Check console for details.',
        confirmButtonColor: '#0052CC'
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

  console.log(" New task row created and inserted!");
}

// Create priority dropdown HTML with placeholder that will be replaced with real data
function createPriorityDropdownHtml(taskId, currentPriorityId = 2) {
  // This will be populated with real data when the row is created
  return `
    <td class="text-center task-priority-cell" style="width: 80px; padding: 8px 12px;" data-task-id="${taskId}" data-current-priority="${currentPriorityId}">
      <div class="priority-loading" style="color: #6B778C; font-size: 11px;">Loading...</div>
    </td>
  `;
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
                        <line x1="12" y1="5" x2="12" y2="19">100000</line>
                        <line x1="5" y1="12" x2="19" y2="12">20000000</line>
                    </svg>
                </span>
            </td>
            

















            <!-- Task key column -->
            <td class="task-key-cell" style="width: 80px; padding: 8px 12px;">
                <a href="addCommentBtn()" class="addCommentBtn" data-task-id="${taskId}" style="color: #0052CC; text-decoration: none; font-weight: 500; cursor: pointer;" title="Click to view task details">${taskId}</a>
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
            
            <!-- Comments column -->
            <td class="text-center task-comments-cell" style="width: 120px; padding: 8px 12px; cursor: pointer; transition: all 0.2s ease;" 
                title="Click to add comment" 
                onmouseover="this.style.backgroundColor='#F4F5F7'" 
                onmouseout="this.style.backgroundColor='transparent'">
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
                    " title="Set deadline" onmouseover="this.innerHTML='Set deadline'; this.style.color='#6B778C'; this.style.fontSize='11px';" onmouseout="this.innerHTML=''; this.style.color='transparent';">&nbsp;</div>
                </div>
            </td>
            
            ${createPriorityDropdownHtml(taskId, 2)}
            
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

// Save task title to server
function saveTaskTitle(taskId, title, callback) {
  console.log("💾 Saving task title to server:", taskId, title);

  // Use the direct PHP endpoint to avoid CodeIgniter session conflicts
  // Get the base path without index.php
  var directUrl =
    baseUrl.replace("/index.php/", "/") +
    "update-new-feature/update_task_title_direct.php";
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
      console.log(" Task title saved successfully:", response);
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
    baseUrl.replace("/index.php/", "/") +
    "update-new-feature/update_task_description_direct.php";
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
      console.log(" Task description saved successfully:", response);
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

  // Get base URL by removing everything after the domain and app folder
  var pathParts = window.location.pathname.split("/");
  var appIndex = pathParts.indexOf("");
  var baseUrl =
    window.location.origin + "/" + pathParts.slice(1, appIndex + 1).join("/");
  var directUrl =
    baseUrl + "/update-new-feature/update_task_assignee_direct.php";

  console.log("🔗 Assignee Direct URL:", directUrl);
  console.log("🔗 Base URL parts:", baseUrl);

  $.ajax({
    url: directUrl,
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      task_id: taskId,
      assignee_id: assigneeId,
    }),
    success: function (response) {
      console.log(" Task assignee saved successfully:", response);
      if (response.success) {
        callback(true, response.assignee);
      } else {
        console.error("❌ Server returned error:", response.message);
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

// Get users list for assignee dropdown
function getUsersList(callback) {
  console.log("🔗 Getting users list...");

  // First try the direct endpoint
  var pathParts = window.location.pathname.split("/");
  var appIndex = pathParts.indexOf("");
  var baseUrl =
    window.location.origin + "/" + pathParts.slice(1, appIndex + 1).join("/");
  var directUrl = baseUrl + "/get_users_list.php";

  console.log("🔗 Users list URL:", directUrl);

  $.ajax({
    url: directUrl,
    type: "GET",
    dataType: "json",
    timeout: 10000,
    success: function (response) {
      console.log(" Direct users response:", response);
      if (response && response.success && response.users) {
        console.log(" Direct users list:", response.users);
        callback(response.users);
      } else {
        console.warn("⚠️ Direct endpoint failed, using fallback users");
        // Fallback to hardcoded users based on what we know from debug
        var fallbackUsers = [
          {
            id: 1,
            name: "Nattapol Phasook",
            initials: "NP",
          },
          {
            id: 3,
            name: "MR SEIN BO TINT MIKE",
            initials: "MS",
          },
          {
            id: 4,
            name: "Arocha Ketbumrung",
            initials: "AK",
          },
          {
            id: 5,
            name: "Benjawan Chomsuk",
            initials: "BC",
          },
          {
            id: 6,
            name: "Rungarun Lanok",
            initials: "RL",
          },
        ];
        callback(fallbackUsers);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ Failed to fetch users:", error);
      console.error("❌ Status:", status);
      console.error("❌ Response text:", xhr.responseText);

      // Use fallback data
      console.warn("🔄 Using fallback user data");
      var fallbackUsers = [
        {
          id: 1,
          name: "Nattapol Phasook",
          initials: "NP",
        },
        {
          id: 3,
          name: "MR SEIN BO TINT MIKE",
          initials: "MS",
        },
        {
          id: 4,
          name: "Arocha Ketbumrung",
          initials: "AK",
        },
        {
          id: 5,
          name: "Benjawan Chomsuk",
          initials: "BC",
        },
        {
          id: 6,
          name: "Rungarun Lanok",
          initials: "RL",
        },
      ];
      callback(fallbackUsers);
    },
  });
}

// Show assignee dropdown
function showAssigneeDropdown($container, taskId, currentAssignee) {
  console.log("🔽 Showing assignee dropdown for task:", taskId);

  // Get users list
  getUsersList(function (users) {
    if (users.length === 0) {
      console.error("❌ No users available");
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
      "max-height: 200px; " +
      "overflow-y: auto;" +
      '">';

    // Add "Unassigned" option
    dropdownHtml +=
      '<div class="assignee-option" data-assignee-id="0" style="' +
      "padding: 8px 12px; " +
      "cursor: pointer; " +
      "border-bottom: 1px solid #F4F5F7; " +
      "font-size: 13px; " +
      (currentAssignee == 0 ? "background: #E3FCEF; color: #00875A;" : "") +
      '" onmouseover="this.style.backgroundColor=\'#F4F5F7\'" onmouseout="this.style.backgroundColor=\'' +
      (currentAssignee == 0 ? "#E3FCEF" : "transparent") +
      "'\">" +
      '<span style="color: #6B778C;">Unassigned</span>' +
      "</div>";

    // Add users
    users.forEach(function (user) {
      var isSelected = currentAssignee == user.id;
      dropdownHtml +=
        '<div class="assignee-option" data-assignee-id="' +
        user.id +
        '" style="' +
        "padding: 8px 12px; " +
        "cursor: pointer; " +
        "border-bottom: 1px solid #F4F5F7; " +
        "display: flex; " +
        "align-items: center; " +
        "font-size: 13px; " +
        (isSelected ? "background: #E3FCEF; color: #00875A;" : "") +
        '" onmouseover="this.style.backgroundColor=\'#F4F5F7\'" onmouseout="this.style.backgroundColor=\'' +
        (isSelected ? "#E3FCEF" : "transparent") +
        "'\">" +
        '<span class="user-avatar" style="' +
        "background: #0052CC; " +
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
        "</span>" +
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
      var newAssigneeId = parseInt($(this).data("assignee-id"));

      console.log("👤 Selected assignee:", newAssigneeId);

      // Update assignee
      saveTaskAssignee(taskId, newAssigneeId, function (success, assigneeData) {
        if (success) {
          console.log(" Assignee updated successfully");
          updateAssigneeDisplay($container, newAssigneeId, assigneeData, users);
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
      });
    });
  });
}

// Update assignee display after successful save
function updateAssigneeDisplay($container, assigneeId, assigneeData, allUsers) {
  $container.data("current-assignee", assigneeId);

  var displayHtml = "";
  if (assigneeId === 0) {
    displayHtml =
      '<div class="assignee-placeholder" style="' +
      "color: #6B778C; " +
      "cursor: pointer; " +
      "padding: 4px 8px; " +
      "border-radius: 3px; " +
      "font-size: 12px; " +
      "border: 1px dashed #DFE1E6; " +
      "text-align: center;" +
      '" title="Click to assign">Unassigned</div>';
  } else {
    // Find user in the list
    var user = allUsers.find((u) => u.id == assigneeId);
    if (user) {
      displayHtml =
        '<div class="assignee-avatar" data-assignee-id="' +
        assigneeId +
        '">' +
        '<span class="assignee-initials" style="' +
        "background: #0052CC; " +
        "color: white; " +
        "border-radius: 50%; " +
        "width: 24px; " +
        "height: 24px; " +
        "display: inline-flex; " +
        "align-items: center; " +
        "justify-content: center; " +
        "font-size: 10px; " +
        "font-weight: 600; " +
        "cursor: pointer; " +
        "border: 2px solid #DFE1E6;" +
        '" title="' +
        user.name +
        ' - Click to change assignee">' +
        user.initials +
        "</span>" +
        "</div>";
    }
  }

  // Update the container content (remove dropdown and old content)
  $container.html(displayHtml);
}

// === COLLABORATORS FUNCTIONALITY ===

// Show collaborators dropdown
function showCollaboratorsDropdown($container, taskId, currentCollaborators) {
  console.log("🤝 Showing collaborators dropdown for task:", taskId);

  // Get users list
  getUsersList(function (users) {
    if (users.length === 0) {
      console.error("❌ No users available for collaborators");
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

    // Create dropdown HTML
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
      "max-height: 300px; " +
      "overflow-y: auto;" +
      '">';

    // Header
    dropdownHtml +=
      '<div style="padding: 8px 12px; border-bottom: 1px solid #F4F5F7; background: #FAFBFC;">' +
      '<strong style="font-size: 12px; color: #6B778C;">Select Collaborators</strong>' +
      "</div>";

    // Add users with checkboxes
    users.forEach(function (user) {
      var isSelected = currentCollaboratorIds.includes(user.id);
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
        "</span>" +
        "<span>" +
        user.name +
        "</span>" +
        "</div>";
    });

    // Action buttons
    dropdownHtml +=
      '<div style="padding: 8px 12px; border-top: 1px solid #F4F5F7; display: flex; gap: 8px;">' +
      '<button class="save-collaborators-btn" style="' +
      "background: #0052CC; color: white; border: none; padding: 4px 12px; border-radius: 3px; font-size: 12px; cursor: pointer;" +
      '">Save</button>' +
      '<button class="cancel-collaborators-btn" style="' +
      "background: #F4F5F7; color: #6B778C; border: none; padding: 4px 12px; border-radius: 3px; font-size: 12px; cursor: pointer;" +
      '">Cancel</button>' +
      "</div>";

    dropdownHtml += "</div>";

    // Position container relatively and add dropdown
    $container.css("position", "relative");
    $container.append(dropdownHtml);

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
          console.log(" Collaborators updated successfully");
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
      });
    });

    // Handle Cancel button
    $(".cancel-collaborators-btn").on("click", function (e) {
      e.stopPropagation();
      $(".collaborators-dropdown").remove();
    });
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

  // Get base URL by removing everything after the domain and app folder
  var pathParts = window.location.pathname.split("/");
  var appIndex = pathParts.indexOf("");
  var baseUrl =
    window.location.origin + "/" + pathParts.slice(1, appIndex + 1).join("/");
  var directUrl =
    baseUrl + "/update-new-feature/update_task_collaborators_direct.php";

  console.log("🔗 Collaborators Direct URL:", directUrl);

  $.ajax({
    url: directUrl,
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      task_id: taskId,
      collaborators: collaboratorsString,
    }),
    success: function (response) {
      console.log(" Task collaborators saved successfully:", response);
      if (response.success) {
        callback(true);
      } else {
        console.error("❌ Server returned error:", response.message);
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
}

// === LABELS FUNCTIONALITY ===

// Show labels dropdown
function showLabelsDropdown($container, taskId, currentLabels) {
  console.log("🏷️ Showing labels dropdown for task:", taskId);
  console.log("🏷️ Current labels:", currentLabels);
  console.log("🏷️ Container:", $container);

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

  console.log("🏷️ Parsed current label IDs:", currentLabelIds);

  // Get base URL for API calls
  var pathParts = window.location.pathname.split("/");
  var appIndex = pathParts.indexOf("");
  var baseUrl =
    window.location.origin + "/" + pathParts.slice(1, appIndex + 1).join("/");

  console.log("🔗 Base URL:", baseUrl);

  // Fetch existing labels from database
  $.ajax({
    url: baseUrl + "/update-new-feature/get_labels_direct.php",
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
          icon: 'error',
          title: 'Load Error',
          text: 'Failed to load labels',
          confirmButtonColor: '#0052CC'
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Failed to fetch labels:", error);
      Swal.fire({
        icon: 'error',
        title: 'Connection Error',
        text: 'Failed to load labels',
        confirmButtonColor: '#0052CC'
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

  // Create dropdown HTML
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
    "max-height: 350px; " +
    "overflow-y: auto;" +
    '">';

  // Header
  dropdownHtml +=
    '<div style="padding: 8px 12px; border-bottom: 1px solid #F4F5F7; background: #FAFBFC;">' +
    '<strong style="font-size: 12px; color: #6B778C;">Select Labels (Task: ' +
    taskId +
    ")</strong>" +
    "</div>";

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

  // Action buttons with data attributes instead of inline handlers
  console.log("🔧 Creating buttons with taskId:", taskId);
  dropdownHtml +=
    '<div style="padding: 8px 12px; border-top: 1px solid #F4F5F7; display: flex; gap: 8px;">' +
    '<button class="save-labels-btn" data-task-id="' +
    taskId +
    '" style="' +
    "background: #0052CC; color: white; border: none; padding: 4px 12px; border-radius: 3px; font-size: 12px; cursor: pointer;" +
    '">Save</button>' +
    '<button class="cancel-labels-btn" style="' +
    "background: #F4F5F7; color: #6B778C; border: none; padding: 4px 12px; border-radius: 3px; font-size: 12px; cursor: pointer;" +
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

  console.log(" Dropdown appended to container");

  // Use setTimeout to ensure events are bound after DOM update
  setTimeout(function () {
    console.log("🎯 Setting up event handlers...");

    // Remove ALL existing event handlers for buttons to avoid conflicts
    $(
      ".labels-dropdown .save-labels-btn, .labels-dropdown .cancel-labels-btn"
    ).off();

    // Use a unique namespace for these events
    $(".labels-dropdown .save-labels-btn").on("click.labelsave", function (e) {
      console.log("🔥🔥🔥 SAVE BUTTON CLICKED WITH NAMESPACE!");
      e.preventDefault();
      e.stopImmediatePropagation();

      var buttonTaskId = $(this).data("task-id");
      console.log("� Task ID from button:", buttonTaskId);

      // Get selected label IDs
      var selectedLabelIds = [];
      $(".labels-dropdown .label-option input[type='checkbox']:checked").each(
        function () {
          var labelId = parseInt(
            $(this).closest(".label-option").data("label-id")
          );
          console.log(" Selected label ID:", labelId);
          if (!isNaN(labelId)) {
            selectedLabelIds.push(labelId);
          }
        }
      );

      console.log("📝 All selected IDs:", selectedLabelIds);

      if (selectedLabelIds.length === 0) {
        console.log("⚠️ No labels selected");
      }

      // Test direct AJAX call
      console.log("� Making direct AJAX call...");

      var pathParts = window.location.pathname.split("/");
      var appIndex = pathParts.indexOf("");
      var baseUrl =
        window.location.origin +
        "/" +
        pathParts.slice(1, appIndex + 1).join("/");
      var directUrl =
        baseUrl + "/update-new-feature/update_task_labels_direct.php";

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
          console.log(" AJAX Success:", response);

          if (response && response.success) {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Labels saved successfully!',
              confirmButtonColor: '#0052CC',
              timer: 1500,
              showConfirmButton: false,
              toast: true,
              position: 'top-end'
            });

            // Update display
            var $container = $(
              '.task-labels-container[data-task-id="' + buttonTaskId + '"]'
            );

            // For now, just update with selected IDs as simple display
            var displayText =
              selectedLabelIds.length > 0
                ? "Labels: " + selectedLabelIds.join(", ")
                : "No labels";

            $container.html(
              '<div style="padding: 4px 8px; background: #E3FCEF; border-radius: 3px;">' +
                displayText +
                "</div>"
            );
          } else {
            console.error(
              "❌ Server error:",
              response ? response.message : "Unknown error"
            );
            Swal.fire({
              icon: 'error',
              title: 'Save Failed',
              text: "Failed to save: " + (response ? response.message : "Unknown error"),
              confirmButtonColor: '#0052CC'
            });
          }

          // Remove dropdown
          $(".labels-dropdown").remove();
        },
        error: function (xhr, status, error) {
          console.error("❌ AJAX Error:", error);
          console.error("❌ Status:", status);
          console.error("❌ Response:", xhr.responseText);
          Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: "AJAX Error: " + error,
            confirmButtonColor: '#0052CC'
          });

          // Remove dropdown
          $(".labels-dropdown").remove();
        },
      });

      return false;
    });

    $(".labels-dropdown .cancel-labels-btn").on(
      "click.labelcancel",
      function (e) {
        console.log("🔥 CANCEL BUTTON CLICKED WITH NAMESPACE!");
        e.preventDefault();
        e.stopImmediatePropagation();
        $(".labels-dropdown").remove();
        return false;
      }
    );

    console.log(" Event handlers bound with unique namespace");

    // Test if button exists and is clickable
    var saveBtn = $(".labels-dropdown .save-labels-btn");
    console.log("🔍 Save button found:", saveBtn.length);
    console.log("🔍 Save button visible:", saveBtn.is(":visible"));
    console.log("🔍 Save button task-id:", saveBtn.data("task-id"));
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
  console.log("🔥🔥🔥 handleSaveLabels called for task:", taskId);
  console.log("🔥🔥🔥 typeof taskId:", typeof taskId);

  // Get selected label IDs
  var selectedLabelIds = [];
  $(".label-option input[type='checkbox']:checked").each(function () {
    var labelId = parseInt($(this).closest(".label-option").data("label-id"));
    console.log("📝 Found checked label ID:", labelId);
    if (!isNaN(labelId)) {
      selectedLabelIds.push(labelId);
    }
  });

  console.log("🏷️ Total selected label IDs:", selectedLabelIds);

  // Get available labels for display conversion
  var $container = $('.task-labels-container[data-task-id="' + taskId + '"]');
  console.log("📦 Container found:", $container.length);

  // Fetch labels to get display data
  var pathParts = window.location.pathname.split("/");
  var appIndex = pathParts.indexOf("");
  var baseUrl =
    window.location.origin + "/" + pathParts.slice(1, appIndex + 1).join("/");

  console.log(
    "🔗 Fetching labels from:",
    baseUrl + "/update-new-feature/get_labels_direct.php"
  );

  $.ajax({
    url: baseUrl + "/update-new-feature/get_labels_direct.php",
    type: "GET",
    success: function (response) {
      console.log("📋 Labels fetch response:", response);
      if (response.success && response.labels) {
        // Save labels
        console.log("🚀 Calling saveTaskLabels...");
        saveTaskLabels(taskId, selectedLabelIds, function (success) {
          console.log("💾 Save callback result:", success);
          if (success) {
            console.log(" Labels updated successfully");
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
              icon: 'error',
              title: 'Update Failed',
              text: 'Failed to update labels',
              confirmButtonColor: '#0052CC'
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
        icon: 'error',
        title: 'Load Error',
        text: 'Failed to load labels data',
        confirmButtonColor: '#0052CC'
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

  // Get base URL by removing everything after the domain and app folder
  var pathParts = window.location.pathname.split("/");
  var appIndex = pathParts.indexOf("");
  var baseUrl =
    window.location.origin + "/" + pathParts.slice(1, appIndex + 1).join("/");
  var directUrl = baseUrl + "/update-new-feature/update_task_labels_direct.php";

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
      console.log(" Task labels saved successfully:", response);
      if (response && response.success) {
        callback(true);
      } else {
        console.error(
          "❌ Server returned error:",
          response ? response.message : "Unknown error"
        );
        Swal.fire({
          icon: 'error',
          title: 'Save Failed',
          text: "Failed to save labels: " + (response ? response.message : "Unknown error"),
          confirmButtonColor: '#0052CC'
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
        icon: 'error',
        title: 'Connection Error',
        text: "Failed to save labels: " + error + " (Status: " + xhr.status + ")",
        confirmButtonColor: '#0052CC'
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

// === DEADLINE FUNCTIONALITY ===

// Show deadline picker
function showDeadlinePicker($container, taskId, currentDeadline) {
  console.log("📅 Showing deadline picker for task:", taskId);

  // Create full calendar picker HTML
  var pickerHtml =
    '<div class="deadline-picker" style="' +
    "position: absolute; " +
    "top: 100%; " +
    "left: 0; " +
    "background: white; " +
    "border: 1px solid #DFE1E6; " +
    "border-radius: 8px; " +
    "box-shadow: 0 8px 16px rgba(0,0,0,0.15); " +
    "z-index: 9999; " +
    "min-width: 280px; " +
    "padding: 16px;" +
    '">';

  // Calendar header
  var today = new Date();
  var currentMonth = today.getMonth();
  var currentYear = today.getFullYear();

  // If there's an existing deadline, start from that month
  if (currentDeadline && currentDeadline !== "0000-00-00") {
    var existingDate = new Date(currentDeadline);
    if (!isNaN(existingDate.getTime())) {
      currentMonth = existingDate.getMonth();
      currentYear = existingDate.getFullYear();
    }
  }

  var monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];

  pickerHtml +=
    '<div class="calendar-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">';
  pickerHtml +=
    '<button class="prev-month" style="background: none; border: none; cursor: pointer; padding: 4px; border-radius: 3px;">';
  pickerHtml +=
    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B778C" stroke-width="2"><polyline points="15,18 9,12 15,6"></polyline></svg>';
  pickerHtml += "</button>";
  pickerHtml +=
    '<span class="month-year" style="font-weight: 600; color: #172B4D;">' +
    monthNames[currentMonth] +
    " " +
    currentYear +
    "</span>";
  pickerHtml +=
    '<button class="next-month" style="background: none; border: none; cursor: pointer; padding: 4px; border-radius: 3px;">';
  pickerHtml +=
    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B778C" stroke-width="2"><polyline points="9,18 15,12 9,6"></polyline></svg>';
  pickerHtml += "</button>";
  pickerHtml += "</div>";

  // Day headers
  pickerHtml +=
    '<div class="calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; margin-bottom: 12px;">';
  var dayHeaders = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
  dayHeaders.forEach(function (day) {
    pickerHtml +=
      '<div style="text-align: center; font-size: 11px; color: #6B778C; font-weight: 600; padding: 4px;">' +
      day +
      "</div>";
  });

  // Generate calendar days
  var firstDay = new Date(currentYear, currentMonth, 1);
  var lastDay = new Date(currentYear, currentMonth + 1, 0);
  var startDate = new Date(firstDay);
  startDate.setDate(startDate.getDate() - firstDay.getDay());

  for (var i = 0; i < 42; i++) {
    // 6 weeks
    var date = new Date(startDate);
    date.setDate(startDate.getDate() + i);

    var isCurrentMonth = date.getMonth() === currentMonth;
    var isToday = date.toDateString() === today.toDateString();
    var isSelected = false;

    if (currentDeadline && currentDeadline !== "0000-00-00") {
      var selectedDate = new Date(currentDeadline);
      isSelected = date.toDateString() === selectedDate.toDateString();
    }

    var dayStyle =
      "text-align: center; padding: 6px; cursor: pointer; border-radius: 3px; font-size: 13px; transition: all 0.2s;";

    if (!isCurrentMonth) {
      dayStyle += "color: #C1C7D0;";
    } else if (isSelected) {
      dayStyle += "background: #0052CC; color: white; font-weight: 600;";
    } else if (isToday) {
      dayStyle += "background: #E3FCEF; color: #00875A; font-weight: 600;";
    } else {
      dayStyle += "color: #172B4D;";
      dayStyle += "hover: background: #F4F5F7;";
    }
    // Fix timezone issue: format date manually instead of using toISOString()
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var day = String(date.getDate()).padStart(2, "0");
    var dateString = year + "-" + month + "-" + day;

    pickerHtml +=
      '<div class="calendar-day" data-date="' +
      dateString +
      '" style="' +
      dayStyle +
      "\" onmouseover=\"if(this.style.background !== 'rgb(0, 82, 204)' && this.style.background !== 'rgb(227, 252, 239)') this.style.background='#F4F5F7'\" onmouseout=\"if(this.style.background === 'rgb(244, 245, 247)') this.style.background='transparent'\">";
    pickerHtml += date.getDate();
    pickerHtml += "</div>";
  }

  pickerHtml += "</div>";

  // Action buttons
  pickerHtml += '<div style="display: flex; gap: 8px; margin-top: 12px;">';
  pickerHtml +=
    '<button class="today-btn" style="background: #F4F5F7; color: #6B778C; border: 1px solid #DFE1E6; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; flex: 1;">Today</button>';

  if (currentDeadline && currentDeadline !== "0000-00-00") {
    pickerHtml +=
      '<button class="clear-deadline-btn" style="background: #E74C3C; color: white; border: none; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; flex: 1;">Clear</button>';
  }

  pickerHtml +=
    '<button class="cancel-deadline-btn" style="background: #F4F5F7; color: #6B778C; border: 1px solid #DFE1E6; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; flex: 1;">Cancel</button>';
  pickerHtml += "</div>";

  pickerHtml += "</div>";

  // Position container relatively and add picker
  $container.css("position", "relative");
  $container.append(pickerHtml);

  // Handle day clicks
  $(".calendar-day").on("click", function (e) {
    e.stopPropagation();

    var selectedDate = $(this).data("date");
    console.log("📅 Selected date:", selectedDate);

    // Save deadline
    saveTaskDeadline(taskId, selectedDate, function (success) {
      if (success) {
        console.log(" Deadline updated successfully");
        updateDeadlineDisplay($container, selectedDate);
      } else {
        console.error("❌ Failed to update deadline");
        Swal.fire({
          icon: 'error',
          title: 'Update Failed',
          text: 'Failed to update deadline',
          confirmButtonColor: '#0052CC'
        });
      }

      // Remove picker
      $(".deadline-picker").remove();
    });
  });

  // Handle Today button
  $(".today-btn").on("click", function (e) {
    e.stopPropagation();

    // Fix timezone issue: format today's date manually instead of using toISOString()
    var today = new Date();
    var year = today.getFullYear();
    var month = String(today.getMonth() + 1).padStart(2, "0");
    var day = String(today.getDate()).padStart(2, "0");
    var todayDate = year + "-" + month + "-" + day;

    saveTaskDeadline(taskId, todayDate, function (success) {
      if (success) {
        console.log(" Deadline set to today");
        updateDeadlineDisplay($container, todayDate);
      } else {
        console.error("❌ Failed to set deadline");
        Swal.fire({
          icon: 'error',
          title: 'Set Failed',
          text: 'Failed to set deadline',
          confirmButtonColor: '#0052CC'
        });
      }

      $(".deadline-picker").remove();
    });
  });

  // Handle Clear button (if present)
  $(".clear-deadline-btn").on("click", function (e) {
    e.stopPropagation();

    saveTaskDeadline(taskId, "", function (success) {
      if (success) {
        console.log(" Deadline cleared successfully");
        updateDeadlineDisplay($container, "");
      } else {
        console.error("❌ Failed to clear deadline");
        Swal.fire({
          icon: 'error',
          title: 'Clear Failed',
          text: 'Failed to clear deadline',
          confirmButtonColor: '#0052CC'
        });
      }

      $(".deadline-picker").remove();
    });
  });

  // Handle Cancel button
  $(".cancel-deadline-btn").on("click", function (e) {
    e.stopPropagation();
    $(".deadline-picker").remove();
  });

  // Handle month navigation
  $(".prev-month, .next-month").on("click", function (e) {
    e.stopPropagation();
    // For simplicity, just reload the picker
    // In a real implementation, you'd update the calendar view
    $(".deadline-picker").remove();
    showDeadlinePicker($container, taskId, currentDeadline);
  });
}

// Save task deadline to server
function saveTaskDeadline(taskId, deadline, callback) {
  console.log("💾 Saving task deadline to server:", taskId, deadline);

  // Get base URL by removing everything after the domain and app folder
  var pathParts = window.location.pathname.split("/");
  var appIndex = pathParts.indexOf("");
  var baseUrl =
    window.location.origin + "/" + pathParts.slice(1, appIndex + 1).join("/");
  var directUrl =
    baseUrl + "/update-new-feature/update_task_deadline_direct.php";

  console.log("🔗 Deadline Direct URL:", directUrl);

  $.ajax({
    url: directUrl,
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      task_id: taskId,
      deadline: deadline,
    }),
    success: function (response) {
      console.log(" Task deadline saved successfully:", response);
      if (response.success) {
        callback(true);
      } else {
        console.error("❌ Server returned error:", response.message);
        callback(false);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ Failed to save task deadline:", error);
      console.error("Response:", xhr.responseText);
      callback(false);
    },
  });
}

// Update deadline display after successful save
function updateDeadlineDisplay($container, deadline) {
  $container.data("current-deadline", deadline);

  var displayHtml = "";

  if (!deadline || deadline === "" || deadline === "0000-00-00") {
    // Empty cell with hover text
    displayHtml =
      '<div class="deadline-empty" style="' +
      "cursor: pointer; " +
      "padding: 8px; " +
      "text-align: center; " +
      "min-height: 28px; " +
      "border-radius: 3px; " +
      "transition: all 0.2s ease; " +
      "position: relative;" +
      "\" title=\"Set deadline\" onmouseover=\"this.innerHTML='Set deadline'; this.style.color='#6B778C'; this.style.fontSize='11px';\" onmouseout=\"this.innerHTML=''; this.style.color='transparent';\">&nbsp;</div>";
  } else {
    // Format the deadline for display
    // Fix timezone issue by parsing date components explicitly
    var deadlineDate;
    if (deadline.includes("-")) {
      // Parse YYYY-MM-DD format to avoid timezone issues
      var parts = deadline.split("-");
      deadlineDate = new Date(
        parseInt(parts[0]),
        parseInt(parts[1]) - 1,
        parseInt(parts[2])
      );
    } else {
      deadlineDate = new Date(deadline);
    }

    var formattedDate = deadlineDate.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });

    // Check if deadline is overdue
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    deadlineDate.setHours(0, 0, 0, 0);

    var isOverdue = deadlineDate < today;
    var backgroundColor = isOverdue ? "#FFEBEE" : "#F4F5F7";
    var borderColor = isOverdue ? "#F44336" : "#DFE1E6";
    var textColor = isOverdue ? "#D32F2F" : "#172B4D";

    displayHtml =
      '<div class="deadline-display" style="' +
      "color: " +
      textColor +
      "; " +
      "cursor: pointer; " +
      "padding: 4px 8px; " +
      "border-radius: 3px; " +
      "font-size: 12px; " +
      "border: 1px solid " +
      borderColor +
      "; " +
      "background: " +
      backgroundColor +
      "; " +
      "text-align: center;" +
      '" title="Click to change deadline">' +
      formattedDate +
      "</div>";
  }

  // Update the container content
  $container.html(displayHtml);
}

// Initialize task data display from database values
function initTaskDataDisplay() {
  console.log("🔄 Initializing task data display from database values...");

  // Get users list to populate assignee and collaborator displays
  getUsersList(function (users) {
    console.log(" Users loaded for data display:", users.length);

    // Update all assignee displays
    $(".task-assignee-container").each(function () {
      var $container = $(this);
      var assigneeId = parseInt($container.data("current-assignee")) || 0;
      var taskId = $container.data("task-id");

      console.log(
        "📝 Updating assignee display for task:",
        taskId,
        "assignee:",
        assigneeId
      );

      if (assigneeId > 0) {
        updateAssigneeDisplay($container, assigneeId, null, users);
      }
    });

    // Update all collaborator displays
    $(".task-collaborators-container").each(function () {
      var $container = $(this);
      var collaboratorsString = $container.data("current-collaborators") || "";
      var taskId = $container.data("task-id");

      console.log(
        "👥 Updating collaborators display for task:",
        taskId,
        "collaborators:",
        collaboratorsString
      );

      if (collaboratorsString) {
        var collaboratorIds = collaboratorsString
          .split(",")
          .map(function (id) {
            return parseInt(id.trim());
          })
          .filter(function (id) {
            return id > 0;
          });

        if (collaboratorIds.length > 0) {
          updateCollaboratorsDisplay($container, collaboratorIds, users);
        }
      }
    });
  });

  // Update all labels displays
  $(".task-labels-container").each(function () {
    var $container = $(this);
    var labelsString = $container.data("current-labels") || "";
    var taskId = $container.data("task-id");

    console.log(
      "🏷️ Updating labels display for task:",
      taskId,
      "labels:",
      labelsString
    );

    if (labelsString) {
      var labelIds = labelsString
        .split(",")
        .map(function (id) {
          return parseInt(id.trim());
        })
        .filter(function (id) {
          return !isNaN(id) && id > 0;
        });

      if (labelIds.length > 0) {
        // Fetch label details from server
        var pathParts = window.location.pathname.split("/");
        var appIndex = pathParts.indexOf("");
        var baseUrl =
          window.location.origin +
          "/" +
          pathParts.slice(1, appIndex + 1).join("/");

        $.ajax({
          url: baseUrl + "/update-new-feature/get_labels_direct.php",
          type: "GET",
          success: function (response) {
            if (response.success && response.labels) {
              // Filter labels to only include the ones assigned to this task
              var taskLabels = response.labels.filter(function (label) {
                return labelIds.includes(parseInt(label.id));
              });

              if (taskLabels.length > 0) {
                updateLabelsDisplay($container, taskLabels);
              }
            }
          },
          error: function (xhr, status, error) {
            console.error("Failed to fetch labels for task:", taskId, error);
          },
        });
      }
    }
  });

  // Add click handlers for assignee dropdowns
  $(document).on("click", ".task-assignee-container", function (e) {
    e.stopPropagation();
    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentAssignee = $container.data("current-assignee") || 0;

    // Remove any existing dropdowns
    $(".assignee-dropdown").remove();

    showAssigneeDropdown($container, taskId, currentAssignee);
  });

  // Add click handlers for collaborators dropdowns
  $(document).on("click", ".task-collaborators-container", function (e) {
    e.stopPropagation();
    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentCollaborators = $container.data("current-collaborators") || "";

    // Remove any existing dropdowns
    $(".collaborators-dropdown").remove();

    showCollaboratorsDropdown($container, taskId, currentCollaborators);
  });

  // Add click handlers for deadline pickers
  $(document).on("click", ".task-deadline-container", function (e) {
    e.stopPropagation();
    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentDeadline = $container.data("current-deadline") || "";

    // Remove any existing pickers
    $(".deadline-picker").remove();

    showDeadlinePicker($container, taskId, currentDeadline);
  });

  // Close dropdowns when clicking outside
  $(document).on("click", function (e) {
    if (
      !$(e.target).closest(
        ".assignee-dropdown, .collaborators-dropdown, .deadline-picker"
      ).length
    ) {
      $(
        ".assignee-dropdown, .collaborators-dropdown, .deadline-picker"
      ).remove();
    }
  });

  console.log(" Task data display initialization complete");
}

// Initialize status dropdowns
function initStatusDropdowns() {
  $(document).on("click", ".status-option", function (e) {
    e.preventDefault();
    console.log("🔥 STATUS OPTION CLICKED!");
    var taskId = $(this).data("task-id");
    var newStatus = $(this).data("status");
    var $badge = $(
      '.status-badge[data-task-id="' +
        taskId +
        '"], .jira-status-badge[data-task-id="' +
        taskId +
        '"]'
    );

    console.log("🎯 Status change details:", {
      taskId: taskId,
      newStatus: newStatus,
      element: this,
      badgeFound: $badge.length,
    });

    // Map string status to status_id
    var statusId = 1; // default to "to do"
    switch (newStatus) {
      case "to_do":
        statusId = 1;
        break;
      case "in_progress":
        statusId = 2;
        break;
      case "done":
        statusId = 3;
        break;
    }

    console.log("🚀 Sending AJAX request:", {
      task_id: taskId,
      status_id: statusId,
    });

    // Use the same URL pattern as title update
    var directUrl =
      baseUrl.replace("/index.php/", "/") +
      "update-new-feature/update_task_status_direct.php";
    console.log("🔗 Status update URL:", directUrl);

    $.ajax({
      url: directUrl,
      type: "POST",
      dataType: "json",
      data: {
        task_id: taskId,
        status_id: statusId,
      },
      success: function (response) {
        console.log("Status update response:", response);
        if (response && response.success) {
          // Update badge appearance
          var statusClass = "secondary";
          var statusText = "TO DO";

          switch (newStatus) {
            case "done":
              statusClass = "success";
              statusText = "DONE";
              break;
            case "in_progress":
              statusClass = "warning";
              statusText = "IN PROGRESS";
              break;
            default:
              statusClass = "secondary";
              statusText = "TO DO";
          }

          $badge
            .removeClass("bg-success bg-warning bg-secondary bg-info")
            .addClass("bg-" + statusClass)
            .text(statusText);

          showNotification(
            " Task status updated to " + statusText,
            "success"
          );
        } else {
          console.error("Status update failed:", response);
          showNotification(
            "❌ Failed to update task status: " +
              (response.message || "Unknown error"),
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", error, xhr.responseText);
        showNotification("❌ Error updating task status: " + error, "error");
      },
    });
  });

  console.log(" Status dropdowns initialized");
}

// Initialize priority dropdowns
function initPriorityDropdowns() {
  $(document).on("click", ".priority-option", function (e) {
    e.preventDefault();
    console.log("🔥 PRIORITY OPTION CLICKED!");
    var taskId = $(this).data("task-id");
    var newPriority = $(this).data("priority");
    var $badge = $(
      '.priority-badge[data-task-id="' +
        taskId +
        '"], .jira-priority-badge[data-task-id="' +
        taskId +
        '"]'
    );

    console.log("🎯 Priority change details:", {
      taskId: taskId,
      newPriority: newPriority,
      element: this,
      badgeFound: $badge.length,
    });

    console.log("🚀 Sending priority AJAX request:", {
      task_id: taskId,
      priority_id: newPriority,
    });

    // Use the same URL pattern as other updates
    var directUrl =
      baseUrl.replace("/index.php/", "/") +
      "update-new-feature/update_task_priority_direct.php";
    console.log("🔗 Priority update URL:", directUrl);

    $.ajax({
      url: directUrl,
      type: "POST",
      dataType: "json",
      data: {
        task_id: taskId,
        priority_id: newPriority,
      },
      success: function (response) {
        console.log("Priority update response:", response);
        if (response && response.success) {
          // Update badge appearance
          $badge.css("color", response.priority_color);
          $badge.find("span").text(response.priority_text);

          showNotification(
            " Task priority updated to " + response.priority_text,
            "success"
          );
        } else {
          console.error("Priority update failed:", response);
          showNotification(
            "❌ Failed to update task priority: " +
              (response.message || "Unknown error"),
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Priority AJAX error:", error, xhr.responseText);
        showNotification("❌ Error updating task priority: " + error, "error");
      },
    });
  });

  console.log(" Priority dropdowns initialized");
}

// Convert static status badges to clickable dropdowns
function convertStatusBadgesToDropdowns() {
  console.log("🔄 Converting static status badges to dropdowns...");

  // Find all static status badges that aren't already dropdowns
  $(".jira-status-badge, .status-badge").each(function () {
    var $badge = $(this);

    // Skip if already converted
    if ($badge.data("dropdown-converted")) {
      return;
    }

    // Get task ID from the row
    var $row = $badge.closest("tr");
    var taskId = $row.data("task-id");

    if (!taskId) {
      console.log("⚠️ No task ID found for badge:", $badge);
      return;
    }

    console.log("🎯 Converting badge for task:", taskId);

    // Get current status from badge text
    var currentText = $badge.text().trim().toUpperCase();
    var currentStatus = "to_do";
    if (currentText.includes("PROGRESS")) currentStatus = "in_progress";
    else if (currentText.includes("DONE")) currentStatus = "done";

    // Wrap badge in dropdown
    var dropdownHtml = `
            <div class="dropdown">
                <span class="jira-status-badge dropdown-toggle" 
                      data-bs-toggle="dropdown" 
                      data-task-id="${taskId}"
                      style="cursor: pointer; ${$badge.attr("style") || ""}"
                      title="Click to change status">
                    ${$badge.html()}
                </span>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item status-option" href="#" data-task-id="${taskId}" data-status="to_do">
                        <span class="badge bg-secondary">TO DO</span>
                    </a></li>
                    <li><a class="dropdown-item status-option" href="#" data-task-id="${taskId}" data-status="in_progress">
                        <span class="badge bg-warning">IN PROGRESS</span>
                    </a></li>
                    <li><a class="dropdown-item status-option" href="#" data-task-id="${taskId}" data-status="done">
                        <span class="badge bg-success">DONE</span>
                    </a></li>
                </ul>
            </div>
        `;

    // Replace the badge with dropdown
    $badge.replaceWith(dropdownHtml);
    $badge.data("dropdown-converted", true);

    console.log(" Converted badge for task:", taskId);
  });

  console.log("🎉 Status badge conversion complete!");
}

// Convert static priority icons to clickable dropdowns
function convertPriorityIconsToDropdowns() {
  console.log("🔄 Converting priority icons using real database data...");

  // Call the modular function that fetches real data
  if (typeof window.convertPriorityIconsToDropdowns === "function") {
    window.convertPriorityIconsToDropdowns();
  } else {
    console.error("❌ Modular convertPriorityIconsToDropdowns not found");

    // Fallback: Find all priority cells and replace with loading placeholders
    $("td").each(function () {
      var $cell = $(this);
      var $svg = $cell.find('svg[stroke="#FFAB00"]'); // Find lightning bolt icons

      if ($svg.length > 0) {
        // Get task ID from the row
        var $row = $cell.closest("tr");
        var taskId = $row.data("task-id");

        if (!taskId) {
          console.log("⚠️ No task ID found for priority icon:", $cell);
          return;
        }

        console.log("🎯 Converting priority icon for task:", taskId);

        // Replace with loading placeholder that will be updated with real data
        $cell.html(createPriorityDropdownHtml(taskId, 2));

        console.log(" Converted priority icon for task:", taskId);
      }
    });
  }

  console.log("🎉 Priority icon conversion complete!");
}

// Initialize expand/collapse functionality
function initExpandCollapse() {
  // Remove any existing handlers
  $(document).off("click", ".expand-toggle, .expand-toggle-jira");

  // Add click handler for expand/collapse buttons - Updated to match PHP class names
  $(document).on("click", ".expand-toggle, .expand-toggle-jira", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $toggle = $(this);
    var taskId = $toggle.data("task-id");
    var $icon = $toggle.find(".expand-icon");
    var $row = $toggle.closest("tr");

    console.log("🔄 Expand/collapse clicked for task:", taskId);

    if (!taskId) {
      console.error("No task ID found for expand toggle");
      return;
    }

    // Check current state
    var isExpanded = $row.hasClass("expanded");

    if (isExpanded) {
      // Collapse: hide direct children only
      hideDirectChildren(taskId);
      $row.removeClass("expanded");

      // Rotate icon to collapsed state (pointing right)
      $icon.css("transform", "rotate(0deg)");

      console.log(" Collapsed task:", taskId);
    } else {
      // Expand: show direct children only
      showDirectChildren(taskId);
      $row.addClass("expanded");

      // Rotate icon to expanded state (pointing down)
      $icon.css("transform", "rotate(90deg)");

      console.log(" Expanded task:", taskId);
    }
    
    // Refresh pagination after expand/collapse
    setTimeout(function() {
      refreshPagination();
    }, 100);
  });

  // Initialize hierarchical view: Show only main tasks, hide all subtasks
  initializeHierarchicalView();

  console.log(" Expand/collapse initialized");
}

// Initialize hierarchical view: Show only main tasks, hide subtasks
function initializeHierarchicalView() {
  console.log("🏗️ Initializing hierarchical view...");

  var mainTasksShown = 0;
  var subtasksHidden = 0;

  // Hide all subtasks (tasks with parent_id > 0 or level > 0)
  $(".task-row").each(function () {
    var $row = $(this);
    var parentId = parseInt($row.data("parent-id")) || 0;
    var level = parseInt($row.data("level")) || 0;
    var taskId = $row.data("task-id");

    if (parentId > 0 || level > 0) {
      // This is a subtask - hide it
      $row.hide().addClass("collapsed-subtask");
      subtasksHidden++;
      console.log(
        "Hidden subtask:",
        taskId,
        "Parent:",
        parentId,
        "Level:",
        level
      );
    } else {
      // This is a main task - show it
      $row.show().removeClass("collapsed-subtask expanded");
      mainTasksShown++;

      // Check if it has children and set expand button state
      var hasChildren = checkIfTaskHasChildren(taskId);
      if (hasChildren) {
        // Ensure expand button is visible and set to collapsed state
        var $expandIcon = $row.find(".expand-icon");
        var $expandToggle = $row.find(".expand-toggle, .expand-toggle-jira");

        $row.removeClass("expanded").addClass("has-children");
        $expandIcon.css("transform", "rotate(0deg)");
        $expandToggle.show(); // Make sure expand button is visible

        console.log("Main task with children ready for expansion:", taskId);
      } else {
        $row.removeClass("has-children");
        console.log("Main task without children:", taskId);
      }
    }
  });

  console.log(" Hierarchical view initialized:");
  console.log("   - Main tasks shown:", mainTasksShown);
  console.log("   - Subtasks hidden:", subtasksHidden);

  // Debug: Check expand buttons after initialization
  setTimeout(function () {
    var visibleExpandButtons = $(".expand-toggle, .expand-toggle-jira").filter(
      ":visible"
    ).length;
    console.log("🔍 Visible expand buttons after init:", visibleExpandButtons);
    
    // Refresh pagination after hierarchical view is set
    refreshPagination();
  }, 100);
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

// Show only direct children of a task
function showDirectChildren(parentTaskId) {
  var shownCount = 0;

  $(".task-row").each(function () {
    var $row = $(this);
    var taskParentId = parseInt($row.data("parent-id")) || 0;

    // Show only direct children (parent_id matches exactly)
    if (taskParentId === parseInt(parentTaskId)) {
      $row.show().removeClass("collapsed-subtask");
      shownCount++;
      console.log(
        "Shown direct child:",
        $row.data("task-id"),
        "of parent:",
        parentTaskId
      );
    }
  });

  console.log("Shown", shownCount, "direct children for parent:", parentTaskId);
}

// Hide only direct children of a task
function hideDirectChildren(parentTaskId) {
  var hiddenCount = 0;

  $(".task-row").each(function () {
    var $row = $(this);
    var taskParentId = parseInt($row.data("parent-id")) || 0;
    var taskId = $row.data("task-id");

    // Hide direct children
    if (taskParentId === parseInt(parentTaskId)) {
      $row.hide().addClass("collapsed-subtask");

      // Also collapse any expanded children and hide their children
      if ($row.hasClass("expanded")) {
        $row.removeClass("expanded");
        $row.find(".expand-icon").css("transform", "rotate(0deg)");
        hideDirectChildren(taskId); // Recursively hide children
      }

      hiddenCount++;
      console.log("Hidden direct child:", taskId, "of parent:", parentTaskId);
    }
  });

  console.log(
    "Hidden",
    hiddenCount,
    "direct children for parent:",
    parentTaskId
  );
}

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

    // Set the editor value to current display text
    var currentText = $display.text().trim();
    $editor.val(currentText);

    // Hide display, show editor - using CSS class to override !important
    $display.css("display", "none");
    $editor.addClass("editing");

    // Focus the editor
    setTimeout(function () {
      $editor.focus();
      $editor.select();
    }, 10);
  });

  // Save title on blur or Enter
  $(document).on("blur keydown", ".task-title-editor", function (e) {
    if (e.type === "blur" || (e.type === "keydown" && e.which === 13)) {
      e.preventDefault();

      var $editor = $(this);
      var $display = $editor.siblings(".task-title-display");
      var taskId = $editor.data("task-id");
      var newTitle = $editor.val().trim();
      var originalTitle = $display.text().trim();

      console.log("💾 Title blur save triggered:", {
        taskId: taskId,
        newTitle: newTitle,
        originalTitle: originalTitle,
        event: e.type,
      });

      // Always exit edit mode first to prevent getting stuck
      $editor.removeClass("editing");
      $display.css("display", "block");

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
          console.log(" Title saved successfully!");
          $display.text(newTitle);
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
    }
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

        // Get values for saving
        var taskId = $editor.data("task-id");
        var newTitle = $editor.val().trim();
        var originalTitle = $display.text().trim();

        // Only save if there are actual changes
        if (newTitle && newTitle !== originalTitle) {
          console.log("📤 Saving changes via click outside...");
          saveTaskTitle(taskId, newTitle, function (success) {
            if (success) {
              console.log(" Title saved via click outside!");
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

    console.log(
      "🏷️ Starting labels edit for task:",
      taskId,
      "Current labels:",
      currentLabels
    );

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
      !$(e.target).closest(
        ".collaborators-dropdown, .task-collaborators-container"
      ).length
    ) {
      $(".collaborators-dropdown").remove();
    }

    if (
      !$(e.target).closest(".deadline-picker, .task-deadline-container").length
    ) {
      $(".deadline-picker").remove();
    }

    if (
      !$(e.target).closest(".labels-dropdown, .task-labels-container").length
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

      console.log(" Description editor focused with full text");
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
        console.log(" Description saved successfully!");

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

  console.log(" Inline editing initialized");
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

  console.log(" Checkboxes initialized");
}

// Global test function for debugging
window.testTaskListFunctions = function () {
  console.log(" Testing task list functions...");

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
    console.log(" Testing expand/collapse click...");
    expandButtons.first().click();
  }

  if (addButtons.length > 0) {
    console.log(" Testing add button click...");
    addButtons.first().click();

    // Check if form was created
    setTimeout(function () {
      const forms = $(".inline-task-form");
      console.log("📝 Inline forms created:", forms.length);

      if (forms.length > 0) {
        console.log(" Form created successfully");

        // Test save button
        const saveButtons = $(".btn-save-task");
        const cancelButtons = $(".btn-cancel-task");
        const inputs = $(".new-task-title");

        console.log("Save buttons found:", saveButtons.length);
        console.log("Cancel buttons found:", cancelButtons.length);
        console.log("Input fields found:", inputs.length);

        if (inputs.length > 0) {
          inputs.first().val("Test Task Title");
          console.log(" Set test title in input");

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
    console.log(" Testing inline editing click...");
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

  console.log(" Test form added. Try clicking Save/Cancel buttons.");
};

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
    
    // Refresh pagination after search
    setTimeout(function() {
      refreshPagination();
    }, 100);
  });

  console.log(" Search initialized");
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

// Add CSS styles for task key links
function addTaskKeyStyles() {
  var styles = `
    <style>
      .task-key-link {
        transition: all 0.2s ease !important;
        border-radius: 3px !important;
        padding: 4px 6px !important;
        display: inline-block !important;
      }
      
      .task-key-link:hover {
        background-color: #E3F2FD !important;
        color: #1976D2 !important;
        text-decoration: none !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(0, 82, 204, 0.2) !important;
      }
      
      .task-key-link:active {
        transform: translateY(0) !important;
        box-shadow: 0 1px 2px rgba(0, 82, 204, 0.3) !important;
      }
      
      .task-key-cell {
        position: relative !important;
      }
      
      .task-key-cell::before {
        content: "🔗" !important;
        position: absolute !important;
        left: 2px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        opacity: 0 !important;
        transition: opacity 0.2s ease !important;
        font-size: 10px !important;
      }
      
      .task-key-cell:hover::before {
        opacity: 0.5 !important;
      }
      
      .task-comments-cell {
        transition: all 0.2s ease !important;
        border-radius: 3px !important;
      }
      
      .task-comments-cell:hover {
        background-color: #F4F5F7 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(66, 82, 110, 0.1) !important;
      }
      
      .task-comments-cell:hover svg {
        stroke: #0052CC !important;
      }
      
      .task-comments-cell:hover span {
        color: #0052CC !important;
      }
    </style>
  `;
  
  // Add scrolling and pagination styles
  styles += `
    /* Table scrolling improvements */
    .table-responsive {
      border: 1px solid #DFE1E6;
      border-radius: 6px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .table-responsive::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
      background: #F4F5F7;
      border-radius: 4px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
      background: #DFE1E6;
      border-radius: 4px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
      background: #B3BAC5;
    }
    
    /* Pagination styles */
    #task-pagination {
      padding: 15px 20px;
      background: #FAFBFC;
      border-top: 1px solid #DFE1E6;
      border-radius: 0 0 6px 6px;
      display: flex !important;
      visibility: visible !important;
      opacity: 1 !important;
    }
    
    .pagination-info {
      font-size: 14px;
      color: #6B778C;
    }
    
    .page-size-selector select {
      border: 1px solid #DFE1E6;
      border-radius: 3px;
      padding: 4px 8px;
      font-size: 12px;
      color: #172B4D;
    }
    
    /* Sticky table header */
    #task-table thead th {
      position: sticky;
      top: 0;
      background: #FAFBFC;
      z-index: 10;
      border-bottom: 2px solid #DFE1E6;
    }
    
    /* Row hover effects */
    .task-row:hover {
      background-color: #F4F5F7 !important;
    }
  `;

  $('head').append(styles);
  console.log(" Task key and scrolling styles added");
}

// Initialize task key modal functionality
function initTaskKeyModal() {
  console.log("🔧 Initializing task key modal functionality...");

  // Handle clicks on task key links
  $(document).on("click", ".task-key-link", function (e) {
    console.log("🔥 TASK KEY LINK CLICKED!", this);
    e.preventDefault();
    e.stopPropagation();

    // Get task ID from the link or parent row
    var taskId = $(this).data("task-id");
    console.log("🔍 Task ID from data attribute:", taskId);
    
    if (!taskId) {
      // Try to get task ID from parent row
      var $row = $(this).closest("tr");
      taskId = $row.data("task-id");
      console.log("🔍 Task ID from parent row:", taskId);
    }

    if (!taskId) {
      console.error("❌ No task ID found for task key link");
      console.log("🔍 Element:", this);
      console.log("🔍 Data attributes:", $(this).data());
      return;
    }

    console.log("🔍 Opening task modal for task ID:", taskId);
    console.log("🔍 TaskModal available:", typeof TaskModal);
    console.log("🔍 window.taskModal:", typeof window.taskModal);

    // Open the task modal using the existing modal system
    openTaskModal(taskId);
  });

  console.log(" Task key modal functionality initialized");
  
  // Debug: Test if click handlers are working
  setTimeout(function() {
    var $links = $('.task-key-link');
    console.log("🔍 Found task key links:", $links.length);
    $links.each(function(index) {
      console.log("🔍 Link " + index + ":", this.outerHTML);
      console.log("🔍 Link " + index + " data-task-id:", $(this).data('task-id'));
    });
  }, 1000);
}

// Open task modal function using the complete TaskModal class
function openTaskModal(taskId) {
  console.log("🔍 openTaskModal called with taskId:", taskId);
  console.log("🔍 typeof TaskModal:", typeof TaskModal);
  console.log("🔍 window.taskModal:", window.taskModal);
  console.log("🔍 window.TaskModal:", window.TaskModal);
  
  // Debug: Log instead of alert
  console.log("Opening task modal for task ID: " + taskId);
  
  // Use the complete TaskModal class from task-modal.js
  if (window.taskModal && typeof window.taskModal.openTask === 'function') {
    console.log(" Using existing TaskModal instance");
    try {
      window.taskModal.openTask(taskId);
    } catch (error) {
      console.error("❌ Error opening task modal:", error);
      Swal.fire({
        icon: 'error',
        title: 'Modal Error',
        text: `Error opening task modal: ${error.message}`,
        confirmButtonColor: '#0052CC'
      });
    }
  } else if (typeof TaskModal !== 'undefined') {
    console.log(" Creating new TaskModal instance");
    try {
      // Initialize the complete TaskModal class
      window.taskModal = new TaskModal();
      window.taskModal.openTask(taskId);
    } catch (error) {
      console.error("❌ Error creating TaskModal:", error);
      Swal.fire({
        icon: 'error',
        title: 'Modal Error',
        text: `Error creating task modal: ${error.message}`,
        confirmButtonColor: '#0052CC'
      });
    }
  } else {
    console.error("❌ TaskModal class not available");
    Swal.fire({
      icon: 'error',
      title: 'Modal Error',
      text: 'TaskModal class not available - check console for details',
      confirmButtonColor: '#0052CC'
    });
    
    // Debug: Check what's available
    console.log("🔍 Available globals:", Object.keys(window).filter(key => key.toLowerCase().includes('task')));
    console.log("🔍 Available globals:", Object.keys(window).filter(key => key.toLowerCase().includes('modal')));
    
    // Fallback: redirect to task view page
    console.log("🔄 Fallback: redirecting to task view page");
    window.location.href = baseUrl + 'tasks/view/' + taskId;
  }
}

// Initialize comment modal functionality
function initCommentModal() {
  console.log("🔧 Initializing comment modal functionality...");

  // Handle clicks on comment column
  $(document).on("click", ".task-comments-cell, .task-comments-cell *", function (e) {
    e.preventDefault();
    e.stopPropagation();

    // Get task ID from parent row
    var $row = $(this).closest("tr");
    var taskId = $row.data("task-id");

    if (!taskId) {
      console.error("❌ No task ID found for comment cell");
      return;
    }

    console.log("💬 Opening task modal with comments focus for task ID:", taskId);

    // Open the task modal and focus on comments section
    openTaskModalWithComments(taskId);
  });

  console.log(" Comment modal functionality initialized");
}

// Open task modal with comments section focused
function openTaskModalWithComments(taskId) {
  console.log("💬 openTaskModalWithComments called with taskId:", taskId);
  
  // First open the task modal
  openTaskModal(taskId);
  
  // After a short delay, try to focus on comments section
  setTimeout(function() {
    // Try to find and click on comments tab or section
    var $commentsTab = $('.nav-link[href="#comments"], .tab-link[data-tab="comments"], [data-target="#comments"]');
    if ($commentsTab.length > 0) {
      $commentsTab.click();
      console.log(" Clicked on comments tab");
    }
    
    // Try to focus on comment input
    var $commentInput = $('#comment-input, .comment-input, textarea[name="comment"]');
    if ($commentInput.length > 0) {
      $commentInput.focus();
      console.log(" Focused on comment input");
    }
  }, 500);
}

// Global pagination variables
var paginationCurrentPage = 1;
var paginationPageSize = 10;
var paginationTotalTasks = 0;

// Global pagination refresh function
function refreshPagination() {
  console.log("🔄 Refreshing pagination...");
  if (typeof updatePaginationInfo === 'function' && typeof showCurrentPage === 'function') {
    paginationCurrentPage = 1; // Reset to first page when refreshing
    showCurrentPage();
    console.log(" Pagination refreshed");
  } else {
    console.error("❌ Pagination functions not available");
  }
}

// Initialize pagination functionality
function initPagination() {
  console.log("📄 Initializing pagination...");
  
  var currentPage = 1;
  var pageSize = 10;
  var totalTasks = 0;
  
  // Update pagination info
  window.updatePaginationInfo = function() {
    // Only count visible rows (not hidden by search/filters)
    var $visibleRows = $('.task-row:not(.collapsed-subtask):visible');
    paginationTotalTasks = $visibleRows.length;
    totalTasks = paginationTotalTasks;
    
    console.log("🔍 Pagination debug:", {
      allTaskRows: $('.task-row').length,
      visibleRows: $visibleRows.length,
      collapsedSubtasks: $('.task-row.collapsed-subtask').length,
      hiddenRows: $('.task-row:hidden').length
    });
    
    var startIndex = totalTasks > 0 ? (paginationCurrentPage - 1) * paginationPageSize + 1 : 0;
    var endIndex = Math.min(paginationCurrentPage * paginationPageSize, totalTasks);
    
    $('#showing-start').text(startIndex);
    $('#showing-end').text(endIndex);
    $('#total-tasks').text(totalTasks);
    
    // Always show pagination controls for debugging
    $('#task-pagination').show().css('display', 'flex');
    console.log("🔍 Pagination container visibility:", $('#task-pagination').is(':visible'));
    console.log("🔍 Pagination container display:", $('#task-pagination').css('display'));
    
    console.log("📊 Pagination info updated:", {
      currentPage: paginationCurrentPage,
      pageSize: paginationPageSize,
      totalTasks: totalTasks,
      showing: startIndex + '-' + endIndex
    });
  }
  
  // Show/hide rows based on current page
  window.showCurrentPage = function() {
    // Only paginate visible rows (not hidden by search/filters)
    var $visibleRows = $('.task-row:not(.collapsed-subtask):visible');
    var startIndex = (paginationCurrentPage - 1) * paginationPageSize;
    var endIndex = startIndex + paginationPageSize;
    
    // Hide all visible rows first
    $visibleRows.hide();
    
    // Show only the rows for current page
    $visibleRows.slice(startIndex, endIndex).show();
    
    updatePaginationInfo();
    updatePaginationButtons();
    
    console.log("📄 Showing page", paginationCurrentPage, "- rows", startIndex, "to", endIndex - 1);
  }
  
  // Update pagination buttons
  window.updatePaginationButtons = function() {
    var totalPages = Math.ceil(paginationTotalTasks / paginationPageSize);
    
    // Update previous button
    if (paginationCurrentPage <= 1) {
      $('#prev-page').addClass('disabled');
    } else {
      $('#prev-page').removeClass('disabled');
    }
    
    // Update next button
    if (paginationCurrentPage >= totalPages) {
      $('#next-page').addClass('disabled');
    } else {
      $('#next-page').removeClass('disabled');
    }
    
    // Update page numbers (show current page and surrounding pages)
    var $pagination = $('.pagination');
    $pagination.find('.page-item:not(#prev-page):not(#next-page)').remove();
    
    var startPage = Math.max(1, paginationCurrentPage - 2);
    var endPage = Math.min(totalPages, paginationCurrentPage + 2);
    
    for (var i = startPage; i <= endPage; i++) {
      var $pageItem = $('<li class="page-item' + (i === paginationCurrentPage ? ' active' : '') + '">');
      var $pageLink = $('<a class="page-link" href="#" data-page="' + i + '">' + i + '</a>');
      $pageItem.append($pageLink);
      $('#next-page').before($pageItem);
    }
  }
  
  // Handle page size change
  $(document).on('change', '#page-size', function() {
    paginationPageSize = parseInt($(this).val());
    paginationCurrentPage = 1; // Reset to first page
    showCurrentPage();
    console.log("📄 Page size changed to:", paginationPageSize);
  });
  
  // Handle page navigation
  $(document).on('click', '.page-link', function(e) {
    e.preventDefault();
    
    var $this = $(this);
    var $parent = $this.parent();
    
    if ($parent.hasClass('disabled')) {
      return;
    }
    
    if ($parent.attr('id') === 'prev-page') {
      if (paginationCurrentPage > 1) {
        paginationCurrentPage--;
      }
    } else if ($parent.attr('id') === 'next-page') {
      var totalPages = Math.ceil(paginationTotalTasks / paginationPageSize);
      if (paginationCurrentPage < totalPages) {
        paginationCurrentPage++;
      }
    } else {
      var page = parseInt($this.data('page'));
      if (page && page > 0) {
        paginationCurrentPage = page;
      }
    }
    
    showCurrentPage();
    console.log("📄 Navigated to page:", paginationCurrentPage);
  });
  
  // Initialize pagination on page load
  setTimeout(function() {
    console.log("🚀 Starting pagination initialization...");
    
    // Force show pagination container first
    $('#task-pagination').show().css('display', 'flex');
    console.log("🔍 Forced pagination container to show");
    
    // Make sure we have task rows before initializing
    var $taskRows = $('.task-row');
    console.log("Found", $taskRows.length, "task rows for pagination");
    
    if ($taskRows.length > 0) {
      updatePaginationInfo();
      showCurrentPage();
      console.log(" Pagination initialized with", $taskRows.length, "tasks");
    } else {
      console.warn("⚠️ No task rows found, retrying pagination in 1 second...");
      setTimeout(function() {
        var $retryRows = $('.task-row');
        if ($retryRows.length > 0) {
          updatePaginationInfo();
          showCurrentPage();
          console.log(" Pagination initialized on retry with", $retryRows.length, "tasks");
        } else {
          console.error("❌ Still no task rows found for pagination");
          // Even if no rows, show pagination with 0 tasks
          updatePaginationInfo();
        }
      }, 1000);
    }
  }, 500);
  
  console.log(" Pagination initialized");
  
  // Add global debug function
  window.debugPagination = function() {
    console.log("=== PAGINATION DEBUG ===");
    console.log("Task rows:", $('.task-row').length);
    console.log("Visible rows:", $('.task-row:visible').length);
    console.log("Collapsed subtasks:", $('.task-row.collapsed-subtask').length);
    console.log("Pagination container:", $('#task-pagination').length);
    console.log("Current page:", paginationCurrentPage);
    console.log("Page size:", paginationPageSize);
    console.log("Total tasks:", paginationTotalTasks);
    
    if (typeof updatePaginationInfo === 'function') {
      updatePaginationInfo();
      showCurrentPage();
      console.log(" Pagination manually refreshed");
    } else {
      console.error("❌ Pagination functions not available");
    }
  };
  
  // Add global force pagination function
  window.forcePagination = function() {
    console.log("🔧 Force initializing pagination...");
    paginationCurrentPage = 1;
    paginationPageSize = 10;
    updatePaginationInfo();
    showCurrentPage();
  };
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
    
    // Refresh pagination after filtering
    setTimeout(function() {
      refreshPagination();
    }, 100);
  });

  console.log(" Filters initialized");
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
  $.ajax({
    url: baseUrl + "tasks/save",
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

      console.log("    Button styling fixed (preserved functionality)");
    }
  );

  console.log(" Fixed existing add buttons without breaking functionality");
}

// Initialize when DOM is ready
$(document).ready(function () {
  // Wait a bit for all dependencies to load
  setTimeout(initTaskList, 500);
});

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

  // AJAX call to save task
  $.ajax({
    url: baseUrl + "tasks/save",
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
      console.log(" AJAX Response:", response);
      if (response && response.success) {
        console.log(" Task data:", response.data);
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

// Test function for comprehensive functionality check - run this in console
function testAllFunctionality() {
  console.log(" === COMPREHENSIVE FUNCTIONALITY TEST ===");

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

  console.log("\n Test complete! Check above for any issues.");

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
  console.log(" Testing hierarchical view...");

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
    console.log(" Hierarchical view is working properly");
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

        console.log(" Fixed expand button for task:", taskId);
      }
    }
  });

  // Check results
  var visibleExpandButtons = $(".expand-toggle, .expand-toggle-jira").filter(
    ":visible"
  ).length;
  console.log("🔧 Expand buttons now visible:", visibleExpandButtons);
}

// Make functions globally accessible
window.testHierarchicalView = testHierarchicalView;
window.fixExpandButtons = fixExpandButtons;
// ===== CSRF TOKEN HELPERS =====

// Helper function to get cookie value
function getCookie(name) {
  var value = "; " + document.cookie;
  var parts = value.split("; " + name + "=");
  if (parts.length == 2) return parts.pop().split(";").shift();
  return null;
}

// Helper function to get CSRF token from various sources
function getCSRFToken() {
  // Try multiple sources for CSRF token
  var token =
    $('meta[name="csrf-token"]').attr("content") ||
    $('input[name="csrf_test_name"]').val() ||
    getCookie("csrf_cookie_name") ||
    window.csrf_token;

  console.log("🔒 CSRF Token found:", token ? "Yes" : "No");
  return token;
}

// Make CSRF functions globally accessible
window.getCookie = getCookie;
window.getCSRFToken = getCSRFToken;

console.log(" Task List with CSRF support loaded!");
