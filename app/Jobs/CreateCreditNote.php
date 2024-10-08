<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Account;
use App\Models\Product;
use App\Models\Document;
use App\Models\SubsidiaryLedger;
use App\Models\Transaction;
use App\Models\CreditNoteLine;
use App\Models\InvoiceItemLine;
use App\Models\SalesReturn;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Posting;
use App\Models\Purchase;

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */

class CreateCreditNote
{
    public function determineAmounts($invoiceId, $invoiceLineId, $quantity, $creditNoteId)
    {
        $invoiceLine = Product::find($invoiceLineId);
        $quantitySold = InvoiceItemLine::where('invoice_id', $invoiceId)
            ->where('product_id', $invoiceLine->id)->sum('quantity');
        $amountSold = InvoiceItemLine::where('invoice_id', $invoiceId)
            ->where('product_id', $invoiceLine->id)->sum('amount');
        $taxSold = InvoiceItemLine::where('invoice_id', $invoiceId)
            ->where('product_id', $invoiceLine->id)->sum('output_tax');
        $qtyReturnForThisCN = 0;
        if (!is_null($creditNoteId)) {
            $qtyReturnForThisCN = CreditNoteLine::where('credit_note_id', $creditNoteId)
                ->where('invoice_id', $invoiceId)
                ->where('product_id', $invoiceLine->id)->sum('quantity');
        }
        $quantityReturned = CreditNoteLine::where('invoice_id', $invoiceId)
            ->where('product_id', $invoiceLine->id)->sum('quantity');
        $amtReturnForThisCN = 0;
        if (!is_null($creditNoteId)) {
            $amtReturnForThisCN = CreditNoteLine::where('credit_note_id', $creditNoteId)
                ->where('invoice_id', $invoiceId)
                ->where('product_id', $invoiceLine->id)->sum('amount');
        }
        $amountReturned = CreditNoteLine::where('invoice_id', $invoiceId)
            ->where('product_id', $invoiceLine->id)->sum('amount');
        $taxReturnForThisCN = 0;
        if (!is_null($creditNoteId)) {
            $taxReturnForThisCN = CreditNoteLine::where('credit_note_id', $creditNoteId)
                ->where('invoice_id', $invoiceId)
                ->where('product_id', $invoiceLine->id)->sum('output_tax');
        }
        $taxReturned = CreditNoteLine::where('invoice_id', $invoiceId)
            ->where('product_id', $invoiceLine->id)->sum('output_tax');
        $quantityUnreturned = $quantitySold - ($quantityReturned - $qtyReturnForThisCN);
        $amountUnreturned = $amountSold - ($amountReturned - $amtReturnForThisCN);
        $taxUnreturned = $taxSold - ($taxReturned - $taxReturnForThisCN);
        $amounts = array();
        $amounts['amount'] = 0;
        $amounts['tax'] = 0;
        $amounts['amount_unreturned'] = $amountUnreturned;
        $amounts['tax_unreturned'] = $taxUnreturned;
        $amounts['quantity_unreturned'] = $quantityUnreturned;
        if (($quantity > 0) && ($quantity < $quantityUnreturned)) {
            $amounts['amount'] = round(($amountUnreturned / $quantityUnreturned) * $quantity, 2);
            $amounts['tax'] = round(($taxUnreturned / $quantityUnreturned) * $quantity, 2);
        }
        if ($quantity == $quantityUnreturned) {
            $amounts['amount'] = $amountUnreturned;
            $amounts['tax'] = $taxUnreturned;
        }
        return $amounts;
    }
    public function updateLines($creditNote)
    {
        if (!is_null(request("item_lines.'product_id'"))) {
            $count = count(request("item_lines.'product_id'"));
            for ($row = 0; $row < $count; $row++) {
                $outputTax = 0;
                if (!is_null(request("item_lines.'output_tax'.".$row))) {
                    $outputTax = request("item_lines.'output_tax'.".$row);
                }
                if (!is_null(request("item_lines.'amount'.".$row)) &&
                    is_numeric(request("item_lines.'amount'.".$row))) {
                    if (request("item_lines.'amount'.".$row) > 0) {
                        $itemLine = new CreditNoteLine([
                            'credit_note_id' => $creditNote->id,
                            'invoice_id' => request('invoice_id'),
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
        }
    }
    public function recordJournalEntry($creditNote, $input)
    {
        $company = \Auth::user()->currentCompany->company;
        $document = Document::firstOrCreate(['name' => 'Credit Note', 'company_id' => $company->id]);
        $receivableAccount = Account::where('company_id', $company->id)
            ->where('title', 'Accounts Receivable')->firstOrFail();
        $taxAccount = Account::where('company_id', $company->id)->where('title', 'Output VAT')->firstOrFail();
        $customer = Customer::all()->find($input['customer_id']);
        $receivableSubsidiary = SubsidiaryLedger::where('name', $customer->name)
            ->firstOrCreate(['name' => $customer->name, 'company_id' => $company->id]);
        $journalEntry = new JournalEntry([
            'company_id' => $company->id,
            'date' => $input['date'],
            'document_type_id' => $document->id,
            'document_number' => $input['number'],
            'explanation' => 'To record return of goods from a customer.'
        ]);
        $creditNote->journalEntry()->save($journalEntry);
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
                $debit = $input['item_lines']["'amount'"][$row];
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
                'debit' => -$taxAmount
            ]);
            $posting->save();
        }
        $posting = new Posting([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $receivableAccount->id,
            'debit' => -$receivableAmount,
            'subsidiary_ledger_id' => $receivableSubsidiary->id
        ]);
        $posting->save();
        $this->recordCost($creditNote, $company, $journalEntry);
    }
    public function recordCost($creditNote, $company, $journalEntry)
    {
        foreach ($creditNote->salesReturns as $salesReturn) {
            $product = Product::find($salesReturn->product_id);
            $posting = new Posting([
                'company_id' => $company->id,
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $product->expenseAccount->id,
                'debit' => -$salesReturn->amount
            ]);
            $posting->save();
            $debit = $salesReturn->amount;
            $inventoryPosting = new Posting([
                'company_id' => $company->id,
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $product->inventoryAccount->id,
                'debit' => $debit
            ]);
            $inventoryPosting->save();
        }
    }
    public function recordTransaction($creditNote)
    {
        $company = \Auth::user()->currentCompany->company;
        $transaction = new Transaction([
            'company_id' => $company->id,
            'type' => 'sales_return',
            'date' => request('date')
        ]);
        $creditNote->transaction()->save($transaction);
    }
    public function deleteSalesReturn($transaction)
    {
        $creditNote = $transaction->transactable;
        if (is_object($creditNote->journalEntry)) {
            foreach ($creditNote->journalEntry->postings as $posting) {
                $posting->delete();
            }
            $creditNote->journalEntry->delete();
        }
        if (is_object($creditNote->salesReturns)) {
            $sales = $creditNote->salesReturns;
            foreach ($sales as $sale) {
                $sale->delete();
            }
        }
    }
    public function recordPurchases($creditNote)
    {
        $salesReturns = $creditNote->lines;
        if (!is_null($salesReturns)) {
            foreach ($salesReturns as $salesReturn) {
                $product = Product::find($salesReturn->product_id);
                if ($product->track_quantity) {
                    $company = \Auth::user()->currentCompany->company;
                    $purchase = new Purchase([
                        'company_id' => $company->id,
                        'date' => $creditNote->date,
                        'product_id' => $salesReturn->product_id,
                        'quantity' => $salesReturn->quantity,
                        'amount' => $salesReturn->amount
                    ]);
                    $creditNote->purchases()->save($purchase);
                }
            }
        }
    }
    public function deleteCreditNote($creditNote)
    {
        $creditNote->journalEntry->delete();
        $creditNote->transaction->delete();
        foreach ($creditNote->lines as $line) {
            $line->delete();
        }
        foreach ($creditNote->salesReturns as $salesReturn) {
            $salesReturn->delete();
        }
        foreach ($creditNote->purchases as $purchase) {
            $purchase->delete();
        }
    }
}
