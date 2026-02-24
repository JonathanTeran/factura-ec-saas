import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class SearchInput extends StatelessWidget {
  const SearchInput({super.key});

  @override
  Widget build(BuildContext context) {
    return TextField(
      decoration: InputDecoration(
        hintText: 'Buscar por emisor, RUC o número de documento',
        hintStyle: const TextStyle(
          fontFamily: 'Avenir Next',
          color: AppColors.textMuted,
          fontWeight: FontWeight.w500,
        ),
        prefixIcon: const Icon(Icons.search_rounded),
        suffixIcon: IconButton(
          onPressed: () {},
          icon: const Icon(Icons.mic_none_rounded),
        ),
      ),
    );
  }
}
