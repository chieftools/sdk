<?php

namespace ChiefTools\SDK\Exceptions;

use Illuminate\Queue\Jobs\Job;
use Sentry\Tracing\SamplingContext;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Request;

class Sentry
{
    private const IGNORE_FROM_TRACING = [
        'manifest.webmanifest',
        'microsoft-identity-association.json',
    ];

    public static function tracesSampler(SamplingContext $context): float
    {
        $sampleRate = config('sentry.traces_sample_rate');

        if ($sampleRate <= 0) {
            return 0.0;
        }

        $request = request();

        // We don't care about CORS requests
        if ($request->isMethod(Request::METHOD_OPTIONS) && $request->headers->has('Access-Control-Request-Method')) {
            return 0.0;
        }

        // We also don't care about some url's in this list so we don't trace them at all
        if ($request->is(self::IGNORE_FROM_TRACING)) {
            return 0.0;
        }

        // We always sample if the front-end indicates it was also sampled to have full traces front to back
        if ($context->getParentSampled()) {
            return 1.0;
        }

        return $sampleRate;
    }

    public static function serializeJob(Job $job): ?array
    {
        $data = [
            'queue'    => $job->getQueue(),
            'attempts' => $job->attempts(),
        ];

        // Extract tags if it's a Horizon job
        if (method_exists($job, 'tags')) {
            $data['tags'] = $job->tags();
        }

        return $data;
    }

    public static function serializeEloquentModel(Model $entity): ?array
    {
        return [$entity->getKeyName() => $entity->getKey()];
    }
}
