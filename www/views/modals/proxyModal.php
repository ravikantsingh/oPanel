<div class="modal fade" id="proxyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-shield-shaded"></i> Traffic Routing: <span id="proxyDomainTitle"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i> If your domain uses a CDN or Load Balancer, configure it here so the server firewall (Fail2ban) can see the real attacker IP addresses.
                </div>
                
                <form id="proxyForm">
                    <input type="hidden" name="domain" id="proxyDomainInput">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Traffic Source / CDN</label>
                        <select class="form-select border-secondary" name="proxy_type" id="proxyTypeSelect">
                            <option value="direct">Direct Connection (No Proxy/CDN)</option>
                            
                            <optgroup label="Automated Global Networks">
                                <option value="cloudflare">Cloudflare (Automated IPs & Headers)</option>
                                <option value="fastly">Fastly (Automated IPs & Headers)</option>
                                <option value="cloudfront">AWS CloudFront (Automated IPs & Headers)</option>
                                <option value="sucuri">Sucuri WAF (Automated IPs & Headers)</option>
                            </optgroup>
                            
                            <optgroup label="Advanced">
                                <option value="custom">Custom Proxy / Private Load Balancer</option>
                            </optgroup>
                        </select>
                    </div>

                    <div id="customProxySettings" class="d-none border-top pt-3 mt-3">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Trusted Proxy IPs (CIDR)</label>
                            <textarea class="form-control font-monospace" name="custom_ips" id="proxyCustomIps" rows="3" placeholder="10.0.0.5&#10;192.168.1.0/24"></textarea>
                            <div class="form-text">Comma or newline separated list of IPs allowed to forward traffic.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Real-IP Header</label>
                            <input type="text" class="form-control font-monospace" name="custom_header" id="proxyCustomHeader" value="X-Forwarded-For">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-2" id="saveProxyBtn"><i class="bi bi-diagram-3"></i> Apply Routing Rules</button>
                </form>
            </div>
        </div>
    </div>
</div>