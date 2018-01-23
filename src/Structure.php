<?php
/**
 * This file is part of the RouterDb
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/pllano/router-db
 * @version 1.0.1
 * @package pllano/router-db
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace routerDb;
 
class Structure {
 
    /**
     * @param $config
     * @var array
    */
    private $config;
 
    public function __construct(array $config = array())
    {
        if (count($config) >= 1){
            // Устанавливаем конфигурацию
            $this->config = $config;
        }
    }
 
    public function start()
    {
        $uri_db = "db.json";
        if (file_exists($uri_db)) {
            // Загрузить файл db.json
            $db = json_decode(file_get_contents($uri_db), true);
 
            if (count($db) >= 1) {
                // Подключаетесь к базе
                $link = mysqli_connect($host, $user, $password, $database) or die("Ошибка " . mysqli_error($link));
                if (!$link) {exit;}
    
                foreach($db as $table)
                {
                    // Если существует колонка table
                    if (isset($table["table"])) {
                        if (count($table["schema"]) >= 1 && $table["action"] == "create") {
                            $row = ""; 
                            foreach($table["schema"] as $key => $value)
                            {
                                if (isset($key) && isset($value)) {
                                    if ($key != "id" && preg_match("[a-z0-9_]", $key)) {
                                        if ($value == "boolean" || $value == "string" || $value == "integer" || $value == "double") {
                                            // Конвертируем тип
                                            $value = str_replace("boolean", "CHAR( 5 ) NOT NULL DEFAULT ''", $value);
                                            $value = str_replace("text", "TEXT NOT NULL DEFAULT ''", $value);
                                            $value = str_replace("datetime", "DATETIME NOT NULL", $value);
                                            $value = str_replace("string", "VARCHAR( 255 ) NOT NULL DEFAULT ''", $value);
                                            $value = str_replace("integer", "INT( 11 ) NOT NULL DEFAULT '0'", $value);
                                            $value = str_replace("double", "FLOAT( 11, 2 ) NOT NULL DEFAULT '0.00'", $value);
                                            $row .= ", ".$key." ".$value;
                                        } else {
                                            echo "название поля или тип данных не определены";
                                        }
                                    } else {
                                      echo $key." не прошел проверку preg_match [a-z0-9_]";
                                    }
                                } else {
                                    echo "value у ".$key." должен иметь один из типов: boolean, string, integer, double";
                                }
                            }
                    
                            if (!mysql_query("SELECT * FROM `".$table["table"]."`")){
                                // Создаем таблицу
                                $query ="CREATE TABLE IF NOT EXISTS ".$table["table"]."(
                                    id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY".$row."
                                    )";
                            } else {
                            // Обновляем существующую таблицу
                            $query ="ALTER TABLE ".$table["table"]." 
                                CHANGE id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY".$row;
                            }
                            // Отправляем запрос
                            mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));
                    
                        } else {
                            echo "У ".$table["table"]." отсутствует schema или action != create";
                        }
                    } else {
                        echo "Название одной из таблиц не определено";
                    }
                }
                // Закрываем соединение с БД
                mysqli_close($link);
                echo "Создание таблиц прошло успешно";
                return true;
            } else {
                echo "Таблицы в файле db.json не найдены";
                return false;
            }
        } else {
            echo "По указанному пути ".$uri_db." файл не найден";
            return false;
        }
    }
 
}
 