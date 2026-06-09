<div class="modal fade" id="dnsRecordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-globe"></i> Add/Delete DNS Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info bg-opacity-10 border-info border-start border-4 small mb-4">
            <strong>BIND9 Strict Syntax:</strong> Stackrium utilizes an industry-standard BIND9 resolver. Pay close attention to the input rules below, specifically the "Trailing Dot" rule for routing aliases.
        </div>
        <form id="dnsRecordForm">
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label">Action</label>
                    <select class="form-select" name="action">
                        <option value="add">Add Record</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Record Type</label>
                    <select class="form-select" name="type" id="dnsRecordType">
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
                <div class="form-text text-muted small">Use <code>@</code> for the root domain. For subdomains, type only the prefix (e.g., type <code>ftp</code>, not ftp.domain.com).</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Record Value</label>
                <input type="text" class="form-control" name="value" id="dnsRecordValue" placeholder="e.g., 192.168.1.10" required>
                <div id="dnsValueHelp" class="form-text text-muted small">Enter a valid IPv4 address.</div>
            </div>
            <div id="dnsRecordAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-primary w-100" id="submitDnsRecordBtn">Execute Change</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
// 1. Dynamic Helper Text Logic (Updates rules based on record type)
document.getElementById('dnsRecordType').addEventListener('change', function() {
    const helpText = document.getElementById('dnsValueHelp');
    const valueInput = document.getElementById('dnsRecordValue');
    
    switch(this.value) {
        case 'A':
            valueInput.placeholder = "e.g., 192.168.1.10";
            helpText.innerHTML = "Enter a valid IPv4 address.";
            break;
        case 'CNAME':
        case 'MX':
            valueInput.placeholder = "e.g., target.domain.com.";
            helpText.innerHTML = "<strong class='text-danger'><i class='bi bi-shield-exclamation'></i> Crucial:</strong> External targets <strong>must</strong> end with a trailing dot (<code>.</code>) so BIND9 doesn't append your domain name to the end of it.";
            break;
        case 'TXT':
            valueInput.placeholder = "e.g., v=spf1 a mx ~all";
            helpText.innerHTML = "Enter your text string. The system will automatically wrap it in quotes for you.";
            break;
    }
});
</script>