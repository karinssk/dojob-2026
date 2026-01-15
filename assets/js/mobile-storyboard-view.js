/**
 * Mobile-Optimized Storyboard View
 * Provides a card-based mobile interface for better usability
 */

// Global variables for mobile view
let isMobileViewActive = false;
let mobileViewData = [];
let currentMobileFilter = "all";

// Initialize mobile storyboard view
function initializeMobileStoryboardView() {
  console.log("Initializing mobile storyboard view...");

  // Detect if we should use mobile view
  if (shouldUseMobileView()) {
    activateMobileView();
  }

  // Listen for window resize to switch views
  window.addEventListener("resize", debounce(handleViewportChange, 250));

  console.log("Mobile storyboard view initialized");
}

// Check if mobile view should be used
function shouldUseMobileView() {
  const screenWidth = window.innerWidth;
  const userAgent = navigator.userAgent.toLowerCase();

  // Use mobile view for screens smaller than 768px or mobile devices
  return (
    screenWidth <= 768 || /mobile|android|iphone|ipad|ipod/.test(userAgent)
  );
}

// Handle viewport changes
function handleViewportChange() {
  const shouldBeMobile = shouldUseMobileView();

  if (shouldBeMobile && !isMobileViewActive) {
    activateMobileView();
  } else if (!shouldBeMobile && isMobileViewActive) {
    deactivateMobileView();
  }
}

// Activate mobile view
function activateMobileView() {
  console.log("Activating mobile view...");

  isMobileViewActive = true;
  document.body.classList.add("mobile-storyboard-view");

  // Hide desktop table and show mobile cards
  hideDesktopTable();
  
  // Small delay to ensure DOM is ready
  setTimeout(() => {
    createMobileInterface();
    loadMobileStoryboardData();
    
    // Force refresh if no cards are shown
    setTimeout(() => {
      const cards = document.querySelectorAll('.mobile-storyboard-card');
      if (cards.length === 0) {
        console.log('No cards found after activation, forcing refresh...');
        forceRefreshMobileView();
      }
    }, 500);
  }, 100);

  console.log("Mobile view activated");
}

// Force refresh mobile view
function forceRefreshMobileView() {
  console.log('Force refreshing mobile view...');
  
  // Clear existing mobile containers
  const existingContainers = document.querySelectorAll('.mobile-scene-container');
  existingContainers.forEach(container => container.remove());
  
  // Clear data and reload
  mobileViewData = [];
  
  // Try multiple extraction methods
  loadMobileStoryboardData();
  
  // If still no data, create sample data for testing
  if (mobileViewData.length === 0) {
    console.log('Creating sample data for testing...');
    createSampleMobileData();
  }
}

// Deactivate mobile view
function deactivateMobileView() {
  console.log("Deactivating mobile view...");

  isMobileViewActive = false;
  document.body.classList.remove("mobile-storyboard-view");

  // Show desktop table and hide mobile cards
  showDesktopTable();
  removeMobileInterface();

  console.log("Mobile view deactivated");
}

// Hide desktop table
function hideDesktopTable() {
  const tables = document.querySelectorAll(
    ".table-responsive, .storyboard-table"
  );
  tables.forEach((table) => {
    table.style.display = "none";
  });
}

// Show desktop table
function showDesktopTable() {
  const tables = document.querySelectorAll(
    ".table-responsive, .storyboard-table"
  );
  tables.forEach((table) => {
    table.style.display = "";
  });
}

// Create mobile interface
function createMobileInterface() {
  // Find scene containers
  const sceneContainers = document.querySelectorAll(
    ".scene-heading-section, .modern-scene-container"
  );

  sceneContainers.forEach((container) => {
    const mobileContainer = createMobileSceneContainer(container);
    container.parentNode.insertBefore(mobileContainer, container.nextSibling);
  });
}

// Remove mobile interface
function removeMobileInterface() {
  const mobileContainers = document.querySelectorAll(".mobile-scene-container");
  mobileContainers.forEach((container) => {
    container.remove();
  });
}

// Create mobile scene container
function createMobileSceneContainer(originalContainer) {
  const mobileContainer = document.createElement("div");
  mobileContainer.className = "mobile-scene-container";

  // Extract scene heading info
  const headingElement = originalContainer.querySelector(
    "h6, .scene-heading-info h6"
  );
  const headingText = headingElement
    ? headingElement.textContent.trim()
    : "Scene";

  mobileContainer.innerHTML = `
        <div class="mobile-scene-header">
            <div class="mobile-scene-title">
                <i data-feather="bookmark" class="icon-16 me-2"></i>
                <span>${headingText}</span>
            </div>
            <div class="mobile-scene-actions">
                <button type="button" class="btn btn-sm btn-primary mobile-add-scene-btn">
                    <i data-feather="plus" class="icon-14"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary mobile-scene-menu-btn">
                    <i data-feather="more-vertical" class="icon-14"></i>
                </button>
            </div>
        </div>
        <div class="mobile-scene-content">
            <div class="mobile-storyboard-cards" id="mobile-cards-${Date.now()}">
                <!-- Cards will be loaded here -->
            </div>
        </div>
    `;

  return mobileContainer;
}

// Load mobile storyboard data
function loadMobileStoryboardData() {
  console.log('Loading mobile storyboard data...');
  
  // Extract data from existing tables with multiple selectors
  const tables = document.querySelectorAll(".storyboard-table, table.table, .table-responsive table");
  mobileViewData = [];

  console.log('Found tables:', tables.length);

  tables.forEach((table, tableIndex) => {
    const rows = table.querySelectorAll("tbody tr[data-id], tbody .storyboard-row, tbody tr");
    console.log(`Table ${tableIndex}: Found ${rows.length} rows`);
    
    // Try to find heading ID from various sources
    let headingId = `table-${tableIndex}`;
    const sceneSection = table.closest(".scene-heading-section");
    const cardSection = table.closest(".card");
    
    if (sceneSection) {
      headingId = sceneSection.getAttribute("data-heading-id") || headingId;
    }

    rows.forEach((row, rowIndex) => {
      // Only process rows that have data-id or are storyboard rows
      const dataId = row.getAttribute("data-id");
      if (!dataId && !row.classList.contains('storyboard-row')) {
        return; // Skip header rows and empty rows
      }
      
      console.log(`Processing row ${rowIndex} in table ${tableIndex}, data-id: ${dataId}`);
      const storyboardData = extractStoryboardDataFromRow(row, headingId, tableIndex);
      if (storyboardData) {
        mobileViewData.push(storyboardData);
        console.log('Added storyboard data:', storyboardData);
      }
    });
  });

  console.log('Total mobile view data:', mobileViewData.length);
  
  // If no data found, try alternative extraction method
  if (mobileViewData.length === 0) {
    console.log('No data found with standard method, trying alternative extraction...');
    extractDataFromAlternativeSources();
  }
  
  renderMobileCards();
}

// Extract storyboard data from table row
function extractStoryboardDataFromRow(row, headingId, tableIndex) {
  let id = row.getAttribute("data-id");
  
  // If no data-id, try to generate one or skip
  if (!id) {
    // Try to find ID from other attributes or generate one
    id = row.id || `generated-${tableIndex}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    console.log('Generated ID for row:', id);
  }

  const cells = row.querySelectorAll("td");
  console.log(`Row ${id}: Found ${cells.length} cells`);
  
  if (cells.length === 0) {
    console.log('No cells found in row, skipping');
    return null;
  }

  // Create data object with safe extraction
  const data = {
    id: id,
    headingId: headingId,
    shot: safeCellExtract(cells[0]) || "1",
    frame: extractFrameData(cells[1]),
    shotSize: safeCellExtract(cells[2]) || "",
    shotType: safeCellExtract(cells[3]) || "",
    movement: safeCellExtract(cells[4]) || "",
    duration: safeCellExtract(cells[5]) || "",
    content: extractContentData(cells[6]) || "",
    dialogues: extractContentData(cells[7]) || "",
    sound: safeCellExtract(cells[8]) || "",
    equipment: safeCellExtract(cells[9]) || "",
    framerate: safeCellExtract(cells[10]) || "",
    lighting: safeCellExtract(cells[11]) || "",
    note: safeCellExtract(cells[12]) || "",
    rawFootage: extractRawFootageData(cells[13]) || [],
    status: extractStatusData(cells[14]) || "",
    actions: cells[15]?.innerHTML || "",
  };
  
  console.log('Extracted data:', data);
  return data;
}

// Safe cell text extraction
function safeCellExtract(cell) {
  if (!cell) return "";
  return cell.textContent?.trim() || "";
}

// Extract frame data
function extractFrameData(cell) {
  if (!cell) return null;

  const img = cell.querySelector("img");
  if (img) {
    return {
      src: img.src,
      alt: img.alt || "Frame",
    };
  }

  return null;
}

// Extract content data (handles full-value attribute)
function extractContentData(cell) {
  if (!cell) return "";

  const editableContent = cell.querySelector(".editable-content");
  if (editableContent) {
    return (
      editableContent.getAttribute("data-full-value") ||
      editableContent.textContent.trim()
    );
  }

  return cell.textContent.trim();
}

// Extract raw footage data
function extractRawFootageData(cell) {
  if (!cell) return [];

  const footageItems = cell.querySelectorAll(".footage-file-item");
  const footage = [];

  footageItems.forEach((item) => {
    const button = item.querySelector("button");
    if (button) {
      footage.push({
        name: button.textContent.trim(),
        url: button.getAttribute("data-video-url") || "#",
      });
    }
  });

  return footage;
}

// Extract status data
function extractStatusData(cell) {
  if (!cell) return "";

  const badge = cell.querySelector(".badge");
  if (badge) {
    return {
      text: badge.textContent.trim(),
      class: badge.className,
    };
  }

  return cell.textContent.trim();
}

// Render mobile cards
function renderMobileCards() {
  console.log('Rendering mobile cards...');
  const mobileContainers = document.querySelectorAll(".mobile-scene-container");
  console.log('Found mobile containers:', mobileContainers.length);

  if (mobileContainers.length === 0) {
    console.log('No mobile containers found, creating them...');
    createMobileInterface();
    return;
  }

  mobileContainers.forEach((container, index) => {
    const cardsContainer = container.querySelector(".mobile-storyboard-cards");
    if (!cardsContainer) {
      console.log(`No cards container found in mobile container ${index}`);
      return;
    }

    // Filter data for this container - be more flexible with matching
    let containerData = mobileViewData.filter(
      (item) =>
        item.headingId === `table-${index}` ||
        item.headingId === container.getAttribute("data-heading-id") ||
        item.headingId === 'alternative'
    );
    
    // If no specific data found, show all data in first container
    if (containerData.length === 0 && index === 0 && mobileViewData.length > 0) {
      console.log('No specific data found, showing all data in first container');
      containerData = mobileViewData;
    }

    console.log(`Container ${index}: Rendering ${containerData.length} items`);
    renderCardsInContainer(cardsContainer, containerData);
  });
  
  // If we have data but no containers rendered it, create a fallback container
  if (mobileViewData.length > 0) {
    const renderedCards = document.querySelectorAll('.mobile-storyboard-card');
    if (renderedCards.length === 0) {
      console.log('Data exists but no cards rendered, creating fallback container');
      createFallbackMobileContainer();
    }
  }
}

// Alternative data extraction method
function extractDataFromAlternativeSources() {
  console.log('Trying alternative data extraction...');
  
  // Try to find any table rows with content
  const allRows = document.querySelectorAll('table tr');
  console.log('Found total rows:', allRows.length);
  
  allRows.forEach((row, index) => {
    const cells = row.querySelectorAll('td');
    if (cells.length > 5) { // Likely a data row
      console.log(`Alternative extraction - Row ${index}: ${cells.length} cells`);
      
      // Create basic data structure
      const data = {
        id: `alt-${index}`,
        headingId: 'alternative',
        shot: cells[0]?.textContent?.trim() || (index + 1).toString(),
        frame: extractFrameData(cells[1]),
        shotSize: cells[2]?.textContent?.trim() || 'Medium Shot',
        shotType: cells[3]?.textContent?.trim() || 'Eye Level',
        movement: cells[4]?.textContent?.trim() || 'Static',
        duration: cells[5]?.textContent?.trim() || '3s',
        content: extractContentData(cells[6]) || 'Scene content',
        dialogues: extractContentData(cells[7]) || '',
        sound: cells[8]?.textContent?.trim() || '',
        equipment: cells[9]?.textContent?.trim() || '',
        framerate: cells[10]?.textContent?.trim() || '24fps',
        lighting: cells[11]?.textContent?.trim() || '',
        note: cells[12]?.textContent?.trim() || '',
        rawFootage: extractRawFootageData(cells[13]) || [],
        status: extractStatusData(cells[14]) || 'Draft',
        actions: cells[15]?.innerHTML || ''
      };
      
      mobileViewData.push(data);
      console.log('Added alternative data:', data);
    }
  });
}

// Render cards in container
function renderCardsInContainer(container, data) {
  if (!container) {
    console.log('No container provided for rendering');
    return;
  }
  
  console.log('Rendering cards in container, data length:', data ? data.length : 0);

  let html = "";

  if (!data || data.length === 0) {
    html = `
            <div class="mobile-empty-state">
                <i data-feather="film" class="icon-48 text-muted mb-3"></i>
                <p class="text-muted">No scenes found in this heading</p>
                <button type="button" class="btn btn-primary btn-sm" onclick="addMobileScene()">
                    <i data-feather="plus" class="icon-14 me-1"></i>Add First Scene
                </button>
                <div class="mt-3">
                    <small class="text-muted">Debug: Container ID: ${container.id || 'no-id'}</small>
                </div>
            </div>
        `;
  } else {
    console.log('Creating cards for', data.length, 'items');
    data.forEach((item, index) => {
      console.log(`Creating card ${index} for item:`, item);
      html += createMobileStoryboardCard(item);
    });
  }

  container.innerHTML = html;
  console.log('Cards rendered, HTML length:', html.length);

  // Re-render feather icons
  if (typeof feather !== "undefined") {
    feather.replace();
  }
}

// Create mobile storyboard card
function createMobileStoryboardCard(data) {
  const hasFrame = data.frame && data.frame.src;
  const hasContent = data.content && data.content !== "-";
  const hasDialogues = data.dialogues && data.dialogues !== "-";
  const hasFootage = data.rawFootage && data.rawFootage.length > 0;

  return `
        <div class="mobile-storyboard-card" data-id="${data.id}">
            <div class="mobile-card-header">
                <div class="mobile-card-shot">
                    <span class="mobile-shot-number">${data.shot}</span>
                    <div class="mobile-shot-meta">
                        ${
                          data.shotSize
                            ? `<span class="mobile-meta-badge">${data.shotSize}</span>`
                            : ""
                        }
                        ${
                          data.movement
                            ? `<span class="mobile-meta-badge">${data.movement}</span>`
                            : ""
                        }
                        ${
                          data.duration
                            ? `<span class="mobile-meta-badge">${data.duration}</span>`
                            : ""
                        }
                    </div>
                </div>
                <div class="mobile-card-actions">
                    ${
                      data.status && typeof data.status === "object"
                        ? `<span class="badge ${data.status.class}">${data.status.text}</span>`
                        : data.status
                        ? `<span class="badge bg-secondary">${data.status}</span>`
                        : ""
                    }
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i data-feather="more-horizontal" class="icon-14"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="editMobileStoryboard('${
                              data.id
                            }')">
                                <i data-feather="edit" class="icon-14 me-2"></i>Edit
                            </a></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteMobileStoryboard('${
                              data.id
                            }')">
                                <i data-feather="trash-2" class="icon-14 me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            ${
              hasFrame
                ? `
                <div class="mobile-card-frame">
                    <img src="${data.frame.src}" alt="${data.frame.alt}" class="mobile-frame-image" 
                         onclick="showMobileImageModal('${data.frame.src}')">
                </div>
            `
                : ""
            }
            
            <div class="mobile-card-content">
                ${
                  hasContent
                    ? `
                    <div class="mobile-content-section">
                        <div class="mobile-content-label">Content</div>
                        <div class="mobile-content-text">${data.content}</div>
                    </div>
                `
                    : ""
                }
                
                ${
                  hasDialogues
                    ? `
                    <div class="mobile-content-section">
                        <div class="mobile-content-label">Dialogues</div>
                        <div class="mobile-content-text mobile-dialogues">${data.dialogues}</div>
                    </div>
                `
                    : ""
                }
                
                <div class="mobile-technical-info">
                    ${
                      data.shotType
                        ? `<div class="mobile-tech-item"><span class="mobile-tech-label">Type:</span> ${data.shotType}</div>`
                        : ""
                    }
                    ${
                      data.sound && data.sound !== "-"
                        ? `<div class="mobile-tech-item"><span class="mobile-tech-label">Sound:</span> ${data.sound}</div>`
                        : ""
                    }
                    ${
                      data.equipment && data.equipment !== "-"
                        ? `<div class="mobile-tech-item"><span class="mobile-tech-label">Equipment:</span> ${data.equipment}</div>`
                        : ""
                    }
                    ${
                      data.framerate && data.framerate !== "-"
                        ? `<div class="mobile-tech-item"><span class="mobile-tech-label">Frame Rate:</span> ${data.framerate}</div>`
                        : ""
                    }
                    ${
                      data.lighting && data.lighting !== "-"
                        ? `<div class="mobile-tech-item"><span class="mobile-tech-label">Lighting:</span> ${data.lighting}</div>`
                        : ""
                    }
                    ${
                      data.note && data.note !== "-"
                        ? `<div class="mobile-tech-item"><span class="mobile-tech-label">Note:</span> ${data.note}</div>`
                        : ""
                    }
                </div>
                
                ${
                  hasFootage
                    ? `
                    <div class="mobile-footage-section">
                        <div class="mobile-content-label">Raw Footage</div>
                        <div class="mobile-footage-list">
                            ${data.rawFootage
                              .map(
                                (footage) => `
                                <button type="button" class="btn btn-sm btn-outline-primary mobile-footage-btn" 
                                        onclick="playMobileFootage('${footage.url}')">
                                    <i data-feather="play" class="icon-12 me-1"></i>${footage.name}
                                </button>
                            `
                              )
                              .join("")}
                        </div>
                    </div>
                `
                    : ""
                }
            </div>
        </div>
    `;
}

// Mobile action handlers
function editMobileStoryboard(id) {
  console.log("Edit mobile storyboard:", id);
  // Trigger the existing edit modal
  const editBtn = document.querySelector(
    `tr[data-id="${id}"] .btn-outline-primary`
  );
  if (editBtn) {
    editBtn.click();
  }
}

function deleteMobileStoryboard(id) {
  console.log("Delete mobile storyboard:", id);
  // Trigger the existing delete action
  const deleteBtn = document.querySelector(
    `tr[data-id="${id}"] .btn-outline-danger`
  );
  if (deleteBtn) {
    deleteBtn.click();
  }
}

function showMobileImageModal(src) {
  console.log("Show mobile image modal:", src);
  // Use existing image modal if available
  if (typeof showImageModal === "function") {
    showImageModal(src);
  }
}

function playMobileFootage(url) {
  console.log("Play mobile footage:", url);
  // Use existing video modal if available
  const videoBtn = document.querySelector(`[data-video-url="${url}"]`);
  if (videoBtn) {
    videoBtn.click();
  }
}

// Mobile toolbar
function createMobileToolbar() {
  const toolbar = document.createElement("div");
  toolbar.className = "mobile-storyboard-toolbar";
  toolbar.innerHTML = `
        <div class="mobile-toolbar-content">
            <div class="mobile-toolbar-left">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleMobileView()">
                    <i data-feather="monitor" class="icon-14 me-1"></i>Desktop View
                </button>
            </div>
            <div class="mobile-toolbar-center">
                <div class="mobile-filter-tabs">
                    <button type="button" class="mobile-filter-tab active" data-filter="all">All</button>
                    <button type="button" class="mobile-filter-tab" data-filter="draft">Draft</button>
                    <button type="button" class="mobile-filter-tab" data-filter="review">Review</button>
                    <button type="button" class="mobile-filter-tab" data-filter="approved">Approved</button>
                </div>
            </div>
            <div class="mobile-toolbar-right">
                <button type="button" class="btn btn-sm btn-primary" onclick="openMobileColumnManager()">
                    <i data-feather="columns" class="icon-14"></i>
                </button>
                <button type="button" class="btn btn-sm btn-success" onclick="addMobileScene()">
                    <i data-feather="plus" class="icon-14"></i>
                </button>
            </div>
        </div>
    `;

  // Insert toolbar at the top of the scene container
  const sceneContainer = document.querySelector(".modern-scene-container");
  if (sceneContainer) {
    sceneContainer.parentNode.insertBefore(toolbar, sceneContainer);
  }

  // Add filter event listeners
  toolbar.querySelectorAll(".mobile-filter-tab").forEach((tab) => {
    tab.addEventListener("click", function () {
      // Update active state
      toolbar
        .querySelectorAll(".mobile-filter-tab")
        .forEach((t) => t.classList.remove("active"));
      this.classList.add("active");

      // Apply filter
      currentMobileFilter = this.getAttribute("data-filter");
      applyMobileFilter();
    });
  });
}

// Apply mobile filter
function applyMobileFilter() {
  const cards = document.querySelectorAll(".mobile-storyboard-card");

  cards.forEach((card) => {
    const shouldShow =
      currentMobileFilter === "all" ||
      card.querySelector(`.badge:contains("${currentMobileFilter}")`);

    card.style.display = shouldShow ? "block" : "none";
  });
}

// Toggle mobile view
function toggleMobileView() {
  if (isMobileViewActive) {
    deactivateMobileView();
  } else {
    activateMobileView();
  }
}

// Mobile column manager
function openMobileColumnManager() {
  // Use existing column manager but with mobile-optimized modal
  if (typeof openEnhancedColumnManager === "function") {
    openEnhancedColumnManager();
  } else if (typeof openColumnManager === "function") {
    openColumnManager();
  }
}

// Add mobile scene
function addMobileScene() {
  // Use existing add scene functionality
  const addBtn = document.querySelector('[data-bs-target="#storyboard-modal"]');
  if (addBtn) {
    addBtn.click();
  }
}

// Utility function for debouncing
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

// Initialize when document is ready
$(document).ready(function () {
  console.log("Initializing mobile storyboard view system...");

  // Small delay to ensure other scripts are loaded
  setTimeout(() => {
    initializeMobileStoryboardView();
  }, 500);
});

// Create fallback mobile container when data exists but no containers
function createFallbackMobileContainer() {
  console.log('Creating fallback mobile container...');
  
  const sceneContainer = document.querySelector('.modern-scene-container, .scene-heading-section');
  if (!sceneContainer) {
    console.log('No scene container found for fallback');
    return;
  }
  
  const fallbackContainer = document.createElement('div');
  fallbackContainer.className = 'mobile-scene-container fallback-container';
  fallbackContainer.innerHTML = `
    <div class="mobile-scene-header">
      <div class="mobile-scene-title">
        <i data-feather="bookmark" class="icon-16 me-2"></i>
        <span>All Scenes</span>
      </div>
      <div class="mobile-scene-actions">
        <button type="button" class="btn btn-sm btn-primary mobile-add-scene-btn" onclick="addMobileScene()">
          <i data-feather="plus" class="icon-14"></i>
        </button>
      </div>
    </div>
    <div class="mobile-scene-content">
      <div class="mobile-storyboard-cards" id="fallback-mobile-cards">
        <!-- Cards will be loaded here -->
      </div>
    </div>
  `;
  
  sceneContainer.parentNode.insertBefore(fallbackContainer, sceneContainer.nextSibling);
  
  // Render all data in fallback container
  const cardsContainer = fallbackContainer.querySelector('.mobile-storyboard-cards');
  renderCardsInContainer(cardsContainer, mobileViewData);
  
  // Re-render feather icons
  if (typeof feather !== 'undefined') {
    feather.replace();
  }
}

// Debug function to check mobile view state
function debugMobileView() {
  console.log('=== Mobile View Debug ===');
  console.log('Is mobile view active:', isMobileViewActive);
  console.log('Mobile view data:', mobileViewData);
  console.log('Mobile containers:', document.querySelectorAll('.mobile-scene-container').length);
  console.log('Mobile cards:', document.querySelectorAll('.mobile-storyboard-card').length);
  console.log('Tables found:', document.querySelectorAll('table').length);
  console.log('Table rows found:', document.querySelectorAll('table tr').length);
  console.log('Storyboard rows found:', document.querySelectorAll('tr[data-id]').length);
}

// Create sample data for testing when no real data is found
function createSampleMobileData() {
  console.log('Creating sample mobile data...');
  
  const sampleData = [
    {
      id: 'sample-1',
      headingId: 'sample',
      shot: '1',
      frame: { src: 'https://picsum.photos/300/200?random=1', alt: 'Sample Frame 1' },
      shotSize: 'Full Shot',
      shotType: 'Eye Level',
      movement: 'Static',
      duration: '5s',
      content: 'Character enters the room and looks around nervously.',
      dialogues: '"Hello? Is anyone there?"',
      sound: 'Ambient room tone',
      equipment: 'Canon EOS R5',
      framerate: '24fps',
      lighting: 'Natural light',
      note: 'First take',
      rawFootage: [{ name: 'scene1.mp4', url: '#' }],
      status: { text: 'Approved', class: 'badge bg-success' }
    },
    {
      id: 'sample-2',
      headingId: 'sample',
      shot: '2',
      frame: { src: 'https://picsum.photos/300/200?random=2', alt: 'Sample Frame 2' },
      shotSize: 'Medium Shot',
      shotType: 'High Angle',
      movement: 'Pan',
      duration: '3s',
      content: 'Close-up of character\'s concerned expression.',
      dialogues: '"I think I hear something upstairs."',
      sound: 'Footsteps (off-screen)',
      equipment: 'Sony A7S III',
      framerate: '30fps',
      lighting: 'LED panel',
      note: 'Take 3 is best',
      rawFootage: [{ name: 'scene2.mp4', url: '#' }],
      status: { text: 'Review', class: 'badge bg-warning' }
    },
    {
      id: 'sample-3',
      headingId: 'sample',
      shot: '3',
      frame: { src: 'https://picsum.photos/300/200?random=3', alt: 'Sample Frame 3' },
      shotSize: 'Close-up',
      shotType: 'Low Angle',
      movement: 'Tracking',
      duration: '7s',
      content: 'Character slowly walks up the stairs, hand on the railing.',
      dialogues: '',
      sound: 'Creaking stairs',
      equipment: 'RED Komodo',
      framerate: '60fps',
      lighting: 'Practical lights',
      note: 'Multiple takes needed',
      rawFootage: [{ name: 'scene3a.mp4', url: '#' }, { name: 'scene3b.mp4', url: '#' }],
      status: { text: 'Draft', class: 'badge bg-secondary' }
    }
  ];
  
  mobileViewData = sampleData;
  console.log('Sample data created:', mobileViewData);
  
  // Create container and render
  createFallbackMobileContainer();
}

// Make functions available globally
window.toggleMobileView = toggleMobileView;
window.editMobileStoryboard = editMobileStoryboard;
window.deleteMobileStoryboard = deleteMobileStoryboard;
window.showMobileImageModal = showMobileImageModal;
window.playMobileFootage = playMobileFootage;
window.debugMobileView = debugMobileView;
window.forceRefreshMobileView = forceRefreshMobileView;
