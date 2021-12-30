<?php

// массив входных ссылок для парсинга
$url = array(
    "protocol://subdomain.domain3.domain2.zone:port/folder/subfolder/../././//../myfolder/script.php?var1=val1&var2=val2",
    "http://http.ru/folder/subfolder/../././script.php?var1=val1&var2=val2",
    "https://http.google.com/folder//././?var1=val1&var2=val2",
    "ftp://mail.ru/?hello=world&url=https://http.google.com/folder//././?var1=val1&var2=val2",
    "mail.ru/?hello=world&url=https://http.google.com/folder//././?var1=val1&var2=val2",
    "index.html?mail=ru",
    // вот так все сломалось, а просто скрипт должен быть index/php
    "domain2.zone:8080/folder/subfolder/../././../asdss/.././//////../myfolder?var1=val1&var2=val2",
    "http://dom.dom.domain2.com:8080/folder/subfolder/./myfolder/script.php?var1=val1&var2=val2?var1=val1&var2=val2",
);

// массив отпарсенных ссылок
$parse_url = array();

// вызов функции разбиениия url по категориям для каждой ссылки
for($index = 0; $index < sizeof($url); $index++){
    $parse_url[$index] = MyUrlParse($url[$index]);
}

// вывод отпарсенных ссылок
for($index = 0; $index < sizeof($url); $index++){
    print '<pre>';
    print_r($parse_url[$index]);
    print '</pre>';
}

/** 
*   Функция преобразует массив ссылок для парсинга и возвращает массив, разбивающий их по параметрам url
*
*   @param string $url - ссылка для парсинга
*
*   @return array
*/
function MyUrlParse($url){

    // параметр url, отображающий "поддоменов > 5?"
    $is_error = 'false';

    // массив отпарсенных ссылок
    $parse_url = array();
    ParseProtocol($parse_url, $url);
    SearchDomain($parse_url, $url, $is_error);
    SearchRawFolder($parse_url, $url, $url);
    SearchFolder($parse_url, $url);
    SetScriptPath($parse_url, ParseScriptPath($parse_url,$url), $url);
    SearchParameters($parse_url, $url);
    $parse_url['is_error'] = $is_error;
    return $parse_url;
}

/** 
*   Функция парсит ссылку и выделяет из неё протокол
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $url - ссылка для парсинга
*/
function ParseProtocol(&$parse_url, &$url){

    // определяем с какого символа начинается протокол
    $pos_protocol = strpos($url, '://');
    
    // проверка "нашелся ли протокол?"
    if($pos_protocol !== false){

        // проверка что протокол находится не в параметрах
        if($pos_protocol < strpos($url, '/')){
        
            // записываем в массив
            $parse_url['protocol'] = substr($url, 0, $pos_protocol);
        
            // обрезаем строку 
            $url = substr($url, $pos_protocol + 3, strlen($url) - $pos_protocol - 1);
        }
    }
}

/** 
*   Функция ищет домен в исходной ссылке, если находит - вызывает функцию для его парсинга
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $url - ссылка для парсинга
*   @param bool   $is_error - параметр url, отображающий "поддоменов > 5?"
*/
function SearchDomain(&$parse_url, &$url, &$is_error){

    // находим позицию первого слеша
    $pos_slash = strpos($url, '/');
    if($pos_slash !== false){
        // если ':' нашелся проверяем что он не в параметрах
        if(strpos($url, ':') !== false){

            //позиция ':' для проверки что домен находится не в параметрах 
            if($url[$pos_slash] < $url[strpos($url, ':')]){
                ParseDomain($parse_url, substr($url, 0, $pos_slash), $is_error);
            }
        }
        // если ':' нет, нас это устроит, можно парсить
        else{
            ParseDomain($parse_url, substr($url, 0, $pos_slash), $is_error);
        }
    }
}

/** 
*   Функция парсит ссылку и выделяет из неё части домена : 'zone', 'domain', '2_level_domain', 'port'
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $url - ссылка для парсинга
*   @param bool   $is_error - параметр url, отображающий "поддоменов > 5?"
*/
function ParseDomain(&$parse_url, $url, &$is_error){

    // первое вхождение ':'
    $pos_colon = strpos($url, ':');

    // если протокола нет в массиве параметров, по заданию domain = false
    if(array_key_exists('protocol', $parse_url) == false){
        $parse_url['domain'] = 'false';
    }
    // если есть, записываем домен в параметры
    else{
        if($pos_colon !== false){

            // запись домена в парметры
            $parse_url['domain'] = substr($url, 0, $pos_colon);

            // после домена идет порт, записыаем его в параметры
            $parse_url['port'] = substr($url,  $pos_colon + 1, strlen($url));

            // обрезаем строку, так, чтобы сотался один домен для explode по точке
            $url = substr($url, 0,  $pos_colon);
        }
        else{
            // нет ':' значит вся оставшаяся строка в домен
            $parse_url['domain'] = substr($url, 0, strlen($url));

            // дефолтный порт
            $parse_url['port'] = '80';
        }
    }

    // разбиваем строку по разделителю - '.'
    $domains = explode(".", $url);

    // знаем, что после последней точки идёт zone
    $parse_url['zone'] = $domains[count($domains) - 1];

    // 2_level_domain это от предпослдней точки до конца
    $parse_url['2_level_domain'] = $domains[count($domains) - 2] . "." . $domains[count($domains) - 1];

    // если поддоменов > 5, 'is_error' в true
    if(str_word_count($url,0,".")>5){
        $is_error = 'true';
    }
}

/** 
*   (Recusive)
*
*   Функция ищет путь к файлу(введённый в url), если есть вызывает функцию парсинга пути
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $url - ссылка для парсинга
*   @param string $string_to_delete - строка, равная url, но в которой можно работать (удалять, обрезать и т.д)
*/
function SearchRawFolder(&$parse_url, &$url, $string_to_delete){

    // последнее вхождение '/'
    $pos_slash = strrpos($string_to_delete, '/');
    if($pos_slash !== false){
        
        // по следующим условиям поймём, что это rawfolder: если '/' совпадает с концом строки ИЛИ '/' идёт до '?' ИЛИ '?' не найдно и последнее вхождение '/' совпадает с первым вхождением '/'
        if(($pos_slash == strlen($string_to_delete) - 1 || $pos_slash < strpos($string_to_delete, '?') || strpos($string_to_delete, '?')===false) && strrpos($string_to_delete, '/') != strpos($string_to_delete, '/')){
            ParseRawFolder($parse_url, $pos_slash, $url);
        }
        // значит последний '/' не обозначает конец rawfolder
        else{
            // уходим в рекурсию, заменяя $pos_slash в url на какой-то символ, пусть 'N'
            $string_to_delete[$pos_slash] = "N";

            // ищем дальше, но уже без последнего '/'
            SearchRawFolder($parse_url, $url, $string_to_delete);
        }
    }
}

/** 
*   Функция парсит ссылку и выделяет из неё rawfolder
*
*   @param array    $parse_url - массив парамтеров url
*   @param integer  $pos_slash - позиция '/' на которой заканчивается rawfolder
*   @param string   $url - ссылка для парсинга
*/
function ParseRawFolder(&$parse_url, $pos_slash, &$url){

    // если есть домен то парсим
    if(array_key_exists('domain', $parse_url) !== false){

        // различие по заданию если domain = false
        if($parse_url['domain'] == 'false'){
            $parse_url['raw_folder'] = substr($url, 0, $pos_slash + 1);
        }
        else{
            $parse_url['raw_folder'] = substr($url, strpos($url, '/'), $pos_slash - strpos($url, '/') + 1);
        }
    }
}

/** 
*   Функция ищет фактический путь к файлу, если находит то вызывает функцию для парсинга
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $url - ссылка для парсинга
*/
function SearchFolder(&$parse_url, &$url) {

    // если, вообще, есть относительный путь, то тогда ищем
    if (array_key_exists('raw_folder', $parse_url) !== false){
        /*ParseFolder($parse_url, substr($parse_url['raw_folder'],strpos($parse_url['raw_folder'],'/'),strrpos($parse_url['raw_folder'],'/') + 1 - strpos($parse_url['raw_folder'],'/')));*/
        ParseFolder($parse_url,$parse_url['raw_folder']);
    }
}

/** 
*   Функция ищет парсит строку и вычисляет фактический путь к файлу
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $url - ссылка для парсинга
*/
function ParseFolder(&$parse_url, $url){

    // разбиваем строку по '/' для работы с ней
    $parts = explode("/", $url);

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

/** 
*   Функция ищет и парсит scriptpath и scriptname, возвращает имя скрипта
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $url - ссылка для парсинга
*
*   @return string
*/
function ParseScriptPath(&$parse_url, $url){
    $pos_question = strpos($url, '?');

    //если вопрос найден
    if($pos_question !== false){

        // если названия исполн. файла нет то дефолт
        if($url[$pos_question - 1] == '/'){
            $script_name = "index.php";
        }
        else{

            //если есть проверка на '/'
            if(strpos($url, '/') !== false){

                // выделяем имя скрипта, ища его до первого слеша перед scriptname
                $iter = 1;
                while($url[$pos_question - $iter - 1] != '/'){
                    $iter++;   
                }
                $script_name = substr($url, $pos_question - $iter, $iter);
            }
            else{
                $script_name = substr($url, 0, $pos_question);
            }
        }
    }
    else if($pos_question === false){
        //конец строки
        if(strrpos($url, '/') + 1 == strlen($url)){
            $script_name = "index.php";
        }
        else{
            $script_name = substr($url , strrpos($url, '/'), strlen($url) - 1 - strrpos($url, '/'));
        }     
    }
    return $script_name;
}

/** 
*   Функция записывает script_path и script_name в параметры массива
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $script_name - имя скрипта
*   @param string $url - ссылка для парсинга
*/
function SetScriptPath(&$parse_url, $script_name, $url)
{

    // проверка на то, есть ли folder
    if (array_key_exists('folder',$parse_url) !== false){
        $parse_url['script_path'] = $parse_url['folder'] . $script_name;
    }
    else{
        $parse_url['script_path'] = $script_name;
    }
    $parse_url['script_name'] = $script_name;

    // проверка на php
    if(strpos($url, ".php") !== false){
        $parse_url['is_php'] = 'true';
    }
    else{
        $parse_url['is_php'] = 'false';
    }
}

/** 
*   Функция ищет параметры и записывает их в массив параметров url
*
*   @param array  $parse_url - массив парамтеров url
*   @param string $url - ссылка для парсинга
*/
function SearchParameters(&$parse_url, $url){

    // массив парамтеров
    $parametrs = array();

    // ключи парамтеров
    $keys = array();

    // счетчик для нумерования элементов массива
    $iter=0;

    // последний амперсанд
    $pos = strrpos($url, '&');
    while($pos !== false){
        $pos_equal = strrpos($url, '=');

        // проверка, что = самое левое к амперсанту
        ChekingForLastEqualSign($url, $pos, $pos_equal);

        // проверка чтобы не перезаписывать ключи с их значениями(в последнем - верное)
        if(!array_key_exists(substr($url,$pos+1,$pos_equal-$pos-1), $parametrs)){
                
                // запись значения
                $parametrs[substr($url,$pos+1,$pos_equal-$pos-1)] = substr($url, $pos_equal+1, strlen($url) - $pos_equal);
                // запись ключа

                $keys[$iter++] = substr($url,$pos+1,$pos_equal-$pos-1);
        }

        //обрезаем строку до следующего параметра
        $url = substr($url, 0, $pos);

        // определяем позицию следующего амперсанда
        $pos = strrpos($url, '&');
    }

    // если амперсанды закночились значит остался один параметр после знака ?, аналогично предыдущему:
    $question = strrpos($url, '?');
    if($question !== false){
        $pos_equal = strpos($url, '=');
        if(!array_key_exists(substr($url,$pos+1,$pos_equal-$pos-1),$parametrs)){
            $parametrs[substr($url,$question+1,$pos_equal-$question-1)] = substr($url, $pos_equal+1, strlen($url) - $pos_equal);
            $keys[$iter] = substr($url,$question+1,$pos_equal-$question-1);
        }
    }

    // соединяем массив параметров и массив ключей в нужном порядке
    $array = array();
    for($index = sizeof($parametrs) - 1; $index >= 0; $index--){
        $array[$keys[$index]] = $parametrs[$keys[$index]];
    }

    // запись в параметры
    $parse_url['parametrs'] = $array;
}
/** 
*   (Recursive)
* 
*   Функция проверяет в строке для одного параметра нужное ли "=" нашли или нет(необходимо самое левое к '&')
*
*   @param string $url - ссылка для парсинга
*   @param int    $pos - номер амперсанта в строке 
*   @param int    $pos_equal - номер '=' которое проверяем
*
*   @return integer
*/
function ChekingForLastEqualSign($url, $pos, &$pos_equal){

    // берём последнее '='
    $check = strrpos($url, '=');

    // обрезаем от имени переменной до последнего '=' включительно
    $substring = substr($url, $pos+1, $pos_equal - $pos);

    // если первое вхождение совпало с последним значит нашли то '=', которое нужно
    if(strrpos($substring, '=') == strpos($substring, '=')){
        $pos_equal = $check;
        return 0;
    }
    // если не совпало заменяем последнее '=' в строке значений на любую букву(к примеру N)
    else{
        $url[$check] = 'N';

        //уходим в рекурсию, ищем дальше
        ChekingForLastEqualSign($url, $pos, $pos_equal);
    }
}