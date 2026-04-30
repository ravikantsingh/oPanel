<div class="modal fade" id="pmaSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title"><i class="bi bi-gear text-dark"></i> Global Upload Limits</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i> This will update the system-wide Nginx and PHP-FPM upload limits, instantly allowing larger SQL imports in phpMyAdmin.
                </div>
                <form id="pmaSettingsForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Max Upload Size (MB)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="upload_size" value="512" min="2" max="2048" required>
                            <span class="input-group-text bg-white">MB</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Max Execution Time (Seconds)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="max_time" value="300" min="30" max="3600" required>
                            <span class="input-group-text bg-white">Sec</span>
                        </div>
                        <div class="form-text text-muted" style="font-size: 0.75rem;">Increase this if massive database imports are timing out.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark w-50" id="submitPmaSettingsBtn"><i class="bi bi-save"></i> Apply Globally</button>
            </div>
        </div>
    </div>
</div>