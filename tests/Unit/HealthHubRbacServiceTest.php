<?php

namespace Tests\Unit;

use App\Services\HealthHubRbacService;
use PHPUnit\Framework\TestCase;

class HealthHubRbacServiceTest extends TestCase
{
    private HealthHubRbacService $rbac;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rbac = new HealthHubRbacService;
    }

    public function test_nurse_cannot_view_subjective_objective_assessment(): void
    {
        $record = [
            'full_name' => 'Test Patient',
            'subjective' => 'secret subjective',
            'objectives' => 'secret objective',
            'assessment' => 'secret assessment',
            'diagnosis' => 'dx',
        ];

        $filtered = $this->rbac->filterMedicalRecordForRole($record, 'nurse');

        $this->assertNull($filtered['subjective']);
        $this->assertNull($filtered['objectives']);
        $this->assertNull($filtered['assessment']);
        $this->assertSame('dx', $filtered['diagnosis']);
    }

    public function test_staff_sees_only_basic_fields(): void
    {
        $record = [
            'full_name' => 'Test Patient',
            'subjective' => 's',
            'diagnosis' => 'dx',
        ];

        $filtered = $this->rbac->filterMedicalRecordForRole($record, 'staff');

        $this->assertNull($filtered['subjective']);
        $this->assertNull($filtered['diagnosis']);
        $this->assertSame('Test Patient', $filtered['full_name']);
    }

    public function test_physician_sees_full_record(): void
    {
        $record = ['subjective' => 's', 'assessment' => 'a'];
        $filtered = $this->rbac->filterMedicalRecordForRole($record, 'physician');
        $this->assertSame('s', $filtered['subjective']);
        $this->assertSame('a', $filtered['assessment']);
    }
}
