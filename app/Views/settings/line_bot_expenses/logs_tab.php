<div class="card-body">
    <div class="mb-3">
        <h4 class="mb-1"><?php echo app_lang('line_expenses_logs'); ?></h4>
        <p class="text-muted mb-0"><small><?php echo app_lang('line_expenses_logs_help'); ?></small></p>
    </div>

    <table id="line-expenses-logs-table" class="display" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th><?php echo app_lang('created_at'); ?></th>
                <th><?php echo app_lang('expense_date'); ?></th>
                <th><?php echo app_lang('title'); ?></th>
                <th><?php echo app_lang('amount'); ?></th>
                <th><?php echo app_lang('category'); ?></th>
                <th><?php echo app_lang('project_name'); ?></th>
                <th><?php echo app_lang('client_name'); ?></th>
                <th><?php echo app_lang('user'); ?></th>
                <th><?php echo app_lang('user_id'); ?></th>
                <th><?php echo app_lang('mapping_status'); ?></th>
                <th><?php echo app_lang('expense_id'); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        $("#line-expenses-logs-table").DataTable({
            ajax: {
                url: "<?php echo get_uri('line_bot_expenses/expense_logs_list_data'); ?>",
                type: "POST"
            },
            columns: [
                {data: 0},
                {data: 1},
                {data: 2},
                {data: 3},
                {data: 4},
                {data: 5},
                {data: 6},
                {data: 7},
                {data: 8},
                {data: 9},
                {data: 10}
            ],
            order: [[0, "desc"]],
            responsive: true,
            language: {emptyTable: "<?php echo app_lang('no_data_found'); ?>"}
        });
    });
</script>
