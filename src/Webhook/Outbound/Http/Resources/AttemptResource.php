<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Resources;

use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \ChiefTools\SDK\Webhook\Outbound\Models\Attempt
 */
#[SchemaName('WebhookDeliveryAttempt')]
class AttemptResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            /** Number of the attempt within its delivery, from 1. */
            'number' => $this->number,
            /** Whether someone resent it, instead of an automatic attempt. */
            'manual' => $this->manual,
            /** Whether the endpoint answered with a `2xx` status in time. */
            'succeeded' => $this->succeeded,
            /** ISO 8601 time the request was sent. */
            'attempted_at' => $this->attempted_at->toIso8601String(),
            /** How long the request took, null when it could not be sent. */
            'duration_ms' => $this->duration_ms,
            /** Why the attempt failed without a usable response, null when a response came. */
            'error' => $this->error,
            /** What went wrong, for people. */
            'error_message' => $this->error_message,
            'request'       => [
                /**
                 * Headers of the request, signature included. The body is the event's payload.
                 *
                 * @var array<string, string>
                 */
                'headers' => (object)$this->request_headers,
            ],
            /** The endpoint's response, null when none came. */
            'response' => $this->response_status === null ? null : [
                /**
                 * HTTP status of the response.
                 *
                 * @var int
                 */
                'status' => $this->response_status,
                /** @var array<string, string> */
                'headers' => (object)($this->response_headers ?? []),
                /** Start of the response body, null for a compressed body that could not be read. */
                'body' => $this->response_body,
                /** Whether the body was cut off at the kept size. */
                'truncated' => $this->response_truncated,
            ],
        ];
    }
}
