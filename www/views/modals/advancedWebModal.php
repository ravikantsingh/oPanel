<!-- Advanced Web Settings Modal -->
<div class="modal fade" id="advancedWebModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill text-warning me-2"></i> Advanced Settings: <span id="advWebDomainTitle" class="text-info"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-0">
                <div class="d-flex align-items-start">
                    <!-- Left Sidebar Tabs -->
                    <div class="nav flex-column nav-pills me-3 p-3 bg-white border-end h-100 shadow-sm" style="min-width: 200px; min-height: 400px;" role="tablist">
                        <button class="nav-link active text-start fw-bold mb-2" data-bs-toggle="pill" data-bs-target="#tab-redirects" type="button"><i class="bi bi-sign-turn-right me-2"></i> URL Redirects</button>
                        <button class="nav-link text-start fw-bold mb-2" data-bs-toggle="pill" data-bs-target="#tab-hotlink" type="button"><i class="bi bi-shield-lock me-2"></i> Hotlink Protection</button>
                        <button class="nav-link text-start fw-bold" data-bs-toggle="pill" data-bs-target="#tab-mimes" type="button"><i class="bi bi-file-earmark-code me-2"></i> MIME Types</button>
                    </div>
                    
                    <!-- Tab Content Area -->
                    <div class="tab-content flex-grow-1 p-4">
                        
                        <!-- TAB 1: REDIRECTS -->
                        <div class="tab-pane fade show active" id="tab-redirects" role="tabpanel">
                            <h6 class="fw-bold border-bottom pb-2 mb-3">Add New Redirect</h6>
                            <form id="addRedirectForm" class="row g-2 mb-4">
                                <input type="hidden" name="domain" class="adv-domain-input">
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm font-monospace" name="source" placeholder="Source (e.g. /old-page)" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="url" class="form-control form-control-sm font-monospace" name="target" placeholder="Target (e.g. https://...)" required>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm" name="type">
                                        <option value="301">301 (Permanent)</option>
                                        <option value="302">302 (Temporary)</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-plus-lg"></i> Add</button>
                                </div>
                            </form>
                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-muted small">Active Redirects</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover border">
                                    <thead class="table-light"><tr><th>Source Path</th><th>Target URL</th><th>Type</th><th class="text-end">Action</th></tr></thead>
                                    <tbody id="dynamicRedirectsTable">
                                        <!-- Fetched via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 2: HOTLINK PROTECTION -->
                        <div class="tab-pane fade" id="tab-hotlink" role="tabpanel">
                            <h6 class="fw-bold border-bottom pb-2 mb-3">Bandwidth Protection</h6>
                            <div class="alert alert-info small border-0 shadow-sm">
                                <i class="bi bi-info-circle-fill me-1"></i> Enabling Hotlink Protection prevents other websites from embedding your images, videos, and audio files, which saves your server bandwidth.
                            </div>
                            <div class="card border-0 shadow-sm mt-4">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark">Hotlink Protection Engine</h6>
                                        <small class="text-muted" id="hotlinkStatusText">Currently disabled.</small>
                                    </div>
                                    <div class="form-check form-switch fs-4 mb-0">
                                        <input class="form-check-input" type="checkbox" id="hotlinkToggle">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: MIME TYPES -->
                        <div class="tab-pane fade" id="tab-mimes" role="tabpanel">
                            <h6 class="fw-bold border-bottom pb-2 mb-3">Add Custom MIME Type</h6>
                            <form id="addMimeForm" class="row g-2 mb-4">
                                <input type="hidden" name="domain" class="adv-domain-input">
                                <div class="col-md-3">
                                    <input type="text" class="form-control form-control-sm font-monospace" name="extension" placeholder="Ext (e.g. apk)" required>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control form-control-sm font-monospace" name="mime_type" placeholder="MIME (e.g. application/vnd.android...)" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-plus-lg"></i> Add</button>
                                </div>
                            </form>
                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-muted small">Custom Types</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover border">
                                    <thead class="table-light"><tr><th>Extension</th><th>MIME Type</th><th class="text-end">Action</th></tr></thead>
                                    <tbody id="dynamicMimesTable">
                                        <!-- Fetched via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>