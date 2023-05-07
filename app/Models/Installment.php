<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;
    protected $table = 'installments';
    protected $fillable = [
        'total',
        'number_of_months',
        'start_date',
        'total_of_months',
        'paid',
        'paidCount',
        'remaining',
        'transaction_id',
        'updated_at',
    ];
}
