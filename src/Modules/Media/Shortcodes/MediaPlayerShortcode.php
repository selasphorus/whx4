<?php

declare(strict_types=1);

namespace atc\WHx4\Modules\Media\Shortcodes;

use atc\WXC\App;
/*use atc\WXC\Utils\ClassInfo;
use atc\WXC\PostTypes\PostTypeHandler;
use atc\WXC\Templates\ViewLoader;
use atc\WXC\Contracts\ShortcodeInterface;
use atc\WXC\Query\ScopedDateResolver;
use atc\WXC\Utils\DateHelper;
//
use atc\Bkkp\Modules\Accounting\PostTypes\Account;
use atc\Bkkp\Modules\Accounting\PostTypes\Transaction;
*/
/*
// Display shortcode for media_player -- for use via EM settings
add_shortcode('display_media_player', 'display_media_player');
function display_media_player( $atts = array() ) 
{
	// TS/logging setup
	$do_ts = devmode_active( array("sdg", "media") );
	$do_log = false;
	sdg_log( "divline2", $do_log );

	// Init vars
	$info = "";
	$ts_info = "";

	// Normalize attribute keys by making them all lowercase
	$atts = array_change_key_case( (array) $atts, CASE_LOWER );

	// Override default attributes with user attributes
	$args = shortcode_atts(
		array(
			'post_id' => get_the_ID(),
			'position' => 'above',
			'return' => 'info',
			'context' => 'unknown'
		), $atts
	);

	// Extract args as vars
	extract( $args );

	if ( $return == "array" ) {
		$arr_info = array();
	}

	// TODO: handle webcast status considerations

	$mp_args = array('post_id' => $post_id, 'position' => $position );
	$mp_info = get_media_player( $mp_args );
	//$mp_info = get_media_player( $post_id, false, $position );
	if ( is_array($mp_info) ) {
		$player_status = $mp_info['status'];
		//
		$info .= "<!-- Audio/Video for post_id: $post_id -->";
		$info .= $mp_info['player'];
		$info .= "<!-- context: $context -->";
		$info .= "<!-- player_status: $player_status -->";
		$info .= '<!-- /Audio/Video -->';
		//if ( $context == "EM-settings" ) { $info .= '<div class="troubleshooting sdgp">'.$mp_info['ts_info'].'</div>'; }
	} else {
		$info .= "<!-- ".print_r($mp_info,true)." -->";
	}

	if ( $return == "array" ) {
		$arr_info['info'] = $info;
		$arr_info['player_status'] = $player_status;
		return $arr_info;
	} else {
		return $info;
	}
}
*/

final class MediaPlayerShortcode implements ShortcodeInterface
{
    public static function tag(): string
    {
        return 'media_player';
    }
    
    /**
     * [accounts] – supports atts like:
     * scope="2024" | "this_year" | "2022-2025"
     * account_category="credit-cards,banking"
     * accounts="517,518" (specific account IDs or slugs)
     * account_status="active" | "closed" | "all"
     * group_by="none|account|account_months|account_years"
     * include_empty="0|1"
     * print_header="Credit Card Usage 2024"
     */
    public function render(array $atts, ?string $content = null, string $tag = ''): string
    {
        $info = "";
        #error_log('[AccountsShortcode] render() called');
        
        // Defaults
        $defaults = [
            'scope' => 'this_year',
            'account_category' => '',
            'accounts' => '',
            'account_status' => 'active',
            'group_by' => 'account_months',
            'include_empty' => '1',
            'print_header' => '',
            'order' => 'ASC',
            'orderby' => 'title',
        ];
        
        $rawAtts = (array)$atts;
        $atts = shortcode_atts($defaults, $rawAtts, self::tag());
    
		// Return something immediately to test
		#return '<div style="background: yellow; padding: 20px;">AccountsShortcode merged atts: <pre>' . print_r($atts, true) . '</pre></div>';
        
        // Resolve scope with query-var override
        $scope = PostTypeHandler::getScopeFromRequest($atts, $atts['scope']);
        $atts['scope'] = $scope;
        
        // Normalize group mode
        $groupMode = strtolower(trim((string)($atts['group_by'])));
        if (!in_array($groupMode, ['none', 'account', 'account_months', 'account_years'], true)) {
            $groupMode = 'account_months';
        }
        $atts['group_by'] = $groupMode;
        
        $info .= "groupMode: {$groupMode}<br />";
        $info .= "scope: {$scope}<br />";

        // Get filtered accounts
        $accounts = $this->resolveAccounts($atts);
        
        #error_log('[AccountsShortcode] Accounts found: ' . count($accounts));
        
        if (empty($accounts)) {
            return '<p>No accounts found matching the specified criteria.</p>';
        }
        
        $info .= "accounts found: " . count($accounts) . "<br />";
        
        // Prepare view variables
        $viewVars = [
            'atts' => $atts,
            'grouped_by' => $groupMode,
            'print_header' => $atts['print_header'],
            'info' => $info,
        ];
        
        $viewSpecs = ['kind' => 'partial', 'module' => 'accounting', 'post_type' => 'account'];
        
        #error_log('[AccountsShortcode] About to render with group_by: ' . $groupMode);
        
        // Branch based on grouping mode
        switch ($groupMode) {
            case 'account_months':
                return $this->renderAccountMonths($accounts, $atts, $viewVars, $viewSpecs);
            
            case 'account_years':
                return $this->renderAccountYears($accounts, $atts, $viewVars, $viewSpecs);
            
            case 'account':
                return $this->renderAccountGrouped($accounts, $atts, $viewVars, $viewSpecs);
            
            case 'none':
            default:
                return $this->renderAccountSimple($accounts, $atts, $viewVars, $viewSpecs);
        }
    }
    
    /**
     * Resolve accounts based on filters
     */
    private function resolveAccounts(array $atts): array
    {
        $filters = [
            'post_type' => 'account',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        ];
        
        // Filter by account_category taxonomy
        if (!empty($atts['account_category'])) {
            $categories = is_array($atts['account_category']) 
                ? $atts['account_category'] 
                : array_map('trim', explode(',', $atts['account_category']));
            
            $filters['tax_query'] = [
                [
                    'taxonomy' => 'account_category',
                    'field' => 'slug',
                    'terms' => $categories,
                ],
            ];
        }
        
        // Filter by specific account IDs or slugs
        if (!empty($atts['accounts'])) {
            $accountIds = is_array($atts['accounts']) 
                ? $atts['accounts'] 
                : array_map('trim', explode(',', $atts['accounts']));
            
            // Check if they're numeric IDs or slugs
            $numericIds = array_filter($accountIds, 'is_numeric');
            if (count($numericIds) === count($accountIds)) {
                $filters['post__in'] = array_map('intval', $accountIds);
            } else {
                $filters['post_name__in'] = $accountIds;
            }
        }
        
        // Filter by account_status meta
        if (!empty($atts['account_status']) && $atts['account_status'] !== 'all') {
            $filters['meta_query'] = [
                [
                    'key' => 'account_status',
                    'value' => $atts['account_status'],
                    'compare' => '=',
                ],
            ];
        }
        #error_log('[AccountsShortcode::resolveAccounts] Filters: ' . print_r($filters, true));
        
        $query = new \WP_Query($filters);
        
        #error_log('[AccountsShortcode::resolveAccounts] Query found: ' . $query->found_posts . ' posts');
        
        return $query->posts;
    }

}