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

class MailmanGateway implements MailmanGatewayInterface
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct($options = [])
    {
        $baseUri = config('mailmansync.url');
        if (substr($baseUri, -1) !== '/') {
            $baseUri .= '/';
        }
        $defaultOptions = [
            'base_uri' => $baseUri,
            'http_errors' => false,
            'cookies' => true,
        ];
        $options = array_merge($options, $defaultOptions);
        $this->client = new Client($options);
    }

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
        $subscribee = $email;
        if ($name !== null) {
            $subscribee = '"'.$name.'" <'.$email.'>';
        }
        $path = 'admin/' . $list . '/members/add';
        $query = [
            'subscribe_or_invite' => 0,
            'send_welcome_msg_to_this_batch' => 0,
            'send_notifications_to_list_owner' => 0,
            'subscribees' => $subscribee,
            'adminpw' => config('mailmansync.lists.'.$list.'.password'),
        ];

        $response = $this->client->get($path .'?'. http_build_query($query));

        $this->checkResponse($response);
        $html = $response->getBody()->getContents();

        if (strstr($html, 'Error subscribing:')) {
            throw new \InvalidArgumentException('Error subscribing: '.$email);
        }

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
        $path = 'admin/' . $list . '/members/remove';
        $query = array(
            'send_unsub_ack_to_this_batch' => 0,
            'send_unsub_notifications_to_list_owner' => 0,
            'unsubscribees' => $email,
            'adminpw' => config('mailmansync.lists.'.$list.'.password')
        );
        $response = $this->client->get($path .'?'. http_build_query($query));
        $this->checkResponse($response);
        $html = $response->getBody()->getContents();

        if (strstr($html, 'Cannot unsubscribe non-members:')) {
            throw new \InvalidArgumentException('Cannot unsubscribe non-members: '.$email);
        }

        return true;
    }

    public function change($list, $emailFrom, $emailTo)
    {
        $path = 'admin/' . $list . '/members/change';
        $query = [
            'change_from' => $emailFrom,
            'change_to' => $emailTo,
            'notice_old' => 0,
            'notice_new' => 0,
            'adminpw' => config('mailmansync.lists.'.$list.'.password'),
        ];
        $response = $this->client->get($path.'?'.http_build_query($query));

        $this->checkResponse($response);
        $html = $response->getBody()->getContents();

        if (strstr($html, 'is not a member')) {
            throw new \InvalidArgumentException($emailFrom.' is not a member');
        }
        if (strstr($html, 'is already a list member')) {
            throw new \InvalidArgumentException($emailTo.' is already a list member');
        }
        return true;
    }

    /**
     * @param $list
     * @return array
     */
    public function roster($list)
    {
        $path = 'roster/'.$list;
        $query = [
            'adminpw' => config('mailmansync.lists.'.$list.'.password'),
        ];
        $this->client->get('admin/'.$list.'?'.http_build_query($query));
        $response = $this->client->get($path);

        $this->checkResponse($response);

        $html = $response->getBody()->getContents();

        $members = [];
        if (preg_match_all('~<a href="../options/'.$list.'/([^"]+)">([^<]+)</a>~', $html, $m)) {
            $members = str_replace(' at ', '@', $m[2]);
        }
        return $members;
    }

    /**
     * @param ResponseInterface|Response $response
     */
    protected function checkResponse(ResponseInterface $response)
    {
        if ($response->getStatusCode() === 401) {
            throw new \InvalidArgumentException('Invalid password');
        }
        if ($response->getStatusCode() === 404) {
            throw new \InvalidArgumentException('Invalid admin url');
        }
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Unknown error getting unsubscribe page');
        }
    }
}
