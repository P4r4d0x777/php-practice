<?php
require_once 'Template.php';
$frame = file_get_contents('template.html');
$cars  = [
    ['manufactor' => 'Tesla', 'model' => 'Cybertruck', "hp" => 240],
    ['manufactor' => 'Tesla', 'model' => 'Model S', "hp" => 249],
    ['manufactor' => 'Opel', 'model' => 'Admiral', "hp" => 13],
    ['manufactor' => 'Volvo', 'model' => 'C70', "hp" => 49],
    ['manufactor' => 'Lamborghini', 'model' => 'Diablo', "hp" => 10],
    ['manufactor' => 'Mercedes-Benz', 'model' => 'W108', "hp" => 22],
    ['manufactor' => 'Ford', 'model' => 'Mustang', "hp" => 155],
    ['manufactor' => 'Mercedes-Benz', 'model' => 'Zetros', "hp" => 247],
    ['manufactor' => 'Cadillac', 'model' => 'CT5', "hp" => 110],
    ['manufactor' => 'Ferrari', 'model' => 'FF', "hp" => 88],
    ['manufactor' => 'Chevrolet', 'model' => 'SS', "hp" => 170],
    ['manufactor' => 'Chevrolet', 'model' => 'Camaro', "hp" => 190],
    ['manufactor' => 'Aston Martin', 'model' => 'Vulcan', "hp" => 205],
    ['manufactor' => 'Lamborghini', 'model' => 'Urus', "hp" => 15],
    ['manufactor' => 'Mercedes-Benz', 'model' => 'W114', "hp" => 18],
    ['manufactor' => 'Tesla', 'model' => 'Model X', "hp" => 210],
    ['manufactor' => 'Mercedes-Benz', 'model' => 'W110', "hp" => 19]
];

$keys            = sortCarsByHp($cars);
$max_step        = findMaxStep($keys , 50);
$colors          = array("#FF0000","#FF7F50","#8FBC8F","#00FF7F","#7FFF00");

// базовые значения интервала цвета и количества строк после
foreach($keys as &$item){
    $item['interval'] = '0';
    $item['color']    = '0';
    $item['row']      = '0';
}

// заполнение интервала и цвета для 1ого элемента в категории 
for($index_of_step = 50, $index_of_colors = 0; $index_of_step <= $max_step; $index_of_step += 50){
    foreach($keys  as &$item){
        if($index_of_step - 50 < $item['hp'] && $item['hp'] <= $index_of_step){
                $item['interval'] = ($index_of_step-50).'-'.$index_of_step.'л.с.';
                $item['color'] = $colors[$index_of_colors++];
                break;
        }
    }
}

$keys = union($keys);
$carsArr['keys'] = $keys;

// вывод
print(Template::build($frame, $carsArr));

/**
* Функция сортирует массив по hp
* 
* @param  array $__cars    - исходный массив машин
*
* @return array $sort_cars - отсортированный по hp массив машин
*/
function sortCarsByHp(array $__cars)
{

    // массив из hp машин
    $hp_arr = array();
    foreach($__cars as $item){
        $hp_arr[] = $item['hp'];
    }

    // по возрастанию
    asort($hp_arr);

    // узнаём ключи у элементов массива с определенными л.с. и сортируем в новый массив по возрастанию
    $keys      = array_keys($hp_arr);
    $sort_cars = array();
    for($index = 0; $index < count($__cars); $index++){
        $sort_cars[$index] = $__cars[$keys[$index]];
    }
    return $sort_cars;
}

/**
* Функция возвращает максимальное hp, округленное с учётом $min_step (=50)
*
* @param  array $__cars   - исходный массив машин
* @param  int   $min_step - изначальный шаг 
*
* @return int   $result   - max hp
*/
function findMaxStep(array $__cars, int $min_step)
{
    $max_step = $min_step;
    foreach($__cars as $item){
        if($item['hp'] > $max_step)
            $max_step = $item['hp'];
    }
    $result = ceil($max_step/$min_step) * $min_step;
    return $result;
}

/**
* Функция объединяет машины одного производителя и возвращает отсортированный массив
* @param  array $__cars - исходный массив
*
* @return array $items  - отсортированный массив
*/
function union(array $__cars)
{

    // массив, где будут хранится объединения по группно
    $union  = array();
    $max_hp = 0;
    // массивы в котором хранятся марки и модели машин которые не надо брать
    $names_of_manufactors = [];
    $names_of_models      = [];
    for($index = 0, $step = 0; $index < sizeof($__cars); $index++, $step++){

        // максимальное для определенной группы
        $max_hp = 0;
        if((in_array($__cars[$index]['manufactor'], $names_of_manufactors) !== true) && (in_array($__cars[$index]['model'], $names_of_models))!==true){
            $first = $__cars[$index];
        }
        else if((in_array($__cars[$index]['manufactor'], $names_of_manufactors) == true) && (in_array($__cars[$index]['model'], $names_of_models))!==true){
            $first = $__cars[$index];
        }
        else
            continue;

        // определяем в какой категории будем искать
        while($max_hp < $__cars[$index]['hp']){
                $max_hp+=50;
        }
        foreach($__cars as $items){
            if($items['manufactor']==$first['manufactor'] && $items['hp']<=$max_hp && ((in_array($items['model'], $names_of_models))!==true)){
                $union[$step][]         = $items;
                $names_of_models[]      = $items['model'];
            }
        }
        $names_of_manufactors[] = $first['manufactor'];

        // присваиваем 1 элементу в блоке сколько будет после него строк объединения
        $union[$step][0]['row'] = sizeof($union[$step]);
    }

    //добалвение полученных групп в новый массив со всеми машинами
    $items = array(); 
    foreach($union as $item){
        $items = array_merge($items, $item);
    }
    return $items;
}