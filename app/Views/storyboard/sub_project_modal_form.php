<?php echo form_open(get_uri("storyboard/save_sub_project"), array("id" => "sub-project-form", "class" => "general-form", "role" => "form")); ?>

<!-- Include Select2 CSS if not already included -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
.select2-container {
    width: 100% !important;
}

.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    padding-left: 12px;
    color: #495057;
}

.select2-container--default .select2-selection--multiple {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    min-height: 38px;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
    border-radius: 0.25rem;
    padding: 2px 8px;
    margin: 2px;
}

.select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

.modal .select2-dropdown {
    z-index: 9999;
}

/* User profile image styles for Select2 */
.user-option {
    display: flex !important;
    align-items: center !important;
    padding: 8px 0 !important;
}

.user-avatar {
    width: 32px !important;
    height: 32px !important;
    border-radius: 50% !important;
    margin-right: 12px !important;
    object-fit: cover !important;
    border: 2px solid #e9ecef !important;
    display: block !important;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 500;
    color: #495057;
    font-size: 14px;
    line-height: 1.2;
}

.user-details {
    font-size: 12px;
    color: #6c757d;
    line-height: 1.2;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Selection (chosen item) styles */
.user-selection {
    display: flex;
    align-items: center;
}

.user-avatar-small {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin-right: 8px;
    object-fit: cover;
    border: 1px solid #e9ecef;
}

.user-name-small {
    font-size: 14px;
    color: #495057;
}

/* Multi-select tag styles */
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    display: flex;
    align-items: center;
    padding: 4px 8px;
    margin: 2px;
    background-color: #007bff;
    border-color: #007bff;
    color: white;
    border-radius: 4px;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice .user-avatar-small {
    width: 16px;
    height: 16px;
    margin-right: 6px;
    border: 1px solid rgba(255,255,255,0.3);
}

.select2-container--default .select2-selection--multiple .select2-selection__choice .user-name-small {
    color: white;
    font-size: 12px;
}

/* Hover effects */
.select2-results__option--highlighted .user-name {
    color: white;
}

.select2-results__option--highlighted .user-details {
    color: rgba(255,255,255,0.8);
}

/* Loading state and fallback */
.user-avatar, .user-avatar-small {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    position: relative;
}

.user-avatar::after, .user-avatar-small::after {
    content: "?";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 16px;
    color: #6c757d;
    display: none;
}

.user-avatar-small::after {
    font-size: 12px;
}

/* Show fallback icon when image fails to load */
.user-avatar[src=""], .user-avatar[src*="avatar.jpg"], 
.user-avatar-small[src=""], .user-avatar-small[src*="avatar.jpg"] {
    background: #f8f9fa;
}

.user-avatar[src=""]::after, .user-avatar[src*="avatar.jpg"]::after,
.user-avatar-small[src=""]::after, .user-avatar-small[src*="avatar.jpg"]::after {
    display: block;
}
</style>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group mb-3">
                    <label for="title" class="form-label">Sub-Project Title *</label>
                    <input type="text" name="title" id="title" class="form-control" 
                           value="<?php echo $model_info->title ?? ''; ?>" 
                           placeholder="Enter sub-project title" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3" 
                              placeholder="Enter sub-project description"><?php echo $model_info->description ?? ''; ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="assigned_to" class="form-label">Assigned To</label>
                            <select name="assigned_to" id="assigned_to" class="form-control select2">
                                <option value="">- Select Assignee -</option>
                                <?php 
                                // First show team members (project members)
                                if (!empty($team_members)): 
                                ?>
                                    <optgroup label="Project Team Members">
                                        <?php foreach ($team_members as $member): ?>
                                        <option value="<?php echo $member['id']; ?>" 
                                                data-image="<?php echo htmlspecialchars($member['image']); ?>"
                                                data-email="<?php echo htmlspecialchars($member['email']); ?>"
                                                data-job-title="<?php echo htmlspecialchars($member['job_title']); ?>"
                                                <?php echo (isset($model_info->assigned_to) && $model_info->assigned_to == $member['id']) ? 'selected' : ''; ?>>
                                            <?php echo $member['name']; ?>
                                            <!-- Debug: <?php echo $member['image']; ?> -->
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                
                                <?php 
                                // Then show all other active users
                                if (!empty($all_users)): 
                                ?>
                                    <optgroup label="All Users">
                                        <?php foreach ($all_users as $user): ?>
                                            <?php 
                                            // Skip if already in team members
                                            $is_team_member = false;
                                            foreach ($team_members as $member) {
                                                if ($member['id'] == $user['id']) {
                                                    $is_team_member = true;
                                                    break;
                                                }
                                            }
                                            if (!$is_team_member): 
                                            ?>
                                            <option value="<?php echo $user['id']; ?>" 
                                                    data-image="<?php echo $user['image']; ?>"
                                                    data-email="<?php echo $user['email']; ?>"
                                                    data-job-title="<?php echo $user['job_title']; ?>"
                                                    <?php echo (isset($model_info->assigned_to) && $model_info->assigned_to == $user['id']) ? 'selected' : ''; ?>>
                                                <?php echo $user['name']; ?>
                                            </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="Draft" <?php echo (!isset($model_info->status) || $model_info->status == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="In Progress" <?php echo (isset($model_info->status) && $model_info->status == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Review" <?php echo (isset($model_info->status) && $model_info->status == 'Review') ? 'selected' : ''; ?>>Review</option>
                                <option value="Approved" <?php echo (isset($model_info->status) && $model_info->status == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Completed" <?php echo (isset($model_info->status) && $model_info->status == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="On Hold" <?php echo (isset($model_info->status) && $model_info->status == 'On Hold') ? 'selected' : ''; ?>>On Hold</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="collaborators" class="form-label">Collaborators</label>
                    <select name="collaborators[]" id="collaborators" class="form-control select2" multiple="multiple">
                        <?php 
                        // Parse existing collaborators
                        $existing_collaborators = array();
                        if (isset($model_info->collaborators) && !empty($model_info->collaborators)) {
                            $existing_collaborators = explode(',', $model_info->collaborators);
                            $existing_collaborators = array_map('trim', $existing_collaborators);
                        }
                        
                        // First show team members (project members)
                        if (!empty($team_members)): 
                        ?>
                            <optgroup label="Project Team Members">
                                <?php foreach ($team_members as $member): ?>
                                <option value="<?php echo $member['id']; ?>" 
                                        data-image="<?php echo $member['image']; ?>"
                                        data-email="<?php echo $member['email']; ?>"
                                        data-job-title="<?php echo $member['job_title']; ?>"
                                        <?php echo in_array($member['id'], $existing_collaborators) ? 'selected' : ''; ?>>
                                    <?php echo $member['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        
                        <?php 
                        // Then show all other active users
                        if (!empty($all_users)): 
                        ?>
                            <optgroup label="All Users">
                                <?php foreach ($all_users as $user): ?>
                                    <?php 
                                    // Skip if already in team members
                                    $is_team_member = false;
                                    foreach ($team_members as $member) {
                                        if ($member['id'] == $user['id']) {
                                            $is_team_member = true;
                                            break;
                                        }
                                    }
                                    if (!$is_team_member): 
                                    ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            data-image="<?php echo $user['image']; ?>"
                                            data-email="<?php echo $user['email']; ?>"
                                            data-job-title="<?php echo $user['job_title']; ?>"
                                            <?php echo in_array($user['id'], $existing_collaborators) ? 'selected' : ''; ?>>
                                        <?php echo $user['name']; ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                    <small class="form-text text-muted">Select multiple users who will collaborate on this sub-project</small>
                </div>
                
                <!-- Debug: Test images directly -->
               
            </div>
        </div>
    </div>
</div>

<div class="modal-header">
    <h5 class="modal-title">
        <?php echo isset($model_info->id) ? 'Edit Sub-Project' : 'Create New Sub-Project'; ?>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i data-feather="x" class="icon-16"></i> Cancel
    </button>
    <button type="submit" class="btn btn-primary">
        <i data-feather="check-circle" class="icon-16"></i> 
        <?php echo isset($model_info->id) ? 'Update' : 'Create'; ?> Sub-Project
    </button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        // Custom template function for Select2 with profile images
        function formatUser(user) {
            if (!user.id) {
                return user.text;
            }
            
            var $element = $(user.element);
            var imageUrl = $element.attr('data-image') || $element.data('image') || '';
            var email = $element.attr('data-email') || $element.data('email') || '';
            var jobTitle = $element.attr('data-job-title') || $element.data('job-title') || '';
            
            // Debug logging
            console.log('formatUser - User:', user.text);
            console.log('formatUser - Element:', user.element);
            console.log('formatUser - Image URL:', imageUrl);
            console.log('formatUser - Email:', email);
            console.log('formatUser - Job Title:', jobTitle);
            
            // Use a default avatar if no image
            if (!imageUrl || imageUrl === '' || imageUrl === 'undefined') {
                imageUrl = '<?php echo base_url("assets/images/avatar.jpg"); ?>';
            }
            
            var html = '<div class="user-option">' +
                    '<img class="user-avatar" src="' + imageUrl + '" onerror="console.log(\'Image failed to load:\', this.src); this.src=\'<?php echo base_url("assets/images/avatar.jpg"); ?>\'" />' +
                    '<div class="user-info">' +
                        '<div class="user-name">' + user.text + '</div>' +
                        '<div class="user-details">' + 
                            (email || '') +
                            (jobTitle ? ' • ' + jobTitle : '') +
                        '</div>' +
                    '</div>' +
                '</div>';
            
            console.log('formatUser - Generated HTML:', html);
            return $(html);
        }
        
        function formatUserSelection(user) {
            if (!user.id) {
                return user.text;
            }
            
            var $element = $(user.element);
            var imageUrl = $element.attr('data-image') || $element.data('image') || '';
            
            console.log('formatUserSelection - User:', user.text, 'Image URL:', imageUrl);
            
            // Use a default avatar if no image
            if (!imageUrl || imageUrl === '' || imageUrl === 'undefined') {
                imageUrl = '<?php echo base_url("assets/images/avatar.jpg"); ?>';
            }
            
            var html = '<div class="user-selection">' +
                    '<img class="user-avatar-small" src="' + imageUrl + '" onerror="this.src=\'<?php echo base_url("assets/images/avatar.jpg"); ?>\'" />' +
                    '<span class="user-name-small">' + user.text + '</span>' +
                '</div>';
            
            return $(html);
        }

        // Debug: Check if data attributes are present
        console.log('Checking data attributes...');
        $("#assigned_to option").each(function() {
            var $option = $(this);
            console.log('Option:', $option.text(), 'Image:', $option.data('image'), 'Email:', $option.data('email'));
        });

        // Destroy existing Select2 if it exists
        if ($("#assigned_to").hasClass("select2-hidden-accessible")) {
            $("#assigned_to").select2('destroy');
        }
        
        // Initialize Select2 for dropdowns with custom templates
        console.log('Initializing Select2 for assigned_to...');
        setTimeout(function() {
            $("#assigned_to").select2({
                placeholder: "- Select Assignee -",
                allowClear: true,
                dropdownParent: $("#sub-project-form").closest('.modal'),
                templateResult: function(user) {
                    console.log('templateResult called for:', user);
                    return formatUser(user);
                },
                templateSelection: function(user) {
                    console.log('templateSelection called for:', user);
                    return formatUserSelection(user);
                },
                escapeMarkup: function(markup) {
                    return markup;
                }
            });
            console.log('Select2 initialized for assigned_to');
        }, 100);
        
        // Destroy existing Select2 if it exists
        if ($("#collaborators").hasClass("select2-hidden-accessible")) {
            $("#collaborators").select2('destroy');
        }
        
        console.log('Initializing Select2 for collaborators...');
        setTimeout(function() {
            $("#collaborators").select2({
                placeholder: "Select collaborators...",
                allowClear: true,
                dropdownParent: $("#sub-project-form").closest('.modal'),
                templateResult: function(user) {
                    console.log('collaborators templateResult called for:', user);
                    return formatUser(user);
                },
                templateSelection: function(user) {
                    console.log('collaborators templateSelection called for:', user);
                    return formatUserSelection(user);
                },
                escapeMarkup: function(markup) {
                    return markup;
                }
            });
            console.log('Select2 initialized for collaborators');
        }, 150);
        
        // Test button to manually test template function
        $("#test-template").click(function() {
            console.log('Testing template function...');
            var $firstOption = $("#assigned_to option:eq(1)"); // Skip the placeholder
            if ($firstOption.length > 0) {
                var testUser = {
                    id: $firstOption.val(),
                    text: $firstOption.text(),
                    element: $firstOption[0]
                };
                console.log('Test user object:', testUser);
                var result = formatUser(testUser);
                console.log('Template result:', result);
                $("#template-test-result").html(result);
            }
        });
        
        // Add event listeners to see when Select2 opens
        $("#assigned_to").on('select2:open', function() {
            console.log('assigned_to dropdown opened');
        });
        
        $("#assigned_to").on('select2:select', function(e) {
            console.log('assigned_to item selected:', e.params.data);
        });
        
        $("#collaborators").on('select2:open', function() {
            console.log('collaborators dropdown opened');
        });
        
        // Force refresh Select2 after a delay
        setTimeout(function() {
            console.log('Force refreshing Select2...');
            $("#assigned_to").trigger('change');
            $("#collaborators").trigger('change');
        }, 500);
        
        // Reinitialize button for testing
        $("#reinit-select2").click(function() {
            console.log('Manual reinitialize...');
            
            // Destroy and recreate
            $("#assigned_to").select2('destroy');
            $("#collaborators").select2('destroy');
            
            // Simple initialization without templates first
            $("#assigned_to").select2({
                placeholder: "- Select Assignee -",
                allowClear: true,
                dropdownParent: $("#sub-project-form").closest('.modal')
            });
            
            $("#collaborators").select2({
                placeholder: "Select collaborators...",
                allowClear: true,
                dropdownParent: $("#sub-project-form").closest('.modal')
            });
            
            console.log('Reinitialized without templates');
            
            // Then try with templates after a delay
            setTimeout(function() {
                $("#assigned_to").select2('destroy');
                $("#collaborators").select2('destroy');
                
                $("#assigned_to").select2({
                    placeholder: "- Select Assignee -",
                    allowClear: true,
                    dropdownParent: $("#sub-project-form").closest('.modal'),
                    templateResult: function(user) {
                        console.log('INLINE templateResult called for:', user);
                        if (!user.id) return user.text;
                        
                        var $element = $(user.element);
                        var imageUrl = $element.attr('data-image') || '';
                        var email = $element.attr('data-email') || '';
                        var jobTitle = $element.attr('data-job-title') || '';
                        
                        if (!imageUrl) imageUrl = '<?php echo base_url("assets/images/avatar.jpg"); ?>';
                        
                        return $('<div class="user-option">' +
                            '<img class="user-avatar" src="' + imageUrl + '" />' +
                            '<div class="user-info">' +
                                '<div class="user-name">' + user.text + '</div>' +
                                '<div class="user-details">' + email + (jobTitle ? ' • ' + jobTitle : '') + '</div>' +
                            '</div>' +
                        '</div>');
                    },
                    templateSelection: function(user) {
                        console.log('INLINE templateSelection called for:', user);
                        if (!user.id) return user.text;
                        
                        var $element = $(user.element);
                        var imageUrl = $element.attr('data-image') || '<?php echo base_url("assets/images/avatar.jpg"); ?>';
                        
                        return $('<div class="user-selection">' +
                            '<img class="user-avatar-small" src="' + imageUrl + '" />' +
                            '<span class="user-name-small">' + user.text + '</span>' +
                        '</div>');
                    },
                    escapeMarkup: function(markup) { return markup; }
                });
                
                $("#collaborators").select2({
                    placeholder: "Select collaborators...",
                    allowClear: true,
                    dropdownParent: $("#sub-project-form").closest('.modal'),
                    templateResult: function(user) {
                        console.log('COLLABORATORS INLINE templateResult called for:', user);
                        if (!user.id) return user.text;
                        
                        var $element = $(user.element);
                        var imageUrl = $element.attr('data-image') || '';
                        var email = $element.attr('data-email') || '';
                        var jobTitle = $element.attr('data-job-title') || '';
                        
                        if (!imageUrl) imageUrl = '<?php echo base_url("assets/images/avatar.jpg"); ?>';
                        
                        return $('<div class="user-option">' +
                            '<img class="user-avatar" src="' + imageUrl + '" />' +
                            '<div class="user-info">' +
                                '<div class="user-name">' + user.text + '</div>' +
                                '<div class="user-details">' + email + (jobTitle ? ' • ' + jobTitle : '') + '</div>' +
                            '</div>' +
                        '</div>');
                    },
                    templateSelection: function(user) {
                        console.log('COLLABORATORS INLINE templateSelection called for:', user);
                        if (!user.id) return user.text;
                        
                        var $element = $(user.element);
                        var imageUrl = $element.attr('data-image') || '<?php echo base_url("assets/images/avatar.jpg"); ?>';
                        
                        return $('<div class="user-selection">' +
                            '<img class="user-avatar-small" src="' + imageUrl + '" />' +
                            '<span class="user-name-small">' + user.text + '</span>' +
                        '</div>');
                    },
                    escapeMarkup: function(markup) { return markup; }
                });
                
                console.log('Reinitialized WITH templates');
            }, 200);
        });
        
        // Enhanced form submission with SweetAlert2
        $("#sub-project-form").on('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            var title = $('#title').val().trim();
            if (!title) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Title Required',
                    text: 'Please enter a sub-project title before saving.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ffc107'
                });
                $('#title').focus();
                return;
            }
            
            // Show loading state
            const submitBtn = $('button[type="submit"]');
            const originalBtnHtml = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
            
            // Prepare form data
            var formData = {
                id: $('input[name="id"]').val(),
                project_id: $('input[name="project_id"]').val(),
                title: title,
                description: $('#description').val().trim(),
                assigned_to: $('#assigned_to').val(),
                status: $('#status').val(),
                collaborators: ''
            };
            
            // Handle collaborators
            var collaborators = $("#collaborators").val();
            if (collaborators && Array.isArray(collaborators) && collaborators.length > 0) {
                formData.collaborators = collaborators.filter(function(val) {
                    return val && val.trim() !== '';
                }).join(',');
            }
            
            console.log('Submitting sub-project form with data:', formData);
            
            $.ajax({
                url: '<?php echo get_uri("storyboard/save_sub_project"); ?>',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(result) {
                    console.log('Sub-project save success:', result);
                    
                    if (result.success) {
                        const isEdit = formData.id && formData.id !== '';
                        const actionText = isEdit ? 'updated' : 'created';
                        
                        Swal.fire({
                            icon: 'success',
                            title: `Sub-Project ${isEdit ? 'Updated' : 'Created'} Successfully!`,
                            html: `
                                <div class="mt-3">
                                    <p><strong>${title}</strong> has been ${actionText} successfully.</p>
                                    <div class="d-flex justify-content-center align-items-center mt-3">
                                        <i class="fas fa-folder text-primary me-2"></i>
                                        <span>Refreshing page...</span>
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'Continue',
                            confirmButtonColor: '#28a745',
                            timer: 2500,
                            timerProgressBar: true,
                            allowOutsideClick: false
                        }).then(() => {
                            $('#sub-project-modal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Save Failed',
                            text: result.message || 'Failed to save sub-project',
                            confirmButtonText: 'Try Again',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Sub-project save error:', error);
                    console.log('Response:', xhr.responseText);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        html: `
                            <div class="text-left">
                                <p><strong>Unable to save sub-project due to a network error.</strong></p>
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
    });
</script>