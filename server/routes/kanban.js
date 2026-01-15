const express = require("express");
const router = express.Router();
const { pool } = require("../config/database");

// Get tasks grouped by status for Kanban board
router.get("/:projectId", async (req, res) => {
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

module.exports = router;
