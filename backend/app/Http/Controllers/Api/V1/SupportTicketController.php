<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Support\SupportTicket;
use App\Models\Support\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Soporte
 */
class SupportTicketController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::where('tenant_id', $request->user()->tenant_id)
            ->with(['user']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $tickets = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'message' => ['required', 'string'],
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'is_admin_reply' => false,
            'message' => $validated['message'],
        ]);

        return $this->created(['ticket' => $ticket->fresh(['user'])], 'Ticket creado');
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_if($ticket->tenant_id !== $request->user()->tenant_id, 403);
        $ticket->load(['user', 'messages.user']);
        return $this->success(['ticket' => $ticket]);
    }

    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_if($ticket->tenant_id !== $request->user()->tenant_id, 403);
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $msg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'is_admin_reply' => false,
            'message' => $validated['message'],
        ]);

        if ($ticket->status?->value === 'resolved' || $ticket->status === 'resolved') {
            $ticket->update(['status' => 'open', 'resolved_at' => null]);
        }

        return $this->created(['message' => $msg], 'Respuesta enviada');
    }

    public function close(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_if($ticket->tenant_id !== $request->user()->tenant_id, 403);
        $ticket->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
        return $this->success(['ticket' => $ticket->fresh()], 'Ticket cerrado');
    }

    public function reopen(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_if($ticket->tenant_id !== $request->user()->tenant_id, 403);
        $ticket->update(['status' => 'open', 'resolved_at' => null]);
        return $this->success(['ticket' => $ticket->fresh()], 'Ticket reabierto');
    }
}
