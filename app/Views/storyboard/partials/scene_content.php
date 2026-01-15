<!-- Mobile-Responsive Scene Headings Section -->
<div class="space-y-4 md:space-y-6">
    <!-- Mobile Layout -->
    <div class="block md:hidden bg-white rounded-lg shadow-sm border p-3">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
                <i data-feather="bookmark" class="w-5 h-5 text-blue-600"></i>
                <h4 class="text-base font-semibold text-gray-800">Scenes</h4>
            </div>
            <button type="button" class="flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg" id="mobile-view-toggle">
                <i data-feather="smartphone" class="w-4 h-4"></i>
            </button>
        </div>
        
        <div class="grid grid-cols-2 gap-2">
            <button type="button" class="flex items-center justify-center space-x-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm" data-bs-toggle="modal" data-bs-target="#scene-heading-modal" onclick="loadSceneHeadingModal(<?php echo $project_id; ?>, null, <?php echo $sub_project_id ?: 'null'; ?>)">
                <i data-feather="bookmark" class="w-3 h-3"></i>
                <span>Add Heading</span>
            </button>
            <button type="button" class="flex items-center justify-center space-x-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm" id="reorder-mode">
                <i data-feather="move" class="w-3 h-3"></i>
                <span>Reorder</span>
            </button>
        </div>
    </div>
    
    <!-- Desktop Layout -->
    <div class="hidden md:flex justify-between items-center bg-white rounded-lg shadow-sm border p-4">
        <div class="flex items-center space-x-3">
            <i data-feather="bookmark" class="w-6 h-6 text-blue-600"></i>
            <h4 class="text-lg font-semibold text-gray-800">Scene Headings & Storyboard</h4>
        </div>
        <div class="flex items-center space-x-2">
            <button type="button" class="flex items-center space-x-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200" data-bs-toggle="modal" data-bs-target="#scene-heading-modal" onclick="loadSceneHeadingModal(<?php echo $project_id; ?>, null, <?php echo $sub_project_id ?: 'null'; ?>)">
                <i data-feather="bookmark" class="w-4 h-4"></i>
                <span>Add Heading</span>
            </button>
            <button type="button" class="hidden lg:flex items-center justify-center w-10 h-10 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200" id="toggle-columns">
                <i data-feather="columns" class="w-5 h-5"></i>
            </button>
            <button type="button" class="flex items-center justify-center w-10 h-10 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200" id="reorder-mode">
                <i data-feather="move" class="w-5 h-5"></i>
            </button>
        </div>
    </div>

    <!-- Render Scene Headings with their Storyboards -->
    <?php foreach ($scene_headings as $heading): ?>
        <?php echo view('storyboard/partials/scene_heading', [
            'heading' => $heading,
            'storyboards_by_heading' => $storyboards_by_heading,
            'project_id' => $project_id,
            'sub_project_id' => $sub_project_id
        ]); ?>
    <?php endforeach; ?>

    <!-- Storyboards without heading -->
    <?php if (!empty($storyboards_without_heading)): ?>
        <?php echo view('storyboard/partials/unorganized_scenes', [
            'storyboards_without_heading' => $storyboards_without_heading,
            'project_id' => $project_id,
            'sub_project_id' => $sub_project_id
        ]); ?>
    <?php endif; ?>

    <!-- Empty state if no headings and no scenes -->
    <?php if (empty($scene_headings) && empty($storyboards_without_heading)): ?>
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <i data-feather="bookmark" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
            <h5 class="text-lg font-medium text-gray-900 mb-2">No Scene Headings or Scenes Yet</h5>
            <p class="text-gray-500 mb-6">Start by creating a scene heading to organize your storyboard scenes.</p>
            <button type="button" class="flex items-center space-x-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 mx-auto" data-bs-toggle="modal" data-bs-target="#scene-heading-modal" onclick="loadSceneHeadingModal(<?php echo $project_id; ?>, null, <?php echo $sub_project_id ?: 'null'; ?>)">
                <i data-feather="bookmark" class="w-4 h-4"></i>
                <span>Create First Scene Heading</span>
            </button>
        </div>
    <?php endif; ?>
</div>