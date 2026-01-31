<?php echo form_open(get_uri("line_bot_expenses/save_daily_report_settings"), array("id" => "lbe-daily-report-form", "class" => "general-form dashed-row", "role" => "form")); ?>
<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="line_expenses_daily_report_enabled" class="col-md-3"><?php echo app_lang('daily_report_enabled'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_dropdown(
                    "line_expenses_daily_report_enabled",
                    array("0" => app_lang("no"), "1" => app_lang("yes")),
                    $line_expenses_daily_report_enabled,
                    "class='select2' id='line_expenses_daily_report_enabled'"
                );
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="line_expenses_daily_report_time" class="col-md-3"><?php echo app_lang('daily_report_time'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "line_expenses_daily_report_time",
                    "name" => "line_expenses_daily_report_time",
                    "value" => $line_expenses_daily_report_time,
                    "class" => "form-control",
                    "type" => "time"
                ));
                ?>
                <small class="form-text text-muted">Thailand timezone (Asia/Bangkok). Requires system cron calling the cron endpoint every minute.</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label class="col-md-3">Cron URL</label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "value" => get_uri("line_bot_expenses/cron_daily_report"),
                    "class" => "form-control",
                    "readonly" => true
                ));
                ?>
                <small class="form-text text-muted">Add this URL to your system cron (every minute): <code>* * * * * curl -s "<?php echo get_uri('line_bot_expenses/cron_daily_report'); ?>" > /dev/null 2>&1</code></small>
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    <button type="submit" class="btn btn-primary">
        <span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?>
    </button>
    <button type="button" class="btn btn-info ms-2" id="test-daily-report-btn">
        <span data-feather="send" class="icon-16"></span> <?php echo app_lang('send_test_daily_report'); ?>
    </button>
</div>
<?php echo form_close(); ?>

<script>
    $(document).ready(function () {
        $("#lbe-daily-report-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
        $("#line_expenses_daily_report_enabled").select2();

        $("#test-daily-report-btn").click(function () {
            var btn = $(this);
            btn.prop("disabled", true).text("Sending...");
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/test_daily_report'); ?>",
                type: "POST",
                dataType: "json",
                success: function (result) {
                    btn.prop("disabled", false).html('<span data-feather="send" class="icon-16"></span> <?php echo app_lang("send_test_daily_report"); ?>');
                    feather.replace();
                    if (result.success) {
                        appAlert.success("<?php echo app_lang('test_report_sent'); ?>", {duration: 5000});
                    } else {
                        appAlert.error(result.error || "<?php echo app_lang('test_report_failed'); ?>", {duration: 5000});
                    }
                },
                error: function () {
                    btn.prop("disabled", false).html('<span data-feather="send" class="icon-16"></span> <?php echo app_lang("send_test_daily_report"); ?>');
                    feather.replace();
                    appAlert.error("<?php echo app_lang('test_report_failed'); ?>", {duration: 5000});
                }
            });
        });
    });
</script>
