// /opt/panel/www/js/modules/system.js

// =================================================================
// 1. GLOBAL FUNCTIONS & STATE (Attached to Window)
// =================================================================
window.currentTaskPage = 1;
window.taskLimit = 5;
window.logInterval = null;

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
                let tbody = $('#dynamicTasksTable');
                tbody.empty(); 
                
                if(response.tasks.length === 0) {
                    tbody.html('<tr><td colspan="6" class="text-center text-muted py-3">No system tasks found.</td></tr>');
                    $('#taskPaginationContainer').empty();
                    return;
                }

                response.tasks.forEach(function(task) {
                    let badgeClass = 'bg-secondary';
                    let actionButtons = '';

                    if(task.status === 'completed') {
                        badgeClass = 'bg-success';
                        actionButtons = `<button class="btn btn-sm btn-outline-secondary p-1 px-2 view-task-log" data-id="${task.id}" title="View Output Log"><i class="bi bi-terminal-fill"></i></button>`;
                    }
                    if(task.status === 'failed') {
                        badgeClass = 'bg-danger';
                        actionButtons = `<button class="btn btn-sm btn-outline-danger p-1 px-2 view-task-log" data-id="${task.id}" title="View Error Log"><i class="bi bi-terminal-fill"></i></button>`;
                    }
                    if(task.status === 'pending' || task.status === 'processing') {
                        badgeClass = 'bg-warning text-dark';
                        actionButtons = `<div class="spinner-border spinner-border-sm text-secondary" role="status"></div>`;
                    }

                    let target = 'System Command';
                    try {
                        let data = JSON.parse(task.payload);
                        target = data.domain || data.username || data.port || 'Data Object';
                    } catch(e) {}

                    let row = `
                        <tr>
                            <td class="fw-bold text-muted">#${task.id}</td>
                            <td><code class="text-dark">${task.action}</code></td>
                            <td>${target}</td>
                            <td><span class="badge ${badgeClass}">${task.status.toUpperCase()}</span></td>
                            <td class="small text-muted">${task.created_at}</td>
                            <td class="text-end">${actionButtons}</td> 
                        </tr>
                    `;
                    tbody.append(row);
                });

                window.renderTaskPagination(response.pagination);
            }
        }
    });
};

window.renderTaskPagination = function(p) {
    let container = $('#taskPaginationContainer');
    if (container.length === 0) {
        $('#dynamicTasksTable').closest('.table-responsive').after(`
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

    pageHtml += `<li class="page-item ${p.current_page == 1 ? 'disabled' : ''}">
        <a class="page-link task-page-link" href="#" data-page="${p.current_page - 1}">Prev</a></li>`;

    for (let i = 1; i <= p.total_pages; i++) {
        pageHtml += `<li class="page-item ${p.current_page == i ? 'active' : ''}">
            <a class="page-link task-page-link" href="#" data-page="${i}">${i}</a></li>`;
    }

    pageHtml += `<li class="page-item ${p.current_page == p.total_pages ? 'disabled' : ''}">
        <a class="page-link task-page-link" href="#" data-page="${p.current_page + 1}">Next</a></li>`;

    pageHtml += `</ul></div>`;
    container.html(pageHtml);
};

window.fetchLogs = function() {
    let type = $('#logType').val();
    let domain = $('#logDomain').val(); 
    let user = $('#logUser').val();

    $.ajax({
        url: '/ajax/get_logs.php',
        type: 'POST',
        data: { type: type, domain: domain, username: user },
        dataType: 'json',
        success: function(response) {
            let terminal = $('#logTerminal');
            let isAtBottom = terminal[0].scrollHeight - terminal.scrollTop() === terminal.outerHeight();

            if(response.success) {
                if(response.logs.trim() !== '') {
                    terminal.text(response.logs);
                } else {
                    terminal.html('<span class="text-secondary">Log file is currently empty.</span>');
                }
            } else {
                terminal.html('<span class="text-danger">' + response.error + '</span>');
            }
            if(isAtBottom) terminal.scrollTop(terminal[0].scrollHeight);
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

// =================================================================
// 2. EVENT LISTENERS
// =================================================================
$(document).ready(function() {

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
    $('#logType').on('change', function() {
        $('#logTerminal').html('Loading...');
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

    $('#submitSecurePanelBtn').click(function() {
        let btn = $(this);
        let domain = $('#masterDomainSelect').val();
        let alertBox = $('#securePanelAlert');
        
        if (!domain) { alert("Please select a domain first."); return; }
        if(!confirm(`Warning: This will lock oPanel to ${domain} and reload Nginx. Your session will redirect. Proceed?`)) return;

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
});