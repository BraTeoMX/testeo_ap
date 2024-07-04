<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produccion1 extends Model
{
    use HasFactory;

    protected $table = 'produccion1';

    protected $fillable = [
        'supervisor_id',
        'semana',
        'año',
        'valor',
        'te',
    ];

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class);
    }
}
?>
