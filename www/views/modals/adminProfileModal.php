<div class="modal fade" id="adminProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-bottom">
                <h5 class="modal-title"><i class="bi bi-person-gear"></i> Administrator Security</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="adminProfileForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Password</label>
                        <input type="password" class="form-control" name="new_password" id="newAdminPass" required minlength="8">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirmAdminPass" required>
                    </div>
                    <div id="adminProfileAlert" class="alert d-none py-2 text-sm"></div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="submitAdminProfileBtn"><i class="bi bi-save"></i> Update Password</button>
            </div>
        </div>
    </div>
</div>