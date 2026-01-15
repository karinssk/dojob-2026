const express = require("express");
const router = express.Router();
const { pool } = require("../config/database");
const { addActivityLog, logActivity } = require("../utils/activityLogger");
const { getCurrentUserId } = require("../utils/sessionManager");
const upload = require("../config/multer");

// Get comments for a task
router.get("/:id/comments", async (req, res) => {
  try {
    const { id } = req.params;

    const query = `
      SELECT c.*, u.first_name, u.last_name, u.image as user_image
      FROM rise_project_comments c
      LEFT JOIN rise_users u ON c.created_by = u.id
      WHERE c.project_id = (SELECT project_id FROM rise_tasks WHERE id = ?) 
      AND c.task_id = ? AND c.deleted = 0
      ORDER BY c.created_at ASC
    `;

    const [rows] = await pool.execute(query, [id, id]);

    const comments = rows.map((comment) => {
      // Parse PHP serialized files data
      let images = [];
      if (comment.files && comment.files !== "a:0:{}") {
        try {
          // Simple PHP serialized array parser for files
          const filesStr = comment.files;
          console.log("🔍 Parsing comment files:", filesStr);
          if (filesStr.includes("file_name")) {
            // Parse PHP serialized format more carefully
            // Format: a:1:{i:0;a:4:{s:9:"file_name";s:X:"filename";s:13:"original_name";s:Y:"original";s:9:"file_size";s:Z:"size";s:7:"file_id";N;}}

            // Extract file_name values
            const fileNameMatches = filesStr.match(
              /s:9:"file_name";s:\d+:"([^"]+)"/g
            );
            const originalNameMatches = filesStr.match(
              /s:13:"original_name";s:\d+:"([^"]+)"/g
            );

            if (fileNameMatches) {
              fileNameMatches.forEach((match, index) => {
                const fileName = match.match(
                  /s:9:"file_name";s:\d+:"([^"]+)"/
                )[1];
                let originalName = fileName;

                if (originalNameMatches && originalNameMatches[index]) {
                  originalName = originalNameMatches[index].match(
                    /s:13:"original_name";s:\d+:"([^"]+)"/
                  )[1];
                }

                const imageObj = {
                  filename: fileName,
                  originalname: originalName,
                  url: `https://dojob.rubyshop.co.th/files/timeline_files/${fileName}`,
                };
                 
                console.log("📷 Extracted image:-------------------------------------", imageObj);
                images.push(imageObj);
              });
            }
          }
        } catch (e) {
          console.error("Error parsing comment files:", e);
        }
      }

      return {
        ...comment,
        author_name:
          `${comment.first_name || ""} ${comment.last_name || ""}`.trim() ||
          "Unknown User",
        images: images,
      };
    });

    res.json({ success: true, data: comments });
  } catch (error) {
    console.error("Error fetching comments:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Add comment to task (with image support)
router.post("/:id/comments", upload.array("images", 10), async (req, res) => {
  console.log("🎯 Comment endpoint hit:", req.method, req.url);
  try {
    const { id } = req.params;
    const { description, user_id, user_name } = req.body;
    
    // Get user ID from multiple sources - prioritize X-User-ID header, then body, then session
    let createdBy = req.headers['x-user-id'] || user_id;
    
    if (!createdBy) {
      // Fallback to session if no user ID provided
      createdBy = await getCurrentUserId(req);
    }
    
    if (!createdBy) {
      return res.status(401).json({
        success: false,
        error: "Authentication required. User ID must be provided."
      });
    }
    
    console.log("👤 Using user ID:", createdBy, "from header:", req.headers['x-user-id'], "from body:", user_id);
    
    const uploadedFiles = req.files || [];

    console.log("📝 Comment request:", {
      taskId: id,
      description: description,
      descriptionLength: description ? description.length : 0,
      uploadedFilesCount: uploadedFiles.length,
      createdBy: createdBy,
      userIdFromHeader: req.headers['x-user-id'],
      userIdFromBody: user_id,
      userNameFromBody: user_name,
      files: uploadedFiles.map((f) => ({
        name: f.originalname,
        size: f.size,
      })),
    });

    // Allow empty description if there are images
    const hasDescription = description && description.trim().length > 0;
    const hasImages = uploadedFiles && uploadedFiles.length > 0;

    if (!hasDescription && !hasImages) {
      return res.status(400).json({
        success: false,
        error: "Comment description or images are required",
      });
    }

    // Get task project_id and title
    const [taskRows] = await pool.execute(
      "SELECT project_id, title FROM rise_tasks WHERE id = ? AND deleted = 0",
      [id]
    );

    if (taskRows.length === 0) {
      return res
        .status(404)
        .json({ success: false, error: "Task not found" });
    }

    const { project_id: projectId, title: taskTitle } = taskRows[0];

    // Process uploaded images
    let filesData = [];
    if (uploadedFiles.length > 0) {
      filesData = uploadedFiles.map((file) => ({
        file_name: file.filename,
        original_name: file.originalname,
        file_size: file.size.toString(),
        file_id: null,
        service_type: null,
      }));
    }

    // Convert to PHP serialized format (for compatibility with existing system)
    const serializedFiles =
      filesData.length > 0
        ? "a:" +
          filesData.length +
          ":{" +
          filesData
            .map(
              (file, index) =>
                `i:${index};a:4:{s:9:"file_name";s:${file.file_name.length}:"${file.file_name}";s:13:"original_name";s:${file.original_name.length}:"${file.original_name}";s:9:"file_size";s:${file.file_size.length}:"${file.file_size}";s:7:"file_id";N;}`
            )
            .join("") +
          "}"
        : "a:0:{}";

    console.log("💾 Serialized files data:", serializedFiles);

    const query = `
      INSERT INTO rise_project_comments (
        project_id, task_id, description, created_by, created_at, files
      ) VALUES (?, ?, ?, ?, NOW(), ?)
    `;

    const [result] = await pool.execute(query, [
      projectId,
      id,
      description ? description.trim() : "",
      createdBy,
      serializedFiles,
    ]);

    // Log activity with current user
    await logActivity(
      req,
      "created",
      "comment",
      `Comment on: ${taskTitle}`,
      result.insertId,
      {
        comment: description ? description.trim() : "",
        task_id: id,
        images_count: uploadedFiles.length,
      },
      "task",
      id
    );

    res.json({
      success: true,
      data: {
        id: result.insertId,
        message: `Comment added successfully${
          uploadedFiles.length > 0
            ? ` with ${uploadedFiles.length} image(s)`
            : ""
        }`,
      },
    });
  } catch (error) {
    console.error("Error adding comment:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

module.exports = router;