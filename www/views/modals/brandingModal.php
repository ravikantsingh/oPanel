<div class="modal fade" id="brandingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-bottom-0">
                <h5 class="modal-title"><i class="bi bi-palette-fill me-2"></i> White-Label Branding</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="brandingForm" enctype="multipart/form-data">
                <div class="modal-body bg-light">
                    
                    <h6 class="border-bottom pb-2 text-dark fw-bold">Global Identity</h6>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Browser Title</label>
                            <input type="text" class="form-control" name="brand_title" placeholder="e.g. Acme Hosting">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Custom Logo URL</label>
                            <input type="text" class="form-control" name="brand_logo_url" placeholder="/index.php">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Theme Primary Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="brand_theme_color" title="Overrides default blue">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Sidebar Background Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="brand_sidebar_color" title="Overrides default dark">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Navbar Logo (PNG/SVG)</label>
                            <input class="form-control form-control-sm" type="file" name="brand_logo" accept=".png,.svg,.jpg">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Favicon (SVG)</label>
                            <input class="form-control form-control-sm" type="file" name="brand_favicon_svg" accept=".svg">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Favicon (ICO)</label>
                            <input class="form-control form-control-sm" type="file" name="brand_favicon_ico" accept=".ico">
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 text-dark fw-bold mt-4">Login Screen & Footer</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Login Subtext</label>
                            <input type="text" class="form-control" name="brand_subtext" placeholder="Unified Server Management">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold">Background Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="brand_login_bg_color">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold">Image Fit</label>
                            <select class="form-select" name="brand_login_bg_fit">
                                <option value="cover">Cover Screen</option>
                                <option value="contain">Center</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Background Image (JPG/PNG)</label>
                            <input class="form-control form-control-sm" type="file" name="brand_login_bg_image" accept=".jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-6 mb-3 pt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="brand_hide_footer" id="hideFooterCheck">
                                <label class="form-check-label small fw-bold" for="hideFooterCheck">Hide oPanel Footer Text</label>
                            </div>
                        </div>
                    </div>

                    <div id="brandingAlert" class="alert d-none mt-3"></div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" id="saveBrandingBtn">Save & Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>