    <div class="tab-pane fade" id="domains" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Hosted Domains & Repositories</h5>
            <div>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addDomainModal"><i class="bi bi-plus"></i> New Domain</button>
                <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#gitModal"><i class="bi bi-git"></i> Deploy Git Repo</button>
                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#changePhpModal"><i class="bi bi-filetype-php"></i> Change PHP</button>
                <button class="btn btn-sm btn-primary ms-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#softwareCenterModal"><i class="bi bi-box-seam"></i> Software Center</button>
            </div>
        </div>
        <!-- The Target Container for panel_12.js -->
        <div class="accordion" id="dynamicDomainsAccordion">
            <!-- Default Loading State -->
            <div class="text-center text-muted py-5 border shadow-sm rounded bg-white">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div>Loading domains...</div>
            </div>
        </div>
    </div>