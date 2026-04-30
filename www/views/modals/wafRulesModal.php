<div class="modal fade" id="wafRulesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Custom WAF Exceptions: <span id="wafDomainTitle" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> Invalid syntax here will be rejected by the server to prevent crashing Nginx. 
                    Use <code>SecRuleRemoveById 123456</code> to disable false positives.
                </div>
                <form id="wafRulesForm">
                    <input type="hidden" id="wafDomainInput" name="domain">
                    <textarea class="form-control font-monospace bg-dark text-light" id="wafRulesTextarea" name="custom_rules" rows="10" placeholder="# Enter ModSecurity directives here..."></textarea>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveWafRulesBtn">Compile & Apply Rules</button>
            </div>
        </div>
    </div>
</div>