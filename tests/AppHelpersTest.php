<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../utils/app_helpers.php';

class AppHelpersTest extends TestCase
{
    public function testRoleToReadableAdmin(): void
    {
        $this->assertEquals('Administrator', role_to_readable('admin'));
    }

    public function testRoleToReadableOwner(): void
    {
        $this->assertEquals('Președinte', role_to_readable('owner'));
    }

    public function testRoleToReadableMember(): void
    {
        $this->assertEquals('Membru', role_to_readable('member'));
    }

    public function testRoleToReadableViewer(): void
    {
        $this->assertEquals('Vizualizator', role_to_readable('viewer'));
    }

    public function testRoleToReadableUnknownRole(): void
    {
        $this->assertEquals('Unknown', role_to_readable('unknown'));
    }

    public function testRoleToReadableNull(): void
    {
        $this->assertEquals('N/A', role_to_readable(null));
    }
}
