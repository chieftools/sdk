<?php

use Tests\TestCase;
use Illuminate\Mail\Markdown;
use ChiefTools\SDK\Helpers\MailBrandColor;

uses(TestCase::class);

test('mail brand color normalizes configured hex values', function () {
    expect(MailBrandColor::normalize('#ABC'))->toBe('#aabbcc');
    expect(MailBrandColor::normalize('ABC'))->toBe('#aabbcc');
    expect(MailBrandColor::normalize('#123456'))->toBe('#123456');
    expect(MailBrandColor::normalize('123456'))->toBe('#123456');
});

test('mail brand color falls back for invalid values', function () {
    expect(MailBrandColor::normalize(null))->toBe('#34495e');
    expect(MailBrandColor::normalize('var(--brand)'))->toBe('#34495e');
    expect(MailBrandColor::normalize('rgb(1, 2, 3)'))->toBe('#34495e');
});

test('mail brand color chooses readable button text', function () {
    expect(MailBrandColor::readableTextFor('#111827'))->toBe('#ffffff');
    expect(MailBrandColor::readableTextFor('#f4c430'))->toBe('#111827');
});

test('mail brand color derives button and hover colors from configured brand color', function () {
    config(['chief.brand.color' => '#F4C430']);

    expect(MailBrandColor::primary())->toBe('#f4c430');
    expect(MailBrandColor::primaryButton())->toBe('#e0ac0c');
    expect(MailBrandColor::primaryButtonText())->toBe('#111827');
    expect(MailBrandColor::primaryHover())->toBe('#e0ac0c');
    expect(MailBrandColor::primaryButtonHover())->toBe('#c3960a');
    expect(MailBrandColor::primaryButtonHoverText())->toBe('#111827');
    expect(MailBrandColor::primaryDarkHover())->toBe('#f6cd51');
    expect(MailBrandColor::primaryDarkButtonHover())->toBe('#e5b933');
    expect(MailBrandColor::primaryDarkButtonHoverText())->toBe('#111827');
});

test('mail brand color derives expected blue 600 button color', function () {
    config(['chief.brand.color' => '#3498DB']);

    expect(MailBrandColor::primary())->toBe('#3498db');
    expect(MailBrandColor::primaryButton())->toBe('#207ab7');
    expect(MailBrandColor::primaryButtonText())->toBe('#ffffff');
});

test('markdown mail uses the brand color for primary accents', function () {
    config([
        'chief.brand.color'    => '#F4C430',
        'mail.markdown.paths'  => [dirname(__DIR__, 2) . '/views/mail'],
    ]);

    app()->forgetInstance(Markdown::class);
    app('view')->addLocation(dirname(__DIR__) . '/Fixtures/views');

    $html = (string)app(Markdown::class)->render('mail-brand-color-email');

    expect($html)
        ->toContain('#f4c430')
        ->toContain('background-color: #e0ac0c')
        ->toContain('border-left: 18px solid #e0ac0c')
        ->toContain('color: #111827')
        ->toContain('a:hover')
        ->toContain('a.button-primary:hover')
        ->toContain('#c3960a')
        ->toContain('#f6cd51')
        ->toContain('#e5b933')
        ->toContain('h1, h2, h3')
        ->toContain('p, ul, ol, blockquote')
        ->toContain('.subcopy')
        ->toContain('subcopy-card')
        ->toContain('subcopy-card-content')
        ->toContain('quick-links-card')
        ->toContain('quick-links-card-content')
        ->toContain('Quick links:')
        ->toContain('Dashboard')
        ->toContain('Notification settings')
        ->toContain('.divider')
        ->toContain('divider-line')
        ->toContain('divider-rule')
        ->toContain('divider-label')
        ->toContain('Replaced by')
        ->toContain('.panel')
        ->toContain('.band')
        ->toContain('band-muted')
        ->toContain('band-success')
        ->toContain('calc(100% + 64px)')
        ->toContain('margin-left: -32px')
        ->toContain('padding: 20px 32px')
        ->toContain('background-color: #4b5563')
        ->toContain('background-color: #047857')
        ->toContain('border-left: #f4c430 solid 4px')
        ->toContain('border-left-color: #f4c430')
        ->toContain('background-color: #2b2c2f')
        ->toContain('.table th')
        ->toContain('<blockquote')
        ->toContain('border-left: 3px solid #f4c430')
        ->toContain('background-color: transparent')
        ->toContain('font-style: italic')
        ->toContain('blockquote p')
        ->toContain('Quoted guidance')
        ->toContain('[data-ogsc] blockquote')
        ->not->toContain('#3498db')
        ->not->toContain('#016baa')
        ->not->toContain('#7698ff');
});

test('markdown mail leaves semantic success and error buttons unchanged', function () {
    config([
        'chief.brand.color'    => '#F4C430',
        'mail.markdown.paths'  => [dirname(__DIR__, 2) . '/views/mail'],
    ]);

    app()->forgetInstance(Markdown::class);

    expect(render_mail_brand_color_test_email('success'))
        ->toContain('#047857')
        ->toContain('a.button-success')
        ->toContain('color: #ffffff');

    expect(render_mail_brand_color_test_email('error'))
        ->toContain('#b91c1c')
        ->toContain('a.button-error')
        ->toContain('color: #ffffff');
});

function render_mail_brand_color_test_email(string $level = 'info'): string
{
    return (string)app(Markdown::class)->render('notifications::email', [
        'level'                => $level,
        'greeting'             => 'Hello',
        'introLines'           => ['Use the action below.'],
        'outroLines'           => ['Thanks for using the SDK.'],
        'actionText'           => 'Continue',
        'actionUrl'            => 'https://example.com/action',
        'displayableActionUrl' => 'example.com/action',
        'salutation'           => null,
    ]);
}
