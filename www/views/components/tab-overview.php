<!-- /opt/panel/www/views/components/tab-overview.php -->
<div class="tab-pane fade show active" id="overview" role="tabpanel">
    
    <!-- 1. Recent System Tasks -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent System Tasks</h5>
            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#logModal"><i class="bi bi-terminal"></i> View Live Logs</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th class="text-end">Logs</th> 
                    </tr>
                </thead>
                    <tbody id="dynamicTasksTable">
                        <tr><td colspan="6" class="text-center text-muted py-3">Loading tasks...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. System Services Manager -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-hdd-network text-primary me-2"></i> System Services Manager</h6>
                    <button class="btn btn-sm btn-outline-secondary shadow-sm" onclick="fetchServices()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0 text-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Service</th>
                                <th>State</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dynamicServicesTable">
                            <tr><td colspan="3" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div> Fetching service states...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam text-success me-2"></i> Installed Components</h6>
                    <button class="btn btn-sm btn-outline-secondary shadow-sm" onclick="fetchComponents()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0 text-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Component Name</th>
                                <th>Package / Engine</th>
                                <th class="text-end">Version Installed</th>
                            </tr>
                        </thead>
                        <tbody id="dynamicComponentsTable">
                            <tr><td colspan="3" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div> Fetching component versions...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>