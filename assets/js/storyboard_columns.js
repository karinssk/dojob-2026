/**
 * Storyboard Column Management System
 * Handles show/hide, reorder, and custom columns functionality
 */

// Global variables for column management
let columnPreferences = {};
let availableColumns = {};
let customColumns = {};
let isColumnManagerOpen = false;

// Default column definitions
const defaultColumns = {
    shot: { label: 'Shot #', type: 'number', width: 80, required: true },
    frame: { label: 'Frame', type: 'image', width: 220, required: true },
    shot_size: { label: 'Shot Size', type: 'select', width: 120, required: false },
    shot_type: { label: 'Shot Type', type: 'select', width: 120, required: false },
    movement: { label: 'Movement', type: 'select', width: 120, required: false },
    duration: { label: 'Duration', type: 'text', width: 100, required: false },
    content: { label: 'Content', type: 'textarea', width: 200, required: false },
    dialogues: { label: 'Dialogues', type: 'textarea', width: 200, required: false },
    sound: { label: 'Sound', type: 'text', width: 120, required: false },
    equipment: { label: 'Equipment', type: 'text', width: 120, required: false },
    framerate: { label: 'Frame Rate', type: 'select', width: 100, required: false },
    lighting: { label: 'Lighting', type: 'textarea', width: 150, required: false },
    note: { label: 'Note', type: 'textarea', width: 150, required: false },
    raw_footage: { label: 'Raw Footage', type: 'file', width: 150, required: false },
    story_status: { label: 'Status', type: 'select', width: 120, required: false },
    actions: { label: 'Actions', type: 'actions', width: 100, required: true }
};

// Initialize column management
function initializeColumnManagement() {
    console.log('Initializing column management...');
    
    // Load user preferences
    loadColumnPreferences();
    
    // Setup column toggle button
    const toggleBtn = document.getElementById('toggle-columns');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', openColumnManager);
    }
    
    // Initialize table row reordering
    initializeTableRowReordering();
    
    // Apply initial column visibility
    applyColumnVisibility();
    
    console.log('Column management initialized');
}

// Initialize table row reordering
function initializeTableRowReordering() {
    const tableBody = document.getElementById('storyboard-table-body');
    if (!tableBody || typeof Sortable === 'undefined') {
        console.log('Table body not found or Sortable not available');
        return;
    }
    
    Sortable.create(tableBody, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onStart: function(evt) {
            // Add visual feedback when dragging starts
            evt.item.style.opacity = '0.8';
        },
        onEnd: function(evt) {
            // Reset visual feedback
            evt.item.style.opacity = '';
            
            // Update shot numbers and save new order
            updateRowOrder();
        }
    });
    
    console.log('Table row reordering initialized');
}

// Update row order after drag and drop
function updateRowOrder() {
    const rows = document.querySelectorAll('#storyboard-table-body .storyboard-row');
    const newOrder = [];
    
    rows.forEach((row, index) => {
        const storyboardId = row.getAttribute('data-id');
        const newShotNumber = index + 1;
        
        // Update shot number in the UI
        const shotCell = row.querySelector('.shot-number');
        if (shotCell) {
            shotCell.textContent = newShotNumber;
        }
        
        newOrder.push({
            id: storyboardId,
            shot: newShotNumber,
            order: index
        });
    });
    
    // Save the new order to the server
    saveRowOrder(newOrder);
}

// Save row order to server
function saveRowOrder(newOrder) {
    const projectId = getProjectId();
    
    $.ajax({
        url: get_uri("storyboard/save_row_order"),
        type: 'POST',
        data: {
            project_id: projectId,
            order_data: JSON.stringify(newOrder)
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success notification
                Swal.fire({
                    icon: 'success',
                    title: 'Order Updated!',
                    text: 'Scene order has been saved successfully.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                Swal.fire('Error!', response.message || 'Failed to save order', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error saving row order:', error);
            Swal.fire('Error!', 'Network error while saving order', 'error');
        }
    });
}

// Open column manager modal
function openColumnManager() {
    console.log('Opening column manager...');
    
    if (isColumnManagerOpen) {
        closeColumnManager();
        return;
    }
    
    createColumnManagerModal();
    isColumnManagerOpen = true;
}

// Close column manager
function closeColumnManager() {
    const modal = document.getElementById('column-manager-modal');
    if (modal) {
        modal.remove();
    }
    isColumnManagerOpen = false;
}

// Create column manager modal
function createColumnManagerModal() {
    const modalHTML = `
        <div class="modal fade" id="column-manager-modal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i data-feather="columns" class="icon-16 me-2"></i>
                            Manage Table Columns
                        </h5>
                        <button type="button" class="btn-close" onclick="closeColumnManager()"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Column List -->
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Column Visibility & Order</h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="resetToDefaults()">
                                            <i data-feather="refresh-cw" class="icon-12 me-1"></i>Reset to Defaults
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" onclick="addCustomColumn()">
                                            <i data-feather="plus" class="icon-12 me-1"></i>Add Custom Column
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="column-list-container">
                                    <div id="sortable-columns" class="sortable-column-list">
                                        <!-- Columns will be loaded here -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Column Properties -->
                            <div class="col-md-4">
                                <div class="column-properties">
                                    <h6>Column Properties</h6>
                                    <div id="column-properties-content">
                                        <p class="text-muted">Select a column to edit its properties</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeColumnManager()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveColumnPreferences()">
                            <i data-feather="save" class="icon-16 me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('column-manager-modal'));
    modal.show();
    
    // Load column list
    loadColumnList();
    
    // Initialize sortable
    initializeSortableColumns();
    
    // Re-render feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Load column list into the manager
function loadColumnList() {
    const container = document.getElementById('sortable-columns');
    if (!container) return;
    
    let html = '';
    
    // Get columns in order
    const orderedColumns = getOrderedColumns();
    
    orderedColumns.forEach((columnName, index) => {
        const column = availableColumns[columnName] || defaultColumns[columnName];
        if (!column) return;
        
        const isVisible = columnPreferences[columnName]?.is_visible !== false;
        const isRequired = column.required || false;
        const isCustom = customColumns[columnName] ? true : false;
        
        html += `
            <div class="column-item" data-column="${columnName}" data-order="${index}">
                <div class="column-item-content">
                    <div class="column-drag-handle">
                        <i data-feather="grip-vertical" class="icon-14"></i>
                    </div>
                    <div class="column-checkbox">
                        <input type="checkbox" 
                               id="col-${columnName}" 
                               ${isVisible ? 'checked' : ''} 
                               ${isRequired ? 'disabled' : ''}
                               onchange="toggleColumnVisibility('${columnName}', this.checked)">
                    </div>
                    <div class="column-info" onclick="selectColumn('${columnName}')">
                        <div class="column-label">${column.label}</div>
                        <div class="column-meta">
                            <span class="column-type">${column.type}</span>
                            <span class="column-width">${column.width}px</span>
                            ${isRequired ? '<span class="badge bg-warning">Required</span>' : ''}
                            ${isCustom ? '<span class="badge bg-info">Custom</span>' : ''}
                        </div>
                    </div>
                    <div class="column-actions">
                        ${!isRequired ? `
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteColumn('${columnName}')" 
                                    title="Delete Column">
                                <i data-feather="trash-2" class="icon-12"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Re-render feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Initialize sortable columns
function initializeSortableColumns() {
    const container = document.getElementById('sortable-columns');
    if (!container || typeof Sortable === 'undefined') return;
    
    Sortable.create(container, {
        animation: 150,
        handle: '.column-drag-handle',
        ghostClass: 'column-ghost',
        chosenClass: 'column-chosen',
        onEnd: function(evt) {
            updateColumnOrder();
        }
    });
}

// Update column order after drag
function updateColumnOrder() {
    const items = document.querySelectorAll('#sortable-columns .column-item');
    items.forEach((item, index) => {
        const columnName = item.getAttribute('data-column');
        if (columnPreferences[columnName]) {
            columnPreferences[columnName].column_order = index;
        } else {
            columnPreferences[columnName] = {
                is_visible: true,
                column_order: index,
                column_width: defaultColumns[columnName]?.width || 120
            };
        }
    });
}

// Toggle column visibility
function toggleColumnVisibility(columnName, isVisible) {
    if (!columnPreferences[columnName]) {
        columnPreferences[columnName] = {
            is_visible: isVisible,
            column_order: Object.keys(columnPreferences).length,
            column_width: defaultColumns[columnName]?.width || 120
        };
    } else {
        columnPreferences[columnName].is_visible = isVisible;
    }
    
    console.log(`Column ${columnName} visibility:`, isVisible);
}

// Select column for editing
function selectColumn(columnName) {
    const column = availableColumns[columnName] || defaultColumns[columnName];
    if (!column) return;
    
    const isCustom = customColumns[columnName] ? true : false;
    const preferences = columnPreferences[columnName] || {};
    
    const propertiesHTML = `
        <div class="selected-column-properties">
            <h6>${column.label}</h6>
            
            <div class="mb-3">
                <label class="form-label">Column Label</label>
                <input type="text" class="form-control" 
                       value="${column.label}"
                       onchange="updateColumnLabel('${columnName}', this.value)"
                       ${!isCustom ? 'title="You can edit labels for all columns"' : ''}>
                <small class="form-text text-muted">
                    ${isCustom ? 'Custom column label' : 'Display label for this column'}
                </small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Column Width (px)</label>
                <input type="number" class="form-control" 
                       value="${preferences.column_width || column.width || 120}"
                       onchange="updateColumnWidth('${columnName}', this.value)"
                       min="50" max="500">
            </div>
            
            ${isCustom ? `
                <div class="mb-3">
                    <label class="form-label">Column Type</label>
                    <select class="form-control" onchange="updateColumnType('${columnName}', this.value)">
                        <option value="text" ${column.type === 'text' ? 'selected' : ''}>Text</option>
                        <option value="textarea" ${column.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                        <option value="select" ${column.type === 'select' ? 'selected' : ''}>Select</option>
                        <option value="number" ${column.type === 'number' ? 'selected' : ''}>Number</option>
                        <option value="date" ${column.type === 'date' ? 'selected' : ''}>Date</option>
                        <option value="checkbox" ${column.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                    </select>
                </div>
                
                ${column.type === 'select' ? `
                    <div class="mb-3">
                        <label class="form-label">Select Options (one per line)</label>
                        <textarea class="form-control" rows="4" 
                                  onchange="updateColumnOptions('${columnName}', this.value)"
                                  placeholder="Option 1&#10;Option 2&#10;Option 3">${(column.options || []).join('\n')}</textarea>
                    </div>
                ` : ''}
                
                <div class="mb-3">
                    <label class="form-label">Default Value</label>
                    <input type="text" class="form-control" 
                           value="${column.default_value || ''}"
                           onchange="updateColumnDefault('${columnName}', this.value)"
                           placeholder="Optional default value">
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" 
                           ${column.required ? 'checked' : ''}
                           onchange="updateColumnRequired('${columnName}', this.checked)"
                           id="required-${columnName}">
                    <label class="form-check-label" for="required-${columnName}">
                        Required field
                    </label>
                </div>
            ` : `
                <div class="alert alert-info">
                    <small>
                        <i data-feather="info" class="icon-12 me-1"></i>
                        Built-in column. You can edit the label and width.
                    </small>
                </div>
            `}
        </div>
    `;
    
    document.getElementById('column-properties-content').innerHTML = propertiesHTML;
    
    // Re-render feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Update column width
function updateColumnWidth(columnName, width) {
    if (!columnPreferences[columnName]) {
        columnPreferences[columnName] = {
            is_visible: true,
            column_order: Object.keys(columnPreferences).length,
            column_width: parseInt(width)
        };
    } else {
        columnPreferences[columnName].column_width = parseInt(width);
    }
}

// Update column label
function updateColumnLabel(columnName, label) {
    if (availableColumns[columnName]) {
        availableColumns[columnName].label = label;
    }
    
    if (customColumns[columnName]) {
        customColumns[columnName].label = label;
    }
    
    // Reload column list to show updated label
    loadColumnList();
}

// Update column type (for custom columns)
function updateColumnType(columnName, type) {
    if (customColumns[columnName]) {
        customColumns[columnName].type = type;
        availableColumns[columnName].type = type;
        
        // Clear options if not select type
        if (type !== 'select') {
            customColumns[columnName].options = [];
            availableColumns[columnName].options = [];
        }
        
        // Reload properties panel to show/hide options
        selectColumn(columnName);
    }
}

// Update column options (for select type)
function updateColumnOptions(columnName, optionsText) {
    if (customColumns[columnName]) {
        const options = optionsText.split('\n').map(opt => opt.trim()).filter(opt => opt);
        customColumns[columnName].options = options;
        availableColumns[columnName].options = options;
    }
}

// Update column default value
function updateColumnDefault(columnName, defaultValue) {
    if (customColumns[columnName]) {
        customColumns[columnName].default_value = defaultValue;
        availableColumns[columnName].default_value = defaultValue;
    }
}

// Update column required status
function updateColumnRequired(columnName, required) {
    if (customColumns[columnName]) {
        customColumns[columnName].required = required;
        availableColumns[columnName].required = required;
        
        // Reload column list to show/hide required badge
        loadColumnList();
    }
}

// Add custom column
function addCustomColumn() {
    Swal.fire({
        title: 'Add Custom Column',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Column Name</label>
                    <input type="text" id="custom-column-name" class="form-control" 
                           placeholder="e.g., custom_field_1" pattern="[a-z_]+" 
                           title="Use lowercase letters and underscores only">
                </div>
                <div class="mb-3">
                    <label class="form-label">Column Label</label>
                    <input type="text" id="custom-column-label" class="form-control" 
                           placeholder="e.g., Custom Field">
                </div>
                <div class="mb-3">
                    <label class="form-label">Column Type</label>
                    <select id="custom-column-type" class="form-control">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="checkbox">Checkbox</option>
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Column',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const name = document.getElementById('custom-column-name').value.trim();
            const label = document.getElementById('custom-column-label').value.trim();
            const type = document.getElementById('custom-column-type').value;
            
            if (!name || !label) {
                Swal.showValidationMessage('Please fill in all fields');
                return false;
            }
            
            if (!/^[a-z_]+$/.test(name)) {
                Swal.showValidationMessage('Column name must contain only lowercase letters and underscores');
                return false;
            }
            
            if (availableColumns[name] || defaultColumns[name]) {
                Swal.showValidationMessage('Column name already exists');
                return false;
            }
            
            return { name, label, type };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { name, label, type } = result.value;
            
            // Add to custom columns
            customColumns[name] = {
                label: label,
                type: type,
                width: 120,
                required: false,
                options: []
            };
            
            // Add to available columns
            availableColumns[name] = customColumns[name];
            
            // Add to preferences
            columnPreferences[name] = {
                is_visible: true,
                column_order: Object.keys(columnPreferences).length,
                column_width: 120
            };
            
            // Reload column list
            loadColumnList();
            
            Swal.fire('Success!', 'Custom column added successfully', 'success');
        }
    });
}

// Delete column
function deleteColumn(columnName) {
    const column = availableColumns[columnName] || defaultColumns[columnName];
    
    Swal.fire({
        title: 'Delete Column?',
        text: `Are you sure you want to delete "${column.label}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            // Remove from all collections
            delete customColumns[columnName];
            delete availableColumns[columnName];
            delete columnPreferences[columnName];
            
            // Reload column list
            loadColumnList();
            
            Swal.fire('Deleted!', 'Column has been deleted.', 'success');
        }
    });
}

// Reset to defaults
function resetToDefaults() {
    Swal.fire({
        title: 'Reset to Defaults?',
        text: 'This will reset all column preferences to default settings and remove custom columns.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reset'
    }).then((result) => {
        if (result.isConfirmed) {
            // Reset preferences
            columnPreferences = {};
            customColumns = {};
            availableColumns = { ...defaultColumns };
            
            // Set default preferences
            Object.keys(defaultColumns).forEach((columnName, index) => {
                columnPreferences[columnName] = {
                    is_visible: true,
                    column_order: index,
                    column_width: defaultColumns[columnName].width
                };
            });
            
            // Reload column list
            loadColumnList();
            
            Swal.fire('Reset!', 'Column preferences have been reset to defaults.', 'success');
        }
    });
}

// Get ordered columns
function getOrderedColumns() {
    return Object.keys(columnPreferences)
        .sort((a, b) => {
            const orderA = columnPreferences[a]?.column_order || 0;
            const orderB = columnPreferences[b]?.column_order || 0;
            return orderA - orderB;
        });
}

// Apply column visibility to the table
function applyColumnVisibility() {
    const table = document.getElementById('storyboard-table');
    if (!table) return;
    
    // Get all columns
    const headers = table.querySelectorAll('thead th');
    const rows = table.querySelectorAll('tbody tr');
    
    headers.forEach((header, index) => {
        const columnName = getColumnNameByIndex(index);
        const isVisible = columnPreferences[columnName]?.is_visible !== false;
        
        // Show/hide header
        header.style.display = isVisible ? '' : 'none';
        
        // Show/hide cells in all rows
        rows.forEach(row => {
            const cell = row.children[index];
            if (cell) {
                cell.style.display = isVisible ? '' : 'none';
            }
        });
    });
}

// Get column name by index
function getColumnNameByIndex(index) {
    const columnNames = ['shot', 'frame', 'shot_size', 'shot_type', 'movement', 'duration', 
                        'content', 'dialogues', 'sound', 'equipment', 'framerate', 'lighting', 
                        'note', 'raw_footage', 'story_status', 'actions'];
    return columnNames[index] || `column_${index}`;
}

// Load column preferences from server
function loadColumnPreferences() {
    const projectId = getProjectId();
    
    $.ajax({
        url: get_uri("storyboard/get_column_preferences"),
        type: 'GET',
        data: { project_id: projectId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                columnPreferences = response.preferences || {};
                customColumns = response.custom_columns || {};
                availableColumns = { ...defaultColumns, ...customColumns };
                
                console.log('Column preferences loaded:', columnPreferences);
                applyColumnVisibility();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading column preferences:', error);
            // Use defaults
            availableColumns = { ...defaultColumns };
        }
    });
}

// Save column preferences to server
function saveColumnPreferences() {
    const projectId = getProjectId();
    
    $.ajax({
        url: get_uri("storyboard/save_column_preferences"),
        type: 'POST',
        data: {
            project_id: projectId,
            preferences: JSON.stringify(columnPreferences),
            custom_columns: JSON.stringify(customColumns)
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Column preferences saved successfully.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                
                // Apply changes to table
                applyColumnVisibility();
                
                // Close modal
                closeColumnManager();
                
                // Optionally reload page to show changes
                // location.reload();
                
            } else {
                Swal.fire('Error!', response.message || 'Failed to save preferences', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error saving column preferences:', error);
            Swal.fire('Error!', 'Network error while saving preferences', 'error');
        }
    });
}

// Get project ID (reuse from reorder script)
function getProjectId() {
    let projectId = null;
    
    const pageContent = document.querySelector('[data-project-id]');
    if (pageContent) {
        projectId = pageContent.getAttribute('data-project-id');
    }
    
    if (!projectId) {
        const hiddenInput = document.getElementById('edit-project-id');
        if (hiddenInput) {
            projectId = hiddenInput.value;
        }
    }
    
    if (!projectId) {
        const urlParams = new URLSearchParams(window.location.search);
        projectId = urlParams.get('project_id');
    }
    
    return projectId;
}

// Initialize when DOM is ready
$(document).ready(function() {
    console.log('Initializing storyboard column management...');
    initializeColumnManagement();
});

// Make functions available globally
window.openColumnManager = openColumnManager;
window.closeColumnManager = closeColumnManager;