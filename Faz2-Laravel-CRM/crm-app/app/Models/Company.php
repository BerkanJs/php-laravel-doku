<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;   // Company::factory() bu trait'ten geliyor - CompanyFactory'yi convention ile bulur (Gun19'daki isimlendirme mantigi)

    protected $fillable = ['name', 'city', 'logo_path'];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
