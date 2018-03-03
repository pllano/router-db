<?php /**
    * This file is part of the RouterDb
    *
    * @license http://opensource.org/licenses/MIT
    * @link https://github.com/pllano/router-db
    * @version 1.2.0
    * @package pllano/router-db
    *
    * For the full copyright and license information, please view the LICENSE
    * file that was distributed with this source code.
*/

namespace Pllano\RouterDb;

// Здесь собраны основные полезные функции
class Utility {
    
    public function clean_json($json) {
        for ($i = 0; $i <= 31; ++$i) {
            $json = str_replace(chr($i), "", $json);
        }
        $json = str_replace(chr(127), "", $json);
        if (0 === strpos(bin2hex($json), "efbbbf")) {
            $json = substr($json, 3);
        }
        
        return $json;
    }
    
    // Функция для проверки длинны строки
    public function check_length($value = "", $min, $max) {
        $result = (mb_strlen($value) < $min || mb_strlen($value) > $max);
        return !$result;
    }
    
    public function utf8_urldecode($str) {
        $str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
        return html_entity_decode($str,null,'UTF-8');
    }
    
    // Функция клинер. Усиленная замена htmlspecialchars
    public function clean($value = "") {
        // Убираем пробелы вначале и в конце
        $value = trim($value);
        // Убираем слеши, если надо
        // Удаляет экранирование символов
        $value = stripslashes($value);
        // Удаляет HTML и PHP-теги из строки
        $value = strip_tags($value);
        // Заменяем служебные символы HTML на эквиваленты
        // Преобразует специальные символы в HTML-сущности
        $value = htmlspecialchars($value, ENT_QUOTES);
        
        return $value;
        
    }
    
    public function search_injections(string $value = null, array $add_keywords = [], array $new_keywords = []): int
    {
        $list_keywords = [];
        if (isset($value)) {
            if (isset($new_keywords)) {
                $list_keywords = $new_keywords;
                } else {
                $plus_keywords = [];
                if (isset($add_keywords)) {
                    $plus_keywords = $add_keywords;
                }
                $list_keywords = [
                '*', 
				'`',
				'(',
				')',
                'SELECT', 
                'UPDATE', 
                'DELETE', 
                'INSERT', 
                'INTO', 
                'VALUES', 
                'FROM', 
                'LEFT', 
                'JOIN', 
                'WHERE', 
                'LIMIT', 
                'ORDER BY', 
                'AND', 
                'OR ',
                'DESC', 
                'ASC', 
                'ON',
                'LOAD_FILE', 
                'GROUP',
                'BY',
                'foreach',
                'echo',
                'script',
                'javascript',
                'public',
                'function',
                'admin',
                'root',
                'push',
                '"false"',
                '"true"',
                'return',
                'onclick'
                ];
                $keywords = array_replace_recursive($list_keywords, $plus_keywords);
            }
            $value = str_ireplace($keywords, "👌", $value, $i);
            return $i;
            } else {
            return 0;
        }
    }
    
    public function search_injection($value = '')
    { 
        if($value == '') {return null;}
        //$value = preg_replace("/['\"]([^'\"]*)['\"]/i", "'<FONT ID='injection' COLOR='#FF6600'>$1</FONT>'", $value, -1);
        $value = str_ireplace([
        '*', 
        'SELECT ', 
        'UPDATE ', 
        'DELETE ', 
        'INSERT ', 
        'INTO', 
        'VALUES', 
        'FROM', 
        'LEFT', 
        'JOIN', 
        'WHERE', 
        'LIMIT', 
        'ORDER BY', 
        'AND', 
        'OR ',
        'DESC', 
        'ASC', 
        'ON ' 
        ], [
        "<FONT ID='injection' COLOR='#FF6600'><B>*</B></FONT>",
        "<FONT ID='injection' COLOR='#00AA00'><B>SELECT</B></FONT>",
        "<FONT ID='injection' COLOR='#00AA00'><B>UPDATE</B></FONT>",
        "<FONT ID='injection' COLOR='#00AA00'><B>DELETE</B></FONT>",
        "<FONT ID='injection' COLOR='#00AA00'><B>INSERT</B></FONT>",
        "<FONT ID='injection' COLOR='#00AA00'><B>INTO</B></FONT>",
        "<FONT ID='injection' COLOR='#00AA00'><B>VALUES</B></FONT>",
        "<FONT ID='injection' COLOR='#00AA00'><B>FROM</B></FONT>",
        "<FONT ID='injection' COLOR='#00CC00'><B>LEFT</B></FONT>",
        "<FONT ID='injection' COLOR='#00CC00'><B>JOIN</B></FONT>",
        "<FONT ID='injection' COLOR='#00AA00'><B>WHERE</B></FONT>",
        "<FONT ID='injection' COLOR='#AA0000'><B>LIMIT</B></FONT>",
        "<FONT ID='injection' COLOR='#00AA00'><B>ORDER BY</B></FONT>",
        "<FONT ID='injection' COLOR='#0000AA'><B>AND</B></FONT>",
        "<FONT ID='injection' COLOR='#0000AA'><B>OR</B></FONT>",
        "<FONT ID='injection' COLOR='#0000AA'><B>DESC</B></FONT>",
        "<FONT ID='injection' COLOR='#0000AA'><B>ASC</B></FONT>",
        "<FONT ID='injection' COLOR='#00DD00'><B>ON</B></FONT>"
        ], $value, $i);
        return $i;
    }
    
    // Функция клинер. Усиленная замена htmlspecialchars
    public function clean_injection($value = "") {
        $value = str_ireplace([
        "INSERT", 
        "UPDATE", 
        "SELECT * FROM",
        "SELECT",
        "FROM",
        "LOAD_FILE", 
        "GROUP BY",
        "WHERE",
        "foreach",
        "echo",
        "script",
        "javascript",
        "public function",
        "function",
        "secret",
        "admin",
        "root",
        "password",
        "push",
        "false",
        "return",
        "onclick"
        ], "👌", $value);
        // Убираем пробелы вначале и в конце
        $value = trim($value);
        // Убираем слеши, если надо
        // Удаляет экранирование символов
        $value = stripslashes($value);
        // Удаляет HTML и PHP-теги из строки
        $value = strip_tags($value);
        // Заменяем служебные символы HTML на эквиваленты
        // Преобразует специальные символы в HTML-сущности
        $value = htmlspecialchars($value, ENT_QUOTES);
        return $value;
    }
    
    public function phone_clean($value = "") {
        
        // Убираем пробелы вначале и в конце
        $value = trim($value);
        // чистим всякие украшательства в номере телефона
        // в результате должны получить просто числовое значение номера
        $value = str_replace("+", "", $value);
        $value = str_replace("(", "", $value);
        $value = str_replace(")", "", $value);
        $value = str_replace("-", "", $value);
        $value = str_replace(" ", "", $value);
        // Убираем слеши, если надо
        // Удаляет экранирование символов
        $value = stripslashes($value);
        // Удаляет HTML и PHP-теги из строки
        $value = strip_tags($value);
        
        return $value;
    }
    
    // Функция очистки для xml
    public function clean_xml($value = "") {
        
        $value = str_replace("&", "&amp;", $value);
        $value = str_replace("<", "&lt;", $value);
        $value = str_replace(">", "&gt;", $value);
        $value = str_replace("{", "&#123;", $value);
        $value = str_replace("}", "&#125;", $value);
        $value = str_replace('"', '&quot;', $value);
        $value = str_replace("'", "&apos;", $value);
        // Убираем пробелы вначале и в конце
        $value = trim($value);
        // Убираем слеши, если надо
        // Удаляет экранирование символов
        $value = stripslashes($value);
        // Удаляет HTML и PHP-теги из строки
        $value = strip_tags($value);
        // Заменяем служебные символы HTML на эквиваленты
        // Преобразует специальные символы в HTML-сущности
        $value = htmlspecialchars($value, ENT_QUOTES);
        
        return $value;
        
    }
    
    // Функция генерации токена длиной 64 символа
    public function random_token($length = 32)
    {
        if(!isset($length) || intval($length) <= 8 ){
            $length = 32;
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
    }
    
    // Функция генерации короткого токена длиной 12 символов
    public function random_alias_id($length = 6)
    {
        if(!isset($length) || intval($length) <= 5 ){
            $length = 6;
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
    }
    
    // Функция генерации алиаса
    public function get_new_alias($str, $charset = 'UTF-8')
    {
        $str = mb_strtolower($str, $charset);
        $glyph_array = array(
        'a' => 'а',
        'b' => 'б',
        'v' => 'в',
        'g' => 'г,ґ',
        'd' => 'д',
        'e' => 'е,є,э',
        'jo' => 'ё',
        'zh' => 'ж',
        'z' => 'з',
        'i' => 'и,і',
        'ji' => 'ї',
        'j' => 'й',
        'k' => 'к',
        'l' => 'л',
        'm' => 'м',
        'n' => 'н',
        'o' => 'о',
        'p' => 'п',
        'r' => 'р',
        's' => 'с',
        't' => 'т',
        'u' => 'у',
        'f' => 'ф',
        'kh' => 'х',
        'ts' => 'ц',
        'ch' => 'ч',
        'sh' => 'ш',
        'shh' => 'щ',
        '' => 'ъ',
        'y' => 'ы',
        '' => 'ь',
        'ju' => 'ю',
        'ja' => 'я',
        '-' => ' ,_',
        'x' => '*'
        );
        
        foreach ($glyph_array as $letter => $glyphs)
        {
            $glyphs = explode(',', $glyphs);
            $str = str_replace($glyphs, $letter, $str);
        }
        
        $str = preg_replace('/[^A-Za-z0-9-]+/', '', $str);
        $str = preg_replace('/\s[\s]+/', '-', $str);
        $str = preg_replace('/_[_]+/', '-', $str);
        $str = preg_replace('/-[-]+/', '-', $str);
        $str = preg_replace('/[\s\W]+/', '-', $str);
        $str = preg_replace('/^[\-]+/', '', $str);
        $str = preg_replace('/[\-]+$/', '', $str);
        // Если нужно что бы url и алиасе вместо черточек были нижние подчеркивания
        //$str = preg_replace('/[^A-Za-z0-9-]+/', '', $str);
        //$str = preg_replace('/\s[\s]+/', '_', $str);
        //$str = preg_replace('/_[_]+/', '_', $str);
        //$str = preg_replace('/-[-]+/', '_', $str);
        //$str = preg_replace('/[\s\W]+/', '_', $str);
        //$str = preg_replace('/^[\-]+/', '', $str);
        //$str = preg_replace('/[\-]+$/', '', $str);
        return $str;
    }
    
    // Функция генерации алиаса
    public function get_alias($str, $charset = 'UTF-8')
    {
        $str = mb_strtolower($str, $charset);
        $glyph_array = array(
        'a' => 'а',
        'b' => 'б',
        'v' => 'в',
        'g' => 'г,ґ',
        'd' => 'д',
        'e' => 'е,є,э',
        'jo' => 'ё',
        'zh' => 'ж',
        'z' => 'з',
        'i' => 'и,і',
        'ji' => 'ї',
        'j' => 'й',
        'k' => 'к',
        'l' => 'л',
        'm' => 'м',
        'n' => 'н',
        'o' => 'о',
        'p' => 'п',
        'r' => 'р',
        's' => 'с',
        't' => 'т',
        'u' => 'у',
        'f' => 'ф',
        'kh' => 'х',
        'ts' => 'ц',
        'ch' => 'ч',
        'sh' => 'ш',
        'shh' => 'щ',
        '' => 'ъ',
        'y' => 'ы',
        '' => 'ь',
        'ju' => 'ю',
        'ja' => 'я',
        '-' => ' ,_',
        'x' => '*'
        );
        
        foreach ($glyph_array as $letter => $glyphs)
        {
            $glyphs = explode(',', $glyphs);
            $str = str_replace($glyphs, $letter, $str);
        }
        
        $str = preg_replace('/[^A-Za-z0-9-]+/', '', $str);
        $str = preg_replace('/\s[\s]+/', '-', $str);
        $str = preg_replace('/_[_]+/', '-', $str);
        $str = preg_replace('/-[-]+/', '-', $str);
        $str = preg_replace('/[\s\W]+/', '-', $str);
        $str = preg_replace('/^[\-]+/', '', $str);
        $str = preg_replace('/[\-]+$/', '', $str);
        
        return $str;
    }
    
    public function is_url($url) {
        return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
    }
    
    public function parse_url_if_valid($url)
    {
        // Массив с компонентами URL, сгенерированный функцией parse_url()
        $arUrl = parse_url($url);
        
        // Возвращаемое значение. По умолчанию будет считать наш URL некорректным.
        $ret = null;
        
        // Если не был указан протокол, или
        // указанный протокол некорректен для url
        if (!array_key_exists("scheme", $arUrl)
        || !in_array($arUrl["scheme"], array("http", "https")))
        
        // Задаем протокол по умолчанию - http
        $arUrl["scheme"] = "http";
        
        // Если функция parse_url смогла определить host
        if (array_key_exists("host", $arUrl) &&
        !empty($arUrl["host"]))
        
        // Собираем конечное значение url
        $ret = sprintf("%s://%s%s", $arUrl["scheme"],
        $arUrl["host"], $arUrl["path"]);
        
        // Если значение хоста не определено
        // (обычно так бывает, если не указан протокол),
        // Проверяем $arUrl["path"] на соответствие шаблона URL.
        else if (preg_match("/^\w+\.[\w\.]+(\/.*)?$/", $arUrl["path"]))
        
        // Собираем URL
        $ret = sprintf("%s://%s", $arUrl["scheme"], $arUrl["path"]);
        
        return $ret;
    }
    
    public function clean_number($value = "")
    {
        $value = preg_replace("/[^0-9]/", "", $value);
        return $value;
    }
    
    /**
        * Функция склонения слов
        *
        * @param mixed $digit
        * @param mixed $expr
        * @param bool $onlyword
        * @return
    */
    public function declension($digit,$expr,$onlyword=false)
    {
        if(!is_array($expr)) $expr = array_filter(explode(' ', $expr));
        if(empty($expr[2])) $expr[2]=$expr[1];
        $i=preg_replace('/[^0-9]+/s','',$digit)%100;
        if($onlyword) $digit='';
        if($i>=5 && $i<=20) $res=$digit.' '.$expr[2];
        else {
            $i%=10;
            if($i==1) $res=$digit.' '.$expr[0];
            elseif($i>=2 && $i<=4) $res=$digit.' '.$expr[1];
            else $res=$digit.' '.$expr[2];
        }
        
        return trim($res);
    }
    
}
