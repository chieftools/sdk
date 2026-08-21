<nav class="-mx-5 -my-2 flex flex-wrap justify-center" aria-label="{{ __('chief::ui.footer.label') }}">
    <div class="px-5 py-2">
        <a href="{{ route('chief.blog') }}" class="text-base text-fg-subtle hover:text-fg">
            {{ __('chief::ui.footer.blog') }}
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ chief_docs_url(config('chief.id') . '-footer-link') }}" class="text-base text-fg-subtle hover:text-fg">
            {{ __('chief::ui.footer.docs') }}
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.about') }}" class="text-base text-fg-subtle hover:text-fg">
            {{ __('chief::ui.footer.about') }}
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.contact') }}" class="text-base text-fg-subtle hover:text-fg">
            {{ __('chief::ui.footer.contact') }}
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.legal') }}" class="text-base text-fg-subtle hover:text-fg">
            {{ __('chief::ui.footer.legal') }}
        </a>
    </div>
</nav>
