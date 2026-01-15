const express = require("express");
const router = express.Router();
const { pool } = require("../config/database");
const { logActivity } = require("../utils/activityLogger");

// Helper function to check if value is numeric
function isNumeric(value) {
  return !isNaN(value) && !isNaN(parseInt(value));
}

// Update subtask
router.put("/:id", async (req, res) => {
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
router.delete("/:id", async (req, res) => {
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
