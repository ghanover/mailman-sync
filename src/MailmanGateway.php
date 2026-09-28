<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 8:27 PM
 */
namespace MailmanSync;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class MailmanGateway implements MailmanGatewayInterface
{
    protected Client $client;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $options = array_merge($options, [
            'base_uri' => $this->baseUri(),
            'http_errors' => false,
        ]);
        $this->client = new Client($options);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function _authHeader(string $list): array
    {
        $lists = config('mailmansync.lists');
        $credentials = is_array($lists) ? ($lists[$list] ?? null) : null;

        if (! is_array($credentials) || ! isset($credentials['user'], $credentials['password'])) {
            throw new \InvalidArgumentException('Mailman credentials are not configured for '.$list);
        }

        return [(string) $credentials['user'], (string) $credentials['password']];
    }

    /**
     * @param array<string, mixed> $options
     * @throws GuzzleException
     */
    private function _execRequest(string $list, string $method, string $path, array $options = []): mixed
    {
        $response = $this->_execRawRequest($list, $method, $path, $options);
        $contents = $response->getBody()->getContents();

        if ($contents === '') {
            return null;
        }

        return json_decode($contents, false);
    }

    /**
     * @param array<string, mixed> $options
     * @throws GuzzleException
     */
    private function _execRawRequest(string $list, string $method, string $path, array $options = []): ResponseInterface
    {
        $options = array_merge($options, [RequestOptions::AUTH => $this->_authHeader($list)]);
        $response = $this->client->request($method, $path, $options);
        if ($response->getStatusCode() === 401) {
            throw new \InvalidArgumentException('Invalid Admin Credentials');
        }

        return $response;
    }

    /**
     * @throws GuzzleException
     */
    private function _getMembershipId(string $list, string $email): string
    {
        $obj = $this->_execRequest($list, 'GET', 'addresses/'.$email.'/memberships');

        if (! is_object($obj) || empty($obj->entries) || ! is_iterable($obj->entries)) {
            throw new \InvalidArgumentException($email.' is not a member or invalid response');
        }

        foreach ($obj->entries as $entry) {
            if (is_object($entry) && isset($entry->self_link) && ($entry->list_id ?? null) == $list) {
                // $entry->member_id is a bigint, which doesn't work in PHP, parse the string from self_link instead
                return basename((string) $entry->self_link);
            }
        }

        throw new \InvalidArgumentException($email.' is not a member or invalid response');
    }

    private function baseUri(): string
    {
        $baseUri = config('mailmansync.url');

        if (! is_string($baseUri) || $baseUri === '') {
            throw new \InvalidArgumentException('Mailman admin URL is not configured');
        }

        return str_ends_with($baseUri, '/') ? $baseUri : $baseUri.'/';
    }

    /**
     * @throws GuzzleException
     */
    public function subscribe(string $list, string $email, ?string $name = null): bool
    {
        $response = $this->_execRawRequest($list, 'POST', 'members', [RequestOptions::FORM_PARAMS => [
            'list_id' => $list,
            'subscriber' => $email,
            'display_name' => $name,
            'pre_verified' => 'true',
            'pre_confirmed' => 'true',
            'pre_approved' => 'true',
        ]]);

        switch ($response->getStatusCode()) {
            case 400:
                throw new \InvalidArgumentException('Address already exists');
            case 409:
                throw new \InvalidArgumentException('Address already a member');
        }

        return true;
    }

    /**
     * @throws GuzzleException
     */
    public function unsubscribe(string $list, string $email): bool
    {
        $membershipId = $this->_getMembershipId($list, $email);

        $this->_execRequest($list, 'DELETE', 'members/'.$membershipId);

        return true;
    }

    /**
     * @throws GuzzleException
     */
    public function change(string $list, string $emailFrom, string $emailTo): bool
    {
        // see if address already exists
        $response = $this->_execRawRequest($list, 'GET', 'users/'.$emailTo);
        if ($response->getStatusCode() !== 404) {
            throw new \InvalidArgumentException($emailTo.' already exists');
        }

        // add the address and verify it
        $this->_execRequest($list, 'POST', 'users/'.$emailFrom.'/addresses', [RequestOptions::FORM_PARAMS => ['email' => $emailTo]]);
        $this->_execRequest($list, 'POST', 'addresses/'.$emailTo.'/verify');

        // get the membershipURI needed for the update
        //http://lists.efnet.org:8001/3.1/addresses/gavin@subnets.org/memberships
        $membershipId = $this->_getMembershipId($list, $emailFrom);

        //update membership to new address
        $this->_execRequest($list, 'PATCH', 'members/'.$membershipId, [RequestOptions::FORM_PARAMS => [
            'address' => $emailTo,
        ]]);

        return true;
    }

    /**
     * @return array<int, string>
     * @throws GuzzleException
     */
    public function roster(string $list): array
    {
        // http://lists.efnet.org:8001/3.1/members/find?list_id=admins.voting.efnet.org&role=member
        $obj = $this->_execRequest($list, 'GET', 'members/find', [RequestOptions::QUERY => [
            'list_id' => $list,
            'role' => 'member',
        ]]);

        if (! is_object($obj) || ! isset($obj->entries) || ! is_iterable($obj->entries)) {
            throw new \InvalidArgumentException('Invalid roster response');
        }

        $members = [];
        foreach ($obj->entries as $entry) {
            if (is_object($entry) && isset($entry->email)) {
                $members[] = (string) $entry->email;
            }
        }

        return $members;
    }
}
