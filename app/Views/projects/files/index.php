<?php
// Add these variable checks at the top of the file to prevent 500 errors
$project_id = isset($project_id) ? $project_id : 0;
$folder_id = isset($folder_id) ? $folder_id : 0;
$can_add_files = isset($can_add_files) ? $can_add_files : false;
$login_user = isset($login_user) ? $login_user : (object)['user_type' => 'client'];
$file_categories_dropdown = isset($file_categories_dropdown) ? $file_categories_dropdown : '[]';
$custom_field_filters = isset($custom_field_filters) ? $custom_field_filters : '';
$custom_field_headers = isset($custom_field_headers) ? $custom_field_headers : '';
$tab = isset($tab) ? $tab : '';
?>

<div>
    <ul id="project-files-tabs" data-bs-toggle="ajax-tab" class="nav nav-tabs bg-white title" role="tablist">
        <li class="nav-item title-tab">
            <h4 class="pl15 pt10 pr15"><?php echo app_lang("files"); ?></h4>
        </li>

        <li class="nav-item"><a class="nav-link" id="files-button" role="presentation" href="javascript:;" data-bs-target="#files-list"><?php echo app_lang("files_list"); ?></a></li>

        <?php if (get_setting("module_file_manager") == "1") { ?>
            <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("projects/explore/" . $folder_id . "/1/project_view/" . $project_id); ?>" data-bs-target="#folder-tab" data-post-view_from="project_view"><?php echo app_lang('folders'); ?></a></li>
        <?php } ?>

        <?php if ($login_user && property_exists($login_user, 'user_type') && $login_user->user_type === "staff") { ?>
            <li class="nav-item"><a class="nav-link" role="presentation" href="<?php echo_uri("projects/file_category/$project_id"); ?>" data-bs-target="#files-category"><?php echo app_lang('category'); ?></a></li>
        <?php } ?>

        <div class="tab-title clearfix no-border">
            <div class="title-button-group">

                <?php echo js_anchor("<i data-feather='check-square' class='icon-16'></i> <span id='btn-text-content'>" . app_lang("select_all") . "</span>", array("title" => app_lang("select_all"), "id" => "select-un-select-all-file-btn", "class" => "btn btn-default hide")); ?>
                <?php echo anchor("", "<i data-feather='download' class='icon-16'></i> " . app_lang("download"), array("title" => app_lang("download"), "id" => "download-multiple-file-btn", "class" => "btn btn-default hide")); ?>
                <?php echo anchor("", "<i data-feather='x' class='icon-16'></i> " . app_lang("delete"), array("title" => app_lang("delete"), "id" => "delete-multiple-file-btn", "class" => "btn btn-default hide")); ?>

                <?php
                if ($can_add_files && $project_id) {
                    echo modal_anchor(get_uri("projects/file_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_files'), array(
                        "class" => "btn btn-default", 
                        "title" => app_lang('add_files'), 
                        "data-title" => app_lang('add_files'),
                        "data-post-project_id" => $project_id, 
                        "data-post-folder_id" => $folder_id,
                        "data-post-view_from" => "project_view",
                        "id" => "file_or_category_add_button",
                        "onclick" => "console.log('Upload button clicked - Project ID: ' + this.getAttribute('data-post-project_id') + ', Folder ID: ' + this.getAttribute('data-post-folder_id'));"
                    ));
                }
                ?>
            </div>
        </div>

    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade" id="files-list">
            <div class="card border-top-0 rounded-top-0">
                <div class="table-responsive">
                    <table id="project-file-table" class="display" width="100%">
                    </table>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane fade default-bg" id="folder-tab"></div>
        <div role="tabpanel" class="tab-pane fade" id="files-category"></div>
    </div>

</div>


<script type="text/javascript">
    $(document).ready(function() {
        console.log('Document ready - File upload page loaded');
        console.log('Project ID:', <?php echo json_encode($project_id); ?>);
        console.log('Folder ID:', <?php echo json_encode($folder_id); ?>);
        console.log('Can add files:', <?php echo json_encode($can_add_files); ?>);

        //we have to add values of selected files for multiple download
        var fields = [];

        $('body').on('click', '[data-act=download-multiple-file-checkbox]', function() {

            var checkbox = $(this).find("span"),
                file_id = $(this).attr("data-id");

            checkbox.addClass("inline-loader");

            //there are two operation
            if ($.inArray(file_id, fields) !== -1) {
                //if there is already added the file to download list
                var index = fields.indexOf(file_id);
                fields.splice(index, 1);
                checkbox.removeClass("checkbox-checked");
            } else {
                //if it's new item to add to download list
                fields.push(file_id);
                checkbox.addClass("checkbox-checked");
            }

            checkbox.removeClass("inline-loader");

            var serializeOfArray = fields.join("-");

            $("#download-multiple-file-btn").attr("href", "<?php echo_uri("projects/download_multiple_files/"); ?>" + serializeOfArray);
            $("#delete-multiple-file-btn").attr("href", "<?php echo_uri("projects/delete_multiple_files/"); ?>" + serializeOfArray);

            if (fields.length) {
                $("#download-multiple-file-btn").removeClass("hide");
                $("#delete-multiple-file-btn").removeClass("hide");
                $("#select-un-select-all-file-btn").removeClass("hide");
            } else {
                $("#download-multiple-file-btn").addClass("hide");
                $("#delete-multiple-file-btn").addClass("hide");
                $("#select-un-select-all-file-btn").addClass("hide");
            }

        });

        //trigger download operation for multiple download
        $("#download-multiple-file-btn").click(function() {
            $(this).addClass("hide");
            $("#select-un-select-all-file-btn").addClass("hide");
            $("[data-act=download-multiple-file-checkbox]").find("span").removeClass("checkbox-checked");
            fields = [];
            window.location.href = $(this).attr("href"); //direct link won't work in ajax tab
        });

        //trigger delete operation for multiple delete
        $("#delete-multiple-file-btn").click(function() {
            $(this).addClass("hide");
            $("#select-un-select-all-file-btn").addClass("hide");
            $("#download-multiple-file-btn").addClass("hide");
            $("[data-act=download-multiple-file-checkbox]").find("span").removeClass("checkbox-checked");
            fields = [];
            appLoader.show();

            $.ajax({
                url: $(this).attr("href"),
                type: 'POST',
                dataType: 'json',
                success: function(result) {
                    appLoader.hide();
                    if (result.success) {
                        appAlert.warning(result.message, {
                            duration: 10000
                        });
                        $("#project-file-table").appTable({
                            reload: true
                        });
                    } else {
                        appAlert.error(result.message);
                    }
                },
                error: function(xhr, status, error) {
                    appLoader.hide();
                    appAlert.error("An error occurred while deleting files: " + error);
                }
            });
        });

        //select/un-select all files
        $("#select-un-select-all-file-btn").click(function() {
            //either it's select/un-select operation
            //removing this first is necessary
            $("[data-act=download-multiple-file-checkbox]").find("span").removeClass("checkbox-checked");
            $("#download-multiple-file-btn").attr("href", "<?php echo_uri("projects/download_multiple_files/"); ?>");
            fields = [];

            if ($(this).attr("is-selected")) {
                //un-select
                $(this).find("#btn-text-content").text("<?php echo app_lang("select_all"); ?>");
                $(this).removeAttr("is-selected");
                $("#download-multiple-file-btn").addClass("hide");
                $("#delete-multiple-file-btn").addClass("hide");
            } else {
                //select
                $(this).find("#btn-text-content").text("<?php echo app_lang("unselect_all"); ?>");
                $(this).attr("is-selected", "1");
                $("#download-multiple-file-btn").removeClass("hide");
                $("#delete-multiple-file-btn").removeClass("hide");
                $("[data-act=download-multiple-file-checkbox]").each(function() {
                    $(this).trigger("click");
                });
            }
        });

        // Check if required variables exist
        var projectId = <?php echo json_encode($project_id); ?>;
        
        var userType = "<?php echo isset($login_user->user_type) ? $login_user->user_type : 'client'; ?>",
            showUploadeBy = true;
        if (userType == "client") {
            showUploadeBy = false;
        }

        // Only initialize table if project_id exists
        console.log('Checking project ID for table initialization:', projectId);
        if (projectId && projectId > 0) {
            console.log('Initializing project file table...');
            console.log('Table source URL:', '<?php echo_uri("projects/files_list_data/" . $project_id) ?>');
            $("#project-file-table").appTable({
                source: '<?php echo_uri("projects/files_list_data/" . $project_id) ?>',
                order: [
                    [0, "desc"]
                ],
                filterDropdown: [{
                    name: "category_id",
                    class: "w200",
                    options: <?php echo $file_categories_dropdown; ?>
                }<?php echo !empty($custom_field_filters) ? ', ' . $custom_field_filters : ''; ?>],
            columns: [
                {title: '<?php echo app_lang("id") ?>'},
                {title: '<?php echo app_lang("file") ?>', "class": "all file-name-section"},
                {title: '<?php echo app_lang("category") ?>'},
                {title: '<?php echo app_lang("size") ?>'},
                {visible: showUploadeBy, title: '<?php echo app_lang("uploaded_by") ?>'},
                {title: '<?php echo app_lang("created_date") ?>'}
                <?php echo $custom_field_headers; ?>,
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w150"}
            ],
                printColumns: combineCustomFieldsColumns([0, 1, 2, 3, 4, 5], '<?php echo $custom_field_headers; ?>'),
                xlsColumns: combineCustomFieldsColumns([0, 1, 2, 3, 4, 5], '<?php echo $custom_field_headers; ?>')
            });
        }

        //change the add button attributes on changing tab panel
        var addButton = $("#file_or_category_add_button");
        console.log('Add button found:', addButton.length > 0);
        
        $(".nav-tabs li").click(function() {
            console.log('Tab clicked:', $(this).find("a").attr("data-bs-target"));
            var activeField = $(this).find("a").attr("data-bs-target");
            if (activeField === "#files-list") {
                console.log('Setting up files tab');
                addButton.removeClass("hide");
                addButton.attr("title", "<?php echo app_lang("add_files"); ?>");
                addButton.attr("data-title", "<?php echo app_lang("add_files"); ?>");
                addButton.attr("data-action-url", "<?php echo_uri("projects/file_modal_form"); ?>");
                addButton.attr("data-post-project_id", "<?php echo $project_id; ?>");
                addButton.attr("data-post-folder_id", "<?php echo $folder_id; ?>");
                console.log('Files tab setup complete - URL:', "<?php echo_uri("projects/file_modal_form"); ?>");

                addButton.html("<?php echo "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_files'); ?>");
            } else if (activeField === "#files-category") {
                addButton.removeClass("hide");
                addButton.attr("title", "<?php echo app_lang("add_category"); ?>");
                addButton.attr("data-title", "<?php echo app_lang("add_category"); ?>");
                addButton.attr("data-action-url", "<?php echo_uri("projects/file_category_modal_form"); ?>");
                addButton.attr("data-post-project_id", "<?php echo $project_id; ?>");

                addButton.html("<?php echo "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_category'); ?>");
            } else {
                addButton.addClass("hide");
            }

            feather.replace();
        });

        // Add debug logging for file upload
        $(document).on('shown.bs.modal', '#ajaxModal', function() {
            console.log('File upload modal opened');
            console.log('Modal content:', $(this).find('.modal-content').length);
            console.log('Modal body:', $(this).find('.modal-body').html().substring(0, 200));
        });

        // Add error handling for file upload completion
        $(document).on('hidden.bs.modal', '#ajaxModal', function() {
            console.log('Modal closed - checking for file upload completion');
            // Reload file list after upload
            if (projectId && projectId > 0) {
                console.log('Reloading file table...');
                setTimeout(function() {
                    $("#project-file-table").appTable({reload: true});
                }, 1000);
            }
        });

        // Add logging for modal form submissions
        $(document).on('submit', '#ajaxModal form', function(e) {
            console.log('Form submitted in modal');
            console.log('Form action:', $(this).attr('action'));
            console.log('Form method:', $(this).attr('method'));
            console.log('Form data:', new FormData(this));
        });

        // Log any AJAX requests
        $(document).ajaxSend(function(event, xhr, settings) {
            console.log('AJAX Request sent:', settings.url, settings.type);
        });

        $(document).ajaxComplete(function(event, xhr, settings) {
            console.log('AJAX Request completed:', settings.url, 'Status:', xhr.status);
            if (xhr.status >= 400) {
                console.error('AJAX Error Response:', xhr.responseText);
            }
        });

        setTimeout(function() {
            var tab = "<?php echo $tab; ?>";
            if (tab === "file_manager" || "<?php echo $folder_id; ?>" != 0) {
                $("[data-bs-target='#folder-tab']").trigger("click");
            }
        }, 150);

        $("[data-bs-target='#folder-tab']").click(function() {
            // Check if this is not page view and $tab is not containing "file_manager"
            if (!window.location.href.includes('file_manager')) {
                var browserState = {
                    Url: window.location.href + '/file_manager/#'
                };
                history.pushState(browserState, "", browserState.Url);
            }
        });

        // Hide modal navigation buttons when file view modal is open
        $(document).on('shown.bs.modal', '.app-modal', function() {
            console.log('Modal opened, hiding navigation buttons');
            $('.app-modal-next-button, .app-modal-prev-button').hide();
        });

        $(document).on('hidden.bs.modal', '.app-modal', function() {
            console.log('Modal closed, showing navigation buttons');
            $('.app-modal-next-button, .app-modal-prev-button').show();
        });

    });
</script>

<style>
/* Hide modal navigation buttons when modal is open */
.modal.show .app-modal-next-button,
.modal.show .app-modal-prev-button,
body.modal-open .app-modal-next-button,
body.modal-open .app-modal-prev-button {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
}
</style>