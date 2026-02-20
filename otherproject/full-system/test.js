#!/usr/bin/env node

/**
 * Test script for LINE Tasks System
 * Run various tests to verify the system is working correctly
 */

require('dotenv').config();
const Database = require('./database');
const Utils = require('./utils');

async function testDatabaseConnection() {
  console.log('📡 Testing database connection...');
  
  try {
    const db = new Database();
    const [result] = await db.pool.execute('SELECT 1 as test');
    
    if (result[0].test === 1) {
      console.log('✅ Database connection successful\n');
      return db;
    }
  } catch (error) {
    console.error('❌ Database connection failed:', error.message);
    return null;
  }
}

async function testUserMappings(db) {
  console.log('👥 Testing user mappings...');
  
  try {
    // Get all users
    const [users] = await db.pool.execute(`
      SELECT * FROM user_mappings_arr WHERE is_active = 1
    `);
    
    console.log(`   Found ${users.length} active users`);
    
    // Count by role
    const bosses = users.filter(u => u.duty_role === 'boss').length;
    const staff = users.filter(u => u.duty_role === 'staff').length;
    
    console.log(`   - ${bosses} boss users`);
    console.log(`   - ${staff} staff users`);
    
    if (users.length === 0) {
      console.log('⚠️  No users found! Please add users to user_mappings_arr table');
    } else {
      console.log('✅ User mappings table is working\n');
    }
    
    return users.length > 0;
  } catch (error) {
    console.error('❌ User mappings test failed:', error.message);
    return false;
  }
}

async function testCurrentMonthProject(db) {
  console.log('📅 Testing current month project...');
  
  try {
    const projectName = Utils.getCurrentMonthProjectName();
    console.log(`   Project name: ${projectName}`);
    
    const project = await db.getProjectByName(projectName);
    
    if (project) {
      console.log(`   ✅ Project found (ID: ${project.id})\n`);
    } else {
      console.log(`   ⚠️  Project not found (will be created when first task is added)\n`);
    }
    
    return true;
  } catch (error) {
    console.error('❌ Project test failed:', error.message);
    return false;
  }
}

async function testTimeRestrictions() {
  console.log('⏰ Testing time restrictions...');
  
  const now = new Date();
  const hour = now.getHours();
  const minute = now.getMinutes();
  
  console.log(`   Current time: ${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`);
  
  const isDisabled = Utils.isDailyTaskDisabledTime();
  
  if (isDisabled) {
    console.log('   ⏸️  Daily task saving is currently DISABLED (07:00-17:29)');
  } else {
    console.log('   ✅ Daily task saving is currently ENABLED (17:30-06:59)');
  }
  
  console.log('');
  return true;
}

async function testTextValidation() {
  console.log('📝 Testing text validation...');
  
  const config = require('./config');
  const minLength = config.dailyTask.minTextLength;
  
  const testCases = [
    { text: 'Short', expected: false },
    { text: 'This is a longer text that should pass the minimum length validation test for daily tasks', expected: true },
    { text: '', expected: false },
    { text: 'Exactly fifty characters text here for validation', expected: true }
  ];
  
  let passed = 0;
  
  testCases.forEach((testCase, index) => {
    const result = Utils.isTextLengthValid(testCase.text, minLength);
    const status = result === testCase.expected ? '✅' : '❌';
    console.log(`   ${status} Test ${index + 1}: "${testCase.text.substring(0, 30)}..." (${testCase.text.length} chars)`);
    
    if (result === testCase.expected) passed++;
  });
  
  console.log(`   ${passed}/${testCases.length} tests passed\n`);
  return passed === testCases.length;
}

async function testNotificationLogsTable(db) {
  console.log('🔔 Testing notification logs table...');
  
  try {
    const [result] = await db.pool.execute(`
      SHOW TABLES LIKE 'rise_line_notification_logs'
    `);
    
    if (result.length > 0) {
      console.log('   ✅ Notification logs table exists');
      
      // Check columns
      const [columns] = await db.pool.execute(`
        SHOW COLUMNS FROM rise_line_notification_logs
      `);
      
      const requiredColumns = ['id', 'task_id', 'user_id', 'notification_date', 'time_slot'];
      const existingColumns = columns.map(col => col.Field);
      
      const missingColumns = requiredColumns.filter(col => !existingColumns.includes(col));
      
      if (missingColumns.length === 0) {
        console.log('   ✅ All required columns present\n');
        return true;
      } else {
        console.log('   ⚠️  Missing columns:', missingColumns.join(', '));
        console.log('');
        return false;
      }
    } else {
      console.log('   ❌ Notification logs table not found');
      console.log('   Run: node setup.js to create it\n');
      return false;
    }
  } catch (error) {
    console.error('❌ Notification logs test failed:', error.message);
    return false;
  }
}

async function testEnvironmentVariables() {
  console.log('⚙️  Testing environment variables...');
  
  const required = {
    'LINE_CHANNEL_SECRET': process.env.LINE_CHANNEL_SECRET,
    'LINE_CHANNEL_ACCESS_TOKEN': process.env.LINE_CHANNEL_ACCESS_TOKEN || process.env.LINE_ACCESS_TOKEN,
    'DB_HOST': process.env.DB_HOST,
    'DB_USER': process.env.DB_USER,
    'DB_PASSWORD': process.env.DB_PASSWORD,
    'DB': process.env.DB,
  };
  
  let allSet = true;
  
  for (const [key, value] of Object.entries(required)) {
    if (!value) {
      console.log(`   ❌ ${key} is not set`);
      allSet = false;
    } else {
      const displayValue = key.includes('PASSWORD') || key.includes('SECRET') || key.includes('TOKEN') 
        ? '***hidden***' 
        : value;
      console.log(`   ✅ ${key} = ${displayValue}`);
    }
  }
  
  console.log('');
  return allSet;
}

async function main() {
  console.log('═══════════════════════════════════════════════════════');
  console.log('   LINE Tasks System - Test Suite');
  console.log('═══════════════════════════════════════════════════════\n');
  
  const results = [];
  
  // Test environment
  results.push({ name: 'Environment Variables', passed: await testEnvironmentVariables() });
  
  // Test database connection
  const db = await testDatabaseConnection();
  results.push({ name: 'Database Connection', passed: db !== null });
  
  if (!db) {
    console.log('\n❌ Cannot continue without database connection');
    process.exit(1);
  }
  
  // Run all tests
  results.push({ name: 'User Mappings', passed: await testUserMappings(db) });
  results.push({ name: 'Current Month Project', passed: await testCurrentMonthProject(db) });
  results.push({ name: 'Time Restrictions', passed: await testTimeRestrictions() });
  results.push({ name: 'Text Validation', passed: await testTextValidation() });
  results.push({ name: 'Notification Logs Table', passed: await testNotificationLogsTable(db) });
  
  // Close connection
  await db.close();
  
  // Summary
  console.log('═══════════════════════════════════════════════════════');
  console.log('   Test Summary');
  console.log('═══════════════════════════════════════════════════════\n');
  
  const passed = results.filter(r => r.passed).length;
  const total = results.length;
  
  results.forEach(result => {
    const icon = result.passed ? '✅' : '❌';
    console.log(`${icon} ${result.name}`);
  });
  
  console.log(`\n${passed}/${total} tests passed`);
  
  if (passed === total) {
    console.log('\n🎉 All tests passed! System is ready to use.\n');
    process.exit(0);
  } else {
    console.log('\n⚠️  Some tests failed. Please fix the issues before using the system.\n');
    process.exit(1);
  }
}

// Run tests
main().catch(error => {
  console.error('Fatal error:', error);
  process.exit(1);
});
