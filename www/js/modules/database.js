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
                    tbody.html('<tr><td colspan="3" class="text-center text-muted py-3">No users found.</td></tr>');
                    return;
                }
                response.users.forEach(function(u) {
                    let badges = '';
                    if(u.has_ssh == 1) badges += '<span class="badge bg-dark me-1" title="SSH Key Generated"><i class="bi bi-key"></i></span>';
                    if(u.has_webhook == 1) badges += '<span class="badge bg-success" title="Webhook Active"><i class="bi bi-lightning-charge"></i></span>';
                    if(badges === '') badges = '<span class="text-muted small">None</span>';

                    let row = `<tr>
                            <td class="fw-bold">${u.username}</td>
                            <td class="small text-muted">${u.email || 'No email'}</td>
                            <td>${badges}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger delete-user" data-user="${u.username}" title="Delete User"><i class="bi bi-trash"></i></button>
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
                    tbody.html('<tr><td colspan="3" class="text-center text-muted py-3">No databases provisioned.</td></tr>');
                    $('.db-dropdown').empty().append('<option value="">No databases available</option>');
                    return;
                }
                response.databases.forEach(function(db) {
                    let row = `<tr>
                            <td class="fw-bold text-primary">${db.db_name}</td>
                            <td><code>${db.db_user}</code></td>
                            <td><i class="bi bi-person text-muted"></i> ${db.owner_username}</td>
                            <td class="text-end">
                                <a href="/pma/index.php?db=${db.db_name}" target="_blank" class="btn btn-sm btn-success" title="Open in phpMyAdmin"><i class="bi bi-database-fill-gear"></i></a>
                                <button class="btn btn-sm btn-outline-secondary change-db-pass ms-1" data-db="${db.db_name}" data-user="${db.db_user}" title="Change Password"><i class="bi bi-key"></i></button>
                                <button class="btn btn-sm btn-outline-danger delete-db ms-1" data-db="${db.db_name}" title="Delete Database"><i class="bi bi-trash"></i></button>
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
            $('#redisStatusBadge').removeClass('bg-secondary bg-danger').addClass('bg-success').text('Online');
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
            $('#redisStatusBadge').removeClass('bg-secondary bg-success').addClass('bg-danger').text('Offline');
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
                    window.fetchUsers();
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() { alert('A server error occurred.'); btn.prop('disabled', false).html(originalText); }
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
            if(privs.length === 0) { alert("You must select at least one privilege for a custom role."); return; }
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
                    setTimeout(window.fetchDatabases, 1500); 
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() { alertBox.addClass('alert-danger').text('A server error occurred.').removeClass('d-none'); btn.prop('disabled', false).html(originalText); }
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
                    alert("Password rotated successfully! Don't forget to update your application config files.");
                } else { alert("Error: " + response.error); }
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
                if(response.success) { setTimeout(window.fetchDatabases, 2500); } 
                else { alert("Error: " + response.error); btn.prop('disabled', false).html('<i class="bi bi-trash"></i>'); }
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
                    alert("Success! Nginx and PHP limits have been increased globally.");
                } else { alert("Error: " + response.error); }
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
                alert(res.message);
                window.fetchRedisStats();
            } else { alert("Error: " + res.error); }
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
                    alert(response.message);
                    $('#overview-tab').tab('show'); 
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).html(originalIcon);
            },
            error: function() { alert("Network Error."); btn.prop('disabled', false).html(originalIcon); }
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
                } else { alert("Error fetching credentials: " + res.error); }
                btn.prop('disabled', false).html(originalHtml);
            },
            error: function() { alert("Network error."); btn.prop('disabled', false).html(originalHtml); }
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

    window.fetchUsers();
    window.fetchDatabases();
});