<?php

namespace Tests\Feature;

use App\Mail\DocumentAuthorizedMail;
use App\Models\SRI\ElectronicDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTestTenant;

class DocumentAuthorizedMailTest extends TestCase
{
    use RefreshDatabase, CreatesTestTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
        Storage::fake();
    }

    /**
     * Regresión: la clase declaraba una propiedad $attachments que chocaba con
     * la del padre Mailable (fatal en PHP 8.4), rompiendo TODO envío de correo.
     */
    public function test_mail_builds_without_error(): void
    {
        $document = $this->createDocument();

        $mail = new DocumentAuthorizedMail($document);

        $this->assertNotEmpty($mail->envelope()->subject);
        $this->assertIsArray($mail->attachments());
    }

    public function test_attaches_ride_pdf_and_xml_from_default_disk(): void
    {
        $ridePath = 'tenants/'.$this->tenant->id.'/documents/1/ride.pdf';
        $xmlPath = 'tenants/'.$this->tenant->id.'/documents/1/authorized.xml';
        Storage::put($ridePath, '%PDF-1.7 demo');
        Storage::put($xmlPath, '<factura/>');

        $document = $this->createDocument([
            'ride_pdf_path' => $ridePath,
            'xml_authorized_path' => $xmlPath,
        ]);

        $attachments = (new DocumentAuthorizedMail($document))->attachments();

        $this->assertCount(2, $attachments);
    }

    public function test_skips_missing_attachments(): void
    {
        $document = $this->createDocument([
            'ride_pdf_path' => 'no/existe/ride.pdf',
            'xml_authorized_path' => null,
        ]);

        $this->assertCount(0, (new DocumentAuthorizedMail($document))->attachments());
    }
}
