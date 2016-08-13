<?php

namespace app\Controllers;

use core\Controller;

class Index extends Controller
{
    public function indexAction()
    {
        $data = new \StdClass;
        $data->titulo = 'PHP com MVC';
        $data->content = 'Acesso <code>/alunos</code>';
        $this->render('bootstrap/index',$data);
    }
}