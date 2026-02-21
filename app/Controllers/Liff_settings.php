<?php

namespace App\Controllers;

/**
 * Liff_settings — Admin hub for LINE LIFF credentials and user approvals.
 * Accessible at /settings/approve_line_liff_users
 */
class Liff_settings extends Security_Controller {

    protected $Liff_pending_model;

    public function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
        $this->Liff_pending_model = model('App\Models\Liff_pending_model');
    }

    // ──────────────────────────────────────────────────────────────
    // Main hub page (4 tabs)
    // ──────────────────────────────────────────────────────────────
    public function approve_line_liff_users() {
        $tab = $this->request->getGet('tab') ?: 'credentials';

        $view_data = [
            'page_title'    => 'LINE LIFF Settings',
            'active_tab'    => 'approve_line_liff_users',
            'current_tab'   => $tab,
            'pending_count' => $this->Liff_pending_model->get_pending_count(),
            // credentials
            'liff_line_channel_access_token'  => get_setting('liff_line_channel_access_token') ?: get_setting('line_channel_access_token'),
            'liff_line_channel_secret'        => get_setting('liff_line_channel_secret') ?: get_setting('line_channel_secret'),
            'line_login_channel_id'      => get_setting('line_login_channel_id'),
            'line_login_channel_secret'  => get_setting('line_login_channel_secret'),
            'line_liff_id'               => get_setting('line_liff_id'),
            'line_admin_notify_uids'     => get_setting('line_admin_notify_uids'),
            'liff_notify_default_start'  => get_setting('liff_notify_default_start'),
            'liff_notify_default_end'    => get_setting('liff_notify_default_end'),
            'liff_notify_default_update' => get_setting('liff_notify_default_update'),
            'liff_notify_mode'           => get_setting('liff_notify_mode') ?: 'user',
            'liff_notify_rooms'          => $this->_decode_json_setting('liff_notify_rooms'),
            'liff_line_rooms'            => $this->_decode_json_setting('liff_line_rooms'),
            // task notification settings
            'liff_reminder_enabled'  => get_setting('liff_reminder_enabled')  ?: '0',
            'liff_reminder_repeat'   => get_setting('liff_reminder_repeat')   ?: '1',
            'liff_reminder_times'    => get_setting('liff_reminder_times')    ?: '["09:00","15:00"]',
            'liff_reminder_days'     => get_setting('liff_reminder_days')     ?: '[1,2,3,4,5]',
            'liff_summary_enabled'   => get_setting('liff_summary_enabled')   ?: '0',
            'liff_summary_time'      => get_setting('liff_summary_time')      ?: '08:00',
            'liff_summary_days'      => get_setting('liff_summary_days')      ?: '[1,2,3,4,5]',
        ];

        return $this->template->rander('settings/approve_line_liff_users', $view_data);
    }

    // ──────────────────────────────────────────────────────────────
    // Save credentials
    // ──────────────────────────────────────────────────────────────
    public function save_liff_credentials() {
        $settings = [
            'liff_line_channel_access_token',
            'liff_line_channel_secret',
            'line_login_channel_id',
            'line_login_channel_secret',
            'line_liff_id',
            'line_admin_notify_uids',
            'liff_notify_default_start',
            'liff_notify_default_end',
            'liff_notify_default_update',
            'liff_notify_mode',
            'liff_notify_rooms',
        ];

        foreach ($settings as $key) {
            $val = $this->request->getPost($key);
            if ($key === 'liff_notify_rooms') {
                if (is_array($val)) {
                    $val = json_encode(array_values($val));
                } elseif ($val === null) {
                    $val = json_encode([]);
                }
            }
            if ($key === 'liff_notify_mode' && !$val) {
                $val = 'user';
            }
            if ($val !== null) {
                $this->Settings_model->save_setting($key, $val);
            }
        }

        echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าสำเร็จ']);
    }

    // ──────────────────────────────────────────────────────────────
    // Test Messaging API connection
    // ──────────────────────────────────────────────────────────────
    public function test_line_messaging_api() {
        $token = get_setting('liff_line_channel_access_token') ?: get_setting('line_channel_access_token');
        $debug = [
            'endpoint'      => 'https://api.line.me/v2/bot/info',
            'token_present' => (bool)$token,
            'token_length'  => $token ? strlen($token) : 0,
            'token_preview' => $token ? (substr($token, 0, 6) . '...' . substr($token, -4)) : '',
            'timestamp'     => date('c'),
        ];
        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Channel Access Token ยังไม่ได้ตั้งค่า',
                'debug'   => $debug,
            ]);
        }

        $ch = curl_init($debug['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $start = microtime(true);
        $res   = curl_exec($ch);
        $debug['curl_errno'] = curl_errno($ch);
        $debug['curl_error'] = curl_error($ch);
        $debug['http_code']  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $debug['elapsed_ms'] = (int)round((microtime(true) - $start) * 1000);
        curl_close($ch);

        $data = json_decode($res, true);
        $debug['response_raw']  = $res;
        $debug['response_json'] = $data;

        if ($debug['http_code'] === 200 && isset($data['displayName'])) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'เชื่อมต่อสำเร็จ — Bot: ' . $data['displayName'],
                'debug'   => $debug,
            ]);
        }

        $error_msg = $data['message'] ?? ('HTTP ' . ($debug['http_code'] ?? ''));
        if ($debug['curl_errno']) {
            $error_msg .= ' (cURL: ' . $debug['curl_error'] . ')';
        }
        return $this->response->setJSON([
            'success' => false,
            'message' => '❌ เชื่อมต่อไม่ได้ — ' . $error_msg,
            'debug'   => $debug,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Test LINE Login channel (verify with dummy check)
    // ──────────────────────────────────────────────────────────────
    public function test_line_login_channel() {
        $channel_id = get_setting('line_login_channel_id');
        $liff_id    = get_setting('line_liff_id');
        $prefix     = '';
        $format_ok  = false;

        if ($liff_id && strpos($liff_id, '-') !== false) {
            $prefix = explode('-', $liff_id)[0];
        }
        if ($liff_id) {
            $format_ok = (bool)preg_match('/^[0-9]+-[A-Za-z0-9]+$/', $liff_id);
        }

        $debug = [
            'line_login_channel_id'   => $channel_id,
            'line_liff_id'            => $liff_id,
            'liff_id_prefix'          => $prefix,
            'liff_id_format_ok'       => $format_ok,
            'prefix_matches_channel'  => ($channel_id && $prefix) ? ($prefix === $channel_id) : null,
            'liff_url'                => get_uri('liff'),
            'timestamp'               => date('c'),
        ];

        if (!$channel_id || !$liff_id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Login Channel ID หรือ LIFF ID ยังไม่ได้ตั้งค่า',
                'debug'   => $debug,
            ]);
        }

        $msg = 'ค่า LIFF ID และ Login Channel ID ถูกบันทึกแล้ว (ทดสอบจริงต้องใช้งานผ่าน LINE)';
        if (!$format_ok) {
            $msg = '⚠️ รูปแบบ LIFF ID ไม่ถูกต้อง (ควรเป็น {ChannelId}-xxxx)';
        } elseif ($debug['prefix_matches_channel'] === false) {
            $msg = '⚠️ LIFF ID ไม่ตรงกับ Login Channel ID';
        }

        return $this->response->setJSON([
            'success' => $format_ok,
            'message' => $msg,
            'debug'   => $debug,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // LIFF webhook debug (last request)
    // ──────────────────────────────────────────────────────────────
    public function get_liff_webhook_debug() {
        $raw = get_setting('liff_webhook_last_debug');
        $data = $raw ? json_decode($raw, true) : null;
        return $this->response->setJSON([
            'success' => true,
            'data' => $data
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Toggle direct LIFF notifications per user
    // ──────────────────────────────────────────────────────────────
    public function toggle_liff_user_notify() {
        $rise_user_id = (int)$this->request->getPost('rise_user_id');
        $enabled      = $this->request->getPost('enabled') ? 1 : 0;

        if (!$rise_user_id) {
            echo json_encode(['success' => false, 'message' => 'Missing user']);
            return;
        }

        $map_t = get_user_mappings_table();
        $this->db->query(
            "UPDATE $map_t SET liff_notify_user=? WHERE rise_user_id=?",
            [$enabled, $rise_user_id]
        );

        echo json_encode(['success' => true, 'message' => 'อัปเดตการแจ้งเตือนสำเร็จ']);
    }

    // ──────────────────────────────────────────────────────────────
    // Save task notification settings (tab: notifications)
    // ──────────────────────────────────────────────────────────────
    public function save_liff_notification_settings() {
        $plain = [
            'liff_reminder_enabled',
            'liff_reminder_repeat',
            'liff_reminder_times',   // already JSON-encoded by JS
            'liff_reminder_days',    // already JSON-encoded by JS
            'liff_summary_enabled',
            'liff_summary_time',
            'liff_summary_days',     // already JSON-encoded by JS
            'line_channel_access_token_fall_back',
            'line_default_room_id_fall_back',
        ];

        foreach ($plain as $key) {
            $val = $this->request->getPost($key);
            if ($val === null) { $val = '0'; }  // unchecked checkboxes
            $this->Settings_model->save_setting($key, $val);
        }

        echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าแจ้งเตือนสำเร็จ']);
    }

    // ──────────────────────────────────────────────────────────────
    // Test fallback bot — send a real text message to fallback room
    // ──────────────────────────────────────────────────────────────
    public function test_liff_fallback() {
        $token   = get_setting('line_channel_access_token_fall_back')   ?: get_setting('line_channel_access_token');
        $room_id = get_setting('line_default_room_id_fall_back') ?: get_setting('line_default_room_id');

        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ยังไม่ได้ตั้งค่า Fallback Token',
            ]);
        }
        if (!$room_id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ยังไม่ได้ตั้งค่า Fallback Room ID',
            ]);
        }

        $msg = '[Fallback Test] ทดสอบระบบแจ้งเตือนสำรอง — ' . date('d/m/Y H:i:s');

        $payload = json_encode([
            'to'       => $room_id,
            'messages' => [['type' => 'text', 'text' => $msg]],
        ]);

        $ch = curl_init('https://api.line.me/v2/bot/message/push');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'User-Agent: DoJob-LIFF/1.0',
            ],
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err  = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            return $this->response->setJSON(['success' => false, 'message' => 'cURL error: ' . $curl_err]);
        }
        if ($http_code >= 200 && $http_code < 300) {
            return $this->response->setJSON(['success' => true, 'message' => 'ส่งสำเร็จ (HTTP ' . $http_code . ')']);
        }

        $decoded = json_decode($response, true);
        $detail  = $decoded['message'] ?? ('HTTP ' . $http_code . ': ' . $response);
        return $this->response->setJSON(['success' => false, 'message' => $detail]);
    }

    // ──────────────────────────────────────────────────────────────
    // Test notification — trigger immediately (ignores schedule)
    // ──────────────────────────────────────────────────────────────
    public function test_liff_notification() {
        $type = $this->request->getPost('type');   // 'reminder' or 'summary'

        $has_token = get_setting('liff_line_channel_access_token') || get_setting('line_channel_access_token');
        if (!$has_token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ยังไม่ได้ตั้งค่า Channel Access Token',
            ]);
        }

        try {
            $cron = new \App\Libraries\Cron_job();
            if ($type === 'reminder') {
                $count = $cron->run_task_reminder_test();
                $msg   = $count > 0
                    ? "ส่งแจ้งเตือนสำเร็จ ({$count} ราย)"
                    : 'ไม่มีงานค้างในระบบ (ไม่ส่ง)';
            } else {
                $count = $cron->run_task_summary_test();
                $msg   = $count > 0
                    ? "ส่งรายงานสรุปสำเร็จ ({$count} รายการงาน)"
                    : 'ไม่มีงานที่เสร็จใน 7 วันที่ผ่านมา (ไม่ส่ง)';
            }

            return $this->response->setJSON(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // GET LINE Push Message quota + consumption this month
    // LINE API:
    //   GET /v2/bot/message/quota          → { type, value }
    //   GET /v2/bot/message/quota/consumption → { totalUsage }
    // ──────────────────────────────────────────────────────────────
    public function get_push_quota() {
        $token = get_setting('liff_line_channel_access_token') ?: get_setting('line_channel_access_token');
        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ยังไม่ได้ตั้งค่า Channel Access Token',
            ]);
        }

        $headers = [
            'Authorization: Bearer ' . $token,
            'User-Agent: DoJob-LIFF/1.0',
        ];

        // Helper: call LINE API and return decoded JSON
        $call = function($url) use ($headers) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            return [$code, $body ? json_decode($body, true) : null, $err];
        };

        [$code1, $quota,  $err1] = $call('https://api.line.me/v2/bot/message/quota');
        [$code2, $usage,  $err2] = $call('https://api.line.me/v2/bot/message/quota/consumption');

        if ($err1 || $code1 !== 200) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ดึง quota ไม่สำเร็จ: HTTP ' . $code1 . ($err1 ? ' / ' . $err1 : ''),
            ]);
        }

        $data = [
            'type'       => $quota['type']       ?? 'unknown',   // 'limited' | 'unlimited'
            'value'      => (int)($quota['value'] ?? 0),          // monthly limit (0 = unlimited)
            'totalUsage' => (int)($usage['totalUsage'] ?? 0),     // used this month
        ];

        return $this->response->setJSON(['success' => true, 'data' => $data]);
    }

    // ──────────────────────────────────────────────────────────────
    // Get schedule status for notifications tab (AJAX)
    // ──────────────────────────────────────────────────────────────
    public function get_liff_notification_schedule_status() {
        $now = time();
        $last_hourly = (int)get_setting('last_hourly_job_time');
        $cron_ok = $last_hourly && ($now - $last_hourly) < 7200; // ran within 2 hours

        $reminder_enabled = get_setting('liff_reminder_enabled') === '1';
        $r_times = json_decode(get_setting('liff_reminder_times') ?: '[]', true) ?: [];
        $r_days  = json_decode(get_setting('liff_reminder_days')  ?: '[]', true) ?: [];
        $reminder_last = get_setting('liff_reminder_last_sent');

        $summary_enabled = get_setting('liff_summary_enabled') === '1';
        $s_time = get_setting('liff_summary_time') ?: '08:00';
        $s_days = json_decode(get_setting('liff_summary_days') ?: '[]', true) ?: [];
        $summary_last = get_setting('liff_summary_last_sent');

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'cron_ok'          => $cron_ok,
                'last_hourly_run'  => $last_hourly ? date('d/m/Y H:i:s', $last_hourly) : null,
                'last_hourly_ago'  => $last_hourly ? $this->_human_diff($now - $last_hourly) : null,
                'reminder_enabled' => $reminder_enabled,
                'reminder_last'    => $reminder_last ?: null,
                'reminder_next'    => $reminder_enabled ? $this->_calc_next_slot($r_times, $r_days) : null,
                'summary_enabled'  => $summary_enabled,
                'summary_last'     => $summary_last ?: null,
                'summary_next'     => $summary_enabled ? $this->_calc_next_slot([$s_time], $s_days) : null,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Force-run scheduled notification now (bypasses time check)
    // ──────────────────────────────────────────────────────────────
    public function force_run_liff_scheduled() {
        $type = $this->request->getPost('type'); // 'reminder' | 'summary'
        $has_token = get_setting('liff_line_channel_access_token') || get_setting('line_channel_access_token');
        if (!$has_token) {
            return $this->response->setJSON(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า Channel Access Token']);
        }

        try {
            $cron = new \App\Libraries\Cron_job();
            if ($type === 'reminder') {
                $count = $cron->run_task_reminder_test();
                $this->Settings_model->save_setting('liff_reminder_last_sent', get_current_utc_time());
                $msg = $count > 0 ? "ส่งแจ้งเตือนสำเร็จ ({$count} ราย)" : 'ไม่มีงานค้าง (ไม่ส่ง)';
            } else {
                $count = $cron->run_task_summary_test();
                $this->Settings_model->save_setting('liff_summary_last_sent', get_current_utc_time());
                $msg = $count > 0 ? "ส่งรายงานสำเร็จ ({$count} รายการ)" : 'ไม่มีงานเสร็จใน 7 วัน (ไม่ส่ง)';
            }
            return $this->response->setJSON(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Get/clear LIFF notification debug log
    // ──────────────────────────────────────────────────────────────
    public function get_liff_notify_log() {
        $log = get_setting('liff_notify_debug_log') ?: '';
        return $this->response->setJSON(['success' => true, 'log' => $log]);
    }

    public function clear_liff_notify_log() {
        $this->Settings_model->save_setting('liff_notify_debug_log', '');
        return $this->response->setJSON(['success' => true]);
    }

    /** Calculate next scheduled slot from a list of times and allowed ISO weekdays (1=Mon,7=Sun) */
    private function _calc_next_slot($times, $days) {
        if (empty($times) || empty($days)) { return null; }
        $now = time();
        $min_future = PHP_INT_MAX;
        for ($d = 0; $d <= 7; $d++) {
            $check_date = strtotime('+' . $d . ' days', strtotime(date('Y-m-d')));
            $dow = (int)date('N', $check_date);
            if (!in_array($dow, $days)) { continue; }
            foreach ($times as $t) {
                $parts = explode(':', $t);
                if (count($parts) < 2) { continue; }
                $slot_ts = mktime((int)$parts[0], (int)$parts[1], 0,
                    (int)date('n', $check_date),
                    (int)date('j', $check_date),
                    (int)date('Y', $check_date));
                if ($slot_ts > $now && $slot_ts < $min_future) {
                    $min_future = $slot_ts;
                }
            }
        }
        return $min_future === PHP_INT_MAX ? null : date('d/m/Y H:i', $min_future);
    }

    /** Human-readable time diff in Thai */
    private function _human_diff($seconds) {
        if ($seconds < 60)   { return $seconds . ' วินาทีที่แล้ว'; }
        if ($seconds < 3600) { return floor($seconds / 60) . ' นาทีที่แล้ว'; }
        if ($seconds < 86400){ return floor($seconds / 3600) . ' ชั่วโมงที่แล้ว'; }
        return floor($seconds / 86400) . ' วันที่แล้ว';
    }

    private function _decode_json_setting($key) {
        $raw = get_setting($key);
        if (!$raw) { return []; }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    // ──────────────────────────────────────────────────────────────
    // Get pending list (AJAX for tab)
    // ──────────────────────────────────────────────────────────────
    public function liff_pending_list() {
        $rows = $this->Liff_pending_model->get_details(['status' => 'pending'])->getResult();
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    // ──────────────────────────────────────────────────────────────
    // Get pending count (badge)
    // ──────────────────────────────────────────────────────────────
    public function liff_pending_count() {
        echo json_encode(['count' => $this->Liff_pending_model->get_pending_count()]);
    }

    // ──────────────────────────────────────────────────────────────
    // Approve a pending request
    // ──────────────────────────────────────────────────────────────
    public function approve_line_liff_user() {
        $id = (int)$this->request->getPost('id');
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
            return;
        }

        $pending = $this->Liff_pending_model->get_one($id);
        if (!$pending) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล']);
            return;
        }

        $admin_id = $this->login_user->id;
        $this->Liff_pending_model->approve(
            $id,
            $admin_id,
            $pending->line_uid,
            $pending->rise_user_id,
            $pending->line_display_name
        );

        // Notify the user via LINE
        $this->_notify_user_approved($pending->line_uid, $pending->line_display_name);

        echo json_encode(['success' => true, 'message' => 'อนุมัติสำเร็จ']);
    }

    // ──────────────────────────────────────────────────────────────
    // Reject a pending request
    // ──────────────────────────────────────────────────────────────
    public function reject_line_liff_user() {
        $id   = (int)$this->request->getPost('id');
        $note = trim($this->request->getPost('note') ?? '');

        $pending = $this->Liff_pending_model->get_one($id);
        if (!$pending) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล']);
            return;
        }

        $this->Liff_pending_model->reject($id, $note);

        // Notify user via LINE
        $this->_notify_user_rejected($pending->line_uid, $pending->line_display_name, $note);

        echo json_encode(['success' => true, 'message' => 'ปฏิเสธสำเร็จ']);
    }

    // ──────────────────────────────────────────────────────────────
    // Revoke an approved mapping
    // ──────────────────────────────────────────────────────────────
    public function revoke_line_liff_user() {
        $id = (int)$this->request->getPost('id');

        $pending = $this->Liff_pending_model->get_one($id);
        if (!$pending) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล']);
            return;
        }

        $this->Liff_pending_model->revoke_by_line_uid($pending->line_uid);
        $this->Liff_pending_model->ci_save(['status' => 'pending'], $id);

        echo json_encode(['success' => true, 'message' => 'ถอนสิทธิ์สำเร็จ — ย้ายกลับเป็น pending']);
    }

    // ──────────────────────────────────────────────────────────────
    // Re-open a rejected request
    // ──────────────────────────────────────────────────────────────
    public function reopen_line_liff_request() {
        $id = (int)$this->request->getPost('id');
        $this->Liff_pending_model->reopen($id);
        echo json_encode(['success' => true, 'message' => 'เปิดคำขอใหม่สำเร็จ']);
    }

    // ──────────────────────────────────────────────────────────────
    // Private: notify user approved/rejected via LINE push
    // ──────────────────────────────────────────────────────────────
    private function _notify_user_approved($line_uid, $display_name) {
        $msg  = "คำขอเชื่อมต่อ LINE ของคุณได้รับการอนุมัติแล้ว!\n";
        $msg .= "ตอนนี้คุณสามารถเข้าใช้งาน DoJob ผ่าน LINE ได้\n";
        $msg .= get_uri('liff');

        try {
            $Line = new \App\Libraries\Liff_line_webhook();
            $Line->send_push_message($line_uid, $msg, 'user', [
                'type' => 'liff_approval_approved'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'LIFF notify approved failed: ' . $e->getMessage());
        }
    }

    private function _notify_user_rejected($line_uid, $display_name, $note = '') {
        $msg  = "❌ คำขอเชื่อมต่อ LINE ของคุณถูกปฏิเสธ\n";
        if ($note) {
            $msg .= "เหตุผล: $note\n";
        }
        $msg .= "ติดต่อผู้ดูแลระบบเพื่อข้อมูลเพิ่มเติม";

        try {
            $Line = new \App\Libraries\Liff_line_webhook();
            $Line->send_push_message($line_uid, $msg, 'user', [
                'type' => 'liff_approval_rejected'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'LIFF notify rejected failed: ' . $e->getMessage());
        }
    }
}
