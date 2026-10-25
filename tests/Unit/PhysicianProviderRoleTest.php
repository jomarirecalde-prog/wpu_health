<?php

namespace Tests\Unit;

use App\Models\Physician;
use PHPUnit\Framework\TestCase;

class PhysicianProviderRoleTest extends TestCase
{
    public function test_dmd_title_is_treated_as_dentist(): void
    {
        $provider = new Physician([
            'professional_title' => 'DMD',
            'name' => 'Jomari B. Recalde',
            'provider_category' => 'physician',
        ]);

        $this->assertTrue($provider->isDentist());
        $this->assertSame('dentist', $provider->clinicalPortalRole());
    }

    public function test_provider_category_dentist(): void
    {
        $provider = new Physician([
            'provider_category' => 'dentist',
        ]);

        $this->assertTrue($provider->isDentist());
    }
}
