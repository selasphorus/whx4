<?php

namespace atc\WHx4\Core\Traits;

use atc\WHx4\Utils\TitleFilter;

trait AppliesTitleArgs
{
    protected function applyTitleArgs( string $postType, array $args ): void
    {
        error_log("=== trait AppliesTitleArgs: applyTitleArgs ===");
        TitleFilter::setGlobalArgsForPostType( $postType, $args );
    }
    /*protected function applyTitleArgs( string $post_type, array $args ): void
    {
        if ( method_exists( $this, 'isPostTypeEnabled' ) && $this->isPostTypeEnabled( $post_type ) ) {
            TitleFilter::setGlobalArgsForPostType( $post_type, $args );
        }
    }*/
}
