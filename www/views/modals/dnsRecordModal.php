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