<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
  use HasFactory;

  protected $table = 'contactos';

  protected $fillable = [
    'nombre',
    'identificacion',
    'email',
    'telefono',
    'direccion',
    'notas',
    'entidad_id',
    'fecha_nacimiento',
    'creado_por'
  ];


  public function entidad()
  {
    return $this->belongsTo(Entidad::class, 'entidad_id');
  }
}
