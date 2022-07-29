<?php

namespace ChiefTools\SDK\Entities;

use RuntimeException;
use ChiefTools\SDK\API\Client;
use ChiefTools\SDK\Helpers\Avatar;
use ChiefTools\SDK\Socialite\ChiefTeam;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int                 $id
 * @property string              $slug
 * @property string              $name
 * @property string|null         $gravatar_email
 * @property string|null         $avatar_hash
 * @property array               $limits
 * @property bool                $is_default
 * @property \Carbon\Carbon|null $last_activity_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class Team extends Entity
{
    protected $table    = 'teams';
    protected $casts    = [
        'limits'           => AsArrayObject::class,
        'last_activity_at' => 'datetime',
    ];
    protected $fillable = [
        'name',
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
                return auth()->user()?->default_team_id === $this->id;
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
        return $this->belongsToMany(User::class)->withTimestamps()->orderBy('created_at');
    }

    // Helpers
    public function updateFromRemote(ChiefTeam $remote): void
    {
        $this->name           = $remote->name;
        $this->limits         = $remote->limits;
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

        return $this->last_activity_at->diffInHours() >= 1;
    }

    // Static helpers
    public static function findOrFailBySlug(string $slug): static
    {
        return static::query()->where('slug', '=', $slug)->firstOrFail();
    }
    public static function createFromRemote(ChiefTeam $remote): static
    {
        $team = new static;

        $team->id   = $remote->id;
        $team->slug = $remote->slug;

        $team->updateFromRemote($remote);

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
            /** @var self|null $team */
            $team = $existingTeams->find($chiefTeam->id);

            if ($team === null) {
                static::createFromRemote($chiefTeam);
            } else {
                $team->updateFromRemote($chiefTeam);
            }
        });
    }

    // UrlRoutable
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    public function resolveRouteBinding($value, $field = null): ?static
    {
        /** @var \ChiefTools\SDK\Entities\User|null $user */
        $user = auth()->user();

        if ($user === null) {
            throw new RuntimeException('Team cannot be resolved from route for guests.');
        }

        return $user->teams()->where($field ?? 'slug', '=', $value)->firstOrFail();
    }
}
