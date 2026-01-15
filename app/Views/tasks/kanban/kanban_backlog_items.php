<?php
/**
 * Backlog Kanban Items View - Grouped by Project
 * Shows tasks in collapsible accordion style, grouped by project
 */

// Group tasks by project_id
$tasks_by_project = array();
$general_tasks = array(); // Tasks without project (project_id = 0)

foreach ($tasks as $task) {
    $task_project_id = isset($task->project_id) ? $task->project_id : 0;
    $task_project_title = isset($task->project_title) ? $task->project_title : '';
    
    if (!$task_project_id || $task_project_id == 0) {
        $general_tasks[] = $task;
    } else {
        if (!isset($tasks_by_project[$task_project_id])) {
            $tasks_by_project[$task_project_id] = array(
                'title' => $task_project_title ? $task_project_title : app_lang('project') . ' #' . $task_project_id,
                'tasks' => array()
            );
        }
        $tasks_by_project[$task_project_id]['tasks'][] = $task;
    }
}

// Helper function to parse collaborator list
if (!function_exists('parse_backlog_collaborators')) {
    function parse_backlog_collaborators($collaborator_list) {
        if (!$collaborator_list) {
            return array();
        }

        $collaborators = array();
        $items = explode(',', $collaborator_list);

        foreach ($items as $item) {
            $parts = explode('--::--', $item);
            if (count($parts) >= 3) {
                $collaborators[] = array(
                    'id' => trim($parts[0]),
                    'name' => trim($parts[1]),
                    'image' => trim($parts[2]),
                    'user_type' => isset($parts[3]) ? trim($parts[3]) : ''
                );
            }
        }

        return $collaborators;
    }
}

// Helper function to render a single task card
if (!function_exists('render_backlog_task_card')) {
    function render_backlog_task_card($task, $tasks_edit_permissions) {
        $disable_dragging = get_array_value($tasks_edit_permissions, $task->id) ? "" : "disable-dragging";

        // Parse collaborators - use isset check
        $collaborator_list = isset($task->collaborator_list) ? $task->collaborator_list : '';
        $collaborators = parse_backlog_collaborators($collaborator_list);

    // Build collaborators HTML
    $collaborators_html = "";
    if (!empty($collaborators)) {
        $collaborators_html = "<div class='backlog-collaborators'>";
        foreach ($collaborators as $collab) {
            $avatar_url = get_avatar($collab['image']);
            $collaborators_html .= "<img src='" . $avatar_url . "' class='backlog-collaborator-avatar' title='" . htmlspecialchars($collab['name']) . "' data-bs-toggle='tooltip' />";
        }
        $collaborators_html .= "</div>";
    }

    // Prepare description (truncate if too long) - use isset check
    $description = "";
    $task_description = isset($task->description) ? $task->description : '';
    if ($task_description) {
        $description = strip_tags($task_description);
        $description = mb_substr($description, 0, 150);
        if (mb_strlen(strip_tags($task_description)) > 150) {
            $description .= "...";
        }
    }

    // Prepare images button
    $view_images_button = "";
    $all_files = array();

    // Check task images field
    if (isset($task->images) && $task->images) {
        $task_images = @json_decode($task->images, true);
        if ($task_images && is_array($task_images)) {
            $all_files = array_merge($all_files, $task_images);
        }
    }

    // Check comment files
    if (isset($task->all_comment_files_array) && is_array($task->all_comment_files_array)) {
        $all_files = array_merge($all_files, $task->all_comment_files_array);
    }

    if (!empty($all_files)) {
        $total_images = 0;
        foreach ($all_files as $file) {
            $file_name = isset($file['file_name']) ? $file['file_name'] : (isset($file['filename']) ? $file['filename'] : '');
            if ($file_name) {
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (in_array($file_ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
                    $total_images++;
                }
            }
        }

        if ($total_images > 0) {
            $files_json = htmlspecialchars(json_encode($all_files), ENT_QUOTES, 'UTF-8');
            $view_images_button = "<button class='btn btn-sm btn-outline-primary view-images-btn mt-2' data-task-id='" . $task->id . "' data-all-files='" . $files_json . "'><i data-feather='image' class='icon-14 me-1'></i>" . app_lang('view_images') . " (" . $total_images . ")</button>";
        }
    }

    // Build the card HTML - use safe access for potentially null values
    $task_project_id = isset($task->project_id) ? $task->project_id : 0;
    $task_new_sort = isset($task->new_sort) ? $task->new_sort : $task->id;
    $task_status_id = isset($task->status_id) ? $task->status_id : 7;
    $assigned_to_user = isset($task->assigned_to_user) ? $task->assigned_to_user : '';
    $assigned_to_avatar = isset($task->assigned_to_avatar) ? $task->assigned_to_avatar : '';
    
    // Use anchor element with kanban-item class for drag-and-drop compatibility
    $card_html = modal_anchor(get_uri("tasks/view"), 
        "<div class='backlog-task-inner'>
            <div class='backlog-task-header'>
                <div class='backlog-task-left'>
                    <span class='backlog-assigned-avatar'>
                        <img src='" . get_avatar($assigned_to_avatar) . "' title='" . htmlspecialchars($assigned_to_user) . "' data-bs-toggle='tooltip' />
                    </span>
                    <span class='backlog-task-title'>#" . $task->id . " " . htmlspecialchars($task->title) . "</span>
                </div>
                $collaborators_html
            </div>" .
            ($description ? "<div class='backlog-task-description'>" . htmlspecialchars($description) . "</div>" : "") .
            ($view_images_button ? "<div class='backlog-task-images'>$view_images_button</div>" : "") .
        "</div>",
        array(
            "class" => "kanban-item backlog-task-card d-block $disable_dragging",
            "data-status_id" => $task_status_id,
            "data-id" => $task->id,
            "data-project_id" => $task_project_id,
            "data-sort" => $task_new_sort,
            "data-post-id" => $task->id,
            "title" => app_lang('task_info') . " #$task->id",
            "data-modal-lg" => "1"
        )
    );

        return $card_html;
    }
}

$accordion_id = "backlog-accordion-" . uniqid();
?>

<div class="backlog-grouped-container" id="<?php echo $accordion_id; ?>">

    <?php if (!empty($general_tasks)) { ?>
    <!-- General Tasks Section -->
    <div class="backlog-project-section">
        <div class="backlog-project-header" data-bs-toggle="collapse" data-bs-target="#general-tasks-collapse-<?php echo $accordion_id; ?>" aria-expanded="true">
            <span class="backlog-toggle-icon"><i data-feather="chevron-down" class="icon-16"></i></span>
            <span class="backlog-project-title"><?php echo app_lang('general'); ?></span>
            <span class="backlog-task-count badge bg-secondary"><?php echo count($general_tasks); ?></span>
        </div>
        <div class="collapse show backlog-tasks-wrapper" id="general-tasks-collapse-<?php echo $accordion_id; ?>">
            <div class="backlog-tasks-list" data-status_id="7">
                <?php foreach ($general_tasks as $task) {
                    echo render_backlog_task_card($task, $tasks_edit_permissions);
                } ?>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php foreach ($tasks_by_project as $project_id => $project_data) { ?>
    <!-- Project Section: <?php echo htmlspecialchars($project_data['title']); ?> -->
    <div class="backlog-project-section">
        <div class="backlog-project-header" data-bs-toggle="collapse" data-bs-target="#project-<?php echo $project_id; ?>-collapse-<?php echo $accordion_id; ?>" aria-expanded="true">
            <span class="backlog-toggle-icon"><i data-feather="chevron-down" class="icon-16"></i></span>
            <span class="backlog-project-title"><?php echo htmlspecialchars($project_data['title']); ?></span>
            <span class="backlog-task-count badge bg-secondary"><?php echo count($project_data['tasks']); ?></span>
        </div>
        <div class="collapse show backlog-tasks-wrapper" id="project-<?php echo $project_id; ?>-collapse-<?php echo $accordion_id; ?>">
            <div class="backlog-tasks-list" data-status_id="7">
                <?php foreach ($project_data['tasks'] as $task) {
                    echo render_backlog_task_card($task, $tasks_edit_permissions);
                } ?>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php if (empty($general_tasks) && empty($tasks_by_project)) { ?>
    <div class="backlog-empty-state text-center text-muted py-4">
        <i data-feather="inbox" class="icon-32 mb-2"></i>
        <p><?php echo app_lang('no_record_found'); ?></p>
    </div>
    <?php } ?>

</div>

<style>
/* Backlog Column - Override kanban default height behavior */
.backlog-column,
#kanban-item-list-7 {
    min-height: 100px;
    overflow-y: auto;
    background: #f5f6f8;
}

.backlog-grouped-container {
    padding: 8px;
    min-height: auto !important;
}

.backlog-project-section {
    margin-bottom: 6px;
    background: transparent;
    border-radius: 4px;
}

.backlog-project-header {
    display: flex;
    align-items: center;
    padding: 6px 8px;
    background: #e9ecef;
    border-radius: 4px;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
    font-size: 12px;
}

.backlog-project-header:hover {
    background: #dee2e6;
}

.backlog-toggle-icon {
    margin-right: 6px;
    transition: transform 0.2s;
    display: flex;
    align-items: center;
}

.backlog-project-header[aria-expanded="false"] .backlog-toggle-icon {
    transform: rotate(-90deg);
}

.backlog-project-title {
    flex-grow: 1;
    font-weight: 600;
    font-size: 12px;
    color: #495057;
}

.backlog-task-count {
    font-size: 10px;
    padding: 2px 6px;
}

.backlog-tasks-wrapper {
    padding: 0 0 0 8px;
    margin-left: 8px;
    border-left: 1px dashed #ccc;
}

.backlog-tasks-list {
    display: flex;
    flex-direction: column;
    gap: 0;
    padding: 4px 0;
}

/* Tree-like task card with connector */
.backlog-task-card {
    position: relative;
    display: block;
    width: calc(100% - 12px);
    max-width: 100%;
    box-sizing: border-box;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 8px 10px;
    margin: 3px 0 3px 12px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    transition: box-shadow 0.2s, background-color 0.2s;
    cursor: grab;
    text-decoration: none;
    color: inherit;
}

/* Tree connector line */
.backlog-task-card::before {
    content: '';
    position: absolute;
    left: -13px;
    top: 50%;
    width: 12px;
    height: 1px;
    background: #ccc;
}

.backlog-task-card:hover {
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    background: #fafbfc;
    text-decoration: none;
    color: inherit;
}

.backlog-task-card.disable-dragging {
    cursor: default;
}

.backlog-task-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.backlog-task-left {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-grow: 1;
    min-width: 0;
}

.backlog-assigned-avatar img {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.12);
}

.backlog-task-title {
    font-size: 12px;
    font-weight: 500;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.backlog-task-link {
    color: #333;
    text-decoration: none;
}

.backlog-task-link:hover {
    color: #1672B9;
    text-decoration: underline;
}

.backlog-collaborators {
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.backlog-collaborator-avatar {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #fff;
    margin-left: -4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}

.backlog-collaborator-avatar:first-child {
    margin-left: 0;
}

.backlog-task-description {
    margin-top: 4px;
    font-size: 11px;
    color: #666;
    line-height: 1.3;
    padding-left: 28px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.backlog-task-images {
    margin-top: 4px;
    padding-left: 28px;
}

.backlog-task-images .btn {
    font-size: 10px;
    padding: 2px 6px;
}

.backlog-empty-state {
    color: #999;
    padding: 20px;
    text-align: center;
}

.backlog-empty-state i {
    display: block;
    margin: 0 auto 8px;
}
</style>

<script>
$(document).ready(function() {
    // Initialize feather icons for newly added content
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Toggle icon rotation on collapse
    $('.backlog-project-header').on('click', function() {
        var $this = $(this);
        var isExpanded = $this.attr('aria-expanded') === 'true';
        $this.attr('aria-expanded', !isExpanded);
    });

    // Override backlog column height to auto (remove empty space)
    function fixBacklogHeight() {
        var $backlogColumn = $('#kanban-item-list-7');
        if ($backlogColumn.length) {
            $backlogColumn.css({
                'height': 'auto',
                'min-height': '100px',
                'max-height': 'calc(100vh - 200px)'
            });
        }
    }
    fixBacklogHeight();
    
    // Re-apply after window resize (kanban JS may reset it)
    $(window).on('resize', function() {
        setTimeout(fixBacklogHeight, 100);
    });

    // Initialize Sortable.js on backlog nested task lists for drag-and-drop
    if (typeof Sortable !== 'undefined') {
        var isChrome = !!window.chrome && !!window.chrome.webstore;
        
        $('.backlog-tasks-list').each(function() {
            var $list = $(this);
            
            var options = {
                animation: 150,
                group: "kanban-item-list", // Same group as other kanban columns
                filter: ".disable-dragging",
                cancel: ".disable-dragging",
                draggable: ".kanban-item",
                onAdd: function(e) {
                    // Task moved into backlog from another column
                    var status_id = $list.attr("data-status_id") || "7";
                    if (typeof saveStatusAndSort === 'function') {
                        saveStatusAndSort($(e.item), status_id);
                    }
                    
                    var $countContainer = $("." + status_id + "-task-count");
                    $countContainer.html($countContainer.html().trim() * 1 + 1);
                    
                    var $item = $(e.item);
                    setTimeout(function() {
                        $item.attr("data-status_id", status_id);
                    });
                },
                onRemove: function(e) {
                    // Task moved out of backlog to another column
                    var status_id = $(e.item)[0].dataset.status_id;
                    var $countContainer = $("." + status_id + "-task-count");
                    $countContainer.html($countContainer.html().trim() * 1 - 1);
                },
                onUpdate: function(e) {
                    // Task reordered within backlog
                    if (typeof saveStatusAndSort === 'function') {
                        saveStatusAndSort($(e.item));
                    }
                }
            };
            
            // Chrome-specific options
            if (isChrome) {
                options.setData = function(dataTransfer, dragEl) {
                    var img = document.createElement("img");
                    var moveIcon = $("#move-icon");
                    if (moveIcon.length) {
                        img.src = moveIcon.attr("src");
                        img.style.opacity = 1;
                        dataTransfer.setDragImage(img, 5, 10);
                    }
                };
                options.ghostClass = "kanban-sortable-ghost";
                options.chosenClass = "kanban-sortable-chosen";
            }
            
            Sortable.create(this, options);
        });
    }
});
</script>
