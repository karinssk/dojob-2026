const express = require("express");
const cors = require("cors");
const bodyParser = require("body-parser");
const path = require("path");
require("dotenv").config();

// Import configuration and utilities
const { testConnection } = require("./config/database");

// Import route modules
const tasksRoutes = require("./routes/tasks");
const taskRoutes = require("./routes/task");
const subtaskRoutes = require("./routes/subtask");
const kanbanRoutes = require("./routes/kanban");
const reorderRoutes = require("./routes/reorder");
const commentsRoutes = require("./routes/comments");
const metadataRoutes = require("./routes/metadata");
const searchRoutes = require("./routes/search");
const debugRoutes = require("./routes/debug");
const statusRoutes = require("./routes/status");
const storyboardRoutes = require("./routes/storyboard");

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
// Enhanced CORS configuration
app.use(
  cors({
    origin: function (origin, callback) {
      // Allow requests with no origin (like mobile apps or curl requests)
      if (!origin) return callback(null, true);

      const allowedOrigins = [
        "http://localhost:8888",
        "http://127.0.0.1:8888",
        "http://localhost:3000",
        "https://dojob.rubyshop168.com",
        "https://api-dojob.rubyshop.co.th",
        "https://dojob.rubyshop.co.th",
      ];

      if (allowedOrigins.indexOf(origin) !== -1) {
        callback(null, true);
      } else {
        console.log("❌ CORS blocked origin:", origin);
        callback(new Error("Not allowed by CORS"));
      }
    },
    credentials: true,
    methods: ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    allowedHeaders: ["Content-Type", "Authorization", "X-User-ID"],
    optionsSuccessStatus: 200,
  })
);

// Additional CORS headers for preflight requests
app.use((req, res, next) => {
  res.header("Access-Control-Allow-Origin", req.headers.origin);
  res.header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
  res.header(
    "Access-Control-Allow-Headers",
    "Content-Type, Authorization, X-User-ID"
  );
  res.header("Access-Control-Allow-Credentials", "true");

  if (req.method === "OPTIONS") {
    res.sendStatus(200);
  } else {
    next();
  }
});
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Serve static files for uploads from PHP file system
app.use(
  "/uploads",
  express.static(path.join(__dirname, "../files/timeline_files"))
);

// Serve storyboard files
app.use("/files", express.static(path.join(__dirname, "../files")));

// Serve test files from parent directory
app.use("/test", express.static(path.join(__dirname, "..")));

// Serve public files (including test pages)
app.use("/public", express.static(path.join(__dirname, "public")));

// Test database connection
testConnection();

// API Routes
app.use("/api/tasks", tasksRoutes);
app.use("/api/task", taskRoutes);
app.use("/api/subtask", subtaskRoutes);
app.use("/api/kanban", kanbanRoutes);
app.use("/api/task", reorderRoutes);
app.use("/api/task", commentsRoutes);
app.use("/api", metadataRoutes);
app.use("/api/search", searchRoutes);
app.use("/api/debug", debugRoutes);
app.use("/api", statusRoutes);
app.use("/api/storyboard", storyboardRoutes);

// Health check endpoint
app.get("/api/health", (req, res) => {
  res.json({
    success: true,
    message: "Task Board API is running",
    timestamp: new Date().toISOString(),
  });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({
    success: false,
    error: "Something went wrong!",
  });
});

// 404 handler
app.use("*", (req, res) => {
  res.status(404).json({
    success: false,
    error: "Endpoint not found",
  });
});

app.listen(PORT, () => {
  console.log(`🚀 Task Board API Server running on port ${PORT}`);
  console.log(`📋 API Base URL: http://localhost:${PORT}/api`);
  console.log(`🏥 Health Check: http://localhost:${PORT}/api/health`);
  console.log(`📁 Uploads: http://localhost:${PORT}/uploads`);
  console.log(`🎬 Storyboard Export: http://localhost:${PORT}/api/storyboard`);
});

module.exports = app;
