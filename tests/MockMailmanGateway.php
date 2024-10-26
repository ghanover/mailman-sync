<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 9:07 PM
 */
namespace MailmanSync\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MailmanSync\MailmanGateway;

class MockMailmanGateway extends MailmanGateway
{
    private static $server;

    /**
     * @param Response[] $responses
     */
    public function __construct(array $responses)
    {
        $mock = new MockHandler(
            $responses
        );

        $handler = HandlerStack::create($mock);
        parent::__construct(['handler' => $handler]);
    }
}
