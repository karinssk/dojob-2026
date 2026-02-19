<?php

namespace App\Controllers;

/**
 * Liff_auth — Public controller for LINE LIFF login flow.
 * No session required to access these routes.
 */
class Liff_auth extends App_Controller {

    protected $db;
    protected $Liff_pending_model;

    public function __construct() {
        parent::__construct();
        $this->db = \Config\Database::connect();
        $this->Liff_pending_model = model('App\Models\Liff_pending_model');
    }

    // ──────────────────────────────────────────────────────────────
    // 1. Entry point — open via LINE app
    //    Loads LIFF SDK, calls liff.init() then POSTs to /liff/verify
    // ──────────────────────────────────────────────────────────────
    public function index() {
        // If already logged in via normal web session, redirect to LIFF app
        if ($this->Users_model->login_user_id()) {
            return redirect()->to(get_uri('liff/app'));
        }

        $liff_id = get_setting('line_liff_id');
        if (!$liff_id) {
            return $this->template->view('liff_auth/error', [
                'message' => 'LIFF is not configured yet. Please contact the administrator.'
            ]);
        }

        return $this->template->view('liff_auth/index', ['liff_id' => $liff_id]);
    }

    // ──────────────────────────────────────────────────────────────
    // 2. Verify LINE id_token server-side
    //    POST: { id_token, line_uid, display_name }
    //    Returns JSON
    // ──────────────────────────────────────────────────────────────
    public function verify() {
        $id_token    = $this->request->getPost('id_token');
        $line_uid    = $this->request->getPost('line_uid');
        $display_name = $this->request->getPost('display_name');

        if (!$id_token || !$line_uid) {
            return $this->_json(['success' => false, 'message' => 'Missing token or UID']);
        }

        // Server-side verify id_token with LINE
        $channel_id = get_setting('line_login_channel_id');
        if ($channel_id) {
            $verify = $this->_verify_id_token($id_token, $channel_id, $line_uid);
            if (!$verify['success']) {
                return $this->_json([
                    'success' => false,
                    'message' => $verify['message'] ?? 'Invalid LINE token. Please try again.',
                    'debug'   => $verify['debug'] ?? null,
                ]);
            }
        }

        // Check if LINE UID already has an approved mapping
        $map_t   = get_user_mappings_table();
        $mapping = $this->db->query(
            "SELECT * FROM $map_t WHERE line_user_id=? AND is_active=1 LIMIT 1",
            [$line_uid]
        )->getRow();

        if ($mapping && $mapping->rise_user_id) {
            // Already linked — log them in
            $this->_do_login($mapping->rise_user_id);
            return $this->_json(['success' => true, 'action' => 'login', 'redirect' => get_uri('liff/app')]);
        }

        // Check if there is a pending request
        $pending = $this->Liff_pending_model->get_by_line_uid($line_uid);
        if ($pending) {
            if ($pending->status === 'pending') {
                return $this->_json(['success' => true, 'action' => 'pending', 'redirect' => get_uri('liff/pending') . '?uid=' . urlencode($line_uid)]);
            }
            if ($pending->status === 'rejected') {
                return $this->_json(['success' => true, 'action' => 'rejected', 'redirect' => get_uri('liff/rejected') . '?uid=' . urlencode($line_uid)]);
            }
        }

        // New user — return list of app users to link
        $users = $this->_get_linkable_users();
        return $this->_json([
            'success'      => true,
            'action'       => 'select_user',
            'display_name' => $display_name,
            'line_uid'     => $line_uid,
            'users'        => $users,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 3. Show select-user page (rendered for non-JS fallback)
    // ──────────────────────────────────────────────────────────────
    public function select_user() {
        $line_uid     = $this->request->getGet('uid');
        $display_name = $this->request->getGet('name');

        $users = $this->_get_linkable_users();

        return $this->template->view('liff_auth/select_user', [
            'line_uid'     => clean_data($line_uid),
            'display_name' => clean_data($display_name),
            'users'        => $users,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 4. Submit link request → save as pending
    //    POST: { line_uid, line_display_name, rise_user_id }
    // ──────────────────────────────────────────────────────────────
    public function request_link() {
        $line_uid      = trim($this->request->getPost('line_uid'));
        $display_name  = trim($this->request->getPost('line_display_name'));
        $rise_user_id  = (int)$this->request->getPost('rise_user_id');

        if (!$line_uid || !$rise_user_id) {
            return $this->_json(['success' => false, 'message' => 'Missing required fields.']);
        }

        // Check if selected app user already linked to a DIFFERENT LINE UID
        $map_t   = get_user_mappings_table();
        $conflict = $this->db->query(
            "SELECT line_user_id FROM $map_t WHERE rise_user_id=? AND is_active=1 AND line_user_id!=? LIMIT 1",
            [$rise_user_id, $line_uid]
        )->getRow();

        if ($conflict) {
            return $this->_json([
                'success' => false,
                'message' => 'This account is already linked to another LINE ID. Please contact the administrator.',
            ]);
        }

        // Get app user snapshot name
        $user_info = $this->Users_model->get_one($rise_user_id);
        $rise_user_name = $user_info ? trim($user_info->first_name . ' ' . $user_info->last_name) : '';

        // Delete previous rejected request for same UID so we can re-submit
        $pending_t = $this->db->prefixTable('liff_pending_registrations');
        $this->db->query("DELETE FROM $pending_t WHERE line_uid=? AND status='rejected'", [$line_uid]);

        // Insert pending record
        $this->Liff_pending_model->ci_save([
            'line_uid'          => $line_uid,
            'line_display_name' => $display_name,
            'rise_user_id'      => $rise_user_id,
            'rise_user_name'    => $rise_user_name,
            'status'            => 'pending',
        ]);

        // Notify admins via LINE
        $this->_notify_admins_new_request($line_uid, $display_name, $rise_user_name);

        return $this->_json([
            'success'  => true,
            'action'   => 'pending',
            'redirect' => get_uri('liff/pending') . '?uid=' . urlencode($line_uid),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 5. Show pending-approval page
    // ──────────────────────────────────────────────────────────────
    public function pending() {
        $line_uid = $this->request->getGet('uid');
        $pending  = $line_uid ? $this->Liff_pending_model->get_by_line_uid($line_uid) : null;

        return $this->template->view('liff_auth/pending', [
            'pending'  => $pending,
            'line_uid' => clean_data($line_uid),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 6. Show rejected page
    // ──────────────────────────────────────────────────────────────
    public function rejected() {
        $line_uid = $this->request->getGet('uid');
        $pending  = $line_uid ? $this->Liff_pending_model->get_by_line_uid($line_uid) : null;

        return $this->template->view('liff_auth/rejected', [
            'pending'  => $pending,
            'line_uid' => clean_data($line_uid),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 7. Poll approval status (AJAX from pending page)
    // ──────────────────────────────────────────────────────────────
    public function check_status() {
        $line_uid = $this->request->getGet('uid');
        if (!$line_uid) {
            return $this->_json(['status' => 'unknown']);
        }

        $pending = $this->Liff_pending_model->get_by_line_uid($line_uid);
        if (!$pending) {
            return $this->_json(['status' => 'not_found']);
        }

        if ($pending->status === 'approved') {
            $this->_do_login($pending->rise_user_id);
            return $this->_json([
                'status'   => 'approved',
                'redirect' => get_uri('liff/app'),
            ]);
        }

        return $this->_json(['status' => $pending->status]);
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    private function _do_login($rise_user_id) {
        $session = \Config\Services::session();
        $session->set('user_id', $rise_user_id);
        $session->set('liff_session', true);
    }

    private function _verify_id_token($id_token, $channel_id, $user_id) {
        $debug = [
            'endpoint'      => 'https://api.line.me/oauth2/v2.1/verify',
            'channel_id'    => $channel_id,
            'user_id'       => $user_id,
            'token_present' => (bool)$id_token,
            'token_length'  => $id_token ? strlen($id_token) : 0,
            'token_preview' => $id_token ? (substr($id_token, 0, 8) . '...' . substr($id_token, -6)) : '',
            'timestamp'     => date('c'),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.line.me/oauth2/v2.1/verify',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'id_token'  => $id_token,
                'client_id' => $channel_id,
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $start = microtime(true);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $debug['curl_errno'] = curl_errno($ch);
        $debug['curl_error'] = curl_error($ch);
        $debug['http_code']  = $http_code;
        $debug['elapsed_ms'] = (int)round((microtime(true) - $start) * 1000);
        curl_close($ch);

        $debug['response_raw'] = $response;
        $data = json_decode($response, true);
        $debug['response_json'] = $data;

        if ($http_code !== 200) {
            log_message('error', 'LIFF id_token verify failed: ' . $response);
            return [
                'success' => false,
                'message' => $data['error_description'] ?? $data['message'] ?? 'Invalid LINE token',
                'debug'   => $debug,
            ];
        }

        if (!isset($data['sub'])) {
            return [
                'success' => false,
                'message' => 'Invalid LINE token (missing sub)',
                'debug'   => $debug,
            ];
        }

        if ($data['sub'] !== $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid LINE token (sub mismatch)',
                'debug'   => $debug,
            ];
        }

        return [
            'success' => true,
            'debug'   => $debug,
        ];
    }

    private function _get_linkable_users() {
        $users_t = $this->db->prefixTable('users');
        $result  = $this->db->query(
            "SELECT id, first_name, last_name, image
             FROM $users_t
             WHERE status='active' AND deleted=0 AND user_type='staff'
             ORDER BY first_name, last_name"
        )->getResult();

        $out = [];
        foreach ($result as $u) {
            $out[] = [
                'id'    => $u->id,
                'name'  => trim($u->first_name . ' ' . $u->last_name),
                'image' => $u->image,
            ];
        }
        return $out;
    }

    private function _notify_admins_new_request($line_uid, $display_name, $rise_user_name) {
        $admin_uids_raw = get_setting('line_admin_notify_uids');
        if (!$admin_uids_raw) {
            // Fallback: use existing line_user_ids setting
            $admin_uids_raw = get_setting('line_user_ids');
        }

        $admin_uids = [];
        if ($admin_uids_raw) {
            $decoded = json_decode($admin_uids_raw, true);
            $admin_uids = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode("\n", $admin_uids_raw)));
        }

        if (empty($admin_uids)) {
            return;
        }

        $approve_url = get_uri('settings/approve_line_liff_users');
        $message     = "🔔 New LIFF Login Request\n"
                     . "LINE: $display_name\n"
                     . "Wants to link to: $rise_user_name\n"
                     . "➡️ Review: $approve_url";

        $Line = new \App\Libraries\Line_webhook();
        foreach ($admin_uids as $uid) {
            $uid = trim($uid);
            if ($uid) {
                $Line->send_push_message($uid, $message, 'user');
            }
        }
    }

    private function _json($data) {
        return $this->response
            ->setContentType('application/json')
            ->setBody(json_encode($data));
    }
}
