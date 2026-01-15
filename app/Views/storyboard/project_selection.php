<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <i data-feather="home" class="icon-14 me-1"></i>Storyboard Projects
                    </li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i data-feather="film" class="icon-16 me-2"></i>
                            Select Storyboard Project
                        </h4>
                        <a href="<?php echo get_uri('storyboard?create_new=1'); ?>" class="btn btn-primary">
                            <i data-feather="plus-circle" class="icon-16 me-2"></i>
                            Create New Project
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($projects as $project): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card project-card h-100" onclick="selectProjectCard(<?php echo $project->id; ?>)" style="cursor: pointer;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <i data-feather="film" class="icon-16 text-primary me-2"></i>
                                            <h5 class="card-title mb-0"><?php echo $project->title; ?></h5>
                                        </div>
                                        <div class="dropdown" onclick="event.stopPropagation();">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle-no-caret p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i data-feather="more-vertical" class="icon-14"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="editProject(<?php echo $project->id; ?>, '<?php echo addslashes($project->title); ?>', '<?php echo addslashes($project->description ?: ''); ?>'); return false;">
                                                        <i data-feather="edit" class="icon-14 me-2"></i>Edit Project
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" onclick="deleteProject(<?php echo $project->id; ?>, '<?php echo addslashes($project->title); ?>'); return false;">
                                                        <i data-feather="trash-2" class="icon-14 me-2"></i>Delete Project
                                                    </a>
                                                </li>
                                                <!-- Debug option (uncomment if needed)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-info" href="#" onclick="debugProject(<?php echo $project->id; ?>); return false;">
                                                        <i data-feather="info" class="icon-14 me-2"></i>Debug Info
                                                    </a>
                                                </li>
                                                -->

                                            </ul>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted"><?php echo character_limiter($project->description ?: 'No description', 100); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge badge-<?php echo $project->status == 'open' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($project->status); ?>
                                        </span>
                                        <small class="text-muted">ID: <?php echo $project->id; ?></small>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <small class="text-muted">
                                        <i data-feather="calendar" class="icon-14"></i>
                                        Created: <?php echo $project->created_date; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (empty($projects)): ?>
                    <div class="text-center py-5">
                        <i data-feather="folder-x" class="icon-48 text-muted mb-3"></i>
                        <h5 class="text-muted">No Storyboard Projects Found</h5>
                        <p class="text-muted">Create your first storyboard project to get started.</p>
                        <a href="<?php echo get_uri('storyboard?create_new=1'); ?>" class="btn btn-primary mt-3">
                            <i data-feather="plus-circle" class="icon-16 me-2"></i>
                            Create New Storyboard Project
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProjectModalLabel">
                    <i data-feather="edit" class="icon-16 me-2"></i>
                    Edit Storyboard Project
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProjectForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_project_id" name="id">
                    <div class="mb-3">
                        <label for="edit_project_title" class="form-label">Project Title *</label>
                        <input type="text" class="form-control" id="edit_project_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_project_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_project_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save" class="icon-16 me-1"></i>
                        Update Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteProjectModalLabel">
                    <i data-feather="alert-triangle" class="icon-16 me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i data-feather="trash-2" class="icon-48 text-danger mb-3"></i>
                    <h6>Are you sure you want to delete this project?</h6>
                </div>
                <div class="alert alert-warning">
                    <strong>Project:</strong> <span id="delete-project-title"></span>
                </div>
                <div class="alert alert-danger">
                    <i data-feather="alert-circle" class="icon-16 me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone and will delete all storyboard data associated with this project.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x" class="icon-16 me-2"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirm-delete-project-btn">
                    <i data-feather="trash-2" class="icon-16 me-2"></i>
                    Delete Project
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
function selectProject(projectId) {
    console.log('Selecting project:', projectId);
    
    $.ajax({
        url: '<?php echo get_uri("storyboard/set_project"); ?>',
        type: 'POST',
        data: {project_id: projectId},
        dataType: 'json',
        success: function(response) {
            console.log('Response:', response);
            if (response.success) {
                console.log('Redirecting to:', response.redirect_to);
                window.location.href = response.redirect_to;
            } else {
                console.error('Error:', response.message);
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            alert('AJAX Error: ' + error + '\nResponse: ' + xhr.responseText);
        }
    });
}

function editProject(projectId, title, description) {
    $('#edit_project_id').val(projectId);
    $('#edit_project_title').val(title);
    $('#edit_project_description').val(description);
    $('#editProjectModal').modal('show');
}

let currentDeleteProjectId = null;

function deleteProject(projectId, title) {
    currentDeleteProjectId = projectId;
    $('#delete-project-title').text(title);
    $('#deleteProjectModal').modal('show');
}

function performProjectDelete() {
    if (!currentDeleteProjectId) {
        console.error('No delete operation in progress');
        return;
    }
    
    const modalBtn = $('#confirm-delete-project-btn');
    const originalHtml = modalBtn.html();
    modalBtn.prop('disabled', true).html('<i data-feather="loader" class="icon-16 me-2"></i> Deleting...');
    
    $.ajax({
        url: '<?php echo get_uri("storyboard/delete_project"); ?>',
        type: 'POST',
        data: {id: currentDeleteProjectId},
        dataType: 'json',
        success: function(response) {
            console.log('Delete response:', response);
            
            // Hide the modal first
            $('#deleteProjectModal').modal('hide');
            
            if (response.success) {
                let details = '';
                if (response.deleted_counts) {
                    details = `
                        <div class="mt-3">
                            <strong>Deletion Summary:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="fas fa-film me-2"></i>${response.deleted_counts.storyboards} storyboards</li>
                                <li><i class="fas fa-bookmark me-2"></i>${response.deleted_counts.scene_headings} scene headings</li>
                                <li><i class="fas fa-folder me-2"></i>${response.deleted_counts.sub_projects} sub-projects</li>
                            </ul>
                        </div>
                    `;
                }
                
                setTimeout(function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Project Deleted Successfully!',
                        html: 'The project and all its associated data have been removed.' + details,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        location.reload();
                    });
                }, 300);
            } else {
                setTimeout(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete Failed',
                        text: response.message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    }).then(() => {
                        location.reload();
                    });
                }, 300);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#deleteProjectModal').modal('hide');
            
            setTimeout(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Error deleting project: ' + error,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            }, 300);
        },
        complete: function() {
            // Reset modal button
            modalBtn.prop('disabled', false).html(originalHtml);
            currentDeleteProjectId = null;
        }
    });
}

function selectProjectCard(projectId) {
    window.location.href = '<?php echo get_uri("storyboard?project_id="); ?>' + projectId;
}

function debugProject(projectId) {
    $.ajax({
        url: '<?php echo get_uri("storyboard/debug_delete_project"); ?>',
        type: 'POST',
        data: {id: projectId},
        dataType: 'json',
        success: function(response) {
            console.log('Debug response:', response);
            
            let debugHtml = `
                <div class="text-left">
                    <strong>Project Info:</strong>
                    <ul class="list-unstyled mt-2">
                        <li><strong>Exists:</strong> ${response.project_exists ? 'Yes' : 'No'}</li>
                        <li><strong>Can View:</strong> ${response.can_view_project ? 'Yes' : 'No'}</li>
                        <li><strong>Storyboards:</strong> ${response.storyboards_count || 0}</li>
                        <li><strong>Scene Headings:</strong> ${response.scene_headings_count || 0}</li>
                        <li><strong>Sub-Projects:</strong> ${response.sub_projects_count || 0}</li>
                    </ul>
                </div>
            `;
            
            Swal.fire({
                icon: 'info',
                title: 'Project Debug Info',
                html: debugHtml,
                confirmButtonText: 'OK',
                confirmButtonColor: '#007bff',
                width: '500px'
            });
        },
        error: function(xhr, status, error) {
            console.error('Debug Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Debug Error',
                text: 'Error getting debug info: ' + error,
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        }
    });
}

$(document).ready(function() {
    $('.project-card').hover(
        function() {
            $(this).addClass('shadow-lg').css('transform', 'translateY(-2px)');
        },
        function() {
            $(this).removeClass('shadow-lg').css('transform', 'translateY(0)');
        }
    );
    
    // Handle edit project form submission
    $('#editProjectForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?php echo get_uri("storyboard/update_project"); ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editProjectModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Project Updated!',
                        text: 'The project has been updated successfully.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: response.message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Error updating project: ' + error,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    });
    
    // Handle confirm delete button click
    $('#confirm-delete-project-btn').on('click', function() {
        performProjectDelete();
    });
    
    // Reset modal state when closed
    $('#deleteProjectModal').on('hidden.bs.modal', function() {
        currentDeleteProjectId = null;
        $('#confirm-delete-project-btn').prop('disabled', false).html('<i data-feather="trash-2" class="icon-16 me-2"></i> Delete Project');
    });
});
</script>

<style>
.project-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

.project-card:hover {
    border-color: #007bff;
}

.btn-xs {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
    line-height: 1;
    border-radius: 0.2rem;
    min-width: 28px;
    height: 28px;
}

.btn-xs i {
    width: 12px;
    height: 12px;
}

.dropdown-toggle-no-caret::after {
    display: none;
}

.project-card {
    transition: all 0.2s ease;
}

.project-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.dropdown-menu {
    min-width: 140px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

/* Delete confirmation modal styling */
#deleteProjectModal .modal-header {
    border-bottom: none;
}

#deleteProjectModal .icon-48 {
    width: 48px;
    height: 48px;
}

#deleteProjectModal .alert {
    margin-bottom: 1rem;
}

#deleteProjectModal .alert:last-child {
    margin-bottom: 0;
}

#deleteProjectModal .btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}

#deleteProjectModal .modal-content {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

#deleteProjectModal .modal-header.bg-danger {
    background-color: #dc3545 !important;
}

#deleteProjectModal .btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}
</style>