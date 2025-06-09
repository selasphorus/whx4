<?php

namespace atc\WHx4\Modules\Events\Utils;

use DateTimeImmutable;
use RRule\RRule;

class InstanceGenerator
{
    protected ?DateTimeImmutable $start = null;
    protected ?string $rrule = null;
    protected array $exdates = [];
    protected array $overrides = [];

    public function __construct( array $config )
    {
        if ( !empty( $config['start'] ) ) {
            $this->start = $config['start'] instanceof \DateTimeInterface
                ? DateTimeImmutable::createFromInterface( $config['start'] )
                : new DateTimeImmutable( $config['start'] );
        }

        $this->rrule     = $config['rrule']     ?? null;
        $this->exdates   = $config['exdates']   ?? [];
        $this->overrides = $config['overrides'] ?? [];
    }

    public static function generateInstanceDates( int $limit = 100, ?\DateTimeInterface $until = null ): array
    {
        if ( !$this->start || !$this->rrule ) {
            return [];
        }

        $rule = new RRule([
            'DTSTART' => $this->start,
            'RRULE'   => $this->rrule,
        ]);

        $results = [];

        foreach ( $rule as $dt ) {
            $iso = $dt->format( 'Y-m-d\TH:i:s' );

            if ( $until && $dt > $until ) {
                break;
            }

            if ( in_array( $iso, $this->exdates, true ) ) {
                continue;
            }

            if ( isset( $this->overrides[ $iso ] ) && $this->overrides[ $iso ] instanceof \DateTimeInterface ) {
                $dt = $this->overrides[ $iso ];
            }

            $results[] = $dt;

            if ( count( $results ) >= $limit ) {
                break;
            }
        }

        return $results;
    }
}
