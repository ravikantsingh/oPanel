<?php
// /opt/panel/www/views/footer.php
?>
        <!--  NEW: True Sidebar Sticky Footer CSS  -->
        <style>
            .main-content {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
                padding-bottom: 0 !important; /* Removes the 30px padding pushing the footer down */
            }
            body {
                overflow-x: hidden;
            }
        </style>

        <!-- oPanel Footer -->
        <footer class="py-3 mt-auto border-top text-muted small d-flex justify-content-between align-items-center">
            <div>
                <span class="fw-bold text-dark"><i class="bi bi-shield-check text-success"></i> oPanel</span> &copy; <?php echo date('Y'); ?>
            </div>
            <div>
                <span class="me-3"><i class="bi bi-hdd-network"></i> Node: <?php echo gethostname(); ?></span>
                <span class="badge bg-secondary">v1.0.0-Stable</span>
            </div>
        </footer>
    </main> <!-- End Main Content Window -->
  </div> <!-- End Row -->
</div> <!-- End Container -->

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Load the new external JS file with a cache-buster version -->
<script src="/js/panel.js?v=<?php echo time(); ?>"></script>

</body>
</html>