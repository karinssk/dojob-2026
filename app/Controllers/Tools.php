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
        $jobs_dir = $target_dir . "jobs/";
        if (!is_dir($target_dir)) {
            @mkdir($target_dir, 0775, true);
        }
        if (!is_dir($jobs_dir)) {
            @mkdir($jobs_dir, 0775, true);
        }

        $safe_name = $this->_make_safe_filename($this->request->getPost("title") ?: "video");
        $base_name = $safe_name . "_" . date("Ymd_His");
        $job_id = $base_name . "_" . bin2hex(random_bytes(4));
        $output_template = $target_dir . $base_name . ".%(ext)s";
        $output_path = $target_dir . $base_name . ".mp4";
        $log_path = $jobs_dir . $job_id . ".log";
        $exit_path = $jobs_dir . $job_id . ".exit";

        set_time_limit(0);

        $progress_template = "download:%(progress._percent_str)s|%(progress.speed)s|%(progress.eta)s";
        $inner_cmd = "yt-dlp --no-warnings --no-playlist --newline --progress-template " . escapeshellarg($progress_template)
            . " -o " . escapeshellarg($output_template)
            . " --merge-output-format mp4 "
            . escapeshellarg($url)
            . " > " . escapeshellarg($log_path) . " 2>&1; echo $? > " . escapeshellarg($exit_path);

        $cmd = "bash -c " . escapeshellarg($inner_cmd . " & echo $!");
        $pid = "";
        exec($cmd, $out);
        if ($out && isset($out[0])) {
            $pid = trim($out[0]);
        }

        echo json_encode(array(
            "success" => true,
            "job_id" => $job_id,
            "file_name" => $base_name . ".mp4",
            "file_url" => base_url("files/general/tools/" . $base_name . ".mp4"),
            "pid" => $pid
        ));
    }

    function progress() {
        $job_id = trim($this->request->getGet("job_id"));
        if (!$job_id) {
            echo json_encode(array("success" => false, "message" => "Missing job_id"));
            return;
        }

        $jobs_dir = FCPATH . "files/general/tools/jobs/";
        $log_path = $jobs_dir . $job_id . ".log";
        $exit_path = $jobs_dir . $job_id . ".exit";

        $last_line = "";
        if (is_file($log_path)) {
            $lines = @file($log_path, FILE_IGNORE_NEW_LINES);
            if ($lines && count($lines)) {
                $last_line = $lines[count($lines) - 1];
            }
        }

        $is_done = false;
        $exit_code = null;
        if (is_file($exit_path)) {
            $exit_code = trim(@file_get_contents($exit_path));
            $is_done = true;
        }

        echo json_encode(array(
            "success" => true,
            "done" => $is_done,
            "exit_code" => $exit_code,
            "last_line" => $last_line
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
