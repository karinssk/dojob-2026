<?php
// Helper function to get icon for field value
function getFieldIcon($field, $value) {
    $fieldOptions = array(
        'story_status' => array(
            array('value' => 'Draft', 'icon' => ''),
            array('value' => 'Editing', 'icon' => ''),
            array('value' => 'Review', 'icon' => ''),
            array('value' => 'Approved', 'icon' => ''),
            array('value' => 'Final', 'icon' => '')
        ),
        'shot_size' => array(
            array('value' => 'Full Shot', 'icon' => '🧍'),
            array('value' => 'Medium Shot', 'icon' => '👤'),
            array('value' => 'Close-up', 'icon' => '😊'),
            array('value' => 'Extreme Close-up', 'icon' => '👁️'),
            array('value' => 'Wide Shot', 'icon' => '🏞️'),
            array('value' => 'Long Shot', 'icon' => '🌄')
        ),
        'shot_type' => array(
            array('value' => 'Eye Level', 'icon' => '👀'),
            array('value' => 'High Angle', 'icon' => '⬆️'),
            array('value' => 'Low Angle', 'icon' => '⬇️'),
            array('value' => 'Bird\'s Eye', 'icon' => '🦅')
        ),
        'movement' => array(
            array('value' => 'Static', 'icon' => '⏸️'),
            array('value' => 'Pan', 'icon' => '↔️'),
            array('value' => 'Tilt', 'icon' => '↕️'),
            array('value' => 'Tracking', 'icon' => '🚂')
        ),
        'framerate' => array(
            array('value' => '24fps', 'icon' => '🎬'),
            array('value' => '30fps', 'icon' => '📹'),
            array('value' => '60fps', 'icon' => '⚡'),
            array('value' => '120fps', 'icon' => '🚀')
        )
    );
    
    if (isset($fieldOptions[$field])) {
        foreach ($fieldOptions[$field] as $option) {
            if ($option['value'] === $value && !empty($option['icon'])) {
                return '<span class="field-icon">' . $option['icon'] . '</span> ';
            }
        }
    }
    return '';
}
?>

<!-- Remove conflicting CSS file and keep only necessary external libraries -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Mobile-Responsive Breadcrumb Navigation -->
<nav aria-label="breadcrumb" class="mb-4 md:mb-6">
    <ol class="flex items-center space-x-1 md:space-x-2 text-xs md:text-sm text-gray-600 overflow-x-auto">
        <li class="flex-shrink-0">
            <a href="<?php echo get_uri('storyboard'); ?>" class="flex items-center hover:text-blue-600 transition-colors duration-200">
                <i data-feather="home" class="w-3 h-3 md:w-4 md:h-4 mr-1"></i>
                <span class="hidden sm:inline">Storyboard Projects</span>
                <span class="sm:hidden">Home</span>
            </a>
        </li>
        <li class="text-gray-400 flex-shrink-0">/</li>
        <?php if (!empty($sub_project_id)): ?>
            <li class="flex-shrink-0">
                <a href="<?php echo get_uri('storyboard?project_id=' . $project_id); ?>" class="hover:text-blue-600 transition-colors duration-200">
                    <?php echo character_limiter($project_info->title, 15); ?>
                </a>
            </li>
            <li class="text-gray-400 flex-shrink-0">/</li>
            <li class="text-gray-900 font-medium flex-shrink-0" aria-current="page">
                <span class="hidden sm:inline">Sub-Project <?php echo $sub_project_id; ?></span>
                <span class="sm:hidden">Sub-<?php echo $sub_project_id; ?></span>
            </li>
        <?php else: ?>
            <li class="text-gray-900 font-medium truncate" aria-current="page">
                <?php echo character_limiter($project_info->title, 20); ?>
            </li>
        <?php endif; ?>
    </ol>
</nav>

<!-- Mobile-Responsive Project Header -->
<div class="bg-white rounded-lg shadow-sm border p-3 md:p-6 mb-4 md:mb-6">
    <!-- Mobile Layout (stacked) -->
    <div class="block md:hidden">
        <!-- Back button and title -->
        <div class="flex items-center justify-between mb-3">
            <?php 
            // Check if we have sub_project_id in URL even if variable is empty
            $url_sub_project_id = $_GET['sub_project_id'] ?? null;
            if (!empty($sub_project_id) || !empty($url_sub_project_id)): 
            ?>
                <a href="<?php echo get_uri('storyboard?project_id=' . $project_id); ?>" class="flex items-center space-x-1 px-2 py-1 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded text-sm">
                    <i data-feather="arrow-left" class="w-4 h-4"></i>
                    <span>Back</span>
                </a>
            <?php else: ?>
                <a href="<?php echo get_uri('storyboard'); ?>" class="flex items-center space-x-1 px-2 py-1 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded text-sm">
                    <i data-feather="arrow-left" class="w-4 h-4"></i>
                    <span>Back</span>
                </a>
            <?php endif; ?>
            
            <!-- Mobile menu dropdown -->
            <div class="dropdown">
                <button type="button" class="flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-feather="more-horizontal" class="w-4 h-4"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 180px;">
                    <li><a class="dropdown-item d-flex align-items-center text-sm" href="<?php echo get_uri('storyboard'); ?>">
                        <i data-feather="list" class="w-4 h-4 me-2"></i>Switch Project
                    </a></li>
                    <li><a class="dropdown-item d-flex align-items-center text-sm" href="<?php echo get_uri('storyboard?create_new=1'); ?>">
                        <i data-feather="plus-circle" class="w-4 h-4 me-2"></i>New Project
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center text-sm" href="#" onclick="openInlineExportModal(); return false;">
                        <i data-feather="download" class="w-4 h-4 me-2"></i>Export Storyboard
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center text-sm" href="#" onclick="editCurrentProject(); return false;">
                        <i data-feather="edit" class="w-4 h-4 me-2"></i>Edit Project
                    </a></li>
                    <li><a class="dropdown-item d-flex align-items-center text-sm" href="#" onclick="openFieldOptionsModal(); return false;">
                        <i data-feather="sliders" class="w-4 h-4 me-2"></i>Manage Fields
                    </a></li>
                </ul>
            </div>
        </div>
        
        <!-- Project title and status -->
        <div class="mb-3">
            <h3 class="flex items-center text-lg font-semibold text-gray-900 mb-2">
                <i data-feather="film" class="w-4 h-4 mr-2 text-blue-600"></i>
                <span class="truncate"><?php echo character_limiter($sub_project_info->title, 30); ?></span>
            </h3>
            <?php 
            $status_classes = [
                'active' => 'bg-green-100 text-green-800',
                'completed' => 'bg-blue-100 text-blue-800',
                'on_hold' => 'bg-yellow-100 text-yellow-800',
                'draft' => 'bg-gray-100 text-gray-800'
            ];
            $status_class = $status_classes[$project_info->status] ?? $status_classes['draft'];
            ?>
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $project_info->status)); ?>
            </span>
        </div>
        
        <!-- Mobile Action buttons -->
        <div class="grid grid-cols-2 gap-2">
            <button type="button" class="flex items-center justify-center space-x-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 text-sm font-medium" onclick="openInlineExportModal()">
                <i data-feather='download' class='w-4 h-4'></i>
                <span>Export</span>
            </button>
            <button type="button" class="flex items-center justify-center space-x-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 text-sm font-medium" data-bs-toggle="modal" data-bs-target="#storyboard-modal" onclick="loadStoryboardModal(<?php echo $project_id; ?>, null, <?php echo $sub_project_id ?: 'null'; ?>); return false;">
                <i data-feather='plus-circle' class='w-4 h-4'></i>
                <span>Add Scene</span>
            </button>
        </div>
    </div>
    
    <!-- Desktop Layout (horizontal) -->
    <div class="hidden md:flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <?php 
            // Check if we have sub_project_id in URL even if variable is empty
            $url_sub_project_id = $_GET['sub_project_id'] ?? null;
            if (!empty($sub_project_id) || !empty($url_sub_project_id)): 
            ?>
                <a href="<?php echo get_uri('storyboard?project_id=' . $project_id); ?>" class="flex items-center space-x-2 px-3 py-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200">
                    <i data-feather="arrow-left" class="w-4 h-4"></i>
                    <span>Back to Sub-Projects</span>
                </a>
            <?php else: ?>
                <a href="<?php echo get_uri('storyboard'); ?>" class="flex items-center space-x-2 px-3 py-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200">
                    <i data-feather="arrow-left" class="w-4 h-4"></i>
                    <span>Back to Projects</span>
                </a>
            <?php endif; ?>
            <div class="ml-4">
                <h3 class="flex items-center text-xl font-semibold text-gray-900 mb-1">
                    <i data-feather="film" class="w-5 h-5 mr-2 text-blue-600"></i>
                    <?php echo $sub_project_info->title; ?>
                </h3>
                <div>
                    <?php 
                    $status_classes = [
                        'active' => 'bg-green-100 text-green-800',
                        'completed' => 'bg-blue-100 text-blue-800',
                        'on_hold' => 'bg-yellow-100 text-yellow-800',
                        'draft' => 'bg-gray-100 text-gray-800'
                    ];
                    $status_class = $status_classes[$project_info->status] ?? $status_classes['draft'];
                    ?>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $project_info->status)); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <button type="button" class="flex items-center space-x-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200" onclick="openInlineExportModal()">
                <i data-feather='download' class='w-4 h-4'></i>
                <span>Export</span>
            </button>
            <button type="button" class="flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200" data-bs-toggle="modal" data-bs-target="#storyboard-modal" onclick="loadStoryboardModal(<?php echo $project_id; ?>, null, <?php echo $sub_project_id ?: 'null'; ?>); return false;">
                <i data-feather='plus-circle' class='w-4 h-4'></i>
                <span>Add Scene</span>
            </button>
            <div class="dropdown">
                <button type="button" class="flex items-center justify-center w-10 h-10 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-feather="more-horizontal" class="w-5 h-5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 200px;">
                    <li><a class="dropdown-item d-flex align-items-center" href="<?php echo get_uri('storyboard'); ?>">
                        <i data-feather="list" class="icon-16 me-2"></i>Switch Project
                    </a></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="<?php echo get_uri('storyboard?create_new=1'); ?>">
                        <i data-feather="plus-circle" class="icon-16 me-2"></i>Create New Project
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="#" onclick="openInlineExportModal(); return false;">
                        <i data-feather="download" class="icon-16 me-2"></i>Export Storyboard
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="#" onclick="editCurrentProject(); return false;">
                        <i data-feather="edit" class="icon-16 me-2"></i>Edit Project
                    </a></li>
                    <li><a class="dropdown-item d-flex align-items-center text-danger" href="#" onclick="deleteCurrentProject(); return false;">
                        <i data-feather="trash-2" class="icon-16 me-2"></i>Delete Project
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="#" onclick="openFieldOptionsModal(); return false;">
                        <i data-feather="sliders" class="icon-16 me-2"></i>Manage Fields
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</div>