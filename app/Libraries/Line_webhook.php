<?php

namespace App\Libraries;

class Line_webhook {

    private $channel_access_token;
    private $enabled;
    private $user_ids;
    private $group_ids;

    function __construct() {
        $this->channel_access_token = get_setting('line_channel_access_token');
        $this->enabled = get_setting('enable_line_notifications');
        $this->user_ids = get_setting('line_user_ids') ? explode(',', get_setting('line_user_ids')) : [];
        $this->group_ids = get_setting('line_group_ids') ? explode(',', get_setting('line_group_ids')) : [];
    }

    /**
     * Check if we're within rate limits (max 50 messages per hour)
     * @return bool True if within limits, false if exceeded
     */
    private function check_rate_limit() {
        try {
            $current_hour = date('Y-m-d H');
            $rate_limit_key = "line_rate_limit_{$current_hour}";
            
            // Get current count for this hour
            $current_count = get_setting($rate_limit_key) ?: 0;
            
            // LINE rate limit: 50 messages per hour (conservative limit)
            $max_messages_per_hour = 50;
            
            if ($current_count >= $max_messages_per_hour) {
                log_message('warning', "LINE rate limit exceeded: {$current_count}/{$max_messages_per_hour} messages this hour");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            // If rate limit check fails, allow the message (fail-safe)
            log_message('error', 'Failed to check rate limit: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Update rate limit counter
     */
    private function update_rate_limit_counter() {
        try {
            $current_hour = date('Y-m-d H');
            $rate_limit_key = "line_rate_limit_{$current_hour}";
            
            $current_count = get_setting($rate_limit_key) ?: 0;
            $new_count = $current_count + 1;
            
            // Save the new count using CodeIgniter 4 syntax
            $settings_model = model('App\Models\Settings_model');
            $settings_model->save_setting($rate_limit_key, $new_count);
        } catch (\Exception $e) {
            // If rate limit update fails, log but don't break the notification
            log_message('error', 'Failed to update rate limit counter: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to LINE using Messaging API
     * @param string $message The message to send
     * @param array $options Additional options (task_id, priority, etc.)
     * @return bool Success status
     */
    public function send_notification($message, $options = []) {
        if (!$this->enabled || !$this->channel_access_token) {
            $this->log_notification($message, $options, false, 'LINE notifications disabled or token missing');
            return false;
        }

        if (empty($this->user_ids) && empty($this->group_ids)) {
            log_message('error', 'LINE: No user IDs or group IDs configured');
            $this->log_notification($message, $options, false, 'No user IDs or group IDs configured');
            return false;
        }

        // Rate limiting: Check if we're sending too many messages (temporarily disabled for debugging)
        // if (!$this->check_rate_limit()) {
        //     $this->log_notification($message, $options, false, 'Rate limit exceeded - message skipped');
        //     return false;
        // }

        try {
            $success = true;
            $responses = [];
            
            // Prevent duplicates: Prioritize groups over individual users
            // If groups are configured, only send to groups (not individual users)
            $has_groups = !empty($this->group_ids) && !empty(array_filter($this->group_ids));
            
            if ($has_groups) {
                // Send to groups only
                foreach ($this->group_ids as $group_id) {
                    $group_id = trim($group_id);
                    if (!empty($group_id)) {
                        if (count($responses) > 0) {
                            sleep(1);
                        }
                        $result = $this->send_push_message($group_id, $message, 'group');
                        if (!$result['success']) {
                            $success = false;
                        }
                        $responses[] = "Group {$group_id}: " . ($result['success'] ? 'OK' : $result['error']);
                    }
                }
            } else {
                // Send to individual users only (if no groups configured)
                foreach ($this->user_ids as $user_id) {
                    $user_id = trim($user_id);
                    if (!empty($user_id)) {
                        if (count($responses) > 0) {
                            sleep(1);
                        }
                        $result = $this->send_push_message($user_id, $message, 'user');
                        if (!$result['success']) {
                            $success = false;
                        }
                        $responses[] = "User {$user_id}: " . ($result['success'] ? 'OK' : $result['error']);
                    }
                }
            }
            
            // Update rate limit counter (temporarily disabled for debugging)
            // $this->update_rate_limit_counter();
            
            // Log the notification
            $this->log_notification($message, $options, $success, implode(', ', $responses));
            
            return $success;
        } catch (\Exception $e) {
            log_message('error', 'LINE Messaging API Error: ' . $e->getMessage());
            $this->log_notification($message, $options, false, 'Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send task deadline reminder to LINE
     * @param object $task Task object
     * @param string $reminder_type Type of reminder (before_deadline, on_deadline, overdue)
     * @return bool Success status
     */
    public function send_task_deadline_reminder($task, $reminder_type = 'before_deadline') {
        if (!$this->enabled || !$this->channel_access_token) {
            return false;
        }

        $message = $this->format_task_reminder_message($task, $reminder_type);
        $options = [
            'task_id' => $task->id,
            'reminder_type' => $reminder_type,
            'priority' => $task->priority_title ?? 'Normal',
            'project' => $task->project_title ?? 'General'
        ];

        return $this->send_notification($message, $options);
    }

    /**
     * Send recurring task creation notification
     * @param object $task Task object
     * @return bool Success status
     */
    public function send_recurring_task_created($task) {
        if (!$this->enabled || !$this->channel_access_token) {
            return false;
        }

        $message = "🔄 **Recurring Task Created**\n\n";
        $message .= "📋 **Task:** {$task->title}\n";
        
        if (!empty($task->project_title)) {
            $message .= "📁 **Project:** {$task->project_title}\n";
        }
        
        if (!empty($task->deadline)) {
            $deadline_formatted = date('M j, Y', strtotime($task->deadline));
            $message .= "⏰ **Deadline:** {$deadline_formatted}\n";
        }
        
        if (!empty($task->assigned_to_user)) {
            $message .= "👤 **Assigned to:** {$task->assigned_to_user}\n";
        }

        $message .= "\n💡 This is an automatically created recurring task.";

        $options = [
            'task_id' => $task->id,
            'type' => 'recurring_task_created',
            'project' => $task->project_title ?? 'General'
        ];

        return $this->send_notification($message, $options);
    }

    /**
     * Format task reminder message based on reminder type
     * @param object $task Task object
     * @param string $reminder_type Type of reminder
     * @return string Formatted message
     */
    private function format_task_reminder_message($task, $reminder_type) {
        $icons = [
            'before_deadline' => '⏰',
            'on_deadline' => '🚨',
            'overdue' => '🔴'
        ];

        $titles = [
            'before_deadline' => 'Task Deadline Reminder',
            'on_deadline' => 'Task Due Today',
            'overdue' => 'Task Overdue'
        ];

        $icon = $icons[$reminder_type] ?? '📋';
        $title = $titles[$reminder_type] ?? 'Task Reminder';

        $message = "{$icon} **{$title}**\n\n";
        $message .= "📋 **Task:** {$task->title}\n";

        if (!empty($task->project_title)) {
            $message .= "📁 **Project:** {$task->project_title}\n";
        }

        if (!empty($task->deadline)) {
            $deadline_formatted = date('M j, Y', strtotime($task->deadline));
            $message .= "⏰ **Deadline:** {$deadline_formatted}\n";
            
            // Calculate days difference
            $today = date('Y-m-d');
            $deadline_date = date('Y-m-d', strtotime($task->deadline));
            $days_diff = (strtotime($deadline_date) - strtotime($today)) / (60 * 60 * 24);
            
            if ($reminder_type === 'before_deadline' && $days_diff > 0) {
                $message .= "📅 **Days remaining:** " . ceil($days_diff) . " day(s)\n";
            } elseif ($reminder_type === 'overdue' && $days_diff < 0) {
                $message .= "📅 **Days overdue:** " . abs(floor($days_diff)) . " day(s)\n";
            }
        }

        if (!empty($task->assigned_to_user)) {
            $message .= "👤 **Assigned to:** {$task->assigned_to_user}\n";
        }

        if (!empty($task->priority_title)) {
            $priority_icons = [
                'High' => '🔴',
                'Medium' => '🟡',
                'Low' => '🟢',
                'Normal' => '⚪'
            ];
            $priority_icon = $priority_icons[$task->priority_title] ?? '⚪';
            $message .= "🎯 **Priority:** {$priority_icon} {$task->priority_title}\n";
        }

        // Add action suggestions based on reminder type
        if ($reminder_type === 'before_deadline') {
            $message .= "\n💡 **Reminder:** Please ensure this task is completed on time.";
        } elseif ($reminder_type === 'on_deadline') {
            $message .= "\n🚨 **Action Required:** This task is due today!";
        } elseif ($reminder_type === 'overdue') {
            $message .= "\n⚠️ **Urgent:** This task is overdue and needs immediate attention!";
        }

        return $message;
    }

    /**
     * Send reply message using LINE Messaging API (for webhook responses)
     * @param string $reply_token Reply token from webhook event
     * @param string $message Message content
     * @return bool Success status
     */
    public function send_reply_message($reply_token, $message) {
        $url = 'https://api.line.me/v2/bot/message/reply';
        
        $payload = [
            'replyToken' => $reply_token,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];

        // Debug log all the details
        error_log("LINE Reply API Debug - URL: $url");
        error_log("LINE Reply API Debug - Payload: " . json_encode($payload));
        error_log("LINE Reply API Debug - Token: " . substr($this->channel_access_token, 0, 10) . "...");

        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->channel_access_token,
                'User-Agent: Task-Management-System/1.0'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        // Debug log all the details
        error_log("LINE Reply API Debug - HTTP Code: $http_code");
        error_log("LINE Reply API Debug - Response: $response");
        error_log("LINE Reply API Debug - cURL Error: $error");

        if ($error) {
            log_message('error', "LINE Reply Message cURL Error: " . $error);
            error_log("LINE Reply Message cURL Error: " . $error);
            return false;
        }

        if ($http_code >= 200 && $http_code < 300) {
            log_message('info', "LINE Reply Message sent successfully. HTTP Code: " . $http_code);
            error_log("LINE Reply Message sent successfully. HTTP Code: " . $http_code);
            return true;
        } else {
            log_message('error', "LINE Reply Message failed. HTTP Code: " . $http_code . ', Response: ' . $response);
            error_log("LINE Reply Message failed. HTTP Code: " . $http_code . ', Response: ' . $response);
            return false;
        }
    }

    /**
     * Send reply with custom message payloads (text/image/etc.)
     * @param string $reply_token Reply token from webhook event
     * @param array $messages Array of LINE message objects
     * @return bool Success status
     */
    public function send_reply_messages($reply_token, $messages) {
        if (!$reply_token || !$messages || !is_array($messages)) {
            return false;
        }

        $url = 'https://api.line.me/v2/bot/message/reply';

        $payload = [
            'replyToken' => $reply_token,
            'messages' => $messages
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->channel_access_token,
                'User-Agent: Task-Management-System/1.0'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', "LINE Reply Message cURL Error: " . $error);
            return false;
        }

        if ($http_code >= 200 && $http_code < 300) {
            return true;
        }

        log_message('error', "LINE Reply Message failed. HTTP Code: " . $http_code . ', Response: ' . $response);
        return false;
    }

    /**
     * Send push message using LINE Messaging API
     * @param string $to User ID or Group ID
     * @param string $message Message content
     * @param string $type 'user' or 'group'
     * @return bool Success status
     */
    public function send_push_message($to, $message, $type = 'user') {
        $url = 'https://api.line.me/v2/bot/message/push';

        // LINE doesn't support markdown - strip ** markers
        $message = str_replace('**', '', $message);

        $payload = [
            'to' => $to,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->channel_access_token,
                'User-Agent: Task-Management-System/1.0'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        log_message('info', "LINE Push to {$type} {$to}: HTTP {$http_code} | Response: {$response} | cURL: {$error}");

        if ($error) {
            return ['success' => false, 'error' => "cURL Error: {$error}"];
        }

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'error' => ''];
        } else {
            return ['success' => false, 'error' => $this->_parse_line_error($http_code, $response)];
        }
    }

    /**
     * Send flex message (rich message) using LINE Messaging API
     * @param string $to User ID or Group ID
     * @param array $flex_content Flex message content
     * @return bool Success status
     */
    private function send_flex_message($to, $flex_content) {
        $url = 'https://api.line.me/v2/bot/message/push';

        $payload = [
            'to' => $to,
            'messages' => [
                [
                    'type' => 'flex',
                    'altText' => 'แจ้งเตือนชำระบิล - กรุณาคลิกปุ่มเพื่อเปลี่ยนสถานะการชำระเงิน',
                    'contents' => $flex_content
                ]
            ]
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->channel_access_token,
                'User-Agent: Task-Management-System/1.0'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        log_message('info', "LINE Flex to {$to}: HTTP {$http_code} | Response: {$response} | cURL: {$error}");

        if ($error) {
            return ['success' => false, 'error' => "cURL Error: {$error}"];
        }

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'error' => ''];
        } else {
            return ['success' => false, 'error' => $this->_parse_line_error($http_code, $response)];
        }
    }

    /**
     * Test LINE Messaging API connection
     * @return array Test result with status and message
     */
    private function _parse_line_error($http_code, $response) {
        $decoded = json_decode($response, true);
        $error_msg = "HTTP {$http_code}";
        if ($decoded && isset($decoded['message'])) {
            $error_msg .= ": {$decoded['message']}";
            if (!empty($decoded['details'])) {
                $detail_msgs = [];
                foreach ($decoded['details'] as $d) {
                    $detail_msgs[] = ($d['property'] ?? '') . ' ' . ($d['message'] ?? '');
                }
                $error_msg .= ' [' . implode('; ', $detail_msgs) . ']';
            }
        } else {
            $error_msg .= ": {$response}";
        }
        return $error_msg;
    }

    public function test_connection() {
        if (!$this->channel_access_token) {
            return [
                'success' => false,
                'message' => 'LINE Channel Access Token is not configured'
            ];
        }

        if (empty($this->user_ids) && empty($this->group_ids)) {
            return [
                'success' => false,
                'message' => 'No LINE User IDs or Group IDs configured'
            ];
        }

        $test_message = "🧪 Test Message\n\nThis is a test message from your Task Management System.\n\n✅ LINE Messaging API is working correctly!";
        
        $success = $this->send_notification($test_message, ['type' => 'test']);
        
        return [
            'success' => $success,
            'message' => $success ? 'LINE Messaging API test successful!' : 'LINE Messaging API test failed. Please check your configuration.'
        ];
    }

    /**
     * Get LINE bot profile (for testing token validity)
     * @return array Bot profile information
     */
    public function get_bot_info() {
        if (!$this->channel_access_token) {
            return ['success' => false, 'message' => 'No access token configured'];
        }

        $url = 'https://api.line.me/v2/bot/info';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->channel_access_token
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }

        if ($http_code === 200) {
            $bot_info = json_decode($response, true);
            return [
                'success' => true,
                'data' => $bot_info,
                'message' => 'Bot info retrieved successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'HTTP ' . $http_code . ': ' . $response
            ];
        }
    }

    /**
     * Send event reminder notification
     * @param object $event Event object
     * @param string $reminder_type Type of reminder (before_event, on_event, overdue)
     * @return bool Success status
     */
    public function send_event_reminder($event, $reminder_type = 'before_event') {
        if (!$this->enabled || !$this->channel_access_token) {
            return false;
        }

        $message = $this->format_event_reminder_message($event, $reminder_type);
        $options = [
            'event_id' => $event->id,
            'reminder_type' => $reminder_type,
            'type' => 'event_reminder'
        ];

        return $this->send_notification($message, $options);
    }

    /**
     * Format event reminder message based on reminder type
     * @param object $event Event object
     * @param string $reminder_type Type of reminder
     * @return string Formatted message
     */
    private function format_event_reminder_message($event, $reminder_type) {
        $icons = [
            'before_event' => '⏰',
            'on_event' => '🚨',
            'overdue' => '🔴'
        ];

        $titles = [
            'before_event' => 'Event Reminder',
            'on_event' => 'Event Starting Today',
            'overdue' => 'Event Overdue'
        ];

        $icon = $icons[$reminder_type] ?? '📅';
        $title = $titles[$reminder_type] ?? 'Event Notification';

        $message = "{$icon} **{$title}**\n\n";
        $message .= "📅 **Event:** {$event->title}\n";

        if (!empty($event->description)) {
            $description = strip_tags($event->description);
            if (strlen($description) > 100) {
                $description = substr($description, 0, 100) . '...';
            }
            $message .= "📝 **Description:** {$description}\n";
        }

        if (!empty($event->start_date)) {
            $start_formatted = $this->safe_format_date($event->start_date, 'M j, Y');
            if (!empty($event->start_time) && $event->start_time !== '00:00:00') {
                $time_formatted = $this->safe_format_time($event->start_time);
                if ($time_formatted) {
                    $start_formatted .= ' at ' . $time_formatted;
                }
            }
            $message .= "🕐 **Start:** {$start_formatted}\n";
        }

        if (!empty($event->end_date) && $event->end_date !== $event->start_date) {
            $end_formatted = $this->safe_format_date($event->end_date, 'M j, Y');
            if (!empty($event->end_time) && $event->end_time !== '00:00:00') {
                $time_formatted = $this->safe_format_time($event->end_time);
                if ($time_formatted) {
                    $end_formatted .= ' at ' . $time_formatted;
                }
            }
            $message .= "🕐 **End:** {$end_formatted}\n";
        }

        if (!empty($event->location)) {
            $message .= "📍 **Location:** {$event->location}\n";
        }

        if (!empty($event->created_by_name)) {
            $message .= "👤 **Created by:** {$event->created_by_name}\n";
        }

        // Calculate time difference for before_event reminders
        if ($reminder_type === 'before_event' && !empty($event->start_date)) {
            $today = date('Y-m-d');
            $event_date = date('Y-m-d', strtotime($event->start_date));
            $days_diff = (strtotime($event_date) - strtotime($today)) / (60 * 60 * 24);
            
            if ($days_diff > 0) {
                $message .= "📅 **Days remaining:** " . ceil($days_diff) . " day(s)\n";
            }
        }

        // Add action suggestions based on reminder type
        if ($reminder_type === 'before_event') {
            $message .= "\n💡 **Reminder:** Don't forget about this upcoming event!";
        } elseif ($reminder_type === 'on_event') {
            $message .= "\n🚨 **Today:** This event is scheduled for today!";
        } elseif ($reminder_type === 'overdue') {
            $message .= "\n⚠️ **Notice:** This event date has passed.";
        }

        return $message;
    }

    /**
     * Send recurring event creation notification
     * @param object $event Event object
     * @return bool Success status
     */
    public function send_recurring_event_created($event) {
        if (!$this->enabled || !$this->channel_access_token) {
            return false;
        }

        $message = "🔄 **Recurring Event Created**\n\n";
        $message .= "📅 **Event:** {$event->title}\n";
        
        if (!empty($event->start_date)) {
            $start_formatted = $this->safe_format_date($event->start_date, 'M j, Y');
            if (!empty($event->start_time) && $event->start_time !== '00:00:00') {
                $time_formatted = $this->safe_format_time($event->start_time);
                if ($time_formatted) {
                    $start_formatted .= ' at ' . $time_formatted;
                }
            }
            $message .= "🕐 **Date:** {$start_formatted}\n";
        }
        
        if (!empty($event->location)) {
            $message .= "📍 **Location:** {$event->location}\n";
        }
        
        if (!empty($event->created_by_name)) {
            $message .= "👤 **Created by:** {$event->created_by_name}\n";
        }

        $message .= "\n💡 This is an automatically created recurring event.";

        $options = [
            'event_id' => $event->id,
            'type' => 'recurring_event_created'
        ];

        return $this->send_notification($message, $options);
    }

    /**
     * Send event payment status flex message with Paid/Waiting buttons
     * @param object $event Event object
     * @param int $paid_status 0=unpaid, 1=paid
     * @return bool Success status
     */
    public function send_event_payment_status_flex($event, $paid_status = 0) {
        $status_text = $paid_status ? '✅ ชำระแล้ว' : '⏳ รอชำระ';
        
        // Build flex contents dynamically
        $flex_contents = [
            [
                'type' => 'text',
                'text' => 'แจ้งเตือนชำระบิล',
                'weight' => 'bold',
                'size' => 'lg',
                'margin' => 'md'
            ],
            [
                'type' => 'text',
                'text' => $event->title,
                'size' => 'md',
                'wrap' => true,
                'margin' => 'md',
                'weight' => 'bold'
            ]
        ];
        
        // Add description if available
        if (!empty($event->description)) {
            $flex_contents[] = [
                'type' => 'text',
                'text' => $event->description,
                'size' => 'sm',
                'wrap' => true,
                'margin' => 'sm',
                'color' => '#666666'
            ];
        }
        
        // Add status section
        $flex_contents[] = [
            'type' => 'text',
            'text' => 'สถานะ',
            'size' => 'md',
            'margin' => 'lg',
            'weight' => 'bold'
        ];
        
        $flex_contents[] = [
            'type' => 'text',
            'text' => $status_text,
            'size' => 'md',
            'color' => $paid_status ? '#27ae60' : '#e67e22',
            'margin' => 'sm'
        ];
        
        $flex_contents[] = [
            'type' => 'separator',
            'margin' => 'lg'
        ];
        
        // Add buttons
        $flex_contents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'lg',
            'spacing' => 'sm',
            'contents' => [
                [
                    'type' => 'button',
                    'style' => 'primary',
                    'height' => 'sm',
                    'color' => '#27ae60',
                    'action' => [
                        'type' => 'postback',
                        'label' => 'ชำระแล้ว',
                        'data' => 'action=mark_paid&event_id=' . $event->id,
                        'displayText' => 'เปลี่ยนสถานะเป็นชำระแล้ว'
                    ],
                    'flex' => 1
                ],
                [
                    'type' => 'separator'
                ],
                [
                    'type' => 'button',
                    'style' => 'secondary',
                    'height' => 'sm',
                    'color' => '#e67e22',
                    'action' => [
                        'type' => 'postback',
                        'label' => 'รอชำระ',
                        'data' => 'action=mark_waiting&event_id=' . $event->id,
                        'displayText' => 'เปลี่ยนสถานะเป็นรอชำระ'
                    ],
                    'flex' => 1
                ]
            ]
        ];
        
        $flex_content = [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => $flex_contents
            ]
        ];

        $success = true;
        $responses = [];
        $has_groups = !empty($this->group_ids) && !empty(array_filter($this->group_ids));
        if ($has_groups) {
            foreach ($this->group_ids as $group_id) {
                $group_id = trim($group_id);
                if (!empty($group_id)) {
                    if (count($responses) > 0) {
                        sleep(1);
                    }
                    $result = $this->send_flex_message($group_id, $flex_content);
                    if (!$result['success']) {
                        $success = false;
                    }
                    $responses[] = "Group {$group_id}: " . ($result['success'] ? 'OK' : $result['error']);
                }
            }
        } else {
            foreach ($this->user_ids as $user_id) {
                $user_id = trim($user_id);
                if (!empty($user_id)) {
                    if (count($responses) > 0) {
                        sleep(1);
                    }
                    $result = $this->send_flex_message($user_id, $flex_content);
                    if (!$result['success']) {
                        $success = false;
                    }
                    $responses[] = "User {$user_id}: " . ($result['success'] ? 'OK' : $result['error']);
                }
            }
        }
        $this->log_notification('[FLEX] แจ้งเตือนชำระบิล', ['event_id' => $event->id, 'type' => 'event_payment_status'], $success, implode(', ', $responses));
        return $success;
    }

    /**
     * Log notification to database
     * @param string $message Message content
     * @param array $options Options containing task_id, event_id, etc.
     * @param bool $success Whether the notification was successful
     * @param string $response Response details
     */
    private function log_notification($message, $options, $success, $response = '') {
        try {
            $Line_logs_model = model('App\Models\Line_notification_logs_model');
            
            $log_data = [
                'task_id' => get_array_value($options, 'task_id'),
                'event_id' => get_array_value($options, 'event_id'),
                'notification_type' => get_array_value($options, 'reminder_type') ?: get_array_value($options, 'type') ?: 'general',
                'message' => $message,
                'status' => $success ? 'sent' : 'failed',
                'response' => $response
            ];
            
            $Line_logs_model->log_notification($log_data);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log LINE notification: ' . $e->getMessage());
        }
    }

    /**
     * Safely format date with error handling
     * @param string $date Date string
     * @param string $format Format string
     * @return string Formatted date or fallback
     */
    private function safe_format_date($date, $format = 'M j, Y') {
        try {
            if (empty($date)) {
                return 'Not set';
            }
            
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return $date; // Return original if can't parse
            }
            
            return date($format, $timestamp);
        } catch (\Exception $e) {
            log_message('error', 'LINE date formatting error: ' . $e->getMessage() . ' for date: ' . $date);
            return $date; // Return original date as fallback
        }
    }

    /**
     * Safely format time with error handling
     * @param string $time Time string
     * @return string|false Formatted time or false on error
     */
    private function safe_format_time($time) {
        try {
            if (empty($time) || $time === '00:00:00') {
                return false;
            }
            
            // Validate time format (HH:MM:SS)
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $time)) {
                log_message('error', 'Invalid time format: ' . $time);
                return false;
            }
            
            $timestamp = strtotime($time);
            if ($timestamp === false) {
                log_message('error', 'Failed to parse time: ' . $time);
                return false;
            }
            
            return date('g:i A', $timestamp);
        } catch (\Exception $e) {
            log_message('error', 'LINE time formatting error: ' . $e->getMessage() . ' for time: ' . $time);
            return false;
        }
    }

    /**
     * Send custom message to LINE
     * @param string $title Message title
     * @param string $content Message content
     * @param array $options Additional options
     * @return bool Success status
     */
    public function send_custom_message($title, $content, $options = []) {
        $message = "📢 **{$title}**\n\n{$content}";
        return $this->send_notification($message, $options);
    }
}
