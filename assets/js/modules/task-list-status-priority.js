/**
 * Task List Status & Priority Module
 * Handles status and priority dropdown functionality
 */

// Initialize status dropdowns
function initStatusDropdowns() {
  $(document).on("click", ".status-option", function (e) {
    e.preventDefault();
    console.log("🔥 STATUS OPTION CLICKED!");
    
    var taskId = $(this).data("task-id");
    var statusId = $(this).data("status-id"); // Use status-id directly from database
    
    if (!statusId) {
      // Fallback: try old data-status attribute for backward compatibility
      var newStatus = $(this).data("status");
      switch (newStatus) {
        case "to_do":
          statusId = 1;
          break;
        case "in_progress":
          statusId = 2;
          break;
        case "done":
          statusId = 3;
          break;
        default:
          statusId = 1;
      }
    }

    var $badge = $(
      '.status-badge[data-task-id="' +
        taskId +
        '"], .jira-status-badge[data-task-id="' +
        taskId +
        '"]'
    );

    console.log("🎯 Status change details:", {
      taskId: taskId,
      newStatus: newStatus,
      statusId: statusId,
      element: this,
      badgeFound: $badge.length,
    });

    console.log("🚀 Sending status AJAX request:", {
      task_id: taskId,
      status_id: statusId,
    });

    // Use the same URL pattern as other updates
    var directUrl =
      baseUrl.replace("/index.php/", "/") + "update-new-feature/update_task_status_direct.php";
    console.log("🔗 Status update URL:", directUrl);

    $.ajax({
      url: directUrl,
      type: "POST",
      dataType: "json",
      data: {
        task_id: taskId,
        status_id: statusId,
      },
      success: function (response) {
        console.log("Status update response:", response);
        if (response && response.success) {
          // Update badge appearance using real response data
          var statusText = response.status_text || "UNKNOWN";
          var statusColor = response.status_color || "#DFE1E6";
          
          // Determine text color based on background color
          var textColor = '#FFFFFF';
          if (['#ffffff', '#f0f0f0', '#dfe1e6', '#f4f5f7', '#fafbfc'].includes(statusColor.toLowerCase())) {
            textColor = '#42526E';
          }

          // Update the badge with real database values
          $badge.css({
            'background': statusColor,
            'color': textColor
          }).text(statusText);

          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Task status updated to ' + statusText,
            confirmButtonColor: '#0052CC',
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
        } else {
          console.error("Status update failed:", response);
          Swal.fire({
            icon: 'error',
            title: 'Update Failed',
            text: 'Failed to update task status: ' + (response.message || "Unknown error"),
            confirmButtonColor: '#0052CC'
          });
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", error, xhr.responseText);
        Swal.fire({
          icon: 'error',
          title: 'Connection Error',
          text: 'Error updating task status: ' + error,
          confirmButtonColor: '#0052CC'
        });
      },
    });
  });

  console.log(" Status dropdowns initialized");
}

// Initialize priority dropdowns
function initPriorityDropdowns() {
  $(document).on("click", ".priority-option", function (e) {
    e.preventDefault();
    console.log("🔥 PRIORITY OPTION CLICKED!");
    var taskId = $(this).data("task-id");
    var newPriority = $(this).data("priority");
    var $badge = $(
      '.priority-badge[data-task-id="' +
        taskId +
        '"], .jira-priority-badge[data-task-id="' +
        taskId +
        '"]'
    );

    console.log("🎯 Priority change details:", {
      taskId: taskId,
      newPriority: newPriority,
      element: this,
      badgeFound: $badge.length,
    });

    console.log("🚀 Sending priority AJAX request:", {
      task_id: taskId,
      priority_id: newPriority,
    });

    // Use the same URL pattern as other updates
    var directUrl =
      baseUrl.replace("/index.php/", "/") + "update-new-feature/update_task_priority_direct.php";
    console.log("🔗 Priority update URL:", directUrl);

    $.ajax({
      url: directUrl,
      type: "POST",
      dataType: "json",
      data: {
        task_id: taskId,
        priority_id: newPriority,
      },
      success: function (response) {
        console.log("Priority update response:", response);
        if (response && response.success) {
          // Update badge appearance with proper icon
          var newIconSvg = getPriorityIcon(response.priority_icon || 'arrow-up');
          $badge.css("color", response.priority_color);
          $badge.find("span").first().html(newIconSvg);
          $badge.find("span").last().text(response.priority_text.toUpperCase());

          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Task priority updated to ' + response.priority_text,
            confirmButtonColor: '#0052CC',
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
        } else {
          console.error("Priority update failed:", response);
          Swal.fire({
            icon: 'error',
            title: 'Update Failed',
            text: 'Failed to update task priority: ' + (response.message || "Unknown error"),
            confirmButtonColor: '#0052CC'
          });
        }
      },
      error: function (xhr, status, error) {
        console.error("Priority AJAX error:", error, xhr.responseText);
        Swal.fire({
          icon: 'error',
          title: 'Connection Error',
          text: 'Error updating task priority: ' + error,
          confirmButtonColor: '#0052CC'
        });
      },
    });
  });

  console.log(" Priority dropdowns initialized");
}

// Fetch status data from database and create dropdown
function fetchStatusDataAndCreateDropdown(taskId, $badge) {
  // Get base URL for API calls
  var pathParts = window.location.pathname.split('/');
  var appIndex = pathParts.indexOf('dojob');
  var baseUrl = window.location.origin + '/' + pathParts.slice(1, appIndex + 1).join('/');
  
  // Fetch statuses from database
  $.ajax({
    url: baseUrl + '/update-new-feature/get_statuses_direct.php',
    type: 'GET',
    success: function(response) {
      if (response && response.success && response.statuses) {
        createStatusDropdownWithRealData(taskId, $badge, response.statuses);
      } else {
        console.error("Failed to fetch statuses, using fallback");
        createStatusDropdownFallback(taskId, $badge);
      }
    },
    error: function(xhr, status, error) {
      console.error("Failed to fetch statuses:", error);
      createStatusDropdownFallback(taskId, $badge);
    }
  });
}

// Create status dropdown with real database data
function createStatusDropdownWithRealData(taskId, $badge, statuses) {
  var dropdownHtml = `
    <div class="dropdown">
      <span class="jira-status-badge dropdown-toggle" 
            data-bs-toggle="dropdown" 
            data-task-id="${taskId}"
            style="cursor: pointer; ${$badge.attr("style") || ""}"
            title="Click to change status">
        ${$badge.html()}
      </span>
      <ul class="dropdown-menu">`;
  
  statuses.forEach(function(status) {
    // Determine text color based on background color
    var textColor = '#FFFFFF';
    if (['#ffffff', '#f0f0f0', '#dfe1e6', '#f4f5f7', '#fafbfc'].includes(status.color.toLowerCase())) {
      textColor = '#42526E';
    }
    
    dropdownHtml += `
      <li><a class="dropdown-item status-option" href="#" data-task-id="${taskId}" data-status-id="${status.id}">
        <span style="background: ${status.color}; color: ${textColor}; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;">${status.title.toUpperCase()}</span>
      </a></li>`;
  });
  
  dropdownHtml += `
      </ul>
    </div>`;
  
  // Replace the badge with dropdown
  $badge.replaceWith(dropdownHtml);
}

// Fallback dropdown with basic statuses
function createStatusDropdownFallback(taskId, $badge) {
  var dropdownHtml = `
    <div class="dropdown">
      <span class="jira-status-badge dropdown-toggle" 
            data-bs-toggle="dropdown" 
            data-task-id="${taskId}"
            style="cursor: pointer; ${$badge.attr("style") || ""}"
            title="Click to change status">
        ${$badge.html()}
      </span>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item status-option" href="#" data-task-id="${taskId}" data-status-id="1">
          <span style="background: #DFE1E6; color: #42526E; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;">TO DO</span>
        </a></li>
        <li><a class="dropdown-item status-option" href="#" data-task-id="${taskId}" data-status-id="2">
          <span style="background: #0052CC; color: #FFFFFF; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;">IN PROGRESS</span>
        </a></li>
        <li><a class="dropdown-item status-option" href="#" data-task-id="${taskId}" data-status-id="3">
          <span style="background: #36B37E; color: #FFFFFF; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;">DONE</span>
        </a></li>
      </ul>
    </div>`;
  
  // Replace the badge with dropdown
  $badge.replaceWith(dropdownHtml);
}

// Convert static status badges to clickable dropdowns
function convertStatusBadgesToDropdowns() {
  console.log("🔄 Converting static status badges to dropdowns...");

  // Find all static status badges that aren't already dropdowns
  $(".jira-status-badge, .status-badge").each(function () {
    var $badge = $(this);

    // Skip if already converted
    if ($badge.data("dropdown-converted")) {
      return;
    }

    // Get task ID from the row
    var $row = $badge.closest("tr");
    var taskId = $row.data("task-id");

    if (!taskId) {
      console.log("⚠️ No task ID found for badge:", $badge);
      return;
    }

    console.log("🎯 Converting badge for task:", taskId);

    // Get current status from badge text
    var currentText = $badge.text().trim().toUpperCase();
    var currentStatus = "to_do";
    if (currentText.includes("PROGRESS")) currentStatus = "in_progress";
    else if (currentText.includes("DONE")) currentStatus = "done";

    // Fetch real status data from database and create dropdown
    fetchStatusDataAndCreateDropdown(taskId, $badge);

    // Replace the badge with dropdown
    $badge.replaceWith(dropdownHtml);
    $badge.data("dropdown-converted", true);

    console.log(" Converted badge for task:", taskId);
  });

  console.log("🎉 Status badge conversion complete!");
}

// Convert static priority icons to clickable dropdowns using real database data
function convertPriorityIconsToDropdowns() {
  console.log("🔄 Converting static priority icons to dropdowns...");

  // First, fetch priorities from database with cache busting
  var apiUrl = window.location.origin + '/dojob/update-new-feature/get_priorities_direct.php?v=' + Date.now();
  console.log("🔄 Fetching priorities from:", apiUrl);
  
  $.ajax({
    url: apiUrl,
    type: 'GET',
    cache: false,
    success: function(response) {
      console.log("📋 Priorities API response:", response);
      if (response && response.success && response.priorities) {
        console.log(" Using real database priorities:", response.priorities);
        
        // Set up global priority mapping for other parts of the system
        setupGlobalPriorityMapping(response.priorities);
        
        renderPriorityDropdowns(response.priorities);
      } else {
        console.error("❌ API returned invalid data:", response);
        // Fallback to hardcoded priorities if API fails
        renderPriorityDropdownsFallback();
      }
    },
    error: function(xhr, status, error) {
      console.error("❌ Failed to fetch priorities - Status:", status, "Error:", error);
      console.error("❌ Response text:", xhr.responseText);
      // Fallback to hardcoded priorities if API fails
      renderPriorityDropdownsFallback();
    }
  });
}

// Set up global priority mapping for use by other parts of the system
function setupGlobalPriorityMapping(priorities) {
  console.log("🌐 Setting up global priority mapping...");
  
  window.priorityMapping = {};
  priorities.forEach(function(priority) {
    window.priorityMapping[priority.id] = {
      id: priority.id,
      text: priority.title.toUpperCase(),
      color: priority.color,
      icon: priority.icon
    };
  });
  
  console.log(" Global priority mapping set up:", window.priorityMapping);
}

// Render priority dropdowns with real database data
function renderPriorityDropdowns(priorities) {
  console.log("🎨 Rendering priority dropdowns with real data:", priorities);

  // Find all priority cells that contain SVG icons
  $("td").each(function () {
    var $cell = $(this);
    var $svg = $cell.find('svg[stroke="#FFAB00"]'); // Find lightning bolt icons

    if ($svg.length > 0) {
      // Get task ID from the row
      var $row = $cell.closest("tr");
      var taskId = $row.data("task-id");

      if (!taskId) {
        console.log("⚠️ No task ID found for priority icon:", $cell);
        return;
      }

      console.log("🎯 Converting priority icon for task:", taskId);

      // Get current priority from data attribute or default to 1 (Minor)
      var currentPriorityId = $row.data("current-priority") || 1;
      var currentPriority = priorities.find(p => p.id == currentPriorityId) || priorities[0];

      // Create priority options HTML
      var priorityOptionsHtml = '';
      priorities.forEach(function(priority) {
        var iconSvg = getPriorityIcon(priority.icon);
        priorityOptionsHtml += `
          <li><a class="dropdown-item priority-option" href="#" data-task-id="${taskId}" data-priority="${priority.id}">
            <span style="color: ${priority.color}; display: flex; align-items: center; gap: 6px;">
              <span style="display: inline-flex; align-items: center;">${iconSvg}</span>
              <span>${priority.title.toUpperCase()}</span>
            </span>
          </a></li>
        `;
      });

      var currentIconSvg = getPriorityIcon(currentPriority.icon);
      var dropdownHtml = `
        <div class="dropdown">
          <span class="jira-priority-badge dropdown-toggle" 
                data-bs-toggle="dropdown" 
                data-task-id="${taskId}"
                style="cursor: pointer; color: ${currentPriority.color}; display: inline-flex; align-items: center; gap: 6px;"
                title="Click to change priority">
            <span style="display: inline-flex; align-items: center;">${currentIconSvg}</span>
            <span style="font-size: 11px; font-weight: 600;">${currentPriority.title.toUpperCase()}</span>
          </span>
          <ul class="dropdown-menu">
            ${priorityOptionsHtml}
          </ul>
        </div>
      `;

      // Replace the cell content
      $cell.html(dropdownHtml);

      console.log(" Converted priority icon for task:", taskId);
    }
  });

  console.log("🎉 Priority icon conversion complete with real data!");
}

// Convert priority icon names to proper SVG icons or symbols
function getPriorityIcon(iconName) {
  switch(iconName) {
    case 'arrow-down': 
      return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>';
    case 'arrow-up': 
      return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';
    case 'alert-circle': 
      return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    case 'alert-octagon': 
      return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    default: 
      return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
  }
}

// Fallback function with hardcoded priorities (in case API fails)
function renderPriorityDropdownsFallback() {
  console.log("🔄 Using fallback hardcoded priorities...");

  // Find all priority cells that contain SVG icons
  $("td").each(function () {
    var $cell = $(this);
    var $svg = $cell.find('svg[stroke="#FFAB00"]'); // Find lightning bolt icons

    if ($svg.length > 0) {
      // Get task ID from the row
      var $row = $cell.closest("tr");
      var taskId = $row.data("task-id");

      if (!taskId) {
        console.log("⚠️ No task ID found for priority icon:", $cell);
        return;
      }

      console.log("🎯 Converting priority icon for task:", taskId);

      // Replace the cell content with dropdown
      var arrowUpSvg = getPriorityIcon('arrow-up');
      var arrowDownSvg = getPriorityIcon('arrow-down');
      var alertCircleSvg = getPriorityIcon('alert-circle');
      var alertOctagonSvg = getPriorityIcon('alert-octagon');
      
      var dropdownHtml = `
                <div class="dropdown">
                    <span class="jira-priority-badge dropdown-toggle" 
                          data-bs-toggle="dropdown" 
                          data-task-id="${taskId}"
                          style="cursor: pointer; color: #e18a00; display: inline-flex; align-items: center; gap: 6px;"
                          title="Click to change priority">
                        <span style="display: inline-flex; align-items: center;">${arrowUpSvg}</span>
                        <span style="font-size: 11px; font-weight: 600;">MAJOR</span>
                    </span>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item priority-option" href="#" data-task-id="${taskId}" data-priority="1">
                            <span style="color: #aab7b7; display: flex; align-items: center; gap: 6px;">
                              <span style="display: inline-flex; align-items: center;">${arrowDownSvg}</span>
                              <span>MINOR</span>
                            </span>
                        </a></li>
                        <li><a class="dropdown-item priority-option" href="#" data-task-id="${taskId}" data-priority="2">
                            <span style="color: #e18a00; display: flex; align-items: center; gap: 6px;">
                              <span style="display: inline-flex; align-items: center;">${arrowUpSvg}</span>
                              <span>MAJOR</span>
                            </span>
                        </a></li>
                        <li><a class="dropdown-item priority-option" href="#" data-task-id="${taskId}" data-priority="3">
                            <span style="color: #ad159e; display: flex; align-items: center; gap: 6px;">
                              <span style="display: inline-flex; align-items: center;">${alertCircleSvg}</span>
                              <span>CRITICAL</span>
                            </span>
                        </a></li>
                        <li><a class="dropdown-item priority-option" href="#" data-task-id="${taskId}" data-priority="4">
                            <span style="color: #e74c3c; display: flex; align-items: center; gap: 6px;">
                              <span style="display: inline-flex; align-items: center;">${alertOctagonSvg}</span>
                              <span>BLOCKER</span>
                            </span>
                        </a></li>
                    </ul>
                </div>
            `;

      // Replace the cell content
      $cell.html(dropdownHtml);

      console.log(" Converted priority icon for task:", taskId);
    }
  });

  console.log("🎉 Priority icon conversion complete (fallback)!");
}

// Manual test function for debugging
window.testPriorityAPI = function() {
  console.log(" Manual priority API test...");
  convertPriorityIconsToDropdowns();
};

// Make functions globally accessible
window.initStatusDropdowns = initStatusDropdowns;
window.initPriorityDropdowns = initPriorityDropdowns;
window.convertStatusBadgesToDropdowns = convertStatusBadgesToDropdowns;
window.convertPriorityIconsToDropdowns = convertPriorityIconsToDropdowns;
window.renderPriorityDropdowns = renderPriorityDropdowns;
window.renderPriorityDropdownsFallback = renderPriorityDropdownsFallback;
