<?php
/**
 * FOGController, individual SQL getters/setters.
 *
 * PHP Version 5
 *
 * Gets and sets data for an individual object.
 * Generates the SQL Statements more specifically.
 *
 * @category FOGController
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * FOGController, individual SQL getters/setters.
 *
 * Gets and sets data for an individual object.
 * Generates the SQL Statements more specifically.
 *
 * @category FOGController
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
abstract class FOGController extends FOGBase
{
    /**
     * The data to set/get.
     *
     * @var array
     */
    protected $data = array();
    /**
     * If true, saves the object automatically.
     *
     * @var bool
     */
    protected $autoSave = false;
    /**
     * The database table to work from.
     *
     * @var string
     */
    protected $databaseTable = '';
    /**
     * The database fields to get.
     *
     * @var array
     */
    protected $databaseFields = array();
    /**
     * The required DB fields.
     *
     * @var array
     */
    protected $databaseFieldsRequired = array();
    /**
     * Additional elements unrelated to DB side directly for object.
     *
     * @var array
     */
    protected $additionalFields = array();
    /**
     * The flipped fields as we commonize names, flipping allows
     * translation to the main db column.
     *
     * @var array
     */
    protected $databaseFieldsFlipped = array();
    /**
     * Fields to ignore.
     *
     * @var array
     */
    protected $databaseFieldsToIgnore = array(
        'createdBy',
        'createdTime',
    );
    /**
     * Not used now, but can be used to setup alternate db aliases.
     *
     * @var array
     */
    protected $aliasedFields = array();
    /**
     * Class relationships, for inner joins of data.
     *
     * @var array
     */
    protected $databaseFieldClassRelationships = array();
    /**
     * The select query template to use.
     *
     * @var string
     */
    protected $loadQueryTemplate = 'SELECT %s FROM `%s` %s WHERE `%s`=%s %s';
    /**
     * The insert query template to use.
     *
     * @var string
     */
    protected $insertQueryTemplate = 'INSERT INTO `%s` (%s) VALUES (%s) %s %s';
    /**
     * The delete query template to use.
     *
     * @var string
     */
    protected $destroyQueryTemplate = 'DELETE FROM `%s` WHERE %s=%s%s';
    /**
     * Constructor to set variables.
     *
     * @param mixed $data the data to construct from if different
     *
     * @throws Exception
     *
     * @return self
     */
    public function __construct($data = '')
    {
        parent::__construct();
        $this->databaseTable = trim($this->databaseTable);
        $this->databaseFields = array_unique($this->databaseFields);
        $this->databaseFields = array_filter($this->databaseFields);
        try {
            if (!isset($this->databaseTable)) {
                throw new Exception(_('Table not defined for this class'));
            }
            if (!count($this->databaseFields)) {
                throw new Exception(_('Fields not defined for this class'));
            }
            $this->databaseFieldsFlipped = array_flip($this->databaseFields);
            if (is_numeric($data) && $data > 0) {
                $this->set('id', $data)->load();
            } elseif (is_array($data)) {
                $this->setQuery($data);
            }
        } catch (Exception $e) {
            $str = sprintf(
                '%s, %s: %s',
                _('Record not found'),
                _('Error'),
                $e->getMessage()
            );
            $this->error($str);
        }

        return $this;
    }
    /**
     * Closes out the object.
     *
     * @return bool
     */
    public function __destruct()
    {
        if ($this->autoSave) {
            $this->save();
        }

        return false;
    }
    /**
     * Default way to present object as a string.
     *
     * @return string
     */
    public function __toString()
    {
        $str = sprintf('%s ID: %s', get_class($this), $this->get('id'));
        if ($this->get('name')) {
            $str = sprintf('%s %s: %s', $str, _('Name'), $this->get('name'));
        }

        return $str;
    }
    /**
     * Test our needed fields.
     *
     * @param string $key the key to test
     *
     * @return bool
     */
    private function _testFields($key)
    {
        $this->key($key);
        $inFields = array_key_exists($key, $this->databaseFields);
        $inFieldsFlipped = array_key_exists($key, $this->databaseFieldsFlipped);
        $inAddFields = in_array($key, $this->additionalFields);
        if (!$inFields && !$inFieldsFlipped && !$inAddFields) {
            return false;
        }

        return true;
    }
    /**
     * Gets an item from the key sent, if no key all object data is returned.
     *
     * @param mixed $key the key to get
     *
     * @return object
     */
    public function get($key = '')
    {
        $key = $this->key($key);
        if (!$key) {
            return $this->data;
        }
        $test = $this->_testFields($key);
        if (!$test) {
            return false;
        }
        if (!$this->isLoaded($key)) {
            $this->loadItem($key);
        }
        if (is_object($this->data[$key])) {
            $msg = sprintf(
                '%s: %s, %s: %s',
                _('Returning value of key'),
                $key,
                _('Object'),
                $this->data[$key]->__toString()
            );
        } elseif (is_array($this->data[$key])) {
            $msg = sprintf(
                '%s: %s',
                _('Returning array within key'),
                $key
            );
        } else {
            $msg = sprintf(
                '%s: %s, %s: %s',
                _('Returning value of key'),
                $key,
                _('Value'),
                $this->data[$key]
            );
        }
        $this->info($msg);

        return $this->data[$key];
    }
    /**
     * Set value to key.
     *
     * @param string $key   the key to set to
     * @param mixed  $value the value to set
     *
     * @throws Exception
     *
     * @return object
     */
    public function set($key, $value)
    {
        try {
            $key = $this->key($key);
            if (!$key) {
                throw new Exception(_('No key being requested'));
            }
            $test = $this->_testFields($key);
            if (!$test) {
                throw new Exception(_('Invalid key being set'));
            }
            if (!$this->isLoaded($key)) {
                $this->loadItem($key);
            }
            if (is_numeric($value) && $value < ($key == 'id' ? 1 : -1)) {
                throw new Exception(_('Invalid numeric entry'));
            }
            if (is_object($value)) {
                $msg = sprintf(
                    '%s: %s, %s: %s',
                    _('Setting Key'),
                    $key,
                    _('Object'),
                    $value->__toString()
                );
            } elseif (is_array($value)) {
                $msg = sprintf(
                    '%s: %s %s',
                    _('Setting Key'),
                    $key,
                    _('Array')
                );
            } else {
                $msg = sprintf(
                    '%s: %s, %s: %s',
                    _('Setting Key'),
                    $key,
                    _('Value'),
                    $value
                );
            }
            $this->info($msg);
            $this->data[$key] = $value;
        } catch (Exception $e) {
            $str = sprintf(
                '%s: %s: %s, %s: %s',
                _('Set failed'),
                _('Key'),
                $key,
                _('Error'),
                $e->getMessage()
            );
            $this->debug($str);
        }

        return $this;
    }
    /**
     * Add value to key (array).
     *
     * @param string $key   the key to add to
     * @param mixed  $value the value to add
     *
     * @throws Exception
     *
     * @return object
     */
    public function add($key, $value)
    {
        try {
            $key = $this->key($key);
            if (!$key) {
                throw new Exception(_('No key being requested'));
            }
            $test = $this->_testFields($key);
            if (!$test) {
                throw new Exception(_('Invalid key being added'));
            }
            if (!$this->isLoaded($key)) {
                $this->loadItem($key);
            }
            if (is_object($value)) {
                $msg = sprintf(
                    '%s: %s, %s: %s',
                    _('Adding Key'),
                    $key,
                    _('Object'),
                    $value->__toString()
                );
            } elseif (is_array($value)) {
                $msg = sprintf(
                    '%s: %s %s',
                    _('Adding Key'),
                    $key,
                    _('Array')
                );
            } else {
                $msg = sprintf(
                    '%s: %s, %s: %s',
                    _('Adding Key'),
                    $key,
                    _('Value'),
                    $value
                );
            }
            $this->info($msg);
            $this->data[$key][] = $value;
        } catch (Exception $e) {
            $str = sprintf(
                '%s: %s: %s, %s: %s',
                _('Add failed'),
                _('Key'),
                $key,
                _('Error'),
                $e->getMessage()
            );
            $this->debug($str);
        }

        return $this;
    }
    /**
     * Remove value from key (array).
     *
     * @param string $key   the key to remove from
     * @param mixed  $value the value to remove
     *
     * @throws Exception
     *
     * @return object
     */
    public function remove($key, $value)
    {
        try {
            $key = $this->key($key);
            if (!$key) {
                throw new Exception(_('No key being requested'));
            }
            $test = $this->_testFields($key);
            if (!$test) {
                throw new Exception(_('Invalid key being removed'));
            }
            if (!$this->isLoaded($key)) {
                $this->loadItem($key);
            }
            if (!is_array($this->data[$key])) {
                $this->data[$key] = array($this->data[$key]);
            }
            $this->data[$key] = array_unique($this->data[$key]);
            $index = array_search($value, $this->data[$key]);
            if (is_object($this->data[$key][$index])) {
                $msg = sprintf(
                    '%s: %s, %s: %s',
                    _('Removing Key'),
                    $key,
                    _('Object'),
                    $this->data[$key][$index]->__toString()
                );
            } elseif (is_array($this->data[$key][$index])) {
                $msg = sprintf(
                    '%s: %s %s',
                    _('Removing Key'),
                    $key,
                    _('Array')
                );
            } else {
                $msg = sprintf(
                    '%s: %s, %s: %s',
                    _('Removing Key'),
                    $key,
                    _('Value'),
                    $this->data[$key][$index]
                );
            }
            $this->info($msg);
            unset($this->data[$key][$index]);
            $this->data[$key] = array_values(array_filter($this->data[$key]));
        } catch (Exception $e) {
            $str = sprintf(
                '%s: %s: %s, %s: %s',
                _('Remove failed'),
                _('Key'),
                $key,
                _('Error'),
                $e->getMessage()
            );
            $this->debug($str);
        }

        return $this;
    }
    /**
     * Stores data into the database.
     *
     * @return bool|object
     */
    public function save()
    {
        try {
            $insertKeys = array();
            $insertValKeys = $updateValKeys = array();
            $insertValues = $updateValues = array();
            $updateData = $fieldData = array();
            if (count($this->aliasedFields) > 0) {
                $this->arrayRemove($this->aliasedFields, $this->databaseFields);
            }
            foreach ($this->databaseFields as $key => &$column) {
                $key = $this->key($key);
                $column = trim($column);
                $eColumn = sprintf('`%s`', $column);
                $paramInsert = sprintf(':%s_insert', $column);
                $val = $this->get($key);
                switch ($key) {
                case 'createdBy':
                    if (!$val) {
                        if (isset($_SESSION['FOG_USERNAME'])) {
                            $val = trim($_SESSION['FOG_USERNAME']);
                        } else {
                            $val = 'fog';
                        }
                    }
                    break;
                case 'createdTime':
                    if (!($val && $this->validDate($val))) {
                        $val = $this->formatTime('now', 'Y-m-d H:i:s');
                    }
                    break;
                case 'id':
                    if (!(is_numeric($val) && $val > 0)) {
                        continue 2;
                    }
                    break;
                }
                if (is_null($val)) {
                    $val = '';
                }
                $insertKeys[] = $eColumn;
                $insertValKeys[] = $paramInsert;
                $insertValues[] = $val;
                $updateData[] = sprintf(
                    '%s=VALUES(%s)',
                    $eColumn,
                    $eColumn
                );
                unset(
                    $column,
                    $eColumn,
                    $key,
                    $val
                );
            }
            $query = sprintf(
                $this->insertQueryTemplate,
                $this->databaseTable,
                implode(',', (array) $insertKeys),
                implode(',', (array) $insertValKeys),
                'ON DUPLICATE KEY UPDATE',
                implode(',', (array) $updateData)
            );
            $queryArray = array_combine(
                $insertValKeys,
                $insertValues
            );
            $msg = sprintf(
                '%s %s %s',
                _('Saving data for'),
                get_class($this),
                _('object')
            );
            $this->info($msg);
            self::$DB->query($query, array(), $queryArray);
            if (!$this->get('id') || $this->get('id') < 1) {
                $this->set('id', self::$DB->insertId());
            }
            if (!$this instanceof History) {
                if ($this->get('name')) {
                    $msg = sprintf(
                        '%s %s: %s %s: %s %s.',
                        get_class($this),
                        _('ID'),
                        $this->get('id'),
                        _('NAME'),
                        $this->get('name'),
                        _('has been successfully updated')
                    );
                } else {
                    $msg = sprintf(
                        '%s %s: %s %s.',
                        get_class($this),
                        _('ID'),
                        $this->get('id'),
                        _('has been successfully updated')
                    );
                }
                $this->logHistory($msg);
            }
        } catch (Exception $e) {
            if (!$this instanceof History) {
                if ($this->get('name')) {
                    $msg = sprintf(
                        '%s %s: %s %s: %s %s. %s: %s',
                        get_class($this),
                        _('ID'),
                        $this->get('id'),
                        _('Name'),
                        $this->get('name'),
                        _('has failed to save'),
                        _('Error'),
                        $e->getMessage()
                    );
                } else {
                    $msg = sprintf(
                        '%s %s: %s %s. %s: %s',
                        get_class($this),
                        _('ID'),
                        $this->get('id'),
                        _('has failed to save'),
                        _('Error'),
                        $e->getMessage()
                    );
                }
                $this->logHistory($msg);
            }
            $msg = sprintf(
                '%s: %s: %s, %s: %s',
                _('Database save failed'),
                _('ID'),
                $this->get('id'),
                _('Error'),
                $e->getMessage()
            );
            $this->debug($msg);

            return false;
        }

        return $this;
    }
    /**
     * Loads the item from the database.
     *
     * @param string $key the key to load
     *
     * @throws Exception
     *
     * @return object
     */
    public function load($key = 'id')
    {
        try {
            if (!is_string($key)) {
                throw new Exception(_('Key field must be a string'));
            }
            if (!$key) {
                throw new Exception(_('No key being requested'));
            }
            $test = $this->_testFields($key);
            if (!$test) {
                throw new Exception(_('Invalid key being added'));
            }
            $val = $this->get($key);
            if (!$val) {
                throw new Exception(
                    sprintf(
                        '%s: %s',
                        _('Operation field not set'),
                        $key
                    )
                );
            }
            $join = $whereArrayAnd = array();
            $c = null;
            $this->buildQuery($join, $whereArrayAnd, $c);
            $join = array_filter((array) $join);
            $join = implode((array) $join);
            $fields = array();
            $this->getcolumns($fields);
            $key = $this->key($key);
            $paramKey = sprintf(':%s', $key);
            $query = sprintf(
                $this->loadQueryTemplate,
                implode(',', $fields),
                $this->databaseTable,
                $join,
                $this->databaseFields[$key],
                $paramKey,
                (
                    count($whereArrayAnd) ?
                    sprintf(
                        ' AND %s',
                        implode(' AND ', $whereArrayAnd)
                    ) :
                    ''
                )
            );
            $msg = sprintf(
                '%s %s',
                _('Loading data to field'),
                $key
            );
            $this->info($msg);
            $queryArray = array_combine(
                (array) $paramKey,
                (array) $val
            );
            $vals = array();
            $vals = self::$DB->query($query, array(), $queryArray)
                ->fetch('', 'fetch_assoc')
                ->get();
            $this->setQuery($vals);
        } catch (Exception $e) {
            $str = sprintf(
                '%s: %s: %s, %s: %s',
                _('Load failed'),
                _('Key'),
                $key,
                _('Error'),
                $e->getMessage()
            );
            $this->debug($str);
        }

        return $this;
    }
    /**
     * Gets the columns.
     *
     * @param array $fields The fields to get.
     *
     * @return void
     */
    public function getcolumns(&$fields)
    {
        /**
         * Lambda to get the fields to use.
         *
         * @param string $k      The key (for class relations).
         * @param string $column The column name.
         */
        $getFields = function (&$column, $k) use (&$fields, &$table) {
            $column = trim($column);
            $fields[] = sprintf('`%s`.*', $table);
            unset($column, $k);
        };
        $table = $this->databaseTable;
        array_walk($this->databaseFields, $getFields);
        foreach ((array)$this->databaseFieldClassRelationships as $class => &$arr) {
            self::getClass($class)->getcolumns($fields);
            unset($arr);
        }
        $fields = array_unique($fields);
    }
    /**
     * Removes the item from the database.
     *
     * @param string $key the key to remove
     *
     * @throws Exception
     *
     * @return object
     */
    public function destroy($key = 'id')
    {
        try {
            if (empty($key)) {
                $key = 'id';
            }
            $key = $this->key($key);
            if (!$key) {
                throw new Exception(_('No key being requested'));
            }
            $test = $this->_testFields($key);
            if (!$test) {
                throw new Exception(_('Invalid key being destroyed'));
            }
            $val = $this->get($key);
            if (!is_numeric($val) && !$val) {
                throw new Exception(
                    sprintf(
                        '%s: %s',
                        _('Operation field not set'),
                        $key
                    )
                );
            }
            $column = $this->databaseFields[$key];
            $eColumn = sprintf(
                '`%s`.`%s`',
                $this->databaseTable,
                $column
            );
            $paramKey = sprintf(':%s', $column);
            $query = sprintf(
                $this->destroyQueryTemplate,
                $this->databaseTable,
                $eColumn,
                $paramKey,
                ''
            );
            $queryArray = array_combine(
                (array) $paramKey,
                (array) $val
            );
            self::$DB->query($query, array(), $queryArray);
            if (!$this instanceof History) {
                if ($this->get('name')) {
                    $msg = sprintf(
                        '%s %s: %s %s: %s %s.',
                        get_class($this),
                        _('ID'),
                        $this->get('id'),
                        _('Name'),
                        $this->get('name'),
                        _('has been successfully destroyed')
                    );
                } else {
                    $msg = sprintf(
                        '%s %s: %s %s.',
                        get_class($this),
                        _('ID'),
                        $this->get('id'),
                        _('has been successfully destroyed')
                    );
                }
                $this->logHistory($msg);
            }
        } catch (Exception $e) {
            if (!$this instanceof History) {
                if ($this->get('name')) {
                    $msg = sprintf(
                        '%s %s: %s %s: %s %s. %s: %s',
                        get_class($this),
                        _('ID'),
                        $this->get('id'),
                        _('Name'),
                        $this->get('name'),
                        _('has failed to destroy'),
                        _('Error'),
                        $e->getMessage()
                    );
                } else {
                    $msg = sprintf(
                        '%s %s: %s %s. %s: %s',
                        get_class($this),
                        _('ID'),
                        $this->get('id'),
                        _('has failed to destroy'),
                        _('Error'),
                        $e->getMessage()
                    );
                }
                $this->logHistory($msg);
            }
            $msg = sprintf(
                '%s: %s: %s, %s: %s',
                _('Destroy failed'),
                _('ID'),
                $this->get('id'),
                _('Error'),
                $e->getMessage()
            );
            $this->debug($msg);

            return false;
        }

        return $this;
    }
    /**
     * Get's the relevant common key if available.
     *
     * @param string|array $key the key to get commonized
     *
     * @return mixed
     */
    protected function key(&$key)
    {
        $key = trim($key);
        if (array_key_exists($key, $this->databaseFieldsFlipped)) {
            $key = $this->databaseFieldsFlipped[$key];
        }

        return $key;
    }
    /**
     * Load the item field.
     *
     * @param string $key the key to load
     *
     * @throws Exception
     *
     * @return object
     */
    protected function loadItem($key)
    {
        $key = $this->key($key);
        if (!$key) {
            throw new Exception(_('No key being requested'));
        }
        $test = $this->_testFields($key);
        if (!$test) {
            return $this;
        }
        $methodCall = sprintf('load%s', ucfirst($key));
        if (method_exists($this, $methodCall)) {
            $this->{$methodCall}();
        }
        unset($methodCall);

        return $this;
    }
    /**
     * Adds or removes items from key field.
     *
     * Example:
     * Remove:
     * $this->addRemItem('hosts', $some_var_data, 'diff')
     * Add:
     * $this->addRemItem('hosts', $some_var_data, 'merge')
     *
     * @param string $key        the key to add/remove from
     * @param mixed  $array      the data to add/remove from
     * @param string $array_type the array type to use
     *
     * @throws Exception
     *
     * @return object
     */
    protected function addRemItem($key, $array, $array_type)
    {
        $key = $this->key($key);
        if (!$key) {
            throw new Exception(_('No key being requested'));
        }
        $test = $this->_testFields($key);
        if (!$test) {
            throw new Exception(_('Invalid key being requested'));
        }
        if (!in_array($array_type, array('merge', 'diff'))) {
            throw new Exception(
                _('Invalid type, merge to add, diff to remove')
            );
        }
        $array_type = sprintf(
            'array_%s',
            $array_type
        );
        if (!is_callable($array_type)) {
            throw new Exception(
                sprintf(
                    '%s %s: %s %s',
                    _('Array type'),
                    _('Type'),
                    $array_type,
                    _('is not callable')
                )
            );
        }
        if (count($array) < 1) {
            return $this;
        }
        $array = $array_type(
            (array) $this->get($key),
            (array) $array
        );

        return $this->set($key, $array);
    }
    /**
     * Tests if an object is valid.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isValid()
    {
        try {
            foreach ($this->databaseFieldsRequired as &$key) {
                $key = $this->key($key);
                $val = $this->get($key);
                if (!is_numeric($val) && !$val) {
                    throw new Exception(self::$foglang['RequiredDB']);
                }
                unset($key);
            }
            if ($this->get('id') < 1) {
                throw new Exception(_('Invalid ID passed'));
            }
            if (array_key_exists('name', $this->databaseFields)) {
                $val = trim($this->get('name'));
            }
        } catch (Exception $e) {
            $str = sprintf(
                '%s: %s: %s',
                _('Failed'),
                _('Error'),
                $e->getMessage()
            );
            $this->debug($str);

            return false;
        }

        return true;
    }
    /**
     * Builds query strings as needed.
     *
     * @param array  $join          The join array.
     * @param array  $whereArrayAnd The where array.
     * @param array  $c             The join object.
     * @param bool   $not           Whether to compare using not operator.
     * @param string $compare       The comparator to use.
     *
     * @return array
     */
    public function buildQuery(
        &$join,
        &$whereArrayAnd,
        &$c,
        $not = false,
        $compare = '='
    ) {
        /**
         * Lambda function to build the where array additionals.
         *
         * @param string $field the field to work from
         * @param mixed  $value the value of the field
         */
        $whereInfo = function (
            &$value,
            &$field
        ) use (
            &$whereArrayAnd,
            &$c,
            $not,
            $compare
        ) {
            if (is_array($value)) {
                $whereArrayAnd[] = sprintf(
                    "`%s`.`%s` IN ('%s')",
                    $c->databaseTable,
                    $field,
                    implode("','", $value)
                );
            } else {
                if (preg_match('#%#', $value)) {
                    $compare = 'LIKE';
                }
                $whereArrayAnd[] = sprintf(
                    "`%s`.`%s` %s '%s'",
                    $c->databaseTable,
                    $c->databaseFields[$field],
                    $compare,
                    $value
                );
            }
            unset($value, $field);
        };
        /**
         * Lambda function to build the join of a query.
         *
         * @param string $class  the class to work from
         * @param mixed  $fields the fields to work off
         */
        $joinInfo = function (
            &$fields,
            &$class
        ) use (
            &$join,
            &$whereArrayAnd,
            &$c,
            $whereInfo,
            $not,
            $compare
        ) {
            $className = strtolower($class);
            $c = self::getClass($class);
            if (!array_key_exists($className, $join)) {
                $join[$className] = sprintf(
                    ' LEFT OUTER JOIN `%s` ON `%s`.`%s`=`%s`.`%s` ',
                    $c->databaseTable,
                    $c->databaseTable,
                    $c->databaseFields[$fields[0]],
                    $this->databaseTable,
                    $this->databaseFields[$fields[1]]
                );
            }
            if ($fields[3]) {
                array_walk($fields[3], $whereInfo);
            }
            $c->buildQuery($join, $whereArrayAnd, $c, $not, $compare);
            unset($class, $fields, $c);
        };
        $className = strtolower(get_class($this));
        if (!array_key_exists($className, $join)) {
            $join[$className] = false;
        }
        array_walk($this->databaseFieldClassRelationships, $joinInfo);

        return array(implode((array) $join), $whereArrayAnd);
    }
    /**
     * Set's the queries data into the object as/where needed.
     *
     * @param array $queryData The data to work from.
     *
     * @return object
     */
    public function setQuery(&$queryData)
    {
        $classData = array_intersect_key(
            (array) $queryData,
            (array) $this->databaseFieldsFlipped
        );
        if (count($classData) < 1) {
            $classData = array_intersect_key(
                (array) $queryData,
                (array)$this->databaseFields
            );
        } else {
            foreach ($this->databaseFieldsFlipped as $db_key => &$obj_key) {
                $this->arrayChangeKey($classData, $db_key, $obj_key);
                unset($db_key, $obj_key);
            }
        }
        $this->data = array_merge(
            (array) $this->data,
            (array) $classData
        );
        foreach ($this->databaseFieldClassRelationships as $class => &$fields) {
            $class = self::getClass($class);
            $this->set(
                $fields[2],
                $class->setQuery($queryData)
            );
            unset($class, $fields);
        }
        unset($queryData);

        return $this;
    }
    /**
     * Get an objects manager class.
     *
     * @return object
     */
    public function getManager()
    {
        $class = sprintf('%sManager', get_class($this));

        return new $class();
    }
    /**
     * Set's values for associative fields.
     *
     * @param string $assocItem    the assoc item to work from/with
     * @param string $alterItem    the alternate item to work with
     * @param bool   $implicitCall call class implicitely instead of appending
     *                             with association
     *
     * @return object
     */
    public function assocSetter($assocItem, $alterItem = '', $implicitCall = false)
    {
        if (empty($alterItem)) {
            $assoc = strtolower($assocItem);
        } else {
            $assoc = $alterItem;
        }
        $plural = sprintf(
            '%ss',
            $assoc
        );
        if (!$this->isLoaded($plural)) {
            return $this;
        }
        if ($implicitCall) {
            $classCall = $assocItem;
        } else {
            $classCall = sprintf(
                '%sAssociation',
                $assocItem
            );
        }
        $objType = get_class($this);
        $objtype = strtolower($objType);
        $objstr = sprintf('%sID', $objtype);
        $assocstr = sprintf('%sID', $assoc);
        if (count($this->get($plural))) {
            if ($assocItem === 'SnapinGroup') {
                $tmpAssoc = $assocItem;
                $assocItem = 'StorageGroup';
            }
            $DBIDs = self::getSubObjectIDs(
                $assocItem,
                array('id' => $this->get($plural))
            );
            if ($tmpAssoc) {
                $assocItem = $tmpAssoc;
                unset($tmpAssoc);
            }
        } else {
            $RemIDs = self::getSubObjectIDs($assoc);
        }
        if (!isset($RemIDs)) {
            $RemIDs = self::getSubObjectIDs(
                $classCall,
                array(
                    $assocstr => $DBIDs,
                ),
                $assocstr,
                true
            );
        }
        $RemIDs = array_filter($RemIDs);
        if (count($RemIDs) > 0) {
            self::getClass(sprintf('%sManager', $classCall))
                ->destroy(
                    array(
                        $objstr => $this->get('id'),
                        $assocstr => $RemIDs,
                    )
                );
            unset($RemIDs);
        }
        $insert_fields = array(
            $objstr,
            $assocstr,
        );
        if ($assocstr == 'moduleID') {
            array_push($insert_fields, 'state');
        }
        $insert_values = array();
        foreach ((array) $this->get($plural) as &$id) {
            $insert_val = array(
                $this->get('id'),
                $id,
            );
            if ($assocstr == 'moduleID') {
                array_push($insert_val, 1);
            }
            $insert_values[] = $insert_val;
            unset($insert_val, $id);
        }
        unset($DBIDs);
        if (count($insert_values) > 0) {
            self::getClass(sprintf('%sManager', $classCall))
                ->insertBatch(
                    $insert_fields,
                    $insert_values
                );
        }
        unset($insert_values, $insert_fields);

        return $this;
    }
}
