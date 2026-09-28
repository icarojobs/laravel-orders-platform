<?php

namespace App\Providers;

use App\Domain\Orders\Contracts\OrderEventPublisher;
use App\Domain\Orders\Contracts\OrderNumberGenerator;
use App\Domain\Orders\Support\DateBasedOrderNumberGenerator;
use App\Infrastructure\Messaging\LogOrderEventPublisher;
use App\Infrastructure\Messaging\RabbitMqOrderEventPublisher;
use App\Listeners\FlushSalesReportCache;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OrderNumberGenerator::class, DateBasedOrderNumberGenerator::class);

        $this->app->singleton(OrderEventPublisher::class, fn ($app) => match ($app['config']->get('messaging.driver')) {
            'rabbitmq' => new RabbitMqOrderEventPublisher($app['config']->get('messaging.rabbitmq')),
            default => $app->make(LogOrderEventPublisher::class),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Event::listen('eloquent.saved: '.Order::class, FlushSalesReportCache::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::preventLazyLoading(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
