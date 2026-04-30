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