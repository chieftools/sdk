<?php

namespace ChiefTools\SDK\Mail;

use InvalidArgumentException;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class ReportingFailoverTransport extends FailoverTransport
{
    public function __construct(array $transports, int $retryPeriod = 60)
    {
        parent::__construct(
            transports: array_map($this->wrapTransportWithErrorReporting(...), $transports),
            retryPeriod: $retryPeriod,
        );
    }

    protected function getNameSymbol(): string
    {
        return 'reporting_failover';
    }

    private function wrapTransportWithErrorReporting(TransportInterface $transport): TransportInterface
    {
        return new readonly class($transport) implements TransportInterface
        {
            public function __construct(
                private TransportInterface $transport,
            ) {}

            public function __toString(): string
            {
                return $this->transport->__toString();
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                try {
                    return $this->transport->send($message, $envelope);
                } catch (TransportException $e) {
                    if ($this->transport instanceof SmtpTransport) {
                        $debugLines = explode("\n", $this->transport->getStream()->getDebug());

                        foreach ($debugLines as $debugLine) {
                            logger()->info($debugLine);
                        }
                    }

                    context()->scope(function () use ($e) {
                        // @TODO: When all transports are exhausted, this exception will be reported once here and again
                        //        by the original failover transport, we might want to find a way to avoid that
                        report($e);
                    }, [
                        'transport' => (string)$this->transport,
                    ]);

                    throw $e;
                }
            }
        };
    }

    public static function registerTransport(): void
    {
        Mail::extend('reporting_failover', static function (array $config) {
            return new ReportingFailoverTransport(
                transports: array_map(
                    static function (string $mailer) {
                        $config = config("mail.mailers.{$mailer}");

                        if (empty($config) || empty($config['transport'])) {
                            throw new InvalidArgumentException("Invalid mailer configuration for {$mailer}");
                        }

                        return Mail::createSymfonyTransport($config);
                    },
                    $config['mailers'],
                ),
                retryPeriod: $config['retry_after'] ?? 60,
            );
        });
    }
}
