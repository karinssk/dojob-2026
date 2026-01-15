/**
 * Storyboard Reorder Functionality
 * Handles drag-and-drop reordering of storyboard scenes
 */

// Global variables for reorder functionality
let sortableInstances = [];
let isReorderMode = false;

// Initialize reorder functionality
function initializeReorderMode() {
    console.log('Initializing reorder mode...');
    
    // Check if SortableJS is loaded
    if (typeof Sortable === 'undefined') {
        console.error('SortableJS not loaded. Please include sortablejs library.');
        Swal.fire({
            icon: 'error',
            title: 'Library Missing',
            text: 'SortableJS library is required for reordering functionality.',
            confirmButtonText: 'OK'
        });
        return false;
    }
    
    // Find the reorder button
    const reorderBtn = document.getElementById('reorder-mode');
    if (!reorderBtn) {
        console.error('Reorder button not found');
        return false;
    }
    
    // Note: Click event listener will be added by mobile.js to handle mobile-specific behavior
    console.log('Reorder mode initialized successfully');
    return true;
}

// Toggle reorder mode on/off
function toggleReorderMode() {
    console.log('Toggle reorder mode called, current state:', isReorderMode);
    
    if (isReorderMode) {
        disableReorderMode();
    } else {
        enableReorderMode();
    }
}

// Enable reorder mode
function enableReorderMode() {
    console.log('Enabling reorder mode...');
    
    const tableBodies = document.querySelectorAll('.storyboard-table-body');
    const reorderBtn = document.getElementById('reorder-mode');
    
    // if (tableBodies.length === 0) {
    //     console.error('No storyboard table bodies found');
    //     Swal.fire({
    //         icon: 'error',
    //         title: 'Tables Not Found',
    //         text: 'Cannot find storyboard tables for reordering.',
    //         confirmButtonText: 'OK'
    //     });
    //     return null;
    // }
       if (tableBodies.length === 0) {
        console.error('No storyboard maybe new storyboard is created');
     
        return null;
    }
    
    try {
        // Clear existing instances
        destroyAllSortableInstances();
        
        // Create sortable instance for each table body
        tableBodies.forEach((tableBody, index) => {
            const headingId = tableBody.getAttribute('data-heading-id');
            console.log(`Creating sortable for table ${index + 1}, heading: ${headingId}`);
            
            const sortableInstance = Sortable.create(tableBody, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                handle: 'tr', // Allow dragging by any part of the row
                onStart: function(evt) {
                    console.log(`Drag started in heading ${headingId}:`, evt.oldIndex);
                    // Add visual feedback
                    document.body.classList.add('reorder-active');
                },
                onEnd: function(evt) {
                    console.log(`Drag ended in heading ${headingId}:`, evt.oldIndex, '->', evt.newIndex);
                    document.body.classList.remove('reorder-active');
                    
                    // Only save if position actually changed
                    if (evt.oldIndex !== evt.newIndex) {
                        saveNewOrder(headingId, tableBody);
                    }
                },
                onMove: function(evt) {
                    // Add visual feedback during move
                    return true;
                }
            });
            
            // Store the instance with heading ID for reference
            sortableInstances.push({
                instance: sortableInstance,
                headingId: headingId,
                tableBody: tableBody
            });
        });
        
        // Update UI
        isReorderMode = true;
        reorderBtn.classList.remove('btn-outline-secondary');
        reorderBtn.classList.add('btn-primary');
        reorderBtn.title = 'Exit Reorder Mode';
        
        // Add visual indicators
        document.querySelectorAll('.storyboard-row').forEach(row => {
            row.classList.add('sortable-enabled');
        });
        
        // Show instructions
        showReorderInstructions();
        
        console.log(`Reorder mode enabled successfully for ${sortableInstances.length} tables`);
        
    } catch (error) {
        console.error('Error enabling reorder mode:', error);
        Swal.fire({
            icon: 'error',
            title: 'Reorder Error',
            text: 'Failed to enable reorder mode: ' + error.message,
            confirmButtonText: 'OK'
        });
    }
}

// Disable reorder mode
function disableReorderMode() {
    console.log('Disabling reorder mode...');
    
    const reorderBtn = document.getElementById('reorder-mode');
    
    try {
        // Destroy all sortable instances
        destroyAllSortableInstances();
        
        // Update UI
        isReorderMode = false;
        reorderBtn.classList.remove('btn-primary');
        reorderBtn.classList.add('btn-outline-secondary');
        reorderBtn.title = 'Enable Reorder Mode';
        
        // Remove visual indicators
        document.querySelectorAll('.storyboard-row').forEach(row => {
            row.classList.remove('sortable-enabled');
        });
        
        // Hide instructions
        hideReorderInstructions();
        
        console.log('Reorder mode disabled successfully');
        
    } catch (error) {
        console.error('Error disabling reorder mode:', error);
    }
}

// Helper function to destroy all sortable instances
function destroyAllSortableInstances() {
    console.log(`Destroying ${sortableInstances.length} sortable instances`);
    
    sortableInstances.forEach((item, index) => {
        try {
            if (item.instance) {
                item.instance.destroy();
                console.log(`Destroyed sortable instance ${index + 1} for heading: ${item.headingId}`);
            }
        } catch (error) {
            console.error(`Error destroying sortable instance ${index + 1}:`, error);
        }
    });
    
    // Clear the array
    sortableInstances = [];
}

// Save the new order to the server
function saveNewOrder(headingId, tableBody) {
    console.log(`Saving new order for heading: ${headingId}`);
    
    if (!tableBody) {
        console.error('Table body not found for saving order');
        return;
    }
    
    // Collect the new order
    const shotOrders = [];
    const rows = tableBody.querySelectorAll('tr[data-id]');
    
    rows.forEach(row => {
        const id = row.getAttribute('data-id');
        if (id) {
            shotOrders.push(parseInt(id));
        }
    });
    
    console.log(`New order for heading ${headingId}:`, shotOrders);
    
    if (shotOrders.length === 0) {
        console.warn('No items to reorder');
        return;
    }
    
    // Get project ID and sub project ID
    const projectId = getProjectId();
    const subProjectId = getSubProjectId();
    
    if (!projectId) {
        console.error('Project ID not found');
        Swal.fire({
            icon: 'error',
            title: 'Missing Project ID',
            text: 'Cannot save order: Project ID not found.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Show loading state
    showReorderLoading(true);
    
    // Send AJAX request
    $.ajax({
        url: get_uri("storyboard/reorder"),
        type: 'POST',
        data: {
            project_id: projectId,
            sub_project_id: subProjectId,
            heading_id: headingId === 'unorganized' ? null : headingId,
            shot_orders: shotOrders
        },
        dataType: 'json',
        success: function(response) {
            console.log(`Reorder response for heading ${headingId}:`, response);
            showReorderLoading(false);
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Reordered Successfully!',
                    text: response.message || 'Scenes have been reordered successfully.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                
                // Update shot numbers in the specific table
                updateShotNumbers(tableBody);
                
            } else {
                console.error('Reorder failed:', response.message);
                Swal.fire({
                    icon: 'error',
                    title: 'Reorder Failed',
                    text: response.message || 'Failed to save new order.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
                
                // Optionally reload to restore original order
                // location.reload();
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', { xhr, status, error });
            showReorderLoading(false);
            
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Error saving new order: ' + error,
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        }
    });
}

// Get project ID from various sources
function getProjectId() {
    // Try multiple sources for project ID
    let projectId = null;
    
    // Method 1: From data attribute
    const pageContent = document.querySelector('[data-project-id]');
    if (pageContent) {
        projectId = pageContent.getAttribute('data-project-id');
    }
    
    // Method 2: From hidden input
    if (!projectId) {
        const hiddenInput = document.getElementById('edit-project-id');
        if (hiddenInput) {
            projectId = hiddenInput.value;
        }
    }
    
    // Method 3: From URL parameters
    if (!projectId) {
        const urlParams = new URLSearchParams(window.location.search);
        projectId = urlParams.get('project_id');
    }
    
    // Method 4: From global variable (if set)
    if (!projectId && typeof window.currentProjectId !== 'undefined') {
        projectId = window.currentProjectId;
    }
    
    console.log('Found project ID:', projectId);
    return projectId;
}

// Get sub project ID from various sources
function getSubProjectId() {
    let subProjectId = null;
    
    // Method 1: From data attribute
    const pageContent = document.querySelector('[data-sub-project-id]');
    if (pageContent) {
        subProjectId = pageContent.getAttribute('data-sub-project-id');
    }
    
    // Method 2: From URL parameters
    if (!subProjectId) {
        const urlParams = new URLSearchParams(window.location.search);
        subProjectId = urlParams.get('sub_project_id');
    }
    
    // Method 3: From global variable (if set)
    if (!subProjectId && typeof window.currentSubProjectId !== 'undefined') {
        subProjectId = window.currentSubProjectId;
    }
    
    console.log('Found sub project ID:', subProjectId);
    return subProjectId;
}

// Update shot numbers in the UI after reordering
function updateShotNumbers(tableBody = null) {
    if (tableBody) {
        // Update specific table
        const rows = tableBody.querySelectorAll('tr[data-id]');
        rows.forEach((row, index) => {
            const shotCell = row.querySelector('td:first-child');
            if (shotCell) {
                // Preserve the blue background styling
                shotCell.innerHTML = `
                    <div class="flex items-center justify-center">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <span class="text-sm font-bold text-white">${index + 1}</span>
                        </div>
                    </div>
                `;
            }
        });
        console.log(`Updated shot numbers for ${rows.length} rows in specific table`);
    } else {
        // Update all tables
        const tableBodies = document.querySelectorAll('.storyboard-table-body');
        tableBodies.forEach((tbody, tableIndex) => {
            const rows = tbody.querySelectorAll('tr[data-id]');
            rows.forEach((row, index) => {
                const shotCell = row.querySelector('td:first-child');
                if (shotCell) {
                    // Preserve the blue background styling
                    shotCell.innerHTML = `
                        <div class="flex items-center justify-center">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-bold text-white">${index + 1}</span>
                            </div>
                        </div>
                    `;
                }
            });
            console.log(`Updated shot numbers for table ${tableIndex + 1}: ${rows.length} rows`);
        });
    }
}

// Show reorder instructions
function showReorderInstructions() {
    // Remove existing instructions
    hideReorderInstructions();
    
    const instructions = document.createElement('div');
    instructions.id = 'reorder-instructions';
    instructions.className = 'alert alert-info alert-dismissible fade show';
    instructions.innerHTML = `
        <div class="d-flex align-items-center">
            <i data-feather="info" class="icon-16 me-2"></i>
            <div>
                <strong>Reorder Mode Active:</strong> 
                Drag and drop rows to reorder scenes. Click the reorder button again to exit.
            </div>
            <button type="button" class="btn-close" onclick="hideReorderInstructions()"></button>
        </div>
    `;
    
    // Insert before the table
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        tableContainer.parentNode.insertBefore(instructions, tableContainer);
        
        // Re-render feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }
}

// Hide reorder instructions
function hideReorderInstructions() {
    const instructions = document.getElementById('reorder-instructions');
    if (instructions) {
        instructions.remove();
    }
}

// Show/hide loading state during reorder save
function showReorderLoading(show) {
    const reorderBtn = document.getElementById('reorder-mode');
    if (!reorderBtn) return;
    
    if (show) {
        reorderBtn.disabled = true;
        reorderBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
    } else {
        reorderBtn.disabled = false;
        reorderBtn.innerHTML = '<i data-feather="move" class="icon-16"></i>';
        
        // Re-render feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }
}

// Test reorder functionality
function testReorderFunctionality() {
    console.log('=== Reorder Functionality Test ===');
    console.log('SortableJS loaded:', typeof Sortable !== 'undefined');
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    console.log('Reorder button exists:', !!document.getElementById('reorder-mode'));
    
    const tableBodies = document.querySelectorAll('.storyboard-table-body');
    console.log('Table bodies found:', tableBodies.length);
    
    tableBodies.forEach((tbody, index) => {
        const headingId = tbody.getAttribute('data-heading-id');
        const rows = tbody.querySelectorAll('tr[data-id]');
        console.log(`Table ${index + 1} (heading: ${headingId}): ${rows.length} rows`);
    });
    
    console.log('Project ID:', getProjectId());
    console.log('Sub Project ID:', getSubProjectId());
    console.log('Current reorder state:', isReorderMode);
    console.log('Active sortable instances:', sortableInstances.length);
    
    // Test button click
    const reorderBtn = document.getElementById('reorder-mode');
    if (reorderBtn) {
        console.log('Button classes:', reorderBtn.className);
        console.log('Button onclick:', reorderBtn.onclick);
        console.log('Button event listeners:', getEventListeners ? getEventListeners(reorderBtn) : 'Cannot check listeners');
    }
    
    // Test all table rows
    const allRows = document.querySelectorAll('.storyboard-table-body tr[data-id]');
    console.log('Total draggable rows found:', allRows.length);
    
    if (allRows.length > 0) {
        console.log('First row data-id:', allRows[0].getAttribute('data-id'));
        console.log('Last row data-id:', allRows[allRows.length - 1].getAttribute('data-id'));
    }
}

// Initialize when DOM is ready
$(document).ready(function() {
    console.log('Initializing storyboard reorder functionality...');
    
    // Initialize reorder mode
    if (initializeReorderMode()) {
        console.log('Reorder functionality ready');
    } else {
        console.error('Failed to initialize reorder functionality');
    }
    
    // Make test function available globally
    window.testReorderFunctionality = testReorderFunctionality;
});

// Make functions available globally for debugging
window.enableReorderMode = enableReorderMode;
window.disableReorderMode = disableReorderMode;
window.toggleReorderMode = toggleReorderMode;
window.saveNewOrder = saveNewOrder;