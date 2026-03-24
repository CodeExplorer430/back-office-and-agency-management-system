<?php
declare(strict_types=1);

$ct2Pagination = is_array($ct2Pagination ?? null) ? $ct2Pagination : [];
if ($ct2Pagination === [] || (int) ($ct2Pagination['total_pages'] ?? 1) <= 1) {
    return;
}

$ct2CurrentPage = (int) ($ct2Pagination['page'] ?? 1);
$ct2TotalPages = (int) ($ct2Pagination['total_pages'] ?? 1);
$ct2PageParam = (string) ($ct2Pagination['page_param'] ?? 'page');
$ct2TotalRecords = (int) ($ct2Pagination['total_records'] ?? 0);
$ct2PerPage = max(1, (int) ($ct2Pagination['per_page'] ?? 10));
$ct2StartRecord = $ct2TotalRecords > 0 ? (($ct2CurrentPage - 1) * $ct2PerPage) + 1 : 0;
$ct2EndRecord = min($ct2TotalRecords, $ct2CurrentPage * $ct2PerPage);
$ct2PaginationQuery = $_GET;
?>
<div class="ct2-pagination">
    <span class="ct2-pagination-summary">
        Showing <?= $ct2StartRecord; ?>-<?= $ct2EndRecord; ?> of <?= $ct2TotalRecords; ?>
    </span>
    <div class="ct2-pagination-links" aria-label="Pagination">
        <?php if ($ct2CurrentPage > 1): ?>
            <?php $ct2PreviousQuery = $ct2PaginationQuery; $ct2PreviousQuery[$ct2PageParam] = $ct2CurrentPage - 1; ?>
            <a class="ct2-pagination-link" href="<?= htmlspecialchars(ct2_url($ct2PreviousQuery), ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
        <?php endif; ?>
        <span class="ct2-pagination-current">Page <?= $ct2CurrentPage; ?> of <?= $ct2TotalPages; ?></span>
        <?php if ($ct2CurrentPage < $ct2TotalPages): ?>
            <?php $ct2NextQuery = $ct2PaginationQuery; $ct2NextQuery[$ct2PageParam] = $ct2CurrentPage + 1; ?>
            <a class="ct2-pagination-link" href="<?= htmlspecialchars(ct2_url($ct2NextQuery), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
        <?php endif; ?>
    </div>
</div>
