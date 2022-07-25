<?php

namespace IronGate\Chief\Entities;

use RuntimeException;
use IronGate\Chief\Socialite\ChiefTeam;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property      int                                                                          $id
 * @property      string                                                                       $slug
 * @property      string                                                                       $name
 * @property      array                                                                        $limits
 * @property      bool                                                                         $is_default
 * @property      \Carbon\Carbon                                                               $created_at
 * @property      \Carbon\Carbon                                                               $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \IronGate\Chief\Entities\User> $users
 */
class Team extends Entity
{
    protected $table = 'teams';
    protected $casts = [
        'limits' => AsArrayObject::class,
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
    public function isDefault(): Attribute
    {
        return new Attribute(
            get: function () {
                return auth()->user()?->default_team_id === $this->id;
            },
        );
    }

    // Relations
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps()->orderBy('created_at');
    }

    // Static helpers
    public static function findOrFailBySlug(string $slug): self
    {
        return self::query()->where('slug', '=', $slug)->firstOrFail();
    }
    public static function createOrUpdateFromRemotes(array $teams): void
    {
        $teamIds = array_map(static fn (ChiefTeam $team) => $team->id, $teams);

        if (empty($teamIds)) {
            return;
        }

        $existingTeams = self::query()->find($teamIds);

        collect($teams)->each(function (ChiefTeam $chiefTeam) use ($existingTeams) {
            /** @var self $team */
            $team = $existingTeams->find($chiefTeam->id) ?? (static function () use ($chiefTeam) {
                $team = new self;

                $team->id   = $chiefTeam->id;
                $team->slug = $chiefTeam->slug;

                return $team;
            })();

            $team->name   = $chiefTeam->name;
            $team->limits = $chiefTeam->limits;

            $team->save();
        });
    }

    // UrlRoutable
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    public function resolveRouteBinding($value, $field = null): ?self
    {
        /** @var \IronGate\Chief\Entities\User|null $user */
        $user = auth()->user();

        if ($user === null) {
            throw new RuntimeException('Team cannot be resolved from route for guests.');
        }

        return $user->teams()->where($field ?? 'slug', '=', $value)->firstOrFail();
    }
}
