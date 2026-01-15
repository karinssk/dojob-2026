<?php

namespace App\Models;

class Line_notification_logs_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'line_notification_logs';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        // Check if table exists first
        if (!$this->table_exists()) {
            return $this->db->query("SELECT 1 WHERE 0"); // Return empty result
        }

        $logs_table = $this->db->prefixTable('line_notification_logs');
        $tasks_table = $this->db->prefixTable('tasks');
        $events_table = $this->db->prefixTable('events');
        $users_table = $this->db->prefixTable('users');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $logs_table.id=$id";
        }

        $task_id = $this->_get_clean_value($options, "task_id");
        if ($task_id) {
            $where .= " AND $logs_table.task_id=$task_id";
        }

        $event_id = $this->_get_clean_value($options, "event_id");
        if ($event_id) {
            $where .= " AND $logs_table.event_id=$event_id";
        }

        $notification_type = $this->_get_clean_value($options, "notification_type");
        if ($notification_type) {
            $where .= " AND $logs_table.notification_type='$notification_type'";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $logs_table.status='$status'";
        }

        $paid_status = $this->_get_clean_value($options, "paid_status");
        if ($paid_status !== null && $paid_status !== '') {
            $where .= " AND $logs_table.paid_status=$paid_status";
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND DATE($logs_table.sent_at)>='$start_date'";
        }

        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND DATE($logs_table.sent_at)<='$end_date'";
        }

        $limit = $this->_get_clean_value($options, "limit");
        $offset = $this->_get_clean_value($options, "offset");
        
        $limit_offset = "";
        if ($limit) {
            $offset = $offset ? $offset : 0;
            $limit_offset = " LIMIT $limit OFFSET $offset ";
        }

        $order_by = " ORDER BY $logs_table.sent_at DESC ";

        $sql = "SELECT $logs_table.*, 
                $tasks_table.title AS task_title,
                $events_table.title AS event_title,
                $events_table.start_date AS event_start_date,
                $events_table.start_time AS event_start_time,
                CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_name
        FROM $logs_table
        LEFT JOIN $tasks_table ON $tasks_table.id = $logs_table.task_id
        LEFT JOIN $events_table ON $events_table.id = $logs_table.event_id
        LEFT JOIN $users_table ON $users_table.id = COALESCE($tasks_table.created_by, $events_table.created_by)
        WHERE $logs_table.deleted=0 $where
        $order_by $limit_offset";

        return $this->db->query($sql);
    }

    function get_statistics($options = array()) {
        // Check if table exists first
        if (!$this->table_exists()) {
            return (object) [
                'total_notifications' => 0,
                'successful_notifications' => 0,
                'failed_notifications' => 0,
                'task_notifications' => 0,
                'event_notifications' => 0
            ];
        }

        $logs_table = $this->db->prefixTable('line_notification_logs');
        
        $where = "";
        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND DATE($logs_table.sent_at)>='$start_date'";
        }

        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND DATE($logs_table.sent_at)<='$end_date'";
        }

        $sql = "SELECT 
                COUNT(*) AS total_notifications,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS successful_notifications,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_notifications,
                SUM(CASE WHEN notification_type LIKE '%task%' THEN 1 ELSE 0 END) AS task_notifications,
                SUM(CASE WHEN notification_type LIKE '%event%' THEN 1 ELSE 0 END) AS event_notifications
        FROM $logs_table
        WHERE $logs_table.deleted=0 $where";

        return $this->db->query($sql)->getRow();
    }

    function log_notification($data) {
        // Check if table exists first
        if (!$this->table_exists()) {
            return false;
        }

        $data['sent_at'] = get_current_utc_time();
        return $this->ci_save($data);
    }

    private function table_exists() {
        try {
            $logs_table = $this->db->prefixTable('line_notification_logs');
            $this->db->query("SELECT 1 FROM $logs_table LIMIT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    function check_notification_sent($event_id, $notification_type, $date = null) {
        // Check if a notification was already sent for this event/type/date
        if (!$this->table_exists()) {
            return false;
        }

        $logs_table = $this->db->prefixTable('line_notification_logs');
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT COUNT(*) as count 
                FROM $logs_table 
                WHERE event_id = ? 
                AND notification_type = ? 
                AND DATE(sent_at) = ? 
                AND status = 'sent' 
                AND deleted = 0";
        
        try {
            $result = $this->db->query($sql, [$event_id, $notification_type, $date]);
            $row = $result->getRow();
            return $row->count > 0;
        } catch (\Exception $e) {
            log_message('error', 'Failed to check notification sent: ' . $e->getMessage());
            return false;
        }
    }

    function create_table_if_not_exists() {
        if (!$this->table_exists()) {
            $logs_table = $this->db->prefixTable('line_notification_logs');
            
            $sql = "CREATE TABLE IF NOT EXISTS $logs_table (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `task_id` int(11) DEFAULT NULL,
                `event_id` int(11) DEFAULT NULL,
                `notification_type` varchar(50) NOT NULL,
                `message` text NOT NULL,
                `status` enum('sent', 'failed') NOT NULL DEFAULT 'sent',
                `response` text,
                `sent_at` datetime NOT NULL,
                `paid_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=unpaid, 1=paid',
                `deleted` tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `task_id` (`task_id`),
                KEY `event_id` (`event_id`),
                KEY `notification_type` (`notification_type`),
                KEY `sent_at` (`sent_at`),
                KEY `status` (`status`),
                KEY `paid_status` (`paid_status`)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_unicode_ci";
            
            try {
                $this->db->query($sql);
                return true;
            } catch (\Exception $e) {
                log_message('error', 'Failed to create line_notification_logs table: ' . $e->getMessage());
                return false;
            }
        }
        return true;
    }
    
    /**
     * Update paid status for a notification
     * @param int $id Notification ID
     * @param int $paid_status 0=unpaid, 1=paid
     * @return bool
     */
    function update_paid_status($id, $paid_status) {
        $logs_table = $this->db->prefixTable('line_notification_logs');
        
        $sql = "UPDATE $logs_table SET paid_status = ? WHERE id = ? AND deleted = 0";
        
        try {
            $this->db->query($sql, [$paid_status, $id]);
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Failed to update paid status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notification by event_id for paid status update
     * @param int $event_id Event ID
     * @return array|null
     */
    function get_by_event_id($event_id) {
        $logs_table = $this->db->prefixTable('line_notification_logs');
        
        $sql = "SELECT * FROM $logs_table WHERE event_id = ? AND deleted = 0 ORDER BY sent_at DESC LIMIT 1";
        
        error_log("DEBUG: get_by_event_id SQL: $sql with event_id: $event_id");
        error_log("DEBUG: Table name: $logs_table");
        
        $query = $this->db->query($sql, [$event_id]);
        $result = $query->getRow('array');
        
        error_log("DEBUG: get_by_event_id result: " . ($result ? json_encode($result) : 'NULL'));
        
        return $result;
    }
    
    /**
     * Get ALL notification records by event_id for payment status analysis
     * @param int $event_id Event ID
     * @return array Array of all records
     */
    function get_all_by_event_id($event_id) {
        $logs_table = $this->db->prefixTable('line_notification_logs');
        
        $sql = "SELECT * FROM $logs_table WHERE event_id = ? AND deleted = 0 ORDER BY sent_at DESC";
        
        error_log("DEBUG: get_all_by_event_id SQL: $sql with event_id: $event_id");
        
        $query = $this->db->query($sql, [$event_id]);
        $results = $query->getResult('array');
        
        error_log("DEBUG: get_all_by_event_id found " . count($results) . " records for event_id: $event_id");
        
        return $results;
    }
    
    /**
     * Count notifications sent today for a specific event
     * @param int $event_id Event ID
     * @param string $date Date in Y-m-d format (defaults to today)
     * @return int Count of notifications sent today
     */
    function count_todays_notifications($event_id, $date = null) {
        $logs_table = $this->db->prefixTable('line_notification_logs');
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT COUNT(*) as count FROM $logs_table 
                WHERE event_id = ? 
                AND DATE(sent_at) = ? 
                AND status = 'sent' 
                AND deleted = 0";
        
        error_log("DEBUG: count_todays_notifications SQL: $sql with event_id: $event_id, date: $date");
        
        $query = $this->db->query($sql, [$event_id, $date]);
        $result = $query->getRow();
        $count = $result->count ?? 0;
        
        error_log("DEBUG: count_todays_notifications found $count notifications for event_id: $event_id on date: $date");
        
        return (int)$count;
    }
}