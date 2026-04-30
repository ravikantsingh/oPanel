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
                    <option value="">Loading installed versions...</option>
                </select>
            </div>
            <div id="domainFormAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-success w-100" id="submitDomainBtn">Create Domain & Nginx vHost</button>
        </form>
      </div>
    </div>
  </div>
</div>