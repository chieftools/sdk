@extends('chief::layout.account', ['title' => 'Preferences'])

@section('maincontent')
    @component('chief::components.page-header', ['nomargin' => true])
        <i class="fad fa-fw fa-cog text-muted"></i> Preferences</small>
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-12">
            @foreach($categories as $categoryKey => $preferences)
                @php($category = config("chief.preference_categories.{$categoryKey}"))

                <div class="card mb-4">
                    <div class="card-header">
                        {{ $category['name'] }}
                    </div>

                    @foreach($preferences as $preference => [$title, $description, $icon, $default])
                        <div class="card-body">
                            <div class="clearfix">
                                <div class="float-right toggleswitch">
                                    <input type="checkbox" {{ (bool)$user->getPreference($preference, $default) ? 'checked="checked"' : '' }} name="preference:{{ $preference }}" id="preference_{{ str_slug($preference) }}">
                                    <label for="preference_{{ str_slug($preference) }}"><i></i></label>
                                </div>

                                <i class="fal fa-fw fa-{{ $icon }} text-muted"></i> {!! $title !!}

                                <p class="mb-0 mt-2 text-muted">
                                    {!! $description !!}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="card mb-4">
                <div class="card-body">
                    <p class="mb-0 mt-0 text-muted font-italic">
                        <i class="fal fa-fw fa-question-circle"></i> Missing something you would like to configure? <a href="{{ route('chief.contact') }}">Tell us!</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body.script')
    <script>
        (function () {
            jQuery('input[name*="preference:"]').change(function () {
                var input = jQuery(this);

                jQuery.post('{{ route('account.preferences.toggle') }}', {
                    identity: input.attr('name'),
                    state:    input.is(':checked') ? 1 : 0,
                });
            });
        })();
    </script>
@endpush
