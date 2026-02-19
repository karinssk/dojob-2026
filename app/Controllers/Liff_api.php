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

        // Handle image uploads
        $this->_handle_task_images($save_id);

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
        $result  = $this->_handle_task_images($task_id);
        return $this->_json($result);
    }

    // ── Event: save ────────────────────────────────────────────────
    public function event_save() {
        $user_id = $this->login_user->id;
        $id      = (int)$this->request->getPost('id');

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

        if ($id) { unset($data['created_by']); }

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
    private function _handle_task_images($task_id) {
        $files = $this->request->getFiles();
        if (empty($files['images'])) { return ['success' => true]; }

        $task       = $this->Tasks_model->get_one($task_id);
        $existing   = $task->images ? json_decode($task->images, true) : [];
        $upload_dir = FCPATH . 'files/';

        foreach ($files['images'] as $file) {
            if (!$file->isValid() || $file->hasMoved()) continue;
            $name = $file->getRandomName();
            $file->move($upload_dir, $name);
            $existing[] = $name;
        }

        $this->Tasks_model->ci_save(['images' => json_encode($existing)], $task_id);
        return ['success' => true, 'images' => $existing];
    }

    private function _notify_assignment($task_id, $assigned_to, $task_title) {
        $map_t  = get_user_mappings_table();
        $mapping = $this->db->query(
            "SELECT line_user_id FROM $map_t WHERE rise_user_id=? AND is_active=1 LIMIT 1",
            [$assigned_to]
        )->getRow();

        if (!$mapping) { return; }

        $msg  = "📋 คุณได้รับมอบหมายงานใหม่\n";
        $msg .= "งาน: $task_title\n";
        $msg .= "ดูรายละเอียด: " . get_uri("liff/app/tasks/$task_id");

        $Line = new \App\Libraries\Line_webhook();
        $Line->send_push_message($mapping->line_user_id, $msg, 'user');
    }

    private function _json($data, $code = 200) {
        return $this->response
            ->setStatusCode($code)
            ->setContentType('application/json')
            ->setBody(json_encode($data));
    }
}
