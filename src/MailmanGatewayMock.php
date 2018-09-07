<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 8:27 PM
 */
namespace MailmanSync;

use Illuminate\Support\Facades\Storage;

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
        $this->getCache($list);
        self::$mockCache[$list][] = $email;
        $this->writeCache($list);
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
        self::$mockCache[$list] = array_filter(
            $this->getCache($list),
            function ($v) use ($email) {return $v !== $email;}
        );
        $this->writeCache($list);
        return true;
    }

    public function change($list, $emailFrom, $emailTo)
    {

        self::$mockCache[$list] = array_map(
            function ($v) use ($emailFrom, $emailTo) {return $v === $emailFrom ? $emailTo : $v;},
            $this->getCache($list)
        );
        $this->writeCache($list);
        return true;
    }

    /**
     * @param $list
     * @return array
     */
    public function roster($list)
    {
        return $this->getCache($list);
    }

    private function getCache($list)
    {
        if (empty(self::$mockCache[$list]) && Storage::disk('local')->exists('mockCache.'.$list.'.txt')) {
            self::$mockCache[$list] = array_filter(
                explode(PHP_EOL, Storage::disk('local')->get('mockCache.'.$list.'.txt'))
            );
        } else {
            self::$mockCache[$list] = [];
        }
        return self::$mockCache[$list];
    }

    private function writeCache($list)
    {
        Storage::disk('local')->put('mockCache.'.$list.'.txt', implode(PHP_EOL, self::$mockCache[$list]));
    }
}
