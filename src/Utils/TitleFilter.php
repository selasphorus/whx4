<?php

namespace atc\WHx4\Utils;

class TitleFilter
{
    protected static array $globalArgsByPostType = [];
    protected static array $contextArgs = [];

    public static function boot(): void
    {
        add_filter( 'the_title', [ self::class, 'filterTitle' ], 10, 2 );
    }

    public static function setGlobalArgsForPostType( string $post_type, array $args ): void
    {
        self::$globalArgsByPostType[ $post_type ] = $args;
    }

    public static function filterTitle( string $title, $postId ): string
    {
        if ( is_admin() ) { return $title; }

        $result = self::normalizeTitleArgs( [ 'post' => $postId ] );
        $args   = $result['args'];
        $post   = $result['post'];

        if ( ! $post instanceof \WP_Post ) {
            return $title;
        }

        if ( isset( self::$contextArgs[ $post_id ] ) ) {
            $args = array_merge( $args, self::$contextArgs[ $post_id ] );
        }

        $args = array_merge(
            [
                'post' => $postId,
                'echo' => false,
            ],
            $args
        );

        // Apply line breaks for certain call contexts
        if ( $args['line_breaks'] ) {
            $title = str_replace( ': ', ":<br>", $title );
        }

        // Append subtitle if enabled and supported
        if ( $args['show_subtitle'] ) {
            $subtitle = get_post_meta( $postId, 'subtitle', true );
            if ( $subtitle ) {
                $title .= sprintf(
                    '<div class="post-subtitle h%d">%s</div>',
                    (int) $args['hlevel_sub'],
                    esc_html( $subtitle )
                );
            }
        }

        return $title;
    }

    /**
     * Returns default arguments for post title rendering,
     * based on post type (if provided).
     */
    protected function getDefaultTitleArgs( ?string $postType = null ): array
    {
        $defaults = [
            'line_breaks'    => false,
            'show_subtitle'  => false,
            'hlevel_sub'     => 3,
            'called_by'      => 'default',
            'echo'           => false,
        ];

        // TODO: move this to appropriate spot in Supernatural module and so on
        if ( $postType === 'monster' ) {
            $defaults['show_subtitle'] = true;
            $defaults['hlevel_sub']    = 4;
        }

        return $defaults;
    }

    /**
     * Normalizes the title args array by merging with type-specific defaults.
     */
    public static function normalizeTitleArgs( array $args ): array
    {
        $postId   = $args['post'] ?? null;
        $post     = $postId && is_numeric( $postId ) ? get_post( $postId ) : null;
        $postType = $post instanceof \WP_Post ? $post->post_type : null;

        $defaults = self::getDefaultTitleArgs( $postType );
        $globals  = $postType && isset( self::$globalArgsByPostType[ $postType ] )
            ? self::$globalArgsByPostType[ $postType ]
            : [];

        return [
            'args' => wp_parse_args( $args, $globals + $defaults ),
            'post' => $post,
        ];
    }


    public static function setArgsForPost( int $post_id, array $args ): void
    {
        self::$contextArgs[ $post_id ] = $args;
    }
}
