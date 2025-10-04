<?php

namespace atc\WHx4\Modules\Supernatural\Shortcodes;

use atc\WHx4\Core\Contracts\ShortcodeInterface;
use atc\WHx4\Core\Shortcodes\ShortcodeBase;
use atc\WHx4\WHx4;
use atc\WHx4\Utils\ClassInfo;

final class SupernaturalShortcode implements ShortcodeInterface
{
    public function getTag(): string
    {
        return 'whoa_supernatural';
    }

    public function render(array $atts = [], ?string $content = null): string
    {
        $ctx = WHx4::ctx();
        $key    = ClassInfo::getModuleKey(self::class);   // 'supernatural'
        $module = Rex::ctx()->getModule($key);
        //$module = $ctx->getModule('supernatural'); // get active module instance

        if (!$module) {
            return '<p>Supernatural module inactive.</p>';
        }

        //$name  = $module->getRandomCreatureName();
        $stats = $module->getModuleStats();

        ob_start(); ?>
        <div class="rex-supernatural">
            <!--p><strong>Random Creature:</strong> <?= esc_html($name) ?></p-->
            <p><strong>Monsters:</strong> <?= (int)$stats['monsters'] ?></p>
            <!--p><strong>Enchanters:</strong> <?= (int)$stats['enchanters'] ?></p-->
        </div>
        <?php
        return ob_get_clean();
    }
}
