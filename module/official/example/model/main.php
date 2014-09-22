<?php

global $m_example_main_m;
$m_example_main_m=new m_exapmle_main_m();
/**
 * Пример класса для направленных функций модуля (в перспективе "раздела").
 * */
class m_example_main_m{
	var $table=M_EXAMLE_TYPE;
	var $prefix=PREFIX;
	/**
	 * Пример файла возврата данных
	 *
	 * @return string
	 */
	function example(){
		$rez='Hello World';
		return $rez;
	}
}