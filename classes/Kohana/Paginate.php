<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Paginate
 *
 * @package        Paginate
 * @author        Micheal Morgan <micheal@morgan.ly>
 * @copyright    (c) 2011-2012 Micheal Morgan
 * @license        MIT
 */
abstract class Kohana_Paginate
{
    /**
     * Sort Ascending
     *
     * @var        string
     */
    const SORT_ASC = 'ASC';

    /**
     * Sort Descending
     *
     * @var        string
     */
    const SORT_DESC = 'DESC';

    /**
     * Factory pattern
     *
     * @static
     * @access    public
     * @param    mixed $object string|object
     * @param   Kohana_Request $request object|null
     * @param   string|false $config
     * @param    mixed $driver NULL|string
     * @return    Paginate
     * @throws    Kohana_Exception
     */
    public static function factory($object, Kohana_Request $request = NULL, $config = 'paginate.default', $driver = NULL)
    {
        if ($driver === NULL) {
            if ($object instanceof ORM) {
                $driver = 'ORM';
            } else if ($object instanceof ORM_REST) {
                $driver = 'ORM_REST';
            } else if ($object instanceof Database_Query) {
                $driver = 'Database';
            } else if ($object instanceof Dispatch_Request) {
                $driver = 'Dispatch';
            }
        }
        if (!$driver)
            throw new Kohana_Exception('Paginate either expecting driver name or a driver
				supported object instance.');

        $class = 'Paginate_' . $driver;

        return new $class($object, $config, $request);
    }

    /**
     * Object to perform paginate operations on
     *
     * @access    protected
     * @var        object
     */
    protected $_object;

    /**
     * Object to retrieve the url from
     *
     * @access    protected
     * @var       Kohana_Request
     */
    protected $_request;

    /**
     * Config set
     *
     * @access    protected
     * @var       string|Kohana_Config
     */
    protected $_config;

    /**
     * Columns
     *
     * @access    protected
     * @var        array
     */
    protected $_columns = array();

    /**
     * Search columns
     *
     * @access    protected
     * @var        array
     */
    protected $_search_columns = array();

    /**
     * Search query
     *
     * @access    protected
     * @var        mixed    NULL|string
     */
    protected $_search_query;

    /**
     * Count for request
     *
     * @access    protected
     * @var        int
     */
    protected $_count = 0;

    /**
     * Total count
     *
     * @access    protected
     * @var        int
     */
    protected $_count_total = 0;

    /**
     * Total search count
     *
     * @access    protected
     * @var        int
     */
    protected $_count_search_total = 0;

    /**
     * Result
     *
     * @access    protected
     * @var        NULL
     */
    protected $_result;

    /**
     * Initialize
     *
     * @param    object $object
     * @param   mixed $config
     * @param    Kohana_Request $request
     */
    public function __construct($object, $config, Kohana_Request $request = NULL)
    {
        $this->_object = $object;

        if ($request == null) {
            $request = Request::$current;
        }

        $this->_request = $request;

        if (is_array($config)) {
            $this->_config = $config;
        } else if (is_a($config, 'Kohana_Config_Group')) {
            $this->_config = $config->as_array();
        } else if ($config != false) {
            $this->_config = Kohana::$config->load($config);
        }
    }

    /**
     * Retrieve the currently loaded page.
     *
     * @return mixed|null
     */
    public function current_page()
    {
        if (is_array($this->_config)) {
            switch ($this->_config['current_page']['source']) {
                case 'query_string':
                    return Arr::get($_GET, $this->_config['current_page']['key'], 1);
                    break;
                case 'route_param':
                    return $this->_request->param($this->_config['current_page']['key'], 1);
                    break;
            }
        }
        return null;
    }

    /**
     * Apply limit
     *
     * @access    protected
     * @param    int
     * @param    int
     * @return    void
     */
    abstract protected function _limit($start, $length);

    /**
     * Apply sort
     *
     * @access    protected
     * @param    string
     * @param    string
     * @return    void
     */
    abstract protected function _sort($column, $direction);

    /**
     * Apply search query
     *
     * @access    protected
     * @param    string
     * @return    void
     */
    abstract protected function _search($query);

    /**
     * Count
     *
     * @access    protected
     * @return    int
     */
    abstract protected function _count();

    /**
     * Count total
     *
     * @access    protected
     * @return    int
     */
    abstract protected function _count_total();

    /**
     * Count search total
     *
     * @access    protected
     * @return    int
     */
    abstract protected function _count_search_total();

    /**
     * Execute result on object
     *
     * @access    protected
     * @return    mixed
     */
    abstract protected function _execute();

    /**
     * Has the object already been limited by hand?
     * @var bool
     */
    protected $_limited = false;

    /**
     * Set limit
     *
     * @access    public
     * @param    int
     * @param    int
     * @return    $this
     */
    public function limit($start, $length)
    {
        $this->_limit($start, $length);
        $this->_limited = true;

        return $this;
    }

    /**
     * Automatically limit the results based on the config.
     * @return $this
     */
    protected function _auto_limit()
    {
        return $this->limit(
            ($this->current_page() - 1) * $this->_config['items_per_page'],
            $this->_config['items_per_page']
        );
    }

    /**
     * Set sort order
     *
     * @access    public
     * @param    string
     * @param    mixed    SORT_ASC|SORT_DESC
     * @return    $this
     * @throws    Kohana_Exception
     */
    public function sort($column, $direction = self::SORT_ASC)
    {
        if (!in_array($direction, array(self::SORT_ASC, self::SORT_DESC)))
            throw new Kohana_Exception('Invalid sort order of `' . $direction . '`.');

        $this->_sort($column, $direction);

        return $this;
    }

    /**
     * Get or set search query
     *
     * @access    public
     * @param    mixed    NULL|string
     * @return    mixed    $this|string
     */
    public function search($query = NULL)
    {
        if ($query === NULL)
            return $this->_search_query;

        $this->_search_query = $query;

        return $this;
    }

    /**
     * Get count based on post operations
     *
     * @access    public
     * @return    int
     */
    public function count()
    {
        return (int)$this->_count;
    }

    /**
     * Get total count prior to operations
     *
     * @access    public
     * @return    int
     */
    public function count_total()
    {
        return (int)$this->_count_total;
    }

    /**
     * Get search count prior to pagination
     *
     * @access    public
     * @return    int
     */
    public function count_search_total()
    {
        return (int)$this->_count_search_total;
    }

    /**
     * Set or get columns
     *
     * @access    public
     * @param    mixed    NULL|string
     * @return    mixed    $this|string
     */
    public function columns(array $columns = NULL)
    {
        if ($columns === NULL)
            return $this->_columns;

        $this->_columns = $columns;

        return $this;
    }

    /**
     * Set or get search columns
     *
     * @access    public
     * @param    mixed    NULL|string
     * @return    mixed    $this|string
     */
    public function search_columns(array $columns = NULL)
    {
        if ($columns === NULL)
            return $this->_search_columns;

        $this->_search_columns = $columns;

        return $this;
    }

    /**
     * Get result
     *
     * @access    public
     * @return    mixed
     */
    public function result()
    {
        return $this->_result;
    }

    /**
     * Execute
     *
     * @access    public
     * @param    mixed    NULL|Request
     * @return    $this
     */
    public function execute()
    {
        if ($this->_search_query !== NULL) {
            $this->_search($this->_search_query);
        }

        if ($this->_limited == false) {
            $this->_auto_limit();
        }

        $this->_result = $this->_execute();

        $this->_count = $this->_count();

        $this->_count_total = $this->_count_total();

        if ($this->_search_query !== NULL) {
            $this->_count_search_total = $this->_count_search_total();
        }

        return $this;
    }

    /**
     * Provide a render object for the
     * @return Paginate_Render
     */
    public function render()
    {
        return new Paginate_Render($this, $this->_config, $this->_request);
    }
}
