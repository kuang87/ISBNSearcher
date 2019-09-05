<?php

require_once '../config/Database.php';
require_once '../src/SearcherInterface.php';
require_once '../src/DBSearcher.php';
require_once '../src/Report.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET'){

    header('Content-Type: application/json, charset=UTF-8');
    if (isset($_GET['isbn'])){
        try{
            $searcher = new DBSearcher();
            $result = $searcher->find(DBSearcher::SEARCH_ISBN);
            $report = Report::find($result);
            $response['report_id'] = $result;
            $response['report_data'] = $report;
        }catch (Exception $exception){
            echo json_encode('непредвиденная ошибка (error)');
        }
        echo json_encode($response);
    }
    elseif (isset($_GET['report']) && preg_match('/^[0-9]+$/', $_GET['report'])){
        try{
            $id = $_GET['report'];
            Report::exportToFile($id, "report$id");
        }catch (Exception $exception){
            echo json_encode('непредвиденная ошибка (error)');
        }
        echo json_encode('Success');
    }
    else{
        echo json_encode('Неверный адрес');
    }
}
