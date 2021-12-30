<?php
// Задокументировать функции
// echo (а лучше print) должен быть ОДИН раз, до этого все аккумулировать в строковую переменную.
/*
* (noRecurive) 
* Функция вычисляет член последовательности Фибоначчи с заданным номером
* @param $__index Номер для которого вычислится член последовательности
*/
function fibonacci($__index)
{
    if($__index < 1) return 0;
    if($__index <=2) return 1;
    $current = $pre_pre = 1; // что за pre_pre? 
    for($i = 3; $i <= $__index; $i++){
        $pre = $current;
        $current = $pre + $pre_pre;
        $pre_pre = $pre;
    }
    return $current;
}
/*
* (Recursive)
* Функция вычисляет член последовательности Фибоначчи с заданным номером
* @param $__index Номер для которого вычислится член последовательности
*/
function fibonacciRecursive($__index)
{
    if($__index < 1)
        return 0; // переносить операторы на новую строку
    if($__index <=2) 
        return 1;
    return fibonacciRecursive($__index - 1) + fibonacciRecursive($__index - 2);
}
$_n = 10;
for($i = 1; $i <= $_n; $i++)
    $_array[$i-1] = fibonacci($i);
for($i = 1; $i <= $_n; $i++)
    $_array[$i+9] = fibonacciRecursive($i);

//строка для вывода
$string = "";
foreach($_array as $temp)
    $string .= $temp.' ';
print $string;
?>