<?php

declare(strict_types=1);

final class CT2_PlaceholderController extends CT2_BaseController
{
    public function show(): void
    {
        ct2_require_auth();

        $ct2ModuleKey = (string) ($_GET['feature'] ?? 'supplier-partner-management');
        $ct2Modules = [
            'tour-availability-resource-planning' => [
                'title' => 'Tour Availability and Resource Planning',
                'status' => 'Scaffolded',
                'summary' => 'Shared CT2 references are reserved for inventory and availability orchestration without taking ownership from CT1.',
            ],
            'marketing-promotions-management' => [
                'title' => 'Marketing and Promotions Management',
                'status' => 'Scaffolded',
                'summary' => 'Campaign, discount, and promotion integration seams are defined as placeholders for future implementation.',
            ],
            'financial-reporting-analytics' => [
                'title' => 'Financial Reporting and Analytics',
                'status' => 'Scaffolded',
                'summary' => 'CT2 reporting endpoints will consume shared identifiers and external financial references instead of becoming system of record.',
            ],
            'document-visa-assistance' => [
                'title' => 'Document and Visa Assistance Module',
                'status' => 'Scaffolded',
                'summary' => 'Document indexing, application tracking, and compliance adapter seams are reserved for the next delivery wave.',
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
