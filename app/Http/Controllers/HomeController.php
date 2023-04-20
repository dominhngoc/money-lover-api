<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Resources\TransactionResource;
use App\Models\Balance;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Lend;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionListByMonth(Request $request)
    {
        $transactions = Transaction::whereMonth('date', '=', $request->month)->whereIn('transaction_type', $request->type)->get();
        foreach ($transactions as $k => &$item) {
            $transactions[$k] = new TransactionResource($item);
        }
        unset($item);
        return response()->json($transactions);
    }

    public function getBalance()
    {
        $balance = Balance::where('id', 1)->get();
        return response()->json($balance);
    }

    public function getBalanceSpecific(Request $request)
    {
        $month = $request->input("month");
        $expenseTotal = Expense::where('month', $month)->value('total');
        $incomeTotal = Income::where('month', $month)->value('total');
        $loanTotal = Loan::where('month', $month)->value('total');
        $lendTotal = Lend::where('month', $month)->value('total');
        return response()->json([
            'expenseTotal'=>$expenseTotal,
            'incomeTotal' => $incomeTotal,
            'loanTotal' => $loanTotal,
            'lendTotal' => $lendTotal
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $balanceAmount = DB::table('balances')->value('total');
        $date = $request->input("date");
        $amount = $request->input("amount");
        $transactionType = $request->input("transactionType");

        $total = 0;
        if ($transactionType === TransactionType::EXPENSE
            || $transactionType === TransactionType::LEND) {
            $total = $balanceAmount - (integer)$amount;
        } else if ($transactionType === TransactionType::INCOME
            || $transactionType === TransactionType::LOAN) {
            $total = $balanceAmount + (integer)$amount;
        }
        $transactionTypeName = strtolower(TransactionType::getKey($transactionType));

        $transactionTypeTableName = $transactionTypeName . "s";
        $transactionTypeRow = DB::table($transactionTypeTableName)->where('month', date('m',strtotime($date)));

        $transactionTypeFieldName = $transactionTypeName . "Total";
        $fieldAmount = DB::table('balances')->value($transactionTypeFieldName);


        DB::transaction(function () use ($fieldAmount,
            $transactionTypeRow,
            $transactionTypeFieldName,
            $date,
            $request,
            $amount,
            $transactionType,
            $total) {
            DB::table('transactions')->insert([
                'date' => $request->input("date"),
                'content' => $request->input("content"),
                'person' => $request->input("person"),
                'amount' => $amount,
                'transaction_type' => $transactionType,
                'category_type' => $request->input("categoryType"),
            ]);
            if($transactionTypeRow->exists()) {
                $transactionTypeAmount = $transactionTypeRow->value("total");
                $transactionTypeRow->update([
                    'total' => (integer)$transactionTypeAmount + (integer)$amount
                ]);
            }else {
                $transactionTypeRow->insert([
                    'total' => (integer)$amount,
                    'month' => date('m',strtotime($date)),
                ]);
            }
            DB::table('balances')->where('id', 1)->update(['total' => $total, $transactionTypeFieldName => (integer)$amount + (integer)$fieldAmount]);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Transaction $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Transaction $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        Log::error((string)$request->input("date"));
        $transaction = Transaction::find($request->input("id"));
        if ($transaction) {
            $transaction->date = $request->input("date");
            $transaction->content = $request->input("content");
            $transaction->person = $request->input("person");
            $transaction->amount = $request->input("amount");
            $transaction->transaction_type = $request->input("transactionType");
            $transaction->category_type = $request->input("categoryType");
            $transaction->save();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Transaction $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        $deleted = Transaction::where('id', $id)->delete();
    }
}
