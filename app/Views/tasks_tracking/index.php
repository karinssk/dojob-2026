<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view["active_tab"] = "tasks_tracking";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h1><i data-feather="git-merge" class="icon-16"></i> <?php echo app_lang("tasks_tracking"); ?></h1>
                </div>

                <?php echo form_open(get_uri("tasks_tracking/save"), array("id" => "tasks-tracking-form", "class" => "general-form dashed-row", "role" => "form")); ?>
                <div class="card-body">
                    <div class="form-group">
                        <div class="row">
                            <label for="line_task_tracking_keywords" class="col-md-3"><?php echo app_lang("line_task_tracking_keywords"); ?></label>
                            <div class="col-md-9">
                                <?php
                                echo form_input(array(
                                    "id" => "line_task_tracking_keywords",
                                    "name" => "line_task_tracking_keywords",
                                    "value" => $keywords,
                                    "class" => "form-control",
                                    "placeholder" => $default_keywords
                                ));
                                ?>
                                <small class="form-text text-muted"><?php echo app_lang("line_task_tracking_keywords_help"); ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="flow-board">
                        <div class="flow-track">
                            <div class="flow-node">
                                <div class="flow-icon"><i data-feather="message-circle" class="icon-16"></i></div>
                                <div class="flow-title"><?php echo app_lang("line_webhook_event"); ?></div>
                                <div class="flow-sub">LINE message arrives</div>
                            </div>
                            <div class="flow-connector"></div>
                            <div class="flow-node">
                                <div class="flow-icon"><i data-feather="hash" class="icon-16"></i></div>
                                <div class="flow-title"><?php echo app_lang("keyword_match"); ?></div>
                                <div class="flow-sub"><?php echo app_lang("keyword_match_detail"); ?></div>
                            </div>
                            <div class="flow-connector"></div>
                            <div class="flow-node">
                                <div class="flow-icon"><i data-feather="user-check" class="icon-16"></i></div>
                                <div class="flow-title"><?php echo app_lang("resolve_user"); ?></div>
                                <div class="flow-sub"><?php echo app_lang("resolve_user_detail"); ?></div>
                            </div>
                            <div class="flow-connector"></div>
                            <div class="flow-node">
                                <div class="flow-icon"><i data-feather="folder" class="icon-16"></i></div>
                                <div class="flow-title"><?php echo app_lang("open_projects_tasks"); ?></div>
                                <div class="flow-sub"><?php echo app_lang("open_projects_tasks_detail"); ?></div>
                            </div>
                            <div class="flow-connector"></div>
                            <div class="flow-node">
                                <div class="flow-icon"><i data-feather="image" class="icon-16"></i></div>
                                <div class="flow-title"><?php echo app_lang("latest_comment_images"); ?></div>
                                <div class="flow-sub"><?php echo app_lang("latest_comment_images_detail"); ?></div>
                            </div>
                            <div class="flow-connector"></div>
                            <div class="flow-node">
                                <div class="flow-icon"><i data-feather="send" class="icon-16"></i></div>
                                <div class="flow-title"><?php echo app_lang("line_reply"); ?></div>
                                <div class="flow-sub"><?php echo app_lang("line_reply_detail"); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang("save"); ?>
                    </button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $("#tasks-tracking-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>

<style>
    .flow-board {
        margin-top: 24px;
        border: 1px solid #e6e8eb;
        border-radius: 12px;
        padding: 20px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        overflow-x: auto;
    }

    .flow-track {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        min-width: 900px;
    }

    .flow-node {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        min-width: 150px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }

    .flow-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #e0f2fe;
        color: #0369a1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
    }

    .flow-title {
        font-weight: 600;
        font-size: 13px;
        color: #0f172a;
    }

    .flow-sub {
        font-size: 11px;
        color: #64748b;
        margin-top: 4px;
    }

    .flow-connector {
        width: 36px;
        height: 2px;
        background: #94a3b8;
        position: relative;
    }

    .flow-connector:after {
        content: "";
        position: absolute;
        right: -2px;
        top: -4px;
        border-top: 5px solid transparent;
        border-bottom: 5px solid transparent;
        border-left: 6px solid #94a3b8;
    }
</style>
