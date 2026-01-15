<?php

namespace App\Models;

class Scene_headings_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'scene_headings';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $scene_headings_table = $this->db->prefixTable('scene_headings');
        $projects_table = $this->db->prefixTable('projects');
        $users_table = $this->db->prefixTable('users');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $scene_headings_table.id=$id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $scene_headings_table.project_id=$project_id";
        }

        $sub_storyboard_project_id = $this->_get_clean_value($options, "sub_storyboard_project_id");
        if ($sub_storyboard_project_id) {
            $where .= " AND $scene_headings_table.sub_storyboard_project_id=$sub_storyboard_project_id";
        }

        $sql = "SELECT $scene_headings_table.*, 
                       $projects_table.title as project_title,
                       CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_user
                FROM $scene_headings_table
                LEFT JOIN $projects_table ON $projects_table.id = $scene_headings_table.project_id
                LEFT JOIN $users_table ON $users_table.id = $scene_headings_table.created_by
                WHERE $scene_headings_table.deleted=0 $where
                ORDER BY $scene_headings_table.sort_order ASC, $scene_headings_table.id ASC";

        return $this->db->query($sql);
    }

    function get_scene_headings_with_scenes($project_id, $sub_project_id = null) {
        $scene_headings_table = $this->db->prefixTable('scene_headings');
        $storyboards_table = $this->db->prefixTable('storyboards');
        
        $where = "WHERE $scene_headings_table.deleted = 0 AND $scene_headings_table.project_id = $project_id";
        
        if ($sub_project_id) {
            $where .= " AND $scene_headings_table.sub_storyboard_project_id = $sub_project_id";
        }

        $sql = "SELECT $scene_headings_table.*, 
                       COUNT($storyboards_table.id) as scene_count,
                       SUM(CASE WHEN $storyboards_table.duration IS NOT NULL AND $storyboards_table.duration != '' 
                           THEN CAST($storyboards_table.duration AS DECIMAL(10,2)) ELSE 0 END) as total_duration
                FROM $scene_headings_table
                LEFT JOIN $storyboards_table ON $storyboards_table.scene_heading_id = $scene_headings_table.id 
                    AND $storyboards_table.deleted = 0
                $where
                GROUP BY $scene_headings_table.id
                ORDER BY $scene_headings_table.sort_order ASC, $scene_headings_table.id ASC";

        return $this->db->query($sql);
    }

    function reorder_scene_headings($project_id, $heading_orders) {
        $scene_headings_table = $this->db->prefixTable('scene_headings');
        
        foreach ($heading_orders as $index => $heading_id) {
            $sql = "UPDATE $scene_headings_table 
                    SET sort_order = " . ($index + 1) . " 
                    WHERE id = $heading_id AND project_id = $project_id";
            $this->db->query($sql);
        }
        
        return true;
    }

    function get_next_shot_number($project_id, $sub_project_id = null) {
        $scene_headings_table = $this->db->prefixTable('scene_headings');
        
        $where = "WHERE project_id = $project_id AND deleted = 0";
        if ($sub_project_id) {
            $where .= " AND sub_storyboard_project_id = $sub_project_id";
        }
        
        $sql = "SELECT MAX(shot) as max_shot FROM $scene_headings_table $where";
        $result = $this->db->query($sql)->getRow();
        
        return $result ? ($result->max_shot + 1) : 1;
    }
}