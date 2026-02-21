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
                <a href="<?php echo get_uri('line_settings'); ?>" class="btn btn-default">
                    <i data-feather="settings" class="icon-16"></i> <?php echo app_lang('line_settings'); ?>
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Status Overview -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 widget-container">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-info">
                                <i data-feather="bell" class="icon"></i>
                            </div>
                            <div class="widget-details">
                                <h1><?php echo $line_enabled ? app_lang('enabled') : app_lang('disabled'); ?></h1>
                                <span class="text-muted"><?php echo app_lang('line_status'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 widget-container">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-primary">
                                <i data-feather="settings" class="icon"></i>
                            </div>
                            <div class="widget-details">
                                <h1><?php echo $has_token ? app_lang('configured') : app_lang('not_configured'); ?></h1>
                                <span class="text-muted"><?php echo app_lang('configuration'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 widget-container">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-success">
                                <i data-feather="users" class="icon"></i>
                            </div>
                            <div class="widget-details">
                                <h1><?php echo !empty($user_ids) ? count(explode(',', $user_ids)) : 0; ?></h1>
                                <span class="text-muted"><?php echo app_lang('users'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 widget-container">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-warning">
                                <i data-feather="message-circle" class="icon"></i>
                            </div>
                            <div class="widget-details">
                                <h1><?php echo !empty($group_ids) ? count(explode(',', $group_ids)) : 0; ?></h1>
                                <span class="text-muted"><?php echo app_lang('groups'); ?></span>
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
                                <button class="btn btn-default" onclick="testConnection()">
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

            <!-- Daily Task Reminder (Not Done) -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="clock" class="icon-16"></i> แจ้งเตือนงานค้างประจำวัน</h4>
                            <div class="card-header-action">
                                <button class="btn btn-sm btn-default" onclick="saveTaskReminderSettings(this)">
                                    <i data-feather="save" class="icon-12"></i> บันทึกเวลา
                                </button>
                                <button class="btn btn-sm btn-default" onclick="testTaskReminderNow(this)">
                                    <i data-feather="send" class="icon-12"></i> ส่งทดสอบ
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="task-reminder-enabled" <?php echo !empty($task_reminder_enabled) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="task-reminder-enabled">
                                    เปิดใช้งาน (ส่งไปที่ห้องจาก LINE Settings → Group IDs)
                                </label>
                            </div>
                            <div class="text-muted mb-3" style="font-size:12px">
                                ส่งทุกวันตามเวลาที่กำหนด | ส่งล่าสุด: <?php echo $task_reminder_last_sent ? format_to_datetime($task_reminder_last_sent) : '-'; ?>
                                <br>
                                เวลาระบบ (เครื่องคุณ): <span id="system-time">-</span>
                                | เวลาเซิร์ฟเวอร์ (<?php echo esc($server_timezone); ?>): <?php echo esc($server_time); ?>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>เวลา</th>
                                            <th>การทำซ้ำ</th>
                                            <th>สถานะ</th>
                                            <th style="width:120px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="task-reminder-times-body">
                                        <?php $times = $task_reminder_times ?? ['09:00','13:00']; ?>
                                        <?php foreach ($times as $t): ?>
                                        <tr>
                                            <td style="max-width:160px">
                                                <input type="time" class="form-control form-control-sm task-reminder-time" value="<?php echo esc($t); ?>">
                                            </td>
                                            <td>ทุกวัน</td>
                                            <td>
                                                <?php if (!empty($task_reminder_enabled)): ?>
                                                    <span class="badge bg-success">Enabled</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Disabled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-xs btn-default" onclick="removeTaskReminderTime(this)">ลบ</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <button class="btn btn-sm btn-default" onclick="addTaskReminderTime()">
                                + เพิ่มเวลา
                            </button>
                            <span id="task-reminder-result" style="margin-left:10px;font-size:13px"></span>
                        </div>
                    </div>
                </div>
            </div>

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

            <!-- Upcoming Task Reminders (Not Done) -->
            <?php if (isset($upcoming_tasks) && !empty($upcoming_tasks)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i data-feather="check-square" class="icon-16"></i> งานค้างที่จะแจ้งเตือน</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Task</th>
                                            <th>Assignee</th>
                                            <th>Deadline</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcoming_tasks as $task): ?>
                                        <tr>
                                            <td><?php echo $task->title; ?></td>
                                            <td><?php echo $task->assigned_name ?: '-'; ?></td>
                                            <td><?php echo $task->deadline ? format_to_date($task->deadline, false) : '-'; ?></td>
                                            <td>
                                                <?php if ($task->status_title): ?>
                                                    <span class="badge bg-info"><?php echo $task->status_title; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">-</span>
                                                <?php endif; ?>
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
                            <div class="btn-group mb-3" role="group" aria-label="Log filters">
                                <button type="button" class="btn btn-default btn-sm log-filter-btn active" data-filter="all" onclick="setLogFilter('all')">All</button>
                                <button type="button" class="btn btn-default btn-sm log-filter-btn" data-filter="liff" onclick="setLogFilter('liff')">LIFF</button>
                            </div>
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
        .then(async response => {
            const text = await response.text();
            if (!response.ok) {
                console.error('[LINE Notify] test_api HTTP', response.status, text);
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('[LINE Notify] test_api JSON parse error', e, text);
                throw e;
            }
        })
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
        .then(async response => {
            const text = await response.text();
            if (!response.ok) {
                console.error('[LINE Notify] trigger_event_notifications HTTP', response.status, text);
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('[LINE Notify] trigger_event_notifications JSON parse error', e, text);
                throw e;
            }
        })
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
        .then(async response => {
            const text = await response.text();
            if (!response.ok) {
                console.error('[LINE Notify] trigger_today_events_only HTTP', response.status, text);
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('[LINE Notify] trigger_today_events_only JSON parse error', e, text);
                throw e;
            }
        })
        .then(data => {
            console.log('[LINE Notify Events] Full response:', JSON.stringify(data, null, 2));
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
                console.error('[LINE Notify Events] FAILED:', JSON.stringify(data, null, 2));
                appAlert.error(data.message || '<?php echo app_lang("failed_to_trigger_today_events"); ?>');
            }
        })
        .catch(error => {
            console.error('[LINE Notify Events] Fetch error:', error);
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

        console.log('[LINE Notify] send_manual_notification request', {
            url: '<?php echo get_uri("line_notify/send_manual_notification"); ?>',
            type: 'test',
            message_length: message.length
        });

        fetch('<?php echo get_uri("line_notify/send_manual_notification"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            console.log('[LINE Notify] send_manual_notification response', {
                status: response.status,
                ok: response.ok,
                text: text
            });
            if (!response.ok) {
                console.error('[LINE Notify] send_manual_notification HTTP', response.status, text);
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('[LINE Notify] send_manual_notification JSON parse error', e, text);
                throw e;
            }
        })
        .then(data => {
            console.log('[LINE Notify] send_manual_notification parsed', data);
            console.log('[LINE Notify] Full response:', JSON.stringify(data, null, 2));
            if (data.success) {
                appAlert.success(data.message || '<?php echo app_lang("notification_sent_successfully"); ?>');
                document.getElementById('test-message').value = '';
                setTimeout(refreshLogs, 1000);
            } else {
                console.error('[LINE Notify] FAILED:', data.error_detail || 'unknown');
                console.error('[LINE Notify] Debug info:', JSON.stringify(data.debug, null, 2));
                appAlert.error((data.message || '<?php echo app_lang("failed_to_send_notification"); ?>') + (data.error_detail ? ' - ' + data.error_detail : ''));
            }
        })
        .catch(error => {
            console.error('[LINE Notify] Fetch error:', error);
            appAlert.error('<?php echo app_lang("error_sending_notification"); ?>: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            feather.replace();
        });
    }

    let currentLogFilter = 'all';

    function setLogFilter(filter) {
        currentLogFilter = filter || 'all';
        document.querySelectorAll('.log-filter-btn').forEach(btn => {
            const isActive = btn.dataset.filter === currentLogFilter;
            btn.classList.toggle('active', isActive);
            btn.classList.toggle('btn-primary', isActive);
            btn.classList.toggle('btn-default', !isActive);
        });
        refreshLogs();
    }

    function refreshLogs() {
        let url = '<?php echo get_uri("line_notify/get_notification_logs"); ?>?limit=20';
        if (currentLogFilter && currentLogFilter !== 'all') {
            url += '&type_group=' + encodeURIComponent(currentLogFilter);
        }
        fetch(url)
        .then(async response => {
            const text = await response.text();
            if (!response.ok) {
                console.error('[LINE Notify] get_notification_logs HTTP', response.status, text);
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('[LINE Notify] get_notification_logs JSON parse error', e, text);
                throw e;
            }
        })
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

    function addTaskReminderTime() {
        const tbody = document.getElementById('task-reminder-times-body');
        if (!tbody) return;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td style="max-width:160px">
                <input type="time" class="form-control form-control-sm task-reminder-time" value="09:00">
            </td>
            <td>ทุกวัน</td>
            <td><span class="badge bg-secondary">Disabled</span></td>
            <td><button class="btn btn-xs btn-default" onclick="removeTaskReminderTime(this)">ลบ</button></td>
        `;
        tbody.appendChild(row);
        feather.replace();
    }

    function removeTaskReminderTime(btn) {
        const row = btn.closest('tr');
        if (row) row.remove();
    }

    function saveTaskReminderSettings(btn) {
        const enabled = document.getElementById('task-reminder-enabled').checked;
        const times = Array.from(document.querySelectorAll('.task-reminder-time'))
            .map(i => (i.value || '').trim())
            .filter(Boolean);

        if (times.length === 0) {
            appAlert.error('กรุณาระบุเวลาอย่างน้อย 1 ช่วง');
            return;
        }

        const formData = new FormData();
        if (enabled) { formData.append('reminder_enabled', '1'); }
        times.forEach(t => formData.append('reminder_times[]', t));

        const result = document.getElementById('task-reminder-result');
        if (btn) {
            btn.disabled = true;
            btn.dataset.original = btn.innerHTML;
            btn.innerHTML = '<i data-feather="loader" class="icon-12 spinning"></i> กำลังบันทึก...';
        }

        fetch('<?php echo get_uri("line_notify/save_task_reminder_settings"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                appAlert.success(data.message || 'บันทึกสำเร็จ');
                if (result) result.textContent = data.message || 'บันทึกสำเร็จ';
            } else {
                appAlert.error(data.message || 'บันทึกไม่สำเร็จ');
                if (result) result.textContent = data.message || 'บันทึกไม่สำเร็จ';
            }
        })
        .catch(err => {
            appAlert.error('บันทึกไม่สำเร็จ: ' + err.message);
            if (result) result.textContent = 'บันทึกไม่สำเร็จ';
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = btn.dataset.original || btn.innerHTML;
                feather.replace();
            }
        });
    }

    function testTaskReminderNow(btn) {
        if (btn) {
            btn.disabled = true;
            btn.dataset.original = btn.innerHTML;
            btn.innerHTML = '<i data-feather="loader" class="icon-12 spinning"></i> กำลังส่ง...';
        }

        fetch('<?php echo get_uri("line_notify/test_task_reminder"); ?>', {
            method: 'POST'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                appAlert.success(data.message || 'ส่งสำเร็จ');
            } else {
                appAlert.error(data.message || 'ส่งไม่สำเร็จ');
            }
        })
        .catch(err => {
            appAlert.error('ส่งไม่สำเร็จ: ' + err.message);
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = btn.dataset.original || btn.innerHTML;
                feather.replace();
            }
        });
    }

    // Auto-refresh logs every 30 seconds
    setInterval(refreshLogs, 30000);

    // Initialize feather icons
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
        setLogFilter(currentLogFilter);
        updateSystemTime();
        setInterval(updateSystemTime, 1000);
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
    margin-right: 12px;
}

.widget-details {
    overflow: hidden;
}

.widget-details h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 600;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
function updateSystemTime() {
    const el = document.getElementById('system-time');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleString();
}
</script>
