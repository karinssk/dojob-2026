<?php

namespace App\Controllers;

class Tools extends App_Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        $view_data['topbar'] = "includes/public/topbar";
        $view_data['left_menu'] = false;

        return $this->template->rander("tools/index", $view_data);
    }

    function preview() {
        $url = trim($this->request->getPost("url"));

        if (!$this->_is_valid_url($url)) {
            echo json_encode(array("success" => false, "message" => "Invalid URL"));
            return;
        }

        if (!$this->_command_exists("yt-dlp")) {
            echo json_encode(array("success" => false, "message" => "yt-dlp not found on server"));
            return;
        }

        $cmd = "yt-dlp --no-warnings --no-playlist --skip-download -J " . escapeshellarg($url);
        $result = $this->_run_command($cmd);

        if ($result["exit_code"] !== 0 || !$result["stdout"]) {
            echo json_encode(array("success" => false, "message" => "Preview failed", "error" => $result["stderr"]));
            return;
        }

        $info = json_decode($result["stdout"], true);
        if (!$info || !is_array($info)) {
            echo json_encode(array("success" => false, "message" => "Preview parse failed"));
            return;
        }

        $title = get_array_value($info, "title");
        $thumbnail = get_array_value($info, "thumbnail");
        $extractor = get_array_value($info, "extractor");
        $duration = get_array_value($info, "duration");

        echo json_encode(array(
            "success" => true,
            "title" => $title,
            "thumbnail" => $thumbnail,
            "extractor" => $extractor,
            "duration" => $duration
        ));
    }

    function download() {
        $url = trim($this->request->getPost("url"));

        if (!$this->_is_valid_url($url)) {
            echo json_encode(array("success" => false, "message" => "Invalid URL"));
            return;
        }

        if (!$this->_command_exists("yt-dlp") || !$this->_command_exists("ffmpeg")) {
            echo json_encode(array("success" => false, "message" => "yt-dlp or ffmpeg not found on server"));
            return;
        }

        $target_dir = FCPATH . "files/general/tools/";
        if (!is_dir($target_dir)) {
            @mkdir($target_dir, 0775, true);
        }

        $safe_name = $this->_make_safe_filename($this->request->getPost("title") ?: "video");
        $base_name = $safe_name . "_" . date("Ymd_His");
        $output_template = $target_dir . $base_name . ".%(ext)s";
        $output_path = $target_dir . $base_name . ".mp4";

        set_time_limit(0);

        $cmd = "yt-dlp --no-warnings --no-playlist -o " . escapeshellarg($output_template) . " --merge-output-format mp4 " . escapeshellarg($url);
        $result = $this->_run_command($cmd);

        if ($result["exit_code"] !== 0 || !file_exists($output_path)) {
            echo json_encode(array("success" => false, "message" => "Download failed", "error" => $result["stderr"]));
            return;
        }

        $file_url = base_url("files/general/tools/" . $base_name . ".mp4");

        echo json_encode(array(
            "success" => true,
            "file_name" => $base_name . ".mp4",
            "file_url" => $file_url
        ));
    }

    private function _run_command($cmd) {
        $descriptor_spec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $process = proc_open($cmd, $descriptor_spec, $pipes);
        if (!is_resource($process)) {
            return array("exit_code" => 1, "stdout" => "", "stderr" => "Unable to run command");
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);

        return array("exit_code" => $exit_code, "stdout" => $stdout, "stderr" => $stderr);
    }

    private function _command_exists($command) {
        $result = $this->_run_command("command -v " . escapeshellarg($command));
        return $result["exit_code"] === 0 && trim($result["stdout"]) !== "";
    }

    private function _is_valid_url($url) {
        if (!$url) {
            return false;
        }

        $parts = parse_url($url);
        if (!$parts || !isset($parts["scheme"]) || !isset($parts["host"])) {
            return false;
        }

        return in_array($parts["scheme"], array("http", "https"), true);
    }

    private function _make_safe_filename($value) {
        $value = strip_tags($value);
        $value = preg_replace('/[\\s\\-]+/', '-', $value);
        $value = preg_replace('/[^A-Za-z0-9\\-]/', '', $value);
        $value = trim($value, "-");
        if (!$value) {
            $value = "video";
        }
        return strtolower($value);
    }
}

/* End of file Tools.php */
