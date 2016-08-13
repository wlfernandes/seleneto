<?php

/**
 * Classe auxiliar
 */
namespace core\Helpers;

trait Util
{
    /**
     * @method alias() = Faz o tratamento de uma string eliminando caracteres especiais
     * @param $str = string que será submetida a tratamento
     * @return mixed|string = retorna uma string composta por letras, numeros e traços
     */
    public static function alias($str){
        $str = strtolower(utf8_decode($str)); $i=1;
        $str = strtr($str, utf8_decode('àáâãäåæçèéêëìíîïñòóôõöøùúûýýÿ"'),'aaaaaaaceeeeiiiinoooooouuuyyy');
        $str = preg_replace("/([^a-z0-9])/",'-',utf8_encode($str));
        while($i>0) $str = str_replace('--','-',$str,$i);
        if (substr($str, -1) == '-') $str = substr($str, 0, -1);
        return $str;
    }
}