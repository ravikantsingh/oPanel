// /opt/panel/www/js/core.js

// --- STACKRIUM GLOBAL TOAST ENGINE ---
window.showToast = function(type, title, message) {
    let icon = type === 'success' ? 'bi-check-circle-fill text-success' : 
              (type === 'error' ? 'bi-exclamation-triangle-fill text-danger' : 'bi-info-circle-fill text-info');
    
    let toastId = 'toast-' + Date.now();
    let toastHtml = `
        <div id="${toastId}" class="toast align-items-center border-0 shadow-lg rounded-4 mb-3" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body d-flex p-3 bg-white rounded-4">
                <i class="bi ${icon} fs-3 me-3 align-self-center"></i>
                <div>
                    <h6 class="mb-1 fw-bold text-dark">${title}</h6>
                    <p class="mb-0 small text-muted">${message}</p>
                </div>
                <button type="button" class="btn-close ms-auto align-self-start" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    $('.toast-container').append(toastHtml);
    let toastEl = new bootstrap.Toast(document.getElementById(toastId), { delay: 4000 });
    toastEl.show();
    
    // Self-cleanup: Remove HTML from DOM after it fades out
    document.getElementById(toastId).addEventListener('hidden.bs.toast', () => {
        $('#' + toastId).remove();
    });
}

$(document).ready(function() {
    // =================================================================
    // GLOBAL UI INJECTIONS
    // =================================================================
    $('<style>').prop('type', 'text/css').html(`
        #logTaskOutput, #logTerminal { cursor: pointer; transition: opacity 0.2s; }
        #logTaskOutput:hover, #logTerminal:hover { opacity: 0.8; }
    `).appendTo('head');

    // =================================================================
    // GLOBAL NETWORK CONTROLLER & FIREWALL
    // =================================================================
    window.stackriumLocked = false;
    let csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrfToken },
        beforeSend: function(jqXHR, settings) {
            // If the panel is locked, ONLY allow License Sync and Logout. 
            // Abort EVERYTHING else instantly.
            if (window.stackriumLocked) {
                let allowedUrls = ['get_license_info.php', 'sync_license.php', 'logout.php'];
                let isAllowed = allowedUrls.some(url => settings.url.includes(url));
                
                if (!isAllowed) {
                    jqXHR.abort();
                    return false;
                }
            }
        }
    });

    // =================================================================
    // TAB STATE PERSISTENCE (URL HASH METHOD)
    // =================================================================
    let activeHash = window.location.hash;
    if (activeHash) {
        let targetTab = $('button[data-bs-target="' + activeHash + '"], a[href="' + activeHash + '"]');
        if (targetTab.length) {
            targetTab.tab('show'); 
            let tabId = targetTab.attr('id');
            $('.sidebar a').removeClass('active');
            $('.sidebar a[onclick*="' + tabId + '"]').addClass('active');
        }
    }

    $('button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        let target = $(e.target).attr('data-bs-target') || $(e.target).attr('href');
        if(history.replaceState) {
            history.replaceState(null, null, target);
        } else {
            window.location.hash = target;
        }
    });

    // =================================================================
    // GLOBAL UX: 100% BULLETPROOF CUSTOM TOAST SYSTEM
    // =================================================================
    if ($('#customOpanelToast').length === 0) {
        $('body').append(`
            <div id="customOpanelToast" style="display:none; position:fixed; bottom:20px; right:20px; z-index:999999; background:#212529; color:#fff; padding:12px 20px; border-radius:8px; box-shadow:0 10px 20px rgba(0,0,0,0.4); font-weight:bold; border-left:4px solid #198754; pointer-events:none;">
                <i class="bi bi-check-circle-fill text-success me-2 fs-5" style="vertical-align: middle;"></i> 
                <span id="customOpanelToastMsg" style="vertical-align: middle;">Copied!</span>
            </div>
        `);
    }

    window.showToast = function(message) {
        $('#customOpanelToastMsg').text(message);
        let toast = $('#customOpanelToast');
        toast.stop(true, true).fadeIn(200);
        setTimeout(() => { toast.fadeOut(400); }, 2500);
    };

    // =================================================================
    // UNIVERSAL COPY CONTROLLERS
    // =================================================================
    $(document).on('click', '.copy-btn', function() {
        let targetId = $(this).data('target');
        let targetEl = $('#' + targetId);
        let textToCopy = targetEl.is('input, textarea') ? targetEl.val() : targetEl.text();
        let btn = $(this);
        
        if (!textToCopy) return;

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

        let originalIcon = btn.html();
        btn.html('<i class="bi bi-check2 text-success"></i>');
        showToast("Copied to clipboard!");
        setTimeout(() => { btn.html(originalIcon); }, 1500);
    });

    $(document).on('click', '#logTaskOutput, #logTerminal', function() {
        let terminal = $(this);
        let textToCopy = terminal.text();
        
        if (!textToCopy || textToCopy.trim() === '') return;

        const triggerSuccessUI = () => {
            terminal.addClass('bg-dark bg-opacity-75 text-success');
            showToast("Terminal log copied to clipboard!");
            setTimeout(() => { terminal.removeClass('bg-dark bg-opacity-75 text-success'); }, 1500);
        };

        const fallbackCopy = (text) => {
            let $temp = $("<textarea>");
            $temp.css({position: 'absolute', left: '-9999px'});
            terminal.parent().append($temp); 
            $temp.val(text).select();
            try {
                document.execCommand("copy");
                triggerSuccessUI();
            } catch (err) {
                console.error("Fallback copy failed.");
            }
            $temp.remove();
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy)
                .then(triggerSuccessUI)
                .catch(() => fallbackCopy(textToCopy));
        } else {
            fallbackCopy(textToCopy);
        }
    });
    // =================================================================
    // FIRST-LOGIN GATEKEEPER
    // =================================================================
    
    // 1. Check if the user has completed the initial registration
    $.ajax({
        url: '/ajax/check_registration.php',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.success && res.is_registered === false) {
                $('.wrapper, .sidebar').css('filter', 'blur(3px)');
                $('#firstLoginModal').modal('show');
            }
        }
    });

    // 2. The Bulletproof Form Submission
    $(document).on('submit', '#firstLoginForm', function(e) {
        e.preventDefault();
        
        let btn = $('#btnSubmitRegistration');
        let alertBox = $('#registrationAlert');
        // Automatically append the CSRF token to the payload so security.php accepts it
let formData = $(this).serialize() + '&csrf_token=' + $('meta[name="csrf-token"]').attr('content');
        
        // DEBUGGING: Watch your browser console (F12) to see this!
        console.log("First Login AJAX Fired!");
        console.log("Payload being sent:", formData);
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Activating License...');
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/ajax/complete_registration.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                console.log("Server Response:", res); // DEBUGGING
                
                if(res.success) {
                    alertBox.addClass('alert-success').text("License Activated! Loading dashboard...").removeClass('d-none');
                    setTimeout(() => {
                        $('#firstLoginModal').modal('hide');
                        $('.wrapper, .sidebar').css('filter', 'none');
                        showToast("Welcome to Stackrium Control!");
                    }, 1500);
                } else {
                    alertBox.addClass('alert-danger').text("Error: " + res.error).removeClass('d-none');
                    btn.prop('disabled', false).html('Try Again <i class="bi bi-arrow-right ms-2"></i>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Crash:", xhr.responseText); // DEBUGGING
                alertBox.addClass('alert-danger').text("Network error. Could not reach Stackrium Central.").removeClass('d-none');
                btn.prop('disabled', false).html('Try Again <i class="bi bi-arrow-right ms-2"></i>');
            }
        });
    });
    // =================================================================
    // GLOBAL AJAX ERROR INTERCEPTOR (Silent Lockouts)
    // =================================================================
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        // If the request was aborted by our kill-switch, ignore the error
        if (jqXHR.status === 0 || jqXHR.statusText === 'abort') return;

        if (jqXHR.status === 403) {
            try {
                let res = JSON.parse(jqXHR.responseText);
                if (res.error && res.hasOwnProperty('license_status')) {
                    
                    // 1. ENGAGE MASTER LOCK - Blocks all future AJAX polling
                    window.stackriumLocked = true;
                    
                    // 2. Clean up the UI
                    $('button').prop('disabled', false);
                    $('.spinner-border').remove();
                    
                    // 3. Un-hide the global PHP banner
                    $('#globalLicenseBanner').removeClass('d-none').addClass('d-flex');
                    $('#bannerHeading').text('CRITICAL: Panel Locked (License ' + res.license_status.toUpperCase() + ')');
                    
                    // 4. Force them to the License Tab
                    $('#license-tab').tab('show');
                }
            } catch(e) {}
        }
    });
});