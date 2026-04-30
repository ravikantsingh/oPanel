    <div class="tab-pane fade" id="domains" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Hosted Domains & Repositories</h5>
            <div>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addDomainModal"><i class="bi bi-plus"></i> New Domain</button>
                <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#gitModal"><i class="bi bi-git"></i> Deploy Git Repo</button>
                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#changePhpModal"><i class="bi bi-filetype-php"></i> Change PHP</button>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Domain Name</th>
                            <th>Owner</th>
                            <th>PHP</th>
                            <th>Git Repository</th>
                            <th>SSL Status</th>
                        </tr>
                    </thead>
                    <tbody id="dynamicDomainsTable">
                        <tr><td colspan="5" class="text-center text-muted py-3">Loading domains...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>