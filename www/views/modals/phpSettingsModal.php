<!-- /opt/panel/www/views/components/modal_php_settings.php -->
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
                        
                        <!-- TAB 1: PERFORMANCE -->
                        <div class="tab-pane fade show active" id="tab-perf">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">memory_limit</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Max RAM a single script can consume (e.g., 128M, 256M).</small>
                                    <input type="text" class="form-control" name="php_memory_limit" id="ps_mem">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">max_execution_time</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Max seconds a script can run before being terminated.</small>
                                    <input type="number" class="form-control" name="php_max_exec_time" id="ps_max_exec">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">max_input_time</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Max seconds allowed to parse request data (POST/GET).</small>
                                    <input type="number" class="form-control" name="php_max_input_time" id="ps_max_in">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">post_max_size</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Max size of POST data allowed. Must be larger than upload limit.</small>
                                    <input type="text" class="form-control" name="php_post_max_size" id="ps_post">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">upload_max_filesize</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Maximum allowed size for a single uploaded file.</small>
                                    <input type="text" class="form-control" name="php_upload_max_filesize" id="ps_up">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">opcache.enable</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Caches precompiled script bytecode in RAM for speed.</small>
                                    <select class="form-select" name="php_opcache_enable" id="ps_opc"><option value="on">on</option><option value="off">off</option></select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold mb-0">disable_functions</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Comma-separated list of risky PHP functions to block (e.g., exec, shell_exec).</small>
                                    <input type="text" class="form-control" name="php_disable_functions" id="ps_dis">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: COMMON SETTINGS -->
                        <div class="tab-pane fade" id="tab-common">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold mb-0">include_path</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Directories where PHP searches for require() and include() files.</small>
                                    <input type="text" class="form-control" name="php_include_path" id="ps_inc">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold mb-0">session.save_path</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Directory where active user session data is physically stored.</small>
                                    <input type="text" class="form-control" name="php_session_save_path" id="ps_sess">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold mb-0">open_basedir</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Jails PHP scripts so they can only access files within specified directories.</small>
                                    <input type="text" class="form-control" name="php_open_basedir" id="ps_open">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold mb-0">error_reporting</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Determines which errors are logged/shown (e.g., E_ALL & ~E_NOTICE).</small>
                                    <input type="text" class="form-control" name="php_error_reporting" id="ps_err_rep">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold mb-0">display_errors</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Print errors directly to the browser (Turn OFF for production).</small>
                                    <select class="form-select" name="php_display_errors" id="ps_disp_err"><option value="on">on</option><option value="off">off</option></select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold mb-0">allow_url_fopen</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Allows PHP to open remote URLs like files. Often required by plugins.</small>
                                    <select class="form-select" name="php_allow_url_fopen" id="ps_fopen"><option value="on">on</option><option value="off">off</option></select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold mb-0">short_open_tag</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Allows the use of &lt;? instead of &lt;?php. Not recommended.</small>
                                    <select class="form-select" name="php_short_open_tag" id="ps_short"><option value="on">on</option><option value="off">off</option></select>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: FPM ENGINE -->
                        <div class="tab-pane fade" id="tab-fpm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">pm (Manager Type)</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">How worker processes are managed (dynamic is best for general web).</small>
                                    <select class="form-select" name="fpm_pm" id="ps_pm"><option value="dynamic">dynamic</option><option value="ondemand">ondemand</option><option value="static">static</option></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">pm.max_children</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Maximum number of concurrent PHP processes allowed.</small>
                                    <input type="number" class="form-control" name="fpm_max_children" id="ps_fpm_child">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">pm.max_requests</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Restarts worker processes after this many requests to prevent memory leaks.</small>
                                    <input type="number" class="form-control" name="fpm_max_requests" id="ps_fpm_req">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">pm.start_servers</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Number of child processes created on startup (dynamic only).</small>
                                    <input type="number" class="form-control" name="fpm_start_servers" id="ps_fpm_start">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">pm.min_spare_servers</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Minimum number of idle processes waiting to serve requests.</small>
                                    <input type="number" class="form-control" name="fpm_min_spare_servers" id="ps_fpm_min">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-0">pm.max_spare_servers</label>
                                    <small class="form-text text-muted d-block lh-sm mb-2" style="font-size:0.75rem;">Maximum number of idle processes. Excess will be killed.</small>
                                    <input type="number" class="form-control" name="fpm_max_spare_servers" id="ps_fpm_max">
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark px-4" id="savePhpSettingsBtn"><i class="bi bi-cpu-fill"></i> Save & Restart FPM</button>
            </div>
        </div>
    </div>
</div>