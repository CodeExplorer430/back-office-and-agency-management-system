<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_marketing_reports', 'marketing.view', 'marketing.view');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    ct2_record_api_log('ct2_marketing_reports', $_SERVER['REQUEST_METHOD'] ?? 'GET', 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2CampaignModel = new CT2_MarketingCampaignModel();
$ct2AffiliateModel = new CT2_AffiliateModel();
$ct2VoucherModel = new CT2_VoucherModel();
$ct2ReferralClickModel = new CT2_ReferralClickModel();
$ct2RedemptionLogModel = new CT2_RedemptionLogModel();

$ct2Data = [
    'campaign_summary' => $ct2CampaignModel->getSummaryCounts(),
    'affiliate_summary' => $ct2AffiliateModel->getSummaryCounts(),
    'top_campaigns' => $ct2CampaignModel->getTopCampaigns(),
    'expiring_vouchers' => $ct2VoucherModel->getExpiringSoon(),
    'recent_referrals' => array_slice($ct2ReferralClickModel->getAll(), 0, 10),
    'recent_redemptions' => array_slice($ct2RedemptionLogModel->getAll(), 0, 10),
];

ct2_record_api_log('ct2_marketing_reports', 'GET', 200, [], ['keys' => array_keys($ct2Data)]);
ct2_json_response(true, $ct2Data, null, 200);
