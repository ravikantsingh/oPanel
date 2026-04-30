    <div class="tab-pane fade" id="users" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">System Users & MySQL Databases</h5>
            <div>
                <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#pmaSettingsModal"><i class="bi bi-gear"></i> Global Settings</button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus"></i> New Linux User</button>
                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#addDbModal"><i class="bi bi-database-add"></i> New Database</button>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-people text-primary"></i> Registered Users</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-sm align-middle">
                            <thead class="table-light">
                                <tr><th>Username</th><th>Email</th><th>Features</th><th>Action</th></tr>
                            </thead>
                            <tbody id="dynamicUsersTable">
                                <tr><td colspan="3" class="text-center text-muted py-3">Loading users...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-database text-warning"></i> Provisioned Databases</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-sm align-middle">
                            <thead class="table-light">
                                <tr><th>Database Name</th><th>DB User</th><th>Owner</th><th class="text-end">Action</th></tr>
                            </thead>
                            <tbody id="dynamicDbTable">
                                <tr><td colspan="3" class="text-center text-muted py-3">Loading databases...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>