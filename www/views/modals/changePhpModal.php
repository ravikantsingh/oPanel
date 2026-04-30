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
                    <option value="">Loading installed versions...</option>
                </select>
            </div>
            <div id="phpFormAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-info w-100" id="submitPhpBtn">Update PHP Version</button>
        </form>
      </div>
    </div>
  </div>
</div>