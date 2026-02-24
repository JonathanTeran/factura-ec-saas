import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_panel.dart';
import '../../core/widgets/loading_widget.dart';
import '../../core/widgets/money_text.dart';
import '../../core/widgets/page_header.dart';
import '../../core/widgets/section_header.dart';
import '../../data/providers/auth_provider.dart';
import '../../data/providers/subscription_provider.dart';

class BillingScreen extends ConsumerStatefulWidget {
  const BillingScreen({super.key});

  @override
  ConsumerState<BillingScreen> createState() => _BillingScreenState();
}

class _BillingScreenState extends ConsumerState<BillingScreen> {
  final _referenceCtrl = TextEditingController();
  XFile? _receiptImage;
  bool _submitting = false;
  String? _errorText;
  String? _successText;
  int? _selectedPlanId;

  @override
  void dispose() {
    _referenceCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickReceipt() async {
    final picker = ImagePicker();
    final image = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1200,
      maxHeight: 1200,
      imageQuality: 85,
    );
    if (image != null) {
      setState(() => _receiptImage = image);
    }
  }

  Future<void> _submitTransfer() async {
    if (_selectedPlanId == null) {
      setState(() => _errorText = 'Selecciona un plan.');
      return;
    }
    if (_referenceCtrl.text.trim().isEmpty) {
      setState(() => _errorText = 'Ingresa la referencia de transferencia.');
      return;
    }
    if (_receiptImage == null) {
      setState(() => _errorText = 'Adjunta el comprobante de transferencia.');
      return;
    }

    setState(() {
      _submitting = true;
      _errorText = null;
      _successText = null;
    });

    try {
      final api = ref.read(v1ApiServiceProvider);
      await api.submitTransferPayment(
        planId: _selectedPlanId!,
        reference: _referenceCtrl.text.trim(),
        receiptPath: _receiptImage!.path,
      );

      ref.invalidate(currentSubscriptionProvider);
      setState(() {
        _successText =
            'Comprobante enviado. Revisaremos tu pago y activaremos tu plan.';
        _receiptImage = null;
        _referenceCtrl.clear();
      });
    } catch (error) {
      setState(() => _errorText = error.toString());
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final subscriptionAsync = ref.watch(currentSubscriptionProvider);
    final plansAsync = ref.watch(plansProvider);
    final bankAccountsAsync = ref.watch(bankAccountsProvider);

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PageHeader(
              title: 'Facturación',
              subtitle: 'Plan, suscripción y pagos',
              trailing: IconButton.filledTonal(
                onPressed: () {
                  ref.invalidate(currentSubscriptionProvider);
                  ref.invalidate(plansProvider);
                  ref.invalidate(bankAccountsProvider);
                },
                icon: const Icon(Icons.refresh_rounded),
              ),
            ),
            const SizedBox(height: 16),

            // ── Current subscription status ──
            const SectionHeader(title: 'Tu suscripción', actionText: ''),
            const SizedBox(height: 10),
            subscriptionAsync.when(
              loading: () => const GlassPanel(
                child: Center(
                  child: Padding(
                    padding: EdgeInsets.all(20),
                    child: CircularProgressIndicator(),
                  ),
                ),
              ),
              error: (error, _) => GlassPanel(
                child: Text(
                  'Error cargando suscripción: $error',
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.error,
                  ),
                ),
              ),
              data: (subscription) {
                if (subscription == null) {
                  return const GlassPanel(
                    child: Column(
                      children: [
                        Icon(Icons.credit_card_off_rounded,
                            size: 40, color: AppColors.textMuted),
                        SizedBox(height: 10),
                        Text(
                          'Sin suscripción activa',
                          style: TextStyle(
                            fontFamily: 'Avenir Next',
                            fontWeight: FontWeight.w700,
                            color: AppColors.textPrimary,
                            fontSize: 18,
                          ),
                        ),
                        SizedBox(height: 4),
                        Text(
                          'Selecciona un plan y realiza una transferencia para activar tu cuenta.',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontFamily: 'Avenir Next',
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  );
                }

                final statusColor = subscription.isActive
                    ? AppColors.success
                    : AppColors.warning;
                final statusText = subscription.onTrial
                    ? 'En período de prueba'
                    : subscription.isActive
                        ? 'Activa'
                        : subscription.status.toUpperCase();

                return GlassPanel(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              subscription.planName,
                              style: const TextStyle(
                                fontFamily: 'Avenir Next',
                                fontWeight: FontWeight.w800,
                                fontSize: 22,
                                color: AppColors.textPrimary,
                              ),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 5,
                            ),
                            decoration: BoxDecoration(
                              color: statusColor.withValues(alpha: 0.18),
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              statusText,
                              style: TextStyle(
                                fontFamily: 'Avenir Next',
                                fontWeight: FontWeight.w800,
                                fontSize: 12,
                                color: statusColor,
                              ),
                            ),
                          ),
                        ],
                      ),
                      if (subscription.currentPeriodEnd != null) ...[
                        const SizedBox(height: 8),
                        Text(
                          'Válido hasta: ${DateFormat('dd/MM/yyyy').format(subscription.currentPeriodEnd!)}',
                          style: const TextStyle(
                            fontFamily: 'Avenir Next',
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                      if (subscription.onTrial &&
                          subscription.trialEndsAt != null) ...[
                        const SizedBox(height: 4),
                        Text(
                          'Prueba termina: ${DateFormat('dd/MM/yyyy').format(subscription.trialEndsAt!)}',
                          style: const TextStyle(
                            fontFamily: 'Avenir Next',
                            color: AppColors.warning,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ],
                  ),
                );
              },
            ),

            const SizedBox(height: 20),

            // ── Available plans ──
            const SectionHeader(title: 'Planes disponibles', actionText: ''),
            const SizedBox(height: 10),
            plansAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => GlassPanel(
                child: Text(
                  'No se pudieron cargar los planes.',
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                  ),
                ),
              ),
              data: (plans) {
                if (plans.isEmpty) {
                  return const GlassPanel(
                    child: Text(
                      'No hay planes disponibles en este momento.',
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        color: AppColors.textSecondary,
                      ),
                    ),
                  );
                }

                return Column(
                  children: [
                    for (var i = 0; i < plans.length; i++) ...[
                      _PlanCard(
                        plan: plans[i],
                        isSelected: _selectedPlanId == plans[i].id,
                        onTap: () =>
                            setState(() => _selectedPlanId = plans[i].id),
                      ),
                      if (i < plans.length - 1) const SizedBox(height: 10),
                    ],
                  ],
                );
              },
            ),

            const SizedBox(height: 20),

            // ── Bank account details ──
            const SectionHeader(
              title: 'Datos para transferencia',
              actionText: '',
            ),
            const SizedBox(height: 10),
            bankAccountsAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (_, _) => const GlassPanel(
                child: Text(
                  'No se pudieron cargar las cuentas bancarias.',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                  ),
                ),
              ),
              data: (accounts) {
                if (accounts.isEmpty) {
                  return const GlassPanel(
                    child: Text(
                      'No hay cuentas bancarias configuradas.',
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        color: AppColors.textSecondary,
                      ),
                    ),
                  );
                }

                return Column(
                  children: [
                    for (final account in accounts)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: GlassPanel(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                account.bankName,
                                style: const TextStyle(
                                  fontFamily: 'Avenir Next',
                                  fontWeight: FontWeight.w700,
                                  fontSize: 18,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 8),
                              _InfoRow(
                                label: 'Tipo',
                                value: account.accountType,
                              ),
                              _InfoRow(
                                label: 'Número',
                                value: account.accountNumber,
                              ),
                              _InfoRow(
                                label: 'Titular',
                                value: account.accountHolder,
                              ),
                              _InfoRow(
                                label: 'RUC/CI',
                                value: account.identificationNumber,
                              ),
                            ],
                          ),
                        ),
                      ),
                  ],
                );
              },
            ),

            const SizedBox(height: 20),

            // ── Upload transfer receipt ──
            const SectionHeader(
              title: 'Subir comprobante',
              actionText: '',
            ),
            const SizedBox(height: 10),
            GlassPanel(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Adjunta la imagen del comprobante de transferencia y la referencia bancaria.',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 14),
                  InkWell(
                    borderRadius: BorderRadius.circular(14),
                    onTap: _submitting ? null : _pickReceipt,
                    child: Container(
                      width: double.infinity,
                      height: 140,
                      decoration: BoxDecoration(
                        color: AppColors.surfaceDark.withValues(alpha: 0.8),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: AppColors.border,
                          style: BorderStyle.solid,
                        ),
                      ),
                      child: _receiptImage != null
                          ? ClipRRect(
                              borderRadius: BorderRadius.circular(13),
                              child: Image.file(
                                File(_receiptImage!.path),
                                fit: BoxFit.cover,
                                width: double.infinity,
                              ),
                            )
                          : const Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  Icons.cloud_upload_outlined,
                                  size: 36,
                                  color: AppColors.textMuted,
                                ),
                                SizedBox(height: 8),
                                Text(
                                  'Toca para seleccionar imagen',
                                  style: TextStyle(
                                    fontFamily: 'Avenir Next',
                                    color: AppColors.textMuted,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _referenceCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Referencia de transferencia',
                      hintText: 'Ej: 12345678',
                      prefixIcon: Icon(Icons.tag_rounded),
                    ),
                  ),
                  if (_errorText != null) ...[
                    const SizedBox(height: 10),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 10,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.error.withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: AppColors.error.withValues(alpha: 0.4),
                        ),
                      ),
                      child: Text(
                        _errorText!,
                        style: const TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.error,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                  if (_successText != null) ...[
                    const SizedBox(height: 10),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 10,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.success.withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: AppColors.success.withValues(alpha: 0.4),
                        ),
                      ),
                      child: Text(
                        _successText!,
                        style: const TextStyle(
                          fontFamily: 'Avenir Next',
                          color: AppColors.success,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 14),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _submitting ? null : _submitTransfer,
                      icon: _submitting
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.send_rounded),
                      label: const Text('Enviar comprobante'),
                      style: ElevatedButton.styleFrom(
                        minimumSize: const Size.fromHeight(52),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PlanCard extends StatelessWidget {
  final ApiPlan plan;
  final bool isSelected;
  final VoidCallback onTap;

  const _PlanCard({
    required this.plan,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final borderColor =
        isSelected ? AppColors.primary : AppColors.border;

    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.surface.withValues(alpha: 0.92),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: borderColor, width: isSelected ? 2 : 1),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.15),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ]
              : null,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    plan.name,
                    style: const TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w800,
                      fontSize: 20,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ),
                if (plan.isPopular)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.warning.withValues(alpha: 0.18),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: const Text(
                      'POPULAR',
                      style: TextStyle(
                        fontFamily: 'Avenir Next',
                        fontWeight: FontWeight.w800,
                        fontSize: 10,
                        color: AppColors.warning,
                      ),
                    ),
                  ),
                if (isSelected)
                  const Padding(
                    padding: EdgeInsets.only(left: 8),
                    child: Icon(
                      Icons.check_circle_rounded,
                      color: AppColors.primary,
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Text(
                  currency(plan.monthlyPrice),
                  style: const TextStyle(
                    fontFamily: 'Avenir Next',
                    fontWeight: FontWeight.w800,
                    fontSize: 28,
                    color: AppColors.textPrimary,
                  ),
                ),
                const Text(
                  ' /mes',
                  style: TextStyle(
                    fontFamily: 'Avenir Next',
                    color: AppColors.textSecondary,
                    fontSize: 14,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              '${currency(plan.yearlyPrice)}/año · ${plan.maxDocuments} docs · ${plan.maxUsers} usuarios',
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textSecondary,
                fontSize: 13,
              ),
            ),
            if (plan.features.isNotEmpty) ...[
              const SizedBox(height: 10),
              for (final feature in plan.features)
                Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.check_rounded,
                        size: 16,
                        color: AppColors.success,
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          feature,
                          style: const TextStyle(
                            fontFamily: 'Avenir Next',
                            color: AppColors.textSecondary,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
            ],
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;

  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          SizedBox(
            width: 80,
            child: Text(
              label,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                color: AppColors.textMuted,
                fontSize: 13,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w700,
                color: AppColors.textPrimary,
                fontSize: 14,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
