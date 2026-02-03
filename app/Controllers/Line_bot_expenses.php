<?php

namespace App\Controllers;

class Line_bot_expenses extends Security_Controller {

    protected $db;
    private $Line_expenses_model;

    function __construct() {
        parent::__construct(false); // false = don't redirect for webhook
        $this->db = \Config\Database::connect();
        $this->Line_expenses_model = model('App\Models\Line_expenses_model');
    }

    private function _require_auth() {
        $login_user_id = $this->Users_model->login_user_id();
        if (!$login_user_id) {
            $uri_string = uri_string();
            app_redirect('signin?redirect=' . get_uri($uri_string));
        }
        $this->access_only_admin_or_settings_admin();
    }

    // ========== MAIN PAGE ==========

    function index() {
        $this->_require_auth();

        $view_data = array(
            'line_expenses_enabled' => get_setting('line_expenses_enabled'),
            'line_expenses_channel_access_token' => get_setting('line_expenses_channel_access_token'),
            'line_expenses_channel_secret' => get_setting('line_expenses_channel_secret'),
            'line_expenses_report_target_id' => get_setting('line_expenses_report_target_id'),
            'line_expenses_report_target_type' => get_setting('line_expenses_report_target_type') ?: 'user',
            'line_expenses_webhook_url' => get_uri("line/v2/expenses/webhook"),
            'line_expenses_daily_report_enabled' => get_setting('line_expenses_daily_report_enabled'),
            'line_expenses_daily_report_time' => get_setting('line_expenses_daily_report_time') ?: '20:00',
            'line_expenses_monthly_report_enabled' => get_setting('line_expenses_monthly_report_enabled'),
            'line_expenses_monthly_report_days' => get_setting('line_expenses_monthly_report_days') ?: '1,monday,saturday',
            'line_expenses_monthly_report_time' => get_setting('line_expenses_monthly_report_time') ?: '20:01',
            'line_expenses_default_category_id' => get_setting('line_expenses_default_category_id') ?: '24',
            'title_keywords' => $this->Line_expenses_model->get_title_keywords()->getResult(),
            'project_keywords' => $this->Line_expenses_model->get_project_keywords()->getResult(),
            'rooms_dropdown' => $this->_get_rooms_dropdown()
        );

        return $this->template->rander("settings/line_bot_expenses/index", $view_data);
    }

    // ========== SAVE SETTINGS ==========

    function save_settings() {
        $this->_require_auth();

        $settings = array(
            "line_expenses_enabled",
            "line_expenses_channel_access_token",
            "line_expenses_channel_secret",
            "line_expenses_report_target_id",
            "line_expenses_report_target_type",
            "line_expenses_default_category_id"
        );

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            $this->Settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    function save_daily_report_settings() {
        $this->_require_auth();

        $settings = array(
            "line_expenses_daily_report_enabled",
            "line_expenses_daily_report_time"
        );

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            $this->Settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    function save_monthly_report_settings() {
        $this->_require_auth();

        $this->Settings_model->save_setting('line_expenses_monthly_report_enabled', $this->request->getPost('line_expenses_monthly_report_enabled'));
        $this->Settings_model->save_setting('line_expenses_monthly_report_time', $this->request->getPost('line_expenses_monthly_report_time'));

        // Build days string from checkboxes
        $days = array();
        if ($this->request->getPost('monthly_day_1')) $days[] = '1';
        if ($this->request->getPost('monthly_day_monday')) $days[] = 'monday';
        if ($this->request->getPost('monthly_day_saturday')) $days[] = 'saturday';
        $this->Settings_model->save_setting('line_expenses_monthly_report_days', implode(',', $days));

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    // ========== TITLE KEYWORDS CRUD ==========

    function title_keywords_list_data() {
        $this->_require_auth();

        $keywords = $this->Line_expenses_model->get_title_keywords()->getResult();
        $data = array();

        foreach ($keywords as $kw) {
            $data[] = array(
                $kw->keyword,
                $kw->title,
                $kw->sort,
                '<div class="text-center">
                    <button class="btn btn-default btn-sm edit-title-keyword" data-id="' . $kw->id . '"><i data-feather="edit" class="icon-16"></i></button>
                    <button class="btn btn-default btn-sm delete-title-keyword" data-id="' . $kw->id . '"><i data-feather="trash-2" class="icon-16"></i></button>
                </div>'
            );
        }

        echo json_encode(array("data" => $data));
    }

    function title_keyword_modal_form() {
        $this->_require_auth();

        $id = $this->request->getPost('id');
        $model_info = $this->Line_expenses_model->get_title_keyword($id);
        if (!$model_info->id) {
            $model_info->sort = $this->Line_expenses_model->get_next_title_sort();
        }
        $view_data = array('model_info' => $model_info);

        return view('settings/line_bot_expenses/title_keyword_modal', $view_data);
    }

    function save_title_keyword() {
        $this->_require_auth();

        $id = $this->request->getPost('id');
        $sort = intval($this->request->getPost('sort') ?: 0);
        if (!$sort) {
            $sort = $this->Line_expenses_model->get_next_title_sort();
        }

        $data = array(
            'keyword' => $this->request->getPost('keyword'),
            'title' => $this->request->getPost('title'),
            'sort' => $sort
        );

        if (!$data['keyword'] || !$data['title']) {
            echo json_encode(array("success" => false, 'message' => 'Keyword and title are required'));
            return;
        }

        $saved = $this->Line_expenses_model->save_title_keyword($data, $id);

        if ($saved) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete_title_keyword() {
        $this->_require_auth();

        $id = $this->request->getPost('id');
        if ($this->Line_expenses_model->delete_title_keyword($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function check_title_keyword_duplicate() {
        $this->_require_auth();

        $keyword = trim($this->request->getPost('keyword'));
        $id = intval($this->request->getPost('id'));
        $exists = $keyword ? $this->Line_expenses_model->title_keyword_exists($keyword, $id) : false;

        echo json_encode(array("success" => true, "exists" => $exists));
    }

    // ========== PROJECT KEYWORDS CRUD ==========

    function project_keywords_list_data() {
        $this->_require_auth();

        $keywords = $this->Line_expenses_model->get_project_keywords()->getResult();
        $data = array();

        foreach ($keywords as $kw) {
            $monthly_badge = $kw->is_monthly_project ? '<span class="badge bg-info">Monthly</span>' : '';
            $data[] = array(
                $kw->keyword,
                $kw->client_name,
                ($kw->project_name ?: '-') . ' ' . $monthly_badge,
                $kw->sort,
                '<div class="text-center">
                    <button class="btn btn-default btn-sm edit-project-keyword" data-id="' . $kw->id . '"><i data-feather="edit" class="icon-16"></i></button>
                    <button class="btn btn-default btn-sm delete-project-keyword" data-id="' . $kw->id . '"><i data-feather="trash-2" class="icon-16"></i></button>
                </div>'
            );
        }

        echo json_encode(array("data" => $data));
    }

    function project_keyword_modal_form() {
        $this->_require_auth();

        $id = $this->request->getPost('id');
        $model_info = $this->Line_expenses_model->get_project_keyword($id);
        // Ensure optional fields exist to avoid undefined property notices on new records.
        if (!isset($model_info->project_id)) {
            $model_info->project_id = 0;
        }
        if (!isset($model_info->project_name)) {
            $model_info->project_name = "";
        }
        if (!isset($model_info->client_name)) {
            $model_info->client_name = "";
        }
        if (!isset($model_info->is_monthly_project)) {
            $model_info->is_monthly_project = 0;
        }
        if (!isset($model_info->sort)) {
            $model_info->sort = 0;
        }
        if (!$model_info->id) {
            $model_info->sort = $this->Line_expenses_model->get_next_project_sort();
        }

        $selected_client_id = 0;
        $selected_project_id = 0;

        if ($model_info && $model_info->project_id) {
            $project = $this->Projects_model->get_one($model_info->project_id);
            if ($project && $project->id) {
                $selected_project_id = $project->id;
                $selected_client_id = $project->client_id;
                if (!$model_info->project_name) {
                    $model_info->project_name = $project->title;
                }
                if (!$model_info->client_name && $selected_client_id) {
                    $client = $this->Clients_model->get_one($selected_client_id);
                    if ($client && $client->id) {
                        $model_info->client_name = $client->company_name;
                    }
                }
            }
        }

        if ($model_info && $model_info->client_name && !$selected_client_id) {
            $client = $this->Clients_model->get_all_where(array(
                "company_name" => $model_info->client_name,
                "deleted" => 0,
                "is_lead" => 0
            ))->getRow();
            if ($client) {
                $selected_client_id = $client->id;
            }
        }

        if ($selected_client_id && !$selected_project_id && $model_info && $model_info->project_name) {
            $project = $this->Projects_model->get_all_where(array(
                "client_id" => $selected_client_id,
                "title" => $model_info->project_name,
                "deleted" => 0
            ))->getRow();
            if ($project) {
                $selected_project_id = $project->id;
            }
        }

        $view_data = array(
            'model_info' => $model_info,
            'clients_dropdown' => $this->_get_clients_dropdown(),
            'projects_dropdown' => $this->_get_projects_dropdown_by_client($selected_client_id),
            'selected_client_id' => $selected_client_id,
            'selected_project_id' => $selected_project_id
        );

        return view('settings/line_bot_expenses/project_keyword_modal', $view_data);
    }

    function save_project_keyword() {
        $this->_require_auth();

        error_log("[Line_bot_expenses] save_project_keyword start: " . json_encode($this->request->getPost()));

        $id = $this->request->getPost('id');
        $client_name = $this->request->getPost('client_name');
        $client_id = $this->request->getPost('client_id');
        if (!$client_name && $client_id) {
            $client = $this->Clients_model->get_one($client_id);
            $client_name = $client ? $client->company_name : '';
        }

        $project_name = $this->request->getPost('project_name') ?: '';
        $project_id = $this->request->getPost('project_id');
        if (!$project_name && $project_id) {
            $project = $this->Projects_model->get_one($project_id);
            $project_name = $project ? $project->title : '';
            if (!$client_id && $project && $project->client_id) {
                $client_id = $project->client_id;
                $client = $this->Clients_model->get_one($client_id);
                $client_name = $client ? $client->company_name : $client_name;
            }
        }

        if ($this->request->getPost('is_monthly_project')) {
            $project_name = '';
            $project_id = 0;
        }

        $sort = intval($this->request->getPost('sort') ?: 0);
        if (!$sort) {
            $sort = $this->Line_expenses_model->get_next_project_sort();
        }

        $data = array(
            'keyword' => $this->request->getPost('keyword'),
            'client_name' => $client_name,
            'project_name' => $project_name,
            'project_id' => intval($project_id ?: 0),
            'is_monthly_project' => $this->request->getPost('is_monthly_project') ? 1 : 0,
            'sort' => $sort
        );

        if (!$data['keyword'] || !$data['client_name']) {
            error_log("[Line_bot_expenses] save_project_keyword validation failed: " . json_encode($data));
            echo json_encode(array("success" => false, 'message' => 'Keyword and client name are required'));
            return;
        }

        try {
            $saved = $this->Line_expenses_model->save_project_keyword($data, $id);
        } catch (\Throwable $e) {
            error_log("[Line_bot_expenses] save_project_keyword exception: " . $e->getMessage());
            error_log($e->getTraceAsString());
            echo json_encode(array("success" => false, 'message' => 'Error saving project keyword'));
            return;
        }

        if ($saved) {
            error_log("[Line_bot_expenses] save_project_keyword success: id=" . $saved);
            echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
        } else {
            error_log("[Line_bot_expenses] save_project_keyword failed");
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete_project_keyword() {
        $this->_require_auth();

        $id = $this->request->getPost('id');
        if ($this->Line_expenses_model->delete_project_keyword($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function check_project_keyword_duplicate() {
        $this->_require_auth();

        $keyword = trim($this->request->getPost('keyword'));
        $id = intval($this->request->getPost('id'));
        $exists = $keyword ? $this->Line_expenses_model->project_keyword_exists($keyword, $id) : false;

        echo json_encode(array("success" => true, "exists" => $exists));
    }

    function get_projects_of_selected_client() {
        $this->_require_auth();

        $client_id = $this->request->getPost("client_id");
        $projects_dropdown = array(array("id" => "", "text" => "- " . app_lang("project") . " -"));

        if ($client_id) {
            $projects = $this->Projects_model->get_all_where(array("client_id" => $client_id, "deleted" => 0, "status_id" => 1), 0, 0, "title")->getResult();
            foreach ($projects as $project) {
                $projects_dropdown[] = array("id" => $project->id, "text" => $project->title);
            }
        } else {
            $projects = $this->Projects_model->get_all_where(array("deleted" => 0, "status_id" => 1), 0, 0, "title")->getResult();
            foreach ($projects as $project) {
                $projects_dropdown[] = array("id" => $project->id, "text" => $project->title);
            }
        }

        echo json_encode($projects_dropdown);
    }

    private function _get_clients_dropdown() {
        $clients_dropdown = array("" => "- " . app_lang("client") . " -");
        $clients = $this->Clients_model->get_dropdown_list(array("company_name"), "id", array("is_lead" => 0));
        foreach ($clients as $key => $value) {
            $clients_dropdown[$key] = $value;
        }
        return $clients_dropdown;
    }

    private function _get_projects_dropdown_by_client($client_id = 0) {
        $projects_dropdown = array("" => "- " . app_lang("project") . " -");
        if ($client_id) {
            $projects = $this->Projects_model->get_all_where(array("client_id" => $client_id, "deleted" => 0, "status_id" => 1), 0, 0, "title")->getResult();
            foreach ($projects as $project) {
                $projects_dropdown[$project->id] = $project->title;
            }
        } else {
            $projects = $this->Projects_model->get_all_where(array("deleted" => 0, "status_id" => 1), 0, 0, "title")->getResult();
            foreach ($projects as $project) {
                $projects_dropdown[$project->id] = $project->title;
            }
        }
        return $projects_dropdown;
    }

    // ========== CATEGORY KEYWORDS CRUD ==========

    function category_keywords_list_data() {
        $this->_require_auth();

        $keywords = $this->Line_expenses_model->get_category_keywords()->getResult();
        $data = array();

        foreach ($keywords as $kw) {
            $cat = $this->Line_expenses_model->find_category_by_id($kw->category_id);
            $category_name = $cat ? $cat->title : 'ID: ' . $kw->category_id;

            $data[] = array(
                $kw->keyword,
                $kw->category_id,
                $category_name,
                $kw->sort,
                '<div class="text-center">
                    <button class="btn btn-default btn-sm edit-category-keyword" data-id="' . $kw->id . '"><i data-feather="edit" class="icon-16"></i></button>
                    <button class="btn btn-default btn-sm delete-category-keyword" data-id="' . $kw->id . '"><i data-feather="trash-2" class="icon-16"></i></button>
                </div>'
            );
        }

        echo json_encode(array("data" => $data));
    }

    function category_keyword_modal_form() {
        $this->_require_auth();

        $id = $this->request->getPost('id');
        $model_info = $this->Line_expenses_model->get_category_keyword($id);
        if (!$model_info->id) {
            $model_info->sort = $this->Line_expenses_model->get_next_category_sort();
        }
        $categories_dropdown = array("" => "- " . app_lang("category") . " -");
        $categories = $this->Expense_categories_model->get_dropdown_list(array("title"));
        foreach ($categories as $key => $value) {
            $categories_dropdown[$key] = $value;
        }

        $view_data = array(
            'model_info' => $model_info,
            'categories_dropdown' => $categories_dropdown
        );

        return view('settings/line_bot_expenses/category_keyword_modal', $view_data);
    }

    function save_category_keyword() {
        $this->_require_auth();

        $id = $this->request->getPost('id');
        $sort = intval($this->request->getPost('sort') ?: 0);
        if (!$sort) {
            $sort = $this->Line_expenses_model->get_next_category_sort();
        }
        $data = array(
            'keyword' => $this->request->getPost('keyword'),
            'category_id' => intval($this->request->getPost('category_id')),
            'sort' => $sort
        );

        if (!$data['keyword'] || !$data['category_id']) {
            echo json_encode(array("success" => false, 'message' => 'Keyword and category ID are required'));
            return;
        }

        $saved = $this->Line_expenses_model->save_category_keyword($data, $id);

        if ($saved) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete_category_keyword() {
        $this->_require_auth();

        $id = $this->request->getPost('id');
        if ($this->Line_expenses_model->delete_category_keyword($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    // ========== EXPENSE LOGS ==========

    function expense_logs_list_data() {
        $this->_require_auth();

        try {
            $logs = $this->Line_expenses_model->get_expense_logs()->getResult();
            $data = array();

            foreach ($logs as $row) {
                $mapping_status = $row->user_id == 1 ? app_lang('fallback_user_id') : app_lang('mapped_user_id');
                $line_user_id = "";
                $mapping_source = "";
                $mapping_reason = "";
                if ($row->changes) {
                    $decoded = json_decode($row->changes, true);
                    if (is_array($decoded)) {
                        $line_user_id = $decoded["line_user_id"] ?? "";
                        $mapping_source = $decoded["mapping_source"] ?? "";
                        $mapping_reason = $decoded["mapping_reason"] ?? "";
                    }
                }
                $data[] = array(
                    $row->log_created_at,
                    $row->expense_date,
                    $row->title,
                    to_currency($row->amount),
                    $row->category_name ?: ('ID: ' . $row->category_id),
                    $row->project_name ?: ('ID: ' . $row->project_id),
                    $row->client_name ?: ('ID: ' . $row->client_id),
                    $row->user_name ?: 'Unknown',
                    $row->user_id,
                    $mapping_status,
                    $line_user_id,
                    $mapping_source,
                    $mapping_reason,
                    $row->expense_id
                );
            }

            echo json_encode(array("data" => $data));
        } catch (\Throwable $e) {
            log_message('error', 'LINE Expenses: expense_logs_list_data error: ' . $e->getMessage());
            echo json_encode(array("data" => array(), "error" => $e->getMessage()));
        }
    }

    function check_category_keyword_duplicate() {
        $this->_require_auth();

        $keyword = trim($this->request->getPost('keyword'));
        $id = intval($this->request->getPost('id'));
        $exists = $keyword ? $this->Line_expenses_model->category_keyword_exists($keyword, $id) : false;

        echo json_encode(array("success" => true, "exists" => $exists));
    }

    // ========== TEST REPORTS ==========

    function test_daily_report() {
        $this->_require_auth();

        $lib = new \App\Libraries\Line_expenses_webhook();
        $result = $lib->send_daily_report();

        echo json_encode($result);
    }

    function test_monthly_report() {
        $this->_require_auth();

        $lib = new \App\Libraries\Line_expenses_webhook();
        $result = $lib->send_monthly_report();

        echo json_encode($result);
    }

    // ========== WEBHOOK ==========

    function webhook() {
        if (session_status() === PHP_SESSION_NONE) {
            // Don't start session for webhook
        }

        $this->response->setContentType('text/plain');

        $input = file_get_contents('php://input');
        log_message('info', 'LINE Expenses Webhook - Raw input: ' . $input);

        if (empty($input)) {
            return $this->response->setBody('OK');
        }

        // Verify signature
        $lib = new \App\Libraries\Line_expenses_webhook();
        $signature = $this->request->getHeaderLine('X-Line-Signature');
        if (!$lib->verify_signature($input, $signature)) {
            log_message('error', 'LINE Expenses Webhook: Invalid signature');
            return $this->response->setStatusCode(401)->setBody('Invalid signature');
        }

        $events = json_decode($input, true);
        if (!$events || !isset($events['events'])) {
            return $this->response->setBody('OK');
        }

        // User sessions stored in settings
        foreach ($events['events'] as $event) {
            try {
                $this->_capture_room($event);
            } catch (\Throwable $e) {
                log_message('error', 'LINE Expenses: _capture_room error: ' . $e->getMessage());
            }

            try {
                $this->_capture_user($event);
            } catch (\Throwable $e) {
                log_message('error', 'LINE Expenses: _capture_user error: ' . $e->getMessage());
            }

            if ($event['type'] === 'message') {
                $user_id = $event['source']['userId'] ?? '';
                $reply_token = $event['replyToken'] ?? '';

                try {
                    if ($event['message']['type'] === 'image') {
                        $this->_handle_image_message($event, $lib, $user_id, $reply_token);
                    } else if ($event['message']['type'] === 'text') {
                        $this->_handle_text_message($event, $lib, $user_id, $reply_token);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'LINE Expenses: Unhandled error in webhook event: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
                    try {
                        $lib->send_reply($reply_token, "เกิดข้อผิดพลาด: {$e->getMessage()}");
                    } catch (\Throwable $e2) {
                        log_message('error', 'LINE Expenses: Failed to send error reply: ' . $e2->getMessage());
                    }
                }
            }
        }

        return $this->response->setBody('OK');
    }

    private function _handle_text_message($event, $lib, $user_id, $reply_token) {
        $text = $event['message']['text'] ?? '';

        try {
            $expense_data = $lib->parse_expense_input($text);

            // Get files from per-image settings (safe for concurrent images)
            $files = array();
            $image_rows = $this->_get_user_image_rows($user_id);
            if ($image_rows) {
                // keep order by created time and limit to last 5
                usort($image_rows, function ($a, $b) {
                    return ($a['ts'] ?? 0) <=> ($b['ts'] ?? 0);
                });
                $image_rows = array_slice($image_rows, -5);
                foreach ($image_rows as $row) {
                    if (isset($row['file'])) {
                        $files[] = $row['file'];
                    }
                }
            }

            $result = $lib->process_expense($user_id, $expense_data, $files);

            if ($result['success'] && $result['flexData']) {
                $flex = $lib->build_expense_confirmation_flex($result['flexData'], $result, $result['userDisplayName']);
                $lib->send_flex_reply($reply_token, $flex);
            } else {
                $flex = $lib->build_expense_confirmation_flex(null, $result, $result['userDisplayName']);
                $lib->send_flex_reply($reply_token, $flex);
            }

        } catch (\Throwable $e) {
            log_message('error', 'LINE Expenses: Error processing text: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            $lib->send_reply($reply_token, "รูปแบบข้อมูลไม่ถูกต้อง: {$e->getMessage()}");
        } finally {
            // Always clear image state after processing (success or error)
            $this->_clear_user_image_rows($user_id);
            $this->Settings_model->save_setting("line_expenses_session_{$user_id}", '');
        }
    }

    private function _handle_image_message($event, $lib, $user_id, $reply_token) {
        $message_id = $event['message']['id'] ?? '';
        $image_data = $lib->download_line_image($message_id);

        if ($image_data) {
            // Use MySQL lock to prevent race condition when multiple images sent at same time
            $lock_name = "line_exp_img_" . substr(md5($user_id), 0, 16);
            $this->db->query("SELECT GET_LOCK(?, 10)", array($lock_name));

            try {
                $image_key = "line_expenses_image_{$user_id}_{$message_id}";
                $payload = array(
                    "file" => $image_data,
                    "ts" => time(),
                    "message_id" => $message_id
                );
                $this->Settings_model->save_setting($image_key, json_encode($payload));
                $count = $this->_count_user_image_rows($user_id);
            } finally {
                $this->db->query("SELECT RELEASE_LOCK(?)", array($lock_name));
            }

            $lib->send_reply($reply_token, "📷 รับรูปภาพแล้ว ({$count}/5)\nกรุณาส่งข้อมูลค่าใช้จ่าย");
        } else {
            $lib->send_reply($reply_token, "เกิดข้อผิดพลาดในการบันทึกรูปภาพ");
        }
    }

    private function _get_user_image_rows($user_id) {
        $settings_table = $this->db->prefixTable('settings');
        $prefix = "line_expenses_image_" . $user_id . "_";
        $rows = $this->db->query("SELECT setting_value FROM $settings_table WHERE setting_name LIKE ?", array($prefix . "%"))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $decoded = json_decode($row->setting_value ?? "", true);
            if (is_array($decoded)) {
                $result[] = $decoded;
            }
        }
        return $result;
    }

    private function _count_user_image_rows($user_id) {
        $settings_table = $this->db->prefixTable('settings');
        $prefix = "line_expenses_image_" . $user_id . "_";
        $row = $this->db->query("SELECT COUNT(*) AS total FROM $settings_table WHERE setting_name LIKE ?", array($prefix . "%"))->getRow();
        return $row && isset($row->total) ? intval($row->total) : 0;
    }

    private function _clear_user_image_rows($user_id) {
        $settings_table = $this->db->prefixTable('settings');
        $prefix = "line_expenses_image_" . $user_id . "_";
        $this->db->query("DELETE FROM $settings_table WHERE setting_name LIKE ?", array($prefix . "%"));
    }

    private function _capture_room($event) {
        $source = $event['source'] ?? array();
        $room_id = $source['roomId'] ?? ($source['groupId'] ?? '');

        if (empty($room_id)) return;

        $rooms_json = get_setting('line_expenses_rooms');
        $rooms = $rooms_json ? json_decode($rooms_json, true) : array();
        if (!is_array($rooms)) $rooms = array();

        foreach ($rooms as $room) {
            if (($room['id'] ?? '') === $room_id) return;
        }

        $type = isset($source['roomId']) ? 'room' : 'group';
        $rooms[] = array('id' => $room_id, 'type' => $type, 'name' => $type . '_' . substr($room_id, 0, 8));
        $this->Settings_model->save_setting('line_expenses_rooms', json_encode($rooms));
    }

    private function _capture_user($event) {
        $source = $event['source'] ?? array();
        $user_id = $source['userId'] ?? '';

        if (empty($user_id)) return;

        $profiles_json = get_setting('line_expenses_user_profiles');
        $profiles = $profiles_json ? json_decode($profiles_json, true) : array();
        if (!is_array($profiles)) $profiles = array();

        foreach ($profiles as $p) {
            if (($p['userId'] ?? '') === $user_id) return;
        }

        $lib = new \App\Libraries\Line_expenses_webhook();
        $profile = $lib->get_user_profile($user_id);
        $profiles[] = array('userId' => $user_id, 'displayName' => $profile['displayName'] ?? 'Unknown');
        $this->Settings_model->save_setting('line_expenses_user_profiles', json_encode($profiles));
    }

    private function _get_rooms_dropdown() {
        $rooms_json = get_setting('line_expenses_rooms');
        $rooms = $rooms_json ? json_decode($rooms_json, true) : array();
        if (!is_array($rooms)) $rooms = array();

        $profiles_json = get_setting('line_expenses_user_profiles');
        $profiles = $profiles_json ? json_decode($profiles_json, true) : array();
        if (!is_array($profiles)) $profiles = array();

        $dropdown = array("" => "- Select -");

        foreach ($profiles as $p) {
            $uid = $p['userId'] ?? '';
            $name = $p['displayName'] ?? 'Unknown';
            if ($uid) {
                $dropdown[$uid] = $name . " (USER)";
            }
        }

        foreach ($rooms as $room) {
            $rid = $room['id'] ?? '';
            $name = $room['name'] ?? $rid;
            $type = strtoupper($room['type'] ?? 'ROOM');
            if ($rid) {
                $dropdown[$rid] = $name . " ({$type})";
            }
        }

        return $dropdown;
    }

    // ========== CRON ENDPOINTS ==========

    function cron_daily_report() {
        if (get_setting('line_expenses_daily_report_enabled') != '1') {
            echo json_encode(array('success' => false, 'message' => 'Daily report disabled'));
            return;
        }

        $now = new \DateTime('now', new \DateTimeZone('Asia/Bangkok'));
        $configured_time = get_setting('line_expenses_daily_report_time') ?: '20:00';
        $current_time = $now->format('H:i');

        if ($current_time !== $configured_time) {
            echo json_encode(array('success' => false, 'message' => "Not time yet. Current: {$current_time}, Configured: {$configured_time}"));
            return;
        }

        $last_sent = get_setting('line_expenses_last_daily_report');
        $today = $now->format('Y-m-d');
        if ($last_sent === $today) {
            echo json_encode(array('success' => false, 'message' => 'Already sent today'));
            return;
        }

        $lib = new \App\Libraries\Line_expenses_webhook();
        $result = $lib->send_daily_report();

        if ($result['success']) {
            $this->Settings_model->save_setting('line_expenses_last_daily_report', $today);
        }

        echo json_encode($result);
    }

    function cron_monthly_report() {
        if (get_setting('line_expenses_monthly_report_enabled') != '1') {
            echo json_encode(array('success' => false, 'message' => 'Monthly report disabled'));
            return;
        }

        $now = new \DateTime('now', new \DateTimeZone('Asia/Bangkok'));
        $configured_time = get_setting('line_expenses_monthly_report_time') ?: '20:01';
        $current_time = $now->format('H:i');

        if ($current_time !== $configured_time) {
            echo json_encode(array('success' => false, 'message' => "Not time yet"));
            return;
        }

        $last_sent = get_setting('line_expenses_last_monthly_report');
        $today = $now->format('Y-m-d');
        if ($last_sent === $today) {
            echo json_encode(array('success' => false, 'message' => 'Already sent today'));
            return;
        }

        // Check if today matches configured days
        $days_config = get_setting('line_expenses_monthly_report_days') ?: '1,monday,saturday';
        $days = array_map('trim', explode(',', strtolower($days_config)));
        $day_of_month = intval($now->format('j'));
        $day_of_week = strtolower($now->format('l'));
        $matches = false;

        foreach ($days as $day) {
            if (is_numeric($day) && intval($day) === $day_of_month) {
                $matches = true;
                break;
            }
            if ($day === $day_of_week) {
                $matches = true;
                break;
            }
        }

        if (!$matches) {
            echo json_encode(array('success' => false, 'message' => 'Not a configured report day'));
            return;
        }

        $lib = new \App\Libraries\Line_expenses_webhook();
        $result = $lib->send_monthly_report();

        if ($result['success']) {
            $this->Settings_model->save_setting('line_expenses_last_monthly_report', $today);
        }

        echo json_encode($result);
    }
}
