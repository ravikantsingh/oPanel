<div class="tab-pane fade" id="redis" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-dark fw-bold"><i class="bi bi-lightning-charge text-warning me-2"></i> Performance & Cache</h4>
        <span id="redisStatusBadge" class="badge bg-secondary">Checking...</span>
    </div>

    <!-- Telemetry Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted small text-uppercase fw-bold">Active Connections</h6>
                    <h2 class="mb-0 fw-bold text-dark" id="redisClients">--</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted small text-uppercase fw-bold">Cache Hit Rate</h6>
                    <h2 class="mb-0 fw-bold text-success" id="redisHitRate">--</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted small text-uppercase fw-bold">Uptime (Days)</h6>
                    <h2 class="mb-0 fw-bold text-dark" id="redisUptime">--</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- The SRE Memory Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-end mb-2">
                <h6 class="mb-0 fw-bold text-dark">RAM Usage (128MB Hard-Cap)</h6>
                <span class="small fw-bold text-muted" id="redisMemText">-- / 128M</span>
            </div>
            <div class="progress" style="height: 25px;">
                <div id="redisMemBar" class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" style="width: 0%">0%</div>
            </div>
            <p class="small text-muted mt-2 mb-0"><i class="bi bi-info-circle"></i> Redis is configured with an <b>allkeys-lru</b> eviction policy. If usage reaches 100%, the oldest cached items will be automatically dropped to prevent server crashes.</p>
        </div>
    </div>

    <!-- The Control Deck -->
    <div class="card border-0 shadow-sm border-top border-primary border-3">
        <div class="card-body bg-light rounded d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-1">Cache Operations</h6>
                <small class="text-muted">Manually intervene with the Redis daemon.</small>
            </div>
            <div>
                <button class="btn btn-primary fw-bold me-2" onclick="redisAction('flush')"><i class="bi bi-eraser-fill me-1"></i> Flush Cache</button>
                <button class="btn btn-outline-danger fw-bold" onclick="redisAction('restart')"><i class="bi bi-arrow-clockwise me-1"></i> Restart Daemon</button>
            </div>
        </div>
    </div>
</div>