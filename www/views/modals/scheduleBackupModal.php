<div class="modal fade" id="scheduleBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title"><i class="bi bi-clock-history text-info"></i> Automated Backup Schedule</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body bg-light">
                <form id="scheduleBackupForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Target Type</label>
                        <select class="form-select" name="backup_type" id="schedType" required>
                            <option value="web">Website Domain</option>
                            <option value="db">MySQL Database</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Target</label>
                        <!-- We will toggle between domain/db dropdowns based on type -->
                        <select class="form-select domain-dropdown" name="target_web" id="schedTargetWeb"><option value="">Loading...</option></select>
                        <select class="form-select db-dropdown d-none" name="target_db" id="schedTargetDb"><option value="">Loading...</option></select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Frequency</label>
                            <select class="form-select" name="frequency" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly (Sundays)</option>
                                <option value="monthly">Monthly (1st)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Run Time (24H)</label>
                            <input type="number" class="form-control" name="run_hour" value="2" min="0" max="23" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Retention Policy (Days to Keep)</label>
                        <input type="number" class="form-control" name="retention_days" value="3" min="1" max="365" required>
                        <div class="form-text" style="font-size: 0.75rem;">Older automated backups for this target will be securely deleted.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light"><button type="button" class="btn btn-dark w-100" id="submitScheduleBtn">Save Schedule</button></div>
        </div>
    </div>
</div>