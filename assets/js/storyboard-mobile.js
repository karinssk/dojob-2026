/**
 * Storyboard Mobile Functionality
 * Handles mobile-specific features and reorder mode toggling
 */

// Global variables for mobile functionality
let isMobileDevice = false;
let isReorderActiveOnMobile = false;

// Device detection
function detectMobileDevice() {
  const userAgent = navigator.userAgent || navigator.vendor || window.opera;
  const screenWidth = window.innerWidth;

  // Check for mobile user agents
  const mobileRegex =
    /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i;
  const isMobileUserAgent = mobileRegex.test(userAgent.toLowerCase());

  // Check screen width (tablets and phones)
  const isMobileScreen = screenWidth <= 768;

  // Consider it mobile if either condition is true
  isMobileDevice = isMobileUserAgent || isMobileScreen;

  console.log("Device detection:", {
    userAgent: userAgent,
    screenWidth: screenWidth,
    isMobileUserAgent: isMobileUserAgent,
    isMobileScreen: isMobileScreen,
    isMobileDevice: isMobileDevice,
  });

  return isMobileDevice;
}

// Initialize mobile features
function initializeMobileFeatures() {
  console.log("Initializing mobile features...");

  // Detect device type
  detectMobileDevice();

  // Set initial reorder mode state based on device
  setInitialReorderState();

  // Add mobile-specific event listeners
  addMobileEventListeners();

  // Update UI based on device type
  updateMobileUI();

  console.log("Mobile features initialized");
}

// Set initial reorder state based on device type
function setInitialReorderState() {
  console.log("Setting initial reorder state...");

  if (isMobileDevice) {
    // Mobile: Reorder mode inactive by default
    isReorderActiveOnMobile = false;
    console.log("Mobile device detected: Reorder mode inactive by default");
  } else {
    // PC: Reorder mode active by default
    console.log("PC device detected: Reorder mode active by default");
    // Enable reorder mode automatically on PC
    setTimeout(() => {
      if (typeof enableReorderMode === "function") {
        enableReorderMode();
      }
    }, 500); // Small delay to ensure DOM is ready
  }
}

// Add mobile-specific event listeners
function addMobileEventListeners() {
  console.log("Adding mobile event listeners...");

  // Listen for window resize to re-detect device type
  window.addEventListener(
    "resize",
    debounce(function () {
      const wasMobile = isMobileDevice;
      detectMobileDevice();

      if (wasMobile !== isMobileDevice) {
        console.log("Device type changed, updating UI...");
        updateMobileUI();
        setInitialReorderState();
      }
    }, 250)
  );

  // Override the reorder button click for mobile
  const reorderBtn = document.getElementById("reorder-mode");
  if (reorderBtn) {
    // Remove existing click handlers and add our mobile-aware handler
    reorderBtn.removeEventListener("click", toggleReorderMode);
    reorderBtn.addEventListener("click", handleMobileReorderToggle);
    console.log("Mobile reorder toggle handler attached");
  }
}

// Handle reorder toggle with mobile awareness
function handleMobileReorderToggle(event) {
  event.preventDefault();
  console.log(
    "Mobile reorder toggle clicked, device is mobile:",
    isMobileDevice
  );

  if (isMobileDevice) {
    // Mobile: Toggle reorder mode on/off
    if (isReorderActiveOnMobile) {
      disableMobileReorderMode();
    } else {
      enableMobileReorderMode();
    }
  } else {
    // PC: Use standard toggle
    if (typeof toggleReorderMode === "function") {
      toggleReorderMode();
    }
  }
}

// Enable reorder mode on mobile
function enableMobileReorderMode() {
  console.log("Enabling mobile reorder mode...");

  isReorderActiveOnMobile = true;

  // Enable the actual reorder functionality
  if (typeof enableReorderMode === "function") {
    enableReorderMode();
  }

  // Update mobile UI
  updateMobileReorderButton(true);

  // Show mobile-specific instructions
  showMobileReorderInstructions();

  console.log("Mobile reorder mode enabled");
}

// Disable reorder mode on mobile
function disableMobileReorderMode() {
  console.log("Disabling mobile reorder mode...");

  isReorderActiveOnMobile = false;

  // Disable the actual reorder functionality
  if (typeof disableReorderMode === "function") {
    disableReorderMode();
  }

  // Update mobile UI
  updateMobileReorderButton(false);

  // Hide mobile instructions
  hideMobileReorderInstructions();

  console.log("Mobile reorder mode disabled");
}

// Update mobile UI based on device type and state
function updateMobileUI() {
  console.log("Updating mobile UI...");

  const body = document.body;
  const reorderBtn = document.getElementById("reorder-mode");

  // Add/remove mobile class
  if (isMobileDevice) {
    body.classList.add("mobile-device");
    body.classList.remove("desktop-device");
  } else {
    body.classList.add("desktop-device");
    body.classList.remove("mobile-device");
  }

  // Update reorder button appearance
  if (reorderBtn) {
    updateMobileReorderButton(isMobileDevice ? isReorderActiveOnMobile : true);
  }

  console.log("Mobile UI updated");
}

// Update reorder button appearance for mobile
function updateMobileReorderButton(isActive) {
  const reorderBtn = document.getElementById("reorder-mode");
  if (!reorderBtn) return;

  if (isMobileDevice) {
    // Mobile: Show toggle state
    if (isActive) {
      reorderBtn.classList.remove("btn-outline-secondary", "modern-tool-btn");
      reorderBtn.classList.add("btn-primary", "modern-tool-btn-active");
      reorderBtn.title = "Disable Reorder Mode";
      reorderBtn.innerHTML = '<i data-feather="move" class="icon-16"></i>';
    } else {
      reorderBtn.classList.remove("btn-primary", "modern-tool-btn-active");
      reorderBtn.classList.add("btn-outline-secondary", "modern-tool-btn");
      reorderBtn.title = "Enable Reorder Mode";
      reorderBtn.innerHTML = '<i data-feather="move" class="icon-16"></i>';
    }
  } else {
    // PC: Always show as active (default behavior)
    reorderBtn.classList.remove("btn-outline-secondary");
    reorderBtn.classList.add("btn-primary", "modern-tool-btn-active");
    reorderBtn.title = "Reorder Mode (Always Active on PC)";
    reorderBtn.innerHTML = '<i data-feather="move" class="icon-16"></i>';
  }

  // Re-render feather icons
  if (typeof feather !== "undefined") {
    feather.replace();
  }
}

// Show mobile-specific reorder instructions
function showMobileReorderInstructions() {
  // Remove existing instructions
  hideMobileReorderInstructions();

  const tableBodies = document.querySelectorAll('.storyboard-table-body');
  const tableCount = tableBodies.length;

  const instructions = document.createElement("div");
  instructions.id = "mobile-reorder-instructions";
  instructions.className =
    "alert alert-info alert-dismissible fade show mobile-instructions";
  instructions.innerHTML = `
        <div class="d-flex align-items-center">
            <i data-feather="smartphone" class="icon-16 me-2"></i>
            <div>
                <strong>Mobile Reorder Mode:</strong> 
                Touch and drag rows to reorder scenes within each heading (${tableCount} table${tableCount !== 1 ? 's' : ''} active). Tap the reorder button again to disable.
            </div>
            <button type="button" class="btn-close" onclick="hideMobileReorderInstructions()"></button>
        </div>
    `;

  // Insert before the first scene container
  const sceneContainer = document.querySelector(".modern-scene-container");
  if (sceneContainer) {
    sceneContainer.parentNode.insertBefore(instructions, sceneContainer);

    // Re-render feather icons
    if (typeof feather !== "undefined") {
      feather.replace();
    }
  }
}

// Hide mobile reorder instructions
function hideMobileReorderInstructions() {
  const instructions = document.getElementById("mobile-reorder-instructions");
  if (instructions) {
    instructions.remove();
  }
}

// Debounce function for resize events
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Check if device is mobile (utility function)
function isMobile() {
  return isMobileDevice;
}

// Check if reorder is active on mobile
function isMobileReorderActive() {
  return isMobileDevice && isReorderActiveOnMobile;
}

// Force enable reorder mode (for testing)
function forceEnableReorder() {
  if (isMobileDevice) {
    enableMobileReorderMode();
  } else {
    if (typeof enableReorderMode === "function") {
      enableReorderMode();
    }
  }
}

// Force disable reorder mode (for testing)
function forceDisableReorder() {
  if (isMobileDevice) {
    disableMobileReorderMode();
  } else {
    if (typeof disableReorderMode === "function") {
      disableReorderMode();
    }
  }
}

// Test mobile functionality
function testMobileFunctionality() {
  console.log("=== Mobile Functionality Test ===");
  console.log("User Agent:", navigator.userAgent);
  console.log("Screen Width:", window.innerWidth);
  console.log("Is Mobile Device:", isMobileDevice);
  console.log("Is Mobile Reorder Active:", isReorderActiveOnMobile);
  console.log("Body Classes:", document.body.className);

  const reorderBtn = document.getElementById("reorder-mode");
  if (reorderBtn) {
    console.log("Reorder Button Classes:", reorderBtn.className);
    console.log("Reorder Button Title:", reorderBtn.title);
  }

  console.log("Available Functions:", {
    enableReorderMode: typeof enableReorderMode,
    disableReorderMode: typeof disableReorderMode,
    toggleReorderMode: typeof toggleReorderMode,
  });
}

// Initialize when DOM is ready
$(document).ready(function () {
  console.log("Initializing mobile functionality...");

  // Small delay to ensure other scripts are loaded
  setTimeout(() => {
    initializeMobileFeatures();
  }, 100);

  // Make test function available globally
  window.testMobileFunctionality = testMobileFunctionality;
});

// Make functions available globally
window.isMobile = isMobile;
window.isMobileReorderActive = isMobileReorderActive;
window.forceEnableReorder = forceEnableReorder;
window.forceDisableReorder = forceDisableReorder;
window.enableMobileReorderMode = enableMobileReorderMode;
window.disableMobileReorderMode = disableMobileReorderMode;
