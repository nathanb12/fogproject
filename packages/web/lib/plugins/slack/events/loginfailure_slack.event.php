<?php
/**
 * The event to call to slack plugin on login
 * failure
 *
 * PHP version 5
 *
 * @category LoginFailure_Slack
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * The event to call to slack plugin on login
 * failure
 *
 * @category LoginFailure_Slack
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class LoginFailure_Slack extends Event
{
    /**
     * The name of this event
     *
     * @var string
     */
    public $name = 'LoginFailure_Slack';
    /**
     * The description of this event
     *
     * @var string
     */
    public $description = 'Triggers when an invalid login occurs';
    /**
     * The event is active
     *
     * @var bool
     */
    public $active = true;
    /**
     * Perform action
     *
     * @param string $event the event to enact
     * @param mixed  $data  the data
     *
     * @return void
     */
    public function onEvent($event, $data)
    {
        $Objects = self::getClass('SlackManager')
            ->find();
        foreach ((array)$Objects as &$Token) {
            if (!$Token->isValid()) {
                continue;
            }
            $args = array(
                'channel' => $Token->get('name'),
                'text' => sprintf(
                    '%s %s.',
                    $data['Failure'],
                    _('failed to login')
                ),
            );
            $Token->call('chat.postMessage', $args);
            unset($Token);
        }
    }
}
$EventManager->register(
    'LoginFail',
    new LoginFailure_Slack()
);
