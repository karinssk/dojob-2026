<?php
// Include task rendering functions
include_once 'task_list_functions.php';
?>

<link rel="stylesheet" href="<?php echo base_url('assets/css/task_list.css?v=' . time()); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/jira-modal.css?v=' . time()); ?>">

<!-- Kanban Board Styles -->
<style>
/* Kanban Board Styles */
#kanban-board-container {
    min-height: 500px;
}

.kanban-column {
    background: #f4f5f7;
    border-radius: 3px;
    padding: 8px;
    margin: 0 8px;
    min-height: 400px;
}

.kanban-column-header {
    padding: 8px 12px;
    font-weight: 600;
    color: #5e6c84;
    font-size: 12px;
    text-transform: uppercase;
    border-bottom: 1px solid #dfe1e6;
    margin-bottom: 8px;
}

.kanban-task-card {
    background: white;
    border-radius: 3px;
    padding: 8px 12px;
    margin-bottom: 8px;
    box-shadow: 0 1px 2px rgba(9, 30, 66, 0.25);
    cursor: pointer;
    transition: box-shadow 0.1s ease;
    border-left: 4px solid transparent;
}

.kanban-task-card:hover {
    box-shadow: 0 2px 4px rgba(9, 30, 66, 0.25);
}

.kanban-task-card.priority-high {
    border-left-color: #de350b;
}

.kanban-task-card.priority-medium {
    border-left-color: #ff8b00;
}

.kanban-task-card.priority-low {
    border-left-color: #36b37e;
}

.kanban-task-title {
    font-size: 14px;
    font-weight: 500;
    color: #172b4d;
    margin-bottom: 4px;
    line-height: 1.3;
}

.kanban-task-key {
    font-size: 11px;
    color: #6b778c;
    font-weight: 600;
    margin-bottom: 8px;
}

.kanban-task-preview-image {
    width: 100%;
    max-height: 120px;
    object-fit: cover;
    border-radius: 3px;
    margin-bottom: 8px;
    border: 1px solid #dfe1e6;
}

.kanban-task-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}

.kanban-task-assignee {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #dfe1e6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    color: #5e6c84;
}

.kanban-task-assignee img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

.kanban-task-labels {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}

.kanban-task-label {
    background: #dfe1e6;
    color: #5e6c84;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 2px;
    font-weight: 600;
}

.kanban-task-description {
    font-size: 12px;
    color: #6b778c;
    margin-bottom: 8px;
    line-height: 1.4;
    max-height: 40px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.kanban-add-card {
    color: #5e6c84;
    font-size: 14px;
    padding: 8px;
    text-align: center;
    cursor: pointer;
    border-radius: 3px;
    transition: background-color 0.1s ease;
}

.kanban-add-card:hover {
    background: #ebecf0;
}

/* Drag and drop styles */
.sortable-ghost {
    opacity: 0.4;
    background: #c8ebfb;
}

.sortable-chosen {
    box-shadow: 0 4px 8px rgba(9, 30, 66, 0.25);
}

.sortable-drag {
    transform: rotate(5deg);
}

.kanban-columns {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding-bottom: 16px;
}

.kanban-column {
    flex: 0 0 280px;
    max-width: 280px;
}

.kanban-tasks {
    min-height: 200px;
}

/* Task card hover effects */
.kanban-task-card {
    position: relative;
    overflow: hidden;
}

.kanban-task-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(9, 30, 66, 0.04);
    opacity: 0;
    transition: opacity 0.1s ease;
}

.kanban-task-card:hover::before {
    opacity: 1;
}

/* Image loading states */
.kanban-task-preview-image {
    transition: opacity 0.2s ease;
}

.kanban-task-preview-image[src=""] {
    display: none;
}

/* Status column colors */
.kanban-column[data-status="todo"] .kanban-column-header {
    color: #6b778c;
}

.kanban-column[data-status="in_progress"] .kanban-column-header {
    color: #0052cc;
}

.kanban-column[data-status="review"] .kanban-column-header {
    color: #ff8b00;
}

.kanban-column[data-status="done"] .kanban-column-header {
    color: #36b37e;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .kanban-columns {
        flex-direction: column;
    }
    
    .kanban-column {
        margin: 8px 0;
        flex: 1 1 auto;
        max-width: none;
    }
}

/* Inline Table Customization Styles */
.draggable-column {
    position: relative;
    transition: all 0.2s ease;
    resize: horizontal;
    overflow: hidden;
}

.draggable-column:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.draggable-column.dragging {
    opacity: 0.5;
    background-color: rgba(0, 123, 255, 0.1);
    transform: rotate(1deg);
}

.column-menu {
    opacity: 0.7; /* Make always visible for testing */
    transition: opacity 0.2s ease;
    position: relative;
    z-index: 1000;
    pointer-events: auto;
}

.draggable-column:hover .column-menu {
    opacity: 1;
}

/* Ensure dropdown menus are always clickable */
.column-menu .dropdown-menu {
    z-index: 1050;
    pointer-events: auto;
}

.column-menu .btn {
    pointer-events: auto;
    z-index: 1001;
}

.column-menu .btn:hover {
    background-color: rgba(0, 123, 255, 0.1);
    border-radius: 3px;
}

.table-column-hidden {
    display: none !important;
}

.column-drag-placeholder {
    background-color: rgba(0, 123, 255, 0.1);
    border: 2px dashed #007bff;
}

.sortable-ghost {
    opacity: 0.4;
    background: rgba(0, 123, 255, 0.1);
}

.sortable-chosen {
    background: rgba(0, 123, 255, 0.15);
}

/* Column reordering animation */
.column-reordering {
    transition: transform 0.3s ease;
}

/* Column resizing */
.column-resizer {
    position: absolute;
    top: 0;
    right: 0;
    width: 5px;
    height: 100%;
    cursor: col-resize;
    background: transparent;
    z-index: 10;
}

.column-resizer:hover {
    background: rgba(0, 123, 255, 0.3);
}

.column-resizer.resizing {
    background: #007bff;
}

.draggable-column.resizing {
    user-select: none;
}

/* Dropdown menu styling */
.dropdown-menu {
    border: 1px solid #dee2e6;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
}

.dropdown-item {
    padding: 8px 12px;
    font-size: 13px;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item i {
    width: 16px;
}

/* Body cursor during column resize */
body.col-resize {
    cursor: col-resize !important;
}

body.col-resize * {
    cursor: col-resize !important;
}
</style>

<!-- Load SortableJS from CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <!-- Project Header -->
            <div class="project-header bg-primary text-white p-3 mb-3 rounded">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo $project_info->title; ?></h3>
                        <div class="project-meta">
                            <span class="badge badge-light me-2">ID: <?php echo $project_info->id; ?></span>
                            <span class="badge badge-<?php echo $project_info->status == 'open' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($project_info->status); ?></span>
                        </div>
                    </div>
                    <div class="project-actions">
                        <?php echo modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='plus' class='icon-16'></i> " . app_lang('add_task'), array("class" => "btn btn-light btn-sm me-2", "title" => app_lang('add_task'), "data-post-project_id" => $project_info->id)); ?>
                        
                        <?php echo modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_multiple_tasks'), array("class" => "btn btn-outline-light btn-sm me-2", "title" => app_lang('add_multiple_tasks'), "data-post-project_id" => $project_info->id, "data-post-add_type" => "multiple")); ?>
                        
                        <div class="btn-group">
                            <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                Group <i data-feather="chevron-down" class="icon-16"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Assignee</a></li>
                                <li><a class="dropdown-item" href="#">Status</a></li>
                                <li><a class="dropdown-item" href="#">Priority</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Navigation Tabs -->
            <div class="card">
                <div class="card-header border-0 p-0">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#summary" role="tab">
                                <i data-feather="file-text" class="icon-16"></i> Summary
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#board" role="tab">
                                <i data-feather="columns" class="icon-16"></i> Board
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#list" role="tab">
                                <i data-feather="list" class="icon-16"></i> List
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#calendar" role="tab">
                                <i data-feather="calendar" class="icon-16"></i> Calendar
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-0">
                    <div class="tab-content">
                        <!-- Summary Tab -->
                        <div class="tab-pane fade" id="summary" role="tabpanel">
                            <div class="text-center p-5">
                                <h5>Project Summary</h5>
                                <p class="text-muted">Project overview and statistics will be displayed here.</p>
                            </div>
                        </div>
                        
                        <!-- Board Tab -->
                        <div class="tab-pane fade" id="board" role="tabpanel">
                            <div id="kanban-board-container" class="p-3">
                                <div class="text-center p-3" id="kanban-loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading Kanban Board...</p>
                                </div>
                                <div id="kanban-content" style="display: none;"></div>
                            </div>
                        </div>
                        
                        <!-- List Tab (Active) -->
                        <div class="tab-pane fade show active" id="list" role="tabpanel">
                            <div class="row">
                                <!-- Task List Column -->
                                <div class="col-md-12" id="task-list-column">
                                    <!-- Search and Filter Bar -->
                                    <div class="p-3 border-bottom bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="search-box me-3">
                                                    <i data-feather="search" class="icon-16 text-muted"></i>
                                                    <input type="text" class="form-control form-control-sm" placeholder="Search list" style="padding-left: 35px; width: 200px;">
                                                </div>
                                                <div class="dropdown me-2">
                                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i data-feather="filter" class="icon-16"></i> Filter
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item filter-option" href="#" data-filter="all">All Tasks</a></li>
                                                        <li><a class="dropdown-item filter-option" href="#" data-filter="todo">To Do</a></li>
                                                        <li><a class="dropdown-item filter-option" href="#" data-filter="in_progress">In Progress</a></li>
                                                        <li><a class="dropdown-item filter-option" href="#" data-filter="review">Review</a></li>
                                                        <li><a class="dropdown-item filter-option" href="#" data-filter="done">Done</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="text-muted me-3">Group by: None</span>
                                                <button class="btn btn-primary btn-sm me-2 add-root-task" data-parent-id="0">
                                                    <i data-feather="plus" class="icon-16"></i> Create Task
                                                </button>

                                                <button class="btn btn-outline-secondary btn-sm me-2">
                                                    <i data-feather="settings" class="icon-16"></i>
                                                </button>
                                                <button class="btn btn-outline-info btn-sm" onclick="testDragDrop()" title="Test drag & drop functionality">
                                                    <i data-feather="move" class="icon-16"></i> Test D&D
                                                </button>
                                                <button class="btn btn-outline-success btn-sm ms-2" onclick="testAddButtons()" title="Test add buttons">
                                                    <i data-feather="plus-circle" class="icon-16"></i> Test +
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Task List Container -->
                                    <div class="task-list-container" id="task-list-container">
                                        <div class="task-list-header">
                                            <div class="d-flex justify-content-between align-items-center p-3">
                                                <h6 class="mb-0">Tasks</h6>
                                                <button class="btn btn-sm btn-outline-primary add-root-task" data-parent-id="0">
                                                    <i data-feather="plus" class="icon-14"></i> Add Task
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Task Table -->
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="task-table">
                                                <thead class="table-light">
                                                    <tr style="background: #FAFBFC; border-bottom: 2px solid #DFE1E6;">
                                                        <th style="width: 30px; padding: 12px 8px; border: none;">
                                                            <input type="checkbox" id="select-all-tasks" style="transform: scale(1.1);" title="Select all">
                                                        </th>
                                                        <th class="draggable-column resizable-column" data-column="type" style="width: 40px; padding: 12px 8px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; position: relative; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span style="margin-left: 18px;">Type</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('type')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('type', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('type', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                        <li><hr class="dropdown-divider"></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="resetColumnWidth('type')"><i data-feather="maximize-2" class="icon-14 me-2"></i>Reset Width</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="column-resizer" data-column="type"></div>
                                                        </th>
                                                        <th class="draggable-column" data-column="key" style="width: 80px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Key</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('key')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('key', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('key', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="summary" style="padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Summary</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('summary')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('summary', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('summary', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="description" style="width: 200px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Description</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('description')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('description', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('description', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="comments" style="width: 120px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Comments</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('comments')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('comments', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('comments', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="status" style="width: 100px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Status</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('status')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('status', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('status', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="category" style="width: 100px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Category</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('category')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('category', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('category', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="assignee" style="width: 100px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Assignee</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('assignee')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('assignee', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('assignee', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="collaborators" style="width: 120px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Collaborators</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('collaborators')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('collaborators', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('collaborators', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="deadline" style="width: 120px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Deadline</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('deadline')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('deadline', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('deadline', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="priority" style="width: 80px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Priority</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('priority')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('priority', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('priority', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="labels" style="width: 100px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Labels</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('labels')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('labels', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('labels', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="level" style="width: 80px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Level</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('level')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('level', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('level', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="draggable-column" data-column="created" style="width: 120px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span>Created</span>
                                                                <div class="dropdown column-menu">
                                                                    <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                            <circle cx="12" cy="12" r="1"></circle>
                                                                            <circle cx="12" cy="5" r="1"></circle>
                                                                            <circle cx="12" cy="19" r="1"></circle>
                                                                        </svg>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#" onclick="hideColumn('created')"><i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('created', 'asc')"><i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="sortColumn('created', 'desc')"><i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th style="width: 40px; padding: 12px 8px; border: none;">
                                                            <i data-feather="plus" style="width: 16px; height: 16px; color: #6B778C;"></i>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                        <th style="width: 120px; padding: 12px 12px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase;">Created</th>
                                                        <th style="width: 40px; padding: 12px 8px; border: none;">
                                                            <i data-feather="plus" style="width: 16px; height: 16px; color: #6B778C;"></i>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody id="sortable-tasks">
                                                    <?php 
                                                    // Debug: Print task structure before rendering
                                                    echo "<!-- DEBUG: Task structure -->\n";
                                                    echo "<!-- Total tasks: " . count($tasks) . " -->\n";
                                                    foreach ($tasks as $task) {
                                                        $parentId = $task->parent_task_id ?? '0';
                                                        echo "<!-- Task ID: {$task->id}, Title: {$task->title}, Parent: {$parentId} -->\n";
                                                    }
                                                    echo "<!-- End DEBUG -->\n";
                                                    
                                                    echo render_hierarchical_tasks($tasks, 0, $project_id, $tasks); 
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Jira-style Create button at bottom left -->
                                        <div class="jira-bottom-actions" style="padding: 16px; border-top: 1px solid #DFE1E6; background: #FAFBFC;">
                                            <button class="jira-create-btn add-root-task" data-parent-id="0" style="
                                                background: none;
                                                border: none;
                                                color: #42526E;
                                                cursor: pointer;
                                                display: flex;
                                                align-items: center;
                                                gap: 6px;
                                                padding: 6px 8px;
                                                border-radius: 3px;
                                                transition: all 0.1s ease;
                                                font-size: 14px;
                                            ">
                                                <i data-feather="plus" style="width: 16px; height: 16px;"></i>
                                                Create
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Calendar Tab -->
                        <div class="tab-pane fade" id="calendar" role="tabpanel">
                            <div class="text-center p-5">
                                <h5>Calendar View</h5>
                                <p class="text-muted">Task calendar coming soon.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- JavaScript Configuration -->
<script>
// Configuration variables
var baseUrl = '<?php echo get_uri(); ?>';
var projectId = <?php echo isset($project_id) ? $project_id : 0; ?>;
</script>

<!-- Load Task List Modules in Order -->
<!-- Load utility modules first -->
<script src="<?php echo base_url('assets/js/modules/task-list-utils.js?v=' . time()); ?>"></script>

<!-- Load feature modules -->
<script src="<?php echo base_url('assets/js/modules/task-list-drag-drop.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/modules/task-list-inline-creation.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/modules/task-list-inline-editing.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/modules/task-list-hierarchy.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/modules/task-list-status-priority.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/modules/task-list-collaborators.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/modules/task-list-labels.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/modules/task-list-deadlines.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/modules/task-list-modals.js?v=' . time()); ?>"></script>

<!-- Load core module last (coordinates everything) -->
<script src="<?php echo base_url('assets/js/modules/task-list-core.js?v=' . time()); ?>"></script>

<!-- Optional: Load debug utilities -->
<script src="<?php echo base_url('assets/js/task_list_debug.js'); ?>"></script>

<script>
// Initialize modular task list system
$(document).ready(function() {
    console.log('🚀 Document ready - initializing modular task list...');
    
    // Check that all required modules are loaded
    const requiredFunctions = [
        'initDragDrop',
        'initInlineTaskCreation', 
        'initInlineEditing',
        'initExpandCollapse',
        'initStatusDropdowns',
        'showCollaboratorsDropdown',
        'showLabelsDropdown',
        'showDeadlinePicker',
        'initCheckboxes',
        'initSearch',
        'initFilters'
    ];
    
    let missingFunctions = [];
    requiredFunctions.forEach(func => {
        if (typeof window[func] !== 'function') {
            missingFunctions.push(func);
        }
    });
    
    if (missingFunctions.length > 0) {
        console.error("❌ Missing required functions:", missingFunctions);
        console.error("⚠️ Make sure all module files are loaded correctly!");
        return;
    }
    
    console.log(" All modular functions loaded successfully!");
    
    // Debug: Check what's actually in the DOM
    setTimeout(function() {
        console.log('=== DOM DEBUG ===');
        console.log('Table rows found:', $('tbody#sortable-tasks tr').length);
        console.log('Task rows (.task-row):', $('.task-row').length);
        console.log('Task items (.task-item):', $('.task-item').length);
        console.log('Jira task rows (.jira-task-row):', $('.jira-task-row').length);
        console.log('Status badges:', $('.status-badge').length);
        console.log('Drag handles:', $('.jira-drag-handle, .drag-handle').length);
        console.log('Expand toggles:', $('.expand-toggle-jira, .expand-toggle').length);
        console.log('Add buttons:', $('.btn-add-child').length);
        console.log('Collaborators containers:', $('.task-collaborators-container').length);
        console.log('Labels containers:', $('.task-labels-container').length);
        console.log('Deadline containers:', $('.task-deadline-container').length);
        
        // Show first few tasks structure
        $('tbody#sortable-tasks tr').each(function(index) {
            if (index < 3) {
                console.log(`Task ${index}:`, {
                    id: $(this).data('task-id'),
                    level: $(this).data('level'),
                    parent: $(this).data('parent-id'),
                    hasChildren: $(this).data('has-children'),
                    classes: $(this).attr('class')
                });
            }
        });
        
        // Initialize the modular task list system
        if (typeof initTaskList === 'function') {
            console.log('💪 Initializing modular task list...');
            initTaskList();
            console.log('🎉 Modular task list initialization complete!');
        } else {
            console.error('❌ initTaskList function not found');
        }
        
    }, 500);
    
    // Wait a bit for all assets to load, then add test functions
    setTimeout(function() {
        
        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
            console.log(' Feather icons initialized');
        }
        
        // Global test functions for debugging
        window.testModularSystem = function() {
            console.log(' Testing modular system...');
            
            console.log(' Available test functions:');
            console.log('  - testTaskListFunctions()');
            console.log('  - testEventHandlers()'); 
            console.log('  - testAllFunctionality()');
            console.log('  - testHierarchicalView()');
            
            console.log(' Available modules loaded:');
            console.log('  - Core:', typeof initTaskList);
            console.log('  - Drag Drop:', typeof initDragDrop);
            console.log('  - Inline Creation:', typeof initInlineTaskCreation);
            console.log('  - Inline Editing:', typeof initInlineEditing);
            console.log('  - Hierarchy:', typeof initExpandCollapse);
            console.log('  - Status/Priority:', typeof initStatusDropdowns);
            console.log('  - Collaborators:', typeof showCollaboratorsDropdown);
            console.log('  - Labels:', typeof showLabelsDropdown);
            console.log('  - Deadlines:', typeof showDeadlinePicker);
            console.log('  - Utils:', typeof initCheckboxes);
            
            return " Modular system test complete!";
        };
        
        // Add test function for add buttons
        window.testAddButtons = function() {
            console.log('===  TESTING ADD BUTTONS ===');
            
            // Test main add task buttons
            var $addRootButtons = $('.add-root-task');
            console.log('Add root task buttons found:', $addRootButtons.length);
            
            // Test subtask add buttons
            var $addSubtaskButtons = $('.add-subtask-jira, .add-subtask-btn, .btn-add-child');
            console.log('Add subtask buttons found:', $addSubtaskButtons.length);
            
            // Show button details
            $addRootButtons.each(function(index) {
                console.log(`Root button ${index}:`, {
                    text: $(this).text().trim(),
                    parentId: $(this).data('parent-id'),
                    visible: $(this).is(':visible'),
                    classes: $(this).attr('class')
                });
            });
            
            $addSubtaskButtons.each(function(index) {
                if (index < 5) { // Show first 5
                    console.log(`Subtask button ${index}:`, {
                        parentId: $(this).data('parent-id'),
                        visible: $(this).is(':visible'),
                        classes: $(this).attr('class'),
                        opacity: $(this).css('opacity')
                    });
                }
            });
            
            // Test clicking first root button
            if ($addRootButtons.length > 0) {
                console.log('Testing click on first root button...');
                $addRootButtons.first().click();
            }
            
            alert(` Add Buttons Test Complete!\n\nRoot buttons: ${$addRootButtons.length}\nSubtask buttons: ${$addSubtaskButtons.length}\n\nCheck console for details.`);
        };
        
        // Add test function for expand/collapse
        window.testExpandCollapse = function() {
            console.log('===  TESTING EXPAND/COLLAPSE ===');
            
            var $expandButtons = $('.expand-toggle, .expand-toggle-jira');
            console.log('Expand buttons found:', $expandButtons.length);
            
            $expandButtons.each(function(index) {
                if (index < 3) { // Show first 3
                    console.log(`Expand button ${index}:`, {
                        taskId: $(this).data('task-id'),
                        visible: $(this).is(':visible'),
                        classes: $(this).attr('class')
                    });
                }
            });
            
            // Test clicking first expand button
            if ($expandButtons.length > 0) {
                console.log('Testing click on first expand button...');
                $expandButtons.first().click();
            }
            
            alert(` Expand/Collapse Test Complete!\n\nExpand buttons: ${$expandButtons.length}\n\nCheck console for details.`);
        };
        
        // Add test function for inline editing
        window.testInlineEditing = function() {
            console.log('===  TESTING INLINE EDITING ===');
            
            var $titleDisplays = $('.task-title-display');
            console.log('Title displays found:', $titleDisplays.length);
            
            $titleDisplays.each(function(index) {
                if (index < 3) { // Show first 3
                    console.log(`Title display ${index}:`, {
                        taskId: $(this).data('task-id'),
                        text: $(this).text().trim(),
                        visible: $(this).is(':visible'),
                        classes: $(this).attr('class')
                    });
                }
            });
            
            // Test clicking first title display
            if ($titleDisplays.length > 0) {
                console.log('Testing click on first title display...');
                $titleDisplays.first().click();
            }
            
            alert(` Inline Editing Test Complete!\n\nTitle displays: ${$titleDisplays.length}\n\nCheck console for details.`);
        };
        
        console.log('🎯 Available test functions:');
        console.log('  - testModularSystem() - Test all modules');
        console.log('  - testAddButtons() - Test add functionality');
        console.log('  - testExpandCollapse() - Test hierarchy');
        console.log('  - testInlineEditing() - Test editing');
        console.log('  - testAllFunctionality() - Complete test suite');
        
        // Initialize inline table customization
        initInlineTableCustomization();
        
    }, 1000);
    
    // Handle Board tab click to load Kanban view
    $('a[href="#board"]').on('shown.bs.tab', function (e) {
        console.log('📋 Loading Kanban Board...');
        loadKanbanBoard();
    });
    
    // Function to load Kanban board
    window.loadKanbanBoard = function() {
        const $container = $('#kanban-content');
        const $loading = $('#kanban-loading');
        
        // Show loading state
        $loading.show();
        $container.hide();
        
        // Load kanban board via AJAX
        $.ajax({
            url: baseUrl + 'tasks/all_tasks_kanban/',
            type: 'GET',
            data: {
                project_id: projectId
            },
            dataType: 'html', // Expect HTML response
            success: function(response) {
                console.log(' Kanban board loaded successfully');
                
                try {
                    // Clean the response to prevent JavaScript conflicts
                    let cleanResponse = response;
                    
                    // Remove any script tags that might cause conflicts
                    cleanResponse = cleanResponse.replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '');
                    
                    // Remove any duplicate variable declarations
                    cleanResponse = cleanResponse.replace(/var\s+DefaultFilters\s*=/gi, '// var DefaultFilters =');
                    cleanResponse = cleanResponse.replace(/let\s+DefaultFilters\s*=/gi, '// let DefaultFilters =');
                    cleanResponse = cleanResponse.replace(/const\s+DefaultFilters\s*=/gi, '// const DefaultFilters =');
                    
                    // Hide loading and show content
                    $loading.hide();
                    $container.html(cleanResponse).show();
                    
                    // Wait a bit for DOM to be ready, then process with throttling
                    setTimeout(function() {
                        processTaskCardsThrottled();
                        initKanbanInteractions();
                        
                        // Initialize feather icons for kanban
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                    }, 200);
                    
                } catch (error) {
                    console.error('❌ Error processing kanban response:', error);
                    // Fallback: load without script cleaning
                    $loading.hide();
                    $container.html(response).show();
                    
                    setTimeout(function() {
                        processTaskCardsThrottled();
                        initKanbanInteractions();
                    }, 200);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Failed to load kanban board:', error);
                $loading.hide();
                $container.html(`
                    <div class="text-center p-5">
                        <div class="alert alert-danger">
                            <h5>Failed to load Kanban Board</h5>
                            <p>Error: ${error}</p>
                            <button class="btn btn-primary btn-sm" onclick="loadKanbanBoard()">
                                <i data-feather="refresh-cw" class="icon-16"></i> Retry
                            </button>
                        </div>
                    </div>
                `).show();
            }
        });
    };
    
    // Function to process task cards and add preview images (original - for compatibility)
    window.processTaskCards = function() {
        console.log('🖼️ Processing task cards for preview images...');
        processTaskCardsThrottled();
    };
    
    // Simple version - just add mock images for now to test the display
    window.processTaskCardsThrottled = function() {
        console.log('🖼️ Processing task cards for preview images...');
        
        // For now, let's just add mock images to test the display
        setTimeout(function() {
            addMockPreviewImages();
            console.log(' Mock images added for testing');
        }, 500);
    };
    
    // Simplified function to find and display task images
    window.loadTaskPreviewImage = function(taskId, $card) {
        // Skip if card already has an image
        if ($card.find('.kanban-task-preview-image').length > 0) {
            return;
        }
        
        console.log(`🔍 Looking for images for task ${taskId}...`);
        
        // First, try to find existing images in the card HTML
        tryFindExistingImages($card, taskId);
    };
    
    // Alternative approach - look for existing images in the kanban HTML
    window.tryFindExistingImages = function($card, taskId) {
        // Look for any existing img tags in the card that might be task images
        const $existingImages = $card.find('img').not('.kanban-task-preview-image');
        
        if ($existingImages.length > 0) {
            const $firstImage = $existingImages.first();
            const imageSrc = $firstImage.attr('src');
            
            if (imageSrc && !imageSrc.includes('avatar') && !imageSrc.includes('profile')) {
                // Clone the image as a preview
                const $previewImage = $firstImage.clone();
                $previewImage.addClass('kanban-task-preview-image');
                $previewImage.css({
                    'width': '100%',
                    'max-height': '120px',
                    'object-fit': 'cover',
                    'border-radius': '3px',
                    'margin': '8px 0',
                    'border': '1px solid #dfe1e6'
                });
                
                // Add to card
                const $title = $card.find('.kanban-task-title, .task-title, .card-title, h5, h6').first();
                if ($title.length) {
                    $title.after($previewImage);
                    console.log(` Used existing image for task ${taskId}`);
                }
            }
        }
    };
    
    // Helper function to find image files
    window.findImageFile = function(files) {
        const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg'];
        
        return files.find(file => {
            if (!file.file_name) return false;
            
            const fileName = file.file_name.toLowerCase();
            return imageExtensions.some(ext => fileName.includes(ext));
        });
    };
    
    // Function to add preview image to card
    window.addPreviewImageToCard = function(imageFile, $card) {
        // Try different URL patterns for file downloads
        const possibleUrls = [
            baseUrl + 'files/download/' + imageFile.id,
            baseUrl + 'files/download_file/' + imageFile.id,
            baseUrl + 'index.php/files/download/' + imageFile.id,
            baseUrl + 'uploads/files/' + imageFile.file_name,
            baseUrl + 'files/system/' + imageFile.file_name,
            imageFile.file_path || imageFile.url || imageFile.src
        ].filter(url => url); // Remove undefined/null values
        
        // Look for title element in various formats
        let $title = $card.find('.kanban-task-title, .task-title, .card-title, h5, h6').first();
        
        if ($title.length && !$card.find('.kanban-task-preview-image').length) {
            // Try the first URL, with fallbacks
            const primaryUrl = possibleUrls[0];
            
            $title.after(`
                <img src="${primaryUrl}" 
                     alt="Task preview" 
                     class="kanban-task-preview-image"
                     style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 3px; margin: 8px 0; border: 1px solid #dfe1e6;"
                     onerror="handleImageError(this, ${JSON.stringify(possibleUrls)})">
            `);
            console.log(` Added preview image for task ${$card.data('task-id')} with URL: ${primaryUrl}`);
        }
    };
    
    // Handle image loading errors by trying alternative URLs
    window.handleImageError = function(img, urls) {
        const $img = $(img);
        const currentSrc = $img.attr('src');
        const currentIndex = urls.indexOf(currentSrc);
        const nextIndex = currentIndex + 1;
        
        if (nextIndex < urls.length) {
            console.log(`Trying alternative URL for image: ${urls[nextIndex]}`);
            $img.attr('src', urls[nextIndex]);
        } else {
            // All URLs failed, hide the image
            console.log('All image URLs failed, hiding image');
            $img.hide();
        }
    };
    
    // Function to add mock preview images for testing
    window.addMockPreviewImages = function() {
        console.log('🎨 Adding mock preview images for testing...');
        
        const mockImages = [
            'https://picsum.photos/300/200?random=1',
            'https://picsum.photos/300/200?random=2', 
            'https://picsum.photos/300/200?random=3',
            'https://picsum.photos/300/200?random=4',
            'https://picsum.photos/300/200?random=5'
        ];
        
        // Find task cards in the kanban board
        const $taskCards = $('#kanban-content').find('.kanban-task-card, .task-card, [data-task-id], .card, .task-item');
        console.log(`Found ${$taskCards.length} task cards for mock images`);
        
        $taskCards.each(function(index) {
            const $card = $(this);
            
            // Skip if already has image
            if ($card.find('.kanban-task-preview-image').length > 0) {
                return;
            }
            
            // Add mock image to every 3rd card
            if (index % 3 === 0 && index < 15) { // Limit to first 15 cards
                const mockUrl = mockImages[index % mockImages.length];
                
                // Try to find a good place to insert the image
                let $insertAfter = $card.find('.kanban-task-title, .task-title, .card-title, h5, h6, .title').first();
                
                if (!$insertAfter.length) {
                    // If no title found, try other elements
                    $insertAfter = $card.find('p, div, span').first();
                }
                
                if ($insertAfter.length) {
                    $insertAfter.after(`
                        <div class="kanban-task-preview-container" style="margin: 8px 0;">
                            <img src="${mockUrl}" 
                                 alt="Task preview image" 
                                 class="kanban-task-preview-image mock-image"
                                 style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 3px; border: 1px solid #dfe1e6;">
                        </div>
                    `);
                    console.log(` Added mock image to card ${index}`);
                } else {
                    // Fallback: prepend to card
                    $card.prepend(`
                        <div class="kanban-task-preview-container" style="margin: 8px 0;">
                            <img src="${mockUrl}" 
                                 alt="Task preview image" 
                                 class="kanban-task-preview-image mock-image"
                                 style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 3px; border: 1px solid #dfe1e6;">
                        </div>
                    `);
                    console.log(` Added mock image to card ${index} (prepended)`);
                }
            }
        });
        
        const addedCount = $('.kanban-task-preview-image.mock-image').length;
        console.log(` Added ${addedCount} mock preview images`);
        
        return addedCount;
    };
    
    // Function to initialize kanban interactions
    window.initKanbanInteractions = function() {
        console.log('🎯 Initializing kanban interactions...');
        
        // Look for task cards with various selectors
        const taskSelectors = [
            '.kanban-task-card',
            '.task-card', 
            '.kanban-item',
            '[data-task-id]'
        ];
        
        let $taskCards = $();
        taskSelectors.forEach(selector => {
            $taskCards = $taskCards.add($(selector));
        });
        
        // Make task cards clickable to open task modal
        $taskCards.off('click.kanban').on('click.kanban', function(e) {
            e.preventDefault();
            const taskId = $(this).data('task-id') || $(this).attr('data-task-id');
            
            if (taskId) {
                // Try to use existing modal function if available
                if (typeof window.showTaskModal === 'function') {
                    window.showTaskModal(taskId);
                } else if (typeof window.loadTaskModal === 'function') {
                    window.loadTaskModal(taskId);
                } else {
                    // Fallback: try to trigger existing modal anchor
                    const modalUrl = baseUrl + 'tasks/modal_form/' + taskId;
                    console.log('Opening task modal:', modalUrl);
                    
                    // Try to find and trigger existing modal
                    const $existingModal = $(`[data-post-id="${taskId}"]`);
                    if ($existingModal.length) {
                        $existingModal.click();
                    } else {
                        // Create temporary modal anchor and click it
                        const $tempAnchor = $(`<a href="#" data-act="ajax-modal" data-action-url="${modalUrl}" style="display:none;"></a>`);
                        $('body').append($tempAnchor);
                        $tempAnchor.click();
                        setTimeout(() => $tempAnchor.remove(), 1000);
                    }
                }
            }
        });
        
        // Add drag and drop functionality if SortableJS is available
        if (typeof Sortable !== 'undefined') {
            // Look for kanban columns
            const columnSelectors = [
                '.kanban-column',
                '.kanban-col',
                '.board-column',
                '[data-status]'
            ];
            
            let $columns = $();
            columnSelectors.forEach(selector => {
                $columns = $columns.add($(selector));
            });
            
            $columns.each(function() {
                const columnElement = this;
                const $column = $(this);
                const columnStatus = $column.data('status');
                
                // Look for tasks container within column
                let tasksContainer = columnElement.querySelector('.kanban-tasks') || 
                                   columnElement.querySelector('.tasks') ||
                                   columnElement.querySelector('.task-list') ||
                                   columnElement;
                
                if (tasksContainer) {
                    new Sortable(tasksContainer, {
                        group: 'kanban',
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        onEnd: function(evt) {
                            const $item = $(evt.item);
                            const taskId = $item.data('task-id') || $item.attr('data-task-id');
                            const $newColumn = $(evt.to).closest('[data-status]');
                            const newStatus = $newColumn.data('status');
                            
                            if (taskId && newStatus) {
                                updateTaskStatus(taskId, newStatus);
                            }
                        }
                    });
                }
            });
        }
    };
    
    // Function to update task status via drag and drop
    window.updateTaskStatus = function(taskId, newStatus) {
        console.log(`📝 Updating task ${taskId} status to ${newStatus}`);
        
        // Try different endpoints for updating task status
        const endpoints = [
            baseUrl + 'tasks/update_task_status',
            baseUrl + 'tasks/save_task_status',
            baseUrl + 'projects/update_task_status'
        ];
        
        function tryUpdate(index) {
            if (index >= endpoints.length) {
                console.error('❌ All update endpoints failed');
                alert('Failed to update task status. Please try again.');
                loadKanbanBoard(); // Reload to revert changes
                return;
            }
            
            $.ajax({
                url: endpoints[index],
                type: 'POST',
                data: {
                    task_id: taskId,
                    status: newStatus,
                    id: taskId // Some endpoints might expect 'id' instead
                },
                success: function(response) {
                    console.log(' Task status updated successfully');
                    // Optionally show success message
                    if (response && response.success === false) {
                        tryUpdate(index + 1);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(`❌ Failed with endpoint ${endpoints[index]}:`, error);
                    tryUpdate(index + 1);
                }
            });
        }
        
        tryUpdate(0);
    };
    
    // Global function to refresh kanban board
    window.refreshKanbanBoard = function() {
        const $boardTab = $('#board');
        if ($boardTab.hasClass('active') || $boardTab.hasClass('show')) {
            loadKanbanBoard();
        }
    };
    
    // Inline Table Customization Functions
    window.initInlineTableCustomization = function() {
        console.log('🔧 Initializing inline table customization...');
        
        // Initialize column drag and drop
        initColumnDragDrop();
        
        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    };
    
    window.initColumnDragDrop = function() {
        if (typeof Sortable !== 'undefined') {
            // Make table header row sortable
            new Sortable(document.querySelector('#task-table thead tr'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'dragging',
                filter: 'th:first-child', // Don't allow dragging the checkbox column
                onStart: function(evt) {
                    console.log('🔄 Started dragging column:', evt.item.dataset.column);
                },
                onEnd: function(evt) {
                    console.log(' Finished dragging column');
                    reorderTableColumns(evt.oldIndex, evt.newIndex);
                }
            });
        }
    };
    
    window.reorderTableColumns = function(oldIndex, newIndex) {
        console.log(`🔄 Reordering column from ${oldIndex} to ${newIndex}`);
        
        // Reorder all table body cells to match header
        $('#task-table tbody tr').each(function() {
            const $row = $(this);
            const $cells = $row.children('td');
            
            if (oldIndex < $cells.length && newIndex < $cells.length) {
                const $cellToMove = $cells.eq(oldIndex);
                const $targetCell = $cells.eq(newIndex);
                
                if (oldIndex < newIndex) {
                    $cellToMove.insertAfter($targetCell);
                } else {
                    $cellToMove.insertBefore($targetCell);
                }
            }
        });
        
        showAlert('success', 'Column reordered successfully!');
        saveColumnOrder();
    };
    
    window.hideColumn = function(columnName) {
        console.log(`👁️ Hiding column: ${columnName}`);
        
        const $column = $(`.draggable-column[data-column="${columnName}"]`);
        const columnIndex = $column.index();
        
        // Hide header
        $column.addClass('table-column-hidden').hide();
        
        // Hide all cells in this column
        $(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).addClass('table-column-hidden').hide();
        
        showAlert('success', `${columnName.charAt(0).toUpperCase() + columnName.slice(1)} column hidden`);
        saveColumnVisibility();
    };
    
    window.sortColumn = function(columnName, direction) {
        console.log(`🔄 Sorting column ${columnName} ${direction}`);
        
        const $table = $('#task-table');
        const $tbody = $table.find('tbody');
        const $rows = $tbody.find('tr').toArray();
        
        const columnIndex = $(`.draggable-column[data-column="${columnName}"]`).index();
        
        $rows.sort(function(a, b) {
            const aText = $(a).find(`td:nth-child(${columnIndex + 1})`).text().trim();
            const bText = $(b).find(`td:nth-child(${columnIndex + 1})`).text().trim();
            
            let comparison = 0;
            if (aText > bText) {
                comparison = 1;
            } else if (aText < bText) {
                comparison = -1;
            }
            
            return direction === 'desc' ? comparison * -1 : comparison;
        });
        
        // Clear and re-append sorted rows
        $tbody.empty().append($rows);
        
        // Update sort indicators
        $('.draggable-column').removeClass('sorted-asc sorted-desc');
        $(`.draggable-column[data-column="${columnName}"]`).addClass(`sorted-${direction}`);
        
        showAlert('success', `Table sorted by ${columnName} (${direction}ending)`);
    };
    
    window.showAlert = function(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const $alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append($alert);
        
        setTimeout(() => {
            $alert.alert('close');
        }, 3000);
    };
    
    // Note: saveColumnOrder and saveColumnVisibility functions are defined later with database support
    
    // Note: loadSavedCustomization function is defined later with database support
    
    // Add context menu for right-click on columns
    window.initColumnContextMenu = function() {
        $('.draggable-column').on('contextmenu', function(e) {
            e.preventDefault();
            const columnName = $(this).data('column');
            
            // Create context menu
            const $contextMenu = $(`
                <div class="context-menu position-absolute bg-white border rounded shadow" style="z-index: 1000;">
                    <a class="dropdown-item" href="#" onclick="hideColumn('${columnName}')">
                        <i data-feather="eye-off" class="icon-14 me-2"></i>Hide Column
                    </a>
                    <a class="dropdown-item" href="#" onclick="sortColumn('${columnName}', 'asc')">
                        <i data-feather="arrow-up" class="icon-14 me-2"></i>Sort Ascending
                    </a>
                    <a class="dropdown-item" href="#" onclick="sortColumn('${columnName}', 'desc')">
                        <i data-feather="arrow-down" class="icon-14 me-2"></i>Sort Descending
                    </a>
                </div>
            `);
            
            // Position and show context menu
            $contextMenu.css({
                left: e.pageX,
                top: e.pageY
            });
            
            $('body').append($contextMenu);
            
            // Hide context menu on click outside
            $(document).one('click', function() {
                $contextMenu.remove();
            });
            
            // Replace feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    };
    
    // Test function for kanban
    window.testKanbanBoard = function() {
        console.log(' Testing Kanban Board...');
        
        // Test loading
        loadKanbanBoard();
        
        setTimeout(function() {
            console.log('=== KANBAN DEBUG ===');
            console.log('Kanban container:', $('#kanban-content').length);
            console.log('Task cards found:', $('.kanban-task-card, .task-card, [data-task-id]').length);
            console.log('Columns found:', $('.kanban-column, [data-status]').length);
            console.log('Preview images:', $('.kanban-task-preview-image').length);
            
            alert(' Kanban test complete! Check console for details.');
        }, 2000);
    };
    
    // Simple test function for images
    window.testKanbanImages = function() {
        console.log('🖼️ Testing Kanban Images...');
        
        // First load the kanban board
        loadKanbanBoard();
        
        setTimeout(function() {
            console.log('=== IMAGE TEST ===');
            console.log('Kanban content loaded:', $('#kanban-content').html().length > 0);
            
            // Check what task cards we have
            const $allCards = $('#kanban-content').find('*').filter(function() {
                return $(this).data('task-id') || $(this).attr('data-task-id');
            });
            console.log(`Found ${$allCards.length} elements with task IDs`);
            
            // Try to add mock images
            const mockCount = addMockPreviewImages();
            
            setTimeout(function() {
                const totalImages = $('.kanban-task-preview-image').length;
                console.log(`Total images now: ${totalImages}`);
                
                // Show results
                alert(` Image test complete!\n\nTask cards found: ${$allCards.length}\nMock images added: ${mockCount}\nTotal images: ${totalImages}\n\nCheck console for details.`);
            }, 1000);
        }, 2000);
    };
    
    // Function to add image upload capability to task cards
    window.addImageUploadToCards = function() {
        console.log('� Adding immage upload capability to task cards...');
        
        // Find task cards
        const $taskCards = $('#kanban-content').find('[data-task-id]');
        console.log(`Found ${$taskCards.length} task cards`);
        
        $taskCards.each(function() {
            const $card = $(this);
            const taskId = $card.data('task-id') || $card.attr('data-task-id');
            
            // Skip if already has upload button
            if ($card.find('.image-upload-btn').length > 0) {
                return;
            }
            
            // Add upload button
            const $uploadBtn = $(`
                <button class="image-upload-btn btn btn-sm btn-outline-secondary" 
                        data-task-id="${taskId}"
                        style="margin: 4px; font-size: 11px; padding: 2px 6px;">
                    <i class="fas fa-image"></i> Add Image
                </button>
            `);
            
            // Add hidden file input
            const $fileInput = $(`
                <input type="file" 
                       class="image-upload-input" 
                       data-task-id="${taskId}"
                       accept="image/*" 
                       style="display: none;">
            `);
            
            $card.append($uploadBtn).append($fileInput);
        });
        
        // Handle upload button clicks
        $(document).off('click', '.image-upload-btn').on('click', '.image-upload-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const taskId = $(this).data('task-id');
            const $fileInput = $(`.image-upload-input[data-task-id="${taskId}"]`);
            $fileInput.click();
        });
        
        // Handle file selection
        $(document).off('change', '.image-upload-input').on('change', '.image-upload-input', function(e) {
            const file = e.target.files[0];
            const taskId = $(this).data('task-id');
            
            if (file && file.type.startsWith('image/')) {
                console.log(`📷 Selected image for task ${taskId}:`, file.name);
                handleImageUpload(file, taskId);
            }
        });
        
        console.log(' Image upload capability added');
    };
    
    // Handle image upload
    window.handleImageUpload = function(file, taskId) {
        console.log(`📤 Uploading image for task ${taskId}...`);
        
        // Create preview immediately
        const reader = new FileReader();
        reader.onload = function(e) {
            const $card = $(`[data-task-id="${taskId}"]`);
            
            // Remove existing preview
            $card.find('.kanban-task-preview-image').remove();
            
            // Add new preview
            const $title = $card.find('.kanban-task-title, .task-title, .card-title, h5, h6').first();
            if ($title.length) {
                $title.after(`
                    <div class="kanban-task-preview-container" style="margin: 8px 0;">
                        <img src="${e.target.result}" 
                             alt="Task preview" 
                             class="kanban-task-preview-image uploaded-image"
                             style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 3px; border: 1px solid #dfe1e6;">
                        <div class="image-status" style="font-size: 10px; color: #666; margin-top: 2px;">
                            📤 Uploading...
                        </div>
                    </div>
                `);
            }
        };
        reader.readAsDataURL(file);
        
        // Simulate upload (replace with actual upload logic)
        setTimeout(function() {
            const $status = $(`[data-task-id="${taskId}"] .image-status`);
            $status.html(' Uploaded').css('color', '#28a745');
            
            console.log(` Image uploaded for task ${taskId}`);
        }, 2000);
    };
    
    // Test function for upload functionality
    window.testImageUpload = function() {
        console.log(' Testing image upload functionality...');
        
        // Load kanban first
        loadKanbanBoard();
        
        setTimeout(function() {
            // Add upload capability
            addImageUploadToCards();
            
            const uploadButtons = $('.image-upload-btn').length;
            alert(` Upload test ready!\n\nUpload buttons added: ${uploadButtons}\n\nClick any "Add Image" button to test!`);
        }, 2000);
    };
    
    // Function to load kanban without images (for performance)
    window.loadKanbanBoardFast = function() {
        console.log('🚀 Loading Kanban Board (fast mode - no images)...');
        
        const $container = $('#kanban-content');
        const $loading = $('#kanban-loading');
        
        // Show loading state
        $loading.show();
        $container.hide();
        
        // Load kanban board via AJAX
        $.ajax({
            url: baseUrl + 'tasks/all_tasks_kanban/',
            type: 'GET',
            data: {
                project_id: projectId
            },
            dataType: 'html',
            success: function(response) {
                console.log(' Kanban board loaded successfully (fast mode)');
                
                try {
                    // Clean the response to prevent JavaScript conflicts
                    let cleanResponse = response;
                    cleanResponse = cleanResponse.replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '');
                    cleanResponse = cleanResponse.replace(/var\s+DefaultFilters\s*=/gi, '// var DefaultFilters =');
                    
                    // Hide loading and show content
                    $loading.hide();
                    $container.html(cleanResponse).show();
                    
                    // Only initialize interactions, skip image processing
                    setTimeout(function() {
                        initKanbanInteractions();
                        
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                        
                        console.log(' Kanban board ready (images disabled for performance)');
                    }, 100);
                    
                } catch (error) {
                    console.error('❌ Error processing kanban response:', error);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Failed to load kanban board:', error);
                $loading.hide();
                $container.html(`
                    <div class="text-center p-5">
                        <div class="alert alert-danger">
                            <h5>Failed to load Kanban Board</h5>
                            <p>Error: ${error}</p>
                            <button class="btn btn-primary btn-sm" onclick="loadKanbanBoardFast()">
                                <i data-feather="refresh-cw" class="icon-16"></i> Retry
                            </button>
                        </div>
                    </div>
                `).show();
            }
        });
    };
    
    // Initialize inline table customization when document is ready
    setTimeout(function() {
        initInlineTableCustomization();
        loadSavedCustomization();
    }, 1500);
    
    // Inline Table Customization Functions
    window.initInlineTableCustomization = function() {
        console.log('🔧 Initializing inline table customization...');
        
        // Initialize column drag and drop
        initColumnDragDrop();
        
        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    };
    
    window.initColumnDragDrop = function() {
        if (typeof Sortable !== 'undefined') {
            // Make table header row sortable
            new Sortable(document.querySelector('#task-table thead tr'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'dragging',
                filter: 'th:first-child', // Don't allow dragging the checkbox column
                onStart: function(evt) {
                    console.log('🔄 Started dragging column:', evt.item.dataset.column);
                },
                onEnd: function(evt) {
                    console.log(' Finished dragging column');
                    reorderTableColumns(evt.oldIndex, evt.newIndex);
                }
            });
        }
    };
    
    window.reorderTableColumns = function(oldIndex, newIndex) {
        console.log(`🔄 Reordering column from ${oldIndex} to ${newIndex}`);
        
        // Reorder all table body cells to match header
        $('#task-table tbody tr').each(function() {
            const $row = $(this);
            const $cells = $row.children('td');
            
            if (oldIndex < $cells.length && newIndex < $cells.length) {
                const $cellToMove = $cells.eq(oldIndex);
                const $targetCell = $cells.eq(newIndex);
                
                if (oldIndex < newIndex) {
                    $cellToMove.insertAfter($targetCell);
                } else {
                    $cellToMove.insertBefore($targetCell);
                }
            }
        });
        
        showAlert('success', 'Column reordered successfully!');
        saveColumnOrder();
    };
    
    window.hideColumn = function(columnName) {
        console.log(`👁️ Hiding column: ${columnName}`);
        
        const $column = $(`.draggable-column[data-column="${columnName}"]`);
        const columnIndex = $column.index();
        
        // Hide header
        $column.addClass('table-column-hidden').hide();
        
        // Hide all cells in this column
        $(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).addClass('table-column-hidden').hide();
        
        showAlert('success', `${columnName.charAt(0).toUpperCase() + columnName.slice(1)} column hidden`);
        saveColumnVisibility();
    };
    
    window.sortColumn = function(columnName, direction) {
        console.log(`🔄 Sorting column ${columnName} ${direction}`);
        
        const $table = $('#task-table');
        const $tbody = $table.find('tbody');
        const $rows = $tbody.find('tr').toArray();
        
        const columnIndex = $(`.draggable-column[data-column="${columnName}"]`).index();
        
        $rows.sort(function(a, b) {
            const aText = $(a).find(`td:nth-child(${columnIndex + 1})`).text().trim();
            const bText = $(b).find(`td:nth-child(${columnIndex + 1})`).text().trim();
            
            let comparison = 0;
            if (aText > bText) {
                comparison = 1;
            } else if (aText <script bText) {
                comparison = -1;
            }
            
            return direction === 'desc' ? comparison * -1 : comparison;
        });
        
        // Clear and re-append sorted rows
        $tbody.empty().append($rows);
        
        // Update sort indicators
        $('.draggable-column').removeClass('sorted-asc sorted-desc');
        $(`.draggable-column[data-column="${columnName}"]`).addClass(`sorted-${direction}`);
        
        showAlert('success', `Table sorted by ${columnName} (${direction}ending)`);
    };
    
    window.showAlert = function(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const $alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append($alert);
        
        setTimeout(() => {
            $alert.alert('close');
        }, 3000);
    };
    
    window.saveColumnOrder = function() {
        const columnOrder = [];
        $('.draggable-column').each(function() {
            columnOrder.push($(this).data('column'));
        });
        
        // Save to database
        $.ajax({
            url: baseUrl + 'table_preferences/save_column_order',
            type: 'POST',
            data: {
                table_name: 'task_list',
                column_order: columnOrder
            },
            success: function(response) {
                if (response.success) {
                    console.log('💾 Column order saved to database:', columnOrder);
                } else {
                    console.error('Failed to save column order:', response.message);
                    // Fallback to localStorage
                    localStorage.setItem('taskTableColumnOrder', JSON.stringify(columnOrder));
                }
            },
            error: function() {
                console.error('Error saving column order, using localStorage fallback');
                localStorage.setItem('taskTableColumnOrder', JSON.stringify(columnOrder));
            }
        });
    };
    
    window.saveColumnVisibility = function() {
        const hiddenColumns = [];
        $('.draggable-column.table-column-hidden').each(function() {
            hiddenColumns.push($(this).data('column'));
        });
        
        // Save to database
        $.ajax({
            url: baseUrl + 'table_preferences/save_column_visibility',
            type: 'POST',
            data: {
                table_name: 'task_list',
                hidden_columns: hiddenColumns
            },
            success: function(response) {
                if (response.success) {
                    console.log('💾 Column visibility saved to database:', hiddenColumns);
                } else {
                    console.error('Failed to save column visibility:', response.message);
                    // Fallback to localStorage
                    localStorage.setItem('taskTableHiddenColumns', JSON.stringify(hiddenColumns));
                }
            },
            error: function() {
                console.error('Error saving column visibility, using localStorage fallback');
                localStorage.setItem('taskTableHiddenColumns', JSON.stringify(hiddenColumns));
            }
        });
    };
    
    // Note: loadSavedCustomization function is defined later with full column reordering support
    
    // Fallback to localStorage if database is not available
    window.loadLocalStorageFallback = function() {
        console.log('📋 Loading from localStorage fallback...');
        
        // Load hidden columns from localStorage
        const hiddenColumns = localStorage.getItem('taskTableHiddenColumns');
        if (hiddenColumns) {
            try {
                const hidden = JSON.parse(hiddenColumns);
                hidden.forEach(columnName => {
                    const $column = $(`.draggable-column[data-column="${columnName}"]`);
                    const columnIndex = $column.index();
                    
                    if ($column.length > 0) {
                        $column.addClass('table-column-hidden').hide();
                        $(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).addClass('table-column-hidden').hide();
                    }
                });
                console.log(' Loaded hidden columns from localStorage:', hidden);
            } catch (e) {
                console.error('Error loading hidden columns from localStorage:', e);
            }
        }
    };
    
    // Add function to show hidden columns (for testing)
    window.showAllColumns = function() {
        console.log('👁️ Showing all columns...');
        
        $('.draggable-column.table-column-hidden').each(function() {
            const $column = $(this);
            const columnIndex = $column.index();
            
            $column.removeClass('table-column-hidden').show();
            $(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).removeClass('table-column-hidden').show();
        });
        
        // Save empty hidden columns to database
        $.ajax({
            url: baseUrl + 'table_preferences/save_column_visibility',
            type: 'POST',
            data: {
                table_name: 'task_list',
                hidden_columns: []
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'All columns are now visible');
                } else {
                    // Fallback to localStorage
                    localStorage.removeItem('taskTableHiddenColumns');
                    showAlert('success', 'All columns are now visible (localStorage)');
                }
            },
            error: function() {
                // Fallback to localStorage
                localStorage.removeItem('taskTableHiddenColumns');
                showAlert('success', 'All columns are now visible (localStorage fallback)');
            }
        });
    };
    
    // Add function to reset table customization
    window.resetTableCustomization = function() {
        console.log('🔄 Resetting table customization...');
        
        // Show all columns first
        $('.draggable-column.table-column-hidden').each(function() {
            const $column = $(this);
            const columnIndex = $column.index();
            
            $column.removeClass('table-column-hidden').show();
            $(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).removeClass('table-column-hidden').show();
        });
        
        // Reset preferences in database
        $.ajax({
            url: baseUrl + 'table_preferences/reset_preferences',
            type: 'POST',
            data: {
                table_name: 'task_list'
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Table customization reset to default');
                } else {
                    // Fallback to localStorage
                    localStorage.removeItem('taskTableColumnOrder');
                    localStorage.removeItem('taskTableHiddenColumns');
                    showAlert('success', 'Table customization reset (localStorage)');
                }
            },
            error: function() {
                // Fallback to localStorage
                localStorage.removeItem('taskTableColumnOrder');
                localStorage.removeItem('taskTableHiddenColumns');
                showAlert('success', 'Table customization reset (localStorage fallback)');
            }
        });
    };

    
    // Initialize table customization on page load
    setTimeout(function() {
        console.log('🔧 Initializing table customization...');
        
        // Initialize drag and drop for columns
        if (typeof Sortable !== 'undefined') {
            new Sortable(document.querySelector('#task-table thead tr'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'dragging',
                filter: 'th:first-child, .column-resizer',
                onEnd: function(evt) {
                    console.log(' Column reordered');
                    reorderTableColumns(evt.oldIndex, evt.newIndex);
                }
            });
        }
        
        // Initialize column resizing
        initColumnResizing();
        
        // Load saved customization from database
        loadSavedCustomization();
        
        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }, 2000);
    
    // Initialize column resizing
    window.initColumnResizing = function() {
        console.log('📏 Initializing column resizing...');
        
        let isResizing = false;
        let currentColumn = null;
        let startX = 0;
        let startWidth = 0;
        
        // Add resizers to all columns
        $('.draggable-column').each(function() {
            const $column = $(this);
            const columnName = $column.data('column');
            
            if (!$column.find('.column-resizer').length) {
                $column.append(`<div class="column-resizer" data-column="${columnName}"></div>`);
            }
        });
        
        // Handle resizer mouse events
        $(document).on('mousedown', '.column-resizer', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            isResizing = true;
            currentColumn = $(this).closest('.draggable-column');
            startX = e.pageX;
            startWidth = currentColumn.outerWidth();
            
            currentColumn.addClass('resizing');
            $(this).addClass('resizing');
            $('body').addClass('col-resize');
            
            console.log('🔄 Started resizing column:', currentColumn.data('column'));
        });
        
        $(document).on('mousemove', function(e) {
            if (!isResizing || !currentColumn) return;
            
            const diff = e.pageX - startX;
            const newWidth = Math.max(50, startWidth + diff); // Minimum 50px
            
            currentColumn.css('width', newWidth + 'px');
        });
        
        $(document).on('mouseup', function(e) {
            if (!isResizing || !currentColumn) return;
            
            const columnName = currentColumn.data('column');
            const newWidth = currentColumn.outerWidth();
            
            console.log(' Finished resizing column:', columnName, 'to', newWidth + 'px');
            
            // Save the new width
            saveColumnWidth(columnName, newWidth + 'px');
            
            // Clean up
            currentColumn.removeClass('resizing');
            $('.column-resizer').removeClass('resizing');
            $('body').removeClass('col-resize');
            
            isResizing = false;
            currentColumn = null;
        });
    };
    
    // Save column width
    window.saveColumnWidth = function(columnName, width) {
        // Get current widths
        const columnWidths = {};
        $('.draggable-column').each(function() {
            const $col = $(this);
            const name = $col.data('column');
            const currentWidth = $col.css('width');
            columnWidths[name] = currentWidth;
        });
        
        // Update the specific column
        columnWidths[columnName] = width;
        
        // Save to database
        $.ajax({
            url: baseUrl + 'table_preferences/save_column_widths',
            type: 'POST',
            data: {
                table_name: 'task_list',
                column_widths: columnWidths
            },
            success: function(response) {
                if (response.success) {
                    console.log('💾 Column width saved:', columnName, width);
                } else {
                    console.error('Failed to save column width:', response.message);
                }
            },
            error: function() {
                console.error('Error saving column width');
            }
        });
    };
    
    // Reset column width
    window.resetColumnWidth = function(columnName) {
        const $column = $(`.draggable-column[data-column="${columnName}"]`);
        
        // Default widths
        const defaultWidths = {
            'type': '40px',
            'key': '80px',
            'summary': '200px',
            'description': '200px',
            'comments': '120px',
            'status': '100px',
            'category': '100px',
            'assignee': '100px',
            'collaborators': '120px',
            'deadline': '120px',
            'priority': '80px',
            'labels': '100px',
            'level': '80px',
            'created': '120px'
        };
        
        const defaultWidth = defaultWidths[columnName] || '100px';
        $column.css('width', defaultWidth);
        
        saveColumnWidth(columnName, defaultWidth);
        showAlert('success', `${columnName} column width reset to default`);
    };
    
    // Apply column order function
    window.applyColumnOrder = function(columnOrder) {
        console.log('🔄 Applying saved column order:', columnOrder);
        
        try {
            const $table = $('#task-table');
            const $headerRow = $table.find('thead tr');
            
            // Create mapping of column names to their current positions
            const columnMapping = {};
            $headerRow.children('th').each(function(index) {
                const $header = $(this);
                const columnName = $header.data('column');
                if (columnName) {
                    columnMapping[columnName] = index;
                }
            });
            
            console.log('📋 Current column mapping:', columnMapping);
            
            // Build the new order array with original indices
            const newOrder = [0]; // Always keep checkbox column first
            columnOrder.forEach(columnName => {
                if (columnMapping[columnName] !== undefined) {
                    newOrder.push(columnMapping[columnName]);
                }
            });
            
            // Add any columns not in the saved order
            Object.entries(columnMapping).forEach(([columnName, index]) => {
                if (!columnOrder.includes(columnName)) {
                    newOrder.push(index);
                }
            });
            
            console.log('🔄 New column order indices:', newOrder);
            
            // Reorder header columns
            const $originalHeaders = $headerRow.children('th').detach();
            newOrder.forEach(originalIndex => {
                if ($originalHeaders[originalIndex]) {
                    $headerRow.append($originalHeaders[originalIndex]);
                }
            });
            
            // Reorder all body row cells
            $table.find('tbody tr').each(function() {
                const $row = $(this);
                const $originalCells = $row.children('td').detach();
                
                newOrder.forEach(originalIndex => {
                    if ($originalCells[originalIndex]) {
                        $row.append($originalCells[originalIndex]);
                    }
                });
            });
            
            console.log(' Column order applied successfully - headers and data moved together');
            
        } catch (error) {
            console.error('❌ Error applying column order:', error);
        }
    };
    
    // Load saved customization function
    window.loadSavedCustomization = function() {
        console.log('📋 Loading saved table customization from database...');
        
        $.ajax({
            url: baseUrl + 'table_preferences/get_preferences',
            type: 'POST',
            data: { table_name: 'task_list' },
            success: function(response) {
                if (response.success && response.preferences) {
                    const prefs = response.preferences;
                    
                    // Apply hidden columns
                    if (prefs.column_visibility && prefs.column_visibility.hidden_columns) {
                        prefs.column_visibility.hidden_columns.forEach(columnName => {
                            const $column = $(`.draggable-column[data-column="${columnName}"]`);
                            const columnIndex = $column.index();
                            
                            if ($column.length > 0) {
                                $column.addClass('table-column-hidden').hide();
                                $(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).addClass('table-column-hidden').hide();
                            }
                        });
                        console.log(' Applied hidden columns:', prefs.column_visibility.hidden_columns);
                    }
                    
                    // Apply column order
                    if (prefs.column_order && prefs.column_order.column_order) {
                        applyColumnOrder(prefs.column_order.column_order);
                        console.log(' Applied column order:', prefs.column_order.column_order);
                    }
                    
                    // Apply column widths
                    if (prefs.column_width && prefs.column_width.column_widths) {
                        Object.entries(prefs.column_width.column_widths).forEach(([columnName, width]) => {
                            const $column = $(`.draggable-column[data-column="${columnName}"]`);
                            if ($column.length > 0) {
                                $column.css('width', width);
                            }
                        });
                        console.log(' Applied column widths:', prefs.column_width.column_widths);
                    }
                } else {
                    console.log('No saved preferences found');
                }
            },
            error: function() {
                console.log('Error loading preferences, using localStorage fallback');
                // Fallback to localStorage
                const hiddenColumns = localStorage.getItem('taskTableHiddenColumns');
                if (hiddenColumns) {
                    try {
                        const hidden = JSON.parse(hiddenColumns);
                        hidden.forEach(columnName => {
                            const $column = $(`.draggable-column[data-column="${columnName}"]`);
                            const columnIndex = $column.index();
                            
                            if ($column.length > 0) {
                                $column.addClass('table-column-hidden').hide();
                                $(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).addClass('table-column-hidden').hide();
                            }
                        });
                    } catch (e) {
                        console.error('Error loading localStorage fallback:', e);
                    }
                }
            }
        });
    };

    // Initialize feather icons for dropdowns
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    
    // Add global test functions for debugging
    window.testHideColumn = function() {
        console.log(' Testing hideColumn function...');
        if (typeof window.hideColumn === 'function') {
            window.hideColumn('description');
        } else {
            console.error('❌ hideColumn function not found');
        }
    };
    
    window.testSortColumn = function() {
        console.log(' Testing sortColumn function...');
        if (typeof window.sortColumn === 'function') {
            window.sortColumn('summary', 'asc');
        } else {
            console.error('❌ sortColumn function not found');
        }
    };
    
    console.log('🔧 Test functions available: testHideColumn(), testSortColumn()');

});
</script>

<!-- Simple Table Customization Script -->
<script src="<?php echo base_url('table_customization_simple.js?v=' . time()); ?>"></script>