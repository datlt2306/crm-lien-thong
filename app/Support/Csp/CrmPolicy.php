<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Policies\Basic;

class CrmPolicy extends Basic
{
    public function configure()
    {
        parent::configure();

        $this
            ->addDirective(Directive::SCRIPT, [
                'self',
                'unsafe-inline',
                'unsafe-eval',
                'https://www.google.com',
                'https://www.gstatic.com',
            ])
            ->addDirective(Directive::STYLE, [
                'self',
                'unsafe-inline',
                'https://fonts.googleapis.com',
            ])
            ->addDirective(Directive::FONT, [
                'self',
                'https://fonts.gstatic.com',
            ])
            ->addDirective(Directive::IMG, [
                'self',
                'data:',
                'https:',
            ])
            ->addDirective(Directive::FRAME, [
                'self',
                'https://www.google.com',
            ])
            ->addDirective(Directive::CONNECT, [
                'self',
                'https://www.google.com',
                'https://www.gstatic.com',
            ]);
    }
}
