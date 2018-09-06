<?php
/**
 * Created by IntelliJ IDEA.
 * User: gavin
 * Date: 9/3/2018
 * Time: 8:30 PM
 */
namespace MailmanSync\Facades;

use Illuminate\Support\Facades\Facade;

class MailmanGateway extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'MailmanSync';
    }
}
