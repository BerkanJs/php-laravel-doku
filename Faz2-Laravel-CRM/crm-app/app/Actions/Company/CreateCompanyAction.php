<?php

namespace App\Actions\Company;

use App\Contracts\Company\CreatesCompany;
use App\Models\Company;
use Illuminate\Http\UploadedFile;

class CreateCompanyAction implements CreatesCompany
{
    public function __invoke(array $veri, ?UploadedFile $logo): Company
    {
        if ($logo) {
            $veri['logo_path'] = $logo->store('logos', 'public');
        }
        unset($veri['logo']);

        return Company::create($veri);
    }
}
