/**
 * Advanced Image Editor for Storyboard
 * Features: Crop, Resize, Rotate, Filters, Brightness, Contrast, etc.
 */

class StoryboardImageEditor {
  constructor() {
    this.canvas = null;
    this.ctx = null;
    this.originalImage = null;
    this.currentImage = null;
    this.history = [];
    this.historyIndex = -1;
    this.isDrawing = false;
    this.cropArea = null;
    this.filters = {
      brightness: 100,
      contrast: 100,
      saturation: 100,
      blur: 0,
      sepia: 0,
      grayscale: 0,
    };
    this.rotation = 0;
    this.scale = 1;

    this.initializeEditor();
  }

  initializeEditor() {
    this.createEditorModal();
    this.bindEvents();
  }

  createEditorModal() {
    const modalHTML = `
            <div class="modal fade" id="imageEditorModal" tabindex="-1" role="dialog" aria-labelledby="imageEditorModalLabel" aria-hidden="true" style="z-index: 9999;">
                <div class="modal-dialog modal-xl" role="document" style="z-index: 9999;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imageEditorModalLabel">
                                <i data-feather="edit-3" class="icon-16"></i>
                                Image Editor
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="row g-0">
                                <!-- Toolbar -->
                                <div class="col-md-3 bg-light border-end">
                                    <div class="p-3">
                                        <div class="editor-toolbar">
                                            <!-- Basic Tools -->
                                            <div class="tool-group mb-3">
                                                <h6 class="tool-group-title">Basic Tools</h6>
                                                <div class="btn-group-vertical w-100" role="group">
                                                    <button type="button" class="btn btn-outline-primary btn-sm tool-btn" data-tool="crop" title="Click and drag to select crop area">
                                                        <i data-feather="crop" class="icon-14"></i> Crop
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-sm tool-btn" data-tool="rotate" title="Rotate image 90° clockwise">
                                                        <i data-feather="rotate-cw" class="icon-14"></i> Rotate 90°
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-sm tool-btn" data-tool="resize" title="Resize image dimensions">
                                                        <i data-feather="maximize" class="icon-14"></i> Resize
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Filters -->
                                            <div class="tool-group mb-3">
                                                <h6 class="tool-group-title">Adjustments</h6>
                                                <div class="filter-controls">
                                                    <div class="mb-2">
                                                        <label class="form-label small">Brightness</label>
                                                        <input type="range" class="form-range" id="brightness" min="0" max="200" value="100">
                                                        <span class="filter-value">100%</span>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Contrast</label>
                                                        <input type="range" class="form-range" id="contrast" min="0" max="200" value="100">
                                                        <span class="filter-value">100%</span>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Saturation</label>
                                                        <input type="range" class="form-range" id="saturation" min="0" max="200" value="100">
                                                        <span class="filter-value">100%</span>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Blur</label>
                                                        <input type="range" class="form-range" id="blur" min="0" max="10" value="0">
                                                        <span class="filter-value">0px</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Effects -->
                                            <div class="tool-group mb-3">
                                                <h6 class="tool-group-title">Effects</h6>
                                                <div class="btn-group-vertical w-100" role="group">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm effect-btn" data-effect="grayscale">
                                                        Grayscale
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm effect-btn" data-effect="sepia">
                                                        Sepia
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm effect-btn" data-effect="invert">
                                                        Invert
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="tool-group">
                                                <h6 class="tool-group-title">Actions</h6>
                                                <div class="btn-group-vertical w-100" role="group">
                                                    <button type="button" class="btn btn-outline-warning btn-sm" id="undoBtn">
                                                        <i data-feather="corner-up-left" class="icon-14"></i> Undo
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info btn-sm" id="redoBtn">
                                                        <i data-feather="corner-up-right" class="icon-14"></i> Redo
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" id="resetBtn">
                                                        <i data-feather="refresh-cw" class="icon-14"></i> Reset
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Canvas Area -->
                                <div class="col-md-9">
                                    <div class="canvas-container p-3">
                                        <div class="canvas-wrapper">
                                            <canvas id="imageCanvas" class="img-fluid"></canvas>
                                            <div id="cropOverlay" class="crop-overlay" style="display: none;">
                                                <div class="crop-area">
                                                    <div class="crop-handle nw"></div>
                                                    <div class="crop-handle ne"></div>
                                                    <div class="crop-handle sw"></div>
                                                    <div class="crop-handle se"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveImageBtn">
                                <i data-feather="save" class="icon-14"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Remove existing modal if present
    const existingModal = document.getElementById("imageEditorModal");
    if (existingModal) {
      existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML("beforeend", modalHTML);

    // Add CSS styles
    this.addEditorStyles();
  }

  addEditorStyles() {
    if (document.getElementById("image-editor-styles")) return;

    const styles = `
            <style id="image-editor-styles">
                /* Image Editor Modal - Ensure it appears above other modals */
                #imageEditorModal {
                    z-index: 999999 !important;
                }
                
                #imageEditorModal.modal.show {
                    z-index: 999999 !important;
                }
                
                #imageEditorModal .modal-dialog {
                    z-index: 999999 !important;
                }
                
                #imageEditorModal .modal-content {
                    z-index: 999999 !important;
                }
                
                /* Force backdrop to be below image editor */
                .modal-backdrop {
                    z-index: 999998 !important;
                }
                
                /* Custom styling for tool feedback toasts */
                .tool-feedback-toast {
                    font-size: 14px !important;
                }
                
                .tool-feedback-toast .swal2-title {
                    font-size: 16px !important;
                    margin: 0 !important;
                }
                
                .canvas-container {
                    background: #f8f9fa;
                    min-height: 500px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    position: relative;
                    z-index: 10002;
                }
                
                .canvas-wrapper {
                    position: relative;
                    display: inline-block;
                    max-width: 100%;
                    max-height: 70vh;
                }
                
                #imageCanvas {
                    max-width: 100%;
                    max-height: 70vh;
                    border: 2px solid #dee2e6;
                    border-radius: 4px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                
                .tool-group-title {
                    font-size: 0.875rem;
                    font-weight: 600;
                    color: #495057;
                    margin-bottom: 0.5rem;
                    padding-bottom: 0.25rem;
                    border-bottom: 1px solid #dee2e6;
                }
                
                .tool-btn.active {
                    background-color: #007bff;
                    color: white;
                    border-color: #007bff;
                }
                
                .filter-controls .form-range {
                    margin-bottom: 0.25rem;
                }
                
                .filter-value {
                    font-size: 0.75rem;
                    color: #6c757d;
                    float: right;
                }
                
                .crop-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    cursor: crosshair;
                }
                
                .crop-area {
                    position: absolute;
                    border: 2px dashed #fff;
                    background: rgba(255, 255, 255, 0.1);
                    min-width: 50px;
                    min-height: 50px;
                }
                
                .crop-handle {
                    position: absolute;
                    width: 10px;
                    height: 10px;
                    background: #007bff;
                    border: 2px solid #fff;
                    border-radius: 50%;
                }
                
                .crop-handle.nw { top: -6px; left: -6px; cursor: nw-resize; }
                .crop-handle.ne { top: -6px; right: -6px; cursor: ne-resize; }
                .crop-handle.sw { bottom: -6px; left: -6px; cursor: sw-resize; }
                .crop-handle.se { bottom: -6px; right: -6px; cursor: se-resize; }
                
                .effect-btn.active {
                    background-color: #6c757d;
                    color: white;
                    border-color: #6c757d;
                }
                
                .editor-toolbar {
                    max-height: 70vh;
                    overflow-y: auto;
                    position: relative;
                    z-index: 10003;
                }
                
                .btn-group-vertical .btn {
                    margin-bottom: 2px;
                }
                
                /* Ensure modal backdrop for image editor is above storyboard modal */
                .modal-backdrop.show {
                    z-index: 9998 !important;
                }
                
                /* Specific backdrop for image editor */
                #imageEditorModal + .modal-backdrop {
                    z-index: 9998 !important;
                }
            </style>
        `;

    document.head.insertAdjacentHTML("beforeend", styles);
  }

  bindEvents() {
    // Tool selection
    $(document).on("click", ".tool-btn", (e) => {
      const tool = $(e.currentTarget).data("tool");
      this.selectTool(tool);
    });

    // Filter controls
    $(document).on("input", '.filter-controls input[type="range"]', (e) => {
      this.updateFilter(e.target.id, e.target.value);
    });

    // Effect buttons
    $(document).on("click", ".effect-btn", (e) => {
      const effect = $(e.currentTarget).data("effect");
      this.applyEffect(effect);
    });

    // Action buttons
    $(document).on("click", "#undoBtn", () => this.undo());
    $(document).on("click", "#redoBtn", () => this.redo());
    $(document).on("click", "#resetBtn", () => this.reset());
    $(document).on("click", "#saveImageBtn", () => this.saveImage());

    // Canvas events for cropping
    $(document).on("mousedown", "#cropOverlay", (e) => this.startCrop(e));
    $(document).on("mousemove", "#cropOverlay", (e) => this.updateCrop(e));
    $(document).on("mouseup", "#cropOverlay", (e) => this.endCrop(e));
  }

  openEditor(imageSrc, callback) {
    this.saveCallback = callback;
    this.loadImage(imageSrc);

    // Simple modal show with high z-index
    const modal = document.getElementById("imageEditorModal");
    if (modal) {
      modal.style.zIndex = "999999";
    }

    $("#imageEditorModal").modal("show");

    // Force z-index after modal is shown
    setTimeout(() => {
      if (modal) {
        modal.style.zIndex = "999999";
      }
    }, 50);
  }

  loadImage(src) {
    const img = new Image();
    img.crossOrigin = "anonymous";
    img.onload = () => {
      this.originalImage = img;
      this.currentImage = img;
      this.setupCanvas();
      this.saveState();
      this.renderImage();
    };
    img.src = src;
  }

  setupCanvas() {
    this.canvas = document.getElementById("imageCanvas");
    this.ctx = this.canvas.getContext("2d");

    // Set canvas size based on image
    const maxWidth = 800;
    const maxHeight = 600;
    let { width, height } = this.currentImage;

    if (width > maxWidth || height > maxHeight) {
      const ratio = Math.min(maxWidth / width, maxHeight / height);
      width *= ratio;
      height *= ratio;
    }

    this.canvas.width = width;
    this.canvas.height = height;
  }

  renderImage() {
    if (!this.canvas || !this.currentImage) return;

    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

    // Apply filters
    this.ctx.filter = this.getFilterString();

    // Apply rotation and scale
    this.ctx.save();
    this.ctx.translate(this.canvas.width / 2, this.canvas.height / 2);
    this.ctx.rotate((this.rotation * Math.PI) / 180);
    this.ctx.scale(this.scale, this.scale);

    this.ctx.drawImage(
      this.currentImage,
      -this.canvas.width / 2,
      -this.canvas.height / 2,
      this.canvas.width,
      this.canvas.height
    );

    this.ctx.restore();
  }

  getFilterString() {
    const f = this.filters;
    return `brightness(${f.brightness}%) contrast(${f.contrast}%) saturate(${f.saturation}%) blur(${f.blur}px) sepia(${f.sepia}%) grayscale(${f.grayscale}%)`;
  }

  selectTool(tool) {
    // Remove active class from all tools
    $(".tool-btn").removeClass("active");

    // Add active class to selected tool
    $(`.tool-btn[data-tool="${tool}"]`).addClass("active");

    // Hide/show crop overlay
    if (tool === "crop") {
      const overlay = $("#cropOverlay");
      const canvas = $("#imageCanvas");

      // Position overlay to match canvas
      const canvasPos = canvas.position();
      overlay.css({
        left: canvasPos.left + "px",
        top: canvasPos.top + "px",
        width: canvas.width() + "px",
        height: canvas.height() + "px",
      });

      overlay.show();
      console.log("Crop overlay shown and positioned");
    } else {
      $("#cropOverlay").hide();
    }

    // Handle specific tools
    switch (tool) {
      case "rotate":
        this.rotateImage(90);
        break;
      case "resize":
        this.showResizeDialog();
        break;
    }

    this.currentTool = tool;
  }

  rotateImage(degrees) {
    this.rotation += degrees;
    if (this.rotation >= 360) this.rotation -= 360;
    if (this.rotation < 0) this.rotation += 360;

    console.log(
      `Rotated image by ${degrees}°, total rotation: ${this.rotation}°`
    );

    this.renderImage();
    this.saveState();

    // Show feedback
    this.showToolFeedback(`Rotated ${degrees}°`);
  }

  async showResizeDialog() {
    const currentWidth = this.canvas.width;
    const currentHeight = this.canvas.height;

    try {
      const { value: newWidth } = await Swal.fire({
        title: "Resize Image",
        text: `Current size: ${currentWidth} × ${currentHeight}`,
        input: "number",
        inputLabel: "Enter new width:",
        inputValue: currentWidth,
        inputAttributes: {
          min: 50,
          max: 2000,
          step: 1,
        },
        showCancelButton: true,
        confirmButtonText: "Next",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#007bff",
        cancelButtonColor: "#6c757d",
        inputValidator: (value) => {
          if (!value || isNaN(value) || value < 50 || value > 2000) {
            return "Please enter a valid width between 50 and 2000 pixels";
          }
        },
      });

      if (newWidth) {
        const { value: newHeight } = await Swal.fire({
          title: "Resize Image",
          text: `Width: ${newWidth}px`,
          input: "number",
          inputLabel: "Enter new height:",
          inputValue: currentHeight,
          inputAttributes: {
            min: 50,
            max: 2000,
            step: 1,
          },
          showCancelButton: true,
          confirmButtonText: "Resize",
          cancelButtonText: "Cancel",
          confirmButtonColor: "#28a745",
          cancelButtonColor: "#6c757d",
          inputValidator: (value) => {
            if (!value || isNaN(value) || value < 50 || value > 2000) {
              return "Please enter a valid height between 50 and 2000 pixels";
            }
          },
        });

        if (newHeight) {
          this.resizeCanvas(parseInt(newWidth), parseInt(newHeight));

          // Show success message
          Swal.fire({
            icon: "success",
            title: "Image Resized!",
            text: `New size: ${newWidth} × ${newHeight} pixels`,
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
          });
        }
      }
    } catch (error) {
      console.error("Error in resize dialog:", error);
    }
  }

  resizeCanvas(newWidth, newHeight) {
    // Create temporary canvas with current image
    const tempCanvas = document.createElement("canvas");
    const tempCtx = tempCanvas.getContext("2d");
    tempCanvas.width = this.canvas.width;
    tempCanvas.height = this.canvas.height;
    tempCtx.drawImage(this.canvas, 0, 0);

    // Resize main canvas
    this.canvas.width = newWidth;
    this.canvas.height = newHeight;

    // Redraw image at new size
    this.ctx.clearRect(0, 0, newWidth, newHeight);
    this.ctx.drawImage(tempCanvas, 0, 0, newWidth, newHeight);

    console.log(`Resized image to ${newWidth}x${newHeight}`);
    this.saveState();
    this.showToolFeedback(`Resized to ${newWidth}×${newHeight}`);
  }

  showToolFeedback(message) {
    // Use SweetAlert toast for tool feedback
    Swal.fire({
      icon: "success",
      title: message,
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
      background: "#28a745",
      color: "#fff",
      customClass: {
        popup: "tool-feedback-toast",
      },
    });
  }

  showError(message) {
    // Use SweetAlert for error messages
    Swal.fire({
      icon: "error",
      title: "Error",
      text: message,
      confirmButtonText: "OK",
      confirmButtonColor: "#dc3545",
    });
  }

  showInfo(title, message) {
    // Use SweetAlert for info messages
    Swal.fire({
      icon: "info",
      title: title,
      text: message,
      confirmButtonText: "OK",
      confirmButtonColor: "#007bff",
    });
  }

  updateFilter(filterId, value) {
    this.filters[filterId] = parseInt(value);

    // Update display value
    const unit = filterId === "blur" ? "px" : "%";
    $(`.filter-controls input#${filterId}`)
      .siblings(".filter-value")
      .text(value + unit);

    this.renderImage();
    this.saveState();
  }

  applyEffect(effect) {
    const btn = $(`.effect-btn[data-effect="${effect}"]`);

    switch (effect) {
      case "grayscale":
        this.filters.grayscale = this.filters.grayscale === 0 ? 100 : 0;
        btn.toggleClass("active");
        break;
      case "sepia":
        this.filters.sepia = this.filters.sepia === 0 ? 100 : 0;
        btn.toggleClass("active");
        break;
      case "invert":
        // Toggle invert filter
        this.ctx.filter += this.ctx.filter.includes("invert")
          ? ""
          : " invert(100%)";
        btn.toggleClass("active");
        break;
    }

    this.renderImage();
    this.saveState();
  }

  saveState() {
    // Remove any states after current index
    this.history = this.history.slice(0, this.historyIndex + 1);

    // Save current state
    const state = {
      imageData: this.ctx.getImageData(
        0,
        0,
        this.canvas.width,
        this.canvas.height
      ),
      filters: { ...this.filters },
      rotation: this.rotation,
      scale: this.scale,
    };

    this.history.push(state);
    this.historyIndex++;

    // Limit history size
    if (this.history.length > 20) {
      this.history.shift();
      this.historyIndex--;
    }

    this.updateActionButtons();
  }

  undo() {
    if (this.historyIndex > 0) {
      this.historyIndex--;
      this.restoreState();
    }
  }

  redo() {
    if (this.historyIndex < this.history.length - 1) {
      this.historyIndex++;
      this.restoreState();
    }
  }

  restoreState() {
    const state = this.history[this.historyIndex];
    this.ctx.putImageData(state.imageData, 0, 0);
    this.filters = { ...state.filters };
    this.rotation = state.rotation;
    this.scale = state.scale;

    this.updateFilterControls();
    this.updateActionButtons();
  }

  updateFilterControls() {
    Object.keys(this.filters).forEach((key) => {
      const input = document.getElementById(key);
      if (input) {
        input.value = this.filters[key];
        const unit = key === "blur" ? "px" : "%";
        $(input)
          .siblings(".filter-value")
          .text(this.filters[key] + unit);
      }
    });
  }

  updateActionButtons() {
    $("#undoBtn").prop("disabled", this.historyIndex <= 0);
    $("#redoBtn").prop(
      "disabled",
      this.historyIndex >= this.history.length - 1
    );
  }

  reset() {
    this.filters = {
      brightness: 100,
      contrast: 100,
      saturation: 100,
      blur: 0,
      sepia: 0,
      grayscale: 0,
    };
    this.rotation = 0;
    this.scale = 1;

    this.currentImage = this.originalImage;
    this.setupCanvas();
    this.renderImage();
    this.updateFilterControls();
    this.saveState();

    // Reset effect buttons
    $(".effect-btn").removeClass("active");
  }

  saveImage() {
    // Convert canvas to blob with high quality
    this.canvas.toBlob(
      (blob) => {
        if (this.saveCallback) {
          // Add metadata about the edits made
          const editMetadata = {
            rotation: this.rotation,
            filters: this.filters,
            originalSize: {
              width: this.originalImage.width,
              height: this.originalImage.height,
            },
            finalSize: {
              width: this.canvas.width,
              height: this.canvas.height,
            },
            editedAt: new Date().toISOString(),
          };

          console.log("Saving edited image with metadata:", editMetadata);

          // Create enhanced blob with metadata
          const enhancedBlob = new Blob([blob], {
            type: "image/jpeg",
            lastModified: Date.now(),
          });

          // Add metadata as property (for debugging)
          enhancedBlob.editMetadata = editMetadata;

          this.saveCallback(enhancedBlob);
        }

        // Properly hide the modal
        const modal = document.getElementById("imageEditorModal");
        if (modal) {
          // Remove focus from save button to prevent accessibility warnings
          const saveBtn = document.getElementById("saveImageBtn");
          if (saveBtn) {
            saveBtn.blur();
          }

          // Hide modal using Bootstrap
          try {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
              bsModal.hide();
            } else {
              $("#imageEditorModal").modal("hide");
            }
          } catch (e) {
            $("#imageEditorModal").modal("hide");
          }
        }
      },
      "image/jpeg",
      0.95 // Higher quality for better results
    );
  }

  // Crop functionality
  startCrop(e) {
    if (this.currentTool !== "crop") return;

    console.log("Start crop:", e);
    this.isDrawing = true;
    const rect = this.canvas.getBoundingClientRect();
    this.cropStart = {
      x: e.clientX - rect.left,
      y: e.clientY - rect.top,
    };
    console.log("Crop start position:", this.cropStart);
  }

  updateCrop(e) {
    if (!this.isDrawing || this.currentTool !== "crop") return;

    const rect = this.canvas.getBoundingClientRect();
    const current = {
      x: e.clientX - rect.left,
      y: e.clientY - rect.top,
    };

    const cropArea = $(".crop-area");
    const width = Math.abs(current.x - this.cropStart.x);
    const height = Math.abs(current.y - this.cropStart.y);
    const left = Math.min(current.x, this.cropStart.x);
    const top = Math.min(current.y, this.cropStart.y);

    console.log("Update crop:", { left, top, width, height });

    cropArea.css({
      left: left + "px",
      top: top + "px",
      width: width + "px",
      height: height + "px",
    });

    // Store crop coordinates
    this.cropArea = { left, top, width, height };
  }

  endCrop(_e) {
    this.isDrawing = false;

    if (
      this.cropArea &&
      this.cropArea.width > 10 &&
      this.cropArea.height > 10
    ) {
      // Show crop confirmation with SweetAlert
      Swal.fire({
        title: "Apply Crop?",
        text: `Crop area: ${Math.round(this.cropArea.width)} × ${Math.round(
          this.cropArea.height
        )} pixels`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, Crop Image",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) {
          this.applyCrop();

          // Show success message
          Swal.fire({
            icon: "success",
            title: "Image Cropped!",
            text: "Crop applied successfully",
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
          });
        } else {
          // Hide crop overlay if cancelled
          $("#cropOverlay").hide();
          $(".tool-btn").removeClass("active");
          this.currentTool = null;
          this.cropArea = null;
        }
      });
    }
  }

  applyCrop() {
    if (!this.cropArea) return;

    const { left, top, width, height } = this.cropArea;

    // Get the current canvas scale factors
    const canvasRect = this.canvas.getBoundingClientRect();
    const scaleX = this.canvas.width / canvasRect.width;
    const scaleY = this.canvas.height / canvasRect.height;

    // Convert crop coordinates to canvas coordinates
    const cropX = left * scaleX;
    const cropY = top * scaleY;
    const cropWidth = width * scaleX;
    const cropHeight = height * scaleY;

    // Get the cropped image data
    const imageData = this.ctx.getImageData(
      cropX,
      cropY,
      cropWidth,
      cropHeight
    );

    // Resize canvas to crop size
    this.canvas.width = cropWidth;
    this.canvas.height = cropHeight;

    // Clear and draw cropped image
    this.ctx.clearRect(0, 0, cropWidth, cropHeight);
    this.ctx.putImageData(imageData, 0, 0);

    // Hide crop overlay
    $("#cropOverlay").hide();
    $(".tool-btn").removeClass("active");
    this.currentTool = null;
    this.cropArea = null;

    this.saveState();
    this.showToolFeedback("Image cropped successfully");
  }
}

// Initialize global image editor
window.storyboardImageEditor = new StoryboardImageEditor();

// Function to open image editor from storyboard modal
window.openImageEditor = function (imageSrc, callback) {
  window.storyboardImageEditor.openEditor(imageSrc, callback);
};
