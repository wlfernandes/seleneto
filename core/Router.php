<?php

namespace core;

class Router
{
    /**
     * @method run() = Motor da aplicação
     * @description = Este método Faz o trabalho principal. Ele Instancia a classe Request para poder capturar os valores
     * do Controller,Action e dos argumentos. E Logo em seguida faz uma verificação da existência do arquivo do controlador e se a classe foi
     * definida juntamente com o méthodo.
     */
    protected function run()
    {
        $Request = new Request;
        $Controller = ucfirst($Request->getController());
        $Action = $this->camelCase($Request->getAction());
        $Args = $Request->getArgs();

        $file = PATH_CONTROLLERS.DS.$Controller.'.php';
        if(file_exists($file))
        {
            $class = "app\\Controllers\\".$Controller;
            if(class_exists($class))
            {
                $class = new $class;
                if(method_exists($class,$Action))
                {
                    call_user_func_array([$class,$Action],$Args);
                }
                // Método não encontrado na classe Controller
            }
            // Classe do controller não existe
        }
        // Controller não existe
    }


    /**
     * @method = camelCase() = Este converte string com traços, em formato CamelCase
     * @param $str = String que será convertida
     * @param array $noStrip
     * @return string = Retorna a string com nome em camelcase
     */
    private function camelCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = lcfirst($str);

        return ucfirst($str);
    }
}