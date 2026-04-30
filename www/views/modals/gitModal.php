<div class="modal fade" id="gitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-github"></i> Clone Git Repository</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="gitForm">
            <div class="mb-3 p-3 bg-light rounded border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small"><i class="bi bi-key"></i> SSH Deploy Key</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="fetchSshBtn">View Key</button>
                </div>
                <textarea class="form-control font-monospace small d-none" id="sshKeyDisplay" rows="3" readonly placeholder="Your public SSH key will appear here..."></textarea>
                <div id="sshKeyMessage" class="small text-muted mt-1">Copy this key to your GitHub/GitLab repository settings.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">System Username</label>
                <select class="form-select user-dropdown" name="username" id="sshUsername" required>
                    <option value="">Loading users...</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Target Domain</label>
                <select class="form-select domain-dropdown" name="domain" required>
                    <option value="">Loading domains...</option>
                </select>
                <div class="form-text text-danger">Note: The domain's public_html folder must be empty!</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Repository URL (Public HTTPS)</label>
                <input type="text" class="form-control" name="repo_url" placeholder="git@github.com:user/repo.git" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Branch Name</label>
                <input type="text" class="form-control" name="branch" value="main" placeholder="main, master, staging..." required>
            </div>
            <div id="gitAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-dark w-100" id="submitGitBtn">Deploy Repository</button>
        </form>
      </div>
    </div>
  </div>
</div>