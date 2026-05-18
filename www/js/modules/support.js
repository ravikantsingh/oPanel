// /opt/panel/www/js/modules/support.js

window.activeTicketId = null; // Tracks if we are viewing a specific thread

window.fetchTickets = function() {
    $.ajax({
        url: '/ajax/sync_tickets.php',
        type: 'POST',
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                window.renderTicketList(res.tickets);
                
                // If the user is currently viewing a specific thread, update the chat bubbles instantly
                if (window.activeTicketId !== null) {
                    window.renderChatThread(window.activeTicketId, res.replies);
                }
            }
        }
    });
};

window.renderTicketList = function(tickets) {
    let list = $('#dynamicTicketList');
    list.empty();

    if (tickets.length === 0) {
        list.html('<div class="list-group-item text-center text-muted py-5 border-0"><i class="bi bi-inboxes fs-1 d-block mb-2 text-light"></i>No support tickets found.</div>');
        return;
    }

    tickets.forEach(t => {
        let statusBadge = t.status === 'Open' ? '<span class="badge bg-primary">Open</span>' : 
                         (t.status === 'Answered' ? '<span class="badge bg-success">Answered</span>' : '<span class="badge bg-secondary">Closed</span>');
        
        let priorityColor = t.priority === 'Critical' ? 'text-danger' : (t.priority === 'High' ? 'text-warning' : 'text-muted');

        let row = `
        <button class="list-group-item list-group-item-action py-3 px-3 border-0 border-bottom view-ticket" data-id="${t.id}" data-subject="${t.subject}">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold text-dark">${t.subject}</h6>
                    <small class="text-muted"><i class="bi bi-flag-fill ${priorityColor} me-1"></i> ${t.priority} &bull; Updated: ${t.updated_at}</small>
                </div>
                <div>${statusBadge}</div>
            </div>
        </button>`;
        list.append(row);
    });
};

window.renderChatThread = function(ticketId, allReplies) {
    let chatBox = $('#chatHistory');
    chatBox.empty().removeClass('d-none');
    
    // Filter replies for just this ticket
    let thread = allReplies.filter(r => parseInt(r.ticket_id) === parseInt(ticketId));
    
    thread.forEach(r => {
        let isClient = r.sender_type === 'Client';
        let align = isClient ? 'text-end' : 'text-start';
        let bg = isClient ? 'bg-primary text-white' : 'bg-white border text-dark';
        let icon = isClient ? 'bi-person-fill' : 'bi-headset';
        let senderName = isClient ? 'You' : 'Stackrium Support';
        
        // Handle Screenshot Attachments
        let attachmentHtml = '';
        if (r.file_path) {
            attachmentHtml = `<div class="mt-2"><a href="${r.file_path}" target="_blank" class="badge bg-light text-dark border text-decoration-none"><i class="bi bi-paperclip"></i> ${r.file_name}</a></div>`;
        }

        let bubble = `
        <div class="mb-3 ${align}">
            <div class="small text-muted mb-1"><i class="bi ${icon}"></i> ${senderName} &bull; ${r.created_at}</div>
            <div class="d-inline-block p-2 px-3 rounded shadow-sm text-start ${bg}" style="max-width: 80%;">
                <div style="white-space: pre-wrap;">${r.message_body}</div>
                ${attachmentHtml}
            </div>
        </div>`;
        chatBox.append(bubble);
    });

    // Auto-scroll to bottom of chat
    chatBox.scrollTop(chatBox[0].scrollHeight);
};

$(document).ready(function() {
    
    // 1. Polling: Sync tickets every 15 seconds IF the support tab is active
    setInterval(function() {
        if ($('#support').hasClass('active')) {
            window.fetchTickets();
        }
    }, 15000);

    // 2. Trigger fetch when tab is clicked
    $('#support-tab').on('shown.bs.tab', function () {
        window.fetchTickets();
    });

    // 3. UI Navigation: Open "New Ticket" View
    $('#btnNewTicket').on('click', function() {
        window.activeTicketId = null;
        $('#supportListView').addClass('d-none');
        $('#supportThreadView').removeClass('d-none');
        $('#chatHistory').addClass('d-none');
        $('#newTicketFields').removeClass('d-none');
        $('#ticketSubject').prop('required', true);
        $('#threadTitle').html('<i class="bi bi-plus-circle me-1"></i> Create Support Ticket');
        $('#ticketForm')[0].reset();
    });

    // 4. UI Navigation: Back to List
    $('#btnBackToTickets').on('click', function() {
        window.activeTicketId = null;
        $('#supportThreadView').addClass('d-none');
        $('#supportListView').removeClass('d-none');
        window.fetchTickets(); // Force a fresh sync
    });

    // 5. Form Submit (Using FormData for Image Support)
    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSubmitTicket');
        let originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Transmitting...');

        let formData = new FormData();
        formData.append('message', $('#ticketMessage').val());
        
        // If it's a new ticket, include subject & priority
        if (window.activeTicketId === null) {
            formData.append('subject', $('#ticketSubject').val());
            formData.append('priority', $('#ticketPriority').val());
        } else {
            // Future-proofing: If we build a reply endpoint later
            formData.append('ticket_id', window.activeTicketId); 
        }

        let fileInput = $('#ticketAttachment')[0];
        if (fileInput.files.length > 0) {
            formData.append('attachment', fileInput.files[0]);
        }

        $.ajax({
            url: '/ajax/submit_ticket.php',
            type: 'POST',
            data: formData,
            contentType: false, // Required for FormData
            processData: false, // Required for FormData
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#ticketForm')[0].reset();
                    if (res.warning) alert(res.warning); // Show if local saved but central offline
                    
                    // Go back to list view instantly to see the new ticket
                    $('#btnBackToTickets').trigger('click');
                } else {
                    alert("Error: " + res.error);
                }
            },
            error: function() { alert("Network Error during transmission."); },
            complete: function() { btn.prop('disabled', false).html(originalHtml); }
        });
    });
});