const express = require('express');
const router = express.Router();

// Test endpoint to show all projects with expenses except main 3
router.get('/test-other-projects', async (req, res) => {
  try {
    const { dbPool, client_and_project, formatNumberWithCommas } = req.app.locals;
    
    // Call the function to get all projects except main 3
    const result = await getAllProjectsWithExpensesExceptMain(
      dbPool, 
      client_and_project, 
      formatNumberWithCommas
    );
    
    if (result.success) {
      res.json({
        success: true,
        message: `Found ${result.summary.totalProjects} other projects with expenses`,
        data: result
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error
      });
    }
    
  } catch (error) {
    console.error('Error in test-other-projects endpoint:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Send other projects summary to LINE with Flex Messages
router.get('/send-other-projects-to-line', async (req, res) => {
  try {
    const { dbPool, client_and_project, formatNumberWithCommas } = req.app.locals;
    const axios = require('axios');
    
    // Get environment variables
    const LINE_ACCESS_TOKEN = process.env.LINE_ACCESS_TOKEN;
    const REPORT_LINE_USER_ID = process.env.REPORT_LINE_USER_ID;
    
    if (!LINE_ACCESS_TOKEN || !REPORT_LINE_USER_ID) {
      return res.status(500).json({
        success: false,
        error: 'LINE configuration missing'
      });
    }
    
    console.log(' Sending other projects to LINE with Flex Messages...');
    
    // Get other projects data
    const result = await getAllProjectsWithExpensesExceptMain(
      dbPool, 
      client_and_project, 
      formatNumberWithCommas
    );
    
    if (!result.success) {
      return res.status(500).json({
        success: false,
        error: result.error
      });
    }
    
    // Create Flex Messages
    const bubbles = [];
    
    // Create header bubble
    const headerBubble = {
      type: "bubble",
      header: {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "text",
            text: "📊 โครงการอื่นๆ ที่มีค่าใช้จ่าย",
            weight: "bold",
            color: "#ffffff",
            size: "lg",
            align: "center"
          },
          {
            type: "text",
            text: `เดือน${result.period.monthName} ${result.period.buddhistYear}`,
            weight: "bold",
            color: "#ffffff",
            size: "md",
            align: "center"
          }
        ],
        backgroundColor: "#FF9500",
        paddingTop: "19px",
        paddingAll: "12px",
        paddingBottom: "16px"
      },
      body: {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "box",
            layout: "baseline",
            contents: [
              {
                type: "text",
                text: "📅 ช่วงวันที่:",
                color: "#8C8C8C",
                size: "sm",
                flex: 2
              },
              {
                type: "text",
                text: result.period.dateRange,
                weight: "bold",
                color: "#1DB446",
                size: "sm",
                flex: 3,
                wrap: true
              }
            ],
            spacing: "sm"
          },
          {
            type: "box",
            layout: "baseline",
            contents: [
              {
                type: "text",
                text: "🏗️ จำนวนโครงการ:",
                color: "#8C8C8C",
                size: "sm",
                flex: 2
              },
              {
                type: "text",
                text: `${result.summary.totalProjects} โครงการ`,
                weight: "bold",
                color: "#FF9500",
                size: "lg",
                flex: 3
              }
            ],
            spacing: "sm"
          },
          {
            type: "box",
            layout: "baseline",
            contents: [
              {
                type: "text",
                text: "💰 รวมทั้งหมด:",
                color: "#8C8C8C",
                size: "sm",
                flex: 2
              },
              {
                type: "text",
                text: `${result.summary.formattedTotalAmount} บาท`,
                weight: "bold",
                color: "#E60012",
                size: "lg",
                flex: 3
              }
            ],
            spacing: "sm"
          },
          {
            type: "box",
            layout: "baseline",
            contents: [
              {
                type: "text",
                text: "📋 รายการค่าใช้จ่าย:",
                color: "#8C8C8C",
                size: "sm",
                flex: 2
              },
              {
                type: "text",
                text: `${result.summary.totalExpenseCount} รายการ`,
                weight: "bold",
                color: "#666666",
                size: "sm",
                flex: 3
              }
            ],
            spacing: "sm"
          },
          {
            type: "text",
            text: `ยกเว้น ${result.summary.excludedMainProjects} โครงการหลัก`,
            color: "#999999",
            size: "xs",
            align: "center",
            margin: "md"
          }
        ],
        spacing: "md",
        paddingAll: "12px"
      }
    };
    
    bubbles.push(headerBubble);
    
    // Create project bubbles (up to 9 projects per carousel)
    const projectsPerBubble = 8; // 8 projects per bubble for better readability
    const projectChunks = [];
    for (let i = 0; i < result.projects.length; i += projectsPerBubble) {
      projectChunks.push(result.projects.slice(i, i + projectsPerBubble));
    }
    
    projectChunks.forEach((chunk, chunkIndex) => {
      const projectContents = [];
      
      chunk.forEach((project, index) => {
        const globalIndex = (chunkIndex * projectsPerBubble) + index + 1;
        
        projectContents.push({
          type: "box",
          layout: "baseline",
          contents: [
            {
              type: "text",
              text: `${globalIndex}.`,
              color: "#FF9500",
              size: "xs",
              flex: 0,
              weight: "bold",
              margin: "none"
            },
            {
              type: "text",
              text: project.title || "โครงการไม่ระบุ",
              color: "#333333",
              size: "xs",
              flex: 5,
              wrap: true,
              margin: "sm"
            },
            {
              type: "text",
              text: `${project.formattedAmount}`,
              weight: "bold",
              color: "#E60012",
              size: "xs",
              flex: 2,
              align: "end"
            }
          ],
          spacing: "none",
          margin: "xs"
        });
        
        projectContents.push({
          type: "box",
          layout: "baseline",
          contents: [
            {
              type: "text",
              text: " ",
              flex: 0
            },
            {
              type: "text",
              text: `📋 ${project.expenseCount} รายการ`,
              color: "#999999",
              size: "xxs",
              flex: 5,
              margin: "sm"
            }
          ],
          spacing: "none",
          margin: "none"
        });
        
        if (index < chunk.length - 1) {
          projectContents.push({
            type: "separator",
            margin: "sm"
          });
        }
      });
      
      const projectBubble = {
        type: "bubble",
        header: {
          type: "box",
          layout: "vertical",
          contents: [
            {
              type: "text",
              text: `📋 รายการโครงการ ${chunkIndex + 1}`,
              weight: "bold",
              color: "#ffffff",
              size: "sm"
            },
            {
              type: "text",
              text: `${chunk.length} โครงการ`,
              weight: "bold",
              color: "#ffffff",
              size: "md",
              wrap: true
            }
          ],
          backgroundColor: "#34C759",
          paddingTop: "19px",
          paddingAll: "12px",
          paddingBottom: "16px"
        },
        body: {
          type: "box",
          layout: "vertical",
          contents: projectContents,
          spacing: "sm",
          paddingAll: "12px"
        }
      };
      
      bubbles.push(projectBubble);
    });
    
    // Create carousel message (limit to 10 bubbles)
    const bubblesChunk = bubbles.slice(0, 10);
    const carouselMessage = {
      type: "flex",
      altText: `รายงานโครงการอื่นๆ เดือน${result.period.monthName} ${result.period.buddhistYear}`,
      contents: {
        type: "carousel",
        contents: bubblesChunk
      }
    };
    
    // Send to LINE
    const response = await axios.post('https://api.line.me/v2/bot/message/push', {
      to: REPORT_LINE_USER_ID,
      messages: [carouselMessage]
    }, {
      headers: {
        'Authorization': `Bearer ${LINE_ACCESS_TOKEN}`,
        'Content-Type': 'application/json'
      }
    });
    
    console.log(' Other projects flex message sent to LINE successfully');
    
    res.json({
      success: true,
      message: ' Other projects sent to LINE successfully!',
      sentTo: REPORT_LINE_USER_ID,
      projectsCount: result.summary.totalProjects,
      totalAmount: result.summary.formattedTotalAmount,
      bubblesCount: bubblesChunk.length,
      timestamp: new Date().toLocaleString('th-TH'),
      data: result.summary
    });
    
  } catch (error) {
    console.error('❌ Error sending other projects to LINE:', error);
    res.status(500).json({
      success: false,
      error: error.message,
      timestamp: new Date().toLocaleString('th-TH')
    });
  }
});

// Generate monthly expense summary for all projects
async function generateMonthlyExpenseSummary(dbPool, client_and_project, formatNumberWithCommas, targetDate = null) {
  try {
    // Use current date if no target date provided
    const today = targetDate ? new Date(targetDate) : new Date();
    const currentYear = today.getFullYear();
    const currentMonth = today.getMonth() + 1; // JavaScript months are 0-indexed
    
    // Calculate first day of current month and current day
    const firstDayOfMonth = new Date(currentYear, currentMonth - 1, 1).toISOString().split('T')[0]; // YYYY-MM-DD format
    const currentDay = today.toISOString().split('T')[0]; // YYYY-MM-DD format
    
    console.log('Generating monthly expense summary from:', firstDayOfMonth, 'to:', currentDay);
    
    const monthNames = [
      'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
      'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    const buddhistYear = currentYear + 543;
    const monthName = monthNames[currentMonth - 1];
    
    let summaryReport = `📊 สรุปค่าใช้จ่ายประจำเดือน\n`;
    summaryReport += `🗓️ เดือน${monthName} ${buddhistYear}\n`;
    summaryReport += `📅 ช่วงวันที่: ${firstDayOfMonth.split('-').reverse().join('/')} - ${currentDay.split('-').reverse().join('/')}\n\n`;
    
    let totalMonthExpense = 0;
    let hasExpenses = false;
    
    // Loop through all projects in client_and_project
    for (let projectIndex = 0; projectIndex < client_and_project.length; projectIndex++) {
      const projectConfig = client_and_project[projectIndex];
      
      try {
        let projects = [];
        
        console.log('Project:', projectConfig.project);
        
        // If project title is specified, search by exact project title
        if (projectConfig.project && projectConfig.project.trim() !== '') {
          console.log(`Searching for exact project: "${projectConfig.project}"`);
          const [projectResults] = await dbPool.query(
            'SELECT id, title FROM rise_projects WHERE title = ? AND deleted = 0',
            [projectConfig.project]
          );
          projects = projectResults;
          console.log(`Found ${projects.length} projects for "${projectConfig.project}"`);
        } else {
          // For ruby projects, use current month title
          const currentMonthProject = `ค่าใช้จ่าย เดือน${monthName} ${buddhistYear}`;
          console.log(`Searching for monthly project: "${currentMonthProject}"`);
          const [projectResults] = await dbPool.query(
            'SELECT id, title FROM rise_projects WHERE title = ? AND deleted = 0',
            [currentMonthProject]
          );
          projects = projectResults;
          console.log(`Found ${projects.length} monthly projects for "${currentMonthProject}"`);
        }
        
        // Determine project title for display
        let projectTitle = '';
        if (projectConfig.project && projectConfig.project.trim() !== '') {
          projectTitle = projectConfig.project;
        } else {
          projectTitle = `ค่าใช้จ่าย เดือน${monthName} ${buddhistYear}`;
        }
        
        summaryReport += `🏗️ Project ${projectIndex + 1}: ${projectTitle}\n`;
        
        let projectTotal = 0;
        let projectExpenseDetails = [];
        let projectHasExpenses = false;
        let expenseCount = 0;
        
        if (projects.length > 0) {
          for (const project of projects) {
            console.log(`🔍 Checking expenses for project ID: ${project.id}, Title: "${project.title}"`);
            
            // Get individual expenses for this project for the current month
            const [expenseDetails] = await dbPool.query(`
              SELECT 
                id,
                expense_date,
                description,
                amount,
                tax_id,
                title,
                CASE WHEN tax_id = 2 THEN amount * 0.07 ELSE 0 END as vat_amount
              FROM rise_expenses 
              WHERE project_id = ? 
                AND DATE(expense_date) >= ? 
                AND DATE(expense_date) <= ?
                AND deleted = 0
              ORDER BY expense_date DESC, id DESC
            `, [project.id, firstDayOfMonth, currentDay]);
            
            console.log(`📊 Found ${expenseDetails.length} expenses for project "${project.title}"`);
            
            if (expenseDetails.length > 0) {
              // Process each expense
              for (const expense of expenseDetails) {
                const amount = parseFloat(expense.amount) || 0;
                const vatAmount = parseFloat(expense.vat_amount) || 0;
                const totalWithVat = amount + vatAmount;
                
                projectTotal += totalWithVat;
                expenseCount++;
                
                // Format expense date
                const expenseDate = new Date(expense.expense_date).toISOString().split('T')[0];
                const formattedDate = expenseDate.split('-').reverse().join('/');
                
                // Format expense description and amount
                const formattedAmount = formatNumberWithCommas(totalWithVat);
                const vatText = expense.tax_id === 2 ? ' (รวม VAT 7%)' : '';
                
                projectExpenseDetails.push(
                  `    ${expenseCount}. ${expense.description} - ${formattedAmount} บาท${vatText}\n` +
                  `       📅 ${formattedDate} | 🆔 ${expense.id}`
                );
              }
              
              console.log(`💰 Project "${project.title}" total: ${projectTotal} baht`);
              projectHasExpenses = true;
            }
          }
        }
        
        // Add expense details or no expense message
        if (projectHasExpenses && projectExpenseDetails.length > 0) {
          summaryReport += `💰 ยอดรวม: ${formatNumberWithCommas(projectTotal)} บาท\n`;
          summaryReport += `📋 รายละเอียดค่าใช้จ่าย (${expenseCount} รายการ):\n`;
          summaryReport += `${projectExpenseDetails.join('\n\n')}\n\n`;
          totalMonthExpense += projectTotal;
          hasExpenses = true;
        } else {
          summaryReport += `💰 ยอดรวม: 0 บาท\n`;
          summaryReport += `📋 รายละเอียด: ไม่มีรายการค่าใช้จ่ายในเดือนนี้\n\n`;
        }
        
        summaryReport += `${'━'.repeat(40)}\n\n`;
        
      } catch (error) {
        console.error(`Error processing project ${projectConfig.keyword}:`, error);
        summaryReport += `❌ เกิดข้อผิดพลาดในการประมวลผลโครงการ ${projectConfig.keyword}\n\n`;
      }
    }
    
    // Add total summary
    summaryReport += `📊 สรุปรวมทั้งเดือน\n`;
    if (hasExpenses) {
      summaryReport += `💰 รวมค่าใช้จ่ายทั้งหมด: ${formatNumberWithCommas(totalMonthExpense)} บาท\n`;
      summaryReport += `📈 เฉลี่ยต่อวัน: ${formatNumberWithCommas(totalMonthExpense / today.getDate())} บาท`;
    } else {
      summaryReport += `ไม่มีค่าใช้จ่ายในเดือนนี้`;
    }
    
    console.log('Monthly expense summary generated:', summaryReport.length, 'characters');
    return summaryReport;
    
  } catch (error) {
    console.error('Error generating monthly expense summary:', error);
    return `❌ เกิดข้อผิดพลาดในการสร้างรายงานรายเดือน: ${error.message}`;
  }
}

// Generate monthly expense data for flex messages
async function generateMonthlyExpenseData(dbPool, client_and_project, formatNumberWithCommas, targetDate = null) {
  try {
    // Use current date if no target date provided
    const today = targetDate ? new Date(targetDate) : new Date();
    const currentYear = today.getFullYear();
    const currentMonth = today.getMonth() + 1; // JavaScript months are 0-indexed
    // if logic how to calculate is wrong just edit firstDayofmonth to  currentYear, currentMonth - 1, 1 (logic)
    // Calculate first day of current month and current day
    const firstDayOfMonth = new Date(currentYear, currentMonth - 1, 2).toISOString().split('T')[0];
    const currentDay = today.toISOString().split('T')[0];
    
    const monthNames = [
      'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
      'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    const buddhistYear = currentYear + 543;
    const monthName = monthNames[currentMonth - 1];
    
    let totalMonthExpense = 0;
    let projectsData = [];
    
    // Loop through all projects in client_and_project
    for (let projectIndex = 0; projectIndex < client_and_project.length; projectIndex++) {
      const projectConfig = client_and_project[projectIndex];
      
      try {
        let projects = [];
        
        // If project title is specified, search by exact project title
        if (projectConfig.project && projectConfig.project.trim() !== '') {
          const [projectResults] = await dbPool.query(
            'SELECT id, title FROM rise_projects WHERE title = ? AND deleted = 0',
            [projectConfig.project]
          );
          projects = projectResults;
        } else {
          // For ruby projects, use current month title
          const currentMonthProject = `ค่าใช้จ่าย เดือน${monthName} ${buddhistYear}`;
          const [projectResults] = await dbPool.query(
            'SELECT id, title FROM rise_projects WHERE title = ? AND deleted = 0',
            [currentMonthProject]
          );
          projects = projectResults;
        }
        
        // Determine project title for display
        let projectTitle = '';
        if (projectConfig.project && projectConfig.project.trim() !== '') {
          projectTitle = projectConfig.project;
        } else {
          projectTitle = `ค่าใช้จ่าย เดือน${monthName} ${buddhistYear}`;
        }
        
        let projectTotal = 0;
        let expenses = [];
        
        if (projects.length > 0) {
          for (const project of projects) {
            // Get individual expenses for this project for the current month
            const [expenseDetails] = await dbPool.query(`
              SELECT 
                id,
                expense_date,
                description,
                amount,
                tax_id,
                title,
                CASE WHEN tax_id = 2 THEN amount * 0.07 ELSE 0 END as vat_amount
              FROM rise_expenses 
              WHERE project_id = ? 
                AND DATE(expense_date) >= ? 
                AND DATE(expense_date) <= ?
                AND deleted = 0
              ORDER BY expense_date DESC, id DESC
              LIMIT 10
            `, [project.id, firstDayOfMonth, currentDay]);
            
            if (expenseDetails.length > 0) {
              // Process each expense
              for (const expense of expenseDetails) {
                const amount = parseFloat(expense.amount) || 0;
                const vatAmount = parseFloat(expense.vat_amount) || 0;
                const totalWithVat = amount + vatAmount;
                
                projectTotal += totalWithVat;
                
                // Format expense date
                const expenseDate = new Date(expense.expense_date).toISOString().split('T')[0];
                const formattedDate = expenseDate.split('-').reverse().join('/');
                
                expenses.push({
                  id: expense.id,
                  description: expense.description && expense.description.trim() !== '' 
                    ? (expense.description.length > 30 ? 
                        expense.description.substring(0, 30) + '...' : 
                        expense.description)
                    : 'รายการไม่ระบุ',
                  amount: totalWithVat,
                  formattedAmount: formatNumberWithCommas(totalWithVat),
                  date: formattedDate,
                  hasVat: expense.tax_id === 2
                });
              }
              
              // Sort expenses by amount (highest first) and keep only top 3
              expenses.sort((a, b) => b.amount - a.amount);
              expenses = expenses.slice(0, 3);
            }
          }
        }
        
        totalMonthExpense += projectTotal;
        
        projectsData.push({
          index: projectIndex + 1,
          title: projectTitle && projectTitle.trim() !== '' 
            ? (projectTitle.length > 25 ? projectTitle.substring(0, 25) + '...' : projectTitle)
            : `โครงการ ${projectIndex + 1}`,
          fullTitle: projectTitle || `โครงการ ${projectIndex + 1}`,
          total: projectTotal,
          formattedTotal: formatNumberWithCommas(projectTotal),
          expenses: expenses,
          expenseCount: expenses.length
        });
        
      } catch (error) {
        console.error(`Error processing project ${projectConfig.keyword}:`, error);
      }
    }
    
    return {
      monthName: monthName,
      buddhistYear: buddhistYear,
      startDate: firstDayOfMonth.split('-').reverse().join('/'),
      endDate: currentDay.split('-').reverse().join('/'),
      totalExpense: totalMonthExpense,
      formattedTotal: formatNumberWithCommas(totalMonthExpense),
      averagePerDay: formatNumberWithCommas(totalMonthExpense / today.getDate()),
      projects: projectsData,
      projectCount: projectsData.length
    };
    
  } catch (error) {
    console.error('Error generating monthly expense data:', error);
    throw error;
  }
}

// Create flex message for monthly summary header
async function createMonthlyHeaderFlexMessage(monthData, dbPool, client_and_project, formatNumberWithCommas) {
  const headerContents = [
    {
      type: "box",
      layout: "baseline",
      contents: [
        {
          type: "text",
          text: `ช่วงวันที่:`,
          color: "#8C8C8C",
          size: "xs",
          flex: 2
        },
        {
          type: "text",
          text: `${monthData.startDate} - ${monthData.endDate}`,
          weight: "bold",
          color: "#1DB446",
          size: "xs",
          flex: 4,
          wrap: true
        }
      ],
      spacing: "xs"
    }
  ];

  // Add first 3 projects with their amounts
  const projectsToShow = monthData.projects.slice(0, 3);
  projectsToShow.forEach((project, index) => {
    headerContents.push({
      type: "box",
      layout: "baseline",
      contents: [
        {
          type: "text",
          text: `${index + 1}.`,
          color: "#8C8C8C",
          size: "xs",
          flex: 0,
          margin: "none"
        },
        {
          type: "text",
          text: `${project.fullTitle}`,
          color: "#333333",
          size: "xs",
          flex: 5,
          wrap: true,
          margin: "xs"
        },
        {
          type: "text",
          text: `${project.formattedTotal}`,
          weight: "bold",
          color: "#FF6B6B",
          size: "xs",
          flex: 2,
          align: "end"
        }
      ],
      spacing: "none",
      margin: "xs"
    });

    // Add underline for each project
    headerContents.push({
      type: "box",
      layout: "baseline",
      contents: [
        {
          type: "text",
          text: " ",
          flex: 0
        },
        {
          type: "text",
          text: `📋 ${project.expenseCount} รายการ`,
          color: "#999999",
          size: "xxs",
          flex: 7,
          margin: "sm"
        }
      ],
      spacing: "none",
      margin: "none"
    });
  });

  // Add separator after main projects
  headerContents.push({
    type: "separator",
    margin: "md"
  });

  // Get other projects data
  let otherProjectsTotal = 0;
  try {
    const otherProjectsResult = await getAllProjectsWithExpensesExceptMain(
      dbPool, 
      client_and_project, 
      formatNumberWithCommas
    );
    
    if (otherProjectsResult.success && otherProjectsResult.projects.length > 0) {
      otherProjectsTotal = otherProjectsResult.summary.totalAmount;
      
      // Add other projects header
      headerContents.push({
        type: "text",
        text: `📋 โครงการอื่นๆ (${otherProjectsResult.summary.totalProjects} โครงการ)`,
        color: "#FF9500",
        size: "xs",
        weight: "bold",
        align: "center",
        margin: "sm"
      });

      // Show individual other projects (limit to top 5 for space)
      const topOtherProjects = otherProjectsResult.projects.slice(0, 5);
      topOtherProjects.forEach((project, index) => {
        headerContents.push({
          type: "box",
          layout: "baseline",
          contents: [
            {
              type: "text",
              text: `${index + 4}.`,
              color: "#8C8C8C",
              size: "xs",
              flex: 0,
              margin: "none"
            },
            {
              type: "text",
              text: `${project.title}`,
              color: "#333333",
              size: "xs",
              flex: 5,
              wrap: true,
              margin: "xs"
            },
            {
              type: "text",
              text: `${project.formattedAmount}`,
              weight: "bold",
              color: "#FF9500",
              size: "xs",
              flex: 2,
              align: "end"
            }
          ],
          spacing: "none",
          margin: "xs"
        });

        // Add underline for each other project
        headerContents.push({
          type: "box",
          layout: "baseline",
          contents: [
            {
              type: "text",
              text: " ",
              flex: 0
            },
            {
              type: "text",
              text: `📋 ${project.expenseCount} รายการ`,
              color: "#999999",
              size: "xxs",
              flex: 7,
              margin: "sm"
            }
          ],
          spacing: "none",
          margin: "none"
        });
      });

      // Show remaining count if more than 5
      if (otherProjectsResult.projects.length > 5) {
        headerContents.push({
          type: "text",
          text: `... และอีก ${otherProjectsResult.projects.length - 5} โครงการ`,
          color: "#999999",
          size: "xs",
          align: "center",
          margin: "sm"
        });
      }
    }
  } catch (error) {
    console.error('Error getting other projects for header:', error);
  }

  // Add separator
  headerContents.push({
    type: "separator",
    margin: "md"
  });

  // Add total and average (including other projects)
  const grandTotal = monthData.totalExpense + otherProjectsTotal;
  const grandTotalFormatted = formatNumberWithCommas(grandTotal);
  const grandAveragePerDay = formatNumberWithCommas(grandTotal / new Date().getDate());
  
  headerContents.push({
    type: "box",
    layout: "baseline",
    contents: [
      {
        type: "text",
        text: "💰 รวมทั้งหมด:",
        color: "#8C8C8C",
        size: "sm",
        flex: 2
      },
      {
        type: "text",
        text: `${grandTotalFormatted} บาท`,
        weight: "bold",
        color: "#E60012",
        size: "lg",
        flex: 3
      }
    ],
    spacing: "sm",
    margin: "md"
  });

  headerContents.push({
    type: "box",
    layout: "baseline",
    contents: [
      {
        type: "text",
        text: "📈 เฉลี่ย/วัน:",
        color: "#8C8C8C",
        size: "sm",
        flex: 2
      },
      {
        type: "text",
        text: `${grandAveragePerDay} บาท`,
        weight: "bold",
        color: "#0B7EC0",
        size: "sm",
        flex: 3
      }
    ],
    spacing: "sm"
  });

  return {
    type: "bubble",
    header: {
      type: "box",
      layout: "vertical",
      contents: [
        {
          type: "text",
          text: "📊 รายงานค่าใช้จ่ายประจำเดือน",
          weight: "bold",
          color: "#ffffff",
          size: "lg",
          align: "center"
        },
        {
          type: "text",
          text: `เดือน${monthData.monthName} ${monthData.buddhistYear}`,
          weight: "bold",
          color: "#ffffff",
          size: "md",
          align: "center"
        }
      ],
      backgroundColor: "#27ACB2",
      paddingTop: "19px",
      paddingAll: "12px",
      paddingBottom: "16px"
    },
    body: {
      type: "box",
      layout: "vertical",
      contents: headerContents,
      spacing: "md",
      paddingAll: "12px"
    },
    styles: {
      footer: {
        separator: false
      }
    }
  };
}

// Create flex message for each project
function createProjectFlexMessage(project, projectNumber, totalProjects) {
  const expenseContents = [];
  
  // Show only top 3 highest expenses
  const displayExpenses = project.expenses.slice(0, 3);
  displayExpenses.forEach((expense, index) => {
    // Ensure description is not empty
    const description = expense.description && expense.description.trim() !== '' 
      ? expense.description 
      : 'รายการไม่ระบุ';
    
    expenseContents.push({
      type: "box",
      layout: "baseline",
      contents: [
        {
          type: "text",
          text: `${index + 1}.`,
          color: "#8C8C8C",
          size: "xs",
          flex: 0,
          margin: "none"
        },
        {
          type: "text",
          text: description,
          color: "#666666",
          size: "xs",
          flex: 4,
          wrap: true,
          margin: "sm"
        },
        {
          type: "text",
          text: `${expense.formattedAmount}`,
          weight: "bold",
          color: expense.hasVat ? "#E60012" : "#1DB446",
          size: "xs",
          flex: 2,
          align: "end"
        }
      ],
      spacing: "none",
      margin: "xs"
    });
    
    expenseContents.push({
      type: "box",
      layout: "baseline",
      contents: [
        {
          type: "text",
          text: " ",
          flex: 0
        },
        {
          type: "text",
          text: `📅 ${expense.date}${expense.hasVat ? ' | VAT 7%' : ''}`,
          color: "#999999",
          size: "xxs",
          flex: 4,
          margin: "sm"
        }
      ],
      spacing: "none",
      margin: "none"
    });
  });
  
  if (project.expenses.length > 3) {
    expenseContents.push({
      type: "text",
      text: `... และอีก ${project.expenses.length - 3} รายการ`,
      color: "#999999",
      size: "xs",
      align: "center",
      margin: "sm"
    });
  }
  
  if (project.expenses.length === 0) {
    expenseContents.push({
      type: "text",
      text: "ไม่มีรายการค่าใช้จ่าย",
      color: "#999999",
      size: "sm",
      align: "center",
      margin: "md"
    });
  }

  return {
    type: "bubble",
      header: {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "text",
            text: `🏗️ โครงการ ${project.index}/${totalProjects}`,
            weight: "bold",
            color: "#ffffff",
            size: "sm"
          },
          {
            type: "text",
            text: project.title || "โครงการไม่ระบุ",
            weight: "bold",
            color: "#ffffff",
            size: "md",
            wrap: true
          }
        ],
        backgroundColor: "#FF6B6B",
        paddingTop: "19px",
        paddingAll: "12px",
        paddingBottom: "16px"
      },
      body: {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "box",
            layout: "baseline",
            contents: [
              {
                type: "text",
                text: "💰 ยอดรวม:",
                color: "#8C8C8C",
                size: "sm",
                flex: 2
              },
              {
                type: "text",
                text: `${project.formattedTotal} บาท`,
                weight: "bold",
                color: "#E60012",
                size: "lg",
                flex: 3
              }
            ],
            spacing: "sm"
          },
          {
            type: "box",
            layout: "baseline",
            contents: [
              {
                type: "text",
                text: "📋 รายการ:",
                color: "#8C8C8C",
                size: "sm",
                flex: 2
              },
              {
                type: "text",
                text: `${project.expenseCount} รายการ`,
                weight: "bold",
                color: "#666666",
                size: "sm",
                flex: 3
              }
            ],
            spacing: "sm",
            margin: "md"
          },
          {
            type: "text",
            text: "💰 ยอดใหญ่ Top 3:",
            color: "#FF6B6B",
            size: "xs",
            weight: "bold",
            align: "center",
            margin: "sm"
          },
          {
            type: "separator",
            margin: "md"
          },
          {
            type: "box",
            layout: "vertical",
            contents: expenseContents,
            margin: "md",
            spacing: "xs"
          }
        ],
        spacing: "md",
        paddingAll: "12px"
      }
    };
}

// Send monthly summary report to LINE with Flex Messages
async function sendMonthlySummaryReport(dbPool, client_and_project, formatNumberWithCommas, LINE_ACCESS_TOKEN, REPORT_LINE_USER_ID, targetDate = null) {
  const axios = require('axios');
  
  try {
    console.log(`📤 Generating monthly flex message for LINE User: ${REPORT_LINE_USER_ID}`);
    
    // Generate monthly data
    const monthData = await generateMonthlyExpenseData(dbPool, client_and_project, formatNumberWithCommas, targetDate);
    
    // Create carousel bubbles array
    const bubbles = [];
    
    // Add header bubble
    bubbles.push(await createMonthlyHeaderFlexMessage(monthData, dbPool, client_and_project, formatNumberWithCommas));
    
    // Add project bubbles (limit to avoid too large message)
    const maxProjectBubbles = 9; // 1 header + up to 9 project bubbles (LINE carousel limit is 10)
    const projectsToShow = monthData.projects.slice(0, maxProjectBubbles);
    
    projectsToShow.forEach(project => {
      // Validate project data before creating flex message
      if (project.title && project.title.trim() !== '') {
        bubbles.push(createProjectFlexMessage(project, project.index, monthData.projectCount));
      }
    });
    
    // Add summary card for remaining projects if any
    if (monthData.projects.length > maxProjectBubbles) {
      const remainingProjects = monthData.projects.slice(maxProjectBubbles);
      bubbles.push(createRemainingProjectsSummaryCard(remainingProjects, formatNumberWithCommas, monthData.projectCount));
    }
    
    // Create single carousel flex message
    const carouselMessage = {
      type: "flex",
      altText: `รายงานค่าใช้จ่ายประจำเดือน ${monthData.monthName} ${monthData.buddhistYear}`,
      contents: {
        type: "carousel",
        contents: bubbles
      }
    };
    
    // Prepare messages array
    const messages = [carouselMessage];
    
    console.log(`📤 Sending 1 carousel message to LINE with ${bubbles.length} bubbles`);
    
    // Send messages to LINE
    try {
      const response = await axios.post('https://api.line.me/v2/bot/message/push', {
        to: REPORT_LINE_USER_ID,
        messages: messages
      }, {
        headers: {
          'Authorization': `Bearer ${LINE_ACCESS_TOKEN}`,
          'Content-Type': 'application/json'
        }
      });
      
      console.log(` Monthly carousel flex message sent successfully`);
      return {
        success: true,
        messageCount: messages.length,
        carouselBubbles: bubbles.length,
        successCount: messages.length,
        failCount: 0,
        sentTo: REPORT_LINE_USER_ID,
        monthData: monthData
      };
      
    } catch (error) {
      console.error(`❌ Failed to send monthly carousel flex message:`, error.response?.data || error.message);
      return { 
        success: false, 
        error: error.response?.data || error.message 
      };
    }
    
  } catch (error) {
    console.error('Error sending monthly summary report:', error);
    return {
      success: false,
      error: error.message
    };
  }
}

// Create summary card for remaining projects
function createRemainingProjectsSummaryCard(remainingProjects, formatNumberWithCommas, totalProjectCount) {
  const remainingTotal = remainingProjects.reduce((sum, p) => sum + p.total, 0);
  const startIndex = totalProjectCount - remainingProjects.length + 1;
  
  // Create project list (show up to 8 projects)
  const projectContents = [];
  const projectsToShow = remainingProjects.slice(0, 8);
  
  projectsToShow.forEach((project, index) => {
    projectContents.push({
      type: "box",
      layout: "baseline",
      contents: [
        {
          type: "text",
          text: `${startIndex + index}.`,
          color: "#8C8C8C",
          size: "xs",
          flex: 0,
          margin: "none"
        },
        {
          type: "text",
          text: project.title || "โครงการไม่ระบุ",
          color: "#666666",
          size: "xs",
          flex: 4,
          wrap: true,
          margin: "sm"
        },
        {
          type: "text",
          text: `${project.formattedTotal}`,
          weight: "bold",
          color: "#1DB446",
          size: "xs",
          flex: 2,
          align: "end"
        }
      ],
      spacing: "none",
      margin: "xs"
    });
  });
  
  if (remainingProjects.length > 8) {
    projectContents.push({
      type: "text",
      text: `... และอีก ${remainingProjects.length - 8} โครงการ`,
      color: "#999999",
      size: "xs",
      align: "center",
      margin: "sm"
    });
  }
  
  return {
    type: "bubble",
    header: {
      type: "box",
      layout: "vertical",
      contents: [
        {
          type: "text",
          text: `📊 โครงการอื่นๆ`,
          weight: "bold",
          color: "#ffffff",
          size: "sm"
        },
        {
          type: "text",
          text: `${remainingProjects.length} โครงการ`,
          weight: "bold",
          color: "#ffffff",
          size: "md",
          wrap: true
        }
      ],
      backgroundColor: "#9B59B6",
      paddingTop: "19px",
      paddingAll: "12px",
      paddingBottom: "16px"
    },
    body: {
      type: "box",
      layout: "vertical",
      contents: [
        {
          type: "box",
          layout: "baseline",
          contents: [
            {
              type: "text",
              text: "💰 ยอดรวม:",
              color: "#8C8C8C",
              size: "sm",
              flex: 2
            },
            {
              type: "text",
              text: `${formatNumberWithCommas(remainingTotal)} บาท`,
              weight: "bold",
              color: "#E60012",
              size: "lg",
              flex: 3
            }
          ],
          spacing: "sm"
        },
        {
          type: "box",
          layout: "baseline",
          contents: [
            {
              type: "text",
              text: "📋 จำนวน:",
              color: "#8C8C8C",
              size: "sm",
              flex: 2
            },
            {
              type: "text",
              text: `${remainingProjects.length} โครงการ`,
              weight: "bold",
              color: "#666666",
              size: "sm",
              flex: 3
            }
          ],
          spacing: "sm",
          margin: "md"
        },
        {
          type: "text",
          text: "📋 รายการโครงการ:",
          color: "#9B59B6",
          size: "xs",
          weight: "bold",
          align: "center",
          margin: "sm"
        },
        {
          type: "separator",
          margin: "md"
        },
        {
          type: "box",
          layout: "vertical",
          contents: projectContents,
          margin: "md",
          spacing: "xs"
        }
      ],
      spacing: "md",
      paddingAll: "12px"
    }
  };
}

// Get all projects with expenses in current month (excluding main 3 projects)
async function getAllProjectsWithExpensesExceptMain(dbPool, client_and_project, formatNumberWithCommas, targetDate = null) {
  try {
    // Use current date if no target date provided
    const today = targetDate ? new Date(targetDate) : new Date();
    const currentYear = today.getFullYear();
    const currentMonth = today.getMonth() + 1;
    
    // Calculate first day of current month and current day
    const firstDayOfMonth = new Date(currentYear, currentMonth - 1, 1).toISOString().split('T')[0];
    const currentDay = today.toISOString().split('T')[0];
    
    const monthNames = [
      'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
      'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    const buddhistYear = currentYear + 543;
    const monthName = monthNames[currentMonth - 1];
    
    // Get all projects that have expenses in current month
    const [allProjectsWithExpenses] = await dbPool.query(`
      SELECT DISTINCT 
        p.id,
        p.title,
        SUM(CASE WHEN e.tax_id = 2 THEN e.amount * 1.07 ELSE e.amount END) as total_amount,
        COUNT(e.id) as expense_count
      FROM rise_projects p
      INNER JOIN rise_expenses e ON p.id = e.project_id
      WHERE e.expense_date >= ? 
        AND e.expense_date <= ?
        AND p.deleted = 0
        AND e.deleted = 0
        AND p.id > 130
      GROUP BY p.id, p.title
      ORDER BY total_amount DESC
    `, [firstDayOfMonth, currentDay]);
    
    // Get the main 3 project titles from client_and_project config
    const mainProjectTitles = [];
    for (const projectConfig of client_and_project.slice(0, 3)) {
      if (projectConfig.project && projectConfig.project.trim() !== '') {
        mainProjectTitles.push(projectConfig.project);
      } else {
        // For ruby projects, use current month title
        mainProjectTitles.push(`ค่าใช้จ่าย เดือน${monthName} ${buddhistYear}`);
      }
    }
    
    // Filter out the main 3 projects
    const otherProjects = allProjectsWithExpenses.filter(project => 
      !mainProjectTitles.includes(project.title)
    );
    
    // Format the results
    const formattedProjects = otherProjects.map((project, index) => ({
      id: project.id,
      title: project.title,
      totalAmount: parseFloat(project.total_amount) || 0,
      formattedAmount: formatNumberWithCommas(parseFloat(project.total_amount) || 0),
      expenseCount: parseInt(project.expense_count) || 0,
      rank: index + 1
    }));
    
    // Calculate totals
    const totalAmount = formattedProjects.reduce((sum, project) => sum + project.totalAmount, 0);
    const totalExpenseCount = formattedProjects.reduce((sum, project) => sum + project.expenseCount, 0);
    
    return {
      success: true,
      period: {
        monthName: monthName,
        buddhistYear: buddhistYear,
        startDate: firstDayOfMonth.split('-').reverse().join('/'),
        endDate: currentDay.split('-').reverse().join('/'),
        dateRange: `${firstDayOfMonth.split('-').reverse().join('/')} - ${currentDay.split('-').reverse().join('/')}`
      },
      summary: {
        totalProjects: formattedProjects.length,
        totalAmount: totalAmount,
        formattedTotalAmount: formatNumberWithCommas(totalAmount),
        totalExpenseCount: totalExpenseCount,
        excludedMainProjects: mainProjectTitles.length
      },
      projects: formattedProjects,
      excludedMainProjects: mainProjectTitles
    };
    
  } catch (error) {
    console.error('Error getting all projects with expenses except main:', error);
    return {
      success: false,
      error: error.message
    };
  }
}

module.exports = {
  generateMonthlyExpenseSummary,
  generateMonthlyExpenseData,
  createMonthlyHeaderFlexMessage,
  createProjectFlexMessage,
  createRemainingProjectsSummaryCard,
  sendMonthlySummaryReport,
  getAllProjectsWithExpensesExceptMain,
  router
};
