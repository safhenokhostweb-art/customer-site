<?php

namespace Tests\Unit;

use App\Helpers\MenuActiveTrail;
use App\Models\MenuItem;
use PHPUnit\Framework\TestCase;

class MenuHelpersTest extends TestCase
{
    public function test_menu_item_utm_key_validation(): void
    {
        $this->assertTrue(MenuItem::isAllowedUtmKey('utm_source'));
        $this->assertTrue(MenuItem::isAllowedUtmKey('utm_custom_field'));
        $this->assertFalse(MenuItem::isAllowedUtmKey('ref'));
        $this->assertFalse(MenuItem::isAllowedUtmKey('evil'));
    }

    public function test_active_trail_path_matching(): void
    {
        $this->assertTrue(MenuActiveTrail::urlMatchesPath('/blog', '/blog'));
        $this->assertTrue(MenuActiveTrail::urlMatchesPath('/blog', '/blog/post-1'));
        $this->assertFalse(MenuActiveTrail::urlMatchesPath('/blog', '/shop'));
    }
}
