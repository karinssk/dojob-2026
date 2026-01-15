<!-- Storyboard Modal -->
<div class="modal fade" id="storyboard-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div id="storyboard-modal-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Scene Heading Modal -->
<div class="modal fade" id="scene-heading-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div id="scene-heading-modal-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Modern Image Viewer -->
<div class="image-viewer-overlay" id="imageViewer" style="display: none;">
    <div class="image-viewer-container">
        <!-- Close Button -->
        <button class="image-viewer-close" onclick="closeImageViewer()">
            <i data-feather="x" class="icon-24"></i>
        </button>
        
        <!-- Navigation Arrows -->
        <button class="image-viewer-nav image-viewer-prev" onclick="navigateImage(-1)">
            <i data-feather="chevron-left" class="icon-32"></i>
        </button>
        <button class="image-viewer-nav image-viewer-next" onclick="navigateImage(1)">
            <i data-feather="chevron-right" class="icon-32"></i>
        </button>
        
        <!-- Image Container -->
        <div class="image-viewer-content">
            <img id="viewerImage" src="" alt="Image Preview">
            <div class="image-viewer-info">
                <span id="imageCounter">1 / 1</span>
            </div>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="edit-project-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-feather="edit" class="icon-16 me-2"></i>
                    Edit Project
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="edit-project-form">
                <div class="modal-body">
                    <input type="hidden" id="edit-project-id" name="id" value="<?php echo $project_id; ?>">
                    <div class="mb-3">
                        <label for="edit-project-title" class="form-label">Project Title *</label>
                        <input type="text" class="form-control" id="edit-project-title" name="title" value="<?php echo htmlspecialchars($project_info->title); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-project-description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit-project-description" name="description" rows="3"><?php echo htmlspecialchars($project_info->description ?: ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save" class="icon-16 me-1"></i>
                        Update Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Field Options Management Modal -->
<div class="modal fade" id="field-options-modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-feather="sliders" class="icon-16 me-2"></i>
                    Manage Field Options
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="list-group field-type-list" id="field-type-list">
                            <button type="button" class="list-group-item list-group-item-action active" data-field="story_status">
                                <i data-feather="flag" class="icon-16 me-2"></i>Status
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" data-field="shot_size">
                                <i data-feather="maximize" class="icon-16 me-2"></i>Shot Size
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" data-field="shot_type">
                                <i data-feather="camera" class="icon-16 me-2"></i>Shot Type
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" data-field="movement">
                                <i data-feather="move" class="icon-16 me-2"></i>Movement
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" data-field="framerate">
                                <i data-feather="film" class="icon-16 me-2"></i>Frame Rate
                            </button>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="field-options-content">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0" id="current-field-title">Status Options</h6>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addNewOption()">
                                    <i data-feather="plus" class="icon-12 me-1"></i>Add Option
                                </button>
                            </div>
                            <div class="options-list" id="options-list">
                                <!-- Options will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveFieldOptions()">
                    <i data-feather="save" class="icon-16 me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Video Preview Modal - Shared for both index and modal form -->
<div class="modal fade" id="video-preview-modal" tabindex="-1" aria-labelledby="videoPreviewModalLabel" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoPreviewModalLabel">
                    <i data-feather="play-circle" class="icon-16 me-2"></i>
                    Video Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="video-container">
                    <video id="preview-video" class="w-100" controls preload="metadata" style="max-height: 70vh;">
                        <source id="video-source" src="" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <div class="video-info p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="video-details">
                            <h6 class="mb-1" id="video-filename">Video Name</h6>
                            <small class="text-muted" id="video-metadata">Loading video information...</small>
                        </div>
                        <div class="video-controls">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="fullscreen-btn" title="Fullscreen">
                                <i data-feather="maximize" class="icon-14"></i>
                            </button>
                            <a href="#" target="_blank" class="btn btn-sm btn-outline-secondary" id="download-video-btn" title="Download">
                                <i data-feather="download" class="icon-14"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Emoji Picker Modal -->
<div class="modal fade" id="emoji-picker-modal" tabindex="-1" aria-labelledby="emojiPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emojiPickerModalLabel">
                    <i class="fas fa-smile me-2"></i>
                    Choose Emoji
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="emoji-search-container mb-3">
                    <input type="text" class="form-control" id="emoji-search" placeholder="Search emojis..." onkeyup="filterEmojis()">
                </div>
                <div class="emoji-loading text-center py-4" id="emoji-modal-loading">
                    <i class="fas fa-spinner fa-spin me-2"></i>Loading emojis...
                </div>
                <div class="emoji-content" id="emoji-modal-content" style="display: none;">
                    <div class="emoji-grid" id="emoji-modal-grid">
                        <!-- Emojis will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-danger" onclick="clearEmoji()">Clear</button>
            </div>
        </div>
    </div>
</div>


<style>/* Image Viewer Modal Styles */
.image-viewer-overlay {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  z-index: 99999 !important;
  background: rgba(0, 0, 0, 0.95) !important;
  display: none !important;
  align-items: center !important;
  justify-content: center !important;
  overflow: hidden !important;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.image-viewer-overlay[style*="display: flex"] {
  display: flex !important;
}

.image-viewer-overlay.show {
  opacity: 1;
}

.image-viewer-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-viewer-close {
  position: absolute;
  top: 20px;
  right: 20px;
  z-index: 100001;
  background: rgba(255, 255, 255, 0.1);
  border: 2px solid rgba(255, 255, 255, 0.3);
  color: white;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.image-viewer-close:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.5);
  transform: rotate(90deg);
}

.image-viewer-content {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.image-viewer-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 100001;
  background: rgba(255, 255, 255, 0.15);
  border: 2px solid rgba(255, 255, 255, 0.4);
  color: white;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.image-viewer-nav:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.6);
  transform: translateY(-50%) scale(1.1);
}

.image-viewer-prev {
  left: 10px;
}

.image-viewer-next {
  right: 10px;
}

.image-viewer-content img {
  max-width: 100%;
  max-height: 85vh;
  width: auto;
  height: auto;
  object-fit: contain;
  border-radius: 8px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
  animation: imageZoomIn 0.3s ease;
}

@keyframes imageZoomIn {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.image-viewer-info {
  position: absolute;
  bottom: -50px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  padding: 10px 20px;
  border-radius: 20px;
  color: white;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .image-viewer-close {
    top: 10px;
    right: 10px;
    width: 40px;
    height: 40px;
  }

  .image-viewer-nav {
    width: 44px;
    height: 44px;
  }

  .image-viewer-prev {
    left: 5px;
  }

  .image-viewer-next {
    right: 5px;
  }

  .image-viewer-content {
    max-width: 95vw;
    max-height: 95vh;
  }

  .image-viewer-content img {
    max-height: 80vh;
  }

  .image-viewer-info {
    bottom: -40px;
    font-size: 12px;
    padding: 8px 16px;
  }
}
</style>