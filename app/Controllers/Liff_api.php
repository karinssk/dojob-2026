<?php

namespace App\Controllers;

/**
 * Liff_api — JSON endpoints for LIFF app AJAX calls.
 */
class Liff_api extends Security_Controller {

    protected $Liff_pending_model;
    protected $Event_comments_model;

    public function __construct() {
        parent::__construct();
        $this->db = \Config\Database::connect();
        $this->Liff_pending_model = model('App\Models\Liff_pending_model');
        $this->Event_comments_model = model('App\Models\Event_comments_model');
    }

    protected function _check_login() {
        $user_id = $this->Users_model->login_user_id();
        if (!$user_id) {
            $this->_json(['success' => false, 'message' => 'Unauthorized'], 401);
            exit;
        }
        return $user_id;
    }

    // ── Task: Quick Assign (from มอบหมาย panel) ───────────────────
    // POST: title, assigned_to, project_id, start_date, start_time,
    //       deadline, end_time, status_id
    // All date/time/project/status values come from server-computed defaults.
    public function quick_assign() {
        $user_id     = $this->login_user->id;
        $title       = trim($this->request->getPost('title') ?? '');
        $assigned_to = (int)$this->request->getPost('assigned_to');

        if (!$title) {
            return $this->_json(['success' => false, 'message' => 'กรุณาระบุชื่องาน']);
        }
        if (!$assigned_to) {
            return $this->_json(['success' => false, 'message' => 'กรุณาเลือกผู้รับงาน']);
        }

        $data = clean_data([
            'title'       => $title,
            'project_id'  => $this->request->getPost('project_id') ?: 0,
            'assigned_to' => $assigned_to,
            'start_date'  => $this->request->getPost('start_date') ?: null,
            'start_time'  => $this->request->getPost('start_time') ?: null,
            'deadline'    => $this->request->getPost('deadline')   ?: null,
            'end_time'    => $this->request->getPost('end_time')   ?: null,
            'status_id'   => $this->request->getPost('status_id')  ?: 0,
            'context'     => 'general',
            'created_by'  => $user_id,
        ]);

        $save_id = $this->Tasks_model->ci_save($data);
        if (!$save_id) {
            return $this->_json(['success' => false, 'message' => 'บันทึกไม่สำเร็จ']);
        }

        // Notify assigned user if different from creator
        if ($assigned_to != $user_id) {
            $this->_notify_assignment($save_id, $assigned_to, $title);
        }

        return $this->_json(['success' => true, 'id' => $save_id]);
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

        // Notify LINE rooms (Group IDs) about task save
        $group_ids_raw = get_setting('line_group_ids');
        if ($group_ids_raw) {
            $ids = preg_split('/[\n,]+/', $group_ids_raw);
            $ids = array_values(array_filter(array_map('trim', $ids)));
            if (!empty($ids)) {
                $Line = new \App\Libraries\Liff_line_webhook();
                $user_name = trim($this->login_user->first_name . ' ' . $this->login_user->last_name);
                $action = $id ? 'อัปเดตงาน' : 'สร้างงาน';
                $msg = "{$action}\n{$data['title']}\nโดย: {$user_name}\n" . get_uri("liff/app/tasks/{$save_id}");
                $meta = [
                    'task_id' => $save_id,
                    'type' => $id ? 'liff_task_updated' : 'liff_task_created',
                    'force_token' => get_setting('line_channel_access_token'),
                    'force_token_label' => 'line_channel_access_token',
                ];
                foreach ($ids as $rid) {
                    $Line->send_push_message($rid, $msg, 'room', $meta);
                }
            }
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

        // Send LINE notification only when status is marked as done
        if (!empty($status->key_name) && strtolower($status->key_name) === 'done') {
            $this->_notify_task_done($task_id);
        }

        return $this->_json(['success' => true, 'status_title' => $status->title ?? '', 'status_key' => $status->key_name ?? '']);
    }

    // ── Notify LINE rooms when a task is marked Done ───────────────
    private function _notify_task_done($task_id) {
        // Get full task info
        $task = $this->Tasks_model->get_one($task_id);
        if (!$task) { return; }

        $task_title = $task->title ?? 'งาน';
        $task_desc  = trim($task->description ?? '');

        // Who marked it done
        $doer_row  = $this->db->query(
            "SELECT first_name, last_name FROM rise_users WHERE id=? LIMIT 1",
            [$this->login_user->id]
        )->getRow();
        $doer_name = $doer_row ? trim($doer_row->first_name . ' ' . $doer_row->last_name) : 'ผู้ใช้';

        // Latest comment on this task (if any) — table is rise_project_comments
        $comments_table = $this->db->prefixTable('project_comments');
        $comment_row = $this->db->query(
            "SELECT description FROM {$comments_table}
             WHERE task_id=? AND deleted=0
             ORDER BY id DESC LIMIT 1",
            [$task_id]
        )->getRow();
        $latest_comment = trim($comment_row->description ?? '');

        // LIFF deep-link — format: https://liff.line.me/{liff-id}/tasks/{task_id}
        $liff_base = rtrim(get_setting('line_liff_id') ?: '2009171467-kn2AHM0C', '/');
        $liff_url  = 'https://liff.line.me/' . $liff_base . '/tasks/' . $task_id;

        // Build body contents
        $body_contents = [
            [
                'type'   => 'text',
                'text'   => '✅ ' . $task_title,
                'weight' => 'bold',
                'size'   => 'md',
                'color'  => '#1A1A2E',
                'wrap'   => true,
            ],
            [
                'type'   => 'separator',
                'margin' => 'sm',
                'color'  => '#E8EAF6',
            ],
        ];

        if ($task_desc) {
            $body_contents[] = [
                'type'     => 'text',
                'text'     => $task_desc,
                'size'     => 'sm',
                'color'    => '#555555',
                'wrap'     => true,
                'maxLines' => 3,
                'margin'   => 'sm',
            ];
        }

        if ($latest_comment) {
            $body_contents[] = [
                'type'            => 'box',
                'layout'          => 'vertical',
                'margin'          => 'sm',
                'paddingAll'      => '10px',
                'backgroundColor' => '#F8FAFF',
                'cornerRadius'    => '8px',
                'contents'        => [
                    [
                        'type'  => 'text',
                        'text'  => '💬 ' . $latest_comment,
                        'size'  => 'sm',
                        'color' => '#444466',
                        'wrap'  => true,
                        'maxLines' => 3,
                    ],
                ],
            ];
        }

        $body_contents[] = [
            'type'  => 'text',
            'text'  => 'โดย: ' . $doer_name,
            'size'  => 'xs',
            'color' => '#888888',
            'margin'=> 'sm',
            'wrap'  => true,
        ];

        $flex = [
            'type'   => 'bubble',
            'size'   => 'kilo',
            'header' => [
                'type'            => 'box',
                'layout'          => 'horizontal',
                'backgroundColor' => '#22C55E',
                'paddingAll'      => '14px',
                'contents'        => [[
                    'type'   => 'text',
                    'text'   => '🎉 อัพเดตงาน เสร็จแล้ว',
                    'color'  => '#FFFFFF',
                    'weight' => 'bold',
                    'size'   => 'md',
                    'flex'   => 1,
                ]],
            ],
            'body' => [
                'type'       => 'box',
                'layout'     => 'vertical',
                'paddingAll' => '16px',
                'spacing'    => 'sm',
                'contents'   => $body_contents,
            ],
            'footer' => [
                'type'       => 'box',
                'layout'     => 'vertical',
                'paddingAll' => '12px',
                'contents'   => [[
                    'type'   => 'button',
                    'style'  => 'primary',
                    'color'  => '#22C55E',
                    'height' => 'sm',
                    'action' => [
                        'type'  => 'uri',
                        'label' => 'ดูรายละเอียดงาน',
                        'uri'   => $liff_url,
                    ],
                ]],
            ],
        ];

        $alt_text = '🎉 อัพเดตงาน เสร็จแล้ว: ' . mb_substr($task_title, 0, 50);

        $Line  = new \App\Libraries\Liff_line_webhook();
        $rooms = $this->_get_liff_rooms();

        foreach ($rooms as $rid) {
            $Line->send_flex_message($rid, $flex, $alt_text, 'room', [
                'task_id' => $task_id,
                'type'    => 'liff_task_done',
            ]);
        }
    }

    // ── Task: test notify to LIFF rooms (immediate) ─────────────────
    public function task_notify_test() {
        $task_id    = (int)$this->request->getPost('id');
        $title      = trim($this->request->getPost('title') ?? '');
        $start_date = $this->request->getPost('start_date') ?: null;
        $start_time = $this->request->getPost('start_time') ?: null;
        $deadline   = $this->request->getPost('deadline') ?: null;
        $end_time   = $this->request->getPost('end_time') ?: null;

        $rooms = $this->_get_liff_rooms();
        if (empty($rooms)) {
            return $this->_json(['success' => false, 'message' => 'ยังไม่ได้เลือกห้องสำหรับแจ้งเตือน (ดูที่ Settings > LIFF)']);
        }

        $title = $title ?: 'งาน';
        $msg  = "🧪 ทดสอบแจ้งเตือนงาน\n";
        $msg .= " {$title}\n";
        if ($start_date && $start_time) {
            $msg .= "⏱ เริ่ม: " . date('d/m H:i', strtotime($start_date . ' ' . $start_time)) . "\n";
        }
        if ($deadline && $end_time) {
            $msg .= "🔚 สิ้นสุด: " . date('d/m H:i', strtotime($deadline . ' ' . $end_time)) . "\n";
        }
        if ($task_id) {
            $msg .= get_uri("liff/app/tasks/{$task_id}");
        }

        $Line = new \App\Libraries\Liff_line_webhook();
        $results = [];
        $ok = true;
        foreach ($rooms as $rid) {
            $res = $Line->send_push_message($rid, $msg, 'room');
            $results[] = [
                'room_id' => $rid,
                'success' => (bool)($res['success'] ?? false),
                'error'   => $res['error'] ?? ''
            ];
            if (empty($res['success'])) { $ok = false; }
        }

        return $this->_json([
            'success' => $ok,
            'message' => $ok ? 'ส่งทดสอบสำเร็จ' : 'ส่งทดสอบบางห้องไม่สำเร็จ',
            'results' => $results
        ]);
    }

    // ── Task: upload image ─────────────────────────────────────────
    public function task_upload_image() {
        $task_id = (int)$this->request->getPost('task_id');
        $result  = $this->_save_task_comment_files($task_id, (int)$this->request->getPost('project_id'));
        return $this->_json($result);
    }

    // ── Task: save comment (with images) ──────────────────────────
    public function task_comment_save() {
        $task_id    = (int)$this->request->getPost('task_id');
        $project_id = (int)$this->request->getPost('project_id');
        $desc       = trim($this->request->getPost('description') ?? '');

        if (!$task_id) {
            return $this->_json(['success' => false, 'message' => 'Missing task_id'], 400);
        }

        $target_path = get_setting("timeline_file_path");
        $files_data  = move_files_from_temp_dir_to_permanent_dir($target_path, "project_comment");
        $has_files   = ($files_data && $files_data !== "a:0:{}");

        if (!$desc && !$has_files) {
            return $this->_json(['success' => false, 'message' => 'กรุณากรอกข้อความหรือแนบไฟล์อย่างน้อย 1 รายการ'], 422);
        }

        $data = [
            "created_by" => $this->login_user->id,
            "created_at" => get_current_utc_time(),
            "project_id" => $project_id,
            "file_id"    => 0,
            "task_id"    => $task_id,
            "customer_feedback_id" => 0,
            "comment_id" => 0,
            "description" => $desc
        ];

        $data = clean_data($data);
        $data["files"] = $files_data; // don't clean serialized data

        $save_id = $this->Project_comments_model->save_comment($data);
        if (!$save_id) {
            return $this->_json(['success' => false, 'message' => 'บันทึกความคิดเห็นไม่สำเร็จ']);
        }

        $comment = $this->Project_comments_model->get_details([
            'id' => $save_id
        ])->getRow();

        $images = [];
        $files = $files_data ? @unserialize($files_data) : [];
        if (is_array($files)) {
            foreach ($files as $file) {
                if (!is_array($file)) { continue; }
                $file_name = $file['file_name'] ?? '';
                if (!$file_name || !is_image_file($file_name)) { continue; }
                $images[] = [
                    'thumb' => get_source_url_of_file($file, get_setting("timeline_file_path"), "thumbnail"),
                    'full'  => get_source_url_of_file($file, get_setting("timeline_file_path")),
                ];
            }
        }

        return $this->_json([
            'success' => true,
            'comment' => [
                'id' => $comment->id ?? $save_id,
                'user_name' => $comment->created_by_user ?? trim($this->login_user->first_name . ' ' . $this->login_user->last_name),
                'user_avatar' => get_avatar($comment->created_by_avatar ?? $this->login_user->image ?? ''),
                'created_at' => $comment->created_at ?? get_current_utc_time(),
                'created_at_label' => date('d M H:i', strtotime($comment->created_at ?? 'now')),
                'description' => $comment->description ?? $desc,
                'images' => $images,
            ]
        ]);
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

    // ── Event: save comment (with images) ─────────────────────────
    public function event_comment_save() {
        $event_id = (int)$this->request->getPost('event_id');
        $desc     = trim($this->request->getPost('description') ?? '');

        if (!$event_id) {
            return $this->_json(['success' => false, 'message' => 'Missing event_id'], 400);
        }

        $target_path = get_setting("timeline_file_path");
        $files_data  = move_files_from_temp_dir_to_permanent_dir($target_path, "event_comment");
        $has_files   = ($files_data && $files_data !== "a:0:{}");

        if (!$desc && !$has_files) {
            return $this->_json(['success' => false, 'message' => 'กรุณากรอกข้อความหรือแนบไฟล์อย่างน้อย 1 รายการ'], 422);
        }

        $data = [
            "event_id"   => $event_id,
            "description"=> $desc,
            "files"      => $files_data,
            "created_by" => $this->login_user->id,
            "created_at" => get_current_utc_time(),
            "deleted"    => 0
        ];

        $data = clean_data($data);
        $data["files"] = $files_data; // keep serialized

        $save_id = $this->Event_comments_model->ci_save($data);
        if (!$save_id) {
            return $this->_json(['success' => false, 'message' => 'บันทึกความคิดเห็นไม่สำเร็จ']);
        }

        return $this->_json(['success' => true]);
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

    // ── Event: confirm done (manual) ─────────────────────────────
    public function event_confirm() {
        $event_id = (int)$this->request->getPost('event_id');
        if (!$event_id) {
            return $this->_json(['success' => false, 'message' => 'Missing event_id'], 400);
        }

        $event = $this->Events_model->get_one($event_id);
        if (!$event || $event->deleted) {
            return $this->_json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user_id = $this->login_user->id;
        $this->Events_model->save_event_status($event_id, $user_id, "confirmed");
        $this->Events_model->ci_save(['reminder_status' => 'done'], $event_id);

        $end_date = $event->end_date ?: $event->start_date;
        // Notify LINE rooms (Group IDs)
        $group_ids_raw = get_setting('line_group_ids');
        if ($group_ids_raw) {
            $ids = preg_split('/[\n,]+/', $group_ids_raw);
            $ids = array_values(array_filter(array_map('trim', $ids)));
            if (!empty($ids)) {
                $Line = new \App\Libraries\Liff_line_webhook();
                $user_name = trim($this->login_user->first_name . ' ' . $this->login_user->last_name);
                $date_label = $end_date ? date('d/m/y', strtotime($end_date)) : '-';
                $msg = " ยืนยันกิจกรรมเสร็จแล้ว\n{$event->title}\nวันที่: {$date_label}\nโดย: {$user_name}";
                $meta = [
                    'event_id' => $event_id,
                    'type' => 'liff_event_confirmed',
                    'force_token' => get_setting('line_channel_access_token'),
                    'force_token_label' => 'line_channel_access_token',
                ];
                foreach ($ids as $rid) {
                    $Line->send_push_message($rid, $msg, 'room', $meta);
                }
            }
        }

        return $this->_json(['success' => true]);
    }

    // ── Event: calendar data (month/week/day) ─────────────────────
    public function events_calendar() {
        $user_id = $this->login_user->id;
        $start   = $this->request->getPost('start');
        $end     = $this->request->getPost('end');

        if (!$start || !$end) {
            return $this->_json(['success' => false, 'message' => 'Missing date range'], 400);
        }
        $options = [
            "user_id"          => $user_id,
            "team_ids"         => $this->login_user->team_ids,
            "start_date"       => $start,
            "end_date"         => $end,
            "include_recurring"=> true,
            "type"             => "event",
        ];

        $list = $this->Events_model->get_details($options)->getResult();
        $result = [];

        foreach ($list as $event) {
            $base_start = $event->start_date;
            $base_end   = $event->end_date ?: $event->start_date;

            if ($this->_event_in_range($base_start, $base_end, $start, $end)) {
                $result[] = $this->_map_liff_event($event, $base_start, $base_end);
            }

            if (!empty($event->recurring)) {
                $no_of_cycles = $this->Events_model->get_no_of_cycles($event->repeat_type, $event->no_of_cycles);
                $cycle_start = $base_start;
                $cycle_end   = $base_end;

                for ($i = 1; $i <= $no_of_cycles; $i++) {
                    $cycle_start = add_period_to_date($cycle_start, $event->repeat_every, $event->repeat_type);
                    $cycle_end   = add_period_to_date($cycle_end, $event->repeat_every, $event->repeat_type);

                    if ($this->_event_in_range($cycle_start, $cycle_end, $start, $end)) {
                        $result[] = $this->_map_liff_event($event, $cycle_start, $cycle_end);
                    }
                }
            }
        }

        return $this->_json(['success' => true, 'events' => $result]);
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

    private function _event_in_range($event_start, $event_end, $range_start, $range_end) {
        if (!$event_start) { return false; }
        $event_end = $event_end ?: $event_start;
        return ($event_start <= $range_end) && ($event_end >= $range_start);
    }

    private function _map_liff_event($event, $start_date, $end_date) {
        return (object)[
            "id"        => $event->id,
            "title"     => $event->title,
            "start_date"=> $start_date,
            "end_date"  => $end_date,
            "start_time"=> $event->start_time,
            "end_time"  => $event->end_time,
            "color"     => $event->color ?: "#4F7DF3",
        ];
    }

    private function _notify_assignment($task_id, $assigned_to, $task_title) {
        $map_t   = get_user_mappings_table();
        $mapping = $this->db->query(
            "SELECT line_liff_user_id FROM $map_t WHERE rise_user_id=? AND is_active=1 LIMIT 1",
            [$assigned_to]
        )->getRow();

        // Get assignee's display name
        $user_row  = $this->db->query(
            "SELECT first_name, last_name FROM rise_users WHERE id=? LIMIT 1",
            [$assigned_to]
        )->getRow();
        $user_name = $user_row ? trim($user_row->first_name . ' ' . $user_row->last_name) : 'คุณ';

        // Sender name (person who assigned the task)
        $sender_row  = $this->db->query(
            "SELECT first_name, last_name FROM rise_users WHERE id=? LIMIT 1",
            [$this->login_user->id]
        )->getRow();
        $sender_name = $sender_row ? trim($sender_row->first_name . ' ' . $sender_row->last_name) : '';

        // LIFF deep-link URL — format: https://liff.line.me/{liff-id}/tasks/{task_id}
        $liff_base = rtrim(get_setting('line_liff_id') ?: '2009171467-kn2AHM0C', '/');
        $liff_url  = 'https://liff.line.me/' . $liff_base . '/tasks/' . $task_id;

        // Build Flex Message bubble
        $flex = [
            'type'   => 'bubble',
            'size'   => 'kilo',
            'header' => [
                'type'            => 'box',
                'layout'          => 'horizontal',
                'backgroundColor' => '#4F7DF3',
                'paddingAll'      => '14px',
                'contents'        => [
                    [
                        'type'   => 'text',
                        'text'   => ' งานใหม่',
                        'color'  => '#FFFFFF',
                        'weight' => 'bold',
                        'size'   => 'md',
                        'flex'   => 1,
                    ],
                ],
            ],
            'body' => [
                'type'       => 'box',
                'layout'     => 'vertical',
                'paddingAll' => '16px',
                'spacing'    => 'sm',
                'contents'   => [
                    [
                        'type'   => 'text',
                        'text'   => $user_name . ' ได้รับมอบหมายงานใหม่',
                        'weight' => 'bold',
                        'size'   => 'md',
                        'color'  => '#1A1A2E',
                        'wrap'   => true,
                    ],
                    [
                        'type'    => 'separator',
                        'margin'  => 'sm',
                        'color'   => '#E8EAF6',
                    ],
                    [
                        'type'    => 'box',
                        'layout'  => 'vertical',
                        'margin'  => 'sm',
                        'spacing' => 'xs',
                        'contents' => [
                            [
                                'type'   => 'text',
                                'text'   => $task_title,
                                'size'   => 'sm',
                                'color'  => '#333333',
                                'wrap'   => true,
                                'maxLines' => 3,
                            ],
                            ($sender_name ? [
                                'type'  => 'text',
                                'text'  => 'มอบหมายโดย: ' . $sender_name,
                                'size'  => 'xs',
                                'color' => '#888888',
                                'wrap'  => true,
                            ] : ['type' => 'filler']),
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type'       => 'box',
                'layout'     => 'vertical',
                'paddingAll' => '12px',
                'contents'   => [
                    [
                        'type'   => 'button',
                        'style'  => 'primary',
                        'color'  => '#4F7DF3',
                        'height' => 'sm',
                        'action' => [
                            'type'  => 'uri',
                            'label' => 'ดูรายละเอียดงาน',
                            'uri'   => $liff_url,
                        ],
                    ],
                ],
            ],
        ];

        $alt_text = $user_name . ' ได้รับมอบหมายงานใหม่: ' . mb_substr($task_title, 0, 50);

        $mode  = get_setting('liff_notify_mode') ?: 'user';
        $rooms = $this->_get_liff_rooms();

        $Line = new \App\Libraries\Liff_line_webhook();

        if ($mode === 'room' && !empty($rooms)) {
            foreach ($rooms as $rid) {
                $Line->send_flex_message($rid, $flex, $alt_text, 'room', [
                    'task_id' => $task_id,
                    'type' => 'liff_task_assignment'
                ]);
            }
            return;
        }

        if (!$mapping || empty($mapping->line_liff_user_id)) { return; }
        $Line->send_flex_message($mapping->line_liff_user_id, $flex, $alt_text, 'user', [
            'task_id' => $task_id,
            'type' => 'liff_task_assignment'
        ]);
    }

    private function _get_liff_rooms() {
        $raw = get_setting('liff_notify_rooms');
        $arr = $raw ? json_decode($raw, true) : [];
        if (!is_array($arr)) { return []; }
        return array_values(array_filter($arr));
    }

    private function _json($data, $code = 200) {
        return $this->response
            ->setStatusCode($code)
            ->setContentType('application/json')
            ->setBody(json_encode($data));
    }
}
