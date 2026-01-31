/**
 * Task List Modals Module - Updated for Native Modal System
 * Now uses Rise CRM's native modal_anchor system instead of custom iframes
 */

console.log("🔧 Task modals module loaded - using native Rise CRM modal system");

// Note: Task modals are now handled by modal_anchor() calls in the PHP templates
// This provides better integration with the Rise CRM modal system and uses the new task_modal_v4 template

// The task key links and comment links are now generated using:
// modal_anchor(get_uri("tasks/view"), $task->id, array("data-post-id" => $task->id))

// This ensures:
// 1. Proper modal integration with Rise CRM
// 2. Uses the new Jira-style task_modal_v4 template
// 3. No iframe complications or cross-origin issues
// 4. Better performance and user experience

// Ensure TaskModal is initialized
function ensureTaskModal() {
  if (!window.taskModal) {
    console.log("🔄 Ensuring TaskModal is initialized...");
    try {
      if (window.getTaskModalInstance) {
        window.taskModal = window.getTaskModalInstance();
        console.log(" Got single TaskModal instance:", window.taskModal.instanceId);
      } else if (window.TaskModal) {
        window.taskModal = new window.TaskModal();
        console.log(" Created new TaskModal instance:", window.taskModal.instanceId);
      }
      console.log(" TaskModal.openTask method:", typeof window.taskModal.openTask);
    } catch (error) {
      console.error("❌ Error initializing TaskModal:", error);
      return false;
    }
  }
  return !!window.taskModal && typeof window.taskModal.openTask === 'function';
}

// Updated functions to use our new TaskModal system
function openTaskModal(taskId) {
  console.log("🔍 openTaskModal called with taskId:", taskId);
  
  if (!ensureTaskModal()) {
    console.log("❌ TaskModal not available");
    Swal.fire({
      icon: 'error',
      title: 'Modal Error',
      text: `TaskModal not available. Task ID: ${taskId}`,
      confirmButtonColor: '#0052CC'
    });
    return;
  }
  
  try {
    console.log(" Opening task with TaskModal");
    window.taskModal.openTask(taskId);
  } catch (error) {
    console.error("❌ Error opening task modal:", error);
    Swal.fire({
      icon: 'error',
      title: 'Modal Error',
      text: `Error opening task modal: ${error.message}`,
      confirmButtonColor: '#0052CC'
    });
  }
}

function showTaskModal(taskId) {
  console.log("🔍 showTaskModal called with taskId:", taskId);
  openTaskModal(taskId);
}

function loadTaskModal(taskId) {
  console.log("🔍 loadTaskModal called with taskId:", taskId);
  openTaskModal(taskId);
}

function initTaskModalTriggers() {
  console.log(" Task modal triggers initialized for TaskModal system");
}

// Make functions globally accessible
window.ensureTaskModal = ensureTaskModal;
window.openTaskModal = openTaskModal;
window.showTaskModal = showTaskModal;
window.loadTaskModal = loadTaskModal;
window.initTaskModalTriggers = initTaskModalTriggers;
