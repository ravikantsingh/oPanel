<div class="modal fade" id="linuxPassModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title"><i class="bi bi-key"></i> System User: <span id="linuxPassTitle"></span></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="linuxPassForm">
                    <input type="hidden" name="username" id="linuxPassUser">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">New System Password</label>
                        <input type="text" class="form-control font-monospace" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="linuxPassBtn">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>