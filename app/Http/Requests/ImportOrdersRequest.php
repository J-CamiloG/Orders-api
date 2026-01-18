<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportOrdersRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Get Establezca las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,json',
                'max:10240', // Tamaño máximo de 10MB
            ],
        ];
    }

    /**
     * Get Mensajes personalizados para errores del validador.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Por favor, sube un archivo',
            'file.file' => 'El elemento cargado debe ser un archivo',
            'file.mimes' => 'El archivo debe ser un CSV o JSON',
            'file.max' => 'El tamaño del archivo no debe exceder los 10MB',
        ];
    }
}
