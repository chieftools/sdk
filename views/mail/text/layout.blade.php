{!! strip_tags($header ?? '') !!}

{!! strip_tags($slot) !!}
@isset($subcopy)

{!! strip_tags($subcopy) !!}
@endisset
@isset($quickLinks)

{!! strip_tags($quickLinks) !!}
@endisset

{!! strip_tags($footer ?? '') !!}
