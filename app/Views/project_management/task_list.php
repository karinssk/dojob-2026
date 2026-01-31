<?php
// Include task rendering functions
include_once 'task_list_functions.php';

?>

<link rel="stylesheet" href="<?php echo base_url('assets/css/task_list.css?v=' . time()); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/jira-modal.css?v=' . time()); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/kanban-board.css?v=' . time() . '&clean=1'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/enhanced-kanban-board.css?v=' . time()); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/task-modal.css?v=' . time()); ?>">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<!-- Kanban Board Styles -->
<style>
/* Hide top navigation bar when board tab is active, but keep project header */
.board-tab-active .project-title-button-group-section,
.board-tab-active .title-button-group,
.board-tab-active .project-timer-box,
.board-tab-active .btn-group:not(#project-header-section .btn-group),
.board-tab-active .project-actions:not(#project-header-section .project-actions),
.board-tab-active .project-title-buttons {
    display: none !important;
}

/* Keep project header visible even when board tab is active */
.board-tab-active #project-header-section {
    display: block !important;
}

/* Hide specific top navigation elements */
.board-tab-active .page-title .title-button-group,
.board-tab-active .page-title .project-title-button-group-section {
    display: none !important;
}

/* Hide the entire top button area when board is active */
body.board-tab-active .project-title-button-group-section,
body.board-tab-active #project-timer-box,
body.board-tab-active .title-button-group {
    display: none !important;
}

/* Alternative selectors for different layouts */
body.board-tab-active .btn:contains("Reminders"),
body.board-tab-active .btn:contains("Settings"),
body.board-tab-active .btn:contains("Actions"),
body.board-tab-active .btn:contains("Start timer") {
    display: none !important;
}

/* Hide any button groups in the header area */
body.board-tab-active .page-title .btn-group,
body.board-tab-active .project-details-view .btn-group,
body.board-tab-active .project-title-section .btn-group {
    display: none !important;
}

/* Clean Kanban Board - Maximize Space Usage */
#kanban-board-container {
    min-height: calc(100vh - 180px) !important; /* Use most of viewport height */
    background: transparent;
    padding: 15px;
}

/* Remove extra margins and padding that create empty space */
.page-content {
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}

.container-fluid {
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}

/* Ensure the board tab content uses full height */
.tab-content {
    height: calc(100vh - 120px) !important;
}

.tab-pane {
    height: 100% !important;
}

/* Tailwind Board Styles - Maximize Available Space */
.tab-pane#board {
    padding: 0 !important;
    margin: 0 !important;
    height: calc(100vh - 120px) !important; /* Reduced from 200px to 120px */
    overflow: hidden;
}

.tab-pane#board .bg-blue-600 {
    margin: -15px -15px 0 -15px !important;
    height: 100%;
    display: flex;
    flex-direction: column;
}

/* Kanban board container scrolling - Use more space */
#kanban-board-container {
    flex: 1;

    height: calc(100vh - 180px) !important; /* Increased available height */
    padding: 15px;
}

#kanban-board-container .flex {
    min-height: 100%;
}

/* Column scrolling - Much taller columns */
.kanban-column {
    max-height: calc(100vh - 220px) !important; /* Increased from 350px */
     overflow-y: scroll;
    min-height: calc(100vh - 220px) !important; /* Set minimum height too */
}

.tasks-container {
    max-height: calc(100vh - 270px) !important; /* Increased from 400px */
    overflow-y: auto;
    min-height: calc(100vh - 270px) !important; /* Set minimum height */
}

/* Loading spinner animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Ensure Tailwind styles override Bootstrap */
.tab-pane#board * {
    box-sizing: border-box;
}

/* Scrollbar styling for webkit browsers */
.tasks-container::-webkit-scrollbar,
#kanban-board-container::-webkit-scrollbar {
    width: 6px;
}

.tasks-container::-webkit-scrollbar-track,
#kanban-board-container::-webkit-scrollbar-track {
    
    border-radius: 3px;
}

.tasks-container::-webkit-scrollbar-thumb,
#kanban-board-container::-webkit-scrollbar-thumb {
 
    border-radius: 3px;
}

.tasks-container::-webkit-scrollbar-thumb:hover,
#kanban-board-container::-webkit-scrollbar-thumb:hover {
   
}

.kanban-board {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 0;
}

.kanban-column {
 
    border-radius: 3px;
    padding: 0;
    min-width: 272px;
    max-width: 272px;
    min-height: 400px;
    display: flex;
    flex-direction: column;
    border: none;
}

.column-header {
    background: #ebecf0;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: #5e6c84;
    border-radius: 3px 3px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.column-title {
    font-size: 12px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.task-count {
    background: #dfe1e6;
    color: #42526e;
    padding: 2px 6px;
    border-radius: 2px;
    font-size: 11px;
    font-weight: 600;
}

.tasks-container {
    padding: 8px;
    flex: 1;
    background: #f4f5f7;
    border-radius: 0 0 3px 3px;
}

.kanban-task-card {
    background: white;
    border-radius: 3px;
    padding: 8px 12px;
    margin-bottom: 8px;

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

/* New Jira-style Task Cards */
.task-card {
    background: white;
    border-radius: 3px;
    padding: 12px;
    margin-bottom: 8px;
    box-shadow: 0 1px 2px rgba(9, 30, 66, 0.15);
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    position: relative;
}

.task-card:hover {
    box-shadow: 0 2px 4px rgba(9, 30, 66, 0.2);
    transform: translateY(-1px);
}

.task-card.dragging {
    opacity: 0.5;
    transform: rotate(2deg);
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.task-key {
    font-size: 11px;
    color: #5e6c84;
    font-weight: 600;
    text-transform: uppercase;
}

.task-content {
    margin-bottom: 8px;
}

.task-title {
    font-size: 14px;
    font-weight: 400;
    color: #172b4d;
    margin: 0 0 4px 0;
    line-height: 1.3;
}

.task-description {
    font-size: 12px;
    color: #5e6c84;
    line-height: 1.3;
    margin: 0;
}

.task-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}

.task-meta {
    display: flex;
    align-items: center;
    gap: 8px;
}

.deadline {
    font-size: 11px;
    color: #5e6c84;
    display: flex;
    align-items: center;
    gap: 4px;
}

.task-assignee {
    display: flex;
    align-items: center;
}

.assignee-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #dfe1e6;
    color: #5e6c84;
    font-size: 10px;
    font-weight: 600;
}

.assignee-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-initials {
    font-size: 10px;
    font-weight: 600;
}

.priority-indicator {
    display: flex;
    align-items: center;
    font-size: 12px;
}

.priority-high {
    color: #de350b;
}

.priority-medium {
    color: #ff8b00;
}

.priority-low {
    color: #36b37e;
}

.priority-normal {
    color: #5e6c84;
}

/* Task Images */
.task-images {
    margin: 8px 0;
    border-radius: 3px;
    overflow: hidden;
}

.task-images img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 3px;
}

.task-images.multiple-images {
    position: relative;
}

.image-overlay {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 2px 6px;
    border-radius: 2px;
    font-size: 11px;
    font-weight: 600;
}

/* Add Task Button */
.add-task-btn {
    background: none;
    border: none;
    color: #5e6c84;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.add-task-btn:hover {
    background: #dfe1e6;
    color: #42526e;
}

/* Drag and Drop States */
.tasks-container.drag-over {
    background: rgba(0, 82, 204, 0.05);
    border: 1px dashed #0052cc;
    border-radius: 3px;
}

/* Error Messages */
.error-message {
    color: #721c24;
    background: #f8d7da;
    padding: 20px;
    border-radius: 4px;
    text-align: center;
    margin: 20px;
}

.loading-board {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
    color: #5e6c84;
    font-size: 16px;
}

.loading-spinner {
    border: 3px solid #f4f5f7;
    border-top: 3px solid #0052cc;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
    opacity: 0;
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
 
}

.column-resizer.resizing {
   
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

/* Hide the secondary navigation tabs - use main header navigation instead */
#secondary-nav-tabs {
    display: none !important;
}

/* Additional fallback selectors */
.card .card-header.border-0.p-0 {
    display: none !important;
}

.nav.nav-tabs.card-header-tabs {
    display: none !important;
}

/* Ensure tab content is still visible when tabs are hidden */
.tab-content .tab-pane {
    display: none;
}

.tab-content .tab-pane.active,
.tab-content .tab-pane.show.active {
    display: block !important;
}

/* When secondary navigation is hidden, make sure the card body has proper spacing */
.card:has(#secondary-nav-tabs) .card-body {
    border-top: none !important;
    border-radius: 0.375rem !important;
}

/* Fallback for browsers that don't support :has() */
.card .card-body {
    border-top: none !important;
    border-radius: 0.375rem !important;
}

/* Show the project header section with proper styling */
#project-header-section {
    display: block !important;
    background: white !important;
    border-bottom: 1px solid #dee2e6 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

.project-selector .dropdown-toggle {
    min-width: 200px;
    text-align: left;
}

.project-selector .dropdown-menu {
    min-width: 300px;
    max-height: 400px;
    overflow-y: auto;
}

.project-selector .dropdown-item {
    padding: 8px 16px;
}

.project-selector .dropdown-item:hover {
    background-color: #f8f9fa;
}

.project-info h4 {
    color: #495057;
    font-weight: 600;
}

.project-meta .badge {
    font-size: 0.75rem;
}

/* Change background to white */
.page-content {
    background-color: white !important;
}

.card {
    background-color: white !important;
}

.card-body {
    background-color: white !important;
}

/* Remove any blue background from board tab */
.tab-pane#board .bg-blue-600 {
    background-color: white !important;
    color: #333 !important;
}

/* Ensure overall clean white background */
body {
    background-color: white !important;
}

.row {
    background-color: white !important;
}

.col-md-12 {
    background-color: white !important;
}

/* Hide top navigation bar when board tab is active */
.board-tab-active .project-title-button-group-section,
.board-tab-active .title-button-group,
.board-tab-active .project-timer-box,
.board-tab-active .btn-group,
.board-tab-active .project-actions,
.board-tab-active .project-title-buttons {
    display: none !important;
}

/* Hide specific top navigation elements */
.board-tab-active .page-title .title-button-group,
.board-tab-active .page-title .project-title-button-group-section {
    display: none !important;
}

/* Hide the entire top button area when board is active */
body.board-tab-active .project-title-button-group-section,
body.board-tab-active #project-timer-box,
body.board-tab-active .title-button-group {
    display: none !important;
}

/* Hide any button groups in the header area */
body.board-tab-active .page-title .btn-group,
body.board-tab-active .project-details-view .btn-group,
body.board-tab-active .project-title-section .btn-group {
    display: none !important;
}

/* More specific selectors for top navigation elements */
body.board-tab-active .project-title-section .project-title-button-group-section,
body.board-tab-active .project-title-section .title-button-group,
body.board-tab-active .project-title-section #project-timer-box,
body.board-tab-active .page-title .title-button-group {
    display: none !important;
}

/* Hide buttons by class and type */
body.board-tab-active .btn-group .btn,
body.board-tab-active .title-button-group .btn,
body.board-tab-active .project-actions .btn {
    display: none !important;
}

/* Hide the entire right side of project title section */
body.board-tab-active .project-title-section > div:last-child,
body.board-tab-active .page-title > div:last-child {
    display: none !important;
}

/* Universal button hiding in header area */
body.board-tab-active .project-title-section .btn,
body.board-tab-active .page-title .btn,
body.board-tab-active .project-details-view > .container-fluid > .row > .col-md-12 > .project-title-section .btn {
    display: none !important;
}
</style>

<!-- Load SortableJS from CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Load SweetAlert2 for better alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo base_url('assets/js/enhanced-kanban-board.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/nodejs-enhanced-kanban.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/simple-local-kanban.js?v=' . time()); ?>"></script>

<div class="page-content clearfix" ">
    <div class="row">
        <div class="col-md-12">
            <!-- Project Header with Project Selector -->
            <div class="project-header bg-white border-bottom p-3 mb-3" id="project-header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="project-selector me-3">
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="projectDropdown" data-bs-toggle="dropdown" aria-expanded="false" onclick="loadProjectDropdown()
">
                                    <i data-feather="folder" class="icon-16 me-2"></i>
                                    <span id="current-project-name"><?php echo $project_info->title; ?></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="projectDropdown" id="project-dropdown-menu">
                                    <li><h6 class="dropdown-header"><?php echo app_lang('switch_project'); ?></h6></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <!-- Projects will be loaded dynamically -->
                                    <li><a class="dropdown-item text-center text-muted" href="#" id="loading-projects">
                                        <i data-feather="loader" class="icon-16 me-2"></i>
                                        Loading projects...
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="project-info">
                            <h4 class="mb-1"><?php echo app_lang('project_task_list'); ?></h4>
                            <div class="project-meta">
                                <span class="badge badge-<?php echo $project_info->status == 'open' ? 'success' : 'secondary'; ?> me-2">
                                    <?php echo app_lang($project_info->status); ?>
                                </span>
                                <small class="text-muted">ID: <?php echo $project_info->id; ?></small>
                            </div>
                        </div>
                    </div>
                   
                </div>
            </div>

            <!-- Main Navigation Tabs (Hidden) -->
            <div class="card">
                <div class="card-header border-0 p-0" id="secondary-nav-tabs">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#summary" role="tab">
                                <i data-feather="file-text" class="icon-16"></i> Summary
                            </a>
                        </li>
                                                 <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#board" role="tab">
                                <i data-feather="columns" class="icon-16"></i> Board
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#list" role="tab">
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
                        
                        <!-- Board Tab (Active) -->
                        <div class="tab-pane fade show active" id="board" role="tabpanel">
                            <!-- White Background Board Design -->
                            <div class="bg-white text-sm text-dark" style="margin: -15px; min-height: calc(100vh - 200px); overflow-y: auto; padding: 15px;">
                                <!-- Controls -->
                               
                                <!-- Kanban Board Container -->
                                <div id="kanban-board-container" class="px-6 py-4" style="height: calc(100vh - 300px); overflow-y: auto;">
                                   
                                       <div class="loading-board flex justify-center items-center min-h-[200px] text-gray-800">
                                        
                                         
                                        <button
  onclick="window.autoInitializeBoard()" 
  class="flex justify-center gap-2 items-center mx-auto shadow-xl text-lg bg-gray-50 backdrop-blur-md lg:font-semibold isolation-auto border-gray-50 before:absolute before:w-full before:transition-all before:duration-700 before:hover:w-full before:-left-full before:hover:left-0 before:rounded-full before:bg-emerald-500 hover:text-gray-50 before:-z-10 before:aspect-square before:hover:scale-150 before:hover:duration-700 relative z-10 px-4 py-2 overflow-hidden border-2 rounded-full group"
>
  Explore
  <svg
    class="w-8 h-8 justify-end group-hover:rotate-90 group-hover:bg-gray-50 text-gray-50 ease-linear duration-300 rounded-full border border-gray-700 group-hover:border-none p-2 rotate-45"
    viewBox="0 0 16 19"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path
      d="M7 18C7 18.5523 7.44772 19 8 19C8.55228 19 9 18.5523 9 18H7ZM8.70711 0.292893C8.31658 -0.0976311 7.68342 -0.0976311 7.29289 0.292893L0.928932 6.65685C0.538408 7.04738 0.538408 7.68054 0.928932 8.07107C1.31946 8.46159 1.95262 8.46159 2.34315 8.07107L8 2.41421L13.6569 8.07107C14.0474 8.46159 14.6805 8.46159 15.0711 8.07107C15.4616 7.68054 15.4616 7.04738 15.0711 6.65685L8.70711 0.292893ZM9 18L9 1H7L7 18H9Z"
      class="fill-gray-800 group-hover:fill-gray-800"
    ></path>
  </svg>
</button>

                                    </div>

                                     <!-- From Uiverse.io by nathAd17 --> 

                                     
                                </div>
                                
                                <!-- Debug Controls (Remove in production) -->
                                <div class="px-6 py-2 border-t border-gray-200">
                                    <div class="flex gap-2 text-xs">
                                        <button onclick="window.autoInitializeBoard()" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-gray-800">
                                            🤖 Auto Load
                                        </button>
                                        <button onclick="window.testBoardLoading()" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-gray-800">
                                             Force Load
                                        </button>
                                        <button onclick="window.debugKanbanLoading()" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-gray-800">
                                            🔍 Debug Info
                                        </button>
                                        <button onclick="window.resetBoard()" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-gray-800">
                                            🔄 Reset Board
                                        </button>
                                        <button onclick="window.testAPIIntegration()" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded text-gray-800">
                                            🔗 Test API
                                        </button>
                                        <button onclick="window.testProjectsAPI()" class="bg-blue-200 hover:bg-blue-300 px-3 py-1 rounded text-gray-800">
                                            📋 Test Projects API
                                        </button>
                                        <button onclick="window.checkAuthStatus()" class="bg-green-200 hover:bg-green-300 px-3 py-1 rounded text-gray-800">
                                            🔐 Check Auth
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- List Tab -->
                        <div class="tab-pane fade" id="list" role="tabpanel">
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
                                        <div class="table-responsive" style="max-height: calc(70vh - 80px); overflow-x: auto; ">
                                            <table class="table table-hover" id="task-table" style="min-width: 1800px;">
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
                                                <tbody id="sortable-tasks">
                                                    <?php 
                                                    // DEBUG: Task data loading disabled for list tab - board tab is default
                                                    echo "<!-- List tab data loading disabled - using board tab as default -->\n";
                                                    echo "<!-- To re-enable, uncomment the render_hierarchical_tasks call below -->\n";
                                                    
                                                    // DISABLED: Task data loading for list tab
                                                    // echo render_hierarchical_tasks($tasks, 0, $project_id, $tasks); 
                                                    
                                                    // Show message that list tab is disabled
                                                    echo '<tr><td colspan="100%" class="text-center p-4">';
                                                    echo '<div class="alert alert-info">';
                                                    echo '<h5>List View Disabled</h5>';
                                                    echo '<p>The list view has been disabled. Please use the Board view instead.</p>';
                                                    echo '<p><small>Board tab is now the default view for better performance.</small></p>';
                                                    echo '</div>';
                                                    echo '</td></tr>';
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Pagination Controls -->
                                        <div class="d-flex justify-content-between align-items-center mt-3" id="task-pagination" style="display: flex !important; visibility: visible !important; opacity: 1 !important; min-height: 60px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px;">
                                            <div class="pagination-info">
                                                <span class="text-muted">Showing <span id="showing-start">1</span>-<span id="showing-end">10</span> of <span id="total-tasks">0</span> tasks</span>
                                            </div>
                                            <nav aria-label="Task pagination">
                                                <ul class="pagination pagination-sm mb-0">
                                                    <li class="page-item" id="prev-page">
                                                        <a class="page-link" href="#" aria-label="Previous">
                                                            <span aria-hidden="true">&laquo;</span>
                                                        </a>
                                                    </li>
                                                    <li class="page-item active" id="page-1">
                                                        <a class="page-link" href="#" data-page="1">1</a>
                                                    </li>
                                                    <li class="page-item" id="page-2">
                                                        <a class="page-link" href="#" data-page="2">2</a>
                                                    </li>
                                                    <li class="page-item" id="page-3">
                                                        <a class="page-link" href="#" data-page="3">3</a>
                                                    </li>
                                                    <li class="page-item" id="next-page">
                                                        <a class="page-link" href="#" aria-label="Next">
                                                            <span aria-hidden="true">&raquo;</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </nav>
                                            <div class="page-size-selector">
                                                <select class="form-select form-select-sm" id="page-size" style="width: auto;">
                                                    <option value="10" selected>10 per page</option>
                                                    <option value="25">25 per page</option>
                                                    <option value="50">50 per page</option>
                                                    <option value="100">100 per page</option>
                                                </select>
                                            </div>
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
var csrfToken = '<?php echo csrf_token(); ?>';
var csrfHash = '<?php echo csrf_hash(); ?>';

// Make CSRF token available globally
window.csrf_token = csrfHash;

// Project Selector Functions
window.loadProjectDropdown = function() {
    console.log('🔄 Loading project dropdown...');
    console.log('🔍 Checking if dropdown element exists...');
    
    const $loadingElement = $('#loading-projects');
    const $dropdownMenu = $('#project-dropdown-menu');
    
    console.log('📋 Loading element found:', $loadingElement.length);
    console.log('📋 Dropdown menu found:', $dropdownMenu.length);
    
    if ($loadingElement.length === 0) {
        console.error('❌ #loading-projects element not found!');
        return;
    }
    
    // Update loading text to show we're trying
    $loadingElement.html('<i data-feather="loader" class="icon-16 me-2"></i>Connecting to API...');
    
    // Use the correct API URL (baseUrl already includes index.php)
    const apiUrl = baseUrl + 'api/projects';
    console.log('🔗 API URL:', apiUrl);
    console.log('🔗 Full URL will be:', apiUrl);
    
    $.ajax({
        url: apiUrl,
        type: 'GET',
        dataType: 'json',
        timeout: 10000, // 10 second timeout
        success: function(response) {
            console.log(' API Response received:', response);
            console.log(' Response type:', typeof response);
            console.log(' Response success:', response.success);
            console.log(' Response data length:', response.data ? response.data.length : 'no data');
            
            // Check if response is a string (HTML) instead of JSON
            if (typeof response === 'string') {
                console.error('❌ Received HTML instead of JSON - possible authentication redirect');
                $loadingElement.html('<i data-feather="alert-circle" class="icon-16 me-2"></i>Authentication required');
                return;
            }
            
            if (response.success && response.data) {
                console.log('📋 Projects found:', response.data.length);
                console.log('📋 First project:', response.data[0]);
                console.log('🚀 Calling populateProjectDropdown...');
                populateProjectDropdown(response.data);
            } else {
                console.error('❌ Failed to load projects:', response.message || 'Unknown error');
                $loadingElement.html('<i data-feather="alert-circle" class="icon-16 me-2"></i>Error: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ AJAX Error details:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status,
                readyState: xhr.readyState
            });
            
            $loadingElement.html('<i data-feather="alert-circle" class="icon-16 me-2"></i>AJAX Error: ' + error);
            
            // Try fallback URL without index.php
            console.log('🔄 Trying fallback URL...');
            setTimeout(() => {
                tryFallbackProjectsAPI();
            }, 1000);
        }
    });
};

// Fallback function to try alternative URL
window.tryFallbackProjectsAPI = function() {
    const fallbackUrl = baseUrl + 'api/projects';
    console.log('🔗 Fallback API URL:', fallbackUrl);
    
    $.ajax({
        url: fallbackUrl,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log(' Fallback API Response:', response);
            console.log(' Response type:', typeof response);
            console.log(' Response.success:', response.success);
            console.log(' Response.data:', response.data);
            console.log(' Response.data length:', response.data ? response.data.length : 'no data');
            
            if (response.success && response.data) {
                console.log('📋 Projects found via fallback:', response.data.length);
                console.log('📋 First project sample:', response.data[0]);
                console.log('🚀 About to call populateProjectDropdown...');
                console.log('🔍 populateProjectDropdown function exists:', typeof populateProjectDropdown);
                
                try {
                    // Call the function and log the result
                    const result = populateProjectDropdown(response.data);
                    console.log(' populateProjectDropdown completed successfully, result:', result);
                } catch (error) {
                    console.error('❌ Error in populateProjectDropdown:', error);
                    console.error('❌ Error stack:', error.stack);
                    showProjectsError('Error populating dropdown: ' + error.message);
                }
            } else {
                console.error('❌ API response invalid:', {
                    success: response.success,
                    hasData: !!response.data,
                    dataType: typeof response.data,
                    response: response
                });
                showProjectsError('Failed to load projects: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Fallback AJAX Error:', error);
            showProjectsError('Error loading projects');
        }
    });
};

// Function to show error in dropdown
window.showProjectsError = function(message) {
    $('#loading-projects').html('<i data-feather="alert-circle" class="icon-16 me-2"></i>' + message);
    
    // Add retry button
    const dropdown = $('#project-dropdown-menu');
    dropdown.append(`
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item text-center" href="#" onclick="loadProjectDropdown()">
                <i data-feather="refresh-cw" class="icon-16 me-2"></i>
                Retry Loading Projects
            </a>
        </li>
    `);
    
    // Replace feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
};

window.populateProjectDropdown = function(projects) {
    console.log('🎯 populateProjectDropdown called with', projects.length, 'projects');
    
    const dropdown = $('#project-dropdown-menu');
    const currentProjectId = projectId;
    
    console.log('📋 Dropdown element found:', dropdown.length);
    console.log('📋 Current project ID:', currentProjectId);
    
    // Clear loading item
    const $loadingItem = $('#loading-projects');
    console.log('🧹 Removing loading item, found:', $loadingItem.length);
    $loadingItem.remove();
    
    // Add projects
    let addedCount = 0;
    projects.forEach(function(project) {
        console.log('🔍 Processing project:', project.id, project.title, 'Current:', currentProjectId);
        
        if (project.id != currentProjectId) {
            const item = $(`
                <li>
                    <a class="dropdown-item" href="#" onclick="switchProject(${project.id})">
                        <i data-feather="folder" class="icon-16 me-2"></i>
                        ${project.title}
                        <small class="text-muted d-block">${project.status}</small>
                    </a>
                </li>
            `);
            dropdown.append(item);
            addedCount++;
            console.log(' Added project to dropdown:', project.title);
        } else {
            console.log('⏭️ Skipped current project:', project.title);
        }
    });
    
    console.log('📊 Added', addedCount, 'projects to dropdown');
    
    // Add recent projects from localStorage
    console.log('📚 Adding recent projects...');
    addRecentProjectsToDropdown(dropdown, currentProjectId);
    
    // Replace feather icons
    if (typeof feather !== 'undefined') {
        console.log('🎨 Replacing feather icons...');
        feather.replace();
    }
    
    console.log(' populateProjectDropdown completed');
};

window.addRecentProjectsToDropdown = function(dropdown, currentProjectId) {
    const recentProjects = JSON.parse(localStorage.getItem('taskListRecentProjects') || '[]');
    
    if (recentProjects.length > 0) {
        dropdown.append('<li><hr class="dropdown-divider"></li>');
        dropdown.append('<li><h6 class="dropdown-header">Recent Projects</h6></li>');
        
        recentProjects.forEach(function(project) {
            if (project.id != currentProjectId) {
                const item = $(`
                    <li>
                        <a class="dropdown-item" href="#" onclick="switchProject(${project.id})">
                            <i data-feather="clock" class="icon-16 me-2"></i>
                            ${project.title}
                        </a>
                    </li>
                `);
                dropdown.append(item);
            }
        });
    }
};

window.switchProject = function(newProjectId) {
    console.log('🔄 Switching to project:', newProjectId);
    
    // Save to recent projects
    saveCurrentProjectToRecent();
    
    // Show loading state
    $('#current-project-name').html('<i class="spinner-border spinner-border-sm me-2"></i>Switching...');
    
    // Redirect to new project
    window.location.href = baseUrl + 'task_list?project_id=' + newProjectId;
};

window.saveCurrentProjectToRecent = function() {
    const currentProject = {
        id: projectId,
        title: $('#current-project-name').text().trim(),
        timestamp: Date.now()
    };
    
    let recentProjects = JSON.parse(localStorage.getItem('taskListRecentProjects') || '[]');
    
    // Remove if already exists
    recentProjects = recentProjects.filter(p => p.id != currentProject.id);
    
    // Add to beginning
    recentProjects.unshift(currentProject);
    
    // Keep only last 5
    recentProjects = recentProjects.slice(0, 5);
    
    localStorage.setItem('taskListRecentProjects', JSON.stringify(recentProjects));
    localStorage.setItem('taskListSelectedProject', currentProject.id);
};

// Function to check authentication status
window.checkAuthStatus = function() {
    console.log('🔐 Checking authentication status...');
    console.log('🔗 Base URL:', baseUrl);
    
    $.ajax({
        url: baseUrl + 'api/auth_check',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('🔐 Auth Response:', response);
            if (response.authenticated) {
                console.log(' User is authenticated, ID:', response.user_id);
                // Now try to load projects directly (skip auth check)
                console.log('🚀 Calling loadProjectDropdown directly...');
                loadProjectDropdown();
            } else {
                console.error('❌ User is not authenticated');
                $('#loading-projects').html('<i data-feather="alert-circle" class="icon-16 me-2"></i>Not authenticated');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Auth check failed:', error);
            console.log('🚀 Auth check failed, but trying to load projects anyway...');
            // Try loading projects anyway since the test shows the API works
            loadProjectDropdown();
        }
    });
};

// Debug function to test API directly
window.testProjectsAPI = function() {
    console.log(' Testing Projects API directly...');
    console.log('🔗 Base URL:', baseUrl);
    console.log('🔗 Full API URL:', baseUrl + 'api/projects');
    
    // Test with fetch first
    fetch(baseUrl + 'api/projects')
        .then(response => {
            console.log('📡 Fetch Response Status:', response.status);
            console.log('📡 Fetch Response Headers:', response.headers);
            return response.text();
        })
        .then(text => {
            console.log('📡 Fetch Response Text:', text);
            try {
                const json = JSON.parse(text);
                console.log('📡 Fetch Response JSON:', json);
            } catch (e) {
                console.error('❌ Failed to parse JSON:', e);
            }
        })
        .catch(error => {
            console.error('❌ Fetch Error:', error);
        });
    
    // Also test with jQuery
    $.get(baseUrl + 'api/projects')
        .done(function(data) {
            console.log(' jQuery Success:', data);
        })
        .fail(function(xhr, status, error) {
            console.error('❌ jQuery Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
        });
};

// Global function for column reordering - MUST be defined before any Sortable instances
window.reorderTableColumns = function(oldIndex, newIndex) {
    console.log(`🔄 Reordering column from ${oldIndex} to ${newIndex}`);
    console.log(`📊 Table has ${$('#task-table tbody tr').length} rows`);
    
    if (oldIndex === newIndex) {
        console.log('⚠️ Same position, no reordering needed');
        return;
    }
    
    // Reorder all table body cells to match header
    let rowsProcessed = 0;
    $('#task-table tbody tr').each(function() {
        const $row = $(this);
        const $cells = $row.children('td');
        
        console.log(`🔄 Processing row ${rowsProcessed + 1}, cells: ${$cells.length}`);
        
        // Skip empty rows or rows with insufficient cells
        if ($cells.length === 0) {
            console.log(`⚠️ Skipping empty row ${rowsProcessed + 1}`);
            return true; // Continue to next row
        }
        
        if (oldIndex < $cells.length && newIndex < $cells.length) {
            const $cellToMove = $cells.eq(oldIndex).detach();
            const $allCells = $row.children('td');
            
            if (newIndex === 0) {
                // Insert at the beginning
                $row.prepend($cellToMove);
            } else if (newIndex >= $allCells.length) {
                // Insert at the end
                $row.append($cellToMove);
            } else {
                // Insert at specific position
                if (oldIndex < newIndex) {
                    $cellToMove.insertAfter($allCells.eq(newIndex - 1));
                } else {
                    $cellToMove.insertBefore($allCells.eq(newIndex));
                }
            }
            rowsProcessed++;
        } else {
            console.warn(`⚠️ Invalid indices for row ${rowsProcessed + 1}: oldIndex=${oldIndex}, newIndex=${newIndex}, cells=${$cells.length}`);
        }
    });
    
    console.log(` Processed ${rowsProcessed} rows`);
    
    // Add visual feedback by temporarily highlighting the moved column
    setTimeout(function() {
        $('#task-table tbody tr').each(function() {
            $(this).children('td').eq(newIndex).css({
                'background-color': '#E3FCEF',
                'transition': 'background-color 0.3s ease'
            });
        });
        
        // Remove highlight after 2 seconds
        setTimeout(function() {
            $('#task-table tbody tr').each(function() {
                $(this).children('td').eq(newIndex).css('background-color', '');
            });
        }, 2000);
    }, 100);
    
    console.log(` Column reordered successfully! (${rowsProcessed} rows updated)`);
    
    // Try to call saveColumnOrder if it exists
    if (typeof saveColumnOrder === 'function') {
        console.log('💾 Calling saveColumnOrder...');
        saveColumnOrder();
    } else {
        console.warn('⚠️ saveColumnOrder function not found');
    }
};

// Global function for saving column order - MUST be defined early
window.saveColumnOrder = function() {
    console.log('💾 Saving column order...');
    const columnOrder = [];
    $('.draggable-column').each(function() {
        const columnName = $(this).data('column');
        if (columnName) {
            columnOrder.push(columnName);
        }
    });
    
    console.log('📋 Column order to save:', columnOrder);
    
    // Save to database
    $.ajax({
        url: baseUrl + 'table_preferences/save_column_order',
        type: 'POST',
        data: {
            table_name: 'task_list',
            column_order: columnOrder
        },
        success: function(response) {
            console.log('📡 AJAX Success - Full response:', response);
            if (response && response.success === true) {
                console.log(' Column order saved to database successfully!', columnOrder);
                console.log(' Server response:', response.message);
            } else {
                console.error('❌ Failed to save column order:', response ? response.message : 'No response');
                console.error('❌ Full response object:', response);
                // Fallback to localStorage
                localStorage.setItem('taskTableColumnOrder', JSON.stringify(columnOrder));
                console.log('💾 Saved to localStorage as fallback');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error saving column order:', error);
            console.error('❌ Response:', xhr.responseText);
            // Fallback to localStorage
            localStorage.setItem('taskTableColumnOrder', JSON.stringify(columnOrder));
            console.log('💾 Saved to localStorage as fallback');
        }
    });
};

console.log(" Global reorderTableColumns function defined");
console.log(" Global saveColumnOrder function defined");

// Test function for debugging
window.testTablePreferences = function() {
    console.log(' Testing table preferences controller...');
    
    // Test 1: Basic controller test
    $.ajax({
        url: baseUrl + 'table_preferences/test',
        type: 'GET',
        success: function(response) {
            console.log(' Controller test response:', response);
        },
        error: function(xhr, status, error) {
            console.error('❌ Controller test failed:', error);
            console.error('❌ Response text:', xhr.responseText);
        }
    });
    
    // Test 2: Save column order test
    $.ajax({
        url: baseUrl + 'table_preferences/save_column_order',
        type: 'POST',
        data: {
            table_name: 'task_list',
            column_order: ['test1', 'test2', 'test3']
        },
        success: function(response) {
            console.log(' Save test response:', response);
        },
        error: function(xhr, status, error) {
            console.error('❌ Save test failed:', error);
            console.error('❌ Response text:', xhr.responseText);
        }
    });
};

console.log(" Test function available: window.testTablePreferences()");
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
<script src="<?php echo base_url('assets/js/task-modal.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('verify-taskmodal.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('fix-modal-overlay.js?v=' . time()); ?>"></script>
<script src="<?php echo base_url('assets/js/modules/task-list-modals.js?v=' . time()); ?>"></script>

<!-- Load core module last (coordinates everything) -->
<script src="<?php echo base_url('assets/js/modules/task-list-core.js?v=' . time()); ?>"></script>

<!-- Optional: Load debug utilities -->
<script src="<?php echo base_url('assets/js/task_list_debug.js'); ?>"></script>

<!-- Debug Board Loading Script -->
<script src="<?php echo base_url('debug-board-loading.js?v=' . time()); ?>"></script>

<script>
// Initialize modular task list system
$(document).ready(function() {
    console.log('🚀 Document ready - initializing modular task list...');
    console.log('📋 Board tab is set as DEFAULT - List tab data loading DISABLED');
    
    // HIDE TOP NAVIGATION immediately since board is default active tab
    console.log('🔒 Hiding top navigation - Board is default active tab');
    $('body').addClass('board-tab-active');
    $('.page-content').addClass('board-tab-active');
    $('.project-details-view').addClass('board-tab-active');
    
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
        
        // Initialize priority dropdowns with real database data
        if (typeof convertPriorityIconsToDropdowns === 'function') {
            console.log('🎯 Initializing priority dropdowns with real data...');
            convertPriorityIconsToDropdowns();
        } else {
            console.error('❌ convertPriorityIconsToDropdowns function not found!');
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
    
    // Handle Board tab click to load Kanban view - DISABLED (using new system)
    // $('a[href="#board"]').on('shown.bs.tab', function (e) {
    //     console.log('📋 Loading Kanban Board...');
    //     loadKanbanBoard();
    // });
    
    // DISABLE LIST TAB DATA LOADING - Board is default
    $('a[href="#list"]').on('shown.bs.tab', function (e) {
        console.log('📋 List tab clicked - data loading disabled, redirecting to board...');
        // Prevent list tab activation and redirect to board
        e.preventDefault();
        $('a[href="#board"]').tab('show');
        return false;
    });
    
    // HIDE TOP NAVIGATION when board tab is active
    $('a[href="#board"]').on('shown.bs.tab', function (e) {
        console.log('📋 Board tab activated - hiding top navigation...');
        $('body').addClass('board-tab-active');
        $('.page-content').addClass('board-tab-active');
        $('.project-details-view').addClass('board-tab-active');
    });
    
    // SHOW TOP NAVIGATION when other tabs are active
    $('a[href="#summary"], a[href="#list"], a[href="#calendar"]').on('shown.bs.tab', function (e) {
        console.log('📋 Non-board tab activated - showing top navigation...');
        $('body').removeClass('board-tab-active');
        $('.page-content').removeClass('board-tab-active');
        $('.project-details-view').removeClass('board-tab-active');
    });
    
    // Function to load Kanban board - DISABLED (using new Node.js API system)
    window.loadKanbanBoard = function() {
        console.log('🚫 Old loadKanbanBoard called - using new Node.js API system instead');
        // The new system is handled in the DOMContentLoaded event listener below
        
        // Use the kanban board JavaScript class instead of AJAX
        if (window.initKanbanBoard) {
            const kanbanBoard = window.initKanbanBoard(projectId);
            console.log(' Kanban board initialized with Node.js API');
        } else {
            console.error('❌ Kanban board initialization function not found');
        }
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
            baseUrl + 'uploads/' + imageFile.file_name,
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
            
            console.log("🔍 Task card clicked, taskId:", taskId);
            
            if (taskId) {
                // Use our new TaskModal system
                if (window.taskModal && typeof window.taskModal.openTask === 'function') {
                    console.log(" Using TaskModal.openTask");
                    window.taskModal.openTask(taskId);
                } else if (window.TaskModal) {
                    console.log(" Creating new TaskModal instance");
                    window.taskModal = new TaskModal();
                    window.taskModal.openTask(taskId);
                } else {
                    console.log("❌ TaskModal not available, falling back to native modal");
                    // Fallback: try to trigger existing modal anchor
                    const $existingModal = $(`[data-post-id="${taskId}"]`);
                    if ($existingModal.length) {
                        $existingModal.click();
                    } else {
                        // Create temporary modal anchor and click it
                        const modalUrl = baseUrl + 'tasks/modal_form/' + taskId;
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
    
    // Function to load kanban without images (for performance) - DISABLED
    window.loadKanbanBoardFast = function() {
        console.log('🚫 Old loadKanbanBoardFast called - using new Node.js API system instead');
        if (window.kanbanBoard) {
            window.kanbanBoard.loadTasks();
        }
        
        // Show loading state
        $loading.show();
        $container.hide();
        
        // Load kanban board via Node.js API (fast mode)
        console.log('🚀 Loading kanban board via Node.js API (fast mode)...');
        
        // Use the kanban board JavaScript class instead of AJAX
        if (window.initKanbanBoard) {
            const kanbanBoard = window.initKanbanBoard(projectId);
            console.log(' Kanban board initialized with Node.js API (fast mode)');
            
            // Hide loading and show container
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
    
    // Note: Duplicate functions removed - using earlier definitions
    
    // saveColumnOrder function moved to global scope for early availability
    
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
    
    // Initialize project dropdown with auth check
    setTimeout(function() {
        checkAuthStatus();
    }, 1000);
    
    // Add test functions for debugging
    window.testDropdownDirectly = function() {
        console.log(' Testing dropdown directly...');
        console.log('🔍 Elements check:');
        console.log('  - #loading-projects:', $('#loading-projects').length);
        console.log('  - #project-dropdown-menu:', $('#project-dropdown-menu').length);
        console.log('  - baseUrl:', baseUrl);
        console.log('  - projectId:', projectId);
        
        // Test with known working data
        const testProjects = [
            {id: "125", title: "Test Project 1", status: "open"},
            {id: "122", title: "Test Project 2", status: "open"},
            {id: "117", title: "Test Project 3", status: "open"}
        ];
        
        console.log('🚀 Testing populateProjectDropdown with test data...');
        try {
            populateProjectDropdown(testProjects);
            console.log(' Test completed successfully');
        } catch (error) {
            console.error('❌ Test failed:', error);
        }
        
        return 'Test completed - check dropdown and console';
    };
    
    // Simple function to manually trigger the API call
    window.manualLoadProjects = function() {
        console.log('🔄 Manual load projects...');
        const fallbackUrl = baseUrl + 'api/projects';
        
        $.get(fallbackUrl)
            .done(function(data) {
                console.log(' Manual API success:', data);
                if (data.success && data.data) {
                    console.log('🚀 Calling populateProjectDropdown manually...');
                    populateProjectDropdown(data.data);
                }
            })
            .fail(function(error) {
                console.error('❌ Manual API failed:', error);
            });
    };
    
    // Test function to populate with known data
    window.testPopulateWithKnownData = function() {
        console.log(' Testing populate with known data...');
        const testData = [
            {id: "125", title: "81 - น้ำรั่ว", status: "open"},
            {id: "122", title: "บ้าน99 - ออฟฟิต ชั้นลอย", status: "open"},
            {id: "117", title: "อบรม การใข้ ระบบ Dojob", status: "open"}
        ];
        
        console.log('🔍 Test data:', testData);
        console.log('🔍 populateProjectDropdown function:', typeof populateProjectDropdown);
        
        try {
            populateProjectDropdown(testData);
            console.log(' Test populate completed');
        } catch (error) {
            console.error('❌ Test populate failed:', error);
        }
    };
    
});
</script>

<!-- Fix three-dot dropdown buttons -->
<script>
$(document).ready(function() {
    console.log('🔧 Fixing three-dot dropdown buttons...');
    
    // Wait a moment for page to load
    setTimeout(function() {
        
        // Make column menus always visible for testing
        $('.column-menu').css({
            'opacity': '1',
            'pointer-events': 'auto',
            'z-index': '1000'
        });
        
        // Ensure dropdown buttons are clickable
        $('.column-menu .btn').css({
            'pointer-events': 'auto',
            'z-index': '1001',
            'position': 'relative'
        });
        
        // Add click handlers as backup if Bootstrap dropdowns don't work
        $('.column-menu .btn').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🔧 Three-dot button clicked');
            
            // Close any open dropdowns
            $('.dropdown-menu').hide();
            
            // Show this dropdown
            const $dropdown = $(this).siblings('.dropdown-menu');
            $dropdown.show();
            
            // Position dropdown
            const buttonRect = this.getBoundingClientRect();
            $dropdown.css({
                'position': 'fixed',
                'top': buttonRect.bottom + 'px',
                'left': (buttonRect.left - 100) + 'px',
                'z-index': '9999',
                'display': 'block'
            });
            
            // Close dropdown when clicking outside
            $(document).one('click', function() {
                $dropdown.hide();
            });
        });
        
        // Ensure dropdown items are clickable
        $('.dropdown-item').css({
            'pointer-events': 'auto',
            'cursor': 'pointer'
        });
        
        // Add click handlers for dropdown items
        $('.dropdown-item[onclick*="hideColumn"]').off('click').on('click', function(e) {
            e.preventDefault();
            const onclick = $(this).attr('onclick');
            console.log('🔧 Hide column clicked:', onclick);
            
            // Extract column name
            const match = onclick.match(/hideColumn\('([^']+)'\)/);
            if (match) {
                const columnName = match[1];
                hideColumnSimple(columnName);
            }
            
            // Hide dropdown
            $(this).closest('.dropdown-menu').hide();
        });
        
        $('.dropdown-item[onclick*="sortColumn"]').off('click').on('click', function(e) {
            e.preventDefault();
            const onclick = $(this).attr('onclick');
            console.log('🔧 Sort column clicked:', onclick);
            
            // Extract parameters
            const match = onclick.match(/sortColumn\('([^']+)',\s*'([^']+)'\)/);
            if (match) {
                const columnName = match[1];
                const direction = match[2];
                sortColumnSimple(columnName, direction);
            }
            
            // Hide dropdown
            $(this).closest('.dropdown-menu').hide();
        });
        
        console.log(' Three-dot buttons fixed');
        
    }, 1000);
    
    // Simple hide column function
    window.hideColumnSimple = function(columnName) {
        console.log('👁️ Hiding column:', columnName);
        
        const $column = $(`.draggable-column[data-column="${columnName}"]`);
        if ($column.length === 0) {
            alert('Column not found: ' + columnName);
            return;
        }
        
        const columnIndex = $column.index();
        
        // Hide header
        $column.hide().addClass('table-column-hidden');
        
        // Hide all cells in this column
        $(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).hide().addClass('table-column-hidden');
        
        alert(columnName + ' column hidden');
        console.log(' Column hidden successfully');
    };
    
    // Simple sort column function
    window.sortColumnSimple = function(columnName, direction) {
        console.log('🔄 Sorting column:', columnName, direction);
        
        const $table = $('#task-table');
        const $tbody = $table.find('tbody');
        const $rows = $tbody.find('tr').toArray();
        
        const $column = $(`.draggable-column[data-column="${columnName}"]`);
        const columnIndex = $column.index();
        
        if (columnIndex === -1) {
            alert('Column not found: ' + columnName);
            return;
        }
        
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
        
        $tbody.empty().append($rows);
        alert(`Table sorted by ${columnName} (${direction}ending)`);
        console.log(' Column sorted successfully');
    };
});
</script>

<!-- Override script to ensure table customization works -->
<script>
$(document).ready(function() {
    // Wait for all other scripts to load, then initialize our functionality
    setTimeout(function() {
        console.log('🔧 Initializing table customization override...');
        
        // Clear any existing Sortable instances on the table header
        const headerRow = document.querySelector('#task-table thead tr');
        if (headerRow && headerRow.sortable) {
            headerRow.sortable.destroy();
        }
        
        // Initialize fresh Sortable instance
        if (typeof Sortable !== 'undefined' && headerRow) {
            new Sortable(headerRow, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                filter: 'th:first-child, .column-resizer', // Don't drag checkbox or resizer
                onStart: function(evt) {
                    console.log('🔄 Started dragging column:', evt.oldIndex);
                },
                onEnd: function(evt) {
                    console.log(' Finished dragging column from', evt.oldIndex, 'to', evt.newIndex);
                    
                    // Use the unified reorderTableColumns function
                    if (typeof window.reorderTableColumns === 'function') {
                        window.reorderTableColumns(evt.oldIndex, evt.newIndex);
                    } else {
                        console.error('❌ reorderTableColumns function not found');
                    }
                    
                    // Note: Column saving is handled by the reorderTableColumns function
                }
            });
            
            console.log(' Table customization override initialized');
        } else {
            console.log('❌ SortableJS not available or table not found');
        }
        
        // Ensure dropdown functions are available
        if (typeof window.hideColumn !== 'function') {
            window.hideColumn = function(columnName) {
                console.log('👁️ Hiding column:', columnName);
                
                const column = document.querySelector(`.draggable-column[data-column="${columnName}"]`);
                if (column) {
                    const columnIndex = Array.from(column.parentNode.children).indexOf(column);
                    
                    // Hide header
                    column.style.display = 'none';
                    column.classList.add('table-column-hidden');
                    
                    // Hide all cells in this column
                    document.querySelectorAll(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).forEach(function(cell) {
                        cell.style.display = 'none';
                        cell.classList.add('table-column-hidden');
                    });
                    
                    alert(`${columnName} column hidden`);
                    
                    // Save to database
                    const hiddenColumns = [];
                    document.querySelectorAll('.draggable-column.table-column-hidden').forEach(function(col) {
                        const columnName = col.getAttribute('data-column');
                        if (columnName) {
                            hiddenColumns.push(columnName);
                        }
                    });
                    
                    if (typeof $ !== 'undefined' && window.baseUrl) {
                        $.ajax({
                            url: window.baseUrl + 'table_preferences/save_column_visibility',
                            type: 'POST',
                            data: {
                                table_name: 'task_list',
                                hidden_columns: hiddenColumns
                            },
                            success: function(response) {
                                console.log(' Column visibility saved to database');
                            },
                            error: function() {
                                console.log('❌ Error saving column visibility');
                            }
                        });
                    }
                }
            };
        }
        
        if (typeof window.sortColumn !== 'function') {
            window.sortColumn = function(columnName, direction) {
                console.log('🔄 Sorting column:', columnName, direction);
                
                const table = document.getElementById('task-table');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                const column = document.querySelector(`.draggable-column[data-column="${columnName}"]`);
                const columnIndex = Array.from(column.parentNode.children).indexOf(column);
                
                rows.sort(function(a, b) {
                    const aText = a.children[columnIndex].textContent.trim();
                    const bText = b.children[columnIndex].textContent.trim();
                    
                    let comparison = 0;
                    if (aText > bText) {
                        comparison = 1;
                    } else if (aText < bText) {
                        comparison = -1;
                    }
                    
                    return direction === 'desc' ? comparison * -1 : comparison;
                });
                
                // Clear and re-append sorted rows
                tbody.innerHTML = '';
                rows.forEach(function(row) {
                    tbody.appendChild(row);
                });
                
                alert(`Table sorted by ${columnName} (${direction}ending)`);
            };
        }
        
        console.log(' All table customization functions ready');
        
    }, 2000); // Wait 2 seconds for all other scripts to load
});
</script>

<!-- Kanban Board JavaScript -->
<script>
// Set global variables for Kanban board
window.baseUrl = '<?php echo base_url("index.php/"); ?>';
window.csrfTokenName = '<?php echo csrf_token(); ?>';
window.csrfHash = '<?php echo csrf_hash(); ?>';
console.log('🔧 Kanban Config:', {
    baseUrl: window.baseUrl,
    csrfTokenName: window.csrfTokenName,
    csrfHash: window.csrfHash
});
</script>
<script src="<?php echo base_url('assets/js/kanban-board.js?v=' . time()); ?>"></script>
<script>
// 🚫 DISABLE ALL OLD KANBAN LOADING FUNCTIONS
// Override any old kanban loading functions to prevent PHP backend calls
window.loadKanban = function() {
    console.log('🚫 Old loadKanban function called - redirecting to Node.js API');
    if (window.kanbanBoard) {
        window.kanbanBoard.loadTasks();
    }
};

window.loadKanbanBoard = function() {
    console.log('🚫 Old loadKanbanBoard function called - redirecting to Node.js API');
    if (window.kanbanBoard) {
        window.kanbanBoard.loadTasks();
    }
};

window.loadKanbanBoardFast = function() {
    console.log('🚫 Old loadKanbanBoardFast function called - redirecting to Node.js API');
    if (window.kanbanBoard) {
        window.kanbanBoard.loadTasks();
    }
};

// 🚫 BLOCK ALL OLD KANBAN AJAX CALLS
const originalAjax = $.ajax;
$.ajax = function(options) {
    if (options.url && (
        options.url.includes('all_tasks_kanban') || 
        options.url.includes('tasks/all_tasks_kanban') ||
        options.url.includes('kanban_data')
    )) {
        console.log('🚫 BLOCKED old kanban AJAX call:', options.url);
        console.log(' Using Node.js API instead via kanban-board.js');
        
        // Return a fake successful response to prevent errors
        return {
            done: function() { return this; },
            fail: function() { return this; },
            always: function() { return this; }
        };
    }
    return originalAjax.apply(this, arguments);
};

document.addEventListener('DOMContentLoaded', function() {
    let kanbanBoard = null;
    
    console.log("🔍 DOM Content Loaded - Starting task list initialization...");
    console.log("ℹ️ Note: Secondary navigation tabs are hidden - using main header navigation");
    console.log("📋 Board tab is default active and will auto-initialize...");
    
    // Initialize TaskModal immediately when DOM is ready
    console.log("🔍 DOM ready - checking TaskModal availability...");
    console.log("window.TaskModal:", typeof window.TaskModal);
    console.log("window.taskModal:", typeof window.taskModal);
    
    // Add board tab click listener with detailed logging
    const boardTabLink = document.querySelector('a[href="#board"]');
    if (boardTabLink) {
        console.log(" Found board tab link, adding click listener...");
        
        // Method 1: Direct click event
        boardTabLink.addEventListener('click', function(e) {
            console.log("🎯 Board tab clicked! (Direct click event)");
            console.log("🔍 Event details:", e);
            console.log("🔍 Current tab:", this.getAttribute('href'));
            
            // Trigger board initialization immediately
            setTimeout(() => {
                console.log("🚀 Auto-triggering board initialization from click event...");
                window.testBoardLoading();
            }, 100);
        });
        
        // Method 2: Bootstrap tab shown event
        boardTabLink.addEventListener('shown.bs.tab', function(e) {
            console.log("🎯 Board tab shown (Bootstrap event)!");
            console.log("🔍 Previous tab:", e.relatedTarget?.getAttribute('href'));
            console.log("🔍 Current tab:", e.target.getAttribute('href'));
            
            // Also trigger here as backup
            setTimeout(() => {
                console.log("� Auto-triggering board initialization from Bootstrap event...");
                window.testBoardLoading();
            }, 100);
        });
        
        // Method 3: Observer for tab content visibility
        const boardTabPane = document.getElementById('board');
        if (boardTabPane) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const hasShow = boardTabPane.classList.contains('show');
                        const hasActive = boardTabPane.classList.contains('active');
                        
                        if (hasShow && hasActive) {
                            console.log("🎯 Board tab became visible (Observer detected)!");
                            
                            // Check if board is already loaded
                            const container = document.getElementById("kanban-board-container");
                            const needsInitialization = !container || 
                                                      container.innerHTML.includes('Loading tasks...') ||
                                                      container.innerHTML.includes('loading-board') ||
                                                      container.innerHTML.length < 100;
                            
                            if (needsInitialization) {
                                console.log("🚀 Auto-triggering board initialization from visibility observer...");
                                setTimeout(() => {
                                    window.autoInitializeBoard();
                                }, 200);
                            } else {
                                console.log(" Board already loaded, skipping initialization");
                            }
                        }
                    }
                });
            });
            
            observer.observe(boardTabPane, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            console.log(" Board tab visibility observer set up");
        }
        
    } else {
        console.error("❌ Board tab link not found!");
        
        // Fallback: Try to find it after a delay
        setTimeout(() => {
            const delayedBoardTabLink = document.querySelector('a[href="#board"]');
            if (delayedBoardTabLink) {
                console.log(" Found board tab link on delayed search, adding listeners...");
                // Add the same event listeners here
                delayedBoardTabLink.addEventListener('click', function(e) {
                    console.log("🎯 Board tab clicked! (Delayed detection)");
                    setTimeout(() => window.autoInitializeBoard(), 200);
                });
            } else {
                console.error("❌ Board tab link still not found after delay");
            }
        }, 1000);
    }
    
    // Force check after scripts load
    setTimeout(() => {
        console.log("🔍 Delayed check - TaskModal availability...");
        console.log("window.TaskModal:", typeof window.TaskModal);
        console.log("window.taskModal:", typeof window.taskModal);
        
        if (typeof window.TaskModal === 'undefined') {
            console.error("❌ TaskModal still not available after delay!");
            console.log("Available window properties:", Object.keys(window).filter(k => k.includes('Task')));
        }
    }, 1000);
    
    // Check if board tab is already active on page load (now default)
    setTimeout(() => {
        const boardTabPane = document.getElementById('board');
        if (boardTabPane && boardTabPane.classList.contains('show') && boardTabPane.classList.contains('active')) {
            console.log("🎯 Board tab is already active on page load (default tab)!");
            console.log("🚀 Auto-triggering board initialization...");
            window.autoInitializeBoard();
        }
    }, 1500);
    
    // Additional immediate trigger since board is now default and navigation is hidden
    setTimeout(() => {
        console.log("🚀 Navigation hidden - Board is default tab - triggering initialization...");
        window.autoInitializeBoard();
    }, 1000); // Reduced delay since navigation is hidden
    
    // Additional check - if someone navigates directly to board tab via URL hash
    if (window.location.hash === '#board' || window.location.hash === '' || !window.location.hash) {
        console.log("🎯 Page loaded with #board hash or no hash (default) - will initialize board");
        setTimeout(() => {
            window.autoInitializeBoard();
        }, 1500); // Reduced delay since navigation is hidden
    }
    
    // Wait a bit for scripts to load, then initialize TaskModal
    setTimeout(() => {
        if (window.getTaskModalInstance) {
            console.log(" Getting single TaskModal instance...");
            try {
                window.taskModal = window.getTaskModalInstance();
                console.log(" TaskModal instance ready:", window.taskModal.instanceId);
                console.log(" TaskModal.openTask method:", typeof window.taskModal.openTask);
            } catch (error) {
                console.error("❌ Error getting TaskModal instance:", error);
            }
        } else if (window.TaskModal && !window.taskModal) {
            console.log(" Fallback: Creating TaskModal instance...");
            try {
                window.taskModal = new window.TaskModal();
                console.log(" TaskModal initialized successfully:", window.taskModal);
                console.log(" TaskModal.openTask method:", typeof window.taskModal.openTask);
            } catch (error) {
                console.error("❌ Error initializing TaskModal:", error);
            }
        } else if (!window.TaskModal) {
            console.error("❌ TaskModal class not available");
        } else {
            console.log(" TaskModal already initialized");
            console.log(" TaskModal.openTask method:", typeof window.taskModal.openTask);
        }
    }, 1500);
    
    // Function to open TaskModal from comment column clicks
    window.openTaskModal = function(taskId) {
        console.log('🔥 Opening TaskModal for task ID:', taskId);
        
        // Check if TaskModal is available
        if (typeof window.TaskModal !== 'undefined') {
            console.log(' TaskModal found, opening...');
            const modal = new window.TaskModal();
            modal.openTask(taskId);  // Correct method name is openTask
        } else {
            console.error('❌ TaskModal not found, falling back to alert');
            alert('TaskModal not available. Task ID: ' + taskId);
        }
    };

    // NOTE: Board tab initialization is now handled by the improved event listeners above
    // The old initialization code has been removed to prevent conflicts
    
    // Refresh board when tab becomes active
    document.querySelector('a[href="#board"]').addEventListener('shown.bs.tab', function() {
        console.log('🔄 Board tab shown - refreshing kanban data...');
        
        let refreshed = false;
        
        if (window.nodeJSEnhancedKanbanBoard) {
            console.log('🔄 Refreshing Node.js Enhanced Kanban...');
            try {
                window.nodeJSEnhancedKanbanBoard.loadStatuses();
                refreshed = true;
                console.log(' Node.js Enhanced Kanban refreshed');
            } catch (error) {
                console.error('❌ Error refreshing Node.js Enhanced Kanban:', error);
            }
        } else if (window.enhancedKanbanBoard) {
            console.log('🔄 Refreshing Enhanced Kanban...');
            try {
                window.enhancedKanbanBoard.loadStatuses();
                refreshed = true;
                console.log(' Enhanced Kanban refreshed');
            } catch (error) {
                console.error('❌ Error refreshing Enhanced Kanban:', error);
            }
        } else if (kanbanBoard) {
            console.log('🔄 Refreshing basic Kanban...');
            try {
                kanbanBoard.loadTasks();
                refreshed = true;
                console.log(' Basic Kanban refreshed');
            } catch (error) {
                console.error('❌ Error refreshing basic Kanban:', error);
            }
        }
        
        if (!refreshed) {
            console.warn('⚠️ No kanban board found to refresh - board may not be initialized');
        }
    });

    // Add cleanup listeners for non-board tabs to restore normal scrolling
    const nonBoardTabs = ['a[href="#summary"]', 'a[href="#list"]', 'a[href="#calendar"]'];
    
    nonBoardTabs.forEach(tabSelector => {
        const tabElement = document.querySelector(tabSelector);
        if (tabElement) {
            tabElement.addEventListener('shown.bs.tab', function(e) {
                console.log(`🧹 Switching away from board to ${this.getAttribute('href')} - cleaning up kanban scrollbar mode...`);
                
                // Clean up kanban scrollbar mode
                if (window.cleanupNodeJSEnhancedKanban) {
                    window.cleanupNodeJSEnhancedKanban();
                }
            });
        }
    });

    // Additional cleanup on page unload or navigation
    window.addEventListener('beforeunload', function() {
        if (window.cleanupNodeJSEnhancedKanban) {
            window.cleanupNodeJSEnhancedKanban();
        }
    });
});

// Add debug function for kanban loading issues
window.debugKanbanLoading = function() {
    console.log("=== KANBAN DEBUG INFO ===");
    console.log("Current page URL:", window.location.href);
    console.log("Project ID available:", typeof projectId !== 'undefined' ? projectId : 'undefined');
    console.log("Board container exists:", !!document.getElementById("kanban-board-container"));
    
    console.log("\n=== KANBAN INSTANCES ===");
    console.log("window.nodeJSEnhancedKanbanBoard:", !!window.nodeJSEnhancedKanbanBoard);
    console.log("window.enhancedKanbanBoard:", !!window.enhancedKanbanBoard);
    console.log("window.kanbanBoard:", !!window.kanbanBoard);
    
    console.log("\n=== INITIALIZATION FUNCTIONS ===");
    console.log("window.initializeNodeJSEnhancedKanban:", typeof window.initializeNodeJSEnhancedKanban);
    console.log("window.initializeEnhancedKanban:", typeof window.initializeEnhancedKanban);
    console.log("window.initializeSimpleLocalKanban:", typeof window.initializeSimpleLocalKanban);
    console.log("window.initKanbanBoard:", typeof window.initKanbanBoard);
    
    console.log("\n=== LOADED SCRIPTS ===");
    const scripts = Array.from(document.querySelectorAll('script[src]')).map(s => s.src);
    const kanbanScripts = scripts.filter(src => src.includes('kanban') || src.includes('nodejs'));
    console.log("Kanban-related scripts:", kanbanScripts);
    
    console.log("\n=== GLOBAL VARIABLES ===");
    console.log("window.baseUrl:", window.baseUrl);
    console.log("window.csrfHash:", window.csrfHash);
    console.log("window.csrfTokenName:", window.csrfTokenName);
    
    console.log("\n=== BOARD CONTAINER STATUS ===");
    const container = document.getElementById("kanban-board-container");
    if (container) {
        console.log("Container HTML length:", container.innerHTML.length);
        console.log("Container content preview:", container.innerHTML.substring(0, 200) + "...");
    } else {
        console.log("Board container not found!");
    }
    
    console.log("\n=== API CONNECTIVITY TEST ===");
    if (window.nodeJSEnhancedKanbanBoard) {
        console.log("Testing Node.js API connectivity...");
        console.log("API Base:", window.nodeJSEnhancedKanbanBoard.apiBase);
    }
    
    alert("Debug info logged to console. Check browser developer tools (F12) for detailed information.");
};

// Add manual board test function
window.testBoardLoading = function() {
    console.log(" Testing board loading manually...");
    
    const projectId = <?php echo $project_info->id ?? 1; ?>;
    console.log("🎯 Using project ID:", projectId);
    
    // Clear any existing board
    const container = document.getElementById("kanban-board-container");
    if (container) {
        container.innerHTML = '<div class="loading-board flex justify-center items-center min-h-[200px] text-white"><div class="loading-spinner border-3 border-white border-t-transparent rounded-full w-8 h-8 animate-spin mr-3"></div><div>Manual test initiated...</div></div>';
    }
    
    // Try Simple Local Kanban first (most reliable)
    if (window.initializeSimpleLocalKanban) {
        console.log("🚀 Testing Simple Local Kanban initialization...");
        try {
            const result = window.initializeSimpleLocalKanban(projectId);
            console.log(" Simple Local Kanban test result:", result);
            return;
        } catch (error) {
            console.error("❌ Simple Local Kanban test failed:", error);
        }
    }
    
    // Fallback to NodeJS Enhanced Kanban
    if (window.initializeNodeJSEnhancedKanban) {
        console.log("🚀 Testing NodeJS Enhanced Kanban initialization...");
        try {
            const result = window.initializeNodeJSEnhancedKanban(projectId);
            console.log(" NodeJS Enhanced Kanban test result:", result);
        } catch (error) {
            console.error("❌ NodeJS Enhanced Kanban test failed:", error);
        }
    } else {
        console.error("❌ No kanban initialization functions found");
    }
};

// Create a smarter auto-initialization function
window.autoInitializeBoard = function() {
    console.log("🤖 Auto-initializing board...");
    
    // Check if board is visible
    const boardTabPane = document.getElementById('board');
    const isVisible = boardTabPane && 
                     boardTabPane.classList.contains('show') && 
                     boardTabPane.classList.contains('active');
    
    if (!isVisible) {
        console.log("📋 Board tab not visible, skipping auto-initialization");
        return false;
    }
    
    // Check if board already has content
    const container = document.getElementById("kanban-board-container");
    const hasContent = container && 
                      !container.innerHTML.includes('Loading tasks...') &&
                      !container.innerHTML.includes('loading-board') &&
                      container.innerHTML.length > 200;
    
    if (hasContent) {
        console.log(" Board already has content, skipping initialization");
        return false;
    }
    
    console.log("🚀 Board is visible and empty, proceeding with initialization...");
    window.testBoardLoading();
    return true;
};

// Add reset function
window.resetBoard = function() {
    console.log("🔄 Resetting board...");
    
    // Clear existing instances
    window.nodeJSEnhancedKanbanBoard = null;
    window.enhancedKanbanBoard = null;
    window.kanbanBoard = null;
    
    // Clear container
    const container = document.getElementById("kanban-board-container");
    if (container) {
        container.innerHTML = '<div style="padding: 20px; text-align: center;">Board reset. Click the Board tab to reload.</div>';
    }
    
    console.log(" Board reset complete");
};

// Debug function for TaskModal
function debugTaskModal() {
    console.log("=== TASKMODAL DEBUG ===");
    console.log("window.TaskModal:", typeof window.TaskModal);
    console.log("window.taskModal:", typeof window.taskModal);
    console.log("window.ensureTaskModal:", typeof window.ensureTaskModal);
    console.log("window.openTaskModal:", typeof window.openTaskModal);
    console.log("Modal element exists:", !!document.getElementById('taskModal'));
    console.log("Bootstrap available:", typeof bootstrap);
    
    // Check taskModal instance methods
    if (window.taskModal) {
        console.log("=== TASKMODAL INSTANCE METHODS ===");
        console.log("taskModal.openTask:", typeof window.taskModal.openTask);
        console.log("taskModal.init:", typeof window.taskModal.init);
        console.log("taskModal.createModalHTML:", typeof window.taskModal.createModalHTML);
        console.log("taskModal constructor:", window.taskModal.constructor.name);
        
        // List all methods
        const methods = Object.getOwnPropertyNames(Object.getPrototypeOf(window.taskModal));
        console.log("All taskModal methods:", methods);
    }
    
    // Test ensureTaskModal
    if (typeof window.ensureTaskModal === 'function') {
        console.log("Testing ensureTaskModal...");
        const result = window.ensureTaskModal();
        console.log("ensureTaskModal result:", result);
    }
    
    // Test opening a modal
    if (window.taskModal && typeof window.taskModal.openTask === 'function') {
        console.log("Testing modal open...");
        try {
            window.taskModal.openTask(1);
        } catch (error) {
            console.error("Error opening modal:", error);
        }
    } else {
        console.error("Cannot test modal - openTask method not available");
    }
    
    alert("Debug info logged to console. Check browser developer tools (F12).");
}

// Test API Integration function
window.testAPIIntegration = async function() {
    console.log("🔗 Testing API Integration...");
    
    try {
        // Step 1: Test local API for current user
        console.log("📞 Testing local API: api/current_user");
        const localResponse = await fetch(`${baseUrl}api/current_user`, {
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const localResult = await localResponse.json();
        console.log(" Local API result:", localResult);
        
        if (!localResult.success) {
            throw new Error(`Local API failed: ${localResult.error}`);
        }
        
        const currentUser = localResult.data;
        
        // Step 2: Test Node.js API for adding comment
        console.log("📞 Testing Node.js API: task comments");
        const testTaskId = projectId ? Object.values(projectId)[0] || 2030 : 2030; // Use project tasks or fallback
        const nodeResponse = await fetch(`https://api-dojob.rubyshop.co.th/api/task/${testTaskId}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-User-ID': currentUser.id.toString()
            },
            credentials: 'include',
            body: JSON.stringify({
                description: `API Integration Test - ${new Date().toISOString()} - User: ${currentUser.full_name}`,
                user_id: currentUser.id,
                user_name: currentUser.full_name
            })
        });
        
        const nodeResult = await nodeResponse.json();
        console.log(" Node.js API result:", nodeResult);
        
        if (nodeResult.success) {
            alert(` API Integration Test Successful!\n\nLocal API: \nNode.js API: \nUser: ${currentUser.full_name} (ID: ${currentUser.id})\nComment added to task ${testTaskId}`);
        } else {
            throw new Error(`Node.js API failed: ${nodeResult.error}`);
        }
        
    } catch (error) {
        console.error("❌ API Integration Test failed:", error);
        alert(`❌ API Integration Test Failed:\n\n${error.message}`);
    }
};
        
   window.addEventListener('DOMContentLoaded', function() {
    console.log("📦 DOM fully loaded and parse dautoInitializeBoard");
    autoInitializeBoard()
    // You can place any code here that needs to run after the DOM is ready
});
  
        
 // Force fix right now
document.querySelectorAll(".sidebar-menu li a").forEach(el => {
  el.style.display = "flex";
  el.style.alignItems = "center";
  el.style.gap = "8px";
  el.style.textDecoration = "none";
  el.style.whiteSpace = "nowrap";
  
  const svg = el.querySelector("svg");
  if (svg) {
    svg.style.flexShrink = "0";
  }
});

        
        
</script>