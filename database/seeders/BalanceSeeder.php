<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('balances')->insert([
            'total' => 0,
            'expenseTotal' => 0,
            'incomeTotal' => 0,
            'loanTotal' => 0,
            'lendTotal' => 0,
            'basicExpenseTotal' => 0,
        ]);
    }
}
