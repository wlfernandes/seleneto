<?php
namespace core;

class Request
{
    private $Controller;
    private $Action;
    private $Args = [];

    /**
     * Request constructor.
     * Construtor que faz a verificação da url e a quebra para conseguir os endereços.
     */
    public function __construct()
    {
        $Uri = $this->uri();
        $exp = explode('/',$Uri);

        # Setando os valores das propriedades da aplicação
        $this->Controller = ($c = array_shift($exp)) ? $c : 'index';
        $this->Action = ($a = array_shift($exp)) ? $a : 'indexAction';
        $this->Args = (sizeof($exp)) ? $exp : [];
    }

    /**
     * @method uri() = Eleimina as querystrings da url
     * @return mixed|string = Retorna a url sem query string
     */
    private function uri()
    {
        $uri = preg_replace("#([a-z0-9\/\-]+)(\?.*)?#i", "$1", $_SERVER['REQUEST_URI']);
        $uri = trim($uri,'/');
        return $uri;
    }

    /**
     * @return mixed|string = Retorna o controlador que já foi processado no Construtor
     */
    public function getController()
    {
        return $this->Controller;
    }

    /**
     * @return mixed|string = Retorna a action que já foi processada no Construtor
     */
    public function getAction()
    {
        return $this->Action;
    }

    /**
     * @return array = Retorna os argumentos do método que já foram processados no Construtor
     */
    public function getArgs()
    {
        return $this->Args;
    }
}