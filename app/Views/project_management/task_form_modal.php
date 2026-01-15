<!-- Task Form Modal -->
<div class="modal fade" id="task-form-modal" tabindex="-1" role="dialog" aria-labelledby="task-form-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="task-form-modal-label"><?php echo app_lang('add_task'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo form_open(get_uri("tasks/save"), array("id" => "task-form", "class" => "general-form", "role" => "form")); ?>
                
                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
                <input type="hidden" name="add_type" value="multiple" />
                
                <div class="form-group">
                    <div class="row">
                        <label for="title" class="col-md-3"><?php echo app_lang('title'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "title",
                                "name" => "title",
                                "value" => "",
                                "class" => "form-control",
                                "placeholder" => app_lang('title'),
                                "autofocus" => true,
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="row">
                        <label for="parent_task_id" class="col-md-3"><?php echo app_lang('parent_task'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_dropdown("parent_task_id", array("0" => "- " . app_lang('main_task') . " -"), "", "class='select2 form-control' id='parent_task_id'");
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="row">
                        <label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_textarea(array(
                                "id" => "description",
                                "name" => "description",
                                "value" => "",
                                "class" => "form-control",
                                "placeholder" => app_lang('description'),
                                "rows" => 3
                            ));
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="row">
                        <label for="assigned_to" class="col-md-3"><?php echo app_lang('assign_to'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "assigned_to",
                                "name" => "assigned_to",
                                "value" => "",
                                "class" => "form-control select2",
                                "placeholder" => app_lang('assign_to')
                            ));
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="row">
                        <label for="deadline" class="col-md-3"><?php echo app_lang('deadline'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "deadline",
                                "name" => "deadline",
                                "value" => "",
                                "class" => "form-control",
                                "placeholder" => app_lang('deadline'),
                                "autocomplete" => "off"
                            ));
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="row">
                        <label for="task_images" class="col-md-3"><?php echo app_lang('task_images'); ?></label>
                        <div class="col-md-9">
                            <input type="file" id="task_images" name="task_images[]" class="form-control" multiple accept="image/*">
                            <small class="form-text text-muted"><?php echo app_lang('upload_task_preview_images'); ?></small>
                        </div>
                    </div>
                </div>
                
                <?php echo form_close(); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
                <button type="submit" form="task-form" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        
        // Initialize date picker
        setDatePicker("#deadline");
        
        // Initialize select2
        $("#assigned_to").select2({
            data: <?php echo json_encode($team_members_dropdown); ?>
        });
        
        // Load parent tasks for hierarchy
        $("#parent_task_id").select2({
            data: <?php echo json_encode($parent_tasks_dropdown); ?>
        });
        
        // Handle form submission
        $("#task-form").appForm({
            onSuccess: function (result) {
                if (result.success) {
                    $("#task-form-modal").modal('hide');
                    location.reload(); // Reload to show new task
                }
            }
        });
    });
</script>