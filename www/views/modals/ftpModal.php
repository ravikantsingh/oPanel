<div class="modal fade" id="ftpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title"><i class="bi bi-hdd-network text-primary"></i> Manage FTP: <span id="ftpDomainTitle" class="fw-bold"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="ftpForm">
                    <input type="hidden" name="action" id="ftpAction" value="create">
                    <input type="hidden" name="domain" id="ftpDomain">
                    <input type="hidden" name="username" id="ftpSysUser">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">FTP Username</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="ftp_user" id="ftpUserInput" placeholder="e.g., dev_user" required>
                            <span class="input-group-text bg-white text-muted" id="ftpSuffix">@domain.com</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted d-flex justify-content-between">
                            FTP Password
                            <a href="#" class="text-decoration-none" id="generateFtpPass"><i class="bi bi-magic"></i> Generate Random</a>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" name="ftp_pass" id="ftpPassInput" placeholder="Enter or generate password" required>
                            <button class="btn btn-outline-secondary copy-btn" type="button" data-target="ftpPassInput"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-danger d-none" id="deleteFtpBtn"><i class="bi bi-trash"></i> Delete User</button>
                        <button type="button" class="btn btn-primary w-100 ms-2" id="saveFtpBtn"><i class="bi bi-save"></i> Save FTP Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>