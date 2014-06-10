<?php defined('SYSPATH') or die('No direct script access.');
return array
(
	'default' => array(
        'items_per_page'    => 15,
        'view'              => 'paginate/bootstrap',
        'auto_hide'         => TRUE,
        'first_page_in_url' => FALSE,
        'prev_next_links'   => TRUE,
        'current_page'      => array('source' => 'query_string', 'key' => 'page') // Source: query_string||route_param
    ),
    'count_in_out' => array(
        'items_per_page'    => 15,
        'view'              => 'paginate/counted',
        'tpl'               => array(
                                'count_in' => 1,
                                'count_out' => 5
                            ),
        'auto_hide'         => TRUE,
        'first_page_in_url' => FALSE,
        'prev_next_links'   => FALSE,
        'current_page'      => array('source' => 'query_string', 'key' => 'page') // Source: query_string||route_param
    )
);