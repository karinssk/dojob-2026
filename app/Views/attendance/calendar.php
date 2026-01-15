<?php
load_css(array(
    "assets/js/fullcalendar/fullcalendar.min.css"
));

load_js(array(
    "assets/js/fullcalendar/fullcalendar.min.js",
    "assets/js/fullcalendar/locales-all.min.js"
));
?>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-4">
                <h4><?php echo app_lang("attendance_calendar"); ?></h4>
            </div>
            <div class="col-md-8">
                <div class="float-end">
                    <div class="d-inline-block mr15">
                        <label class="form-label"><?php echo app_lang("select_users"); ?>:</label>
                        <?php echo form_input(array(
                            "id" => "calendar-users-filter",
                            "name" => "calendar-users-filter",
                            "class" => "select2 w250",
                            "multiple" => "multiple"
                        )); ?>
                    </div>
                    <div class="d-inline-block mr15">
                        <label class="form-label"><?php echo app_lang("view_mode"); ?>:</label>
                        <select id="calendar-view-mode" class="form-control w150 d-inline-block">
                            <option value="all"><?php echo app_lang("show_all_users"); ?></option>
                            <option value="selected"><?php echo app_lang("show_selected_users"); ?></option>
                        </select>
                    </div>
                    <div class="d-inline-block">
                        <label class="form-label"><?php echo app_lang("show_events"); ?>:</label>
                        <div class="form-check-inline">
                            <input type="checkbox" id="show-attendance" class="form-check-input" checked>
                            <label class="form-check-label" for="show-attendance"><?php echo app_lang("attendance"); ?></label>
                        </div>
                        <div class="form-check-inline ml-3">
                            <input type="checkbox" id="show-leaves" class="form-check-input" checked>
                            <label class="form-check-label" for="show-leaves"><?php echo app_lang("leaves"); ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div id="attendance-calendar"></div>
    </div>
</div>

<style>
.fc-event-title {
    font-weight: 500;
}
.fc-event {
    border-radius: 3px;
    margin: 1px 0;
    font-size: 11px;
}
.fc-daygrid-event {
    margin: 1px 0;
    padding: 2px 4px;
    min-height: 18px;
}
.fc-daygrid-day-events {
    margin-bottom: 2px;
}
.fc-more-link {
    font-size: 10px;
    color: #007bff;
    cursor: pointer;
}
.fc-daygrid-event .fc-event-title {
    font-size: 10px;
    line-height: 1.2;
    word-wrap: break-word;
    white-space: normal;
}
.attendance-event {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
}
.day-details-container .card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.day-details-container .table-sm th,
.day-details-container .table-sm td {
    padding: 0.3rem;
    font-size: 12px;
}
.day-details-container .list-group-item {
    padding: 0.5rem 1rem;
    font-size: 13px;
}
.employee-attendance-details .info-item {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 0.5rem;
}
.employee-attendance-details .info-item:last-child {
    border-bottom: none;
}
.employee-attendance-details .form-label {
    font-size: 12px;
    margin-bottom: 0.25rem;
}
.employee-attendance-details .fw-bold {
    font-size: 14px;
}
.employee-attendance-details .avatar {
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.employee-attendance-details .avatar-lg {
    transition: transform 0.2s ease;
}
.employee-attendance-details .avatar-lg:hover {
    transform: scale(1.05);
}
</style>

<!-- Day Details Modal -->
<div class="modal fade" id="day-details-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php echo app_lang("attendance_details"); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="day-details-content"></div>
            </div>
        </div>
    </div>
</div>

<!-- Individual Employee Attendance Modal -->
<div class="modal fade" id="employee-attendance-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php echo app_lang("employee_attendance_details"); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="employee-attendance-content"></div>
            </div>
        </div>
    </div>
</div>

<!-- Leave Details Modal -->
<div class="modal fade" id="leave-details-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php echo app_lang("leave_details"); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="leave-details-content"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var selectedUserIds = [];
    var viewMode = "all";
    var showAttendance = true;
    var showLeaves = true;
    
    // Initialize user dropdown
    var teamMembersDropdown = <?php echo $team_members_dropdown; ?>;
    $("#calendar-users-filter").select2({
        data: teamMembersDropdown,
        placeholder: "<?php echo app_lang('select_users'); ?>",
        allowClear: true
    });
    
    var loadAttendanceCalendar = function () {
        var $attendanceCalendar = document.getElementById('attendance-calendar');
        
        appLoader.show();
        
        window.attendanceCalendar = new FullCalendar.Calendar($attendanceCalendar, {
            locale: AppLanugage.locale,
            height: $(window).height() - 300,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            initialView: 'dayGridMonth',
            dayMaxEvents: 6, // Show up to 6 events per day
            moreLinkClick: 'popover', // Show popover when clicking "more" link
            events: function(fetchInfo, successCallback, failureCallback) {
                var startDate = moment(fetchInfo.start).format('YYYY-MM-DD');
                var endDate = moment(fetchInfo.end).format('YYYY-MM-DD');
                
                $.ajax({
                    url: '<?php echo_uri("attendance/calendar_data"); ?>',
                    type: 'POST',
                    data: {
                        start_date: startDate,
                        end_date: endDate,
                        user_ids: selectedUserIds.join(','),
                        view_mode: viewMode,
                        show_attendance: showAttendance,
                        show_leaves: showLeaves
                    },
                    success: function(response) {
                        try {
                            var events = JSON.parse(response);
                            successCallback(events);
                        } catch (e) {
                            console.error('Error parsing calendar data:', e);
                            console.log('Response:', response);
                            failureCallback();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        failureCallback();
                    }
                });
            },
            eventContent: function(element) {
                var event = element.event;
                var title = event.title;
                var backgroundColor = event.backgroundColor;
                
                return {
                    html: "<span class='clickable p2 w100p inline-block' style='background-color: " + backgroundColor + "; color: #fff; border-radius: 3px; font-size: 11px; word-wrap: break-word; white-space: normal;'>" + title + "</span>"
                };
            },
            dateClick: function(info) {
                showDayDetails(info.dateStr);
            },
            eventClick: function(info) {
                var event = info.event;
                if (event.extendedProps.event_type === 'leave') {
                    showLeaveDetails(event.extendedProps);
                } else {
                    showEmployeeAttendanceDetails(event.extendedProps);
                }
            },
            loading: function(state) {
                if (state === false) {
                    appLoader.hide();
                    $(".fc-prev-button").html("<i data-feather='chevron-left' class='icon-16'></i>");
                    $(".fc-next-button").html("<i data-feather='chevron-right' class='icon-16'></i>");
                    feather.replace();
                    setTimeout(function () {
                        feather.replace();
                    }, 100);
                }
            },
            firstDay: AppHelper.settings.firstDayOfWeek
        });
        
        window.attendanceCalendar.render();
    };
    
    // Function to show day details in modal
    function showDayDetails(date) {
        $.ajax({
            url: '<?php echo_uri("attendance/day_details"); ?>',
            type: 'POST',
            data: {
                date: date,
                user_ids: selectedUserIds.join(','),
                view_mode: viewMode
            },
            success: function(response) {
                $('#day-details-content').html(response);
                $('#day-details-modal').modal('show');
            },
            error: function() {
                alert('Error loading day details');
            }
        });
    }
    
    // Function to show individual employee attendance details
    function showEmployeeAttendanceDetails(eventProps) {
        var content = '<div class="employee-attendance-details">';
        content += '<div class="row">';
        content += '<div class="col-12">';
        content += '<div class="card">';
        content += '<div class="card-header bg-success text-white">';
        content += '<h6 class="mb-0 d-flex align-items-center">';
        content += '<img src="' + eventProps.avatar_url + '" alt="' + eventProps.user_name + '" class="avatar avatar-sm me-2" style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid white;">';
        content += eventProps.user_name;
        content += '</h6>';
        content += '</div>';
        content += '<div class="card-body">';
        content += '<div class="row mb-3">';
        content += '<div class="col-12 text-center">';
        content += '<img src="' + eventProps.avatar_url + '" alt="' + eventProps.user_name + '" class="avatar avatar-lg mb-2" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid #28a745;">';
        content += '<h5 class="mb-0">' + eventProps.user_name + '</h5>';
        content += '<small class="text-muted"><?php echo app_lang("employee_id"); ?>: ' + eventProps.user_id + '</small>';
        content += '</div>';
        content += '</div>';
        content += '<div class="row">';
        content += '<div class="col-md-6">';
        content += '<div class="info-item mb-3">';
        content += '<label class="form-label text-muted"><?php echo app_lang("date"); ?>:</label>';
        content += '<div class="fw-bold">' + eventProps.date + '</div>';
        content += '</div>';
        content += '<div class="info-item mb-3">';
        content += '<label class="form-label text-muted"><?php echo app_lang("clock_in"); ?>:</label>';
        content += '<div class="fw-bold text-success">' + eventProps.clock_in + '</div>';
        content += '</div>';
        content += '</div>';
        content += '<div class="col-md-6">';
        content += '<div class="info-item mb-3">';
        content += '<label class="form-label text-muted"><?php echo app_lang("clock_out"); ?>:</label>';
        if (eventProps.clock_out) {
            content += '<div class="fw-bold text-primary">' + eventProps.clock_out + '</div>';
        } else {
            content += '<div class="fw-bold text-warning"><?php echo app_lang("not_clocked_out"); ?></div>';
        }
        content += '</div>';
        content += '</div>';
        content += '</div>';
        
        // Calculate duration if both clock in and out are available
        if (eventProps.clock_out) {
            var clockIn = new Date(eventProps.date + ' ' + eventProps.clock_in);
            var clockOut = new Date(eventProps.date + ' ' + eventProps.clock_out);
            var duration = clockOut - clockIn;
            var hours = Math.floor(duration / (1000 * 60 * 60));
            var minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
            
            content += '<div class="row mt-3">';
            content += '<div class="col-12">';
            content += '<div class="alert alert-info">';
            content += '<i data-feather="clock" class="icon-16 mr-2"></i>';
            content += '<strong><?php echo app_lang("duration"); ?>:</strong> ' + hours + 'h ' + minutes + 'm';
            content += '</div>';
            content += '</div>';
            content += '</div>';
        }
        
        content += '</div>';
        content += '</div>';
        content += '</div>';
        content += '</div>';
        content += '</div>';
        
        $('#employee-attendance-content').html(content);
        $('#employee-attendance-modal').modal('show');
        
        // Re-initialize feather icons
        setTimeout(function() {
            feather.replace();
        }, 100);
    }
    
    // Function to show leave details
    function showLeaveDetails(eventProps) {
        var content = '<div class="leave-details">';
        content += '<div class="row">';
        content += '<div class="col-12">';
        content += '<div class="card">';
        content += '<div class="card-header text-white" style="background-color: ' + eventProps.backgroundColor + ';">';
        content += '<h6 class="mb-0 d-flex align-items-center">';
        content += '<img src="' + eventProps.avatar_url + '" alt="' + eventProps.user_name + '" class="avatar avatar-sm me-2" style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid white;">';
        content += eventProps.user_name + ' - <?php echo app_lang("on_leave"); ?>';
        content += '</h6>';
        content += '</div>';
        content += '<div class="card-body">';
        content += '<div class="row mb-3">';
        content += '<div class="col-12 text-center">';
        content += '<img src="' + eventProps.avatar_url + '" alt="' + eventProps.user_name + '" class="avatar avatar-lg mb-2" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid ' + eventProps.backgroundColor + ';">';
        content += '<h5 class="mb-0">' + eventProps.user_name + '</h5>';
        content += '<small class="text-muted"><?php echo app_lang("employee_id"); ?>: ' + eventProps.user_id + '</small>';
        content += '</div>';
        content += '</div>';
        content += '<div class="row">';
        content += '<div class="col-md-6">';
        content += '<div class="info-item mb-3">';
        content += '<label class="form-label text-muted"><?php echo app_lang("leave_type"); ?>:</label>';
        content += '<div class="fw-bold">' + eventProps.leave_type + '</div>';
        content += '</div>';
        content += '<div class="info-item mb-3">';
        content += '<label class="form-label text-muted"><?php echo app_lang("start_date"); ?>:</label>';
        content += '<div class="fw-bold text-warning">' + eventProps.start_date + '</div>';
        content += '</div>';
        content += '</div>';
        content += '<div class="col-md-6">';
        content += '<div class="info-item mb-3">';
        content += '<label class="form-label text-muted"><?php echo app_lang("end_date"); ?>:</label>';
        content += '<div class="fw-bold text-warning">' + eventProps.end_date + '</div>';
        content += '</div>';
        content += '<div class="info-item mb-3">';
        content += '<label class="form-label text-muted"><?php echo app_lang("status"); ?>:</label>';
        content += '<div class="fw-bold text-success"><?php echo app_lang("approved"); ?></div>';
        content += '</div>';
        content += '</div>';
        content += '</div>';
        
        // Calculate leave duration
        var startDate = new Date(eventProps.start_date);
        var endDate = new Date(eventProps.end_date);
        var timeDiff = endDate.getTime() - startDate.getTime();
        var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 to include both start and end dates
        
        content += '<div class="row mt-3">';
        content += '<div class="col-12">';
        content += '<div class="alert alert-warning">';
        content += '<i data-feather="calendar" class="icon-16 mr-2"></i>';
        content += '<strong><?php echo app_lang("duration"); ?>:</strong> ' + daysDiff + ' <?php echo app_lang("days"); ?>';
        content += '</div>';
        content += '</div>';
        content += '</div>';
        
        // Show reason if available
        if (eventProps.reason && eventProps.reason.trim() !== '') {
            content += '<div class="row">';
            content += '<div class="col-12">';
            content += '<div class="info-item">';
            content += '<label class="form-label text-muted"><?php echo app_lang("reason"); ?>:</label>';
            content += '<div class="fw-bold">' + eventProps.reason + '</div>';
            content += '</div>';
            content += '</div>';
            content += '</div>';
        }
        
        content += '</div>';
        content += '</div>';
        content += '</div>';
        content += '</div>';
        content += '</div>';
        
        $('#leave-details-content').html(content);
        $('#leave-details-modal').modal('show');
        
        // Re-initialize feather icons
        setTimeout(function() {
            feather.replace();
        }, 100);
    }
    
    // Load calendar initially
    loadAttendanceCalendar();
    
    // Handle user selection changes
    $("#calendar-users-filter").on('change', function() {
        selectedUserIds = $(this).val() || [];
        if (window.attendanceCalendar) {
            window.attendanceCalendar.refetchEvents();
        }
    });
    
    // Handle view mode changes
    $("#calendar-view-mode").on('change', function() {
        viewMode = $(this).val();
        if (window.attendanceCalendar) {
            window.attendanceCalendar.refetchEvents();
        }
    });
    
    // Handle attendance toggle
    $("#show-attendance").on('change', function() {
        showAttendance = $(this).is(':checked');
        if (window.attendanceCalendar) {
            window.attendanceCalendar.refetchEvents();
        }
    });
    
    // Handle leaves toggle
    $("#show-leaves").on('change', function() {
        showLeaves = $(this).is(':checked');
        if (window.attendanceCalendar) {
            window.attendanceCalendar.refetchEvents();
        }
    });
});
</script>

