<?php

namespace App\Models;

use App\Enums\MedicalReportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalReport extends Model
{
    protected $fillable = [
        'admission_id',
        'created_by',
        'type',
        'report_date',
        'exam_type',
        'referring_doctor',
        'diagnosis',
        'procedure_notes',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'type' => MedicalReportType::class,
            'report_date' => 'date',
        ];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
