<?php
/**
 * Legacy client use only just returns the users to cleanup
 *
 * PHP version 5
 *
 * @category UserCleaner
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Legacy client use only just returns the users to cleanup
 *
 * @category UserCleaner
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class UserCleaner extends FOGClient implements FOGClientSend
{
    /**
     * Sends the data to the client
     *
     * @return void
     */
    public function send()
    {
        $UserCleanups = self::getClass('UserCleanupManager')->find();
        $this->send = "#!start\n";
        foreach ($UserCleanups as &$User) {
            $this->send .= sprintf(
                "%s\n",
                base64_encode($User->get('name'))
            );
            unset($User);
        }
        $this->send .= "#!end";
    }
}
