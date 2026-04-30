    <div class="tab-pane fade" id="backups" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Backup Vault</h5>
            <div>
                <button class="btn btn-sm btn-outline-dark me-1" data-bs-toggle="modal" data-bs-target="#uploadBackupModal"><i class="bi bi-cloud-upload"></i> Upload</button>
                <button class="btn btn-sm btn-dark me-2" data-bs-toggle="modal" data-bs-target="#scheduleBackupModal"><i class="bi bi-clock-history"></i> Auto-Schedule</button>
                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#backupWebModal"><i class="bi bi-globe"></i> Backup Web</button>
                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#backupDbModal"><i class="bi bi-database"></i> Backup DB</button>
            </div>
        </div>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-clock-history text-info"></i> Active Automation Schedules</h6></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Target Name</th>
                            <th>Type</th>
                            <th>Frequency</th>
                            <th>Run Time (24H)</th>
                            <th>Retention Limit</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="dynamicSchedulesTable">
                        <tr><td colspan="6" class="text-center text-muted py-3">Loading schedules...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Target Name</th>
                            <th>Timestamp</th>
                            <th>Size</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="dynamicBackupsTable">
                        <tr><td colspan="5" class="text-center text-muted py-3">Loading vault...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>