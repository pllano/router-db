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

class JsonApi implements DatabaseInterface
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
	protected $response = null;

    public function __construct(array $config = [], string $database = null, array $options = [], string $format = null, string $prefix = null, $other_base = null)
    {
		$this->database = $database;
		$this->options = $options;
        if (isset($format)) {
            $this->format = strtolower($format);
        }
		if (isset($config)) {
            if (isset($other_base)) {
                $this->other_base = $other_base;
                $this->config = $config['db'][$this->other_base];
            } elseif (isset($prefix)) {
			    $this->prefix = $prefix;
                $this->config = $config['db'][$this->database.'_'.$this->prefix];
            } else {
                $this->config = $config['db'][$this->database];
            }
        }
		return new Database();
	}

    public function ping(string $resource = null)
    {
		$ping = null;
		if (isset($resource)) {
            try {
			    Validate::table($resource)->exists();
				$ping = "json";
			} catch(dbException $e){
			    $ping = null;
			}
		}
		return $ping;
	}

    public function get(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {
			$get = Database::table($resource);
			if (isset($id)) {
				$get->where('id', '=', $id);
            } else {
			    if (isset($query)) {
					$table_config = json_decode(file_get_contents($this->config["dir"].''.$resource.'.config.json'), true);
			        foreach($query as $key => $value)
			        {
			            if (isset($key) && isset($value)) {
							if(!in_array($key, ['andWhere', 'orWhere', 'asArray', 'LIKE', 'relation', 'order', 'sort', 'limit', 'offset' ], true)) {
							    if (array_key_exists($key, $table_config["schema"])) {
							        // Убираем пробелы и одинарные кавычки
							        $key = str_replace(array(" ", "'", "%", "%27", "%20"), "", $key);
							        $value = str_replace(array(" ", "'", "%", "%27", "%20"), "", $value);
							        $get->where($key, '=', $value);
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
							$get->andWhere('id', '=', $andWhere);
						} else {
							// , запятая найдена
							$explode = explode(",", $andWhere);
							$get->andWhere($explode["0"], $explode["1"], $explode["2"]);
			            }
			        }
			        if (isset($query["orWhere"])) {
			            // Убираем пробелы и одинарные кавычки
			            $orWhere = str_replace(array(" ", "'", "%"), "", $query["orWhere"]);
			            // Ищем разделитель , запятую
			            $pos = strripos($orWhere, ",");
			            if ($pos === false) {
							// : запятая не найдена
							$get->orWhere('id', '=', $orWhere);
						} else {
							// , запятая найдена
							$explode = explode(",", $relation);
							$get->orWhere($explode["0"], $explode["1"], $explode["2"]);
			            }
			        }
			        if (isset($query["LIKE"])) {
			            // Ищем разделитель , запятую
			            $pos = strripos($query["LIKE"], ",");
			            if ($pos === false) {
							// : запятая не найдена
							$get->where('id', 'LIKE', $query["LIKE"]);
					    } else {
							// , запятая найдена
							$explode = explode(",", $query["LIKE"]);
							$get->where(str_replace(array(" ", "'"), "", $explode["0"]), 'LIKE', str_replace(array("<", ">", "'"), "", $explode["1"]));
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
			            if (isset($query["sort"])) {
							if (preg_match("/^[A-Za-z0-9]+$/", $query["sort"])) {
							    $sort = $query["sort"];
							}
						}
			            $get->orderBy($sort, $order);
			        }
			        if (isset($query["limit"]) && isset($query["offset"]) == false) {
			            $limit = intval($query["limit"]);
			            $get->limit($limit);
			        } elseif (isset($query["limit"]) && isset($query["offset"])) {
			            $limit = intval($query["limit"]);
			            $offset = intval($query["offset"]);
			            $get->limit($limit, $offset);
			        }
			    }
			}

			$get->findAll();
			//$get->asArray();
			$this->response = $get ?? [];

		}

        if ($this->format != "api") {
            $this->response = $this->format($this->response, $this->format);
        }
        return $this->response;

	}

    public function getJoin(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {
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
								foreach($res as $key => $arr)
								{
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
        return $this->response;
	}

    public function post(string $resource = null, array $query = [], string $field_id = null): int
    {
        $this->response = null;
		if (isset($resource)) {
            try {
                Validate::table($resource)->exists();
                $data = Database::table($resource);
				$table_config = json_decode(file_get_contents($this->config["dir"].'/'.$resource.'.config.json'), true);
                foreach($query as $key => $value)
				{
					if (isset($key) && isset($value)) {
                        if ($key != "id") {
                            if (array_key_exists($key, $table_config["schema"])) {
                                try {
                                    $data->{$key} = $this->control($table_config["schema"], $key, $value);
								} catch(dbException $error){
                                    // echo $error;
								}
							}
						}
					}
				}
                $data->save();
                if ($data->id >= 1) {
                    $put = Database::table($resource)->find($data->id);
                    $put->{$resource."_id"} = $data->id;
                    $put->save();
                    $this->response = $data->id;
				} 
			} catch(dbException $e) {
                $this->response = null;
			}
		}
        return $this->response;
	}

    public function put(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {
            $table_config = json_decode(file_get_contents($this->config["dir"].''.$resource.'.config.json'), true);
            if (isset($id)) {
                $data = Database::table($resource)->find($id);
                foreach($query as $key => $value)
				{
				    if (isset($key) && isset($value)) {
                        if ($key != "id") {
                            if (array_key_exists($key, $table_config["schema"])) {
                                try {
									$data->{$key} = $this->control($table_config["schema"], $key, $value);
								} catch(dbException $error){
                                    // echo $error;
								}
							}
						}
					}
				}
                $data->save();
                $this->response = $data->id ?? null;
			}
		}
        return $this->response;
	}

    public function patch(string $resource = null, array $query = [], int $id = null, string $field_id = null)
    {
        if (isset($resource)) {
            $table_config = json_decode(file_get_contents($this->config["dir"].''.$resource.'.config.json'), true);
            if (isset($id)) {
                $data = Database::table($resource)->find($id);
                foreach($query as $key => $value)
				{
				    if (isset($key) && isset($value)) {
                        if ($key != "id") {
                            if (array_key_exists($key, $table_config["schema"])) {
                                try {
                                    $data->{$key} = $this->control($table_config["schema"], $key, $value);
								} catch(dbException $error){
                                    // echo $error;
								}
							}
						}
					}
				}
                $data->save();
                $this->response = $data->id ?? null;
			}
		}
        return $this->response;
	}
	
    public function delete(string $resource = null, int $id = null, string $field_id = null)
    {
        if (isset($resource)) {
            if (isset($id)) {
				$delete = Database::table($resource)->find($id)->delete();
				$this->response = $delete ?? null;
			} else {
				$file = $this->config["dir"].'/'.$resource.'.data.json';
				$current = file_get_contents($file);
				$current = "[]";
				file_put_contents($file, $current);
			}
		}
        return $this->response;
	}

    public function search(string $resource = null, array $query = [], string $keyword = null, string $field_id = null)
    {
        return null;
	}

    public function count(string $resource = null, array $query = [], int $id = null, string $field_id = null): int
    {
        return null;
	}
	
    public function lastId(string $resource = null): int
    {
        $last_id = Database::table($resource)->lastId();
		return (int)$last_id;
	}
	
	public function fieldMap($resource = null)
	{
		$table_config = json_decode(file_get_contents($this->config["dir"].''.$resource.'.config.json'), true);
		return $table_config["schema"];
	}

    public function tableSchema($resource)
    {
        $fieldMap = $this->fieldMap($resource);
        $table_schema = [];
        foreach($fieldMap as $key => $val)
        {
            $table_schema[$key] = $val;
		}
        return $table_schema;
	}

    public function control($schema, $key, $value)
    {
		if ($schema[$key] == "integer") {
            $value = (integer)$value;
		}
		if ($schema[$key] == "double") {
		    $value = (double)$value;
		}
		if ($schema[$key] == "boolean") {
		    $value = (boolean)$value;
		}
		if ($schema[$key] == "string") {
		    $value = (string)$value;
		}
		return $value;
	}

    public function setFormat($format = null)
    {
        if (isset($format)) {
            $this->format = strtolower($format);
        }
    }

    public function format($data, $format = null)
    {
		if (isset($format)) {
            $this->format = strtolower($format);
        }
        $response = [];
        if ($this->format == 'api') {
            $response = $data;
        } elseif ($this->format == 'object') {
		    $response = null;
            $response = (object)$data;
        } else {
            $response = $data;
        }
        return $response;
	}
}
 