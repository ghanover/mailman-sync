<?php
namespace MailmanSync\Test;

use Illuminate\Support\Facades\Storage;
use MailmanSync\MailmanGatewayMock;

class MailmanGatewayMockTest extends PackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->resetMockCache();
    }

    protected function tearDown(): void
    {
        $this->resetMockCache();

        parent::tearDown();
    }

    public function testMockPersistsMembershipChanges(): void
    {
        $gateway = new MailmanGatewayMock();
        $gateway->subscribe('list.example.com', 'a@example.com', 'A');
        $gateway->subscribe('list.example.com', 'b@example.com');
        $gateway->change('list.example.com', 'a@example.com', 'c@example.com');
        $gateway->unsubscribe('list.example.com', 'b@example.com');

        $this->assertSame(['c@example.com'], $gateway->roster('list.example.com'));

        $this->resetMockCache();

        $reloaded = new MailmanGatewayMock();
        $this->assertSame(['c@example.com'], $reloaded->roster('list.example.com'));
    }

    private function resetMockCache(): void
    {
        (new \ReflectionClass(MailmanGatewayMock::class))->setStaticPropertyValue('mockCache', []);
    }
}
