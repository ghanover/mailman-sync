<?php
namespace MailmanSync\Test;

use MailmanSync\Facades\MailmanGateway;
use MailmanSync\SyncServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class PackageTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            SyncServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MailmanGateway' => MailmanGateway::class,
        ];
    }

    /**
     * @param array<string, array{user: string, password: string}> $lists
     */
    protected function configureMailman(array $lists = []): void
    {
        config([
            'mailmansync.url' => 'http://localhost/mailman',
            'mailmansync.mock' => false,
            'mailmansync.lists' => $lists + [
                'list.example.com' => [
                    'user' => 'restadmin',
                    'password' => 'supersecure',
                ],
                'list' => [
                    'user' => 'restadmin',
                    'password' => 'supersecure',
                ],
                'test' => [
                    'user' => 'restadmin',
                    'password' => 'supersecure',
                ],
            ],
        ]);
    }
}
