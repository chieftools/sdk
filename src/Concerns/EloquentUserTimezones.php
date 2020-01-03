<?php

namespace IronGate\Chief\Concerns;

use Illuminate\Support\Carbon;

trait EloquentUserTimezones
{
    /**
     * {@inheritdoc}
     */
    protected function asDateTime($value): Carbon
    {
        $value = parent::asDateTime($value);

        if (!request()->is('nova*')) {
            $timezone = config('app.timezone_user');

            if (!empty($timezone)) {
                return $value->setTimezone($timezone);
            }
        }

        return $value;
    }
}
