<!-- /opt/panel/www/views/components/tab-overview.php -->
<div class="tab-pane fade show active" id="overview" role="tabpanel">
    
    <div class="accordion shadow-sm" id="overviewAccordion">
        
        <!-- 1. Recent System Tasks (Open by default) -->
        <div class="accordion-item border-0 border-bottom border-secondary border-opacity-25">
            <h2 class="accordion-header" id="headingTasks">
                <button class="accordion-button fw-bold bg-white text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTasks" aria-expanded="true" aria-controls="collapseTasks">
                    <i class="bi bi-activity text-primary me-2"></i> Recent System Tasks
                </button>
            </h2>
            <div id="collapseTasks" class="accordion-collapse collapse show" aria-labelledby="headingTasks">
                <div class="accordion-body p-0">
                    <!-- Action Bar -->
                    <div class="bg-light p-2 border-bottom d-flex justify-content-end">
                        <button class="btn btn-sm btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#logModal"><i class="bi bi-terminal"></i> View Live Logs</button>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-sm">
                            <thead class="table-light">
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
        </div>

        <!-- 2. System Services Manager -->
        <div class="accordion-item border-0 border-bottom border-secondary border-opacity-25">
            <h2 class="accordion-header" id="headingServices">
                <button class="accordion-button collapsed fw-bold bg-white text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseServices" aria-expanded="false" aria-controls="collapseServices">
                    <i class="bi bi-hdd-network text-primary me-2"></i> System Services Manager
                </button>
            </h2>
            <div id="collapseServices" class="accordion-collapse collapse" aria-labelledby="headingServices">
                <div class="accordion-body p-0">
                    <!-- Action Bar -->
                    <div class="bg-light p-2 border-bottom d-flex justify-content-end">
                        <button class="btn btn-sm btn-outline-secondary shadow-sm bg-white" onclick="fetchServices()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                    </div>
                    <!-- Table -->
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

        <!-- 3. Installed Components -->
        <div class="accordion-item border-0">
            <h2 class="accordion-header" id="headingComponents">
                <button class="accordion-button collapsed fw-bold bg-white text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseComponents" aria-expanded="false" aria-controls="collapseComponents">
                    <i class="bi bi-box-seam text-success me-2"></i> Installed Components
                </button>
            </h2>
            <div id="collapseComponents" class="accordion-collapse collapse" aria-labelledby="headingComponents">
                <div class="accordion-body p-0">
                    <!-- Action Bar -->
                    <div class="bg-light p-2 border-bottom d-flex justify-content-end">
                        <button class="btn btn-sm btn-outline-secondary shadow-sm bg-white" onclick="fetchComponents()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                    </div>
                    <!-- Table -->
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
</div>