<div class="modal fade" id="rotateFmPassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title"><i class="bi bi-key text-dark"></i> Rotate FM Password: <span id="rotateFmDomainTitle" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="rotateFmPassForm">
                    <input type="hidden" name="domain" id="rotateFmDomain">
                    <input type="hidden" name="username" id="rotateFmUser">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted d-flex justify-content-between">
                            New Password
                            <a href="#" class="text-decoration-none" id="generateRotateFmPass"><i class="bi bi-magic"></i> Generate</a>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" name="new_fm_password" id="rotateFmPassInput" required>
                            <button class="btn btn-outline-secondary copy-btn" type="button" data-target="rotateFmPassInput"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark w-50" id="submitRotateFmBtn"><i class="bi bi-save"></i> Update Key</button>
            </div>
        </div>
    </div>
</div>