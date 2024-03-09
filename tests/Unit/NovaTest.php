<?php

namespace Tests\Unit;

use App\Classes\NovaWhitelist;
use Tests\TestCase;

class NovaTest extends TestCase
{
    public function test_whitelist_none(): void
    {
        $whitelist = '';
        $object = new NovaWhitelist($whitelist);
        $this->assertFalse($object->allow('lester@hurtado.ph'));
    }

    public function test_whitelist_some(): void
    {
        $whitelist = 'lester@hurtado.ph,dene@hurtado.ph';
        $object = new NovaWhitelist($whitelist);
        $this->assertTrue($object->allow('lester@hurtado.ph'));
        $this->assertFalse($object->allow('glen@hurtado.ph'));
    }

    public function test_whitelist_all(): void
    {
        $whitelist = '*';
        $object = new NovaWhitelist($whitelist);
        $this->assertTrue($object->allow('lester@hurtado.ph'));
    }

    public function test_default_whitelist_all(): void
    {
        $whitelist = config('disbursement.nova.whitelist');
        $object = new NovaWhitelist($whitelist);
        $this->assertTrue($object->allow('lester@hurtado.ph'));
    }
}
