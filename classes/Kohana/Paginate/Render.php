<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Paginate renderer
 * 
 * @package		Paginate
 * @author		Maxim Kerstens <maxim.kerstens@gmail.com>
 * @copyright	(c) 2014 Maxim Kerstens
 * @license		MIT
 */
class Kohana_Paginate_Render
{
    protected $paginator;
    protected $config;
    protected $request;

	public function __construct(Kohana_Paginate $object, Array $config, Kohana_Request $request)
    {
        $this->paginator = $object;
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * get the total amount of pages based on results
     * @return integer
     */
    public function total_pages()
    {
        return ceil($this->paginator->count_total() / $this->config['items_per_page']);
    }

    /**
     * get the current page's number
     * @return mixed|null
     */
    public function current_page()
    {
        return $this->paginator->current_page();
    }

    /**
     * Prepare an array with info for rendering page elements
     * @return string
     */
    public function pages()
    {
        if(($this->total_pages() == 0 || $this->total_pages() == null) && $this->config['auto_hide'] == TRUE)
            return '';

        $pages = array();

        $total_pages = $this->total_pages();

        for($i=0;$i<$total_pages;$i++)
        {
            $page = $i + 1;
            $pages[] = array(
                'content' => $page,
                'active' => $page == $this->current_page(),
                'url' => $this->parse_url($page)
            );
        }

        if($this->config['prev_next_links'] == true)
        {
            // Add the next link if needed
            if($this->current_page() != $this->total_pages())
            {
                $pages[] = array(
                    'content' => '&raquo;',
                    'active' => false,
                    'url' => $this->parse_url($this->current_page() + 1)
                );
            }

            // Add the previous link if needed
            if($this->current_page() != 1)
            {
                array_unshift($pages, array(
                    'content' => '&laquo;',
                    'active' => false,
                    'url' => $this->parse_url($this->current_page() - 1)
                ));
            }
        }
    }

    /**
     * Parse the url for the specified page number
     * @param $page
     * @return string
     */
    public function parse_url($page)
    {
        // Clean the page number
        $page = max(1, (int) $page);

        $key = $this->config['current_page']['key'];

        // No page number in URLs to first page
        if ($page === 1 AND ! $this->config['first_page_in_url'])
        {
            $page = NULL;
        }

        switch($this->config['current_page']['source'])
        {
            case 'query_string':
                return $this->request->url()
                .URL::query(array($this->config['current_page']['key'] => $page));
                break;
            case 'route_param':
                return URL::site($this->request
                    ->uri(array(
                        $key => $page
                    )))
                .URL::query();
                break;
        }
    }

    /**
     * Load a view with the result data
     * @param $template
     * @return View
     */
    public function results($template)
    {
        return View::factory($template, array('results' => $this->paginator->result()));
    }

    /**
     * Load a view and assign pagination data.
     *
     * @param null $template
     * @return View
     */
    public function pagination($template=null)
    {
        if($this->config['auto_hide'] == TRUE && $this->paginator->count_total() <= $this->config['items_per_page'])
            return '';

        if($template == null)
        {
            $template = $this->config['view'];
        }

        $data = array(
            'pages' => $this->pages(),
            'renderer' => $this
        );
        if(isset($this->config['tpl']))
        {
            $data['config'] = $this->config['tpl'];
        }

        return View::factory($template, $data);
    }
}
