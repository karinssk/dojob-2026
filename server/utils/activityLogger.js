const { pool } = require("../config/database");
const { getCurrentUserId } = require("./sessionManager");

// Add activity log entry with automatic user detection
async function addActivityLog(
  action,
  logType,
  logTypeTitle,
  logTypeId,
  createdBy = null,
  changes = null,
  logFor = null,
  logForId = null,
  req = null
) {
  try {
    // If createdBy is not provided, try to get current user from request
    let userId = createdBy;
    if (!userId && req) {
      userId = await getCurrentUserId(req);
      console.log(`👤 Auto-detected user ID: ${userId}`);
    }
    
    // Fallback to user ID 1 if still no user
    if (!userId) {
      userId = 1;
      console.warn("⚠️ Using fallback user ID 1 for activity log");
    }

    const query = `
      INSERT INTO rise_activity_logs (
        created_at, created_by, action, log_type, log_type_title, log_type_id,
        changes, log_for, log_for_id, deleted
      ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 0)
    `;

    const changesJson = changes ? JSON.stringify(changes) : null;

    await pool.execute(query, [
      userId,
      action,
      logType,
      logTypeTitle,
      logTypeId,
      changesJson,
      logFor || logType,
      logForId || logTypeId,
    ]);

    console.log(
      `📝 Activity logged: ${action} ${logType} ${logTypeId} by user ${userId}`
    );
  } catch (error) {
    console.error("Error logging activity:", error);
  }
}

// Enhanced version that automatically gets current user from request
async function logActivity(req, action, logType, logTypeTitle, logTypeId, changes = null, logFor = null, logForId = null) {
  return addActivityLog(action, logType, logTypeTitle, logTypeId, null, changes, logFor, logForId, req);
}

module.exports = { addActivityLog, logActivity };