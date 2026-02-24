<?php

namespace App\Services\Notification;

use App\Models\SRI\ElectronicDocument;
use App\Models\User;
use App\Enums\UserRole;
use App\Notifications\AdminEventNotification;
use App\Notifications\DocumentAuthorizedNotification;
use App\Notifications\DocumentRejectedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NotificationService
{
    /**
     * Enviar documento autorizado por email.
     */
    public function sendDocumentByEmail(ElectronicDocument $document, ?string $email = null): bool
    {
        $recipientEmail = $email ?? $document->customer->email;

        if (!$recipientEmail) {
            return false;
        }

        try {
            // Obtener archivos adjuntos
            $attachments = [];

            if ($document->ride_pdf_path) {
                $attachments[] = [
                    'path' => $document->ride_pdf_path,
                    'name' => "Factura_{$document->getDocumentNumber()}.pdf",
                    'mime' => 'application/pdf',
                ];
            }

            if ($document->xml_authorized_path) {
                $attachments[] = [
                    'path' => $document->xml_authorized_path,
                    'name' => "Factura_{$document->getDocumentNumber()}.xml",
                    'mime' => 'application/xml',
                ];
            }

            // Enviar email
            Mail::send('emails.document-authorized', [
                'document' => $document,
                'customer' => $document->customer,
                'company' => $document->company,
            ], function ($message) use ($recipientEmail, $document, $attachments) {
                $message->to($recipientEmail)
                    ->subject("Factura Electrónica {$document->getDocumentNumber()}")
                    ->from(config('mail.from.address'), $document->company->trade_name ?? $document->company->business_name);

                foreach ($attachments as $attachment) {
                    $content = Storage::disk('s3')->get($attachment['path']);
                    $message->attachData($content, $attachment['name'], [
                        'mime' => $attachment['mime'],
                    ]);
                }
            });

            // Actualizar registro
            $document->update([
                'email_sent' => true,
                'email_sent_at' => now(),
            ]);

            Log::info('Document email sent', [
                'document_id' => $document->id,
                'recipient' => $recipientEmail,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send document email', [
                'document_id' => $document->id,
                'recipient' => $recipientEmail,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Enviar notificación de autorización a usuario interno.
     */
    public function notifyDocumentAuthorized(ElectronicDocument $document): void
    {
        $user = $document->createdBy;

        if ($user) {
            $user->notify(new DocumentAuthorizedNotification($document));
        }
    }

    /**
     * Enviar notificación de rechazo a usuario interno.
     */
    public function notifyDocumentRejected(ElectronicDocument $document): void
    {
        $user = $document->createdBy;

        if ($user) {
            $user->notify(new DocumentRejectedNotification($document));
        }
    }

    /**
     * Enviar documento por WhatsApp.
     */
    public function sendDocumentByWhatsApp(ElectronicDocument $document, ?string $phone = null): bool
    {
        $recipientPhone = $phone ?? $document->customer->phone;

        if (!$recipientPhone) {
            return false;
        }

        // Formatear número para WhatsApp
        $formattedPhone = $this->formatPhoneForWhatsApp($recipientPhone);

        try {
            $whatsapp = app(TwilioWhatsAppService::class);
            $message = "Hola {$document->customer->name}, adjunto su factura electrónica {$document->getDocumentNumber()} de {$document->company->business_name}.";
            
            $mediaUrls = [];
            if ($document->ride_pdf_path) {
                $mediaUrls[] = Storage::disk('s3')->temporaryUrl(
                    $document->ride_pdf_path,
                    now()->addMinutes(30)
                );
            }

            $success = $whatsapp->send($formattedPhone, $message, $mediaUrls);

            if ($success) {
                $document->update([
                    'whatsapp_sent' => true,
                    'whatsapp_sent_at' => now(),
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification', [
                'document_id' => $document->id,
                'phone' => $formattedPhone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Enviar recordatorio de pago.
     */
    public function sendPaymentReminder(ElectronicDocument $document): bool
    {
        $email = $document->customer->email;

        if (!$email) {
            return false;
        }

        try {
            Mail::send('emails.payment-reminder', [
                'document' => $document,
                'customer' => $document->customer,
                'company' => $document->company,
                'daysOverdue' => $document->due_date?->diffInDays(now()) ?? 0,
            ], function ($message) use ($email, $document) {
                $message->to($email)
                    ->subject("Recordatorio de pago - Factura {$document->getDocumentNumber()}");
            });

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send payment reminder', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Notificar a administradores sobre eventos importantes.
     */
    public function notifyAdmins(string $event, array $data): void
    {
        $admins = User::withoutGlobalScopes()
            ->where('role', UserRole::SUPER_ADMIN)
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new AdminEventNotification($event, $data));

            Log::info('Admin notification sent', [
                'admin_id' => $admin->id,
                'event' => $event,
            ]);
        }
    }

    /**
     * Formatear número de teléfono para WhatsApp.
     */
    private function formatPhoneForWhatsApp(string $phone): string
    {
        // Remover caracteres no numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Agregar código de país Ecuador si no lo tiene
        if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            $phone = '593' . substr($phone, 1);
        } elseif (strlen($phone) === 9) {
            $phone = '593' . $phone;
        }

        return $phone;
    }
}
