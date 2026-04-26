    </div><!-- /page-content -->
    <footer class="text-center py-3" style="font-size:.75rem;color:var(--text-muted);border-top:1px solid var(--border);background:var(--bg-card)">
        © <?= date('Y') ?> Unidad Residencial Vivimostodos — Sistema de Gestión v1.0
    </footer>
</div><!-- /main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/vivimostodos/assets/js/app.js"></script>
<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
