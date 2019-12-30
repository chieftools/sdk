<?php

namespace IronGate\Integration\Entities;

use Laravel\Passport\Passport;
use Illuminate\Support\Collection;
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use IronGate\Integration\Concerns\UsesUUID;
use IronGate\Integration\Concerns\Observable;
use IronGate\Integration\Socialite\ChiefUser;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

/**
 * @property string              $id
 * @property string              $name
 * @property string              $email
 * @property string              $timezone
 * @property string              $chief_id
 * @property string              $password
 * @property bool                $is_admin
 * @property \Carbon\Carbon|null $last_login
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 * @property array               $preferences
 * @property string|null         $remember_token
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasApiTokens, UsesUUID, Observable;

    public $incrementing = false;

    protected $table    = 'users';
    protected $keyType  = 'string';
    protected $fillable = [
        'name',
        'email',
        'timezone',
        'password',
    ];
    protected $visible  = [
        'id',
        'name',
        'email',
        'is_admin',
        'timezone',
        'last_login',
        'created_at',
        'updated_at',
        'preferences',
        'is_email_verified',
    ];
    protected $hidden   = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];
    protected $casts    = [
        'is_admin'    => 'bool',
        'preferences' => 'array',
    ];
    protected $dates    = [
        'last_login',
    ];

    // Getters
    public function __toString()
    {
        return $this->name;
    }
    public function getTimezoneAttribute()
    {
        return array_get($this->attributes, 'timezone') ?? config('app.timezone');
    }

    // Setters
    public function setEmailAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['email'] = strtolower(trim($value));
        }
    }
    public function setPasswordAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }
    public function setTimezoneAttribute($value): void
    {
        $timezones = timezones();

        if (empty($value) || !array_key_exists($value, $timezones)) {
            $value = config('app.timezone');
        }

        $this->attributes['timezone'] = $value;
    }

    // Relations
    /** @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder */
    public function personalAccessTokens()
    {
        /** @var \Illuminate\Database\Eloquent\Model $clientModel */
        $clientModel = app(Passport::clientModel());

        /** @var \Illuminate\Database\Eloquent\Model $tokenModel */
        $tokenModel = app(Passport::tokenModel());

        return $this->hasMany(Passport::tokenModel(), 'user_id')
                    ->select([$tokenModel->qualifyColumn('*')])
                    ->join($clientModel->getTable(), $tokenModel->qualifyColumn('client_id'), '=', $clientModel->qualifyColumn('id'))
                    ->where($clientModel->qualifyColumn('personal_access_client'), '=', 1)
                    ->where($tokenModel->qualifyColumn('revoked'), '=', false)
                    ->orderBy($tokenModel->qualifyColumn('created_at'), 'desc');
    }

    // Preference helpers
    public function getPreference($preference, $default = null)
    {
        if (($template = config("chief.preferences.{$preference}", false)) === false) {
            throw new RuntimeException("Preference '{$preference}' does not exist.");
        }

        return array_get($this->preferences, $preference, $default ?? $template[3]);
    }
    public function setPreference($preference, $value)
    {
        if (config("chief.preferences.{$preference}", false) === false) {
            throw new RuntimeException("Preference '{$preference}' does not exist.");
        }

        $preferences = $this->preferences;

        if ($value === null) {
            unset($preferences[$preference]);
        } else {
            array_set($preferences, $preference, (bool)$value);
        }

        $this->preferences = $preferences;
    }
    public static function hasPreferences(): bool
    {
        return !empty(self::getPreferences());
    }
    public static function getPreferences(): array
    {
        return config('chief.preferences', []);
    }
    public static function getPreferencesByCategory(): Collection
    {
        return collect(self::getPreferences())->groupBy(4, true);
    }

    // Auth helpers
    public function updateFromRemote(ChiefUser $remote): void
    {
        $this->chief_id = $remote->getId();
        $this->is_admin = $remote->isAdmin();

        $this->fill([
            'name'     => $remote->getName(),
            'email'    => $remote->getEmail(),
            'timezone' => $remote->getTimezone(),
            'password' => $this->chief_id === null ? str_random(64) : null,
        ])->save();
    }
    public static function createFromRemote(ChiefUser $remote): self
    {
        $user = new static();

        $user->updateFromRemote($remote);

        return $user;
    }
    public static function createOrUpdateFromRemote(ChiefUser $remote): self
    {
        /** @var self|null $local */
        $local = self::query()
                     ->where('chief_id', '=', $remote->getId())
                     ->orWhere(function (Builder $query) use ($remote) {
                         $query->whereNull('chief_id')
                               ->where('email', '=', $remote->getEmail());
                     })
                     ->first();

        if ($local === null) {
            $local = self::createFromRemote($remote);
        } else {
            $local->updateFromRemote($remote);
        }

        return $local;
    }
}
