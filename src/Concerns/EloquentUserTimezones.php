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

        if ($this->isServingApplication()) {
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
        if ($value instanceof Carbon && $this->isServingApplication()) {
            $timezone = config('app.timezone_user');

            if (!empty($timezone)) {
                $value->shiftTimezone($timezone);
            }
        }

        return parent::fromDateTime($value);
    }

    /**
     * Admin panels and other tools that are not the application itself should not use user timezones.
     */
    private function isServingApplication(): bool
    {
        if (request()?->is('nova*')) {
            return false;
        }

        if (function_exists('filament') && filament()->isServing()) {
            return false;
        }

        return true;
    }
}
