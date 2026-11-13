/*
  Self-hosted GraphiQL playground for the `api.playground` route.

  Built into `files/graphiql` with `yarn build:graphiql` and copied into each
  app's `public/js/graphiql` by the `@chieftools/sdk/vite` plugin, so the
  playground never loads code from a third-party origin at runtime.

  The mount element's `data-endpoint` is the session authenticated GraphQL
  endpoint (`api.web`), `data-subscriptions` is present when the app has
  GraphQL subscriptions enabled. The current team (`window.TEAM`) is sent as
  the `X-Chief-Team` header and subscriptions connect with `window.REALTIME`,
  both injected by the `JavaScriptInjector` middleware.
*/
import 'graphiql/style.css';
import 'graphiql/setup-workers/vite';
import {createElement} from 'react';
import {createRoot} from 'react-dom/client';
import {GraphiQL} from 'graphiql';
import {createTransport} from '@graphiql/toolkit';
import {createLighthouseSubscriptionClient} from './subscriptions';

const container = document.getElementById('graphiql');

if (container) {
    const headers = {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (window.TEAM?.slug) {
        headers['X-Chief-Team'] = window.TEAM.slug;
    }

    // The `api.web` route rejects GET (Lighthouse's EnsureXHR), so pin POST and skip the OPTIONS method discovery
    const transport = createTransport({
        url: container.dataset.endpoint,
        headers,
        method: 'POST',
        supportedMethods: ['POST'],
        subscriptionClient:
            'subscriptions' in container.dataset ? createLighthouseSubscriptionClient({url: container.dataset.endpoint, headers, realtime: window.REALTIME}) : undefined,
    });

    // The shell sets `data-theme` on <html> (resolving 'system' to a concrete value before paint), keep the playground in sync with it
    const forcedTheme = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';

    createRoot(container).render(
        createElement(GraphiQL, {
            transport,
            forcedTheme,
            defaultQuery: '{\n  viewer {\n    name\n  }\n}\n',
            introspectionQueryName: 'playgroundIntrospectionQuery',
            defaultEditorToolsVisibility: true,
        })
    );
}
