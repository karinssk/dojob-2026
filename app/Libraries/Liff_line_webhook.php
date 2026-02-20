<?php

namespace App\Libraries;

class Liff_line_webhook {

    private $channel_access_token;
    public $last_error = '';

    function __construct() {
        $this->channel_access_token = get_setting('liff_line_channel_access_token') ?: get_setting('line_channel_access_token');
    }

    /**
     * Send push message using LINE Messaging API (LIFF credentials)
     * @param string $to User/Group/Room ID
     * @param string $message Message content
     * @param string $type 'user' | 'group' | 'room'
     * @return array
     */
    public function send_push_message($to, $message, $type = 'user') {
        $this->last_error = '';
        if (!$this->channel_access_token || !$to) {
            $this->last_error = 'Missing LIFF channel token or target';
            return ['success' => false, 'error' => $this->last_error];
        }

        $url = 'https://api.line.me/v2/bot/message/push';

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
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->channel_access_token,
                'User-Agent: DoJob-LIFF/1.0'
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

        log_message('info', "LIFF Push to {$type} {$to}: HTTP {$http_code} | Response: {$response} | cURL: {$error}");

        if ($error) {
            return ['success' => false, 'error' => "cURL Error: {$error}"];
        }

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'error' => ''];
        }

        $this->last_error = "HTTP {$http_code}: {$response}";
        return ['success' => false, 'error' => $this->last_error];
    }

    /**
     * Send a Flex Message bubble via LINE Messaging API
     * @param string $to        User/Group/Room ID
     * @param array  $flex      Flex Message container object (already built)
     * @param string $alt_text  Alt text shown in notification tray
     * @param string $type      'user' | 'group' | 'room'
     * @return array
     */
    public function send_flex_message($to, $flex, $alt_text = 'แจ้งเตือนงาน', $type = 'user') {
        $this->last_error = '';
        if (!$this->channel_access_token || !$to) {
            $this->last_error = 'Missing LIFF channel token or target';
            return ['success' => false, 'error' => $this->last_error];
        }

        $url = 'https://api.line.me/v2/bot/message/push';
        $payload = [
            'to' => $to,
            'messages' => [
                [
                    'type'     => 'flex',
                    'altText'  => $alt_text,
                    'contents' => $flex,
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->channel_access_token,
                'User-Agent: DoJob-LIFF/1.0'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        log_message('info', "LIFF Flex Push to {$type} {$to}: HTTP {$http_code} | Response: {$response} | cURL: {$error}");

        if ($error) {
            return ['success' => false, 'error' => "cURL Error: {$error}"];
        }

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'error' => ''];
        }

        $this->last_error = "HTTP {$http_code}: {$response}";
        return ['success' => false, 'error' => $this->last_error];
    }
}
