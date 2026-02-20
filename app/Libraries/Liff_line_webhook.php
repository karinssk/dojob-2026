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
 *   - token : setting  `liff_fallback_token`  (falls back to `line_channel_access_token`)
 *   - target: setting  `liff_fallback_room_id` (falls back to `line_default_room_id`)
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

        $this->fallback_token = get_setting('liff_fallback_token')
                             ?: get_setting('line_channel_access_token');

        $this->fallback_room  = get_setting('liff_fallback_room_id')
                             ?: get_setting('line_default_room_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Public: send plain-text push
    // ──────────────────────────────────────────────────────────────
    public function send_push_message($to, $message, $type = 'user') {
        $this->last_error    = '';
        $this->used_fallback = false;

        if (!$this->primary_token || !$to) {
            $this->last_error = 'Missing LIFF channel token or target';
            return ['success' => false, 'error' => $this->last_error];
        }

        $message  = str_replace('**', '', $message);
        $messages = [['type' => 'text', 'text' => $message]];

        // Try primary
        $result = $this->_push($this->primary_token, $to, $messages);
        if ($result['success']) { return $result; }

        // Primary failed — try fallback
        return $this->_send_fallback_text($message, $result['error']);
    }

    // ──────────────────────────────────────────────────────────────
    // Public: send Flex Message
    // ──────────────────────────────────────────────────────────────
    public function send_flex_message($to, $flex, $alt_text = 'แจ้งเตือนงาน', $type = 'user') {
        $this->last_error    = '';
        $this->used_fallback = false;

        if (!$this->primary_token || !$to) {
            $this->last_error = 'Missing LIFF channel token or target';
            return ['success' => false, 'error' => $this->last_error];
        }

        $messages = [[
            'type'     => 'flex',
            'altText'  => $alt_text,
            'contents' => $flex,
        ]];

        // Try primary
        $result = $this->_push($this->primary_token, $to, $messages, true);
        if ($result['success']) { return $result; }

        // Primary failed — degrade to plain text sent to fallback room
        return $this->_send_fallback_text($alt_text, $result['error']);
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
}
