<?php

namespace atc\WHx4\Core;

use atc\WHx4\Plugin;

class SettingsManager
{
    public function getOption(): array
	{
		$saved = get_option( 'whx4_plugin_settings', [] );

		$defaults = [
			'active_modules'     => [],
			'enabled_post_types' => [],
			'enabled_taxonomies' => [],
		];

		return array_merge( $defaults, $saved );
	}

	public function getActiveModuleSlugs(): array
	{
		return $this->getOption()['active_modules'];
	}

	public function getEnabledPostTypeSlugs(): array
	{
		return $this->getOption()[ 'enabled_post_types' ];
	}

	//public function getEnabledTaxonomySlugs(): array { ... }

}
