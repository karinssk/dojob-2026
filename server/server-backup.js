const express = require("express");
const cors = require("cors");
const bodyParser = require("body-parser");
const path = require("path");
require("dotenv").config();

// Import configuration and utilities
const { testConnection } = require("./config/database");

// Import route modules
const tasksRoutes = require("./routes/tasks");
const kanbanRoutes = require("./routes/kanban");
const reorderRoutes = require("./routes/reorder");
const commentsRoutes = require("./routes/comments");
const metadataRoutes = require("./routes/metadata");
const searchRoutes = require("./routes/search");
const debugRoutes = require("./routes/debug");

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Serve static files for uploads
app.use("/uploads", express.static(path.join(__dirname, "uploads")));

// Test database connection
testConnection();

// TASKS CRUD OPERATIONS

// Get all tasks for a project
app.get("/api/tasks/:projectId", async (req, res) => {
  try {
    const { projectId } = req.params;
    const { status } = req.query;

    let query = `
      SELECT t.*, 
             ts.title as status_title, ts.color as status_color,
             tp.title as priority_title, tp.color as priority_color, tp.icon as priority_icon,
             u.first_name, u.last_name, u.image as user_image
      FROM rise_tasks t
      LEFT JOIN rise_task_status ts ON t.status_id = ts.id
      LEFT JOIN rise_task_priority tp ON t.priority_id = tp.id
      LEFT JOIN rise_users u ON t.assigned_to = u.id
      WHERE t.project_id = ? AND t.deleted = 0
    `;

    const params = [projectId];

    if (status) {
      query += " AND t.status = ?";
      params.push(status);
    }

    query += " ORDER BY t.sort ASC, t.id DESC";

    const [rows] = await pool.execute(query, params);

    // Parse collaborators and labels
    const tasks = rows.map((task) => ({
      ...task,
      collaborators: task.collaborators
        ? task.collaborators.split(",").map((id) => parseInt(id))
        : [],
      labels: task.labels ? task.labels.split(",") : [],
      images: task.images ? JSON.parse(task.images) : [],
    }));

    res.json({ success: true, data: tasks });
  } catch (error) {
    console.error("Error fetching tasks:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Get single task
app.get("/api/task/:id", async (req, res) => {
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

// Create new task
app.post("/api/tasks", async (req, res) => {
  try {
    const {
      title,
      description = "",
      project_id,
      assigned_to = 0,
      status_id = 1,
      priority_id = 0,
      deadline = null,
      labels = [],
      collaborators = [],
      parent_task_id = 0,
      milestone_id = 0,
      points = 1,
      start_date = null,
      context = "project",
    } = req.body;

    // Get current user ID (from header or default to 1)
    const createdBy = req.headers["x-user-id"] || 1;

    if (!title || !project_id) {
      return res.status(400).json({
        success: false,
        error: "Title and project_id are required",
      });
    }

    const labelsStr = Array.isArray(labels) ? labels.join(",") : "";
    const collaboratorsStr = Array.isArray(collaborators)
      ? collaborators.join(",")
      : "";
    const createdDate = new Date().toISOString().split("T")[0];

    // Calculate task level based on parent
    let taskLevel = 0;
    let taskPath = "";

    if (parent_task_id > 0) {
      const parentQuery =
        "SELECT task_level, task_path FROM rise_tasks WHERE id = ?";
      const [parentRows] = await pool.execute(parentQuery, [parent_task_id]);

      if (parentRows.length > 0) {
        taskLevel = (parentRows[0].task_level || 0) + 1;
        taskPath = parentRows[0].task_path
          ? `${parentRows[0].task_path}.${parent_task_id}`
          : parent_task_id.toString();
      }
    }

    // Map status enum value
    const statusEnumMap = {
      1: "to_do",
      2: "in_progress",
      3: "done",
    };
    const statusEnum = statusEnumMap[status_id] || "to_do";

    const query = `
      INSERT INTO rise_tasks (
        title, description, project_id, assigned_to, status_id, priority_id,
        deadline, labels, collaborators, parent_task_id, milestone_id, 
        created_date, points, start_date, task_level, task_path, 
        status, context, blocking, blocked_by, images
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;

    const [result] = await pool.execute(query, [
      title,
      description,
      project_id,
      assigned_to,
      status_id,
      priority_id,
      deadline,
      labelsStr,
      collaboratorsStr,
      parent_task_id,
      milestone_id,
      createdDate,
      points,
      start_date,
      taskLevel,
      taskPath,
      statusEnum,
      context,
      "",
      "",
      "[]",
    ]);

    // Update task path with the new task ID
    if (!taskPath) {
      const updatePathQuery =
        "UPDATE rise_tasks SET task_path = ? WHERE id = ?";
      await pool.execute(updatePathQuery, [
        result.insertId.toString(),
        result.insertId,
      ]);
    }

    // Log activity
    await addActivityLog(
      "created",
      "task",
      title,
      result.insertId,
      createdBy,
      { title, project_id, status_id, priority_id },
      "project",
      project_id
    );

    res.json({
      success: true,
      data: {
        id: result.insertId,
        task_id: result.insertId,
        message: "Task created successfully",
        task_level: taskLevel,
        parent_task_id: parent_task_id,
      },
    });
  } catch (error) {
    console.error("Error creating task:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Update task
app.put("/api/task/:id", async (req, res) => {
  try {
    const { id } = req.params;
    const updates = req.body;
    const updatedBy = req.headers["x-user-id"] || 1;

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
      await addActivityLog(
        "updated",
        "task",
        currentTask.title,
        id,
        updatedBy,
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
app.delete("/api/task/:id", async (req, res) => {
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

// TASK STATUS OPERATIONS

// Get all task statuses
app.get("/api/task-statuses", async (req, res) => {
  try {
    const query =
      "SELECT * FROM rise_task_status WHERE deleted = 0 ORDER BY sort ASC";
    const [rows] = await pool.execute(query);
    res.json({ success: true, data: rows });
  } catch (error) {
    console.error("Error fetching task statuses:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// TASK PRIORITY OPERATIONS

// Get all task priorities
app.get("/api/task-priorities", async (req, res) => {
  try {
    const query =
      "SELECT * FROM rise_task_priority WHERE deleted = 0 ORDER BY id ASC";
    const [rows] = await pool.execute(query);
    res.json({ success: true, data: rows });
  } catch (error) {
    console.error("Error fetching task priorities:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// USERS OPERATIONS

// Get all users for assignment
app.get("/api/users", async (req, res) => {
  try {
    const query = `
      SELECT id, first_name, last_name, email, image, user_type
      FROM rise_users 
      WHERE deleted = 0 AND status = 'active'
      ORDER BY first_name ASC
    `;
    const [rows] = await pool.execute(query);
    res.json({ success: true, data: rows });
  } catch (error) {
    console.error("Error fetching users:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// LABELS OPERATIONS

// Get all labels
app.get("/api/labels", async (req, res) => {
  try {
    const { context } = req.query;
    let query = "SELECT * FROM rise_labels WHERE deleted = 0";
    const params = [];

    if (context) {
      query += " AND context = ?";
      params.push(context);
    }

    query += " ORDER BY title ASC";

    const [rows] = await pool.execute(query, params);
    res.json({ success: true, data: rows });
  } catch (error) {
    console.error("Error fetching labels:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// KANBAN BOARD OPERATIONS

// Get tasks grouped by status for Kanban board
app.get("/api/kanban/:projectId", async (req, res) => {
  try {
    const { projectId } = req.params;

    // Get all statuses
    const statusQuery =
      "SELECT * FROM rise_task_status WHERE deleted = 0 ORDER BY sort ASC";
    const [statuses] = await pool.execute(statusQuery);

    // Get tasks for each status with complete information
    const tasksQuery = `
      SELECT t.id, t.title, t.description, t.project_id, t.milestone_id, 
             t.assigned_to, t.deadline, t.labels, t.points, t.status, 
             t.status_id, t.priority_id, t.start_date, t.collaborators, 
             t.sort, t.created_date, t.parent_task_id, t.task_level, 
             t.task_path, t.images, t.status_changed_at,
             ts.title as status_title, ts.color as status_color, ts.key_name as status_key,
             tp.title as priority_title, tp.color as priority_color, tp.icon as priority_icon,
             u.first_name, u.last_name, u.image as user_image, u.email as user_email
      FROM rise_tasks t
      LEFT JOIN rise_task_status ts ON t.status_id = ts.id
      LEFT JOIN rise_task_priority tp ON t.priority_id = tp.id
      LEFT JOIN rise_users u ON t.assigned_to = u.id
      WHERE t.project_id = ? AND t.deleted = 0
      ORDER BY t.sort ASC, t.id DESC
    `;

    const [tasks] = await pool.execute(tasksQuery, [projectId]);

    // Group tasks by status and process data
    const kanbanData = statuses.map((status) => ({
      id: status.id,
      title: status.title,
      key_name: status.key_name,
      color: status.color,
      sort: status.sort,
      hide_from_kanban: status.hide_from_kanban,
      deleted: status.deleted,
      hide_from_non_project_related_tasks:
        status.hide_from_non_project_related_tasks,
      tasks: tasks
        .filter((task) => task.status_id === status.id)
        .map((task) => {
          // Parse images safely
          let parsedImages = [];
          if (task.images) {
            try {
              parsedImages =
                typeof task.images === "string"
                  ? JSON.parse(task.images)
                  : task.images;
              if (!Array.isArray(parsedImages)) parsedImages = [];
            } catch (e) {
              parsedImages = [];
            }
          }

          // Parse collaborators
          let collaboratorIds = [];
          if (task.collaborators) {
            collaboratorIds = task.collaborators
              .split(",")
              .map((id) => parseInt(id.trim()))
              .filter((id) => !isNaN(id));
          }

          // Parse labels
          let taskLabels = [];
          if (task.labels) {
            taskLabels = task.labels
              .split(",")
              .map((label) => label.trim())
              .filter((label) => label.length > 0);
          }

          return {
            id: task.id,
            title: task.title,
            description: task.description || "",
            project_id: task.project_id,
            milestone_id: task.milestone_id,
            assigned_to: task.assigned_to,
            deadline: task.deadline,
            labels: taskLabels,
            points: task.points,
            status: task.status,
            status_id: task.status_id,
            priority_id: task.priority_id,
            start_date: task.start_date,
            collaborators: collaboratorIds,
            sort: task.sort,
            created_date: task.created_date,
            parent_task_id: task.parent_task_id,
            task_level: task.task_level,
            task_path: task.task_path,
            images: parsedImages,
            status_changed_at: task.status_changed_at,
            // Status information
            status_title: task.status_title,
            status_color: task.status_color,
            status_key: task.status_key,
            // Priority information
            priority_title: task.priority_title,
            priority_color: task.priority_color,
            priority_icon: task.priority_icon,
            // User information
            first_name: task.first_name,
            last_name: task.last_name,
            user_image: task.user_image,
            user_email: task.user_email,
          };
        }),
    }));

    res.json({ success: true, data: kanbanData });
  } catch (error) {
    console.error("Error fetching kanban data:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Reorder task to specific position within same status
app.put("/api/task/:id/reorder-to-position", async (req, res) => {
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

// Reorder task within same status
app.put("/api/task/:id/reorder", async (req, res) => {
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

// Update task status (for drag & drop)
app.put("/api/task/:id/status", async (req, res) => {
  try {
    const { id } = req.params;
    const { status_id, sort } = req.body;

    // Map status ID to enum value
    const statusEnumMap = {
      1: "to_do",
      2: "in_progress",
      3: "done",
    };
    const statusEnum = statusEnumMap[status_id] || "to_do";

    let query =
      "UPDATE rise_tasks SET status_id = ?, status = ?, status_changed_at = NOW()";
    let params = [status_id, statusEnum];

    if (sort !== undefined) {
      query += ", sort = ?";
      params.push(sort);
    }

    query += " WHERE id = ? AND deleted = 0";
    params.push(id);

    const [result] = await pool.execute(query, params);

    if (result.affectedRows === 0) {
      return res.status(404).json({ success: false, error: "Task not found" });
    }

    res.json({
      success: true,
      message: "Task status updated successfully",
      data: {
        task_id: id,
        status_id: status_id,
        status: statusEnum,
      },
    });
  } catch (error) {
    console.error("Error updating task status:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// BULK OPERATIONS

// Bulk update tasks
app.put("/api/tasks/bulk", async (req, res) => {
  try {
    const { task_ids, updates } = req.body;

    if (!Array.isArray(task_ids) || task_ids.length === 0) {
      return res.status(400).json({
        success: false,
        error: "task_ids must be a non-empty array",
      });
    }

    const allowedFields = ["status_id", "priority_id", "assigned_to"];
    const updateFields = [];
    const values = [];

    Object.keys(updates).forEach((key) => {
      if (allowedFields.includes(key)) {
        updateFields.push(`${key} = ?`);
        values.push(updates[key]);
      }
    });

    if (updateFields.length === 0) {
      return res.status(400).json({
        success: false,
        error: "No valid fields to update",
      });
    }

    const placeholders = task_ids.map(() => "?").join(",");
    values.push(...task_ids);

    const query = `
      UPDATE rise_tasks 
      SET ${updateFields.join(", ")} 
      WHERE id IN (${placeholders}) AND deleted = 0
    `;

    const [result] = await pool.execute(query, values);

    res.json({
      success: true,
      message: `${result.affectedRows} tasks updated successfully`,
    });
  } catch (error) {
    console.error("Error bulk updating tasks:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// SEARCH AND FILTER

// Search tasks
app.get("/api/search/tasks", async (req, res) => {
  try {
    const { q, project_id, status_id, priority_id, assigned_to } = req.query;

    let query = `
      SELECT t.*, 
             ts.title as status_title, ts.color as status_color,
             tp.title as priority_title, tp.color as priority_color, tp.icon as priority_icon,
             u.first_name, u.last_name, u.image as user_image
      FROM rise_tasks t
      LEFT JOIN rise_task_status ts ON t.status_id = ts.id
      LEFT JOIN rise_task_priority tp ON t.priority_id = tp.id
      LEFT JOIN rise_users u ON t.assigned_to = u.id
      WHERE t.deleted = 0
    `;

    const params = [];

    if (q) {
      query += " AND (t.title LIKE ? OR t.description LIKE ?)";
      params.push(`%${q}%`, `%${q}%`);
    }

    if (project_id) {
      query += " AND t.project_id = ?";
      params.push(project_id);
    }

    if (status_id) {
      query += " AND t.status_id = ?";
      params.push(status_id);
    }

    if (priority_id) {
      query += " AND t.priority_id = ?";
      params.push(priority_id);
    }

    if (assigned_to) {
      query += " AND t.assigned_to = ?";
      params.push(assigned_to);
    }

    query += " ORDER BY t.id DESC LIMIT 50";

    const [rows] = await pool.execute(query, params);

    const tasks = rows.map((task) => ({
      ...task,
      collaborators: task.collaborators
        ? task.collaborators.split(",").map((id) => parseInt(id))
        : [],
      labels: task.labels ? task.labels.split(",") : [],
      images: task.images ? JSON.parse(task.images) : [],
    }));

    res.json({ success: true, data: tasks });
  } catch (error) {
    console.error("Error searching tasks:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// IMAGE UPLOAD OPERATIONS

// Upload images for a task
app.post(
  "/api/task/:id/upload",
  upload.array("images", 10),
  async (req, res) => {
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
        return res
          .status(404)
          .json({ success: false, error: "Task not found" });
      }

      let currentImages = [];
      try {
        currentImages = taskRows[0].images
          ? JSON.parse(taskRows[0].images)
          : [];
      } catch (e) {
        currentImages = [];
      }

      // Add new images
      const newImages = req.files.map((file) => ({
        filename: file.filename,
        originalname: file.originalname,
        size: file.size,
        url: `http://localhost:${PORT}/uploads/${file.filename}`,
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
  }
);

// Delete image from task
app.delete("/api/task/:id/image/:filename", async (req, res) => {
  try {
    const { id, filename } = req.params;

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

    // Delete physical file
    const filePath = path.join(__dirname, "uploads", filename);
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    }

    res.json({ success: true, message: "Image deleted successfully" });
  } catch (error) {
    console.error("Error deleting image:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// TASK COMMENTS OPERATIONS

// Get comments for a task
app.get("/api/task/:id/comments", async (req, res) => {
  try {
    const { id } = req.params;

    const query = `
      SELECT c.*, u.first_name, u.last_name, u.image as user_image
      FROM rise_project_comments c
      LEFT JOIN rise_users u ON c.created_by = u.id
      WHERE c.project_id = (SELECT project_id FROM rise_tasks WHERE id = ?) 
      AND c.task_id = ? AND c.deleted = 0
      ORDER BY c.created_at ASC
    `;

    const [rows] = await pool.execute(query, [id, id]);

    const comments = rows.map((comment) => {
      // Parse PHP serialized files data
      let images = [];
      if (comment.files && comment.files !== "a:0:{}") {
        try {
          // Simple PHP serialized array parser for files
          const filesStr = comment.files;
          console.log("🔍 Parsing comment files:", filesStr);
          if (filesStr.includes("file_name")) {
            // Parse PHP serialized format more carefully
            // Format: a:1:{i:0;a:4:{s:9:"file_name";s:X:"filename";s:13:"original_name";s:Y:"original";s:9:"file_size";s:Z:"size";s:7:"file_id";N;}}

            // Extract file_name values
            const fileNameMatches = filesStr.match(
              /s:9:"file_name";s:\d+:"([^"]+)"/g
            );
            const originalNameMatches = filesStr.match(
              /s:13:"original_name";s:\d+:"([^"]+)"/g
            );

            if (fileNameMatches) {
              fileNameMatches.forEach((match, index) => {
                const fileName = match.match(
                  /s:9:"file_name";s:\d+:"([^"]+)"/
                )[1];
                let originalName = fileName;

                if (originalNameMatches && originalNameMatches[index]) {
                  originalName = originalNameMatches[index].match(
                    /s:13:"original_name";s:\d+:"([^"]+)"/
                  )[1];
                }

                const imageObj = {
                  filename: fileName,
                  originalname: originalName,
                  url: `http://localhost:8888/dojob/files/timeline_files/${fileName}`,
                };
                console.log("📷 Extracted image:", imageObj);
                images.push(imageObj);
              });
            }
          }
        } catch (e) {
          console.error("Error parsing comment files:", e);
        }
      }

      return {
        ...comment,
        author_name:
          `${comment.first_name || ""} ${comment.last_name || ""}`.trim() ||
          "Unknown User",
        images: images,
      };
    });

    res.json({ success: true, data: comments });
  } catch (error) {
    console.error("Error fetching comments:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Add comment to task (with image support)
app.post(
  "/api/task/:id/comments",
  upload.array("images", 10),
  async (req, res) => {
    console.log("🎯 Comment endpoint hit:", req.method, req.url);
    try {
      const { id } = req.params;
      const { description } = req.body;
      const createdBy = req.headers["x-user-id"] || 1; // Get current user
      const uploadedFiles = req.files || [];

      console.log("📝 Comment request:", {
        taskId: id,
        description: description,
        descriptionLength: description ? description.length : 0,
        uploadedFilesCount: uploadedFiles.length,
        files: uploadedFiles.map((f) => ({
          name: f.originalname,
          size: f.size,
        })),
      });

      // Allow empty description if there are images
      const hasDescription = description && description.trim().length > 0;
      const hasImages = uploadedFiles && uploadedFiles.length > 0;

      if (!hasDescription && !hasImages) {
        return res.status(400).json({
          success: false,
          error: "Comment description or images are required",
        });
      }

      // Get task project_id and title
      const [taskRows] = await pool.execute(
        "SELECT project_id, title FROM rise_tasks WHERE id = ? AND deleted = 0",
        [id]
      );

      if (taskRows.length === 0) {
        return res
          .status(404)
          .json({ success: false, error: "Task not found" });
      }

      const { project_id: projectId, title: taskTitle } = taskRows[0];

      // Process uploaded images
      let filesData = [];
      if (uploadedFiles.length > 0) {
        filesData = uploadedFiles.map((file) => ({
          file_name: file.filename,
          original_name: file.originalname,
          file_size: file.size.toString(),
          file_id: null,
          service_type: null,
        }));
      }

      // Convert to PHP serialized format (for compatibility with existing system)
      const serializedFiles =
        filesData.length > 0
          ? "a:" +
            filesData.length +
            ":{" +
            filesData
              .map(
                (file, index) =>
                  `i:${index};a:4:{s:9:"file_name";s:${file.file_name.length}:"${file.file_name}";s:13:"original_name";s:${file.original_name.length}:"${file.original_name}";s:9:"file_size";s:${file.file_size.length}:"${file.file_size}";s:7:"file_id";N;}`
              )
              .join("") +
            "}"
          : "a:0:{}";

      console.log("💾 Serialized files data:", serializedFiles);

      const query = `
      INSERT INTO rise_project_comments (
        project_id, task_id, description, created_by, created_at, files
      ) VALUES (?, ?, ?, ?, NOW(), ?)
    `;

      const [result] = await pool.execute(query, [
        projectId,
        id,
        description ? description.trim() : "",
        createdBy,
        serializedFiles,
      ]);

      // Log activity
      await addActivityLog(
        "created",
        "comment",
        `Comment on: ${taskTitle}`,
        result.insertId,
        createdBy,
        {
          comment: description ? description.trim() : "",
          task_id: id,
          images_count: uploadedFiles.length,
        },
        "task",
        id
      );

      res.json({
        success: true,
        data: {
          id: result.insertId,
          message: `Comment added successfully${
            uploadedFiles.length > 0
              ? ` with ${uploadedFiles.length} image(s)`
              : ""
          }`,
        },
      });
    } catch (error) {
      console.error("Error adding comment:", error);
      res.status(500).json({ success: false, error: error.message });
    }
  }
);

// CURRENT USER OPERATIONS

// Get current user from session (simplified - you may need to adapt this)
app.get("/api/current-user", async (req, res) => {
  try {
    // For now, we'll use a default user ID (you can modify this to get from session/auth)
    const userId = req.headers["x-user-id"] || 1; // Default to user ID 1

    const query = `
      SELECT id, first_name, last_name, email, image, user_type
      FROM rise_users 
      WHERE id = ? AND deleted = 0 AND status = 'active'
    `;

    const [rows] = await pool.execute(query, [userId]);

    if (rows.length === 0) {
      return res.status(404).json({ success: false, error: "User not found" });
    }

    res.json({ success: true, data: rows[0] });
  } catch (error) {
    console.error("Error fetching current user:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// ACTIVITY LOGGING

// Add activity log entry
async function addActivityLog(
  action,
  logType,
  logTypeTitle,
  logTypeId,
  createdBy,
  changes = null,
  logFor = null,
  logForId = null
) {
  try {
    const query = `
      INSERT INTO rise_activity_logs (
        created_at, created_by, action, log_type, log_type_title, log_type_id,
        changes, log_for, log_for_id, deleted
      ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 0)
    `;

    const changesJson = changes ? JSON.stringify(changes) : null;

    await pool.execute(query, [
      createdBy,
      action,
      logType,
      logTypeTitle,
      logTypeId,
      changesJson,
      logFor || logType,
      logForId || logTypeId,
    ]);

    console.log(
      `📝 Activity logged: ${action} ${logType} ${logTypeId} by user ${createdBy}`
    );
  } catch (error) {
    console.error("Error logging activity:", error);
  }
}

// Health check endpoint
app.get("/api/health", (req, res) => {
  res.json({
    success: true,
    message: "Task Board API is running",
    timestamp: new Date().toISOString(),
  });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({
    success: false,
    error: "Something went wrong!",
  });
});

// 404 handler
app.use("*", (req, res) => {
  res.status(404).json({
    success: false,
    error: "Endpoint not found",
  });
});

app.listen(PORT, () => {
  console.log(`🚀 Task Board API Server running on port ${PORT}`);
  console.log(`📋 API Base URL: http://localhost:${PORT}/api`);
  console.log(`🏥 Health Check: http://localhost:${PORT}/api/health`);
});

module.exports = app;
