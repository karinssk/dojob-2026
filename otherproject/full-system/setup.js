#!/usr/bin/env node

/**
 * Migration and Setup Script for LINE Tasks System
 * This script helps migrate from old systems to the new unified system
 */

require('dotenv').config();
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const config = {
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB,
  port: process.env.DB_PORT || 3306
};

async function createConnection() {
  try {
    const connection = await mysql.createConnection(config);
    console.log('✅ Database connection established');
    return connection;
  } catch (error) {
    console.error('❌ Database connection failed:', error.message);
    process.exit(1);
  }
}

async function checkTableExists(connection, tableName) {
  try {
    const [rows] = await connection.execute(
      `SHOW TABLES LIKE ?`,
      [tableName]
    );
    return rows.length > 0;
  } catch (error) {
    console.error(`Error checking table ${tableName}:`, error.message);
    return false;
  }
}

async function createUserMappingsArrTable(connection) {
  console.log('\n📋 Creating user_mappings_arr table...');
  
  const createTableSQL = `
    CREATE TABLE IF NOT EXISTS user_mappings_arr (
      id INT AUTO_INCREMENT PRIMARY KEY,
      line_user_id VARCHAR(255) NOT NULL,
      rise_user_id INT NOT NULL,
      nick_name VARCHAR(255),
      line_display_name VARCHAR(255),
      duty_role ENUM('boss', 'staff') NOT NULL DEFAULT 'staff',
      is_active TINYINT(1) DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_line_user_id (line_user_id),
      INDEX idx_rise_user_id (rise_user_id),
      INDEX idx_duty_role (duty_role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `;
  
  try {
    await connection.execute(createTableSQL);
    console.log('✅ Table user_mappings_arr created successfully');
    return true;
  } catch (error) {
    console.error('❌ Failed to create table:', error.message);
    return false;
  }
}

async function migrateFromUserMappings(connection) {
  console.log('\n📊 Migrating data from user_mappings...');
  
  try {
    // Check if old table exists
    const hasOldTable = await checkTableExists(connection, 'user_mappings');
    
    if (!hasOldTable) {
      console.log('⚠️  user_mappings table not found, skipping migration');
      return true;
    }
    
    // Migrate data
    const migrateSQL = `
      INSERT INTO user_mappings_arr (line_user_id, rise_user_id, nick_name, line_display_name, duty_role, is_active)
      SELECT 
        line_user_id, 
        rise_user_id, 
        nick_name, 
        line_display_name,
        'staff' as duty_role,
        1 as is_active
      FROM user_mappings
      WHERE NOT EXISTS (
        SELECT 1 FROM user_mappings_arr 
        WHERE user_mappings_arr.line_user_id = user_mappings.line_user_id
      )
    `;
    
    const [result] = await connection.execute(migrateSQL);
    console.log(`✅ Migrated ${result.affectedRows} users from user_mappings`);
    return true;
  } catch (error) {
    console.error('❌ Migration failed:', error.message);
    return false;
  }
}

async function createNotificationLogsTable(connection) {
  console.log('\n📋 Checking/Creating rise_line_notification_logs table...');
  
  const createTableSQL = `
    CREATE TABLE IF NOT EXISTS rise_line_notification_logs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      task_id INT NOT NULL,
      user_id INT NOT NULL,
      notification_date DATE NOT NULL,
      time_slot INT NOT NULL DEFAULT 1,
      notification_type VARCHAR(50) DEFAULT 'overdue_task',
      sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_task_id (task_id),
      INDEX idx_user_id (user_id),
      INDEX idx_notification_date (notification_date),
      INDEX idx_time_slot (time_slot)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `;
  
  try {
    await connection.execute(createTableSQL);
    console.log('✅ Table rise_line_notification_logs ready');
    return true;
  } catch (error) {
    console.error('❌ Failed to create notification logs table:', error.message);
    return false;
  }
}

async function showUserMappings(connection) {
  console.log('\n👥 Current User Mappings:');
  console.log('═══════════════════════════════════════════════════════');
  
  try {
    const [rows] = await connection.execute(`
      SELECT 
        um.id,
        um.line_user_id,
        um.rise_user_id,
        um.nick_name,
        um.duty_role,
        um.is_active,
        u.first_name,
        u.last_name
      FROM user_mappings_arr um
      LEFT JOIN rise_users u ON um.rise_user_id = u.id
      ORDER BY um.duty_role, um.id
    `);
    
    if (rows.length === 0) {
      console.log('No users found. Please add users manually.');
      return;
    }
    
    rows.forEach(row => {
      const roleIcon = row.duty_role === 'boss' ? '👔' : '👤';
      const statusIcon = row.is_active ? '✅' : '❌';
      const name = row.nick_name || `${row.first_name || ''} ${row.last_name || ''}`.trim();
      console.log(`${roleIcon} ${statusIcon} [${row.id}] ${name} (${row.duty_role})`);
      console.log(`   LINE ID: ${row.line_user_id}`);
      console.log(`   Rise ID: ${row.rise_user_id}`);
      console.log('');
    });
  } catch (error) {
    console.error('❌ Failed to fetch user mappings:', error.message);
  }
}

async function createDirectories() {
  console.log('\n📁 Creating required directories...');
  
  const dirs = ['logs', 'logs/users', 'uploads'];
  
  for (const dir of dirs) {
    const dirPath = path.join(__dirname, dir);
    if (!fs.existsSync(dirPath)) {
      fs.mkdirSync(dirPath, { recursive: true });
      console.log(`✅ Created ${dir}/`);
    } else {
      console.log(`✓  ${dir}/ already exists`);
    }
  }
}

async function checkEnvFile() {
  console.log('\n⚙️  Checking environment configuration...');
  
  const envPath = path.join(__dirname, '.env');
  const envExamplePath = path.join(__dirname, '.env.example');
  
  if (!fs.existsSync(envPath)) {
    console.log('⚠️  .env file not found');
    
    if (fs.existsSync(envExamplePath)) {
      console.log('💡 Copy .env.example to .env and configure it:');
      console.log('   cp .env.example .env');
    }
    return false;
  }
  
  console.log('✅ .env file exists');
  
  // Check required variables
  const required = [
    'LINE_CHANNEL_SECRET',
    'LINE_CHANNEL_ACCESS_TOKEN',
    'DB_HOST',
    'DB_USER',
    'DB_PASSWORD',
    'DB'
  ];
  
  const missing = [];
  for (const key of required) {
    if (!process.env[key]) {
      missing.push(key);
    }
  }
  
  if (missing.length > 0) {
    console.log('⚠️  Missing required environment variables:');
    missing.forEach(key => console.log(`   - ${key}`));
    return false;
  }
  
  console.log('✅ All required environment variables are set');
  return true;
}

async function main() {
  console.log('═══════════════════════════════════════════════════════');
  console.log('   LINE Tasks System - Migration & Setup Script');
  console.log('═══════════════════════════════════════════════════════\n');
  
  // Check environment
  const envOk = await checkEnvFile();
  if (!envOk) {
    console.log('\n⚠️  Please configure .env file before running this script');
    process.exit(1);
  }
  
  // Create directories
  await createDirectories();
  
  // Connect to database
  const connection = await createConnection();
  
  try {
    // Create tables
    await createUserMappingsArrTable(connection);
    await createNotificationLogsTable(connection);
    
    // Migrate data
    await migrateFromUserMappings(connection);
    
    // Show results
    await showUserMappings(connection);
    
    console.log('\n═══════════════════════════════════════════════════════');
    console.log('✅ Setup completed successfully!');
    console.log('═══════════════════════════════════════════════════════\n');
    
    console.log('📝 Next Steps:');
    console.log('   1. Assign boss roles to users:');
    console.log('      UPDATE user_mappings_arr SET duty_role = \'boss\' WHERE line_user_id = \'...\';');
    console.log('');
    console.log('   2. Start the server:');
    console.log('      npm start');
    console.log('');
    console.log('   3. Configure LINE webhook:');
    console.log('      https://your-domain.com/webhook');
    console.log('');
    console.log('   4. Test the system:');
    console.log('      curl http://localhost:3010/health');
    console.log('');
    
  } catch (error) {
    console.error('\n❌ Setup failed:', error.message);
    process.exit(1);
  } finally {
    await connection.end();
  }
}

// Run the script
main().catch(error => {
  console.error('Fatal error:', error);
  process.exit(1);
});
