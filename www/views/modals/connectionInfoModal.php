<div class="modal fade" id="connectionInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <div>
                    <h5 class="modal-title mb-0 fw-bold">Connection Info</h5>
                    <div class="text-muted small" id="infoDomainTitle">loading...</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light pt-0">
                
                <div class="card shadow-sm border-0 mt-3 mb-3">
                    <div class="card-header bg-white border-bottom"><h6 class="mb-0 text-primary"><i class="bi bi-terminal"></i> System & File Access (SSH/SFTP)</h6></div>
                    <ul class="list-group list-group-flush text-sm">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><strong class="text-muted d-block" style="font-size: 0.75rem;">Server IP</strong><span id="infoIp"></span></div>
                            <button class="btn btn-sm btn-light copy-btn" data-target="infoIp"><i class="bi bi-clipboard"></i></button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><strong class="text-muted d-block" style="font-size: 0.75rem;">System Username</strong><span id="infoUser"></span></div>
                            <button class="btn btn-sm btn-light copy-btn" data-target="infoUser"><i class="bi bi-clipboard"></i></button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><strong class="text-muted d-block" style="font-size: 0.75rem;">Password</strong><span>••••••••••••••••</span></div>
                            <span class="badge bg-secondary">Manage in Users Tab</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-dark text-light">
                            <div><strong class="text-gray-400 d-block" style="font-size: 0.75rem;">Quick SSH Command</strong><code class="text-success" id="infoSsh"></code></div>
                            <button class="btn btn-sm btn-outline-light copy-btn" data-target="infoSsh"><i class="bi bi-clipboard"></i></button>
                        </li>
                    </ul>
                </div>

                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white border-bottom"><h6 class="mb-0 text-success"><i class="bi bi-folder2-open"></i> Absolute Application Paths</h6></div>
                    <ul class="list-group list-group-flush text-sm font-monospace" style="font-size: 0.8rem;">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="text-truncate me-2"><strong class="text-muted text-sans-serif d-block" style="font-size: 0.75rem;">Web Root (Document Root)</strong><span id="infoWebRoot"></span></div>
                            <button class="btn btn-sm btn-light copy-btn" data-target="infoWebRoot"><i class="bi bi-clipboard"></i></button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="text-truncate me-2"><strong class="text-muted text-sans-serif d-block" style="font-size: 0.75rem;">Nginx Config Path</strong><span id="infoNginx"></span></div>
                            <button class="btn btn-sm btn-light copy-btn" data-target="infoNginx"><i class="bi bi-clipboard"></i></button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="text-truncate me-2"><strong class="text-muted text-sans-serif d-block" style="font-size: 0.75rem;">PHP-FPM Socket</strong><span id="infoPhpSock"></span></div>
                            <button class="btn btn-sm btn-light copy-btn" data-target="infoPhpSock"><i class="bi bi-clipboard"></i></button>
                        </li>
                    </ul>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom"><h6 class="mb-0 text-warning"><i class="bi bi-database"></i> Database Connection</h6></div>
                    <ul class="list-group list-group-flush text-sm">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><strong class="text-muted d-block" style="font-size: 0.75rem;">Host</strong><span id="infoDbHost"></span></div>
                            <button class="btn btn-sm btn-light copy-btn" data-target="infoDbHost"><i class="bi bi-clipboard"></i></button>
                        </li>
                        <li class="list-group-item text-muted text-center small">
                            Database names and passwords are managed in the <strong>Users & DBs</strong> tab.
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>