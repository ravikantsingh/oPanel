<div class="modal fade" id="wafSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-shield-check me-2"></i>Global WAF Configuration</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="wafSettingsForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">OWASP Core Rule Set Version</label>
                        <select class="form-select" id="wafVersionSelect" name="waf_branch">
                            <option value="v3.3/master">v3.3 (Legacy Stable - Ubuntu Default)</option>
                            <option value="lts/v4.25.x">v4.25 (Long-Term Support - Recommended)</option>
                        </select>
                        <div class="form-text text-muted mt-2">
                            <i class="bi bi-info-circle text-primary"></i> 
                            Changes will be dynamically applied and compiled tonight at <strong>3:00 AM</strong> during the automated maintenance window to prevent live traffic interruption.
                        </div>
                    </div>
                    <div class="alert alert-danger d-none" id="wafSettingsAlert"></div>
                    <button type="submit" class="btn btn-primary w-100" id="saveWafSettingsBtn">
                        <i class="bi bi-save me-2"></i>Save Preferences
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>