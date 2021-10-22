<div class="page-header {{ isset($nomargin) ? 'mt-0' : ((empty($breadcrumbs) ? 'mt-md-5 mt-2' : 'mt-md-4 mt-2')) }} mb-4">
    @if(!empty($breadcrumbs))
        <div class="breadcrumbs text-muted">
            <a href="{{ route('dashboard') }}" class="d-inline-block">
                <i class="fal fa-fw fa-home"></i>
            </a> /

            @foreach($breadcrumbs as [$title, $link])
                @if($loop->last && empty($link))
                    <span class="d-inline-block">{{ $title }}</span>
                @else
                    <a href="{{ $link }}" class="d-inline-block">
                        {{ $title }}
                    </a>@if(!$loop->last) / @endif
                @endif
            @endforeach
        </div>
    @endif
    <h1 class="float-left">
        {!! $slot !!}

        @if(!empty($search) && request()->filled('query'))
            <small>&mdash; <em>"{{ str_limit(request('query'), 32) }}"</em></small>
        @endif
    </h1>

    <br class="d-md-none d-lg-none d-xl-none">
    <br class="d-md-none d-lg-none d-xl-none">

    @if(!empty($actions) || !empty($search))
        <div class="d-inline-block float-md-right">
            @if(!empty($actions))
                @foreach($actions as $action)
                    @if(array_get($action, 'ignore', false))
                        @continue
                    @endif

                    @if(array_get($action, 'actions', false))
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-sm btn-outline-{{ array_get($action, 'type') ?? 'primary' }} d-inline-block" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                    {!! !empty($search) ? 'style="margin-top: -2px;"' : '' !!}>
                                <i class="fal fa-fw {{ array_get($action, 'icon') }}"></i> {{ array_get($action, 'text') }}
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                @foreach($action['actions'] as $action)
                                    @if(array_get($action, 'ignore', false))
                                        @continue
                                    @endif

                                    <a href="{{ array_get($action, 'action') }}" class="dropdown-item" {!! (($confirm = array_get($action, 'confirm')) !== null) ? 'data-confirm="' . ($confirm ? 'true' : 'false') . '" data-method="' . array_get($action, 'method', 'post') . '"' : '' !!}>
                                        <i class="fal fa-fw {{ array_get($action, 'icon') }}"></i> {{ array_get($action, 'text') }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ array_get($action, 'action') }}"
                           class="btn btn-sm btn-outline-{{ array_get($action, 'type') ?? 'primary' }} d-inline-block"
                           {!! !empty($search) ? 'style="margin-top: -2px;"' : '' !!}
                           {!! (($confirm = array_get($action, 'confirm')) !== null) ? 'data-confirm="' . ($confirm ? 'true' : 'false') . '" data-method="' . array_get($action, 'method', 'post') . '"' : '' !!}
                           {!! (($tooltip = array_get($action, 'tooltip')) !== null) ? 'data-title="' . $tooltip . '" data-toggle="tooltip"' : '' !!}>
                            <i class="fal fa-fw {{ array_get($action, 'icon') }}"></i> {{ array_get($action, 'text') }}
                        </a>
                    @endif
                @endforeach
            @endif

            @if(!empty($search))
                @include('components.header-search')
            @endif
        </div>
    @endif

    @if(isset($subtitle))
        <p class="subtitle text-muted d-none d-md-block">
            {!! $subtitle !!}
        </p>
    @endif

    <div class="clearfix"></div>
</div>
