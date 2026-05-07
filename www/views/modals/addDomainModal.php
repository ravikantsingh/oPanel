<div class="modal fade" id="addDomainModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white border-bottom border-success border-3">
        <h5 class="modal-title"><i class="bi bi-globe text-success me-2"></i> Add New Domain Environment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-light">
        <form id="addDomainForm">
          
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Assign to User</label>
              <select class="form-select user-dropdown border-secondary" name="username" required>
                  <option value="">Select User...</option>
                  </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">PHP Engine</label>
                <select class="form-select border-secondary" name="php_version" id="phpVersionSelect">
                    <option value="">Scanning server...</option>
                </select>
            </div>
          </div>

          <div class="form-check form-switch mb-3 bg-white p-3 border border-secondary border-opacity-25 rounded shadow-sm d-flex align-items-center">
              <input class="form-check-input ms-1 me-3 mt-0" type="checkbox" role="switch" id="isSubdomainToggle" name="is_subdomain" value="true" style="transform: scale(1.3); cursor: pointer;">
              <label class="form-check-label small fw-bold text-dark pt-1" for="isSubdomainToggle" style="cursor: pointer;">Create as a Subdomain</label>
          </div>

          <div id="primaryDomainGroup" class="mb-3">
              <label class="form-label small fw-bold">Domain Name</label>
              <input type="text" class="form-control font-monospace border-secondary" name="domain" placeholder="example.com" id="primaryDomainInput" required>
              <div class="form-text small">Do not include http:// or www.</div>
          </div>

          <div id="subdomainGroup" class="mb-3 d-none">
              <label class="form-label small fw-bold">Subdomain Prefix & Parent</label>
              <div class="input-group shadow-sm">
                  <input type="text" class="form-control text-end font-monospace border-secondary" name="prefix" placeholder="blog" id="subdomainPrefixInput">
                  <span class="input-group-text bg-light border-secondary">.</span>
                  <select class="form-select font-monospace domain-dropdown border-secondary" name="parent_domain" id="subdomainParentInput">
                      <option value="">Select Parent...</option>
                      </select>
              </div>
              <div class="form-text small text-success mt-2"><i class="bi bi-shield-check"></i> Environment will be created in an isolated sibling directory for maximum security.</div>
          </div>

        </form>
      </div>
      <div class="modal-footer bg-white border-top">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="addDomainForm" class="btn btn-success px-4 fw-bold" id="btnSubmitDomain"><i class="bi bi-cloud-plus"></i> Provision Environment</button>
      </div>
    </div>
  </div>
</div>