<?php echo form_open(get_uri("storyboard/save"), array("id" => "storyboard-form", "class" => "general-form", "role" => "form", "enctype" => "multipart/form-data")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
        <input type="hidden" name="sub_project_id" value="<?php echo $sub_project_id ?? ''; ?>" />
        
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
                <div class="form-group">
                    <div class="row">
                        <label for="shot" class="col-md-3 col-form-label">Shot Number</label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "shot",
                                "name" => "shot",
                                "value" => $model_info->shot ?? $next_shot_number ?? 1,
                                "class" => "form-control",
                                "type" => "number",
                                "min" => "1",
                                "placeholder" => "Shot Number",
                                "autofocus" => true,
                                "data-rule-required" => true,
                                "data-msg-required" => "This field is required",
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="scene_heading_id" class="col-md-3 col-form-label">Scene Heading</label>
                        <div class="col-md-9">
                            <select name="scene_heading_id" id="scene_heading_id" class="form-control">
                                <option value="">- No Scene Heading -</option>
                                <?php if (isset($scene_headings) && !empty($scene_headings)): ?>
                                    <?php foreach ($scene_headings as $heading): ?>
                                        <option value="<?php echo $heading->id; ?>" 
                                                <?php echo (isset($model_info->scene_heading_id) && $model_info->scene_heading_id == $heading->id) ? 'selected' : ''; ?>>
                                            Shot <?php echo $heading->shot; ?>: <?php echo $heading->header; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="form-text text-muted">Select which scene heading this storyboard belongs to</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="frame" class="col-md-3 col-form-label"><?php echo app_lang('frame_image'); ?></label>
                        <div class="col-md-9">
                            <input type="file" name="frame_file" id="frame_file" class="form-control" accept="image/*" onchange="handleNewImageUpload(this)">
                            <small class="form-text text-muted">Upload frame image (JPG, PNG, GIF) - You can edit images after upload. Press <kbd>Ctrl+V</kbd> to paste from clipboard</small>
                            
                            <!-- Paste Area for Clipboard Images -->
                            <div id="paste-area" class="paste-area mt-2" tabindex="0">
                                <div class="paste-area-content">
                                    <i data-feather="clipboard" class="icon-24 text-muted"></i>
                                    <p class="mb-0 text-muted small">Click here and press <kbd>Ctrl+V</kbd> to paste image</p>
                                </div>
                            </div>
                            <?php if (isset($model_info->frame) && $model_info->frame): ?>
                                <?php 
                                $frame_data = unserialize($model_info->frame);
                                if ($frame_data && isset($frame_data['file_name'])): ?>
                                    <!-- Existing Image Preview with Edit/Delete Buttons -->
                                    <div id="image-preview-section" class="mt-2">
                                        <div class="d-flex align-items-start gap-3">
                                            <!-- Image Display -->
                                            <div class="image-display">
                                                <img id="preview-image" 
                                                     src="<?php echo base_url('files/storyboard_frames/' . $frame_data['file_name']); ?>" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 200px; max-height: 150px; cursor: pointer;"
                                                     onclick="showImageModal('<?php echo base_url('files/storyboard_frames/' . $frame_data['file_name']); ?>')">
                                            </div>
                                            
                                            <!-- Action Buttons -->
                                            <div class="image-actions d-flex flex-column gap-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary" 
                                                        id="edit-image-btn"
                                                        onclick="openImageEditor('<?php echo base_url('files/storyboard_frames/' . $frame_data['file_name']); ?>', handleEditedImage)"
                                                        title="Edit Image">
                                                    <i data-feather="edit-3" class="icon-14 me-1"></i>
                                                    Edit
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        id="delete-image-btn"
                                                        onclick="deleteCurrentImage()"
                                                        title="Delete Image">
                                                    <i data-feather="trash-2" class="icon-14 me-1"></i>
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Image Info -->
                                        <div class="image-info mt-2">
                                            <p class="small text-muted mb-1" id="image-filename">Current image: <?php echo $frame_data['file_name']; ?></p>
                                            <small class="text-info">
                                                <i data-feather="info" class="icon-12"></i>
                                                Click image to view full size, or use buttons to edit/delete
                                            </small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Hidden Preview Section for New Images -->
                                <div id="image-preview-section" class="mt-2" style="display: none;">
                                    <div class="d-flex align-items-start gap-3">
                                        <!-- Image Display -->
                                        <div class="image-display">
                                            <img id="preview-image" 
                                                 src="" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 200px; max-height: 150px; cursor: pointer;"
                                                 onclick="showImageModal(this.src)">
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="image-actions d-flex flex-column gap-2">
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary" 
                                                    id="edit-image-btn"
                                                    onclick="openImageEditor(document.getElementById('preview-image').src, handleEditedImage)"
                                                    title="Edit Image">
                                                <i data-feather="edit-3" class="icon-14 me-1"></i>
                                                Edit
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    id="delete-image-btn"
                                                    onclick="deleteCurrentImage()"
                                                    title="Delete Image">
                                                <i data-feather="trash-2" class="icon-14 me-1"></i>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Image Info -->
                                    <div class="image-info mt-2">
                                        <p class="small text-muted mb-1" id="image-filename">New image selected</p>
                                        <small class="text-info">
                                            <i data-feather="info" class="icon-12"></i>
                                            Click image to view full size, or use buttons to edit/delete
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="shot_size" class="col-md-3 col-form-label"><?php echo app_lang('shot_size'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_dropdown("shot_size", array(
                                "" => "- Select Shot Size -",
                                "Full Shot" => "Full Shot",
                                "Medium Shot" => "Medium Shot", 
                                "Close-up" => "Close-up",
                                "Extreme Close-up" => "Extreme Close-up",
                                "Wide Shot" => "Wide Shot",
                                "Long Shot" => "Long Shot",
                                "Medium Close-up" => "Medium Close-up",
                                "Over-the-shoulder" => "Over-the-shoulder"
                            ), $model_info->shot_size ?? '', "class='form-control select2'");
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="shot_type" class="col-md-3 col-form-label"><?php echo app_lang('shot_type'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_dropdown("shot_type", array(
                                "" => "- Select Shot Type -",
                                "Eye Level" => "Eye Level",
                                "High Angle" => "High Angle",
                                "Low Angle" => "Low Angle",
                                "Bird's Eye" => "Bird's Eye",
                                "Worm's Eye" => "Worm's Eye",
                                "Dutch Angle" => "Dutch Angle",
                                "Point of View" => "Point of View"
                            ), $model_info->shot_type ?? '', "class='form-control select2'");
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="movement" class="col-md-3 col-form-label"><?php echo app_lang('movement'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_dropdown("movement", array(
                                "" => "- Select Movement -",
                                "Static" => "Static",
                                "Pan" => "Pan",
                                "Tilt" => "Tilt",
                                "Tracking" => "Tracking",
                                "Dolly" => "Dolly",
                                "Zoom" => "Zoom",
                                "Handheld" => "Handheld",
                                "Steadicam" => "Steadicam"
                            ), $model_info->movement ?? '', "class='form-control select2'");
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="duration" class="col-md-3 col-form-label"><?php echo app_lang('duration'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "duration",
                                "name" => "duration",
                                "value" => $model_info->duration ?? '',
                                "class" => "form-control",
                                "type" => "text",
                                "placeholder" => "Duration in seconds (e.g., 5 or 5.1)",
                                "pattern" => "[0-9]+(\.[0-9]+)?",
                                "title" => "Enter a valid duration (whole numbers or decimals)"
                            ));
                            ?>
                            <small class="form-text text-muted">Enter duration as whole number (5) or decimal (5.1)</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="framerate" class="col-md-3 col-form-label"><?php echo app_lang('frame_rate'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_dropdown("framerate", array(
                                "" => "- Select Frame Rate -",
                                "24" => "24 fps",
                                "25" => "25 fps",
                                "30" => "30 fps",
                                "50" => "50 fps",
                                "60" => "60 fps",
                                "120" => "120 fps"
                            ), $model_info->framerate ?? '', "class='form-control select2'");
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="story_status" class="col-md-3 col-form-label">Status</label>
                        <div class="col-md-9">
                            <select name="story_status" id="story_status" class="form-control">
                                <option value="Draft" <?php echo (!isset($model_info->story_status) || $model_info->story_status == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="Editing" <?php echo (isset($model_info->story_status) && $model_info->story_status == 'Editing') ? 'selected' : ''; ?>>Editing</option>
                                <option value="Review" <?php echo (isset($model_info->story_status) && $model_info->story_status == 'Review') ? 'selected' : ''; ?>>Review</option>
                                <option value="Approved" <?php echo (isset($model_info->story_status) && $model_info->story_status == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Final" <?php echo (isset($model_info->story_status) && $model_info->story_status == 'Final') ? 'selected' : ''; ?>>Final</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <div class="form-group">
                    <div class="row">
                        <label for="content" class="col-md-3 col-form-label"><?php echo app_lang('content'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_textarea(array(
                                "id" => "content",
                                "name" => "content",
                                "value" => $model_info->content ?? '',
                                "class" => "form-control",
                                "placeholder" => app_lang('scene_content_description'),
                                "rows" => "3"
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="dialogues" class="col-md-3 col-form-label"><?php echo app_lang('dialogues'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_textarea(array(
                                "id" => "dialogues",
                                "name" => "dialogues",
                                "value" => $model_info->dialogues ?? '',
                                "class" => "form-control",
                                "placeholder" => app_lang('dialogue_text'),
                                "rows" => "3"
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="sound" class="col-md-3 col-form-label"><?php echo app_lang('sound'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "sound",
                                "name" => "sound",
                                "value" => $model_info->sound ?? '',
                                "class" => "form-control",
                                "placeholder" => app_lang('sound_file_or_description'),
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="equipment" class="col-md-3 col-form-label"><?php echo app_lang('equipment'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "equipment",
                                "name" => "equipment",
                                "value" => $model_info->equipment ?? '',
                                "class" => "form-control",
                                "placeholder" => app_lang('equipment_used'),
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="lighting" class="col-md-3 col-form-label"><?php echo app_lang('lighting'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_textarea(array(
                                "id" => "lighting",
                                "name" => "lighting",
                                "value" => $model_info->lighting ?? '',
                                "class" => "form-control",
                                "placeholder" => app_lang('lighting_description'),
                                "rows" => "2"
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="raw_footage_files" class="col-md-3 col-form-label"><?php echo app_lang('raw_footage'); ?></label>
                        <div class="col-md-9">
                            <!-- Multiple Video File Upload -->
                            <div class="mb-3">
                                <input type="file" 
                                       id="raw_footage_files" 
                                       name="raw_footage_files[]" 
                                       class="form-control" 
                                       multiple 
                                       accept="video/*,.mp4,.avi,.mov,.wmv,.flv,.webm,.mkv">
                                <small class="form-text text-muted">
                                    Select multiple video files (MP4, AVI, MOV, WMV, FLV, WebM, MKV). Max 100MB per file, 500MB total.
                                </small>
                                <div id="file-size-warning" class="alert alert-warning mt-2" style="display: none;">
                                    <i class="fa fa-exclamation-triangle"></i> Large files detected. Upload may take several minutes.
                                </div>
                                
                                <!-- Upload Progress Bar -->
                                <div id="upload-progress-container" class="mt-3" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Uploading files...</span>
                                        <span id="upload-percentage" class="text-muted">0%</span>
                                    </div>
                                    <div class="progress mb-2">
                                        <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div id="upload-status" class="small text-muted">
                                        <span id="upload-speed">0 KB/s</span> • 
                                        <span id="upload-eta">Calculating...</span> • 
                                        <span id="upload-size">0 MB / 0 MB</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Display existing footage files -->
                            <div id="existing-footage-files">
                                <?php 
                                if (isset($model_info->raw_footage) && $model_info->raw_footage): 
                                    $footage_data = @unserialize($model_info->raw_footage);
                                    if ($footage_data && is_array($footage_data)):
                                ?>
                                    <div class="existing-files-section">
                                        <h6 class="text-muted mb-3 d-flex align-items-center">
                                            <i data-feather="folder" class="icon-16 me-2"></i>
                                            Existing Raw Footage Files
                                        </h6>
                                        <?php foreach ($footage_data as $index => $file): ?>
                                            <div class="existing-file-item mb-3 p-3 border rounded bg-light">
                                                <div class="d-flex align-items-start">
                                                    <div class="file-icon me-3">
                                                        <i data-feather="video" class="icon-20 text-primary"></i>
                                                    </div>
                                                    <div class="file-details flex-grow-1 me-3">
                                                        <div class="file-name mb-1">
                                                            <strong class="text-truncate d-block" style="max-width: 300px;" title="<?php echo htmlspecialchars($file['original_name'] ?? $file['file_name'] ?? 'Unknown file'); ?>">
                                                                <?php echo htmlspecialchars($file['original_name'] ?? $file['file_name'] ?? 'Unknown file'); ?>
                                                            </strong>
                                                        </div>
                                                        <div class="file-meta">
                                                            <small class="text-muted">
                                                                <i data-feather="hard-drive" class="icon-12 me-1"></i>
                                                                <?php echo isset($file['file_size']) ? number_format($file['file_size'] / 1024 / 1024, 2) . ' MB' : 'Size unknown'; ?>
                                                                <?php if (isset($file['uploaded_at'])): ?>
                                                                    • <i data-feather="clock" class="icon-12 me-1"></i>
                                                                    <?php echo date('M j, Y', strtotime($file['uploaded_at'])); ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="file-actions d-flex gap-2">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-success preview-video-btn" 
                                                                data-video-url="<?php echo base_url('files/storyboard_footage/' . ($file['file_name'] ?? '')); ?>"
                                                                data-video-name="<?php echo htmlspecialchars($file['original_name'] ?? $file['file_name'] ?? 'Unknown file'); ?>"
                                                                title="Preview Video">
                                                            <i data-feather="play" class="icon-14"></i>
                                                        </button>
                                                        <a href="<?php echo base_url('files/storyboard_footage/' . ($file['file_name'] ?? '')); ?>" 
                                                           target="_blank" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           title="Download">
                                                            <i data-feather="download" class="icon-14"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger remove-footage-file" 
                                                                data-file-index="<?php echo $index; ?>"
                                                                title="Remove">
                                                            <i data-feather="trash-2" class="icon-14"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php 
                                    endif;
                                endif; 
                                ?>
                            </div>
                            
                            <!-- Preview area for new files -->
                            <div id="footage-preview-area" class="mt-3" style="display: none;">
                                <div class="new-files-section">
                                    <h6 class="text-muted mb-3 d-flex align-items-center">
                                        <i data-feather="upload" class="icon-16 me-2"></i>
                                        New Files to Upload
                                    </h6>
                                    <div id="footage-file-list"></div>
                                </div>
                            </div>
                            
                            <!-- Hidden input to store removed file indices -->
                            <input type="hidden" id="removed_footage_files" name="removed_footage_files" value="">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="note" class="col-md-3 col-form-label"><?php echo app_lang('note'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_textarea(array(
                                "id" => "note",
                                "name" => "note",
                                "value" => $model_info->note ?? '',
                                "class" => "form-control",
                                "placeholder" => app_lang('additional_notes'),
                                "rows" => "3"
                            ));
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> Close</button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Save</button>
</div>
<?php echo form_close(); ?>



<!-- Image Editor Styles -->
<style>
.image-preview-container {
    position: relative;
    display: inline-block;
}

.image-edit-overlay {
    position: absolute;
    top: 5px;
    right: 5px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.image-preview-container:hover .image-edit-overlay {
    opacity: 1;
}

.edit-image-btn {
    background: rgba(0, 123, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.edit-image-btn:hover {
    background: rgba(0, 123, 255, 1);
    transform: scale(1.1);
}

#current-frame-image {
    transition: transform 0.2s ease;
}

#current-frame-image:hover {
    transform: scale(1.05);
}
</style>

<script type="text/javascript">
    // Global variables to store edited image data and current storyboard info
    // Use window object to avoid redeclaration conflicts
    window.storyboardEditor = window.storyboardEditor || {};
    window.storyboardEditor.editedImageBlob = null;
    window.storyboardEditor.currentStoryboardId = <?php echo json_encode($model_info->id ?? 'null'); ?>;
    
    console.log('Initial storyboard ID:', window.storyboardEditor.currentStoryboardId);
    
    // Function to handle edited image from image editor
    function handleEditedImage(imageBlob) {
        console.log('Received edited image blob:', imageBlob);
        
        // Ensure namespace exists
        if (!window.storyboardEditor) {
            window.storyboardEditor = {};
        }
        
        // Store the edited image blob
        window.storyboardEditor.editedImageBlob = imageBlob;
        
        // Create a preview URL for the edited image
        const previewUrl = URL.createObjectURL(imageBlob);
        
        // Update the preview image
        const currentImage = document.getElementById('current-frame-image');
        if (currentImage) {
            currentImage.src = previewUrl;
        }
        
        // Show success message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Image Edited!',
                text: 'Your image has been edited. Save the form to apply changes.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
        
        // Mark form as having edited image
        $('#storyboard-form').data('has-edited-image', true);
    }
    
    // Function to convert edited image to proper format for PHP controller
    function appendEditedImageToForm(formData) {
        console.log('📎 appendEditedImageToForm called');
        console.log('Has storyboardEditor:', !!window.storyboardEditor);
        console.log('Has editedImageBlob:', !!(window.storyboardEditor && window.storyboardEditor.editedImageBlob));
        
        if (window.storyboardEditor) {
            console.log('Editor state:', {
                hasBlob: !!window.storyboardEditor.editedImageBlob,
                blobSize: window.storyboardEditor.editedImageBlob ? window.storyboardEditor.editedImageBlob.size : 'N/A',
                currentId: window.storyboardEditor.currentStoryboardId
            });
        }
        
        if (window.storyboardEditor && window.storyboardEditor.editedImageBlob) {
            // Convert blob to file for proper PHP handling
            const editedFile = new File([window.storyboardEditor.editedImageBlob], 'edited_frame_' + Date.now() + '.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now()
            });
            
            // Replace the original file input with edited image
            formData.set('frame_file', editedFile);
            
            // Add flag to indicate this is an edited image
            formData.set('is_edited_image', '1');
            
            console.log('=== FORM DATA DEBUG ===');
            console.log('Added edited image to form data:', {
                fileName: editedFile.name,
                fileSize: editedFile.size,
                fileType: editedFile.type,
                lastModified: editedFile.lastModified
            });
            console.log('Form data entries:');
            for (let pair of formData.entries()) {
                if (pair[0] === 'frame_file') {
                    console.log('- frame_file:', pair[1].name, '(' + pair[1].size + ' bytes)');
                } else {
                    console.log('- ' + pair[0] + ':', pair[1]);
                }
            }
            console.log('======================');
        }
    }
    
    // Function to update storyboard image in the main table
    function updateStoryboardImageInTable(storyboardId, imageBlob) {
        try {
            // Create object URL for the edited image
            const imageUrl = URL.createObjectURL(imageBlob);
            
            console.log('=== UPDATE IMAGE DEBUG ===');
            console.log('Storyboard ID:', storyboardId);
            console.log('Image blob size:', imageBlob.size);
            console.log('Generated URL:', imageUrl);
            
            // Method 1: Find by edit button data-post-id
            console.log('Looking for edit button with data-post-id:', storyboardId);
            const editButton = document.querySelector(`[data-post-id="${storyboardId}"]`);
            console.log('Found edit button:', !!editButton);
            
            if (editButton) {
                const parentRow = editButton.closest('tr');
                console.log('Found parent row:', !!parentRow);
                
                if (parentRow) {
                    const img = parentRow.querySelector('img[onclick*="showImageModal"]');
                    console.log('Found image in row:', !!img);
                    console.log('Current image src:', img ? img.src : 'no image');
                    
                    if (img) {
                        // Store original src for fallback
                        img.dataset.originalSrc = img.src;
                        
                        // Update the image source
                        img.src = imageUrl;
                        
                        // Update the onclick attribute to use the new URL
                        const newOnclick = img.getAttribute('onclick').replace(/showImageModal\('[^']*'\)/, `showImageModal('${imageUrl}')`);
                        img.setAttribute('onclick', newOnclick);
                        
                        // Add a visual indicator that the image was updated
                        img.style.border = '2px solid #28a745';
                        setTimeout(() => {
                            img.style.border = '';
                        }, 2000);
                        
                        console.log('Successfully updated image in table');
                        
                        // Show success notification
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Image Updated!',
                                text: 'The edited image has been applied to the storyboard.',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });
                        }
                        return;
                    }
                }
            }
            
            // Method 2: Fallback - find by current modal image source
            const currentModalImage = document.getElementById('current-frame-image');
            if (currentModalImage) {
                const originalSrc = currentModalImage.dataset.originalSrc || currentModalImage.src;
                
                // Find matching image in table
                const tableImages = document.querySelectorAll('.storyboard-table img[onclick*="showImageModal"]');
                tableImages.forEach(img => {
                    if (img.src === originalSrc || img.getAttribute('onclick').includes(originalSrc)) {
                        img.src = imageUrl;
                        const newOnclick = img.getAttribute('onclick').replace(/showImageModal\('[^']*'\)/, `showImageModal('${imageUrl}')`);
                        img.setAttribute('onclick', newOnclick);
                        
                        // Visual feedback
                        img.style.border = '2px solid #28a745';
                        setTimeout(() => {
                            img.style.border = '';
                        }, 2000);
                        
                        console.log('Updated image using fallback method');
                    }
                });
            }
            
        } catch (error) {
            console.error('Error updating image in table:', error);
        }
    }
    
    // Alternative method to update image without relying on storyboard ID
    function updateImageWithoutId(imageBlob) {
        try {
            const imageUrl = URL.createObjectURL(imageBlob);
            console.log('=== ALTERNATIVE UPDATE METHOD ===');
            
            // Method 1: Update based on the original image that was being edited
            const currentModalImage = document.getElementById('current-frame-image');
            if (currentModalImage && currentModalImage.dataset.originalSrc) {
                const originalSrc = currentModalImage.dataset.originalSrc;
                console.log('Looking for image with original src:', originalSrc);
                
                // Find matching image in table
                const tableImages = document.querySelectorAll('.storyboard-table img[onclick*="showImageModal"]');
                console.log('Found table images:', tableImages.length);
                
                let updated = false;
                tableImages.forEach((img, index) => {
                    console.log(`Checking image ${index}:`, img.src);
                    if (img.src === originalSrc || img.getAttribute('onclick').includes(originalSrc)) {
                        console.log('Found matching image, updating...');
                        img.src = imageUrl;
                        const newOnclick = img.getAttribute('onclick').replace(/showImageModal\('[^']*'\)/, `showImageModal('${imageUrl}')`);
                        img.setAttribute('onclick', newOnclick);
                        
                        // Visual feedback
                        img.style.border = '3px solid #28a745';
                        setTimeout(() => {
                            img.style.border = '';
                        }, 3000);
                        
                        updated = true;
                        console.log('Successfully updated image using alternative method');
                    }
                });
                
                if (!updated) {
                    console.log('No matching image found in table');
                }
            } else {
                console.log('No current modal image or original src found');
            }
            
        } catch (error) {
            console.error('Error in alternative update method:', error);
        }
    }
    
    // Function to handle new image upload and show preview with edit option
    function handleNewImageUpload(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Show the new image in the single preview section
                showImagePreview(e.target.result, file.name);
            };
            
            reader.readAsDataURL(file);
        }
    }
    
    // Function to handle pasted images from clipboard
    function handlePastedImage(blob, filename) {
        // Create a File object from the blob
        const file = new File([blob], filename, { type: blob.type });
        
        // Create a DataTransfer object to set the file input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        
        // Set the file input
        const fileInput = document.getElementById('frame_file');
        fileInput.files = dataTransfer.files;
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            showImagePreview(e.target.result, filename);
            
            // Show success notification
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Image Pasted!',
                    text: 'Image from clipboard has been added',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
            }
        };
        reader.readAsDataURL(blob);
    }

    // Function to show image preview in the single preview section
    function showImagePreview(imageSrc, filename) {
        const previewSection = document.getElementById('image-preview-section');
        const previewImage = document.getElementById('preview-image');
        const imageFilename = document.getElementById('image-filename');
        const editButton = document.getElementById('edit-image-btn');
        
        if (previewSection && previewImage && imageFilename) {
            // Update image source and filename
            previewImage.src = imageSrc;
            imageFilename.textContent = filename.startsWith('data:') ? 'New image selected' : `Current image: ${filename}`;
            
            // Update edit button onclick for new images
            if (editButton && imageSrc.startsWith('data:')) {
                editButton.setAttribute('onclick', `openImageEditor('${imageSrc}', handleEditedImage)`);
            }
            
            // Update image onclick for modal view
            previewImage.setAttribute('onclick', `showImageModal('${imageSrc}')`);
            
            // Show the preview section
            previewSection.style.display = 'block';
            
            // Re-initialize feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    }

    // Function to delete current image
    function deleteCurrentImage() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete Image?',
                text: 'Are you sure you want to remove this image permanently?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    performImageDeletion();
                }
            });
        } else {
            // Fallback to confirm if SweetAlert not available
            if (confirm('Are you sure you want to delete this image permanently?')) {
                performImageDeletion();
            }
        }
    }

    // Function to perform actual image deletion
    function performImageDeletion() {
        const storyboardId = window.storyboardEditor ? window.storyboardEditor.currentStoryboardId : null;
        
        // Show loading state
        Swal.fire({
            title: 'Deleting Image...',
            text: 'Please wait while we delete the image',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        if (storyboardId) {
            // Delete from server for existing storyboard
            $.ajax({
                url: '<?php echo get_uri("storyboard/delete_frame_image"); ?>',
                type: 'POST',
                data: {
                    storyboard_id: storyboardId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Delete response:', response);
                    
                    if (response.success) {
                        // Clear frontend elements
                        clearImagePreview();
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Image Deleted!',
                            text: 'Image has been permanently removed',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed',
                            text: response.message || 'Failed to delete image',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Error deleting image: ' + error,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        } else {
            // Just clear frontend for new uploads (not saved yet)
            clearImagePreview();
            
            Swal.fire({
                icon: 'success',
                title: 'Image Removed',
                text: 'Image has been removed',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        }
    }

    // Function to clear image preview elements
    function clearImagePreview() {
        // Clear the file input
        const fileInput = document.getElementById('frame_file');
        if (fileInput) {
            fileInput.value = '';
        }
        
        // Hide the preview section
        const previewSection = document.getElementById('image-preview-section');
        if (previewSection) {
            previewSection.style.display = 'none';
        }
        
        // Clear any stored edited image
        if (window.storyboardEditor) {
            window.storyboardEditor.editedImageBlob = null;
        }
        
        console.log('Image preview cleared');
    }

    $(document).ready(function () {
        console.log('🔧 Modal form JavaScript loaded');
        console.log('Form exists:', !!document.getElementById('storyboard-form'));
        
        // Check if there are global handlers for .general-form
        const generalFormHandlers = $._data(document, 'events');
        if (generalFormHandlers) {
            console.log('Global event handlers found:', Object.keys(generalFormHandlers));
        }
        
        // Setup paste functionality for clipboard images
        const pasteArea = document.getElementById('paste-area');
        
        // Handle paste event on paste area
        if (pasteArea) {
            pasteArea.addEventListener('paste', function(e) {
                e.preventDefault();
                handlePasteEvent(e);
            });
            
            // Visual feedback when paste area is focused
            pasteArea.addEventListener('focus', function() {
                this.classList.add('paste-area-focused');
            });
            
            pasteArea.addEventListener('blur', function() {
                this.classList.remove('paste-area-focused');
            });
        }
        
        // Global paste handler for the entire modal
        $('#storyboard-modal').on('paste', function(e) {
            // Only handle if not in a textarea or input field
            if (!$(e.target).is('textarea, input[type="text"]')) {
                e.preventDefault();
                handlePasteEvent(e.originalEvent);
            }
        });
        
        // Function to handle paste events
        function handlePasteEvent(e) {
            const items = e.clipboardData.items;
            
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                
                // Check if the item is an image
                if (item.type.indexOf('image') !== -1) {
                    const blob = item.getAsFile();
                    const timestamp = new Date().getTime();
                    const filename = 'pasted_image_' + timestamp + '.png';
                    
                    handlePastedImage(blob, filename);
                    return;
                }
            }
            
            // No image found in clipboard
            if (typeof appAlert !== 'undefined') {
                appAlert.warning('No image found in clipboard. Copy an image first.');
            }
        }
        
        // Check for existing event handlers
        const form = document.getElementById('storyboard-form');
        if (form) {
            console.log('Form found, checking existing handlers...');
            
            // Test if form has other submit handlers
            const events = $._data(form, 'events');
            if (events && events.submit) {
                console.log('Existing submit handlers:', events.submit.length);
            } else {
                console.log('No existing submit handlers found');
            }
        }
        
        // Remove any existing handlers and add our custom one
        $('#storyboard-form').off('submit').on('submit', function(e) {
            console.log('🚀 FORM SUBMISSION STARTED - Custom handler triggered!');
            console.log('Event object:', e);
            console.log('Form element:', this);
            console.log('Preventing default form submission...');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // Validate duration first
            const durationValue = $('#duration').val();
            if (durationValue && !/^[0-9]+(\.[0-9]+)?$/.test(durationValue)) {
                $('#duration').addClass('is-invalid');
                if (!$('#duration').next('.invalid-feedback').length) {
                    $('#duration').after('<div class="invalid-feedback">Please enter a valid duration (e.g., 5 or 5.1)</div>');
                }
                appAlert.error('Please fix the duration field before saving.');
                return false;
            }
            
            // Validate file sizes before upload
            var fileInput = $('#raw_footage_files')[0];
            if (fileInput && fileInput.files.length > 0) {
                var totalSize = 0;
                var maxFileSize = 100 * 1024 * 1024; // 100MB per file
                var maxTotalSize = 500 * 1024 * 1024; // 500MB total
                
                for (var i = 0; i < fileInput.files.length; i++) {
                    var fileSize = fileInput.files[i].size;
                    totalSize += fileSize;
                    
                    if (fileSize > maxFileSize) {
                        appAlert.error('File "' + fileInput.files[i].name + '" is too large. Maximum file size is 100MB.');
                        return false;
                    }
                }
                
                if (totalSize > maxTotalSize) {
                    appAlert.error('Total file size is too large. Maximum total size is 500MB.');
                    return false;
                }
            }
            
            // Start upload with progress tracking
            uploadWithProgress();
        });
        
        function uploadWithProgress() {
            console.log('🚀 UPLOAD WITH PROGRESS STARTED');
            console.log('Function called successfully!');
            
            const form = document.getElementById('storyboard-form');
            if (!form) {
                console.error('❌ Form not found!');
                return;
            }
            
            console.log('✅ Form found:', form);
            const formData = new FormData(form);
            
            // Add edited image if available
            console.log('📎 Appending edited image to form...');
            appendEditedImageToForm(formData);
            const submitBtn = $('#storyboard-form').find('button[type="submit"]');
            const fileInput = $('#raw_footage_files')[0];
            const hasFiles = fileInput && fileInput.files.length > 0;
            
            // Show progress UI
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
            
            if (hasFiles) {
                $('#upload-progress-container').show();
                resetProgressBar();
            }
            
            // Create XMLHttpRequest with progress tracking
            const xhr = new XMLHttpRequest();
            let startTime = Date.now();
            
            // Progress tracking
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable && hasFiles) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    const elapsed = (Date.now() - startTime) / 1000;
                    const speed = e.loaded / elapsed; // bytes per second
                    const remaining = (e.total - e.loaded) / speed;
                    
                    updateProgressBar(percentComplete, e.loaded, e.total, speed, remaining);
                }
            });
            
            // Handle response
            xhr.onload = function() {
                submitBtn.prop('disabled', false).html('<i data-feather="check-circle" class="icon-16"></i> Save');
                $('#upload-progress-container').hide();
                
                if (xhr.status === 200) {
                    try {
                        const result = JSON.parse(xhr.responseText);
                        console.log('Server response:', result);
                        console.log('Result success status:', result.success);
                        
                        if (result.success) {
                            console.log('SUCCESS HANDLER CALLED - Form saved successfully!');
                            appAlert.success(result.message || 'Storyboard saved successfully');
                            
                            // Show success message for edited image
                            if (window.storyboardEditor && window.storyboardEditor.editedImageBlob) {
                                console.log('Edited image was saved to database');
                                
                                // Show specific success message for image editing
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Image Saved!',
                                        text: 'Your edited image has been saved to the database.',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true
                                    });
                                }
                            }
                            
                            $('#storyboard-modal').modal('hide');
                            
                            // Use setTimeout to allow modal to close before reload
                            setTimeout(() => {
                                location.reload();
                            }, 500);
                        } else {
                            appAlert.error(result.message || 'Failed to save storyboard');
                        }
                    } catch (e) {
                        appAlert.error('Invalid response from server');
                    }
                } else {
                    let errorMessage = 'Upload failed';
                    if (xhr.status === 413) {
                        errorMessage = 'File too large. Please reduce file size or check server upload limits.';
                    } else if (xhr.status === 504 || xhr.status === 408) {
                        errorMessage = 'Upload timeout. Please try with smaller files or check your connection.';
                    }
                    appAlert.error(errorMessage);
                }
            };
            
            // Handle errors
            xhr.onerror = function() {
                submitBtn.prop('disabled', false).html('<i data-feather="check-circle" class="icon-16"></i> Save');
                $('#upload-progress-container').hide();
                appAlert.error('Network error occurred during upload');
            };
            
            // Handle timeout
            xhr.ontimeout = function() {
                submitBtn.prop('disabled', false).html('<i data-feather="check-circle" class="icon-16"></i> Save');
                $('#upload-progress-container').hide();
                appAlert.error('Upload timeout. Please try with smaller files.');
            };
            
            // Configure and send request
            xhr.timeout = 300000; // 5 minutes
            xhr.open('POST', '<?php echo get_uri("storyboard/save"); ?>');
            xhr.send(formData);
        }
        
        function resetProgressBar() {
            $('#upload-progress-bar').css('width', '0%').attr('aria-valuenow', 0);
            $('#upload-percentage').text('0%');
            $('#upload-speed').text('0 KB/s');
            $('#upload-eta').text('Calculating...');
            $('#upload-size').text('0 MB / 0 MB');
        }
        
        function updateProgressBar(percent, loaded, total, speed, remaining) {
            const percentRounded = Math.round(percent);
            const loadedMB = (loaded / 1024 / 1024).toFixed(1);
            const totalMB = (total / 1024 / 1024).toFixed(1);
            const speedKB = (speed / 1024).toFixed(0);
            const eta = remaining > 0 ? formatTime(remaining) : 'Almost done';
            
            $('#upload-progress-bar').css('width', percent + '%').attr('aria-valuenow', percentRounded);
            $('#upload-percentage').text(percentRounded + '%');
            $('#upload-speed').text(speedKB + ' KB/s');
            $('#upload-eta').text(eta);
            $('#upload-size').text(loadedMB + ' MB / ' + totalMB + ' MB');
        }
        
        function formatTime(seconds) {
            if (seconds < 60) {
                return Math.round(seconds) + 's';
            } else if (seconds < 3600) {
                const minutes = Math.floor(seconds / 60);
                const secs = Math.round(seconds % 60);
                return minutes + 'm ' + secs + 's';
            } else {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                return hours + 'h ' + minutes + 'm';
            }
        }
        
        // Initialize Select2 for dropdowns (excluding story_status)
        $('.select2').not('#story_status').select2({
            dropdownParent: $('#storyboard-form').closest('.modal')
        });
        
        // Initialize feather icons for the edit button
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
        
        // Add click handler to submit button for debugging
        $('button[type="submit"]').on('click', function(e) {
            console.log('💾 SUBMIT BUTTON CLICKED!');
            console.log('Button element:', this);
            console.log('Event:', e);
            console.log('Form will be submitted...');
            
            // Prevent default and handle manually
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🔄 Manually triggering form submission...');
            
            // Manually call our upload function
            uploadWithProgress();
            
            return false;
        });
        
        // Store original image source for tracking updates
        const currentImage = document.getElementById('current-frame-image');
        if (currentImage) {
            currentImage.dataset.originalSrc = currentImage.src;
        }
        
        // Clean up when modal is closed
        $('#storyboard-modal').on('hidden.bs.modal', function() {
            if (window.storyboardEditor) {
                window.storyboardEditor.editedImageBlob = null;
                window.storyboardEditor.currentStoryboardId = null;
            }
        });

        // Handle duration field validation
        $('#duration').on('input', function() {
            const value = $(this).val();
            const isValid = /^[0-9]+(\.[0-9]+)?$/.test(value) || value === '';
            
            if (!isValid && value !== '') {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Please enter a valid duration (e.g., 5 or 5.1)</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });



        // Handle file upload preview
        $('#frame_file').change(function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Show preview
                    const preview = $('<div class="mt-2"><img src="' + e.target.result + '" class="img-thumbnail" style="max-width: 150px;"><p class="small text-muted mt-1">New image selected</p></div>');
                    $('#frame_file').parent().find('.mt-2').remove();
                    $('#frame_file').parent().append(preview);
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle multiple raw footage file uploads
        $('#raw_footage_files').change(function() {
            const files = this.files;
            const previewArea = $('#footage-preview-area');
            const fileList = $('#footage-file-list');
            const warningDiv = $('#file-size-warning');
            
            if (files.length > 0) {
                previewArea.show();
                fileList.empty();
                
                let totalSize = 0;
                let hasLargeFiles = false;
                const maxFileSize = 100 * 1024 * 1024; // 100MB
                const maxTotalSize = 500 * 1024 * 1024; // 500MB
                
                Array.from(files).forEach((file, index) => {
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    totalSize += file.size;
                    
                    if (file.size > 50 * 1024 * 1024) { // 50MB+
                        hasLargeFiles = true;
                    }
                    
                    let statusClass = '';
                    let statusText = '';
                    
                    if (file.size > maxFileSize) {
                        statusClass = 'text-danger';
                        statusText = ' (Too large!)';
                    } else if (file.size > 50 * 1024 * 1024) {
                        statusClass = 'text-warning';
                        statusText = ' (Large file)';
                    }
                    
                    const fileItem = $(`
                        <div class="file-preview-item mb-3 p-3 border rounded bg-light">
                            <div class="d-flex align-items-start">
                                <div class="file-icon me-3">
                                    <i data-feather="video" class="icon-20 text-primary"></i>
                                </div>
                                <div class="file-details flex-grow-1 me-3">
                                    <div class="file-name mb-1">
                                        <strong class="text-truncate d-block" style="max-width: 300px;" title="${file.name}">
                                            ${file.name}
                                        </strong>
                                    </div>
                                    <div class="file-meta">
                                        <small class="text-muted ${statusClass}">
                                            <i data-feather="hard-drive" class="icon-12 me-1"></i>
                                            ${fileSize} MB${statusText}
                                            • <i data-feather="clock" class="icon-12 me-1"></i>
                                            Ready to upload
                                        </small>
                                    </div>
                                </div>
                                <div class="file-actions d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success preview-new-video-btn" data-file-index="${index}" title="Preview">
                                        <i data-feather="play" class="icon-14"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-new-file" data-file-index="${index}" title="Remove">
                                        <i data-feather="trash-2" class="icon-14"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `);
                    fileList.append(fileItem);
                });
                
                // Show warnings
                if (hasLargeFiles || totalSize > 100 * 1024 * 1024) {
                    warningDiv.show();
                } else {
                    warningDiv.hide();
                }
                
                if (totalSize > maxTotalSize) {
                    warningDiv.removeClass('alert-warning').addClass('alert-danger')
                        .html('<i class="fa fa-exclamation-triangle"></i> Total file size exceeds 500MB limit. Please remove some files.');
                }
                
                // Show total size
                const totalSizeMB = (totalSize / 1024 / 1024).toFixed(2);
                if (!$('#total-size-info').length) {
                    fileList.append(`<div id="total-size-info" class="text-muted small mt-2">Total size: ${totalSizeMB} MB</div>`);
                } else {
                    $('#total-size-info').text(`Total size: ${totalSizeMB} MB`);
                }
                
                // Re-initialize feather icons
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            } else {
                previewArea.hide();
                warningDiv.hide();
            }
        });

        // Handle removal of existing footage files
        $(document).on('click', '.remove-footage-file', function() {
            const fileIndex = $(this).data('file-index');
            const removedFiles = $('#removed_footage_files').val();
            const removedArray = removedFiles ? removedFiles.split(',') : [];
            
            if (!removedArray.includes(fileIndex.toString())) {
                removedArray.push(fileIndex.toString());
                $('#removed_footage_files').val(removedArray.join(','));
            }
            
            $(this).closest('.existing-file-item').fadeOut(300, function() {
                $(this).remove();
            });
        });

        // Handle removal of new files (before upload)
        $(document).on('click', '.remove-new-file', function() {
            const fileIndex = $(this).data('file-index');
            const fileInput = $('#raw_footage_files')[0];
            const dt = new DataTransfer();
            
            // Rebuild file list without the removed file
            Array.from(fileInput.files).forEach((file, index) => {
                if (index !== fileIndex) {
                    dt.items.add(file);
                }
            });
            
            fileInput.files = dt.files;
            
            // Trigger change event to refresh preview
            $('#raw_footage_files').trigger('change');
        });

        // Handle video preview from modal form
        $(document).on('click', '.preview-video-btn', function() {
            const videoUrl = $(this).data('video-url');
            const videoName = $(this).data('video-name');
            
            // Set video source and info
            $('#video-source').attr('src', videoUrl);
            $('#video-filename').text(videoName);
            $('#download-video-btn').attr('href', videoUrl);
            $('#video-metadata').text('Loading video information...');
            
            // Load video
            const video = document.getElementById('preview-video');
            
            // Clear previous event listeners
            video.removeEventListener('loadedmetadata', updateVideoMetadata);
            
            // Add error handling
            video.addEventListener('error', function(e) {
                console.error('Video error:', e);
                $('#video-metadata').text('Error loading video. Please try again.');
            });
            
            // Add loading event
            video.addEventListener('loadstart', function() {
                $('#video-metadata').text('Loading video...');
            });
            
            // Add can play event
            video.addEventListener('canplay', function() {
                $('#video-metadata').text('Video ready to play');
            });
            
            video.load();
            
            // Show video modal with proper z-index stacking
            const videoModal = new bootstrap.Modal(document.getElementById('video-preview-modal'), {
                backdrop: true,
                keyboard: true
            });
            videoModal.show();
            
            // Ensure video modal appears above storyboard modal
            $('#video-preview-modal').css('z-index', 1060);
            $('.modal-backdrop').last().css('z-index', 1059);
            
            // Update metadata when video loads
            video.addEventListener('loadedmetadata', updateVideoMetadata);
        });

        // Shared function to update video metadata
        function updateVideoMetadata() {
            const video = document.getElementById('preview-video');
            const duration = formatVideoDuration(video.duration);
            const dimensions = video.videoWidth + 'x' + video.videoHeight;
            $('#video-metadata').text(`Duration: ${duration} • Resolution: ${dimensions}`);
        }

        // Handle fullscreen (shared handler)
        $(document).on('click', '#fullscreen-btn', function() {
            const video = document.getElementById('preview-video');
            if (video.requestFullscreen) {
                video.requestFullscreen();
            } else if (video.webkitRequestFullscreen) {
                video.webkitRequestFullscreen();
            } else if (video.msRequestFullscreen) {
                video.msRequestFullscreen();
            }
        });

        // Pause video when video modal is closed (but keep storyboard modal open)
        $('#video-preview-modal').on('hidden.bs.modal', function() {
            const video = document.getElementById('preview-video');
            video.pause();
            video.currentTime = 0;
            
            // Reset z-index after video modal is closed
            $('#video-preview-modal').css('z-index', '');
        });

        // Handle preview of new (not yet uploaded) video files
        $(document).on('click', '.preview-new-video-btn', function() {
            const fileIndex = $(this).data('file-index');
            const fileInput = $('#raw_footage_files')[0];
            const file = fileInput.files[fileIndex];
            
            if (file) {
                // Create object URL for the file
                const videoUrl = URL.createObjectURL(file);
                const videoName = file.name;
                
                // Set video source and info
                $('#video-source').attr('src', videoUrl);
                $('#video-filename').text(videoName + ' (Preview)');
                $('#download-video-btn').attr('href', '#').hide(); // Hide download for new files
                $('#video-metadata').text('Loading video information...');
                
                // Load video and show modal
                const video = document.getElementById('preview-video');
                video.load();
                
                // Show modal
                $('#video-preview-modal').modal('show');
                
                // Update metadata when video loads
                video.addEventListener('loadedmetadata', function() {
                    const duration = formatVideoDuration(video.duration);
                    const dimensions = video.videoWidth + 'x' + video.videoHeight;
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    $('#video-metadata').text(`Duration: ${duration} • Resolution: ${dimensions} • Size: ${fileSize} MB`);
                });
                
                // Clean up object URL when modal is closed
                $('#video-preview-modal').one('hidden.bs.modal', function() {
                    URL.revokeObjectURL(videoUrl);
                    $('#download-video-btn').show(); // Show download button again
                });
            }
        });

        // Format video duration
        function formatVideoDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = Math.floor(seconds % 60);
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            } else {
                return `${minutes}:${secs.toString().padStart(2, '0')}`;
            }
        }
    });
</script>

<style>
/* Paste Area Styles */
.paste-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;
    outline: none;
}

.paste-area:hover {
    border-color: #007bff;
    background: #e7f3ff;
}

.paste-area-focused {
    border-color: #007bff;
    background: #e7f3ff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

.paste-area-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.paste-area kbd {
    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);
    color: #333;
    display: inline-block;
    font-family: monospace;
    font-size: 0.85em;
    padding: 2px 6px;
    white-space: nowrap;
}

.icon-24 {
    width: 24px;
    height: 24px;
}

/* Upload Progress Bar Styles */
#upload-progress-container {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-top: 1rem;
}

#upload-progress-container .progress {
    height: 1.5rem;
    background-color: #e9ecef;
}

#upload-progress-container .progress-bar {
    background: linear-gradient(45deg, #007bff, #0056b3);
    transition: width 0.3s ease;
}

#upload-status {
    font-size: 0.875rem;
    color: #6c757d;
}

#upload-percentage {
    font-weight: 600;
    color: #495057;
}

/* File upload preview styles */
.existing-files-section, .new-files-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #e9ecef;
}

.existing-file-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.existing-file-item:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
}

.file-preview-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
}

.file-preview-item:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
}

.file-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #e3f2fd;
    border-radius: 8px;
    flex-shrink: 0;
}

.file-details {
    min-width: 0; /* Allow text truncation */
}

.file-name strong {
    font-size: 0.9rem;
    color: #495057;
    line-height: 1.3;
}

.file-meta {
    margin-top: 0.25rem;
}

.file-meta small {
    font-size: 0.75rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.file-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.file-actions .btn {
    padding: 0.375rem 0.5rem;
    border-radius: 6px;
}

/* Icon improvements */
.icon-20 {
    width: 20px;
    height: 20px;
}

.icon-14 {
    width: 14px;
    height: 14px;
}

/* Text truncation */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Progress animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.progress-bar-animated {
    animation: pulse 1.5s ease-in-out infinite;
}

/* Video Preview Modal Styles - Ensure proper stacking */
#video-preview-modal {
    z-index: 1060 !important; /* Higher than storyboard modal */
}

#video-preview-modal .modal-dialog {
    max-width: 900px;
}

#video-preview-modal .video-container {
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
}

#video-preview-modal video {
    background: #000;
    outline: none;
}

#video-preview-modal .video-info {
    border-top: 1px solid #dee2e6;
}

#video-preview-modal .video-details h6 {
    color: #495057;
    margin-bottom: 0.25rem;
}

#video-preview-modal .video-controls .btn {
    margin-left: 0.5rem;
}

/* Ensure video modal backdrop doesn't interfere with storyboard modal */
#video-preview-modal + .modal-backdrop {
    z-index: 1059 !important;
}

/* Video preview button styling */
.preview-video-btn, .preview-new-video-btn {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

.preview-video-btn:hover, .preview-new-video-btn:hover {
    background-color: #218838;
    border-color: #1e7e34;
    color: white;
}

/* Responsive video modal */
@media (max-width: 768px) {
    #video-preview-modal .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
    
    #video-preview-modal video {
        max-height: 50vh;
    }
    
    #video-preview-modal .video-controls {
        margin-top: 0.5rem;
    }
    
    #video-preview-modal .video-info .d-flex {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>