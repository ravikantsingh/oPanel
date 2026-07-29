<div class="modal fade" id="wafSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-check me-2"></i>Global WAF Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="wafSettingsForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">OWASP Core Rule Set Version</label>
                        <select class="form-select border-secondary" id="wafVersionSelect" name="waf_branch">
                            <option value="v3.3/master">v3.3 (Legacy Stable - Ubuntu Default)</option>
                            <option value="lts/v4.25.x">v4.25 (Long-Term Support - Recommended)</option>
                        </select>
                        
                        
                        <div class="form-text text-muted mt-2 lh-sm">
                            <i class="bi bi-info-circle-fill text-primary me-1"></i> 
                            Changes will be compiled and applied <strong>instantly</strong>. Stackrium performs a strict syntax dry-run before reloading the web server to guarantee zero downtime.
                        </div>
                    </div>
                    
                    <div class="alert alert-danger d-none" id="wafSettingsAlert"></div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold" id="saveWafSettingsBtn">
                        <i class="bi bi-save me-2"></i>Apply WAF Engine Update
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>