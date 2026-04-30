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