<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranSurah extends Model
{
    protected $fillable = ['id', 'name_arabic', 'verses_count', 'revelation_place'];

    public function ayahs(): HasMany
    {
        return $this->hasMany(QuranAyah::class, 'surah_id');
    }
}
