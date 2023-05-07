<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Resources\TransactionResource;
use App\Models\Balance;
use App\Models\BasicExpense;
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
    function camelize($input, $separator = '_')
    {
        return lcfirst(str_replace($separator, '', ucwords($input, $separator)));
    }

    public function getAllOfInstallmentAndComingSoon()
    {
        $transactions = DB::table('transactions')
            ->where("is_coming_soon", "=", "1")
            ->orWhere("is_installment", "=", "1")
            ->leftJoin('installments', 'transactions.id', '=', 'installments.transaction_id')
            ->select('transactions.*', 'installments.*', 'transactions.id as id', 'installments.id as installment_id')
            ->get();
        foreach ($transactions as $k => &$item) {
            $transactions[$k] = new TransactionResource($item);
        }
        unset($item);
        return response()->json($transactions);
    }

    public function getTransactionListByMonth(Request $request)
    {
        $transactions = Transaction::whereMonth('date', '=', $request->month)
            ->whereIn('transaction_type', $request->type)
            ->where('is_coming_soon', false)
            ->where('is_installment', false)
            ->orderBy('date')
            ->get();
        foreach ($transactions as $k => &$item) {
            $transactions[$k] = new TransactionResource($item);
        }
        unset($item);
        return response()->json($transactions);
    }

    public function getTransactionList(Request $request)
    {
        $total = DB::table('transactions')
            ->whereIn('transaction_type', $request->type)
            ->groupBy('transaction_type')
            ->selectRaw('transaction_type, sum(amount) as sum')
            ->get();

        $transactions = Transaction::whereIn('transaction_type', $request->type)
            ->where('is_coming_soon', false)
            ->where('is_installment', false)
            ->orderBy('date')
            ->get();
        foreach ($transactions as $k => &$item) {
            $transactions[$k] = new TransactionResource($item);
        }
        unset($item);
        return response()->json([
            'transactions' => $transactions,
            'total' => $total
        ]);
    }

    public function getBalanceSpecific(Request $request)
    {
        $expenseTotal = Expense::sum('total');
        $basicExpenseTotal = BasicExpense::sum('total');
        $incomeTotal = Income::sum('total');
        $loanTotal = Loan::sum('total');
        $lendTotal = Lend::sum('total');
        return response()->json([
            'balanceTotal' => $incomeTotal - $expenseTotal - $basicExpenseTotal,
            'savingsTotal' => $incomeTotal + $loanTotal - $expenseTotal - $basicExpenseTotal - $lendTotal,
            'expenseTotal' => $expenseTotal,
            'basicExpenseTotal' => $basicExpenseTotal,
            'incomeTotal' => $incomeTotal,
            'loanTotal' => $loanTotal,
            'lendTotal' => $lendTotal
        ]);
    }

    public function getExpenseSpecific(Request $request)
    {
        $expense = DB::table('transactions')
            ->selectRaw('category_type, sum(amount) as sum')
            ->groupBy('category_type')
            ->whereMonth('date', $request->month)
            ->where('transaction_type', TransactionType::EXPENSE)
            ->where('is_coming_soon', false)
            ->where('is_installment', false)
            ->get();
        return response()->json($expense);
    }

    public function getBalanceSpecificByMonth(Request $request)
    {
        $month = $request->input("month");
        $expenseTotal = Expense::where('month', $month)->value('total');
        $basicExpenseTotal = BasicExpense::where('month', $month)->value('total');
        $incomeTotal = Income::where('month', $month)->value('total');
        $loanTotal = Loan::where('month', $month)->value('total');
        $lendTotal = Lend::where('month', $month)->value('total');
        return response()->json([
            'balanceTotal' => $incomeTotal - $expenseTotal - $basicExpenseTotal,
            'savingsTotal' => $incomeTotal + $loanTotal - $expenseTotal - $basicExpenseTotal - $lendTotal,
            'expenseTotal' => $expenseTotal,
            'basicExpenseTotal' => $basicExpenseTotal,
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
        $date = $request->input("date");
        $amount = $request->input("amount");
        $transactionType = $request->input("transactionType");
        $isComingSoon = $request->input("isComingSoon");
        $isInstallment = $request->input("isInstallment");

        $transactionTypeName = strtolower(TransactionType::getKey($transactionType));
        $transactionTypeTableName = $transactionTypeName . "s";
        $transactionTypeRow = DB::table($transactionTypeTableName)->where('month', date('m', strtotime($date)));

        DB::transaction(function () use (
            $transactionTypeRow,
            $date,
            $request,
            $amount,
            $transactionType,
            $isComingSoon,
            $isInstallment
        ) {
            $transaction_id = DB::table('transactions')->insertGetId([
                'date' => $request->input("date"),
                'content' => $request->input("content"),
                'person' => $request->input("person"),
                'amount' => $request->input("amount"),
                'transaction_type' => $request->input("transactionType"),
                'is_coming_soon' => $request->input("isComingSoon"),
                'is_installment' => $request->input("isInstallment"),
                'category_type' => $request->input("categoryType"),
            ]);
            if ($isInstallment) {
                DB::table('installments')->insert([
                    'total' => $request->input("total"),
                    'start_date' => $request->input("startDate"),
                    'number_of_months' => $request->input("numberOfMonths"),
                    'total_of_months' => $request->input("totalOfMonths"),
                    'paid' => $request->input("paid"),
                    'paidCount' => $request->input("paidCount"),
                    'remaining' => $request->input("remaining"),
                    'transaction_id' => $transaction_id,
                ]);
            }
            if ($request->input('no_balance_effect')) {
                return;
            }
            if (!$isComingSoon || $isInstallment) {
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
            }


        });
    }

    public function paymentInstallment(Request $request)
    {
        $installment = DB::table("installments");
        $installment->where('id', $request->installmentId)
            ->update([
                'paid' => $request->input("paid"),
                'paidCount' => $request->input("paidCount"),
                'remaining' => $request->input("remaining"),
                'updated_at' => $request->input("date"),
            ]);
        $request->merge([
            'isInstallment' => 0,
            'no_balance_effect' => false,
        ]);
        $this->store($request);
        if ((integer)$request->input("remaining") <= 0) {
            if (!$installment->exists()) {
                return;
            }
            $installment->delete();
        }
    }

    public function storeMulti(Request $request)
    {
        $date = $request->date;
        $data = $request->transactions;
        $sum = $request->sum;
        $basicExpenseRow = DB::table('basic_expenses')->where('month', date('m', strtotime($date)));
        DB::transaction(function () use ($sum, $basicExpenseRow, $date, $data) {
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
        if ($transaction) {
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
    public function destroy(Request $request)
    {
        $date = $request->input("date");
        $amount = $request->input("amount");
        $transactionType = $request->input("transactionType");
        $isComingSoon = $request->input("isComingSoon");

        $transactionTypeName = strtolower(TransactionType::getKey($transactionType));

        $transactionTypeTableName = $transactionTypeName . "s";
        $transactionTypeRow = DB::table($transactionTypeTableName)->where('month', date('m', strtotime($date)));

        DB::transaction(function () use (
            $transactionTypeRow,
            $date,
            $request,
            $amount,
            $transactionType,
            $isComingSoon
        ) {
            $transactionRow = Transaction::where('id', $request->id);
            if (!$transactionRow->exists()) {
                return;
            }
            $transactionRow->delete();
            if ($isComingSoon) {
                $request->merge([
                    'isComingSoon' => 0,
                    'date' => date("Y-m-d H:i:s"),
                    'no_balance_effect' => true,
                ]);
                $this->store($request);
                return;
            }
            if ($transactionTypeRow->exists()) {
                $transactionTypeAmount = $transactionTypeRow->value("total");
                $transactionTypeRow->update([
                    'total' => (integer)$transactionTypeAmount - (integer)$amount
                ]);
            } else {
                $transactionTypeRow->insert([
                    'total' => (integer)$amount,
                    'month' => date('m', strtotime($date)),
                ]);
            }
        });
    }
}
