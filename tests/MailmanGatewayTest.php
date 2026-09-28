<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 9:01 PM
 */
namespace MailmanSync\Test;

use GuzzleHttp\Psr7\Response;

class MailmanGatewayTest extends PackageTestCase
{
    const RESPONSE_FILE_INDEX = 0;
    const RESPONSE_STATUS_INDEX = 1;

    /**
     * @param array<int, array{0: string, 1: int}> $responses
     * @return array<int, Response>
     */
    private function makeResponseArray(array $responses): array
    {
        $headers = ['Content-Type' => 'text/html'];
        $r = [];
        foreach ($responses as $response) {
            $r[] = new Response(
                $response[self::RESPONSE_STATUS_INDEX],
                $headers,
                (string) file_get_contents(__DIR__.'/mock/'.$response[self::RESPONSE_FILE_INDEX])
            );
        }

        return $r;
    }

    /**
     * @param array<int, array{0: string, 1: int}> $responses
     */
    private function getGateway(array $responses): MockMailmanGateway
    {
        $this->configureMailman();

        return new MockMailmanGateway(
            $this->makeResponseArray($responses)
        );
    }

    public function testChangeSuccess(): void
    {
        $gateway = $this->getGateway(
            [
                ['change.404addressnotfound.html', 404],
                ['change.201addressadded.html', 201],
                ['change.204addressverified.html', 204],
                ['change.200memberships.html', 200],
                ['change.204success.html', 204],
            ]
        );
        $test = $gateway->change('list.example.com', 'test@test.com', 'test2@test.com');
        $this->assertTrue($test);
    }

    public function testChangeAddressAlreadyExists(): void
    {
        $gateway = $this->getGateway(
            [
                ['change.200findaddress.html', 200],
            ]
        );
        $this->expectException(\InvalidArgumentException::class);
        $gateway->change('list.example.com', 'test@test.com', 'test2@test.com');
    }

    public function testSubscribeSuccess(): void
    {
        $gateway = $this->getGateway(
            [
                ['subscribe.201success.html', 201],
            ]
        );
        $test = $gateway->subscribe('list.example.com', 'test@test.com');
        $this->assertTrue($test);
    }

    public function testSubscribeAlreadyMember(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $gateway = $this->getGateway(
            [
                ['subscribe.409alreadymember.html', 409],
            ]
        );
        $gateway->subscribe('list.example.com', 'test@test.com');
    }

    public function testSubscribeInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Address already exists');
        $gateway = $this->getGateway(
            [
                ['subscribe.400invalidemail.html', 400],
            ]
        );
        $gateway->subscribe('list', 'test@test');
    }

    public function testSubscribeBadPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Admin Credentials');
        $gateway = $this->getGateway(
            [
                ['subscribe.401badpassword.html', 401],
            ]
        );
        $gateway->subscribe('list', 'test@test');
    }

    public function testRosterSuccess(): void
    {
        $gateway = $this->getGateway(
            [
                ['roster.200success.html', 200],
            ]
        );
        $list = $gateway->roster('test');
        $this->assertSame([
            'member1@no.no',
            'member2@no.no',
            'member3@no.no',
            'member4@no.no',
            'member5@no.no',
        ], $list);
    }

    public function testRosterRejectsInvalidPayload(): void
    {
        $this->configureMailman();
        $gateway = new MockMailmanGateway([
            new Response(200, [], '{}'),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $gateway->roster('list.example.com');
    }

    public function testMissingUrlThrows(): void
    {
        config(['mailmansync.url' => null]);

        $this->expectException(\InvalidArgumentException::class);
        new MockMailmanGateway([]);
    }

    public function testMissingCredentialsThrow(): void
    {
        config([
            'mailmansync.url' => 'http://localhost/mailman/',
            'mailmansync.lists' => [],
        ]);
        $gateway = new MockMailmanGateway([
            new Response(201, [], '{}'),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mailman credentials are not configured for missing-list');
        $gateway->subscribe('missing-list', 'test@test.com');
    }
}
