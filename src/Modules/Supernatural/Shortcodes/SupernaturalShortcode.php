<?php

namespace atc\WHx4\Modules\Supernatural\Shortcodes;

use atc\WHx4\Core\Contracts\ShortcodeInterface;
use atc\WHx4\Core\WHx4;
use atc\WHx4\Utils\ClassInfo;

final class SupernaturalShortcode implements ShortcodeInterface
{
    public static function tag(): string
    {
        return 'whoa_supernatural';
    }

    public function render(array $atts = [], string $content = '', string $tag = ''): string
    {
        $ctx = WHx4::ctx();
        $key = ClassInfo::getModuleKey(self::class); // 'supernatural'
        $module = $ctx->getModule($key);

        if (!$module) {
            return '<p>Supernatural module inactive.</p>';
        }

        $stats = $module->getModuleStats();

        ob_start(); ?>
        <div class="whx4-supernatural">
            <p><strong>Monsters:</strong> <?php echo (int)$stats['monsters']; ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}
