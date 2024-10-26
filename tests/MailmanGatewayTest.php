<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 9:01 PM
 */
namespace MailmanSync\Test;

use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;

class MailmanGatewayTest extends TestCase
{
    const RESPONSE_FILE_INDEX = 0;
    const RESPONSE_STATUS_INDEX = 1;

    private function makeResponseArray($responses)
    {
        $headers = ['Content-Type' => 'text/html'];
        $r = [];
        foreach ($responses as $response) {
            $r[] = new Response($response[self::RESPONSE_STATUS_INDEX], $headers, file_get_contents(__DIR__.'/mock/'.$response[self::RESPONSE_FILE_INDEX]));
        }
        return $r;
    }

    private function getGateway($responses)
    {
        return new MockMailmanGateway(
            $this->makeResponseArray($responses)
        );
    }

    public function testChangeSuccess()
    {
        $gateway = $this->getGateway(
            [
                ['change.404addressnotfound.html', 404],
                ['change.201addressadded.html', 201],
                ['change.204addressverified.html', 204],
                ['change.200memberships.html', 200],
                ['change.204success.html', 204]
            ]
        );
        $test = $gateway->change('list.example.com', 'test@test.com', 'test2@test.com');
        $this->assertTrue($test);
    }

    public function testChangeAddressAlreadyExists()
    {
        $gateway = $this->getGateway(
            [
                ['change.200findaddress.html', 200]
            ]
        );
        $this->expectException(\InvalidArgumentException::class);
        $gateway->change('list.example.com', 'test@test.com', 'test2@test.com');
    }

    public function testSubscribeSuccess()
    {
        $gateway = $this->getGateway(
            [
                ['subscribe.201success.html', 201]
            ]
        );
        $test = $gateway->subscribe('list.example.com', 'test@test.com');
        $this->assertTrue($test);
    }

    public function testSubscribeAlreadyMember()
    {
        $this->expectException(\InvalidArgumentException::class);
        $gateway = $this->getGateway(
            [
                ['subscribe.409alreadymember.html', 409]
            ]
        );
        $gateway->subscribe('list.example.com', 'test@test.com');
    }

    public function testSubscribeInvalidEmail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $gateway = $this->getGateway(
            [
                ['subscribe.400invalidemail.html', 400]
            ]
        );
        $gateway->subscribe('list', 'test@test');
    }

    public function testSubscribeBadPassword()
    {
        $this->expectException(\InvalidArgumentException::class);
        $gateway = $this->getGateway(
            [
                ['subscribe.401badpassword.html', 401]
            ]
        );
        $gateway->subscribe('list', 'test@test');
    }

    public function testRosterSuccess()
    {
        $gateway = $this->getGateway(
            [
                ['roster.200success.html', 200]
            ]
        );
        $list = $gateway->roster('test');
        $this->assertInternalType('array', $list);
        $this->assertArraySubset(['member1@no.no'], $list);
    }
}
