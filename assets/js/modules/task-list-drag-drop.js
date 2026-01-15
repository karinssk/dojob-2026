/**
 * Task List Drag & Drop Module
 * Handles drag and drop functionality for task reordering
 */

// Initialize drag and drop
function initDragDrop() {
  var sortableEl = document.getElementById("sortable-tasks");
  if (!sortableEl) {
    console.warn("❌ Sortable container not found");
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
      console.log("🚀 Drag started:", evt.oldIndex);
      $(evt.item).addClass("dragging");
    },

    onEnd: function (evt) {
      console.log("🎯 Drag ended:", evt.oldIndex, "->", evt.newIndex);
      $(evt.item).removeClass("dragging");

      // Only update if position actually changed
      if (evt.oldIndex !== evt.newIndex) {
        var taskId = $(evt.item).data("task-id");
        updateTaskHierarchy(taskId, evt.newIndex, evt.oldIndex);
      }
    },
  });

  console.log("✅ Drag & Drop initialized");
}

// Update task hierarchy after drag and drop
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

// Make functions globally accessible
window.initDragDrop = initDragDrop;
window.updateTaskHierarchy = updateTaskHierarchy;
