<?php 
// Generate unique IDs for each table based on heading ID or a random identifier
$table_id = isset($heading_id) ? 'storyboard-table-' . $heading_id : 'storyboard-table-' . uniqid();
$tbody_id = isset($heading_id) ? 'storyboard-table-body-' . $heading_id : 'storyboard-table-body-' . uniqid();
?>

<!-- Clean Storyboard Table -->
<div class="bg-white rounded-lg shadow border overflow-hidden storyboard-table-container">
    <!-- Table Header -->
    <div class="bg-gray-50 px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
            <i data-feather="film" class="w-5 h-5 mr-2 text-blue-600"></i>
            Storyboard Scenes
        </h3>
    </div>

    <!-- Table -->
    <div class="w-full">
        <table class="w-full divide-y divide-gray-200 storyboard-table table-fixed" id="<?php echo $table_id; ?>">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="shot">Shot #</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="frame">Frame</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="shot_size">Size</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="shot_type">Type</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="movement">Movement</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="duration">Duration</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="content">Content</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="dialogues">Dialogues</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="sound">Sound</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="equipment">Equipment</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="framerate">FPS</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="lighting">Lighting</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="note">Note</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="raw_footage">Footage</th>
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-200 transition-colors duration-200" data-column="story_status">Status</th>
                    <th class="px-3 py-3 text-center text-sm font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 storyboard-table-body" id="<?php echo $tbody_id; ?>" data-heading-id="<?php echo $heading_id ?? 'unorganized'; ?>">
                <?php foreach ($storyboards as $storyboard): ?>
                <tr data-id="<?php echo $storyboard->id; ?>" class="hover:bg-gray-50 transition-colors storyboard-row">
                    <!-- Shot Number -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="flex items-center justify-center">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-bold text-white"><?php echo $storyboard->shot; ?></span>
                            </div>
                        </div>
                    </td>
                    
                    <!-- Frame Image -->
                    <td class="px-3 py-3">
                        <div class="relative image-container group">
                            <?php if ($storyboard->frame): ?>
                                <?php 
                                $frame_data = @unserialize($storyboard->frame);
                                if ($frame_data && isset($frame_data['file_name'])): ?>
                                    <div class="relative">
                                        <img src="<?php echo base_url('files/storyboard_frames/' . $frame_data['file_name']); ?>" 
                                            class="w-40 h-32 object-cover rounded shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer"
                                            onclick="showImageModal('<?php echo base_url('files/storyboard_frames/' . $frame_data['file_name']); ?>')"
                                            alt="Storyboard frame">
                                        
                                        <!-- Edit Button with better positioning -->
                                        <button type="button" 
                                                class="edit-image-btn absolute top-0 right-0 bg-white/90 hover:bg-white text-gray-700 rounded p-1 text-xs opacity-0 group-hover:opacity-100 transition-all duration-200 shadow-sm hover:shadow" 
                                                data-storyboard-id="<?php echo $storyboard->id; ?>"
                                                title="Edit Image">
                                            <i data-feather="edit-3" class="w-3 h-3"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Upload Area -->
                                <div class="frame-upload-area w-40 h-32 bg-gray-50 border-2 border-dashed border-gray-300 rounded flex flex-col items-center justify-center hover:bg-blue-50 hover:border-blue-400 transition-all duration-200 cursor-pointer relative"
                                     data-storyboard-id="<?php echo $storyboard->id; ?>"
                                     onclick="triggerFrameUpload(<?php echo $storyboard->id; ?>)">
                                    <input type="file" 
                                           id="frame-upload-<?php echo $storyboard->id; ?>" 
                                           class="hidden frame-upload-input" 
                                           accept="image/*"
                                           data-storyboard-id="<?php echo $storyboard->id; ?>">
                                    <i data-feather="upload-cloud" class="w-8 h-8 text-gray-400 mb-1"></i>
                                    <span class="text-xs text-gray-500">Click to upload</span>
                                    
                                    <!-- Upload Progress -->
                                    <div class="upload-progress hidden absolute inset-0 bg-white/95 rounded flex flex-col items-center justify-center">
                                        <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden mb-2">
                                            <div class="upload-progress-bar h-full bg-blue-600 transition-all duration-300" style="width: 0%"></div>
                                        </div>
                                        <span class="upload-progress-text text-xs text-gray-600">Uploading...</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    
                    <!-- Shot Size -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-blue-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="shot_size" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <span class="text-sm text-gray-900 editable-content truncate block" data-full-value="<?php echo htmlspecialchars($storyboard->shot_size ?: ''); ?>">
                                <span class="field-icon-php"></span><?php echo $storyboard->shot_size ?: 'Not set'; ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Shot Type -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-green-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="shot_type" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <span class="text-sm text-gray-900 editable-content truncate block" data-full-value="<?php echo htmlspecialchars($storyboard->shot_type ?: ''); ?>">
                                <span class="field-icon-php"></span><?php echo $storyboard->shot_type ?: 'Not set'; ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Movement -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-purple-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="movement" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <span class="text-sm text-gray-900 editable-content truncate block" data-full-value="<?php echo htmlspecialchars($storyboard->movement ?: ''); ?>">
                                <?php echo $storyboard->movement ?: 'Not set'; ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Duration -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-orange-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="duration" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <span class="text-sm text-gray-900 editable-content truncate block" data-full-value="<?php echo htmlspecialchars($storyboard->duration ?: ''); ?>">
                                <?php echo $storyboard->duration ? $storyboard->duration . ' s' : 'Not set'; ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Content -->
                    <td class="px-3 py-3">
                        <div class="cursor-pointer hover:bg-indigo-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="content" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <p class="text-sm text-gray-900 truncate editable-content" data-full-value="<?php echo htmlspecialchars($storyboard->content ?: ''); ?>">
                                <?php 
                                $content_text = $storyboard->content ?: '';
                                if (mb_strlen($content_text, 'UTF-8') > 30) {
                                    echo htmlspecialchars(mb_substr($content_text, 0, 30, 'UTF-8')) . '...';
                                } else {
                                    echo $content_text ? htmlspecialchars($content_text) : 'No content';
                                }
                                ?>
                            </p>
                        </div>
                    </td>
                    
                    <!-- Dialogues -->
                    <td class="px-3 py-3">
                        <div class="cursor-pointer hover:bg-pink-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="dialogues" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <p class="text-sm text-gray-900 truncate editable-content" data-full-value="<?php echo htmlspecialchars($storyboard->dialogues ?: ''); ?>">
                                <?php 
                                $dialogues_text = $storyboard->dialogues ?: '';
                                if (mb_strlen($dialogues_text, 'UTF-8') > 30) {
                                    echo htmlspecialchars(mb_substr($dialogues_text, 0, 30, 'UTF-8')) . '...';
                                } else {
                                    echo $dialogues_text ? htmlspecialchars($dialogues_text) : 'No dialogues';
                                }
                                ?>
                            </p>
                        </div>
                    </td>
                    
                    <!-- Sound -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-yellow-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="sound" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <span class="text-sm text-gray-900 editable-content truncate block" data-full-value="<?php echo htmlspecialchars($storyboard->sound ?: ''); ?>">
                                <?php 
                                $sound_text = $storyboard->sound ?: '';
                                if (mb_strlen($sound_text, 'UTF-8') > 15) {
                                    echo htmlspecialchars(mb_substr($sound_text, 0, 15, 'UTF-8')) . '...';
                                } else {
                                    echo $sound_text ? htmlspecialchars($sound_text) : 'Not set';
                                }
                                ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Equipment -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-teal-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="equipment" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <span class="text-sm text-gray-900 editable-content truncate block" data-full-value="<?php echo htmlspecialchars($storyboard->equipment ?: ''); ?>">
                                <?php 
                                $equipment_text = $storyboard->equipment ?: '';
                                if (mb_strlen($equipment_text, 'UTF-8') > 15) {
                                    echo htmlspecialchars(mb_substr($equipment_text, 0, 15, 'UTF-8')) . '...';
                                } else {
                                    echo $equipment_text ? htmlspecialchars($equipment_text) : 'Not set';
                                }
                                ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Frame Rate -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-red-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="framerate" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <span class="text-sm text-gray-900 editable-content truncate block" data-full-value="<?php echo htmlspecialchars($storyboard->framerate ?: ''); ?>">
                                <?php echo $storyboard->framerate ?: 'Not set'; ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Lighting -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-amber-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="lighting" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <span class="text-sm text-gray-900 editable-content truncate block" data-full-value="<?php echo htmlspecialchars($storyboard->lighting ?: ''); ?>">
                                <?php 
                                $lighting_text = $storyboard->lighting ?: '';
                                if (mb_strlen($lighting_text, 'UTF-8') > 15) {
                                    echo htmlspecialchars(mb_substr($lighting_text, 0, 15, 'UTF-8')) . '...';
                                } else {
                                    echo $lighting_text ? htmlspecialchars($lighting_text) : 'Not set';
                                }
                                ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Note -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-cyan-50 rounded p-1 transition-all duration-200 editable-cell inline-editable" data-field="note" data-id="<?php echo $storyboard->id; ?>" title="Click to edit">
                            <span class="text-sm text-gray-900 editable-content truncate block" data-full-value="<?php echo htmlspecialchars($storyboard->note ?: ''); ?>">
                                <?php 
                                $note_text = $storyboard->note ?: '';
                                if (mb_strlen($note_text, 'UTF-8') > 15) {
                                    echo htmlspecialchars(mb_substr($note_text, 0, 15, 'UTF-8')) . '...';
                                } else {
                                    echo $note_text ? htmlspecialchars($note_text) : 'Not set';
                                }
                                ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Raw Footage -->
                    <td class="px-3 py-3">
                        <?php if ($storyboard->raw_footage): ?>
                            <?php 
                            $footage_data = @unserialize($storyboard->raw_footage);
                            if ($footage_data && is_array($footage_data) && count($footage_data) > 0): ?>
                                <div class="space-y-1">
                                    <?php foreach (array_slice($footage_data, 0, 1) as $index => $file): ?>
                                        <button type="button" 
                                                class="flex items-center space-x-1 px-2 py-1 text-sm bg-gray-50 border border-gray-200 rounded hover:bg-blue-50 hover:border-blue-300 transition-colors preview-video-btn-index" 
                                                data-video-url="<?php echo base_url('files/storyboard_footage/' . $file['file_name']); ?>"
                                                data-video-name="<?php echo htmlspecialchars($file['original_name'] ?? $file['file_name']); ?>"
                                                title="Click to preview video">
                                            <i data-feather="video" class="w-4 h-4 text-gray-500"></i>
                                            <span class="text-gray-700 truncate max-w-16">
                                                <?php echo character_limiter($file['original_name'] ?? $file['file_name'], 8); ?>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                    <?php if (count($footage_data) > 1): ?>
                                        <div class="text-sm text-gray-500">
                                            +<?php echo count($footage_data) - 1; ?> more
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm">No files</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-400 text-sm">No files</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Status -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="cursor-pointer hover:bg-gray-50 rounded p-1 transition-all duration-200 editable-cell status-cell inline-editable" data-field="story_status" data-id="<?php echo $storyboard->id; ?>" title="Click to change status">
                            <?php 
                            $status_classes = [
                                'Draft' => 'bg-gray-100 text-gray-800',
                                'Editing' => 'bg-yellow-100 text-yellow-800',
                                'Review' => 'bg-blue-100 text-blue-800',
                                'Approved' => 'bg-green-100 text-green-800',
                                'Final' => 'bg-purple-100 text-purple-800'
                            ];
                            $current_status = $storyboard->story_status ?: 'Draft';
                            $status_class = $status_classes[$current_status] ?? $status_classes['Draft'];
                            ?>
                            <span class="inline-flex px-2 py-1 text-sm font-semibold rounded <?php echo $status_class; ?> editable-content" data-value="<?php echo $current_status; ?>">
                                <span class="field-icon-php"></span><?php echo $current_status; ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Actions -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="flex items-center justify-center space-x-2">
                            <?php echo modal_anchor(get_uri("storyboard/modal_form"), 
                                '<i data-feather="edit" class="w-4 h-4"></i>', 
                                array(
                                    "class" => "inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded transition-colors", 
                                    "title" => "Edit", 
                                    "data-post-id" => $storyboard->id, 
                                    "data-post-project_id" => $project_id, 
                                    "data-post-sub_project_id" => $sub_project_id
                                )
                            ); ?>
                            
                            <?php echo js_anchor(
                                '<i data-feather="trash-2" class="w-4 h-4"></i>', 
                                array(
                                    'title' => 'Delete', 
                                    "class" => "inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 hover:bg-red-100 rounded transition-colors delete", 
                                    "data-id" => $storyboard->id, 
                                    "data-action-url" => get_uri("storyboard/delete"), 
                                    "data-action" => "delete-confirmation"
                                )
                            ); ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Empty State -->
    <?php if (empty($storyboards)): ?>
    <div class="text-center py-12">
        <i data-feather="film" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No storyboard scenes yet</h3>
        <p class="text-sm text-gray-500 mb-6">Get started by creating your first storyboard scene.</p>
        <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
            <i data-feather="plus" class="w-4 h-4 mr-2"></i>
            Add New Scene
        </button>
    </div>
    <?php endif; ?>
</div>