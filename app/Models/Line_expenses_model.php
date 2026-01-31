<?php

namespace App\Models;

class Line_expenses_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'line_expenses_title_keywords';
        parent::__construct($this->table);
    }

    // ========== Title Keywords CRUD ==========

    function get_title_keywords() {
        $this->use_table('line_expenses_title_keywords');
        return $this->db_builder->where('deleted', 0)
            ->orderBy('sort', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();
    }

    function get_title_keyword($id) {
        $this->use_table('line_expenses_title_keywords');
        return $this->get_one($id);
    }

    function save_title_keyword($data, $id = 0) {
        $this->use_table('line_expenses_title_keywords');
        return $this->ci_save($data, $id);
    }

    function delete_title_keyword($id) {
        $this->use_table('line_expenses_title_keywords');
        $data = array('deleted' => 1);
        return $this->ci_save($data, $id);
    }

    function find_title_by_exact_keyword($keyword) {
        $this->use_table('line_expenses_title_keywords');
        $result = $this->db_builder->where('keyword', $keyword)
            ->where('deleted', 0)
            ->get();
        if ($result->getRow()) {
            return $result->getRow()->title;
        }
        return null;
    }

    function title_keyword_exists($keyword, $exclude_id = 0) {
        $this->use_table('line_expenses_title_keywords');
        $this->db_builder->where('keyword', $keyword)->where('deleted', 0);
        if ($exclude_id) {
            $this->db_builder->where('id !=', $exclude_id);
        }
        return $this->db_builder->countAllResults() > 0;
    }

    function get_next_title_sort() {
        $this->use_table('line_expenses_title_keywords');
        $row = $this->db_builder->selectMax('sort')->where('deleted', 0)->get()->getRow();
        $max_sort = $row && isset($row->sort) ? intval($row->sort) : 0;
        return $max_sort + 1;
    }

    // ========== Project Keywords CRUD ==========

    function get_project_keywords() {
        $this->use_table('line_expenses_project_keywords');
        return $this->db_builder->where('deleted', 0)
            ->orderBy('sort', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();
    }

    function get_project_keyword($id) {
        $this->use_table('line_expenses_project_keywords');
        return $this->get_one($id);
    }

    function save_project_keyword($data, $id = 0) {
        $this->use_table('line_expenses_project_keywords');
        return $this->ci_save($data, $id);
    }

    function delete_project_keyword($id) {
        $this->use_table('line_expenses_project_keywords');
        $data = array('deleted' => 1);
        return $this->ci_save($data, $id);
    }

    function find_project_by_exact_keyword($keyword) {
        $this->use_table('line_expenses_project_keywords');
        $result = $this->db_builder->where('keyword', $keyword)
            ->where('deleted', 0)
            ->get();
        if ($result->getRow()) {
            return $result->getRow();
        }
        return null;
    }

    function project_keyword_exists($keyword, $exclude_id = 0) {
        $this->use_table('line_expenses_project_keywords');
        $this->db_builder->where('keyword', $keyword)->where('deleted', 0);
        if ($exclude_id) {
            $this->db_builder->where('id !=', $exclude_id);
        }
        return $this->db_builder->countAllResults() > 0;
    }

    function get_next_project_sort() {
        $this->use_table('line_expenses_project_keywords');
        $row = $this->db_builder->selectMax('sort')->where('deleted', 0)->get()->getRow();
        $max_sort = $row && isset($row->sort) ? intval($row->sort) : 0;
        return $max_sort + 1;
    }

    // ========== Category Keywords CRUD ==========

    function get_category_keywords() {
        $this->use_table('line_expenses_category_keywords');
        return $this->db_builder->where('deleted', 0)
            ->orderBy('sort', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();
    }

    function get_category_keyword($id) {
        $this->use_table('line_expenses_category_keywords');
        return $this->get_one($id);
    }

    function save_category_keyword($data, $id = 0) {
        $this->use_table('line_expenses_category_keywords');
        return $this->ci_save($data, $id);
    }

    function delete_category_keyword($id) {
        $this->use_table('line_expenses_category_keywords');
        $data = array('deleted' => 1);
        return $this->ci_save($data, $id);
    }

    function find_category_by_exact_keyword($keyword) {
        $this->use_table('line_expenses_category_keywords');
        $result = $this->db_builder->where('keyword', $keyword)
            ->where('deleted', 0)
            ->get();
        if ($result->getRow()) {
            return $result->getRow();
        }
        return null;
    }

    function category_keyword_exists($keyword, $exclude_id = 0) {
        $this->use_table('line_expenses_category_keywords');
        $this->db_builder->where('keyword', $keyword)->where('deleted', 0);
        if ($exclude_id) {
            $this->db_builder->where('id !=', $exclude_id);
        }
        return $this->db_builder->countAllResults() > 0;
    }

    function get_next_category_sort() {
        $this->use_table('line_expenses_category_keywords');
        $row = $this->db_builder->selectMax('sort')->where('deleted', 0)->get()->getRow();
        $max_sort = $row && isset($row->sort) ? intval($row->sort) : 0;
        return $max_sort + 1;
    }

    // ========== Category Lookup ==========

    function find_category_by_id($id) {
        $this->use_table('expense_categories');
        $result = $this->db_builder->where('id', $id)
            ->where('deleted', 0)
            ->get();
        if ($result->getRow()) {
            return $result->getRow();
        }
        return null;
    }

    // ========== User Mappings ==========

    function get_rise_user_id_from_line_id($line_user_id) {
        if (!$line_user_id) {
            return null;
        }

        // Prefer array-based mappings if available
        if ($this->db->tableExists('user_mappings_arr')) {
            $table = $this->db->table($this->db->getPrefix() . 'user_mappings_arr');

            $row = $table->where('line_user_id', $line_user_id)->get()->getRow();
            if ($row && isset($row->rise_user_id)) {
                return $row->rise_user_id;
            }

            $rows = $table->select('rise_user_id, line_user_ids')->where('line_user_ids IS NOT NULL', null, false)->get()->getResult();
            foreach ($rows as $r) {
                $ids = $this->_parse_line_user_ids($r->line_user_ids ?? "");
                if (in_array($line_user_id, $ids, true)) {
                    return $r->rise_user_id;
                }
            }
        }

        // Fallback: check users table line_user_id field (may contain JSON or CSV)
        if ($this->db->tableExists('users')) {
            $users_table = $this->db->table($this->db->getPrefix() . 'users');
            $rows = $users_table->select('id, line_user_id')->where('line_user_id IS NOT NULL', null, false)->get()->getResult();
            foreach ($rows as $r) {
                $ids = $this->_parse_line_user_ids($r->line_user_id ?? "");
                if (in_array($line_user_id, $ids, true)) {
                    return $r->id;
                }
            }
        }

        // Legacy single-value mappings
        $this->use_table('user_mappings');
        $result = $this->db_builder->where('line_user_id', $line_user_id)->get();
        if ($result->getRow()) {
            return $result->getRow()->rise_user_id;
        }
        return null;
    }

    function save_user_mapping($line_user_id, $display_name, $rise_user_id) {
        if ($this->db->tableExists('user_mappings_arr')) {
            $table_name = $this->db->getPrefix() . 'user_mappings_arr';
            $fields = $this->db->getFieldNames($table_name);
            if (in_array('line_user_id', $fields, true) && in_array('rise_user_id', $fields, true)) {
                $table = $this->db->table($table_name);
                $data = array(
                    'line_user_id' => $line_user_id,
                    'rise_user_id' => $rise_user_id
                );
                if (in_array('line_display_name', $fields, true)) {
                    $data['line_display_name'] = $display_name;
                }
                if (in_array('line_user_ids', $fields, true)) {
                    $data['line_user_ids'] = json_encode(array($line_user_id));
                }
                if (in_array('updated_at', $fields, true)) {
                    $data['updated_at'] = date('Y-m-d H:i:s');
                }

                $existing = $table->where('line_user_id', $line_user_id)->get()->getRow();
                if ($existing) {
                    $table->where('line_user_id', $line_user_id);
                    return $table->update($data);
                }

                if (in_array('created_at', $fields, true)) {
                    $data['created_at'] = date('Y-m-d H:i:s');
                }
                return $table->insert($data);
            }
        }

        // Legacy table fallback
        $this->use_table('user_mappings');
        $existing = $this->db_builder->where('line_user_id', $line_user_id)->get();
        if ($existing->getRow()) {
            $this->db_builder->where('line_user_id', $line_user_id);
            return $this->db_builder->update(array(
                'line_display_name' => $display_name,
                'rise_user_id' => $rise_user_id,
                'updated_at' => date('Y-m-d H:i:s')
            ));
        }

        return $this->db_builder->insert(array(
            'line_user_id' => $line_user_id,
            'line_display_name' => $display_name,
            'rise_user_id' => $rise_user_id,
            'created_at' => date('Y-m-d H:i:s')
        ));
    }

    // ========== Report Data Queries ==========

    function get_daily_expenses_by_project($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $db_prefix = $this->db->getPrefix();

        $sql = "SELECT e.id, e.expense_date, e.title, e.description, e.amount, e.tax_id,
                       e.project_id, e.client_id, e.category_id, e.user_id,
                       p.title as project_title,
                       c.company_name as client_name,
                       cat.title as category_name,
                       u.first_name as user_name,
                       CASE WHEN e.tax_id = 2 THEN e.amount * 0.07 ELSE 0 END as vat_amount
                FROM {$db_prefix}expenses e
                LEFT JOIN {$db_prefix}projects p ON e.project_id = p.id
                LEFT JOIN {$db_prefix}clients c ON e.client_id = c.id
                LEFT JOIN {$db_prefix}expense_categories cat ON e.category_id = cat.id
                LEFT JOIN {$db_prefix}users u ON e.user_id = u.id
                WHERE DATE(e.expense_date) = ? AND e.deleted = 0
                ORDER BY e.project_id, e.id DESC";

        return $this->db->query($sql, array($date));
    }

    function get_monthly_expenses_by_project($start_date, $end_date) {
        $db_prefix = $this->db->getPrefix();

        $sql = "SELECT e.id, e.expense_date, e.title, e.description, e.amount, e.tax_id,
                       e.project_id, e.client_id, e.category_id,
                       p.title as project_title,
                       c.company_name as client_name,
                       cat.title as category_name,
                       CASE WHEN e.tax_id = 2 THEN e.amount * 0.07 ELSE 0 END as vat_amount
                FROM {$db_prefix}expenses e
                LEFT JOIN {$db_prefix}projects p ON e.project_id = p.id
                LEFT JOIN {$db_prefix}clients c ON e.client_id = c.id
                LEFT JOIN {$db_prefix}expense_categories cat ON e.category_id = cat.id
                WHERE DATE(e.expense_date) >= ? AND DATE(e.expense_date) <= ? AND e.deleted = 0
                ORDER BY e.project_id, e.expense_date DESC";

        return $this->db->query($sql, array($start_date, $end_date));
    }

    function get_expenses_created_today($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $db_prefix = $this->db->getPrefix();

        $sql = "SELECT COALESCE(SUM(e.amount), 0) as total_amount, COUNT(e.id) as total_count
                FROM {$db_prefix}expenses e
                INNER JOIN {$db_prefix}activity_logs al ON al.log_type = 'expense' AND al.log_type_id = e.id AND al.action = 'created'
                WHERE DATE(al.created_at) = ? AND e.deleted = 0";

        return $this->db->query($sql, array($date));
    }

    function get_all_projects_with_monthly_expenses($start_date, $end_date) {
        $db_prefix = $this->db->getPrefix();

        $sql = "SELECT p.id as project_id, p.title as project_title,
                       c.company_name as client_name,
                       COALESCE(SUM(e.amount), 0) as total_amount,
                       COUNT(e.id) as expense_count,
                       COALESCE(SUM(CASE WHEN e.tax_id = 2 THEN e.amount * 0.07 ELSE 0 END), 0) as total_vat
                FROM {$db_prefix}expenses e
                LEFT JOIN {$db_prefix}projects p ON e.project_id = p.id
                LEFT JOIN {$db_prefix}clients c ON e.client_id = c.id
                WHERE DATE(e.expense_date) >= ? AND DATE(e.expense_date) <= ? AND e.deleted = 0
                GROUP BY p.id, p.title, c.company_name
                ORDER BY total_amount DESC";

        return $this->db->query($sql, array($start_date, $end_date));
    }

    function get_project_expenses_detail($project_id, $start_date, $end_date) {
        $db_prefix = $this->db->getPrefix();

        $sql = "SELECT e.id, e.expense_date, e.title, e.description, e.amount, e.tax_id,
                       cat.title as category_name,
                       CASE WHEN e.tax_id = 2 THEN e.amount * 0.07 ELSE 0 END as vat_amount
                FROM {$db_prefix}expenses e
                LEFT JOIN {$db_prefix}expense_categories cat ON e.category_id = cat.id
                WHERE e.project_id = ? AND DATE(e.expense_date) >= ? AND DATE(e.expense_date) <= ? AND e.deleted = 0
                ORDER BY e.amount DESC";

        return $this->db->query($sql, array($project_id, $start_date, $end_date));
    }

    // ========== Expense Logs ==========

    function get_expense_logs($limit = 200) {
        $db_prefix = $this->db->getPrefix();
        $limit = intval($limit) > 0 ? intval($limit) : 200;

        $sql = "SELECT al.created_at AS log_created_at, e.id AS expense_id, e.expense_date, e.title,
                       e.amount, e.category_id, e.project_id, e.client_id, e.user_id,
                       p.title AS project_name, c.company_name AS client_name,
                       cat.title AS category_name,
                       CONCAT(u.first_name, ' ', u.last_name) AS user_name
                FROM {$db_prefix}activity_logs al
                LEFT JOIN {$db_prefix}expenses e ON e.id = al.log_type_id
                LEFT JOIN {$db_prefix}projects p ON p.id = e.project_id
                LEFT JOIN {$db_prefix}clients c ON c.id = e.client_id
                LEFT JOIN {$db_prefix}expense_categories cat ON cat.id = e.category_id
                LEFT JOIN {$db_prefix}users u ON u.id = e.user_id
                WHERE al.log_type = 'expense' AND al.action = 'created' AND al.deleted = 0
                ORDER BY al.created_at DESC
                LIMIT {$limit}";

        return $this->db->query($sql);
    }

    // ========== Find or Create Rise User ==========

    function find_or_create_rise_user($display_name, $line_user_id) {
        $this->use_table('users');

        // Try to find existing user by display name or line email
        $line_email = $line_user_id . '@line.user';
        $result = $this->db_builder->groupStart()
            ->where('first_name', $display_name)
            ->orWhere('email', $line_email)
            ->groupEnd()
            ->get();

        if ($result->getRow()) {
            return $result->getRow()->id;
        }

        // Create new user
        $saved = $this->db_builder->insert(array(
            'first_name' => $display_name,
            'last_name' => '',
            'user_type' => 'staff',
            'email' => $line_email,
            'status' => 'active',
            'job_title' => 'Staff',
            'language' => 'thai',
            'created_at' => date('Y-m-d H:i:s')
        ));
        if ($saved) {
            return $this->db->insertID();
        }
        return null;
    }

    private function _parse_line_user_ids($raw) {
        if (!$raw) {
            return array();
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map("trim", $decoded)));
        }

        return array_values(array_filter(array_map("trim", explode(",", $raw))));
    }
}
