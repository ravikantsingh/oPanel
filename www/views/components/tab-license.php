<div class="tab-pane fade" id="license-updates" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-shield-check text-primary me-2"></i> License & Updates</h4>
        <button class="btn btn-outline-primary btn-sm fw-bold" id="btnSyncLicense" onclick="forceLicenseSync()">
            <i class="bi bi-arrow-clockwise"></i> Force Sync
        </button>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-key text-secondary me-2"></i> Commercial License</h6>
                </div>
                <div class="card-body bg-light rounded-bottom">
                    <div class="mb-3">
                        <small class="text-muted text-uppercase fw-bold">Status</small><br>
                        <span id="ui-license-status" class="badge bg-secondary">Loading...</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted text-uppercase fw-bold">Valid Until</small><br>
                        <span id="ui-license-expiry" class="fw-bold text-dark"><span class="spinner-border spinner-border-sm"></span></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted text-uppercase fw-bold">License Key</small><br>
                        <code id="ui-license-key" class="fs-6 text-dark bg-white px-2 py-1 rounded border">--</code>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted text-uppercase fw-bold">Registered Owner</small><br>
                        <span id="ui-license-owner" class="fw-bold text-dark">--</span><br>
                        <span id="ui-license-email" class="small text-muted">--</span>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Authorized IP</small><br>
                        <span id="ui-license-ip" class="fw-bold text-dark">--</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-down text-secondary me-2"></i> Core Updates</h6>
                </div>
                <div class="card-body bg-light rounded-bottom text-center d-flex flex-column justify-content-center py-5">
                    <h1 class="display-4 fw-bold text-success mb-0"><i class="bi bi-check-circle"></i></h1>
                    <h5 class="fw-bold text-dark mt-3">Stackrium is up to date</h5>
                    <p class="text-muted small">You are running the latest stable release.</p>
                    <div class="mt-3">
                        <span class="badge bg-dark bg-opacity-10 text-dark border me-2">Current: v1.0.0</span>
                    </div>
                    </div>
            </div>
        </div>
    </div>
</div>