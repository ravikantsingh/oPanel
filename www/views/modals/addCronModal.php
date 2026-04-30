<div class="modal fade" id="addCronModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Scheduled Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCronForm">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Linux User</label>
                        <select class="form-select user-dropdown" name="username" required>
                            <option value="">Loading users...</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col"><label class="form-label small text-muted">Minute</label><input type="text" class="form-control text-center" name="minute" value="*" required></div>
                        <div class="col"><label class="form-label small text-muted">Hour</label><input type="text" class="form-control text-center" name="hour" value="*" required></div>
                        <div class="col"><label class="form-label small text-muted">Day</label><input type="text" class="form-control text-center" name="day" value="*" required></div>
                        <div class="col"><label class="form-label small text-muted">Month</label><input type="text" class="form-control text-center" name="month" value="*" required></div>
                        <div class="col"><label class="form-label small text-muted">Weekday</label><input type="text" class="form-control text-center" name="weekday" value="*" required></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Command to Execute</label>
                        <input type="text" class="form-control font-monospace text-sm" name="command" placeholder="php /home/user1/web/domain.com/public_html/artisan schedule:run" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCronBtn">Save Cron Job</button>
            </div>
        </div>
    </div>
</div>