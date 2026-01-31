<div class="card-body">
    <div class="mb-3">
        <button class="btn btn-primary btn-sm" id="add-category-keyword-btn">
            <i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('add_category_keyword'); ?>
        </button>
        <p class="text-muted mt-2"><small><?php echo app_lang('category_keyword_help'); ?></small></p>
    </div>

    <table id="category-keywords-table" class="display" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th><?php echo app_lang('keyword'); ?></th>
                <th><?php echo app_lang('category_id'); ?></th>
                <th><?php echo app_lang('category_name'); ?></th>
                <th>Sort</th>
                <th class="text-center" style="width:100px;"><?php echo app_lang('action'); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        var categoryKeywordsTable = $("#category-keywords-table").DataTable({
            ajax: {
                url: "<?php echo get_uri('line_bot_expenses/category_keywords_list_data'); ?>",
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
            language: {emptyTable: "No category keywords configured"}
        });

        // Add keyword
        $("#add-category-keyword-btn").click(function () {
            console.log("[Category Keywords] Add button clicked");
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/category_keyword_modal_form'); ?>",
                type: "POST",
                data: {id: 0},
                success: function (response) {
                    console.log("[Category Keywords] Modal form loaded (add)", response);
                    appLoader.hide();
                    $("#ajaxModalContent").html(response);
                    $("#ajaxModalTitle").html("<?php echo app_lang('add_category_keyword'); ?>");
                    $("#ajaxModal").modal("show");
                },
                error: function (xhr) {
                    console.log("[Category Keywords] Modal form load failed (add)", xhr.status, xhr.responseText);
                    appLoader.hide();
                }
            });
        });

        // Edit keyword
        $(document).on("click", ".edit-category-keyword", function () {
            var id = $(this).data("id");
            console.log("[Category Keywords] Edit button clicked", id);
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/category_keyword_modal_form'); ?>",
                type: "POST",
                data: {id: id},
                success: function (response) {
                    console.log("[Category Keywords] Modal form loaded (edit)", id, response);
                    appLoader.hide();
                    $("#ajaxModalContent").html(response);
                    $("#ajaxModalTitle").html("<?php echo app_lang('edit_category_keyword'); ?>");
                    $("#ajaxModal").modal("show");
                },
                error: function (xhr) {
                    console.log("[Category Keywords] Modal form load failed (edit)", id, xhr.status, xhr.responseText);
                    appLoader.hide();
                }
            });
        });

        // Delete keyword
        $(document).on("click", ".delete-category-keyword", function () {
            var id = $(this).data("id");
            if (confirm("<?php echo app_lang('are_you_sure'); ?>")) {
                $.ajax({
                    url: "<?php echo get_uri('line_bot_expenses/delete_category_keyword'); ?>",
                    type: "POST",
                    data: {id: id},
                    dataType: "json",
                    success: function (result) {
                        if (result.success) {
                            categoryKeywordsTable.ajax.reload();
                            appAlert.success(result.message, {duration: 5000});
                        }
                    }
                });
            }
        });

        // Reload table after save
        window.reloadCategoryKeywordsTable = function () {
            categoryKeywordsTable.ajax.reload();
        };
    });
</script>
