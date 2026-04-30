<div class="modal fade" id="changeDbPassModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title"><i class="bi bi-key text-secondary"></i> Change DB Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-light">
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i> Changing this password will instantly break any web applications (like WordPress) connected to this database until you update their config files!
        </div>
        <form id="changeDbPassForm">
            <input type="hidden" name="db_user" id="editDbUserHidden">
            
            <div class="mb-3">
                <label class="form-label small fw-bold">Database User</label>
                <input type="text" class="form-control bg-white" id="editDbUserDisplay" disabled>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold d-flex justify-content-between">
                    New Password
                    <a href="#" class="text-decoration-none" id="generateEditDbPass"><i class="bi bi-magic"></i> Generate</a>
                </label>
                <div class="input-group">
                    <input type="text" class="form-control font-monospace" name="new_password" id="editDbPassInput" required>
                    <button class="btn btn-outline-secondary copy-btn" type="button" data-target="editDbPassInput"><i class="bi bi-clipboard"></i></button>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-dark px-4" id="submitEditDbPassBtn"><i class="bi bi-save"></i> Save New Password</button>
      </div>
    </div>
  </div>
</div>