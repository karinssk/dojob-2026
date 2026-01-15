<?php

namespace App\Controllers;

class Api extends Security_Controller {

    function __construct() {
        parent::__construct();
        
        // Set JSON headers with proper CORS for local development
        header('Content-Type: application/json');
        
        // Allow multiple origins for development
        $allowed_origins = [
            'https://api-dojob.rubyshop.co.th',
            'https://dojob.rubyshop168.com',
            'http://localhost:8888',
            'http://127.0.0.1:8888',
            'http://localhost:3000'
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Requested-With');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Check authentication status and return user info
     * URL: /api/auth-check
     */
    function auth_check() {
        try {
            // Get current user ID from session
            $login_user_id = $this->Users_model->login_user_id();
            
            echo json_encode([
                'success' => true,
                'authenticated' => !empty($login_user_id),
                'user_id' => $login_user_id,
                'session_id' => session_id(),
                'session_status' => session_status(),
                'login_user' => $this->login_user ? [
                    'id' => $this->login_user->id,
                    'first_name' => $this->login_user->first_name,
                    'last_name' => $this->login_user->last_name,
                    'email' => $this->login_user->email,
                    'user_type' => $this->login_user->user_type
                ] : null
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'authenticated' => false,
                'error' => 'Auth check failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get current authenticated user information
     * URL: /api/current-user
     */
    function current_user() {
        try {
            // Get current user ID from session
            $login_user_id = $this->Users_model->login_user_id();
            
            if (!$login_user_id) {
                // Return more detailed auth info for debugging
                echo json_encode([
                    'success' => false,
                    'authenticated' => false,
                    'error' => 'User not authenticated',
                    'debug' => [
                        'session_id' => session_id(),
                        'session_status' => session_status(),
                        'login_user_id' => $login_user_id,
                        'session_data' => $_SESSION ?? 'No session data'
                    ]
                ]);
                return;
            }

            // Get user data
            $user = $this->Users_model->get_one($login_user_id);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not found'
                ]);
                return;
            }

            // Return current user data
            echo json_encode([
                'success' => true,
                'authenticated' => true,
                'data' => [
                    'id' => (int)$user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'image' => $user->image,
                    'user_type' => $user->user_type,
                    'full_name' => trim($user->first_name . ' ' . $user->last_name)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get user information by ID (for admin users)
     * URL: /api/user/{id}
     */
    function user($user_id = null) {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Only allow admin users to get other user info
            if ($this->login_user->user_type !== 'staff' && $user_id != $this->login_user->id) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Access denied'
                ]);
                return;
            }

            $user_id = $user_id ?: $this->login_user->id;
            
            // Get user from database
            $user = $this->Users_model->get_one($user_id);
            
            if (!$user || $user->deleted || $user->status !== 'active') {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not found'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => (int)$user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'image' => $user->image,
                    'user_type' => $user->user_type,
                    'full_name' => trim($user->first_name . ' ' . $user->last_name)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get task information by ID
     * URL: /api/task/{id}
     */
    function task($taskId = null) {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Validate task ID
            if (!$taskId || !is_numeric($taskId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid task ID'
                ]);
                return;
            }

            // Get task data from PHP (always use PHP for task info to ensure consistency)
            $task = $this->Tasks_model->get_one($taskId);
            
            if (!$task) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Task not found'
                ]);
                return;
            }

            // Check if user has access to this task
            if ($task->project_id) {
                $is_member = $this->Project_members_model->is_user_a_project_member($task->project_id, $this->login_user->id);
                
                if (!$is_member && $task->assigned_to != $this->login_user->id && 
                    !strpos($task->collaborators, (string)$this->login_user->id)) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Access denied to this task'
                    ]);
                    return;
                }
            }

            // Get additional task details
            $task_data = [
                'id' => (int)$task->id,
                'title' => $task->title ?? '',
                'description' => $task->description ?? '',
                'project_id' => (int)($task->project_id ?? 0),
                'status_id' => (int)($task->status_id ?? 1),
                'priority_id' => (int)($task->priority_id ?? 2),
                'assigned_to' => (int)($task->assigned_to ?? 0),
                'collaborators' => $task->collaborators ?? '',
                'deadline' => $task->deadline ?? null,
                'created_date' => $task->created_date ?? date('Y-m-d H:i:s'),
                'status_changed_at' => $task->status_changed_at ?? null,
                'start_date' => $task->start_date ?? null,
                'sort' => (int)($task->sort ?? 0),
                'images' => $task->images ?? null,
                'labels' => $task->labels ?? '',
                'points' => (int)($task->points ?? 1),
                'parent_task_id' => (int)($task->parent_task_id ?? 0)
            ];

            // Get assignee details if assigned
            if ($task->assigned_to) {
                $assignee = $this->Users_model->get_one($task->assigned_to);
                if ($assignee) {
                    $task_data['first_name'] = $assignee->first_name;
                    $task_data['last_name'] = $assignee->last_name;
                    $task_data['user_image'] = $assignee->image;
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $task_data
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete an image from a task
     * URL: /api/task/{taskId}/image/{filename}
     * Method: DELETE
     */
    function delete_task_image($taskId = null, $filename = null) {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Validate parameters
            if (!$taskId || !is_numeric($taskId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid task ID'
                ]);
                return;
            }

            if (!$filename) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Filename is required'
                ]);
                return;
            }

            // Get task to verify access
            $task = $this->Tasks_model->get_one($taskId);
            
            if (!$task) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Task not found'
                ]);
                return;
            }

            // Check if user has access to this task
            if ($task->project_id) {
                $is_member = $this->Project_members_model->is_user_a_project_member($task->project_id, $this->login_user->id);
                
                if (!$is_member && $task->assigned_to != $this->login_user->id && 
                    !strpos($task->collaborators, (string)$this->login_user->id)) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Access denied to this task'
                    ]);
                    return;
                }
            }

            // Get current images
            $images = [];
            if ($task->images) {
                try {
                    $images = json_decode($task->images, true) ?: [];
                } catch (Exception $e) {
                    $images = [];
                }
            }

            // Find and remove the image
            $imageFound = false;
            $updatedImages = [];
            
            foreach ($images as $image) {
                $imageFilename = '';
                if (is_string($image)) {
                    $imageFilename = basename($image);
                } elseif (is_array($image) && isset($image['filename'])) {
                    $imageFilename = $image['filename'];
                } elseif (is_array($image) && isset($image['file_name'])) {
                    $imageFilename = $image['file_name'];
                }

                if ($imageFilename !== $filename) {
                    $updatedImages[] = $image;
                } else {
                    $imageFound = true;
                    
                    // Try to delete the physical file
                    $filePath = FCPATH . 'files/timeline_files/' . $filename;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            if (!$imageFound) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Image not found'
                ]);
                return;
            }

            // Update task with new images array
            $updateData = [
                'images' => json_encode($updatedImages)
            ];

            $result = $this->Tasks_model->ci_save($updateData, $taskId);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update task'
                ]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete a comment
     * URL: /api/comment/{commentId}
     * Method: DELETE
     */
    function delete_comment($commentId = null) {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Only handle DELETE requests
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                http_response_code(405);
                echo json_encode([
                    'success' => false,
                    'error' => 'Method not allowed'
                ]);
                return;
            }

            // Validate comment ID
            if (!$commentId || !is_numeric($commentId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid comment ID'
                ]);
                return;
            }

            // Load Timeline_model for comments
            $this->load->model('Timeline_model');
            
            // Get comment to verify ownership
            $comment = $this->Timeline_model->get_one($commentId);
            
            if (!$comment || $comment->deleted) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Comment not found'
                ]);
                return;
            }

            // Check if user can delete this comment (author or admin)
            if ($comment->created_by != $this->login_user->id && $this->login_user->user_type !== 'staff') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Access denied - you can only delete your own comments'
                ]);
                return;
            }

            // Soft delete the comment
            $result = $this->Timeline_model->ci_save(['deleted' => 1], $commentId);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Comment deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to delete comment'
                ]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get all active users for dropdowns
     * URL: /api/users
     */
    function users() {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Get all active users
            $users = $this->Users_model->get_all_where([
                'deleted' => 0,
                'status' => 'active'
            ])->getResult();

            $user_data = [];
            foreach ($users as $user) {
                $user_data[] = [
                    'id' => (int)$user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'image' => $user->image
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $user_data
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get task statuses
     * URL: /api/task-statuses
     */
    function task_statuses() {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Load Task_status_model
            $this->load->model('Task_status_model');
            
            // Get all task statuses
            $statuses = $this->Task_status_model->get_all_where([
                'deleted' => 0
            ])->getResult();

            $status_data = [];
            foreach ($statuses as $status) {
                $status_data[] = [
                    'id' => (int)$status->id,
                    'title' => $status->title,
                    'color' => $status->color ?? '#28a745'
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $status_data
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get task priorities
     * URL: /api/task-priorities
     */
    function task_priorities() {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Define standard task priorities
            $priorities = [
                ['id' => 1, 'title' => 'Low'],
                ['id' => 2, 'title' => 'Medium'],
                ['id' => 3, 'title' => 'High'],
                ['id' => 4, 'title' => 'Urgent']
            ];

            echo json_encode([
                'success' => true,
                'data' => $priorities
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Debug endpoint to check authentication and session status
     * URL: /api/debug
     */
    function debug() {
        try {
            $login_user_id = $this->Users_model->login_user_id();
            
            echo json_encode([
                'success' => true,
                'debug_info' => [
                    'session_id' => session_id(),
                    'session_status' => session_status(),
                    'login_user_id' => $login_user_id,
                    'login_user_exists' => !empty($this->login_user),
                    'login_user_data' => $this->login_user ? [
                        'id' => $this->login_user->id,
                        'first_name' => $this->login_user->first_name,
                        'last_name' => $this->login_user->last_name,
                        'user_type' => $this->login_user->user_type
                    ] : null,
                    'session_data_keys' => array_keys($_SESSION ?? []),
                    'cookies' => array_keys($_COOKIE ?? []),
                    'request_headers' => getallheaders(),
                    'request_method' => $_SERVER['REQUEST_METHOD'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Debug failed: ' . $e->getMessage(),
                'debug_info' => [
                    'session_id' => session_id(),
                    'session_status' => session_status()
                ]
            ]);
        }
    }

    /**
     * Test endpoint to verify API is working
     * URL: /api/test
     */
    function test() {
        echo json_encode([
            'success' => true,
            'message' => 'API is working',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Simple test endpoint for projects
     * URL: /api/test_projects
     */
    function test_projects() {
        try {
            $login_user_id = $this->Users_model->login_user_id();
            if (!$login_user_id) {
                echo json_encode(['success' => false, 'error' => 'Not authenticated']);
                return;
            }
            
            // Get just first 3 projects
            $projects = $this->Projects_model->get_details([])->getResult();
            $limited_projects = array_slice($projects, 0, 3);
            
            $formatted = [];
            foreach ($limited_projects as $project) {
                $formatted[] = [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->status
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $formatted,
                'count' => count($formatted),
                'message' => 'Test endpoint working'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Debug endpoint for projects
     * URL: /api/debug_projects
     */
    function debug_projects() {
        try {
            $login_user_id = $this->Users_model->login_user_id();
            $current_user = $this->Users_model->get_one($login_user_id);
            
            // Get all projects without filtering
            $all_projects = $this->Projects_model->get_details([])->getResult();
            
            // Get projects with user filter
            $user_projects = [];
            if (!$current_user->is_admin && $current_user->user_type == "staff") {
                $user_projects = $this->Projects_model->get_details(["user_id" => $current_user->id])->getResult();
            }
            
            echo json_encode([
                'success' => true,
                'debug_info' => [
                    'user_id' => $login_user_id,
                    'user_name' => $current_user->first_name . ' ' . $current_user->last_name,
                    'is_admin' => $current_user->is_admin,
                    'user_type' => $current_user->user_type,
                    'all_projects_count' => count($all_projects),
                    'user_projects_count' => count($user_projects),
                    'all_projects' => array_map(function($p) { 
                        return ['id' => $p->id, 'title' => $p->title, 'status' => $p->status]; 
                    }, $all_projects),
                    'user_projects' => array_map(function($p) { 
                        return ['id' => $p->id, 'title' => $p->title, 'status' => $p->status]; 
                    }, $user_projects)
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get subtasks for a task
     * URL: /api/task/{taskId}/subtasks
     * Method: GET
     */
    function task_subtasks($taskId = null) {
        try {
            // Basic validation
            if (!$taskId || !is_numeric($taskId)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid task ID'
                ]);
                return;
            }

            // Check authentication
            $login_user_id = $this->Users_model->login_user_id();
            if (!$login_user_id) {
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Verify parent task exists and user has access
            $parent_task = $this->Tasks_model->get_one($taskId);
            if (!$parent_task) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Parent task not found'
                ]);
                return;
            }

            // Check if user has access to this task
            if ($parent_task->project_id) {
                $is_member = $this->Project_members_model->is_user_a_project_member($parent_task->project_id, $login_user_id);
                
                if (!$is_member && $parent_task->assigned_to != $login_user_id && 
                    !strpos($parent_task->collaborators, (string)$login_user_id)) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Access denied to this task'
                    ]);
                    return;
                }
            }

            // Send request to Node.js API
            $nodejs_url = 'https://api-dojob.rubyshop.co.th/api/task/' . $taskId . '/subtasks';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nodejs_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-User-ID: ' . $login_user_id
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                // Fallback to PHP implementation
                $subtasks = $this->Tasks_model->get_all_where([
                    'parent_task_id' => $taskId,
                    'deleted' => 0
                ], 1000, 0, 'sort ASC');

                $subtask_data = [];
                if ($subtasks && $subtasks->getNumRows() > 0) {
                    foreach ($subtasks->getResult() as $subtask) {
                        // Get assignee name if assigned
                        $assignee_name = '';
                        if ($subtask->assigned_to) {
                            $assignee = $this->Users_model->get_one($subtask->assigned_to);
                            if ($assignee) {
                                $assignee_name = trim($assignee->first_name . ' ' . $assignee->last_name);
                            }
                        }

                        $subtask_data[] = [
                            'id' => (int)$subtask->id,
                            'title' => $subtask->title ?? '',
                            'description' => $subtask->description ?? '',
                            'status_id' => (int)($subtask->status_id ?? 1),
                            'priority_id' => (int)($subtask->priority_id ?? 2),
                            'assigned_to' => (int)($subtask->assigned_to ?? 0),
                            'assignee_name' => $assignee_name,
                            'deadline' => $subtask->deadline ?? null,
                            'created_date' => $subtask->created_date ?? date('Y-m-d'),
                            'sort' => (int)($subtask->sort ?? 0)
                        ];
                    }
                }

                echo json_encode([
                    'success' => true,
                    'data' => $subtask_data
                ]);
                return;
            }

            if ($http_code !== 200) {
                // Fallback to PHP implementation
                $subtasks = $this->Tasks_model->get_all_where([
                    'parent_task_id' => $taskId,
                    'deleted' => 0
                ], 1000, 0, 'sort ASC');

                $subtask_data = [];
                if ($subtasks && $subtasks->getNumRows() > 0) {
                    foreach ($subtasks->getResult() as $subtask) {
                        // Get assignee name if assigned
                        $assignee_name = '';
                        if ($subtask->assigned_to) {
                            $assignee = $this->Users_model->get_one($subtask->assigned_to);
                            if ($assignee) {
                                $assignee_name = trim($assignee->first_name . ' ' . $assignee->last_name);
                            }
                        }

                        $subtask_data[] = [
                            'id' => (int)$subtask->id,
                            'title' => $subtask->title ?? '',
                            'description' => $subtask->description ?? '',
                            'status_id' => (int)($subtask->status_id ?? 1),
                            'priority_id' => (int)($subtask->priority_id ?? 2),
                            'assigned_to' => (int)($subtask->assigned_to ?? 0),
                            'assignee_name' => $assignee_name,
                            'deadline' => $subtask->deadline ?? null,
                            'created_date' => $subtask->created_date ?? date('Y-m-d'),
                            'sort' => (int)($subtask->sort ?? 0)
                        ];
                    }
                }

                echo json_encode([
                    'success' => true,
                    'data' => $subtask_data
                ]);
                return;
            }

            $nodejs_response = json_decode($response, true);
            
            if (!$nodejs_response) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid response from subtask service'
                ]);
                return;
            }

            // Return the Node.js response directly
            echo json_encode($nodejs_response);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create a new subtask
     * URL: /api/task/{taskId}/subtasks
     * Method: POST
     */
    function create_subtask($taskId = null) {
        try {
            // Basic validation
            if (!$taskId || !is_numeric($taskId)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid task ID: ' . $taskId
                ]);
                return;
            }

            // Check authentication
            $login_user_id = $this->Users_model->login_user_id();
            if (!$login_user_id) {
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Get and validate input
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid JSON input'
                ]);
                return;
            }

            $title = trim($input['title'] ?? '');
            if (empty($title)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Title is required'
                ]);
                return;
            }

            // Verify parent task exists and user has access
            $parent_task = $this->Tasks_model->get_one($taskId);
            if (!$parent_task) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Parent task not found'
                ]);
                return;
            }

            // Check if user has access to this task
            if ($parent_task->project_id) {
                $is_member = $this->Project_members_model->is_user_a_project_member($parent_task->project_id, $login_user_id);
                
                if (!$is_member && $parent_task->assigned_to != $login_user_id && 
                    !strpos($parent_task->collaborators, (string)$login_user_id)) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Access denied to this task'
                    ]);
                    return;
                }
            }

            // Prepare data for Node.js API
            $nodejs_data = [
                'title' => $title,
                'description' => $input['description'] ?? '',
                'user_id' => (int)$login_user_id
            ];

            // Send request to Node.js API
            $nodejs_url = 'https://api-dojob.rubyshop.co.th/api/task/' . $taskId . '/subtasks';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nodejs_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($nodejs_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to connect to subtask service. Please try again later.'
                ]);
                return;
            }

            if ($http_code !== 200) {
                // Try to get error message from response
                $error_response = json_decode($response, true);
                $error_message = isset($error_response['error']) ? $error_response['error'] : 'Subtask service error';
                
                echo json_encode([
                    'success' => false,
                    'error' => $error_message
                ]);
                return;
            }

            $nodejs_response = json_decode($response, true);
            
            if (!$nodejs_response) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid response from subtask service'
                ]);
                return;
            }

            // Return the Node.js response directly
            echo json_encode($nodejs_response);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update a subtask
     * URL: /api/subtask/{subtaskId}
     * Method: PUT
     */
    function update_subtask($subtaskId = null) {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Only handle PUT requests
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                http_response_code(405);
                echo json_encode([
                    'success' => false,
                    'error' => 'Method not allowed'
                ]);
                return;
            }

            // Validate subtask ID
            if (!$subtaskId || !is_numeric($subtaskId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid subtask ID'
                ]);
                return;
            }

            // Get PUT data
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid JSON data',
                    'debug' => [
                        'raw_input' => file_get_contents('php://input'),
                        'json_error' => json_last_error_msg()
                    ]
                ]);
                return;
            }

            // Verify subtask exists and user has access
            $subtask = $this->Tasks_model->get_one($subtaskId);
            
            if (!$subtask || $subtask->deleted) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Subtask not found'
                ]);
                return;
            }

            // Check if user has access to subtask
            if ($subtask->project_id) {
                $is_member = $this->Project_members_model->is_user_a_project_member($subtask->project_id, $this->login_user->id);
                
                if (!$is_member && $subtask->assigned_to != $this->login_user->id && 
                    !strpos($subtask->collaborators, (string)$this->login_user->id)) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Access denied to this subtask'
                    ]);
                    return;
                }
            }

            // Add user_id to the request data
            $input['user_id'] = (int)$this->login_user->id;

            // Send request to Node.js API
            $nodejs_url = 'https://api-dojob.rubyshop.co.th/api/subtask/' . $subtaskId;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nodejs_url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                // Fallback to PHP implementation
                $update_data = [];
                $allowed_fields = ['title', 'description', 'status_id', 'priority_id', 'assigned_to', 'deadline', 'sort'];
                
                foreach ($allowed_fields as $field) {
                    if (isset($input[$field])) {
                        $update_data[$field] = $input[$field];
                    }
                }

                if (empty($update_data)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'No valid fields to update',
                        'debug' => [
                            'input' => $input,
                            'allowed_fields' => $allowed_fields
                        ]
                    ]);
                    return;
                }

                // Update subtask
                $update_data['id'] = $subtaskId;
                $result = $this->Tasks_model->ci_save($update_data, $subtaskId);

                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'message' => 'Subtask updated successfully'
                        ]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to update subtask'
                    ]);
                }
                return;
            }

            if ($http_code !== 200) {
                // Fallback to PHP implementation
                $update_data = [];
                $allowed_fields = ['title', 'description', 'status_id', 'priority_id', 'assigned_to', 'deadline', 'sort'];
                
                foreach ($allowed_fields as $field) {
                    if (isset($input[$field])) {
                        $update_data[$field] = $input[$field];
                    }
                }

                if (empty($update_data)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'No valid fields to update',
                        'debug' => [
                            'input' => $input,
                            'allowed_fields' => $allowed_fields,
                            'http_code' => $http_code
                        ]
                    ]);
                    return;
                }

                // Update subtask
                $update_data['id'] = $subtaskId;
                $result = $this->Tasks_model->ci_save($update_data, $subtaskId);

                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'message' => 'Subtask updated successfully'
                        ]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to update subtask'
                    ]);
                }
                return;
            }

            $nodejs_response = json_decode($response, true);
            
            if (!$nodejs_response) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid response from subtask service'
                ]);
                return;
            }

            // Return the Node.js response directly
            echo json_encode($nodejs_response);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete a subtask
     * URL: /api/subtask/{subtaskId}
     * Method: DELETE
     */
    function delete_subtask($subtaskId = null) {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Only handle DELETE requests
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                http_response_code(405);
                echo json_encode([
                    'success' => false,
                    'error' => 'Method not allowed'
                ]);
                return;
            }

            // Validate subtask ID
            if (!$subtaskId || !is_numeric($subtaskId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid subtask ID'
                ]);
                return;
            }

            // Verify subtask exists and user has access
            $subtask = $this->Tasks_model->get_one($subtaskId);
            
            if (!$subtask || $subtask->deleted) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Subtask not found'
                ]);
                return;
            }

            // Check if user has access to subtask
            if ($subtask->project_id) {
                $is_member = $this->Project_members_model->is_user_a_project_member($subtask->project_id, $this->login_user->id);
                
                if (!$is_member && $subtask->assigned_to != $this->login_user->id && 
                    !strpos($subtask->collaborators, (string)$this->login_user->id)) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Access denied to this subtask'
                    ]);
                    return;
                }
            }

            // Prepare data for Node.js
            $delete_data = [
                'user_id' => (int)$this->login_user->id
            ];

            // Send request to Node.js API
            $nodejs_url = 'https://api-dojob.rubyshop.co.th/api/subtask/' . $subtaskId;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nodejs_url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($delete_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                // Fallback to PHP implementation
                $result = $this->Tasks_model->ci_save(['deleted' => 1], $subtaskId);

                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'message' => 'Subtask deleted successfully'
                        ]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to delete subtask'
                    ]);
                }
                return;
            }

            if ($http_code !== 200) {
                // Fallback to PHP implementation
                $result = $this->Tasks_model->ci_save(['deleted' => 1], $subtaskId);

                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'message' => 'Subtask deleted successfully'
                        ]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to delete subtask'
                    ]);
                }
                return;
            }

            $nodejs_response = json_decode($response, true);
            
            if (!$nodejs_response) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid response from subtask service'
                ]);
                return;
            }

            // Return the Node.js response directly
            echo json_encode($nodejs_response);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Log activity for a task
     * URL: /api/task/{taskId}/activity
     * Method: POST
     */
    function task_activity($taskId = null) {
        try {
            // Check if user is logged in
            if (!$this->login_user || !$this->login_user->id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not authenticated'
                ]);
                return;
            }

            // Only handle POST requests
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode([
                    'success' => false,
                    'error' => 'Method not allowed'
                ]);
                return;
            }

            // Validate task ID
            if (!$taskId || !is_numeric($taskId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid task ID'
                ]);
                return;
            }

            // Get POST data
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ]);
                return;
            }

            // Load required models
            // Already loaded in parent constructor
            
            // Verify task exists and user has access
            $task = $this->Tasks_model->get_one($taskId);
            
            if (!$task) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Task not found'
                ]);
                return;
            }

            // Check if user has access to this task
            if ($task->project_id) {
                // Already loaded in parent constructor
                $is_member = $this->Project_members_model->is_user_a_project_member($task->project_id, $this->login_user->id);
                
                if (!$is_member && $task->assigned_to != $this->login_user->id && 
                    !strpos($task->collaborators, (string)$this->login_user->id)) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Access denied to this task'
                    ]);
                    return;
                }
            }

            // Prepare activity log data
            $activity_data = [
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $this->login_user->id,
                'action' => $input['action'] ?? 'updated',
                'log_type' => 'tasks',
                'log_type_title' => $task->title,
                'log_type_id' => (int)$taskId,
                'changes' => $input['changes'] ?? '',
                'log_for' => 'tasks',
                'log_for_id' => (int)$taskId,
                'log_for2' => 'projects',
                'log_for_id2' => (int)$task->project_id,
                'deleted' => 0
            ];

            // Save activity log to database
            $log_id = $this->Activity_logs_model->ci_save($activity_data);

            if ($log_id) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'log_id' => $log_id,
                        'message' => 'Activity logged successfully'
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to save activity log'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get projects list for dropdown
     * URL: /api/projects
     * Method: GET
     */
    function projects() {
        try {
            // Debug: Log the start of the function
            error_log("API projects() method called");
            
            // Check authentication
            $login_user_id = $this->Users_model->login_user_id();
            if (!$login_user_id) {
                error_log("API projects() - No login user ID");
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Authentication required'
                ]);
                return;
            }

            error_log("API projects() - User ID: " . $login_user_id);

            // Get current user
            $current_user = $this->Users_model->get_one($login_user_id);
            if (!$current_user->id) {
                error_log("API projects() - Invalid user");
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid user'
                ]);
                return;
            }

            error_log("API projects() - User: " . $current_user->first_name . " " . $current_user->last_name . " (Admin: " . ($current_user->is_admin ? 'Yes' : 'No') . ")");

            // Build projects query options
            $projects_options = array();
            
            // If not admin, only show projects user has access to
            if (!$current_user->is_admin && $current_user->user_type == "staff") {
                $projects_options["user_id"] = $current_user->id;
                error_log("API projects() - Non-admin user, filtering by user_id: " . $current_user->id);
                
                // Also try to get projects where user is a member (alternative approach)
                $member_projects = $this->Project_members_model->get_details([
                    "user_id" => $current_user->id,
                    "deleted" => 0
                ])->getResult();
                
                if (count($member_projects) > 0) {
                    $project_ids = array_map(function($member) {
                        return $member->project_id;
                    }, $member_projects);
                    
                    // Override the user_id filter with project IDs
                    unset($projects_options["user_id"]);
                    $projects_options["project_ids"] = $project_ids;
                    error_log("API projects() - Found " . count($member_projects) . " project memberships, using project IDs: " . implode(',', $project_ids));
                }
            } else {
                error_log("API projects() - Admin user or client, showing all projects");
            }
            
            error_log("API projects() - Query options: " . json_encode($projects_options));
            
            // Get projects
            $projects = $this->Projects_model->get_details($projects_options)->getResult();
            
            error_log("API projects() - Found " . count($projects) . " projects");
            
            // Limit to first 50 projects to avoid response size issues
            if (count($projects) > 50) {
                $projects = array_slice($projects, 0, 50);
                error_log("API projects() - Limited to first 50 projects");
            }
            
            // If no projects found for staff user, try getting all projects as fallback
            if (count($projects) == 0 && !$current_user->is_admin && $current_user->user_type == "staff") {
                error_log("API projects() - No projects found for staff user, trying all projects as fallback");
                $all_projects = $this->Projects_model->get_details([])->getResult();
                $projects = array_slice($all_projects, 0, 50); // Also limit fallback
                error_log("API projects() - Fallback found " . count($projects) . " projects (limited)");
            }
            
            // Format projects for dropdown
            $formatted_projects = array();
            foreach ($projects as $project) {
                $formatted_projects[] = array(
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->status,
                    'description' => '' // Temporarily remove description to avoid character_limiter issues
                );
                // Only log first few projects to avoid log spam
                if (count($formatted_projects) <= 5) {
                    error_log("API projects() - Added project: " . $project->id . " - " . $project->title);
                }
            }

            $response = [
                'success' => true,
                'data' => $formatted_projects,
                'count' => count($formatted_projects)
            ];
            
            error_log("API projects() - Returning " . count($formatted_projects) . " projects");
            
            // Set proper headers
            header('Content-Type: application/json');
            echo json_encode($response);

        } catch (Exception $e) {
            error_log("API projects() - Exception: " . $e->getMessage());
            error_log("API projects() - Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }
}
?>