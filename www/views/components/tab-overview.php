    <div class="tab-pane fade show active" id="overview" role="tabpanel">
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
                            <tr><td colspan="5" class="text-center text-muted py-3">Loading tasks...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>