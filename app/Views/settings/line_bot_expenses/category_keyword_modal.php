<?php echo form_open(get_uri("line_bot_expenses/save_category_keyword"), array("id" => "category-keyword-form", "class" => "general-form", "role" => "form")); ?>
<input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

<div class="modal-body clearfix">
    <div class="form-group">
        <div class="row">
            <label for="keyword" class="col-md-3"><?php echo app_lang('keyword'); ?> <span class="text-danger">*</span></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "keyword",
                    "name" => "keyword",
                    "value" => $model_info->keyword,
                    "class" => "form-control",
                    "placeholder" => "e.g. 24, 13, 06",
                    "data-rule-required" => true,
                    "data-msg-required" => "Keyword is required"
                ));
                ?>
                <small class="form-text text-muted">This keyword will be matched exactly (===)</small>
                <small id="category-keyword-duplicate-msg" class="form-text text-danger d-none">Keyword already exists.</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="category_id" class="col-md-3"><?php echo app_lang('category'); ?> <span class="text-danger">*</span></label>
            <div class="col-md-9">
                <?php
                echo form_dropdown("category_id", $categories_dropdown, $model_info->category_id, "class='select2' id='category_id' data-rule-required='true' data-msg-required='Category is required'");
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="sort" class="col-md-3">Sort</label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "sort",
                    "name" => "sort",
                    "value" => $model_info->sort ?: 0,
                    "class" => "form-control",
                    "type" => "number"
                ));
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script>
    $(document).ready(function () {
        var $keyword = $("#keyword");
        var $form = $("#category-keyword-form");
        var $msg = $("#category-keyword-duplicate-msg");
        var $submit = $form.find("button[type=submit]");
        var keywordTimer = null;

        function checkDuplicate() {
            var value = $.trim($keyword.val());
            if (!value) {
                $msg.addClass("d-none");
                $submit.prop("disabled", false);
                return;
            }
            $.ajax({
                url: "<?php echo get_uri('line_bot_expenses/check_category_keyword_duplicate'); ?>",
                type: "POST",
                dataType: "json",
                data: {keyword: value, id: $form.find("input[name=id]").val()},
                success: function (result) {
                    var exists = result && result.exists;
                    $msg.toggleClass("d-none", !exists);
                    $submit.prop("disabled", !!exists);
                }
            });
        }

        $keyword.on("input", function () {
            if (keywordTimer) {
                clearTimeout(keywordTimer);
            }
            keywordTimer = setTimeout(checkDuplicate, 300);
        });

        checkDuplicate();

        $("#category-keyword-form").appForm({
            onSuccess: function (result) {
                $("#ajaxModal").modal("hide");
                if (typeof window.reloadCategoryKeywordsTable === "function") {
                    window.reloadCategoryKeywordsTable();
                }
                appAlert.success(result.message, {duration: 5000});
            }
        });

        var $category = $("#category_id");
        if (!$category.hasClass("select2-hidden-accessible")) {
            $category.select2({dropdownParent: $("#ajaxModal")});
        }
    });
</script>
