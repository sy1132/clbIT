<?php

declare(strict_types=1);
?>
<?php if (($pageLayout ?? 'public') === 'auth'): ?>
</div>
<?php else: ?>
    </div>
</main>
<?php endif; ?>
<?php if (!isset($hideFooter) || !$hideFooter): ?>
<footer class="footer-glass mt-5 py-4">
    <div class="container d-flex flex-column flex-lg-row justify-content-between gap-2 align-items-lg-center">
        <div>
            <div class="fw-semibold"><?php echo e(APP_NAME); ?></div>
            <div class="small text-white-50">Website câu lạc bộ IT dùng PHP & MySQL</div>
        </div>
        <div class="small text-white-50">Responsive layout, CRUD, upload, search, đăng ký sự kiện và quản lý bình luận.</div>
    </div>
</footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo e(BASE_URL); ?>/assets/js/app.js"></script>
</body>
</html>
