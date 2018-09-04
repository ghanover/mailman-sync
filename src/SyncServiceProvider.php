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
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/mailmansync.php' => config_path('mailmansync.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('MailmanGateway', function ($app) {
            return new MailmanGateway();
        });
        $this->mergeConfigFrom(
            __DIR__ . '/../config/mailmansync.php', 'mailman'
        );
    }
}
