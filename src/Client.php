<?php

namespace IronGate\Integration;

class Client extends \GuzzleHttp\Client
{
    /**
     * Get the base URL for the account chief.
     *
     * @return string
     */
    public static function getBaseUrl(): string
    {
        return config('services.chief.base_url', 'https://account.chief.app');
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
    {
        parent::__construct(array_merge([
            'base_uri' => self::getBaseUrl(),
            'verify'   => config('services.chief.verify', true),
        ], $config));
    }
}
