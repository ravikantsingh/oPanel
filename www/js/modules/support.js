// /opt/panel/www/js/modules/support.js

window.activeTicketId = null; 
window.globalReplies = []; 

window.fetchTickets = function() {
    $.ajax({
        url: '/ajax/sync_tickets.php',
        type: 'POST',
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                window.globalReplies = res.replies; 
                window.renderTicketList(res.tickets);
                
                if (window.activeTicketId !== null) {
                    window.renderChatThread(window.activeTicketId);
                }
            }
        }
    });
};

window.renderTicketList = function(tickets) {
    let list = $('#dynamicTicketList');
    list.empty();

    // OPTION 1: Update the Navigation Sidebar Badge
    let unreadCount = tickets.filter(t => t.is_unread == 1).length;
    let tabHtml = '<i class="bi bi-life-preserver"></i> Support Desk';
    if (unreadCount > 0) {
        tabHtml += ` <span class="badge bg-danger rounded-circle ms-1 bounce-anim">${unreadCount}</span>`;
    }
    $('#support-tab').html(tabHtml);

    if (tickets.length === 0) {
        list.html('<div class="list-group-item text-center text-muted py-5 border-0"><i class="bi bi-inboxes fs-1 d-block mb-2 text-light"></i>No support tickets found.</div>');
        return;
    }

    tickets.forEach(t => {
        let statusBadge = t.status === 'Open' ? '<span class="badge bg-primary">Open</span>' : 
                         (t.status === 'Answered' ? '<span class="badge bg-success">Answered</span>' : '<span class="badge bg-secondary">Closed</span>');
        
        let priorityColor = t.priority === 'Critical' ? 'text-danger' : (t.priority === 'High' ? 'text-warning' : 'text-muted');
        let globalTicketNum = t.ticket_number ? '#' + t.ticket_number : '#SYNCING...';
        
        // Unread highlighting styling
        let bgClass = t.is_unread == 1 ? 'bg-light border-start border-4 border-danger' : 'border-0 border-bottom';
        let newIndicator = t.is_unread == 1 ? '<span class="badge bg-danger ms-2">NEW REPLY</span>' : '';

        let row = `
        <button class="list-group-item list-group-item-action py-3 px-3 ${bgClass} view-ticket" 
                data-id="${t.id}" 
                data-central-id="${t.central_id || ''}"
                data-ticket-number="${t.ticket_number || ''}" 
                data-subject="${t.subject}" 
                data-status="${t.status}" 
                style="cursor: pointer;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold text-dark"><span class="text-secondary small me-1">${globalTicketNum}</span> ${t.subject} ${newIndicator}</h6>
                    <small class="text-muted"><i class="bi bi-flag-fill ${priorityColor} me-1"></i> ${t.priority} &bull; Updated: ${t.updated_at}</small>
                </div>
                <div>${statusBadge}</div>
            </div>
        </button>`;
        list.append(row);
    });
};

window.renderChatThread = function(ticketId) {
    let chatBox = $('#chatHistory');
    chatBox.empty().removeClass('d-none');
    
    let thread = window.globalReplies.filter(r => parseInt(r.ticket_id) === parseInt(ticketId));
    
    thread.forEach(r => {
        let isClient = r.sender_type === 'Client';
        let align = isClient ? 'text-end' : 'text-start';
        let bg = isClient ? 'bg-primary text-white' : 'bg-white border text-dark';
        let icon = isClient ? 'bi-person-fill' : 'bi-headset';
        let senderName = isClient ? 'You' : 'Stackrium Support';
        
        // Option 2: Markdown Injection
        let msgHtml = typeof marked !== 'undefined' ? marked.parse(r.message_body, {breaks: true}) : r.message_body;

        let attachmentHtml = r.file_path ? `<div class="mt-2"><a href="${r.file_path}" target="_blank" class="badge bg-light text-dark border text-decoration-none"><i class="bi bi-paperclip"></i> ${r.file_name}</a></div>` : '';

        let bubble = `
        <div class="mb-3 ${align}">
            <div class="small text-muted mb-1"><i class="bi ${icon}"></i> ${senderName} &bull; ${r.created_at}</div>
            <div class="d-inline-block p-3 rounded shadow-sm text-start ${bg}" style="max-width: 80%;">
                <div>${msgHtml}</div>
                ${attachmentHtml}
            </div>
        </div>`;
        chatBox.append(bubble);
    });

    chatBox.scrollTop(chatBox[0].scrollHeight);
};

$(document).ready(function() {
    
    setInterval(function() {
        if ($('#support').hasClass('active')) {
            window.fetchTickets();
        }
    }, 15000);

    $('#support-tab').on('shown.bs.tab', function () {
        window.fetchTickets();
    });

    $('#btnNewTicket').on('click', function() {
        window.activeTicketId = null;
        $('#supportListView').addClass('d-none');
        $('#supportThreadView').removeClass('d-none');
        $('#chatHistory').addClass('d-none');
        $('#newTicketFields').removeClass('d-none');
        $('#ticketSubject').prop('required', true);
        $('#threadTitle').html('<i class="bi bi-plus-circle me-1"></i> Create Support Ticket');
        
        // Ensure fields are completely enabled for a new ticket
        $('#btnSubmitTicket, #ticketAttachment, #ticketMessage').prop('disabled', false);
        $('#ticketMessage').attr('placeholder', 'Describe the issue, include error codes or Nginx logs...');
        
        $('#ticketForm')[0].reset();
    });

    $('#btnBackToTickets').on('click', function() {
        window.activeTicketId = null;
        $('#supportThreadView').addClass('d-none');
        $('#supportListView').removeClass('d-none');
        window.fetchTickets(); 
    });

    /// Handle opening a ticket (FIXED)
    $(document).on('click', '.view-ticket', function() {
        let ticketId = $(this).data('id');
        let ticketNum = $(this).data('ticket-number');
        let subject = $(this).data('subject');
        let status = $(this).data('status'); 

        window.activeTicketId = ticketId;
        let globalTicketNum = ticketNum ? '#' + ticketNum : '#SYNCING...';

        // OPTION 1: Clear the Unread Flag instantly when clicked
        if ($(this).hasClass('border-danger')) {
            $.post('/ajax/mark_ticket_read.php', { ticket_id: ticketId });
            window.fetchTickets(); // Soft refresh to clear badges
        }

        $('#supportListView').addClass('d-none');
        $('#supportThreadView').removeClass('d-none');
        $('#newTicketFields').addClass('d-none');
        $('#ticketSubject').prop('required', false);

        $('#threadTitle').html(`<i class="bi bi-chat-text me-1"></i> <span class="text-warning me-2">${globalTicketNum}</span> ${subject}`);
        
        if (status === 'Closed') {
            $('#btnCloseTicket').addClass('d-none');
            $('#btnSubmitTicket, #ticketAttachment').prop('disabled', true);
            $('#ticketMessage').prop('disabled', true).attr('placeholder', 'This support ticket has been marked as closed.');
        } else {
            $('#btnCloseTicket').removeClass('d-none');
            $('#btnSubmitTicket, #ticketAttachment').prop('disabled', false); 
            $('#ticketMessage').prop('disabled', false).attr('placeholder', 'Type a follow-up message... (Markdown supported)');
        }

        window.renderChatThread(ticketId);
    });

    $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSubmitTicket');
        let originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Transmitting...');

        let formData = new FormData();
        formData.append('message', $('#ticketMessage').val());
        
        if (window.activeTicketId === null) {
            formData.append('subject', $('#ticketSubject').val());
            formData.append('priority', $('#ticketPriority').val());
        } else {
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
            contentType: false, 
            processData: false, 
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#ticketForm')[0].reset();
                    if (res.warning) alert(res.warning); 
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