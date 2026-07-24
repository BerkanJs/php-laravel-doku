<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = Company::all();   // Gun 15-18'deki sabit dizinin yerini artik gercek Eloquent sorgusu aldi

        return view('companies.index', ['companies' => $companies]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $veri = $request->validate([
            'name' => 'required|max:255',
            'city' => 'nullable|max:255',
            'logo' => 'nullable|image|max:2048',   // resim olmali, en fazla 2048 KB
        ]);

        if ($request->hasFile('logo')) {
            // store() -> storage/app/public/logos/ altina rastgele isimle kaydeder, $veri['logo_path']'e YOLU atar (dosyanin kendisini degil)
            $veri['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        unset($veri['logo']);   // 'logo' -> $fillable'da yok, Company::create'e dogrudan gecirmiyoruz

        $company = Company::create($veri);

        return redirect()->route('companies.show', $company)->with('success', 'Şirket eklendi.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        // Company $company -> Gun 15'teki route model binding: {company} otomatik Company nesnesine cevrildi
        // load() -> zaten elimizdeki $company nesnesine iliskiyi sonradan eager-load eder (with() ile ayni SQL mantigi)
        $company->load('contacts', 'deals');

        return view('companies.show', ['company' => $company]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $this->authorize('delete', $company);   // admin degilse buradan AuthorizationException (403) firlar, asagisi hic calismaz

        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Şirket silindi.');
    }
}
