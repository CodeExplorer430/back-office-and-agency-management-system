<?php

declare(strict_types=1);

final class CT2_PlaceholderController extends CT2_BaseController
{
    public function show(): void
    {
        ct2_require_auth();

        $ct2ModuleKey = (string) ($_GET['feature'] ?? 'financial-reporting-analytics');
        $ct2Modules = [
            'marketing-promotions-management' => [
                'title' => 'Marketing and Promotions Management',
                'status' => 'Implemented',
                'summary' => 'Campaign operations, promotions, vouchers, affiliates, and reporting are now available in the live CT2 module.',
            ],
            'financial-reporting-analytics' => [
                'title' => 'Financial Reporting and Analytics',
                'status' => 'Scaffolded',
                'summary' => 'CT2 reporting endpoints will consume shared identifiers and external financial references instead of becoming system of record.',
            ],
            'document-visa-assistance' => [
                'title' => 'Document and Visa Assistance Module',
                'status' => 'Implemented',
                'summary' => 'Visa intake, checklist management, document metadata, payment references, and notification logging are now available in the live CT2 module.',
            ],
        ];

        if (!isset($ct2Modules[$ct2ModuleKey])) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $this->ct2Render(
            'placeholders/ct2_module',
            [
                'ct2Module' => $ct2Modules[$ct2ModuleKey],
                'ct2ModuleKey' => $ct2ModuleKey,
            ]
        );
    }
}
