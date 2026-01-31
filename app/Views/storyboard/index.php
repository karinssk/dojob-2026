    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Clean Storyboard Layout -->
    <div class="bg-gray-50 min-h-screen" data-project-id="<?php echo $project_id; ?>" data-sub-project-id="<?php echo $sub_project_id ?: ''; ?>">
        <div class="container-fluid px-4 py-6">
            
            <!-- Header Section -->
            <div class="mb-6">
                <?php echo view('storyboard/partials/header', [
                    'project_info' => $project_info,
                    'project_id' => $project_id,
                    'sub_project_id' => $sub_project_id,
                    'sub_project_info' => $sub_project_info
                ]); ?>
            </div>

            <!-- Statistics Section -->
            <div class="mb-6">
                <?php echo view('storyboard/partials/statistics', [
                    'statistics' => $statistics
                ]); ?>
            </div>

            <!-- Main Content Section -->
            <div class="bg-white rounded-lg shadow-sm border">
                <?php echo view('storyboard/partials/scene_content', [
                    'scene_headings' => $scene_headings,
                    'storyboards_by_heading' => $storyboards_by_heading,
                    'storyboards_without_heading' => $storyboards_without_heading,
                    'project_id' => $project_id,
                    'sub_project_id' => $sub_project_id
                ]); ?>
            </div>

        </div>
    </div>

    <?php echo view('storyboard/partials/modals', [
        'project_info' => $project_info,
        'project_id' => $project_id
    ]); ?>

    <!-- Note: Export functionality now uses SweetAlert instead of modal -->

    <!-- Include JavaScript files -->
    <script src="<?php echo base_url('assets/js/storyboard.js?v=' . time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/storyboard_reorder.js?v=' . time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/storyboard-mobile.js?v=' . time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/modal-scroll-manager.js?v=' . time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/storyboard_columns.js?v=' . time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/enhanced-column-manager.js?v=' . time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/mobile-storyboard-view.js?v=' . time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/enhanced-video-manager.js?v=' . time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/image-editor.js?v=' . time()); ?>"></script>

    <!-- Tailwind Custom Styles -->
    <style>
    /* Protect sidebar from Tailwind conflicts */
    .sidebar-menu li a {
        display: flex !important;
        align-items: center !important;
        text-decoration: none !important;
    }

    .sidebar-menu li a .icon {
        flex-shrink: 0 !important;
        display: inline-block !important;
        width: auto !important;
        height: auto !important;
        margin-right: 10px !important;
        margin-left: 0 !important;
    }

    .sidebar-menu li a .menu-text {
        flex: 1 !important;
        display: inline-block !important;
        margin-left: 0 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .sidebar-menu li a span {
        margin-left: 0 !important;
    }

    /* Ensure Feather icons work properly in sidebar */
    .sidebar-menu li a i[data-feather] {
        width: 16px !important;
        height: 16px !important;
        display: inline-block !important;
        vertical-align: middle !important;
        margin-right: 10px !important;
        margin-left: 0 !important;
        flex-shrink: 0 !important;
    }

    /* Sub-menu items */
    .sidebar-menu li ul li a {
        display: flex !important;
        align-items: center !important;
    }

    .sidebar-menu li ul li a i[data-feather] {
        width: 14px !important;
        height: 14px !important;
        margin-right: 8px !important;
        margin-left: 0 !important;
    }

    /* Badge positioning */
    .sidebar-menu .badge {
        margin-left: auto !important;
        flex-shrink: 0 !important;
    }

    /* Only essential custom styles that Tailwind can't handle */
    @layer utilities {
        /* Responsive table behavior - fit to screen */
        .storyboard-table-container {
            @apply w-full;
            max-width: 100vw;
        }
        
        .storyboard-table {
            @apply w-full table-fixed;
            min-width: 100%;
        }
        
        /* Column width adjustments for better fit */
        .storyboard-table th:nth-child(1), /* Shot # */
        .storyboard-table td:nth-child(1) {
            width: 5%;
            min-width: 60px;
        }
        
        .storyboard-table th:nth-child(2), /* Frame */
        .storyboard-table td:nth-child(2) {
            width: 12% !important;
            min-width: 180px !important;
        }
        
        .storyboard-table th:nth-child(3), /* Shot Size */
        .storyboard-table th:nth-child(4), /* Shot Type */
        .storyboard-table th:nth-child(5), /* Movement */
        .storyboard-table td:nth-child(3),
        .storyboard-table td:nth-child(4),
        .storyboard-table td:nth-child(5) {
            width: 8%;
            min-width: 100px;
        }
        
        .storyboard-table th:nth-child(6), /* Duration */
        .storyboard-table td:nth-child(6) {
            width: 6%;
            min-width: 80px;
        }
        
        .storyboard-table th:nth-child(7), /* Content */
        .storyboard-table th:nth-child(8), /* Dialogues */
        .storyboard-table td:nth-child(7),
        .storyboard-table td:nth-child(8) {
            width: 12%;
            min-width: 120px;
        }
        
        .storyboard-table th:nth-child(9), /* Sound - HIDDEN */
        .storyboard-table th:nth-child(10), /* Equipment - HIDDEN */
        .storyboard-table td:nth-child(9),
        .storyboard-table td:nth-child(10) {
            display: none !important;
        }
        
        .storyboard-table th:nth-child(11), /* Frame Rate */
        .storyboard-table td:nth-child(11) {
            width: 6%;
            min-width: 80px;
        }
        
        .storyboard-table th:nth-child(12), /* Lighting - HIDDEN */
        .storyboard-table td:nth-child(12) {
            display: none !important;
        }
        
        .storyboard-table th:nth-child(13), /* Note */
        .storyboard-table td:nth-child(13) {
            width: 8%;
            min-width: 80px;
        }
        
        .storyboard-table th:nth-child(14), /* Raw Footage */
        .storyboard-table td:nth-child(14) {
            width: 6%;
            min-width: 70px;
        }
        
        .storyboard-table th:nth-child(15), /* Status */
        .storyboard-table td:nth-child(15) {
            width: 10%;
            min-width: 100px;
        }
        
        .storyboard-table th:nth-child(16), /* Actions */
        .storyboard-table td:nth-child(16) {
            width: 6%;
            min-width: 80px !important;
            text-align: center !important;
        }
        
        /* Ensure Actions column content is centered and visible */
        .storyboard-table td:nth-child(16) .flex {
            justify-content: center !important;
        }
        
        /* Text truncation for better fit */
        .storyboard-table td .editable-content {
            @apply block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Override truncate for content, dialogues, and notes to allow multiline */
        .storyboard-table td:nth-child(7) .editable-content, /* Content */
        .storyboard-table td:nth-child(8) .editable-content, /* Dialogues */
        .storyboard-table td:nth-child(13) .editable-content, /* Note */
        .storyboard-table td:nth-child(7) p, /* Content p tag */
        .storyboard-table td:nth-child(8) p, /* Dialogues p tag */
        .storyboard-table td:nth-child(13) span /* Note span tag */ {
            white-space: normal !important;
            text-overflow: initial !important;
            overflow: visible !important;
            text-align: left !important;
            vertical-align: top !important;
            justify-content: flex-start !important;
            align-items: flex-start !important;
        }
        
        /* Override Tailwind's truncate class for these specific columns */
        .storyboard-table td:nth-child(7) .truncate,
        .storyboard-table td:nth-child(8) .truncate,
        .storyboard-table td:nth-child(13) .truncate {
            text-align: left !important;
            justify-content: flex-start !important;
        }
        
        /* Ensure all columns are visible */
        .storyboard-table {
            table-layout: fixed !important;
            width: 100% !important;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        /* Debug: Show table boundaries */
        .storyboard-table-container {
            @apply w-full;
            max-width: none !important;
            overflow-x: visible !important;
        }
        
        /* Ensure Actions column is never hidden */
        .storyboard-table th:last-child,
        .storyboard-table td:last-child {
            background: rgba(249, 250, 251, 0.9) !important;
            font-weight: 600 !important;
            border-left: 2px solid #e5e7eb !important;
        }
        
        /* Smaller padding on smaller screens */
        @media (max-width: 1400px) {
            .storyboard-table th,
            .storyboard-table td {
                @apply px-2 py-2 text-xs;
            }
            
            /* Adjust column widths for smaller screens */
            .storyboard-table th:nth-child(7), /* Content */
            .storyboard-table th:nth-child(8), /* Dialogues */
            .storyboard-table td:nth-child(7),
            .storyboard-table td:nth-child(8) {
                width: 10%;
                min-width: 100px;
            }
        }

        @media (max-width: 768px) {
            .storyboard-table-container {
                @apply hidden !important;
            }
            
            .mobile-storyboard-cards {
                @apply block !important;
            }
            
            /* Force mobile layout */
            .scene-heading-section .bg-white.rounded-lg.shadow-sm.border {
                @apply hidden !important;
            }
            
            /* Mobile-friendly buttons */
            .scene-heading-header button {
                @apply text-xs px-2 py-1 !important;
            }
            
            /* Mobile scene headings */
            .scene-heading-header {
                @apply text-sm !important;
            }
            
            .scene-heading-header h6 {
                @apply text-sm font-medium !important;
            }
            
            /* Mobile navigation improvements */
            .container-fluid {
                @apply px-2 py-4 !important;
            }
            
            /* Mobile-specific storyboard cards */
            body.mobile-storyboard-view .storyboard-table-container {
                @apply hidden !important;
            }
            
            body.mobile-storyboard-view .mobile-scene-container {
                @apply block !important;
            }
        }

        /* Editable cell states - improved with proper Tailwind classes */
        .editable-cell.editing {
            @apply bg-amber-100 border-2 border-amber-500 rounded-lg shadow-sm;
        }

        /* Remove any image sizing conflicts */
        .image-container img {
            max-width: none !important; /* Override any global img max-width */
            height: auto !important;
            @apply object-cover rounded-lg shadow-sm hover:shadow-md transition-all duration-200;
        }

        /* Table cell improvements */
        .storyboard-table td[data-field="content"],
        .storyboard-table td[data-field="dialogues"] {
            height: auto !important;
            min-height: auto !important;
        }

        .storyboard-table tbody tr {
            height: auto !important;
        }

        .storyboard-table tbody td {
            @apply align-top;
        }
        
        /* Specific top alignment for content, dialogues, and notes columns */
        .storyboard-table td:nth-child(7), /* Content */
        .storyboard-table td:nth-child(8), /* Dialogues */
        .storyboard-table td:nth-child(13) /* Note */ {
            @apply align-top;
            vertical-align: top !important;
            text-align: left !important;
        }
        
        /* Ensure text within these cells is also top-aligned - both before and after inline edit */
        .storyboard-table td:nth-child(7) .editable-content,
        .storyboard-table td:nth-child(8) .editable-content,
        .storyboard-table td:nth-child(13) .editable-content,
        .storyboard-table td:nth-child(7) .inline-editable,
        .storyboard-table td:nth-child(8) .inline-editable,
        .storyboard-table td:nth-child(13) .inline-editable,
        .storyboard-table td:nth-child(7) .editable-cell,
        .storyboard-table td:nth-child(8) .editable-cell,
        .storyboard-table td:nth-child(13) .editable-cell {
            @apply text-left;
            vertical-align: top !important;
            text-align: left !important;
            display: block !important;
            line-height: 1.4;
            justify-content: flex-start !important;
            align-items: flex-start !important;
        }
        
        /* Force left alignment on ALL child elements in these columns */
        .storyboard-table td:nth-child(7) *,
        .storyboard-table td:nth-child(8) *,
        .storyboard-table td:nth-child(13) * {
            text-align: left !important;
            justify-content: flex-start !important;
        }

        /* Inline edit container styling with Tailwind */
        .inline-edit-container {
            @apply space-y-2;
        }

        .inline-edit-input {
            @apply w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200;
        }

        .inline-edit-buttons {
            @apply flex gap-1 mt-2;
        }

        /* Button improvements */
        .save-inline-edit {
            @apply bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs font-medium transition-colors duration-200;
        }

        .cancel-inline-edit {
            @apply bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded text-xs font-medium transition-colors duration-200;
        }

        /* Fix any bootstrap conflicts */
        .btn-success.save-inline-edit {
            @apply bg-green-500 hover:bg-green-600 border-green-500 hover:border-green-600;
        }

        .btn-secondary.cancel-inline-edit {
            @apply bg-gray-500 hover:bg-gray-600 border-gray-500 hover:border-gray-600;
        }
        
        /* Custom Loading Spinner */
        .loader {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: inline-block;
            border-top: 4px solid #FFF;
            border-right: 4px solid transparent;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
            position: relative;
        }
        
        .loader::after {
            content: '';  
            box-sizing: border-box;
            position: absolute;
            left: 0;
            top: 0;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border-left: 4px solid #FF3D00;
            border-bottom: 4px solid transparent;
            animation: rotation 0.5s linear infinite reverse;
        }
        
        /* Smaller version for inline buttons */
        .loader-sm {
            width: 16px;
            height: 16px;
            border-top: 2px solid #FFF;
            border-right: 2px solid transparent;
        }
        
        .loader-sm::after {
            width: 16px;
            height: 16px;
            border-left: 2px solid #FF3D00;
            border-bottom: 2px solid transparent;
        }
        
        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    }


    .sidebar .fa-spin,
        .sidebar-menu .fa-spin,
        .sidebar .icon.fa-spin {
            animation: fa-spin 1s infinite linear !important;
        }
    </style>

    <!-- Storyboard JavaScript -->
    <script>
    $(document).ready(function() {
        // Initialize stats visibility on page load
        initializeStatsVisibility();
        
        // Initialize mobile view if needed
        if (typeof initializeMobileStoryboardView === 'function') {
            initializeMobileStoryboardView();
        }
        
        // The inline editing functionality is handled by storyboard.js
        // Just ensure field options are loaded from database
        console.log('Inline editing is handled by storyboard.js');
        
        // Utility function to show toast messages (used by other functions)
        window.showToast = function(type, message) {
            if (typeof Swal !== 'undefined') {
                const iconType = type === 'success' ? 'success' : 
                            type === 'warning' ? 'warning' : 
                            type === 'info' ? 'info' : 'error';
                Swal.fire({
                    icon: iconType,
                    title: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: type === 'warning' ? 3000 : 2000,
                    timerProgressBar: true
                });
            } else {
                alert(message);
            }
        };
        
        // Handle edit project form submission
        $('#edit-project-form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: get_uri("storyboard/update_project"),
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#edit-project-modal').modal('hide');
                        showToast('success', 'Project updated successfully!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    showToast('error', 'Error updating project: ' + error);
                }
            });
        });

        // Delete confirmation
        $(document).on('click', '.delete', function() {
            let id = $(this).data('id');
            if (confirm('Are you sure you want to delete this storyboard scene?')) {
                $.ajax({
                    url: $(this).data('action-url'),
                    type: 'POST',
                    dataType: 'json',
                    data: {id: id},
                    success: function(response) {
                        if (response.success) {
                            $('tr[data-id="' + id + '"]').fadeOut(300, function() {
                                $(this).remove();
                            });
                            showToast('success', response.message);
                        } else {
                            showToast('error', response.message);
                        }
                    }
                });
            }
        });

        // Reorder functionality is now handled by storyboard_reorder.js
        
        // Image Editor Integration
        window.editStoryboardImage = function(storyboardId, currentImageSrc) {
            console.log('=== EDIT STORYBOARD IMAGE ===');
            console.log('Storyboard ID:', storyboardId);
            console.log('Current image src:', currentImageSrc);
            
            if (!storyboardId) {
                showToast('error', 'Invalid storyboard ID');
                return;
            }
            
            if (!currentImageSrc || currentImageSrc === '' || currentImageSrc.includes('placeholder')) {
                showToast('warning', 'No image to edit. Please upload an image first.');
                return;
            }
            
            // Open the image editor with callback to handle saving
            window.openImageEditor(currentImageSrc, function(editedImageBlob) {
                updateStoryboardImage(storyboardId, editedImageBlob);
            });
        };
        
        // Function to update storyboard image after editing
        function updateStoryboardImage(storyboardId, imageBlob) {
            console.log('=== UPDATE STORYBOARD IMAGE ===');
            console.log('Storyboard ID:', storyboardId);
            console.log('Image blob size:', imageBlob.size);
            
            const formData = new FormData();
            formData.append('id', storyboardId);
            formData.append('edited_image', imageBlob, 'edited_frame.jpg');
            
            // Show loading state with custom loader
            showToast('info', '<span class="loader loader-sm"></span> Saving edited image...');
            
            $.ajax({
                url: get_uri('storyboard/update_image'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log('Update image response:', response);
                    
                    if (response.success) {
                        showToast('success', 'Image updated successfully!');
                        
                        // Update the image in the storyboard table
                        const $imageCell = $(`tr[data-id="${storyboardId}"] .frame-image img`);
                        if ($imageCell.length) {
                            // Add timestamp to force browser to reload image
                            const newSrc = response.image_url + '?t=' + Date.now();
                            $imageCell.attr('src', newSrc);
                            console.log('Updated image src:', newSrc);
                        }
                        
                        // If we're in a modal, update the modal image too
                        const $modalImage = $('#storyboard-modal img[src*="' + storyboardId + '"]');
                        if ($modalImage.length) {
                            const newModalSrc = response.image_url + '?t=' + Date.now();
                            $modalImage.attr('src', newModalSrc);
                        }
                        
                    } else {
                        showToast('error', response.message || 'Failed to update image');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Update image error:', error);
                    console.error('Response:', xhr.responseText);
                    showToast('error', 'Network error: ' + error);
                }
            });
        }
        
        // Add image editor button click handler
        $(document).on('click', '.edit-image-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const storyboardId = $(this).data('storyboard-id');
            const $imageContainer = $(this).closest('.image-container, .frame-cell');
            const $img = $imageContainer.find('img');
            
            console.log('=== EDIT IMAGE BUTTON CLICKED ===');
            console.log('Storyboard ID:', storyboardId);
            console.log('Found image:', $img.length > 0);
            
            if ($img.length > 0) {
                const imageSrc = $img.attr('src');
                console.log('Image src:', imageSrc);
                editStoryboardImage(storyboardId, imageSrc);
            } else {
                showToast('error', 'No image found to edit');
            }
        });
        
        // Auto-select heading in storyboard modal
        window.autoSelectHeading = function(headingId) {
            if (headingId && headingId !== 'null' && headingId !== null) {
                console.log('Auto-selecting heading ID:', headingId);
                
                // Wait for modal content to load, then select the heading
                setTimeout(function() {
                    const $headingSelect = $('#scene_heading_id, [name="scene_heading_id"]');
                    if ($headingSelect.length > 0) {
                        $headingSelect.val(headingId).trigger('change');
                        console.log('Heading auto-selected:', headingId);
                        
                        // If it's a select2 dropdown, update it
                        if ($headingSelect.hasClass('select2-hidden-accessible')) {
                            $headingSelect.select2('val', headingId);
                        }
                        
                        // Visual feedback
                        $headingSelect.addClass('border-blue-500 bg-blue-50');
                        setTimeout(function() {
                            $headingSelect.removeClass('border-blue-500 bg-blue-50');
                        }, 2000);
                    } else {
                        console.log('Heading select field not found, retrying...');
                        // Retry after a longer delay if form isn't loaded yet
                        setTimeout(function() {
                            const $retrySelect = $('#scene_heading_id, [name="scene_heading_id"]');
                            if ($retrySelect.length > 0) {
                                $retrySelect.val(headingId).trigger('change');
                                if ($retrySelect.hasClass('select2-hidden-accessible')) {
                                    $retrySelect.select2('val', headingId);
                                }
                            }
                        }, 1000);
                    }
                }, 500);
            }
        };
        
        // Enhanced loadStoryboardModal function with auto-select
        window.originalLoadStoryboardModal = window.loadStoryboardModal;
        window.loadStoryboardModal = function(projectId, storyboardId, subProjectId, headingId) {
            // Call the original function
            if (window.originalLoadStoryboardModal) {
                window.originalLoadStoryboardModal(projectId, storyboardId, subProjectId, headingId);
            }
            
            // If headingId is provided, auto-select it
            if (headingId && headingId !== 'null' && headingId !== null) {
                window.autoSelectHeading(headingId);
            }
        };
        
        // Mobile view detection and activation
        function detectAndActivateMobileView() {
            const isMobile = window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            if (isMobile) {
                console.log('Mobile device detected, activating mobile view...');
                $('body').addClass('mobile-storyboard-view');
                
                // Hide desktop tables and show mobile cards
                $('.storyboard-table-container').hide();
                
                // Create mobile cards if they don't exist
                setTimeout(() => {
                    createMobileCards();
                }, 500);
            }
        }
        
        // Create mobile-friendly cards
        function createMobileCards() {
            console.log('Creating mobile cards...');
            
            // Find all storyboard rows
            $('.storyboard-table tbody tr').each(function() {
                const $row = $(this);
                const storyboardId = $row.data('id');
                
                if (!storyboardId) return;
                
                // Extract data from table cells
                const shotNum = $row.find('td:nth-child(1)').text().trim();
                const frameImg = $row.find('td:nth-child(2) img').attr('src') || '';
                const shotSize = $row.find('td:nth-child(3)').text().trim();
                const shotType = $row.find('td:nth-child(4)').text().trim();
                const movement = $row.find('td:nth-child(5)').text().trim();
                const duration = $row.find('td:nth-child(6)').text().trim();
                const content = $row.find('td:nth-child(7)').text().trim();
                const dialogues = $row.find('td:nth-child(8)').text().trim();
                const fps = $row.find('td:nth-child(11)').text().trim();
                const note = $row.find('td:nth-child(13)').text().trim();
                const status = $row.find('td:nth-child(15)').text().trim();
                
                // Create mobile card
                const mobileCard = `
                    <div class="mobile-storyboard-card bg-white rounded-lg shadow-sm border mb-4 p-4" data-id="${storyboardId}">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-bold text-white">${shotNum}</span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-sm font-medium text-gray-900">Scene ${shotNum}</h3>
                                    <span class="inline-block px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">${status}</span>
                                </div>
                                
                                ${frameImg ? `<div class="mb-3"><img src="${frameImg}" alt="Frame ${shotNum}" class="w-full h-32 object-cover rounded-lg border"></div>` : ''}
                                
                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                                    <div><span class="font-medium">Size:</span> ${shotSize}</div>
                                    <div><span class="font-medium">Type:</span> ${shotType}</div>
                                    <div><span class="font-medium">Movement:</span> ${movement}</div>
                                    <div><span class="font-medium">Duration:</span> ${duration}</div>
                                    <div><span class="font-medium">FPS:</span> ${fps}</div>
                                </div>
                                
                                ${content !== 'No content' ? `<div class="mb-2"><span class="font-medium text-xs text-gray-700">Content:</span><p class="text-sm text-gray-900 mt-1">${content}</p></div>` : ''}
                                
                                ${dialogues !== 'No dialogues' ? `<div class="mb-2"><span class="font-medium text-xs text-gray-700">Dialogues:</span><p class="text-sm text-gray-900 mt-1">${dialogues}</p></div>` : ''}
                                
                                ${note !== 'Not set' ? `<div class="mb-2"><span class="font-medium text-xs text-gray-700">Note:</span><p class="text-sm text-gray-600 mt-1">${note}</p></div>` : ''}
                                
                                <div class="flex justify-end space-x-2 mt-3 pt-3 border-t">
                                    <button class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100" onclick="loadStoryboardModal(<?php echo $project_id; ?>, ${storyboardId}, <?php echo $sub_project_id ?: 'null'; ?>)">
                                        <i data-feather="edit" class="w-3 h-3 mr-1"></i>Edit
                                    </button>
                                    <button class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded hover:bg-red-100" onclick="deleteStoryboard(${storyboardId})">
                                        <i data-feather="trash-2" class="w-3 h-3 mr-1"></i>Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Find the heading container or create one
                const headingId = $row.closest('[data-heading-id]').data('heading-id') || 'unorganized';
                let $mobileContainer = $(`.mobile-scene-container[data-heading-id="${headingId}"]`);
                
                if ($mobileContainer.length === 0) {
                    const headingTitle = headingId === 'unorganized' ? 'Unorganized Scenes' : 
                        $(`.scene-heading-section[data-heading-id="${headingId}"] h6`).text() || `Heading ${headingId}`;
                    
                    $mobileContainer = $(`
                        <div class="mobile-scene-container" data-heading-id="${headingId}">
                            <div class="mb-4">
                                <h4 class="text-lg font-semibold text-gray-800 mb-2">${headingTitle}</h4>
                                <div class="mobile-cards-wrapper"></div>
                            </div>
                        </div>
                    `);
                    
                    $row.closest('.scene-heading-section, .storyboard-table-container').after($mobileContainer);
                }
                
                $mobileContainer.find('.mobile-cards-wrapper').append(mobileCard);
            });
            
            // Initialize Feather icons for mobile cards
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
        
        // Run mobile detection on load and resize
        detectAndActivateMobileView();
        $(window).on('resize', debounce(detectAndActivateMobileView, 250));
        
        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Helper function to get URI (if not already defined)
        if (typeof get_uri === 'undefined') {
            window.get_uri = function(path) {
                return window.location.origin + '/index.php/' + path;
            };
        }
        
        // Debug function to test inline editing from storyboard.js
        window.debugInlineEdit = function() {
            console.log('=== INLINE EDIT DEBUG ===');
            console.log('Editable elements found:', $('.editable-cell').length);
            console.log('Current editing element (storyboard.js):', window.currentlyEditing);
            console.log('Field options data:', window.fieldOptionsData);
            console.log('Body has active edit class:', $('body').hasClass('has-active-edit'));
            
            $('.editable-cell').each(function(index) {
                console.log(`Element ${index}:`, {
                    field: $(this).data('field'),
                    id: $(this).data('id'),
                    text: $(this).text().substring(0, 50) + ($(this).text().length > 50 ? '...' : ''),
                    classes: $(this).attr('class'),
                    isEditing: $(this).hasClass('editing')
                });
            });
        };
        
        // Test the inline editing functionality
        console.log('Inline edit functionality is handled by storyboard.js');
        console.log('Found', $('.editable-cell').length, 'editable elements');
        console.log('Test with: debugInlineEdit() or click any editable cell');
        
        // Initialize inline export functionality
        initializeInlineExport();
    });

    // Inline Export Functionality
    function initializeInlineExport() {
        console.log('Initializing inline export functionality...');
        
        // Verify dependencies
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 is not loaded! Export functionality may not work.');
            return;
        }
        
        if (typeof $ === 'undefined') {
            console.error('jQuery is not loaded! Export functionality may not work.');
            return;
        }
        
        console.log(' All dependencies loaded successfully');
        console.log('🚀 Inline export functionality ready');
        
        // Add global error handler for export functions
        window.exportErrorHandler = function(error) {
            console.error('Export Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Export Error',
                text: 'An unexpected error occurred during export: ' + error.message,
                footer: 'Please refresh the page and try again.'
            });
        };
    }

    // Function to get the correct API base URL
    function getApiBaseUrl() {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            return 'http://localhost:3001';
        } else {
            return 'https://api-dojob.rubyshop.co.th';
        }
    }

    // Open the inline export confirmation
    function openInlineExportModal() {
        // Declare variables outside try block so they're available in .then() callback
        let projectId;
        let subProjectId;
        
        try {
            console.log('Opening inline export confirmation...');
            
            projectId = $('[data-project-id]').data('project-id');
            subProjectId = $('[data-sub-project-id]').data('sub-project-id');
            
            console.log('Export parameters:', { projectId, subProjectId });
            console.log('Project ID from data attribute:', $('[data-project-id]'));
            console.log('Sub-project ID from data attribute:', $('[data-sub-project-id]'));
            
            if (!projectId) {
                // Try alternative methods to get project ID
                const urlParams = new URLSearchParams(window.location.search);
                const urlProjectId = urlParams.get('project_id');
                const urlSubProjectId = urlParams.get('sub_project_id') || urlParams.get('selected_sub_project_id');
                
                console.log('Fallback - URL project ID:', urlProjectId);
                console.log('Fallback - URL sub-project ID:', urlSubProjectId);
                
                if (urlProjectId) {
                    // Use URL parameter as fallback
                    projectId = urlProjectId;
                    subProjectId = urlSubProjectId;
                    console.log('Using fallback project ID:', projectId);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: `
                            <div class="text-left">
                                <p>Project ID not found. Please refresh the page and try again.</p>
                                <details class="mt-2">
                                    <summary class="text-sm text-muted cursor-pointer">Debug Info</summary>
                                    <pre class="text-xs mt-1 p-2 bg-light rounded">
Current URL: ${window.location.href}
Data attributes: project-id=${$('[data-project-id]').data('project-id')}, sub-project-id=${$('[data-sub-project-id]').data('sub-project-id')}
URL parameters: ${window.location.search}
                                    </pre>
                                </details>
                            </div>
                        `
                    });
                    return;
                }
            }
        } catch (error) {
            console.error('Error in openInlineExportModal:', error);
            if (window.exportErrorHandler) {
                window.exportErrorHandler(error);
            }
            return;
        }
        
        // Show confirmation dialog with SweetAlert
        const hasSubProject = subProjectId && subProjectId !== '' && subProjectId !== 'null';
        const projectTypeText = hasSubProject ? 'sub-project' : 'project';
        
        Swal.fire({
            title: 'Export Storyboard',
            html: `
                <div class="text-left">
                    <p class="mb-3"><i class="fas fa-info-circle text-info mr-2"></i>This will export <strong>all scenes</strong> from this storyboard ${projectTypeText}.</p>
                    <div class="bg-light p-3 rounded">
                        <h6 class="mb-2">Choose Export Format:</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="swal_export_format" id="swal_png" value="png" checked>
                            <label class="form-check-label d-flex align-items-start" for="swal_png">
                                <div>
                                    <i class="fas fa-images text-primary mr-2"></i>
                                    <strong>PNG Images</strong><br>
                                    <small class="text-muted">Individual PNG files for each scene (Recommended)</small>
                                </div>
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="swal_export_format" id="swal_png_exact" value="png-exact">
                            <label class="form-check-label d-flex align-items-start" for="swal_png_exact">
                                <div>
                                    <i class="fas fa-expand-arrows-alt text-success mr-2"></i>
                                    <strong>PNG Images (Original Size)</strong><br>
                                    <small class="text-muted">Preserve exact original image dimensions</small>
                                </div>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="swal_export_format" id="swal_pdf" value="pdf">
                            <label class="form-check-label d-flex align-items-start" for="swal_pdf">
                                <div>
                                    <i class="fas fa-file-pdf text-danger mr-2"></i>
                                    <strong>PDF Document</strong><br>
                                    <small class="text-muted">Single PDF file with all scenes</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-download"></i> Export Now',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            width: '500px',
            preConfirm: () => {
                const selectedFormat = document.querySelector('input[name="swal_export_format"]:checked');
                if (!selectedFormat) {
                    Swal.showValidationMessage('Please select an export format');
                    return false;
                }
                return selectedFormat.value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const exportFormat = result.value;
                console.log('User confirmed export with format:', exportFormat);
                startInlineExportWithFormat(exportFormat, projectId, subProjectId);
            }
        });
    }

    // Start the inline export process with specified format
    function startInlineExportWithFormat(exportFormat, passedProjectId = null, passedSubProjectId = null) {
        let projectId = passedProjectId || $('[data-project-id]').data('project-id');
        let subProjectId = passedSubProjectId || $('[data-sub-project-id]').data('sub-project-id');
        
        // Fallback to URL parameters if data attributes are not available
        if (!projectId) {
            const urlParams = new URLSearchParams(window.location.search);
            projectId = urlParams.get('project_id');
            subProjectId = urlParams.get('sub_project_id') || urlParams.get('selected_sub_project_id');
        }
        
        console.log('Starting inline export:', {
            projectId,
            subProjectId,
            exportFormat,
            includeAllScenes: true // Always include all scenes
        });
        
        // Debug: Check if we have the right project type
        console.log('🔍 Debug Info:');
        console.log('- Current URL:', window.location.href);
        console.log('- Project ID type:', typeof projectId, projectId);
        console.log('- Sub-project ID type:', typeof subProjectId, subProjectId);
        console.log('- Export format:', exportFormat);
        
        if (!projectId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Project ID not found'
            });
            return;
        }
        
        // Show progress with SweetAlert
        let progressSwal = Swal.fire({
            title: 'Exporting Storyboard',
            html: `
                <div class="text-center">
                    <div class="progress mb-3" style="height: 20px;">
                        <div id="swal-progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p id="swal-progress-text" class="mb-0">Preparing export data...</p>
                </div>
            `,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        const apiBaseUrl = getApiBaseUrl();
        
        // Determine the export endpoint
        let exportEndpoint;
        if (exportFormat === 'png') {
            exportEndpoint = '/api/storyboard/export-png';
        } else if (exportFormat === 'png-exact') {
            exportEndpoint = '/api/storyboard/export-png-exact';
        } else {
            exportEndpoint = '/api/storyboard/export';
        }
        
        // Prepare export data - automatically include all scenes
        // Important: For sub-projects, we need to send BOTH project_id and sub_project_id
        // The API will look for storyboards that match both IDs
        const exportData = {
            project_id: parseInt(projectId), // Always send the parent project ID
            sub_project_id: subProjectId ? parseInt(subProjectId) : null, // Add sub-project ID if exists
            export_format: exportFormat,
            include_all_scenes: true, // Always true - no selection needed
            selected_scenes: [], // Empty array means all scenes
            scene_heading_title: "All Scenes",
            image_width: 800,  // Max width: 800px (will maintain aspect ratio)
            image_height: 800, // Max height: 800px (will maintain aspect ratio)
            quality: 90,
            preserve_aspect_ratio: true, // Preserve aspect ratio, no cropping
            fit_mode: 'contain', // Fit entire image within dimensions (no crop)
            preserve_image_size: exportFormat === 'png-exact'
        };
        
        console.log('🎯 Export data structure:');
        console.log('- Parent project ID:', projectId);
        console.log('- Sub-project ID:', subProjectId);
        console.log('- Full export data:', exportData);
        
        updateSwalProgress(30, 'Connecting to export API...');
        
        console.log('Export API URL:', apiBaseUrl + exportEndpoint);
        console.log('Export data being sent:', exportData);
        
        // Test API connectivity first, then proceed with export
        $.ajax({
            url: apiBaseUrl + '/api/health',
            type: 'GET',
            timeout: 5000,
            success: function(healthResponse) {
                console.log(' API server is accessible:', healthResponse);
                updateSwalProgress(40, 'API server connected. Processing export...');
                proceedWithExport();
            },
            error: function(xhr, status, error) {
                console.warn('⚠️ API health check failed, but proceeding with export:', { xhr, status, error });
                updateSwalProgress(40, 'Proceeding with export...');
                proceedWithExport();
            }
        });
        
        // Function to proceed with the actual export
        function proceedWithExport() {
            // For PNG exports, we need to handle binary data differently
            // We'll use XMLHttpRequest directly to handle the blob response
            const xhr = new XMLHttpRequest();
            xhr.open('POST', apiBaseUrl + exportEndpoint, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-User-ID', '1');
            xhr.responseType = 'blob'; // Important: handle binary data
            
            // Track progress
            xhr.upload.addEventListener('progress', function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = (evt.loaded / evt.total) * 50 + 30; // 30-80%
                    updateSwalProgress(percentComplete, 'Processing export...');
                }
            });
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    console.log('Export successful, received blob');
                    console.log('Blob type:', xhr.response.type);
                    console.log('Blob size:', xhr.response.size);
                    updateSwalProgress(100, 'Export completed successfully!');
                    
                    // Get the blob response
                    const blob = xhr.response;
                    
                    // Check if it's actually a ZIP file
                    const contentType = xhr.getResponseHeader('Content-Type');
                    console.log('Content-Type:', contentType);
                    
                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    
                    // Set filename based on format and content type
                    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').substring(0, 19);
                    const projectName = '<?php echo addslashes($project_info->title ?? "storyboard"); ?>';
                    const subProjectName = '<?php echo addslashes($sub_project_info->title ?? ""); ?>';
                    
                    let filename;
                    let fileExtension = '.zip'; // Default to zip
                    
                    // Determine file extension from content type or export format
                    if (contentType && contentType.includes('image/png')) {
                        fileExtension = '.png';
                    } else if (contentType && contentType.includes('application/pdf')) {
                        fileExtension = '.pdf';
                    } else if (contentType && contentType.includes('application/zip')) {
                        fileExtension = '.zip';
                    }
                    
                    // Generate filename
                    if (subProjectName) {
                        filename = `${projectName}_${subProjectName}_${timestamp}${fileExtension}`;
                    } else {
                        filename = `${projectName}_${timestamp}${fileExtension}`;
                    }
                    
                    console.log('Download filename:', filename);
                    
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    
                    // Cleanup
                    setTimeout(() => {
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        
                        // Show success message with file info
                        const fileSize = formatFileSize(blob.size);
                        const fileTypeText = fileExtension === '.zip' ? 'ZIP archive containing PNG images' : 
                                           fileExtension === '.png' ? 'PNG image' : 
                                           fileExtension === '.pdf' ? 'PDF document' : 'Export file';
                        
                        Swal.fire({
                            title: 'Export Complete',
                            html: `
                                <div class="text-center">
                                    <i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                    <h4 class="text-success mb-3">Export Completed Successfully!</h4>
                                    <p class="mb-2">Your storyboard has been exported and the download should start automatically.</p>
                                    <div class="text-left mt-3 p-3 bg-light rounded">
                                        <p class="mb-1"><strong>File:</strong> <code>${filename}</code></p>
                                        <p class="mb-1"><strong>Type:</strong> ${fileTypeText}</p>
                                        <p class="mb-0"><strong>Size:</strong> ${fileSize}</p>
                                    </div>
                                    <p class="text-muted mt-3 small">
                                        ${fileExtension === '.zip' ? '💡 Extract the ZIP file to access individual PNG images' : ''}
                                    </p>
                                </div>
                            `,
                            icon: 'success',
                            confirmButtonText: 'Close',
                            confirmButtonColor: '#16a34a',
                            width: '600px'
                        });
                        
                        if (typeof showToast === 'function') {
                            showToast('success', 'Storyboard exported successfully!');
                        }
                    }, 1000);
                } else {
                    // Handle error
                    console.error('Export failed with status:', xhr.status);
                    
                    // Try to read error message from blob
                    const reader = new FileReader();
                    reader.onload = function() {
                        try {
                            const errorData = JSON.parse(reader.result);
                            handleSwalExportError({
                                status: xhr.status,
                                responseJSON: errorData,
                                responseText: reader.result
                            }, 'error', errorData.message || 'Unknown error');
                        } catch (e) {
                            handleSwalExportError({
                                status: xhr.status,
                                responseText: reader.result
                            }, 'error', 'Server error');
                        }
                    };
                    reader.readAsText(xhr.response);
                }
            };
            
            xhr.onerror = function() {
                console.error('Network error during export');
                handleSwalExportError({
                    status: 0,
                    responseText: 'Network error'
                }, 'error', 'Network error occurred');
            };
            
            xhr.ontimeout = function() {
                console.error('Export timed out');
                handleSwalExportError({
                    status: 0,
                    responseText: 'Timeout'
                }, 'timeout', 'Export timed out');
            };
            
            xhr.timeout = 300000; // 5 minutes
            xhr.send(JSON.stringify(exportData));
        } // Close proceedWithExport function
    }

    // Update export progress
    function updateExportProgress(percentage, message) {
        $('#export-progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
        $('#export-status-text').text(message);
    }

    // Update SweetAlert progress
    function updateSwalProgress(percentage, message) {
        $('#swal-progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
        $('#swal-progress-text').text(message);
    }

    // Legacy functions kept for compatibility (not used with SweetAlert approach)

    // Handle successful export with SweetAlert
    function handleSwalExportSuccess(response, exportFormat) {
        console.log('Handling SweetAlert export success:', response);
        
        let resultHtml = '<div class="text-center">';
        resultHtml += '<i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 1rem;"></i>';
        resultHtml += '<h4 class="text-success mb-3">Export Completed Successfully!</h4>';
        
        if (response.files && response.files.length > 0) {
            resultHtml += '<div class="text-left">';
            resultHtml += '<h6 class="font-weight-bold mb-2">Downloaded Files:</h6>';
            resultHtml += '<div class="list-group list-group-flush">';
            
            response.files.forEach((file, index) => {
                const fileName = file.filename || file.name || `exported_file_${index + 1}`;
                const fileSize = file.size ? formatFileSize(file.size) : '';
                
                resultHtml += '<div class="list-group-item d-flex justify-content-between align-items-center px-0">';
                resultHtml += '<div>';
                resultHtml += '<i class="fas fa-file mr-2 text-primary"></i>';
                resultHtml += fileName;
                if (fileSize) {
                    resultHtml += '<small class="text-muted ml-2">(' + fileSize + ')</small>';
                }
                resultHtml += '</div>';
                
                if (file.download_url || file.url) {
                    const downloadUrl = file.download_url || file.url;
                    resultHtml += '<a href="' + downloadUrl + '" class="btn btn-sm btn-outline-primary" download>';
                    resultHtml += '<i class="fas fa-download"></i>';
                    resultHtml += '</a>';
                }
                resultHtml += '</div>';
            });
            
            resultHtml += '</div></div>';
        } else if (response.download_url) {
            // Single file download
            resultHtml += '<div class="mt-3">';
            resultHtml += '<a href="' + response.download_url + '" class="btn btn-success btn-lg" download>';
            resultHtml += '<i class="fas fa-download mr-2"></i>';
            resultHtml += 'Download Export';
            resultHtml += '</a>';
            resultHtml += '</div>';
        }
        
        resultHtml += '</div>';
        
        Swal.fire({
            title: 'Export Complete',
            html: resultHtml,
            icon: 'success',
            confirmButtonText: 'Close',
            confirmButtonColor: '#16a34a',
            width: '600px',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            }
        });
        
        // Show toast notification
        if (typeof showToast === 'function') {
            showToast('success', 'Storyboard exported successfully!');
        }
    }

    // Handle export error with SweetAlert
    function handleSwalExportError(xhr, status, error) {
        console.error('SweetAlert export error details:', { xhr, status, error });
        console.error('Response status:', xhr.status);
        console.error('Response text:', xhr.responseText);
        console.error('Response JSON:', xhr.responseJSON);
        
        let errorMessage = 'An error occurred during export.';
        let detailedError = '';
        
        if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
            detailedError = xhr.responseJSON.error || '';
        } else if (xhr.responseText) {
            try {
                const responseData = JSON.parse(xhr.responseText);
                errorMessage = responseData.message || 'Server error occurred';
                detailedError = responseData.error || '';
            } catch (e) {
                errorMessage = 'Server returned invalid response: ' + xhr.status;
                detailedError = xhr.responseText.substring(0, 200);
            }
        } else if (status === 'timeout') {
            errorMessage = 'Export timed out. Please try again.';
        } else if (status === 'error') {
            errorMessage = 'Network error. Please check your connection.';
        } else if (xhr.status === 400) {
            errorMessage = 'Invalid request data. Please check the project parameters.';
        } else if (xhr.status === 404) {
            errorMessage = 'Project not found or API endpoint unavailable.';
        } else if (xhr.status === 500) {
            errorMessage = 'Server error occurred. Please try again later.';
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Export Failed',
            html: `
                <div class="text-left">
                    <p class="mb-2">${errorMessage}</p>
                    ${detailedError ? `<details class="mt-2"><summary class="text-sm text-muted cursor-pointer">Technical Details</summary><pre class="text-xs mt-1 p-2 bg-light rounded">${detailedError}</pre></details>` : ''}
                </div>
            `,
            confirmButtonColor: '#dc3545',
            footer: '<small class="text-muted">If the problem persists, please contact support.</small>'
        });
        
        // Show toast notification
        if (typeof showToast === 'function') {
            showToast('error', 'Export failed: ' + errorMessage);
        }
    }

    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    </script>


    <style>
    /* Storyboard CSS - Compatible with Tailwind CDN */

    /* Sortable and Interactive Elements */
    .sortable-enabled {
        cursor: move;
    }

    .sortable-ghost {
        opacity: 0.4;
        background-color: #f9fafb;
    }

    .sortable-chosen {
        background-color: #eff6ff;
    }

    .storyboard-row:hover {
        background-color: #f9fafb;
    }

    /* Image Hover Effects */
    .img-thumbnail {
        transition: transform 0.2s;
    }

    .img-thumbnail:hover {
        transform: scale(1.1);
    }

    /* Frame Column Specific Styles */
    .frame-column {
        width: 24rem;
        min-width: 24rem;
        vertical-align: top;
    }

    .frame-column img {
        width: 13rem;
        height: 13rem;
        object-fit: cover;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    /* Editable Cell States */
    .editable-cell {
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
    }

    .editable-cell:hover:not(.editing) {
        background-color: #fefce8;
    }

    .editable-cell.editing {
        background-color: #fef3c7;
        cursor: default;
        border: 2px solid #3b82f6;
        border-radius: 0.375rem;
    }

    .editable-cell.editing:hover {
        background-color: #fef3c7;
    }

    /* Inline Edit Components */
    .inline-edit-input {
        width: 100%;
        border: 2px solid #3b82f6;
        border-radius: 0.25rem;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        resize: vertical;
    }

    .inline-edit-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .inline-edit-buttons {
        display: flex;
        gap: 0.25rem;
        margin-top: 0.25rem;
    }

    .inline-edit-buttons .btn {
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }

    /* Status Cell Specific */
    .status-cell .badge {
        cursor: pointer;
        transition: transform 0.2s;
    }

    .status-cell:hover .badge {
        transform: scale(1.05);
    }

    /* Editable Cell Indicators */
    .editable-cell::after {
        content: '✎';
        position: absolute;
        top: 0.125rem;
        right: 0.25rem;
        font-size: 0.75rem;
        color: #6b7280;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .editable-cell:hover::after {
        opacity: 0.7;
    }

    .editable-cell.editing::after {
        display: none;
    }

    /* File Upload and Preview Styles */
    .existing-files-section {
        background-color: #f9fafb;
        border-radius: 0.25rem;
        padding: 0.75rem;
        margin-bottom: 1rem;
    }

    .existing-file-item, .file-preview-item {
        background-color: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.25rem;
        padding: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .file-info {
        display: flex;
        align-items: center;
        flex: 1;
    }

    .file-actions {
        display: flex;
        gap: 0.25rem;
    }

    /* Video Preview Styles */
    #video-preview-modal {
        z-index: 1060;
    }

    #video-preview-modal .modal-dialog {
        max-width: 56rem;
    }

    #video-preview-modal .video-container {
        background-color: black;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #video-preview-modal video {
        background-color: black;
        outline: none;
    }

    /* Utility Classes */
    .footage-files {
        max-width: 12rem;
    }

    .footage-file-item {
        margin-bottom: 0.25rem;
    }

    .preview-video-btn-index {
        background-color: transparent;
        border: 1px solid #3b82f6;
        color: #3b82f6;
        padding: 0.125rem 0.5rem;
        font-size: 0.75rem;
        text-align: left;
        border-radius: 0.25rem;
        transition: all 0.2s;
    }

    .preview-video-btn-index:hover {
        background-color: #3b82f6;
        border-color: #3b82f6;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
    }

    .preview-video-btn-index:focus {
        background-color: #3b82f6;
        border-color: #3b82f6;
        color: white;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
    }

    /* Single Edit Restriction */
    .has-active-edit .editable-cell:not(.editing) {
        pointer-events: none;
        opacity: 0.6;
        cursor: not-allowed;
    }

    .editable-cell.editing-highlight {
        animation: highlight-pulse 1s ease-in-out 2;
        border: 2px solid #fbbf24;
    }

    @keyframes highlight-pulse {
        0%, 100% { 
            background-color: transparent;
            transform: scale(1);
        }
        50% { 
            background-color: rgba(251, 191, 36, 0.2);
            transform: scale(1.02);
        }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .frame-column {
            min-width: 7.5rem;
        }
        
        .frame-column img {
            width: 5rem !important;
            height: 3.75rem !important;
        }
        
        /* Mobile Card View */
        .storyboard-cards {
            display: block;
        }
        
        .storyboard-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        
        .storyboard-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .storyboard-card-shot {
            font-size: 1.125rem;
            font-weight: 700;
            color: #374151;
        }
        
        .storyboard-card-status {
            font-size: 0.75rem;
        }
        
        .storyboard-card-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }
        
        .storyboard-card-field {
            display: flex;
            flex-direction: column;
        }
        
        .storyboard-card-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.125rem;
        }
        
        .storyboard-card-value {
            font-size: 0.875rem;
            color: #374151;
            font-weight: 500;
        }
        
        .storyboard-card-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            padding-top: 0.5rem;
            border-top: 1px solid #f3f4f6;
        }
        
        .storyboard-card-actions .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
    }

    /* Hide mobile cards on desktop */
    @media (min-width: 769px) {
        .storyboard-cards {
            display: none;
        }
    }

    /* Additional responsive fixes */
    @media (max-width: 1200px) {
        .storyboard-table {
            min-width: 1200px;
        }
    }















    </style>