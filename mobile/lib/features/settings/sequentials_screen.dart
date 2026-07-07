import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/v1_api_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/aurora_background.dart';
import '../../core/widgets/glass_panel.dart';
import '../../data/providers/auth_provider.dart';

/// Los 6 tipos de comprobante del SRI (código + etiqueta).
const _docTypes = <(String, String)>[
  ('01', 'Factura'),
  ('03', 'Liquidación de Compra'),
  ('04', 'Nota de Crédito'),
  ('05', 'Nota de Débito'),
  ('06', 'Guía de Remisión'),
  ('07', 'Comprobante de Retención'),
];

/// Ajuste del secuencial de un punto de emisión, por tipo de comprobante.
/// El "último número usado" permite migrar a producción sin repetir números:
/// el próximo comprobante emitido será ese número + 1.
class SequentialsScreen extends ConsumerStatefulWidget {
  final ApiEmissionPoint emissionPoint;

  const SequentialsScreen({super.key, required this.emissionPoint});

  @override
  ConsumerState<SequentialsScreen> createState() => _SequentialsScreenState();
}

class _SequentialsScreenState extends ConsumerState<SequentialsScreen> {
  final Map<String, TextEditingController> _ctrls = {
    for (final t in _docTypes) t.$1: TextEditingController(text: '0'),
  };
  bool _loading = true;
  bool _saving = false;
  Object? _error;

  V1ApiService get _api => ref.read(v1ApiServiceProvider);

  @override
  void initState() {
    super.initState();
    Future.microtask(_load);
  }

  @override
  void dispose() {
    for (final c in _ctrls.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final rows = await _api.emissionPointSequentials(widget.emissionPoint.id);
      final byType = {for (final r in rows) r.documentType: r.currentNumber};
      if (!mounted) return;
      setState(() {
        for (final t in _docTypes) {
          _ctrls[t.$1]!.text = (byType[t.$1] ?? 0).toString();
        }
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  Future<void> _save() async {
    FocusScope.of(context).unfocus();
    setState(() => _saving = true);
    final messenger = ScaffoldMessenger.of(context);
    try {
      final list = <({String documentType, int lastNumber})>[
        for (final t in _docTypes)
          (
            documentType: t.$1,
            lastNumber: int.tryParse(_ctrls[t.$1]!.text.trim()) ?? 0,
          ),
      ];
      await _api.saveEmissionPointSequentials(widget.emissionPoint.id, list);
      if (!mounted) return;
      messenger.showSnackBar(
        const SnackBar(content: Text('Secuenciales guardados.')),
      );
      context.pop();
    } catch (e) {
      messenger.showSnackBar(
        SnackBar(
          content: Text(e is ApiException ? e.message : 'No se pudo guardar.'),
        ),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final ep = widget.emissionPoint;
    return Scaffold(
      appBar: AppBar(title: const Text('Secuenciales')),
      body: Stack(
        children: [
          const Positioned.fill(child: AuroraBackground()),
          SafeArea(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        GlassPanel(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Punto de emisión ${ep.code}',
                                style: const TextStyle(
                                  fontFamily: 'Avenir Next',
                                  fontWeight: FontWeight.w800,
                                  fontSize: 16,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 4),
                              const Text(
                                'Escribí el ÚLTIMO número usado por cada tipo. '
                                'El próximo comprobante será ese número + 1. '
                                'Útil para migrar a Producción sin repetir '
                                'números.',
                                style: TextStyle(
                                  fontFamily: 'Avenir Next',
                                  color: AppColors.textSecondary,
                                  fontSize: 13,
                                  height: 1.35,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 12),
                        GlassPanel(
                          child: Column(
                            children: [
                              for (var i = 0; i < _docTypes.length; i++) ...[
                                _SequentialRow(
                                  label: _docTypes[i].$2,
                                  controller: _ctrls[_docTypes[i].$1]!,
                                ),
                                if (i < _docTypes.length - 1)
                                  const Divider(height: 18),
                              ],
                            ],
                          ),
                        ),
                        if (_error != null) ...[
                          const SizedBox(height: 12),
                          Text(
                            _error is ApiException
                                ? (_error as ApiException).message
                                : _error.toString(),
                            style: const TextStyle(
                              color: AppColors.error,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                        const SizedBox(height: 18),
                        ElevatedButton.icon(
                          onPressed: _saving ? null : _save,
                          icon: _saving
                              ? const SizedBox(
                                  width: 16,
                                  height: 16,
                                  child:
                                      CircularProgressIndicator(strokeWidth: 2),
                                )
                              : const Icon(Icons.save_rounded),
                          label: const Text('Guardar secuenciales'),
                          style: ElevatedButton.styleFrom(
                            minimumSize: const Size.fromHeight(52),
                          ),
                        ),
                      ],
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

class _SequentialRow extends StatelessWidget {
  final String label;
  final TextEditingController controller;

  const _SequentialRow({required this.label, required this.controller});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: const TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w700,
              fontSize: 15,
              color: AppColors.textPrimary,
            ),
          ),
        ),
        const SizedBox(width: 12),
        SizedBox(
          width: 120,
          child: TextField(
            controller: controller,
            keyboardType: TextInputType.number,
            textAlign: TextAlign.end,
            decoration: const InputDecoration(
              labelText: 'Último #',
              isDense: true,
            ),
          ),
        ),
      ],
    );
  }
}
