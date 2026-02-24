<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Models\SRI\ElectronicDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, ElectronicDocument $document): bool
    {
        return $user->tenant_id === $document->tenant_id;
    }

    public function create(User $user): bool
    {
        if ($user->tenant_id === null) {
            return false;
        }

        if (!$user->can('create_documents')) {
            return false;
        }

        return $user->tenant->canIssueDocuments();
    }

    public function update(User $user, ElectronicDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if (!$user->can('edit_documents')) {
            return false;
        }

        return $document->status === DocumentStatus::DRAFT;
    }

    public function delete(User $user, ElectronicDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if (!$user->can('delete_documents')) {
            return false;
        }

        return $document->status === DocumentStatus::DRAFT;
    }

    public function restore(User $user, ElectronicDocument $document): bool
    {
        return false;
    }

    public function forceDelete(User $user, ElectronicDocument $document): bool
    {
        return false;
    }

    public function sign(User $user, ElectronicDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if (!$user->can('sign_documents')) {
            return false;
        }

        return $document->status === DocumentStatus::DRAFT;
    }

    public function send(User $user, ElectronicDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if (!$user->can('send_documents')) {
            return false;
        }

        return $document->status === DocumentStatus::SIGNED;
    }

    public function void(User $user, ElectronicDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if (!$user->can('void_documents')) {
            return false;
        }

        return $document->status === DocumentStatus::AUTHORIZED;
    }

    public function downloadPdf(User $user, ElectronicDocument $document): bool
    {
        return $user->tenant_id === $document->tenant_id;
    }

    public function downloadXml(User $user, ElectronicDocument $document): bool
    {
        return $user->tenant_id === $document->tenant_id;
    }

    public function sendEmail(User $user, ElectronicDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        return in_array($document->status, [
            DocumentStatus::AUTHORIZED,
            DocumentStatus::SIGNED,
        ]);
    }
}
