<!-- /opt/panel/www/views/components/tab-security.php -->
<div class="tab-pane fade" id="security" role="tabpanel">
    
    <!-- 1. Master Security Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h5 class="mb-0"><i class="bi bi-shield-check text-primary me-2"></i> Security & Network</h5>
    </div>

    <!-- 2. The Inner Sub-Navigation (Pills) -->
    <ul class="nav nav-pills mb-4 bg-white p-1 rounded shadow-sm border" id="securitySubTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="pill-ufw-tab" data-bs-toggle="pill" data-bs-target="#sec-ufw" type="button" role="tab">
                <i class="bi bi-bricks me-1"></i> Firewall (UFW)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-danger" id="pill-f2b-tab" data-bs-toggle="pill" data-bs-target="#sec-f2b" type="button" role="tab">
                <i class="bi bi-shield-slash-fill me-1"></i> Active Defense
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="pill-dns-tab" data-bs-toggle="pill" data-bs-target="#sec-dns" type="button" role="tab">
                <i class="bi bi-hdd-network me-1"></i> DNS Manager
            </button>
        </li>
    </ul>

    <!-- 3. The Inner Tab Content -->
    <div class="tab-content" id="securitySubTabsContent">
        
        <!-- PANE 1: FIREWALL (UFW) -->
        <div class="tab-pane fade show active" id="sec-ufw" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold">Active Firewall Rules</h6>
                    <button class="btn btn-sm btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#firewallModal"><i class="bi bi-plus-circle"></i> Open Port</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-sm align-middle">
                        <thead class="table-light">
                            <tr><th>Port</th><th>Protocol</th><th>Status</th><th class="text-end">Action</th></tr>
                        </thead>
                        <tbody id="dynamicFirewallTable">
                            <tr><td colspan="4" class="text-center text-muted py-3">Loading rules...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PANE 2: ACTIVE DEFENSE (Fail2ban) -->
        <div class="tab-pane fade" id="sec-f2b" role="tabpanel">
            <div class="card shadow-sm border-0 border-top border-danger border-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 text-danger fw-bold"><i class="bi bi-shield-slash-fill me-2"></i> Intrusion Blocks (Fail2ban)</h6>
                    <div>
                        <!-- The new modal button -->
                        <button class="btn btn-sm btn-dark me-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#fail2banStatusModal"><i class="bi bi-activity"></i> System Status</button>
                        <!-- The existing refresh button -->
                        <button class="btn btn-sm btn-outline-secondary shadow-sm" onclick="fetchFail2Ban()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
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

        <!-- PANE 3: DNS MANAGER (BIND9) -->
        <div class="tab-pane fade" id="sec-dns" role="tabpanel">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold">Active DNS Records (BIND9)</h6>
                    <div>
                        <!-- Moved the DNS specific buttons here -->
                        <button class="btn btn-sm btn-dark me-1 shadow-sm" id="initDnsZoneBtn"><i class="bi bi-magic"></i> Init Zone</button>
                        <button class="btn btn-sm btn-dark me-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#installSslModal"><i class="bi bi-lock"></i> Install SSL</button>
                        <button class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#dnsRecordModal"><i class="bi bi-globe"></i> Manage DNS</button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover mb-0 text-sm align-middle">
                        <thead class="table-light">
                            <tr><th>Zone</th><th>Name</th><th>Type</th><th>Value</th><th class="text-end">Action</th></tr>
                        </thead>
                        <tbody id="dynamicDnsTable">
                            <tr><td colspan="5" class="text-center text-muted py-3">Loading DNS records...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> <!-- End Inner Tab Content -->
</div>