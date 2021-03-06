<?php
/**
 * Handles the display of schema and schema updating in general.
 *
 * PHP version 5
 *
 * @category SchemaUpdaterPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Handles the display of schema and schema updating in general.
 *
 * @category SchemaUpdaterPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class SchemaUpdaterPage extends FOGPage
{
    /**
     * The relavent calling node url
     *
     * @var string
     */
    public $node = 'schema';
    /**
     * The page initializer
     *
     * @param string $name The name to work from.
     *
     * @return void
     */
    public function __construct($name = '')
    {
        parent::__construct($name);
        $schema = new Schema(1);
        if ($schema->get('version') >= FOG_SCHEMA) {
            self::redirect('index.php');
        }
        $this->name = 'Database Schema Installer / Updater';
        $this->menu = array();
        $this->subMenu = array();
    }
    /**
     * The first page displayed if on GUI
     *
     * @return void
     */
    public function index()
    {
        $this->title = _('Database Schema Installer / Updater');
        $vals = array(
            sprintf(
                '%s, %s %s. %s, %s %s %s. %s, %s %s.',
                _('Your FOG database schema is not up to date'),
                _('either because you have updated'),
                _('or this is a new FOG installation'),
                _('If this is an upgrade'),
                _('there will be a database backup stored on your'),
                _('FOG server defaulting under the folder'),
                '/home/fogDBbackups',
                _('Should anything go wrong'),
                _('this backup will enable you to return to the'),
                _('previous install if needed')
            ),
            sprintf(
                '%s %s?',
                _('Are you sure you wish to'),
                _('install or update the FOG database')
            ),
            $this->formAction,
            _('Install/Upgrade Now'),
            sprintf(
                '%s %s %s %s %s (%s->%s->%s), %s %s.',
                _('If you would like to backup your'),
                _('FOG database you can do so using'),
                _('MySQL Administrator or by running'),
                _('the following command in a terminal'),
                _('window'),
                _('Applications'),
                _('System Tools'),
                _('Terminal'),
                _('this will save the backup in your home'),
                _('directory')
            ),
            "\n",
        );
        vprintf(
            '<div id="dbRunning" class="hidden">'
            . '<p>%s</p><p>%s</p><br/>'
            . '<form method="post" action="%s">'
            . '<p class="c"><input type="hidden" '
            . 'name="fogverified"/><input type="submit" '
            . 'name="confirm" value="%s"/></p></form>'
            . '<p>%s</p><div id="sidenotes">'
            . '<pre><code>cd%smysqldump --allow-keywords '
            . '-x -v fog > fogbackup.sql</code></pre></div>'
            . '<br/></div>',
            $vals
        );
        echo '<div id="dbNotRunning" class="hidden">';
        printf(
            '%s. %s. %s. %s %s%s%s. %s. %s, %s, %s.',
            _('Your database connection appears to be invalid'),
            _('FOG is unable to communicate with the database'),
            _('There are many reasons why this could be the case'),
            _('Please check your credentials in'),
            dirname(dirname(__FILE__)),
            DIRECTORY_SEPARATOR,
            'fog/config.class.php',
            _('Also confirm that the database is indeed running'),
            _('If credentials are correct'),
            _('and if the Database service is running'),
            _('check to ensure your filesystem has enough space')
        );
        echo '</div>';
    }
    /**
     * When a form is submitted, this function handles it.
     *
     * @return void
     */
    public function indexPost()
    {
        if (!isset($_POST['fogverified'])) {
            return;
        }
        if (!isset($_POST['confirm'])) {
            return;
        }
        include sprintf(
            '%s%scommons%sschema.php',
            BASEPATH,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
        $errors = array();
        try {
            if (!self::$DB->getLink()) {
                throw new Exception(_('No connection available'));
            }
            if (count($this->schema) <= self::$mySchema) {
                throw new Exception(_('Update not required!'));
            }
            $items = array_slice(
                $this->schema,
                self::$mySchema,
                null,
                true
            );
            foreach ((array)$items as $version => &$updates) {
                foreach ((array)$updates as &$update) {
                    if (is_callable($update)) {
                        $result = $update();
                        if (is_string($result)) {
                            $errors[] = sprintf(
                                '<p><b>%s %s:</b>'
                                . ' %s</p><p><b>%s %s:</b>'
                                . ' <pre>%s</pre></p>'
                                . '<p><b>%s:</b>'
                                . ' <pre>%s</pre></p>',
                                _('Update'),
                                _('ID'),
                                $version,
                                _('Function'),
                                _('Error'),
                                $result,
                                _('Function'),
                                print_r($update, 1)
                            );
                        }
                    } elseif (false === self::$DB->query($update)) {
                        $errors[] = sprintf(
                            '<p><b>%s %s:</b>'
                            . ' %s</p><p><b>%s %s:</b>'
                            . ' <pre>%s</pre></p>'
                            . '<p><b>%s:</b>'
                            . ' <pre>%s</pre></p>',
                            _('Update'),
                            _('ID'),
                            $version,
                            _('Database'),
                            _('Error'),
                            self::$DB->sqlerror(),
                            _('Database SQL'),
                            $update
                        );
                    }
                }
            }
            $newSchema = self::getClass('Schema', 1)
                ->set('version', ++$version);
            if (!$newSchema->save()) {
                $fatalerrmsg = '';
                $fatalerrmsg = sprintf(
                    '<p>%s</p>',
                    _('Install / Update Failed!')
                );
                if (count($errors)) {
                    $fatalerrmsg .= sprintf(
                        '<h2>%s</h2>%s',
                        _('The following errors occurred'),
                        implode('<hr/>', $errors)
                    );
                }
                throw new Exception($fatalerrmsg);
            }
            self::$DB->currentDb(self::$DB->returnThis());
            $text = sprintf(
                '<p>%s</p><p>%s <a href="index.php">%s</a> %s</p>',
                _('Install / Update Successful!'),
                _('Click'),
                _('here'),
                _('to login')
            );
            if (count($errors)) {
                $text = sprintf(
                    '<h2>%s</h2>%s',
                    _('The following errors occured'),
                    implode('<hr/>', $errors)
                );
            }
            if (self::$ajax) {
                echo json_encode($text);
                exit;
            }
            echo $text;
        } catch (Exception $e) {
            printf('<p>%s</p>', $e->getMessage());
            exit(1);
        }
    }
}
