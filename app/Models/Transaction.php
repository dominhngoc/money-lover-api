<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'transactions';
    protected $fillable = [
        'date',
        'content',
        'person',
        'amount',
        'transaction_type',
        'category_type',
        'isComingSoon',
        'isInstallment',
    ];

}
