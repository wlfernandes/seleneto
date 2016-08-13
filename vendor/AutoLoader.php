<?php

class AutoLoader
{
	private static $dir;
	private static $ext = '.php';

	public static function inc($dir)
	{
		self::$dir[] = $dir;
	}

	private static function dir()
	{
		$dir = new DirectoryIterator(ROOT.DS.'vendor');
		if(is_dir($dir))
		{
			foreach($dir as $val)
			{
				if($val->isDir() && !$val->isDot())
				{
					self::$dir[] = $val->__toString();
				}
			}
		}
	}

	private static function load($classname)
	{
		$classname = ltrim($classname,'\\');

		if($posns=strrpos($classname,'\\'))
		{
			$namesapce = substr($classname, 0, $posns);
			$class     = substr($classname,$posns+1);
			$file 	   = $namesapce.DS.$class.self::$ext;
		}
		else
			$file = $classname.self::$ext;

		$file = str_replace('\\', DS, $file);
		$file = str_replace('_', DS, $file);

		if(file_exists($file))
		{
			require($file);
			return;
		}

		if(!empty(self::$dir))
		{
			foreach(self::$dir as $dir)
			{
				$file_dir = $dir.DS.$file;
				if(file_exists($file_dir))
				{
					require_once($file_dir);
					return;
				}
			}
		}

		if(stream_resolve_include_path($file)!==false)
		{
			require($file);
			return;
		}

		trigger_error("Arquivo <b>$file</b> não encontrado",E_USER_ERROR);
		//var_dump(debug_backtrace());
	}

	public static function dispatch()
	{
		spl_autoload_register(['AutoLoader', 'load']);
		//call_user_func(['AutoLoader', 'load']);
	}
}