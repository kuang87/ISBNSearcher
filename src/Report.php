<?php

class Report
{
    public $id;
    public $message;
    public $id_book;
    public $description_ru;

    public function __construct()
    {
        $this->id = $this->add();
    }

    public static function find($id)
    {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM report_detail WHERE id_report = :id";
        $query = $pdo->prepare($sql);
        $query->bindParam('id',$id);
        $query->execute();

        return $query->fetchAll();
    }

    public function save()
    {
        $pdo = Database::getInstance();
        $sql = 'INSERT INTO report_detail (id_report, message, id_book, description_ru) VALUES (:id, :message, :id_book, :description_ru)';
        $query = $pdo->prepare($sql);
        $query->bindParam('id',$this->id);
        $query->bindParam('message', $this->message);
        $query->bindParam('id_book', $this->id_book);
        $query->bindParam('description_ru', $this->description_ru);

        $query->execute();

        return $pdo->lastInsertId();
    }

    protected function add()
    {
        $pdo = Database::getInstance();
        $sql = 'INSERT INTO reports () VALUES ()';
        $pdo->query($sql);

        return $pdo->lastInsertId();
    }

    public static function exportToFile($id, $name)
    {
        $pdo = Database::getInstance();
        $name .= '_' . date('d-m-Y');
        $sql = "SELECT * FROM report_detail WHERE id_report = :id INTO OUTFILE '/tmp/$name.cvs' FIELDS TERMINATED BY ';'";
        $query = $pdo->prepare($sql);
        $query->bindParam('id',$id);
        $query->execute();
    }
}