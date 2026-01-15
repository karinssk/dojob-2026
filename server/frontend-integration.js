/**
 * Frontend Integration Helper for Task Board API
 * Replace your existing PHP AJAX calls with these functions
 */

class TaskBoardAPI {
  constructor(baseUrl = "https://api-dojob.rubyshop.co.th/api") {
    this.baseUrl = baseUrl;
  }

  // Helper method for API calls
  async apiCall(endpoint, options = {}) {
    try {
      const response = await fetch(`${this.baseUrl}${endpoint}`, {
        headers: {
          "Content-Type": "application/json",
          ...options.headers,
        },
        ...options,
      });

      const result = await response.json();

      if (!result.success) {
        throw new Error(result.error || "API call failed");
      }

      return result;
    } catch (error) {
      console.error("API Error:", error);
      throw error;
    }
  }

  // TASK OPERATIONS

  // Load kanban board for project
  async loadKanbanBoard(projectId) {
    return await this.apiCall(`/kanban/${projectId}`);
  }

  // Get all tasks for project
  async getTasks(projectId, status = null) {
    const endpoint = status
      ? `/tasks/${projectId}?status=${status}`
      : `/tasks/${projectId}`;
    return await this.apiCall(endpoint);
  }

  // Get single task
  async getTask(taskId) {
    return await this.apiCall(`/task/${taskId}`);
  }

  // Create new task
  async createTask(taskData) {
    return await this.apiCall("/tasks", {
      method: "POST",
      body: JSON.stringify(taskData),
    });
  }

  // Update task
  async updateTask(taskId, updates) {
    return await this.apiCall(`/task/${taskId}`, {
      method: "PUT",
      body: JSON.stringify(updates),
    });
  }

  // Update task status (for drag & drop)
  async updateTaskStatus(taskId, statusId, sort = null) {
    const data = { status_id: statusId };
    if (sort !== null) data.sort = sort;

    return await this.apiCall(`/task/${taskId}/status`, {
      method: "PUT",
      body: JSON.stringify(data),
    });
  }

  // Delete task
  async deleteTask(taskId) {
    return await this.apiCall(`/task/${taskId}`, {
      method: "DELETE",
    });
  }

  // Bulk update tasks
  async bulkUpdateTasks(taskIds, updates) {
    return await this.apiCall("/tasks/bulk", {
      method: "PUT",
      body: JSON.stringify({
        task_ids: taskIds,
        updates: updates,
      }),
    });
  }

  // REFERENCE DATA

  // Get task statuses
  async getTaskStatuses() {
    return await this.apiCall("/task-statuses");
  }

  // Get task priorities
  async getTaskPriorities() {
    return await this.apiCall("/task-priorities");
  }

  // Get users for assignment
  async getUsers() {
    return await this.apiCall("/users");
  }

  // Get labels
  async getLabels(context = null) {
    const endpoint = context ? `/labels?context=${context}` : "/labels";
    return await this.apiCall(endpoint);
  }

  // SEARCH AND FILTER

  // Search tasks
  async searchTasks(filters = {}) {
    const params = new URLSearchParams(filters);
    return await this.apiCall(`/search/tasks?${params}`);
  }
}

// Initialize API instance
const taskAPI = new TaskBoardAPI();

// INTEGRATION EXAMPLES FOR YOUR EXISTING CODE

// Example 1: Replace kanban board loading
function loadKanbanBoard(projectId) {
  // Old PHP way:
  // $.post("<?php echo get_uri('projects/load_kanban'); ?>", {project_id: projectId}, function(result) {

  // New Node.js way:
  taskAPI
    .loadKanbanBoard(projectId)
    .then((result) => {
      if (result.success) {
        renderKanbanBoard(result.data);
      }
    })
    .catch((error) => {
      console.error("Error loading kanban board:", error);
      showErrorMessage("Failed to load kanban board");
    });
}

// Example 2: Create new task
function createNewTask(formData) {
  const taskData = {
    title: formData.title,
    description: formData.description,
    project_id: formData.project_id,
    assigned_to: formData.assigned_to || 0,
    status_id: formData.status_id || 1,
    priority_id: formData.priority_id || 1,
    deadline: formData.deadline || null,
    labels: formData.labels || [],
    collaborators: formData.collaborators || [],
  };

  taskAPI
    .createTask(taskData)
    .then((result) => {
      if (result.success) {
        showSuccessMessage("Task created successfully");
        loadKanbanBoard(taskData.project_id); // Refresh board
        closeTaskModal();
      }
    })
    .catch((error) => {
      console.error("Error creating task:", error);
      showErrorMessage("Failed to create task");
    });
}

// Example 3: Handle drag & drop
function handleTaskDrop(taskId, newStatusId, newSort) {
  taskAPI
    .updateTaskStatus(taskId, newStatusId, newSort)
    .then((result) => {
      if (result.success) {
        console.log("Task moved successfully");
      }
    })
    .catch((error) => {
      console.error("Error moving task:", error);
      // Revert the UI change
      revertTaskPosition(taskId);
    });
}

// Example 4: Update task inline
function updateTaskField(taskId, field, value) {
  const updates = {};
  updates[field] = value;

  taskAPI
    .updateTask(taskId, updates)
    .then((result) => {
      if (result.success) {
        showSuccessMessage("Task updated");
      }
    })
    .catch((error) => {
      console.error("Error updating task:", error);
      showErrorMessage("Failed to update task");
    });
}

// Example 5: Load dropdown data
async function loadDropdownData() {
  try {
    const [statuses, priorities, users, labels] = await Promise.all([
      taskAPI.getTaskStatuses(),
      taskAPI.getTaskPriorities(),
      taskAPI.getUsers(),
      taskAPI.getLabels("task"),
    ]);

    populateStatusDropdown(statuses.data);
    populatePriorityDropdown(priorities.data);
    populateUserDropdown(users.data);
    populateLabelsDropdown(labels.data);
  } catch (error) {
    console.error("Error loading dropdown data:", error);
  }
}

// UTILITY FUNCTIONS

function showSuccessMessage(message) {
  // Your existing success message function
  console.log("Success:", message);
}

function showErrorMessage(message) {
  // Your existing error message function
  console.error("Error:", message);
}

function renderKanbanBoard(kanbanData) {
  // Your existing kanban board rendering logic
  console.log("Rendering kanban board:", kanbanData);
}

function closeTaskModal() {
  // Your existing modal close logic
  console.log("Closing task modal");
}

function revertTaskPosition(taskId) {
  // Your existing UI revert logic
  console.log("Reverting task position:", taskId);
}

// MIGRATION CHECKLIST
/*
1. Replace all PHP AJAX endpoints with taskAPI methods
2. Update your existing JavaScript functions to use the new API
3. Ensure error handling is in place
4. Test all CRUD operations
5. Update any hardcoded URLs to use the Node.js API
6. Make sure CORS is properly configured if frontend is on different port

COMMON REPLACEMENTS:
- load_kanban -> taskAPI.loadKanbanBoard()
- save_task -> taskAPI.createTask() or taskAPI.updateTask()
- delete_task -> taskAPI.deleteTask()
- update_task_status -> taskAPI.updateTaskStatus()
- get_users -> taskAPI.getUsers()
- get_labels -> taskAPI.getLabels()
*/
