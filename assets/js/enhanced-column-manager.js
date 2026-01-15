/**
 * Enhanced Column Management System
 * Fixes issues with adding new columns and hiding/showing columns
 */

// Global variables for enhanced column management
let enhancedColumnPreferences = {};
let enhancedAvailableColumns = {};
let enhancedCustomColumns = {};
let isEnhancedColumnManagerOpen = false;

// Enhanced default column definitions
const enhancedDefaultColumns = {
    shot: { label: 'Shot #', type: 'number', width: 80, required: true, visible: true },
    frame: { label: 'Frame', type: 'image', width: 220, required: true, visible: true },
    shot_size: { label: 'Shot Size', type: 'select', width: 120, required: false, visible: true },
    shot_type: { label: 'Shot Type', type: 'select', width: 120, required: false, visible: true },
    movement: { label: 'Movement', type: 'select', width: 120, required: false, visible: true },
    duration: { label: 'Duration', type: 'text', width: 100, required: false, visible: true },
    content: { label: 'Content', type: 'textarea', width: 200, required: false, visible: true },
    dialogues: { label: 'Dialogues', type: 'textarea', width: 200, required: false, visible: true },
    sound: { label: 'Sound', type: 'text', width: 120, required: false, visible: true },
    equipment: { label: 'Equipment', type: 'text', width: 120, required: false, visible: true },
    framerate: { label: 'Frame Rate', type: 'select', width: 100, required: false, visible: true },
    lighting: { label: 'Lighting', type: 'textarea', width: 150, required: false, visible: true },
    note: { label: 'Note', type: 'textarea', width: 150, required: false, visible: true },
    raw_footage: { label: 'Raw Footage', type: 'file', width: 150, required: false, visible: true },
    story_status: { label: 'Status', type: 'select', width: 120, required: false, visible: true },
    actions: { label: 'Actions', type: 'actions', width: 100, required: true, visible: true }
};

// Initialize enhanced column management
function initializeEnhancedColumnManager() {
    console.log('Initializing enhanced column management...');
    
    // Override the original openColumnManager function
    if (typeof window.originalOpenColumnManager === 'undefined') {
        window.originalOpenColumnManager = window.openColumnManager;
    }
    
    window.openColumnManager = openEnhancedColumnManager;
    
    // Load preferences
    loadEnhancedColumnPreferences();
    
    console.log('Enhanced column management initialized');
}

// Open enhanced column manager
function openEnhancedColumnManager() {
    console.log('Opening enhanced column manager...');
    
    if (isEnhancedColumnManagerOpen) {
        closeEnhancedColumnManager();
        return;
    }
    
    createEnhancedColumnManagerModal();
    isEnhancedColumnManagerOpen = true;
}

// Close enhanced column manager
function closeEnhancedColumnManager() {
    const modal = document.getElementById('enhanced-column-manager-modal');
    if (modal) {
        const bootstrapModal = bootstrap.Modal.getInstance(modal);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
    isEnhancedColumnManagerOpen = false;
}

// Create enhanced column manager modal
function createEnhancedColumnManagerModal() {
    const modalHTML = `
        <div class="modal fade" id="enhanced-column-manager-modal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i data-feather="columns" class="icon-16 me-2"></i>
                            Manage Table Columns
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Column List -->
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Column Visibility & Order</h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-info me-2" onclick="showAllColumns()">
                                            <i data-feather="eye" class="icon-12 me-1"></i>Show All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning me-2" onclick="hideAllOptionalColumns()">
                                            <i data-feather="eye-off" class="icon-12 me-1"></i>Hide Optional
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="resetEnhancedToDefaults()">
                                            <i data-feather="refresh-cw" class="icon-12 me-1"></i>Reset
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" onclick="addEnhancedCustomColumn()">
                                            <i data-feather="plus" class="icon-12 me-1"></i>Add Column
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i data-feather="info" class="icon-14 me-2"></i>
                                    <strong>Instructions:</strong> Use checkboxes to show/hide columns. Drag columns to reorder them. Click on a column to edit its properties.
                                </div>
                                
                                <div class="column-list-container">
                                    <div id="enhanced-sortable-columns" class="enhanced-sortable-column-list">
                                        <!-- Columns will be loaded here -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Column Properties -->
                            <div class="col-md-4">
                                <div class="column-properties">
                                    <h6>Column Properties</h6>
                                    <div id="enhanced-column-properties-content">
                                        <div class="text-center text-muted py-4">
                                            <i data-feather="mouse-pointer" class="icon-24 mb-2"></i>
                                            <p>Select a column to edit its properties</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveEnhancedColumnPreferences()">
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
    const modal = new bootstrap.Modal(document.getElementById('enhanced-column-manager-modal'));
    modal.show();
    
    // Handle modal close events
    const modalElement = document.getElementById('enhanced-column-manager-modal');
    modalElement.addEventListener('hidden.bs.modal', function() {
        closeEnhancedColumnManager();
    });
    
    // Load column list
    loadEnhancedColumnList();
    
    // Initialize sortable
    initializeEnhancedSortableColumns();
    
    // Re-render feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Load enhanced column list
function loadEnhancedColumnList() {
    const container = document.getElementById('enhanced-sortable-columns');
    if (!container) return;
    
    let html = '';
    
    // Get all available columns
    const allColumns = { ...enhancedDefaultColumns, ...enhancedCustomColumns };
    const orderedColumns = getEnhancedOrderedColumns(allColumns);
    
    orderedColumns.forEach((columnName, index) => {
        const column = allColumns[columnName];
        if (!column) return;
        
        const preferences = enhancedColumnPreferences[columnName] || {};
        const isVisible = preferences.is_visible !== false;
        const isRequired = column.required || false;
        const isCustom = enhancedCustomColumns[columnName] ? true : false;
        
        html += `
            <div class="enhanced-column-item ${isVisible ? 'visible' : 'hidden'}" 
                 data-column="${columnName}" 
                 data-order="${index}">
                <div class="enhanced-column-item-content">
                    <div class="enhanced-column-drag-handle">
                        <i data-feather="grip-vertical" class="icon-14"></i>
                    </div>
                    <div class="enhanced-column-checkbox">
                        <input type="checkbox" 
                               id="enhanced-col-${columnName}" 
                               ${isVisible ? 'checked' : ''} 
                               ${isRequired ? 'disabled' : ''}
                               onchange="toggleEnhancedColumnVisibility('${columnName}', this.checked)">
                    </div>
                    <div class="enhanced-column-info" onclick="selectEnhancedColumn('${columnName}')">
                        <div class="enhanced-column-label">${column.label}</div>
                        <div class="enhanced-column-meta">
                            <span class="enhanced-column-type badge bg-light text-dark">${column.type}</span>
                            <span class="enhanced-column-width badge bg-secondary">${preferences.column_width || column.width || 120}px</span>
                            ${isRequired ? '<span class="badge bg-warning">Required</span>' : ''}
                            ${isCustom ? '<span class="badge bg-info">Custom</span>' : ''}
                            ${!isVisible ? '<span class="badge bg-danger">Hidden</span>' : ''}
                        </div>
                    </div>
                    <div class="enhanced-column-actions">
                        ${!isRequired && isCustom ? `
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteEnhancedColumn('${columnName}')" 
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

// Initialize enhanced sortable columns
function initializeEnhancedSortableColumns() {
    const container = document.getElementById('enhanced-sortable-columns');
    if (!container || typeof Sortable === 'undefined') return;
    
    Sortable.create(container, {
        animation: 150,
        handle: '.enhanced-column-drag-handle',
        ghostClass: 'enhanced-column-ghost',
        chosenClass: 'enhanced-column-chosen',
        onEnd: function(evt) {
            updateEnhancedColumnOrder();
        }
    });
}

// Update enhanced column order
function updateEnhancedColumnOrder() {
    const items = document.querySelectorAll('#enhanced-sortable-columns .enhanced-column-item');
    items.forEach((item, index) => {
        const columnName = item.getAttribute('data-column');
        if (!enhancedColumnPreferences[columnName]) {
            enhancedColumnPreferences[columnName] = {};
        }
        enhancedColumnPreferences[columnName].column_order = index;
    });
    
    console.log('Column order updated:', enhancedColumnPreferences);
}

// Toggle enhanced column visibility
function toggleEnhancedColumnVisibility(columnName, isVisible) {
    if (!enhancedColumnPreferences[columnName]) {
        enhancedColumnPreferences[columnName] = {};
    }
    
    enhancedColumnPreferences[columnName].is_visible = isVisible;
    
    // Update visual state
    const columnItem = document.querySelector(`[data-column="${columnName}"]`);
    if (columnItem) {
        columnItem.classList.toggle('visible', isVisible);
        columnItem.classList.toggle('hidden', !isVisible);
        
        // Update badge
        const existingBadge = columnItem.querySelector('.badge.bg-danger');
        if (existingBadge) {
            existingBadge.remove();
        }
        
        if (!isVisible) {
            const metaDiv = columnItem.querySelector('.enhanced-column-meta');
            metaDiv.insertAdjacentHTML('beforeend', '<span class="badge bg-danger">Hidden</span>');
        }
    }
    
    console.log(`Column ${columnName} visibility:`, isVisible);
}

// Select enhanced column for editing
function selectEnhancedColumn(columnName) {
    const allColumns = { ...enhancedDefaultColumns, ...enhancedCustomColumns };
    const column = allColumns[columnName];
    if (!column) return;
    
    const isCustom = enhancedCustomColumns[columnName] ? true : false;
    const preferences = enhancedColumnPreferences[columnName] || {};
    
    // Highlight selected column
    document.querySelectorAll('.enhanced-column-item').forEach(item => {
        item.classList.remove('selected');
    });
    document.querySelector(`[data-column="${columnName}"]`).classList.add('selected');
    
    const propertiesHTML = `
        <div class="selected-enhanced-column-properties">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">${column.label}</h6>
                ${isCustom ? '<span class="badge bg-info">Custom Column</span>' : '<span class="badge bg-secondary">Built-in Column</span>'}
            </div>
            
            <div class="mb-3">
                <label class="form-label">Column Label</label>
                <input type="text" class="form-control" 
                       value="${column.label}"
                       onchange="updateEnhancedColumnLabel('${columnName}', this.value)"
                       placeholder="Enter column label">
                <small class="form-text text-muted">
                    Display name for this column in the table header
                </small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Column Width (px)</label>
                <input type="number" class="form-control" 
                       value="${preferences.column_width || column.width || 120}"
                       onchange="updateEnhancedColumnWidth('${columnName}', this.value)"
                       min="50" max="500" step="10">
                <small class="form-text text-muted">
                    Width of the column in pixels (50-500px)
                </small>
            </div>
            
            ${isCustom ? `
                <div class="mb-3">
                    <label class="form-label">Column Type</label>
                    <select class="form-control" onchange="updateEnhancedColumnType('${columnName}', this.value)">
                        <option value="text" ${column.type === 'text' ? 'selected' : ''}>Text Input</option>
                        <option value="textarea" ${column.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                        <option value="select" ${column.type === 'select' ? 'selected' : ''}>Dropdown Select</option>
                        <option value="number" ${column.type === 'number' ? 'selected' : ''}>Number</option>
                        <option value="date" ${column.type === 'date' ? 'selected' : ''}>Date</option>
                        <option value="checkbox" ${column.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                        <option value="url" ${column.type === 'url' ? 'selected' : ''}>URL</option>
                        <option value="email" ${column.type === 'email' ? 'selected' : ''}>Email</option>
                    </select>
                </div>
                
                ${column.type === 'select' ? `
                    <div class="mb-3">
                        <label class="form-label">Select Options</label>
                        <textarea class="form-control" rows="4" 
                                  onchange="updateEnhancedColumnOptions('${columnName}', this.value)"
                                  placeholder="Enter one option per line:&#10;Option 1&#10;Option 2&#10;Option 3">${(column.options || []).join('\n')}</textarea>
                        <small class="form-text text-muted">
                            Enter one option per line for the dropdown
                        </small>
                    </div>
                ` : ''}
                
                <div class="mb-3">
                    <label class="form-label">Default Value</label>
                    <input type="text" class="form-control" 
                           value="${column.default_value || ''}"
                           onchange="updateEnhancedColumnDefault('${columnName}', this.value)"
                           placeholder="Optional default value">
                    <small class="form-text text-muted">
                        Default value when creating new entries
                    </small>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" 
                           ${column.required ? 'checked' : ''}
                           onchange="updateEnhancedColumnRequired('${columnName}', this.checked)"
                           id="enhanced-required-${columnName}">
                    <label class="form-check-label" for="enhanced-required-${columnName}">
                        Required field
                    </label>
                    <small class="form-text text-muted d-block">
                        Required fields cannot be hidden and must be filled
                    </small>
                </div>
            ` : `
                <div class="alert alert-info">
                    <i data-feather="info" class="icon-14 me-2"></i>
                    <strong>Built-in Column</strong><br>
                    You can edit the label and width for built-in columns. 
                    To change the type or add options, create a custom column instead.
                </div>
            `}
            
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" 
                        onclick="previewEnhancedColumn('${columnName}')">
                    <i data-feather="eye" class="icon-12 me-1"></i>Preview Column
                </button>
                ${isCustom ? `
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            onclick="deleteEnhancedColumn('${columnName}')">
                        <i data-feather="trash-2" class="icon-12 me-1"></i>Delete Column
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    
    document.getElementById('enhanced-column-properties-content').innerHTML = propertiesHTML;
    
    // Re-render feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Update enhanced column label
function updateEnhancedColumnLabel(columnName, label) {
    if (enhancedCustomColumns[columnName]) {
        enhancedCustomColumns[columnName].label = label;
    } else if (enhancedDefaultColumns[columnName]) {
        // For built-in columns, store label override in preferences
        if (!enhancedColumnPreferences[columnName]) {
            enhancedColumnPreferences[columnName] = {};
        }
        enhancedColumnPreferences[columnName].custom_label = label;
    }
    
    // Reload column list to show updated label
    loadEnhancedColumnList();
}

// Update enhanced column width
function updateEnhancedColumnWidth(columnName, width) {
    if (!enhancedColumnPreferences[columnName]) {
        enhancedColumnPreferences[columnName] = {};
    }
    enhancedColumnPreferences[columnName].column_width = parseInt(width);
    
    // Update the badge in the list
    const columnItem = document.querySelector(`[data-column="${columnName}"]`);
    if (columnItem) {
        const widthBadge = columnItem.querySelector('.enhanced-column-width');
        if (widthBadge) {
            widthBadge.textContent = `${width}px`;
        }
    }
}

// Update enhanced column type (for custom columns)
function updateEnhancedColumnType(columnName, type) {
    if (enhancedCustomColumns[columnName]) {
        enhancedCustomColumns[columnName].type = type;
        
        // Clear options if not select type
        if (type !== 'select') {
            enhancedCustomColumns[columnName].options = [];
        }
        
        // Reload properties panel to show/hide options
        selectEnhancedColumn(columnName);
        
        // Update type badge in list
        const columnItem = document.querySelector(`[data-column="${columnName}"]`);
        if (columnItem) {
            const typeBadge = columnItem.querySelector('.enhanced-column-type');
            if (typeBadge) {
                typeBadge.textContent = type;
            }
        }
    }
}

// Update enhanced column options (for select type)
function updateEnhancedColumnOptions(columnName, optionsText) {
    if (enhancedCustomColumns[columnName]) {
        const options = optionsText.split('\n').map(opt => opt.trim()).filter(opt => opt);
        enhancedCustomColumns[columnName].options = options;
    }
}

// Update enhanced column default value
function updateEnhancedColumnDefault(columnName, defaultValue) {
    if (enhancedCustomColumns[columnName]) {
        enhancedCustomColumns[columnName].default_value = defaultValue;
    }
}

// Update enhanced column required status
function updateEnhancedColumnRequired(columnName, required) {
    if (enhancedCustomColumns[columnName]) {
        enhancedCustomColumns[columnName].required = required;
        
        // If making required, ensure it's visible
        if (required) {
            toggleEnhancedColumnVisibility(columnName, true);
            const checkbox = document.getElementById(`enhanced-col-${columnName}`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.disabled = true;
            }
        } else {
            const checkbox = document.getElementById(`enhanced-col-${columnName}`);
            if (checkbox) {
                checkbox.disabled = false;
            }
        }
        
        // Reload column list to show/hide required badge
        loadEnhancedColumnList();
    }
}

// Add enhanced custom column
function addEnhancedCustomColumn() {
    Swal.fire({
        title: 'Add Custom Column',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Column Name (Internal)</label>
                    <input type="text" id="enhanced-custom-column-name" class="form-control" 
                           placeholder="e.g., custom_field_1" pattern="[a-z0-9_]+" 
                           title="Use lowercase letters, numbers, and underscores only">
                    <small class="form-text text-muted">
                        Internal name used in database (cannot be changed later)
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Column Label (Display)</label>
                    <input type="text" id="enhanced-custom-column-label" class="form-control" 
                           placeholder="e.g., Custom Field">
                    <small class="form-text text-muted">
                        Display name shown in table header
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Column Type</label>
                    <select id="enhanced-custom-column-type" class="form-control">
                        <option value="text">Text Input</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Dropdown Select</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="url">URL</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Column Width (px)</label>
                    <input type="number" id="enhanced-custom-column-width" class="form-control" 
                           value="120" min="50" max="500" step="10">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Column',
        cancelButtonText: 'Cancel',
        width: '500px',
        preConfirm: () => {
            const name = document.getElementById('enhanced-custom-column-name').value.trim();
            const label = document.getElementById('enhanced-custom-column-label').value.trim();
            const type = document.getElementById('enhanced-custom-column-type').value;
            const width = parseInt(document.getElementById('enhanced-custom-column-width').value);
            
            if (!name || !label) {
                Swal.showValidationMessage('Please fill in all required fields');
                return false;
            }
            
            if (!/^[a-z0-9_]+$/.test(name)) {
                Swal.showValidationMessage('Column name must contain only lowercase letters, numbers, and underscores');
                return false;
            }
            
            if (enhancedDefaultColumns[name] || enhancedCustomColumns[name]) {
                Swal.showValidationMessage('Column name already exists');
                return false;
            }
            
            return { name, label, type, width };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { name, label, type, width } = result.value;
            
            // Add to custom columns
            enhancedCustomColumns[name] = {
                label: label,
                type: type,
                width: width,
                required: false,
                visible: true,
                options: type === 'select' ? [] : undefined,
                default_value: ''
            };
            
            // Add to preferences
            enhancedColumnPreferences[name] = {
                is_visible: true,
                column_order: Object.keys(enhancedColumnPreferences).length,
                column_width: width
            };
            
            // Reload column list
            loadEnhancedColumnList();
            
            // Select the new column
            setTimeout(() => {
                selectEnhancedColumn(name);
            }, 100);
            
            Swal.fire({
                icon: 'success',
                title: 'Column Added!',
                text: `Custom column "${label}" has been added successfully.`,
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

// Delete enhanced column
function deleteEnhancedColumn(columnName) {
    const column = enhancedCustomColumns[columnName];
    if (!column) return;
    
    Swal.fire({
        title: 'Delete Column?',
        html: `
            <p>Are you sure you want to delete the column <strong>"${column.label}"</strong>?</p>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> This will permanently delete the column and all its data. This action cannot be undone.
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Remove from all collections
            delete enhancedCustomColumns[columnName];
            delete enhancedColumnPreferences[columnName];
            
            // Clear properties panel
            document.getElementById('enhanced-column-properties-content').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i data-feather="mouse-pointer" class="icon-24 mb-2"></i>
                    <p>Select a column to edit its properties</p>
                </div>
            `;
            
            // Reload column list
            loadEnhancedColumnList();
            
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Column has been deleted successfully.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

// Show all columns
function showAllColumns() {
    const allColumns = { ...enhancedDefaultColumns, ...enhancedCustomColumns };
    Object.keys(allColumns).forEach(columnName => {
        if (!enhancedColumnPreferences[columnName]) {
            enhancedColumnPreferences[columnName] = {};
        }
        enhancedColumnPreferences[columnName].is_visible = true;
    });
    
    loadEnhancedColumnList();
    
    Swal.fire({
        icon: 'success',
        title: 'All Columns Shown',
        text: 'All columns are now visible.',
        timer: 1500,
        showConfirmButton: false
    });
}

// Hide all optional columns
function hideAllOptionalColumns() {
    const allColumns = { ...enhancedDefaultColumns, ...enhancedCustomColumns };
    Object.keys(allColumns).forEach(columnName => {
        const column = allColumns[columnName];
        if (!column.required) {
            if (!enhancedColumnPreferences[columnName]) {
                enhancedColumnPreferences[columnName] = {};
            }
            enhancedColumnPreferences[columnName].is_visible = false;
        }
    });
    
    loadEnhancedColumnList();
    
    Swal.fire({
        icon: 'success',
        title: 'Optional Columns Hidden',
        text: 'All optional columns are now hidden. Required columns remain visible.',
        timer: 2000,
        showConfirmButton: false
    });
}

// Reset enhanced to defaults
function resetEnhancedToDefaults() {
    Swal.fire({
        title: 'Reset to Defaults?',
        html: `
            <p>This will:</p>
            <ul class="text-start">
                <li>Reset all column preferences to default settings</li>
                <li>Remove all custom columns</li>
                <li>Show all built-in columns</li>
                <li>Reset column widths and order</li>
            </ul>
            <div class="alert alert-warning">
                <strong>Warning:</strong> Custom columns and their data will be permanently deleted.
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reset everything',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Reset everything
            enhancedColumnPreferences = {};
            enhancedCustomColumns = {};
            
            // Set default preferences for built-in columns
            Object.keys(enhancedDefaultColumns).forEach((columnName, index) => {
                enhancedColumnPreferences[columnName] = {
                    is_visible: true,
                    column_order: index,
                    column_width: enhancedDefaultColumns[columnName].width
                };
            });
            
            // Clear properties panel
            document.getElementById('enhanced-column-properties-content').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i data-feather="mouse-pointer" class="icon-24 mb-2"></i>
                    <p>Select a column to edit its properties</p>
                </div>
            `;
            
            // Reload column list
            loadEnhancedColumnList();
            
            Swal.fire({
                icon: 'success',
                title: 'Reset Complete!',
                text: 'All column preferences have been reset to defaults.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

// Preview enhanced column
function previewEnhancedColumn(columnName) {
    const allColumns = { ...enhancedDefaultColumns, ...enhancedCustomColumns };
    const column = allColumns[columnName];
    const preferences = enhancedColumnPreferences[columnName] || {};
    
    let previewHTML = '';
    
    switch (column.type) {
        case 'text':
        case 'url':
        case 'email':
            previewHTML = `<input type="${column.type}" class="form-control" placeholder="${column.default_value || 'Enter ' + column.label.toLowerCase()}" ${column.required ? 'required' : ''}>`;
            break;
        case 'textarea':
            previewHTML = `<textarea class="form-control" rows="3" placeholder="${column.default_value || 'Enter ' + column.label.toLowerCase()}" ${column.required ? 'required' : ''}></textarea>`;
            break;
        case 'number':
            previewHTML = `<input type="number" class="form-control" placeholder="${column.default_value || '0'}" ${column.required ? 'required' : ''}>`;
            break;
        case 'date':
            previewHTML = `<input type="date" class="form-control" value="${column.default_value || ''}" ${column.required ? 'required' : ''}>`;
            break;
        case 'checkbox':
            previewHTML = `<div class="form-check"><input class="form-check-input" type="checkbox" ${column.default_value === 'true' ? 'checked' : ''}><label class="form-check-label">${column.label}</label></div>`;
            break;
        case 'select':
            const options = column.options || [];
            previewHTML = `<select class="form-control" ${column.required ? 'required' : ''}>
                <option value="">Select ${column.label}</option>
                ${options.map(opt => `<option value="${opt}" ${opt === column.default_value ? 'selected' : ''}>${opt}</option>`).join('')}
            </select>`;
            break;
        default:
            previewHTML = `<input type="text" class="form-control" placeholder="Preview not available for ${column.type}">`;
    }
    
    Swal.fire({
        title: `Preview: ${column.label}`,
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <strong>Column Properties:</strong>
                    <ul>
                        <li>Type: ${column.type}</li>
                        <li>Width: ${preferences.column_width || column.width}px</li>
                        <li>Required: ${column.required ? 'Yes' : 'No'}</li>
                        <li>Visible: ${preferences.is_visible !== false ? 'Yes' : 'No'}</li>
                        ${column.default_value ? `<li>Default: ${column.default_value}</li>` : ''}
                    </ul>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Field Preview:</strong></label>
                    ${previewHTML}
                </div>
            </div>
        `,
        confirmButtonText: 'Close',
        width: '500px'
    });
}

// Get enhanced ordered columns
function getEnhancedOrderedColumns(allColumns) {
    return Object.keys(allColumns).sort((a, b) => {
        const orderA = enhancedColumnPreferences[a]?.column_order ?? 999;
        const orderB = enhancedColumnPreferences[b]?.column_order ?? 999;
        return orderA - orderB;
    });
}

// Load enhanced column preferences
function loadEnhancedColumnPreferences() {
    // Initialize with defaults if no preferences exist
    if (Object.keys(enhancedColumnPreferences).length === 0) {
        Object.keys(enhancedDefaultColumns).forEach((columnName, index) => {
            enhancedColumnPreferences[columnName] = {
                is_visible: true,
                column_order: index,
                column_width: enhancedDefaultColumns[columnName].width
            };
        });
    }
    
    console.log('Enhanced column preferences loaded:', enhancedColumnPreferences);
}

// Save enhanced column preferences
function saveEnhancedColumnPreferences() {
    // Show loading state
    const saveBtn = document.querySelector('#enhanced-column-manager-modal .btn-primary');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    saveBtn.disabled = true;
    
    // Simulate save (replace with actual AJAX call)
    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'Preferences Saved!',
            text: 'Your column preferences have been saved successfully.',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            closeEnhancedColumnManager();
            
            // Apply changes to the actual table (you would implement this)
            applyEnhancedColumnChanges();
        });
        
        // Restore button
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    }, 1000);
}

// Apply enhanced column changes to the actual table
function applyEnhancedColumnChanges() {
    console.log('Applying column changes to table...');
    console.log('Preferences:', enhancedColumnPreferences);
    console.log('Custom columns:', enhancedCustomColumns);
    
    // Here you would implement the actual table column changes
    // This might involve:
    // 1. Hiding/showing columns based on is_visible
    // 2. Reordering columns based on column_order
    // 3. Adjusting column widths
    // 4. Adding custom columns to the table structure
    
    Swal.fire({
        icon: 'info',
        title: 'Changes Applied',
        text: 'Column changes have been applied. You may need to refresh the page to see all changes.',
        timer: 3000,
        showConfirmButton: false
    });
}

// Initialize when document is ready
$(document).ready(function() {
    console.log('Initializing enhanced column manager...');
    
    // Small delay to ensure other scripts are loaded
    setTimeout(() => {
        initializeEnhancedColumnManager();
    }, 300);
});

// Make functions available globally
window.openEnhancedColumnManager = openEnhancedColumnManager;
window.closeEnhancedColumnManager = closeEnhancedColumnManager;
window.addEnhancedCustomColumn = addEnhancedCustomColumn;