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
                        Create New Project
                    </li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i data-feather="film" class="icon-16 me-2"></i>
                            Create New Storyboard Project
                        </h4>
                        <a href="<?php echo get_uri('storyboard'); ?>" class="btn btn-outline-secondary">
                            <i data-feather="arrow-left" class="icon-16 me-2"></i>
                            Back to Projects
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="text-center mb-4">
                                <i data-feather="folder-plus" class="icon-48 text-primary mb-3"></i>
                                <h5>No Storyboard Projects Found</h5>
                                <p class="text-muted">Create your first storyboard project to get started with visual storytelling.</p>
                            </div>
                            
                            <?php echo form_open(get_uri("storyboard/save_storyboard_project"), array("id" => "storyboard-project-form", "class" => "general-form", "role" => "form")); ?>
                            
                            <div class="form-group mb-3">
                                <label for="title" class="form-label">Project Title *</label>
                                <input type="text" name="title" id="title" class="form-control" placeholder="Enter storyboard project title" required>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Enter project description (optional)"></textarea>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg me-3">
                                    <i data-feather="plus-circle" class="icon-16 me-2"></i>
                                    Create Storyboard Project
                                </button>
                                <a href="<?php echo get_uri('storyboard'); ?>" class="btn btn-outline-secondary btn-lg">
                                    <i data-feather="x" class="icon-16 me-2"></i>
                                    Cancel
                                </a>
                            </div>
                            
                            <?php echo form_close(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
$(document).ready(function() {
    // Enhanced form submission with validation and loading state
    $("#storyboard-project-form").on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            title: $('#title').val().trim(),
            description: $('#description').val().trim()
        };
        
        // Basic validation
        if (!formData.title) {
            Swal.fire({
                icon: 'warning',
                title: 'Title Required',
                text: 'Please enter a project title before creating the project.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ffc107'
            });
            $('#title').focus();
            return;
        }
        
        // Show loading state
        const submitBtn = $('button[type="submit"]');
        const originalBtnHtml = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creating Project...');
        
        console.log('Submitting form with data:', formData);
        
        $.ajax({
            url: '<?php echo get_uri("storyboard/save_storyboard_project"); ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Project Created Successfully!',
                        html: `
                            <div class="mt-3">
                                <p><strong>${formData.title}</strong> has been created and is ready for storyboarding.</p>
                                <div class="d-flex justify-content-center align-items-center mt-3">
                                    <i class="fas fa-film text-primary me-2"></i>
                                    <span>Redirecting to your new project...</span>
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'Continue',
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true,
                        allowOutsideClick: false
                    }).then(() => {
                        if (response.redirect_to) {
                            window.location.href = response.redirect_to;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Creation Failed',
                        text: response.message,
                        confirmButtonText: 'Try Again',
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                console.log('Response:', xhr.responseText);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    html: `
                        <div class="text-left">
                            <p><strong>Unable to create project due to a network error.</strong></p>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <strong>Error Details:</strong><br>
                                    ${error}<br>
                                    <em>Check console for more details</em>
                                </small>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Try Again',
                    confirmButtonColor: '#dc3545',
                    width: '500px'
                });
            },
            complete: function() {
                // Restore button state
                submitBtn.prop('disabled', false).html(originalBtnHtml);
            }
        });
    });
    
    // Add some nice form enhancements
    $('#title').on('input', function() {
        const title = $(this).val().trim();
        if (title.length > 0) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });
    
    // Auto-resize description textarea
    $('#description').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
</script>

<style>
.icon-48 {
    width: 48px;
    height: 48px;
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
}

/* Form validation styles */
.form-control.is-valid {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

/* Loading button animation */
.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Auto-resize textarea */
#description {
    resize: none;
    min-height: 100px;
    transition: height 0.2s ease;
}

/* Enhanced form styling */
.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Success animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.5s ease-out;
}
</style>