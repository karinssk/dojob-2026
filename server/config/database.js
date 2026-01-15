const mysql = require("mysql2/promise");
require("dotenv").config();

// Database connection configuration
const dbConfig = {
  host: process.env.DB_HOST ,
  user: process.env.DB_USER ,
  password: process.env.DB_PASSWORD ,
  database: process.env.DB_NAME ,
  port: process.env.DB_PORT ,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
};

const pool = mysql.createPool(dbConfig);

// Test database connection
async function testConnection() {
  try {
    const connection = await pool.getConnection();
    console.log("✅ Database connected successfully");
    connection.release();
  } catch (error) {
    console.error("❌ Database connection failed:", error.message);
  }
}

module.exports = { pool, testConnection };