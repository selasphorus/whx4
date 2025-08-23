<?php

namespace atc\WHx4\Core\Contracts;

interface PostTypeFieldGroupInterface
{
    /** Base post type, e.g. 'group' */
    public function getPostType(): string;
}
