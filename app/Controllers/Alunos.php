<?php

namespace app\Controllers;

class Alunos extends \core\Controller
{
    public function indexAction()
    {
        $data = $this->model('aluno');
        $data->titulo = 'Alunos';
        $data->message = '* Esses dados estão vindo do Banco de Dados';
        $this->render('bootstrap/pages/alunos',$data);
    }

   
    
    
    
 }