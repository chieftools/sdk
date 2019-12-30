<?php

namespace IronGate\Integration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class Request extends FormRequest
{
    /**
     * The validation rules to use.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Returns if the user is authorized to perform this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Return the currently autgenticated user.
     *
     * @param null $guard
     *
     * @return \IronGate\Integration\Entities\User
     */
    public function user($guard = null)
    {
        return parent::user($guard);
    }
}
