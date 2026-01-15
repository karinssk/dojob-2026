/**
 * Task List Core Module
 * Main initialization and core functionality
 * Requires all module dependencies to be loaded first
 */

// Global variables
var taskListInitialized = false;

// Initialize task list functionality
function initTaskList() {
  if (taskListInitialized) {
    console.log("Task list already initialized");
    return;
  }

  console.log("🚀 Initializing Task List Core...");

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
    console.log("Collaborators containers (.task-collaborators-container):", $(".task-collaborators-container").length);
    console.log("Assignee containers (.task-assignee-container):", $(".task-assignee-container").length);
    console.log("Deadline containers (.task-deadline-container):", $(".task-deadline-container").length);
    console.log("Labels containers (.task-labels-container):", $(".task-labels-container").length);

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

  // Initialize all module functionality
  initDragDrop();                    // From task-list-drag-drop.js
  initStatusDropdowns();            // From task-list-status-priority.js
  initPriorityDropdowns();          // From task-list-status-priority.js
  initExpandCollapse();             // From task-list-hierarchy.js
  initInlineTaskCreation();         // From task-list-inline-creation.js
  initInlineEditing();              // From task-list-inline-editing.js
  initCheckboxes();                 // From task-list-utils.js
  initSearch();                     // From task-list-utils.js
  initFilters();                    // From task-list-utils.js

  // Convert existing static elements to interactive ones
  convertStatusBadgesToDropdowns(); // From task-list-status-priority.js
  convertPriorityIconsToDropdowns(); // From task-list-status-priority.js
  fixExistingAddButtons();          // From task-list-utils.js

  // Initialize click handlers for interactive containers
  initContainerClickHandlers();

  // Initialize task data display from database values
  initTaskDataDisplay();

  console.log("✅ Task List initialized successfully");
}

// Initialize task data display from database values
function initTaskDataDisplay() {
  console.log("🔧 Initializing task data display...");

  // Initialize assignee displays with actual data
  $(".task-assignee-container").each(function() {
    var $container = $(this);
    var taskId = $container.data("task-id");
    var assignedTo = $container.data("assigned-to");
    var assigneeName = $container.data("assignee-name");
    var assigneeAvatar = $container.data("assignee-avatar");

    if (assignedTo && assignedTo > 0 && assigneeName) {
      updateAssigneeDisplay($container, assignedTo, {
        id: assignedTo,
        name: assigneeName,
        avatar: assigneeAvatar
      });
    }
  });

  // Initialize collaborators displays with actual data
  $(".task-collaborators-container").each(function() {
    var $container = $(this);
    var taskId = $container.data("task-id");
    var collaborators = $container.data("collaborators");

    if (collaborators && collaborators.length > 0) {
      updateCollaboratorsDisplay($container, collaborators.split(',').map(Number));
    }
  });

  // Initialize deadline displays with actual data
  $(".task-deadline-container").each(function() {
    var $container = $(this);
    var taskId = $container.data("task-id");
    var deadline = $container.data("deadline");

    if (deadline) {
      updateDeadlineDisplay($container, deadline);
    }
  });

  // Initialize labels displays with actual data
  initLabelsDisplay(); // From task-list-labels.js

  console.log("✅ Task data display initialized");
}

// Initialize click handlers for interactive containers
function initContainerClickHandlers() {
  console.log("🔧 Initializing container click handlers...");

  // Deadline containers
  $(document).on("click", ".task-deadline-container", function (e) {
    e.stopPropagation();
    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentDeadline = $container.data("deadline");
    
    console.log("📅 Deadline container clicked:", taskId);
    
    // Close any existing dropdowns first
    $(".deadline-picker, .assignee-dropdown, .collaborators-dropdown").remove();
    
    if (typeof showDeadlinePicker !== 'undefined') {
      showDeadlinePicker($container, taskId, currentDeadline);
    } else {
      console.error("❌ showDeadlinePicker function not available");
    }
  });

  // Assignee containers
  $(document).on("click", ".task-assignee-container", function (e) {
    e.stopPropagation();
    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentAssignee = $container.data("assigned-to");
    
    console.log("👤 Assignee container clicked:", taskId);
    
    // Close any existing dropdowns first
    $(".deadline-picker, .assignee-dropdown, .collaborators-dropdown").remove();
    
    if (typeof showAssigneeDropdown !== 'undefined') {
      showAssigneeDropdown($container, taskId, currentAssignee);
    } else {
      console.error("❌ showAssigneeDropdown function not available");
    }
  });

  // Collaborators containers
  $(document).on("click", ".task-collaborators-container", function (e) {
    e.stopPropagation();
    var $container = $(this);
    var taskId = $container.data("task-id");
    var currentCollaborators = $container.data("collaborators");
    
    console.log("👥 Collaborators container clicked:", taskId);
    
    // Close any existing dropdowns first
    $(".deadline-picker, .assignee-dropdown, .collaborators-dropdown").remove();
    
    if (typeof showCollaboratorsDropdown !== 'undefined') {
      showCollaboratorsDropdown($container, taskId, currentCollaborators);
    } else {
      console.error("❌ showCollaboratorsDropdown function not available");
    }
  });

  console.log("✅ Container click handlers initialized");
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
      $row.addClass("selected");
    } else {
      $row.removeClass("selected");
    }

    // Update select all checkbox state
    updateSelectAllCheckbox();
  });

  function updateSelectAllCheckbox() {
    var totalCheckboxes = $(".task-checkbox").length;
    var checkedCheckboxes = $(".task-checkbox:checked").length;
    var $selectAll = $("#select-all-tasks");

    if (checkedCheckboxes === 0) {
      $selectAll.prop("indeterminate", false);
      $selectAll.prop("checked", false);
    } else if (checkedCheckboxes === totalCheckboxes) {
      $selectAll.prop("indeterminate", false);
      $selectAll.prop("checked", true);
    } else {
      $selectAll.prop("indeterminate", true);
    }
  }

  console.log("✅ Checkboxes initialized");
}

// Initialize search functionality
function initSearch() {
  $(document).on("keyup", ".search-box input", function () {
    var searchTerm = $(this).val().toLowerCase();

    if (searchTerm === "") {
      // Show all tasks
      $(".task-row").show();
    } else {
      // Filter tasks based on search term
      $(".task-row").each(function () {
        var $row = $(this);
        var title = $row.find(".task-title-display").text().toLowerCase();
        var key = $row.find(".task-key-link").text().toLowerCase();

        if (title.includes(searchTerm) || key.includes(searchTerm)) {
          $row.show();
          // Show parent hierarchy for found tasks
          showParentHierarchy($row.data("task-id"));
        } else {
          $row.hide();
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
      var parentId = parseInt($row.data("parent-id"));
      if (parentId > 0) {
        // Show this parent and recurse up
        var $parentRow = $('[data-task-id="' + parentId + '"]');
        if ($parentRow.length > 0) {
          $parentRow.show();
          showParentHierarchy(parentId);
        }
      }
    }
  });
}

// Initialize filters
function initFilters() {
  $(document).on("click", ".filter-option", function (e) {
    e.preventDefault();
    var filterType = $(this).data("filter");

    if (filterType === "all") {
      $(".task-row").show();
    } else {
      // Apply specific filter logic here
      $(".task-row").each(function () {
        // Implement filter logic based on filterType
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

// Initialize when DOM is ready
$(document).ready(function () {
  // Wait a bit for all dependencies to load
  setTimeout(initTaskList, 500);
});

// Make core functions globally accessible
window.initTaskList = initTaskList;
window.initTaskDataDisplay = initTaskDataDisplay;
window.initContainerClickHandlers = initContainerClickHandlers;
window.initCheckboxes = initCheckboxes;
window.initSearch = initSearch;
window.initFilters = initFilters;
window.showParentHierarchy = showParentHierarchy;
