<?php echo form_open(get_uri("expenses/export"), array("id" => "expenses-export-form", "class" => "general-form", "role" => "form", "target" => "_blank")); ?>

<div class="modal-body clearfix">
    <div class="form-group">
        <div class="row">
            <label for="start_date" class="col-md-3"><?php echo app_lang('start_date'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "start_date",
                    "name" => "start_date",
                    "value" => date("Y-m-01"),
                    "class" => "form-control",
                    "placeholder" => app_lang('start_date'),
                    "data-rule-required" => true,
                    "data-msg-required" => app_lang('field_required')
                ));
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="end_date" class="col-md-3"><?php echo app_lang('end_date'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "end_date",
                    "name" => "end_date",
                    "value" => date("Y-m-t"),
                    "class" => "form-control",
                    "placeholder" => app_lang('end_date'),
                    "data-rule-required" => true,
                    "data-rule-greaterThanOrEqual" => "#start_date",
                    "data-msg-required" => app_lang('field_required'),
                    "data-msg-greaterThanOrEqual" => app_lang("end_date_must_be_equal_or_greater_than_start_date")
                ));
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label class="col-md-3">VAT</label>
            <div class="col-md-9">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="vat_mode" id="vat_mode_with" value="with_tax" checked>
                    <label class="form-check-label" for="vat_mode_with">With Tax</label>
                </div>
                <div class="form-check mt5">
                    <input class="form-check-input" type="radio" name="vat_mode" id="vat_mode_without" value="no_tax">
                    <label class="form-check-label" for="vat_mode_without">No Tax</label>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="export_format" class="col-md-3">Format</label>
            <div class="col-md-9">
                <?php
                echo form_dropdown("export_format", array(
                    "csv" => "CSV",
                    "xlsx" => "XLSX"
                ), "csv", "class='select2' id='export_format'");
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="download" class="icon-16"></span> Export</button>
</div>
<?php echo form_close(); ?>

<script>
    $(document).ready(function () {
        setDatePicker("#start_date, #end_date");
        if (!$("#export_format").hasClass("select2-hidden-accessible")) {
            $("#export_format").select2({dropdownParent: $("#ajaxModal")});
        }
    });
</script>
