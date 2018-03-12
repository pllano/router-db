<?php /**
 * RouterDb (https://pllano.com)
 *
 * @link https://github.com/pllano/router-db
 * @version 1.2.0
 * @copyright Copyright (c) 2017-2018 PLLANO
 * @license http://opensource.org/licenses/MIT (MIT License)
 */
namespace Pllano\RouterDb\Drivers;

use Pllano\Interfaces\DatabaseInterface;
use jsonDB\{Db, Database, Validate, dbException};

class JsonPdo implements DatabaseInterface
{
    protected $resource = null;
    protected $dir = null;
    protected $temp = null;
    protected $api = null;
    protected $crypt = null;
    protected $config = [];
	protected $options = [];
	protected $prefix = null;
	protected $other_base = null;
	protected $response = [];
	
    public function __construct(array $config = [], array $options = [], string $format = null, string $prefix = null, $other_base = null)
    {
        $db = [];
		if (isset($config)) {
            if (isset($other_base)) {
                $this->other_base = $other_base;
                $db = $config['db'][$other_base];
				} elseif (isset($prefix)) {
				$this->config = $prefix;
                $db = $config['db']['json_'.$prefix];
				} else {
                $db = $config['db']['json'];
			}
		}
		$this->config = $db;
        if (isset($format)) {
            $this->format = strtolower($format);
        }
		return new Database();
	}

    public function ping(string $resource = null)
    {
        $return = "json";
		if (isset($resource)) {
            try {
			    Validate::table($resource)->exists();
			} catch(dbException $e){
                $return = null;
			}
		}
		return $return;
	}

    public function post(string $resource = null, array $query = [], string $field_id = null): int
    {
        $resp = null;
		if (isset($resource)) {
            // Проверяем наличие главной базы если нет даем ошибку
            try {
                Validate::table($resource)->exists();
                // Получаем параметры ресурса
                $table_config = json_decode(file_get_contents($this->config["dir"].'/'.$resource.'.config.json'), true);
                // Подключаем таблицу
                $row = Database::table($resource);
                // Разбираем параметры полученные в теле запроса
                foreach($query as $key => $value)
				{
					if (isset($key) && isset($value)) {
                        if ($key != "id") {
                            if (array_key_exists($key, $table_config["schema"])) {
                                if ($table_config["schema"][$key] == "integer") {
                                    if (is_numeric($value)) {
                                        $value = intval($value);
									} else {
                                        $value = 0;
									}
								}
                                if ($table_config["schema"][$key] == "double") {
                                    if (is_float($value * 1)) {
                                        //$value = floatval($value);
                                        $value = (float)$value;
									} else {
                                        $value = (float)$value;
									}
								}
                                if ($table_config["schema"][$key] == "boolean") {
                                    if (is_bool($value)) {
                                        $value = boolval($value);
									} else {
                                        $value = false;
									}
								}
                                if ($table_config["schema"][$key] == "string") {
                                    if (is_string($value)) {
                                        $value = strval($value);
									} else {
                                        $value = null;
									}
								} 
                                try {
                                    $row->{$key} = $value;
								} catch(dbException $error){
                                    //echo $error;
								}
							}
						}
					}
				}
                // Сохраняем
                $row->save();
                if ($row->id >= 1) {
                    // Добавляем вротой id
                    $update = Database::table($resource)->find($row->id);
                    $update->{$resource."_id"} = $row->id;
                    $update->save();
                    $resp = $row->id;
				} 
			} catch(dbException $e){
                $resp = null;
			}
		}
        return $resp;
	}

    public function get(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {
            // Проверяем наличие главной базы
            try {
				Validate::table($resource)->exists();
                
                // Конфигурация таблицы
                $table_config = json_decode(file_get_contents($this->config["dir"].''.$resource.'.config.json'), true);
				
				// Если указан id
				if (isset($id)) {
					$res = Database::table($resource)->where('id', '=', $id)->findAll();
					$resCount = count($res);
					if ($resCount == 1) {
						if (isset($query["relation"])) {
							$id = null;
							$resource_id = $resource.'_id';
							$relation = null;
							$foreach = 0;
							if (base64_decode($query["relation"], true) != false){
								$relation = base64_decode($query["relation"]);
								if (json_decode($relation, true) != null){
									$relation = json_decode($relation, true);
									$foreach = 1;
									} else {
									$relation = $query["relation"];
								}
								} else {
								$relation = $query["relation"];
							}
							$resp["request"]["relation"] = $relation;
							
							foreach($res as $key => $arr){
								if (isset($key) && isset($arr)) {
									$id = $arr->{$resource_id};
									$newArr = (array)$arr;
									if (isset($id)) {
										if ($foreach == 1) {
											foreach($relation as $key => $value) {
												$rel = Database::table($key)->where($resource_id, '=', $id)->findAll();
												foreach($rel as $k => $v) {
													if (in_array($k, $value)) {
														$a = array($k, $v);
														unset($a["0"]);
														$a = $a["1"];
														$r[$key][] = $a;
													}
												}
												$newArr = array_merge($newArr, $r);
											}
											} else {
											$rel = null;
											$ex = explode(",", $relation);
											foreach($ex as $ex_keys => $ex_val) {
												$ex_pos = strripos($ex_val, ":");
												$new_ex = [];
												if ($ex_pos === false) {
													$val = $ex_val;
													$c = 0;
													} else {
													$ex_new = explode(":", $ex_val);
													$val = $ex_new["0"];
													unset($ex_new["0"]);
													$new_ex = array_flip($ex_new);
													$c = 1;
												}
												
												$val_name = $val.'_id';
												if (isset($newArr[$val_name])) {
													$val_id = $newArr[$val_name];
												}
												
												$rel_table_config = json_decode(file_get_contents($this->config["dir"].'/'.$val.'.config.json'), true);
												
												if (array_key_exists($resource_id, $rel_table_config["schema"]) && isset($id)) {
													
													$rel = Database::table($val)->where($resource_id, '=', $id)->findAll();
													if ($c == 1){
														$control = $new_ex;
														} else {
														$control = $rel_table_config["schema"];
													}
													
													} elseif(array_key_exists($val_name, $table_config["schema"]) && isset($val_id)) {
													
													$rel = Database::table($val)->where($val_name, '=', $val_id)->findAll();
													if ($c == 1){
														$control = $new_ex;
														} else {
														$control = $rel_table_config["schema"];
													}
												}
												
												if (count($rel) >= 1) {
													$r = [];
													foreach($rel as $k => $v) {
														$vv = is_array($v) ? $v : (array)$v;
														$ar = [];
														foreach($vv as $key => $va) {
															if (array_key_exists($key, $control) && $key != "password" && $key != "cookie") {
																$ar[$key] = $va;
															}
														}
														$a = array($k, $ar);
														unset($a["0"]);
														$a = $a["1"];
														$r[$val][] = $a;
													}
													$newArr = array_merge($newArr, $r);
												}
											}
										}
									}
									//$newArr = (object)$newArr;
								}
								$array = array($key, $newArr);
								unset($array["0"]);
								$array = $array["1"];
								$item["item"] = $array;
								$items['items'][] = $item;
							}
							$resp['body'] = $items;
						}
						else {
							$reset = reset($res);
							$array = (array)$reset["0"];
							$item["item"] = $array;
							$items['items'][] = $item;
							$resp['body'] = $items;
							//print_r($resp);
						}
						
					}                        
                } else {
					// id не указан, формируем запрос списка
					// Указываем таблицу
					$res = Database::table($resource);
					// Если есть параметры
					if (count($query) >= 1) {
						foreach($query as $key => $value)
						{
							if(!in_array($key, ['andWhere', 'orWhere', 'asArray', 'LIKE', 'relation', 'order', 'sort', 'limit', 'offset' ], true)) {
								if (isset($key) && isset($value)) {
									if (array_key_exists($key, $table_config["schema"])) {
										// Убираем пробелы и одинарные кавычки
										$key = str_replace(array(" ", "'", "%", "%27", "%20"), "", $key);
										$value = str_replace(array(" ", "'", "%", "%27", "%20"), "", $value);
										$res->where($key, '=', $value);
									}
								}
							}
						}
						
						if (isset($query["andWhere"])) {
							// Убираем пробелы и одинарные кавычки
							$andWhere = str_replace(array(" ", "'", "%"), "", $query["andWhere"]);
							// Ищем разделитель , запятую
							$pos = strripos($andWhere, ",");
							if ($pos === false) {
								// : запятая не найдена
								$res->andWhere('id', '=', $andWhere);
								} else {
								// , запятая найдена
								$explode = explode(",", $andWhere);
								$res->andWhere($explode["0"], $explode["1"], $explode["2"]);
							}
						}
						
						if (isset($query["orWhere"])) {
							// Убираем пробелы и одинарные кавычки
							$orWhere = str_replace(array(" ", "'", "%"), "", $query["orWhere"]);
							// Ищем разделитель , запятую
							$pos = strripos($orWhere, ",");
							if ($pos === false) {
								// : запятая не найдена
								$res->orWhere('id', '=', $orWhere);
								} else {
								// , запятая найдена
								$explode = explode(",", $relation);
								$res->orWhere($explode["0"], $explode["1"], $explode["2"]);
							}
						}
						
						if (isset($query["LIKE"])) {
							// Ищем разделитель , запятую
							$pos = strripos($query["LIKE"], ",");
							if ($pos === false) {
								// : запятая не найдена
								$res->where('id', 'LIKE', $query["LIKE"]);
								} else {
								// , запятая найдена
								$explode = explode(",", $query["LIKE"]);
								$res->where(str_replace(array(" ", "'"), "", $explode["0"]), 'LIKE', str_replace(array("<", ">", "'"), "", $explode["1"]));
							}
						}
						
						if (isset($query["order"]) || isset($query["sort"])) {
							
							$order = "DESC";
							$sort = "id";
							
							if (isset($query["order"])) {
								if ($query["order"] == "DESC" || $query["order"] == "ASC" || $query["order"] == "desc" || $query["order"] == "asc") {
									$order = $query["offset"];
								}
							}
							
							if (isset($query["sort"])) {if (preg_match("/^[A-Za-z0-9]+$/", $query["sort"])) {
								$sort = $query["sort"];
							}}
							
							$res->orderBy($sort, $order);
						}
						
						if (isset($query["limit"]) && isset($query["offset"]) == false) {
							$limit = intval($query["limit"]);
							$res->limit($limit);
							} elseif (isset($query["limit"]) && isset($query["offset"])) {
							$limit = intval($query["limit"]);
							$offset = intval($query["offset"]);
							$res->limit($limit, $offset);
						}
						
						$res->findAll();
						
						if (isset($query["asArray"])) {
							// Не работает в этом случае. Если цепочкой то работает.
							if ($query["asArray"] == true) {
								$res->asArray();
							}
						}
						
						$count->findAll()->count();
						$newCount = count($count);
						
						$resCount = count($res);
						if ($resCount >= 1) {
							if (isset($query["relation"])) {
								$id = null;
								$resource_id = $resource.'_id';
								$relation = null;
								$foreach = 0;
								if (base64_decode($query["relation"], true) != false){
									$relation = base64_decode($query["relation"]);
									if (json_decode($relation, true) != null){
										$relation = json_decode($relation, true);
										$foreach = 1;
                                        } else {
										$relation = $query["relation"];
									}
                                    } else {
									$relation = $query["relation"];
								}
								foreach($res as $key => $arr){
									if (isset($key) && isset($arr)) {
										$id = $arr->{$resource_id};
										$newArr = is_array($arr) ? $arr : (array)$arr;
										if (isset($id)) {
											if ($foreach == 1) {
												foreach($relation as $key => $value) {
													$rel = Database::table($key)->where($resource_id, '=', $id)->findAll();
													foreach($rel as $k => $v) {
														if (in_array($k, $value)) {
															$a = array($k, $v);
															unset($a["0"]);
															$a = $a["1"];
															$r[$key][] = $a;
														}
													}
													$newArr = array_merge($newArr, $r);
												}
                                                } else {
												$rel = null;
												$ex = explode(",", $relation);
												foreach($ex as $ex_keys => $ex_val) {
													$ex_pos = strripos($ex_val, ":");
													$new_ex = [];
													if ($ex_pos === false) {
														$val = $ex_val;
														$c = 0;
                                                        } else {
														$ex_new = explode(":", $ex_val);
														$val = $ex_new["0"];
														unset($ex_new["0"]);
														$new_ex = array_flip($ex_new);
														$c = 1;
													}
													$val_name = $val.'_id';
													if (isset($newArr[$val_name])) {
														$val_id = $newArr[$val_name];
													}
													$rel_table_config = json_decode(file_get_contents($this->config["dir"].'/'.$val.'.config.json'), true);
													if (array_key_exists($resource_id, $rel_table_config["schema"]) && isset($id)) {
														
														$rel = Database::table($val)->where($resource_id, '=', $id)->findAll();
														if ($c == 1){
															$control = $new_ex;
                                                            } else {
															$control = $rel_table_config["schema"];
														}
                                                        } elseif(array_key_exists($val_name, $table_config["schema"]) && isset($val_id)) {
														$rel = Database::table($val)->where($val_name, '=', $val_id)->findAll();
														if ($c == 1){
															$control = $new_ex;
                                                            } else {
															$control = $rel_table_config["schema"];
														}
													}
													if (count($rel) >= 1) {
														$r = [];
														foreach($rel as $k => $v) {
															$vv = (array)$v;
															$ar = [];
															foreach($vv as $key => $va) {
																if (array_key_exists($key, $control) && $key != "password" && $key != "cookie") {
																	$ar[$key] = $va;
																}
															}
															$a = array($k, $ar);
															unset($a["0"]);
															$a = $a["1"];
															$r[$val][] = $a;
														}
														$newArr = array_merge($newArr, $r);
													}
												}
											}
										}
										//$newArr = (object)$newArr;
									}
									$array = array($key, $newArr);
									unset($array["0"]);
									$array = $array["1"];
									$item["item"] = $array;
									$items['items'][] = $item;
								}
								$this->response = $items;
                                } else {
								foreach($res as $key => $arr){
									if (isset($key) && isset($arr)) {
										$array = array($key, $arr);
										unset($array["0"]);
										$array = $array["1"];
										$item["item"] = $array;
										$items['items'][] = $item;
									}
								}
								$this->response = $items;
							}
						}
                        } else {
						$items = Database::table($resource)->findAll();
						if (count($items) >= 1) {
							$this->response = $items;
						}
						
					}
				}
			} 
		}
        return $this->response;
	}
	
    public function search(string $resource = null, string $keyword = null, array $query = [], string $field_id = null)
    {
        return null;
	}
	
    public function put(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {
            try {
                Validate::table($resource)->exists();
                $table_config = json_decode(file_get_contents($this->config["dir"].''.$resource.'.config.json'), true);
                if (isset($id)) {
                    $row = Database::table($resource)->find($id);
                    foreach($arr as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id") {
                                if (array_key_exists($key, $table_config["schema"])) {
                                    if ($table_config["schema"][$key] == "integer") {
                                        $value = str_replace(array('"', "'", " "), '', $value);
                                        if (is_numeric($value)) {
                                            $value = int($value);
										} else {
                                            $value = int($value);
										}
									} elseif ($table_config["schema"][$key] == "double") {
                                        $value = str_replace(array('"', "'", " "), '', $value);
                                        if (is_float($value * 1)) {
                                            $value = (float)$value;
										} else {
                                            $value = (float)$value;
										}
									} elseif ($table_config["schema"][$key] == "boolean") {
                                        $value = str_replace(array('"', "'", " "), '', $value);
                                        if (is_bool($value)) {
                                            $value = bool($value);
										} else {
                                            $value = bool($value);
										}
									} elseif ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = strval($value);
										} else {
                                            $value = strval($value);
										}
									} else {
                                        $value = null;
									}
                                    $row->{$key} = $value;
								}
							}
						}
					}
                    $row->save();
                    if ($row->id == $id) {
                        $resp = $id;
					}
				} else {
                    foreach($arr as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id") {
                                if (array_key_exists($key, $table_config["schema"])) {
									
                                    if ($table_config["schema"][$key] == "integer") {
                                        if (is_numeric($value)) {
                                            $value = (int)$value;
										} else {
                                            $value = 0;
										}
									}
                                    if ($table_config["schema"][$key] == "double") {
                                        if (is_float($value)) {
                                            $value = (float)$value;
										} else {
                                            $value = 0.00;
										}
									}
                                    if ($table_config["schema"][$key] == "boolean") {
                                        if (is_bool($value)) {
                                            $value = (bool)$value;
										} else {
                                            $value = false;
										}
									}
                                    if ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = (strval)$value;
										} else {
                                            $value = null;
										}
									} else {
                                        $value = null;
									}
                                    $row->{$key} = $value;
								}
							}
						}
					}
                    $row->save();
                    if ($row->id >= 1) {
                        $resp = $row->id;
					}
				}
			} catch(dbException $e) {}
		}
        return $resp;
	}
	
    public function patch(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {
            // Проверяем наличие главной базы если нет даем ошибку
            try {
                Validate::table($resource)->exists();
                $table_config = json_decode(file_get_contents($this->config["dir"].'/'.$resource.'.config.json'), true);
				
                // Если указан id обновляем одну запись
                if ($id >= 1) {
                    // Подключаем таблицу
                    $row = Database::table($resource)->find($id);
                    // Разбираем параметры полученные в теле запроса
                    foreach($arr as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id") {
                                if (array_key_exists($key, $table_config["schema"])) {
									
                                    if ($table_config["schema"][$key] == "integer") {
                                        if (is_numeric($value)) {
                                            $value = intval($value);
											} else {
                                            $value = 0;
										}
									}
                                    if ($table_config["schema"][$key] == "double") {
                                        if (is_float($value * 1)) {
                                            $value = (float)$value;
											} else {
                                            $value = (float)$value;
										}
									}
                                    if ($table_config["schema"][$key] == "boolean") {
                                        if (is_bool($value)) {
                                            $value = boolval($value);
											} else {
                                            $value = false;
										}
									}
                                    if ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = strval($value);
											} else {
                                            $value = null;
										}
                                        
									}
                                    else {
                                        $value = null;
									}
									
                                    try {
                                        $row->{$key} = $value;
                                        
										} catch(dbException $error){
                                        //echo $error;
									}
								}
							}
						}
					}
                    // Сохраняем изменения
                    $row->save();
					
                    if ($row == 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->config['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = $id;
                        $resp["request"]["query"] = "PATCH";
                        $resp["request"]["resource"] = $resource;
						} else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->config['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
					}
					
					} else {
                    // Обновляем несколько записей
                    // Разбираем параметры полученные в теле запроса
                    foreach($arr as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id") {
                                if (array_key_exists($key, $table_config["schema"])) {
									
                                    if ($table_config["schema"][$key] == "integer") {
                                        if (is_numeric($value)) {
                                            $value = intval($value);
											} else {
                                            $value = 0;
										}
									}
                                    if ($table_config["schema"][$key] == "double") {
                                        if (is_float($value)) {
                                            $value = floatval($value);
											} else {
                                            $value = 0.00;
										}
									}
                                    if ($table_config["schema"][$key] == "boolean") {
                                        if (is_bool($value)) {
                                            $value = boolval($value);
											} else {
                                            $value = false;
										}
									}
                                    if ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = strval($value);
											} else {
                                            $value = null;
										}
                                        
									}
                                    else {
                                        $value = null;
									}
									
                                    try {
                                        $row->{$key} = $value;
                                        
										} catch(dbException $error){
                                        //echo $error;
									}
								}
							}
						}
					}
                    // Сохраняем изменения
                    $row->save();
					
                    if ($row->id >= 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->config['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 1;
                        $resp["response"]["id"] = '';
                        $resp["request"]["query"] = "PATCH";
                        $resp["request"]["resource"] = $resource;
						
						} else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->config['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
					}
				}
				
				} catch(dbException $e){
                // Таблица не существует даем ошибку 404
                $resp["headers"]["status"] = '404 Not Found';
                $resp["headers"]["code"] = 404;
                $resp["headers"]["message"] = 'Not Found';
                $resp["headers"]["message_id"] = $this->config['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
			}
			
			} else {
            // Если таблица не определена даем ошибку 400
            $resp["headers"]["status"] = '400 Bad Request';
            $resp["headers"]["code"] = 400;
            $resp["headers"]["message"] = 'Bad Request';
            $resp["headers"]["message_id"] = $this->config['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
		}
		
        return $resp;
		
	}
	
    public function delete(string $resource = null, int $id = null, string $field_id = null)
    {
        if (isset($resource)) {
            // Проверяем наличие главной базы если нет даем ошибку
            try {
                Validate::table($resource)->exists();
                $table_config = json_decode(file_get_contents($this->config["dir"].'/'.$resource.'.config.json'), true);
                // Если указан id удаляем одну запись
                if (isset($id)) {
                    // Удаляем запись из таблицы
                    $row = Database::table($resource)->find($id)->delete();
                    if ($row == 1) {
                        $resp = $id;
					}
				} else {
				    $file = $this->config["dir"].'/'.$resource.'.data.json';
				    // Открываем файл для получения существующего содержимого
				    $current = file_get_contents($file);
				    // Очищаем весь контент оставляем только []
				    $current = "[]";
				    // Пишем содержимое обратно в файл
				    file_put_contents($file, $current);
				}
			} catch(dbException $e){}
		}
        return $resp;
	}

    public function count(string $resource = null, array $query = [], int $id = null, string $field_id = null): int
    {
		
	}
	
    public function lastId(string $resource = null, string $field_id = null): int
    {
        $last_id = Database::table($resource)->lastId();
		return (int)$last_id;
	}
	
	public function fieldMap($resource = null)
	{
		return [];
	}
	
    public function tableSchema($table)
    {
        $fieldMap = $this->fieldMap($table);
        $table_schema = [];
        foreach($fieldMap as $key => $val)
        {
            $table_schema[$key] = $val;
		}
        
        return $table_schema;
	}
	
}
