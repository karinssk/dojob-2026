<?php

namespace App\Controllers;

class Line_notify extends Security_Controller {

    protected $db;

    function __construct() {
        // Initialize with authentication but don't redirect immediately
        parent::__construct(false);
        $this->db = \Config\Database::connect();
    }

    function index() {
        // Check authentication for index page
        $login_user_id = $this->Users_model->login_user_id();
        if (!$login_user_id) {
            $uri_string = uri_string();
            app_redirect('signin?redirect=' . get_uri($uri_string));
        }
        
        $view_data = array();
        $view_data['page_type'] = "full";
        
        // Get LINE bot information if configured
        $Line_webhook = new \App\Libraries\Line_webhook();
        $bot_info = $Line_webhook->get_bot_info();
        $view_data['bot_info'] = $bot_info;
        
        // Get current settings
        $view_data['line_enabled'] = get_setting('enable_line_notifications');
        $view_data['has_token'] = !empty(get_setting('line_channel_access_token'));
        $view_data['user_ids'] = get_setting('line_user_ids');
        $view_data['group_ids'] = get_setting('line_group_ids');
        
        // Get recent tasks for demonstration
        $view_data['recent_tasks'] = $this->get_sample_tasks();
        
        // Get notification statistics
        $view_data['notification_stats'] = $this->get_notification_statistics();
        
        // Get upcoming events with LINE notifications enabled
        $view_data['upcoming_events'] = $this->get_upcoming_line_events();
        
        // Get recent notification logs
        $view_data['recent_logs'] = $this->get_recent_notification_logs();
        
        return $this->template->rander("line_notify/index", $view_data);
    }

    /**
     * Handle LINE webhook events (for payment button actions)
     */
    function webhook() {
        // Prevent session initialization to avoid header issues
        if (session_status() === PHP_SESSION_NONE) {
            // Don't start session for webhook
        }
        
        // Force return plain text response for LINE
        $this->response->setContentType('text/plain');
        
        // Log the incoming request for debugging
        $timestamp = date('Y-m-d H:i:s');
        $method = $this->request->getMethod();
        $user_agent = $this->request->getUserAgent();
        
        error_log("=== LINE Webhook Called at $timestamp ===");
        error_log('LINE Webhook - Method: ' . $method);
        error_log('LINE Webhook - User Agent: ' . $user_agent);
        
        log_message('info', "=== LINE Webhook Called at $timestamp ===");
        log_message('info', 'LINE Webhook - Method: ' . $method);
        log_message('info', 'LINE Webhook - User Agent: ' . $user_agent);
        log_message('info', 'LINE Webhook - Headers: ' . json_encode($this->request->getHeaders()));
        
        // Get the raw POST data
        $input = file_get_contents('php://input');
        log_message('info', 'LINE Webhook - Raw input length: ' . strlen($input));
        log_message('info', 'LINE Webhook - Raw input: ' . $input);
        
        if (empty($input)) {
            log_message('error', 'LINE Webhook: Empty input received');
            return $this->response->setBody('OK');
        }

        $channel_secret = get_setting('line_channel_secret');
        if ($channel_secret) {
            $signature = $this->request->getHeaderLine('X-Line-Signature');
            if (!$this->verify_line_signature($input, $signature, $channel_secret)) {
                log_message('error', 'LINE Webhook: Invalid signature');
                return $this->response->setStatusCode(401)->setBody('Invalid signature');
            }
        }
        
        $events = json_decode($input, true);
        
        if (!$events) {
            log_message('error', 'LINE Webhook: Invalid JSON received: ' . $input);
            return $this->response->setBody('OK');
        }
        
        if (!isset($events['events'])) {
            log_message('error', 'LINE Webhook: No events array in JSON: ' . json_encode($events));
            return $this->response->setBody('OK');
        }
        
        log_message('info', 'LINE Webhook: Processing ' . count($events['events']) . ' events');
        
        foreach ($events['events'] as $event) {
            $this->capture_line_room($event);
            $this->capture_line_user($event);

            log_message('info', 'LINE Webhook: Event details: ' . json_encode($event));
            log_message('info', 'LINE Webhook: Event type: ' . ($event['type'] ?? 'unknown'));
            
            if ($event['type'] === 'postback') {
                log_message('info', 'LINE Webhook: Processing postback event');
                $this->handle_postback_event($event);
            } elseif ($event['type'] === 'message') {
                log_message('info', 'LINE Webhook: Processing message event');
                $this->handle_message_event($event);
            } elseif ($event['type'] === 'follow') {
                log_message('info', 'LINE Webhook: Processing follow event');
                $this->handle_follow_event($event);
            } elseif ($event['type'] === 'unfollow') {
                log_message('info', 'LINE Webhook: Processing unfollow event');
                $this->handle_unfollow_event($event);
            } else {
                log_message('info', 'LINE Webhook: Unknown event type: ' . ($event['type'] ?? 'null'));
            }
        }
        
        log_message('info', 'LINE Webhook: Completed processing, sending OK response');
        return $this->response->setBody('OK');
    }
    
    /**
     * Handle postback events from LINE flex message buttons
     */
    private function handle_postback_event($event) {
        try {
            $postback_data = $event['postback']['data'] ?? '';
            $reply_token = $event['replyToken'] ?? '';
            
            log_message('info', 'LINE Webhook: Postback data received: ' . $postback_data);
            log_message('info', 'LINE Webhook: Reply token: ' . $reply_token);
            
            if (empty($postback_data)) {
                log_message('error', 'LINE Webhook: Empty postback data');
                return;
            }
            
            if (empty($reply_token)) {
                log_message('error', 'LINE Webhook: No reply token provided');
                return;
            }
            
            parse_str($postback_data, $params);
            log_message('info', 'LINE Webhook: Parsed params: ' . json_encode($params));
            
            $action = $params['action'] ?? '';
            $event_id = $params['event_id'] ?? 0;
            
            if (!$event_id) {
                log_message('error', 'LINE Webhook: No event_id in postback data: ' . json_encode($params));
                return;
            }
            
            $Line_logs_model = model('App\Models\Line_notification_logs_model');
            $Line_webhook = new \App\Libraries\Line_webhook();
            
            // Get event details
            $events_table = $this->db->prefixTable('events');
            $sql = "SELECT * FROM {$events_table} WHERE id = ? AND deleted = 0";
            $event_obj = $this->db->query($sql, [$event_id])->getRow();
            
            if (!$event_obj) {
                log_message('error', "LINE Webhook: Event {$event_id} not found");
                return;
            }
            
            $user_id = $event['source']['userId'] ?? 'unknown';
            log_message('info', "LINE Webhook: Processing action '{$action}' for event {$event_id} by user {$user_id}");
            
            switch ($action) {
                case 'mark_paid':
                    // Update payment status to paid (1)
                    error_log("DEBUG: Looking for existing notification for event_id: $event_id");
                    $notification = $Line_logs_model->get_by_event_id($event_id);
                    error_log("DEBUG: Found existing notification: " . ($notification ? 'YES (ID: ' . $notification['id'] . ')' : 'NO'));
                    
                    if ($notification) {
                        error_log("DEBUG: Updating existing record ID {$notification['id']} to paid_status=1");
                        $update_success = $Line_logs_model->update_paid_status($notification['id'], 1);
                        error_log("DEBUG: Update result: " . ($update_success ? 'SUCCESS' : 'FAILED'));
                        log_message('info', "LINE Webhook: Event {$event_id} marked as PAID by user {$user_id} - Update success: " . ($update_success ? 'YES' : 'NO'));
                    } else {
                        // If no notification record exists, create one with paid status
                        error_log("DEBUG: No existing record found, creating new record for event_id: $event_id");
                        log_message('info', "LINE Webhook: Creating new notification record for event {$event_id} with PAID status");
                        $log_data = [
                            'event_id' => $event_id,
                            'notification_type' => 'payment_status',
                            'message' => 'Payment status updated via button click',
                            'status' => 'sent',
                            'paid_status' => 1
                        ];
                        $save_result = $Line_logs_model->ci_save($log_data);
                        error_log("DEBUG: New record creation result: " . ($save_result ? 'SUCCESS' : 'FAILED'));
                    }
                    
                    // Also save to a simple log table for debugging
                    $this->save_button_click_log($event_id, 'paid', $user_id);
                    
                    // Send reply message using reply token
                    $reply_message = "✅ ยืนยันการชำระเงิน\n\nรายการ: {$event_obj->title}\nรายละเอียด: {$event_obj->description}\n\nได้เปลี่ยนสถานะเป็น ชำระแล้ว";
                    $confirmation_success = $Line_webhook->send_reply_message($reply_token, $reply_message);
                    
                    
                    error_log("DEBUG: Paid confirmation reply sent result: " . ($confirmation_success ? 'SUCCESS' : 'FAILED'));
                    log_message('info', "LINE Webhook: Confirmation reply sent: " . ($confirmation_success ? 'YES' : 'NO'));
                    break;
                    
                case 'mark_waiting':
                    // Update payment status to waiting (0)
                    error_log("DEBUG: Looking for existing notification for event_id: $event_id");
                    $notification = $Line_logs_model->get_by_event_id($event_id);
                    error_log("DEBUG: Found existing notification: " . ($notification ? 'YES (ID: ' . $notification['id'] . ')' : 'NO'));
                    
                    if ($notification) {
                        error_log("DEBUG: Updating existing record ID {$notification['id']} to paid_status=0");
                        $update_success = $Line_logs_model->update_paid_status($notification['id'], 0);
                        error_log("DEBUG: Update result: " . ($update_success ? 'SUCCESS' : 'FAILED'));
                        log_message('info', "LINE Webhook: Event {$event_id} marked as WAITING by user {$user_id} - Update success: " . ($update_success ? 'YES' : 'NO'));
                    } else {
                        // If no notification record exists, create one with waiting status
                        error_log("DEBUG: No existing record found, creating new record for event_id: $event_id");
                        log_message('info', "LINE Webhook: Creating new notification record for event {$event_id} with WAITING status");
                        $log_data = [
                            'event_id' => $event_id,
                            'notification_type' => 'payment_status',
                            'message' => 'Payment status updated via button click',
                            'status' => 'sent',
                            'paid_status' => 0
                        ];
                        $save_result = $Line_logs_model->ci_save($log_data);
                        error_log("DEBUG: New record creation result: " . ($save_result ? 'SUCCESS' : 'FAILED'));
                    }
                    
                    // Also save to a simple log table for debugging
                    $this->save_button_click_log($event_id, 'waiting', $user_id);
                    
                    // Send reply message using reply token
                    $reply_message = "⏳ อัปเดตสถานะการชำระเงิน\n\nกิจกรรม '{$event_obj->title}' ถูกทำเครื่องหมายเป็น รอชำระ\n\nกรุณาทำการชำระเงินเมื่อพร้อม";
                    $confirmation_success = $Line_webhook->send_reply_message($reply_token, $reply_message);
                    
                    error_log("DEBUG: Waiting confirmation reply sent result: " . ($confirmation_success ? 'SUCCESS' : 'FAILED'));
                    log_message('info', "LINE Webhook: Confirmation reply sent: " . ($confirmation_success ? 'YES' : 'NO'));
                    break;
                    
                default:
                    log_message('warning', "LINE Webhook: Unknown action '{$action}' for event {$event_id}");
                    // Send error reply
                    $error_message = "❌ ไม่รู้จักคำสั่งนี้ กรุณาลองใหม่อีกครั้งหรือติดต่อฝ่ายสนับสนุน";
                    $Line_webhook->send_reply_message($reply_token, $error_message);
                    break;
            }
        } catch (\Exception $e) {
            log_message('error', 'LINE Webhook: Exception in handle_postback_event: ' . $e->getMessage());
            error_log('LINE Webhook: Exception in handle_postback_event: ' . $e->getMessage());
        }
    }

    private function handle_line_event($event) {
        // Handle different types of LINE events
        switch ($event['type']) {
            case 'message':
                $this->handle_message_event($event);
                break;
            case 'follow':
                $this->handle_follow_event($event);
                break;
            case 'unfollow':
                $this->handle_unfollow_event($event);
                break;
        }
    }

    private function handle_message_event($event) {
        $user_id = $event['source']['userId'] ?? null;
        $message_text = $event['message']['text'] ?? '';
        
        // Log the message for debugging
        log_message('info', "LINE Message from {$user_id}: {$message_text}");

        if ($this->is_task_tracking_keyword($message_text)) {
            $this->handle_task_tracking_request($event);
            return;
        }
        
        // Simple command handling
        if (strtolower($message_text) === 'help') {
            $this->send_help_message($user_id);
        } elseif (strtolower($message_text) === 'tasks') {
            $this->send_tasks_summary($user_id);
        } elseif (strtolower($message_text) === 'test') {
            $this->send_test_response($user_id);
        }
    }

    private function handle_follow_event($event) {
        $user_id = $event['source']['userId'] ?? null;
        if ($user_id) {
            log_message('info', "New LINE follower: {$user_id}");
            $this->send_welcome_message($user_id);
        }
    }

    private function handle_unfollow_event($event) {
        $user_id = $event['source']['userId'] ?? null;
        if ($user_id) {
            log_message('info', "LINE unfollower: {$user_id}");
        }
    }

    private function send_welcome_message($user_id) {
        $Line_webhook = new \App\Libraries\Line_webhook();
        $message = "🎉 Welcome to Task Management System!\n\n";
        $message .= "I'll help you stay on top of your tasks and deadlines.\n\n";
        $message .= "Available commands:\n";
        $message .= "• 'help' - Show available commands\n";
        $message .= "• 'tasks' - Get tasks summary\n";
        $message .= "• 'test' - Test connection\n\n";
        $message .= "You'll receive automatic reminders for upcoming deadlines!";
        
        // Send directly to this user
        $Line_webhook->send_push_message($user_id, $message, 'user');
    }

    private function send_help_message($user_id) {
        $Line_webhook = new \App\Libraries\Line_webhook();
        $message = "📋 Task Management System Help\n\n";
        $message .= "Available commands:\n";
        $message .= "• 'help' - Show this help message\n";
        $message .= "• 'tasks' - Get summary of your tasks\n";
        $message .= "• 'test' - Test system connection\n\n";
        $message .= "🔔 Automatic notifications:\n";
        $message .= "• Deadline reminders (3 days before)\n";
        $message .= "• Due today alerts\n";
        $message .= "• Overdue task warnings\n";
        $message .= "• New recurring task notifications";
        
        $Line_webhook->send_push_message($user_id, $message, 'user');
    }

    private function send_tasks_summary($user_id) {
        $Line_webhook = new \App\Libraries\Line_webhook();
        
        // Get tasks summary (simplified for demo)
        $today = date('Y-m-d');
        $upcoming_date = date('Y-m-d', strtotime('+7 days'));
        
        $message = "📊 Tasks Summary\n\n";
        $message .= "📅 Today: " . date('M j, Y') . "\n\n";
        $message .= "🔴 Due Today: 2 tasks\n";
        $message .= "⏰ Due This Week: 5 tasks\n";
        $message .= "📋 Total Active: 12 tasks\n\n";
        $message .= "💡 Use the web interface for detailed task management.";
        
        $Line_webhook->send_push_message($user_id, $message, 'user');
    }

    private function send_test_response($user_id) {
        $Line_webhook = new \App\Libraries\Line_webhook();
        $message = "✅ Test Successful!\n\n";
        $message .= "🤖 Bot is working correctly\n";
        $message .= "📡 Connection established\n";
        $message .= "⏰ Time: " . date('Y-m-d H:i:s') . "\n\n";
        $message .= "You're all set to receive task notifications!";
        
        $Line_webhook->send_push_message($user_id, $message, 'user');
    }

    function send_manual_notification() {
        // API endpoint for sending manual notifications
        $message = $this->request->getPost('message');
        $type = $this->request->getPost('type') ?: 'info';

        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message is required']);
            return;
        }

        $Line_webhook = new \App\Libraries\Line_webhook();
        $formatted_message = "📢 Manual Notification\n\n" . $message;

        $success = $Line_webhook->send_notification($formatted_message, ['type' => $type]);
        log_message('info', 'LINE Notify: manual notification result=' . ($success ? 'success' : 'fail') . ' error=' . ($Line_webhook->last_error ?? ''));

        $response = [
            'success' => $success,
            'message' => $success ? 'Notification sent successfully!' : 'Failed to send notification',
        ];

        if (!$success) {
            $response['error_detail'] = $Line_webhook->last_error;
            log_message('error', 'LINE Notify: manual notification failed. ' . ($Line_webhook->last_error ?? 'unknown'));
            $response['debug'] = [
                'enabled' => get_setting('enable_line_notifications'),
                'has_token' => !empty(get_setting('line_channel_access_token')),
                'user_ids' => get_setting('line_user_ids'),
                'group_ids' => get_setting('line_group_ids'),
                'formatted_message' => $formatted_message,
            ];
        }

        echo json_encode($response);
    }

    function get_bot_status() {
        // API endpoint for getting bot status
        $Line_webhook = new \App\Libraries\Line_webhook();
        $bot_info = $Line_webhook->get_bot_info();
        
        $status = [
            'enabled' => get_setting('enable_line_notifications'),
            'configured' => !empty(get_setting('line_channel_access_token')),
            'user_ids_count' => count(explode(',', get_setting('line_user_ids') ?: '')),
            'group_ids_count' => count(explode(',', get_setting('line_group_ids') ?: '')),
            'bot_info' => $bot_info
        ];
        
        echo json_encode($status);
    }

    private function get_sample_tasks() {
        // Get some sample tasks for display
        try {
            $tasks = $this->Tasks_model->get_details([
                'limit' => 5,
                'status_ids' => '1,2,3', // Active tasks only
                'order_by' => 'deadline',
                'order_dir' => 'ASC'
            ]);
            
            return is_array($tasks) ? $tasks['data'] : $tasks->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    function setup_guide() {
        // Show setup guide page
        $view_data = array();
        $view_data['page_type'] = "full";
        return $this->template->view("line_notify/setup_guide", $view_data);
    }

    function test_api() {
        // Test API endpoint
        $Line_webhook = new \App\Libraries\Line_webhook();
        
        $tests = [
            'bot_info' => $Line_webhook->get_bot_info(),
            'connection' => $Line_webhook->test_connection()
        ];
        
        echo json_encode($tests);
    }

    private function get_notification_statistics() {
        try {
            $Line_logs_model = model('App\Models\Line_notification_logs_model');
            
            // Try to create table if it doesn't exist
            $Line_logs_model->create_table_if_not_exists();
            
            // Get statistics for last 30 days
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $end_date = date('Y-m-d');
            
            return $Line_logs_model->get_statistics([
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get notification statistics: ' . $e->getMessage());
            return (object) [
                'total_notifications' => 0,
                'successful_notifications' => 0,
                'failed_notifications' => 0,
                'task_notifications' => 0,
                'event_notifications' => 0
            ];
        }
    }

    private function get_upcoming_line_events() {
        try {
            $today = date('Y-m-d');
            $future_date = date('Y-m-d', strtotime('+30 days'));
            
            $events = $this->Events_model->get_details([
                'start_date' => $today,
                'end_date' => $future_date,
                'type' => 'event'
            ])->getResult();
            
            // Filter only events with LINE notifications enabled and validate data
            $line_events = [];
            foreach ($events as $event) {
                if ($event->line_notify_enabled) {
                    // Validate and clean event data
                    $event = $this->validate_event_data($event);
                    $line_events[] = $event;
                }
            }
            
            return array_slice($line_events, 0, 10); // Limit to 10 events
        } catch (\Exception $e) {
            log_message('error', 'Failed to get upcoming LINE events: ' . $e->getMessage());
            return [];
        }
    }

    private function validate_event_data($event) {
        // Validate and fix time formats
        if (!empty($event->start_time)) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $event->start_time)) {
                $event->start_time = '00:00:00';
            }
        }
        
        if (!empty($event->end_time)) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $event->end_time)) {
                $event->end_time = '00:00:00';
            }
        }
        
        return $event;
    }

    private function get_recent_notification_logs() {
        try {
            $Line_logs_model = model('App\Models\Line_notification_logs_model');
            
            return $Line_logs_model->get_details([
                'limit' => 20,
                'start_date' => date('Y-m-d', strtotime('-7 days'))
            ])->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    function get_notification_logs() {
        // API endpoint for getting notification logs with pagination
        $limit = $this->request->getGet('limit') ?: 50;
        $offset = $this->request->getGet('offset') ?: 0;
        $status = $this->request->getGet('status');
        $type = $this->request->getGet('type');
        $start_date = $this->request->getGet('start_date');
        $end_date = $this->request->getGet('end_date');
        
        try {
            $Line_logs_model = model('App\Models\Line_notification_logs_model');
            
            $options = [
                'limit' => $limit,
                'offset' => $offset
            ];
            
            if ($status) {
                $options['status'] = $status;
            }
            
            if ($type) {
                $options['notification_type'] = $type;
            }
            
            if ($start_date) {
                $options['start_date'] = $start_date;
            }
            
            if ($end_date) {
                $options['end_date'] = $end_date;
            }
            
            $logs = $Line_logs_model->get_details($options)->getResult();
            $stats = $Line_logs_model->get_statistics($options);
            
            echo json_encode([
                'success' => true,
                'logs' => $logs,
                'statistics' => $stats
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch notification logs: ' . $e->getMessage()
            ]);
        }
    }

    function get_upcoming_events() {
        // API endpoint for getting upcoming events with LINE notifications
        try {
            $events = $this->get_upcoming_line_events();
            
            echo json_encode([
                'success' => true,
                'events' => $events
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch upcoming events: ' . $e->getMessage()
            ]);
        }
    }

    function trigger_event_notifications() {
        // Manual trigger for event notifications (for testing)
        if (!get_setting('enable_line_notifications')) {
            echo json_encode([
                'success' => false,
                'message' => 'LINE notifications are disabled'
            ]);
            return;
        }

        try {
            $Line_webhook = new \App\Libraries\Line_webhook();
            $Line_logs_model = model('App\Models\Line_notification_logs_model');
            $today = date('Y-m-d');
            $current_time = date('H:i:s');
            $sent_notifications = [];
            $skipped_notifications = [];
            $processed_events = []; // Track processed events to avoid duplicates
            
            // Debug: Show what today is
            log_message('info', "Manual trigger - Today is: {$today}, Current time: {$current_time}");

            // Check for today's events
            $today_events = $this->Events_model->get_details([
                'start_date' => $today,
                'end_date' => $today,
                'type' => 'event'
            ])->getResult();

            foreach ($today_events as $event) {
                if ($event->line_notify_enabled && !in_array($event->id, $processed_events)) {
                    $processed_events[] = $event->id;
                    
                    // Determine if event is overdue (time has passed) or still upcoming today
                    $event_time = $event->start_time ?: '00:00:00';
                    $is_overdue = ($current_time > $event_time);
                    
                    $notification_type = $is_overdue ? 'overdue' : 'on_event';
                    $display_type = $is_overdue ? 'overdue (today)' : 'today';
                    
                    // Check if notification already sent today
                    $already_sent = $Line_logs_model->check_notification_sent($event->id, $notification_type, $today);
                    
                    if (!$already_sent) {
                        $success = $Line_webhook->send_event_reminder($event, $notification_type);
                        $sent_notifications[] = [
                            'event' => $event->title,
                            'type' => $display_type,
                            'status' => $success ? 'sent' : 'failed'
                        ];
                    } else {
                        $skipped_notifications[] = [
                            'event' => $event->title,
                            'type' => $display_type,
                            'reason' => 'already_sent_today'
                        ];
                    }
                }
            }

            // Check for overdue events (past days, EXCLUDING today and future)
            $past_date = date('Y-m-d', strtotime('-7 days'));
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            
            // Make sure we only get events that are actually in the past
            $overdue_events = $this->Events_model->get_details([
                'start_date' => $past_date,
                'end_date' => $yesterday,
                'type' => 'event'
            ])->getResult();
            
            // STRICT filter to ensure we only process truly overdue events
            $filtered_overdue_events = [];
            foreach ($overdue_events as $event) {
                $event_date = date('Y-m-d', strtotime($event->start_date));
                
                // ONLY include if event date is STRICTLY less than today
                if ($event_date < $today) {
                    $filtered_overdue_events[] = $event;
                    log_message('info', "Including overdue event: {$event->title} ({$event_date}) - Today: {$today}");
                } else {
                    log_message('warning', "SKIPPING event {$event->title} ({$event_date}) - Not actually overdue (Today: {$today})");
                }
            }
            $overdue_events = $filtered_overdue_events;

            // TEMPORARILY DISABLED - Overdue processing causing issues with tomorrow events
            // foreach ($overdue_events as $event) {
            //     if ($event->line_notify_enabled && !in_array($event->id, $processed_events)) {
            //         $processed_events[] = $event->id;
            //         
            //         // Check if overdue notification already sent today
            //         $already_sent = $Line_logs_model->check_notification_sent($event->id, 'overdue', $today);
            //         
            //         if (!$already_sent) {
            //             $success = $Line_webhook->send_event_reminder($event, 'overdue');
            //             $sent_notifications[] = [
            //                 'event' => $event->title,
            //                 'type' => 'overdue',
            //                 'status' => $success ? 'sent' : 'failed'
            //             ];
            //         } else {
            //             $skipped_notifications[] = [
            //                 'event' => $event->title,
            //                 'type' => 'overdue',
            //                 'reason' => 'already_sent_today'
            //             ];
            //         }
            //     }
            // }
            
            // Add note about disabled overdue processing
            $skipped_notifications[] = [
                'event' => 'Overdue processing',
                'type' => 'system',
                'reason' => 'temporarily_disabled_for_debugging'
            ];

            // Check for upcoming events (next 3 days, EXCLUDING today)
            $reminder_days = get_setting('line_reminder_days_before') ?: 3;
            $future_date = date('Y-m-d', strtotime("+{$reminder_days} days"));
            
            $upcoming_events = $this->Events_model->get_details([
                'start_date' => $future_date,
                'end_date' => $future_date,
                'type' => 'event'
            ])->getResult();

            foreach ($upcoming_events as $event) {
                if ($event->line_notify_enabled && !in_array($event->id, $processed_events)) {
                    $processed_events[] = $event->id;
                    
                    // Check if reminder notification already sent for this date
                    $already_sent = $Line_logs_model->check_notification_sent($event->id, 'before_event', $today);
                    
                    if (!$already_sent) {
                        $success = $Line_webhook->send_event_reminder($event, 'before_event');
                        $sent_notifications[] = [
                            'event' => $event->title,
                            'type' => 'upcoming',
                            'status' => $success ? 'sent' : 'failed'
                        ];
                    } else {
                        $skipped_notifications[] = [
                            'event' => $event->title,
                            'type' => 'upcoming',
                            'reason' => 'already_sent_today'
                        ];
                    }
                }
            }

            $total_sent = count(array_filter($sent_notifications, function($n) { return $n['status'] === 'sent'; }));
            $total_failed = count(array_filter($sent_notifications, function($n) { return $n['status'] === 'failed'; }));
            $total_skipped = count($skipped_notifications);

            echo json_encode([
                'success' => true,
                'message' => "Processed " . (count($sent_notifications) + $total_skipped) . " notifications. Sent: {$total_sent}, Failed: {$total_failed}, Skipped: {$total_skipped}",
                'notifications' => $sent_notifications,
                'skipped' => $skipped_notifications,
                'summary' => [
                    'total' => count($sent_notifications) + $total_skipped,
                    'sent' => $total_sent,
                    'failed' => $total_failed,
                    'skipped' => $total_skipped
                ],
                'debug' => [
                    'today' => $today,
                    'current_time' => $current_time,
                    'processed_events' => $processed_events
                ]
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error triggering notifications: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment status flex message for a specific event
     */
    function send_payment_status() {
        $event_id = $this->request->getPost('event_id');
        $paid_status = $this->request->getPost('paid_status') ?? 0;
        
        if (!$event_id) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            return;
        }
        
        try {
            // Get event details
            $events_table = $this->db->prefixTable('events');
            $sql = "SELECT * FROM {$events_table} WHERE id = ? AND deleted = 0";
            $event = $this->db->query($sql, [$event_id])->getRow();
            
            if (!$event) {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
                return;
            }
            
            $Line_webhook = new \App\Libraries\Line_webhook();
            $success = $Line_webhook->send_event_payment_status_flex($event, $paid_status);
            
            echo json_encode([
                'success' => $success,
                'message' => $success 
                    ? "Payment status flex message sent for event '{$event->title}'" 
                    : 'Failed to send payment status flex message'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', "Send payment status error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error sending payment status: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Test webhook endpoint functionality
     */
    function test_webhook() {
        // Create a test postback event to verify webhook processing
        $test_event = [
            'type' => 'postback',
            'postback' => [
                'data' => 'action=mark_paid&event_id=999'
            ],
            'source' => [
                'userId' => 'test_user_123'
            ],
            'timestamp' => time() * 1000
        ];
        
        $test_payload = [
            'events' => [$test_event]
        ];
        
        log_message('info', 'Testing webhook with payload: ' . json_encode($test_payload));
        
        // Simulate the webhook call
        try {
            $this->handle_postback_event($test_event);
            
            echo json_encode([
                'success' => true,
                'message' => 'Webhook test completed. Check application logs for details.',
                'test_payload' => $test_payload
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
                'test_payload' => $test_payload
            ]);
        }
    }
    
    /**
     * Cron endpoint to automatically send today's payment status flex messages
     */
    function cron() {
        // Set headers for plain text output
        $this->response->setContentType('text/plain');
        
        echo "=== LINE Payment Status Flex Message Cron ===\n";
        echo "Executed at: " . date('Y-m-d H:i:s') . "\n\n";
        
        if (!get_setting('enable_line_notifications')) {
            echo "❌ LINE notifications are disabled\n";
            return $this->response->setBody(ob_get_clean());
        }

        try {
            $Line_webhook = new \App\Libraries\Line_webhook();
            $today = date('Y-m-d');
            $sent_messages = [];
            $errors = [];
            
            echo "📅 Processing events for today: $today\n\n";

            // Get today's events only (strict date filtering)
            $today_events = $this->Events_model->get_details([
                'start_date' => $today,
                'end_date' => $today,
                'type' => 'event'
            ])->getResult();
            
            // Additional filtering to ensure ONLY today's events
            $filtered_today_events = [];
            foreach ($today_events as $event) {
                $event_date = date('Y-m-d', strtotime($event->start_date));
                if ($event_date === $today) {
                    $filtered_today_events[] = $event;
                    echo "✅ Including event: {$event->title} (Date: {$event_date})\n";
                } else {
                    echo "❌ EXCLUDING event: {$event->title} (Date: {$event_date}) - Not today ({$today})\n";
                }
            }
            $today_events = $filtered_today_events;

            if (empty($today_events)) {
                echo "ℹ️  No events found for today ($today) after filtering\n";
                return $this->response->setBody(ob_get_clean());
            }

            echo "\nAfter strict filtering - Found " . count($today_events) . " events for TODAY ONLY:\n";

            foreach ($today_events as $event) {
                $event_date = date('Y-m-d', strtotime($event->start_date));
                echo "\n--- Processing Event: {$event->title} (ID: {$event->id}) ---\n";
                echo "📅 Event Date: {$event_date} | Today: $today\n";
                
                // Double check - skip if not exactly today
                if ($event_date !== $today) {
                    echo "⚠️  SKIPPING: Event date ({$event_date}) is not today ({$today})\n";
                    continue;
                }
                
                if (!$event->line_notify_enabled) {
                    echo "⏭️  LINE notifications disabled for this event\n";
                    continue;
                }
                
                // Check payment status logic from rise_line_notification_logs table
                $Line_logs_model = model('App\Models\Line_notification_logs_model');
                
                // Get ALL records for this event_id from rise_line_notification_logs
                $all_records = $Line_logs_model->get_all_by_event_id($event->id);
                echo "🔍 Found " . count($all_records) . " records in rise_line_notification_logs for event_id: {$event->id}\n";
                
                $should_send_message = true;
                $current_paid_status = 0; // Default to waiting
                
                if (!empty($all_records)) {
                    // Check if ANY record has paid_status = 1
                    $has_paid_record = false;
                    
                    foreach ($all_records as $record) {
                        echo "📋 Record ID {$record['id']}: paid_status = {$record['paid_status']}\n";
                        if ($record['paid_status'] == 1) {
                            $has_paid_record = true;
                            break;
                        }
                    }
                    
                    if ($has_paid_record) {
                        echo "✅ Found paid record - SKIPPING message (already paid)\n";
                        $should_send_message = false;
                    } else {
                        echo "💰 All records have paid_status = 0 - Checking daily limit...\n";
                        $current_paid_status = 0;
                        
                        // Check how many messages we've sent today (MAX 3 per day)
                        $todays_count = $Line_logs_model->count_todays_notifications($event->id, $today);
                        echo "📊 Messages sent today: $todays_count / 3 (maximum)\n";
                        
                        if ($todays_count >= 3) {
                            echo "� Daily limit reached (3/3) - SKIPPING message\n";
                            $should_send_message = false;
                        } else {
                            echo "📤 Daily limit OK ({$todays_count}/3) - Will send message\n";
                        }
                    }
                } else {
                    echo "ℹ️  No records found - Will send message with default waiting status\n";
                    $current_paid_status = 0;
                }
                
                // Special case: Check if event is overdue (past deadline)
                $event_deadline = $event->end_date ?: $event->start_date;
                if ($event_deadline && $event_deadline < $today) {
                    echo "⏰ Event is OVERDUE (deadline: {$event_deadline})\n";
                    
                    // Even for overdue events, respect the daily limit unless it's critical
                    $todays_count = $Line_logs_model->count_todays_notifications($event->id, $today);
                    if ($todays_count < 3) {
                        echo "⚠️  Overdue event - Will send message (count: {$todays_count}/3)\n";
                        $should_send_message = true;
                        $current_paid_status = 0; // Keep as waiting for overdue events
                    } else {
                        echo "🚫 Overdue event but daily limit reached (3/3) - SKIPPING\n";
                        $should_send_message = false;
                    }
                }
                
                if (!$should_send_message) {
                    echo "⏭️  SKIPPING message based on payment status logic\n";
                    continue;
                }
                
                echo "💰 Final payment status: " . ($current_paid_status ? 'PAID' : 'WAITING') . "\n";
                
                // Send payment status flex message
                echo "📤 Sending payment status flex message...\n";
                $success = $Line_webhook->send_event_payment_status_flex($event, $current_paid_status);
                
                if ($success) {
                    $sent_messages[] = [
                        'event_id' => $event->id,
                        'event_title' => $event->title,
                        'paid_status' => $current_paid_status,
                        'status' => 'sent',
                        'daily_count' => ($Line_logs_model->count_todays_notifications($event->id, $today) + 1)
                    ];
                    echo "✅ Payment status flex message sent successfully\n";
                } else {
                    $errors[] = [
                        'event_id' => $event->id,
                        'event_title' => $event->title,
                        'error' => 'Failed to send flex message'
                    ];
                    echo "❌ Failed to send payment status flex message\n";
                }
                
                // Add delay between messages
                if (count($today_events) > 1) {
                    echo "⏳ Waiting 2 seconds before next message...\n";
                    sleep(2);
                }
            }

            // Summary
            echo "\n=== SUMMARY ===\n";
            echo "📊 Total events processed: " . count($today_events) . "\n";
            echo "✅ Messages sent successfully: " . count($sent_messages) . "\n";
            echo "❌ Errors encountered: " . count($errors) . "\n";

            if (!empty($sent_messages)) {
                echo "\n📤 Sent Messages:\n";
                foreach ($sent_messages as $msg) {
                    $status_text = $msg['paid_status'] ? 'PAID' : 'WAITING';
                    $count_text = isset($msg['daily_count']) ? " (Daily: {$msg['daily_count']}/3)" : "";
                    echo "  - {$msg['event_title']} (ID: {$msg['event_id']}) - Status: $status_text{$count_text}\n";
                }
            }

            if (!empty($errors)) {
                echo "\n❌ Errors:\n";
                foreach ($errors as $error) {
                    echo "  - {$error['event_title']} (ID: {$error['event_id']}): {$error['error']}\n";
                }
            }

            echo "\n🎉 Cron execution completed!\n";

        } catch (\Exception $e) {
            echo "💥 EXCEPTION: " . $e->getMessage() . "\n";
            echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
        }
        
        return $this->response->setBody(ob_get_clean());
    }

    /**
     * Save button click log for debugging
     */
    private function save_button_click_log($event_id, $action, $user_id) {
        try {
            $log_entry = [
                'event_id' => $event_id,
                'action' => $action,
                'user_id' => $user_id,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => $this->request->getIPAddress()
            ];
            
            // Save to a simple file for debugging
            $log_file = WRITEPATH . 'logs/button_clicks.log';
            file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND);
            
            log_message('info', 'Button click saved: ' . json_encode($log_entry));
        } catch (\Exception $e) {
            log_message('error', 'Failed to save button click log: ' . $e->getMessage());
        }
    }

    private function verify_line_signature($body, $signature, $channel_secret) {
        if (!$signature || !$channel_secret) {
            return false;
        }

        $hash = hash_hmac('sha256', $body, $channel_secret, true);
        $expected = base64_encode($hash);

        return hash_equals($expected, $signature);
    }

    private function capture_line_room($event) {
        $source = get_array_value($event, "source");
        if (!$source || !is_array($source)) {
            return;
        }

        $source_type = get_array_value($source, "type");
        $room_id = "";
        $api_type = "";

        if ($source_type === "group") {
            $room_id = get_array_value($source, "groupId");
            $api_type = "group";
        } elseif ($source_type === "room") {
            $room_id = get_array_value($source, "roomId");
            $api_type = "room";
        }

        if (!$room_id) {
            return;
        }

        $room_name = $this->fetch_line_room_name($api_type, $room_id);
        if (!$room_name) {
            $room_name = $room_id;
        }

        $rooms = $this->get_line_rooms();
        $updated = false;

        foreach ($rooms as $index => $room) {
            if (get_array_value($room, "id") === $room_id) {
                $rooms[$index] = array(
                    "id" => $room_id,
                    "name" => $room_name,
                    "type" => $api_type,
                    "updated_at" => get_current_utc_time()
                );
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $rooms[] = array(
                "id" => $room_id,
                "name" => $room_name,
                "type" => $api_type,
                "updated_at" => get_current_utc_time()
            );
        }

        $settings_model = model('App\Models\Settings_model');
        $settings_model->save_setting("line_rooms", json_encode($rooms));
    }

    private function get_line_rooms() {
        $rooms_json = get_setting('line_rooms');
        $rooms = $rooms_json ? json_decode($rooms_json, true) : array();
        return is_array($rooms) ? $rooms : array();
    }

    private function fetch_line_room_name($type, $room_id) {
        $token = get_setting('line_channel_access_token');
        if (!$token || !$type || !$room_id) {
            return "";
        }

        $url = "https://api.line.me/v2/bot/{$type}/{$room_id}/summary";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer {$token}"
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            return "";
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return "";
        }

        if ($type === "group") {
            return get_array_value($data, "groupName");
        }

        if ($type === "room") {
            return get_array_value($data, "roomName");
        }

        return "";
    }

    private function capture_line_user($event) {
        $source = get_array_value($event, "source");
        if (!$source || !is_array($source)) {
            return;
        }

        $user_id = get_array_value($source, "userId");
        if (!$user_id) {
            return;
        }

        $source_type = get_array_value($source, "type");
        $group_id = get_array_value($source, "groupId");
        $room_id = get_array_value($source, "roomId");

        $profile = $this->fetch_line_user_profile($user_id, $source_type, $group_id, $room_id);
        if (!$profile) {
            return;
        }

        $profiles = $this->get_line_user_profiles();
        $updated = false;

        foreach ($profiles as $index => $item) {
            if (get_array_value($item, "id") === $user_id) {
                $profiles[$index] = array_merge($item, $profile, array(
                    "updated_at" => get_current_utc_time()
                ));
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $profile["updated_at"] = get_current_utc_time();
            $profiles[] = $profile;
        }

        $settings_model = model('App\Models\Settings_model');
        $settings_model->save_setting("line_user_profiles", json_encode($profiles));
    }

    private function get_line_user_profiles() {
        $profiles_json = get_setting('line_user_profiles');
        $profiles = $profiles_json ? json_decode($profiles_json, true) : array();
        return is_array($profiles) ? $profiles : array();
    }

    private function fetch_line_user_profile($user_id, $source_type, $group_id, $room_id) {
        $token = get_setting('line_channel_access_token');
        if (!$token || !$user_id) {
            return array();
        }

        $url = "";
        if ($source_type === "group" && $group_id) {
            $url = "https://api.line.me/v2/bot/group/{$group_id}/member/{$user_id}";
        } elseif ($source_type === "room" && $room_id) {
            $url = "https://api.line.me/v2/bot/room/{$room_id}/member/{$user_id}";
        } else {
            $url = "https://api.line.me/v2/bot/profile/{$user_id}";
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer {$token}"
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            return array();
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return array();
        }

        return array(
            "id" => $user_id,
            "display_name" => get_array_value($data, "displayName"),
            "picture_url" => get_array_value($data, "pictureUrl"),
            "status_message" => get_array_value($data, "statusMessage"),
            "source_type" => $source_type ? $source_type : "user",
            "group_id" => $group_id ? $group_id : "",
            "room_id" => $room_id ? $room_id : ""
        );
    }

    private function is_task_tracking_keyword($message_text) {
        $keywords = $this->get_task_tracking_keywords();
        if (!$keywords || !$message_text) {
            return false;
        }

        $text = trim(mb_strtolower($message_text));
        foreach ($keywords as $keyword) {
            $keyword = trim(mb_strtolower($keyword));
            if (!$keyword) {
                continue;
            }

            if ($text === $keyword || str_starts_with($text, $keyword . " ")) {
                return true;
            }
        }

        return false;
    }

    private function get_task_tracking_keywords() {
        $keywords = get_setting("line_task_tracking_keywords");
        if (!$keywords) {
            $keywords = "work,งาน";
        }

        $parts = array_map("trim", explode(",", $keywords));
        return array_filter($parts);
    }

    private function handle_task_tracking_request($event) {
        $reply_token = $event['replyToken'] ?? '';
        $line_user_id = $event['source']['userId'] ?? '';

        if (!$reply_token || !$line_user_id) {
            return;
        }

        $rise_user = $this->find_user_by_line_id($line_user_id);
        if (!$rise_user) {
            $this->send_line_reply($reply_token, "No linked Rise user found for this LINE account.");
            return;
        }

        $status_ids = $this->get_tracking_status_ids();
        if (!$status_ids) {
            $this->send_line_reply($reply_token, "No matching task statuses found for tracking.");
            return;
        }

        $options = array(
            "specific_user_id" => $rise_user->id,
            "project_status" => 1,
            "status_ids" => implode(",", $status_ids),
            "sort_by_project" => 1,
            "order_by" => "project",
            "order_dir" => "ASC"
        );

        $tasks = $this->Tasks_model->get_details($options)->getResult();
        if (!$tasks) {
            $this->send_line_reply($reply_token, "No open tasks found for your account.");
            return;
        }

        $tasks_by_project = array();
        $task_ids = array();
        foreach ($tasks as $task) {
            if (!$task->project_id) {
                continue;
            }

            $tasks_by_project[$task->project_id]["title"] = $task->project_title ?: "Project #" . $task->project_id;
            $tasks_by_project[$task->project_id]["tasks"][] = $task;
            $task_ids[] = $task->id;
        }

        $comment_images = $this->get_latest_task_comment_images($task_ids);
        $reply_payload = $this->build_task_tracking_reply($rise_user, $tasks_by_project, $comment_images);

        $this->send_line_reply_with_images($reply_token, $reply_payload["text"], $reply_payload["images"]);
    }

    private function find_user_by_line_id($line_user_id) {
        $users_table = $this->db->prefixTable('users');
        $sql = "SELECT id, first_name, last_name, user_type, client_id, line_user_id
            FROM $users_table
            WHERE deleted=0 AND line_user_id!=''";
        $users = $this->db->query($sql)->getResult();

        foreach ($users as $user) {
            $line_ids = $this->parse_line_user_ids($user->line_user_id);
            if (in_array($line_user_id, $line_ids, true)) {
                return $user;
            }
        }

        return null;
    }

    private function parse_line_user_ids($line_user_id) {
        if (!$line_user_id) {
            return array();
        }

        $decoded = json_decode($line_user_id, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map("trim", $decoded)));
        }

        return array_values(array_filter(array_map("trim", explode(",", $line_user_id))));
    }

    private function get_tracking_status_ids() {
        $task_status_model = model('App\Models\Task_status_model');
        $statuses = $task_status_model->get_details()->getResult();
        $matches = array();

        foreach ($statuses as $status) {
            $key_name = isset($status->key_name) ? $status->key_name : "";
            $title = isset($status->title) ? $status->title : "";
            $value = mb_strtolower($key_name ?: $title);

            if ($value === "to_do" || $value === "to do" || $value === "in_progress" || $value === "in progress") {
                $matches[] = $status->id;
            }
        }

        return array_values(array_unique($matches));
    }

    private function get_latest_task_comment_images($task_ids) {
        if (!$task_ids) {
            return array();
        }

        $images_by_task = array();
        $project_comments_model = model('App\Models\Project_comments_model');
        $files = $project_comments_model->get_files_for_tasks($task_ids);

        foreach ($files as $file_row) {
            $task_id = $file_row->task_id;
            if (!$task_id || isset($images_by_task[$task_id])) {
                continue;
            }

            $file_items = @unserialize($file_row->files);
            if (!$file_items || !is_array($file_items)) {
                continue;
            }

            $images = array();
            foreach ($file_items as $file) {
                $file_name = get_array_value($file, "file_name");
                if (!$file_name) {
                    continue;
                }

                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (!in_array($ext, array("jpg", "jpeg", "png", "gif", "webp"))) {
                    continue;
                }

                $images[] = get_source_url_of_file($file, get_setting("timeline_file_path"), "raw");
            }

            if ($images) {
                $images_by_task[$task_id] = $images;
            }
        }

        return $images_by_task;
    }

    private function build_task_tracking_reply($rise_user, $tasks_by_project, $comment_images) {
        $lines = array();
        $image_urls = array();
        $user_name = trim($rise_user->first_name . " " . $rise_user->last_name);
        $lines[] = "Tasks for {$user_name}";

        if (!$tasks_by_project) {
            $lines[] = "No open projects found.";
            return array("text" => implode("\n", $lines), "images" => array());
        }

        foreach ($tasks_by_project as $project_data) {
            $lines[] = "";
            $lines[] = "Project: " . $project_data["title"];

            foreach ($project_data["tasks"] as $task) {
                $lines[] = "- " . $task->title;

                $description = $task->description ? trim(strip_tags($task->description)) : "";
                if ($description) {
                    $lines[] = "  " . mb_substr($description, 0, 140);
                }

                if (isset($comment_images[$task->id])) {
                    $image_urls = array_merge($image_urls, $comment_images[$task->id]);
                }
            }
        }

        $message = implode("\n", $lines);
        $max_length = 4500;
        if (mb_strlen($message) > $max_length) {
            $message = mb_substr($message, 0, $max_length) . "\n...more tasks available in the app.";
        }

        $unique_images = array_values(array_unique($image_urls));
        return array("text" => $message, "images" => $unique_images);
    }

    private function send_line_reply($reply_token, $message) {
        $Line_webhook = new \App\Libraries\Line_webhook();
        $Line_webhook->send_reply_message($reply_token, $message);
    }

    private function send_line_reply_with_images($reply_token, $message, $image_urls) {
        $Line_webhook = new \App\Libraries\Line_webhook();
        $messages = array(
            array(
                "type" => "text",
                "text" => $message
            )
        );

        if ($image_urls && is_array($image_urls)) {
            $image_urls = array_slice($image_urls, 0, 4); // LINE reply limit is 5 messages total
            foreach ($image_urls as $url) {
                $url = $this->normalize_line_image_url($url);
                if (!$url) {
                    continue;
                }

                $messages[] = array(
                    "type" => "image",
                    "originalContentUrl" => $url,
                    "previewImageUrl" => $url
                );
            }
        }

        $Line_webhook->send_reply_messages($reply_token, $messages);
    }

    private function normalize_line_image_url($url) {
        if (!$url) {
            return "";
        }

        if (str_starts_with($url, "//")) {
            return "https:" . $url;
        }

        if (!str_starts_with($url, "http://") && !str_starts_with($url, "https://")) {
            return get_uri($url);
        }

        return $url;
    }
}
