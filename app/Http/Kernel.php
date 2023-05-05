<?php

namespace App\Http;

use App\Http\Controllers\HomeController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Type\Integer;

class Kernel extends HttpKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $homeController = new HomeController();
        $schedule->call(function () use ($homeController) {
//            get all installment
            $installments = DB::table("installments")
                ->join('transactions', 'installments.transaction_id', '=', 'transactions.id')
                ->get();
            foreach ($installments as $installment) {
                $request = new \Illuminate\Http\Request();
                $request->replace([
                    'date' => $installment->date,
                    'content' => $installment->content,
                    'person' => $installment->person,
                    'amount' => $installment->amount,
                    'transaction_type' => $installment->transactionType,
                    'is_coming_soon' => $installment->isComingSoon,
                    'is_installment' => $installment->isInstallment,
                    'category_type' => $installment->categoryType,
                ]);
                //check date same today
                $now = date("m-d-Y");
                $start_date = date("m-d-Y",$installment->start_date);
                if($now == $start_date){
                    //            create transaction
                    $homeController->store($request);
//            update balance installment
                    $transaction = DB::table("installments")
                        ->where('id', $installment->id)
                        ->update([
                            'paid' => (Integer)$installment->paid + (Integer)$installment->total_of_months,
                            'paidCount' => (Integer)$installment->paidCount + 1,
                            'remaining' => (Integer)$installment->total - ((Integer)$installment->paid + (Integer)$installment->total_of_months),
                        ]);
                }
            }

        })->dailyAt('09:33');
    }

    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Fruitcake\Cors\HandleCors::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
    ];
}
