<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class TransactionType extends Enum
{
    const EXPENSE = 1;
    const INCOME = 2;
    const LOAN = 3;
    const LEND = 4;
    const BASIC_EXPENSE = 5;
    const INVESTMENT = 6;
}
