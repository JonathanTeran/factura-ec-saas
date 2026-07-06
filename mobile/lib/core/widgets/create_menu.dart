import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../theme/app_theme.dart';

/// Menú "+" de creación (como Ecuafact): elegir el tipo de comprobante a emitir
/// o crear un cliente/producto, desde un solo botón.
Future<void> showCreateMenu(BuildContext context) {
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: AppColors.surface,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
    ),
    builder: (ctx) {
      return SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
              const SizedBox(height: 8),
              const Padding(
                padding: EdgeInsets.fromLTRB(8, 4, 8, 8),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    '¿Qué querés crear?',
                    style: TextStyle(
                      fontFamily: 'Avenir Next',
                      fontWeight: FontWeight.w700,
                      fontSize: 16,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ),
              ),
              _CreateItem(
                icon: Icons.receipt_long_rounded,
                label: 'Factura',
                onTap: () => _go(ctx, '/documents/new?type=01'),
              ),
              _CreateItem(
                icon: Icons.assignment_return_rounded,
                label: 'Nota de Crédito',
                onTap: () => _go(ctx, '/documents/new?type=04'),
              ),
              _CreateItem(
                icon: Icons.request_quote_rounded,
                label: 'Nota de Débito',
                onTap: () => _go(ctx, '/documents/new?type=05'),
              ),
              _CreateItem(
                icon: Icons.inventory_2_outlined,
                label: 'Liquidación de Compra',
                onTap: () => _go(ctx, '/documents/new?type=03'),
              ),
              _CreateItem(
                icon: Icons.account_balance_rounded,
                label: 'Retención',
                onTap: () => _go(ctx, '/documents/new?type=07'),
              ),
              _CreateItem(
                icon: Icons.local_shipping_outlined,
                label: 'Guía de Remisión',
                onTap: () => _go(ctx, '/documents/new?type=06'),
              ),
              const Divider(height: 20),
              _CreateItem(
                icon: Icons.person_add_alt_1_rounded,
                label: 'Cliente',
                onTap: () => _go(ctx, '/customers/new'),
              ),
              _CreateItem(
                icon: Icons.add_shopping_cart_rounded,
                label: 'Producto',
                onTap: () => _go(ctx, '/products/new'),
              ),
            ],
          ),
        ),
      );
    },
  );
}

void _go(BuildContext ctx, String location) {
  // Resolvemos el router ANTES de cerrar la hoja: tras el pop el `ctx` queda
  // desactivado y `ctx.go` fallaría.
  final router = GoRouter.of(ctx);
  Navigator.pop(ctx);
  router.go(location);
}

class _CreateItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _CreateItem({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      onTap: onTap,
      leading: Container(
        width: 40,
        height: 40,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: AppColors.primary.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Icon(icon, color: AppColors.primary, size: 22),
      ),
      title: Text(
        label,
        style: const TextStyle(
          fontFamily: 'Avenir Next',
          fontWeight: FontWeight.w600,
          fontSize: 15,
          color: AppColors.textPrimary,
        ),
      ),
      trailing: const Icon(
        Icons.chevron_right_rounded,
        color: AppColors.textMuted,
      ),
    );
  }
}
