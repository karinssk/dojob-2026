<div class="scene-heading-section mb-6">
    <div class="scene-heading-header">
        <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div class="flex-1">
                        <h6 class="flex items-center text-yellow-800 font-semibold mb-2">
                            <i data-feather="alert-triangle" class="w-4 h-4 mr-2"></i>
                            Unorganized Scenes
                        </h6>
                        <p class="text-gray-600 text-sm mb-2">These scenes are not assigned to any scene heading</p>
                        <div class="flex items-center text-sm text-gray-500">
                            <i data-feather="film" class="w-3 h-3 mr-1"></i>
                            <span><?php echo count($storyboards_without_heading); ?> scene(s)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Unorganized Storyboard Scenes -->
    <div class="bg-white rounded-lg shadow-sm border mt-3 overflow-hidden">
        <?php echo view('storyboard/partials/storyboard_table', [
            'storyboards' => $storyboards_without_heading,
            'project_id' => $project_id,
            'sub_project_id' => $sub_project_id,
            'heading_id' => 'unorganized'
        ]); ?>
    </div>
</div>