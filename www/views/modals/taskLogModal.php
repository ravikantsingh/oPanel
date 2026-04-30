<div class="modal fade" id="taskLogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h6 class="modal-title font-monospace"><i class="bi bi-terminal text-success"></i> Task Execution Log: <span id="logTaskAction" class="text-warning"></span></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="bg-black p-3 font-monospace" style="height: 400px; overflow-y: auto; font-size: 0.85rem;">
                    <pre id="logTaskOutput" class="text-light text-wrap mb-0" style="white-space: pre-wrap;"></pre>
                </div>
            </div>
            <div class="modal-footer border-secondary justify-content-between py-1">
                <span class="small text-muted font-monospace" id="logTaskStatus"></span>
                <button type="button" class="btn btn-sm btn-outline-light" data-bs-dismiss="modal">Close Terminal</button>
            </div>
        </div>
    </div>
</div>