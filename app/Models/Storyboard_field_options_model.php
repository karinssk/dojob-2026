<?php

namespace App\Models;

class Storyboard_field_options_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'storyboard_field_options';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $field_options_table = $this->db->prefixTable('storyboard_field_options');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $field_options_table.id=$id";
        }

        $field_type = $this->_get_clean_value($options, "field_type");
        if ($field_type) {
            $where .= " AND $field_options_table.field_type='$field_type'";
        }

        $sql = "SELECT $field_options_table.*
                FROM $field_options_table
                WHERE $field_options_table.deleted=0 $where
                ORDER BY $field_options_table.sort_order ASC, $field_options_table.id ASC";

        return $this->db->query($sql);
    }

    function get_field_options_array($field_type) {
        $options = $this->get_details(array("field_type" => $field_type))->getResult();
        $result = array();
        
        foreach ($options as $option) {
            $result[] = array(
                'value' => $option->option_value,
                'label' => $option->option_label,
                'icon' => $option->option_icon,
                'color' => $option->option_color
            );
        }
        
        return $result;
    }

    function get_valid_values($field_type) {
        $options = $this->get_details(array("field_type" => $field_type))->getResult();
        $values = array();
        
        foreach ($options as $option) {
            $values[] = $option->option_value;
        }
        
        return $values;
    }

    function save_field_options($field_type, $options_data) {
        // First, mark all existing options as deleted
        $this->db->query("UPDATE " . $this->db->prefixTable('storyboard_field_options') . " 
                         SET deleted = 1 
                         WHERE field_type = '$field_type'");

        // Insert new options
        $sort_order = 1;
        foreach ($options_data as $option) {
            if (!empty($option['value']) && !empty($option['label'])) {
                $data = array(
                    'field_type' => $field_type,
                    'option_value' => $option['value'],
                    'option_label' => $option['label'],
                    'option_icon' => $option['icon'] ?: '',
                    'option_color' => $option['color'] ?: '',
                    'sort_order' => $sort_order,
                    'created_by' => $this->login_user_id,
                    'deleted' => 0
                );
                
                $this->ci_save($data);
                $sort_order++;
            }
        }
        
        return true;
    }
}