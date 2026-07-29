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
                    tbody.html('<tr><td colspan="4" class="text-center text-muted py-4 border-0">No custom rules configured.</td></tr>');
                    return;
                }
                response.rules.forEach(function(r) {
                    let row = `<tr>
                            <td class="fw-bold text-dark">${r.port}</td>
                            <td class="text-uppercase small">${r.protocol}</td>
                            <td><span class="badge bg-success bg-opacity-10 text-success border-0 rounded-pill px-3 shadow-sm">ALLOW</span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light text-danger shadow-sm delete-fw" data-port="${r.port}" data-proto="${r.protocol}" title="Close Port"><i class="bi bi-trash"></i></button>
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
                    tbody.html('<tr><td colspan="5" class="text-center text-muted py-4 border-0">No DNS records managed by panel.</td></tr>');
                    return;
                }
                response.records.forEach(function(r) {
                    let typeBadge = '';
                    if(r.record_type === 'A') typeBadge = '<span class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-2">A</span>';
                    else if(r.record_type === 'CNAME') typeBadge = '<span class="badge bg-info bg-opacity-10 text-info border-0 rounded-pill px-2">CNAME</span>';
                    else if(r.record_type === 'TXT') typeBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border-0 rounded-pill px-2">TXT</span>';
                    else if(r.record_type === 'MX') typeBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border-0 rounded-pill px-2">MX</span>';
                    else typeBadge = `<span class="badge bg-dark bg-opacity-10 text-dark border-0 rounded-pill px-2">${r.record_type}</span>`;

                    let row = `<tr>
                            <td class="fw-bold text-dark small">${r.domain_name}</td>
                            <td class="small text-muted">${r.record_name}</td>
                            <td>${typeBadge}</td>
                            <td class="text-truncate small" style="max-width: 150px;" title="${r.record_value}"><code>${r.record_value}</code></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light text-danger shadow-sm delete-dns" 
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
                
                // ==========================================
                // 1. Handle the Banned IPs Table
                // ==========================================
                let tbody = $('#dynamicFail2banTable');
                tbody.empty();
                
                if(response.bans.length === 0) {
                    tbody.html('<tr><td colspan="3" class="text-center text-muted py-5 border-0"><i class="bi bi-shield-check text-success fs-2 d-block mb-2"></i> No active IP bans detected.</td></tr>');
                } else {
                    response.bans.forEach(function(b) {
                        let badgeClass = 'text-danger bg-danger bg-opacity-10';
                        if(b.jail === 'stackrium') badgeClass = 'text-dark bg-dark bg-opacity-10';
                        if(b.jail === 'sshd') badgeClass = 'text-primary bg-primary bg-opacity-10';
                        
                        let row = `<tr>
                            <td class="fw-bold font-monospace text-danger">${b.ip}</td>
                            <td><span class="badge ${badgeClass} border-0 rounded-pill px-3 shadow-sm text-uppercase"><i class="bi bi-lock-fill"></i> ${b.jail}</span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-success unban-ip fw-bold shadow-sm" data-ip="${b.ip}" data-jail="${b.jail}" title="Unban IP">
                                    <i class="bi bi-unlock-fill"></i> Unban
                                </button>
                            </td>
                        </tr>`;
                        tbody.append(row);
                    });
                }

                // ==========================================
                // 2. Handle the Telemetry Stats
                // ==========================================
                let statsBody = $('#dynamicFail2banStatsTable');
                statsBody.empty();
                let globalTotalBans = 0;

                if(response.stats && response.stats.length > 0) {
                    $('#f2bGlobalJails').text(response.stats.length);
                    
                    response.stats.forEach(function(s) {
                        globalTotalBans += parseInt(s.total_banned);
                        
                        let cleanHoverTitle = s.file_list
                            .replace(/(<([^>]+)>)/gi, "")
                            .split(/[\s,]+/) 
                            .filter(path => path.trim() !== '')
                            .map(path => '📄 ' + path.trim())
                            .join('&#10;');

                        let curFailedHtml = s.currently_failed > 0 
                            ? `<span class="badge bg-warning text-dark border-0 shadow-sm">${s.currently_failed}</span>` 
                            : `<span class="text-muted bg-light px-2 py-1 rounded small border-0">${s.currently_failed}</span>`;

                        let curBannedHtml = s.currently_banned > 0 
                            ? `<span class="badge bg-danger shadow-sm border-0">${s.currently_banned}</span>` 
                            : `<span class="badge bg-light text-dark border-0">${s.currently_banned}</span>`;

                        let row = `
                        <div class="list-group-item py-3 px-3 border-0 border-bottom bg-white list-group-item-action shadow-sm mb-2 rounded-3">
                            <div class="row align-items-center">
                                <div class="col-6 col-md-3 mb-2 mb-md-0 fw-bold text-uppercase text-dark text-nowrap">
                                    <i class="bi bi-lock-fill text-muted me-1"></i> ${s.name}
                                </div>
                                <div class="col-6 col-md-3 mb-2 mb-md-0" style="min-width: 0;">
                                    <div class="text-muted font-monospace small text-truncate" title="${cleanHoverTitle}" style="cursor: help;">
                                        ${s.file_list}
                                    </div>
                                </div>
                                <div class="col-4 col-md-2 text-center">
                                    <div class="d-md-none small text-muted mb-1">Strikes</div>
                                    ${curFailedHtml}
                                </div>
                                <div class="col-4 col-md-2 text-center">
                                    <div class="d-md-none small text-muted mb-1">Bans</div>
                                    ${curBannedHtml}
                                </div>
                                <div class="col-4 col-md-2 text-center">
                                    <div class="d-md-none small text-muted mb-1">Lifetime</div>
                                    <div class="fw-bold ${s.total_banned > 0 ? 'text-secondary' : 'text-muted'}">${s.total_banned}</div>
                                </div>
                            </div>
                        </div>`;
                        
                        statsBody.append(row);
                    });
                    
                    $('#f2bGlobalTotalBans').text(globalTotalBans);
                    
                } else {
                    statsBody.html('<div class="list-group-item text-center text-danger py-5 border-0 bg-transparent"><i class="bi bi-exclamation-octagon me-2 fs-3 d-block mb-2"></i> No active jails found. Check daemon.</div>');
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
                    window.showToast('success', 'Firewall Updated', 'UFW rules applied successfully.');
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
                    window.showToast('success', 'DNS Init', response.message + ' Check Live Tasks.');
                    setTimeout(window.fetchDnsRecords, 3000); 
                } else { 
                    window.showToast('error', 'Error', response.error); 
                }
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
                    window.showToast('success', 'IP Unbanned', response.message);
                    window.fetchFail2Ban();
                } else {
                    window.showToast('error', 'Unban Failed', response.error);
                    btn.prop('disabled', false).html('<i class="bi bi-unlock-fill"></i> Unban');
                }
            }
        });
    });

    // === SMART SSH KEY AUTO-POLLER ===
    $('#sshUsername').on('change', function() {
        let targetUser = $(this).val();
        let keyContainer = $('#sshKeyContainer');
        let keyDisplay = $('#sshKeyDisplay');

        if (!targetUser) {
            keyContainer.addClass('d-none'); 
            return;
        }

        keyContainer.removeClass('d-none');
        keyDisplay.removeClass('text-success').addClass('text-warning').val('Fetching SSH deploy key... Please wait.');
        
        let pollCount = 0;
        let maxPolls = 10; 
        
        function fetchKey() {
            $.ajax({
                url: '/ajax/get_ssh_key.php',
                type: 'POST',
                data: { username: targetUser },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        keyDisplay.removeClass('text-warning text-danger').addClass('text-success').val(response.key);
                    } else if (response.message && response.message.includes('Generating')) {
                        pollCount++;
                        if (pollCount < maxPolls) {
                            keyDisplay.val(`Generating secure ED25519 key... (Attempt ${pollCount}/${maxPolls})\nPlease wait, the Python daemon is working...`);
                            setTimeout(fetchKey, 2000);
                        } else {
                            keyDisplay.removeClass('text-warning').addClass('text-danger').val('Generation timeout. Please check the Live Tasks log.');
                        }
                    } else {
                        keyDisplay.removeClass('text-warning').addClass('text-danger').val('Error: ' + (response.message || response.error));
                    }
                },
                error: function() {
                    keyDisplay.removeClass('text-warning').addClass('text-danger').val('Network error while communicating with the API.');
                }
            });
        }
        
        fetchKey();
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
                        window.showToast('info', '2FA Setup', 'Scan the QR code to finish setup.');
                    } else {
                        $('#qrCodeContainer').addClass('d-none');
                        window.showToast('info', 'Security', '2FA has been successfully disabled.');
                    }
                } else { 
                    window.showToast('error', '2FA Error', response.error); 
                    toggleBtn.prop('checked', !isChecked); 
                }
                toggleBtn.prop('disabled', false); 
            },
            error: function() { 
                window.showToast('error', 'Network Error', 'Could not reach server.'); 
                toggleBtn.prop('checked', !isChecked); 
                toggleBtn.prop('disabled', false); 
            }
        });
    });

    window.fetchFirewall();
    window.fetchDnsRecords();
    window.fetchFail2Ban();
    setInterval(window.fetchFail2Ban, 10000);
});