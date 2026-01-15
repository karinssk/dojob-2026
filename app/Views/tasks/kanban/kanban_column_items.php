<?php

$show_in_kanban = get_setting("show_in_kanban");
$show_in_kanban_items = explode(',', $show_in_kanban);

foreach ($tasks as $task) {
    $task_labels = "";
    $task_checklist_status = "";
    $checklist_label_color = "#6690F4";

    if ($task->total_checklist_checked <= 0) {
        $checklist_label_color = "#E18A00";
    } else if ($task->total_checklist_checked == $task->total_checklist) {
        $checklist_label_color = "#01B392";
    }

    if ($task->priority_id) {
        $task_labels .= "<div class='meta float-start mr5'><span class='sub-task-icon priority-badge' data-bs-toggle='tooltip' title='" . app_lang("priority") . ": " . $task->priority_title . "' style='background: $task->priority_color'><i data-feather='$task->priority_icon' class='icon-14'></i></span></div>";
    }

    if ($task->total_checklist) {
        $task_checklist_status .= "<div class='meta float-start badge rounded-pill mr5' style='background-color:$checklist_label_color'><span data-bs-toggle='tooltip' title='" . app_lang("checklist_status") . "'><i data-feather='check' class='icon-14'></i> $task->total_checklist_checked/$task->total_checklist</span></div>";
    }

    $task_labels_data = make_labels_view_data($task->labels_list);
    $sub_task_icon = "";
    if ($task->parent_task_id) {
        $sub_task_icon = "<span class='sub-task-icon mr5' title='" . app_lang("sub_task") . "'><i data-feather='git-merge' class='icon-14'></i></span>";
    }

    if ($task_labels_data) {
        $task_labels .= "<div class='meta float-start mr5'>$task_labels_data</div>";
    }

    $unread_comments_class = "";
    if (isset($task->unread) && $task->unread && $task->unread != "0") {
        $unread_comments_class = "unread-comments-of-kanban unread";
    }

    $toggle_sub_task_icon = "";

    if ($task->has_sub_tasks) {
        $toggle_sub_task_icon = "<span class='filter-sub-task-kanban-button clickable float-end ml5' title='" . app_lang("show_sub_tasks") . "' main-task-id= '#$task->id'><i data-feather='filter' class='icon-14'></i></span>";
    }

    $disable_dragging = get_array_value($tasks_edit_permissions, $task->id) ? "" : "disable-dragging";

    $kanban_custom_fields_data = "";
    $kanban_custom_fields = get_custom_variables_data("tasks", $task->id, $login_user->is_admin);
    if ($kanban_custom_fields) {
        foreach ($kanban_custom_fields as $kanban_custom_field) {
            $kanban_custom_fields_data .= "<div class='mt5 font-12'>" . get_array_value($kanban_custom_field, "custom_field_title") . ": " . view("custom_fields/output_" . get_array_value($kanban_custom_field, "custom_field_type"), array("value" => get_array_value($kanban_custom_field, "value"))) . "</div>";
        }
    }

    $start_date = "";
    if ($task->start_date) {
        $start_date = "<div class='mt10 font-12 float-start' title='" . app_lang("start_date") . "'><i data-feather='calendar' class='icon-14 text-off mr5'></i> " . format_to_date($task->start_date, false) . "</div>";
    }

    $deadline_text = "-";
    if ($task->deadline && is_date_exists($task->deadline)) {
        $deadline_text = format_to_date($task->deadline, false);
        if (get_my_local_time("Y-m-d") > $task->deadline && $task->status_id != "3") {
            $deadline_text = "<span class='text-danger'>" . $deadline_text . "</span> ";
        } else if (format_to_date(get_my_local_time(), false) == format_to_date($task->deadline, false) && $task->status_id != "3") {
            $deadline_text = "<span class='text-warning'>" . $deadline_text . "</span> ";
        }
    }

    $end_date = "";
    if ($task->deadline) {
        $end_date = "<div class='mt10 font-12 float-end' title='" . app_lang("deadline") . "'><i data-feather='calendar' class='icon-14 text-off mr5'></i> " . $deadline_text . "</div>";
    }

    $task_id = "";
    $parent_task_id = "";
    if (in_array("id", $show_in_kanban_items)) {
        $task_id = $task->id . ". ";
        $parent_task_id = $task->parent_task_id . ". ";
    }

    $project_name = "";
    if ($task->project_title && in_array("project_name", $show_in_kanban_items)) {
        $project_name = "<div class='clearfix mt5 text-truncate'><i data-feather='grid' class='icon-14 text-off mr5'></i> " . $task->project_title . "</div>";
    }

    $client_name = "";
    if (in_array("client_name", $show_in_kanban_items) && $task->project_type == "client_project") {
        $client_name = "<div class='clearfix mt5 text-truncate'><i data-feather='briefcase' class='icon-14 text-off mr5'></i> " . $task->client_name . "</div>";
    }

    $sub_task_status = "";
    $sub_task_label_color = "#6690F4";

    if ($task->total_sub_tasks_done <= 0) {
        $sub_task_label_color = "#E18A00";
    } else if ($task->total_sub_tasks_done == $task->total_sub_tasks) {
        $sub_task_label_color = "#01B392";
    }

    if ($task->total_sub_tasks) {
        $sub_task_status .= "<div class='meta float-start badge rounded-pill' style='background-color:$sub_task_label_color'><span data-bs-toggle='tooltip' title='" . app_lang("sub_task_status") . "'><i data-feather='git-merge' class='icon-14'></i> " . ($task->total_sub_tasks_done ? $task->total_sub_tasks_done : 0) . "/$task->total_sub_tasks</span></div>";
    }

    $parent_task = "";
    if (in_array("parent_task", $show_in_kanban_items) && $task->parent_task_title) {
        $parent_task = "<div class='mt5 text-truncate text-off'>" . $parent_task_id . $task->parent_task_title . "</div>";
    }

    $last_comment_files = array();
    $last_comment_files_json = "";
    if (isset($task->last_comment_files) && $task->last_comment_files) {
        $parsed_files = @unserialize($task->last_comment_files);
        if ($parsed_files && is_array($parsed_files)) {
            $last_comment_files = $parsed_files;
            $encoded_files = json_encode($parsed_files);
            if ($encoded_files !== false) {
                $last_comment_files_json = htmlspecialchars($encoded_files, ENT_QUOTES, 'UTF-8');
            }
        }
    }

    $all_comment_files_array = array();
    if (isset($task->all_comment_files_array) && is_array($task->all_comment_files_array)) {
        $all_comment_files_array = $task->all_comment_files_array;
    } else if (isset($task->all_comment_files) && $task->all_comment_files) {
        $decoded_all_files = @json_decode($task->all_comment_files, true);
        if ($decoded_all_files && is_array($decoded_all_files)) {
            $all_comment_files_array = $decoded_all_files;
        }
    }

    $all_comment_files_json = "";
    if (!empty($all_comment_files_array)) {
        $encoded_all_files = isset($task->all_comment_files) && $task->all_comment_files ? $task->all_comment_files : json_encode($all_comment_files_array);
        if ($encoded_all_files !== false) {
            $all_comment_files_json = htmlspecialchars($encoded_all_files, ENT_QUOTES, 'UTF-8');
        }
    }

    $preview_files_array = !empty($all_comment_files_array) ? $all_comment_files_array : $last_comment_files;
    $preview_files_json = $all_comment_files_json ? $all_comment_files_json : $last_comment_files_json;

    $last_comment_html = "";
    if (isset($task->last_comment_description) && $task->last_comment_description) {
        $comment_text = strip_tags($task->last_comment_description);
        $comment_text = mb_substr($comment_text, 0, 80) . (mb_strlen($comment_text) > 80 ? "..." : "");
        
        $comment_images_html = "";
        if ($last_comment_files && $last_comment_files_json !== "") {
            $timeline_file_path = get_setting("timeline_file_path");
            $image_files_data = array();

            foreach ($last_comment_files as $index => $file) {
                if (!isset($file['file_name'])) {
                    continue;
                }

                $file_name = $file['file_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (!in_array($file_ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
                    continue;
                }

                $file_url = get_source_url_of_file($file, $timeline_file_path);
                $image_files_data[] = array(
                    "url" => $file_url,
                    "index" => $index,
                    "title" => htmlspecialchars(preg_replace("/^\d+_/", "", $file_name), ENT_QUOTES, 'UTF-8')
                );
            }

            if (!empty($image_files_data)) {
                $primary_image = $image_files_data[0];
                $comment_images_html .= "<div class='mt5 kanban-image-hover-wrapper' data-task-id='$task->id' data-all-files='" . $preview_files_json . "'>";
                $comment_images_html .= "<img src='" . $primary_image["url"] . "' class='kanban-comment-image' data-task-id='$task->id' data-all-files='" . $preview_files_json . "' data-file-index='" . $primary_image["index"] . "' style='width: 274px; height: 240px; object-fit: cover; border-radius: 3px;' alt='" . $primary_image["title"] . "' />";
                $comment_images_html .= "</div>";
            }
        }
        
        if ($comment_text || $comment_images_html) {
            $last_comment_html = "<div class='mt5 pt5' style='border-top: 1px solid #e8e8e8;'>";
            // $last_comment_html .= "<div class='font-11 text-muted mb5'><i data-feather='message-circle' class='icon-12'></i> " . app_lang('last_comment') . "</div>";
            $last_comment_html .= $comment_images_html;
            if ($comment_text) {
                $last_comment_html .= "<div class='font-12 mt5'>" . $comment_text . "</div>";
            }
            $last_comment_html .= "</div>";
        }
    }

    $view_images_button = "";
    if ($preview_files_array) {
        $total_images = 0;
        foreach ($preview_files_array as $file) {
            if (isset($file['file_name'])) {
                $file_ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
                if (in_array($file_ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
                    $total_images++;
                }
            }
        }
        
        if ($total_images > 0 && $preview_files_json !== "") {
            $view_images_button = "<div class='mt5'><button class='btn btn-sm btn-outline-primary view-images-btn' data-task-id='$task->id' data-all-files='" . $preview_files_json . "'><i data-feather='image' class='icon-14 mr5'></i>" . app_lang('view_images') . " ($total_images)</button></div>";
        }
    }

    echo modal_anchor(get_uri("tasks/view"), "<span class='avatar'>" .
        "<img src='" . get_avatar($task->assigned_to_avatar) . "'>" .
        "</span>" . $sub_task_icon . $task_id . $task->title . $toggle_sub_task_icon . $last_comment_html . $view_images_button . "<div class='clearfix'>" . $start_date . $end_date . "</div>" . $project_name . $client_name . $kanban_custom_fields_data .
        $task_labels . $task_checklist_status . $sub_task_status . "<div class='clearfix'></div>" . $parent_task, array("class" => "kanban-item d-block $disable_dragging $unread_comments_class", "data-status_id" => $task->status_id, "data-id" => $task->id, "data-project_id" => $task->project_id, "data-sort" => $task->new_sort, "data-post-id" => $task->id, "title" => app_lang('task_info') . " #$task->id", "data-modal-lg" => "1"));
}

?>

<style>
.kanban-item {
    background: white !important;
    border: 1px solid #e0e0e0 !important;
    border-radius: 8px !important;
    margin-bottom: 10px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    transition: box-shadow 0.2s ease !important;
    padding: 12px !important;
}

.kanban-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
}

.view-images-btn {
    z-index: 10;
    position: relative;
}

.view-images-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.kanban-comment-image {
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.kanban-comment-image:hover {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
}

.kanban-image-hover-wrapper {
    position: relative;
    display: block;
}

.kanban-hover-preview {
    position: absolute;
    width: 260px;
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 10px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.2);
    padding: 14px;
    z-index: 2200;
    opacity: 0;
    transform: translateY(6px);
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.kanban-hover-preview.visible {
    opacity: 1;
    transform: translateY(0);
}

.kanban-hover-preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 6px;
}

.kanban-hover-preview-title {
    display: flex;
    align-items: center;
    gap: 6px;
}

.kanban-hover-preview-meta {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 10px;
}

.kanban-hover-close {
    font-size: 18px;
    line-height: 1;
    color: #94a3b8;
    pointer-events: none;
}

.kanban-hover-preview-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
}

.kanban-hover-image {
    width: 100%;
    height: 54px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.kanban-hover-image:hover {
    transform: translateY(-1px);
    border-color: #4f46e5;
    box-shadow: 0 6px 12px rgba(79, 70, 229, 0.18);
}

.kanban-hover-preview-more {
    margin-top: 10px;
    font-size: 12px;
    color: #4f46e5;
    text-align: right;
}
</style>

<script type="text/javascript">
$(document).ready(function() {
    var timelineFilePath = "<?php echo get_setting('timeline_file_path'); ?>";
    var baseUrl = "<?php echo base_url(); ?>";
    var hoverPreviewTitle = "<?php echo addslashes(app_lang('view_images')); ?>";
    var hoverPreviewSubtitle = "<?php echo addslashes(app_lang('last_comment')); ?>";
    var hoverPreviewMoreText = "<?php echo addslashes(app_lang('more')); ?>";
    var hoverPreviewHideTimeout = null;

    if (!window.kanbanImagesHandlerBound) {
        window.kanbanImagesHandlerBound = true;

        document.addEventListener('click', function(event) {
            var trigger = event.target.closest('.view-images-btn, .kanban-comment-image, .kanban-hover-image');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            if ($('.simple-timeline-modal').length > 0) {
                return false;
            }

            var $trigger = $(trigger);
            var allFilesAttr = $trigger.attr('data-all-files');
            if (!allFilesAttr) {
                return false;
            }

            var allFiles;
            try {
                allFiles = JSON.parse(allFilesAttr);
            } catch (error) {
                allFiles = [];
            }

            if (!allFiles.length) {
                return false;
            }

            var isImageClick = $trigger.hasClass('kanban-comment-image') || $trigger.hasClass('kanban-hover-image');
            var originalIndex = isImageClick ? parseInt($trigger.attr('data-file-index'), 10) : null;
            if (isImageClick && isNaN(originalIndex)) {
                originalIndex = null;
            }

            var imageFiles = extractImageFiles(allFiles);
            if (!imageFiles.length) {
                return false;
            }

            var startingImageIndex = 0;
            if (isImageClick && originalIndex !== null) {
                imageFiles.forEach(function(image, idx) {
                    if (image.originalIndex === originalIndex) {
                        startingImageIndex = idx;
                    }
                });
            }

            removeKanbanHoverPreview();
            createKanbanImageModal(imageFiles, isImageClick ? startingImageIndex : 0);
            return false;
        }, true);

        $(document)
            .off('mouseenter.hoverPreviewWrapper', '.kanban-image-hover-wrapper')
            .on('mouseenter.hoverPreviewWrapper', '.kanban-image-hover-wrapper', function() {
                clearTimeout(hoverPreviewHideTimeout);
                var $wrapper = $(this);
                var allFilesAttr = $wrapper.attr('data-all-files');
                if (!allFilesAttr) {
                    removeKanbanHoverPreview();
                    return;
                }

                var allFiles;
                try {
                    allFiles = JSON.parse(allFilesAttr);
                } catch (error) {
                    allFiles = [];
                }

                var imageFiles = extractImageFiles(allFiles);
                if (!imageFiles.length) {
                    removeKanbanHoverPreview();
                    return;
                }

                showKanbanHoverPreview($wrapper, imageFiles, allFilesAttr);
            })
            .off('mouseleave.hoverPreviewWrapper', '.kanban-image-hover-wrapper')
            .on('mouseleave.hoverPreviewWrapper', '.kanban-image-hover-wrapper', function() {
                scheduleHoverPreviewRemoval(150);
            });

        $(document)
            .off('mouseenter.hoverPreviewFloating', '#kanban-hover-preview')
            .on('mouseenter.hoverPreviewFloating', '#kanban-hover-preview', function() {
                clearTimeout(hoverPreviewHideTimeout);
            })
            .off('mouseleave.hoverPreviewFloating', '#kanban-hover-preview')
            .on('mouseleave.hoverPreviewFloating', '#kanban-hover-preview', function() {
                scheduleHoverPreviewRemoval(150);
            });

        $(window)
            .off('scroll.kanbanHoverPreview resize.kanbanHoverPreview')
            .on('scroll.kanbanHoverPreview resize.kanbanHoverPreview', function() {
                removeKanbanHoverPreview();
            });
    }

    $(document)
        .off('mouseenter.hoverKanbanImage', '.kanban-comment-image')
        .on('mouseenter.hoverKanbanImage', '.kanban-comment-image', function() {
            var $img = $(this);
            console.log('Kanban image hover enter', {
                taskId: $img.attr('data-task-id'),
                fileIndex: $img.attr('data-file-index')
            });
        })
        .off('mouseleave.hoverKanbanImage', '.kanban-comment-image')
        .on('mouseleave.hoverKanbanImage', '.kanban-comment-image', function() {
            var $img = $(this);
            console.log('Kanban image hover leave', {
                taskId: $img.attr('data-task-id'),
                fileIndex: $img.attr('data-file-index')
            });
        });

    function isViewableImageFile(fileName) {
        var imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        var extension = fileName.split('.').pop().toLowerCase();
        return imageExtensions.includes(extension);
    }

    function getSourceUrlOfFile(file) {
        if (file.file_id && file.service_type == "google") {
            return baseUrl + 'uploader/stream_google_drive_file/' + file.file_id + '/' + removeFilePrefix(file.file_name);
        }
        return baseUrl + timelineFilePath + file.file_name;
    }

    function removeFilePrefix(fileName) {
        return fileName.replace(/^\d+_/, '');
    }

    function extractImageFiles(allFiles) {
        if (!Array.isArray(allFiles)) {
            return [];
        }
        var imageFiles = [];
        allFiles.forEach(function(file, index) {
            if (!file || !file.file_name) {
                return;
            }
            if (!isViewableImageFile(file.file_name)) {
                return;
            }
            imageFiles.push({
                url: getSourceUrlOfFile(file),
                title: removeFilePrefix(file.file_name),
                originalIndex: index
            });
        });
        return imageFiles;
    }

    function scheduleHoverPreviewRemoval(delay) {
        clearTimeout(hoverPreviewHideTimeout);
        hoverPreviewHideTimeout = setTimeout(removeKanbanHoverPreview, delay || 0);
    }

    function removeKanbanHoverPreview() {
        clearTimeout(hoverPreviewHideTimeout);
        hoverPreviewHideTimeout = null;
        $('#kanban-hover-preview').remove();
    }

    function showKanbanHoverPreview($wrapper, imageFiles, allFilesAttr) {
        removeKanbanHoverPreview();

        var taskId = $wrapper.attr('data-task-id') || '';
        var maxThumbs = Math.min(imageFiles.length, 8);
        var moreLabel = hoverPreviewMoreText && hoverPreviewMoreText.length ? hoverPreviewMoreText : 'more';

        var $preview = $('<div>', {
            id: 'kanban-hover-preview',
            class: 'kanban-hover-preview',
            'data-task-id': taskId,
            'data-all-files': allFilesAttr
        });

        var $header = $('<div>', { class: 'kanban-hover-preview-header' });
        $header.append($('<div>', { class: 'kanban-hover-preview-title', text: hoverPreviewTitle }));
        $header.append($('<span>', { class: 'kanban-hover-close', 'aria-hidden': 'true', text: '×' }));

        var $meta = $('<div>', {
            class: 'kanban-hover-preview-meta',
            text: hoverPreviewSubtitle + ' · ' + imageFiles.length
        });

        var $grid = $('<div>', { class: 'kanban-hover-preview-grid' });

        for (var i = 0; i < maxThumbs; i++) {
            var image = imageFiles[i];
            $grid.append($('<img>', {
                src: image.url,
                class: 'kanban-hover-image',
                alt: image.title,
                'data-task-id': taskId,
                'data-all-files': allFilesAttr,
                'data-file-index': image.originalIndex
            }));
        }

        $preview.append($header);
        $preview.append($meta);
        $preview.append($grid);

        if (imageFiles.length > maxThumbs) {
            $preview.append($('<div>', {
                class: 'kanban-hover-preview-more',
                text: '+' + (imageFiles.length - maxThumbs) + ' ' + moreLabel
            }));
        }

        $('body').append($preview);

        var rect = $wrapper[0].getBoundingClientRect();
        var previewWidth = $preview.outerWidth();
        var previewHeight = $preview.outerHeight();
        var left = rect.right + 12 + window.scrollX;
        var top = rect.top + window.scrollY;
        var viewportRight = window.scrollX + window.innerWidth;

        if (left + previewWidth > viewportRight - 12) {
            left = rect.left + window.scrollX - previewWidth - 12;
        }

        var viewportBottom = window.scrollY + window.innerHeight;
        if (top + previewHeight > viewportBottom - 12) {
            top = Math.max(window.scrollY + 12, viewportBottom - previewHeight - 12);
        }

        $preview.css({ top: top + 'px', left: left + 'px' });

        requestAnimationFrame(function() {
            $preview.addClass('visible');
        });
    }

    function createKanbanImageModal(imageFiles, currentIndex) {
        var hasMultiple = imageFiles.length > 1;
        var currentImage = imageFiles[currentIndex];

        var modalHtml = '<div class="modal fade simple-timeline-modal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">' +
            '<div class="modal-dialog modal-lg" role="document" style="max-width: 90vw; margin: 2vh auto;">' +
            '<div class="modal-content" style="background: rgba(0,0,0,0.95); border: none; border-radius: 8px;">' +
            '<div class="modal-header" style="border: none; padding: 15px; display: flex; justify-content: space-between; align-items: center;">' +
            '<h5 class="modal-title" style="color: white; margin: 0; flex-grow: 1;">' + currentImage.title + '</h5>';

        if (hasMultiple) {
            modalHtml += '<div class="image-counter" style="color: white; margin-right: 15px; font-size: 14px;">' +
                '<span id="current-image-num">' + (currentIndex + 1) + '</span> / ' + imageFiles.length +
                '</div>';
        }

        modalHtml += '<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1; background: none; border: none; font-size: 24px; cursor: pointer; padding: 0;">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '</div>' +
            '<div class="modal-body" style="padding: 0; text-align: center; position: relative;">';

        if (hasMultiple) {
            modalHtml += '<button class="nav-btn prev-btn" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(0,0,0,0.7); color: white; border: none; padding: 15px 20px; border-radius: 50%; cursor: pointer; font-size: 18px;">‹</button>';
        }

        modalHtml += '<img id="modal-image-kanban" src="' + currentImage.url + '" style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 4px; transition: opacity 0.3s;">';

        if (hasMultiple) {
            modalHtml += '<button class="nav-btn next-btn" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(0,0,0,0.7); color: white; border: none; padding: 15px 20px; border-radius: 50%; cursor: pointer; font-size: 18px;">›</button>';
        }

        modalHtml += '</div></div></div></div>';

        var modal = $(modalHtml);
        window.kanbanImages = imageFiles;
        window.kanbanImageIndex = currentIndex;

        $('body').append(modal);
        modal.modal('show');

        modal.find('.prev-btn').on('click', function(e) {
            e.stopPropagation();
            navigate(-1);
        });

        modal.find('.next-btn').on('click', function(e) {
            e.stopPropagation();
            navigate(1);
        });

        modal.find('.close').on('click', function() {
            closeModal();
        });

        $(document).on('keydown.kanban-modal', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            } else if (hasMultiple && $('.simple-timeline-modal').is(':visible')) {
                if (e.key === 'ArrowLeft') {
                    navigate(-1);
                } else if (e.key === 'ArrowRight') {
                    navigate(1);
                }
            }
        });

        modal.on('click', function(e) {
            if (e.target === this || $(e.target).hasClass('modal-body')) {
                closeModal();
            }
        });

        modal.on('hidden.bs.modal', function() {
            $(document).off('keydown.kanban-modal');
            window.kanbanImages = null;
            window.kanbanImageIndex = 0;
            modal.remove();
        });

        function navigate(direction) {
            if (!window.kanbanImages || window.kanbanImages.length <= 1) {
                return;
            }

            window.kanbanImageIndex += direction;

            if (window.kanbanImageIndex < 0) {
                window.kanbanImageIndex = window.kanbanImages.length - 1;
            } else if (window.kanbanImageIndex >= window.kanbanImages.length) {
                window.kanbanImageIndex = 0;
            }

            var nextImage = window.kanbanImages[window.kanbanImageIndex];
            var imgElement = document.getElementById('modal-image-kanban');
            var titleElement = modal.find('.modal-title');
            var counterElement = modal.find('#current-image-num');

            $(imgElement).css('opacity', '0');
            setTimeout(function() {
                imgElement.src = nextImage.url;
                titleElement.text(nextImage.title);
                if (counterElement.length) {
                    counterElement.text(window.kanbanImageIndex + 1);
                }
                $(imgElement).css('opacity', '1');
            }, 150);
        }

        function closeModal() {
            removeKanbanHoverPreview();
            $(document).off('keydown.kanban-modal');
            window.kanbanImages = null;
            window.kanbanImageIndex = 0;
            modal.modal('hide');
        }
    }
});
</script>

