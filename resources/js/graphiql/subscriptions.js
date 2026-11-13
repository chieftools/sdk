/*
  GraphiQL `subscriptionClient` for Lighthouse subscriptions broadcast over the
  Pusher protocol (the same flow as the apps' Apollo `PusherLink`):

   1. connect to the socket server and wait for a socket id
   2. POST the subscription with the `chief-socket-id` header, the response
      carries the channel in `extensions.lighthouse_subscriptions.channel` and
      a pre-signed channel authorization in `extensions.chief_socket.auth`
   3. subscribe to the private channel and yield the `result` of every
      `lighthouse-subscription` event until the server sends `more: false`
      or GraphiQL stops the subscription
*/
import Pusher from 'pusher-js';

const AUTHENTICATE_CHANNEL_QUERY = `query playgroundAuthenticateBroadcastChannel($input: AuthenticateBroadcastChannelInput!) {
    result: authenticateBroadcastChannel(input: $input) { token data }
}`;

export function createLighthouseSubscriptionClient({url, headers, realtime}) {
    let pusher = null;
    const presignedAuths = {};

    async function post(body, extraHeaders = {}) {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                ...headers,
                ...extraHeaders,
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
        });

        return response.json();
    }

    function authorizeChannel({channelName, socketId}, callback) {
        if (presignedAuths[channelName]) {
            const auth = presignedAuths[channelName];

            delete presignedAuths[channelName];

            callback(null, JSON.parse(auth));

            return;
        }

        post({query: AUTHENTICATE_CHANNEL_QUERY, variables: {input: {channel: channelName, socketId}}})
            .then(({data}) => {
                if (!data?.result) {
                    throw new Error(`Could not authorize the socket for the ${channelName} channel.`);
                }

                callback(null, {auth: data.result.token, channel_data: data.result.data ?? undefined});
            })
            .catch(error => callback(error, null));
    }

    function connect() {
        if (pusher === null) {
            pusher = new Pusher(realtime.APPID, {
                wsHost: realtime.HOST || undefined,
                wsPort: realtime.WSPORT || undefined,
                wssPort: realtime.WSPORT || undefined,
                cluster: 'eu',
                forceTLS: true,
                enableStats: false,
                enabledTransports: ['ws', 'wss'],
                channelAuthorization: {customHandler: authorizeChannel},
            });
        }

        return pusher;
    }

    function socketId() {
        const connection = connect().connection;

        if (connection.state === 'connected') {
            return Promise.resolve(connection.socket_id);
        }

        return new Promise((resolve, reject) => {
            const onStateChange = ({current}) => {
                if (current === 'connected') {
                    connection.unbind('state_change', onStateChange);
                    resolve(connection.socket_id);
                } else if (current === 'failed' || current === 'unavailable') {
                    connection.unbind('state_change', onStateChange);
                    reject(new Error(`Could not connect to the realtime server (${current}).`));
                }
            };

            connection.bind('state_change', onStateChange);
        });
    }

    return {
        iterate(request) {
            const results = [];
            const waiting = [];
            let channel = null;
            let finished = false;
            let failure = null;

            // Hand out queued results to pending `next()` calls, and end them once the subscription finished or failed
            function flush() {
                while (waiting.length > 0 && results.length > 0) {
                    waiting.shift().resolve({value: results.shift(), done: false});
                }

                while (waiting.length > 0 && (finished || failure)) {
                    const {resolve, reject} = waiting.shift();

                    if (failure) {
                        reject(failure);
                        failure = null;
                        finished = true;
                    } else {
                        resolve({value: undefined, done: true});
                    }
                }
            }

            function finish() {
                if (channel !== null) {
                    pusher.unsubscribe(channel);
                    channel = null;
                }

                finished = true;
                flush();
            }

            async function start() {
                if (!realtime?.ENABLED || !realtime?.APPID) {
                    throw new Error('Realtime updates are not enabled for this application, so subscriptions cannot be received.');
                }

                const result = await post(request, {'chief-socket-id': await socketId()});

                const subscriptionChannel = result?.extensions?.lighthouse_subscriptions?.channel;

                // No channel means the subscription was refused (or was not a subscription at all), show the response as is
                if (!subscriptionChannel) {
                    results.push(result);
                    finish();

                    return;
                }

                // GraphiQL stopped the subscription while it was being set up
                if (finished) {
                    return;
                }

                if (result.extensions.chief_socket?.auth) {
                    presignedAuths[subscriptionChannel] = result.extensions.chief_socket.auth;
                }

                channel = subscriptionChannel;

                const subscription = pusher.subscribe(channel);

                subscription.bind('pusher:subscription_error', ({error}) => {
                    failure = new Error(error ?? `Could not subscribe to the ${channel} channel.`);
                    finish();
                });

                subscription.bind('lighthouse-subscription', payload => {
                    if (payload.result) {
                        results.push(payload.result);
                    }

                    if (payload.more) {
                        flush();
                    } else {
                        finish();
                    }
                });
            }

            start().catch(error => {
                failure = error;
                finish();
            });

            return {
                next() {
                    return new Promise((resolve, reject) => {
                        waiting.push({resolve, reject});
                        flush();
                    });
                },
                return() {
                    finish();

                    return Promise.resolve({value: undefined, done: true});
                },
                [Symbol.asyncIterator]() {
                    return this;
                },
            };
        },
    };
}
