<?php
Class database
{
	// Указатель на подключение к бд
	private $mysql = false;
	// Массив встроенных функций
	public $illum = array
	(
		// Определить id домена
		0 => "domain_id",
		// Определить имя домена
		1 => "domain_name",
		// Массив не сканированных доменов
		2 => "nscanned_names",
		// Массив доменов для обновления
		3 => "fupdate_names",
		// Массив новых доменов
		4 => "new_names",
		// Сохранение массива данных сайта
		5 => "save_data",
		// Получение id содержимого страници
		6 => "page_id",
		// Обновление статуса домена
		7 => "scanned_upd",
		// Полное удаление сайта из базы
		8 => "remove_site"
	);
	/*
		Назначение:	Инициализация подключения
		Параметры:	Данные для покдлючения
	*/
	public function __construct($host = "", $user = "", $password = "", $db = "")
	{
		// Проверяем входящие данные
		if (empty($host) || empty($user) || empty($password) || empty($db))
			// Возвращаем отрицательный ответ
			return false;
		// Производим подключение
		if (!($this->mysql = mysqli_connect($host, $user, $password, $db)))
			// Возвращаем отрицательный ответ
			return false;
		// Положительный ответ
		return true;
	}
	/*
		Назначение:	Завершение работы класса
		Параметры:	Нет
	*/
	public function __destruct()
	{
		// Освобождаем указатель подключения
		mysqli_close($this->mysql);
	}
	/*
		Назначение:	Обычный запрос к базе данных
		Параметры:	SQL запрос
	*/
	public function query($sql = "")
	{
		// Проверяем входящие параметры
		if (empty($sql) || strlen($sql) < 10 || !$this->mysql) return false;
		// Выволняем запрос к базе и выполняем проверку
		//if (!mysqli_query($this->mysql, $sql)) return false;
		mysqli_query($this->mysql, $sql) or die($sql . mysqli_error($this->mysql));
		// Возвращаем положительный ответ
		return true;
	}
	/*
		Назначение:	Получение массива данных
		Параметры:	SQL запрос
	*/
	public function fetch_assoc($sql = "")
	{
		// Проверяем входящие параметры
		if (empty($sql) || strlen($sql) < 10 || !$this->mysql) return array();
		// Массив данных 
		$array = array();
		// Выполняем запрос
		$q = mysqli_query($this->mysql, $sql);
		// Записываем данные в массив
		while ($rows = mysqli_fetch_assoc($q)) $array[] = $rows;
		// Возвращаем ответ
		return $array;
	}
	/*
		Назначение:	Количество данных в базе
		Параметры:	SQL запрос
	*/
	public function count($sql = "")
	{
		// Проверяем входящие параметры
		if (empty($sql) || strlen($sql) < 10 || !$this->mysql) return array();
		// Выполняем запрос
		$q = mysqli_query($this->mysql, $sql);
		// Возвращаем ответ
		return mysqli_num_rows($q);
	}
	/*
		Назначение:	Использование спец-функций illum базы
		Параметры:	Строка
	*/
	public function illum_db($mixed, $type = -1)
	{
		// Проверяем входящие параметры на вилидность
		if (!isset($this->illum[$type])) return NULL;
		// Генерируем имя функции
		$function = "illum_" . $this->illum[$type];
		// Возвращаем ответ вызовом функции
		return $this->$function($mixed);
	}
	/*
		Назначение:	Функция получения id домена
		Параметры:	Строка
	*/
	private function illum_domain_id($string = "")
	{
		// Проверяем входящие данные на валидность
		if (($domain = Guard::oniondomain($string)) == NULL || !$this->mysql) return -1;
		// Выполняем запрос к базе и получаем массив
		$arr = $this->fetch_assoc("SELECT `id` FROM `domains` WHERE `name`='{$domain}'");
		// Производим проверку элементов
		if (count($arr) != 1) return -1;
		// Возвращамем данные
		return $arr[0]['id'];
	}
	/*
		Назначение:	Функция получения id домена
		Параметры:	Строка
	*/
	private function illum_domain_name($number = -1)
	{
		// Проверяем входящие данные на валидность
		if (!Guard::isint($number) || !$this->mysql || $number < 0) return NULL;
		// Выполняем запрос к базе и получаем массив
		$arr = $this->fetch_assoc("SELECT `name` FROM `domains` WHERE `id`='{$number}'");
		// Производим проверку элементов
		if (count($arr) != 1) return NULL;
		// Возвращамем данные
		return $arr[0]['name'];
	}
	/*
		Назначение:	Функция получения массива не сканнированных имен
		Параметры:	Нет
	*/
	private function illum_nscanned_names($string = "")
	{
		// Проверка подключения
		if (!$this->mysql) return array();
		// Создаем массив данных
		$array = $this->fetch_assoc(
			"SELECT DISTINCT(`name`), `id` FROM `domains` WHERE `scanned`='false'"
		); // Возвращаем ответ
		return $array;
	}
	/*
		Назначение:	Функция получения массива имен для обновления
		Параметры:	Нет
	*/
	private function illum_fupdate_names($string = "")
	{
		// Проверка подключения
		if (!$this->mysql) return array();
		// Массив данных для ответа
		$return = array();
		// Создаем массив данных
		$array = $this->fetch_assoc("SELECT DISTINCT(`domain_id`), `date` FROM `content`");
		// Прогоняем данные циклом и проверяем их условием
		for ($i = 0; $i < count($array); $i++) if (Guard::datedays($array[$i]['date']) > 2)
			// Записываем данные в массив
			$return[] = $array[$i]['domain_id'];
		// Возвращаем ответ
		return $return;
	}
	/*
		Назначение:	Функция получения массива новых
		Параметры:	Нет
	*/
	private function illum_new_names($string = "")
	{
		// Проверка подключения
		if (!$this->mysql) return array();
		// Возвращаем ответ
		return $this->fetch_assoc("SELECT DISTINCT(`name`), `id` FROM `ndomains`");
	}
	/*
		Назначение:	Сохранение данных от crawler'а
		Параметры:	Массив данных
	*/
	private function illum_save_data($array = array())
	{
		echo "\n=============> Save <=============";
		// Проверяем входящие данные и элементы данных
		if (!is_array($array) || !isset($array['id']) || !isset($array['data']))
			return;
		// Прогоняем все новые домены циклом
		for ($i = 0; $i < count($array['data']['domains']); $i++)
			// Сохраняем данные в базу
			$this->illum_tdomain_save($array['data']['domains'][$i]);
		// Прогоняем все страници сайта циклом
		for ($i = 0; $i < count($array['data']['pages']); $i++)
			// Сохраняем данные страници в базу
			$this->illum_savepage($array['data']['pages'][$i], $array['id']);
	}
	/*
		Назначение:	Сохранение нового домена
		Параметры:	Доменное имя
	*/
	private function illum_tdomain_save($domain = "")
	{
		// Проверка подключения и валидности домена
		if (!$this->mysql || ($domain = Guard::oniondomain($domain)) == NULL)
			return;
		// Проверяем существование домена в базе данных
		if ($this->illum_domain_id($domain) != -1) return;
		// Заносим доменное имя в базу данных
		$this->query("INSERT INTO `ndomains` VALUES (NULL, '{$domain}')");
	}
	/*
		Назначение:	Сохранение новой страници
		Параметры:	Массив данных, id домена
	*/
	private function illum_savepage($array = array(), $id = -1)
	{
		print_r($array);
		// Проверяем входящие данные и приобразовываем тип данных id
		if (!is_array($array) || !Guard::isint($id) || $id < 0) return;
		// Содержимое тегов сайта и текущая дата
		$tegsline = NULL; $date = date("d.m.Y");
		// Проверяем существование домена по id
		if (empty($this->illum_domain_name($id))) return;
		// Проверяем существование страници в базе данных
		if ($this->count(
			"SELECT `date` FROM `content` WHERE `domain_id`='{$id}' AND `page`='{$array['page']}'"
		) != 0) return;
		// Преобразовываем слова тегов в строку
		for ($i = 0; $i < count($array['tags']); $i++)
			$tegsline .= "{$array['tags'][$i]} ";
		// Заполняем таблицу содержимого страници
		$this->query("INSERT INTO `content` VALUES (
			NULL, '{$id}', '{$array['page']}', '{$array['title']}', '{$array['content']}',
			'{$array['meta']}', '{$array['description']}', '{$tegsline}', '{$date}'
		)"); // Получаем id содержимого для заполнения заголовков
		$c_id = $this->illum_page_id(array("id" => $id, "page" => $array['page']));
		// Выполняем проверку наличия заголовков в базе данных
		if ($c_id >= 0 && $this->count("SELECT * FROM `headers` WHERE `content_id`='{$c_id}'") == 0)
			// Заполняем траблицу заголовков страници
			$this->query(
				"INSERT INTO `headers` VALUES (NULL, '{$c_id}', '{$array['jheaders']}')"
			);
	}
	/*
		Назначение:	Получение id содержимого страници
		Параметры:	Массив данных
	*/
	private function illum_page_id($array = array())
	{
		// Проверка входящих данных
		if (!is_array($array) || !isset($array['id']) || !isset($array['page']))
			return -1;
		// Фильтрация и проверка содержимого массива
		if (!Guard::isint($array['id']) || empty($page = Guard::escape($array['page'])))
			return -1;
		// Получаем массив данных запроса к бд
		$data = $this->fetch_assoc(
			"SELECT `id` FROM `content` WHERE `domain_id`='{$array['id']}' AND `page`='{$page}'"
		); // Проверяем количество элементов массива
		if (count($data) != 1) return -1;
		// Возвращаем ответ
		return $data[0]['id'];
	}
	/*
		Назначение: Обновление статуса доменного имени
		Параметры:	Доменное имя
	*/
	private function illum_scanned_upd($string = "")
	{
		// Проверка входящих параметров
		if (($domain = Guard::oniondomain($string)) == NULL) return false;
		// Проверка существование доменного имени в базе
		if ($this->illum_domain_id($domain) < 0) return false;
		// Обновление статуса домена
		$this->query("UPDATE `domains` SET `scanned`='true' WHERE `name`='{$domain}'");
		// Возвращаем ответ
		return true;
	}
	/*
		Назначение: Полное удаление сайта из базы данных
		Параметры:	Доменное имя
	*/
	private function illum_remove_site($string = "")
	{
		// Проверка входящих параметров
		if (($domain = Guard::oniondomain($string)) == NULL) return;
		// Получаем id доменного имени
		if (($id = $this->illum_domain_id($domain)) == -1) return;
		// Массив данных страниц сайта
		$pages = $this->fetch_assoc("SELECT `id` FROM `domains` WHERE `domain_id`='{$id}'");
		// Прогоняем все страници циклом
		for ($i = 0; $i < count($pages); $i++)
			// Удаляем заголовки по id страници сайта
			$this->query("DELETE FROM `headers` WHERE `content_id`='{$pages[$i]['id']}'");
		// Удаляем все страници из базы связанные с доменом
		$this->query("DELETE FROM `content` WHERE `domain_id`='{$id}'");
		// Удаляем доменное имя из базы
		$this->query("DELETE FROM `domains` WHERE `id`='{$id}'");
	}
}