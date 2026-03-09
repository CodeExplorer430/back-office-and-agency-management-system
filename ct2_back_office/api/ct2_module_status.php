<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

$ct2Modules = [
    [
        'module_key' => 'travel-agent-and-staff-management',
        'title' => 'Travel Agent and Staff Management',
        'status' => 'implemented',
        'routes' => [
            ct2_app_url(['module' => 'agents', 'action' => 'index']),
            ct2_app_url(['module' => 'staff', 'action' => 'index']),
            ct2_app_url(['module' => 'approvals', 'action' => 'index']),
        ],
    ],
    [
        'module_key' => 'supplier-partner-management',
        'title' => 'Supplier and Partner Management',
        'status' => 'implemented',
        'routes' => [ct2_app_url(['module' => 'suppliers', 'action' => 'index'])],
    ],
    [
        'module_key' => 'tour-availability-resource-planning',
        'title' => 'Tour Availability and Resource Planning',
        'status' => 'implemented',
        'routes' => [ct2_app_url(['module' => 'availability', 'action' => 'index'])],
    ],
    [
        'module_key' => 'marketing-promotions-management',
        'title' => 'Marketing and Promotions Management',
        'status' => 'implemented',
        'routes' => [ct2_app_url(['module' => 'marketing', 'action' => 'index'])],
    ],
    [
        'module_key' => 'financial-reporting-analytics',
        'title' => 'Financial Reporting and Analytics',
        'status' => 'scaffolded',
        'routes' => [ct2_app_url(['module' => 'placeholders', 'action' => 'show', 'feature' => 'financial-reporting-analytics'])],
    ],
    [
        'module_key' => 'document-visa-assistance',
        'title' => 'Document and Visa Assistance Module',
        'status' => 'implemented',
        'routes' => [ct2_app_url(['module' => 'visa', 'action' => 'index'])],
    ],
];

ct2_record_api_log('ct2_module_status', 'GET', 200, [], ['count' => count($ct2Modules)]);
ct2_json_response(true, ['modules' => $ct2Modules], null, 200);
