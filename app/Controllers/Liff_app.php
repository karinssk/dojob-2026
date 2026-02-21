<?php

namespace App\Controllers;

/**
 * Liff_app — Protected LIFF app pages.
 * All methods require an active session (LIFF or normal web login).
 */
class Liff_app extends Security_Controller {

    protected $Liff_pending_model;
    protected $Task_priority_model;
    protected $Event_comments_model;

    public function __construct() {
        parent::__construct();
        $this->db = \Config\Database::connect();
        $this->Liff_pending_model = model('App\Models\Liff_pending_model');
        $this->Task_priority_model = model('App\Models\Task_priority_model');
        $this->Event_comments_model = model('App\Models\Event_comments_model');
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
            "SELECT COUNT(*) AS cnt FROM rise_tasks t
             LEFT JOIN rise_task_status ts ON ts.id = t.status_id
             WHERE t.assigned_to=? AND t.deleted=0
             AND t.deadline IS NOT NULL AND t.deadline > '0000-00-00' AND t.deadline < CURDATE()
             AND (ts.key_name IS NULL OR ts.key_name != 'done')",
            [$user_id]
        )->getRow()->cnt ?? 0;

        $pending_tasks = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM rise_tasks t
             LEFT JOIN rise_task_status ts ON ts.id = t.status_id
             WHERE t.assigned_to=? AND t.deleted=0
             AND (ts.key_name IS NULL OR ts.key_name != 'done')",
            [$user_id]
        )->getRow()->cnt ?? 0;

        $total_tasks = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM rise_tasks
             WHERE assigned_to=? AND deleted=0",
            [$user_id]
        )->getRow()->cnt ?? 0;

        $done_tasks = max(0, $total_tasks - $pending_tasks);
        $progress_pct = $total_tasks > 0 ? round(($done_tasks / $total_tasks) * 100) : 0;

        // Recent tasks (not done, ordered by deadline)
        $recent_tasks = $this->db->query(
            "SELECT t.*, ts.title AS status_title, ts.color AS status_color,
                    tp.title AS priority_title,
                    p.title AS project_title
             FROM rise_tasks t
             LEFT JOIN rise_task_status ts ON ts.id = t.status_id
             LEFT JOIN rise_task_priority tp ON tp.id = t.priority_id
             LEFT JOIN rise_projects p ON p.id = t.project_id
             WHERE t.assigned_to=? AND t.deleted=0
               AND (ts.key_name IS NULL OR ts.key_name != 'done')
             ORDER BY t.deadline ASC
             LIMIT 5",
            [$user_id]
        )->getResult();

        return $this->_liff_view('liff_app/dashboard', [
            'page_title'     => 'Dashboard',
            'active_tab'     => 'dashboard',
            'tasks_due_today'=> $tasks_due_today,
            'events_today'   => $events_today,
            'todos_pending'  => $todos_pending,
            'overdue_tasks'  => $overdue_tasks,
            'pending_tasks'  => $pending_tasks,
            'total_tasks'    => $total_tasks,
            'done_tasks'     => $done_tasks,
            'progress_pct'   => $progress_pct,
            'recent_tasks'   => $recent_tasks,
        ]);
    }

    // ── Tasks ──────────────────────────────────────────────────────
    public function tasks() {
        $user_id   = $this->login_user->id;
        $filter    = $this->request->getGet('filter') ?: 'mine';
        $status_id = (int)($this->request->getGet('status_id') ?: 0);
        $overdue   = $this->request->getGet('overdue') ? 1 : 0;

        $where = $filter === 'assigned_by_me'
            ? "t.created_by=$user_id"
            : "t.assigned_to=$user_id";

        if ($status_id) {
            $where .= " AND t.status_id=$status_id";
        } else {
            // Default: hide done tasks; user must explicitly tap Done chip to see them
            $where .= " AND (ts.key_name IS NULL OR ts.key_name != 'done')";
        }
        if ($overdue) $where .= " AND t.deadline IS NOT NULL AND t.deadline > '0000-00-00' AND t.deadline < CURDATE()";

        $order = $overdue ? "t.deadline ASC" : "t.deadline ASC, t.id DESC";

        $tasks = $this->db->query(
            "SELECT t.*, ts.title AS status_title, ts.color AS status_color, ts.key_name AS status_key,
                    tp.title AS priority_title, tp.color AS priority_color,
                    CONCAT(u.first_name,' ',u.last_name) AS assigned_name, u.image AS assigned_img,
                    p.title AS project_title
             FROM rise_tasks t
             LEFT JOIN rise_task_status ts ON ts.id = t.status_id
             LEFT JOIN rise_task_priority tp ON tp.id = t.priority_id
             LEFT JOIN rise_users u ON u.id = t.assigned_to
             LEFT JOIN rise_projects p ON p.id = t.project_id
             WHERE $where AND t.deleted=0
             ORDER BY $order
             LIMIT 100"
        )->getResult();

        $this->_attach_task_comment_files($tasks);

        $statuses = $this->Task_status_model->get_details()->getResult();

        // For มอบหมาย tab: staff list + default project for Quick Assign panel
        $staff_users     = [];
        $quick_assign_defaults = [];
        if ($filter === 'assigned_by_me') {
            $staff_users = $this->_get_staff_list();
            $def_project = $this->_get_default_monthly_project(0);

            // Find "To Do" status id
            $todo_status_id = 0;
            foreach ($statuses as $s) {
                if (($s->key_name ?? '') === 'to_do') { $todo_status_id = $s->id; break; }
            }

            $quick_assign_defaults = [
                'project_id'  => $def_project->id,
                'project_name'=> $def_project->title,
                'start_date'  => date('Y-m-d'),
                'start_time'  => '09:00',
                'deadline'    => date('Y-m-d'),
                'end_time'    => '17:30',
                'status_id'   => $todo_status_id,
            ];
        }

        return $this->_liff_view('liff_app/tasks/index', [
            'page_title'            => 'Tasks',
            'active_tab'            => 'tasks',
            'fab_url'               => get_uri('liff/app/tasks/create'),
            'tasks'                 => $tasks,
            'statuses'              => $statuses,
            'filter'                => $filter,
            'status_id'             => $status_id,
            'overdue'               => $overdue,
            'staff_users'           => $staff_users,
            'quick_assign_defaults' => $quick_assign_defaults,
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
             LEFT JOIN rise_users u ON u.id = al.created_by
             WHERE al.log_for='task' AND al.log_for_id=?
             ORDER BY al.id DESC LIMIT 20",
            [$task_id]
        )->getResult();

        $statuses = $this->Task_status_model->get_details()->getResult();
        $comments = array_reverse($this->Project_comments_model->get_details([
            'task_id'    => (int)$task_id,
            'comment_id' => 0
        ])->getResult());
        return $this->_liff_view('liff_app/tasks/detail', [
            'page_title' => $task->title,
            'active_tab' => 'tasks',
            'task'       => $task,
            'activity'   => $activity,
            'statuses'   => $statuses,
            'comments'   => $comments,
        ]);
    }

    private function _task_form($task_id) {
        $task     = $task_id ? $this->Tasks_model->get_one($task_id) : new \stdClass();
        $users    = $this->_get_staff_list();
        $projects = $this->_get_user_projects();
        $statuses = $this->Task_status_model->get_details()->getResult();
        $priorities = $this->Task_priority_model->get_details()->getResult() ?? [];

        // For new tasks: pre-select the current month's งานรายวัน project
        $default_project = null;
        if (!$task_id) {
            $default_project = $this->_get_default_monthly_project(0);

            // If the default project isn't in the user's project list, inject it
            $found = false;
            foreach ($projects as $p) {
                if ($p->id == $default_project->id) { $found = true; break; }
            }
            if (!$found) {
                array_unshift($projects, $default_project);
            }
        }

        return $this->_liff_view('liff_app/tasks/form', [
            'page_title'         => $task_id ? 'แก้ไข Task' : 'สร้าง Task',
            'active_tab'         => 'tasks',
            'task'               => $task,
            'users'              => $users,
            'projects'           => $projects,
            'statuses'           => $statuses,
            'priorities'         => $priorities,
            'default_project_id' => $default_project ? $default_project->id : null,
        ]);
    }

    // ── Events ─────────────────────────────────────────────────────
    public function events() {
        $user_id    = $this->login_user->id;
        $month_start = date('Y-m-01');
        $month_end   = date('Y-m-t');

        // Pre-fetch current month events so the calendar renders immediately without an AJAX call
        $rows = $this->db->query(
            "SELECT e.id, e.title, e.start_date, e.end_date, e.start_time, e.end_time, e.color
             FROM rise_events e
             WHERE e.deleted=0
               AND e.start_date <= ? AND (e.end_date >= ? OR (e.end_date IS NULL AND e.start_date >= ?))
               AND (e.created_by=? OR e.share_with LIKE '%all_team%' OR FIND_IN_SET(?,e.share_with))
             ORDER BY e.start_date ASC, e.start_time ASC",
            [$month_end, $month_start, $month_start, $user_id, $user_id]
        )->getResult();

        $initial_events = array_map(function($e) {
            return [
                'id'         => (int)$e->id,
                'title'      => $e->title,
                'start_date' => $e->start_date,
                'end_date'   => $e->end_date ?: $e->start_date,
                'start_time' => $e->start_time,
                'end_time'   => $e->end_time,
                'color'      => $e->color ?: '#4F7DF3',
            ];
        }, $rows);

        return $this->_liff_view('liff_app/events/index', [
            'page_title'     => 'Events',
            'active_tab'     => 'events',
            'fab_url'        => get_uri('liff/app/events/create'),
            'initial_events' => json_encode($initial_events),
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

        $event_comments = $this->Event_comments_model->get_by_event($event_id);

        return $this->_liff_view('liff_app/events/detail', [
            'page_title' => $event->title,
            'active_tab' => 'events',
            'event'      => $event,
            'comments'   => $event_comments,
            'login_user_id' => $this->login_user->id,
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
                    ps.key_name AS status_key_name,
                    ps.title AS status_title, NULL AS status_color,
                    COUNT(DISTINCT pm.user_id) AS member_count,
                    COUNT(DISTINCT t.id) AS task_count,
                    SUM(CASE WHEN ts.key_name='closed' THEN 1 ELSE 0 END) AS done_count
             FROM rise_projects p
             LEFT JOIN rise_project_members pm2 ON pm2.project_id=p.id AND pm2.user_id=$user_id
             LEFT JOIN rise_project_status ps ON ps.id=p.status_id
             LEFT JOIN rise_project_members pm ON pm.project_id=p.id
             LEFT JOIN rise_tasks t ON t.project_id=p.id AND t.deleted=0
             LEFT JOIN rise_task_status ts ON ts.id=t.status_id
             WHERE p.deleted=0
               AND (p.created_by=$user_id OR pm2.id IS NOT NULL)
               AND ps.key_name='open'
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
             LEFT JOIN rise_users u ON u.id=al.created_by
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
            'page_title'         => 'สร้าง Task',
            'active_tab'         => 'projects',
            'task'               => $task,
            'users'              => $users,
            'projects'           => $projects,
            'statuses'           => $statuses,
            'priorities'         => $priorities,
            'default_project_id' => null, // project already set by context
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

        // Show current and next month's งานรายวัน projects
        $current_project = $this->_get_default_monthly_project(0);
        $next_project    = $this->_get_default_monthly_project(1);

        return $this->_liff_view('liff_app/profile/index', [
            'page_title'      => 'Profile',
            'active_tab'      => 'profile',
            'mapping'         => $mapping,
            'current_project' => $current_project,
            'next_project'    => $next_project,
        ]);
    }

    // ── Default Monthly Project ────────────────────────────────────
    /**
     * Return the "งานรายวัน" project for a given month offset (0=current, 1=next).
     * Searches by exact Thai title. Creates the project if not found.
     * @param int $month_offset 0 for current month, 1 for next month
     * @return object  project row with at least ->id and ->title
     */
    private function _get_default_monthly_project($month_offset = 0) {
        $thai_months = [
            1  => 'มกราคม',   2  => 'กุมภาพันธ์', 3  => 'มีนาคม',
            4  => 'เมษายน',   5  => 'พฤษภาคม',    6  => 'มิถุนายน',
            7  => 'กรกฎาคม',  8  => 'สิงหาคม',    9  => 'กันยายน',
            10 => 'ตุลาคม',   11 => 'พฤศจิกายน',  12 => 'ธันวาคม',
        ];

        // Compute target month/year (Thai year = CE + 543)
        $ts          = mktime(0, 0, 0, date('n') + $month_offset, 1, date('Y'));
        $month_num   = (int)date('n', $ts);
        $thai_year   = (int)date('Y', $ts) + 543;
        $title       = 'งานรายวัน เดือน' . $thai_months[$month_num] . ' ' . $thai_year;

        $t = $this->db->prefixTable('projects');

        // Search — match exactly (LIKE is fine; titles are unique by convention)
        $row = $this->db->query(
            "SELECT id, title FROM $t WHERE title=? AND deleted=0 LIMIT 1",
            [$title]
        )->getRow();

        if ($row) {
            return $row;
        }

        // Not found — create it
        $new_id = $this->Projects_model->ci_save([
            'title'        => $title,
            'client_id'    => 1,
            'project_type' => 'client_project',
            'status_id'    => 1,   // Open
            'created_by'   => $this->login_user->id,
            'created_date' => date('Y-m-d'),
            'start_date'   => date('Y-m-01', $ts),
            'deadline'     => date('Y-m-t', $ts),
        ]);

        return (object)['id' => $new_id, 'title' => $title];
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
             WHERE p.deleted=0
               AND (p.created_by=$user_id OR pm.id IS NOT NULL)
             ORDER BY p.title"
        )->getResult();
    }

    private function _get_task_comment_files($task_id) {
        $rows = $this->Project_comments_model->get_files_for_tasks([(int)$task_id]);
        if (!$rows) {
            return [];
        }
        $files = [];
        foreach ($rows as $row) {
            if (!$row->files) { continue; }
            $items = @unserialize($row->files);
            if (!$items || !is_array($items)) { continue; }
            foreach ($items as $file) {
                if (!is_array($file) || !get_array_value($file, "file_name")) { continue; }
                $files[] = $file;
            }
        }
        return $files;
    }

    private function _attach_task_comment_files(&$tasks = []) {
        if (!$tasks || !is_array($tasks)) {
            return;
        }

        $task_ids = [];
        foreach ($tasks as $task) {
            if (is_object($task) && isset($task->id)) {
                $task_ids[] = (int)$task->id;
                $task->all_comment_files_array = [];
            }
        }
        if (!$task_ids) { return; }

        $comment_files = $this->Project_comments_model->get_files_for_tasks($task_ids);
        if (!$comment_files) { return; }

        $files_map = [];
        foreach ($comment_files as $comment_row) {
            if (!$comment_row->files) { continue; }
            $items = @unserialize($comment_row->files);
            if (!$items || !is_array($items)) { continue; }
            foreach ($items as $file) {
                if (!is_array($file) || !get_array_value($file, "file_name")) { continue; }
                $files_map[$comment_row->task_id][] = $file;
            }
        }

        foreach ($tasks as $task) {
            $task_files = $files_map[$task->id] ?? [];
            if (!is_array($task_files)) { $task_files = []; }
            $task->all_comment_files_array = array_values($task_files);
        }
    }
}
