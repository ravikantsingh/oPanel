// /opt/panel/www/js/modules/web.js

// =================================================================
// 1. GLOBAL FUNCTIONS (Attached to Window for Cross-Module Access)
// =================================================================

window.fetchDomains = function() {
    $.ajax({
        url: '/ajax/get_domains.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let container = $('#dynamicDomainsAccordion');
                container.empty();
                
                if(response.domains.length === 0) {
                    container.html('<div class="text-center text-muted py-5 border-0 bg-transparent">No domains configured.</div>');
                    return;
                }

                let allRowsHtml = ''; 
                let dnsDropdownOptions = '<option value="all">All Domains</option>';

                response.domains.forEach(function(d, index) {
                    let isExpanded = index === 0 ? 'show' : '';
                    let isCollapsed = index === 0 ? '' : 'collapsed';
                    let proto = d.has_ssl == 1 ? 'https' : 'http'; 
                    let isPhp = (d.app_type === 'php' || !d.app_type);
                    
                    dnsDropdownOptions += `<option value="${d.domain_name}">${d.domain_name}</option>`;
                    
                    let isSuspended = d.status === 'suspended';
                    let suspendIcon = isSuspended ? 'bi-play-fill' : 'bi-pause-circle';
                    let suspendText = isSuspended ? 'Unsuspend' : 'Suspend';
                    let suspendColor = isSuspended ? 'outline-success' : 'outline-warning text-dark';
                    let suspendAction = isSuspended ? 'unsuspend' : 'suspend';

                    let wafColor = (d.waf_enabled == 1) ? 'success' : 'outline-secondary';
                    let wafIcon = (d.waf_enabled == 1) ? 'bi-shield-check' : 'bi-shield-slash';
                    let wafText = (d.waf_enabled == 1) ? 'WAF: ON' : 'WAF: OFF';

                    let appActions = '';
                    if (isPhp) {
                        appActions = `
                            <button class="btn btn-sm btn-outline-danger text-start deploy-laravel shadow-sm" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-box-seam me-2"></i> Deploy Laravel
                            </button>
                            <button class="btn btn-sm btn-outline-warning text-dark text-start deploy-python shadow-sm" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-filetype-py me-2"></i> Deploy Python
                            </button>
                            <button class="btn btn-sm btn-outline-success text-start open-node-modal shadow-sm" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-hexagon-fill me-2"></i> Deploy Node.js
                            </button>
                            <button class="btn btn-sm btn-outline-primary text-start open-wp-modal shadow-sm" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-wordpress me-2"></i> WordPress
                            </button>
                        `;
                    } else {
                        let appLabel = d.app_type.charAt(0).toUpperCase() + d.app_type.slice(1);
                        let color = d.app_type === 'laravel' ? 'danger' : 'warning text-dark';
                        let restartBtn = '';
                        if (d.app_type === 'python' || d.app_type === 'node') {
                            restartBtn = `
                            <button class="btn btn-sm btn-outline-dark text-start restart-app shadow-sm" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-arrow-clockwise me-2"></i> Restart Engine
                            </button>`;
                        }
                        appActions = `
                            <button class="btn btn-sm btn-${color} text-start disabled shadow-sm border-0">
                                <i class="bi bi-cpu-fill me-2"></i> ${appLabel} Active
                            </button>
                            ${restartBtn}
                            <button class="btn btn-sm btn-outline-secondary text-start revert-app shadow-sm" data-domain="${d.domain_name}" data-user="${d.username}" data-type="${d.app_type}">
                                <i class="bi bi-arrow-counterclockwise me-2"></i> Revert to PHP
                            </button>
                        `;
                    }

                    let gitDisplay = '<div class="text-muted small px-2 py-1"><i class="bi bi-github"></i> Git Auto-Deployment Not Configured</div>';
                    if (d.git_repo && d.git_repo !== 'Not Configured') {
                        let host = window.location.hostname;
                        let webhookUrl = `https://${host}:7443/ajax/webhook.php?domain=${d.domain_name}&token=${d.webhook_token}`;
                        let currentBranch = d.git_branch || 'main'; 
                        let commitsHtml = '';
                        if (d.latest_commits) {
                            try {
                                let commits = JSON.parse(d.latest_commits).slice(0, 4);
                                commitsHtml = '<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-2 mt-2">';
                                commits.forEach(c => {
                                    commitsHtml += `
                                    <div class="col">
                                        <div class="p-2 border-0 rounded-3 bg-white h-100 shadow-sm d-flex flex-column">
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge bg-success bg-opacity-10 text-success me-2 border-0 p-1"><i class="bi bi-check-lg"></i></span>
                                                <span class="text-primary font-monospace fw-bold small">[${c.commit}]</span>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.70rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="${c.message}">
                                                ${c.message}
                                            </div>
                                        </div>
                                    </div>`;
                                });
                                commitsHtml += '</div>';
                            } catch(e) {}
                        }
                        gitDisplay = `
                            <div class="d-flex flex-wrap justify-content-between align-items-end">
                                <div class="flex-grow-1 me-3 mb-2 mb-md-0">
                                    <div class="fw-bold small text-dark mb-1"><i class="bi bi-github me-1"></i> Repository: <span class="text-primary">${d.git_repo}</span> (Branch: ${currentBranch})</div>
                                    <div class="input-group input-group-sm shadow-sm border-0 rounded-3">
                                        <span class="input-group-text bg-light border-0 px-2"><i class="bi bi-lightning-charge-fill text-warning"></i> Hook</span>
                                        <input type="text" class="form-control font-monospace text-muted border-0 copy-trigger" style="font-size: 0.70rem; cursor: pointer;" value="${webhookUrl}" readonly title="Click to copy Webhook URL">
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-dark manual-git-pull shadow-sm border-0" data-domain="${d.domain_name}" data-user="${d.username}" data-branch="${currentBranch}">
                                        <i class="bi bi-arrow-down-circle me-1"></i> Pull Latest Code
                                    </button>
                                </div>
                            </div>
                            ${commitsHtml}
                        `;
                    }

                    allRowsHtml += `
                    <div class="accordion-item mb-3 border-0 shadow-sm rounded-3">
                        <div class="d-flex align-items-stretch border-0 bg-white rounded-top-3">
                            <h2 class="accordion-header flex-grow-1 m-0">
                                <button class="accordion-button collapsed py-2 rounded-start border-0 shadow-none bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#acc-${d.id}">
                                    <div class="d-flex align-items-center w-100">
                                        <div class="me-2"><i class="bi bi-globe fs-4 text-primary"></i></div>
                                        <div class="lh-sm">
                                            <span class="fw-bold text-dark fs-6">${d.domain_name}</span>
                                            ${isSuspended ? '<span class="badge bg-danger bg-opacity-10 text-danger border-0 rounded-pill ms-1" style="font-size:0.65rem;">Suspended</span>' : ''}
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border-0 rounded-pill ms-3 async-dns-check" data-domain="${d.domain_name}">
                                                <i class="spinner-border spinner-border-sm" style="width: 10px; height: 10px;"></i> Checking...
                                            </span>
                                            <span class="text-muted small ms-2" style="font-size:0.75rem;">(User: ${d.username} | PHP ${d.php_version})</span>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div class="d-flex align-items-center px-3 border-start border-opacity-10 bg-light rounded-end-3">
                                <a href="${proto}://${d.domain_name}" target="_blank" onclick="event.stopPropagation()" class="btn btn-sm btn-light border-0 shadow-sm me-2 py-1 px-2" title="Visit Site">
                                    <i class="bi bi-box-arrow-up-right text-primary me-1"></i> Visit
                                </a>
                                <button class="btn btn-sm btn-outline-primary shadow-sm border-0 btn-jump-dns py-1 px-2" data-domain="${d.domain_name}"><i class="bi bi-globe me-1"></i> Manage DNS</button>
                                <button class="btn btn-sm btn-danger shadow-sm border-0 delete-domain ms-2 py-1 px-2" data-domain="${d.domain_name}" data-user="${d.username}" title="Delete Domain">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <div id="acc-${d.id}" class="accordion-collapse collapse" data-bs-parent="#dynamicDomainsAccordion">
                            <div class="accordion-body bg-light rounded-bottom-3 p-3 border-top border-opacity-10">
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 mb-4">
                                    <div class="col">
                                        <h6 class="text-muted small fw-bold text-uppercase border-bottom border-opacity-10 pb-2 mb-2"><i class="bi bi-cpu me-1"></i> App Engines</h6>
                                        <div class="d-grid gap-2">${appActions}</div>
                                    </div>
                                    <div class="col border-start border-opacity-10">
                                        <h6 class="text-muted small fw-bold text-uppercase border-bottom border-opacity-10 pb-2 mb-2"><i class="bi bi-shield-check me-1"></i> Security</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-${wafColor} shadow-sm border-0 text-start toggle-waf" data-domain="${d.domain_name}" data-action="${d.waf_enabled == 1 ? 'off' : 'on'}"><i class="bi ${wafIcon} me-2"></i> ${wafText}</button>
                                            <button class="btn btn-sm btn-outline-dark shadow-sm border-0 text-start edit-waf-rules" data-domain="${d.domain_name}" data-rules="${btoa(d.waf_custom_rules || '')}"><i class="bi bi-shield-lock me-2"></i> WAF Rules</button>
                                            <button class="btn btn-sm btn-outline-success shadow-sm border-0 text-start" data-bs-toggle="modal" data-bs-target="#installSslModal" onclick="$('#sslTargetDomain').val('${d.domain_name}').trigger('change');"><i class="bi bi-shield-lock-fill me-2"></i> Install SSL</button>
                                        </div>
                                    </div>
                                    <div class="col border-start border-opacity-10">
                                        <h6 class="text-muted small fw-bold text-uppercase border-bottom border-opacity-10 pb-2 mb-2"><i class="bi bi-folder2-open me-1"></i> Files & Cache</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-warning shadow-sm border-0 text-dark text-start deploy-fm" data-domain="${d.domain_name}" data-user="${d.username}" data-ver="${d.php_version}"><i class="bi bi-cloud-arrow-up-fill me-2"></i> Deploy File Manager</button>
                                            <button class="btn btn-sm btn-outline-primary shadow-sm border-0 text-start open-fm-sso" data-domain="${d.domain_name}"><i class="bi bi-folder2-open me-2"></i> Open File Manager</button>
                                            <button class="btn btn-sm btn-outline-secondary shadow-sm border-0 text-start rotate-fm-pass" data-domain="${d.domain_name}" data-user="${d.username}"><i class="bi bi-key me-2"></i> Rotate FM Key</button>
                                            <button class="btn btn-sm btn-outline-danger shadow-sm border-0 text-start enable-redis-btn" data-domain="${d.domain_name}" data-user="${d.username}"><i class="bi bi-memory me-2"></i> Inject Redis Cache</button>
                                            <button class="btn btn-sm btn-outline-dark shadow-sm border-0 text-start edit-php-settings" data-json='${JSON.stringify(d).replace(/'/g, "&apos;")}'> <i class="bi bi-sliders me-2"></i> PHP Config</button>
                                        </div>
                                    </div>
                                    <div class="col border-start border-opacity-10">
                                        <h6 class="text-muted small fw-bold text-uppercase border-bottom border-opacity-10 pb-2 mb-2"><i class="bi bi-hdd-network me-1"></i> Network & Info</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-info shadow-sm border-0 text-dark text-start show-connection-info" data-domain="${d.domain_name}"><i class="bi bi-info-circle-fill me-2"></i> Connection Info</button>
                                            <button class="btn btn-sm btn-outline-primary shadow-sm border-0 text-start open-advanced-web" data-domain="${d.domain_name}" data-hotlink="${d.hotlink_protection}"><i class="bi bi-gear-wide-connected me-2"></i> Web Settings</button>
                                            <button class="btn btn-sm btn-outline-dark shadow-sm border-0 text-start manage-proxy-btn" data-domain="${d.domain_name}" title="CDN / Proxy Settings"><i class="bi bi-shield-shaded"></i> Proxy/CDN</button>
                                            <button class="btn btn-sm btn-outline-warning shadow-sm border-0 text-start manage-ftp" data-domain="${d.domain_name}" data-user="${d.username}"><i class="bi bi-hdd-network-fill me-2"></i> FTP Accounts</button>
                                            <button class="btn btn-sm btn-outline-secondary shadow-sm border-0 text-start manage-mail" data-domain="${d.domain_name}"><i class="bi bi-envelope-at-fill me-2"></i> Mailboxes</button>
                                            <button class="btn btn-sm btn-outline-dark shadow-sm border-0 text-start view-domain-logs" data-domain="${d.domain_name}" data-user="${d.username}"><i class="bi bi-journal-code me-2"></i> Website Logs</button>
                                            <button class="btn btn-sm btn-outline-${suspendColor} shadow-sm border-0 text-start toggle-domain-status" data-domain="${d.domain_name}" data-action="${suspendAction}"><i class="bi ${suspendIcon} me-2"></i> ${suspendText} Domain</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-top border-2 border-secondary border-opacity-10 pt-3">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-2"><i class="bi bi-git me-1"></i> CI/CD Pipeline</h6>
                                    ${gitDisplay}
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
                
                container.html(allRowsHtml);
                if (typeof window.runAsyncDnsChecks === "function") window.runAsyncDnsChecks();
                
                $('#dnsDomainSelector').html(dnsDropdownOptions);
                let domainDropdowns = $('.domain-dropdown').not('#dnsDomainSelector');
                domainDropdowns.empty().append('<option value="">Select a Domain...</option>');
                response.domains.forEach(function(d) {
                    domainDropdowns.append('<option value="' + d.domain_name + '">' + d.domain_name + '</option>');
                });
            }
        }
    });
};

window.runAsyncDnsChecks = function() {
    $('.async-dns-check').each(function() {
        let badge = $(this);
        let domain = badge.data('domain');
        if (badge.hasClass('checked')) return; 

        $.ajax({
            url: '/ajax/check_dns_pointer.php',
            type: 'POST',
            data: { domain: domain },
            dataType: 'json',
            success: function(response) {
                badge.addClass('checked');
                if (response.success) {
                    if (response.pointing) {
                        badge.removeClass('bg-secondary bg-opacity-10 text-secondary').addClass('bg-success bg-opacity-10 text-success').html('<i class="bi bi-check-circle"></i> Pointing');
                    } else {
                        let titleText = response.resolved_ip ? `Pointing to: ${response.resolved_ip}` : 'No DNS Record Found';
                        badge.removeClass('bg-secondary bg-opacity-10 text-secondary').addClass('bg-danger bg-opacity-10 text-danger').attr('title', titleText).html('<i class="bi bi-x-circle"></i> Not Pointing');
                    }
                } else {
                    badge.html('<i class="bi bi-exclamation-triangle"></i> Check Failed');
                }
            },
            error: function() {
                badge.html('<i class="bi bi-exclamation-triangle"></i> Timeout');
            }
        });
    });
};

window.fetchAdvancedWebData = function(domain) {
    $.ajax({
        url: '/ajax/get_advanced_web.php',
        type: 'POST',
        data: { domain: domain },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                let rBody = $('#dynamicRedirectsTable');
                rBody.empty();
                if(res.redirects.length === 0) rBody.html('<tr><td colspan="4" class="text-center text-muted small border-0">No active redirects.</td></tr>');
                res.redirects.forEach(r => {
                    let typeBadge = r.redirect_type == 301 ? '<span class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-2 shadow-sm">301</span>' : '<span class="badge bg-secondary bg-opacity-10 text-secondary border-0 rounded-pill px-2 shadow-sm">302</span>';
                    rBody.append(`<tr>
                        <td class="font-monospace small text-dark">${r.source_path}</td>
                        <td class="font-monospace small text-truncate text-muted" style="max-width:200px;">${r.target_url}</td>
                        <td>${typeBadge}</td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-danger shadow-sm border-0 del-adv-btn py-0" data-id="${r.id}" data-action="del_redirect" title="Delete"><i class="bi bi-trash"></i></button></td>
                    </tr>`);
                });
                let mBody = $('#dynamicMimesTable');
                mBody.empty();
                if(res.mimes.length === 0) mBody.html('<tr><td colspan="3" class="text-center text-muted small border-0">No custom MIME types.</td></tr>');
                res.mimes.forEach(m => {
                    mBody.append(`<tr>
                        <td class="fw-bold text-dark">.${m.extension}</td>
                        <td class="font-monospace small text-muted">${m.mime_type}</td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-danger shadow-sm border-0 del-adv-btn py-0" data-id="${m.id}" data-action="del_mime" title="Delete"><i class="bi bi-trash"></i></button></td>
                    </tr>`);
                });
            }
        }
    });
};

window.loadPhpVersions = function() {
    $.ajax({
        url: '/ajax/get_php_versions.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.versions.length > 0) {
                let options = '';
                response.versions.forEach(function(version, index) {
                    let isSelected = (index === 0) ? 'selected' : '';
                    let defaultText = (index === 0) ? ' (Default)' : '';
                    options += `<option value="${version}" ${isSelected}>PHP ${version}${defaultText}</option>`;
                });
                $('#phpVersion, #newPhpVersion').html(options);
            } else {
                $('#phpVersion, #newPhpVersion').html('<option value="">Error: No PHP versions found</option>');
            }
        },
        error: function() {
            $('#phpVersion, #newPhpVersion').html('<option value="">Error contacting API</option>');
        }
    });
};

window.fetchInstalledPhpVersions = function() {
    $.ajax({
        url: '/ajax/get_php_versions.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let select = $('#phpVersionSelect');
                select.empty(); 
                if (response.versions.length > 0) {
                    response.versions.forEach(function(ver, index) {
                        let isSelected = (index === 0) ? 'selected' : '';
                        select.append(`<option value="${ver}" ${isSelected}>PHP ${ver} (FPM)</option>`);
                    });
                }
            }
        }
    });
};

window.fetchFtpUsers = function(domain) {
    $('#dynamicFtpTable').html('<tr><td colspan="2" class="text-center text-muted py-3 border-0"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>');
    
    $.ajax({
        url: '/ajax/get_ftp_users.php',
        type: 'POST',
        data: { domain: domain },
        dataType: 'json',
        success: function(res) {
            let tbody = $('#dynamicFtpTable');
            tbody.empty();
            if(res.success) {
                if(res.users.length === 0) {
                    tbody.html('<tr><td colspan="2" class="text-center text-muted py-3 small border-0">No FTP accounts found.</td></tr>');
                } else {
                    res.users.forEach(u => {
                        tbody.append(`
                            <tr>
                                <td class="ps-3 fw-bold small text-dark">${u.ftp_user}</td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-outline-primary py-0 shadow-sm border-0 edit-ftp-btn" data-user="${u.ftp_user}" title="Change Password"><i class="bi bi-key"></i></button>
                                    <button class="btn btn-sm btn-outline-danger py-0 shadow-sm border-0 delete-ftp-btn ms-1" data-user="${u.ftp_user}" data-domain="${domain}" title="Delete User"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        `);
                    });
                }
            } else {
                tbody.html(`<tr><td colspan="2" class="text-center text-danger py-3 small border-0">${res.error}</td></tr>`);
            }
        }
    });
};
// =================================================================
// 2. EVENT LISTENERS
// =================================================================
$(document).ready(function() {

    // === CREATE DOMAIN / SUBDOMAIN ===
    $('#isSubdomainToggle').on('change', function() {
        let isSubdomain = $(this).is(':checked');
        if (isSubdomain) {
            $('#primaryDomainGroup').addClass('d-none');
            $('#subdomainGroup').removeClass('d-none');
            $('#primaryDomainInput').removeAttr('required').val('');
            $('#subdomainPrefixInput').attr('required', true);
            $('#subdomainParentInput').attr('required', true);
        } else {
            $('#subdomainGroup').addClass('d-none');
            $('#primaryDomainGroup').removeClass('d-none');
            $('#subdomainPrefixInput').removeAttr('required').val('');
            $('#subdomainParentInput').removeAttr('required').val('');
            $('#primaryDomainInput').attr('required', true);
        }
    });

    $(document).on('click', '.btn-jump-dns', function() {
        let targetDomain = $(this).data('domain');
        $('#security-tab').tab('show');
        $('#pill-dns-tab').tab('show');
        $('#dnsDomainSelector').val(targetDomain).trigger('change');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    $('#sslRoutingForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSaveRouting');
        let domain = $('#sslTargetDomain').val();
        let originalText = btn.html();

        if(!domain) { window.showToast('warning', 'Validation', "Select a domain first."); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Applying Rules...');

        $.ajax({
            url: '/ajax/manage_https_routing.php', 
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    window.showToast('success', 'Routing Queued', "Routing rules queued for Nginx compilation!");
                    $('#overview-tab').tab('show'); 
                } else {
                    window.showToast('error', 'Routing Failed', response.error);
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                if (xhr.status === 403) {
                    window.showToast('error', 'Auth Error', "Security Token Expired. Please refresh.");
                } else {
                    window.showToast('error', 'Network Error', "Could not reach the server.");
                }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $('#generateFmPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#fmPassInput').val(pass);
        
        navigator.clipboard.writeText(pass);
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
    });

    $('#sslTargetDomain').on('change', function() {
        let domain = $(this).val();
        $('.sync-domain').val(domain);

        if(!domain) {
            $('#sslStateUnsecured, #sslStateSecured, .tab-content form').addClass('opacity-50').css('pointer-events', 'none');
            return;
        }

        $('#sslStateUnsecured, #sslStateSecured, .tab-content form').removeClass('opacity-50').css('pointer-events', 'auto');
        $('#sslStateSecured').addClass('d-none');
        $('#sslStateUnsecured').addClass('d-none');

        $.ajax({
            url: '/ajax/get_ssl_info.php',
            type: 'POST',
            data: { domain: domain },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#forceHttpsToggle').prop('checked', response.force_https);
                    $('#sslAutoRenewToggle').prop('checked', response.auto_renew); 

                    $('#hstsToggle').prop('checked', response.hsts_enabled).trigger('change');
                    if (response.hsts_enabled) {
                        $('#hstsSlider').val(response.hsts_max_age).trigger('input');
                        $('#hstsSubdomains').prop('checked', response.hsts_subdomains);
                        $('#hstsPreload').prop('checked', response.hsts_preload);
                    } else {
                        $('#hstsSlider').val(15552000).trigger('input');
                        $('#hstsSubdomains').prop('checked', false);
                        $('#hstsPreload').prop('checked', false);
                    }

                    if(response.is_secured) {
                        $('#sslIssuerDisplay').text(response.issuer);
                        $('#sslValidFrom').text(response.valid_from);
                        $('#sslValidUntil').text(response.valid_until);
                        $('#sslDaysRemainingText').text(response.days_remaining + ' Days');
                        
                        let bar = $('#sslDaysBar');
                        bar.css('width', response.percent_remaining + '%');
                        bar.removeClass('bg-success bg-warning bg-danger').addClass('bg-' + response.status_color);

                        $('#sslStateSecured').removeClass('d-none');
                    } else {
                        $('#sslStateUnsecured').removeClass('d-none');
                    }
                } else {
                    window.showToast('error', 'Status Check Failed', response.error);
                }
            }
        });
    });

    $('#issueLetsEncryptForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnIssueLe');
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Communicating...');

        $.ajax({
            url: '/ajax/install_ssl.php', 
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    window.showToast('success', 'SSL Provisioned', 'SSL Installed Successfully! Refreshing...');
                    $('#installSslModal').modal('hide');
                    setTimeout(window.fetchDomains, 1500);
                } else {
                    window.showToast('error', 'Provisioning Failed', response.error);
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() { window.showToast('error', 'Server Error', "A server error occurred."); btn.prop('disabled', false).html(originalText); }
        });
    });

    $('#hstsToggle').on('change', function() {
        if($(this).is(':checked')) {
            $('.hsts-controls').removeClass('opacity-50').css('pointer-events', 'auto');
        } else {
            $('.hsts-controls').addClass('opacity-50').css('pointer-events', 'none');
        }
    });

    $('#hstsSlider').on('input', function() {
        let seconds = $(this).val();
        let months = Math.round(seconds / 2592000); // 30 days
        let labelText = months + ' Months';
        if(months === 12) labelText = '1 Year';
        if(months === 24) labelText = '2 Years (Recommended)';
        $('#hstsDurationLabel').text(labelText);
    });

    $(document).on('change', '#dnsDomainSelector', function() {
        let selectedDomain = $(this).val();
        $('#dynamicDnsTable tr').each(function() {
            let rowDomain = $(this).find('td:eq(0)').text().trim(); 
            if (selectedDomain === 'all' || selectedDomain === '') {
                $(this).show(); 
            } else {
                if (rowDomain === selectedDomain) { $(this).show(); } 
                else { $(this).hide(); }
            }
        });
    });

    $('#addDomainForm').on('submit', function(e) {
        e.preventDefault();
        let submitBtn = $('#btnSubmitDomain');
        let originalText = submitBtn.html();
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span> Provisioning...').prop('disabled', true);

        $.ajax({
            url: '/ajax/create_domain.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addDomainModal').modal('hide');
                    $('#addDomainForm')[0].reset();
                    $('#isSubdomainToggle').prop('checked', false).trigger('change');
                    window.showToast('success', 'Domain Added', response.message);
                    setTimeout(window.fetchDomains, 1500); 
                } else {
                    window.showToast('error', 'Provisioning Failed', response.error);
                }
            },
            complete: function() { submitBtn.html(originalText).prop('disabled', false); }
        });
    });

    $(document).on('click', '.delete-domain', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        let isMasterDomain = window.location.hostname === domain;
        
        let confirmText = isMasterDomain 
            ? prompt(`CRITICAL: '${domain}' is currently securing Stackrium. Deleting this will unbind the panel. Type the domain name to proceed:`)
            : prompt(`WARNING: This will permanently destroy all files and SSL for '${domain}'. Type the domain name to proceed:`);
            
        if (confirmText !== domain) return;
        
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/delete_domain.php',
            type: 'POST',
            data: { domain: domain, username: user },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    if (isMasterDomain) {
                        window.showToast('warning', 'Master Deleted', "Master domain deleted. Reverting to IP address...");
                        setTimeout(() => { window.location.href = "https://" + (response.server_ip || window.location.hostname) + ":7443"; }, 2000);
                    } else {
                        window.showToast('success', 'Domain Deleted', "Site removed from infrastructure.");
                        setTimeout(window.fetchDomains, 1500); 
                    }
                } else {
                    window.showToast('error', 'Deletion Failed', response.error);
                    btn.prop('disabled', false).html('<i class="bi bi-trash"></i> Delete');
                }
            }
        });
    });

    $(document).on('click', '.toggle-domain-status', function() {
        let domain = $(this).data('domain');
        let action = $(this).data('action');
        let btn = $(this);
        
        let warning = action === 'suspend' 
            ? `Suspend ${domain}? All traffic will be blocked with a 503 error.` 
            : `Unsuspend ${domain} and restore traffic?`;
            
        if(!confirm(warning)) return;
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_domain_status.php',
            type: 'POST',
            data: { domain: domain, action: action },
            dataType: 'json',
            success: function(response) {
                if(response.success) { setTimeout(window.fetchDomains, 2000); } 
                else { window.showToast('error', 'Status Update Failed', response.error); btn.prop('disabled', false); }
            }
        });
    });

    $(document).on('click', '.manage-ftp', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        
        $('#ftpDomainTitle').text(domain);
        $('#ftpDomain').val(domain);
        $('#ftpSysUser').val(user);
        $('#ftpSuffix').text('@' + domain);
        
        $('#ftpForm')[0].reset();
        $('#ftpAction').val('create');
        $('#ftpUserInput').prop('readonly', false);
        $('#deleteFtpBtn').addClass('d-none');
        $('#saveFtpBtn').removeClass('w-100'); 
        
        $('#ftpModal').modal('show');
    });

    $('#generateFtpPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) { pass += chars.charAt(Math.floor(Math.random() * chars.length)); }
        $('#ftpPassInput').val(pass);
        
        navigator.clipboard.writeText(pass);
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
    });

    $('#saveFtpBtn').click(function() {
        let btn = $(this);
        let form = $('#ftpForm');
        
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        let rawUser = $('#ftpUserInput').val();
        if (!rawUser.includes('@')) { $('#ftpUserInput').val(rawUser + $('#ftpSuffix').text()); }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        $.ajax({
            url: '/ajax/manage_ftp.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#ftpModal').modal('hide');
                    window.showToast('success', 'FTP Saved', "FTP Account saved successfully.");
                } else {
                    window.showToast('error', 'FTP Error', response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save FTP Account');
            }
        });
    });

    $(document).on('click', '.deploy-laravel', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        let btn = $(this);
        let originalIcon = btn.html();
        if(!confirm(`Deploy Laravel Environment for ${domain}?`)) return;
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/deploy_laravel.php',
            type: 'POST',
            data: { domain: domain, username: user },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    window.showToast('success', 'Queued', "Laravel build queued! Switching to Live Tasks...");
                    $('#overview-tab').tab('show'); 
                    setTimeout(window.fetchDomains, 1500); 
                } else { window.showToast('error', 'Error', res.error); btn.prop('disabled', false).html(originalIcon); }
            }
        });
    });

    $(document).on('click', '.deploy-python', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        let btn = $(this);
        let originalIcon = btn.html();
        if(!confirm(`Deploy Python Environment for ${domain}?`)) return;
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/deploy_python.php',
            type: 'POST',
            data: { domain: domain, username: user },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    window.showToast('success', 'Queued', "Python build queued! Switching to Live Tasks...");
                    $('#overview-tab').tab('show'); 
                    setTimeout(window.fetchDomains, 1500); 
                } else { window.showToast('error', 'Error', res.error); btn.prop('disabled', false).html(originalIcon); }
            }
        });
    });

    $(document).on('click', '.open-node-modal', function() {
        $('#nodeDomain').val($(this).data('domain'));
        $('#nodeUser').val($(this).data('user'));
        $('#nodeJsForm')[0].reset();
        $('#nodeJsModal').modal('show');
    });

    $('#systemSettingsModal').on('show.bs.modal', function () {
        $('#masterWafToggle').prop('disabled', true);
        $.ajax({
            url: '/ajax/get_master_waf_status.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) { $('#masterWafToggle').prop('checked', response.status === 'on'); }
                $('#masterWafToggle').prop('disabled', false);
            },
            error: function() { $('#masterWafToggle').prop('disabled', false); }
        });
    });

    $(document).on('change', '#masterWafToggle', function() {
        let isChecked = $(this).is(':checked');
        let action = isChecked ? 'on' : 'off';
        let toggleBtn = $(this);
        
        let warning = isChecked 
            ? "Enabling the Master WAF will secure the panel against SQLi and XSS attacks." 
            : "WARNING: Disabling the Master WAF reduces panel security. Only do this if you are experiencing 403 blocks.";
            
        if(!confirm(warning)) { toggleBtn.prop('checked', !isChecked); return; }
        toggleBtn.prop('disabled', true); 

        $.ajax({
            url: '/ajax/toggle_master_waf.php',
            type: 'POST',
            data: { status: action },
            dataType: 'json',
            success: function(response) {
                if (response.success) { window.showToast('success', 'WAF Update', "Master WAF is now " + action.toUpperCase() + "."); } 
                else { window.showToast('error', 'WAF Update', response.error); toggleBtn.prop('checked', !isChecked); }
                toggleBtn.prop('disabled', false); 
            },
            error: function() { window.showToast('error', 'Network', 'Network Error'); toggleBtn.prop('checked', !isChecked); toggleBtn.prop('disabled', false); }
        });
    });

    $('#submitNodeJsBtn').click(function() {
        let btn = $(this);
        let form = $('#nodeJsForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Deploying via PM2...');

        $.ajax({
            url: '/ajax/deploy_node.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#nodeJsModal').modal('hide');
                    window.showToast('success', 'Queued', "Node.js Deployment Queued!");
                    $('#overview-tab').tab('show');
                } else { window.showToast('error', 'Deploy Failed', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-rocket-takeoff"></i> Launch App via PM2');
            }
        });
    });

    $('.node-action-btn').click(function() {
        let btn = $(this);
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/ajax/node_action.php',
            type: 'POST',
            data: {
                domain: $('#nodeDomain').val(),
                username: $('#nodeUser').val(),
                app_root: $('input[name="app_root"]').val(),
                sub_action: btn.data('action')
            },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#nodeJsModal').modal('hide');
                    window.showToast('success', 'Command Sent', "Node action queued.");
                    $('#overview-tab').tab('show');
                } else { window.showToast('error', 'Action Failed', res.error); }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $(document).on('click', '.open-wp-modal', function() {
        let domain = $(this).data('domain');
        $('#wpDomain').val(domain);
        $('#wpUser').val($(this).data('user'));
        $('#wpEmailInput').val('admin@' + domain); 
        $('#installWpForm')[0].reset();
        $('#wpPassInput').val('');
        $('#installWpModal').modal('show');
    });

    $('#generateWpPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 20; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#wpPassInput').val(pass);
        navigator.clipboard.writeText(pass);
        let btn = $(this);
        let orig = btn.html();
        btn.html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { btn.html(orig); }, 2000);
    });

    $('#submitInstallWpBtn').click(function() {
        let btn = $(this);
        let form = $('#installWpForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/install_wp.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#installWpModal').modal('hide');
                    window.showToast('success', 'Queued', "WordPress installation queued!");
                    $('#overview-tab').tab('show'); 
                } else { window.showToast('error', 'Install Failed', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-down"></i> Install WordPress');
            }
        });
    });

    $(document).on('click', '.revert-app', function() {
        let domain = $(this).data('domain');
        let btn = $(this);
        let orig = btn.html();
        if(!confirm(`Are you sure you want to revert ${domain} back to standard PHP?`)) return;
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_app_state.php',
            type: 'POST',
            data: { domain: domain, username: $(this).data('user'), action: 'revert' },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    window.showToast('success', 'Reverting', "Revert initiated!");
                    $('#overview-tab').tab('show'); 
                    setTimeout(window.fetchDomains, 1500); 
                } else { window.showToast('error', 'Revert Failed', res.error); btn.prop('disabled', false).html(orig); }
            }
        });
    });

    $(document).on('click', '.restart-app', function() {
        let btn = $(this);
        let orig = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_app_state.php',
            type: 'POST',
            data: { domain: $(this).data('domain'), username: $(this).data('user'), action: 'restart' },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    window.showToast('success', 'Restarting', "Engine Restart queued.");
                    setTimeout(() => { btn.prop('disabled', false).html(orig); }, 2500);
                } else { window.showToast('error', 'Restart Failed', res.error); btn.prop('disabled', false).html(orig); }
            }
        });
    });

    $('#gitForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#submitGitBtn');
        let alertBox = $('#gitAlert');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Cloning...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/clone_repo.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    alertBox.addClass('alert-success').text(res.message).removeClass('d-none');
                    $('#gitForm')[0].reset();
                } else { alertBox.addClass('alert-danger').text(res.error).removeClass('d-none'); }
            },
            complete: function() { btn.prop('disabled', false).text('Deploy Repository'); }
        });
    });

    $(document).on('click', '.manual-git-pull', function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Pulling...');
        $.ajax({
            url: '/ajax/manual_git_pull.php', 
            type: 'POST',
            data: { domain: btn.data('domain'), username: btn.data('user'), branch: btn.data('branch') },
            dataType: 'json',
            success: function(res) {
                if(res.success) { window.showToast('success', 'Git', "Git Pull Queued! Check Live Tasks."); } 
                else { window.showToast('error', 'Git Error', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-arrow-down-circle"></i> Pull Now');
            }
        });
    });

    $('#changePhpForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#submitPhpBtn');
        let alertBox = $('#phpFormAlert');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Reconfiguring...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/change_php.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) { alertBox.addClass('alert-success').text(res.message).removeClass('d-none'); } 
                else { alertBox.addClass('alert-danger').text(res.error).removeClass('d-none'); }
            },
            complete: function() { btn.prop('disabled', false).text('Update PHP Version'); }
        });
    });

    $(document).on('click', '.edit-php-settings', function() {
        let d = $(this).data('json');
        $('#phpDomainTitle').text(d.domain_name);
        $('#psDomain').val(d.domain_name);
        $('#psUser').val(d.username);
        $('#psVer').val(d.php_version);
        
        $('#ps_mem').val(d.php_memory_limit || '128M');
        $('#ps_max_exec').val(d.php_max_exec_time || 30);
        $('#ps_max_in').val(d.php_max_input_time || 60);
        $('#ps_post').val(d.php_post_max_size || '8M');
        $('#ps_up').val(d.php_upload_max_filesize || '2M');
        $('#ps_opc').val(d.php_opcache_enable || 'on');
        $('#ps_dis').val(d.php_disable_functions || 'exec,shell_exec,system,passthru,popen,proc_open');
        $('#ps_inc').val(d.php_include_path || '.:/usr/share/php');
        $('#ps_sess').val(d.php_session_save_path || `/home/${d.username}/web/${d.domain_name}/tmp`);
        $('#ps_open').val(d.php_open_basedir || '{WEBSPACEROOT}{/}{:}{TMP}{/}');
        $('#ps_mail').val(d.php_mail_params || '');
        $('#ps_err_rep').val(d.php_error_reporting || 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED');
        $('#ps_disp_err').val(d.php_display_errors || 'off');
        $('#ps_log_err').val(d.php_log_errors || 'on');
        $('#ps_fopen').val(d.php_allow_url_fopen || 'on');
        $('#ps_f_up').val(d.php_file_uploads || 'on');
        $('#ps_short').val(d.php_short_open_tag || 'off');
        
        $('#ps_pm').val(d.fpm_pm || 'dynamic');
        $('#ps_fpm_child').val(d.fpm_max_children || 12);
        $('#ps_fpm_req').val(d.fpm_max_requests || 500);
        $('#ps_fpm_start').val(d.fpm_start_servers || 3);
        $('#ps_fpm_min').val(d.fpm_min_spare_servers || 2);
        $('#ps_fpm_max').val(d.fpm_max_spare_servers || 5);

        $('#phpSettingsModal').modal('show');
    });

    $('#savePhpSettingsBtn').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Applying...');
        $.ajax({
            url: '/ajax/manage_php.php',
            type: 'POST',
            data: $('#phpSettingsForm').serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#phpSettingsModal').modal('hide');
                    window.showToast('success', 'PHP Configured', "FPM restart queued.");
                    setTimeout(window.fetchDomains, 1000); 
                } else { window.showToast('error', 'Config Failed', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save & Restart FPM');
            }
        });
    });

    $(document).on('click', '.open-advanced-web', function() {
        let domain = $(this).data('domain');
        let hotlinkActive = $(this).data('hotlink') == 1;
        $('#advWebDomainTitle').text(domain);
        $('.adv-domain-input').val(domain);
        $('#hotlinkToggle').prop('checked', hotlinkActive);
        $('#hotlinkStatusText').text(hotlinkActive ? 'Active and protecting assets.' : 'Currently disabled.');
        $('#dynamicRedirectsTable').html('<tr><td colspan="4" class="text-center text-muted small border-0">Loading...</td></tr>');
        $('#dynamicMimesTable').html('<tr><td colspan="3" class="text-center text-muted small border-0">Loading...</td></tr>');
        window.fetchAdvancedWebData(domain);
        $('#advancedWebModal').modal('show');
    });

    $('#addRedirectForm, #addMimeForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = form.find('button[type="submit"]');
        let actionStr = form.attr('id') === 'addRedirectForm' ? 'add_redirect' : 'add_mime';
        let domain = form.find('.adv-domain-input').val();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/ajax/manage_advanced_web.php',
            type: 'POST',
            data: form.serialize() + '&action=' + actionStr,
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    form[0].reset();
                    window.showToast('success', 'Applied', "Rebuilding Nginx...");
                    setTimeout(() => window.fetchAdvancedWebData(domain), 1500); 
                } else { window.showToast('error', 'Error', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-plus-lg"></i> Add');
            }
        });
    });

    $(document).on('click', '.del-adv-btn', function() {
        if(!confirm("Are you sure you want to remove this rule?")) return;
        let btn = $(this);
        let domain = $('.adv-domain-input').first().val(); 
        btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');
        $.ajax({
            url: '/ajax/manage_advanced_web.php',
            type: 'POST',
            data: { action: btn.data('action'), id: btn.data('id'), domain: domain },
            dataType: 'json',
            success: function(res) {
                if(res.success) setTimeout(() => window.fetchAdvancedWebData(domain), 1500);
            }
        });
    });

    $('#hotlinkToggle').on('change', function() {
        let isChecked = $(this).is(':checked');
        let domain = $('.adv-domain-input').first().val();
        let textEl = $('#hotlinkStatusText');
        $(this).prop('disabled', true);
        textEl.html('<span class="spinner-border spinner-border-sm text-primary"></span> Updating Engine...');

        $.ajax({
            url: '/ajax/manage_advanced_web.php',
            type: 'POST',
            data: { action: 'toggle_hotlink', domain: domain, status: isChecked },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    textEl.text(isChecked ? 'Active and protecting assets.' : 'Currently disabled.');
                    $(`.open-advanced-web[data-domain="${domain}"]`).data('hotlink', isChecked ? 1 : 0);
                } else {
                    window.showToast('error', 'Update Failed', res.error);
                    $('#hotlinkToggle').prop('checked', !isChecked); 
                }
                $('#hotlinkToggle').prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.show-connection-info', function() {
        let domain = $(this).data('domain');
        let btn = $(this);
        let originalIcon = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm text-primary"></span>');

        $.ajax({
            url: '/ajax/get_connection_info.php',
            type: 'POST',
            data: { domain: domain },
            dataType: 'json',
            success: function(res) {
                btn.html(originalIcon); 
                if(res.success) {
                    let d = res.data;
                    $('#infoDomainTitle').text(d.domain);
                    $('#infoIp').text(d.server_ip);
                    $('#infoUser').text(d.username);
                    $('#infoSsh').text(d.ssh_command);
                    $('#infoWebRoot').text(d.web_root);
                    $('#infoNginx').text(d.nginx_conf);
                    $('#infoPhpSock').text(d.php_socket);
                    $('#infoDbHost').text(d.db_host);
                    $('#connectionInfoModal').modal('show');
                } else { window.showToast('error', 'Error', res.error); }
            }
        });
    });

    $(document).on('click', '.open-fm-sso', function() {
        let domain = $(this).data('domain');
        let btn = $(this);
        let originalIcon = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/get_fm_sso.php',
            type: 'POST',
            data: { domain: domain },
            dataType: 'json',
            success: function(res) {
                if(res.success) { window.open(res.url, '_blank'); } 
                else { window.showToast('error', 'SSO Failed', res.error); }
                btn.prop('disabled', false).html(originalIcon);
            }
        });
    });

    $(document).on('click', '.deploy-fm', function() {
        $('#fmDomainTitle').text($(this).data('domain'));
        $('#fmDomain').val($(this).data('domain'));
        $('#fmUser').val($(this).data('user'));
        $('#fmVer').val($(this).data('ver'));
        $('#fmUserDisplay').val($(this).data('user')); 
        $('#fileManagerModal').modal('show');
    });

    $('#saveFmBtn').click(function() {
        let btn = $(this);
        let form = $('#fileManagerForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Deploying...');
        $.ajax({
            url: '/ajax/manage_fm.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#fileManagerModal').modal('hide');
                    form[0].reset();
                    window.showToast('success', 'Deployed', "Available at " + $('#fmDomain').val() + "/filemanager shortly.");
                } else { window.showToast('error', 'Deploy Failed', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-up"></i> Deploy TFM');
            }
        });
    });

    $(document).on('click', '.rotate-fm-pass', function() {
        $('#rotateFmDomainTitle').text($(this).data('domain'));
        $('#rotateFmDomain').val($(this).data('domain'));
        $('#rotateFmUser').val($(this).data('user'));
        $('#rotateFmPassInput').val(''); 
        $('#rotateFmPassModal').modal('show');
    });

    $('#generateRotateFmPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#rotateFmPassInput').val(pass);
        navigator.clipboard.writeText(pass);
        let orig = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(orig); }, 2000);
    });

    $('#submitRotateFmBtn').click(function() {
        let btn = $(this);
        let form = $('#rotateFmPassForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');
        $.ajax({
            url: '/ajax/rotate_fm_password.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#rotateFmPassModal').modal('hide');
                    window.showToast('success', 'Key Rotated', "File Manager password updated!");
                } else { window.showToast('error', 'Error', res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Update Key');
            }
        });
    });

    // ==========================================
    // 3. INITIALIZATION CALLS
    // ==========================================
    window.fetchDomains();
    window.loadPhpVersions();
    window.fetchInstalledPhpVersions();
    
    $('#wafSettingsModal').on('show.bs.modal', function() {
        $('#wafVersionSelect').prop('disabled', true);
        $.ajax({
            url: '/ajax/get_waf_settings.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if(res.success && res.branch) { $('#wafVersionSelect').val(res.branch); } 
                else { $('#wafVersionSelect').val('v3.3/master'); }
                $('#wafVersionSelect').prop('disabled', false);
            },
            error: function() { $('#wafVersionSelect').prop('disabled', false); }
        });
    });

    $('#wafSettingsForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#saveWafSettingsBtn');
        let alertBox = $('#wafSettingsAlert');
        let originalText = btn.html();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
        alertBox.addClass('d-none');
        
        $.ajax({
            url: '/ajax/save_waf_settings.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#wafSettingsModal').modal('hide');
                    window.showToast('success', 'Saved', "WAF Preferences Saved!");
                } else { alertBox.removeClass('d-none').text("Error: " + res.error); }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() {
                alertBox.removeClass('d-none').text("Network error saving WAF settings.");
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $(document).on('click', '.view-domain-logs', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');

        $('#logModal').modal('show');

        setTimeout(() => {
            $('#logTypeSelect').val('error'); 
            $('#logDomainGroup').slideDown('fast');
            $('#logDomainSelect').val(domain);
            $('#logUserSelect').val(user);
            $('#fetchLogBtn').trigger('click'); 
        }, 300);
    });

    $('#sslAutoRenewToggle').on('change', function() {
        let isEnabled = $(this).is(':checked');
        let domain = $('#sslTargetDomain').val();
        let toggleBtn = $(this);
        
        toggleBtn.prop('disabled', true);
        
        $.ajax({
            url: '/ajax/toggle_ssl_renewal.php',
            type: 'POST',
            data: { domain: domain, enable: isEnabled },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    window.showToast('info', 'Auto-Renew', isEnabled ? "Certbot auto-renewal ENABLED." : "Certbot auto-renewal DISABLED.");
                } else {
                    window.showToast('error', 'Error', res.error);
                    toggleBtn.prop('checked', !isEnabled); 
                }
                toggleBtn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.manage-ftp', function() {
        let domain = $(this).data('domain');
        window.fetchFtpUsers(domain);
    });

    $(document).on('click', '.edit-ftp-btn', function() {
        let fullUser = $(this).data('user');
        
        $('#ftpFormTitle').text("Update Password: " + fullUser).addClass('text-primary');
        $('#ftpAction').val('update');
        
        $('#ftpUserInput').val(fullUser).prop('readonly', true);
        $('#ftpSuffix').addClass('d-none');
        
        $('#ftpPassInput').val(''); 
        $('#cancelFtpEditBtn').removeClass('d-none');
        $('#saveFtpBtn').removeClass('w-100').html('<i class="bi bi-key"></i> Update Password');
    });

    $(document).on('click', '#cancelFtpEditBtn', function() {
        $('#ftpFormTitle').text("Create New Account").removeClass('text-primary');
        $('#ftpAction').val('create');
        $('#ftpUserInput').val('').prop('readonly', false);
        $('#ftpSuffix').removeClass('d-none');
        $('#ftpPassInput').val('');
        $(this).addClass('d-none');
        $('#saveFtpBtn').addClass('w-100').html('<i class="bi bi-save"></i> Save Account');
    });

    $(document).on('click', '.delete-ftp-btn', function() {
        let ftpUser = $(this).data('user');
        let domain = $(this).data('domain');
        let sysUser = $('#ftpSysUser').val(); 
        
        if(!confirm(`Delete FTP user ${ftpUser}? This cannot be undone.`)) return;
        
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');

        $.ajax({
            url: '/ajax/manage_ftp.php',
            type: 'POST',
            data: { action: 'delete', ftp_user: ftpUser, domain: domain, username: sysUser },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    window.showToast('success', 'Queued', "FTP Delete Task Queued!");
                    window.fetchFtpUsers(domain); 
                } else {
                    window.showToast('error', 'Error', res.error);
                    btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                }
            }
        });
    });

    $(document).on('click', '.manage-proxy-btn', function() {
        let domain = $(this).data('domain');
        $('#proxyDomainTitle').text(domain);
        $('#proxyDomainInput').val(domain);
        
        $('#proxyTypeSelect').val('direct').trigger('change');
        $('#proxyCustomIps').val('');
        $('#proxyCustomHeader').val('X-Forwarded-For');
        
        $('#proxyModal').modal('show');
    });

    $('#proxyTypeSelect').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customProxySettings').removeClass('d-none').hide().slideDown('fast');
            $('#proxyCustomIps').prop('required', true);
            $('#proxyCustomHeader').prop('required', true);
        } else {
            $('#customProxySettings').slideUp('fast', function() { $(this).addClass('d-none'); });
            $('#proxyCustomIps').prop('required', false);
            $('#proxyCustomHeader').prop('required', false);
        }
    });

    $('#proxyForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#saveProxyBtn');
        let originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Applying Rules...');

        $.ajax({
            url: '/ajax/manage_proxy.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#proxyModal').modal('hide');
                    window.showToast('success', 'Applied', res.message); 
                    $('#overview-tab').tab('show');
                } else { window.showToast('error', 'Error', res.error); }
                btn.prop('disabled', false).html(originalHtml);
            },
            error: function() {
                window.showToast('error', 'Network Error', "Communication failed.");
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});