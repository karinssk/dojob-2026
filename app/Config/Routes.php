<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Dashboard');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Dashboard::index');

// Project Management Routes - Must come before generic controller routing
$routes->get('projectManagement', 'ProjectManagement::index');
$routes->get('projects/(:num)/list', 'ProjectManagement::task_list/$1');
$routes->post('projects/update_task_status', 'ProjectManagement::update_task_status');
$routes->post('projects/update_task_hierarchy', 'ProjectManagement::update_task_hierarchy');

// Task List Routes
$routes->get('task_list', 'Task_list::index');
$routes->get('task-list', 'Task_list::index'); // Alternative format
$routes->post('task_list/set_project', 'Task_list::set_project');

// Workflow Routes
$routes->get('workflow', 'Workflow::index');
$routes->get('workflow/create', 'Workflow::create');
$routes->get('workflow/edit/(:num)', 'Workflow::edit/$1');
$routes->post('workflow/save', 'Workflow::save');
$routes->post('workflow/delete', 'Workflow::delete');
$routes->post('workflow/toggle_status', 'Workflow::toggle_status');

//custom routing for custom pages
//this route will move 'about/any-text' to 'domain.com/about/index/any-text'
$routes->add('about/(:any)', 'About::index/$1');

//add routing for controllers
$excluded_controllers = array("About", "App_Controller", "Security_Controller");
$controller_dropdown = array();
$dir = "./app/Controllers/";
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $controller_name = substr($file, 0, -4);
            if ($file && $file != "." && $file != ".." && $file != "index.html" && $file != ".gitkeep" && !in_array($controller_name, $excluded_controllers)) {
                $controller_dropdown[] = $controller_name;
            }
        }
        closedir($dh);
    }
}

foreach ($controller_dropdown as $controller) {
    $routes->get(strtolower($controller), "$controller::index");
    $routes->get(strtolower($controller) . '/(:any)', "$controller::$1");
    $routes->post(strtolower($controller) . '/(:any)', "$controller::$1");
}

//add uppercase links
$routes->get("Plugins", "Plugins::index");
$routes->get("Plugins/(:any)", "Plugins::$1");
$routes->post("Plugins/(:any)", "Plugins::$1");

$routes->get("Updates", "Updates::index");
$routes->get("Updates/(:any)", "Updates::$1");
$routes->post("Updates/(:any)", "Updates::$1");

$routes->get('apitest', 'ApiTest::index');
$routes->get('apitest/current_user', 'ApiTest::current_user');
$routes->get('apitest/debug', 'ApiTest::debug');
$routes->get('api-test', 'ApiTest::index');
$routes->get('api-test/current-user', 'ApiTest::current_user');
$routes->get('api-test/debug', 'ApiTest::debug');


$routes->get('table_preferences/(:any)', 'Table_preferences::$1');
$routes->post('table_preferences/(:any)', 'Table_preferences::$1');



$routes->get('payslips', 'Payslips::index');
$routes->get('payslips/create', 'Payslips::create');
$routes->post('payslips/store', 'Payslips::store');
$routes->get('payslips/delete/(:num)', 'Payslips::delete/$1');
$routes->get('payslips/view/(:num)', 'Payslips::view/$1');
$routes->get('payslips/print/(:num)', 'Payslips::print/$1');
$routes->get('payslips/pdf/(:num)', 'Payslips::downloadPdf/$1');
$routes->get('payslips/test_db', 'Payslips::test_db');
$routes->post('payslips/test_form', 'Payslips::test_form');
$routes->get('payslips/createEmployees', 'Payslips::createEmployees');
$routes->post('payslips/storeEmployee', 'Payslips::storeEmployee');
$routes->get('payslips/listEmployees', 'Payslips::listEmployees');
$routes->post('payslips/updateEmployeeLoan', 'Payslips::updateEmployeeLoan');
$routes->get('payslips/deleteEmployee/(:num)', 'Payslips::deleteEmployee/$1');

// System Settings Routes
$routes->get('systemSettings', 'SystemSettings::index');

// Video Streaming Routes
$routes->get('video_stream/stream/(:any)', 'VideoStream::stream/$1');

// Task update routes
$routes->post('tasks/update_task_info', 'Tasks::update_task_info');
$routes->post('tasks/update_task_title', 'Tasks::update_task_title');
$routes->post('tasks/save_task_status/(:num)', 'Tasks::save_task_status/$1');
$routes->post('tasks/save_task_status', 'Tasks::save_task_status');
$routes->post('tasks/update_task_status', 'Tasks::save_task_status');

// Kanban Board Routes
$routes->get('tasks/get_kanban_tasks', 'Tasks::get_kanban_tasks');
$routes->post('tasks/update_kanban_task_status', 'Tasks::update_kanban_task_status');
$routes->post('tasks/kanban_update_status/(:num)', 'Tasks::kanban_update_status/$1');
$routes->post('tasks/simple_status_update/(:num)', 'Tasks::simple_status_update/$1');
$routes->get('tasks/test_kanban_api', 'Tasks::test_kanban_api');
$routes->get('tasks/test_kanban_connection', 'Tasks::test_kanban_connection');
$routes->post('tasks/test_kanban_connection', 'Tasks::test_kanban_connection');

// API Routes
$routes->group('api', function($routes) {
    // Authentication endpoints
    $routes->get('auth_check', 'Api::auth_check');
    $routes->get('auth-check', 'Api::auth_check'); // Alternative format
    
    // User endpoints
    $routes->get('current_user', 'Api::current_user');
    $routes->get('current-user', 'Api::current_user'); // Alternative format
    $routes->get('user/(:num)', 'Api::user/$1');
    $routes->get('user', 'Api::current_user'); // Alias for current_user
    $routes->get('users', 'Api::users'); // Get all users for dropdowns
    
    // Project endpoints
    $routes->get('projects', 'Api::projects'); // Get all projects for dropdowns
    
    // Task metadata endpoints
    $routes->get('task-statuses', 'Api::task_statuses');
    $routes->get('task_statuses', 'Api::task_statuses'); // Alternative format
    $routes->get('task-priorities', 'Api::task_priorities');
    $routes->get('task_priorities', 'Api::task_priorities'); // Alternative format
    
    // Task endpoints
    $routes->get('task/(:num)', 'Api::task/$1');
    $routes->delete('task/(:num)/image/(:any)', 'Api::delete_task_image/$1/$2');
    
    // Subtask endpoints
    $routes->get('task/(:num)/subtasks', 'Api::task_subtasks/$1');
    $routes->post('task/(:num)/subtasks', 'Api::create_subtask/$1');
    $routes->get('task_subtasks/(:num)', 'Api::task_subtasks/$1'); // Fallback route
    $routes->post('create_subtask/(:num)', 'Api::create_subtask/$1'); // Fallback route
    
    // Individual subtask operations
    $routes->put('subtask/(:num)', 'Api::update_subtask/$1');
    $routes->delete('subtask/(:num)', 'Api::delete_subtask/$1');
    $routes->put('update_subtask/(:num)', 'Api::update_subtask/$1'); // Fallback route
    $routes->delete('delete_subtask/(:num)', 'Api::delete_subtask/$1'); // Fallback route
    
    // Activity logging
    $routes->post('task/(:num)/activity', 'Api::task_activity/$1');
    $routes->post('task_activity/(:num)', 'Api::task_activity/$1'); // Fallback route
    $routes->post('activity/task/(:num)', 'Api::task_activity/$1'); // Alternative route
    
    // Comment endpoints
    $routes->delete('comment/(:num)', 'Api::delete_comment/$1');
    
    // Test endpoint
    $routes->get('test', 'Api::test');
    $routes->get('debug', 'Api::debug');
    $routes->get('test_projects', 'Api::test_projects');
    $routes->get('debug_projects', 'Api::debug_projects');
});

// LINE Webhook Routes (outside API group for security)
$routes->post('line_webhook', 'Line_notify::webhook');
$routes->post('line/v1/webhook', 'Line_notify::webhook');
$routes->get('tasks-tracking', 'Tasks_tracking::index');
$routes->post('tasks-tracking/save', 'Tasks_tracking::save');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
