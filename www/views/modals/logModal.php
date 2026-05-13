<div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-white border-secondary">
      
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="logModalLabel"><i class="bi bi-terminal me-2"></i>Live Server Terminal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body p-0">
        <div class="p-3 border-bottom border-secondary bg-dark">
            
            <div class="row g-2 mb-3" id="logDomainGroup">
                <div class="col-md-6">
                    <select class="form-select form-select-sm bg-black text-white border-secondary domain-dropdown" id="logDomainSelect">
                        <option value="">Target Domain...</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <select class="form-select form-select-sm bg-black text-white border-secondary user-dropdown" id="logUserSelect">
                        <option value="">Username...</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex w-75 gap-2">
                    <select class="form-select form-select-sm bg-black text-white border-secondary" id="logTypeSelect" style="max-width: 250px;">
                        <optgroup label="Website Logs (Contextual)">
                            <option value="error">Nginx error.log</option>
                            <option value="access">Nginx access.log</option>
                        </optgroup>
                        <optgroup label="System Logs (Global)">
                            <option value="daemon">Python Daemon Engine</option>
                            <option value="fail2ban">Fail2ban Defense</option>
                            <option value="updater">Auto-Update Engine</option>
                            <option value="syslog">OS Syslog</option>
                        </optgroup>
                    </select>
                    <button class="btn btn-sm btn-primary px-3 shadow-sm" id="fetchLogBtn"><i class="bi bi-arrow-repeat"></i> Fetch</button>
                </div>
                <span class="badge bg-success shadow-sm" id="liveIndicator"><span class="spinner-grow spinner-grow-sm" style="width: 0.5rem; height: 0.5rem;"></span> LIVE</span>
            </div>
            
        </div>
        
        <div class="p-3 bg-black text-success" id="logTerminal" style="height: 450px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; white-space: pre-wrap;">
Select a Log Type above, then click Fetch to stream...
        </div>
        
      </div>
    </div>
  </div>
</div>