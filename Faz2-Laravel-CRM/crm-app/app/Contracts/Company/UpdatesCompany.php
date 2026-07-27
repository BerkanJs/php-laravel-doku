<?php

namespace App\Contracts\Company;

use App\Models\Company;
use Illuminate\Http\UploadedFile;


interface UpdatesCompany
{
    public function __invoke(Company $company, array $veri, ?UploadedFile $logo): Company;
}