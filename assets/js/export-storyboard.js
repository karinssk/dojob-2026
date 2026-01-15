/**
 * Enhanced Export Storyboard JavaScript
 * Provides advanced functionality for storyboard export
 */

window.storyboardExporterLegacyDisabled = true;

class StoryboardExporter {
    constructor() {
        this.selectedHeadings = new Set();
        this.selectedScenes = new Set();
        this.exportInProgress = false;
        this.currentProjectData = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeTooltips();
    }

    parseFramePayload(frame) {
        if (!frame) {
            return null;
        }

        if (typeof frame === 'object') {
            return frame;
        }

        if (typeof frame !== 'string') {
            return null;
        }

        const trimmed = frame.trim();
        if (!trimmed) {
            return null;
        }

        // Attempt to parse JSON payloads
        try {
            const json = JSON.parse(trimmed);
            if (json && typeof json === 'object') {
                return json;
            }
        } catch (ignore) {
            // Not JSON – fall back to other formats
        }

        // Attempt to extract values from PHP serialized strings
        const extractSerializedValue = (key) => {
            const escapedKey = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const keyPattern = new RegExp(`s:\\d+:"${escapedKey}";s:\\d+:"([^"]+)"`);
            const match = trimmed.match(keyPattern);
            return match && match[1] ? match[1] : null;
        };

        const fileName = extractSerializedValue('file_name');
        const filePath = extractSerializedValue('file_path') || extractSerializedValue('path');

        if (fileName || filePath) {
            return {
                file_name: fileName || null,
                file_path: filePath || null
            };
        }

        return trimmed;
    }

    encodePathSegments(pathname) {
        return pathname
            .split('/')
            .map((segment) => {
                if (!segment) {
                    return segment;
                }

                try {
                    return encodeURIComponent(decodeURIComponent(segment));
                } catch (error) {
                    return encodeURIComponent(segment);
                }
            })
            .join('/');
    }

    buildAbsoluteUrl(relativePath) {
        const sanitized = relativePath.replace(/^\.\/+/g, '').replace(/^\/+/, '');

        try {
            const url = new URL(sanitized, baseUrl);
            url.pathname = this.encodePathSegments(url.pathname);
            return url.toString();
        } catch (error) {
            const hasTrailingSlash = /\/$/.test(baseUrl);
            const combined = `${baseUrl}${hasTrailingSlash ? '' : '/'}${sanitized}`;
            const parts = combined.split('?');
            const encodedPath = parts[0]
                .split('/')
                .map((segment) => (segment ? encodeURIComponent(segment) : segment))
                .join('/');
            return parts.length > 1 ? `${encodedPath}?${parts.slice(1).join('?')}` : encodedPath;
        }
    }

    sanitizeUrl(urlCandidate) {
        if (!urlCandidate) {
            return null;
        }

        const candidate = urlCandidate.trim();
        if (!candidate) {
            return null;
        }

        if (/^https?:\/\//i.test(candidate)) {
            try {
                const remote = new URL(candidate);
                remote.pathname = this.encodePathSegments(remote.pathname);
                return remote.toString();
            } catch (error) {
                return candidate.replace(/ /g, '%20');
            }
        }

        if (candidate.startsWith('files/')) {
            return this.buildAbsoluteUrl(candidate);
        }

        if (candidate.startsWith('/')) {
            return this.buildAbsoluteUrl(candidate.slice(1));
        }

        return this.buildAbsoluteUrl(`files/storyboard_frames/${candidate}`);
    }

    resolveStoryboardThumbnail(storyboard) {
        if (!storyboard) {
            return null;
        }

        const candidates = [];
        const pushCandidate = (value) => {
            if (typeof value === 'string' && value.trim() !== '') {
                candidates.push(value.trim());
            }
        };

        const frameData = this.parseFramePayload(storyboard.frame);
        if (frameData && typeof frameData === 'object') {
            if (frameData.file_name) {
                pushCandidate(`files/storyboard_frames/${frameData.file_name}`);
                pushCandidate(frameData.file_name);
            }

            ['file_path', 'path', 'full_path', 'local_path', 'image_path', 'url'].forEach((key) => {
                if (frameData[key]) {
                    pushCandidate(frameData[key]);
                }
            });
        } else if (typeof frameData === 'string') {
            pushCandidate(frameData);
        }

        ['storyboard_image', 'image', 'image_path', 'file_name', 'filename', 'frame_file', 'frame_path', 'frame_url'].forEach((field) => {
            if (storyboard[field]) {
                pushCandidate(storyboard[field]);
            }
        });

        for (const candidate of candidates) {
            const resolved = this.sanitizeUrl(candidate);
            if (resolved) {
                return resolved;
            }
        }

        return null;
    }

    escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    escapeAttribute(value) {
        return this.escapeHtml(value);
    }

    bindEvents() {
        // Project selection
        $('#project_id').on('change', () => this.onProjectChange());
        $('#sub_project_id').on('change', () => this.onSubProjectChange());
        
        // Load data
        $('#load-storyboard-btn').on('click', () => this.loadStoryboardData());
        
        // Selection controls
        $('#select-all-headings').on('click', () => this.selectAllHeadings());
        $('#deselect-all-headings').on('click', () => this.deselectAllHeadings());
        $('#select-all-scenes').on('click', () => this.selectAllScenes());
        $('#deselect-all-scenes').on('click', () => this.deselectAllScenes());
        
        // Export
        $('#export-btn').on('click', () => this.exportStoryboard());
        
        // Dynamic checkbox handling
        $(document).on('change', '.heading-checkbox', (e) => this.onHeadingCheckboxChange(e));
        $(document).on('change', '.scene-checkbox', (e) => this.onSceneCheckboxChange(e));
        
        // Keyboard shortcuts
        $(document).on('keydown', (e) => this.handleKeyboardShortcuts(e));
    }

    initializeTooltips() {
        // Initialize Bootstrap tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
    }

    onProjectChange() {
        const projectId = $('#project_id').val();
        
        if (projectId) {
            this.loadSubProjects(projectId);
        } else {
            $('#sub_project_id').html('<option value="">-</option>').trigger('change');
        }
        
        this.hideStoryboardSelection();
        this.resetSelections();
    }

    onSubProjectChange() {
        this.hideStoryboardSelection();
        this.resetSelections();
    }

    async loadSubProjects(projectId) {
        try {
            const response = await $.ajax({
                url: `${baseUrl}projects/get_sub_projects`,
                type: 'POST',
                data: { project_id: projectId }
            });
            
            $('#sub_project_id').html(response);
        } catch (error) {
            console.error('Error loading sub-projects:', error);
            this.showError('Error loading sub-projects');
        }
    }

    async loadStoryboardData() {
        const projectId = $('#project_id').val();
        const subProjectId = $('#sub_project_id').val();

        if (!projectId) {
            this.showError('Please select a project first.');
            return;
        }

        this.showLoading();
        this.hideStoryboardSelection();

        try {
            const response = await $.ajax({
                url: `${baseUrl}export_storyboard/get_storyboard_data`,
                type: 'POST',
                dataType: 'json',
                data: {
                    project_id: projectId,
                    sub_project_id: subProjectId
                }
            });

            this.hideLoading();

            if (response.success) {
                this.currentProjectData = response;
                this.displayStoryboardData(response.data);
                this.showStoryboardSelection();
                this.showSuccess('Storyboard data loaded successfully');
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.hideLoading();
            console.error('Error loading storyboard data:', error);
            this.showError('Error loading storyboard data');
        }
    }

    displayStoryboardData(data) {
        if (!data || data.length === 0) {
            $('#storyboard-content').html(this.getEmptyStateHtml());
            return;
        }

        let html = '';
        
        data.forEach((section, sectionIndex) => {
            html += this.generateSectionHtml(section, sectionIndex);
        });

        $('#storyboard-content').html(html);
        
        // Re-initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
        
        this.updateSelectionCounts();
        this.initializeSectionAnimations();
    }

    generateSectionHtml(section, sectionIndex) {
        const headingId = section.heading.id || 'unorganized';
        const headingTitle = section.heading.title || 'Unorganized Scenes';
        const sceneCount = section.storyboards.length;

        let html = `
            <div class="storyboard-section mb-4" data-section-index="${sectionIndex}">
                <div class="section-header p-3 bg-light border rounded">
                    <div class="form-check">
                        <input class="form-check-input heading-checkbox" 
                               type="checkbox" 
                               id="heading_${headingId}" 
                               value="${section.heading.id || ''}"
                               data-section-index="${sectionIndex}">
                        <label class="form-check-label fw-bold" for="heading_${headingId}">
                            <i data-feather="folder" class="icon-16 me-2"></i>
                            ${headingTitle}
                            <span class="badge bg-primary ms-2">${sceneCount}</span>
                        </label>
                        <div class="section-actions ms-auto">
                            <button type="button" class="btn btn-sm btn-outline-secondary preview-section-btn" 
                                    data-section-index="${sectionIndex}"
                                    data-bs-toggle="tooltip" 
                                    title="Preview Section">
                                <i data-feather="eye" class="icon-14"></i>
                            </button>
                        </div>
                    </div>
                </div>
        `;

        if (sceneCount > 0) {
            html += '<div class="storyboards-list mt-3">';
            
            section.storyboards.forEach((storyboard, storyboardIndex) => {
                html += this.generateStoryboardHtml(storyboard, sectionIndex, storyboardIndex);
            });
            
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    generateStoryboardHtml(storyboard, sectionIndex, storyboardIndex) {
        const sceneTitleRaw = storyboard.content || storyboard.scene_title || 'Untitled Scene';
        const sceneTitle = typeof sceneTitleRaw === 'string'
            ? sceneTitleRaw.replace(/<[^>]*>/g, '')
            : sceneTitleRaw;
        const sceneDescription = storyboard.content || storyboard.scene_description || storyboard.dialogues || '';
        const plainDescription = typeof sceneDescription === 'string'
            ? sceneDescription.replace(/<[^>]*>/g, '')
            : '';
        const truncatedDescription = plainDescription.length > 100 ?
            `${plainDescription.substring(0, 100)}...` : plainDescription;

        const thumbnailUrl = this.resolveStoryboardThumbnail(storyboard);
        const safeThumbnail = thumbnailUrl ? this.escapeAttribute(thumbnailUrl) : null;
        const modalImageAttr = thumbnailUrl ? ` data-bs-toggle="modal" data-bs-target="#image-preview-modal" data-image-src="${safeThumbnail}" data-image-title="${this.escapeAttribute(sceneTitle)}"` : '';

        let html = `
            <div class="storyboard-item p-3 border rounded mb-2" 
                 data-storyboard-id="${storyboard.id}"
                 data-section-index="${sectionIndex}"
                 data-storyboard-index="${storyboardIndex}">
                <div class="form-check">
                    <input class="form-check-input scene-checkbox" 
                           type="checkbox" 
                           id="scene_${storyboard.id}" 
                           value="${storyboard.id}">
                    <label class="form-check-label" for="scene_${storyboard.id}">
                        <div class="d-flex align-items-start">
        `;

        // Storyboard image thumbnail
        if (safeThumbnail) {
            html += `
                <div class="storyboard-thumbnail me-3">
                    <img src="${safeThumbnail}" 
                         class="img-thumbnail storyboard-thumb" 
                         style="width: 80px; height: 60px; object-fit: cover;"${modalImageAttr}>
                </div>
            `;
        } else {
            html += `
                <div class="storyboard-thumbnail me-3">
                    <div class="img-thumbnail d-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 60px; background: #f8f9fa;">
                        <i data-feather="image" class="icon-24 text-muted"></i>
                    </div>
                </div>
            `;
        }

        // Storyboard details
        html += `
                            <div class="storyboard-details flex-grow-1">
                                <h6 class="mb-1">${this.escapeHtml(sceneTitle)}</h6>
        `;

        if (truncatedDescription) {
            html += `<p class="text-muted small mb-1">${this.escapeHtml(truncatedDescription)}</p>`;
        }

        if (storyboard.camera_angle || storyboard.shot_type) {
            html += '<div class="scene-meta small text-info">';
            if (storyboard.camera_angle) {
                html += `<span class="me-3">Camera: ${this.escapeHtml(storyboard.camera_angle)}</span>`;
            }
            if (storyboard.shot_type) {
                html += `<span>Shot: ${this.escapeHtml(storyboard.shot_type)}</span>`;
            }
            html += '</div>';
        }

        html += `
                            </div>
                            <div class="storyboard-actions ms-2">
                                <button type="button" class="btn btn-sm btn-outline-primary preview-storyboard-btn" 
                                        data-storyboard-id="${storyboard.id}"
                                        data-bs-toggle="tooltip" 
                                        title="Preview Storyboard">
                                    <i data-feather="eye" class="icon-14"></i>
                                </button>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        `;

        return html;
    }

    getEmptyStateHtml() {
        return `
            <div class="text-center py-5">
                <i data-feather="film" class="icon-48 text-muted mb-3"></i>
                <h5 class="text-muted">No Storyboard Data Found</h5>
                <p class="text-muted">No storyboards were found for the selected project.</p>
                <p class="text-muted small">Try selecting a different project or create some storyboards first.</p>
            </div>
        `;
    }

    initializeSectionAnimations() {
        // Add smooth animations for section interactions
        $('.storyboard-section').each(function(index) {
            $(this).css('animation-delay', `${index * 0.1}s`);
        });
    }

    onHeadingCheckboxChange(e) {
        const checkbox = $(e.target);
        const sectionIndex = checkbox.data('section-index');
        const isChecked = checkbox.is(':checked');
        
        // Select/deselect all scenes in this section
        $(`.storyboard-item[data-section-index="${sectionIndex}"] .scene-checkbox`)
            .prop('checked', isChecked)
            .trigger('change');
        
        this.updateSelectionCounts();
    }

    onSceneCheckboxChange(e) {
        const checkbox = $(e.target);
        const sectionIndex = checkbox.data('section-index');
        
        // Check if all scenes in section are selected
        const sectionScenes = $(`.storyboard-item[data-section-index="${sectionIndex}"] .scene-checkbox`);
        const checkedScenes = sectionScenes.filter(':checked');
        
        // Update heading checkbox state
        const headingCheckbox = $(`.heading-checkbox[data-section-index="${sectionIndex}"]`);
        
        if (checkedScenes.length === 0) {
            headingCheckbox.prop('checked', false).prop('indeterminate', false);
        } else if (checkedScenes.length === sectionScenes.length) {
            headingCheckbox.prop('checked', true).prop('indeterminate', false);
        } else {
            headingCheckbox.prop('checked', false).prop('indeterminate', true);
        }
        
        this.updateSelectionCounts();
    }

    selectAllHeadings() {
        $('.heading-checkbox').prop('checked', true).trigger('change');
    }

    deselectAllHeadings() {
        $('.heading-checkbox').prop('checked', false).trigger('change');
    }

    selectAllScenes() {
        $('.scene-checkbox').prop('checked', true).trigger('change');
    }

    deselectAllScenes() {
        $('.scene-checkbox').prop('checked', false).trigger('change');
    }

    updateSelectionCounts() {
        const selectedHeadings = $('.heading-checkbox:checked').length;
        const selectedScenes = $('.scene-checkbox:checked').length;
        const totalSelected = selectedHeadings + selectedScenes;

        $('#selected-headings-count').text(selectedHeadings);
        $('#selected-scenes-count').text(selectedScenes);
        $('#total-selected-count').text(totalSelected);

        // Enable/disable export button
        $('#export-btn').prop('disabled', totalSelected === 0);
        
        // Update button text
        if (totalSelected > 0) {
            $('#export-btn').html(`
                <i data-feather="download" class="icon-16 me-2"></i>
                Export ${totalSelected} Item${totalSelected > 1 ? 's' : ''}
            `);
        } else {
            $('#export-btn').html(`
                <i data-feather="download" class="icon-16 me-2"></i>
                Export Storyboard
            `);
        }
        
        // Re-initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    async exportStoryboard() {
        if (this.exportInProgress) return;
        
        const projectId = $('#project_id').val();
        const subProjectId = $('#sub_project_id').val();

        // Get selected items
        const selectedHeadings = [];
        const selectedScenes = [];

        $('.heading-checkbox:checked').each(function() {
            const value = $(this).val();
            if (value) selectedHeadings.push(value);
        });

        $('.scene-checkbox:checked').each(function() {
            selectedScenes.push($(this).val());
        });

        if (selectedHeadings.length === 0 && selectedScenes.length === 0) {
            this.showError('Please select at least one heading or scene to export.');
            return;
        }

        // Get export options
        const exportOptions = {
            include_images: $('#include_images').is(':checked'),
            include_descriptions: $('#include_descriptions').is(':checked'),
            include_notes: $('#include_notes').is(':checked'),
            include_camera_info: $('#include_camera_info').is(':checked')
        };

        this.startExport();

        try {
            const exportUrl = `${baseUrl}export_storyboard/export_png`;

            const response = await $.ajax({
                url: exportUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    project_id: projectId,
                    sub_project_id: subProjectId,
                    selected_headings: selectedHeadings.join(','),
                    selected_scenes: selectedScenes.join(','),
                    export_options: exportOptions
                }
            });

            this.finishExport();

            if (response.success) {
                this.showPngDownloadModal(response.files);
                this.showSuccess(response.message);
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.finishExport();
            console.error('Export error:', error);
            this.showError('Error exporting storyboard');
        }
    }

    startExport() {
        this.exportInProgress = true;
        $('#export-progress').show();
        $('#export-btn').prop('disabled', true);
        
        // Animate progress bar
        this.animateProgressBar();
    }

    finishExport() {
        this.exportInProgress = false;
        $('#export-progress').hide();
        $('#export-btn').prop('disabled', false);
    }

    animateProgressBar() {
        const progressBar = $('#export-progress .progress-bar');
        let progress = 0;
        
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            
            progressBar.css('width', `${progress}%`);
            
            if (!this.exportInProgress) {
                progressBar.css('width', '100%');
                setTimeout(() => {
                    progressBar.css('width', '0%');
                }, 500);
                clearInterval(interval);
            }
        }, 200);
    }

    showPngDownloadModal(files) {
        const modal = this.createPngDownloadModal(files);
        $('body').append(modal);
        $('#png-download-modal').modal('show');
        
        // Remove modal after hiding
        $('#png-download-modal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
        
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    createPngDownloadModal(files) {
        let html = `
            <div class="modal fade" id="png-download-modal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i data-feather="download" class="icon-16 me-2"></i>
                                Download PNG Files
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">Your storyboard has been exported as PNG images. Click on the files below to download:</p>
                            <div class="list-group">
        `;

        files.forEach(file => {
            const downloadUrl = `${baseUrl}export_storyboard/download?file=${encodeURIComponent(file.path)}`;
            html += `
                <a href="${downloadUrl}" class="list-group-item list-group-item-action" target="_blank">
                    <div class="d-flex align-items-center">
                        <i data-feather="image" class="icon-16 me-3 text-success"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${file.scene_title}</h6>
                            <small class="text-muted">${file.filename}</small>
                        </div>
                        <i data-feather="download" class="icon-16 text-primary"></i>
                    </div>
                </a>
            `;
        });

        html += `
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" id="download-all-png">
                                    <i data-feather="download" class="icon-16 me-2"></i>
                                    Download All Files
                                </button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + A: Select all
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && e.target.tagName !== 'INPUT') {
            e.preventDefault();
            this.selectAllScenes();
        }
        
        // Ctrl/Cmd + D: Deselect all
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            this.deselectAllScenes();
        }
        
        // Ctrl/Cmd + E: Export
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            if (!$('#export-btn').prop('disabled')) {
                this.exportStoryboard();
            }
        }
    }

    showLoading() {
        $('#loading-indicator').show();
    }

    hideLoading() {
        $('#loading-indicator').hide();
    }

    showStoryboardSelection() {
        $('#storyboard-selection-area').show();
    }

    hideStoryboardSelection() {
        $('#storyboard-selection-area').hide();
    }

    resetSelections() {
        this.selectedHeadings.clear();
        this.selectedScenes.clear();
        this.updateSelectionCounts();
    }

    showSuccess(message) {
        if (typeof appAlert !== 'undefined') {
            appAlert.success(message);
        } else {
            alert(message);
        }
    }

    showError(message) {
        if (typeof appAlert !== 'undefined') {
            appAlert.error(message);
        } else {
            alert(message);
        }
    }
}

// Initialize when document is ready
$(document).ready(function() {
    window.storyboardExporter = new StoryboardExporter();
});

// Make functions available globally for backward compatibility
window.loadStoryboardData = function() {
    if (window.storyboardExporter) {
        window.storyboardExporter.loadStoryboardData();
    }
};

window.exportStoryboard = function() {
    if (window.storyboardExporter) {
        window.storyboardExporter.exportStoryboard();
    }
};
