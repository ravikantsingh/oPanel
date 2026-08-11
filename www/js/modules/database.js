// /opt/panel/www/js/modules/database.js

// =================================================================
// 1. GLOBAL FUNCTIONS (Attached to Window)
// =================================================================
window.fetchUsers = function() {
    $.ajax({
        url: '/ajax/get_users.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let tbody = $('#dynamicUsersTable');
                tbody.empty();
                if(response.users.length === 0) {
                    tbody.html('<tr><td colspan="4" class="text-center text-muted py-4 border-0">No users found.</td></tr>');
                    return;
                }
                response.users.forEach(function(u) {
                    let badges = '';
                    if(u.has_ssh == 1) badges += '<span class="badge bg-light text-dark border-0 shadow-sm rounded-pill me-2 px-3" title="SSH Key Generated"><i class="bi bi-key text-primary me-1"></i> SSH</span>';
                    if(u.has_webhook == 1) badges += '<span class="badge bg-success bg-opacity-10 text-success border-0 shadow-sm rounded-pill px-3" title="Webhook Active"><i class="bi bi-lightning-charge-fill me-1"></i> Webhook</span>';
                    if(badges === '') badges = '<span class="text-muted small">None</span>';

                    let row = `<tr>
                                <td class="fw-bold text-dark">${u.username}</td>
                                <td class="small text-muted">${u.email || 'No email'}</td>
                                <td>${badges}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light shadow-sm text-primary change-linux-pass me-1" data-user="${u.username}" title="Change OS Password"><i class="bi bi-key"></i></button>
                                    <button class="btn btn-sm btn-light shadow-sm text-danger delete-user" data-user="${u.username}" title="Delete User"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>`;
                    tbody.append(row);
                });
                
                let userDropdowns = $('.user-dropdown');
                userDropdowns.empty().append('<option value="">Select a User...</option>');
                response.users.forEach(function(u) {
                    userDropdowns.append('<option value="' + u.username + '">' + u.username + '</option>');
                });
            }
        }
    });
};

window.fetchDatabases = function() {
    $.ajax({
        url: '/ajax/get_databases.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let tbody = $('#dynamicDbTable');
                tbody.empty();
                if(response.databases.length === 0) {
                    tbody.html('<tr><td colspan="4" class="text-center text-muted py-4 border-0">No databases provisioned.</td></tr>');
                    $('.db-dropdown').empty().append('<option value="">No databases available</option>');
                    return;
                }
                response.databases.forEach(function(db) {
                    let row = `<tr>
                                <td class="fw-bold text-primary">${db.db_name}</td>
                                <td><code>${db.db_user}</code></td>
                                <td><i class="bi bi-person text-muted me-1"></i> ${db.owner_username}</td>
                                
                                <td class="text-end text-nowrap">
                                    <!-- Original Core Action -->
                                    <button class="btn btn-sm btn-success shadow-sm open-pma-sso" data-db="${db.db_name}" title="Open in phpMyAdmin"><i class="bi bi-database-fill-gear"></i></button>
                                    
                                    <!-- New Enterprise Suite Actions -->
                                    <button class="btn btn-sm btn-light shadow-sm text-primary export-db-btn ms-1" data-db="${db.db_name}" title="Export SQL Dump"><i class="bi bi-cloud-arrow-down"></i></button>
                                    <button class="btn btn-sm btn-light shadow-sm text-primary open-import-db-modal ms-1" data-db="${db.db_name}" title="Import SQL Dump"><i class="bi bi-file-earmark-arrow-up"></i></button>
                                    <button class="btn btn-sm btn-light shadow-sm text-info open-copy-db-modal ms-1" data-db="${db.db_name}" data-owner="${db.owner_username}" title="Clone Database"><i class="bi bi-files"></i></button>
                                    <button class="btn btn-sm btn-light shadow-sm text-dark open-repair-db-modal ms-1" data-db="${db.db_name}" title="Diagnostics & Repair"><i class="bi bi-tools"></i></button>
                                    <button class="btn btn-sm btn-light shadow-sm text-warning open-transfer-db-modal ms-1" data-db="${db.db_name}" data-owner="${db.owner_username}" title="Transfer Owner"><i class="bi bi-person-gear"></i></button>
                                    
                                    <!-- Original Management Actions -->
                                    <button class="btn btn-sm btn-light shadow-sm text-dark change-db-pass ms-1" data-db="${db.db_name}" data-user="${db.db_user}" title="Change Password"><i class="bi bi-key"></i></button>
                                    <button class="btn btn-sm btn-light shadow-sm text-danger delete-db ms-1" data-db="${db.db_name}" title="Delete Database"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>`;
                    tbody.append(row);
                });
                
                let dbDropdowns = $('.db-dropdown');
                dbDropdowns.empty().append('<option value="">Select a Database...</option>');
                response.databases.forEach(function(db) {
                    dbDropdowns.append('<option value="' + db.db_name + '">' + db.db_name + '</option>');
                });
            }
        }
    });
};

window.fetchRedisStats = function() {
    $.getJSON('/ajax/redis_stats.php', function(data) {
        if (data.success) {
            $('#redisStatusBadge').removeClass('bg-secondary bg-danger').addClass('bg-success bg-opacity-10 text-success border-0 rounded-pill shadow-sm px-3').html('<i class="bi bi-check-circle-fill me-1"></i> Online');
            $('#redisClients').text(data.clients);
            $('#redisHitRate').text(data.hit_rate);
            $('#redisUptime').text(data.uptime_days);
            
            $('#redisMemText').text(data.used_memory_human + ' / 128M');
            $('#redisMemBar')
                .css('width', data.memory_percent + '%')
                .text(data.memory_percent + '%')
                .removeClass('bg-secondary bg-primary bg-success bg-warning bg-danger')
                .addClass('bg-' + data.memory_color);
        } else {
            $('#redisStatusBadge').removeClass('bg-secondary bg-success').addClass('bg-danger bg-opacity-10 text-danger border-0 rounded-pill shadow-sm px-3').html('<i class="bi bi-x-circle-fill me-1"></i> Offline');
            $('#redisMemBar').css('width', '0%').text('0%').removeClass().addClass('progress-bar bg-danger');
        }
    });
};

// =================================================================
// 2. EVENT LISTENERS
// =================================================================
$(document).ready(function() {

    // === USERS ===
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault(); 
        let btn = $('#submitUserBtn');
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Provisioning User...');
        $.ajax({
            url: '/ajax/create_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addUserModal').modal('hide');
                    $('#addUserForm')[0].reset();
                    window.showToast('success', 'User Created', 'System user has been successfully provisioned.');
                    window.fetchUsers();
                } else { 
                    window.showToast('error', 'Provisioning Failed', response.error); 
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() { 
                window.showToast('error', 'Server Error', 'A server error occurred.'); 
                btn.prop('disabled', false).html(originalText); 
            }
        });
    });

    $(document).on('click', '.delete-user', function() {
        let user = $(this).data('user');
        if(!confirm(`CRITICAL WARNING: Are you sure you want to permanently delete '${user}' and destroy their home directory? \n\nNOTE: You MUST delete their domains from the Web tab first! `)) return;
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/ajax/delete_user.php',
            type: 'POST',
            data: { username: user },
            success: function() { setTimeout(window.fetchUsers, 3000); }
        });
    });

    $('#generateUserPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#password').val(pass);
        navigator.clipboard.writeText(pass);
        let btn = $(this);
        let originalText = btn.html();
        btn.html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { btn.html(originalText); }, 2000);
    });

    // === DATABASES ===
    $('#dbOwner').on('change', function() {
        let val = $(this).val();
        $('#dbPrefixLabel').text(val ? val + '_' : 'prefix_');
    });

    $(document).on('change', '#dbAcl', function() {
        if($(this).val() === 'custom') { $('#dbCustomIp').removeClass('d-none').prop('required', true); } 
        else { $('#dbCustomIp').addClass('d-none').prop('required', false); }
    });

    $(document).on('change', '#dbRole', function() {
        if($(this).val() === 'custom') { $('#customPrivilegesGrid').removeClass('d-none'); } 
        else { $('#customPrivilegesGrid').addClass('d-none'); }
    });

    $(document).on('click', '#submitDbBtn', function(e) {
        e.preventDefault();
        let form = $('#addDbForm');
        let btn = $(this);
        let alertBox = $('#dbFormAlert');
        let originalText = btn.html();
        
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }

        if ($('#dbRole').val() === 'custom') {
            let privs = [];
            $('.db-priv-chk:checked').each(function() { privs.push($(this).val()); });
            if(privs.length === 0) { 
                window.showToast('warning', 'Validation Error', 'You must select at least one privilege for a custom role.'); 
                return; 
            }
            $('#customPrivString').val(privs.join(', '));
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Provisioning DB...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/create_db.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addDbModal').modal('hide');
                    form[0].reset();
                    $('#dbPrefixLabel').text('prefix_');
                    $('#dbCustomIp').addClass('d-none');
                    $('#customPrivilegesGrid').addClass('d-none');
                    window.showToast('success', 'Database Created', 'Instance provisioned successfully.');
                    setTimeout(window.fetchDatabases, 1500); 
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() { 
                alertBox.addClass('alert-danger').text('A server error occurred.').removeClass('d-none'); 
                btn.prop('disabled', false).html(originalText); 
            }
        });
    });

    $(document).on('click', '#generateDbPass', function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 20; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#dbPassInput').val(pass);
        navigator.clipboard.writeText(pass);
        let btn = $(this);
        let originalText = btn.html();
        btn.html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { btn.html(originalText); }, 2000);
    });

    $(document).on('click', '.change-db-pass', function() {
        let dbUser = $(this).data('user');
        $('#editDbUserHidden').val(dbUser);
        $('#editDbUserDisplay').val(dbUser);
        $('#editDbPassInput').val('');
        $('#changeDbPassModal').modal('show');
    });

    $(document).on('click', '#generateEditDbPass', function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 20; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#editDbPassInput').val(pass);
        navigator.clipboard.writeText(pass);
        let btn = $(this);
        let originalText = btn.html();
        btn.html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { btn.html(originalText); }, 2000);
    });

    $(document).on('click', '#submitEditDbPassBtn', function() {
        let btn = $(this);
        let form = $('#changeDbPassForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Rotating Key...');

        $.ajax({
            url: '/ajax/change_db_password.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#changeDbPassModal').modal('hide');
                    window.showToast('success', 'Password Rotated', "Don't forget to update your application config files.");
                } else { 
                    window.showToast('error', 'Update Failed', response.error); 
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save New Password');
            }
        });
    });

    $(document).on('click', '.delete-db', function() {
        let dbName = $(this).data('db');
        if(!confirm(`CRITICAL WARNING: Are you sure you want to permanently delete the database '${dbName}' and its user? All data will be destroyed!`)) return;
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/delete_db.php',
            type: 'POST',
            data: { db_name: dbName },
            dataType: 'json',
            success: function(response) {
                if(response.success) { 
                    window.showToast('success', 'Database Deleted', 'Instance removed successfully.');
                    setTimeout(window.fetchDatabases, 2500); 
                } 
                else { 
                    window.showToast('error', 'Delete Failed', response.error); 
                    btn.prop('disabled', false).html('<i class="bi bi-trash"></i>'); 
                }
            }
        });
    });

    // === PHPMYADMIN SSO TRIGGER ===
    $(document).on('click', '.open-pma-sso', function() {
        let dbName = $(this).data('db');
        let btn = $(this);
        let originalIcon = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/get_pma_sso.php',
            type: 'POST',
            data: { db: dbName },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html(originalIcon);
                if(response.success) {
                    window.open(response.url, '_blank');
                } else {
                    window.showToast('error', 'SSO Failed', response.error);
                }
            },
            error: function() {
                btn.prop('disabled', false).html(originalIcon);
                window.showToast('error', 'Network Error', 'Failed to connect to SSO Gateway.');
            }
        });
    });

    // === PHPMYADMIN LIMITS ===
    $('#submitPmaSettingsBtn').click(function() {
        let btn = $(this);
        let form = $('#pmaSettingsForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Applying...');
        $.ajax({
            url: '/ajax/update_server_limits.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#pmaSettingsModal').modal('hide');
                    window.showToast('success', 'Limits Applied', 'Nginx and PHP limits have been increased globally.');
                } else { 
                    window.showToast('error', 'Error Applying Limits', response.error); 
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Apply Globally');
            }
        });
    });

    // === REDIS CACHE ENGINE ===
    let redisInterval;
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#redis') {
            window.fetchRedisStats();
            redisInterval = setInterval(window.fetchRedisStats, 5000);
        } else {
            clearInterval(redisInterval);
        }
    });

    window.redisAction = function(actionType) {
        if (!confirm('Are you sure you want to ' + actionType + ' the Redis cache?')) return;
        $.post('/ajax/redis_action.php', { action: actionType, csrf_token: document.querySelector('meta[name="csrf-token"]').content }, function(res) {
            if(res.success) {
                window.showToast('success', 'Redis Action', res.message);
                window.fetchRedisStats();
            } else { 
                window.showToast('error', 'Action Failed', res.error); 
            }
        }, 'json');
    };

    $(document).on('click', '.enable-redis-btn', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        let btn = $(this);
        let originalIcon = btn.html();

        if(!confirm(`Are you sure you want to inject Redis caching into the WordPress installation at ${domain}?`)) return;
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/enable_wp_redis.php',
            type: 'POST',
            data: { domain: domain, username: user },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    window.showToast('success', 'Redis Injected', response.message);
                    $('#overview-tab').tab('show'); 
                } else { 
                    window.showToast('error', 'Injection Failed', response.error); 
                }
                btn.prop('disabled', false).html(originalIcon);
            },
            error: function() { 
                window.showToast('error', 'Network Error', 'Could not communicate with the server.'); 
                btn.prop('disabled', false).html(originalIcon); 
            }
        });
    });

    $(document).on('click', '#openDevGuideBtn', function() {
        let btn = $(this);
        let originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Loading Vault...');

        $.ajax({
            url: '/ajax/get_redis_credentials.php',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#devRedisPass').val(res.password);
                    let boilerplate = $('#devPhpBoilerplate').val();
                    boilerplate = boilerplate.replace('PASSWORD_WILL_LOAD_HERE', res.password);
                    $('#devPhpBoilerplate').val(boilerplate);
                    $('#devRedisModal').modal('show');
                } else { 
                    window.showToast('error', 'Fetch Error', "Could not load vault: " + res.error); 
                }
                btn.prop('disabled', false).html(originalHtml);
            },
            error: function() { 
                window.showToast('error', 'Network Error', 'Could not reach server.'); 
                btn.prop('disabled', false).html(originalHtml); 
            }
        });
    });

    $('#devRedisModal').on('hidden.bs.modal', function () {
        let boilerplate = $('#devPhpBoilerplate').val();
        let currentPass = $('#devRedisPass').val();
        if(currentPass) {
            boilerplate = boilerplate.replace(currentPass, 'PASSWORD_WILL_LOAD_HERE');
            $('#devPhpBoilerplate').val(boilerplate);
            $('#devRedisPass').val('');
        }
    });

    $(document).on('click', '.change-linux-pass', function() {
        let user = $(this).data('user');
        $('#linuxPassUser').val(user);
        $('#linuxPassTitle').text(user);
        $('#linuxPassForm')[0].reset();
        $('#linuxPassModal').modal('show');
    });

    $('#linuxPassForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#linuxPassBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        $.ajax({
            url: '/ajax/change_user_password.php', 
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#linuxPassModal').modal('hide');
                    window.showToast('success', 'Task Queued', 'Password update queued to system processor.');
                    $('#overview-tab').tab('show'); 
                } else {
                    window.showToast('error', 'Update Failed', res.error);
                }
                btn.prop('disabled', false).html('Update Password');
            }
        });
    });
    // =================================================================
    // ENTERPRISE DATABASE SUITE CONTROLLERS
    // =================================================================

    // 1. Direct Export Dump Trigger (Routed to Backup Vault)
    $(document).on('click', '.export-db-btn', function(e) {
        e.preventDefault();
        let dbName = $(this).data('db');
        window.showToast('info', 'Export Queued', `Generating archive for ${dbName}...`);
        
        $.ajax({
            url: '/ajax/create_backup.php', // Routed to existing backup controller
            type: 'POST',
            data: { action: 'backup_db', target: dbName }, // Matched to backup parameters
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    window.showToast('success', 'Backup Running', 'File will appear in the Backups tab shortly.');
                } else {
                    window.showToast('error', 'Export Failed', res.error);
                }
            }
        });
    });

    // 2. Open Import Modal
    $(document).on('click', '.open-import-db-modal', function(e) {
        e.preventDefault();
        let dbName = $(this).data('db');
        $('#importTargetDb').val(dbName);
        $('#importDbDisplay').val(dbName);
        $('#importFileInput').val('');
        $('#importDbModal').modal('show');
    });

    // 3. Open Clone / Copy Modal
    $(document).on('click', '.open-copy-db-modal', function(e) {
        e.preventDefault();
        let dbName = $(this).data('db');
        $('#copySrcDb').val(dbName);
        $('#copySrcDbDisplay').val(dbName);
        $('#copyDbPassInput').val('');
        $('#copyDbModal').modal('show');
    });

    // 4. Open Check & Repair Modal
    $(document).on('click', '.open-repair-db-modal', function(e) {
        e.preventDefault();
        let dbName = $(this).data('db');
        $('#repairTargetDb').val(dbName);
        $('#repairDbDisplay').val(dbName);
        $('#repairDbModal').modal('show');
    });

    // 5. Open Transfer Owner Modal
    $(document).on('click', '.open-transfer-db-modal', function(e) {
        e.preventDefault();
        let dbName = $(this).data('db');
        let currentOwner = $(this).data('owner');
        
        $('#transferTargetDb').val(dbName);
        $('#transferDbDisplay').val(dbName);
        $('#transferCurrentOwner').val(currentOwner);
        $('#transferNewOwner').val('');
        $('#transferDbModal').modal('show');
    });

    // =================================================================
    // ENTERPRISE DATABASE SUITE AJAX SUBMISSIONS
    // =================================================================

    // Import Submission
    $('#submitImportDbBtn').click(function() {
        let btn = $(this);
        let form = $('#importDbForm')[0];
        if (!form.checkValidity()) { form.reportValidity(); return; }
        
        let formData = new FormData(form);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Uploading...');

        $.ajax({
            url: '/ajax/manage_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                if(res.success) {
                    $('#importDbModal').modal('hide');
                    window.showToast('success', 'Task Queued', 'Import process dispatched to Python queue.');
                } else {
                    window.showToast('error', 'Import Error', res.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-up"></i> Start Import');
            }
        });
    });

    // Clone Submission
    $('#submitCopyDbBtn').click(function() {
        let btn = $(this);
        let form = $('#copyDbForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Cloning...');
        $.ajax({
            url: '/ajax/manage_db.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#copyDbModal').modal('hide');
                    window.showToast('success', 'Task Queued', 'Database cloning dispatched in background.');
                    setTimeout(window.fetchDatabases, 2000); // Refresh list
                } else { window.showToast('error', 'Clone Error', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-files"></i> Clone Database');
            }
        });
    });

    // Generate Pass helper for Clone Modal
    $('#generateCopyDbPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 20; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#copyDbPassInput').val(pass);
        navigator.clipboard.writeText(pass);
        let btn = $(this);
        let originalText = btn.html();
        btn.html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { btn.html(originalText); }, 2000);
    });

    // Repair Submission
    $('#submitRepairDbBtn').click(function() {
        let btn = $(this);
        let form = $('#repairDbForm');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Executing...');
        $.ajax({
            url: '/ajax/manage_db.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#repairDbModal').modal('hide');
                    window.showToast('success', 'Task Queued', 'Check Live Tasks to view the diagnostic output log.');
                } else { window.showToast('error', 'Repair Error', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-play-circle"></i> Run Diagnostics');
            }
        });
    });

    // Soft Transfer Submission
    $('#submitTransferDbBtn').click(function() {
        let btn = $(this);
        let form = $('#transferDbForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Transferring...');
        $.ajax({
            url: '/ajax/manage_db.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#transferDbModal').modal('hide');
                    window.showToast('success', 'Task Queued', 'Ownership update processed.');
                    setTimeout(window.fetchDatabases, 2000); // Refresh UI 
                } else { window.showToast('error', 'Transfer Error', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-arrow-right-circle"></i> Transfer Owner');
            }
        });
    });

    window.fetchUsers();
    window.fetchDatabases();
});