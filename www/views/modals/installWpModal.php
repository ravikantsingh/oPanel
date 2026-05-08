<div class="modal fade" id="installWpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title"><i class="bi bi-wordpress text-primary"></i> 1-Click WordPress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> This requires an empty <code>public_html</code> directory. The default oPanel index file will be overwritten!
                </div>
                <form id="installWpForm">
                    <input type="hidden" name="domain" id="wpDomain">
                    <input type="hidden" name="username" id="wpUser">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Site Title</label>
                        <input type="text" class="form-control" name="wp_title" placeholder="My Awesome Blog" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Admin Username</label>
                        <input type="text" class="form-control bg-white" name="wp_admin" placeholder="admin" required pattern="[a-zA-Z0-9_]+">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold d-flex justify-content-between">
                            Admin Password
                            <a href="#" class="text-decoration-none" id="generateWpPass"><i class="bi bi-magic"></i> Generate</a>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" name="wp_pass" id="wpPassInput" required>
                            <button class="btn btn-outline-secondary copy-btn" type="button" data-target="wpPassInput"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Admin Email</label>
                        <input type="email" class="form-control bg-white" name="wp_email" id="wpEmailInput" required>
                    </div>
                    <div class="mb-3 form-check form-switch border rounded p-3 bg-white shadow-sm">
                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="wpRedisToggle" name="enable_redis" value="1" checked>
                        <label class="form-check-label fw-bold d-block" for="wpRedisToggle">
                            Enable Redis Object Cache <span class="badge bg-success ms-1">Recommended</span>
                        </label>
                        <small class="text-muted d-block mt-1">Automatically configures a secure, isolated in-memory cache for this site to maximize performance.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="submitInstallWpBtn"><i class="bi bi-cloud-arrow-down"></i> Install WordPress</button>
            </div>
        </div>
    </div>
</div>