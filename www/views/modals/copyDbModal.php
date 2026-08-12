<div class="modal fade" id="copyDbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom border-info border-3">
                <h5 class="modal-title"><i class="bi bi-copy text-info me-2"></i> Clone / Duplicate Database</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="copyDbForm">
                    <input type="hidden" name="action" value="copy">
                    <input type="hidden" name="src_db_name" id="copySrcDb">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Source Database</label>
                            <input type="text" class="form-control font-monospace bg-white" id="copySrcDbDisplay" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Target Owner User</label>
                            <select class="form-select user-dropdown" name="username" id="copyOwnerUser" required>
                                <option value="">Loading users...</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Database Suffix</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white" id="copyDbPrefixLabel">user_</span>
                            <input type="text" class="form-control" name="db_suffix" placeholder="staging" required pattern="[a-zA-Z0-9_]+">
                        </div>
                        <div class="form-text small">Final database name format: <code>username_suffix</code></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold d-flex justify-content-between">
                            New Database Password
                            <a href="#" class="text-decoration-none" id="generateCopyDbPass"><i class="bi bi-magic"></i> Generate Secure</a>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" name="db_pass" id="copyDbPassInput" placeholder="Enter or generate password" required>
                            <button class="btn btn-outline-secondary copy-btn" type="button" data-target="copyDbPassInput"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle-fill me-1"></i> Data will stream directly in-memory from the source database to the new target database without temporary disk storage.
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info text-white px-4 fw-bold" id="submitCopyDbBtn"><i class="bi bi-files"></i> Clone Database</button>
            </div>
        </div>
    </div>
</div>