<?php

namespace App\Http\Controllers;

use App\Models\ReceivedPayment;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;
use JavaScript;
use App\Models\ReceivedPaymentLine;
use App\Http\Requests\StoreReceivedPayment;
use App\Jobs\CreateReceivedPayment;

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UndefinedVariable)
     * @SuppressWarnings(PHPMD.ShortVariableName)
     */

class ReceivedPaymentController extends Controller
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
        if (empty(request('customer_name'))) {
            $receivedPayments = ReceivedPayment::where('company_id', $company->id)->latest()->get();
        } else {
            $customer = Customer::where('name', request('customer_name'))->firstOrFail();
            $receivedPayments = ReceivedPayment::where('company_id', $company->id)
                ->where('customer_id', $customer->id)->latest()->get();
        }
        if (\Route::currentRouteName() === 'received_payments.index') {
            \Request::flash();
        }
        return view('received_payments.index', compact('receivedPayments'));
    }
    public function create()
    {
        $company = \Auth::user()->currentCompany->company;
        $customers = Customer::where('company_id', $company->id)->latest()->get();
        $accounts = Account::where('company_id', $company->id)->latest()->get();
        return view(
            'received_payments.create',
            compact('customers', 'accounts')
        );
    }
    public function store(StoreReceivedPayment $request)
    {
        try {
            \DB::transaction(function () use ($request) {
        $company = \Auth::user()->currentCompany->company;
        $receivedPayment = new ReceivedPayment([
            'company_id' => $company->id,
            'date' => request('date'),
            'customer_id' => request('customer_id'),
            'number' => request('number'),
            'account_id' => request('account_id')
        ]);
        $receivedPayment->save();
        if (!is_null(request("item_lines.'invoice_id'"))) {
            $count = count(request("item_lines.'invoice_id'"));
            for ($row = 0; $row < $count; $row++) {
                if (is_numeric(request("item_lines.'payment'.".$row)) && request("item_lines.'payment'.".$row) > 0) {
                    $receivedPaymentLine = new ReceivedPaymentLine([
                        'company_id' => $company->id,
                        'received_payment_id' => $receivedPayment->id,
                        'invoice_id' => request("item_lines.'invoice_id'.".$row),
                        'amount' => request("item_lines.'payment'.".$row)
                    ]);
                    $receivedPaymentLine->save();
                }
            }
        }
        $createRecvPayment = new CreateReceivedPayment();
        $createRecvPayment->recordJournalEntry($receivedPayment);
            });
            return redirect(route('received_payments.index'));
        } catch (\Exception $e) {
            return back()->with('status', $this->translateError($e))->withInput();
        }
//        return redirect(route('received_payments.index'));
    }
    public function show(ReceivedPayment $receivedPayment)
    {
        $company = \Auth::user()->currentCompany->company;
        $customers = Customer::where('company_id', $company->id)->latest()->get();
        $accounts = Account::where('company_id', $company->id)->latest()->get();
        $unpaidInvoicesIds = array();
        return view(
            'received_payments.show',
            compact('receivedPayment', 'customers', 'accounts', 'unpaidInvoicesIds')
        );
    }
    public function edit(ReceivedPayment $receivedPayment)
    {
        $company = \Auth::user()->currentCompany->company;
        $customers = Customer::where('company_id', $company->id)->latest()->get();
        $accounts = Account::where('company_id', $company->id)->latest()->get();
        $customer = Customer::find($receivedPayment->customer_id);
        $invoices = Invoice::where('customer_id', $customer->id)->get();
        $recvPaymentInv = array();
        foreach ($receivedPayment->lines as $line) {
            $recvPaymentInv[] = $line->invoice_id;
        }
        $unpaidInvoicesIds = array();
        foreach ($invoices as $invoice) {
            $amountReceivable = $invoice->itemLines->sum('amount') + $invoice->itemLines->sum('output_tax');
            $totalAmountPaid = \DB::table('received_payment_lines')->where('invoice_id', $invoice->id)->sum('amount');
            $receivedPaymentTotalAmount = \DB::table('received_payment_lines')->where('received_payment_id', $receivedPayment->id)->sum('amount');
            $balance = $amountReceivable - ($totalAmountPaid - $receivedPaymentTotalAmount);
            if ($balance > 0 && !in_array($invoice->id, $recvPaymentInv)) {
                $unpaidInvoicesIds[] = array(
                    'invoice_id' => $invoice->id,
                    'number' => $invoice->invoice_number,
                    'date' => $invoice->date,
                    'due_date' => $invoice->due_date,
                    'amount' => $amountReceivable,
                    'balance' => $balance
                );
            }
        }
        return view(
            'received_payments.edit',
            compact('receivedPayment', 'customers', 'accounts', 'unpaidInvoicesIds')
        );
    }
    public function update(StoreReceivedPayment $request, ReceivedPayment $receivedPayment)
    {
        $company = \Auth::user()->currentCompany->company;
        $receivedPayment->update([
            'company_id' => $company->id,
            'date' => request('date'),
            'customer_id' => request('customer_id'),
            'number' => request('number'),
            'account_id' => request('account_id')
        ]);
        $receivedPayment->save();
        foreach ($receivedPayment->lines as $line) {
            $line->delete();
        }
        if (!is_null(request("item_lines.'invoice_id'"))) {
            $count = count(request("item_lines.'invoice_id'"));
            for ($row = 0; $row < $count; $row++) {
                if (is_numeric(request("item_lines.'payment'.".$row)) && request("item_lines.'payment'.".$row) > 0) {
                    $receivedPaymentLine = new ReceivedPaymentLine([
                        'company_id' => $company->id,
                        'received_payment_id' => $receivedPayment->id,
                        'invoice_id' => request("item_lines.'invoice_id'.".$row),
                        'amount' => request("item_lines.'payment'.".$row)
                    ]);
                    $receivedPaymentLine->save();
                }
            }
        }
        $receivedPayment->journalEntry()->delete();
        $createRecvPayment = new CreateReceivedPayment();
        $createRecvPayment->deleteReceivedPayment($receivedPayment);
        $createRecvPayment->recordJournalEntry($receivedPayment);
        return redirect(route('received_payments.index'));
    }
    public function destroy(ReceivedPayment $receivedPayment)
    {
        $receivedPayment->delete();
        return redirect(route('received_payments.index'));
    }
}
