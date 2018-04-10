<?php
Class main
{
	// Количество страниц для сканирования
	private static $pages = 200;
	/*
		Назначение:	Функция для запуска кравлера
		Параметры:	Указатель на класс бд
	*/
	public static function crawler($db = false)
	{
		// Проверяем входящие данные
		if (!$db) exit("Error: Can not connect to database.\n");
		// Массив данных для записи
		$array = array();
		// Получаем список не сканированных доменов
		$domains = $db->illum_db(NULL, 2);
		// Прогоняем доменые через массив
		for ($i = 0; $i < count($domains); $i++)
		{
			// Определяем id доменного имени
			$domain_id = $db->illum_db($domains[$i]['name'], 0);
			// Запускаем сканирование сайта
			$array = illum::crawler($domains[$i]['name'], self::$pages);
			// Сохраняем данные в базу
			$db->illum_db(array("id" => $domain_id, "data" => $array), 5);
			// Обновляем статус доменного имени после сканирования
			$db->illum_db($domains[$i]['name'], 7);
		}
	}
	/*
		Назначение:	Функция проверки новых доменов
		Параметры:	Указатель на класс бд
	*/
	public static function new_domains($db = false)
	{
		// Проверяем входящие данные
		if (!$db) exit("Error: Can not connect to database.\n");
		// Получаем список доменов на проверку
		$domains = $db->illum_db(NULL, 4);
		// Прогоняем список циклом
		for ($i = 0; $i < count($domains); $i++)
			// Проверяем доступность доменного имени
			if (($domain = Guard::oniondomain($domains[$i]['name'])) != NULL &&
				count(illum::onioncontent($domain)) >= 6 && $db->illum_db($domain, 0) == -1
			) { // Заносим новый домен в базу данных со статусом - отсканировать
				$db->query("INSERT INTO `domains` VALUES (NULL, '{$domain}', 'false')");
				// Удаляем домен из списка временных доменов
				$db->query("DELETE FROM `ndomains` WHERE `name`='{$domains[$i]['name']}'");
			// Если домен некорректный - удаляем его из базы данных
			} else $db->query("DELETE FROM `ndomains` WHERE `name`='{$domains[$i]['name']}'");
	}
	/*
		Назначение:	Обновляет контент всех отсканированных сайтов
		Параметры:	Указатель на класс бд
	*/
	public static function upd_pages($db = false)
	{
		// Проверяем входящие данные
		if (!$db) exit("Error: Can not connect to database.\n");
		// Получаем массив id доменов для обновления
		$array = $db->illum_db(NULL, 3);
		// Массив данных страниц
		$upd = $pages = array(); $domain = NULL;
		// Прогоняем массив данных циклом
		for ($i = 0; $i < count($array); $i++)
		{
			// Обнуляем массив контента
			$upd = array();
			// Извлекаем доменное имя сайта
			if (($domain = $db->illum_db($array[$i], 1)) == -1) continue;
			// Извлекаем все страницы из базы
			$pages = $db->fetch_assoc("SELECT `id`, `page` FROM `content` WHERE `domain_id`='{$array[$i]}'");
			// Обновляем контент и записываем в массив
			$upd[] = illum::upd_content(array("domain" => $domain, "pages" => $pages));
		}
	}
	/*
		Назначение:	Удаляет мертвые сайты/домены
		Параметры:	Указатель на класс бд
	*/
	public static function rm_invalid($db = false)
	{
		// Проверяем входящие данные
		if (!$db) exit("Error: Can not connect to database.\n");
		// Получаем список всех отсканированных сайтов
		$domains = $db->query("SELECT `name` FROM `domains` WHERE `scanned`='true'");
		// Прогоняем циклом все доменные имена
		for ($i = 0; $i < count($domains); $i++)
			// Проверяем доступность доменного имени
			if (count(illum::onioncontent($domain[$i]['name'])) == 0)
				// Удаляем домен из базы
				$db->illum_db($domain[$i]['name'], 8);
	}
}