<div class="modal fade" id="backupWebModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light"><h5 class="modal-title"><i class="bi bi-globe text-info"></i> Backup Website</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="backupWebForm">
                    <input type="hidden" name="action" value="backup_web">
                    <label class="form-label small fw-bold">Select Domain to Archive</label>
                    <select class="form-select domain-dropdown" name="target" required><option value="">Loading...</option></select>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-info text-white w-100" id="submitBackupWebBtn">Generate Web Archive</button></div>
        </div>
    </div>
</div>