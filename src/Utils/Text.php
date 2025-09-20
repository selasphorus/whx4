<?php

declare(strict_types=1);

namespace atc\WHx4\Utils;

/**
 * Generic string utilities for WHx4.
 */
final class Text
{
    // Normalize a slug-like string by trimming whitespace and converting to lowercase
    public static function slugify(string $value): string
    {
        return strtolower(trim($value));
    }

    // Translate string to studly caps to match class naming conventions
    // e.g. "habitat" -> "Habitat", "event_tag" -> "EventTag", "event-tag" -> "EventTag"
    public static function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    public static function camel(string $value): string
    {
        return lcfirst(self::studly($value));
    }

    public static function kebab(string $value): string
    {
        return strtolower(preg_replace('/[A-Z]/', '-$0', lcfirst($value)));
    }
}
