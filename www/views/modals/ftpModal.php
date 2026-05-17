<div class="modal fade" id="ftpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title"><i class="bi bi-hdd-network text-primary"></i> Manage FTP: <span id="ftpDomainTitle" class="fw-bold"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                
                <div class="table-responsive mb-4 bg-white rounded border shadow-sm">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-3 py-2">FTP Username</th>
                                <th class="text-end pe-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dynamicFtpTable">
                            <tr><td colspan="2" class="text-center text-muted py-3">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <h6 id="ftpFormTitle" class="mb-3 fw-bold text-dark border-bottom pb-2">Create New Account</h6>
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
                            <a href="#" class="text-decoration-none" id="generateFtpPass"><i class="bi bi-magic"></i> Generate</a>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" name="ftp_pass" id="ftpPassInput" placeholder="Enter or generate password" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary d-none" id="cancelFtpEditBtn">Cancel Edit</button>
                        <button type="button" class="btn btn-primary w-100" id="saveFtpBtn"><i class="bi bi-save"></i> Save Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>