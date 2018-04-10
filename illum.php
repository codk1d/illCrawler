<?php
// Подключаем все библиотеки
require_once __DIR__ . "/resources/illum.class.php";
require_once __DIR__ . "/resources/database.class.php";
require_once __DIR__ . "/resources/guard.class.php";
require_once __DIR__ . "/resources/main.class.php";
// Инициализируем подключени к бд
$db = new database("localhost", "torCrowler", "ivpy5YZBva6CAksn", "illum");
// Массив данных с доступными командами
$commands = array(
	// Функция первого сканирования сайта
	"--crawler" => "crawler",
	// Функция проверки новых доменов
	"--new-domains" => "new_domains",
	// Функция обновления содержимого
	"--updater" => "upd_pages",
	// Фукнция удаления мертвых сайтов
	"--remove-invalid" => "rm_invalid"
); // Проверяем подключение
if (!$db || !illum::init("localhost:3128"))
	exit("Error: Can not connect to database.\n");
// Проверяем входящие данные
if (!isset($argv[1]) || empty($argv[1]) || !isset($commands[$argv[1]]))
	exit("Error: Incorrect input data.\n");
// Записываем имя функции в переменную
$function = $commands[$argv[1]];
// Вызываем функцию
main::$function($db);