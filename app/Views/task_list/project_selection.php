<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i data-feather="list" class="icon-16"></i>
                        <?php echo app_lang('select_project_for_task_list'); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <div class="text-center mb-4">
                                <p class="text-muted"><?php echo app_lang('select_project_to_view_tasks'); ?></p>
                            </div>
                            
                            <div class="project-selection-container">
                                <?php if (count($projects) > 0): ?>
                                    <div class="row">
                                        <?php foreach ($projects as $project): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card project-card h-100" style="cursor: pointer;" onclick="selectProject(<?php echo $project->id; ?>)">
                                                    <div class="card-body text-center">
                                                        <div class="project-icon mb-3">
                                                            <i data-feather="folder" class="icon-32 text-primary"></i>
                                                        </div>
                                                        <h5 class="card-title"><?php echo $project->title; ?></h5>
                                                        <p class="card-text text-muted small">
                                                            <?php echo app_lang('status'); ?>: 
                                                            <span class="badge badge-<?php echo $project->status == 'open' ? 'success' : 'secondary'; ?>">
                                                                <?php echo app_lang($project->status); ?>
                                                            </span>
                                                        </p>
                                                        <?php if ($project->description): ?>
                                                            <p class="card-text small text-truncate" title="<?php echo strip_tags($project->description); ?>">
                                                                <?php echo character_limiter(strip_tags($project->description), 60); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <div class="empty-state">
                                            <i data-feather="folder-x" class="icon-64 text-muted mb-3"></i>
                                            <h5><?php echo app_lang('no_projects_found'); ?></h5>
                                            <p class="text-muted"><?php echo app_lang('no_projects_available_for_task_list'); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Recently Selected Projects -->
                            <div class="recent-projects mt-4" id="recent-projects" style="display: none;">
                                <h6><?php echo app_lang('recently_selected'); ?></h6>
                                <div class="list-group" id="recent-projects-list">
                                    <!-- Recent projects will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.project-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.project-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.project-icon {
    opacity: 0.8;
}

.project-card:hover .project-icon {
    opacity: 1;
}

.empty-state {
    padding: 40px 20px;
}

.recent-projects .list-group-item {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.recent-projects .list-group-item:hover {
    background-color: #f8f9fa;
}
</style>

<script>
function selectProject(projectId) {
    // Save to localStorage for future reference
    saveRecentProject(projectId);
    
    // Show loading state
    showLoadingState();
    
    // Redirect to task list with selected project
    window.location.href = '<?php echo get_uri("task_list"); ?>?project_id=' + projectId;
}

function saveRecentProject(projectId) {
    let recentProjects = JSON.parse(localStorage.getItem('taskListRecentProjects') || '[]');
    
    // Remove if already exists
    recentProjects = recentProjects.filter(p => p.id != projectId);
    
    // Find project info
    const projectCard = document.querySelector(`[onclick="selectProject(${projectId})"]`);
    const projectTitle = projectCard.querySelector('.card-title').textContent;
    
    // Add to beginning
    recentProjects.unshift({
        id: projectId,
        title: projectTitle,
        timestamp: Date.now()
    });
    
    // Keep only last 5
    recentProjects = recentProjects.slice(0, 5);
    
    localStorage.setItem('taskListRecentProjects', JSON.stringify(recentProjects));
    localStorage.setItem('taskListSelectedProject', projectId);
}

function loadRecentProjects() {
    const recentProjects = JSON.parse(localStorage.getItem('taskListRecentProjects') || '[]');
    
    if (recentProjects.length > 0) {
        const recentContainer = document.getElementById('recent-projects');
        const recentList = document.getElementById('recent-projects-list');
        
        recentList.innerHTML = '';
        
        recentProjects.forEach(project => {
            const item = document.createElement('a');
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            item.onclick = () => selectProject(project.id);
            
            item.innerHTML = `
                <div>
                    <i data-feather="folder" class="icon-16 me-2"></i>
                    ${project.title}
                </div>
                <small class="text-muted">${formatTimestamp(project.timestamp)}</small>
            `;
            
            recentList.appendChild(item);
        });
        
        recentContainer.style.display = 'block';
        
        // Replace feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }
}

function formatTimestamp(timestamp) {
    const now = Date.now();
    const diff = now - timestamp;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
}

function showLoadingState() {
    const cards = document.querySelectorAll('.project-card');
    cards.forEach(card => {
        card.style.opacity = '0.6';
        card.style.pointerEvents = 'none';
    });
    
    // Show loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'text-center mt-3';
    loadingDiv.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading task list...</p>
    `;
    
    document.querySelector('.project-selection-container').appendChild(loadingDiv);
}

// Load recent projects on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRecentProjects();
    
    // Replace feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>