<?php
################################################################################
#Template goes as a part of Simpla CMS Libraries, by Sergey Sorokin (aka dCm)
#Please contact dev@simpla.ru for terms of use
################################################################################

class Template {
#Вид шаблона
# <!-- cycle[-4] -->   - объявление цикла, в квадратных скобках ограничения на количество итераций. Знак минус - считать от размера данных.
# 	<!-- cycle.$cyclevar --><!-- .$cyclevar -->   - в этих переменных одно и то же!
#	<!-- RootVar.$var --> - переменная самого верхнего уровня
#	<!-- uppercycle.$var --> - переменная вышестоящего цикла
#	<!-- $globalvar --> - глобальная переменная шаблонизатора
#	<!-- $global_arr['index']['index2'] --> - глобальная переменная шаблонизатора из массива

###условный блок на итерацию цикла cycle
###	<!-- cycle%2=1 -->   - выполнится, if((итерация цикла) % 2 == 1)
###		то что надо вывести в шаблон
###	<!-- cycle%_ends -->

# <!-- cycle_ends -->

###условный блок на значение переменной
###	<!-- cycle.$cyclevar<>10 -->   выполнится, если значение переменной !=10; Поддерживаются <>, <, >, =
###		то, что надо вывести в шаблон (поддерживается вложенность конструкций)
###	<!-- cycle.$cyclevar_ends -->

###рекурсивный блок, вставка производится на место <!-- INSERT_RECURSION_block --> в любом из случаев:
### 1. Есть подциклы в блоке данных
### 2. Установлена переменная FORCE_RECURSION = true в данных на текущей итерации
###	<!-- RECURSION_block -->
###	<ul><!-- links -->
###		<li><!-- .$link_name --><!-- INSERT_RECURSION_block --></li>
###	<!-- links_ends --></ul>
###	<!-- RECURSION_block_ends -->



static $variables;
static $Globals; #Глобальные переменные шаблонов (используются различными компонентами)
static $iteration_data=array(); #Временно сохраненные данные с верхних уровней
static $recursive_blocks = array(); #Для хранения блоков рекурсии, которые подлежат подстановке

static function process_if_constructions ($__TemplateCode){
	#Функция производит разбор if-конструкций верхнего уровня шаблона с использованием данных, сохраненных в self::iteration_data
	#Поддерживается бесконечная вложенность конструкций (рекурсия)
	#Работа ведется в допущении, что if-конструкции не должны лежать внутри циклов

	$splits= preg_split("/<!--\s*([a-z0-9_]+)[.][$]([a-z0-9_]+)([<>=]{1,2})([a-z0-9_]+)\s*-->(.*?)<!--\s*\\1[.][$]\\2_ends\s*-->/uis", $__TemplateCode,-1,PREG_SPLIT_DELIM_CAPTURE);
	#$splits - это массив следующей структуры:
	#0+6N элемент - код, не содержащий IF-конструкций.
	#1+6N элемент - заголовок цикла.
	#2+6N элемент - название переменной.
	#3+6N элемент - операция.
	#4+6N элемент - значение переменной.
	#5+6N элемент - содержимое if-конструкции.
	$NewTemplateCode = '';
	$need_end=false;
    foreach($splits as $SplitNumber=>$SplitPart)
    if($SplitNumber%6===0){

    	#Удаление закрытых циклов
    	$tmp = preg_replace("/<!--\s*([a-z0-9_]+)\[?(\-?[0-9]*)\]?\s*-->(.*?)<!--\s*\\1_ends\s*-->/uis",'',$SplitPart);

    	if($need_end){
    		#Поиск хвоста цикла
	    	if(preg_match("/<!--\s*(".$need_end.")_ends\s*-->/uis", $tmp, $result)){
	    		$need_end = false;
	    		$tmp2 = explode($result[0], $tmp, 2);
	    		$tmp = $tmp2[1];
	    	}
    	}
		if(!$need_end){
			#Поиск открытого цикла
	    	if(preg_match("/<!--\s*([a-z0-9_]+)\[?(\-?[0-9]*)\]?\s*-->/uis", $tmp, $result))
	    		$need_end = $result[1];
		}

    	if(!$need_end) {
    		#Текущая if-конструкция должна быть проанализирована, т.к. находится на верхнем уровне шаблона
    		if(isset($splits[$SplitNumber+1])&&isset(self::$iteration_data[($splits[$SplitNumber+1]?$splits[$SplitNumber+1]:'RootVar').".".$splits[$SplitNumber+2]])){
    			#Проверка условия if-конструкции
    			if(self::check_operators(self::$iteration_data[($splits[$SplitNumber+1]?$splits[$SplitNumber+1]:'RootVar').".".$splits[$SplitNumber+2]], $splits[$SplitNumber+4], $splits[$SplitNumber+3]))
    		    	$NewTemplateCode.=$splits[$SplitNumber].self::process_if_constructions($splits[$SplitNumber+5]);
    		    else $NewTemplateCode.=$splits[$SplitNumber];
    		}
    		elseif(isset($splits[$SplitNumber+1])&&!isset(self::$iteration_data[($splits[$SplitNumber+1]?$splits[$SplitNumber+1]:'RootVar').".".$splits[$SplitNumber+2]])&&$splits[$SplitNumber+3]=='<>'){
    			#Условие <> подходит!
   		    	$NewTemplateCode.=$splits[$SplitNumber].self::process_if_constructions($splits[$SplitNumber+5]);

    		}
    		else
    			#Если конец шаблона или нет данных для проверки if-конструкции, то просто ее удаляем
				$NewTemplateCode.=$splits[$SplitNumber];
    	}
    	elseif(isset($splits[$SplitNumber+1])){
    		#Текущая if-конструкция находится внутри цикла, нужно передать на обработку циклов
    		$NewTemplateCode.=$splits[$SplitNumber]."<!-- ".$splits[$SplitNumber+1].'.$'.$splits[$SplitNumber+2].$splits[$SplitNumber+3].$splits[$SplitNumber+4]." -->".$splits[$SplitNumber+5]."<!-- ".$splits[$SplitNumber+1].'.$'.$splits[$SplitNumber+2]."_ends -->";
        }
        else
    		#Если конец шаблона
			$NewTemplateCode.=$splits[$SplitNumber];
    }
	return $NewTemplateCode;
}

#Основная функция заполнения шаблона (рекурсивная)
static function build($__TemplateCode, $__DataArray=array(), $__Cycle_name='.', $__Iteration_Start=0, $__Iterations=-1){
//print_r($__TemplateCode);
//print_r($__DataArray);
//print_r(self::$iteration_data);
#Базовая функция для заполнения шаблона компонента.
#Основные возможности:
#1. Поддержка Глобальных переменных шаблонов
#2. Поддержка Циклических и линейных блоков
#3. Поддержка вложенных циклов
#4. Понимает Данные текущей итерации всех циклов в любом из подциклов
#5. Понимает Ограничения шаблона на количество выводимых в шаблоне циклов
#6. Понимает Много ограниченных циклов с одинаковым названием.
#7. В каждом циклическом блоке можно разместить вставки, зависящие от текущей итерации блока.
#8. Понимает "нежадные" бесконечные циклы
#9. Поддержка глобальных переменных-массивов
#10. Обработка if любой вложенности
#11. Саморекурсивные шаблоны, т.е. шаблон размножается на нужное количество данных
	#$__TemplateCode - код шаблона;
	#$__DataArray - массив данных для вставки в шаблон;
	#$__Cycle_name="." - Название текущего цикла, . - название корневого фрагмента;
	#$__Iteration_Start=0 - Позиция начала подстановки массива данных;
	#$__Iteratons=-1 - Количество повторений для данного цикла;
	#print "<font color=green>Обработка цикла с именем ".$__Cycle_name.", начало: ".$__Iteration_Start.", количество повторений ".$__Iterations." содержит: <pre>";
	#print_r ($__DataArray);
	#print "</pre></font><br/>";

	#Анализ <!-- RECURSION_block --> и сохранение содержимого и удаление хвостов

	if(preg_match_all("/<!--\s*RECURSION_([a-z0-9_]+)\s*-->(.*?)<!--\s*RECURSION_\\1_ends\s*-->/uis", $__TemplateCode, $results))
	foreach($results[0] as $key => $result){
		self::$recursive_blocks[$results[1][$key]] = $results[2][$key];
		$__TemplateCode = str_replace($result, $results[2][$key], $__TemplateCode);
		//print '<font color="green">'.$__TemplateCode.'</font>';
	}

    $out=""; #Для сохранения результата
    $Iterations_spent=array(); #массив хранит, сколько был уже выполнен каждый цикл на текущем уровне. Используется для пересчета повторений в циклах с повторящимися именами.

	#Приведение верхнего уровня массива данных к стандартному виду подуровня
	if($__Cycle_name=="."){
		$__DataArrayTemp[0]=$__DataArray;
		$__DataArray=$__DataArrayTemp;
		unset($__DataArrayTemp);
		$__Iterations=1;
	}
	#print "<font color=green>Массив с данными для обработки цикла с именем ".$__Cycle_name.", количество повторений ".$__Iterations." содержит: <pre>";
	#print_r ($__DataArray);
	#print "</pre></font><br/>";

	#Основной цикл разбора блоков данного уровня
	$IterationsToStart=array(); #Храним сколько было выведено данных в различных циклах
    $CurrentIteration=$__Iteration_Start; #Текущее смещение для массива с данными

    $i=0; #Счетчик повторений в данном цикле
    while(isset($__DataArray[$CurrentIteration])){
        #Сохранение текущих значений переменных уровня для использования потомками
		$Prefix="";
		if($__Cycle_name=="."){self::$iteration_data =array(); $Prefix="RootVar";}
     	else  $Prefix=$__Cycle_name;
		#print "<font color=green>Цикл с именем ".$__Cycle_name."(смещение:".$CurrentIteration.") перед очисткой: <pre>"; print_r (self::$iteration_data); print "</pre></font><br/>";

        #Если на предыдущем шаге цикла было установлено больше переменных, то их надо очистить!
        foreach(self::$iteration_data as $VarName=>$VarValue)
        	if(preg_match('/'.$Prefix.'\\.[0-9a-z_]+/ui',$VarName)) unset(self::$iteration_data[$VarName]);
		#print "<font color=blue>Цикл с именем ".$__Cycle_name." после очистки: <pre>"; print_r (self::$iteration_data); print "</pre></font><br/>";

		if(isset($__DataArray[$CurrentIteration]) && is_array($__DataArray[$CurrentIteration]))
			foreach($__DataArray[$CurrentIteration] as $VarName=>$VarValue) {
				if(is_scalar($VarValue)) self::$iteration_data[$Prefix.".".$VarName] = $VarValue;
			}
		#Конец сохранения.
		#print "<font color=red>Цикл с именем ".$__Cycle_name."(смещение:".$CurrentIteration.") сохраняет даные: <pre>"; print_r (self::$iteration_data); print "</pre></font><br/>";

		#Разбор if-конструкций верхнего уровня шаблона
		$__TemplateCodeNEW =self::process_if_constructions($__TemplateCode);

	    #получаем блоки данных текущего уровня
	    //$splits=preg_split ("/<!--\s*([a-z0-9_]+)\[?(\-?[0-9]*)\]?\s*-->(.*?)<!--\s*\\1_ends\s*-->/is", $__TemplateCodeNEW,-1,PREG_SPLIT_DELIM_CAPTURE);
		#$splits - это массив следующей структуры:
		#0+4N элемент - код, не содержащий циклов.
		#1+4N элемент - заголовок цикла.
		#2+4N элемент - к-во итераций циклов.
		#3+4N элемент - содержимое цикла.
		//print "\n\nI have template:=======================================\n".$__TemplateCodeNEW;
		//print_r($splits);

		$recursion_set = false;
		
		#Проверка рекурсивного блока
		$splits=preg_split ("/(<!--\s*[a-z0-9_]+\[?\-?[0-9]*\]?\s*-->)(.*?)(<!--\s*\\1_ends\s*-->)/uis", $__TemplateCodeNEW,-1,PREG_SPLIT_DELIM_CAPTURE);
		foreach($splits as $SplitNumber=>$SplitPart)
			if($SplitNumber%4===0&&$__Cycle_name!="."){
				
				# Чтобы вставлять блок, он должен быть в линейном коде
				if(preg_match_all("/<!--\s*INSERT_RECURSION_([a-z0-9_]+)\s*-->/uis", $SplitPart, $result_rec)){
					if(self::array_has_subarrays($__DataArray[$CurrentIteration])||(isset($__DataArray[$CurrentIteration]['FORCE_RECURSION'])&&$__DataArray[$CurrentIteration]['FORCE_RECURSION'])){
						$recursion_set = true;
						foreach($result_rec[0] as $rec_id => $rec_value)
							$splits[$SplitNumber] = str_replace($rec_value, self::$recursive_blocks[$result_rec[1][$rec_id]], $splits[$SplitNumber]);
					}
					else{
						foreach($result_rec[0] as $rec_id => $rec_value)
							$splits[$SplitNumber] = str_replace($rec_value, '', $splits[$SplitNumber]);
					}
				}
			}

		if($recursion_set){
			$__TemplateCodeNEW = implode('', $splits);
		}
		
		//print "\n\nI now i've done template:=======================================\n".$__TemplateCodeNEW."\n\n\n.........................................";
		$splits = preg_split ("/<!--\s*([a-z0-9_]+)\[?(\-?[0-9]*)\]?\s*-->(.*?)<!--\s*\\1_ends\s*-->/uis", $__TemplateCodeNEW,-1,PREG_SPLIT_DELIM_CAPTURE);
		//print_r($splits);

		foreach($splits as $SplitNumber=>$SplitPart){
			#Замена линейных блоков
			if($SplitNumber%4===0){
				#Вычленяем из линейного блока блоки с условиями на итерацию цикла
				$linears=preg_split ("/<!--\s*([a-z0-9_]+)%([0-9]+)=([0-9]+)\s*-->(.*?)<!--\s*\\1%_ends\s*-->/uis", $SplitPart,-1,PREG_SPLIT_DELIM_CAPTURE);
				#$linears - это массив следующей структуры:
				#0+5N элемент - код, не содержащий циклов.
				#1+5N элемент - заголовок цикла.
				#2+5N элемент - на сколько надо делить текущую итерацию цикла.
				#3+5N элемент - Каков должен быть остаток.
				#4+5N элемент - содержимое цикла.
				#print "<font color=red>Цикл с именем ".$__Cycle_name." проверяет заполнение хвостов: <pre>"; print_r ($linears); print "</pre></font><br/>";

                foreach($linears as $LinearNumber=>$LinearPart){
					#Замена простых линейных блоков
					if($LinearNumber%5===0){
						//$out.=self::build_linear_template($SplitPart, $__DataArray[$CurrentIteration]);
						$out.=self::build_linear_template($LinearPart, $__DataArray[$CurrentIteration]);
					}
					#Замена условных линейных блоков
                    elseif($LinearNumber%5===4){
                    	if(($i+1)%intval($linears[$LinearNumber-2])===intval($linears[$LinearNumber-1]))
                    	$out.=self::build_linear_template($LinearPart, $__DataArray[$CurrentIteration]);
                    }
				}
			}
	        #Замена циклицеских блоков
	        elseif($SplitNumber%4===3){
	        	#Название цикла, поступающего на обработку
	        	$Cycle_name=$splits[$SplitNumber-2];
	        	#Количество повторений в поступающем цикле
	        	(trim($splits[$SplitNumber-1])==="")? $Iterations="Infinite" :	$Iterations=intval($splits[$SplitNumber-1]);
                #print "<font color=blue>Найден цикл: $Cycle_name <br> повторений: $Iterations - Начало цикла:".(isset($IterationsToStart[$Cycle_name])?$IterationsToStart[$Cycle_name]:"нет информации")." (смещение:".$CurrentIteration.")</font><br>";
	        	#Запуск заполнения цикла имеет смысл, только если естьь данные для текущего шага:
	        	if(@is_array($__DataArray[$CurrentIteration][$Cycle_name])){
                    #Если был установлен флаг, что ранее в шаблоне был бесконечный цикл с таким же именем
	        		if(isset($IterationsToStart[$CurrentIteration][$Cycle_name])&&$IterationsToStart[$CurrentIteration][$Cycle_name]=="Finish"){
	        			#Ничего не делаем
	        			#print "<font color=red>$Cycle_name - $Iterations</font>";
	        		}
	        		else{
		        		#Если ранее циклов с таким названием не было
		        		if(!isset($IterationsToStart[$CurrentIteration][$Cycle_name])){
		        			$IterationStart=0;
		        			if($Iterations=="Infinite") {$IterationsToStart[$CurrentIteration][$Cycle_name]="Finish"; $Iterations=-1;}
		        			elseif ($Iterations>0) $IterationsToStart[$CurrentIteration][$Cycle_name]=$Iterations;
		        			elseif ($Iterations<0){
		        			  $Iterations=count($__DataArray[$CurrentIteration][$Cycle_name])+$Iterations;
		        			  if($Iterations>0)
								$IterationsToStart[$CurrentIteration][$Cycle_name]=$Iterations;
                              else $Iterations=0;
		        			}
						}
						#Если ранее были циклы ограниченной длины
		        		else {//подразумевается if($IterationsToStart[$Cycle_name]!="Finish")
		        			$IterationStart=$IterationsToStart[$CurrentIteration][$Cycle_name];
		        			if($Iterations=="Infinite") {$IterationsToStart[$CurrentIteration][$Cycle_name]="Finish"; $Iterations=-1;}
		        			elseif ($Iterations>0) $IterationsToStart[$CurrentIteration][$Cycle_name]+=$Iterations;
		        			elseif ($Iterations<0){
		        			  $Iterations=count($__DataArray[$CurrentIteration][$Cycle_name])+$Iterations-$IterationsToStart[$CurrentIteration][$Cycle_name];
		        			  if($Iterations>0)
		        			  $IterationsToStart[$CurrentIteration][$Cycle_name]+=$Iterations;
		        			  else $Iterations=0;
		        			}


						}

		        		//print "<font color=brown>Запуск рекурсии с шага: ".$IterationStart.", количество итераций:".$Iterations."</font><br/>";
		        		#Запуск рекурсивной обработки циклического блока
		        		if($Iterations!==0){
		        			$out.=self::build($SplitPart, $__DataArray[$CurrentIteration][$Cycle_name], $Cycle_name, $IterationStart, $Iterations);
                        	//unset($IterationsToStart[$Cycle_name]);
                        }

                        //print "<font color=red>возврат рекурсии с $Cycle_name - $Iterations - ".$IterationsToStart[$Cycle_name]."</font><br/>";

                    }
                }
	        }
		}
		$i++;
		$CurrentIteration++;
		#Если выполнено необходимое количество повторений цикла
		if ($i==$__Iterations) break;
	}
	
	return $out;
}

static function array_has_subarrays($array){
	foreach($array as $subarray)
		if(is_array($subarray)) return true;
	return false;
}


#Вспомогательные Функции заполнения шаблона
static function build_linear_template($__TemplateCode, $__DataArray){
	#print "<font color=blue>Обработка линейного фрагмента:<p>".htmlentities($__TemplateCode)."</p> с массивом подстановок:<pre>";
	#print_r ($__DataArray);
	#print "</pre></font><br/>";

	#Замена глобальных переменных в подстановке. Вид: <!-- $GlobalVar -->
	$__TemplateCode = preg_replace_callback("/<!--\s*\\$([a-z0-9_]+)\s*-->/uis", "Template::replace_global_vars", $__TemplateCode);

	#Замена глобальных переменных из глобальных массивов в подстановке. Вид: <!-- $GlobalVar['name']['name2']... -->
	#Нужно ли это в циклах?
	$__TemplateCode = preg_replace_callback("/<!--\s*\\$([a-z0-9_\\[\\]\\']+)\s*-->/uis", "Template::replace_global_array_vars", $__TemplateCode);

	#Замена переменных цикла текущего уровня. Вид: <!-- .$GlobalVar -->
	$__TemplateCode = preg_replace_callback("/<!--\s*\\.\\$([a-z0-9_]+)\s*-->/is",
		function ($parameter) use ($__DataArray) {
			return self::replace_current_level_vars ($__DataArray, $parameter);	
		},
		$__TemplateCode	
	);
	
	//$HandlerFunc=self::data_arguments_carrier("Template::replace_current_level_vars");
	//$__TemplateCode = preg_replace_callback("/<!--\s*\\.\\$([a-z0-9_]+)\s*-->/is", $HandlerFunc($__DataArray), $__TemplateCode);

	#Замена переменных цикла текущего уровня. Вид: <!-- SomeCycle.$CurrentVar -->
	$__TemplateCode = preg_replace_callback("/<!--\s*([a-z0-9_]+)\\.\\$([a-z0-9_]+)\s*-->/uis", "Template::replace_full_named_vars", $__TemplateCode);

	#print "<font color=purple>Обработка завершена:<p>".htmlentities($__TemplateCode)."</p>";
	return $__TemplateCode;
}
static function replace_global_vars ($__parameter_name){
#Функция используется для подстановки глобальных переменных шаблона "на лету"
	if(isset(self::$Globals[$__parameter_name[1]])) return self::$Globals[$__parameter_name[1]];
	else return "";
}
static function replace_global_array_vars ($__parameter_name){
#Функция используется для подстановки из глобальных переменных по индексу массива шаблона "на лету"
    preg_match("/([a-z0-9_]+)([a-z0-9_\[\'\]]*)/uis", $__parameter_name[1], $arrayname);
	preg_match_all("/\[\'([a-z0-9_]+)\'\]/is", $arrayname[2], $params);
    $data=self::$Globals[$arrayname[1]];
    foreach($params[1] as $key=>$value){
    	if(isset($data[$value])) $data=$data[$value];
    	else return "";
    }
	if(is_scalar($data)) return $data;
	else return "";
}
static function replace_current_level_vars ($__Data, $__parameter_name){
#Функция используется для подстановки переменных верхнего уровня цикла шаблона "на лету"
	if(isset($__Data[$__parameter_name[1]])&&is_scalar($__Data[$__parameter_name[1]])) return $__Data[$__parameter_name[1]];
	else return "";
}
static function replace_full_named_vars ($__parameter_name){
#Функция используется для подстановки переменных с полными названиями шаблона "на лету"
	if(isset(self::$iteration_data[$__parameter_name[1].".".$__parameter_name[2]])) return self::$iteration_data[$__parameter_name[1].".".$__parameter_name[2]];
	else return "";
}

#Функция-курьер
static function data_arguments_carrier($func,$arity=2) {
#Функция, которая отложенно вызывает обработчики (callback), передавая им параметры из области видимости кода, в котором происходят вызовы.
    return create_function('', "
        \$args = func_get_args();
        if(count(\$args) >= $arity)
            return call_user_func_array('$func', \$args);
        \$args = var_export(\$args, 1);
        return create_function('','
            \$a = func_get_args();
            \$z = ' . \$args . ';
            \$a = array_merge(\$z,\$a);
            return call_user_func_array(\'$func\', \$a);
        ');
    ");
}

#Функции конструирования структуры шаблона по массиву-образцу данных
static function construct_template_for_array($__DataArray=array()){
#Функция для построения шаблона к произвольному массиву данных
	return self::construct_template_for_tree_array(self::build_parameters_tree($__DataArray));
}
static function construct_template_for_tree_array($__DataArray=array(), $__Cycle_name='.', $__TemplatePrefix=""){
#Рекурсивная функция строит пустой шаблон, соответствующий иерархии передаваемых данных
#Массив на входе - массив с информацией о структуре - результат выполнения функции build_parameters_tree()
	$TemplateCode="";
	foreach($__DataArray as $key => $value){
		$TemplateBegin="";
		$TemplateEnd="";
		$TemplateDownLevel="";
		if(is_scalar($value)) $TemplateCode.="\n".$__TemplatePrefix.'<!-- '.$__Cycle_name.'$'.$key.' -->';
		else{
			#обработка подцикла
			$TemplateBegin.="\n".$__TemplatePrefix.'<!-- '.$key.' -->';
   			$TemplateEnd="\n".$__TemplatePrefix.'<!-- '.$key.'_ends -->';
   			$TemplateDownLevel=Template::construct_template_for_tree_array($value, $key.'.', $__TemplatePrefix."	");
		}
        $TemplateCode.=$TemplateBegin.$TemplateDownLevel.$TemplateEnd;
	}

	return $TemplateCode;
}
static function build_parameters_tree($__DataArray=array(), $__Cycle_name='.'){
#Рекурсивная Функция возвращает структуру массива, собирая данные во всех его ветвях.
#Предполагается, что в различных ветвях массива переменные и циклы могут быть различными в иерархии
	#Превращение верхнего уровня в стандартный вид подуровня
	if($__Cycle_name=="."){
		$__DataArrayTemp[0]=$__DataArray;
		$__DataArray=$__DataArrayTemp;
		unset($__DataArrayTemp);
	}

	$return_array=array();
	$cache_array=array(); //нужен для того, чтобы дописывать циклы в конец уровня массива вслед за обычными переменными
	foreach($__DataArray as $ArrayItem){
		foreach($ArrayItem as $key=>$value){
			if(is_scalar($value)) $return_array[$key]='1';
			elseif(is_array($value)) $cache_array[$key]=self::build_parameters_tree($value, $key);
		}
	}
	//подписываем циклы
	foreach($cache_array as $key=>$value){
		$return_array[$key]=$value;
	}
	return $return_array;
}

#Обертки для установки глобальных переменных
static function add_global($__VariableName, $__Value){
	if(is_string($__VariableName)){
		self::$Globals[$__VariableName]=$__Value;
		return true;
	}
    return false;
}
static function delete_global($__VariableName){
	if(is_string($__VariableName)){
		unset(self::$Globals[$__VariableName]);
		return true;
	}
    return false;
}
static function add_seo ($__title_arr=array(), $__keys_arr=array(), $__desc_arr=array()){
	if(is_scalar($__title_arr))	$__title_arr=array($__title_arr);
	if(is_scalar($__keys_arr))	$__keys_arr=array($__keys_arr);
	if(is_scalar($__desc_arr))	$__desc_arr=array($__desc_arr);

    foreach($__title_arr as $key => $value){
    	if ($value!=""){Template::add_global('page_title', $value); break;}
    }
    foreach($__keys_arr as $key => $value){
    	if ($value!=""){Template::add_global('page_keywords', $value); break;}
    }
    foreach($__desc_arr as $key => $value){
    	if ($value!=""){Template::add_global('page_description', $value); break;}
    }
    return;
}
static function check_seo(){
	$vars = array('title', 'keywords', 'description');
	$write_to_end = Session::get_current_value('seo_write_end');
	foreach($vars as $var){
		if(!isset(self::$Globals['page_'.$var])||!self::$Globals['page_'.$var]){
			if(Session::get_current_value('seo_'.$var)!="")
				Template::add_global('page_'.$var, Session::get_current_value('seo_'.$var));
		}
		elseif($write_to_end){
			if(Session::get_current_value('seo_'.$var)!="")
				Template::add_global('page_'.$var, self::$Globals['page_'.$var].' - '.Session::get_current_value('seo_'.$var));
		}
	}
}

static function check_operators($arg1, $arg2, $op){
	switch($op){
		case '=': return ($arg1==$arg2)? true: false; break;
		case '>': return ($arg1>$arg2)? true: false; break;
		case '<': return ($arg1<$arg2)? true: false; break;
		case '<>': return ($arg1!=$arg2)? true: false; break;
	}
	return false;
}
}




?>