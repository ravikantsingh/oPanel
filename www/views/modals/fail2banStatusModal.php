<div class="modal fade" id="fail2banStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white border-bottom border-danger border-3">
        <h5 class="modal-title"><i class="bi bi-shield-slash-fill me-2 text-danger"></i> Intrusion Prevention</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body bg-light p-0">
        <ul class="nav nav-tabs nav-fill bg-white pt-2 border-bottom-0" id="f2bTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold border-0 text-dark" id="f2b-telemetry-tab" data-bs-toggle="tab" data-bs-target="#tab-f2b-telemetry" type="button" role="tab">
                    <i class="bi bi-activity me-1"></i> Live Telemetry
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold border-0 text-dark" id="f2b-settings-tab" data-bs-toggle="tab" data-bs-target="#tab-f2b-settings" type="button" role="tab" onclick="window.fetchFail2BanSettings()">
                    <i class="bi bi-gear me-1"></i> Global Settings
                </button>
            </li>
        </ul>

        <div class="tab-content" id="f2bTabContent">
            
            <div class="tab-pane fade show active" id="tab-f2b-telemetry" role="tabpanel">
                <div class="row g-0 border-bottom text-center bg-white">
                    <div class="col-6 py-3 border-end">
                        <div class="small text-muted fw-bold text-uppercase">Total Lifetime Bans</div>
                        <div class="fs-3 fw-bold text-danger" id="f2bGlobalTotalBans">0</div>
                    </div>
                    <div class="col-6 py-3">
                        <div class="small text-muted fw-bold text-uppercase">Active Jails</div>
                        <div class="fs-3 fw-bold text-dark" id="f2bGlobalJails">0</div>
                    </div>
                </div>

                <div class="p-3">
                    <div class="table-responsive border rounded bg-white shadow-sm">
                        <table class="table table-hover mb-0 text-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Security Jail</th>
                                    <th>Monitored Log File</th>
                                    <th class="text-center text-warning">Current Strikes</th>
                                    <th class="text-center">Active Bans</th>
                                    <th class="text-center">Total Lifetime</th>
                                </tr>
                            </thead>
                            <tbody id="dynamicFail2banStatsTable">
                                <tr><td colspan="5" class="text-center text-muted py-3">Loading telemetry...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 small text-muted text-center">
                        <i class="bi bi-info-circle"></i> Telemetry is updated in real-time directly from the fail2ban daemon.
                    </div>
                </div>
            </div>

            <div class="tab-pane fade p-4" id="tab-f2b-settings" role="tabpanel">
                <div class="alert alert-secondary border-0 shadow-sm small mb-4">
                    <i class="bi bi-info-circle-fill text-primary me-2"></i> These are the global parameters applied to all active jails (SSH, FTP, Stackrium). Changing these will automatically restart the Fail2ban service.
                </div>
                
                <form id="formF2bSettings">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ban Time</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="f2b_bantime_val" required min="1">
                                <select class="form-select" id="f2b_bantime_unit" style="max-width: 80px;">
                                    <option value="m">Min</option>
                                    <option value="h">Hour</option>
                                    <option value="d">Day</option>
                                </select>
                            </div>
                            <div class="form-text small">Duration of the ban.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Find Time Window</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="f2b_findtime_val" required min="1">
                                <select class="form-select" id="f2b_findtime_unit" style="max-width: 80px;">
                                    <option value="m">Min</option>
                                    <option value="h">Hour</option>
                                </select>
                            </div>
                            <div class="form-text small">Time span to count failures.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Max Retries</label>
                            <input type="number" class="form-control" id="f2b_maxretry" required min="1" max="20">
                            <div class="form-text small">Failures before ban.</div>
                        </div>
                    </div>
                    
                    <div class="text-end mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-primary shadow-sm" onclick="window.saveFail2BanSettings()">
                            <i class="bi bi-save me-1"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>

        </div>
      </div>
    </div>
  </div>
</div>