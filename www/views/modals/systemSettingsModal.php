<div class="modal fade" id="systemSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-bottom">
                <h5 class="modal-title"><i class="bi bi-sliders"></i> oPanel Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <h6 class="border-bottom pb-2 mb-3 text-danger"><i class="bi bi-fingerprint"></i> Administrator Security</h6>
                <div class="form-check form-switch fs-5 mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="twoFactorToggle">
                    <label class="form-check-label" for="twoFactorToggle">Require 2FA for Admin Login</label>
                </div>
                <div id="qrCodeContainer" class="d-none text-center p-3 border rounded bg-white mb-4 shadow-sm">
                    <h6 class="text-success"><i class="bi bi-shield-lock-fill"></i> 2FA Enabled!</h6>
                    <p class="text-muted small mb-2">Scan this QR Code with Google Authenticator immediately:</p>
                    <img id="qrCodeImage" src="" alt="2FA QR Code" class="img-thumbnail mb-2" style="width: 150px; height: 150px;">
                    <p class="small text-muted mb-1">Or enter this manual secret key:</p>
                    <p class="fw-bold font-monospace fs-5 text-dark mb-0" id="totpSecretText"></p>
                </div>
                
                <h6 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-shield-lock"></i> Secure Panel Domain</h6>
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i> Bind this oPanel to a real domain name with a free Let's Encrypt SSL certificate. <strong>DNS must point to this server first!</strong>
                </div>
                
                <form id="securePanelForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Panel Domain Name (e.g., cp.yourdomain.com)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-globe"></i></span>
                            <input type="text" class="form-control" name="domain" placeholder="cp.example.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Admin Email (For SSL Expiry Alerts)</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div id="securePanelAlert" class="alert d-none mt-3"></div>
                    <button type="button" class="btn btn-dark w-100" id="submitSecurePanelBtn">
                        <i class="bi bi-lock-fill"></i> Secure oPanel
                    </button>
                </form>
                <hr class="my-4 border-secondary border-opacity-25">
                <h6 class="pb-2 mb-3 text-warning"><i class="bi bi-clock-history"></i> Global Server Time</h6>
                <form id="serverTimezoneForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Master System Time Zone</label>
                        <div class="input-group">
                            <select class="form-select" name="timezone" id="serverTimezoneSelect">
                                <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                                <option value="UTC">Universal Time (UTC)</option>
                                <option value="America/New_York">America/New_York (EST)</option>
                                <option value="Europe/London">Europe/London (GMT)</option>
                                <option value="Australia/Sydney">Australia/Sydney (AEST)</option>
                            </select>
                            <button type="button" class="btn btn-dark" id="submitTimezoneBtn">Sync Server Time</button>
                        </div>
                        <div class="form-text" style="font-size: 0.75rem;">This permanently shifts the Linux Kernel, PHP, MariaDB, and automated cron schedules to the selected timezone.</div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>