<?php

namespace App\Controllers;

class Line_settings extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
    }

    function index() {
        $view_data['line_channel_access_token'] = get_setting('line_channel_access_token');
        $view_data['line_user_ids'] = get_setting('line_user_ids');
        $view_data['line_group_ids'] = get_setting('line_group_ids');
        $view_data['enable_line_notifications'] = get_setting('enable_line_notifications');
        $view_data['line_reminder_days_before'] = get_setting('line_reminder_days_before') ?: 3;
        $view_data['line_notify_recurring_tasks'] = get_setting('line_notify_recurring_tasks');
        $view_data['line_notify_overdue_tasks'] = get_setting('line_notify_overdue_tasks');
        
        return $this->template->rander("settings/line_notifications", $view_data);
    }

    function save() {
        $settings = array(
            "line_channel_access_token",
            "line_user_ids",
            "line_group_ids",
            "enable_line_notifications", 
            "line_reminder_days_before",
            "line_notify_recurring_tasks",
            "line_notify_overdue_tasks"
        );

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            $this->Settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    function test_webhook() {
        $Line_webhook = new \App\Libraries\Line_webhook();
        $result = $Line_webhook->test_connection();
        
        echo json_encode($result);
    }

    function send_test_notification() {
        if (!get_setting('enable_line_notifications')) {
            echo json_encode(array(
                "success" => false, 
                "message" => "LINE notifications are disabled. Please enable them first."
            ));
            return;
        }

        $Line_webhook = new \App\Libraries\Line_webhook();
        
        // Create a sample task object for testing
        $sample_task = (object) array(
            'id' => 'TEST',
            'title' => 'Sample Task - Credit Card Payment',
            'deadline' => date('Y-m-d', strtotime('+3 days')),
            'project_title' => 'Personal Finance',
            'assigned_to_user' => 'Test User',
            'priority_title' => 'High'
        );

        $success = $Line_webhook->send_task_deadline_reminder($sample_task, 'before_deadline');
        
        if ($success) {
            echo json_encode(array(
                "success" => true, 
                "message" => "Test notification sent successfully to LINE!"
            ));
        } else {
            echo json_encode(array(
                "success" => false, 
                "message" => "Failed to send test notification. Please check your webhook URL and settings."
            ));
        }
    }

    function create_sample_recurring_tasks() {
        // Create sample recurring tasks for common bills
        $sample_tasks = array(
            array(
                'title' => 'Pay Credit Card Bill',
                'description' => 'Monthly credit card payment reminder',
                'deadline_day' => 26,
                'priority_id' => 3, // High priority
                'context' => 'general'
            ),
            array(
                'title' => 'Pay Water Bill', 
                'description' => 'Monthly water utility payment',
                'deadline_day' => 29,
                'priority_id' => 2, // Medium priority
                'context' => 'general'
            ),
            array(
                'title' => 'Pay Electricity Bill',
                'description' => 'Monthly electricity payment',
                'deadline_day' => 15,
                'priority_id' => 2, // Medium priority
                'context' => 'general'
            )
        );

        $created_tasks = array();
        
        foreach ($sample_tasks as $task_data) {
            // Calculate next deadline based on the day of month
            $current_month = date('Y-m');
            $deadline_date = $current_month . '-' . str_pad($task_data['deadline_day'], 2, '0', STR_PAD_LEFT);
            
            // If the deadline has passed this month, set it for next month
            if (strtotime($deadline_date) < strtotime(date('Y-m-d'))) {
                $deadline_date = date('Y-m-d', strtotime('first day of next month +' . ($task_data['deadline_day'] - 1) . ' days'));
            }

            $new_task_data = array(
                "title" => $task_data['title'],
                "description" => $task_data['description'],
                "project_id" => 0, // General tasks
                "milestone_id" => 0,
                "points" => 1,
                "status_id" => 1, // To Do
                "context" => $task_data['context'],
                "priority_id" => $task_data['priority_id'],
                "deadline" => $deadline_date,
                "recurring" => 1,
                "repeat_type" => "months",
                "repeat_every" => 1,
                "next_recurring_date" => date('Y-m-d', strtotime($deadline_date . ' +1 month')),
                "assigned_to" => $this->login_user->id,
                "created_date" => get_current_utc_time(),
                "created_by" => $this->login_user->id,
                "reminder_date" => date('Y-m-d', strtotime($deadline_date . ' -3 days')) // 3 days before
            );

            $task_id = $this->Tasks_model->ci_save($new_task_data);
            
            if ($task_id) {
                $created_tasks[] = array(
                    'id' => $task_id,
                    'title' => $task_data['title'],
                    'deadline' => $deadline_date
                );
            }
        }

        if (!empty($created_tasks)) {
            echo json_encode(array(
                "success" => true, 
                "message" => "Sample recurring tasks created successfully!",
                "tasks" => $created_tasks
            ));
        } else {
            echo json_encode(array(
                "success" => false, 
                "message" => "Failed to create sample tasks."
            ));
        }
    }

    function send_test_event_notification() {
        if (!get_setting('enable_line_notifications')) {
            echo json_encode(array(
                "success" => false, 
                "message" => "LINE notifications are disabled. Please enable them first."
            ));
            return;
        }

        $Line_webhook = new \App\Libraries\Line_webhook();
        
        // Create a sample event object for testing
        $sample_event = (object) array(
            'id' => 'TEST',
            'title' => 'Sample Event - Monthly Team Meeting',
            'description' => 'Regular monthly team meeting to discuss project progress and upcoming tasks.',
            'start_date' => date('Y-m-d', strtotime('+3 days')),
            'start_time' => '14:00:00',
            'end_date' => date('Y-m-d', strtotime('+3 days')),
            'end_time' => '15:30:00',
            'location' => 'Conference Room A',
            'created_by_name' => 'Test User',
            'line_notify_enabled' => 1
        );

        $success = $Line_webhook->send_event_reminder($sample_event, 'before_event');
        
        if ($success) {
            echo json_encode(array(
                "success" => true, 
                "message" => "Test event notification sent successfully to LINE!"
            ));
        } else {
            echo json_encode(array(
                "success" => false, 
                "message" => "Failed to send test event notification. Please check your settings."
            ));
        }
    }

    function line_settings() {
        $view_data = array(
            "line_channel_access_token" => get_setting('line_channel_access_token'),
            "line_channel_secret" => get_setting('line_channel_secret'),
            "line_default_room_id" => get_setting('line_default_room_id'),
            "line_webhook_url" => get_uri("line/v1/webhook"),
            "line_rooms_dropdown" => $this->_get_line_rooms_dropdown()
        );

        return $this->template->rander("settings/line_settings", $view_data);
    }

    function save_line_settings() {
        $settings = array(
            "line_channel_access_token",
            "line_channel_secret",
            "line_default_room_id"
        );

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            $this->Settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    private function _get_line_rooms_dropdown() {
        $rooms_json = get_setting('line_rooms');
        $rooms = $rooms_json ? json_decode($rooms_json, true) : array();

        if (!is_array($rooms)) {
            $rooms = array();
        }

        $dropdown = array("" => "-");
        foreach ($rooms as $room) {
            $room_id = get_array_value($room, "id");
            if (!$room_id) {
                continue;
            }

            $room_name = get_array_value($room, "name");
            $room_type = get_array_value($room, "type");
            $label_name = $room_name ? $room_name : $room_id;
            $label_type = $room_type ? strtoupper($room_type) : "ROOM";
            $dropdown[$room_id] = $label_name . " (" . $label_type . ")";
        }

        return $dropdown;
    }
}
