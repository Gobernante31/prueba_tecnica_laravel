<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use App\Models\Entidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ContactoController extends Controller
{
  public function index()
  {
    $contactos = Contacto::with('entidad')->get();
    Log::info('Listado de contactos solicitado', ['total' => $contactos->count()]);
    return response()->json($contactos, Response::HTTP_OK);
  }


  public function store(Request $request)
  {
    $validatedData = $request->validate([
      'nombre' => 'required|string|max:191',
      'identificacion' => 'required|string|unique:contactos,identificacion',
      'email' => 'nullable|email|max:191',
      'telefono' => 'nullable|string|max:191',
      'direccion' => 'nullable|string|max:191',
      'notas' => 'nullable|string',
      'entidad_id' => 'required|exists:entidades,id',
      'fecha_nacimiento' => 'nullable|date',
      'creado_por' => 'nullable|integer',
    ]);

    // Validación de duplicados nombre + email
    $duplicado = Contacto::where('nombre', $validatedData['nombre'])
      ->where('email', $validatedData['email'] ?? null)
      ->first();

    if ($duplicado) {
      Log::warning('Intento de crear contacto duplicado', [
        'nombre' => $validatedData['nombre'],
        'email' => $validatedData['email'] ?? 'N/A'
      ]);
      return response()->json([
        'error' => 'Ya existe un contacto con el mismo nombre y email'
      ], Response::HTTP_CONFLICT);
    }

    $contacto = Contacto::create($validatedData);
    Log::info('Contacto creado exitosamente', ['id' => $contacto->id]);
    return response()->json($contacto->load('entidad'), Response::HTTP_CREATED);
  }


  public function show($id)
  {
    $contacto = Contacto::with('entidad')->find($id);

    if (!$contacto) {
      return response()->json(['error' => 'Contacto no encontrado'], Response::HTTP_NOT_FOUND);
    }

    return response()->json($contacto, Response::HTTP_OK);
  }


  public function update(Request $request, $id)
  {
    $contacto = Contacto::find($id);

    if (!$contacto) {
      return response()->json(['error' => 'Contacto no encontrado'], Response::HTTP_NOT_FOUND);
    }

    $validatedData = $request->validate([
      'nombre' => 'sometimes|required|string|max:191',
      'identificacion' => [
        'sometimes',
        'required',
        'string',
        Rule::unique('contactos', 'identificacion')->ignore($contacto->id)
      ],
      'email' => 'nullable|email|max:191',
      'telefono' => 'nullable|string|max:191',
      'direccion' => 'nullable|string|max:191',
      'notas' => 'nullable|string',
      'entidad_id' => 'sometimes|required|exists:entidades,id',
      'fecha_nacimiento' => 'nullable|date',
      'creado_por' => 'nullable|integer',
    ]);

    // Validación de duplicados nombre + email (excluyendo el registro actual)
    if (isset($validatedData['nombre']) || isset($validatedData['email'])) {
      $nombre = $validatedData['nombre'] ?? $contacto->nombre;
      $email = $validatedData['email'] ?? $contacto->email;

      $duplicado = Contacto::where('nombre', $nombre)
        ->where('email', $email)
        ->where('id', '!=', $contacto->id)
        ->first();

      if ($duplicado) {
        Log::warning('Intento de actualizar a contacto duplicado', [
          'id' => $contacto->id,
          'nombre' => $nombre,
          'email' => $email ?? 'N/A'
        ]);
        return response()->json([
          'error' => 'Ya existe otro contacto con el mismo nombre y email'
        ], Response::HTTP_CONFLICT);
      }
    }

    $contacto->update($validatedData);
    Log::info('Contacto actualizado exitosamente', ['id' => $contacto->id]);
    return response()->json($contacto->load('entidad'), Response::HTTP_OK);
  }


  public function destroy($id)
  {
    $contacto = Contacto::find($id);

    if (!$contacto) {
      return response()->json(['error' => 'Contacto no encontrado'], Response::HTTP_NOT_FOUND);
    }

    $contacto->delete();
    Log::info('Contacto eliminado exitosamente', ['id' => $id]);
    return response()->json(['message' => 'Contacto eliminado correctamente'], Response::HTTP_OK);
  }
}
