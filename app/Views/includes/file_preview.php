<?php




//always show the google drive files using iframe. 
if ($is_image_file) {
    // Check if we have multiple files for navigation
    $image_files = array();
    $current_index = 0;
    
    if (isset($all_files) && is_array($all_files)) {
        foreach ($all_files as $index => $file) {
            $fname = get_array_value($file, "file_name");
            if (is_image_file($fname)) {
                $image_files[] = array(
                    'index' => $index,
                    'url' => get_source_url_of_file($file, get_setting("timeline_file_path")),
                    'name' => $fname
                );
                if ($index == $current_file_index) {
                    $current_index = count($image_files) - 1;
                }
            }
        }
    }
    
    $has_multiple_images = count($image_files) > 1;
?>
    <div class="image-preview-container" style="position: relative; height: 80vh; max-width: 90vw; margin: 0 auto; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.95); border-radius: 8px;">
        <?php if ($has_multiple_images): ?>
            <button class="app-modal-previous-button" onclick="navigateImage(-1)" style="position: absolute; left: 20px; z-index: 10; background: rgba(0,0,0,0.5); color: white; border: none; padding: 10px 15px; border-radius: 50%; cursor: pointer;">
                <i data-feather="chevron-left"></i>
            </button>
        <?php endif; ?>
        
        <img id="preview-image" src="<?php echo $file_url; ?>" style="max-width: 90%; max-height: 90%; object-fit: contain; transition: opacity 0.3s ease;" onload="this.style.opacity=1" onloadstart="this.style.opacity=0.5">
        
        <?php if ($has_multiple_images): ?>
            <button class="app-modal-next-button" onclick="navigateImage(1)" style="position: absolute; right: 20px; z-index: 10; background: rgba(0,0,0,0.5); color: white; border: none; padding: 10px 15px; border-radius: 50%; cursor: pointer;">
                <i data-feather="chevron-right"></i>
            </button>
            
            <div class="image-counter" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.7); color: white; padding: 5px 15px; border-radius: 15px; font-size: 14px;">
                <span id="current-image-number"><?php echo $current_index + 1; ?></span> / <?php echo count($image_files); ?>
            </div>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
        var imageFiles = <?php echo json_encode($image_files); ?>;
        var currentImageIndex = <?php echo $current_index; ?>;
        var expenseId = <?php echo isset($expense_id) ? $expense_id : 0; ?>;
        
        function navigateImage(direction) {
            currentImageIndex += direction;
            
            if (currentImageIndex < 0) {
                currentImageIndex = imageFiles.length - 1;
            } else if (currentImageIndex >= imageFiles.length) {
                currentImageIndex = 0;
            }
            
            var currentImage = imageFiles[currentImageIndex];
            var imgElement = document.getElementById('preview-image');
            
            // Add loading state
            imgElement.style.opacity = '0.5';
            
            imgElement.onload = function() {
                this.style.opacity = '1';
            };
            
            imgElement.src = currentImage.url;
            document.getElementById('current-image-number').textContent = currentImageIndex + 1;
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (imageFiles.length > 1) {
                if (e.key === 'ArrowLeft') {
                    navigateImage(-1);
                } else if (e.key === 'ArrowRight') {
                    navigateImage(1);
                }
            }
        });
        
        // Touch/swipe support for mobile
        if (imageFiles.length > 1) {
            var startX = 0;
            var endX = 0;
            
            document.getElementById('preview-image').addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
            });
            
            document.getElementById('preview-image').addEventListener('touchend', function(e) {
                endX = e.changedTouches[0].clientX;
                var diffX = startX - endX;
                
                if (Math.abs(diffX) > 50) { // Minimum swipe distance
                    if (diffX > 0) {
                        navigateImage(1); // Swipe left, go to next
                    } else {
                        navigateImage(-1); // Swipe right, go to previous
                    }
                }
            });
        }
        
        $(document).ready(function() {
            // Initialize feather icons for navigation buttons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
            
            // Force smaller modal size with inline styles
            $(".app-modal.image-modal .app-modal-body").css({
                "margin": "5vh auto",
                "width": "90vw",
                "max-width": "1200px",
                "height": "80vh",
                "border-radius": "8px",
                "overflow": "hidden"
            });
            
            $(".app-modal.image-modal .app-modal-content").css({
                "background": "rgba(0, 0, 0, 0.95)",
                "border-radius": "8px",
                "height": "100%"
            });
            
            $(".app-modal.image-modal .app-modal-content-area").css({
                "height": "80vh",
                "border-radius": "8px",
                "display": "flex",
                "align-items": "center",
                "justify-content": "center",
                "padding": "0"
            });
        });
    </script>
    
    <style>
        /* Force smaller modal size - inline styles to override any cache issues */
        .app-modal.image-modal .app-modal-body {
            margin: 5vh auto !important;
            width: 90vw !important;
            max-width: 1200px !important;
            height: 80vh !important;
            border-radius: 8px !important;
            overflow: hidden !important;
        }
        
        .app-modal.image-modal .app-modal-content {
            background: rgba(0, 0, 0, 0.95) !important;
            border-radius: 8px !important;
            height: 100% !important;
        }
        
        .app-modal.image-modal .app-modal-content-area {
            height: 80vh !important;
            border-radius: 8px !important;
            padding: 0 !important;
            overflow: hidden !important;
        }
        
        @media (max-width: 768px) {
            .app-modal.image-modal .app-modal-body {
                margin: 2vh auto !important;
                width: 95vw !important;
                height: 85vh !important;
            }
            
            .app-modal.image-modal .app-modal-content-area {
                height: 85vh !important;
            }
        }
    </style>
<?php
} else if ($is_viewable_video_file || (isset($is_iframe_preview_available) && $is_iframe_preview_available)) {
    //show with default iframe
?>

    <iframe id="iframe-file-viewer" src="<?php echo $file_url ?>" style="width: 100%; border: 0; height: 100%; background:#fff;"></iframe>

    <script type="text/javascript">
        $(document).ready(function() {
            $("#iframe-file-viewer").closest("div.app-modal-content-area").css({
                "height": "100%",
                display: "table",
                width: "100%"
            });
        });
    </script>
<?php
} else if (!get_setting("disable_google_preview") && !is_localhost() && $is_google_preview_available) {
    //show some files using the google drive viewer
    //don't show in localhost
    //don't show if the google preive is disabled from config

    $src_url = "https://drive.google.com/viewerng/viewer?url=$file_url&pid=explorer&efh=false&a=v&chrome=false&embedded=true&usp=sharing";
?>
    <iframe id='google-file-viewer' src="<?php echo $src_url; ?>" style="width: 100%; height:100%; margin: 0; border: 0;"></iframe>
    <script type="text/javascript">
        $(document).ready(function() {
            $(".app-modal-content-area").css({
                "width": "100%"
            });
            $(".app-modal-content-area #google-file-viewer").css({
                height: $(window).height() + "px"
            });
        });
    </script>

<?php
} else {
    //Preview is not avaialble. 
    echo "<div class='text-white'>" . app_lang("file_preview_is_not_available") . "<br />";
    echo anchor($file_url, app_lang("download")) . "</div>";
}
?>