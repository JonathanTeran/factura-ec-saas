<div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Importar Productos</h3>

        @if(session()->has('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <p class="text-green-700 text-sm">{{ session('success') }}</p>
            </div>
        @endif

        @if(session()->has('warning'))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <p class="text-yellow-700 text-sm">{{ session('warning') }}</p>
            </div>
        @endif

        @if(!$imported)
            <form wire:submit="import" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Archivo CSV o Excel</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition">
                        <input type="file" wire:model="file" accept=".csv,.xlsx,.xls" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-2 text-xs text-gray-400">Formatos: CSV, XLSX, XLS. Max: 5MB</p>
                    </div>
                    @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Columnas esperadas:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['codigo*', 'nombre*', 'descripcion', 'tipo', 'precio_unitario*', 'codigo_impuesto', 'codigo_porcentaje', 'tarifa_impuesto', 'controla_stock', 'stock_actual', 'stock_minimo', 'unidad_medida'] as $col)
                            <span class="px-2 py-1 bg-white border border-gray-200 rounded text-xs text-gray-600">{{ $col }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('panel.products.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="import">Importar</span>
                        <span wire:loading wire:target="import">Importando...</span>
                    </button>
                </div>
            </form>
        @else
            @if(count($errors) > 0)
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-red-700 mb-2">Errores encontrados ({{ $failedCount }}):</h4>
                    <div class="max-h-60 overflow-y-auto border border-red-200 rounded-lg">
                        <table class="min-w-full text-sm">
                            <thead class="bg-red-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-red-700">Fila</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-red-700">Campo</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-red-700">Error</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-red-100">
                                @foreach($errors as $error)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-600">{{ $error['row'] }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $error['attribute'] }}</td>
                                        <td class="px-3 py-2 text-red-600">{{ implode(', ', $error['errors']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <a href="{{ route('panel.products.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                    Ver productos
                </a>
                <button wire:click="resetImport" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                    Importar otro archivo
                </button>
            </div>
        @endif
    </div>
</div>
