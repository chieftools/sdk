<?php

namespace ChiefTools\SDK\Concerns;

use Illuminate\Support\Carbon;

trait EloquentUserTimezones
{
    /**
     * {@inheritdoc}
     */
    protected function asDateTime($value): Carbon
    {
        $value = parent::asDateTime($value);

        if (!request()?->is('nova*')) {
            $timezone = config('app.timezone_user');

            if (!empty($timezone)) {
                return $value->setTimezone($timezone);
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDateTime($value): ?string
    {
        if ($value instanceof Carbon && !request()?->is('nova*')) {
            $timezone = config('app.timezone_user');

            if (!empty($timezone)) {
                $value->shiftTimezone($timezone);
            }
        }

        return parent::fromDateTime($value);
    }
}
