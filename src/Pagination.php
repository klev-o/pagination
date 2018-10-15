<?php
/**
 * Created by PhpStorm.
 * User: x-stels
 * Date: 04.10.2018
 * Time: 3:05
 */

namespace konstantinLev\pagination;
use Exception;

/**
 * Class Pagination
 * @package konstantinLev\pagination
 */
class Pagination
{
    const DB_TYPE_MYSQL  = 'mysql';
    const DB_TYPE_ORACLE = 'oracle';

    /**
     * @var int Number of records to display on the page
     */
    private $_countOnPage = 0;

    /**
     * @var int Total number of records
     */
    private $_totalCount = 0;

    /**
     * @var string query
     */
    private $_query = '';

    /**
     * @var null Pagination options
     */
    private $_params = null;

    /**
     * @var string The beginning of the query with pagination
     */
    private $_pagStart = '';

    /**
     * @var string The end of the query with pagination
     */
    private $_pagEnd = '';

    /**
     * @var string Class name to configure css-properties
     */
    private $_className = '';

    /**
     * @var int Number of elements to the right and left of the active in the link pager
     */
    private $_leftRightNum = 4;

    /**
     * @var bool Show or not the information block (the current page and the total number of pages are displayed)
     */
    private $_showInfo = true;

    /**
     * @var array Link pager controls
     */
    private $_controls = ['«','»'];

    /**
     * @var array Possible database types
     */
    private $_typesDB = [
        'mysql',
        'oracle'
    ];

    /**
     * @var string Database type used in the project
     */
    private $_typeDB = 'mysql';

    /**
     * Pagination constructor.
     * @param $query
     * @param $params
     * @throws Exception
     */
    public function __construct($query, $params)
    {
        $this->validate($query, $params);
        $this->_countOnPage  = (int)$params['countOnPage'];
        $this->_totalCount   = (int)$params['totalCount'];
        $this->_className    = !empty($params['className']) ? $params['className'] : '';
        $this->_leftRightNum = !empty($params['leftRightNum']) ? (int)$params['leftRightNum'] : $this->_leftRightNum;
        $this->_controls     = !empty($params['controls']) ? $params['controls'] : $this->_controls;
        $this->_showInfo     = isset($params['showInfo']) ? $params['showInfo'] : $this->_showInfo;
        $this->_typeDB       = isset($params['db']) ? $params['db'] :  $this->_typeDB;
        $this->buildQueryWithPagination($query);
        $this->getParams();
        return $this;
    }

    /**
     * Demonstration of the pagination module features
     * @return array
     */
    public function demo()
    {
        $data = [];
        $result = [];
        for($i=0;$i < $this->_totalCount; $i++){
            $data[$i]['title'] = 'Item '.($i+1);
        }
        $params = $this->getParams();
        for($i = $params['limit']; $i < $params['countRows'] + $params['limit']; $i++){
            if(!empty($data[$i])){
                $result[] = $data[$i];
            }
        }
        return $result;
    }

    /**
     * Getting a prepared query
     * @return string
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Getting parameters for query execution
     * @return array
     */
    public function getParamsForQuery()
    {
        $data = [];
        $params = $this->getParams();
        switch ($this->_typeDB){
            case self::DB_TYPE_MYSQL:
                $data['limit'] = $params['limit'];
                $data['countRows'] = $params['countRows'];
                break;
            case self::DB_TYPE_ORACLE:
                $data['from_f'] = $params['from_f'];
                $data['to_end'] = $params['to_end'];
                break;
        }
        return $data;
    }

    /**
     * Getting parameters for query execution and drawing link-pager
     * @return null|array
     */
    public function getParams()
    {
        if(is_null($this->_params)){
            // how many entries to display on one page
            $rows_per_page = $this->_countOnPage;

            // total records
            $total_rows = $this->_totalCount;

            // total pages
            $total_pages = ceil($total_rows / $rows_per_page);

            if($total_pages < 1) $total_pages = 1;

            //current page number
            if (isset($_GET['page'])){
                $cur_page = is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                if ($cur_page < 1 || $cur_page > $total_pages) $cur_page = 1;
            } else {
                $cur_page = 1;
            }

            $this->setParams($rows_per_page, $cur_page, $total_pages);

            $parts = parse_url($_SERVER['REQUEST_URI']);
            $queryParams = array();
            if(!isset($parts['query'])) $parts['query'] = '';
            parse_str($parts['query'], $queryParams);
            unset($queryParams['page']);

            $uri = $parts['path'].'?';
            if (count($queryParams)){
                $uri .= http_build_query($queryParams) . "&";
            }
            for($i = 1; $i <= $total_pages; $i++){
                $pages[$i] = $uri . 'page=' . $i;
            }
            $this->_params['pages'] = isset($pages) ? $pages : '';
        }
        return $this->_params;
    }

    /**
     * Setting parameters for query execution and drawing link-pager
     * @param $rows_per_page
     * @param $cur_page
     * @param $total_pages
     */
    private function setParams($rows_per_page, $cur_page, $total_pages)
    {
        switch ($this->_typeDB){
            case self::DB_TYPE_MYSQL:
                $countRows = $rows_per_page;
                $limit = $countRows * $cur_page - $countRows;
                $this->_params['countRows'] = $countRows;
                $this->_params['limit'] = $limit;
                $this->_params['total_pages'] = $total_pages;
                $this->_params['cur_page'] = $cur_page;
                break;
            case self::DB_TYPE_ORACLE:
                $from_f = ($cur_page - 1) * $rows_per_page;
                $to_end = ($from_f == 0) ? $rows_per_page : ($from_f + $rows_per_page);
                $this->_params['from_f'] = $from_f;
                $this->_params['to_end'] = $to_end;
                $this->_params['total_pages'] = $total_pages;
                $this->_params['cur_page'] = $cur_page;
                break;
        }
    }

    /**
     * Link Pager (Page Management)
     * @return string
     * @throws Exception
     */
    public function drawLinkPager()
    {
        if($this->_totalCount == 0) return '';

        $leftRightNum = $this->_leftRightNum;
        $controlLeft  = $this->_controls[0];
        $controlRight = $this->_controls[1];
        $className    = $this->_className;

        $result = '<div class="pagination-block">';
        $result .= '<div class="info" style="display: '.($this->_showInfo ? 'block' : 'none').'">Текущая страница: '.$this->_params['cur_page'].' из '.$this->_params['total_pages'].'</div>';
        $result .= '<ul class="pagination '.$className.'">';
        $linkBegin = $this->_params['pages'][1];
        $linkEnd = $this->_params['pages'][count($this->_params['pages'])];
        $result .= '<li><a href="'.$linkBegin.'">'.$controlLeft.'</a></li>';
        /***total links in link-pager (active link + links to the left and right of the active)**/
        $countLink = $leftRightNum * 2 + 1;
        if($this->_params['total_pages'] < $countLink){
            for($i = 1; $i <= $this->_params['total_pages']; $i++){
                if ($i == $this->_params['cur_page']){
                    $result .= '<li class="active"><span>'.$i.'</span></li>';
                } else {
                    $result .= '<li><a href="'.$this->_params['pages'][$i].'">'.$i.'</a></li>';
                }
            }
        } else {
            /***left threshold***/
            $leftOffset = $this->_params['cur_page'] - $leftRightNum;
            if($leftOffset <= 0) $leftOffset = 1;
            if ($this->_params['cur_page'] > ($this->_params['total_pages'] - $leftRightNum)){
                $leftOffset = $this->_params['total_pages'] - $countLink + 1;
            }
            /***right threshold***/
            $rightOffset = $this->_params['cur_page'] + $leftRightNum;
            if($this->_params['cur_page'] <= $leftRightNum) $rightOffset = $countLink;
            if($rightOffset > $this->_params['total_pages']) $rightOffset = $this->_params['total_pages'];

            for($i = $leftOffset; $i <= $rightOffset; $i++){
                if ($i == $this->_params['cur_page']){
                    $result .= '<li class="active"><span>'.$i.'</span></li>';
                } else {
                    $result .= '<li><a href="'.$this->_params['pages'][$i].'">'.$i.'</a></li>';
                }
            }
        }
        $result .= '<li><a href="'.$linkEnd.'">'.$controlRight.'</a></li></ul></div>';
        return $result;
    }

    /**
     * Parameter validation
     * @param $query
     * @param $params
     * @throws Exception
     */
    private function validate($query, $params)
    {
        if(empty($query))                            throw new Exception('Missed query param!');
        if(!is_string($query))                       throw new Exception('query must be a string');
        if(!isset($params['countOnPage']))           throw new Exception('Missed countOnPage param!');
        if(!isset($params['totalCount']))            throw new Exception('Missed totalCount param!');
        if(!is_numeric($params['countOnPage']))      throw new Exception('countOnPage must be a numeric!');
        if(!is_numeric($params['totalCount']))       throw new Exception('totalCount must be a numeric!');
        if(!is_string($params['className']))         throw new Exception('param className be a string!');
        if(isset($params['showInfo'])){
            if(!is_bool($params['showInfo']))        throw new Exception('param showInfo be a bool!');
        }
        if(!empty($params['leftRightNum'])){
            if(!is_numeric($params['leftRightNum'])) throw new Exception('leftRightNum must be a numeric!');
        }
        if(!empty($params['controls'])){
            if(!is_array($params['controls']))       throw new Exception('controls must be array!');
            if(count($params['controls']) !== 2)     throw new Exception('controls must contain 2 elements!');
        }
        if(isset($params['db'])){
            if(!in_array($params['db'], $this->_typesDB)) throw new Exception('unknown type db!');
        }
    }

    /**
     * Build sql query with pagination
     * @param $query
     */
    private function buildQueryWithPagination($query)
    {
        switch ($this->_typeDB){
            case self::DB_TYPE_MYSQL:
                $this->_pagStart = '';
                $this->_pagEnd = 'LIMIT :limit, :countRows ';
                break;
            case self::DB_TYPE_ORACLE:
                $this->_pagStart = 'select * from (select rownum b, a.* from (';
                $this->_pagEnd = ') a) where b> :from_f and b<= :to_end  ';
                break;
        }
        $this->_query = $this->_pagStart.$query.$this->_pagEnd;
    }
}