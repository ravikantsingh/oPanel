<div class="modal fade" id="uploadBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light"><h5 class="modal-title"><i class="bi bi-cloud-upload text-dark"></i> Upload Archive</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="uploadBackupForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Archive Type</label>
                        <select class="form-select" name="type" required>
                            <option value="Website">Website Archive (.tar.gz)</option>
                            <option value="Database">Database Dump (.sql.gz)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select File</label>
                        <input class="form-control" type="file" name="backup_file" accept=".gz" required>
                        <div class="form-text" style="font-size: 0.75rem;">Files will be securely stored in your server's vault for 1-click restoration.</div>
                    </div>
                    <div id="uploadProgress" class="progress mt-3 d-none" style="height: 10px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-dark w-100" id="submitUploadBtn">Upload to Vault</button></div>
        </div>
    </div>
</div>