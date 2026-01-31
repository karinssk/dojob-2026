/**
 * Node.js Enhanced Kanban Board - Pure Node.js API Integration
 */

class NodeJSEnhancedKanban {
  constructor(projectId) {
    console.log("🏗️ Creating NodeJS Enhanced Kanban instance...");
    console.log("📋 Project ID:", projectId);

    this.projectId = projectId;
    this.boardData = {};
    this.statuses = [];
    this.apiBase = "https://api-dojob.rubyshop.co.th/api";

    // Initialize duplicate prevention flags
    this.isProcessingAddTask = false;
    this.isShowingPrompt = false;
    this.isCreatingTaskDirect = false;
    this.isCreatingTask = false;
    this.recentCreations = new Set();

    // Global flag to prevent any task creation
    if (!window.globalTaskCreationLock) {
      window.globalTaskCreationLock = new Set();
    }

    console.log("🔗 API Base URL:", this.apiBase);

    // Check if board container exists
    const container = document.getElementById("kanban-board-container");
    if (!container) {
      console.error(
        "❌ Board container not found! Make sure the Board tab is active."
      );
      return;
    }

    console.log(" Board container found, proceeding with initialization...");
    this.init();
  }

  init() {
    console.log("🚀 Initializing Node.js Enhanced Kanban Board");

    try {
      // Show immediate loading message
      const boardContainer = document.getElementById("kanban-board-container");
      if (boardContainer) {
        boardContainer.innerHTML = `
                    <div class="loading-board">
                        <div class="loading-spinner"></div>
                        <div>Initializing Kanban Board...</div>
                        <div style="font-size: 12px; color: #666; margin-top: 10px;">
                            Project ID: ${this.projectId}<br>
                            Connecting to API: ${this.apiBase}
                        </div>
                    </div>
                `;
      }

      // Add kanban-active class to enable scrollbar management
      this.enableKanbanScrollbarMode();

      // Add task menu styles
      this.addTaskMenuStyles();

      this.setupEventListeners();
      this.loadStatuses();
    } catch (error) {
      console.error("❌ Error during kanban initialization:", error);
      this.showError("Failed to initialize kanban board: " + error.message);
    }
  }

  enableKanbanScrollbarMode() {
    // Add classes to manage page scrolling
    document.body.classList.add("kanban-board-active");

    const pageContent = document.querySelector(".page-content");
    if (pageContent) {
      pageContent.classList.add("kanban-active");
    }

    const appContent = document.querySelector(".app-content");
    if (appContent) {
      appContent.classList.add("kanban-active");
    }

    const mainContent = document.querySelector(".main-content");
    if (mainContent) {
      mainContent.classList.add("kanban-active");
    }

    console.log(" Kanban scrollbar mode enabled");
  }

  disableKanbanScrollbarMode() {
    // Remove classes to restore normal page scrolling
    document.body.classList.remove("kanban-board-active");

    const pageContent = document.querySelector(".page-content");
    if (pageContent) {
      pageContent.classList.remove("kanban-active");
    }

    const appContent = document.querySelector(".app-content");
    if (appContent) {
      appContent.classList.remove("kanban-active");
    }

    const mainContent = document.querySelector(".main-content");
    if (mainContent) {
      mainContent.classList.remove("kanban-active");
    }

    console.log(" Kanban scrollbar mode disabled");
  }

  addTaskMenuStyles() {
    // Add CSS styles for task menu
    const styles = `
      <style id="task-menu-styles">
        .task-menu-container {
          position: relative;
        }
        
        .task-menu-btn {
          background: #6b7280 !important;
          border: 1px solid #6b7280 !important;
          color: white !important;
          cursor: pointer !important;
          padding: 4px 6px !important;
          border-radius: 3px !important;
          opacity: 1 !important;
          transition: all 0.2s ease !important;
          font-size: 14px !important;
          font-weight: bold !important;
          min-width: 24px !important;
          min-height: 24px !important;
          display: flex !important;
          align-items: center !important;
          justify-content: center !important;
          z-index: 10 !important;
        }
        
        .task-menu-btn:hover {
          background: #4b5563 !important;
          color: white !important;
          transform: scale(1.05) !important;
        }
        
        .task-context-menu {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .task-context-menu .menu-item {
          padding: 8px 12px;
          cursor: pointer;
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 13px;
          color: #374151;
          transition: background-color 0.1s ease;
        }
        
        .task-context-menu .menu-item:hover {
          background-color: #f3f4f6;
        }
        
        .task-context-menu .menu-item.text-danger {
          color: #dc2626;
        }
        
        .task-context-menu .menu-item.text-danger:hover {
          background-color: #fef2f2;
        }
        
        .task-context-menu .menu-divider {
          height: 1px;
          background-color: #e5e7eb;
          margin: 4px 0;
        }
        
        .task-header-actions {
          display: flex;
          align-items: center;
          gap: 4px;
        }
      </style>
    `;

    // Remove existing styles if any
    const existingStyles = document.getElementById("task-menu-styles");
    if (existingStyles) {
      existingStyles.remove();
    }

    // Add new styles
    document.head.insertAdjacentHTML("beforeend", styles);
    console.log(" Task menu styles added");
  }

  async loadStatuses() {
    try {
      console.log(
        "🔄 Loading statuses from Node.js API... api-dojob.rubyshop.co.th"
      );

      // Add timeout to prevent infinite loading
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

      const response = await fetch(`${this.apiBase}/statuses`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const result = await response.json();

      if (result && result.success) {
        this.statuses = result.data;
        console.log(" Statuses loaded:", this.statuses);
        this.loadTasks();
      } else {
        console.error(
          "❌ Failed to load statuses:",
          result ? result.error : "No response received"
        );
        this.showError(
          result ? result.error : "Failed to load status configuration"
        );
        this.useFallbackStatuses();
      }
    } catch (error) {
      if (error.name === "AbortError") {
        console.error("❌ API request timed out after 10 seconds");
        this.showError("API request timed out - using fallback data");
      } else {
        console.error("❌ Network error loading statuses:", error);
        console.log("🔄 Using fallback statuses...");
      }
      this.useFallbackStatuses();
    }
  }

  useFallbackStatuses() {
    // Fallback to default statuses if API fails
    this.statuses = [
      {
        id: 1,
        title: "To Do",
        key_name: "to_do",
        color: "#F9A52D",
        sort: 0,
        hide_from_kanban: 0,
      },
      {
        id: 2,
        title: "In Progress",
        key_name: "in_progress",
        color: "#1672B9",
        sort: 1,
        hide_from_kanban: 0,
      },
      {
        id: 3,
        title: "Done",
        key_name: "done",
        color: "#00B393",
        sort: 2,
        hide_from_kanban: 0,
      },
    ];

    console.log(" Using fallback statuses:", this.statuses);
    this.loadTasks();
  }

  async loadTasks() {
    try {
      console.log("🔄 Loading tasks from Node.js API...");
      console.log("🔗 API URL:", `${this.apiBase}/kanban/${this.projectId}`);

      // Add timeout to prevent infinite loading
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 8000); // 8 second timeout

      const response = await fetch(`${this.apiBase}/kanban/${this.projectId}`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const result = await response.json();

      if (result.success) {
        console.log(" API data received:", result.data);
        this.boardData = result.data;
        this.renderBoard();
      } else {
        console.error("❌ API Error:", result.error);
        this.showError(result.error);
        this.loadFallbackData();
      }
    } catch (error) {
      if (error.name === "AbortError") {
        console.error("❌ Task loading timed out after 8 seconds");
        this.showError("Task loading timed out - using sample data");
      } else {
        console.error("❌ Network error:", error);
        console.log(
          "🔄 Falling back to mock data due to API unavailability..."
        );
      }
      this.loadFallbackData();
    }
  }

  loadFallbackData() {
    console.log("📋 Loading fallback/mock kanban data...");

    // Create mock data structure that matches the expected format
    this.boardData = [
      {
        key_name: "to_do",
        title: "To Do",
        tasks: [
          {
            id: 1,
            title: "Sample Task 1",
            description: "This is a sample task for testing the kanban board",
            priority_id: 2,
            assigned_to: null,
            deadline: null,
            images: [],
          },
          {
            id: 2,
            title: "Setup Project Documentation",
            description: "Create comprehensive documentation for the project",
            priority_id: 1,
            assigned_to: null,
            deadline: "2024-12-31",
            images: [],
          },
        ],
      },
      {
        key_name: "in_progress",
        title: "In Progress",
        tasks: [
          {
            id: 3,
            title: "Fix Kanban Board Loading",
            description: "Debug and fix the kanban board loading issues",
            priority_id: 3,
            assigned_to: 1,
            first_name: "Admin",
            last_name: "User",
            deadline: "2024-12-20",
            images: [],
          },
        ],
      },
      {
        key_name: "done",
        title: "Done",
        tasks: [
          {
            id: 4,
            title: "Complete Task Sorting",
            description: "Implemented task sorting by newest first",
            priority_id: 1,
            assigned_to: 1,
            first_name: "Admin",
            last_name: "User",
            deadline: null,
            images: [],
          },
        ],
      },
    ];

    this.showSuccess("Kanban board loaded with sample data (API unavailable)");
    this.renderBoard();
  }

  renderBoard() {
    const boardContainer = document.getElementById("kanban-board-container");
    if (!boardContainer) {
      console.error("❌ Board container not found");
      return;
    }

    console.log("🎨 Rendering enhanced board...");

    // Create board header with status management
    const boardHeader = this.renderBoardHeader();

    // Create columns based on statuses
    const columnsHtml = this.statuses
      .map((status) => {
        const tasks = this.getTasksForStatus(status.key_name);
        return this.renderColumn(status, tasks);
      })
      .join("");

    boardContainer.innerHTML = `
            ${boardHeader}
            <div class="kanban-board" id="kanban-columns-container">
                ${columnsHtml}
            </div>
        `;

    this.setupDragAndDrop();
    this.setupColumnReordering();
  }

  renderBoardHeader() {
    return `
           
        `;
  }

  renderColumn(status, tasks) {
    return `
            <div class="kanban-column" data-status="${
              status.key_name
            }" data-status-id="${status.id}">
                <div class="column-header" style="border-top: 3px solid ${
                  status.color
                }">
                    <div class="column-title-section">
                        <div class="column-title">
                            ${status.title.toUpperCase()}
                            <span class="task-count">${tasks.length}</span>
                        </div>
                        <div class="column-actions">
                            <button class="column-menu-btn" data-status-id="${
                              status.id
                            }">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <button class="add-task-btn" data-status="${
                              status.key_name
                            }" style="
                                background: #0052cc;
                                color: white;
                                border: none;
                                border-radius: 3px;
                                padding: 6px 8px;
                                cursor: pointer;
                                font-size: 12px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                min-width: 24px;
                                min-height: 24px;
                                margin-left: 4px;
                            " title="Add new task">
                                +
                            </button>
                        </div>
                    </div>
                </div>
                <div class="tasks-container" data-status="${
                  status.key_name
                }" data-status-id="${status.id}">
                    ${this.renderTasks(tasks, status.key_name)}
                </div>
            </div>
        `;
  }

  getTasksForStatus(statusKey) {
    if (!this.boardData || !Array.isArray(this.boardData)) {
      return [];
    }

    const column = this.boardData.find((col) => col.key_name === statusKey);
    return column ? column.tasks || [] : [];
  }

  renderTasks(tasks, status) {
    if (tasks.length === 0) {
      return this.renderCreateOnlyCard(status);
    }

    // Sort tasks by sort value (descending) so newest tasks (highest sort) appear at TOP
    const sortedTasks = [...tasks].sort((a, b) => {
      const sortA = parseInt(a.sort) || parseInt(a.id) || 0;
      const sortB = parseInt(b.sort) || parseInt(b.id) || 0;
      return sortB - sortA; // Descending order: highest sort values at top, lowest at bottom
    });

    console.log(
      `📋 Rendering ${sortedTasks.length} tasks for ${status}:`,
      sortedTasks.map((t) => `${t.title} (sort: ${t.sort || t.id})`)
    );

    return sortedTasks
      .map((task, index) => {
        const isFirstTask = index === 0;
        return this.renderTaskCard(task, isFirstTask, status);
      })
      .join("");
  }

  renderTaskCard(task, isFirstTask = false, status = null) {
    const hasImages = task.images && task.images.length > 0;
    const imagePreview = hasImages ? this.renderImagePreview(task.images) : "";

    const assignee = task.assigned_to
      ? {
          name:
            `${task.first_name || ""} ${task.last_name || ""}`.trim() ||
            "Unassigned",
          avatar: task.user_image ? this.parseUserImage(task.user_image) : null,
          initials: this.getInitials(
            `${task.first_name || ""} ${task.last_name || ""}`
          ),
        }
      : null;

    const assigneeAvatar = assignee ? this.renderAssigneeAvatar(assignee) : "";
    const priority = this.mapPriority(task.priority_id);
    const priorityIndicator = this.renderPriorityIndicator(
      priority,
      task.priority_color
    );

    // Clean task ID properly
    let cleanTaskId = parseInt(task.id, 10);
    if (!cleanTaskId || cleanTaskId < 1) {
      console.error("Invalid task ID:", task.id);
      cleanTaskId = 0;
    }

    const taskStatus = status || this.getTaskStatus(task);

    return `
            <div class="task-card ${
              isFirstTask ? "first-task" : ""
            }" data-task-id="${cleanTaskId}" draggable="true">
                <div class="task-header">
                    <span class="task-key">TASK-${task.id}</span>
                    <div class="task-header-actions">
                        <div class="reorder-buttons">
                            <button class="reorder-btn reorder-up" data-task-id="${cleanTaskId}" data-direction="up" title="Move up">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <button class="reorder-btn reorder-down" data-task-id="${cleanTaskId}" data-direction="down" title="Move down">
                                <i class="fas fa-chevron-down"></i>🔻
                            </button>
                        </div>
                        ${priorityIndicator}
                        <div class="task-menu-container">
                            <button class="task-menu-btn" data-task-id="${cleanTaskId}" title="More options">
                                ⋮
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="task-content">
                    <h4 class="task-title">${this.escapeHtml(task.title)}</h4>
                    ${
                      task.description
                        ? `<p class="task-description">${this.escapeHtml(
                            task.description.substring(0, 100)
                          )}${task.description.length > 100 ? "..." : ""}</p>`
                        : ""
                    }
                </div>

                ${imagePreview}

                <div class="task-footer">
                    <div class="task-meta">
                        ${
                          task.deadline
                            ? `<span class="deadline"><i class="fas fa-calendar"></i> ${this.formatDate(
                                task.deadline
                              )}</span>`
                            : ""
                        }
                    </div>
                    <div class="task-assignee">
                        
                             
                        ${assigneeAvatar}
                    </div>
                </div>
                
                ${isFirstTask ? this.renderCreateSection(taskStatus) : ""}
            </div>
        `;
  }

  renderCreateOnlyCard(status = "to_do") {
    return `
            <div class="task-card create-only-card" data-create-only="true">
                ${this.renderCreateSection(status)}
            </div>
        `;
  }

  renderCreateSection(status) {
    return `
            <div class="task-create-section">
                <button class="create-task-button" data-status="${status}">
                    <i class="fas fa-plus"></i>
                    Create
                </button>
            </div>
        `;
  }

  setupColumnReordering() {
    const columnsContainer = document.getElementById(
      "kanban-columns-container"
    );
    if (!columnsContainer) return;

    // Make columns sortable
    new Sortable(columnsContainer, {
      animation: 150,
      ghostClass: "column-ghost",
      chosenClass: "column-chosen",
      dragClass: "column-drag",
      handle: ".column-header",
      onEnd: (evt) => {
        this.handleColumnReorder(evt);
      },
    });
  }

  async handleColumnReorder(_evt) {
    const columns = Array.from(document.querySelectorAll(".kanban-column"));
    const statusOrders = columns.map((column, index) => ({
      id: parseInt(column.dataset.statusId),
      sort: index,
    }));

    try {
      const response = await fetch(`${this.apiBase}/statuses/reorder`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          status_orders: statusOrders,
        }),
      });

      const result = await response.json();

      if (result.success) {
        console.log(" Columns reordered successfully");
        this.showSuccess(result.message);
        // Reload to reflect new order
        setTimeout(() => this.loadStatuses(), 500);
      } else {
        console.error("❌ Failed to reorder columns:", result.error);
        this.showError(result.error);
        // Revert visual change
        this.renderBoard();
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Failed to save column order");
      this.renderBoard();
    }
  }

  setupEventListeners() {
    // Add status button
    document.addEventListener("click", (e) => {
      if (e.target.closest("#add-status-btn")) {
        this.showAddStatusModal();
      }
    });

    // Manage statuses button
    document.addEventListener("click", (e) => {
      if (e.target.closest("#manage-statuses-btn")) {
        this.showManageStatusesModal();
      }
    });

    // Column menu buttons
    document.addEventListener("click", (e) => {
      if (e.target.closest(".column-menu-btn")) {
        const statusId = e.target.closest(".column-menu-btn").dataset.statusId;
        this.showColumnMenu(e.target, statusId);
      }
    });

    // Refresh button
    document.addEventListener("click", (e) => {
      if (e.target.closest("#refresh-board-btn")) {
        this.loadStatuses();
      }
    });

    // Add task buttons with debounce
    let lastClickTime = 0;
    document.addEventListener("click", (e) => {
      if (e.target.closest(".add-task-btn")) {
        e.preventDefault();
        e.stopPropagation();

        // Debounce rapid clicks (prevent clicks within 1 second)
        const now = Date.now();
        if (now - lastClickTime < 1000) {
          console.log("⚠️ Rapid click detected, ignoring");
          return;
        }
        lastClickTime = now;

        // Prevent multiple clicks
        if (this.isProcessingAddTask) {
          console.log("⚠️ Add task already in progress, ignoring click");
          return;
        }

        const status = e.target.closest(".add-task-btn").dataset.status;
        console.log("🔍 Add task button clicked for status:", status);
        this.openCreateTaskModal(status);
      }
    });

    // Create task buttons
    document.addEventListener("click", (e) => {
      if (e.target.closest(".create-task-button")) {
        const button = e.target.closest(".create-task-button");
        const status = button.dataset.status;
        this.showInlineTaskForm(button, status);
      }
    });

    // Task reorder buttons
    document.addEventListener("click", (e) => {
      if (e.target.closest(".reorder-btn")) {
        e.preventDefault();
        e.stopPropagation();
        const button = e.target.closest(".reorder-btn");
        const taskId = button.dataset.taskId;
        const direction = button.dataset.direction;
        this.reorderTask(taskId, direction);
      }
    });

    // Task menu buttons (three dots) - using arrow function to preserve 'this' context
    const kanbanInstance = this;
    document.addEventListener("click", function (e) {
      console.log("🔍 Click detected on:", e.target);
      if (e.target.closest(".task-menu-btn")) {
        console.log("🔍 Three-dot button clicked!", e.target);
        e.preventDefault();
        e.stopPropagation();
        const button = e.target.closest(".task-menu-btn");
        const taskId = button.dataset.taskId;
        console.log("🔍 Task ID:", taskId, "Button:", button);
        console.log("🔍 Calling showTaskMenu with instance:", kanbanInstance);
        kanbanInstance.showTaskMenu(button, taskId);
      }
    });

    // Handle form submissions
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("btn-create-task")) {
        e.preventDefault();
        this.handleCreateTask(e.target);
      } else if (e.target.classList.contains("btn-cancel-task")) {
        e.preventDefault();
        this.hideInlineTaskForm(e.target);
      }
    });

    // Handle Enter/Escape in task input
    document.addEventListener("keydown", (e) => {
      if (e.target.classList.contains("task-title-input")) {
        if (e.key === "Enter" && !e.shiftKey) {
          e.preventDefault();
          const createBtn = e.target
            .closest(".inline-task-form")
            .querySelector(".btn-create-task");
          if (createBtn) createBtn.click();
        } else if (e.key === "Escape") {
          e.preventDefault();
          const cancelBtn = e.target
            .closest(".inline-task-form")
            .querySelector(".btn-cancel-task");
          if (cancelBtn) cancelBtn.click();
        }
      }
    });
  }

  showAddStatusModal() {
    // Simple prompt-based status creation
    const title = prompt("Enter new status name:");
    if (title && title.trim()) {
      const color = prompt(
        "Enter status color (hex code, e.g., #FF5722):",
        "#6c757d"
      );
      this.createStatusDirectly(title.trim(), color || "#6c757d");
    }
  }

  async createStatusDirectly(title, color) {
    try {
      const response = await fetch(`${this.apiBase}/statuses`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          title: title,
          color: color,
          project_id: this.projectId,
        }),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(`Status "${result.data.title}" added successfully`);
        setTimeout(() => this.loadStatuses(), 500);
      } else {
        this.showError(result.error);
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Failed to add status");
    }
  }

  async handleAddStatus(modal) {
    const title = document.getElementById("statusTitle").value.trim();
    const color = document.getElementById("statusColor").value;

    if (!title) {
      this.showError("Status title is required");
      return;
    }

    try {
      const response = await fetch(`${this.apiBase}/statuses`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          title: title,
          color: color,
          project_id: this.projectId,
        }),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        modal.hide();
        // Reload board with new status
        setTimeout(() => this.loadStatuses(), 500);
      } else {
        this.showError(result.error);
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Failed to add status");
    }
  }

  setupDragAndDrop() {
    const taskCards = document.querySelectorAll(".task-card");
    const containers = document.querySelectorAll(".tasks-container");

    taskCards.forEach((card) => {
      if (card.dataset.createOnly === "true") return;

      card.addEventListener("dragstart", (e) => {
        e.dataTransfer.setData("text/plain", card.dataset.taskId);
        card.classList.add("dragging");
      });

      card.addEventListener("dragend", () => {
        card.classList.remove("dragging");
      });

      card.addEventListener("click", (e) => {
        if (
          e.target.closest(".create-task-button") ||
          e.target.closest(".task-create-section") ||
          e.target.closest(".inline-task-form") ||
          e.target.closest(".reorder-btn") ||
          e.target.closest(".task-menu-btn") ||
          e.target.closest(".task-menu-container") ||
          e.target.closest(".dragging")
        ) {
          return;
        }

        if (card.dataset.taskId && card.dataset.taskId !== "undefined") {
          this.openTaskModal(card.dataset.taskId);
        }
      });
    });

    containers.forEach((container) => {
      container.addEventListener("dragover", (e) => {
        e.preventDefault();
        container.classList.add("drag-over");
      });

      container.addEventListener("dragleave", (e) => {
        if (!container.contains(e.relatedTarget)) {
          container.classList.remove("drag-over");
        }
      });

      container.addEventListener("drop", (e) => {
        e.preventDefault();
        container.classList.remove("drag-over");

        const taskId = e.dataTransfer.getData("text/plain");
        const newStatusKey = container.dataset.status;

        // Find status ID from key
        const status = this.statuses.find((s) => s.key_name === newStatusKey);
        if (status) {
          // Calculate the drop position and new sort value
          const dropPosition = this.calculateDropPosition(e, container);
          this.updateTaskStatusWithPosition(taskId, status.id, dropPosition);
        }
      });
    });
  }

  async updateTaskStatus(taskId, statusId) {
    console.log(`🎯 Updating Task ${taskId} → Status ID ${statusId}`);

    try {
      // Clean task ID
      const cleanTaskId = parseInt(taskId, 10);
      if (!cleanTaskId || isNaN(cleanTaskId)) {
        throw new Error("Invalid task ID");
      }

      const response = await fetch(
        `${this.apiBase}/task/${cleanTaskId}/status`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({
            status_id: statusId,
          }),
        }
      );

      const result = await response.json();

      if (result.success) {
        console.log(" Status updated successfully");
        const status = this.statuses.find((s) => s.id == statusId);
        this.showSuccess(
          `Task moved to ${status ? status.title : "new status"}`
        );
        setTimeout(() => this.loadTasks(), 500);
      } else {
        console.error("❌ Update failed:", result.error);
        this.showError(result.error || "Failed to update status");
        this.loadTasks();
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
      this.loadTasks();
    }
  }

  calculateDropPosition(event, container) {
    const taskCards = Array.from(
      container.querySelectorAll('.task-card:not([data-create-only="true"])')
    );
    const mouseY = event.clientY;

    // If no tasks in container, return position 0 (top)
    if (taskCards.length === 0) {
      return { position: 0, taskAbove: null, taskBelow: null };
    }

    // Find the position where the task should be inserted
    for (let i = 0; i < taskCards.length; i++) {
      const card = taskCards[i];
      const rect = card.getBoundingClientRect();
      const cardMiddle = rect.top + rect.height / 2;

      if (mouseY < cardMiddle) {
        // Insert before this card
        return {
          position: i,
          taskAbove: i > 0 ? taskCards[i - 1] : null,
          taskBelow: card,
        };
      }
    }

    // Insert at the end
    return {
      position: taskCards.length,
      taskAbove: taskCards[taskCards.length - 1],
      taskBelow: null,
    };
  }

  async updateTaskStatusWithPosition(taskId, statusId, dropPosition) {
    console.log(
      `🎯 Updating Task ${taskId} → Status ID ${statusId} at position ${dropPosition.position}`
    );

    try {
      // Clean task ID
      const cleanTaskId = parseInt(taskId, 10);
      if (!cleanTaskId || isNaN(cleanTaskId)) {
        throw new Error("Invalid task ID");
      }

      // Calculate the new sort value based on neighboring tasks
      const newSortValue = this.calculateSortValue(dropPosition, statusId);

      console.log(`📊 Calculated sort value: ${newSortValue}`);

      const response = await fetch(
        `${this.apiBase}/task/${cleanTaskId}/status`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({
            status_id: statusId,
            sort: newSortValue,
          }),
        }
      );

      const result = await response.json();

      if (result.success) {
        console.log(" Status and position updated successfully");
        const status = this.statuses.find((s) => s.id == statusId);
        this.showSuccess(
          `Task moved to ${status ? status.title : "new status"} at position ${
            dropPosition.position + 1
          }`
        );
        setTimeout(() => this.loadTasks(), 500);
      } else {
        console.error("❌ Update failed:", result.error);
        this.showError(result.error || "Failed to update status");
        this.loadTasks();
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
      this.loadTasks();
    }
  }

  calculateSortValue(dropPosition, _statusId) {
    const { taskAbove, taskBelow } = dropPosition;

    let aboveSort = null;
    let belowSort = null;

    // Get sort values from neighboring tasks
    if (taskAbove) {
      const aboveTaskId = taskAbove.dataset.taskId;
      const aboveTask = this.findTaskById(aboveTaskId);
      aboveSort = parseInt(aboveTask?.sort) || parseInt(aboveTaskId) || 0;
    }

    if (taskBelow) {
      const belowTaskId = taskBelow.dataset.taskId;
      const belowTask = this.findTaskById(belowTaskId);
      belowSort = parseInt(belowTask?.sort) || parseInt(belowTaskId) || 0;
    }

    console.log(`🔍 Sort calculation:`, {
      position: dropPosition.position,
      aboveSort,
      belowSort,
      taskAbove: taskAbove?.dataset.taskId,
      taskBelow: taskBelow?.dataset.taskId,
    });

    // Calculate new sort value based on position - keep values reasonable for INT column
    if (aboveSort !== null && belowSort !== null) {
      // Between two tasks - use average
      const newSort = Math.floor((aboveSort + belowSort) / 2);
      // If the average is too close, add some spacing
      if (Math.abs(aboveSort - belowSort) <= 1) {
        return Math.min(aboveSort + 100, 2000000000); // Add spacing but cap at 2 billion
      }
      return newSort;
    } else if (aboveSort !== null) {
      // At the bottom - add 100 to the task above (smaller increment)
      return Math.min(aboveSort + 100, 2000000000);
    } else if (belowSort !== null) {
      // At the top - subtract 100 from the task below
      return Math.max(belowSort - 100, 1);
    } else {
      // First task in empty column - use a reasonable starting value
      return 1000; // Start with 1000 instead of timestamp
    }
  }

  findTaskById(taskId) {
    // Search through all status columns to find the task
    for (const column of this.boardData) {
      if (column.tasks) {
        const task = column.tasks.find((t) => t.id == taskId);
        if (task) return task;
      }
    }
    return null;
  }

  // Utility methods
  mapPriority(priorityId) {
    // Use dynamic priority mapping from database instead of hardcoded values
    if (window.priorityMapping && window.priorityMapping[priorityId]) {
      return window.priorityMapping[priorityId].text.toLowerCase();
    }

    // Fallback mapping if database data not available
    const fallbackMap = {
      0: "lowest",
      1: "low",
      2: "medium",
      3: "high",
      4: "highest",
    };
    return fallbackMap[priorityId] || "medium";
  }

  parseUserImage(imageData) {
    console.log("debug>>>", imageData);

    if (!imageData) return null;
    try {
      if (typeof imageData === "string" && imageData.includes("file_name")) {
        // Match all strings inside quotes
        const matches = [...imageData.matchAll(/s:\d+:"([^"]+)"/g)];
        if (matches.length >= 2) {
          const fileName = matches[1][1]; // second match is the value
          const baseUrl = window.location.origin + "/";
          return `${baseUrl}files/profile_images/${fileName}`;
        }
      }
      console.log("debug after fixed!! >>>", imageData);
      return imageData;
    } catch (e) {
      console.error("parseUserImage error", e);
      return null;
    }
  }

  getInitials(name) {
    if (!name || name === "Unassigned") return "U";
    return name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
      .substring(0, 2);
  }

  escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, (m) => map[m]);
  }

  formatDate(dateString) {
    return new Date(dateString).toLocaleDateString();
  }

  showSuccess(message) {
    console.log(" Success:", message);
    this.showToast(message, "success");
  }

  showError(message) {
    console.error("❌ Error:", message);
    this.showToast(message, "error");
  }

  showToast(message, type) {
    const toast = document.createElement("div");
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `<i class="fas fa-${
      type === "success" ? "check-circle" : "exclamation-circle"
    }"></i> ${message}`;
    toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === "success" ? "#d4edda" : "#f8d7da"};
            color: ${type === "success" ? "#155724" : "#721c24"};
            padding: 12px 20px;
            border: 1px solid ${type === "success" ? "#c3e6cb" : "#f5c6cb"};
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 10000;
            font-size: 14px;
            max-width: 300px;
        `;

    document.body.appendChild(toast);

    setTimeout(
      () => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      },
      type === "success" ? 3000 : 5000
    );
  }

  // Image preview implementation
  renderImagePreview(images) {
    if (!images || images.length === 0) return "";

    const getImageUrl = (image) => {
      if (typeof image === "string") {
        return image;
      } else if (typeof image === "object" && image !== null) {
        if (image.url) return image.url;
        else if (image.filename)
          return `https://dojob.rubyshop.co.th/files/timeline_files/${image.filename}99999`;
        else if (image.file_name)
          return `https://dojob.rubyshop.co.th/files/timeline_files/${image.file_name}9999`;
      }
      return null;
    };

    if (images.length === 1) {
      const imageUrl = getImageUrl(images[0]);
      if (!imageUrl) return "";
      return `<div class="task-images single-image"><img src="${imageUrl}" alt="Task image" class="task-image" loading="lazy"></div>`;
    } else {
      const firstImageUrl = getImageUrl(images[0]);
      if (!firstImageUrl) return "";
      const remainingCount = images.length - 1;
      return `<div class="task-images multiple-images"><img src="${firstImageUrl}" alt="Task image" class="task-image main-image" loading="lazy"><div class="image-overlay"><span class="image-count">+${remainingCount}</span></div></div>`;
    }
  }

  renderAssigneeAvatar(assignee) {
    if (assignee.avatar && assignee.avatar !== "") {
      return `<div class="assignee-avatar" title="${this.escapeHtml(
        assignee.name
      )}"><img src="${assignee.avatar}" alt="${this.escapeHtml(
        assignee.name
      )}" class="avatar-img"></div>`;
    } else {
      return `<div class="assignee-avatar initials" title="${this.escapeHtml(
        assignee.name
      )}"><span class="avatar-initials">${assignee.initials}</span></div>`;
    }
  }

  renderPriorityIndicator(priority, color) {
    const priorityIcons = {
      high: "fas fa-arrow-up",
      medium: "fas fa-minus",
      low: "fas fa-arrow-down",
      normal: "fas fa-minus",
    };
    const icon = priorityIcons[priority] || priorityIcons["normal"];
    return `<span class="priority-indicator priority-${priority}" style="color: ${
      color || "#666"
    }" title="${
      priority.charAt(0).toUpperCase() + priority.slice(1)
    } Priority"><i class="${icon}"></i></span>`;
  }

  getTaskStatus(task) {
    if (task.status_id) {
      const statusMap = { 1: "to_do", 2: "in_progress", 3: "done" };
      return statusMap[task.status_id] || "to_do";
    }
    return "to_do";
  }

  openTaskModal(taskId) {
    console.log("🔍 Opening custom task modal for ID:", taskId);

    // Use your custom TaskModal from task-modal.js
    try {
      // Check if TaskModal is available
      if (window.TaskModal) {
        console.log(" Using custom TaskModal class");

        // Get or create TaskModal instance
        if (!window.taskModalInstance) {
          window.taskModalInstance = new window.TaskModal();
          console.log(" Created new TaskModal instance");
        }

        // Open the task using your custom modal
        window.taskModalInstance.openTask(taskId);
        console.log(" Custom task modal opened for task:", taskId);
      } else if (window.getTaskModalInstance) {
        console.log(" Using getTaskModalInstance function");
        const taskModal = window.getTaskModalInstance();
        taskModal.openTask(taskId);
      } else if (
        window.taskModal &&
        typeof window.taskModal.openTask === "function"
      ) {
        console.log(" Using existing taskModal instance");
        window.taskModal.openTask(taskId);
      } else {
        console.error("❌ Custom TaskModal not available");
        // Fallback to Rise CRM modal
        const modalUrl = `${window.location.origin}/index.php/tasks/view/${taskId}`;
        if (typeof modal_anchor === "function") {
          modal_anchor(modalUrl, "", {
            "data-post-id": taskId,
            "data-modal-lg": "1",
            title: `Task #${taskId}`,
          });
        } else {
          window.open(modalUrl, "_blank");
        }
      }
    } catch (error) {
      console.error("❌ Error opening custom task modal:", error);

      // Fallback to Rise CRM modal on error
      try {
        const modalUrl = `${window.location.origin}/index.php/tasks/view/${taskId}`;
        if (typeof modal_anchor === "function") {
          modal_anchor(modalUrl, "", {
            "data-post-id": taskId,
            "data-modal-lg": "1",
            title: `Task #${taskId}`,
          });
        } else {
          window.open(modalUrl, "_blank");
        }
      } catch (fallbackError) {
        console.error("❌ Fallback also failed:", fallbackError);
        alert(`Unable to open task ${taskId}. Please try refreshing the page.`);
      }
    }
  }

  showTaskModal(task) {
    const modalHtml = `
            <div class="modal fade" id="taskModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <span class="task-key">TASK-${task.id}</span>
                                ${task.title}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" id="taskModalTitle" value="${this.escapeHtml(
                                          task.title
                                        )}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" id="taskModalDescription" rows="4">${this.escapeHtml(
                                          task.description || ""
                                        )}</textarea>
                                    </div>
                                    ${
                                      task.images && task.images.length > 0
                                        ? `
                                        <div class="mb-3">
                                            <label class="form-label">Images</label>
                                            <div class="task-images-grid">
                                                ${this.renderTaskImages(
                                                  task.images
                                                )}
                                            </div>
                                        </div>
                                    `
                                        : ""
                                    }
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-control" id="taskModalStatus">
                                            ${this.statuses
                                              .map(
                                                (status) =>
                                                  `<option value="${
                                                    status.id
                                                  }" ${
                                                    status.id == task.status_id
                                                      ? "selected"
                                                      : ""
                                                  }>${status.title}</option>`
                                              )
                                              .join("")}
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Priority</label>
                                        <select class="form-control" id="taskModalPriority">
                                            <option value="1" ${
                                              task.priority_id == 1
                                                ? "selected"
                                                : ""
                                            }>Normal</option>
                                            <option value="2" ${
                                              task.priority_id == 2
                                                ? "selected"
                                                : ""
                                            }>Medium</option>
                                            <option value="3" ${
                                              task.priority_id == 3
                                                ? "selected"
                                                : ""
                                            }>High</option>
                                            <option value="4" ${
                                              task.priority_id == 4
                                                ? "selected"
                                                : ""
                                            }>Critical</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Assignee</label>
                                        <div class="assignee-info">
                                            ${
                                              task.first_name || task.last_name
                                                ? `<div class="d-flex align-items-center">
                                                    <div class="assignee-avatar me-2">
                                                        ${
                                                          task.user_image
                                                            ? `<img src="${this.parseUserImage(
                                                                task.user_image
                                                              )}" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%;">`
                                                            : `<span class="avatar-initials">${this.getInitials(
                                                                `${
                                                                  task.first_name ||
                                                                  ""
                                                                } ${
                                                                  task.last_name ||
                                                                  ""
                                                                }`
                                                              )}</span>`
                                                        }
                                                    </div>
                                                    <span>${
                                                      task.first_name || ""
                                                    } ${
                                                    task.last_name || ""
                                                  }</span>
                                                </div>`
                                                : '<span class="text-muted">Unassigned</span>'
                                            }
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Deadline</label>
                                        <input type="date" class="form-control" id="taskModalDeadline" 
                                               value="${
                                                 task.deadline
                                                   ? task.deadline.split("T")[0]
                                                   : ""
                                               }">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Created</label>
                                        <div class="text-muted">${
                                          task.created_date
                                            ? this.formatDate(task.created_date)
                                            : "Unknown"
                                        }</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" onclick="window.nodeJSEnhancedKanbanBoard.deleteTask(${
                              task.id
                            })">
                                <i class="fas fa-trash"></i> Delete Task
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="saveTaskChanges" data-task-id="${
                              task.id
                            }">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Remove existing modal
    const existingModal = document.getElementById("taskModal");
    if (existingModal) existingModal.remove();

    // Add modal to page
    document.body.insertAdjacentHTML("beforeend", modalHtml);

    // Show modal (custom implementation without Bootstrap dependency)
    const modalElement = document.getElementById("taskModal");
    this.showCustomModal(modalElement);

    // Handle save changes
    document.getElementById("saveTaskChanges").addEventListener("click", () => {
      this.handleSaveTaskChanges(modalElement, task.id);
    });
  }

  renderTaskImages(images) {
    if (!images || images.length === 0) return "";

    return images
      .map((image) => {
        const imageUrl =
          typeof image === "string"
            ? image
            : image.url ||
              `https://dojob.rubyshop168.com/files/timeline_files/${
                image.filename || image.file_name
              }`;
        return `<img src="${imageUrl}" alt="Task image" style="width: 100px; height: 100px; object-fit: cover; margin: 5px; border-radius: 4px;">`;
      })
      .join("");
  }

  async handleSaveTaskChanges(modal, taskId) {
    const title = document.getElementById("taskModalTitle").value.trim();
    const description = document
      .getElementById("taskModalDescription")
      .value.trim();
    const status_id = parseInt(
      document.getElementById("taskModalStatus").value
    );
    const priority_id = parseInt(
      document.getElementById("taskModalPriority").value
    );
    const deadline = document.getElementById("taskModalDeadline").value;

    if (!title) {
      this.showError("Task title is required");
      return;
    }

    try {
      const response = await fetch(`${this.apiBase}/task/${taskId}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          title: title,
          description: description,
          status_id: status_id,
          priority_id: priority_id,
          deadline: deadline || null,
        }),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess("Task updated successfully");
        modal.hide();
        setTimeout(() => this.loadTasks(), 500);
      } else {
        this.showError(result.error || "Failed to update task");
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
    }
  }

  async deleteTask(taskId) {
    if (
      !confirm(
        "Are you sure you want to delete this task? This action cannot be undone."
      )
    ) {
      return;
    }

    try {
      const response = await fetch(`${this.apiBase}/task/${taskId}`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess("Task deleted successfully");
        // Close modal if it's open
        const modal = document.getElementById("taskModal");
        if (modal) {
          this.hideCustomModal(modal);
        }
        setTimeout(() => this.loadTasks(), 500);
      } else {
        this.showError(result.error || "Failed to delete task");
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
    }
  }

  openCreateTaskModal(status) {
    console.log("🔍 Opening inline task form for status:", status);

    // Set processing flag
    this.isProcessingAddTask = true;

    // Find the create task button for this status and trigger the inline form
    const createButton = document.querySelector(
      `[data-status="${status}"].create-task-button`
    );

    if (createButton) {
      console.log(" Found create button, showing inline form");
      // Trigger the existing inline task form
      this.showInlineTaskForm(createButton, status);

      // Scroll to the form so user can see it, but keep tabs visible
      setTimeout(() => {
        const form = document.querySelector(".inline-task-form");
        if (form) {
          // Calculate scroll position to keep tabs visible
          const tabsElement = document.getElementById("project-tabs");
          const tabsHeight = tabsElement ? tabsElement.offsetHeight + 20 : 80;

          // Scroll to form but offset by tabs height
          const formRect = form.getBoundingClientRect();
          const scrollTop = window.pageYOffset + formRect.top - tabsHeight - 20;

          window.scrollTo({
            top: Math.max(0, scrollTop),
            behavior: "smooth",
          });

          // Focus on the input field
          const input = form.querySelector(".task-title-input");
          if (input) {
            input.focus();
          }
        }
        // Reset flag after form is shown
        this.isProcessingAddTask = false;
      }, 100);
    } else {
      console.log("⚠️ Create button not found, using fallback");
      // Fallback: use simple prompt-based creation
      this.showSimpleCreateTaskForm(status);
      // Reset flag after fallback
      this.isProcessingAddTask = false;
    }
  }

  showSimpleCreateTaskForm(status) {
    // Prevent multiple prompts
    if (this.isShowingPrompt) {
      console.log("⚠️ Prompt already showing, ignoring duplicate request");
      return;
    }

    this.isShowingPrompt = true;

    const statusObj = this.statuses.find((s) => s.key_name === status);
    const statusTitle = statusObj ? statusObj.title : status;

    const title = prompt(
      `Create new task in "${statusTitle}":\n\nEnter task title:`
    );

    this.isShowingPrompt = false;

    if (title && title.trim()) {
      this.createTaskDirectly(title.trim(), status);
    }
  }

  async createTaskDirectly(title, status) {
    // Create unique key for this specific task creation (without timestamp for better duplicate detection)
    const creationKey = `${title}_${status}`;

    console.log("🔍 createTaskDirectly called:", {
      title,
      status,
      creationKey,
      isCreatingTaskDirect: this.isCreatingTaskDirect,
      recentCreationsSize: this.recentCreations?.size || 0,
      hasRecentCreation: this.recentCreations?.has(creationKey),
      callStack: new Error().stack,
    });

    // Prevent multiple simultaneous creation attempts
    if (this.isCreatingTaskDirect) {
      console.log(
        "⚠️ Direct task creation already in progress, ignoring request"
      );
      return;
    }

    // Check for recent duplicate
    if (this.recentCreations?.has(creationKey)) {
      console.log("⚠️ Duplicate task creation detected, ignoring request");
      return;
    }

    // Check global lock
    if (window.globalTaskCreationLock?.has(creationKey)) {
      console.log("⚠️ Global task creation lock detected, ignoring request");
      return;
    }

    // Initialize recent creations set if not exists
    if (!this.recentCreations) {
      this.recentCreations = new Set();
    }

    // Set all flags to prevent duplicates
    this.isCreatingTaskDirect = true;
    this.recentCreations.add(creationKey);
    window.globalTaskCreationLock.add(creationKey);

    console.log(" Starting task creation:", creationKey);

    // Clean up recent creations after 3 seconds (shorter window)
    setTimeout(() => {
      this.recentCreations.delete(creationKey);
      window.globalTaskCreationLock.delete(creationKey);
      console.log("🧹 Cleaned up creation key:", creationKey);
    }, 3000);

    try {
      const statusObj = this.statuses.find((s) => s.key_name === status);
      if (!statusObj) {
        this.showError("Invalid status");
        this.isCreatingTaskDirect = false;
        return;
      }

      // Get current user ID
      const currentUserId = await this.getCurrentUserId();

      // Calculate next sort value for this status (to appear at bottom)
      const nextSortValue = await this.getNextSortValue(statusObj.id);

      console.log(
        `🔄 Creating direct task in ${statusObj.title} with sort ${nextSortValue}, assigned to user ${currentUserId}`
      );

      const response = await fetch(`${this.apiBase}/tasks`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          title: title,
          project_id: this.projectId,
          status_id: statusObj.id,
          priority_id: 2, // Medium priority
          assigned_to: currentUserId,
          sort: nextSortValue,
        }),
      });

      const result = await response.json();
      console.log("📋 Create direct task response:", result);

      if (result.success || result.id) {
        this.showSuccess(`Task created in ${statusObj.title}`);
        setTimeout(() => this.loadTasks(), 500);
        return; // Exit early to prevent duplicate creation
      } else {
        this.showError(
          result.error || result.message || "Failed to create task"
        );
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
    } finally {
      // Always reset the flag to allow future task creation
      this.isCreatingTaskDirect = false;
    }
  }

  async handleCreateTaskModal(modal, status) {
    const title = document.getElementById("taskTitle").value.trim();
    const description = document.getElementById("taskDescription").value.trim();
    const priority = document.getElementById("taskPriority").value;

    if (!title) {
      this.showError("Task title is required");
      return;
    }

    try {
      // Find status ID from key
      const statusObj = this.statuses.find((s) => s.key_name === status);
      if (!statusObj) {
        this.showError("Invalid status");
        return;
      }

      const response = await fetch(`${this.apiBase}/tasks`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          title: title,
          description: description,
          project_id: this.projectId,
          status_id: statusObj.id,
          priority_id: priority,
          assigned_to: 0, // Unassigned for now
        }),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess("Task created successfully");
        modal.hide();
        setTimeout(() => this.loadTasks(), 500);
      } else {
        this.showError(result.error || "Failed to create task");
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
    }
  }

  showInlineTaskForm(button, status) {
    this.hideAllInlineForms();
    const formHtml = `
            <div class="inline-task-form" data-status="${status}">
                <textarea class="task-title-input" placeholder="What needs to be done?" rows="2" autofocus></textarea>
                <div class="form-actions">
                    <button class="btn-create-task btn-primary">Create</button>
                    <button class="btn-cancel-task btn-secondary">Cancel</button>
                    <div class="form-controls">
                        <button type="button" title="Add description"><i class="fas fa-align-left"></i></button>
                        <button type="button" title="Set due date"><i class="fas fa-calendar"></i></button>
                        <button type="button" title="Assign"><i class="fas fa-user"></i></button>
                        <button type="button" title="Add attachment"><i class="fas fa-paperclip"></i></button>
                    </div>
                </div>
            </div>
        `;
    button.insertAdjacentHTML("afterend", formHtml);
    const textarea =
      button.nextElementSibling.querySelector(".task-title-input");
    if (textarea) textarea.focus();
  }

  hideInlineTaskForm(element) {
    const form = element.closest(".inline-task-form");
    if (form) form.remove();
  }

  hideAllInlineForms() {
    document
      .querySelectorAll(".inline-task-form")
      .forEach((form) => form.remove());
  }

  async handleCreateTask(button) {
    // Prevent multiple simultaneous creation attempts
    if (this.isCreatingTask) {
      console.log(
        "⚠️ Task creation already in progress, ignoring duplicate request"
      );
      return;
    }

    this.isCreatingTask = true;

    const form = button.closest(".inline-task-form");
    const textarea = form.querySelector(".task-title-input");
    const title = textarea.value.trim();
    const status = form.dataset.status;

    if (!title) {
      this.showError("Task title is required");
      this.isCreatingTask = false;
      return;
    }

    try {
      // Find status ID from key
      const statusObj = this.statuses.find((s) => s.key_name === status);
      if (!statusObj) {
        this.showError("Invalid status");
        this.isCreatingTask = false;
        return;
      }

      // Get current user ID
      const currentUserId = await this.getCurrentUserId();

      // Calculate next sort value for this status (to appear at bottom)
      const nextSortValue = await this.getNextSortValue(statusObj.id);

      console.log(
        `🔄 Creating task in ${statusObj.title} with sort ${nextSortValue}, assigned to user ${currentUserId}`
      );

      const response = await fetch(`${this.apiBase}/tasks`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          title: title,
          project_id: this.projectId,
          status_id: statusObj.id,
          priority_id: 2, // Medium priority
          assigned_to: currentUserId,
          sort: nextSortValue,
        }),
      });

      const result = await response.json();
      console.log("📋 Create task response:", result);

      if (result.success || result.id) {
        this.showSuccess(`Task created in ${statusObj.title}`);
        form.remove();
        setTimeout(() => this.loadTasks(), 500);
        return; // Exit early to prevent duplicate creation
      } else {
        this.showError(
          result.error || result.message || "Failed to create task"
        );
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
    } finally {
      // Always reset the flag to allow future task creation
      this.isCreatingTask = false;
    }
  }

  async getCurrentUserId() {
    try {
      // Try local API first (more reliable with session)
      const localResponse = await fetch(
        `${window.location.origin}/api/current_user`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "include",
        }
      );

      if (localResponse.ok) {
        const localResult = await localResponse.json();
        if (localResult.success && localResult.data && localResult.data.id) {
          console.log(
            " Current user ID from local API:",
            localResult.data.id
          );
          return localResult.data.id;
        }
      }

      // Try external API as fallback
      const response = await fetch(`${this.apiBase}/current-user`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success && result.data && result.data.id) {
          console.log(" Current user ID from external API:", result.data.id);
          return result.data.id;
        }
      }

      // Try to get from global variables or DOM
      if (window.login_user_id) {
        console.log(
          " Current user ID from window.login_user_id:",
          window.login_user_id
        );
        return window.login_user_id;
      }

      if (window.currentUserId) {
        console.log(
          " Current user ID from window.currentUserId:",
          window.currentUserId
        );
        return window.currentUserId;
      }

      // Try to extract from page elements
      const userElement = document.querySelector("[data-user-id]");
      if (userElement) {
        const userId = parseInt(userElement.dataset.userId);
        if (userId) {
          console.log(" Current user ID from DOM element:", userId);
          return userId;
        }
      }

      console.error(
        "❌ Could not determine current user ID, using your ID as fallback"
      );
      return 22; // Your user ID as fallback instead of 1
    } catch (error) {
      console.error("❌ Error getting current user:", error);
      return 22; // Your user ID as fallback instead of 1
    }
  }

  async getNextSortValue(statusId) {
    try {
      // Get all tasks in this status to find the highest sort value
      const response = await fetch(
        `${this.apiBase}/tasks/max-sort/${this.projectId}/${statusId}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
        }
      );

      if (response.ok) {
        const result = await response.json();
        if (result.success && typeof result.maxSort === "number") {
          const nextSort = result.maxSort + 1000; // Add 1000 for spacing
          console.log(` Next sort value for status ${statusId}: ${nextSort}`);
          return nextSort;
        }
      }

      // Fallback: calculate from current board data
      const statusTasks = this.getTasksForStatus(
        this.statuses.find((s) => s.id === statusId)?.key_name || "to_do"
      );

      let maxSort = 0;
      statusTasks.forEach((task) => {
        const taskSort = parseInt(task.sort) || parseInt(task.id) || 0;
        if (taskSort > maxSort) {
          maxSort = taskSort;
        }
      });

      // Ensure we have a reasonable minimum value
      if (maxSort === 0) {
        maxSort = 1000; // Use reasonable starting value instead of timestamp
      }

      const nextSort = Math.min(maxSort + 100, 2000000000); // Cap at 2 billion
      console.log(
        `⚠️ Using fallback sort calculation for status ${statusId}:`,
        {
          statusTasks: statusTasks.length,
          maxSort,
          nextSort,
          taskSorts: statusTasks.map((t) => ({
            title: t.title,
            sort: t.sort,
            id: t.id,
          })),
        }
      );
      return nextSort;
    } catch (error) {
      console.error("❌ Error calculating next sort value:", error);
      // Ultimate fallback: use reasonable value
      return 1000;
    }
  }

  async reorderTask(taskId, direction) {
    console.log(`🔄 Reordering Task ${taskId} ${direction}`);
    try {
      const response = await fetch(`${this.apiBase}/task/${taskId}/reorder`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({
          direction: direction,
          project_id: this.projectId,
        }),
      });

      const result = await response.json();
      if (result.success) {
        console.log(" Task reordered successfully");
        this.showSuccess(`Task moved ${direction}`);
        setTimeout(() => this.loadTasks(), 300);
      } else {
        console.error("❌ Reorder failed:", result.error);
        this.showError(result.error || "Failed to reorder task");
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
    }
  }

  showColumnMenu(button, statusId) {
    // Remove existing menus
    document
      .querySelectorAll(".column-context-menu")
      .forEach((menu) => menu.remove());

    const status = this.statuses.find((s) => s.id == statusId);
    if (!status) return;

    const menuHtml = `
            <div class="column-context-menu" style="position: absolute; z-index: 1000; background: white; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); min-width: 150px;">
                <div class="menu-item" data-action="edit" data-status-id="${statusId}">
                    <i class="fas fa-edit"></i> Edit Status
                </div>
                <div class="menu-item" data-action="toggle-visibility" data-status-id="${statusId}">
                    <i class="fas fa-eye${
                      status.hide_from_kanban ? "" : "-slash"
                    }"></i> 
                    ${status.hide_from_kanban ? "Show" : "Hide"} in Kanban
                </div>
                <div class="menu-divider"></div>
                <div class="menu-item text-danger" data-action="delete" data-status-id="${statusId}">
                    <i class="fas fa-trash"></i> Delete Status
                </div>
            </div>
        `;

    document.body.insertAdjacentHTML("beforeend", menuHtml);

    const menu = document.querySelector(".column-context-menu");
    const rect = button.getBoundingClientRect();

    menu.style.left = `${rect.left}px`;
    menu.style.top = `${rect.bottom + 5}px`;

    // Handle menu clicks
    menu.addEventListener("click", (e) => {
      const action = e.target.closest(".menu-item")?.dataset.action;
      const statusId = e.target.closest(".menu-item")?.dataset.statusId;

      if (action && statusId) {
        this.handleColumnMenuAction(action, statusId);
      }

      menu.remove();
    });

    // Close menu on outside click
    setTimeout(() => {
      document.addEventListener("click", function closeMenu(e) {
        if (!menu.contains(e.target)) {
          menu.remove();
          document.removeEventListener("click", closeMenu);
        }
      });
    }, 100);
  }

  showTaskMenu(button, taskId) {
    console.log("🔍 showTaskMenu called with:", { button, taskId });

    // Check if menu is already open for this button
    const existingMenu = document.querySelector(".task-context-menu");
    const currentButtonId = button.dataset.taskId;

    console.log("🔍 Existing menu:", existingMenu);
    console.log("🔍 Current button ID:", currentButtonId);

    if (existingMenu) {
      // If menu exists, check if it's for the same button
      const existingTaskId = existingMenu.dataset.taskId;
      console.log("🔍 Existing task ID:", existingTaskId);
      if (existingTaskId === currentButtonId) {
        // Same button clicked - close the menu (toggle off)
        console.log("🔄 Closing existing menu (same button)");
        existingMenu.remove();
        return;
      } else {
        // Different button clicked - remove old menu and create new one
        console.log("🔄 Removing old menu (different button)");
        existingMenu.remove();
      }
    }

    const menuHtml = `
      <div class="task-context-menu" data-task-id="${taskId}" style="
        position: fixed !important; 
        z-index: 9999 !important; 
        background: white !important; 
        border: 2px solid #6b7280 !important; 
        border-radius: 4px !important; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important; 
        min-width: 120px !important;
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
      ">
        <div class="menu-item" data-action="edit" data-task-id="${taskId}" style="
          padding: 8px 12px !important;
          cursor: pointer !important;
          display: flex !important;
          align-items: center !important;
          gap: 8px !important;
          font-size: 13px !important;
          color: #374151 !important;
          background: white !important;
        ">
          📝 Edit
        </div>
        <div class="menu-divider" style="
          height: 1px !important;
          background-color: #e5e7eb !important;
          margin: 4px 0 !important;
        "></div>
        <div class="menu-item text-danger" data-action="delete" data-task-id="${taskId}" style="
          padding: 8px 12px !important;
          cursor: pointer !important;
          display: flex !important;
          align-items: center !important;
          gap: 8px !important;
          font-size: 13px !important;
          color: #dc2626 !important;
          background: white !important;
        ">
          🗑️ Delete
        </div>
      </div>
    `;

    console.log("🔄 Creating menu HTML...");
    document.body.insertAdjacentHTML("beforeend", menuHtml);

    const menu = document.querySelector(".task-context-menu");
    console.log("🔍 Menu element created:", menu);

    if (!menu) {
      console.error("❌ Failed to create menu element!");
      return;
    }

    const rect = button.getBoundingClientRect();
    console.log("🔍 Button rect:", rect);

    // Position menu to the bottom-right of the button
    const leftPos = rect.right - 120;
    const topPos = rect.bottom + 5;

    menu.style.left = `${leftPos}px`;
    menu.style.top = `${topPos}px`;

    console.log("🔍 Menu positioned at:", { left: leftPos, top: topPos });
    console.log("🔍 Menu styles:", {
      position: menu.style.position,
      left: menu.style.left,
      top: menu.style.top,
      zIndex: menu.style.zIndex,
      display: menu.style.display,
    });

    // Handle menu clicks
    menu.addEventListener("click", (e) => {
      const action = e.target.closest(".menu-item")?.dataset.action;
      const taskId = e.target.closest(".menu-item")?.dataset.taskId;

      if (action && taskId) {
        this.handleTaskMenuAction(action, taskId);
      }

      menu.remove();
    });

    // Close menu on outside click
    setTimeout(() => {
      document.addEventListener("click", function closeTaskMenu(e) {
        if (!menu.contains(e.target) && !e.target.closest(".task-menu-btn")) {
          menu.remove();
          document.removeEventListener("click", closeTaskMenu);
        }
      });
    }, 100);
  }

  async handleTaskMenuAction(action, taskId) {
    switch (action) {
      case "edit":
        this.openTaskModal(taskId);
        break;
      case "delete":
        await this.confirmDeleteTask(taskId);
        break;
    }
  }

  async confirmDeleteTask(taskId) {
    // Use SweetAlert for confirmation
    const result = await Swal.fire({
      title: "Delete Task",
      text: "Are you sure you want to delete this task? This action cannot be undone.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc2626",
      cancelButtonColor: "#6b7280",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
      focusCancel: true,
    });

    if (!result.isConfirmed) {
      return;
    }

    try {
      const response = await fetch(`${this.apiBase}/task/${taskId}`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
      });

      const apiResult = await response.json();

      if (apiResult.success) {
        this.showSuccess("Task deleted successfully");
        setTimeout(() => this.loadTasks(), 500);
      } else {
        this.showError(apiResult.error || "Failed to delete task");
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
    }
  }

  async handleColumnMenuAction(action, statusId) {
    switch (action) {
      case "edit":
        this.showEditStatusModal(statusId);
        break;
      case "toggle-visibility":
        await this.toggleStatusVisibility(statusId);
        break;
      case "delete":
        await this.deleteStatus(statusId);
        break;
    }
  }

  showEditStatusModal(statusId) {
    const status = this.statuses.find((s) => s.id == statusId);
    if (!status) return;

    const newTitle = prompt(
      `Edit status "${status.title}":\n\nEnter new title:`,
      status.title
    );
    if (newTitle && newTitle.trim() && newTitle !== status.title) {
      const newColor = prompt("Enter new color (hex code):", status.color);
      this.updateStatusDirectly(
        statusId,
        newTitle.trim(),
        newColor || status.color
      );
    }
  }

  async updateStatusDirectly(statusId, title, color) {
    try {
      const response = await fetch(`${this.apiBase}/statuses/${statusId}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          title: title,
          color: color,
        }),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        setTimeout(() => this.loadStatuses(), 500);
      } else {
        this.showError(result.error);
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Failed to update status");
    }
  }

  async handleUpdateStatus(modal, statusId) {
    const title = document.getElementById("editStatusTitle").value.trim();
    const color = document.getElementById("editStatusColor").value;

    if (!title) {
      this.showError("Status title is required");
      return;
    }

    try {
      const response = await fetch(`${this.apiBase}/statuses/${statusId}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          title: title,
          color: color,
        }),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        modal.hide();
        setTimeout(() => this.loadStatuses(), 500);
      } else {
        this.showError(result.error);
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Failed to update status");
    }
  }

  async toggleStatusVisibility(statusId) {
    const status = this.statuses.find((s) => s.id == statusId);
    if (!status) return;

    const newVisibility = status.hide_from_kanban ? 0 : 1;

    try {
      const response = await fetch(
        `${this.apiBase}/statuses/${statusId}/visibility`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({
            hide_from_kanban: newVisibility,
          }),
        }
      );

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        setTimeout(() => this.loadStatuses(), 500);
      } else {
        this.showError(result.error);
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Failed to update status visibility");
    }
  }

  async deleteStatus(statusId) {
    const status = this.statuses.find((s) => s.id == statusId);
    if (!status) return;

    if (
      !confirm(
        `Are you sure you want to delete the "${status.title}" status? This action cannot be undone.`
      )
    ) {
      return;
    }

    try {
      const response = await fetch(`${this.apiBase}/statuses/${statusId}`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        setTimeout(() => this.loadStatuses(), 500);
      } else {
        this.showError(result.error);
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Failed to delete status");
    }
  }

  showManageStatusesModal() {
    // Simple status list display
    const statusList = this.statuses
      .map((s) => `• ${s.title} (${s.key_name})`)
      .join("\n");
    alert(
      `Current Statuses:\n\n${statusList}\n\nUse the column context menus (right-click) to edit individual statuses.`
    );
  }

  renderStatusesList() {
    return this.statuses
      .map(
        (status) => `
            <div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2">
                <div class="d-flex align-items-center">
                    <div class="status-color-indicator" style="width: 20px; height: 20px; background: ${
                      status.color
                    }; border-radius: 50%; margin-right: 10px;"></div>
                    <div>
                        <strong>${status.title}</strong>
                        <small class="text-muted d-block">${
                          status.key_name
                        }</small>
                    </div>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="window.nodeJSEnhancedKanbanBoard.showEditStatusModal(${
                      status.id
                    })">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary me-1" onclick="window.nodeJSEnhancedKanbanBoard.toggleStatusVisibility(${
                      status.id
                    })">
                        <i class="fas fa-eye${
                          status.hide_from_kanban ? "" : "-slash"
                        }"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="window.nodeJSEnhancedKanbanBoard.deleteStatus(${
                      status.id
                    })">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `
      )
      .join("");
  }

  // Cleanup method to restore normal scrolling when kanban is destroyed
  cleanup() {
    console.log("🧹 Cleaning up NodeJS Enhanced Kanban...");

    // Restore normal page scrolling
    this.disableKanbanScrollbarMode();

    // Remove any event listeners
    // (Note: Most event listeners are attached to document, so they'll persist
    // but that's okay since they check for element existence)

    console.log(" NodeJS Enhanced Kanban cleanup completed");
  }

  // Simplified status and task management using existing system
}

// Initialize Node.js enhanced kanban board
window.addEventListener("DOMContentLoaded", () => {
  console.log("🔧 NodeJS Enhanced Kanban script loaded and ready");
  // Note: Kanban board will be initialized when Board tab is clicked
});

window.initializeNodeJSEnhancedKanban = function (projectId) {
  console.log(
    "🚀 Initializing NodeJS Enhanced Kanban with project ID:",
    projectId
  );

  try {
    // Clean up any existing instance
    if (window.nodeJSEnhancedKanbanBoard) {
      window.nodeJSEnhancedKanbanBoard.cleanup();
    }

    window.nodeJSEnhancedKanbanBoard = new NodeJSEnhancedKanban(projectId);
    console.log(" NodeJS Enhanced Kanban instance created successfully");
    return true;
  } catch (error) {
    console.error("❌ Error creating NodeJS Enhanced Kanban instance:", error);
    return false;
  }
};

// Global cleanup function for when switching away from kanban board
window.cleanupNodeJSEnhancedKanban = function () {
  if (window.nodeJSEnhancedKanbanBoard) {
    window.nodeJSEnhancedKanbanBoard.cleanup();
    window.nodeJSEnhancedKanbanBoard = null;
    console.log(" NodeJS Enhanced Kanban cleaned up");
  }
};
