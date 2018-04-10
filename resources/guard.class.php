<?php
Class Guard
{
	/*
		Назначение:	Очистка строки от спец-символов
		Параметры:	Строка
	*/
	public static function escape($string)
	{
		$string = str_replace(	"&#032;"			, " " 			, $string);
		$string = str_replace(	"<!--"				, "&#60;&#33;--", $string);
		$string = str_replace(	"-->"				, "--&#62;" 	, $string);
		$string = preg_replace(	"/<script/i"		, "&#60;script"	, $string);
		$string = str_replace(	">"					, "&gt;" 		, $string);
		$string = str_replace(	"<"					, "&lt;" 		, $string);
		$string = str_replace(	"\""				, "&quot;" 		, $string);
		$string = str_replace(	"\&quot;"			, "&quot;" 		, $string);
		$string = str_replace(	"\'"				, "&#39;" 		, $string);
		$string = preg_replace(	"/\n/"				, "<br />" 		, $string);
		$string = preg_replace(	"/\\\$/"			, "&#036;" 		, $string);
		$string = preg_replace(	"/\r/"				, "" 			, $string);
		$string = str_replace(	"!"					, "&#33;" 		, $string);
		$string = str_replace(	"'"					, "&#39;" 		, $string);
		$string = str_replace(	"<br />"			, "" 			, $string);
		$string = str_replace(	"<br >"				, "" 			, $string);
		$string = str_replace(	"<br>"				, "" 			, $string);
		$string = preg_replace(	"/&amp;#([0-9]+);/s", "&#\\1;" 		, $string);
		// Удаляем лишние слеши
		if(get_magic_quotes_runtime()) $string = stripslashes($string);
		// Возвращаем ответ
		return $string;
	}
	/*
		Назначение:	Выдергивает доменное имя
		Параметры:	Текст
	*/
	public static function oniondomain($domain = "")
	{
		// Проверяем параметр
		if (empty($domain)) return NULL;
		// Парсим доменное имя из текста
		preg_match_all('/([a-zA-Z0-9]+\.onion)/', $domain, $array);
		// Проверяем результат регулярного выражения
		if (!isset($array[1]) || empty($array[1][0]) || strlen($array[1][0]) < 11)
			return NULL;
		// Возвращаем ответ
		return $array[1][0];
	}
	/*
		Назначение функции: Является ли параметр типом int
		Входящие параметры: Переменная
	*/
	public static function isint($int)
	{
		// Преобразуем тип данных
		settype($int, "integer");
		// Проверяем тип данных
		return is_int($int);
	}
	/*
		Назначение:	Подсчитывает разницу между датами
		Параметры:	Сравниваемая дата
	*/
	public static function datedays($date = "01.01.2016")
	{
		// Проверяем параметры
		if (empty($date)) return 0;
		// Объявляем текущую дату
		$cdate = new DateTime(date("d.m.Y"));
		// Объявляем сравниваемую дату
		$rdate = new DateTime($date);
		// Возвращаем разницу
		return $cdate->diff($rdate)->format("%d");
	}
	/*
		Назначение:	Очистка контента от мусора
		Параметры:	Контент, пойдет ли запись в бд
	*/
	public static function clearcontent($content = "", $db = false)
	{
		// Проверяем параметры
		if (empty($content)) return NULL;
		// Удаление JavaScript из текста
		$content = preg_replace("/<script>(.+?)<\/script>/", "", $content);
		// Удаление CSS из текста
		$content = preg_replace("/<style>(.+?)<\/style>/", "", $content);
		// Удаление лишних пробелов, табов, переносов
		$content = preg_replace(array('/\s{2,}/', '/[\t\n\r]/'), " ", $content);
		// Если данные заносятся в базу - продолжаем чистку
		if (!$db) return $content;
		// Переводим контент в нижний регистр
		$content = mb_strtolower($content);
		// Очищаем контент от html тегов
		$content = preg_replace("/<[^>]*>/", "", $content);
		// Возвращаем данные
		return self::escape($content);
	}
	/*
		Назначение:	Очищаем url от лишних слешей
		Параметры:	Страница
	*/
	public static function clearpage($page = "", $domain = "")
	{
		// Проверяем параметры
		if (empty($page) || ($domain = Guard::oniondomain($domain)) == NULL) return "/";
		// Очищаем путь от доменя и слеша
		$npage = str_replace($domain, "", str_replace("./", "", $page));
		// Очищаем путь от названия протокола
		$npage = str_replace("https://", "", str_replace("http://", "", $npage));
		// Очищаем некорректные пути
		$npage = str_replace("amp;", "", $npage);
		// Возвращаем ответ
		return str_replace("//", "", $npage);
	}
	/*
		Назначение:	Кодирование текста в UTF-8
		Параметры:	Строка
	*/
	public static function utf8_encode($text = "")
	{
		return $text;
		// it doesn't work
		try  {
			// Преобразовываем текст в кодировку UTF-8
			return iconv(mb_detect_encoding($text, mb_detect_order(), true), "UTF-8", $text);
		// Обработчик ошибок
		} catch (Exception $e)
		{
			// Если возникла ошибка - возвращаем текст по умолчанию
			return $text;
		}
	}
}