<div class="modal fade" id="backupDbModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light"><h5 class="modal-title"><i class="bi bi-database text-warning"></i> Backup Database</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="backupDbForm">
                    <input type="hidden" name="action" value="backup_db">
                    <label class="form-label small fw-bold">Select Database to Dump</label>
                    
                    <!---THE FIX: Changed from Text Input to Dropdown  -->
                    <select class="form-select db-dropdown" name="target" required>
                        <option value="">Loading databases...</option>
                    </select>
                    
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-warning w-100" id="submitBackupDbBtn">Generate SQL Dump</button></div>
        </div>
    </div>
</div>