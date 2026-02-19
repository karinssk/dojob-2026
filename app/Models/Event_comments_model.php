<?php

namespace App\Models;

class Event_comments_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'event_comments';
        parent::__construct($this->table);
    }

    function get_by_event($event_id) {
        $t = $this->db->prefixTable('event_comments');
        $users_t = $this->db->prefixTable('users');

        return $this->db->query(
            "SELECT $t.*,
                    CONCAT($users_t.first_name,' ',$users_t.last_name) AS created_by_user,
                    $users_t.image AS created_by_avatar
             FROM $t
             LEFT JOIN $users_t ON $users_t.id = $t.created_by
             WHERE $t.deleted=0 AND $t.event_id=?
             ORDER BY $t.created_at DESC",
            [(int)$event_id]
        )->getResult();
    }
}
