<?php

// массив входных ссылок для парсинга
$url = array(
    "subdomain.domain3.domain2.zone:port/folder/subfolder/../../../myfolder/script.php?var1=val1&var2=val2&var2=val3",
    "protocol://subdomain.domain3.domain2.zone:port/folder/subfolder/../././//../myfolder/script.php?var1=val1&var2=val2",
    "http://http.ru/folder/subfolder/../././script.php?var1=val1&var2=val2",
    "https://http.google.com/folder//././?var1=val1&var2=val2",
    "ftp://mail.ru/?hello=world&url=https://http.google.com/folder//././?var1=val1&var2=val2",
    "mail.ru/?hello=world&url=https://http.google.com/folder//././?var1=val1&var2=val2",
    "domain2.zone:8080/folder/subfolder/../././../asdss/.././//////../myfolder/script.php?var1=val1&var2=val2",
    "http://dom.dom.domain2.com:8080/folder/subfolder/./myfolder/script.php?var1=val1&var2=val2?var1=val1&var2=val2",
);

for($index = 0; $index < sizeof($url); $index++){
    $arr = MyUrlParse($url[$index]);
    DeleteNoArgument($arr);
    FormingFinallyArray($arr);
    print '<pre>';
    print_r($arr);
    print '</pre>';
}

/** 
*   Функция парсит url
*
*   @param string $url - ссылка для парсинга
*
*   @return array $parse_array - отпарсенный массив
*/
function MyUrlParse(string $url){
    preg_match_all(
        '~
            ((?<protocol>[a-z0-9]*)://)*                        #протокол
            ((?<domain>([a-z0-9]*\.)*                           #домен
            (?<second_level_domain>[a-z0-9]*)\.                 #домен второго уровная
            (?<zone>[a-z0-9]*)?)                                #зона
            (:*(?<port>\w*)))*                                  #порт
            /?(?<raw_folder>([a-z0-9.]*\/)*)?                   #относительный путь
            ((?<script_name>[a-z0-9]*                           #имя скрипта
            \.?(?<is_php>[a-z0-9]*))\?)*                        #скрипт php?
            (?<var>[a-z0-9]+)=(?<val>[a-z0-9.\:\/\?=]+)&*       #параметры и значения
    ~xsi', $url, $parse_array);

    return $parse_array;

}

/**
*   Функция удаляет ненужные элементы массива
* 
*   @param array $parse_array - отпарсенный массив
*/
function DeleteNoArgument(array &$parse_array){
    $count = count($parse_array);

    for($index = 0; $index < $count; $index++){
        unset($parse_array[$index]);
    }
    foreach($parse_array as &$key){
        $size = count($key);
        for($index = 0; $index < $size; $index++){
            if($key[$index] == "")
                unset($key[$index]);
        }
    }
}

/**
*   Функция формирует финальный массив
*   
*   @param array $parse_array - отпарсенный массив
*/
function FormingFinallyArray(array &$parse_array){

    // формируем массив параметр - значение, учитывая, что в последнем параметре с тем же именем значение для записи
    $temp = array_reverse(array_combine($parse_array['val'],$parse_array['var']));
    $temp = array_flip(array_unique($temp));

    // итоговый массив
    $arr = array(
        'protocol'         => $parse_array['protocol'][0] ?? "",
        'domain'           => $parse_array['domain'][0] ?? "",
        'zone'             => $parse_array['zone'][0] ?? "",
        '2_level_domain'   => $parse_array['second_level_domain'][0] ?? "", 
        'port'             => $parse_array['port'][0] ?? "", 
        'raw_folder'	   => $parse_array['raw_folder'][0] ?? "", 
        'folder'           => "",
        'script_path'      => "", 
        'script_name'      => $parse_array['script_name'][0] ?? "",
        'is_php'           =>  $parse_array['script_name'] ==  "php" ? "true" : "false",
        'parameters'	   => $temp,
        'is_error'         => "false",
    );


    // проверка протокола
    if($arr['protocol'] == ""){
        unset($arr['protocol']);
        $arr['raw_folder'] = $arr['port']== "" ? $arr['domain']."/".$arr['raw_folder'] : $arr['domain'].":".$arr['port']."/".$arr['raw_folder'];
        $arr['domain'] = "false";
    }

    // задание folder
    if($arr['raw_folder'] != ""){
        ParseFolder($arr);
    }

    // задание script_path
    $arr['script_path'] = $arr['folder'].$arr['script_name'];

    // проверка доменов > 5 ? 
    if($arr['domain'] != ""){
        if(substr_count($arr['domain'],".") > 5){
            $arr['is_error'] = "true";
        }
    }

    // проверка порта
    if($arr['port'] == ""){
        $arr['port'] = "80";
    }

    // 2_level_domain
    if($arr['2_level_domain'] != ""){
        $arr['2_level_domain'] = $arr['2_level_domain'].".".$arr['zone'];
    }

    // установление дефолтного скрипта в случае его отсутсвия
    if($arr['script_name'] == ""){
        $arr['script_name'] = 'index.php';
        $arr['is_php'] = "true";
    }

    $parse_array = $arr;
}

/** 
*   Функция ищет парсит строку и вычисляет фактический путь к файлу
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $url - ссылка для парсинга
*/
function ParseFolder(&$parse_url){

    // разбиваем строку по '/' для работы с ней
    $parts = preg_split('/\//', $parse_url['raw_folder']);

    // кол-во частей
    $size = count($parts);

    // чистим массив $parts, оставляя в нём только '..' и имена папок
    for ($index = 0; $index < $size; $index++){
        if($parts[$index] == "" || $parts[$index] == "."){
            unset($parts[$index]);
        }
    }

    // возвращает индексированный массив значений
    $parts = array_values($parts);

    // счетчик сколько раз нужно "подняться вверх относительно текущей папки"
    $go_up_count = 0;

    // последняя папка
    $last_folder = "/";

    // весь фактический путь
    $folder = "";

    // идём в обратном порядке
    for ($index = count($parts) - 1; $index >= 0; $index--) {
        
        // нужно будет подняться на уровень вверх, запоминаем
        if($parts[$index] == ".."){
            $go_up_count++;
        }

        // если у нас встретилась папка и нужно было подняться, уменьшаем, продолжаем 
        else if($go_up_count > 0){
            $go_up_count--;
            continue;
        }

        // если у нас встретилась папка и подниматься не нужно, добавляем в факт путь 
        else if($go_up_count == 0){
            $folder = "/".$parts[$index] . $folder;
        }
        
        // запоминаем нашу последнюю открытую папку
        if($go_up_count == 0 && $last_folder == "/"){
            $last_foler = "/".$parts[$index];
        }
    }

    // если всё ещё нужно подниматься вверх, то по заданию выйти за домен нельзя, значит мы в последней папке
    if($go_up_count > 0){
        $parse_url['folder'] =  $last_folder."/";
    }
    else{
        $parse_url['folder'] = $folder."/";
    }
}
