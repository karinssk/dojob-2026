<div class="card-body">
    <div class="mb-3">
        <button class="btn btn-primary btn-sm" id="add-project-keyword-btn">
            <i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('add_project_keyword'); ?>
        </button>
    </div>

    <table id="project-keywords-table" class="display" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th><?php echo app_lang('keyword'); ?></th>
                <th><?php echo app_lang('client_name'); ?></th>
                <th><?php echo app_lang('project_name'); ?></th>
                <th>Sort</th>
                <th class="text-center" style="width:100px;"><?php echo app_lang('action'); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        var projectKeywordsTable = $("#project-keywords-table").DataTable({
            ajax: {
                url: "<?php echo get_uri('line_bot_expenses/project_keywords_list_data'); ?>",
                type: "POST"
            },
            columns: [
                {data: 0},
                {data: 1},
                {data: 2},
                {data: 3},
                {data: 4}
            ],
            order: [[3, 'asc']],
            responsive: true,
            language: {emptyTable: "No project keywords configured"}
        });

        $("#add-project-keyword-btn").click(function () {
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/project_keyword_modal_form'); ?>",
                type: "POST",
                data: {id: 0},
                success: function (response) {
                    appLoader.hide();
                    $("#ajaxModalContent").html(response);
                    $("#ajaxModalTitle").html("<?php echo app_lang('add_project_keyword'); ?>");
                    $("#ajaxModal").modal("show");
                }
            });
        });

        $(document).on("click", ".edit-project-keyword", function () {
            var id = $(this).data("id");
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/project_keyword_modal_form'); ?>",
                type: "POST",
                data: {id: id},
                success: function (response) {
                    appLoader.hide();
                    $("#ajaxModalContent").html(response);
                    $("#ajaxModalTitle").html("<?php echo app_lang('edit_project_keyword'); ?>");
                    $("#ajaxModal").modal("show");
                }
            });
        });

        $(document).on("click", ".delete-project-keyword", function () {
            var id = $(this).data("id");
            console.log("[Project Keywords] Delete clicked", id);
            if (confirm("<?php echo app_lang('are_you_sure'); ?>")) {
                $.ajax({
                    url: "<?php echo get_uri('line_bot_expenses/delete_project_keyword'); ?>",
                    type: "POST",
                    data: {id: id},
                    dataType: "json",
                    success: function (result) {
                        console.log("[Project Keywords] Delete response", result);
                        if (result.success) {
                            projectKeywordsTable.ajax.reload();
                            appAlert.success(result.message, {duration: 5000});
                        }
                    },
                    error: function (xhr) {
                        console.log("[Project Keywords] Delete failed", id, xhr.status, xhr.responseText);
                    }
                });
            }
        });

        window.reloadProjectKeywordsTable = function () {
            projectKeywordsTable.ajax.reload();
        };
    });
</script>
