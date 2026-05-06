<div class="modal fade" id="systemSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-bottom border-primary border-3">
                <h5 class="modal-title"><i class="bi bi-sliders me-2 text-primary"></i> oPanel System Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body bg-light p-0">
                
                <!-- NAVIGATION TABS -->
                <ul class="nav nav-tabs px-3 pt-3 bg-white border-bottom shadow-sm" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-danger" data-bs-toggle="tab" data-bs-target="#sys-tab-security" type="button"><i class="bi bi-fingerprint"></i> Security & Access</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-primary" data-bs-toggle="tab" data-bs-target="#sys-tab-routing" type="button"><i class="bi bi-shield-lock"></i> Panel Routing</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-warning" data-bs-toggle="tab" data-bs-target="#sys-tab-env" type="button"><i class="bi bi-clock-history"></i> Environment</button>
                    </li>
                </ul>

                <div class="tab-content p-4">
                    
                    <!-- TAB 1: SECURITY & ACCESS -->
                    <div class="tab-pane fade show active" id="sys-tab-security" role="tabpanel">
                        <div class="alert alert-secondary small mb-4">
                            <i class="bi bi-info-circle"></i> Configure high-level security protocols for oPanel administration.
                        </div>

                        <!-- 2FA Toggle -->
                        <div class="form-check form-switch fs-5 mb-3 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="form-check-label fw-bold text-dark" for="twoFactorToggle">Require 2FA for Admin Login</label>
                                    <div class="form-text mt-0" style="font-size: 0.75rem;">Protect your master login with Google Authenticator or Authy.</div>
                                </div>
                                <input class="form-check-input" type="checkbox" role="switch" id="twoFactorToggle">
                            </div>
                        </div>

                        <!-- QR Code Container (Hidden by default) -->
                        <div id="qrCodeContainer" class="d-none text-center p-3 border rounded bg-white mb-4 shadow-sm">
                            <h6 class="text-success fw-bold"><i class="bi bi-shield-check"></i> 2FA Enabled!</h6>
                            <p class="text-muted small mb-2">Scan this QR Code with your authenticator app immediately:</p>
                            <img id="qrCodeImage" src="" alt="2FA QR Code" class="img-thumbnail mb-2" style="width: 150px; height: 150px;">
                            <p class="small text-muted mb-1">Or enter this manual secret key:</p>
                            <p class="fw-bold font-monospace fs-5 text-dark mb-0" id="totpSecretText"></p>
                        </div>

                        <!-- Master WAF Toggle -->
                        <div class="form-check form-switch fs-5 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="form-check-label fw-bold text-dark" for="masterWafToggle">Enable Master Panel WAF</label>
                                    <div class="form-text mt-0 text-danger" style="font-size: 0.75rem;">If oPanel blocks legitimate saves (403 Error), disable this temporarily.</div>
                                </div>
                                <input class="form-check-input border-danger" type="checkbox" role="switch" id="masterWafToggle">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: PANEL ROUTING -->
                    <div class="tab-pane fade" id="sys-tab-routing" role="tabpanel">
                        <div class="alert alert-info small mb-4">
                            <i class="bi bi-info-circle"></i> Bind oPanel to an existing domain on this server to utilize its Let's Encrypt SSL certificate. <strong>The domain must already be created and secured in the Web tab.</strong>
                        </div>

                        <div class="bg-white p-3 border rounded shadow-sm">
                            <form id="securePanelForm">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Select Active Domain</label>
                                    <select class="form-select border-secondary domain-dropdown fw-bold text-dark" name="domain" id="masterDomainSelect" required>
                                        <option value="">Select a secured domain...</option>
                                        <!-- Populated dynamically by panel.js -->
                                    </select>
                                </div>
                                
                                <div id="securePanelAlert" class="alert d-none mt-3 small py-2"></div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="button" class="btn btn-dark w-75 fw-bold" id="submitSecurePanelBtn">
                                        <i class="bi bi-link-45deg"></i> Bind to Panel
                                    </button>
                                    <button type="button" class="btn btn-outline-danger w-25 fw-bold" id="unbindPanelBtn" title="Revert to IP & Self-Signed Cert">
                                        <i class="bi bi-x-circle"></i> Unbind
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TAB 3: ENVIRONMENT -->
                    <div class="tab-pane fade" id="sys-tab-env" role="tabpanel">
                        <div class="alert alert-warning text-dark small mb-4">
                            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Warning:</strong> This permanently shifts the Linux Kernel, PHP, MariaDB, and automated cron schedules to the selected timezone.
                        </div>

                        <div class="bg-white p-3 border rounded shadow-sm">
                            <form id="serverTimezoneForm">
                                <div class="mb-0">
                                    <label class="form-label small fw-bold text-muted">Master System Time Zone</label>
                                    <div class="input-group">
                                        <select class="form-select fw-bold text-dark border-secondary" name="timezone" id="serverTimezoneSelect">
                                            <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                                            <option value="UTC">Universal Time (UTC)</option>
                                            <option value="America/New_York">America/New_York (EST)</option>
                                            <option value="Europe/London">Europe/London (GMT)</option>
                                            <option value="Australia/Sydney">Australia/Sydney (AEST)</option>
                                        </select>
                                        <button type="button" class="btn btn-dark fw-bold" id="submitTimezoneBtn"><i class="bi bi-arrow-repeat"></i> Sync Server Time</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div> <!-- End Tab Content -->
            </div>
        </div>
    </div>
</div>