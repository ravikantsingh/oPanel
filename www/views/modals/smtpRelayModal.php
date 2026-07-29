<div class="modal fade" id="smtpRelayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-send-arrow-up text-primary me-2"></i> External SMTP Relay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info bg-opacity-10 border-info border-start border-4 small">
                    <strong>Bypass Cloud Provider Blocks:</strong> Use this to route all outbound server mail through a trusted provider (like SendGrid or Amazon SES) to bypass port 25 blocks and guarantee 100% inbox delivery.
                </div>

                <form id="formSmtpRelay">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Provider Preset</label>
                            <select class="form-select bg-light" id="relayProvider" name="provider">
                                <option value="custom">Custom Provider</option>
                                <option value="sendgrid">SendGrid (Twilio)</option>
                                <option value="mailgun">Mailgun</option>
                                <option value="aws_ses">Amazon SES</option>
                            </select>
                        </div>
                        
                        <div class="col-md-8">
                            <label class="form-label">SMTP Hostname</label>
                            <input type="text" class="form-control" id="relayHost" name="host" placeholder="smtp.sendgrid.net" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Port</label>
                            <input type="number" class="form-control" id="relayPort" name="port" value="587" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" id="relayUser" name="user" placeholder="apikey" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SMTP Password / API Key</label>
                            <input type="password" class="form-control" id="relayPass" name="pass" placeholder="Starts with SG. or similar..." required>
                            <div class="form-text text-muted"><i class="bi bi-shield-lock text-success"></i> Never stored in DB. Secured via chown root.</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-danger me-auto" id="btnDisableRelay">Disable Relay</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnEnableRelay">Apply Routing Rules</button>
            </div>
        </div>
    </div>
</div>

<script>
// 1. Smart Presets Logic
document.getElementById('relayProvider').addEventListener('change', function() {
    const host = document.getElementById('relayHost');
    const port = document.getElementById('relayPort');
    const user = document.getElementById('relayUser');
    
    if (this.value === 'sendgrid') {
        host.value = 'smtp.sendgrid.net'; port.value = '587'; user.value = 'apikey';
    } else if (this.value === 'mailgun') {
        host.value = 'smtp.mailgun.org'; port.value = '587'; user.value = 'postmaster@yourdomain.com';
    } else if (this.value === 'aws_ses') {
        host.value = 'email-smtp.us-east-1.amazonaws.com'; port.value = '587'; user.value = '';
    } else {
        host.value = ''; port.value = '587'; user.value = '';
    }
});

// 2. Dynamic SRE Context State Poller
document.getElementById('smtpRelayModal').addEventListener('show.bs.modal', function () {
    const statusTextElement = document.getElementById('smtpRelayStatusText');
    const disableButtonElement = document.getElementById('btnDisableRelay');
    
    if (statusTextElement && disableButtonElement) {
        // Evaluate the exact live database string injected into the parent template
        if (statusTextElement.innerHTML.includes('Active')) {
            disableButtonElement.style.display = 'inline-block';
        } else {
            disableButtonElement.style.display = 'none';
        }
    }
});
</script>