<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Liff_notify_webhook extends Controller {

    public function webhook() {
        helper('general');
        $this->response->setContentType('text/plain');
        $debug = [
            'timestamp' => date('c'),
            'ip' => $this->request->getIPAddress(),
            'user_agent' => (string)$this->request->getUserAgent(),
            'headers' => $this->get_headers_array(),
        ];

        try {
            $input = file_get_contents('php://input');
            $debug['raw_length'] = $input ? strlen($input) : 0;
            $debug['raw_preview'] = $input ? substr($input, 0, 2000) : '';

            if (empty($input)) {
                $debug['status'] = 200;
                $debug['message'] = 'Empty input';
                $this->save_debug($debug);
                return $this->response->setBody('OK');
            }

            $channel_secret = get_setting('liff_line_channel_secret') ?: get_setting('line_channel_secret');
            if ($channel_secret) {
                $signature = $this->request->getHeaderLine('X-Line-Signature');
                if (!$this->verify_line_signature($input, $signature, $channel_secret)) {
                    $debug['status'] = 401;
                    $debug['message'] = 'Invalid signature';
                    $this->save_debug($debug);
                    log_message('error', 'LIFF Webhook: Invalid signature');
                    return $this->response->setStatusCode(401)->setBody('Invalid signature');
                }
            }

            $events = json_decode($input, true);
            if (!$events || !isset($events['events'])) {
                $debug['status'] = 200;
                $debug['message'] = 'No events array';
                $this->save_debug($debug);
                return $this->response->setBody('OK');
            }

            foreach ($events['events'] as $event) {
                $this->capture_line_room($event);
            }

            $debug['status'] = 200;
            $debug['message'] = 'OK';
            $debug['events_count'] = count($events['events']);
            $this->save_debug($debug);
            return $this->response->setBody('OK');
        } catch (\Throwable $e) {
            $debug['status'] = 500;
            $debug['message'] = $e->getMessage();
            $debug['trace'] = substr($e->getTraceAsString(), 0, 2000);
            $this->save_debug($debug);
            log_message('error', 'LIFF Webhook exception: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('Internal Server Error');
        }
    }

    private function verify_line_signature($body, $signature, $channel_secret) {
        if (!$signature || !$channel_secret) {
            return false;
        }
        $hash = hash_hmac('sha256', $body, $channel_secret, true);
        $expected = base64_encode($hash);
        return hash_equals($expected, $signature);
    }

    private function capture_line_room($event) {
        $source = $event['source'] ?? null;
        if (!$source || !is_array($source)) {
            return;
        }

        $source_type = $source['type'] ?? '';
        $room_id = '';
        $api_type = '';

        if ($source_type === 'group') {
            $room_id = $source['groupId'] ?? '';
            $api_type = 'group';
        } elseif ($source_type === 'room') {
            $room_id = $source['roomId'] ?? '';
            $api_type = 'room';
        }

        if (!$room_id) {
            return;
        }

        $room_name = $this->fetch_line_room_name($api_type, $room_id);
        if (!$room_name) {
            $room_name = $room_id;
        }

        $rooms = $this->get_line_rooms();
        $updated = false;

        foreach ($rooms as $index => $room) {
            if (($room['id'] ?? '') === $room_id) {
                $rooms[$index] = [
                    'id' => $room_id,
                    'name' => $room_name,
                    'type' => $api_type,
                    'updated_at' => get_current_utc_time()
                ];
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $rooms[] = [
                'id' => $room_id,
                'name' => $room_name,
                'type' => $api_type,
                'updated_at' => get_current_utc_time()
            ];
        }

        $settings_model = model('App\Models\Settings_model');
        $settings_model->save_setting('liff_line_rooms', json_encode($rooms));
    }

    private function get_line_rooms() {
        $rooms_json = get_setting('liff_line_rooms');
        $rooms = $rooms_json ? json_decode($rooms_json, true) : [];
        return is_array($rooms) ? $rooms : [];
    }

    private function fetch_line_room_name($type, $room_id) {
        $token = get_setting('liff_line_channel_access_token') ?: get_setting('line_channel_access_token');
        if (!$token || !$type || !$room_id) {
            return '';
        }

        $url = "https://api.line.me/v2/bot/{$type}/{$room_id}/summary";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            return '';
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return '';
        }

        if ($type === 'group') {
            return $data['groupName'] ?? '';
        }

        if ($type === 'room') {
            return $data['roomName'] ?? '';
        }

        return '';
    }

    private function get_headers_array() {
        $out = [];
        foreach ($this->request->getHeaders() as $name => $header) {
            $out[$name] = $header->getValueLine();
        }
        return $out;
    }

    private function save_debug($debug) {
        try {
            $settings_model = model('App\Models\Settings_model');
            $settings_model->save_setting('liff_webhook_last_debug', json_encode($debug));
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
