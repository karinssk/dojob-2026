/**
 * Task Modal Component - Jira-style task editing modal
 */

console.log("🔄 Loading TaskModal class...");

class TaskModal {
  constructor() {
    console.log("🔄 TaskModal constructor called");
    console.log("🔍 Constructor - this:", this);
    this.currentTask = null;
    this.currentUser = null;

    // Use local API base URL
    this.localApiBase = window.location.origin;
    this.apiBase = "https://api-dojob.rubyshop.co.th/api";

    this.instanceId = Math.random().toString(36).substr(2, 9); // Add unique ID
    console.log("🔍 TaskModal instance ID:", this.instanceId);
    console.log("🔍 Local API base:", this.localApiBase);
    console.log("🔍 External API base:", this.apiBase);

    try {
      this.init();
      console.log(
        " TaskModal initialized successfully, instance:",
        this.instanceId
      );
    } catch (error) {
      console.error("❌ Error in TaskModal constructor:", error);
      throw error;
    }
  }

  init() {
    this.createModalHTML();
    this.setupEventListeners();
  }

  createModalHTML() {
    const modalHTML = `
            <style>
                  .modal-backdrop {
                    display: none !important;
                }
                
                /* Ensure modal doesn't block interactions */
                #taskModal {
                    pointer-events: none;
                }
                
                #taskModal .modal-dialog {
                    pointer-events: auto;
                }
                /* Touch-friendly drag and drop styles */
                .subtask-item {
                    transition: all 0.2s ease;
                    user-select: none;
                    -webkit-user-select: none;
                    -moz-user-select: none;
                    -ms-user-select: none;
                }
                
                .subtask-item.dragging {
                    opacity: 0.8;
                    transform: scale(1.02);
                    z-index: 1000;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                }
                
                .drag-placeholder {
                    opacity: 0.3 !important;
                    pointer-events: none;
                    border: 2px dashed #cbd5e0;
                    background-color: #f7fafc;
                }
                
                .drag-handle {
                    touch-action: none;
                    cursor: grab;
                }
                
                .drag-handle:active {
                    cursor: grabbing;
                }
                
                .touch-manipulation {
                    touch-action: manipulation;
                }
                
                /* Larger touch targets for mobile */
                @media (max-width: 768px) {
                    .subtask-status-btn,
                    .drag-handle {
                        min-width: 44px;
                        min-height: 44px;
                    }
                    
                    .subtask-item {
                        padding: 12px 8px;
                    }
                    
                    .subtask-actions {
                        opacity: 1 !important; /* Always show action buttons on mobile */
                    }
                }
                
                /* Better visibility for action buttons */
                .subtask-item:hover .subtask-actions {
                    opacity: 1;
                }
                
                /* Ensure delete button is always visible on hover */
                .subtask-actions button {
                    transition: all 0.2s ease;
                }
                
                /* iPad specific optimizations */
                @media (min-width: 768px) and (max-width: 1024px) {
                    .drag-handle {
                        min-width: 32px;
                        min-height: 32px;
                    }
                    
                    .subtask-item {
                        padding: 10px;
                    }
                }
            </style>
            <div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true" data-bs-backdrop="false">
                <div class="modal-dialog" style="max-width: 1400px; width: 95vw;">
                    <div class="modal-content bg-white text-sm text-gray-800" style="height: 90vh; font-family: 'Inter', sans-serif;">
                        <!-- Modal Body with 70/30 Layout -->
                        <div class="flex h-full">
                            <!-- Left Side - 70% -->
                            <div class="flex-1 p-6 overflow-y-auto" style="flex: 0 0 70%;">
                                <!-- Top Header -->
                                <div class="mb-4">
                                    <div class="text-xs text-gray-500 mb-1" id="taskKey">TASK-123</div>
                                    <div class="flex items-center gap-2">
                                        <input type="text" id="taskTitle" 
                                               class="text-2xl font-semibold bg-transparent border-none outline-none flex-1" 
                                               placeholder="Task title...">
                                        <button class="w-6 h-6 border border-gray-300 rounded flex items-center justify-center hover:bg-gray-100">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="mb-6">
                                    <div class="font-semibold mb-2">Description</div>
                                    <textarea id="taskDescription" 
                                              class="w-full text-gray-700 bg-transparent border border-gray-200 rounded px-3 py-2 outline-none resize-none focus:border-blue-500" 
                                              placeholder="Add a description..." rows="4"></textarea>
                                </div>
                                
                                <!-- Subtasks -->
                                <div class="mb-6">
                                    <div class="font-semibold mb-2">Subtasks</div>
                                    <div id="childTasks">
                                        <div id="subtasksList" class="space-y-2 mb-3"></div>
                                        <button id="addChildTaskBtn" class="text-gray-400 hover:text-gray-600 text-sm flex items-center gap-1" type="button">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Add subtask
                                        </button>
                                        <div id="newSubtaskForm" class="mt-2 p-3 border rounded bg-gray-50" style="display: none;">
                                            <input type="text" id="newSubtaskTitle" placeholder="Subtask title..." 
                                                   class="w-full text-sm border rounded px-2 py-1 mb-2">
                                            <div class="flex gap-2">
                                                <button id="saveSubtaskBtn" type="button" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">Save</button>
                                                <button id="cancelSubtaskBtn" type="button" class="px-3 py-1 border text-sm rounded hover:bg-gray-100">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Attachments -->
                                <div class="mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <div class="font-semibold">Attachments</div>
                                            <span id="attachmentCount" class="bg-gray-200 text-gray-600 px-2 py-1 rounded text-xs">0</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button class="text-gray-400 hover:text-gray-600 w-6 h-6 flex items-center justify-center">⋯</button>
                                            <button id="uploadImagesBtn" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center text-xl font-bold border border-gray-300 rounded hover:bg-gray-100 hover:border-gray-400 transition-colors">+</button>
                                        </div>
                                    </div>
                                    <input type="file" id="imageUpload" multiple accept="image/*" style="display: none;">
                                    
                                    <!-- Attachments Table -->
                                    <div id="attachmentsTable" class="border rounded" style="display: none;">
                                        <div class="bg-gray-50 px-4 py-2 border-b">
                                            <div class="grid grid-cols-12 gap-4 text-xs text-gray-600 font-medium">
                                                <div class="col-span-5">Name</div>
                                                <div class="col-span-2">Size</div>
                                                <div class="col-span-3">Date added</div>
                                                <div class="col-span-2 text-right">Actions</div>
                                            </div>
                                        </div>
                                        <div id="attachmentsList" class="divide-y">
                                            <!-- Attachments will be populated here -->
                                        </div>
                                    </div>
                                    
                                    <div id="uploadProgress" class="mt-2" style="display: none;">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Section -->
                                <div>
                                    <div class="font-semibold text-gray-800 mb-3">Activity</div>
                                    <div class="flex gap-2 mb-4">
                                        <button class="px-3 py-1 text-sm rounded border border-gray-300 bg-white hover:bg-gray-100">All</button>
                                        <button class="px-3 py-1 text-sm rounded border border-blue-500 text-blue-600 bg-blue-50">Comments</button>
                                        <button class="px-3 py-1 text-sm rounded border border-gray-300 bg-white hover:bg-gray-100">History</button>
                                        <button class="px-3 py-1 text-sm rounded border border-gray-300 bg-white hover:bg-gray-100">Work log</button>
                                    </div>

                                     <!-- Comment Input -->
                                    <div class="border rounded-md mb-4">
                                        <div class="flex items-start p-3">
                                            <div class="bg-green-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold mr-3 text-sm">
                                                GS
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 border-b pb-2 mb-2">
                                                    <select class="text-sm border rounded px-1">
                                                        <option>Normal</option>
                                                        <option>Heading</option>
                                                    </select>
                                                    <button class="font-bold hover:bg-gray-100 px-1 rounded">B</button>
                                                    <button class="italic hover:bg-gray-100 px-1 rounded">I</button>
                                                    <button class="hover:bg-gray-100 px-1 rounded">•</button>
                                                    <button class="hover:bg-gray-100 px-1 rounded">1.</button>
                                                    <button class="hover:bg-gray-100 px-1 rounded">🔗</button>
                                                    <button id="attachImageBtn" class="hover:bg-gray-100 px-1 rounded">📎</button>
                                                    <button class="hover:bg-gray-100 px-1 rounded">🖼</button>
                                                    <button class="hover:bg-gray-100 px-1 rounded">😀</button>
                                                </div>
                                                <textarea id="newComment" 
                                                          class="w-full text-sm p-2 h-20 outline-none border-none resize-none" 
                                                          placeholder="this is inline edit!"></textarea>
                                                <input type="file" id="commentImageUpload" multiple accept="image/*" style="display: none;">
                                                <div id="commentImagePreview" class="mt-2" style="display: none;">
                                                    <div id="commentImages" class="flex flex-wrap gap-2"></div>
                                                </div>
                                                <div class="flex justify-end mt-2 pt-2 border-t">
                                                    <button id="addCommentBtn" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                                                        Save Comment
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                     
                                    </div>
                                    
                                    <!-- Comments List -->
                                    <div id="commentsList" class="space-y-3"></div>
                                </div>
                            </div>

                            <!-- Right Side - 30% -->
                            <div class="border-l bg-gray-50 p-6 overflow-y-auto" style="flex: 0 0 30%;">
                                <!-- Status and Actions -->
                                <div class="mb-4">
                                    <div class="flex gap-2 mb-3">
                                        <select id="taskStatus" class="border text-sm px-2 py-1 rounded bg-white flex-1">
                                            <option value="1">To Do</option>
                                            <option value="2">In Progress</option>
                                            <option value="3">Done</option>
                                        </select>
                                         <button type="button" class="text-gray-400 hover:text-gray-600" data-bs-dismiss="modal" title="Close" onclick="if(typeof window.testBoardLoading === 'function') { window.testBoardLoading(); }">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <button class="w-full border px-3 py-2 rounded bg-white hover:bg-gray-100 text-sm">✨ Improve work item</button>
                                </div>
                                
                                <!-- Details Panel -->
                                <div class="border rounded px-3 py-3 bg-white mb-4">
                                    <div class="font-medium mb-3">Details</div>
                                    
                                    <!-- Assignee -->
                                    <div class="mb-3">
                                        <label class="text-xs text-gray-500 block mb-1">Assignee</label>
                                        <select id="taskAssignee" class="w-full text-sm border rounded px-2 py-1 bg-white">
                                            <option value="0">Unassigned</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Priority -->
                                    <div class="mb-3">
                                        <label class="text-xs text-gray-500 block mb-1">Priority</label>
                                        <select id="taskPriority" class="w-full text-sm border rounded px-2 py-1 bg-white">
                                            <option value="1">Low</option>
                                            <option value="2">Medium</option>
                                            <option value="3">High</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Due Date -->
                                    <div class="mb-3">
                                        <label class="text-xs text-gray-500 block mb-1">Due date</label>
                                        <input type="datetime-local" id="taskDeadline" class="w-full text-sm border rounded px-2 py-1 bg-white">
                                    </div>
                                </div>
                                
                                <!-- Automation Panel -->
                                <div class="border rounded px-3 py-3 bg-white mb-4">
                                    <div class="font-medium mb-1">Automation</div>
                                    <div class="text-xs text-gray-500">Rule executions</div>
                                </div>
                                
                                <!-- Created/Updated Info -->
                                <div class="text-xs text-gray-500">
                                    Created <span id="taskCreated">yesterday</span><br>
                                    Updated <span id="taskUpdated">yesterday</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Add modal to body if it doesn't exist
    if (!document.getElementById("taskModal")) {
      document.body.insertAdjacentHTML("beforeend", modalHTML);
    }
  }

  setupEventListeners() {
    // Store reference to this instance for event handlers
    const modalInstance = this;
    console.log("🔧 Setting up event listeners for instance:", this.instanceId);

    // Use setTimeout to ensure elements exist
    setTimeout(() => {
      // Clear any existing event listeners to prevent duplicates
      this.clearEventListeners();

      // Upload images
      const uploadBtn = document.getElementById("uploadImagesBtn");
      const uploadInput = document.getElementById("imageUpload");

      if (uploadBtn && uploadInput) {
        const uploadClickHandler = () => {
          console.log(
            "Upload button clicked on instance:",
            modalInstance.instanceId
          );
          uploadInput.click();
        };
        const uploadChangeHandler = (e) => {
          console.log(
            "Files selected on instance:",
            modalInstance.instanceId,
            e.target.files.length
          );
          modalInstance.handleImageUpload(e.target.files);
        };

        uploadBtn.addEventListener("click", uploadClickHandler);
        uploadInput.addEventListener("change", uploadChangeHandler);

        // Store handlers for cleanup
        uploadBtn._taskModalHandler = uploadClickHandler;
        uploadInput._taskModalHandler = uploadChangeHandler;
      } else {
        console.error("Upload elements not found:", {
          uploadBtn: !!uploadBtn,
          uploadInput: !!uploadInput,
        });
      }

      // Save task changes
      const taskTitle = document.getElementById("taskTitle");
      const taskDescription = document.getElementById("taskDescription");
      const taskStatus = document.getElementById("taskStatus");
      const taskAssignee = document.getElementById("taskAssignee");
      const taskPriority = document.getElementById("taskPriority");
      const taskDeadline = document.getElementById("taskDeadline");

      if (taskTitle) {
        const handler = () => modalInstance.saveTaskField("title");
        taskTitle.addEventListener("blur", handler);
        taskTitle._taskModalHandler = handler;
      }
      if (taskDescription) {
        const handler = () => modalInstance.saveTaskField("description");
        taskDescription.addEventListener("blur", handler);
        taskDescription._taskModalHandler = handler;
      }
      if (taskStatus) {
        const handler = () => modalInstance.saveTaskField("status_id");
        taskStatus.addEventListener("change", handler);
        taskStatus._taskModalHandler = handler;
      }
      if (taskAssignee) {
        const handler = () => modalInstance.saveTaskField("assigned_to");
        taskAssignee.addEventListener("change", handler);
        taskAssignee._taskModalHandler = handler;
      }
      if (taskPriority) {
        const handler = () => modalInstance.saveTaskField("priority_id");
        taskPriority.addEventListener("change", handler);
        taskPriority._taskModalHandler = handler;
      }
      if (taskDeadline) {
        const handler = () => modalInstance.saveTaskField("deadline");
        taskDeadline.addEventListener("change", handler);
        taskDeadline._taskModalHandler = handler;
      }

      // Add comment
      const addCommentBtn = document.getElementById("addCommentBtn");
      if (addCommentBtn) {
        const handler = () => {
          console.log(
            "Add comment clicked on instance:",
            modalInstance.instanceId
          );
          modalInstance.addComment();
        };
        addCommentBtn.addEventListener("click", handler);
        addCommentBtn._taskModalHandler = handler;
      }

      // Subtask functionality
      const addChildTaskBtn = document.getElementById("addChildTaskBtn");
      const saveSubtaskBtn = document.getElementById("saveSubtaskBtn");
      const cancelSubtaskBtn = document.getElementById("cancelSubtaskBtn");
      const newSubtaskTitle = document.getElementById("newSubtaskTitle");

      if (addChildTaskBtn) {
        const handler = (e) => {
          e.preventDefault();
          e.stopPropagation();
          console.log("🔍 Add subtask button clicked");
          modalInstance.showAddSubtaskForm();
        };
        addChildTaskBtn.addEventListener("click", handler);
        addChildTaskBtn._taskModalHandler = handler;
      }

      if (saveSubtaskBtn) {
        const handler = (e) => {
          e.preventDefault();
          e.stopPropagation();
          modalInstance.saveNewSubtask();
        };
        saveSubtaskBtn.addEventListener("click", handler);
        saveSubtaskBtn._taskModalHandler = handler;
      }

      if (cancelSubtaskBtn) {
        const handler = (e) => {
          e.preventDefault();
          e.stopPropagation();
          modalInstance.hideAddSubtaskForm();
        };
        cancelSubtaskBtn.addEventListener("click", handler);
        cancelSubtaskBtn._taskModalHandler = handler;
      }

      if (newSubtaskTitle) {
        const keydownHandler = (e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            modalInstance.saveNewSubtask();
          } else if (e.key === "Escape") {
            modalInstance.hideAddSubtaskForm();
          }
        };
        newSubtaskTitle.addEventListener("keydown", keydownHandler);
        newSubtaskTitle._taskModalKeydownHandler = keydownHandler;
      }

      // Enter key in comment textarea
      const newComment = document.getElementById("newComment");
      if (newComment) {
        const keydownHandler = (e) => {
          if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            console.log(
              "Enter key pressed on instance:",
              modalInstance.instanceId
            );
            modalInstance.addComment();
          }
        };
        const pasteHandler = (e) => {
          modalInstance.handleCommentImagePaste(e);
        };

        newComment.addEventListener("keydown", keydownHandler);
        newComment.addEventListener("paste", pasteHandler);
        newComment._taskModalKeydownHandler = keydownHandler;
        newComment._taskModalPasteHandler = pasteHandler;
      }

      // Comment image upload button
      const attachImageBtn = document.getElementById("attachImageBtn");
      const commentImageUpload = document.getElementById("commentImageUpload");

      if (attachImageBtn && commentImageUpload) {
        const attachClickHandler = () => {
          commentImageUpload.click();
        };
        const uploadChangeHandler = (e) => {
          modalInstance.handleCommentImageFiles(e.target.files);
        };

        attachImageBtn.addEventListener("click", attachClickHandler);
        commentImageUpload.addEventListener("change", uploadChangeHandler);
        attachImageBtn._taskModalHandler = attachClickHandler;
        commentImageUpload._taskModalHandler = uploadChangeHandler;
      }

      console.log(
        " Event listeners set up for instance:",
        modalInstance.instanceId
      );
    }, 100);
  }

  clearEventListeners() {
    console.log("🧹 Clearing event listeners for instance:", this.instanceId);

    // List of elements that might have event listeners
    const elements = [
      "uploadImagesBtn",
      "imageUpload",
      "taskTitle",
      "taskDescription",
      "taskStatus",
      "taskAssignee",
      "taskPriority",
      "taskDeadline",
      "addCommentBtn",
      "newComment",
      "attachImageBtn",
      "commentImageUpload",
      "addChildTaskBtn",
      "saveSubtaskBtn",
      "cancelSubtaskBtn",
      "newSubtaskTitle",
    ];

    elements.forEach((id) => {
      const element = document.getElementById(id);
      if (element) {
        // Remove stored handlers
        if (element._taskModalHandler) {
          element.removeEventListener("click", element._taskModalHandler);
          element.removeEventListener("change", element._taskModalHandler);
          element.removeEventListener("blur", element._taskModalHandler);
          delete element._taskModalHandler;
        }
        if (element._taskModalKeydownHandler) {
          element.removeEventListener(
            "keydown",
            element._taskModalKeydownHandler
          );
          delete element._taskModalKeydownHandler;
        }
        if (element._taskModalPasteHandler) {
          element.removeEventListener("paste", element._taskModalPasteHandler);
          delete element._taskModalPasteHandler;
        }
      }
    });
  }

  async logActivity(action, details = {}) {
    // Log activity to database using the real rise_activity_logs table structure
    try {
      // Ensure we have a valid user ID
      const userId = this.currentUser?.id;
      if (!userId) {
        console.warn("⚠️ Cannot log activity: No valid user ID");
        return;
      }

      const activityData = {
        created_at: new Date().toISOString().slice(0, 19).replace("T", " "), // MySQL datetime format
        created_by: userId,
        action: this.mapActionToEnum(action),
        log_type: "tasks",
        log_type_title: this.currentTask?.title || "Unknown Task",
        log_type_id: this.currentTask?.id || 0,
        changes: JSON.stringify(details),
        log_for: "tasks",
        log_for_id: this.currentTask?.id || 0,
        log_for2: "projects",
        log_for_id2: this.currentTask?.project_id || 0,
        deleted: 0,
      };

      console.log("📝 Logging activity to rise_activity_logs:", {
        ...activityData,
        created_by: `${userId} (${this.currentUser.first_name} ${this.currentUser.last_name})`,
      });

      // Use local API only (has session access, external API will always return 401)
      const response = await fetch(
        `${this.localApiBase}/api/task_activity/${this.currentTask?.id || 0}`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-User-ID": userId.toString(),
          },
          credentials: "include",
          body: JSON.stringify(activityData),
        }
      );

      const result = await response.json();

      if (result.success) {
        console.log(" Activity logged successfully via local API");
      } else {
        console.warn("⚠️ Activity logging failed:", result.error);
      }
    } catch (error) {
      console.error("❌ Error logging activity:", error);
    }
  }

  mapActionToEnum(action) {
    // Map our custom actions to the database enum values
    const actionMap = {
      TASK_VIEWED: "updated",
      TASK_UPDATED: "updated",
      TASK_CREATED: "created",
      TASK_DELETED: "deleted",
      IMAGES_UPLOADED: "updated",
      IMAGE_DELETED: "updated",
      COMMENT_ADDED: "updated",
      COMMENT_DELETED: "deleted",
      COMMENT_UPDATED: "updated",
      SUBTASK_CREATED: "created",
      SUBTASK_UPDATED: "updated",
      SUBTASK_DELETED: "deleted",
      SUBTASK_STATUS_CHANGED: "updated",
      SUBTASKS_REORDERED: "updated",
    };

    return actionMap[action] || "updated";
  }

  async openTask(taskId) {
    try {
      console.log("🔍 TaskModal.openTask called with taskId:", taskId);
      console.log("🔍 openTask - instance ID:", this.instanceId);
      console.log("🔍 openTask - this:", this);

      // Validate task ID
      if (!taskId || isNaN(taskId) || taskId <= 0) {
        throw new Error(`Invalid task ID: ${taskId}`);
      }

      this.currentTask = null;

      // Check if Bootstrap is available
      if (typeof bootstrap === "undefined") {
        console.error("❌ Bootstrap not available");
        alert("Bootstrap is required for the modal to work");
        return;
      }

      // Check if modal element exists
      const modalElement = document.getElementById("taskModal");
      if (!modalElement) {
        console.error("❌ Modal element not found");
        alert("Modal element not found - creating it now");
        this.createModalHTML();
      }

      // Set task ID attribute for reference
      const taskModal = document.getElementById("taskModal");
      if (taskModal) {
        taskModal.setAttribute("data-task-id", taskId);
      }

      console.log(" Showing modal...");
      // Show modal with proper configuration
      const modal = new bootstrap.Modal(document.getElementById("taskModal"), {
        backdrop: false,
        keyboard: true,
        focus: true,
      });

      // Ensure modal content is clickable after showing
      modal.show();

      // Fix any z-index issues after modal is shown
      setTimeout(() => {
        const modalElement = document.getElementById("taskModal");
        if (modalElement) {
          modalElement.style.zIndex = "1060";
          const modalDialog = modalElement.querySelector(".modal-dialog");
          if (modalDialog) {
            modalDialog.style.zIndex = "1061";
            modalDialog.style.pointerEvents = "auto";
          }
          const modalContent = modalElement.querySelector(".modal-content");
          if (modalContent) {
            modalContent.style.zIndex = "1062";
            modalContent.style.pointerEvents = "auto";
          }
        }
      }, 100);

      // Show loading state
      this.showLoading();

      console.log("🔄 Loading task data...");
      // Load current user and task data
      await Promise.all([this.loadCurrentUser(), this.loadTaskData(taskId)]);

      // Enhanced debugging for currentTask state
      console.log("🔍 Post-loading currentTask debug:", {
        currentTask: this.currentTask,
        hasCurrentTask: !!this.currentTask,
        currentTaskId: this.currentTask?.id,
        currentTaskType: typeof this.currentTask,
        taskIdMatch: this.currentTask?.id == taskId,
        fullTaskObject: JSON.stringify(this.currentTask, null, 2),
      });

      if (this.currentTask && this.currentTask.id) {
        console.log(" Task loaded successfully, populating modal...");
        await this.populateModal();

        // Load comments and subtasks sequentially to avoid race conditions
        try {
          await this.loadComments();
          await this.loadSubtasks();
        } catch (error) {
          console.error("❌ Error loading comments/subtasks:", error);
          // Continue anyway, the modal is still functional
        }

        // Log task view activity
        await this.logActivity("TASK_VIEWED", {
          task_id: this.currentTask.id,
          task_title: this.currentTask?.title || "Unknown Task",
        });
      } else {
        console.error("❌ Failed to load task data");
        console.error("❌ currentTask is:", this.currentTask);
        console.error("❌ Expected task ID:", taskId);
        throw new Error(`Failed to load task with ID: ${taskId}`);
      }
    } catch (error) {
      console.error("❌ Error opening task:", error);
      this.showError(`Failed to load task: ${error.message}`);
    }
  }

  async loadCurrentUser() {
    try {
      console.log("🔄 Loading current user from local API...");

      // First check authentication status
      const authResponse = await fetch(
        `${window.location.origin}/api/auth_check`,
        {
          credentials: "include",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
        }
      );

      const authResult = await authResponse.json();
      console.log("🔍 Auth check result:", authResult);

      if (!authResult.authenticated) {
        console.log("⚠️ User not authenticated, redirecting to login...");
        // Redirect to login page or show login modal
        window.location.href = "/signin";
        return;
      }

      // Use local Api.php instead of external API
      const response = await fetch(
        `${window.location.origin}/api/current_user`,
        {
          credentials: "include",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
        }
      );

      const result = await response.json();
      console.log("📋 Current user API response:", result);

      if (result.success && result.authenticated) {
        this.currentUser = result.data;
        console.log(" Current user loaded from local API:", {
          id: this.currentUser.id,
          first_name: this.currentUser.first_name,
          last_name: this.currentUser.last_name,
          email: this.currentUser.email,
          full_name: this.currentUser.full_name,
        });
      } else {
        console.log("⚠️ Local API returned error:", result.error);
        console.log("🔍 Debug info:", result.debug);

        // If not authenticated, redirect to login
        if (!result.authenticated) {
          console.log("🔄 Redirecting to login page...");
          window.location.href = "/signin";
          return;
        }

        // Fallback to external API if local fails but user is authenticated
        console.log("🔄 Falling back to external API...");
        const externalResponse = await fetch(`${this.apiBase}/current-user`, {
          credentials: "include",
        });
        const externalResult = await externalResponse.json();

        if (externalResult.success) {
          this.currentUser = externalResult.data || externalResult.user;
          console.log(
            " Current user loaded from external API:",
            this.currentUser
          );
        } else {
          console.log("⚠️ External API also failed, using default user");
          this.currentUser = { id: 1, first_name: "User", last_name: "" };
        }
      }
    } catch (error) {
      console.error("❌ Error loading current user:", error);
      // Use default user if failed
      this.currentUser = { id: 1, first_name: "User", last_name: "" };
    }
  }

  async loadTaskData(taskId) {
    try {
      console.log(`🔄 Loading task data for ID: ${taskId}`);

      // Try local API first since it has session access
      console.log(`🔄 Trying local API first...`);
      const localUrl = `${this.localApiBase}/api/task/${taskId}`;
      console.log(`🔗 Local API URL: ${localUrl}`);

      let response = await fetch(localUrl, {
        credentials: "include",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      console.log(`📡 Local API response status: ${response.status}`);

      if (response.ok) {
        const result = await response.json();
        console.log("📋 Local API response:", result);

        if (result.success && result.data) {
          this.currentTask = result.data;
          console.log(" Task data loaded from local API:", {
            id: this.currentTask.id,
            title: this.currentTask.title,
            status_id: this.currentTask.status_id,
            hasRequiredFields: !!(
              this.currentTask.id && this.currentTask.title
            ),
          });

          // Ensure task has an ID
          if (!this.currentTask.id) {
            throw new Error("Task data is missing required ID field");
          }
          return; // Success, exit early
        } else {
          throw new Error(
            result.error || result.message || "Local API returned invalid data"
          );
        }
      } else {
        throw new Error(
          `Local API failed: HTTP ${response.status}: ${response.statusText}`
        );
      }
    } catch (localError) {
      console.warn(
        "⚠️ Local API failed, trying external API:",
        localError.message
      );

      // Fallback to external API
      try {
        console.log(`🔄 Trying external API...`);
        const externalUrl = `${this.apiBase}/task/${taskId}`;
        console.log(`🔗 External API URL: ${externalUrl}`);

        const externalResponse = await fetch(externalUrl, {
          credentials: "include",
        });

        console.log(
          `📡 External API response status: ${externalResponse.status}`
        );

        if (!externalResponse.ok) {
          throw new Error(
            `External API failed: HTTP ${externalResponse.status}: ${externalResponse.statusText}`
          );
        }

        const externalResult = await externalResponse.json();
        console.log("📋 External API response:", externalResult);

        if (externalResult.success && externalResult.data) {
          this.currentTask = externalResult.data;
          console.log(" Task data loaded from external API:", {
            id: this.currentTask.id,
            title: this.currentTask.title,
            status_id: this.currentTask.status_id,
            hasRequiredFields: !!(
              this.currentTask.id && this.currentTask.title
            ),
          });

          // Ensure task has an ID
          if (!this.currentTask.id) {
            throw new Error("External task data is missing required ID field");
          }
          return; // Success with external API
        } else {
          throw new Error(
            externalResult.error ||
              externalResult.message ||
              "External API returned invalid data"
          );
        }
      } catch (externalError) {
        console.error("❌ Both local and external APIs failed");
        console.error("❌ Local error:", localError.message);
        console.error("❌ External error:", externalError.message);
        console.error("❌ Task ID:", taskId);

        this.currentTask = null;
        throw new Error(
          `Failed to load task from both APIs. Local: ${localError.message}, External: ${externalError.message}`
        );
      }
    }
  }

  async populateModal() {
    const task = this.currentTask;

    if (!task || !task.id) {
      console.error("❌ Cannot populate modal: Invalid task data");
      console.error("🔍 Task data:", task);
      throw new Error("Invalid task data for modal population");
    }

    console.log("🔄 Populating modal with task data:", {
      id: task.id,
      title: task.title,
      status_id: task.status_id,
      project_id: task.project_id,
    });

    // Basic info
    document.getElementById("taskKey").textContent = `TASK-${task.id}`;
    document.getElementById("taskTitle").value = task.title || "";
    document.getElementById("taskDescription").value = task.description || "";
    document.getElementById("taskStatus").value = task.status_id || 1;
    document.getElementById("taskAssignee").value = task.assigned_to || 0;
    document.getElementById("taskPriority").value = task.priority_id || 1;

    // Format deadline for datetime-local input
    if (task.deadline) {
      const deadline = new Date(task.deadline);
      document.getElementById("taskDeadline").value = deadline
        .toISOString()
        .slice(0, 16);
    }

    // Load dropdowns
    await this.loadDropdowns();

    // Load images
    this.displayImages();

    // Dates
    if (task.created_date) {
      document.getElementById("taskCreated").textContent = this.formatDate(
        task.created_date
      );
    }
    if (task.status_changed_at) {
      document.getElementById("taskUpdated").textContent = this.formatDate(
        task.status_changed_at
      );
    }
  }

  async loadDropdowns() {
    try {
      // Load users for assignee dropdown
      const usersResponse = await fetch(`${this.apiBase}/users`);
      const usersResult = await usersResponse.json();

      if (usersResult.success) {
        const assigneeSelect = document.getElementById("taskAssignee");
        assigneeSelect.innerHTML = '<option value="0">Unassigned</option>';

        usersResult.data.forEach((user) => {
          const option = document.createElement("option");
          option.value = user.id;
          option.textContent = `${user.first_name} ${user.last_name}`;
          if (user.id == this.currentTask.assigned_to) {
            option.selected = true;
          }
          assigneeSelect.appendChild(option);
        });
      }

      // Load statuses
      const statusResponse = await fetch(`${this.apiBase}/task-statuses`);
      const statusResult = await statusResponse.json();

      if (statusResult.success) {
        const statusSelect = document.getElementById("taskStatus");
        statusSelect.innerHTML = "";

        statusResult.data.forEach((status) => {
          const option = document.createElement("option");
          option.value = status.id;
          option.textContent = status.title;
          if (status.id == this.currentTask.status_id) {
            option.selected = true;
          }
          statusSelect.appendChild(option);
        });
      }

      // Load priorities
      const priorityResponse = await fetch(`${this.apiBase}/task-priorities`);
      const priorityResult = await priorityResponse.json();

      if (priorityResult.success) {
        const prioritySelect = document.getElementById("taskPriority");
        prioritySelect.innerHTML = "";

        priorityResult.data.forEach((priority) => {
          const option = document.createElement("option");
          option.value = priority.id;
          option.textContent = priority.title;
          if (priority.id == this.currentTask.priority_id) {
            option.selected = true;
          }
          prioritySelect.appendChild(option);
        });
      }
    } catch (error) {
      console.error("Error loading dropdowns:", error);
    }
  }

  displayImages() {
    const attachmentsList = document.getElementById("attachmentsList");
    const attachmentCount = document.getElementById("attachmentCount");
    const attachmentsTable = document.getElementById("attachmentsTable");

    attachmentsList.innerHTML = "";

    let images = [];
    try {
      images = this.currentTask.images
        ? JSON.parse(this.currentTask.images)
        : [];
    } catch (e) {
      images = this.currentTask.images || [];
    }

    // Update attachment count
    attachmentCount.textContent = images.length;

    if (images.length === 0) {
      attachmentsTable.style.display = "none";
      return;
    }

    attachmentsTable.style.display = "block";

    images.forEach((image, index) => {
      // Handle different image formats
      let imageUrl = "";
      let filename = "";
      let originalname = "Image";
      let fileSize = "Unknown";
      let dateAdded = "Unknown";

      if (typeof image === "string") {
        imageUrl = image;
        filename = image;
        originalname = image.split("/").pop() || "Image";
      } else if (typeof image === "object" && image !== null) {
        imageUrl =
          image.url ||
          `https://dojob.rubyshop.co.th/files/timeline_files/${
            image.filename || image.file_name
          }`;
        filename = image.filename || image.file_name || index;
        originalname =
          image.originalname ||
          image.original_name ||
          image.filename ||
          image.file_name ||
          "Image";

        // Format file size
        if (image.size || image.file_size) {
          const bytes = image.size || image.file_size;
          if (bytes < 1024) {
            fileSize = bytes + " B";
          } else if (bytes < 1024 * 1024) {
            fileSize = Math.round(bytes / 1024) + " KB";
          } else {
            fileSize = Math.round(bytes / (1024 * 1024)) + " MB";
          }
        }

        // Format date
        if (image.created_at || image.uploadedAt) {
          const date = new Date(image.created_at || image.uploadedAt);
          dateAdded = date.toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
          });
        }
      }

      if (!imageUrl) return;

      // Get file extension for icon
      const extension = originalname.split(".").pop()?.toLowerCase() || "file";
      const isImage = ["jpg", "jpeg", "png", "gif", "webp", "svg"].includes(
        extension
      );

      const attachmentHtml = `
                <div class="px-4 py-3 hover:bg-gray-50 group">
                    <div class="grid grid-cols-12 gap-4 items-center">
                        <div class="col-span-5 flex items-center gap-3">
                            <div class="w-10 h-10 rounded flex items-center justify-center flex-shrink-0 overflow-hidden border">
                                ${
                                  isImage
                                    ? `<img src="${imageUrl}" class="w-full h-full object-cover cursor-pointer hover:opacity-80" 
                                           onclick="window.getTaskModalInstance().viewImage('${imageUrl}')" 
                                           title="${originalname}" 
                                           onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                       <div class="w-full h-full bg-blue-100 flex items-center justify-center" style="display: none;">
                                         <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                           <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                         </svg>
                                       </div>`
                                    : `<div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                         <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                           <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                         </svg>
                                       </div>`
                                }
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900 truncate cursor-pointer hover:text-blue-600" 
                                     onclick="window.getTaskModalInstance().viewImage('${imageUrl}')" 
                                     title="${originalname}">
                                    ${originalname}
                                </div>
                            </div>
                        </div>
                        <div class="col-span-2 text-sm text-gray-500">${fileSize}</div>
                        <div class="col-span-3 text-sm text-gray-500">${dateAdded}</div>
                        <div class="col-span-2 flex items-center justify-end gap-2">
                            <button class="w-8 h-8 text-blue-600 hover:text-blue-800 opacity-100 transition-all duration-200 flex items-center justify-center rounded hover:bg-blue-50" 
                                    onclick="window.getTaskModalInstance().viewImage('${imageUrl}')" 
                                    title="View file">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button class="w-8 h-8 text-white bg-red-500 hover:bg-red-600 opacity-100 transition-all duration-200 flex items-center justify-center rounded" 
                                    onclick="window.getTaskModalInstance().confirmDeleteImage('${filename}', '${originalname.replace(
        /'/g,
        "\\'"
      )}'); event.stopPropagation();" 
                                    title="Delete file">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
      attachmentsList.insertAdjacentHTML("beforeend", attachmentHtml);
    });
  }

  async handleImageUpload(files) {
    if (!files || files.length === 0) return;

    // Check if currentTask is available
    if (!this.currentTask || !this.currentTask.id) {
      console.error(
        "❌ Cannot upload images: currentTask is null or missing ID"
      );
      this.showError("Cannot upload images: Task not loaded");
      return;
    }

    const formData = new FormData();
    Array.from(files).forEach((file) => {
      formData.append("images", file);
    });

    try {
      // Show progress
      const progressElement = document.getElementById("uploadProgress");
      if (progressElement) {
        progressElement.style.display = "block";
        const progressBar = progressElement.querySelector(".bg-blue-600");
        if (progressBar) {
          progressBar.style.width = "30%";
        }
      }

      const response = await fetch(
        `${this.apiBase}/task/${this.currentTask.id}/upload`,
        {
          method: "POST",
          body: formData,
        }
      );

      const result = await response.json();

      if (result.success) {
        // Reload task to get updated images
        await this.reloadTask();
        this.showSuccess("Images uploaded successfully");

        // Log image upload activity
        await this.logActivity("IMAGES_UPLOADED", {
          files_count: files.length,
          file_names: Array.from(files).map((f) => f.name),
          task_title: this.currentTask?.title || "Unknown Task",
        });
      } else {
        this.showError(result.error || "Failed to upload images");
      }
    } catch (error) {
      console.error("Error uploading images:", error);
      this.showError("Failed to upload images");
    } finally {
      const progressElement = document.getElementById("uploadProgress");
      const uploadInput = document.getElementById("imageUpload");

      if (progressElement) {
        progressElement.style.display = "none";
      }
      if (uploadInput) {
        uploadInput.value = "";
      }
    }
  }

  confirmDeleteImage(filename, originalname = "this file") {
    console.log("🔍 confirmDeleteImage called with:", {
      filename,
      originalname,
      instanceId: this.instanceId,
      currentTask: this.currentTask,
    });

    // Use SweetAlert2 for confirmation
    Swal.fire({
      title: "Delete File",
      text: `Are you sure you want to delete "${originalname}"? This action cannot be undone.`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
      focusCancel: true,
    }).then((result) => {
      if (result.isConfirmed) {
        // Ensure we're calling deleteImage on the correct instance
        const modalInstance = window.getTaskModalInstance();
        console.log(
          "🔍 Calling deleteImage on instance:",
          modalInstance.instanceId
        );
        modalInstance.deleteImage(filename);
      }
    });
  }

  async deleteImage(filename) {
    // Debug logging
    console.log("🔍 deleteImage called with:", {
      filename,
      instanceId: this.instanceId,
      currentTask: this.currentTask,
      hasCurrentTask: !!this.currentTask,
      currentTaskId: this.currentTask?.id,
    });

    // Check if currentTask is available
    if (!this.currentTask || !this.currentTask.id) {
      console.error(
        "❌ Cannot delete image: currentTask is null or missing ID"
      );

      // Try to recover task ID from modal
      const modal = document.getElementById("taskModal");
      const taskIdFromModal = modal?.getAttribute("data-task-id");

      if (taskIdFromModal) {
        console.log(
          "🔄 Attempting to reload task from modal attribute:",
          taskIdFromModal
        );
        try {
          await this.loadTaskData(parseInt(taskIdFromModal));
          if (this.currentTask && this.currentTask.id) {
            console.log(
              " Task reloaded successfully, retrying image deletion"
            );
            return this.deleteImage(filename);
          }
        } catch (error) {
          console.error("❌ Failed to reload task:", error);
        }
      }

      this.showError("Cannot delete image: Task not loaded");
      return;
    }

    try {
      console.log(
        "🔄 Deleting image:",
        filename,
        "from task:",
        this.currentTask.id
      );

      // Try local API first
      let response = await fetch(
        `${this.localApiBase}/api/task/${this.currentTask.id}/image/${filename}`,
        {
          method: "DELETE",
          credentials: "include",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "X-User-ID": this.currentUser?.id || "1",
          },
        }
      );

      if (!response.ok) {
        console.log("⚠️ Local API failed, trying external API...");
        // Fallback to external API
        response = await fetch(
          `${this.apiBase}/task/${this.currentTask.id}/image/${filename}`,
          {
            method: "DELETE",
            credentials: "include",
            headers: {
              "X-User-ID": this.currentUser?.id || "1",
            },
          }
        );
      }

      const result = await response.json();
      console.log("📋 Delete image response:", result);

      if (result.success) {
        await this.reloadTask();
        this.showSuccess("Image deleted successfully");

        // Log image deletion activity
        await this.logActivity("IMAGE_DELETED", {
          filename: filename,
          task_title: this.currentTask?.title || "Unknown Task",
        });
      } else {
        this.showError(result.error || "Failed to delete image");
      }
    } catch (error) {
      console.error("❌ Error deleting image:", error);
      this.showError("Failed to delete image: " + error.message);
    }
  }

  viewImage(imageUrl) {
    // Create simple image viewer without Bootstrap modal
    const viewerHtml = `
            <div id="imageViewer" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 1070;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            ">
                <div style="
                    position: relative;
                    max-width: 90%;
                    max-height: 90%;
                    cursor: default;
                " onclick="event.stopPropagation()">
                    <button onclick="document.getElementById('imageViewer').remove()" style="
                        position: absolute;
                        top: -10px;
                        right: -10px;
                        background: white;
                        border: none;
                        border-radius: 50%;
                        width: 30px;
                        height: 30px;
                        cursor: pointer;
                        font-size: 18px;
                        font-weight: bold;
                        z-index: 1071;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                    ">&times;</button>
                    <img src="${imageUrl}" style="
                        max-width: 100%;
                        max-height: 80vh;
                        border-radius: 8px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
                    ">
                </div>
            </div>
        `;

    // Remove existing viewer
    const existingViewer = document.getElementById("imageViewer");
    if (existingViewer) {
      existingViewer.remove();
    }

    // Add new viewer
    document.body.insertAdjacentHTML("beforeend", viewerHtml);

    // Add click outside to close
    const imageViewer = document.getElementById("imageViewer");
    imageViewer.addEventListener("click", function () {
      this.remove();
    });

    // Add escape key to close
    const escapeHandler = function (e) {
      if (e.key === "Escape") {
        const viewer = document.getElementById("imageViewer");
        if (viewer) {
          viewer.remove();
        }
        document.removeEventListener("keydown", escapeHandler);
      }
    };
    document.addEventListener("keydown", escapeHandler);
  }

  async saveTaskField(field) {
    if (!this.currentTask) return;

    const fieldMap = {
      title: "taskTitle",
      description: "taskDescription",
      status_id: "taskStatus",
      assigned_to: "taskAssignee",
      priority_id: "taskPriority",
      deadline: "taskDeadline",
    };

    const element = document.getElementById(fieldMap[field]);
    const value = element.value;

    // Don't save if value hasn't changed
    if (value === this.currentTask[field]) return;

    try {
      const updates = {};
      updates[field] = value;

      const response = await fetch(
        `${this.apiBase}/task/${this.currentTask.id}`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            "X-User-ID": this.currentUser?.id || "1",
          },
          body: JSON.stringify(updates),
        }
      );

      const result = await response.json();

      if (result.success) {
        const oldValue = this.currentTask[field];
        this.currentTask[field] = value;
        this.showSuccess("Task updated");

        // Log field update activity
        await this.logActivity("TASK_UPDATED", {
          field: field,
          old_value: oldValue,
          new_value: value,
          task_title: this.currentTask?.title || "Unknown Task",
        });

        // Refresh kanban board if it exists
        if (window.kanbanBoard) {
          setTimeout(() => window.kanbanBoard.loadTasks(), 500);
        }
      } else {
        this.showError(result.error || "Failed to update task");
        // Revert value
        element.value = this.currentTask[field] || "";
      }
    } catch (error) {
      console.error("Error saving task field:", error);
      this.showError("Failed to update task");
      element.value = this.currentTask[field] || "";
    }
  }

  async loadComments() {
    // Check if currentTask is available
    if (!this.currentTask || !this.currentTask.id) {
      console.error(
        "❌ Cannot load comments: currentTask is null or missing ID"
      );
      return;
    }

    try {
      const response = await fetch(
        `${this.apiBase}/task/${this.currentTask.id}/comments`,
        {
          credentials: "include",
        }
      );
      const result = await response.json();

      if (result.success) {
        this.displayComments(result.data);
      }
    } catch (error) {
      console.error("Error loading comments:", error);
    }
  }

  displayComments(comments) {
    const commentsList = document.getElementById("commentsList");

    if (comments.length === 0) {
      commentsList.innerHTML =
        '<div class="text-gray-400 text-center py-3">No comments yet</div>';
      return;
    }

    commentsList.innerHTML = comments
      .map((comment) => {
        // Parse comment images if they exist
        let images = [];
        try {
          images = comment.images ? JSON.parse(comment.images) : [];
        } catch (e) {
          images = comment.images || [];
        }

        const imagesHtml =
          images.length > 0
            ? `
                <div class="mt-2">
                    <div class="flex flex-wrap gap-2">
                        ${images
                          .map((image) => {
                            let imageUrl = "";
                            let filename = "Image";
                            console.log("filename tests", images[0].filename);
                            if (typeof image === "string") {
                              imageUrl = image;
                              console.log("filename tests", imageUrl);
                            } else if (
                              typeof image === "object" &&
                              image !== null
                            ) {
                              imageUrl =
                                image.url ||
                                `https://dojob.rubyshop168.com/files/timeline_files/${images[0].filename}`;
                              filename =
                                image.originalname ||
                                image.original_name ||
                                image.filename ||
                                image.file_name ||
                                "Image";
                            }

                            return `
                                <div class="relative">
                                    <img src="${imageUrl}" class="w-20 h-20 object-cover rounded border cursor-pointer hover:opacity-80" 
                                         onclick="window.taskModal.viewImage('${imageUrl}')"
                                         title="${filename}">
                                </div>
                            `;
                          })
                          .join("")}
                    </div>
                </div>
            `
            : "";

        return `
                <div class="flex mb-4 p-3 border rounded bg-gray-50">
                    <div class="mr-3">
                        <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm">
                            ${comment.author_name.charAt(0).toUpperCase()}
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center mb-1">
                            <span class="font-semibold text-gray-800 mr-2">${
                              comment.author_name
                            }</span>
                            <span class="text-xs text-gray-500">${this.formatDate(
                              comment.created_at
                            )}</span>
                        </div>
                        ${
                          comment.description
                            ? `<div class="text-gray-700 text-sm">${comment.description}</div>`
                            : ""
                        }
                        ${imagesHtml}
                    </div>
                </div>
            `;
      })
      .join("");
  }

  // Comment image handling
  commentImages = [];

  handleCommentImagePaste(e) {
    const items = e.clipboardData.items;

    for (let i = 0; i < items.length; i++) {
      const item = items[i];

      if (item.type.indexOf("image") !== -1) {
        e.preventDefault();
        const file = item.getAsFile();
        this.addCommentImage(file);
      }
    }
  }

  handleCommentImageFiles(files) {
    Array.from(files).forEach((file) => {
      if (file.type.startsWith("image/")) {
        this.addCommentImage(file);
      }
    });
    // Clear the input
    document.getElementById("commentImageUpload").value = "";
  }

  addCommentImage(file) {
    // Create a unique ID for this image
    const imageId = Date.now() + "_" + Math.random().toString(36).substr(2, 9);

    // Add to our images array
    this.commentImages.push({
      id: imageId,
      file: file,
      name: file.name,
    });

    // Set default comment text if this is the first image and textarea is empty
    const textarea = document.getElementById("newComment");
    if (this.commentImages.length === 1 && !textarea.value.trim()) {
      textarea.value = "additional images";
      // Select the text so user can easily replace it
      textarea.select();
      textarea.focus();
    }

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      this.displayCommentImagePreview(imageId, e.target.result, file.name);
    };
    reader.readAsDataURL(file);
  }

  displayCommentImagePreview(imageId, dataUrl, fileName) {
    const previewContainer = document.getElementById("commentImagePreview");
    const imagesContainer = document.getElementById("commentImages");

    const imageHtml = `
            <div class="position-relative d-inline-block" data-image-id="${imageId}">
                <img src="${dataUrl}" class="rounded border" style="width: 60px; height: 60px; object-fit: cover;">
                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" 
                        style="width: 20px; height: 20px; padding: 0; border-radius: 50%; transform: translate(50%, -50%);"
                        onclick="window.taskModal.removeCommentImage('${imageId}')">
                    <i class="fas fa-times" style="font-size: 8px;"></i>
                </button>
                <div class="small text-muted text-center mt-1" style="font-size: 10px; max-width: 60px; overflow: hidden; text-overflow: ellipsis;">
                    ${fileName}
                </div>
            </div>
        `;

    imagesContainer.insertAdjacentHTML("beforeend", imageHtml);
    previewContainer.style.display = "block";
  }

  removeCommentImage(imageId) {
    // Remove from array
    this.commentImages = this.commentImages.filter((img) => img.id !== imageId);

    // Remove from preview
    const imageElement = document.querySelector(`[data-image-id="${imageId}"]`);
    if (imageElement) {
      imageElement.remove();
    }

    // Hide preview container if no images
    if (this.commentImages.length === 0) {
      document.getElementById("commentImagePreview").style.display = "none";

      // Clear default text if no images left and text is still default
      const textarea = document.getElementById("newComment");
      if (textarea.value.trim() === "additional images") {
        textarea.value = "";
      }
    }
  }

  clearCommentImages() {
    this.commentImages = [];
    document.getElementById("commentImages").innerHTML = "";
    document.getElementById("commentImagePreview").style.display = "none";

    // Clear default text if it's still there
    const textarea = document.getElementById("newComment");
    if (textarea.value.trim() === "additional images") {
      textarea.value = "";
    }
  }

  async addComment() {
    console.log("🔍 addComment called on instance:", this.instanceId);
    const textarea = document.getElementById("newComment");
    const description = textarea.value.trim();

    if (!description && this.commentImages.length === 0) return;

    // Check if currentTask and currentUser are available
    console.log("🔍 addComment - state check:", {
      instanceId: this.instanceId,
      currentTask: this.currentTask,
      hasCurrentTask: !!this.currentTask,
      currentTaskId: this.currentTask?.id,
      currentUser: this.currentUser,
      hasCurrentUser: !!this.currentUser,
      currentUserId: this.currentUser?.id,
      currentUserType: typeof this.currentUser,
      apiBase: this.apiBase,
      modalElementExists: !!document.getElementById("taskModal"),
    });

    if (!this.currentTask || !this.currentTask.id) {
      console.error("❌ Cannot add comment: currentTask is null or missing ID");
      console.error("❌ currentTask state:", this.currentTask);
      console.error("❌ This might indicate a task loading failure");

      // Try to reload the task if we can identify it from the modal
      const taskKey = document.getElementById("taskKey")?.textContent;
      if (taskKey) {
        const taskIdFromModal = taskKey.replace("TASK-", "");
        console.log(
          "🔄 Attempting to reload task from modal:",
          taskIdFromModal
        );

        try {
          await this.loadTaskData(taskIdFromModal);
          if (this.currentTask && this.currentTask.id) {
            console.log(" Task reloaded successfully, retrying comment");
            // Retry the comment operation
            return this.addComment();
          }
        } catch (reloadError) {
          console.error("❌ Failed to reload task:", reloadError);
        }
      }

      this.showError(
        "Cannot add comment: Task not loaded properly. Please close and reopen the task."
      );
      return;
    }

    // Check if currentUser is available
    if (!this.currentUser || !this.currentUser.id) {
      console.error("❌ Cannot add comment: currentUser is null or missing ID");
      console.error("❌ currentUser state:", this.currentUser);

      // Try to reload current user
      try {
        console.log("🔄 Attempting to reload current user...");
        await this.loadCurrentUser();
        if (this.currentUser && this.currentUser.id) {
          console.log(" User reloaded successfully, retrying comment");
          // Retry the comment operation
          return this.addComment();
        }
      } catch (reloadError) {
        console.error("❌ Failed to reload user:", reloadError);
      }

      this.showError(
        "Cannot add comment: User not authenticated. Please refresh the page and try again."
      );
      return;
    }

    try {
      let commentData = { description: description || "" };

      // If we have images, upload them first
      if (this.commentImages.length > 0) {
        const formData = new FormData();
        formData.append("description", description || "");

        // Add current user information to the request
        formData.append("user_id", this.currentUser.id);
        formData.append(
          "user_name",
          this.currentUser.full_name ||
            `${this.currentUser.first_name} ${this.currentUser.last_name}`.trim()
        );

        console.log("📝 Adding comment with user info:", {
          user_id: this.currentUser.id,
          user_name:
            this.currentUser.full_name ||
            `${this.currentUser.first_name} ${this.currentUser.last_name}`.trim(),
          has_images: true,
          images_count: this.commentImages.length,
        });

        this.commentImages.forEach((imageData, index) => {
          formData.append("images", imageData.file);
        });

        const response = await fetch(
          `${this.apiBase}/task/${this.currentTask.id}/comments`,
          {
            method: "POST",
            credentials: "include",
            headers: {
              // Add user ID header for Node.js backend
              "X-User-ID": this.currentUser.id.toString(),
            },
            body: formData,
          }
        );

        const result = await response.json();

        console.log("📝 Comment response:", {
          status: response.status,
          success: result.success,
          error: result.error,
          data: result.data,
        });

        if (result.success) {
          textarea.value = "";
          this.clearCommentImages();
          await this.loadComments();
          this.showSuccess("Comment added with images");

          // Log comment with images activity
          await this.logActivity("COMMENT_ADDED", {
            comment_text: description || "Image attachment",
            has_images: true,
            images_count: this.commentImages.length,
            task_title: this.currentTask?.title || "Unknown Task",
          });
        } else {
          console.error("Comment error:", result.error);
          this.showError(result.error || "Failed to add comment");
        }
      } else {
        // No images, use JSON
        commentData.user_id = this.currentUser.id;
        commentData.user_name =
          this.currentUser.full_name ||
          `${this.currentUser.first_name} ${this.currentUser.last_name}`.trim() ||
          "Unknown User";

        console.log("📝 Adding comment with user info:", {
          user_id: this.currentUser.id,
          user_name: commentData.user_name,
          has_images: false,
          description: description,
        });

        const response = await fetch(
          `${this.apiBase}/task/${this.currentTask.id}/comments`,
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              // Add user ID header for Node.js backend
              "X-User-ID": this.currentUser.id.toString(),
            },
            credentials: "include",
            body: JSON.stringify(commentData),
          }
        );

        const result = await response.json();

        if (result.success) {
          textarea.value = "";
          await this.loadComments();
          this.showSuccess("Comment added");

          // Log text comment activity
          await this.logActivity("COMMENT_ADDED", {
            comment_text: description,
            has_images: false,
            task_title: this.currentTask?.title || "Unknown Task",
          });
        } else {
          this.showError(result.error || "Failed to add comment");
        }
      }
    } catch (error) {
      console.error("Error adding comment:", error);
      this.showError("Failed to add comment");
    }
  }

  async reloadTask() {
    if (!this.currentTask) {
      console.warn("⚠️ Cannot reload task: currentTask is null");
      return;
    }

    try {
      console.log("🔄 Reloading task:", this.currentTask.id);

      // Try local API first
      let response = await fetch(
        `${this.localApiBase}/api/task/${this.currentTask.id}`,
        {
          credentials: "include",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
        }
      );

      if (!response.ok) {
        // Fallback to external API
        response = await fetch(`${this.apiBase}/task/${this.currentTask.id}`, {
          credentials: "include",
        });
      }

      const result = await response.json();

      if (result.success && result.data) {
        this.currentTask = result.data;
        this.displayImages();
        console.log(" Task reloaded successfully");
      } else {
        console.error("❌ Failed to reload task:", result.error);
      }
    } catch (error) {
      console.error("❌ Error reloading task:", error);
    }
  }

  showLoading() {
    document.getElementById("taskTitle").value = "Loading...";
    document.getElementById("taskDescription").value =
      "Loading task details...";
  }

  showSuccess(message) {
    // Use SweetAlert2 for success notifications - positioned at top right
    Swal.fire({
      icon: "success",
      title: "Success!",
      text: message,
      confirmButtonColor: "#0052CC",
      timer: 1500,
      showConfirmButton: false,
      position: "top-end",
      toast: true,
      background: "#fff",
      iconColor: "#10B981",
      customClass: {
        popup: "swal2-toast-custom",
      },
    });
  }

  showError(message) {
    // Use your existing notification system or create a simple one
    console.error("Error:", message);
    alert(message); // Simple fallback
  }

  formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + " " + date.toLocaleTimeString();
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Subtask Management Methods
  async loadSubtasks() {
    if (!this.currentTask || !this.currentTask.id) {
      console.error(
        "❌ Cannot load subtasks: currentTask is null or missing ID"
      );
      console.error("🔍 Debug info:", {
        currentTask: this.currentTask,
        hasCurrentTask: !!this.currentTask,
        currentTaskId: this.currentTask?.id,
        instanceId: this.instanceId,
      });

      // Try to get task ID from modal if available
      const modal = document.getElementById("taskModal");
      const taskIdFromModal = modal?.getAttribute("data-task-id");

      if (taskIdFromModal) {
        console.log(
          "🔄 Found task ID in modal attribute, attempting to load task:",
          taskIdFromModal
        );
        try {
          await this.loadTaskData(parseInt(taskIdFromModal));
          if (this.currentTask && this.currentTask.id) {
            console.log(
              " Successfully loaded task data, retrying subtasks load"
            );
            // Retry the subtasks loading now that we have task data
            return this.loadSubtasks();
          }
        } catch (error) {
          console.error("❌ Failed to load task data for subtasks:", error);
        }
      }

      // Still no task data, display empty subtasks
      this.displaySubtasks([]);
      return;
    }

    try {
      console.log("🔄 Loading subtasks for task:", this.currentTask.id);

      // Try local API first since it has session access
      let response = await fetch(
        `${this.localApiBase}/api/task_subtasks/${this.currentTask.id}`,
        {
          credentials: "include",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
        }
      );

      if (!response.ok) {
        // Fallback to external API
        response = await fetch(
          `${this.apiBase}/task/${this.currentTask.id}/subtasks`,
          {
            credentials: "include",
          }
        );
      }

      const result = await response.json();

      if (result.success) {
        this.displaySubtasks(result.data);
        console.log(" Subtasks loaded:", result.data.length);
      } else {
        console.warn("⚠️ Failed to load subtasks:", result.error);
        this.displaySubtasks([]);
      }
    } catch (error) {
      console.error("❌ Error loading subtasks:", error);
      this.displaySubtasks([]);
    }
  }

  displaySubtasks(subtasks) {
    const subtasksList = document.getElementById("subtasksList");

    if (!subtasks || subtasks.length === 0) {
      subtasksList.innerHTML = "";
      return;
    }

    subtasksList.innerHTML = subtasks
      .map((subtask, index) => {
        const statusIcon = subtask.status_id === 3 ? "" : "⭕";
        const statusClass =
          subtask.status_id === 3 ? "line-through text-gray-500" : "";

        // Escape HTML in title to prevent XSS and display issues
        const escapedTitle = this.escapeHtml(subtask.title || "");

        return `
        <div class="subtask-item flex items-center gap-2 p-2 border rounded bg-white hover:bg-gray-50 group touch-manipulation" 
             data-subtask-id="${subtask.id}" 
             data-sort-order="${subtask.sort || index}"
             draggable="true">
          <!-- Drag Handle -->
          <div class="drag-handle w-4 h-4 flex items-center justify-center text-gray-400 hover:text-gray-600 cursor-grab active:cursor-grabbing touch-manipulation" 
               title="Drag to reorder">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
              <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
            </svg>
          </div>
          
          <button class="subtask-status-btn w-5 h-5 flex items-center justify-center text-sm hover:bg-gray-100 rounded touch-manipulation" 
                  onclick="window.getTaskModalInstance().toggleSubtaskStatus(${
                    subtask.id
                  }, ${subtask.status_id})"
                  title="Toggle completion">
            ${statusIcon}
          </button>
          
          <div class="flex-1 min-w-0">
            <div class="subtask-title text-sm ${statusClass} cursor-pointer touch-manipulation" 
                 onclick="window.getTaskModalInstance().editSubtaskInline(${
                   subtask.id
                 })"
                 title="Click to edit"
                 data-original-title="${escapedTitle}">
              ${escapedTitle}
            </div>
            ${
              subtask.assignee_name
                ? `<div class="text-xs text-gray-500">Assigned to: ${this.escapeHtml(
                    subtask.assignee_name
                  )}</div>`
                : ""
            }
            ${
              subtask.deadline
                ? `<div class="text-xs text-gray-500">Due: ${this.formatDate(
                    subtask.deadline
                  )}</div>`
                : ""
            }
          </div>
          
          <div class="subtask-actions opacity-100 flex gap-1">
            <button class="w-6 h-6 text-blue-600 hover:text-blue-800 flex items-center justify-center rounded hover:bg-blue-50 touch-manipulation" 
                    onclick="window.getTaskModalInstance().openSubtaskDetail(${
                      subtask.id
                    })"
                    title="View Details">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
            <button class="w-6 h-6 text-red-600 hover:text-red-800 flex items-center justify-center rounded hover:bg-red-50 touch-manipulation" 
                    onclick="window.getTaskModalInstance().confirmDeleteSubtask(${
                      subtask.id
                    }, '${escapedTitle.replace(/'/g, "\\'")}')"
                    title="Delete subtask">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
          

        </div>
      `;
      })
      .join("");

    // Initialize drag and drop functionality after rendering
    this.initializeSubtaskDragAndDrop();
  }

  showAddSubtaskForm() {
    console.log("🔍 showAddSubtaskForm called");
    const form = document.getElementById("newSubtaskForm");
    const input = document.getElementById("newSubtaskTitle");

    if (form && input) {
      form.style.display = "block";
      input.focus();
      input.value = "";
      console.log(" Add subtask form shown");
    } else {
      console.error("❌ Add subtask form elements not found");
    }
  }

  hideAddSubtaskForm() {
    const form = document.getElementById("newSubtaskForm");
    const input = document.getElementById("newSubtaskTitle");

    form.style.display = "none";
    input.value = "";
  }

  async saveNewSubtask() {
    const titleInput = document.getElementById("newSubtaskTitle");
    const title = titleInput.value.trim();

    if (!title) {
      titleInput.focus();
      return;
    }

    // Debug logging
    console.log("🔍 saveNewSubtask - Debug info:");
    console.log("🔍 currentTask:", this.currentTask);
    console.log("🔍 currentUser:", this.currentUser);
    console.log("🔍 instanceId:", this.instanceId);

    if (!this.currentTask || !this.currentTask.id) {
      console.error(
        "❌ Cannot create subtask: currentTask is null or missing ID"
      );

      // Try to get task ID from modal if available
      const modal = document.getElementById("taskModal");
      const taskIdFromModal = modal?.getAttribute("data-task-id");

      if (taskIdFromModal) {
        console.log(
          "🔄 Found task ID in modal attribute, attempting to load task:",
          taskIdFromModal
        );
        try {
          await this.loadTaskData(parseInt(taskIdFromModal));
          if (this.currentTask && this.currentTask.id) {
            console.log(
              " Successfully loaded task data, retrying subtask creation"
            );
            // Retry the subtask creation now that we have task data
            return this.saveNewSubtask();
          }
        } catch (error) {
          console.error("❌ Failed to load task data:", error);
        }
      }

      // If still no task data, show error and hide form
      this.hideAddSubtaskForm();
      this.showError(
        "Cannot create subtask: Task not loaded properly. Please close and reopen the task."
      );
      return;
    }

    try {
      const subtaskData = {
        title: title,
        description: "",
        status_id: 1, // To Do
        priority_id: 2, // Medium
        assigned_to: 0, // Unassigned
      };

      console.log("🔄 Creating subtask for task ID:", this.currentTask.id);
      console.log("🔄 Subtask data:", subtaskData);

      // Try local API first (more reliable)
      const localUrl = `${this.localApiBase}/api/create_subtask/${this.currentTask.id}`;
      console.log("🔗 Local API URL:", localUrl);

      let response = await fetch(localUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-User-ID": this.currentUser?.id || "1",
        },
        credentials: "include",
        body: JSON.stringify(subtaskData),
      });

      console.log("📡 Local API response status:", response.status);

      if (!response.ok) {
        console.log("⚠️ Local API failed, trying external API...");
        // Fallback to external API
        const externalUrl = `${this.apiBase}/task/${this.currentTask.id}/subtasks`;
        console.log("🔗 External API URL:", externalUrl);

        response = await fetch(externalUrl, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-User-ID": this.currentUser?.id || "1",
          },
          credentials: "include",
          body: JSON.stringify(subtaskData),
        });

        console.log("📡 External API response status:", response.status);
      }

      const result = await response.json();

      if (result.success) {
        this.hideAddSubtaskForm();
        await this.loadSubtasks();
        this.showSuccess("Subtask created successfully");

        // Log subtask creation activity
        await this.logActivity("SUBTASK_CREATED", {
          subtask_title: title,
          parent_task_title: this.currentTask?.title || "Unknown Task",
        });
      } else {
        console.error("❌ API returned error:", result.error);
        this.showError(result.error || "Failed to create subtask");
      }
    } catch (error) {
      console.error("❌ Error creating subtask:", error);
      this.showError("Failed to create subtask: " + error.message);
    }
  }

  async toggleSubtaskStatus(subtaskId, currentStatusId) {
    const newStatusId = currentStatusId === 3 ? 1 : 3; // Toggle between To Do (1) and Done (3)

    try {
      // Try local API first since it has session access
      let response = await fetch(
        `${this.localApiBase}/api/update_subtask/${subtaskId}`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-User-ID": this.currentUser?.id || "1",
          },
          credentials: "include",
          body: JSON.stringify({ status_id: newStatusId }),
        }
      );

      if (!response.ok) {
        // Fallback to external API
        response = await fetch(`${this.apiBase}/subtask/${subtaskId}`, {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            "X-User-ID": this.currentUser?.id || "1",
          },
          credentials: "include",
          body: JSON.stringify({ status_id: newStatusId }),
        });
      }

      const result = await response.json();

      if (result.success) {
        await this.loadSubtasks();
        const statusText = newStatusId === 3 ? "completed" : "reopened";
        this.showSuccess(`Subtask ${statusText}`);

        // Log subtask status change activity
        await this.logActivity("SUBTASK_STATUS_CHANGED", {
          subtask_id: subtaskId,
          old_status: currentStatusId,
          new_status: newStatusId,
          parent_task_title: this.currentTask?.title || "Unknown Task",
        });
      } else {
        this.showError(result.error || "Failed to update subtask status");
      }
    } catch (error) {
      console.error("❌ Error updating subtask status:", error);
      this.showError("Failed to update subtask status");
    }
  }

  editSubtaskInline(subtaskId) {
    console.log("🔍 editSubtaskInline called for subtask:", subtaskId);

    // Find the subtask element
    const subtaskElement = document.querySelector(
      `[data-subtask-id="${subtaskId}"]`
    );
    if (!subtaskElement) {
      console.error("❌ Subtask element not found:", subtaskId);
      return;
    }

    const titleElement = subtaskElement.querySelector(".subtask-title");
    if (!titleElement) {
      console.error("❌ Title element not found in subtask:", subtaskId);
      return;
    }

    // Get the original title from data attribute (unescaped)
    const currentTitle =
      titleElement.getAttribute("data-original-title") ||
      titleElement.textContent.trim();
    console.log("🔍 Current title:", currentTitle);

    // Create inline edit input
    const input = document.createElement("input");
    input.type = "text";
    input.value = currentTitle;
    input.className =
      "text-sm border rounded px-2 py-1 w-full focus:outline-none focus:border-blue-500";

    // Store original element for restoration
    const originalElement = titleElement.cloneNode(true);

    // Replace title with input
    titleElement.replaceWith(input);
    input.focus();
    input.select();

    // Handle save/cancel
    const saveEdit = async () => {
      const newTitle = input.value.trim();
      console.log("💾 Saving subtask title:", {
        subtaskId,
        oldTitle: currentTitle,
        newTitle,
      });

      if (newTitle && newTitle !== currentTitle) {
        try {
          await this.updateSubtaskField(subtaskId, "title", newTitle);
          // Reload subtasks to show updated data
          await this.loadSubtasks();
        } catch (error) {
          console.error("❌ Error updating subtask:", error);
          // Restore original element on error
          input.replaceWith(originalElement);
        }
      } else {
        // No change, just reload to restore display
        await this.loadSubtasks();
      }
    };

    const cancelEdit = () => {
      console.log("❌ Canceling subtask edit");
      input.replaceWith(originalElement);
    };

    // Event listeners
    input.addEventListener("blur", saveEdit);
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        saveEdit();
      } else if (e.key === "Escape") {
        e.preventDefault();
        cancelEdit();
      }
    });
  }

  async updateSubtaskField(subtaskId, field, value) {
    try {
      const updateData = {};
      updateData[field] = value;

      console.log("🔄 Updating subtask field:", {
        subtaskId,
        field,
        value,
        updateData,
        instanceId: this.instanceId,
      });

      // Try local API first since it has session access
      let response = await fetch(
        `${this.localApiBase}/api/update_subtask/${subtaskId}`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-User-ID": this.currentUser?.id || "1",
          },
          credentials: "include",
          body: JSON.stringify(updateData),
        }
      );

      if (!response.ok) {
        // Fallback to external API (though this likely won't work due to session)
        response = await fetch(`${this.apiBase}/subtask/${subtaskId}`, {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            "X-User-ID": this.currentUser?.id || "1",
          },
          credentials: "include",
          body: JSON.stringify(updateData),
        });
      }

      const result = await response.json();

      if (result.success) {
        this.showSuccess("Subtask updated");

        // Log subtask update activity
        await this.logActivity("SUBTASK_UPDATED", {
          subtask_id: subtaskId,
          field: field,
          new_value: value,
          parent_task_title: this.currentTask?.title || "Unknown Task",
        });
      } else {
        this.showError(result.error || "Failed to update subtask");
      }
    } catch (error) {
      console.error("❌ Error updating subtask:", error);
      this.showError("Failed to update subtask");
    }
  }

  confirmDeleteSubtask(subtaskId, subtaskTitle) {
    // Use SweetAlert2 for confirmation
    Swal.fire({
      title: "Delete Subtask",
      text: `Are you sure you want to delete "${subtaskTitle}"? This action cannot be undone.`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
      focusCancel: true,
    }).then((result) => {
      if (result.isConfirmed) {
        this.deleteSubtask(subtaskId);
      }
    });
  }

  async deleteSubtask(subtaskId) {
    try {
      // Try local API first since it has session access
      let response = await fetch(
        `${this.localApiBase}/api/delete_subtask/${subtaskId}`,
        {
          method: "DELETE",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "X-User-ID": this.currentUser?.id || "1",
          },
          credentials: "include",
        }
      );

      if (!response.ok) {
        // Fallback to external API
        response = await fetch(`${this.apiBase}/subtask/${subtaskId}`, {
          method: "DELETE",
          headers: {
            "X-User-ID": this.currentUser?.id || "1",
          },
          credentials: "include",
        });
      }

      const result = await response.json();

      if (result.success) {
        await this.loadSubtasks();
        this.showSuccess("Subtask deleted successfully");

        // Log subtask deletion activity
        await this.logActivity("SUBTASK_DELETED", {
          subtask_id: subtaskId,
          parent_task_title: this.currentTask?.title || "Unknown Task",
        });
      } else {
        this.showError(result.error || "Failed to delete subtask");
      }
    } catch (error) {
      console.error("❌ Error deleting subtask:", error);
      this.showError("Failed to delete subtask");
    }
  }

  // Touch-enabled Drag and Drop for Subtasks
  initializeSubtaskDragAndDrop() {
    const subtasksList = document.getElementById("subtasksList");
    if (!subtasksList) return;

    const subtaskItems = subtasksList.querySelectorAll(".subtask-item");

    subtaskItems.forEach((item) => {
      // Mouse events
      item.addEventListener("dragstart", this.handleDragStart.bind(this));
      item.addEventListener("dragover", this.handleDragOver.bind(this));
      item.addEventListener("drop", this.handleDrop.bind(this));
      item.addEventListener("dragend", this.handleDragEnd.bind(this));

      // Touch events for mobile/tablet support
      item.addEventListener("touchstart", this.handleTouchStart.bind(this), {
        passive: false,
      });
      item.addEventListener("touchmove", this.handleTouchMove.bind(this), {
        passive: false,
      });
      item.addEventListener("touchend", this.handleTouchEnd.bind(this), {
        passive: false,
      });
    });

    // Initialize drag state
    this.dragState = {
      draggedElement: null,
      draggedData: null,
      touchStartY: 0,
      touchCurrentY: 0,
      isDragging: false,
      placeholder: null,
      touchOffset: { x: 0, y: 0 },
    };
  }

  handleDragStart(e) {
    this.dragState.draggedElement = e.target.closest(".subtask-item");
    this.dragState.draggedData = {
      id: this.dragState.draggedElement.dataset.subtaskId,
      sortOrder: this.dragState.draggedElement.dataset.sortOrder,
    };

    e.dataTransfer.effectAllowed = "move";
    e.dataTransfer.setData(
      "text/html",
      this.dragState.draggedElement.outerHTML
    );

    // Add visual feedback
    setTimeout(() => {
      this.dragState.draggedElement.style.opacity = "0.5";
    }, 0);
  }

  handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";

    const targetItem = e.target.closest(".subtask-item");
    if (targetItem && targetItem !== this.dragState.draggedElement) {
      const rect = targetItem.getBoundingClientRect();
      const midpoint = rect.top + rect.height / 2;

      if (e.clientY < midpoint) {
        targetItem.parentNode.insertBefore(
          this.dragState.draggedElement,
          targetItem
        );
      } else {
        targetItem.parentNode.insertBefore(
          this.dragState.draggedElement,
          targetItem.nextSibling
        );
      }
    }
  }

  handleDrop(e) {
    e.preventDefault();
    this.updateSubtaskOrder();
  }

  handleDragEnd(e) {
    if (this.dragState.draggedElement) {
      this.dragState.draggedElement.style.opacity = "1";
    }
    this.resetDragState();
  }

  // Touch event handlers for iPad/tablet support
  handleTouchStart(e) {
    // Only handle touches on the drag handle
    if (!e.target.closest(".drag-handle")) return;

    e.preventDefault();

    const touch = e.touches[0];
    const item = e.target.closest(".subtask-item");

    this.dragState.draggedElement = item;
    this.dragState.draggedData = {
      id: item.dataset.subtaskId,
      sortOrder: item.dataset.sortOrder,
    };

    this.dragState.touchStartY = touch.clientY;
    this.dragState.touchCurrentY = touch.clientY;

    // Calculate touch offset relative to element
    const rect = item.getBoundingClientRect();
    this.dragState.touchOffset = {
      x: touch.clientX - rect.left,
      y: touch.clientY - rect.top,
    };

    // Add visual feedback with delay to distinguish from tap
    this.dragState.touchStartTimeout = setTimeout(() => {
      if (this.dragState.draggedElement) {
        this.dragState.isDragging = true;
        this.dragState.draggedElement.style.opacity = "0.8";
        this.dragState.draggedElement.style.transform = "scale(1.02)";
        this.dragState.draggedElement.style.zIndex = "1000";
        this.dragState.draggedElement.style.boxShadow =
          "0 4px 12px rgba(0,0,0,0.15)";

        // Create placeholder
        this.createPlaceholder();

        // Add haptic feedback if available
        if (navigator.vibrate) {
          navigator.vibrate(50);
        }
      }
    }, 150);
  }

  handleTouchMove(e) {
    if (!this.dragState.draggedElement) return;

    e.preventDefault();

    const touch = e.touches[0];
    this.dragState.touchCurrentY = touch.clientY;

    // Only start dragging after initial delay
    if (!this.dragState.isDragging) return;

    // Move the dragged element
    const deltaY = touch.clientY - this.dragState.touchStartY;
    this.dragState.draggedElement.style.transform = `translateY(${deltaY}px) scale(1.02)`;

    // Find the element we're hovering over
    const elementBelow = this.getElementBelowTouch(
      touch.clientX,
      touch.clientY
    );
    const targetItem = elementBelow?.closest(".subtask-item");

    if (
      targetItem &&
      targetItem !== this.dragState.draggedElement &&
      targetItem !== this.dragState.placeholder
    ) {
      this.insertPlaceholder(targetItem, touch.clientY);
    }
  }

  handleTouchEnd(e) {
    // Clear the timeout if touch ended quickly
    if (this.dragState.touchStartTimeout) {
      clearTimeout(this.dragState.touchStartTimeout);
    }

    if (!this.dragState.isDragging) {
      this.resetDragState();
      return;
    }

    e.preventDefault();

    // Animate back to position
    if (this.dragState.draggedElement) {
      this.dragState.draggedElement.style.transition = "all 0.2s ease";
      this.dragState.draggedElement.style.transform = "scale(1)";
      this.dragState.draggedElement.style.opacity = "1";
      this.dragState.draggedElement.style.zIndex = "";
      this.dragState.draggedElement.style.boxShadow = "";

      // Replace placeholder with actual element
      if (this.dragState.placeholder && this.dragState.placeholder.parentNode) {
        this.dragState.placeholder.parentNode.replaceChild(
          this.dragState.draggedElement,
          this.dragState.placeholder
        );
      }

      // Clean up transition after animation
      setTimeout(() => {
        if (this.dragState.draggedElement) {
          this.dragState.draggedElement.style.transition = "";
        }
      }, 200);
    }

    // Update order and reset
    this.updateSubtaskOrder();
    this.resetDragState();
  }

  createPlaceholder() {
    if (!this.dragState.draggedElement) return;

    this.dragState.placeholder = this.dragState.draggedElement.cloneNode(true);
    this.dragState.placeholder.style.opacity = "0.3";
    this.dragState.placeholder.style.transform = "";
    this.dragState.placeholder.style.pointerEvents = "none";
    this.dragState.placeholder.classList.add("drag-placeholder");

    // Insert placeholder after the original element
    this.dragState.draggedElement.parentNode.insertBefore(
      this.dragState.placeholder,
      this.dragState.draggedElement.nextSibling
    );
  }

  insertPlaceholder(targetItem, touchY) {
    if (!this.dragState.placeholder || !targetItem) return;

    const rect = targetItem.getBoundingClientRect();
    const midpoint = rect.top + rect.height / 2;

    if (touchY < midpoint) {
      targetItem.parentNode.insertBefore(
        this.dragState.placeholder,
        targetItem
      );
    } else {
      targetItem.parentNode.insertBefore(
        this.dragState.placeholder,
        targetItem.nextSibling
      );
    }
  }

  getElementBelowTouch(x, y) {
    // Temporarily hide the dragged element to get element below
    const originalDisplay = this.dragState.draggedElement.style.display;
    this.dragState.draggedElement.style.display = "none";

    const elementBelow = document.elementFromPoint(x, y);

    // Restore the dragged element
    this.dragState.draggedElement.style.display = originalDisplay;

    return elementBelow;
  }

  async updateSubtaskOrder() {
    const subtasksList = document.getElementById("subtasksList");
    if (!subtasksList) return;

    const items = Array.from(
      subtasksList.querySelectorAll(".subtask-item:not(.drag-placeholder)")
    );
    const updates = [];

    items.forEach((item, index) => {
      const subtaskId = item.dataset.subtaskId;
      const newSortOrder = (index + 1) * 1000; // Give some spacing between items

      updates.push({
        id: subtaskId,
        sort: newSortOrder,
      });
    });

    // Send batch update to server
    try {
      for (const update of updates) {
        await this.updateSubtaskField(update.id, "sort", update.sort);
      }

      console.log(" Subtask order updated successfully");

      // Log reorder activity
      await this.logActivity("SUBTASKS_REORDERED", {
        subtask_count: updates.length,
        parent_task_title: this.currentTask?.title || "Unknown Task",
      });
    } catch (error) {
      console.error("❌ Error updating subtask order:", error);
      this.showError("Failed to update subtask order");
      // Reload subtasks to restore original order
      await this.loadSubtasks();
    }
  }

  resetDragState() {
    // Clean up placeholder
    if (this.dragState.placeholder && this.dragState.placeholder.parentNode) {
      this.dragState.placeholder.parentNode.removeChild(
        this.dragState.placeholder
      );
    }

    // Clear timeout
    if (this.dragState.touchStartTimeout) {
      clearTimeout(this.dragState.touchStartTimeout);
    }

    // Reset state
    this.dragState = {
      draggedElement: null,
      draggedData: null,
      touchStartY: 0,
      touchCurrentY: 0,
      isDragging: false,
      placeholder: null,
      touchOffset: { x: 0, y: 0 },
      touchStartTimeout: null,
    };
  }

  openSubtaskDetail(subtaskId) {
    console.log("🔍 Opening subtask detail for ID:", subtaskId);

    // Show loading screen
    this.showLoadingScreen();

    // Create a new TaskModal instance for the subtask after 1 second
    setTimeout(() => {
      this.hideLoadingScreen();
      const subtaskModal = new TaskModal();
      subtaskModal.openTask(subtaskId);
    }, 1000);
  }

  showLoadingScreen() {
    // Create loading overlay
    const loadingOverlay = document.createElement("div");
    loadingOverlay.id = "subtask-loading-overlay";
    loadingOverlay.innerHTML = `
      <div style="
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
      ">
        <div style="
          background: white;
          padding: 30px;
          border-radius: 8px;
          text-align: center;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        ">
          <div style="
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
          "></div>
          <div style="color: #333; font-size: 16px;">Loading subtask...</div>
        </div>
      </div>
      <style>
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      </style>
    `;

    document.body.appendChild(loadingOverlay);
  }

  hideLoadingScreen() {
    const loadingOverlay = document.getElementById("subtask-loading-overlay");
    if (loadingOverlay) {
      loadingOverlay.remove();
    }
  }
}

// Make TaskModal class available globally
window.TaskModal = TaskModal;

// Create a single global instance to prevent multiple instance issues
window.getTaskModalInstance = function () {
  if (!window._taskModalInstance) {
    console.log("🔧 Creating single global TaskModal instance");
    try {
      window._taskModalInstance = new TaskModal();
      console.log(" Global TaskModal instance created successfully");
    } catch (error) {
      console.error("❌ Error creating global TaskModal instance:", error);
      throw error;
    }
  }
  return window._taskModalInstance;
};

// Also create a backup instance as window.taskModal for compatibility
window.taskModal = null;

// TaskModal class is now available globally as window.TaskModal
// Initialize manually when needed to prevent conflicts
console.log(" TaskModal class loaded and available globally");
console.log(" window.TaskModal:", typeof window.TaskModal);
console.log(
  " window.getTaskModalInstance:",
  typeof window.getTaskModalInstance
);

// Initialize global instance when DOM is ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", function () {
    console.log("🔄 DOM ready, initializing global TaskModal instance");
    try {
      window.taskModal = window.getTaskModalInstance();
      console.log(" Global TaskModal instance ready");
    } catch (error) {
      console.error(
        "❌ Error initializing global TaskModal on DOM ready:",
        error
      );
    }
  });
} else {
  // DOM already ready
  console.log("🔄 DOM already ready, initializing global TaskModal instance");
  try {
    window.taskModal = window.getTaskModalInstance();
    console.log(" Global TaskModal instance ready");
  } catch (error) {
    console.error("❌ Error initializing global TaskModal:", error);
  }
}
