<?php

namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__.'/../../unified_portal/includes/wpu_medical_consent.php';
require_once __DIR__.'/../../unified_portal/includes/wpu_medical_consent_docx.php';

class MedicalConsentHelpersTest extends TestCase
{
    public function test_rejects_empty_signature(): void
    {
        $this->assertFalse(wpu_medical_consent_validate_signature_data_url(''));
    }

    public function test_rejects_typed_name_only(): void
    {
        $this->assertFalse(wpu_medical_consent_validate_signature_data_url('John Doe'));
    }

    public function test_accepts_minimal_png_data_url(): void
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
        $this->assertNotFalse($png);
        $dataUrl = 'data:image/png;base64,'.base64_encode($png.str_repeat('x', 200));
        $this->assertTrue(wpu_medical_consent_validate_signature_data_url($dataUrl));
    }

    public function test_status_labels(): void
    {
        $this->assertSame('Completed', wpu_medical_consent_status_label('completed'));
        $this->assertSame(
            'Awaiting physician certification',
            wpu_medical_consent_status_label('awaiting_physician_certification')
        );
    }

    public function test_dental_visit_always_requires_consent(): void
    {
        $this->assertTrue(wpu_medical_consent_visit_requires_consent('dental', 'Dental', 'Cleaning'));
    }

    public function test_health_with_treatment_requires_consent(): void
    {
        $this->assertTrue(wpu_medical_consent_visit_requires_consent('health', '', 'Wound dressing'));
    }

    public function test_docx_generation_populates_template_fields(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive not available');
        }

        $row = [
            'purpose' => 'Dental extraction',
            'named_patient' => 'Minor Patient',
            'signed_by_name' => 'Guardian Name',
            'representative_relationship' => 'Parent',
            'signed_at' => '2026-02-01 14:00:00',
        ];

        $binary = wpu_medical_consent_generate_docx($row);
        $this->assertNotSame('', $binary);

        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'mc_test_');
        file_put_contents($tmp, $binary);
        $this->assertTrue($zip->open($tmp) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertStringContainsString('Dental extraction', $xml);
        $this->assertStringContainsString('Guardian Name', $xml);
        $this->assertStringContainsString('pageBreakBefore', $xml);
    }
}
