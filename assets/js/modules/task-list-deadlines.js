/**
 * Task List Deadlines Module
 * Handles deadline picker and management functionality
 */

// Show deadline picker
function showDeadlinePicker($container, taskId, currentDeadline) {
  console.log("📅 Showing deadline picker for task:", taskId);

  // Create full calendar picker HTML
  var pickerHtml =
    '<div class="deadline-picker" style="' +
    "position: absolute; " +
    "top: 100%; " +
    "left: 0; " +
    "background: white; " +
    "border: 1px solid #DFE1E6; " +
    "border-radius: 8px; " +
    "box-shadow: 0 8px 16px rgba(0,0,0,0.15); " +
    "z-index: 9999; " +
    "min-width: 280px; " +
    "padding: 16px;" +
    '">';

  // Calendar header
  var today = new Date();
  var currentMonth = today.getMonth();
  var currentYear = today.getFullYear();
  
  // If there's an existing deadline, start from that month
  if (currentDeadline && currentDeadline !== '0000-00-00') {
    var existingDate = new Date(currentDeadline);
    if (!isNaN(existingDate.getTime())) {
      currentMonth = existingDate.getMonth();
      currentYear = existingDate.getFullYear();
    }
  }

  var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'];

  pickerHtml += '<div class="calendar-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">';
  pickerHtml += '<button class="prev-month" style="background: none; border: none; cursor: pointer; padding: 4px; border-radius: 3px;">';
  pickerHtml += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B778C" stroke-width="2"><polyline points="15,18 9,12 15,6"></polyline></svg>';
  pickerHtml += '</button>';
  pickerHtml += '<span class="month-year" style="font-weight: 600; color: #172B4D;">' + monthNames[currentMonth] + ' ' + currentYear + '</span>';
  pickerHtml += '<button class="next-month" style="background: none; border: none; cursor: pointer; padding: 4px; border-radius: 3px;">';
  pickerHtml += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B778C" stroke-width="2"><polyline points="9,18 15,12 9,6"></polyline></svg>';
  pickerHtml += '</button>';
  pickerHtml += '</div>';

  // Day headers
  pickerHtml += '<div class="calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; margin-bottom: 12px;">';
  var dayHeaders = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
  dayHeaders.forEach(function(day) {
    pickerHtml += '<div style="text-align: center; font-size: 11px; color: #6B778C; font-weight: 600; padding: 4px;">' + day + '</div>';
  });

  // Generate calendar days
  var firstDay = new Date(currentYear, currentMonth, 1);
  var lastDay = new Date(currentYear, currentMonth + 1, 0);
  var startDate = new Date(firstDay);
  startDate.setDate(startDate.getDate() - firstDay.getDay());

  for (var i = 0; i < 42; i++) { // 6 weeks
    var date = new Date(startDate);
    date.setDate(startDate.getDate() + i);
    
    var isCurrentMonth = date.getMonth() === currentMonth;
    var isToday = date.toDateString() === today.toDateString();
    var isSelected = false;
    
    if (currentDeadline && currentDeadline !== '0000-00-00') {
      var selectedDate = new Date(currentDeadline);
      isSelected = date.toDateString() === selectedDate.toDateString();
    }
    
    var dayStyle = 'text-align: center; padding: 6px; cursor: pointer; border-radius: 3px; font-size: 13px; transition: all 0.2s;';
    
    if (!isCurrentMonth) {
      dayStyle += 'color: #C1C7D0;';
    } else if (isSelected) {
      dayStyle += 'background: #0052CC; color: white; font-weight: 600;';
    } else if (isToday) {
      dayStyle += 'background: #E3FCEF; color: #00875A; font-weight: 600;';
    } else {
      dayStyle += 'color: #172B4D;';
      dayStyle += 'hover: background: #F4F5F7;';
    }
    
    // Fix timezone issue: format date manually instead of using toISOString()
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, '0');
    var day = String(date.getDate()).padStart(2, '0');
    var dateString = year + '-' + month + '-' + day;
    
    pickerHtml += '<div class="calendar-day" data-date="' + dateString + '" style="' + dayStyle + '" onmouseover="if(this.style.background !== \'rgb(0, 82, 204)\' && this.style.background !== \'rgb(227, 252, 239)\') this.style.background=\'#F4F5F7\'" onmouseout="if(this.style.background === \'rgb(244, 245, 247)\') this.style.background=\'transparent\'">';
    pickerHtml += date.getDate();
    pickerHtml += '</div>';
  }

  pickerHtml += '</div>';

  // Action buttons
  pickerHtml += '<div style="display: flex; gap: 8px; margin-top: 12px;">';
  pickerHtml += '<button class="today-btn" style="background: #F4F5F7; color: #6B778C; border: 1px solid #DFE1E6; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; flex: 1;">Today</button>';
  
  if (currentDeadline && currentDeadline !== '0000-00-00') {
    pickerHtml += '<button class="clear-deadline-btn" style="background: #E74C3C; color: white; border: none; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; flex: 1;">Clear</button>';
  }
  
  pickerHtml += '<button class="cancel-deadline-btn" style="background: #F4F5F7; color: #6B778C; border: 1px solid #DFE1E6; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; flex: 1;">Cancel</button>';
  pickerHtml += '</div>';

  pickerHtml += '</div>';

  // Position container relatively and add picker
  $container.css("position", "relative");
  $container.append(pickerHtml);

  // Handle day clicks
  $(".calendar-day").on("click", function (e) {
    e.stopPropagation();
    
    var selectedDate = $(this).data('date');
    console.log("📅 Selected date:", selectedDate);

    // Save deadline
    saveTaskDeadline(taskId, selectedDate, function (success) {
      if (success) {
        console.log(" Deadline updated successfully");
        updateDeadlineDisplay($container, selectedDate);
      } else {
        console.error("❌ Failed to update deadline");
        Swal.fire({
          icon: 'error',
          title: 'Update Failed',
          text: 'Failed to update deadline',
          confirmButtonColor: '#0052CC'
        });
      }

      // Remove picker
      $(".deadline-picker").remove();
    });
  });

  // Handle Today button
  $(".today-btn").on("click", function (e) {
    e.stopPropagation();
    
    // Fix timezone issue: format today's date manually instead of using toISOString()
    var today = new Date();
    var year = today.getFullYear();
    var month = String(today.getMonth() + 1).padStart(2, '0');
    var day = String(today.getDate()).padStart(2, '0');
    var todayDate = year + '-' + month + '-' + day;
    
    saveTaskDeadline(taskId, todayDate, function (success) {
      if (success) {
        console.log(" Deadline set to today");
        updateDeadlineDisplay($container, todayDate);
      } else {
        console.error("❌ Failed to set deadline");
        Swal.fire({
          icon: 'error',
          title: 'Set Failed',
          text: 'Failed to set deadline',
          confirmButtonColor: '#0052CC'
        });
      }

      $(".deadline-picker").remove();
    });
  });

  // Handle Clear button (if present)
  $(".clear-deadline-btn").on("click", function (e) {
    e.stopPropagation();
    
    saveTaskDeadline(taskId, "", function (success) {
      if (success) {
        console.log(" Deadline cleared successfully");
        updateDeadlineDisplay($container, "");
      } else {
        console.error("❌ Failed to clear deadline");
        Swal.fire({
          icon: 'error',
          title: 'Clear Failed',
          text: 'Failed to clear deadline',
          confirmButtonColor: '#0052CC'
        });
      }

      $(".deadline-picker").remove();
    });
  });

  // Handle Cancel button
  $(".cancel-deadline-btn").on("click", function (e) {
    e.stopPropagation();
    $(".deadline-picker").remove();
  });

  // Handle month navigation
  $(".prev-month, .next-month").on("click", function (e) {
    e.stopPropagation();
    // For simplicity, just reload the picker
    // In a real implementation, you'd update the calendar view
    $(".deadline-picker").remove();
    showDeadlinePicker($container, taskId, currentDeadline);
  });

  // Handle clicks outside the deadline picker
  setTimeout(function() {
    $(document).on("click.deadline-picker", function (e) {
      if (!$(e.target).closest(".deadline-picker").length && 
          !$(e.target).closest(".task-deadline-container").length) {
        console.log("🚫 Clicking outside deadline picker - closing");
        $(".deadline-picker").remove();
        $(document).off("click.deadline-picker");
      }
    });
  }, 100);
}

// Save task deadline to server
function saveTaskDeadline(taskId, deadline, callback) {
  console.log("💾 Saving task deadline to server:", taskId, deadline);

  // Simple and reliable URL construction
  var directUrl = window.location.origin + '/update-new-feature/update_task_deadline_direct.php';
  
  console.log("🔗 Deadline Direct URL:", directUrl);

  $.ajax({
    url: directUrl,
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      task_id: taskId,
      deadline: deadline,
    }),
    success: function (response) {
      console.log(" Task deadline saved successfully:", response);
      if (response.success) {
        callback(true);
      } else {
        console.error("❌ Server returned error:", response.message);
        callback(false);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ Failed to save task deadline:", error);
      console.error("Response:", xhr.responseText);
      callback(false);
    },
  });
}

// Update deadline display after successful save
function updateDeadlineDisplay($container, deadline) {
  $container.data("current-deadline", deadline);

  var displayHtml = "";
  
  if (!deadline || deadline === "" || deadline === "0000-00-00") {
    // Empty cell with hover text
    displayHtml = '<div class="deadline-empty" style="' +
      "cursor: pointer; " +
      "padding: 8px; " +
      "text-align: center; " +
      "min-height: 28px; " +
      "border-radius: 3px; " +
      "transition: all 0.2s ease; " +
      "position: relative;" +
      '" title="Set deadline" onmouseover="this.innerHTML=\'Set deadline\'; this.style.color=\'#6B778C\'; this.style.fontSize=\'11px\';" onmouseout="this.innerHTML=\'\'; this.style.color=\'transparent\';">&nbsp;</div>';
  } else {
    // Format the deadline for display
    // Fix timezone issue by parsing date components explicitly
    var deadlineDate;
    if (deadline.includes('-')) {
      // Parse YYYY-MM-DD format to avoid timezone issues
      var parts = deadline.split('-');
      deadlineDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    } else {
      deadlineDate = new Date(deadline);
    }
    
    var formattedDate = deadlineDate.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
    
    // Check if deadline is overdue
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    deadlineDate.setHours(0, 0, 0, 0);
    
    var isOverdue = deadlineDate < today;
    var backgroundColor = isOverdue ? "#FFEBEE" : "#F4F5F7";
    var borderColor = isOverdue ? "#F44336" : "#DFE1E6";
    var textColor = isOverdue ? "#D32F2F" : "#172B4D";
    
    displayHtml = '<div class="deadline-display" style="' +
      "color: " + textColor + "; " +
      "cursor: pointer; " +
      "padding: 4px 8px; " +
      "border-radius: 3px; " +
      "font-size: 12px; " +
      "border: 1px solid " + borderColor + "; " +
      "background: " + backgroundColor + "; " +
      "text-align: center;" +
      '" title="Click to change deadline">' + formattedDate + '</div>';
  }

  // Update the container content
  $container.html(displayHtml);
}

// Make functions globally accessible
window.showDeadlinePicker = showDeadlinePicker;
window.saveTaskDeadline = saveTaskDeadline;
window.updateDeadlineDisplay = updateDeadlineDisplay;
