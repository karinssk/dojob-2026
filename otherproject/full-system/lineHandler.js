const { Client } = require('@line/bot-sdk');
const axios = require('axios');
const fs = require('fs').promises;
const path = require('path');
const moment = require('moment');
const config = require('./config');
const Database = require('./database');
const Utils = require('./utils');
const logger = require('./logger');

class LineHandler {
  constructor() {
    this.client = new Client(config.line);
    this.db = new Database();
    this.messageQueue = new Map(); // Queue for batching daily task messages (staff only)
    this.pendingTemplates = new Map(); // Queue for template messages waiting for images (boss/staff)
    this.standaloneImageQueue = new Map(); // Queue for standalone images sent after task creation
    
    // Timers for message batching
    this.TEXT_ONLY_WAIT_TIME = 58000; // 2.5 minutes for staff daily tasks
    this.MIXED_CONTENT_WAIT_TIME = 58000; // ~2 minutes for staff daily tasks
    this.TEMPLATE_IMAGE_WAIT_TIME = 5000; // 30 seconds wait for images after template

    // Ensure upload directory exists
    this.ensureUploadDir();
  }

  async ensureUploadDir() {
    try {
      await fs.mkdir(config.upload.dir, { recursive: true });
    } catch (error) {
      console.error('Error creating upload directory:', error);
    }
  }

  async handleEvent(event) {
    logger.info('handleEvent', 'Received event', { type: event.type });

    try {
      if (event.type === 'message') {
        const userId = event.source.userId;
        
        // Get user info from database (auto-create if missing)
        let userInfo = await this.db.getUserByLineId(userId);
        
        if (!userInfo) {
          logger.warn('handleEvent', 'User not found in database', { userId });

          // Try to create user from LINE profile
          let profile = null;
          try {
            profile = await this.client.getProfile(userId);
          } catch (profileError) {
            logger.error('handleEvent', 'Error getting LINE profile for new user', profileError, userId);
            profile = { userId, displayName: 'Unknown User' };
          }

          try {
            userInfo = await this.db.ensureUserByLineProfile(profile, 'staff');
          } catch (createError) {
            logger.error('handleEvent', 'Error creating user', createError, userId);
            return;
          }

          if (!userInfo) {
            logger.warn('handleEvent', 'User creation failed', { userId });
            return;
          }

          logger.info('handleEvent', 'User created', { 
            userId, 
            riseUserId: userInfo.rise_user_id 
          });
        }

        logger.info('handleEvent', 'User found', { 
          userId, 
          dutyRole: userInfo.duty_role 
        });

        if (event.message.type === 'text') {
          await this.handleTextMessage(event, userInfo);
        } else if (event.message.type === 'image') {
          await this.handleImageMessage(event, userInfo);
        }
      } else if (event.type === 'follow') {
        await this.handleFollow(event);
      }
    } catch (error) {
      logger.error('handleEvent', 'Error handling event', error);
    }
  }

  async handleTextMessage(event, userInfo) {
    const userId = event.source.userId;
    const text = event.message.text;
    const replyToken = event.replyToken;
    const trimmedText = text.trim();

    logger.info('handleTextMessage', 'Processing text message', { 
      userId, 
      dutyRole: userInfo.duty_role, 
      textLength: text.length 
    });

    try {
      // Quick reply menu for job/tasks
      if (trimmedText === 'job' || trimmedText === 'งาน') {
        const liffUrl = 'https://liff.line.me/2009171467-kn2AHM0C';
        const quickReplyMessage = {
          type: 'text',
          text: 'เลือกเมนูงาน',
          quickReply: {
            items: [
              {
                type: 'action',
                action: { type: 'uri', label: 'สร้าง Tasks', uri: liffUrl }
              },
              {
                type: 'action',
                action: { type: 'uri', label: 'สร้าง Events', uri: liffUrl }
              },
              {
                type: 'action',
                action: { type: 'uri', label: 'มอบหมายงาน', uri: liffUrl }
              },
              {
                type: 'action',
                action: { type: 'uri', label: 'อัพเดทงาน', uri: liffUrl }
              }
            ]
          }
        };

        await this.replyToUser(replyToken, quickReplyMessage);
        return;
      }

      // Check for command: "งานค้าง" to show overdue tasks
      if (trimmedText === 'งานค้าง') {
        await this.handleOverdueTasksCommand(event, userInfo, replyToken);
        return;
      }

      // Boss users: Handle task creation/updates
      if (userInfo.duty_role === 'boss') {
        await this.handleBossMessage(event, userInfo, text);
      } 
      // Staff users: Handle daily task saving with conditions
      else if (userInfo.duty_role === 'staff') {
        await this.handleStaffMessage(event, userInfo, text, replyToken);
      }
    } catch (error) {
      logger.error('handleTextMessage', 'Error processing text message', error, userId);
      await this.replyToUser(replyToken, 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
    }
  }

  async handleBossMessage(event, userInfo, text) {
    const userId = event.source.userId;
    const replyToken = event.replyToken;
    
    logger.info('handleBossMessage', 'Processing boss message', { userId });

    // Check for task creation format: "งาน<name> @<mention> [<date>]\n- task1\n- task2"
    // Date is optional - if not provided, use today's date
    const taskPatternWithDate = /^งาน(.+?)\s+@(.+?)\s+(\d{1,2}\/\d{1,2}\/\d{2,4})\s*([\s\S]*)/;
    const taskPatternNoDate = /^งาน(.+?)\s+@(.+?)\s*([\s\S]+)/;
    
    let match = text.match(taskPatternWithDate);
    let hasDate = true;
    
    if (!match) {
      match = text.match(taskPatternNoDate);
      hasDate = false;
    }
    
    if (match) {
      await this.handleBossTaskCreation(event, userInfo, match, hasDate);
    } else {
      logger.info('handleBossMessage', 'Message does not match boss task template', { userId });
    }
  }

  async handleOverdueTasksCommand(event, userInfo, replyToken) {
    const userId = event.source.userId;
    logger.info('handleOverdueTasksCommand', 'Processing overdue tasks command', { userId });

    try {
      // Get current month project
      const projectName = Utils.getCurrentMonthProjectName();
      const project = await this.db.getProjectByName(projectName);

      if (!project) {
        await this.replyToUser(replyToken, '⚠️ ไม่พบโปรเจกต์สำหรับเดือนนี้');
        return;
      }

      // Get overdue tasks for boss user (rise_users.id = 5)
      const bossUserId = 5;
      const overdueTasks = await this.db.getOverdueTasksForUser(bossUserId, project.id);

      logger.info('handleOverdueTasksCommand', `Found ${overdueTasks.length} overdue tasks for boss`, { userId, bossUserId });

      if (overdueTasks.length === 0) {
        await this.replyToUser(replyToken, '✅ ไม่มีงานค้างในขณะนี้');
        return;
      }

      // Create flex message to display overdue tasks
      const flexMessage = this.createOverdueTasksFlexMessage(overdueTasks, userInfo);
      await this.replyToUser(replyToken, flexMessage);

    } catch (error) {
      logger.error('handleOverdueTasksCommand', 'Error processing overdue tasks command', error, userId);
      await this.replyToUser(replyToken, 'เกิดข้อผิดพลาดในการดึงข้อมูลงานค้าง กรุณาลองใหม่อีกครั้ง');
    }
  }

  async handleBossTaskCreation(event, userInfo, match, hasDate) {
    const userId = event.source.userId;
    const replyToken = event.replyToken;
    
    let assigneeName, mentionedUser, dateStr, tasksText;
    
    if (hasDate) {
      [, assigneeName, mentionedUser, dateStr, tasksText] = match;
    } else {
      // No date provided - use today's date
      [, assigneeName, mentionedUser, tasksText] = match;
      const today = moment();
      const thaiYear = (today.year() + 543).toString().slice(-2); // Get last 2 digits of Buddhist year
      dateStr = `${today.date()}/${today.month() + 1}/${thaiYear}`;
    }

    logger.info('handleBossTaskCreation', 'Creating boss tasks', { 
      userId, 
      assigneeName: assigneeName.trim(),
      mentionedUser: mentionedUser.trim(),
      dateStr,
      hasDate
    });

    try {
      // Parse tasks from the text (lines starting with -)
      const tasks = this.parseTasksFromText(tasksText);
      
      if (tasks.length === 0) {
        logger.warn('handleBossTaskCreation', 'No tasks found in message', { userId });
        await this.replyToUser(replyToken, '⚠️ ไม่พบรายการงาน กรุณาใส่งานที่ขึ้นต้นด้วย - ');
        return;
      }

      logger.info('handleBossTaskCreation', `Found ${tasks.length} tasks`, { userId });

      // Parse start date from format like "1/10/68" or "15/12/2568"
      const startDate = this.parseStartDate(dateStr);
      const startDateFormatted = startDate ? Utils.formatDateForMySQL(startDate.toDate()) : Utils.formatDateForMySQL(new Date());
      
      // Calculate deadline (start date + 3 days)
      const deadline = startDate ? startDate.clone().add(3, 'days').toDate() : null;
      const deadlineFormatted = deadline ? Utils.formatDateForMySQL(deadline) : null;

      // Store pending boss task creation and wait for images (1 minute)
      this.pendingTemplates.set(userId, {
        type: 'boss_creation',
        assigneeName: assigneeName.trim(),
        mentionedUser: mentionedUser.trim(),
        dateStr: dateStr,
        tasks: tasks,
        startDateFormatted: startDateFormatted,
        deadlineFormatted: deadlineFormatted,
        deadline: deadline,
        userInfo: userInfo,
        userId: userId,
        replyToken: replyToken,
        images: [],
        timestamp: Date.now(),
        timer: null
      });

      const pendingData = this.pendingTemplates.get(userId);
      
      // Set timer to process after 1 minute
      pendingData.timer = setTimeout(() => {
        this.processPendingBossTaskCreation(userId);
      }, this.TEMPLATE_IMAGE_WAIT_TIME);

      logger.info('handleBossTaskCreation', 'Boss tasks queued, waiting for images (30 sec)', { userId });
    } catch (error) {
      logger.error('handleBossTaskCreation', 'Error creating boss tasks', error, userId);
      await this.replyToUser(replyToken, 'เกิดข้อผิดพลาดในการสร้างงาน กรุณาลองใหม่อีกครั้ง');
    }
  }

  async processPendingBossTaskCreation(userId) {
    logger.info('processPendingBossTaskCreation', 'Processing boss tasks', { userId });

    if (!this.pendingTemplates.has(userId)) {
      return;
    }

    const pendingData = this.pendingTemplates.get(userId);
    
    // Clear from pending
    this.pendingTemplates.delete(userId);

    try {
      // Get or create project
      const projectName = Utils.getCurrentMonthProjectName();
      let project = await this.db.getProjectByName(projectName);
      
      if (!project) {
        const projectId = await this.db.createProject(projectName, pendingData.userInfo.rise_user_id);
        project = { id: projectId };
        logger.info('processPendingBossTaskCreation', 'Created new project', { projectId });
      }

      logger.info('processPendingBossTaskCreation', 'Parsed dates', { 
        startDate: pendingData.startDateFormatted,
        deadline: pendingData.deadlineFormatted
      });

      const createdTasks = [];

      // Create each task
      for (let i = 0; i < pendingData.tasks.length; i++) {
        const taskText = pendingData.tasks[i];
        const taskTitle = Utils.cleanTextForDatabase(taskText);
        const taskDescription = Utils.cleanTextForDatabase(
          `งาน${pendingData.assigneeName} ${pendingData.dateStr} - ${taskText}`
        );

        // Add images to first task if available (save in rise_tasks.images)
        let taskImages = null;
        if (i === 0 && pendingData.images.length > 0) {
          // Format images for database: serialize array of image info
          taskImages = this.formatImagesForDatabase(pendingData.images);
        }

        const taskData = {
          title: taskTitle,
          description: taskDescription,
          project_id: project.id,
          assigned_to: pendingData.userInfo.rise_user_id,
          deadline: pendingData.deadlineFormatted,
          start_date: pendingData.startDateFormatted,
          created_by: pendingData.userInfo.rise_user_id,
          images: taskImages
        };

        const taskId = await this.db.createTask(taskData);
        createdTasks.push({ id: taskId, title: taskText });

        // Log activity
        await this.db.logActivity({
          created_by: pendingData.userInfo.rise_user_id,
          action: 'created',
          log_type: 'task',
          log_type_title: taskTitle,
          log_type_id: taskId,
          changes: JSON.stringify(taskData),
          log_for: 'project',
          log_for_id: project.id,
          log_for2: 'task',
          log_for_id2: taskId
        });

        logger.info('processPendingBossTaskCreation', 'Task created', { 
          taskId, 
          taskNumber: i + 1,
          title: taskText,
          hasImages: !!taskImages
        });
      }

      // Send confirmation with flex message
      const flexMessage = this.createBossTaskCreationFlexMessage(pendingData, createdTasks);
      await this.replyToUser(pendingData.replyToken, flexMessage);

      logger.info('processPendingBossTaskCreation', 'All boss tasks created successfully', { 
        count: createdTasks.length,
        imagesCount: pendingData.images.length
      });
    } catch (error) {
      logger.error('processPendingBossTaskCreation', 'Error processing boss tasks', error, userId);
      await this.replyToUser(pendingData.replyToken, 'เกิดข้อผิดพลาดในการสร้างงาน กรุณาลองใหม่อีกครั้ง');
    }
  }

  parseTasksFromText(tasksText) {
    const lines = tasksText.split('\n');
    const tasks = [];
    
    for (const line of lines) {
      const trimmed = line.trim();
      if (trimmed.startsWith('-')) {
        tasks.push(trimmed.substring(1).trim());
      }
    }
    
    return tasks;
  }

  parseStartDate(dateStr) {
    const moment = require('moment');
    
    // Parse format like "1/10/68" or "15/12/2568"
    const parts = dateStr.split('/');
    if (parts.length !== 3) return null;
    
    let day = parseInt(parts[0]);
    let month = parseInt(parts[1]);
    let year = parseInt(parts[2]);
    
    // Handle 2-digit years
    if (year < 100) {
      // 68 = 2568 (Buddhist), 25 = 2568
      if (year < 50) {
        year = 2500 + year; // 25 -> 2525
      } else {
        year = 2500 + year; // 68 -> 2568
      }
    }
    
    // Convert Buddhist year to Gregorian
    if (year > 2500) {
      year = year - 543;
    }
    
    return moment({ year, month: month - 1, day });
  }

  async handleStaffMessage(event, userInfo, text, replyToken) {
    const userId = event.source.userId;
    
    logger.info('handleStaffMessage', 'Processing staff message', { userId });

    // Check if this is a task update format: "อัพเดทงาน @mention\n-task\n*description"
    // Allow variations: อัพเดทงาน, อัพเดท, อัเดท, update, Update
    const updatePattern = /^(อัพเดทงาน|อัพเดท|อัเดทงาน|อัเดท|update|Update)\s+@(.+?)[\s\n]+([\s\S]*)/i;
    const updateMatch = text.match(updatePattern);

    if (updateMatch) {
      // This is a task update, handle it separately
      await this.handleStaffTaskUpdate(event, userInfo, updateMatch, replyToken);
      return;
    }

    // Otherwise, handle as daily task saving
//     const textLength = text.trim().length;
//     const minLength = config.dailyTask.minTextLength;
//     const isDisabledTime = Utils.isDailyTaskDisabledTime();

//     logger.info('handleStaffMessage', 'Checking daily task conditions', { 
//       textLength, 
//       minLength, 
//       isDisabledTime 
//     });

//     // Ignore if text is too short
//     if (!textLength < minLength) {
//       logger.info('handleStaffMessage', 'Text too short, ignoring', { 
//         textLength, 
//         minLength 
//       });
//       return;
//     }

//     // Ignore if within disabled time (07:00-17:29)
//     if (!isDisabledTime) {
//       logger.info('handleStaffMessage', 'Within disabled time, ignoring');
//       return;
//     }

    // Queue the message for batching (daily task)
    this.queueStaffMessage(userId, 'text', replyToken, text, null, new Date(), userInfo);
  }

  async handleStaffTaskUpdate(event, userInfo, match, replyToken) {
    const userId = event.source.userId;
    const [, keyword, mentionedUser, updateText] = match;

    logger.info('handleStaffTaskUpdate', 'Processing task update', { 
      userId,
      keyword: keyword,
      mentionedUser: mentionedUser.trim()
    });

    try {
      // Parse task title and description from update text
      const lines = updateText.trim().split('\n').filter(line => line.trim());

      if (lines.length < 2) {
        logger.warn('handleStaffTaskUpdate', 'Invalid format - need at least 2 lines', { userId });
        await this.replyToUser(replyToken, '⚠️ รูปแบบไม่ถูกต้อง กรุณาใช้:\nอัพเดทงาน @mention\n-ชื่องาน\n*รายละเอียด');
        return;
      }

      // Find line starting with '-' (task title)
      const taskTitleLine = lines.find(line => line.trim().startsWith('-'));
      // Find line starting with '*' (task description)
      const taskDescriptionLine = lines.find(line => line.trim().startsWith('*'));

      if (!taskTitleLine || !taskDescriptionLine) {
        logger.warn('handleStaffTaskUpdate', 'Missing required lines', { userId });
        await this.replyToUser(replyToken, '⚠️ ต้องมีบรรทัดที่ขึ้นต้นด้วย - และ *');
        return;
      }

      const taskTitle = taskTitleLine.replace(/^-\s*/, '').trim();
      const taskDescription = taskDescriptionLine.replace(/^\*\s*/, '').trim();

      logger.info('handleStaffTaskUpdate', 'Parsed update', { taskTitle, taskDescription });

      // Store pending staff task update and wait for images (1 minute)
      this.pendingTemplates.set(userId, {
        type: 'staff_update',
        taskTitle: taskTitle,
        taskDescription: taskDescription,
        mentionedUser: mentionedUser.trim(),
        userInfo: userInfo,
        userId: userId,
        replyToken: replyToken,
        images: [],
        timestamp: Date.now(),
        timer: null
      });

      const pendingData = this.pendingTemplates.get(userId);
      
      // Set timer to process after 1 minute
      pendingData.timer = setTimeout(() => {
        this.processPendingStaffTaskUpdate(userId);
      }, this.TEMPLATE_IMAGE_WAIT_TIME);

      logger.info('handleStaffTaskUpdate', 'Staff task update queued, waiting for images (30 sec)', { userId });
    } catch (error) {
      logger.error('handleStaffTaskUpdate', 'Error updating task', error, userId);
      await this.replyToUser(replyToken, 'เกิดข้อผิดพลาดในการอัพเดทงาน กรุณาลองใหม่อีกครั้ง');
    }
  }

  async processPendingStaffTaskUpdate(userId) {
    logger.info('processPendingStaffTaskUpdate', 'Processing staff task update', { userId });

    if (!this.pendingTemplates.has(userId)) {
      return;
    }

    const pendingData = this.pendingTemplates.get(userId);
    
    // Clear from pending
    this.pendingTemplates.delete(userId);

    try {
      // Get current month project
      const projectName = Utils.getCurrentMonthProjectName();
      const project = await this.db.getProjectByName(projectName);

      if (!project) {
        logger.warn('processPendingStaffTaskUpdate', 'Project not found', { projectName });
        await this.replyToUser(pendingData.replyToken, '⚠️ ไม่พบโปรเจกต์สำหรับเดือนนี้');
        return;
      }

      // Find task by title
      logger.info('processPendingStaffTaskUpdate', 'Searching for task', { 
        taskTitle: pendingData.taskTitle, 
        projectId: project.id 
      });
      const task = await this.db.findTaskByTitle(pendingData.taskTitle, project.id);

      if (!task) {
        logger.warn('processPendingStaffTaskUpdate', 'Task not found', { taskTitle: pendingData.taskTitle });
        await this.replyToUser(pendingData.replyToken, `⚠️ ไม่พบงาน: ${pendingData.taskTitle}`);
        return;
      }

      logger.info('processPendingStaffTaskUpdate', 'Found task', { taskId: task.id, title: task.title });

      // Update task description (no images in task table for staff)
      try {
        await this.db.updateTask(task.id, {
          description: Utils.cleanTextForDatabase(pendingData.taskDescription),
          status: 'done',
          status_id: 3
        });
        logger.info('processPendingStaffTaskUpdate', 'Task updated', { taskId: task.id });
      } catch (updateError) {
        logger.error('processPendingStaffTaskUpdate', 'Error updating task', updateError, userId);
        throw updateError;
      }

      // If images exist, add them as a comment in rise_project_comments
      if (pendingData.images.length > 0) {
        try {
          const formattedImages = this.formatImagesForDatabase(pendingData.images);
          logger.info('processPendingStaffTaskUpdate', 'Images formatted', { 
            count: pendingData.images.length,
            serializedLength: formattedImages.length 
          });

          const commentData = {
            created_by: pendingData.userInfo.rise_user_id,
            description: 'Additional images',
            project_id: project.id,
            task_id: task.id,
            files: formattedImages
          };

          const commentId = await this.db.addTaskComment(commentData);
          
          logger.info('processPendingStaffTaskUpdate', 'Comment with images added', { 
            commentId,
            imagesCount: pendingData.images.length 
          });
        } catch (commentError) {
          logger.error('processPendingStaffTaskUpdate', 'Error adding comment with images', commentError, userId);
          // Don't throw - task was updated successfully, comment is optional
        }
      }

      // Log activity
      await this.db.logActivity({
        created_by: pendingData.userInfo.rise_user_id,
        action: 'updated',
        log_type: 'task',
        log_type_title: pendingData.taskTitle,
        log_type_id: task.id,
        changes: JSON.stringify({
          description: pendingData.taskDescription,
          status: 'done',
          images_in_comment: pendingData.images.length
        }),
        log_for: 'project',
        log_for_id: project.id,
        log_for2: 'task',
        log_for_id2: task.id
      });

      // Send confirmation with flex message
      const flexMessage = this.createStaffTaskUpdateFlexMessage(pendingData, task.id);
      await this.replyToUser(pendingData.replyToken, flexMessage);

      logger.info('processPendingStaffTaskUpdate', 'Task updated successfully', { 
        taskId: task.id,
        imagesCount: pendingData.images.length 
      });
    } catch (error) {
      logger.error('processPendingStaffTaskUpdate', 'Error processing task update', error, userId);
      await this.replyToUser(pendingData.replyToken, 'เกิดข้อผิดพลาดในการอัพเดทงาน กรุณาลองใหม่อีกครั้ง');
    }
  }

  async handleImageMessage(event, userInfo) {
    const userId = event.source.userId;
    const messageId = event.message.id;
    const replyToken = event.replyToken;

    logger.info('handleImageMessage', 'Processing image', { userId, messageId });

    try {
      // Download image
      const imageBuffer = await this.getLineContent(messageId);
      const filename = `${Date.now()}_${userId}.jpg`;
      const filepath = path.join(config.upload.dir, filename);
      
      await fs.writeFile(filepath, imageBuffer);

      logger.info('handleImageMessage', 'Image saved', { filename });

      // Check if user has pending template (boss creation or staff update)
      if (this.pendingTemplates.has(userId)) {
        const pendingData = this.pendingTemplates.get(userId);
        pendingData.images.push(filepath);
        
        // Reset timer to wait for more images
        if (pendingData.timer) {
          clearTimeout(pendingData.timer);
        }
        
        pendingData.timer = setTimeout(() => {
          if (pendingData.type === 'boss_creation') {
            this.processPendingBossTaskCreation(userId);
          } else if (pendingData.type === 'staff_update') {
            this.processPendingStaffTaskUpdate(userId);
          }
        }, this.TEMPLATE_IMAGE_WAIT_TIME);
        
        logger.info('handleImageMessage', 'Image added to pending template', { 
          userId, 
          type: pendingData.type,
          totalImages: pendingData.images.length 
        });
      }
      // Otherwise, handle as staff daily task image or update
      else if (userInfo.duty_role === 'staff') {
        // Staff: always allow images (no disabled-time restriction)
        // Check if user has a queued message - add to queue
        if (this.messageQueue.has(userId)) {
          this.queueStaffMessage(userId, 'image', replyToken, null, filepath, new Date(), userInfo);
        } 
        // No queued message - this is a standalone image to update last task
        else {
          await this.handleStaffStandaloneImageUpdate(userId, userInfo, filepath, replyToken);
        }
      } else {
        logger.info('handleImageMessage', 'Image ignored - no pending template', { userId });
      }
    } catch (error) {
      logger.error('handleImageMessage', 'Error processing image', error, userId);
    }
  }

  // Handle standalone images sent after daily task is saved (update last task today)
  async handleStaffStandaloneImageUpdate(userId, userInfo, filepath, replyToken) {
    logger.info('handleStaffStandaloneImageUpdate', 'Processing standalone image update', { userId });

    // Queue standalone images with 30 second wait
    if (!this.standaloneImageQueue) {
      this.standaloneImageQueue = new Map();
    }

    if (!this.standaloneImageQueue.has(userId)) {
      this.standaloneImageQueue.set(userId, {
        images: [],
        timer: null,
        userInfo: userInfo,
        replyToken: replyToken
      });
    }

    const imageQueue = this.standaloneImageQueue.get(userId);
    imageQueue.images.push(filepath);
    imageQueue.replyToken = replyToken;

    // Clear existing timer
    if (imageQueue.timer) {
      clearTimeout(imageQueue.timer);
    }

    // Set timer to process after 30 seconds
    imageQueue.timer = setTimeout(() => {
      this.processStandaloneImageUpdate(userId);
    }, this.TEMPLATE_IMAGE_WAIT_TIME);

    logger.info('handleStaffStandaloneImageUpdate', 'Image queued for standalone update', { 
      userId, 
      totalImages: imageQueue.images.length 
    });
  }

  async processStandaloneImageUpdate(userId) {
    logger.info('processStandaloneImageUpdate', 'Processing standalone image update', { userId });

    if (!this.standaloneImageQueue || !this.standaloneImageQueue.has(userId)) {
      logger.warn('processStandaloneImageUpdate', 'No queue found for user', { userId });
      return;
    }

    const imageQueue = this.standaloneImageQueue.get(userId);
    const images = imageQueue.images;
    const userInfo = imageQueue.userInfo;
    const replyToken = imageQueue.replyToken;

    // Clear from queue
    this.standaloneImageQueue.delete(userId);

    logger.info('processStandaloneImageUpdate', 'Queue data', {
      userId,
      imagesCount: images.length,
      hasUserInfo: !!userInfo,
      hasReplyToken: !!replyToken
    });

    try {
      // Get current month project
      const projectName = Utils.getCurrentMonthProjectName();
      logger.info('processStandaloneImageUpdate', 'Looking for project', { projectName });
      
      const project = await this.db.getProjectByName(projectName);

      if (!project) {
        logger.warn('processStandaloneImageUpdate', 'Project not found', { projectName });
        await this.replyToUser(replyToken, '⚠️ ไม่พบโปรเจกต์สำหรับเดือนนี้');
        return;
      }

      logger.info('processStandaloneImageUpdate', 'Project found', { 
        projectId: project.id, 
        projectName: project.project_name 
      });

      // Get last task created today by this user
      logger.info('processStandaloneImageUpdate', 'Searching for last task', {
        riseUserId: userInfo.rise_user_id,
        projectId: project.id
      });
      
      const lastTask = await this.db.getLastTaskToday(userInfo.rise_user_id, project.id);

      if (!lastTask) {
        logger.warn('processStandaloneImageUpdate', 'No task found today', { 
          userId,
          riseUserId: userInfo.rise_user_id,
          projectId: project.id
        });
        await this.replyToUser(replyToken, '⚠️ ไม่พบงานที่สร้างวันนี้');
        return;
      }

      logger.info('processStandaloneImageUpdate', 'Found last task today', { 
        taskId: lastTask.id, 
        title: lastTask.title 
      });

      // Format images for database
      const formattedImages = this.formatImagesForDatabase(images);
      logger.info('processStandaloneImageUpdate', 'Images formatted', {
        originalCount: images.length,
        serializedLength: formattedImages.length
      });

      // Add images as comment
      const commentData = {
        created_by: userInfo.rise_user_id,
        description: 'Additional images',
        project_id: project.id,
        task_id: lastTask.id,
        files: formattedImages
      };

      logger.info('processStandaloneImageUpdate', 'Adding comment', commentData);
      
      const commentId = await this.db.addTaskComment(commentData);

      logger.info('processStandaloneImageUpdate', 'Images added to task', { 
        commentId,
        taskId: lastTask.id,
        imagesCount: images.length 
      });

      // Send confirmation with flex message
      const flexMessage = this.createStandaloneImageUpdateFlexMessage(images.length);
      await this.replyToUser(replyToken, flexMessage);

    } catch (error) {
      logger.error('processStandaloneImageUpdate', 'Error processing standalone images', error, userId);
      
      // Log full error details
      console.error('Full error details:', {
        message: error.message,
        stack: error.stack,
        userId: userId
      });
      
      await this.replyToUser(replyToken, 'เกิดข้อผิดพลาดในการอัพเดทรูปภาพ กรุณาลองใหม่อีกครั้ง');
    }
  }

  // Queue staff messages for batching
  queueStaffMessage(userId, type, replyToken, text, imagePath, timestamp, userInfo) {
    logger.info('queueStaffMessage', 'Queuing message', { 
      userId, 
      type, 
      hasText: !!text, 
      hasImage: !!imagePath 
    });

    if (!this.messageQueue.has(userId)) {
      this.messageQueue.set(userId, {
        replyToken: replyToken,
        messages: [],
        timer: null,
        userInfo: userInfo,
        lastMessageTime: Date.now(),
        hasImages: type === 'image'
      });
    } else {
      const userQueue = this.messageQueue.get(userId);
      userQueue.lastMessageTime = Date.now();
      
      if (type === 'image') {
        userQueue.hasImages = true;
      }
    }

    const userQueue = this.messageQueue.get(userId);
    userQueue.replyToken = replyToken;

    userQueue.messages.push({
      type: type,
      text: text,
      imagePath: imagePath,
      timestamp: timestamp
    });

    // Clear existing timer
    if (userQueue.timer) {
      clearTimeout(userQueue.timer);
    }

    const waitTime = userQueue.hasImages ? this.MIXED_CONTENT_WAIT_TIME : this.TEXT_ONLY_WAIT_TIME;

    logger.info('queueStaffMessage', 'Setting timer', { 
      waitTime, 
      hasImages: userQueue.hasImages, 
      queueSize: userQueue.messages.length 
    });

    userQueue.timer = setTimeout(() => {
      this.processQueuedStaffMessages(userId);
    }, waitTime);
  }

  // Process queued staff messages
  async processQueuedStaffMessages(userId) {
    logger.info('processQueuedStaffMessages', 'Processing queue', { userId });

    if (!this.messageQueue.has(userId)) {
      return;
    }

    const userQueue = this.messageQueue.get(userId);
    const messages = userQueue.messages;
    const replyToken = userQueue.replyToken;
    const userInfo = userQueue.userInfo;

    // Clear the queue
    this.messageQueue.delete(userId);

    try {
      let combinedText = '';
      let imagePath = null;

      // Combine all messages
      for (const message of messages) {
        if (message.type === 'text' && message.text) {
          if (combinedText) combinedText += '\n';
          combinedText += message.text;
        } else if (message.type === 'image' && message.imagePath) {
          imagePath = message.imagePath;
        }
      }

      // Create daily task
      const taskId = await this.createDailyTask(userId, userInfo, combinedText, imagePath);

      if (taskId) {
        // Send confirmation with flex message
        const flexMessage = this.createStaffDailyTaskFlexMessage(userInfo, taskId, combinedText, imagePath);
        await this.replyToUser(replyToken, flexMessage);

        logger.info('processQueuedStaffMessages', 'Daily task created', { 
          taskId, 
          userId 
        });
      }
    } catch (error) {
      logger.error('processQueuedStaffMessages', 'Error processing queued messages', error, userId);
      await this.replyToUser(replyToken, 'เกิดข้อผิดพลาดในการบันทึกงาน กรุณาลองใหม่อีกครั้ง');
    }
  }

  // Create daily task for staff
  async createDailyTask(userId, userInfo, text, imagePath) {
    logger.info('createDailyTask', 'Creating daily task', { userId });

    try {
      // Get or create project
      const projectName = Utils.getCurrentMonthProjectName();
      let project = await this.db.getProjectByName(projectName);

      if (!project) {
        const projectId = await this.db.createProject(projectName, userInfo.rise_user_id);
        project = { id: projectId };
      }

      // Create task
      const displayName = userInfo.nick_name || userInfo.line_display_name;
      const taskTitle = Utils.cleanTextForDatabase(`งานรายวัน ${displayName}`);
      const taskDescription = text ? Utils.cleanTextForDatabase(text) : (imagePath ? `Image: ${path.basename(imagePath)}` : '');

      const taskData = {
        title: taskTitle,
        description: taskDescription,
        project_id: project.id,
        assigned_to: userInfo.rise_user_id,
        status: 'done',
        status_id: 3,
        deadline: null,
        start_date: Utils.formatDateForMySQL(new Date()),
        created_by: userInfo.rise_user_id,
        images: imagePath ? path.basename(imagePath) : null
      };

      const taskId = await this.db.createTask(taskData);

      // Force status to done in case createTask applies defaults
      try {
        await this.db.updateTask(taskId, { status: 'done', status_id: 3 });
      } catch (updateError) {
        logger.error('createDailyTask', 'Error enforcing done status', updateError, userId);
      }

      // Log activity
      await this.db.logActivity({
        created_by: userInfo.rise_user_id,
        action: 'created',
        log_type: 'task',
        log_type_title: taskTitle,
        log_type_id: taskId,
        changes: JSON.stringify(taskData),
        log_for: 'project',
        log_for_id: project.id,
        log_for2: 'task',
        log_for_id2: taskId
      });

      logger.info('createDailyTask', 'Daily task created successfully', { taskId });

      return taskId;
    } catch (error) {
      logger.error('createDailyTask', 'Error creating daily task', error, userId);
      throw error;
    }
  }

  async handleFollow(event) {
    const userId = event.source.userId;
    logger.info('handleFollow', 'New follower', { userId });

    try {
      // Get LINE profile
      const profile = await this.client.getProfile(userId);
      
      // Check if user exists in database
      const existingUser = await this.db.getUserByLineId(userId);

      if (!existingUser) {
        await this.client.replyMessage(event.replyToken, {
          type: 'text',
          text: `สวัสดีค่ะ คุณ ${profile.displayName}\nกรุณาติดต่อผู้ดูแลระบบเพื่อเพิ่มบัญชีของคุณค่ะ`
        });
      } else {
        await this.client.replyMessage(event.replyToken, {
          type: 'text',
          text: `ยินดีต้อนรับค่ะ คุณ ${profile.displayName}!\nระบบพร้อมใช้งานแล้วค่ะ`
        });
      }
    } catch (error) {
      logger.error('handleFollow', 'Error handling follow event', error);
    }
  }

  async getLineContent(messageId) {
    try {
      const stream = await this.client.getMessageContent(messageId);
      const chunks = [];
      
      for await (const chunk of stream) {
        chunks.push(chunk);
      }
      
      return Buffer.concat(chunks);
    } catch (error) {
      logger.error('getLineContent', 'Error getting LINE content', error);
      throw error;
    }
  }

  async replyToUser(replyToken, message) {
    try {
      // Check if message is a flex message object or plain text
      const messageObject = typeof message === 'string' 
        ? { type: 'text', text: message }
        : message;

      await this.client.replyMessage(replyToken, messageObject);
      
      logger.info('replyToUser', 'Reply sent successfully');
    } catch (error) {
      logger.error('replyToUser', 'Error sending reply', error);
    }
  }

  async pushMessage(userId, message) {
    try {
      // Check if message is a flex message object or plain text
      const messageObject = typeof message === 'string' 
        ? { type: 'text', text: message }
        : message;

      await this.client.pushMessage(userId, messageObject);
      
      logger.info('pushMessage', 'Push message sent successfully', { userId });
    } catch (error) {
      logger.error('pushMessage', 'Error sending push message', error);
    }
  }

  // Format images for database storage (PHP serialized format)
  formatImagesForDatabase(imagePaths) {
    const images = imagePaths.map((filepath, index) => {
      const filename = path.basename(filepath);
      
      // Get file size - use try/catch for safety
      let fileSize = '0';
      try {
        const stats = require('fs').statSync(filepath);
        fileSize = stats.size.toString();
      } catch (error) {
        logger.warn('formatImagesForDatabase', 'Could not get file size', { filepath });
      }
      
      return {
        file_name: filename,
        file_size: fileSize,
        file_id: null,
        service_type: null
      };
    });

    // Create PHP serialized array format
    const serializedItems = images.map((img, index) => {
      return `i:${index};a:4:{s:9:"file_name";s:${img.file_name.length}:"${img.file_name}";s:9:"file_size";s:${img.file_size.length}:"${img.file_size}";s:7:"file_id";N;s:12:"service_type";N;}`;
    }).join('');

    return `a:${images.length}:{${serializedItems}}`;
  }

  // Create flex message for boss task creation confirmation
  createBossTaskCreationFlexMessage(pendingData, createdTasks) {
    const moment = require('moment');
    const taskListContents = createdTasks.map((task, index) => ({
      type: "text",
      text: `${index + 1}. ${task.title}`,
      size: "sm",
      color: "#333333",
      wrap: true,
      margin: index === 0 ? "md" : "sm"
    }));

    const contents = {
      type: "bubble",
      header: {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "text",
            text: "✅ สร้างงานสำเร็จ",
            weight: "bold",
            color: "#FFFFFF",
            size: "lg",
          },
          {
            type: "text",
            text: `${pendingData.assigneeName} - ${createdTasks.length} รายการ`,
            weight: "bold",
            color: "#FFFFFF",
            size: "sm",
          },
        ],
        backgroundColor: "#28A745",
        paddingAll: "16px",
        spacing: "sm",
      },
      body: {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "text",
            text: `📅 วันที่เริ่ม: ${pendingData.dateStr}`,
            size: "sm",
            color: "#666666",
            weight: "bold",
          },
          ...(pendingData.deadlineFormatted ? [{
            type: "text",
            text: `⏰ กำหนดส่ง: ${moment(pendingData.deadline).format('DD/MM/YYYY')}`,
            size: "sm",
            color: "#FD7E14",
            weight: "bold",
            margin: "sm",
          }] : []),
          ...(pendingData.images.length > 0 ? [{
            type: "text",
            text: `📷 รูปภาพ: ${pendingData.images.length} รูป`,
            size: "sm",
            color: "#17A2B8",
            weight: "bold",
            margin: "sm",
          }] : []),
          {
            type: "separator",
            margin: "md",
          },
          {
            type: "text",
            text: "📋 รายการงาน",
            size: "sm",
            color: "#666666",
            weight: "bold",
            margin: "md",
          },
          ...taskListContents,
        ],
        spacing: "sm",
        paddingAll: "16px",
      },
      footer: {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "text",
            text: `สร้างเมื่อ: ${moment().format('DD/MM/YYYY HH:mm')}`,
            size: "xs",
            color: "#999999",
            align: "center",
          },
        ],
        paddingAll: "12px",
      },
    };

    return {
      type: "flex",
      altText: `✅ สร้างงาน${pendingData.assigneeName} สำเร็จ ${createdTasks.length} รายการ`,
      contents: contents,
    };
  }

  // Create flex message for staff task update confirmation
  createStaffTaskUpdateFlexMessage(pendingData, taskId) {
    const moment = require('moment');
    
    return {
      type: "flex",
      altText: `✅ อัพเดทงาน "${pendingData.taskTitle}" สำเร็จ`,
      contents: {
        type: "bubble",
        header: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: "✅ อัพเดทงานสำเร็จ",
              weight: "bold",
              color: "#FFFFFF",
              size: "lg",
            },
            {
              type: "text",
              text: "งานเสร็จสมบูรณ์",
              weight: "bold",
              color: "#FFFFFF",
              size: "sm",
            },
          ],
          backgroundColor: "#007BFF",
          paddingAll: "16px",
          spacing: "sm",
        },
        body: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: "📋 ชื่องาน",
              size: "sm",
              color: "#666666",
              weight: "bold",
            },
            {
              type: "text",
              text: pendingData.taskTitle,
              size: "md",
              color: "#333333",
              wrap: true,
              weight: "bold",
              margin: "sm",
            },
            {
              type: "separator",
              margin: "md",
            },
            {
              type: "text",
              text: "📝 รายละเอียด",
              size: "sm",
              color: "#666666",
              weight: "bold",
              margin: "md",
            },
            {
              type: "text",
              text: pendingData.taskDescription,
              size: "sm",
              color: "#333333",
              wrap: true,
              margin: "sm",
            },
            ...(pendingData.images.length > 0 ? [
              {
                type: "separator",
                margin: "md",
              },
              {
                type: "text",
                text: `📷 แนบรูปภาพ: ${pendingData.images.length} รูป`,
                size: "sm",
                color: "#17A2B8",
                weight: "bold",
                margin: "md",
              }
            ] : []),
            {
              type: "separator",
              margin: "md",
            },
            {
              type: "text",
              text: "✓ สถานะ: เสร็จสมบูรณ์",
              size: "sm",
              color: "#28A745",
              weight: "bold",
              margin: "md",
            },
          ],
          spacing: "sm",
          paddingAll: "16px",
        },
        footer: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: `Task ID: ${taskId} | ${moment().format('DD/MM/YYYY HH:mm')}`,
              size: "xs",
              color: "#999999",
              align: "center",
            },
          ],
          paddingAll: "12px",
        },
      },
    };
  }

  // Create flex message for staff daily task confirmation
  createStaffDailyTaskFlexMessage(userInfo, taskId, text, imagePath) {
    const moment = require('moment');
    const displayName = userInfo.nick_name || userInfo.line_display_name || 'Staff';
    const textPreview = text ? (text.length > 100 ? text.substring(0, 100) + '...' : text) : null;

    return {
      type: "flex",
      altText: `✅ บันทึกงานรายวัน ของ ${displayName} สำเร็จ`,
      contents: {
        type: "bubble",
        header: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: "✅ บันทึกงานรายวันสำเร็จ",
              weight: "bold",
              color: "#FFFFFF",
              size: "lg",
            },
            {
              type: "text",
              text: displayName,
              weight: "bold",
              color: "#FFFFFF",
              size: "sm",
            },
          ],
          backgroundColor: "#6F42C1",
          paddingAll: "16px",
          spacing: "sm",
        },
        body: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: `📅 วันที่: ${moment().format('DD/MM/YYYY')}`,
              size: "sm",
              color: "#666666",
              weight: "bold",
            },
            ...(textPreview ? [
              {
                type: "separator",
                margin: "md",
              },
              {
                type: "text",
                text: "📝 รายละเอียด",
                size: "sm",
                color: "#666666",
                weight: "bold",
                margin: "md",
              },
              {
                type: "text",
                text: textPreview,
                size: "sm",
                color: "#333333",
                wrap: true,
                margin: "sm",
              }
            ] : []),
            ...(imagePath ? [
              {
                type: "separator",
                margin: "md",
              },
              {
                type: "text",
                text: "📷 พร้อมรูปภาพ",
                size: "sm",
                color: "#17A2B8",
                weight: "bold",
                margin: "md",
              }
            ] : []),
          ],
          spacing: "sm",
          paddingAll: "16px",
        },
        footer: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: `Task ID: ${taskId} | ${moment().format('DD/MM/YYYY HH:mm')}`,
              size: "xs",
              color: "#999999",
              align: "center",
            },
          ],
          paddingAll: "12px",
        },
      },
    };
  }

  // Create flex message for standalone image update confirmation
  createStandaloneImageUpdateFlexMessage(imageCount) {
    const moment = require('moment');
    
    return {
      type: "flex",
      altText: "✅ อัพเดทรูปภาพเพิ่มเติมไปยังงานวันนี้เรียบร้อยแล้ว",
      contents: {
        type: "bubble",
        header: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: "✅ อัพเดทรูปภาพสำเร็จ",
              weight: "bold",
              color: "#FFFFFF",
              size: "lg",
            },
            {
              type: "text",
              text: "เพิ่มรูปภาพไปยังงานวันนี้",
              weight: "bold",
              color: "#FFFFFF",
              size: "sm",
            },
          ],
          backgroundColor: "#17A2B8",
          paddingAll: "16px",
          spacing: "sm",
        },
        body: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: "📷 รูปภาพที่อัพโหลด",
              size: "sm",
              color: "#666666",
              weight: "bold",
            },
            {
              type: "text",
              text: `${imageCount} รูป`,
              size: "xl",
              color: "#17A2B8",
              weight: "bold",
              margin: "sm",
              align: "center",
            },
            {
              type: "separator",
              margin: "md",
            },
            {
              type: "text",
              text: "✓ รูปภาพถูกเพิ่มเข้างานล่าสุดของคุณวันนี้เรียบร้อยแล้ว",
              size: "sm",
              color: "#333333",
              wrap: true,
              margin: "md",
              align: "center",
            },
          ],
          spacing: "sm",
          paddingAll: "16px",
        },
        footer: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: `อัพเดทเมื่อ: ${moment().format('DD/MM/YYYY HH:mm')}`,
              size: "xs",
              color: "#999999",
              align: "center",
            },
          ],
          paddingAll: "12px",
        },
      },
    };
  }

  // Create flex message to display overdue tasks
  createOverdueTasksFlexMessage(overdueTasks, userInfo) {
    const moment = require('moment');
    const displayName = userInfo.nick_name || userInfo.line_display_name || 'User';
    
    // Create task list
    const taskListContents = overdueTasks.map((task, index) => {
      const deadlineDate = moment(task.deadline);
      const today = moment();
      const daysOverdue = today.diff(deadlineDate, 'days');
      const thaiDate = `${deadlineDate.date()}/${deadlineDate.month() + 1}/${(deadlineDate.year() + 543).toString().slice(-2)}`;
      
      // Determine color based on days overdue
      const urgencyColor = daysOverdue >= 3 ? '#DC3545' : daysOverdue >= 1 ? '#FD7E14' : '#FFC107';
      
      return {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "box",
            layout: "horizontal",
            contents: [
              {
                type: "text",
                text: `${index + 1}.`,
                size: "sm",
                color: "#666666",
                flex: 0,
                margin: "none"
              },
              {
                type: "text",
                text: task.title,
                size: "sm",
                color: "#333333",
                wrap: true,
                flex: 1,
                margin: "sm"
              }
            ]
          },
          {
            type: "box",
            layout: "horizontal",
            contents: [
              {
                type: "text",
                text: "📅",
                size: "xs",
                flex: 0
              },
              {
                type: "text",
                text: `ครบกำหนด: ${thaiDate}`,
                size: "xs",
                color: urgencyColor,
                flex: 1,
                margin: "sm"
              },
              {
                type: "text",
                text: daysOverdue > 0 ? `เกิน ${daysOverdue} วัน` : 'วันนี้',
                size: "xs",
                color: urgencyColor,
                weight: "bold",
                align: "end"
              }
            ],
            margin: "xs"
          },
          {
            type: "separator",
            margin: "md"
          }
        ],
        margin: index === 0 ? "none" : "md"
      };
    });

    return {
      type: "flex",
      altText: `งานค้าง: พบ ${overdueTasks.length} รายการ`,
      contents: {
        type: "bubble",
        header: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: "⚠️ งานค้าง",
              weight: "bold",
              color: "#FFFFFF",
              size: "lg",
            },
            {
              type: "text",
              text: `${overdueTasks.length} รายการ - ${displayName}`,
              weight: "bold",
              color: "#FFFFFF",
              size: "sm",
            },
          ],
          backgroundColor: "#DC3545",
          paddingAll: "16px",
          spacing: "sm",
        },
        body: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: "รายการงานที่ยังค้างอยู่:",
              size: "sm",
              color: "#666666",
              weight: "bold",
            },
            ...taskListContents,
          ],
          spacing: "sm",
          paddingAll: "16px",
        },
        footer: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: `ตรวจสอบเมื่อ: ${moment().format('DD/MM/YYYY HH:mm')}`,
              size: "xs",
              color: "#999999",
              align: "center",
            },
          ],
          paddingAll: "12px",
        },
      },
    };
  }
}

module.exports = LineHandler;
