<?php

namespace App\Contracts\Company;

use App\Models\Company;
use Illuminate\Http\UploadedFile;

interface CreatesCompany
{
    public function __invoke(array $veri, ?UploadedFile $logo): Company;
}
