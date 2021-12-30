<?php
// долгосрок берите, да
$bases_arr = [
    // Сделайте строками
    "bin" => ['0', '1'],
    "mybin" => ['x', 'y'], // почему-то не получается сложить числа
    "oct" => ['0', '1', '2', '3', '4', '5', '6', '7'],
    "dec" => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
    "hex" => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'],  
];

// Изначальные значения реузльтата ошибок с.с. и алфавита
$result    = '';
$errors    = '';
$base_name = 'dec';
// Тоже строкой, но это лишнее, у вас значение цифры - это ее позиция в алфавите. Собственно, мой пример из-за этого и не работает
$all       = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F','G','H', 'I', 'J', 'K', 'L', 'M', 'N','O','P', 'Q', 'R', 'S','T','U', 'V', 'W', 'X', 'Y', 'Z'];

// проверяем какая операция выполняется в калькуляторе и достаточно ли параметров для её выполнения div = division
if(isset($_POST['plus']) || isset(($_POST['minus'])) || isset(($_POST['div'])) || isset(($_POST['multi']))){
    $base_name = $_POST['base'];
    if((strlen($_POST['param1']) > 0) && (strlen($_POST['param2']) > 0 )){
        if(isset($_POST['plus'])){
            $result = sum($_POST['param1'], $_POST['param2'], $bases_arr[$base_name]);
        }
        if(isset($_POST['minus'])){
            $result = minus($_POST['param1'], $_POST['param2'], $bases_arr[$base_name]);
        }
        if(isset($_POST['div'])){
            $result = division($_POST['param1'], $_POST['param2'], $bases_arr[$base_name]);
        }
        if(isset($_POST['multi'])){
            $result = multi($_POST['param1'], $_POST['param2'], $bases_arr[$base_name]);
        }
    }
    else if(((strlen($_POST['param1']) > 0) && (strlen($_POST['param2']) < 1)) && (strlen($_POST['result']) > 0)){
        if(isset($_POST['plus'])){
            $result = sum($_POST['result'], $_POST['param1'], $bases_arr[$base_name]);
        }
        if(isset($_POST['minus'])){
            $result = minus($_POST['result'], $_POST['param1'], $bases_arr[$base_name]);
        }
        if(isset($_POST['div'])){
            $result = division($_POST['result'], $_POST['param1'], $bases_arr[$base_name]);
        }
        if(isset($_POST['multi'])){
            $result = multi($_POST['result'], $_POST['param1'], $bases_arr[$base_name]);
        }
    }
    else if(((strlen($_POST['param1']) < 1) && (strlen($_POST['param2']) > 0)) && (strlen($_POST['result']) > 0)){
        if(isset($_POST['plus'])){
            $result = sum($_POST['result'], $_POST['param2'], $bases_arr[$base_name]);
        }
        if(isset($_POST['minus'])){
            $result = minus($_POST['result'], $_POST['param2'], $bases_arr[$base_name]);
        }
        if(isset($_POST['div'])){
            $result = division($_POST['result'], $_POST['param2'], $bases_arr[$base_name]);
        }
        if(isset($_POST['multi'])){
            $result = multi($_POST['result'], $_POST['param2'], $bases_arr[$base_name]);
        }
    }
    else{
        $errors = "Not Enough Parameters";
    }
}

// формирование html
$html = 
'<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Сalculator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="calc">
    <p id = "head"> Calculator </p>
    <p>Select Number System: </p>
    <form action="index.php" method="post">
        <select class="base" name="base">';

//поиск выбранной с.с.
foreach(array_keys($bases_arr) as $base){
    ($base == $base_name)? $html.="<option selected value=$base> $base </option>":$html.="<option value=$base>$base</option>";
}

$html .= 
"</select>
    <input type='text' name='param1' placeholder='First parametr'>
    <input type='text' name='param2' placeholder='Second parametr'>
    <p>Sign:</p>
    <div class = 'signs'>
        <input type='submit' name='plus' value ='+'>
        <input type='submit' name='minus' value ='-'>
        <input type='submit' name='div' value ='/'>
        <input type='submit' name='multi' value ='*'>
    </div>
    Result: <input name='result' value='$result' type='text'>
    </br>
    Errors: <input name='errors' value='$errors' type='text'>
</form>
</div>

</body>
</html>";

print $html;
/**
* Сложение
* 
* @param string $__param1 - первое число
* @param string $__param2 - второе числоа
* @param array  $__base   - система счисления
*
* @return string $res - сумма
*/
function sum(string $__param1, string $__param2, array $__base)
{
    $__param1 = mb_strtoupper($__param1);
    $__param2 = mb_strtoupper($__param2);

    global $all;
    global $errors;

    // обработка ошибки 
    if((($__param1[0] == $__base[0]) && (strlen($__param1))!==1) || (($__param2[0] == $__base[0]) && (strlen($__param1))!==1)) {
        $errors = "Error! Number can`t start with 0";
        return false;
    }

    // определим знак
    if($__param1[0] == "-"){
        $sign1     = -1;
        $__param1  = substr($__param1, 1);
    }
    else{
        $sign1 = 1;
    }
    if($__param2[0] == "-"){
        $sign2      = -1;
        $__param2   = substr($__param2, 1);
    }
    else{
        $sign2 = 1;
    }
    $size = sizeof($__base);
    
    // проверяем первое число
    $part1 = "";
    $key = -1;
    for($index = 0; $index < strlen($__param1); $index++){
        for($char = 0; $char < $size; $char++){
            if($__base[$char] == $__param1[$index]){
                $key = $char;
            }
        }
        if($key == -1){
            $errors = "Wrong scale of notation";
            return false;
        }
        else
            $part1 .=$all[$key];
        $key = -1;
    }

    // проверяем второе число
    $part2 = "";
    $key = -1;
    for($index = 0; $index < strlen($__param2); $index++){
        for($char = 0; $char < $size; $char++){
            if($__base[$char] == $__param2[$index]){
                $key = $char;
            }
        }
        if($key == -1){
            $errors = "Wrong scale of notation";
            return false;
        }
        else    
            $part2 .= $all[$key];
        $key = -1;
    }

    // оформление результата
    $res = base_convert($part1, $size, 10) * $sign1 + base_convert($part2, $size, 10) * $sign2;
    $sign_res = "";
    if($res < 0){
        $sign_res = "-";
        $res = $res * (-1);
    }

    $res = base_convert($res, 10, $size);
    $res = mb_strtoupper($res);
    
    $output = "";
    for($index = 0; $index < strlen($res); $index++){
        for($char = 0; $char < $size; $char++){
            if($all[$char] == $res[$index]){
                $output .= $__base[$char];
            }
        }
    }
    $output = $sign_res.$output;
    return $output;
}

/**
* Вычитание
* 
* @param string $__param1 - первое число
* @param string $__param2 - второе числоа
* @param array  $__base   - система счисления
*
* @return string $res - разность
*/
function minus(string $__param1, string $__param2, array $__base)
{
    if($__param2[0] == "-"){
        $sign_param2 = substr($__param2, 1);
    }
    else{
        $sign_param2 = "-".$__param2;
    }

    $res = sum($__param1, $sign_param2, $__base);
    return $res;
}

/**
* Деление
* 
* @param string $__param1 - первое число
* @param string $__param2 - второе числоа
* @param array  $__base   - система счисления
*
* @return string $res - частное
*/
function division(string $__param1, string $__param2, array $__base)
{
    $__param1 = mb_strtoupper($__param1);
    $__param2 = mb_strtoupper($__param2);

    global $all;
    global $errors;

    // обработка ошибки 
    if((($__param1[0] == $__base[0]) && (strlen($__param1))!==1) || (($__param2[0] == $__base[0]) && (strlen($__param1))!==1)) {
        $errors = "Error! Number can`t start with 0";
        return false;
    }
    
    if($__param2 == $__base[0]){
        $errors = "It is impossible to divide by 0";
        return false;
    }
    // определим знак
    if($__param1[0] == "-"){
        $sign1     = -1;
        $__param1  = substr($__param1, 1);
    }
    else{
        $sign1 = 1;
    }
    if($__param2[0] == "-"){
        $sign2      = -1;
        $__param2   = substr($__param2, 1);
    }
    else{
        $sign2 = 1;
    }
    $size = sizeof($__base);
    
    // проверяем первое число
    $part1 = "";
    $key = -1;
    for($index = 0; $index < strlen($__param1); $index++){
        for($char = 0; $char < $size; $char++){
            if($__base[$char] == $__param1[$index]){
                $key = $char;
            }
        }
        if($key == -1){
            $errors = "Wrong scale of notation";
            return false;
        }
        else
            $part1 .=$all[$key];
        $key = -1;
    }

    // проверяем второе число
    $part2 = "";
    $key = -1;
    for($index = 0; $index < strlen($__param2); $index++){
        for($char = 0; $char < $size; $char++){
            if($__base[$char] == $__param2[$index]){
                $key = $char;
            }
        }
        if($key == -1){
            $errors = "Wrong scale of notation";
            return false;
        }
        else    
            $part2 .= $all[$key];
        $key = -1;
    }

    // оформление результата
    $res = (int)(base_convert($part1, $size, 10) * $sign1 / base_convert($part2, $size, 10) * $sign2);
    $sign_res = "";
    if($res < 0){
        $sign_res = "-";
        $res = $res * (-1);
    }

    $res = base_convert($res, 10, $size);
    $res = mb_strtoupper($res);
    
    $output = "";
    for($index = 0; $index < strlen($res); $index++){
        for($char = 0; $char < $size; $char++){
            if($all[$char] == $res[$index]){
                $output .= $__base[$char];
            }
        }
    }
    $output = $sign_res.$output;
    return $output;
}

/**
* Произведение
* 
* @param string $__param1 - первое число
* @param string $__param2 - второе числоа
* @param array  $__base   - система счисления
*
* @return string $res - произведение
*/
function multi(string $__param1, string $__param2, array $__base)
{
    $__param1 = mb_strtoupper($__param1);
    $__param2 = mb_strtoupper($__param2);

    global $all;
    global $errors;

    // обработка ошибки 
    if((($__param1[0] == $__base[0]) && (strlen($__param1))!==1) || (($__param2[0] == $__base[0]) && (strlen($__param1))!==1)) {
        $errors = "Error! Number can`t start with 0";
        return false;
    }

    // определим знак
    if($__param1[0] == "-"){
        $sign1     = -1;
        $__param1  = substr($__param1, 1);
    }
    else{
        $sign1 = 1;
    }
    if($__param2[0] == "-"){
        $sign2      = -1;
        $__param2   = substr($__param2, 1);
    }
    else{
        $sign2 = 1;
    }
    $size = sizeof($__base);
    
    // проверяем первое число
    $part1 = "";
    $key = -1;
    for($index = 0; $index < strlen($__param1); $index++){
        for($char = 0; $char < $size; $char++){
            if($__base[$char] == $__param1[$index]){
                $key = $char;
            }
        }
        if($key == -1){
            $errors = "Wrong scale of notation";
            return false;
        }
        else{
            $part1 .=$all[$key];
        }
        $key = -1;
    }

    // проверяем второе число
    $part2 = "";
    $key = -1;
    for($index = 0; $index < strlen($__param2); $index++){
        for($char = 0; $char < $size; $char++){
            if($__base[$char] == $__param2[$index]){
                $key = $char;
            }
        }
        if($key == -1){
            $errors = "Wrong scale of notation";
            return false;
        }
        else    
            $part2 .= $all[$key];
        $key = -1;
    }

    // оформление результата
    $res      = (base_convert($part1, $size, 10) * $sign1 * base_convert($part2, $size, 10) * $sign2);
    $sign_res = "";
    if($res < 0){
        $sign_res = "-";
        $res      = $res * (-1);
    }

    $res = base_convert($res, 10, $size);
    $res = mb_strtoupper($res);
    
    $output = "";
    for($index = 0; $index < strlen($res); $index++){
        for($char = 0; $char < $size; $char++){
            if($all[$char] == $res[$index]){
                $output .= $__base[$char];
            }
        }
    }
    $output = $sign_res.$output;
    return $output;
}