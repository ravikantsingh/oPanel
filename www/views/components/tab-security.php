    <div class="tab-pane fade" id="security" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Firewall Rules & DNS</h5>
            <div>
                <button class="btn btn-sm btn-dark me-2" id="initDnsZoneBtn"><i class="bi bi-magic"></i> Initialize New Zone</button>
                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#firewallModal"><i class="bi bi-shield-lock"></i> Open Port</button>
                <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#installSslModal"><i class="bi bi-lock"></i> Install SSL</button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#dnsRecordModal"><i class="bi bi-globe"></i> Manage DNS</button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white"><h6 class="mb-0">Active Firewall Rules (UFW)</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-sm align-middle">
                            <thead class="table-light">
                                <tr><th>Port</th><th>Protocol</th><th>Status</th><th class="text-end">Action</th></tr>
                            </thead>
                            <tbody id="dynamicFirewallTable">
                                <tr><td colspan="3" class="text-center text-muted py-3">Loading rules...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0 border-top border-danger border-3">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 text-danger fw-bold"><i class="bi bi-shield-slash-fill me-2"></i> Active Intrusion Blocks (Fail2ban)</h6>
                            <button class="btn btn-sm btn-outline-secondary" onclick="fetchFail2Ban()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                        </div>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-hover mb-0 text-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Banned IP Address</th>
                                        <th>Triggered Security Jail</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="dynamicFail2banTable">
                                    <tr><td colspan="3" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div> Scanning Jails...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white"><h6 class="mb-0">Active DNS Records (BIND9)</h6></div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0 text-sm align-middle">
                            <thead class="table-light">
                                <tr><th>Zone</th><th>Name</th><th>Type</th><th>Value</th><th class="text-end">Action</th></tr>
                            </thead>
                            <tbody id="dynamicDnsTable">
                                <tr><td colspan="4" class="text-center text-muted py-3">Loading DNS records...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>