require('dotenv').config();

module.exports = {
  line: {
    channelSecret: process.env.LINE_CHANNEL_SECRET,
    channelAccessToken: process.env.LINE_CHANNEL_ACCESS_TOKEN || process.env.LINE_ACCESS_TOKEN
  },
  database: {
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB,
    port: process.env.DB_PORT || 3306,
    charset: 'utf8mb4'
  },
  server: {
    port: process.env.PORT || 3010
  },
  upload: {
   dir: '/var/www/dojob.rubyshop.co.th/files/timeline_files'
  },
  notification: {
    enabled: process.env.ENABLE_NOTIFICATIONS === 'true',
    roomId: process.env.NOTIFICATION_ROOM_ID,
    sendToRoom: true
  },
  dailyTask: {
    minTextLength: 50, // Minimum characters to save as daily task
    disableStartHour: 7, // TESTING: Changed from 7 to 0 (disabled all day = enabled all day)
    disableEndHour: 17, // TESTING: Changed from 17 to 0
    disableEndMinute: 29  // TESTING: Changed from 29 to 0
    // ORIGINAL VALUES: disableStartHour: 7, disableEndHour: 17, disableEndMinute: 29
    // This makes daily tasks ENABLED 24/7 for testing
  }
};
