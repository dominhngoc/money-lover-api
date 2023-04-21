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
            'expenseTotal' => $expenseTotal,
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
            if ($transactionTypeRow->exists()) {
                $transactionTypeAmount = $transactionTypeRow->value("total");
                $transactionTypeRow->update([
                    'total' => (integer)$transactionTypeAmount + (integer)$amount
                ]);
            } else {
                $transactionTypeRow->insert([
                    'total' => (integer)$amount,
                    'month' => date('m', strtotime($date)),
                ]);
            }
            DB::table('balances')->where('id', 1)->update(['total' => $total, $transactionTypeFieldName => (integer)$amount + (integer)$fieldAmount]);
        });
    }
    public function storeMulti(Request $request){
        $date = $request->date;
        $data = $request->transactions;
        $sum = $request->sum;
        $basicExpenseRow = DB::table('basic_expenses')->where('month', date('m',strtotime($date)));
        DB::transaction(function () use ($sum, $basicExpenseRow, $date, $data){
            DB::table('transactions')->insert($data);
            if ($basicExpenseRow->exists()) {
                $basicExpenseAmount = $basicExpenseRow->value("total");
                $basicExpenseRow->update([
                    'total' => (integer)$basicExpenseAmount + $sum
                ]);
            } else {
                $basicExpenseRow->insert([
                    'total' => $sum,
                    'month' => date('m', strtotime($date)),
                ]);
            }
            $balanceAmount = DB::table('balances')->value('total');
            $balanceBasicExpenseAmount = DB::table('balances')->value('basicExpenseTotal');
            DB::table('balances')->where('id', 1)->update(['total' => $balanceAmount + $sum, 'basicExpenseTotal' => (integer)$balanceBasicExpenseAmount + $sum]);
        });

    }
    /**
     * Display the specified resource.
     *
     * @param \App\Models\Transaction $transaction
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $transaction = DB::table("transactions")->where('id', $id)->first();
        if($transaction){
            return response()->json(new TransactionResource($transaction));
        }
        return response()->json();
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
        $transaction = DB::table("transactions")
            ->where('id', $request->id)
            ->update([
                'date' => $request->input("date"),
                'content' => $request->input("content"),
                'person' => $request->input("person"),
                'amount' => $request->input("amount"),
                'transaction_type' => $request->input("transactionType"),
                'category_type' => $request->input("categoryType"),
            ]);
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
