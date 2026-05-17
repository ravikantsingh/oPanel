// /opt/panel/www/js/modules/system.js

// =================================================================
// 1. GLOBAL FUNCTIONS & STATE (Attached to Window)
// =================================================================
window.currentTaskPage = 1;
window.taskLimit = 5;
window.logInterval = null;

// The Human-Readable Task Translation Dictionary
const taskDictionary = {
    // === Core Web & App Deployment ===
    'create_vhost': { icon: 'bi-globe text-success', title: 'Provision Domain', desc: 'Setting up Nginx & PHP environment' },
    'delete_domain': { icon: 'bi-trash text-danger', title: 'Destroy Domain', desc: 'Removing web files & configurations' },
    'domain_status': { icon: 'bi-pause-circle text-warning', title: 'Toggle Domain Status', desc: 'Suspending or unsuspending website' },
    'install_wp': { icon: 'bi-wordpress text-primary', title: 'Install WordPress', desc: 'Deploying CMS and Database' },
    'deploy_laravel': { icon: 'bi-box-seam text-danger', title: 'Deploy Laravel', desc: 'Building composer framework' },
    'deploy_python': { icon: 'bi-filetype-py text-info', title: 'Deploy Python', desc: 'Setting up WSGI/Gunicorn environment' },
    'deploy_node': { icon: 'bi-hexagon-fill text-success', title: 'Deploy Node.js', desc: 'Configuring PM2 process manager' },
    'node_action': { icon: 'bi-cpu text-warning', title: 'App Process Action', desc: 'Executing PM2 command' },
    'restart_app': { icon: 'bi-bootstrap-reboot text-success', title: 'Restart Application', desc: 'Reloading backend PM2 process' },
    'revert_to_php': { icon: 'bi-arrow-return-left text-primary', title: 'Revert App to PHP', desc: 'Restoring standard FastCGI pass' },
    
    // === SSL, Routing & Nginx Configuration ===
    'install_ssl': { icon: 'bi-lock-fill text-success', title: 'Install SSL Certificate', desc: 'Provisioning Let\'s Encrypt SSL' },
    'https_routing_manager': { icon: 'bi-sign-turn-right text-success', title: 'HTTPS Routing', desc: 'Applying Force HTTPS & HSTS rules' },
    'adv_web_compile': { icon: 'bi-code-square text-dark', title: 'Advanced Web Rules', desc: 'Compiling custom Nginx directives' },
    'manage_php': { icon: 'bi-sliders text-info', title: 'Reconfigure PHP', desc: 'Applying custom FPM settings' },
    'install_php': { icon: 'bi-filetype-php text-primary', title: 'Install PHP Engine', desc: 'Compiling specific PHP version' },

    // === Database Management ===
    'create_db': { icon: 'bi-database-add text-primary', title: 'Provision Database', desc: 'Creating MariaDB instance' },
    'manage_db': { icon: 'bi-database-gear text-info', title: 'Manage Database', desc: 'Updating DB users and privileges' },
    'delete_db': { icon: 'bi-database-dash text-danger', title: 'Delete Database', desc: 'Dropping MariaDB instance' },
    'wp_redis_manager': { icon: 'bi-lightning-charge text-danger', title: 'WordPress Redis', desc: 'Configuring object caching connection' },

    // === Security & Firewall ===
    'update_waf': { icon: 'bi-shield-check text-primary', title: 'WAF Security Update', desc: 'Rebuilding Nginx security rules' },
    'manage_firewall': { icon: 'bi-bricks text-danger', title: 'Modify Firewall', desc: 'Updating UFW port rules' },
    'manage_fail2ban': { icon: 'bi-shield-slash text-danger', title: 'Manage Fail2ban', desc: 'Updating intrusion prevention jails' },
    'secure_panel': { icon: 'bi-shield-lock text-success', title: 'Secure Dashboard', desc: 'Applying Let\'s Encrypt to panel' },
    
    // === Git & File Management ===
    'git_clone': { icon: 'bi-git text-primary', title: 'Clone Repository', desc: 'Pulling source code from remote Git' },
    'git_pull': { icon: 'bi-git text-dark', title: 'Git Pull', desc: 'Pulling latest repository code' },
    'generate_ssh_key': { icon: 'bi-key text-success', title: 'Generate SSH Key', desc: 'Creating ed25519 deployment key' },
    'manage_fm': { icon: 'bi-folder2-open text-warning', title: 'Deploy File Manager', desc: 'Provisioning TinyFM application' },
    'rotate_fm': { icon: 'bi-key text-secondary', title: 'Rotate FM Password', desc: 'Updating File Manager credentials' },
    'manage_ftp': { icon: 'bi-folder-symlink text-warning', title: 'Manage FTP Account', desc: 'Updating Pure-FTPd credentials' },

    // === Mail Server Operations ===
    'install_mail_engine': { icon: 'bi-envelope-plus text-success', title: 'Install Mail Engine', desc: 'Provisioning Postfix & Dovecot' },
    'uninstall_mail_engine': { icon: 'bi-envelope-x text-danger', title: 'Uninstall Mail Engine', desc: 'Removing mail server components' },
    'manage_mail_dns': { icon: 'bi-envelope-paper text-primary', title: 'Mail DNS Routing', desc: 'Updating MX and SPF records' },
    'manage_mail_user': { icon: 'bi-person-badge text-info', title: 'Manage Mailbox', desc: 'Updating Postfix/Dovecot accounts' },

    // === DNS & System Level Tasks ===
    'create_dns': { icon: 'bi-diagram-2 text-info', title: 'Create DNS Zone', desc: 'Generating BIND9 master zone' },
    'manage_dns_record': { icon: 'bi-card-list text-secondary', title: 'Manage DNS Record', desc: 'Updating BIND9 zone file' },
    'create_user': { icon: 'bi-person-plus text-primary', title: 'Create System User', desc: 'Provisioning isolated Linux user environment' },
    'delete_user': { icon: 'bi-person-x text-danger', title: 'Delete System User', desc: 'Removing user and associated data' },
    'manage_cron': { icon: 'bi-clock-history text-primary', title: 'Manage Cron Job', desc: 'Updating scheduled tasks' },
    'update_limits': { icon: 'bi-speedometer2 text-danger', title: 'Update Quotas', desc: 'Applying storage and resource limits' },
    'set_timezone': { icon: 'bi-globe2 text-info', title: 'Set Timezone', desc: 'Updating system clock' },
    'manage_service': { icon: 'bi-arrow-clockwise text-warning', title: 'Manage Service', desc: 'Restarting system daemon' },

    // === Backups & Restoration ===
    'manage_backup': { icon: 'bi-archive text-primary', title: 'Generate Backup', desc: 'Archiving system data to vault' },
    'restore_backup': { icon: 'bi-arrow-counterclockwise text-warning', title: 'Restore Backup', desc: 'Overwriting live data from vault' },
    'delete_backup': { icon: 'bi-archive-x text-danger', title: 'Delete Backup', desc: 'Removing archive from vault' },

    // === The Fallback ===
    'default': { icon: 'bi-gear text-secondary', title: 'System Task', desc: 'Executing backend process' }
};

// Helper to make dates look like "Today, 3:32 PM"
function formatTaskTime(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    const timeStr = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    
    if (date.toDateString() === today.toDateString()) return `Today, ${timeStr}`;
    if (date.toDateString() === yesterday.toDateString()) return `Yesterday, ${timeStr}`;
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + `, ${timeStr}`;
}

window.fetchSystemStats = function() {
    $.ajax({
        url: '/ajax/system_stats.php',
        type: 'POST',
        dataType: 'json',
        success: function(data) {
            let cpuVisualPercent = (data.cpu_load / 2.0) * 100;
            if(cpuVisualPercent > 100) cpuVisualPercent = 100;
            
            $('#cpuBar').css('width', cpuVisualPercent + '%');
            $('#cpuText').text(data.cpu_load);

            $('#ramBar').css('width', data.ram_percent + '%');
            $('#ramText').text(data.ram_used + ' / ' + data.ram_total + ' MB (' + data.ram_percent + '%)');

            $('#diskBar').css('width', data.disk_percent + '%');
            $('#diskText').text(data.disk_used + ' / ' + data.disk_total + ' GB (' + data.disk_percent + '%)');

            if(data.ram_percent > 85) { $('#ramBar').removeClass('bg-info').addClass('bg-danger'); } 
            else { $('#ramBar').removeClass('bg-danger').addClass('bg-info'); }

            if(data.disk_percent > 90) { $('#diskBar').removeClass('bg-warning').addClass('bg-danger'); } 
            else { $('#diskBar').removeClass('bg-danger').addClass('bg-warning'); }
        }
    });
};

window.fetchRecentTasks = function() {
    $.ajax({
        url: '/ajax/get_tasks.php',
        type: 'POST',
        data: { page: window.currentTaskPage, limit: window.taskLimit },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let container = $('#dynamicTasksTable');
                container.empty(); 
                
                if(response.tasks.length === 0) {
                    container.html('<div class="list-group-item text-center text-muted py-5 border-0">No system tasks found.</div>');
                    $('#taskPaginationContainer').empty();
                    return;
                }

                response.tasks.forEach(function(task) {
                    let map = taskDictionary[task.action] || taskDictionary['default'];
                    // Fallback to raw action name if not in dictionary
                    let displayTitle = (map === taskDictionary['default']) ? task.action : map.title;
                    
                    let statusBadge = '';
                    let btnClass = 'btn-outline-secondary';
                    let btnText = 'View Log';
                    
                    if(task.status === 'completed') {
                        statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>';
                        btnClass = 'btn-outline-success';
                    } else if(task.status === 'failed') {
                        statusBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-x-circle-fill me-1"></i>Failed</span>';
                        btnClass = 'btn-outline-danger';
                        btnText = 'View Error';
                    } else {
                        statusBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><i class="spinner-border spinner-border-sm me-1" style="width:10px;height:10px;"></i>Running</span>';
                        btnClass = 'btn-outline-warning disabled';
                        btnText = 'Processing...';
                    }
                    // Build the new Flexbox Card row (Thinner version)
                    let row = `
                        <div class="list-group-item py-2 px-3 border-0 border-bottom bg-white">
                            <div class="row align-items-center">
                                
                                <div class="col-12 col-md-5 d-flex align-items-center mb-1 mb-md-0">
                                    <div class="me-3 fs-5 bg-light rounded border shadow-sm d-flex justify-content-center align-items-center" style="width: 36px; height: 36px;">
                                        <i class="bi ${map.icon}"></i>
                                    </div>
                                    <div class="lh-sm">
                                        <h6 class="mb-0 fw-bold text-dark" style="font-size:0.85rem;">${displayTitle} <span class="text-muted ms-1 fw-normal" style="font-size: 0.65rem;">#${task.id}</span></h6>
                                        <small class="text-muted" style="font-size:0.7rem;">${map.desc}</small>
                                    </div>
                                </div>
                                
                                <div class="col-6 col-md-4 text-start text-md-center border-start-md">
                                    <div class="fw-bold text-dark small lh-sm mb-1" style="font-size:0.8rem;"><i class="bi bi-hdd-network text-muted me-1"></i>${task.target_name}</div>
                                    <div style="font-size:0.75rem;">${statusBadge}</div>
                                </div>
                                
                                <div class="col-6 col-md-3 text-end">
                                    <div class="text-muted mb-1" style="font-size:0.7rem;">
                                        <i class="bi bi-calendar-event me-1"></i>${formatTaskTime(task.created_at)}
                                    </div>
                                    <button class="btn btn-sm ${btnClass} view-task-log shadow-sm py-1 px-2" data-id="${task.id}" style="font-size:0.75rem;">
                                        <i class="bi bi-terminal"></i> ${btnText}
                                    </button>
                                </div>
                                
                            </div>
                        </div>
                    `;
                    container.append(row);
                });

                window.renderTaskPagination(response.pagination);
            }
        }
    });
};

window.renderTaskPagination = function(p) {
    let container = $('#taskPaginationContainer');
    if (container.length === 0) {
        // Attach to our new .tasks-wrapper div
        $('#dynamicTasksTable').closest('.tasks-wrapper').after(`
            <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light" id="taskPaginationContainer"></div>
        `);
        container = $('#taskPaginationContainer');
    }

    let pageHtml = `<div class="text-muted small">Showing ${p.limit} tasks (Total: ${p.total_tasks})</div>`;
    pageHtml += `<div class="d-flex align-items-center">
        <select class="form-select form-select-sm w-auto me-3 shadow-sm" id="taskLimitSelect">
            <option value="5" ${p.limit == 5 ? 'selected' : ''}>5 per page</option>
            <option value="10" ${p.limit == 10 ? 'selected' : ''}>10 per page</option>
            <option value="25" ${p.limit == 25 ? 'selected' : ''}>25 per page</option>
        </select>
        <ul class="pagination pagination-sm mb-0 shadow-sm">`;

    // 1. Previous Button
    pageHtml += `<li class="page-item ${p.current_page == 1 ? 'disabled' : ''}">
        <a class="page-link task-page-link" href="#" data-page="${p.current_page - 1}">Prev</a></li>`;

    // 2. SLIDING WINDOW CALCULATION (Show max 3 pages)
    let startPage = Math.max(1, p.current_page - 1);
    let endPage = Math.min(p.total_pages, p.current_page + 1);
    
    // Edge cases to always show exactly 3 pages if available
    if (p.current_page === 1 && p.total_pages >= 3) endPage = 3;
    if (p.current_page === p.total_pages && p.total_pages >= 3) startPage = p.total_pages - 2;

    // Add absolute first page and ellipsis if we are deep in the pagination
    if (startPage > 1) {
        pageHtml += `<li class="page-item"><a class="page-link task-page-link" href="#" data-page="1">1</a></li>`;
        if (startPage > 2) pageHtml += `<li class="page-item disabled"><span class="page-link text-muted border-0 bg-transparent">...</span></li>`;
    }

    // Render the dynamic 3-button window
    for (let i = startPage; i <= endPage; i++) {
        pageHtml += `<li class="page-item ${p.current_page == i ? 'active' : ''}">
            <a class="page-link task-page-link" href="#" data-page="${i}">${i}</a></li>`;
    }

    // Add absolute last page and ellipsis if we have many pages left
    if (endPage < p.total_pages) {
        if (endPage < p.total_pages - 1) pageHtml += `<li class="page-item disabled"><span class="page-link text-muted border-0 bg-transparent">...</span></li>`;
        pageHtml += `<li class="page-item"><a class="page-link task-page-link" href="#" data-page="${p.total_pages}">${p.total_pages}</a></li>`;
    }

    // 3. Next Button
    pageHtml += `<li class="page-item ${p.current_page == p.total_pages ? 'disabled' : ''}">
        <a class="page-link task-page-link" href="#" data-page="${p.current_page + 1}">Next</a></li>`;

    pageHtml += `</ul></div>`;
    container.html(pageHtml);
};

window.fetchLogs = function(isManualFetch = false) {
    let logType = $('#logTypeSelect').val();
    let targetDomain = $('#logDomainSelect').val(); 
    let targetUser = $('#logUserSelect').val();
    let terminal = $('#logTerminal');
    let btn = $('#fetchLogBtn');

    if (isManualFetch) {
        terminal.html('<span class="text-warning">Streaming logs...</span>');
        btn.prop('disabled', true);
    }

    // Frontend Security: Replaces PHP's htmlspecialchars to prevent XSS attacks
    const escapeHTML = (str) => {
        return (str || '').toString().replace(/[&<>'"]/g, 
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag])
        );
    };

    $.ajax({
        url: '/ajax/get_logs.php',
        type: 'POST',
        data: { type: logType, domain: targetDomain, username: targetUser },
        dataType: 'json',
        success: function(response) {
            // Check if user is already at the bottom BEFORE we add new text
            // Added a 10px buffer to make the "sticky" bottom more forgiving
            let isAtBottom = (terminal[0].scrollHeight - terminal.scrollTop()) <= (terminal.outerHeight() + 10);

            if(response.success) {
                if(response.logs.trim() !== '') {
                    // REMOVED .reverse() -> Now renders chronologically (Newest at bottom)
                    const lines = response.logs.split('\n').filter(line => line.trim() !== '');
                    terminal.empty(); // Clear terminal

                    lines.forEach(line => {
                        let formattedHtml = '';

                        try {
                            // STRATEGY 3: Native JSON Parse (For Nginx Access Logs)
                            const logObj = JSON.parse(line);
                            
                            // Dynamic Badge Colors
                            let statusBadge = '<span class="badge bg-success" style="width:45px;">' + logObj.status + '</span>';
                            if(logObj.status >= 400) statusBadge = '<span class="badge bg-warning text-dark" style="width:45px;">' + logObj.status + '</span>';
                            if(logObj.status >= 500) statusBadge = '<span class="badge bg-danger" style="width:45px;">' + logObj.status + '</span>';

                            // New Flexbox Column Layout
                            formattedHtml = `
                                <div class="mb-2 pb-2 border-bottom border-secondary border-opacity-25 d-flex align-items-start gap-3">
                                    <div class="text-secondary small text-nowrap font-monospace">[${escapeHTML(logObj.time)}]</div>
                                    <div class="text-info fw-bold text-nowrap font-monospace" style="width: 120px;">${escapeHTML(logObj.ip)}</div>
                                    <div>${statusBadge}</div>
                                    <div class="text-light text-break w-100">${escapeHTML(logObj.method)} ${escapeHTML(logObj.uri)}</div>
                                </div>`;

                        } catch (e) {
                            // STRATEGY 2: Regex Fallback (For standard error.log or old access logs)
                            let badge = '<span class="badge bg-secondary" style="width:45px;">INFO</span>';
                            let textColor = 'text-light';
                            let lowerLine = line.toLowerCase();

                            if (lowerLine.includes('error') || lowerLine.includes('fatal') || lowerLine.includes('crit')) {
                                badge = '<span class="badge bg-danger" style="width:45px;">ERR</span>';
                                textColor = 'text-danger';
                            } else if (lowerLine.includes('warn')) {
                                badge = '<span class="badge bg-warning text-dark" style="width:45px;">WARN</span>';
                                textColor = 'text-warning';
                            }

                            let cleanLine = line;
                            let timestamp = '';
                            const timeMatch = line.match(/^\[.*?\]|^\w+\s+\d+\s+\d+:\d+:\d+/);
                            
                            if (timeMatch) {
                                // Extract and format timestamp
                                timestamp = `<div class="text-secondary small text-nowrap font-monospace">[${escapeHTML(timeMatch[0].replace(/[\[\]]/g, ''))}]</div>`;
                                cleanLine = line.substring(timeMatch[0].length).trim();
                            }

                            // New Flexbox Column Layout
                            formattedHtml = `
                                <div class="mb-2 pb-2 border-bottom border-secondary border-opacity-25 d-flex align-items-start gap-3">
                                    ${timestamp} 
                                    <div>${badge}</div> 
                                    <div class="${textColor} text-break w-100">${escapeHTML(cleanLine)}</div>
                                </div>`;
                        }

                        terminal.append(formattedHtml);
                    });
                } else {
                    terminal.html('<span class="text-secondary">Log file is currently empty.</span>');
                }
            } else {
                terminal.html('<span class="text-danger">' + escapeHTML(response.error) + '</span>');
            }
            
            // Auto-scroll logic: Scroll down if it's a fresh manual click OR if they were already at the bottom
            if(isManualFetch || isAtBottom) {
                terminal.scrollTop(terminal[0].scrollHeight);
            }
            
            if(isManualFetch) btn.prop('disabled', false);
        },
        error: function() {
            if(isManualFetch) {
                terminal.html('<span class="text-danger">Critical Network Error.</span>');
                btn.prop('disabled', false);
            }
        }
    });
};

window.fetchBackups = function() {
    $.ajax({
        url: '/ajax/get_backups.php',
        type: 'POST',
        dataType: 'json', 
        success: function(response) {
            if(response.success) {
                let tbody = $('#dynamicBackupsTable');
                tbody.empty();
                if(response.backups.length === 0) {
                    tbody.html('<tr><td colspan="5" class="text-center text-muted py-3">Vault is empty.</td></tr>');
                    return;
                }
                response.backups.forEach(function(b) {
                    let badge = b.type === 'Website' ? '<span class="badge bg-info text-dark">Web Archive</span>' : '<span class="badge bg-warning text-dark">SQL Dump</span>';
                    let dlUrl = `/ajax/download_backup.php?type=${b.type}&file=${b.filename}`;
                    let row = `<tr>
                            <td>${badge}</td>
                            <td class="fw-bold">${b.target}</td>
                            <td class="text-muted small">${b.time}</td>
                            <td><span class="badge bg-light text-dark border">${b.size}</span></td>
                            <td class="text-end">
                                <a href="${dlUrl}" class="btn btn-sm btn-dark me-1" title="Download to Computer"><i class="bi bi-cloud-arrow-down-fill"></i></a>
                                <button class="btn btn-sm btn-danger restore-backup me-1" data-file="${b.filename}" data-type="${b.type}" data-target="${b.target}" title="Restore to Server"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                <button class="btn btn-sm btn-outline-danger delete-backup" data-file="${b.filename}" data-type="${b.type}" title="Delete Archive"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>`;
                    tbody.append(row);
                });
            }
        }
    });
};

window.fetchSchedules = function() {
    $.ajax({
        url: '/ajax/get_schedules.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let tbody = $('#dynamicSchedulesTable');
                tbody.empty();
                if(response.schedules.length === 0) {
                    tbody.html('<tr><td colspan="6" class="text-center text-muted py-3">No automated schedules configured.</td></tr>');
                    return;
                }
                response.schedules.forEach(function(s) {
                    let typeBadge = s.backup_type === 'web' ? '<span class="badge bg-primary">Website</span>' : '<span class="badge bg-warning text-dark">Database</span>';
                    let runTime = s.run_hour + ':00';
                    let row = `<tr>
                        <td class="fw-bold">${s.target}</td>
                        <td>${typeBadge}</td>
                        <td class="text-capitalize">${s.frequency}</td>
                        <td><span class="badge bg-secondary"><i class="bi bi-clock"></i> ${runTime}</span></td>
                        <td>${s.retention_days} Days</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger delete-schedule" data-id="${s.id}" title="Delete Schedule"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>`;
                    tbody.append(row);
                });
            }
        }
    });
};

window.fetchCronJobs = function() {
    $.ajax({
        url: '/ajax/get_cron.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let tbody = $('#dynamicCronTable');
                tbody.empty();
                if(response.jobs.length === 0) {
                    tbody.html('<tr><td colspan="4" class="text-center text-muted py-3">No active cron jobs.</td></tr>');
                    return;
                }
                response.jobs.forEach(function(job) {
                    let schedule = `<span class="badge bg-light text-dark border font-monospace">${job.minute} ${job.hour} ${job.day} ${job.month} ${job.weekday}</span>`;
                    let row = `<tr>
                            <td class="fw-bold"><i class="bi bi-person text-muted"></i> ${job.username}</td>
                            <td>${schedule}</td>
                            <td><code class="text-dark bg-light px-2 py-1 rounded">${job.command}</code></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger delete-cron" 
                                    data-user="${job.username}" 
                                    data-min="${job.minute}" data-hr="${job.hour}" 
                                    data-day="${job.day}" data-mon="${job.month}" 
                                    data-wk="${job.weekday}" data-cmd="${btoa(job.command)}" 
                                    title="Delete Job"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>`;
                    tbody.append(row);
                });
            }
        }
    });
};

window.fetchServices = function() {
    $.ajax({
        url: '/ajax/get_services.php',
        type: 'POST',
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                let tbody = $('#dynamicServicesTable');
                tbody.empty();
                res.services.forEach(function(s) {
                    let statusBadge = '';
                    if (s.status === 'active') {
                        statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i> Running</span>';
                    } else if (s.status === 'inactive' || s.status === 'failed') {
                        statusBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-x-circle-fill me-1"></i> Stopped</span>';
                    } else {
                        statusBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">Not Installed</span>';
                    }

                    let actions = '';
                    if (s.status !== 'unknown') {
                        let startBtn = `<button class="btn btn-sm btn-outline-success mx-1 execute-service" data-action="start" data-svc="${s.service}" title="Start"><i class="bi bi-play-fill"></i></button>`;
                        let stopBtn  = `<button class="btn btn-sm btn-outline-danger mx-1 execute-service" data-action="stop" data-svc="${s.service}" title="Stop"><i class="bi bi-stop-fill"></i></button>`;
                        let resBtn   = `<button class="btn btn-sm btn-outline-dark mx-1 execute-service" data-action="restart" data-svc="${s.service}" title="Restart"><i class="bi bi-arrow-clockwise"></i></button>`;

                        if (!s.can_stop) { stopBtn = ''; startBtn = ''; }
                        
                        if (s.status === 'active') startBtn = startBtn.replace('btn-outline-success', 'btn-outline-success disabled');
                        if (s.status !== 'active') {
                            stopBtn = stopBtn.replace('btn-outline-danger', 'btn-outline-danger disabled');
                            resBtn = resBtn.replace('btn-outline-dark', 'btn-outline-dark disabled');
                        }
                        actions = startBtn + stopBtn + resBtn;
                    }

                    let row = `<tr>
                        <td class="fw-bold text-dark">${s.name}</td>
                        <td>${statusBadge}</td>
                        <td class="text-end">${actions}</td>
                    </tr>`;
                    tbody.append(row);
                });
            }
        }
    });
};

window.fetchComponents = function() {
    $.ajax({
        url: '/ajax/get_components.php',
        type: 'POST',
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                let tbody = $('#dynamicComponentsTable');
                tbody.empty();
                res.components.forEach(function(c) {
                    let versionDisplay = c.version === 'Not Installed' 
                        ? `<span class="badge bg-secondary bg-opacity-10 text-secondary border">Not Installed</span>` 
                        : `<code class="text-dark bg-light px-2 py-1 rounded shadow-sm border">${c.version}</code>`;

                    let row = `<tr>
                        <td class="fw-bold text-dark">${c.name}</td>
                        <td class="text-muted small font-monospace">${c.package}</td>
                        <td class="text-end">${versionDisplay}</td>
                    </tr>`;
                    tbody.append(row);
                });
            }
        }
    });
};

window.renderSoftwareCenter = function() {
    const supportedVersions = ['8.4', '8.3', '8.2', '8.1', '8.0', '7.4'];
    $.ajax({
        url: '/ajax/get_php_versions.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let installedVersions = response.versions;
                let tableRows = '';
                supportedVersions.forEach(function(ver) {
                    let isInstalled = installedVersions.includes(ver);
                    let badge = isInstalled 
                        ? '<span class="badge bg-success shadow-sm"><i class="bi bi-check-circle"></i> Installed</span>' 
                        : '<span class="badge bg-secondary shadow-sm">Not Installed</span>';
                        
                    let actionBtn = isInstalled
                        ? `<button class="btn btn-sm btn-outline-danger software-action-btn" data-action="remove" data-version="${ver}"><i class="bi bi-trash"></i> Uninstall</button>`
                        : `<button class="btn btn-sm btn-primary software-action-btn shadow-sm" data-action="install" data-version="${ver}"><i class="bi bi-download"></i> Install</button>`;

                    tableRows += `
                        <tr>
                            <td class="fw-bold text-dark">PHP ${ver}</td>
                            <td class="text-muted small">FastCGI Process Manager (FPM)</td>
                            <td>${badge}</td>
                            <td class="text-end">${actionBtn}</td>
                        </tr>
                    `;
                });
                $('#dynamicSoftwareTable').html(tableRows);
            }
        }
    });
};

// ==========================================
// FAIL2BAN GLOBAL SETTINGS LOGIC
// ==========================================

// 1. Fetch current settings when the tab is clicked
window.fetchFail2BanSettings = function() {
    // Briefly disable inputs while loading
    $('#f2b_bantime_val, #f2b_findtime_val, #f2b_maxretry').prop('disabled', true);
    
    $.ajax({
        url: '/ajax/get_f2b_settings.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate the inputs with the data from the server
                $('#f2b_bantime_val').val(response.bantime_val);
                $('#f2b_bantime_unit').val(response.bantime_unit);
                
                $('#f2b_findtime_val').val(response.findtime_val);
                $('#f2b_findtime_unit').val(response.findtime_unit);
                
                $('#f2b_maxretry').val(response.maxretry);
            } else {
                alert('Error loading Fail2ban settings: ' + response.error);
            }
        },
        error: function() {
            alert('Network error while fetching Fail2ban settings.');
        },
        complete: function() {
            // Re-enable inputs
            $('#f2b_bantime_val, #f2b_findtime_val, #f2b_maxretry').prop('disabled', false);
        }
    });
};

// 2. Save new settings to the Task Queue
window.saveFail2BanSettings = function() {
    const bantimeVal = $('#f2b_bantime_val').val();
    const bantimeUnit = $('#f2b_bantime_unit').val();
    
    const findtimeVal = $('#f2b_findtime_val').val();
    const findtimeUnit = $('#f2b_findtime_unit').val();
    
    const maxretry = $('#f2b_maxretry').val();

    if (!bantimeVal || !findtimeVal || !maxretry) {
        alert("Please fill in all numerical fields.");
        return;
    }

    // Combine values and units (e.g., "1" + "h" = "1h")
    const bantime = bantimeVal + bantimeUnit;
    const findtime = findtimeVal + findtimeUnit;

    const btn = $('#formF2bSettings button');
    const originalText = btn.html();
    
    // Show loading spinner on button
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

    $.ajax({
        url: '/ajax/update_f2b_settings.php',
        type: 'POST',
        data: {
            bantime: bantime,
            findtime: findtime,
            maxretry: maxretry
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Close the modal
                $('#fail2banStatusModal').modal('hide');
                
                // Extract Task ID and trigger the global live-polling UI
                const taskIdMatch = response.message.match(/Task ID: (\d+)/);
                if (taskIdMatch && typeof pollTaskStatus === 'function') {
                    pollTaskStatus(taskIdMatch[1]);
                } else {
                    alert("Settings saved! Fail2ban is restarting.");
                }
            } else {
                alert('Error: ' + response.error);
            }
        },
        error: function() {
            alert('Network error while saving settings.');
        },
        complete: function() {
            // Restore button state
            btn.prop('disabled', false).html(originalText);
        }
    });
};
    // ==========================================
    // LICENSE & UPDATES LOGIC
    // ==========================================
    window.fetchLicenseData = function() {
        $.ajax({
            url: '/ajax/get_license_info.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#ui-license-key').text(res.key);
                    $('#ui-license-owner').text(res.owner_name);
                    $('#ui-license-email').text(res.owner_email);
                    $('#ui-license-ip').text(res.ip);
                    $('#ui-license-expiry').text(res.expiry);

                    let statusBadge = $('#ui-license-status');
                    statusBadge.removeClass('bg-secondary bg-success bg-danger bg-warning');
                    
                    if (res.status === 'active') {
                        statusBadge.addClass('bg-success bg-opacity-10 text-success border border-success').html('<i class="bi bi-check-circle-fill"></i> Active');
                    } else if (res.status === 'suspended') {
                        statusBadge.addClass('bg-warning bg-opacity-10 text-warning border border-warning').html('<i class="bi bi-pause-circle-fill"></i> Suspended');
                    } else {
                        statusBadge.addClass('bg-danger bg-opacity-10 text-danger border border-danger').html('<i class="bi bi-x-circle-fill"></i> ' + res.status.toUpperCase());
                    }
                }
            },
            // NEW: Fallback if the script fails to execute
            error: function() {
                $('#ui-license-status').text('Error Loading Data').removeClass('bg-secondary').addClass('badge bg-danger');
                $('#ui-license-key').text('Network Error');
            }
        });
    };

    window.forceLicenseSync = function() {
        let btn = $('#btnSyncLicense');
        let originalText = btn.html();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Syncing...');
        
        $.ajax({
            url: '/ajax/sync_license.php',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    window.fetchLicenseData(); // Refresh the UI data
                    showToast("License successfully synced with Stackrium Central.");
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() {
                alert("Network error during sync.");
                btn.prop('disabled', false).html(originalText);
            }
        });
    };
// =================================================================
// 2. EVENT LISTENERS
// =================================================================
$(document).ready(function() {

    // Auto-fetch the license data when the tab is clicked
    $('button[data-bs-target="#license-updates"], a[href="#license-updates"]').on('shown.bs.tab', function () {
        window.fetchLicenseData();
    });

    // === TASK PAGINATION & LOG VIEWER ===
    $(document).on('click', '.task-page-link', function(e) {
        e.preventDefault();
        if($(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) return;
        window.currentTaskPage = $(this).data('page');
        window.fetchRecentTasks();
    });

    $(document).on('change', '#taskLimitSelect', function() {
        window.taskLimit = $(this).val();
        window.currentTaskPage = 1;
        window.fetchRecentTasks();
    });

    $(document).on('click', '.view-task-log', function() {
        let taskId = $(this).data('id');
        let btn = $(this);
        let originalIcon = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/get_task_log.php',
            type: 'POST',
            data: { id: taskId },
            dataType: 'json',
            success: function(response) {
                btn.html(originalIcon);
                if(response.success) {
                    $('#logTaskAction').text(response.action);
                    $('#logTaskOutput').text(response.output);
                    if (response.status === 'failed') {
                        $('#logTaskStatus').html('<span class="text-danger">[FAILED]</span> Process exited with errors.');
                    } else {
                        $('#logTaskStatus').html('<span class="text-success">[OK]</span> Process exited cleanly.');
                    }
                    $('#taskLogModal').modal('show');
                } else { alert("Error fetching log: " + response.error); }
            },
            error: function() { btn.html(originalIcon); alert("Network error."); }
        });
    });

    // === TERMINAL LOG VIEWER ===
    $('#logModal').on('show.bs.modal', function () {
        window.fetchLogs();
        window.logInterval = setInterval(window.fetchLogs, 2000); 
    });
    $('#logModal').on('hide.bs.modal', function () {
        clearInterval(window.logInterval); 
    });
    // Change #logType to #logTypeSelect
    $('#logTypeSelect').on('change', function() {
        $('#logTerminal').html('<span class="text-warning">Loading...</span>');
        window.fetchLogs();
    });

    // === BACKUP & SCHEDULE SYSTEM ===
    $('#submitBackupWebBtn, #submitBackupDbBtn').click(function() {
        let btn = $(this);
        let form = btn.closest('.modal-content').find('form');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Queueing...');
        $.ajax({
            url: '/ajax/create_backup.php',
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if(response.success) {
                    $('.modal').modal('hide');
                    form[0].reset();
                    setTimeout(window.fetchBackups, 5000); 
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).text('Generate Archive');
            }
        });
    });

    $('#submitUploadBtn').click(function() {
        let btn = $(this);
        let form = $('#uploadBackupForm')[0];
        if (!form.checkValidity()) { form.reportValidity(); return; }
        
        let formData = new FormData(form);
        btn.prop('disabled', true).text('Uploading...');
        $('#uploadProgress').removeClass('d-none');
        $('.progress-bar').css('width', '0%');

        $.ajax({
            url: '/ajax/upload_backup.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('.progress-bar').css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;
                if(res.success) {
                    $('#uploadBackupModal').modal('hide');
                    form.reset();
                    $('#uploadProgress').addClass('d-none');
                    window.fetchBackups();
                } else {
                    alert("Error: " + res.error);
                    $('#uploadProgress').addClass('d-none');
                }
                btn.prop('disabled', false).text('Upload to Vault');
            },
            error: function() {
                alert("Upload failed. The file might exceed PHP's max_upload_size.");
                $('#uploadProgress').addClass('d-none');
                btn.prop('disabled', false).text('Upload to Vault');
            }
        });
    });

    $(document).on('click', '.restore-backup', function() {
        let fileName = $(this).data('file');
        let type = $(this).data('type');
        let target = $(this).data('target');
        
        let warning = `CRITICAL WARNING: You are about to overwrite the live ${type} for '${target}'.\n\nAll current data will be permanently destroyed and replaced with this backup.\n\nAre you absolutely sure?`;
        if(!confirm(warning)) return;
        
        let btn = $(this);
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/restore_backup.php',
            type: 'POST',
            data: { file: fileName, type: type, target: target },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert("Restore task queued successfully! Check the Live Tasks log for status.");
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $(document).on('click', '.delete-backup', function() {
        let fileName = $(this).data('file');
        let type = $(this).data('type');
        if(!confirm(`Are you sure you want to permanently delete this ${type} backup? This cannot be undone.`)) return;
        
        let btn = $(this);
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/delete_backup.php',
            type: 'POST',
            data: { file: fileName, type: type },
            dataType: 'json',
            success: function(response) {
                if(response.success) { window.fetchBackups(); } 
                else { alert("Error: " + response.error); btn.prop('disabled', false).html(originalText); }
            }
        });
    });

    $('#schedType').on('change', function() {
        if($(this).val() === 'web') {
            $('#schedTargetWeb').removeClass('d-none').prop('required', true);
            $('#schedTargetDb').addClass('d-none').prop('required', false);
        } else {
            $('#schedTargetWeb').addClass('d-none').prop('required', false);
            $('#schedTargetDb').removeClass('d-none').prop('required', true);
        }
    });

    $('#submitScheduleBtn').click(function() {
        let btn = $(this);
        let form = $('#scheduleBackupForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        $.ajax({
            url: '/ajax/manage_schedule.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#scheduleBackupModal').modal('hide');
                    alert("Backup schedule saved successfully! The engine will run it automatically.");
                    window.fetchSchedules();
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).text('Save Schedule');
            }
        });
    });

    $(document).on('click', '.delete-schedule', function() {
        let scheduleId = $(this).data('id');
        if(!confirm("Are you sure you want to stop automated backups for this target?")) return;
        
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/ajax/delete_schedule.php',
            type: 'POST',
            data: { id: scheduleId },
            dataType: 'json',
            success: function(response) {
                if(response.success) { window.fetchSchedules(); } 
                else { alert("Error: " + response.error); btn.prop('disabled', false).html('<i class="bi bi-trash"></i>'); }
            }
        });
    });

    // === CRON JOBS ===
    $('#saveCronBtn').click(function() {
        let btn = $(this);
        let formData = $('#addCronForm').serialize();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_cron.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                let res = JSON.parse(response);
                if(res.success) {
                    $('#addCronModal').modal('hide');
                    document.getElementById('addCronForm').reset();
                    setTimeout(window.fetchCronJobs, 3000);
                } else { alert("Error: " + res.error); }
                btn.prop('disabled', false).text('Save Cron Job');
            }
        });
    });

    $(document).on('click', '.delete-cron', function() {
        if(!confirm('Delete this cron job?')) return;
        let btn = $(this);
        let cmdDecoded = atob(btn.data('cmd'));
        
        btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');
        $.ajax({
            url: '/ajax/manage_cron.php',
            type: 'POST',
            data: {
                action: 'delete',
                username: btn.data('user'),
                minute: btn.data('min'),
                hour: btn.data('hr'),
                day: btn.data('day'),
                month: btn.data('mon'),
                weekday: btn.data('wk'),
                command: cmdDecoded
            },
            success: function(response) { setTimeout(window.fetchCronJobs, 3000); }
        });
    });

    // === SYSTEM SERVICES ===
    $(document).on('click', '.execute-service', function() {
        if ($(this).hasClass('disabled')) return;
        let action = $(this).data('action');
        let svc = $(this).data('svc');
        
        let warning = `Are you sure you want to ${action.toUpperCase()} the ${svc} service?`;
        if (action === 'stop' && !confirm(warning)) return;

        let btn = $(this);
        let originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_service.php',
            type: 'POST',
            data: { action: action, service: svc },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    showToast(res.message);
                    setTimeout(window.fetchServices, 3000);
                } else {
                    alert("Error: " + res.error);
                    btn.prop('disabled', false).html(originalHtml);
                }
            }
        });
    });

    // === SOFTWARE CENTER ===
    $(document).on('click', '.software-action-btn', function() {
        let action = $(this).data('action');
        let version = $(this).data('version');
        
        if(confirm(`Are you sure you want to ${action} PHP ${version}? This will run in the background.`)) {
            let btn = $(this);
            let originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Queueing...');

            $.ajax({
                url: '/ajax/install_php.php',
                type: 'POST',
                data: { sub_action: action, version: version },
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        $('#softwareCenterModal').modal('hide');
                        $('#overview-tab').tab('show');
                        if (typeof window.fetchRecentTasks === "function") window.fetchRecentTasks();
                    } else {
                        alert("Error: " + res.error);
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    alert("Network error occurred.");
                    btn.prop('disabled', false).html(originalText);
                }
            });
        }
    });

    $('#softwareCenterModal').on('show.bs.modal', function () { window.renderSoftwareCenter(); });

    // === SYSTEM SETTINGS (Branding, Timezone, Admin Profile, Secure Panel) ===
    $('#submitAdminProfileBtn').click(function() {
        let btn = $(this);
        let form = $('#adminProfileForm');
        let alertBox = $('#adminProfileAlert');
        
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        if ($('#newAdminPass').val() !== $('#confirmAdminPass').val()) {
            alertBox.removeClass('d-none alert-success').addClass('alert-danger').text("New passwords do not match.");
            return;
        }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');
        alertBox.addClass('d-none');

        $.ajax({
            url: '/ajax/change_admin_password.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.removeClass('d-none alert-danger').addClass('alert-success').text("Password updated! You will be logged out in 3 seconds.");
                    form[0].reset();
                    setTimeout(function() { window.location.href = '/logout'; }, 3000);
                } else {
                    alertBox.removeClass('d-none alert-success').addClass('alert-danger').text(response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Update Password');
            }
        });
    });

    $('#submitTimezoneBtn').click(function() {
        let btn = $(this);
        let tz = $('#serverTimezoneSelect').val();
        
        if(!confirm(`WARNING: You are about to shift the entire server's absolute time to ${tz}. Scheduled cron jobs and database timestamps will run based on this new time. Proceed?`)) return;
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Syncing...');

        $.ajax({
            url: '/ajax/set_timezone.php',
            type: 'POST',
            data: { timezone: tz },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert("Timezone sync queued! The server will migrate in a few seconds.");
                    $('#systemSettingsModal').modal('hide');
                    $('#overview-tab').tab('show');
                } else { alert("Error: " + response.error); }
                btn.prop('disabled', false).text('Sync Server Time');
            }
        });
    });

    $('#brandingForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#saveBrandingBtn');
        let alertBox = $('#brandingAlert');
        let formData = new FormData(this);

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/save_branding.php',
            type: 'POST',
            data: formData,
            contentType: false, 
            processData: false, 
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    alertBox.addClass('alert-success').text("Branding saved! Reloading to apply changes...").removeClass('d-none');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    alertBox.addClass('alert-danger').text("Error: " + res.error).removeClass('d-none');
                    btn.prop('disabled', false).html('Save Changes');
                }
            }
        });
    });

    // === FETCH & POPULATE BRANDING MODAL ON OPEN ===
    $('#brandingModal').on('show.bs.modal', function () {
        $.ajax({
            url: '/ajax/get_branding.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if(res.success && res.data) {
                    $('input[name="brand_title"]').val(res.data.brand_title || '');
                    $('input[name="brand_subtext"]').val(res.data.brand_subtext || '');
                    $('input[name="brand_logo_url"]').val(res.data.brand_logo_url || '');
                    $('input[name="brand_theme_color"]').val(res.data.brand_theme_color || '#0d6efd');
                    $('input[name="brand_sidebar_color"]').val(res.data.brand_sidebar_color || '#212529');
                    $('input[name="brand_login_bg_color"]').val(res.data.brand_login_bg_color || '#f8f9fa');
                    $('select[name="brand_login_bg_fit"]').val(res.data.brand_login_bg_fit || 'cover');
                    $('#hideFooterCheck').prop('checked', res.data.brand_hide_footer == '1');
                }
            }
        });
    });
    // Open WAF Rules Modal
    $(document).on('click', '.edit-waf-rules', function() {
        let domain = $(this).data('domain');
        // Decode the rules from base64 safely
        let existingRules = atob($(this).data('rules')); 
        
        $('#wafDomainTitle').text(domain);
        $('#wafDomainInput').val(domain);
        $('#wafRulesTextarea').val(existingRules);
        $('#wafRulesModal').modal('show');
    });

    // Submit Custom WAF Rules
    $('#saveWafRulesBtn').click(function() {
        let btn = $(this);
        let formData = $('#wafRulesForm').serialize();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Compiling...');

        $.ajax({
            url: '/ajax/manage_waf_rules.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#wafRulesModal').modal('hide');
                    setTimeout(fetchDomains, 3000); 
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).text('Compile & Apply Rules');
            }
        });
    });
    // WAF Toggle Button Click Handler
    $(document).on('click', '.toggle-waf', function() {
        let btn = $(this);
        let domain = btn.data('domain');
        let action = btn.data('action'); // 'on' or 'off'
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_waf.php',
            type: 'POST',
            data: { domain: domain, status: action },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Refresh the table after 3 seconds to let Python process it
                    setTimeout(fetchDomains, 3000); 
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false);
                }
            }
        });
    });
    $('#submitSecurePanelBtn').click(function() {
        let btn = $(this);
        let domain = $('#masterDomainSelect').val();
        let alertBox = $('#securePanelAlert');
        
        if (!domain) { alert("Please select a domain first."); return; }
        if(!confirm(`Warning: This will lock Stackrium to ${domain} and reload Nginx. Your session will redirect. Proceed?`)) return;

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Binding...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/secure_panel.php',
            type: 'POST',
            data: { action: 'bind', domain: domain },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    alertBox.addClass('alert-success').text("Success! Redirecting in 3 seconds...").removeClass('d-none');
                    setTimeout(() => window.location.href = "https://" + res.domain + ":7443", 3000);
                } else {
                    alertBox.addClass('alert-danger').text("Error: " + res.error).removeClass('d-none');
                    btn.prop('disabled', false).html('<i class="bi bi-link-45deg"></i> Bind to Panel');
                }
            }
        });
    });

    $('#unbindPanelBtn').click(function() {
        let btn = $(this);
        let alertBox = $('#securePanelAlert');
        
        if(!confirm("Are you sure you want to unbind the panel? This reverts to the raw IP address and self-signed certificates.")) return;

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/secure_panel.php',
            type: 'POST',
            data: { action: 'unbind' },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    alertBox.addClass('alert-success').text("Success! Redirecting to IP in 3 seconds...").removeClass('d-none');
                    setTimeout(() => window.location.href = "https://" + res.ip + ":7443", 3000);
                } else {
                    alertBox.addClass('alert-danger').text("Error: " + res.error).removeClass('d-none');
                    btn.prop('disabled', false).html('<i class="bi bi-x-circle"></i> Unbind');
                }
            }
        });
    });
    // =================================================================
    // STACKRIUM UPDATE ENGINE LOGIC
    // =================================================================

    // Global variables to hold the download URLs so the Install button can access them
    window.latestStableUrl = '';
    window.latestBetaUrl = '';

    // 1. Trigger the check when the Updates button is clicked
    $(document).on('click', '#btnCheckUpdates', function() {
        $('#updateModal').modal('show');
        
        // Reset UI to loading state
        $('.btn-start-update').prop('disabled', true);
        $('#ui-stable-version, #ui-beta-version').text('Loading...');
        $('#ui-stable-date, #ui-beta-date').text('--');
        $('#ui-stable-changelog, #ui-beta-changelog').html('<div class="spinner-border spinner-border-sm text-secondary"></div> Fetching data...');
        
        // Fetch from the local PHP Bridge (which pings stackrium.com)
        $.ajax({
            url: '/ajax/check_updates.php',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    // ---> SAVE THE URLS GLOBALLY <---
                    window.latestStableUrl = res.stable.url;
                    window.latestBetaUrl = res.beta.url;

                    // Populate Stable Tab
                    $('#ui-stable-version').text(res.stable.version);
                    $('#ui-stable-date').text(res.stable.release_date);
                    $('#ui-stable-changelog').html(res.stable.changelog);
                    $('button[data-channel="stable"]').prop('disabled', false);

                    // Populate Beta Tab
                    $('#ui-beta-version').text(res.beta.version);
                    $('#ui-beta-date').text(res.beta.release_date);
                    $('#ui-beta-changelog').html(res.beta.changelog);
                    $('button[data-channel="beta"]').prop('disabled', false);

                    // Set the Auto-Update Toggle State
                    $('#autoUpdateToggle').prop('checked', res.local_auto_update);
                } else {
                    $('#ui-stable-changelog').html('<span class="text-danger"><i class="bi bi-x-circle"></i> ' + res.error + '</span>');
                    $('#ui-beta-changelog').html('<span class="text-danger">Failed to fetch beta branch.</span>');
                }
            },
            error: function() {
                $('#ui-stable-changelog').html('<span class="text-danger">Network error reaching update server.</span>');
            }
        });
    });

    // 2. Handle the Auto-Update Toggle Switch
    $(document).on('change', '#autoUpdateToggle', function() {
        let isEnabled = $(this).is(':checked');
        $.ajax({
            url: '/ajax/toggle_autoupdate.php',
            type: 'POST',
            data: { enable: isEnabled },
            dataType: 'json',
            success: function(res) {
                if (res.success) showToast(isEnabled ? "Unattended Auto-Updates Enabled!" : "Auto-Updates Disabled.");
            }
        });
    });

    // 3. Handle the "Install Update" Click (The Real Engine)
    $(document).on('click', '.btn-start-update', function() {
        let channel = $(this).data('channel');
        
        if (!confirm(`Are you sure you want to install the ${channel.toUpperCase()} update? Your panel will be momentarily offline.`)) {
            return;
        }

        // Lock the Modal (Prevent closing)
        $('#closeUpdateModalBtn').hide();
        $('#updateModal').data('bs-backdrop', 'static').data('bs-keyboard', 'false');

        // Robust UI Transition: Hide tabs instantly, show progress bar instantly
        $('#updateTabs').hide();
        $('#updateTabContent').hide();
        $('#updateProgressUI').removeClass('d-none').show(); // Fixed visibility issue!

        // Grab the correct URL we saved earlier
        let downloadUrl = channel === 'stable' ? window.latestStableUrl : window.latestBetaUrl;

        // Trigger the Bash Script in the background
        $.ajax({
            url: '/ajax/run_update.php',
            type: 'POST',
            data: { channel: channel, url: downloadUrl },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    
                    // Start Polling the JSON status file every 1.5 seconds
                    let pollInterval = setInterval(function() {
                        $.ajax({
                            url: '/ajax/get_update_status.php?t=' + Date.now(), // Cache-buster
                            dataType: 'json',
                            cache: false,
                            success: function(statusData) {
                                // Update the visual Progress Bar
                                $('#updateProgressBar').css('width', statusData.progress + '%');
                                $('#updateStepText').text(statusData.step);
                                
                                // Success condition
                                if (statusData.progress === 100 || statusData.status === 'complete') {
                                    clearInterval(pollInterval);
                                    $('#updateProgressBar').removeClass('progress-bar-animated bg-primary').addClass('bg-success');
                                    setTimeout(() => window.location.reload(), 2000);
                                }
                                
                                // Error condition
                                if (statusData.status === 'error') {
                                    clearInterval(pollInterval);
                                    $('#updateProgressBar').removeClass('progress-bar-animated bg-primary').addClass('bg-danger');
                                    alert("Update Failed: " + statusData.step);
                                }
                            }
                        });
                    }, 1500);

                } else {
                    alert("Failed to start update: " + res.error);
                }
            }
        });
    });

    // ==========================================
    // 3. INITIALIZATION CALLS
    // ==========================================
    window.fetchSystemStats();
    setInterval(window.fetchSystemStats, 3000);

    window.fetchRecentTasks();
    setInterval(window.fetchRecentTasks, 5000);

    window.fetchBackups();
    window.fetchSchedules();
    window.fetchCronJobs();
    window.fetchServices();
    window.fetchComponents();

    // Toggle the visibility of the Domain/User selectors based on log type
    $(document).on('change', '#logTypeSelect', function() {
        let type = $(this).val();
        let isSystemLog = ['daemon', 'fail2ban', 'updater', 'syslog'].includes(type);
        
        if (isSystemLog) {
            // Hide the domain/user dropdowns using a smooth slide
            $('#logDomainGroup').slideUp('fast');
        } else {
            // Show them for website logs
            $('#logDomainGroup').slideDown('fast');
        }
    });

    // Make sure the Overview tab "System Logs" button auto-selects Daemon
    $(document).on('click', '[data-bs-target="#logModal"]:contains("System Logs")', function() {
        $('#logTypeSelect').val('daemon').trigger('change');
        setTimeout(() => { $('#fetchLogBtn').trigger('click'); }, 300);
    });
    // =================================================================
    // THE UNIVERSAL LOG FETCHER
    // =================================================================
    $(document).on('click', '#fetchLogBtn', function() {
        // Pass "true" to trigger the manual loading animations
        window.fetchLogs(true);
    });
    // Force the active tab to load its data on a hard page refresh
    setTimeout(function() {
        let activeTab = $('.nav-link.active');
        if (activeTab.length > 0) {
            activeTab.trigger('click').trigger('shown.bs.tab'); 
        }
    }, 100); // 100ms delay guarantees all other scripts have finished loading
});