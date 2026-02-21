<?php

namespace App\Libraries;

use App\Controllers\App_Controller;
use App\Libraries\Google_calendar_events;
use App\Libraries\Imap;
use App\Libraries\Outlook_imap;
use App\Libraries\Reminders;

class Cron_job {

    private $today = null;
    private $current_time = null;
    private $ci = null;

    function run() {
        $this->today = get_today_date();
        $this->ci = new App_Controller();
        $this->current_time = strtotime(get_current_utc_time());

        $this->call_hourly_jobs();
        $this->call_daily_jobs();

        // LIFF scheduled notifications — checked every cron run (own cooldown prevents duplicates)
        if (get_setting('liff_line_channel_access_token') || get_setting('line_channel_access_token')) {
            try {
                $this->liff_task_reminder();
            } catch (\Throwable $e) {
                log_message('error', 'LIFF task reminder: ' . $e->getMessage());
            }

            try {
                $this->liff_task_summary();
            } catch (\Throwable $e) {
                log_message('error', 'LIFF task summary: ' . $e->getMessage());
            }
        }

        try {
            $this->run_imap();
        } catch (\Exception $e) {
            echo $e;
        }

        try {
            $this->get_google_calendar_events();
        } catch (\Exception $e) {
            echo $e;
        }

        try {
            $this->close_inactive_tickets();
        } catch (\Exception $e) {
            echo $e;
        }
    }

    private function call_hourly_jobs() {
        //wait 1 hour for each call of following actions
        if ($this->_is_hourly_job_runnable()) {


            try {
                $this->create_recurring_invoices();
            } catch (\Exception $e) {
                echo $e;
            }

            try {
                $this->create_recurring_expenses();
            } catch (\Exception $e) {
                echo $e;
            }

            try {
                $this->send_invoice_due_pre_reminder();
            } catch (\Exception $e) {
                echo $e;
            }


            try {
                $this->send_invoice_due_after_reminder();
            } catch (\Exception $e) {
                echo $e;
            }


            try {
                $this->send_recurring_invoice_creation_reminder();
            } catch (\Exception $e) {
                echo $e;
            }


            try {
                $this->create_recurring_tasks();
            } catch (\Exception $e) {
                echo $e;
            }

            try {
                $this->send_task_reminder_notifications();
            } catch (\Exception $e) {
                echo $e;
            }

            try {
                $this->create_recurring_reminders();
            } catch (\Exception $e) {
                echo $e;
            }

            try {
                $this->create_subscription_invoices();
            } catch (\Exception $e) {
                echo $e;
            }

            try {
                $this->_create_subscription_reminders();
            } catch (\Exception $e) {
                echo $e;
            }

            try {
                $this->_send_available_reminders();
            } catch (\Exception $e) {
                echo $e;
            }

            // LINE LIFF push notifications
            if (get_setting('liff_line_channel_access_token') || get_setting('line_channel_access_token')) {
                try {
                    $this->liff_notify_before_start();
                } catch (\Throwable $e) {
                    log_message('error', 'LIFF notify before_start: ' . $e->getMessage());
                }

                try {
                    $this->liff_notify_before_end();
                } catch (\Throwable $e) {
                    log_message('error', 'LIFF notify before_end: ' . $e->getMessage());
                }

                try {
                    $this->liff_notify_no_update();
                } catch (\Throwable $e) {
                    log_message('error', 'LIFF notify no_update: ' . $e->getMessage());
                }

            }

            $this->ci->Settings_model->save_setting("last_hourly_job_time", $this->current_time);
        }
    }

    private function _is_hourly_job_runnable() {
        $last_hourly_job_time = get_setting('last_hourly_job_time');
        if (!$last_hourly_job_time || ($this->current_time > ($last_hourly_job_time * 1 + 3600))) {
            return true;
        }
    }

    private function send_invoice_due_pre_reminder() {

        $reminder_date = get_setting("send_invoice_due_pre_reminder");
        $reminder_date2 = get_setting("send_invoice_due_pre_second_reminder");
        if (!($reminder_date || $reminder_date2)) {
            return false;
        }

        //prepare invoice due date accroding to the setting
        $reminder_due_date = $reminder_date ? add_period_to_date($this->today, $reminder_date, "days") : "";
        $reminder_due_date2 = $reminder_date2 ? add_period_to_date($this->today, $reminder_date2, "days") : "";

        $invoices = $this->ci->Invoices_model->get_details(array(
            "status" => "not_paid_and_partially_paid", //find all invoices which are not paid yet but due date not expired
            "reminder_due_date" => $reminder_due_date,
            "reminder_due_date2" => $reminder_due_date2,
            "exclude_due_reminder_date" => $this->today //don't find invoices which reminder already sent today
        ))->getResult();

        foreach ($invoices as $invoice) {
            log_notification("invoice_due_reminder_before_due_date", array("invoice_id" => $invoice->id), "0");
        }
    }

    private function send_invoice_due_after_reminder() {

        $reminder_date = get_setting("send_invoice_due_after_reminder");
        $reminder_date2 = get_setting("send_invoice_due_after_second_reminder");
        if (!($reminder_date || $reminder_date2)) {
            return false;
        }

        //prepare invoice due date accroding to the setting
        $reminder_due_date = $reminder_date ? subtract_period_from_date($this->today, $reminder_date, "days") : "";
        $reminder_due_date2 = $reminder_date2 ? subtract_period_from_date($this->today, $reminder_date2, "days") : "";

        $invoices = $this->ci->Invoices_model->get_details(array(
            "status" => "overdue", //find all invoices where due date has expired
            "reminder_due_date" => $reminder_due_date,
            "reminder_due_date2" => $reminder_due_date2,
            "exclude_due_reminder_date" => $this->today //don't find invoices which reminder already sent today
        ))->getResult();

        foreach ($invoices as $invoice) {
            log_notification("invoice_overdue_reminder", array("invoice_id" => $invoice->id), "0");
        }
    }

    private function send_recurring_invoice_creation_reminder() {

        $reminder_date = get_setting("send_recurring_invoice_reminder_before_creation");

        if ($reminder_date) {

            //prepare invoice due date accroding to the setting
            $start_date = add_period_to_date($this->today, get_setting("send_recurring_invoice_reminder_before_creation"), "days");

            $invoices = $this->ci->Invoices_model->get_details(array(
                "status" => "not_paid", //non-draft invoices
                "recurring" => 1,
                "next_recurring_start_date" => $start_date,
                "next_recurring_end_date" => $start_date, //both should be same
                "exclude_recurring_reminder_date" => $this->today //don't find invoices which reminder already sent today
            ))->getResult();

            foreach ($invoices as $invoice) {
                log_notification("recurring_invoice_creation_reminder", array("invoice_id" => $invoice->id), "0");
            }
        }
    }

    private function create_recurring_invoices() {
        $recurring_invoices = $this->ci->Invoices_model->get_renewable_invoices($this->today);
        if ($recurring_invoices->resultID->num_rows) {
            foreach ($recurring_invoices->getResult() as $invoice) {
                $this->_create_new_invoice($invoice);
            }
        }
    }

    //create new invoice from a recurring invoice 
    private function _create_new_invoice($invoice) {

        //don't update the next recurring date when updating invoice manually?
        //stop backdated recurring invoice creation.
        //check recurring invoice once/hour?
        //settings: send invoice to client


        $bill_date = $invoice->next_recurring_date;
        $diff_of_due_date = get_date_difference_in_days($invoice->due_date, $invoice->bill_date); //calculate the due date difference of the original invoice
        $due_date = add_period_to_date($bill_date, $diff_of_due_date, "days");

        $_new_invoice_data = array(
            "client_id" => $invoice->client_id,
            "project_id" => $invoice->project_id,
            "bill_date" => $bill_date,
            "due_date" => $due_date,
            "note" => $invoice->note,
            "status" => "draft",
            "tax_id" => $invoice->tax_id,
            "tax_id2" => $invoice->tax_id2,
            "tax_id3" => $invoice->tax_id3,
            "recurring_invoice_id" => $invoice->id,
            "discount_amount" => $invoice->discount_amount,
            "discount_amount_type" => $invoice->discount_amount_type,
            "discount_type" => $invoice->discount_type,
            "company_id" => $invoice->company_id,
            "invoice_subtotal" => $invoice->invoice_subtotal,
            "invoice_total" => $invoice->invoice_total,
            "discount_total" => $invoice->discount_total,
            "tax" => $invoice->tax,
            "tax2" => $invoice->tax2,
            "tax3" => $invoice->tax3
        );

        $new_invoice_data = array_merge($_new_invoice_data, prepare_invoice_display_id_data($due_date, $bill_date));

        //create new invoice
        $new_invoice_id = $this->ci->Invoices_model->ci_save($new_invoice_data);

        //create invoice items
        $items = $this->ci->Invoice_items_model->get_details(array("invoice_id" => $invoice->id))->getResult();
        foreach ($items as $item) {
            //create invoice items for new invoice
            $new_invoice_item_data = array(
                "title" => $item->title,
                "description" => $item->description,
                "quantity" => $item->quantity,
                "unit_type" => $item->unit_type,
                "rate" => $item->rate,
                "total" => $item->total,
                "taxable" => $item->taxable,
                "invoice_id" => $new_invoice_id,
            );
            $this->ci->Invoice_items_model->ci_save($new_invoice_item_data);
        }


        //update the main recurring invoice
        $no_of_cycles_completed = $invoice->no_of_cycles_completed + 1;
        $next_recurring_date = add_period_to_date($bill_date, $invoice->repeat_every, $invoice->repeat_type);

        $recurring_invoice_data = array(
            "next_recurring_date" => $next_recurring_date,
            "no_of_cycles_completed" => $no_of_cycles_completed
        );
        $this->ci->Invoices_model->ci_save($recurring_invoice_data, $invoice->id);

        //finally send notification
        log_notification("recurring_invoice_created_vai_cron_job", array("invoice_id" => $new_invoice_id), "0");
    }

    private function create_subscription_invoices() {
        $subscriptions = $this->ci->Subscriptions_model->get_renewable_subscriptions($this->today);
        if ($subscriptions->resultID->num_rows) {
            foreach ($subscriptions->getResult() as $subscription) {
                $this->_create_new_invoice_of_subscription($subscription);
            }
        }
    }

    //create new invoice from a subscription
    private function _create_new_invoice_of_subscription($subscription_info) {
        $invoice_id = create_invoice_from_subscription($subscription_info->id);

        //update the main recurring subscription
        $no_of_cycles_completed = $subscription_info->no_of_cycles_completed + 1;
        $next_recurring_date = add_period_to_date($subscription_info->next_recurring_date, $subscription_info->repeat_every, $subscription_info->repeat_type);

        $subscription_data = array(
            "next_recurring_date" => $next_recurring_date,
            "no_of_cycles_completed" => $no_of_cycles_completed
        );

        $this->ci->Subscriptions_model->ci_save($subscription_data, $subscription_info->id);

        //finally send notification
        log_notification("subscription_invoice_created_via_cron_job", array("invoice_id" => $invoice_id, "subscription_id" => $subscription_info->id), "0");
    }

    private function get_google_calendar_events() {
        $Google_calendar_events = new Google_calendar_events();
        $Google_calendar_events->get_google_calendar_events();
    }

    private function run_imap() {
        if (!$this->_is_imap_callable()) {
            return false;
        }

        if (!get_setting('imap_type') || get_setting('imap_type') === "general_imap") {
            $imap = new Imap();
            $imap->run_imap();
        } else {
            $imap = new Outlook_imap();
            $imap->run_imap();
        }
    }

    private function _is_imap_callable() {

        //check if settings is enabled and authorized
        if (!(get_setting("enable_email_piping") && get_setting("imap_authorized"))) {
            return false;
        }

        return true;
    }

    private function create_recurring_tasks() {

        if (!get_setting("enable_recurring_option_for_tasks")) {
            return false;
        }

        $date = $this->today;

        //if create recurring task before certain days setting is active,
        //add the days with today
        $create_recurring_tasks_before = get_setting("create_recurring_tasks_before");
        if ($create_recurring_tasks_before) {
            $date = add_period_to_date($date, $create_recurring_tasks_before, "days");
        }

        $recurring_tasks = $this->ci->Tasks_model->get_renewable_tasks($date);
        if ($recurring_tasks->resultID->num_rows) {
            foreach ($recurring_tasks->getResult() as $task) {
                $this->_create_new_task($task);
            }
        }
    }

    //create new task from a recurring task 
    private function _create_new_task($task) {

        //don't update the next recurring date when updating task manually
        //stop backdated recurring task creation.
        //check recurring task once/hour?

        $start_date = $task->next_recurring_date;
        $deadline = NULL;

        $context = $task->context;

        if ($task->deadline) {
            $task_start_date = $task->start_date ? $task->start_date : $task->created_date;
            $diff_of_deadline = get_date_difference_in_days($task->deadline, $task_start_date); //calculate the deadline difference of the original task
            $deadline = add_period_to_date($start_date, $diff_of_deadline, "days");
        }

        $new_task_data = array(
            "title" => $task->title,
            "description" => $task->description,
            "project_id" => $task->project_id,
            "milestone_id" => $task->milestone_id,
            "points" => $task->points,
            "status_id" => 1, //new tasks should be on ToDo
            "context" => $context,
            "client_id" => $task->client_id,
            "lead_id" => $task->lead_id,
            "invoice_id" => $task->invoice_id,
            "estimate_id" => $task->estimate_id,
            "order_id" => $task->order_id,
            "contract_id" => $task->contract_id,
            "proposal_id" => $task->proposal_id,
            "expense_id" => $task->expense_id,
            "subscription_id" => $task->subscription_id,
            "priority_id" => $task->priority_id,
            "labels" => $task->labels,
            "start_date" => $start_date,
            "deadline" => $deadline,
            "recurring_task_id" => $task->id,
            "assigned_to" => $task->assigned_to,
            "collaborators" => $task->collaborators,
            "created_date" => get_current_utc_time(),
            "activity_log_created_by_app" => true,
            "created_by" => $task->created_by
        );

        $new_task_data["sort"] = $this->ci->Tasks_model->get_next_sort_value($task->project_id, $new_task_data["status_id"]);

        //create new task
        $new_task_id = $this->ci->Tasks_model->ci_save($new_task_data);

        //create checklist items
        $Checklist_items_model = model("App\Models\Checklist_items_model");
        $checklist_item_options = array("task_id" => $task->id);
        $checklist_items = $Checklist_items_model->get_details($checklist_item_options);
        if ($checklist_items->resultID->num_rows) {
            foreach ($checklist_items->getResult() as $item) {
                $checklist_item_data = array(
                    "title" => $item->title,
                    "is_checked" => $item->is_checked,
                    "task_id" => $new_task_id,
                    "sort" => $item->sort
                );

                $Checklist_items_model->ci_save($checklist_item_data);
            }
        }

        //create sub tasks
        $sub_tasks = $this->ci->Tasks_model->get_all_where(array("parent_task_id" => $task->id, "deleted" => 0))->getResult();
        foreach ($sub_tasks as $sub_task) {
            //prepare new sub task data
            $sub_task_data = (array) $sub_task;

            unset($sub_task_data["id"]);
            unset($sub_task_data["blocked_by"]);
            unset($sub_task_data["blocking"]);

            if ($task->start_date && $sub_task->start_date) {
                $sub_task_data['start_date'] = $start_date;
            } else {
                $sub_task_data['start_date'] = NULL;
            }

            $sub_task_data['status_id'] = 1;
            $sub_task_data['parent_task_id'] = $new_task_id;
            $sub_task_data['created_date'] = get_current_utc_time();
            $sub_task_data['deadline'] = NULL;

            $sub_task_data["sort"] = $this->ci->Tasks_model->get_next_sort_value(get_array_value($sub_task_data, "project_id"), $sub_task_data["status_id"]);

            $sub_task_save_id = $this->ci->Tasks_model->ci_save($sub_task_data);

            //create sub tasks checklist
            $checklist_items = $Checklist_items_model->get_all_where(array("task_id" => $sub_task->id, "deleted" => 0))->getResult();
            foreach ($checklist_items as $checklist_item) {
                //prepare new checklist data
                $checklist_item_data = (array) $checklist_item;
                unset($checklist_item_data["id"]);
                $checklist_item_data['task_id'] = $sub_task_save_id;

                $Checklist_items_model->ci_save($checklist_item_data);
            }
        }

        //update the main recurring task
        $no_of_cycles_completed = $task->no_of_cycles_completed + 1;
        $next_recurring_date = add_period_to_date($start_date, $task->repeat_every, $task->repeat_type);

        $recurring_task_data = array(
            "next_recurring_date" => $next_recurring_date,
            "no_of_cycles_completed" => $no_of_cycles_completed
        );
        $this->ci->Tasks_model->save_reminder_date($recurring_task_data, $task->id);

        //send notification
        if ($context === "project") {
            $notification_option = array("project_id" => $task->project_id, "task_id" => $new_task_id);
        } else if ($context === "general") {
            $notification_option = array("task_id" => $new_task_id);
        } else {
            $context_id_key = $context . "_id";
            $context_id_value = $task->{$context . "_id"};

            $notification_option = array("$context_id_key" => $context_id_value, "task_id" => $new_task_id);
        }

        log_notification("recurring_task_created_via_cron_job", $notification_option, "0");
    }

    private function send_task_reminder_notifications() {
        $notification_option = array("notification_multiple_tasks" => true);
        log_notification("project_task_deadline_pre_reminder", $notification_option, "0");
        log_notification("project_task_deadline_overdue_reminder", $notification_option, "0");
        log_notification("project_task_reminder_on_the_day_of_deadline", $notification_option, "0");
    }

    private function close_inactive_tickets() {

        $inactive_ticket_closing_date = get_setting("inactive_ticket_closing_date");
        if (!(!$inactive_ticket_closing_date || ($inactive_ticket_closing_date != $this->today))) {
            return false;
        }

        $auto_close_ticket_after_days = get_setting("auto_close_ticket_after");

        if ($auto_close_ticket_after_days) {
            //prepare last activity date accroding to the setting
            $last_activity_date = subtract_period_from_date($this->today, get_setting("auto_close_ticket_after"), "days");

            $tickets = $this->ci->Tickets_model->get_details(array(
                "status" => "open", //don't find closed tickets
                "last_activity_date_or_before" => $last_activity_date
            ))->getResult();

            foreach ($tickets as $ticket) {
                //make ticket closed
                $ticket_data = array(
                    "status" => "closed",
                    "closed_at" => get_current_utc_time()
                );

                $this->ci->Tickets_model->ci_save($ticket_data, $ticket->id);

                //send notification
                log_notification("ticket_closed", array("ticket_id" => $ticket->id), "0");
            }
        }

        $this->ci->Settings_model->save_setting("inactive_ticket_closing_date", $this->today);
    }

    private function create_recurring_expenses() {
        $recurring_expenses = $this->ci->Expenses_model->get_renewable_expenses($this->today);
        if ($recurring_expenses->resultID->num_rows) {
            foreach ($recurring_expenses->getResult() as $expense) {
                $this->_create_new_expense($expense);
            }
        }
    }

    //create new expense from a recurring expense 
    private function _create_new_expense($expense) {

        //don't update the next recurring date when updating expense manually?
        //stop backdated recurring expense creation.
        //check recurring expense once/hour?

        $expense_date = $expense->next_recurring_date;

        $new_expense_data = array(
            "title" => $expense->title,
            "expense_date" => $expense_date,
            "description" => $expense->description,
            "category_id" => $expense->category_id,
            "amount" => $expense->amount,
            "project_id" => $expense->project_id,
            "user_id" => $expense->user_id,
            "tax_id" => $expense->tax_id,
            "tax_id2" => $expense->tax_id2,
            "recurring_expense_id" => $expense->id
        );

        //create new expense
        $new_expense_id = $this->ci->Expenses_model->ci_save($new_expense_data);

        //update the main recurring expense
        $no_of_cycles_completed = $expense->no_of_cycles_completed + 1;
        $next_recurring_date = add_period_to_date($expense_date, $expense->repeat_every, $expense->repeat_type);

        $recurring_expense_data = array(
            "next_recurring_date" => $next_recurring_date,
            "no_of_cycles_completed" => $no_of_cycles_completed
        );

        $this->ci->Expenses_model->ci_save($recurring_expense_data, $expense->id);

        //finally send notification
        //log_notification("recurring_expense_created_vai_cron_job", array("expense_id" => $new_expense_id), "0");
    }

    private function create_recurring_reminders() {
        $options = array(
            "type" => "all",
            "recurring" => true,
            "reminder_status" => "new",
        );

        $recurring_reminders = $this->ci->Events_model->get_details($options)->getResult();
        foreach ($recurring_reminders as $reminder) {

            $now = get_my_local_time();
            $target_time = is_null($reminder->next_recurring_time) ? ($reminder->start_date . " " . $reminder->start_time) : $reminder->next_recurring_time;

            if ($target_time < $now && (!$reminder->no_of_cycles || $reminder->no_of_cycles_completed < $reminder->no_of_cycles)) {
                $data["next_recurring_time"] = add_period_to_date($target_time, $reminder->repeat_every, $reminder->repeat_type, "Y-m-d H:i:s");
                $data['no_of_cycles_completed'] = (int) $reminder->no_of_cycles_completed + 1;

                $this->ci->Events_model->ci_save($data, $reminder->id);
            }
        }
    }

    private function call_daily_jobs() {
        //wait 1 day for each call of following actions
        if ($this->_is_daily_job_runnable()) {

            try {
                $this->remove_old_session_data();
            } catch (\Exception $e) {
                echo $e;
            }

            $this->ci->Settings_model->save_setting("last_daily_cron_job_date", $this->today);
        }
    }

    private function _is_daily_job_runnable() {
        $last_daily_cron_job_date = get_setting('last_daily_cron_job_date');
        if (!$last_daily_cron_job_date || ($this->today > $last_daily_cron_job_date)) {
            return true;
        }
    }

    private function remove_old_session_data() {
        $Ci_sessions_model = model('App\Models\Ci_sessions_model');
        $last_weak_date = subtract_period_from_date($this->today, 7, "days");

        $Ci_sessions_model->delete_session_by_date($last_weak_date);
    }

    private function _create_subscription_reminders() {
        $reminders = new Reminders();
        $reminders->create_reminders("subscription");
    }

    private function _send_available_reminders() {
        $reminders = new Reminders();
        $reminders->send_available_reminders();
    }

    // ─────────────────────────────────────────────────────────────
    // LINE LIFF Notification Engine
    // Runs every hour. All three columns are nullable — skip if NULL.
    // ─────────────────────────────────────────────────────────────

    /** Resolve actual table name for user_mappings_arr (may or may not have rise_ prefix) */
    private function _liff_mappings_table() {
        $db = \Config\Database::connect();
        $prefixed = $db->getPrefix() . 'user_mappings_arr';
        $res = $db->query("SHOW TABLES LIKE ?", [$prefixed])->getResultArray();
        if ($res) { return $prefixed; }
        return 'user_mappings_arr';
    }

    private function _liff_notify_mode() {
        return get_setting('liff_notify_mode') ?: 'user';
    }

    private function _liff_notify_rooms() {
        $raw = get_setting('liff_notify_rooms');
        $arr = $raw ? json_decode($raw, true) : [];
        if (is_array($arr) && !empty($arr)) {
            return array_values(array_filter($arr));
        }

        // Fallback: use LINE Notify group/room IDs (same channel token)
        $fallback = get_setting('line_group_ids');
        if ($fallback) {
            $ids = preg_split('/[\n,]+/', $fallback);
            return array_values(array_filter(array_map('trim', $ids)));
        }

        return [];
    }

    private function _liff_rooms_from_line_groups() {
        $raw = get_setting('liff_notify_rooms');
        $arr = $raw ? json_decode($raw, true) : [];
        if (is_array($arr) && !empty($arr)) {
            return false;
        }

        $fallback = get_setting('line_group_ids');
        if (!$fallback) {
            return false;
        }

        $ids = preg_split('/[\n,]+/', $fallback);
        $ids = array_values(array_filter(array_map('trim', $ids)));
        return !empty($ids);
    }

    private function _liff_room_meta($meta = []) {
        if ($this->_liff_rooms_from_line_groups()) {
            $meta['force_token'] = get_setting('line_channel_access_token');
            $meta['force_token_label'] = 'line_channel_access_token';
        }
        return $meta;
    }

    /**
     * Notify users X minutes before task/event start_time.
     */
    private function liff_notify_before_start() {
        $db   = \Config\Database::connect();
        $Line = new \App\Libraries\Liff_line_webhook();
        $now  = date('Y-m-d H:i:s');
        $mt   = $this->_liff_mappings_table();
        $mode = $this->_liff_notify_mode();
        $rooms = $this->_liff_notify_rooms();

        // ── Tasks ─────────────────────────────────────────────────
        if ($mode === 'room') {
            if (empty($rooms)) { return; }
            $tasks = $db->query(
                "SELECT t.id, t.title, t.start_date, t.start_time, t.line_notify_before_start
                 FROM rise_tasks t
                 WHERE t.deleted = 0
                   AND t.line_notify_enabled = 1
                   AND t.line_notify_before_start IS NOT NULL
                   AND t.start_date IS NOT NULL
                   AND t.start_time IS NOT NULL
                   AND t.line_notify_sent_start IS NULL
                   AND TIMESTAMPDIFF(MINUTE, ?, CONCAT(t.start_date,' ',t.start_time))
                       BETWEEN 0 AND t.line_notify_before_start",
                [$now]
            )->getResult();
        } else {
            $tasks = $db->query(
                "SELECT t.id, t.title, t.start_date, t.start_time, t.line_notify_before_start,
                        m.line_liff_user_id AS line_user_id
                 FROM rise_tasks t
                 JOIN $mt m ON m.rise_user_id = t.assigned_to
                           AND m.is_active = 1
                           AND m.liff_notify_user = 1
                           AND m.line_liff_user_id IS NOT NULL
                           AND m.line_liff_user_id != ''
                 WHERE t.deleted = 0
                   AND t.line_notify_enabled = 1
                   AND t.line_notify_before_start IS NOT NULL
                   AND t.start_date IS NOT NULL
                   AND t.start_time IS NOT NULL
                   AND t.line_notify_sent_start IS NULL
                   AND TIMESTAMPDIFF(MINUTE, ?, CONCAT(t.start_date,' ',t.start_time))
                       BETWEEN 0 AND t.line_notify_before_start",
                [$now]
            )->getResult();
        }

        foreach ($tasks as $t) {
            $msg  = " แจ้งเตือนก่อนเริ่มงาน\n";
            $msg .= " {$t->title}\n";
            $msg .= "⏱ เริ่ม: " . date('d/m H:i', strtotime($t->start_date . ' ' . $t->start_time)) . "\n";
            $msg .= get_uri("liff/app/tasks/{$t->id}");
            $meta = ['task_id' => $t->id, 'type' => 'liff_task_before_start'];
            if ($mode === 'room') {
                $meta = $this->_liff_room_meta($meta);
                foreach ($rooms as $rid) {
                    $Line->send_push_message($rid, $msg, 'room', $meta);
                }
            } else if (!empty($t->line_user_id)) {
                $Line->send_push_message($t->line_user_id, $msg, 'user', $meta);
            }
            $db->query("UPDATE rise_tasks SET line_notify_sent_start=? WHERE id=?", [$now, $t->id]);
        }

        // ── Events ────────────────────────────────────────────────
        if ($mode === 'room') {
            if (empty($rooms)) { return; }
            $events = $db->query(
                "SELECT e.id, e.title, e.start_date, e.start_time, e.line_notify_before_start
                 FROM rise_events e
                 WHERE e.deleted = 0
                   AND e.line_notify_enabled = 1
                   AND e.line_notify_before_start IS NOT NULL
                   AND e.start_date IS NOT NULL
                   AND e.start_time IS NOT NULL
                   AND e.line_notify_sent_start IS NULL
                   AND TIMESTAMPDIFF(MINUTE, ?, CONCAT(e.start_date,' ',e.start_time))
                       BETWEEN 0 AND e.line_notify_before_start",
                [$now]
            )->getResult();
        } else {
            $events = $db->query(
                "SELECT e.id, e.title, e.start_date, e.start_time, e.line_notify_before_start,
                        m.line_liff_user_id AS line_user_id
                 FROM rise_events e
                 JOIN $mt m ON m.rise_user_id = e.created_by
                           AND m.is_active = 1
                           AND m.liff_notify_user = 1
                           AND m.line_liff_user_id IS NOT NULL
                           AND m.line_liff_user_id != ''
                 WHERE e.deleted = 0
                   AND e.line_notify_enabled = 1
                   AND e.line_notify_before_start IS NOT NULL
                   AND e.start_date IS NOT NULL
                   AND e.start_time IS NOT NULL
                   AND e.line_notify_sent_start IS NULL
                   AND TIMESTAMPDIFF(MINUTE, ?, CONCAT(e.start_date,' ',e.start_time))
                       BETWEEN 0 AND e.line_notify_before_start",
                [$now]
            )->getResult();
        }

        foreach ($events as $ev) {
            $msg  = " แจ้งเตือนก่อนเริ่ม Event\n";
            $msg .= "📅 {$ev->title}\n";
            $msg .= "⏱ เริ่ม: " . date('d/m H:i', strtotime($ev->start_date . ' ' . $ev->start_time)) . "\n";
            $msg .= get_uri("liff/app/events/{$ev->id}");
            $meta = ['event_id' => $ev->id, 'type' => 'liff_event_before_start'];
            if ($mode === 'room') {
                $meta = $this->_liff_room_meta($meta);
                foreach ($rooms as $rid) {
                    $Line->send_push_message($rid, $msg, 'room', $meta);
                }
            } else if (!empty($ev->line_user_id)) {
                $Line->send_push_message($ev->line_user_id, $msg, 'user', $meta);
            }
            $db->query("UPDATE rise_events SET line_notify_sent_start=? WHERE id=?", [$now, $ev->id]);
        }
    }

    /**
     * Notify users X minutes before task/event end_time.
     */
    private function liff_notify_before_end() {
        $db   = \Config\Database::connect();
        $Line = new \App\Libraries\Liff_line_webhook();
        $now  = date('Y-m-d H:i:s');
        $mt   = $this->_liff_mappings_table();
        $mode = $this->_liff_notify_mode();
        $rooms = $this->_liff_notify_rooms();

        // ── Tasks ─────────────────────────────────────────────────
        if ($mode === 'room') {
            if (empty($rooms)) { return; }
            $tasks = $db->query(
                "SELECT t.id, t.title, t.deadline, t.end_time, t.line_notify_before_end
                 FROM rise_tasks t
                 JOIN rise_task_status ts ON ts.id = t.status_id
                 WHERE t.deleted = 0
                   AND t.line_notify_enabled = 1
                   AND t.line_notify_before_end IS NOT NULL
                   AND t.deadline IS NOT NULL
                   AND t.end_time IS NOT NULL
                   AND ts.key_name != 'closed'
                   AND t.line_notify_sent_end IS NULL
                   AND TIMESTAMPDIFF(MINUTE, ?, CONCAT(t.deadline,' ',t.end_time))
                       BETWEEN 0 AND t.line_notify_before_end",
                [$now]
            )->getResult();
        } else {
            $tasks = $db->query(
                "SELECT t.id, t.title, t.deadline, t.end_time, t.line_notify_before_end,
                        m.line_liff_user_id AS line_user_id
                 FROM rise_tasks t
                 JOIN $mt m ON m.rise_user_id = t.assigned_to
                           AND m.is_active = 1
                           AND m.liff_notify_user = 1
                           AND m.line_liff_user_id IS NOT NULL
                           AND m.line_liff_user_id != ''
                 JOIN rise_task_status ts ON ts.id = t.status_id
                 WHERE t.deleted = 0
                   AND t.line_notify_enabled = 1
                   AND t.line_notify_before_end IS NOT NULL
                   AND t.deadline IS NOT NULL
                   AND t.end_time IS NOT NULL
                   AND ts.key_name != 'closed'
                   AND t.line_notify_sent_end IS NULL
                   AND TIMESTAMPDIFF(MINUTE, ?, CONCAT(t.deadline,' ',t.end_time))
                       BETWEEN 0 AND t.line_notify_before_end",
                [$now]
            )->getResult();
        }

        foreach ($tasks as $t) {
            $msg  = "⚠️ แจ้งเตือนก่อนสิ้นสุดงาน\n";
            $msg .= " {$t->title}\n";
            $msg .= "🔚 สิ้นสุด: " . date('d/m H:i', strtotime($t->deadline . ' ' . $t->end_time)) . "\n";
            $msg .= get_uri("liff/app/tasks/{$t->id}");
            $meta = ['task_id' => $t->id, 'type' => 'liff_task_before_end'];
            if ($mode === 'room') {
                $meta = $this->_liff_room_meta($meta);
                foreach ($rooms as $rid) {
                    $Line->send_push_message($rid, $msg, 'room', $meta);
                }
            } else if (!empty($t->line_user_id)) {
                $Line->send_push_message($t->line_user_id, $msg, 'user', $meta);
            }
            $db->query("UPDATE rise_tasks SET line_notify_sent_end=? WHERE id=?", [$now, $t->id]);
        }

        // ── Events ────────────────────────────────────────────────
        if ($mode === 'room') {
            if (empty($rooms)) { return; }
            $events = $db->query(
                "SELECT e.id, e.title, e.end_date, e.end_time, e.line_notify_before_end
                 FROM rise_events e
                 WHERE e.deleted = 0
                   AND e.line_notify_enabled = 1
                   AND e.line_notify_before_end IS NOT NULL
                   AND e.end_date IS NOT NULL
                   AND e.end_time IS NOT NULL
                   AND e.line_notify_sent_end IS NULL
                   AND TIMESTAMPDIFF(MINUTE, ?, CONCAT(e.end_date,' ',e.end_time))
                       BETWEEN 0 AND e.line_notify_before_end",
                [$now]
            )->getResult();
        } else {
            $events = $db->query(
                "SELECT e.id, e.title, e.end_date, e.end_time, e.line_notify_before_end,
                        m.line_liff_user_id AS line_user_id
                 FROM rise_events e
                 JOIN $mt m ON m.rise_user_id = e.created_by
                           AND m.is_active = 1
                           AND m.liff_notify_user = 1
                           AND m.line_liff_user_id IS NOT NULL
                           AND m.line_liff_user_id != ''
                 WHERE e.deleted = 0
                   AND e.line_notify_enabled = 1
                   AND e.line_notify_before_end IS NOT NULL
                   AND e.end_date IS NOT NULL
                   AND e.end_time IS NOT NULL
                   AND e.line_notify_sent_end IS NULL
                   AND TIMESTAMPDIFF(MINUTE, ?, CONCAT(e.end_date,' ',e.end_time))
                       BETWEEN 0 AND e.line_notify_before_end",
                [$now]
            )->getResult();
        }

        foreach ($events as $ev) {
            $msg  = "⚠️ แจ้งเตือนก่อนสิ้นสุด Event\n";
            $msg .= "📅 {$ev->title}\n";
            $msg .= "🔚 สิ้นสุด: " . date('d/m H:i', strtotime($ev->end_date . ' ' . $ev->end_time)) . "\n";
            $msg .= get_uri("liff/app/events/{$ev->id}");
            $meta = ['event_id' => $ev->id, 'type' => 'liff_event_before_end'];
            if ($mode === 'room') {
                $meta = $this->_liff_room_meta($meta);
                foreach ($rooms as $rid) {
                    $Line->send_push_message($rid, $msg, 'room', $meta);
                }
            } else if (!empty($ev->line_user_id)) {
                $Line->send_push_message($ev->line_user_id, $msg, 'user', $meta);
            }
            $db->query("UPDATE rise_events SET line_notify_sent_end=? WHERE id=?", [$now, $ev->id]);
        }
    }

    /**
     * Notify when a task hasn't been updated in X hours.
     */
    private function liff_notify_no_update() {
        $db   = \Config\Database::connect();
        $Line = new \App\Libraries\Liff_line_webhook();
        $now  = date('Y-m-d H:i:s');
        $mt   = $this->_liff_mappings_table();
        $mode = $this->_liff_notify_mode();
        $rooms = $this->_liff_notify_rooms();

        if ($mode === 'room') {
            if (empty($rooms)) { return; }
            $tasks = $db->query(
                "SELECT t.id, t.title,
                        COALESCE(t.status_changed_at, t.created_date) AS last_updated,
                        t.line_notify_no_update_hours
                 FROM rise_tasks t
                 JOIN rise_task_status ts ON ts.id = t.status_id
                 WHERE t.deleted = 0
                   AND t.line_notify_enabled = 1
                   AND t.line_notify_no_update_hours IS NOT NULL
                   AND ts.key_name != 'closed'
                   AND TIMESTAMPDIFF(HOUR, COALESCE(t.status_changed_at, t.created_date), ?) >= t.line_notify_no_update_hours
                   AND (t.line_notify_sent_no_update IS NULL
                        OR TIMESTAMPDIFF(HOUR, t.line_notify_sent_no_update, ?) >= t.line_notify_no_update_hours)",
                [$now, $now]
            )->getResult();
        } else {
            $tasks = $db->query(
                "SELECT t.id, t.title,
                        COALESCE(t.status_changed_at, t.created_date) AS last_updated,
                        t.line_notify_no_update_hours,
                        m.line_liff_user_id AS line_user_id
                 FROM rise_tasks t
                 JOIN $mt m ON m.rise_user_id = t.assigned_to
                           AND m.is_active = 1
                           AND m.liff_notify_user = 1
                           AND m.line_liff_user_id IS NOT NULL
                           AND m.line_liff_user_id != ''
                 JOIN rise_task_status ts ON ts.id = t.status_id
                 WHERE t.deleted = 0
                   AND t.line_notify_enabled = 1
                   AND t.line_notify_no_update_hours IS NOT NULL
                   AND ts.key_name != 'closed'
                   AND TIMESTAMPDIFF(HOUR, COALESCE(t.status_changed_at, t.created_date), ?) >= t.line_notify_no_update_hours
                   AND (t.line_notify_sent_no_update IS NULL
                        OR TIMESTAMPDIFF(HOUR, t.line_notify_sent_no_update, ?) >= t.line_notify_no_update_hours)",
                [$now, $now]
            )->getResult();
        }

        foreach ($tasks as $t) {
            $hours = (int)$t->line_notify_no_update_hours;
            $msg   = " ไม่มีการอัปเดตงาน {$hours} ชั่วโมงแล้ว\n";
            $msg  .= " {$t->title}\n";
            $msg  .= "อัปเดตล่าสุด: " . ($t->last_updated ? date('d/m H:i', strtotime($t->last_updated)) : '—') . "\n";
            $msg  .= get_uri("liff/app/tasks/{$t->id}");
            $meta = ['task_id' => $t->id, 'type' => 'liff_task_no_update'];
            if ($mode === 'room') {
                $meta = $this->_liff_room_meta($meta);
                foreach ($rooms as $rid) {
                    $Line->send_push_message($rid, $msg, 'room', $meta);
                }
            } else if (!empty($t->line_user_id)) {
                $Line->send_push_message($t->line_user_id, $msg, 'user', $meta);
            }
            $db->query("UPDATE rise_tasks SET line_notify_sent_no_update=? WHERE id=?", [$now, $t->id]);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  LIFF: แจ้งเตือนงานค้าง (scheduled reminder)
    // ══════════════════════════════════════════════════════════════════
    private function liff_task_reminder() {
        $enabled = get_setting('liff_reminder_enabled');
        if ($enabled !== '1') {
            $this->_liff_log("REMINDER: disabled (value='" . $enabled . "'), skipped");
            return;
        }

        $times  = json_decode(get_setting('liff_reminder_times') ?: '[]', true) ?: [];
        $repeat = get_setting('liff_reminder_repeat') === '1';
        $days   = json_decode(get_setting('liff_reminder_days')  ?: '[]', true) ?: [1,2,3,4,5];

        $reason = '';
        if (!$this->_is_notify_time_now($times, $days, $repeat, 'liff_reminder_last_sent', $reason)) {
            $this->_liff_log("REMINDER: skipped — {$reason}");
            return;
        }

        $this->_liff_log("REMINDER: firing — {$reason}");
        try {
            $count = $this->_send_task_reminder_flex(false);
            $this->ci->Settings_model->save_setting('liff_reminder_last_sent', get_current_utc_time());
            $this->_liff_log("REMINDER: sent OK — {$count} task(s)");
        } catch (\Exception $e) {
            $this->_liff_log("REMINDER: ERROR — " . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  LIFF: สรุปงานเสร็จ 7 วัน (scheduled summary)
    // ══════════════════════════════════════════════════════════════════
    private function liff_task_summary() {
        $enabled = get_setting('liff_summary_enabled');
        if ($enabled !== '1') {
            $this->_liff_log("SUMMARY: disabled (value='" . $enabled . "'), skipped");
            return;
        }

        $times = [get_setting('liff_summary_time') ?: '08:00'];
        $days  = json_decode(get_setting('liff_summary_days') ?: '[]', true) ?: [1,2,3,4,5];

        $reason = '';
        if (!$this->_is_notify_time_now($times, $days, true, 'liff_summary_last_sent', $reason)) {
            $this->_liff_log("SUMMARY: skipped — {$reason}");
            return;
        }

        $this->_liff_log("SUMMARY: firing — {$reason}");
        try {
            $count = $this->_send_task_summary_flex(false);
            $this->ci->Settings_model->save_setting('liff_summary_last_sent', get_current_utc_time());
            $this->_liff_log("SUMMARY: sent OK — {$count} task(s)");
        } catch (\Exception $e) {
            $this->_liff_log("SUMMARY: ERROR — " . $e->getMessage());
        }
    }

    // ── Public test entry-points (called from Liff_settings controller) ──

    public function run_task_reminder_test() {
        $this->ci = new \App\Controllers\App_Controller();
        return $this->_send_task_reminder_flex(true);
    }

    public function run_task_summary_test() {
        $this->ci = new \App\Controllers\App_Controller();
        return $this->_send_task_summary_flex(true);
    }

    // ── Core: build & send incomplete-task reminder ───────────────────

    private function _send_task_reminder_flex($is_test = false) {
        $db   = \Config\Database::connect();
        $Line = new \App\Libraries\Liff_line_webhook();

        $mode  = get_setting('liff_notify_mode') ?: 'user';
        $rooms = $this->_get_liff_room_ids();
        $failed = 0;
        $errors = [];

        // Get all incomplete tasks grouped by assigned user
        $mt    = get_user_mappings_table();
        $liff_base = rtrim(get_setting('line_liff_id') ?: '2009171467-kn2AHM0C', '/');

        $rows = $db->query("
            SELECT
                u.id AS user_id,
                CONCAT(u.first_name,' ',u.last_name) AS user_name,
                m.line_liff_user_id AS line_user_id,
                t.id AS task_id,
                t.title AS task_title
            FROM rise_users u
            JOIN rise_tasks t ON t.assigned_to = u.id AND t.deleted = 0
            LEFT JOIN rise_task_status ts ON ts.id = t.status_id
            LEFT JOIN $mt m ON m.rise_user_id = u.id
                           AND m.is_active = 1
                           AND m.line_liff_user_id IS NOT NULL
                           AND m.line_liff_user_id != ''
            WHERE (ts.key_name IS NULL OR ts.key_name != 'done')
              AND u.deleted = 0
            ORDER BY u.id, t.id
        ")->getResult();

        if (empty($rows)) {
            return $is_test ? ['count' => 0, 'failed' => 0, 'errors' => []] : 0;
        }

        // Group by user
        $users = [];
        foreach ($rows as $r) {
            $uid = $r->user_id;
            if (!isset($users[$uid])) {
                $users[$uid] = [
                    'user_name'   => $r->user_name,
                    'line_user_id'=> $r->line_user_id,
                    'tasks'       => [],
                ];
            }
            $users[$uid]['tasks'][] = ['id' => $r->task_id, 'title' => $r->task_title];
        }

        $total_users = count($users);
        if ($total_users === 0) { return 0; }

        // Build one combined Flex carousel or stacked bubble
        // If room mode — send a single combined message to each room
        // If user mode — send individual bubble per user to their own LINE
        if ($mode === 'room') {
            if (empty($rooms)) {
                return $is_test ? ['count' => 0, 'failed' => 1, 'errors' => ['missing_room_ids']] : 0;
            }
            $flex = $this->_build_reminder_carousel($users, $liff_base);
            $alt  = "📋 สรุปงานค้าง — {$total_users} คน";
            $meta = $this->_liff_room_meta(['type' => 'liff_task_reminder']);
            foreach ($rooms as $rid) {
                $res = $Line->send_flex_message($rid, $flex, $alt, 'room', $meta);
                if (empty($res['success'])) {
                    $failed++;
                    if (!empty($res['error'])) {
                        $errors[] = $rid . ': ' . $res['error'];
                    }
                }
            }
        } else {
            foreach ($users as $uid => $u) {
                if (empty($u['line_user_id'])) { continue; }
                $single = $this->_build_reminder_bubble_single($u['user_name'], $u['tasks'], $liff_base);
                $alt    = $u['user_name'] . ' มีงานค้าง ' . count($u['tasks']) . ' รายการ';
                $res = $Line->send_flex_message($u['line_user_id'], $single, $alt, 'user', ['type' => 'liff_task_reminder']);
                if (empty($res['success'])) {
                    $failed++;
                    if (!empty($res['error'])) {
                        $errors[] = $u['line_user_id'] . ': ' . $res['error'];
                    }
                }
            }
        }

        $count = array_sum(array_map(fn($u) => count($u['tasks']), $users));
        return $is_test ? ['count' => $count, 'failed' => $failed, 'errors' => $errors] : $count;
    }

    // ── Core: build & send 7-day completion summary ───────────────────

    private function _send_task_summary_flex($is_test = false) {
        $db        = \Config\Database::connect();
        $Line      = new \App\Libraries\Liff_line_webhook();
        $mode      = get_setting('liff_notify_mode') ?: 'user';
        $rooms     = $this->_get_liff_room_ids();
        $liff_base = rtrim(get_setting('line_liff_id') ?: '2009171467-kn2AHM0C', '/');
        $since     = date('Y-m-d', strtotime('-7 days'));

        // Tasks completed in last 7 days (status done, updated within window)
        $mt = get_user_mappings_table();
        $rows = $db->query("
            SELECT
                u.id AS user_id,
                CONCAT(u.first_name,' ',u.last_name) AS user_name,
                m.line_liff_user_id AS line_user_id,
                COUNT(t.id) AS done_count
            FROM rise_users u
            JOIN rise_tasks t ON t.assigned_to = u.id AND t.deleted = 0
            JOIN rise_task_status ts ON ts.id = t.status_id AND ts.key_name = 'done'
            LEFT JOIN $mt m ON m.rise_user_id = u.id
                           AND m.is_active = 1
                           AND m.line_liff_user_id IS NOT NULL
                           AND m.line_liff_user_id != ''
            WHERE t.status_changed_at >= ?
              AND u.deleted = 0
            GROUP BY u.id
            HAVING done_count > 0
            ORDER BY done_count DESC
        ", [$since . ' 00:00:00'])->getResult();

        if (empty($rows)) { return 0; }

        $total_tasks = array_sum(array_column($rows, 'done_count'));
        $total_users = count($rows);

        $flex     = $this->_build_summary_bubble($rows, $since, $total_tasks);
        $alt_text = " สรุปงานเสร็จ 7 วัน — {$total_tasks} งาน จาก {$total_users} คน";

        if ($mode === 'room' && !empty($rooms)) {
            $meta = $this->_liff_room_meta(['type' => 'liff_task_summary']);
            foreach ($rooms as $rid) {
                $Line->send_flex_message($rid, $flex, $alt_text, 'room', $meta);
            }
        } else {
            // For user mode, send the combined summary to users who have LINE linked
            foreach ($rows as $r) {
                if (empty($r->line_user_id)) { continue; }
                $Line->send_flex_message($r->line_user_id, $flex, $alt_text, 'user', ['type' => 'liff_task_summary']);
            }
        }

        return (int)$total_tasks;
    }

    // ── Flex builders ─────────────────────────────────────────────────

    /**
     * Single-user reminder bubble
     */
    private function _build_reminder_bubble_single($user_name, $tasks, $liff_base) {
        $task_count = count($tasks);
        $body_rows  = [];

        foreach (array_slice($tasks, 0, 8) as $i => $t) {
            $body_rows[] = [
                'type'   => 'box',
                'layout' => 'horizontal',
                'contents' => [
                    [
                        'type'  => 'text',
                        'text'  => ($i + 1) . '.',
                        'size'  => 'xs',
                        'color' => '#94A3B8',
                        'flex'  => 0,
                        'align' => 'start',
                    ],
                    [
                        'type'      => 'text',
                        'text'      => $t['title'],
                        'size'      => 'xs',
                        'color'     => '#334155',
                        'wrap'      => true,
                        'maxLines'  => 2,
                        'flex'      => 1,
                    ],
                ],
                'spacing' => 'xs',
            ];
        }

        if ($task_count > 8) {
            $body_rows[] = [
                'type'  => 'text',
                'text'  => '... และอีก ' . ($task_count - 8) . ' งาน',
                'size'  => 'xs',
                'color' => '#94A3B8',
            ];
        }

        $liff_url = 'https://liff.line.me/' . $liff_base;

        return [
            'type'   => 'bubble',
            'size'   => 'kilo',
            'header' => [
                'type'            => 'box',
                'layout'          => 'horizontal',
                'backgroundColor' => '#F97316',
                'paddingAll'      => '12px',
                'contents'        => [
                    [
                        'type'   => 'text',
                        'text'   => ' งานที่ยังไม่เสร็จ',
                        'color'  => '#FFFFFF',
                        'weight' => 'bold',
                        'size'   => 'sm',
                    ],
                ],
            ],
            'body' => [
                'type'       => 'box',
                'layout'     => 'vertical',
                'paddingAll' => '14px',
                'spacing'    => 'sm',
                'contents'   => array_merge(
                    [[
                        'type'   => 'text',
                        'text'   => $user_name . ' มีงานค้าง ' . $task_count . ' รายการ',
                        'weight' => 'bold',
                        'size'   => 'sm',
                        'color'  => '#1E293B',
                        'wrap'   => true,
                    ],
                    [
                        'type'   => 'separator',
                        'margin' => 'sm',
                        'color'  => '#FED7AA',
                    ]],
                    $body_rows
                ),
            ],
            'footer' => [
                'type'       => 'box',
                'layout'     => 'vertical',
                'paddingAll' => '10px',
                'contents'   => [
                    [
                        'type'   => 'button',
                        'style'  => 'primary',
                        'color'  => '#F97316',
                        'height' => 'sm',
                        'action' => [
                            'type'  => 'uri',
                            'label' => 'อัปเดตงาน',
                            'uri'   => $liff_url,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Room mode — carousel of per-user bubbles (max 12 bubbles per carousel)
     */
    private function _build_reminder_carousel($users, $liff_base) {
        $bubbles = [];
        foreach (array_slice($users, 0, 12) as $u) {
            $bubbles[] = $this->_build_reminder_bubble_single(
                $u['user_name'], $u['tasks'], $liff_base
            );
        }

        return [
            'type'     => 'carousel',
            'contents' => $bubbles,
        ];
    }

    /**
     * Summary bubble: tasks completed in 7 days per user
     */
    private function _build_summary_bubble($rows, $since, $total_tasks) {
        $since_fmt = date('d/m/Y', strtotime($since));
        $today_fmt = date('d/m/Y');

        $body_rows = [];
        foreach (array_slice($rows, 0, 10) as $r) {
            $body_rows[] = [
                'type'    => 'box',
                'layout'  => 'horizontal',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type'  => 'text',
                        'text'  => $r->user_name,
                        'size'  => 'xs',
                        'color' => '#334155',
                        'flex'  => 1,
                        'wrap'  => true,
                    ],
                    [
                        'type'   => 'text',
                        'text'   => $r->done_count . ' งาน',
                        'size'   => 'xs',
                        'color'  => '#00B393',
                        'weight' => 'bold',
                        'flex'   => 0,
                        'align'  => 'end',
                    ],
                ],
            ];
        }

        if (count($rows) > 10) {
            $body_rows[] = [
                'type'  => 'text',
                'text'  => '... และอีก ' . (count($rows) - 10) . ' คน',
                'size'  => 'xs',
                'color' => '#94A3B8',
            ];
        }

        return [
            'type'   => 'bubble',
            'size'   => 'kilo',
            'header' => [
                'type'            => 'box',
                'layout'          => 'vertical',
                'backgroundColor' => '#00B393',
                'paddingAll'      => '12px',
                'contents'        => [
                    [
                        'type'   => 'text',
                        'text'   => ' สรุปงานเสร็จประจำสัปดาห์',
                        'color'  => '#FFFFFF',
                        'weight' => 'bold',
                        'size'   => 'sm',
                    ],
                    [
                        'type'  => 'text',
                        'text'  => $since_fmt . ' – ' . $today_fmt,
                        'color' => '#CCFBF1',
                        'size'  => 'xxs',
                    ],
                ],
            ],
            'body' => [
                'type'       => 'box',
                'layout'     => 'vertical',
                'paddingAll' => '14px',
                'spacing'    => 'sm',
                'contents'   => array_merge(
                    [[
                        'type'   => 'text',
                        'text'   => 'ทีมเสร็จงานรวม ' . $total_tasks . ' รายการ',
                        'weight' => 'bold',
                        'size'   => 'sm',
                        'color'  => '#1E293B',
                    ],
                    [
                        'type'   => 'separator',
                        'margin' => 'sm',
                        'color'  => '#99F6E4',
                    ]],
                    $body_rows
                ),
            ],
            'footer' => [
                'type'       => 'box',
                'layout'     => 'vertical',
                'paddingAll' => '10px',
                'contents'   => [
                    [
                        'type'   => 'text',
                        'text'   => 'ขอบคุณทุกคนที่ช่วยกัน! 💪',
                        'size'   => 'xs',
                        'color'  => '#64748B',
                        'align'  => 'center',
                    ],
                ],
            ],
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Check whether "now" falls within any of the given time slots (±30 min window)
     * and that today's day-of-week is in the allowed days list,
     * and that we haven't already sent within the last 45 minutes.
     *
     * @param array  $times      e.g. ['09:00','15:00']
     * @param array  $days       day-of-week 1=Mon … 7=Sun (ISO)
     * @param bool   $repeat     if false, only fire once per slot ever (not needed here but kept)
     * @param string $last_key   settings key that stores last-sent datetime
     */
    private function _is_notify_time_now($times, $days, $repeat, $last_key, &$reason = '') {
        if (empty($times)) { $reason = 'no times configured'; return false; }

        $dow = (int)date('N'); // 1=Mon, 7=Sun

        if (!in_array($dow, $days)) {
            $reason = "today=day{$dow} not in allowed=[" . implode(',', $days) . "]";
            return false;
        }

        // Check last sent — skip if already sent within 45 min
        $last_sent = get_setting($last_key);
        if ($last_sent) {
            $diff = time() - strtotime($last_sent);
            if ($diff < 2700) {
                $reason = "cooldown: sent {$diff}s ago (need 2700s), last={$last_sent}";
                return false;
            }
        }

        $now_hm  = date('H:i');
        $now_min = (int)date('H') * 60 + (int)date('i');

        foreach ($times as $t) {
            $parts = explode(':', $t);
            if (count($parts) < 2) { continue; }
            $slot_min = (int)$parts[0] * 60 + (int)$parts[1];
            $diff = abs($now_min - $slot_min);
            if ($diff <= 30) {
                $reason = "matched slot={$t} now={$now_hm} diff={$diff}min";
                return true;
            }
        }

        $reason = "no match: now={$now_hm}({$now_min}min) slots=[" . implode(',', $times) . "]";
        return false;
    }

    /** Prepend a line to the LIFF notification debug log (keeps last 80 lines) */
    private function _liff_log($msg) {
        // Read directly from DB (not CI cache) so sequential log entries accumulate correctly
        $raw   = $this->ci->Settings_model->get_setting('liff_notify_debug_log') ?: '';
        $lines = $raw ? explode("\n", $raw) : [];
        array_unshift($lines, '[' . date('d/m H:i:s') . '] ' . $msg);
        $this->ci->Settings_model->save_setting(
            'liff_notify_debug_log',
            implode("\n", array_slice($lines, 0, 80))
        );
    }

    private function _get_liff_room_ids() {
        return $this->_liff_notify_rooms();
    }

}
