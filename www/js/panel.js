$(document).ready(function() {
    // === GLOBAL CSRF INTERCEPTOR ===
    // Grabs the token from the <meta> tag and attaches it to all AJAX headers
    let csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    });
    // ===============================
    // ... the rest of your existing JS logic ...
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault(); // Stop the page from reloading
        
        // Grab the button and alert box
        let btn = $('#submitUserBtn');
        let alertBox = $('#formAlert');
        
        // Set loading state
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        // Send data to PHP
        $.ajax({
            url: '/ajax/create_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.addClass('alert-success').text(response.message).removeClass('d-none');
                    $('#addUserForm')[0].reset(); // Clear form
                    // Here we would normally trigger a function to check task status
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
            },
            error: function() {
                alertBox.addClass('alert-danger').text('A server error occurred.').removeClass('d-none');
            },
            complete: function() {
                btn.prop('disabled', false).text('Create User');
            }
        });
    });
    // Add Domain Form Submission
    $('#addDomainForm').on('submit', function(e) {
        e.preventDefault();
        
        let btn = $('#submitDomainBtn');
        let alertBox = $('#domainFormAlert');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Configuring Nginx...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/create_domain.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.addClass('alert-success').text(response.message).removeClass('d-none');
                    $('#addDomainForm')[0].reset();
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
            },
            error: function() {
                alertBox.addClass('alert-danger').text('A server error occurred. Check PHP logs.').removeClass('d-none');
            },
            complete: function() {
                btn.prop('disabled', false).text('Create Domain & Nginx vHost');
            }
        });
    });
    $('#dbOwner').on('change', function() {
        let val = $(this).val();
        $('#dbPrefixLabel').text(val ? val + '_' : 'prefix_');
    });
    // === Add Database Form Submission (Upgraded Button Click) ===
    $(document).on('click', '#submitDbBtn', function(e) {
        e.preventDefault();
        
        let form = $('#addDbForm');
        
        // Native HTML5 Validation Check (Forces required fields to be filled)
        if (!form[0].checkValidity()) { 
            form[0].reportValidity(); 
            return; 
        }

        // Build the Custom Privileges String if 'custom' is selected
        if ($('#dbRole').val() === 'custom') {
            let privs = [];
            $('.db-priv-chk:checked').each(function() { privs.push($(this).val()); });
            
            if(privs.length === 0) {
                alert("You must select at least one privilege for a custom role.");
                return;
            }
            $('#customPrivString').val(privs.join(', '));
        }

        let btn = $(this);
        let alertBox = $('#dbFormAlert');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Provisioning DB...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/create_db.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.addClass('alert-success').text(response.message).removeClass('d-none');
                    
                    // Reset the UI cleanly
                    form[0].reset();
                    $('#dbPrefixLabel').text('prefix_');
                    $('#dbCustomIp').addClass('d-none');
                    $('#customPrivilegesGrid').addClass('d-none');
                    
                    // Automatically refresh the table to show the new database!
                    setTimeout(fetchDatabases, 2500); 
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
            },
            error: function() {
                alertBox.addClass('alert-danger').text('A server error occurred.').removeClass('d-none');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-database-check"></i> Provision Database');
            }
        });
    });
    // === Advanced Database Modal Logic ===
    
    // Auto Password Generator
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

    // Toggle Remote Access Input
    $(document).on('change', '#dbAcl', function() {
        if($(this).val() === 'custom') {
            $('#dbCustomIp').removeClass('d-none').prop('required', true);
        } else {
            $('#dbCustomIp').addClass('d-none').prop('required', false);
        }
    });

    // Toggle Custom Privileges Grid
    $(document).on('change', '#dbRole', function() {
        if($(this).val() === 'custom') {
            $('#customPrivilegesGrid').removeClass('d-none');
        } else {
            $('#customPrivilegesGrid').addClass('d-none');
        }
    });
    // Install SSL Form Submission
    $('#installSslForm').on('submit', function(e) {
        e.preventDefault();
        
        let btn = $('#submitSslBtn');
        let alertBox = $('#sslFormAlert');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Communicating with Let\'s Encrypt...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/install_ssl.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.addClass('alert-success').text(response.message).removeClass('d-none');
                    $('#installSslForm')[0].reset();
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
            },
            error: function() {
                alertBox.addClass('alert-danger').text('A server error occurred.').removeClass('d-none');
            },
            complete: function() {
                btn.prop('disabled', false).text('Secure Domain (HTTPS)');
            }
        });
    });
    // Change PHP Form Submission
    $('#changePhpForm').on('submit', function(e) {
        e.preventDefault();
        
        let btn = $('#submitPhpBtn');
        let alertBox = $('#phpFormAlert');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Reconfiguring Nginx...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/change_php.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.addClass('alert-success').text(response.message).removeClass('d-none');
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
            },
            error: function() {
                alertBox.addClass('alert-danger').text('A server error occurred.').removeClass('d-none');
            },
            complete: function() {
                btn.prop('disabled', false).text('Update PHP Version');
            }
        });
    });
    // Firewall Form Submission
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
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
            },
            error: function() {
                alertBox.addClass('alert-danger').text('A server error occurred.').removeClass('d-none');
            },
            complete: function() {
                btn.prop('disabled', false).text('Allow Port');
            }
        });
    });
    // Live System Health Monitor (Heartbeat)
    function fetchSystemStats() {
        $.ajax({
            url: '/ajax/system_stats.php',
            type: 'POST',
            dataType: 'json',
            success: function(data) {
                // Update CPU (Assume a load of 2.0 is "100%" for a basic 2-core VM visualization)
                let cpuVisualPercent = (data.cpu_load / 2.0) * 100;
                if(cpuVisualPercent > 100) cpuVisualPercent = 100;
                
                $('#cpuBar').css('width', cpuVisualPercent + '%');
                $('#cpuText').text(data.cpu_load);

                // Update RAM
                $('#ramBar').css('width', data.ram_percent + '%');
                $('#ramText').text(data.ram_used + ' / ' + data.ram_total + ' MB (' + data.ram_percent + '%)');

                // Update Disk
                $('#diskBar').css('width', data.disk_percent + '%');
                $('#diskText').text(data.disk_used + ' / ' + data.disk_total + ' GB (' + data.disk_percent + '%)');

                // Color logic (Turn red if usage gets dangerously high)
                if(data.ram_percent > 85) { $('#ramBar').removeClass('bg-info').addClass('bg-danger'); } 
                else { $('#ramBar').removeClass('bg-danger').addClass('bg-info'); }

                if(data.disk_percent > 90) { $('#diskBar').removeClass('bg-warning').addClass('bg-danger'); } 
                else { $('#diskBar').removeClass('bg-danger').addClass('bg-warning'); }
            }
        });
    }
    // === DYNAMIC PAGINATION GLOBALS ===
    let currentTaskPage = 1;
    let taskLimit = 5;

    // Fetch Dynamic Tasks with Pagination
    function fetchRecentTasks() {
        $.ajax({
            url: '/ajax/get_tasks.php',
            type: 'POST',
            data: { page: currentTaskPage, limit: taskLimit },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    let tbody = $('#dynamicTasksTable');
                    tbody.empty(); // Clear old data
                    
                    if(response.tasks.length === 0) {
                        tbody.html('<tr><td colspan="6" class="text-center text-muted py-3">No system tasks found.</td></tr>');
                        $('#taskPaginationContainer').empty();
                        return;
                    }

                    // Loop and render rows (Keeping your exact existing row HTML)
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

                    // ---> NEW: Render the Pagination UI <---
                    renderTaskPagination(response.pagination);
                }
            }
        });
    }

    // Generate the Pagination UI dynamically
    function renderTaskPagination(p) {
        let container = $('#taskPaginationContainer');
        
        // Inject the container right after the table if it doesn't exist yet
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

        // Previous Button
        pageHtml += `<li class="page-item ${p.current_page == 1 ? 'disabled' : ''}">
            <a class="page-link task-page-link" href="#" data-page="${p.current_page - 1}">Prev</a></li>`;

        // Page Numbers
        for (let i = 1; i <= p.total_pages; i++) {
            pageHtml += `<li class="page-item ${p.current_page == i ? 'active' : ''}">
                <a class="page-link task-page-link" href="#" data-page="${i}">${i}</a></li>`;
        }

        // Next Button
        pageHtml += `<li class="page-item ${p.current_page == p.total_pages ? 'disabled' : ''}">
            <a class="page-link task-page-link" href="#" data-page="${p.current_page + 1}">Next</a></li>`;

        pageHtml += `</ul></div>`;
        
        container.html(pageHtml);
    }

    // Pagination Click Listener
    $(document).on('click', '.task-page-link', function(e) {
        e.preventDefault();
        if($(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) return;
        currentTaskPage = $(this).data('page');
        fetchRecentTasks();
    });

    // Dropdown Change Listener
    $(document).on('change', '#taskLimitSelect', function() {
        taskLimit = $(this).val();
        currentTaskPage = 1; // Reset to page 1 when changing limits
        fetchRecentTasks();
    });
    // Open Task Log Terminal
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
                } else {
                    alert("Error fetching log: " + response.error);
                }
            },
            error: function() {
                btn.html(originalIcon);
                alert("Network error.");
            }
        });
    });
    // Run immediately, then check for new tasks every 5 seconds
    fetchRecentTasks();
    setInterval(fetchRecentTasks, 5000);
    // === DYNAMIC PHP VERSION LOADER ===
    function loadPhpVersions() {
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
    }

    // Trigger the fetch on load
    loadPhpVersions();
    // === SOFTWARE CENTER MANAGER ===
    function renderSoftwareCenter() {
        // The definitive list of PHP versions supported by the Ondrej PPA
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
    }

    // Bind the execution clicks for the Software Center
    $(document).on('click', '.software-action-btn', function() {
        let action = $(this).data('action');
        let version = $(this).data('version');
        
        if(confirm(`Are you sure you want to ${action} PHP ${version}? This will run in the background.`)) {
            
            let btn = $(this);
            let originalText = btn.html();
            // Show a quick loading spinner on the button itself
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Queueing...');

            $.ajax({
                url: '/ajax/install_php.php', // ---> FIX: Pointing to the real endpoint!
                type: 'POST',
                data: {
                    sub_action: action,
                    version: version
                },
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        // 1. Close the Software Center Modal
                        $('#softwareCenterModal').modal('hide');
                        
                        // 2. Instantly switch the UI back to the Overview Dashboard tab
                        $('#overview-tab').tab('show');
                        
                        // 3. Force a task table refresh so the user sees it immediately!
                        if (typeof fetchRecentTasks === "function") {
                            fetchRecentTasks();
                        }
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

    // Refresh the table every time the modal is opened
    $('#softwareCenterModal').on('show.bs.modal', function () {
        renderSoftwareCenter();
    });
    // Run it immediately on page load, then every 3 seconds (3000ms)
    fetchSystemStats();
    setInterval(fetchSystemStats, 3000);
    // Git Clone Form Submission
    $('#gitForm').on('submit', function(e) {
        e.preventDefault();
        
        let btn = $('#submitGitBtn');
        let alertBox = $('#gitAlert');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Cloning Repository...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/clone_repo.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alertBox.addClass('alert-success').text(response.message).removeClass('d-none');
                    $('#gitForm')[0].reset();
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
            },
            complete: function() {
                btn.prop('disabled', false).text('Deploy Repository');
            }
        });
    });
    // Fetch SSH Key (Updated to POST for Security)
    $('#fetchSshBtn').on('click', function() {
        let btn = $(this);
        // Ensure you have a hidden input with id="sshUsername" in your HTML modal!
        let targetUser = $('#sshUsername').val(); 

        if(!targetUser) {
            alert("Error: Username is missing from the UI.");
            return;
        }

        btn.prop('disabled', true).text('Loading...');

        $.ajax({
            url: '/ajax/get_ssh_key.php',
            type: 'POST', // Changed from GET
            data: { username: targetUser }, // Send dynamic username in body
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
    // DNS Record Form Submission
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
                    if(action === 'add') $('#dnsRecordForm')[0].reset(); // Only reset on add
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
            },
            complete: function() {
                btn.prop('disabled', false).text('Execute Change');
            }
        });
    });
    // Fetch Domains & Git Data
    function fetchDomains() {
        $.ajax({
            url: '/ajax/get_domains.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    let tbody = $('#dynamicDomainsTable');
                    tbody.empty();
                    if(response.domains.length === 0) {
                        tbody.html('<tr><td colspan="5" class="text-center text-muted py-3">No domains configured.</td></tr>');
                        return;
                    }
                    response.domains.forEach(function(d) {
                        let sslBadge = d.has_ssl == 1 ? '<span class="badge bg-success"><i class="bi bi-lock"></i> Secured</span>' : '<span class="badge bg-secondary">Unsecured</span>';
                        
                        // WAF Toggle & Settings Logic
                        let wafBadge = d.waf_enabled == 1 
                            ? `<div class="btn-group"><button class="btn btn-sm btn-success toggle-waf" data-domain="${d.domain_name}" data-action="off" title="Disable WAF"><i class="bi bi-shield-check"></i> ON</button>
                               <button class="btn btn-sm btn-dark edit-waf-rules" data-domain="${d.domain_name}" data-rules="${btoa(d.waf_custom_rules || '')}" title="Custom Rules"><i class="bi bi-gear"></i></button></div>` 
                            : `<button class="btn btn-sm btn-outline-danger toggle-waf" data-domain="${d.domain_name}" data-action="on" title="Enable WAF"><i class="bi bi-shield-x"></i> OFF</button>`;

                        // Git Logic (keeping your existing code)
                        let gitDisplay = '<span class="text-muted small">None</span>';
                        if (d.git_repo !== 'Not Configured') {
                            let host = window.location.hostname;
                            let webhookUrl = `https://${host}:7443/ajax/webhook.php?domain=${d.domain_name}&token=${d.webhook_token}`;
                            let currentBranch = d.git_branch || 'main'; 

                            // === RENDER COMMIT HISTORY ===
                            let commitsHtml = '';
                            if (d.latest_commits) {
                                try {
                                    let commits = JSON.parse(d.latest_commits);
                                    commitsHtml = '<div class="mt-2 border-top pt-2"><h6 class="small fw-bold text-muted mb-1">Latest Commits</h6><ul class="list-unstyled mb-0" style="font-size: 0.75rem;">';
                                    
                                    commits.forEach(c => {
                                        commitsHtml += `
                                            <li class="mb-1 text-truncate" title="${c.message}">
                                                <span class="text-primary font-monospace me-2">${c.commit}</span>
                                                <span class="text-muted me-2">${c.date}</span>
                                                ${c.message}
                                            </li>`;
                                    });
                                    commitsHtml += '</ul></div>';
                                } catch(e) { console.error("Could not parse commits JSON", e); }
                            }

                            gitDisplay = `
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold"><i class="bi bi-github"></i> ${d.git_repo}</div>
                                    <span class="badge bg-secondary font-monospace"><i class="bi bi-code-branch"></i> ${currentBranch}</span>
                                </div>
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-light border-secondary text-muted" title="Webhook URL"><i class="bi bi-lightning-charge-fill"></i></span>
                                    <input type="text" class="form-control border-secondary text-muted font-monospace" style="font-size: 0.7rem;" value="${webhookUrl}" readonly onclick="this.select(); document.execCommand('copy'); alert('Webhook URL copied!');">
                                </div>
                                <button class="btn btn-sm btn-dark w-100 manual-git-pull mb-2" data-domain="${d.domain_name}" data-user="${d.username}" data-branch="${currentBranch}">
                                    <i class="bi bi-arrow-down-circle"></i> Pull Now
                                </button>
                                ${commitsHtml}
                            `;
                        }

                        // === DYNAMIC FILE MANAGER BUTTONS ===
                        let proto = d.has_ssl == 1 ? 'https' : 'http';
                        // === DYNAMIC FILE MANAGER BUTTONS ===
                        let fmButtons = `
                            <div class="btn-group ms-1">
                                <button class="btn btn-sm btn-warning deploy-fm" data-domain="${d.domain_name}" data-user="${d.username}" data-ver="${d.php_version}" title="Deploy / Reset File Manager"><i class="bi bi-cloud-arrow-up-fill"></i></button>
                                <button class="btn btn-sm btn-outline-dark open-fm-sso" data-domain="${d.domain_name}" title="Open File Manager"><i class="bi bi-folder2-open"></i></button>
                                <button class="btn btn-sm btn-dark rotate-fm-pass" data-domain="${d.domain_name}" data-user="${d.username}" title="Rotate FM Password"><i class="bi bi-key"></i></button>
                            </div>
                        `;

                        let row = `
                            <tr>
                                <td class="fw-bold align-middle">
                                    ${d.domain_name} 
                                    <button class="btn btn-sm btn-link text-primary p-0 ms-2 show-connection-info" data-domain="${d.domain_name}" title="Connection Info"><i class="bi bi-info-circle-fill fs-5"></i></button>
                                    <button class="btn btn-sm btn-link text-secondary p-0 ms-1 edit-php-settings" data-json='${JSON.stringify(d).replace(/'/g, "&apos;")}' title="PHP Settings"><i class="bi bi-sliders fs-5"></i></button>
                                    ${fmButtons}
                                    <button class="btn btn-sm btn-link text-primary p-0 ms-1 open-wp-modal" data-domain="${d.domain_name}" data-user="${d.username}" title="1-Click Install WordPress"><i class="bi bi-wordpress fs-5"></i></button>
                                    <button class="btn btn-sm btn-link text-success p-0 ms-1 open-node-modal" data-domain="${d.domain_name}" data-user="${d.username}" title="Node.js Deployment"><i class="bi bi-hexagon-fill fs-5"></i></button>
                                    <button class="btn btn-sm btn-link text-info p-0 ms-1 manage-ftp" data-domain="${d.domain_name}" data-user="${d.username}" title="Manage FTP Accounts"><i class="bi bi-hdd-network-fill fs-5"></i></button>
                                    <button class="btn btn-sm btn-link text-warning p-0 ms-1 manage-mail" data-domain="${d.domain_name}" title="Manage Mailboxes"><i class="bi bi-envelope-at-fill fs-5"></i></button>
                                    <button class="btn btn-sm btn-link text-danger p-0 ms-2 delete-domain" data-domain="${d.domain_name}" data-user="${d.username}" title="Permanently Delete Domain"><i class="bi bi-trash-fill fs-5"></i></button>
                                </td>
                                <td class="align-middle"><i class="bi bi-person text-muted"></i> ${d.username}</td>
                                <td class="align-middle"><span class="badge bg-info text-dark">v${d.php_version}</span></td>
                                <td class="align-middle" style="max-width: 300px;">${gitDisplay}</td>
                                <td class="align-middle">${sslBadge}<br><div class="mt-1">${wafBadge}</div></td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                    // ---> NEW: Populate all domain dropdowns <---
                    let domainDropdowns = $('.domain-dropdown');
                    domainDropdowns.empty().append('<option value="">Select a Domain...</option>');
                    response.domains.forEach(function(d) {
                        domainDropdowns.append('<option value="' + d.domain_name + '">' + d.domain_name + '</option>');
                    });
                }
            }
        });
    }
    // === SCORCHED EARTH: Delete Domain ===
    $(document).on('click', '.delete-domain', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        
        // Strict safety check
        let confirmText = prompt(`CRITICAL WARNING: This will permanently destroy the Nginx configuration, SSL certificates, and all website files for '${domain}'.\n\nType the domain name below to confirm:`);
        
        if (confirmText !== domain) {
            if (confirmText !== null) alert("Domain name did not match. Deletion aborted.");
            return;
        }
        
        let btn = $(this);
        let originalIcon = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/delete_domain.php',
            type: 'POST',
            data: { domain: domain, username: user },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Wait 3 seconds for Python to wipe the server, then refresh the UI
                    setTimeout(fetchDomains, 3000); 
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false).html(originalIcon);
                }
            }
        });
    });
    // === Cryptographic Cross-Domain SSO Click ===
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
            success: function(response) {
                if(response.success) {
                    // Open the secure URL in a new tab
                    window.open(response.url, '_blank');
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalIcon);
            }
        });
    });
    // === Manual Git Pull Logic ===
    $(document).on('click', '.manual-git-pull', function() {
        let btn = $(this);
        let domain = btn.data('domain');
        let user = btn.data('user');
        let branch = btn.data('branch');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Pulling...');

        $.ajax({
            url: '/ajax/manual_git_pull.php', // You will need to create this endpoint to trigger the TaskQueue!
            type: 'POST',
            data: { domain: domain, username: user, branch: branch },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert("Git Pull Queued! Check the Live Tasks log for status.");
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-arrow-down-circle"></i> Pull Now');
            }
        });
    });
    // 1. Open Modern PHP Settings Modal & Populate 23 Fields
    $(document).on('click', '.edit-php-settings', function() {
        let d = $(this).data('json');
        
        $('#phpDomainTitle').text(d.domain_name);
        $('#psDomain').val(d.domain_name);
        $('#psUser').val(d.username);
        $('#psVer').val(d.php_version);
        
        // Populate all 23 fields from the database (using logical defaults if null)
        $('#ps_mem').val(d.php_memory_limit || '128M');
        $('#ps_max_exec').val(d.php_max_exec_time || 30);
        $('#ps_max_in').val(d.php_max_input_time || 60);
        $('#ps_post').val(d.php_post_max_size || '8M');
        $('#ps_up').val(d.php_upload_max_filesize || '2M');
        $('#ps_opc').val(d.php_opcache_enable || 'on');
        $('#ps_dis').val(d.php_disable_functions || '');
        
        $('#ps_inc').val(d.php_include_path || '.:/opt/plesk/php/8.3/share/pear');
        $('#ps_sess').val(d.php_session_save_path || '/var/lib/php/sessions');
        $('#ps_mail').val(d.php_mail_params || '');
        $('#ps_open').val(d.php_open_basedir || '{WEBSPACEROOT}{/}{:}{TMP}{/}');
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
    // 1. Open FTP Modal
    $(document).on('click', '.manage-ftp', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        
        $('#ftpDomainTitle').text(domain);
        $('#ftpDomain').val(domain);
        $('#ftpSysUser').val(user);
        $('#ftpSuffix').text('@' + domain);
        
        // Reset form to "Create" mode
        $('#ftpForm')[0].reset();
        $('#ftpAction').val('create');
        $('#ftpUserInput').prop('readonly', false);
        $('#deleteFtpBtn').addClass('d-none');
        $('#saveFtpBtn').removeClass('w-100'); // Make room for delete button if needed later
        
        $('#ftpModal').modal('show');
    });

    // 2. Auto Password Generator
    $('#generateFtpPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#ftpPassInput').val(pass);
        
        // Auto-copy to clipboard
        navigator.clipboard.writeText(pass);
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
    });

    // 3. Save / Update FTP User
    $('#saveFtpBtn').click(function() {
        let btn = $(this);
        let form = $('#ftpForm');
        
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        // Append domain to username to ensure global server uniqueness (e.g., user@domain.com)
        let rawUser = $('#ftpUserInput').val();
        if (!rawUser.includes('@')) {
            $('#ftpUserInput').val(rawUser + $('#ftpSuffix').text());
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        $.ajax({
            url: '/ajax/manage_ftp.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#ftpModal').modal('hide');
                    alert("FTP Account saved successfully.");
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save FTP Account');
            }
        });
    });
    // Open File Manager Modal
    $(document).on('click', '.deploy-fm', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        
        $('#fmDomainTitle').text(domain);
        $('#fmDomain').val(domain);
        $('#fmUser').val(user);
        $('#fmVer').val($(this).data('ver'));
        $('#fmUserDisplay').val(user); // TFM uses the system username for login
        
        $('#fileManagerModal').modal('show');
    });

    // Submit File Manager Deployment
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
            success: function(response) {
                if(response.success) {
                    $('#fileManagerModal').modal('hide');
                    form[0].reset();
                    alert("Deployment Queued. The File Manager will be available at " + $('#fmDomain').val() + "/filemanager in a few seconds.");
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-up"></i> Deploy TFM');
            }
        });
    });
    // 2. Submit PHP Settings via AJAX
    $('#savePhpSettingsBtn').click(function() {
        let btn = $(this);
        let formData = $('#phpSettingsForm').serialize();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Applying to Server...');

        $.ajax({
            url: '/ajax/manage_php.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#phpSettingsModal').modal('hide');
                    setTimeout(fetchDomains, 1000); 
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save & Restart FPM');
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
    // Fetch Firewall Rules
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
                        let row = `
                            <tr>
                                <td class="fw-bold">${r.port}</td>
                                <td class="text-uppercase">${r.protocol}</td>
                                <td><span class="badge bg-success">ALLOW</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger delete-fw" data-port="${r.port}" data-proto="${r.protocol}" title="Close Port"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
            }
        });
    }

    // Run them immediately
    fetchDomains();
    fetchFirewall();
    // Fetch Users Data
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
                    // ---> NEW: Populate all user dropdowns <---
                    let userDropdowns = $('.user-dropdown');
                    userDropdowns.empty().append('<option value="">Select a User...</option>');
                    response.users.forEach(function(u) {
                        userDropdowns.append('<option value="' + u.username + '">' + u.username + '</option>');
                    });
                }
            }
        });
    }

    // Fetch Databases Data
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
                        // Empty the dropdowns too
                        $('.db-dropdown').empty().append('<option value="">No databases available</option>');
                        return;
                    }
                    
                    // 1. Populate the UI Table
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

                    // 2. ---> NEW: Populate all Database Dropdowns <---
                    let dbDropdowns = $('.db-dropdown');
                    dbDropdowns.empty().append('<option value="">Select a Database...</option>');
                    response.databases.forEach(function(db) {
                        dbDropdowns.append('<option value="' + db.db_name + '">' + db.db_name + '</option>');
                    });
                }
            }
        });
    }
    // === Change Database Password Logic ===

    // 1. Open the Modal
    $(document).on('click', '.change-db-pass', function() {
        let dbUser = $(this).data('user');
        
        $('#editDbUserHidden').val(dbUser);
        $('#editDbUserDisplay').val(dbUser);
        $('#editDbPassInput').val(''); // Clear old passwords
        
        $('#changeDbPassModal').modal('show');
    });

    // 2. Generate New Password
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

    // 3. Submit the Form
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
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save New Password');
            }
        });
    });
    // === Delete Database Logic ===
    $(document).on('click', '.delete-db', function() {
        let dbName = $(this).data('db');
        
        // Critical safety check before deleting data
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
                    // Wait 2.5 seconds for Python to drop the tables, then refresh the UI
                    setTimeout(fetchDatabases, 2500); 
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                }
            },
            error: function() {
                alert("A server error occurred.");
                btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
            }
        });
    });
    // Run them immediately
    fetchUsers();
    fetchDatabases();
    // Fetch DNS Records Data
    function fetchDnsRecords() {
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

                        // Note: We use btoa() to safely encode the value in case it contains spaces or quotes (like TXT records)
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
    }
    // === Delete Firewall Rule ===
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
            success: function() {
                setTimeout(fetchFirewall, 2500); // Refresh table after Python processes it
            }
        });
    });

    // === Delete DNS Record ===
    $(document).on('click', '.delete-dns', function() {
        let domain = $(this).data('domain');
        let name = $(this).data('name');
        let type = $(this).data('type');
        let val = atob($(this).data('val')); // Decode the value safely
        
        if(!confirm(`Are you sure you want to delete this ${type} record (${name}.${domain})?`)) return;
        
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_dns_records.php',
            type: 'POST',
            data: { action: 'delete', domain: domain, name: name, type: type, value: val },
            success: function() {
                setTimeout(fetchDnsRecords, 2500); // Refresh table after Python processes it
            }
        });
    });
    // Run immediately
    fetchDnsRecords();
    // Fetch Cron Data
    function fetchCronJobs() {
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
    }

    // Submit New Cron Job
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
                    setTimeout(fetchCronJobs, 3000);
                } else {
                    alert("Error: " + res.error);
                }
                btn.prop('disabled', false).text('Save Cron Job');
            }
        });
    });

    // Delete Cron Job
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
            success: function(response) {
                setTimeout(fetchCronJobs, 3000);
            }
        });
    });

    // Initial Fetch call
    fetchCronJobs();
    // 1. Open Connection Info Modal
    $(document).on('click', '.show-connection-info', function() {
        let domain = $(this).data('domain');
        let btn = $(this);
        
        // Visual loading feedback on the little icon
        let originalIcon = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm text-primary"></span>');

        $.ajax({
            url: '/ajax/get_connection_info.php',
            type: 'POST',
            data: { domain: domain },
            dataType: 'json',
            success: function(response) {
                btn.html(originalIcon); // Restore icon
                
                if(response.success) {
                    let d = response.data;
                    
                    // Populate Modal Fields
                    $('#infoDomainTitle').text(d.domain);
                    $('#infoIp').text(d.server_ip);
                    $('#infoUser').text(d.username);
                    $('#infoSsh').text(d.ssh_command);
                    $('#infoWebRoot').text(d.web_root);
                    $('#infoNginx').text(d.nginx_conf);
                    $('#infoPhpSock').text(d.php_socket);
                    $('#infoDbHost').text(d.db_host);
                    
                    // Show Modal
                    $('#connectionInfoModal').modal('show');
                } else {
                    alert("Error: " + response.error);
                }
            },
            error: function() {
                btn.html(originalIcon);
                alert("Network error fetching connection info.");
            }
        });
    });

    // 2. Universal Copy to Clipboard Button Logic
    $(document).on('click', '.copy-btn', function() {
        let targetId = $(this).data('target');
        let textToCopy = $('#' + targetId).text();
        let btn = $(this);
        
        navigator.clipboard.writeText(textToCopy).then(() => {
            // Momentarily change icon to a checkmark
            let originalIcon = btn.html();
            btn.html('<i class="bi bi-check2 text-success"></i>');
            setTimeout(() => { btn.html(originalIcon); }, 1500);
        });
    });

 // Live Log Viewer Logic
    let logInterval;

    // When the modal opens, fetch logs and start the 2-second timer
    $('#logModal').on('show.bs.modal', function () {
        fetchLogs();
        logInterval = setInterval(fetchLogs, 2000); 
    });

    // When the modal closes, kill the timer to save CPU
    $('#logModal').on('hide.bs.modal', function () {
        clearInterval(logInterval); 
    });

    // If they swap between access/error logs, fetch immediately
    $('#logType').on('change', function() {
        $('#logTerminal').html('Loading...');
        fetchLogs();
    });

    function fetchLogs() {
        let type = $('#logType').val();
        // Ensure you have hidden inputs with these IDs in your Log Modal!
        let domain = $('#logDomain').val(); 
        let user = $('#logUser').val();

        $.ajax({
            url: '/ajax/get_logs.php',
            type: 'POST', // Changed from GET
            data: { 
                type: type,
                domain: domain,
                username: user
            },
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

                if(isAtBottom) {
                    terminal.scrollTop(terminal[0].scrollHeight);
                }
            }
        });
    }
    // === BACKUP SYSTEM LOGIC ===

    // 1. Fetch Vault Contents
    function fetchBackups() {
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
            },
            error: function(xhr, status, error) {
                $('#dynamicBackupsTable').html('<tr><td colspan="5" class="text-center text-danger py-3">Error loading backups. Check server logs.</td></tr>');
                console.error("Backup Fetch Error:", xhr.responseText);
            }
        });
    }
    // === Fetch Active Schedules ===
    function fetchSchedules() {
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
    }

    // === Delete a Schedule ===
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
                if(response.success) {
                    fetchSchedules(); // Refresh instantly
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                }
            }
        });
    });

    // Make sure to call this alongside fetchBackups() so it loads on page boot!
    fetchSchedules();
    // === 1-Click Restore Logic ===
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
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    // === Delete Backup Logic ===
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
                if(response.success) {
                    // Refresh the table immediately to show it's gone
                    fetchBackups(); 
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false).html(originalText);
                }
            }
        });
    });
    // 2. Submit Backup Form (Works for both Web and DB)
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
                    // Auto-refresh the vault after 5 seconds to show the new file
                    setTimeout(fetchBackups, 5000); 
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).text('Generate Archive');
            }
        });
    });

    // Load backups safely AFTER the CSRF token is attached
    fetchBackups();
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
                } else {
                    alert("Error: " + response.error);
                    toggleBtn.prop('checked', !isChecked); 
                }
                toggleBtn.prop('disabled', false); 
            },
            error: function() {
                alert("Network Error.");
                toggleBtn.prop('checked', !isChecked); 
                toggleBtn.prop('disabled', false);
            }
        });
    });

    // Open Rotate FM Password Modal
    $(document).on('click', '.rotate-fm-pass', function() {
        $('#rotateFmDomainTitle').text($(this).data('domain'));
        $('#rotateFmDomain').val($(this).data('domain'));
        $('#rotateFmUser').val($(this).data('user'));
        $('#rotateFmPassInput').val(''); 
        $('#rotateFmPassModal').modal('show');
    });
    // Toggle Schedule Target Dropdown based on Type
    $('#schedType').on('change', function() {
        if($(this).val() === 'web') {
            $('#schedTargetWeb').removeClass('d-none').prop('required', true);
            $('#schedTargetDb').addClass('d-none').prop('required', false);
        } else {
            $('#schedTargetWeb').addClass('d-none').prop('required', false);
            $('#schedTargetDb').removeClass('d-none').prop('required', true);
        }
    });

    // Submit Schedule
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
                    fetchSchedules();
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).text('Save Schedule');
            }
        });
    });

    // Upload Backup File
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
                // Ensure response is parsed JSON if it comes back as string
                let res = typeof response === 'string' ? JSON.parse(response) : response;
                if(res.success) {
                    $('#uploadBackupModal').modal('hide');
                    form.reset();
                    $('#uploadProgress').addClass('d-none');
                    fetchBackups(); // Refresh the vault instantly
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
    
 // ===========================
    // Auto-Generate FM Password
    $('#generateRotateFmPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#rotateFmPassInput').val(pass);
        navigator.clipboard.writeText(pass);
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
    });
    // Submit Rotate FM Password    
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
            success: function(response) {
                if(response.success) {
                    $('#rotateFmPassModal').modal('hide');
                    alert("File Manager password updated successfully!");
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Update Key');
            }
        });
    });
    // === Global Server Settings (PMA Uploads) ===
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
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Apply Globally');
            }
        });
    });
    // === Secure Panel SSL Automation ===
    $('#submitSecurePanelBtn').click(function() {
        let btn = $(this);
        let form = $('#securePanelForm');
        let alertBox = $('#securePanelAlert');
        
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        let warning = "Warning: This will lock the oPanel to the new domain and restart the web server. You will be automatically redirected. Proceed?";
        if(!confirm(warning)) return;

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Provisioning SSL & Reloading Nginx...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/secure_panel.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    let targetDomain = form.find('input[name="domain"]').val();
                    alertBox.addClass('alert-success').text("Success! Redirecting to your new secure domain in 5 seconds...").removeClass('d-none');
                    
                    // Redirect the user to the new secure domain automatically
                    setTimeout(function() {
                        window.location.href = "https://" + targetDomain + ":7443";
                    }, 5000);

                } else {
                    alertBox.addClass('alert-danger').text("Error: " + response.error).removeClass('d-none');
                    btn.prop('disabled', false).html('<i class="bi bi-lock-fill"></i> Secure oPanel');
                }
            },
            error: function() {
                alertBox.addClass('alert-danger').text("A network error occurred.").removeClass('d-none');
                btn.prop('disabled', false).html('<i class="bi bi-lock-fill"></i> Secure oPanel');
            }
        });
    });

    // === 1-CLICK WORDPRESS LOGIC ===
    // 1. Open the Modal
    $(document).on('click', '.open-wp-modal', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        
        $('#wpDomain').val(domain);
        $('#wpUser').val(user);
        $('#wpEmailInput').val('admin@' + domain); // Smart default
        $('#installWpForm')[0].reset();
        $('#wpPassInput').val('');
        
        $('#installWpModal').modal('show');
    });

    // 2. Generate Password
    $('#generateWpPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 20; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#wpPassInput').val(pass);
        
        navigator.clipboard.writeText(pass);
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
    });

    // 3. Submit the Form
    $('#submitInstallWpBtn').click(function() {
        let btn = $(this);
        let form = $('#installWpForm');
        
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Initializing WP-CLI...');

        $.ajax({
            url: '/ajax/install_wp.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#installWpModal').modal('hide');
                    alert("WordPress installation queued! Check the Live Tasks log to watch the progress.");
                    // Switch to Overview tab to watch the task automatically
                    $('#overview-tab').tab('show'); 
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-down"></i> Install WordPress');
            }
        });
    });
    // === NODE.JS DEPLOYMENT LOGIC ===
    $(document).on('click', '.open-node-modal', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        
        $('#nodeDomain').val(domain);
        $('#nodeUser').val(user);
        $('#nodeJsForm')[0].reset();
        
        $('#nodeJsModal').modal('show');
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
            success: function(response) {
                if(response.success) {
                    $('#nodeJsModal').modal('hide');
                    alert("Node.js Deployment Queued! Check Live Tasks to watch PM2 start the app.");
                    $('#overview-tab').tab('show');
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-rocket-takeoff"></i> Launch App via PM2');
            }
        });
    });
    // 3. PM2 Action Buttons (Restart, Stop, NPM Install)
    $('.node-action-btn').click(function() {
        let btn = $(this);
        let action = btn.data('action');
        let domain = $('#nodeDomain').val();
        let username = $('#nodeUser').val();
        let app_root = $('input[name="app_root"]').val();
        
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Queuing...');

        $.ajax({
            url: '/ajax/node_action.php',
            type: 'POST',
            data: {
                domain: domain,
                username: username,
                app_root: app_root,
                sub_action: action
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#nodeJsModal').modal('hide');
                    alert("Command Sent! Check Live Tasks for execution status.");
                    $('#overview-tab').tab('show');
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
 // === Admin Profile Password Change ===
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
                    setTimeout(function() { window.location.href = '/logout.php'; }, 3000);
                } else {
                    alertBox.removeClass('d-none alert-success').addClass('alert-danger').text(response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Update Password');
            }
        });
    });
    // === Global Timezone Sync ===
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
                    $('#overview-tab').tab('show'); // Switch to overview to watch the task
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).text('Sync Server Time');
            }
        });
    });
    // === Delete User Logic ===
    $(document).on('click', '.delete-user', function() {
        let user = $(this).data('user');
        
        if(!confirm(`CRITICAL WARNING: Are you sure you want to permanently delete '${user}' and destroy their home directory? \n\nNOTE: You MUST delete their domains from the Web tab first! `)) return;
        
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/delete_user.php',
            type: 'POST',
            data: { username: user },
            success: function() {
                setTimeout(fetchUsers, 3000); 
            }
        });
    });
    // === MAIL SERVER LOGIC ===

    // 1. Open the Modal & Check Engine Status
    $(document).on('click', '.manage-mail', function() {
        let domain = $(this).data('domain');
        
        $('#mailDomainTitle').text(domain);
        $('#mailDomain').val(domain);
        $('#mailSuffixLabel').text('@' + domain);
        $('#createMailForm')[0].reset();
        $('#mailAlert').addClass('d-none');
        
        // Hide both states initially to prevent UI flashing
        $('#mailEngineNotInstalled').addClass('d-none');
        $('#mailEngineInstalled').addClass('d-none');
        
        // Ask PHP if Postfix is installed
        $.ajax({
            url: '/ajax/get_mail_engine_status.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.installed) {
                    $('#mailEngineInstalled').removeClass('d-none');
                    fetchMailboxes(domain); // Only fetch DB if engine exists
                } else {
                    $('#mailEngineNotInstalled').removeClass('d-none');
                }
                $('#mailBoxModal').modal('show');
            }
        });
    });
    // 1.5 Trigger Mail Engine Installation
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
                    $('#overview-tab').tab('show'); // Switch to overview to watch the task
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html('<i class="bi bi-download"></i> Install Mail Engine');
            }
        });
    });
    // 1.6 Trigger Mail Engine Uninstallation
    $('#uninstallMailEngineBtn').click(function() {
        let btn = $(this);
        
        // Strict confirmation prompt to prevent accidental data loss
        let confirmText = prompt("CRITICAL WARNING: This will permanently destroy all physical mailboxes on this server and uninstall Postfix/Dovecot. Type 'PURGE' to confirm:");
        
        if(confirmText !== 'PURGE') {
            if(confirmText !== null) alert("Aborted. You must type exactly PURGE.");
            return;
        }
        
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
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).text('Uninstall Engine');
            }
        });
    });

    // 2. Fetch the Source of Truth from DB
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

    // 3. Auto Password Generator
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

    // 4. Provision New Mailbox
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
                    // Auto-refresh the table after 2.5 seconds to show the new email!
                    setTimeout(() => fetchMailboxes(domain), 2500);
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Provision Mailbox');
            }
        });
    });

    // 5. Delete Mailbox
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
                if(response.success) {
                    setTimeout(() => fetchMailboxes(domain), 2000);
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                }
            }
        });
    });
    // 6. External DNS Routing (Google / Microsoft)
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
                    $('#overview-tab').tab('show'); // Switch to overview to watch the task run
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    // === Initialize Default BIND9 Zone ===
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
                    // Auto-refresh the DNS table after 3 seconds so you see the new Source of Truth data!
                    setTimeout(fetchDnsRecords, 3000); 
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
});