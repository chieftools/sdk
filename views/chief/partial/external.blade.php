@includeWhen(config('services.plain.app_key') !== null && (auth()->guest() || auth()->user()->getPreference('enable_support_widget', true)), 'chief::partial.external.plain')
@includeWhen(config('chief.analytics.fathom.site') !== null, 'chief::partial.external.fathom')
