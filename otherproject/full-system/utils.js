const moment = require('moment');

class Utils {
  // Clean text for database (remove emojis)
  static cleanTextForDatabase(text) {
    if (!text) return text;
    
    return text
      .replace(/[\u{1F600}-\u{1F64F}]/gu, '') // Emoticons
      .replace(/[\u{1F300}-\u{1F5FF}]/gu, '') // Misc Symbols and Pictographs
      .replace(/[\u{1F680}-\u{1F6FF}]/gu, '') // Transport and Map
      .replace(/[\u{1F1E0}-\u{1F1FF}]/gu, '') // Regional country flags
      .replace(/[\u{2600}-\u{26FF}]/gu, '')   // Misc symbols
      .replace(/[\u{2700}-\u{27BF}]/gu, '')   // Dingbats
      .replace(/[\u{E000}-\u{F8FF}]/gu, '')   // Private use area
      .replace(/[\u{FE00}-\u{FE0F}]/gu, '')   // Variation selectors
      .replace(/[\u{1F900}-\u{1F9FF}]/gu, '') // Supplemental Symbols and Pictographs
      .replace(/[\u{1FA70}-\u{1FAFF}]/gu, '') // Symbols and Pictographs Extended-A
      .trim();
  }

  // Parse Thai date (e.g., "1 ม.ค. 68" or "1 มกราคม 2568")
  static parseThaiDate(dateString) {
    const thaiMonths = {
      'ม.ค.': 0, 'มกราคม': 0,
      'ก.พ.': 1, 'กุมภาพันธ์': 1,
      'มี.ค.': 2, 'มีนาคม': 2,
      'เม.ย.': 3, 'เมษายน': 3,
      'พ.ค.': 4, 'พฤษภาคม': 4,
      'มิ.ย.': 5, 'มิถุนายน': 5,
      'ก.ค.': 6, 'กรกฎาคม': 6,
      'ส.ค.': 7, 'สิงหาคม': 7,
      'ก.ย.': 8, 'กันยายน': 8,
      'ต.ค.': 9, 'ตุลาคม': 9,
      'พ.ย.': 10, 'พฤศจิกายน': 10,
      'ธ.ค.': 11, 'ธันวาคม': 11
    };

    try {
      const parts = dateString.trim().split(/\s+/);
      if (parts.length < 3) return null;

      const day = parseInt(parts[0]);
      const monthStr = parts[1];
      let year = parseInt(parts[2]);

      const month = thaiMonths[monthStr];
      if (month === undefined) return null;

      // Convert Buddhist year to Gregorian if needed
      if (year > 2500) {
        year = year - 543;
      }

      const date = new Date(year, month, day);
      return date;
    } catch (error) {
      console.error('Error parsing Thai date:', error);
      return null;
    }
  }

  // Format date to MySQL format
  static formatDateForMySQL(date) {
    if (!date) return null;
    return moment(date).format('YYYY-MM-DD');
  }

  // Get current month project name in Thai
  static getCurrentMonthProjectName() {
    const thaiMonths = [
      'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
      'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];

    const now = new Date();
    const month = now.getMonth();
    const thaiYear = now.getFullYear() + 543;

    return `งานรายวัน เดือน${thaiMonths[month]} ${thaiYear}`;
  }

  // Check if current time is within disabled hours for daily tasks
  static isDailyTaskDisabledTime() {
    const config = require('./config');
    const now = new Date();
    const hour = now.getHours();
    const minute = now.getMinutes();

    const currentTime = hour + (minute / 60);
    const startTime = config.dailyTask.disableStartHour;
    const endTime = config.dailyTask.disableEndHour + (config.dailyTask.disableEndMinute / 60);

    return currentTime >= startTime && currentTime <= endTime;
  }

  // Check if text meets minimum length requirement
  static isTextLengthValid(text, minLength = 50) {
    if (!text) return false;
    return text.trim().length >= minLength;
  }

  // Create PHP serialized array for files
  static createPHPSerializedFilesArray(files) {
    const filesCount = files.length;
    let serialized = `a:${filesCount}:{`;

    files.forEach((file, index) => {
      const fileName = file.file_name;
      const fileSize = String(file.file_size);

      serialized += `i:${index};a:4:{`;
      serialized += `s:9:"file_name";s:${fileName.length}:"${fileName}";`;
      serialized += `s:9:"file_size";s:${fileSize.length}:"${fileSize}";`;
      serialized += `s:7:"file_id";N;`;
      serialized += `s:12:"service_type";N;`;
      serialized += `}`;
    });

    serialized += `}`;
    return serialized;
  }

  // Extract task number from title
  static extractTaskNumber(title) {
    const match = title.match(/งานที่\s*(\d+)/);
    return match ? parseInt(match[1]) : null;
  }

  // Format task message for LINE
  static formatTaskMessage(task) {
    let message = `📋 งาน: ${task.title}\n`;
    
    if (task.description) {
      message += `📝 รายละเอียด: ${task.description}\n`;
    }
    
    if (task.deadline) {
      const deadline = moment(task.deadline).format('DD/MM/YYYY');
      message += `⏰ กำหนดส่ง: ${deadline}\n`;
    }
    
    if (task.status) {
      message += `📊 สถานะ: ${task.status}\n`;
    }
    
    return message;
  }
}

module.exports = Utils;
