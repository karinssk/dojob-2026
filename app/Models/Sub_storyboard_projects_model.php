<?php

namespace App\Models;

class Sub_storyboard_projects_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'sub_storyboard_projects';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $sub_projects_table = $this->db->prefixTable('sub_storyboard_projects');
        $projects_table = $this->db->prefixTable('projects');
        $users_table = $this->db->prefixTable('users');
        $assigned_users_table = $this->db->prefixTable('users') . ' AS assigned_users';

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $sub_projects_table.id=$id";
        }

        $rise_story_id = $this->_get_clean_value($options, "rise_story_id");
        if ($rise_story_id) {
            $where .= " AND $sub_projects_table.rise_story_id=$rise_story_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $sub_projects_table.status='$status'";
        }

        $assigned_to = $this->_get_clean_value($options, "assigned_to");
        if ($assigned_to) {
            $where .= " AND $sub_projects_table.assigned_to=$assigned_to";
        }

        $sql = "SELECT $sub_projects_table.*, 
                       $projects_table.title as main_project_title,
                       CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_user,
                       CONCAT(assigned_users.first_name, ' ', assigned_users.last_name) AS assigned_to_user
                FROM $sub_projects_table
                LEFT JOIN $projects_table ON $projects_table.id = $sub_projects_table.rise_story_id
                LEFT JOIN $users_table ON $users_table.id = $sub_projects_table.created_by
                LEFT JOIN $assigned_users_table ON assigned_users.id = $sub_projects_table.assigned_to
                WHERE $sub_projects_table.deleted=0 $where
                ORDER BY $sub_projects_table.sort_order ASC, $sub_projects_table.id ASC";

        return $this->db->query($sql);
    }

    function get_sub_project_statistics($rise_story_id = 0) {
        $sub_projects_table = $this->db->prefixTable('sub_storyboard_projects');
        
        $where = "";
        if ($rise_story_id) {
            $where = " AND rise_story_id = $rise_story_id";
        }

        $sql = "SELECT 
                    status,
                    COUNT(*) as total
                FROM $sub_projects_table 
                WHERE deleted = 0 $where
                GROUP BY status";

        return $this->db->query($sql);
    }

    function get_sub_project_with_scene_count($rise_story_id) {
        $sub_projects_table = $this->db->prefixTable('sub_storyboard_projects');
        $storyboards_table = $this->db->prefixTable('storyboards');
        $users_table = $this->db->prefixTable('users');
        $assigned_users_table = $this->db->prefixTable('users') . ' AS assigned_users';
        
        $sql = "SELECT $sub_projects_table.*, 
                       COUNT($storyboards_table.id) as scene_count,
                       CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_user,
                       CONCAT(assigned_users.first_name, ' ', assigned_users.last_name) AS assigned_to_user
                FROM $sub_projects_table
                LEFT JOIN $storyboards_table ON $storyboards_table.sub_storyboard_project_id = $sub_projects_table.id 
                    AND $storyboards_table.deleted = 0
                LEFT JOIN $users_table ON $users_table.id = $sub_projects_table.created_by
                LEFT JOIN $assigned_users_table ON assigned_users.id = $sub_projects_table.assigned_to
                WHERE $sub_projects_table.rise_story_id = $rise_story_id 
                    AND $sub_projects_table.deleted = 0
                GROUP BY $sub_projects_table.id, $users_table.first_name, $users_table.last_name, 
                         assigned_users.first_name, assigned_users.last_name
                ORDER BY $sub_projects_table.sort_order ASC, $sub_projects_table.id ASC";

        return $this->db->query($sql);
    }

    function reorder_sub_projects($rise_story_id, $sub_project_orders) {
        $sub_projects_table = $this->db->prefixTable('sub_storyboard_projects');
        
        foreach ($sub_project_orders as $index => $sub_project_id) {
            $sql = "UPDATE $sub_projects_table 
                    SET sort_order = " . ($index + 1) . " 
                    WHERE id = $sub_project_id AND rise_story_id = $rise_story_id";
            $this->db->query($sql);
        }
        
        return true;
    }
}