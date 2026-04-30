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