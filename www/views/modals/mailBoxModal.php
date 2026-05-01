<!-- /opt/panel/www/views/modals/mailBoxModal.php -->
<div class="modal fade" id="mailBoxModal" tabindex="-1" aria-labelledby="mailBoxModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="mailBoxModalLabel"><i class="bi bi-envelope"></i> Mail Routing: <span id="mailDomainTitle" class="fw-bold"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        
        <!-- THE NEW ROUTING TOGGLE -->
        <ul class="nav nav-pills nav-fill bg-light rounded p-1 mb-4 border" id="mailModeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="local-mail-tab" data-bs-toggle="pill" data-bs-target="#localMailMode" type="button"><i class="bi bi-hdd-network"></i> Host Locally (oPanel)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="external-mail-tab" data-bs-toggle="pill" data-bs-target="#externalMailMode" type="button"><i class="bi bi-cloud-check"></i> External Provider (DNS)</button>
            </li>
        </ul>

        <div class="tab-content" id="mailModeContent">
            
            <!-- MODE A: LOCAL HOSTING -->
            <div class="tab-pane fade show active" id="localMailMode" role="tabpanel">
                
                <!-- STATE 1: ENGINE NOT INSTALLED -->
                <div id="mailEngineNotInstalled" class="text-center py-5 d-none">
                    <i class="bi bi-hdd-network display-1 text-secondary mb-3"></i>
                    <h5 class="fw-bold text-dark">Local Mail Engine is Offline</h5>
                    <p class="text-muted small w-75 mx-auto">To conserve RAM and CPU, the local Postfix/Dovecot mail stack is not installed by default. Click below to install it securely in the background.</p>
                    <button class="btn btn-primary fw-bold mt-3" id="installMailEngineBtn"><i class="bi bi-download"></i> Install Mail Engine</button>
                </div>

                <!-- STATE 2: ENGINE IS INSTALLED (Your existing form) -->
                <div id="mailEngineInstalled" class="d-none">
                    <div class="card shadow-sm mb-4 border-0 bg-light">
                        <div class="card-body">
                            <h6 class="mb-3 text-primary"><i class="bi bi-plus-circle"></i> Create New Mailbox</h6>
                            <form id="createMailForm" class="row g-2 align-items-end">
                                <input type="hidden" id="mailDomain" name="domain">
                                <input type="hidden" name="action" value="add">
                                
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Email Prefix</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" name="prefix" placeholder="admin" required pattern="[a-zA-Z0-9.-_]+">
                                        <span class="input-group-text text-muted" id="mailSuffixLabel">@domain.com</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Secure Password</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="mailPassInput" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="generateMailPass" title="Generate Password"><i class="bi bi-magic"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold" id="submitMailBtn">
                                        <i class="bi bi-save"></i> Provision Mailbox
                                    </button>
                                </div>
                            </form>
                            <div id="mailAlert" class="alert d-none mt-3 mb-0 py-2 small"></div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted border-bottom pb-2 mb-3">Active Accounts</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Email Address</th>
                                    <th>Disk Quota</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="dynamicMailTable">
                                <tr><td colspan="3" class="text-center text-muted">Loading mailboxes...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- DANGER ZONE: Uninstall -->
                    <div class="alert alert-danger mt-4 mb-0 border-danger border-2 border-start-0 border-end-0 border-bottom-0 rounded-0 pt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-danger fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill"></i> Uninstall Mail Engine</h6>
                                <p class="small mb-0 text-dark">Purge Postfix/Dovecot to reclaim RAM. <strong class="text-danger">All local emails will be permanently deleted.</strong></p>
                            </div>
                            <button class="btn btn-sm btn-danger fw-bold" id="uninstallMailEngineBtn">Uninstall Engine</button>
                        </div>
                    </div>
                </div> <!-- End State 2 -->

            </div>

            <!-- MODE B: EXTERNAL ROUTING (The missing piece!) -->
            <div class="tab-pane fade" id="externalMailMode" role="tabpanel">
                <div class="alert alert-info border-info small mb-4">
                    <i class="bi bi-info-circle-fill"></i> Selecting a provider below will automatically overwrite this domain's MX, SPF, and DMARC records in the BIND9 DNS server.
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm text-center h-100">
                            <div class="card-body">
                                <h5 class="fw-bold text-dark"><i class="bi bi-google text-success"></i> Google Workspace</h5>
                                <p class="text-muted small">Auto-configures strict SPF and Google MX routing.</p>
                                <button class="btn btn-outline-dark w-100 fw-bold route-external-mail" data-provider="google">1-Click Connect</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm text-center h-100">
                            <div class="card-body">
                                <h5 class="fw-bold text-dark"><i class="bi bi-microsoft text-primary"></i> Microsoft 365</h5>
                                <p class="text-muted small">Auto-configures Exchange MX and Autodiscover CNAME.</p>
                                <button class="btn btn-outline-dark w-100 fw-bold route-external-mail" data-provider="microsoft">1-Click Connect</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
      </div>
    </div>
  </div>
</div>