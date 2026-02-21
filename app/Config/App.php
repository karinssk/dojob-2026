<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public function __construct()
    {
        $this->set_supported_languages();
        $this->baseURL = $this->resolve_base_url($this->baseURL);
    }

    /**
     * Base Site URL
     *
     * URL หลักของระบบ (บังคับใช้ HTTPS)
     *
     * @var string
     */
    public string $baseURL = 'http://localhost:8888/dojob-2026/';

    /**
     * Allowed Hostnames
     *
     * @var string[]
     */
    public array $allowedHostnames = [];

    /**
     * Index File
     *
     * @var string
     */
    public $indexPage = 'index.php';

    /**
     * URI Protocol
     *
     * @var string
     */
    public $uriProtocol = 'REQUEST_URI';

    public $permittedURIChars = 'a-z 0-9~%.:_\-';

    public $defaultLocale = 'english';
    public $negotiateLocale = false;
    public $supportedLocales = [];

    public $appTimezone = 'UTC';
    public $charset = 'UTF-8';

    /**
     * Force HTTPS
     *
     * ถ้า request ไม่ใช่ HTTPS → redirect ไป HTTPS
     */
    public $forceGlobalSecureRequests = false;

    /**
     * Content Security Policy
     *
     * @var bool
     */
    public $CSPEnabled = false;

    /* User configs */
    public $encryption_key = "3183e15d03086c5";
    public $csrf_protection = true;
    public $temp_file_path = 'files/temp/';
    public $profile_image_path = 'files/profile_images/';
    public $timeline_file_path = 'files/timeline_files/';
    public $project_file_path = 'files/project_files/';
    public $system_file_path = 'files/system/';
    public $check_notification_after_every = "60";

    /**
     * Reverse Proxy IPs
     *
     * Cloudflare IP ranges เพื่อให้ได้ client IP ที่แท้จริง
     *
     * @var string[]
     */
public $proxyIPs = [
    // Cloudflare IP ranges : ใช้ header CF-Connecting-IP
    '103.21.244.0/22'   => 'CF-Connecting-IP',
    '103.22.200.0/22'   => 'CF-Connecting-IP',
    '103.31.4.0/22'     => 'CF-Connecting-IP',
    '104.16.0.0/13'     => 'CF-Connecting-IP',
    '104.24.0.0/14'     => 'CF-Connecting-IP',
    '108.162.192.0/18'  => 'CF-Connecting-IP',
    '131.0.72.0/22'     => 'CF-Connecting-IP',
    '141.101.64.0/18'   => 'CF-Connecting-IP',
    '162.158.0.0/15'    => 'CF-Connecting-IP',
    '172.64.0.0/13'     => 'CF-Connecting-IP',
    '173.245.48.0/20'   => 'CF-Connecting-IP',
    '188.114.96.0/20'   => 'CF-Connecting-IP',
    '190.93.240.0/20'   => 'CF-Connecting-IP',
    '197.234.240.0/22'  => 'CF-Connecting-IP',
    '198.41.128.0/17'   => 'CF-Connecting-IP',
];


    /**
     * โหลดภาษาอัตโนมัติ
     */
    private function set_supported_languages()
    {
        if (count($this->supportedLocales)) return;

        $language_dropdown = [];
        $dir = "./app/Language/";
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file && $file != "." && $file != ".." && $file != "index.html" && $file != ".gitkeep") {
                        $language_dropdown[] = $file;
                    }
                }
                closedir($dh);
            }
        }

        $this->supportedLocales = $language_dropdown;
    }

    /**
     * Auto-detect baseURL when the configured value is empty or localhost.
     * This prevents production from generating localhost URLs when .env is misconfigured.
     */
    private function resolve_base_url($current)
    {
        $env_base = env('app.baseURL');
        if ($env_base) {
            $current = $env_base;
        }

        $current = trim((string) $current);
        $is_local = (stripos($current, 'localhost') !== false) || (stripos($current, '127.0.0.1') !== false);
        if ($current && !$is_local) {
            return rtrim($current, '/') . '/';
        }

        if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
            return $current ? rtrim($current, '/') . '/' : $current;
        }

        $scheme = 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $proto = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO']);
            $scheme = trim($proto[0]) ?: $scheme;
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $path = $script ? rtrim(str_replace('\\', '/', dirname($script)), '/') : '';
        if ($path === '/') {
            $path = '';
        }

        return $scheme . '://' . $host . $path . '/';
    }
}
