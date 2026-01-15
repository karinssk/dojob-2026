<?php echo form_open(get_uri("storyboard/save_scene_heading"), array("id" => "scene-heading-form", "class" => "general-form", "role" => "form")); ?>

<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
        <input type="hidden" name="sub_project_id" value="<?php echo $sub_project_id ?: ''; ?>" />
        
        <div class="space-y-4">
            <div>
                <label for="header" class="block text-sm font-medium text-gray-700 mb-2">Scene Heading *</label>
                <input type="text" name="header" id="header" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" 
                       value="<?php echo $model_info->header ?? ''; ?>" 
                       placeholder="Enter scene heading (e.g., INT. LIVING ROOM - DAY)" required>
                <p class="mt-1 text-sm text-gray-500">This will be displayed as a section header above the storyboard scenes</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="shot" class="block text-sm font-medium text-gray-700 mb-2">Shot Number</label>
                    <input type="number" name="shot" id="shot" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" 
                           value="<?php echo $model_info->shot ?? ''; ?>" 
                           placeholder="Auto-generated if empty" min="1">
                    <p class="mt-1 text-sm text-gray-500">Leave empty to auto-generate next number</p>
                </div>
                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">Estimated Duration</label>
                    <input type="text" name="duration" id="duration" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" 
                           value="<?php echo $model_info->duration ?? ''; ?>" 
                           placeholder="e.g., 2:30 or 150">
                    <p class="mt-1 text-sm text-gray-500">Summary duration for scenes under this heading</p>
                </div>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" rows="3" 
                          placeholder="Optional description or notes for this scene heading"><?php echo $model_info->description ?? ''; ?></textarea>
            </div>
        </div>
    </div>
</div>

<div class="modal-header border-b border-gray-200 px-6 py-4">
    <h5 class="flex items-center text-lg font-semibold text-gray-900">
        <i data-feather="bookmark" class="w-5 h-5 mr-2 text-blue-600"></i>
        <?php echo isset($model_info->id) ? 'Edit Scene Heading' : 'Add Scene Heading'; ?>
    </h5>
    <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors duration-200" data-bs-dismiss="modal">
        <i data-feather="x" class="w-6 h-6"></i>
    </button>
</div>

<div class="modal-footer border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
    <button type="button" class="flex items-center space-x-2 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200" data-bs-dismiss="modal">
        <i data-feather="x" class="w-4 h-4"></i>
        <span>Cancel</span>
    </button>
    <button type="submit" class="flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
        <i data-feather="check-circle" class="w-4 h-4"></i>
        <span><?php echo isset($model_info->id) ? 'Update' : 'Add'; ?> Scene Heading</span>
    </button>
</div>

<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#scene-heading-form").appForm({
            onSuccess: function (result) {
                console.log('Scene heading save success:', result);
                if (result.success) {
                    if (typeof appAlert !== 'undefined') {
                        appAlert.success(result.message);
                    } else {
                        alert('Success: ' + result.message);
                    }
                    location.reload(); // Refresh to show new heading
                } else {
                    if (typeof appAlert !== 'undefined') {
                        appAlert.error(result.message);
                    } else {
                        alert('Error: ' + result.message);
                    }
                }
            },
            onError: function (result) {
                console.log('Scene heading save error:', result);
                if (typeof appAlert !== 'undefined') {
                    appAlert.error(result.message || 'Unknown error occurred');
                } else {
                    alert('Error: ' + (result.message || 'Unknown error occurred'));
                }
            }
        });
    });
</script>

<style>
/* Custom styles with Tailwind compatibility */
#header {
    font-family: 'Courier New', monospace;
    font-weight: bold;
}

/* Focus states for better accessibility */
input:focus, textarea:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Modal backdrop */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
}
</style>