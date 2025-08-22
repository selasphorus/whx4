<?php

namespace atc\WHx4\Core\Contracts;

interface SubtypeInterface
{
    public function getPostType(): string; // e.g. 'group'
    public function getSlug(): string;     // e.g. 'employers'
    public function getLabel(): string;    // e.g. 'Employers'
    public function getTermArgs(): array;  // optional extras for wp_insert_term()
}
