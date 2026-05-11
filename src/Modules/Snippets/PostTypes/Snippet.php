<?php

namespace atc\WHx4\Modules\Snippets\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;
use atc\WXC\Logger;

class Snippet extends PostTypeHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'             => 'snippet',
            'supports'         => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
        ];
    }

	public function boot(): void
	{
	    parent::boot(); // Optional if you add shared logic later
	}
}

