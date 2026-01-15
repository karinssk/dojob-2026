const express = require("express");
const router = express.Router();
const { pool } = require("../config/database");

// Search tasks
router.get("/tasks", async (req, res) => {
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

// Bulk update tasks
router.put("/tasks/bulk", async (req, res) => {
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

module.exports = router;