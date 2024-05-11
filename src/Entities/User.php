<?php

namespace ChiefTools\SDK\Entities;

use RuntimeException;
use ChiefTools\SDK\Chief;
use Laravel\Passport\Passport;
use ChiefTools\SDK\Helpers\Avatar;
use Illuminate\Support\Collection;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use ChiefTools\SDK\Socialite\ChiefTeam;
use ChiefTools\SDK\Socialite\ChiefUser;
use Illuminate\Database\Eloquent\Builder;
use Stayallive\Laravel\Eloquent\UUID\UsesUUID;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

/**
 * @property string                                                                       $id
 * @property string                                                                       $name
 * @property string                                                                       $email
 * @property string|null                                                                  $avatar_hash
 * @property string                                                                       $timezone
 * @property string                                                                       $chief_id
 * @property string                                                                       $password
 * @property bool                                                                         $is_admin
 * @property string                                                                       $avatar_url
 * @property \Carbon\Carbon|null                                                          $last_login
 * @property \Carbon\Carbon                                                               $created_at
 * @property \Carbon\Carbon                                                               $updated_at
 * @property array                                                                        $preferences
 * @property string|null                                                                  $remember_token
 * @property int|null                                                                     $default_team_id
 * @property \ChiefTools\SDK\Entities\Team|null                                           $team
 * @property \Illuminate\Database\Eloquent\Collection<int, \ChiefTools\SDK\Entities\Team> $teams
 * @property \ChiefTools\SDK\Entities\Team|null                                           $defaultTeam
 */
class User extends Entity implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, UsesUUID;

    protected $table    = 'users';
    protected $fillable = [
        'name',
        'email',
        'timezone',
        'password',
        'default_team_id',
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
    ];
    protected $hidden   = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];
    protected $casts    = [
        'is_admin'    => 'bool',
        'last_login'  => 'datetime',
        'preferences' => 'array',
    ];

    private ?Team $memoizedCurrentTeam;

    // Attributes
    public function __toString(): string
    {
        return $this->name;
    }
    public function name(): Attribute
    {
        return new Attribute(
            set: static fn ($value) => trim($value),
        );
    }
    public function team(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currentTeam(),
        );
    }
    public function timezone(): Attribute
    {
        return new Attribute(
            get: static fn (?string $value) => $value ?? config('app.timezone'),
            set: static function (?string $value) {
                if (empty($value) || !array_key_exists($value, timezones())) {
                    return config('app.timezone');
                }

                return $value;
            },
        );
    }
    public function avatarUrl(): Attribute
    {
        return new Attribute(
            get: fn () => Avatar::of($this)->url(),
        );
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

    // Relations
    public function teams(): BelongsToMany
    {
        $relation = $this->belongsToMany(Chief::teamModel())->withTimestamps();

        $relation->getQuery()->when($this->default_team_id !== null, function (Builder $query) {
            $grammer = $query->getQuery()->grammar;

            match (true) {
                $grammer instanceof MySqlGrammar    => $query->orderByRaw('IF(`teams`.`id` = ?, -1, `name`) ASC', [$this->default_team_id]),
                $grammer instanceof PostgresGrammar => $query->orderByRaw('CASE WHEN "teams"."id" = ? THEN \'-1\' ELSE "name" END ASC', [$this->default_team_id]),
                default                             => throw new RuntimeException('Unsupported database grammar for teams relation ordering!'),
            };
        }, function (Builder $query) {
            $query->orderBy('name');
        })->orderBy('id');

        return $relation;
    }
    public function defaultTeam(): BelongsTo
    {
        return $this->belongsTo(Chief::teamModel(), 'default_team_id');
    }

    // Passport relations
    public function personalAccessTokens(): HasMany
    {
        if (!class_exists(Passport::class) || !config('chief.auth.passport')) {
            throw new RuntimeException('Passport is not installed/active!');
        }

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

    // Team helpers
    public function currentTeam(): Team
    {
        if (isset($this->memoizedCurrentTeam)) {
            return $this->memoizedCurrentTeam;
        }

        $request = request();

        $fromRequest = $request->attributes->get('team_hint') ??
                       $request->route('team_hint') ??
                       $request->header('x-chief-team') ??
                       session('chief_team_hint') ??
                       null;

        if ($fromRequest === null) {
            return $this->memoizedCurrentTeam = $this->defaultOrFirstTeam();
        }

        if ($fromRequest instanceof Team) {
            return $this->memoizedCurrentTeam = $fromRequest;
        }

        return $this->memoizedCurrentTeam = $this->teams()->where('slug', '=', $fromRequest)->first() ?? $this->defaultOrFirstTeam();
    }
    public function setCurrentTeam(Team $team): void
    {
        $this->memoizedCurrentTeam = $team;

        url()->defaults(['team_hint' => $team->slug]);
        session()->put('chief_team_hint', $team->slug);
        request()->attributes->set('team_hint', $team);
        app()->bind(Chief::teamModel(), static fn () => $team);

        $team->maybeUpdateLastActivity();
    }
    public function clearCurrentTeam(): void
    {
        unset($this->memoizedCurrentTeam);
    }
    public function defaultOrFirstTeam(): Team
    {
        return $this->defaultTeam ?? $this->teams->first();
    }
    public function getTeamFromSession(): ?Team
    {
        $sessionHint = session('chief_team_hint');

        if ($sessionHint === null) {
            return null;
        }

        return $this->memoizedCurrentTeam = $this->teams()->where('slug', '=', $sessionHint)->first();
    }

    // Preference helpers
    public function getPreference($preference, $default = null)
    {
        $template = config("chief.preferences.{$preference}", false);

        if ($template === false && $default === null) {
            throw new RuntimeException("Preference '{$preference}' does not exist and no default was given.");
        }

        return array_get($this->preferences, $preference, $default ?? $template[3]);
    }
    public function setPreference($preference, $value): void
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
        if ($this->chief_id === null) {
            $this->chief_id = $remote->getId();
        }

        $this->is_admin = $remote->is_admin;

        Team::createOrUpdateFromRemotes($remote->teams);

        $this->forceFill([
            'name'            => $remote->getName(),
            'email'           => $remote->getEmail(),
            'timezone'        => $remote->timezone,
            'password'        => empty($this->password) ? str_random(64) : null,
            'avatar_hash'     => $remote->avatar_hash,
            'default_team_id' => $remote->default_team_id,
        ])->save();

        $this->teams()->sync(array_map(
            static fn (ChiefTeam $team) => $team->id,
            $remote->teams,
        ));
    }
    private static function createFromRemote(ChiefUser $remote): self
    {
        $user = new static;

        $user->chief_id = $remote->getId();

        return $user;
    }
    public static function createOrUpdateFromRemote(ChiefUser $remote): self
    {
        /** @var \ChiefTools\SDK\Entities\User|null $local */
        $local = self::query()
                     ->where('chief_id', '=', $remote->getId())
                     ->orWhere(function (Builder $query) use ($remote) {
                         $query->whereNull('chief_id')
                               ->where('email', '=', $remote->getEmail());
                     })
                     ->first();

        if ($local === null) {
            $local = self::createFromRemote($remote);
        }

        $local->updateFromRemote($remote);

        Chief::dispatchAfterUserUpdateJob($local);

        return $local;
    }
}
