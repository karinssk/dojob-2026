const express = require("express");
const router = express.Router();
const { pool } = require("../config/database");
const { addActivityLog, logActivity } = require("../utils/activityLogger");
const { getCurrentUserId } = require("../utils/sessionManager");
const upload = require("../config/multer");

// Create a new task
router.post("/", async (req, res) => {
  try {
    const { title, description, project_id, status_id, priority_id, assigned_to, deadline } = req.body;

    if (!title || !project_id) {
      return res.status(400).json({
        success: false,
        error: "Title and project_id are required"
      });
    }

    console.log(`📝 Creating new task: ${title}`);

    // Get the next sort order for this project and status
    const [sortResult] = await pool.execute(
      'SELECT MAX(sort) as max_sort FROM rise_tasks WHERE project_id = ? AND status_id = ? AND deleted = 0',
      [project_id, status_id || 1]
    );
    const nextSort = (sortResult[0].max_sort || 0) + 1;

    // Map status ID to enum value
    const statusEnumMap = {
      1: "to_do",
      2: "in_progress", 
      3: "done",
    };
    const statusEnum = statusEnumMap[status_id] || "to_do";

    // Insert the new task
    const [result] = await pool.execute(`
      INSERT INTO rise_tasks (
        title, description, project_id, status_id, status, priority_id, 
        assigned_to, deadline, sort, created_date, task_level, task_path, 
        collaborators, labels, images, context
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 0, '', '', '', '[]', 'project')
    `, [
      title,
      description || '',
      project_id,
      status_id || 1,
      statusEnum,
      priority_id || 1,
      assigned_to || 0,
      deadline || null,
      nextSort
    ]);

    // Get the created task with joined data
    const [newTask] = await pool.execute(`
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

    console.log(`✅ Task created with ID: ${result.insertId}`);

    // Log activity
    try {
      await logActivity(
        getCurrentUserId() || 1,
        'task_created',
        'rise_tasks',
        result.insertId,
        `Created task: ${title}`
      );
    } catch (logError) {
      console.warn('Failed to log activity:', logError.message);
    }

    res.json({
      success: true,
      data: newTask[0],
      message: "Task created successfully"
    });

  } catch (error) {
    console.error("❌ Error creating task:", error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Get all tasks for a project
router.get("/:projectId", async (req, res) => {
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

// Note: Single task endpoint moved to /api/task/:id route

// Create new task
router.post("/", async (req, res) => {
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

    // Get current user ID from session
    const createdBy = await getCurrentUserId(req);
    
    if (!createdBy) {
      return res.status(401).json({
        success: false,
        error: "Authentication required. Please log in to DoJob to create tasks."
      });
    }

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

    // Log activity with current user
    await logActivity(
      req,
      "created",
      "task",
      title,
      result.insertId,
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

// Note: Individual task operations (update, delete, upload) moved to /api/task/* routes

module.exports = router;