<?php
/**
 * Interface for all WHx4 ACF Field Groups.
 *
 * Developers: Please follow WHx4 Field Group standards.
 * See: /docs/FieldGroupStandards.md
 */

namespace atc\WHx4\Core\Contracts;

interface FieldGroupInterface
{
    public static function register(): void;
    //public static function getPostTypes(): array;
}
