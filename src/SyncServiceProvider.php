<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 8:18 PM
 */
namespace MailmanSync;

use Illuminate\Support\ServiceProvider;

class SyncServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/mailmansync.php' => config_path('mailmansync.php'),
        ], 'mailmansync-config');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/mailmansync.php',
            'mailmansync'
        );

        $this->app->singleton(MailmanGatewayInterface::class, function () {
            if (filter_var(config('mailmansync.mock'), FILTER_VALIDATE_BOOLEAN)) {
                return new MailmanGatewayMock();
            }

            return new MailmanGateway();
        });

        $this->app->alias(MailmanGatewayInterface::class, 'MailmanSync');
    }
}
