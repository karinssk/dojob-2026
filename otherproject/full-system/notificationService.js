const { Client } = require('@line/bot-sdk');
const moment = require('moment');
const cron = require('node-cron');
const config = require('./config');
const Database = require('./database');
const Utils = require('./utils');
const logger = require('./logger');

class NotificationService {
  constructor() {
    this.client = new Client(config.line);
    this.db = new Database();
  }

  // Start the notification scheduler
  start() {
    if (!config.notification.enabled) {
      console.log('Notification service is disabled');
      return;
    }

    console.log('Starting notification service...');

    // Schedule staggered notifications at specific times
    // 09:15 - First notification slot
    cron.schedule('15 9 * * *', () => {
      console.log('Running notification check at 09:15');
      this.checkAndSendStaggeredNotifications(1);
    });

    // 13:15 - Second notification slot
    cron.schedule('15 13 * * *', () => {
      console.log('Running notification check at 13:15');
      this.checkAndSendStaggeredNotifications(2);
    });

    // 16:15 - Third notification slot
    cron.schedule('15 16 * * *', () => {
      console.log('Running notification check at 16:15');
      this.checkAndSendStaggeredNotifications(3);
    });

    console.log('Notification service started successfully');
  }

  async checkAndSendStaggeredNotifications(timeSlot) {
    try {
      logger.info('NotificationService', `Checking for overdue tasks - Time slot ${timeSlot}`);

      // Get current month project
      const currentMonthProject = Utils.getCurrentMonthProjectName();
      const project = await this.db.getProjectByName(currentMonthProject);
      
      if (!project) {
        logger.warn('NotificationService', 'Current month project not found');
        return;
      }

      // Get all overdue tasks for boss users that haven't been notified today
      const overdueTasks = await this.getOverdueTasksForTimeSlot(project.id, timeSlot);
      
      logger.info('NotificationService', `Found ${overdueTasks.length} overdue tasks for time slot ${timeSlot}`);

      if (overdueTasks.length === 0) {
        logger.info('NotificationService', `No tasks to notify for time slot ${timeSlot}`);
        return;
      }

      // Send only the first task for this time slot
      const taskToNotify = overdueTasks[0];
      
      logger.info('NotificationService', `Sending notification for task ${taskToNotify.id} at time slot ${timeSlot}`);

      if (config.notification.sendToRoom && config.notification.roomId) {
        // Send notification to the configured room
        await this.sendRoomTaskNotification(config.notification.roomId, taskToNotify);
        await this.db.logNotification(taskToNotify.id, taskToNotify.assigned_to, timeSlot);
      } else {
        // Send individual notification
        await this.processOverdueTask(taskToNotify, timeSlot);
      }
    } catch (error) {
      logger.error('NotificationService', `Error in notification check for time slot ${timeSlot}`, error);
    }
  }

  // Keep the old method for backward compatibility
  async checkAndSendNotifications() {
    await this.checkAndSendStaggeredNotifications(1);
  }

  async getOverdueTasksForTimeSlot(projectId, timeSlot) {
    try {
      // Get boss users from database
      const bossUsers = await this.db.getBossUsers();
      
      if (bossUsers.length === 0) {
        logger.warn('NotificationService', 'No boss users found');
        return [];
      }

      const bossUserIds = bossUsers.map(user => user.rise_user_id);

      // Get tasks that are:
      // 1. In current month project
      // 2. Assigned to boss users
      // 3. Status = 'to_do'
      // 4. Deadline <= today
      // 5. Not deleted
      // 6. Haven't been notified today for this time slot
      const [rows] = await this.db.pool.execute(`
        SELECT t.*, u.first_name, u.last_name, um.line_user_id
        FROM rise_tasks t
        LEFT JOIN rise_users u ON t.assigned_to = u.id
        LEFT JOIN user_mappings_arr um ON u.id = um.rise_user_id
        WHERE t.project_id = ? 
        AND t.assigned_to IN (${bossUserIds.map(() => '?').join(',')})
        AND t.status = 'to_do'
        AND DATE(t.deadline) <= CURDATE()
        AND t.deleted = 0
        AND um.duty_role = 'boss'
        AND NOT EXISTS (
          SELECT 1 FROM rise_line_notification_logs 
          WHERE task_id = t.id 
          AND DATE(notification_date) = CURDATE()
          AND time_slot = ?
        )
        ORDER BY t.deadline ASC, t.id ASC
      `, [projectId, ...bossUserIds, timeSlot]);

      return rows;
    } catch (error) {
      logger.error('NotificationService', `Error getting overdue tasks for time slot: ${error.message}`, error);
      console.error('Full error details:', error);
      return [];
    }
  }

  async processOverdueTask(task, timeSlot = 1) {
    try {
      // Check if we already sent notification today for this time slot
      const sentToday = await this.db.hasBeenNotifiedToday(task.id, timeSlot);
      
      if (sentToday) {
        logger.info('NotificationService', `Notification already sent today for task ${task.id} at time slot ${timeSlot}`);
        return;
      }

      // Get all LINE user IDs for this task's assignee
      const lineUserIds = await this.db.getAllLineUserIds(task.assigned_to);
      
      if (!lineUserIds || lineUserIds.length === 0) {
        logger.warn('NotificationService', `No LINE user IDs found for task ${task.id}, assigned_to: ${task.assigned_to}`);
        return;
      }

      logger.info('NotificationService', `Sending notification to ${lineUserIds.length} LINE user ID(s) for task ${task.id}`);

      // Send notification to all LINE user IDs
      for (const lineUserId of lineUserIds) {
        try {
          await this.sendTaskNotification(lineUserId, task);
          logger.info('NotificationService', `Notification sent to LINE user ${lineUserId} for task ${task.id}`);
        } catch (error) {
          logger.error('NotificationService', `Failed to send to LINE user ${lineUserId}`, error);
        }
      }

      // Log the notification with time slot
      await this.db.logNotification(task.id, task.assigned_to, timeSlot);
      
      logger.info('NotificationService', `Notification sent successfully for task ${task.id} to ${lineUserIds.length} recipient(s)`);
    } catch (error) {
      logger.error('NotificationService', `Error processing overdue task ${task.id}`, error);
    }
  }

  async sendTaskNotification(lineUserId, task) {
    try {
      // Format deadline in Thai format
      const deadlineDate = moment(task.deadline);
      const thaiDate = this.formatThaiDate(deadlineDate);
      const today = moment();
      const daysOverdue = today.diff(deadlineDate, 'days');

      // Extract assignee name from task title
      const assigneeName = this.extractAssigneeName(task.title);

      // Determine urgency color
      const urgencyColor = daysOverdue >= 3 ? '#DC3545' : daysOverdue >= 1 ? '#FD7E14' : '#FFC107';
      const urgencyText = daysOverdue > 0 ? `เกิน ${daysOverdue} วัน` : 'วันนี้';

      // Create flex message
      const flexMessage = {
        type: 'flex',
        altText: `งานที่ยังค้างอยู่วันที่ ${thaiDate} ${assigneeName}\n- ${task.title}`,
        contents: {
          type: 'bubble',
          header: {
            type: 'box',
            layout: 'vertical',
            contents: [
              {
                type: 'text',
                text: '⚠️ งานค้าง',
                weight: 'bold',
                color: '#FFFFFF',
                size: 'lg'
              },
              {
                type: 'text',
                text: assigneeName,
                weight: 'bold',
                color: '#FFFFFF',
                size: 'sm'
              }
            ],
            backgroundColor: urgencyColor,
            paddingAll: '16px',
            spacing: 'sm'
          },
          body: {
            type: 'box',
            layout: 'vertical',
            contents: [
              {
                type: 'text',
                text: '📋 งาน',
                size: 'sm',
                color: '#666666',
                weight: 'bold'
              },
              {
                type: 'text',
                text: task.title,
                size: 'md',
                color: '#333333',
                wrap: true,
                weight: 'bold',
                margin: 'sm'
              },
              {
                type: 'separator',
                margin: 'md'
              },
              {
                type: 'box',
                layout: 'horizontal',
                contents: [
                  {
                    type: 'text',
                    text: '📅 ครบกำหนด:',
                    size: 'sm',
                    color: '#666666',
                    flex: 0
                  },
                  {
                    type: 'text',
                    text: thaiDate,
                    size: 'sm',
                    color: urgencyColor,
                    weight: 'bold',
                    margin: 'sm',
                    flex: 1
                  },
                  {
                    type: 'text',
                    text: urgencyText,
                    size: 'sm',
                    color: urgencyColor,
                    weight: 'bold',
                    align: 'end'
                  }
                ],
                margin: 'md'
              },
              {
                type: 'text',
                text: `🆔 Task ID: ${task.id}`,
                size: 'xs',
                color: '#999999',
                margin: 'md'
              }
            ],
            spacing: 'sm',
            paddingAll: '16px'
          },
          footer: {
            type: 'box',
            layout: 'vertical',
            contents: [
              {
                type: 'text',
                text: `แจ้งเตือนเมื่อ: ${moment().format('DD/MM/YYYY HH:mm')}`,
                size: 'xs',
                color: '#999999',
                align: 'center'
              }
            ],
            paddingAll: '12px'
          }
        }
      };

      await this.client.pushMessage(lineUserId, flexMessage);

      logger.info('NotificationService', `Sent flex notification for task ${task.id}`);
    } catch (error) {
      logger.error('NotificationService', `Failed to send notification for task ${task.id}`, error);
    }
  }

  async sendRoomTaskNotification(roomId, task) {
    try {
      const deadlineDate = moment(task.deadline);
      const thaiDate = this.formatThaiDate(deadlineDate);
      const today = moment();
      const daysOverdue = today.diff(deadlineDate, 'days');
      const assigneeName = this.extractAssigneeName(task.title);

      // Determine urgency color
      const urgencyColor = daysOverdue >= 3 ? '#DC3545' : daysOverdue >= 1 ? '#FD7E14' : '#FFC107';
      const urgencyText = daysOverdue > 0 ? `เกิน ${daysOverdue} วัน` : 'วันนี้';

      // Create flex message
      const flexMessage = {
        type: 'flex',
        altText: `งานที่ยังค้างอยู่วันที่ ${thaiDate} ${assigneeName}\n- ${task.title}`,
        contents: {
          type: 'bubble',
          header: {
            type: 'box',
            layout: 'vertical',
            contents: [
              {
                type: 'text',
                text: '⚠️ งานค้าง',
                weight: 'bold',
                color: '#FFFFFF',
                size: 'lg'
              },
              {
                type: 'text',
                text: assigneeName,
                weight: 'bold',
                color: '#FFFFFF',
                size: 'sm'
              }
            ],
            backgroundColor: urgencyColor,
            paddingAll: '16px',
            spacing: 'sm'
          },
          body: {
            type: 'box',
            layout: 'vertical',
            contents: [
              {
                type: 'text',
                text: '📋 งาน',
                size: 'sm',
                color: '#666666',
                weight: 'bold'
              },
              {
                type: 'text',
                text: task.title,
                size: 'md',
                color: '#333333',
                wrap: true,
                weight: 'bold',
                margin: 'sm'
              },
              {
                type: 'separator',
                margin: 'md'
              },
              {
                type: 'box',
                layout: 'horizontal',
                contents: [
                  {
                    type: 'text',
                    text: '📅 ครบกำหนด:',
                    size: 'sm',
                    color: '#666666',
                    flex: 0
                  },
                  {
                    type: 'text',
                    text: thaiDate,
                    size: 'sm',
                    color: urgencyColor,
                    weight: 'bold',
                    margin: 'sm',
                    flex: 1
                  },
                  {
                    type: 'text',
                    text: urgencyText,
                    size: 'sm',
                    color: urgencyColor,
                    weight: 'bold',
                    align: 'end'
                  }
                ],
                margin: 'md'
              },
              {
                type: 'text',
                text: `🆔 Task ID: ${task.id}`,
                size: 'xs',
                color: '#999999',
                margin: 'md'
              }
            ],
            spacing: 'sm',
            paddingAll: '16px'
          },
          footer: {
            type: 'box',
            layout: 'vertical',
            contents: [
              {
                type: 'text',
                text: `แจ้งเตือนเมื่อ: ${moment().format('DD/MM/YYYY HH:mm')}`,
                size: 'xs',
                color: '#999999',
                align: 'center'
              }
            ],
            paddingAll: '12px'
          }
        }
      };

      await this.client.pushMessage(roomId, flexMessage);

      logger.info('NotificationService', `Sent room flex notification for task ${task.id}`);
    } catch (error) {
      logger.error('NotificationService', `Failed to send room notification for task ${task.id}`, error);
    }
  }

  formatThaiDate(momentDate) {
    const day = momentDate.date();
    const month = momentDate.month() + 1;
    const year = momentDate.year() + 543;
    const shortYear = String(year).slice(-2);

    return `${day}/${month}/${shortYear}`;
  }

  extractAssigneeName(title) {
    // Try to extract name from patterns like "งานพี่xxx" or "งานของพี่xxx"
    const patterns = [
      /งานพี่([^\s:]+)/,
      /งานของพี่([^\s:]+)/,
      /พี่([^\s:]+)/
    ];

    for (const pattern of patterns) {
      const match = title.match(pattern);
      if (match) {
        return `พี่${match[1]}`;
      }
    }

    return 'งานที่ยังไม่ได้ทำ';
  }
}

module.exports = NotificationService;
