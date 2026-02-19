const express = require('express');
const axios = require('axios');
const router = express.Router();

// Generate daily expense data for flex messages
async function generateDailyExpenseData(dbPool, client_and_project, formatNumberWithCommas, targetDate = null) {
  try {
    const today = targetDate || new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
    console.log('Generating daily expense data for:', today);
    
    const thaiDate = today.split('-').reverse().join('/'); // Convert to dd/mm/yyyy
    let totalDayExpense = 0;
    let hasExpenses = false;
    let projectCount = 0;
    const projects = [];
    
    // Loop through all projects in client_and_project
    for (const projectConfig of client_and_project) {
      try {
        let dbProjects = [];
        let projectTitle = '';
        
        // Determine project title and search for projects
        if (projectConfig.project && projectConfig.project.trim() !== '') {
          projectTitle = projectConfig.project;
          const [projectResults] = await dbPool.query(
            'SELECT id, title FROM rise_projects WHERE title = ? AND deleted = 0',
            [projectConfig.project]
          );
          dbProjects = projectResults;
        } else {
          // For ruby projects, use current month title
          const monthNames = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
          ];
          const date = new Date(targetDate || Date.now());
          const buddhistYear = date.getFullYear() + 543;
          projectTitle = `ค่าใช้จ่าย เดือน${monthNames[date.getMonth()]} ${buddhistYear}`;
          
          const [projectResults] = await dbPool.query(
            'SELECT id, title FROM rise_projects WHERE title = ? AND deleted = 0',
            [projectTitle]
          );
          dbProjects = projectResults;
        }
        
        let projectTotal = 0;
        let projectExpenseDetails = [];
        let projectHasExpenses = false;
        
        if (dbProjects.length > 0) {
          for (const project of dbProjects) {
            // Get individual expenses for this project for today
            const [expenseDetails] = await dbPool.query(`
              SELECT 
                description,
                amount,
                tax_id,
                CASE WHEN tax_id = 2 THEN amount * 0.07 ELSE 0 END as vat_amount
              FROM rise_expenses 
              WHERE project_id = ? 
                AND DATE(expense_date) = ? 
                AND deleted = 0
              ORDER BY id
            `, [project.id, today]);
            
            if (expenseDetails.length > 0) {
              for (const expense of expenseDetails) {
                const amount = parseFloat(expense.amount) || 0;
                const vatAmount = parseFloat(expense.vat_amount) || 0;
                const totalWithVat = amount + vatAmount;
                const hasVat = expense.tax_id === 2;
                
                projectTotal += totalWithVat;
                
                const formattedAmount = formatNumberWithCommas(totalWithVat);
                projectExpenseDetails.push({
                  description: expense.description,
                  amount: totalWithVat,
                  formattedAmount: formattedAmount,
                  hasVat: hasVat,
                  date: today, // Can add date formatting here if needed
                  preVatAmount: amount,
                  vatAmount: vatAmount
                });
              }
              projectHasExpenses = true;
            }
          }
        }
        
        // Always add project to the list
        projectCount++;
        projects.push({
          index: projectCount,
          title: projectTitle,
          fullTitle: projectTitle, // Keep full title for display
          client: projectConfig.client,
          total: projectTotal,
          formattedTotal: formatNumberWithCommas(projectTotal),
          hasExpenses: projectHasExpenses,
          expenses: projectExpenseDetails,
          expenseCount: projectExpenseDetails.length
        });
        
        if (projectHasExpenses) {
          totalDayExpense += projectTotal;
          hasExpenses = true;
        }
        
      } catch (error) {
        console.error(`Error processing project ${projectConfig.keyword}:`, error);
      }
    }

    // Sum expenses that were CREATED today (based on activity log timestamp)
    let createdTodayTotal = 0;
    let createdTodayCount = 0;
    try {
      const [createdRows] = await dbPool.query(`
        SELECT 
          COUNT(*) AS expense_count,
          SUM(e.amount + CASE WHEN e.tax_id = 2 THEN e.amount * 0.07 ELSE 0 END) AS total_with_vat
        FROM rise_activity_logs al
        JOIN rise_expenses e
          ON al.log_type = 'expense' AND al.log_type_id = e.id
        WHERE al.action = 'created'
          AND DATE(al.created_at) = ?
          AND e.deleted = 0
      `, [today]);

      if (createdRows.length > 0) {
        createdTodayCount = createdRows[0].expense_count || 0;
        createdTodayTotal = createdRows[0].total_with_vat || 0;
      }
    } catch (error) {
      console.error('Error calculating created-today expenses:', error);
    }
    
    return {
      date: today,
      thaiDate: thaiDate,
      totalExpense: totalDayExpense,
      formattedTotal: formatNumberWithCommas(totalDayExpense),
      createdTodayTotal: createdTodayTotal,
      formattedCreatedTodayTotal: formatNumberWithCommas(createdTodayTotal),
      createdTodayCount: createdTodayCount,
      hasExpenses: hasExpenses,
      projectCount: projectCount,
      projects: projects
    };
    
  } catch (error) {
    console.error('Error generating daily expense data:', error);
    throw error;
  }
}

// Create daily header flex message with summary (matching monthly style)
function createDailyHeaderFlexMessage(dailyData, formatNumberWithCommas) {
  const headerColor = dailyData.hasExpenses ? "#1DB446" : "#999999";
  
  // Create header contents array similar to monthly format
  const headerContents = [
    {
      type: "box",
      layout: "baseline",
      contents: [
        {
          type: "text",
          text: `วันที่รายงาน:`,
          color: "#8C8C8C",
          size: "xs",
          flex: 2
        },
        {
          type: "text",
          text: `${dailyData.thaiDate}`,
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

  // Add all projects (show even those with no expenses, like monthly format)
  const allProjects = dailyData.projects;
  const projectsToShow = allProjects.slice(0, 5); // Show max 5 projects in header
  
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
          text: project.title.length > 30 ? project.title.substring(0, 30) + "..." : project.title,
          color: "#333333",
          size: "xs",
          flex: 5,
          wrap: true,
          margin: "xs"
        },
        {
          type: "text",
          text: project.hasExpenses ? `${project.formattedTotal}` : "0.0",
          weight: "bold",
          color: project.hasExpenses ? "#FF6B6B" : "#999999",
          size: "xs",
          flex: 2,
          align: "end"
        }
      ],
      spacing: "none",
      margin: "xs"
    });

    // Add expense count underline (show count or "no expense")
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
          text: project.hasExpenses ? `📋 ${project.expenses.length} รายการ` : `📋 ไม่มีรายการ`,
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

  // Add separator if there are projects
  if (allProjects.length > 0) {
    headerContents.push({
      type: "separator",
      margin: "md"
    });
  }

  // Add other projects summary if more than 5 total projects
  const remainingProjects = allProjects.length - 5;
  if (remainingProjects > 0) {
    const otherProjects = allProjects.slice(5);
    const otherTotal = otherProjects.reduce((sum, p) => sum + p.total, 0);
    const otherWithExpenses = otherProjects.filter(p => p.hasExpenses).length;
    
    headerContents.push({
      type: "text",
      text: `📋 โครงการอื่นๆ (${remainingProjects} โครงการ)`,
      color: "#FF9500",
      size: "xs",
      weight: "bold",
      align: "center",
      margin: "sm"
    });

    headerContents.push({
      type: "box",
      layout: "baseline",
      contents: [
        {
          type: "text",
          text: `มีค่าใช้จ่าย ${otherWithExpenses} โครงการ:`,
          color: "#8C8C8C",
          size: "xs",
          flex: 3
        },
        {
          type: "text",
          text: formatNumberWithCommas(otherTotal),
          weight: "bold",
          color: otherTotal > 0 ? "#FF9500" : "#999999",
          size: "xs",
          flex: 2,
          align: "end"
        }
      ],
      spacing: "xs",
      margin: "xs"
    });
  }

  // Add grand total
  headerContents.push({
    type: "separator",
    margin: "lg"
  });

  headerContents.push({
    type: "box",
    layout: "baseline",
    contents: [
      {
        type: "text",
        text: "ค่าใช้จ่ายเฉพาะวันนี้:",
        color: "#1DB446",
        size: "md",
        weight: "bold",
        flex: 3
      },
      {
        type: "text",
        text: `${dailyData.formattedTotal} ฿`,
        weight: "bold",
        color: "#1DB446",
        size: "lg",
        flex: 2,
        align: "end"
      }
    ],
    spacing: "sm",
    margin: "lg"
  });

  // Show expenses that were added (created) today
  headerContents.push({
    type: "box",
    layout: "baseline",
    contents: [
      {
        type: "text",
        text: "ค่าใช้จ่ายที่เพิ่มวันนี้:",
        color: "#0066CC",
        size: "sm",
        weight: "bold",
        flex: 3
      },
      {
        type: "text",
        text: `${dailyData.formattedCreatedTodayTotal} ฿`,
        weight: "bold",
        color: "#0066CC",
        size: "md",
        flex: 2,
        align: "end"
      }
    ],
    spacing: "sm",
    margin: "md"
  });

  return {
    type: "flex",
    altText: `สรุปค่าใช้จ่ายประจำวัน ${dailyData.thaiDate} - ${dailyData.formattedTotal} ฿`,
    contents: {
      type: "bubble",
      header: {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "text",
            text: "📊 สรุปค่าใช้จ่ายประจำวัน",
            weight: "bold",
            color: "#ffffff",
            size: "lg"
          },
          {
            type: "text",
            text: `วันที่ ${dailyData.thaiDate}`,
            color: "#ffffff",
            size: "md",
            margin: "sm"
          }
        ],
        backgroundColor: headerColor,
        paddingAll: "20px"
      },
      body: {
        type: "box",
        layout: "vertical",
        contents: headerContents,
        paddingAll: "20px"
      }
    }
  };
}

// Create daily project flex message with expense details (matching monthly style)
function createDailyProjectFlexMessage(project, index, totalProjects, formatNumberWithCommas) {
  const headerColor = project.hasExpenses ? "#0066CC" : "#999999";
  
  const expenseContents = [];
  
  // Show only top 3 highest expenses like monthly format
  const sortedExpenses = project.expenses.sort((a, b) => b.amount - a.amount); // Sort by amount descending
  const displayExpenses = sortedExpenses.slice(0, 3); // Show only top 3 highest
  
  displayExpenses.forEach((expense, expIndex) => {
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
          text: `${expIndex + 1}.`,
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
    
    // Add date and VAT info line similar to monthly
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
          text: `📅 วันนี้${expense.hasVat ? ' | VAT 7%' : ''}`,
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
  
  // Add "more items" if there are more than 3 expenses
  if (project.expenses.length > 3) {
    const remainingCount = project.expenses.length - 3;
    const remainingExpenses = sortedExpenses.slice(3);
    const remainingTotal = remainingExpenses.reduce((sum, exp) => sum + exp.amount, 0);
    
    expenseContents.push({
      type: "text",
      text: `... และอีก ${remainingCount} รายการ (${formatNumberWithCommas(remainingTotal)} ฿)`,
      color: "#999999",
      size: "xs",
      align: "center",
      margin: "sm"
    });
  }
  
  // Handle no expenses case
  if (project.expenses.length === 0) {
    expenseContents.push({
      type: "text",
      text: "ไม่มีรายการค่าใช้จ่ายในวันนี้",
      color: "#999999",
      size: "sm",
      align: "center",
      margin: "md"
    });
  }

  return {
    type: "flex",
    altText: `${project.title} - ${project.formattedTotal} ฿`,
    contents: {
      type: "bubble",
      header: {
        type: "box",
        layout: "vertical",
        contents: [
          {
            type: "text",
            text: `🏗️ โครงการ ${index}/${totalProjects}`,
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
        backgroundColor: headerColor,
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
                text: "ลูกค้า:",
                color: "#8C8C8C",
                size: "sm",
                flex: 2
              },
              {
                type: "text",
                text: project.client || "ไม่ระบุ",
                weight: "bold",
                color: "#333333",
                size: "sm",
                flex: 5,
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
                text: "ยอดรวม:",
                color: "#8C8C8C",
                size: "sm",
                flex: 2
              },
              {
                type: "text",
                text: `${project.formattedTotal} ฿`,
                weight: "bold",
                color: project.hasExpenses ? "#1DB446" : "#999999",
                size: "lg",
                flex: 5,
                align: "end"
              }
            ],
            spacing: "sm",
            margin: "md"
          },
          {
            type: "separator",
            margin: "lg"
          },
          {
            type: "text",
            text: "รายการค่าใช้จ่าย:",
            weight: "bold",
            color: "#333333",
            size: "sm",
            margin: "lg"
          },
          {
            type: "box",
            layout: "vertical",
            contents: expenseContents,
            spacing: "xs",
            margin: "md"
          }
        ],
        paddingAll: "20px"
      }
    }
  };
}

// Send daily summary report with flex messages
async function sendDailySummaryFlexReport(dbPool, client_and_project, formatNumberWithCommas, LINE_ACCESS_TOKEN, REPORT_LINE_USER_ID, targetDate = null) {
  try {
    console.log('📊 Generating daily flex messages...');
    
    // Generate daily data
    const dailyData = await generateDailyExpenseData(dbPool, client_and_project, formatNumberWithCommas, targetDate);
    
    console.log(`📈 Daily data generated for ${dailyData.thaiDate}:`);
    console.log(`- Total expense: ${dailyData.formattedTotal} ฿`);
    console.log(`- Projects with expenses: ${dailyData.projects.filter(p => p.hasExpenses).length}`);
    console.log(`- Total projects: ${dailyData.projectCount}`);
    
    if (!REPORT_LINE_USER_ID) {
      throw new Error('REPORT_LINE_USER_ID not configured');
    }
    
    // Prepare flex messages array
    const flexMessages = [];
    
    // Add header message
    const headerMessage = createDailyHeaderFlexMessage(dailyData, formatNumberWithCommas);
    flexMessages.push(headerMessage);
    
    // Add project messages for all projects (limit to 9 more messages for total of 10)
    // Show all projects, not just those with expenses
    const allProjectsToShow = dailyData.projects.slice(0, 9); // Limit to 9 projects + 1 header = 10 total
    
    allProjectsToShow.forEach(project => {
      const projectMessage = createDailyProjectFlexMessage(project, project.index, dailyData.projectCount, formatNumberWithCommas);
      flexMessages.push(projectMessage);
    });
    
    console.log(`📱 Sending ${flexMessages.length} flex messages to LINE...`);
    
    // Send as carousel if multiple messages, otherwise single message
    let messagePayload;
    if (flexMessages.length > 1) {
      messagePayload = {
        type: "flex",
        altText: `สรุปค่าใช้จ่ายประจำวัน ${dailyData.thaiDate} - ${dailyData.formattedTotal} ฿`,
        contents: {
          type: "carousel",
          contents: flexMessages.map(msg => msg.contents)
        }
      };
    } else {
      messagePayload = flexMessages[0];
    }
    
    // Send to LINE
    const response = await axios.post('https://api.line.me/v2/bot/message/push', {
      to: REPORT_LINE_USER_ID,
      messages: [messagePayload]
    }, {
      headers: {
        'Authorization': `Bearer ${LINE_ACCESS_TOKEN}`,
        'Content-Type': 'application/json'
      }
    });
    
    console.log(' Daily flex messages sent successfully to LINE');
    
    return {
      success: true,
      messageCount: flexMessages.length,
      sentTo: REPORT_LINE_USER_ID,
      dailyData: dailyData,
      response: response.status
    };
    
  } catch (error) {
    console.error('❌ Error sending daily flex report:', error);
    return {
      success: false,
      error: error.message
    };
  }
}

// Generate text-based daily expense summary (legacy function)
async function generateDailyExpenseSummary(dbPool, client_and_project, formatNumberWithCommas, targetDate = null) {
  try {
    const today = targetDate || new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
    console.log('Generating daily expense summary for:', today);
    
    let summaryReport = `สรุปค่าใช้จ่ายประจำวัน  วันที่: ${today.split('-').reverse().join('/')}\n`;
    let totalDayExpense = 0;
    let hasExpenses = false;
    
    // Loop through all projects in client_and_project
    for (const projectConfig of client_and_project) {
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

          const monthNames = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
          ];
          const now = new Date();
          const buddhistYear = now.getFullYear() + 543;
      
          const currentMonthProject = `ค่าใช้จ่าย เดือน${monthNames[now.getMonth()]} ${buddhistYear}`;
          console.log(`Searching for monthly project: "${currentMonthProject}"`);
          console.log(`Searching for monthNames: "${monthNames[now.getMonth()]}`);
          const [projectResults] = await dbPool.query(
            'SELECT id, title FROM rise_projects WHERE title = ? AND deleted = 0',
            [currentMonthProject]
          );
          projects = projectResults;
          console.log(`Found ${projects.length} monthly projects for "${currentMonthProject}"`);
        }
        
        // Always show project, even if no expenses found
        let projectTitle = '';
        if (projectConfig.project && projectConfig.project.trim() !== '') {
          projectTitle = projectConfig.project;
        } else {
          // For ruby projects, use current month title
          const monthNames = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
          ];
          const now = new Date();
          const buddhistYear = now.getFullYear() + 543;
          projectTitle = `ค่าใช้จ่าย เดือน${monthNames[now.getMonth()]} ${buddhistYear}`;
        }
        
        summaryReport += ` - Project ${projectTitle}\n`;
        
        let projectTotal = 0;
        let projectExpenseDetails = [];
        let projectHasExpenses = false;
        
        if (projects.length > 0) {
          for (const project of projects) {
            console.log(`🔍 Checking expenses for project ID: ${project.id}, Title: "${project.title}"`);
            
            // Get individual expenses for this project for today
            const [expenseDetails] = await dbPool.query(`
              SELECT 
                description,
                amount,
                tax_id,
                CASE WHEN tax_id = 2 THEN amount * 0.07 ELSE 0 END as vat_amount
              FROM rise_expenses 
              WHERE project_id = ? 
                AND DATE(expense_date) = ? 
                AND deleted = 0
              ORDER BY id
            `, [project.id, today]);
            
            console.log(`📊 Found ${expenseDetails.length} expenses for project "${project.title}"`);
            
            if (expenseDetails.length > 0) {
              // Process each expense
              for (const expense of expenseDetails) {
                const amount = parseFloat(expense.amount) || 0;
                const vatAmount = parseFloat(expense.vat_amount) || 0;
                const totalWithVat = amount + vatAmount;
                
                projectTotal += totalWithVat;
                
                // Format expense description and amount
                const formattedAmount = formatNumberWithCommas(totalWithVat);
                projectExpenseDetails.push(`         - ${expense.description} ${formattedAmount} บาท`);
              }
              
              console.log(`💰 Project "${project.title}" total: ${projectTotal} baht`);
              projectHasExpenses = true;
            }
          }
        }
        
        // Add expense details or no expense message
        if (projectHasExpenses && projectExpenseDetails.length > 0) {
          summaryReport += `     รายละเอียด:\n`;
          summaryReport += `${projectExpenseDetails.join('\n')}\n`;
          summaryReport += `    รวม: ${formatNumberWithCommas(projectTotal)} บาท\n\n`;
          totalDayExpense += projectTotal;
          hasExpenses = true;
        } else {
          summaryReport += `     รายละเอียด:\n`;
          summaryReport += `         - ไม่มีรายการค่าใช้จ่ายวันนี้\n\n`;
        }
        
      } catch (error) {
        console.error(`Error processing project ${projectConfig.keyword}:`, error);
      }
    }
    
    // Add total summary
    if (hasExpenses) {
      summaryReport += `----------\n`;
      summaryReport += ` รวมทั้งหมด: ${formatNumberWithCommas(totalDayExpense)} บาท\n`;
      summaryReport += `-----------`;
    } else {
      summaryReport += `ไม่มีค่าใช้จ่ายในวันนี้`;
    }
    
    console.log('Daily expense summary generated:', summaryReport);
    return summaryReport;
    
  } catch (error) {
    console.error('Error generating daily expense summary:', error);
    return `❌ เกิดข้อผิดพลาดในการสร้างรายงาน: ${error.message}`;
  }
}

// Send text-based summary report to LINE (legacy function)
async function sendDailySummaryReport(dbPool, client_and_project, formatNumberWithCommas, LINE_ACCESS_TOKEN, REPORT_LINE_USER_ID, targetDate = null) {
  try {
    const summary = await generateDailyExpenseSummary(dbPool, client_and_project, formatNumberWithCommas, targetDate);
    
    if (REPORT_LINE_USER_ID) {
      // Send via LINE push message (not reply)
      await axios.post('https://api.line.me/v2/bot/message/push', {
        to: REPORT_LINE_USER_ID,
        messages: [{
          type: 'text',
          text: summary
        }]
      }, {
        headers: {
          'Authorization': `Bearer ${LINE_ACCESS_TOKEN}`,
          'Content-Type': 'application/json'
        }
      });
      console.log('Daily summary sent to LINE user:', REPORT_LINE_USER_ID);
      return {
        success: true,
        summary: summary,
        sentTo: REPORT_LINE_USER_ID
      };
    } else {
      console.log('Daily Summary Report:\n', summary);
      return {
        success: false,
        error: 'REPORT_LINE_USER_ID not configured',
        summary: summary
      };
    }
    
  } catch (error) {
    console.error('Error sending daily summary report:', error);
    return {
      success: false,
      error: error.message
    };
  }
}

// API Routes

// Bearer token middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN
  
  const validToken = process.env.API_BEARER_TOKEN;
  
  if (!token) {
    return res.status(401).json({ error: 'Access token required' });
  }
  
  if (token !== validToken) {
    return res.status(403).json({ error: 'Invalid token' });
  }
  
  next();
};

// API endpoint for daily summary (text format)
router.get('/summary', async (req, res) => {
  try {
    const dbPool = req.app.locals.dbPool;
    const client_and_project = req.app.locals.client_and_project;
    const formatNumberWithCommas = req.app.locals.formatNumberWithCommas;
    const targetDate = req.query.date || null;
    
    const summary = await generateDailyExpenseSummary(dbPool, client_and_project, formatNumberWithCommas, targetDate);
    res.json({
      success: true,
      date: targetDate || new Date().toISOString().split('T')[0],
      summary: summary
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// API endpoint for daily summary flex messages
router.get('/flex-summary', async (req, res) => {
  try {
    const dbPool = req.app.locals.dbPool;
    const client_and_project = req.app.locals.client_and_project;
    const formatNumberWithCommas = req.app.locals.formatNumberWithCommas;
    const targetDate = req.query.date || null;
    
    const dailyData = await generateDailyExpenseData(dbPool, client_and_project, formatNumberWithCommas, targetDate);
    
    res.json({
      success: true,
      date: dailyData.date,
      thaiDate: dailyData.thaiDate,
      dailyData: dailyData
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// API endpoint to send daily summary (text format)
router.post('/send-summary', authenticateToken, async (req, res) => {
  try {
    const dbPool = req.app.locals.dbPool;
    const client_and_project = req.app.locals.client_and_project;
    const formatNumberWithCommas = req.app.locals.formatNumberWithCommas;
    const targetDate = req.body.date || null;
    
    const LINE_ACCESS_TOKEN = process.env.LINE_ACCESS_TOKEN;
    const REPORT_LINE_USER_ID = process.env.REPORT_LINE_USER_ID;
    
    const result = await sendDailySummaryReport(dbPool, client_and_project, formatNumberWithCommas, LINE_ACCESS_TOKEN, REPORT_LINE_USER_ID, targetDate);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Daily summary sent to LINE successfully',
        sentTo: result.sentTo
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

// API endpoint to send daily flex summary
router.post('/send-flex-summary', authenticateToken, async (req, res) => {
  try {
    const dbPool = req.app.locals.dbPool;
    const client_and_project = req.app.locals.client_and_project;
    const formatNumberWithCommas = req.app.locals.formatNumberWithCommas;
    const targetDate = req.body.date || null;
    
    
    const LINE_ACCESS_TOKEN = process.env.LINE_ACCESS_TOKEN;
    const REPORT_LINE_USER_ID = process.env.REPORT_LINE_USER_ID;
    
    const result = await sendDailySummaryFlexReport(dbPool, client_and_project, formatNumberWithCommas, LINE_ACCESS_TOKEN, REPORT_LINE_USER_ID, targetDate);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Daily flex summary sent to LINE successfully',
        messageCount: result.messageCount,
        sentTo: result.sentTo,
        dailyData: result.dailyData
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

// Test endpoint for daily flex summary (no authentication)
router.get('/test-flex-summary', async (req, res) => {
  try {
    const dbPool = req.app.locals.dbPool;
    const client_and_project = req.app.locals.client_and_project;
    const formatNumberWithCommas = req.app.locals.formatNumberWithCommas;
    const targetDate = req.query.date || null;
    console.log('test data')
    const LINE_ACCESS_TOKEN = process.env.LINE_ACCESS_TOKEN;
    const REPORT_LINE_USER_ID = process.env.REPORT_LINE_USER_ID;
    
    console.log(' TEST: Sending daily flex messages to LINE...');
    console.log(`📱 Target LINE User ID: ${REPORT_LINE_USER_ID}`);
    
    const result = await sendDailySummaryFlexReport(dbPool, client_and_project, formatNumberWithCommas, LINE_ACCESS_TOKEN, REPORT_LINE_USER_ID, targetDate);
    
    if (result.success) {
      res.json({
        success: true,
        message: ' Daily flex messages sent to LINE successfully!',
        messageCount: result.messageCount,
        sentTo: result.sentTo,
        timestamp: new Date().toLocaleString('th-TH'),
        dailyData: result.dailyData
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

// Test endpoint to preview daily flex message structure (no authentication)
router.get('/test-flex-preview', async (req, res) => {
  try {
    const dbPool = req.app.locals.dbPool;
    const client_and_project = req.app.locals.client_and_project;
    const formatNumberWithCommas = req.app.locals.formatNumberWithCommas;
    const targetDate = req.query.date || null;
    
    console.log(' TEST: Generating daily flex message preview...');
    
    const dailyData = await generateDailyExpenseData(dbPool, client_and_project, formatNumberWithCommas, targetDate);
    
    const messages = [];
    
    // Add header message
    messages.push(createDailyHeaderFlexMessage(dailyData, formatNumberWithCommas));
    
    // Add first 2 project messages for preview
    const projectsWithExpenses = dailyData.projects.filter(p => p.hasExpenses);
    const projectsToShow = projectsWithExpenses.slice(0, 2);
    projectsToShow.forEach(project => {
      messages.push(createDailyProjectFlexMessage(project, project.index, dailyData.projectCount, formatNumberWithCommas));
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
      message: ' Daily flex message preview generated successfully!',
      dailyData: dailyData,
      flexMessages: messages,
      messageCount: messages.length,
      validationErrors: validationErrors,
      isValid: validationErrors.length === 0,
      timestamp: new Date().toLocaleString('th-TH')
    });
    
  } catch (error) {
    console.error('❌ Daily flex preview error:', error);
    res.status(500).json({
      success: false,
      error: error.message,
      timestamp: new Date().toLocaleString('th-TH')
    });
  }
});

// Test endpoint to preview "created today" totals and rows
router.get('/test-created-today', async (req, res) => {
  try {
    const dbPool = req.app.locals.dbPool;
    const targetDate = req.query.date || new Date().toISOString().split('T')[0];

    const [rows] = await dbPool.query(`
      SELECT 
        al.created_at AS created_time,
        e.id,
        DATE(e.expense_date) AS expense_day,
        e.expense_date,
        e.title,
        e.description,
        e.amount AS pre_vat_amount,
        e.tax_id,
        CASE WHEN e.tax_id = 2 THEN e.amount * 0.07 ELSE 0 END AS vat_amount,
        e.amount + CASE WHEN e.tax_id = 2 THEN e.amount * 0.07 ELSE 0 END AS total_with_vat,
        p.title AS project,
        cl.company_name AS client
      FROM rise_activity_logs al
      JOIN rise_expenses e
        ON al.log_type = 'expense' AND al.log_type_id = e.id
      LEFT JOIN rise_projects p ON p.id = e.project_id
      LEFT JOIN rise_clients cl ON cl.id = e.client_id
      WHERE al.action = 'created'
        AND DATE(al.created_at) = ?
        AND e.deleted = 0
      ORDER BY al.created_at, e.id
    `, [targetDate]);

    const totals = rows.reduce((acc, r) => {
      acc.count += 1;
      acc.sum_pre_vat += Number(r.pre_vat_amount || 0);
      acc.sum_vat += Number(r.vat_amount || 0);
      acc.sum_total += Number(r.total_with_vat || 0);
      return acc;
    }, { count: 0, sum_pre_vat: 0, sum_vat: 0, sum_total: 0 });

    res.json({
      success: true,
      date: targetDate,
      totals: totals,
      rows: rows
    });
  } catch (error) {
    console.error('❌ Error in /test-created-today:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = {
  router,
  generateDailyExpenseData,
  createDailyHeaderFlexMessage,
  createDailyProjectFlexMessage,
  sendDailySummaryFlexReport,
  generateDailyExpenseSummary,
  sendDailySummaryReport
};
