import * as Sentry from '@sentry/browser';

if (window.SENTRY !== undefined && window.SENTRY !== null && window.SENTRY.DSN) {
    const urlParams = new URLSearchParams(window.location.search);
    const envDisabledTracing = urlParams.get('sentry_no_trace') !== null || navigator.userAgent.includes('GTmetrix');

    const isAuthenticated = window.USER !== undefined && window.USER !== null;
    const enableSentryTracing = !envDisabledTracing && window.SENTRY.TRACES_SAMPLE_RATE && window.SENTRY.TRACES_SAMPLE_RATE > 0;
    const firstPartyHostMatcher = new RegExp('https://(?:[\\w.]+\\.)?' + window.BASE.replaceAll('.', '\\.'));

    const feedback = (window.SENTRY_FEEDBACK = Sentry.feedbackIntegration({
        showName: !isAuthenticated,
        showEmail: !isAuthenticated,
        autoInject: false,
        colorScheme: 'light',
        showBranding: false,
        useSentryUser: {
            name: 'name',
            email: 'email',
        },
        isEmailRequired: true,
    }));

    Sentry.init({
        dsn: window.SENTRY.DSN,
        tunnel: window.SENTRY.TUNNEL,
        release: window.SENTRY.RELEASE,
        beforeSend(event, hint) {
            if (window.UNAUTHENTICATED_RELOAD_PENDING) {
                return null;
            }

            return event;
        },
        environment: window.ENV,
        integrations: [
            feedback,
            ...(enableSentryTracing
                ? [
                      Sentry.browserSessionIntegration(),
                      Sentry.browserTracingIntegration({
                          beforeStartSpan: context => {
                              return {
                                  ...context,
                                  name: location.pathname
                                      .replaceAll(/(\/)(@[a-zA-Z0-9-_]+)(\/|$)/g, '$1<username>$3')
                                      .replaceAll(/(\/)([a-f0-9-]{32,36})(\/|$)/g, '$1<uuid>$3')
                                      .replaceAll(/(\/)(CHIEF-[A-Z0-9-]+-[A-Z]+)(\/|$)/g, '$1<handle>$3')
                                      .replaceAll(/(\/team\/)([a-z0-9]{8})(\/|$)/g, '$1<slug>$3')
                                      .replaceAll(/(\/)(\d+)(\/|$)/g, '$1<id>$3'),
                              };
                          },
                      }),
                      Sentry.replayIntegration({
                          workerUrl: '/js/sentry/replay-worker.min.js',
                          networkDetailAllowUrls: [firstPartyHostMatcher],
                      }),
                  ]
                : []),
        ],
        sendDefaultPii: true,
        tracesSampleRate: enableSentryTracing ? window.SENTRY.TRACES_SAMPLE_RATE : 0.0,
        tracePropagationTargets: [firstPartyHostMatcher],
        replaysSessionSampleRate: enableSentryTracing ? (window.SENTRY.REPLAYS_SAMPLE_RATE ?? 0.0) : 0.0,
        replaysOnErrorSampleRate: enableSentryTracing ? (window.SENTRY.REPLAYS_ERROR_SAMPLE_RATE ?? 1.0) : 0.0,
    });

    if (isAuthenticated) {
        Sentry.getCurrentScope().setUser({
            id: window.USER.id,
            name: window.USER.name,
            email: window.USER.email,
            chief_id: window.USER.chief_id,
        });
    }
}

window.Sentry = Sentry;
