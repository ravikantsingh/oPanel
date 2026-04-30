<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body py-3">
                <div class="row text-center">
                    <div class="col-md-4 border-end">
                        <div class="text-muted small fw-bold mb-1">CPU LOAD (1m)</div>
                        <div class="d-flex align-items-center justify-content-center">
                            <h4 class="mb-0 me-2" id="cpuText">--</h4>
                            <div class="progress flex-grow-1" style="height: 6px; max-width: 100px;">
                                <div id="cpuBar" class="progress-bar bg-primary" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 border-end">
                        <div class="text-muted small fw-bold mb-1">RAM USAGE</div>
                        <div class="d-flex align-items-center justify-content-center">
                            <h4 class="mb-0 me-2 fs-6" id="ramText">-- / -- MB</h4>
                            <div class="progress flex-grow-1" style="height: 6px; max-width: 100px;">
                                <div id="ramBar" class="progress-bar bg-info" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small fw-bold mb-1">DISK SPACE (ROOT)</div>
                        <div class="d-flex align-items-center justify-content-center">
                            <h4 class="mb-0 me-2 fs-6" id="diskText">-- / -- GB</h4>
                            <div class="progress flex-grow-1" style="height: 6px; max-width: 100px;">
                                <div id="diskBar" class="progress-bar bg-warning" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>