<?php

declare(strict_types=1);

namespace atc\WHx4\Core\Contracts;

interface ShortcodeInterface
{
    public static function tag(): string;

    /** @param array<string,mixed> $atts */
    public function handle(array $atts, string $content = '', string $tag = ''): string;
}
