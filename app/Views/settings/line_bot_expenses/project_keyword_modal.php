<?php echo form_open(get_uri("line_bot_expenses/save_project_keyword"), array("id" => "project-keyword-form", "class" => "general-form", "role" => "form")); ?>
<input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

<div class="modal-body clearfix">
    <div class="form-group">
        <div class="row">
            <label for="keyword" class="col-md-3"><?php echo app_lang('keyword'); ?> <span class="text-danger">*</span></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "keyword",
                    "name" => "keyword",
                    "value" => $model_info->keyword,
                    "class" => "form-control",
                    "placeholder" => "Exact match keyword (e.g. ruby, 9)",
                    "data-rule-required" => true,
                    "data-msg-required" => "Keyword is required"
                ));
                ?>
                <small class="form-text text-muted">This keyword will be matched exactly (===)</small>
                <small id="project-keyword-duplicate-msg" class="form-text text-danger d-none">Keyword already exists.</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="client_id" class="col-md-3"><?php echo app_lang('client_name'); ?> <span class="text-danger">*</span></label>
            <div class="col-md-9">
                <?php
                echo form_dropdown("client_id", $clients_dropdown, $selected_client_id, "class='select2' id='client_id' data-rule-required='true' data-msg-required='Client name is required'");
                ?>
                <input type="hidden" id="client_name" name="client_name" value="<?php echo $model_info->client_name; ?>" />
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="project_id" class="col-md-3"><?php echo app_lang('project_name'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_dropdown("project_id", $projects_dropdown, $selected_project_id, "class='select2' id='project_id'");
                ?>
                <input type="hidden" id="project_name" name="project_name" value="<?php echo $model_info->project_name; ?>" />
                <small class="form-text text-muted">Project options are filtered by the selected client.</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="is_monthly_project" class="col-md-3"><?php echo app_lang('is_monthly_project'); ?></label>
            <div class="col-md-9">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_monthly_project" id="is_monthly_project" value="1" <?php echo $model_info->is_monthly_project ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_monthly_project"><?php echo app_lang('is_monthly_project_help'); ?></label>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="sort" class="col-md-3">Sort</label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "sort",
                    "name" => "sort",
                    "value" => $model_info->sort ?: 0,
                    "class" => "form-control",
                    "type" => "number"
                ));
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script>
    $(document).ready(function () {
        var $keyword = $("#keyword");
        var $form = $("#project-keyword-form");
        var $msg = $("#project-keyword-duplicate-msg");
        var $submit = $form.find("button[type=submit]");
        var keywordTimer = null;

        function checkDuplicate() {
            var value = $.trim($keyword.val());
            if (!value) {
                $msg.addClass("d-none");
                $submit.prop("disabled", false);
                return;
            }
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/check_project_keyword_duplicate'); ?>",
                type: "POST",
                dataType: "json",
                data: {keyword: value, id: $form.find("input[name=id]").val()},
                success: function (result) {
                    var exists = result && result.exists;
                    $msg.toggleClass("d-none", !exists);
                    $submit.prop("disabled", !!exists);
                }
            });
        }

        $keyword.on("input", function () {
            if (keywordTimer) {
                clearTimeout(keywordTimer);
            }
            keywordTimer = setTimeout(checkDuplicate, 300);
        });

        checkDuplicate();

        $("#project-keyword-form").appForm({
            onSuccess: function (result) {
                console.log("[Project Keywords] Save success", result);
                if (typeof window.reloadProjectKeywordsTable === "function") {
                    window.reloadProjectKeywordsTable();
                }
                appAlert.success(result.message, {duration: 5000});
            },
            onError: function () {
                console.log("[Project Keywords] Save failed", arguments);
            }
        });
        $form.on("submit", function () {
            console.log("[Project Keywords] Save submit", $form.serialize());
        });
        $(document).ajaxError(function (event, xhr, settings, thrownError) {
            if (settings && settings.url && settings.url.indexOf("line_bot_expenses/save_project_keyword") !== -1) {
                console.log("[Project Keywords] Global ajaxError", settings.url, xhr.status, xhr.responseText, thrownError);
            }
        });

        var $client = $("#client_id");
        var $project = $("#project_id");
        var $modal = $("#ajaxModal");

        if (!$client.hasClass("select2-hidden-accessible")) {
            $client.select2({dropdownParent: $modal});
        }
        if (!$project.hasClass("select2-hidden-accessible")) {
            $project.select2({dropdownParent: $modal});
        }

        function syncClientName() {
            var text = $client.find("option:selected").text() || "";
            $("#client_name").val($client.val() ? text : "");
        }

        function syncProjectName() {
            var text = $project.find("option:selected").text() || "";
            $("#project_name").val($project.val() ? text : "");
        }

        function setProjectOptions(options, selectedId) {
            $project.empty();
            $.each(options, function (i, item) {
                var option = new Option(item.text, item.id, false, String(item.id) === String(selectedId));
                $project.append(option);
            });
            $project.val(selectedId || "").trigger("change");
            syncProjectName();
        }

        function loadProjects(clientId, selectedId) {
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/get_projects_of_selected_client'); ?>",
                type: "POST",
                dataType: "json",
                data: {client_id: clientId},
                success: function (result) {
                    setProjectOptions(result, selectedId);
                },
                error: function () {
                    setProjectOptions([{id: "", text: "- <?php echo app_lang('project'); ?> -"}], "");
                }
            });
        }

        $client.on("change", function () {
            syncClientName();
            loadProjects($client.val(), "");
        });

        $project.on("change", function () {
            syncProjectName();
        });

        syncClientName();
        syncProjectName();
    });
</script>
