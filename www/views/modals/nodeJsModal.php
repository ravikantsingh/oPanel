<div class="modal fade" id="nodeJsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-bottom">
                <h5 class="modal-title"><i class="bi bi-hexagon-fill text-success"></i> Deploy Node.js Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-success small mb-4">
                    <i class="bi bi-lightning-charge-fill"></i> <strong>High-Performance Architecture:</strong> Your app will be kept alive 24/7 by PM2. Nginx will automatically be reconfigured as a Reverse Proxy to route external web traffic to your app's Internal Port.
                </div>
                <form id="nodeJsForm">
                    <input type="hidden" name="domain" id="nodeDomain">
                    <input type="hidden" name="username" id="nodeUser">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Application Folder</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted">~/web/domain.com/</span>
                                <input type="text" class="form-control" name="app_root" placeholder="e.g., app" value="app" required>
                            </div>
                            <div class="form-text" style="font-size: 0.75rem;">Create this folder next to public_html</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Startup File</label>
                            <input type="text" class="form-control" name="startup_file" placeholder="e.g., server.js or index.js" value="server.js" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Internal App Port</label>
                            <input type="number" class="form-control" name="app_port" placeholder="e.g., 3000" min="1024" max="65535" required>
                            <div class="form-text" style="font-size: 0.75rem;">Nginx will route 80/443 traffic here.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Environment Mode</label>
                            <select class="form-select" name="app_mode">
                                <option value="production">Production</option>
                                <option value="development">Development</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold d-flex justify-content-between">
                            Custom Environment Variables (.env)
                            <span class="badge bg-secondary">Optional</span>
                        </label>
                        <textarea class="form-control font-monospace" name="env_vars" rows="3" placeholder="API_KEY=your_secret_key&#10;DB_HOST=127.0.0.1"></textarea>
                        <div class="form-text" style="font-size: 0.75rem;">Format: KEY=VALUE (One per line). These are injected securely into PM2.</div>
                    </div>
                    <hr class="my-4">
                    <div id="nodeActionButtons" class="p-3 bg-white border rounded border-secondary border-opacity-25">
                        <h6 class="small fw-bold mb-3 text-muted"><i class="bi bi-cpu"></i> PM2 Process Controls</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success node-action-btn" data-action="restart"><i class="bi bi-arrow-clockwise"></i> Restart App</button>
                            <button type="button" class="btn btn-sm btn-outline-warning node-action-btn" data-action="stop"><i class="bi bi-stop-circle"></i> Stop App</button>
                            <button type="button" class="btn btn-sm btn-outline-primary node-action-btn" data-action="npm_install"><i class="bi bi-box-seam"></i> Run npm install</button>
                        </div>
                        <div class="form-text mt-2" style="font-size: 0.75rem;">Use these controls after uploading a custom package.json or updating your code.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" id="submitNodeJsBtn"><i class="bi bi-rocket-takeoff"></i> Launch App via PM2</button>
            </div>
        </div>
    </div>
</div>