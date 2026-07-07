import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Campo de búsqueda funcional y reutilizable. Notifica cada cambio por
/// [onChanged] para que la pantalla filtre su lista.
class SearchInput extends StatefulWidget {
  final String hintText;
  final ValueChanged<String>? onChanged;

  const SearchInput({
    super.key,
    this.hintText = 'Buscar',
    this.onChanged,
  });

  @override
  State<SearchInput> createState() => _SearchInputState();
}

class _SearchInputState extends State<SearchInput> {
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: _controller,
      onChanged: (value) {
        setState(() {}); // refresca el botón de limpiar
        widget.onChanged?.call(value);
      },
      textInputAction: TextInputAction.search,
      decoration: InputDecoration(
        hintText: widget.hintText,
        hintStyle: const TextStyle(
          fontFamily: 'Avenir Next',
          color: AppColors.textMuted,
          fontWeight: FontWeight.w500,
        ),
        prefixIcon: const Icon(Icons.search_rounded),
        suffixIcon: _controller.text.isEmpty
            ? null
            : IconButton(
                icon: const Icon(Icons.close_rounded),
                onPressed: () {
                  _controller.clear();
                  setState(() {});
                  widget.onChanged?.call('');
                },
              ),
      ),
    );
  }
}
