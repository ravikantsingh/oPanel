<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 mb-0">Dashboard</h1>
    
    <!-- MICRO-BADGES (System Stats) -->
    <div class="d-flex align-items-center ms-auto me-4">
        <span class="badge bg-light text-dark border me-2 shadow-sm py-2" title="CPU Load">
            <i class="bi bi-cpu text-primary me-1 fs-6 align-middle"></i> 
            <span id="cpuText" class="align-middle fw-bold">--</span>
        </span>
        <span class="badge bg-light text-dark border me-2 shadow-sm py-2" title="RAM Usage">
            <i class="bi bi-memory text-info me-1 fs-6 align-middle"></i> 
            <span id="ramText" class="align-middle fw-bold">-- / -- MB</span>
        </span>
        <span class="badge bg-light text-dark border shadow-sm py-2" title="Disk Usage">
            <i class="bi bi-hdd text-warning me-1 fs-6 align-middle"></i> 
            <span id="diskText" class="align-middle fw-bold">-- / -- GB</span>
        </span>
        
        <!-- Hidden progress bars to prevent legacy JS errors -->
        <div class="d-none">
            <div id="cpuBar"></div><div id="ramBar"></div><div id="diskBar"></div>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div>
        <button class="btn btn-sm btn-outline-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#systemSettingsModal">
            <i class="bi bi-sliders"></i> System Settings
        </button>
        <button class="btn btn-sm btn-outline-primary shadow-sm ms-2" data-bs-toggle="modal" data-bs-target="#brandingModal">
            <i class="bi bi-palette-fill"></i> Branding & UI
        </button>
    </div>
</div>