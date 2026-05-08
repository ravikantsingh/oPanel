<!-- Custom App Integration Modal -->
<div class="modal fade" id="devRedisModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-code-slash text-warning me-2"></i> Custom App Integration Guide</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-info border-0 shadow-sm small">
                    <i class="bi bi-info-circle-fill me-1"></i> <strong>Developer Note:</strong> oPanel provides an isolated, high-speed RAM cache. Use these credentials to connect your raw PHP, Laravel, or Node.js applications.
                </div>

                <!-- Credentials Section -->
                <h6 class="fw-bold mb-3 mt-4 text-dark border-bottom pb-2">1. Secure Connection Keys</h6>
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">Redis Host</label>
                        <div class="input-group input-group-sm shadow-sm">
                            <input type="text" class="form-control bg-white font-monospace" value="127.0.0.1" readonly id="devRedisHost">
                            <button class="btn btn-outline-secondary copy-btn" data-target="devRedisHost"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">Redis Port</label>
                        <div class="input-group input-group-sm shadow-sm">
                            <input type="text" class="form-control bg-white font-monospace" value="6379" readonly id="devRedisPort">
                            <button class="btn btn-outline-secondary copy-btn" data-target="devRedisPort"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label small fw-bold text-muted mb-1">Master Password (SRE Vault)</label>
                        <div class="input-group input-group-sm shadow-sm">
                            <input type="text" class="form-control bg-white font-monospace" id="devRedisPass" readonly>
                            <button class="btn btn-outline-secondary copy-btn" data-target="devRedisPass"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Boilerplate Section -->
                <h6 class="fw-bold mb-3 text-dark border-bottom pb-2">2. PHP Boilerplate Example</h6>
                <p class="small text-muted mb-2">You must define a <strong>unique prefix</strong> (e.g., <code>myapp_</code>) to prevent data collisions with other domains on this server.</p>
                <div class="position-relative shadow-sm">
                    <textarea class="form-control bg-dark text-success font-monospace" id="devPhpBoilerplate" rows="12" style="font-size: 0.85rem; resize: none;" readonly>
&lt;?php
// 1. Connect to oPanel Redis
$redis = new Redis();
$redis-&gt;connect('127.0.0.1', 6379);
$redis-&gt;auth('PASSWORD_WILL_LOAD_HERE');

// 2. Set Domain Prefix (CRITICAL for Shared Servers)
$redis-&gt;setOption(Redis::OPT_PREFIX, 'my_domain_com_');

// 3. Cache Logic
$cacheKey = 'latest_articles';
$data = $redis-&gt;get($cacheKey);

if (!$data) {
    // Cache Miss: Query MariaDB
    $data = ['article 1', 'article 2']; // fetch_from_db();
    
    // Save to RAM for 1 Hour
    $redis-&gt;setex($cacheKey, 3600, json_encode($data));
} else {
    // Cache Hit!
    $data = json_decode($data, true);
}
print_r($data);
?&gt;</textarea>
                    <button class="btn btn-sm btn-outline-light copy-btn position-absolute top-0 end-0 m-2" data-target="devPhpBoilerplate"><i class="bi bi-clipboard"></i> Copy</button>
                </div>
            </div>
        </div>
    </div>
</div>