@php
    $mailBrandColor = \ChiefTools\SDK\Helpers\MailBrandColor::primary();
    $mailBrandButtonColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryButton();
    $mailBrandButtonTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryButtonText();
    $mailSuccessColor = \ChiefTools\SDK\Helpers\MailBrandColor::success();
    $mailSuccessButtonTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::successButtonText();
    $mailErrorColor = \ChiefTools\SDK\Helpers\MailBrandColor::error();
    $mailErrorButtonTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::errorButtonText();
@endphp

/* Base */

body,
body *:not(html):not(style):not(br):not(tr):not(code) {
    box-sizing: border-box;
    font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
    position: relative;
}

body {
    -webkit-text-size-adjust: none;
    background-color: #f3f4f6;
    color: #374151;
    height: 100%;
    line-height: 1.4;
    margin: 0;
    padding: 0;
    width: 100% !important;
}

p,
ul,
ol,
blockquote {
    line-height: 1.4;
    text-align: left;
}

a {
    color: {{ $mailBrandColor }};
}

a img {
    border: none;
}

/* Typography */

h1 {
    color: #111827;
    font-size: 18px;
    font-weight: bold;
    margin-top: 0;
    text-align: left;
}

h2 {
    font-size: 16px;
    font-weight: bold;
    margin-top: 0;
    text-align: left;
}

h3 {
    font-size: 14px;
    font-weight: bold;
    margin-top: 0;
    text-align: left;
}

p {
    font-size: 16px;
    line-height: 1.5em;
    margin-top: 0;
    text-align: left;
}

blockquote {
    background-color: transparent;
    border-left: 3px solid {{ $mailBrandColor }};
    color: #6b7280;
    font-style: italic;
    margin: 24px 0;
    padding: 4px 0 4px 16px;
}

blockquote p {
    color: #6b7280;
    font-style: italic;
    margin-bottom: 0;
}

p.sub {
    font-size: 12px;
}

img {
    max-width: 100%;
}

/* Layout */

.wrapper {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: #f3f4f6;
    margin: 0;
    padding: 0;
    width: 100%;
}

.content {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 0;
    padding: 0;
    width: 100%;
}

/* Header */

.header {
    padding: 25px 0;
    text-align: center;
}

.header a {
    color: #111827;
    font-size: 19px;
    font-weight: bold;
    text-decoration: none;
}

/* Logo */

.logo {
    height: 50px;
    max-height: 50px;
}

/* Body */

.body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: #f3f4f6;
    border-bottom: 1px solid #edf2f7;
    border-top: 1px solid #edf2f7;
    margin: 0;
    padding: 0;
    width: 100%;
}

.inner-body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    background-color: #ffffff;
    border-color: #e8e5ef;
    border-radius: 2px;
    border-width: 1px;
    box-shadow: 0 2px 0 rgba(0, 0, 150, 0.025), 2px 4px 0 rgba(0, 0, 150, 0.015);
    margin: 0 auto;
    padding: 0;
    width: 570px;
}

.inner-body a {
    word-break: break-all;
}

/* Subcopy */

.subcopy {
    border-top: 1px solid #e8e5ef;
    margin-top: 25px;
    padding-top: 25px;
}

.subcopy p {
    font-size: 14px;
}

/* Footer */

.footer {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    margin: 0 auto;
    padding: 0;
    text-align: center;
    width: 570px;
}

.footer p {
    color: #b0adc5;
    font-size: 12px;
    text-align: center;
}

.footer a {
    color: #b0adc5;
    text-decoration: underline;
}

/* Tables */

.table table {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 30px auto;
    width: 100%;
}

.table th {
    border-bottom: 1px solid #edeff2;
    margin: 0;
    padding-bottom: 8px;
}

.table td {
    color: #74787e;
    font-size: 15px;
    line-height: 18px;
    margin: 0;
    padding: 10px 0;
}

.content-cell {
    max-width: 100vw;
    padding: 32px;
}

.content-cell > *:last-child {
    margin-bottom: 0;
}

/* Buttons */

.action {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 30px auto;
    padding: 0;
    text-align: center;
    width: 100%;
    float: unset;
}

.button {
    -webkit-text-size-adjust: none;
    border-radius: 4px;
    color: #ffffff;
    display: inline-block;
    overflow: hidden;
    text-decoration: none;
}

.button-blue,
.button-primary {
    background-color: {{ $mailBrandButtonColor }};
    border-bottom: 8px solid {{ $mailBrandButtonColor }};
    border-left: 18px solid {{ $mailBrandButtonColor }};
    border-right: 18px solid {{ $mailBrandButtonColor }};
    border-top: 8px solid {{ $mailBrandButtonColor }};
    color: {{ $mailBrandButtonTextColor }};
}

.button-green,
.button-success {
    background-color: {{ $mailSuccessColor }};
    border-bottom: 8px solid {{ $mailSuccessColor }};
    border-left: 18px solid {{ $mailSuccessColor }};
    border-right: 18px solid {{ $mailSuccessColor }};
    border-top: 8px solid {{ $mailSuccessColor }};
    color: {{ $mailSuccessButtonTextColor }};
}

.button-red,
.button-error {
    background-color: {{ $mailErrorColor }};
    border-bottom: 8px solid {{ $mailErrorColor }};
    border-left: 18px solid {{ $mailErrorColor }};
    border-right: 18px solid {{ $mailErrorColor }};
    border-top: 8px solid {{ $mailErrorColor }};
    color: {{ $mailErrorButtonTextColor }};
}

/* Panels */

.panel {
    border-left: {{ $mailBrandColor }} solid 4px;
    margin: 21px 0;
}

.panel-content {
    background-color: #f3f4f6;
    color: #718096;
    padding: 16px;
}

.panel-content p {
    color: #718096;
}

.panel-item {
    padding: 0;
}

.panel-item p:last-of-type {
    margin-bottom: 0;
    padding-bottom: 0;
}

/* Utilities */

.break-all {
    word-break: break-all;
}
