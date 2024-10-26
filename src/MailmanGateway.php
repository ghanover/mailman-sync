<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 8:27 PM
 */
namespace MailmanSync;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class MailmanGateway implements MailmanGatewayInterface
{
    /**
     * @var Client
     */
    protected $client;

    private function _authHeader($list): array
    {
        return [config('mailmansync.lists')[$list]['user'], config('mailmansync.lists')[$list]['password']];
    }

    /**
     * @throws GuzzleException
     */
    private function _execRequest($list, $method, $path, $options = [])
    {
        $response = $this->_execRawRequest($list, $method, $path, $options);

        return json_decode($response->getBody()->getContents(), false);
    }

    /**
     * @param $list
     * @param $method
     * @param $path
     * @param array $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function _execRawRequest($list, $method, $path, array $options = []): ResponseInterface
    {
        $options = array_merge($options, [RequestOptions::AUTH => $this->_authHeader($list)]);
        $response = $this->client->request($method, $path, $options);
        if ($response->getStatusCode() == 401) {
            throw new \InvalidArgumentException('Invalid Admin Credentials');
        }
        return $response;
    }

    /**
     * @throws GuzzleException
     */
    private function _getMembershipId($list, $email): string
    {
        $path = 'addresses/'. $email . '/memberships';

        $obj = $this->_execRequest($list, 'GET', $path);

        if (empty($obj->entries)) {
            throw new \InvalidArgumentException($email . ' is not a member or invalid response');
        }
        foreach ($obj->entries as $entry) {
            if ($entry->list_id == $list) {
                // $entry->member_id is a bigint, which doesn't work in PHP, parse the string from self_link instead
                return basename($entry->self_link);
            }
        }
        throw new \InvalidArgumentException($email . ' is not a member or invalid response');
    }

    public function __construct($options = [])
    {
        $baseUri = config('mailmansync.url');
        if (substr($baseUri, -1) !== '/') {
            $baseUri .= '/';
        }
        $defaultOptions = [
            'base_uri' => $baseUri,
            'http_errors' => false,
        ];
        $options = array_merge($options, $defaultOptions);
        $this->client = new Client($options);
    }

    /**
     * @param $list
     * @param $email
     * @param null $name
     * @return bool
     * @throws GuzzleException
     */
    public function subscribe($list, $email, $name = null): bool
    {
        $response = $this->_execRawRequest($list, 'POST', 'members', [RequestOptions::FORM_PARAMS => [
            'list_id' => $list,
            'subscriber' => $email,
            'display_name' => $name,
            'pre_verified' => "true",
            'pre_confirmed' => "true",
            'pre_approved' => "true",
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
     * @param $list
     * @param $email
     * @return bool
     * @throws GuzzleException
     */
    public function unsubscribe($list, $email): bool
    {
        $membershipId = $this->_getMembershipId($list, $email);

        $this->_execRequest($list, 'DELETE', 'members/'.strval($membershipId));

        return true;
    }

    /**
     * @throws GuzzleException
     */
    public function change($list, $emailFrom, $emailTo): bool
    {
        // see if address already exists
        $response = $this->_execRawRequest($list, 'GET', 'users/'.$emailTo);
        if ($response->getStatusCode() != 404) {
            throw new \InvalidArgumentException($emailTo . ' already exists');
        }

        // add the address and verify it
        $this->_execRequest($list, 'POST', 'users/'.$emailFrom.'/addresses', [RequestOptions::FORM_PARAMS => ['email' => $emailTo]]);
        $this->_execRequest($list, 'POST', 'addresses/'.$emailTo.'/verify');

        // get the membershipURI needed for the update
        //http://lists.efnet.org:8001/3.1/addresses/gavin@subnets.org/memberships
        $membershipId = $this->_getMembershipId($list, $emailFrom);

        //update membership to new address
        $body = ['address' => $emailTo];
        $this->_execRequest($list, 'PATCH', 'members/'.strval($membershipId), [RequestOptions::FORM_PARAMS => $body]);

        return true;
    }

    /**
     * @param $list
     * @return array
     * @throws GuzzleException
     */
    public function roster($list): array
    {
        // http://lists.efnet.org:8001/3.1/members/find?list_id=admins.voting.efnet.org&role=member
        $path = 'members/find';
        $query = [
            'list_id' => $list,
            'role' => 'member',
        ];
        $obj = $this->_execRequest($list, 'GET', $path, [RequestOptions::QUERY => $query]);

        $members = [];
        foreach ($obj->entries as $entry) {
            $members[] = $entry->email;
        }
        return $members;
    }
}
