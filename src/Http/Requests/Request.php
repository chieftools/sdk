<?php

namespace ChiefTools\SDK\Http\Requests;

use ChiefTools\SDK\Entities\User;
use Illuminate\Foundation\Http\FormRequest;

abstract class Request extends FormRequest
{
    /**
     * The validation rules to use.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Returns if the user is authorized to perform this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Return the currently autgenticated user.
     *
     * @param string|null $guard
     *
     * @return \ChiefTools\SDK\Entities\User
     */
    public function user($guard = null): User
    {
        return parent::user($guard);
    }
}
