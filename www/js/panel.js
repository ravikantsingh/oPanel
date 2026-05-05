$(document).ready(function() {
    // Inject CSS pointer for both log terminals
    $('<style>').prop('type', 'text/css').html(`
        #logTaskOutput, #logTerminal { cursor: pointer; transition: opacity 0.2s; }
        #logTaskOutput:hover, #logTerminal:hover { opacity: 0.8; }
    `).appendTo('head');
    // === GLOBAL CSRF INTERCEPTOR ===
    // Grabs the token from the <meta> tag and attaches it to all AJAX headers
    let csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    });
    // =================================================================
    // TAB STATE PERSISTENCE (URL HASH METHOD)
    // =================================================================

    // 1. On page load: Read the URL hash and open the matching tab
    let activeHash = window.location.hash;
    if (activeHash) {
        // Find the physical tab button that corresponds to the hash
        let targetTab = $('button[data-bs-target="' + activeHash + '"], a[href="' + activeHash + '"]');
        
        if (targetTab.length) {
            targetTab.tab('show'); // Trigger Bootstrap to show the tab
            
            // Sync the dark sidebar menu highlight on the left
            let tabId = targetTab.attr('id');
            $('.sidebar a').removeClass('active');
            $('.sidebar a[onclick*="' + tabId + '"]').addClass('active');
        }
    }

    // 2. On tab click: Update the URL hash silently
    $('button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        // Grab the ID of the tab that was just opened (e.g., '#security')
        let target = $(e.target).attr('data-bs-target') || $(e.target).attr('href');
        
        // Use replaceState to change the URL without causing the screen to "jump" down
        if(history.replaceState) {
            history.replaceState(null, null, target);
        } else {
            window.location.hash = target;
        }
    });
    // === Create User Form Submission ===
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault(); 
        
        let btn = $('#submitUserBtn');
        let originalText = btn.html();
        
        // Set loading state
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Provisioning User...');

        $.ajax({
            url: '/ajax/create_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addUserModal').modal('hide'); // Close the modal
                    $('#addUserForm')[0].reset();     // Clear form
                    fetchUsers();                     // Auto-refresh the UI table instantly!
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalText); // Restore button
            },
            error: function() {
                alert('A server error occurred.');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    // Add Domain Form Submission
    $('#addDomainForm').on('submit', function(e) {
        e.preventDefault();
        
        let btn = $('#submitDomainBtn');
        let originalText = btn.html();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Configuring Nginx...');

        $.ajax({
            url: '/ajax/create_domain.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addDomainModal').modal('hide'); // Close modal
                    $('#addDomainForm')[0].reset();     // Clear form
                    setTimeout(fetchDomains, 1500);     // Auto-refresh the UI table (delay to let Nginx restart)
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalText); // Restore button
            },
            error: function() {
                alert('A server error occurred. Check PHP logs.');
                btn.prop('disabled', false).html(originalText);
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
        let btn = $(this);
        let alertBox = $('#dbFormAlert');
        let originalText = btn.html();
        
        // Native HTML5 Validation Check
        if (!form[0].checkValidity()) { 
            form[0].reportValidity(); 
            return; 
        }

        // Build the Custom Privileges String
        if ($('#dbRole').val() === 'custom') {
            let privs = [];
            $('.db-priv-chk:checked').each(function() { privs.push($(this).val()); });
            
            if(privs.length === 0) {
                alert("You must select at least one privilege for a custom role.");
                return;
            }
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
                    $('#addDbModal').modal('hide'); // <--- THE FIX: Close the modal smoothly
                    
                    // Reset the UI cleanly in the background
                    form[0].reset();
                    $('#dbPrefixLabel').text('prefix_');
                    $('#dbCustomIp').addClass('d-none');
                    $('#customPrivilegesGrid').addClass('d-none');
                    
                    // Auto-refresh the DB table!
                    setTimeout(fetchDatabases, 1500); 
                } else {
                    alertBox.addClass('alert-danger').text(response.error).removeClass('d-none');
                }
                btn.prop('disabled', false).html(originalText); // Restore button
            },
            error: function() {
                alertBox.addClass('alert-danger').text('A server error occurred.').removeClass('d-none');
                btn.prop('disabled', false).html(originalText); // Restore button
            }
        });
    });
    
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

    // 2. Issue Let's Encrypt Form Submission
    $('#issueLetsEncryptForm').on('submit', function(e) {
        e.preventDefault();
        
        let btn = $('#btnIssueLe');
        let originalText = btn.html();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Communicating with Let\'s Encrypt...');

        $.ajax({
            url: '/ajax/install_ssl.php', // Your existing Phase 1 script
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert("SSL Installed Successfully! The panel will now refresh.");
                    // Close modal and refresh domains table
                    $('#installSslModal').modal('hide');
                    setTimeout(fetchDomains, 1000);
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert("A server error occurred.");
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // 3. HSTS Slider Sync Logic
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
    // =================================================================
    // GLOBAL UX: DYNAMIC TOAST NOTIFICATION SYSTEM
    // =================================================================
    
    // 1. Inject the Toast Container into the DOM automatically
    if ($('#oPanelToastContainer').length === 0) {
        $('body').append(`
            <div id="oPanelToastContainer" class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 9999;">
                <div id="oPanelToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex px-1 py-2">
                        <div class="toast-body fw-bold fs-6">
                            <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i> 
                            <span id="oPanelToastMsg">Copied!</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-3 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
        `);
    }

    // 2. Global Helper Function to trigger the Toast anywhere in the panel
    window.showToast = function(message) {
        $('#oPanelToastMsg').text(message);
        let toastEl = new bootstrap.Toast(document.getElementById('oPanelToast'), { delay: 2500 });
        toastEl.show();
    };
    // =================================================================
    // GLOBAL UX: 100% BULLETPROOF CUSTOM TOAST SYSTEM
    // =================================================================
    
    // 1. Inject a pure HTML/CSS element (Zero Bootstrap JS dependencies)
    if ($('#customOpanelToast').length === 0) {
        $('body').append(`
            <div id="customOpanelToast" style="display:none; position:fixed; bottom:20px; right:20px; z-index:999999; background:#212529; color:#fff; padding:12px 20px; border-radius:8px; box-shadow:0 10px 20px rgba(0,0,0,0.4); font-weight:bold; border-left:4px solid #198754; pointer-events:none;">
                <i class="bi bi-check-circle-fill text-success me-2 fs-5" style="vertical-align: middle;"></i> 
                <span id="customOpanelToastMsg" style="vertical-align: middle;">Copied!</span>
            </div>
        `);
    }

    // 2. Global Helper Function using pure jQuery animations
    window.showToast = function(message) {
        $('#customOpanelToastMsg').text(message);
        let toast = $('#customOpanelToast');
        toast.stop(true, true).fadeIn(200);
        setTimeout(() => { toast.fadeOut(400); }, 2500);
    };

    // =================================================================
    // UNIVERSAL COPY CONTROLLERS
    // =================================================================

    // 3. Fix for Password/Text Inputs (.copy-btn)
    $(document).on('click', '.copy-btn', function() {
        let targetId = $(this).data('target');
        let targetEl = $('#' + targetId);
        
        // Dynamically grab .val() for inputs or .text() for spans
        let textToCopy = targetEl.is('input, textarea') ? targetEl.val() : targetEl.text();
        let btn = $(this);
        
        if (!textToCopy) return;

        // The Focus-Trap Beating Fallback for self-signed certs
        const doFallbackCopy = () => {
            let $temp = $("<textarea>");
            $temp.css({position: 'absolute', left: '-9999px'});
            $('body').append($temp);
            $temp.val(textToCopy).select();
            try { document.execCommand("copy"); } catch (e) {}
            $temp.remove();
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).catch(() => doFallbackCopy());
        } else {
            doFallbackCopy();
        }

        // Fire UI Feedback
        let originalIcon = btn.html();
        btn.html('<i class="bi bi-check2 text-success"></i>');
        showToast("Copied to clipboard!");
        setTimeout(() => { btn.html(originalIcon); }, 1500);
    });

    // 4. Click-to-Copy Universal Log Terminal
    $(document).on('click', '#logTaskOutput, #logTerminal', function() {
        let terminal = $(this);
        let textToCopy = terminal.text();
        
        if (!textToCopy || textToCopy.trim() === '') return;

        // Visual Success Feedback
        const triggerSuccessUI = () => {
            terminal.addClass('bg-dark bg-opacity-75 text-success');
            showToast("Terminal log copied to clipboard!");
            setTimeout(() => { terminal.removeClass('bg-dark bg-opacity-75 text-success'); }, 1500);
        };

        // Fallback function strictly appended INSIDE the terminal to beat the Modal Focus Trap
        const fallbackCopy = (text) => {
            let $temp = $("<textarea>");
            $temp.css({position: 'absolute', left: '-9999px'});
            terminal.parent().append($temp); // <--- DEFEATS MODAL FOCUS TRAP
            $temp.val(text).select();
            try {
                document.execCommand("copy");
                triggerSuccessUI();
            } catch (err) {
                console.error("Fallback copy failed.");
            }
            $temp.remove();
        };

        // Try modern API first, use fallback if blocked by SSL
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy)
                .then(triggerSuccessUI)
                .catch(() => fallbackCopy(textToCopy));
        } else {
            fallbackCopy(textToCopy);
        }
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
                    
                    let container = $('#dynamicDomainsAccordion');
                    container.empty();
                    
                    if(response.domains.length === 0) {
                        container.html('<div class="text-center text-muted py-5">No domains configured.</div>');
                        return;
                    }
                    
                    let allRowsHtml = ''; 
                    
                    response.domains.forEach(function(d) {
                        let proto = d.has_ssl == 1 ? 'https' : 'http'; 
                        let isPhp = (d.app_type === 'php' || !d.app_type);
                        
                        // --- Status & Toggle Logic ---
                        let isSuspended = d.status === 'suspended';
                        let suspendIcon = isSuspended ? 'bi-play-fill' : 'bi-pause-circle';
                        let suspendText = isSuspended ? 'Unsuspend' : 'Suspend';
                        let suspendColor = isSuspended ? 'outline-success' : 'outline-warning text-dark';
                        let suspendAction = isSuspended ? 'unsuspend' : 'suspend';

                        // --- WAF UI Logic ---
                        let wafColor = (d.waf_enabled == 1) ? 'success' : 'outline-secondary';
                        let wafIcon = (d.waf_enabled == 1) ? 'bi-shield-check' : 'bi-shield-slash';
                        let wafText = (d.waf_enabled == 1) ? 'WAF: ON' : 'WAF: OFF';

                        // --- App Engine Logic ---
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

                        // --- Git & CI/CD Horizontal Footer Logic ---
                        let gitDisplay = '<div class="text-muted small px-2 py-1"><i class="bi bi-github"></i> Git Auto-Deployment Not Configured</div>';
                        if (d.git_repo && d.git_repo !== 'Not Configured') {
                            let host = window.location.hostname;
                            let webhookUrl = `https://${host}:7443/ajax/webhook.php?domain=${d.domain_name}&token=${d.webhook_token}`;
                            let currentBranch = d.git_branch || 'main'; 

                            let commitsHtml = '';
                            if (d.latest_commits) {
                                try {
                                    // Slice to strictly grab only the latest 4 commits
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
                                                <!-- The CSS Clamp limits text to exactly 2 lines -->
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

                        // --- Assemble the Accordion Item ---
                        allRowsHtml += `
                        <div class="accordion-item mb-3 border shadow-sm rounded">
                            
                            <!-- SPLIT HEADER: Safe container for Accordion Toggle + Action Buttons -->
                            <div class="d-flex align-items-stretch border-bottom bg-white rounded-top">
                                
                                <!-- LEFT SIDE: The Accordion Toggle -->
                                <h2 class="accordion-header flex-grow-1 m-0">
                                    <button class="accordion-button collapsed py-2 rounded-start border-0 shadow-none bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#acc-${d.id}">
                                        <div class="d-flex align-items-center w-100">
                                            <div class="me-2"><i class="bi bi-globe fs-4 text-primary"></i></div>
                                            <div class="lh-sm">
                                                <span class="fw-bold text-dark fs-6">${d.domain_name}</span>
                                                ${isSuspended ? '<span class="badge bg-danger ms-1" style="font-size:0.65rem;">Suspended</span>' : ''}
                                                <span class="text-muted small ms-2" style="font-size:0.75rem;">(User: ${d.username} | PHP ${d.php_version})</span>
                                            </div>
                                        </div>
                                    </button>
                                </h2>

                                <!-- RIGHT SIDE: Pinned Actions (Visit & Delete) -->
                                <div class="d-flex align-items-center px-3 border-start bg-light rounded-end">
                                    <a href="${proto}://${d.domain_name}" target="_blank" onclick="event.stopPropagation()" class="btn btn-sm btn-light border shadow-sm me-2 py-1 px-2" title="Visit Site">
                                        <i class="bi bi-box-arrow-up-right text-primary me-1"></i> Visit
                                    </a>
                                    <button class="btn btn-sm btn-danger shadow-sm delete-domain py-1 px-2" data-domain="${d.domain_name}" data-user="${d.username}" title="Delete Domain">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>

                            <!-- ACCORDION BODY -->
                            <div id="acc-${d.id}" class="accordion-collapse collapse" data-bs-parent="#dynamicDomainsAccordion">
                                <div class="accordion-body bg-light p-3">
                                    
                                    <!-- TOP SECTION: 4 Tool Columns -->
                                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 mb-4">
                                        
                                        <!-- COLUMN 1: App Engines -->
                                        <div class="col">
                                            <h6 class="text-muted small fw-bold text-uppercase border-bottom pb-2 mb-2">
                                                <i class="bi bi-cpu me-1"></i> App Engines
                                            </h6>
                                            <div class="d-grid gap-2">
                                                ${appActions}
                                            </div>
                                        </div>

                                        <!-- COLUMN 2: Security -->
                                        <div class="col border-start">
                                            <h6 class="text-muted small fw-bold text-uppercase border-bottom pb-2 mb-2">
                                                <i class="bi bi-shield-check me-1"></i> Security
                                            </h6>
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-sm btn-${wafColor} text-start toggle-waf" data-domain="${d.domain_name}" data-action="${d.waf_enabled == 1 ? 'off' : 'on'}">
                                                    <i class="bi ${wafIcon} me-2"></i> ${wafText}
                                                </button>
                                                <button class="btn btn-sm btn-outline-dark text-start edit-waf-rules" data-domain="${d.domain_name}" data-rules="${btoa(d.waf_custom_rules || '')}">
                                                    <i class="bi bi-shield-lock me-2"></i> WAF Rules
                                                </button>
                                                <button class="btn btn-sm btn-outline-success text-start" data-bs-toggle="modal" data-bs-target="#installSslModal" onclick="$('#sslTargetDomain').val('${d.domain_name}').trigger('change');">
                                                    <i class="bi bi-shield-lock-fill me-2"></i> Install SSL
                                                </button>
                                            </div>
                                        </div>

                                        <!-- COLUMN 3: Files & Cache -->
                                        <div class="col border-start">
                                            <h6 class="text-muted small fw-bold text-uppercase border-bottom pb-2 mb-2">
                                                <i class="bi bi-folder2-open me-1"></i> Files & Cache
                                            </h6>
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-sm btn-outline-primary text-start open-fm-sso" data-domain="${d.domain_name}">
                                                    <i class="bi bi-folder2-open me-2"></i> Open File Manager
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning text-dark text-start deploy-fm" data-domain="${d.domain_name}" data-user="${d.username}" data-ver="${d.php_version}">
                                                    <i class="bi bi-cloud-arrow-up-fill me-2"></i> Deploy File Manager
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary text-start rotate-fm-pass" data-domain="${d.domain_name}" data-user="${d.username}">
                                                    <i class="bi bi-key me-2"></i> Rotate FM Key
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger text-start enable-redis-btn" data-domain="${d.domain_name}" data-user="${d.username}">
                                                    <i class="bi bi-memory me-2"></i> Inject Redis Cache
                                                </button>
                                                <button class="btn btn-sm btn-outline-dark text-start edit-php-settings" data-json='${JSON.stringify(d).replace(/'/g, "&apos;")}'>
                                                    <i class="bi bi-sliders me-2"></i> PHP Config
                                                </button>
                                            </div>
                                        </div>

                                        <!-- COLUMN 4: Network & Info -->
                                        <div class="col border-start">
                                            <h6 class="text-muted small fw-bold text-uppercase border-bottom pb-2 mb-2">
                                                <i class="bi bi-hdd-network me-1"></i> Network & Info
                                            </h6>
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-sm btn-outline-info text-dark text-start show-connection-info" data-domain="${d.domain_name}">
                                                    <i class="bi bi-info-circle-fill me-2"></i> Connection Info
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary text-start open-advanced-web" data-domain="${d.domain_name}" data-hotlink="${d.hotlink_protection}">
                                                    <i class="bi bi-gear-wide-connected me-2"></i> Web Settings
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary text-start manage-ftp" data-domain="${d.domain_name}" data-user="${d.username}">
                                                    <i class="bi bi-hdd-network-fill me-2"></i> FTP Accounts
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary text-start manage-mail" data-domain="${d.domain_name}">
                                                    <i class="bi bi-envelope-at-fill me-2"></i> Mailboxes
                                                </button>
                                                <button class="btn btn-sm btn-${suspendColor} text-start toggle-domain-status" data-domain="${d.domain_name}" data-action="${suspendAction}">
                                                    <i class="bi ${suspendIcon} me-2"></i> ${suspendText} Domain
                                                </button>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- BOTTOM SECTION: Full-Width Git Footer -->
                                    <div class="border-top border-2 border-secondary border-opacity-10 pt-3">
                                        <h6 class="text-muted small fw-bold text-uppercase mb-2">
                                            <i class="bi bi-git me-1"></i> CI/CD Pipeline
                                        </h6>
                                        ${gitDisplay}
                                    </div>

                                </div>
                            </div>
                        </div>`;
                    });
                    
                    container.html(allRowsHtml);
                    
                    // Populate dropdowns
                    let domainDropdowns = $('.domain-dropdown');
                    domainDropdowns.empty().append('<option value="">Select a Domain...</option>');
                    response.domains.forEach(function(d) {
                        domainDropdowns.append('<option value="' + d.domain_name + '">' + d.domain_name + '</option>');
                    });
                }
            }
        });
    }
    // === Suspend/Unsuspend Domain Logic ===
    $(document).on('click', '.toggle-domain-status', function() {
        let domain = $(this).data('domain');
        let action = $(this).data('action');
        let btn = $(this);
        
        let warning = action === 'suspend' 
            ? `Are you sure you want to suspend ${domain}? All traffic will be blocked immediately with a 503 error.` 
            : `Are you sure you want to unsuspend ${domain} and restore traffic?`;
            
        if(!confirm(warning)) return;

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_domain_status.php',
            type: 'POST',
            data: { domain: domain, action: action },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Refresh the domains table after 2 seconds so Nginx has time to reload
                    setTimeout(fetchDomains, 2000); 
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false);
                }
            }
        });
    });
    // === SCORCHED EARTH: Delete Domain (Upgraded SRE Fallback) ===
    $(document).on('click', '.delete-domain', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        
        // 1. Is the current browser URL matching the domain they are trying to delete?
        let isMasterDomain = window.location.hostname === domain;
        
        let confirmText;
        if (isMasterDomain) {
            confirmText = prompt(`CRITICAL: '${domain}' is currently securing oPanel. Deleting this will unbind the panel, revert to the raw IP, and disconnect your session. Type the domain name to proceed:`);
        } else {
            confirmText = prompt(`WARNING: This will permanently destroy all files and SSL for '${domain}'. Type the domain name to proceed:`);
        }
        
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
                        // If we just deleted the master domain, we MUST redirect to the IP to save the session
                        alert("Master domain deleted. Reverting to IP address...");
                        let ip = response.server_ip || window.location.hostname; // Ensure your delete_domain.php returns the server_ip
                        window.location.href = "https://" + ip + ":7443";
                    } else {
                        setTimeout(fetchDomains, 3000); 
                    }
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false).html('<i class="bi bi-trash-fill"></i>');
                }
            }
        });
    });
    // === Retrofit Redis Button Logic ===
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
                    $('#overview-tab').tab('show'); // Jump to tasks tab to watch it
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalIcon);
            },
            error: function() {
                alert("Network Error.");
                btn.prop('disabled', false).html(originalIcon);
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
    // 1. Open Modern PHP Settings Modal & Populate Fields
    $(document).on('click', '.edit-php-settings', function() {
        let d = $(this).data('json');
        
        $('#phpDomainTitle').text(d.domain_name);
        $('#psDomain').val(d.domain_name);
        $('#psUser').val(d.username);
        $('#psVer').val(d.php_version);
        
        // Populate fields from the database, using our Secure Defaults if null
        $('#ps_mem').val(d.php_memory_limit || '128M');
        $('#ps_max_exec').val(d.php_max_exec_time || 30);
        $('#ps_max_in').val(d.php_max_input_time || 60);
        $('#ps_post').val(d.php_post_max_size || '8M');
        $('#ps_up').val(d.php_upload_max_filesize || '2M');
        $('#ps_opc').val(d.php_opcache_enable || 'on');
        $('#ps_dis').val(d.php_disable_functions || 'exec,shell_exec,system,passthru,popen,proc_open');
        
        // ---> SECURE PATH DEFAULTS <---
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

    // === SECURE PANEL: UNBIND & REVERT TO IP ===
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
    // === Auto-Generate Linux User Password ===
    $('#generateUserPass').click(function(e) {
        e.preventDefault();
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        let pass = "";
        for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#password').val(pass);
        
        // Auto-copy to clipboard
        navigator.clipboard.writeText(pass);
        let originalText = $(this).html();
        $(this).html('<span class="text-success"><i class="bi bi-check2"></i> Copied!</span>');
        setTimeout(() => { $(this).html(originalText); }, 2000);
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
    // =================================================================
    // FAIL2BAN ACTIVE DEFENSE CONTROLLER
    // =================================================================

    // 1. Fetch Live Bans & Telemetry
    window.fetchFail2Ban = function() {
        $.ajax({
            url: '/ajax/get_fail2ban.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // --- A. Populate the Main Bans Table ---
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

                    // --- B. Populate the Stats Modal ---
                    let statsBody = $('#dynamicFail2banStatsTable');
                    statsBody.empty();
                    
                    let globalTotalBans = 0;

                    if(response.stats && response.stats.length > 0) {
                        $('#f2bGlobalJails').text(response.stats.length);
                        
                        response.stats.forEach(function(s) {
                            globalTotalBans += parseInt(s.total_banned);
                            
                            // Visual highlight if a jail is currently under attack
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

    // 2. Unban Button Click Handler
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
                    // Switch to the Overview tab so the user can watch the queue process the unban
                    $('#overview-tab').tab('show');
                    alert(response.message);
                    
                    // Optimistically refresh the UI table
                    fetchFail2Ban();
                } else {
                    alert("Error: " + response.error);
                    btn.prop('disabled', false).html('<i class="bi bi-unlock-fill"></i> Unban');
                }
            }
        });
    });

    // 3. Initialize and set background polling (every 10 seconds)
    fetchFail2Ban();
    setInterval(fetchFail2Ban, 10000);
    // =================================================================
    // SYSTEM SERVICES MANAGER
    // =================================================================

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
                        // 1. Determine Status Badge
                        let statusBadge = '';
                        if (s.status === 'active') {
                            statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i> Running</span>';
                        } else if (s.status === 'inactive' || s.status === 'failed') {
                            statusBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-x-circle-fill me-1"></i> Stopped</span>';
                        } else {
                            statusBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">Not Installed</span>';
                        }

                        // 2. Build Action Buttons
                        let actions = '';
                        if (s.status !== 'unknown') { // Only show buttons if installed
                            let startBtn = `<button class="btn btn-sm btn-outline-success mx-1 execute-service" data-action="start" data-svc="${s.service}" title="Start"><i class="bi bi-play-fill"></i></button>`;
                            let stopBtn  = `<button class="btn btn-sm btn-outline-danger mx-1 execute-service" data-action="stop" data-svc="${s.service}" title="Stop"><i class="bi bi-stop-fill"></i></button>`;
                            let resBtn   = `<button class="btn btn-sm btn-outline-dark mx-1 execute-service" data-action="restart" data-svc="${s.service}" title="Restart"><i class="bi bi-arrow-clockwise"></i></button>`;

                            // SRE Guardrail: Hide the stop/start buttons for core services if active to prevent self-sabotage
                            if (!s.can_stop) {
                                stopBtn = '';
                                startBtn = ''; 
                            }
                            
                            // If it's already running, disable the start button. If stopped, disable restart/stop.
                            if (s.status === 'active') startBtn = startBtn.replace('btn-outline-success', 'btn-outline-success disabled');
                            if (s.status !== 'active') {
                                stopBtn = stopBtn.replace('btn-outline-danger', 'btn-outline-danger disabled');
                                resBtn = resBtn.replace('btn-outline-dark', 'btn-outline-dark disabled');
                            }

                            actions = startBtn + stopBtn + resBtn;
                        }

                        // 3. Render Row
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

    // Execute Service Action Click Handler
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
                    // Wait 3 seconds for Python to execute, then refresh the list
                    setTimeout(fetchServices, 3000);
                } else {
                    alert("Error: " + res.error);
                    btn.prop('disabled', false).html(originalHtml);
                }
            }
        });
    });

    // Run on load
    fetchServices();
    // =================================================================
    // COMPONENT VERSION MANAGER
    // =================================================================

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

    // Run on load alongside fetchServices()
    fetchComponents();
    
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
            contentType: false, // Required for file uploads
            processData: false, // Required for file uploads
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
    // === REDIS CACHE ENGINE ===
    let redisInterval;

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#redis') {
            fetchRedisStats();
            redisInterval = setInterval(fetchRedisStats, 5000); // Live update every 5s
        } else {
            clearInterval(redisInterval); // Stop pinging when tab is closed
        }
    });

    function fetchRedisStats() {
        $.getJSON('/ajax/redis_stats.php', function(data) {
            if (data.success) {
                $('#redisStatusBadge').removeClass('bg-secondary bg-danger').addClass('bg-success').text('Online');
                $('#redisClients').text(data.clients);
                $('#redisHitRate').text(data.hit_rate);
                $('#redisUptime').text(data.uptime_days);
                
                // Animate Memory Bar
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
    }

    window.redisAction = function(actionType) {
        if (!confirm('Are you sure you want to ' + actionType + ' the Redis cache?')) return;
        
        $.post('/ajax/redis_action.php', { action: actionType, csrf_token: document.querySelector('meta[name="csrf-token"]').content }, function(res) {
            if(res.success) {
                alert(res.message);
                fetchRedisStats(); // Refresh UI instantly
            } else {
                alert("Error: " + res.error);
            }
        }, 'json');
    }
    // === Custom App Developer Guide ===
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
                    // Populate the inputs
                    $('#devRedisPass').val(res.password);
                    
                    // Inject the password into the boilerplate code dynamically
                    let boilerplate = $('#devPhpBoilerplate').val();
                    boilerplate = boilerplate.replace('PASSWORD_WILL_LOAD_HERE', res.password);
                    $('#devPhpBoilerplate').val(boilerplate);

                    // Show the modal
                    $('#devRedisModal').modal('show');
                } else {
                    alert("Error fetching credentials: " + res.error);
                }
                btn.prop('disabled', false).html(originalHtml);
            },
            error: function() {
                alert("Network error.");
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Reset the boilerplate text when the modal closes so it's ready for the next click
    $('#devRedisModal').on('hidden.bs.modal', function () {
        let boilerplate = $('#devPhpBoilerplate').val();
        let currentPass = $('#devRedisPass').val();
        if(currentPass) {
            boilerplate = boilerplate.replace(currentPass, 'PASSWORD_WILL_LOAD_HERE');
            $('#devPhpBoilerplate').val(boilerplate);
            $('#devRedisPass').val('');
        }
    });
    // === ADVANCED WEB SETTINGS UI LOGIC ===
    // 1. Function to Fetch and Render Tables
     window.fetchAdvancedWebData = function(domain) {
        $.ajax({
            url: '/ajax/get_advanced_web.php',
            type: 'POST',
            data: { domain: domain },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    // Render Redirects
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

                    // Render MIME Types
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
    }

    // 2. Hook into the Opener logic (Update the existing one from the previous step)
    $(document).on('click', '.open-advanced-web', function() {
        let domain = $(this).data('domain');
        let hotlinkActive = $(this).data('hotlink') == 1;
        
        $('#advWebDomainTitle').text(domain);
        $('.adv-domain-input').val(domain);
        
        $('#hotlinkToggle').prop('checked', hotlinkActive);
        $('#hotlinkStatusText').text(hotlinkActive ? 'Active and protecting assets.' : 'Currently disabled.');
        
        $('#dynamicRedirectsTable').html('<tr><td colspan="4" class="text-center text-muted small">Loading...</td></tr>');
        $('#dynamicMimesTable').html('<tr><td colspan="3" class="text-center text-muted small">Loading...</td></tr>');

        // Fetch the fresh data!
        fetchAdvancedWebData(domain);
        $('#advancedWebModal').modal('show');
    });

    // 3. Handle Add Redirect / Add MIME Submissions
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
                    setTimeout(() => fetchAdvancedWebData(domain), 1500); // Refresh tables
                } else { alert("Error: " + res.error); }
                btn.prop('disabled', false).html('<i class="bi bi-plus-lg"></i> Add');
            }
        });
    });

    // 4. Handle Deletions (Redirects & MIMEs)
    $(document).on('click', '.del-adv-btn', function() {
        if(!confirm("Are you sure you want to remove this rule?")) return;
        let btn = $(this);
        let id = btn.data('id');
        let action = btn.data('action');
        let domain = $('.adv-domain-input').first().val(); // Get domain from active modal

        btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');

        $.ajax({
            url: '/ajax/manage_advanced_web.php',
            type: 'POST',
            data: { action: action, id: id, domain: domain },
            dataType: 'json',
            success: function(res) {
                if(res.success) setTimeout(() => fetchAdvancedWebData(domain), 1500);
            }
        });
    });

    // 5. Handle Hotlink Toggle
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
                    // Update the master DOM attribute so it persists if modal closes
                    $(`.open-advanced-web[data-domain="${domain}"]`).data('hotlink', isChecked ? 1 : 0);
                } else {
                    alert("Error: " + res.error);
                    $('#hotlinkToggle').prop('checked', !isChecked); // Revert UI
                }
                $('#hotlinkToggle').prop('disabled', false);
            }
        });
    });
    // === LARAVEL DEPLOYMENT TRIGGER ===
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
            success: function(response) {
                if(response.success) {
                    showToast("Laravel build queued! Switching to Live Tasks...");
                    $('#overview-tab').tab('show'); 
                    setTimeout(fetchDomains, 1500); // <--- Auto-refresh the UI to show the Revert button!
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalIcon);
            },
            error: function() {
                alert("API Error: Check the server logs.");
                btn.prop('disabled', false).html(originalIcon);
            }
        });
    });

    // === PYTHON DEPLOYMENT TRIGGER ===
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
            success: function(response) {
                if(response.success) {
                    showToast("Python build queued! Switching to Live Tasks...");
                    $('#overview-tab').tab('show'); 
                    setTimeout(fetchDomains, 1500); // <--- Auto-refresh the UI!
                } else {
                    alert("Error: " + response.error);
                }
                btn.prop('disabled', false).html(originalIcon);
            },
            error: function() {
                alert("API Error: Check the server logs.");
                btn.prop('disabled', false).html(originalIcon);
            }
        });
    });
    // =================================================================
    // DYNAMIC ENVIRONMENT LIFECYCLE MANAGERS
    // =================================================================

    // === REVERT APP ENVIRONMENT BACK TO PHP ===
    $(document).on('click', '.revert-app', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        let type = $(this).data('type');
        let btn = $(this);
        let originalIcon = btn.html();
        
        let warning = `CRITICAL WARNING: Are you sure you want to revert ${domain} back to standard PHP?`;
        if(!confirm(warning)) return;
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_app_state.php',
            type: 'POST',
            data: { domain: domain, username: user, action: 'revert' },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    showToast("Revert sequence initiated! Check Live Tasks.");
                    $('#overview-tab').tab('show'); 
                    setTimeout(fetchDomains, 1500); // <--- Auto-refresh the UI to bring back the deploy buttons!
                } else {
                    alert("Error: " + res.error);
                }
                btn.prop('disabled', false).html(originalIcon);
            },
            error: function() {
                alert("API Error: Check the server logs.");
                btn.prop('disabled', false).html(originalIcon);
            }
        });
    });

    // 2. RESTART PERSISTENT APP ENGINE (Python/Node)
    $(document).on('click', '.restart-app', function() {
        let domain = $(this).data('domain');
        let user = $(this).data('user');
        let btn = $(this);
        let originalIcon = btn.html();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/ajax/manage_app_state.php',
            type: 'POST',
            data: { domain: domain, username: user, action: 'restart' },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    showToast("Engine Restart queued. App will reload in 1-2 seconds.");
                    // Give visual feedback then restore button
                    setTimeout(() => { btn.prop('disabled', false).html(originalIcon); }, 2500);
                } else {
                    alert("Error: " + res.error);
                    btn.prop('disabled', false).html(originalIcon);
                }
            }
        });
    });
    
});