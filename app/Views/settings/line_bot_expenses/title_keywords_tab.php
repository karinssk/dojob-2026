<div class="card-body">
    <div class="mb-3">
        <button class="btn btn-primary btn-sm" id="add-title-keyword-btn">
            <i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('add_title_keyword'); ?>
        </button>
        <p class="text-muted mt-2"><small><?php echo app_lang('title_keyword_help'); ?></small></p>
    </div>

    <table id="title-keywords-table" class="display" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th><?php echo app_lang('keyword'); ?></th>
                <th><?php echo app_lang('vendor_title'); ?></th>
                <th>Sort</th>
                <th class="text-center" style="width:100px;"><?php echo app_lang('action'); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        var titleKeywordsTable = $("#title-keywords-table").DataTable({
            ajax: {
                url: "<?php echo get_uri('line_bot_expenses/title_keywords_list_data'); ?>",
                type: "POST"
            },
            columns: [
                {data: 0},
                {data: 1},
                {data: 2},
                {data: 3}
            ],
            order: [[2, 'asc']],
            responsive: true,
            language: {emptyTable: "No keywords configured"}
        });

        // Add keyword
        $("#add-title-keyword-btn").click(function () {
            console.log("[Title Keywords] Add button clicked");
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/title_keyword_modal_form'); ?>",
                type: "POST",
                data: {id: 0},
                success: function (response) {
                    console.log("[Title Keywords] Modal form loaded (add)", response);
                    appLoader.hide();
                    $("#ajaxModalContent").html(response);
                    $("#ajaxModalTitle").html("<?php echo app_lang('add_title_keyword'); ?>");
                    $("#ajaxModal").modal("show");
                },
                error: function (xhr) {
                    console.log("[Title Keywords] Modal form load failed (add)", xhr.status, xhr.responseText);
                    appLoader.hide();
                }
            });
        });

        // Edit keyword
        $(document).on("click", ".edit-title-keyword", function () {
            var id = $(this).data("id");
            console.log("[Title Keywords] Edit button clicked", id);
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/title_keyword_modal_form'); ?>",
                type: "POST",
                data: {id: id},
                success: function (response) {
                    console.log("[Title Keywords] Modal form loaded (edit)", id, response);
                    appLoader.hide();
                    $("#ajaxModalContent").html(response);
                    $("#ajaxModalTitle").html("<?php echo app_lang('edit_title_keyword'); ?>");
                    $("#ajaxModal").modal("show");
                },
                error: function (xhr) {
                    console.log("[Title Keywords] Modal form load failed (edit)", id, xhr.status, xhr.responseText);
                    appLoader.hide();
                }
            });
        });

        // Delete keyword
        $(document).on("click", ".delete-title-keyword", function () {
            var id = $(this).data("id");
            if (confirm("<?php echo app_lang('are_you_sure'); ?>")) {
                $.ajax({
                    url: "<?php echo get_uri('line_bot_expenses/delete_title_keyword'); ?>",
                    type: "POST",
                    data: {id: id},
                    dataType: "json",
                    success: function (result) {
                        if (result.success) {
                            titleKeywordsTable.ajax.reload();
                            appAlert.success(result.message, {duration: 5000});
                        }
                    }
                });
            }
        });

        // Reload table after save
        window.reloadTitleKeywordsTable = function () {
            titleKeywordsTable.ajax.reload();
        };
    });
</script>
