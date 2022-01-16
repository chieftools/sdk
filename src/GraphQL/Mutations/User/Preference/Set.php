<?php

namespace IronGate\Chief\GraphQL\Mutations\User\Preference;

use IronGate\Chief\GraphQL\Mutations\Mutation;

class Set extends Mutation
{
    public function rules(): array
    {
        $preferences = collect($this->user()::getPreferences());

        return [
            'key'   => 'required|in:' . $preferences->keys()->implode(','),
            'value' => 'required|bool',
        ];
    }

    public function mutate(): ?array
    {
        $preferences = collect($this->user()::getPreferences());

        [$name, $description, , $default] = $preferences->get($key = $this->input('key'));

        $this->user()->setPreference($key, $this->input('value'));
        $this->user()->save();

        $value   = $this->user()->getPreference($key);
        $changed = true;

        return compact('key', 'name', 'description', 'default', 'value', 'changed');
    }
}
