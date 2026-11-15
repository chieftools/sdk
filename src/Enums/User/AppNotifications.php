<?php

namespace ChiefTools\SDK\Enums\User;

/**
 * Whether the app can notify a user through the Chief Tools app, synced from Account Chief. Users without a signed-in
 * Chief Tools app with notifications on have none.
 */
enum AppNotifications: string
{
    /** Notifications from this app reach the user's phone. */
    case ENABLED = 'enabled';

    /** The user muted this app in the Chief Tools app's settings. */
    case MUTED = 'muted';
}
