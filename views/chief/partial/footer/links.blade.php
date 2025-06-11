<nav class="-mx-5 -my-2 flex flex-wrap justify-center" aria-label="Footer">
    <div class="px-5 py-2">
        <a href="{{ route('chief.blog') }}" class="text-base text-gray-500 hover:text-gray-900">
            Blog
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ chief_docs_url(config('chief.id') . '-footer-link') }}" class="text-base text-gray-500 hover:text-gray-900">
            Docs
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.about') }}" class="text-base text-gray-500 hover:text-gray-900">
            About
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.contact') }}" class="text-base text-gray-500 hover:text-gray-900">
            Contact
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.privacy') }}" class="text-base text-gray-500 hover:text-gray-900">
            Privacy
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.terms') }}" class="text-base text-gray-500 hover:text-gray-900">
            Terms
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.abuse') }}" class="text-base text-gray-500 hover:text-gray-900">
            Abuse
        </a>
    </div>
</nav>
