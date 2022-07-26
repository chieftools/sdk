<?php

namespace ChiefTools\SDK\GraphQL\Mutations\User\Preference;

use ChiefTools\SDK\GraphQL\Mutations\Mutation;

class Reset extends Mutation
{
    public function rules(): array
    {
        $preferences = collect($this->user()::getPreferences());

        return [
            'key' => 'required|in:' . $preferences->keys()->implode(','),
        ];
    }

    public function mutate(): array
    {
        $preferences = collect($this->user()::getPreferences());

        [$name, $description, , $default] = $preferences->get($key = $this->input('key'));

        $this->user()->setPreference($key, null);
        $this->user()->save();

        $value   = $default;
        $changed = true;

        return compact('key', 'name', 'description', 'default', 'value', 'changed');
    }
}
