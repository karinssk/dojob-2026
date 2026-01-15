const express = require("express");
const router = express.Router();
const { pool } = require("../config/database");
const { getCurrentUser } = require("../utils/sessionManager");

// Get all task statuses
router.get("/task-statuses", async (req, res) => {
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

// Get all task priorities
router.get("/task-priorities", async (req, res) => {
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

// Get all users for assignment
router.get("/users", async (req, res) => {
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

// Get all labels
router.get("/labels", async (req, res) => {
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

// Get current user from session
router.get("/current-user", async (req, res) => {
  try {
    // Debug: Log what cookies we're receiving
    console.log(`🔍 Node.js received cookies: ${req.headers.cookie || 'NONE'}`);
    console.log(`🔍 All request headers:`, Object.keys(req.headers));
    
    const user = await getCurrentUser(req);

    if (!user) {
      return res.status(404).json({
        success: false,
        message: "User not found or not authenticated",
      });
    }

    res.json({ success: true, user: user });
  } catch (error) {
    console.error("Error fetching current user:", error);
    res.status(500).json({ success: false, message: error.message });
  }
});

module.exports = router;
