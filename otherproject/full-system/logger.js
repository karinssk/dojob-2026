const fs = require('fs');
const path = require('path');

class Logger {
  constructor() {
    this.logsDir = path.join(__dirname, 'logs');
    this.usersLogsDir = path.join(this.logsDir, 'users');
    
    // Create directories if they don't exist
    if (!fs.existsSync(this.logsDir)) {
      fs.mkdirSync(this.logsDir, { recursive: true });
    }
    
    if (!fs.existsSync(this.usersLogsDir)) {
      fs.mkdirSync(this.usersLogsDir, { recursive: true });
    }
  }

  getLogFilename() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}.log`;
  }

  getTimestamp() {
    return new Date().toISOString();
  }

  writeToLog(level, functionName, message, data = null, userId = null) {
    const logEntry = {
      timestamp: this.getTimestamp(),
      level,
      function: functionName,
      message,
      data: data ? JSON.stringify(data) : undefined,
      userId
    };

    const logString = JSON.stringify(logEntry) + '\n';
    const consoleMessage = `[${logEntry.timestamp}] [${level}] ${userId ? `[User: ${userId}] ` : ''}[${functionName}] ${message}`;

    switch(level) {
      case 'ERROR':
        console.error(consoleMessage);
        break;
      case 'WARN':
        console.warn(consoleMessage);
        break;
      case 'INFO':
        console.info(consoleMessage);
        break;
      default:
        console.log(consoleMessage);
    }

    // Write to main log file
    const mainLogPath = path.join(this.logsDir, this.getLogFilename());
    fs.appendFileSync(mainLogPath, logString);

    // Write to user-specific log file if userId provided
    if (userId) {
      const userDir = path.join(this.usersLogsDir, userId);
      if (!fs.existsSync(userDir)) {
        fs.mkdirSync(userDir, { recursive: true });
      }

      const userLogPath = path.join(userDir, this.getLogFilename());
      fs.appendFileSync(userLogPath, logString);
    }
  }

  extractUserId(params) {
    if (!params) return null;
    if (params.userId) return params.userId;
    if (Array.isArray(params) && params.length > 0 && params[0] && typeof params[0] === 'string') {
      return params[0];
    }
    return null;
  }

  enter(functionName, params = null) {
    const userId = this.extractUserId(params);
    this.writeToLog('INFO', functionName, 'ENTER', params, userId);
  }

  exit(functionName, result = null, userId = null) {
    this.writeToLog('INFO', functionName, 'EXIT', result, userId);
  }

  info(functionName, message, data = null, userId = null) {
    const extractedUserId = userId || this.extractUserId(data);
    this.writeToLog('INFO', functionName, message, data, extractedUserId);
  }

  warn(functionName, message, data = null, userId = null) {
    const extractedUserId = userId || this.extractUserId(data);
    this.writeToLog('WARN', functionName, message, data, extractedUserId);
  }

  error(functionName, message, error = null, userId = null) {
    const errorData = error ? {
      message: error.message,
      stack: error.stack,
      ...(error.response ? { response: error.response.data } : {})
    } : null;

    const extractedUserId = userId || (error && error.userId ? error.userId : null);
    this.writeToLog('ERROR', functionName, message, errorData, extractedUserId);
  }

  debug(functionName, message, data = null, userId = null) {
    const extractedUserId = userId || this.extractUserId(data);
    this.writeToLog('DEBUG', functionName, message, data, extractedUserId);
  }

  user(userId, functionName, message, data = null) {
    this.writeToLog('INFO', functionName, message, data, userId);
  }
}

module.exports = new Logger();
