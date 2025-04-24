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

    public static function setArgsForPost( int $post_id, array $args ): void
    {
        self::$contextArgs[ $post_id ] = $args;
    }

    public static function filterTitle( string $title, $post_id ): string
    {
        if ( is_admin() ) {
            return $title;
        }

        $post = get_post( $post_id );
        if ( ! $post instanceof \WP_Post ) {
            return $title;
        }

        $post_type = $post->post_type;
        $args = self::$globalArgsByPostType[ $post_type ] ?? [];

        if ( isset( self::$contextArgs[ $post_id ] ) ) {
            $args = array_merge( $args, self::$contextArgs[ $post_id ] );
        }

        $args = array_merge(
            [
                'post' => $post_id,
                'echo' => false,
            ],
            $args
        );

        return special_post_title( $args ) ?: $title;
    }
}
