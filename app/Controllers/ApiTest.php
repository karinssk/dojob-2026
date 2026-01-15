<?php

namespace App\Controllers;

class ApiTest extends App_Controller {

    function __construct() {
        parent::__construct();
        
        // Set JSON headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Simple test endpoint to verify API routing works
     */
    function index() {
        echo json_encode([
            'success' => true,
            'message' => 'API routing is working!',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'request_uri' => $_SERVER['REQUEST_URI']
            ]
        ]);
    }

    /**
     * Test endpoint for current user - no authentication required
     */
    function current_user() {
        echo json_encode([
            'success' => true,
            'message' => 'API Test endpoint reached successfully',
            'note' => 'This is a test endpoint without authentication',
            'timestamp' => date('Y-m-d H:i:s'),
            'session_status' => session_status(),
            'cookies' => !empty($_COOKIE) ? array_keys($_COOKIE) : [],
            'session_id' => session_id()
        ]);
    }

    /**
     * Debug endpoint to show all available information
     */
    function debug() {
        echo json_encode([
            'success' => true,
            'debug_info' => [
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'request_uri' => $_SERVER['REQUEST_URI'],
                'query_string' => $_SERVER['QUERY_STRING'] ?? '',
                'http_host' => $_SERVER['HTTP_HOST'],
                'script_name' => $_SERVER['SCRIPT_NAME'],
                'session_status' => session_status(),
                'session_id' => session_id(),
                'cookies' => $_COOKIE,
                'headers' => getallheaders(),
                'post_data' => $_POST,
                'get_data' => $_GET
            ]
        ]);
    }
}
