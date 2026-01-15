<div class="day-details-container">
    <h5 class="mb-3"><?php echo app_lang("attendance_for"); ?> <?php echo $date; ?></h5>
    
    <!-- Attendance Section - Full Width First -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><?php echo app_lang("employees_present"); ?> (<?php echo count($attendance_data); ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if ($attendance_data): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th><?php echo app_lang("employee"); ?></th>
                                        <th><?php echo app_lang("clock_in"); ?></th>
                                        <th><?php echo app_lang("clock_out"); ?></th>
                                        <th><?php echo app_lang("duration"); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_data as $data): ?>
                                        <?php
                                        $out_time = $data->out_time;
                                        if (!is_date_exists($out_time)) {
                                            $out_time = "";
                                        }
                                        
                                        $to_time = strtotime($data->out_time ? $data->out_time : "");
                                        if (!$out_time) {
                                            $to_time = strtotime($data->in_time ? $data->in_time : "");
                                        }
                                        $from_time = strtotime($data->in_time ? $data->in_time : "");
                                        $duration = convert_seconds_to_time_format(abs($to_time - $from_time));
                                        ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $image_url = get_avatar($data->created_by_avatar);
                                                echo "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span>";
                                                echo $data->created_by_user;
                                                ?>
                                            </td>
                                            <td><?php echo format_to_time($data->in_time); ?></td>
                                            <td><?php echo $out_time ? format_to_time($out_time) : "<span class='text-warning'>" . app_lang("not_clocked_out") . "</span>"; ?></td>
                                            <td><?php echo $duration; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i data-feather="clock" class="icon-48 text-muted mb-3"></i>
                            <p class="text-muted"><?php echo app_lang("no_attendance_found"); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Leave Section - Full Width Second -->
    <?php if (isset($leave_data) && count($leave_data) > 0): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h6 class="mb-0"><?php echo app_lang("employees_on_leave"); ?> (<?php echo count($leave_data); ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th><?php echo app_lang("employee"); ?></th>
                                    <th><?php echo app_lang("leave_type"); ?></th>
                                    <th><?php echo app_lang("duration"); ?></th>
                                    <th><?php echo app_lang("reason"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leave_data as $leave): ?>
                                    <?php
                                    // Calculate leave duration
                                    $start_date = new DateTime($leave->start_date);
                                    $end_date = new DateTime($leave->end_date);
                                    $interval = $start_date->diff($end_date);
                                    $days = $interval->days + 1; // +1 to include both start and end dates
                                    ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $image_url = get_avatar($leave->applicant_avatar);
                                            echo "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span>";
                                            echo $leave->applicant_name;
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($leave->leave_type_color) && $leave->leave_type_color): ?>
                                                <span class="badge" style="background-color: <?php echo $leave->leave_type_color; ?>;">
                                                    <?php echo isset($leave->leave_type_title) ? $leave->leave_type_title : app_lang("leave"); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo isset($leave->leave_type_title) ? $leave->leave_type_title : app_lang("leave"); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($days == 1): ?>
                                                <span class="text-info"><?php echo app_lang("full_day"); ?></span>
                                            <?php else: ?>
                                                <span class="text-info"><?php echo $days . ' ' . app_lang("days"); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($leave->reason) && !empty($leave->reason)): ?>
                                                <span class="text-muted" title="<?php echo $leave->reason; ?>">
                                                    <?php echo strlen($leave->reason) > 30 ? substr($leave->reason, 0, 30) . '...' : $leave->reason; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Summary Section -->
    <?php if (isset($leave_data)): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><?php echo app_lang("daily_summary"); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3">
                                <h4 class="text-success"><?php echo count($attendance_data); ?></h4>
                                <small class="text-muted"><?php echo app_lang("present"); ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h4 class="text-warning"><?php echo count($leave_data); ?></h4>
                                <small class="text-muted"><?php echo app_lang("on_leave"); ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h4 class="text-info"><?php echo count($attendance_data) + count($leave_data); ?></h4>
                                <small class="text-muted"><?php echo app_lang("total_accounted"); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>