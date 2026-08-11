<div class="modal fade" id="repairDbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom border-dark border-3">
                <h5 class="modal-title"><i class="bi bi-tools text-dark me-2"></i> Database Diagnostics & Repair</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="repairDbForm">
                    <input type="hidden" name="action" value="check_repair">
                    <input type="hidden" name="db_name" id="repairTargetDb">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Target Database</label>
                        <input type="text" class="form-control font-monospace bg-white" id="repairDbDisplay" readonly>
                    </div>

                    <div class="p-3 bg-white border rounded shadow-sm mb-3">
                        <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-shield-check text-success me-1"></i> Diagnostic Operations Performed:</h6>
                        <ul class="small text-muted mb-0 ps-3">
                            <li><strong>Integrity Check:</strong> Scans all tables for corrupted indexes or pages.</li>
                            <li><strong>Auto-Repair:</strong> Fixes broken Aria / MyISAM / InnoDB structures.</li>
                            <li><strong>Table Optimization:</strong> Reclaims fragmented physical disk space.</li>
                        </ul>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-terminal-fill me-1"></i> The execution logs will be streamed directly to Stackrium's Live Task Terminal once dispatched.
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark px-4 fw-bold" id="submitRepairDbBtn"><i class="bi bi-play-circle"></i> Run Diagnostics</button>
            </div>
        </div>
    </div>
</div>