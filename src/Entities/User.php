<?php

namespace IronGate\Integration\Entities;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use IronGate\Integration\Concerns\UsesUUID;
use IronGate\Integration\Concerns\Observable;
use Laravel\Socialite\Two\User as SocialiteUser;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

/**
 * @property string              $id
 * @property string              $name
 * @property string              $email
 * @property string              $timezone
 * @property string              $chief_id
 * @property bool                $is_admin
 * @property \Carbon\Carbon|null $last_login
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, UsesUUID, Observable;

    public $incrementing = false;

    protected $table    = 'users';
    protected $keyType  = 'string';
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'chief_id',
    ];
    protected $visible  = [
        'id',
        'name',
        'email',
        'timezone',
        'last_login',
        'created_at',
        'updated_at',
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

    // Auth helpers
    public function updateFromRemote(SocialiteUser $remote): void
    {
        $this->fill([
            'name'     => $remote->getName(),
            'email'    => $remote->getEmail(),
            'chief_id' => $remote->getId(),
            'timezone' => $remote['timezone'],
            'password' => $this->chief_id === null ? str_random(64) : null,
        ])->save();
    }
    public static function createFromRemote(SocialiteUser $remote): self
    {
        return self::create([
            'name'     => $remote->getName(),
            'email'    => $remote->getEmail(),
            'chief_id' => $remote->getId(),
            'timezone' => $remote['timezone'],
            'password' => str_random(64),
        ]);
    }
    public static function createOrUpdateFromRemote(SocialiteUser $remote): self
    {
        /** @var self|null $local */
        $local = self::query()
                     ->where('chief_id', '=', $remote->getId())
                     ->orWhere('email', '=', $remote->getEmail())
                     ->first();

        if ($local === null) {
            $local = self::createFromRemote($remote);
        } else {
            $local->updateFromRemote($remote);
        }

        return $local;
    }
}
