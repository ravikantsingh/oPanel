<div class="modal fade" id="importDbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom border-primary border-3">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i> Import SQL Dump</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="importDbForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import">
                    <input type="hidden" name="db_name" id="importTargetDb">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Target Database</label>
                        <input type="text" class="form-control font-monospace bg-white" id="importDbDisplay" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select SQL File (`.sql` or `.sql.gz`)</label>
                        <input type="file" class="form-control" name="dump_file" id="importFileInput" accept=".sql,.gz" required>
                        <div class="form-text small text-muted">
                            <i class="bi bi-info-circle"></i> Files larger than PHP upload limits should be uploaded via File Manager to <code>/tmp/</code> or imported via CLI.
                        </div>
                    </div>

                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Warning:</strong> Importing a dump will overwrite existing table structures and data if matching tables are present in the dump.
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4 fw-bold" id="submitImportDbBtn"><i class="bi bi-cloud-arrow-up"></i> Start Import</button>
            </div>
        </div>
    </div>
</div>