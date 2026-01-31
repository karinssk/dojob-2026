<?php echo form_open(get_uri("line_bot_expenses/save_settings"), array("id" => "lbe-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="line_expenses_enabled" class="col-md-3"><?php echo app_lang('line_expenses_enabled'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_dropdown(
                    "line_expenses_enabled",
                    array("0" => app_lang("no"), "1" => app_lang("yes")),
                    $line_expenses_enabled,
                    "class='select2' id='line_expenses_enabled'"
                );
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="line_expenses_channel_access_token" class="col-md-3"><?php echo app_lang('line_expenses_channel_access_token'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "line_expenses_channel_access_token",
                    "name" => "line_expenses_channel_access_token",
                    "value" => $line_expenses_channel_access_token,
                    "class" => "form-control",
                    "placeholder" => "Channel access token for expenses bot",
                    "type" => "password"
                ));
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="line_expenses_channel_secret" class="col-md-3"><?php echo app_lang('line_expenses_channel_secret'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "line_expenses_channel_secret",
                    "name" => "line_expenses_channel_secret",
                    "value" => $line_expenses_channel_secret,
                    "class" => "form-control",
                    "placeholder" => "Channel secret for expenses bot",
                    "type" => "password"
                ));
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label class="col-md-3"><?php echo app_lang('line_expenses_webhook_url'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "value" => $line_expenses_webhook_url,
                    "class" => "form-control",
                    "readonly" => true
                ));
                ?>
                <small class="form-text text-muted"><?php echo app_lang('line_expenses_webhook_url_help'); ?></small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="line_expenses_report_target_id" class="col-md-3"><?php echo app_lang('line_expenses_report_target'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_dropdown(
                    "line_expenses_report_target_id",
                    $rooms_dropdown,
                    $line_expenses_report_target_id,
                    "class='select2' id='line_expenses_report_target_id'"
                );
                ?>
                <small class="form-text text-muted"><?php echo app_lang('line_expenses_report_target_help'); ?></small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="line_expenses_report_target_type" class="col-md-3">Target Type</label>
            <div class="col-md-9">
                <?php
                echo form_dropdown(
                    "line_expenses_report_target_type",
                    array("user" => "User", "group" => "Group", "room" => "Room"),
                    $line_expenses_report_target_type,
                    "class='select2' id='line_expenses_report_target_type'"
                );
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="line_expenses_default_category_id" class="col-md-3">Default Category ID</label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "line_expenses_default_category_id",
                    "name" => "line_expenses_default_category_id",
                    "value" => $line_expenses_default_category_id,
                    "class" => "form-control",
                    "placeholder" => "24",
                    "type" => "number"
                ));
                ?>
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    <button type="submit" class="btn btn-primary">
        <span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?>
    </button>
</div>
<?php echo form_close(); ?>

<script>
    $(document).ready(function () {
        $("#lbe-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
        function initLineExpensesSelects() {
            var options = {minimumResultsForSearch: -1, dropdownAutoWidth: true};
            $("#line_expenses_report_target_id").select2(options);
            $("#line_expenses_report_target_type").select2(options);
            $("#line_expenses_enabled").select2(options);
        }

        initLineExpensesSelects();

        $('a[data-bs-toggle="tab"][href="#lbe-settings-tab"]').on("shown.bs.tab", function () {
            $("#line_expenses_report_target_id").select2("close");
            $("#line_expenses_report_target_type").select2("close");
            $("#line_expenses_enabled").select2("close");
            $(".select2-drop-active").hide();
        });
    });
</script>
