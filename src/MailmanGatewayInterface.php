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
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \InvalidArgumentException
     */
    public function subscribe(string $list, string $email, ?string $name = null): bool;

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \InvalidArgumentException
     */
    public function unsubscribe(string $list, string $email): bool;

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \InvalidArgumentException
     */
    public function change(string $list, string $emailFrom, string $emailTo): bool;

    /**
     * @return array<int, string>
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \InvalidArgumentException
     */
    public function roster(string $list): array;
}
