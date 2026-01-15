<div id="file-manager-window-area" class="show-context-menu">
    <?php
    $folder_count = count($folders_list);
    $file_count = $folder_item_type === "file" ? count($folder_items) : 0;
    $has_any_items = $folder_count || $file_count;
    ?>

    <div class="finder-canvas">
        <div class="finder-toolbar">
            <div class="finder-toolbar-info">
                <div class="finder-label"><?php echo app_lang('view_options'); ?></div>
                <div class="finder-helper"><?php echo app_lang('choose_how_to_browse'); ?></div>
            </div>
            <div class="finder-toggle" id="file-view-toggle">
                <button class="view-mode-btn" data-mode="grid">
                    <span class="view-mode-icon">?</span>
                    <span><?php echo app_lang('icon_view'); ?></span>
                </button>
                <button class="view-mode-btn" data-mode="list">
                    <span class="view-mode-icon">?</span>
                    <span><?php echo app_lang('list_view'); ?></span>
                </button>
            </div>
        </div>

        <?php if (!$has_any_items) { ?>
            <div class="finder-empty">
                <div class="finder-empty-card">
                    <div class="finder-empty-icon">?</div>
                    <div class="finder-empty-title"><?php echo app_lang('no_record_found'); ?></div>
                    <div class="finder-empty-text"><?php echo app_lang('use_actions_to_get_started'); ?></div>
                </div>
            </div>
        <?php } else { ?>
            <div class="files-and-folders-list" data-has_write_permission="<?php echo $has_write_permission; ?>" data-has_upload_permission="<?php echo $has_upload_permission; ?>">

                <div id="finder-grid-view" class="finder-grid-view">
                <?php if ($folder_count) { ?>
                    <section class="finder-section">
                <div class="finder-section-header">
                            <div>
                                <div class="finder-section-title"><?php echo app_lang('folders'); ?></div>
                                <div class="finder-section-helper"><?php echo app_lang('quickly_find_folders'); ?></div>
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
                                    <div class="folder-item-content show-context-menu finder-card finder-card-folder">
                                        <div class="finder-card-top">
                                            <div class="finder-card-icon">?</div>
                                            <div class="finder-card-title">
                                                <div class="finder-card-name folder-name"><?php echo $folder->title; ?></div>
                                                <div class="finder-card-meta"><?php echo app_lang('folder'); ?> · <?php echo app_lang('click_to_open'); ?></div>
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
                                <div class="finder-section-helper"><?php echo app_lang('quickly_find_files'); ?></div>
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
                                        <div class="folder-item-content show-context-menu finder-card finder-card-file">
                                            <div class="finder-card-top">
                                                <div class="finder-card-icon">?</div>
                                                <div class="finder-card-title">
                                                    <div class="finder-card-name file-name"><?php echo js_anchor($file_name, $preview_link_attr); ?></div>
                                                    <div class="finder-card-meta"><?php echo app_lang('file'); ?> · <?php echo app_lang('click_to_preview'); ?></div>
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

            <div id="finder-list-view" class="finder-list-view finder-hidden">
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
                                <div class="folder-item-content show-context-menu finder-list-row">
                                    <div class="finder-list-name">
                                        <span class="finder-list-icon">?</span>
                                        <div>
                                            <div class="folder-name"><?php echo $folder->title; ?></div>
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
                                        echo implode(" · ", $details);
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
                                    <div class="folder-item-content show-context-menu finder-list-row">
                                        <div class="finder-list-name">
                                            <span class="finder-list-icon">?</span>
                                            <div>
                                                <div class="file-name"><?php echo js_anchor($file_name, $preview_link_attr); ?></div>
                                                <small><?php echo app_lang('click_to_preview'); ?></small>
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

<style>
    /* Scope all styles to the window area only, not the details panel */
    #file-manager-window-area .finder-canvas {
        padding: 0;
    }

    #file-manager-window-area .finder-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 20px;
        gap: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    #file-manager-window-area .finder-label {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #6b7280;
    }

    #file-manager-window-area .finder-helper {
        font-size: 14px;
        color: #374151;
        margin-top: 2px;
        font-weight: 500;
    }

    .finder-canvas .finder-toggle {
        display: inline-flex;
        background: #f3f4f6;
        border-radius: 6px;
        padding: 3px;
        gap: 4px;
    }

    .finder-canvas .view-mode-btn {
        border: none;
        background: transparent;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .finder-canvas .view-mode-btn:hover {
        color: #374151;
    }

    .finder-canvas .view-mode-btn.active {
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        color: #111827;
    }

    .finder-canvas .finder-empty {
        padding: 80px 20px;
    }

    .finder-canvas .finder-empty-card {
        max-width: 400px;
        margin: 0 auto;
        text-align: center;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 48px 32px;
    }

    .finder-canvas .finder-empty-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .finder-canvas .finder-empty-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #111827;
    }

    .finder-canvas .finder-empty-text {
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
    }

    .finder-canvas .finder-section {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .finder-canvas .finder-section:last-child {
        margin-bottom: 0;
    }

    .finder-canvas .finder-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f3f4f6;
    }

    .finder-canvas .finder-section-title {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        font-weight: 600;
    }

    .finder-canvas .finder-section-helper {
        font-size: 13px;
        color: #9ca3af;
        margin-top: 2px;
    }

    .finder-canvas .finder-pill {
        min-width: 28px;
        height: 28px;
        border-radius: 14px;
        background: #f3f4f6;
        color: #374151;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 12px;
        padding: 0 8px;
    }

    .finder-canvas .finder-card-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .finder-canvas .finder-list-body {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .finder-canvas .folder-item {
        position: relative;
        list-style: none;
    }

    .finder-canvas .folder-item .file-manager-more-menu {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 6px;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s ease;
        z-index: 10;
    }

    .finder-canvas .folder-item:hover .file-manager-more-menu {
        opacity: 1;
    }

    .finder-canvas .finder-card {
        border-radius: 10px;
        padding: 16px;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
    }

    .finder-canvas .finder-card-folder {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #ffffff;
        border: 1px solid #334155;
    }

    .finder-canvas .finder-card-file {
        background: #ffffff;
        color: #111827;
        border: 1px solid #e5e7eb;
    }

    .finder-canvas .finder-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .finder-canvas .finder-card-folder:hover {
        box-shadow: 0 4px 16px rgba(30, 41, 59, 0.3);
    }

    .finder-canvas .finder-card-top {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .finder-canvas .finder-card-icon {
        font-size: 28px;
        line-height: 1;
        flex-shrink: 0;
    }

    .finder-canvas .finder-card-title {
        flex: 1;
        min-width: 0;
    }

    .finder-canvas .finder-card-name {
        font-size: 15px;
        font-weight: 600;
        line-height: 1.4;
        margin-bottom: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .finder-canvas .finder-card-name a {
        color: inherit;
        text-decoration: none;
    }

    .finder-canvas .finder-card-name a:hover {
        text-decoration: underline;
    }

    .finder-canvas .finder-card-meta {
        font-size: 11px;
        opacity: 0.7;
    }

    .finder-canvas .finder-card-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .finder-canvas .finder-badge {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 4px;
        padding: 3px 8px;
        background: rgba(255, 255, 255, 0.15);
        font-weight: 500;
    }

    .finder-canvas .finder-card-file .finder-badge {
        background: #f3f4f6;
        color: #6b7280;
    }

    .finder-canvas .finder-card-details {
        font-size: 12px;
        color: #6b7280;
        margin-top: 8px;
    }

    .finder-canvas .finder-hidden {
        display: none !important;
    }

    .finder-canvas .finder-list-view {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .finder-canvas .finder-list-head,
    .finder-canvas .finder-list-row {
        display: grid;
        grid-template-columns: 2fr 1.2fr 0.8fr 0.8fr 40px;
        gap: 12px;
        align-items: center;
        padding: 12px 16px;
    }

    .finder-canvas .finder-list-head {
        background: #f9fafb;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.5px;
        color: #6b7280;
        font-weight: 600;
        border-bottom: 1px solid #e5e7eb;
    }

    .finder-canvas .finder-list-row {
        border-top: 1px solid #f3f4f6;
        transition: background 0.15s ease;
        cursor: pointer;
    }

    .finder-canvas .finder-list-row:first-child {
        border-top: none;
    }

    .finder-canvas .finder-list-name {
        display: flex;
        gap: 12px;
        align-items: center;
        min-width: 0;
    }

    .finder-canvas .finder-list-icon {
        font-size: 20px;
        flex-shrink: 0;
    }

    .finder-canvas .finder-list-name > div {
        min-width: 0;
        flex: 1;
    }

    .finder-canvas .finder-list-name .folder-name,
    .finder-canvas .finder-list-name .file-name {
        font-size: 14px;
        font-weight: 500;
        color: #111827;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .finder-canvas .finder-list-name .file-name a {
        color: inherit;
        text-decoration: none;
    }

    .finder-canvas .finder-list-name .file-name a:hover {
        color: #2563eb;
    }

    .finder-canvas .finder-list-row small {
        color: #9ca3af;
        font-size: 12px;
        display: block;
        margin-top: 2px;
    }

    .finder-canvas .finder-list-details,
    .finder-canvas .finder-list-size,
    .finder-canvas .finder-list-type {
        font-size: 13px;
        color: #6b7280;
    }

    .finder-canvas .finder-list-row:hover {
        background: #f9fafb;
    }

    .finder-canvas .finder-list-row .file-manager-more-menu {
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .finder-canvas .finder-list-row:hover .file-manager-more-menu {
        opacity: 1;
    }

    .finder-hidden {
        display: none !important;
    }

    /* Modal and positioning fixes */
    #file-manager-window-area {
        position: relative;
        z-index: 1;
        overflow: visible;
        min-height: 400px;
    }

    /* Fix for modal overlays */
    #file-manager-window-area .finder-canvas {
        position: relative;
        z-index: auto;
        transform: translateZ(0); /* Create stacking context */
    }

    /* Ensure file manager doesn't interfere with modals */
    .app-modal {
        z-index: 9999 !important;
    }

    .app-modal .modal-backdrop {
        z-index: 9998 !important;
    }

    /* Fix context menu positioning */
    #folder-context-menu {
        z-index: 10000 !important;
        position: fixed !important;
    }

    /* Prevent scroll issues in modals */
    .modal-open #file-manager-window-area {
        transform: none;
        filter: none;
    }

    @media (max-width: 1024px) {
        .finder-canvas .finder-card-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .finder-canvas .finder-toolbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .finder-canvas .finder-card-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }

        .finder-canvas .finder-list-head,
        .finder-canvas .finder-list-row {
            grid-template-columns: 1fr 40px;
            gap: 8px;
        }

        .finder-canvas .finder-list-head {
            display: none;
        }

        .finder-canvas .finder-list-details,
        .finder-canvas .finder-list-size,
        .finder-canvas .finder-list-type {
            display: none;
        }
    }
</style>

<script>
    (function() {
        // Enhanced view toggle functionality
        const toggleButtons = document.querySelectorAll("#file-view-toggle .view-mode-btn");
        const gridView = document.getElementById("finder-grid-view");
        const listView = document.getElementById("finder-list-view");
        const storageKey = "fileManagerViewMode";
        let currentMode = localStorage.getItem(storageKey) || "grid";

        function setMode(mode) {
            currentMode = mode;
            localStorage.setItem(storageKey, mode);

            // Update view visibility
            if (mode === "grid") {
                if (gridView) gridView.classList.remove("finder-hidden");
                if (listView) listView.classList.add("finder-hidden");
            } else {
                if (listView) listView.classList.remove("finder-hidden");
                if (gridView) gridView.classList.add("finder-hidden");
            }

            // Update button states
            toggleButtons.forEach(btn => {
                if (btn.getAttribute("data-mode") === mode) {
                    btn.classList.add("active");
                } else {
                    btn.classList.remove("active");
                }
            });
        }

        // Add event listeners
        toggleButtons.forEach(button => {
            button.addEventListener("click", function(e) {
                e.preventDefault();
                setMode(this.getAttribute("data-mode"));
            });
        });

        // Initialize view mode
        setMode(currentMode);

        // Fix modal issues - prevent event bubbling conflicts
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure modals work properly with file manager
            const fileManagerContainer = document.getElementById('file-manager-window-area');
            if (fileManagerContainer) {
                fileManagerContainer.addEventListener('click', function(e) {
                    // Prevent clicks on file manager from interfering with modals
                    if (document.querySelector('.modal.show')) {
                        return;
                    }
                });
            }

            // Fix z-index conflicts
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        // Check for modal being added
                        const addedNodes = Array.from(mutation.addedNodes);
                        addedNodes.forEach(function(node) {
                            if (node.classList && node.classList.contains('modal')) {
                                // Ensure modal is on top
                                node.style.zIndex = '9999';
                                const backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) {
                                    backdrop.style.zIndex = '9998';
                                }
                            }
                        });
                    }
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        });

    })();
</script>
