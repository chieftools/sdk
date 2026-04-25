@php
    $mailBrandColor = \ChiefTools\SDK\Helpers\MailBrandColor::primary();
    $mailBrandButtonColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryButton();
    $mailBrandButtonTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryButtonText();
    $mailBrandHoverColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryHover();
    $mailBrandButtonHoverColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryButtonHover();
    $mailBrandButtonHoverTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryButtonHoverText();
    $mailBrandDarkHoverColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryDarkHover();
    $mailBrandDarkButtonHoverColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryDarkButtonHover();
    $mailBrandDarkButtonHoverTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryDarkButtonHoverText();
    $mailSuccessButtonTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::successButtonText();
    $mailErrorButtonTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::errorButtonText();
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>{{ config('app.name') }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="color-scheme" content="light dark">
        <meta name="supported-color-schemes" content="light dark">
        <style>
            :root {
                color-scheme: light dark;
                supported-color-schemes: light dark;
            }

            a:hover {
                color: {{ $mailBrandHoverColor }} !important;
            }

            a.button-primary:hover, a.button-blue:hover {
                background-color: {{ $mailBrandButtonHoverColor }} !important;
                border-bottom: 8px solid {{ $mailBrandButtonHoverColor }} !important;
                border-left: 18px solid {{ $mailBrandButtonHoverColor }} !important;
                border-right: 18px solid {{ $mailBrandButtonHoverColor }} !important;
                border-top: 8px solid {{ $mailBrandButtonHoverColor }} !important;
                color: {{ $mailBrandButtonHoverTextColor }} !important;
            }

            a.button-success:hover, a.button-green:hover {
                color: {{ $mailSuccessButtonTextColor }} !important;
            }

            a.button-error:hover, a.button-red:hover {
                color: {{ $mailErrorButtonTextColor }} !important;
            }

            @media only screen and (max-width: 600px) {
                .inner-body {
                    width: 100% !important;
                }

                .footer {
                    width: 100% !important;
                }
            }

            @media only screen and (max-width: 500px) {
                .button {
                    width: 100% !important;
                }
            }

            @media (prefers-color-scheme: dark) {
                .logo.light {
                    display: none !important;
                }

                .logo.dark {
                    display: block !important;
                }

                body, .wrapper, .body {
                    color: #f3f4f6 !important;
                    background-color: #262728 !important;
                }

                h1, h2, h3 {
                    color: #ffffff !important;
                }

                p, ul, ol, blockquote {
                    color: #e5e7eb !important;
                }

                a {
                    color: {{ $mailBrandColor }} !important;
                }

                a.button-primary, a.button-blue {
                    background-color: {{ $mailBrandButtonColor }} !important;
                    border-bottom: 8px solid {{ $mailBrandButtonColor }} !important;
                    border-left: 18px solid {{ $mailBrandButtonColor }} !important;
                    border-right: 18px solid {{ $mailBrandButtonColor }} !important;
                    border-top: 8px solid {{ $mailBrandButtonColor }} !important;
                    color: {{ $mailBrandButtonTextColor }} !important;
                }

                a.button-success, a.button-green, a.button-success:hover, a.button-green:hover {
                    color: {{ $mailSuccessButtonTextColor }} !important;
                }

                a.button-error, a.button-red, a.button-error:hover, a.button-red:hover {
                    color: {{ $mailErrorButtonTextColor }} !important;
                }

                a:hover {
                    color: {{ $mailBrandDarkHoverColor }} !important;
                }

                a.button-primary:hover, a.button-blue:hover {
                    background-color: {{ $mailBrandDarkButtonHoverColor }} !important;
                    border-bottom: 8px solid {{ $mailBrandDarkButtonHoverColor }} !important;
                    border-left: 18px solid {{ $mailBrandDarkButtonHoverColor }} !important;
                    border-right: 18px solid {{ $mailBrandDarkButtonHoverColor }} !important;
                    border-top: 8px solid {{ $mailBrandDarkButtonHoverColor }} !important;
                    color: {{ $mailBrandDarkButtonHoverTextColor }} !important;
                }

                .inner-body {
                    background-color: #212121 !important;
                    border-color: #4b5563 !important;
                    box-shadow: 0 2px 0 rgba(36, 37, 45, 0.025), 2px 4px 0 rgba(36, 37, 45, 0.015) !important;
                }

                .subcopy {
                    border-top-color: #374151 !important;
                }

                .panel {
                    border-left-color: {{ $mailBrandColor }} !important;
                }

                .panel-content {
                    background-color: #2b2c2f !important;
                    color: #d1d5db !important;
                }

                .panel-content p {
                    color: #d1d5db !important;
                }

                blockquote {
                    background-color: transparent !important;
                    border-left-color: {{ $mailBrandColor }} !important;
                    color: #d1d5db !important;
                }

                blockquote p {
                    color: #d1d5db !important;
                }

                .table td, .footer p, .footer a {
                    color: #9aa1ae !important;
                }

                .table th {
                    border-bottom: 1px solid #262728 !important;
                    color: #e5e7eb !important;
                }
            }

            [data-ogsc] .logo.light {
                display: none !important;
            }

            [data-ogsc] .logo.dark {
                display: block !important;
            }

            [data-ogsc] body, [data-ogsc] .wrapper, [data-ogsc] .body {
                color: #f3f4f6 !important;
                background-color: #262728 !important;
            }

            [data-ogsc] h1, [data-ogsc] h2, [data-ogsc] h3 {
                color: #ffffff !important;
            }

            [data-ogsc] p, [data-ogsc] ul, [data-ogsc] ol, [data-ogsc] blockquote {
                color: #e5e7eb !important;
            }

            [data-ogsc] a {
                color: {{ $mailBrandColor }} !important;
            }

            [data-ogsc] a.button-primary, [data-ogsc] a.button-blue {
                background-color: {{ $mailBrandButtonColor }} !important;
                border-bottom: 8px solid {{ $mailBrandButtonColor }} !important;
                border-left: 18px solid {{ $mailBrandButtonColor }} !important;
                border-right: 18px solid {{ $mailBrandButtonColor }} !important;
                border-top: 8px solid {{ $mailBrandButtonColor }} !important;
                color: {{ $mailBrandButtonTextColor }} !important;
            }

            [data-ogsc] a.button-success,
            [data-ogsc] a.button-green,
            [data-ogsc] a.button-success:hover,
            [data-ogsc] a.button-green:hover {
                color: {{ $mailSuccessButtonTextColor }} !important;
            }

            [data-ogsc] a.button-error,
            [data-ogsc] a.button-red,
            [data-ogsc] a.button-error:hover,
            [data-ogsc] a.button-red:hover {
                color: {{ $mailErrorButtonTextColor }} !important;
            }

            [data-ogsc] a:hover {
                color: {{ $mailBrandDarkHoverColor }} !important;
            }

            [data-ogsc] a.button-primary:hover, [data-ogsc] a.button-blue:hover {
                background-color: {{ $mailBrandDarkButtonHoverColor }} !important;
                border-bottom: 8px solid {{ $mailBrandDarkButtonHoverColor }} !important;
                border-left: 18px solid {{ $mailBrandDarkButtonHoverColor }} !important;
                border-right: 18px solid {{ $mailBrandDarkButtonHoverColor }} !important;
                border-top: 8px solid {{ $mailBrandDarkButtonHoverColor }} !important;
                color: {{ $mailBrandDarkButtonHoverTextColor }} !important;
            }

            [data-ogsc] .inner-body {
                background-color: #212121 !important;
                border-color: #4b5563 !important;
                box-shadow: 0 2px 0 rgba(36, 37, 45, 0.025), 2px 4px 0 rgba(36, 37, 45, 0.015) !important;
            }

            [data-ogsc] .subcopy {
                border-top-color: #374151 !important;
            }

            [data-ogsc] .panel {
                border-left-color: {{ $mailBrandColor }} !important;
            }

            [data-ogsc] .panel-content {
                background-color: #2b2c2f !important;
                color: #d1d5db !important;
            }

            [data-ogsc] .panel-content p {
                color: #d1d5db !important;
            }

            [data-ogsc] blockquote {
                background-color: transparent !important;
                border-left-color: {{ $mailBrandColor }} !important;
                color: #d1d5db !important;
            }

            [data-ogsc] blockquote p {
                color: #d1d5db !important;
            }

            [data-ogsc] .table td, [data-ogsc] .footer p, [data-ogsc] .footer a {
                color: #9aa1ae !important;
            }

            [data-ogsc] .table th {
                border-bottom: 1px solid #262728 !important;
                color: #e5e7eb !important;
            }

            @font-face {
                font-display: swap;
                font-family: 'IBM Plex Sans';
                font-style: normal;
                font-weight: 400;
                src: local(''), url('{{ static_asset('fonts/gf-ibm_plex_sans/v19/ibm-plex-sans-v19-latin-regular.woff2') }}') format('woff2')
            }

            @font-face {
                font-display: swap;
                font-family: 'IBM Plex Sans';
                font-style: normal;
                font-weight: 700;
                src: local(''), url('{{ static_asset('fonts/gf-ibm_plex_sans/v19/ibm-plex-sans-v19-latin-700.woff2') }}') format('woff2')
            }
        </style>
        {{ $head ?? '' }}
    </head>
    <body>

        <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td align="center">
                    <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        {{ $header ?? '' }}

                        <!-- Email Body -->
                        <tr>
                            <td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
                                <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                    <!-- Body content -->
                                    <tr>
                                        <td class="content-cell">
                                            {{ Illuminate\Mail\Markdown::parse($slot) }}

                                            {{ $subcopy ?? '' }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        {{ $footer ?? '' }}
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
