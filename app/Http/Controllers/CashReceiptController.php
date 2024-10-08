<?php

namespace App\Http\Controllers;

use App\Models\CashReceipt;
use App\Models\Account;
use App\Models\SubsidiaryLedger;
use Illuminate\Http\Request;

class CashReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $company = \Auth::user()->currentCompany->company;

        $cashReceipts = CashReceipt::where('company_id', $company->id)->latest()->get();

        return view('cash_receipts.index', compact('cashReceipts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $company = \Auth::user()->currentCompany->company;

        $accounts = Account::where('company_id', $company->id)->latest()->get();

        $subsidiaryLedgers = SubsidiaryLedger::where('company_id', $company->id)->latest()->get();

        return view('cash_receipts.create', compact('accounts', 'subsidiaryLedgers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function show(CashReceipt $cashReceipt)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function edit(CashReceipt $cashReceipt)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CashReceipt $cashReceipt)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function destroy(CashReceipt $cashReceipt)
    {
        //
    }
}
