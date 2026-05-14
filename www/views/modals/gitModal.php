<div class="modal fade" id="gitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-github me-2"></i> Clone Git Repository</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="gitForm">
            
            <div class="mb-3">
                <label class="form-label fw-bold small">1. System Username</label>
                <select class="form-select user-dropdown border-secondary" name="username" id="sshUsername" required>
                    <option value="">Select a user first...</option>
                </select>
            </div>

            <div class="mb-3 p-3 bg-light rounded border d-none" id="sshKeyContainer">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small text-dark"><i class="bi bi-key text-success me-1"></i> SSH Deploy Key</span>
                </div>
                <textarea class="form-control font-monospace small text-success bg-dark" id="sshKeyDisplay" rows="4" readonly placeholder="Fetching key..."></textarea>
                <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i> Add this key to your Git provider (GitHub/GitLab) as a Deploy Key before deploying.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">2. Target Domain</label>
                <select class="form-select domain-dropdown" name="domain" required>
                    <option value="">Loading domains...</option>
                </select>
                <div class="form-text text-danger">Note: The domain's public_html folder must be completely empty!</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">3. Repository URL (SSH Format)</label>
                <input type="text" class="form-control" name="repo_url" placeholder="git@github.com:user/repo.git" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label fw-bold small">4. Branch Name</label>
                <input type="text" class="form-control" name="branch" value="main" placeholder="main, master, staging..." required>
            </div>

            <div id="gitAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-dark w-100 fw-bold" id="submitGitBtn"><i class="bi bi-cloud-download me-2"></i> Deploy Repository</button>
        </form>
      </div>
    </div>
  </div>
</div>