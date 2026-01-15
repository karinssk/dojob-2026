<?php
// Include task rendering functions
include_once 'task_list_functions.php';
?>

<link rel="stylesheet" href="<?php echo base_url('assets/css/task_list.css?v=' . time()); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/jira-modal.css?v=' . time()); ?>">

<!-- Load SortableJS from CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
/* Basic table customization styles */
.draggable-column {
    position: relative;
    cursor: move;
    transition: all 0.2s ease;
}

.draggable-column:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.column-menu {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.draggable-column:hover .column-menu {
    opacity: 1;
}

.table-column-hidden {
    display: none !important;
}

.sortable-ghost {
    opacity: 0.4;
    background: rgba(0, 123, 255, 0.1);
}

.sortable-chosen {
    background: rgba(0, 123, 255, 0.15);
}
</style>

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
                </div>
            </div>

            <!-- Main Navigation Tabs -->
            <div class="card">
                <div class="card-header border-0 p-0">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#list" role="tab">
                                <i data-feather="list" class="icon-16"></i> List
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-0">
                    <div class="tab-content">
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
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <button class="btn btn-primary btn-sm me-2">
                                                    <i data-feather="plus" class="icon-16"></i> Create Task
                                                </button>
                                                <button class="btn btn-outline-secondary btn-sm me-2" onclick="testTableCustomization()">
                                                    <i data-feather="settings" class="icon-16"></i> Test
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Task Table -->
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="task-table">
                                            <thead class="table-light">
                                                <tr style="background: #FAFBFC; border-bottom: 2px solid #DFE1E6;">
                                                    <th style="width: 30px; padding: 12px 8px; border: none;">
                                                        <input type="checkbox" id="select-all-tasks">
                                                    </th>
                                                    <th class="draggable-column" data-column="type" style="width: 40px; padding: 12px 8px; border: none; color: #6B778C; font-weight: 600; font-size: 12px; text-transform: uppercase; cursor: move;">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <span>Type</span>
                                                            <div class="dropdown column-menu">
                                                                <button class="btn btn-sm p-0" data-bs-toggle="dropdown" style="border: none; background: none;">
                                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#42526E" stroke-width="2">
                                                                        <circle cx="12" cy="12" r="1"></circle>
                                                                        <circle cx="12" cy="5" r="1"></circle>
                                                                        <circle cx="12" cy="19" r="1"></circle>
                                                                    </svg>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li><a class="dropdown-item" href="#" onclick="hideColumn('type')">Hide Column</a></li>
                                                                    <li><a class="dropdown-item" href="#" onclick="sortColumn('type', 'asc')">Sort Ascending</a></li>
                                                                    <li><a class="dropdown-item" href="#" onclick="sortColumn('type', 'desc')">Sort Descending</a></li>
                                                                </ul>
                                                            </div>
                                                        </div>
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
                                                                    <li><a class="dropdown-item" href="#" onclick="hideColumn('key')">Hide Column</a></li>
                                                                    <li><a class="dropdown-item" href="#" onclick="sortColumn('key', 'asc')">Sort Ascending</a></li>
                                                                    <li><a class="dropdown-item" href="#" onclick="sortColumn('key', 'desc')">Sort Descending</a></li>
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
                                                                    <li><a class="dropdown-item" href="#" onclick="hideColumn('summary')">Hide Column</a></li>
                                                                    <li><a class="dropdown-item" href="#" onclick="sortColumn('summary', 'asc')">Sort Ascending</a></li>
                                                                    <li><a class="dropdown-item" href="#" onclick="sortColumn('summary', 'desc')">Sort Descending</a></li>
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
                                                                    <li><a class="dropdown-item" href="#" onclick="hideColumn('status')">Hide Column</a></li>
                                                                    <li><a class="dropdown-item" href="#" onclick="sortColumn('status', 'asc')">Sort Ascending</a></li>
                                                                    <li><a class="dropdown-item" href="#" onclick="sortColumn('status', 'desc')">Sort Descending</a></li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Sample data for testing -->
                                                <tr>
                                                    <td><input type="checkbox"></td>
                                                    <td>Task</td>
                                                    <td>PROJ-1</td>
                                                    <td>Sample task summary</td>
                                                    <td><span class="badge bg-primary">In Progress</span></td>
                                                </tr>
                                                <tr>
                                                    <td><input type="checkbox"></td>
                                                    <td>Bug</td>
                                                    <td>PROJ-2</td>
                                                    <td>Fix login issue</td>
                                                    <td><span class="badge bg-danger">To Do</span></td>
                                                </tr>
                                                <tr>
                                                    <td><input type="checkbox"></td>
                                                    <td>Feature</td>
                                                    <td>PROJ-3</td>
                                                    <td>Add new dashboard</td>
                                                    <td><span class="badge bg-success">Done</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configuration
var baseUrl = '<?php echo get_uri(); ?>';

$(document).ready(function() {
    console.log('🔧 Initializing clean table customization...');
    
    // Initialize drag and drop
    if (typeof Sortable !== 'undefined') {
        new Sortable(document.querySelector('#task-table thead tr'), {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            filter: 'th:first-child', // Don't drag checkbox column
            onEnd: function(evt) {
                console.log('✅ Column moved from', evt.oldIndex, 'to', evt.newIndex);
                reorderTableColumns(evt.oldIndex, evt.newIndex);
            }
        });
        console.log('✅ Drag and drop initialized');
    }
    
    // Reorder table columns
    window.reorderTableColumns = function(oldIndex, newIndex) {
        console.log(`🔄 Reordering column from ${oldIndex} to ${newIndex}`);
        
        const $table = $('#task-table');
        
        // Reorder body cells to match header
        $table.find('tbody tr').each(function() {
            const $row = $(this);
            const $cells = $row.children('td');
            const $cellToMove = $cells.eq(oldIndex);
            const $targetCell = $cells.eq(newIndex);
            
            if (oldIndex < newIndex) {
                $cellToMove.insertAfter($targetCell);
            } else {
                $cellToMove.insertBefore($targetCell);
            }
        });
        
        alert('Column reordered successfully!');
        saveColumnOrder();
    };
    
    // Hide column
    window.hideColumn = function(columnName) {
        console.log(`👁️ Hiding column: ${columnName}`);
        
        const $column = $(`.draggable-column[data-column="${columnName}"]`);
        const columnIndex = $column.index();
        
        // Hide header
        $column.addClass('table-column-hidden').hide();
        
        // Hide all cells in this column
        $(`#task-table tbody tr td:nth-child(${columnIndex + 1})`).addClass('table-column-hidden').hide();
        
        alert(`${columnName} column hidden`);
        saveColumnVisibility();
    };
    
    // Sort column
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
        
        $tbody.empty().append($rows);
        alert(`Table sorted by ${columnName} (${direction}ending)`);
    };
    
    // Save functions
    window.saveColumnOrder = function() {
        const columnOrder = [];
        $('.draggable-column').each(function() {
            columnOrder.push($(this).data('column'));
        });
        
        console.log('💾 Saving column order:', columnOrder);
        
        $.ajax({
            url: baseUrl + 'table_preferences/save_column_order',
            type: 'POST',
            data: {
                table_name: 'task_list',
                column_order: columnOrder
            },
            success: function(response) {
                console.log('✅ Column order saved');
            },
            error: function() {
                console.log('❌ Error saving column order');
            }
        });
    };
    
    window.saveColumnVisibility = function() {
        const hiddenColumns = [];
        $('.draggable-column.table-column-hidden').each(function() {
            hiddenColumns.push($(this).data('column'));
        });
        
        console.log('💾 Saving column visibility:', hiddenColumns);
        
        $.ajax({
            url: baseUrl + 'table_preferences/save_column_visibility',
            type: 'POST',
            data: {
                table_name: 'task_list',
                hidden_columns: hiddenColumns
            },
            success: function(response) {
                console.log('✅ Column visibility saved');
            },
            error: function() {
                console.log('❌ Error saving column visibility');
            }
        });
    };
    
    // Test function
    window.testTableCustomization = function() {
        console.log('🧪 Testing table customization...');
        console.log('SortableJS:', typeof Sortable !== 'undefined');
        console.log('Table found:', $('#task-table').length > 0);
        console.log('Columns found:', $('.draggable-column').length);
        alert('Check console for test results');
    };
    
    console.log('✅ Clean table customization ready!');
});
</script>