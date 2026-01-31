<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1>
                <i data-feather="bell" class="icon-16"></i> <?php echo app_lang('line_notifications'); ?>
                <?php if (isset($bot_info) && $bot_info && isset($bot_info['displayName'])): ?>
                    <small class="text-muted ms-2">- <?php echo htmlspecialchars($bot_info['displayName']); ?></small>
                <?php endif; ?>
            </h1>
            <div class="title-button-group">
                <a href="<?php echo get_uri('line_settings'); ?>" class="btn btn-info">
                    <i data-feather="settings" class="icon-16"></i> <?php echo app_lang('line_settings'); ?>
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Status Overview -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card bg-info">
                        <div class="card-body">
                            <div class="widget-icon">
                                <i data-feather="bell" class="icon-24"></i>
                            </div>
                            <div class="widget-details">
                                <h1><?php echo $line_enabled ? app_lang('enabled') : app_lang('disabled'); ?></h1>
                                <span class="bg-transparent-white"><?php echo app_lang('line_status'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card bg-primary">
                        <div class="card-body">
                            <div class="widget-icon">
                                <i data-feather="settings" class="icon-24"></i>
                            </div>
                            <div class="widget-details">
                                <h1><?php echo $has_token ? app_lang('configured') : app_lang('not_configured'); ?></h1>
                                <span class="bg-transparent-white"><?php echo app_lang('configuration'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card bg-success">
                        <div class="card-body">
                            <div class="widget-icon">
                                <i data-feather="users" class="icon-24"></i>
                            </div>
                            <div class="widget-details">
                                <h1><?php echo !empty($user_ids) ? count(explode(',', $user_ids)) : 0; ?></h1>
                                <span class="bg-transparent-white"><?php echo app_lang('users'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card bg-warning">
                        <div class="card-body">
                            <div class="widget-icon">
                                <i data-feather="message-circle" class="icon-24"></i>
                            </div>
                            <div class="widget-details">
                                <h1><?php echo !empty($group_ids) ? count(explode(',', $group_ids)) : 0; ?></h1>
                                <span class="bg-transparent-white"><?php echo app_lang('groups'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="zap" class="icon-16"></i> <?php echo app_lang('quick_actions'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="btn-group" role="group">
                                <button class="btn btn-info" onclick="testConnection()">
                                    <i data-feather="wifi" class="icon-16"></i> <?php echo app_lang('test_connection'); ?>
                                </button>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Statistics -->
            <?php if (isset($notification_stats) && $notification_stats): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="bar-chart-2" class="icon-16"></i> <?php echo app_lang('notification_statistics'); ?> (<?php echo app_lang('last_30_days'); ?>)</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <h2 class="text-primary"><?php echo $notification_stats->total_notifications ?? 0; ?></h2>
                                    <p class="text-muted"><?php echo app_lang('total_sent'); ?></p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <h2 class="text-success"><?php echo $notification_stats->successful_notifications ?? 0; ?></h2>
                                    <p class="text-muted"><?php echo app_lang('successful'); ?></p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <h2 class="text-danger"><?php echo $notification_stats->failed_notifications ?? 0; ?></h2>
                                    <p class="text-muted"><?php echo app_lang('failed'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upcoming Events -->
            <?php if (isset($upcoming_events) && !empty($upcoming_events)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="calendar" class="icon-16"></i> <?php echo app_lang('upcoming_events_with_line_notifications'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo app_lang('event'); ?></th>
                                            <th><?php echo app_lang('start_date'); ?></th>
                                            <th><?php echo app_lang('start_time'); ?></th>
                                            <th><?php echo app_lang('status'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcoming_events as $event): ?>
                                        <tr>
                                            <td><?php echo $event->title; ?></td>
                                            <td><?php echo format_to_date($event->start_date, false); ?></td>
                                            <td><?php echo $event->start_time ?: '-'; ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i data-feather="bell" class="icon-12"></i> <?php echo app_lang('enabled'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Notification Logs -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="list" class="icon-16"></i> <?php echo app_lang('recent_notification_logs'); ?></h4>
                            <div class="card-header-action">
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshLogs()">
                                    <i data-feather="refresh-cw" class="icon-12"></i> <?php echo app_lang('refresh'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="notification-logs-container">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="notification-logs-table">
                                        <thead>
                                            <tr>
                                                <th><?php echo app_lang('date_time'); ?></th>
                                                <th><?php echo app_lang('type'); ?></th>
                                                <th><?php echo app_lang('event_task'); ?></th>
                                                <th><?php echo app_lang('status'); ?></th>
                                                <th><?php echo app_lang('message'); ?></th>
                                                <th>Response / Error</th>
                                            </tr>
                                        </thead>
                                        <tbody id="logs-tbody">
                                            <?php if (isset($recent_logs) && !empty($recent_logs)): ?>
                                                <?php foreach ($recent_logs as $log): ?>
                                                <tr>
                                                    <td><?php echo format_to_datetime($log->sent_at); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $log->notification_type; ?></span>
                                                    </td>
                                                    <td><?php echo $log->event_title ?: $log->task_title ?: '-'; ?></td>
                                                    <td>
                                                        <?php if ($log->status === 'sent'): ?>
                                                            <span class="badge bg-success"><?php echo app_lang('sent'); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger"><?php echo app_lang('failed'); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($log->message); ?>">
                                                        <?php echo character_limiter($log->message, 50); ?>
                                                    </td>
                                                    <td class="text-truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($log->response ?? ''); ?>">
                                                        <?php if (!empty($log->response)): ?>
                                                            <small class="<?php echo $log->status === 'sent' ? 'text-success' : 'text-danger'; ?>"><?php echo htmlspecialchars($log->response); ?></small>
                                                        <?php else: ?>
                                                            <small class="text-muted">-</small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">
                                                        <?php echo app_lang('no_notification_logs_found'); ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Notification Test -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="send" class="icon-16"></i> <?php echo app_lang('send_test_notification'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="test-message"><?php echo app_lang('message'); ?></label>
                                <textarea id="test-message" class="form-control" rows="3" placeholder="<?php echo app_lang('enter_test_message'); ?>"></textarea>
                            </div>
                            <button class="btn btn-primary" onclick="sendTestNotification()">
                                <i data-feather="send" class="icon-16"></i> <?php echo app_lang('send_notification'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function testConnection() {
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i data-feather="loader" class="icon-16 spinning"></i> <?php echo app_lang("testing"); ?>...';

        fetch('<?php echo get_uri("line_notify/test_api"); ?>')
        .then(response => response.json())
        .then(data => {
            if (data.bot_info && data.bot_info.success) {
                appAlert.success('<?php echo app_lang("connection_test_successful"); ?>');
            } else {
                appAlert.error('<?php echo app_lang("connection_test_failed"); ?>');
            }
        })
        .catch(error => {
            appAlert.error('<?php echo app_lang("connection_test_error"); ?>: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            feather.replace();
        });
    }

    function triggerEventNotifications() {
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i data-feather="loader" class="icon-16 spinning"></i> <?php echo app_lang("processing"); ?>...';

        fetch('<?php echo get_uri("line_notify/trigger_event_notifications"); ?>', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = data.message || '<?php echo app_lang("notifications_triggered_successfully"); ?>';
                
                if (data.notifications && data.notifications.length > 0) {
                    let details = '\n\n<?php echo app_lang("details"); ?>:\n';
                    data.notifications.forEach(notif => {
                        details += `• ${notif.event} (${notif.type}): ${notif.status}\n`;
                    });
                    appAlert.success(message + details);
                } else {
                    appAlert.success(message);
                }
                
                setTimeout(refreshLogs, 1000);
            } else {
                appAlert.error(data.message || '<?php echo app_lang("failed_to_trigger_notifications"); ?>');
            }
        })
        .catch(error => {
            appAlert.error('<?php echo app_lang("error_triggering_notifications"); ?>: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            feather.replace();
        });
    }

    function triggerTodayEventsOnly() {
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i data-feather="loader" class="icon-16 spinning"></i> <?php echo app_lang("sending"); ?>...';

        fetch('<?php echo get_uri("line_notify/trigger_today_events_only"); ?>', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = data.message || '<?php echo app_lang("today_events_triggered_successfully"); ?>';
                
                if (data.notifications && data.notifications.length > 0) {
                    let details = '\n\n<?php echo app_lang("today_events"); ?>:\n';
                    data.notifications.forEach(notif => {
                        details += `• ${notif.event} (${notif.type}): ${notif.status}\n`;
                        details += `  <?php echo app_lang("date"); ?>: ${notif.event_date}, <?php echo app_lang("time"); ?>: ${notif.event_time}\n`;
                    });
                    appAlert.success(message + details);
                } else {
                    appAlert.success(message + '\n\n<?php echo app_lang("no_events_found_for_today"); ?>');
                }
                
                setTimeout(refreshLogs, 1000);
            } else {
                appAlert.error(data.message || '<?php echo app_lang("failed_to_trigger_today_events"); ?>');
            }
        })
        .catch(error => {
            appAlert.error('<?php echo app_lang("error_triggering_today_events"); ?>: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            feather.replace();
        });
    }

    function sendTestNotification() {
        const message = document.getElementById('test-message').value;
        if (!message.trim()) {
            appAlert.error('<?php echo app_lang("please_enter_a_message"); ?>');
            return;
        }

        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i data-feather="loader" class="icon-16 spinning"></i> <?php echo app_lang("sending"); ?>...';

        const formData = new FormData();
        formData.append('message', message);
        formData.append('type', 'test');

        fetch('<?php echo get_uri("line_notify/send_manual_notification"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                appAlert.success(data.message || '<?php echo app_lang("notification_sent_successfully"); ?>');
                document.getElementById('test-message').value = '';
                setTimeout(refreshLogs, 1000);
            } else {
                appAlert.error(data.message || '<?php echo app_lang("failed_to_send_notification"); ?>');
            }
        })
        .catch(error => {
            appAlert.error('<?php echo app_lang("error_sending_notification"); ?>: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            feather.replace();
        });
    }

    function refreshLogs() {
        fetch('<?php echo get_uri("line_notify/get_notification_logs"); ?>?limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.logs) {
                const tbody = document.getElementById('logs-tbody');
                tbody.innerHTML = '';
                
                if (data.logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted"><?php echo app_lang("no_notification_logs_found"); ?></td></tr>';
                } else {
                    data.logs.forEach(log => {
                        const statusBadge = log.status === 'sent'
                            ? '<span class="badge bg-success"><?php echo app_lang("sent"); ?></span>'
                            : '<span class="badge bg-danger"><?php echo app_lang("failed"); ?></span>';

                        const eventTask = log.event_title || log.task_title || '-';
                        const message = log.message.length > 50 ? log.message.substring(0, 50) + '...' : log.message;
                        const response = log.response || '-';
                        const responseClass = log.status === 'sent' ? 'text-success' : 'text-danger';

                        tbody.innerHTML += `
                            <tr>
                                <td>${log.sent_at}</td>
                                <td><span class="badge bg-info">${log.notification_type}</span></td>
                                <td>${eventTask}</td>
                                <td>${statusBadge}</td>
                                <td class="text-truncate" style="max-width: 200px;" title="${log.message}">${message}</td>
                                <td class="text-truncate" style="max-width: 300px;" title="${response}"><small class="${responseClass}">${response}</small></td>
                            </tr>
                        `;
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error refreshing logs:', error);
        });
    }

    // Auto-refresh logs every 30 seconds
    setInterval(refreshLogs, 30000);

    // Initialize feather icons
    document.addEventListener('DOMContentLoaded', function() {
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

.widget-icon {
    float: left;
    margin-right: 15px;
}

.widget-details {
    overflow: hidden;
}

.widget-details h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.bg-transparent-white {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    text-transform: uppercase;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>