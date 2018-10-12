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
     * @var int Кол-во записей для отображения на странице
     */
    private $_countOnPage = 0;

    /**
     * @var int Всего кол-во записей
     */
    private $_totalCount = 0;

    /**
     * @var string Строка запроса в бд
     */
    private $_query = '';

    /**
     * @var null Параметры пагинации
     */
    private $_params = null;

    /**
     * @var string Начало запроса с учетом пагинации
     */
    private $_pagStart = '';

    /**
     * @var string Окончание запроса с учетом пагинации
     */
    private $_pagEnd = '';

    /**
     * @var string класс для настройки css-свойств
     */
    private $_className = '';

    /**
     * @var int кол-во элементов справа и слева от активного
     */
    private $_leftRightNum = 4;

    /**
     * @var bool Показ информационного блока
     */
    private $_showInfo = true;

    /**
     * @var array элементы управления линк-пейджером
     */
    private $_controls = ['«','»'];

    private $_typesDB = [
        'mysql',
        'oracle'
    ];

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
        return $this;
    }

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
     * @return string
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * @return null|array
     */
    public function getParams()
    {
        if(is_null($this->_params)){
            //сколько записей отображать на одной странице
            $rows_per_page = $this->_countOnPage;

            // общее количество записей
            $total_rows = $this->_totalCount;

            // общее количество страниц
            $total_pages = ceil($total_rows / $rows_per_page);

            if($total_pages < 1) $total_pages = 1;

            // номер текущей страницы
            if (isset($_GET['page'])){
                $cur_page = is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                if ($cur_page < 1 || $cur_page > $total_pages) $cur_page = 1;
            } else {
                $cur_page = 1;
            }

            $this->setParamsForQuery($rows_per_page, $cur_page, $total_pages);

            $parts = parse_url($_SERVER['REQUEST_URI']);
            $queryParams = array();
            if(!isset($parts['query'])) $parts['query'] = '';
            parse_str($parts['query'], $queryParams);
            unset($queryParams['page']);

            $uri = $parts['path'].'?';
            if (count($queryParams)){
                $uri .= http_build_query($queryParams) . "&";
            }
            // массив со ссылками на страницы
            for($i = 1; $i <= $total_pages; $i++){
                $pages[$i] = $uri . 'page=' . $i;
            }
            $this->_params['pages'] = isset($pages) ? $pages : '';
        }
        return $this->_params;
    }

    private function setParamsForQuery($rows_per_page, $cur_page, $total_pages)
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
                // значение первой записи для LIMIT
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
     * Отрисовка линк-пейджера (системы управления страницами)
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
        //всего ссылок в линк-пейджере (активная ссылка + ссылки слева и справа от активной)
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
            /***левый порог***/
            $leftOffset = $this->_params['cur_page'] - $leftRightNum;
            if($leftOffset <= 0) $leftOffset = 1;
            if ($this->_params['cur_page'] > ($this->_params['total_pages'] - $leftRightNum)){
                $leftOffset = $this->_params['total_pages'] - $countLink + 1;
            }
            /***правый порог***/
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
     * Валидация параметров
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