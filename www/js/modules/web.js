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
                    container.html('<div class="text-center text-muted py-5">No domains configured.</div>');
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
                            <button class="btn btn-sm btn-outline-danger text-start deploy-laravel" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-box-seam me-2"></i> Deploy Laravel
                            </button>
                            <button class="btn btn-sm btn-outline-warning text-dark text-start deploy-python" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-filetype-py me-2"></i> Deploy Python
                            </button>
                            <button class="btn btn-sm btn-outline-success text-start open-node-modal" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-hexagon-fill me-2"></i> Deploy Node.js
                            </button>
                            <button class="btn btn-sm btn-outline-primary text-start open-wp-modal" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-wordpress me-2"></i> WordPress
                            </button>
                        `;
                    } else {
                        let appLabel = d.app_type.charAt(0).toUpperCase() + d.app_type.slice(1);
                        let color = d.app_type === 'laravel' ? 'danger' : 'warning text-dark';
                        let restartBtn = '';
                        if (d.app_type === 'python' || d.app_type === 'node') {
                            restartBtn = `
                            <button class="btn btn-sm btn-outline-dark text-start restart-app" data-domain="${d.domain_name}" data-user="${d.username}">
                                <i class="bi bi-arrow-clockwise me-2"></i> Restart Engine
                            </button>`;
                        }
                        appActions = `
                            <button class="btn btn-sm btn-${color} text-start disabled">
                                <i class="bi bi-cpu-fill me-2"></i> ${appLabel} Active
                            </button>
                            ${restartBtn}
                            <button class="btn btn-sm btn-outline-secondary text-start revert-app" data-domain="${d.domain_name}" data-user="${d.username}" data-type="${d.app_type}">
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
                                        <div class="p-2 border rounded bg-white h-100 shadow-sm d-flex flex-column">
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge bg-success bg-opacity-10 text-success me-2 border border-success p-1"><i class="bi bi-check-lg"></i></span>
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
                                    <div class="input-group input-group-sm shadow-sm">
                                        <span class="input-group-text bg-light px-2"><i class="bi bi-lightning-charge-fill text-warning"></i> Hook</span>
                                        <input type="text" class="form-control font-monospace text-muted" style="font-size: 0.70rem;" value="${webhookUrl}" readonly onclick="this.select(); document.execCommand('copy'); showToast('Webhook URL copied!');" title="Click to copy Webhook URL">
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-dark manual-git-pull shadow-sm" data-domain="${d.domain_name}" data-user="${d.username}" data-branch="${currentBranch}">
                                        <i class="bi bi-arrow-down-circle me-1"></i> Pull Latest Code
                                    </button>
                                </div>
                            </div>
                            ${commitsHtml}
                        `;
                    }

                    allRowsHtml += `
                    <div class="accordion-item mb-3 border shadow-sm rounded">
                        <div class="d-flex align-items-stretch border-bottom bg-white rounded-top">
                            <h2 class="accordion-header flex-grow-1 m-0">
                                <button class="accordion-button collapsed py-2 rounded-start border-0 shadow-none bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#acc-${d.id}">
                                    <div class="d-flex align-items-center w-100">
                                        <div class="me-2"><i class="bi bi-globe fs-4 text-primary"></i></div>
                                        <div class="lh-sm">
                                            <span class="fw-bold text-dark fs-6">${d.domain_name}</span>
                                            ${isSuspended ? '<span class="badge bg-danger ms-1" style="font-size:0.65rem;">Suspended</span>' : ''}
                                            <span class="badge bg-secondary ms-3 async-dns-check" data-domain="${d.domain_name}">
                                                <i class="spinner-border spinner-border-sm" style="width: 10px; height: 10px;"></i> Checking...
                                            </span>
                                            <span class="text-muted small ms-2" style="font-size:0.75rem;">(User: ${d.username} | PHP ${d.php_version})</span>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div class="d-flex align-items-center px-3 border-start bg-light rounded-end">
                                <a href="${proto}://${d.domain_name}" target="_blank" onclick="event.stopPropagation()" class="btn btn-sm btn-light border shadow-sm me-2 py-1 px-2" title="Visit Site">
                                    <i class="bi bi-box-arrow-up-right text-primary me-1"></i> Visit
                                </a>
                                <button class="btn btn-sm btn-outline-primary btn-jump-dns" data-domain="${d.domain_name}"><i class="bi bi-globe"></i> Manage DNS</button>
                                <button class="btn btn-sm btn-danger shadow-sm delete-domain ms-2 py-1 px-2" data-domain="${d.domain_name}" data-user="${d.username}" title="Delete Domain">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <div id="acc-${d.id}" class="accordion-collapse collapse" data-bs-parent="#dynamicDomainsAccordion">
                            <div class="accordion-body bg-light p-3">
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 mb-4">
                                    <div class="col">
                                        <h6 class="text-muted small fw-bold text-uppercase border-bottom pb-2 mb-2"><i class="bi bi-cpu me-1"></i> App Engines</h6>
                                        <div class="d-grid gap-2">${appActions}</div>
                                    </div>
                                    <div class="col border-start">
                                        <h6 class="text-muted small fw-bold text-uppercase border-bottom pb-2 mb-2"><i class="bi bi-shield-check me-1"></i> Security</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-sm btn-${wafColor} text-start toggle-waf" data-domain="${d.domain_name}" data-action="${d.waf_enabled == 1 ? 'off' : 'on'}"><i class="bi ${wafIcon} me-2"></i> ${wafText}</button>
                                            <button class="btn btn-sm btn-outline-dark text-start edit-waf-rules" data-domain="${d.domain_name}" data-rules="${btoa(d.waf_custom_rules || '')}"><i class="bi bi-shield-lock me-2"></i> WAF Rules</button>
                                            <button class="btn btn-sm btn-outline-success text-start" data-bs-toggle="modal" data-bs-target="#installSslModal" onclick="$('#sslTargetDomain').val('${d.domain_name}').trigger('change');"><i class="bi bi-shield-lock-fill me-2"></i> Install SSL</button>
                                        </div>
                                    </div>
                                    <div class="col border-start">
                                        <h6 class="text-muted small fw-bold text-uppercase border-bottom pb-2 mb-2"><i class="bi bi-folder2-open me-1"></i> Files & Cache</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-primary text-start open-fm-sso" data-domain="${d.domain_name}"><i class="bi bi-folder2-open me-2"></i> Open File Manager</button>
                                            <button class="btn btn-sm btn-outline-warning text-dark text-start deploy-fm" data-domain="${d.domain_name}" data-user="${d.username}" data-ver="${d.php_version}"><i class="bi bi-cloud-arrow-up-fill me-2"></i> Deploy File Manager</button>
                                            <button class="btn btn-sm btn-outline-secondary text-start rotate-fm-pass" data-domain="${d.domain_name}" data-user="${d.username}"><i class="bi bi-key me-2"></i> Rotate FM Key</button>
                                            <button class="btn btn-sm btn-outline-danger text-start enable-redis-btn" data-domain="${d.domain_name}" data-user="${d.username}"><i class="bi bi-memory me-2"></i> Inject Redis Cache</button>
                                            <button class="btn btn-sm btn-outline-dark text-start edit-php-settings" data-json='${JSON.stringify(d).replace(/'/g, "&apos;")}'> <i class="bi bi-sliders me-2"></i> PHP Config</button>
                                        </div>
                                    </div>
                                    <div class="col border-start">
                                        <h6 class="text-muted small fw-bold text-uppercase border-bottom pb-2 mb-2"><i class="bi bi-hdd-network me-1"></i> Network & Info</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-info text-dark text-start show-connection-info" data-domain="${d.domain_name}"><i class="bi bi-info-circle-fill me-2"></i> Connection Info</button>
                                            <button class="btn btn-sm btn-outline-primary text-start open-advanced-web" data-domain="${d.domain_name}" data-hotlink="${d.hotlink_protection}"><i class="bi bi-gear-wide-connected me-2"></i> Web Settings</button>
                                            <button class="btn btn-sm btn-outline-secondary text-start manage-ftp" data-domain="${d.domain_name}" data-user="${d.username}"><i class="bi bi-hdd-network-fill me-2"></i> FTP Accounts</button>
                                            <button class="btn btn-sm btn-outline-secondary text-start manage-mail" data-domain="${d.domain_name}"><i class="bi bi-envelope-at-fill me-2"></i> Mailboxes</button>
                                            <button class="btn btn-sm btn-${suspendColor} text-start toggle-domain-status" data-domain="${d.domain_name}" data-action="${suspendAction}"><i class="bi ${suspendIcon} me-2"></i> ${suspendText} Domain</button>
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
                        badge.removeClass('bg-secondary text-dark').addClass('bg-success text-white').html('<i class="bi bi-check-circle"></i> Pointing');
                    } else {
                        let titleText = response.resolved_ip ? `Pointing to: ${response.resolved_ip}` : 'No DNS Record Found';
                        badge.removeClass('bg-secondary text-dark').addClass('bg-danger text-white').attr('title', titleText).html('<i class="bi bi-x-circle"></i> Not Pointing');
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
                if(res.redirects.length === 0) rBody.html('<tr><td colspan="4" class="text-center text-muted small">No active redirects.</td></tr>');
                res.redirects.forEach(r => {
                    let typeBadge = r.redirect_type == 301 ? '<span class="badge bg-primary">301</span>' : '<span class="badge bg-secondary">302</span>';
                    rBody.append(`<tr>
                        <td class="font-monospace small">${r.source_path}</td>
                        <td class="font-monospace small text-truncate" style="max-width:200px;">${r.target_url}</td>
                        <td>${typeBadge}</td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-danger del-adv-btn py-0" data-id="${r.id}" data-action="del_redirect" title="Delete"><i class="bi bi-trash"></i></button></td>
                    </tr>`);
                });
                let mBody = $('#dynamicMimesTable');
                mBody.empty();
                if(res.mimes.length === 0) mBody.html('<tr><td colspan="3" class="text-center text-muted small">No custom MIME types.</td></tr>');
                res.mimes.forEach(m => {
                    mBody.append(`<tr>
                        <td class="fw-bold">.${m.extension}</td>
                        <td class="font-monospace small text-muted">${m.mime_type}</td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-danger del-adv-btn py-0" data-id="${m.id}" data-action="del_mime" title="Delete"><i class="bi bi-trash"></i></button></td>
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
                        // Mark the highest version as the default selected option
                        let isSelected = (index === 0) ? 'selected' : '';
                        let defaultText = (index === 0) ? ' (Default)' : '';
                        options += `<option value="${version}" ${isSelected}>PHP ${version}${defaultText}</option>`;
                    });
                    
                    // Inject into both the Add Domain and Change PHP modals instantly
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

    // ==========================================
    // 2. Hybrid Tab Jumping Logic
    // ==========================================
    $(document).on('click', '.btn-jump-dns', function() {
        let targetDomain = $(this).data('domain');
        
        // 1. Switch to the Security Tab natively
        $('#security-tab').tab('show');
        
        // 2. Switch to the Inner DNS Pill
        $('#pill-dns-tab').tab('show');
        
        // 3. Set the dropdown to the target domain and trigger a change event
        // (Assuming you have a change event listener on #dnsDomainSelector to fetch records)
        $('#dnsDomainSelector').val(targetDomain).trigger('change');
        
        // Optional: Scroll to the top to ensure they see the table
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    // 4. Save Advanced Routing (HSTS/Force HTTPS)
    $('#sslRoutingForm').on('submit', function(e) {
        e.preventDefault();
        
        let btn = $('#btnSaveRouting');
        let domain = $('#sslTargetDomain').val();
        let originalText = btn.html();

        if(!domain) { alert("Select a domain first."); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Applying Rules...');

        $.ajax({
            url: '/ajax/manage_https_routing.php', // We will build this next!
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert("Routing rules applied successfully!");
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // === Auto-Generate File Manager Password ===
    $('#generateFmPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#fmPassInput').val(pass);
        
        // Auto-copy to clipboard
        navigator.clipboard.writeText(pass);
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
    });

    // Install SSL Form Submission
    // === NEW MODULAR SSL CONTROLLER ===
    // 1. When the master domain dropdown changes, fetch the SSL status
    $('#sslTargetDomain').on('change', function() {
        let domain = $(this).val();
        
        // Sync the hidden inputs in all 3 forms so they know which domain to target
        $('.sync-domain').val(domain);

        if(!domain) {
            // Hide everything if no domain is selected
            $('#sslStateUnsecured, #sslStateSecured, .tab-content form').addClass('opacity-50').css('pointer-events', 'none');
            return;
        }

        // Enable forms
        $('#sslStateUnsecured, #sslStateSecured, .tab-content form').removeClass('opacity-50').css('pointer-events', 'auto');

        // Show a loading state briefly
        $('#sslStateSecured').addClass('d-none');
        $('#sslStateUnsecured').addClass('d-none');

        $.ajax({
            url: '/ajax/get_ssl_info.php',
            type: 'POST',
            data: { domain: domain },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    if(response.is_secured) {
                        // Populate Telemetry Data
                        $('#sslIssuerDisplay').text(response.issuer);
                        $('#sslValidFrom').text(response.valid_from);
                        $('#sslValidUntil').text(response.valid_until);
                        $('#sslDaysRemainingText').text(response.days_remaining + ' Days');
                        
                        // Update Progress Bar
                        let bar = $('#sslDaysBar');
                        bar.css('width', response.percent_remaining + '%');
                        bar.removeClass('bg-success bg-warning bg-danger').addClass('bg-' + response.status_color);

                        // Show Secured View
                        $('#sslStateSecured').removeClass('d-none');
                    } else {
                        // Show Unsecured (Issue) View
                        $('#sslStateUnsecured').removeClass('d-none');
                    }
                } else {
                    alert("Error checking SSL status: " + response.error);
                }
            }
        });
    });

    // ==========================================
    // DNS Table Filtering Logic
    // ==========================================
    $(document).on('change', '#dnsDomainSelector', function() {
        let selectedDomain = $(this).val();
        
        // Loop through every row in the DNS table
        $('#dynamicDnsTable tr').each(function() {
            // Assuming the Domain name is in the first column (td:eq(0))
            // Adjust 'td:eq(0)' to 'td:eq(1)' if your domain name is in the second column
            let rowDomain = $(this).find('td:eq(0)').text().trim(); 
            
            if (selectedDomain === 'all' || selectedDomain === '') {
                $(this).show(); // Show all if "All Domains" is selected
            } else {
                if (rowDomain === selectedDomain) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
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
                    showToast(response.message);
                    setTimeout(window.fetchDomains, 1500); 
                } else {
                    alert("Error: " + response.error);
                }
            },
            complete: function() { submitBtn.html(originalText).prop('disabled', false); }
        });
    });

    // === DOMAIN LIFECYCLE (Delete & Suspend) ===
    $(document).on('click', '.delete-domain', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        let isMasterDomain = window.location.hostname === domain;
        
        let confirmText = isMasterDomain 
            ? prompt(`CRITICAL: '${domain}' is currently securing oPanel. Deleting this will unbind the panel. Type the domain name to proceed:`)
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
                        alert("Master domain deleted. Reverting to IP address...");
                        window.location.href = "https://" + (response.server_ip || window.location.hostname) + ":7443";
                    } else {
                        setTimeout(window.fetchDomains, 3000); 
                    }
                } else {
                    alert("Error: " + response.error);
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
                else { alert("Error: " + response.error); btn.prop('disabled', false); }
            }
        });
    });

    // === APP DEPLOYMENTS (Laravel, Node, Python, WP) ===
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
                    showToast("Laravel build queued! Switching to Live Tasks...");
                    $('#overview-tab').tab('show'); 
                    setTimeout(window.fetchDomains, 1500); 
                } else { alert("Error: " + res.error); btn.prop('disabled', false).html(originalIcon); }
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
                    showToast("Python build queued! Switching to Live Tasks...");
                    $('#overview-tab').tab('show'); 
                    setTimeout(window.fetchDomains, 1500); 
                } else { alert("Error: " + res.error); btn.prop('disabled', false).html(originalIcon); }
            }
        });
    });

    $(document).on('click', '.open-node-modal', function() {
        $('#nodeDomain').val($(this).data('domain'));
        $('#nodeUser').val($(this).data('user'));
        $('#nodeJsForm')[0].reset();
        $('#nodeJsModal').modal('show');
    });

    // =================================================================
    // MASTER WAF TOGGLE CONTROLLER & SYNC
    // =================================================================

    // 1. Sync state when the System Settings modal opens
    $('#systemSettingsModal').on('show.bs.modal', function () {
        $('#masterWafToggle').prop('disabled', true);
        //console.log("[WAF] Checking live Nginx configuration...");

        $.ajax({
            url: '/ajax/get_master_waf_status.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                //console.log("[WAF] Server Status:", response);
                if (response.success) {
                    // Update the toggle to match the live server state
                    $('#masterWafToggle').prop('checked', response.status === 'on');
                }
                $('#masterWafToggle').prop('disabled', false);
            },
            error: function() {
                //console.error("[WAF] Failed to check status.");
                $('#masterWafToggle').prop('disabled', false);
            }
        });
    });

    // 2. Handle the toggle switch click
    $(document).on('change', '#masterWafToggle', function() {
        //console.log("[WAF] Toggle clicked!");

        let isChecked = $(this).is(':checked');
        let action = isChecked ? 'on' : 'off';
        let toggleBtn = $(this);
        
        let warning = isChecked 
            ? "Enabling the Master WAF will secure the panel against SQLi and XSS attacks." 
            : "WARNING: Disabling the Master WAF reduces panel security. Only do this if you are experiencing 403 blocks.";
            
        // Show confirmation dialogue
        if(!confirm(warning)) {
            //console.log("[WAF] User cancelled action.");
            toggleBtn.prop('checked', !isChecked); // Revert UI
            return;
        }

        //console.log("[WAF] Firing AJAX to toggle WAF:", action);
        toggleBtn.prop('disabled', true); 

        $.ajax({
            url: '/ajax/toggle_master_waf.php',
            type: 'POST',
            data: { status: action },
            dataType: 'json',
            success: function(response) {
                //console.log("[WAF] Toggle Response:", response);
                if (response.success) {
                    showToast("Master WAF is now " + action.toUpperCase() + ".");
                } else {
                    alert("Error: " + response.error);
                    toggleBtn.prop('checked', !isChecked); // Revert UI on error
                }
                toggleBtn.prop('disabled', false); 
            },
            error: function(xhr, status, error) {
                //console.error("[WAF] AJAX Error:", xhr.responseText);
                alert("Network Error. Check browser console.");
                toggleBtn.prop('checked', !isChecked); // Revert UI on error
                toggleBtn.prop('disabled', false);
            }
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
                    alert("Node.js Deployment Queued!");
                    $('#overview-tab').tab('show');
                } else { alert("Error: " + res.error); }
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
                    alert("Command Sent!");
                    $('#overview-tab').tab('show');
                } else { alert("Error: " + res.error); }
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
                    alert("WordPress installation queued!");
                    $('#overview-tab').tab('show'); 
                } else { alert("Error: " + res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-down"></i> Install WordPress');
            }
        });
    });

    // === LIFECYCLE (Revert & Restart) ===
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
                    showToast("Revert initiated!");
                    $('#overview-tab').tab('show'); 
                    setTimeout(window.fetchDomains, 1500); 
                } else { alert("Error: " + res.error); btn.prop('disabled', false).html(orig); }
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
                    showToast("Engine Restart queued.");
                    setTimeout(() => { btn.prop('disabled', false).html(orig); }, 2500);
                } else { alert("Error: " + res.error); btn.prop('disabled', false).html(orig); }
            }
        });
    });

    // === GIT DEPLOYMENTS ===
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
                if(res.success) { alert("Git Pull Queued! Check Live Tasks."); } 
                else { alert("Error: " + res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-arrow-down-circle"></i> Pull Now');
            }
        });
    });

    // === PHP SETTINGS & VERSIONS ===
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
                    setTimeout(window.fetchDomains, 1000); 
                } else { alert("Error: " + res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save & Restart FPM');
            }
        });
    });

    // === ADVANCED WEB (Redirects, MIME, Hotlink) ===
    $(document).on('click', '.open-advanced-web', function() {
        let domain = $(this).data('domain');
        let hotlinkActive = $(this).data('hotlink') == 1;
        $('#advWebDomainTitle').text(domain);
        $('.adv-domain-input').val(domain);
        $('#hotlinkToggle').prop('checked', hotlinkActive);
        $('#hotlinkStatusText').text(hotlinkActive ? 'Active and protecting assets.' : 'Currently disabled.');
        $('#dynamicRedirectsTable').html('<tr><td colspan="4" class="text-center text-muted small">Loading...</td></tr>');
        $('#dynamicMimesTable').html('<tr><td colspan="3" class="text-center text-muted small">Loading...</td></tr>');
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
                    showToast("Applied! Rebuilding Nginx...");
                    setTimeout(() => window.fetchAdvancedWebData(domain), 1500); 
                } else { alert("Error: " + res.error); }
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
                    alert("Error: " + res.error);
                    $('#hotlinkToggle').prop('checked', !isChecked); 
                }
                $('#hotlinkToggle').prop('disabled', false);
            }
        });
    });

    // === CONNECTION INFO ===
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
                } else { alert("Error: " + res.error); }
            }
        });
    });

    // === FILE MANAGER LOGIC ===
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
                else { alert("Error: " + res.error); }
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
                    alert("Deployment Queued. It will be available at " + $('#fmDomain').val() + "/filemanager shortly.");
                } else { alert("Error: " + res.error); }
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

    $('#generateFmPass, #generateRotateFmPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        let target = $(this).attr('id') === 'generateFmPass' ? '#fmPassInput' : '#rotateFmPassInput';
        $(target).val(pass);
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
                    alert("File Manager password updated!");
                } else { alert("Error: " + res.error); }
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
});