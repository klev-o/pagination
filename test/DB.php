<?php
// класс работы с базой данных
class DB
{
    // инстанс подключения к БД
    private static $_instance = null;

    const DB_HOST = 'localhost';
    const DB_NAME = 'test';
    const DB_USER = 'root';
    const DB_PASS = '';

    //создаем подключение к базе данных
    private function __construct()
    {
        self::$_instance = new PDO('mysql:host='.self::DB_HOST.';dbname='.self::DB_NAME, self::DB_USER, self::DB_PASS);
        self::$_instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$_instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        //устанавливаем кодировку
        self::$_instance->query('set names utf8');
    }

    //используем для выполнения запросов к БД
    //select
    public function select($query, $params = array())
    {
        try {
            $sth = self::$_instance->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $this->bindArrayValue($sth, $params);
            $sth->execute();
            return $sth->fetchAll();
        }
        catch(PDOException $e){
            echo 'Error : '.$e->getMessage();
            exit();
        }
    }

    //используем для выполнения запросов к БД
    //insert,update,delete
    public function execute($query, $params = array())
    {
        try {
            $sth = self::$_instance->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

            $this->bindArrayValue($sth, $params);
            return $sth->execute();
        }
        catch(PDOException $e){
            echo 'Error : '.$e->getMessage();
            exit();
        }
    }

    // получение инстанса БД
    public static function getInstance()
    {
        if (self::$_instance != null) {
            return self::$_instance;
        }
        return new self;
    }

    // подстановка значений для выполняемого запроса
    private function bindArrayValue($req, $array, $typeArray = false)
    {
        if(is_object($req) && ($req instanceof PDOStatement))
        {
            foreach($array as $key => $value)
            {
                if($typeArray)
                    $req->bindValue(":$key",$value,$typeArray[$key]);
                else
                {
                    if(is_int($value))
                        $param = PDO::PARAM_INT;
                    elseif(is_bool($value))
                        $param = PDO::PARAM_BOOL;
                    elseif(is_null($value))
                        $param = PDO::PARAM_NULL;
                    elseif(is_string($value))
                        $param = PDO::PARAM_STR;
                    else
                        $param = FALSE;
                    if($param && strripos($req->queryString, ":$key")){

                        $req->bindValue(":$key",$value,$param);
                    }
                }
            }
        }
    }
}