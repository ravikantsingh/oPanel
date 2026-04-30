<div class="modal fade" id="firewallModal" tabindex="-1" aria-labelledby="firewallModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="firewallModalLabel">Open Firewall Port</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="firewallForm">
            <div class="mb-3">
                <label for="fwPort" class="form-label">Port Number (1 - 65535)</label>
                <input type="number" class="form-control" id="fwPort" name="port" min="1" max="65535" required>
            </div>
            <div class="mb-3">
                <label for="fwProtocol" class="form-label">Protocol</label>
                <select class="form-select" id="fwProtocol" name="protocol">
                    <option value="tcp" selected>TCP</option>
                    <option value="udp">UDP</option>
                </select>
            </div>
            <div id="fwFormAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-danger w-100" id="submitFwBtn">Allow Port</button>
        </form>
      </div>
    </div>
  </div>
</div>