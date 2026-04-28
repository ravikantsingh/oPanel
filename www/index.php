<?php
// /opt/panel/www/index.php
// ---> NEW: FORCE SECURE COOKIES <---
session_name('PANEL_SESSION');
session_set_cookie_params([
    'secure' => true,      // Only transmit over HTTPS
    'httponly' => true,    // Block Javascript from reading the cookie
    'samesite' => 'Strict' // Prevent Cross-Site Request Forgery
]);
// -----------------------------------
session_start();

// 1. Existing Login Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: /login.php");
    exit;
}

// 2. CSRF Token Generation (NEW)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$load = sys_getloadavg();
$cpu_load = $load[0];

include 'views/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#systemSettingsModal">
        <i class="bi bi-sliders"></i> System Settings
    </button>
</div>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body py-3">
                <div class="row text-center">
                    <div class="col-md-4 border-end">
                        <div class="text-muted small fw-bold mb-1">CPU LOAD (1m)</div>
                        <div class="d-flex align-items-center justify-content-center">
                            <h4 class="mb-0 me-2" id="cpuText">--</h4>
                            <div class="progress flex-grow-1" style="height: 6px; max-width: 100px;">
                                <div id="cpuBar" class="progress-bar bg-primary" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 border-end">
                        <div class="text-muted small fw-bold mb-1">RAM USAGE</div>
                        <div class="d-flex align-items-center justify-content-center">
                            <h4 class="mb-0 me-2 fs-6" id="ramText">-- / -- MB</h4>
                            <div class="progress flex-grow-1" style="height: 6px; max-width: 100px;">
                                <div id="ramBar" class="progress-bar bg-info" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small fw-bold mb-1">DISK SPACE (ROOT)</div>
                        <div class="d-flex align-items-center justify-content-center">
                            <h4 class="mb-0 me-2 fs-6" id="diskText">-- / -- GB</h4>
                            <div class="progress flex-grow-1" style="height: 6px; max-width: 100px;">
                                <div id="diskBar" class="progress-bar bg-warning" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<ul class="nav nav-tabs mb-4" id="panelTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-bold" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button"><i class="bi bi-speedometer2"></i> Overview</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold" id="domains-tab" data-bs-toggle="tab" data-bs-target="#domains" type="button"><i class="bi bi-globe"></i> Web & Git</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button"><i class="bi bi-shield-check"></i> Security & DNS</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button"><i class="bi bi-people"></i> Users & DBs</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold" id="cron-tab" data-bs-toggle="tab" data-bs-target="#cron" type="button"><i class="bi bi-clock-history"></i> Cron Jobs</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold" id="backups-tab" data-bs-toggle="tab" data-bs-target="#backups" type="button"><i class="bi bi-archive"></i> Backups</button>
  </li>
</ul>

<div class="tab-content" id="panelTabsContent">
    
    <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent System Tasks</h5>
                <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#logModal"><i class="bi bi-terminal"></i> View Live Logs</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Action</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th class="text-end">Logs</th> 
                        </tr>
                    </thead>
                        <tbody id="dynamicTasksTable">
                            <tr><td colspan="5" class="text-center text-muted py-3">Loading tasks...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="domains" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Hosted Domains & Repositories</h5>
            <div>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addDomainModal"><i class="bi bi-plus"></i> New Domain</button>
                <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#gitModal"><i class="bi bi-git"></i> Deploy Git Repo</button>
                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#changePhpModal"><i class="bi bi-filetype-php"></i> Change PHP</button>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Domain Name</th>
                            <th>Owner</th>
                            <th>PHP</th>
                            <th>Git Repository</th>
                            <th>SSL Status</th>
                        </tr>
                    </thead>
                    <tbody id="dynamicDomainsTable">
                        <tr><td colspan="5" class="text-center text-muted py-3">Loading domains...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="security" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Firewall Rules & DNS</h5>
            <div>
                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#firewallModal"><i class="bi bi-shield-lock"></i> Open Port</button>
                <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#installSslModal"><i class="bi bi-lock"></i> Install SSL</button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#dnsRecordModal"><i class="bi bi-globe"></i> Manage DNS</button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white"><h6 class="mb-0">Active Firewall Rules (UFW)</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-sm align-middle">
                            <thead class="table-light">
                                <tr><th>Port</th><th>Protocol</th><th>Status</th><th class="text-end">Action</th></tr>
                            </thead>
                            <tbody id="dynamicFirewallTable">
                                <tr><td colspan="3" class="text-center text-muted py-3">Loading rules...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white"><h6 class="mb-0">Active DNS Records (BIND9)</h6></div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0 text-sm align-middle">
                            <thead class="table-light">
                                <tr><th>Zone</th><th>Name</th><th>Type</th><th>Value</th><th class="text-end">Action</th></tr>
                            </thead>
                            <tbody id="dynamicDnsTable">
                                <tr><td colspan="4" class="text-center text-muted py-3">Loading DNS records...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="users" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">System Users & MySQL Databases</h5>
            <div>
                <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#pmaSettingsModal"><i class="bi bi-gear"></i> Global Settings</button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus"></i> New Linux User</button>
                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#addDbModal"><i class="bi bi-database-add"></i> New Database</button>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-people text-primary"></i> Registered Users</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-sm align-middle">
                            <thead class="table-light">
                                <tr><th>Username</th><th>Email</th><th>Features</th></tr>
                            </thead>
                            <tbody id="dynamicUsersTable">
                                <tr><td colspan="3" class="text-center text-muted py-3">Loading users...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-database text-warning"></i> Provisioned Databases</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-sm align-middle">
                            <thead class="table-light">
                                <tr><th>Database Name</th><th>DB User</th><th>Owner</th><th class="text-end">Action</th></tr>
                            </thead>
                            <tbody id="dynamicDbTable">
                                <tr><td colspan="3" class="text-center text-muted py-3">Loading databases...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="cron" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Automated Tasks (Cron Jobs)</h5>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCronModal"><i class="bi bi-clock-history"></i> Add Cron Job</button>
        </div>
        
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle text-sm">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Schedule (M H D M W)</th>
                            <th>Command Executed</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="dynamicCronTable">
                        <tr><td colspan="4" class="text-center text-muted py-3">Loading cron jobs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="backups" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Backup Vault</h5>
            <div>
                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#backupWebModal"><i class="bi bi-globe"></i> Backup Website</button>
                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#backupDbModal"><i class="bi bi-database"></i> Backup Database</button>
            </div>
        </div>
        
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Target Name</th>
                            <th>Timestamp</th>
                            <th>Size</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="dynamicBackupsTable">
                        <tr><td colspan="5" class="text-center text-muted py-3">Loading vault...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="backupWebModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title"><i class="bi bi-globe text-info"></i> Backup Website</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="backupWebForm">
                        <input type="hidden" name="action" value="backup_web">
                        <label class="form-label small fw-bold">Select Domain to Archive</label>
                        <select class="form-select domain-dropdown" name="target" required><option value="">Loading...</option></select>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-info text-white w-100" id="submitBackupWebBtn">Generate Web Archive</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="backupDbModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title"><i class="bi bi-database text-warning"></i> Backup Database</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="backupDbForm">
                        <input type="hidden" name="action" value="backup_db">
                        <label class="form-label small fw-bold">Select Database to Dump</label>
                        
                        <!---THE FIX: Changed from Text Input to Dropdown  -->
                        <select class="form-select db-dropdown" name="target" required>
                            <option value="">Loading databases...</option>
                        </select>
                        
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-warning w-100" id="submitBackupDbBtn">Generate SQL Dump</button></div>
            </div>
        </div>
    </div>
    
</div>
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">Create New Linux User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addUserForm">
            <div class="mb-3">
                <label for="username" class="form-label">Username (lowercase, no spaces)</label>
                <input type="text" class="form-control" id="username" name="username" required pattern="[a-z0-9]+">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div id="formAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-primary w-100" id="submitUserBtn">Create User</button>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="addDomainModal" tabindex="-1" aria-labelledby="addDomainModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addDomainModalLabel">Provision New Domain</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addDomainForm">
            <div class="mb-3">
                <label for="domain" class="form-label">Domain Name (e.g., example.com)</label>
                <input type="text" class="form-control" id="domain" name="domain" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Assign to User (Owner)</label>
                <select class="form-select user-dropdown" id="domainUser" name="username" required>
                    <option value="">Loading users...</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="phpVersion" class="form-label">PHP Version</label>
                <select class="form-select" id="phpVersion" name="php_version">
                    <option value="8.3" selected>PHP 8.3 (Default)</option>
                </select>
            </div>
            <div id="domainFormAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-success w-100" id="submitDomainBtn">Create Domain & Nginx vHost</button>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="addDbModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title"><i class="bi bi-database-add text-warning"></i> Provision MySQL Database</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-light">
        <form id="addDbForm">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Assign to User (Owner)</label>
                    <select class="form-select user-dropdown" id="dbOwner" name="username" required>
                        <option value="">Loading users...</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Database & User Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white" id="dbPrefixLabel">prefix_</span>
                        <input type="text" class="form-control" name="db_suffix" required pattern="[a-zA-Z0-9_]+">
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold d-flex justify-content-between">
                    Database Password
                    <a href="#" class="text-decoration-none" id="generateDbPass"><i class="bi bi-magic"></i> Generate Secure</a>
                </label>
                <div class="input-group">
                    <input type="text" class="form-control font-monospace" name="db_pass" id="dbPassInput" placeholder="Enter or generate password" required>
                    <button class="btn btn-outline-secondary copy-btn" type="button" data-target="dbPassInput"><i class="bi bi-clipboard"></i></button>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-primary"><i class="bi bi-shield-lock"></i> Access Control</label>
                    <select class="form-select mb-2" id="dbAcl" name="db_acl">
                        <option value="localhost" selected>Localhost Only (Most Secure)</option>
                        <option value="anywhere">Any Remote Host (%)</option>
                        <option value="custom">Specific Remote IP...</option>
                    </select>
                    <input type="text" class="form-control d-none" id="dbCustomIp" name="db_custom_ip" placeholder="e.g., 192.168.1.50">
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-success"><i class="bi bi-key"></i> User Privileges</label>
                    <select class="form-select mb-2" id="dbRole" name="db_role">
                        <option value="ALL PRIVILEGES" selected>Full Access (Read, Write, Structure)</option>
                        <option value="SELECT, SHOW VIEW">Read-Only (Data Analysis)</option>
                        <option value="custom">Custom Privileges...</option>
                    </select>
                </div>
            </div>

            <div class="card border-secondary mt-3 d-none" id="customPrivilegesGrid">
                <div class="card-header bg-dark text-white small fw-bold py-2">Select Granular Privileges</div>
                <div class="card-body bg-white p-3">
                    <div class="row text-sm">
                        <div class="col-6">
                            <h6 class="text-muted small border-bottom pb-1">Data Access</h6>
                            <div class="form-check"><input class="form-check-input db-priv-chk" type="checkbox" value="SELECT" checked> <label class="form-check-label">SELECT</label></div>
                            <div class="form-check"><input class="form-check-input db-priv-chk" type="checkbox" value="INSERT" checked> <label class="form-check-label">INSERT</label></div>
                            <div class="form-check"><input class="form-check-input db-priv-chk" type="checkbox" value="UPDATE" checked> <label class="form-check-label">UPDATE</label></div>
                            <div class="form-check"><input class="form-check-input db-priv-chk" type="checkbox" value="DELETE" checked> <label class="form-check-label">DELETE</label></div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted small border-bottom pb-1">Structure Access</h6>
                            <div class="form-check"><input class="form-check-input db-priv-chk" type="checkbox" value="CREATE" checked> <label class="form-check-label">CREATE</label></div>
                            <div class="form-check"><input class="form-check-input db-priv-chk" type="checkbox" value="DROP"> <label class="form-check-label">DROP</label></div>
                            <div class="form-check"><input class="form-check-input db-priv-chk" type="checkbox" value="ALTER"> <label class="form-check-label">ALTER</label></div>
                            <div class="form-check"><input class="form-check-input db-priv-chk" type="checkbox" value="INDEX"> <label class="form-check-label">INDEX</label></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="custom_priv_string" id="customPrivString" value="">

            <div id="dbFormAlert" class="alert d-none mt-3"></div>
        </form>
      </div>
      <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-warning px-4" id="submitDbBtn"><i class="bi bi-database-check"></i> Provision Database</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="installSslModal" tabindex="-1" aria-labelledby="installSslModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="installSslModalLabel">Install Free Let's Encrypt SSL</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="installSslForm">
        <div class="mb-3">
                <label class="form-label">Domain Name</label>
                <select class="form-select domain-dropdown" id="sslDomain" name="domain" required>
                    <option value="">Loading domains...</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="sslEmail" class="form-label">Admin Email (For Expiry Notices)</label>
                <input type="email" class="form-control" id="sslEmail" name="email" required>
            </div>
            <div class="alert alert-info small">
                <i class="bi bi-info-circle"></i> The domain must already point to this server's IP address, or the installation will fail.
            </div>
            <div id="sslFormAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-dark w-100" id="submitSslBtn">Secure Domain (HTTPS)</button>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="changePhpModal" tabindex="-1" aria-labelledby="changePhpModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changePhpModalLabel">Change PHP Version</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="changePhpForm">
            <div class="mb-3">
                <label class="form-label">Domain Name</label>
                <select class="form-select domain-dropdown" id="phpDomain" name="domain" required>
                    <option value="">Loading domains...</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="newPhpVersion" class="form-label">Select PHP Version</label>
                <select class="form-select" id="newPhpVersion" name="php_version">
                    <option value="8.1">PHP 8.1</option>
                    <option value="8.2">PHP 8.2</option>
                    <option value="8.3" selected>PHP 8.3 (Default)</option>
                </select>
            </div>
            <div id="phpFormAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-info w-100" id="submitPhpBtn">Update PHP Version</button>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="firewallModal" tabindex="-1" aria-labelledby="firewallModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="firewallModalLabel">Open Firewall Port</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="firewallForm">
            <div class="mb-3">
                <label for="fwPort" class="form-label">Port Number (1 - 65535)</label>
                <input type="number" class="form-control" id="fwPort" name="port" min="1" max="65535" required>
            </div>
            <div class="mb-3">
                <label for="fwProtocol" class="form-label">Protocol</label>
                <select class="form-select" id="fwProtocol" name="protocol">
                    <option value="tcp" selected>TCP</option>
                    <option value="udp">UDP</option>
                </select>
            </div>
            <div id="fwFormAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-danger w-100" id="submitFwBtn">Allow Port</button>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-white border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="logModalLabel"><i class="bi bi-terminal"></i> Live Server Logs</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        
        <div class="p-3 border-bottom border-secondary bg-dark">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <select class="form-select form-select-sm bg-black text-white border-secondary domain-dropdown" id="logDomain">
                        <option value="">Target Domain...</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <select class="form-select form-select-sm bg-black text-white border-secondary user-dropdown" id="logUser">
                        <option value="">Username...</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <select class="form-select form-select-sm bg-black text-white border-secondary w-auto" id="logType">
                    <option value="error">Nginx error.log</option>
                    <option value="access">Nginx access.log</option>
                </select>
                <span class="badge bg-success shadow-sm" id="liveIndicator"><span class="spinner-grow spinner-grow-sm" style="width: 0.5rem; height: 0.5rem;"></span> LIVE</span>
            </div>
        </div>
        
        <div class="p-3 bg-black text-success" id="logTerminal" style="height: 400px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; white-space: pre-wrap;">
Select a Domain and Username above, then wait for logs to load...
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="gitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-github"></i> Clone Git Repository</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="gitForm">
            <div class="mb-3 p-3 bg-light rounded border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-key"></i> SSH Deploy Key</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="fetchSshBtn">View Key</button>
                </div>
                <textarea class="form-control font-monospace small d-none" id="sshKeyDisplay" rows="3" readonly placeholder="Your public SSH key will appear here..."></textarea>
                <div id="sshKeyMessage" class="small text-muted mt-1">Copy this key to your GitHub/GitLab repository settings.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">System Username</label>
                <select class="form-select user-dropdown" name="username" id="sshUsername" required>
                    <option value="">Loading users...</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Target Domain</label>
                <select class="form-select domain-dropdown" name="domain" required>
                    <option value="">Loading domains...</option>
                </select>
                <div class="form-text text-danger">Note: The domain's public_html folder must be empty!</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Repository URL (Public HTTPS)</label>
                <input type="text" class="form-control" name="repo_url" placeholder="git@github.com:user/repo.git" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Branch Name</label>
                <input type="text" class="form-control" name="branch" value="main" placeholder="main, master, staging..." required>
            </div>
            <div id="gitAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-dark w-100" id="submitGitBtn">Deploy Repository</button>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="dnsRecordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-globe"></i> Add/Delete DNS Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="dnsRecordForm">
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label">Action</label>
                    <select class="form-select" name="action">
                        <option value="add">Add Record</option>
                        <option value="delete">Delete Record</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Record Type</label>
                    <select class="form-select" name="type">
                        <option value="A">A (IP Address)</option>
                        <option value="CNAME">CNAME (Alias)</option>
                        <option value="TXT">TXT (Text)</option>
                        <option value="MX">MX (Mail Exchange)</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Target Domain (Zone)</label>
                <select class="form-select domain-dropdown" name="domain" required>
                    <option value="">Loading domains...</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Record Name</label>
                <input type="text" class="form-control" name="name" placeholder="e.g., sub or _dmarc" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Record Value</label>
                <input type="text" class="form-control" name="value" placeholder="e.g., 192.168.1.10" required>
            </div>
            <div id="dnsRecordAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-primary w-100" id="submitDnsRecordBtn">Execute Change</button>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wafRulesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Custom WAF Exceptions: <span id="wafDomainTitle" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> Invalid syntax here will be rejected by the server to prevent crashing Nginx. 
                        Use <code>SecRuleRemoveById 123456</code> to disable false positives.
                    </div>
                    <form id="wafRulesForm">
                        <input type="hidden" id="wafDomainInput" name="domain">
                        <textarea class="form-control font-monospace bg-dark text-light" id="wafRulesTextarea" name="custom_rules" rows="10" placeholder="# Enter ModSecurity directives here..."></textarea>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveWafRulesBtn">Compile & Apply Rules</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addCronModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Scheduled Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCronForm">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Linux User</label>
                            <select class="form-select user-dropdown" name="username" required>
                                <option value="">Loading users...</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col"><label class="form-label small text-muted">Minute</label><input type="text" class="form-control text-center" name="minute" value="*" required></div>
                            <div class="col"><label class="form-label small text-muted">Hour</label><input type="text" class="form-control text-center" name="hour" value="*" required></div>
                            <div class="col"><label class="form-label small text-muted">Day</label><input type="text" class="form-control text-center" name="day" value="*" required></div>
                            <div class="col"><label class="form-label small text-muted">Month</label><input type="text" class="form-control text-center" name="month" value="*" required></div>
                            <div class="col"><label class="form-label small text-muted">Weekday</label><input type="text" class="form-control text-center" name="weekday" value="*" required></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Command to Execute</label>
                            <input type="text" class="form-control font-monospace text-sm" name="command" placeholder="php /home/user1/web/domain.com/public_html/artisan schedule:run" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCronBtn">Save Cron Job</button>
                </div>
            </div>
        </div>
    </div>
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
<div class="modal fade" id="phpSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="bi bi-filetype-php text-info"></i> PHP Configuration: <span id="phpDomainTitle" class="fw-bold text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <form id="phpSettingsForm">
                    <input type="hidden" name="domain" id="psDomain">
                    <input type="hidden" name="username" id="psUser">
                    <input type="hidden" name="php_version" id="psVer">
                    
                    <ul class="nav nav-tabs px-3 pt-3 bg-white" role="tablist">
                        <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#tab-perf" type="button">Performance & Core</button></li>
                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-common" type="button">Common Settings</button></li>
                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-fpm" type="button">FPM Engine (Workers)</button></li>
                    </ul>

                    <div class="tab-content p-4">
                        
                        <div class="tab-pane fade show active" id="tab-perf">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label small fw-bold">memory_limit</label><input type="text" class="form-control" name="php_memory_limit" id="ps_mem"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">max_execution_time</label><input type="number" class="form-control" name="php_max_exec_time" id="ps_max_exec"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">max_input_time</label><input type="number" class="form-control" name="php_max_input_time" id="ps_max_in"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">post_max_size</label><input type="text" class="form-control" name="php_post_max_size" id="ps_post"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">upload_max_filesize</label><input type="text" class="form-control" name="php_upload_max_filesize" id="ps_up"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">opcache.enable</label><select class="form-select" name="php_opcache_enable" id="ps_opc"><option value="on">on</option><option value="off">off</option></select></div>
                                <div class="col-12"><label class="form-label small fw-bold">disable_functions</label><input type="text" class="form-control" name="php_disable_functions" id="ps_dis"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-common">
                            <div class="row g-3">
                                <div class="col-12"><label class="form-label small fw-bold">include_path</label><input type="text" class="form-control" name="php_include_path" id="ps_inc"></div>
                                <div class="col-12"><label class="form-label small fw-bold">session.save_path</label><input type="text" class="form-control" name="php_session_save_path" id="ps_sess"></div>
                                <div class="col-12"><label class="form-label small fw-bold">mail.force_extra_parameters</label><input type="text" class="form-control" name="php_mail_params" id="ps_mail"></div>
                                <div class="col-12"><label class="form-label small fw-bold">open_basedir</label><input type="text" class="form-control" name="php_open_basedir" id="ps_open"></div>
                                <div class="col-12"><label class="form-label small fw-bold">error_reporting</label><input type="text" class="form-control" name="php_error_reporting" id="ps_err_rep"></div>
                                
                                <div class="col-md-4"><label class="form-label small fw-bold">display_errors</label><select class="form-select" name="php_display_errors" id="ps_disp_err"><option value="on">on</option><option value="off">off</option></select></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">log_errors</label><select class="form-select" name="php_log_errors" id="ps_log_err"><option value="on">on</option><option value="off">off</option></select></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">allow_url_fopen</label><select class="form-select" name="php_allow_url_fopen" id="ps_fopen"><option value="on">on</option><option value="off">off</option></select></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">file_uploads</label><select class="form-select" name="php_file_uploads" id="ps_f_up"><option value="on">on</option><option value="off">off</option></select></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">short_open_tag</label><select class="form-select" name="php_short_open_tag" id="ps_short"><option value="on">on</option><option value="off">off</option></select></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-fpm">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label small fw-bold">pm (Manager Type)</label><select class="form-select" name="fpm_pm" id="ps_pm"><option value="dynamic">dynamic</option><option value="ondemand">ondemand</option><option value="static">static</option></select></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">pm.max_children</label><input type="number" class="form-control" name="fpm_max_children" id="ps_fpm_child"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">pm.max_requests</label><input type="number" class="form-control" name="fpm_max_requests" id="ps_fpm_req"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">pm.start_servers</label><input type="number" class="form-control" name="fpm_start_servers" id="ps_fpm_start"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">pm.min_spare_servers</label><input type="number" class="form-control" name="fpm_min_spare_servers" id="ps_fpm_min"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">pm.max_spare_servers</label><input type="number" class="form-control" name="fpm_max_spare_servers" id="ps_fpm_max"></div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="savePhpSettingsBtn"><i class="bi bi-save"></i> Save & Restart FPM</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="fileManagerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="bi bi-folder text-warning"></i> Deploy File Manager</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i> This will securely deploy Tiny File Manager to <strong><span id="fmDomainTitle"></span>/filemanager</strong>.
                </div>
                <form id="fileManagerForm">
                    <input type="hidden" name="domain" id="fmDomain">
                    <input type="hidden" name="username" id="fmUser">
                    <input type="hidden" name="php_version" id="fmVer">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Login Username</label>
                        <input type="text" class="form-control bg-light" id="fmUserDisplay" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Set Access Password</label>
                        <input type="password" class="form-control" name="fm_password" placeholder="Enter a secure password..." required>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="saveFmBtn"><i class="bi bi-cloud-arrow-up"></i> Deploy TFM</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="rotateFmPassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title"><i class="bi bi-key text-dark"></i> Rotate FM Password: <span id="rotateFmDomainTitle" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="rotateFmPassForm">
                    <input type="hidden" name="domain" id="rotateFmDomain">
                    <input type="hidden" name="username" id="rotateFmUser">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted d-flex justify-content-between">
                            New Password
                            <a href="#" class="text-decoration-none" id="generateRotateFmPass"><i class="bi bi-magic"></i> Generate</a>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" name="new_fm_password" id="rotateFmPassInput" required>
                            <button class="btn btn-outline-secondary copy-btn" type="button" data-target="rotateFmPassInput"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark w-50" id="submitRotateFmBtn"><i class="bi bi-save"></i> Update Key</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="ftpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title"><i class="bi bi-hdd-network text-primary"></i> Manage FTP: <span id="ftpDomainTitle" class="fw-bold"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="ftpForm">
                    <input type="hidden" name="action" id="ftpAction" value="create">
                    <input type="hidden" name="domain" id="ftpDomain">
                    <input type="hidden" name="username" id="ftpSysUser">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">FTP Username</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="ftp_user" id="ftpUserInput" placeholder="e.g., dev_user" required>
                            <span class="input-group-text bg-white text-muted" id="ftpSuffix">@domain.com</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted d-flex justify-content-between">
                            FTP Password
                            <a href="#" class="text-decoration-none" id="generateFtpPass"><i class="bi bi-magic"></i> Generate Random</a>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" name="ftp_pass" id="ftpPassInput" placeholder="Enter or generate password" required>
                            <button class="btn btn-outline-secondary copy-btn" type="button" data-target="ftpPassInput"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-danger d-none" id="deleteFtpBtn"><i class="bi bi-trash"></i> Delete User</button>
                        <button type="button" class="btn btn-primary w-100 ms-2" id="saveFtpBtn"><i class="bi bi-save"></i> Save FTP Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="taskLogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h6 class="modal-title font-monospace"><i class="bi bi-terminal text-success"></i> Task Execution Log: <span id="logTaskAction" class="text-warning"></span></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="bg-black p-3 font-monospace" style="height: 400px; overflow-y: auto; font-size: 0.85rem;">
                    <pre id="logTaskOutput" class="text-light text-wrap mb-0" style="white-space: pre-wrap;"></pre>
                </div>
            </div>
            <div class="modal-footer border-secondary justify-content-between py-1">
                <span class="small text-muted font-monospace" id="logTaskStatus"></span>
                <button type="button" class="btn btn-sm btn-outline-light" data-bs-dismiss="modal">Close Terminal</button>
            </div>
        </div>
    </div>
</div>
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
<div class="modal fade" id="pmaSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title"><i class="bi bi-gear text-dark"></i> Global Upload Limits</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i> This will update the system-wide Nginx and PHP-FPM upload limits, instantly allowing larger SQL imports in phpMyAdmin.
                </div>
                <form id="pmaSettingsForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Max Upload Size (MB)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="upload_size" value="512" min="2" max="2048" required>
                            <span class="input-group-text bg-white">MB</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Max Execution Time (Seconds)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="max_time" value="300" min="30" max="3600" required>
                            <span class="input-group-text bg-white">Sec</span>
                        </div>
                        <div class="form-text text-muted" style="font-size: 0.75rem;">Increase this if massive database imports are timing out.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark w-50" id="submitPmaSettingsBtn"><i class="bi bi-save"></i> Apply Globally</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="systemSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-bottom">
                <h5 class="modal-title"><i class="bi bi-sliders"></i> Control Panel Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <h6 class="border-bottom pb-2 mb-3 text-danger"><i class="bi bi-fingerprint"></i> Administrator Security</h6>
                <div class="form-check form-switch fs-5 mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="twoFactorToggle">
                    <label class="form-check-label" for="twoFactorToggle">Require 2FA for Admin Login</label>
                </div>
                <div id="qrCodeContainer" class="d-none text-center p-3 border rounded bg-white mb-4 shadow-sm">
                    <h6 class="text-success"><i class="bi bi-shield-lock-fill"></i> 2FA Enabled!</h6>
                    <p class="text-muted small mb-2">Scan this QR Code with Google Authenticator immediately:</p>
                    <img id="qrCodeImage" src="" alt="2FA QR Code" class="img-thumbnail mb-2" style="width: 150px; height: 150px;">
                    <p class="small text-muted mb-1">Or enter this manual secret key:</p>
                    <p class="fw-bold font-monospace fs-5 text-dark mb-0" id="totpSecretText"></p>
                </div>
                
                <h6 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-shield-lock"></i> Secure Panel Domain</h6>
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i> Bind this control panel to a real domain name with a free Let's Encrypt SSL certificate. <strong>DNS must point to this server first!</strong>
                </div>
                
                <form id="securePanelForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Panel Domain Name (e.g., cp.yourdomain.com)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-globe"></i></span>
                            <input type="text" class="form-control" name="domain" placeholder="cp.example.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Admin Email (For SSL Expiry Alerts)</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div id="securePanelAlert" class="alert d-none mt-3"></div>
                    <button type="button" class="btn btn-dark w-100" id="submitSecurePanelBtn">
                        <i class="bi bi-lock-fill"></i> Secure Control Panel
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
<?php include 'views/footer.php'; ?>