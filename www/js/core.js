// /opt/panel/www/js/core.js

// =================================================================
// 1. STACKRIUM GLOBAL TOAST ENGINE (SaaS Edition)
// =================================================================
window.showToast = function(type, title, message) {
    let icon = type === 'success' ? 'bi-check-circle-fill text-success' : 
              (type === 'error' ? 'bi-exclamation-triangle-fill text-danger' : 
              (type === 'warning' ? 'bi-exclamation-triangle-fill text-warning' : 'bi-info-circle-fill text-info'));
    
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
// =================================================================
// DOCS: DEEP CONTENT SMART SEARCH FILTER
// =================================================================
window.filterDocs = function() {
    let filter = $('#docSearch').val().toLowerCase();
    
    $('#docs-list .list-group-item').each(function() {
        // 1. Get the text of the menu item itself
        let linkText = $(this).text().toLowerCase();
        
        // 2. Find the target tab pane (e.g., "#doc-cdn") and get its inner content safely
        let targetPaneId = $(this).attr('href');
        let paneText = "";
        
        if (targetPaneId && $(targetPaneId).length) {
            paneText = $(targetPaneId).text().toLowerCase();
        }

        // 3. Show the link if the search term matches the title OR the deep content
        if (linkText.includes(filter) || paneText.includes(filter)) {
            $(this).removeClass('d-none');
        } else {
            $(this).addClass('d-none');
        }
    });
};

$(document).ready(function() {
    // =================================================================
    // 2. GLOBAL UI INJECTIONS
    // =================================================================
    $('<style>').prop('type', 'text/css').html(`
        #logTaskOutput, #logTerminal { cursor: pointer; transition: opacity 0.2s; }
        #logTaskOutput:hover, #logTerminal:hover { opacity: 0.8; }
        .copy-trigger { cursor: pointer; transition: transform 0.1s; }
        .copy-trigger:active { transform: scale(0.90); }
    `).appendTo('head');

    // =================================================================
    // 3. GLOBAL NETWORK CONTROLLER & FIREWALL
    // =================================================================
    window.stackriumLocked = false;
    let csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrfToken },
        beforeSend: function(jqXHR, settings) {
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
    // 4. TAB STATE PERSISTENCE (URL HASH METHOD)
    // =================================================================
    let urlHash = window.location.hash;
    if (urlHash) {
        let targetTab = document.querySelector(`.sidebar a[href="${urlHash}"]`);
        if (targetTab) {
            new bootstrap.Tab(targetTab).show();
            let titleEl = document.getElementById('pageTitle');
            if(titleEl) titleEl.innerText = targetTab.innerText.trim();
        }
    }
    
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if(history.pushState) {
            history.pushState(null, null, e.target.hash);
        } else {
            window.location.hash = e.target.hash;
        }
    });

    // =================================================================
    // 5. UNIVERSAL COPY CONTROLLERS
    // =================================================================
    $(document).on('click', '.copy-trigger', function() {
        let textToCopy = $(this).data('copy') || $(this).text().trim();
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                window.showToast('success', 'Copied to Clipboard', `Data successfully copied.`);
            }).catch(err => {
                window.showToast('error', 'Copy Failed', 'Your browser blocked the clipboard action.');
            });
        } else {
            // Fallback for older browsers
            let $temp = $("<textarea>");
            $("body").append($temp);
            $temp.val(textToCopy).select();
            document.execCommand("copy");
            $temp.remove();
            window.showToast('success', 'Copied to Clipboard', `Data successfully copied.`);
        }
    });

    // Terminal Log Copy Handler
    $(document).on('click', '#logTaskOutput, #logTerminal', function() {
        let terminal = $(this);
        let textToCopy = terminal.text();
        if (!textToCopy || textToCopy.trim() === '') return;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                terminal.addClass('bg-dark bg-opacity-75 text-success');
                window.showToast('success', 'Terminal Copied', 'Log output copied to clipboard!');
                setTimeout(() => { terminal.removeClass('bg-dark bg-opacity-75 text-success'); }, 1500);
            });
        }
    });

    // =================================================================
    // 6. FIRST-LOGIN GATEKEEPER (Forces Admin to change 'admin123')
    // =================================================================
    $.ajax({
        url: '/ajax/check_first_login.php',
        type: 'POST',
        dataType: 'json',
        success: function(res) {
            if (res.is_first_login) {
                let gatekeeperModal = new bootstrap.Modal(document.getElementById('firstLoginModal'), {
                    backdrop: 'static', 
                    keyboard: false     
                });
                gatekeeperModal.show();
            }
        }
    });

    $(document).on('submit', '#firstLoginForm', function(e) {
        e.preventDefault();
        let btn = $('#btnSaveFirstLogin');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Securing Server...');

        let pass1 = $('#firstPass1').val();
        let pass2 = $('#firstPass2').val();

        if (pass1 !== pass2) {
            window.showToast('error', 'Password Mismatch', 'Your new passwords do not match.');
            btn.prop('disabled', false).html('Secure & Unlock Panel');
            return;
        }

        $.ajax({
            url: '/ajax/setup_first_admin.php',
            type: 'POST',
            data: { new_password: pass1 },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#firstLoginModal').modal('hide');
                    window.showToast('success', 'Server Secured', 'Your administrator password has been updated.');
                } else {
                    window.showToast('error', 'Update Failed', res.error);
                    btn.prop('disabled', false).html('Secure & Unlock Panel');
                }
            }
        });
    });

    // =================================================================
    // 7. GLOBAL AJAX ERROR INTERCEPTOR (Silent Lockouts)
    // =================================================================
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        if (jqXHR.status === 0 || jqXHR.statusText === 'abort') return;

        if (jqXHR.status === 403) {
            try {
                let res = JSON.parse(jqXHR.responseText);
                if (res.error && res.hasOwnProperty('license_status')) {
                    window.stackriumLocked = true;
                    $('button').prop('disabled', false);
                    $('.spinner-border').remove();
                    $('#globalLicenseBanner').removeClass('d-none').addClass('d-flex');
                    $('#bannerHeading').text('CRITICAL: Panel Locked (License ' + res.license_status.toUpperCase() + ')');
                    
                    let licenseTab = document.querySelector('.sidebar a[href="#license-updates"]');
                    if (licenseTab) new bootstrap.Tab(licenseTab).show();
                }
            } catch(e) {}
        }
    });
});