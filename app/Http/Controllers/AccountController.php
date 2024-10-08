<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use App\Models\LineItem;

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */

class AccountController extends Controller
{
    public function __construct()
    {
//        $this->middleware('auth');
//        $this->middleware('company');
//        $this->middleware('web');
    }
    public function index()
    {
        $company = \Auth::user()->currentCompany->company;
        if (empty(request('title'))) {
            $accounts = Account::where('company_id', $company->id)->latest()->get();
        } else {
            $accounts = Account::where('company_id', $company->id)
                ->where('title', 'like', '%' . request('title') . '%')->get();
        }
        if (\Route::currentRouteName() === 'accounts.index') {
            \Request::flash();
        }
        return view('accounts.index', compact('accounts'));
    }
    public function show(Account $account)
    {
        return view('accounts.show', compact('account'));
    }
    public function create()
    {
        $company = \Auth::user()->currentCompany->company;
        $lineItems = LineItem::where('company_id', $company->id)->latest()->get();
        if (\Route::currentRouteName() === 'accounts.create') {
            \Request::flash();
        }
        return view('accounts.create', compact('lineItems'));
    }
    public function store()
    {
        $this->validateAccount();
        $company = \Auth::user()->currentCompany->company;
        $subsidiaryLedger = false;
        if (request('subsidiary_ledger') == "on") {
            $subsidiaryLedger = true;
        }
        $account = new Account([
            'company_id' => $company->id,
            'number' => request('number'),
            'title' => request('title'),
            'type' => request('type'),
            'line_item_id' => request('line_item_id'),
            'subsidiary_ledger' => $subsidiaryLedger
        ]);
        $account->save();
        return redirect(route('accounts.index'));
    }
    public function edit(Account $account)
    {
        $company = \Auth::user()->currentCompany->company;
        $lineItems = LineItem::where('company_id', $company->id)->latest()->get();
        if (\Route::currentRouteName() === 'accounts.edit') {
            \Request::flash();
        }
        return view('accounts.edit', compact('account', 'lineItems'));
    }
    public function update(Account $account)
    {
        $this->validateAccount();
        $subsidiaryLedger = true;
        if (empty(request('subsidiary_ledger'))) {
            $subsidiaryLedger = false;
        }
        $account->update([
            'number' => request('number'),
            'title' => request('title'),
            'type' => request('type'),
            'line_item_id' => request('line_item_id'),
            'subsidiary_ledger' => $subsidiaryLedger
        ]);
        return redirect($account->path());
    }
    public function validateAccount()
    {
        return request()->validate([
            'number' => 'required',
            'title' => 'required',
            'type' => 'required',
            'line_item_id' => 'required'
        ]);
    }
    public function destroy(Account $account)
    {
        $account->delete();
        return redirect(route('accounts.index'));
    }
}
