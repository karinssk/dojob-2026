/**
 * Modal Scroll Manager
 * Manages page scrollbar visibility when modals are open
 * Prevents background scrolling while allowing modal content to scroll
 */

// Global variables for scroll management
let originalBodyOverflow = "";
let originalBodyPaddingRight = "";
let scrollbarWidth = 0;

// Calculate scrollbar width
function getScrollbarWidth() {
  if (scrollbarWidth !== 0) {
    return scrollbarWidth;
  }

  // Create temporary div to measure scrollbar width
  const outer = document.createElement("div");
  outer.style.visibility = "hidden";
  outer.style.overflow = "scroll";
  outer.style.msOverflowStyle = "scrollbar";
  document.body.appendChild(outer);

  const inner = document.createElement("div");
  outer.appendChild(inner);

  scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
  outer.parentNode.removeChild(outer);

  return scrollbarWidth;
}

// Disable page scrolling when modal opens
function disablePageScroll() {
  console.log("Disabling page scroll for modal");

  // Store original values
  originalBodyOverflow = document.body.style.overflow;
  originalBodyPaddingRight = document.body.style.paddingRight;

  // Calculate scrollbar width to prevent layout shift
  const scrollWidth = getScrollbarWidth();

  // Check if page has vertical scrollbar
  const hasVerticalScrollbar = document.body.scrollHeight > window.innerHeight;

  if (hasVerticalScrollbar) {
    // Add padding to compensate for hidden scrollbar
    document.body.style.paddingRight = `${scrollWidth}px`;
  }

  // Hide scrollbar
  document.body.style.overflow = "hidden";

  // Also apply to html element for better browser compatibility
  document.documentElement.style.overflow = "hidden";
}

// Enable page scrolling when modal closes
function enablePageScroll() {
  console.log("Enabling page scroll after modal close");

  // Restore original values
  document.body.style.overflow = originalBodyOverflow;
  document.body.style.paddingRight = originalBodyPaddingRight;
  document.documentElement.style.overflow = "";
}

// Initialize modal scroll management
function initializeModalScrollManager() {
  console.log("Initializing modal scroll manager...");

  // List of modal IDs that should disable page scrolling
  const managedModals = [
    "field-options-modal",
    "storyboard-modal",
    "scene-heading-modal",
    "edit-project-modal",
    "video-preview-modal",
    "emoji-picker-modal",
    "column-manager-modal",
  ];

  // Add event listeners for each modal
  managedModals.forEach((modalId) => {
    const modalElement = document.getElementById(modalId);
    if (modalElement) {
      console.log(`Setting up scroll management for modal: ${modalId}`);

      // Listen for modal show events
      modalElement.addEventListener("show.bs.modal", function (e) {
        console.log(`Modal ${modalId} is opening`);
        disablePageScroll();
      });

      // Listen for modal hide events
      modalElement.addEventListener("hide.bs.modal", function (e) {
        console.log(`Modal ${modalId} is closing`);
        enablePageScroll();
      });

      // Listen for modal hidden events (cleanup)
      modalElement.addEventListener("hidden.bs.modal", function (e) {
        console.log(`Modal ${modalId} is fully closed`);
        // Ensure scroll is enabled (fallback)
        enablePageScroll();
      });
    } else {
      console.warn(`Modal element not found: ${modalId}`);
    }
  });

  // Handle escape key and backdrop clicks
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      // Small delay to allow modal to close first
      setTimeout(() => {
        // Check if any modal is still open
        const openModals = document.querySelectorAll(".modal.show");
        if (openModals.length === 0) {
          enablePageScroll();
        }
      }, 100);
    }
  });

  console.log("Modal scroll manager initialized");
}

// Enhanced modal scroll management for specific modals
function enhanceModalScrolling() {
  // Special handling for field options modal (has internal scrolling areas)
  const fieldOptionsModal = document.getElementById("field-options-modal");
  if (fieldOptionsModal) {
    const modalBody = fieldOptionsModal.querySelector(".modal-body");
    if (modalBody) {
      // Ensure modal body can scroll
      modalBody.style.maxHeight = "70vh";
      modalBody.style.overflowY = "auto";

      // Smooth scrolling for better UX
      modalBody.style.scrollBehavior = "smooth";
    }

    // Handle options list scrolling
    const optionsList = fieldOptionsModal.querySelector(".options-list");
    if (optionsList) {
      optionsList.style.maxHeight = "50vh";
      optionsList.style.overflowY = "auto";
    }
  }

  // Special handling for video preview modal
  const videoModal = document.getElementById("video-preview-modal");
  if (videoModal) {
    const modalBody = videoModal.querySelector(".modal-body");
    if (modalBody) {
      modalBody.style.maxHeight = "80vh";
      modalBody.style.overflowY = "auto";
    }
  }

  // Add smooth scrolling to all modal bodies
  const allModalBodies = document.querySelectorAll(".modal-body");
  allModalBodies.forEach((modalBody) => {
    modalBody.style.scrollBehavior = "smooth";

    // Add custom scrollbar styling
    modalBody.classList.add("custom-scrollbar");
  });
}

// Utility function to check if any modal is currently open
function isAnyModalOpen() {
  const openModals = document.querySelectorAll(".modal.show");
  return openModals.length > 0;
}

// Force enable scroll (emergency function)
function forceEnableScroll() {
  console.log("Force enabling page scroll");
  document.body.style.overflow = "";
  document.body.style.paddingRight = "";
  document.documentElement.style.overflow = "";
}

// Test function for debugging
function testModalScrollManager() {
  console.log("=== Modal Scroll Manager Test ===");
  console.log("Scrollbar width:", getScrollbarWidth());
  console.log(
    "Page has vertical scrollbar:",
    document.body.scrollHeight > window.innerHeight
  );
  console.log("Current body overflow:", document.body.style.overflow);
  console.log("Current body padding-right:", document.body.style.paddingRight);
  console.log("Any modal open:", isAnyModalOpen());

  // Test disable/enable
  console.log("Testing disable scroll...");
  disablePageScroll();

  setTimeout(() => {
    console.log("Testing enable scroll...");
    enablePageScroll();
  }, 2000);
}

// Initialize when DOM is ready
$(document).ready(function () {
  console.log("Initializing modal scroll management system...");

  // Small delay to ensure Bootstrap modals are initialized
  setTimeout(() => {
    initializeModalScrollManager();
    enhanceModalScrolling();
  }, 300);
});

// Make functions available globally
window.initializeModalScrollManager = initializeModalScrollManager;
window.disablePageScroll = disablePageScroll;
window.enablePageScroll = enablePageScroll;
window.forceEnableScroll = forceEnableScroll;
window.testModalScrollManager = testModalScrollManager;
