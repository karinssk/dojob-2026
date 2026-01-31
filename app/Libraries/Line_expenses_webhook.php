<?php

namespace App\Libraries;

class Line_expenses_webhook {

    private $channel_access_token;
    private $channel_secret;
    private $db;
    private $Line_expenses_model;

    private $thai_month_names = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];

    function __construct() {
        $this->channel_access_token = get_setting('line_expenses_channel_access_token');
        $this->channel_secret = get_setting('line_expenses_channel_secret');
        $this->db = \Config\Database::connect();
        $this->Line_expenses_model = model('App\Models\Line_expenses_model');
    }

    // ========== NUMBER FORMATTING ==========

    public function format_number_with_commas($number) {
        if (!is_numeric($number)) {
            $number = floatval($number);
        }
        if (is_nan($number)) {
            return '0';
        }
        $formatted = number_format($number, 2, '.', ',');
        // Remove trailing zeros but keep at least one decimal
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');
        if (strpos($formatted, '.') !== false) {
            $parts = explode('.', $formatted);
            if (strlen($parts[1]) < 1) {
                $formatted = $parts[0];
            }
        }
        return $formatted;
    }

    // ========== DATE HELPERS ==========

    public function get_current_thai_date() {
        $now = new \DateTime('now', new \DateTimeZone('Asia/Bangkok'));
        $day = $now->format('d');
        $month = $now->format('m');
        $year = substr(($now->format('Y') + 543), -2);
        return "{$day}/{$month}/{$year}";
    }

    public function parse_buddhist_date($date_str) {
        try {
            $parts = explode('/', $date_str);
            if (count($parts) === 3) {
                $day = intval($parts[0]);
                $month = intval($parts[1]);
                $year = intval($parts[2]);

                if ($year < 100) {
                    if ($year >= 68) {
                        $year = 2500 + $year;
                    } else {
                        $year = 2600 + $year;
                    }
                }

                $christian_year = $year - 543;
                return sprintf('%04d-%02d-%02d', $christian_year, $month, $day);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error parsing Buddhist date: ' . $e->getMessage());
        }

        return date('Y-m-d');
    }

    public function get_buddhist_month_year($date_str) {
        try {
            $parts = explode('/', $date_str);
            if (count($parts) === 3) {
                $month = intval($parts[1]);
                $year = intval($parts[2]);

                if ($year < 100) {
                    $year = 2500 + $year;
                }

                $month_name = $this->thai_month_names[$month - 1] ?? 'มกราคม';
                return "ค่าใช้จ่าย เดือน{$month_name} {$year}";
            }
        } catch (\Exception $e) {
            log_message('error', 'Error getting Buddhist month/year: ' . $e->getMessage());
        }

        $now = new \DateTime('now', new \DateTimeZone('Asia/Bangkok'));
        $buddhist_year = $now->format('Y') + 543;
        $month_name = $this->thai_month_names[intval($now->format('m')) - 1];
        return "ค่าใช้จ่าย เดือน{$month_name} {$buddhist_year}";
    }

    // ========== AMOUNT PARSING ==========

    private function parse_amount($amount_str) {
        if (empty($amount_str) || !is_string($amount_str)) {
            throw new \Exception('Invalid amount format');
        }

        $clean = trim($amount_str);
        $clean = str_replace(',', '', $clean);

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
            throw new \Exception("Invalid amount format: {$amount_str}");
        }

        $amount = floatval($clean);
        if (is_nan($amount) || $amount <= 0) {
            throw new \Exception("Invalid amount: {$amount_str}");
        }

        return round($amount * 100) / 100;
    }

    // ========== EXPENSE INPUT PARSING ==========

    public function parse_expense_input($text) {
        if (empty($text) || !is_string($text)) {
            throw new \Exception('Invalid input: text is required');
        }

        $parts = explode('-', $text);

        if (count($parts) < 5) {
            throw new \Exception('Invalid input format. Expected at least: date-title-category-description-amount-project (optional: -vat)');
        }

        // Parse date
        $date_input = trim($parts[0]);
        if (empty($date_input)) {
            $date_input = $this->get_current_thai_date();
        }

        // Parse title keyword (exact match)
        $title_keyword = trim($parts[1]);
        if (empty($title_keyword)) {
            throw new \Exception('Title keyword is required');
        }

        // Parse category
        $category_input = trim($parts[2]);
        if (empty($category_input)) {
            throw new \Exception('Category is required');
        }

        // Check VAT
        $last_part = $parts[count($parts) - 1];
        $has_vat = (strtolower(trim($last_part)) === 'vat' || strpos(strtolower(trim($last_part)), 'vat') !== false);

        if ($has_vat) {
            if (count($parts) < 6) {
                throw new \Exception('Invalid input format with VAT');
            }
            $project_index = count($parts) - 2;
            $amount_index = count($parts) - 3;
            $desc_end_index = count($parts) - 3;
        } else {
            $project_index = count($parts) - 1;
            $amount_index = count($parts) - 2;
            $desc_end_index = count($parts) - 2;
        }

        // Parse amount
        $amount = $this->parse_amount(trim($parts[$amount_index]));

        // Parse project keyword (exact match)
        $project_keyword = trim($parts[$project_index]);
        if (empty($project_keyword)) {
            throw new \Exception('Project keyword is required');
        }

        // Parse description
        $desc_parts = array_slice($parts, 3, $desc_end_index - 3);
        $description = implode('-', array_map('trim', $desc_parts));
        if (empty($description)) {
            throw new \Exception('Description is required');
        }

        return array(
            'date' => $date_input,
            'titleKeyword' => $title_keyword,
            'categoryKeyword' => $category_input,
            'description' => $description,
            'amount' => $amount,
            'projectKeyword' => $project_keyword,
            'hasVat' => $has_vat
        );
    }

    // ========== VAT CALCULATION ==========

    public function calculate_vat($pre_vat_amount, $vat_rate = 0.07) {
        $vat_amount = $pre_vat_amount * $vat_rate;
        $total_with_vat = $pre_vat_amount + $vat_amount;

        return array(
            'preVat' => round($pre_vat_amount * 100) / 100,
            'vatAmount' => round($vat_amount * 100) / 100,
            'postVat' => round($total_with_vat * 100) / 100
        );
    }

    private function get_vat_rate() {
        try {
            $db_prefix = $this->db->getPrefix();
            $result = $this->db->query("SELECT percentage FROM {$db_prefix}taxes WHERE id = 2");
            if ($result->getRow()) {
                return $result->getRow()->percentage / 100;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error getting VAT rate: ' . $e->getMessage());
        }
        return 0.07;
    }

    // ========== PHP SERIALIZED FILES ==========

    private function create_php_serialized_files($files) {
        if (empty($files)) {
            return '';
        }

        $count = count($files);
        $serialized = "a:{$count}:{";

        foreach ($files as $index => $file) {
            $file_name = $file['file_name'];
            $file_size = strval($file['file_size']);
            $fn_len = strlen($file_name);
            $fs_len = strlen($file_size);

            $serialized .= "i:{$index};a:4:{";
            $serialized .= "s:9:\"file_name\";s:{$fn_len}:\"{$file_name}\";";
            $serialized .= "s:9:\"file_size\";s:{$fs_len}:\"{$file_size}\";";
            $serialized .= "s:7:\"file_id\";N;";
            $serialized .= "s:12:\"service_type\";N;";
            $serialized .= "}";
        }

        $serialized .= "}";
        return $serialized;
    }

    // ========== FIND CLIENT & PROJECT ==========

    private function find_or_create_client_project($keyword, $input_date = null) {
        // Exact match lookup
        $project_config = $this->Line_expenses_model->find_project_by_exact_keyword($keyword);

        if (!$project_config) {
            return array(
                'clientId' => 0,
                'clientName' => 'Unknown Client',
                'projectId' => 0,
                'projectName' => 'Unknown Project'
            );
        }

        $db_prefix = $this->db->getPrefix();

        // Find or create client
        $client_result = $this->db->query(
            "SELECT id FROM {$db_prefix}clients WHERE company_name LIKE ?",
            array('%' . $project_config->client_name . '%')
        );

        $client_id = 0;
        if ($client_result->getRow()) {
            $client_id = $client_result->getRow()->id;
        } else {
            $this->db->query(
                "INSERT INTO {$db_prefix}clients (company_name, type, created_date, created_by, owner_id, starred_by, group_ids, last_lead_status, client_migration_date, stripe_customer_id, stripe_card_ending_digit) VALUES (?, 'organization', CURDATE(), 1, 1, '', '', '', CURDATE(), '', 0)",
                array($project_config->client_name)
            );
            $client_id = $this->db->insertID();
        }

        // Handle monthly project
        if ($project_config->is_monthly_project) {
            if ($input_date) {
                $project_title = $this->get_buddhist_month_year($input_date);
            } else {
                $now = new \DateTime('now', new \DateTimeZone('Asia/Bangkok'));
                $buddhist_year = $now->format('Y') + 543;
                $month_name = $this->thai_month_names[intval($now->format('m')) - 1];
                $project_title = "ค่าใช้จ่าย เดือน{$month_name} {$buddhist_year}";
            }

            $project_result = $this->db->query(
                "SELECT id, title FROM {$db_prefix}projects WHERE title = ? AND deleted = 0",
                array($project_title)
            );

            if ($project_result->getRow()) {
                $project_id = $project_result->getRow()->id;
            } else {
                $this->db->query(
                    "INSERT INTO {$db_prefix}projects (title, client_id, created_date, created_by, status, status_id, starred_by, estimate_id, order_id, deleted) VALUES (?, ?, CURDATE(), 1, 'open', 1, '', 0, 0, 0)",
                    array($project_title, $client_id)
                );
                $project_id = $this->db->insertID();
            }

            return array(
                'clientId' => $client_id,
                'clientName' => $project_config->client_name,
                'projectId' => $project_id,
                'projectName' => $project_title
            );
        }

        // Handle regular project
        $project_id = 0;
        $project_name = $project_config->project_name;
        if (!empty($project_config->project_id)) {
            $project_result = $this->db->query(
                "SELECT id, title FROM {$db_prefix}projects WHERE id = ? AND deleted = 0",
                array($project_config->project_id)
            );

            if ($project_result->getRow()) {
                $project_id = $project_result->getRow()->id;
                $project_name = $project_result->getRow()->title;
            }
        } else if (!empty($project_name)) {
            $project_result = $this->db->query(
                "SELECT id FROM {$db_prefix}projects WHERE title LIKE ? AND deleted = 0",
                array('%' . $project_name . '%')
            );

            if ($project_result->getRow()) {
                $project_id = $project_result->getRow()->id;
            } else {
                $this->db->query(
                    "INSERT INTO {$db_prefix}projects (title, client_id, created_date, created_by, status, status_id, starred_by, estimate_id, order_id, deleted) VALUES (?, ?, CURDATE(), 1, 'open', 1, '', 0, 0, 0)",
                    array($project_name, $client_id)
                );
                $project_id = $this->db->insertID();
            }
        }

        return array(
            'clientId' => $client_id,
            'clientName' => $project_config->client_name,
            'projectId' => $project_id,
            'projectName' => $project_name ?: ''
        );
    }

    // ========== GET RISE USER FROM LINE USER ==========

    private function get_rise_user_mapping($line_user_id, $display_name) {
        $mapping = $this->Line_expenses_model->get_rise_user_mapping_info($line_user_id);
        $rise_user_id = $mapping["rise_user_id"] ?? 1;

        if (($mapping["source"] ?? "") === "created_user") {
            $this->Line_expenses_model->save_user_mapping($line_user_id, $display_name, $rise_user_id);
        }

        return $mapping;
    }

    // ========== PROCESS EXPENSE ==========

    public function process_expense($line_user_id, $expense_data, $files = array()) {
        try {
            $profile = $this->get_user_profile($line_user_id);
            $display_name = $profile['displayName'] ?? 'คุณ';
            $mapping = $this->get_rise_user_mapping($line_user_id, $display_name);
            $rise_user_id = $mapping["rise_user_id"] ?? 1;

            $db_prefix = $this->db->getPrefix();

            // Parse date
            $expense_date = $this->parse_buddhist_date($expense_data['date']);

            // Find title by exact keyword match
            $title = $this->Line_expenses_model->find_title_by_exact_keyword($expense_data['titleKeyword']);
            if (!$title) {
                $title = $expense_data['titleKeyword'];
            }

            // Find category by keyword or ID
            $category_input = $expense_data['categoryKeyword'];
            $category_id = intval(get_setting('line_expenses_default_category_id') ?: 24);
            $cat_keyword = $this->Line_expenses_model->find_category_by_exact_keyword($category_input);
            if ($cat_keyword) {
                $category_id = $cat_keyword->category_id;
            } else if (is_numeric($category_input)) {
                $cat = $this->Line_expenses_model->find_category_by_id(intval($category_input));
                if ($cat) {
                    $category_id = $cat->id;
                }
            }

            // Find client and project by exact keyword match
            $client_project = $this->find_or_create_client_project($expense_data['projectKeyword'], $expense_data['date']);

            // Prepare files
            $files_data = $this->create_php_serialized_files($files);

            // VAT
            $vat_rate = $this->get_vat_rate();
            $vat_calculation = $this->calculate_vat($expense_data['amount'], $vat_rate);
            $tax_id = $expense_data['hasVat'] ? 2 : 0;
            $amount_to_store = $expense_data['amount'];

            // Insert expense
            $this->db->query("
                INSERT INTO {$db_prefix}expenses (
                    expense_date, category_id, description, amount, files, title,
                    project_id, user_id, tax_id, tax_id2, client_id, recurring,
                    recurring_expense_id, repeat_every, repeat_type, no_of_cycles,
                    next_recurring_date, no_of_cycles_completed, deleted
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0, 0, 0, NULL, 0, NULL, 0, 0)
            ", array(
                $expense_date,
                $category_id,
                "- ค่า{$expense_data['description']}",
                $amount_to_store,
                $files_data,
                $title,
                $client_project['projectId'],
                $rise_user_id,
                $tax_id,
                $client_project['clientId']
            ));

            $expense_id = $this->db->insertID();

            // Log activity
            $mapping_meta = json_encode(array(
                "line_user_id" => $line_user_id,
                "mapping_source" => $mapping["source"] ?? "fallback",
                "mapping_reason" => $mapping["reason"] ?? ""
            ));

            $this->db->query("
                INSERT INTO {$db_prefix}activity_logs (
                    created_at, created_by, action, log_type, log_type_title,
                    log_type_id, changes, log_for, log_for_id, log_for2, log_for_id2, deleted
                ) VALUES (NOW(), ?, 'created', 'expense', ?, ?, ?, 'project', ?, NULL, NULL, 0)
            ", array($rise_user_id, $title, $expense_id, $mapping_meta, $client_project['projectId']));

            // Get category name
            $cat_obj = $this->Line_expenses_model->find_category_by_id($category_id);
            $category_name = $cat_obj ? $cat_obj->title : 'Unknown Category';

            $display_date = implode('/', array_reverse(explode('-', $expense_date)));

            return array(
                'success' => true,
                'message' => "บันทึกค่าใช้จ่ายเรียบร้อยแล้ว",
                'expenseId' => $expense_id,
                'vatCalculation' => $expense_data['hasVat'] ? $vat_calculation : null,
                'flexData' => array_merge($expense_data, array(
                    'title' => $title,
                    'categoryName' => $category_name,
                    'clientName' => $client_project['clientName'],
                    'projectName' => $client_project['projectName'],
                    'displayDate' => $display_date
                )),
                'userDisplayName' => $display_name
            );

        } catch (\Exception $e) {
            log_message('error', 'Error processing expense: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => "เกิดข้อผิดพลาด: {$e->getMessage()}",
                'flexData' => null,
                'userDisplayName' => 'คุณ'
            );
        }
    }

    // ========== LINE API CALLS ==========

    public function get_user_profile($user_id) {
        try {
            $ch = curl_init("https://api.line.me/v2/bot/profile/{$user_id}");
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer {$this->channel_access_token}"
                )
            ));
            $response = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($response, true);
            return $data ?: array('displayName' => 'คุณ', 'userId' => $user_id);
        } catch (\Exception $e) {
            return array('displayName' => 'คุณ', 'userId' => $user_id);
        }
    }

    public function send_reply($reply_token, $message) {
        $payload = array(
            'replyToken' => $reply_token,
            'messages' => array(
                array('type' => 'text', 'text' => $message)
            )
        );
        return $this->_post_to_line('https://api.line.me/v2/bot/message/reply', $payload);
    }

    public function send_flex_reply($reply_token, $flex_message) {
        $payload = array(
            'replyToken' => $reply_token,
            'messages' => array(
                array(
                    'type' => 'flex',
                    'altText' => 'Expense Confirmation',
                    'contents' => $flex_message
                )
            )
        );
        return $this->_post_to_line('https://api.line.me/v2/bot/message/reply', $payload);
    }

    public function send_push_message($to, $message) {
        $payload = array(
            'to' => $to,
            'messages' => array(
                array('type' => 'text', 'text' => $message)
            )
        );
        return $this->_post_to_line('https://api.line.me/v2/bot/message/push', $payload);
    }

    public function send_push_flex($to, $flex_content, $alt_text = 'Expense Report') {
        $payload = array(
            'to' => $to,
            'messages' => array(
                array(
                    'type' => 'flex',
                    'altText' => $alt_text,
                    'contents' => $flex_content
                )
            )
        );
        return $this->_post_to_line('https://api.line.me/v2/bot/message/push', $payload);
    }

    private function _post_to_line($url, $payload) {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer {$this->channel_access_token}",
                    "Content-Type: application/json"
                ),
                CURLOPT_POSTFIELDS => json_encode($payload)
            ));
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return array('success' => ($http_code >= 200 && $http_code < 300), 'response' => $response);
        } catch (\Exception $e) {
            log_message('error', 'LINE API error: ' . $e->getMessage());
            return array('success' => false, 'response' => $e->getMessage());
        }
    }

    public function download_line_image($message_id) {
        try {
            $ch = curl_init("https://api-data.line.me/v2/bot/message/{$message_id}/content");
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer {$this->channel_access_token}"
                )
            ));
            $content = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200 || empty($content)) {
                return null;
            }

            $app_config = config("App");
            $upload_dir = FCPATH . $app_config->timeline_file_path;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ms = (int) round(microtime(true) * 1000);
            $iso = gmdate('Y-m-d\TH:i:s', (int) ($ms / 1000)) . '.' . str_pad($ms % 1000, 3, '0', STR_PAD_LEFT) . 'Z';
            $file_name = 'expense_file' . $ms . '-' . rand(100000000, 999999999) . '_' . $iso . '.jpg';
            $file_path = $upload_dir . $file_name;
            file_put_contents($file_path, $content);

            return array(
                'file_name' => $file_name,
                'file_size' => filesize($file_path),
                'file_path' => $file_path
            );
        } catch (\Exception $e) {
            log_message('error', 'Error downloading LINE image: ' . $e->getMessage());
            return null;
        }
    }

    // ========== FLEX MESSAGE: EXPENSE CONFIRMATION ==========

    public function build_expense_confirmation_flex($expense_data, $result, $user_display_name) {
        $status_color = $result['success'] ? "#22c55e" : "#ef4444";
        $status_text = $result['success'] ? "บันทึกเรียบร้อย" : "เกิดข้อผิดพลาด";
        $bg_color = $result['success'] ? "#f0fdf4" : "#fef2f2";

        $body_contents = array();

        if ($result['success'] && $expense_data) {
            $body_contents[] = $this->_flex_row("รายการ:", $expense_data['title'] ?? 'N/A');
            $body_contents[] = $this->_flex_row("รายละเอียด:", "- ค่า" . ($expense_data['description'] ?? 'N/A'));
            $body_contents[] = $this->_flex_row("โครงการ:", $expense_data['projectName'] ?? 'N/A');
            $body_contents[] = $this->_flex_row("วันที่:", $expense_data['displayDate'] ?? 'N/A');
            $body_contents[] = $this->_flex_row("จำนวนเงิน:", $this->format_number_with_commas($expense_data['amount']) . " บาท");
            $body_contents[] = $this->_flex_row("VAT:", $expense_data['hasVat'] ? "รวม VAT 7%" : "ไม่รวม VAT");

            if ($result['vatCalculation']) {
                $vc = $result['vatCalculation'];
                $body_contents[] = array('type' => 'separator', 'margin' => 'md');
                $body_contents[] = $this->_flex_row("จำนวน (ก่อน VAT):", $this->format_number_with_commas($vc['preVat']) . " บาท");
                $body_contents[] = $this->_flex_row("VAT (7%):", $this->format_number_with_commas($vc['vatAmount']) . " บาท");
                $body_contents[] = $this->_flex_row("รวม (หลัง VAT):", $this->format_number_with_commas($vc['postVat']) . " บาท");
            }

            $body_contents[] = array('type' => 'separator', 'margin' => 'lg');
            $body_contents[] = array(
                'type' => 'box', 'layout' => 'horizontal', 'margin' => 'lg',
                'contents' => array(
                    array('type' => 'text', 'text' => 'Expense ID:', 'size' => 'xs', 'color' => '#aaaaaa', 'flex' => 1),
                    array('type' => 'text', 'text' => '#' . $result['expenseId'], 'size' => 'xs', 'color' => '#aaaaaa', 'flex' => 1, 'align' => 'end')
                )
            );
        } else {
            $body_contents[] = array(
                'type' => 'text',
                'text' => $result['message'] ?? 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ',
                'size' => 'sm', 'color' => '#ef4444', 'wrap' => true, 'margin' => 'md'
            );
        }

        return array(
            'type' => 'bubble',
            'header' => array(
                'type' => 'box', 'layout' => 'vertical',
                'backgroundColor' => $bg_color, 'paddingAll' => '20px',
                'contents' => array(
                    array(
                        'type' => 'text', 'text' => $status_text,
                        'size' => 'lg', 'weight' => 'bold', 'color' => $status_color
                    ),
                    array(
                        'type' => 'text',
                        'text' => $result['success'] ? "ค่าใช้จ่ายของ {$user_display_name}" : "ไม่สามารถบันทึกได้",
                        'size' => 'sm', 'color' => '#666666', 'margin' => 'sm'
                    )
                )
            ),
            'body' => array(
                'type' => 'box', 'layout' => 'vertical',
                'contents' => $body_contents, 'paddingAll' => '20px'
            )
        );
    }

    private function _flex_row($label, $value, $margin = 'sm') {
        return array(
            'type' => 'box', 'layout' => 'horizontal', 'margin' => $margin,
            'contents' => array(
                array('type' => 'text', 'text' => $label, 'size' => 'sm', 'color' => '#666666', 'flex' => 2),
                array('type' => 'text', 'text' => $value, 'size' => 'sm', 'color' => '#333333', 'flex' => 3, 'wrap' => true)
            )
        );
    }

    // ========== DAILY REPORT ==========

    public function generate_daily_report_data($target_date = null) {
        $now = new \DateTime('now', new \DateTimeZone('Asia/Bangkok'));
        $today = $target_date ?: $now->format('Y-m-d');
        $thai_date = implode('/', array_reverse(explode('-', $today)));

        $db_prefix = $this->db->getPrefix();

        // Get all project keywords
        $project_keywords = $this->Line_expenses_model->get_project_keywords()->getResult();

        $total_day_expense = 0;
        $has_expenses = false;
        $projects = array();
        $project_count = 0;

        foreach ($project_keywords as $pk) {
            $project_title = '';
            $db_projects = array();

            if (!empty($pk->project_name)) {
                $project_title = $pk->project_name;
                $result = $this->db->query(
                    "SELECT id, title FROM {$db_prefix}projects WHERE title = ? AND deleted = 0",
                    array($pk->project_name)
                );
                $db_projects = $result->getResult();
            } else if ($pk->is_monthly_project) {
                $date_obj = $target_date ? new \DateTime($target_date) : $now;
                $buddhist_year = $date_obj->format('Y') + 543;
                $month_name = $this->thai_month_names[intval($date_obj->format('m')) - 1];
                $project_title = "ค่าใช้จ่าย เดือน{$month_name} {$buddhist_year}";

                $result = $this->db->query(
                    "SELECT id, title FROM {$db_prefix}projects WHERE title = ? AND deleted = 0",
                    array($project_title)
                );
                $db_projects = $result->getResult();
            }

            $project_total = 0;
            $expense_details = array();
            $project_has_expenses = false;

            foreach ($db_projects as $project) {
                $expenses = $this->db->query("
                    SELECT description, amount, tax_id,
                           CASE WHEN tax_id = 2 THEN amount * 0.07 ELSE 0 END as vat_amount
                    FROM {$db_prefix}expenses
                    WHERE project_id = ? AND DATE(expense_date) = ? AND deleted = 0
                    ORDER BY id
                ", array($project->id, $today));

                foreach ($expenses->getResult() as $expense) {
                    $amount = floatval($expense->amount);
                    $vat_amount = floatval($expense->vat_amount);
                    $total_with_vat = $amount + $vat_amount;
                    $project_total += $total_with_vat;

                    $expense_details[] = array(
                        'description' => $expense->description,
                        'amount' => $total_with_vat,
                        'formattedAmount' => $this->format_number_with_commas($total_with_vat),
                        'hasVat' => ($expense->tax_id == 2),
                        'preVatAmount' => $amount,
                        'vatAmount' => $vat_amount
                    );
                    $project_has_expenses = true;
                }
            }

            $project_count++;
            $projects[] = array(
                'index' => $project_count,
                'title' => $project_title,
                'client' => $pk->client_name,
                'total' => $project_total,
                'formattedTotal' => $this->format_number_with_commas($project_total),
                'hasExpenses' => $project_has_expenses,
                'expenses' => $expense_details,
                'expenseCount' => count($expense_details)
            );

            if ($project_has_expenses) {
                $total_day_expense += $project_total;
                $has_expenses = true;
            }
        }

        // Get created today totals
        $created_today = $this->db->query("
            SELECT COUNT(*) as expense_count,
                   COALESCE(SUM(e.amount + CASE WHEN e.tax_id = 2 THEN e.amount * 0.07 ELSE 0 END), 0) as total_with_vat
            FROM {$db_prefix}activity_logs al
            JOIN {$db_prefix}expenses e ON al.log_type = 'expense' AND al.log_type_id = e.id
            WHERE al.action = 'created' AND DATE(al.created_at) = ? AND e.deleted = 0
        ", array($today));

        $ct = $created_today->getRow();

        return array(
            'date' => $today,
            'thaiDate' => $thai_date,
            'totalExpense' => $total_day_expense,
            'formattedTotal' => $this->format_number_with_commas($total_day_expense),
            'createdTodayTotal' => floatval($ct->total_with_vat ?? 0),
            'formattedCreatedTodayTotal' => $this->format_number_with_commas(floatval($ct->total_with_vat ?? 0)),
            'createdTodayCount' => intval($ct->expense_count ?? 0),
            'hasExpenses' => $has_expenses,
            'projectCount' => $project_count,
            'projects' => $projects
        );
    }

    public function build_daily_header_flex($daily_data) {
        $header_color = $daily_data['hasExpenses'] ? "#1DB446" : "#999999";
        $header_contents = array();

        // Date row
        $header_contents[] = array(
            'type' => 'box', 'layout' => 'baseline', 'spacing' => 'xs',
            'contents' => array(
                array('type' => 'text', 'text' => 'วันที่รายงาน:', 'color' => '#8C8C8C', 'size' => 'xs', 'flex' => 2),
                array('type' => 'text', 'text' => $daily_data['thaiDate'], 'weight' => 'bold', 'color' => '#1DB446', 'size' => 'xs', 'flex' => 4, 'wrap' => true)
            )
        );

        // Projects (show up to 5)
        $projects_to_show = array_slice($daily_data['projects'], 0, 5);
        foreach ($projects_to_show as $index => $project) {
            $display_title = mb_strlen($project['title']) > 30 ? mb_substr($project['title'], 0, 30) . "..." : $project['title'];

            $header_contents[] = array(
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'none', 'margin' => 'xs',
                'contents' => array(
                    array('type' => 'text', 'text' => ($index + 1) . '.', 'color' => '#8C8C8C', 'size' => 'xs', 'flex' => 0, 'margin' => 'none'),
                    array('type' => 'text', 'text' => $display_title, 'color' => '#333333', 'size' => 'xs', 'flex' => 5, 'wrap' => true, 'margin' => 'xs'),
                    array('type' => 'text', 'text' => $project['hasExpenses'] ? $project['formattedTotal'] : '0.0', 'weight' => 'bold', 'color' => $project['hasExpenses'] ? '#FF6B6B' : '#999999', 'size' => 'xs', 'flex' => 2, 'align' => 'end')
                )
            );

            $header_contents[] = array(
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'none', 'margin' => 'none',
                'contents' => array(
                    array('type' => 'text', 'text' => ' ', 'flex' => 0),
                    array('type' => 'text', 'text' => $project['hasExpenses'] ? "📋 {$project['expenseCount']} รายการ" : "📋 ไม่มีรายการ", 'color' => '#999999', 'size' => 'xxs', 'flex' => 7, 'margin' => 'sm')
                )
            );
        }

        if (count($daily_data['projects']) > 0) {
            $header_contents[] = array('type' => 'separator', 'margin' => 'md');
        }

        // Other projects summary
        if (count($daily_data['projects']) > 5) {
            $other = array_slice($daily_data['projects'], 5);
            $other_total = array_sum(array_column($other, 'total'));
            $remaining = count($other);

            $header_contents[] = array(
                'type' => 'text', 'text' => "📋 โครงการอื่นๆ ({$remaining} โครงการ)",
                'color' => '#FF9500', 'size' => 'xs', 'weight' => 'bold', 'align' => 'center', 'margin' => 'sm'
            );
            $header_contents[] = array(
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'xs', 'margin' => 'xs',
                'contents' => array(
                    array('type' => 'text', 'text' => "รวม:", 'color' => '#8C8C8C', 'size' => 'xs', 'flex' => 3),
                    array('type' => 'text', 'text' => $this->format_number_with_commas($other_total), 'weight' => 'bold', 'color' => $other_total > 0 ? '#FF9500' : '#999999', 'size' => 'xs', 'flex' => 2, 'align' => 'end')
                )
            );
        }

        // Grand total
        $header_contents[] = array('type' => 'separator', 'margin' => 'lg');
        $header_contents[] = array(
            'type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm', 'margin' => 'lg',
            'contents' => array(
                array('type' => 'text', 'text' => 'ค่าใช้จ่ายเฉพาะวันนี้:', 'color' => '#1DB446', 'size' => 'md', 'weight' => 'bold', 'flex' => 3),
                array('type' => 'text', 'text' => "{$daily_data['formattedTotal']} ฿", 'weight' => 'bold', 'color' => '#1DB446', 'size' => 'lg', 'flex' => 2, 'align' => 'end')
            )
        );
        $header_contents[] = array(
            'type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm', 'margin' => 'md',
            'contents' => array(
                array('type' => 'text', 'text' => 'ค่าใช้จ่ายที่เพิ่มวันนี้:', 'color' => '#0066CC', 'size' => 'sm', 'weight' => 'bold', 'flex' => 3),
                array('type' => 'text', 'text' => "{$daily_data['formattedCreatedTodayTotal']} ฿", 'weight' => 'bold', 'color' => '#0066CC', 'size' => 'md', 'flex' => 2, 'align' => 'end')
            )
        );

        return array(
            'type' => 'bubble',
            'header' => array(
                'type' => 'box', 'layout' => 'vertical', 'backgroundColor' => $header_color, 'paddingAll' => '20px',
                'contents' => array(
                    array('type' => 'text', 'text' => '📊 สรุปค่าใช้จ่ายประจำวัน', 'weight' => 'bold', 'color' => '#ffffff', 'size' => 'lg'),
                    array('type' => 'text', 'text' => "วันที่ {$daily_data['thaiDate']}", 'color' => '#ffffff', 'size' => 'md', 'margin' => 'sm')
                )
            ),
            'body' => array(
                'type' => 'box', 'layout' => 'vertical', 'contents' => $header_contents, 'paddingAll' => '20px'
            )
        );
    }

    public function build_daily_project_flex($project, $index, $total_projects) {
        $header_color = $project['hasExpenses'] ? "#0066CC" : "#999999";
        $expense_contents = array();

        $sorted = $project['expenses'];
        usort($sorted, function($a, $b) { return $b['amount'] - $a['amount']; });
        $display = array_slice($sorted, 0, 3);

        foreach ($display as $i => $expense) {
            $desc = !empty(trim($expense['description'])) ? $expense['description'] : 'รายการไม่ระบุ';

            $expense_contents[] = array(
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'none', 'margin' => 'xs',
                'contents' => array(
                    array('type' => 'text', 'text' => ($i + 1) . '.', 'color' => '#8C8C8C', 'size' => 'xs', 'flex' => 0, 'margin' => 'none'),
                    array('type' => 'text', 'text' => $desc, 'color' => '#666666', 'size' => 'xs', 'flex' => 4, 'wrap' => true, 'margin' => 'sm'),
                    array('type' => 'text', 'text' => $expense['formattedAmount'], 'weight' => 'bold', 'color' => $expense['hasVat'] ? '#E60012' : '#1DB446', 'size' => 'xs', 'flex' => 2, 'align' => 'end')
                )
            );
            $expense_contents[] = array(
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'none', 'margin' => 'none',
                'contents' => array(
                    array('type' => 'text', 'text' => ' ', 'flex' => 0),
                    array('type' => 'text', 'text' => "📅 วันนี้" . ($expense['hasVat'] ? ' | VAT 7%' : ''), 'color' => '#999999', 'size' => 'xxs', 'flex' => 4, 'margin' => 'sm')
                )
            );
        }

        if (count($project['expenses']) > 3) {
            $remaining = array_slice($sorted, 3);
            $remaining_total = array_sum(array_column($remaining, 'amount'));
            $expense_contents[] = array(
                'type' => 'text', 'text' => "... และอีก " . count($remaining) . " รายการ ({$this->format_number_with_commas($remaining_total)} ฿)",
                'color' => '#999999', 'size' => 'xs', 'align' => 'center', 'margin' => 'sm'
            );
        }

        if (empty($project['expenses'])) {
            $expense_contents[] = array(
                'type' => 'text', 'text' => 'ไม่มีรายการค่าใช้จ่ายในวันนี้',
                'color' => '#999999', 'size' => 'sm', 'align' => 'center', 'margin' => 'md'
            );
        }

        return array(
            'type' => 'bubble',
            'header' => array(
                'type' => 'box', 'layout' => 'vertical', 'backgroundColor' => $header_color,
                'paddingTop' => '19px', 'paddingAll' => '12px', 'paddingBottom' => '16px',
                'contents' => array(
                    array('type' => 'text', 'text' => "🏗️ โครงการ {$index}/{$total_projects}", 'weight' => 'bold', 'color' => '#ffffff', 'size' => 'sm'),
                    array('type' => 'text', 'text' => $project['title'] ?: 'โครงการไม่ระบุ', 'weight' => 'bold', 'color' => '#ffffff', 'size' => 'md', 'wrap' => true)
                )
            ),
            'body' => array(
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => '20px',
                'contents' => array(
                    array('type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm',
                        'contents' => array(
                            array('type' => 'text', 'text' => 'ลูกค้า:', 'color' => '#8C8C8C', 'size' => 'sm', 'flex' => 2),
                            array('type' => 'text', 'text' => $project['client'] ?: 'ไม่ระบุ', 'weight' => 'bold', 'color' => '#333333', 'size' => 'sm', 'flex' => 5, 'wrap' => true)
                        )
                    ),
                    array('type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm', 'margin' => 'md',
                        'contents' => array(
                            array('type' => 'text', 'text' => 'ยอดรวม:', 'color' => '#8C8C8C', 'size' => 'sm', 'flex' => 2),
                            array('type' => 'text', 'text' => "{$project['formattedTotal']} ฿", 'weight' => 'bold', 'color' => $project['hasExpenses'] ? '#1DB446' : '#999999', 'size' => 'lg', 'flex' => 5, 'align' => 'end')
                        )
                    ),
                    array('type' => 'separator', 'margin' => 'lg'),
                    array('type' => 'text', 'text' => 'รายการค่าใช้จ่าย:', 'weight' => 'bold', 'color' => '#333333', 'size' => 'sm', 'margin' => 'lg'),
                    array('type' => 'box', 'layout' => 'vertical', 'contents' => $expense_contents, 'spacing' => 'xs', 'margin' => 'md')
                )
            )
        );
    }

    public function send_daily_report($target_date = null) {
        $target_id = get_setting('line_expenses_report_target_id');
        if (empty($target_id)) {
            return array('success' => false, 'error' => 'Report target not configured');
        }

        $daily_data = $this->generate_daily_report_data($target_date);
        $bubbles = array();

        $bubbles[] = $this->build_daily_header_flex($daily_data);

        $projects_to_show = array_slice($daily_data['projects'], 0, 9);
        foreach ($projects_to_show as $project) {
            $bubbles[] = $this->build_daily_project_flex($project, $project['index'], $daily_data['projectCount']);
        }

        $carousel = array('type' => 'carousel', 'contents' => $bubbles);
        $alt_text = "สรุปค่าใช้จ่ายประจำวัน {$daily_data['thaiDate']} - {$daily_data['formattedTotal']} ฿";

        $result = $this->send_push_flex($target_id, $carousel, $alt_text);

        return array(
            'success' => $result['success'],
            'messageCount' => count($bubbles),
            'sentTo' => $target_id,
            'dailyData' => $daily_data
        );
    }

    // ========== MONTHLY REPORT ==========

    public function generate_monthly_report_data($target_date = null) {
        $now = $target_date ? new \DateTime($target_date) : new \DateTime('now', new \DateTimeZone('Asia/Bangkok'));
        $year = intval($now->format('Y'));
        $month = intval($now->format('m'));

        $first_day = sprintf('%04d-%02d-01', $year, $month);
        $current_day = $now->format('Y-m-d');
        $buddhist_year = $year + 543;
        $month_name = $this->thai_month_names[$month - 1];

        $db_prefix = $this->db->getPrefix();
        $project_keywords = $this->Line_expenses_model->get_project_keywords()->getResult();

        $total_month_expense = 0;
        $projects_data = array();

        foreach ($project_keywords as $pk_index => $pk) {
            $project_title = '';
            $db_projects = array();

            if (!empty($pk->project_name)) {
                $project_title = $pk->project_name;
                $result = $this->db->query(
                    "SELECT id, title FROM {$db_prefix}projects WHERE title = ? AND deleted = 0",
                    array($pk->project_name)
                );
                $db_projects = $result->getResult();
            } else if ($pk->is_monthly_project) {
                $project_title = "ค่าใช้จ่าย เดือน{$month_name} {$buddhist_year}";
                $result = $this->db->query(
                    "SELECT id, title FROM {$db_prefix}projects WHERE title = ? AND deleted = 0",
                    array($project_title)
                );
                $db_projects = $result->getResult();
            }

            $project_total = 0;
            $expenses = array();

            foreach ($db_projects as $project) {
                $exp_result = $this->db->query("
                    SELECT id, expense_date, description, amount, tax_id, title,
                           CASE WHEN tax_id = 2 THEN amount * 0.07 ELSE 0 END as vat_amount
                    FROM {$db_prefix}expenses
                    WHERE project_id = ? AND DATE(expense_date) >= ? AND DATE(expense_date) <= ? AND deleted = 0
                    ORDER BY expense_date DESC, id DESC LIMIT 10
                ", array($project->id, $first_day, $current_day));

                foreach ($exp_result->getResult() as $expense) {
                    $amount = floatval($expense->amount);
                    $vat_amount = floatval($expense->vat_amount);
                    $total_with_vat = $amount + $vat_amount;
                    $project_total += $total_with_vat;

                    $exp_date = date('d/m/Y', strtotime($expense->expense_date));
                    $desc = !empty(trim($expense->description ?? '')) ? $expense->description : 'รายการไม่ระบุ';
                    if (mb_strlen($desc) > 30) {
                        $desc = mb_substr($desc, 0, 30) . '...';
                    }

                    $expenses[] = array(
                        'id' => $expense->id,
                        'description' => $desc,
                        'amount' => $total_with_vat,
                        'formattedAmount' => $this->format_number_with_commas($total_with_vat),
                        'date' => $exp_date,
                        'hasVat' => ($expense->tax_id == 2)
                    );
                }
            }

            // Sort by amount desc, keep top 3
            usort($expenses, function($a, $b) { return $b['amount'] - $a['amount']; });
            $expenses = array_slice($expenses, 0, 3);

            $total_month_expense += $project_total;

            $display_title = $project_title ?: "โครงการ " . ($pk_index + 1);
            $short_title = mb_strlen($display_title) > 25 ? mb_substr($display_title, 0, 25) . '...' : $display_title;

            $projects_data[] = array(
                'index' => $pk_index + 1,
                'title' => $short_title,
                'fullTitle' => $display_title,
                'total' => $project_total,
                'formattedTotal' => $this->format_number_with_commas($project_total),
                'expenses' => $expenses,
                'expenseCount' => count($expenses)
            );
        }

        $days_elapsed = intval($now->format('d'));

        return array(
            'monthName' => $month_name,
            'buddhistYear' => $buddhist_year,
            'startDate' => implode('/', array_reverse(explode('-', $first_day))),
            'endDate' => implode('/', array_reverse(explode('-', $current_day))),
            'totalExpense' => $total_month_expense,
            'formattedTotal' => $this->format_number_with_commas($total_month_expense),
            'averagePerDay' => $this->format_number_with_commas($days_elapsed > 0 ? $total_month_expense / $days_elapsed : 0),
            'projects' => $projects_data,
            'projectCount' => count($projects_data)
        );
    }

    public function build_monthly_header_flex($month_data) {
        $header_contents = array();

        $header_contents[] = array(
            'type' => 'box', 'layout' => 'baseline', 'spacing' => 'xs',
            'contents' => array(
                array('type' => 'text', 'text' => 'ช่วงวันที่:', 'color' => '#8C8C8C', 'size' => 'xs', 'flex' => 2),
                array('type' => 'text', 'text' => "{$month_data['startDate']} - {$month_data['endDate']}", 'weight' => 'bold', 'color' => '#1DB446', 'size' => 'xs', 'flex' => 4, 'wrap' => true)
            )
        );

        $projects_to_show = array_slice($month_data['projects'], 0, 3);
        foreach ($projects_to_show as $index => $project) {
            $header_contents[] = array(
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'none', 'margin' => 'xs',
                'contents' => array(
                    array('type' => 'text', 'text' => ($index + 1) . '.', 'color' => '#8C8C8C', 'size' => 'xs', 'flex' => 0, 'margin' => 'none'),
                    array('type' => 'text', 'text' => $project['fullTitle'], 'color' => '#333333', 'size' => 'xs', 'flex' => 5, 'wrap' => true, 'margin' => 'xs'),
                    array('type' => 'text', 'text' => $project['formattedTotal'], 'weight' => 'bold', 'color' => '#FF6B6B', 'size' => 'xs', 'flex' => 2, 'align' => 'end')
                )
            );
            $header_contents[] = array(
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'none', 'margin' => 'none',
                'contents' => array(
                    array('type' => 'text', 'text' => ' ', 'flex' => 0),
                    array('type' => 'text', 'text' => "📋 {$project['expenseCount']} รายการ", 'color' => '#999999', 'size' => 'xxs', 'flex' => 7, 'margin' => 'sm')
                )
            );
        }

        $header_contents[] = array('type' => 'separator', 'margin' => 'md');

        // Total
        $header_contents[] = array(
            'type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm', 'margin' => 'md',
            'contents' => array(
                array('type' => 'text', 'text' => '💰 รวมทั้งหมด:', 'color' => '#8C8C8C', 'size' => 'sm', 'flex' => 2),
                array('type' => 'text', 'text' => "{$month_data['formattedTotal']} บาท", 'weight' => 'bold', 'color' => '#E60012', 'size' => 'lg', 'flex' => 3)
            )
        );
        $header_contents[] = array(
            'type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm',
            'contents' => array(
                array('type' => 'text', 'text' => '📈 เฉลี่ย/วัน:', 'color' => '#8C8C8C', 'size' => 'sm', 'flex' => 2),
                array('type' => 'text', 'text' => "{$month_data['averagePerDay']} บาท", 'weight' => 'bold', 'color' => '#0B7EC0', 'size' => 'sm', 'flex' => 3)
            )
        );

        return array(
            'type' => 'bubble',
            'header' => array(
                'type' => 'box', 'layout' => 'vertical', 'backgroundColor' => '#27ACB2',
                'paddingTop' => '19px', 'paddingAll' => '12px', 'paddingBottom' => '16px',
                'contents' => array(
                    array('type' => 'text', 'text' => '📊 รายงานค่าใช้จ่ายประจำเดือน', 'weight' => 'bold', 'color' => '#ffffff', 'size' => 'lg', 'align' => 'center'),
                    array('type' => 'text', 'text' => "เดือน{$month_data['monthName']} {$month_data['buddhistYear']}", 'weight' => 'bold', 'color' => '#ffffff', 'size' => 'md', 'align' => 'center')
                )
            ),
            'body' => array(
                'type' => 'box', 'layout' => 'vertical', 'contents' => $header_contents, 'spacing' => 'md', 'paddingAll' => '12px'
            )
        );
    }

    public function build_monthly_project_flex($project, $project_number, $total_projects) {
        $expense_contents = array();
        $display_expenses = array_slice($project['expenses'], 0, 3);

        foreach ($display_expenses as $i => $expense) {
            $desc = !empty(trim($expense['description'])) ? $expense['description'] : 'รายการไม่ระบุ';

            $expense_contents[] = array(
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'none', 'margin' => 'xs',
                'contents' => array(
                    array('type' => 'text', 'text' => ($i + 1) . '.', 'color' => '#8C8C8C', 'size' => 'xs', 'flex' => 0, 'margin' => 'none'),
                    array('type' => 'text', 'text' => $desc, 'color' => '#666666', 'size' => 'xs', 'flex' => 4, 'wrap' => true, 'margin' => 'sm'),
                    array('type' => 'text', 'text' => $expense['formattedAmount'], 'weight' => 'bold', 'color' => $expense['hasVat'] ? '#E60012' : '#1DB446', 'size' => 'xs', 'flex' => 2, 'align' => 'end')
                )
            );
            $expense_contents[] = array(
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'none', 'margin' => 'none',
                'contents' => array(
                    array('type' => 'text', 'text' => ' ', 'flex' => 0),
                    array('type' => 'text', 'text' => "📅 {$expense['date']}" . ($expense['hasVat'] ? ' | VAT 7%' : ''), 'color' => '#999999', 'size' => 'xxs', 'flex' => 4, 'margin' => 'sm')
                )
            );
        }

        if (count($project['expenses']) > 3) {
            $expense_contents[] = array(
                'type' => 'text', 'text' => "... และอีก " . (count($project['expenses']) - 3) . " รายการ",
                'color' => '#999999', 'size' => 'xs', 'align' => 'center', 'margin' => 'sm'
            );
        }

        if (empty($project['expenses'])) {
            $expense_contents[] = array(
                'type' => 'text', 'text' => 'ไม่มีรายการค่าใช้จ่าย',
                'color' => '#999999', 'size' => 'sm', 'align' => 'center', 'margin' => 'md'
            );
        }

        return array(
            'type' => 'bubble',
            'header' => array(
                'type' => 'box', 'layout' => 'vertical', 'backgroundColor' => '#FF6B6B',
                'paddingTop' => '19px', 'paddingAll' => '12px', 'paddingBottom' => '16px',
                'contents' => array(
                    array('type' => 'text', 'text' => "🏗️ โครงการ {$project['index']}/{$total_projects}", 'weight' => 'bold', 'color' => '#ffffff', 'size' => 'sm'),
                    array('type' => 'text', 'text' => $project['title'] ?: 'โครงการไม่ระบุ', 'weight' => 'bold', 'color' => '#ffffff', 'size' => 'md', 'wrap' => true)
                )
            ),
            'body' => array(
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'md', 'paddingAll' => '12px',
                'contents' => array(
                    array('type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm',
                        'contents' => array(
                            array('type' => 'text', 'text' => '💰 ยอดรวม:', 'color' => '#8C8C8C', 'size' => 'sm', 'flex' => 2),
                            array('type' => 'text', 'text' => "{$project['formattedTotal']} บาท", 'weight' => 'bold', 'color' => '#E60012', 'size' => 'lg', 'flex' => 3)
                        )
                    ),
                    array('type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm', 'margin' => 'md',
                        'contents' => array(
                            array('type' => 'text', 'text' => '📋 รายการ:', 'color' => '#8C8C8C', 'size' => 'sm', 'flex' => 2),
                            array('type' => 'text', 'text' => "{$project['expenseCount']} รายการ", 'weight' => 'bold', 'color' => '#666666', 'size' => 'sm', 'flex' => 3)
                        )
                    ),
                    array('type' => 'text', 'text' => '💰 ยอดใหญ่ Top 3:', 'color' => '#FF6B6B', 'size' => 'xs', 'weight' => 'bold', 'align' => 'center', 'margin' => 'sm'),
                    array('type' => 'separator', 'margin' => 'md'),
                    array('type' => 'box', 'layout' => 'vertical', 'contents' => $expense_contents, 'margin' => 'md', 'spacing' => 'xs')
                )
            )
        );
    }

    public function send_monthly_report($target_date = null) {
        $target_id = get_setting('line_expenses_report_target_id');
        if (empty($target_id)) {
            return array('success' => false, 'error' => 'Report target not configured');
        }

        $month_data = $this->generate_monthly_report_data($target_date);
        $bubbles = array();

        $bubbles[] = $this->build_monthly_header_flex($month_data);

        $max_projects = 9;
        $projects_to_show = array_slice($month_data['projects'], 0, $max_projects);
        foreach ($projects_to_show as $project) {
            if (!empty($project['title'])) {
                $bubbles[] = $this->build_monthly_project_flex($project, $project['index'], $month_data['projectCount']);
            }
        }

        $carousel = array('type' => 'carousel', 'contents' => $bubbles);
        $alt_text = "รายงานค่าใช้จ่ายประจำเดือน {$month_data['monthName']} {$month_data['buddhistYear']}";

        $result = $this->send_push_flex($target_id, $carousel, $alt_text);

        return array(
            'success' => $result['success'],
            'messageCount' => count($bubbles),
            'sentTo' => $target_id,
            'monthData' => $month_data
        );
    }

    // ========== SIGNATURE VERIFICATION ==========

    public function verify_signature($body, $signature) {
        if (empty($this->channel_secret)) {
            return true; // No secret configured, skip verification
        }
        $hash = hash_hmac('sha256', $body, $this->channel_secret, true);
        $expected = base64_encode($hash);
        return hash_equals($expected, $signature);
    }
}
