<nav class="-mx-5 -my-2 flex flex-wrap justify-center" aria-label="Footer">
    <div class="px-5 py-2">
        <a href="{{ route('chief.blog') }}" class="text-base text-fg-subtle hover:text-fg">
            Blog
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ chief_docs_url(config('chief.id') . '-footer-link') }}" class="text-base text-fg-subtle hover:text-fg">
            Docs
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.about') }}" class="text-base text-fg-subtle hover:text-fg">
            About
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.contact') }}" class="text-base text-fg-subtle hover:text-fg">
            Contact
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.legal') }}" class="text-base text-fg-subtle hover:text-fg">
            Legal
        </a>
    </div>
</nav>
