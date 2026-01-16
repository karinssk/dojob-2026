<?php

namespace App\Controllers;

class Tasks_tracking extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
    }

    function index() {
        $default_keywords = "work,งาน";
        $stored_keywords = get_setting("line_task_tracking_keywords");

        $view_data = array(
            "keywords" => $stored_keywords ? $stored_keywords : $default_keywords,
            "default_keywords" => $default_keywords
        );

        return $this->template->rander("tasks_tracking/index", $view_data);
    }

    function save() {
        $keywords = $this->request->getPost("line_task_tracking_keywords");
        $keywords = $keywords ? trim($keywords) : "";

        if (!$keywords) {
            $keywords = "work,งาน";
        }

        $this->Settings_model->save_setting("line_task_tracking_keywords", $keywords);

        echo json_encode(array("success" => true, "message" => app_lang("settings_updated")));
    }
}
