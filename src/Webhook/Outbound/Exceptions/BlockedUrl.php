<?php

namespace ChiefTools\SDK\Webhook\Outbound\Exceptions;

use RuntimeException;

/**
 * The webhook URL is not allowed: not HTTPS, not resolvable or resolving to a private or reserved address.
 */
class BlockedUrl extends RuntimeException {}
