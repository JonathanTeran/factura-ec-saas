<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 {{ $account->is_parent ? 'font-medium' : '' }}">
    <td class="px-4 py-2.5 font-mono text-xs {{ $account->is_parent ? 'font-semibold' : '' }}">{{ $account->code }}</td>
    <td class="px-4 py-2.5">
        <span style="padding-left: {{ $level * 1.5 }}rem" class="inline-flex items-center gap-1.5">
            @if($account->is_parent)
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                </svg>
            @endif
            <span class="{{ !$account->is_active ? 'text-slate-400 line-through' : '' }}">{{ $account->name }}</span>
        </span>
    </td>
    <td class="px-4 py-2.5">
        <span class="badge badge-{{ $account->account_type->color() }}">
            {{ $account->account_type->label() }}
        </span>
    </td>
    <td class="px-4 py-2.5 text-xs text-slate-500">
        {{ $account->account_nature === 'debit' ? 'Deudora' : 'Acreedora' }}
    </td>
    <td class="px-4 py-2.5 text-center">
        @if($account->allows_movement)
            <svg class="mx-auto h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
            </svg>
        @else
            <span class="text-xs text-slate-400">-</span>
        @endif
    </td>
    <td class="px-4 py-2.5 text-center">
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
            {{ $account->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
            {{ $account->is_active ? 'Activa' : 'Inactiva' }}
        </span>
    </td>
    <td class="px-4 py-2.5 text-right">
        <div class="flex items-center justify-end gap-1">
            <a href="{{ route('panel.accounting.chart-of-accounts.edit', $account->id) }}" class="btn-icon-sm" title="Editar">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
            </a>
            <a href="{{ route('panel.accounting.chart-of-accounts.create', ['parentId' => $account->id]) }}" class="btn-icon-sm" title="Agregar subcuenta">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </a>
        </div>
    </td>
</tr>
@if($account->children && $account->children->count() > 0)
    @foreach($account->children as $child)
        @include('livewire.panel.accounting.partials.account-tree-row', ['account' => $child, 'level' => $level + 1])
    @endforeach
@endif
