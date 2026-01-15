const express = require("express");
const router = express.Router();
const { pool } = require("../config/database");

// Reorder task to specific position within same status
router.put("/:id/reorder-to-position", async (req, res) => {
  try {
    const { id } = req.params;
    const { target_position, project_id } = req.body;

    console.log(
      `🎯 Reordering task ${id} to position ${target_position} in project ${project_id}`
    );

    // Get current task info
    const [currentTask] = await pool.execute(
      "SELECT sort, status_id FROM rise_tasks WHERE id = ? AND deleted = 0",
      [id]
    );

    if (currentTask.length === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    const { sort: currentSort, status_id } = currentTask[0];

    // Get all tasks in the same status and project, ordered by sort
    const [tasks] = await pool.execute(
      "SELECT id, sort FROM rise_tasks WHERE project_id = ? AND status_id = ? AND deleted = 0 ORDER BY sort ASC",
      [project_id, status_id]
    );

    const currentIndex = tasks.findIndex((task) => task.id == id);

    if (currentIndex === -1) {
      return res
        .status(404)
        .json({ success: false, error: "Task not found in list" });
    }

    if (target_position < 0 || target_position >= tasks.length) {
      return res
        .status(400)
        .json({ success: false, error: "Invalid target position" });
    }

    if (currentIndex === target_position) {
      return res.json({
        success: true,
        message: "Task is already in target position",
      });
    }

    // Calculate new sort value based on target position
    let newSort;

    if (target_position === 0) {
      // Moving to first position
      newSort = tasks[0].sort - 1000;
    } else if (target_position === tasks.length - 1) {
      // Moving to last position
      newSort = tasks[tasks.length - 1].sort + 1000;
    } else {
      // Moving to middle position
      const beforeTask = tasks[target_position - 1];
      const afterTask = tasks[target_position];
      newSort = Math.floor((beforeTask.sort + afterTask.sort) / 2);

      // If the calculated sort is the same as one of the neighbors, create space
      if (newSort === beforeTask.sort || newSort === afterTask.sort) {
        newSort = beforeTask.sort + 500;
      }
    }

    // Update the task
    await pool.execute("UPDATE rise_tasks SET sort = ? WHERE id = ?", [
      newSort,
      id,
    ]);

    console.log(
      `✅ Task ${id} moved to position ${target_position}: sort ${currentSort} → ${newSort}`
    );

    res.json({
      success: true,
      message: `Task moved to position ${target_position}`,
      data: {
        taskId: id,
        oldSort: currentSort,
        newSort: newSort,
        oldPosition: currentIndex,
        newPosition: target_position,
      },
    });
  } catch (error) {
    console.error("Error reordering task to position:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Reorder task within same status (up/down one position)
router.put("/:id/reorder", async (req, res) => {
  try {
    const { id } = req.params;
    const { direction, project_id } = req.body;

    console.log(
      `🔄 Reordering task ${id} ${direction} in project ${project_id}`
    );

    // Get current task info
    const [currentTask] = await pool.execute(
      "SELECT sort, status_id FROM rise_tasks WHERE id = ? AND deleted = 0",
      [id]
    );

    if (currentTask.length === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    const { sort: currentSort, status_id } = currentTask[0];
    console.log(
      `📋 Current task: ID=${id}, Sort=${currentSort}, Status=${status_id}`
    );

    // Get all tasks in the same status and project, ordered by sort
    const [tasks] = await pool.execute(
      "SELECT id, sort FROM rise_tasks WHERE project_id = ? AND status_id = ? AND deleted = 0 ORDER BY sort ASC",
      [project_id, status_id]
    );

    console.log(
      `📊 Tasks in same status:`,
      tasks.map((t) => `ID=${t.id}:Sort=${t.sort}`).join(", ")
    );

    // Find current task index
    const currentIndex = tasks.findIndex((task) => task.id == id);
    console.log(
      `📍 Current task index: ${currentIndex} of ${tasks.length} tasks`
    );

    if (currentIndex === -1) {
      return res
        .status(404)
        .json({ success: false, error: "Task not found in list" });
    }

    let newIndex;
    if (direction === "up") {
      newIndex = Math.max(0, currentIndex - 1);
    } else if (direction === "down") {
      newIndex = Math.min(tasks.length - 1, currentIndex + 1);
    } else {
      return res
        .status(400)
        .json({ success: false, error: "Invalid direction" });
    }

    // If no change needed
    if (newIndex === currentIndex) {
      return res.json({
        success: true,
        message: "Task is already at the edge",
      });
    }

    // Get target task
    const targetTask = tasks[newIndex];

    // Declare variables outside the if/else blocks
    let newSort, targetNewSort;

    // If both tasks have the same sort value, we need to create proper values
    if (currentSort === targetTask.sort) {
      console.log(
        `⚠️ Both tasks have same sort value (${currentSort}), creating proper values`
      );

      // Assign new sort values based on position
      if (direction === "up") {
        // Moving up: current task gets smaller sort value
        newSort = targetTask.sort - 500;
        targetNewSort = currentSort + 500;
      } else {
        // Moving down: current task gets larger sort value
        newSort = targetTask.sort + 500;
        targetNewSort = currentSort - 500;
      }

      console.log(
        `🔄 Creating new values: Task ${id} (${currentSort} → ${newSort}) ↔ Task ${targetTask.id} (${targetTask.sort} → ${targetNewSort})`
      );
    } else {
      // Normal swap when sort values are different
      newSort = targetTask.sort;
      targetNewSort = currentSort;

      console.log(
        `🔄 Swapping: Task ${id} (${currentSort}) ↔ Task ${targetTask.id} (${newSort})`
      );
    }

    // Update both tasks (outside the if/else blocks)
    const result1 = await pool.execute(
      "UPDATE rise_tasks SET sort = ? WHERE id = ?",
      [newSort, id]
    );

    const result2 = await pool.execute(
      "UPDATE rise_tasks SET sort = ? WHERE id = ?",
      [targetNewSort, targetTask.id]
    );

    console.log(
      `💾 Database updates: Task ${id} affected ${result1[0].affectedRows} rows, Task ${targetTask.id} affected ${result2[0].affectedRows} rows`
    );
    console.log(
      `✅ Task ${id} moved ${direction}: sort ${currentSort} → ${newSort}`
    );

    res.json({
      success: true,
      message: `Task moved ${direction}`,
      data: {
        taskId: id,
        oldSort: currentSort,
        newSort: newSort,
      },
    });
  } catch (error) {
    console.error("Error reordering task:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Note: Task status update moved to /api/task/:id/status in task.js

module.exports = router;