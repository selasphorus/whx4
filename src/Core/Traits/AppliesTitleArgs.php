<?php

namespace atc\WHx4\Core\Traits;

use atc\WHx4\Utils\TitleFilter;

trait AppliesTitleArgs
{
    protected function applyTitleArgs( string $postType, array $args ): void
    {
        TitleFilter::setGlobalArgsForPostType( $postType, $args );
    }
}
