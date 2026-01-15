/**
 * Enhanced Video Manager for Storyboard
 * Provides better video deletion functionality with improved UX
 */

// Global variables for video management
let removedVideoFiles = [];
let videoPreviewModal = null;

// Initialize enhanced video manager
function initializeEnhancedVideoManager() {
    console.log('Initializing enhanced video manager...');
    
    // Override existing video deletion handlers
    $(document).off('click', '.remove-footage-file');
    $(document).off('click', '.remove-new-file');
    
    // Add enhanced handlers
    $(document).on('click', '.remove-footage-file', handleExistingVideoDelete);
    $(document).on('click', '.remove-new-file', handleNewVideoDelete);
    $(document).on('click', '.preview-video-btn', handleVideoPreview);
    
    // Add confirmation dialogs
    setupVideoDeleteConfirmations();
    
    console.log('Enhanced video manager initialized');
}

// Handle existing video file deletion with confirmation
function handleExistingVideoDelete(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const button = $(this);
    const fileIndex = button.data('file-index');
    const fileName = button.closest('.existing-file-item').find('.file-name').text().trim();
    const fileItem = button.closest('.existing-file-item');
    
    console.log('Attempting to delete existing video:', fileName, 'Index:', fileIndex);
    
    // Show confirmation dialog
    Swal.fire({
        title: 'Delete Video File?',
        html: `
            <div class="text-start">
                <p>Are you sure you want to delete this video file?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>File:</strong> ${fileName}<br>
                    <strong>Warning:</strong> This action cannot be undone.
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            performExistingVideoDelete(fileIndex, fileItem, fileName);
        }
    });
}

// Perform the actual deletion of existing video
function performExistingVideoDelete(fileIndex, fileItem, fileName) {
    console.log('=== PERFORMING VIDEO DELETION ===');
    console.log('File Index:', fileIndex);
    console.log('File Name:', fileName);
    
    // Ensure fileIndex is a valid number
    if (fileIndex === undefined || fileIndex === null || fileIndex === '') {
        console.error('Invalid file index:', fileIndex);
        Swal.fire({
            icon: 'error',
            title: 'Deletion Error',
            text: 'Invalid file index. Cannot delete video.',
            timer: 3000
        });
        return;
    }
    
    // Add to removed files list
    let removedFilesInput = $('#removed_footage_files');
    if (removedFilesInput.length === 0) {
        console.error('removed_footage_files input not found!');
        // Create the input if it doesn't exist
        const hiddenInput = $('<input type="hidden" id="removed_footage_files" name="removed_footage_files" value="">');
        $('#storyboard-form').append(hiddenInput);
        removedFilesInput = hiddenInput;
        console.log('Created missing removed_footage_files input');
    }

    const currentRemoved = removedFilesInput.val() || '';
    const removedArray = currentRemoved ? currentRemoved.split(',').filter(v => v.trim()) : [];
    
    console.log('Current removed files:', currentRemoved);
    console.log('Current removed array:', removedArray);
    
    // Add the index if not already present
    const indexStr = fileIndex.toString();
    if (!removedArray.includes(indexStr)) {
        removedArray.push(indexStr);
        const newValue = removedArray.join(',');
        removedFilesInput.val(newValue);
        removedVideoFiles.push({ index: fileIndex, name: fileName });
        
        console.log('Added index to removal list:', indexStr);
        console.log('New removed files value:', newValue);
    } else {
        console.log('Index already in removal list:', indexStr);
    }
    
    // Mark the item as deleted
    fileItem.addClass('removing deleted-video');
    fileItem.attr('data-deleted', 'true');
    fileItem.attr('data-file-index', fileIndex); // Ensure index is preserved
    
    // Add multiple hidden inputs to ensure the deletion is processed
    const hiddenInputs = [
        $('<input type="hidden" name="delete_video_' + fileIndex + '" value="1">'),
        $('<input type="hidden" name="confirm_video_deletions" value="1">'),
        $('<input type="hidden" name="deleted_file_' + fileIndex + '" value="' + fileName + '">')
    ];
    
    hiddenInputs.forEach(input => {
        $('#storyboard-form').append(input);
    });
    
    console.log('Added hidden inputs for deletion confirmation');
    
    // Animate removal
    fileItem.fadeOut(400, function() {
        $(this).hide(); // Hide instead of remove to keep the data
        
        // Show success message
        showVideoDeleteSuccess(fileName);
        
        // Update file count
        updateVideoFileCount();
        
        // Trigger form change event to indicate unsaved changes
        $('#storyboard-form').trigger('change');
        
        // Final verification
        console.log('=== DELETION SETUP COMPLETE ===');
        console.log('Final removed files value:', $('#removed_footage_files').val());
        console.log('Hidden inputs added:', hiddenInputs.length);
        console.log('File item hidden:', fileItem.is(':hidden'));
    });
}

// Handle new video file deletion (before upload)
function handleNewVideoDelete(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const button = $(this);
    const fileIndex = button.data('file-index');
    const fileName = button.closest('.new-file-item').find('.file-name').text().trim();
    
    console.log('Attempting to delete new video:', fileName, 'Index:', fileIndex);
    
    // Show confirmation dialog
    Swal.fire({
        title: 'Remove Video File?',
        html: `
            <div class="text-start">
                <p>Remove this video file from the upload queue?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>File:</strong> ${fileName}<br>
                    <strong>Note:</strong> This file hasn't been uploaded yet.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            performNewVideoDelete(fileIndex, fileName);
        }
    });
}

// Perform the actual deletion of new video
function performNewVideoDelete(fileIndex, fileName) {
    console.log('Performing removal of new video:', fileName);
    
    const fileInput = $('#raw_footage_files')[0];
    if (!fileInput || !fileInput.files) {
        console.error('File input not found');
        return;
    }
    
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
    
    // Show success message
    showVideoRemoveSuccess(fileName);
    
    console.log('New video file removed successfully');
}

// Enhanced video preview with better modal handling
function handleVideoPreview(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const button = $(this);
    const videoUrl = button.data('video-url');
    const videoName = button.data('video-name');
    
    console.log('Opening video preview:', videoName, videoUrl);
    
    if (!videoUrl) {
        Swal.fire({
            icon: 'error',
            title: 'Video Not Found',
            text: 'The video file could not be found.',
            timer: 3000
        });
        return;
    }
    
    // Set video source and info
    $('#video-source').attr('src', videoUrl);
    $('#video-filename').text(videoName || 'Unknown Video');
    $('#download-video-btn').attr('href', videoUrl);
    $('#video-metadata').text('Loading video information...');
    
    // Load video
    const video = document.getElementById('preview-video');
    if (video) {
        video.load();
        
        // Update metadata when video loads
        video.addEventListener('loadedmetadata', function() {
            const duration = formatVideoDuration(video.duration);
            const dimensions = `${video.videoWidth}x${video.videoHeight}`;
            $('#video-metadata').text(`Duration: ${duration} | Resolution: ${dimensions}`);
        });
        
        video.addEventListener('error', function() {
            $('#video-metadata').text('Error loading video metadata');
        });
    }
    
    // Show modal
    const videoModal = new bootstrap.Modal(document.getElementById('video-preview-modal'));
    videoModal.show();
    
    // Ensure video modal appears above storyboard modal
    $('#video-preview-modal').css('z-index', 1060);
    $('.modal-backdrop').last().css('z-index', 1059);
}

// Setup video delete confirmations
function setupVideoDeleteConfirmations() {
    // Add enhanced styling to delete buttons
    $(document).on('mouseenter', '.remove-footage-file, .remove-new-file', function() {
        $(this).addClass('btn-danger-hover');
    });
    
    $(document).on('mouseleave', '.remove-footage-file, .remove-new-file', function() {
        $(this).removeClass('btn-danger-hover');
    });
}

// Show success message for video deletion
function showVideoDeleteSuccess(fileName) {
    Swal.fire({
        icon: 'success',
        title: 'Video Deleted',
        text: `"${fileName}" has been marked for deletion.`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

// Show success message for video removal
function showVideoRemoveSuccess(fileName) {
    Swal.fire({
        icon: 'success',
        title: 'Video Removed',
        text: `"${fileName}" has been removed from upload queue.`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

// Update video file count display
function updateVideoFileCount() {
    const existingFiles = $('.existing-file-item:visible').length;
    const newFiles = $('.new-file-item:visible').length;
    const totalFiles = existingFiles + newFiles;
    
    // Update any file count displays
    $('.video-file-count').text(totalFiles);
    
    // Show/hide empty state
    if (totalFiles === 0) {
        $('.no-videos-message').show();
    } else {
        $('.no-videos-message').hide();
    }
}

// Format video duration
function formatVideoDuration(seconds) {
    if (isNaN(seconds) || seconds < 0) return 'Unknown';
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    
    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    } else {
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }
}

// Enhanced file upload handling
function enhanceFileUploadHandling() {
    const fileInput = $('#raw_footage_files');
    
    fileInput.on('change', function() {
        const files = this.files;
        console.log('Files selected:', files.length);
        
        // Validate files
        let validFiles = 0;
        let invalidFiles = [];
        
        Array.from(files).forEach((file, index) => {
            if (isValidVideoFile(file)) {
                validFiles++;
            } else {
                invalidFiles.push(file.name);
            }
        });
        
        // Show validation results
        if (invalidFiles.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Files Detected',
                html: `
                    <div class="text-start">
                        <p><strong>${validFiles}</strong> valid video files selected.</p>
                        <p><strong>${invalidFiles.length}</strong> invalid files will be ignored:</p>
                        <ul class="text-muted small">
                            ${invalidFiles.map(name => `<li>${name}</li>`).join('')}
                        </ul>
                        <p class="small text-info">Supported formats: MP4, AVI, MOV, WMV, FLV, MKV</p>
                    </div>
                `,
                confirmButtonText: 'Continue'
            });
        }
        
        updateVideoFileCount();
    });
}

// Validate video file
function isValidVideoFile(file) {
    const validTypes = [
        'video/mp4',
        'video/avi',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-flv',
        'video/x-matroska'
    ];
    
    const validExtensions = ['.mp4', '.avi', '.mov', '.wmv', '.flv', '.mkv'];
    
    const hasValidType = validTypes.includes(file.type);
    const hasValidExtension = validExtensions.some(ext => 
        file.name.toLowerCase().endsWith(ext)
    );
    
    return hasValidType || hasValidExtension;
}

// Add bulk video operations
function addBulkVideoOperations() {
    // Add bulk delete button for existing videos
    const existingVideosContainer = $('#existing-footage-files');
    if (existingVideosContainer.length && $('.existing-file-item').length > 1) {
        const bulkDeleteBtn = `
            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-outline-danger bulk-delete-videos">
                    <i data-feather="trash-2" class="icon-14 me-1"></i>
                    Delete All Videos
                </button>
            </div>
        `;
        existingVideosContainer.prepend(bulkDeleteBtn);
    }
    
    // Handle bulk delete
    $(document).on('click', '.bulk-delete-videos', function() {
        const videoCount = $('.existing-file-item:visible').length;
        
        Swal.fire({
            title: 'Delete All Videos?',
            html: `
                <div class="text-start">
                    <p>Are you sure you want to delete all <strong>${videoCount}</strong> video files?</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone.
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, delete all ${videoCount} videos`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $('.existing-file-item:visible .remove-footage-file').each(function() {
                    const fileIndex = $(this).data('file-index');
                    const fileItem = $(this).closest('.existing-file-item');
                    const fileName = fileItem.find('.file-name').text().trim();
                    
                    performExistingVideoDelete(fileIndex, fileItem, fileName);
                });
                
                Swal.fire({
                    icon: 'success',
                    title: 'All Videos Deleted',
                    text: `${videoCount} videos have been marked for deletion.`,
                    timer: 3000
                });
            }
        });
    });
}

// Initialize when document is ready
$(document).ready(function() {
    console.log('Initializing enhanced video manager...');
    
    // Small delay to ensure modal is loaded
    setTimeout(() => {
        initializeEnhancedVideoManager();
        enhanceFileUploadHandling();
        addBulkVideoOperations();
    }, 500);
});

// Initialize when storyboard modal is shown
$(document).on('shown.bs.modal', '#storyboard-modal', function() {
    console.log('Storyboard modal shown, reinitializing video manager...');
    setTimeout(() => {
        initializeEnhancedVideoManager();
        enhanceFileUploadHandling();
        addBulkVideoOperations();
        updateVideoFileCount();
        setupFormSubmissionHandler();
    }, 200);
});

// Setup form submission handler to ensure video deletions are processed
function setupFormSubmissionHandler() {
    console.log('Setting up form submission handler for video deletions...');
    
    // Override form submission to ensure video deletion data is sent
    $('#storyboard-form').off('submit.videoManager').on('submit.videoManager', function(e) {
        console.log('=== FORM SUBMISSION INTERCEPTED ===');
        
        // Ensure removed files data is properly set
        const removedFiles = $('#removed_footage_files').val();
        console.log('Removed files being sent:', removedFiles);
        
        // Validate that we have the required input
        if (!$('#removed_footage_files').length) {
            console.error('removed_footage_files input missing! Creating it...');
            const hiddenInput = $('<input type="hidden" id="removed_footage_files" name="removed_footage_files" value="">');
            $(this).append(hiddenInput);
        }
        
        if (removedFiles && removedFiles.trim()) {
            console.log('Video deletions detected, adding confirmation inputs...');
            
            // Add multiple confirmation inputs to ensure server processing
            const confirmInputs = [
                $('<input type="hidden" name="confirm_video_deletions" value="1">'),
                $('<input type="hidden" name="process_video_deletions" value="1">'),
                $('<input type="hidden" name="video_deletion_count" value="' + removedFiles.split(',').filter(f => f.trim()).length + '">')
            ];
            
            confirmInputs.forEach(input => {
                // Remove existing to avoid duplicates
                $('input[name="' + input.attr('name') + '"]').remove();
                $(this).append(input);
            });
            
            // Log all form data for debugging
            const formData = new FormData(this);
            console.log('=== FORM DATA BEING SENT ===');
            for (let [key, value] of formData.entries()) {
                if (key.includes('footage') || key.includes('delete') || key.includes('removed') || key.includes('video')) {
                    console.log(`${key}: ${value}`);
                }
            }
            
            // Show user confirmation
            const deletedCount = removedFiles.split(',').filter(f => f.trim()).length;
            console.log(`${deletedCount} video(s) will be deleted on save`);
            
        } else {
            console.log('No video deletions to process');
        }
        
        // Don't prevent default - let the form submit normally
        return true;
    });
    
    // Also handle direct button clicks
    $(document).off('click.videoManager', '#storyboard-form button[type="submit"], .save-storyboard-btn').on('click.videoManager', '#storyboard-form button[type="submit"], .save-storyboard-btn', function(e) {
        console.log('=== SAVE BUTTON CLICKED ===');
        
        const removedFiles = $('#removed_footage_files').val();
        if (removedFiles && removedFiles.trim()) {
            const deletedCount = removedFiles.split(',').filter(f => f.trim()).length;
            console.log(`Preparing to delete ${deletedCount} video(s):`, removedFiles);
            
            // Final validation before submission
            if (!confirm(`Are you sure you want to delete ${deletedCount} video file(s)? This action cannot be undone.`)) {
                e.preventDefault();
                return false;
            }
        }
    });
}

// Add server-side validation for video deletions
function validateServerVideoProcessing() {
    console.log('Validating server-side video processing...');
    
    // Check if the server properly handles removed_footage_files parameter
    const testData = {
        removed_footage_files: '0,1,2',
        confirm_video_deletions: '1'
    };
    
    console.log('Server should process these parameters:', testData);
    
    // Add warning if videos come back after refresh
    $(window).on('beforeunload', function() {
        const removedFiles = $('#removed_footage_files').val();
        if (removedFiles && removedFiles.trim()) {
            console.warn('Videos marked for deletion:', removedFiles);
            console.warn('If these videos reappear after refresh, check server-side processing');
        }
    });
}

// Add debugging function for server integration
function debugServerIntegration() {
    console.log('=== SERVER INTEGRATION DEBUG ===');
    
    // Check form existence
    const form = $('#storyboard-form');
    console.log('Form exists:', form.length > 0);
    console.log('Form action:', form.attr('action'));
    console.log('Form method:', form.attr('method'));
    
    // Check removed files input
    const removedInput = $('#removed_footage_files');
    console.log('Removed files input exists:', removedInput.length > 0);
    console.log('Removed files input value:', removedInput.val());
    console.log('Removed files input name:', removedInput.attr('name'));
    
    // Check deleted video items
    const deletedItems = $('.deleted-video');
    console.log('Deleted video items:', deletedItems.length);
    deletedItems.each(function(index) {
        const item = $(this);
        console.log(`  Deleted item ${index}:`, {
            index: item.data('file-index'),
            deleted: item.attr('data-deleted'),
            fileName: item.find('.file-name').text().trim()
        });
    });
    
    // Check hidden deletion inputs
    const hiddenInputs = $('input[name^="delete_video_"], input[name*="confirm"], input[name*="removed"]');
    console.log('Hidden deletion inputs:', hiddenInputs.length);
    hiddenInputs.each(function() {
        const input = $(this);
        console.log(`  ${input.attr('name')}: ${input.val()}`);
    });
    
    // Check all form data
    if (form.length > 0) {
        const formData = new FormData(form[0]);
        console.log('=== ALL FORM DATA ===');
        for (let [key, value] of formData.entries()) {
            if (key.includes('footage') || key.includes('delete') || key.includes('removed') || key.includes('video')) {
                console.log(`${key}: ${value}`);
            }
        }
    }
    
    // Check existing video items
    const existingItems = $('.existing-file-item');
    console.log('Total existing video items:', existingItems.length);
    console.log('Visible existing video items:', $('.existing-file-item:visible').length);
    console.log('Hidden existing video items:', $('.existing-file-item:hidden').length);
    
    // Provide debugging steps
    console.log('=== DEBUGGING STEPS ===');
    console.log('1. Check if removed_footage_files parameter is being sent');
    console.log('2. Verify server receives the parameter (check server logs)');
    console.log('3. Ensure server processes the parameter correctly');
    console.log('4. Check file permissions for deletion');
    console.log('5. Verify database updates remove file references');
    console.log('6. Test with debug_video_deletion.php');
    
    // Return debug info for further inspection
    return {
        formExists: form.length > 0,
        removedFilesValue: removedInput.val(),
        deletedItemsCount: deletedItems.length,
        hiddenInputsCount: hiddenInputs.length,
        totalVideoItems: existingItems.length,
        visibleVideoItems: $('.existing-file-item:visible').length
    };
}

// Make functions available globally
window.initializeEnhancedVideoManager = initializeEnhancedVideoManager;
window.handleExistingVideoDelete = handleExistingVideoDelete;
window.handleNewVideoDelete = handleNewVideoDelete;
window.debugServerIntegration = debugServerIntegration;
window.validateServerVideoProcessing = validateServerVideoProcessing;

// Test function to simulate video deletion
function testVideoDeletion() {
    console.log('=== TESTING VIDEO DELETION ===');
    
    // Find the first video item
    const firstVideo = $('.existing-file-item').first();
    if (firstVideo.length === 0) {
        console.error('No video items found to test deletion');
        return;
    }
    
    const fileIndex = firstVideo.find('.remove-footage-file').data('file-index');
    const fileName = firstVideo.find('.file-name').text().trim();
    
    console.log('Testing deletion of:', fileName, 'at index:', fileIndex);
    
    // Simulate the deletion process
    performExistingVideoDelete(fileIndex, firstVideo, fileName);
    
    // Check the results
    setTimeout(() => {
        console.log('=== TEST RESULTS ===');
        debugServerIntegration();
    }, 1000);
}

// Force initialization function for troubleshooting
function forceInitializeVideoManager() {
    console.log('=== FORCE INITIALIZING VIDEO MANAGER ===');
    
    // Remove all existing handlers
    $(document).off('click', '.remove-footage-file');
    $(document).off('click', '.remove-new-file');
    $(document).off('click', '.preview-video-btn');
    
    // Re-initialize
    initializeEnhancedVideoManager();
    enhanceFileUploadHandling();
    addBulkVideoOperations();
    setupFormSubmissionHandler();
    
    console.log('Video manager force-initialized');
    
    // Test if handlers are working
    const deleteButtons = $('.remove-footage-file');
    console.log('Delete buttons found:', deleteButtons.length);
    
    if (deleteButtons.length > 0) {
        console.log('Click handlers attached:', deleteButtons.first().data('events') !== undefined);
    }
}

// Make test functions available globally
window.testVideoDeletion = testVideoDeletion;
window.forceInitializeVideoManager = forceInitializeVideoManager;
