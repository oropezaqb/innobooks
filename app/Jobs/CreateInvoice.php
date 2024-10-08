<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Account;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Document;
use App\Models\JournalEntry;
use App\Models\Posting;
use App\Models\SubsidiaryLedger;
use App\Models\Transaction;
use App\Models\InvoiceItemLine;

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */

class CreateInvoice
{
    public function recordSales($invoice, $input)
    {
        $count = count($input['item_lines']["'product_id'"], 1);
        if ($count > 0) {
            for ($row = 0; $row < $count; $row++) {
                $product = Product::find($input['item_lines']["'product_id'"][$row]);
                if ($product->track_quantity) {
                    $numberRecorded = 0;
                    do {
                        $company = \Auth::user()->currentCompany->company;
                        $purchase = $this->determinePurchaseSold($company, $product);
                        if (is_object($purchase)) {
                            $numberUnrecorded = $input['item_lines']["'quantity'"][$row] - $numberRecorded;
                            $quantity = $this->determineQuantitySold($company, $purchase, $numberUnrecorded);
                            $amount = $this->determineAmountSold($company, $purchase, $numberUnrecorded);
                            $sale = new Sale([
                                'company_id' => $company->id,
                                'purchase_id' => $purchase->id,
                                'date' => $input['date'],
                                'product_id' => $product->id,
                                'quantity' => $quantity,
                                'amount' => $amount
                            ]);
                            $invoice->sales()->save($sale);
                            $numberRecorded += $quantity;
                        } else {
                            break;
                        }
                    } while ($numberRecorded < $input['item_lines']["'quantity'"][$row]);
                }
            }
        }
    }
    public function determinePurchaseSold($company, $product)
    {
        $allPurchases = Purchase::where('company_id', $company->id)->where('product_id', $product->id)->get();
        $purchases = $allPurchases->sortBy('date');
        foreach ($purchases as $purchase) {
            $numberSold = Sale::where('company_id', $company->id)->where('purchase_id', $purchase->id)->sum('quantity');
            if ($numberSold < $purchase->quantity) {
                return $purchase;
            }
        }
    }
    public function determineQuantitySold($company, $purchase, $numberUnrecorded)
    {
        $numberSold = Sale::where('company_id', $company->id)->where('purchase_id', $purchase->id)->sum('quantity');
        $numberUnsold = $purchase->quantity - $numberSold;
        if ($numberUnrecorded < $numberUnsold) {
            return $numberUnrecorded;
        } else {
            return $numberUnsold;
        }
    }
    public function determineAmountSold($company, $purchase, $numberUnrecorded)
    {
        $numberSold = Sale::where('company_id', $company->id)->where('purchase_id', $purchase->id)->sum('quantity');
        $numberUnsold = $purchase->quantity - $numberSold;
        $amountSold = Sale::where('company_id', $company->id)->where('purchase_id', $purchase->id)->sum('amount');
        $amountUnsold = $purchase->amount - $amountSold;
        if ($numberUnrecorded < $numberUnsold) {
            $costOfSales = round($amountUnsold / $numberUnsold * $numberUnrecorded, 2);
            return $costOfSales;
        } else {
            return $amountUnsold;
        }
    }
    public function recordJournalEntry($invoice, $input)
    {
        $company = \Auth::user()->currentCompany->company;
        $document = Document::firstOrCreate(['name' => 'Invoice', 'company_id' => $company->id]);
        $receivableAccount = Account::where('title', 'Accounts Receivable')->firstOrFail();
        $taxAccount = Account::where('title', 'Output VAT')->firstOrFail();
        $customer = Customer::all()->find($input['customer_id']);
        $receivableSubsidiary = SubsidiaryLedger::where('name', $customer->name)
            ->firstOrCreate(['name' => $customer->name, 'company_id' => $company->id]);
        $journalEntry = new JournalEntry([
            'company_id' => $company->id,
            'date' => $input['date'],
            'document_type_id' => $document->id,
            'document_number' => $input['invoice_number'],
            'explanation' => 'To record sale of goods on account.'
        ]);
        $invoice->journalEntry()->save($journalEntry);
        $receivableAmount = 0;
        $taxAmount = 0;
        $count = count($input['item_lines']["'product_id'"]);
        if ($count > 0) {
            for ($row = 0; $row < $count; $row++) {
                $inputTax = 0;
                if (!is_null($input['item_lines']["'output_tax'"][$row])) {
                    $inputTax = $input['item_lines']["'output_tax'"][$row];
                }
                $product = Product::find($input['item_lines']["'product_id'"][$row]);
                $debit = -$input['item_lines']["'amount'"][$row];
                $posting = new Posting([
                    'company_id' => $company->id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $product->incomeAccount->id,
                    'debit' => $debit
                ]);
                $posting->save();
                $receivableAmount += $input['item_lines']["'amount'"][$row] + $inputTax;
                $taxAmount -= $inputTax;
            }
        }
        if ($taxAmount != 0) {
            $posting = new Posting([
                'company_id' => $company->id,
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $taxAccount->id,
                'debit' => $taxAmount
            ]);
            $posting->save();
        }
        $posting = new Posting([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $receivableAccount->id,
            'debit' => $receivableAmount,
            'subsidiary_ledger_id' => $receivableSubsidiary->id
        ]);
        $posting->save();
        $this->recordCost($invoice, $company, $journalEntry);
    }
    public function recordCost($invoice, $company, $journalEntry)
    {
        foreach ($invoice->sales as $sale) {
            $product = Product::find($sale->product_id);
            $posting = new Posting([
                'company_id' => $company->id,
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $product->expenseAccount->id,
                'debit' => $sale->amount
            ]);
            $posting->save();
            $debit = -$sale->amount;
            $inventoryPosting = new Posting([
                'company_id' => $company->id,
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $product->inventoryAccount->id,
                'debit' => $debit
            ]);
            $inventoryPosting->save();
        }
    }
    public function recordTransaction($invoice)
    {
        $company = \Auth::user()->currentCompany->company;
        $transaction = new Transaction([
            'company_id' => $company->id,
            'type' => 'sale',
            'date' => request('date')
        ]);
        $invoice->transaction()->save($transaction);
    }
    public function updateSales($salesForUpdate)
    {
        foreach ($salesForUpdate as $saleForUpdate) {
            $transactions = Transaction::all();
            $transaction = $transactions->find($saleForUpdate->id);
            $invoice = $transaction->transactable;
            if (is_object($invoice->journalEntry)) {
                foreach ($invoice->journalEntry->postings as $posting) {
                    $posting->delete();
                }
                $invoice->journalEntry->delete();
            }
            if (is_object($invoice->sales)) {
                $sales = $invoice->sales;
                foreach ($sales as $sale) {
                    $sale->delete();
                }
            }
        }
        foreach ($salesForUpdate as $saleForUpdate) {
            $transactions = Transaction::all();
            $transaction = $transactions->find($saleForUpdate->id);
            $invoice = $transaction->transactable;
            $input = array();
            $row = 0;
            $input['customer_id'] = $invoice->customer_id;
            $input['date'] = $invoice->date;
            $input['invoice_number'] = $invoice->invoice_number;
            foreach ($invoice->itemLines as $itemLine) {
                $input['item_lines']["'product_id'"][$row] = $itemLine->product_id;
                $input['item_lines']["'description'"][$row] = $itemLine->description;
                $input['item_lines']["'quantity'"][$row] = $itemLine->quantity;
                $input['item_lines']["'amount'"][$row] = $itemLine->amount;
                $input['item_lines']["'output_tax'"][$row] = $itemLine->output_tax;
                $row += 1;
            }
            $createInvoice = new CreateInvoice();
            $createInvoice->recordSales($invoice, $input);
            $createInvoice->recordJournalEntry($invoice, $input);
        }
    }
    public function updateLines($invoice)
    {
        if (!is_null(request("item_lines.'product_id'"))) {
            $count = count(request("item_lines.'product_id'"));
            for ($row = 0; $row < $count; $row++) {
                $outputTax = 0;
                if (!is_null(request("item_lines.'output_tax'.".$row))) {
                    $outputTax = request("item_lines.'output_tax'.".$row);
                }
                $itemLine = new InvoiceItemLine([
                    'invoice_id' => $invoice->id,
                    'product_id' => request("item_lines.'product_id'.".$row),
                    'description' => request("item_lines.'description'.".$row),
                    'quantity' => request("item_lines.'quantity'.".$row),
                    'amount' => request("item_lines.'amount'.".$row),
                    'output_tax' => $outputTax
                ]);
                $itemLine->save();
            }
        }
    }
    public function deleteInvoiceDetails($invoice)
    {
        foreach ($invoice->itemLines as $itemLine) {
            $itemLine->delete();
        }
        foreach ($invoice->sales as $sale) {
            $sale->delete();
        }
    }
}
