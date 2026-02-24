import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../theme/app_theme.dart';

final NumberFormat _moneyFormatter = NumberFormat.currency(
  locale: 'en_US',
  symbol: r'$',
  decimalDigits: 2,
);

String currency(double value) => _moneyFormatter.format(value);

class MoneyText extends StatelessWidget {
  final double amount;
  final TextStyle? style;

  const MoneyText({super.key, required this.amount, this.style});

  @override
  Widget build(BuildContext context) {
    return Text(
      currency(amount),
      style: style ??
          const TextStyle(
            fontFamily: 'Avenir Next',
            fontWeight: FontWeight.w800,
            fontSize: 21,
            color: AppColors.textPrimary,
          ),
    );
  }
}
