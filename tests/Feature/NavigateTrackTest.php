<?php

use Illuminate\Support\Facades\Blade;

test('the navigate track asset is available as an empty stylesheet', function () {
    config(['app.key' => 'base64:oE72uRMtvwHlVTVBthR+K3FBDmSqNXTevcEU2LtLqrw=']);

    $this->get('/chief/navigate-track.css')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/css; charset=UTF-8')
        ->assertContent('');
});

test('the chief layout tracks application version changes for Livewire navigate', function () {
    config([
        'app.key'     => 'base64:oE72uRMtvwHlVTVBthR+K3FBDmSqNXTevcEU2LtLqrw=',
        'app.version' => 'release-123',
    ]);

    $html = Blade::render(<<<'BLADE'
        @extends('chief::layout.html', ['title' => 'Test'])
        @section('styles')
            <style></style>
        @endsection
        @section('body')
            Body
        @endsection
        @section('scripts')
            <script></script>
        @endsection
    BLADE);

    expect($html)
        ->toContain('/chief/navigate-track.css?id=release-123')
        ->toContain('data-navigate-track');
});
