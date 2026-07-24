<?php

namespace App\Http\Controllers;

use App\Jobs\SendDealCreatedEmail;
use App\Models\Company;
use App\Models\Deal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('deals.create', ['companies' => Company::all()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $veri = $request->validate([
            'title' => 'required|max:255',
            'amount' => 'required|numeric|min:0',
            'company_id' => 'required|exists:companies,id',
        ]);

        $deal = Deal::create($veri);

        dispatch(new SendDealCreatedEmail($deal));   // jobs tablosuna INSERT edilir, gercek isleme queue:work ile olur

        return redirect()->route('companies.show', $deal->company_id)->with('success', 'Fırsat eklendi, bildirim kuyruğa alındı.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function destroy(string $id)
    {
        //
    }
}
