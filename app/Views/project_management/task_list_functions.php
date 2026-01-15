<?php
/**
 * Task List Helper Functions
 * Separated from main view for better organization
 */

if (!function_exists('render_inline_task_form')) {
    function render_inline_task_form($parent_id = 0, $level = 0, $project_id = 0) {
        $indentStyle = 'margin-left: ' . ($level * 20) . 'px;';
        
        $output = '<tr class="inline-task-form" data-parent-id="' . $parent_id . '" data-level="' . $level . '" style="background: #F7F8F9; border-left: 3px solid #0052CC;">';
        
        // Checkbox column
        $output .= '<td style="width: 30px; padding: 8px 4px;"></td>';
        
        // Type column
        $output .= '<td style="width: 40px; padding: 8px 4px; text-center;">
            <div class="task-type-icon plus-icon" title="New Task" style="
                display: flex;
                align-items: center;
                justify-content: center;
                width: 16px;
                height: 16px;
                font-size: 14px;
                color: #6B778C;
                font-weight: bold;
            ">+</div>
        </td>';
        
        // Key column
        $output .= '<td style="width: 80px; padding: 8px 12px; color: #6B778C;">
            <em>Auto-generated</em>
        </td>';
        
        // Summary column with input
        $output .= '<td style="padding: 8px 12px;">';
        $output .= '<div style="' . $indentStyle . '">';
        if ($level > 0) {
            $output .= '<span class="hierarchy-connector" style="margin-right: 4px; color: #8993a4;">└</span>';
        }
        $output .= '<input type="text" class="new-task-title" placeholder="Enter task title..." style="
            border: 2px solid #0052CC;
            border-radius: 3px;
            padding: 6px 12px;
            font-size: 14px;
            width: 300px;
            outline: none;
        " autofocus>';
        $output .= '<div class="inline-form-actions" style="margin-top: 8px;">';
        $output .= '<button class="btn-save-task" style="
            background: #0052CC;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 3px;
            margin-right: 8px;
            cursor: pointer;
            font-size: 12px;
        ">Save</button>';
        $output .= '<button class="btn-cancel-task" style="
            background: none;
            color: #6B778C;
            border: 1px solid #DFE1E6;
            padding: 6px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        ">Cancel</button>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</td>';
        
        // Empty columns - Updated count for new Level column (added after Labels)
        for ($i = 0; $i < 11; $i++) {
            $output .= '<td></td>';
        }
        
        $output .= '</tr>';
        
        return $output;
    }
}

if (!function_exists('render_hierarchical_tasks')) {
    function render_hierarchical_tasks($tasks, $level = 0, $project_id = 0, $allTasks = null) {
        // Build complete parent-child map on first call
        static $parentChildMap = null;
        static $taskMap = null;
        static $rootTasksOnly = false;
        
        // Reset static variables on main call
        if ($allTasks !== null) {
            $parentChildMap = array();
            $taskMap = array();
            $rootTasksOnly = true;
            
            // Debug: Log what we're working with
            error_log("=== RENDER HIERARCHICAL TASKS DEBUG ===");
            error_log("Total tasks received: " . count($allTasks));
            
            // Build task map and parent-child relationships from all tasks
            foreach ($allTasks as $task) {
                $taskMap[$task->id] = $task;
                
                // Handle different possible field names and formats
                $parentId = 0;
                if (isset($task->parent_task_id)) {
                    $parentId = intval($task->parent_task_id);
                } elseif (isset($task->parent_id)) {
                    $parentId = intval($task->parent_id);
                } elseif (isset($task->parentTaskId)) {
                    $parentId = intval($task->parentTaskId);
                }
                
                error_log("Task ID: {$task->id}, Title: {$task->title}, Parent ID: {$parentId} (raw: " . print_r($task->parent_task_id ?? 'null', true) . ")");
                
                if ($parentId > 0) {
                    if (!isset($parentChildMap[$parentId])) {
                        $parentChildMap[$parentId] = array();
                    }
                    $parentChildMap[$parentId][] = $task->id;
                }
            }
            
            error_log("Parent-Child Map: " . print_r($parentChildMap, true));
            
            // Filter to show only root tasks (no parent) on the main call
            $rootTasks = array();
            foreach ($allTasks as $task) {
                $parentId = 0;
                if (isset($task->parent_task_id)) {
                    $parentId = intval($task->parent_task_id);
                } elseif (isset($task->parent_id)) {
                    $parentId = intval($task->parent_id);
                } elseif (isset($task->parentTaskId)) {
                    $parentId = intval($task->parentTaskId);
                }
                
                if ($parentId == 0) {
                    $rootTasks[] = $task;
                }
            }
            
            error_log("Root tasks found: " . count($rootTasks));
            $tasks = $rootTasks;
        }
        
        $output = '';
        foreach ($tasks as $task) {
            // Jira-style indentation with connecting lines
            $jira_indent = '';
            $hierarchy_visual = '';
            
            if ($level > 0) {
                // Create connecting lines for hierarchy levels
                for ($i = 0; $i < $level; $i++) {
                    if ($i == $level - 1) {
                        // Last level - use L-shaped connector
                        $jira_indent .= '<span class="hierarchy-connector hierarchy-branch">└─</span>';
                    } else {
                        // Middle levels - use vertical line or space
                        $jira_indent .= '<span class="hierarchy-connector hierarchy-line">│&nbsp;</span>';
                    }
                }
                $hierarchy_visual = '<div class="jira-hierarchy" style="display: inline-block; margin-right: 8px;">' . $jira_indent . '</div>';
            }
            
            // Jira-style task type icons
            if ($level == 0) {
                // Parent/Epic task
                $task_type_icon = '<div class="task-type-icon epic-icon" title="Epic">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B46C1" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <polyline points="9,11 12,14 22,4"/>
                    </svg>
                </div>';
            } else {
                // Plus emoji for subtasks
                $task_type_icon = '<div class="task-type-icon plus-icon" title="Subtask" style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 14px;
                    height: 14px;
                    font-size: 12px;
                    color: #6B778C;
                    font-weight: bold;
                ">+</div>';
            }
            
            // Check if this task has children
            $hasChildren = isset($parentChildMap[$task->id]) && !empty($parentChildMap[$task->id]);
            
            // Get parent ID consistently
            $taskParentId = 0;
            if (isset($task->parent_task_id)) {
                $taskParentId = intval($task->parent_task_id);
            } elseif (isset($task->parent_id)) {
                $taskParentId = intval($task->parent_id);
            } elseif (isset($task->parentTaskId)) {
                $taskParentId = intval($task->parentTaskId);
            }
            
            // Jira-style expandable icon 
            $expand_icon = '';
            
            if ($hasChildren) {
                $expand_icon = '<span class="expand-toggle-jira expand-toggle" data-task-id="' . $task->id . '" style="cursor: pointer; margin-right: 6px;" title="Expand/Collapse">
                    <svg class="expand-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </span>';
            } else {
                // Subtle indicator for leaf nodes
                $expand_icon = '<span class="expand-placeholder" style="width: 20px; display: inline-block;"></span>';
            }
            
            // Get task images
            $images = '';
            if (isset($task->images) && !empty($task->images)) {
                $task_images = json_decode($task->images, true);
                if ($task_images && count($task_images) > 0) {
                    // Handle different image storage formats
                    $first_image = $task_images[0];
                    $image_url = '';
                    
                    if (is_string($first_image)) {
                        // Simple string format
                        $image_url = $first_image;
                    } elseif (is_array($first_image)) {
                        // Object format with filename or url
                        if (isset($first_image['url'])) {
                            $image_url = $first_image['url'];
                        } elseif (isset($first_image['filename'])) {
                            $image_url = base_url('files/task_images/' . $first_image['filename']);
                        } elseif (isset($first_image['file_name'])) {
                            $image_url = base_url('files/task_images/' . $first_image['file_name']);
                        }
                    }
                    
                    if (!empty($image_url)) {
                        $images = '<img src="' . $image_url . '" class="task-preview-image ms-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 3px;" title="Task preview">';
                    }
                }
            }
            
            // Jira-style drag handle
            $drag_handle = '<div class="jira-drag-handle drag-handle" style="cursor: grab; padding: 4px; display: inline-flex; align-items: center; opacity: 0.4; transition: opacity 0.2s;" title="Drag to reorder">
                <svg width="10" height="16" viewBox="0 0 10 16" fill="none">
                    <circle cx="2" cy="2" r="1" fill="#9CA3AF"/>
                    <circle cx="8" cy="2" r="1" fill="#9CA3AF"/>
                    <circle cx="2" cy="8" r="1" fill="#9CA3AF"/>
                    <circle cx="8" cy="8" r="1" fill="#9CA3AF"/>
                    <circle cx="2" cy="14" r="1" fill="#9CA3AF"/>
                    <circle cx="8" cy="14" r="1" fill="#9CA3AF"/>
                </svg>
            </div>';
            
            // Legacy priority indicator code removed - now using database-driven priority system
            
            // Get level colors for left border
            $level_colors = [
                0 => '#22C55E', // Green for main tasks
                1 => '#3B82F6', // Blue for level 1
                2 => '#F59E0B', // Orange for level 2  
                3 => '#EF4444', // Red for level 3
                4 => '#8B5CF6', // Purple for level 4
                5 => '#06B6D4', // Cyan for level 5
                6 => '#F97316', // Orange for level 6
                7 => '#EC4899', // Pink for level 7
                8 => '#84CC16', // Lime for level 8
                9 => '#6366F1'  // Indigo for level 9
            ];
            $border_color = $level_colors[$level] ?? '#6B7280';
            
            // Status badge - show actual status, not level
            $status_class = 'primary';
            $status_text = 'L' . ($level + 1); // Show level (L1, L2, L3, etc.)
            
            // You can also show actual status instead of level:
            /*
            switch ($task->status) {
                case 'done':
                    $status_class = 'success';
                    $status_text = 'DONE';
                    break;
                case 'in_progress':
                    $status_class = 'warning';
                    $status_text = 'PROGRESS';
                    break;
                case 'review':
                    $status_class = 'info';
                    $status_text = 'REVIEW';
                    break;
                default:
                    $status_class = 'secondary';
                    $status_text = 'TODO';
            }
            */
            
            // Task description/subtitle
            $task_description = '';
            if (isset($task->description) && !empty($task->description)) {
                $task_description = '<div class="task-description text-muted small">' . htmlspecialchars(substr($task->description, 0, 100)) . '</div>';
            }
            
            // Output as table row format to match Jira exactly
            $output .= '<tr class="jira-task-row task-row task-item level-' . $level . ($hasChildren ? ' has-children' : '') . '" 
                            data-task-id="' . $task->id . '" 
                            data-level="' . $level . '" 
                            data-parent-id="' . $taskParentId . '" 
                            data-has-children="' . ($hasChildren ? 'true' : 'false') . '">';
            
            // Checkbox column (like Jira)
            $output .= '<td class="text-center" style="width: 30px; padding: 8px 4px;">';
            $output .= '<input type="checkbox" class="task-checkbox" data-task-id="' . $task->id . '" style="transform: scale(1.1);">';
            $output .= '</td>';
            
            // Task type column with expand/collapse and add buttons
            $output .= '<td class="task-type-cell" style="width: 40px; padding: 8px 4px; position: relative;">';
            
            // Expand/collapse button (left side of checkbox) - FIXED CLASS NAME
            if ($hasChildren) {
                $output .= '<span class="expand-toggle" data-task-id="' . $task->id . '" style="
                    position: absolute; 
                    left: 2px; 
                    top: 50%; 
                    transform: translateY(-50%); 
                    cursor: pointer; 
                    z-index: 10;
                    width: 16px;
                    height: 16px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                " title="Expand/Collapse">
                    <svg class="expand-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </span>';
            }
            
            // Task type icon (center)
            $output .= '<div class="task-type-icon-wrapper" style="display: flex; justify-content: center; align-items: center; margin: 0 18px;">';
            if ($level == 0) {
                // Epic icon (purple)
                $output .= '<div class="task-type-icon epic-icon" title="Epic">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <rect x="1" y="1" width="14" height="14" rx="2" fill="#6B46C1" stroke="#6B46C1" stroke-width="1"/>
                        <path d="M4 8l2 2 6-6" stroke="white" stroke-width="2" fill="none"/>
                    </svg>
                </div>';
            } else {
                // Plus emoji for subtasks
                $output .= '<div class="task-type-icon plus-icon" title="Subtask" style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 16px;
                    height: 16px;
                    font-size: 14px;
                    color: #6B778C;
                    font-weight: bold;
                ">+</div>';
            }
            $output .= '</div>';
            
            // Add task button (right side) - CSS-BASED PLUS SYMBOL
            if ($level < 9) {
                $output .= '<span class="add-subtask-jira add-subtask-btn btn-add-child" data-parent-id="' . $task->id . '" style="
                    position: absolute; 
                    right: 2px; 
                    top: 50%; 
                    transform: translateY(-50%); 
                    cursor: pointer; 
                    z-index: 10;
                    width: 28px;
                    height: 28px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0.9;
                    transition: all 0.2s;
                    background:rgb(0, 0, 0);
                    border-radius: 3px;
                    border: 1px solid #047857;
                    color:white;
                    color: white;
                    font-size: 24px;
                    font-weight: bold;
                    line-height: 1;
                " title="Add subtask">+</span>';
            }
            
            $output .= '</td>';
            
            // Task key column (clickable to open task modal)
            $output .= '<td class="task-key-cell" style="width: 80px; padding: 8px 12px;">';
            $output .= '<a href="#" class="task-key-link" data-task-id="' . $task->id . '" style="color: #0052CC; text-decoration: none; font-weight: 500; cursor: pointer;" title="Click to view task details">' . $task->id . '</a>';
            $output .= '</td>';
            
            // Summary column with inline editing capability
            $output .= '<td class="task-summary-cell" style="padding: 8px 12px;">';
            
            // Add hierarchy indentation (20px per level like Jira)
            $indentStyle = 'margin-left: ' . ($level * 20) . 'px;';
            
            $output .= '<div class="task-summary-container" style="' . $indentStyle . ' display: flex; align-items: center;">';
            
            // Hierarchy visual connectors (only for child tasks)
            if ($level > 0) {
                $output .= '<span class="hierarchy-connector" style="margin-right: 4px; color: #8993a4; font-family: monospace;">└</span>';
            }
            
            // Task title with inline editing
            $output .= '<div class="task-title-wrapper" style="flex: 1; position: relative;">';
            $output .= '<span class="task-title-display" data-task-id="' . $task->id . '" style="
                color: #172B4D; 
                cursor: text; 
                padding: 4px 8px; 
                border-radius: 3px; 
                transition: background-color 0.2s;
                display: block;
                min-height: 24px;
                line-height: 16px;
                position: relative;
                z-index: 1;
            " title="Click to edit">' . htmlspecialchars($task->title) . '</span>';
            
            $output .= '<input type="text" class="task-title-editor" data-task-id="' . $task->id . '" value="' . htmlspecialchars($task->title) . '" style="
                display: none;
                border: 2px solid #0052CC;
                border-radius: 3px;
                padding: 4px 8px;
                font-size: 14px;
                width: calc(100% - 16px);
                outline: none;
                background: white;
                color: #172B4D;
                z-index: 1000;
                position: absolute;
                top: 0;
                left: 0;
                box-shadow: 0 0 0 2px rgba(0, 82, 204, 0.2);
                pointer-events: auto;
                user-select: text;
                -webkit-user-select: text;
                -moz-user-select: text;
                -ms-user-select: text;
            ">';
            $output .= '</div>';
            
            $output .= '</div>';
            $output .= '</td>';
            
            // Description column - NEW
            $output .= '<td class="task-description-cell" style="width: 200px; padding: 8px 12px;">';
            $output .= '<div class="task-description-container">';
            $output .= '<div class="task-description-wrapper" style="flex: 1;">';
            
            // Get task description (empty for now since existing tasks might not have descriptions)
            $taskDescription = isset($task->description) && !empty(trim($task->description)) ? trim($task->description) : '';
            
            if (empty($taskDescription)) {
                $displayText = 'Click to add description...';
                $fontStyle = 'italic';
                $dataAttribute = '';
                $titleAttribute = '';
            } else {
                // Truncate for display if longer than 30 characters
                if (strlen($taskDescription) > 30) {
                    $displayText = htmlspecialchars(substr($taskDescription, 0, 30) . '...');
                    $titleAttribute = ' title="' . htmlspecialchars($taskDescription) . '"';
                } else {
                    $displayText = htmlspecialchars($taskDescription);
                    $titleAttribute = '';
                }
                $fontStyle = 'normal';
                $dataAttribute = ' data-full-description="' . htmlspecialchars($taskDescription) . '"';
            }
            
            $output .= '<span class="task-description-display" data-task-id="' . $task->id . '"' . $dataAttribute . $titleAttribute . ' style="
                color: #6B778C; 
                cursor: pointer; 
                padding: 6px 8px; 
                border-radius: 3px; 
                transition: all 0.2s ease;
                display: block;
                min-height: 28px;
                line-height: 16px;
                font-size: 13px;
                font-weight: 400;
                border: 1px solid transparent;
                background: transparent;
                font-style: ' . $fontStyle . ';
            " 
            onmouseover="this.style.backgroundColor=\'#F4F5F7\'; this.style.borderColor=\'#DFE1E6\';" 
            onmouseout="this.style.backgroundColor=\'transparent\'; this.style.borderColor=\'transparent\';" 
            title="Click to edit description">' . $displayText . '</span>';
            
            $output .= '<textarea class="task-description-editor" data-task-id="' . $task->id . '" placeholder="Enter task description..." style="
                display: none;
                border: 2px solid #0052CC;
                border-radius: 3px;
                padding: 6px 8px;
                font-size: 13px;
                font-weight: 400;
                width: 100%;
                outline: none;
                background: #FFFFFF;
                color: #172B4D;
                box-shadow: 0 0 0 2px rgba(0, 82, 204, 0.2);
                transition: all 0.2s ease;
                min-height: 60px;
                resize: vertical;
                font-family: inherit;
            ">' . htmlspecialchars($taskDescription) . '</textarea>';
            
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</td>';
            
            // Comments column (clickable to open new TaskModal)
            $output .= '<td class="text-center task-comments-cell" style="width: 120px; padding: 8px 12px; cursor: pointer;" title="Click to view task and add comments" onclick="openTaskModal(' . $task->id . ')">';
            $commentIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="1.5">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>';
            $output .= '<div style="color: #42526E; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">' . $commentIcon . ' <span style="font-size: 13px;">Add comment</span></div>';
            $output .= '</td>';
            
            // Status column with exact Jira styling
            $output .= '<td class="text-center" style="width: 100px; padding: 8px 12px;">';
            
            // Get status data from database instead of hardcoded mapping
            $statusMapping = [];
            try {
                $db = \Config\Database::connect();
                $statusQuery = $db->query("SELECT id, title, color FROM rise_task_status ORDER BY id");
                $statuses = $statusQuery->getResultArray();
                
                foreach ($statuses as $status) {
                    // Determine text color based on background color brightness
                    $bgColor = $status['color'];
                    $textColor = '#FFFFFF'; // Default to white
                    
                    // Simple brightness check - if it's a light color, use dark text
                    if (in_array(strtolower($bgColor), ['#ffffff', '#f0f0f0', '#dfe1e6', '#f4f5f7', '#fafbfc'])) {
                        $textColor = '#42526E';
                    }
                    
                    $statusMapping[$status['id']] = [
                        'text' => strtoupper($status['title']),
                        'bg' => $status['color'],
                        'color' => $textColor
                    ];
                }
            } catch (Exception $e) {
                // Fallback to basic mapping if database query fails
                error_log("Status query failed: " . $e->getMessage());
                $statusMapping = [
                    1 => ['text' => 'TO DO', 'bg' => '#DFE1E6', 'color' => '#42526E']
                ];
            }
            
            // Get actual status from database or default to first available status
            $statusId = isset($task->status_id) ? intval($task->status_id) : 1;
            $currentStatus = $statusMapping[$statusId] ?? (reset($statusMapping) ?: ['text' => 'UNKNOWN', 'bg' => '#DFE1E6', 'color' => '#42526E']);
            
            $output .= '<div class="dropdown">';
            $output .= '<span class="jira-status-badge dropdown-toggle" 
                      data-bs-toggle="dropdown" 
                      data-task-id="' . $task->id . '"
                      style="cursor: pointer; background: ' . $currentStatus['bg'] . ';
                color: ' . $currentStatus['color'] . ';
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                display: inline-block;
                min-width: 60px;
                " title="Click to change status">' . $currentStatus['text'] . '</span>';
            $output .= '<ul class="dropdown-menu">';
            foreach ($statusMapping as $id => $status) {
                $output .= '<li><a class="dropdown-item status-option" href="#" data-task-id="' . $task->id . '" data-status-id="' . $id . '">';
                $output .= '<span style="background: ' . $status['bg'] . '; color: ' . $status['color'] . '; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;">' . $status['text'] . '</span>';
                $output .= '</a></li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
            $output .= '</td>';
            
            // Category column
            $output .= '<td style="width: 100px; padding: 8px 12px;"></td>';
            
            // Assignee column with dropdown functionality
            $output .= '<td class="task-assignee-cell" style="width: 120px; padding: 8px 12px;">';
            
            // Get current assignee info - will be populated by JavaScript getUsersList
            $assigneeDisplay = '';
            if (isset($task->assigned_to) && $task->assigned_to > 0) {
                // Placeholder that will be populated by JavaScript
                $assigneeDisplay = '<div class="assignee-avatar" data-assignee-id="' . $task->assigned_to . '">
                    <span class="assignee-initials" style="
                        background: #0052CC;
                        color: white;
                        border-radius: 50%;
                        width: 24px;
                        height: 24px;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 10px;
                        font-weight: 600;
                        cursor: pointer;
                        border: 2px solid #DFE1E6;
                    " title="Click to change assignee">??</span>
                </div>';
            } else {
                $assigneeDisplay = '<div class="assignee-placeholder" style="
                    color: #6B778C;
                    cursor: pointer;
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    border: 1px dashed #DFE1E6;
                    text-align: center;
                " title="Click to assign">Unassigned</div>';
            }
            
            $output .= '<div class="task-assignee-container" data-task-id="' . $task->id . '" data-current-assignee="' . ($task->assigned_to ?? 0) . '">';
            $output .= $assigneeDisplay;
            $output .= '</div>';
            $output .= '</td>';
            
            // Collaborators column with multi-user functionality
            $output .= '<td class="task-collaborators-cell" style="width: 120px; padding: 8px 12px;">';
            
            // Check if task has collaborators and store as data attribute
            $collaborators = isset($task->collaborators) ? trim($task->collaborators) : '';
            $output .= '<div class="task-collaborators-container" data-task-id="' . $task->id . '" data-current-collaborators="' . htmlspecialchars($collaborators) . '">';
            
            if (!empty($collaborators)) {
                // Parse collaborators (assuming comma-separated user IDs)
                $collaboratorIds = explode(',', $collaborators);
                $collaboratorIds = array_filter(array_map('trim', $collaboratorIds));
                
                if (count($collaboratorIds) > 0) {
                    $output .= '<div class="collaborators-avatars" style="display: flex; gap: 2px; align-items: center;">';
                    
                    // Show up to 3 collaborator avatars - will be populated by JavaScript
                    $showCount = min(3, count($collaboratorIds));
                    for ($i = 0; $i < $showCount; $i++) {
                        $output .= '<span class="collaborator-avatar" style="
                            background: #36B37E;
                            color: white;
                            border-radius: 50%;
                            width: 20px;
                            height: 20px;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 9px;
                            font-weight: 600;
                            border: 1px solid white;
                            margin-left: -2px;
                            cursor: pointer;
                        " title="Collaborator">?</span>';
                    }
                    
                    // Show count if more than 3
                    if (count($collaboratorIds) > 3) {
                        $remaining = count($collaboratorIds) - 3;
                        $output .= '<span style="
                            background: #DFE1E6;
                            color: #6B778C;
                            border-radius: 50%;
                            width: 20px;
                            height: 20px;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 8px;
                            font-weight: 600;
                            margin-left: -2px;
                            cursor: pointer;
                        " title="' . $remaining . ' more collaborators">+' . $remaining . '</span>';
                    }
                    
                    $output .= '</div>';
                } else {
                    $output .= '<div class="collaborators-placeholder" style="
                        color: #6B778C;
                        cursor: pointer;
                        padding: 4px 8px;
                        border-radius: 3px;
                        font-size: 11px;
                        border: 1px dashed #DFE1E6;
                        text-align: center;
                        background: #F4F5F7;
                    " title="Click to add collaborators">Add</div>';
                }
            } else {
                $output .= '<div class="collaborators-placeholder" style="
                    color: #6B778C;
                    cursor: pointer;
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 11px;
                    border: 1px dashed #DFE1E6;
                    text-align: center;
                    background: #F4F5F7;
                " title="Click to add collaborators">Add</div>';
            }
            
            $output .= '</div>';
            $output .= '</td>';
            
            // Deadline column with actual database value
            $output .= '<td class="task-deadline-cell" style="width: 120px; padding: 8px 12px;">';
            
            // Check if task has a deadline and store as data attribute
            $deadline = isset($task->deadline) && !empty($task->deadline) ? $task->deadline : '';
            $output .= '<div class="task-deadline-container" data-task-id="' . $task->id . '" data-current-deadline="' . htmlspecialchars($deadline) . '">';
            
            if (!empty($deadline) && $deadline !== '0000-00-00') {
                // Format the deadline date
                $formattedDeadline = date('M j, Y', strtotime($deadline));
                $output .= '<div class="deadline-display" style="
                    color: #172B4D;
                    cursor: pointer;
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    border: 1px solid #DFE1E6;
                    background: #F4F5F7;
                    text-align: center;
                " title="Click to change deadline">' . $formattedDeadline . '</div>';
            } else {
                // Empty cell with hover text
                $output .= '<div class="deadline-empty" style="
                    cursor: pointer;
                    padding: 8px;
                    text-align: center;
                    min-height: 28px;
                    border-radius: 3px;
                    transition: all 0.2s ease;
                    position: relative;
                " title="Set deadline" onmouseover="this.innerHTML=\'Set deadline\'; this.style.color=\'#6B778C\'; this.style.fontSize=\'11px\';" onmouseout="this.innerHTML=\'\'; this.style.color=\'transparent\';">&nbsp;</div>';
            }
            
            $output .= '</div>';
            $output .= '</td>';
            
            // Priority column with dropdown functionality
            $output .= '<td class="text-center" style="width: 80px; padding: 8px 12px;">';
            
            // Get priority data from database instead of hardcoded mapping
            $priorityMapping = [];
            try {
                $db = \Config\Database::connect();
                $priorityQuery = $db->query("SELECT id, title, color, icon FROM rise_task_priority ORDER BY id");
                $priorities = $priorityQuery->getResultArray();
                
                foreach ($priorities as $priority) {
                    $priorityMapping[$priority['id']] = [
                        'text' => strtoupper($priority['title']),
                        'color' => $priority['color'],
                        'icon' => $priority['icon'] ?: '→'
                    ];
                }
            } catch (Exception $e) {
                // Fallback to basic mapping if database query fails
                error_log("Priority query failed: " . $e->getMessage());
                $priorityMapping = [
                    2 => ['text' => 'MEDIUM', 'color' => '#F39C12', 'icon' => '→']
                ];
            }
            
            // Get actual priority from database or default to MEDIUM
            $priorityId = isset($task->priority_id) ? intval($task->priority_id) : 2;
            $currentPriority = $priorityMapping[$priorityId] ?? ($priorityMapping[2] ?? ['text' => 'MEDIUM', 'color' => '#F39C12', 'icon' => '→']);
            
            $output .= '<div class="dropdown">';
            $output .= '<span class="jira-priority-badge dropdown-toggle" 
                      data-bs-toggle="dropdown" 
                      data-task-id="' . $task->id . '"
                      style="cursor: pointer; color: ' . $currentPriority['color'] . '; display: inline-flex; align-items: center; gap: 4px;"
                      title="Click to change priority">';
            $output .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            $output .= '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>';
            $output .= '</svg>';
            $output .= '<span style="font-size: 11px; font-weight: 600;">' . $currentPriority['text'] . '</span>';
            $output .= '</span>';
            $output .= '<ul class="dropdown-menu">';
            foreach ($priorityMapping as $id => $priority) {
                $output .= '<li><a class="dropdown-item priority-option" href="#" data-task-id="' . $task->id . '" data-priority="' . $id . '">';
                $output .= '<span style="color: ' . $priority['color'] . ';">' . $priority['icon'] . ' ' . $priority['text'] . '</span>';
                $output .= '</a></li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
            $output .= '</td>';
            
            // Labels column with multi-label functionality
            $output .= '<td class="task-labels-cell" style="width: 120px; padding: 8px 12px;">';
            
            // Check if task has labels and store as data attribute
            $labels = isset($task->labels) ? trim($task->labels) : '';
            $output .= '<div class="task-labels-container" data-task-id="' . $task->id . '" data-current-labels="' . htmlspecialchars($labels) . '">';
            
            if (!empty($labels)) {
                // Parse label IDs (assuming comma-separated IDs from rise_labels table)
                $labelIds = explode(',', $labels);
                $labelIds = array_filter(array_map('trim', $labelIds));
                
                if (count($labelIds) > 0) {
                    // We'll let JavaScript handle the display since we need to fetch label details
                    // For now, show a loading state that JavaScript will replace
                    $output .= '<div class="labels-loading" style="
                        color: #6B778C;
                        font-size: 11px;
                        padding: 4px 8px;
                    ">Loading labels...</div>';
                } else {
                    $output .= '<div class="labels-placeholder" style="
                        color: #6B778C;
                        cursor: pointer;
                        padding: 4px 8px;
                        border-radius: 3px;
                        font-size: 11px;
                        border: 1px dashed #DFE1E6;
                        text-align: center;
                        background: #F4F5F7;
                    " title="Click to add labels">Add</div>';
                }
            } else {
                $output .= '<div class="labels-placeholder" style="
                    color: #6B778C;
                    cursor: pointer;
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 11px;
                    border: 1px dashed #DFE1E6;
                    text-align: center;
                    background: #F4F5F7;
                " title="Click to add labels">Add</div>';
            }
            
            $output .= '</div>';
            $output .= '</td>';
            
            // Level column - show task hierarchy level
            $output .= '<td class="task-level-cell" style="width: 80px; padding: 8px 12px; text-align: center;">';
            
            // Get task level and parent info
            $taskLevel = isset($task->task_level) ? intval($task->task_level) : 0;
            $parentId = isset($task->parent_task_id) ? intval($task->parent_task_id) : 0;
            
            if ($taskLevel === 0) {
                // Main task
                $output .= '<div class="level-badge main-task" style="
                    background: #0052CC;
                    color: white;
                    padding: 3px 8px;
                    border-radius: 12px;
                    font-size: 11px;
                    font-weight: 600;
                    display: inline-block;
                " title="Main Task">MAIN</div>';
            } else {
                // Subtask with level number
                $output .= '<div class="level-badge sub-task" style="
                    background: #E3FCEF;
                    color: #006644;
                    padding: 3px 8px;
                    border-radius: 12px;
                    font-size: 11px;
                    font-weight: 600;
                    display: inline-block;
                " title="Subtask - Level ' . $taskLevel . '">L' . $taskLevel . '</div>';
            }
            
            $output .= '</td>';
            
            // Created column
            $output .= '<td style="width: 120px; padding: 8px 12px; color: #6B778C; font-size: 13px;">';
            $output .= 'Aug 7, 2024'; // You can use actual created date here
            $output .= '</td>';
            
            // Actions column (three dots menu)
            $output .= '<td class="text-center" style="width: 40px; padding: 8px 4px;">';
            $output .= '<button class="task-menu-btn" style="background: none; border: none; cursor: pointer; padding: 4px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="12" cy="5" r="1"></circle>
                    <circle cx="12" cy="19" r="1"></circle>
                </svg>
            </button>';
            $output .= '</td>';
            
            $output .= '</tr>';
            
            // Render children tasks immediately after parent
            if ($hasChildren && isset($parentChildMap[$task->id])) {
                $childTasks = array();
                foreach ($parentChildMap[$task->id] as $childId) {
                    if (isset($taskMap[$childId])) {
                        $childTasks[] = $taskMap[$childId];
                    }
                }
                // Recursively render children at the next level
                if (!empty($childTasks)) {
                    $output .= render_hierarchical_tasks($childTasks, $level + 1, $project_id, null);
                }
            }
        }
        return $output;
    }
}
?>