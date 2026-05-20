<?php
// /opt/panel/www/views/footer.php
?>
<?php require_once __DIR__ . '/../classes/Branding.php'; $brand = Branding::getSettings(); ?>
        <style>
            .main-content {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
                padding-bottom: 0 !important; 
            }
            body { overflow-x: hidden; }
            .markdown-body pre { background-color: #212529; color: #f8f9fa; padding: 12px; border-radius: 6px; font-family: monospace; overflow-x: auto; }
            .markdown-body code { font-family: monospace; color: #d63384; }
            .markdown-body pre code { color: #f8f9fa; }
            .markdown-body p:last-child { margin-bottom: 0; }
        </style>

        <footer class="py-3 mt-auto border-top text-muted small d-flex justify-content-between align-items-center">
            <div>
                <?php if (!$brand['hide_footer']): ?>
                    <span class="fw-bold text-dark"><i class="bi bi-shield-check text-success"></i> <?= htmlspecialchars($brand['title']) ?></span> &copy; <?php echo date('Y'); ?>
                <?php else: ?>
                    <span>&copy; <?php echo date('Y'); ?> <?= htmlspecialchars($brand['title']) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <span class="me-3"><i class="bi bi-hdd-network"></i> Node: <?php echo gethostname(); ?></span>
                <?php 
                if(!defined('PANEL_VERSION')) {
                    @include_once '/opt/panel/www/version.php';
                }
                ?>
                <span class="text-muted small">Stackrium Control v<?php echo defined('PANEL_VERSION') ? PANEL_VERSION : 'Unknown'; ?></span>
            </div>
        </footer>
    </main> </div> </div> <div class="toast-container position-fixed top-0 end-0 p-4" style="z-index: 1100;"></div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<script>
// Make the page title change dynamically when clicking the sidebar
document.querySelectorAll('#sidebarNav .nav-link').forEach(link => {
    link.addEventListener('shown.bs.tab', function (event) {
        let tabText = event.target.innerText.trim();
        let titleEl = document.getElementById('pageTitle');
        if(titleEl) titleEl.innerText = tabText;
    });
});
</script>

<script src="/js/core.js?v=<?php echo time(); ?>"></script>
<script src="/js/modules/web.js"></script>
<script src="/js/modules/system.js"></script>
<script src="/js/modules/database.js"></script>
<script src="/js/modules/security.js"></script>
<script src="/js/modules/mail.js"></script>
<script src="/js/modules/support.js"></script>

</body>
</html>