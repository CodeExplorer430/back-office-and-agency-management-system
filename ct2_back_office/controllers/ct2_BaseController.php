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
}
