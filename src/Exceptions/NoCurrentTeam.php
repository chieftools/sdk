<?php

namespace ChiefTools\SDK\Exceptions;

use LogicException;

/**
 * The request has no current team: the global team view combines all teams of the user, and the team selection asks
 * the user to choose one.
 *
 * Code that asks for the current team there does not support it, falling back to a team would show or change the data
 * of a team the user did not choose.
 */
class NoCurrentTeam extends LogicException
{
    public static function inGlobalTeamView(): self
    {
        return new self('The global team view has no current team, use the teams in context of the user instead.');
    }

    public static function inTeamSelection(): self
    {
        return new self('The team selection has no current team, the user chooses the team to continue with.');
    }
}
