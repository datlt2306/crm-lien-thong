<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policies\Policy;

class CrmPolicy extends Policy
{
    public function configure()
    {
        $this
            ->addDirective(Directive::BASE, Keyword::SELF)
            ->addDirective(Directive::CONNECT, [
                Keyword::SELF,
                'https://www.google.com',
                'https://www.gstatic.com',
                'https://static.cloudflareinsights.com',
            ])
            ->addDirective(Directive::DEFAULT, Keyword::SELF)
            ->addDirective(Directive::FONT, [
                Keyword::SELF,
                'https://fonts.gstatic.com',
                'data:',
            ])
            ->addDirective(Directive::FRAME, [
                Keyword::SELF,
                'https://www.google.com',
                'https://www.gstatic.com',
            ])
            ->addDirective(Directive::IMG, [
                Keyword::SELF,
                'https:',
                'data:',
            ])
            ->addDirective(Directive::MEDIA, Keyword::SELF)
            ->addDirective(Directive::OBJECT, Keyword::NONE)
            ->addDirective(Directive::SCRIPT, [
                Keyword::SELF,
                Keyword::UNSAFE_INLINE,
                Keyword::UNSAFE_EVAL,
                'https://www.google.com',
                'https://www.gstatic.com',
                'https://unpkg.com',
                'https://static.cloudflareinsights.com',
            ])
            ->addDirective(Directive::STYLE, [
                Keyword::SELF,
                Keyword::UNSAFE_INLINE,
                'https://fonts.googleapis.com',
                'https://unpkg.com',
            ]);
    }
}
