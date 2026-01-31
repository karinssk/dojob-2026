/**
 * Task List Hierarchy Module
 * Handles expand/collapse functionality and hierarchical view management
 */

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

// Helper functions for hierarchy management
function hideChildRows(parentId, parentLevel) {
  var $allRows = $(".task-row");
  var foundParent = false;
  var hiddenCount = 0;

  $allRows.each(function () {
    if (foundParent) {
      var currentLevel = parseInt($(this).data("level")) || 0;

      if (currentLevel > parentLevel) {
        // This is a child of the parent - hide it
        $(this).hide().addClass("collapsed-subtask");
        hiddenCount++;
      } else {
        // We've reached a sibling or higher level - stop
        return false;
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
      var currentLevel = parseInt($(this).data("level")) || 0;

      if (currentLevel === parentLevel + 1) {
        // This is a direct child - show it
        $(this).show().removeClass("collapsed-subtask");
        shownCount++;
      } else if (currentLevel <= parentLevel) {
        // We've reached a sibling or higher level - stop
        return false;
      }
      // Skip grandchildren and deeper (currentLevel > parentLevel + 1)
    }

    if ($(this).data("task-id") == parentId) {
      foundParent = true;
    }
  });

  console.log("Shown", shownCount, "direct child rows for parent", parentId);
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
window.initExpandCollapse = initExpandCollapse;
window.initializeHierarchicalView = initializeHierarchicalView;
window.checkIfTaskHasChildren = checkIfTaskHasChildren;
window.showDirectChildren = showDirectChildren;
window.hideDirectChildren = hideDirectChildren;
window.hideChildRows = hideChildRows;
window.showDirectChildRows = showDirectChildRows;
window.testHierarchicalView = testHierarchicalView;
window.fixExpandButtons = fixExpandButtons;
