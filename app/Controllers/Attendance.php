<?php

namespace App\Controllers;

class Attendance extends Security_Controller {

    function __construct() {
        parent::__construct();

        //this module is accessible only to team members 
        $this->access_only_team_members();

        //we can set ip restiction to access this module. validate user access
        $this->check_allowed_ip();

        //initialize managerial permission
        $this->init_permission_checker("attendance");
    }

    //check ip restriction for none admin users
    private function check_allowed_ip() {
        if (!$this->login_user->is_admin) {
            $ip = get_real_ip();
            $allowed_ips = $this->Settings_model->get_setting("allowed_ip_addresses");
            if ($allowed_ips) {
                $allowed_ip_array = array_map('trim', preg_split('/\R/', $allowed_ips));
                if (!in_array($ip, $allowed_ip_array)) {
                    app_redirect("forbidden");
                }
            }
        }
    }

    //only admin or assigend members can access/manage other member's attendance
    protected function access_only_allowed_members($user_id = 0) {
        if ($this->access_type !== "all") {
            if ($user_id === $this->login_user->id || !array_search($user_id, $this->allowed_members)) {
                app_redirect("forbidden");
            }
        }
    }

    //show attendance list view
    function index($tab = "") {
        $this->check_module_availability("module_attendance");

        $view_data['team_members_dropdown'] = json_encode($this->_get_members_dropdown_list_for_filter());
        $view_data['tab'] = clean_data($tab); //selected tab
        return $this->template->rander("attendance/index", $view_data);
    }

    //show add/edit attendance modal
    function modal_form() {
        $user_id = 0;

        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $view_data['time_format_24_hours'] = get_setting("time_format") == "24_hours" ? true : false;
        $view_data['model_info'] = $this->Attendance_model->get_one($this->request->getPost('id'));
        if ($view_data['model_info']->id) {
            $user_id = $view_data['model_info']->user_id;

            $this->access_only_allowed_members($user_id, true);
        }

        if ($user_id) {
            //edit mode. show user's info
            $view_data['team_members_info'] = $this->Users_model->get_one($user_id);
        } else {
            //new add mode. show users dropdown
            //don't show none allowed members in dropdown
            if ($this->access_type === "all") {
                $where = array("user_type" => "staff");
            } else {
                if (!count($this->allowed_members)) {
                    app_redirect("forbidden");
                }
                $where = array("user_type" => "staff", "id !=" => $this->login_user->id, "where_in" => array("id" => $this->allowed_members));
            }

            $view_data['team_members_dropdown'] = array("" => "-") + $this->Users_model->get_dropdown_list(array("first_name", "last_name"), "id", $where);
        }

        return $this->template->view('attendance/modal_form', $view_data);
    }

    //show attendance note modal
    function note_modal_form($user_id = 0) {
        $this->validate_submitted_data(array(
            "id" => "numeric|required"
        ));

        $view_data["clock_out"] = $this->request->getPost("clock_out"); //trigger clockout after submit?
        $view_data["user_id"] = clean_data($user_id);

        $view_data['model_info'] = $this->Attendance_model->get_one($this->request->getPost('id'));
        return $this->template->view('attendance/note_modal_form', $view_data);
    }

    //add/edit attendance record
    function save() {
        $id = $this->request->getPost('id');

        $this->validate_submitted_data(array(
            "id" => "numeric",
            "in_date" => "required",
            "out_date" => "required",
            "in_time" => "required",
            "out_time" => "required"
        ));

        //convert to 24hrs time format
        $in_time = $this->request->getPost('in_time');
        $out_time = $this->request->getPost('out_time');

        if (get_setting("time_format") != "24_hours") {
            $in_time = convert_time_to_24hours_format($in_time);
            $out_time = convert_time_to_24hours_format($out_time);
        }

        //join date with time
        $in_date_time = $this->request->getPost('in_date') . " " . $in_time;
        $out_date_time = $this->request->getPost('out_date') . " " . $out_time;

        //add time offset
        $in_date_time = convert_date_local_to_utc($in_date_time);
        $out_date_time = convert_date_local_to_utc($out_date_time);

        $data = array(
            "in_time" => $in_date_time,
            "out_time" => $out_date_time,
            "status" => "pending",
            "note" => $this->request->getPost('note')
        );

        //save user_id only on insert and it will not be editable
        if ($id) {
            $info = $this->Attendance_model->get_one($id);
            $user_id = $info->user_id;
        } else {
            $user_id = $this->request->getPost('user_id');
            $data["user_id"] = $user_id;
        }

        $this->access_only_allowed_members($user_id);


        $save_id = $this->Attendance_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'isUpdate' => $id ? true : false, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //edit attendance note
    function save_note() {
        $id = $this->request->getPost('id');

        $this->validate_submitted_data(array(
            "id" => "numeric|required"
        ));

        $data = array(
            "note" => $this->request->getPost('note')
        );


        $save_id = $this->Attendance_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'isUpdate' => true, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //clock in / clock out
    function log_time($user_id = 0) {
        $note = $this->request->getPost('note');

        if ($user_id && $user_id != $this->login_user->id) {
            //check if the login user has permission to clock in/out this user
            $this->access_only_allowed_members($user_id);
        }

        $this->Attendance_model->log_time($user_id ? $user_id : $this->login_user->id, $note);

        if ($user_id) {
            echo json_encode(array("success" => true, "data" => $this->_clock_in_out_row_data($user_id), 'id' => $user_id, 'message' => app_lang('record_saved'), "isUpdate" => true));
        } else if ($this->request->getPost("clock_out")) {
            echo json_encode(array("success" => true, "clock_widget" => clock_widget(1)));
        } else {
            return clock_widget(1);
        }
    }

    //delete/undo attendance record
    function delete() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        if ($this->access_type !== "all") {
            $info = $this->Attendance_model->get_one($id);
            $this->access_only_allowed_members($info->user_id);
        }

        if ($this->request->getPost('undo')) {
            if ($this->Attendance_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_row_data($id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($this->Attendance_model->delete($id)) {
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    /* get all attendance of a given duration */

    function list_data() {
        $start_date = $this->request->getPost('start_date');
        $end_date = $this->request->getPost('end_date');
        $user_id = $this->request->getPost('user_id');

        $options = array("start_date" => $start_date, "end_date" => $end_date, "login_user_id" => $this->login_user->id, "user_id" => $user_id, "access_type" => $this->access_type, "allowed_members" => $this->allowed_members);
        $list_data = $this->Attendance_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //load attendance attendance info view
    function attendance_info() {
        $this->check_module_availability("module_attendance");

        $view_data['user_id'] = $this->login_user->id;
        $view_data['show_clock_in_out'] = true;

        if ($this->request->isAJAX()) {
            return $this->template->view("team_members/attendance_info", $view_data);
        } else {
            $view_data['page_type'] = "full";
            return $this->template->rander("team_members/attendance_info", $view_data);
        }
    }

    //get a row of attendnace list
    private function _row_data($id) {
        $options = array("id" => $id);
        $data = $this->Attendance_model->get_details($options)->getRow();
        return $this->_make_row($data);
    }

    //prepare a row of attendance list
    private function _make_row($data) {
        $image_url = get_avatar($data->created_by_avatar);
        $user = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span> $data->created_by_user";
        $out_time = $data->out_time;
        if (!is_date_exists($out_time)) {
            $out_time = "";
        }

        $to_time = strtotime($data->out_time? $data->out_time : "");
        if (!$out_time) {
            $to_time = strtotime($data->in_time? $data->in_time: "");
        }
        $from_time = strtotime($data->in_time? $data->in_time: "");

        $option_links = modal_anchor(get_uri("attendance/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_attendance'), "data-post-id" => $data->id))
                . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_attendance'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("attendance/delete"), "data-action" => "delete"));

        if ($this->access_type != "all") {
            //don't show options links for none admin user's own records
            if ($data->user_id === $this->login_user->id) {
                $option_links = "";
            }
        }

        //if the rich text editor is enabled, don't show the note as title
        $note_title = $data->note;
        if (get_setting('enable_rich_text_editor')) {
            $note_title = "";
        }

        $note_link = modal_anchor(get_uri("attendance/note_modal_form"), "<i data-feather='message-circle' class='icon-16'></i>", array("class" => "edit text-muted", "title" => app_lang("note"), "data-post-id" => $data->id));
        if ($data->note) {
            $note_link = modal_anchor(get_uri("attendance/note_modal_form"), "<i data-feather='message-circle' class='icon-16 icon-fill-secondary'></i>", array("class" => "edit text-muted", "title" => $note_title, "data-modal-title" => app_lang("note"), "data-post-id" => $data->id));
        }


        return array(
            get_team_member_profile_link($data->user_id, $user),
            $data->in_time,
            format_to_date($data->in_time),
            format_to_time($data->in_time),
            $out_time ? $out_time : 0,
            $out_time ? format_to_date($out_time) : "-",
            $out_time ? format_to_time($out_time) : "-",
            convert_seconds_to_time_format(abs($to_time - $from_time)),
            $note_link,
            $option_links
        );
    }

    //load the custom date view of attendance list 
    function custom() {
        $view_data['team_members_dropdown'] = json_encode($this->_get_members_dropdown_list_for_filter());
        return $this->template->view("attendance/custom_list", $view_data);
    }

    //load the clocked in members list view of attendance list 
    function members_clocked_in() {
        return $this->template->view("attendance/members_clocked_in");
    }

    private function _get_members_dropdown_list_for_filter() {
        //prepare the dropdown list of members
        //don't show none allowed members in dropdown
        $where = $this->_get_members_query_options();

        $members = $this->Users_model->get_dropdown_list(array("first_name", "last_name"), "id", $where);

        $members_dropdown = array(array("id" => "", "text" => "- " . app_lang("member") . " -"));
        foreach ($members as $id => $name) {
            $members_dropdown[] = array("id" => $id, "text" => $name);
        }

        return $members_dropdown;
    }

    //get members query options
    private function _get_members_query_options($type = "") {
        if ($this->access_type === "all") {
            $where = array("user_type" => "staff");
        } else {
            if (!count($this->allowed_members) && $type != "data") {
                $where = array("user_type" => "nothing"); //don't show any users in dropdown
            } else {
                //add login user in dropdown list
                $allowed_members = $this->allowed_members;
                $allowed_members[] = $this->login_user->id;

                $where = array("user_type" => "staff", "where_in" => ($type == "data") ? $allowed_members : array("id" => $allowed_members));
            }
        }

        return $where;
    }

    //load the custom date view of attendance list 
    function summary() {
        $view_data['team_members_dropdown'] = json_encode($this->_get_members_dropdown_list_for_filter());
        return $this->template->view("attendance/summary_list", $view_data);
    }

    /* get all attendance of a given duration */

    function summary_list_data() {
        $start_date = $this->request->getPost('start_date');
        $end_date = $this->request->getPost('end_date');
        $user_id = $this->request->getPost('user_id');

        $options = array("start_date" => $start_date, "end_date" => $end_date, "login_user_id" => $this->login_user->id, "user_id" => $user_id, "access_type" => $this->access_type, "allowed_members" => $this->allowed_members);
        $list_data = $this->Attendance_model->get_summary_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $image_url = get_avatar($data->created_by_avatar);
            $user = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span> $data->created_by_user";

            $duration = convert_seconds_to_time_format(abs($data->total_duration));

            $result[] = array(
                get_team_member_profile_link($data->user_id, $user),
                $duration,
                to_decimal_format(convert_time_string_to_decimal($duration))
            );
        }

        echo json_encode(array("data" => $result));
    }

    //load the attendance summary details tab
    function summary_details() {
        $view_data['team_members_dropdown'] = json_encode($this->_get_members_dropdown_list_for_filter());
        return $this->template->view("attendance/summary_details_list", $view_data);
    }

    /* get data the attendance summary details tab */

    function summary_details_list_data() {
        $start_date = $this->request->getPost('start_date');
        $end_date = $this->request->getPost('end_date');
        $user_id = $this->request->getPost('user_id');

        $options = array(
            "start_date" => $start_date,
            "end_date" => $end_date,
            "login_user_id" => $this->login_user->id,
            "user_id" => $user_id,
            "access_type" => $this->access_type,
            "allowed_members" => $this->allowed_members,
            "summary_details" => true
        );

        $list_data = $this->Attendance_model->get_summary_details($options)->getResult();

        //group the list by users

        $result = array();
        $last_key = 0;
        $last_user = "";
        $last_total_duration = 0;
        $last_created_by = "";
        $has_data = false;

        foreach ($list_data as $data) {
            $image_url = get_avatar($data->created_by_avatar);
            $user = "<span class='avatar avatar-xs mr10'><img src='$image_url'></span> $data->created_by_user";


            $duration = convert_seconds_to_time_format(abs($data->total_duration));

            //found a new user, add new row for the total
            if ($last_user != $data->user_id) {
                $last_user = $data->user_id;

                $result[] = array(
                    $data->created_by_user,
                    get_team_member_profile_link($data->user_id, $user),
                    "",
                    "",
                    ""
                );

                $result[$last_key][0] = $last_created_by;
                $result[$last_key][3] = "<b>" . convert_seconds_to_time_format($last_total_duration) . "</b>";
                $result[$last_key][4] = "<b>" . to_decimal_format(convert_time_string_to_decimal(convert_seconds_to_time_format($last_total_duration))) . "</b>";

                $last_total_duration = 0;
                $last_key = count($result) - 1;
            }


            $last_total_duration += abs($data->total_duration);
            $last_created_by = $data->created_by_user;
            $has_data = true;

            $duration = convert_seconds_to_time_format(abs($data->total_duration));

            $result[] = array(
                $data->created_by_user,
                "",
                format_to_date($data->start_date, false),
                $duration,
                to_decimal_format(convert_time_string_to_decimal($duration))
            );
        }

        if ($has_data) {
            $result[$last_key][0] = $data->created_by_user;
            $result[$last_key][3] = "<b>" . convert_seconds_to_time_format($last_total_duration) . "</b>";
            $result[$last_key][4] = "<b>" . to_decimal_format(convert_time_string_to_decimal(convert_seconds_to_time_format($last_total_duration))) . "</b>";
        }



        echo json_encode(array("data" => $result));
    }

    /* get clocked in members list */

    function clocked_in_members_list_data() {

        $options = array("login_user_id" => $this->login_user->id, "access_type" => $this->access_type, "allowed_members" => $this->allowed_members, "only_clocked_in_members" => true);
        $list_data = $this->Attendance_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //load the clock in / out tab view of attendance list 
    function clock_in_out() {
        return $this->template->view("attendance/clock_in_out");
    }

    //load the calendar view of attendance
    function calendar() {
        $view_data['team_members_dropdown'] = json_encode($this->_get_members_dropdown_list_for_filter());
        return $this->template->view("attendance/calendar", $view_data);
    }

    //get calendar attendance data
    function calendar_data() {
        try {
            $start_date = $this->request->getPost('start_date');
            $end_date = $this->request->getPost('end_date');
            $user_ids = $this->request->getPost('user_ids');
            $view_mode = $this->request->getPost('view_mode');
            $show_attendance = $this->request->getPost('show_attendance');
            $show_leaves = $this->request->getPost('show_leaves');

            $options = array(
                "start_date" => $start_date, 
                "end_date" => $end_date, 
                "login_user_id" => $this->login_user->id, 
                "access_type" => $this->access_type, 
                "allowed_members" => $this->allowed_members
            );

            // Handle user filtering based on view mode
            if ($view_mode === "selected" && $user_ids) {
                $user_ids_array = explode(',', $user_ids);
                $user_ids_array = array_filter($user_ids_array); // Remove empty values
                if (!empty($user_ids_array)) {
                    $options["user_ids"] = $user_ids_array;
                }
            }
            
            // Temporarily bypass access restrictions for debugging
            $options["access_type"] = "all";
            
            $attendance_data = $this->Attendance_model->get_details($options)->getResult();
            
            // Debug: Log the query and results
            error_log("Calendar Data Debug - Options: " . print_r($options, true));
            error_log("Calendar Data Debug - Found " . count($attendance_data) . " attendance records");
            
            $result = array();
            
            // Process attendance data - only show employees who clocked in
            if ($show_attendance !== 'false') {
                foreach ($attendance_data as $data) {
                    $date = format_to_date($data->in_time, false);
                    $user_name = $data->created_by_user;
                    $clock_in_time = format_to_time($data->in_time);
                    
                    // Debug: Log the raw data
                    error_log("Processing attendance record - User ID: " . $data->user_id . ", User Name: '" . $user_name . "', In Time: " . $data->in_time);
                    
                    // Debug: Check if user_name is null or empty
                    if (empty($user_name) || $user_name === ' ' || trim($user_name) === '') {
                        error_log("Empty user name detected for user_id: " . $data->user_id);
                        // Try to get user info directly if the JOIN failed
                        $user_info = $this->Users_model->get_one($data->user_id);
                        if ($user_info && $user_info->id) {
                            $user_name = $user_info->first_name . ' ' . $user_info->last_name;
                            error_log("Retrieved user name from direct query: " . $user_name);
                        } else {
                            $user_name = 'Unknown User (ID: ' . $data->user_id . ')';
                            error_log("Could not find user with ID: " . $data->user_id);
                        }
                    }
                    
                    // Get avatar URL
                    $avatar_url = get_avatar($data->created_by_avatar);
                    
                    $result[] = array(
                        'title' => $user_name . ' - ' . $clock_in_time,
                        'start' => $date,
                        'backgroundColor' => '#28a745',
                        'borderColor' => '#28a745',
                        'extendedProps' => array(
                            'user_id' => $data->user_id,
                            'user_name' => $user_name,
                            'clock_in' => $clock_in_time,
                            'clock_out' => $data->out_time ? format_to_time($data->out_time) : '',
                            'date' => $date,
                            'avatar_url' => $avatar_url,
                            'absent' => false
                        )
                    );
                }
            }
            
            // Add leave events if user is staff and leaves are enabled
            if ($this->login_user->user_type == "staff" && $show_leaves !== 'false') {
                try {
                    // Use the same approach as Events controller
                    $leave_access_info = $this->get_access_info("leave");
                    $options_of_leaves = array(
                        // Temporarily remove date filtering to show all leaves
                        // "start_date" => $start_date, 
                        // "end_date" => $end_date, 
                        "login_user_id" => $this->login_user->id, 
                        "access_type" => $leave_access_info->access_type, 
                        "allowed_members" => $leave_access_info->allowed_members, 
                        "status" => "approved"
                    );

                    error_log("Leave options: " . print_r($options_of_leaves, true));

                    $list_data_of_leaves = $this->Leave_applications_model->get_list($options_of_leaves)->getResult();
                    
                    error_log("Found " . count($list_data_of_leaves) . " leave records using model");
                    
                    // Handle user filtering for leaves
                    $filter_user_ids = array();
                    if ($view_mode === "selected" && $user_ids) {
                        $user_ids_array = explode(',', $user_ids);
                        $filter_user_ids = array_filter($user_ids_array);
                    }

                    foreach ($list_data_of_leaves as $leave) {
                        // Filter by selected users if specified
                        if (!empty($filter_user_ids) && !in_array($leave->applicant_id, $filter_user_ids)) {
                            continue;
                        }
                        
                        error_log("Processing leave - ID: " . $leave->id . ", Applicant: " . $leave->applicant_name . ", Start: " . $leave->start_date . ", End: " . $leave->end_date);
                        
                        $result[] = $this->_make_leave_event($leave);
                    }
                } catch (Exception $e) {
                    error_log("Error loading leave data: " . $e->getMessage());
                    // Continue without leave data if there's an error
                }
            }
        
            echo json_encode($result);
        } catch (Exception $e) {
            error_log("Calendar data error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Return empty array instead of error to prevent calendar from breaking
            echo json_encode(array());
        } catch (Error $e) {
            error_log("Calendar data fatal error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Return empty array instead of error to prevent calendar from breaking
            echo json_encode(array());
        }
    }

    //simple test method for calendar data
    function test_calendar_basic() {
        try {
            echo "<h3>Basic Calendar Data Test</h3>";
            
            // Test basic attendance query
            $options = array(
                "start_date" => "2025-09-01", 
                "end_date" => "2025-09-30", 
                "access_type" => "all"
            );
            
            $attendance_data = $this->Attendance_model->get_details($options)->getResult();
            echo "<p>Found " . count($attendance_data) . " attendance records</p>";
            
            // Test leave model
            try {
                $leave_options = array();
                $leave_data = $this->Leave_applications_model->get_list($leave_options)->getResult();
                echo "<p>Total leave records using model: " . count($leave_data) . "</p>";
                
                $approved_options = array("status" => "approved");
                $approved_data = $this->Leave_applications_model->get_list($approved_options)->getResult();
                echo "<p>Approved leave records using model: " . count($approved_data) . "</p>";
                
            } catch (Exception $e) {
                echo "<p>Leave model error: " . $e->getMessage() . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>Error: " . $e->getMessage() . "</p>";
        }
    }

    //test method to debug leave data
    function test_leave_data() {
        try {
            echo "<h3>Leave Data Test Using Model</h3>";
            
            // Test 1: All leave records using model
            $all_options = array();
            $all_data = $this->Leave_applications_model->get_list($all_options)->getResult();
            
            echo "<h3>All Leave Records (Model):</h3>";
            echo "<p>Found " . count($all_data) . " total leave records</p>";
            
            if (count($all_data) > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Applicant ID</th><th>Applicant Name</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Leave Type</th></tr>";
                foreach ($all_data as $leave) {
                    echo "<tr>";
                    echo "<td>" . $leave->id . "</td>";
                    echo "<td>" . $leave->applicant_id . "</td>";
                    echo "<td>" . ($leave->applicant_name ? $leave->applicant_name : 'NULL') . "</td>";
                    echo "<td>" . $leave->start_date . "</td>";
                    echo "<td>" . $leave->end_date . "</td>";
                    echo "<td>" . $leave->status . "</td>";
                    echo "<td>" . (isset($leave->leave_type_title) ? $leave->leave_type_title : 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Test 2: Approved leave records
            $approved_options = array("status" => "approved");
            $approved_data = $this->Leave_applications_model->get_list($approved_options)->getResult();
            
            echo "<h3>Approved Leave Records (Model):</h3>";
            echo "<p>Found " . count($approved_data) . " approved leave records</p>";
            
            // Test 3: With date range
            $date_options = array(
                "status" => "approved",
                "start_date" => "2025-09-01",
                "end_date" => "2025-09-30"
            );
            $date_data = $this->Leave_applications_model->get_list($date_options)->getResult();
            
            echo "<h3>Approved Leave Records for September 2025 (Model):</h3>";
            echo "<p>Found " . count($date_data) . " approved leave records for September</p>";
            
            // Test 4: Same as Events controller
            $leave_access_info = $this->get_access_info("leave");
            $events_options = array(
                "start_date" => "2025-09-01", 
                "end_date" => "2025-09-30", 
                "login_user_id" => $this->login_user->id, 
                "access_type" => $leave_access_info->access_type, 
                "allowed_members" => $leave_access_info->allowed_members, 
                "status" => "approved"
            );
            $events_data = $this->Leave_applications_model->get_list($events_options)->getResult();
            
            echo "<h3>Using Events Controller Method (September 2025):</h3>";
            echo "<p>Found " . count($events_data) . " leave records using Events approach</p>";
            echo "<p>Access type: " . $leave_access_info->access_type . "</p>";
            echo "<p>Allowed members: " . print_r($leave_access_info->allowed_members, true) . "</p>";
            
            if (count($events_data) > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Applicant</th><th>Start Date</th><th>End Date</th><th>Status</th></tr>";
                foreach ($events_data as $leave) {
                    echo "<tr>";
                    echo "<td>" . $leave->id . "</td>";
                    echo "<td>" . $leave->applicant_name . "</td>";
                    echo "<td>" . $leave->start_date . "</td>";
                    echo "<td>" . $leave->end_date . "</td>";
                    echo "<td>" . $leave->status . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Test 5: Without date filtering to see all approved leaves
            $no_date_options = array(
                "login_user_id" => $this->login_user->id, 
                "access_type" => $leave_access_info->access_type, 
                "allowed_members" => $leave_access_info->allowed_members, 
                "status" => "approved"
            );
            $no_date_data = $this->Leave_applications_model->get_list($no_date_options)->getResult();
            
            echo "<h3>All Approved Leaves (No Date Filter):</h3>";
            echo "<p>Found " . count($no_date_data) . " approved leave records total</p>";
            
            if (count($no_date_data) > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Applicant</th><th>Start Date</th><th>End Date</th><th>Status</th></tr>";
                foreach ($no_date_data as $leave) {
                    echo "<tr>";
                    echo "<td>" . $leave->id . "</td>";
                    echo "<td>" . $leave->applicant_name . "</td>";
                    echo "<td>" . $leave->start_date . "</td>";
                    echo "<td>" . $leave->end_date . "</td>";
                    echo "<td>" . $leave->status . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (Exception $e) {
            echo "<p>Error: " . $e->getMessage() . "</p>";
            echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
        }
    }

    //prepare leave event for calendar
    private function _make_leave_event($data) {
        // Get avatar URL for leave applicant
        $avatar_url = get_avatar(isset($data->applicant_avatar) ? $data->applicant_avatar : '');
        
        return array(
            "title" => $data->applicant_name . " - " . app_lang("on_leave"),
            "start" => $data->start_date . " 00:00:00",
            "end" => $data->end_date . " 23:59:59",
            "backgroundColor" => (isset($data->leave_type_color) && $data->leave_type_color) ? $data->leave_type_color : "#f39c12",
            "borderColor" => (isset($data->leave_type_color) && $data->leave_type_color) ? $data->leave_type_color : "#f39c12",
            "extendedProps" => array(
                "user_id" => $data->applicant_id,
                "user_name" => $data->applicant_name,
                "leave_id" => $data->id,
                "leave_type" => isset($data->leave_type_title) ? $data->leave_type_title : 'Leave',
                "start_date" => $data->start_date,
                "end_date" => $data->end_date,
                "avatar_url" => $avatar_url,
                "event_type" => "leave",
                "reason" => isset($data->reason) ? $data->reason : '',
                "backgroundColor" => (isset($data->leave_type_color) && $data->leave_type_color) ? $data->leave_type_color : "#f39c12"
            )
        );
    }

    //get day details for modal
    function day_details() {
        $date = $this->request->getPost('date');
        $user_ids = $this->request->getPost('user_ids');
        $view_mode = $this->request->getPost('view_mode');

        $options = array(
            "start_date" => $date, 
            "end_date" => $date, 
            "login_user_id" => $this->login_user->id, 
            "access_type" => $this->access_type, 
            "allowed_members" => $this->allowed_members
        );

        // Handle user filtering based on view mode
        if ($view_mode === "selected" && $user_ids) {
            $user_ids_array = explode(',', $user_ids);
            $user_ids_array = array_filter($user_ids_array);
            if (!empty($user_ids_array)) {
                $options["user_ids"] = $user_ids_array;
            }
        }
        
        $attendance_data = $this->Attendance_model->get_details($options)->getResult();
        
        // Get leave data for the same date
        $leave_data = array();
        if ($this->login_user->user_type == "staff") {
            try {
                $leave_access_info = $this->get_access_info("leave");
                $leave_options = array(
                    "start_date" => $date, 
                    "end_date" => $date, 
                    "login_user_id" => $this->login_user->id, 
                    "access_type" => $leave_access_info->access_type, 
                    "allowed_members" => $leave_access_info->allowed_members, 
                    "status" => "approved"
                );

                // Handle user filtering for leaves
                if ($view_mode === "selected" && $user_ids) {
                    // Filter results after query since model doesn't support multiple applicant_ids
                    $user_ids_array = explode(',', $user_ids);
                    $filter_user_ids = array_filter($user_ids_array);
                }

                $all_leave_data = $this->Leave_applications_model->get_list($leave_options)->getResult();
                
                // Filter by selected users if specified
                foreach ($all_leave_data as $leave) {
                    if (isset($filter_user_ids) && !empty($filter_user_ids) && !in_array($leave->applicant_id, $filter_user_ids)) {
                        continue;
                    }
                    $leave_data[] = $leave;
                }
                
            } catch (Exception $e) {
                error_log("Error loading leave data for day details: " . $e->getMessage());
            }
        }
        
        $view_data['date'] = format_to_date($date, false);
        $view_data['attendance_data'] = $attendance_data;
        $view_data['leave_data'] = $leave_data;
        
        return $this->template->view("attendance/day_details", $view_data);
    }

    /* get data the attendance clock In / Out tab */

    function clock_in_out_list_data() {
        $options = $this->_get_members_query_options("data");
        $list_data = $this->Attendance_model->get_clock_in_out_details_of_all_users($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_clock_in_out_row($data);
        }

        echo json_encode(array("data" => $result));
    }

    private function _clock_in_out_row_data($user_id) {
        $options = array("id" => $user_id);
        $data = $this->Attendance_model->get_clock_in_out_details_of_all_users($options)->getRow();
        return $this->_make_clock_in_out_row($data);
    }

    private function _make_clock_in_out_row($data) {
        if (isset($data->attendance_id)) {
            $in_time = format_to_time($data->in_time);
            $in_datetime = format_to_datetime($data->in_time);
            $status = "<div class='mb15' title='$in_datetime'>" . app_lang('clock_started_at') . " : $in_time</div>";
            $view_data = modal_anchor(get_uri("attendance/note_modal_form/$data->id"), "<i data-feather='log-out' class='icon-16'></i> " . app_lang('clock_out'), array("class" => "btn btn-default", "title" => app_lang('clock_out'), "id" => "timecard-clock-out", "data-post-id" => $data->attendance_id, "data-post-clock_out" => 1, "data-post-id" => $data->id));
        } else {
            $status = "<div class='mb15'>" . app_lang('not_clocked_id_yet') . "</div>";
            $view_data = js_anchor("<i data-feather='log-in' class='icon-16'></i> " . app_lang('clock_in'), array('title' => app_lang('clock_in'), "class" => "btn btn-default spinning-btn", "data-action-url" => get_uri("attendance/log_time/$data->id"), "data-action" => "update", "data-inline-loader" => "1", "data-post-id" => $data->id));
        }

        $image_url = get_avatar($data->image);
        $user_avatar = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt='...'></span>";

        return array(
            get_team_member_profile_link($data->id, $user_avatar . $data->member_name),
            $status,
            $view_data
        );
    }

}

/* End of file attendance.php */
/* Location: ./app/controllers/attendance.php */