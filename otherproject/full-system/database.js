const mysql = require('mysql2/promise');
const config = require('./config');

class Database {
  constructor() {
    this.pool = mysql.createPool({
      ...config.database,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0
    });
  }

  // Get user by LINE ID from new array table
  async getUserByLineId(lineUserId) {
    try {
      const [rows] = await this.pool.execute(`
        SELECT um.rise_user_id, um.duty_role, um.nick_name, um.line_display_name, 
               u.first_name, u.last_name 
        FROM user_mappings_arr um 
        JOIN rise_users u ON um.rise_user_id = u.id 
        WHERE (
          um.line_user_id = ? 
          OR JSON_CONTAINS(um.line_user_ids, ?)
        ) 
        AND u.deleted = 0 
        AND um.is_active = 1
      `, [lineUserId, JSON.stringify(lineUserId)]);
      
      return rows[0] || null;
    } catch (error) {
      console.error('Error getting user by LINE ID:', error);
      throw error;
    }
  }

  // Get all boss users
  async getBossUsers() {
    try {
      const [rows] = await this.pool.execute(`
        SELECT um.line_user_id, um.rise_user_id, um.nick_name, 
               u.first_name, u.last_name 
        FROM user_mappings_arr um 
        JOIN rise_users u ON um.rise_user_id = u.id 
        WHERE um.duty_role = 'boss' AND u.deleted = 0 AND um.is_active = 1
      `);
      
      return rows;
    } catch (error) {
      console.error('Error getting boss users:', error);
      throw error;
    }
  }

  // Get all staff users
  async getStaffUsers() {
    try {
      const [rows] = await this.pool.execute(`
        SELECT um.line_user_id, um.rise_user_id, um.nick_name, 
               u.first_name, u.last_name 
        FROM user_mappings_arr um 
        JOIN rise_users u ON um.rise_user_id = u.id 
        WHERE um.duty_role = 'staff' AND u.deleted = 0 AND um.is_active = 1
      `);
      
      return rows;
    } catch (error) {
      console.error('Error getting staff users:', error);
      throw error;
    }
  }

  async getProjectByName(projectName) {
    try {
      const [rows] = await this.pool.execute(`
        SELECT id FROM rise_projects 
        WHERE title = ? AND deleted = 0
      `, [projectName]);
      
      return rows[0] || null;
    } catch (error) {
      console.error('Error getting project by name:', error);
      throw error;
    }
  }

  async createProject(projectName, createdBy) {
    try {
      const [result] = await this.pool.execute(`
        INSERT INTO rise_projects (
          title, client_id, status_id, start_date, created_date, 
          created_by, starred_by, estimate_id, order_id, deleted
        ) VALUES (?, 0, 1, NOW(), CURDATE(), ?, '', 0, 0, 0)
      `, [projectName, createdBy]);
      
      return result.insertId;
    } catch (error) {
      console.error('Error creating project:', error);
      throw error;
    }
  }

  async createTask(taskData) {
    try {
      const status = taskData.status || 'to_do';
      const statusId = taskData.status_id || 1;
      const [result] = await this.pool.execute(`
        INSERT INTO rise_tasks (
          title, description, project_id, assigned_to, deadline,
          status, status_id, priority_id, start_date, created_date,
          context, created_by, images, deleted, milestone_id,
          points, sort, recurring, repeat_every, no_of_cycles,
          recurring_task_id, no_of_cycles_completed, parent_task_id,
          task_level, ticket_id, expense_id, subscription_id,
          proposal_id, contract_id, order_id, estimate_id,
          invoice_id, lead_id, client_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, CURDATE(), 'project', ?, ?, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
      `, [
        taskData.title,
        taskData.description || null,
        taskData.project_id,
        taskData.assigned_to,
        taskData.deadline || null,
        status,
        statusId,
        taskData.start_date || 'NOW()',
        taskData.created_by,
        taskData.images || null
      ]);
      
      return result.insertId;
    } catch (error) {
      console.error('Error creating task:', error);
      throw error;
    }
  }

  async findOrCreateRiseUser(profile) {
    try {
      const displayName = profile.displayName || 'LINE User';
      const email = `${profile.userId}@line.user`;

      const [existing] = await this.pool.execute(
        'SELECT id FROM rise_users WHERE email = ? LIMIT 1',
        [email]
      );

      if (existing.length > 0) {
        return existing[0].id;
      }

      const [result] = await this.pool.execute(
        `INSERT INTO rise_users (
          first_name, last_name, user_type, email, status, 
          job_title, language, created_at
        ) VALUES (?, ?, 'staff', ?, 'active', 'Staff', 'thai', NOW())`,
        [displayName, '', email]
      );

      return result.insertId;
    } catch (error) {
      console.error('Error finding/creating Rise user:', error);
      throw error;
    }
  }

  async ensureUserMapping(lineUserId, riseUserId, displayName, dutyRole = 'staff') {
    try {
      const [existing] = await this.pool.execute(
        'SELECT id FROM user_mappings_arr WHERE line_user_id = ? LIMIT 1',
        [lineUserId]
      );

      if (existing.length > 0) {
        return existing[0].id;
      }

      const [result] = await this.pool.execute(
        `INSERT INTO user_mappings_arr (
          line_user_id, rise_user_id, nick_name, line_display_name, duty_role, is_active
        ) VALUES (?, ?, ?, ?, ?, 1)`,
        [lineUserId, riseUserId, displayName || null, displayName || null, dutyRole]
      );

      return result.insertId;
    } catch (error) {
      console.error('Error creating user mapping:', error);
      throw error;
    }
  }

  async ensureUserByLineProfile(profile, dutyRole = 'staff') {
    try {
      const existing = await this.getUserByLineId(profile.userId);
      if (existing) return existing;

      const riseUserId = await this.findOrCreateRiseUser(profile);
      if (!riseUserId) return null;

      await this.ensureUserMapping(profile.userId, riseUserId, profile.displayName, dutyRole);
      return await this.getUserByLineId(profile.userId);
    } catch (error) {
      console.error('Error ensuring user by LINE profile:', error);
      throw error;
    }
  }

  async findTaskByTitle(title, projectId) {
    try {
      console.log(`Database: Searching for task with title: "${title}" in project: ${projectId}`);
      const [rows] = await this.pool.execute(`
        SELECT * FROM rise_tasks 
        WHERE title = ? AND project_id = ? AND deleted = 0
        ORDER BY id DESC LIMIT 1
      `, [title, projectId]);
      
      console.log(`Database: Found ${rows.length} matching tasks`);
      return rows[0] || null;
    } catch (error) {
      console.error('Error finding task by title:', error);
      throw error;
    }
  }

  async updateTask(taskId, updates) {
    try {
      const fields = [];
      const values = [];
      
      Object.keys(updates).forEach(key => {
        fields.push(`${key} = ?`);
        values.push(updates[key]);
      });
      
      values.push(taskId);
      
      await this.pool.execute(`
        UPDATE rise_tasks 
        SET ${fields.join(', ')} 
        WHERE id = ?
      `, values);
      
      return true;
    } catch (error) {
      console.error('Error updating task:', error);
      throw error;
    }
  }

  async getOverdueTasks(projectId) {
    try {
      const [rows] = await this.pool.execute(`
        SELECT t.*, u.first_name, u.last_name, um.line_user_id, um.duty_role
        FROM rise_tasks t
        JOIN rise_users u ON t.assigned_to = u.id
        LEFT JOIN user_mappings_arr um ON u.id = um.rise_user_id
        WHERE t.project_id = ? 
          AND t.status != 'done' 
          AND t.deadline < CURDATE()
          AND t.deleted = 0
          AND u.deleted = 0
          AND um.duty_role = 'boss'
        ORDER BY t.deadline ASC
      `, [projectId]);
      
      return rows;
    } catch (error) {
      console.error('Error getting overdue tasks:', error);
      throw error;
    }
  }

  async logNotification(taskId, userId, timeSlot) {
    try {
      await this.pool.execute(`
        INSERT INTO rise_line_notification_logs (
          task_id, user_id, notification_date, time_slot, notification_type, message, status, sent_at
        ) VALUES (?, ?, CURDATE(), ?, 'overdue_task', CONCAT('Task deadline notification - Time slot ', ?), 'sent', NOW())
      `, [taskId, userId, timeSlot, timeSlot]);
      
      return true;
    } catch (error) {
      console.error('Error logging notification:', error);
      // Don't throw error to prevent notification failure
      return false;
    }
  }

  async getOverdueTasksForUser(userId, projectId) {
    try {
      const [rows] = await this.pool.execute(`
        SELECT t.*, u.first_name, u.last_name
        FROM rise_tasks t
        LEFT JOIN rise_users u ON t.assigned_to = u.id
        WHERE t.project_id = ? 
        AND t.assigned_to = ?
        AND t.status = 'to_do'
        AND DATE(t.deadline) <= CURDATE()
        AND t.deleted = 0
        ORDER BY t.deadline ASC, t.id ASC
      `, [projectId, userId]);

      return rows;
    } catch (error) {
      console.error('Error getting overdue tasks for user:', error);
      return [];
    }
  }

  async hasBeenNotifiedToday(taskId, timeSlot) {
    try {
      const [rows] = await this.pool.execute(`
        SELECT COUNT(*) as count 
        FROM rise_line_notification_logs 
        WHERE task_id = ? 
          AND time_slot = ? 
          AND notification_date = CURDATE()
      `, [taskId, timeSlot]);
      
      return rows[0].count > 0;
    } catch (error) {
      console.error('Error checking notification status:', error);
      return false;
    }
  }


async addTaskComment(commentData) {
    try {
      const [result] = await this.pool.execute(`
        INSERT INTO rise_project_comments (
          created_by, created_at, description, project_id, 
          task_id, files, deleted
        ) VALUES (?, NOW(), ?, ?, ?, ?, 0)
      `, [
        commentData.created_by,
        commentData.description,
        commentData.project_id,
        commentData.task_id,
        commentData.files || null
      ]);
      
      return result.insertId;
    } catch (error) {
      console.error('Error adding task comment:', error);
      throw error;
    }
  }

  async getLastTaskToday(userId, projectId) {
    try {
      const [rows] = await this.pool.execute(`
        SELECT * FROM rise_tasks 
        WHERE created_by = ? 
          AND project_id = ? 
          AND DATE(created_date) = CURDATE()
          AND deleted = 0
        ORDER BY created_date DESC, id DESC
        LIMIT 1
      `, [userId, projectId]);
      
      return rows[0] || null;
    } catch (error) {
      console.error('Error getting last task today:', error);
      throw error;
    }
  }


  async logActivity(activityData) {
    try {
      await this.pool.execute(`
        INSERT INTO rise_activity_logs (
          created_at, created_by, action, log_type, log_type_title,
          log_type_id, changes, log_for, log_for_id, log_for2,
          log_for_id2, deleted
        ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
      `, [
        activityData.created_by,
        activityData.action,
        activityData.log_type,
        activityData.log_type_title,
        activityData.log_type_id,
        activityData.changes || null,
        activityData.log_for,
        activityData.log_for_id,
        activityData.log_for2 || null,
        activityData.log_for_id2 || null
      ]);
      
      return true;
    } catch (error) {
      console.error('Error logging activity:', error);
      return false;
    }
  }

  async close() {
    await this.pool.end();
  }
}

module.exports = Database;
