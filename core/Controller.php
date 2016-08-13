<?php
namespace core;

/**
 * Class Controller
 * Classe responsável por instanciar models e redenrizar views
 * Todas as classes de controllers da aplicação deverá extender dessa classe.
 */

use core\Helpers\Util;

class Controller
{
    /**
     * Trait Util contem alguns métodos de auxílio. Todos os métodos nesse Trait poderão ser acessados em qualquer Controller Filho
     * ou em qualquer View.
     */
    use Util;


    /**
     * @method model() = Este método devera ser chamado apartir de qualquer Controller Filho ou de qualquer View.
     * @param $name = Nome do Objeto a ser instanciado
     * @param null $param = Parâmetro opcional que poderá ser passado para o construtor do objeto
     * @return string = Retorna um objeto instanciado
     */
    protected function model($name,$param=null)
    {
        $model = ucfirst($name);
        $file = PATH_MODELS.DS.$model.'.php';
        if(file_exists($file))
        {
            $class = "app\\Models\\".$model;
            if(class_exists($class))
            {
                $class = new $class($param);
                return $class;
            }
            // classe do model não definida
        }
        // Model não encontrado
    }


    /**
     *
     * @param $view = Nome da view que deverá ser rendenrizada
     * @param null $data = Este parâmetro não seja obrigatório, é fundamental, pois é apartir deste que dados serão passados para as views.
     * Ele não é obrigatório porque nem toda view necessitará de dados
     */
    protected function render($view,$data=null)
    {
        $file = PATH_VIEWS.DS.$view.'.phtml';
        if(file_exists($file))
        {
            require_once($file);
        }
        // View não encontrada
    }
}