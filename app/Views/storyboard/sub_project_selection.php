<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?php echo get_uri('storyboard'); ?>">
                            <i data-feather="home" class="icon-14 me-1"></i>Storyboard Projects
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo character_limiter($project_info->title, 40); ?> - Sub-Projects
                    </li>
                </ol>
            </nav>
            
            <!-- Project Header -->
            <div class="project-header bg-white border-bottom p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <a href="<?php echo get_uri('storyboard'); ?>" class="btn btn-outline-secondary me-3">
                            <i data-feather="arrow-left" class="icon-16 me-2"></i>
                            Back to Projects
                        </a>
                        <div class="project-info">
                            <h4 class="mb-1">
                                <i data-feather="film" class="icon-16 me-2"></i>
                                <?php echo $project_info->title; ?> - Sub-Projects
                            </h4>
                            <div class="project-meta">
                                <span class="badge badge-<?php echo $project_info->status == 'open' ? 'success' : 'secondary'; ?> me-2">
                                    <?php echo ucfirst($project_info->status); ?>
                                </span>
                                <small class="text-muted">Main Project ID: <?php echo $project_info->id; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sub-project-modal" onclick="loadSubProjectModal(<?php echo $project_id; ?>)">
                            <i data-feather="plus-circle" class="icon-16 me-2"></i>
                            Add Sub-Project
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i data-feather="folder" class="icon-16 me-2"></i>
                        Sub-Projects
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($sub_projects as $sub_project): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card sub-project-card h-100" onclick="openSubProjectScenes(<?php echo $project_id; ?>, <?php echo $sub_project->id; ?>)" style="cursor: pointer;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <i data-feather="folder" class="icon-16 text-primary me-2"></i>
                                            <h5 class="card-title mb-0"><?php echo $sub_project->title; ?></h5>
                                        </div>
                                        <div class="dropdown" onclick="event.stopPropagation();">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle-no-caret p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i data-feather="more-vertical" class="icon-14"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="loadSubProjectModal(<?php echo $project_id; ?>, <?php echo $sub_project->id; ?>); $('#sub-project-modal').modal('show'); return false;">
                                                        <i data-feather="edit" class="icon-14 me-2"></i>Edit Sub-Project
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" onclick="deleteSubProject(<?php echo $sub_project->id; ?>, '<?php echo addslashes($sub_project->title); ?>'); return false;">
                                                        <i data-feather="trash-2" class="icon-14 me-2"></i>Delete Sub-Project
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted"><?php echo character_limiter($sub_project->description ?: 'No description', 100); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge badge-<?php echo $sub_project->status == 'Completed' ? 'success' : ($sub_project->status == 'In Progress' ? 'primary' : 'secondary'); ?>">
                                            <?php echo $sub_project->status; ?>
                                        </span>
                                        <small class="text-muted"><?php echo $sub_project->scene_count; ?> scenes</small>
                                    </div>
                                    <?php if (isset($sub_project->assigned_to_user) && $sub_project->assigned_to_user): ?>
                                    <div class="assigned-user-info mb-2">
                                        <div class="d-flex align-items-center">
                                            <?php if (isset($sub_project->assigned_user_image)): ?>
                                                <img src="<?php echo $sub_project->assigned_user_image; ?>" 
                                                     class="assigned-user-avatar me-2" 
                                                     alt="<?php echo $sub_project->assigned_to_user; ?>"
                                                     title="<?php echo $sub_project->assigned_to_user; ?><?php echo isset($sub_project->assigned_user_email) ? ' (' . $sub_project->assigned_user_email . ')' : ''; ?>">
                                            <?php else: ?>
                                                <div class="assigned-user-avatar me-2 d-flex align-items-center justify-content-center bg-light">
                                                    <i data-feather="user" class="icon-12 text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <div class="assigned-user-name">
                                                    <small><strong><?php echo $sub_project->assigned_to_user; ?></strong></small>
                                                </div>
                                                <?php if (isset($sub_project->assigned_user_job_title) && $sub_project->assigned_user_job_title): ?>
                                                    <div class="assigned-user-title">
                                                        <small class="text-muted" style="font-size: 0.7rem;"><?php echo $sub_project->assigned_user_job_title; ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <small class="text-muted">
                                        <i data-feather="calendar" class="icon-14"></i>
                                        Created: <?php echo date('M j, Y', strtotime($sub_project->created_date)); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (empty($sub_projects)): ?>
                    <div class="text-center py-5">
                        <i data-feather="folder-x" class="icon-48 text-muted mb-3"></i>
                        <h5 class="text-muted">No Sub-Projects Found</h5>
                        <p class="text-muted">Create your first sub-project to organize your storyboard scenes.</p>
                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#sub-project-modal" onclick="loadSubProjectModal(<?php echo $project_id; ?>)">
                            <i data-feather="plus-circle" class="icon-16 me-2"></i>
                            Create Sub-Project
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sub-Project Modal -->
<div class="modal fade" id="sub-project-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div id="sub-project-modal-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="delete-confirmation-modal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">
                    <i data-feather="alert-triangle" class="icon-16 me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i data-feather="trash-2" class="icon-48 text-danger mb-3"></i>
                    <h6>Are you sure you want to delete this sub-project?</h6>
                </div>
                <div class="alert alert-warning">
                    <strong>Sub-Project:</strong> <span id="delete-sub-project-title"></span>
                </div>
                <div class="alert alert-danger">
                    <i data-feather="alert-circle" class="icon-16 me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone and will also delete all associated storyboard scenes.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x" class="icon-16 me-2"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                    <i data-feather="trash-2" class="icon-16 me-2"></i>
                    Delete Sub-Project
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
function openSubProjectScenes(projectId, subProjectId) {
    window.location.href = '<?php echo get_uri("storyboard?project_id="); ?>' + projectId + '&sub_project_id=' + subProjectId;
}

function loadSubProjectModal(projectId, subProjectId = null) {
    console.log('loadSubProjectModal called with projectId:', projectId, 'subProjectId:', subProjectId);
    
    $.ajax({
        url: '<?php echo get_uri("storyboard/create_sub_project"); ?>',
        type: 'POST',
        data: {
            project_id: projectId,
            id: subProjectId
        },
        success: function(response) {
            console.log('Sub-project modal form loaded successfully');
            $('#sub-project-modal-content').html(response);
        },
        error: function(xhr, status, error) {
            console.error('Error loading sub-project form:', error);
            Swal.fire({
                icon: 'error',
                title: 'Loading Error',
                text: 'Error loading sub-project form: ' + error,
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        }
    });
}

let currentDeleteSubProjectId = null;
let currentDeleteButton = null;

function deleteSubProject(subProjectId, subProjectTitle) {
    console.log('deleteSubProject called with ID:', subProjectId, 'Title:', subProjectTitle);
    
    // Store current delete info
    currentDeleteSubProjectId = subProjectId;
    currentDeleteButton = $('button[onclick*="deleteSubProject(' + subProjectId + ')"]');
    
    // Set the sub-project title in the modal
    $('#delete-sub-project-title').text(subProjectTitle);
    
    // Show the confirmation modal
    $('#delete-confirmation-modal').modal('show');
}

function performDelete() {
    if (!currentDeleteSubProjectId || !currentDeleteButton) {
        console.error('No delete operation in progress');
        return;
    }
    
    console.log('Performing delete for ID:', currentDeleteSubProjectId);
    
    // Show loading state on the delete button
    const originalHtml = currentDeleteButton.html();
    currentDeleteButton.prop('disabled', true).html('<i data-feather="loader" class="icon-12 me-1"></i> Deleting...');
    
    // Show loading state on modal button
    const modalBtn = $('#confirm-delete-btn');
    const modalOriginalHtml = modalBtn.html();
    modalBtn.prop('disabled', true).html('<i data-feather="loader" class="icon-16 me-2"></i> Deleting...');
    
    $.ajax({
        url: '<?php echo get_uri("storyboard/delete_sub_project"); ?>',
        type: 'POST',
        data: {
            id: currentDeleteSubProjectId
        },
        dataType: 'json',
        success: function(response) {
            console.log('Delete response:', response);
            
            if (response.success) {
                // Hide the modal first
                $('#delete-confirmation-modal').modal('hide');
                
                // Show success message with SweetAlert2
                setTimeout(function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sub-Project Deleted Successfully!',
                        text: response.message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                }, 300);
            } else {
                // Hide modal and show error
                $('#delete-confirmation-modal').modal('hide');
                
                setTimeout(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete Failed',
                        text: response.message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                }, 300);
                
                // Restore button
                currentDeleteButton.prop('disabled', false).html(originalHtml);
            }
            
            // Reset modal button
            modalBtn.prop('disabled', false).html(modalOriginalHtml);
        },
        error: function(xhr, status, error) {
            console.error('Delete error:', error);
            console.error('Response:', xhr.responseText);
            
            // Hide modal
            $('#delete-confirmation-modal').modal('hide');
            
            // Show error message with SweetAlert2
            setTimeout(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Error deleting sub-project: ' + error,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            }, 300);
            
            // Restore buttons
            currentDeleteButton.prop('disabled', false).html(originalHtml);
            modalBtn.prop('disabled', false).html(modalOriginalHtml);
        }
    });
    
    // Clear current delete info
    currentDeleteSubProjectId = null;
    currentDeleteButton = null;
}

$(document).ready(function() {
    $('.sub-project-card').hover(
        function() {
            $(this).addClass('shadow-lg').css('transform', 'translateY(-2px)');
        },
        function() {
            $(this).removeClass('shadow-lg').css('transform', 'translateY(0)');
        }
    );
    
    // Handle confirm delete button click
    $('#confirm-delete-btn').click(function() {
        performDelete();
    });
    
    // Reset modal state when closed
    $('#delete-confirmation-modal').on('hidden.bs.modal', function() {
        currentDeleteSubProjectId = null;
        currentDeleteButton = null;
        $('#confirm-delete-btn').prop('disabled', false).html('<i data-feather="trash-2" class="icon-16 me-2"></i> Delete Sub-Project');
    });
});
</script>

<style>
.sub-project-card {
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
}

.sub-project-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.dropdown-toggle-no-caret::after {
    display: none;
}

.dropdown-menu {
    min-width: 160px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.icon-48 {
    width: 48px;
    height: 48px;
}

.icon-12 {
    width: 12px;
    height: 12px;
}

/* Assigned user avatar styling */
.assigned-user-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
    transition: transform 0.2s ease;
}

.assigned-user-avatar:hover {
    transform: scale(1.1);
    border-color: #007bff;
}

/* Enhanced sub-project card styling */
.sub-project-card .card-body {
    position: relative;
}

.sub-project-card .assigned-user-info {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 8px;
    margin-bottom: 8px;
}

.sub-project-card .assigned-user-info:hover {
    background: #e9ecef;
}
.badge{
    color: #000 !important;
}
/* Button styling */
.sub-project-card .card-footer .btn {
    margin-right: 4px;
    margin-bottom: 4px;
}

.sub-project-card .card-footer .btn:last-child {
    margin-right: 0;
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

/* Responsive button layout */
@media (max-width: 768px) {
    .sub-project-card .card-footer {
        flex-direction: column;
        align-items: stretch;
    }
    
    .sub-project-card .card-footer > div {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .sub-project-card .card-footer .btn {
        margin-right: 0;
        margin-bottom: 8px;
    }
    
    .sub-project-card .card-footer .btn:last-child {
        margin-bottom: 0;
    }
}

/* Loading state for delete button */
.btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Animation for card removal */
.sub-project-card.removing {
    opacity: 0;
    transform: scale(0.95);
    transition: all 0.4s ease;
}

.col-md-4.removing {
    opacity: 0;
    transform: scale(0.95);
    transition: all 0.4s ease;
}

/* Delete confirmation modal styling */
#delete-confirmation-modal .modal-header {
    border-bottom: none;
}

#delete-confirmation-modal .icon-48 {
    width: 48px;
    height: 48px;
}

#delete-confirmation-modal .alert {
    margin-bottom: 1rem;
}

#delete-confirmation-modal .alert:last-child {
    margin-bottom: 0;
}

/* Modal button loading state */
#delete-confirmation-modal .btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Enhanced modal styling */
#delete-confirmation-modal .modal-content {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

#delete-confirmation-modal .modal-header.bg-danger {
    background-color: #dc3545 !important;
}

#delete-confirmation-modal .btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}
</style>