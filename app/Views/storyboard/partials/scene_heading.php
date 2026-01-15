<div class="scene-heading-section mb-6" data-heading-id="<?php echo $heading->id; ?>">
    <!-- Scene Heading Header - Tailwind -->
    <div class="scene-heading-header">
        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg">
            <!-- Mobile Layout -->
            <div class="block md:hidden p-3">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1 min-w-0">
                        <h6 class="flex items-center text-blue-800 font-semibold text-sm mb-1">
                            <i data-feather="bookmark" class="w-3 h-3 mr-1 flex-shrink-0"></i>
                            <span class="truncate">Shot <?php echo $heading->shot; ?>: <?php echo character_limiter($heading->header, 25); ?></span>
                        </h6>
                        <div class="flex items-center space-x-3 text-xs text-gray-500">
                            <?php 
                            $scene_count = isset($storyboards_by_heading[$heading->id]) ? count($storyboards_by_heading[$heading->id]) : 0;
                            ?>
                            <span><?php echo $scene_count; ?> scene(s)</span>
                            <?php if ($heading->duration): ?>
                                <span class="flex items-center">
                                    <i data-feather="clock" class="w-3 h-3 mr-1"></i>
                                    <?php echo $heading->duration; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dropdown flex-shrink-0 ml-2">
                        <button class="flex items-center justify-center w-6 h-6 text-gray-500 hover:text-gray-700 hover:bg-white rounded transition-colors duration-200 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-feather="more-vertical" class="w-3 h-3"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 160px;">
                            <li>
                                <a class="dropdown-item d-flex align-items-center text-sm" href="#" onclick="loadSceneHeadingModal(<?php echo $project_id; ?>, <?php echo $heading->id; ?>, <?php echo $sub_project_id ?: 'null'; ?>); $('#scene-heading-modal').modal('show'); return false;">
                                    <i data-feather="edit" class="w-3 h-3 me-2"></i>Edit
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center text-danger text-sm" href="#" onclick="deleteSceneHeading(<?php echo $heading->id; ?>, '<?php echo addslashes($heading->header); ?>'); return false;">
                                    <i data-feather="trash-2" class="w-3 h-3 me-2"></i>Delete
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <?php if ($heading->description): ?>
                    <p class="text-gray-600 text-xs mb-2 line-clamp-2"><?php echo $heading->description; ?></p>
                <?php endif; ?>
                
                <button type="button" class="w-full flex items-center justify-center space-x-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors duration-200" data-bs-toggle="modal" data-bs-target="#storyboard-modal" onclick="loadStoryboardModal(<?php echo $project_id; ?>, null, <?php echo $sub_project_id ?: 'null'; ?>, <?php echo $heading->id; ?>)">
                    <i data-feather="plus" class="w-3 h-3"></i>
                    <span>Add Scene</span>
                </button>
            </div>
            
            <!-- Desktop Layout -->
            <div class="hidden md:block p-4">
                <div class="flex justify-between items-center">
                    <div class="flex-1">
                        <h6 class="flex items-center text-blue-800 font-semibold mb-2">
                            <i data-feather="bookmark" class="w-4 h-4 mr-2"></i>
                            Shot <?php echo $heading->shot; ?>: <?php echo $heading->header; ?>
                        </h6>
                        <?php if ($heading->description): ?>
                            <p class="text-gray-600 text-sm mb-2"><?php echo $heading->description; ?></p>
                        <?php endif; ?>
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <?php 
                            $scene_count = isset($storyboards_by_heading[$heading->id]) ? count($storyboards_by_heading[$heading->id]) : 0;
                            ?>
                            <span><?php echo $scene_count; ?> scene(s)</span>
                            <?php if ($heading->duration): ?>
                                <span class="flex items-center">
                                    <i data-feather="clock" class="w-3 h-3 mr-1"></i>
                                    <?php echo $heading->duration; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" class="flex items-center space-x-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors duration-200" data-bs-toggle="modal" data-bs-target="#storyboard-modal" onclick="loadStoryboardModal(<?php echo $project_id; ?>, null, <?php echo $sub_project_id ?: 'null'; ?>, <?php echo $heading->id; ?>)">
                            <i data-feather="plus" class="w-3 h-3"></i>
                            <span>Add Scene</span>
                        </button>
                        <div class="dropdown">
                            <button class="flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-white rounded-lg transition-colors duration-200 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i data-feather="more-vertical" class="w-4 h-4"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 200px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="#" onclick="loadSceneHeadingModal(<?php echo $project_id; ?>, <?php echo $heading->id; ?>, <?php echo $sub_project_id ?: 'null'; ?>); $('#scene-heading-modal').modal('show'); return false;">
                                        <i data-feather="edit" class="icon-16 me-2"></i>Edit Scene Heading
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center text-danger" href="#" onclick="deleteSceneHeading(<?php echo $heading->id; ?>, '<?php echo addslashes($heading->header); ?>'); return false;">
                                        <i data-feather="trash-2" class="icon-16 me-2"></i>Delete Scene Heading
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Storyboard Scenes under this heading -->
    <?php if (isset($storyboards_by_heading[$heading->id]) && !empty($storyboards_by_heading[$heading->id])): ?>
        <div class="bg-white rounded-lg shadow-sm border mt-3 overflow-hidden">
            <?php echo view('storyboard/partials/storyboard_table', [
                'storyboards' => $storyboards_by_heading[$heading->id],
                'project_id' => $project_id,
                'sub_project_id' => $sub_project_id,
                'heading_id' => $heading->id
            ]); ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border mt-3 p-8 text-center">
            <i data-feather="film" class="w-8 h-8 text-gray-400 mx-auto mb-3"></i>
            <p class="text-gray-500 mb-4">No scenes under this heading yet</p>
            <button type="button" class="flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors duration-200 mx-auto" data-bs-toggle="modal" data-bs-target="#storyboard-modal" onclick="loadStoryboardModal(<?php echo $project_id; ?>, null, <?php echo $sub_project_id ?: 'null'; ?>, <?php echo $heading->id; ?>)">
                <i data-feather="plus" class="w-3 h-3"></i>
                <span>Add First Scene</span>
            </button>
        </div>
    <?php endif; ?>
</div>