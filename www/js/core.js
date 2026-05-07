// /opt/panel/www/js/core.js

$(document).ready(function() {
    // =================================================================
    // GLOBAL UI INJECTIONS
    // =================================================================
    $('<style>').prop('type', 'text/css').html(`
        #logTaskOutput, #logTerminal { cursor: pointer; transition: opacity 0.2s; }
        #logTaskOutput:hover, #logTerminal:hover { opacity: 0.8; }
    `).appendTo('head');

    // =================================================================
    // GLOBAL CSRF INTERCEPTOR
    // =================================================================
    let csrfToken = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrfToken }
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
});