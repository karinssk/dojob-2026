// /**
//  * Task List JavaScript Functionality
//  * Clean, modular implementation
//  */

// // Global variables
// var taskListInitialized = false;

// // Initialize task list functionality
// function initTaskList() {
//     if (taskListInitialized) {
//         console.log('Task list already initialized');
//         return;
//     }
    
//     console.log('🚀 Initializing Task List...');
    
//     // Check dependencies
//     if (typeof $ === 'undefined') {
//         console.error('jQuery not loaded');
//         return;
//     }
    
//     if (typeof Sortable === 'undefined') {
//         console.error('SortableJS not loaded');
//         return;
//     }
    
//     taskListInitialized = true;
    
//     // Initialize all functionality
//     initDragDrop();
//     initStatusDropdowns();
//     initExpandCollapse();
//     initSearch();
//     initFilters();
    
//     console.log('✅ Task List initialized successfully');
// }

// // Initialize drag and drop
// function initDragDrop() {
//     var sortableEl = document.getElementById('sortable-tasks');
//     if (!sortableEl) {
//         console.error('Sortable element not found');
//         return;
//     }
    
//     var sortable = Sortable.create(sortableEl, {
//         handle: '.jira-drag-handle',
//         animation: 150,
//         ghostClass: 'sortable-ghost',
//         dragClass: 'sortable-drag',
//         chosenClass: 'sortable-chosen',
//         fallbackOnBody: true,
//         swapThreshold: 0.65,
//         direction: 'vertical',
        
//         onStart: function(evt) {
//             console.log('Drag started');
//             $(evt.item).addClass('dragging');
//         },
        
//         onEnd: function(evt) {
//             console.log('Drag ended');
//             $(evt.item).removeClass('dragging');
            
//             var taskId = $(evt.item).data('task-id');
//             var newIndex = evt.newIndex;
//             var oldIndex = evt.oldIndex;
            
//             if (newIndex !== oldIndex && taskId) {
//                 updateTaskHierarchy(taskId, newIndex, oldIndex);
//             }
//         }
//     });
    
//     console.log('✅ Drag & Drop initialized');
// }

// // Initialize status dropdowns
// function initStatusDropdowns() {
//     $(document).on('click', '.status-option', function(e) {
//         e.preventDefault();
//         var taskId = $(this).data('task-id');
//         var newStatus = $(this).data('status');
//         var $badge = $('.status-badge[data-task-id="' + taskId + '"]');
        
//         console.log('Status change:', taskId, newStatus);
        
//         // Map string status to status_id
//         var statusId = 1; // default to "to do"
//         switch(newStatus) {
//             case 'to_do':
//                 statusId = 1;
//                 break;
//             case 'in_progress':
//                 statusId = 2;
//                 break;
//             case 'done':
//                 statusId = 3;
//                 break;
//         }
        
//         $.ajax({
//             url: baseUrl + 'tasks/save_task_status/' + taskId,
//             type: 'POST',
//             dataType: 'json',
//             data: {
//                 value: statusId
//             },
//             success: function(response) {
//                 if (response && response.success) {
//                     // Update badge appearance
//                     var statusClass = 'secondary';
//                     var statusText = 'TO DO';
                    
//                     switch(newStatus) {
//                         case 'done':
//                             statusClass = 'success';
//                             statusText = 'DONE';
//                             break;
//                         case 'in_progress':
//                             statusClass = 'warning';
//                             statusText = 'IN PROGRESS';
//                             break;
//                         default:
//                             statusClass = 'secondary';
//                             statusText = 'TO DO';
//                     }
                    
//                     $badge.removeClass('bg-success bg-warning bg-secondary bg-info')
//                           .addClass('bg-' + statusClass)
//                           .text(statusText);
                    
//                     showNotification('Task status updated to ' + statusText, 'success');
//                 } else {
//                     showNotification('Failed to update task status', 'error');
//                 }
//             },
//             error: function(xhr, status, error) {
//                 console.error('AJAX error:', error);
//                 showNotification('Error updating task status', 'error');
//             }
//         });
//     });
    
//     console.log('✅ Status dropdowns initialized');
// }

// // Initialize expand/collapse functionality
// function initExpandCollapse() {
//     $(document).on('click', '.expand-toggle-jira', function(e) {
//         e.preventDefault();
//         var taskId = $(this).data('task-id');
//         var $row = $(this).closest('.task-row');
//         var level = parseInt($row.data('level'));
//         var $icon = $(this).find('.expand-icon');
        
//         console.log('Expand/collapse clicked:', taskId);
        
//         if ($row.hasClass('expanded')) {
//             // Collapse - hide children
//             hideChildRows(taskId, level);
//             $row.removeClass('expanded');
//             $icon.removeClass('rotated');
//         } else {
//             // Expand - show direct children
//             showDirectChildRows(taskId, level);
//             $row.addClass('expanded');
//             $icon.addClass('rotated');
//         }
//     });
    
//     // Initialize collapse state - start with all children visible
//     $('.task-row[data-has-children="true"]').each(function() {
//         $(this).addClass('expanded');
//         $(this).find('.expand-icon').addClass('rotated');
//     });
    
//     console.log('✅ Expand/collapse initialized');
// }

// // Initialize search functionality
// function initSearch() {
//     $(document).on('keyup', '.search-box input', function() {
//         var searchTerm = $(this).val().toLowerCase();
        
//         if (searchTerm === '') {
//             // Reset to current collapse state
//             $('.task-row').each(function() {
//                 if (!$(this).hasClass('collapsed-child')) {
//                     $(this).show();
//                 }
//             });
//         } else {
//             $('.task-row').each(function() {
//                 var taskTitle = $(this).find('.task-title-text').text().toLowerCase();
//                 var taskId = $(this).find('.task-key').text().toLowerCase();
                
//                 if (taskTitle.includes(searchTerm) || taskId.includes(searchTerm)) {
//                     $(this).show();
//                     // Also show parent if this child matches
//                     var parentId = $(this).data('parent-id');
//                     if (parentId > 0) {
//                         $('[data-task-id="' + parentId + '"]').show();
//                     }
//                 } else if (!$(this).hasClass('collapsed-child')) {
//                     $(this).hide();
//                 }
//             });
//         }
//     });
    
//     console.log('✅ Search initialized');
// }

// // Initialize filters
// function initFilters() {
//     $(document).on('click', '.filter-option', function(e) {
//         e.preventDefault();
//         var filterType = $(this).data('filter');
        
//         $('.task-row').each(function() {
//             var $row = $(this);
//             var statusBadge = $row.find('.status-badge').text().toLowerCase();
//             var shouldShow = false;
            
//             if (filterType === 'all') {
//                 shouldShow = true;
//             } else if (filterType === 'todo' && statusBadge.includes('to do')) {
//                 shouldShow = true;
//             } else if (filterType === 'in_progress' && statusBadge.includes('in progress')) {
//                 shouldShow = true;
//             } else if (filterType === 'review' && statusBadge.includes('review')) {
//                 shouldShow = true;
//             } else if (filterType === 'done' && statusBadge.includes('done')) {
//                 shouldShow = true;
//             }
            
//             if (shouldShow && !$row.hasClass('collapsed-child')) {
//                 $row.show();
//             } else {
//                 $row.hide();
//             }
//         });
        
//         // Update filter button text
//         var filterText = $(this).text();
//         $(this).closest('.dropdown').find('.dropdown-toggle').html('<i data-feather="filter" class="icon-16"></i> ' + filterText);
//         if (typeof feather !== 'undefined') {
//             feather.replace();
//         }
//     });
    
//     console.log('✅ Filters initialized');
// }

// // Helper functions
// function hideChildRows(parentId, parentLevel) {
//     var $allRows = $('.task-row');
//     var foundParent = false;
//     var hiddenCount = 0;
    
//     $allRows.each(function() {
//         if (foundParent) {
//             var currentLevel = parseInt($(this).data('level'));
//             if (currentLevel > parentLevel) {
//                 $(this).hide().addClass('collapsed-child');
//                 hiddenCount++;
//             } else {
//                 return false; // Stop when we reach same or higher level
//             }
//         }
        
//         if ($(this).data('task-id') == parentId) {
//             foundParent = true;
//         }
//     });
    
//     console.log('Hidden', hiddenCount, 'child rows for parent', parentId);
// }

// function showDirectChildRows(parentId, parentLevel) {
//     var $allRows = $('.task-row');
//     var foundParent = false;
//     var shownCount = 0;
    
//     $allRows.each(function() {
//         if (foundParent) {
//             var currentLevel = parseInt($(this).data('level'));
//             var currentParentId = parseInt($(this).data('parent-id'));
            
//             if (currentLevel > parentLevel) {
//                 // Only show direct children
//                 if (currentLevel === parentLevel + 1 && currentParentId == parentId) {
//                     $(this).show().removeClass('collapsed-child');
//                     shownCount++;
//                 }
//             } else {
//                 return false; // Stop when we reach same or higher level
//             }
//         }
        
//         if ($(this).data('task-id') == parentId) {
//             foundParent = true;
//         }
//     });
    
//     console.log('Shown', shownCount, 'direct child rows for parent', parentId);
// }

// function updateTaskHierarchy(taskId, newIndex, oldIndex) {
//     console.log('Updating hierarchy for task:', taskId);
    
//     var $allRows = $('.task-row');
//     var $movedRow = $('[data-task-id="' + taskId + '"]');
//     var newParentId = 0;
//     var newLevel = 0;
    
//     // Determine new parent and level based on position
//     if (newIndex > 0) {
//         var $prevRow = $allRows.eq(newIndex - 1);
//         var prevLevel = parseInt($prevRow.data('level')) || 0;
        
//         // Simple logic: make it sibling of previous task
//         newParentId = parseInt($prevRow.data('parent-id')) || 0;
//         newLevel = prevLevel;
//     }
    
//     // Update the row data attributes
//     $movedRow.attr('data-parent-id', newParentId);
//     $movedRow.attr('data-level', newLevel);
    
//     // Send AJAX request to update database
//     $.ajax({
//         url: baseUrl + 'tasks/save',
//         type: 'POST',
//         dataType: 'json',
//         data: {
//             id: taskId,
//             parent_task_id: newParentId,
//             project_id: projectId
//         },
//         success: function(response) {
//             if (response && response.success) {
//                 console.log('Task hierarchy updated successfully');
//                 showNotification('Task moved successfully!', 'success');
//             } else {
//                 console.error('Failed to update task hierarchy');
//                 setTimeout(function() {
//                     location.reload();
//                 }, 1500);
//             }
//         },
//         error: function(xhr, status, error) {
//             console.error('AJAX error:', error);
//             setTimeout(function() {
//                 location.reload();
//             }, 1500);
//         }
//     });
// }

// function showNotification(message, type) {
//     var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
//     var icon = type === 'success' ? 'check-circle' : 'x-circle';
    
//     var notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
//         '<i data-feather="' + icon + '" class="icon-16 me-2"></i>' + message +
//         '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
//         '</div>');
    
//     $('body').append(notification);
    
//     // Initialize feather icons
//     if (typeof feather !== 'undefined') {
//         feather.replace();
//     }
    
//     // Auto remove after 3 seconds
//     setTimeout(function() {
//         notification.alert('close');
//     }, 3000);
// }

// // Initialize when DOM is ready
// $(document).ready(function() {
//     // Wait a bit for all dependencies to load
//     setTimeout(initTaskList, 500);
// });