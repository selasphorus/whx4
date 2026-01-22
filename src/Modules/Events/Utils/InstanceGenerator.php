<?php

namespace atc\WHx4\Modules\Events\Utils;

use RRule\RRule; // RRule\RRule accepts any DateTimeInterface
use DateTimeInterface; // "An interface: implemented by both DateTime and DateTimeImmutable"
use atc\WXC\Utils\DateHelper;
use atc\WHx4\Modules\Events\Utils\EventInstances;

class InstanceGenerator
{
    /**
     * Generate instance dates based on RRULE, exclusions, and overrides.
     *
     * @param  DateTimeInterface  $start        Starting datetime for recurrence
     * @param  string             $rrule        RRULE string (e.g., "FREQ=WEEKLY;BYDAY=MO,WE,FR")
     * @param  array              $exdates      ISO-formatted date strings to exclude (Y-m-d)
     * @param  array              $overrides    Map of original date => replacement info
     * @param  int                $limit        Maximum number of instances to generate
     * @param  DateTimeInterface|null $until    Optional end date for generation
     * @return array              Array of instances with metadata
     */
    public static function generateInstanceDates(
        DateTimeInterface $start,
        string $rrule,
        array $exdates = [],
        array $overrides = [],
        int $limit = 100,
        ?DateTimeInterface $until = null
    ): array 
    {
        if ( ! $start || ! $rrule ) {
            return [];
        }

        // Build RRULE string for library
        $rrule_string = "DTSTART:" . $start->format( 'Ymd\THis\Z' ) . "\nRRULE:" . $rrule;
        
        try {
            $rule = new RRule( $rrule_string );
        } catch ( \Exception $e ) {
            error_log( "RRULE parsing error: " . $e->getMessage() );
            return [];
        }
        
        $results = [];

        foreach ( $rule as $dt ) {
            // Use Y-m-d format for consistency (date only, no time)
            $dateKey = $dt->format( 'Y-m-d' );
            //$iso = $dt->format( 'Y-m-d\TH:i:s' );

            // Check until limit
            if ( $until && $dt > $until ) {
                break;
            }

            // Check if this date is excluded
            if ( in_array( $dateKey, $exdates, true ) ) {
                continue;
            }

            // Build instance data
            $instance = [
                'datetime' => $dt,
                'date_key' => $dateKey,
                'is_override' => false,
                'override_post_id' => null,
            ];

            // Check if this date has an override/replacement
            if ( isset( $overrides[ $dateKey ] ) ) {
                $override = $overrides[ $dateKey ];
                
                // Override datetime takes precedence (for rescheduled events)
                if ( isset( $override['datetime'] ) && $override['datetime'] instanceof DateTimeInterface ) {
                    $instance['datetime'] = $override['datetime'];
                }
                
                $instance['is_override'] = true;
                $instance['override_post_id'] = $override['post_id'] ?? null;
            }
        
			// Add permalink if we have a post ID
			if ( $postID ) {
				$instance['permalink'] = self::getInstancePermalink( $postID, $dateKey );
			}

            $results[] = $instance;

            // Check limit
            if ( count( $results ) >= $limit ) {
                break;
            }
        }

        return $results;
    }

    /**
     * Generate instance dates using a post ID (convenience wrapper).
     *
     * @param  int                $postID
     * @param  int                $limit
     * @param  bool               $includeExcluded  Include excluded dates in output
     * @param  DateTimeInterface|null $until
     * @return array              Array of instance data
     */
    public static function fromPostId(
        int $postID,
        int $limit = 100,
        bool $includeExcluded = false,
        ?DateTimeInterface $until = null
    ): array 
    {
        // Get start datetime
        $startDT = DateHelper::combineDateAndTime(
            get_post_meta( $postID, 'whx4_events_start_date', true ),
            get_post_meta( $postID, 'whx4_events_start_time', true )
        );

        // Get RRULE
        $rrule = get_post_meta( $postID, 'whx4_events_rrule', true ); //$rrule = get_field( 'whx4_events_rrule', $postID );

        if ( ! $startDT || ! $rrule ) {
            return [];
        }

        // Get excluded dates
        $exdates = [];
        if ( ! $includeExcluded ) {
            $exdates_raw = get_post_meta( $postID, 'whx4_events_excluded_dates', true ) ?: [];
            
            if ( ! is_array( $exdates_raw ) ) {
                $exdates_raw = [];
            }
            
            $exdates = array_filter(
                (array) $exdates_raw,
                fn( $date ) => is_string( $date ) && strtotime( $date ) !== false
            );

            /*$exdates = array_map(
                fn( $date ) => ( new \DateTime( $date ) )->format( 'Y-m-d\TH:i:s' ),
                $exdates
            );*/
            
        }
        
        // Get override/replacement dates
        $overrides = EventInstances::getOverrideDates( $postID );

        return self::generateInstanceDates(
            $startDT,
            $rrule,
            $exdates,
            $overrides,
            $limit,
            $until,
            $postID  // Pass the post ID so permalinks are generated
        );
    }

    /**
     * Get a single instance for a specific date.
     *
     * @param  int    $postID
     * @param  string $dateKey  Date in Y-m-d format
     * @return array|null       Instance data or null if not found
     */
    public static function getSingleInstance( int $postID, string $dateKey ): ?array
    {
        $instances = self::fromPostId( $postID, 500, true );
        
        foreach ( $instances as $instance ) {
            if ( $instance['date_key'] === $dateKey ) {
                return $instance;
            }
        }
        
        return null;
    }

    /**
     * Check if an event is recurring.
     *
     * @param  int  $postID
     * @return bool
     */
    public static function isRecurring( int $postID ): bool
    {
        $rrule = get_post_meta( $postID, 'whx4_events_rrule', true );
        return ! empty( $rrule );
    }

	/**
	 * Get permalink for a specific instance.
	 *
	 * @param  int    $postID   The event post ID
	 * @param  string $dateKey  Date in Y-m-d format
	 * @return string           Instance-specific URL
	 */
	public static function getInstancePermalink( int $postID, string $dateKey ): string
	{
		$event_slug = get_post_field( 'post_name', $postID );
		return home_url( "/event/{$dateKey}-{$event_slug}/" );
	}
	
	/**
	 * Check if currently viewing a specific instance.
	 *
	 * @param  int    $postID   The event post ID
	 * @param  string $dateKey  Date in Y-m-d format
	 * @return bool
	 */
	public static function isCurrentInstance( int $postID, string $dateKey ): bool
	{
		return get_query_var( 'event_instance' ) === $dateKey;
	}
	
	/**
	 * Get the current instance date from query var.
	 *
	 * @return string|false Date string or false if not viewing an instance
	 */
	public static function getCurrentInstanceDate()
	{
		return get_query_var( 'event_instance' ) ?: false;
	}
}