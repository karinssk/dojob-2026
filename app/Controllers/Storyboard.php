<?php

namespace App\Controllers;

class Storyboard extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_team_members();
        
        // Force load models
        try {
            $this->Storyboards_model = model("App\Models\Storyboards_model");
            $this->Sub_storyboard_projects_model = model("App\Models\Sub_storyboard_projects_model");
            $this->Scene_headings_model = model("App\Models\Scene_headings_model");
            $this->Project_members_model = model("App\Models\Project_members_model");
            $this->Users_model = model("App\Models\Users_model");
            $this->Storyboard_field_options_model = model("App\Models\Storyboard_field_options_model");
        } catch (Exception $e) {
            error_log("Error loading models: " . $e->getMessage());
            // Continue without models for now
        }
    }

    function index() {
        // Check if project is selected
        $selected_project_id = $this->request->getGet('project_id');
        $selected_sub_project_id = $this->request->getGet('sub_project_id');
        $create_new = $this->request->getGet('create_new');
        
        // Debug logging
        error_log("Storyboard index - project_id: " . ($selected_project_id ?: 'NULL') . ", sub_project_id: " . ($selected_sub_project_id ?: 'NULL'));
        
        if (!$selected_project_id) {
            // Check if user wants to create new project
            if ($create_new === '1') {
                return $this->create_storyboard_project();
            }
            
            // Check if there are any storyboard projects
            $storyboard_projects = $this->get_storyboard_projects();
            
            if (empty($storyboard_projects)) {
                // No storyboard projects exist, show create new project form
                return $this->create_storyboard_project();
            } else {
                // Show project selection page with storyboard projects only
                return $this->project_selection();
            }
        }
        
        // If project is selected but no sub-project, show sub-project selection
        if ($selected_project_id && !$selected_sub_project_id) {
            return $this->sub_project_selection($selected_project_id);
        }
        
        // Validate project access and that it's a storyboard project
        if (!$this->can_view_project($selected_project_id)) {
            show_404();
        }
        
        // Load project info and verify it's a storyboard project
        $project_info = $this->Projects_model->get_one($selected_project_id);
        if (!$project_info->id || $project_info->is_storyboard != 1) {
            show_404();
        }
        
        // Get scene headings and storyboards organized by headings
        $scene_headings = array();
        $storyboards_by_heading = array();
        $storyboards_without_heading = array();
        $statistics = array();
        
        // Try to get scene headings and storyboard data
        try {
            if (isset($this->Scene_headings_model) && isset($this->Storyboards_model)) {
                // Get scene headings for this project/sub-project
                $heading_options = array("project_id" => $selected_project_id);
                if ($selected_sub_project_id) {
                    $heading_options["sub_storyboard_project_id"] = $selected_sub_project_id;
                }
                
                $headings_result = $this->Scene_headings_model->get_details($heading_options);
                if ($headings_result) {
                    $scene_headings = $headings_result->getResult();
                }
                
                // Get all storyboards for this project/sub-project
                $storyboard_options = array("project_id" => $selected_project_id);
                if ($selected_sub_project_id) {
                    $storyboard_options["sub_storyboard_project_id"] = $selected_sub_project_id;
                }
                
                $result = $this->Storyboards_model->get_details($storyboard_options);
                if ($result) {
                    $all_storyboards = $result->getResult();
                    
                    // Organize storyboards by scene heading
                    foreach ($all_storyboards as $storyboard) {
                        // Fix null values in storyboard data
                        $storyboard->content = $storyboard->content ?: '';
                        $storyboard->dialogues = $storyboard->dialogues ?: '';
                        $storyboard->note = $storyboard->note ?: '';
                        $storyboard->shot_size = $storyboard->shot_size ?: '';
                        $storyboard->shot_type = $storyboard->shot_type ?: '';
                        $storyboard->movement = $storyboard->movement ?: '';
                        $storyboard->sound = $storyboard->sound ?: '';
                        $storyboard->equipment = $storyboard->equipment ?: '';
                        $storyboard->lighting = $storyboard->lighting ?: '';
                        $storyboard->raw_footage = $storyboard->raw_footage ?: '';
                        
                        // Format duration
                        if ($storyboard->duration) {
                            $storyboard->duration = $this->formatDuration($storyboard->duration);
                        }
                        
                        // Group by scene heading
                        if ($storyboard->scene_heading_id) {
                            if (!isset($storyboards_by_heading[$storyboard->scene_heading_id])) {
                                $storyboards_by_heading[$storyboard->scene_heading_id] = array();
                            }
                            $storyboards_by_heading[$storyboard->scene_heading_id][] = $storyboard;
                        } else {
                            $storyboards_without_heading[] = $storyboard;
                        }
                    }
                }
                
                // Get statistics
                $stats_result = $this->Storyboards_model->get_storyboard_statistics($selected_project_id, $selected_sub_project_id);
                if ($stats_result) {
                    $statistics = $stats_result->getResult();
                }
            }
        } catch (Exception $e) {
            error_log("Scene headings/Storyboards error: " . $e->getMessage());
        }
        
        // Get sub-project info if sub_project_id is provided
        $sub_project_info = null;
        if ($selected_sub_project_id) {
            try {
                if (isset($this->Sub_storyboard_projects_model)) {
                    // Use get_details to get full sub-project info like in sub_project_selection
                    $sub_project_result = $this->Sub_storyboard_projects_model->get_details(array("id" => $selected_sub_project_id));
                    if ($sub_project_result) {
                        $sub_projects = $sub_project_result->getResult();
                        if (!empty($sub_projects)) {
                            $sub_project_info = $sub_projects[0];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error getting sub-project info: " . $e->getMessage());
            }
        }

        $view_data['project_info'] = $project_info;
        $view_data['project_id'] = $selected_project_id;
        $view_data['sub_project_id'] = $selected_sub_project_id;
        $view_data['sub_project_info'] = $sub_project_info;
        $view_data['scene_headings'] = $scene_headings;
        $view_data['storyboards_by_heading'] = $storyboards_by_heading;
        $view_data['storyboards_without_heading'] = $storyboards_without_heading;
        $view_data['statistics'] = $statistics;
        $view_data['page_type'] = "full";
        
        // Debug logging
        error_log("View data - project_id: " . ($view_data['project_id'] ?: 'NULL') . ", sub_project_id: " . ($view_data['sub_project_id'] ?: 'NULL'));
        
        return $this->template->rander("storyboard/index.php", $view_data);
    }
    
    function project_selection() {
        // Get only storyboard projects user can access
        $storyboard_projects = $this->get_storyboard_projects();
        
        $view_data['projects'] = $storyboard_projects;
        $view_data['page_type'] = "full";
        
        return $this->template->rander("storyboard/project_selection", $view_data);
    }
    
    function set_project() {
        $project_id = $this->request->getPost('project_id');
        
        // Debug logging
        error_log("set_project called with project_id: " . ($project_id ?: 'NULL'));
        
        if (!$project_id) {
            error_log("set_project: No project_id provided");
            echo json_encode(array("success" => false, "message" => "No project ID provided"));
            return;
        }
        
        if (!$this->can_view_project($project_id)) {
            error_log("set_project: Cannot view project $project_id");
            echo json_encode(array("success" => false, "message" => "Cannot access this project"));
            return;
        }
        
        // Verify it's a storyboard project
        try {
            $project_info = $this->Projects_model->get_one($project_id);
            if (!$project_info->id) {
                error_log("set_project: Project $project_id not found");
                echo json_encode(array("success" => false, "message" => "Project not found"));
                return;
            }
            
            if ($project_info->is_storyboard != 1) {
                error_log("set_project: Project $project_id is not a storyboard project (is_storyboard = {$project_info->is_storyboard})");
                echo json_encode(array("success" => false, "message" => "Not a storyboard project"));
                return;
            }
            
            // Success - redirect to storyboard
            $redirect_url = get_uri("storyboard?project_id=" . $project_id);
            error_log("set_project: Success, redirecting to: $redirect_url");
            
            echo json_encode(array(
                "success" => true, 
                "message" => "Project selected successfully",
                "redirect_to" => $redirect_url
            ));
            
        } catch (Exception $e) {
            error_log("set_project: Exception - " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
        }
    }

    function create_storyboard_project() {
        $view_data['page_type'] = "full";
        return $this->template->render("storyboard/create_project", $view_data);
    }
    
    function simple_create() {
        $view_data['page_type'] = "full";
        return $this->template->render("storyboard/simple_create_project", $view_data);
    }
    
    function test_sub_project() {
        echo "<h1>Test Sub-Project Creation</h1>";
        
        $project_id = 133;
        
        echo "<h2>Direct Method Test</h2>";
        echo "<p>Testing create_sub_project method directly...</p>";
        
        try {
            $_POST['project_id'] = $project_id;
            $this->create_sub_project();
        } catch (Exception $e) {
            echo "<p>❌ Error: " . $e->getMessage() . "</p>";
        }
    }

    function test_hierarchical_setup() {
        echo "<h1>Test Hierarchical Storyboard Setup</h1>";
        
        echo "<h2>Database Tables Check</h2>";
        
        // Check if sub_storyboard_projects table exists
        try {
            $db = \Config\Database::connect();
            $query = $db->query("SHOW TABLES LIKE 'rise_sub_storyboard_projects'");
            if ($query->getNumRows() > 0) {
                echo "<p> rise_sub_storyboard_projects table exists</p>";
            } else {
                echo "<p>❌ rise_sub_storyboard_projects table does NOT exist</p>";
                echo "<p>Run: complete_hierarchical_storyboard_setup.sql</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error checking table: " . $e->getMessage() . "</p>";
        }
        
        // Check if storyboards table has sub_storyboard_project_id column
        try {
            $db = \Config\Database::connect();
            $query = $db->query("DESCRIBE rise_storyboards");
            $columns = $query->getResult();
            $has_sub_project_column = false;
            foreach ($columns as $column) {
                if ($column->Field == 'sub_storyboard_project_id') {
                    $has_sub_project_column = true;
                    break;
                }
            }
            
            if ($has_sub_project_column) {
                echo "<p> rise_storyboards has sub_storyboard_project_id column</p>";
            } else {
                echo "<p>❌ rise_storyboards missing sub_storyboard_project_id column</p>";
                echo "<p>Run: complete_hierarchical_storyboard_setup.sql</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error checking column: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>Model Loading Check</h2>";
        if (isset($this->Sub_storyboard_projects_model)) {
            echo "<p> Sub_storyboard_projects_model is loaded</p>";
            
            // Test model method
            try {
                $result = $this->Sub_storyboard_projects_model->get_details(array("rise_story_id" => 133));
                echo "<p> Model get_details() works</p>";
            } catch (Exception $e) {
                echo "<p>❌ Model method error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ Sub_storyboard_projects_model is NOT loaded</p>";
        }
        
        echo "<h2>Test Links</h2>";
        echo "<p><a href='" . get_uri("storyboard?project_id=133") . "'>Go to Project 133</a></p>";
        echo "<p><a href='" . get_uri("storyboard/test_sub_project") . "'>Test Sub-Project Method</a></p>";
        echo "<p><a href='" . get_uri("storyboard/create_sub_project?project_id=133") . "'>Direct Sub-Project Modal</a></p>";
    }

    function test_storyboard_data() {
        echo "<h1>Test Storyboard Data</h1>";
        
        $project_id = 141;
        
        echo "<h2>Saved Storyboard Scenes</h2>";
        try {
            if (isset($this->Storyboards_model)) {
                $storyboard_options = array("project_id" => $project_id);
                $result = $this->Storyboards_model->get_details($storyboard_options);
                if ($result) {
                    $storyboards = $result->getResult();
                    echo "<p>Found " . count($storyboards) . " storyboard scenes:</p>";
                    
                    foreach ($storyboards as $storyboard) {
                        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
                        echo "<h3>Scene ID: {$storyboard->id}</h3>";
                        echo "<p>Shot: {$storyboard->shot}</p>";
                        echo "<p>Content: '" . ($storyboard->content ?: 'NULL') . "'</p>";
                        echo "<p>Dialogues: '" . ($storyboard->dialogues ?: 'NULL') . "'</p>";
                        echo "<p>Note: '" . ($storyboard->note ?: 'NULL') . "'</p>";
                        echo "<p>Story Status: {$storyboard->story_status}</p>";
                        echo "<p>Created: {$storyboard->created_date}</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>❌ No result from get_details()</p>";
                }
            } else {
                echo "<p>❌ Storyboards_model not available</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>Links</h2>";
        echo "<p><a href='" . get_uri("storyboard?project_id=$project_id") . "'>Go to Project $project_id Storyboard</a></p>";
        echo "<p><a href='" . get_uri("storyboard/test_storyboard_save") . "'>Back to Save Test</a></p>";
    }

    function test_storyboard_save() {
        echo "<h1>Test Storyboard Scene Save</h1>";
        
        // Test with project 141 (latest created)
        $project_id = 141;
        
        echo "<h2>Test Project Access</h2>";
        try {
            $project = $this->Projects_model->get_one($project_id);
            echo "<p>Project $project_id: {$project->title}</p>";
            echo "<p>is_storyboard: {$project->is_storyboard}</p>";
            
            if ($this->can_view_project($project_id)) {
                echo "<p> Can access project $project_id</p>";
            } else {
                echo "<p>❌ Cannot access project $project_id</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>Test Storyboard Save Form</h2>";
        echo "<form action='" . get_uri("storyboard/save") . "' method='post'>";
        echo "<input type='hidden' name='project_id' value='$project_id'>";
        echo "<p>Shot: <input type='number' name='shot' value='1' required></p>";
        echo "<p>Shot Size: <select name='shot_size'><option value='Medium Shot'>Medium Shot</option><option value='Close-up'>Close-up</option></select></p>";
        echo "<p>Story Status: <select name='story_status'><option value='Draft'>Draft</option><option value='Editing'>Editing</option></select></p>";
        echo "<p>Content: <textarea name='content'>Test storyboard scene content</textarea></p>";
        echo "<p><input type='submit' value='Save Test Storyboard Scene'></p>";
        echo "</form>";
        
        echo "<h2>Links</h2>";
        echo "<p><a href='" . get_uri("storyboard?project_id=$project_id") . "'>Go to Project $project_id Storyboard</a></p>";
        echo "<p><a href='" . get_uri("storyboard/test_storyboard_data") . "'>View Saved Data</a></p>";
    }

    function debug_post() {
        echo "<h1>Debug POST Data</h1>";
        echo "<h2>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</h2>";
        echo "<h2>All POST Data:</h2>";
        echo "<pre>" . print_r($_POST, true) . "</pre>";
        
        echo "<h2>Raw Input:</h2>";
        echo "<pre>" . htmlspecialchars(file_get_contents('php://input')) . "</pre>";
        
        echo "<h2>CodeIgniter Request:</h2>";
        echo "<p>Title: " . ($this->request->getPost('title') ?: 'NULL/EMPTY') . "</p>";
        echo "<p>Description: " . ($this->request->getPost('description') ?: 'NULL/EMPTY') . "</p>";
        
        echo "<h2>Test Forms:</h2>";
        
        echo "<h3>1. Debug Form (test POST data):</h3>";
        echo "<form action='" . get_uri("storyboard/debug_post") . "' method='post'>";
        echo "<input type='text' name='title' value='Debug Test Title' placeholder='Enter title'>";
        echo "<textarea name='description' placeholder='Enter description'>Debug Test Description</textarea>";
        echo "<input type='submit' value='Test POST Data'>";
        echo "</form>";
        
        echo "<h3>2. Create Project Form (actual creation):</h3>";
        echo "<form action='" . get_uri("storyboard/save_storyboard_project") . "' method='post'>";
        echo "<input type='text' name='title' value='Actual Test Project' placeholder='Enter title' required>";
        echo "<textarea name='description' placeholder='Enter description'>Actual test project description</textarea>";
        echo "<input type='submit' value='Create Actual Project'>";
        echo "</form>";
    }

    function save_storyboard_project() {
        $title = $this->request->getPost('title');
        $description = $this->request->getPost('description');
        
        if (!$title || trim($title) === '') {
            echo json_encode(array("success" => false, "message" => "Project title is required"));
            return;
        }

        $data = array(
            "title" => $title,
            "description" => $description,
            "project_type" => "internal_project",
            "client_id" => 0,
            "created_date" => date('Y-m-d'),
            "created_by" => $this->login_user->id,
            "status" => "open",
            "status_id" => 1,
            "is_storyboard" => 1
        );

        try {
            $save_id = $this->Projects_model->ci_save($data);
            
            if ($save_id) {
                // Add current user as project member
                $member_data = array(
                    "project_id" => $save_id,
                    "user_id" => $this->login_user->id,
                    "is_leader" => 1
                );
                $this->Project_members_model->ci_save($member_data);
                
                echo json_encode(array(
                    "success" => true, 
                    "message" => "Storyboard project created successfully",
                    "redirect_to" => get_uri("storyboard?project_id=" . $save_id)
                ));
            } else {
                echo json_encode(array("success" => false, "message" => "Failed to create project"));
            }
        } catch (Exception $e) {
            echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
        }
    }

    function test_save() {
        echo "<h1>Test Save Storyboard Project</h1>";
        
        // Test if we can access the method
        echo "<p>Testing save_storyboard_project method...</p>";
        
        // Simulate POST data
        $_POST['title'] = 'Test Storyboard Project';
        $_POST['description'] = 'Test description';
        
        echo "<p>Calling save_storyboard_project()...</p>";
        
        // Call the method
        $this->save_storyboard_project();
    }
    
    function test_urls() {
        echo "<h1>Test URLs</h1>";
        echo "<p>Current URL: " . current_url() . "</p>";
        echo "<p>Base URL: " . base_url() . "</p>";
        echo "<p>get_uri('storyboard/save_storyboard_project'): " . get_uri("storyboard/save_storyboard_project") . "</p>";
        echo "<p>get_uri('storyboard/save'): " . get_uri("storyboard/save") . "</p>";
        
        echo "<h2>Test Direct URLs</h2>";
        echo "<p><a href='" . get_uri("storyboard/save_storyboard_project") . "' target='_blank'>Direct link to save_storyboard_project</a></p>";
        echo "<p><a href='" . get_uri("storyboard/save") . "' target='_blank'>Direct link to save (wrong method)</a></p>";
        
        echo "<h2>Test Links</h2>";
        echo "<p><a href='" . get_uri("storyboard") . "'>Go to /storyboard</a></p>";
        echo "<p><a href='" . get_uri("storyboard?project_id=136") . "'>Go to /storyboard?project_id=136</a> (created project)</p>";
        
        echo "<h2>Test Storyboard Projects</h2>";
        try {
            $projects = $this->get_storyboard_projects();
            echo "<p>Found " . count($projects) . " storyboard projects:</p>";
            echo "<ul>";
            foreach ($projects as $project) {
                echo "<li>ID: {$project->id} - {$project->title} - is_storyboard: {$project->is_storyboard}</li>";
            }
            echo "</ul>";
        } catch (Exception $e) {
            echo "<p>Error getting storyboard projects: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>Test Form Actions</h2>";
        echo "<h3>Correct Form (should work):</h3>";
        echo "<form action='" . get_uri("storyboard/save_storyboard_project") . "' method='post'>";
        echo "<input type='text' name='title' value='Test Project Correct' required />";
        echo "<input type='text' name='description' value='Test description' />";
        echo "<input type='submit' value='Submit to save_storyboard_project' />";
        echo "</form>";
        
        echo "<h3>Wrong Form (will give error):</h3>";
        echo "<form action='" . get_uri("storyboard/save") . "' method='post'>";
        echo "<input type='text' name='title' value='Test Project Wrong' />";
        echo "<input type='submit' value='Submit to save (wrong)' />";
        echo "</form>";
    }

    function test_project() {
        echo "<h1>Test Latest Created Projects</h1>";
        
        // Test the latest projects (136, 141)
        $project_ids = [136, 141];
        
        foreach ($project_ids as $project_id) {
            echo "<h2>Project $project_id:</h2>";
            try {
                $project = $this->Projects_model->get_one($project_id);
                if ($project->id) {
                    echo "<p> Project $project_id exists</p>";
                    echo "<p>Title: {$project->title}</p>";
                    echo "<p>Description: " . ($project->description ?: 'NULL') . "</p>";
                    echo "<p>is_storyboard: {$project->is_storyboard}</p>";
                    echo "<p>Status: {$project->status}</p>";
                    echo "<p>Created by: {$project->created_by}</p>";
                    
                    if ($project->is_storyboard == 1) {
                        echo "<p> Project is correctly marked as storyboard project</p>";
                    } else {
                        echo "<p>❌ Project is NOT marked as storyboard project</p>";
                    }
                    
                    echo "<p><a href='" . get_uri("storyboard?project_id=$project_id") . "'>Access Project $project_id Storyboard</a></p>";
                } else {
                    echo "<p>❌ Project $project_id not found</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Error getting project $project_id: " . $e->getMessage() . "</p>";
            }
            echo "<hr>";
        }
        
        echo "<h2>Test Storyboard Projects List</h2>";
        try {
            $storyboard_projects = $this->get_storyboard_projects();
            echo "<p>Found " . count($storyboard_projects) . " storyboard projects:</p>";
            foreach ($storyboard_projects as $project) {
                echo "<p>- ID: {$project->id}, Title: {$project->title}, is_storyboard: {$project->is_storyboard}</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error getting storyboard projects: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>Quick Links</h2>";
        echo "<p><a href='" . get_uri("storyboard") . "'>Go to main storyboard page</a></p>";
        echo "<p><a href='" . get_uri("storyboard?project_id=141") . "'>Go to Project 141 (latest)</a></p>";
    }

    function test_model() {
        echo "<h1>Storyboard Model Test</h1>";
        
        if (isset($this->Storyboards_model)) {
            echo "<p> Storyboards_model is loaded</p>";
            
            try {
                $result = $this->Storyboards_model->get_details(array());
                echo "<p> get_details() method works</p>";
                echo "<p>Found " . count($result->getResult()) . " storyboard records</p>";
            } catch (Exception $e) {
                echo "<p>❌ Error calling get_details(): " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ Storyboards_model is not loaded</p>";
        }
        
        if (isset($this->Projects_model)) {
            echo "<p> Projects_model is loaded</p>";
        } else {
            echo "<p>❌ Projects_model is not loaded</p>";
        }
    }

    function sub_project_selection($project_id) {
        // Validate project access
        if (!$this->can_view_project($project_id)) {
            show_404();
        }
        
        // Load project info
        $project_info = $this->Projects_model->get_one($project_id);
        if (!$project_info->id || $project_info->is_storyboard != 1) {
            show_404();
        }
        
        // Get sub-projects for this main project
        $sub_projects_result = $this->Sub_storyboard_projects_model->get_sub_project_with_scene_count($project_id);
        $sub_projects = $sub_projects_result->getResult();
        
        // Add profile images for assigned users
        foreach ($sub_projects as $sub_project) {
            if ($sub_project->assigned_to) {
                try {
                    $assigned_user = $this->Users_model->get_one($sub_project->assigned_to);
                    if ($assigned_user && $assigned_user->id) {
                        // Get profile image
                        $profile_image = '';
                        if ($assigned_user->image) {
                            $profile_image = get_avatar($assigned_user->image);
                            if (empty($profile_image) || strpos($profile_image, 'avatar.jpg') !== false) {
                                $profile_image = base_url('files/profile_images/' . $assigned_user->image);
                            }
                        }
                        if (empty($profile_image)) {
                            $profile_image = base_url('assets/images/avatar.jpg');
                        }
                        
                        $sub_project->assigned_user_image = $profile_image;
                        $sub_project->assigned_user_email = $assigned_user->email;
                        $sub_project->assigned_user_job_title = $assigned_user->job_title;
                    }
                } catch (Exception $e) {
                    error_log("Error getting assigned user data: " . $e->getMessage());
                }
            }
        }
        
        $view_data['project_info'] = $project_info;
        $view_data['project_id'] = $project_id;
        $view_data['sub_projects'] = $sub_projects;
        $view_data['page_type'] = "full";
        
        return $this->template->rander("storyboard/sub_project_selection", $view_data);
    }

    function create_sub_project() {
        try {
            $project_id = $this->request->getPost('project_id') ?: $this->request->getGet('project_id');
            $sub_project_id = $this->request->getPost('id');
            
            error_log("create_sub_project called with project_id: $project_id, sub_project_id: $sub_project_id");
            
            if (!$project_id) {
                error_log("create_sub_project: No project_id provided");
                echo "Error: No project ID provided";
                return;
            }
            
            if (!$this->can_view_project($project_id)) {
                error_log("create_sub_project: Cannot view project $project_id");
                echo "Error: Cannot access project";
                return;
            }

            $view_data['project_id'] = $project_id;
            $view_data['login_user'] = $this->login_user;
            
            // Get team members for this project
            $team_members = array();
            try {
                if (isset($this->Project_members_model) && isset($this->Users_model)) {
                    $project_members = $this->Project_members_model->get_details(array("project_id" => $project_id))->getResult();
                    foreach ($project_members as $member) {
                        $user_info = $this->Users_model->get_one($member->user_id);
                        if ($user_info && $user_info->id) {
                            // Get profile image - try multiple approaches
                            $profile_image = '';
                            if ($user_info->image) {
                                // Try get_avatar function first
                                $profile_image = get_avatar($user_info->image);
                                
                                // If get_avatar returns empty or default, try manual construction
                                if (empty($profile_image) || strpos($profile_image, 'avatar.jpg') !== false) {
                                    $profile_image = base_url('files/profile_images/' . $user_info->image);
                                }
                            }
                            
                            // Fallback to default avatar
                            if (empty($profile_image)) {
                                $profile_image = base_url('assets/images/avatar.jpg');
                            }
                            
                            $team_members[] = array(
                                'id' => $user_info->id,
                                'name' => trim($user_info->first_name . ' ' . $user_info->last_name),
                                'email' => $user_info->email ?: '',
                                'image' => $profile_image,
                                'job_title' => $user_info->job_title ?: ''
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error getting team members: " . $e->getMessage());
            }
            
            // Get all active users as fallback
            $all_users = array();
            try {
                if (isset($this->Users_model)) {
                    $users = $this->Users_model->get_details(array("status" => "active", "user_type" => "staff"))->getResult();
                    foreach ($users as $user) {
                        if ($user && $user->id) {
                            // Get profile image - try multiple approaches
                            $profile_image = '';
                            if ($user->image) {
                                // Try get_avatar function first
                                $profile_image = get_avatar($user->image);
                                
                                // If get_avatar returns empty or default, try manual construction
                                if (empty($profile_image) || strpos($profile_image, 'avatar.jpg') !== false) {
                                    $profile_image = base_url('files/profile_images/' . $user->image);
                                }
                            }
                            
                            // Fallback to default avatar
                            if (empty($profile_image)) {
                                $profile_image = base_url('assets/images/avatar.jpg');
                            }
                            
                            $all_users[] = array(
                                'id' => $user->id,
                                'name' => trim($user->first_name . ' ' . $user->last_name),
                                'email' => $user->email ?: '',
                                'image' => $profile_image,
                                'job_title' => $user->job_title ?: ''
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error getting all users: " . $e->getMessage());
            }
            
            $view_data['team_members'] = $team_members;
            $view_data['all_users'] = $all_users;
            
            // Debug logging
            error_log("Team members data: " . print_r($team_members, true));
            error_log("All users data: " . print_r(array_slice($all_users, 0, 2), true));
            
            // If editing existing sub-project
            if ($sub_project_id) {
                try {
                    if (isset($this->Sub_storyboard_projects_model)) {
                        $sub_project_info = $this->Sub_storyboard_projects_model->get_one($sub_project_id);
                        if ($sub_project_info->id && $sub_project_info->rise_story_id == $project_id) {
                            $view_data['model_info'] = $sub_project_info;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error getting sub-project: " . $e->getMessage());
                }
            }
            
            error_log("create_sub_project: About to render template");
            return $this->template->view("storyboard/sub_project_modal_form", $view_data);
            
        } catch (Exception $e) {
            error_log("create_sub_project: Exception - " . $e->getMessage());
            echo "Error: " . $e->getMessage();
            return;
        }
    }

    function save_sub_project() {
        try {
            $sub_project_id = $this->request->getPost('id');
            $project_id = $this->request->getPost('project_id');
            
            // Debug logging
            error_log("save_sub_project called with project_id: $project_id, sub_project_id: $sub_project_id");
            error_log("POST data: " . print_r($_POST, true));
            
            if (!$project_id || !$this->can_view_project($project_id)) {
                echo json_encode(array("success" => false, "message" => "Invalid project"));
                return;
            }

            $title = $this->request->getPost('title');
            if (!$title || trim($title) === '') {
                echo json_encode(array("success" => false, "message" => "Sub-project title is required"));
                return;
            }

            // Handle collaborators - ensure it's a string
            $collaborators = $this->request->getPost('collaborators');
            if (is_array($collaborators)) {
                $collaborators = implode(',', array_filter($collaborators));
            }
            
            // Handle assigned_to - ensure it's a valid integer or null
            $assigned_to = $this->request->getPost('assigned_to');
            if ($assigned_to && !is_numeric($assigned_to)) {
                $assigned_to = null;
            }

            $data = array(
                "rise_story_id" => $project_id,
                "title" => $title,
                "description" => $this->request->getPost('description') ?: '',
                "collaborators" => $collaborators ?: '',
                "assigned_to" => $assigned_to ?: null,
                "status" => $this->request->getPost('status') ?: 'Draft'
            );
            
            error_log("Data to save: " . print_r($data, true));

            // Check if model is available
            if (!isset($this->Sub_storyboard_projects_model)) {
                echo json_encode(array("success" => false, "message" => "Sub-project model not available"));
                return;
            }

            if ($sub_project_id) {
                // Update existing sub-project
                error_log("Updating sub-project ID: $sub_project_id");
                
                // First check if the sub-project exists
                $existing = $this->Sub_storyboard_projects_model->get_one($sub_project_id);
                if (!$existing || !$existing->id) {
                    echo json_encode(array("success" => false, "message" => "Sub-project not found"));
                    return;
                }
                
                error_log("Existing sub-project: " . print_r($existing, true));
                
                // Verify ownership/access
                if ($existing->rise_story_id != $project_id) {
                    echo json_encode(array("success" => false, "message" => "Sub-project does not belong to this project"));
                    return;
                }
                
                $save_id = $this->Sub_storyboard_projects_model->ci_save($data, $sub_project_id);
                error_log("Update result: " . ($save_id ? "Success (ID: $save_id)" : "Failed"));
            } else {
                // Create new sub-project
                error_log("Creating new sub-project");
                $data["created_by"] = $this->login_user->id;
                $data["sort_order"] = 0;
                $data["deleted"] = 0;
                $save_id = $this->Sub_storyboard_projects_model->ci_save($data);
                error_log("Create result: " . ($save_id ? "Success (ID: $save_id)" : "Failed"));
            }

            if ($save_id) {
                echo json_encode(array(
                    "success" => true, 
                    "message" => "Sub-project saved successfully",
                    "id" => $save_id
                ));
            } else {
                echo json_encode(array("success" => false, "message" => "Failed to save sub-project"));
            }
            
        } catch (Exception $e) {
            error_log("save_sub_project exception: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
        }
    }

    function debug_sub_project_table() {
        echo "<h1>Debug Sub-Project Table</h1>";
        
        try {
            $db = \Config\Database::connect();
            
            // Check table structure
            echo "<h2>Table Structure:</h2>";
            $query = $db->query("DESCRIBE rise_sub_storyboard_projects");
            $columns = $query->getResult();
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column->Field}</td>";
                echo "<td>{$column->Type}</td>";
                echo "<td>{$column->Null}</td>";
                echo "<td>{$column->Key}</td>";
                echo "<td>{$column->Default}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check existing records
            echo "<h2>Existing Records:</h2>";
            $query = $db->query("SELECT * FROM rise_sub_storyboard_projects WHERE deleted = 0 LIMIT 5");
            $records = $query->getResult();
            if ($records) {
                echo "<p>Found " . count($records) . " records:</p>";
                foreach ($records as $record) {
                    echo "<p>ID: {$record->id}, Title: {$record->title}, Project: {$record->rise_story_id}, Status: {$record->status}</p>";
                }
            } else {
                echo "<p>No records found</p>";
            }
            
            // Test update operation
            echo "<h2>Test Update Operation:</h2>";
            if ($records && count($records) > 0) {
                $test_record = $records[0];
                $test_data = array(
                    "title" => $test_record->title . " (test update)",
                    "description" => "Test update description",
                    "status" => $test_record->status
                );
                
                echo "<p>Testing update on ID: {$test_record->id}</p>";
                echo "<p>Test data: " . print_r($test_data, true) . "</p>";
                
                try {
                    $result = $this->Sub_storyboard_projects_model->ci_save($test_data, $test_record->id);
                    echo "<p>Update result: " . ($result ? " Success (ID: $result)" : "❌ Failed") . "</p>";
                } catch (Exception $e) {
                    echo "<p>❌ Update error: " . $e->getMessage() . "</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Error: " . $e->getMessage() . "</p>";
        }
    }

    function test_sub_project_update() {
        echo "<h1>Test Sub-Project Update</h1>";
        
        // Get the first sub-project to test with
        try {
            $sub_projects = $this->Sub_storyboard_projects_model->get_details()->getResult();
            if (empty($sub_projects)) {
                echo "<p>No sub-projects found to test with</p>";
                return;
            }
            
            $test_sub_project = $sub_projects[0];
            echo "<h2>Testing with Sub-Project ID: {$test_sub_project->id}</h2>";
            echo "<p>Current title: {$test_sub_project->title}</p>";
            echo "<p>Current status: {$test_sub_project->status}</p>";
            
            // Test form
            echo "<h3>Test Update Form:</h3>";
            echo "<form method='post' action='" . get_uri("storyboard/save_sub_project") . "'>";
            echo "<input type='hidden' name='id' value='{$test_sub_project->id}'>";
            echo "<input type='hidden' name='project_id' value='{$test_sub_project->rise_story_id}'>";
            echo "<p>Title: <input type='text' name='title' value='{$test_sub_project->title} (updated)' required></p>";
            echo "<p>Description: <textarea name='description'>{$test_sub_project->description} (updated)</textarea></p>";
            echo "<p>Status: <select name='status'>";
            echo "<option value='Draft'" . ($test_sub_project->status == 'Draft' ? ' selected' : '') . ">Draft</option>";
            echo "<option value='In Progress'" . ($test_sub_project->status == 'In Progress' ? ' selected' : '') . ">In Progress</option>";
            echo "<option value='Review'" . ($test_sub_project->status == 'Review' ? ' selected' : '') . ">Review</option>";
            echo "</select></p>";
            echo "<p>Assigned To: <input type='number' name='assigned_to' value='{$test_sub_project->assigned_to}'></p>";
            echo "<p>Collaborators: <input type='text' name='collaborators' value='{$test_sub_project->collaborators}'></p>";
            echo "<p><input type='submit' value='Test Update'></p>";
            echo "</form>";
            
        } catch (Exception $e) {
            echo "<p>❌ Error: " . $e->getMessage() . "</p>";
        }
    }

    function debug_user_images() {
        echo "<h1>Debug User Images</h1>";
        
        try {
            if (isset($this->Users_model)) {
                $users = $this->Users_model->get_details(array("status" => "active"))->getResult();
                echo "<h2>User Image Data:</h2>";
                
                foreach (array_slice($users, 0, 5) as $user) {
                    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
                    echo "<h3>{$user->first_name} {$user->last_name} (ID: {$user->id})</h3>";
                    echo "<p>Raw image field: '" . ($user->image ?: 'NULL/EMPTY') . "'</p>";
                    
                    $avatar_url = get_avatar($user->image);
                    echo "<p>get_avatar() result: '" . $avatar_url . "'</p>";
                    echo "<p>Image preview: <img src='{$avatar_url}' style='width: 50px; height: 50px; border-radius: 50%;' onerror='this.style.display=\"none\"'></p>";
                    
                    // Test different avatar formats
                    if ($user->image) {
                        $manual_url = base_url('files/profile_images/' . $user->image);
                        echo "<p>Manual URL: <img src='{$manual_url}' style='width: 50px; height: 50px; border-radius: 50%;' onerror='this.style.display=\"none\"'></p>";
                    }
                    
                    echo "</div>";
                }
            }
        } catch (Exception $e) {
            echo "<p>❌ Error: " . $e->getMessage() . "</p>";
        }
    }

    function delete_sub_project() {
        $sub_project_id = $this->request->getPost('id');
        
        if (!$sub_project_id) {
            echo json_encode(array("success" => false, "message" => "No sub-project ID provided"));
            return;
        }
        
        try {
            // Check if model is available
            if (!isset($this->Sub_storyboard_projects_model)) {
                echo json_encode(array("success" => false, "message" => "Sub-project model not available"));
                return;
            }
            
            // Get sub-project info to verify access
            $sub_project_info = $this->Sub_storyboard_projects_model->get_one($sub_project_id);
            if (!$sub_project_info || !$sub_project_info->id) {
                echo json_encode(array("success" => false, "message" => "Sub-project not found"));
                return;
            }
            
            // Check if user can access the parent project
            if (!$this->can_view_project($sub_project_info->rise_story_id)) {
                echo json_encode(array("success" => false, "message" => "Access denied"));
                return;
            }
            
            // Check if there are any storyboard scenes associated with this sub-project
            $scene_count = 0;
            if (isset($this->Storyboards_model)) {
                try {
                    $scenes = $this->Storyboards_model->get_details(array("sub_storyboard_project_id" => $sub_project_id));
                    if ($scenes) {
                        $scene_count = count($scenes->getResult());
                    }
                } catch (Exception $e) {
                    error_log("Error checking scenes: " . $e->getMessage());
                }
            }
            
            // Soft delete the sub-project (set deleted = 1)
            $delete_data = array("deleted" => 1);
            $result = $this->Sub_storyboard_projects_model->ci_save($delete_data, $sub_project_id);
            
            if ($result) {
                // Also soft delete associated storyboard scenes
                if ($scene_count > 0 && isset($this->Storyboards_model)) {
                    try {
                        $scenes = $this->Storyboards_model->get_details(array("sub_storyboard_project_id" => $sub_project_id));
                        if ($scenes) {
                            foreach ($scenes->getResult() as $scene) {
                                $scene_delete_data = array("deleted" => 1);
                                $this->Storyboards_model->ci_save($scene_delete_data, $scene->id);
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error deleting scenes: " . $e->getMessage());
                    }
                }
                
                $message = "Sub-project '{$sub_project_info->title}' deleted successfully";
                if ($scene_count > 0) {
                    $message .= " (including {$scene_count} associated scenes)";
                }
                
                echo json_encode(array(
                    "success" => true, 
                    "message" => $message
                ));
            } else {
                echo json_encode(array("success" => false, "message" => "Failed to delete sub-project"));
            }
            
        } catch (Exception $e) {
            error_log("delete_sub_project exception: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
        }
    }

    function scene_heading_modal_form() {
        $heading_id = $this->request->getPost('id');
        $project_id = $this->request->getPost('project_id');
        $sub_project_id = $this->request->getPost('sub_project_id');
        
        if (!$project_id || !$this->can_view_project($project_id)) {
            echo "Error: Cannot access project";
            return;
        }

        $view_data['project_id'] = $project_id;
        $view_data['sub_project_id'] = $sub_project_id;
        $view_data['login_user'] = $this->login_user;
        
        // If editing existing scene heading
        if ($heading_id) {
            try {
                if (isset($this->Scene_headings_model)) {
                    $heading_info = $this->Scene_headings_model->get_one($heading_id);
                    if ($heading_info->id && $heading_info->project_id == $project_id) {
                        $view_data['model_info'] = $heading_info;
                    }
                }
            } catch (Exception $e) {
                error_log("Error getting scene heading: " . $e->getMessage());
            }
        }
        
        return $this->template->view("storyboard/scene_heading_modal_form", $view_data);
    }

    function save_scene_heading() {
        try {
            $heading_id = $this->request->getPost('id');
            $project_id = $this->request->getPost('project_id');
            $sub_project_id = $this->request->getPost('sub_project_id');
            
            if (!$project_id || !$this->can_view_project($project_id)) {
                echo json_encode(array("success" => false, "message" => "Invalid project"));
                return;
            }

            $header = $this->request->getPost('header');
            if (!$header || trim($header) === '') {
                echo json_encode(array("success" => false, "message" => "Scene heading is required"));
                return;
            }

            // Get next shot number if not provided
            $shot = $this->request->getPost('shot');
            if (!$shot && isset($this->Scene_headings_model)) {
                $shot = $this->Scene_headings_model->get_next_shot_number($project_id, $sub_project_id);
            }

            $data = array(
                "project_id" => $project_id,
                "sub_storyboard_project_id" => $sub_project_id ?: null,
                "header" => $header,
                "shot" => $shot ?: 1,
                "description" => $this->request->getPost('description') ?: '',
                "duration" => $this->request->getPost('duration') ?: null
            );

            if (!isset($this->Scene_headings_model)) {
                echo json_encode(array("success" => false, "message" => "Scene headings model not available"));
                return;
            }

            if ($heading_id) {
                // Update existing
                $save_id = $this->Scene_headings_model->ci_save($data, $heading_id);
            } else {
                // Create new
                $data["created_by"] = $this->login_user->id;
                $data["sort_order"] = 0;
                $data["deleted"] = 0;
                $save_id = $this->Scene_headings_model->ci_save($data);
            }

            if ($save_id) {
                echo json_encode(array(
                    "success" => true, 
                    "message" => "Scene heading saved successfully",
                    "id" => $save_id
                ));
            } else {
                echo json_encode(array("success" => false, "message" => "Failed to save scene heading"));
            }
            
        } catch (Exception $e) {
            error_log("save_scene_heading exception: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
        }
    }

    function delete_scene_heading() {
        $heading_id = $this->request->getPost('id');
        
        if (!$heading_id) {
            echo json_encode(array("success" => false, "message" => "No scene heading ID provided"));
            return;
        }
        
        try {
            if (!isset($this->Scene_headings_model)) {
                echo json_encode(array("success" => false, "message" => "Scene headings model not available"));
                return;
            }
            
            // Get heading info to verify access
            $heading_info = $this->Scene_headings_model->get_one($heading_id);
            if (!$heading_info || !$heading_info->id) {
                echo json_encode(array("success" => false, "message" => "Scene heading not found"));
                return;
            }
            
            // Check project access
            if (!$this->can_view_project($heading_info->project_id)) {
                echo json_encode(array("success" => false, "message" => "Access denied"));
                return;
            }
            
            // Soft delete the scene heading
            $delete_data = array("deleted" => 1);
            $result = $this->Scene_headings_model->ci_save($delete_data, $heading_id);
            
            if ($result) {
                // Also remove scene_heading_id from associated storyboards
                if (isset($this->Storyboards_model)) {
                    try {
                        $scenes = $this->Storyboards_model->get_details(array("scene_heading_id" => $heading_id));
                        if ($scenes) {
                            foreach ($scenes->getResult() as $scene) {
                                $scene_update_data = array("scene_heading_id" => null);
                                $this->Storyboards_model->ci_save($scene_update_data, $scene->id);
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error updating scenes: " . $e->getMessage());
                    }
                }
                
                echo json_encode(array(
                    "success" => true, 
                    "message" => "Scene heading deleted successfully"
                ));
            } else {
                echo json_encode(array("success" => false, "message" => "Failed to delete scene heading"));
            }
            
        } catch (Exception $e) {
            error_log("delete_scene_heading exception: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
        }
    }

    function debug_models() {
        echo "<h1>Debug Models</h1>";
        
        echo "<h2>Model Status:</h2>";
        echo "<p>Storyboards_model: " . (isset($this->Storyboards_model) ? " Loaded" : "❌ Not loaded") . "</p>";
        echo "<p>Sub_storyboard_projects_model: " . (isset($this->Sub_storyboard_projects_model) ? " Loaded" : "❌ Not loaded") . "</p>";
        echo "<p>Project_members_model: " . (isset($this->Project_members_model) ? " Loaded" : "❌ Not loaded") . "</p>";
        echo "<p>Users_model: " . (isset($this->Users_model) ? " Loaded" : "❌ Not loaded") . "</p>";
        
        echo "<h2>Test User Data:</h2>";
        try {
            if (isset($this->Users_model)) {
                $users = $this->Users_model->get_details(array("status" => "active"))->getResult();
                echo "<p>Found " . count($users) . " active users</p>";
                foreach (array_slice($users, 0, 3) as $user) {
                    echo "<p>- ID: {$user->id}, Name: {$user->first_name} {$user->last_name}, Email: {$user->email}</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p>❌ Error getting users: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>Test Project Members:</h2>";
        try {
            if (isset($this->Project_members_model)) {
                $members = $this->Project_members_model->get_details(array("project_id" => 133))->getResult();
                echo "<p>Found " . count($members) . " project members for project 133</p>";
                foreach ($members as $member) {
                    echo "<p>- User ID: {$member->user_id}, Project ID: {$member->project_id}</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p>❌ Error getting project members: " . $e->getMessage() . "</p>";
        }
    }

    private function get_storyboard_projects() {
        $projects_options = array(
            "is_storyboard" => 1
        );
        
        if (!$this->login_user->is_admin && $this->login_user->user_type == "staff") {
            $projects_options["user_id"] = $this->login_user->id;
        }
        
        return $this->Projects_model->get_details($projects_options)->getResult();
    }

    function modal_form() {
        $storyboard_id = $this->request->getPost('id');
        $project_id = $this->request->getPost('project_id');
        $sub_project_id = $this->request->getPost('sub_project_id');
        
        if (!$project_id || !$this->can_view_project($project_id)) {
            show_404();
        }

        $view_data['project_id'] = $project_id;
        $view_data['sub_project_id'] = $sub_project_id;
        
        if ($storyboard_id) {
            // For editing existing storyboard
            try {
                if (isset($this->Storyboards_model)) {
                    $storyboard_info = $this->Storyboards_model->get_one($storyboard_id);
                    if (!$storyboard_info->id || $storyboard_info->project_id != $project_id) {
                        show_404();
                    }
                    
                    // Format duration for display
                    if ($storyboard_info->duration) {
                        $storyboard_info->duration = $this->formatDuration($storyboard_info->duration);
                    }
                    
                    $view_data['model_info'] = $storyboard_info;
                } else {
                    // Model not available, create empty object
                    $view_data['model_info'] = new \stdClass();
                }
            } catch (Exception $e) {
                error_log("Error getting storyboard: " . $e->getMessage());
                $view_data['model_info'] = new \stdClass();
            }
        } else {
            // For new storyboard - start with shot number 1
            $view_data['next_shot_number'] = 1;
            
            // Try to get actual next shot number if model works
            try {
                if (isset($this->Storyboards_model)) {
                    $max_shot = $this->Storyboards_model->get_max_shot_number($project_id, $sub_project_id);
                    $view_data['next_shot_number'] = $max_shot + 1;
                }
            } catch (Exception $e) {
                error_log("Error getting max shot number: " . $e->getMessage());
            }
        }

        // Get scene headings for this project/sub-project
        $scene_headings = array();
        try {
            if (isset($this->Scene_headings_model)) {
                $heading_options = array("project_id" => $project_id);
                if ($sub_project_id) {
                    $heading_options["sub_storyboard_project_id"] = $sub_project_id;
                }
                
                $headings_result = $this->Scene_headings_model->get_details($heading_options);
                if ($headings_result) {
                    $scene_headings = $headings_result->getResult();
                }
            }
        } catch (Exception $e) {
            error_log("Error getting scene headings: " . $e->getMessage());
        }
        
        $view_data['scene_headings'] = $scene_headings;

        return $this->template->view("storyboard/modal_form", $view_data);
    }

    function save() {
        // Debug: This method is for saving storyboard scenes, not projects
        error_log("WARNING: save() method called - this is for storyboard scenes, not project creation");
        
        $storyboard_id = $this->request->getPost('id');
        $project_id = $this->request->getPost('project_id');
        
        error_log("save() method - storyboard_id: " . $storyboard_id . ", project_id: " . $project_id);
        
        if (!$project_id) {
            error_log("save() method - No project_id provided");
            echo json_encode(array("success" => false, "message" => "No project ID provided"));
            return;
        }
        
        // Check if project exists and is a storyboard project
        try {
            $project_info = $this->Projects_model->get_one($project_id);
            if (!$project_info->id) {
                error_log("save() method - Project $project_id not found");
                echo json_encode(array("success" => false, "message" => "Project not found"));
                return;
            }
            
            if ($project_info->is_storyboard != 1) {
                error_log("save() method - Project $project_id is not a storyboard project");
                echo json_encode(array("success" => false, "message" => "Not a storyboard project"));
                return;
            }
        } catch (Exception $e) {
            error_log("save() method - Error checking project: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error checking project"));
            return;
        }
        
        if (!$this->can_view_project($project_id)) {
            error_log("save() method - Cannot access project $project_id");
            echo json_encode(array("success" => false, "message" => "Cannot access project $project_id"));
            return;
        }

        // Handle raw footage upload with error handling
        $raw_footage_data = null;
        try {
            $raw_footage_data = $this->handle_raw_footage_upload($storyboard_id);
            error_log("Raw footage upload completed successfully");
        } catch (Exception $e) {
            error_log("Error handling raw footage upload: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error uploading footage files: " . $e->getMessage()));
            return;
        }

        // Get sub_project_id from POST data
        $sub_project_id = $this->request->getPost('sub_project_id');
        
        $data = array(
            "project_id" => $project_id,
            "sub_storyboard_project_id" => $sub_project_id,
            "scene_heading_id" => $this->request->getPost('scene_heading_id') ?: null,
            "shot" => $this->request->getPost('shot') ?: 1,
            "shot_size" => $this->request->getPost('shot_size'),
            "shot_type" => $this->request->getPost('shot_type'),
            "movement" => $this->request->getPost('movement'),
            "duration" => $this->request->getPost('duration'),
            "content" => $this->request->getPost('content'),
            "dialogues" => $this->request->getPost('dialogues'),
            "sound" => $this->request->getPost('sound'),
            "equipment" => $this->request->getPost('equipment'),
            "framerate" => $this->request->getPost('framerate'),
            "lighting" => $this->request->getPost('lighting'),
            "note" => $this->request->getPost('note'),
            "raw_footage" => $raw_footage_data,
            "story_status" => $this->request->getPost('story_status') ?: 'Draft'
        );

        // Handle frame upload (including edited images)
        $frame_file = $this->request->getFile('frame_file');
        if ($frame_file && $frame_file->isValid() && !$frame_file->hasMoved()) {
            // Create upload directory if it doesn't exist
            $upload_path = FCPATH . 'files/storyboard_frames/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            // Generate unique filename
            $file_name = uniqid() . '_' . $frame_file->getName();
            
            // Move uploaded file
            if ($frame_file->move($upload_path, $file_name)) {
                // Get file info for serialization
                $file_path = $upload_path . $file_name;
                $file_size = filesize($file_path);
                $mime_type = mime_content_type($file_path);
                
                // Serialize frame data with additional metadata
                $frame_data = array(
                    'file_name' => $file_name,
                    'original_name' => $frame_file->getClientName(),
                    'file_size' => $file_size,
                    'mime_type' => $mime_type,
                    'uploaded_at' => date('Y-m-d H:i:s'),
                    'is_edited' => $this->request->getPost('is_edited_image') ? true : false
                );
                $data["frame"] = serialize($frame_data);
                
                // Detailed logging for debugging
                error_log("=== IMAGE SAVE DEBUG ===");
                error_log("File uploaded successfully!");
                error_log("Original file name: " . $frame_file->getClientName());
                error_log("Saved file name: " . $file_name);
                error_log("Full file path: " . $file_path);
                error_log("File size: " . $file_size . " bytes");
                error_log("MIME type: " . $mime_type);
                error_log("Is edited image: " . ($this->request->getPost('is_edited_image') ? 'YES' : 'NO'));
                error_log("Upload directory: " . $upload_path);
                error_log("Serialized data: " . serialize($frame_data));
                error_log("Web URL will be: " . base_url('files/storyboard_frames/' . $file_name));
                
                // Verify file actually exists
                if (file_exists($file_path)) {
                    error_log(" File verification: File exists at " . $file_path);
                    error_log(" File is readable: " . (is_readable($file_path) ? 'YES' : 'NO'));
                } else {
                    error_log("❌ File verification: File NOT found at " . $file_path);
                }
                error_log("========================");
            } else {
                error_log("ERROR: Failed to move uploaded file to: " . $upload_path . $file_name);
            }
        }

        try {
            if (!isset($this->Storyboards_model)) {
                throw new Exception("Storyboards_model not loaded");
            }
            
            if ($storyboard_id) {
                $save_id = $this->Storyboards_model->ci_save($data, $storyboard_id);
            } else {
                // Add required fields for new storyboard
                $data["created_by"] = $this->login_user->id;
                $data["sort_order"] = 0;
                $data["deleted"] = 0;
                $save_id = $this->Storyboards_model->ci_save($data);
            }

            if ($save_id) {
                // Log successful save with details
                error_log("=== DATABASE SAVE SUCCESS ===");
                error_log("Storyboard ID: " . $save_id);
                error_log("Project ID: " . $project_id);
                if (isset($data["frame"])) {
                    error_log("Frame data saved to database: " . $data["frame"]);
                    $frame_data = unserialize($data["frame"]);
                    if ($frame_data && isset($frame_data['file_name'])) {
                        error_log("Image file name in DB: " . $frame_data['file_name']);
                        error_log("Image URL: " . base_url('files/storyboard_frames/' . $frame_data['file_name']));
                    }
                }
                error_log("=============================");
                
                echo json_encode(array(
                    "success" => true, 
                    "id" => $save_id, 
                    "message" => "Storyboard saved successfully",
                    "debug_info" => array(
                        "file_saved" => isset($data["frame"]) ? "YES" : "NO",
                        "storyboard_id" => $save_id
                    )
                ));
            } else {
                error_log("ERROR: Failed to save storyboard to database");
                echo json_encode(array("success" => false, "message" => "Failed to save storyboard"));
            }
        } catch (Exception $e) {
            error_log("Error saving storyboard: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error saving storyboard: " . $e->getMessage()));
        }
    }

    private function handle_raw_footage_upload($storyboard_id = null) {
        error_log("handle_raw_footage_upload called with storyboard_id: " . ($storyboard_id ?: 'null'));
        
        try {
            // Increase memory limit and execution time for large uploads
            ini_set('memory_limit', '512M');
            set_time_limit(300); // 5 minutes
            
            // Get existing footage data if editing
            $existing_footage = array();
            if ($storyboard_id) {
                $existing_storyboard = $this->Storyboards_model->get_one($storyboard_id);
                if ($existing_storyboard->raw_footage) {
                    $existing_footage = @unserialize($existing_storyboard->raw_footage);
                    if (!is_array($existing_footage)) {
                        $existing_footage = array();
                    }
                }
                error_log("Found " . count($existing_footage) . " existing footage files");
            }

            // Handle file removals
            $removed_files = $this->request->getPost('removed_footage_files');
            if ($removed_files) {
                $removed_indices = explode(',', $removed_files);
                foreach ($removed_indices as $index) {
                    if (isset($existing_footage[$index])) {
                        // Delete physical file
                        $file_path = FCPATH . 'files/storyboard_footage/' . $existing_footage[$index]['file_name'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                            error_log("Deleted existing file: " . $file_path);
                        }
                        unset($existing_footage[$index]);
                    }
                }
                // Re-index array
                $existing_footage = array_values($existing_footage);
            }

            // Check for uploaded files
            if (isset($_FILES['raw_footage_files']) && !empty($_FILES['raw_footage_files']['name'][0])) {
                error_log("Found uploaded files via \$_FILES");
                
                // Create upload directory if it doesn't exist
                $upload_path = FCPATH . 'files/storyboard_footage/';
                if (!is_dir($upload_path)) {
                    if (!mkdir($upload_path, 0755, true)) {
                        throw new Exception("Failed to create upload directory: " . $upload_path);
                    }
                    error_log("Created upload directory: " . $upload_path);
                }

                // Check if directory is writable
                if (!is_writable($upload_path)) {
                    throw new Exception("Upload directory is not writable: " . $upload_path);
                }

                $file_count = count($_FILES['raw_footage_files']['name']);
                error_log("Processing " . $file_count . " files");

                $upload_errors = array();
                $successful_uploads = 0;

                for ($i = 0; $i < $file_count; $i++) {
                    $upload_error = $_FILES['raw_footage_files']['error'][$i];
                    $original_name = $_FILES['raw_footage_files']['name'][$i];
                    
                    // Skip empty file slots
                    if (empty($original_name)) {
                        continue;
                    }
                    
                    // Check for upload errors
                    if ($upload_error !== UPLOAD_ERR_OK) {
                        $error_message = $this->getUploadErrorMessage($upload_error);
                        error_log("Upload error for file '$original_name': $error_message");
                        $upload_errors[] = "File '$original_name': $error_message";
                        continue;
                    }

                    $tmp_name = $_FILES['raw_footage_files']['tmp_name'][$i];
                    $file_size = $_FILES['raw_footage_files']['size'][$i];
                    $mime_type = $_FILES['raw_footage_files']['type'][$i];
                    
                    error_log("Processing file: $original_name, Size: " . number_format($file_size / 1024 / 1024, 2) . " MB");

                    // Validate file size (100MB limit)
                    if ($file_size > 100 * 1024 * 1024) {
                        $error_msg = "File too large (max 100MB): " . number_format($file_size / 1024 / 1024, 2) . " MB";
                        error_log($error_msg);
                        $upload_errors[] = "File '$original_name': $error_msg";
                        continue;
                    }

                    // Validate file type
                    $allowed_types = array('video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/webm', 'video/x-matroska');
                    if (!in_array($mime_type, $allowed_types)) {
                        $error_msg = "Invalid file type: $mime_type";
                        error_log($error_msg);
                        $upload_errors[] = "File '$original_name': $error_msg";
                        continue;
                    }

                    // Generate unique filename
                    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $destination = $upload_path . $file_name;
                    
                    error_log("Moving file to: " . $destination);
                    
                    // Move uploaded file
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $existing_footage[] = array(
                            'file_name' => $file_name,
                            'original_name' => $original_name,
                            'file_size' => $file_size,
                            'mime_type' => $mime_type,
                            'uploaded_at' => date('Y-m-d H:i:s')
                        );
                        $successful_uploads++;
                        error_log("Successfully uploaded file: $file_name");
                    } else {
                        $error_msg = "Failed to move uploaded file";
                        error_log($error_msg . ": $original_name");
                        $upload_errors[] = "File '$original_name': $error_msg";
                    }
                }

                // Log summary
                error_log("Upload summary: $successful_uploads successful, " . count($upload_errors) . " errors");
                
                // If there were errors but some files uploaded successfully, log them
                if (!empty($upload_errors)) {
                    error_log("Upload errors: " . implode('; ', $upload_errors));
                }
                
            } else {
                error_log("No files found in \$_FILES['raw_footage_files']");
            }

            // Return serialized footage data
            $result = !empty($existing_footage) ? serialize($existing_footage) : null;
            error_log("Final footage data: " . ($result ? "serialized array with " . count($existing_footage) . " files" : "null"));
            return $result;
            
        } catch (Exception $e) {
            error_log("Exception in handle_raw_footage_upload: " . $e->getMessage());
            throw $e;
        }
    }

    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    function test_upload() {
        error_log("test_upload called");
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        
        $uploaded_files = $this->request->getFiles();
        error_log("CodeIgniter files: " . print_r($uploaded_files, true));
        
        echo json_encode(array(
            "success" => true, 
            "message" => "Test upload endpoint reached",
            "post_count" => count($_POST),
            "files_count" => count($_FILES),
            "ci_files_count" => count($uploaded_files)
        ));
    }

    function delete() {
        $id = $this->request->getPost('id');
        
        if ($id) {
            $storyboard_info = $this->Storyboards_model->get_one($id);
            if (!$this->can_view_project($storyboard_info->project_id)) {
                echo json_encode(array("success" => false, "message" => "Access denied"));
                return;
            }

            if ($this->Storyboards_model->delete($id)) {
                echo json_encode(array("success" => true, "message" => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, "message" => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    function reorder() {
        $project_id = $this->request->getPost('project_id');
        $sub_project_id = $this->request->getPost('sub_project_id');
        $heading_id = $this->request->getPost('heading_id');
        $shot_orders = $this->request->getPost('shot_orders');
        
        // Debug logging
        error_log("Reorder request - project_id: $project_id, sub_project_id: $sub_project_id, heading_id: $heading_id");
        error_log("Shot orders: " . json_encode($shot_orders));
        
        if (!$project_id || !$this->can_view_project($project_id)) {
            echo json_encode(array("success" => false, "message" => "Access denied"));
            return;
        }

        if (!$shot_orders || !is_array($shot_orders)) {
            echo json_encode(array("success" => false, "message" => "Invalid shot orders"));
            return;
        }

        try {
            // Update sort_order for each shot
            foreach ($shot_orders as $index => $shot_id) {
                $data = array(
                    'sort_order' => $index + 1,
                    'shot' => $index + 1  // Also update the shot number
                );
                $this->Storyboards_model->ci_save($data, $shot_id);
            }
            
            echo json_encode(array(
                "success" => true, 
                "message" => "Scenes reordered successfully"
            ));
        } catch (Exception $e) {
            error_log("Reorder error: " . $e->getMessage());
            echo json_encode(array(
                "success" => false, 
                "message" => "Failed to update order: " . $e->getMessage()
            ));
        }
    }

    function get_row_data() {
        header('Content-Type: application/json');
        
        $id = $this->request->getPost('id');
        
        if (!$id) {
            echo json_encode(array("success" => false, "message" => "ID is required"));
            return;
        }
        
        try {
            // Get storyboard data
            $options = array("id" => $id);
            $result = $this->Storyboards_model->get_details($options);
            
            if (!$result) {
                echo json_encode(array("success" => false, "message" => "Storyboard not found"));
                return;
            }
            
            $storyboard = $result->getRow();
            
            if (!$storyboard) {
                echo json_encode(array("success" => false, "message" => "Storyboard not found"));
                return;
            }
            
            // Check access
            if (!$this->can_view_project($storyboard->project_id)) {
                echo json_encode(array("success" => false, "message" => "Access denied"));
                return;
            }
            
            // Fix null values
            $storyboard->content = $storyboard->content ?: '';
            $storyboard->dialogues = $storyboard->dialogues ?: '';
            $storyboard->note = $storyboard->note ?: '';
            $storyboard->shot_size = $storyboard->shot_size ?: '';
            $storyboard->shot_type = $storyboard->shot_type ?: '';
            $storyboard->movement = $storyboard->movement ?: '';
            $storyboard->sound = $storyboard->sound ?: '';
            $storyboard->equipment = $storyboard->equipment ?: '';
            $storyboard->lighting = $storyboard->lighting ?: '';
            $storyboard->raw_footage = $storyboard->raw_footage ?: '';
            
            // Format duration
            if ($storyboard->duration) {
                $storyboard->duration = $this->formatDuration($storyboard->duration);
            }
            
            // Generate row HTML
            $view_data = array(
                'storyboard' => $storyboard,
                'project_id' => $storyboard->project_id,
                'sub_project_id' => $storyboard->sub_storyboard_project_id
            );
            
            $html = view('storyboard/partials/storyboard_row', $view_data);
            
            echo json_encode(array(
                "success" => true,
                "html" => $html
            ));
            
        } catch (Exception $e) {
            error_log("Get row data error: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
        }
    }

    function upload_frame() {
        // Set JSON header
        header('Content-Type: application/json');
        
        try {
            $id = $this->request->getPost('id');
            
            error_log("Upload frame called for ID: " . $id);
            
            if (!$id) {
                echo json_encode(array("success" => false, "message" => "Storyboard ID is required"));
                return;
            }
            
            // Get storyboard info to check project access
            $storyboard_info = $this->Storyboards_model->get_one($id);
            if (!$storyboard_info->id) {
                echo json_encode(array("success" => false, "message" => "Storyboard not found"));
                return;
            }
            
            if (!$this->can_view_project($storyboard_info->project_id)) {
                echo json_encode(array("success" => false, "message" => "Access denied"));
                return;
            }
            
            // Handle file upload - try both methods
            $frame_file = $this->request->getFile('frame');
            
            if (!$frame_file) {
                error_log("No file found with getFile()");
                echo json_encode(array("success" => false, "message" => "No file uploaded"));
                return;
            }
            
            error_log("File received: " . $frame_file->getName() . ", Size: " . $frame_file->getSize());
            
            // Check if file has already been moved
            if ($frame_file->hasMoved()) {
                error_log("File has already been moved");
                echo json_encode(array("success" => false, "message" => "File has already been moved"));
                return;
            }
            
            // Check if file is valid
            if (!$frame_file->isValid()) {
                $error = $frame_file->getErrorString() . ' (' . $frame_file->getError() . ')';
                error_log("File validation failed: " . $error);
                echo json_encode(array("success" => false, "message" => "Invalid file: " . $error));
                return;
            }
            
            // Validate file type
            $mime_type = $frame_file->getMimeType();
            $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
            
            error_log("File MIME type: " . $mime_type);
            
            if (!in_array($mime_type, $allowed_types)) {
                echo json_encode(array("success" => false, "message" => "Invalid file type: " . $mime_type . ". Only images are allowed."));
                return;
            }
            
            // Validate file size (max 10MB)
            $file_size = $frame_file->getSize();
            if ($file_size > 10 * 1024 * 1024) {
                echo json_encode(array("success" => false, "message" => "File size must be less than 10MB"));
                return;
            }
            
            // Create upload directory if it doesn't exist
            $upload_path = FCPATH . 'files/storyboard_frames/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
                error_log("Created directory: " . $upload_path);
            }
            
            // Generate unique filename
            $file_name = uniqid() . '_' . $frame_file->getName();
            
            error_log("Moving file to: " . $upload_path . $file_name);
            
            // Move uploaded file
            if ($frame_file->move($upload_path, $file_name)) {
                error_log("File moved successfully");
                
                // Save frame data
                $frame_data = array(
                    'file_name' => $file_name,
                    'original_name' => $frame_file->getClientName(),
                    'file_size' => $file_size,
                    'mime_type' => $mime_type
                );
                
                $data = array(
                    'frame' => serialize($frame_data)
                );
                
                error_log("Saving to database...");
                
                if ($this->Storyboards_model->ci_save($data, $id)) {
                    error_log("Database save successful");
                    echo json_encode(array(
                        "success" => true, 
                        "message" => "Frame image uploaded successfully",
                        "file_name" => $file_name
                    ));
                } else {
                    error_log("Database save failed");
                    // Delete uploaded file if database save fails
                    @unlink($upload_path . $file_name);
                    echo json_encode(array("success" => false, "message" => "Failed to save frame data"));
                }
            } else {
                $error = $frame_file->getErrorString();
                error_log("File move failed: " . $error);
                echo json_encode(array("success" => false, "message" => "Failed to move uploaded file: " . $error));
            }
            
        } catch (\Exception $e) {
            error_log("Frame upload exception: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(array("success" => false, "message" => "Upload error: " . $e->getMessage()));
        }
    }

    private function _row_data($id) {
        $options = array("id" => $id);
        $data = $this->Storyboards_model->get_details($options)->getRow();
        return $this->_make_row($data);
    }

    private function _make_row($data) {
        $frame_display = "";
        if ($data->frame) {
            $frame_data = unserialize($data->frame);
            if ($frame_data && isset($frame_data['file_name'])) {
                $frame_display = "<img src='" . base_url("files/storyboard_frames/" . $frame_data['file_name']) . "' class='img-thumbnail' style='max-width: 100px; max-height: 60px;'>";
            }
        }

        $status_class = "";
        switch($data->story_status) {
            case 'Draft': $status_class = "bg-secondary"; break;
            case 'Editing': $status_class = "bg-warning"; break;
            case 'Review': $status_class = "bg-info"; break;
            case 'Approved': $status_class = "bg-success"; break;
            case 'Final': $status_class = "bg-primary"; break;
        }

        return array(
            $data->shot,
            $frame_display,
            $data->shot_size ?: "-",
            $data->shot_type ?: "-",
            $data->movement ?: "-",
            $data->duration ?: "-",
            character_limiter($data->content ?: '', 50),
            character_limiter($data->dialogues ?: '', 50),
            $data->sound ?: "-",
            $data->equipment ?: "-",
            $data->framerate ?: "-",
            $data->lighting ?: "-",
            character_limiter($data->note ?: '', 50),
            $data->raw_footage ?: "-",
            "<span class='badge $status_class'>" . $data->story_status . "</span>",
            modal_anchor(get_uri("storyboard/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_storyboard'), "data-post-id" => $data->id, "data-post-project_id" => $data->project_id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_storyboard'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("storyboard/delete"), "data-action" => "delete-confirmation"))
        );
    }
    
    function update_project() {
        $project_id = $this->request->getPost('id');
        $title = $this->request->getPost('title');
        $description = $this->request->getPost('description');
        
        if (!$project_id || !$title) {
            echo json_encode(array("success" => false, "message" => "Project ID and title are required."));
            return;
        }
        
        // Validate project access and that it's a storyboard project
        if (!$this->can_view_project($project_id)) {
            echo json_encode(array("success" => false, "message" => "Access denied."));
            return;
        }
        
        $project_info = $this->Projects_model->get_one($project_id);
        if (!$project_info->id || $project_info->is_storyboard != 1) {
            echo json_encode(array("success" => false, "message" => "Invalid storyboard project."));
            return;
        }
        
        // Update project
        $project_data = array(
            "title" => $title,
            "description" => $description
        );
        
        $save_id = $this->Projects_model->ci_save($project_data, $project_id);
        
        if ($save_id) {
            echo json_encode(array("success" => true, "message" => "Project updated successfully."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to update project."));
        }
    }
    
    function delete_project() {
        $project_id = $this->request->getPost('id');
        
        if (!$project_id) {
            echo json_encode(array("success" => false, "message" => "Project ID is required."));
            return;
        }
        
        // Validate project access and that it's a storyboard project
        if (!$this->can_view_project($project_id)) {
            echo json_encode(array("success" => false, "message" => "Access denied."));
            return;
        }
        
        $project_info = $this->Projects_model->get_one($project_id);
        if (!$project_info->id || $project_info->is_storyboard != 1) {
            echo json_encode(array("success" => false, "message" => "Invalid storyboard project."));
            return;
        }
        
        try {
            $deleted_counts = array(
                'storyboards' => 0,
                'scene_headings' => 0,
                'sub_projects' => 0
            );
            
            // Delete related storyboard data first
            if (isset($this->Storyboards_model)) {
                // Delete all storyboards for this project
                $storyboards = $this->Storyboards_model->get_details(array("project_id" => $project_id))->getResult();
                foreach ($storyboards as $storyboard) {
                    $this->Storyboards_model->delete_permanently($storyboard->id);
                    $deleted_counts['storyboards']++;
                }
            }
            
            if (isset($this->Scene_headings_model)) {
                // Delete all scene headings for this project
                $headings = $this->Scene_headings_model->get_details(array("project_id" => $project_id))->getResult();
                foreach ($headings as $heading) {
                    $this->Scene_headings_model->delete_permanently($heading->id);
                    $deleted_counts['scene_headings']++;
                }
            }
            
            if (isset($this->Sub_storyboard_projects_model)) {
                // Delete all sub-projects for this project (using correct field name: rise_story_id)
                $sub_projects = $this->Sub_storyboard_projects_model->get_details(array("rise_story_id" => $project_id))->getResult();
                foreach ($sub_projects as $sub_project) {
                    $this->Sub_storyboard_projects_model->delete_permanently($sub_project->id);
                    $deleted_counts['sub_projects']++;
                }
            }
            
            // Log what we're about to delete
            error_log("Deleting project $project_id - Counts: " . json_encode($deleted_counts));
            
            // Finally delete the project - use soft delete instead of permanent delete
            $project_data = array("deleted" => 1);
            $delete_result = $this->Projects_model->ci_save($project_data, $project_id);
            
            if ($delete_result) {
                error_log("Project $project_id marked as deleted successfully");
                echo json_encode(array(
                    "success" => true, 
                    "message" => "Project deleted successfully.",
                    "deleted_counts" => $deleted_counts
                ));
            } else {
                error_log("Failed to mark project $project_id as deleted");
                echo json_encode(array("success" => false, "message" => "Failed to delete project."));
            }
            
        } catch (Exception $e) {
            error_log("Error deleting storyboard project: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error deleting project: " . $e->getMessage()));
        }
    }
    
    function debug_delete_project() {
        $project_id = $this->request->getPost('id') ?: $this->request->getGet('id');
        
        if (!$project_id) {
            echo json_encode(array("error" => "No project ID provided"));
            return;
        }
        
        $debug_info = array();
        
        // Check project exists
        $project_info = $this->Projects_model->get_one($project_id);
        $debug_info['project_exists'] = $project_info ? true : false;
        $debug_info['project_info'] = $project_info;
        
        // Check related data
        if (isset($this->Storyboards_model)) {
            $storyboards = $this->Storyboards_model->get_details(array("project_id" => $project_id))->getResult();
            $debug_info['storyboards_count'] = count($storyboards);
        }
        
        if (isset($this->Scene_headings_model)) {
            $headings = $this->Scene_headings_model->get_details(array("project_id" => $project_id))->getResult();
            $debug_info['scene_headings_count'] = count($headings);
        }
        
        if (isset($this->Sub_storyboard_projects_model)) {
            $sub_projects = $this->Sub_storyboard_projects_model->get_details(array("rise_story_id" => $project_id))->getResult();
            $debug_info['sub_projects_count'] = count($sub_projects);
        }
        
        $debug_info['can_view_project'] = $this->can_view_project($project_id);
        
        echo json_encode($debug_info);
    }
    
    function get_field_options() {
        $field_type = $this->request->getGet('field_type') ?: $this->request->getPost('field_type');
        
        if (!$field_type) {
            echo json_encode(array("success" => false, "message" => "Field type is required"), JSON_UNESCAPED_UNICODE);
            return;
        }
        
        try {
            if (isset($this->Storyboard_field_options_model)) {
                $options = $this->Storyboard_field_options_model->get_field_options_array($field_type);
                echo json_encode(array("success" => true, "options" => $options), JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(array("success" => false, "message" => "Field options model not available"), JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error getting field options: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()), JSON_UNESCAPED_UNICODE);
        }
    }
    
    function delete_frame_image() {
        $storyboard_id = $this->request->getPost('storyboard_id');
        
        if (!$storyboard_id) {
            echo json_encode(array("success" => false, "message" => "No storyboard ID provided"));
            return;
        }
        
        try {
            // Get storyboard info
            if (!isset($this->Storyboards_model)) {
                throw new Exception("Storyboards model not available");
            }
            
            $storyboard = $this->Storyboards_model->get_one($storyboard_id);
            if (!$storyboard->id) {
                throw new Exception("Storyboard not found");
            }
            
            // Check permissions
            if (!$this->can_view_project($storyboard->project_id)) {
                throw new Exception("Access denied");
            }
            
            // Get current frame data
            $frame_data = null;
            if ($storyboard->frame) {
                $frame_data = @unserialize($storyboard->frame);
            }
            
            // Delete physical file if exists
            if ($frame_data && isset($frame_data['file_name'])) {
                $file_path = FCPATH . 'files/storyboard_frames/' . $frame_data['file_name'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                    error_log("Deleted frame image file: " . $file_path);
                }
            }
            
            // Update database to remove frame data
            $update_data = array('frame' => null);
            $result = $this->Storyboards_model->ci_save($update_data, $storyboard_id);
            
            if ($result) {
                error_log("Frame image deleted successfully for storyboard ID: " . $storyboard_id);
                echo json_encode(array(
                    "success" => true, 
                    "message" => "Image deleted successfully"
                ));
            } else {
                throw new Exception("Failed to update database");
            }
            
        } catch (Exception $e) {
            error_log("Error deleting frame image: " . $e->getMessage());
            echo json_encode(array(
                "success" => false, 
                "message" => "Error deleting image: " . $e->getMessage()
            ));
        }
    }
    
    function save_field_options() {
        $field_type = $this->request->getPost('field_type');
        $options_data = $this->request->getPost('options');
        
        if (!$field_type || !$options_data) {
            echo json_encode(array("success" => false, "message" => "Field type and options data are required"), JSON_UNESCAPED_UNICODE);
            return;
        }
        
        try {
            // Decode JSON if it's a string - use JSON_UNESCAPED_UNICODE for emoji support
            if (is_string($options_data)) {
                $options_data = json_decode($options_data, true);
            }
            
            if (isset($this->Storyboard_field_options_model)) {
                $result = $this->Storyboard_field_options_model->save_field_options($field_type, $options_data);
                
                if ($result) {
                    echo json_encode(array("success" => true, "message" => "Field options saved successfully"), JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(array("success" => false, "message" => "Failed to save field options"), JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode(array("success" => false, "message" => "Field options model not available"), JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error saving field options: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Database error: " . $e->getMessage()), JSON_UNESCAPED_UNICODE);
        }
    }
    
    function test_inline_edit() {
        echo json_encode(array(
            "success" => true, 
            "message" => "Test endpoint working",
            "post_data" => $_POST,
            "method" => $_SERVER['REQUEST_METHOD'],
            "models_loaded" => [
                "Storyboards_model" => isset($this->Storyboards_model),
                "Projects_model" => isset($this->Projects_model)
            ]
        ));
    }

    function debug_inline_edit() {
        $id = $this->request->getPost('id');
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');
        
        $debug_info = array(
            "received_data" => array(
                "id" => $id,
                "field" => $field, 
                "value" => $value
            ),
            "models_available" => isset($this->Storyboards_model),
            "user_info" => array(
                "id" => $this->login_user->id ?? 'not set',
                "is_admin" => $this->login_user->is_admin ?? 'not set'
            )
        );
        
        if ($id && isset($this->Storyboards_model)) {
            try {
                $storyboard = $this->Storyboards_model->get_one($id);
                $debug_info["storyboard_found"] = !!$storyboard->id;
                $debug_info["project_id"] = $storyboard->project_id ?? 'not found';
            } catch (Exception $e) {
                $debug_info["storyboard_error"] = $e->getMessage();
            }
        }
        
        echo json_encode($debug_info);
    }

    function test_filtering() {
        $project_id = $this->request->getGet('project_id') ?: 133;
        $sub_project_id = $this->request->getGet('sub_project_id');
        
        echo "<h1>Test Storyboard Filtering</h1>";
        echo "<p>Project ID: $project_id</p>";
        echo "<p>Sub-Project ID: " . ($sub_project_id ?: 'NULL') . "</p>";
        
        echo "<h2>All Storyboards for Project $project_id:</h2>";
        try {
            $all_options = array("project_id" => $project_id);
            $all_result = $this->Storyboards_model->get_details($all_options);
            $all_storyboards = $all_result->getResult();
            
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Shot</th><th>Project ID</th><th>Sub-Project ID</th><th>Content</th></tr>";
            foreach ($all_storyboards as $sb) {
                echo "<tr>";
                echo "<td>{$sb->id}</td>";
                echo "<td>{$sb->shot}</td>";
                echo "<td>{$sb->project_id}</td>";
                echo "<td>" . ($sb->sub_storyboard_project_id ?: 'NULL') . "</td>";
                echo "<td>" . character_limiter($sb->content ?: '', 30) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<p>Error: " . $e->getMessage() . "</p>";
        }
        
        if ($sub_project_id) {
            echo "<h2>Filtered Storyboards for Sub-Project $sub_project_id:</h2>";
            try {
                $filtered_options = array(
                    "project_id" => $project_id,
                    "sub_storyboard_project_id" => $sub_project_id
                );
                $filtered_result = $this->Storyboards_model->get_details($filtered_options);
                $filtered_storyboards = $filtered_result->getResult();
                
                echo "<table border='1'>";
                echo "<tr><th>ID</th><th>Shot</th><th>Project ID</th><th>Sub-Project ID</th><th>Content</th></tr>";
                foreach ($filtered_storyboards as $sb) {
                    echo "<tr>";
                    echo "<td>{$sb->id}</td>";
                    echo "<td>{$sb->shot}</td>";
                    echo "<td>{$sb->project_id}</td>";
                    echo "<td>" . ($sb->sub_storyboard_project_id ?: 'NULL') . "</td>";
                    echo "<td>" . character_limiter($sb->content ?: '', 30) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<p>Found " . count($filtered_storyboards) . " scenes for sub-project $sub_project_id</p>";
                
            } catch (Exception $e) {
                echo "<p>Error: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h2>Test Links:</h2>";
        echo "<p><a href='" . get_uri("storyboard/test_filtering?project_id=133&sub_project_id=1") . "'>Test Sub-Project 1</a></p>";
        echo "<p><a href='" . get_uri("storyboard/test_filtering?project_id=133&sub_project_id=2") . "'>Test Sub-Project 2</a></p>";
        echo "<p><a href='" . get_uri("storyboard?project_id=133&sub_project_id=1") . "'>Go to Sub-Project 1 Storyboard</a></p>";
        echo "<p><a href='" . get_uri("storyboard?project_id=133&sub_project_id=2") . "'>Go to Sub-Project 2 Storyboard</a></p>";
    }

    function inline_edit() {
        $id = $this->request->getPost('id');
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');
        
        if (!$id || !$field) {
            echo json_encode(array("success" => false, "message" => "Missing required parameters"), JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Get storyboard info to check permissions
        try {
            if (!isset($this->Storyboards_model)) {
                throw new Exception("Storyboards model not available");
            }
            
            $storyboard_info = $this->Storyboards_model->get_one($id);
            if (!$storyboard_info->id) {
                echo json_encode(array("success" => false, "message" => "Storyboard not found"), JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Check if user can edit this project
            if (!$this->can_view_project($storyboard_info->project_id)) {
                echo json_encode(array("success" => false, "message" => "Access denied"), JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Validate field
            $allowed_fields = ['duration', 'content', 'dialogues', 'sound', 'equipment', 'lighting', 'note', 'story_status', 'shot_size', 'shot_type', 'movement', 'framerate'];
            if (!in_array($field, $allowed_fields)) {
                echo json_encode(array("success" => false, "message" => "Invalid field: $field"), JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Validate duration field
            if ($field === 'duration' && $value && !preg_match('/^[0-9]+(\.[0-9]+)?$/', $value)) {
                echo json_encode(array("success" => false, "message" => "Invalid duration format"), JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Validate fields with predefined options using dynamic field options
            $fields_with_options = ['story_status', 'shot_size', 'shot_type', 'movement', 'framerate'];
            if (in_array($field, $fields_with_options) && $value) {
                try {
                    if (isset($this->Storyboard_field_options_model)) {
                        $valid_values = $this->Storyboard_field_options_model->get_valid_values($field);
                        if (!empty($valid_values) && !in_array($value, $valid_values)) {
                            echo json_encode(array("success" => false, "message" => "Invalid " . str_replace('_', ' ', $field) . ": $value"), JSON_UNESCAPED_UNICODE);
                            return;
                        }
                    }
                } catch (Exception $e) {
                    // If database validation fails, skip validation (allow any value)
                    error_log("Field validation error for $field: " . $e->getMessage());
                }
            }
            
            // Format duration
            if ($field === 'duration' && $value) {
                $value = $this->formatDuration($value);
            }
            
            // Prepare data for update
            $data = array($field => $value);
            
            // Update the storyboard
            $result = $this->Storyboards_model->ci_save($data, $id);
            
            if ($result) {
                echo json_encode(array(
                    "success" => true, 
                    "message" => "Updated successfully",
                    "field" => $field,
                    "value" => $value
                ), JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(array("success" => false, "message" => "Database update failed"), JSON_UNESCAPED_UNICODE);
            }
            
        } catch (Exception $e) {
            error_log("Inline edit error: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()), JSON_UNESCAPED_UNICODE);
        }
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

    private function formatDuration($duration) {
        if (!$duration) return '';
        
        // Convert to float and remove unnecessary decimal places
        $num = (float)$duration;
        
        // If it's a whole number, return without decimals
        if ($num == (int)$num) {
            return (string)(int)$num;
        }
        
        // Otherwise, format to remove trailing zeros
        return rtrim(rtrim(number_format($num, 3), '0'), '.');
    }

    // Column Management Methods

    // Get column preferences for current user
    function get_column_preferences() {
        $project_id = $this->request->getGet('project_id');
        $user_id = $this->login_user->id;
        
        try {
            // Get column preferences
            $db = \Config\Database::connect();
            
            $preferences_query = $db->query("
                SELECT column_name, is_visible, column_order, column_width 
                FROM rise_storyboard_column_preferences 
                WHERE user_id = ? AND (project_id = ? OR project_id IS NULL)
                ORDER BY project_id DESC, column_order ASC
            ", [$user_id, $project_id]);
            
            $preferences_result = $preferences_query->getResult();
            $preferences = array();
            
            foreach ($preferences_result as $pref) {
                $preferences[$pref->column_name] = array(
                    'is_visible' => (bool)$pref->is_visible,
                    'column_order' => (int)$pref->column_order,
                    'column_width' => (int)$pref->column_width
                );
            }
            
            // Get custom columns
            $custom_query = $db->query("
                SELECT column_name, column_label, column_type, column_options, default_value, is_required
                FROM rise_storyboard_custom_columns 
                WHERE user_id = ? AND (project_id = ? OR project_id IS NULL)
            ", [$user_id, $project_id]);
            
            $custom_result = $custom_query->getResult();
            $custom_columns = array();
            
            foreach ($custom_result as $custom) {
                $custom_columns[$custom->column_name] = array(
                    'label' => $custom->column_label,
                    'type' => $custom->column_type,
                    'options' => $custom->column_options ? json_decode($custom->column_options, true) : array(),
                    'default_value' => $custom->default_value,
                    'required' => (bool)$custom->is_required,
                    'width' => $preferences[$custom->column_name]['column_width'] ?? 120
                );
            }
            
            echo json_encode(array(
                "success" => true,
                "preferences" => $preferences,
                "custom_columns" => $custom_columns
            ));
            
        } catch (Exception $e) {
            error_log("Error getting column preferences: " . $e->getMessage());
            echo json_encode(array(
                "success" => false,
                "message" => "Error loading column preferences: " . $e->getMessage()
            ));
        }
    }

    // Save column preferences
    function save_column_preferences() {
        $project_id = $this->request->getPost('project_id');
        $preferences_json = $this->request->getPost('preferences');
        $custom_columns_json = $this->request->getPost('custom_columns');
        $user_id = $this->login_user->id;
        
        try {
            $preferences = json_decode($preferences_json, true);
            $custom_columns = json_decode($custom_columns_json, true);
            
            if (!$preferences) {
                throw new Exception("Invalid preferences data");
            }
            
            $db = \Config\Database::connect();
            $db->transStart();
            
            // Delete existing preferences for this user/project
            $db->query("
                DELETE FROM rise_storyboard_column_preferences 
                WHERE user_id = ? AND (project_id = ? OR project_id IS NULL)
            ", [$user_id, $project_id]);
            
            // Insert new preferences
            foreach ($preferences as $column_name => $pref) {
                $db->query("
                    INSERT INTO rise_storyboard_column_preferences 
                    (user_id, project_id, column_name, is_visible, column_order, column_width) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $user_id,
                    $project_id,
                    $column_name,
                    $pref['is_visible'] ? 1 : 0,
                    $pref['column_order'],
                    $pref['column_width']
                ]);
            }
            
            // Handle custom columns
            if ($custom_columns) {
                // Delete existing custom columns
                $db->query("
                    DELETE FROM rise_storyboard_custom_columns 
                    WHERE user_id = ? AND (project_id = ? OR project_id IS NULL)
                ", [$user_id, $project_id]);
                
                // Insert new custom columns
                foreach ($custom_columns as $column_name => $custom) {
                    $db->query("
                        INSERT INTO rise_storyboard_custom_columns 
                        (user_id, project_id, column_name, column_label, column_type, column_options, default_value, is_required) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ", [
                        $user_id,
                        $project_id,
                        $column_name,
                        $custom['label'],
                        $custom['type'],
                        json_encode($custom['options'] ?? array()),
                        $custom['default_value'] ?? null,
                        $custom['required'] ? 1 : 0
                    ]);
                }
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === FALSE) {
                throw new Exception("Transaction failed");
            }
            
            echo json_encode(array(
                "success" => true,
                "message" => "Column preferences saved successfully"
            ));
            
        } catch (Exception $e) {
            error_log("Error saving column preferences: " . $e->getMessage());
            echo json_encode(array(
                "success" => false,
                "message" => "Error saving column preferences: " . $e->getMessage()
            ));
        }
    }

    // Get storyboard data with custom fields
    function get_storyboard_with_custom_fields($project_id, $sub_project_id = null) {
        try {
            $db = \Config\Database::connect();
            
            // Get storyboard data including custom fields
            $query = "
                SELECT s.*, s.custom_fields
                FROM rise_storyboards s
                WHERE s.project_id = ?
            ";
            
            $params = [$project_id];
            
            if ($sub_project_id) {
                $query .= " AND s.sub_storyboard_project_id = ?";
                $params[] = $sub_project_id;
            }
            
            $query .= " ORDER BY s.shot ASC";
            
            $result = $db->query($query, $params);
            $storyboards = $result->getResult();
            
            // Parse custom fields JSON
            foreach ($storyboards as $storyboard) {
                if ($storyboard->custom_fields) {
                    $storyboard->custom_data = json_decode($storyboard->custom_fields, true);
                } else {
                    $storyboard->custom_data = array();
                }
            }
            
            return $storyboards;
            
        } catch (Exception $e) {
            error_log("Error getting storyboard with custom fields: " . $e->getMessage());
            return array();
        }
    }

    // Save custom field data
    function save_custom_field() {
        $storyboard_id = $this->request->getPost('storyboard_id');
        $field_name = $this->request->getPost('field_name');
        $field_value = $this->request->getPost('field_value');
        
        try {
            $db = \Config\Database::connect();
            
            // Get current custom fields
            $query = $db->query("SELECT custom_fields FROM rise_storyboards WHERE id = ?", [$storyboard_id]);
            $result = $query->getRow();
            
            if (!$result) {
                throw new Exception("Storyboard not found");
            }
            
            // Parse existing custom fields
            $custom_fields = $result->custom_fields ? json_decode($result->custom_fields, true) : array();
            
            // Update the specific field
            $custom_fields[$field_name] = $field_value;
            
            // Save back to database
            $db->query("
                UPDATE rise_storyboards 
                SET custom_fields = ?, updated_at = NOW() 
                WHERE id = ?
            ", [json_encode($custom_fields), $storyboard_id]);
            
            echo json_encode(array(
                "success" => true,
                "message" => "Custom field saved successfully"
            ));
            
        } catch (Exception $e) {
            error_log("Error saving custom field: " . $e->getMessage());
            echo json_encode(array(
                "success" => false,
                "message" => "Error saving custom field: " . $e->getMessage()
            ));
        }
    }

    // Reset column preferences to defaults
    function reset_column_preferences() {
        $project_id = $this->request->getPost('project_id');
        $user_id = $this->login_user->id;
        
        try {
            $db = \Config\Database::connect();
            $db->transStart();
            
            // Delete existing preferences
            $db->query("
                DELETE FROM rise_storyboard_column_preferences 
                WHERE user_id = ? AND (project_id = ? OR project_id IS NULL)
            ", [$user_id, $project_id]);
            
            // Delete custom columns
            $db->query("
                DELETE FROM rise_storyboard_custom_columns 
                WHERE user_id = ? AND (project_id = ? OR project_id IS NULL)
            ", [$user_id, $project_id]);
            
            // Insert default preferences
            $default_columns = array(
                array('shot', 1, 1, 80),
                array('frame', 1, 2, 220),
                array('shot_size', 1, 3, 120),
                array('shot_type', 1, 4, 120),
                array('movement', 1, 5, 120),
                array('duration', 1, 6, 100),
                array('content', 1, 7, 200),
                array('dialogues', 1, 8, 200),
                array('sound', 1, 9, 120),
                array('equipment', 1, 10, 120),
                array('framerate', 1, 11, 100),
                array('lighting', 1, 12, 150),
                array('note', 1, 13, 150),
                array('raw_footage', 1, 14, 150),
                array('story_status', 1, 15, 120),
                array('actions', 1, 16, 100)
            );
            
            foreach ($default_columns as $col) {
                $db->query("
                    INSERT INTO rise_storyboard_column_preferences 
                    (user_id, project_id, column_name, is_visible, column_order, column_width) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ", [$user_id, $project_id, $col[0], $col[1], $col[2], $col[3]]);
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === FALSE) {
                throw new Exception("Transaction failed");
            }
            
            echo json_encode(array(
                "success" => true,
                "message" => "Column preferences reset to defaults"
            ));
            
        } catch (Exception $e) {
            error_log("Error resetting column preferences: " . $e->getMessage());
            echo json_encode(array(
                "success" => false,
                "message" => "Error resetting column preferences: " . $e->getMessage()
            ));
        }
    }

    // Save row order after drag and drop
    function save_row_order() {
        $project_id = $this->request->getPost('project_id');
        $order_data_json = $this->request->getPost('order_data');
        
        try {
            $order_data = json_decode($order_data_json, true);
            
            if (!$order_data || !is_array($order_data)) {
                throw new Exception("Invalid order data");
            }
            
            // Check project access
            if (!$this->can_view_project($project_id)) {
                throw new Exception("Access denied");
            }
            
            $db = \Config\Database::connect();
            $db->transStart();
            
            // Update shot numbers for each storyboard
            foreach ($order_data as $item) {
                $storyboard_id = $item['id'];
                $new_shot_number = $item['shot'];
                
                // Verify the storyboard belongs to this project
                $storyboard_info = $this->Storyboards_model->get_one($storyboard_id);
                if (!$storyboard_info->id || $storyboard_info->project_id != $project_id) {
                    throw new Exception("Invalid storyboard ID: $storyboard_id");
                }
                
                // Update the shot number
                $this->Storyboards_model->ci_save(
                    array('shot' => $new_shot_number), 
                    $storyboard_id
                );
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === FALSE) {
                throw new Exception("Transaction failed");
            }
            
            echo json_encode(array(
                "success" => true,
                "message" => "Scene order updated successfully"
            ));
            
        } catch (Exception $e) {
            error_log("Error saving row order: " . $e->getMessage());
            echo json_encode(array(
                "success" => false,
                "message" => "Error saving row order: " . $e->getMessage()
            ));
        }
    }
    
    function update_image() {
        try {
            $storyboard_id = $this->request->getPost('id');
            
            if (!$storyboard_id) {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Storyboard ID is required"
                ));
                return;
            }
            
            // Check if edited image was uploaded
            $edited_image = $this->request->getFile('edited_image');
            
            if (!$edited_image || !$edited_image->isValid()) {
                echo json_encode(array(
                    "success" => false,
                    "message" => "No valid image provided"
                ));
                return;
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($edited_image->getMimeType(), $allowedTypes)) {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Invalid file type. Only JPEG, PNG, and GIF are allowed."
                ));
                return;
            }
            
            // Generate unique filename
            $fileName = 'edited_frame_' . $storyboard_id . '_' . time() . '.' . $edited_image->getExtension();
            
            // Set upload path
            $uploadPath = FCPATH . 'files/storyboard_frames/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Move uploaded file
            if ($edited_image->move($uploadPath, $fileName)) {
                // Update database with new frame path
                if ($this->Storyboards_model) {
                    $data = array('frame' => $fileName);
                    $save_id = $this->Storyboards_model->ci_save($data, $storyboard_id);
                    
                    if ($save_id) {
                        echo json_encode(array(
                            "success" => true,
                            "message" => "Image updated successfully",
                            "image_url" => base_url('files/storyboard_frames/' . $fileName),
                            "storyboard_id" => $storyboard_id
                        ));
                    } else {
                        // Delete uploaded file if database update failed
                        unlink($uploadPath . $fileName);
                        echo json_encode(array(
                            "success" => false,
                            "message" => "Failed to update database"
                        ));
                    }
                } else {
                    echo json_encode(array(
                        "success" => false,
                        "message" => "Storyboards model not available"
                    ));
                }
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Failed to save uploaded file"
                ));
            }
            
        } catch (Exception $e) {
            error_log("Error updating image: " . $e->getMessage());
            echo json_encode(array(
                "success" => false,
                "message" => "Error updating image: " . $e->getMessage()
            ));
        }
    }
}