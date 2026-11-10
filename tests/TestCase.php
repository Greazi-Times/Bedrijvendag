<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Never write the erasure ledger into the real storage directory during tests.
        config(['privacy.erasure_ledger.path' => sys_get_temp_dir().'/erasure-ledger-'.bin2hex(random_bytes(6)).'.jsonl']);
        \App\Support\Privacy\EmailCrypto::flush();
    }

    protected function tearDown(): void
    {
        @unlink((string) config('privacy.erasure_ledger.path'));
        \App\Support\Privacy\EmailCrypto::flush();

        parent::tearDown();
    }
}
