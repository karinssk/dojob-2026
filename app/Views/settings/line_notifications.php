<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><i data-feather="settings" class="icon-16"></i> <?php echo app_lang('line_settings'); ?></h1>
            <div class="title-button-group">
                <a href="<?php echo get_uri('line_notify'); ?>" class="btn btn-default">
                    <i data-feather="activity" class="icon-16"></i> <?php echo app_lang('view_notifications'); ?>
                </a>
            </div>
        </div>

        <div class="card-body">

            <?php echo form_open(get_uri("line_settings/save"), array("id" => "line-settings-form", "class" => "general-form", "role" => "form")); ?>
            
            <!-- Configuration Status -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="info" class="icon-16"></i> <?php echo app_lang('configuration_status'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <h6><?php echo app_lang('line_status'); ?></h6>
                                    <span class="badge <?php echo get_setting('enable_line_notifications') ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo get_setting('enable_line_notifications') ? app_lang('enabled') : app_lang('disabled'); ?>
                                    </span>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h6><?php echo app_lang('token_status'); ?></h6>
                                    <span class="badge <?php echo !empty(get_setting('line_channel_access_token')) ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo !empty(get_setting('line_channel_access_token')) ? app_lang('configured') : app_lang('not_configured'); ?>
                                    </span>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h6><?php echo app_lang('recipients'); ?></h6>
                                    <span class="badge bg-info">
                                        <?php 
                                        $user_count = !empty(get_setting('line_user_ids')) ? count(explode(',', get_setting('line_user_ids'))) : 0;
                                        $group_count = !empty(get_setting('line_group_ids')) ? count(explode(',', get_setting('line_group_ids'))) : 0;
                                        echo ($user_count + $group_count) . ' ' . app_lang('recipients');
                                        ?>
                                    </span>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h6><?php echo app_lang('webhook_url'); ?></h6>
                                    <small class="text-muted"><?php echo get_uri('line_notify/webhook'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Configuration -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="settings" class="icon-16"></i> <?php echo app_lang('basic_configuration'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="row">
                                    <label for="enable_line_notifications" class="col-md-3"><?php echo app_lang('enable_line_notifications'); ?></label>
                                    <div class="col-md-9">
                                        <div class="form-check form-switch">
                                            <?php
                                            echo form_checkbox("enable_line_notifications", "1", get_setting('enable_line_notifications') ? true : false, "id='enable_line_notifications' class='form-check-input'");
                                            ?>
                                            <label class="form-check-label" for="enable_line_notifications">
                                                <?php echo app_lang('enable_line_notifications_help'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <label for="line_channel_access_token" class="col-md-3">
                                        <?php echo app_lang('line_channel_access_token'); ?>
                                        <span class="help" data-container="body" data-bs-toggle="tooltip" title="<?php echo app_lang('line_channel_access_token_help'); ?>">
                                            <i data-feather="help-circle" class="icon-16"></i>
                                        </span>
                                    </label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <?php
                                            echo form_input(array(
                                                "id" => "line_channel_access_token",
                                                "name" => "line_channel_access_token",
                                                "value" => get_setting('line_channel_access_token'),
                                                "class" => "form-control",
                                                "placeholder" => "Your LINE Channel Access Token",
                                                "type" => "password"
                                            ));
                                            ?>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('line_channel_access_token')">
                                                <i data-feather="eye" class="icon-16"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipients Configuration -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="users" class="icon-16"></i> <?php echo app_lang('recipients_configuration'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i data-feather="alert-triangle" class="icon-16"></i>
                                <strong><?php echo app_lang('important'); ?>:</strong> <?php echo app_lang('recipients_configuration_note'); ?>
                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <label for="line_user_ids" class="col-md-3">
                                        <?php echo app_lang('line_user_ids'); ?>
                                        <span class="help" data-container="body" data-bs-toggle="tooltip" title="<?php echo app_lang('line_user_ids_help'); ?>">
                                            <i data-feather="help-circle" class="icon-16"></i>
                                        </span>
                                    </label>
                                    <div class="col-md-9">
                                        <?php
                                        echo form_textarea(array(
                                            "id" => "line_user_ids",
                                            "name" => "line_user_ids",
                                            "value" => get_setting('line_user_ids'),
                                            "class" => "form-control",
                                            "placeholder" => "U1234567890abcdef1234567890abcdef1,U0987654321fedcba0987654321fedcba0",
                                            "rows" => "3"
                                        ));
                                        ?>
                                        <small class="form-text text-muted">
                                            <i data-feather="info" class="icon-12"></i> <?php echo app_lang('line_user_ids_help_text'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <label for="line_group_ids" class="col-md-3">
                                        <?php echo app_lang('line_group_ids'); ?>
                                        <span class="help" data-container="body" data-bs-toggle="tooltip" title="<?php echo app_lang('line_group_ids_help'); ?>">
                                            <i data-feather="help-circle" class="icon-16"></i>
                                        </span>
                                    </label>
                                    <div class="col-md-9">
                                        <?php
                                        echo form_textarea(array(
                                            "id" => "line_group_ids",
                                            "name" => "line_group_ids",
                                            "value" => get_setting('line_group_ids'),
                                            "class" => "form-control",
                                            "placeholder" => "C1234567890abcdef1234567890abcdef1,C0987654321fedcba0987654321fedcba0",
                                            "rows" => "3"
                                        ));
                                        ?>
                                        <small class="form-text text-muted">
                                            <i data-feather="info" class="icon-12"></i> <?php echo app_lang('line_group_ids_help_text'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="bell" class="icon-16"></i> <?php echo app_lang('notification_settings'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="row">
                                    <label for="line_reminder_days_before" class="col-md-3"><?php echo app_lang('reminder_days_before_deadline'); ?></label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <?php
                                            echo form_input(array(
                                                "id" => "line_reminder_days_before",
                                                "name" => "line_reminder_days_before",
                                                "value" => get_setting('line_reminder_days_before') ?: 3,
                                                "class" => "form-control",
                                                "type" => "number",
                                                "min" => "1",
                                                "max" => "30",
                                                "style" => "max-width: 100px;"
                                            ));
                                            ?>
                                            <span class="input-group-text"><?php echo app_lang('days'); ?></span>
                                        </div>
                                        <small class="form-text text-muted">
                                            <i data-feather="info" class="icon-12"></i> <?php echo app_lang('reminder_days_before_help'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <label for="line_notify_recurring_tasks" class="col-md-3"><?php echo app_lang('notify_recurring_task_creation'); ?></label>
                                    <div class="col-md-9">
                                        <div class="form-check form-switch">
                                            <?php
                                            echo form_checkbox("line_notify_recurring_tasks", "1", get_setting('line_notify_recurring_tasks') ? true : false, "id='line_notify_recurring_tasks' class='form-check-input'");
                                            ?>
                                            <label class="form-check-label" for="line_notify_recurring_tasks">
                                                <?php echo app_lang('notify_recurring_task_creation_help'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <label for="line_notify_overdue_tasks" class="col-md-3"><?php echo app_lang('notify_overdue_tasks'); ?></label>
                                    <div class="col-md-9">
                                        <div class="form-check form-switch">
                                            <?php
                                            echo form_checkbox("line_notify_overdue_tasks", "1", get_setting('line_notify_overdue_tasks') ? true : false, "id='line_notify_overdue_tasks' class='form-check-input'");
                                            ?>
                                            <label class="form-check-label" for="line_notify_overdue_tasks">
                                                <?php echo app_lang('notify_overdue_tasks_help'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testing & Actions -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="zap" class="icon-16"></i> <?php echo app_lang('testing_actions'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><?php echo app_lang('connection_testing'); ?></h6>
                                    <p class="text-muted"><?php echo app_lang('test_your_line_configuration'); ?></p>
                                    <div class="d-grid gap-2">
                                        <button type="button" id="test-webhook-btn" class="btn btn-info">
                                            <i data-feather="wifi" class="icon-16"></i> <?php echo app_lang('test_webhook'); ?>
                                        </button>
                                        <button type="button" id="send-test-notification-btn" class="btn btn-warning">
                                            <i data-feather="send" class="icon-16"></i> <?php echo app_lang('send_test_notification'); ?>
                                        </button>
                                        <button type="button" id="send-test-event-btn" class="btn btn-info">
                                            <i data-feather="calendar" class="icon-16"></i> <?php echo app_lang('test_event_notification'); ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6><?php echo app_lang('sample_data'); ?></h6>
                                    <p class="text-muted"><?php echo app_lang('create_sample_tasks_description'); ?></p>
                                    <button type="button" id="create-sample-tasks-btn" class="btn btn-success">
                                        <i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('create_sample_tasks'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php echo form_close(); ?>
        </div>

        <!-- Footer -->
        <div class="card-footer clearfix">
            <div class="float-start">
                <button type="submit" form="line-settings-form" class="btn btn-primary">
                    <i data-feather="check-circle" class="icon-16"></i> <?php echo app_lang('save'); ?>
                </button>
            </div>
            <div class="float-end">
                <a href="<?php echo get_uri('line_notify'); ?>" class="btn btn-default">
                    <i data-feather="activity" class="icon-16"></i> <?php echo app_lang('view_notifications'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Password visibility toggle
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            button.setAttribute('data-feather', 'eye-off');
        } else {
            field.type = 'password';
            button.setAttribute('data-feather', 'eye');
        }
        feather.replace();
    }

    $(document).ready(function () {
        $("#line-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        });

        // Test webhook connection
        $("#test-webhook-btn").click(function() {
            const btn = $(this);
            const originalText = btn.html();
            
            btn.prop('disabled', true).html('<i data-feather="loader" class="icon-16 spinning"></i> <?php echo app_lang("testing"); ?>...');
            
            $.ajax({
                url: "<?php echo get_uri('line_settings/test_webhook'); ?>",
                type: 'POST',
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        appAlert.success(result.message || '<?php echo app_lang("connection_test_successful"); ?>');
                    } else {
                        appAlert.error(result.message || '<?php echo app_lang("connection_test_failed"); ?>');
                    }
                },
                error: function() {
                    appAlert.error('<?php echo app_lang("connection_test_error"); ?>');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalText);
                    feather.replace();
                }
            });
        });

        // Send test notification
        $("#send-test-notification-btn").click(function() {
            const btn = $(this);
            const originalText = btn.html();
            
            btn.prop('disabled', true).html('<i data-feather="loader" class="icon-16 spinning"></i> <?php echo app_lang("sending"); ?>...');
            
            $.ajax({
                url: "<?php echo get_uri('line_settings/send_test_notification'); ?>",
                type: 'POST',
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        appAlert.success(result.message || '<?php echo app_lang("notification_sent_successfully"); ?>');
                    } else {
                        appAlert.error(result.message || '<?php echo app_lang("failed_to_send_notification"); ?>');
                    }
                },
                error: function() {
                    appAlert.error('<?php echo app_lang("error_sending_notification"); ?>');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalText);
                    feather.replace();
                }
            });
        });

        // Send test event notification
        $("#send-test-event-btn").click(function() {
            const btn = $(this);
            const originalText = btn.html();
            
            btn.prop('disabled', true).html('<i data-feather="loader" class="icon-16 spinning"></i> <?php echo app_lang("sending"); ?>...');
            
            $.ajax({
                url: "<?php echo get_uri('line_settings/send_test_event_notification'); ?>",
                type: 'POST',
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        appAlert.success(result.message || '<?php echo app_lang("test_event_sent_successfully"); ?>');
                    } else {
                        appAlert.error(result.message || '<?php echo app_lang("failed_to_send_test_event"); ?>');
                    }
                },
                error: function() {
                    appAlert.error('<?php echo app_lang("error_sending_test_event"); ?>');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalText);
                    feather.replace();
                }
            });
        });

        // Create sample tasks
        $("#create-sample-tasks-btn").click(function() {
            if (!confirm('<?php echo app_lang("create_sample_tasks_confirm"); ?>')) {
                return;
            }
            
            const btn = $(this);
            const originalText = btn.html();
            
            btn.prop('disabled', true).html('<i data-feather="loader" class="icon-16 spinning"></i> <?php echo app_lang("creating"); ?>...');
            
            $.ajax({
                url: "<?php echo get_uri('line_settings/create_sample_recurring_tasks'); ?>",
                type: 'POST',
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        let message = result.message || '<?php echo app_lang("sample_tasks_created_successfully"); ?>';
                        if (result.tasks && result.tasks.length > 0) {
                            let taskList = result.tasks.map(task => `• ${task.title} (<?php echo app_lang("due"); ?>: ${task.deadline})`).join('\n');
                            message += '\n\n<?php echo app_lang("created_tasks"); ?>:\n' + taskList;
                        }
                        appAlert.success(message, {duration: 15000});
                    } else {
                        appAlert.error(result.message || '<?php echo app_lang("failed_to_create_sample_tasks"); ?>');
                    }
                },
                error: function() {
                    appAlert.error('<?php echo app_lang("error_creating_sample_tasks"); ?>');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalText);
                    feather.replace();
                }
            });
        });

        // Initialize feather icons
        feather.replace();
    });
</script>

<style>
.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.form-check-input:checked {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.alert {
    border-left: 4px solid;
}

.alert-warning {
    border-left-color: var(--bs-warning);
}

.card-header h4 {
    margin: 0;
    font-weight: 600;
}

.d-grid .btn {
    margin-bottom: 0.5rem;
}

.d-grid .btn:last-child {
    margin-bottom: 0;
}
</style>