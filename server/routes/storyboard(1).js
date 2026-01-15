const express = require("express");
const router = express.Router();
const { pool } = require("../config/database");
const path = require("path");
const fs = require("fs").promises;
const { createCanvas, loadImage, registerFont } = require('canvas');

// Simple regex-based PHP serialized data parser
function parseSerializedData(str) {
  if (!str) return null;
  
  // Handle simple array format like: a:1:{s:9:"file_name";s:29:"68d7c442e6652_S__27172875.jpg";}
  const fileNameMatch = str.match(/s:\d+:"file_name";s:\d+:"([^"]+)"/);
  if (fileNameMatch) {
    return { file_name: fileNameMatch[1] };
  }
  
  // Handle array with multiple fields
  const result = {};
  
  // Extract all key-value pairs
  const pairs = str.match(/s:\d+:"([^"]+)";s:\d+:"([^"]+)"/g);
  if (pairs) {
    pairs.forEach(pair => {
      const match = pair.match(/s:\d+:"([^"]+)";s:\d+:"([^"]+)"/);
      if (match) {
        result[match[1]] = match[2];
      }
    });
  }
  
  // Extract integer values
  const intPairs = str.match(/s:\d+:"([^"]+)";i:(\d+)/g);
  if (intPairs) {
    intPairs.forEach(pair => {
      const match = pair.match(/s:\d+:"([^"]+)";i:(\d+)/);
      if (match) {
        result[match[1]] = parseInt(match[2]);
      }
    });
  }
  
  return Object.keys(result).length > 0 ? result : null;
}

// Helper function to wrap text and draw it
function drawWrappedText(ctx, text, x, y, maxWidth, maxLines = 2, lineHeight = 22) {
  const words = text.split(' ');
  let line = '';
  let currentY = y;
  let lineCount = 0;
  
  for (let n = 0; n < words.length; n++) {
    const testLine = line + words[n] + ' ';
    const metrics = ctx.measureText(testLine);
    const testWidth = metrics.width;
    
    if (testWidth > maxWidth && n > 0) {
      ctx.fillText(line, x, currentY);
      line = words[n] + ' ';
      currentY += lineHeight;
      lineCount++;
      // Limit to maxLines to fit in space
      if (lineCount >= maxLines) {
        // Add "..." to indicate more text if we're truncating
        if (n < words.length - 1) {
          line = line.trim() + '...';
        }
        break;
      }
    } else {
      line = testLine;
    }
  }
  ctx.fillText(line, x, currentY);
  return currentY + lineHeight; // Return next Y position
}

console.log("📋 Storyboard routes loaded successfully");

// Get storyboard data for export

router.get("/test", async (req,res) => {
   console.log('test ok')
   res.send(200)
})




router.post("/export-data", async (req, res) => {
  console.log("📋 === STORYBOARD EXPORT-DATA ENDPOINT HIT ===");
  console.log("📋 Method:", req.method);
  console.log("📋 URL:", req.url);
  console.log("📋 Origin:", req.headers.origin);
  console.log("📋 Content-Type:", req.headers['content-type']);
  console.log("📋 Request body:", req.body);
  
  try {
    
    const { project_id, sub_project_id } = req.body;

    if (!project_id) {
      return res.status(400).json({
        success: false,
        message: "Project ID is required",
      });
    }

    console.log(
      "📋 Fetching storyboard data for project:",
      project_id,
      "sub-project:",
      sub_project_id
    );

    // Get project info (only storyboard projects)
    console.log("🔍 Looking for storyboard project with ID:", project_id);
    const [projectRows] = await pool.execute(
      "SELECT * FROM rise_projects WHERE id = ? AND deleted = 0 AND is_storyboard = 1",
      [project_id]
    );
    console.log("🔍 Found projects:", projectRows.length, projectRows);

    let project = null;
    if (projectRows.length === 0) {
      // Try without deleted filter to see if project exists but is deleted
      const [allProjectRows] = await pool.execute(
        "SELECT * FROM rise_projects WHERE id = ?",
        [project_id]
      );
      console.log("🔍 Project exists but deleted?:", allProjectRows.length, allProjectRows);
      
      // Check if there are storyboards for this project even if project doesn't exist
      const [storyboardCheck] = await pool.execute(
        "SELECT COUNT(*) as count FROM rise_storyboards WHERE project_id = ? AND deleted = 0",
        [project_id]
      );
      
      if (storyboardCheck[0].count > 0) {
        console.log("🔍 Found storyboards for non-existent project, creating virtual project");
        // Create a virtual project for orphaned storyboards
        project = {
          id: project_id,
          title: `Project ${project_id} (Orphaned)`,
          description: "This project has storyboards but no project record"
        };
      } else {
        return res.status(404).json({
          success: false,
          message: "Storyboard project not found",
          debug: {
            searched_id: project_id,
            found_projects: projectRows.length,
            exists_but_deleted: allProjectRows.length > 0,
            has_storyboards: false
          }
        });
      }
    } else {
      project = projectRows[0];
    }

    // Build query conditions
    let storyboardQuery = `
      SELECT s.*, 
             sh.header as scene_heading_title,
             sh.description as scene_heading_description,
             sh.id as scene_heading_id
      FROM rise_storyboards s
      LEFT JOIN rise_scene_headings sh ON s.scene_heading_id = sh.id
      WHERE s.project_id = ? AND s.deleted = 0
    `;
    let queryParams = [project_id];

    if (sub_project_id) {
      storyboardQuery += " AND s.sub_storyboard_project_id = ?";
      queryParams.push(sub_project_id);
    }

    storyboardQuery += " ORDER BY sh.sort_order ASC, s.shot ASC";

    // Get storyboards
    const [storyboardRows] = await pool.execute(storyboardQuery, queryParams);

    // Get scene headings
    let headingQuery = `
      SELECT * FROM rise_scene_headings 
      WHERE project_id = ? AND deleted = 0
    `;
    let headingParams = [project_id];

    if (sub_project_id) {
      headingQuery += " AND sub_storyboard_project_id = ?";
      headingParams.push(sub_project_id);
    }

    headingQuery += " ORDER BY sort_order ASC";

    const [headingRows] = await pool.execute(headingQuery, headingParams);

    // Organize data by scene headings
    const organizedData = [];
    const headingsMap = new Map();

    // Create map of headings
    headingRows.forEach((heading) => {
      headingsMap.set(heading.id, {
        id: heading.id,
        title: heading.header, // Use 'header' field from database
        description: heading.description,
        storyboards: [],
      });
    });

    // Add unorganized section for storyboards without headings
    const unorganizedSection = {
      id: null,
      title: "Unorganized Scenes",
      description: "Scenes without a specific heading",
      storyboards: [],
    };

    // Organize storyboards by headings
    storyboardRows.forEach((storyboard) => {
      console.log(`🔍 DEBUG: Processing storyboard ${storyboard.id}, frame data:`, {
        has_frame: !!storyboard.frame,
        frame_length: storyboard.frame ? storyboard.frame.length : 0,
        frame_preview: storyboard.frame ? storyboard.frame.substring(0, 100) : null
      });
      
      // Process frame data (PHP serialized)
      if (storyboard.frame) {
        try {
          // Try regex parser first
          const frameData = parseSerializedData(storyboard.frame);
          storyboard.frame_data = frameData;
          if (frameData && frameData.file_name) {
            storyboard.frame_url = `/files/storyboard_frames/${frameData.file_name}`;
          }
          console.log(`🖼️ SUCCESS: Regex parser worked for storyboard ${storyboard.id}:`, frameData);
        } catch (e) {
          console.log(`⚠️ Regex parser failed for storyboard ${storyboard.id}, trying JSON...`);
          // Fallback to JSON parse
          try {
            const frameData = JSON.parse(storyboard.frame);
            storyboard.frame_data = frameData;
            if (frameData.file_name) {
              storyboard.frame_url = `/files/storyboard_frames/${frameData.file_name}`;
            }
            console.log(`🖼️ SUCCESS: JSON parse worked for storyboard ${storyboard.id}:`, frameData);
          } catch (e2) {
            console.warn(
              `❌ FAILED: Both regex parser and JSON parse failed for storyboard ${storyboard.id}`,
              "Raw data:",
              storyboard.frame,
              "Regex error:", e.message,
              "JSON error:", e2.message
            );
            storyboard.frame_data = null;
          }
        }
      } else {
        console.log(`⚠️ No frame data for storyboard ${storyboard.id}`);
      }

      // Process raw footage data (PHP serialized) - more complex, often arrays
      if (storyboard.raw_footage) {
        try {
          // Raw footage is usually an array, try JSON first for arrays
          const footageData = JSON.parse(storyboard.raw_footage);
          storyboard.footage_data = footageData;
          if (Array.isArray(footageData)) {
            storyboard.footage_urls = footageData.map((file) => ({
              ...file,
              url: `/files/storyboard_footage/${file.file_name}`,
            }));
          }
        } catch (e) {
          // Fallback to regex parser for PHP serialized arrays
          try {
            const footageData = parseSerializedData(storyboard.raw_footage);
            storyboard.footage_data = footageData;
            if (footageData && footageData.file_name) {
              storyboard.footage_urls = [{
                ...footageData,
                url: `/files/storyboard_footage/${footageData.file_name}`,
              }];
            }
          } catch (e2) {
            console.warn(
              "Failed to parse footage data for storyboard",
              storyboard.id
            );
            storyboard.footage_data = null;
          }
        }
      }

      // Clean up null values
      Object.keys(storyboard).forEach((key) => {
        if (storyboard[key] === null || storyboard[key] === undefined) {
          storyboard[key] = "";
        }
      });

      // Add to appropriate section
      if (
        storyboard.scene_heading_id &&
        headingsMap.has(storyboard.scene_heading_id)
      ) {
        headingsMap
          .get(storyboard.scene_heading_id)
          .storyboards.push(storyboard);
      } else {
        unorganizedSection.storyboards.push(storyboard);
      }
    });

    // Build final organized data array
    headingRows.forEach((heading) => {
      const section = headingsMap.get(heading.id);
      if (section && section.storyboards.length > 0) {
        organizedData.push(section);
      }
    });

    // Add unorganized section if it has storyboards
    if (unorganizedSection.storyboards.length > 0) {
      organizedData.push(unorganizedSection);
    }

    // Calculate statistics
    const totalScenes = storyboardRows.length;
    const totalHeadings = headingRows.length;
    const scenesWithImages = storyboardRows.filter((s) => s.frame).length;
    const scenesWithFootage = storyboardRows.filter(
      (s) => s.raw_footage
    ).length;

    const statistics = {
      total_scenes: totalScenes,
      total_headings: totalHeadings,
      scenes_with_images: scenesWithImages,
      scenes_with_footage: scenesWithFootage,
      completion_percentage:
        totalScenes > 0
          ? Math.round((scenesWithImages / totalScenes) * 100)
          : 0,
    };

    console.log("✅ Storyboard data fetched successfully:", {
      project: project.title,
      sections: organizedData.length,
      totalScenes,
      totalHeadings,
    });

    res.json({
      success: true,
      data: organizedData,
      project: {
        id: project.id,
        title: project.title,
        description: project.description,
      },
      statistics,
    });
  } catch (error) {
    console.error("❌ Error fetching storyboard data:", error);
    res.status(500).json({
      success: false,
      message: "Failed to fetch storyboard data",
      error: error.message,
    });
  }
});

// Export storyboard to various formats
router.post("/export", async (req, res) => {
  try {
    const {
      project_id,
      sub_project_id,
      selected_headings = [],
      selected_scenes = [],
      export_format = "json",
      include_images = true,
      include_descriptions = true,
      include_notes = true,
      include_camera_info = true,
    } = req.body;

    if (!project_id) {
      return res.status(400).json({
        success: false,
        message: "Project ID is required",
      });
    }

    console.log("📤 Exporting storyboard:", {
      project_id,
      sub_project_id,
      format: export_format,
      selected_scenes: selected_scenes.length,
      selected_headings: selected_headings.length,
    });

    // Get project info (only storyboard projects)
    const [projectRows] = await pool.execute(
      "SELECT * FROM rise_projects WHERE id = ? AND deleted = 0 AND is_storyboard = 1",
      [project_id]
    );

    if (projectRows.length === 0) {
      return res.status(404).json({
        success: false,
        message: "Storyboard project not found",
      });
    }

    const project = projectRows[0];

    // Build query for selected scenes
    let storyboardQuery = `
      SELECT s.*, 
             sh.header as scene_heading_title,
             sh.description as scene_heading_description,
             sh.id as scene_heading_id
      FROM rise_storyboards s
      LEFT JOIN rise_scene_headings sh ON s.scene_heading_id = sh.id
      WHERE s.project_id = ? AND s.deleted = 0
    `;
    let queryParams = [project_id];

    if (sub_project_id) {
      storyboardQuery += " AND s.sub_storyboard_project_id = ?";
      queryParams.push(sub_project_id);
    }

    // Filter by selected scenes if provided
    if (selected_scenes.length > 0) {
      const placeholders = selected_scenes.map(() => "?").join(",");
      storyboardQuery += ` AND s.id IN (${placeholders})`;
      queryParams.push(...selected_scenes);
    }

    // Filter by selected headings if provided
    if (selected_headings.length > 0) {
      const placeholders = selected_headings.map(() => "?").join(",");
      storyboardQuery += ` AND (s.scene_heading_id IN (${placeholders}) OR s.scene_heading_id IS NULL)`;
      queryParams.push(...selected_headings);
    }

    storyboardQuery += " ORDER BY sh.sort_order ASC, s.shot ASC";

    const [storyboardRows] = await pool.execute(storyboardQuery, queryParams);

    // Process and filter data based on export options
    const exportData = {
      project: {
        id: project.id,
        title: project.title,
        description: project.description,
        exported_at: new Date().toISOString(),
        export_options: {
          include_images,
          include_descriptions,
          include_notes,
          include_camera_info,
        },
      },
      scenes: [],
    };

    storyboardRows.forEach((storyboard) => {
      const sceneData = {
        id: storyboard.id,
        shot: storyboard.shot,
        scene_heading: {
          id: storyboard.scene_heading_id,
          title: storyboard.scene_heading_title,
          description: storyboard.scene_heading_description,
        },
      };

      // Add content based on export options
      if (include_descriptions) {
        sceneData.content = storyboard.content || "";
        sceneData.dialogues = storyboard.dialogues || "";
      }

      if (include_notes) {
        sceneData.note = storyboard.note || "";
      }

      if (include_camera_info) {
        sceneData.camera_info = {
          shot_size: storyboard.shot_size || "",
          shot_type: storyboard.shot_type || "",
          movement: storyboard.movement || "",
          duration: storyboard.duration || "",
          framerate: storyboard.framerate || "",
          lighting: storyboard.lighting || "",
          equipment: storyboard.equipment || "",
          sound: storyboard.sound || "",
        };
      }

      if (include_images && storyboard.frame) {
        try {
          const frameData = JSON.parse(storyboard.frame);
          sceneData.frame = {
            file_name: frameData.file_name,
            original_name: frameData.original_name,
            url: `/files/storyboard_frames/${frameData.file_name}`,
          };
        } catch (e) {
          console.warn(
            "Failed to parse frame data for storyboard",
            storyboard.id
          );
        }
      }

      // Add footage data if available
      if (storyboard.raw_footage) {
        try {
          const footageData = JSON.parse(storyboard.raw_footage);
          if (Array.isArray(footageData)) {
            sceneData.footage = footageData.map((file) => ({
              file_name: file.file_name,
              original_name: file.original_name,
              url: `/files/storyboard_footage/${file.file_name}`,
            }));
          }
        } catch (e) {
          console.warn(
            "Failed to parse footage data for storyboard",
            storyboard.id
          );
        }
      }

      sceneData.status = storyboard.story_status || "Draft";
      sceneData.created_at = storyboard.created_at;
      sceneData.updated_at = storyboard.updated_at;

      exportData.scenes.push(sceneData);
    });

    // Handle different export formats
    switch (export_format.toLowerCase()) {
      case "json":
        res.setHeader("Content-Type", "application/json");
        res.setHeader(
          "Content-Disposition",
          `attachment; filename="storyboard_${project.title.replace(
            /[^a-zA-Z0-9]/g,
            "_"
          )}_${Date.now()}.json"`
        );
        res.json(exportData);
        break;

      case "csv":
        const csv = convertToCSV(exportData);
        res.setHeader("Content-Type", "text/csv");
        res.setHeader(
          "Content-Disposition",
          `attachment; filename="storyboard_${project.title.replace(
            /[^a-zA-Z0-9]/g,
            "_"
          )}_${Date.now()}.csv"`
        );
        res.send(csv);
        break;

      default:
        res.json(exportData);
    }

    console.log("✅ Storyboard exported successfully:", {
      project: project.title,
      scenes: exportData.scenes.length,
      format: export_format,
    });
  } catch (error) {
    console.error("❌ Error exporting storyboard:", error);
    res.status(500).json({
      success: false,
      message: "Failed to export storyboard",
      error: error.message,
    });
  }
});

// Export storyboard as PNG image with 100% exact image sizes
router.post("/export-png", async (req, res) => {
  try {
    const {
      project_id,
      sub_project_id,
      selected_scenes = [],
      scene_heading_title = "SCENE 1",
      preserve_image_size = true  // New option to preserve exact image sizes
    } = req.body;

    if (!project_id) {
      return res.status(400).json({
        success: false,
        message: "Project ID is required",
      });
    }

    console.log("🖼️ Exporting storyboard as PNG:", {
      project_id,
      sub_project_id,
      selected_scenes: selected_scenes.length,
    });

    // Get project info (only storyboard projects)
    const [projectRows] = await pool.execute(
      "SELECT * FROM rise_projects WHERE id = ? AND deleted = 0 AND is_storyboard = 1",
      [project_id]
    );

    if (projectRows.length === 0) {
      return res.status(404).json({
        success: false,
        message: "Storyboard project not found",
      });
    }

    const project = projectRows[0];

    // Build query for selected scenes
    let storyboardQuery = `
      SELECT s.*, 
             sh.header as scene_heading_title,
             sh.description as scene_heading_description,
             sh.id as scene_heading_id
      FROM rise_storyboards s
      LEFT JOIN rise_scene_headings sh ON s.scene_heading_id = sh.id
      WHERE s.project_id = ? AND s.deleted = 0
    `;
    let queryParams = [project_id];

    if (sub_project_id) {
      storyboardQuery += " AND s.sub_storyboard_project_id = ?";
      queryParams.push(sub_project_id);
    }

    // Filter by selected scenes if provided
    if (selected_scenes.length > 0) {
      const placeholders = selected_scenes.map(() => "?").join(",");
      storyboardQuery += ` AND s.id IN (${placeholders})`;
      queryParams.push(...selected_scenes);
    }

    storyboardQuery += " ORDER BY sh.sort_order ASC, s.shot ASC";

    const [storyboardRows] = await pool.execute(storyboardQuery, queryParams);

    if (storyboardRows.length === 0) {
      return res.status(404).json({
        success: false,
        message: "No storyboard scenes found",
      });
    }

    // Pre-scan images to determine optimal canvas size if preserving exact sizes
    let maxImageWidth = 0;
    let maxImageHeight = 0;
    
    if (preserve_image_size) {
      console.log("🔍 Pre-scanning images to determine optimal canvas size...");
      
      for (const storyboard of storyboardRows) {
        let frameData = null;
        if (storyboard.frame) {
          frameData = parseSerializedData(storyboard.frame);
        }
        
        if (frameData && frameData.file_name) {
          try {
            const imagePath = path.join(__dirname, '../../files/storyboard_frames', frameData.file_name);
            const image = await loadImage(imagePath);
            maxImageWidth = Math.max(maxImageWidth, image.width);
            maxImageHeight = Math.max(maxImageHeight, image.height);
            console.log(`📐 Image ${frameData.file_name}: ${image.width}x${image.height}`);
          } catch (err) {
            console.warn(`Could not pre-scan image: ${frameData.file_name}`);
          }
        }
      }
      
      console.log(`📐 Maximum image dimensions found: ${maxImageWidth}x${maxImageHeight}`);
    }

    // Calculate canvas size based on image requirements
    let canvasWidth, canvasHeight, sceneWidth, sceneHeight;
    
    if (preserve_image_size && maxImageWidth > 0 && maxImageHeight > 0) {
      // Dynamic canvas size based on actual image dimensions
      const cols = 3;
      const rows = Math.ceil(storyboardRows.length / cols);
      const margin = 100;
      const gapX = 80;
      const gapY = 100;
      const headerHeight = 350;
      const textAreaHeight = 300; // Space for text below images
      
      sceneWidth = Math.max(700, maxImageWidth + 20); // Increased card width: image width + minimal padding
      sceneHeight = headerHeight + maxImageHeight + textAreaHeight + 30; // Header + image + text + padding
      
      canvasWidth = margin * 2 + (sceneWidth * cols) + (gapX * (cols - 1));
      canvasHeight = margin * 2 + (sceneHeight * rows) + (gapY * (rows - 1));
      
      console.log(`📐 Dynamic canvas size: ${canvasWidth}x${canvasHeight}`);
      console.log(`📐 Scene size: ${sceneWidth}x${sceneHeight}`);
    } else {
      // Standard A4 size
      canvasWidth = 2480;
      canvasHeight = 3508;
      sceneWidth = 700;
      sceneHeight = 1000;
      console.log(`📐 Using standard A4 canvas size: ${canvasWidth}x${canvasHeight}`);
    }

    // Check if canvas size is too large (over 32767 pixels - canvas limit)
    const MAX_CANVAS_DIMENSION = 32767;
    if (canvasWidth > MAX_CANVAS_DIMENSION || canvasHeight > MAX_CANVAS_DIMENSION) {
      console.error(`❌ Canvas size too large: ${canvasWidth}x${canvasHeight}`);
      return res.status(400).json({
        success: false,
        message: `Canvas size too large (${canvasWidth}x${canvasHeight}). Maximum dimension is ${MAX_CANVAS_DIMENSION}px. Please export fewer scenes or use a different format.`,
        canvas_size: { width: canvasWidth, height: canvasHeight },
        max_dimension: MAX_CANVAS_DIMENSION,
        total_scenes: storyboardRows.length
      });
    }

    // Create PNG image with calculated dimensions
    const canvas = createCanvas(canvasWidth, canvasHeight);
    const ctx = canvas.getContext('2d');

    // Fill background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Header
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 60px Arial';
    ctx.textAlign = 'left';
    ctx.fillText(`STORYBOARD`, 100, 150);
    
    ctx.font = 'bold 48px Arial';
    ctx.fillText(project.title.toUpperCase(), 100, 220);
    
    ctx.font = '36px Arial';
    ctx.fillText(scene_heading_title.toUpperCase(), 100, 280);
    
    // Location (top right)
    ctx.textAlign = 'right';
    ctx.fillText('LOCATION: RUBYSHOP STORE', canvas.width - 100, 220);

    // Draw scenes in a grid (3 columns, dynamic rows)
    const cols = 3;
    const rows = Math.ceil(storyboardRows.length / cols);
    const startX = 100;
    const startY = 400;
    const gapX = preserve_image_size ? 80 : 120;
    const gapY = preserve_image_size ? 100 : 150;

    // Export ALL scenes (removed the 6 scene limit)
    for (let i = 0; i < storyboardRows.length; i++) {
      const storyboard = storyboardRows[i];
      const col = i % cols;
      const row = Math.floor(i / cols);
      
      const x = startX + col * (sceneWidth + gapX);
      const y = startY + row * (sceneHeight + gapY);

      // Process frame data
      let frameData = null;
      if (storyboard.frame) {
        frameData = parseSerializedData(storyboard.frame);
      }

      // Shot header
      ctx.fillStyle = '#E3F2FD';
      ctx.fillRect(x, y, sceneWidth, 80);
      
      ctx.fillStyle = '#1976D2';
      ctx.font = 'bold 32px Arial';
      ctx.textAlign = 'left';
      ctx.fillText(`SHOT ${storyboard.shot}`, x + 20, y + 50);
      
      ctx.textAlign = 'right';
      ctx.fillText('HEADING:', x + sceneWidth - 20, y + 50);

      // Image area - dynamic height based on preserve_image_size option
      const imageY = y + 80;
      let imageHeight = preserve_image_size ? (maxImageHeight + 40) : 400;
      
      ctx.fillStyle = '#f5f5f5';
      ctx.fillRect(x, imageY, sceneWidth, imageHeight);
      
      // Try to load and draw the actual image at 100% EXACT size
      if (frameData && frameData.file_name) {
        try {
          const imagePath = path.join(__dirname, '../../files/storyboard_frames', frameData.file_name);
          const image = await loadImage(imagePath);
          
          console.log(`🖼️ Loading image for shot ${storyboard.shot}: ${frameData.file_name}`);
          console.log(`📐 Original image size: ${image.width}x${image.height}`);
          console.log(`📐 Available space: ${sceneWidth}x${imageHeight}`);
          
          // FIXED: Scale image to fit within available space WITHOUT cropping
          // Calculate aspect ratio preserving scale with MINIMAL padding
          const horizontalPadding = 5; // Minimal padding: just 5px per side
          const verticalPadding = 15; // Reduced vertical padding too
          const availableWidth = sceneWidth - (horizontalPadding * 2);
          const availableHeight = imageHeight - (verticalPadding * 2);
          const imageAspect = image.width / image.height;
          const spaceAspect = availableWidth / availableHeight;
          
          let drawWidth, drawHeight;
          
          if (imageAspect > spaceAspect) {
            // Image is wider - fit to width
            drawWidth = availableWidth;
            drawHeight = availableWidth / imageAspect;
          } else {
            // Image is taller - fit to height
            drawHeight = availableHeight;
            drawWidth = availableHeight * imageAspect;
          }
          
          // Center the scaled image in the available space
          const drawX = x + (sceneWidth - drawWidth) / 2;
          const drawY = imageY + (imageHeight - drawHeight) / 2;
          
          console.log(`📐 Scaled to fit: ${Math.round(drawWidth)}x${Math.round(drawHeight)} at (${Math.round(drawX)}, ${Math.round(drawY)})`);
          console.log(`📐 No cropping - full image visible`);
          
          // Draw the ENTIRE image scaled to fit (no cropping)
          ctx.drawImage(
            image, 
            0, 0, image.width, image.height,              // Source: entire image
            drawX, drawY, drawWidth, drawHeight           // Destination: scaled size
          );
          
          // Add a subtle border around the image to show its exact boundaries
          ctx.strokeStyle = '#e0e0e0';
          ctx.lineWidth = 1;
          ctx.strokeRect(drawX, drawY, drawWidth, drawHeight);
          
        } catch (err) {
          console.warn(`Could not load image for storyboard ${storyboard.id}:`, err.message);
          // Draw placeholder
          ctx.fillStyle = '#f0f0f0';
          ctx.fillRect(x + 20, imageY + 20, sceneWidth - 40, imageHeight - 40);
          ctx.fillStyle = '#999999';
          ctx.font = '24px Arial';
          ctx.textAlign = 'center';
          ctx.fillText('Image Not Found', x + sceneWidth/2, imageY + imageHeight/2);
          ctx.font = '16px Arial';
          ctx.fillText(frameData.file_name, x + sceneWidth/2, imageY + imageHeight/2 + 30);
        }
      } else {
        // Draw placeholder for missing image
        ctx.fillStyle = '#f8f8f8';
        ctx.fillRect(x + 20, imageY + 20, sceneWidth - 40, imageHeight - 40);
        
        // Dashed border for placeholder
        ctx.setLineDash([10, 5]);
        ctx.strokeStyle = '#cccccc';
        ctx.lineWidth = 2;
        ctx.strokeRect(x + 20, imageY + 20, sceneWidth - 40, imageHeight - 40);
        ctx.setLineDash([]); // Reset line dash
        
        ctx.fillStyle = '#999999';
        ctx.font = '24px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No Image', x + sceneWidth/2, imageY + imageHeight/2);
      }

      // Content section
      const contentY = imageY + imageHeight;
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(x, contentY, sceneWidth, 400);
      
      // Draw content fields
      ctx.fillStyle = '#000000';
      ctx.font = 'bold 20px Arial';
      ctx.textAlign = 'left';
      
      let fieldY = contentY + 30;
      
      // Content
      ctx.fillText('CONTENT:', x + 20, fieldY);
      ctx.font = '18px Arial';
      
      const content = storyboard.content || 'No content';
      drawWrappedText(ctx, content, x + 20, fieldY + 25, sceneWidth - 40, 2, 22);
      
      fieldY += 70;
      
      // Description (show dialogues) - check if text is long
      ctx.font = 'bold 20px Arial';
      ctx.fillText('DESCRIPTION:', x + 20, fieldY);
      ctx.font = '18px Arial';
      
      const dialogues = storyboard.dialogues || 'No dialogue';
      const isLongDescription = dialogues.length > 80; // Consider long if more than 80 characters
      
      if (isLongDescription) {
        // For long descriptions, give more space and move technical details to bottom
        drawWrappedText(ctx, dialogues, x + 20, fieldY + 25, sceneWidth - 40, 4, 22);
        fieldY += 150; // More space for long text
        
        // Draw technical details at bottom in a compact horizontal layout
        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(x, fieldY, sceneWidth, 60);
        
        ctx.fillStyle = '#000000';
        ctx.font = 'bold 14px Arial';
        ctx.fillText('SIZE:', x + 20, fieldY + 20);
        ctx.font = '12px Arial';
        ctx.fillText(storyboard.shot_size || 'Full shot', x + 20, fieldY + 35);
        
        ctx.font = 'bold 14px Arial';
        ctx.fillText('TYPE:', x + 150, fieldY + 20);
        ctx.font = '12px Arial';
        ctx.fillText(storyboard.shot_type || 'Eye Level', x + 150, fieldY + 35);
        
        ctx.font = 'bold 14px Arial';
        ctx.fillText('MOVEMENT:', x + 300, fieldY + 20);
        ctx.font = '12px Arial';
        ctx.fillText(storyboard.movement || 'static', x + 300, fieldY + 35);
        
        ctx.font = 'bold 14px Arial';
        ctx.fillText('DURATION:', x + 500, fieldY + 20);
        ctx.font = '12px Arial';
        ctx.fillText(`${storyboard.duration || '15'} s`, x + 500, fieldY + 35);
        
      } else {
        // For short descriptions, use original layout
        drawWrappedText(ctx, dialogues, x + 20, fieldY + 25, sceneWidth - 40, 2, 22);
        
        fieldY += 70;
        
        // Shot details in columns (original layout)
        ctx.font = 'bold 18px Arial';
        ctx.fillText('SIZE TYPE:', x + 20, fieldY);
        ctx.font = '16px Arial';
        ctx.fillText(storyboard.shot_size || 'Full shot', x + 20, fieldY + 20);
        ctx.fillText(storyboard.shot_type || 'Eye Level', x + 20, fieldY + 40);
        
        // Movement and Duration
        ctx.font = 'bold 18px Arial';
        ctx.fillText('MOVEMENT:', x + 350, fieldY);
        ctx.font = '16px Arial';
        ctx.fillText(storyboard.movement || 'static', x + 350, fieldY + 20);
        
        ctx.font = 'bold 18px Arial';
        ctx.fillText('DURATION:', x + 350, fieldY + 45);
        ctx.font = '16px Arial';
        ctx.fillText(`${storyboard.duration || '15'} s`, x + 350, fieldY + 65);
      }

      // Border around each scene
      ctx.strokeStyle = '#cccccc';
      ctx.lineWidth = 2;
      ctx.strokeRect(x, y, sceneWidth, sceneHeight);
    }

    // Convert canvas to PNG buffer with error handling
    console.log(`🔄 Converting canvas to PNG buffer...`);
    let buffer;
    try {
      buffer = canvas.toBuffer('image/png', { compressionLevel: 6, filters: canvas.PNG_FILTER_NONE });
      console.log(`✅ Buffer created successfully: ${(buffer.length / 1024 / 1024).toFixed(2)} MB`);
    } catch (bufferError) {
      console.error(`❌ Error creating PNG buffer:`, bufferError);
      return res.status(500).json({
        success: false,
        message: "Failed to create PNG buffer. Image may be too large.",
        error: bufferError.message,
        canvas_size: { width: canvas.width, height: canvas.height }
      });
    }

    // Check buffer size (warn if over 50MB)
    const bufferSizeMB = buffer.length / 1024 / 1024;
    if (bufferSizeMB > 50) {
      console.warn(`⚠️ Large buffer size: ${bufferSizeMB.toFixed(2)} MB`);
    }

    // Set response headers
    res.setHeader('Content-Type', 'image/png');
    res.setHeader('Content-Disposition', `attachment; filename="storyboard_${project.title.replace(/[^a-zA-Z0-9]/g, '_')}_${Date.now()}.png"`);
    res.setHeader('Content-Length', buffer.length);

    // Send the PNG buffer
    res.end(buffer);

    console.log("✅ Storyboard PNG exported successfully:", {
      project: project.title,
      scenes: storyboardRows.length,
      size: `${canvas.width}x${canvas.height}`,
      buffer_size: `${bufferSizeMB.toFixed(2)} MB`
    });

  } catch (error) {
    console.error("❌ Error exporting storyboard PNG:", error);
    res.status(500).json({
      success: false,
      message: "Failed to export storyboard PNG",
      error: error.message,
    });
  }
});

// Export storyboard as PNG with EXACT 800x800px image sizes and simple layout
router.post("/export-png-exact", async (req, res) => {
  try {
    const {
      project_id,
      sub_project_id,
      selected_scenes = [],
      scene_heading_title = "SCENE 1"
    } = req.body;

    if (!project_id) {
      return res.status(400).json({
        success: false,
        message: "Project ID is required",
      });
    }

    console.log("🖼️ Exporting storyboard as 800x800px PNG with simple layout:", {
      project_id,
      sub_project_id,
      selected_scenes: selected_scenes.length,
    });

    // Get project and storyboard data (only storyboard projects)
    const [projectRows] = await pool.execute(
      "SELECT * FROM rise_projects WHERE id = ? AND deleted = 0 AND is_storyboard = 1",
      [project_id]
    );

    if (projectRows.length === 0) {
      return res.status(404).json({
        success: false,
        message: "Storyboard project not found",
      });
    }

    const project = projectRows[0];

    // Build query for selected scenes
    let storyboardQuery = `
      SELECT s.*, 
             sh.header as scene_heading_title,
             sh.description as scene_heading_description,
             sh.id as scene_heading_id
      FROM rise_storyboards s
      LEFT JOIN rise_scene_headings sh ON s.scene_heading_id = sh.id
      WHERE s.project_id = ? AND s.deleted = 0
    `;
    let queryParams = [project_id];

    if (sub_project_id) {
      storyboardQuery += " AND s.sub_storyboard_project_id = ?";
      queryParams.push(sub_project_id);
    }

    if (selected_scenes.length > 0) {
      const placeholders = selected_scenes.map(() => "?").join(",");
      storyboardQuery += ` AND s.id IN (${placeholders})`;
      queryParams.push(...selected_scenes);
    }

    storyboardQuery += " ORDER BY sh.sort_order ASC, s.shot ASC";
    const [storyboardRows] = await pool.execute(storyboardQuery, queryParams);

    if (storyboardRows.length === 0) {
      return res.status(404).json({
        success: false,
        message: "No storyboard scenes found",
      });
    }

    // Simple grid layout: 3 columns, 800x800px images with shot header
    const imageSize = 800; // Fixed 800x800px
    const headerHeight = 80; // Simple header with shot number
    const cardHeight = imageSize + headerHeight;
    const cardWidth = imageSize;
    const cols = 3;
    const rows = Math.ceil(storyboardRows.length / cols);
    const padding = 50;
    const gap = 40;
    
    const canvasWidth = padding * 2 + (cardWidth * cols) + (gap * (cols - 1));
    const canvasHeight = padding * 2 + (cardHeight * rows) + (gap * (rows - 1));
    
    console.log(`📐 Creating simple grid canvas: ${canvasWidth}x${canvasHeight}`);
    console.log(`📐 ${rows} rows x ${cols} columns, ${imageSize}x${imageSize}px images`);
    console.log(`📐 Total scenes to export: ${storyboardRows.length}`);
    
    // Check if canvas size is too large (over 32767 pixels - canvas limit)
    const MAX_CANVAS_DIMENSION = 32767;
    if (canvasWidth > MAX_CANVAS_DIMENSION || canvasHeight > MAX_CANVAS_DIMENSION) {
      console.error(`❌ Canvas size too large: ${canvasWidth}x${canvasHeight}`);
      return res.status(400).json({
        success: false,
        message: `Canvas size too large (${canvasWidth}x${canvasHeight}). Maximum dimension is ${MAX_CANVAS_DIMENSION}px. Please export fewer scenes or use a different format.`,
        canvas_size: { width: canvasWidth, height: canvasHeight },
        max_dimension: MAX_CANVAS_DIMENSION
      });
    }

    const canvas = createCanvas(canvasWidth, canvasHeight);
    const ctx = canvas.getContext('2d');

    // Fill background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Draw each scene in grid
    for (let i = 0; i < storyboardRows.length; i++) {
      const storyboard = storyboardRows[i];
      const col = i % cols;
      const row = Math.floor(i / cols);
      
      const x = padding + col * (cardWidth + gap);
      const y = padding + row * (cardHeight + gap);
      
      // Simple shot header
      ctx.fillStyle = '#E3F2FD';
      ctx.fillRect(x, y, cardWidth, headerHeight);
      
      ctx.fillStyle = '#1976D2';
      ctx.font = 'bold 40px Arial';
      ctx.textAlign = 'center';
      ctx.fillText(`Shot ${storyboard.shot}`, x + cardWidth / 2, y + 55);
      
      // Image area (800x800px)
      const imageY = y + headerHeight;
      
      // Load and draw image at exactly 800x800px
      let frameData = null;
      if (storyboard.frame) {
        frameData = parseSerializedData(storyboard.frame);
      }
      
      if (frameData && frameData.file_name) {
        try {
          const imagePath = path.join(__dirname, '../../files/storyboard_frames', frameData.file_name);
          const image = await loadImage(imagePath);
          
          console.log(`🖼️ Loading image for shot ${storyboard.shot}: ${frameData.file_name} (${image.width}x${image.height})`);
          
          // Scale image to fit 800x800px while maintaining aspect ratio
          const imageAspect = image.width / image.height;
          let drawWidth, drawHeight;
          
          if (imageAspect > 1) {
            // Wider image - fit to width
            drawWidth = imageSize;
            drawHeight = imageSize / imageAspect;
          } else {
            // Taller image - fit to height
            drawHeight = imageSize;
            drawWidth = imageSize * imageAspect;
          }
          
          // Center the image in the 800x800 space
          const drawX = x + (imageSize - drawWidth) / 2;
          const drawY = imageY + (imageSize - drawHeight) / 2;
          
          // Draw white background for the image area
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(x, imageY, imageSize, imageSize);
          
          // Draw the image
          ctx.drawImage(
            image,
            0, 0, image.width, image.height,
            drawX, drawY, drawWidth, drawHeight
          );
          
          console.log(`✅ Drew image at 800x800px: scaled to ${Math.round(drawWidth)}x${Math.round(drawHeight)}`);
          
        } catch (err) {
          console.warn(`Could not load image for shot ${storyboard.shot}:`, err.message);
          
          // Placeholder if image not found
          ctx.fillStyle = '#f5f5f5';
          ctx.fillRect(x, imageY, imageSize, imageSize);
          
          ctx.fillStyle = '#999999';
          ctx.font = '24px Arial';
          ctx.textAlign = 'center';
          ctx.fillText('Image Not Found', x + imageSize / 2, imageY + imageSize / 2);
        }
      } else {
        // No image - show placeholder
        ctx.fillStyle = '#f8f8f8';
        ctx.fillRect(x, imageY, imageSize, imageSize);
        
        ctx.setLineDash([10, 5]);
        ctx.strokeStyle = '#cccccc';
        ctx.lineWidth = 2;
        ctx.strokeRect(x, imageY, imageSize, imageSize);
        ctx.setLineDash([]);
        
        ctx.fillStyle = '#999999';
        ctx.font = '32px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No Image', x + imageSize / 2, imageY + imageSize / 2);
      }
      
      // Border around card
      ctx.strokeStyle = '#e0e0e0';
      ctx.lineWidth = 2;
      ctx.strokeRect(x, y, cardWidth, cardHeight);
    }

    // Convert canvas to PNG buffer with error handling
    console.log(`🔄 Converting canvas to PNG buffer...`);
    let buffer;
    try {
      buffer = canvas.toBuffer('image/png', { compressionLevel: 6, filters: canvas.PNG_FILTER_NONE });
      console.log(`✅ Buffer created successfully: ${(buffer.length / 1024 / 1024).toFixed(2)} MB`);
    } catch (bufferError) {
      console.error(`❌ Error creating PNG buffer:`, bufferError);
      return res.status(500).json({
        success: false,
        message: "Failed to create PNG buffer. Image may be too large.",
        error: bufferError.message,
        canvas_size: { width: canvasWidth, height: canvasHeight }
      });
    }

    // Check buffer size (warn if over 50MB)
    const bufferSizeMB = buffer.length / 1024 / 1024;
    if (bufferSizeMB > 50) {
      console.warn(`⚠️ Large buffer size: ${bufferSizeMB.toFixed(2)} MB`);
    }

    // Set response headers
    res.setHeader('Content-Type', 'image/png');
    res.setHeader('Content-Disposition', `attachment; filename="storyboard_800x800_${project.title.replace(/[^a-zA-Z0-9]/g, '_')}_${Date.now()}.png"`);
    res.setHeader('Content-Length', buffer.length);

    // Send the PNG buffer
    res.end(buffer);

    console.log("✅ Simple 800x800 storyboard PNG exported successfully:", {
      project: project.title,
      scenes: storyboardRows.length,
      canvas_size: `${canvasWidth}x${canvasHeight}`,
      buffer_size: `${bufferSizeMB.toFixed(2)} MB`,
      image_size: `${imageSize}x${imageSize}px`
    });

  } catch (error) {
    console.error("❌ Error exporting 800x800 storyboard PNG:", error);
    res.status(500).json({
      success: false,
      message: "Failed to export 800x800 storyboard PNG",
      error: error.message,
    });
  }
});

// Helper function to convert data to CSV
function convertToCSV(data) {
  const scenes = data.scenes;
  if (scenes.length === 0) return "";

  // Define CSV headers
  const headers = [
    "Shot",
    "Scene Heading",
    "Content",
    "Dialogues",
    "Shot Size",
    "Shot Type",
    "Movement",
    "Duration",
    "Framerate",
    "Lighting",
    "Equipment",
    "Sound",
    "Note",
    "Status",
    "Frame File",
    "Created At",
  ];

  // Convert scenes to CSV rows
  const rows = scenes.map((scene) => [
    scene.shot || "",
    scene.scene_heading?.title || "",
    (scene.content || "").replace(/"/g, '""'),
    (scene.dialogues || "").replace(/"/g, '""'),
    scene.camera_info?.shot_size || "",
    scene.camera_info?.shot_type || "",
    scene.camera_info?.movement || "",
    scene.camera_info?.duration || "",
    scene.camera_info?.framerate || "",
    scene.camera_info?.lighting || "",
    scene.camera_info?.equipment || "",
    scene.camera_info?.sound || "",
    (scene.note || "").replace(/"/g, '""'),
    scene.status || "",
    scene.frame?.file_name || "",
    scene.created_at || "",
  ]);

  // Combine headers and rows
  const csvContent = [headers, ...rows]
    .map((row) => row.map((field) => `"${field}"`).join(","))
    .join("\n");

  return csvContent;
}

// Test CORS endpoint
router.get("/test-cors", (req, res) => {
  console.log("🧪 CORS test request from:", req.headers.origin);
  res.json({
    success: true,
    message: "CORS is working!",
    origin: req.headers.origin,
    timestamp: new Date().toISOString()
  });
});

// Test regex parser
router.get("/test-parser", (req, res) => {
  console.log("🧪 Testing regex parser");
  
  const testData = 'a:1:{s:9:"file_name";s:29:"68d7c442e6652_S__27172875.jpg";}';
  
  try {
    const result = parseSerializedData(testData);
    console.log("✅ Regex parser SUCCESS:", result);
    
    res.json({
      success: true,
      message: "Regex parser is working!",
      test_data: testData,
      parsed_result: result,
      file_name: result ? result.file_name : null,
      frame_url: result && result.file_name ? `/files/storyboard_frames/${result.file_name}` : null
    });
  } catch (error) {
    console.error("❌ Regex parser ERROR:", error);
    res.json({
      success: false,
      message: "Regex parser failed",
      error: error.message,
      test_data: testData
    });
  }
});

// Debug endpoint to list all available routes
router.get("/debug-routes", (req, res) => {
  console.log("🔍 Debug routes request");
  res.json({
    success: true,
    message: "Storyboard routes are working",
    available_routes: [
      "GET /api/storyboard/test-cors",
      "GET /api/storyboard/debug-routes", 
      "GET /api/storyboard/debug-data",
      "POST /api/storyboard/export-data",
      "POST /api/storyboard/export",
      "GET /api/storyboard/projects",
      "GET /api/storyboard/projects/:id/sub-projects"
    ],
    timestamp: new Date().toISOString()
  });
});

// Debug endpoint to check what storyboard data exists
router.get("/debug-data", async (req, res) => {
  try {
    console.log("🔍 Debug data request");
    
    // Get count of storyboards per project
    const [storyboardCounts] = await pool.execute(`
      SELECT project_id, COUNT(*) as count 
      FROM rise_storyboards 
      GROUP BY project_id 
      ORDER BY project_id
    `);
    
    // Get sample storyboards with frame data
    const [sampleStoryboards] = await pool.execute(`
      SELECT id, project_id, sub_storyboard_project_id, scene_heading_id, shot, 
             SUBSTRING(frame, 1, 200) as frame_preview,
             LENGTH(frame) as frame_length,
             SUBSTRING(raw_footage, 1, 200) as footage_preview,
             LENGTH(raw_footage) as footage_length
      FROM rise_storyboards 
      WHERE project_id = 128
      LIMIT 5
    `);
    
    // Get projects that have storyboards
    const [projectsWithStoryboards] = await pool.execute(`
      SELECT DISTINCT s.project_id, p.title, p.description
      FROM rise_storyboards s
      LEFT JOIN rise_projects p ON s.project_id = p.id
      ORDER BY s.project_id
    `);
    
    res.json({
      success: true,
      storyboard_counts: storyboardCounts,
      sample_storyboards: sampleStoryboards,
      projects_with_storyboards: projectsWithStoryboards,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error("❌ Error in debug data:", error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Get available projects for export (only storyboard projects)
router.get("/projects", async (req, res) => {
  try {
    const [rows] = await pool.execute(
      "SELECT id, title, description FROM rise_projects WHERE deleted = 0 AND is_storyboard = 1 ORDER BY title ASC"
    );

    res.json({
      success: true,
      projects: rows,
    });
  } catch (error) {
    console.error("❌ Error fetching storyboard projects:", error);
    res.status(500).json({
      success: false,
      message: "Failed to fetch projects",
      error: error.message,
    });
  }
});

// Get sub-projects for a specific project
router.get("/projects/:project_id/sub-projects", async (req, res) => {
  try {
    const { project_id } = req.params;

    const [rows] = await pool.execute(
      "SELECT id, title FROM rise_sub_storyboard_projects WHERE rise_story_id = ? ORDER BY title ASC",
      [project_id]
    );

    res.json({
      success: true,
      sub_projects: rows,
    });
  } catch (error) {
    console.error("❌ Error fetching sub-projects:", error);
    res.status(500).json({
      success: false,
      message: "Failed to fetch sub-projects",
      error: error.message,
    });
  }
});

module.exports = router;
