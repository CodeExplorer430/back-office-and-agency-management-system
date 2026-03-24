<?php

declare(strict_types=1);

abstract class CT2_BaseController
{
    protected function ct2Render(string $ct2View, array $ct2Data = []): void
    {
        ct2_render($ct2View, $ct2Data);
    }

    protected function ct2Redirect(array $ct2Parameters = []): void
    {
        ct2_redirect($ct2Parameters);
    }

    protected function ct2ResolveTab(array $ct2AllowedTabs, string $ct2DefaultTab = 'overview', string $ct2Param = 'tab'): string
    {
        $ct2RequestedTab = trim((string) ($_GET[$ct2Param] ?? ''));

        if ($ct2RequestedTab !== '' && in_array($ct2RequestedTab, $ct2AllowedTabs, true)) {
            return $ct2RequestedTab;
        }

        return $ct2DefaultTab;
    }

    protected function ct2PaginateArray(array $ct2Records, string $ct2PageParam, int $ct2PerPage = 10): array
    {
        $ct2Page = max(1, (int) ($_GET[$ct2PageParam] ?? 1));
        $ct2TotalRecords = count($ct2Records);
        $ct2TotalPages = max(1, (int) ceil($ct2TotalRecords / max(1, $ct2PerPage)));
        $ct2Page = min($ct2Page, $ct2TotalPages);
        $ct2Offset = ($ct2Page - 1) * $ct2PerPage;

        return [
            'records' => array_slice($ct2Records, $ct2Offset, $ct2PerPage),
            'page' => $ct2Page,
            'page_param' => $ct2PageParam,
            'per_page' => $ct2PerPage,
            'total_pages' => $ct2TotalPages,
            'total_records' => $ct2TotalRecords,
        ];
    }

    protected function ct2CombineDateAndTime(?string $ct2Date, ?string $ct2Time): string
    {
        $ct2Date = trim((string) $ct2Date);
        $ct2Time = trim((string) $ct2Time);

        if ($ct2Date === '' || $ct2Time === '') {
            return '';
        }

        $ct2NormalizedTime = preg_match('/^\d{2}:\d{2}$/', $ct2Time) === 1
            ? $ct2Time . ':00'
            : $ct2Time;

        $ct2Timestamp = strtotime($ct2Date . ' ' . $ct2NormalizedTime);

        return $ct2Timestamp !== false ? date('Y-m-d H:i:s', $ct2Timestamp) : '';
    }

    protected function ct2ResolveDateTimeInput(array $ct2Input, string $ct2Field): string
    {
        $ct2SplitDate = trim((string) ($ct2Input[$ct2Field . '_date'] ?? ''));
        $ct2SplitTime = trim((string) ($ct2Input[$ct2Field . '_time'] ?? ''));
        if ($ct2SplitDate !== '' || $ct2SplitTime !== '') {
            return $this->ct2CombineDateAndTime($ct2SplitDate, $ct2SplitTime);
        }

        $ct2LegacyValue = trim((string) ($ct2Input[$ct2Field] ?? ''));
        if ($ct2LegacyValue !== '') {
            $ct2Timestamp = strtotime(str_replace('T', ' ', $ct2LegacyValue));

            return $ct2Timestamp !== false ? date('Y-m-d H:i:s', $ct2Timestamp) : '';
        }

        return '';
    }
}
