<?php
/**
 * Created by PhpStorm.
 * User: x-stels
 * Date: 04.10.2018
 * Time: 3:05
 */

namespace konstantinLev\pagination;
use Exception;

class Pagination
{
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
    private $_pagStart = ''; //todo for oracle DB

    /**
     * @var string Окончание запроса с учетом пагинации
     */
    private $_pagEnd = ' LIMIT :limit, :countRows ';//todo??

    /**
     * Pagination constructor.
     * @param $query
     * @param $params
     * @throws Exception
     */
    public function __construct($query, $params)
    {
        $this->validate($query, $params);
        $this->buildQueryWithPagination($query);
        $this->_countOnPage = (int)$params['countOnPage'];
        $this->_totalCount  = (int)$params['totalCount'];
        return $this;
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

            $countRows = $rows_per_page;
            $limit = $countRows * $cur_page - $countRows;

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

            $this->_params['countRows'] = $countRows;
            $this->_params['limit'] = $limit;
            $this->_params['total_pages'] = $total_pages;
            $this->_params['cur_page'] = $cur_page;
            $this->_params['pages'] = isset($pages) ? $pages : '';
        }
        return $this->_params;
    }

    /**
     * Отрисовка линк-пейджера (системы управления страницами)
     * @param string $className - класс для настройки css-свойств
     * @param int $leftRightNum - кол-во страниц справа и слева от активной страницы
     * @return string
     * @throws Exception
     */
    public function drawLinkPager($className = '', $leftRightNum = 4)
    {
        if($this->_totalCount == 0) return '';
        if(!is_numeric($leftRightNum))throw new Exception('param leftRightNum be a numeric!');
        if(!is_string($className)) throw new Exception('param className be a string!');
        $leftRightNum = (int)$leftRightNum;
        $result = '<div class="pagination-block">';
        $result .= 'Текущая страница: '.$this->_params['cur_page'].' из '.$this->_params['total_pages'].'<br>';
        $result .= '<ul class="pagination '.$className.'">';
        $linkBegin = $this->_params['pages'][1];
        $linkEnd = $this->_params['pages'][count($this->_params['pages'])];
        $result .= '<li><a href="'.$linkBegin.'">«</a></li>';
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
        $result .= '<li><a href="'.$linkEnd.'">»</a></li></ul></div>';
        echo $result;
    }

    /**
     * @param $query
     * @param $params
     * @throws Exception
     */
    private function validate($query, $params)
    {
        if(empty($query)) throw new Exception('Missed query param!');
        if(!is_string($query)) throw new Exception('query must be a string');
        if(!isset($params['countOnPage'])) throw new Exception('Missed countOnPage param!');
        if(!isset($params['totalCount'])) throw new Exception('Missed totalCount param!');
        if(!is_numeric($params['countOnPage'])) throw new Exception('countOnPage must be a numeric!');
        if(!is_numeric($params['totalCount'])) throw new Exception('totalCount must be a numeric!');
    }

    /**
     * @param $query
     */
    private function buildQueryWithPagination($query)
    {
        $this->_query = $this->_pagStart.$query.$this->_pagEnd;
    }
}