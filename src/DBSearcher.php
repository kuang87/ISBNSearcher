<?php


class DBSearcher implements SearcherInterface
{
    const SEARCH_ISBN = 'isbn';

    public function find($object)
    {
        switch ($object) {
            case self::SEARCH_ISBN:
                return $this->findISBN();
            
            default:
                throw new Exception ('Invalid find object');
        }
    }

    protected function findISBN()
    {
        $pdo = Database::getInstance();
        $sql = "SELECT id, description_ru, isbn, isbn2, isbn3, isbn4, isbn_wrong FROM books_catalog WHERE description_ru REGEXP '[0-9]+' LIMIT 1000";
        $query = $pdo->query($sql);

        //создаем новый отчет
        $report = new Report();

        foreach ($query->fetchAll() as $row){

            //все числа в строке
            $num = preg_replace('/[^0-9]/', '', $row['description_ru']);

            //если длина не менее 10
            if (strlen($num) > 9){

                $isbn_wrong = explode(',', preg_replace('/[^0-9,]/', '', $row['isbn_wrong']));
                if ($isbn_wrong[0] == '')
                    unset($isbn_wrong[0]);

                $isbn4 = explode(',', preg_replace('/[^0-9,]/', '', $row['isbn4']));
                if ($isbn4[0] == '')
                    unset($isbn4[0]);

                $isbn4Check = function() use($num, $isbn4){
                    foreach ($isbn4 as $value) {
                        if (strpos($num, ($value == '') ? 'aaa' : $value) !== false) {
                            return true;
                        }
                    }
                    return false;
                };
                //Проверяем совпадения isbn с другими полями
                if (strpos($num, $this->strToNum($row['isbn'])) !== false ||
                    strpos($num, $this->strToNum($row['isbn2'])) !== false ||
                    strpos($num, $this->strToNum($row['isbn3'])) !== false ||
                    $isbn4Check()
                    ){
                        //Событие в отчет с ID строки, [description_ru], где найдено совпадение
                        $report->message = 'Найденный ISBN уже есть в списке';
                        $report->id_book = $row['id'];
                        $report->description_ru = $row['description_ru'];
                        $report->save();
                } //Совпадений не найдено - ищем уникальный isbn
                else {
                    while (strlen($num) > 9) {
                        //Если находим что-то похожее на isbn
                        if (preg_match('/((?:97[89])?\d{9}[\d])/', $num, $matches)) {
                            $isbn = $matches[0];
                            $length = strlen($isbn);
                            $checksum = $isbn[$length-1] * 1 + $isbn[$length-2] * 2 +
                            $isbn[$length-3] * 3 + $isbn[$length-4] * 4 + $isbn[$length-5] * 5 + 
                            $isbn[$length-6] * 6 + $isbn[$length-7] * 7 + $isbn[$length-8] * 8 +
                            $isbn[$length-9] * 9 + $isbn[$length-10] * 10;
                            //Проверка на валидность ISBN
                            //Если правильный
                            if (($checksum % 11) === 0){
                                //сохраняем в колонке isbn2-4 и Отчет

                                if (empty($row['isbn2'])){
                                    $this->saveISBN($row['id'], $isbn, false, 2);
                                }
                                elseif (empty($row['isbn3'])){
                                    $this->saveISBN($row['id'], $isbn, false, 3);
                                }
                                else{
                                    //собираем isbn4
                                    $isbn4[] = $isbn;
                                    $this->saveISBN($row['id'], $isbn4, false, 4);
                                }

                                $report->message = 'Найден ISBN: ' . $isbn . ' в строке ' . $row['id'] . ', сохранен в поле ';
                                $report->id_book = $row['id'];
                                $report->description_ru = $row['description_ru'];
                            }
                            else{
                                //ISBN неправильный
                                //сохраняем в ISBN wrong и Отчет
                                $isbn_wrong[] = $isbn;

                                //$this->saveISBN($row['id'], $isbn_wrong, true);

                                $report->message = 'Найденый ISBN: ' . $isbn . ' в строке ' . $row['id'] . ' неверный.';
                                $report->id_book = $row['id'];
                                $report->description_ru = $row['description_ru'];
                            }
                            $report->save();
                        }
                        $num = substr($num, 1, strlen($num)-1);
                    }
                    $this->saveISBN($row['id'], $isbn_wrong, true);
                }
            }
        };
        //exit;
        return $report->id;
    }

    protected function strToNum($str)
    {
        $result = preg_replace('/[^0-9]/', '', $str);
        return ($result == '') ? 'aaa' : $result;
    }

    protected function saveISBN($idbook, $isbn, bool $wrong, $isbnNum = 1)
    {
        $pdo = Database::getInstance();

        if ($wrong) {
            //isbn неправильный - сохраняем в isbn_wrong
            $isbn = implode(",", $isbn);
            $sql = "UPDATE books_catalog SET isbn_wrong = :isbn WHERE id = :idbook";
            $query = $pdo->prepare($sql);
            $query->bindParam('isbn',$isbn);
            $query->bindParam('idbook', $idbook);
            $query->execute();
        }
        else {
            //isbn правильный -  сохраняем в isbn...
            switch ($isbnNum){
                case 2:
                    $sql = "UPDATE books_catalog SET isbn2 = :isbn WHERE id = :idbook";
                    $query = $pdo->prepare($sql);
                    $query->bindParam('isbn',$isbn);
                    $query->bindParam('idbook', $idbook);
                    $query->execute();
                    break;

                case 3:
                    $sql = "UPDATE books_catalog SET isbn3 = :isbn WHERE id = :idbook";
                    $query = $pdo->prepare($sql);
                    $query->bindParam('isbn',$isbn);
                    $query->bindParam('idbook', $idbook);
                    $query->execute();
                    break;

                case 4:
                    $isbn = implode(",", $isbn);
                    $sql = "UPDATE books_catalog SET isbn4 = :isbn WHERE id = :idbook";
                    $query = $pdo->prepare($sql);
                    $query->bindParam('isbn',$isbn);
                    $query->bindParam('idbook', $idbook);
                    $query->execute();
                    break;

                default:
                    throw new Exception ('Invalid isbn number');
            }
        }
    }
}