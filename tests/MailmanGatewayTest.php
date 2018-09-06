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
    private function makeResponse($body, $statusCode = 200)
    {
        $headers = ['Content-Type' => 'text/html'];

        return new Response($statusCode, $headers, $body);
    }

    private function getGateway($mockfile, $statusCode = 200)
    {
        return new MockMailmanGateway(
            $this->makeResponse(file_get_contents(__DIR__.'/mock/'.$mockfile), $statusCode)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testChangeAlreadyMember()
    {
        $gateway = $this->getGateway('change.200alreadymember.html');
        $gateway->change('list', 'test@test.com', 'test2@test.com');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testChangeNotAMember()
    {
        $gateway = $this->getGateway('change.200notamember.html');
        $gateway->change('list', 'test@test.com', 'test2@test.com');
    }

    public function testChangeSuccess():void
    {
        $gateway = $this->getGateway('change.200success.html');
        $test = $gateway->change('list', 'test@test.com', 'test2@test.com');
        $this->assertTrue($test);
    }

    public function testSubscribeSuccess():void
    {
        $gateway = $this->getGateway('subscribe.200success.html');
        $test = $gateway->subscribe('list', 'test@test.com');
        $this->assertTrue($test);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSubscribeAlreadyMember():void
    {
        $gateway = $this->getGateway('subscribe.200alreadymember.html');
        $gateway->subscribe('list', 'test@test.com');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSubscribeInvalidEmail():void
    {
        $gateway = $this->getGateway('subscribe.200invalidemail.html');
        $gateway->subscribe('list', 'test@test');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSubscribeBadPassword():void
    {
        $gateway = $this->getGateway('subscribe.401badpassword.html', 401);
        $gateway->subscribe('list', 'test@test');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSubscribeBadUrl():void
    {
        $gateway = $this->getGateway('subscribe.404badurl.html', 404);
        $gateway->subscribe('list', 'test@test');
    }

    public function testRosterSuccess()
    {
        $gateway = $this->getGateway('roster.200success.html');
        $list = $gateway->roster('test');
        $this->assertInternalType('array', $list);
        $this->assertArraySubset(['test2@subnets.org'], $list);
    }
}
