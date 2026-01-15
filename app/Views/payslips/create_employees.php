<div class="card">
    <div class="page-title clearfix">
        <h4>Create New Employee</h4>
        <div class="title-button-group">
            <a href="<?= get_uri('payslips/listEmployees') ?>" class="btn btn-default">
                <i data-feather="users" class="icon-16"></i> View All Employees
            </a>
            <a href="<?= get_uri('payslips') ?>" class="btn btn-default">
                <i data-feather="arrow-left" class="icon-16"></i> Back to Payslips
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($available_users)): ?>
            <div class="alert alert-info">
                <h5>No Available Users</h5>
                <p>All staff users already have employee records, or there are no staff users in the system.</p>
                <p>To create a new employee, you need to:</p>
                <ol>
                    <li>First create a new user account in the Users section</li>
                    <li>Set the user type to "Staff"</li>
                    <li>Then return here to create their employee record</li>
                </ol>
            </div>
        <?php else: ?>
            <form action="<?= base_url('payslips/storeEmployee') ?>" method="post" id="employee-form">
                <?= csrf_field() ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user_id">Select User *</label>
                            <select name="user_id" id="user_id" class="form-control" required onchange="populateEmployeeData()">
                                <option value="">-- Select User --</option>
                                <?php foreach ($available_users as $user): ?>
                                    <option value="<?= $user['id'] ?>" 
                                            data-name="<?= $user['first_name'] . ' ' . $user['last_name'] ?>"
                                            data-position="<?= $user['job_title'] ?>"
                                            <?= old('user_id') == $user['id'] ? 'selected' : '' ?>>
                                        <?= $user['first_name'] . ' ' . $user['last_name'] ?> (<?= $user['email'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Select a user to create an employee record for</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Employee Name *</label>
                            <input type="text" name="name" id="name" class="form-control" 
                                   value="<?= old('name') ?>" required>
                            <small class="form-text text-muted">Full name as it should appear on payslips</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="position">Position *</label>
                            <input type="text" name="position" id="position" class="form-control" 
                                   value="<?= old('position') ?>" required>
                            <small class="form-text text-muted">Job title or position</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_name">Company Name *</label>
                            <input type="text" name="company_name" id="company_name" class="form-control" 
                                   value="<?= old('company_name', 'Rubyshop') ?>" required>
                            <small class="form-text text-muted">Company or organization name</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_account">Bank Account</label>
                            <input type="text" name="bank_account" id="bank_account" class="form-control" 
                                   value="<?= old('bank_account') ?>" placeholder="e.g., 054-881-61-49">
                            <small class="form-text text-muted">Bank account number (optional)</small>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="user-plus" class="icon-16"></i> Create Employee
                            </button>
                            <a href="<?= get_uri('payslips/listEmployees') ?>" class="btn btn-default">
                                <i data-feather="list" class="icon-16"></i> View All Employees
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    function populateEmployeeData() {
        const userSelect = document.getElementById('user_id');
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        
        if (selectedOption.value) {
            // Auto-populate name field
            const nameField = document.getElementById('name');
            if (!nameField.value) {
                nameField.value = selectedOption.getAttribute('data-name');
            }
            
            // Auto-populate position field
            const positionField = document.getElementById('position');
            if (!positionField.value && selectedOption.getAttribute('data-position')) {
                positionField.value = selectedOption.getAttribute('data-position');
            }
        }
    }

    $(document).ready(function () {
        feather.replace();
    });
</script>