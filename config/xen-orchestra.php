<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Appliance URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your XOA, without the API path. The `/rest/v0` prefix is
    | appended automatically, so both of these work:
    |
    |   https://xo.example.com
    |   https://xo.example.com/rest/v0
    |
    */

    'url' => env('XO_URL'),

    /*
    |--------------------------------------------------------------------------
    | Authentication token
    |--------------------------------------------------------------------------
    |
    | Create one in the XO web interface under your user's settings, or via
    | POST /rest/v0/users/<id>/authentication_tokens.
    |
    | Note this travels as an `authenticationToken` cookie rather than a Bearer
    | header — the package handles that for you, but it explains why a token
    | that "should work" fails when you try it by hand with curl -H.
    |
    */

    'token' => env('XO_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | TLS verification
    |--------------------------------------------------------------------------
    |
    | Internal XOA deployments very often use a self-signed certificate, and
    | setting this to false is the quickest way past that. It also disables the
    | protection against someone impersonating your appliance, so prefer
    | pointing this at a CA bundle path when you can:
    |
    |   'verify_ssl' => storage_path('certs/xo-ca.pem'),
    |
    */

    'verify_ssl' => env('XO_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Timeouts (seconds)
    |--------------------------------------------------------------------------
    |
    | `timeout` covers ordinary requests. `wait_timeout` is the default budget
    | for Task::wait(), which long-polls XO and can legitimately sit there for
    | minutes during a rolling update or a large snapshot.
    |
    */

    'timeout' => (int) env('XO_TIMEOUT', 30),

    'connect_timeout' => (int) env('XO_CONNECT_TIMEOUT', 10),

    'wait_timeout' => (int) env('XO_WAIT_TIMEOUT', 300),

];
