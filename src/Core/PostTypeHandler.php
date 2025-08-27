<?php

namespace atc\WHx4\Core;

use WP_Post;
use atc\WHx4\Core\BaseHandler;
use atc\WHx4\Core\Traits\AppliesTitleArgs;

abstract class PostTypeHandler extends BaseHandler
{
	use AppliesTitleArgs;

	// Property to store the post object
    protected $post; // better private?
    protected const TYPE = 'post_type';

    // Constructor to set the config and post object

    public function __construct( array $config = [], WP_Post|null $post = null )
    {
        parent::__construct( $config, $post );
    }

    public function boot(): void
	{
		// Optional: common setup logic for all post types can go here
	}

    public function getCapType(): array {
        $capType = $this->getConfig()['capability_type'] ?? [];
        if ( empty($capType) ) { $capType = [ $this->getSlug(), $this->getPluralSlug() ]; } else if ( !is_array($capType) ) { $capType = [$capType, "{$capType}s" ]; };
        return $capType;
        //return $this->getConfig()['capability_type'] ?? [ $this->getSlug(), $this->getPluralSlug() ];
    }

    public function getSupports(): array {
        return $this->getConfig()['supports'] ?? [ 'title', 'editor' ];
    }

    public function getTaxonomies(): array {
        //$taxonomies = $this->getConfig()['taxonomies'] ?? [ 'admin_tag' => 'AdminTag' ];
        return $this->getConfig()['taxonomies'] ?? [ 'admin_tag' ];
        // WIP 08/26/25 -- turn this into an array of slug -> className pairs
        //return $taxonomies;
        //// WIP 08/26/25 -- figure out how to get fqcn for bare class names

        // Wherever you attach/ensure taxonomies, resolve them:
        //$taxonomyClasses = $this->resolveTaxonomyClasses($this->getConfig('taxonomies') ?? []);
        // Example: hand them to your registrar, or call static register() if you use handlers.
        // $this->taxonomyRegistrar->ensureRegistered($taxonomyClasses);

        //return $this->getConfig()['taxonomies'] ?? [ 'admin_tag' => 'AdminTag' ];
        //return $taxonomyClasses;
    }

    public function getMenuIcon(): ?string {
        return $this->getConfig()['menu_icon'] ?? 'dashicons-superhero';
    }

    ////

    // Method to get the post ID
    public function getPostID()
    {
        return $this->post->ID;
    }

    // Method to get the post title
    public function get_post_title()
    {
        return get_the_title($this->getPostID());
    }

	public function getCustomContent()
	{
		$post_id = $this->getPostID();

		// This function retrieves supplementary info -- the regular content template (content.php) handles title, content, featured image

		// Init
		$info = "";
		$ts_info = "";

		$ts_info .= "post_id: ".$post_id."<br />";

		if ( $post_id === null ) { return false; }

		$info .= $ts_info;

		return $info;

	}



    // WIP 08/27/25 -- the following three functions may be better placed in some other class, TBD

    /**
     * @param array|string $taxonomies Short names like 'habitat', or FQCNs, or 'Module:habitat'.
     * @return string[] FQCNs
     */
    protected function resolveTaxonomyClasses(array|string $taxonomies): array
    {
        $taxonomies = is_array($taxonomies) ? $taxonomies : [ $taxonomies ];
        $resolved   = [];

        foreach ($taxonomies as $t) {
            $t = trim((string) $t);
            if ($t === '') {
                continue;
            }
            $resolved[] = $this->resolveTaxonomyFqcn($t);
        }

        return array_values(array_unique($resolved));
    }

    // TODO: generalize
    protected function resolveTaxonomyFqcn(string $name): string
    {
        // Already an FQCN?
        if (str_contains($name, '\\')) {
            return ltrim($name, '\\');
        }

        // Extract root prefix up to "Modules\"
        // e.g. atc\WHx4\Modules\Supernatural\PostTypes\Monster
        // -> prefix: atc\WHx4\Modules\, currentModule: Supernatural
        $class = static::class;
        if (!preg_match('/^(.*\\\\Modules\\\\)([^\\\\]+)/', $class, $m)) {
            // Fallback: just StudlyCase in current namespace root (unlikely)
            return $this->studly($name);
        }
        $modulesPrefix = $m[1]; // "atc\WHx4\Modules\"
        $currentModule = $m[2]; // "Supernatural"

        // Optional "Module:basename" syntax
        $targetModule = $currentModule;
        $basename     = $name;
        if (str_contains($name, ':')) {
            [ $targetModule, $basename ] = array_map('trim', explode(':', $name, 2));
            if ($targetModule === '') {
                $targetModule = $currentModule;
            }
        }

        // Build FQCN: <prefix><Module>\Taxonomies\<Studly>
        // TODO: generalize for classes other than Taxonomies by replacing hardcoded '\\Taxonomies\\' with another var
        return $modulesPrefix . $targetModule . '\\Taxonomies\\' . $this->studly($basename);
    }

    // Translate slug to studly caps to match class naming conventions
    private function studly(string $value): string
    {
        // "habitat" -> "Habitat", "event_tag" -> "EventTag", "event-tag" -> "EventTag"
        $value = str_replace([ '-', '_' ], ' ', $value);
        $value = ucwords($value);
        return str_replace(' ', '', $value);
    }
}

