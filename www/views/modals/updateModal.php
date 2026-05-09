<div class="modal fade" id="updateModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            
            <div class="modal-header bg-dark text-white border-bottom-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-cloud-arrow-down-fill me-2 text-primary"></i> Stackrium Updates
                </h5>
                <button type=\"button\" class=\"btn-close btn-close-white\" data-bs-dismiss=\"modal\" id=\"closeUpdateModalBtn\"></button>
            </div>
            
            <div class="modal-body bg-light p-0">
                <ul class="nav nav-tabs nav-fill bg-white pt-2 border-bottom-0" id="updateTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold border-0 text-success" id="stable-update-tab" data-bs-toggle="tab" data-bs-target="#tab-stable" type="button" role="tab">
                            <i class="bi bi-shield-check me-1"></i> Stable Channel
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold border-0 text-warning" id="beta-update-tab" data-bs-toggle="tab" data-bs-target="#tab-beta" type="button" role="tab">
                            <i class="bi bi-cone-striped me-1"></i> Beta Channel
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4" id="updateTabContent">
                    
                    <div class="tab-pane fade show active" id="tab-stable" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="fw-bold mb-0 text-dark">Version <span id="ui-stable-version">Loading...</span></h4>
                                <small class="text-muted">Released: <span id="ui-stable-date">--</span></small>
                            </div>
                            <button class="btn btn-success fw-bold px-4 shadow-sm btn-start-update" data-channel="stable">
                                Install Update <i class="bi bi-download ms-2"></i>
                            </button>
                        </div>
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white fw-bold"><i class="bi bi-journal-text me-2"></i>Changelog</div>
                            <div class="card-body bg-light" id="ui-stable-changelog" style="min-height: 120px;">
                                <div class="spinner-border spinner-border-sm text-secondary"></div> Fetching data...
                            </div>
                        </div>

                        <div class="alert alert-secondary border d-flex align-items-center mb-0">
                            <div class="form-check form-switch fs-5 mb-0 me-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="autoUpdateToggle">
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Enable Unattended Auto-Updates</h6>
                                <p class="mb-0 small text-muted">Automatically install Stable releases at 3:00 AM server time. 
                                    <a href="#" class="text-decoration-none" data-bs-toggle="collapse" data-bs-target="#autoUpdateWarning">Read risks...</a>
                                </p>
                            </div>
                        </div>
                        <div class="collapse mt-2" id="autoUpdateWarning">
                            <div class="alert alert-warning border-warning small mb-0">
                                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> <strong>Important:</strong> While stable, auto-updates carry a small risk of service interruption. We strongly advise enabling Automated Daily Backups before turning this feature on. Beta builds will never auto-install.
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-beta" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="fw-bold mb-0 text-dark">Version <span id="ui-beta-version">Loading...</span></h4>
                                <small class="text-muted">Released: <span id="ui-beta-date">--</span></small>
                            </div>
                            <button class="btn btn-warning text-dark fw-bold px-4 shadow-sm btn-start-update" data-channel="beta">
                                Install Beta <i class="bi bi-download ms-2"></i>
                            </button>
                        </div>
                        <div class="card border-0 shadow-sm border-warning mb-0">
                            <div class="card-header bg-warning bg-opacity-10 fw-bold text-dark"><i class="bi bi-bug me-2"></i>Experimental Changelog</div>
                            <div class="card-body bg-light" id="ui-beta-changelog" style="min-height: 120px;">
                                <div class="spinner-border spinner-border-sm text-secondary"></div> Fetching data...
                            </div>
                        </div>
                    </div>

                </div>

                <div id="updateProgressUI" class="p-4 bg-white border-top d-none">
                    <h6 class="fw-bold text-primary mb-2" id="updateStepText">Initializing Engine...</h6>
                    <div class="progress mb-2" style="height: 15px;">
                        <div id="updateProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="text-muted fw-bold">Please do not close this window or refresh the page.</small>
                </div>

            </div>
        </div>
    </div>
</div>