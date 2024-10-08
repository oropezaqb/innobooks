<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Ability;
use App\Models\Role;
use App\Models\CurrentCompany;
use App\Models\Document;
use App\Models\LineItem;
use App\Models\Account;
use App\Jobs\CreateCompany;

class CompanyController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth');
        //$this->middleware('web');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companies = \Auth::user()->companies()->latest()->get();
        return view('companies.index', ['companies' => $companies]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        try {
            \DB::transaction(function () {
                $this->validateCompany();
                $company = new Company(request(['name']));
                $company->code = substr(md5(microtime()), rand(0, 26), 6);
                $company->save();
                $createCompany = new CreateCompany();
                $createCompany->run($company);
            });
            return redirect(route('dashboard'))
                ->with('status', 'Company created! You may now start adding items through the navigation pane.');
        } catch (\Exception $e) {
            return back()->with('status', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        return view('companies.show', ['company' => $company]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Company $company)
    {
        try {
            $company->update($this->validateCompany());
            return redirect($company->path());
        } catch (\Exception $e) {
            return back()->with('status', $e->getMessage());
        }
    }

    public function validateCompany()
    {
        return request()->validate([
            'name' => 'required'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        $company->delete();
        return redirect(route('companies.index'));
    }
}
