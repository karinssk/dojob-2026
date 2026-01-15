<!-- Ensure proper UTF-8 encoding -->
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="page-title clearfix">
                    <h1>
                        <i data-feather="download" class="icon-16"></i>
                        Export Storyboard
                    </h1>
                </div>

                <div class="card-body">
                    <!-- Project Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="project_id" class="form-label">Project *</label>
                                <?php
                                echo form_dropdown("project_id", $projects_dropdown, $selected_project_id ?? "", "class='select2 form-control' id='project_id' data-rule-required='true' data-msg-required='This field is required'");
                                ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sub_project_id" class="form-label">Sub-Project</label>
                                <?php
                                $sub_project_options = array("" => "- All Sub-Projects -");
                                if (!empty($selected_sub_project_id)) {
                                    $sub_project_options[$selected_sub_project_id] = "Sub-Project " . $selected_sub_project_id;
                                }
                                echo form_dropdown("sub_project_id", $sub_project_options, $selected_sub_project_id ?? "", "class='select2 form-control' id='sub_project_id'");
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Load Storyboard Button -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" id="load-storyboard-btn">
                                <i data-feather="refresh-cw" class="icon-16 me-2"></i>
                                Load Storyboard Data
                            </button>
                            <small class="text-muted ms-2">Now powered by Node.js API for better performance</small>
                        </div>
                    </div>

                    <!-- Storyboard Selection Area -->
                    <div id="storyboard-selection-area" style="display: none;">
                        <!-- Selection Controls -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i data-feather="check-square" class="icon-16 me-2"></i>
                                    Selection Controls
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="select-all-headings">
                                                <i data-feather="check-square" class="icon-14 me-1"></i>
                                                Select All Headings
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="deselect-all-headings">
                                                <i data-feather="square" class="icon-14 me-1"></i>
                                                Deselect All
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-success btn-sm" id="select-all-scenes">
                                                <i data-feather="check-square" class="icon-14 me-1"></i>
                                                Select All Scenes
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="deselect-all-scenes">
                                                <i data-feather="square" class="icon-14 me-1"></i>
                                                Deselect All
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Storyboard Preview -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i data-feather="eye" class="icon-16 me-2"></i>
                                    Storyboard Preview
                                </h5>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm active" id="view-cards">
                                        <i data-feather="grid" class="icon-14 me-1"></i>Cards
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="view-list">
                                        <i data-feather="list" class="icon-14 me-1"></i>List
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="storyboard-preview" class="storyboard-preview-container">
                                    <!-- Preview will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <!-- Export Options -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i data-feather="settings" class="icon-16 me-2"></i>
                                    Export Options
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Export Format</label>
                                            <select class="form-control" id="export_format">
                                                <option value="png">📸 Professional PNG Storyboard</option>
                                                <option value="png-exact">🎯 PNG with 100% Exact Image Sizes</option>
                                                <option value="json">📄 JSON Data Format</option>
                                                <option value="csv">📊 CSV Data Format</option>
                                            </select>
                                            <small class="form-text text-muted">Choose the export format for your storyboard data</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Include in Export</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="include_images" checked>
                                                <label class="form-check-label" for="include_images">
                                                    Storyboard Images
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="include_descriptions" checked>
                                                <label class="form-check-label" for="include_descriptions">
                                                    Scene Descriptions
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="include_notes" checked>
                                                <label class="form-check-label" for="include_notes">
                                                    Notes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="include_camera_info" checked>
                                                <label class="form-check-label" for="include_camera_info">
                                                    Camera & Shot Information
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Actions -->
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="export-summary">
                                            <h6>Export Summary</h6>
                                            <p class="mb-1">Selected Headings: <span id="selected-headings-count">0</span></p>
                                            <p class="mb-1">Selected Scenes: <span id="selected-scenes-count">0</span></p>
                                            <p class="mb-0">Total Items: <span id="total-selected-count">0</span></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button type="button" class="btn btn-success btn-lg" id="export-btn" disabled>
                                            <i data-feather="download" class="icon-16 me-2"></i>
                                            Export Storyboard
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loading-indicator" style="display: none;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Loading storyboard data...</p>
                        </div>
                    </div>

                    <!-- Export Progress -->
                    <div id="export-progress" style="display: none;">
                        <div class="card">
                            <div class="card-body">
                                <h5>
                                    <i data-feather="download" class="icon-16 me-2"></i>
                                    Exporting Storyboard...
                                </h5>
                                <div class="progress mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                </div>
                                <p id="export-status">Preparing export...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Storyboard Preview Styles */
.storyboard-preview-container {
    min-height: 200px;
}

.storyboard-preview-card {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    min-height: 320px;
}

.storyboard-preview-card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.storyboard-preview-card.selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.card-header-blue {
    background: #f1f5f9;
    padding: 8px 10px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
}

.scene-title {
    font-weight: 600;
    color: #1e40af;
    font-size: 12px;
}

.card-image-container {
    padding: 0;
    text-align: center;
    background: #f8fafc;
    height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-bottom: 1px solid #e2e8f0;
}

.storyboard-preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.storyboard-preview-image:hover {
    transform: scale(1.02);
}

.storyboard-preview-placeholder {
    width: 120px;
    height: 90px;
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.placeholder-icon {
    width: 32px;
    height: 32px;
    margin-bottom: 8px;
    opacity: 0.5;
}

.placeholder-text {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card-details {
    padding: 12px 14px 14px 14px;
    background: white;
    flex: 1;
    min-height: 100px;
    display: flex;
    flex-direction: column;
}

.shot-info {
    display: grid;
    gap: 6px;
    flex: 1;
}

.detail-row {
    display: flex;
    font-size: 13px;
    line-height: 1.5;
    margin-bottom: 4px;
    align-items: flex-start;
}

.detail-row .label {
    font-weight: 700;
    color: #374151;
    min-width: 65px;
    margin-right: 8px;
    font-size: 12px;
    flex-shrink: 0;
}

.detail-row .value {
    color: #6b7280;
    flex: 1;
    font-size: 12px;
    line-height: 1.4;
    word-break: break-word;
}

.technical-details {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #e5e7eb;
    display: grid;
    gap: 2px;
}

.export-preview-empty {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.export-preview-empty i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

/* Grid Layout Responsive Styles */
.storyboard-cards-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    padding: 16px 0;
}

/* Responsive grid - adjust for different screen sizes */
@media (max-width: 1400px) {
    .storyboard-cards-view {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 16px;
    }
}

@media (max-width: 992px) {
    .storyboard-cards-view {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 14px;
    }
}

@media (max-width: 768px) {
    .storyboard-cards-view {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}

@media (max-width: 480px) {
    .storyboard-cards-view {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}

.detail-row .value {
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.storyboard-preview-image {
    max-width: 100%;
    max-height: 80px;
    object-fit: cover;
    border-radius: 3px;
}
</style>

<script type="text/javascript">
$(document).ready(function () {
    "use strict";

    // Initialize Select2
    $("#project_id, #sub_project_id").select2();

    // Load sub-projects when project changes
    $("#project_id").change(function () {
        var projectId = $(this).val();
        if (projectId) {
            loadSubProjects(projectId);
        } else {
            $("#sub_project_id").html('<option value="">-</option>').trigger('change');
        }
        // Hide storyboard selection area when project changes
        $("#storyboard-selection-area").hide();
    });

    // Load storyboard data
    $("#load-storyboard-btn").click(function () {
        loadStoryboardData();
    });
    
    // Debug: Log available projects and current selection, auto-correct to valid project
    $(document).ready(function() {
        console.log('🔍 DEBUG: Available projects in dropdown:');
        $("#project_id option").each(function() {
            console.log('  - ID:', $(this).val(), 'Title:', $(this).text());
        });
        console.log('🔍 DEBUG: Currently selected project ID:', $("#project_id").val());
        console.log('🔍 DEBUG: URL parameters:', window.location.search);
        
        // Auto-correct if current selection doesn't exist
        var currentSelection = $("#project_id").val();
        if (!currentSelection || currentSelection === '' || currentSelection === '156') {
            // Look for project 128 specifically
            if ($("#project_id option[value='128']").length > 0) {
                console.log('🔧 AUTO-CORRECTING: Setting project to 128 (actual existing project)');
                $("#project_id").val('128').trigger('change');
            } else {
                // Use the first available project
                var firstProject = $("#project_id option:not([value=''])").first().val();
                if (firstProject) {
                    console.log('🔧 AUTO-CORRECTING: Setting project to first available:', firstProject);
                    $("#project_id").val(firstProject).trigger('change');
                }
            }
        }
    });

    // Selection controls
    $("#select-all-headings").click(function () {
        $(".heading-checkbox").prop('checked', true).trigger('change');
    });

    $("#deselect-all-headings").click(function () {
        $(".heading-checkbox").prop('checked', false).trigger('change');
    });

    $("#select-all-scenes").click(function () {
        $(".scene-checkbox").prop('checked', true).trigger('change');
    });

    $("#deselect-all-scenes").click(function () {
        $(".scene-checkbox").prop('checked', false).trigger('change');
    });

    // Export button
    $("#export-btn").click(function () {
        exportStoryboard();
    });

    // Update selection counts when checkboxes change
    $(document).on('change', '.heading-checkbox, .scene-checkbox', function () {
        updateSelectionCounts();
    });

    // View toggle functionality
    $("#view-cards").click(function() {
        $(this).addClass('active');
        $("#view-list").removeClass('active');
        $(".storyboard-cards-view").addClass('active').show();
        $(".storyboard-list-view").removeClass('active').hide();
    });

    $("#view-list").click(function() {
        $(this).addClass('active');
        $("#view-cards").removeClass('active');
        $(".storyboard-list-view").addClass('active').show();
        $(".storyboard-cards-view").removeClass('active').hide();
    });

    function loadSubProjects(projectId) {
        $.ajax({
            url: "<?php echo base_url('index.php/export_storyboard/get_sub_projects'); ?>",
            type: 'POST',
            data: {project_id: projectId},
            success: function (result) {
                $("#sub_project_id").html(result);
            }
        });
    }

    // Function to get the correct API base URL
    function getApiBaseUrl() {
        // Check if we're on localhost or production
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            return 'http://localhost:3001';
        } else {
            return 'https://api-dojob.rubyshop.co.th';
        }
    }

    function loadStoryboardData() {
        var projectId = $("#project_id").val();
        var subProjectId = $("#sub_project_id").val();
        
        console.log('🔍 DEBUG: Selected project from dropdown:', projectId);
        console.log('🔍 DEBUG: Selected sub-project from dropdown:', subProjectId);
        console.log('🔍 DEBUG: Project dropdown HTML:', $("#project_id").html());

        if (!projectId) {
            appAlert.error("<?php echo app_lang('please_select_project_first'); ?>");
            return;
        }

        var apiBaseUrl = getApiBaseUrl();
        
        console.log('🔄 Loading storyboard data for project:', projectId, 'sub-project:', subProjectId);
        console.log('📡 Using Node.js API for storyboard export');
        console.log('🌐 API Base URL:', apiBaseUrl);
        console.log('🏠 Current hostname:', window.location.hostname);
        
        // First test CORS connectivity
        $.ajax({
            url: apiBaseUrl + "/api/storyboard/test-cors",
            type: 'GET',
            timeout: 5000,
            success: function(result) {
                console.log('✅ CORS test passed:', result);
                
                // Then test health endpoint
                $.ajax({
                    url: apiBaseUrl + "/api/health",
                    type: 'GET',
                    timeout: 5000,
                    success: function(healthResult) {
                        console.log('✅ Node.js API health check passed:', healthResult);
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Node.js API health check failed:', {status, error, xhr});
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('❌ CORS test failed:', {status, error, xhr});
                if (xhr.status === 0) {
                    console.error('🚫 Cannot reach Node.js API server at', apiBaseUrl, '- check if it\'s running');
                } else {
                    console.error('🚫 CORS configuration issue - server responded but blocked by CORS policy');
                }
            }
        });
        
        $("#loading-indicator").show();
        $("#storyboard-selection-area").hide();
        
        // Update loading message
        $("#loading-indicator p").text("Loading storyboard data for project " + $("#project_id option:selected").text() + "...");

        // Use Node.js API instead of PHP
        $.ajax({
            url: apiBaseUrl + "/api/storyboard/export-data",
            crossDomain: true,
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                project_id: parseInt(projectId),
                sub_project_id: subProjectId ? parseInt(subProjectId) : null
            }),
            beforeSend: function(xhr) {
                console.log('📤 Sending Node.js API request...');
                console.log('📋 Request data:', {
                    project_id: parseInt(projectId),
                    sub_project_id: subProjectId ? parseInt(subProjectId) : null
                });
            },
            success: function (result) {
                $("#loading-indicator").hide();
                
                console.log('✅ Storyboard data response received:', result);
                
                if (result.success) {
                    displayStoryboardData(result.data);
                    $("#storyboard-selection-area").show();
                    
                    if (result.data && result.data.length > 0) {
                        appAlert.success("Loaded " + result.data.length + " storyboard section(s) successfully via Node.js API.");
                    }
                } else {
                    console.error('❌ Server returned error:', result.message);
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            },
            error: function (xhr, status, error) {
                $("#loading-indicator").hide();
                console.error('❌ Node.js API Error Details:', error);
                
                var errorMessage = "<?php echo app_lang('error_loading_storyboard_data'); ?>";
                if (xhr.status === 0) {
                    errorMessage = "Cannot connect to Node.js API. Please ensure the server is running on " + apiBaseUrl;
                } else if (xhr.status === 404) {
                    errorMessage = "Node.js API endpoint not found. Please ensure the Node.js server is running.";
                } else if (xhr.status === 500) {
                    errorMessage = "Node.js API server error occurred. Please check the server logs.";
                }
                
                appAlert.error(errorMessage);
            }
        });
    }

    function displayStoryboardData(data) {
        console.log('🎨 Starting to display storyboard preview...');
        
        if (!data || data.length === 0) {
            console.log('⚠️ No data to display');
            var emptyHtml = '<div class="export-preview-empty">';
            emptyHtml += '<i data-feather="image" class="mb-3"></i>';
            emptyHtml += '<h5>No Storyboard Data Found</h5>';
            emptyHtml += '<p>No storyboards were found for the selected project.<br>';
            emptyHtml += 'Please make sure the project has storyboard scenes created.</p>';
            emptyHtml += '</div>';
            $("#storyboard-preview").html(emptyHtml);
            feather.replace();
            return;
        }

        // Create cards view
        var cardsHtml = createCardsView(data);
        
        var html = '<div class="storyboard-cards-view active">' + cardsHtml + '</div>';
        
        $("#storyboard-preview").html(html);
        
        // Add event handlers
        $(document).on('click', '.storyboard-preview-card', function(e) {
            if (!$(e.target).is('input[type="checkbox"]')) {
                var checkbox = $(this).find('.scene-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            }
        });
        
        // Update card appearance when selected
        $(document).on('change', '.scene-checkbox', function() {
            var card = $(this).closest('.storyboard-preview-card');
            if ($(this).is(':checked')) {
                card.addClass('selected');
            } else {
                card.removeClass('selected');
            }
        });
        
        feather.replace();
        updateSelectionCounts();
    }

    function createCardsView(data) {
        var html = '';
        
        data.forEach(function(section) {
            html += '<div class="mb-4">';
            html += '<h4 class="mb-3">' + section.title + '</h4>';
            html += '<div class="storyboard-cards-view">';
            
            // Sort storyboards by shot number
            var sortedStoryboards = section.storyboards.sort(function(a, b) {
                return parseInt(a.shot) - parseInt(b.shot);
            });
            
            sortedStoryboards.forEach(function(storyboard) {
                html += '<div class="storyboard-preview-card" data-storyboard-id="' + storyboard.id + '">';
                
                // Header with checkbox and title
                html += '<div class="card-header-blue">';
                html += '<input type="checkbox" class="scene-checkbox me-2" value="' + storyboard.id + '">';
                html += '<span class="scene-title">Shot ' + storyboard.shot + '</span>';
                html += '</div>';
                
                // Image Section
                html += '<div class="card-image-container">';
                if (storyboard.frame_url) {
                    html += '<img src="https://dojob.rubyshop.co.th' + storyboard.frame_url + '" class="storyboard-preview-image" alt="Storyboard frame">';
                } else {
                    html += '<div class="storyboard-preview-placeholder">';
                    html += '<i data-feather="image" class="placeholder-icon"></i>';
                    html += '<span class="placeholder-text">No Image</span>';
                    html += '</div>';
                }
                html += '</div>';
                
                // Details Section
                html += '<div class="card-details">';
                html += '<div class="shot-info">';
                if (storyboard.content) {
                    html += '<div class="detail-row"><span class="label">Content:</span><span class="value">' + storyboard.content.substring(0, 60) + '...</span></div>';
                }
                if (storyboard.shot_size) {
                    html += '<div class="detail-row"><span class="label">Size:</span><span class="value">' + storyboard.shot_size + '</span></div>';
                }
                if (storyboard.shot_type) {
                    html += '<div class="detail-row"><span class="label">Type:</span><span class="value">' + storyboard.shot_type + '</span></div>';
                }
                html += '</div>';
                html += '</div>';
                
                html += '</div>';
            });
            
            html += '</div>';
            html += '</div>';
        });
        
        return html;
    }

    function exportStoryboard() {
        var projectId = $("#project_id").val();
        var subProjectId = $("#sub_project_id").val();
        var apiBaseUrl = getApiBaseUrl();
        console.log('🚀 Starting export process via Node.js API...');
        console.log('🌐 Export API URL:', apiBaseUrl + "/api/storyboard/export");
        
        // Get selected scenes
        var selectedScenes = [];
        $(".scene-checkbox:checked").each(function () {
            selectedScenes.push(parseInt($(this).val()));
        });
        
        console.log('✅ Selected scenes:', selectedScenes);
        
        if (selectedScenes.length === 0) {
            appAlert.error("<?php echo app_lang('please_select_items_to_export'); ?>");
            return;
        }
        
        // Get export options
        var exportOptions = {
            include_images: $("#include_images").is(':checked'),
            include_descriptions: $("#include_descriptions").is(':checked'),
            include_notes: $("#include_notes").is(':checked'),
            include_camera_info: $("#include_camera_info").is(':checked')
        };
        
        // Show progress
        $("#export-progress").show();
        $("#export-btn").prop('disabled', true);
        updateExportProgress(10, "Preparing export data...");
        
        // Prepare export data for Node.js API
        var exportData = {
            project_id: parseInt(projectId),
            sub_project_id: subProjectId ? parseInt(subProjectId) : null,
            selected_headings: [],
            selected_scenes: selectedScenes,
            export_format: $("#export_format").val() || "json",
            include_images: exportOptions.include_images,
            include_descriptions: exportOptions.include_descriptions,
            include_notes: exportOptions.include_notes,
            include_camera_info: exportOptions.include_camera_info
        };
        
        updateExportProgress(30, "Connecting to Node.js API...");
        
        // Choose the right endpoint based on format
        var exportEndpoint;
        if (exportData.export_format === 'png') {
            exportEndpoint = '/api/storyboard/export-png';
        } else if (exportData.export_format === 'png-exact') {
            exportEndpoint = '/api/storyboard/export-png-exact';
        } else {
            exportEndpoint = '/api/storyboard/export';
        }
        
        console.log('📡 Using export endpoint:', apiBaseUrl + exportEndpoint);
        
        // Use Node.js API for export
        $.ajax({
            url: apiBaseUrl + exportEndpoint,
            crossDomain: true,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(exportData),
            processData: false,
            cache: false,
            xhrFields: {
                responseType: exportData.export_format === 'png' || exportData.export_format === 'png-exact' ? 'blob' : 'json'
            },
            beforeSend: function(xhr) {
                console.log('📡 Sending export request to Node.js API...');
                console.log('📡 Response type will be:', exportData.export_format === 'png' || exportData.export_format === 'png-exact' ? 'blob' : 'json');
                updateExportProgress(50, "Processing export...");
            },
            success: function (data, status, xhr) {
                console.log('✅ Export completed successfully via Node.js API');
                console.log('✅ Response data type:', typeof data);
                console.log('✅ Response content type:', xhr.getResponseHeader('Content-Type'));
                updateExportProgress(90, "Preparing download...");
                
                // Handle file download
                var contentDisposition = xhr.getResponseHeader('Content-Disposition');
                var defaultExtension = (exportData.export_format === 'png' || exportData.export_format === 'png-exact') ? 'png' : 
                                      exportData.export_format === 'csv' ? 'csv' : 'json';
                var filename = 'storyboard_export.' + defaultExtension;
                
                if (contentDisposition) {
                    var filenameMatch = contentDisposition.match(/filename="(.+)"/);
                    if (filenameMatch) {
                        filename = filenameMatch[1];
                    }
                }
                
                // Handle different response types
                var blob;
                if (exportData.export_format === 'png' || exportData.export_format === 'png-exact') {
                    // For PNG exports, data should already be a blob
                    if (data instanceof Blob) {
                        blob = data;
                    } else {
                        // Convert to blob if it's not already
                        blob = new Blob([data], { type: 'image/png' });
                    }
                } else {
                    // For JSON/CSV exports
                    var contentType = exportData.export_format === 'csv' ? 'text/csv' : 'application/json';
                    if (typeof data === 'string') {
                        blob = new Blob([data], { type: contentType });
                    } else {
                        blob = new Blob([JSON.stringify(data, null, 2)], { type: contentType });
                    }
                }
                
                // Create download link
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                updateExportProgress(100, "Export completed!");
                
                setTimeout(function() {
                    $("#export-progress").hide();
                    $("#export-btn").prop('disabled', false);
                    appAlert.success("Storyboard exported successfully via Node.js API! File: " + filename);
                }, 1000);
            },
            error: function (xhr, status, error) {
                console.error('❌ Node.js API Export Error Details:', {
                    status: status,
                    error: error,
                    xhr_status: xhr.status,
                    xhr_statusText: xhr.statusText,
                    xhr_responseText: xhr.responseText ? xhr.responseText.substring(0, 500) : 'No response text'
                });
                
                $("#export-progress").hide();
                $("#export-btn").prop('disabled', false);
                
                var errorMessage = "Export failed via Node.js API";
                
                // Handle specific error cases
                if (xhr.status === 0) {
                    errorMessage = "Cannot connect to Node.js API. Please ensure the server is running on " + apiBaseUrl;
                } else if (xhr.status === 404) {
                    errorMessage = "Node.js export endpoint not found. Please check the API configuration.";
                } else if (xhr.status === 500) {
                    errorMessage = "Node.js API server error occurred during export.";
                } else if (xhr.status === 200 && status === 'error') {
                    // This is the specific case we're dealing with - 200 OK but treated as error
                    console.log('🔄 Attempting fallback method for export...');
                    
                    // Try fallback method using window.open for direct download
                    try {
                        var fallbackUrl = apiBaseUrl + exportEndpoint + '?' + $.param({
                            project_id: exportData.project_id,
                            sub_project_id: exportData.sub_project_id,
                            selected_scenes: exportData.selected_scenes.join(','),
                            export_format: exportData.export_format,
                            include_images: exportData.include_images,
                            include_descriptions: exportData.include_descriptions,
                            include_notes: exportData.include_notes,
                            include_camera_info: exportData.include_camera_info
                        });
                        
                        console.log('🔄 Trying fallback URL:', fallbackUrl);
                        
                        // Create a temporary form and submit it
                        var form = $('<form>', {
                            'method': 'POST',
                            'action': apiBaseUrl + exportEndpoint,
                            'target': '_blank'
                        });
                        
                        // Add form fields
                        form.append($('<input>', {'type': 'hidden', 'name': 'project_id', 'value': exportData.project_id}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'sub_project_id', 'value': exportData.sub_project_id || ''}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'selected_scenes', 'value': JSON.stringify(exportData.selected_scenes)}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'export_format', 'value': exportData.export_format}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'include_images', 'value': exportData.include_images}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'include_descriptions', 'value': exportData.include_descriptions}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'include_notes', 'value': exportData.include_notes}));
                        form.append($('<input>', {'type': 'hidden', 'name': 'include_camera_info', 'value': exportData.include_camera_info}));
                        
                        // Submit form
                        form.appendTo('body').submit().remove();
                        
                        setTimeout(function() {
                            $("#export-progress").hide();
                            $("#export-btn").prop('disabled', false);
                            appAlert.success("Export initiated via fallback method. Check your downloads folder.");
                        }, 2000);
                        
                        return; // Don't show error if fallback was attempted
                        
                    } catch (fallbackError) {
                        console.error('Fallback method also failed:', fallbackError);
                        errorMessage = "Export response received but couldn't be processed. CORS issue detected and fallback failed.";
                    }
                } else {
                    errorMessage = "Export failed with HTTP " + xhr.status + ": " + (xhr.statusText || error);
                }
                
                appAlert.error(errorMessage);
            }
        });
    }
    
    // Helper function to update export progress
    function updateExportProgress(percentage, message) {
        $("#export-progress .progress-bar").css('width', percentage + '%');
        $("#export-status").text(message);
    }

    function updateSelectionCounts() {
        var selectedHeadings = $(".heading-checkbox:checked").length;
        var selectedScenes = $(".scene-checkbox:checked").length;
        var totalSelected = selectedHeadings + selectedScenes;
        
        $("#selected-headings-count").text(selectedHeadings);
        $("#selected-scenes-count").text(selectedScenes);
        $("#total-selected-count").text(totalSelected);
        
        // Enable/disable export button
        $("#export-btn").prop('disabled', totalSelected === 0);
    }
});
</script>