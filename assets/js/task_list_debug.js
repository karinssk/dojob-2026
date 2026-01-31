/**
 * Task List Debug Functions
 * Clean, working debug functions for testing
 */

// Test drag and drop functionality
window.testDragDrop = function() {
    console.log('=== 🧪 DRAG & DROP TEST ===');
    console.log('SortableJS available:', typeof Sortable !== 'undefined');
    console.log('jQuery available:', typeof $ !== 'undefined');
    console.log('Task table found:', $('#sortable-tasks').length > 0);
    console.log('Task rows found:', $('.task-item').length);
    console.log('Drag handles found:', $('.jira-drag-handle').length);
    
    var summary = ' Drag & Drop Test Results:\n\n' +
                 '• SortableJS: ' + (typeof Sortable !== 'undefined' ? 'Available' : 'Missing') + '\n' +
                 '• jQuery: ' + (typeof $ !== 'undefined' ? 'Available' : 'Missing') + '\n' +
                 '• Task table: ' + ($('#sortable-tasks').length > 0 ? 'Found' : 'Missing') + '\n' +
                 '• Task rows: ' + $('.task-item').length + '\n' +
                 '• Drag handles: ' + $('.jira-drag-handle').length + '\n\n' +
                 'Check console for detailed logs.';
    
    alert(summary);
};

// Debug current page state
window.debugCurrentState = function() {
    console.log('=== 🔍 CURRENT STATE DEBUG ===');
    
    // Analyze current DOM state
    var $taskRows = $('.task-item, .task-row, .jira-task-row');
    var $statusBadges = $('.status-badge');
    var $expandToggles = $('.expand-toggle-jira, .expand-toggle');
    var $dragHandles = $('.jira-drag-handle, .drag-handle');
    
    console.log('Task rows:', $taskRows.length);
    console.log('Status badges:', $statusBadges.length);
    console.log('Expand toggles:', $expandToggles.length);
    console.log('Drag handles:', $dragHandles.length);
    
    // Analyze task hierarchy
    var hierarchyData = {};
    $taskRows.each(function() {
        var taskId = $(this).data('task-id');
        var level = $(this).data('level');
        var parentId = $(this).data('parent-id');
        var hasChildren = $(this).data('has-children');
        var title = $(this).find('.task-title-text').text().trim();
        
        hierarchyData[taskId] = {
            level: level,
            parent: parentId,
            hasChildren: hasChildren,
            title: title
        };
        
        console.log('Task ' + taskId + ': "' + title + '" (Level: ' + level + ', Parent: ' + parentId + ')');
    });
    
    var summary = ' Current State Analysis:\n\n' +
                 '• Task rows: ' + $taskRows.length + '\n' +
                 '• Status badges: ' + $statusBadges.length + '\n' +
                 '• Expand toggles: ' + $expandToggles.length + '\n' +
                 '• Drag handles: ' + $dragHandles.length + '\n\n' +
                 'Check console for detailed hierarchy data.';
    
    alert(summary);
    return hierarchyData;
};

// Test status dropdown functionality
window.testStatusDropdown = function() {
    console.log('=== 🧪 STATUS DROPDOWN TEST ===');
    
    var $statusBadges = $('.status-badge');
    var $statusOptions = $('.status-option');
    
    console.log('Status badges found:', $statusBadges.length);
    console.log('Status options found:', $statusOptions.length);
    
    if ($statusBadges.length > 0) {
        console.log('Testing first status badge...');
        var $firstBadge = $statusBadges.first();
        var taskId = $firstBadge.data('task-id');
        var currentStatus = $firstBadge.text();
        
        console.log('Badge task ID:', taskId);
        console.log('Current status:', currentStatus);
        
        // Simulate click to open dropdown
        $firstBadge.trigger('click');
        
        setTimeout(function() {
            var $visibleDropdowns = $('.dropdown-menu:visible');
            console.log('Visible dropdowns after click:', $visibleDropdowns.length);
            
            alert(' Status Dropdown Test:\n\n' +
                 '• Status badges: ' + $statusBadges.length + '\n' +
                 '• Status options: ' + $statusOptions.length + '\n' +
                 '• Test badge ID: ' + taskId + '\n' +
                 '• Current status: ' + currentStatus + '\n\n' +
                 'Dropdown should be visible now.');
        }, 500);
    } else {
        alert('❌ No status badges found!');
    }
};

// Test Jira hierarchy
window.debugJiraHierarchy = function() {
    console.log('=== 🎯 JIRA HIERARCHY DEBUG ===');
    
    var $jiraRows = $('.task-item');
    var $level0Tasks = $('.task-item.level-0');
    var $tasksWithChildren = $('.task-item.has-children');
    var $hierarchyConnectors = $('.jira-hierarchy');
    
    console.log('Total Jira task rows:', $jiraRows.length);
    console.log('Level 0 tasks (Epics):', $level0Tasks.length);
    console.log('Tasks with children:', $tasksWithChildren.length);
    console.log('Hierarchy connectors:', $hierarchyConnectors.length);
    
    // Analyze hierarchy structure
    var levelCounts = {};
    $jiraRows.each(function() {
        var level = $(this).data('level') || 0;
        levelCounts[level] = (levelCounts[level] || 0) + 1;
    });
    
    console.log('Tasks by level:', levelCounts);
    
    var summary = ' Jira Hierarchy Analysis:\n\n' +
                 '• Total tasks: ' + $jiraRows.length + '\n' +
                 '• Level 0 (Epics): ' + $level0Tasks.length + '\n' +
                 '• Tasks with children: ' + $tasksWithChildren.length + '\n' +
                 '• Hierarchy connectors: ' + $hierarchyConnectors.length + '\n\n';
    
    // Add level breakdown
    for (var level in levelCounts) {
        summary += '• Level ' + level + ': ' + levelCounts[level] + ' tasks\n';
    }
    
    alert(summary);
};

// Test expand/collapse functionality
window.testJiraExpandCollapse = function() {
    console.log('=== 🎯 EXPAND/COLLAPSE TEST ===');
    
    var $expandToggles = $('.expand-toggle-jira');
    var $expandIcons = $('.expand-icon');
    
    console.log('Expand toggles found:', $expandToggles.length);
    console.log('Expand icons found:', $expandIcons.length);
    
    if ($expandToggles.length > 0) {
        console.log('Testing first expand toggle...');
        var $firstToggle = $expandToggles.first();
        var taskId = $firstToggle.data('task-id');
        
        console.log('Testing expand/collapse for task:', taskId);
        
        // Test expand
        $firstToggle.trigger('click');
        
        setTimeout(function() {
            console.log('First click completed, testing collapse...');
            // Test collapse
            $firstToggle.trigger('click');
            
            setTimeout(function() {
                alert(' Expand/Collapse Test:\n\n' +
                     '• Expand toggles: ' + $expandToggles.length + '\n' +
                     '• Test task ID: ' + taskId + '\n\n' +
                     'Expand/collapse should have been tested.\nCheck console for details.');
            }, 500);
        }, 1000);
    } else {
        alert('❌ No expand toggles found!\n\nThis means no tasks have children.');
    }
};

// Test inline task creation
window.testInlineTaskCreation = function() {
    console.log('=== 🧪 INLINE TASK CREATION TEST ===');
    
    var $createBtn = $('#create-task-btn');
    var $addButtons = $('.add-subtask-jira');
    
    console.log('Create task button found:', $createBtn.length > 0);
    console.log('Add subtask buttons found:', $addButtons.length);
    
    if ($createBtn.length > 0) {
        console.log('Testing create task button...');
        $createBtn.trigger('click');
        
        setTimeout(function() {
            var $inputs = $('.task-title-input');
            console.log('Task input fields created:', $inputs.length);
            
            alert(' Inline Task Creation Test:\n\n' +
                 '• Create button: ' + ($createBtn.length > 0 ? 'Found' : 'Missing') + '\n' +
                 '• Add subtask buttons: ' + $addButtons.length + '\n' +
                 '• Input fields created: ' + $inputs.length + '\n\n' +
                 'Task creation should have been triggered.');
        }, 500);
    } else {
        alert('❌ Create task button not found!');
    }
};

// Create test hierarchy
window.createTestHierarchy = function() {
    console.log('=== 🧪 CREATING TEST HIERARCHY ===');
    
    alert('🚧 Test Hierarchy Creation\n\n' +
         'This will create a multi-level test hierarchy:\n' +
         '• Level 0: Main Task\n' +
         '• Level 1: Subtask\n' +
         '• Level 2: Sub-subtask\n\n' +
         'Click OK to proceed...');
    
    // Step 1: Create main task
    $('#create-task-btn').trigger('click');
    
    setTimeout(function() {
        var $input = $('.task-title-input').last();
        if ($input.length > 0) {
            $input.val('TEST HIERARCHY - Main Task').focus();
            
            // Simulate Enter key
            var enterEvent = $.Event('keydown');
            enterEvent.which = 13;
            enterEvent.keyCode = 13;
            $input.trigger(enterEvent);
            
            console.log('Main task created');
            
            // Wait and create subtask
            setTimeout(function() {
                var $addButtons = $('.add-subtask-jira');
                if ($addButtons.length > 0) {
                    $addButtons.first().trigger('click');
                    
                    setTimeout(function() {
                        var $subInput = $('.task-title-input').last();
                        if ($subInput.length > 0) {
                            $subInput.val('TEST HIERARCHY - Subtask').focus();
                            
                            var enterEvent2 = $.Event('keydown');
                            enterEvent2.which = 13;
                            enterEvent2.keyCode = 13;
                            $subInput.trigger(enterEvent2);
                            
                            console.log('Subtask created');
                            
                            setTimeout(function() {
                                alert(' Test hierarchy creation completed!\n\nCheck the task list for the new hierarchy.');
                            }, 2000);
                        }
                    }, 1000);
                }
            }, 2000);
        }
    }, 1000);
};

// Check task hierarchy
window.checkTaskHierarchy = function() {
    console.log('=== 🌳 TASK HIERARCHY CHECK ===');
    
    var hierarchyMap = {};
    var rootTasks = [];
    var totalTasks = 0;
    
    $('.task-item').each(function() {
        var taskId = $(this).data('task-id');
        var parentId = $(this).data('parent-id') || 0;
        var level = $(this).data('level') || 0;
        var title = $(this).find('.task-title-text').text().trim() || 'Untitled';
        
        totalTasks++;
        
        if (parentId == 0) {
            rootTasks.push({id: taskId, title: title, level: level});
        } else {
            if (!hierarchyMap[parentId]) {
                hierarchyMap[parentId] = [];
            }
            hierarchyMap[parentId].push({id: taskId, title: title, level: level});
        }
        
        console.log('Task ' + taskId + ': "' + title + '" (Level: ' + level + ', Parent: ' + parentId + ')');
    });
    
    var summary = ' Task Hierarchy Analysis:\n\n' +
                 '• Total tasks: ' + totalTasks + '\n' +
                 '• Root tasks: ' + rootTasks.length + '\n' +
                 '• Parent-child groups: ' + Object.keys(hierarchyMap).length + '\n\n' +
                 'Check console for detailed breakdown.';
    
    alert(summary);
    
    return {
        totalTasks: totalTasks,
        rootTasks: rootTasks,
        hierarchyMap: hierarchyMap
    };
};

// Reinitialize task list
window.reinitializeTaskList = function() {
    console.log('=== 🔄 REINITIALIZING TASK LIST ===');
    
    taskListInitialized = false;
    
    setTimeout(function() {
        initTaskList();
        alert(' Task list reinitialized!\n\nAll functionality should be working now.');
    }, 500);
};

// Debug click handlers
window.debugClickHandlers = function() {
    console.log('=== 🧪 CLICK HANDLERS TEST ===');
    
    var handlers = {
        'Create Task Button': $('#create-task-btn').length,
        'Status Badges': $('.status-badge').length,
        'Expand Toggles': $('.expand-toggle-jira').length,
        'Add Subtask Buttons': $('.add-subtask-jira').length,
        'Drag Handles': $('.jira-drag-handle').length,
        'Search Input': $('.search-box input').length,
        'Filter Options': $('.filter-option').length
    };
    
    var summary = ' Click Handlers Analysis:\n\n';
    for (var handler in handlers) {
        summary += '• ' + handler + ': ' + handlers[handler] + '\n';
        console.log(handler + ':', handlers[handler]);
    }
    
    alert(summary);
};

// Auto-verify debug functions are available
setTimeout(function() {
    console.log('🔍 Verifying debug functions...');
    
    var debugFunctions = [
        'testDragDrop', 'debugCurrentState', 'testStatusDropdown',
        'debugJiraHierarchy', 'testJiraExpandCollapse', 'testInlineTaskCreation',
        'createTestHierarchy', 'checkTaskHierarchy', 'reinitializeTaskList',
        'debugClickHandlers'
    ];
    
    var available = 0;
    debugFunctions.forEach(function(funcName) {
        if (typeof window[funcName] === 'function') {
            available++;
        } else {
            console.error('❌ Missing function:', funcName);
        }
    });
    
    console.log(' Debug functions available: ' + available + '/' + debugFunctions.length);
    
    if (available === debugFunctions.length) {
        console.log('🎯 All debug functions are ready!');
    }
}, 2000);