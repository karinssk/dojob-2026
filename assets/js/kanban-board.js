/**
 * Modern Kanban Board with Image Support
 */

class KanbanBoard {
  constructor(projectId) {
    this.projectId = projectId;
    this.boardData = {};
    this.init();
  }

  init() {
    this.loadTasks();
    this.setupEventListeners();
  }

  async loadTasks() {
    try {
      console.log("🔄 Loading tasks from Node.js API...");

      // Use Node.js API instead of PHP
      const response = await fetch(
        `https://api-dojob.rubyshop.co.th/api/kanban/${this.projectId}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
        }
      );

      console.log("📡 API Response status:", response.status);

      const result = await response.json();
      console.log("📊 API Result:", result);

      if (result.success) {
        console.log("✅ API data received:", result.data);

        // Use the API data directly (it's already in the right format)
        this.boardData = result.data;
        console.log("📋 Board data set:", this.boardData);

        this.renderBoard();
      } else {
        console.error("❌ API Error:", result.error);
        this.showError(result.error);
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError(
        "Failed to load tasks - make sure Node.js API is running on port 3001"
      );
    }
  }

  // Transform Node.js API data to match expected kanban format
  transformApiData(apiData) {
    const transformed = {
      to_do: [],
      in_progress: [],
      done: [],
    };

    // Group tasks by status
    apiData.forEach((column) => {
      const statusKey =
        column.key_name || column.title.toLowerCase().replace(" ", "_");

      if (column.tasks && column.tasks.length > 0) {
        transformed[statusKey] = column.tasks.map((task) => ({
          id: task.id,
          key: `TASK-${task.id}`,
          title: task.title,
          description: task.description,
          priority: this.mapPriority(task.priority_id),
          priority_color: task.priority_color || "#666",
          deadline: task.deadline,
          assignee: task.assigned_to
            ? {
                name:
                  `${task.first_name || ""} ${task.last_name || ""}`.trim() ||
                  "Unassigned",
                avatar: task.user_image
                  ? this.parseUserImage(task.user_image)
                  : null,
                initials: this.getInitials(
                  `${task.first_name || ""} ${task.last_name || ""}`
                ),
              }
            : null,
          images: task.images || [],
        }));
      }
    });

    return transformed;
  }

  // Map priority ID to priority name
  mapPriority(priorityId) {
    const priorityMap = {
      0: "low",
      1: "normal",
      2: "medium",
      3: "high",
      4: "high",
    };
    return priorityMap[priorityId] || "normal";
  }

  // Parse user image from database format
  parseUserImage(imageData) {
    if (!imageData) return null;

    try {
      // Handle serialized PHP array format
      if (typeof imageData === "string" && imageData.includes("file_name")) {
        const match = imageData.match(/s:\d+:"([^"]+)"/);
        if (match) {
          return `${window.baseUrl}files/profile_images/${match[1]}`;
        }
      }
      return imageData;
    } catch (e) {
      return null;
    }
  }

  // Get initials from name
  getInitials(name) {
    if (!name || name === "Unassigned") return "U";
    return name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
      .substring(0, 2);
  }

  renderBoard() {
    const boardContainer = document.getElementById("kanban-board-container");
    if (!boardContainer) {
      console.error("❌ Board container not found");
      return;
    }

    console.log("🎨 Rendering board with data:", this.boardData);

    // Validate board data
    if (!this.boardData) {
      console.error("❌ No board data available");
      boardContainer.innerHTML =
        '<div class="error-message">No board data available</div>';
      return;
    }

    // Use the API data directly (it's already in the right format)
    const columns = Array.isArray(this.boardData)
      ? this.boardData
      : Object.values(this.boardData);

    console.log("📊 Columns to render:", columns);

    if (!columns || columns.length === 0) {
      console.error("❌ No columns to render");
      boardContainer.innerHTML =
        '<div class="error-message">No columns available</div>';
      return;
    }

    try {
      boardContainer.innerHTML = `
              <div class="kanban-board">
                  ${columns
                    .map((column) => {
                      if (!column) {
                        console.warn("⚠️ Skipping null/undefined column");
                        return "";
                      }

                      const statusKey =
                        column.key_name ||
                        (column.title
                          ? column.title.toLowerCase().replace(/\s+/g, "_")
                          : "unknown");
                      const tasks = column.tasks || [];
                      const title = column.title || "Unknown Status";

                      console.log(
                        `📋 Rendering column: ${title} with ${tasks.length} tasks`
                      );

                      return `
                      <div class="kanban-column" data-status="${statusKey}">
                          <div class="column-header">
                              <div class="column-title">
                                  ${title.toUpperCase()}
                                  <span class="task-count">${
                                    tasks.length
                                  }</span>
                              </div>
                              <button class="add-task-btn" data-status="${statusKey}">
                                  <i class="fas fa-plus"></i>
                              </button>
                          </div>
                          <div class="tasks-container" data-status="${statusKey}">
                              ${this.renderTasks(tasks, statusKey)}
                          </div>
                      </div>
                    `;
                    })
                    .filter((html) => html.length > 0) // Remove empty columns
                    .join("")}
              </div>
          `;

      console.log("✅ Board rendered successfully");
      this.setupDragAndDrop();
    } catch (error) {
      console.error("❌ Error rendering board:", error);
      boardContainer.innerHTML = `<div class="error-message">Error rendering board: ${error.message}</div>`;
    }
  }

  renderTasks(tasks, status) {
    if (tasks.length === 0) {
      // If no tasks, show a create-only card
      return this.renderCreateOnlyCard(status);
    }

    // Render all tasks, with create button integrated into the last task
    return tasks
      .map((task, index) => {
        const isLastTask = index === tasks.length - 1;
        return this.renderTaskCard(task, isLastTask, status);
      })
      .join("");
  }

  renderTaskCard(task, isLastTask = false, status = null) {
    const hasImages = task.images && task.images.length > 0;
    const imagePreview = hasImages ? this.renderImagePreview(task.images) : "";

    // Create assignee info from task data
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

    // Map priority
    const priority = this.mapPriority(task.priority_id);
    const priorityIndicator = this.renderPriorityIndicator(
      priority,
      task.priority_color
    );

    // Ensure clean task ID
    let cleanTaskId = parseInt(task.id, 10);
    if (!cleanTaskId || cleanTaskId < 1) {
      console.error("Invalid task ID:", task.id);
      cleanTaskId = 0;
    }

    // Use provided status or determine from task
    const taskStatus = status || this.getTaskStatus(task);

    return `
            <div class="task-card ${
              isLastTask ? "last-task" : ""
            }" data-task-id="${cleanTaskId}" draggable="true">
                <div class="task-header">
                    <span class="task-key">TASK-${task.id}</span>
                    <div class="task-header-actions">
                        <div class="reorder-buttons">
                            <button class="reorder-btn reorder-up" data-task-id="${cleanTaskId}" data-direction="up" title="Move up">
                                🔺🔺
                            </button>
                            <button class="reorder-btn reorder-down" data-task-id="${cleanTaskId}" data-direction="down" title="Move down">
                                🔻🔻🔻
                            </button>
                        </div>
                        ${priorityIndicator}
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
                
                ${isLastTask ? this.renderCreateSection(taskStatus) : ""}
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

  getTaskStatus(task) {
    // Try to determine status from task data
    if (task.status_id) {
      const statusMap = {
        1: "to_do",
        2: "in_progress",
        3: "done",
      };
      return statusMap[task.status_id] || "to_do";
    }
    return "to_do"; // default
  }

  renderImagePreview(images) {
    if (!images || images.length === 0) return "";

    // Helper function to extract image URL from different formats
    const getImageUrl = (image) => {
      let urlImg = 'https://dojob.rubyshop168.com'
      if (typeof image === "string") {
        return image;
      } else if (typeof image === "object" && image !== null) {
        // Handle different object formats
        if (image.url) {
          return image.url;
        } else if (image.filename) {
          return `${urlImg}/${image.filename}`;
        } else if (image.file_name) {
          return `${urlImg}/${image.file_name}`;
        }
      }
      return null;
    };

    if (images.length === 1) {
      const imageUrl = getImageUrl(images[0]);
      if (!imageUrl) return "";

      return `
                <div class="task-images single-image">
                    <img src="${imageUrl}" alt="Task image" class="task-image" loading="lazy">
                </div>
            `;
    } else {
      const firstImageUrl = getImageUrl(images[0]);
      if (!firstImageUrl) return "";

      const remainingCount = images.length - 1;

      return `
                <div class="task-images multiple-images">
                    <img src="${firstImageUrl}" alt="Task image" class="task-image main-image" loading="lazy">
                    <div class="image-overlay">
                        <span class="image-count">+${remainingCount}</span>
                    </div>
                </div>
            `;
    }
  }

  renderAssigneeAvatar(assignee) {
    if (assignee.avatar && assignee.avatar !== "") {
      return `
                <div class="assignee-avatar" title="${this.escapeHtml(
                  assignee.name
                )}">
                    <img src="${assignee.avatar}" alt="${this.escapeHtml(
        assignee.name
      )}" class="avatar-img">
                </div>
            `;
    } else {
      return `
                <div class="assignee-avatar initials" title="${this.escapeHtml(
                  assignee.name
                )}">
                    <span class="avatar-initials">${assignee.initials}</span>
                </div>
            `;
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

    return `
            <span class="priority-indicator priority-${priority}" style="color: ${color}" title="${
      priority.charAt(0).toUpperCase() + priority.slice(1)
    } Priority">
                <i class="${icon}"></i>
            </span>
        `;
  }

  setupDragAndDrop() {
    const taskCards = document.querySelectorAll(".task-card");
    const containers = document.querySelectorAll(".tasks-container");

    // Setup drag events for task cards (but not create-only cards)
    taskCards.forEach((card) => {
      // Skip create-only cards for drag and drop
      if (card.dataset.createOnly === "true") {
        return;
      }

      card.addEventListener("dragstart", (e) => {
        e.dataTransfer.setData("text/plain", card.dataset.taskId);
        card.classList.add("dragging");
      });

      card.addEventListener("dragend", () => {
        card.classList.remove("dragging");
      });

      // Click to open task modal (but not if clicking create button or reorder buttons)
      card.addEventListener("click", (e) => {
        // Don't open modal if clicking on create button, create section, or reorder buttons
        if (
          e.target.closest(".create-task-button") ||
          e.target.closest(".task-create-section") ||
          e.target.closest(".inline-task-form") ||
          e.target.closest(".reorder-btn") ||
          e.target.closest(".dragging")
        ) {
          return;
        }

        // Only open modal if the card has a task ID (not create-only cards)
        if (card.dataset.taskId && card.dataset.taskId !== "undefined") {
          this.openTaskModal(card.dataset.taskId);
        }
      });

      // Setup reorder button listeners for this card
      const reorderButtons = card.querySelectorAll(".reorder-btn");
      reorderButtons.forEach((button) => {
        button.addEventListener("click", (e) => {
          console.log("🎯 Reorder button clicked!", e.target);
          e.preventDefault();
          e.stopPropagation(); // Prevent task modal from opening
          const taskId = button.dataset.taskId;
          const direction = button.dataset.direction;
          console.log("📝 Reorder data:", { taskId, direction });
          this.reorderTask(taskId, direction);
        });
      });
    });

    // Setup drop events for containers
    containers.forEach((container) => {
      container.addEventListener("dragover", (e) => {
        e.preventDefault();
        container.classList.add("drag-over");

        // Add visual feedback for in-column reordering
        this.updateDropIndicator(container, e);
      });

      container.addEventListener("dragleave", (e) => {
        if (!container.contains(e.relatedTarget)) {
          container.classList.remove("drag-over");
          // Clean up drop indicators
          container.querySelectorAll(".drop-indicator").forEach((indicator) => {
            indicator.remove();
          });
        }
      });

      container.addEventListener("drop", (e) => {
        e.preventDefault();
        container.classList.remove("drag-over");

        const taskId = e.dataTransfer.getData("text/plain");
        const newStatus = container.dataset.status;

        // Get the dragged task's current status
        const draggedCard = document.querySelector(
          `[data-task-id="${taskId}"]`
        );
        const currentContainer = draggedCard?.closest(".tasks-container");
        const currentStatus = currentContainer?.dataset.status;

        console.log(
          `🎯 Drop detected: Task ${taskId} from ${currentStatus} to ${newStatus}`
        );

        // Clean up drop indicators
        container.querySelectorAll(".drop-indicator").forEach((indicator) => {
          indicator.remove();
        });

        if (currentStatus === newStatus) {
          // Same column - handle reordering
          this.handleInColumnReorder(taskId, container, e);
        } else {
          // Different column - handle status change
          this.updateTaskStatus(taskId, newStatus);
        }
      });
    });
  }

  handleInColumnReorder(taskId, container, dropEvent) {
    console.log(`🔄 Handling in-column reorder for task ${taskId}`);

    // Get all task cards in this container (excluding create-only cards)
    const taskCards = Array.from(
      container.querySelectorAll(".task-card:not(.create-only-card)")
    );
    const draggedCard = document.querySelector(`[data-task-id="${taskId}"]`);

    if (!draggedCard) {
      console.error("❌ Dragged card not found");
      return;
    }

    // Find current position of dragged task
    const currentIndex = taskCards.indexOf(draggedCard);

    // Find drop position based on mouse position
    const dropY = dropEvent.clientY;
    let newIndex = taskCards.length - 1; // Default to end

    for (let i = 0; i < taskCards.length; i++) {
      const card = taskCards[i];
      if (card === draggedCard) continue;

      const rect = card.getBoundingClientRect();
      const cardMiddle = rect.top + rect.height / 2;

      if (dropY < cardMiddle) {
        newIndex = i;
        break;
      }
    }

    console.log(`📍 Reorder: ${currentIndex} → ${newIndex}`);

    if (currentIndex === newIndex) {
      console.log("📍 No position change needed");
      return;
    }

    // Use the more efficient position-based reordering
    this.reorderTaskToPosition(taskId, newIndex);
  }

  updateDropIndicator(container, dragEvent) {
    // Remove existing drop indicators
    container.querySelectorAll(".drop-indicator").forEach((indicator) => {
      indicator.remove();
    });

    const taskCards = Array.from(
      container.querySelectorAll(".task-card:not(.create-only-card)")
    );
    const draggedTaskId = dragEvent.dataTransfer?.getData?.("text/plain");
    const draggedCard = document.querySelector(
      `[data-task-id="${draggedTaskId}"]`
    );

    if (!draggedCard || !taskCards.includes(draggedCard)) return;

    const dropY = dragEvent.clientY;
    let insertBeforeCard = null;

    for (const card of taskCards) {
      if (card === draggedCard) continue;

      const rect = card.getBoundingClientRect();
      const cardMiddle = rect.top + rect.height / 2;

      if (dropY < cardMiddle) {
        insertBeforeCard = card;
        break;
      }
    }

    // Create drop indicator
    const indicator = document.createElement("div");
    indicator.className = "drop-indicator";
    indicator.style.cssText = `
      height: 2px;
      background: #0052cc;
      margin: 4px 0;
      border-radius: 1px;
      opacity: 0.8;
    `;

    if (insertBeforeCard) {
      insertBeforeCard.parentNode.insertBefore(indicator, insertBeforeCard);
    } else {
      // Insert at the end
      const lastCard = taskCards[taskCards.length - 1];
      if (lastCard && lastCard !== draggedCard) {
        lastCard.parentNode.insertBefore(indicator, lastCard.nextSibling);
      }
    }
  }

  async reorderTaskToPosition(taskId, targetPosition) {
    console.log(`🎯 Moving task ${taskId} to position ${targetPosition}`);

    try {
      const response = await fetch(
        `https://api-dojob.rubyshop.co.th/api/task/${taskId}/reorder-to-position`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({
            target_position: targetPosition,
            project_id: this.projectId,
          }),
        }
      );

      const result = await response.json();

      if (result.success) {
        console.log("✅ Task reordered to position successfully");
        this.showSuccess(`Task moved to position ${targetPosition + 1}`);
        // Reload board to show changes
        setTimeout(() => this.loadTasks(), 300);
      } else {
        console.error("❌ Position reorder failed:", result.error);
        this.showError(result.error || "Failed to reorder task");
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError("Connection error - make sure Node.js API is running");
    }
  }

  async reorderTaskMultipleSteps(taskId, direction, steps) {
    console.log(`🔄 Moving task ${taskId} ${direction} ${steps} steps`);

    for (let i = 0; i < steps; i++) {
      await this.reorderTask(taskId, direction);
      // Small delay to prevent race conditions
      await new Promise((resolve) => setTimeout(resolve, 100));
    }
  }

  async reorderTask(taskId, direction) {
    console.log(`🔄 Reordering Task ${taskId} ${direction}`);
    console.log(`📋 Project ID: ${this.projectId}`);

    try {
      const requestData = {
        direction: direction,
        project_id: this.projectId,
      };
      console.log(`📤 Request data:`, requestData);

      const response = await fetch(
        `https://api-dojob.rubyshop.co.th/api/task/${taskId}/reorder`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify(requestData),
        }
      );

      console.log(`📥 Response status: ${response.status}`);

      const result = await response.json();

      if (result.success) {
        console.log("✅ Task reordered successfully");
        this.showSuccess(`Task moved ${direction}`);
        // Reload board to show changes
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

  async updateTaskStatus(taskId, newStatus) {
    console.log(`🎯 Updating Task ${taskId} → ${newStatus} via Node.js API`);

    try {
      // Clean and validate task ID
      let cleanTaskId = parseInt(taskId, 10);

      if (!cleanTaskId || isNaN(cleanTaskId) || cleanTaskId < 1) {
        console.error("❌ Invalid task ID:", taskId);
        this.showError("Invalid task ID");
        return;
      }

      // Map status to database values
      const statusMap = {
        to_do: 1,
        in_progress: 2,
        done: 3,
      };

      const statusId = statusMap[newStatus];
      if (!statusId) {
        console.error("❌ Invalid status:", newStatus);
        this.showError("Invalid status");
        return;
      }

      console.log(
        `📝 Updating task ${cleanTaskId} to status ${statusId} via Node.js API`
      );

      // Use Node.js API
      const response = await fetch(
        `https://api-dojob.rubyshop.co.th/api/task/${cleanTaskId}/status`,
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
        console.log("✅ Status updated successfully via Node.js API");
        this.showSuccess(
          "Task moved to " + newStatus.replace("_", " ").toUpperCase()
        );
        // Reload board to show changes
        setTimeout(() => this.loadTasks(), 500);
      } else {
        console.error("❌ Update failed:", result.error);
        this.showError(result.error || "Failed to update status");
        this.loadTasks(); // Revert visual change
      }
    } catch (error) {
      console.error("❌ Network error:", error);
      this.showError(
        "Connection error - make sure Node.js API is running on port 3001"
      );
      this.loadTasks(); // Revert visual change
    }
  }

  openTaskModal(taskId) {
    // Use the new task modal
    if (window.taskModal) {
      window.taskModal.openTask(taskId);
    } else {
      console.error("Task modal not available - loading it now...");
      // Try to initialize the modal if it's not available
      if (typeof TaskModal !== "undefined") {
        window.taskModal = new TaskModal();
        window.taskModal.openTask(taskId);
      } else {
        // Fallback: show alert with task ID
        alert(`Task modal not loaded. Task ID: ${taskId}`);
      }
    }
  }

  setupEventListeners() {
    // Add task buttons (header)
    document.addEventListener("click", (e) => {
      if (e.target.closest(".add-task-btn")) {
        const status = e.target.closest(".add-task-btn").dataset.status;
        this.openCreateTaskModal(status);
      }
    });

    // Create task buttons (bottom of columns)
    document.addEventListener("click", (e) => {
      if (e.target.closest(".create-task-button")) {
        const button = e.target.closest(".create-task-button");
        const status = button.dataset.status;
        this.showInlineTaskForm(button, status);
      }
    });

    // Handle form submissions and cancellations
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("btn-create-task")) {
        e.preventDefault();
        this.handleCreateTask(e.target);
      } else if (e.target.classList.contains("btn-cancel-task")) {
        e.preventDefault();
        this.hideInlineTaskForm(e.target);
      }
    });

    // Handle Enter key in task input
    document.addEventListener("keydown", (e) => {
      if (
        e.target.classList.contains("task-title-input") &&
        e.key === "Enter" &&
        !e.shiftKey
      ) {
        e.preventDefault();
        const createBtn = e.target
          .closest(".inline-task-form")
          .querySelector(".btn-create-task");
        if (createBtn) createBtn.click();
      } else if (
        e.target.classList.contains("task-title-input") &&
        e.key === "Escape"
      ) {
        e.preventDefault();
        const cancelBtn = e.target
          .closest(".inline-task-form")
          .querySelector(".btn-cancel-task");
        if (cancelBtn) cancelBtn.click();
      }
    });

    // Refresh button
    const refreshBtn = document.getElementById("refresh-board-btn");
    if (refreshBtn) {
      refreshBtn.addEventListener("click", () => {
        this.loadTasks();
      });
    }
  }

  openCreateTaskModal(defaultStatus) {
    // Use existing task creation modal
    if (window.showCreateTaskModal) {
      window.showCreateTaskModal(this.projectId, defaultStatus);
    } else {
      console.log("Create task modal not available");
    }
  }

  showInlineTaskForm(button, status) {
    // Hide any existing forms
    this.hideAllInlineForms();

    // Create the inline form
    const formHtml = `
      <div class="inline-task-form" data-status="${status}">
        <textarea class="task-title-input" placeholder="What needs to be done?" rows="2" autofocus></textarea>
        <div class="form-actions">
          <button class="btn-create-task btn-primary">Create</button>
          <button class="btn-cancel-task btn-secondary">Cancel</button>
          <div class="form-controls">
            <button type="button" title="Add description">
              <i class="fas fa-align-left"></i>
            </button>
            <button type="button" title="Set due date">
              <i class="fas fa-calendar"></i>
            </button>
            <button type="button" title="Assign">
              <i class="fas fa-user"></i>
            </button>
            <button type="button" title="Add attachment">
              <i class="fas fa-paperclip"></i>
            </button>
          </div>
        </div>
      </div>
    `;

    // Keep the button visible and add form after it
    button.insertAdjacentHTML("afterend", formHtml);

    // Focus the textarea
    const textarea =
      button.nextElementSibling.querySelector(".task-title-input");
    if (textarea) {
      textarea.focus();
    }
  }

  hideInlineTaskForm(element) {
    const form = element.closest(".inline-task-form");
    if (form) {
      // Just remove the form (button stays visible)
      form.remove();
    }
  }

  hideAllInlineForms() {
    document.querySelectorAll(".inline-task-form").forEach((form) => {
      // Just remove the form (buttons stay visible)
      form.remove();
    });
  }

  async handleCreateTask(button) {
    const form = button.closest(".inline-task-form");
    const textarea = form.querySelector(".task-title-input");
    const title = textarea.value.trim();
    const status = form.dataset.status;

    if (!title) {
      textarea.focus();
      return;
    }

    // Disable button during creation
    button.disabled = true;
    button.textContent = "Creating...";

    try {
      // Map status to status_id
      const statusMap = {
        to_do: 1,
        in_progress: 2,
        done: 3,
      };

      const taskData = {
        title: title,
        description: "",
        project_id: this.projectId,
        status_id: statusMap[status] || 1,
        assigned_to: 0,
        priority_id: 1,
      };

      const response = await fetch("https://api-dojob.rubyshop.co.th/api/tasks", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(taskData),
      });

      const result = await response.json();

      if (result.success) {
        // Hide form and reload tasks
        this.hideInlineTaskForm(button);
        await this.loadTasks();
        this.showSuccess("Task created successfully");
      } else {
        throw new Error(result.error || "Failed to create task");
      }
    } catch (error) {
      console.error("Error creating task:", error);
      this.showError("Failed to create task: " + error.message);

      // Re-enable button
      button.disabled = false;
      button.textContent = "Create";
    }
  }

  // Utility methods
  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
    });
  }

  showSuccess(message) {
    // Use your existing notification system
    if (window.appAlert) {
      window.appAlert.success(message);
    } else {
      console.log("Success:", message);
    }
  }

  showError(message) {
    // Use your existing notification system
    if (window.appAlert) {
      window.appAlert.error(message);
    } else {
      console.error("Error:", message);
    }
  }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  // This will be called from the main task list page
  window.initKanbanBoard = function (projectId) {
    return new KanbanBoard(projectId);
  };
});
