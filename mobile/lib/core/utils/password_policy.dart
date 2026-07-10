/// Política de contraseñas — espejo de `Password::defaults()` del backend:
/// mínimo 8 caracteres, al menos una mayúscula, una minúscula y un carácter
/// especial. Validar aquí da un mensaje inmediato y claro antes de llamar
/// a la API.
String? validatePassword(String password) {
  if (password.length < 8) {
    return 'La contraseña debe tener al menos 8 caracteres.';
  }
  if (!password.contains(RegExp(r'[A-ZÁÉÍÓÚÑ]'))) {
    return 'La contraseña debe incluir al menos una letra mayúscula.';
  }
  if (!password.contains(RegExp(r'[a-záéíóúñ]'))) {
    return 'La contraseña debe incluir al menos una letra minúscula.';
  }
  if (!password.contains(RegExp(r'[^A-Za-z0-9áéíóúÁÉÍÓÚñÑ]'))) {
    return 'La contraseña debe incluir al menos un carácter especial (ej. !@#\$%).';
  }
  return null;
}

const passwordPolicyHint =
    'Mínimo 8 caracteres, con mayúscula, minúscula y un carácter especial.';
