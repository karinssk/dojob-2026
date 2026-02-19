<?php

namespace App\Models;

class Liff_pending_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'liff_pending_registrations';
        parent::__construct($this->table);
    }

    function get_details($options = []) {
        $t = $this->db->prefixTable('liff_pending_registrations');
        $users_t = $this->db->prefixTable('users');

        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $t.id=$id";
        }

        $line_uid = $this->_get_clean_value($options, "line_uid");
        if ($line_uid) {
            $where .= " AND $t.line_uid='" . $this->db->escapeString($line_uid) . "'";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $t.status='" . $this->db->escapeString($status) . "'";
        }

        $rise_user_id = $this->_get_clean_value($options, "rise_user_id");
        if ($rise_user_id) {
            $where .= " AND $t.rise_user_id=$rise_user_id";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $s = $this->db->escapeString($search);
            $where .= " AND ($t.line_display_name LIKE '%$s%' OR $t.line_uid LIKE '%$s%' OR $t.rise_user_name LIKE '%$s%')";
        }

        $sql = "SELECT $t.*,
                    CONCAT($users_t.first_name,' ',$users_t.last_name) AS approver_name
                FROM $t
                LEFT JOIN $users_t ON $users_t.id = $t.approved_by
                WHERE 1=1 $where
                ORDER BY $t.created_at DESC";

        return $this->db->query($sql);
    }

    function get_pending_count() {
        $t = $this->db->prefixTable('liff_pending_registrations');
        $row = $this->db->query("SELECT COUNT(*) AS cnt FROM $t WHERE status='pending'")->getRow();
        return $row ? (int)$row->cnt : 0;
    }

    function get_by_line_uid($line_uid) {
        $t = $this->db->prefixTable('liff_pending_registrations');
        return $this->db->query(
            "SELECT * FROM $t WHERE line_uid=? ORDER BY created_at DESC LIMIT 1",
            [$line_uid]
        )->getRow();
    }

    function approve($id, $admin_user_id, $line_uid, $rise_user_id, $line_display_name) {
        // 1. Update pending record
        $this->ci_save([
            'status'      => 'approved',
            'approved_by' => $admin_user_id,
            'approved_at' => date('Y-m-d H:i:s'),
        ], $id);

        // 2. Upsert into user_mappings_arr (LIFF uses line_liff_user_id)
        $map_t = get_user_mappings_table();
        $existing = $this->db->query(
            "SELECT id FROM $map_t WHERE rise_user_id=? LIMIT 1",
            [$rise_user_id]
        )->getRow();

        if ($existing) {
            $this->db->query(
                "UPDATE $map_t SET line_liff_user_id=?, updated_at=NOW() WHERE id=?",
                [$line_uid, $existing->id]
            );
        } else {
            // line_user_id is NOT NULL in schema; use line_uid for first-time mapping
            $this->db->query(
                "INSERT INTO $map_t (line_user_id, line_liff_user_id, rise_user_id, line_display_name, nick_name, is_active, line_user_ids, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())",
                [$line_uid, $line_uid, $rise_user_id, $line_display_name, $line_display_name, json_encode([$line_uid])]
            );
        }

        return true;
    }

    function reject($id, $note = '') {
        return $this->ci_save([
            'status'         => 'rejected',
            'rejection_note' => $note,
        ], $id);
    }

    function revoke_by_line_uid($line_uid) {
        $map_t = get_user_mappings_table();
        $this->db->query(
            "UPDATE $map_t SET line_liff_user_id=NULL, updated_at=NOW() WHERE line_liff_user_id=?",
            [$line_uid]
        );
    }

    function reopen($id) {
        return $this->ci_save([
            'status'         => 'pending',
            'rejection_note' => null,
            'approved_by'    => null,
            'approved_at'    => null,
        ], $id);
    }
}
