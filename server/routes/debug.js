const express = require("express");
const router = express.Router();
const { pool } = require("../config/database");
const path = require("path");
const fs = require("fs");

// Debug endpoint to list uploaded files
router.get("/uploads", (req, res) => {
  try {
    const uploadsDir = path.join(__dirname, "../uploads");
    const files = fs.readdirSync(uploadsDir);
    res.json({ success: true, files: files });
  } catch (error) {
    res.json({ success: false, error: error.message });
  }
});

// Fix sort values for a project (initialize proper ordering)
router.post("/fix-sort/:projectId", async (req, res) => {
  try {
    const { projectId } = req.params;

    console.log(`🔧 Fixing sort values for project ${projectId}`);

    // Get all statuses
    const [statuses] = await pool.execute(
      "SELECT id FROM rise_task_status WHERE deleted = 0 ORDER BY sort ASC"
    );

    let totalFixed = 0;

    // Fix sort values for each status
    for (const status of statuses) {
      const [tasks] = await pool.execute(
        "SELECT id FROM rise_tasks WHERE project_id = ? AND status_id = ? AND deleted = 0 ORDER BY id ASC",
        [projectId, status.id]
      );

      // Assign incremental sort values (1000, 2000, 3000, etc.)
      for (let i = 0; i < tasks.length; i++) {
        const newSort = (i + 1) * 1000;
        await pool.execute("UPDATE rise_tasks SET sort = ? WHERE id = ?", [
          newSort,
          tasks[i].id,
        ]);
        console.log(`📝 Task ${tasks[i].id} → sort ${newSort}`);
        totalFixed++;
      }
    }

    console.log(`✅ Fixed ${totalFixed} tasks`);
    res.json({
      success: true,
      message: `Fixed sort values for ${totalFixed} tasks`,
    });
  } catch (error) {
    console.error("Error fixing sort values:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Debug endpoint to check sort values
router.get("/sort/:projectId", async (req, res) => {
  try {
    const { projectId } = req.params;

    const query = `
      SELECT t.id, t.title, t.sort, t.status_id, ts.title as status_title
      FROM rise_tasks t
      LEFT JOIN rise_task_status ts ON t.status_id = ts.id
      WHERE t.project_id = ? AND t.deleted = 0
      ORDER BY t.status_id ASC, t.sort ASC, t.id DESC
    `;

    const [tasks] = await pool.execute(query, [projectId]);

    // Group by status
    const grouped = {};
    tasks.forEach((task) => {
      if (!grouped[task.status_id]) {
        grouped[task.status_id] = {
          status_title: task.status_title,
          tasks: [],
        };
      }
      grouped[task.status_id].tasks.push({
        id: task.id,
        title: task.title,
        sort: task.sort,
      });
    });

    res.json({ success: true, data: grouped });
  } catch (error) {
    console.error("Error getting sort values:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

module.exports = router;