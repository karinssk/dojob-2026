<?php

namespace App\Libraries;

/**
 * Liff_line_webhook — LINE Messaging API push helper (LIFF bot)
 *
 * Fallback behaviour
 * ──────────────────
 * If the primary token fails for ANY reason (quota exceeded = HTTP 429,
 * invalid token = 401, network error, etc.) the library automatically
 * retries using the fallback credentials:
 *   - token : setting  `line_channel_access_token_fall_back`  (falls back to `line_channel_access_token`)
 *   - target: setting  `line_default_room_id_fall_back` (falls back to `line_default_room_id`)
 *
 * Flex messages are degraded to a plain-text summary when sent via the
 * fallback channel (many bots share a general room that may not support
 * the same rich content).
 */
class Liff_line_webhook {

    private $primary_token;
    private $fallback_token;
    private $fallback_room;

    public $last_error   = '';
    public $used_fallback = false;   // set to true after a fallback send

    function __construct() {
        $this->primary_token  = get_setting('liff_line_channel_access_token')
                             ?: get_setting('line_channel_access_token');

        $this->fallback_token = get_setting('line_channel_access_token_fall_back')
                             ?: get_setting('line_channel_access_token');

        $this->fallback_room  = get_setting('line_default_room_id_fall_back')
                             ?: get_setting('line_default_room_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Public: send plain-text push
    // ──────────────────────────────────────────────────────────────
    public function send_push_message($to, $message, $type = 'user', $meta = []) {
        $this->last_error    = '';
        $this->used_fallback = false;

        $token = $this->primary_token;
        if (!empty($meta['force_token'])) {
            $token = $meta['force_token'];
        }

        if (!$token || !$to) {
            $this->last_error = 'Missing LIFF channel token or target';
            $res = ['success' => false, 'error' => $this->last_error];
            $this->_log_notification($message, $meta, false, $res, $to, $type);
            return $res;
        }

        $message  = str_replace('**', '', $message);
        $messages = [['type' => 'text', 'text' => $message]];

        // Try primary
        $result = $this->_push($token, $to, $messages);
        if ($result['success']) {
            $this->_log_notification($message, $meta, true, $result, $to, $type);
            return $result;
        }

        // Primary failed — try fallback
        $fallback = $this->_send_fallback_text($message, $result['error']);
        $this->_log_notification($message, $meta, (bool)($fallback['success'] ?? false), $fallback, $to, $type);
        return $fallback;
    }

    // ──────────────────────────────────────────────────────────────
    // Public: send Flex Message
    // ──────────────────────────────────────────────────────────────
    public function send_flex_message($to, $flex, $alt_text = 'แจ้งเตือนงาน', $type = 'user', $meta = []) {
        $this->last_error    = '';
        $this->used_fallback = false;

        $token = $this->primary_token;
        if (!empty($meta['force_token'])) {
            $token = $meta['force_token'];
        }

        if (!$token || !$to) {
            $this->last_error = 'Missing LIFF channel token or target';
            $res = ['success' => false, 'error' => $this->last_error];
            $this->_log_notification($alt_text, $meta, false, $res, $to, $type);
            return $res;
        }

        $messages = [[
            'type'     => 'flex',
            'altText'  => $alt_text,
            'contents' => $flex,
        ]];

        // Try primary
        $result = $this->_push($token, $to, $messages, true);
        if ($result['success']) {
            $this->_log_notification($alt_text, $meta, true, $result, $to, $type);
            return $result;
        }

        // Primary failed — degrade to plain text sent to fallback room
        $fallback = $this->_send_fallback_text($alt_text, $result['error']);
        $this->_log_notification($alt_text, $meta, (bool)($fallback['success'] ?? false), $fallback, $to, $type);
        return $fallback;
    }

    // ──────────────────────────────────────────────────────────────
    // Internal: core cURL push
    // ──────────────────────────────────────────────────────────────
    private function _push($token, $to, array $messages, $is_flex = false) {
        $payload = json_encode(
            ['to' => $to, 'messages' => $messages],
            JSON_UNESCAPED_UNICODE
        );

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.line.me/v2/bot/message/push',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'User-Agent: DoJob-LIFF/1.0',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err  = curl_error($ch);
        curl_close($ch);

        $label = $is_flex ? 'Flex' : 'Text';
        log_message('info', "LIFF {$label} Push → {$to}: HTTP {$http_code} | {$response} | cURL: {$curl_err}");

        if ($curl_err) {
            return ['success' => false, 'error' => "cURL: {$curl_err}", 'http_code' => 0];
        }
        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true,  'error' => '', 'http_code' => $http_code];
        }

        $this->last_error = "HTTP {$http_code}: {$response}";
        return ['success' => false, 'error' => $this->last_error, 'http_code' => $http_code];
    }

    // ──────────────────────────────────────────────────────────────
    // Internal: fallback — plain text to fallback room
    // ──────────────────────────────────────────────────────────────
    private function _send_fallback_text($text, $primary_error) {
        if (!$this->fallback_token || !$this->fallback_room) {
            log_message('warning', "LIFF Fallback: no fallback credentials configured. Primary error: {$primary_error}");
            return ['success' => false, 'error' => $primary_error . ' (fallback not configured)'];
        }

        log_message('warning', "LIFF Primary failed ({$primary_error}), using fallback bot → room {$this->fallback_room}");

        $messages = [['type' => 'text', 'text' => '[Fallback] ' . $text]];
        $result   = $this->_push($this->fallback_token, $this->fallback_room, $messages);

        if ($result['success']) {
            $this->used_fallback = true;
            return ['success' => true, 'error' => '', 'fallback' => true];
        }

        return ['success' => false, 'error' => "Primary: {$primary_error} | Fallback: {$result['error']}"];
    }

    // ──────────────────────────────────────────────────────────────
    // Internal: log to line_notification_logs (same UI as Line Notify)
    // ──────────────────────────────────────────────────────────────
    private function _log_notification($message, $meta, $success, $result, $to = '', $type = '') {
        try {
            $Line_logs_model = model('App\Models\Line_notification_logs_model');
            $notification_type = '';
            if (is_array($meta)) {
                $notification_type = $meta['notification_type']
                    ?? $meta['reminder_type']
                    ?? $meta['type']
                    ?? '';
            }
            if (!$notification_type) {
                $notification_type = 'liff';
            }

            $response = '';
            if (is_array($result)) {
                if (!empty($result['success'])) {
                    $response = !empty($result['fallback']) ? 'OK (fallback)' : 'OK';
                } else {
                    $response = $result['error'] ?? 'failed';
                }
                if (isset($result['http_code']) && $result['http_code']) {
                    $response .= " | HTTP {$result['http_code']}";
                }
            }

            if (!empty($meta['force_token_label'])) {
                $response = '[token=' . $meta['force_token_label'] . '] ' . $response;
            }

            if ($to) {
                $label = $type ?: 'user';
                $response = "{$label} {$to}: " . $response;
            }

            $Line_logs_model->log_notification([
                'task_id' => is_array($meta) ? ($meta['task_id'] ?? null) : null,
                'event_id' => is_array($meta) ? ($meta['event_id'] ?? null) : null,
                'notification_type' => $notification_type,
                'message' => $message,
                'status' => $success ? 'sent' : 'failed',
                'response' => $response,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'LIFF log_notification failed: ' . $e->getMessage());
        }
    }
}
