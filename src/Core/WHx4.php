<?php

namespace atc\WHx4\Core;

use LogicException;
use atc\WHx4\Core\Contracts\PluginContext;

final class WHx4
{
    private static ?PluginContext $ctx = null;

    public static function setContext(PluginContext $ctx): void
    {
        self::$ctx = $ctx;
    }

    public static function ctx(): PluginContext
    {
        if (self::$ctx === null) {
            throw new LogicException('PluginContext not set. Call WHx4::setContext() during plugin boot.');
        }
        return self::$ctx;
    }

    private function __construct() {}
}
