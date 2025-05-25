<?php

namespace ChiefTools\SDK\GraphQL\Resolvers;

use ChiefTools\SDK\Entities\User;
use ChiefTools\SDK\GraphQL\QueryResolver;

/**
 * @extends QueryResolver<User|null>
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

        if ($this->filled('only')) {
            $settings = $settings->whereIn('key', $this->input('only'));
        }

        if ($this->filled('categories')) {
            $settings = $settings->whereIn('category.key', $this->input('categories'));
        }

        return $settings->all();
    }
}
