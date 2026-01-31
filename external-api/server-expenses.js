const express = require('express');
const bodyParser = require('body-parser');
const fs = require('fs');
const axios = require('axios');
const mysql = require('mysql2/promise');
const path = require('path');
const multer = require('multer');
const cron = require('node-cron');
const app = express();
const port = 3011;
require('dotenv').config();
// LINE Bot configuration
const LINE_ACCESS_TOKEN = process.env.LINE_ACCESS_TOKEN;
const REPORT_LINE_USER_ID = process.env.REPORT_LINE_USER_ID;

const monthlyReportsRoute = require('./routes/monthlyReportsRoute.js');
const dailyReportsRoute = require('./routes/dailyReportsRoute.js');

app.use('/api/monthly', monthlyReportsRoute.router);
app.use('/api/daily', dailyReportsRoute.router);


// Import data mappings
const { titile_expenses, client_and_project, catagory_expenses } = require('./data.js');

// Import monthly reports
const { 
  generateMonthlyExpenseSummary, 
  generateMonthlyExpenseData, 
  createMonthlyHeaderFlexMessage, 
  createProjectFlexMessage, 
  sendMonthlySummaryReport 
} = require('./routes/monthlyReportsRoute.js');

// Import daily reports
const {
  sendDailySummaryFlexReport,
  sendDailySummaryReport
} = require('./routes/dailyReportsRoute.js');

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Create uploads directory
const uploadsDir = path.join(__dirname, 'uploads');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadsDir);
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, file.fieldname + '-' + uniqueSuffix + path.extname(file.originalname));
  }
});
const upload = multer({ storage: storage });

// MySQL Pool
const dbPool = mysql.createPool({
  host: process.env.DB_HOST ,
  user: process.env.DB_USER ,
  password: process.env.DB_PASSWORD ,
  database: process.env.DB ,
  port: process.env.DB_PORT ,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Make shared variables available to routes
app.locals.dbPool = dbPool;
app.locals.client_and_project = client_and_project;
app.locals.formatNumberWithCommas = formatNumberWithCommas;







// Store user sessions for file uploads
const userSessions = new Map();

// Root route
app.get('/', (req, res) => {
  res.writeHead(200, {'Content-Type': 'text/plain'});
  var message = 'web api for rubyshop\n',
      version = 'NodeJS ' + process.versions.node + '\n',
      response = [message, version].join('\n');
  res.end(response);
});

// === Helper Functions ===

// Format number with commas for display
function formatNumberWithCommas(number) {
  if (typeof number !== 'number') {
    number = parseFloat(number);
  }
  
  if (isNaN(number)) {
    return '0';
  }
  
  // Convert to fixed decimal places (2) and then format with commas
  const fixedNumber = number.toFixed(2);
  const parts = fixedNumber.split('.');
  
  // Add commas to the integer part
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  
  // Remove trailing zeros from decimal part
  if (parts[1]) {
    parts[1] = parts[1].replace(/0+$/, '0');
    if (parts[1] === '') {
      return parts[0]; // Return without decimal point if no decimal places
    }
    return parts.join('.');
  }
  
  return parts[0];
}

// Get current date in Thai format (dd/mm/yyyy)
function getCurrentThaiDate() {
  const now = new Date();
  const day = now.getDate().toString().padStart(2, '0');
  const month = (now.getMonth() + 1).toString().padStart(2, '0');
  const year = (now.getFullYear() + 543).toString().slice(-2); // Convert to Buddhist year and get last 2 digits
  return `${day}/${month}/${year}`;
}

// Clean and parse amount with proper floating point handling
function parseAmount(amountStr) {
  if (!amountStr || typeof amountStr !== 'string') {
    throw new Error('Invalid amount format');
  }
  
  // Remove all spaces and special characters except digits, dots, and commas
  let cleanAmount = amountStr.trim();
  
  // Handle different amount formats
  // Remove commas (thousands separator)
  cleanAmount = cleanAmount.replace(/,/g, '');
  
  // Validate that we only have digits and at most one decimal point
  if (!/^\d+(\.\d{1,2})?$/.test(cleanAmount)) {
    throw new Error(`Invalid amount format: ${amountStr}`);
  }
  
  const amount = parseFloat(cleanAmount);
  
  if (isNaN(amount) || amount <= 0) {
    throw new Error(`Invalid amount: ${amountStr}`);
  }
  
  // Round to 2 decimal places to handle floating point precision
  return Math.round(amount * 100) / 100;
}

// Clean text input to handle special characters
function cleanTextInput(text) {
  if (!text || typeof text !== 'string') {
    return '';
  }
  
  // Trim whitespace and handle special characters
  return text.trim();
}

// Validate and clean category (should be numeric)
function parseCategory(categoryStr) {
  if (!categoryStr || typeof categoryStr !== 'string') {
    throw new Error('Category is required');
  }
  
  const cleanCategory = categoryStr.trim();
  
  // Check if it's a valid number or text that can be used for keyword matching
  if (/^\d+$/.test(cleanCategory)) {
    return parseInt(cleanCategory);
  }
  
  // If not a number, treat as keyword for category lookup
  return cleanCategory;
}

// Get LINE user profile
async function getLineUserProfile(userId) {
  try {
    const response = await axios.get(`https://api.line.me/v2/bot/profile/${userId}`, {
      headers: {
        'Authorization': `Bearer ${LINE_ACCESS_TOKEN}`
      }
    });
    console.log('LINE user profile:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error getting LINE user profile:', error);
    return { displayName: 'คุณ', userId: userId };
  }
}

// Find or create Rise user
async function findOrCreateRiseUser(userProfile) {
  try {
    // Try to find existing user by display name or create new one
    const [existingUsers] = await dbPool.query(
      'SELECT id FROM rise_users WHERE first_name = ? OR email = ?',
      [userProfile.displayName, `${userProfile.userId}@line.user`]
    );

    if (existingUsers.length > 0) {
      return existingUsers[0].id;
    }

    // Create new user
    const [result] = await dbPool.query(
      `INSERT INTO rise_users (
        first_name, last_name, user_type, email, status, 
        job_title, language, created_at
      ) VALUES (?, ?, 'staff', ?, 'active', 'Staff', 'thai', NOW())`,
      [userProfile.displayName, '', `${userProfile.userId}@line.user`]
    );

    return result.insertId;
  } catch (error) {
    console.error('Error finding/creating Rise user:', error);
    return 1; // Default to admin user
  }
}

// Get Rise User ID from LINE User ID
async function getRiseUserIdFromLineId(lineUserId) {
  try {
    // Check user_mappings table
    const [mappings] = await dbPool.query(
      'SELECT rise_user_id FROM user_mappings WHERE line_user_id = ?',
      [lineUserId]
    );

    if (mappings.length > 0 && mappings[0].rise_user_id) {
      return mappings[0].rise_user_id;
    }

    // Create mapping if not found
    const userProfile = await getLineUserProfile(lineUserId);
    const riseUserId = await findOrCreateRiseUser(userProfile);

    if (riseUserId) {
      await dbPool.query(
        'INSERT INTO user_mappings (line_user_id, line_display_name, rise_user_id) VALUES (?, ?, ?)',
        [lineUserId, userProfile.displayName, riseUserId]
      );
    }

    return riseUserId;
  } catch (err) {
    console.error('Error getting Rise user ID:', err.message);
    return 1; // Default to admin user
  }
}

// Parse Buddhist date to MySQL date - FIXED
function parseBuddhistDate(dateStr) {
  try {
    const parts = dateStr.split('/');
    if (parts.length === 3) {
      const day = parseInt(parts[0]);
      const month = parseInt(parts[1]);
      let year = parseInt(parts[2]);
      
      // Handle 2-digit year format (68 -> 2568 -> 2025)
      if (year < 100) {
        // For years 68-99, assume 25xx (Buddhist era)
        if (year >= 68) {
          year = 2500 + year; // 68 -> 2568
        } else {
          year = 2600 + year; // 01-67 -> 2601-2667
        }
      }
      
      // Convert Buddhist year to Christian year
      const christianYear = year - 543;
      
      return `${christianYear}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
    }
  } catch (error) {
    console.error('Error parsing date:', error);
  }
  
  // Return current date if parsing fails
  const now = new Date();
  return now.toISOString().split('T')[0];
}

// Get Buddhist month name from date - FIXED
function getBuddhistMonthYear(dateStr) {
  try {
    const parts = dateStr.split('/');
    if (parts.length === 3) {
      const month = parseInt(parts[1]);
      let year = parseInt(parts[2]);
      
      // Enhanced year handling to treat both 68 and 25 as same era
      if (year < 100) {
        // For 2-digit years, assume Buddhist era 25xx
        year = 2500 + year; // 68 -> 2568, 25 -> 2525
      }
      
      const monthNames = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
      ];
      
      const monthName = monthNames[month - 1] || 'มกราคม';
      return `ค่าใช้จ่าย เดือน${monthName} ${year}`;
    }
  } catch (error) {
    console.error('Error getting Buddhist month/year:', error);
  }
  
  // Fallback to current month/year
  const now = new Date();
  const monthNames = [
    'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
  ];
  const buddhistYear = now.getFullYear() + 543;
  return `ค่าใช้จ่าย เดือน${monthNames[now.getMonth()]} ${buddhistYear}`;
}

// Find category by keyword or ID
function findCategoryByKeyword(categoryInput) {
  // If it's a number, try to find by ID first
  if (typeof categoryInput === 'number') {
    const categoryById = catagory_expenses.find(cat => cat.id === categoryInput);
    if (categoryById) {
      return categoryById.id;
    }
  }
  
  // If it's text, search by keyword
  const lowerText = categoryInput.toString().toLowerCase();
  
  for (const category of catagory_expenses) {
    if (lowerText.includes(category.keyword.toLowerCase())) {
      return category.id;
    }
  }
  
  return 45; // Default to "ค่าวัสดุอุปกรณ์"
}

// Find title by keyword
function findTitleByKeyword(text) {
  const lowerText = text.toLowerCase();
  
  for (const title of titile_expenses) {
    if (lowerText.includes(title.keyword.toLowerCase())) {
      return title.title;
    }
  }
  
  return text; // Return original text if no match
}

// Find client and project by keyword - FIXED to check existing projects first
async function findClientAndProject(keyword, inputDate = null, useExistingProject = false) {
  const lowerKeyword = keyword.toLowerCase();
  console.log('Looking for keyword:', lowerKeyword);
  console.log('inputDate:', inputDate);
  console.log('useExistingProject:', useExistingProject);
  
  for (const item of client_and_project) {
    console.log('Checking against:', item.keyword.toLowerCase());
    if (lowerKeyword.includes(item.keyword.toLowerCase())) {
      console.log('Found match for:', item.keyword);
      
      // Find client_id
      const [clients] = await dbPool.query(
        'SELECT id FROM rise_clients WHERE company_name LIKE ?',
        [`%${item.client}%`]
      );
      
      let clientId = 0;
      if (clients.length === 0) {
        // Create new client
        const [clientResult] = await dbPool.query(
          `INSERT INTO rise_clients (
            company_name, type, created_date, created_by, owner_id,
            starred_by, group_ids, last_lead_status, client_migration_date,
            stripe_customer_id, stripe_card_ending_digit
          ) VALUES (?, 'organization', CURDATE(), 1, 1, '', '', '', CURDATE(), '', 0)`,
          [item.client]
        );
        clientId = clientResult.insertId;
      } else {
        clientId = clients[0].id;
      }
      
      // Handle ruby keyword specially - use monthly project with enhanced logic
      if (item.keyword.toLowerCase() === 'ruby') {
        console.log('Processing ruby keyword - looking for monthly project');
        
        // Generate project title based on input date
        let projectTitle;
        if (inputDate) {
          projectTitle = getBuddhistMonthYear(inputDate);
          console.log('Generated project title:', projectTitle);
        } else {
          // Fallback to current month/year
          const now = new Date();
          const monthNames = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
          ];
          const buddhistYear = now.getFullYear() + 543;
          projectTitle = `ค่าใช้จ่าย เดือน${monthNames[now.getMonth()]} ${buddhistYear}`;
        }
        
        console.log('Generated project title:', projectTitle);
        
        let projectId = 0;
        
        if (useExistingProject) {
          // Search for existing project with exact title match
          console.log('Searching for existing project with title:', projectTitle);
          const [existingProjects] = await dbPool.query(
            'SELECT id, title FROM rise_projects WHERE title = ? AND deleted = 0',
            [projectTitle]
          );
          
          if (existingProjects.length > 0) {
            projectId = existingProjects[0].id;
            console.log('Found existing project:', projectId, projectTitle);
          } else {
            console.log('No existing project found with title:', projectTitle);
            // Return error or create new project based on requirements
            throw new Error(`ไม่พบโครงการ "${projectTitle}" กรุณาตรวจสอบวันที่หรือสร้างโครงการใหม่`);
          }
        } else {
          // Look for existing project first, create if not found
          const [monthlyProjects] = await dbPool.query(
            'SELECT id, title FROM rise_projects WHERE title = ? AND deleted = 0',
            [projectTitle]
          );
          
          if (monthlyProjects.length > 0) {
            projectId = monthlyProjects[0].id;
            console.log('Found existing monthly project:', projectId, projectTitle);
          } else {
            // Create new monthly project
            const [monthlyResult] = await dbPool.query(
              `INSERT INTO rise_projects (
                title, client_id, created_date, created_by, status,
                status_id, starred_by, estimate_id, order_id, deleted
              ) VALUES (?, ?, CURDATE(), 1, 'open', 1, '', 0, 0, 0)`,
              [projectTitle, clientId]
            );
            projectId = monthlyResult.insertId;
            console.log('Created new monthly project:', projectId, projectTitle);
          }
        }
        
        return {
          clientId: clientId,
          clientName: item.client,
          projectId: projectId,
          projectName: projectTitle
        };
      }
      
      // Handle other projects normally
      let projectId = 0;
      if (item.project) {
        if (useExistingProject) {
          // Search for existing project only
          const [projects] = await dbPool.query(
            'SELECT id FROM rise_projects WHERE title LIKE ? AND deleted = 0',
            [`%${item.project}%`]
          );
          
          if (projects.length > 0) {
            projectId = projects[0].id;
          } else {
            throw new Error(`ไม่พบโครงการ "${item.project}" กรุณาตรวจสอบชื่อโครงการ`);
          }
        } else {
          // Look for existing project first, create if not found
          const [projects] = await dbPool.query(
            'SELECT id FROM rise_projects WHERE title LIKE ? AND deleted = 0',
            [`%${item.project}%`]
          );
          
          if (projects.length === 0) {
            // Create new project
            const [projectResult] = await dbPool.query(
              `INSERT INTO rise_projects (
                title, client_id, created_date, created_by, status,
                status_id, starred_by, estimate_id, order_id, deleted
              ) VALUES (?, ?, CURDATE(), 1, 'open', 1, '', 0, 0, 0)`,
              [item.project, clientId]
            );
            projectId = projectResult.insertId;
          } else {
            projectId = projects[0].id;
          }
        }
      }
      
      return {
        clientId: clientId,
        clientName: item.client,
        projectId: projectId,
        projectName: item.project || ''
      };
    }
  }
  
  // Default return
  console.log('No match found for keyword:', lowerKeyword);
  return {
    clientId: 0,
    clientName: 'Unknown Client',
    projectId: 0,
    projectName: 'Unknown Project'
  };
}

// Create PHP serialized files array
function createPHPSerializedFilesArray(files) {
  if (!files || files.length === 0) {
    return '';
  }
  
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

// Calculate VAT with proper rounding - 7%
function calculateVAT(preVatAmount, vatRate = 0.07) {
  const vatAmount = preVatAmount * vatRate;
  const totalWithVat = preVatAmount + vatAmount;
  
  // Round to 2 decimal places with proper rounding
  return {
    preVat: Math.round(preVatAmount * 100) / 100,
    vatAmount: Math.round(vatAmount * 100) / 100,
    postVat: Math.round(totalWithVat * 100) / 100
  };
}

// Get VAT rate from database - Default to 7%
async function getVATRate() {
  try {
    const [taxes] = await dbPool.query('SELECT percentage FROM rise_taxes WHERE id = 2');
    if (taxes.length > 0) {
      return taxes[0].percentage / 100; // Convert percentage to decimal
    }
  } catch (error) {
    console.error('Error getting VAT rate:', error);
  }
  return 0.07; // Default 7%
}

// Log activity
async function logActivity(
  createdBy, 
  action, 
  logType, 
  logTypeTitle, 
  logTypeId, 
  changes = null, 
  logFor = '0', 
  logForId = 0, 
  logFor2 = null, 
  logFor2Id = null
) {
  try {
    const sql = `
      INSERT INTO rise_activity_logs (
        created_at, created_by, action, log_type, log_type_title,
        log_type_id, changes, log_for, log_for_id, log_for2,
        log_for_id2, deleted
      ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
    `;
    
    const [result] = await dbPool.query(sql, [
      createdBy, action, logType, logTypeTitle, logTypeId,
      changes, logFor, logForId, logFor2,
      logFor2Id
    ]);
    
    console.log(`Activity logged: ${action} ${logType} #${logTypeId} by user #${createdBy}`);
    return result.insertId;
  } catch (error) {
    console.error('Error logging activity:', error);
  }
}

// Create flex message for expense confirmation
function createExpenseConfirmationFlexMessage(expenseData, result, userDisplayName) {
  const statusColor = result.success ? "#22c55e" : "#ef4444";
  const statusIcon = result.success ? "" : "❌";
  const statusText = result.success ? "บันทึกเรียบร้อย" : "เกิดข้อผิดพลาด";
  
  // Create VAT details if applicable
  const vatDetails = [];
  if (result.success && result.vatCalculation) {
    vatDetails.push(
      {
        type: "box",
        layout: "horizontal",
        contents: [
          {
            type: "text",
            text: "จำนวน (ก่อน VAT):",
            size: "sm",
            color: "#666666",
            flex: 3
          },
          {
            type: "text",
            text: `${formatNumberWithCommas(result.vatCalculation.preVat)} บาท`,
            size: "sm",
            color: "#333333",
            flex: 2,
            align: "end"
          }
        ]
      },
      {
        type: "box",
        layout: "horizontal",
        contents: [
          {
            type: "text",
            text: "VAT (7%):",
            size: "sm",
            color: "#666666",
            flex: 3
          },
          {
            type: "text",
            text: `${formatNumberWithCommas(result.vatCalculation.vatAmount)} บาท`,
            size: "sm",
            color: "#333333",
            flex: 2,
            align: "end"
          }
        ]
      },
      {
        type: "box",
        layout: "horizontal",
        contents: [
          {
            type: "text",
            text: "รวม (หลัง VAT):",
            size: "sm",
            color: "#333333",
            weight: "bold",
            flex: 3
          },
          {
            type: "text",
            text: `${formatNumberWithCommas(result.vatCalculation.postVat)} บาท`,
            size: "sm",
            color: "#333333",
            weight: "bold",
            flex: 2,
            align: "end"
          }
        ]
      },
      {
        type: "separator",
        margin: "md"
      }
    );
  }

  // Format date for display
  let displayDate = '';
  if (result.success && expenseData && expenseData.date) {
    const dateParts = expenseData.date.split('/');
    if (dateParts.length === 3) {
      displayDate = `${dateParts[0]}/${dateParts[1]}/${dateParts[2]}`;
    }
  }

  const flexMessage = {
    type: "bubble",
    header: {
      type: "box",
      layout: "vertical",
      contents: [
        {
          type: "box",
          layout: "horizontal",
          contents: [
            {
              type: "text",
              text: statusIcon,
              size: "lg",
              flex: 0,
              margin: "none"
            },
            {
              type: "text",
              text: statusText,
              size: "lg",
              weight: "bold",
              color: statusColor,
              flex: 1,
              margin: "sm"
            }
          ]
        },
        {
          type: "text",
          text: result.success ? `ค่าใช้จ่ายของ ${userDisplayName}` : "ไม่สามารถบันทึกได้",
          size: "sm",
          color: "#666666",
          margin: "sm"
        }
      ],
      backgroundColor: result.success ? "#f0fdf4" : "#fef2f2",
      paddingAll: "20px"
    },
    body: {
      type: "box",
      layout: "vertical",
      contents: result.success ? [
        // Success content
        {
          type: "box",
          layout: "horizontal",
          contents: [
            {
              type: "text",
              text: "รายการ:",
              size: "sm",
              color: "#666666",
              flex: 2
            },
            {
              type: "text",
              text: expenseData.title || "N/A",
              size: "sm",
              color: "#333333",
              flex: 3,
              wrap: true
            }
          ],
          margin: "md"
        },
        {
          type: "box",
          layout: "horizontal",
          contents: [
            {
              type: "text",
              text: "รายละเอียด:",
              size: "sm",
              color: "#666666",
              flex: 2
            },
            {
              type: "text",
              text: `- ค่า${expenseData.description || "N/A"}`,
              size: "sm",
              color: "#333333",
              flex: 3,
              wrap: true
            }
          ],
          margin: "sm"
        },
        {
          type: "box",
          layout: "horizontal",
          contents: [
            {
              type: "text",
              text: "โครงการ:",
              size: "sm",
              color: "#666666",
              flex: 2
            },
            {
              type: "text",
              text: expenseData.projectName || "N/A",
              size: "sm",
              color: "#333333",
              flex: 3,
              wrap: true
            }
          ],
          margin: "sm"
        },
        {
          type: "box",
          layout: "horizontal",
          contents: [
            {
              type: "text",
              text: "วันที่:",
              size: "sm",
              color: "#666666",
              flex: 2
            },
            {
              type: "text",
              text: displayDate || "N/A",
              size: "sm",
              color: "#333333",
              flex: 3
            }
          ],
          margin: "sm"
        },
        {
          type: "box",
          layout: "horizontal",
          contents: [
            {
              type: "text",
              text: "จำนวนเงิน:",
              size: "sm",
              color: "#666666",
              flex: 2
            },
            {
              type: "text",
              text: `${formatNumberWithCommas(expenseData.amount)} บาท`,
              size: "sm",
              color: "#333333",
              weight: "bold",
              flex: 3
            }
          ],
          margin: "sm"
        },
        {
          type: "box",
          layout: "horizontal",
          contents: [
            {
              type: "text",
              text: "VAT:",
              size: "sm",
              color: "#666666",
              flex: 2
            },
            {
              type: "text",
              text: expenseData.hasVat ? "รวม VAT 7%" : "ไม่รวม VAT",
              size: "sm",
              color: expenseData.hasVat ? "#22c55e" : "#666666",
              flex: 3
            }
          ],
          margin: "sm"
        },
        ...vatDetails,
        {
          type: "separator",
          margin: "lg"
        },
        {
          type: "box",
          layout: "horizontal",
          contents: [
            {
              type: "text",
              text: "Expense ID:",
              size: "xs",
              color: "#aaaaaa",
              flex: 1
            },
            {
              type: "text",
              text: `#${result.expenseId}`,
              size: "xs",
              color: "#aaaaaa",
              flex: 1,
              align: "end"
            }
          ],
          margin: "lg"
        }
      ] : [
        // Error content
        {
          type: "text",
          text: result.message || "เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ",
          size: "sm",
          color: "#ef4444",
          wrap: true,
          margin: "md"
        }
      ],
      paddingAll: "20px"
    }
  };

  return flexMessage;
}

// Send LINE reply with flex message
async function sendLineFlexReply(replyToken, flexMessage) {
  try {
    await axios.post('https://api.line.me/v2/bot/message/reply', {
      replyToken: replyToken,
      messages: [{
        type: 'flex',
        altText: 'Expense Confirmation',
        contents: flexMessage
      }]
    }, {
      headers: {
        'Authorization': `Bearer ${LINE_ACCESS_TOKEN}`,
        'Content-Type': 'application/json'
      }
    });
  } catch (error) {
    console.error('Error sending LINE flex reply:', error);
  }
}

// Send LINE reply
async function sendLineReply(replyToken, message) {
  try {
    await axios.post('https://api.line.me/v2/bot/message/reply', {
      replyToken: replyToken,
      messages: [{
        type: 'text',
        text: message
      }]
    }, {
      headers: {
        'Authorization': `Bearer ${LINE_ACCESS_TOKEN}`,
        'Content-Type': 'application/json'
      }
    });
  } catch (error) {
    console.error('Error sending LINE reply:', error);
  }
}

// Download LINE image  expense_file{timestamp}-{randomNumber}_{ISO_timestamp}.{extension}
async function downloadLineImage(messageId, userId) {
  try {
    const response = await axios.get(`https://api-data.line.me/v2/bot/message/${messageId}/content`, {
      headers: {
        'Authorization': `Bearer ${LINE_ACCESS_TOKEN}`
      },
      responseType: 'stream'
    });

    const fileName = `expense_file${Date.now()}-${Math.round(Math.random() * 1E9)}_${new Date().toISOString()}.jpg`;
    const filePath = path.join(uploadsDir, fileName);

    return new Promise((resolve, reject) => {
      const writer = fs.createWriteStream(filePath);
      response.data.pipe(writer);

      writer.on('finish', () => {
        const stats = fs.statSync(filePath);
        resolve({
          file_name: fileName,
          file_size: stats.size,
          file_path: filePath
        });
      });
      writer.on('error', reject);
    });
  } catch (error) {
    console.error('Error downloading LINE image:', error);
    return null;
  }
}

// Enhanced parse expense input with better handling
function parseExpenseInput(text) {
  if (!text || typeof text !== 'string') {
    throw new Error('Invalid input: text is required');
  }

  const parts = text.split('-');
  console.log('Input parts:', parts);
  
  if (parts.length < 5) {
    throw new Error('Invalid input format. Expected at least: date-title-category-description-amount-project (optional: -vat)');
  }

  try {
    // Parse date (first part) - if empty or just "-", use current date
    let dateInput = cleanTextInput(parts[0]);
    if (!dateInput || dateInput === '' || dateInput === '-') {
      dateInput = getCurrentThaiDate();
      console.log('Using current date:', dateInput);
    }

    // Parse title keyword (second part) - can contain special characters
    const titleKeyword = cleanTextInput(parts[1]);
    if (!titleKeyword) {
      throw new Error('Title keyword is required');
    }

    // Parse category (third part) - should be number or keyword
    const categoryInput = parseCategory(parts[2]);

    // Check if last part contains VAT
    const lastPart = parts[parts.length - 1];
    const hasVat = lastPart.toLowerCase().includes('vat');
    
    // Determine where amount and project are based on VAT presence
    let amountIndex, projectIndex, descriptionEndIndex;
    
    if (hasVat) {
      // Format: date-title-category-description-amount-project-vat
      if (parts.length < 6) {
        throw new Error('Invalid input format with VAT. Expected: date-title-category-description-amount-project-vat');
      }
      projectIndex = parts.length - 2;
      amountIndex = parts.length - 3;
      descriptionEndIndex = parts.length - 3;
    } else {
      // Format: date-title-category-description-amount-project
      projectIndex = parts.length - 1;
      amountIndex = parts.length - 2;
      descriptionEndIndex = parts.length - 2;
    }

    // Parse amount with enhanced handling
    const amountStr = cleanTextInput(parts[amountIndex]);
    const amount = parseAmount(amountStr);
    console.log('Parsed amount:', amount, 'from:', amountStr);

    // Parse project keyword
    const projectKeyword = cleanTextInput(parts[projectIndex]);
    if (!projectKeyword) {
      throw new Error('Project keyword is required');
    }

    // Parse description (everything between category and amount)
    const descriptionParts = parts.slice(3, descriptionEndIndex);
    const description = descriptionParts.map(part => cleanTextInput(part)).join('-');
    if (!description) {
      throw new Error('Description is required');
    }

    const result = {
      date: dateInput,
      titleKeyword: titleKeyword,
      categoryKeyword: categoryInput,
      description: description,
      amount: amount,
      projectKeyword: projectKeyword,
      hasVat: hasVat
    };

    console.log('Parsed expense input:', result);
    return result;

  } catch (error) {
    console.error('Error parsing expense input:', error);
    throw new Error(`Parsing error: ${error.message}`);
  }
}

// Process expense data
async function processExpenseData(userId, expenseData, files = []) {
  try {
    const riseUserId = await getRiseUserIdFromLineId(userId);
    const userProfile = await getLineUserProfile(userId);
    
    // Parse date
    const expenseDate = parseBuddhistDate(expenseData.date);
    
    // Find category
    const categoryId = findCategoryByKeyword(expenseData.categoryKeyword);
    
    // Find title
    const title = findTitleByKeyword(expenseData.titleKeyword);
    
    // Find client and project - pass the original date for proper month/year calculation
    const clientProject = await findClientAndProject(expenseData.projectKeyword, expenseData.date);
    
    // Prepare files data
    const filesData = createPHPSerializedFilesArray(files);
    
    // Get VAT rate from database (ensure it's 7%)
    const vatRate = await getVATRate();
    
    // Calculate VAT - amount is pre-VAT
    const vatCalculation = calculateVAT(expenseData.amount, vatRate);
    const taxId = expenseData.hasVat ? 2 : 0;
    
    // The amount to store in DB should be the original pre-VAT amount
    const amountToStore = expenseData.amount;
    
    // Insert expense
    const [result] = await dbPool.query(`
      INSERT INTO rise_expenses (
        expense_date, category_id, description, amount, files, title,
        project_id, user_id, tax_id, tax_id2, client_id, recurring,
        recurring_expense_id, repeat_every, repeat_type, no_of_cycles,
        next_recurring_date, no_of_cycles_completed, deleted
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0, 0, 0, NULL, 0, NULL, 0, 0)
    `, [
      expenseDate,
      categoryId,
      `- ค่า${expenseData.description}`,
      amountToStore,
      filesData,
      title,
      clientProject.projectId,
      riseUserId,
      taxId,
      clientProject.clientId
    ]);

    const expenseId = result.insertId;

    // Log activity
    await logActivity(
      riseUserId,
      'created',
      'expense',
      title,
      expenseId,
      null,
      'project',
      clientProject.projectId
    );

    // Get category name for response
    const categoryName = catagory_expenses.find(cat => cat.id === categoryId)?.title || 'Unknown Category';
    console.log('expenseDate',expenseDate)

    let newexpenseDate = expenseDate.split('-').reverse().join('/')
    const ex = '- ค่า'
    // Prepare VAT validation message with formatted numbers
    let vatValidationText = '';
    if (expenseData.hasVat) {
      vatValidationText = `\n💰 VAT Calculation (7%):
- จำนวนเงิน (ก่อน VAT): ${formatNumberWithCommas(vatCalculation.preVat)} บาท
- VAT (7%): ${formatNumberWithCommas(vatCalculation.vatAmount)} บาท
- รวม (หลัง VAT): ${formatNumberWithCommas(vatCalculation.postVat)} บาท`;
    }

    const responseMessage = ` บันทึกค่าใช้จ่ายของ ${userProfile.displayName || 'คุณ'} เรียบร้อยแล้ว

📋 รายละเอียด:
- ชื่อรายการ: ${title}
- หมวดหมู่: ${categoryName}
- รายละเอียด: ${ex}${expenseData.description}
- ลูกค้า: ${clientProject.clientName}
- โครงการ: ${clientProject.projectName}
- วันที่: ${newexpenseDate}
- จำนวนเงิน: ${formatNumberWithCommas(expenseData.amount)} บาท
- VAT: ${expenseData.hasVat ? 'รวม VAT 7%' : 'ไม่รวม VAT'}${vatValidationText}


🆔 Expense ID: ${expenseId}`;

    return {
      success: true,
      message: responseMessage,
      expenseId: expenseId,
      vatCalculation: expenseData.hasVat ? vatCalculation : null,
      // Add data for flex message
      flexData: {
        ...expenseData,
        title: title,
        categoryName: categoryName,
        clientName: clientProject.clientName,
        projectName: clientProject.projectName,
        displayDate: newexpenseDate
      },
      userDisplayName: userProfile.displayName || 'คุณ'
    };

  } catch (error) {
    console.error('Error processing expense data:', error);
    return {
      success: false,
      message: `❌ เกิดข้อผิดพลาด: ${error.message}`,
      flexData: null,
      userDisplayName: 'คุณ'
    };
  }
}

// === API Routes ===

// Bearer token middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN
  
  const validToken = process.env.API_BEARER_TOKEN 

  
  if (!token) {
    return res.status(401).json({ error: 'Access token required' });
  }
  
  if (token !== validToken) {
    return res.status(403).json({ error: 'Invalid token' });
  }
  
  next();
};

// Test route for number formatting
app.get('/api/test-number-formatting/:number', (req, res) => {
  try {
    const number = parseFloat(req.params.number);
    const formatted = formatNumberWithCommas(number);
    res.json({
      success: true,
      input: req.params.number,
      parsed: number,
      formatted: formatted
    });
  } catch (error) {
    res.json({
      success: false,
      input: req.params.number,
      error: error.message
    });
  }
});

// Test route for amount parsing
app.get('/api/test-amount-parsing/:amount', (req, res) => {
  try {
    const amount = req.params.amount;
    const parsed = parseAmount(amount);
    const formatted = formatNumberWithCommas(parsed);
    res.json({
      success: true,
      input: amount,
      parsed: parsed,
      formatted: formatted
    });
  } catch (error) {
    res.json({
      success: false,
      input: req.params.amount,
      error: error.message
    });
  }
});

// Test route for expense parsing
app.get('/api/test-expense-parsing', (req, res) => {
  const testCases = [
    '-ค่าน้ำมัน-45-ซื้อน้ำมัน-1,500.50-ruby-vat',
    '15/12/68-ค่าอาหาร-food-ซื้ออาหารกลางวัน-250-ruby',
    '-office supplies-office-ซื้อกระดาษ A4-1,200.75-project1-vat',
    '20/12/68-เครื่องเขียน-45-ปากกา มาร์กเกอร์ และดินสอ-500.25-ruby',
    '-fuel-transport-ค่าน้ำมันรถ-2,500.00-client2-vat',
    '-ค่าอาหาร-food-ซื้ออาหาร-10000-ruby',
    '-office-45-เครื่องเขียน-1022.30-ruby-vat'
  ];

  const results = testCases.map(testCase => {
    try {
      const parsed = parseExpenseInput(testCase);
      return {
        input: testCase,
        success: true,
        parsed: parsed,
        formattedAmount: formatNumberWithCommas(parsed.amount)
      };
    } catch (error) {
      return {
        input: testCase,
        success: false,
        error: error.message
      };
    }
  });

  res.json({
    testCases: results,
    currentDate: getCurrentThaiDate()
  });
});

// LINE Webhook
app.post('/webhook/line', async (req, res) => {
  try {
    const events = req.body.events;
    
    for (const event of events) {
      const userId = event.source.userId;
      const replyToken = event.replyToken;
      console.log('event')
    console.log('event',events )
       const roomId = event.source.roomId;
    console.log(`Bot joined room: ${roomId}`);
      if (event.type === 'message') {
        if (event.message.type === 'image') {
          // Handle image upload
          const imageData = await downloadLineImage(event.message.id, userId);
          
          if (imageData) {
            // Store image in user session
            if (!userSessions.has(userId)) {
              userSessions.set(userId, { files: [], timestamp: Date.now() });
            }
            
            const session = userSessions.get(userId);
            session.files.push(imageData);
            session.timestamp = Date.now();
            
            await sendLineReply(replyToken, `📷 รับรูปภาพแล้ว (${session.files.length}/5)\nกรุณาส่งข้อมูลค่าใช้จ่าย`);
          } else {
            await sendLineReply(replyToken, '❌ เกิดข้อผิดพลาดในการบันทึกรูปภาพ');
          }
          
        } else if (event.message.type === 'text') {
          // Handle text message (expense data)
          const text = event.message.text;
          
          try {
            // Parse expense input
            const expenseData = parseExpenseInput(text);
            
            // Get user files from session
            const session = userSessions.get(userId) || { files: [] };
            const files = session.files || [];
            
            // Process expense
            const result = await processExpenseData(userId, expenseData, files);
            
            // Create and send flex message response
            if (result.success && result.flexData) {
              const flexMessage = createExpenseConfirmationFlexMessage(result.flexData, result, result.userDisplayName);
              await sendLineFlexReply(replyToken, flexMessage);
            } else {
              // For errors, still use flex message but with error content
              const flexMessage = createExpenseConfirmationFlexMessage(null, result, result.userDisplayName);
              await sendLineFlexReply(replyToken, flexMessage);
            }
            
            // Clear user session
            if (userSessions.has(userId)) {
              userSessions.delete(userId);
            }
            
          } catch (error) {
            console.error('Error processing text message:', error);
            await sendLineReply(replyToken, `❌ รูปแบบข้อมูลไม่ถูกต้อง: ${error.message}

`);
          }
        }
      }
    }
    
    res.status(200).send('OK');
  } catch (error) {
    console.error('Webhook error:', error);
    res.status(500).send('Error');
  }
});

// API route to test expense creation
app.post('/api/test-expense', authenticateToken, async (req, res) => {
  try {
    const { userId, expenseText } = req.body;
    
    if (!userId || !expenseText) {
      return res.status(400).json({
        success: false,
        error: 'userId and expenseText are required'
      });
    }

    // Parse expense input
    const expenseData = parseExpenseInput(expenseText);
    
    // Process expense
    const result = await processExpenseData(userId, expenseData, []);
    
    res.json(result);
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
});

// API route to get recent expenses with formatted amounts
app.get('/api/expenses', authenticateToken, async (req, res) => {
  try {
    const limit = parseInt(req.query.limit) || 10;
    
    const [expenses] = await dbPool.query(`
      SELECT 
        e.id,
        e.expense_date,
        e.title,
        e.description,
        e.amount,
        c.title as category_name,
        p.title as project_name,
        cl.company_name as client_name,
        u.first_name,
        CASE WHEN e.tax_id = 2 THEN 'Yes' ELSE 'No' END as has_vat
      FROM rise_expenses e
      LEFT JOIN rise_expense_categories c ON e.category_id = c.id
      LEFT JOIN rise_projects p ON e.project_id = p.id
      LEFT JOIN rise_clients cl ON e.client_id = cl.id
      LEFT JOIN rise_users u ON e.user_id = u.id
      WHERE e.deleted = 0
      ORDER BY e.id DESC
      LIMIT ?
    `, [limit]);

    // Format amounts with commas
    const formattedExpenses = expenses.map(expense => ({
      ...expense,
      formatted_amount: formatNumberWithCommas(expense.amount)
    }));

    res.json({
      success: true,
      expenses: formattedExpenses
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Test route for date parsing
app.get('/api/test-date-parsing/:day/:month/:year', (req, res) => {
  try {
    const { day, month, year } = req.params;
    const dateStr = `${day}/${month}/${year}`;
    const parsed = parseBuddhistDate(dateStr);
    const monthYear = getBuddhistMonthYear(dateStr);
    
    res.json({
      success: true,
      input: dateStr,
      parsed: parsed,
      monthYear: monthYear,
      currentThaiDate: getCurrentThaiDate()
    });
  } catch (error) {
    res.json({
      success: false,
      input: `${req.params.day}/${req.params.month}/${req.params.year}`,
      error: error.message
    });
  }
});

// Test route for project lookup
app.get('/api/test-project-lookup/:keyword', async (req, res) => {
  try {
    const keyword = req.params.keyword;
    const date = req.query.date;
    const useExisting = req.query.useExisting === 'true';
    
    const result = await findClientAndProject(keyword, date, useExisting);
    
    // Also show what the generated project title would be
    let generatedTitle = '';
    if (date && keyword.toLowerCase() === 'ruby') {
      generatedTitle = getBuddhistMonthYear(date);
    }
    
    res.json({
      success: true,
      keyword: keyword,
      date: date,
      useExistingProject: useExisting,
      generatedProjectTitle: generatedTitle,
      result: result
    });
  } catch (error) {
    res.json({
      success: false,
      keyword: req.params.keyword,
      date: req.query.date,
      useExistingProject: req.query.useExisting,
      error: error.message
    });
  }
});

// View existing projects
app.get('/api/existing-projects', authenticateToken, async (req, res) => {
  try {
    const [projects] = await dbPool.query(`
      SELECT 
        p.id,
        p.title,
        c.company_name as client_name,
        p.created_date,
        p.status
      FROM rise_projects p
      LEFT JOIN rise_clients c ON p.client_id = c.id
      WHERE p.deleted = 0
      ORDER BY p.id DESC
      LIMIT 20
    `);

    res.json({
      success: true,
      projects: projects
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Clean up old user sessions (run every hour)
setInterval(() => {
  const now = Date.now();
  const oneHour = 60 * 60 * 1000;
  
  for (const [userId, session] of userSessions.entries()) {
    if (now - session.timestamp > oneHour) {
      // Clean up files
      session.files.forEach(file => {
        try {
          if (fs.existsSync(file.file_path)) {
            fs.unlinkSync(file.file_path);
          }
        } catch (error) {
          console.error('Error deleting file:', error);
        }
      });
      
      userSessions.delete(userId);
    }
  }
}, 60 * 60 * 1000);




// Send summary report to LINE using the daily reports route
async function sendDailySummaryReportScheduled() {
  try {
    // Use the new daily reports route function
    const result = await sendDailySummaryFlexReport(
      dbPool, 
      client_and_project, 
      formatNumberWithCommas, 
      LINE_ACCESS_TOKEN, 
      REPORT_LINE_USER_ID
    );
    
    if (result.success) {
      console.log(' Scheduled daily flex summary sent successfully');
    } else {
      console.error('❌ Error sending scheduled daily summary:', result.error);
    }
    
  } catch (error) {
    console.error('Error sending daily summary report:', error);
  }
}

// Schedule daily report at 21:30 (PM2 compatible)
// Schedule daily reports at 20:00 every day
function scheduleDailyReport() {
  // Cron format: second minute hour dayOfMonth month dayOfWeek
  // 0 0 20 * * * = every day at 20:00 (8:00 PM)
  cron.schedule('0 0 20 * * *', async () => {
    console.log('� Scheduled task triggered: Sending daily flex report at 20:00');
    try {
      await sendDailySummaryReportScheduled();
      console.log(' Scheduled daily flex report sent successfully at 20:00');
    } catch (error) {
      console.error('❌ Error sending scheduled daily report:', error.message);
    }
  }, {
    scheduled: true,
    timezone: "Asia/Bangkok"
  });
  
  console.log('⏰ Daily flex reports scheduled to run every day at 20:00 (Thailand time)');
}

// API endpoint for monthly summary report
app.get('/api/monthly-summary', authenticateToken, async (req, res) => {
  try {
    const targetDate = req.query.date || null; // Optional date parameter (YYYY-MM-DD format)
    const summary = await generateMonthlyExpenseSummary(dbPool, client_and_project, formatNumberWithCommas, targetDate);
    
    res.json({
      success: true,
      summary: summary,
      targetDate: targetDate || 'current month'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// API endpoint to trigger manual monthly report send
app.post('/api/send-monthly-summary', authenticateToken, async (req, res) => {
  try {
    const targetDate = req.body.date || null; // Optional date parameter
    const result = await sendMonthlySummaryReport(dbPool, client_and_project, formatNumberWithCommas, LINE_ACCESS_TOKEN, REPORT_LINE_USER_ID, targetDate);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Monthly summary sent to LINE successfully',
        messageCount: result.messageCount,
        successCount: result.successCount,
        failCount: result.failCount,
        sentTo: result.sentTo,
        results: result.results
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Quick test endpoint for monthly summary (no authentication required for quick testing)
app.get('/api/test-monthly-summary', async (req, res) => {
  try {
    const targetDate = req.query.date || null;
    const summary = await generateMonthlyExpenseSummary(dbPool, client_and_project, formatNumberWithCommas, targetDate);
    
    res.writeHead(200, {'Content-Type': 'text/plain; charset=utf-8'});
    res.end(`🧪 TEST MONTHLY SUMMARY REPORT\n${'='.repeat(50)}\n\n${summary}\n\n${'='.repeat(50)}\nGenerated at: ${new Date().toLocaleString('th-TH')}`);
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Quick test endpoint to send monthly summary to LINE immediately (no authentication)
app.get('/api/test-send-monthly-summary', async (req, res) => {
  try {
    const targetDate = req.query.date || null;
    
    console.log('🧪 TEST: Sending monthly flex messages to LINE...');
    console.log(`📱 Target LINE User ID: ${REPORT_LINE_USER_ID}`);
    
    const result = await sendMonthlySummaryReport(dbPool, client_and_project, formatNumberWithCommas, LINE_ACCESS_TOKEN, REPORT_LINE_USER_ID, targetDate);
    
    if (result.success) {
      res.json({
        success: true,
        message: ' Monthly flex messages sent to LINE successfully!',
        messageCount: result.messageCount,
        successCount: result.successCount,
        failCount: result.failCount,
        sentTo: result.sentTo,
        timestamp: new Date().toLocaleString('th-TH'),
        monthData: result.monthData
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: new Date().toLocaleString('th-TH')
      });
    }
  } catch (error) {
    console.error('❌ Test send error:', error);
    res.status(500).json({
      success: false,
      error: error.message,
      timestamp: new Date().toLocaleString('th-TH')
    });
  }
});

// Test endpoint to preview flex message structure (no authentication)
app.get('/api/test-flex-preview', async (req, res) => {
  try {
    const targetDate = req.query.date || null;
    
    console.log('🧪 TEST: Generating flex message preview...');
    
    const monthData = await generateMonthlyExpenseData(dbPool, client_and_project, formatNumberWithCommas, targetDate);
    
    const messages = [];
    
    // Add header message
    messages.push(createMonthlyHeaderFlexMessage(monthData));
    
    // Add first 2 project messages for preview
    const projectsToShow = monthData.projects.slice(0, 2);
    projectsToShow.forEach(project => {
      messages.push(createProjectFlexMessage(project, project.index, monthData.projectCount));
    });
    
    // Validate flex messages for empty text fields
    const validationErrors = [];
    messages.forEach((message, index) => {
      const validateFlexContent = (content, path = '') => {
        if (content.type === 'text') {
          if (!content.text || content.text.trim() === '') {
            validationErrors.push(`Message ${index + 1}: Empty text field at ${path}`);
          }
        }
        
        if (content.contents) {
          if (Array.isArray(content.contents)) {
            content.contents.forEach((item, i) => {
              validateFlexContent(item, `${path}/contents/${i}`);
            });
          } else {
            validateFlexContent(content.contents, `${path}/contents`);
          }
        }
        
        if (content.header) {
          validateFlexContent(content.header, `${path}/header`);
        }
        
        if (content.body) {
          validateFlexContent(content.body, `${path}/body`);
        }
      };
      
      if (message.contents) {
        validateFlexContent(message.contents, `message[${index}]`);
      }
    });
    
    res.json({
      success: true,
      message: ' Flex message preview generated successfully!',
      monthData: monthData,
      flexMessages: messages,
      messageCount: messages.length,
      validationErrors: validationErrors,
      isValid: validationErrors.length === 0,
      timestamp: new Date().toLocaleString('th-TH')
    });
    
  } catch (error) {
    console.error('❌ Flex preview error:', error);
    res.status(500).json({
      success: false,
      error: error.message,
      timestamp: new Date().toLocaleString('th-TH')
    });
  }
});

// Schedule monthly summary reports
function scheduleMonthlyReports() {
  // Schedule for every Monday and Saturday at 20:01
  // Cron format: second minute hour dayOfMonth month dayOfWeek
  // 0 1 20 * * 1 = every Monday at 20:01
  // 0 1 20 * * 6 = every Saturday at 20:01
  cron.schedule('0 1 20 * * 1', async () => {
    console.log('� Scheduled task triggered: Sending monthly summary on Monday at 20:01');
    try {
      const response = await axios.get(`http://localhost:${port}/api/test-send-monthly-summary`);
      console.log(' Monday monthly summary sent successfully:', response.data);
    } catch (error) {
      console.error('❌ Error sending Monday monthly summary:', error.message);
    }
  }, {
    scheduled: true,
    timezone: "Asia/Bangkok"
  });

  cron.schedule('0 1 20 * * 6', async () => {
    console.log('📅 Scheduled task triggered: Sending monthly summary on Saturday at 20:01');
    try {
      const response = await axios.get(`http://localhost:${port}/api/test-send-monthly-summary`);
      console.log(' Saturday monthly summary sent successfully:', response.data);
    } catch (error) {
      console.error('❌ Error sending Saturday monthly summary:', error.message);
    }
  }, {
    scheduled: true,
    timezone: "Asia/Bangkok"
  });

  // Schedule for first day of every month at 20:01
  // 0 1 20 1 * * = every 1st day of the month at 20:01
  cron.schedule('0 1 20 1 * *', async () => {
    console.log('📅 Scheduled task triggered: Sending monthly summary on 1st day of month at 20:01');
    try {
      const response = await axios.get(`http://localhost:${port}/api/test-send-monthly-summary`);
      console.log(' First-day-of-month monthly summary sent successfully:', response.data);
    } catch (error) {
      console.error('❌ Error sending first-day monthly summary:', error.message);
    }
  }, {
    scheduled: true,
    timezone: "Asia/Bangkok"
  });
  
  console.log('⏰ Monthly summaries scheduled:');
  console.log('  - Every Monday at 20:01 (Thailand time)');
  console.log('  - Every Saturday at 20:01 (Thailand time)');
  console.log('  - Every 1st day of month at 20:01 (Thailand time)');
}

// Start server
app.listen(port, async () => {
  console.log(`🚀 Expense Bot Server running on port ${port}`);
  console.log(`📱 LINE Webhook URL: http://localhost:${port}/webhook/line`);
  console.log(`🧪 Test API: http://localhost:${port}/api/test-expense`);
  console.log(`📊 View Expenses: http://localhost:${port}/api/expenses`);
  console.log(`🔢 Test Amount Parsing: http://localhost:${port}/api/test-amount-parsing/1,500.50`);
  console.log(`📝 Test Expense Parsing: http://localhost:${port}/api/test-expense-parsing`);
  console.log(`💰 Test Number Formatting: http://localhost:${port}/api/test-number-formatting/10000`);
  console.log(`🗓️ Test Date Parsing: http://localhost:${port}/api/test-date-parsing/2/6/68`);
  console.log(`🔍 Test Project Lookup: http://localhost:${port}/api/test-project-lookup/ruby?date=2/6/68`);
  console.log(`📁 View Existing Projects: http://localhost:${port}/api/existing-projects`);
  console.log(`📊 Daily Summary: http://localhost:${port}/api/daily/summary`);
  console.log(`📱 Daily Flex Summary: http://localhost:${port}/api/daily/flex-summary`);
  console.log(`📤 Send Daily Summary: http://localhost:${port}/api/daily/send-summary`);
  console.log(`📲 Send Daily Flex: http://localhost:${port}/api/daily/send-flex-summary`);
  console.log(`🧪 Test Daily Flex: http://localhost:${port}/api/daily/test-flex-summary`);
  console.log(`🎨 Daily Flex Preview: http://localhost:${port}/api/daily/test-flex-preview`);
  console.log(`📋 Monthly Summary: http://localhost:${port}/api/monthly-summary`);
  console.log(`📤 Send Monthly Summary: http://localhost:${port}/api/send-monthly-summary`);
  console.log(`🧪 Test Monthly Summary: http://localhost:${port}/api/test-monthly-summary`);
  console.log(`📲 Test Send to LINE: http://localhost:${port}/api/test-send-monthly-summary`);
  console.log(`🎨 Test Flex Preview: http://localhost:${port}/api/test-flex-preview`);
  console.log(`🔍 Flex Validation: http://localhost:${port}/api/test-flex-preview`);
  console.log(`🗓️ Current Thai Date: ${getCurrentThaiDate()}`);
  
  // Generate and send startup summary report   if start will run auto
  console.log('\n📊 Generating startup expense summary...');
  // await sendDailySummaryReport();
  
  // Schedule daily reports at 20:00
  scheduleDailyReport();
  
  // Schedule monthly summary reports (Mon/Sat 20:01 + 1st of month 20:01)
  scheduleMonthlyReports();
  
  console.log(`\n🎯 Ready to receive LINE messages!`);
});

module.exports = app;


