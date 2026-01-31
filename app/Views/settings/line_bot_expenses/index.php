<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "line_bot_expenses";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h1><i data-feather="message-circle" class="icon-16"></i> <?php echo app_lang('line_bot_expenses'); ?></h1>
                </div>

                <ul class="nav nav-tabs bg-white title" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#lbe-settings-tab" role="tab"><?php echo app_lang('settings'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lbe-title-keywords-tab" role="tab"><?php echo app_lang('title_keywords'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lbe-project-keywords-tab" role="tab"><?php echo app_lang('project_keywords'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lbe-category-keywords-tab" role="tab"><?php echo app_lang('category_keywords'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lbe-workflow-tab" role="tab"><?php echo app_lang('line_expenses_workflow'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lbe-logs-tab" role="tab"><?php echo app_lang('line_expenses_logs'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lbe-daily-report-tab" role="tab"><?php echo app_lang('daily_report_settings'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lbe-monthly-report-tab" role="tab"><?php echo app_lang('monthly_report_settings'); ?></a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="lbe-settings-tab" role="tabpanel">
                        <?php echo view('settings/line_bot_expenses/settings_tab', $this->data); ?>
                    </div>
                    <div class="tab-pane fade" id="lbe-title-keywords-tab" role="tabpanel">
                        <?php echo view('settings/line_bot_expenses/title_keywords_tab', $this->data); ?>
                    </div>
                    <div class="tab-pane fade" id="lbe-project-keywords-tab" role="tabpanel">
                        <?php echo view('settings/line_bot_expenses/project_keywords_tab', $this->data); ?>
                    </div>
                    <div class="tab-pane fade" id="lbe-category-keywords-tab" role="tabpanel">
                        <?php echo view('settings/line_bot_expenses/category_keywords_tab', $this->data); ?>
                    </div>
                    <div class="tab-pane fade" id="lbe-workflow-tab" role="tabpanel">
                        <?php echo view('settings/line_bot_expenses/workflow_tab', $this->data); ?>
                    </div>
                    <div class="tab-pane fade" id="lbe-logs-tab" role="tabpanel">
                        <?php echo view('settings/line_bot_expenses/logs_tab', $this->data); ?>
                    </div>
                    <div class="tab-pane fade" id="lbe-daily-report-tab" role="tabpanel">
                        <?php echo view('settings/line_bot_expenses/daily_report_tab', $this->data); ?>
                    </div>
                    <div class="tab-pane fade" id="lbe-monthly-report-tab" role="tabpanel">
                        <?php echo view('settings/line_bot_expenses/monthly_report_tab', $this->data); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
