<?php

namespace App\Actions\Company;

use App\Contracts\Company\UpdatesCompany;
use App\Models\Company;
use Illuminate\Http\UploadedFile;

class UpdateCompanyAction implements UpdatesCompany
{
    public function __invoke(Company $company, array $veri, ?UploadedFile $logo): Company
    {
        if ($logo) {
            $veri['logo_path'] = $logo->store('logos', 'public');
        }
        unset($veri['logo']);

        $company->update($veri);

        return $company;
    }
}
