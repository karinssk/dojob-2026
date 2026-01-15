<?php

namespace App\Models;

class Storyboards_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'storyboards';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $storyboards_table = $this->db->prefixTable('storyboards');
        $projects_table = $this->db->prefixTable('projects');
        $users_table = $this->db->prefixTable('users');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $storyboards_table.id=$id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $storyboards_table.project_id=$project_id";
        }

        $sub_storyboard_project_id = $this->_get_clean_value($options, "sub_storyboard_project_id");
        if ($sub_storyboard_project_id) {
            $where .= " AND $storyboards_table.sub_storyboard_project_id=$sub_storyboard_project_id";
        }

        $scene_heading_id = $this->_get_clean_value($options, "scene_heading_id");
        if ($scene_heading_id) {
            $where .= " AND $storyboards_table.scene_heading_id=$scene_heading_id";
        }

        $story_status = $this->_get_clean_value($options, "story_status");
        if ($story_status) {
            $where .= " AND $storyboards_table.story_status='$story_status'";
        }

        $sql = "SELECT $storyboards_table.*, 
                       $projects_table.title as project_title,
                       CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_user
                FROM $storyboards_table
                LEFT JOIN $projects_table ON $projects_table.id = $storyboards_table.project_id
                LEFT JOIN $users_table ON $users_table.id = $storyboards_table.created_by
                WHERE $storyboards_table.deleted=0 $where
                ORDER BY $storyboards_table.sort_order ASC, $storyboards_table.shot ASC";

        return $this->db->query($sql);
    }

    function get_storyboard_statistics($project_id = 0, $sub_storyboard_project_id = null) {
        $storyboards_table = $this->db->prefixTable('storyboards');
        
        $where = "";
        if ($project_id) {
            $where .= " AND project_id = $project_id";
        }
        
        if ($sub_storyboard_project_id) {
            $where .= " AND sub_storyboard_project_id = $sub_storyboard_project_id";
        }

        $sql = "SELECT 
                    story_status,
                    COUNT(*) as total
                FROM $storyboards_table 
                WHERE deleted = 0 $where
                GROUP BY story_status";

        return $this->db->query($sql);
    }

    function get_max_shot_number($project_id, $sub_storyboard_project_id = null) {
        $storyboards_table = $this->db->prefixTable('storyboards');
        
        $where = "project_id = $project_id AND deleted = 0";
        if ($sub_storyboard_project_id) {
            $where .= " AND sub_storyboard_project_id = $sub_storyboard_project_id";
        }
        
        $sql = "SELECT MAX(shot) as max_shot 
                FROM $storyboards_table 
                WHERE $where";
        
        $result = $this->db->query($sql)->getRow();
        return $result ? $result->max_shot : 0;
    }

    function update_sort_order($id, $sort_order) {
        $storyboards_table = $this->db->prefixTable('storyboards');
        
        $sql = "UPDATE $storyboards_table 
                SET sort_order = $sort_order 
                WHERE id = $id";
        
        return $this->db->query($sql);
    }

    function reorder_shots($project_id, $shot_orders) {
        $storyboards_table = $this->db->prefixTable('storyboards');
        
        foreach ($shot_orders as $index => $shot_id) {
            $sql = "UPDATE $storyboards_table 
                    SET sort_order = " . ($index + 1) . " 
                    WHERE id = $shot_id AND project_id = $project_id";
            $this->db->query($sql);
        }
        
        return true;
    }
}