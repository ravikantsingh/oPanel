<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">Create New Linux User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addUserForm">
            <div class="mb-3">
                <label for="username" class="form-label">Username (lowercase, no spaces)</label>
                <input type="text" class="form-control" id="username" name="username" required pattern="[a-z0-9]+">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div id="formAlert" class="alert d-none"></div>
            <button type="submit" class="btn btn-primary w-100" id="submitUserBtn">Create User</button>
        </form>
      </div>
    </div>
  </div>
</div>