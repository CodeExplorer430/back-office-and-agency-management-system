<?php
$ct2CampaignForm = $ct2CampaignForEdit ?? null;
$ct2PromotionForm = $ct2PromotionForEdit ?? null;
$ct2AffiliateForm = $ct2AffiliateForEdit ?? null;
?>
<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Demand Generation</p>
            <h2>Marketing and Promotions Management</h2>
        </div>
        <form method="get" action="<?= htmlspecialchars(ct2_url(), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-inline-form">
            <input type="hidden" name="module" value="marketing">
            <input type="hidden" name="action" value="index">
            <input class="ct2-input" name="search" type="text" placeholder="Search campaigns or affiliates" value="<?= htmlspecialchars((string) $ct2Search, ENT_QUOTES, 'UTF-8'); ?>">
            <select class="ct2-select" name="campaign_status">
                <option value="">All campaign states</option>
                <?php foreach (['draft', 'pending_approval', 'active', 'paused', 'completed', 'archived'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= $ct2CampaignStatus === $ct2Option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="ct2-select" name="channel_type">
                <option value="">All channels</option>
                <?php foreach (['email', 'social', 'search', 'direct', 'affiliate', 'hybrid'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= $ct2ChannelType === $ct2Option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="ct2-select" name="affiliate_status">
                <option value="">All affiliate states</option>
                <?php foreach (['onboarding', 'active', 'paused', 'inactive'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= $ct2AffiliateStatus === $ct2Option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ct2-btn ct2-btn-secondary" type="submit">Filter</button>
        </form>
    </div>
</section>

<section class="ct2-stat-grid">
    <article class="ct2-stat-card">
        <h3>Total Campaigns</h3>
        <strong><?= (int) ($ct2CampaignSummary['total_campaigns'] ?? 0); ?></strong>
        <span>Pending approval: <?= (int) ($ct2CampaignSummary['pending_campaigns'] ?? 0); ?></span>
    </article>
    <article class="ct2-stat-card">
        <h3>Active Campaigns</h3>
        <strong><?= (int) ($ct2CampaignSummary['active_campaigns'] ?? 0); ?></strong>
        <span>Attributed revenue: <?= number_format((float) ($ct2CampaignSummary['attributed_revenue'] ?? 0), 2); ?></span>
    </article>
    <article class="ct2-stat-card">
        <h3>Affiliate Network</h3>
        <strong><?= (int) ($ct2AffiliateSummary['total_affiliates'] ?? 0); ?></strong>
        <span>Payout hold: <?= (int) ($ct2AffiliateSummary['blocked_payout_affiliates'] ?? 0); ?></span>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3><?= $ct2CampaignForm !== null ? 'Update Campaign' : 'Register Campaign'; ?></h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'saveCampaign']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form ct2-form-grid">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="ct2_campaign_id" value="<?= (int) ($ct2CampaignForm['ct2_campaign_id'] ?? 0); ?>">

            <label class="ct2-label">Campaign Code</label>
            <input class="ct2-input" name="campaign_code" required value="<?= htmlspecialchars((string) ($ct2CampaignForm['campaign_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Campaign Name</label>
            <input class="ct2-input" name="campaign_name" required value="<?= htmlspecialchars((string) ($ct2CampaignForm['campaign_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Campaign Type</label>
            <select class="ct2-select" name="campaign_type">
                <?php foreach (['seasonal', 'partner', 'voucher', 'affiliate', 'brand', 'other'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2CampaignForm['campaign_type'] ?? 'other') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Channel</label>
            <select class="ct2-select" name="channel_type">
                <?php foreach (['email', 'social', 'search', 'direct', 'affiliate', 'hybrid'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2CampaignForm['channel_type'] ?? 'hybrid') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Start Date</label>
            <input class="ct2-input" name="start_date" type="date" required value="<?= htmlspecialchars((string) ($ct2CampaignForm['start_date'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">End Date</label>
            <input class="ct2-input" name="end_date" type="date" required value="<?= htmlspecialchars((string) ($ct2CampaignForm['end_date'] ?? date('Y-m-d', strtotime('+30 days'))), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Budget Amount</label>
            <input class="ct2-input" name="budget_amount" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($ct2CampaignForm['budget_amount'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Campaign Status</label>
            <select class="ct2-select" name="status">
                <?php foreach (['draft', 'pending_approval', 'active', 'paused', 'completed', 'archived'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2CampaignForm['status'] ?? 'pending_approval') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Approval Status</label>
            <select class="ct2-select" name="approval_status">
                <?php foreach (['pending', 'approved', 'rejected'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2CampaignForm['approval_status'] ?? 'pending') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Target Audience</label>
            <input class="ct2-input" name="target_audience" value="<?= htmlspecialchars((string) ($ct2CampaignForm['target_audience'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">External Segment ID</label>
            <input class="ct2-input" name="external_customer_segment_id" value="<?= htmlspecialchars((string) ($ct2CampaignForm['external_customer_segment_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="ct1 or crm" value="<?= htmlspecialchars((string) ($ct2CampaignForm['source_system'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <button class="ct2-btn ct2-btn-primary" type="submit">Save Campaign</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3><?= $ct2PromotionForm !== null ? 'Update Promotion' : 'Promotion Builder'; ?></h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'savePromotion']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="ct2_promotion_id" value="<?= (int) ($ct2PromotionForm['ct2_promotion_id'] ?? 0); ?>">
            <label class="ct2-label">Campaign</label>
            <select class="ct2-select" name="ct2_campaign_id" required>
                <option value="">Select campaign</option>
                <?php foreach ($ct2CampaignSelection as $ct2Campaign): ?>
                    <option value="<?= (int) $ct2Campaign['ct2_campaign_id']; ?>" <?= ((int) ($ct2PromotionForm['ct2_campaign_id'] ?? 0) === (int) $ct2Campaign['ct2_campaign_id']) ? 'selected' : ''; ?>><?= htmlspecialchars((string) $ct2Campaign['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Promotion Code</label>
            <input class="ct2-input" name="promotion_code" required value="<?= htmlspecialchars((string) ($ct2PromotionForm['promotion_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Promotion Name</label>
            <input class="ct2-input" name="promotion_name" required value="<?= htmlspecialchars((string) ($ct2PromotionForm['promotion_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Promotion Type</label>
            <select class="ct2-select" name="promotion_type">
                <?php foreach (['percentage', 'fixed_amount', 'bundle', 'referral', 'loyalty', 'manual'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2PromotionForm['promotion_type'] ?? 'percentage') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Discount Value</label>
            <input class="ct2-input" name="discount_value" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($ct2PromotionForm['discount_value'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Valid From</label>
            <input class="ct2-input" name="valid_from" type="date" required value="<?= htmlspecialchars((string) ($ct2PromotionForm['valid_from'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Valid Until</label>
            <input class="ct2-input" name="valid_until" type="date" required value="<?= htmlspecialchars((string) ($ct2PromotionForm['valid_until'] ?? date('Y-m-d', strtotime('+14 days'))), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Usage Limit</label>
            <input class="ct2-input" name="usage_limit" type="number" min="1" value="<?= htmlspecialchars((string) ($ct2PromotionForm['usage_limit'] ?? '100'), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Promotion Status</label>
            <select class="ct2-select" name="promotion_status">
                <?php foreach (['draft', 'pending_approval', 'active', 'paused', 'expired', 'archived'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2PromotionForm['promotion_status'] ?? 'pending_approval') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Approval Status</label>
            <select class="ct2-select" name="approval_status">
                <?php foreach (['pending', 'approved', 'rejected'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2PromotionForm['approval_status'] ?? 'pending') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Eligibility Rule</label>
            <textarea class="ct2-textarea" name="eligibility_rule" rows="3"><?= htmlspecialchars((string) ($ct2PromotionForm['eligibility_rule'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            <label class="ct2-label">External Booking Scope</label>
            <input class="ct2-input" name="external_booking_scope" placeholder="package group or channel" value="<?= htmlspecialchars((string) ($ct2PromotionForm['external_booking_scope'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="ct1 or crm" value="<?= htmlspecialchars((string) ($ct2PromotionForm['source_system'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Promotion</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Voucher Register</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'saveVoucher']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Promotion</label>
            <select class="ct2-select" name="ct2_promotion_id">
                <option value="0">Standalone voucher</option>
                <?php foreach ($ct2PromotionSelection as $ct2Promotion): ?>
                    <option value="<?= (int) $ct2Promotion['ct2_promotion_id']; ?>"><?= htmlspecialchars((string) $ct2Promotion['promotion_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2Promotion['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Voucher Code</label>
            <input class="ct2-input" name="voucher_code" required>
            <label class="ct2-label">Voucher Name</label>
            <input class="ct2-input" name="voucher_name" required>
            <label class="ct2-label">Customer Scope</label>
            <select class="ct2-select" name="customer_scope">
                <?php foreach (['single_use', 'multi_use', 'affiliate', 'open'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Max Redemptions</label>
            <input class="ct2-input" name="max_redemptions" type="number" min="1" value="1">
            <label class="ct2-label">Voucher Status</label>
            <select class="ct2-select" name="voucher_status">
                <?php foreach (['issued', 'active', 'redeemed', 'expired', 'cancelled'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Valid From</label>
            <input class="ct2-input" name="valid_from" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" required>
            <label class="ct2-label">Valid Until</label>
            <input class="ct2-input" name="valid_until" type="date" value="<?= htmlspecialchars(date('Y-m-d', strtotime('+14 days')), ENT_QUOTES, 'UTF-8'); ?>" required>
            <label class="ct2-label">External Customer ID</label>
            <input class="ct2-input" name="external_customer_id" placeholder="optional targeted customer">
            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="ct1 or crm">
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Voucher</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3><?= $ct2AffiliateForm !== null ? 'Update Affiliate' : 'Affiliate Registry'; ?></h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'saveAffiliate']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form ct2-form-grid">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="ct2_affiliate_id" value="<?= (int) ($ct2AffiliateForm['ct2_affiliate_id'] ?? 0); ?>">
            <label class="ct2-label">Affiliate Code</label>
            <input class="ct2-input" name="affiliate_code" required value="<?= htmlspecialchars((string) ($ct2AffiliateForm['affiliate_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Affiliate Name</label>
            <input class="ct2-input" name="affiliate_name" required value="<?= htmlspecialchars((string) ($ct2AffiliateForm['affiliate_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Contact Name</label>
            <input class="ct2-input" name="contact_name" required value="<?= htmlspecialchars((string) ($ct2AffiliateForm['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Email</label>
            <input class="ct2-input" name="email" type="email" required value="<?= htmlspecialchars((string) ($ct2AffiliateForm['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Phone</label>
            <input class="ct2-input" name="phone" required value="<?= htmlspecialchars((string) ($ct2AffiliateForm['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Affiliate Status</label>
            <select class="ct2-select" name="affiliate_status">
                <?php foreach (['onboarding', 'active', 'paused', 'inactive'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2AffiliateForm['affiliate_status'] ?? 'onboarding') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Commission Rate (%)</label>
            <input class="ct2-input" name="commission_rate" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($ct2AffiliateForm['commission_rate'] ?? '5.00'), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Payout Status</label>
            <select class="ct2-select" name="payout_status">
                <?php foreach (['pending_setup', 'ready', 'hold'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2AffiliateForm['payout_status'] ?? 'pending_setup') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Referral Code</label>
            <input class="ct2-input" name="referral_code" required value="<?= htmlspecialchars((string) ($ct2AffiliateForm['referral_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">External Partner ID</label>
            <input class="ct2-input" name="external_partner_id" value="<?= htmlspecialchars((string) ($ct2AffiliateForm['external_partner_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="partner portal" value="<?= htmlspecialchars((string) ($ct2AffiliateForm['source_system'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Affiliate</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Referral Tracking</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'saveReferral']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Affiliate</label>
            <select class="ct2-select" name="ct2_affiliate_id" required>
                <option value="">Select affiliate</option>
                <?php foreach ($ct2AffiliateSelection as $ct2Affiliate): ?>
                    <option value="<?= (int) $ct2Affiliate['ct2_affiliate_id']; ?>"><?= htmlspecialchars((string) $ct2Affiliate['affiliate_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2Affiliate['referral_code'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Campaign</label>
            <select class="ct2-select" name="ct2_campaign_id">
                <option value="0">Optional campaign</option>
                <?php foreach ($ct2CampaignSelection as $ct2Campaign): ?>
                    <option value="<?= (int) $ct2Campaign['ct2_campaign_id']; ?>"><?= htmlspecialchars((string) $ct2Campaign['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Referral Code</label>
            <input class="ct2-input" name="referral_code" required>
            <label class="ct2-label">Click Date</label>
            <input class="ct2-input" name="click_date" type="datetime-local" required value="<?= htmlspecialchars(date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Landing Page</label>
            <input class="ct2-input" name="landing_page" placeholder="/promos/summer-sale">
            <label class="ct2-label">External Customer ID</label>
            <input class="ct2-input" name="external_customer_id">
            <label class="ct2-label">External Booking ID</label>
            <input class="ct2-input" name="external_booking_id">
            <label class="ct2-label">Attribution Status</label>
            <select class="ct2-select" name="attribution_status">
                <?php foreach (['clicked', 'qualified', 'booked', 'lost'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="web or crm">
            <button class="ct2-btn ct2-btn-primary" type="submit">Record Referral</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Redemption Register</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'saveRedemption']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Campaign</label>
            <select class="ct2-select" name="ct2_campaign_id">
                <option value="0">Optional campaign</option>
                <?php foreach ($ct2CampaignSelection as $ct2Campaign): ?>
                    <option value="<?= (int) $ct2Campaign['ct2_campaign_id']; ?>"><?= htmlspecialchars((string) $ct2Campaign['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Promotion</label>
            <select class="ct2-select" name="ct2_promotion_id">
                <option value="0">Optional promotion</option>
                <?php foreach ($ct2PromotionSelection as $ct2Promotion): ?>
                    <option value="<?= (int) $ct2Promotion['ct2_promotion_id']; ?>"><?= htmlspecialchars((string) $ct2Promotion['promotion_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Voucher</label>
            <select class="ct2-select" name="ct2_voucher_id">
                <option value="0">Optional voucher</option>
                <?php foreach ($ct2VoucherSelection as $ct2Voucher): ?>
                    <option value="<?= (int) $ct2Voucher['ct2_voucher_id']; ?>"><?= htmlspecialchars((string) $ct2Voucher['voucher_code'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2Voucher['voucher_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Redemption Date</label>
            <input class="ct2-input" name="redemption_date" type="datetime-local" required value="<?= htmlspecialchars(date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">External Customer ID</label>
            <input class="ct2-input" name="external_customer_id">
            <label class="ct2-label">External Booking ID</label>
            <input class="ct2-input" name="external_booking_id">
            <label class="ct2-label">Redeemed Amount</label>
            <input class="ct2-input" name="redeemed_amount" type="number" min="0" step="0.01" value="0.00">
            <label class="ct2-label">Redemption Status</label>
            <select class="ct2-select" name="redemption_status">
                <?php foreach (['pending', 'redeemed', 'reversed', 'expired'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="ct1 booking or pos">
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Redemption</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Campaign Metrics Input</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'saveMetric']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Campaign</label>
            <select class="ct2-select" name="ct2_campaign_id" required>
                <option value="">Select campaign</option>
                <?php foreach ($ct2CampaignSelection as $ct2Campaign): ?>
                    <option value="<?= (int) $ct2Campaign['ct2_campaign_id']; ?>"><?= htmlspecialchars((string) $ct2Campaign['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Report Date</label>
            <input class="ct2-input" name="report_date" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" required>
            <label class="ct2-label">Impressions</label>
            <input class="ct2-input" name="impressions_count" type="number" min="0" value="0">
            <label class="ct2-label">Clicks</label>
            <input class="ct2-input" name="click_count" type="number" min="0" value="0">
            <label class="ct2-label">Leads</label>
            <input class="ct2-input" name="lead_count" type="number" min="0" value="0">
            <label class="ct2-label">Conversions</label>
            <input class="ct2-input" name="conversion_count" type="number" min="0" value="0">
            <label class="ct2-label">Attributed Revenue</label>
            <input class="ct2-input" name="attributed_revenue" type="number" min="0" step="0.01" value="0.00">
            <label class="ct2-label">Positive Reviews</label>
            <input class="ct2-input" name="positive_reviews" type="number" min="0" value="0">
            <label class="ct2-label">Neutral Reviews</label>
            <input class="ct2-input" name="neutral_reviews" type="number" min="0" value="0">
            <label class="ct2-label">Negative Reviews</label>
            <input class="ct2-input" name="negative_reviews" type="number" min="0" value="0">
            <label class="ct2-label">External Review Batch ID</label>
            <input class="ct2-input" name="external_review_batch_id">
            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="analytics export">
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Metrics</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Marketing Notes</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'saveNote']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Campaign</label>
            <select class="ct2-select" name="ct2_campaign_id">
                <option value="0">Optional campaign</option>
                <?php foreach ($ct2CampaignSelection as $ct2Campaign): ?>
                    <option value="<?= (int) $ct2Campaign['ct2_campaign_id']; ?>"><?= htmlspecialchars((string) $ct2Campaign['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Affiliate</label>
            <select class="ct2-select" name="ct2_affiliate_id">
                <option value="0">Optional affiliate</option>
                <?php foreach ($ct2AffiliateSelection as $ct2Affiliate): ?>
                    <option value="<?= (int) $ct2Affiliate['ct2_affiliate_id']; ?>"><?= htmlspecialchars((string) $ct2Affiliate['affiliate_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Note Type</label>
            <select class="ct2-select" name="note_type">
                <?php foreach (['performance', 'partner_follow_up', 'review_summary', 'risk', 'handoff'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Title</label>
            <input class="ct2-input" name="note_title" required>
            <label class="ct2-label">Note Body</label>
            <textarea class="ct2-textarea" name="note_body" rows="4" required></textarea>
            <label class="ct2-label">Next Action Date</label>
            <input class="ct2-input" name="next_action_date" type="date">
            <button class="ct2-btn ct2-btn-primary" type="submit">Record Note</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <div class="ct2-section-header">
            <h3>Campaign Snapshot</h3>
            <span class="ct2-subtle">Customer and booking ownership remains external.</span>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Status</th>
                    <th>Channel</th>
                    <th>Promotions</th>
                    <th>Revenue</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Campaigns as $ct2Campaign): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Campaign['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Campaign['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Campaign['channel_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) $ct2Campaign['promotion_count']; ?></td>
                        <td><?= number_format((float) $ct2Campaign['attributed_revenue'], 2); ?></td>
                        <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'index', 'campaign_edit_id' => (int) $ct2Campaign['ct2_campaign_id']]), ENT_QUOTES, 'UTF-8'); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Campaigns === []): ?>
                    <tr><td colspan="6">No campaigns registered yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <h3>Top Campaign Performance</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Status</th>
                    <th>Conversions</th>
                    <th>Revenue</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2TopCampaigns as $ct2Campaign): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Campaign['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Campaign['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) $ct2Campaign['total_conversions']; ?></td>
                        <td><?= number_format((float) $ct2Campaign['attributed_revenue'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2TopCampaigns === []): ?>
                    <tr><td colspan="4">No campaign metrics recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Promotions and Vouchers</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Campaign</th>
                    <th>Status</th>
                    <th>Redemptions</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Promotions as $ct2Promotion): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Promotion['promotion_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Promotion['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Promotion['promotion_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) $ct2Promotion['redemption_count']; ?></td>
                        <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'index', 'promotion_edit_id' => (int) $ct2Promotion['ct2_promotion_id']]), ENT_QUOTES, 'UTF-8'); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Promotions === []): ?>
                    <tr><td colspan="5">No promotions configured yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Voucher</th>
                    <th>Status</th>
                    <th>Window</th>
                    <th>Usage</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Vouchers as $ct2Voucher): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Voucher['voucher_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Voucher['voucher_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Voucher['valid_from'], ENT_QUOTES, 'UTF-8'); ?> to <?= htmlspecialchars((string) $ct2Voucher['valid_until'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) $ct2Voucher['redeemed_count']; ?> / <?= (int) $ct2Voucher['max_redemptions']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Vouchers === []): ?>
                    <tr><td colspan="4">No vouchers issued yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <h3>Affiliate Network and Expiring Vouchers</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Status</th>
                    <th>Referral Code</th>
                    <th>Clicks</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Affiliates as $ct2Affiliate): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Affiliate['affiliate_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Affiliate['affiliate_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Affiliate['referral_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) $ct2Affiliate['total_clicks']; ?></td>
                        <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'index', 'affiliate_edit_id' => (int) $ct2Affiliate['ct2_affiliate_id']]), ENT_QUOTES, 'UTF-8'); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Affiliates === []): ?>
                    <tr><td colspan="5">No affiliates registered yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Voucher</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Usage</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2ExpiringVouchers as $ct2Voucher): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Voucher['voucher_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Voucher['valid_until'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Voucher['voucher_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) $ct2Voucher['redeemed_count']; ?> / <?= (int) $ct2Voucher['max_redemptions']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2ExpiringVouchers === []): ?>
                    <tr><td colspan="4">No vouchers expiring in the next 14 days.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Recent Referral and Redemption Activity</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Campaign</th>
                    <th>Status</th>
                    <th>Booking Ref</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2ReferralClicks, 0, 5) as $ct2Click): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Click['affiliate_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($ct2Click['campaign_name'] ?? 'Independent'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Click['attribution_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($ct2Click['external_booking_id'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2ReferralClicks === []): ?>
                    <tr><td colspan="4">No referral activity recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Voucher</th>
                    <th>Promotion</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2RedemptionLogs, 0, 5) as $ct2Redemption): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($ct2Redemption['voucher_code'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($ct2Redemption['promotion_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Redemption['redemption_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= number_format((float) $ct2Redemption['redeemed_amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2RedemptionLogs === []): ?>
                    <tr><td colspan="4">No redemptions recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <h3>Metrics and Notes</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Date</th>
                    <th>Conversions</th>
                    <th>Review Mix</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2CampaignMetrics, 0, 5) as $ct2Metric): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Metric['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Metric['report_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) $ct2Metric['conversion_count']; ?></td>
                        <td><?= (int) $ct2Metric['positive_reviews']; ?>/<?= (int) $ct2Metric['neutral_reviews']; ?>/<?= (int) $ct2Metric['negative_reviews']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2CampaignMetrics === []): ?>
                    <tr><td colspan="4">No metrics submitted yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Linked To</th>
                    <th>Next Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2MarketingNotes, 0, 5) as $ct2Note): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Note['note_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Note['note_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($ct2Note['campaign_name'] ?? $ct2Note['affiliate_name'] ?? 'General'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($ct2Note['next_action_date'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2MarketingNotes === []): ?>
                    <tr><td colspan="4">No marketing notes recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
