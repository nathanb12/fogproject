<?php
/**
 * Pushes notification on login failure.
 *
 * PHP version 5
 *
 * @category LogonFailure_PushBullet
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Pushes notification on login failure.
 *
 * @category LogonFailure_PushBullet
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class LoginFailure_PushBullet extends PushbulletExtends
{
    /**
     * The name of the event.
     *
     * @var string
     */
    protected $name = 'LoginFailure_PushBullet';
    /**
     * The description of the event.
     *
     * @var string
     */
    protected $description = 'Triggers when a an invalid login occurs';
    /**
     * Active flag.
     *
     * @var bool
     */
    public $active = true;
    /**
     * Perform action when event met.
     *
     * @param string $event The event to perform from.
     * @param mixed  $data  The data to send.
     *
     * @return void
     */
    public function onEvent($event, $data)
    {
        self::$message = 'If you see repeatedly, please check your security';
        self::$shortdesc = sprintf('%s %s', $data['Failure'], _('failed to login'));
        parent::onEvent($event, $data);
    }
}
$EventManager
    ->register(
        'LoginFail',
        new LoginFailure_PushBullet()
    );
