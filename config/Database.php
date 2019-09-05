<?php

class Database{
    private static $instance;

    public static function getInstance(){
        if (self::$instance == null){
            self::$instance = new PDO('mysql:host=localhost;port=33060;dbname=books;charset=UTF8', 'homestead', 'secret', [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        }
        return self::$instance;
    }
}