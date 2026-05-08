// /opt/panel/www/js/modules/security.js

window.fetchFirewall = function() {
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
};

window.fetchDnsRecords = function() {
    $.ajax({
        url: '/ajax/get_dns.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let tbody = $('#dynamicDnsTable');
                tbody.empty();
                if(response.records.length === 0) {
                    tbody.html('<tr><td colspan="4" class="text-center text-muted py-3">No DNS records managed by panel.</td></tr>');
                    return;
                }
                response.records.forEach(function(r) {
                    let typeBadge = '';
                    if(r.record_type === 'A') typeBadge = '<span class="badge bg-primary">A</span>';
                    else if(r.record_type === 'CNAME') typeBadge = '<span class="badge bg-info text-dark">CNAME</span>';
                    else if(r.record_type === 'TXT') typeBadge = '<span class="badge bg-secondary">TXT</span>';
                    else if(r.record_type === 'MX') typeBadge = '<span class="badge bg-warning text-dark">MX</span>';
                    else typeBadge = `<span class="badge bg-dark">${r.record_type}</span>`;

                    let row = `<tr>
                            <td class="fw-bold">${r.domain_name}</td>
                            <td>${r.record_name}</td>
                            <td>${typeBadge}</td>
                            <td class="text-truncate" style="max-width: 150px;" title="${r.record_value}"><code>${r.record_value}</code></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger delete-dns" 
                                    data-domain="${r.domain_name}" data-name="${r.record_name}" 
                                    data-type="${r.record_type}" data-val="${btoa(r.record_value)}" 
                                    title="Delete Record"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>`;
                    tbody.append(row);
                });
            }
        }
    });
};

window.fetchFail2Ban = function() {
    $.ajax({
        url: '/ajax/get_fail2ban.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let tbody = $('#dynamicFail2banTable');
                tbody.empty();
                
                if(response.bans.length === 0) {
                    tbody.html('<tr><td colspan="3" class="text-center text-muted py-4"><i class="bi bi-shield-check text-success fs-4 d-block mb-2"></i> No active IP bans detected.</td></tr>');
                } else {
                    response.bans.forEach(function(b) {
                        let badgeClass = 'bg-danger';
                        if(b.jail === 'opanel') badgeClass = 'bg-dark';
                        if(b.jail === 'sshd') badgeClass = 'bg-primary';
                        
                        let row = `<tr>
                            <td class="fw-bold font-monospace text-danger">${b.ip}</td>
                            <td><span class="badge ${badgeClass} text-uppercase"><i class="bi bi-lock-fill"></i> ${b.jail}</span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-success unban-ip fw-bold shadow-sm" data-ip="${b.ip}" data-jail="${b.jail}" title="Unban IP">
                                    <i class="bi bi-unlock-fill"></i> Unban
                                </button>
                            </td>
                        </tr>`;
                        tbody.append(row);
                    });
                }

                let statsBody = $('#dynamicFail2banStatsTable');
                statsBody.empty();
                let globalTotalBans = 0;

                if(response.stats && response.stats.length > 0) {
                    $('#f2bGlobalJails').text(response.stats.length);
                    response.stats.forEach(function(s) {
                        globalTotalBans += parseInt(s.total_banned);
                        let curBannedHtml = s.currently_banned > 0 
                            ? `<span class="badge bg-danger fs-6">${s.currently_banned}</span>` 
                            : `<span class="badge bg-light text-dark border">${s.currently_banned}</span>`;

                        let row = `<tr>
                            <td class="fw-bold text-uppercase"><i class="bi bi-lock-fill text-muted me-1"></i> ${s.name}</td>
                            <td><code class="text-muted small">${s.file_list}</code></td>
                            <td class="text-center">${curBannedHtml}</td>
                            <td class="text-center fw-bold text-secondary">${s.total_banned}</td>
                        </tr>`;
                        statsBody.append(row);
                    });
                    $('#f2bGlobalTotalBans').text(globalTotalBans);
                } else {
                    statsBody.html('<tr><td colspan="4" class="text-center text-danger py-3">No active jails found. Check daemon.</td></tr>');
                }
            }
        }
    });
};

$(document).ready(function() {
    
    // === FIREWALL ===
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
            success: function() { setTimeout(window.fetchFirewall, 2500); }
        });
    });

    // === DNS ===
    $('#dnsRecordForm').on('submit', function(e) {
        e.preventDefault();
        let action = $('select[name="action"]').val();
        if(action === 'delete') {
            if(!confirm("Are you sure you want to delete this specific DNS record? This cannot be undone.")) return;
        }

        let btn = $('#submitDnsRecordBtn');
        let alertBox = $('#dnsRecordAlert');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/manage_dns_records.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.addClass('alert-success').text(response.message).removeClass('d-none');
                    if(action === 'add') $('#dnsRecordForm')[0].reset(); 
                } else { alertBox.addClass('alert-danger').text(response.error).removeClass('d-none'); }
            },
            complete: function() { btn.prop('disabled', false).text('Execute Change'); }
        });
    });

    $(document).on('click', '.delete-dns', function() {
        let domain = $(this).data('domain');
        let name = $(this).data('name');
        let type = $(this).data('type');
        let val = atob($(this).data('val')); 
        
        if(!confirm(`Are you sure you want to delete this ${type} record (${name}.${domain})?`)) return;
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_dns_records.php',
            type: 'POST',
            data: { action: 'delete', domain: domain, name: name, type: type, value: val },
            success: function() { setTimeout(window.fetchDnsRecords, 2500); }
        });
    });

    $(document).on('click', '#initDnsZoneBtn', function() {
        let domain = prompt("Enter the exact domain name to initialize its baseline DNS Zone (e.g., example.com):");
        if(!domain) return;
        let btn = $(this);
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Generating Zone...');

        $.ajax({
            url: '/ajax/create_dns.php',
            type: 'POST',
            data: { domain: domain },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert(response.message + " Check Live Tasks for status.");
                    setTimeout(window.fetchDnsRecords, 3000); 
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // === FAIL2BAN ===
    $(document).on('click', '.unban-ip', function() {
        let ip = $(this).data('ip');
        let jail = $(this).data('jail');
        
        let warning = `Are you sure you want to remove ${ip} from the ${jail} jail?`;
        if(!confirm(warning)) return;
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/unban_ip.php',
            type: 'POST',
            data: { ip: ip, jail: jail },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#overview-tab').tab('show');
                    alert(response.message);
                    window.fetchFail2Ban();
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false).html('<i class="bi bi-unlock-fill"></i> Unban');
                }
            }
        });
    });

    // === SSH & 2FA ===
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

    window.fetchFirewall();
    window.fetchDnsRecords();
    window.fetchFail2Ban();
    setInterval(window.fetchFail2Ban, 10000);
});