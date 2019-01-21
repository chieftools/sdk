<?php

namespace IronGate\Integration\Concerns;

trait Observable
{
    public static function bootObservable(): void
    {
        $class  = static::class;
        $events = (new static)->getObservableEvents();

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'register' . class_basename($trait) . 'Events';

            if (method_exists($class, $method)) {
                $events = array_merge($events, static::{$method}());
            }
        }

        collect($events)->unique()->each(function ($event) {
            $method = 'on' . ucfirst($event);

            if (method_exists(static::class, $method)) {
                static::registerModelEvent($event, static::class . '@' . $method);
            }
        });
    }
}
