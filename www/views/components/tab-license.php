<div class="tab-pane fade" id="license-updates" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-shield-check text-primary me-2"></i> License & Updates</h4>
        <button class="btn btn-outline-primary btn-sm fw-bold" id="btnSyncLicense" onclick="forceLicenseSync()">
            <i class="bi bi-arrow-clockwise"></i> Force Sync
        </button>
        <button class="btn btn-outline-primary btn-sm fw-bold" id="btnCheckUpdates">
            <i class="bi bi-cloud-arrow-down-fill"></i> Fetch Updates
        </button>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                        <i class="bi bi-shield-check fs-5"></i>
                    </div>
                    <h6 class="mb-0 fw-bold fs-5"><s>Commercial License</s> (Kidding: Free License)</h6>
                </div>
                <div class="card-body p-4 bg-light bg-opacity-50">
                    
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 border rounded bg-white text-center shadow-sm h-100 transition-all">
                                <small class="text-muted text-uppercase fw-bold d-block mb-2" style="letter-spacing: 0.5px;">Status</small>
                                <span id="ui-license-status" class="badge bg-secondary fs-6 py-2 w-100 shadow-sm">Loading...</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded bg-white text-center shadow-sm h-100">
                                <small class="text-muted text-uppercase fw-bold d-block mb-2" style="letter-spacing: 0.5px;">Valid Until</small>
                                <span id="ui-license-expiry" class="fw-bold text-dark fs-5">
                                    <span class="spinner-border spinner-border-sm text-secondary"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 p-3 border rounded bg-white shadow-sm position-relative">
                        <small class="text-muted text-uppercase fw-bold d-block mb-2" style="letter-spacing: 0.5px;">License Key</small>
                        <div class="d-flex align-items-center bg-light p-2 rounded border">
                            <code id="ui-license-key" class="fs-6 text-primary flex-grow-1 user-select-all mb-0" style="font-family: monospace;">--</code>
                            <button class="btn btn-sm btn-outline-secondary border-0 copy-btn ms-2" data-target="ui-license-key" title="Copy Key">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-7">
                            <div class="p-3 border rounded bg-white shadow-sm h-100">
                                <small class="text-muted text-uppercase fw-bold d-block mb-2" style="letter-spacing: 0.5px;">Registered To</small>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle fs-3 text-secondary me-3"></i>
                                    <div class="overflow-hidden">
                                        <div id="ui-license-owner" class="fw-bold text-dark text-truncate">--</div>
                                        <div id="ui-license-email" class="small text-muted text-truncate">--</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="p-3 border rounded bg-white shadow-sm h-100">
                                <small class="text-muted text-uppercase fw-bold d-block mb-2" style="letter-spacing: 0.5px;">Authorized Node</small>
                                <div class="d-flex align-items-center mt-2">
                                    <i class="bi bi-hdd-network text-success fs-4 me-2"></i>
                                    <span id="ui-license-ip" class="fw-bold text-dark fs-6">--</span>
                                </div>
                            </div>
                        </div>
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
                        <?php 
                            if(!defined('PANEL_VERSION')) {
                                @include_once '/opt/panel/www/version.php';
                            }
                            ?>
                            <h6 class="fw-bold">Current Version: <span class="badge bg-primary">v<?php echo defined('PANEL_VERSION') ? PANEL_VERSION : 'Unknown'; ?></span></h6>
                    </div>
                    </div>
            </div>
        </div>
    </div>
</div>