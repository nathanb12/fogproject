<?php
/**
 * Adds the location stuff to task page.
 *
 * PHP version 5
 *
 * @category AddLocationTasks
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @author   Lee Rowlett <nah@nah.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Adds the location stuff to task page.
 *
 * @category AddLocationTasks
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @author   Lee Rowlett <nah@nah.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class AddLocationTasks extends Hook
{
    /**
     * The name of this hook.
     *
     * @var string
     */
    public $name = 'AddLocationTasks';
    /**
     * The description of this hook.
     *
     * @var string
     */
    public $description = 'Add Location to Active Tasks';
    /**
     * The active flag.
     *
     * @var bool
     */
    public $active = true;
    /**
     * The node this hook works from.
     *
     * @var string
     */
    public $node = 'location';
    /**
     * The header to change within tasks.
     *
     * @param mixed $arguments The arguments to change.
     *
     * @return void
     */
    public function tasksActiveTableHeader($arguments)
    {
        global $node;
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if ($node != 'task') {
            return;
        }
        $arguments['headerData'][4] = _('Location');
    }
    /**
     * The header to change within active tasks.
     *
     * @param mixed $arguments The arguments to change.
     *
     * @return void
     */
    public function tasksActiveData($arguments)
    {
        global $node;
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if ($node != 'task') {
            return;
        }
        $arguments['templates'][4] = '${location}';
        $arguments['attributes'][4] = array('class'=>'r');
        foreach ((array)$arguments['data'] as $i => &$data) {
            $Locations = self::getClass('LocationAssociationManager')->find(
                array(
                    'hostID' => $data['host_id']
                )
            );
            if (count($Locations) < 1) {
                $arguments['data'][$i]['location'] = '';
            }
            foreach ((array)$Locations as &$Location) {
                $arguments['data'][$i]['location'] = $Location
                    ->getLocation()
                    ->get('name');
                unset($Location);
            }
            unset($data);
        }
    }
}
$AddLocationTasks = new AddLocationTasks();
$HookManager
    ->register(
        'HOST_DATA',
        array(
            $AddLocationTasks,
            'tasksActiveTableHeader'
        )
    );
$HookManager
    ->register(
        'HOST_DATA',
        array(
            $AddLocationTasks,
            'tasksActiveData'
        )
    );
