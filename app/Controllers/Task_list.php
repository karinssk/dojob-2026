<?php

namespace App\Controllers;

class Task_list extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_team_members();
    }

    function index() {
        // Check if project is selected
        $selected_project_id = $this->request->getGet('project_id');
        
        if (!$selected_project_id) {
            // Show project selection page
            return $this->project_selection();
        }
        
        // Validate project access
        if (!$this->can_view_project($selected_project_id)) {
            show_404();
        }
        
        // Load project info
        $project_info = $this->Projects_model->get_one($selected_project_id);
        if (!$project_info->id) {
            show_404();
        }
        
        // Get tasks for the project
        $tasks_options = array(
            "project_id" => $selected_project_id,
            "show_assigned_tasks_only_user_id" => $this->show_assigned_tasks_only_user_id()
        );
        
        $tasks = $this->Tasks_model->get_details($tasks_options)->getResult();
        
        $view_data['project_info'] = $project_info;
        $view_data['project_id'] = $selected_project_id;
        $view_data['tasks'] = $tasks;
        $view_data['page_type'] = "full";
        
        return $this->template->rander("project_management/task_list", $view_data);
    }
    
    function project_selection() {
        // Get all projects user can access
        $projects_options = array();
        
        if (!$this->login_user->is_admin && $this->login_user->user_type == "staff") {
            $projects_options["user_id"] = $this->login_user->id;
        }
        
        $projects = $this->Projects_model->get_details($projects_options)->getResult();
        
        $view_data['projects'] = $projects;
        $view_data['page_type'] = "full";
        
        return $this->template->rander("task_list/project_selection", $view_data);
    }
    
    function set_project() {
        $project_id = $this->request->getPost('project_id');
        
        if (!$project_id || !$this->can_view_project($project_id)) {
            echo json_encode(array("success" => false, "message" => "Invalid project"));
            return;
        }
        
        // Redirect to task list with selected project
        echo json_encode(array(
            "success" => true, 
            "redirect_to" => get_uri("task_list?project_id=" . $project_id)
        ));
    }
    
    protected function can_view_project($project_id) {
        if ($this->login_user->is_admin) {
            return true;
        }
        
        if ($this->login_user->user_type == "staff") {
            $project_member = $this->Project_members_model->get_one_where(array(
                "project_id" => $project_id,
                "user_id" => $this->login_user->id,
                "deleted" => 0
            ));
            
            return $project_member->id ? true : false;
        }
        
        return false;
    }
    
    protected function show_assigned_tasks_only_user_id() {
        if (!$this->login_user->is_admin && get_array_value($this->login_user->permissions, "show_assigned_tasks_only") == "1") {
            return $this->login_user->id;
        }
        return false;
    }
}