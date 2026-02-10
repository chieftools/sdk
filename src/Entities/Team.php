<?php

namespace ChiefTools\SDK\Entities;

use RuntimeException;
use ChiefTools\SDK\Chief;
use ChiefTools\SDK\API\Client;
use ChiefTools\SDK\Helpers\Avatar;
use ChiefTools\SDK\Auth\HasRemoteToken;
use ChiefTools\SDK\Socialite\ChiefTeam;
use Illuminate\Database\Eloquent\Casts\Attribute;
use ChiefTools\SDK\Auth\AuthenticatesWithRemoteToken;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

/**
 * @property int                 $id
 * @property string              $slug
 * @property string              $name
 * @property string              $avatar_url
 * @property string|null         $avatar_hash
 * @property string|null         $gravatar_email
 * @property array               $limits
 * @property string              $timezone
 * @property bool                $is_default
 * @property \Carbon\Carbon|null $last_activity_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class Team extends Entity implements AuthenticatableContract, AuthenticatesWithRemoteToken
{
    use HasRemoteToken;

    protected $table    = 'teams';
    protected $casts    = [
        'limits'           => AsArrayObject::class,
        'last_activity_at' => 'datetime',
    ];
    protected $fillable = [
        'name',
        'timezone',
        'gravatar_email',
    ];

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
    public function password(): Attribute
    {
        // We don't support passwords for teams
        return new Attribute(
            set: static fn ($value) => null,
            get: static fn () => str_random(),
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
            get: fn () => Avatar::ofTeam($this)->url(),
        );
    }
    public function isDefault(): Attribute
    {
        return new Attribute(
            get: function () {
                return authenticated_user()?->default_team_id === $this->id;
            },
        );
    }
    public function gravatarEmail(): Attribute
    {
        return new Attribute(
            set: static function ($value) {
                $email = $value === null ? null : strtolower(trim($value));

                return empty($email) ? null : $email;
            },
        );
    }

    // Relations
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(Chief::userModel())->withTimestamps()->orderBy('created_at');
    }

    // Helpers
    public function updateFromRemote(ChiefTeam $remote): void
    {
        $this->name           = $remote->name;
        $this->limits         = $remote->limits;
        $this->timezone       = $remote->timezone;
        $this->avatar_hash    = $remote->avatarHash;
        $this->gravatar_email = $remote->gravatarEmail;

        $this->save();
    }
    public function maybeUpdateLastActivity(): void
    {
        if (!$this->shouldUpdateLastActivity()) {
            return;
        }

        $this->last_activity_at = now();
        $this->saveQuietly();

        dispatch(function () {
            app(Client::class)->reportActivity($this);
        })->afterResponse();
    }
    public function shouldUpdateLastActivity(): bool
    {
        if ($this->last_activity_at === null) {
            return true;
        }

        return $this->last_activity_at->diffInHours(absolute: true) >= 1;
    }

    // Static helpers
    public static function findOrFailBySlug(string $slug): static
    {
        return static::query()->where('slug', '=', $slug)->firstOrFail();
    }
    protected static function newFromRemote(ChiefTeam $remote): static
    {
        /** @phpstan-ignore new.static */
        $team = new static;

        $team->id   = $remote->id;
        $team->slug = $remote->slug;

        return $team;
    }
    public static function createOrUpdateFromRemotes(array $teams): void
    {
        $teamIds = array_map(static fn (ChiefTeam $team) => $team->id, $teams);

        if (empty($teamIds)) {
            return;
        }

        $existingTeams = static::query()->find($teamIds);

        collect($teams)->each(function (ChiefTeam $chiefTeam) use ($existingTeams) {
            /** @var \ChiefTools\SDK\Entities\Team|null $team */
            $team = $existingTeams->find($chiefTeam->id);

            if ($team === null) {
                $team = static::newFromRemote($chiefTeam);
            }

            $team->updateFromRemote($chiefTeam);

            Chief::dispatchAfterTeamUpdateJob($team);
        });
    }

    // UrlRoutable
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $user = authenticated_user();

        if ($user === null) {
            throw new RuntimeException('Team cannot be resolved from route for guests.');
        }

        if ($value === 'default') {
            return $user->defaultOrFirstTeam();
        }

        if ($value === 'current') {
            return $user->getTeamFromSession() ?? $user->defaultOrFirstTeam();
        }

        $currentTeam = $user->getMemoizedCurrentTeam();

        if ($currentTeam?->slug === $value) {
            return $currentTeam;
        }

        return $user->teams()->where($field ?? 'slug', '=', $value)->first();
    }

    // Authenticatable
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }
    public function getAuthIdentifier(): int
    {
        return $this->id;
    }
    public function getAuthPasswordName(): string
    {
        return 'password';
    }
    public function getAuthPassword(): string
    {
        return str_random();
    }
    public function getRememberToken(): string
    {
        return '';
    }
    public function setRememberToken($value): void
    {
        // Do nothing, we don't support remember tokens
    }
    public function getRememberTokenName(): string
    {
        return '';
    }
}
