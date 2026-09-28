<?php
namespace MailmanSync\Test;

use MailmanSync\Facades\MailmanGateway as MailmanGatewayFacade;
use MailmanSync\MailmanGateway;
use MailmanSync\MailmanGatewayInterface;
use MailmanSync\MailmanGatewayMock;

class SyncServiceProviderTest extends PackageTestCase
{
    public function testPackageConfigIsMerged(): void
    {
        $this->assertSame('http://localhost/mailman', config('mailmansync.url'));
        $this->assertFalse(config('mailmansync.mock'));
        $this->assertSame([
            'user' => 'restadmin',
            'password' => 'supersecure',
        ], config('mailmansync.lists')['list.example.com']);
    }

    public function testContainerResolvesGatewayAndFacade(): void
    {
        $this->configureMailman();

        $gateway = $this->app->make(MailmanGatewayInterface::class);

        $this->assertInstanceOf(MailmanGateway::class, $gateway);
        $this->assertSame($gateway, $this->app->make('MailmanSync'));
        $this->assertSame($gateway, MailmanGatewayFacade::getFacadeRoot());
    }

    public function testContainerResolvesMockGateway(): void
    {
        $this->configureMailman();
        config(['mailmansync.mock' => true]);

        $gateway = $this->app->make(MailmanGatewayInterface::class);

        $this->assertInstanceOf(MailmanGatewayMock::class, $gateway);
        $this->assertSame($gateway, $this->app->make('MailmanSync'));
    }

    public function testConfigCanBePublished(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'mailmansync-config', '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertFileExists(config_path('mailmansync.php'));
    }
}
