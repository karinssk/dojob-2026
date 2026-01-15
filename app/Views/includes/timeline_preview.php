<?php
if ($files && count($files)) {

    $group_id = make_random_string();

    $box_class = "mb15";
    $caption_class = "more";
    $caption_lang = " " . app_lang('more');
    if (isset($is_message_row)) {
        $box_class = "message-images mb5 mt5";
        $caption_class .= " message-more";
        $caption_lang = "";
    }

    $file_count = 0;

    // Remove the problematic container div - just output files directly

    $is_localhost = is_localhost();

    $timeline_file_path = isset($file_path) ? $file_path : get_setting("timeline_file_path");

    // Initialize arrays to collect webm files and other files
    $recording_files = "";
    $other_files = "";
    $preview_image = "";
   
    // Separate webm files containing "recording" from other files
    foreach ($files as $file) {

        $file_name = $file['file_name'];
        $file_id = get_array_value($file, "file_id");
        $service_type = get_array_value($file, "service_type");

        $is_google_drive_file = ($file_id && $service_type == "google") ? true : false;

        $actual_file_name = remove_file_prefix($file_name);
        $thumbnail = get_source_url_of_file($file, $timeline_file_path, "thumbnail");
        $url = get_source_url_of_file($file, $timeline_file_path);

        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $image = "";

        if ($file_id && $is_google_drive_file) {
            $url = get_uri("uploader/stream_google_drive_file/".$file_id."/".$actual_file_name);
        }

        if (isset($seperate_audio) && $seperate_audio && $extension === "webm" && strpos($file_name, 'recording')) {

            $actual_file_name_without_extension = remove_file_extension($actual_file_name);
            
            $recording_files .= "<audio src='$url' controls='' class='audio file-highlight-section' id='$actual_file_name_without_extension'></audio>";

        } else {

            if (is_viewable_image_file($file_name)) {

                if (!$file_count) {
                    // Count total images for overlay
                    $total_images = 0;
                    foreach ($files as $temp_file) {
                        if (is_viewable_image_file($temp_file['file_name'])) {
                            $total_images++;
                        }
                    }
                    
                    $preview_image = "<div style='position: relative; display: inline-block;'>";
                    $preview_image .= "<img src='$thumbnail' alt='$file_name' style='max-width: 200px; max-height: 150px; object-fit: cover; border-radius: 4px;'/>";
                    
                    // Add overlay if more than 1 image
                    if ($total_images > 1) {
                        $preview_image .= "<div style='position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; padding: 2px 6px; border-radius: 10px; font-size: 12px; font-weight: bold;'>+" . ($total_images - 1) . "</div>";
                    }
                    
                    $preview_image .= "</div>";
                    $image = $preview_image;
                }
                $other_files .= "<a href='#' class='simple-image-modal' data-image-url='$url' data-title='" . $actual_file_name . "' data-file-index='$file_count' data-all-files='" . htmlspecialchars(json_encode($files), ENT_QUOTES, 'UTF-8') . "'>$image</a>";

            } else if ($extension === "webm") {

                if (!$file_count) {
                    $preview_image = "<img src='" . get_file_uri("assets/images/video_preview.jpg") . "' alt='video'/>";
                    $image = $preview_image;
                }
                $other_files .= "<a href='#' class='' data-toggle='app-modal' data-group='$group_id' data-sidebar='0' data-type='audio'  data-content_url='$url' data-title='" . $actual_file_name . "'>$image</a>";
            } else if ($extension === "txt") {

                if (!$file_count) {
                    $preview_image = "<div class='inline-block'><div class='file-mockup'><i data-feather='" . get_file_icon($extension) . "' width='10rem' height='10rem' class='mt-12'></i></div></div>";
                    $image = $preview_image;
                }

                $other_files .= "<a href='#' class='' data-toggle='app-modal' data-group='$group_id' data-sidebar='0' data-type='txt' data-content_url='$url' data-title='" . $actual_file_name . "'>$image</a>";
            } else if (is_iframe_preview_available($file_name)) {

                if (!$file_count) {
                    $preview_image = "<div class='inline-block'><div class='file-mockup'><i data-feather='" . get_file_icon($extension) . "' width='10rem' height='10rem' class='mt-12'></i></div></div>";
                    $image = $preview_image;
                }

                $other_files .= "<a href='#' class='' data-toggle='app-modal' data-group='$group_id' data-sidebar='0' data-type='iframe'  data-content_url='$url' data-title='" . $actual_file_name . "'>$image</a>";
            } else if ((is_viewable_video_file($file_name) && !$file_id && $service_type != "google") || (is_viewable_video_file($file_name) && $file_id && $service_type == "google" && !get_setting("disable_google_preview"))) {

                if (!$file_count) {
                    $preview_image = "<img src='" . get_file_uri("assets/images/video_preview.jpg") . "' alt='video'/>";
                    $image = $preview_image;
                }
                $other_files .= "<a href='#' class='' data-toggle='app-modal' data-group='$group_id' data-sidebar='0' data-type='iframe'  data-content_url='$url' data-title='" . $actual_file_name . "'>$image</a>";
            } else {
                if (!$file_count) {
                    $preview_image = "<div class='inline-block'><div class='file-mockup'><i data-feather='" . get_file_icon($extension) . "' width='10rem' height='10rem' class='mt-12'></i></div></div>";
                    $image = $preview_image;
                }


                if (!$is_localhost && is_google_preview_available($file_name) && !get_setting("disable_google_preview")) {
                    $other_files .= "<a href='#' class='' data-toggle='app-modal' data-group='$group_id' data-sidebar='0' data-type='iframe'  data-content_url='https://drive.google.com/viewerng/viewer?url=$url?pid=explorer&efh=false&a=v&chrome=false&embedded=true' data-title='" . $actual_file_name . "'>$image</a>";
                } else {
                    $other_files .= "<a href='#' class='' data-toggle='app-modal' data-group='$group_id' data-sidebar='0' data-type='not_viewable' data-filename='$actual_file_name' data-description='" . app_lang("file_preview_is_not_available") . "'  data-content_url='$url' data-title='" . $actual_file_name . "'>$image</a>";
                }
            }


            $file_count++;
        }
    }

    $more_image = "";
    if ($file_count > 1) {
        $more_image = "<span class='$caption_class'>+" . ($file_count - 1) . $caption_lang . "</span>";
    }


    if ($recording_files) {
        echo $recording_files;
    }

    echo $other_files . $more_image;
}
?>

<script>
    $(document).ready(function() {
        console.log('Simple timeline image modal loaded');
        
        // File highlight functionality (keep existing)
        $(".file-highlight-link").click(function(e) {
            var fileId = $(this).attr('data-file-id');
            e.preventDefault();
            highlightSpecificFile(fileId);
        });

        function highlightSpecificFile(fileId) {
            $(".file-highlight-section").removeClass("file-highlight");
            $("#recording-" + fileId).addClass("file-highlight");
            window.location.hash = "";
            window.location.hash = "recording-" + fileId;
        }

        // Multi-image modal with navigation - prevent duplicate clicks
        $(document).off('click', '.simple-image-modal').on('click', '.simple-image-modal', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Prevent multiple clicks
            if ($('.simple-timeline-modal').length > 0) {
                console.log('Modal already open, ignoring click');
                return false;
            }
            
            console.log('Multi-image modal clicked');
            
            var clickedImageUrl = $(this).attr('data-image-url');
            var clickedTitle = $(this).attr('data-title');
            var fileIndex = parseInt($(this).attr('data-file-index')) || 0;
            
            console.log('Clicked file index:', fileIndex, 'URL:', clickedImageUrl);
            
            // Parse all files to get image list
            var allFiles = [];
            try {
                allFiles = JSON.parse($(this).attr('data-all-files'));
            } catch(e) {
                console.log('Error parsing files:', e);
                allFiles = [];
            }
            
            // Build image files array and find correct current index
            var imageFiles = [];
            var currentImageIndex = 0;
            var imageFileIndex = 0;
            
            allFiles.forEach(function(file, index) {
                if (isViewableImageFile(file.file_name)) {
                    var fileUrl = getSourceUrlOfFile(file);
                    var fileName = removeFilePrefix(file.file_name);
                    imageFiles.push({
                        url: fileUrl,
                        title: fileName,
                        originalIndex: index
                    });
                    
                    // Check if this is the clicked image by comparing URLs
                    if (fileUrl === clickedImageUrl || index === fileIndex) {
                        currentImageIndex = imageFileIndex;
                        console.log('Found clicked image at image index:', currentImageIndex);
                    }
                    
                    imageFileIndex++;
                }
            });
            
            console.log('Found', imageFiles.length, 'images, current index:', currentImageIndex);
            console.log('Image files:', imageFiles);
            
            // Create modal with navigation
            createImageModal(imageFiles, currentImageIndex);
            
            return false;
        });
        
        function isViewableImageFile(fileName) {
            var imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            var extension = fileName.split('.').pop().toLowerCase();
            return imageExtensions.includes(extension);
        }
        
        function getSourceUrlOfFile(file) {
            var timelineFilePath = '<?php echo isset($file_path) ? $file_path : get_setting("timeline_file_path"); ?>';
            var baseUrl = '<?php echo base_url(); ?>';
            
            if (file.file_id && file.service_type == "google") {
                return baseUrl + 'uploader/stream_google_drive_file/' + file.file_id + '/' + removeFilePrefix(file.file_name);
            }
            
            return baseUrl + timelineFilePath + file.file_name;
        }
        
        function removeFilePrefix(fileName) {
            return fileName.replace(/^\d+_/, '');
        }
        
        function createImageModal(imageFiles, currentIndex) {
            var hasMultiple = imageFiles.length > 1;
            var currentImage = imageFiles[currentIndex];
            
            var modalHtml = '<div class="modal fade simple-timeline-modal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">' +
                '<div class="modal-dialog modal-lg" role="document" style="max-width: 90vw; margin: 2vh auto;">' +
                '<div class="modal-content" style="background: rgba(0,0,0,0.95); border: none; border-radius: 8px;">' +
                '<div class="modal-header" style="border: none; padding: 15px; display: flex; justify-content: space-between; align-items: center;">' +
                '<h5 class="modal-title" style="color: white; margin: 0; flex-grow: 1;">' + currentImage.title + '</h5>';
            
            if (hasMultiple) {
                modalHtml += '<div class="image-counter" style="color: white; margin-right: 15px; font-size: 14px;">' +
                    '<span id="current-image-num">' + (currentIndex + 1) + '</span> / ' + imageFiles.length +
                    '</div>';
            }
            
            modalHtml += '<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1; background: none; border: none; font-size: 24px; cursor: pointer; padding: 0;">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>' +
                '<div class="modal-body" style="padding: 0; text-align: center; position: relative;">';
            
            // Add navigation buttons if multiple images
            if (hasMultiple) {
                modalHtml += '<button class="nav-btn prev-btn" onclick="navigateImage(-1)" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(0,0,0,0.7); color: white; border: none; padding: 15px 20px; border-radius: 50%; cursor: pointer; font-size: 18px;">' +
                    '‹' +
                    '</button>';
            }
            
            modalHtml += '<img id="modal-image" src="' + currentImage.url + '" style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 4px; transition: opacity 0.3s;" onload="console.log(\'Modal image loaded\')">';
            
            if (hasMultiple) {
                modalHtml += '<button class="nav-btn next-btn" onclick="navigateImage(1)" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(0,0,0,0.7); color: white; border: none; padding: 15px 20px; border-radius: 50%; cursor: pointer; font-size: 18px;">' +
                    '›' +
                    '</button>';
            }
            
            modalHtml += '</div></div></div></div>';
            
            var modal = $(modalHtml);
            
            // Store data globally for navigation
            window.timelineImages = imageFiles;
            window.currentImageIndex = currentIndex;
            
            // Add to body and show
            $('body').append(modal);
            modal.modal('show');
            
            // Simple close - just use Bootstrap's built-in functionality
            modal.find('.close').on('click', function() {
                console.log('Close clicked');
                closeModal();
            });
            
            // ESC key to close
            $(document).on('keydown.timeline-modal', function(e) {
                if (e.key === 'Escape') {
                    console.log('ESC pressed');
                    closeModal();
                } else if (hasMultiple && $('.simple-timeline-modal').is(':visible')) {
                    if (e.key === 'ArrowLeft') {
                        navigateImage(-1);
                    } else if (e.key === 'ArrowRight') {
                        navigateImage(1);
                    }
                }
            });
            
            // Backdrop click
            modal.on('click', function(e) {
                if (e.target === this) {
                    console.log('Backdrop clicked');
                    closeModal();
                }
            });
            
            // Cleanup function
            function closeModal() {
                console.log('Closing modal');
                $(document).off('keydown.timeline-modal');
                window.timelineImages = null;
                window.currentImageIndex = 0;
                modal.modal('hide');
                setTimeout(function() {
                    modal.remove();
                }, 300);
            }
        }
    });
    
    // Global navigation function
    function navigateImage(direction) {
        if (!window.timelineImages || window.timelineImages.length <= 1) return;
        
        window.currentImageIndex += direction;
        
        // Loop around
        if (window.currentImageIndex < 0) {
            window.currentImageIndex = window.timelineImages.length - 1;
        } else if (window.currentImageIndex >= window.timelineImages.length) {
            window.currentImageIndex = 0;
        }
        
        var currentImage = window.timelineImages[window.currentImageIndex];
        var imgElement = document.getElementById('modal-image');
        var titleElement = document.querySelector('.simple-timeline-modal .modal-title');
        var counterElement = document.getElementById('current-image-num');
        
        if (imgElement && currentImage) {
            // Add loading effect
            imgElement.style.opacity = '0.5';
            
            imgElement.onload = function() {
                this.style.opacity = '1';
            };
            
            imgElement.src = currentImage.url;
            
            if (titleElement) {
                titleElement.textContent = currentImage.title;
            }
            
            if (counterElement) {
                counterElement.textContent = window.currentImageIndex + 1;
            }
        }
    }
</script>

<style>
    /* Simple timeline image modal styles */
    .simple-timeline-modal .modal-content {
        background: rgba(0, 0, 0, 0.95) !important;
        border: none !important;
        border-radius: 8px !important;
    }
    
    .simple-timeline-modal .modal-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    
    .simple-timeline-modal .modal-title {
        color: white !important;
        font-size: 16px !important;
    }
    
    .simple-timeline-modal .close {
        color: white !important;
        opacity: 1 !important;
        text-shadow: none !important;
    }
    
    .simple-timeline-modal .close:hover {
        opacity: 0.7 !important;
    }
    
    .simple-timeline-modal .nav-btn {
        transition: all 0.3s ease !important;
    }
    
    .simple-timeline-modal .nav-btn:hover {
        background: rgba(0,0,0,0.9) !important;
        transform: translateY(-50%) scale(1.1) !important;
    }
    
    .simple-timeline-modal .image-counter {
        background: rgba(0,0,0,0.7) !important;
        padding: 5px 10px !important;
        border-radius: 15px !important;
    }
    
    @media (max-width: 768px) {
        .simple-timeline-modal .modal-dialog {
            margin: 1vh auto !important;
            max-width: 95vw !important;
        }
        
        .simple-timeline-modal .modal-body img {
            max-height: 70vh !important;
        }
        
        .simple-timeline-modal .nav-btn {
            padding: 10px 15px !important;
            font-size: 16px !important;
        }
        
        .simple-timeline-modal .nav-btn.prev-btn {
            left: 10px !important;
        }
        
        .simple-timeline-modal .nav-btn.next-btn {
            right: 10px !important;
        }
    }
</style>