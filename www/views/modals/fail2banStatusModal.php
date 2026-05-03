<!-- /opt/panel/www/views/modals/fail2banStatusModal.php -->
<div class="modal fade" id="fail2banStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white border-bottom border-danger border-3">
        <h5 class="modal-title"><i class="bi bi-shield-slash-fill me-2 text-danger"></i> Intrusion Prevention Status</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light p-0">
        
        <!-- Global Metrics -->
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

        <!-- Telemetry Table -->
        <div class="p-3">
            <div class="table-responsive border rounded bg-white shadow-sm">
                <table class="table table-hover mb-0 text-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Security Jail</th>
                            <th>Monitored Log File</th>
                            <th class="text-center">Active Bans</th>
                            <th class="text-center">Total Lifetime</th>
                        </tr>
                    </thead>
                    <tbody id="dynamicFail2banStatsTable">
                        <tr><td colspan="4" class="text-center text-muted py-3">Loading telemetry...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 small text-muted text-center">
                <i class="bi bi-info-circle"></i> Telemetry is updated in real-time directly from the fail2ban daemon.
            </div>
        </div>

      </div>
    </div>
  </div>
</div>