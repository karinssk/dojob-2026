// Storyboard JavaScript Functions

// Add CSS for single inline edit restriction
$(document).ready(function () {
  // Check and apply dynamic spacing based on scene count
  applyDynamicSpacing();

  // Initialize responsive table
  initResponsiveTable();

  // Add styles for single edit restriction
  if (!document.getElementById("single-edit-styles")) {
    const styles = `
      <style id="single-edit-styles">
        /* Single edit restriction styles */
        .has-active-edit .editable-cell:not(.editing) {
          pointer-events: none;
          opacity: 0.5;
          cursor: not-allowed;
        }
        
  
        
        .editable-cell {
          transition: all 0.3s ease;
          position: relative;
        }
        
        .editable-cell:hover:not(.editing):not(.has-active-edit .editable-cell) {
         
          cursor: pointer;
        }
        
  
        
        .editable-cell.editing .inline-edit-input:focus {
          outline: none;

          box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        /* Constrained editing area for content and dialogues to prevent overlay */
        .editable-cell[data-field="content"].editing .inline-edit-input,
        .editable-cell[data-field="dialogues"].editing .inline-edit-input {
          min-width: 400px;
          width: 400px !important;
          box-sizing: border-box;
        }
        
        /* Other field inputs */
        .editable-cell.editing .inline-edit-input {
          min-width: 300px;
          width: 300px !important;
          box-sizing: border-box;
        }
        
        .editable-cell.editing textarea.inline-edit-input {
          min-height: 80px;
          resize: vertical;
        }
        
        .inline-edit-buttons {
          display: flex;
          gap: 5px;
          justify-content: flex-start;
          margin-top: 8px !important;
          padding-top: 5px;
          border-top: 1px solid #dee2e6;
        }
        
        .inline-edit-buttons .btn {
          min-width: 35px;
          height: 32px;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        
        /* Enhanced column widths for content-heavy fields */
        .editable-cell[data-field="content"],
        .editable-cell[data-field="dialogues"] {
          min-width: 200px;
          max-width: 300px;
          word-wrap: break-word;
         
          width: 100%;
          box-sizing: border-box;
        }
        
        /* Specific constraints for content and dialogues when editing */
        .editable-cell[data-field="content"].editing,
        .editable-cell[data-field="dialogues"].editing {
          max-width: 300px;
          width: auto;
          overflow: visible;
          position: relative;
          z-index: 1002;
        }
        
        .editable-cell[data-field="lighting"],
        .editable-cell[data-field="note"] {
          min-width: 150px;
          max-width: 300px;
          word-wrap: break-word;
         
        }
        
        /* Better text display in cells */
        .editable-content {
          display: block;
          line-height: 1.4;
          word-break: break-word;
        }
        
        /* Visual indicator for editable fields */
        .editable-cell:hover:not(.editing)::after {
          content: '✎';
          position: absolute;
          top: 2px;
          right: 2px;
          font-size: 10px;
          color: #6c757d;
          z-index: 1;
        }
        
        .has-active-edit .editable-cell:not(.editing)::after {
          display: none;
        }
        
        /* CRITICAL FIX: Tailwind-compatible fixes for table layout */
        .storyboard-table td[data-field="content"],
        .storyboard-table td[data-field="dialogues"] {
          height: auto !important;
          min-height: auto !important;
        }
        
        .storyboard-table td[data-field="content"] .editable-content,
        .storyboard-table td[data-field="dialogues"] .editable-content {
          word-break: break-word !important;
          overflow-wrap: break-word !important;
        }
        
        /* Force table rows to not have excessive height */
        .storyboard-table tbody tr {
          height: auto !important;
          min-height: auto !important;
        }
        
        /* Frame column auto height for Tailwind */ 
        .storyboard-table .frame-column {
          height: auto !important;
        }
        
        /* Table cell overflow control - allow dropdown overflow */
        .storyboard-table td {
          overflow: visible;
          position: relative;
          vertical-align: top;
        }
        
        /* Use fixed table layout normally, but allow expansion when editing */
        .storyboard-table {
          table-layout: fixed;
          width: 100%;
        }
        
        /* Switch to auto layout when editing to allow expansion */
        .storyboard-table.table-editing {
          table-layout: auto !important;
          width: auto !important;
          min-width: 100% !important;
        }
        
        /* Ensure table container can scroll horizontally when needed */
        .table-responsive,
        .storyboard-table-container {
          overflow-x: auto !important;
          width: 100%;
        }
        
        /* When editing, allow container to expand */
        .table-responsive.table-container-editing,
        .storyboard-table-container.table-container-editing {
          overflow-x: auto !important;
          min-width: 100%;
          width: auto;
        }
        
        /* Content and dialogue columns with flexible constraints */
        .storyboard-table td[data-field="content"],
        .storyboard-table td[data-field="dialogues"] {
          max-width: 300px;
          min-width: 200px;
          overflow: visible;
          word-break: break-word;
        }
        
        /* Non-editing cells should maintain their normal width */
        .storyboard-table td:not(.editing) {
          width: auto;
          white-space: normal;
        }
        
        /* Editing cells expand to the right without affecting other columns */
        .storyboard-table td.editing {
          position: relative;
          z-index: 1001;
          overflow: visible !important;
          white-space: nowrap;
          min-width: 400px !important;
          width: auto !important;
        }
        
        /* Content and dialogue fields get extra space when editing */
        .storyboard-table td[data-field="content"].editing,
        .storyboard-table td[data-field="dialogues"].editing {
          min-width: 500px !important;
          width: auto !important;
        }
        
        /* Ensure table row expands when table is in editing mode */
        .storyboard-table.table-editing tr {
          height: auto !important;
          min-height: 120px !important;
        }
        
        /* Special handling for editing cells with dropdowns */
        .storyboard-table td.editing {
          position: relative;
          z-index: 1001;
          overflow: visible !important;
        }
        
        /* Tailwind-specific hover and editing states */
        .sortable-column:hover {
          background-color: rgb(243 244 246) !important;
        }
        

        
        /* Custom dropdown styles for Tailwind compatibility */
        .custom-dropdown-container {
          position: relative;
          width: 100%;
          min-width: 150px;
          z-index: 1;
        }
        
        /* Force remove any blue backgrounds from dropdown elements */
        .custom-dropdown-trigger,
        .custom-dropdown-trigger:hover,
        .custom-dropdown-trigger:focus,
        .custom-dropdown-trigger:active {
          background-color: white !important;
          color: #374151 !important;
        }
        
        .custom-dropdown-container.open .dropdown-arrow {
          transform: rotate(180deg);
        }
        
        .custom-dropdown-trigger {
          transition: all 0.2s ease;
        }
        
        .custom-dropdown-trigger:hover {
          border-color: rgb(156 163 175) !important;
        }
        
        .custom-dropdown-trigger:focus {
          outline: none;
          ring: 2px;
          ring-color: rgb(59 130 246);
          border-color: rgb(59 130 246);
        }
        
        .custom-dropdown-menu {
          position: relative;
          min-width: 200px;
          width: 100%;
          max-width: 350px;
          z-index: 99999 !important;
          background-color: white;
          border: 1px solid rgb(209 213 219);
          border-radius: 0.375rem;
          box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
          max-height: 15rem;
          overflow-y: auto;
          white-space: nowrap;
          transform: translateZ(0); /* Force hardware acceleration for smoother positioning */
        }
        
        /* Collapsed state - show only current value */
        .custom-dropdown-menu.collapsed {
          max-height: 2.5rem;
          overflow: hidden;
        }
        
        /* Expanded state - show all options */
        .custom-dropdown-menu.expanded {
          max-height: 15rem;
          overflow-y: auto;
        }
        
        .custom-dropdown-menu.hidden {
          display: none !important;
        }
        
        /* Dynamic spacing for single scene scenarios only */
        .storyboard-table.single-scene-spacing {
          margin-bottom: 300px !important;
        }
        
        .scene-content-container.single-scene-spacing {
          padding-bottom: 250px !important;
          min-height: 400px;
        }
        
        /* Default spacing for multiple scenes */
        .storyboard-table {
          margin-bottom: 50px;
        }
        
        .scene-content-container {
          padding-bottom: 20px;
          min-height: auto;
        }
        
        /* Force dropdown to appear above everything */
        
        .editable-cell.editing .custom-dropdown-menu {
          z-index: 99999 !important;
          position: absolute !important;
        }
        
        .custom-dropdown-item {
          padding: 0.5rem 0.75rem;
          font-size: 0.875rem;
          cursor: pointer;
          transition: all 0.15s ease;
          background-color: white !important;
          color: #374151 !important;
        }
        
        .custom-dropdown-item:hover {
          background-color: #f9fafb !important;
          color: #374151 !important;
        }
        
        .custom-dropdown-item.selected {
          background-color: #f3f4f6 !important;
          color: #374151 !important;
        }
        
        /* Override any Tailwind blue background classes */
        .custom-dropdown-container .bg-blue-50,
        .custom-dropdown-container .bg-blue-100,
        .custom-dropdown-container .bg-blue-200 {
          background-color: white !important;
        }
        
        .custom-dropdown-container .text-blue-600,
        .custom-dropdown-container .text-blue-700,
        .custom-dropdown-container .text-blue-800,
        .custom-dropdown-container .text-blue-900 {
          color: #374151 !important;
        }
        
        /* Force all dropdown elements to have white/gray backgrounds */
        .custom-dropdown-container * {
          background-color: white !important;
        }
        
        .custom-dropdown-container *:hover {
          background-color: #f9fafb !important;
        }
        
        /* Mobile-first responsive table optimizations */
        @media (max-width: 768px) {
          /* Make table container horizontally scrollable */
          .storyboard-table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            position: relative;
          }
          
          /* Sticky header on mobile */
          .storyboard-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f9fafb;
          }
          
          /* Switch to auto layout for better mobile handling */
          .storyboard-table {
            table-layout: auto !important;
            min-width: 800px; /* Minimum width to prevent cramping */
          }
          
          /* Hide less important columns on mobile */
          .storyboard-table th[data-column="equipment"],
          .storyboard-table td:nth-child(10), /* Equipment column */
          .storyboard-table th[data-column="framerate"],
          .storyboard-table td:nth-child(11), /* FPS column */
          .storyboard-table th[data-column="lighting"],
          .storyboard-table td:nth-child(12), /* Lighting column */
          .storyboard-table th[data-column="sound"],
          .storyboard-table td:nth-child(9) /* Sound column */ {
            display: none;
          }
          
          /* Optimize column widths for mobile */
          .storyboard-table th,
          .storyboard-table td {
            min-width: 80px;
            padding: 8px 6px;
            font-size: 12px;
          }
          
          /* Make shot number smaller */
          .storyboard-table td:first-child {
            min-width: 50px;
          }
          
          /* Make frame column smaller */
          .storyboard-table td:nth-child(2) {
            min-width: 60px;
          }
          
          /* Optimize content and dialogue columns */
          .storyboard-table td[data-field="content"],
          .storyboard-table td[data-field="dialogues"] {
            min-width: 120px;
            max-width: 150px;
          }
          
          /* Make actions column smaller */
          .storyboard-table td:last-child {
            min-width: 70px;
          }
        }
        
        /* Tablet optimizations */
        @media (max-width: 1024px) and (min-width: 769px) {
          .storyboard-table {
            table-layout: auto !important;
          }
          
          /* Hide some less critical columns on tablet */
          .storyboard-table th[data-column="equipment"],
          .storyboard-table td:nth-child(10), /* Equipment column */
          .storyboard-table th[data-column="framerate"],
          .storyboard-table td:nth-child(11) /* FPS column */ {
            display: none;
          }
          
          .storyboard-table th,
          .storyboard-table td {
            padding: 10px 8px;
            font-size: 13px;
          }
        }
        
        /* Mobile editing optimizations */
        @media (max-width: 768px) {
          /* Smaller editing interface on mobile */
          .editable-cell.editing {
            min-width: 200px !important;
            padding: 8px !important;
          }
          
          .editable-cell.editing .inline-edit-input {
            min-width: 180px !important;
            width: 180px !important;
            font-size: 14px;
          }
          
          /* Smaller buttons on mobile */
          .inline-edit-buttons .btn {
            min-width: 60px;
            height: 32px;
            font-size: 12px;
            padding: 4px 8px;
          }
          
          /* Compact dropdown on mobile */
          .custom-dropdown-menu {
            min-width: 180px;
            max-width: 250px;
            font-size: 13px;
          }
          
          .custom-dropdown-item {
            padding: 8px 12px;
            font-size: 13px;
          }
        }
        
        .custom-dropdown-item:first-child {
          border-bottom: 1px solid rgb(243 244 246);
        }
        
        .custom-dropdown-item:hover {
          background-color: rgb(239 246 255);
        }
        
        .custom-dropdown-item.selected {
          background-color: rgb(219 234 254);
          color: rgb(30 58 138);
        }
        
        .dropdown-arrow {
          transition: transform 0.2s ease;
        }
        
        /* Fix Bootstrap dropdown toggle caret for three-dot buttons */
        .dropdown-toggle::after {
          display: none !important;
        }
        
        .dropdown-toggle-no-caret::after {
          display: none !important;
        }
        
        /* Ensure three-dot dropdown buttons don't show caret */
        button[data-feather*="more"].dropdown-toggle::after,
        .dropdown-toggle:has(i[data-feather*="more"])::after {
          display: none !important;
        }
        
        /* Enhanced three-dot button styling */
        .btn.dropdown-toggle:focus {
          box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
          border-color: #3b82f6 !important;
        }
        
        .btn.dropdown-toggle:hover {
          background-color: #f3f4f6 !important;
          border-color: #d1d5db !important;
        }
        
        /* Improved inline editing container - expands to the right */
        .editable-cell.editing {
          background-color: rgba(59, 130, 246, 0.05) !important;
          border: 2px solid rgba(59, 130, 246, 0.2) !important;
          border-radius: 8px !important;
          padding: 12px !important;
          position: relative;
          z-index: 1000 !important;
          width: auto !important;
          min-width: 400px !important;
          box-sizing: border-box;
          overflow: visible;
          white-space: nowrap;
        }
        
        /* Better button styling for inline edit */
        .inline-edit-buttons {
          display: flex;
          gap: 10px;
          justify-content: flex-start;
          margin-top: 15px !important;
          padding-top: 10px;
          border-top: 1px solid rgba(59, 130, 246, 0.2);
          flex-wrap: nowrap;
          min-width: 180px;
          white-space: nowrap;
        }
        
        .inline-edit-buttons .btn {
          min-width: 80px;
          height: 38px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 14px;
          font-weight: 500;
          border-radius: 6px;
          transition: all 0.2s ease;
          white-space: nowrap;
          flex-shrink: 0;
        }
        
        .inline-edit-buttons .btn-success {
          background-color: #10b981;
          border-color: #10b981;
          color: white;
        }
        
        .inline-edit-buttons .btn-success:hover {
          background-color: #059669;
          border-color: #059669;
          transform: translateY(-1px);
        }
        
        .inline-edit-buttons .btn-secondary {
          background-color: #6b7280;
          border-color: #6b7280;
          color: white;
        }
        
        .inline-edit-buttons .btn-secondary:hover {
          background-color: #4b5563;
          border-color: #4b5563;
          transform: translateY(-1px);
        }
      </style>
    `;

    document.head.insertAdjacentHTML("beforeend", styles);
  }
});

// Function to initialize responsive table behavior
function initResponsiveTable() {
  console.log("🔧 Initializing responsive table");

  // Add responsive classes based on screen size
  function updateTableResponsiveness() {
    const screenWidth = window.innerWidth;
    const $tables = $(".storyboard-table");

    if (screenWidth <= 768) {
      $tables.addClass("mobile-view").removeClass("tablet-view desktop-view");
      console.log("📱 Switched to mobile view");
    } else if (screenWidth <= 1024) {
      $tables.addClass("tablet-view").removeClass("mobile-view desktop-view");
      console.log("📱 Switched to tablet view");
    } else {
      $tables.addClass("desktop-view").removeClass("mobile-view tablet-view");
      console.log("🖥️ Switched to desktop view");
    }
  }

  // Initial check
  updateTableResponsiveness();

  // Update on window resize
  $(window).on("resize", function () {
    clearTimeout(window.resizeTimer);
    window.resizeTimer = setTimeout(updateTableResponsiveness, 250);
  });
}

// Load storyboard modal
function loadStoryboardModal(
  projectId,
  storyboardId = null,
  subProjectId = null,
  sceneHeadingId = null
) {
  console.log(
    "loadStoryboardModal called with projectId:",
    projectId,
    "storyboardId:",
    storyboardId,
    "subProjectId:",
    subProjectId,
    "sceneHeadingId:",
    sceneHeadingId
  );

  $.ajax({
    url: get_uri("storyboard/modal_form"),
    type: "POST",
    data: {
      project_id: projectId,
      sub_project_id: subProjectId,
      id: storyboardId,
      scene_heading_id: sceneHeadingId,
    },
    success: function (response) {
      console.log("Modal form loaded successfully");
      $("#storyboard-modal-content").html(response);
    },
    error: function (xhr, status, error) {
      console.error("Error loading storyboard form:", error);
      console.error("Response:", xhr.responseText);
      Swal.fire({
        icon: "error",
        title: "Loading Error",
        text: "Error loading storyboard form: " + error,
        confirmButtonText: "OK",
        confirmButtonColor: "#dc3545",
      });
    },
  });
}

// Load scene heading modal
function loadSceneHeadingModal(
  projectId,
  headingId = null,
  subProjectId = null
) {
  console.log(
    "loadSceneHeadingModal called with projectId:",
    projectId,
    "headingId:",
    headingId,
    "subProjectId:",
    subProjectId
  );

  $.ajax({
    url: get_uri("storyboard/scene_heading_modal_form"),
    type: "POST",
    data: {
      project_id: projectId,
      sub_project_id: subProjectId,
      id: headingId,
    },
    success: function (response) {
      console.log("Scene heading modal form loaded successfully");
      $("#scene-heading-modal-content").html(response);
    },
    error: function (xhr, status, error) {
      console.error("Error loading scene heading form:", error);
      console.error("Response:", xhr.responseText);
      Swal.fire({
        icon: "error",
        title: "Loading Error",
        text: "Error loading scene heading form: " + error,
        confirmButtonText: "OK",
        confirmButtonColor: "#dc3545",
      });
    },
  });
}

// Delete scene heading
function deleteSceneHeading(headingId, headingTitle) {
  console.log(
    "deleteSceneHeading called with ID:",
    headingId,
    "Title:",
    headingTitle
  );

  if (
    confirm(
      'Are you sure you want to delete the scene heading "' +
        headingTitle +
        '"?\n\nThis will not delete the storyboard scenes, but they will become unorganized.'
    )
  ) {
    $.ajax({
      url: get_uri("storyboard/delete_scene_heading"),
      type: "POST",
      data: {
        id: headingId,
      },
      dataType: "json",
      success: function (response) {
        console.log("Delete response:", response);

        if (response.success) {
          Swal.fire({
            icon: "success",
            title: "Scene Heading Deleted!",
            text: response.message,
            confirmButtonText: "OK",
            confirmButtonColor: "#28a745",
            timer: 2000,
            timerProgressBar: true,
          }).then(() => {
            location.reload(); // Refresh to show updated layout
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Delete Failed",
            text: response.message,
            confirmButtonText: "OK",
            confirmButtonColor: "#dc3545",
          });
        }
      },
      error: function (xhr, status, error) {
        console.error("Delete error:", error);
        console.error("Response:", xhr.responseText);

        Swal.fire({
          icon: "error",
          title: "Network Error",
          text: "Error deleting scene heading: " + error,
          confirmButtonText: "OK",
          confirmButtonColor: "#dc3545",
        });
      },
    });
  }
}

// Toggle statistics visibility with localStorage
function toggleStats() {
  const statsContainer = document.getElementById("statsContainer");
  const toggleBtn = document.getElementById("statsToggleBtn");
  const toggleIcon = document.getElementById("statsToggleIcon");
  const toggleText = document.getElementById("statsToggleText");

  const isHidden = statsContainer.style.display === "none";

  if (isHidden) {
    // Show stats
    statsContainer.style.display = "flex";
    toggleIcon.setAttribute("data-feather", "eye-off");
    toggleText.textContent = "Hide";
    localStorage.setItem("storyboard_stats_visible", "true");
  } else {
    // Hide stats
    statsContainer.style.display = "none";
    toggleIcon.setAttribute("data-feather", "eye");
    toggleText.textContent = "Show";
    localStorage.setItem("storyboard_stats_visible", "false");
  }

  // Re-render feather icons
  if (typeof feather !== "undefined") {
    feather.replace();
  }
}

// Initialize stats visibility from localStorage
function initializeStatsVisibility() {
  const statsContainer = document.getElementById("statsContainer");
  const toggleBtn = document.getElementById("statsToggleBtn");
  const toggleIcon = document.getElementById("statsToggleIcon");
  const toggleText = document.getElementById("statsToggleText");

  // Get saved preference (default to visible)
  const isVisible =
    localStorage.getItem("storyboard_stats_visible") !== "false";

  if (isVisible) {
    statsContainer.style.display = "flex";
    toggleIcon.setAttribute("data-feather", "eye-off");
    toggleText.textContent = "Hide";
  } else {
    statsContainer.style.display = "none";
    toggleIcon.setAttribute("data-feather", "eye");
    toggleText.textContent = "Show";
  }

  // Re-render feather icons
  if (typeof feather !== "undefined") {
    feather.replace();
  }
}

// Project management functions
function editCurrentProject() {
  $("#edit-project-modal").modal("show");
}

function deleteCurrentProject() {
  const projectTitle = document
    .querySelector(".project-title")
    .textContent.trim();
  const projectId = document.getElementById("edit-project-id").value;

  if (
    confirm(
      'Are you sure you want to delete the project "' +
        projectTitle +
        '"?\n\nThis action cannot be undone and will delete all storyboard data associated with this project.'
    )
  ) {
    $.ajax({
      url: get_uri("storyboard/delete_project"),
      type: "POST",
      data: { id: projectId },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          alert("Project deleted successfully!");
          window.location.href = get_uri("storyboard");
        } else {
          alert("Error: " + response.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        alert("Error deleting project: " + error);
      },
    });
  }
}

// Helper function to get URI (if not already defined)
function get_uri(path) {
  return window.location.origin + "/index.php/" + path;
}

// Function to apply dynamic spacing based on scene count per heading
function applyDynamicSpacing() {
  console.log("Checking scene counts for dynamic spacing...");

  // Check each scene heading section
  $(".scene-heading-card, .scene-content-container").each(function () {
    const $container = $(this);
    const $storyboardTables = $container.find(".storyboard-table");

    // Count total scenes in this heading section
    let totalScenes = 0;
    $storyboardTables.each(function () {
      const sceneCount = $(this).find("tbody tr").length;
      totalScenes += sceneCount;
    });

    console.log(`Scene heading has ${totalScenes} scenes`);

    // Apply spacing only if there's exactly 1 scene
    if (totalScenes === 1) {
      $container.addClass("single-scene-spacing");
      $storyboardTables.addClass("single-scene-spacing");
      console.log("Applied single scene spacing");
    } else {
      $container.removeClass("single-scene-spacing");
      $storyboardTables.removeClass("single-scene-spacing");
      console.log("Removed single scene spacing (multiple scenes detected)");
    }
  });

  // Also check for unorganized scenes container
  const $unorganizedContainer = $(".unorganized-scenes");
  if ($unorganizedContainer.length > 0) {
    const $unorganizedTable = $unorganizedContainer.find(".storyboard-table");
    const unorganizedSceneCount = $unorganizedTable.find("tbody tr").length;

    console.log(`Unorganized scenes: ${unorganizedSceneCount} scenes`);

    if (unorganizedSceneCount === 1) {
      $unorganizedContainer.addClass("single-scene-spacing");
      $unorganizedTable.addClass("single-scene-spacing");
      console.log("Applied single scene spacing to unorganized scenes");
    } else {
      $unorganizedContainer.removeClass("single-scene-spacing");
      $unorganizedTable.removeClass("single-scene-spacing");
      console.log("Removed single scene spacing from unorganized scenes");
    }
  }
}

// Document ready functions for field options
$(document).ready(function () {
  // Load field options from database
  loadFieldOptionsFromDB();

  // After field options are loaded, populate icons in table cells
  setTimeout(function () {
    populateFieldIcons();
  }, 1000); // Wait for field options to load

  // Handle field type selection
  $("#field-type-list .list-group-item").on("click", function () {
    const fieldType = $(this).data("field");
    loadFieldOptions(fieldType);
  });

  // Fix Bootstrap dropdown toggle issues - but only for field options modal
  $(document).on(
    "click",
    "#field-options-modal .dropdown-toggle",
    function (e) {
      const $this = $(this);
      const $dropdown = $this.next(".dropdown-menu");

      // Close all other dropdowns in the modal first
      $("#field-options-modal .dropdown-menu")
        .not($dropdown)
        .removeClass("show")
        .hide();
      $("#field-options-modal .dropdown-toggle")
        .not($this)
        .attr("aria-expanded", "false");

      // Toggle current dropdown
      const isOpen = $dropdown.hasClass("show");
      if (isOpen) {
        $dropdown.removeClass("show").hide();
        $this.attr("aria-expanded", "false");
      } else {
        $dropdown.addClass("show").show();
        $this.attr("aria-expanded", "true");
      }

      e.preventDefault();
      e.stopPropagation();
    }
  );

  // Close field options modal dropdowns when clicking outside
  $(document).on("click", function (e) {
    if (!$(e.target).closest("#field-options-modal .dropdown").length) {
      $("#field-options-modal .dropdown-menu").removeClass("show").hide();
      $("#field-options-modal .dropdown-toggle").attr("aria-expanded", "false");
    }
  });

  // Prevent field options modal dropdown from closing when clicking inside
  $(document).on("click", "#field-options-modal .dropdown-menu", function (e) {
    e.stopPropagation();
  });

  // Allow modal triggers to work normally - don't interfere with data-bs-toggle="modal"
  $(document).on("click", '[data-bs-toggle="modal"]', function (e) {
    // Don't prevent default behavior for modal triggers
    console.log("Modal trigger clicked:", $(this).attr("data-bs-target"));
  });

  // Handle dropdown toggle clicks properly (including three-dot buttons)
  $(document).on("click", '[data-bs-toggle="dropdown"]', function (e) {
    e.stopPropagation();
    console.log("Dropdown toggle clicked");

    // Let Bootstrap handle the dropdown naturally
    // The data-bs-toggle attribute will trigger Bootstrap's dropdown functionality
  });

  // Ensure dropdown menus stay open when clicked inside
  $(document).on("click", ".dropdown-menu", function (e) {
    e.stopPropagation();
  });

  // Close dropdowns when clicking outside (but not when editing)
  $(document).on("click", function (e) {
    if (!currentlyEditing && !$(e.target).closest(".dropdown").length) {
      $(".dropdown-menu").removeClass("show");
      $(".dropdown-toggle").attr("aria-expanded", "false");
    }
  });

  // Fix Bootstrap dropdown positioning in modal
  $("#field-options-modal").on("shown.bs.modal", function () {
    // Force dropdown menus to use proper z-index
    $(this).find(".dropdown-menu").css({
      "z-index": "99998",
      position: "absolute",
    });

    // Disable body scroll when modal is open
    $("body").addClass("modal-open-no-scroll");
  });

  // Re-enable body scroll when modal is closed
  $("#field-options-modal").on("hidden.bs.modal", function () {
    $("body").removeClass("modal-open-no-scroll");
  });

  // Fix dropdown positioning when opened
  $(document).on(
    "shown.bs.dropdown",
    "#field-options-modal .dropdown",
    function () {
      const $menu = $(this).find(".dropdown-menu");
      $menu.css({
        "z-index": "99998",
        position: "absolute",
      });
    }
  );
});
// Inline editing functionality
let currentlyEditing = null;
let originalContent = {};

// Field Options Data - Initialize as empty, will be loaded from database
let fieldOptionsData = {};

// DISABLED: Handle click on editable cells - SINGLE EDIT RESTRICTION
// This functionality is now handled in the main index.php file to avoid conflicts
// with the new dynamic field options system

$(document).on("click", ".editable-cell", function (e) {
  // Don't handle clicks on buttons, links, modal triggers, or images inside cells
  if (
    $(e.target).is(
      "button, a, [data-bs-toggle], [data-toggle], .btn, .dropdown-toggle, img, .img-thumbnail"
    )
  ) {
    console.log("Ignoring click on action element:", e.target);
    return;
  }

  // Don't handle clicks on dropdown menus or their children
  if ($(e.target).closest(".dropdown-menu, .dropdown").length > 0) {
    console.log("Ignoring click inside dropdown");
    return;
  }

  e.stopPropagation();

  const field = $(this).data("field");
  const clickedCell = this;

  console.log("Clicked editable cell with field:", field);

  // STRICT SINGLE EDIT POLICY - Show warning if another cell is being edited
  if (currentlyEditing && currentlyEditing !== clickedCell) {
    console.log("Another cell is editing, showing warning...");

    // Show warning toast
    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: "warning",
        title: "Finish Current Edit",
        text: "Please finish editing the current field before starting a new one.",
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    } else {
      alert(
        "Please finish editing the current field before starting a new one."
      );
    }

    // Highlight the currently editing cell
    $(currentlyEditing).addClass("editing-highlight");
    setTimeout(() => {
      $(currentlyEditing).removeClass("editing-highlight");
    }, 2000);

    return; // BLOCK the new edit attempt
  }

  // Don't edit if this cell is already being edited
  if ($(this).hasClass("editing")) {
    return;
  }

  // Set global editing state
  $("body").addClass("has-active-edit");
  startEdit(this);
});

function startEdit(cell) {
  const $cell = $(cell);
  const field = $cell.data("field");
  const id = $cell.data("id");
  const $content = $cell.find(".editable-content");

  console.log("Starting edit for field:", field, "id:", id);

  // Add editing classes to table for layout expansion
  const $table = $cell.closest(".storyboard-table");
  const $tableContainer = $cell.closest(".storyboard-table-container");

  $table.addClass("table-editing").removeClass("table-fixed");
  $tableContainer.addClass("table-container-editing");

  console.log("Added editing classes to table");

  // Close any open field options modal dropdowns before starting edit
  $("#field-options-modal .dropdown-menu").removeClass("show").hide();
  $("#field-options-modal .dropdown-toggle").attr("aria-expanded", "false");

  // Get current value based on field type
  let currentValue;
  if (field === "story_status") {
    currentValue = $content.data("value") || $content.text();
  } else if ($content.data("full-value") !== undefined) {
    currentValue = $content.data("full-value");
  } else {
    currentValue = $content.text();
  }

  const displayValue = currentValue === "-" ? "" : currentValue;

  // Store original content for cancel functionality
  if (!originalContent[id]) {
    originalContent[id] = {};
  }
  originalContent[id][field] = {
    value: currentValue,
    html: $cell.html(), // Store the complete HTML for proper restoration
  };

  currentlyEditing = cell;
  $cell.addClass("editing");

  let inputHtml = "";

  // Check if field has predefined options for custom dropdown
  const fieldOptions = getFieldOptions(field);

  if (fieldOptions) {
    // Create custom styled dropdown
    inputHtml = createCustomDropdown(fieldOptions, currentValue, field);
    $cell.html(inputHtml);
    setupCustomDropdown($cell, id, field);
  } else if (
    field === "content" ||
    field === "dialogues" ||
    field === "lighting" ||
    field === "note"
  ) {
    // Textarea for longer text fields with constrained sizing to prevent overlay
    const textareaRows = Math.max(
      3,
      Math.min(6, Math.ceil(displayValue.length / 40))
    );

    // Determine width based on field type - fixed width for consistent expansion
    const fieldWidth =
      field === "content" || field === "dialogues" ? "400px" : "300px";

    inputHtml = `<textarea class="form-control inline-edit-input" rows="${textareaRows}" 
                 style="width: ${fieldWidth}; resize: vertical; box-sizing: border-box;" 
                 placeholder="Enter ${field.replace(
                   "_",
                   " "
                 )}...">${displayValue}</textarea>`;
    $cell.html(inputHtml + getEditButtons(id, field));
    setupTextInput($cell, id, field);
  } else {
    // Regular input for short fields
    inputHtml = `<input type="text" class="form-control inline-edit-input" value="${displayValue}" placeholder="Enter ${field.replace(
      "_",
      " "
    )}...">`;
    $cell.html(inputHtml + getEditButtons(id, field));
    setupTextInput($cell, id, field);
  }
}

function getFieldOptions(field) {
  console.log("Getting field options for:", field);
  return fieldOptionsData[field] || null;
}

function createCustomDropdown(options, currentValue, field) {
  // Find the current option to display in trigger
  const currentOption = options.find((opt) => opt.value === currentValue);
  let triggerDisplay = "Select " + field.replace("_", " ");

  if (currentOption) {
    if (currentOption.icon) {
      triggerDisplay = `<span class="inline-flex items-center mr-2">${currentOption.icon}</span> ${currentOption.label}`;
    } else if (currentOption.color) {
      triggerDisplay = `<span class="inline-block w-3 h-3 rounded-full mr-2" style="background-color: ${currentOption.color}"></span> ${currentOption.label}`;
    } else {
      triggerDisplay = currentOption.label;
    }
  }

  let html = '<div class="relative w-full custom-dropdown-container">';

  // Merged dropdown - single element that shows current value and options
  html +=
    '<div class="custom-dropdown-menu bg-white border border-gray-300 rounded-md shadow-sm max-h-60 overflow-y-auto">';

  // Current value as first item (always visible)
  html += `<div class="px-3 py-2 text-sm bg-white border-b border-gray-200 cursor-pointer custom-dropdown-trigger" data-value="${currentValue}">`;
  html += '<span class="flex items-center justify-between">';
  html += `<span class="flex items-center font-medium text-gray-900">${triggerDisplay}</span>`;
  html += `<svg class="w-4 h-4 text-gray-400 dropdown-arrow transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">`;
  html += `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>`;
  html += `</svg>`;
  html += `</span>`;
  html += "</div>";

  // Add empty option with Tailwind styling
  html +=
    '<div class="px-3 py-2 text-sm text-gray-500 cursor-pointer hover:bg-gray-50 border-b border-gray-100 custom-dropdown-item" data-value="">';
  html += '<span class="flex items-center">';
  html += '<span class="text-gray-400 mr-2">×</span>';
  html += '<span class="item-label">Clear selection</span>';
  html += "</span>";
  html += "</div>";

  // Add database options with enhanced Tailwind styling
  options.forEach((option) => {
    const isSelected = option.value === currentValue;
    html += `<div class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 transition-colors duration-150 custom-dropdown-item ${
      isSelected ? "bg-gray-100 text-gray-900 selected" : "text-gray-900"
    }" data-value="${option.value}">`;

    html += '<span class="flex items-center justify-between">';
    html += '<span class="flex items-center">';

    if (option.icon) {
      html += `<span class="inline-flex items-center mr-2 text-base item-icon">${option.icon}</span>`;
    } else if (option.color) {
      html += `<span class="inline-block w-3 h-3 rounded-full mr-2 item-color" style="background-color: ${option.color}"></span>`;
    }

    html += `<span class="item-label">${option.label}</span>`;
    html += "</span>";

    if (isSelected) {
      html +=
        '<svg class="w-4 h-4 text-blue-600 item-check" fill="currentColor" viewBox="0 0 20 20">';
      html +=
        '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>';
      html += "</svg>";
    }

    html += "</span>";
    html += "</div>";
  });

  html += "</div>";
  html += "</div>";

  return html;
}

function setupCustomDropdown($cell, id, field) {
  const $container = $cell.find(".custom-dropdown-container");
  const $trigger = $container.find(".custom-dropdown-trigger");
  const $menu = $container.find(".custom-dropdown-menu");
  const $arrow = $container.find(".dropdown-arrow");

  // Start with collapsed state
  $menu.addClass("collapsed");

  // Toggle dropdown on trigger click
  $trigger.on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const isExpanded = $menu.hasClass("expanded");

    if (isExpanded) {
      // Collapse dropdown
      $menu.removeClass("expanded").addClass("collapsed");
      $arrow.removeClass("rotate-180");
      $container.removeClass("open");
      enablePageScroll();
      cleanupDropdownSpacing();
    } else {
      // Expand dropdown
      $menu.removeClass("collapsed").addClass("expanded");
      $arrow.addClass("rotate-180");
      $container.addClass("open");
    }
  });

  // Prevent page scroll when mouse is over dropdown menu
  $menu.on("mouseenter", function () {
    disablePageScroll();
  });

  // Re-enable page scroll when mouse leaves dropdown menu
  $menu.on("mouseleave", function () {
    enablePageScroll();
  });

  // Prevent page scroll when scrolling inside dropdown
  $menu.on("wheel", function (e) {
    const element = this;
    const scrollTop = element.scrollTop;
    const scrollHeight = element.scrollHeight;
    const height = element.clientHeight;
    const delta = e.originalEvent.deltaY;
    const up = delta < 0;

    // Prevent page scroll when dropdown scroll reaches limits
    if (!up && scrollTop + height >= scrollHeight) {
      // At bottom, prevent scrolling down
      e.preventDefault();
      return false;
    } else if (up && scrollTop <= 0) {
      // At top, prevent scrolling up
      e.preventDefault();
      return false;
    }

    // Allow normal scrolling within dropdown
    e.stopPropagation();
  });

  // Handle item selection
  $menu.on("click", ".custom-dropdown-item", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const value = $(this).data("value");
    const label = $(this).find(".item-label").text();
    const $icon = $(this).find(".item-icon");
    const $color = $(this).find(".item-color");

    // Update trigger display with selected value
    let displayHtml = "";
    if ($icon.length > 0) {
      const iconText = $icon.text();
      displayHtml = `<span class="inline-flex items-center mr-2">${iconText}</span> ${label}`;
    } else if ($color.length > 0) {
      const colorValue = $color.attr("style");
      displayHtml = `<span class="inline-block w-3 h-3 rounded-full mr-2" ${colorValue}></span> ${label}`;
    } else {
      displayHtml = label;
    }

    // Update the trigger content
    $trigger.find("span").first().html(displayHtml);
    $trigger.attr("data-value", value);

    // Remove previous selection styling
    $menu
      .find(".custom-dropdown-item")
      .removeClass("selected bg-gray-100 text-gray-900")
      .addClass("text-gray-900");
    $menu.find(".item-check").remove();

    // Add selection to clicked item
    if (value !== "") {
      $(this)
        .removeClass("text-gray-900")
        .addClass("selected bg-gray-100 text-gray-900");
      // Add checkmark with Tailwind styling
      const checkHtml =
        '<svg class="w-4 h-4 text-gray-600 item-check" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
      $(this).find("span:last-child").append(checkHtml);
    }

    // Collapse dropdown and re-enable page scroll
    $menu.removeClass("expanded").addClass("collapsed");
    $arrow.removeClass("rotate-180");
    $container.removeClass("open");
    enablePageScroll();
    cleanupDropdownSpacing();

    // Save the value
    saveEdit(id, field, value);
  });

  // Close dropdown when clicking outside
  $(document).on("click.custom-dropdown", function (e) {
    if (!$container.is(e.target) && $container.has(e.target).length === 0) {
      $menu.removeClass("expanded").addClass("collapsed");
      $arrow.removeClass("rotate-180");
      $container.removeClass("open");
      enablePageScroll();
      cleanupDropdownSpacing();
    }
  });

  // Close dropdown when scrolling
  $(window).on("scroll.custom-dropdown", function () {
    $menu.removeClass("expanded").addClass("collapsed");
    $arrow.removeClass("rotate-180");
    $container.removeClass("open");
    enablePageScroll();
    cleanupDropdownSpacing();
  });

  // Handle escape key
  $(document).on("keydown.custom-dropdown", function (e) {
    if (e.key === "Escape") {
      $menu.removeClass("expanded").addClass("collapsed");
      $arrow.removeClass("rotate-180");
      $container.removeClass("open");
      enablePageScroll();
      cleanupDropdownSpacing();
      cancelEdit();
    }
  });

  // Auto-expand dropdown after a short delay
  setTimeout(() => {
    $menu.removeClass("collapsed").addClass("expanded");
    $arrow.addClass("rotate-180");
    $container.addClass("open");

    // Ensure proper spacing is applied before positioning dropdown
    applyDynamicSpacing();

    // Check if dropdown has enough space and adjust position if needed
    adjustDropdownPosition($menu, $container);
  }, 100);
}

// Function to adjust dropdown position if there's not enough space
function adjustDropdownPosition($menu, $container) {
  const menuHeight = $menu.outerHeight();
  const containerRect = $container[0].getBoundingClientRect();
  const viewportHeight = window.innerHeight;
  const spaceBelow = viewportHeight - containerRect.bottom;
  const spaceAbove = containerRect.top;

  console.log("Dropdown space check:", {
    menuHeight,
    spaceBelow,
    spaceAbove,
    containerRect,
  });

  // Use absolute positioning relative to container
  let topPosition = "100%"; // Default: below the container
  let bottomPosition = "auto";

  // If not enough space below but enough space above, show dropdown above
  if (spaceBelow < menuHeight && spaceAbove > menuHeight) {
    topPosition = "auto";
    bottomPosition = "100%";
    console.log("Dropdown positioned above due to insufficient space below");
  }

  // Apply absolute positioning relative to container
  $menu.css({
    position: "absolute",
    top: topPosition,
    bottom: bottomPosition,
    left: "0",
    right: "auto",
    "z-index": "99999",
    "min-width": "200px",
    "max-width": "350px",
    "margin-top": topPosition === "100%" ? "0.25rem" : "0",
    "margin-bottom": bottomPosition === "100%" ? "0.25rem" : "0",
  });

  console.log("Dropdown positioned with:", {
    top: topPosition,
    bottom: bottomPosition,
  });
}

// Helper functions to control page scrolling
function disablePageScroll() {
  // Store current scroll position
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

  // Add no-scroll class to body
  document.body.style.overflow = "hidden";
  document.body.style.position = "fixed";
  document.body.style.top = `-${scrollTop}px`;
  document.body.style.left = `-${scrollLeft}px`;
  document.body.style.width = "100%";
}

function enablePageScroll() {
  // Get stored scroll position
  const scrollTop = parseInt(document.body.style.top || "0") * -1;
  const scrollLeft = parseInt(document.body.style.left || "0") * -1;

  // Remove no-scroll styles
  document.body.style.overflow = "";
  document.body.style.position = "";
  document.body.style.top = "";
  document.body.style.left = "";
  document.body.style.width = "";

  // Restore scroll position
  window.scrollTo(scrollLeft, scrollTop);
}

// Function to clean up temporary body padding when dropdown closes
function cleanupDropdownSpacing() {
  // Reset any temporary body padding that was added for dropdown spacing
  const currentPadding = parseInt($("body").css("padding-bottom")) || 0;
  if (currentPadding > 0) {
    $("body").css("padding-bottom", "0px");
    console.log("Cleaned up temporary dropdown spacing");
  }

  // Reapply proper scene-based spacing
  setTimeout(() => {
    applyDynamicSpacing();
  }, 50);
}

function getEditButtons(id, field) {
  return `
        <div class="inline-edit-buttons">
            <button class="btn btn-sm btn-success save-edit flex items-center justify-center" data-id="${id}" data-field="${field}" title="Save changes">
                <i data-feather="check" class="w-4 h-4 mr-1"></i>
                <span>Save</span>
            </button>
            <button class="btn btn-sm btn-secondary cancel-edit flex items-center justify-center" type="button" title="Cancel editing">
                <i data-feather="x" class="w-4 h-4 mr-1"></i>
                <span>Cancel</span>
            </button>
        </div>
    `;
}

function setupTextInput($cell, id, field) {
  const $input = $cell.find(".inline-edit-input");

  // Auto-resize textarea based on content
  if ($input.is("textarea")) {
    $input.on("input", function () {
      this.style.height = "auto";
      this.style.height = Math.max(80, this.scrollHeight) + "px";
    });

    // Initial resize
    $input[0].style.height = "auto";
    $input[0].style.height = Math.max(80, $input[0].scrollHeight) + "px";
  }

  $input.focus();

  if ($input.is('input[type="text"]')) {
    $input.select();
  } else if ($input.is("textarea")) {
    // For textarea, place cursor at end
    const val = $input.val();
    $input.val("").val(val);
  }

  // Re-initialize feather icons
  if (typeof feather !== "undefined") {
    feather.replace();
  }

  // Handle keyboard events
  $input.on("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey && !$input.is("textarea")) {
      e.preventDefault();
      saveEdit(id, field, $input.val());
    } else if (e.key === "Escape") {
      cancelEdit();
    }
    // For textarea, allow Enter for new lines, Ctrl+Enter to save
    else if (e.key === "Enter" && e.ctrlKey && $input.is("textarea")) {
      e.preventDefault();
      saveEdit(id, field, $input.val());
    }
  });
}

// Handle save button click
$(document).on("click", ".save-edit", function () {
  const id = $(this).data("id");
  const field = $(this).data("field");
  const $input = $(this).closest(".editable-cell").find(".inline-edit-input");
  const value = $input.val();

  saveEdit(id, field, value);
});

// Handle cancel button click
$(document).on("click", ".cancel-edit", function (e) {
  e.preventDefault();
  e.stopPropagation();
  console.log("Cancel button clicked");
  cancelEdit();
});

function saveEdit(id, field, value) {
  const $cell = $(currentlyEditing);

  console.log("Saving edit:", { id, field, value });

  // Show loading state
  $cell.html('<i class="fa fa-spinner fa-spin"></i> Saving...');

  // Send AJAX request to save
  $.ajax({
    url: get_uri("storyboard/inline_edit"),
    type: "POST",
    dataType: "json",
    data: {
      id: id,
      field: field,
      value: value,
    },
    success: function (response) {
      console.log("Save response:", response);

      // Handle both JSON object and JSON string responses
      let result = response;
      if (typeof response === "string") {
        try {
          result = JSON.parse(response);
        } catch (e) {
          console.error("Failed to parse JSON response:", response);
          Swal.fire({
            icon: "error",
            title: "Invalid Response",
            text: "Invalid server response",
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
          });
          cancelEdit();
          return;
        }
      }

      if (result.success) {
        // Update cell content and clear global editing state
        updateCellContent($cell, field, result.value || value);

        Swal.fire({
          icon: "success",
          title: "Updated!",
          text: result.message || "Updated successfully",
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true,
        });
      } else {
        console.error("Save failed:", result.message);
        Swal.fire({
          icon: "error",
          title: "Update Failed",
          text: result.message || "Failed to update",
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 3000,
        });
        cancelEdit();
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX error:", { xhr, status, error });
      console.error("Response text:", xhr.responseText);
      Swal.fire({
        icon: "error",
        title: "Network Error",
        text: "Error updating field: " + error,
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
      });
      cancelEdit();
    },
  });
}

function updateCellContent($cell, field, value) {
  const displayValue = value || "-";
  let html = "";

  if (field === "story_status") {
    // Update status badge with icons
    const statusClasses = {
      Draft: "bg-secondary",
      Editing: "bg-warning",
      Review: "bg-info",
      Approved: "bg-success",
      Final: "bg-primary",
    };
    const statusClass = statusClasses[value] || "bg-secondary";

    // Get icon from field options
    let iconHtml = "";
    const fieldOptions = getFieldOptions(field);
    if (fieldOptions) {
      const option = fieldOptions.find((opt) => opt.value === value);
      if (option && option.icon) {
        iconHtml = `<span class="badge-icon">${option.icon}</span> `;
      }
    }

    html = `<span class="badge ${statusClass} editable-content" data-value="${value}">${iconHtml}${
      value || "Draft"
    }</span>`;
  } else {
    // Regular text content with truncation matching initial page load
    let displayText = displayValue;

    // Apply same truncation as PHP character_limiter() function
    if (field === "content" || field === "dialogues") {
      // Truncate to 30 characters like the initial page load
      if (value && value.length > 30) {
        displayText = value.substring(0, 30) + "...";
      } else {
        displayText =
          value || (field === "content" ? "No content" : "No dialogues");
      }
    } else if (
      field === "sound" ||
      field === "equipment" ||
      field === "lighting" ||
      field === "note"
    ) {
      // Truncate to 15 characters for these fields
      if (value && value.length > 15) {
        displayText = value.substring(0, 15) + "...";
      } else {
        displayText = value || "Not set";
      }
    } else if (field === "duration") {
      // Duration field - add "s" suffix
      displayText = value ? value + " s" : "Not set";
    } else {
      // Other fields - no truncation but show "Not set" if empty
      displayText = value || "Not set";
    }

    // Get icon from field options
    let iconHtml = "";
    const fieldOptions = getFieldOptions(field);
    if (fieldOptions && value) {
      const option = fieldOptions.find((opt) => opt.value === value);
      if (option && option.icon) {
        iconHtml = `<span class="field-icon">${option.icon}</span> `;
      }
    }

    // Use <p> tag for content and dialogues to match initial HTML structure
    if (field === "content" || field === "dialogues") {
      html = `<p class="text-sm text-gray-900 truncate editable-content" data-full-value="${
        value || ""
      }">${iconHtml}${displayText}</p>`;
    } else {
      // Use <span> for other fields
      html = `<span class="text-sm text-gray-900 editable-content truncate block" data-full-value="${
        value || ""
      }">${iconHtml}${displayText}</span>`;
    }
  }

  // Clear editing state and global lock
  $cell.removeClass("editing editing-highlight").html(html);

  // Apply special styling for content and dialogues cells
  if (field === "content" || field === "dialogues") {
    $cell.css({
      "vertical-align": "top",
      padding: "8px",
      "max-width": "250px",
      height: "auto",
      "min-height": "auto",
    });
  }

  // Clean up stored original content after successful save
  const cellId = $cell.data("id");
  if (originalContent[cellId] && originalContent[cellId][field]) {
    delete originalContent[cellId][field];
    // Clean up the entire cellId object if it's empty
    if (Object.keys(originalContent[cellId]).length === 0) {
      delete originalContent[cellId];
    }
  }

  currentlyEditing = null;
  $("body").removeClass("has-active-edit");

  // Remove editing classes from table to restore normal layout
  const $table = $cell.closest(".storyboard-table");
  const $tableContainer = $cell.closest(".storyboard-table-container");

  $table.removeClass("table-editing").addClass("table-fixed");
  $tableContainer.removeClass("table-container-editing");

  console.log("Removed editing classes from table after save");

  // Recheck spacing after content update
  setTimeout(() => {
    applyDynamicSpacing();
  }, 100);
}

function cancelEdit() {
  if (!currentlyEditing) return;

  const $cell = $(currentlyEditing);
  const cellId = $cell.data("id");
  const field = $cell.data("field");

  console.log("Canceling edit for:", field, "id:", cellId);

  // Clean up custom dropdown event listeners
  $(document).off("click.custom-dropdown");
  $(document).off("keydown.custom-dropdown");
  $(window).off("scroll.custom-dropdown");

  // Re-enable page scroll in case dropdown was open
  enablePageScroll();
  cleanupDropdownSpacing();

  // Restore original HTML content from stored data
  if (originalContent[cellId] && originalContent[cellId][field]) {
    const originalData = originalContent[cellId][field];
    // Restore the complete original HTML structure
    $cell.html(originalData.html);
    console.log("Restored original HTML content");
  } else {
    // Fallback: just remove editing class
    $cell.removeClass("editing");
    console.log("No original content found, using fallback");
  }

  // Clean up stored content
  if (originalContent[cellId]) {
    delete originalContent[cellId][field];
    // Clean up the entire cellId object if it's empty
    if (Object.keys(originalContent[cellId]).length === 0) {
      delete originalContent[cellId];
    }
  }

  // IMPORTANT: Clear the editing state and global lock
  $cell.removeClass("editing editing-highlight");
  currentlyEditing = null;
  $("body").removeClass("has-active-edit");

  // Remove editing classes from table to restore normal layout
  const $table = $cell.closest(".storyboard-table");
  const $tableContainer = $cell.closest(".storyboard-table-container");

  $table.removeClass("table-editing").addClass("table-fixed");
  $tableContainer.removeClass("table-container-editing");

  console.log("Removed editing classes from table");
  console.log("Edit canceled, currentlyEditing cleared, global lock removed");
}

// Click outside to cancel edit
$(document).on("click", function (e) {
  if (
    currentlyEditing &&
    !$(e.target).closest(".editable-cell").length &&
    !$(e.target).closest(".inline-edit-buttons").length &&
    !$(e.target).hasClass("cancel-edit") &&
    !$(e.target).hasClass("save-edit")
  ) {
    cancelEdit();
  }
});
// Image viewer functionality - only initialize in storyboard context
let currentImages = [];
let currentImageIndex = 0;

// Check if we should initialize image viewer functionality
function shouldInitializeImageViewer() {
  // Don't initialize on task pages
  if (window.location.href.includes("/tasks/")) {
    return false;
  }

  // Only initialize if we have storyboard elements or are on storyboard page
  return (
    $(".storyboard-table").length > 0 ||
    window.location.href.includes("/storyboard/") ||
    document.getElementById("imageViewer") !== null ||
    $('img[onclick*="showImageModal"]').length > 0
  );
}

// Initialize image modal functionality
$(document).ready(function () {
  // Always initialize image viewer on storyboard pages
  initializeImageViewer();
});

// Initialize image viewer functionality
function initializeImageViewer() {
  // Check if we should initialize storyboard image viewer
  const isStoryboardPage =
    $(".storyboard-table").length > 0 ||
    document.getElementById("imageViewer") !== null ||
    window.location.href.includes("/storyboard/");

  if (isStoryboardPage) {
    console.log("Initializing storyboard image viewer");

    // Define showImageModal function globally
    window.showImageModal = function (imageSrc) {
      console.log("showImageModal called with:", imageSrc);

      if (!imageSrc) {
        console.error("No image source provided");
        return false;
      }

      // Collect all storyboard frame images for navigation
      currentImages = [];
      $('.storyboard-table img[onclick*="showImageModal"]').each(function () {
        const onclick = $(this).attr("onclick");
        if (onclick) {
          const match = onclick.match(/showImageModal\('([^']+)'\)/);
          if (match) {
            currentImages.push(match[1]);
          }
        }
      });

      // Find current image index
      currentImageIndex = currentImages.indexOf(imageSrc);
      if (currentImageIndex === -1) {
        currentImageIndex = 0;
        currentImages = [imageSrc];
      }

      console.log(
        "Found",
        currentImages.length,
        "storyboard images, current index:",
        currentImageIndex
      );

      // Show the image viewer
      showImageViewer();
      return true;
    };

    // Also handle direct image clicks without onclick attribute
    $(document).on("click", ".storyboard-table img", function (e) {
      const imageSrc = $(this).attr("src");
      if (imageSrc && !$(this).attr("onclick")) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Direct image click detected:", imageSrc);
        window.showImageModal(imageSrc);
      }
    });
  } else {
    console.log(
      "Not on storyboard page - skipping image viewer initialization"
    );
  }
}

function showImageViewer() {
  // Safety check - don't run on task pages
  if (window.location.href.includes("/tasks/")) {
    console.log("Skipping image viewer on tasks page");
    return;
  }

  const viewer = document.getElementById("imageViewer");
  const image = document.getElementById("viewerImage");
  const counter = document.getElementById("imageCounter");

  if (!viewer || !image) {
    console.error(
      "Image viewer elements not found. Required elements: imageViewer, viewerImage"
    );

    // Try to create a simple modal if elements don't exist
    if (currentImages.length > 0) {
      const imageSrc = currentImages[currentImageIndex];
      console.warn(
        "Image viewer modal not found, opening in new tab:",
        imageSrc
      );
      window.open(imageSrc, "_blank");
    }
    return;
  }

  // Set image source
  image.src = currentImages[currentImageIndex];

  // Update counter if it exists
  if (counter) {
    counter.textContent = `${currentImageIndex + 1} / ${currentImages.length}`;
  }

  // Show/hide navigation arrows
  const prevBtn = document.querySelector(".image-viewer-prev");
  const nextBtn = document.querySelector(".image-viewer-next");

  if (prevBtn && nextBtn) {
    prevBtn.style.display = currentImages.length > 1 ? "flex" : "none";
    nextBtn.style.display = currentImages.length > 1 ? "flex" : "none";
  }

  // Show viewer with fade in effect
  try {
    viewer.style.display = "flex";

    setTimeout(() => {
      viewer.classList.add("show");
    }, 10);

    // Disable page scrolling
    document.body.style.overflow = "hidden";

    // Add keyboard event listeners
    document.addEventListener("keydown", handleImageViewerKeydown);

    // Re-initialize feather icons
    if (typeof feather !== "undefined") {
      feather.replace();
    }

    console.log("Image viewer shown successfully");
  } catch (error) {
    console.error("Error showing image viewer:", error);

    // Fallback to opening in new tab
    const imageSrc = currentImages[currentImageIndex];
    window.open(imageSrc, "_blank");
  }
}

function closeImageViewer() {
  // Safety check - don't run on task pages
  if (window.location.href.includes("/tasks/")) {
    return;
  }

  const viewer = document.getElementById("imageViewer");
  if (!viewer) return;

  // Fade out effect
  viewer.classList.remove("show");

  setTimeout(() => {
    viewer.style.display = "none";
    document.body.style.overflow = "auto";
  }, 300);

  // Remove keyboard event listeners
  document.removeEventListener("keydown", handleImageViewerKeydown);

  console.log("Image viewer closed");
}

function navigateImage(direction) {
  // Safety check - don't run on task pages or expenses pages
  if (
    window.location.href.includes("/tasks/") ||
    window.location.href.includes("/expenses/")
  ) {
    return;
  }

  // Only handle if we have the storyboard image viewer elements
  if (
    !document.getElementById("imageViewer") ||
    !document.getElementById("viewerImage")
  ) {
    return;
  }

  if (currentImages.length <= 1) return;

  currentImageIndex += direction;

  // Loop around
  if (currentImageIndex >= currentImages.length) {
    currentImageIndex = 0;
  } else if (currentImageIndex < 0) {
    currentImageIndex = currentImages.length - 1;
  }

  console.log("Navigating to image index:", currentImageIndex);
  showImageViewer();
}

// Handle keyboard navigation for image viewer
function handleImageViewerKeydown(e) {
  switch (e.key) {
    case "Escape":
      closeImageViewer();
      break;
    case "ArrowLeft":
      e.preventDefault();
      navigateImage(-1);
      break;
    case "ArrowRight":
      e.preventDefault();
      navigateImage(1);
      break;
  }
}

// Click outside image to close viewer
$(document).on("click", ".image-viewer-overlay", function (e) {
  if (e.target === this) {
    closeImageViewer();
  }
});

// Video preview functionality
$(document).on("click", ".preview-video-btn-index", function () {
  const videoUrl = $(this).data("video-url");
  const videoName = $(this).data("video-name");

  // Set video source and info
  $("#video-source").attr("src", videoUrl);
  $("#video-filename").text(videoName);
  $("#download-video-btn").attr("href", videoUrl);
  $("#video-metadata").text("Loading video information...");

  // Load video and show modal
  const video = document.getElementById("preview-video");

  // Clear previous event listeners
  video.removeEventListener("loadedmetadata", updateVideoMetadata);

  // Add error handling
  video.addEventListener("error", function (e) {
    console.error("Video error:", e);
    $("#video-metadata").text("Error loading video. Please try again.");
  });

  // Add loading event
  video.addEventListener("loadstart", function () {
    $("#video-metadata").text("Loading video...");
  });

  // Add can play event
  video.addEventListener("canplay", function () {
    $("#video-metadata").text("Video ready to play");
  });

  video.load();

  // Show modal with higher z-index to appear above storyboard modal
  $("#video-preview-modal").modal("show");

  // Update metadata when video loads
  video.addEventListener("loadedmetadata", updateVideoMetadata);
});

// Shared function to update video metadata
function updateVideoMetadata() {
  const video = document.getElementById("preview-video");
  const duration = formatVideoDuration(video.duration);
  const dimensions = video.videoWidth + "x" + video.videoHeight;
  $("#video-metadata").text(
    `Duration: ${duration} • Resolution: ${dimensions}`
  );
}

// Handle fullscreen for shared video modal
$(document).on("click", "#fullscreen-btn", function () {
  const video = document.getElementById("preview-video");
  if (video.requestFullscreen) {
    video.requestFullscreen();
  } else if (video.webkitRequestFullscreen) {
    video.webkitRequestFullscreen();
  } else if (video.msRequestFullscreen) {
    video.msRequestFullscreen();
  }
});

// Pause video when modal is closed
$("#video-preview-modal").on("hidden.bs.modal", function () {
  const video = document.getElementById("preview-video");
  video.pause();
  video.currentTime = 0;
});

// Format video duration helper
function formatVideoDuration(seconds) {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = Math.floor(seconds % 60);

  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, "0")}:${secs
      .toString()
      .padStart(2, "0")}`;
  } else {
    return `${minutes}:${secs.toString().padStart(2, "0")}`;
  }
}

// Keyboard navigation for image viewer
document.addEventListener("keydown", function (e) {
  const viewer = document.getElementById("imageViewer");
  if (viewer && viewer.style.display === "flex") {
    switch (e.key) {
      case "Escape":
        closeImageViewer();
        break;
      case "ArrowLeft":
        navigateImage(-1);
        break;
      case "ArrowRight":
        navigateImage(1);
        break;
    }
  }
});

// Close image viewer on background click
$(document).on("click", "#imageViewer", function (e) {
  if (e.target === this) {
    closeImageViewer();
  }
});

// Field Options Management
function openFieldOptionsModal() {
  console.log("openFieldOptionsModal called");

  // Check if modal exists
  const modal = document.getElementById("field-options-modal");
  if (!modal) {
    console.error("Field options modal not found in DOM");
    alert(
      "Error: Field options modal not found. Please check if the modal is included in the page."
    );
    return;
  }

  console.log("Modal found, attempting to show...");

  try {
    $("#field-options-modal").modal("show");
    loadFieldOptions("story_status");
    console.log("Modal show successful");
  } catch (error) {
    console.error("Error showing field options modal:", error);
    alert("Error opening field options modal: " + error.message);
  }
}

function loadFieldOptions(fieldType) {
  // Update active field type
  $("#field-type-list .list-group-item").removeClass("active");
  $(`#field-type-list [data-field="${fieldType}"]`).addClass("active");

  // Update title
  const fieldTitles = {
    story_status: "Status Options",
    shot_size: "Shot Size Options",
    shot_type: "Shot Type Options",
    movement: "Movement Options",
    framerate: "Frame Rate Options",
  };
  $("#current-field-title").text(fieldTitles[fieldType]);

  // Load options for this field
  const options = fieldOptionsData[fieldType] || [];
  let html = "";

  options.forEach((option, index) => {
    html += `
            <div class="option-item mb-4 p-4 border rounded shadow-sm" data-index="${index}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary">
                            <i class="fas fa-tag me-1"></i>Value
                        </label>
                        <input type="text" class="form-control option-value" value="${
                          option.value
                        }" placeholder="e.g., draft, full-shot">
                        <small class="text-muted">Internal value used in database</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary">
                            <i class="fas fa-eye me-1"></i>Display Label
                        </label>
                        <input type="text" class="form-control option-label" value="${
                          option.label
                        }" placeholder="e.g., Draft, Full Shot">
                        <small class="text-muted">Text shown to users</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-primary">
                            <i class="fas fa-smile me-1"></i>Icon
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control option-icon" value="${
                              option.icon || ""
                            }" placeholder="Type emoji or use button" data-index="${index}" onchange="updateIconPreview(${index})">
                            <button type="button" class="btn btn-outline-secondary emoji-picker-btn" onclick="openEmojiModal(${index})" title="Choose Emoji">
                                <i class="fas fa-smile"></i>
                            </button>
                            <span class="input-group-text icon-preview" data-index="${index}">${
      option.icon ? option.icon : ""
    }</span>
                        </div>
                        <small class="text-muted">Click emoji button to choose or type manually</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-primary">
                            <i class="fas fa-palette me-1"></i>Color
                        </label>
                        <div class="color-picker-wrapper">
                            <div class="color-display-container" onclick="openColorPicker(${index})">
                                <div class="color-display" style="background-color: ${
                                  option.color || "#007bff"
                                }">
                                    <div class="color-info">
                                        <div class="color-preview-circle"></div>
                                        <span class="color-value">${
                                          option.color || "#007bff"
                                        }</span>
                                    </div>
                                </div>
                            </div>
                            <input type="color" class="form-control option-color d-none" value="${
                              option.color || "#007bff"
                            }" onchange="updateColorDisplay(this, ${index})">
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-mouse-pointer me-1"></i>Click color box to open picker or choose preset
                        </small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary">
                            <i class="fas fa-cogs me-1"></i>Actions
                        </label>
                        <div class="d-flex justify-content-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; background: none;">
                                    <i data-feather="more-vertical" class="icon-14"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="moveOption(${index}, 'up'); return false;">
                                            <i data-feather="arrow-up" class="icon-14 me-2"></i>Move Up
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="moveOption(${index}, 'down'); return false;">
                                            <i data-feather="arrow-down" class="icon-14 me-2"></i>Move Down
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" onclick="removeOption(${index}); return false;">
                                            <i data-feather="trash-2" class="icon-14 me-2"></i>Remove Option
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
  });

  $("#options-list").html(html);

  // Re-initialize feather icons
  if (typeof feather !== "undefined") {
    feather.replace();
  }

  // Add real-time preview updates
  setupOptionPreviewUpdates();
}

function addNewOption() {
  const activeField = $("#field-type-list .list-group-item.active").data(
    "field"
  );
  if (!fieldOptionsData[activeField]) {
    fieldOptionsData[activeField] = [];
  }

  fieldOptionsData[activeField].push({
    value: "",
    label: "",
    color: "#007bff",
    icon: "",
  });

  loadFieldOptions(activeField);

  // Auto-focus on the new option's value field after a short delay
  setTimeout(() => {
    const $newOption = $("#options-list .option-item:last-child");
    const $valueInput = $newOption.find(".option-value");

    // Scroll to the new option
    $newOption[0].scrollIntoView({ behavior: "smooth", block: "center" });

    // Focus and highlight the input
    $valueInput.focus().select();

    // Add a subtle highlight animation
    $newOption.addClass("new-option-highlight");
    setTimeout(() => {
      $newOption.removeClass("new-option-highlight");
    }, 2000);
  }, 100);
}

function removeOption(index) {
  const activeField = $("#field-type-list .list-group-item.active").data(
    "field"
  );

  Swal.fire({
    title: "Remove Option?",
    text: "Are you sure you want to remove this option?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, remove it",
  }).then((result) => {
    if (result.isConfirmed) {
      fieldOptionsData[activeField].splice(index, 1);
      loadFieldOptions(activeField);
    }
  });
}

function moveOption(index, direction) {
  const activeField = $("#field-type-list .list-group-item.active").data(
    "field"
  );
  const options = fieldOptionsData[activeField];

  if (direction === "up" && index > 0) {
    [options[index], options[index - 1]] = [options[index - 1], options[index]];
  } else if (direction === "down" && index < options.length - 1) {
    [options[index], options[index + 1]] = [options[index + 1], options[index]];
  }

  loadFieldOptions(activeField);
}

function saveFieldOptions() {
  // Collect all current values from the form
  const activeField = $("#field-type-list .list-group-item.active").data(
    "field"
  );
  const updatedOptions = [];

  $("#options-list .option-item").each(function () {
    const $item = $(this);
    const option = {
      value: $item.find(".option-value").val().trim(),
      label: $item.find(".option-label").val().trim(),
      icon: $item.find(".option-icon").val().trim(),
      color: $item.find(".option-color").val(),
    };

    if (option.value && option.label) {
      updatedOptions.push(option);
    }
  });

  // Save to database
  $.ajax({
    url: get_uri("storyboard/save_field_options"),
    type: "POST",
    data: {
      field_type: activeField,
      options: JSON.stringify(updatedOptions),
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        fieldOptionsData[activeField] = updatedOptions;

        Swal.fire({
          icon: "success",
          title: "Options Saved!",
          text: "Field options have been updated successfully.",
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Save Failed",
          text: response.message || "Failed to save field options",
          confirmButtonText: "OK",
          confirmButtonColor: "#dc3545",
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Error saving field options:", error);
      Swal.fire({
        icon: "error",
        title: "Network Error",
        text: "Error saving field options: " + error,
        confirmButtonText: "OK",
        confirmButtonColor: "#dc3545",
      });
    },
  });
}

// Color picker functions
function openColorPicker(index) {
  const $optionItem = $(`.option-item[data-index="${index}"]`);
  const $colorInput = $optionItem.find(".option-color");
  $colorInput.click();
}

function updateColorDisplay(colorInput, index) {
  const $colorInput = $(colorInput);
  const $optionItem = $(`.option-item[data-index="${index}"]`);
  const $colorDisplay = $optionItem.find(".color-display");
  const $colorValue = $optionItem.find(".color-value");
  const $colorPreviewCircle = $optionItem.find(".color-preview-circle");

  const newColor = $colorInput.val();
  $colorDisplay.css("background-color", newColor);
  $colorPreviewCircle.css("background-color", newColor);
  $colorValue.text(newColor.toUpperCase());

  // Add a subtle animation to show the change
  $colorDisplay.addClass("color-updated");
  setTimeout(() => {
    $colorDisplay.removeClass("color-updated");
  }, 300);
}

function updateIconPreview(index) {
  const $optionItem = $(`.option-item[data-index="${index}"]`);
  const $iconInput = $optionItem.find(".option-icon");
  const $iconPreview = $optionItem.find(".icon-preview");

  const iconValue = $iconInput.val().trim();
  if (iconValue) {
    $iconPreview.text(iconValue).removeAttr("data-empty");
  } else {
    $iconPreview.text("").attr("data-empty", "true");
  }
}

// Setup real-time preview updates
function setupOptionPreviewUpdates() {
  // Icon preview updates
  $(".option-icon").on("input", function () {
    const $input = $(this);
    const $preview = $input.siblings(".input-group").find(".icon-preview");
    const iconValue = $input.val().trim();

    if (iconValue) {
      $preview.text(iconValue).removeAttr("data-empty");
    } else {
      $preview.text("").attr("data-empty", "true");
    }
  });

  // Auto-fill label from value
  $(".option-value").on("input", function () {
    const $valueInput = $(this);
    const $labelInput = $valueInput
      .closest(".option-item")
      .find(".option-label");

    // Only auto-fill if label is empty
    if (!$labelInput.val().trim()) {
      const value = $valueInput.val();
      // Convert snake_case or kebab-case to Title Case
      const label = value
        .replace(/[_-]/g, " ")
        .replace(/\b\w/g, (l) => l.toUpperCase());
      $labelInput.val(label);
    }
  });
}

// Load field options from database
function loadFieldOptionsFromDB() {
  const fieldTypes = [
    "story_status",
    "shot_size",
    "shot_type",
    "movement",
    "framerate",
  ];

  console.log("Loading field options from database for fields:", fieldTypes);

  fieldTypes.forEach((fieldType) => {
    $.ajax({
      url: get_uri("storyboard/get_field_options"),
      type: "GET",
      data: { field_type: fieldType },
      dataType: "json",
      success: function (response) {
        console.log(`Database response for ${fieldType}:`, response);

        if (
          response.success &&
          response.options &&
          Array.isArray(response.options) &&
          response.options.length > 0
        ) {
          // Successfully loaded from database
          fieldOptionsData[fieldType] = response.options;

          // Make sure the global window object also has the data
          if (!window.fieldOptionsData) {
            window.fieldOptionsData = {};
          }
          window.fieldOptionsData[fieldType] = response.options;

          console.log(
            ` Successfully loaded ${response.options.length} options for ${fieldType} from database:`,
            response.options
          );
        } else {
          console.warn(
            `⚠️ No valid options found in database for ${fieldType}, using fallback options`
          );
          setFallbackOptions(fieldType);
        }
      },
      error: function (xhr, status, error) {
        console.error(`❌ Error loading field options for ${fieldType}:`, {
          status: status,
          error: error,
          responseText: xhr.responseText,
        });

        // Set fallback options if database request fails
        setFallbackOptions(fieldType);
      },
    });
  });
}

// Function to set fallback options when database is empty or fails
function setFallbackOptions(fieldType) {
  const fallbackOptions = {
    story_status: [
      { value: "Draft", label: "Draft", color: "#6c757d", icon: "" },
      { value: "Editing", label: "Editing", color: "#ffc107", icon: "" },
      { value: "Review", label: "Review", color: "#17a2b8", icon: "" },
      { value: "Approved", label: "Approved", color: "#28a745", icon: "" },
      { value: "Final", label: "Final", color: "#007bff", icon: "" },
    ],
    shot_size: [
      { value: "Full Shot", label: "Full Shot", color: "", icon: "🧍" },
      { value: "Medium Shot", label: "Medium Shot", color: "", icon: "👤" },
      { value: "Close-up", label: "Close-up", color: "", icon: "😊" },
      {
        value: "Extreme Close-up",
        label: "Extreme Close-up",
        color: "",
        icon: "👁️",
      },
      { value: "Wide Shot", label: "Wide Shot", color: "", icon: "🏞️" },
      { value: "Long Shot", label: "Long Shot", color: "", icon: "🌄" },
    ],
    shot_type: [
      { value: "Eye Level", label: "Eye Level", color: "", icon: "👀" },
      { value: "High Angle", label: "High Angle", color: "", icon: "⬆️" },
      { value: "Low Angle", label: "Low Angle", color: "", icon: "⬇️" },
      { value: "Bird's Eye", label: "Bird's Eye", color: "", icon: "🦅" },
    ],
    movement: [
      { value: "Static", label: "Static", color: "", icon: "⏸️" },
      { value: "Pan", label: "Pan", color: "", icon: "↔️" },
      { value: "Tilt", label: "Tilt", color: "", icon: "↕️" },
      { value: "Tracking", label: "Tracking", color: "", icon: "🚂" },
    ],
    framerate: [
      { value: "24fps", label: "24 FPS", color: "", icon: "🎬" },
      { value: "30fps", label: "30 FPS", color: "", icon: "📹" },
      { value: "60fps", label: "60 FPS", color: "", icon: "⚡" },
      { value: "120fps", label: "120 FPS", color: "", icon: "🚀" },
    ],
  };

  if (fallbackOptions[fieldType]) {
    fieldOptionsData[fieldType] = fallbackOptions[fieldType];
    if (!window.fieldOptionsData) {
      window.fieldOptionsData = {};
    }
    window.fieldOptionsData[fieldType] = fallbackOptions[fieldType];
    console.log(`Using fallback options for ${fieldType}`);
  }
}

// Emoji picker functionality
let emojiCache = null;

async function fetchEmojis() {
  if (emojiCache) {
    return emojiCache;
  }

  try {
    const response = await fetch("https://emojidb.org/refresh-emojis");
    if (!response.ok) {
      throw new Error("Failed to fetch emojis");
    }
    const data = await response.json();
    emojiCache = data;
    return data;
  } catch (error) {
    console.error("Error fetching emojis:", error);
    // Fallback emojis if API fails
    return {
      emojis: [
        { emoji: "😀", name: "grinning face" },
        { emoji: "😃", name: "grinning face with big eyes" },
        { emoji: "😄", name: "grinning face with smiling eyes" },
        { emoji: "😁", name: "beaming face with smiling eyes" },
        { emoji: "😆", name: "grinning squinting face" },
        { emoji: "😅", name: "grinning face with sweat" },
        { emoji: "🤣", name: "rolling on the floor laughing" },
        { emoji: "😂", name: "face with tears of joy" },
        { emoji: "🙂", name: "slightly smiling face" },
        { emoji: "🙃", name: "upside down face" },
        { emoji: "😉", name: "winking face" },
        { emoji: "😊", name: "smiling face with smiling eyes" },
        { emoji: "😇", name: "smiling face with halo" },
        { emoji: "🥰", name: "smiling face with hearts" },
        { emoji: "😍", name: "smiling face with heart-eyes" },
        { emoji: "🤩", name: "star-struck" },
        { emoji: "😘", name: "face blowing a kiss" },
        { emoji: "😗", name: "kissing face" },
        { emoji: "☺️", name: "smiling face" },
        { emoji: "😚", name: "kissing face with closed eyes" },
        { emoji: "😙", name: "kissing face with smiling eyes" },
        { emoji: "🥲", name: "smiling face with tear" },
        { emoji: "😋", name: "face savoring food" },
        { emoji: "😛", name: "face with tongue" },
        { emoji: "😜", name: "winking face with tongue" },
        { emoji: "🤪", name: "zany face" },
        { emoji: "😝", name: "squinting face with tongue" },
        { emoji: "🤑", name: "money-mouth face" },
        { emoji: "🤗", name: "hugging face" },
        { emoji: "🤭", name: "face with hand over mouth" },
        { emoji: "🤫", name: "shushing face" },
        { emoji: "🤔", name: "thinking face" },
        { emoji: "🎬", name: "clapper board" },
        { emoji: "🎭", name: "performing arts" },
        { emoji: "🎨", name: "artist palette" },
        { emoji: "🎪", name: "circus tent" },
        { emoji: "🎯", name: "direct hit" },
        { emoji: "🎲", name: "game die" },
        { emoji: "🎮", name: "video game" },
        { emoji: "🎸", name: "guitar" },
        { emoji: "🎹", name: "musical keyboard" },
        { emoji: "🎤", name: "microphone" },
        { emoji: "🎧", name: "headphone" },
        { emoji: "📝", name: "memo" },
        { emoji: "📋", name: "clipboard" },
        { emoji: "📌", name: "pushpin" },
        { emoji: "📍", name: "round pushpin" },
        { emoji: "📎", name: "paperclip" },
        { emoji: "🔗", name: "link" },
        { emoji: "📁", name: "file folder" },
        { emoji: "📂", name: "open file folder" },
        { emoji: "🗂️", name: "card index dividers" },
        { emoji: "🗃️", name: "card file box" },
        { emoji: "🗄️", name: "file cabinet" },
        { emoji: "📊", name: "bar chart" },
        { emoji: "📈", name: "chart increasing" },
        { emoji: "📉", name: "chart decreasing" },
        { emoji: "⭐", name: "star" },
        { emoji: "🌟", name: "glowing star" },
        { emoji: "✨", name: "sparkles" },
        { emoji: "💫", name: "dizzy" },
        { emoji: "⚡", name: "high voltage" },
        { emoji: "🔥", name: "fire" },
        { emoji: "💎", name: "gem stone" },
        { emoji: "🏆", name: "trophy" },
        { emoji: "🥇", name: "1st place medal" },
        { emoji: "🥈", name: "2nd place medal" },
        { emoji: "🥉", name: "3rd place medal" },
        { emoji: "🎖️", name: "military medal" },
        { emoji: "🏅", name: "sports medal" },
        { emoji: "🎗️", name: "reminder ribbon" },
        { emoji: "🎀", name: "ribbon" },
      ],
    };
  }
}

// Global variable to track which input is being edited
let currentEmojiInputIndex = null;
let allEmojis = [];

async function openEmojiModal(index) {
  currentEmojiInputIndex = index;

  // Show the modal
  const modal = new bootstrap.Modal(
    document.getElementById("emoji-picker-modal")
  );
  modal.show();

  // Load emojis if not already loaded
  if (allEmojis.length === 0) {
    await loadEmojisInModal();
  } else {
    // Show already loaded emojis
    document.getElementById("emoji-modal-loading").style.display = "none";
    document.getElementById("emoji-modal-content").style.display = "block";
  }
}

async function loadEmojisInModal() {
  const loading = document.getElementById("emoji-modal-loading");
  const content = document.getElementById("emoji-modal-content");
  const grid = document.getElementById("emoji-modal-grid");

  loading.style.display = "block";
  content.style.display = "none";

  try {
    const emojiData = await fetchEmojis();
    allEmojis = emojiData.emojis || emojiData;

    displayEmojis(allEmojis);

    loading.style.display = "none";
    content.style.display = "block";
  } catch (error) {
    loading.innerHTML =
      '<span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load emojis</span>';
  }
}

function displayEmojis(emojis) {
  const grid = document.getElementById("emoji-modal-grid");
  let html = "";

  emojis.forEach((emojiObj) => {
    const emoji = typeof emojiObj === "string" ? emojiObj : emojiObj.emoji;
    const name = typeof emojiObj === "object" ? emojiObj.name : "";
    html += `<button type="button" class="emoji-item" onclick="selectEmojiFromModal('${emoji}')" title="${name}">${emoji}</button>`;
  });

  grid.innerHTML = html;
}

function selectEmojiFromModal(emoji) {
  if (currentEmojiInputIndex !== null) {
    const $optionItem = $(
      `.option-item[data-index="${currentEmojiInputIndex}"]`
    );
    const $iconInput = $optionItem.find(".option-icon");
    const $iconPreview = $optionItem.find(".icon-preview");

    $iconInput.val(emoji);
    $iconPreview.text(emoji).removeAttr("data-empty");
  }

  // Close the modal
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("emoji-picker-modal")
  );
  modal.hide();

  // Reset search
  document.getElementById("emoji-search").value = "";
}

function filterEmojis() {
  const searchTerm = document
    .getElementById("emoji-search")
    .value.toLowerCase();

  if (searchTerm === "") {
    displayEmojis(allEmojis);
    return;
  }

  const filteredEmojis = allEmojis.filter((emojiObj) => {
    const name = typeof emojiObj === "object" ? emojiObj.name : "";
    return name.toLowerCase().includes(searchTerm);
  });

  displayEmojis(filteredEmojis);
}

function clearEmoji() {
  if (currentEmojiInputIndex !== null) {
    const $optionItem = $(
      `.option-item[data-index="${currentEmojiInputIndex}"]`
    );
    const $iconInput = $optionItem.find(".option-icon");
    const $iconPreview = $optionItem.find(".icon-preview");

    $iconInput.val("");
    $iconPreview.text("").attr("data-empty", "true");
  }

  // Close the modal
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("emoji-picker-modal")
  );
  modal.hide();
}
// Debug function to test field options
function debugFieldOptions() {
  console.log("=== Field Options Debug ===");
  console.log("jQuery loaded:", typeof $ !== "undefined");
  console.log("Bootstrap loaded:", typeof bootstrap !== "undefined");
  console.log("SweetAlert loaded:", typeof Swal !== "undefined");

  const modal = document.getElementById("field-options-modal");
  console.log("Modal element found:", !!modal);

  if (modal) {
    console.log("Modal classes:", modal.className);
    console.log("Modal style display:", modal.style.display);
  }

  console.log("fieldOptionsData:", fieldOptionsData);
  console.log("openFieldOptionsModal function:", typeof openFieldOptionsModal);

  // Test the function
  try {
    openFieldOptionsModal();
  } catch (error) {
    console.error("Error calling openFieldOptionsModal:", error);
  }
}

// Make debug functions available globally
window.debugFieldOptions = debugFieldOptions;

// Debug function to test image modal
function debugImageModal() {
  console.log("=== Image Modal Debug ===");
  console.log("showImageModal function:", typeof window.showImageModal);
  console.log("imageViewer element:", !!document.getElementById("imageViewer"));
  console.log("viewerImage element:", !!document.getElementById("viewerImage"));
  console.log(
    "imageCounter element:",
    !!document.getElementById("imageCounter")
  );

  // Check for different types of images
  const thumbnails = $(".storyboard-table .img-thumbnail");
  const allImages = $(".storyboard-table img");
  const clickableImages = $('img[onclick*="showImageModal"]');

  console.log("Found .img-thumbnail images:", thumbnails.length);
  console.log("Found all images in storyboard table:", allImages.length);
  console.log(
    "Found clickable images with showImageModal:",
    clickableImages.length
  );

  if (clickableImages.length > 0) {
    const firstImage = clickableImages.first();
    const imageSrc = firstImage.attr("src");
    console.log("First clickable image src:", imageSrc);
    console.log("First clickable image onclick:", firstImage.attr("onclick"));
    console.log("First clickable image classes:", firstImage.attr("class"));

    // Test the function
    if (window.showImageModal) {
      console.log("Testing showImageModal with first clickable image...");
      try {
        window.showImageModal(imageSrc);
      } catch (error) {
        console.error("Error testing showImageModal:", error);
      }
    }
  } else if (allImages.length > 0) {
    const firstImage = allImages.first();
    const imageSrc = firstImage.attr("src");
    console.log(
      "No clickable images found, testing with first image:",
      imageSrc
    );

    if (window.showImageModal) {
      try {
        window.showImageModal(imageSrc);
      } catch (error) {
        console.error("Error testing showImageModal:", error);
      }
    }
  } else {
    console.log("No images found in storyboard table");
  }
}

// Quick test function to show modal with a test image
function testImageModal() {
  console.log("Testing image modal with placeholder image...");
  const testImageSrc =
    "https://via.placeholder.com/800x600/0066cc/ffffff?text=Test+Image";

  if (window.showImageModal) {
    try {
      window.showImageModal(testImageSrc);
      console.log("Test image modal called successfully");
    } catch (error) {
      console.error("Error testing image modal:", error);
    }
  } else {
    console.error("showImageModal function not found");
  }
}

window.debugImageModal = debugImageModal;
window.testImageModal = testImageModal;

// Function to populate field icons on initial page load
function populateFieldIcons() {
  console.log("Populating field icons on page load...");

  // Fields that have icons
  const fieldsWithIcons = [
    "shot_size",
    "shot_type",
    "movement",
    "framerate",
    "story_status",
  ];

  fieldsWithIcons.forEach(function (fieldName) {
    // Get field options for this field
    const fieldOptions = window.fieldOptionsData
      ? window.fieldOptionsData[fieldName]
      : null;

    if (!fieldOptions) {
      console.log(`No field options found for ${fieldName}`);
      return;
    }

    // Find all cells for this field
    $(`.editable-cell[data-field="${fieldName}"]`).each(function () {
      const $cell = $(this);
      const $content = $cell.find(".editable-content");
      const $iconPlaceholder = $content.find(".field-icon-php");

      // Get the current value - use data-value for status badge, data-full-value for others
      let currentValue;
      if (fieldName === "story_status") {
        currentValue = $content.attr("data-value") || $content.text().trim();
      } else {
        currentValue =
          $content.attr("data-full-value") || $content.text().trim();
      }

      // Skip if no value or "Not set"
      if (!currentValue || currentValue === "Not set") {
        return;
      }

      // Find the matching option
      const option = fieldOptions.find((opt) => opt.value === currentValue);

      if (option && option.icon) {
        // Add the icon
        if ($iconPlaceholder.length > 0) {
          $iconPlaceholder.html(option.icon + " ");
        } else {
          // If no placeholder, prepend icon to content
          const currentHtml = $content.html();
          $content.html(
            `<span class="field-icon">${option.icon}</span> ${currentHtml}`
          );
        }

        console.log(
          `Added icon "${option.icon}" for ${fieldName}: ${currentValue}`
        );
      }
    });
  });

  console.log("Field icons populated");
}

// Make it globally accessible for debugging
window.populateFieldIcons = populateFieldIcons;

// Frame Upload Functionality
function triggerFrameUpload(storyboardId) {
  console.log("Triggering frame upload for storyboard:", storyboardId);
  const fileInput = document.getElementById(`frame-upload-${storyboardId}`);
  if (fileInput) {
    fileInput.click();
  }
}

// Handle frame upload file selection
$(document).on("change", ".frame-upload-input", function (e) {
  const file = e.target.files[0];
  const storyboardId = $(this).data("storyboard-id");

  if (!file) {
    return;
  }

  // Validate file type
  if (!file.type.startsWith("image/")) {
    Swal.fire({
      icon: "error",
      title: "Invalid File",
      text: "Please select an image file.",
      confirmButtonText: "OK",
    });
    return;
  }

  // Validate file size (max 10MB)
  if (file.size > 10 * 1024 * 1024) {
    Swal.fire({
      icon: "error",
      title: "File Too Large",
      text: "Image size must be less than 10MB.",
      confirmButtonText: "OK",
    });
    return;
  }

  console.log(
    "Uploading frame for storyboard:",
    storyboardId,
    "File:",
    file.name
  );
  uploadFrameImage(storyboardId, file);
});

// Upload frame image with progress
function uploadFrameImage(storyboardId, file) {
  const uploadArea = $(
    `.frame-upload-area[data-storyboard-id="${storyboardId}"]`
  );
  const progressContainer = uploadArea.find(".upload-progress");
  const progressBar = uploadArea.find(".upload-progress-bar");
  const progressText = uploadArea.find(".upload-progress-text");

  // Show progress
  progressContainer.removeClass("hidden");
  progressBar.css("width", "0%");
  progressText.text("Uploading...");

  // Create form data
  const formData = new FormData();
  formData.append("frame", file);
  formData.append("id", storyboardId);

  // Upload with progress
  $.ajax({
    url: get_uri("storyboard/upload_frame"),
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    xhr: function () {
      const xhr = new window.XMLHttpRequest();
      // Upload progress
      xhr.upload.addEventListener(
        "progress",
        function (e) {
          if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            progressBar.css("width", percentComplete + "%");
            progressText.text(`Uploading... ${Math.round(percentComplete)}%`);
          }
        },
        false
      );
      return xhr;
    },
    success: function (response) {
      console.log("Frame upload response:", response);

      // Parse response if it's a string
      if (typeof response === "string") {
        try {
          response = JSON.parse(response);
        } catch (e) {
          console.error("Failed to parse response:", e);
        }
      }

      if (response && response.success) {
        progressText.text("Upload complete!");
        progressBar.css("width", "100%");

        // Show success message
        Swal.fire({
          icon: "success",
          title: "Image Uploaded!",
          text: "Frame image uploaded successfully.",
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true,
        });

        // Update the cell with the new image instead of reloading
        setTimeout(function () {
          updateFrameCell(storyboardId, response.file_name);
        }, 500);
      } else {
        progressContainer.addClass("hidden");
        Swal.fire({
          icon: "error",
          title: "Upload Failed",
          text: response.message || "Failed to upload image.",
          confirmButtonText: "OK",
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Frame upload error:", error);
      progressContainer.addClass("hidden");

      Swal.fire({
        icon: "error",
        title: "Upload Error",
        text: "An error occurred while uploading the image.",
        confirmButtonText: "OK",
      });
    },
  });
}

// Update frame cell with uploaded image
function updateFrameCell(storyboardId, fileName) {
  console.log(
    "Updating frame cell for storyboard:",
    storyboardId,
    "with file:",
    fileName
  );

  const uploadArea = $(
    `.frame-upload-area[data-storyboard-id="${storyboardId}"]`
  );
  const imageContainer = uploadArea.parent();

  if (!imageContainer.length) {
    console.error("Image container not found");
    return;
  }

  // Build the image URL - use base URL without index.php for static files
  // Get base URL by removing /index.php from get_uri result
  let imageUrl = get_uri("files/storyboard_frames/" + fileName);
  imageUrl = imageUrl.replace("/index.php/", "/");

  console.log("Image URL:", imageUrl);

  // Create new image HTML with edit button
  const newImageHtml = `
    <div class="relative">
      <img src="${imageUrl}" 
           class="w-40 h-32 object-cover rounded shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer"
           onclick="showImageModal('${imageUrl}')"
           alt="Storyboard frame">
      
      <button type="button" 
              class="edit-image-btn absolute top-0 right-0 bg-white/90 hover:bg-white text-gray-700 rounded p-1 text-xs opacity-0 group-hover:opacity-100 transition-all duration-200 shadow-sm hover:shadow" 
              data-storyboard-id="${storyboardId}"
              title="Edit Image">
        <i data-feather="edit-3" class="w-3 h-3"></i>
      </button>
    </div>
  `;

  // Replace the upload area with the image
  imageContainer.html(newImageHtml);

  // Re-render feather icons
  if (typeof feather !== "undefined") {
    feather.replace();
  }

  console.log("Frame cell updated successfully");
}

// Make functions globally available
window.triggerFrameUpload = triggerFrameUpload;
window.uploadFrameImage = uploadFrameImage;
window.updateFrameCell = updateFrameCell;

// Handle storyboard modal form submission
$(document).on("submit", "#storyboard-form", function (e) {
  e.preventDefault();

  const form = $(this);
  const formData = new FormData(this);
  const storyboardId = form.find('input[name="id"]').val();

  console.log("Storyboard form submitted, ID:", storyboardId);

  // Show loading state
  const submitBtn = form.find('button[type="submit"]');
  const originalBtnText = submitBtn.html();
  submitBtn
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin"></i> Saving...');

  $.ajax({
    url: form.attr("action"),
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (response) {
      console.log("Storyboard save response:", response);

      // Parse response if string
      if (typeof response === "string") {
        try {
          response = JSON.parse(response);
        } catch (e) {
          console.error("Failed to parse response:", e);
        }
      }

      if (response && response.success) {
        // Show success message
        Swal.fire({
          icon: "success",
          title: "Saved!",
          text: response.message || "Storyboard updated successfully.",
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
        });

        // Close modal
        $("#storyboard-modal").modal("hide");

        // Update the table row instead of reloading
        if (storyboardId && response.id) {
          updateStoryboardRow(response.id);
        } else {
          // New storyboard - reload to show it
          setTimeout(function () {
            location.reload();
          }, 1000);
        }
      } else {
        // Show error
        Swal.fire({
          icon: "error",
          title: "Save Failed",
          text: response.message || "Failed to save storyboard.",
          confirmButtonText: "OK",
        });

        // Restore button
        submitBtn.prop("disabled", false).html(originalBtnText);
      }
    },
    error: function (xhr, status, error) {
      console.error("Storyboard save error:", error);

      Swal.fire({
        icon: "error",
        title: "Error",
        text: "An error occurred while saving.",
        confirmButtonText: "OK",
      });

      // Restore button
      submitBtn.prop("disabled", false).html(originalBtnText);
    },
  });

  return false;
});

// Update a single storyboard row after edit
function updateStoryboardRow(storyboardId) {
  console.log("Updating storyboard row:", storyboardId);

  // Simple approach: just reload the page content without full reload
  // This preserves scroll position and is faster than full reload
  location.reload();
}

// Make function globally available
window.updateStoryboardRow = updateStoryboardRow;
