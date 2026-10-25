<?php

namespace Tests\Unit;

use App\Services\ClinicalWorkspaceAccessService;
use Tests\TestCase;

class ClinicalWorkspaceAccessServiceTest extends TestCase
{
    private ClinicalWorkspaceAccessService $access;

    protected function setUp(): void
    {
        parent::setUp();
        $this->access = app(ClinicalWorkspaceAccessService::class);
    }

    public function test_dentist_landing_points_to_dental_records(): void
    {
        $landing = $this->access->landingForRole('dentist');
        $this->assertSame('health_dental_records', $landing['page']);
        $this->assertSame('dental', $landing['query']['records_tab'] ?? null);
    }

    public function test_nurse_cannot_access_admin_management_page(): void
    {
        $this->assertFalse($this->access->canAccessPage('nurse', 'admin_management'));
        $this->assertTrue($this->access->canAccessPage('nurse', 'dispensary'));
    }
}
