<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 8:27 PM
 */
namespace MailmanSync;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class MailmanGatewayMock implements MailmanGatewayInterface
{
    /**
     * @var array
     */
    private static $mockCache = [];

    /**
     * @param $list
     * @param $email
     * @param null $name
     * @return bool
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function subscribe($list, $email, $name = null)
    {
        self::$mockCache[$list][] = $email;
        return true;
    }

    /**
     * @param $list
     * @param $email
     * @return bool
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function unsubscribe($list, $email)
    {
        self::$mockCache = array_filter(self::$mockCache, function ($v) use ($email) {return $v !== $email;});
        return true;
    }

    public function change($list, $emailFrom, $emailTo)
    {
        self::$mockCache = array_map(
            function ($v) use ($emailFrom, $emailTo) {return $v === $emailFrom ? $emailTo : $v;}, self::$mockCache
        );
        return true;
    }

    /**
     * @param $list
     * @return array
     */
    public function roster($list)
    {
        return self::$mockCache;
    }
}
