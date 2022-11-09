<script async src="https://customer-chat.cdn-plain.com/latest/customerChat.js"></script>
<script>
    const plainCustomerJwt = @json(auth()->user()?->getPlainCustomerJwtToken());

    window.$plain = window.$plain || [];

    function plain() {
        $plain.push(arguments);
    }

    plain("init", {
        appKey: @json(config('services.plain.app_key'))
    });
    plain("set-theme", { brandColor: @json(config('chief.brand.color', '#34495e')) });
    plain("set-customer", {
        type:           plainCustomerJwt === null ? "logged-out" : "logged-in",
        getCustomerJwt: () => plainCustomerJwt
    });
</script>
