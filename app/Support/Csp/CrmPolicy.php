<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

class CrmPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, [
                Keyword::SELF,
                'https://www.google.com',
                'https://www.gstatic.com',
                'https://static.cloudflareinsights.com',
            ])
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FONT, [
                Keyword::SELF,
                'https://fonts.gstatic.com',
                'data:',
            ])
            ->add(Directive::FRAME, [
                Keyword::SELF,
                'https://www.google.com',
                'https://www.gstatic.com',
            ])
            ->add(Directive::IMG, [
                Keyword::SELF,
                'https:',
                'data:',
            ])
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, [
                Keyword::SELF,
                Keyword::UNSAFE_INLINE,
                Keyword::UNSAFE_EVAL,
                'https://www.google.com',
                'https://www.gstatic.com',
                'https://unpkg.com',
                'https://static.cloudflareinsights.com',
            ])
            ->add(Directive::STYLE, [
                Keyword::SELF,
                Keyword::UNSAFE_INLINE,
                'https://fonts.googleapis.com',
                'https://unpkg.com',
            ]);
    }
}
