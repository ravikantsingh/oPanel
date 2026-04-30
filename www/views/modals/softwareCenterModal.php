<div class="modal fade" id="softwareCenterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-bottom border-primary border-3">
                <h5 class="modal-title"><i class="bi bi-box-seam me-2"></i> Software Center</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-info small shadow-sm">
                    <i class="bi bi-info-circle"></i> Install or remove PHP engines here. Once installed, the new engine will instantly appear in the <strong>Change PHP</strong> menu for your domains.
                </div>
                
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Package Name</th>
                                    <th>Engine Type</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="dynamicSoftwareTable">
                                <tr><td colspan="4" class="text-center text-muted py-4">Scanning system packages...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>