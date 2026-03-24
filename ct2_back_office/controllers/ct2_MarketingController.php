<?php

declare(strict_types=1);

final class CT2_MarketingController extends CT2_BaseController
{
    private CT2_MarketingCampaignModel $ct2CampaignModel;
    private CT2_PromotionModel $ct2PromotionModel;
    private CT2_VoucherModel $ct2VoucherModel;
    private CT2_AffiliateModel $ct2AffiliateModel;
    private CT2_ReferralClickModel $ct2ReferralClickModel;
    private CT2_RedemptionLogModel $ct2RedemptionLogModel;
    private CT2_CampaignMetricModel $ct2CampaignMetricModel;
    private CT2_MarketingNoteModel $ct2MarketingNoteModel;
    private CT2_ApprovalModel $ct2ApprovalModel;
    private CT2_AuditLogModel $ct2AuditLogModel;

    public function __construct()
    {
        $this->ct2CampaignModel = new CT2_MarketingCampaignModel();
        $this->ct2PromotionModel = new CT2_PromotionModel();
        $this->ct2VoucherModel = new CT2_VoucherModel();
        $this->ct2AffiliateModel = new CT2_AffiliateModel();
        $this->ct2ReferralClickModel = new CT2_ReferralClickModel();
        $this->ct2RedemptionLogModel = new CT2_RedemptionLogModel();
        $this->ct2CampaignMetricModel = new CT2_CampaignMetricModel();
        $this->ct2MarketingNoteModel = new CT2_MarketingNoteModel();
        $this->ct2ApprovalModel = new CT2_ApprovalModel();
        $this->ct2AuditLogModel = new CT2_AuditLogModel();
    }

    public function index(): void
    {
        ct2_require_permission('marketing.view');

        $ct2Search = trim((string) ($_GET['search'] ?? ''));
        $ct2CampaignStatus = trim((string) ($_GET['campaign_status'] ?? ''));
        $ct2ChannelType = trim((string) ($_GET['channel_type'] ?? ''));
        $ct2AffiliateStatus = trim((string) ($_GET['affiliate_status'] ?? ''));
        $ct2CampaignEditId = isset($_GET['campaign_edit_id']) ? (int) $_GET['campaign_edit_id'] : 0;
        $ct2PromotionEditId = isset($_GET['promotion_edit_id']) ? (int) $_GET['promotion_edit_id'] : 0;
        $ct2AffiliateEditId = isset($_GET['affiliate_edit_id']) ? (int) $_GET['affiliate_edit_id'] : 0;
        $ct2Campaigns = $this->ct2CampaignModel->getAll(
            $ct2Search !== '' ? $ct2Search : null,
            $ct2CampaignStatus !== '' ? $ct2CampaignStatus : null,
            $ct2ChannelType !== '' ? $ct2ChannelType : null
        );
        $ct2Promotions = $this->ct2PromotionModel->getAll();
        $ct2Vouchers = $this->ct2VoucherModel->getAll();
        $ct2Affiliates = $this->ct2AffiliateModel->getAll(
            $ct2Search !== '' ? $ct2Search : null,
            $ct2AffiliateStatus !== '' ? $ct2AffiliateStatus : null
        );
        $ct2ReferralClicks = $this->ct2ReferralClickModel->getAll();
        $ct2RedemptionLogs = $this->ct2RedemptionLogModel->getAll();
        $ct2CampaignMetrics = $this->ct2CampaignMetricModel->getAll();
        $ct2MarketingNotes = $this->ct2MarketingNoteModel->getAll();
        $ct2ActiveTab = $this->ct2ResolveTab(['campaigns', 'offers', 'affiliates', 'activity'], 'campaigns');

        $this->ct2Render(
            'marketing/ct2_index',
            [
                'ct2Campaigns' => $ct2Campaigns,
                'ct2Promotions' => $ct2Promotions,
                'ct2Vouchers' => $ct2Vouchers,
                'ct2Affiliates' => $ct2Affiliates,
                'ct2ReferralClicks' => $ct2ReferralClicks,
                'ct2RedemptionLogs' => $ct2RedemptionLogs,
                'ct2CampaignMetrics' => $ct2CampaignMetrics,
                'ct2MarketingNotes' => $ct2MarketingNotes,
                'ct2CampaignPages' => $this->ct2PaginateArray($ct2Campaigns, 'campaigns_page'),
                'ct2PromotionPages' => $this->ct2PaginateArray($ct2Promotions, 'promotions_page'),
                'ct2VoucherPages' => $this->ct2PaginateArray($ct2Vouchers, 'vouchers_page'),
                'ct2AffiliatePages' => $this->ct2PaginateArray($ct2Affiliates, 'affiliates_page'),
                'ct2ReferralPages' => $this->ct2PaginateArray($ct2ReferralClicks, 'referrals_page'),
                'ct2RedemptionPages' => $this->ct2PaginateArray($ct2RedemptionLogs, 'redemptions_page'),
                'ct2MetricPages' => $this->ct2PaginateArray($ct2CampaignMetrics, 'metrics_page'),
                'ct2NotePages' => $this->ct2PaginateArray($ct2MarketingNotes, 'notes_page'),
                'ct2ActiveTab' => $ct2ActiveTab,
                'ct2CampaignSummary' => $this->ct2CampaignModel->getSummaryCounts(),
                'ct2AffiliateSummary' => $this->ct2AffiliateModel->getSummaryCounts(),
                'ct2TopCampaigns' => $this->ct2CampaignModel->getTopCampaigns(),
                'ct2ExpiringVouchers' => $this->ct2VoucherModel->getExpiringSoon(),
                'ct2CampaignSelection' => $this->ct2CampaignModel->getAllForSelection(),
                'ct2PromotionSelection' => $this->ct2PromotionModel->getAllForSelection(),
                'ct2VoucherSelection' => $this->ct2VoucherModel->getAllForSelection(),
                'ct2AffiliateSelection' => $this->ct2AffiliateModel->getAllForSelection(),
                'ct2CampaignForEdit' => $ct2CampaignEditId > 0 ? $this->ct2CampaignModel->findById($ct2CampaignEditId) : null,
                'ct2PromotionForEdit' => $ct2PromotionEditId > 0 ? $this->ct2PromotionModel->findById($ct2PromotionEditId) : null,
                'ct2AffiliateForEdit' => $ct2AffiliateEditId > 0 ? $this->ct2AffiliateModel->findById($ct2AffiliateEditId) : null,
                'ct2Search' => $ct2Search,
                'ct2CampaignStatus' => $ct2CampaignStatus,
                'ct2ChannelType' => $ct2ChannelType,
                'ct2AffiliateStatus' => $ct2AffiliateStatus,
            ]
        );
    }

    public function saveCampaign(): void
    {
        ct2_require_permission('marketing.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = $this->ct2ValidateCampaignPayload($_POST);
        $ct2UserId = (int) ct2_current_user_id();
        $ct2CampaignId = isset($_POST['ct2_campaign_id']) ? (int) $_POST['ct2_campaign_id'] : 0;

        if ($ct2CampaignId > 0) {
            $this->ct2CampaignModel->update($ct2CampaignId, $ct2Payload, $ct2UserId);
            $ct2Action = 'marketing.campaign_update';
        } else {
            $ct2CampaignId = $this->ct2CampaignModel->create($ct2Payload, $ct2UserId);
            $ct2Action = 'marketing.campaign_create';
        }

        $this->ct2ApprovalModel->createOrRefreshRequest('campaign', $ct2CampaignId, $ct2UserId);
        $this->ct2AuditLogModel->recordAudit($ct2UserId, 'campaign', $ct2CampaignId, $ct2Action, $ct2Payload);

        ct2_flash('success', 'Marketing campaign saved successfully.');
        $this->ct2Redirect(['module' => 'marketing', 'action' => 'index', 'tab' => 'campaigns', 'campaign_edit_id' => $ct2CampaignId]);
    }

    public function savePromotion(): void
    {
        ct2_require_permission('marketing.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = $this->ct2ValidatePromotionPayload($_POST);
        $ct2UserId = (int) ct2_current_user_id();
        $ct2PromotionId = isset($_POST['ct2_promotion_id']) ? (int) $_POST['ct2_promotion_id'] : 0;

        if ($ct2PromotionId > 0) {
            $this->ct2PromotionModel->update($ct2PromotionId, $ct2Payload, $ct2UserId);
            $ct2Action = 'marketing.promotion_update';
        } else {
            $ct2PromotionId = $this->ct2PromotionModel->create($ct2Payload, $ct2UserId);
            $ct2Action = 'marketing.promotion_create';
        }

        $this->ct2ApprovalModel->createOrRefreshRequest('promotion', $ct2PromotionId, $ct2UserId);
        $this->ct2AuditLogModel->recordAudit($ct2UserId, 'promotion', $ct2PromotionId, $ct2Action, $ct2Payload);

        ct2_flash('success', 'Promotion saved successfully.');
        $this->ct2Redirect(['module' => 'marketing', 'action' => 'index', 'tab' => 'offers', 'promotion_edit_id' => $ct2PromotionId]);
    }

    public function saveVoucher(): void
    {
        ct2_require_permission('marketing.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_promotion_id' => (int) ($_POST['ct2_promotion_id'] ?? 0),
            'voucher_code' => trim((string) ($_POST['voucher_code'] ?? '')),
            'voucher_name' => trim((string) ($_POST['voucher_name'] ?? '')),
            'customer_scope' => (string) ($_POST['customer_scope'] ?? 'single_use'),
            'max_redemptions' => max(1, (int) ($_POST['max_redemptions'] ?? 1)),
            'voucher_status' => (string) ($_POST['voucher_status'] ?? 'issued'),
            'valid_from' => (string) ($_POST['valid_from'] ?? ''),
            'valid_until' => (string) ($_POST['valid_until'] ?? ''),
            'external_customer_id' => trim((string) ($_POST['external_customer_id'] ?? '')),
            'source_system' => trim((string) ($_POST['source_system'] ?? '')),
        ];

        foreach (['voucher_code', 'voucher_name', 'valid_from', 'valid_until'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required voucher field: ' . $ct2RequiredField);
            }
        }

        $ct2VoucherId = isset($_POST['ct2_voucher_id']) ? (int) $_POST['ct2_voucher_id'] : 0;
        if ($ct2VoucherId > 0) {
            $this->ct2VoucherModel->update($ct2VoucherId, $ct2Payload);
            $ct2Action = 'marketing.voucher_update';
        } else {
            $ct2VoucherId = $this->ct2VoucherModel->create($ct2Payload, (int) ct2_current_user_id());
            $ct2Action = 'marketing.voucher_create';
        }

        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'voucher', $ct2VoucherId, $ct2Action, $ct2Payload);

        ct2_flash('success', 'Voucher saved successfully.');
        $this->ct2Redirect(['module' => 'marketing', 'action' => 'index', 'tab' => 'offers']);
    }

    public function saveAffiliate(): void
    {
        ct2_require_permission('marketing.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = $this->ct2ValidateAffiliatePayload($_POST);
        $ct2UserId = (int) ct2_current_user_id();
        $ct2AffiliateId = isset($_POST['ct2_affiliate_id']) ? (int) $_POST['ct2_affiliate_id'] : 0;

        if ($ct2AffiliateId > 0) {
            $this->ct2AffiliateModel->update($ct2AffiliateId, $ct2Payload, $ct2UserId);
            $ct2Action = 'marketing.affiliate_update';
        } else {
            $ct2AffiliateId = $this->ct2AffiliateModel->create($ct2Payload, $ct2UserId);
            $ct2Action = 'marketing.affiliate_create';
        }

        $this->ct2AuditLogModel->recordAudit($ct2UserId, 'affiliate', $ct2AffiliateId, $ct2Action, $ct2Payload);

        ct2_flash('success', 'Affiliate profile saved successfully.');
        $this->ct2Redirect(['module' => 'marketing', 'action' => 'index', 'tab' => 'affiliates', 'affiliate_edit_id' => $ct2AffiliateId]);
    }

    public function saveReferral(): void
    {
        ct2_require_permission('marketing.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_affiliate_id' => (int) ($_POST['ct2_affiliate_id'] ?? 0),
            'ct2_campaign_id' => (int) ($_POST['ct2_campaign_id'] ?? 0),
            'referral_code' => trim((string) ($_POST['referral_code'] ?? '')),
            'click_date' => $this->ct2ResolveDateTimeInput($_POST, 'click_date'),
            'landing_page' => trim((string) ($_POST['landing_page'] ?? '')),
            'external_customer_id' => trim((string) ($_POST['external_customer_id'] ?? '')),
            'external_booking_id' => trim((string) ($_POST['external_booking_id'] ?? '')),
            'attribution_status' => (string) ($_POST['attribution_status'] ?? 'clicked'),
            'source_system' => trim((string) ($_POST['source_system'] ?? '')),
        ];

        if ($ct2Payload['ct2_affiliate_id'] < 1 || $ct2Payload['referral_code'] === '' || $ct2Payload['click_date'] === '') {
            throw new InvalidArgumentException('Referral tracking requires affiliate, referral code, and click date.');
        }

        $ct2ReferralId = $this->ct2ReferralClickModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'referral_click', $ct2ReferralId, 'marketing.referral_create', $ct2Payload);

        ct2_flash('success', 'Referral click recorded.');
        $this->ct2Redirect(['module' => 'marketing', 'action' => 'index', 'tab' => 'activity']);
    }

    public function saveRedemption(): void
    {
        ct2_require_permission('marketing.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_campaign_id' => (int) ($_POST['ct2_campaign_id'] ?? 0),
            'ct2_promotion_id' => (int) ($_POST['ct2_promotion_id'] ?? 0),
            'ct2_voucher_id' => (int) ($_POST['ct2_voucher_id'] ?? 0),
            'redemption_date' => $this->ct2ResolveDateTimeInput($_POST, 'redemption_date'),
            'external_customer_id' => trim((string) ($_POST['external_customer_id'] ?? '')),
            'external_booking_id' => trim((string) ($_POST['external_booking_id'] ?? '')),
            'redeemed_amount' => number_format((float) ($_POST['redeemed_amount'] ?? 0), 2, '.', ''),
            'redemption_status' => (string) ($_POST['redemption_status'] ?? 'pending'),
            'source_system' => trim((string) ($_POST['source_system'] ?? '')),
        ];

        if ($ct2Payload['redemption_date'] === '' || ($ct2Payload['ct2_promotion_id'] < 1 && $ct2Payload['ct2_voucher_id'] < 1)) {
            throw new InvalidArgumentException('Redemption logging requires a promotion or voucher, plus a redemption date.');
        }

        $ct2RedemptionId = $this->ct2RedemptionLogModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'redemption_log', $ct2RedemptionId, 'marketing.redemption_create', $ct2Payload);

        ct2_flash('success', 'Redemption recorded successfully.');
        $this->ct2Redirect(['module' => 'marketing', 'action' => 'index', 'tab' => 'activity']);
    }

    public function saveMetric(): void
    {
        ct2_require_permission('marketing.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_campaign_id' => (int) ($_POST['ct2_campaign_id'] ?? 0),
            'report_date' => (string) ($_POST['report_date'] ?? ''),
            'impressions_count' => max(0, (int) ($_POST['impressions_count'] ?? 0)),
            'click_count' => max(0, (int) ($_POST['click_count'] ?? 0)),
            'lead_count' => max(0, (int) ($_POST['lead_count'] ?? 0)),
            'conversion_count' => max(0, (int) ($_POST['conversion_count'] ?? 0)),
            'attributed_revenue' => number_format((float) ($_POST['attributed_revenue'] ?? 0), 2, '.', ''),
            'positive_reviews' => max(0, (int) ($_POST['positive_reviews'] ?? 0)),
            'neutral_reviews' => max(0, (int) ($_POST['neutral_reviews'] ?? 0)),
            'negative_reviews' => max(0, (int) ($_POST['negative_reviews'] ?? 0)),
            'external_review_batch_id' => trim((string) ($_POST['external_review_batch_id'] ?? '')),
            'source_system' => trim((string) ($_POST['source_system'] ?? '')),
        ];

        if ($ct2Payload['ct2_campaign_id'] < 1 || $ct2Payload['report_date'] === '') {
            throw new InvalidArgumentException('Campaign metrics require a campaign and report date.');
        }

        $ct2MetricId = $this->ct2CampaignMetricModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'campaign_metric', $ct2MetricId, 'marketing.metric_create', $ct2Payload);

        ct2_flash('success', 'Campaign metrics saved.');
        $this->ct2Redirect(['module' => 'marketing', 'action' => 'index', 'tab' => 'activity']);
    }

    public function saveNote(): void
    {
        ct2_require_permission('marketing.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_campaign_id' => (int) ($_POST['ct2_campaign_id'] ?? 0),
            'ct2_affiliate_id' => (int) ($_POST['ct2_affiliate_id'] ?? 0),
            'note_type' => (string) ($_POST['note_type'] ?? 'performance'),
            'note_title' => trim((string) ($_POST['note_title'] ?? '')),
            'note_body' => trim((string) ($_POST['note_body'] ?? '')),
            'next_action_date' => (string) ($_POST['next_action_date'] ?? ''),
        ];

        if (($ct2Payload['ct2_campaign_id'] < 1 && $ct2Payload['ct2_affiliate_id'] < 1) || $ct2Payload['note_title'] === '' || $ct2Payload['note_body'] === '') {
            throw new InvalidArgumentException('Marketing notes require a campaign or affiliate, plus title and note body.');
        }

        $ct2NoteId = $this->ct2MarketingNoteModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'marketing_note', $ct2NoteId, 'marketing.note_create', $ct2Payload);

        ct2_flash('success', 'Marketing note recorded.');
        $this->ct2Redirect(['module' => 'marketing', 'action' => 'index', 'tab' => 'activity']);
    }

    private function assertPostWithCsrf(): void
    {
        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            ct2_flash('error', 'Invalid request token.');
            $this->ct2Redirect(['module' => 'marketing', 'action' => 'index']);
        }
    }

    private function ct2ValidateCampaignPayload(array $ct2Input): array
    {
        $ct2Payload = [
            'campaign_code' => trim((string) ($ct2Input['campaign_code'] ?? '')),
            'campaign_name' => trim((string) ($ct2Input['campaign_name'] ?? '')),
            'campaign_type' => (string) ($ct2Input['campaign_type'] ?? 'other'),
            'channel_type' => (string) ($ct2Input['channel_type'] ?? 'hybrid'),
            'start_date' => (string) ($ct2Input['start_date'] ?? ''),
            'end_date' => (string) ($ct2Input['end_date'] ?? ''),
            'budget_amount' => number_format((float) ($ct2Input['budget_amount'] ?? 0), 2, '.', ''),
            'status' => (string) ($ct2Input['status'] ?? 'pending_approval'),
            'approval_status' => (string) ($ct2Input['approval_status'] ?? 'pending'),
            'target_audience' => trim((string) ($ct2Input['target_audience'] ?? '')),
            'external_customer_segment_id' => trim((string) ($ct2Input['external_customer_segment_id'] ?? '')),
            'source_system' => trim((string) ($ct2Input['source_system'] ?? '')),
        ];

        foreach (['campaign_code', 'campaign_name', 'start_date', 'end_date'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required campaign field: ' . $ct2RequiredField);
            }
        }

        if ($ct2Payload['end_date'] < $ct2Payload['start_date']) {
            throw new InvalidArgumentException('Campaign end date cannot be earlier than the start date.');
        }

        return $ct2Payload;
    }

    private function ct2ValidatePromotionPayload(array $ct2Input): array
    {
        $ct2Payload = [
            'ct2_campaign_id' => (int) ($ct2Input['ct2_campaign_id'] ?? 0),
            'promotion_code' => trim((string) ($ct2Input['promotion_code'] ?? '')),
            'promotion_name' => trim((string) ($ct2Input['promotion_name'] ?? '')),
            'promotion_type' => (string) ($ct2Input['promotion_type'] ?? 'percentage'),
            'discount_value' => number_format((float) ($ct2Input['discount_value'] ?? 0), 2, '.', ''),
            'eligibility_rule' => trim((string) ($ct2Input['eligibility_rule'] ?? '')),
            'valid_from' => (string) ($ct2Input['valid_from'] ?? ''),
            'valid_until' => (string) ($ct2Input['valid_until'] ?? ''),
            'usage_limit' => max(1, (int) ($ct2Input['usage_limit'] ?? 1)),
            'promotion_status' => (string) ($ct2Input['promotion_status'] ?? 'pending_approval'),
            'approval_status' => (string) ($ct2Input['approval_status'] ?? 'pending'),
            'external_booking_scope' => trim((string) ($ct2Input['external_booking_scope'] ?? '')),
            'source_system' => trim((string) ($ct2Input['source_system'] ?? '')),
        ];

        if ($ct2Payload['ct2_campaign_id'] < 1) {
            throw new InvalidArgumentException('Promotions must be linked to a campaign.');
        }

        foreach (['promotion_code', 'promotion_name', 'valid_from', 'valid_until'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required promotion field: ' . $ct2RequiredField);
            }
        }

        if ($ct2Payload['valid_until'] < $ct2Payload['valid_from']) {
            throw new InvalidArgumentException('Promotion end date cannot be earlier than the start date.');
        }

        return $ct2Payload;
    }

    private function ct2ValidateAffiliatePayload(array $ct2Input): array
    {
        $ct2Payload = [
            'affiliate_code' => trim((string) ($ct2Input['affiliate_code'] ?? '')),
            'affiliate_name' => trim((string) ($ct2Input['affiliate_name'] ?? '')),
            'contact_name' => trim((string) ($ct2Input['contact_name'] ?? '')),
            'email' => trim((string) ($ct2Input['email'] ?? '')),
            'phone' => trim((string) ($ct2Input['phone'] ?? '')),
            'affiliate_status' => (string) ($ct2Input['affiliate_status'] ?? 'onboarding'),
            'commission_rate' => number_format((float) ($ct2Input['commission_rate'] ?? 0), 2, '.', ''),
            'payout_status' => (string) ($ct2Input['payout_status'] ?? 'pending_setup'),
            'referral_code' => trim((string) ($ct2Input['referral_code'] ?? '')),
            'external_partner_id' => trim((string) ($ct2Input['external_partner_id'] ?? '')),
            'source_system' => trim((string) ($ct2Input['source_system'] ?? '')),
        ];

        foreach (['affiliate_code', 'affiliate_name', 'contact_name', 'email', 'phone', 'referral_code'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required affiliate field: ' . $ct2RequiredField);
            }
        }

        return $ct2Payload;
    }
}
