<?php

/**
 * Traducciones mínimas de validación (es). Solo las claves que la API expone
 * a usuarios finales; el resto cae al inglés por defecto de Laravel.
 */
return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'unique' => 'El :attribute ya está registrado.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'string' => 'El campo :attribute no debe superar los :max caracteres.',
    ],

    // Regla Password (Illuminate\Validation\Rules\Password)
    'password' => [
        'letters' => 'La :attribute debe contener al menos una letra.',
        'mixed' => 'La :attribute debe contener al menos una letra mayúscula y una minúscula.',
        'numbers' => 'La :attribute debe contener al menos un número.',
        'symbols' => 'La :attribute debe contener al menos un carácter especial (ej. !@#$%).',
        'uncompromised' => 'La :attribute aparece en una filtración de datos. Elige otra diferente.',
    ],

    'attributes' => [
        'password' => 'contraseña',
        'email' => 'correo',
        'name' => 'nombre',
        'phone' => 'teléfono',
        'current_password' => 'contraseña actual',
    ],
];
