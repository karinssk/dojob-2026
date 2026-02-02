<?php echo form_open(get_uri("line_bot_expenses/save_title_keyword"), array("id" => "title-keyword-form", "class" => "general-form", "role" => "form")); ?>
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
                    "placeholder" => "Exact match keyword",
                    "data-rule-required" => true,
                    "data-msg-required" => "Keyword is required"
                ));
                ?>
                <small class="form-text text-muted">This keyword will be matched exactly (===)</small>
                <small id="title-keyword-duplicate-msg" class="form-text text-danger d-none">Keyword already exists.</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="title" class="col-md-3"><?php echo app_lang('vendor_title'); ?> <span class="text-danger">*</span></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "title",
                    "name" => "title",
                    "value" => $model_info->title,
                    "class" => "form-control",
                    "placeholder" => "Full vendor/company name",
                    "data-rule-required" => true,
                    "data-msg-required" => "Title is required"
                ));
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
        var $form = $("#title-keyword-form");
        var $msg = $("#title-keyword-duplicate-msg");
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
                url: "<?php echo get_uri('line_bot_expenses/check_title_keyword_duplicate'); ?>",
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

        $("#title-keyword-form").appForm({
            onSubmit: function () {
                console.log("[Title Keywords] Submit started", {
                    keyword: $("#keyword").val(),
                    title: $("#title").val(),
                    sort: $("#sort").val()
                });
            },
            onAjaxSuccess: function (result) {
                console.log("[Title Keywords] Ajax success response", result);
            },
            onSuccess: function (result) {
                console.log("[Title Keywords] Save success", result);
                if (typeof window.reloadTitleKeywordsTable === "function") {
                    window.reloadTitleKeywordsTable();
                }
                appAlert.success(result.message, {duration: 5000});
            },
            onError: function (result) {
                console.log("[Title Keywords] Save error", result);
                return true;
            }
        });
    });
</script>
