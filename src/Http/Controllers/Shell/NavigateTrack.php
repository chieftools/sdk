<?php

namespace ChiefTools\SDK\Http\Controllers\Shell;

use Illuminate\Http\Response;

class NavigateTrack
{
    public function __invoke(): Response
    {
        return response('', 200, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Type'  => 'text/css; charset=UTF-8',
        ]);
    }
}
