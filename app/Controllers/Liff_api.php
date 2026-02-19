<?php

namespace App\Controllers;

/**
 * Liff_api — JSON endpoints for LIFF app AJAX calls.
 */
class Liff_api extends Security_Controller {

    protected $Liff_pending_model;

    public function __construct() {
        parent::__construct();
        $this->Liff_pending_model = model('App\Models\Liff_pending_model');
    }

    protected function _check_login() {
        $user_id = $this->Users_model->login_user_id();
        if (!$user_id) {
            $this->_json(['success' => false, 'message' => 'Unauthorized'], 401);
            exit;
        }
        return $user_id;
    }

    // ── Task: save (create / update) ───────────────────────────────
    public function task_save() {
        $user_id = $this->login_user->id;
        $id      = (int)$this->request->getPost('id');

        $data = clean_data([
            'title'                    => $this->request->getPost('title'),
            'description'              => $this->request->getPost('description'),
            'project_id'               => $this->request->getPost('project_id') ?: 0,
            'assigned_to'              => $this->request->getPost('assigned_to') ?: $user_id,
            'collaborators'            => $this->request->getPost('collaborators') ?: '',
            'start_date'               => $this->request->getPost('start_date') ?: null,
            'start_time'               => $this->request->getPost('start_time') ?: null,
            'deadline'                 => $this->request->getPost('deadline') ?: null,
            'end_time'                 => $this->request->getPost('end_time') ?: null,
            'priority_id'              => $this->request->getPost('priority_id') ?: 0,
            'status_id'                => $this->request->getPost('status_id') ?: 0,
            'line_notify_enabled'      => $this->request->getPost('line_notify_enabled') ? 1 : 0,
            'line_notify_before_start' => $this->request->getPost('line_notify_before_start') ?: null,
            'line_notify_before_end'   => $this->request->getPost('line_notify_before_end') ?: null,
            'line_notify_no_update_hours' => $this->request->getPost('line_notify_no_update_hours') ?: null,
            'context'                  => 'general',
            'created_by'               => $id ? null : $user_id,
        ]);

        // If notify is OFF, clear sub-fields
        if (!$data['line_notify_enabled']) {
            $data['line_notify_before_start']    = null;
            $data['line_notify_before_end']      = null;
            $data['line_notify_no_update_hours'] = null;
        }

        // Remove null created_by for updates
        if ($id) { unset($data['created_by']); }

        $save_id = $this->Tasks_model->ci_save($data, $id);

        if (!$save_id) {
            return $this->_json(['success' => false, 'message' => 'บันทึกไม่สำเร็จ']);
        }

        // Handle image uploads (store as project_comments files)
        $this->_save_task_comment_files($save_id, $data['project_id'] ?? 0);

        // Notify assigned user if changed
        if (!$id && $data['assigned_to'] && $data['assigned_to'] != $user_id) {
            $this->_notify_assignment($save_id, $data['assigned_to'], $data['title']);
        }

        return $this->_json(['success' => true, 'id' => $save_id, 'redirect' => get_uri('liff/app/tasks/' . $save_id)]);
    }

    // ── Task: update status ────────────────────────────────────────
    public function task_update_status() {
        $task_id   = (int)$this->request->getPost('task_id');
        $status_id = (int)$this->request->getPost('status_id');

        if (!$task_id || !$status_id) {
            return $this->_json(['success' => false, 'message' => 'Missing params']);
        }

        $status = $this->Task_status_model->get_one($status_id);
        $this->Tasks_model->ci_save([
            'status_id'         => $status_id,
            'status_changed_at' => date('Y-m-d H:i:s'),
        ], $task_id);

        return $this->_json(['success' => true, 'status_title' => $status->title ?? '']);
    }

    // ── Task: upload image ─────────────────────────────────────────
    public function task_upload_image() {
        $task_id = (int)$this->request->getPost('task_id');
        $result  = $this->_save_task_comment_files($task_id, (int)$this->request->getPost('project_id'));
        return $this->_json($result);
    }

    // ── Event: save ────────────────────────────────────────────────
    public function event_save() {
        $user_id = $this->login_user->id;
        $id      = (int)$this->request->getPost('id');

        $target_path = get_setting("timeline_file_path");
        $files_data  = move_files_from_temp_dir_to_permanent_dir($target_path, "event");
        $new_files   = @unserialize($files_data);
        if (!is_array($new_files)) { $new_files = []; }

        $data = clean_data([
            'title'                    => $this->request->getPost('title'),
            'description'              => $this->request->getPost('description'),
            'start_date'               => $this->request->getPost('start_date'),
            'end_date'                 => $this->request->getPost('end_date') ?: null,
            'start_time'               => $this->request->getPost('start_time') ?: null,
            'end_time'                 => $this->request->getPost('end_time') ?: null,
            'color'                    => $this->request->getPost('color') ?: '#6C8EF5',
            'share_with'               => $this->request->getPost('share_with') ?: 'only_me',
            'line_notify_enabled'      => $this->request->getPost('line_notify_enabled') ? 1 : 0,
            'line_notify_before_start' => $this->request->getPost('line_notify_before_start') ?: null,
            'line_notify_before_end'   => $this->request->getPost('line_notify_before_end') ?: null,
            'line_notify_no_update_hours' => $this->request->getPost('line_notify_no_update_hours') ?: null,
            'created_by'               => $id ? null : $user_id,
            'type'                     => 'event',
            'reminder_status'          => 'new',
        ]);

        if (!$data['line_notify_enabled']) {
            $data['line_notify_before_start']    = null;
            $data['line_notify_before_end']      = null;
            $data['line_notify_no_update_hours'] = null;
        }

        if ($id) {
            unset($data['created_by']);
            $event_info = $this->Events_model->get_one($id);
            if ($event_info && $event_info->files) {
                $new_files = update_saved_files($target_path, $event_info->files, $new_files);
            }
        }

        $data['files'] = serialize($new_files);

        $save_id = $this->Events_model->ci_save($data, $id);

        if (!$save_id) {
            return $this->_json(['success' => false, 'message' => 'บันทึกไม่สำเร็จ']);
        }

        return $this->_json(['success' => true, 'id' => $save_id, 'redirect' => get_uri('liff/app/events/' . $save_id)]);
    }

    // ── Event: delete ──────────────────────────────────────────────
    public function event_delete() {
        $id      = (int)$this->request->getPost('id');
        $user_id = $this->login_user->id;

        $event = $this->Events_model->get_one($id);
        if (!$event || ($event->created_by != $user_id && !$this->login_user->is_admin)) {
            return $this->_json(['success' => false, 'message' => 'ไม่มีสิทธิ์ลบ']);
        }

        $this->Events_model->ci_save(['deleted' => 1], $id);
        return $this->_json(['success' => true]);
    }

    // ── Todo: save ─────────────────────────────────────────────────
    public function todo_save() {
        $user_id = $this->login_user->id;
        $id      = (int)$this->request->getPost('id');

        $data = clean_data([
            'title'       => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'start_date'  => $this->request->getPost('start_date') ?: null,
            'labels'      => $this->request->getPost('labels') ?: '',
            'created_by'  => $user_id,
        ]);

        $save_id = $this->Todo_model->ci_save($data, $id);
        return $this->_json(['success' => (bool)$save_id, 'id' => $save_id]);
    }

    // ── Todo: toggle done ──────────────────────────────────────────
    public function todo_toggle() {
        $id   = (int)$this->request->getPost('id');
        $todo = $this->Todo_model->get_one($id);
        if (!$todo) { return $this->_json(['success' => false]); }

        $done    = $todo->status === 'done';
        $new_status = $done ? 'to_do' : 'done';
        $this->Todo_model->ci_save(['status' => $new_status], $id);
        return $this->_json(['success' => true, 'done' => !$done]);
    }

    // ── Task quick update from LINE message ─────────────────────────
    public function task_quick_update() {
        $task_id = (int)$this->request->getPost('task_id');
        $action  = $this->request->getPost('action'); // 'done' | 'snooze'

        if (!$task_id) { return $this->_json(['success' => false]); }

        if ($action === 'done') {
            $closed = $this->db->query(
                "SELECT id FROM rise_task_status WHERE key_name='closed' LIMIT 1"
            )->getRow();
            if ($closed) {
                $this->Tasks_model->ci_save(['status_id' => $closed->id, 'status_changed_at' => date('Y-m-d H:i:s')], $task_id);
            }
        }

        if ($action === 'snooze') {
            // Snooze: postpone next notification by setting updated_at to now
            $this->db->query("UPDATE rise_tasks SET updated_at=NOW() WHERE id=?", [$task_id]);
        }

        return $this->_json(['success' => true]);
    }

    // ── Helpers ────────────────────────────────────────────────────
    private function _save_task_comment_files($task_id, $project_id = 0) {
        $target_path = get_setting("timeline_file_path");
        $files_data  = move_files_from_temp_dir_to_permanent_dir($target_path, "project_comment");

        if (!$files_data || $files_data === "a:0:{}") {
            return ['success' => true, 'files' => []];
        }

        $comment_data = [
            "created_by" => $this->login_user->id,
            "created_at" => get_current_utc_time(),
            "project_id" => (int)$project_id,
            "file_id"    => 0,
            "task_id"    => (int)$task_id,
            "customer_feedback_id" => 0,
            "comment_id" => 0,
            "description" => ""
        ];

        $comment_data = clean_data($comment_data);
        $comment_data["files"] = $files_data; // don't clean serialized data

        $this->Project_comments_model->save_comment($comment_data);
        return ['success' => true];
    }

    private function _notify_assignment($task_id, $assigned_to, $task_title) {
        $map_t  = get_user_mappings_table();
        $mapping = $this->db->query(
            "SELECT line_liff_user_id FROM $map_t WHERE rise_user_id=? AND is_active=1 LIMIT 1",
            [$assigned_to]
        )->getRow();

        if (!$mapping || empty($mapping->line_liff_user_id)) { return; }

        $msg  = "📋 คุณได้รับมอบหมายงานใหม่\n";
        $msg .= "งาน: $task_title\n";
        $msg .= "ดูรายละเอียด: " . get_uri("liff/app/tasks/$task_id");

        $Line = new \App\Libraries\Line_webhook();
        $Line->send_push_message($mapping->line_liff_user_id, $msg, 'user');
    }

    private function _json($data, $code = 200) {
        return $this->response
            ->setStatusCode($code)
            ->setContentType('application/json')
            ->setBody(json_encode($data));
    }
}
