<?php
$folder_count = count($folders_list);
$file_count = $folder_item_type === "file" ? count($folder_items) : 0;
$has_any_items = $folder_count || $file_count;

$lang_or = function ($key, $fallback) {
    $value = app_lang($key);
    if ($value === "default_lang.$key" || $value === "custom_lang.$key") {
        return $fallback;
    }
    return $value;
};

$view_options_text = $lang_or("view_options", "View options");
$choose_how_to_browse_text = $lang_or("choose_how_to_browse", "Choose how to browse");
$icon_view_text = $lang_or("icon_view", "Icon view");
$list_view_text = $lang_or("list_view", "List view");
$quickly_find_folders_text = $lang_or("quickly_find_folders", "Quickly find folders in this location.");
$quickly_find_files_text = $lang_or("quickly_find_files", "Quickly preview files stored in this location.");
$use_actions_to_get_started_text = $lang_or("use_actions_to_get_started", "Use New folder or Add files to get started.");
$click_to_open_text = $lang_or("click_to_open", "Click to open");
$click_to_preview_text = $lang_or("click_to_preview", "Click to preview");
?>

<div id="file-manager-detail-area" class="file-manager-pane">
    <div class="finder-canvas">
        <div class="finder-toolbar">
            <div class="finder-toolbar-info">
                <div class="finder-label"><?php echo $view_options_text; ?></div>
                <div class="finder-helper"><?php echo $choose_how_to_browse_text; ?></div>
            </div>
            <div class="finder-toggle" id="file-view-toggle-detail">
                <button class="view-mode-btn" data-mode="grid">
                    <span class="view-mode-icon"><i data-feather="grid" class="icon-14"></i></span>
                    <span><?php echo $icon_view_text; ?></span>
                </button>
                <button class="view-mode-btn" data-mode="list">
                    <span class="view-mode-icon"><i data-feather="list" class="icon-14"></i></span>
                    <span><?php echo $list_view_text; ?></span>
                </button>
            </div>
        </div>

        <?php if (!$has_any_items) { ?>
            <div class="finder-empty">
                <div class="finder-empty-card">
                    <div class="finder-empty-icon"><i data-feather="inbox"></i></div>
                    <div class="finder-empty-title"><?php echo app_lang('no_record_found'); ?></div>
                    <div class="finder-empty-text"><?php echo $use_actions_to_get_started_text; ?></div>
                </div>
            </div>
        <?php } else { ?>
            <div class="files-and-folders-list" data-has_write_permission="<?php echo $has_write_permission; ?>" data-has_upload_permission="<?php echo $has_upload_permission; ?>">

                <div id="finder-grid-view-detail" class="finder-grid-view">
                    <?php if ($folder_count) { ?>
                        <section class="finder-section">
                            <div class="finder-section-header">
                                <div>
                                    <div class="finder-section-title"><?php echo app_lang('folders'); ?></div>
                                    <div class="finder-section-helper"><?php echo $quickly_find_folders_text; ?></div>
                                </div>
                                <span class="finder-pill"><?php echo $folder_count; ?></span>
                            </div>
                            <div class="finder-card-grid">
                                <?php
                                foreach ($folders_list as $folder) {
                                    $is_favourite = strpos($folder->starred_by, ":" . $login_user->id . ":") ? 1 : '';
                                    $has_this_folder_write_permission = false;

                                    if ($login_user->is_admin || ($folder->context == "file_manager" && $folder->actual_permission_rank >= 6) || ($folder->context != "file_manager" && $login_user->user_type == "staff")) {
                                        $has_this_folder_write_permission = true;
                                    }
                                    ?>
                                    <li class="folder-item" data-id="<?php echo $folder->id; ?>" data-folder_id="<?php echo $folder->folder_id; ?>" data-type="folder" data-is_favourite="<?php echo $is_favourite; ?>" data-has_this_folder_write_permission="<?php echo $has_this_folder_write_permission; ?>">
                                        <div class="folder-item-content show-context-menu finder-card finder-card-folder folder-thumb-area">
                                            <div class="finder-card-top">
                                                <div class="finder-card-icon"><i data-feather="folder"></i></div>
                                                <div class="finder-card-title">
                                                    <div class="finder-card-name folder-name item-name"><?php echo $folder->title; ?></div>
                                                    <div class="finder-card-meta"><?php echo app_lang('folder'); ?> &middot; <?php echo $click_to_open_text; ?></div>
                                                </div>
                                            </div>
                                            <div class="finder-card-badges">
                                                <?php
                                                if ($folder->subfolder_count) {
                                                    $label = $folder->subfolder_count > 1 ? app_lang("folders") : app_lang("folder");
                                                    echo "<span class='finder-badge'>" . $folder->subfolder_count . " " . $label . "</span>";
                                                }
                                                if ($folder->subfile_count) {
                                                    $label = $folder->subfile_count > 1 ? app_lang("files") : app_lang("file");
                                                    echo "<span class='finder-badge'>" . $folder->subfile_count . " " . $label . "</span>";
                                                }
                                                if (!$folder->subfolder_count && !$folder->subfile_count) {
                                                    echo "<span class='finder-badge'>" . app_lang('empty') . "</span>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <span class="file-manager-more-menu">
                                            <i data-feather="more-horizontal" class="icon-18"></i>
                                        </span>
                                    </li>
                                <?php } ?>
                            </div>
                        </section>
                    <?php } ?>

                    <?php if ($file_count) { ?>
                        <section class="finder-section">
                            <div class="finder-section-header">
                                <div>
                                    <div class="finder-section-title"><?php echo app_lang('files'); ?></div>
                                    <div class="finder-section-helper"><?php echo $quickly_find_files_text; ?></div>
                                </div>
                                <span class="finder-pill"><?php echo $file_count; ?></span>
                            </div>
                            <div class="finder-card-grid">
                                <?php foreach ($folder_items as $folder_item) {
                                    if ($folder_item_type == "file") {
                                        $file_name = short_file_name(remove_file_prefix($folder_item->file_name));
                                        $file_size = convert_file_size($folder_item->file_size);

                                        $preview_link_attr = $file_preview_link_attributes;

                                        $data_url = $file_preview_url . "/" . $folder_item->id;
                                        if ($client_id) {
                                            $data_url .= "/" . $client_id;
                                        }

                                        $preview_link_attr["data-url"] = $data_url;
                                        $preview_link_attr["data-preview_function"] = "showFilePreviewAppModal";
                                        $preview_link_attr["data-group"] = "window_files";
                                        ?>
                                        <li class="folder-item" data-id="<?php echo $folder_item->id; ?>" data-type="file">
                                            <div class="folder-item-content show-context-menu finder-card finder-card-file file-thumb-area">
                                                <div class="finder-card-top">
                                                    <div class="finder-card-icon"><i data-feather="file-text"></i></div>
                                                    <div class="finder-card-title">
                                                        <div class="finder-card-name file-name item-name"><?php echo js_anchor($file_name, $preview_link_attr); ?></div>
                                                        <div class="finder-card-meta"><?php echo app_lang('file'); ?> &middot; <?php echo $click_to_preview_text; ?></div>
                                                    </div>
                                                </div>
                                                <div class="finder-card-details"><?php echo $file_size; ?></div>
                                            </div>
                                            <span class="file-manager-more-menu">
                                                <i data-feather="more-horizontal" class="icon-18"></i>
                                            </span>
                                        </li>
                                    <?php
                                    }
                                } ?>
                            </div>
                        </section>
                    <?php } ?>
                </div>

                <div id="finder-list-view-detail" class="finder-list-view finder-hidden">
                    <div class="finder-list-head">
                        <div><?php echo app_lang('name'); ?></div>
                        <div><?php echo app_lang('details'); ?></div>
                        <div><?php echo app_lang('size'); ?></div>
                        <div><?php echo app_lang('type'); ?></div>
                    </div>
                    <div class="finder-list-body">
                        <?php if ($folder_count) { ?>
                            <?php foreach ($folders_list as $folder) {
                                $is_favourite = strpos($folder->starred_by, ":" . $login_user->id . ":") ? 1 : '';
                                $has_this_folder_write_permission = false;

                                if ($login_user->is_admin || ($folder->context == "file_manager" && $folder->actual_permission_rank >= 6) || ($folder->context != "file_manager" && $login_user->user_type == "staff")) {
                                    $has_this_folder_write_permission = true;
                                }
                                ?>
                                <li class="folder-item" data-id="<?php echo $folder->id; ?>" data-folder_id="<?php echo $folder->folder_id; ?>" data-type="folder" data-is_favourite="<?php echo $is_favourite; ?>" data-has_this_folder_write_permission="<?php echo $has_this_folder_write_permission; ?>">
                                    <div class="folder-item-content show-context-menu finder-list-row folder-thumb-area">
                                        <div class="finder-list-name">
                                            <span class="finder-list-icon"><i data-feather="folder"></i></span>
                                            <div>
                                                <div class="folder-name item-name"><?php echo $folder->title; ?></div>
                                                <small><?php echo app_lang('folder'); ?></small>
                                            </div>
                                        </div>
                                        <div class="finder-list-details">
                                            <?php
                                            $details = array();
                                            if ($folder->subfolder_count) {
                                                $details[] = $folder->subfolder_count . " " . ($folder->subfolder_count > 1 ? app_lang("folders") : app_lang("folder"));
                                            }
                                            if ($folder->subfile_count) {
                                                $details[] = $folder->subfile_count . " " . ($folder->subfile_count > 1 ? app_lang("files") : app_lang("file"));
                                            }
                                            if (!$details) {
                                                $details[] = app_lang('empty');
                                            }
                                            echo implode(" &middot; ", $details);
                                            ?>
                                        </div>
                                        <div class="finder-list-size"><?php echo app_lang('na'); ?></div>
                                        <div class="finder-list-type"><?php echo app_lang('folder'); ?></div>
                                    </div>
                                    <span class="file-manager-more-menu">
                                        <i data-feather="more-horizontal" class="icon-18"></i>
                                    </span>
                                </li>
                            <?php } ?>
                        <?php } ?>

                        <?php if ($file_count) { ?>
                            <?php foreach ($folder_items as $folder_item) {
                                if ($folder_item_type == "file") {
                                    $file_name = short_file_name(remove_file_prefix($folder_item->file_name));
                                    $file_size = convert_file_size($folder_item->file_size);

                                    $preview_link_attr = $file_preview_link_attributes;
                                    $data_url = $file_preview_url . "/" . $folder_item->id;
                                    if ($client_id) {
                                        $data_url .= "/" . $client_id;
                                    }

                                    $preview_link_attr["data-url"] = $data_url;
                                    $preview_link_attr["data-preview_function"] = "showFilePreviewAppModal";
                                    $preview_link_attr["data-group"] = "window_files";
                                    ?>
                                    <li class="folder-item" data-id="<?php echo $folder_item->id; ?>" data-type="file">
                                        <div class="folder-item-content show-context-menu finder-list-row file-thumb-area">
                                            <div class="finder-list-name">
                                                <span class="finder-list-icon"><i data-feather="file-text"></i></span>
                                                <div>
                                                    <div class="file-name item-name"><?php echo js_anchor($file_name, $preview_link_attr); ?></div>
                                                    <small><?php echo $click_to_preview_text; ?></small>
                                                </div>
                                            </div>
                                            <div class="finder-list-details"><?php echo app_lang('file'); ?></div>
                                            <div class="finder-list-size file-size"><?php echo $file_size; ?></div>
                                            <div class="finder-list-type"><?php echo app_lang('file'); ?></div>
                                        </div>
                                        <span class="file-manager-more-menu">
                                            <i data-feather="more-horizontal" class="icon-18"></i>
                                        </span>
                                    </li>
                                <?php
                                }
                            } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<script>
    (function() {
        const pane = document.getElementById("file-manager-detail-area");
        if (!pane) {
            return;
        }

        const toggleButtons = pane.querySelectorAll("#file-view-toggle-detail .view-mode-btn");
        const gridView = pane.querySelector("#finder-grid-view-detail");
        const listView = pane.querySelector("#finder-list-view-detail");
        const storageKey = "fileManagerDetailViewMode";
        let currentMode = localStorage.getItem(storageKey) || "grid";

        function setMode(mode) {
            currentMode = mode;
            localStorage.setItem(storageKey, mode);

            if (mode === "grid") {
                if (gridView) {
                    gridView.classList.remove("finder-hidden");
                }
                if (listView) {
                    listView.classList.add("finder-hidden");
                }
            } else {
                if (listView) {
                    listView.classList.remove("finder-hidden");
                }
                if (gridView) {
                    gridView.classList.add("finder-hidden");
                }
            }

            toggleButtons.forEach(function(btn) {
                if (btn.getAttribute("data-mode") === mode) {
                    btn.classList.add("active");
                } else {
                    btn.classList.remove("active");
                }
            });
        }

        toggleButtons.forEach(function(button) {
            button.addEventListener("click", function(e) {
                e.preventDefault();
                setMode(this.getAttribute("data-mode"));
            });
        });

        setMode(currentMode);

        if (typeof feather !== "undefined") {
            feather.replace();
        }
    })();
</script>
