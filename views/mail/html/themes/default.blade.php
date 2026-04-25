@php
    $mailBrandColor = \ChiefTools\SDK\Helpers\MailBrandColor::primary();
    $mailBrandButtonColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryButton();
    $mailBrandButtonTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::primaryButtonText();
    $mailSuccessColor = \ChiefTools\SDK\Helpers\MailBrandColor::success();
    $mailSuccessButtonTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::successButtonText();
    $mailErrorColor = \ChiefTools\SDK\Helpers\MailBrandColor::error();
    $mailErrorButtonTextColor = \ChiefTools\SDK\Helpers\MailBrandColor::errorButtonText();
    $mailWarningColor = '#b45309';
    $mailMutedColor = '#4b5563';
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
    margin: 0;
}

.subcopy p {
    font-size: 14px;
    margin-bottom: 8px;
}

.subcopy p:last-child {
    margin-bottom: 0;
}

.subcopy-card {
    margin-top: 12px;
}

.content-cell.subcopy-card-content {
    padding: 18px 32px;
}

/* Quick Links */

.quick-links-card {
    margin-top: 12px;
}

.content-cell.quick-links-card-content {
    padding: 12px 32px;
}

.quick-links {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    width: 100%;
}

.quick-links-label {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.4;
    padding-right: 12px;
    white-space: nowrap;
    width: 1%;
}

.quick-links-items {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.4;
}

.quick-links-items a {
    font-weight: normal;
}

.quick-links-separator {
    color: #9ca3af;
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

/* Dividers */

.divider {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 12px auto;
    width: 100%;
}

.divider-line {
    font-size: 0;
    line-height: 0;
    vertical-align: middle;
    width: 50%;
}

.divider-rule {
    border-top: 1px solid #e5e7eb;
    font-size: 0;
    line-height: 0;
}

.divider-label {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.2;
    padding: 0 12px;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    width: 1%;
}

.divider-label p {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.2;
    margin: 0;
    text-align: center;
    white-space: nowrap;
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

/* Bands */

.band {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: calc(100% + 64px);
    margin: 24px 0;
    margin-left: -32px;
    margin-right: -32px;
    width: calc(100% + 64px);
}

.band-content {
    background-color: {{ $mailBrandButtonColor }};
    color: {{ $mailBrandButtonTextColor }};
    padding: 20px 32px;
}

.band-content h1,
.band-content h2,
.band-content h3,
.band-content p,
.band-content ul,
.band-content ol,
.band-content li,
.band-content strong,
.band-content a {
    color: inherit;
}

.band-content p {
    margin-bottom: 8px;
}

.band-content > *:last-child,
.band-content p:last-child {
    margin-bottom: 0;
}

.band-content a {
    text-decoration: underline;
}

.band-success .band-content {
    background-color: {{ $mailSuccessColor }};
    color: {{ $mailSuccessButtonTextColor }};
}

.band-error .band-content {
    background-color: {{ $mailErrorColor }};
    color: {{ $mailErrorButtonTextColor }};
}

.band-warning .band-content {
    background-color: {{ $mailWarningColor }};
    color: #ffffff;
}

.band-muted .band-content {
    background-color: {{ $mailMutedColor }};
    color: #ffffff;
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
