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

                h1, a.button {
                    color: #ffffff !important;
                }

                a {
                    color: #7698ff !important;
                }

                a.button {
                    background-color: #016baa !important;
                    border-bottom: 8px solid #016baa !important;
                    border-left: 18px solid #016baa !important;
                    border-right: 18px solid #016baa !important;
                    border-top: 8px solid #016baa !important;
                }

                .inner-body {
                    background-color: #212121 !important;
                    border-color: #4b5563 !important;
                    box-shadow: 0 2px 0 rgba(36, 37, 45, 0.025), 2px 4px 0 rgba(36, 37, 45, 0.015) !important;
                }

                .table td, .footer p, .footer a {
                    color: #9aa1ae !important;
                }

                .table th {
                    border-bottom: 1px solid #262728 !important;
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

            [data-ogsc] h1, [data-ogsc] a.button {
                color: #ffffff !important;
            }

            [data-ogsc] a {
                color: #7698ff !important;
            }

            [data-ogsc] a.button {
                background-color: #016baa !important;
                border-bottom: 8px solid #016baa !important;
                border-left: 18px solid #016baa !important;
                border-right: 18px solid #016baa !important;
                border-top: 8px solid #016baa !important;
            }

            [data-ogsc] .inner-body {
                background-color: #212121 !important;
                border-color: #4b5563 !important;
                box-shadow: 0 2px 0 rgba(36, 37, 45, 0.025), 2px 4px 0 rgba(36, 37, 45, 0.015) !important;
            }

            [data-ogsc] .table td, [data-ogsc] .footer p, [data-ogsc] .footer a {
                color: #9aa1ae !important;
            }

            [data-ogsc] .table th {
                border-bottom: 1px solid #262728 !important;
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
