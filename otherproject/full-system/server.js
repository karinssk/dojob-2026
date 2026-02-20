const express = require('express');
const { middleware } = require('@line/bot-sdk');
const config = require('./config');
const LineHandler = require('./lineHandler');
const NotificationService = require('./notificationService');
const logger = require('./logger');

const app = express();
const lineHandler = new LineHandler();
const notificationService = new NotificationService();

// LINE webhook middleware
const lineMiddleware = middleware(config.line);

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ 
    status: 'OK', 
    timestamp: new Date().toISOString(),
    service: 'LINE Tasks System'
  });
});

app.get('/', (req, res) => {
  res.json({ 
    status: 'OK', 
    message: 'LINE Tasks System is running',
    timestamp: new Date().toISOString()
  });
});

// LINE webhook endpoint
// app.post('/webhook', lineMiddleware, async (req, res) => {
//   console.log('event',req.body.events)
//   try {
//  logger.info('Webhook', 'Received webhook request', { 
//       eventsCount: req.body.events.length ,
//       events: req.body.events
//     });
//     // Process each event
//     const promises = req.body.events.map(event => lineHandler.handleEvent(event));
//     await Promise.all(promises);

//     res.status(200).json({ status: 'success' });
//   } catch (error) {
//     logger.error('Webhook', 'Error processing webhook', error);
//     res.status(500).json({ error: 'Internal server error' });
//   }
// });



function isReplyTokenValid(lineTimestamp, grace = 60000) {
  const now = Date.now();
  const diff = now - lineTimestamp;

  console.log(`[ReplyToken Check] LINE timestamp=${lineTimestamp}, Server now=${now}, Diff=${diff}ms`);

  return diff <= grace;
}

app.post('/webhook', lineMiddleware, async (req, res) => {
  console.log('event', req.body.events);
  try {
    logger.info('Webhook', 'Received webhook request', { 
      eventsCount: req.body.events.length,
      events: req.body.events
    });

    // Process each event with replyToken check
    const promises = req.body.events.map(async (event) => {
      // only events with replyToken need check
      if (event.replyToken) {
        if (!isReplyTokenValid(event.timestamp)) {
          logger.warn('Webhook', 'Reply token expired, consider pushMessage fallback', {
            replyToken: event.replyToken,
            timestamp: event.timestamp
          });
          // Optional: do push instead of reply
          // return client.pushMessage(event.source.userId, { type: 'text', text: 'Token expired!' });
        }
      }
      return lineHandler.handleEvent(event);
    });

    await Promise.all(promises);

    res.status(200).json({ status: 'success' });
  } catch (error) {
    logger.error('Webhook', 'Error processing webhook', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});










// Test notifications endpoint (for manual testing)
app.get('/test-notifications', async (req, res) => {
  try {
    logger.info('TestNotifications', 'Manual notification test triggered');
    await notificationService.checkAndSendNotifications();
    res.json({ 
      status: 'Notification check completed', 
      timestamp: new Date().toISOString() 
    });
  } catch (error) {
    logger.error('TestNotifications', 'Error in manual notification test', error);
    res.status(500).json({ 
      error: 'Notification test failed', 
      message: error.message 
    });
  }
});

// Error handling middleware
app.use((error, req, res, next) => {
  if (error instanceof Error) {
    logger.error('Express', 'Express error', error);
    res.status(500).json({ error: 'Internal server error' });
  } else {
    next();
  }
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({ error: 'Not found' });
});

// Start server
const port = config.server.port;
app.listen(port, () => {
  console.log(`====================================`);
  console.log(`LINE Tasks System`);
  console.log(`====================================`);
  console.log(`Server running on port ${port}`);
  console.log(`Webhook URL: http://localhost:${port}/webhook`);
  console.log(`Health check: http://localhost:${port}/health`);
  console.log(`Test notifications: http://localhost:${port}/test-notifications`);
  console.log(`====================================`);
  
  // Start notification service
  notificationService.start();
  
  logger.info('Server', 'LINE Tasks System started successfully', { port });
});

// Graceful shutdown
process.on('SIGTERM', async () => {
  console.log('SIGTERM received, shutting down gracefully');
  logger.info('Server', 'SIGTERM received, shutting down gracefully');
  await lineHandler.db.close();
  process.exit(0);
});

process.on('SIGINT', async () => {
  console.log('SIGINT received, shutting down gracefully');
  logger.info('Server', 'SIGINT received, shutting down gracefully');
  await lineHandler.db.close();
  process.exit(0);
});
