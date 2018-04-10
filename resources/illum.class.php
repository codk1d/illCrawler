<?php
Class illum
{
	// Переменная с прокси-сервером
	private static $proxy = NULL;
	// Массив новых onion доменов
	private static $oninos = array();
	// Массив страниц домена
	private static $pages = array();
	// Текущее число просканированного
	private static $climit = 0;
	// Массив скопированных ссылок
	private static $tmpurls = array();
	// Массив новых доменов
	private static $tmponions = array();
	/*
		Назначение:	Функция инициализации класса
		Параметры:	Прокси, указатель на бд
	*/
	public static function init($string = "")
	{
		// Проверка входящих данных
		if (empty(self::$proxy = $string)) return false;
		// Положительный ответ
		return true;
	}
	/*
		Назначение:	Функция обновления контента страниц
		Параметры:	Массив данных страниц
	*/
	public static function upd_content($array = array())
	{
		// Проверяем входящие параметры
		if (!is_array($array) || !isset($array['domain']) || !is_array($array['pages']))
			return;
		// Проверяем валидность содержимого массива
		if (($domain = Guard::oniondomain($array['domain'])) == NULL || count($array['pages']) < 1)
			return;
		// Обнуляем все массивы для генерации новых под другой домен
		self::$tmponions = self::$tmpurls = self::$pages = array();
		// Обнуляем счетчик
		self::$climit = 0;
		// Прогоняем циклом все страници массива
		for ($i = 0; $i < 2; $i++)
			// Записываем данные в массив
			self::crawlersave(
				self::onioncontent($domain, $array['pages'][$i]['page']), $domain
			);
		// Возвращаем данные 
		return self::$pages;
	}
	/*
		Назначение:	Функция для инициализация кравлера
		Параметры:	Доменное имя сайта, лимит сканирования
	*/
	public static function crawler($domain = "", $limit = 0)
	{
		// Обнуляем все массивы для генерации новых под другой домен
		self::$tmponions = self::$tmpurls = self::$pages = array();
		// Обнуляем счетчики
		self::$climit = $len = 0;
		// Проверяем входящие параметры
		if (($domain = Guard::oniondomain($domain)) == NULL || $limit < 1) return;
		// Совершаем первое вхождение
		$array = self::onioncontent($domain);
		// Проверяем полученные данные
		if (count($array) == 0 || empty($array['content'])) return;
		// Сохраняем полученные данные и объявляем id
		self::crawlersave($array, $domain);
		// Начинаем прогонять полученные ссылки
		while (isset(self::$tmpurls[$len]))
		{
			// Проверяем условие выхода из цикла
			if (self::$climit >= $limit || $len > $limit * 3) break;
			// Получаем массив данных с новой страницы
			$array = self::onioncontent($domain, self::$tmpurls[$len]);
			// Если есть данные - сохраняем контент
			if (count($array) != 0 && !empty($array['content']))
				// Сохраняем содержимое в массив
				self::crawlersave($array, $domain);
			// Увеличиваем шаг
			$len++;
		} // Возвращаем отсканированные страницы и найденные домены
		return array("pages" => self::$pages, "domains" => self::$tmponions);
	}
	/*
		Назначение:	Сохранение данных для кравлера
		Параметры:	Массив данных
	*/
	private static function crawlersave($array = array(), $domain = "")
	{
		// Проверяем входящие параметры
		if (!is_array($array) || count($array) == 0 || empty($domain)) return;
		// Сохраняем данные
		if (!empty($array['content']) && !empty($array['page']) 
			&& $array['title'] != NULL && !empty(trim($array['title']))
		) // Записываем данные в массив страниц для внесения их в базу данных
		self::$pages[] = array("page" => Guard::escape($array['page']), "title" => Guard::escape($array['title']),
			"description" => Guard::clearcontent($array['description']), "meta" => Guard::escape($array['meta']),
			"jheaders" => Guard::escape($array['jheaders']), "tags" => Guard::escape($array['tags']),
			"content" => Guard::clearcontent($array['content'], true)
		); // Сохраняем все ссылки со страницы
		self::saveurls($array['content'], $domain);
		// Увеличиваем текущий шаг
		self::$climit++;
	}
	/*
		Назначение:	Находим все ссылки на странице
		Параметры:	Контент сайта
	*/
	private static function saveurls($content = "", $domain = "")
	{
		// Проверяем входящий параметр
		if (empty($content) || ($domain = Guard::oniondomain($domain)) == NULL) return;
		// Парсим все ссылки на странице
		preg_match_all('/href="(.+?)"/', $content, $arr1);
		preg_match_all('/href=\'(.+?)\'/', $content, $arr2);
		// Прогоняем циклом все ссылки страницы
		for ($i = 0; $i < count($urls = array_merge($arr1[1], $arr2[1])); $i++)
		// Производим проверку полученных ссылок отбрасывая повторения
		if (!self::inpage($urls[$i]) && !self::urlexcept($urls[$i]))
		{
			// Если полученная ссылка - onion домен
			if (Guard::oniondomain(str_replace($domain, "", $urls[$i])) != NULL)
				// Добавляем ссылку в список onion доменов
				self::$tmponions[] = Guard::oniondomain($urls[$i]);
			// Добавляем ссылку в список доменов на сканирование
			else if (!in_array($urls[$i], self::$tmpurls))
				// Сохраняем данные в временный массив
				self::$tmpurls[] = Guard::clearpage($urls[$i], $domain);
		}
	}
	/*
		Назначение:	Находим все ссылки на странице
		Параметры:	Контент сайта
	*/
	private static function urlexcept($url = "")
	{
		// Проверяем входящий параметр
		if (empty($url) || strpos($url, "#")) return true;
		// Проверка на главную страницу
		if ($url == "/index.php" || $url == "/") return true;
		// Массив исключенных расширений
		$array = array("jpg", "png", "ico", "exe", "pdf", "jpeg", 
			"mp4", "doc", "docx", "xls", "xlam", "css", "xml", "js"
		); // Получаем последние вхождение после точки
		$afterdot = explode(".", $url);
		// Удаляем GET запрос, если он есть
		$withoutget = explode("?", $afterdot[count($afterdot) - 1]);
		// Возвращаем ответ
		return in_array($withoutget[0], $array);
	}
	/*
		Назначение:	Получаем контент сайта
		Параметры:	Домен, страница
	*/
	public static function onioncontent($domain = "", $page = "")
	{
		// Проверяем параметры
		if (($domain = Guard::oniondomain($domain)) == NULL || empty(self::$proxy))
			return array();
		// Корректируем страницу
		if (empty($page)) $page = "/";
		// Если мы сканируем туже страницу
		else if ($page[0] == "#") return array();
		// Если нету слеша в конце страницы
		else if ($page[0] != "/") $page = "/{$page}";
		// Генерируем параметры для CURL
		$options = array(
			CURLOPT_RETURNTRANSFER => true, 
			CURLOPT_HEADER         => false, 
			CURLOPT_FOLLOWLOCATION => true, 
			CURLOPT_ENCODING       => "UTF-8",     
			CURLOPT_AUTOREFERER    => true,
			CURLOPT_PROXY 		   => self::$proxy,
			CURLOPT_PROXYTYPE	   => CURLPROXY_HTTP,
			CURLOPT_CONNECTTIMEOUT => 300,   
			CURLOPT_TIMEOUT        => 400,   
			CURLOPT_MAXREDIRS      => 10,    
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HEADER 		   => true,
			CURLOPT_HTTPHEADER	   => array(
				"User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0",
				"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
				"Accept-Language: en-US,en;q=0.5", "Accept-Encoding: gzip, deflate, br"
			)
		); // Инициализируем подключение
		$ch = curl_init("http://{$domain}{$page}");
		// Применяем параметры CURL
		curl_setopt_array($ch, $options);
		// Получаем текущую ссылку
		$currenturl = Guard::clearpage(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), $domain);
		// Получаем полный контент сайта
		$content = Guard::utf8_encode(curl_exec($ch));
		// Получаем вес страницы
		$size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		// Получаем заголовки сайта
		$headers = substr($content, 0, $size);
		// Получаем html контент сайта и чистим его
		$body = Guard::clearcontent(substr($content, $size), false);
		// Закрываем соединение CURL
		curl_close($ch);
		// Проверяем полученные параметры
		if (self::inpage($currenturl) || empty($body) || !self::statusok($headers)) return array();
		// Возвращаем массив данных
		return array(
			"title" => self::sitetitle($content), "page" => $currenturl, "meta" => self::metawords($content),
			"jheaders" => self::headers2json($headers), "description" => self::description($content),
			"tags" => ($tags = self::tags($content)), "content" => str_replace($tags, "", $body)
		);
	}
	/*
		Назначение:	Отбирает текст в тегах h1, h2...
		Параметры:	Контент
	*/
	private static function tags($body = "")
	{
		// Проверяем параметры
		if (empty($text = Guard::escape($body))) return array();
		// Массив с содержимым
		$array = array();
		// Прогоняем контент через регулярки
		preg_match_all(
			"/<h1.*>(.+?)<\/h1>|<h2.*>(.+?)<\/h2>|<h3.*>(.+?)<\/h3>|<p.*>(.+?)<\/p>|<pre.*>(.+?)<\/pre>/",
			$body, $out
		); // Прогоняем циклом выдачу и записываем результат в массив
		for ($i = 1; $i < count($out); $i++) for ($j = 0; $j < count($out[$i]); $j++)
			// Условие попадания массива
			if (!empty($out[$i][$j]) && strlen($out[$i][$j]) > 2)
				// Записываем данные в массив
				$array[] = Guard::escape(Guard::clearcontent($out[$i][$j], true));
		// Возвращаем массив данных
		return $array;
	}
	/*
		Назначение:	Получаем название сайта
		Параметры:	Контент
	*/
	private static function sitetitle($content = "")
	{
		// Проверяем параметры
		if (empty($content)) return NULL;
		// Парсим содержимое между тегов
		preg_match_all('/<title>(.+?)<\/title>/', $content, $out);
		// Проверяем полученный контент
		if (!isset($out[1][0]) || strlen($out[1][0]) < 3) return NULL;
		// Возвращаем ответ
		return (empty(trim($out[1][0]))) ? NULL 
			: Guard::escape(Guard::clearcontent($out[1][0], true));
	}
	/*
		Назначение:	Получаем примечание сайта
		Параметры:	Контент
	*/
	private static function description($content = "")
	{
		// Проверяем данные
		if (empty($content)) return NULL;
		// Прогоняем данные через регулярку - 1 раз
		preg_match_all("/<meta.+?=\".*description\".+?content=\"(.+?)\"/", $content, $out);
		// Проверка полученных данных
		if (!isset($out[1][0])) return NULL;
		// Переменная описания
		$desc = NULL;
		// Выбираем подходящий элемент
		for ($i = 0; $i < count($out[1]); $i++)
			// Условие присвоения
			if (strlen($desc) < strlen($out[1][$i])) $desc = $out[1][$i];
		// Возвращаем ответ
		return Guard::escape($desc);
	}
	/*
		Назначение:	Получаем ключевые слова сайта
		Параметры:	Контент
	*/
	private static function metawords($content = "")
	{
		// Проверяем данные
		if (empty($content)) return NULL;
		// Прогоняем данные через регулярку - 1 раз
		preg_match_all("/<meta.name=\"keywords\".content=\"(.+?)\"/", $content, $out);
		// Проверка полученных данных
		if (!isset($out[1][0]) || strlen($out[1][0]) < 3) return NULL;
		// Приводим данные к единому виду строки для разбивки на части
		$text = str_replace(",", " ", str_replace(", ", " ", $out[1][0]));
		// Проверяем выходные данные
		if (strlen($text) <= 3) return NULL;
		// Возвращаем ответ
		return Guard::escape($text);
	}
	/*
		Назначение:	Проверка текущего url на совпадение
		Параметры:	Страница
	*/
	private static function inpage($page = "")
	{
		// Проверяем входящий параметр
		if (empty($page)) return true;
		// Прогоняем циклом все страницы
		for ($i = 0; $i < count(self::$pages); $i++)
			// Если найдено совпадение
			if (self::$pages[$i]['page'] == $page) return true;
		// Возвращаем отрицательный ответ
		return false;
	}
	/*
		Назначение:	Проверка страници сайта на валидность
		Параметры:	Заголовки сайта
	*/
	private static function statusok($headers = "")
	{
		// Проверяем параметры
		if (empty($headers) || !strpos($headers, "\r\n"))
			return false; if (self::ctype($headers))
		// Возвращаем ответ
		return (strpos(explode("\r\n", $headers)[0], "200") && self::ctype($headers)) ? true : false;
	}
	/*
		Назначение:	Проверка MIME-типа страници
		Параметры:	Заголовки сайта
	*/
	private static function ctype($headers = "")
	{
		// Проверяем параметры
		if (empty($headers) || !strpos($headers, "\r\n"))
			return false;
		// Проверяем MIME Type
		if (strpos($headers, "text/html")) return true;
		if (strpos($headers, "text/php")) return true;
		// Возвращаем отрицательный ответ
		return false;
	}
	/*
		Назначение:	Переводим заголовки в json формат
		Параметры:	Заголовки
	*/
	private static function headers2json($headers = "")
	{
		// Проверяем параметры
		if (empty($headers)) return json_encode(array());
		// Массив с заголовками
		$array = array();
		// Дробим заголовки на части
		$arr = explode("\r\n", $headers);
		// Прогоняем части циклом
		for ($i = 0; $i < count($arr); $i++) if (!empty(strstr($arr[$i], ": ")))
		{
			// Дробим заголовок на части
			$inn = explode(": ", $arr[$i]);
			// Записываем параметры в массив
			$array[$inn[0]] = $inn[1];
		} // Возвращаем ответ в json
		return json_encode($array);
	}
}