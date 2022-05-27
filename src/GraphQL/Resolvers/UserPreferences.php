<?php

namespace IronGate\Chief\GraphQL\Resolvers;

use IronGate\Chief\Entities\User;
use IronGate\Chief\GraphQL\QueryResolver;

/**
 * @extends QueryResolver<User>
 */
class UserPreferences extends QueryResolver
{
    public function execute(): ?array
    {
        if ($this->root === null) {
            return null;
        }

        $settings = collect();

        foreach (User::getPreferences() as $key => [$name, $description, $icon, $default, $categoryKey]) {
            $value       = $this->root->getPreference($key, -1);
            $changed     = $value !== -1;
            $category    = config("chief.preference_categories.{$categoryKey}");
            $description = strip_tags($description);

            // Make sure the category contains a key
            $category['key'] = $categoryKey;

            if ($value === -1) {
                $value = $default;
            }

            $settings->push(compact('key', 'name', 'description', 'default', 'value', 'changed', 'icon', 'category'));
        }

        if (!empty($args['only'])) {
            $settings = $settings->whereIn('key', $args['only']);
        }

        if (!empty($args['categories'])) {
            $settings = $settings->whereIn('category.key', $args['categories']);
        }

        return $settings->all();
    }
}
