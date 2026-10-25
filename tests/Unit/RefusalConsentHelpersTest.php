<?php

namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__.'/../../unified_portal/includes/wpu_refusal_consent.php';
require_once __DIR__.'/../../unified_portal/includes/wpu_refusal_consent_docx.php';

class RefusalConsentHelpersTest extends TestCase
{
    public function test_form_reference_is_refusal_form_number(): void
    {
        $this->assertStringContainsString('HSO-31', wpu_refusal_consent_form_reference());
    }

    public function test_docx_generation_populates_refusal_template(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive not available');
        }

        $row = [
            'declined_procedure' => 'Influenza vaccination',
            'advising_physician' => 'Sample Advising Physician, MD',
            'named_patient' => 'Minor Patient',
            'signed_by_name' => 'Jane Guardian',
            'representative_relationship' => 'Parent',
            'signed_at' => '2026-02-01 14:00:00',
            'physician_printed_name' => 'Test Physician, MD',
            'physician_certified_at' => '2026-02-02 09:00:00',
        ];

        $binary = wpu_refusal_consent_generate_docx($row);
        $this->assertNotSame('', $binary);

        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'rc_test_');
        file_put_contents($tmp, $binary);
        $this->assertTrue($zip->open($tmp) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $footer = (string) $zip->getFromName('word/footer1.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertStringContainsString('Influenza vaccination', $xml);
        $this->assertStringContainsString('Sample Advising Physician', $xml);
        $this->assertStringContainsString('Jane Guardian', $xml);
        $this->assertStringContainsString('pageBreakBefore', $xml);
        $this->assertStringContainsString('REFUSAL OF CONSENT', $xml);
        $this->assertStringContainsString('HSO-31', strip_tags($footer));
        $this->assertStringContainsString('refuse to consent', strtolower(strip_tags($xml)));
        $this->assertStringContainsString(
            'risks and consequences of not proceeding',
            strtolower(strip_tags($xml))
        );
        $this->assertStringNotContainsString('willingly consent', strtolower(strip_tags($xml)));
        $this->assertStringNotContainsString('HSO-23', strip_tags($footer));
    }
}
