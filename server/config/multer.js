const multer = require("multer");
const path = require("path");
const fs = require("fs");

// Configure multer for file uploads to PHP file system
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    // Use the PHP file system path
    const uploadDir = path.join(__dirname, "../../files/timeline_files");
    if (!fs.existsSync(uploadDir)) {
      fs.mkdirSync(uploadDir, { recursive: true });
    }
    cb(null, uploadDir);
  },
  filename: function (req, file, cb) {
    // Generate filename similar to PHP system: project_comment_file + unique ID
    const uniqueId = Math.random().toString(36).substr(2, 9) + "-" + Date.now() + Math.floor(Math.random() * 1000000000000000);
    const filename = `project_comment_file${uniqueId}${path.extname(file.originalname)}`;
    cb(null, filename);
  },
});

const upload = multer({
  storage: storage,
  limits: {
    fileSize: 10 * 1024 * 1024, // 10MB limit
  },
  fileFilter: function (req, file, cb) {
    // Allow images only
    if (file.mimetype.startsWith("image/")) {
      cb(null, true);
    } else {
      cb(new Error("Only image files are allowed!"), false);
    }
  },
});

module.exports = upload;