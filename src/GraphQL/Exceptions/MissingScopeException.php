<?php

namespace ChiefTools\SDK\GraphQL\Exceptions;

use GraphQL\Error\UserError;
use GraphQL\Error\ProvidesExtensions;

class MissingScopeException extends UserError implements ProvidesExtensions
{
    public function __construct(
        string $field,
        private readonly array $scopesRequired,
        private readonly array $scopesGranted,
        private readonly bool $allScopesRequired,
    ) {
        $scopesRestriction = $allScopesRequired ? 'all' : 'one';

        parent::__construct(
            "The '{$field}' field requires {$scopesRestriction} of the following scopes: ['" . implode("', '", $scopesRequired) . "'], but your token has only been granted the: ['" . implode("', '", $scopesGranted) . "'] scopes.",
        );
    }

    public function getExtensions(): array
    {
        return array_merge([
            'grantedScopes'     => $this->scopesGranted,
            'requiredScopes'    => $this->scopesRequired,
            'allScopesRequired' => $this->allScopesRequired,
        ]);
    }
}
