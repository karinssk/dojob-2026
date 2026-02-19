<?php

namespace App\Controllers;

/**
 * Liff_app — Protected LIFF app pages.
 * All methods require an active session (LIFF or normal web login).
 */
class Liff_app extends Security_Controller {

    protected $Liff_pending_model;

    public function __construct() {
        parent::__construct();
        $this->Liff_pending_model = model('App\Models\Liff_pending_model');
    }

    // Override Security_Controller redirect to go to LIFF login instead of web signin
    protected function _check_login() {
        $user_id = $this->Users_model->login_user_id();
        if (!$user_id) {
            app_redirect('liff');
        }
        return $user_id;
    }

    private function _liff_view($view, $data = []) {
        $data['login_user'] = $this->login_user;
        $content = view($view, $data);
        return view('liff_app/layout', array_merge($data, ['content' => $content]));
    }

    // ── Dashboard ──────────────────────────────────────────────────
    public function dashboard() {
        $user_id = $this->login_user->id;

        // Summary counts
        $tasks_due_today = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM rise_tasks
             WHERE assigned_to=? AND deleted=0 AND DATE(deadline)=CURDATE()",
            [$user_id]
        )->getRow()->cnt ?? 0;

        $events_today = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM rise_events
             WHERE deleted=0 AND start_date=CURDATE()
             AND (created_by=? OR FIND_IN_SET(?,share_with) OR share_with LIKE '%all_team%')",
            [$user_id, $user_id]
        )->getRow()->cnt ?? 0;

        $todos_pending = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM rise_to_do
             WHERE created_by=? AND deleted=0 AND status!='done'",
            [$user_id]
        )->getRow()->cnt ?? 0;

        $overdue_tasks = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM rise_tasks
             WHERE assigned_to=? AND deleted=0 AND deadline < NOW()
             AND status_id NOT IN (SELECT id FROM rise_task_status WHERE key_name='closed')",
            [$user_id]
        )->getRow()->cnt ?? 0;

        // Recent tasks
        $recent_tasks = $this->db->query(
            "SELECT t.*, ts.title AS status_title, ts.color AS status_color,
                    tp.title AS priority_title, tp.color AS priority_color
             FROM rise_tasks t
             LEFT JOIN rise_task_status ts ON ts.id = t.status_id
             LEFT JOIN rise_task_priority tp ON tp.id = t.priority_id
             WHERE t.assigned_to=? AND t.deleted=0
             ORDER BY t.deadline ASC
             LIMIT 5",
            [$user_id]
        )->getResult();

        return $this->_liff_view('liff_app/dashboard', [
            'page_title'     => 'Dashboard',
            'active_tab'     => '',
            'tasks_due_today'=> $tasks_due_today,
            'events_today'   => $events_today,
            'todos_pending'  => $todos_pending,
            'overdue_tasks'  => $overdue_tasks,
            'recent_tasks'   => $recent_tasks,
        ]);
    }

    // ── Tasks ──────────────────────────────────────────────────────
    public function tasks() {
        $user_id = $this->login_user->id;
        $filter  = $this->request->getGet('filter') ?: 'mine';

        $where = $filter === 'assigned_by_me'
            ? "t.created_by=$user_id"
            : "t.assigned_to=$user_id";

        $tasks = $this->db->query(
            "SELECT t.*, ts.title AS status_title, ts.color AS status_color,
                    tp.title AS priority_title, tp.color AS priority_color,
                    CONCAT(u.first_name,' ',u.last_name) AS assigned_name, u.image AS assigned_img,
                    p.title AS project_title
             FROM rise_tasks t
             LEFT JOIN rise_task_status ts ON ts.id = t.status_id
             LEFT JOIN rise_task_priority tp ON tp.id = t.priority_id
             LEFT JOIN rise_users u ON u.id = t.assigned_to
             LEFT JOIN rise_projects p ON p.id = t.project_id
             WHERE $where AND t.deleted=0
             ORDER BY t.deadline ASC, t.id DESC
             LIMIT 50"
        )->getResult();

        $statuses = $this->Task_status_model->get_details()->getResult();

        return $this->_liff_view('liff_app/tasks/index', [
            'page_title' => 'Tasks',
            'active_tab' => 'tasks',
            'fab_url'    => get_uri('liff/app/tasks/create'),
            'tasks'      => $tasks,
            'statuses'   => $statuses,
            'filter'     => $filter,
        ]);
    }

    public function task_create() {
        return $this->_task_form(0);
    }

    public function task_edit($task_id) {
        return $this->_task_form((int)$task_id);
    }

    public function task_detail($task_id) {
        $task = $this->db->query(
            "SELECT t.*, ts.title AS status_title, ts.color AS status_color,
                    tp.title AS priority_title,
                    CONCAT(u.first_name,' ',u.last_name) AS assigned_name, u.image AS assigned_img,
                    p.title AS project_title
             FROM rise_tasks t
             LEFT JOIN rise_task_status ts ON ts.id = t.status_id
             LEFT JOIN rise_task_priority tp ON tp.id = t.priority_id
             LEFT JOIN rise_users u ON u.id = t.assigned_to
             LEFT JOIN rise_projects p ON p.id = t.project_id
             WHERE t.id=? AND t.deleted=0",
            [$task_id]
        )->getRow();

        if (!$task) { app_redirect('liff/app/tasks'); }

        $activity = $this->db->query(
            "SELECT al.*, CONCAT(u.first_name,' ',u.last_name) AS user_name, u.image AS user_img
             FROM rise_activity_logs al
             LEFT JOIN rise_users u ON u.id = al.log_for_user_id
             WHERE al.log_for='task' AND al.log_for_id=?
             ORDER BY al.id DESC LIMIT 20",
            [$task_id]
        )->getResult();

        $statuses = $this->Task_status_model->get_details()->getResult();

        return $this->_liff_view('liff_app/tasks/detail', [
            'page_title' => $task->title,
            'active_tab' => 'tasks',
            'task'       => $task,
            'activity'   => $activity,
            'statuses'   => $statuses,
        ]);
    }

    private function _task_form($task_id) {
        $task     = $task_id ? $this->Tasks_model->get_one($task_id) : new \stdClass();
        $users    = $this->_get_staff_list();
        $projects = $this->_get_user_projects();
        $statuses = $this->Task_status_model->get_details()->getResult();
        $priorities = $this->Task_priority_model->get_details()->getResult() ?? [];

        return $this->_liff_view('liff_app/tasks/form', [
            'page_title' => $task_id ? 'แก้ไข Task' : 'สร้าง Task',
            'active_tab' => 'tasks',
            'task'       => $task,
            'users'      => $users,
            'projects'   => $projects,
            'statuses'   => $statuses,
            'priorities' => $priorities,
        ]);
    }

    // ── Events ─────────────────────────────────────────────────────
    public function events() {
        $user_id = $this->login_user->id;
        $view    = $this->request->getGet('view') ?: 'today';

        $date_where = match ($view) {
            'week'  => "e.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
            'all'   => "e.start_date >= CURDATE()",
            default => "e.start_date = CURDATE()",
        };

        $events = $this->db->query(
            "SELECT e.*, CONCAT(u.first_name,' ',u.last_name) AS creator_name
             FROM rise_events e
             LEFT JOIN rise_users u ON u.id = e.created_by
             WHERE e.deleted=0 AND $date_where
             AND (e.created_by=$user_id OR e.share_with LIKE '%all_team%' OR e.share_with LIKE '%$user_id%')
             ORDER BY e.start_date ASC, e.start_time ASC
             LIMIT 30"
        )->getResult();

        return $this->_liff_view('liff_app/events/index', [
            'page_title' => 'Events',
            'active_tab' => 'events',
            'fab_url'    => get_uri('liff/app/events/create'),
            'events'     => $events,
            'view'       => $view,
        ]);
    }

    public function event_create() {
        return $this->_event_form(0);
    }

    public function event_edit($event_id) {
        return $this->_event_form((int)$event_id);
    }

    public function event_detail($event_id) {
        $event = $this->db->query(
            "SELECT e.*, CONCAT(u.first_name,' ',u.last_name) AS creator_name
             FROM rise_events e LEFT JOIN rise_users u ON u.id=e.created_by
             WHERE e.id=? AND e.deleted=0",
            [$event_id]
        )->getRow();
        if (!$event) { app_redirect('liff/app/events'); }

        return $this->_liff_view('liff_app/events/detail', [
            'page_title' => $event->title,
            'active_tab' => 'events',
            'event'      => $event,
        ]);
    }

    private function _event_form($event_id) {
        $event = $event_id ? $this->Events_model->get_one($event_id) : new \stdClass();
        $users = $this->_get_staff_list();
        return $this->_liff_view('liff_app/events/form', [
            'page_title' => $event_id ? 'แก้ไข Event' : 'สร้าง Event',
            'active_tab' => 'events',
            'event'      => $event,
            'users'      => $users,
        ]);
    }

    // ── Projects ───────────────────────────────────────────────────
    public function projects() {
        $user_id  = $this->login_user->id;
        $projects = $this->db->query(
            "SELECT p.*,
                    ps.title AS status_title, ps.color AS status_color,
                    COUNT(DISTINCT pm.user_id) AS member_count,
                    COUNT(DISTINCT t.id) AS task_count,
                    SUM(CASE WHEN ts.key_name='closed' THEN 1 ELSE 0 END) AS done_count
             FROM rise_projects p
             LEFT JOIN rise_project_members pm2 ON pm2.project_id=p.id AND pm2.user_id=$user_id
             LEFT JOIN rise_project_status ps ON ps.id=p.status_id
             LEFT JOIN rise_project_members pm ON pm.project_id=p.id
             LEFT JOIN rise_tasks t ON t.project_id=p.id AND t.deleted=0
             LEFT JOIN rise_task_status ts ON ts.id=t.status_id
             WHERE p.deleted=0 AND (p.created_by=$user_id OR pm2.id IS NOT NULL)
             GROUP BY p.id
             ORDER BY p.created_date DESC
             LIMIT 30"
        )->getResult();

        return $this->_liff_view('liff_app/projects/index', [
            'page_title' => 'Projects',
            'active_tab' => 'projects',
            'projects'   => $projects,
        ]);
    }

    public function project_detail($project_id) {
        $project = $this->Projects_model->get_one($project_id);
        if (!$project || $project->deleted) { app_redirect('liff/app/projects'); }

        $members = $this->db->query(
            "SELECT u.id, CONCAT(u.first_name,' ',u.last_name) AS name, u.image
             FROM rise_project_members pm
             JOIN rise_users u ON u.id=pm.user_id
             WHERE pm.project_id=?",
            [$project_id]
        )->getResult();

        $tasks = $this->db->query(
            "SELECT t.*, ts.title AS status_title, ts.color AS status_color,
                    CONCAT(u.first_name,' ',u.last_name) AS assigned_name
             FROM rise_tasks t
             LEFT JOIN rise_task_status ts ON ts.id=t.status_id
             LEFT JOIN rise_users u ON u.id=t.assigned_to
             WHERE t.project_id=? AND t.deleted=0
             ORDER BY t.deadline ASC, t.sort ASC",
            [$project_id]
        )->getResult();

        $total     = count($tasks);
        $done      = count(array_filter($tasks, fn($t) => ($t->status_key_name ?? '') === 'closed'));
        $progress  = $total > 0 ? round($done / $total * 100) : 0;

        $activity = $this->db->query(
            "SELECT al.*, CONCAT(u.first_name,' ',u.last_name) AS user_name
             FROM rise_activity_logs al
             LEFT JOIN rise_users u ON u.id=al.log_for_user_id
             WHERE al.log_for='project' AND al.log_for_id=?
             ORDER BY al.id DESC LIMIT 15",
            [$project_id]
        )->getResult();

        return $this->_liff_view('liff_app/projects/detail', [
            'page_title' => $project->title,
            'active_tab' => 'projects',
            'fab_url'    => get_uri('liff/app/projects/' . $project_id . '/task/create'),
            'project'    => $project,
            'members'    => $members,
            'tasks'      => $tasks,
            'progress'   => $progress,
            'activity'   => $activity,
        ]);
    }

    public function project_task_create($project_id) {
        $task = new \stdClass();
        $task->project_id = $project_id;
        $users    = $this->_get_staff_list();
        $projects = $this->_get_user_projects();
        $statuses = $this->Task_status_model->get_details()->getResult();
        $priorities = $this->Task_priority_model->get_details()->getResult() ?? [];

        return $this->_liff_view('liff_app/tasks/form', [
            'page_title' => 'สร้าง Task',
            'active_tab' => 'projects',
            'task'       => $task,
            'users'      => $users,
            'projects'   => $projects,
            'statuses'   => $statuses,
            'priorities' => $priorities,
        ]);
    }

    // ── Todo ───────────────────────────────────────────────────────
    public function todo() {
        $user_id = $this->login_user->id;
        $todos   = $this->Todo_model->get_details(['created_by' => $user_id])->getResult();

        return $this->_liff_view('liff_app/todo/index', [
            'page_title' => 'To-Do',
            'active_tab' => 'todo',
            'todos'      => $todos,
        ]);
    }

    // ── Profile ────────────────────────────────────────────────────
    public function profile() {
        $user_id = $this->login_user->id;
        $map_t   = get_user_mappings_table();
        $mapping = $this->db->query(
            "SELECT * FROM $map_t WHERE rise_user_id=? AND is_active=1 LIMIT 1",
            [$user_id]
        )->getRow();

        return $this->_liff_view('liff_app/profile/index', [
            'page_title' => 'Profile',
            'active_tab' => 'profile',
            'mapping'    => $mapping,
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────
    private function _get_staff_list() {
        return $this->db->query(
            "SELECT id, first_name, last_name, image FROM rise_users
             WHERE status='active' AND deleted=0 AND user_type='staff'
             ORDER BY first_name"
        )->getResult();
    }

    private function _get_user_projects() {
        $user_id = $this->login_user->id;
        return $this->db->query(
            "SELECT p.id, p.title FROM rise_projects p
             LEFT JOIN rise_project_members pm ON pm.project_id=p.id AND pm.user_id=$user_id
             WHERE p.deleted=0 AND (p.created_by=$user_id OR pm.id IS NOT NULL)
             ORDER BY p.title"
        )->getResult();
    }
}
