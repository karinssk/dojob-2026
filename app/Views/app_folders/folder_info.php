<div id="file-manager-window-area" class="show-context-menu">
    <?php
    $folder_count = count($folders_list);
    $is_file_listing = $folder_item_type === "file";
    $file_items = $is_file_listing ? $folder_items : array();
    $file_count = count($file_items);
    $has_any_items = $folder_count || $file_count;
    ?>

    <div class="finder-toolbar flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <p class="text-xs font-semibold tracking-[0.3em] text-slate-500 uppercase"><?php echo app_lang('view_options'); ?></p>
            <p class="text-base text-slate-600 mt-1"><?php echo app_lang('choose_how_to_browse'); ?></p>
        </div>
        <div class="finder-view-toggle inline-flex rounded-full bg-slate-100 p-1" id="file-view-toggle">
            <button class="view-mode-btn flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold text-slate-500 focus:outline-none transition" data-mode="grid">
                <i data-feather="grid" class="w-4 h-4"></i>
                <span><?php echo app_lang('icon_view'); ?></span>
            </button>
            <button class="view-mode-btn flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold text-slate-500 focus:outline-none transition" data-mode="list">
                <i data-feather="list" class="w-4 h-4"></i>
                <span><?php echo app_lang('list_view'); ?></span>
            </button>
        </div>
    </div>

    <?php if (!$has_any_items) { ?>
        <div class="file-browser-empty-state">
            <div class="empty-state-card max-w-xl mx-auto text-center bg-white/90 border border-slate-200 rounded-3xl shadow-sm p-10">
                <i data-feather="inbox" class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-4"></i>
                <div class="empty-state-text text-xl font-semibold text-slate-700"><?php echo app_lang('no_record_found'); ?></div>
                <p class="text-off text-sm mt-2"><?php echo app_lang('use_actions_to_get_started'); ?></p>
            </div>
        </div>
    <?php } else { ?>
        <div class="files-and-folders-list space-y-8" data-has_write_permission="<?php echo $has_write_permission; ?>" data-has_upload_permission="<?php echo $has_upload_permission; ?>">
            <div id="finder-grid-view" class="space-y-8">
                <?php if ($folder_count) { ?>
                    <section class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-amber-500 font-semibold"><?php echo app_lang('folders'); ?></p>
                                <p class="text-sm text-slate-500"><?php echo app_lang('quickly_find_folders'); ?></p>
                            </div>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 text-amber-600 text-sm font-semibold"><?php echo $folder_count; ?></span>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <?php foreach ($folders_list as $folder) {
                                $is_favourite = strpos($folder->starred_by, ":" . $login_user->id . ":") ? 1 : '';
                                $has_this_folder_write_permission = false;

                                if ($login_user->is_admin || ($folder->context == "file_manager" && $folder->actual_permission_rank >= 6) || ($folder->context != "file_manager" && $login_user->user_type == "staff")) {
                                    $has_this_folder_write_permission = true;
                                }
                            ?>
                                <div class="folder-item" data-id="<?php echo $folder->id; ?>" data-folder_id="<?php echo $folder->folder_id; ?>" data-type="folder" data-is_favourite="<?php echo $is_favourite; ?>" data-has_this_folder_write_permission="<?php echo $has_this_folder_write_permission; ?>">
                                    <div class="folder-item-content show-context-menu finder-icon-card rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900/95 text-white p-5 shadow-xl hover:-translate-y-1 transition">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-center gap-3">
                                                <div class="rounded-2xl p-3 bg-gradient-to-br from-sky-400 to-blue-500">
                                                    <i data-feather="folder" class="w-10 h-10"></i>
                                                </div>
                                                <div>
                                                    <div class="folder-name text-lg font-semibold"><?php echo $folder->title; ?></div>
                                                    <div class="flex items-center gap-2 text-[11px] uppercase tracking-wide text-white/70">
                                                        <span class="px-2 py-0.5 rounded-full bg-white/10"><?php echo app_lang('folder'); ?></span>
                                                        <span class="flex items-center gap-1 text-white/60">
                                                            <i data-feather="corner-down-right" class="w-3 h-3"></i>
                                                            <?php echo app_lang('click_to_open'); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($is_favourite) { ?>
                                                <i data-feather="star" class="text-yellow-300"></i>
                                            <?php } ?>
                                        </div>
                                        <div class="flex flex-wrap gap-2 mt-4 text-xs font-semibold">
                                            <?php
                                            if ($folder->subfolder_count) {
                                                $label = $folder->subfolder_count > 1 ? app_lang("folders") : app_lang("folder");
                                                echo "<span class='inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white/10 text-white'>" . $folder->subfolder_count . " " . $label . "</span>";
                                            }
                                            if ($folder->subfile_count) {
                                                $label = $folder->subfile_count > 1 ? app_lang("files") : app_lang("file");
                                                echo "<span class='inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white/10 text-white'>" . $folder->subfile_count . " " . $label . "</span>";
                                            }
                                            if (!$folder->subfolder_count && !$folder->subfile_count) {
                                                echo "<span class='inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white/10 text-white'>" . app_lang('empty') . "</span>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <span class="file-manager-more-menu bg-white border border-slate-200 shadow text-slate-500">
                                        <i data-feather="more-horizontal" class="icon-18"></i>
                                    </span>
                                </div>
                            <?php } ?>
                        </div>
                    </section>
                <?php } ?>

                <?php if ($file_count) { ?>
                    <section class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-blue-500 font-semibold"><?php echo app_lang('files'); ?></p>
                                <p class="text-sm text-slate-500"><?php echo app_lang('quickly_find_files'); ?></p>
                            </div>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-600 text-sm font-semibold"><?php echo $file_count; ?></span>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <?php foreach ($file_items as $folder_item) {
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
                                <div class="folder-item" data-id="<?php echo $folder_item->id; ?>" data-type="file">
                                    <div class="folder-item-content show-context-menu finder-icon-card rounded-2xl border border-slate-200 bg-white text-slate-900 p-5 shadow hover:-translate-y-1 hover:shadow-blue-200 transition">
                                        <div class="flex items-start gap-3">
                                            <div class="rounded-2xl p-3 bg-blue-50 text-blue-500">
                                                <i data-feather="file" class="w-10 h-10"></i>
                                            </div>
                                            <div>
                                                <div class="item-name text-lg font-semibold"><?php echo js_anchor($file_name, $preview_link_attr); ?></div>
                                                <div class="flex items-center gap-2 text-[11px] uppercase tracking-wide text-slate-500">
                                                    <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600"><?php echo app_lang('file'); ?></span>
                                                    <span class="inline-flex items-center gap-1 text-slate-500">
                                                        <i data-feather="eye" class="w-3 h-3"></i>
                                                        <?php echo app_lang('click_to_preview'); ?>
                                                    </span>
                                                </div>
                                                <div class="text-sm text-slate-500 mt-3"><?php echo $file_size; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="file-manager-more-menu bg-white border border-slate-200 shadow text-slate-500">
                                        <i data-feather="more-horizontal" class="icon-18"></i>
                                    </span>
                                </div>
                            <?php } ?>
                        </div>
                    </section>
                <?php } ?>
            </div>

            <div id="finder-list-view" class="hidden rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
                <div class="hidden md:flex items-center px-4 py-3 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200 bg-slate-50">
                    <div class="w-2/5"><?php echo app_lang('name'); ?></div>
                    <div class="w-1/5"><?php echo app_lang('details'); ?></div>
                    <div class="w-1/5"><?php echo app_lang('size'); ?></div>
                    <div class="w-1/5"><?php echo app_lang('type'); ?></div>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php if ($folder_count) { ?>
                        <?php foreach ($folders_list as $folder) {
                            $is_favourite = strpos($folder->starred_by, ":" . $login_user->id . ":") ? 1 : '';
                            $has_this_folder_write_permission = false;

                            if ($login_user->is_admin || ($folder->context == "file_manager" && $folder->actual_permission_rank >= 6) || ($folder->context != "file_manager" && $login_user->user_type == "staff")) {
                                $has_this_folder_write_permission = true;
                            }
                        ?>
                            <div class="folder-item" data-id="<?php echo $folder->id; ?>" data-folder_id="<?php echo $folder->folder_id; ?>" data-type="folder" data-is_favourite="<?php echo $is_favourite; ?>" data-has_this_folder_write_permission="<?php echo $has_this_folder_write_permission; ?>">
                                <div class="folder-item-content show-context-menu flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-3">
                                    <div class="w-full md:w-2/5 flex items-center gap-3">
                                        <i data-feather="chevron-right" class="w-4 h-4 text-slate-400"></i>
                                        <div>
                                            <div class="font-semibold text-sm text-slate-800 folder-name"><?php echo $folder->title; ?></div>
                                            <small class="text-xs text-slate-500 uppercase tracking-wide"><?php echo app_lang('folder'); ?></small>
                                        </div>
                                    </div>
                                    <div class="w-full md:w-1/5 text-xs text-slate-500 folder-info">
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
                                    <div class="w-full md:w-1/5 text-xs text-slate-500"><?php echo app_lang('na'); ?></div>
                                    <div class="w-full md:w-1/5 text-xs font-semibold text-slate-600"><?php echo app_lang('folder'); ?></div>
                                </div>
                                <span class="file-manager-more-menu bg-white border border-slate-200 shadow text-slate-500">
                                    <i data-feather="more-horizontal" class="icon-18"></i>
                                </span>
                            </div>
                        <?php } ?>
                    <?php } ?>

                    <?php if ($file_count) { ?>
                        <?php foreach ($file_items as $folder_item) {
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
                            <div class="folder-item" data-id="<?php echo $folder_item->id; ?>" data-type="file">
                                <div class="folder-item-content show-context-menu flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-3">
                                    <div class="w-full md:w-2/5 flex items-center gap-3">
                                        <i data-feather="file-text" class="w-4 h-4 text-slate-400"></i>
                                        <div>
                                            <div class="font-semibold text-sm text-slate-800 file-name"><?php echo js_anchor($file_name, $preview_link_attr); ?></div>
                                            <small class="text-xs text-slate-500 uppercase tracking-wide"><?php echo app_lang('click_to_preview'); ?></small>
                                        </div>
                                    </div>
                                    <div class="w-full md:w-1/5 text-xs text-slate-500"><?php echo app_lang('file'); ?></div>
                                    <div class="w-full md:w-1/5 text-xs text-slate-500 file-size"><?php echo $file_size; ?></div>
                                    <div class="w-full md:w-1/5 text-xs font-semibold text-slate-600"><?php echo app_lang('file'); ?></div>
                                </div>
                                <span class="file-manager-more-menu bg-white border border-slate-200 shadow text-slate-500">
                                    <i data-feather="more-horizontal" class="icon-18"></i>
                                </span>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<script>
    (function() {
        const toggleButtons = document.querySelectorAll("#file-view-toggle .view-mode-btn");
        const gridView = document.getElementById("finder-grid-view");
        const listView = document.getElementById("finder-list-view");
        const storageKey = "fileManagerViewMode";
        let currentMode = localStorage.getItem(storageKey) || "grid";

        function setMode(mode) {
            currentMode = mode;
            localStorage.setItem(storageKey, mode);

            if (mode === "grid") {
                gridView.classList.remove("hidden");
                listView.classList.add("hidden");
            } else {
                listView.classList.remove("hidden");
                gridView.classList.add("hidden");
            }

            toggleButtons.forEach(btn => {
                if (btn.getAttribute("data-mode") === mode) {
                    btn.classList.remove("text-slate-500");
                    btn.classList.add("bg-white", "text-slate-900", "shadow");
                } else {
                    btn.classList.remove("bg-white", "text-slate-900", "shadow");
                    btn.classList.add("text-slate-500");
                }
            });
        }

        toggleButtons.forEach(button => {
            button.addEventListener("click", function() {
                setMode(this.getAttribute("data-mode"));
            });
        });

        setMode(currentMode);
    })();
</script>
