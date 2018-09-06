<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/6/2018
 * Time: 6:52 AM
 */
namespace MailmanSync;

interface MailmanGatewayInterface
{
    /**
     * @param $list
     * @param $email
     * @param null $name
     * @return bool
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function subscribe($list, $email, $name = null);

    /**
     * @param $list
     * @param $email
     * @return bool
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function unsubscribe($list, $email);

    public function change($list, $emailFrom, $emailTo);

    /**
     * @param $list
     * @return array
     */
    public function roster($list);
}
