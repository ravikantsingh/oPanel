<div class="modal fade" id="transferDbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom border-warning border-3">
                <h5 class="modal-title"><i class="bi bi-person-gear text-warning me-2"></i> Transfer Workspace Ownership</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="transferDbForm">
                    <input type="hidden" name="action" value="transfer_owner">
                    <input type="hidden" name="db_name" id="transferTargetDb">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Database Name</label>
                        <input type="text" class="form-control font-monospace bg-white" id="transferDbDisplay" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Current Owner</label>
                        <input type="text" class="form-control bg-white" id="transferCurrentOwner" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select New Owner</label>
                        <select class="form-select user-dropdown" name="new_owner" id="transferNewOwner" required>
                            <option value="">Loading users...</option>
                        </select>
                    </div>

                    <div class="alert alert-success small mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i> <strong>Soft Move:</strong> Transfers control and automated backup routing without modifying the physical schema name or breaking live application connection strings.
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning px-4 fw-bold" id="submitTransferDbBtn"><i class="bi bi-arrow-right-circle"></i> Transfer Owner</button>
            </div>
        </div>
    </div>
</div>