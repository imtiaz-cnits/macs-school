<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_name',
        'subject_code',
        'subject_type',
        'status',
        'user_id'
    ];

    public function class(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function getSubjectNameAttribute($value)
    {
        if (empty($value)) return $value;
        if (preg_match('/[àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ]/i', $value)) {
            $bytes = '';
            for ($i = 0; $i < mb_strlen($value, 'UTF-8'); $i++) {
                $char = mb_substr($value, $i, 1, 'UTF-8');
                $code = mb_ord($char, 'UTF-8');
                if ($code < 256) {
                    $bytes .= chr($code);
                } else {
                    $map = [
                        8218 => 0x82, 8482 => 0x99, 8250 => 0x9B, 8364 => 0x80,
                        8226 => 0x95, 8211 => 0x96, 8212 => 0x97, 8224 => 0x86,
                        8225 => 0x87, 8240 => 0x89, 8254 => 0x9E,
                        353  => 0x9A, 339  => 0x9C, 382  => 0x9E, 376  => 0x9F
                    ];
                    $bytes .= chr($map[$code] ?? 0x3F);
                }
            }
            if (mb_check_encoding($bytes, 'UTF-8') && preg_match('/[\x{0980}-\x{09FF}]/u', $bytes)) {
                return $bytes;
            }
        }
        return $value;
    }
}