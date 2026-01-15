const express = require("express");
const router = express.Router();
const path = require("path");
const fs = require("fs");
const { pool } = require("../config/database");
const { addActivityLog, logActivity } = require("../utils/activityLogger");
const { getCurrentUserId } = require("../utils/sessionManager");
const upload = require("../config/multer");

// Get single task
router.get("/:id", async (req, res) => {
  try {
    const { id } = req.params;

    const query = `
      SELECT t.*, 
             ts.title as status_title, ts.color as status_color,
             tp.title as priority_title, tp.color as priority_color, tp.icon as priority_icon,
             u.first_name, u.last_name, u.image as user_image
      FROM rise_tasks t
      LEFT JOIN rise_task_status ts ON t.status_id = ts.id
      LEFT JOIN rise_task_priority tp ON t.priority_id = tp.id
      LEFT JOIN rise_users u ON t.assigned_to = u.id
      WHERE t.id = ? AND t.deleted = 0
    `;

    const [rows] = await pool.execute(query, [id]);

    if (rows.length === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    const task = {
      ...rows[0],
      collaborators: rows[0].collaborators
        ? rows[0].collaborators.split(",").map((id) => parseInt(id))
        : [],
      labels: rows[0].labels ? rows[0].labels.split(",") : [],
      images: rows[0].images ? JSON.parse(rows[0].images) : [],
    };

    res.json({ success: true, data: task });
  } catch (error) {
    console.error("Error fetching task:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Update task
router.put("/:id", async (req, res) => {
  try {
    const { id } = req.params;
    const updates = req.body;
    const updatedBy = await getCurrentUserId(req);

    if (!updatedBy) {
      return res.status(401).json({
        success: false,
        error:
          "Authentication required. Please log in to DoJob to update tasks.",
      });
    }

    // Get current task data for comparison
    const currentTaskQuery =
      "SELECT * FROM rise_tasks WHERE id = ? AND deleted = 0";
    const [currentTaskRows] = await pool.execute(currentTaskQuery, [id]);

    if (currentTaskRows.length === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    const currentTask = currentTaskRows[0];

    // Build dynamic update query
    const allowedFields = [
      "title",
      "description",
      "assigned_to",
      "status_id",
      "priority_id",
      "deadline",
      "labels",
      "collaborators",
      "parent_task_id",
      "milestone_id",
      "status",
      "sort",
    ];

    const updateFields = [];
    const values = [];
    const changes = {};

    Object.keys(updates).forEach((key) => {
      if (allowedFields.includes(key)) {
        let newValue = updates[key];

        if (key === "labels" && Array.isArray(newValue)) {
          newValue = newValue.join(",");
        } else if (key === "collaborators" && Array.isArray(newValue)) {
          newValue = newValue.join(",");
        }

        // Track changes for activity log
        if (currentTask[key] !== newValue) {
          changes[key] = {
            from: currentTask[key],
            to: newValue,
          };
        }

        updateFields.push(`${key} = ?`);
        values.push(newValue);
      }
    });

    if (updateFields.length === 0) {
      return res.status(400).json({
        success: false,
        error: "No valid fields to update",
      });
    }

    values.push(id);

    const query = `UPDATE rise_tasks SET ${updateFields.join(
      ", "
    )} WHERE id = ? AND deleted = 0`;

    const [result] = await pool.execute(query, values);

    if (result.affectedRows === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    // Log activity if there were changes
    if (Object.keys(changes).length > 0) {
      await logActivity(
        req,
        "updated",
        "task",
        currentTask.title,
        id,
        changes,
        "project",
        currentTask.project_id
      );
    }

    res.json({ success: true, message: "Task updated successfully" });
  } catch (error) {
    console.error("Error updating task:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Delete task (soft delete)
router.delete("/:id", async (req, res) => {
  try {
    const { id } = req.params;

    const query = "UPDATE rise_tasks SET deleted = 1 WHERE id = ?";
    const [result] = await pool.execute(query, [id]);

    if (result.affectedRows === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    res.json({ success: true, message: "Task deleted successfully" });
  } catch (error) {
    console.error("Error deleting task:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Upload images for a task
router.post("/:id/upload", upload.array("images", 10), async (req, res) => {
  try {
    const { id } = req.params;

    if (!req.files || req.files.length === 0) {
      return res
        .status(400)
        .json({ success: false, error: "No files uploaded" });
    }

    // Get current task images
    const [taskRows] = await pool.execute(
      "SELECT images FROM rise_tasks WHERE id = ? AND deleted = 0",
      [id]
    );

    if (taskRows.length === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    let currentImages = [];
    try {
      currentImages = taskRows[0].images ? JSON.parse(taskRows[0].images) : [];
    } catch (e) {
      currentImages = [];
    }

    // Add new images with PHP file system URL
    const newImages = req.files.map((file) => ({
      filename: file.filename,
      originalname: file.originalname,
      size: file.size,
      url: `https://dojob.rubyshop.co.th/files/timeline_files/${file.filename}`,
      uploadedAt: new Date().toISOString(),
    }));

    const updatedImages = [...currentImages, ...newImages];

    // Update task with new images
    await pool.execute("UPDATE rise_tasks SET images = ? WHERE id = ?", [
      JSON.stringify(updatedImages),
      id,
    ]);

    res.json({
      success: true,
      data: {
        uploaded: newImages,
        total: updatedImages.length,
      },
    });
  } catch (error) {
    console.error("Error uploading images:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Delete image from task
router.delete("/:id/image/:filename", async (req, res) => {
  try {
    const { id, filename } = req.params;
    const path = require("path");
    const fs = require("fs");

    // Get current task images
    const [taskRows] = await pool.execute(
      "SELECT images FROM rise_tasks WHERE id = ? AND deleted = 0",
      [id]
    );

    if (taskRows.length === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    let currentImages = [];
    try {
      currentImages = taskRows[0].images ? JSON.parse(taskRows[0].images) : [];
    } catch (e) {
      currentImages = [];
    }

    // Remove image from array
    const updatedImages = currentImages.filter(
      (img) => img.filename !== filename
    );

    // Update task
    await pool.execute("UPDATE rise_tasks SET images = ? WHERE id = ?", [
      JSON.stringify(updatedImages),
      id,
    ]);

    // Delete physical file from PHP file system
    const filePath = path.join(__dirname, "../../files/timeline_files", filename);
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
      console.log(`✅ Deleted file: ${filePath}`);
    } else {
      console.log(`⚠️ File not found: ${filePath}`);
    }

    res.json({ success: true, message: "Image deleted successfully" });
  } catch (error) {
    console.error("Error deleting image:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Update task status (for drag & drop between columns)
router.put("/:id/status", async (req, res) => {
  try {
    // Clean and validate task ID
    let taskId = req.params.id;

    // Remove any whitespace and non-numeric characters
    taskId = taskId.toString().trim().replace(/\D/g, "");

    if (!taskId || isNaN(taskId)) {
      console.error("❌ Invalid task ID received:", req.params.id);
      return res.status(400).json({
        success: false,
        error: "Invalid task ID format",
      });
    }

    taskId = parseInt(taskId);
    console.log(`🎯 Updating task ${taskId} status`);

    const { status_id, sort } = req.body;

    if (!status_id) {
      return res.status(400).json({
        success: false,
        error: "Status ID is required",
      });
    }

    // Validate status_id exists
    const [statusCheck] = await pool.execute(
      "SELECT id, key_name FROM rise_task_status WHERE id = ? AND deleted = 0",
      [status_id]
    );

    if (statusCheck.length === 0) {
      return res.status(400).json({
        success: false,
        error: "Invalid status ID",
      });
    }

    const statusInfo = statusCheck[0];

    // Map status ID to enum value (for backward compatibility)
    // The status column is an enum('to_do','in_progress','done') so we need to map correctly
    const statusEnumMap = {
      1: "to_do",
      2: "in_progress",
      3: "done",
    };

    // Use the enum mapping first, then fall back to key_name if it matches enum values
    let statusEnum = statusEnumMap[status_id];
    if (!statusEnum) {
      // Check if key_name matches valid enum values
      const validEnumValues = ["to_do", "in_progress", "done"];
      if (validEnumValues.includes(statusInfo.key_name)) {
        statusEnum = statusInfo.key_name;
      } else {
        // Default to to_do for any custom statuses
        statusEnum = "to_do";
      }
    }

    let query =
      "UPDATE rise_tasks SET status_id = ?, status = ?, status_changed_at = NOW()";
    let params = [status_id, statusEnum];

    if (sort !== undefined && !isNaN(sort)) {
      query += ", sort = ?";
      params.push(parseInt(sort));
    }

    query += " WHERE id = ? AND deleted = 0";
    params.push(taskId);

    console.log("📝 Executing query:", query);
    console.log("📝 With params:", params);

    const [result] = await pool.execute(query, params);

    if (result.affectedRows === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    console.log(
      `✅ Task ${taskId} status updated to ${status_id} (${statusEnum})`
    );

    res.json({
      success: true,
      message: "Task status updated successfully",
      data: {
        task_id: taskId,
        status_id: status_id,
        status: statusEnum,
        status_key: statusInfo.key_name,
      },
    });
  } catch (error) {
    console.error("❌ Error updating task status:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Get subtasks for a task
router.get("/:id/subtasks", async (req, res) => {
  try {
    const { id: taskId } = req.params;

    if (!taskId || !isNumeric(taskId)) {
      return res.status(400).json({
        success: false,
        error: "Invalid task ID"
      });
    }

    console.log(`📋 Getting subtasks for task ${taskId}`);

    // Get subtasks
    const [subtasks] = await pool.execute(`
      SELECT t.*, 
             ts.title as status_title, ts.color as status_color,
             tp.title as priority_title, tp.color as priority_color, tp.icon as priority_icon,
             u.first_name, u.last_name, u.image as user_image
      FROM rise_tasks t
      LEFT JOIN rise_task_status ts ON t.status_id = ts.id
      LEFT JOIN rise_task_priority tp ON t.priority_id = tp.id
      LEFT JOIN rise_users u ON t.assigned_to = u.id
      WHERE t.parent_task_id = ? AND t.deleted = 0
      ORDER BY t.sort ASC
    `, [taskId]);

    const subtaskData = subtasks.map(subtask => {
      const assigneeName = subtask.first_name && subtask.last_name 
        ? `${subtask.first_name} ${subtask.last_name}`.trim()
        : '';

      return {
        id: parseInt(subtask.id),
        title: subtask.title || '',
        description: subtask.description || '',
        status_id: parseInt(subtask.status_id || 1),
        priority_id: parseInt(subtask.priority_id || 2),
        assigned_to: parseInt(subtask.assigned_to || 0),
        assignee_name: assigneeName,
        deadline: subtask.deadline || null,
        created_date: subtask.created_date || new Date().toISOString().split('T')[0],
        sort: parseInt(subtask.sort || 0)
      };
    });

    res.json({
      success: true,
      data: subtaskData
    });

  } catch (error) {
    console.error("❌ Error getting subtasks:", error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Log activity for a task
router.post("/:id/activity", async (req, res) => {
  try {
    const { id: taskId } = req.params;
    const { action, changes, user_id } = req.body;

    if (!taskId || !isNumeric(taskId)) {
      return res.status(400).json({
        success: false,
        error: "Invalid task ID"
      });
    }

    if (!user_id) {
      return res.status(401).json({
        success: false,
        error: "User ID is required"
      });
    }

    console.log(`📝 Logging activity for task ${taskId}: ${action}`);

    // Get task details
    const [taskRows] = await pool.execute(
      'SELECT * FROM rise_tasks WHERE id = ? AND deleted = 0',
      [taskId]
    );

    if (taskRows.length === 0) {
      return res.status(404).json({
        success: false,
        error: "Task not found"
      });
    }

    const task = taskRows[0];

    // Insert activity log
    const [result] = await pool.execute(`
      INSERT INTO rise_activity_logs (
        created_at, created_by, action, log_type, log_type_title, 
        log_type_id, changes, log_for, log_for_id, log_for2, 
        log_for_id2, deleted
      ) VALUES (NOW(), ?, ?, 'tasks', ?, ?, ?, 'tasks', ?, 'projects', ?, 0)
    `, [
      user_id,
      action || 'updated',
      task.title,
      taskId,
      changes || '',
      taskId,
      task.project_id || 0
    ]);

    console.log(`✅ Activity logged with ID: ${result.insertId}`);

    res.json({
      success: true,
      data: {
        log_id: result.insertId,
        message: 'Activity logged successfully'
      }
    });

  } catch (error) {
    console.error("❌ Error logging activity:", error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Helper function to check if value is numeric
function isNumeric(value) {
  return !isNaN(value) && !isNaN(parseInt(value));
}

// Create subtask
router.post("/:id/subtasks", async (req, res) => {
  try {
    const { id: taskId } = req.params;
    const { title, description, user_id } = req.body;

    if (!title) {
      return res.status(400).json({
        success: false,
        error: "Title is required"
      });
    }

    if (!user_id) {
      return res.status(401).json({
        success: false,
        error: "User ID is required"
      });
    }

    console.log(`📝 Creating subtask for task ${taskId}: ${title}`);

    // Verify parent task exists
    const [parentTaskRows] = await pool.execute(
      'SELECT * FROM rise_tasks WHERE id = ? AND deleted = 0',
      [taskId]
    );

    if (parentTaskRows.length === 0) {
      return res.status(404).json({
        success: false,
        error: "Parent task not found"
      });
    }

    const parentTask = parentTaskRows[0];

    // Get the next sort order for subtasks
    const [sortResult] = await pool.execute(
      'SELECT MAX(sort) as max_sort FROM rise_tasks WHERE parent_task_id = ? AND deleted = 0',
      [taskId]
    );
    const nextSort = (sortResult[0].max_sort || 0) + 1;

    // Insert the new subtask
    const [result] = await pool.execute(`
      INSERT INTO rise_tasks (
        title, description, project_id, parent_task_id, task_level, 
        status_id, status, priority_id, assigned_to, deadline, 
        sort, created_date, created_by, collaborators, labels, 
        images, context, deleted
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, '', '', '[]', 'project', 0)
    `, [
      title,
      description || '',
      parentTask.project_id,
      taskId,
      1, // task_level for subtask
      1, // status_id: To Do
      'to_do', // status enum
      2, // priority_id: Medium
      0, // assigned_to: Unassigned
      null, // deadline
      nextSort,
      user_id
    ]);

    // Get the created subtask with joined data
    const [newSubtask] = await pool.execute(`
      SELECT t.*, 
             ts.title as status_title, ts.color as status_color,
             tp.title as priority_title, tp.color as priority_color, tp.icon as priority_icon,
             u.first_name, u.last_name, u.image as user_image
      FROM rise_tasks t
      LEFT JOIN rise_task_status ts ON t.status_id = ts.id
      LEFT JOIN rise_task_priority tp ON t.priority_id = tp.id
      LEFT JOIN rise_users u ON t.assigned_to = u.id
      WHERE t.id = ?
    `, [result.insertId]);

    console.log(`✅ Subtask created with ID: ${result.insertId}`);

    // Log activity
    try {
      await logActivity(
        user_id,
        'subtask_created',
        'rise_tasks',
        result.insertId,
        `Created subtask: ${title}`
      );
    } catch (logError) {
      console.warn('Failed to log activity:', logError.message);
    }

    // Format response data
    const subtaskData = {
      id: parseInt(result.insertId),
      title: title,
      description: description || '',
      status_id: 1,
      priority_id: 2,
      assigned_to: 0,
      assignee_name: '',
      deadline: null,
      created_date: new Date().toISOString().split('T')[0],
      sort: nextSort
    };

    res.json({
      success: true,
      data: subtaskData,
      message: "Subtask created successfully"
    });

  } catch (error) {
    console.error("❌ Error creating subtask:", error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Update subtask
router.put("/subtask/:id", async (req, res) => {
  try {
    const { id: subtaskId } = req.params;
    const updates = req.body;
    const { user_id } = req.body;

    if (!subtaskId || !isNumeric(subtaskId)) {
      return res.status(400).json({
        success: false,
        error: "Invalid subtask ID"
      });
    }

    if (!user_id) {
      return res.status(401).json({
        success: false,
        error: "User ID is required"
      });
    }

    console.log(`📝 Updating subtask ${subtaskId}`);

    // Verify subtask exists
    const [subtaskRows] = await pool.execute(
      'SELECT * FROM rise_tasks WHERE id = ? AND deleted = 0',
      [subtaskId]
    );

    if (subtaskRows.length === 0) {
      return res.status(404).json({
        success: false,
        error: "Subtask not found"
      });
    }

    // Build dynamic update query
    const allowedFields = ['title', 'description', 'status_id', 'priority_id', 'assigned_to', 'deadline'];
    const updateFields = [];
    const values = [];

    Object.keys(updates).forEach((key) => {
      if (allowedFields.includes(key) && updates[key] !== undefined) {
        updateFields.push(`${key} = ?`);
        values.push(updates[key]);
      }
    });

    if (updateFields.length === 0) {
      return res.status(400).json({
        success: false,
        error: "No valid fields to update"
      });
    }

    values.push(subtaskId);

    const query = `UPDATE rise_tasks SET ${updateFields.join(", ")} WHERE id = ? AND deleted = 0`;
    const [result] = await pool.execute(query, values);

    if (result.affectedRows === 0) {
      return res.status(404).json({
        success: false,
        error: "Subtask not found"
      });
    }

    console.log(`✅ Subtask ${subtaskId} updated successfully`);

    res.json({
      success: true,
      data: {
        message: 'Subtask updated successfully'
      }
    });

  } catch (error) {
    console.error("❌ Error updating subtask:", error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Delete subtask
router.delete("/subtask/:id", async (req, res) => {
  try {
    const { id: subtaskId } = req.params;
    const { user_id } = req.body;

    if (!subtaskId || !isNumeric(subtaskId)) {
      return res.status(400).json({
        success: false,
        error: "Invalid subtask ID"
      });
    }

    if (!user_id) {
      return res.status(401).json({
        success: false,
        error: "User ID is required"
      });
    }

    console.log(`🗑️ Deleting subtask ${subtaskId}`);

    // Verify subtask exists
    const [subtaskRows] = await pool.execute(
      'SELECT * FROM rise_tasks WHERE id = ? AND deleted = 0',
      [subtaskId]
    );

    if (subtaskRows.length === 0) {
      return res.status(404).json({
        success: false,
        error: "Subtask not found"
      });
    }

    // Soft delete subtask
    const [result] = await pool.execute(
      'UPDATE rise_tasks SET deleted = 1 WHERE id = ?',
      [subtaskId]
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        success: false,
        error: "Subtask not found"
      });
    }

    console.log(`✅ Subtask ${subtaskId} deleted successfully`);

    res.json({
      success: true,
      data: {
        message: 'Subtask deleted successfully'
      }
    });

  } catch (error) {
    console.error("❌ Error deleting subtask:", error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
