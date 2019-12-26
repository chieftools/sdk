<?php

namespace IronGate\Integration\GraphQL\Resolvers;

use IronGate\Integration\Entities\User;

class UserPreferences
{
    public function __invoke(?User $root, array $args): ?array
    {
        if ($root === null) {
            return null;
        }

        $settings = collect();

        foreach (User::getPreferences() as $key => [$name, $description, $icon, $default]) {
            $value       = $root->getPreference($key, -1);
            $changed     = $value !== -1;
            $description = strip_tags($description);

            if ($value === -1) {
                $value = $default;
            }

            $settings->push(compact('key', 'name', 'description', 'default', 'value', 'changed', 'icon'));
        }

        if (!empty($args['only'])) {
            $settings = $settings->whereIn('key', $args['only']);
        }

        return $settings->all();
    }
}
