<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabourItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'notes',
        'cost',
        'sell',
        'unit_measure_id',
        'status',
	'labour_type_id',
    ];

    public function unitMeasure()
    {
        return $this->belongsTo(UnitMeasure::class, 'unit_measure_id');
    }
    public function labourType()
{
    return $this->belongsTo(LabourType::class, 'labour_type_id');
}

}
