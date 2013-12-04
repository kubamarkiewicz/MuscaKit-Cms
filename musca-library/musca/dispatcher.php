<?php
	
	/* modified 2013.11.23 */

	class Musca_Dispatcher
	{
		private $uri;
		private $uri_real;
        public $ar_folder_web;
        protected $controllersPath;
        protected $modulesPath;
        protected $db;
        protected $i18n;
		
		function __construct($uri_url, $db=null, $i18n=null)
		{
			$this->controllersPath = MUSCA_PATH . APP_DIR . CONTROLLERS_DIR;
			$this->modulesPath = MUSCA_PATH . APP_DIR . MODULES_DIR . DS;

			$this->uri_real = $uri_url;
			$this->uri = array_slice(explode('/', $uri_url),1);

			foreach ($this->uri as $k => $v)
			{
				$tmp = explode('?', $v);
				$this->uri[$k] = $tmp[0];
			}

			$this->db = $db;
			$this->i18n = $i18n;
		}
		
		function igniter()
		{
			$i = 0;

			$doc_root = $_SERVER['DOCUMENT_ROOT'];
			$folder = str_replace('/index.php', '', $_SERVER['SCRIPT_FILENAME']);
			$doc_root_len = strlen($doc_root);
			$folder_len = strlen($folder);
			$folder_real = substr($folder,$doc_root_len,$folder_len);
			$this->ar_folder_web = explode('/', PREF . $folder_real);

			// IDIOMAS

				$langs = $this->i18n->getLangs();
				$nFolder = count($this->ar_folder_web);
				$nFolder = $nFolder > 1 ? $nFolder - 1 : 0;

				if(@in_array(@$this->uri[$nFolder], $langs))
				{
	                $this->i18n->selectLang($this->uri[$nFolder]);
					unset($this->uri[$nFolder]);
					$this->uri = array_values($this->uri);
				}
			// ----------------- >>


			foreach($this->ar_folder_web as $folder)
			{
				if(!empty($this->uri[$i]) && (in_array('/'.$this->uri[$i], $this->ar_folder_web) || in_array($this->uri[$i], $this->ar_folder_web)))
				{
	                array_shift($this->uri);
					if(empty($this->uri)) $this->uri[$i] = '';
				}
			}

			if(empty($this->uri)) $this->uri[$i] = '';
			// print_r($this->uri);

			// Destraducción de la URL
			if (defined('HOST')) $this->routes();

			// averiguar si el primer parametro es un directorio existente
			$dir = DS;
			if(is_dir($this->controllersPath.$this->uri[$i])) $dir = array_shift($this->uri).DS;

			// averiguar si el segundo parametro es un directorio existente
			if(isset($this->uri[$i]) && is_dir($this->controllersPath.$dir.$this->uri[$i])) $dir .= array_shift($this->uri).DS;

			// averiguar si el tercer parametro es un directorio existente
			if(isset($this->uri[$i]) && is_dir($this->controllersPath.$dir.$this->uri[$i])) $dir .= array_shift($this->uri).DS;

			// check if module exists
			$module = '';
			if(isset($this->uri[$i]))
			{
				// echo $this->modulesPath.$this->uri[$i];
				if(is_dir($this->modulesPath.$this->uri[$i]))
				{
					$module = array_shift($this->uri);

					// include module library path
					set_include_path(
						get_include_path()
						. PATH_SEPARATOR . $this->modulesPath . $module . LIBRARY_DIR . DS
					);
				}	
			}
			DEFINE('MODULE', $module);



			// averiguar si el controlador existe
			$controller = 'index';
			// print_r($this->uri);
			// print_r($dir);
				// echo $this->modulesPath.$module.CONTROLLERS_DIR.DS.$this->uri[$i].'.php';
			if(isset($this->uri[$i]))
			{
				if ($module && is_file($this->modulesPath.$module.CONTROLLERS_DIR.DS.$this->uri[$i].'.php')) $controller = array_shift($this->uri);
				elseif(is_file($this->controllersPath.$dir.$this->uri[$i].'.php')) $controller = array_shift($this->uri);
			}

			// cargar el controlador
			if($module && is_file($this->modulesPath.$module.CONTROLLERS_DIR.DS.$controller.'.php')) require_once($this->modulesPath.$module.CONTROLLERS_DIR.DS.$controller.'.php');
			else require_once($this->controllersPath.$dir.$controller.'.php');
			$dispatcher = new $controller($this->db, $this->i18n);

			// include module template path
			if($module)
				$dispatcher->smarty->addTemplateDir($this->modulesPath . $module . TEMPLATES_DIR . DS);

			// cargar el metodo
			$action = 'first';
			if (isset($this->uri[$i]))
			{
				if (method_exists($controller, $this->uri[$i])) $action = array_shift($this->uri);
				
				// if controler does not exist but exists template then display template
				// elseif (($controller == 'index') && file_exists(MUSCA_PATH . APP_DIR . TEMPLATES_DIR . DS . $this->uri[$i].'.tpl'))
				// {
				// 	$action = 'output';
				// 	$this->uri[$i] .= '.tpl';
				// }
			}
			for ($e=0; $e<=5; $e++) $p[] = isset($this->uri[$i]) ? $this->uri[$i++] : '';

			// echo 'module:'.$module.'/controller:'.$controller.'/action:'.$action;
			// print_r($this->uri); exit;

			call_user_func_array(array($dispatcher, $action), $this->uri);
		}

		private function routes()
		{
			$db = new Musca_DB(HOST, USER, PASSWORD, DB_NAME);
			$arRoutes = $db->getAssoc("SELECT value, value_tpl FROM musca_i18n WHERE section='routes'");

			foreach($this->uri as $k => $v)
				if(!empty($arRoutes[$v])) $this->uri[$k] = $arRoutes[$v];
		}
	}