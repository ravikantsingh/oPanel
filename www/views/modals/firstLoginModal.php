<div class="modal fade" id="firstLoginModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-rocket-takeoff me-2"></i> Welcome to Stackrium Control</h5>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-dark">Let's setup your server profile</h4>
                    <p class="text-muted small">Please complete your registration to activate your commercial license and unlock the dashboard.</p>
                </div>

                <form id="firstLoginForm" onsubmit="event.preventDefault();">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="owner_name" required placeholder="John Doe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Working Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="owner_email" required placeholder="admin@company.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Company Name</label>
                            <input type="text" class="form-control" name="company" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Country <span class="text-danger">*</span></label>
                            <select class="form-select" name="country" required>
                                <option value="" selected disabled>Select your country...</option>
                                <option value="US">United States</option>
                                <option value="GB">United Kingdom</option>
                                <option value="IN">India</option>
                                <option value="CA">Canada</option>
                                <option value="AU">Australia</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="registrationAlert" class="alert d-none mt-4 mb-0"></div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold" id="btnSubmitRegistration">
                            Activate License & Unlock Panel <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>