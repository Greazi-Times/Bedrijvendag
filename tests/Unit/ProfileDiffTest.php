<?php

use App\Support\ProfileDiff;

test('word differences mark only removed and added words and escape content', function () {
    $diff = ProfileDiff::render('name', ['before' => 'We build old robots.', 'after' => 'We build smart robots.']);
    expect((string) $diff['before'])->toContain('We build <del class="profile-diff-del" title="Removed">old</del> robots.')
        ->and((string) $diff['after'])->toContain('We build <ins class="profile-diff-ins" title="Added">smart</ins> robots.');

    $unsafe = ProfileDiff::render('name', ['before' => '', 'after' => '<script>alert(1)</script>']);
    expect((string) $unsafe['after'])->not->toContain('<script>')->toContain('&lt;script&gt;');
});

test('list differences preserve whole names including commas and mark removals', function () {
    $diff = ProfileDiff::render('educations', [
        'before' => 'ICT, Design, engineering', 'after' => 'ICT, Mechatronica',
        'before_items' => ['ICT', 'Design, engineering'], 'after_items' => ['ICT', 'Mechatronica'],
    ]);
    expect((string) $diff['before'])->toBe('ICT<br><del class="profile-diff-del" title="Removed">Design, engineering</del>')
        ->and((string) $diff['after'])->toBe('ICT<br><ins class="profile-diff-ins" title="Added">Mechatronica</ins>');
});

test('description diff compares visible words and handles complete removal', function () {
    $diff = ProfileDiff::render('description', ['before' => '<p>Hello <strong>world</strong></p>', 'after' => '<p>Hello <em>team</em></p>']);
    expect((string) $diff['before'])->toContain('>world</del>')->not->toContain('&lt;strong')
        ->and((string) $diff['after'])->toContain('>team</ins>');
    $removed = ProfileDiff::render('description', ['before' => '<p>Gone</p>', 'after' => null]);
    expect((string) $removed['before'])->toContain('>Gone</del>')
        ->and(strip_tags((string) $removed['after']))->toBe('');
});

test('unchanged and repeated words remain in order', function () {
    $diff = ProfileDiff::render('name', ['before' => 'one two one', 'after' => 'one one']);
    expect(strip_tags((string) $diff['before']))->toBe('one two one')
        ->and(strip_tags((string) $diff['after']))->toBe('one one');
    $same = ProfileDiff::render('name', ['before' => 'Same', 'after' => 'Same']);
    expect((string) $same['before'])->not->toContain('<del', '<ins');
});
