<?php

//исходный массив
$word_array_arr = array(
    array("emotion", "ecstatic", "conent", "bored", "happy"),
    array("berry", "cher11111111111111111111111ry", "blackberry"),
    array("man", "woman", "body", "arm", "head", "shoulder111111111111111111111111111", "leg"),
    array("sport", "football", "soccer", "basketball", "tennis", "golf", "rugby"),
    array("teacher", "driver", "manager"),
    array("clothes",  "socks", "sweater", "cap"),
);

//создание массива с максимальной длиной слова из каждого подмассива
$lenght_max_words = FindMaxWordInColumn($word_array_arr);

//отсортированная строка
$string = SortArray($word_array_arr, $lenght_max_words);

//вывод строки
print "<pre>$string</pre>";

/** 
*   Функция формирует массив из количества знаков в словах максимльной длины из подмассивов 
*
*   @param array $arr - исходный массив
*
*   @return array
*/
function FindMaxWordInColumn(array $arr){
    $index = 0;

    //создание массива с максимальной длиной слова из каждого подмассива
    $lenght_max_words = array(count($arr));

    //прохожу циклом по подмассивам
    foreach($arr as $element){

        //создаю элемент для обозначения слова максимальной длины
        $word_max_size = $element[0];
        $lenght_max_words[$index] = strlen($element[0]);

        //сравнение на нахождение элемента максимальной длины
        foreach($element as $word){
            if(strlen($word) > strlen($word_max_size)){
                $word_max_size = $word;
                $lenght_max_words[$index] = strlen($word);
            }
        }
        $index++;
    }
    return $lenght_max_words;
}  
/** 
*   Функция сортирует слова по левому и праву краю
*
*   @param array $arr - исходный массив
*   @param array $lenght_max_words - массив с максимальными длинами слов из каждого подмассива
*
*   @return string
*/
function SortArray(&$arr, $lenght_max_words){

    //транспонирование массива слов для построчного вывода
    $arr = array_map(null, ...$arr);
    $string = "";
    foreach($arr as $element){

        //флаг для чередования отступов слева/справа
        $odd = true; // переименуйте в odd/even
        $i = 0;
        foreach($element as $word){

            // добавление к строке слов, выравнивая их поочередно 
            $odd == true? $string .= str_pad($word, $lenght_max_words[$i] + 1, " ", STR_PAD_RIGHT) : $string .= str_pad($word, $lenght_max_words[$i] + 1, " ", STR_PAD_LEFT) . " ";
            $odd = !$odd;

            // следующее слово будет из следующей колонки, увеличивая $i перейдем в массиве $lenght_max_words к максимлальной длине слова для следующей колонки
            $i++;
        }
        $string .= "\n";
    }
    return $string;
}
