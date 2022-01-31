<nav class="-mx-5 -my-2 flex flex-wrap justify-center" aria-label="Footer">
    <div class="px-5 py-2">
        <a href="/" class="text-base text-gray-500 hover:text-gray-900">
            Home
        </a>
    </div>

    <div class="px-5 py-2">
        <a href="{{ route('chief.contact') }}" class="text-base text-gray-500 hover:text-gray-900">
            Contact
        </a>
    </div>

    @guest
        <div class="px-5 py-2">
            <a href="{{ route('auth.login') }}" class="text-base text-gray-500 hover:text-gray-900">
                Sign in
            </a>
        </div>

        <div class="px-5 py-2">
            <a href="{{ route('auth.register') }}" class="text-base text-gray-500 hover:text-gray-900">
                Sign up
            </a>
        </div>
    @endguest

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
</nav>
