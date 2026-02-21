<?php

namespace App\Controllers;

use App\Libraries\Cron_job;

class Cron extends App_Controller {

    private $cron_job;

    function __construct() {
        parent::__construct();
        $this->cron_job = new Cron_job();
    }

    // ── Secret-protected endpoint for Node.js scheduler ──────────────────────
    // Called by test-with-node/server.js when schedule matches.
    // Secret is auto-generated and stored in rise_settings as 'liff_cron_secret'.
    function run_liff() {
        ini_set('max_execution_time', 120);

        // Verify secret
        $secret   = get_setting('liff_cron_secret');
        $provided = $this->request->getHeaderLine('X-Cron-Secret') ?: $this->request->getGet('secret');

        if (!$secret || $provided !== $secret) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $type = $this->request->getGet('type') ?: 'reminder';

        try {
            $count = 0;
            if ($type === 'reminder') {
                $count = $this->cron_job->run_task_reminder_test();
                $this->Settings_model->save_setting('liff_reminder_last_sent', get_current_utc_time());
            } else if ($type === 'summary') {
                $count = $this->cron_job->run_task_summary_test();
                $this->Settings_model->save_setting('liff_summary_last_sent', get_current_utc_time());
            } else if ($type === 'event_reminder' || $type === 'event_daily') {
                $count = $this->cron_job->run_event_reminder_test();
                $this->Settings_model->save_setting('liff_event_reminder_last_sent', get_current_utc_time());
            } else if ($type === 'events' || $type === 'event') {
                $count = $this->cron_job->run_liff_event_notifications();
            }
            return $this->response->setJSON(['success' => true, 'type' => $type, 'count' => $count]);
        } catch (\Throwable $e) {
            log_message('error', 'cron/run_liff: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    function index() {
        ini_set('max_execution_time', 300); //execute maximum 300 seconds 

        $last_cron_job_time = get_setting('last_cron_job_time');

        $minimum_cron_interval_seconds = get_setting('minimum_cron_interval_seconds');
        if (!$minimum_cron_interval_seconds) {
            $minimum_cron_interval_seconds = 300; //5 minutes
        }

        $current_time = strtotime(get_current_utc_time());

        if ($last_cron_job_time == "" || ($current_time > ($last_cron_job_time * 1 + $minimum_cron_interval_seconds))) {
            $this->cron_job->run();
            app_hooks()->do_action("app_hook_after_cron_run");
            $this->Settings_model->save_setting("last_cron_job_time", $current_time);
            echo "Cron job executed.";
        } else {
            $start = new \DateTime(date("Y-m-d H:i:s", $last_cron_job_time * 1 + $minimum_cron_interval_seconds));
            $end = new \DateTime();
            $diff = $end->diff($start);
            $format = "%i minutes, %s seconds.";

            if ($diff->i <= 0) {
                $format = "%s seconds.";
            }
            echo "Please try after " . $end->diff($start)->format($format);
        }
    }
}

/* End of file Cron.php */
/* Location: ./app/controllers/Cron.php */
