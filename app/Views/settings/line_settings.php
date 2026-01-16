<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "line_settings";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h1><i data-feather="settings" class="icon-16"></i> <?php echo app_lang('line_settings'); ?></h1>
                </div>

                <?php echo form_open(get_uri("line_settings/save_line_settings"), array("id" => "line-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
                <div class="card-body">
                    <div class="form-group">
                        <div class="row">
                            <label for="line_channel_access_token" class="col-md-3">
                                <?php echo app_lang('line_channel_access_token'); ?>
                            </label>
                            <div class="col-md-9">
                                <?php
                                echo form_input(array(
                                    "id" => "line_channel_access_token",
                                    "name" => "line_channel_access_token",
                                    "value" => $line_channel_access_token,
                                    "class" => "form-control",
                                    "placeholder" => "Channel access token",
                                    "type" => "password"
                                ));
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="line_channel_secret" class="col-md-3">
                                <?php echo app_lang('line_channel_secret'); ?>
                            </label>
                            <div class="col-md-9">
                                <?php
                                echo form_input(array(
                                    "id" => "line_channel_secret",
                                    "name" => "line_channel_secret",
                                    "value" => $line_channel_secret,
                                    "class" => "form-control",
                                    "placeholder" => "Channel secret",
                                    "type" => "password"
                                ));
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label class="col-md-3"><?php echo app_lang('line_webhook_url'); ?></label>
                            <div class="col-md-9">
                                <?php
                                echo form_input(array(
                                    "value" => $line_webhook_url,
                                    "class" => "form-control",
                                    "readonly" => true
                                ));
                                ?>
                                <small class="form-text text-muted"><?php echo app_lang('line_webhook_url_help'); ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="line_default_room_id" class="col-md-3"><?php echo app_lang('line_default_room'); ?></label>
                            <div class="col-md-9">
                                <?php
                                echo form_dropdown(
                                    "line_default_room_id",
                                    $line_rooms_dropdown,
                                    $line_default_room_id,
                                    "class='select2' id='line_default_room_id'"
                                );
                                ?>
                                <small class="form-text text-muted"><?php echo app_lang('line_detected_rooms'); ?></small>
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
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $("#line-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });

        $("#line_default_room_id").select2();
    });
</script>
