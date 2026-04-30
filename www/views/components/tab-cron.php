    <div class="tab-pane fade" id="cron" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Automated Tasks (Cron Jobs)</h5>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCronModal"><i class="bi bi-clock-history"></i> Add Cron Job</button>
        </div>
        
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle text-sm">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Schedule (M H D M W)</th>
                            <th>Command Executed</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="dynamicCronTable">
                        <tr><td colspan="4" class="text-center text-muted py-3">Loading cron jobs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>