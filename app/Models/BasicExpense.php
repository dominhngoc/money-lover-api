<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicExpense extends Model
{
    use HasFactory;
    protected $table = 'basicExpense';
    protected $fillable = [
        'total',
        'month',
    ];
}
