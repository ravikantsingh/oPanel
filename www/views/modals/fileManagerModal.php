<div class="modal fade" id="fileManagerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="bi bi-folder text-warning"></i> Deploy File Manager</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i> This will securely deploy Tiny File Manager to <strong><span id="fmDomainTitle"></span>/filemanager</strong>.
                </div>
                <form id="fileManagerForm">
                    <input type="hidden" name="domain" id="fmDomain">
                    <input type="hidden" name="username" id="fmUser">
                    <input type="hidden" name="php_version" id="fmVer">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Login Username</label>
                        <input type="text" class="form-control bg-light" id="fmUserDisplay" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold d-flex justify-content-between w-100">
                            Set Access Password
                            <a href="#" class="text-decoration-none" id="generateFmPass"><i class="bi bi-magic"></i> Generate</a>
                        </label>
                        <div class="input-group">
                            <!-- Changed to type="text" so the user can copy the generated password -->
                            <input type="text" class="form-control font-monospace" name="fm_password" id="fmPassInput" placeholder="Enter or generate password" required>
                            <button class="btn btn-outline-secondary copy-btn" type="button" data-target="fmPassInput"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="saveFmBtn"><i class="bi bi-cloud-arrow-up"></i> Deploy TFM</button>
            </div>
        </div>
    </div>
</div>