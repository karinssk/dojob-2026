<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="page-title clearfix">
                    <h4><?php echo app_lang('project_management'); ?></h4>
                    <div class="title-button-group">
                        <a href="<?php echo get_uri("projects/form"); ?>" class="btn btn-default">
                            <i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('add_project'); ?>
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="project-table" class="display" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th><?php echo app_lang('id'); ?></th>
                                <th><?php echo app_lang('title'); ?></th>
                                <th><?php echo app_lang('client'); ?></th>
                                <th><?php echo app_lang('status'); ?></th>
                                <th><?php echo app_lang('start_date'); ?></th>
                                <th><?php echo app_lang('deadline'); ?></th>
                                <th class="w100"><?php echo app_lang('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo $project->id; ?></td>
                                <td>
                                    <a href="<?php echo get_uri("projects/task_list/" . $project->id); ?>">
                                        <?php echo $project->title; ?>
                                    </a>
                                </td>
                                <td><?php echo $project->client_id; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $project->status == 'open' ? 'success' : 'secondary'; ?>">
                                        <?php echo app_lang($project->status); ?>
                                    </span>
                                </td>
                                <td><?php echo $project->start_date ? format_to_date($project->start_date, false) : '-'; ?></td>
                                <td><?php echo $project->deadline ? format_to_date($project->deadline, false) : '-'; ?></td>
                                <td>
                                    <a href="<?php echo get_uri("projects/task_list/" . $project->id); ?>" class="btn btn-sm btn-outline-primary">
                                        <i data-feather="list" class="icon-16"></i> <?php echo app_lang('tasks'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#project-table").appTable({
            source: '<?php echo_uri("projects/list_data") ?>',
            order: [[0, "desc"]],
            columns: [
                {title: "<?php echo app_lang("id") ?>", "class": "text-center w50"},
                {title: "<?php echo app_lang("title") ?>"},
                {title: "<?php echo app_lang("client") ?>"},
                {title: "<?php echo app_lang("status") ?>", "class": "text-center"},
                {title: "<?php echo app_lang("start_date") ?>", "class": "text-center", "iDataSort": 4},
                {title: "<?php echo app_lang("deadline") ?>", "class": "text-center", "iDataSort": 5},
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
            ]
        });
    });
</script>