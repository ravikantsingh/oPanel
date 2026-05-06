// /opt/panel/www/js/modules/mail.js

window.fetchMailboxes = function(domain) {
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
};

$(document).ready(function() {

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
                    window.fetchMailboxes(domain); 
                } else {
                    $('#mailEngineNotInstalled').removeClass('d-none');
                }
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
                    setTimeout(() => window.fetchMailboxes(domain), 2500);
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
                if(response.success) { setTimeout(() => window.fetchMailboxes(domain), 2000); } 
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

});