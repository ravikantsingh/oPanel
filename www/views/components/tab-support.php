<div class="tab-pane fade" id="support" role="tabpanel">
    
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h5 class=\"mb-0\"><i class=\"bi bi-life-preserver text-primary me-2\"></i> Stackrium Support Center</h5>
        <button class="btn btn-sm btn-primary shadow-sm fw-bold" id="btnNewTicket">
            <i class="bi bi-pencil-square me-1"></i> Open Ticket
        </button>
    </div>

    <div id="supportListView">
        <div class="list-group shadow-sm border rounded" id="dynamicTicketList">
            <div class="list-group-item text-center text-muted py-5 border-0">
                <span class="spinner-border spinner-border-sm me-2"></span> Syncing with Central Server...
            </div>
        </div>
    </div>

    <div id="supportThreadView" class="d-none">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0" id="threadTitle">New Support Ticket</h6>
                <button class="btn btn-sm btn-outline-light border-0" id="btnBackToTickets"><i class="bi bi-arrow-left"></i> Back</button>
            </div>
            
            <div class="card-body bg-light p-3 d-none" id="chatHistory" style="max-height: 400px; overflow-y: auto;">
                </div>

            <div class="card-body border-top">
                <form id="ticketForm">
                    <div id="newTicketFields">
                        <div class="row mb-3">
                            <div class="col-md-9">
                                <input type="text" class="form-control fw-bold" id="ticketSubject" placeholder="Brief subject of the issue..." required>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="ticketPriority" required>
                                    <option value="Normal">Normal Priority</option>
                                    <option value="High">High Priority</option>
                                    <option value="Critical" class="text-danger fw-bold">Critical (Offline)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <textarea class="form-control font-monospace text-sm" id="ticketMessage" rows="4" placeholder="Describe the issue, include error codes or Nginx logs..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="input-group w-50">
                            <label class="input-group-text bg-white text-muted border-end-0" for="ticketAttachment"><i class="bi bi-paperclip"></i></label>
                            <input type="file" class="form-control border-start-0 ps-0" id="ticketAttachment" accept=".jpg,.png,.webp,.pdf">
                        </div>
                        <button type="submit" class="btn btn-success shadow-sm fw-bold px-4" id="btnSubmitTicket">
                            <i class="bi bi-send-fill me-1"></i> Send to Support
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>