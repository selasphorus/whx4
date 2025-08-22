<?php

namespace atc\WHx4\Core\Contracts;

interface SubtypeFieldGroupInterface
{
    /** Base post type, e.g. 'group' */
    public function getPostType(): string;

    /** Subtype slug, e.g. 'employers' */
    public function getSubtypeSlug(): string;
}
