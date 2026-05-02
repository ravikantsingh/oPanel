<!-- /opt/panel/www/views/modals/installSslModal.php -->
<div class="modal fade" id="installSslModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white border-bottom border-success border-3">
        <h5 class="modal-title"><i class="bi bi-shield-lock-fill me-2 text-success"></i> Manage SSL & Security</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light p-0">
        
        <!-- MASTER DOMAIN SELECTOR -->
        <div class="p-3 bg-white border-bottom shadow-sm">
            <label class="form-label small fw-bold text-muted mb-1">Target Domain</label>
            <select class="form-select border-secondary domain-dropdown fw-bold text-dark" id="sslTargetDomain" name="domain" required>
                <option value="">Select a domain to manage...</option>
            </select>
        </div>

        <!-- NAVIGATION TABS -->
        <ul class="nav nav-tabs px-3 pt-3 bg-white" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#ssl-tab-overview" type="button"><i class="bi bi-activity"></i> Lifecycle</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#ssl-tab-custom" type="button"><i class="bi bi-key"></i> Custom SSL</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-danger" data-bs-toggle="tab" data-bs-target="#ssl-tab-routing" type="button"><i class="bi bi-sign-turn-right"></i> Advanced Routing</button>
            </li>
        </ul>

        <div class="tab-content p-4">
            
            <!-- TAB 1: OVERVIEW & LET'S ENCRYPT -->
            <div class="tab-pane fade show active" id="ssl-tab-overview" role="tabpanel">
                
                <!-- STATE A: NO SSL INSTALLED -->
                <div id="sslStateUnsecured">
                    <div class="text-center py-4">
                        <i class="bi bi-shield-x display-4 text-secondary mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">Connection Not Secure</h5>
                        <p class="text-muted small w-75 mx-auto">This domain currently does not have an active SSL certificate. You can issue a free Let's Encrypt certificate below.</p>
                        
                        <form id="issueLetsEncryptForm" class="mt-4 text-start bg-white p-3 rounded border">
                            <input type="hidden" name="domain" class="sync-domain">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Admin Email (For Expiry Notices)</label>
                                <input type="email" class="form-control" name="email" placeholder="admin@example.com" required>
                            </div>
                            <div class="alert alert-info small py-2 mb-3">
                                <i class="bi bi-info-circle"></i> DNS must point to this server's IP globally before issuing.
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold" id="btnIssueLe"><i class="bi bi-lightning-charge"></i> Issue Let's Encrypt SSL</button>
                        </form>
                    </div>
                </div>

                <!-- STATE B: SSL IS ACTIVE -->
                <div id="sslStateSecured" class="d-none">
                    <div class="card border-success border-2 shadow-sm mb-4">
                        <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="bi bi-patch-check-fill"></i> Certificate Active</span>
                            <span class="badge bg-light text-success font-monospace" id="sslIssuerDisplay">Let's Encrypt</span>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-6 border-end">
                                    <div class="small text-muted fw-bold">Valid From</div>
                                    <div class="text-dark font-monospace small" id="sslValidFrom">--</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted fw-bold">Valid Until</div>
                                    <div class="text-dark font-monospace small" id="sslValidUntil">--</div>
                                </div>
                            </div>
                            <div class="mb-1 d-flex justify-content-between small fw-bold">
                                <span>Time Remaining</span>
                                <span id="sslDaysRemainingText">-- Days</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="sslDaysBar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted border-bottom pb-2 mb-3">Lifecycle Controls</h6>
                    <div class="d-flex justify-content-between align-items-center p-3 bg-white border rounded mb-3">
                        <div>
                            <div class="fw-bold text-dark">Automated Renewals</div>
                            <div class="small text-muted">Cron checks expiration and renews automatically.</div>
                        </div>
                        <div class="form-check form-switch fs-4 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="sslAutoRenewToggle">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-dark w-50" id="btnForceRenew"><i class="bi bi-arrow-clockwise"></i> Force Renew Now</button>
                        <button class="btn btn-outline-danger w-50" id="btnRevokeSsl"><i class="bi bi-trash"></i> Revoke & Delete</button>
                    </div>
                </div>

            </div>

            <!-- TAB 2: CUSTOM SSL UPLOAD -->
            <div class="tab-pane fade" id="ssl-tab-custom" role="tabpanel">
                <div class="alert alert-secondary small">
                    <i class="bi bi-info-circle"></i> Upload a third-party certificate (e.g., Cloudflare Origin, DigiCert). Ensure your private key is unencrypted.
                </div>
                <form id="customSslForm">
                    <input type="hidden" name="domain" class="sync-domain">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Private Key (.key)</label>
                        <textarea class="form-control font-monospace text-success bg-dark text-sm" name="private_key" rows="4" placeholder="-----BEGIN PRIVATE KEY-----" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Certificate (.crt / .pem)</label>
                        <textarea class="form-control font-monospace bg-light text-sm" name="certificate" rows="4" placeholder="-----BEGIN CERTIFICATE-----" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">CA Bundle / Chain <span class="badge bg-secondary">Optional</span></label>
                        <textarea class="form-control font-monospace bg-light text-sm" name="ca_bundle" rows="3" placeholder="Paste intermediate certificates here..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-dark w-100" id="btnInstallCustomSsl"><i class="bi bi-upload"></i> Install Custom Certificate</button>
                </form>
            </div>

            <!-- TAB 3: ADVANCED ROUTING (HSTS) -->
            <div class="tab-pane fade" id="ssl-tab-routing" role="tabpanel">
                <form id="sslRoutingForm">
                    <input type="hidden" name="domain" class="sync-domain">
                    
                    <!-- HTTPS Redirect -->
                    <div class="d-flex justify-content-between align-items-center p-3 bg-white border rounded mb-4 shadow-sm">
                        <div>
                            <div class="fw-bold text-dark"><i class="bi bi-sign-turn-right text-primary"></i> Force HTTPS Redirect</div>
                            <div class="small text-muted">Automatically redirect HTTP (Port 80) traffic to HTTPS.</div>
                        </div>
                        <div class="form-check form-switch fs-4 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="force_https" id="forceHttpsToggle">
                        </div>
                    </div>

                    <!-- HSTS Section -->
                    <div class="alert alert-danger border-danger border-start-0 border-end-0 border-bottom-0 border-3 rounded-0 bg-white shadow-sm p-3">
                        <h6 class="fw-bold text-danger mb-2"><i class="bi bi-shield-lock-fill"></i> Strict Transport Security (HSTS)</h6>
                        <p class="small text-muted mb-3">HSTS instructs browsers to <strong>never</strong> connect to this domain over insecure HTTP. If your SSL expires, your site will be inaccessible. Proceed with caution.</p>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input border-danger" type="checkbox" role="switch" name="enable_hsts" id="hstsToggle">
                            <label class="form-check-label fw-bold text-dark" for="hstsToggle">Enable HSTS Directives</label>
                        </div>

                        <div class="hsts-controls opacity-50" style="pointer-events: none;">
                            <label class="form-label small fw-bold text-dark mt-2 d-flex justify-content-between">
                                <span>Max-Age Directive (Duration)</span>
                                <span class="badge bg-danger" id="hstsDurationLabel">6 Months</span>
                            </label>
                            <!-- Sliders in Seconds: 1 Month to 2 Years -->
                            <input type="range" class="form-range" name="hsts_max_age" id="hstsSlider" min="2592000" max="63072000" step="2592000" value="15552000">
                            
                            <div class="d-flex justify-content-between mt-3">
                                <div class="form-check">
                                    <input class="form-check-input border-secondary" type="checkbox" name="hsts_subdomains" id="hstsSubdomains">
                                    <label class="form-check-label small text-dark" for="hstsSubdomains">includeSubDomains</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input border-secondary" type="checkbox" name="hsts_preload" id="hstsPreload">
                                    <label class="form-check-label small text-dark" for="hstsPreload">preload</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-100 fw-bold" id="btnSaveRouting"><i class="bi bi-save"></i> Apply Security Rules</button>
                </form>
            </div>

        </div> <!-- End Tab Content -->
      </div>
    </div>
  </div>
</div>