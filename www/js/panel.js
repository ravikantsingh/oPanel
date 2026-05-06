$(document).ready(function() {
    
    // =================================================================
    // DATABASE & USER MANAGEMENT MODULE
    // =================================================================
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
                    fetchUsers();                    
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
            success: function() { setTimeout(fetchUsers, 3000); }
        });
    });

    $('#generateUserPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#password').val(pass);
        navigator.clipboard.writeText(pass);
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
    });

    function fetchUsers() {
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
                    response.users.forEach(function(u) { userDropdowns.append('<option value="' + u.username + '">' + u.username + '</option>'); });
                }
            }
        });
    }

    $('#dbOwner').on('change', function() {
        let val = $(this).val();
        $('#dbPrefixLabel').text(val ? val + '_' : 'prefix_');
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
                    setTimeout(fetchDatabases, 1500); 
                } else { alertBox.addClass('alert-danger').text(response.error).removeClass('d-none'); }
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
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
    });

    $(document).on('change', '#dbAcl', function() {
        if($(this).val() === 'custom') { $('#dbCustomIp').removeClass('d-none').prop('required', true); } 
        else { $('#dbCustomIp').addClass('d-none').prop('required', false); }
    });

    $(document).on('change', '#dbRole', function() {
        if($(this).val() === 'custom') { $('#customPrivilegesGrid').removeClass('d-none'); } 
        else { $('#customPrivilegesGrid').addClass('d-none'); }
    });

    function fetchDatabases() {
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
                    response.databases.forEach(function(db) { dbDropdowns.append('<option value="' + db.db_name + '">' + db.db_name + '</option>'); });
                }
            }
        });
    }

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
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
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
                if(response.success) { setTimeout(fetchDatabases, 2500); } 
                else { alert("Error: " + response.error); btn.prop('disabled', false).html('<i class="bi bi-trash"></i>'); }
            },
            error: function() { alert("A server error occurred."); btn.prop('disabled', false).html('<i class="bi bi-trash"></i>'); }
        });
    });

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

    // =================================================================
    // SECURITY MODULE (Firewall, Fail2ban, SSH, 2FA)
    // =================================================================
    $('#firewallForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#submitFwBtn');
        let alertBox = $('#fwFormAlert');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating UFW...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');
        $.ajax({
            url: '/ajax/manage_firewall.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.addClass('alert-success').text(response.message).removeClass('d-none');
                    $('#firewallForm')[0].reset();
                } else { alertBox.addClass('alert-danger').text(response.error).removeClass('d-none'); }
            },
            error: function() { alertBox.addClass('alert-danger').text('A server error occurred.').removeClass('d-none'); },
            complete: function() { btn.prop('disabled', false).text('Allow Port'); }
        });
    });

    function fetchFirewall() {
        $.ajax({
            url: '/ajax/get_firewall.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    let tbody = $('#dynamicFirewallTable');
                    tbody.empty();
                    if(response.rules.length === 0) {
                        tbody.html('<tr><td colspan="3" class="text-center text-muted py-3">No custom rules configured.</td></tr>');
                        return;
                    }
                    response.rules.forEach(function(r) {
                        let row = `<tr>
                                <td class="fw-bold">${r.port}</td>
                                <td class="text-uppercase">${r.protocol}</td>
                                <td><span class="badge bg-success">ALLOW</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger delete-fw" data-port="${r.port}" data-proto="${r.protocol}" title="Close Port"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>`;
                        tbody.append(row);
                    });
                }
            }
        });
    }

    $(document).on('click', '.delete-fw', function() {
        let port = $(this).data('port');
        let proto = $(this).data('proto');
        if(!confirm(`Are you sure you want to close port ${port}/${proto}?`)) return;
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/ajax/manage_firewall.php',
            type: 'POST',
            data: { action: 'delete', port: port, protocol: proto },
            success: function() { setTimeout(fetchFirewall, 2500); }
        });
    });

    $('#fetchSshBtn').on('click', function() {
        let btn = $(this);
        let targetUser = $('#sshUsername').val(); 
        if(!targetUser) { alert("Error: Username is missing from the UI."); return; }
        btn.prop('disabled', true).text('Loading...');
        $.ajax({
            url: '/ajax/get_ssh_key.php',
            type: 'POST', 
            data: { username: targetUser }, 
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#sshKeyDisplay').removeClass('d-none').val(response.key);
                    btn.text('Key Ready');
                } else {
                    alert(response.message || response.error);
                    btn.prop('disabled', false).text('View Key');
                }
            }
        });
    });

    $('#twoFactorToggle').on('change', function() {
        let isChecked = $(this).is(':checked');
        let action = isChecked ? 'enable' : 'disable';
        let toggleBtn = $(this);
        toggleBtn.prop('disabled', true); 
        $.ajax({
            url: '/ajax/toggle_2fa.php',
            type: 'POST',
            data: { action: action },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.state === 'enabled') {
                        $('#qrCodeImage').attr('src', response.qr_url);
                        $('#totpSecretText').text(response.secret);
                        $('#qrCodeContainer').removeClass('d-none');
                    } else {
                        $('#qrCodeContainer').addClass('d-none');
                        alert("2FA has been successfully disabled.");
                    }
                } else { alert("Error: " + response.error); toggleBtn.prop('checked', !isChecked); }
                toggleBtn.prop('disabled', false); 
            },
            error: function() { alert("Network Error."); toggleBtn.prop('checked', !isChecked); toggleBtn.prop('disabled', false); }
        });
    });

    // =================================================================
    // MAIL MODULE
    // =================================================================
    $(document).on('click', '.manage-mail', function() {
        let domain = $(this).data('domain');
        $('#mailDomainTitle').text(domain);
        $('#mailDomain').val(domain);
        $('#mailSuffixLabel').text('@' + domain);
        $('#createMailForm')[0].reset();
        $('#mailAlert').addClass('d-none');
        $('#mailEngineNotInstalled').addClass('d-none');
        $('#mailEngineInstalled').addClass('d-none');
        
        $.ajax({
            url: '/ajax/get_mail_engine_status.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.installed) {
                    $('#mailEngineInstalled').removeClass('d-none');
                    fetchMailboxes(domain); 
                } else { $('#mailEngineNotInstalled').removeClass('d-none'); }
                $('#mailBoxModal').modal('show');
            }
        });
    });

    $('#installMailEngineBtn').click(function() {
        let btn = $(this);
        if(!confirm("This will install and configure Postfix and Dovecot. It takes about 60 seconds. Proceed?")) return;
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Installing in Background...');
        $.ajax({
            url: '/ajax/install_mail_engine.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#mailBoxModal').modal('hide');
                    alert(response.message + " Check the Live Tasks log to watch the installation.");
                    $('#overview-tab').tab('show'); 
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).html('<i class="bi bi-download"></i> Install Mail Engine');
            }
        });
    });

    $('#uninstallMailEngineBtn').click(function() {
        let btn = $(this);
        let confirmText = prompt("CRITICAL WARNING: This will permanently destroy all physical mailboxes on this server and uninstall Postfix/Dovecot. Type 'PURGE' to confirm:");
        if(confirmText !== 'PURGE') { if(confirmText !== null) alert("Aborted. You must type exactly PURGE."); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Purging...');
        $.ajax({
            url: '/ajax/uninstall_mail_engine.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#mailBoxModal').modal('hide');
                    alert(response.message + " Check Live Tasks for completion, then reopen this modal to see the Offline state.");
                    $('#overview-tab').tab('show'); 
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).text('Uninstall Engine');
            }
        });
    });

    function fetchMailboxes(domain) {
        let tbody = $('#dynamicMailTable');
        tbody.html('<tr><td colspan="3" class="text-center"><span class="spinner-border spinner-border-sm"></span> Syncing with Database...</td></tr>');
        $.ajax({
            url: '/ajax/get_mail_users.php',
            type: 'POST',
            data: { domain: domain },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    tbody.empty();
                    if(response.emails.length === 0) {
                        tbody.html('<tr><td colspan="3" class="text-center text-muted">No mailboxes provisioned for this domain.</td></tr>');
                        return;
                    }
                    response.emails.forEach(function(m) {
                        let row = `<tr>
                            <td class="fw-bold text-dark"><i class="bi bi-person-badge text-muted me-1"></i> ${m.email}</td>
                            <td><span class="badge bg-light text-dark border">${m.quota} MB</span></td>
                            <td class="text-end">
                                <a href="https://webmail.${domain}" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Login to Webmail"><i class="bi bi-box-arrow-up-right"></i></a>
                                <button class="btn btn-sm btn-danger delete-mail" data-email="${m.email}" data-domain="${domain}" title="Delete Mailbox"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>`;
                        tbody.append(row);
                    });
                }
            }
        });
    }

    $('#generateMailPass').click(function() {
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*+";
        let pass = "";
        for (let i = 0; i < 18; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#mailPassInput').val(pass);
        navigator.clipboard.writeText(pass);
        let btn = $(this);
        let original = btn.html();
        btn.html('<i class="bi bi-check2 text-success"></i>');
        setTimeout(() => { btn.html(original); }, 1500);
    });

    $('#createMailForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#submitMailBtn');
        let alertBox = $('#mailAlert');
        let domain = $('#mailDomain').val();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Queueing...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');
        $.ajax({
            url: '/ajax/manage_mail_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.addClass('alert-success').text(response.message).removeClass('d-none');
                    $('#createMailForm')[0].reset();
                    setTimeout(() => fetchMailboxes(domain), 2500);
                } else { alertBox.addClass('alert-danger').text(response.error).removeClass('d-none'); }
            },
            complete: function() { btn.prop('disabled', false).html('<i class="bi bi-save"></i> Provision Mailbox'); }
        });
    });

    $(document).on('click', '.delete-mail', function() {
        let email = $(this).data('email');
        let domain = $(this).data('domain');
        if(!confirm(`CRITICAL: Are you sure you want to permanently delete ${email}? All physical emails on the hard drive will be destroyed instantly.`)) return;
        
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/ajax/manage_mail_user.php',
            type: 'POST',
            data: { action: 'delete', email: email, domain: domain },
            dataType: 'json',
            success: function(response) {
                if(response.success) { setTimeout(() => fetchMailboxes(domain), 2000); } 
                else { alert("Error: " + response.error); btn.prop('disabled', false).html('<i class="bi bi-trash"></i>'); }
            }
        });
    });

    $(document).on('click', '.route-external-mail', function() {
        let provider = $(this).data('provider');
        let domain = $('#mailDomain').val();
        let btn = $(this);
        let originalText = btn.html();
        
        let warning = `WARNING: You are about to wipe all existing mail DNS records for ${domain} and route all email to ${provider.toUpperCase()}.\n\nProceed?`;
        if(!confirm(warning)) return;
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Injecting DNS...');
        $.ajax({
            url: '/ajax/manage_mail_dns.php',
            type: 'POST',
            data: { domain: domain, provider: provider },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#mailBoxModal').modal('hide');
                    alert(response.message + " Check the Live Tasks log to verify propagation.");
                    $('#overview-tab').tab('show'); 
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // =================================================================
    // INITIALIZATION
    // =================================================================
    fetchUsers();
    fetchDatabases();
    fetchFirewall();
});