<?php

namespace Core;

/**
 * Class Model
 * @description Classe para manipulação de tabelas no banco de dados. Implementa o pattern ActiveRecord.
 * @package Core
 * @author Airton Lopes <airtonlopes_@hotmail.com>
 * @copyright (c) 2015 webcomcafe sistemas
 */

abstract class Model extends Db\Conn
{
    /**
     * @var \PDO = Propriedade que receberá a conexão com o banco
     */
    private $Conn;

    /**
     * @var = $Propriedade que armazenará o statements que será processado
     */
    private $Stmt;

    /**
     * @var = Tabela que será manipulada
     */
    private $Table;

    /**
     * @var array = Array que guardara as colunas da tabela
     */
    private $Data = [];

    /**
     * @var = Campo da chave estrangeria nas tabelas relacionadas
     */
    private $Fk;

    /**
     * @var array = Objetos relacionadas
     */
    private $Orm = [];

    /**
     * @var array = Array que guardará os places referentes aos statements que serão processados
     */
    private $Places = [];

    /**
     * @var = Essa propriedade guarda a instrução SQL que será executada no banco
     */
    private $Query;

    /**
     * @var = Número de registros afetados no banco após uma operação
     */
    private $Results;

    /**
     * @var = Armazena o id do último registro inserido no banco
     */
    private $LastInsertId;

    /**
     * @var = Contém o objeto que exibe mensagens no sistema
     */
    private $Message;

    /**
     * @var = Guarda as mensagens de error do sistema
     */
    private $Error;


    /**
     * @method __construct() = Instancia a classe carregando ou não um objeto previamente.
     * @param null $id = id de um objeto que será carregado na memória no memento da instanciação da classe.
     */
    public function __construct($id = null)
    {
        $this->Conn = parent::getConn();
        $this->configure();
        if($id)
        {
            $id = (int) $id;
            $this->load($id);
        }
    }


    /**
     * @method configure() = Responsável por capturar o nome da tabela que será manipulada
     * juntamente com informações de relacionamento e chaves estrangeiras
     */
    private function configure()
    {
        $class = get_class($this);
        $this->Table = defined("{$class}::TABLE") ? constant("{$class}::TABLE") : substr(strrchr($class,'\\'),1).'s';
        $ORM = defined("{$class}::ORM") ? constant("{$class}::ORM") : null;
        $this->Fk = $ORM ? key($ORM) : null;

        if($ORM)
            foreach($ORM[$this->Fk] as $name)
                $this->Orm[] = $name;

        $columns_table = $this->query(["SHOW COLUMNS FROM {$this->Table}"]);
        foreach($columns_table as $column)
            $this->Data[$column->Field] = $column->Default;

    }


    /**
     * @method sql() = Principal método. Prepara as instruções SQLs que serão executadas
     * no banco de acordo com as informações passadas.
     * @param $type = Nome da operação a ser executado.
     * @param null $param = Opções personalizadas da operação especificada
     */
    private function sql($type, $param = null)
    {
        $type = strtolower($type);
        switch($type)
        {
            case 'insert' :
                $this->Data['id'] = $this->getSequence();
                $fields = implode(', ', array_keys($this->Data));
                $values = ':'.implode(', :', array_keys($this->Data));
                $this->Query = "INSERT INTO {$this->Table} ({$fields}) VALUES ({$values})";
                $this->Stmt = $this->Conn->prepare($this->Query);
                break;

            case 'update' :
                $places = '';
                $i = 1;
                foreach($this->Data as $name => $val)
                {
                    if($name<>'id')
                    {
                        $places .= "{$name}=:{$name}";
                        if($i<count($this->Data)) $places .= ', ';
                    }
                    $i++;
                }
                $places = rtrim($places,', ');
                $this->Query = "UPDATE {$this->Table} SET {$places} WHERE id=:id";
                $this->Stmt = $this->Conn->prepare($this->Query);
                break;

            case 'all' :
                $query = "SELECT * FROM {$this->Table}";
                if(is_array($param))
                {
                    $query .= " WHERE $param[0]";
                    $this->places($param[1]);
                }
                if(is_string($param))
                    $query .= " {$param}";

                $this->Query = $query;
                break;

            case 'find' :
                if(is_int($param) && $param>0)
                    return call_user_func([$this,'load'],$param,false);

                $keys = key($param);
                $str = stristr($keys,':',TRUE);
                $fields = stristr($keys,':');
                $places = $param[$keys][0];
                $query_places = $param[$keys][1];
                $vars = ['first','last','all'];
                if(in_array($str,$vars))
                {
                    $var = $str;
                    $fields = str_replace('#',',',ltrim($fields,':'));
                }
                else
                {
                    $var = 'all'; # id#thumb
                    if(gettype($keys)=='string')
                    {
                        if(!in_array($keys,$vars))
                        {
                            $fields = str_replace('#',',', $keys);
                        }
                        else
                        {
                            $var = $keys;
                            $fields = '*';
                        }
                    }
                    else
                    {
                        $places = $param[0];
                        $query_places = $param[1];
                        $fields = '*';
                    }
                }

                $this->Query = "SELECT {$fields} FROM {$this->Table} WHERE {$places}";
                if($var=='first')
                    $limits = " ORDER BY id ASC LIMIT 1";
                if($var=='last')
                    $limits = " ORDER BY id DESC LIMIT 1";
                /*if($var=='all')
                    $limits = " ORDER BY id ASC";*/
                $this->Query .= $limits;
                $this->Stmt = $this->Conn->prepare($this->Query);
                $this->places($query_places);
                break;

            case 'remove':
                if(is_array($param))
                {
                    $places = $param[0];
                    $this->places($param[1]);
                    $this->Query = "DELETE FROM {$this->Table} WHERE {$places}";
                }
                else
                {
                    if(is_int($param))
                        $this->Places['id'] = $param;
                    else
                        $this->Places['id'] = $this->Data['id'];
                    $this->Query = "DELETE FROM {$this->Table} WHERE id=:id";
                }
                $this->Stmt = $this->Conn->prepare($this->Query);
                break;

            case 'query':
                $sql = str_replace('this', $this->Table, $param[0]);
                $places = $param[1];
                $this->places($places);
                $this->Query = "{$sql}";
                $this->Stmt = $this->Conn->prepare($this->Query);
                break;
        }
    }


    /**
     * @method() save() = Salva um registro no banco de dados de acordo com a sql preparada.
     * @return bool = Retorna verdadeiro caso tudo ocorra bem, ou falso caso contrário.
     */
    public function save()
    {
        if($this->new_record())
            $this->sql('insert');
        else
            $this->sql('update');

        try
        {
            $this->Stmt->execute($this->Data);
            $this->LastInsertId = $this->Conn->lastInsertId();
            $this->Results = $this->Stmt->rowCount();
            return true;
        }
        catch(\PDOException $e)
        {
            echo("<code>Erro ao salvar registro {$e->getMessage()}</code>");
            $this->Error = $e->getMessage();
            return false;
        }
    }


    /**
     * @method create() = Faz o mesmo que o método save(), com a diferença que os dados a serem
     * persistidos no banco, podem ser informados diretamente no parâmetro array.
     * @param array|null $Data = Array associativo com os dados que serão persistidos no banco.
     * @return mixed = Retorna o resultado da operação realizada pelo método save()
     */
    public function create(array $Data = null)
    {
        $this->Data = $Data ? $Data : $this->Data;
        return call_user_func([$this,'save']);
    }


    /**
     * @method all() = Retorna todos os registros na tabela
     * @param null $options = Opções personalizadas para a busca
     * @return array|bool = Retorna um array de objetos
     */
    public function all($options = null, $assoc = null)
    {
        $this->sql('all',$options);
        try
        {
            $stmt = $this->Conn->prepare($this->Query);
            $stmt->execute($this->Places);
            $this->Places = [];
            if($res=$stmt->rowCount())
            {
                $this->Results = $res;
                if($assoc)
                    $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                else
                    $data = $stmt->fetchAll(\PDO::FETCH_FUNC,[$this,'fetchObject']);

                return $data;
            }
        }
        catch(\PDOException $e)
        {
            echo("<code>(all)::Erro ao listar registros: {$e->getMessage()} - {$this->Query}</code>");
            return false;
        }
    }


    /**
     * @method find() = Busca registro/s baseado nas opções informadas ['first:*'=>['sql','places']
     * @param array $options = array com as opções de filtro.
     * @return bool = Retorna um array de objetos
     */
    public function find($arr, $assoc = false)
    {
        $this->sql('find',$arr);
        try
        {
            $this->Stmt->execute($this->Places);
            $this->Places = [];
            if($res=$this->Stmt->rowCount())
            {
                $this->Results = $res;
                if($assoc)
                    $data = $this->Stmt->fetchAll(\PDO::FETCH_ASSOC);
                else
                    $data = $this->Stmt->fetchAll(\PDO::FETCH_FUNC,[$this,'fetchObject']);

                if(count($data)>1)
                    return $data;
                else
                    return $data[0];
            }
        }
        catch(\PDOException $e)
        {
            echo("<code>(find)::Erro ao selecionar registro: {$e->getMessage()}</code>");
            return false;
        }
    }


    /**
     * @method fetch() = Busca um objeto pelo id informado e o retorna.
     * @param $id = ID do registro a ser retornado
     * @return bool = Retorna um objeto caso tudo ocarra bem.
     */
    public function fetch($id,$assoc=1)
    {
        $id = (int) $id;
        if($Data=$this->find(['*'=>['id=:id',"id={$id}"]],$assoc))
            return $Data;
    }


    /**
     * @method load() = Este método pesquisa o um registro e atribui seus dados ao atual objeto da classe
     * @param $id = id do registro a ser retornado.
     */
    private function load($id,$assoc=TRUE)
    {
        if($data=$this->find(['*'=>['id=:id',"id={$id}"]],$assoc))
            $this->Data = $data;

        return $data;
    }


    /**
     * @method remove() = Remove um objeto do banco de dados
     * @param null $custom = Inormações personalizadas da busca
     * @return bool = retorna verdadeiro caso tudo ocorra bem, e false caso contrário
     */
    public function remove($custom=null)
    {
        $this->sql('remove',$custom);
        try
        {
            $this->Stmt->execute($this->Places);
            $this->Places = [];
            if($rows=$this->Stmt->rowCount())
            {
                $this->Results = $rows;
                return true;
            }
        }
        catch(\PDOException $e)
        {
            echo("<code>(remove)::Não foi possível remover o registro: {$e->getMessage()}</code>");
            return false;
        }
    }


    /**
     * @method() query() = Este método é um bonus para a necessidade de se fazer uma consulta mais customizada no banco.
     * @param array $options = Array contendo no índice 0 a declaração sql e no índice 2,
     * os places caso solicitados pela sql.
     * @return bool verdadeiro caso tudo ocorra bem e falso, caso contrário. Pode retornar um Array de objetos.
     */
    public function query(array $options, $param=null)
    {
        $this->sql('query',$options);
        try
        {
            $this->Stmt->execute($this->Places);
            $this->Places = [];
            if($rows=$this->Stmt->rowCount())
            {
                $this->Results = $rows;

                if($this->getCommand()=='insert')
                    $this->LastInsertId = $this->Conn->lastInsertId();

                if($this->getCommand()=='select')
                {
                    if(!$param)
                        return $this->Stmt->fetchAll(\PDO::FETCH_FUNC,[$this,'fetchObject']);
                    else
                        return $this->Stmt->fetchAll(\PDO::FETCH_OBJ);
                }

                if($this->getCommand()=='show')
                    return $this->Stmt->fetchAll(\PDO::FETCH_OBJ);

                return true;
            }
        }
        catch(\PDOException $e)
        {
            echo("<code>(query)::Não foi possível executar a consulta: {$e->getMessage()}</code>");
            return false;
        }
    }


    /**
     * @method exists() = Verifica se uma determinada valor existe na coluna especificada da tabela em questão
     * @param $column = Coluna a ser verificada
     * @param $val = Valor a ser comparado
     * @return bool = Retorna verdadeiro caso tudo ocorra bem e false caso contrário.
     */
    public function exists($column, $val)
    {
        if($this->find(['first:id'=>["{$column}=:val","val={$val}"]]))
            return true;
        else
            return false;
    }


    /**
     * @method fetchObject() = Um calback a ser chamado para retornar os registros em objetos da classe atual
     * @param $id = Nome da coluna
     * @param $val = valor da coluna
     * @return mixed = Retorna um aray de objetos
     */
    public function fetchObject($id, $val=null)
    {
        $class = get_class($this);
        return new $class($id);
    }


    /**
     * @method data() = Retorna os dados da tabela em forma de array
     * @return array = Retorna o array com os campos da tabela contido na propriedade $Data
     */
    public function data()
    {
        return $this->Data;
    }


    /**
     * @method foreignKey() = Relaciona determinados objetos ao objeto atual
     * @param $name = Nome do objeto relacionado a ser carregado
     */
    private function relation($name, $arg=null)
    {
        $model = "App\\Models\\{$name}";
        $class = new $model;
        if($this->Fk<>'#')
            $this->$name = $class->all(["{$this->Fk}=:fk","fk={$this->id}"]);
        else
        {
            if($arg)
                $this->$name = $class->all(['id=:id',"id={$arg[0]}"],$arg[1]);
            else
                $this->$name = $class->all();
        }
    }


    /**
     * @method camelcase() = Recebe uma string e elimina os underlines, transformando a string
     * no formato de camelcase.
     * @param $string = String a ser convertida para o formato camelcase
     * @return string = Retorna a nova string
     */
    private function camelcase($string)
    {
        $string = substr($string,2);
        $name = '';
        for ($i = 0; $i < strlen($string); $i++)
        {
            $char = $string{$i};
            if($char=='_')
            {
                $string{$i+1} = strtoupper($string{$i+1});
                continue;
            }
            $name .= $char;
        }
        return rtrim($name,'s');
    }


    /**
     * @method getCommand() = Inspeciona a propriedade Query e retorna o nome do comando que realizou a ultima operação
     * @return string = Nome do comando executado na query
     */
    private function getCommand()
    {
        return strtolower(substr($this->Query,0,strpos($this->Query," ")));
    }


    /**
     * @method getSequence() = Pesquisa o último registro inserido no banco e retorna o seu id acrescido de mais um
     * @return int = Retorna o número do novo id a ser inserido no banco
     */
    public function getSequence()
    {
        $sequence = $this->query(["SELECT MAX(id) as id FROM this"]);
        $sequence = (int) $sequence[0]->id;
        return ($sequence+1);
    }


    /**
     * @method places() = Recebe uma query string e converte em places a serem utilizados na query
     * @param $string = Retorna os places organizados para serem processados no statements
     */
    private function places($string)
    {
        $string = (string) $string;
        $exp = explode('&', $string);
        foreach($exp as $val)
        {
            $str = explode('=', $val);
            if($str[0]=='limit' || $str[0]=='offset')
                $str[1] = (int) $str[1];

            $this->Places[$str[0]] = $str[1];
        }
    }


    /**
     * @method results() = Retorna o valor da propriedade que contém o número de linhas afetadas no banco
     * @return mixed =  Retorna o valor da propriedade Results.
     */
    public function results()
    {
        return $this->Results;
    }


    /**
     * @method = e() = retorna o objeto de erro que que foi criado
     * @return mixed = Retorna o objeto de erro atual
     */
    public function e()
    {
        return $this->Error;
    }


    /**
     * @method getLastInsertId() = Retorna o ultimo id inserido no banco
     * @return mixed = Retorna o valor da propriedade LastInsertId que contém o último id inserido no banco
     */
    public function getLastInsertId()
    {
        return $this->LastInsertId;
    }


    /**
     * @method new_record() = Verifica se existe um id na propriedade Data. Caso exista trata-se de uma atualização.
     * @return bool = retorna verdadeiro caso o índice id não exista na propriedade Data
     */
    private function new_record()
    {
        if(!array_key_exists('id', $this->Data) || $this->Data['id']==null)
            return true;
    }


    /**
     * @method debug() = Mostra informações do status do objeto
     */
    public function debug()
    {
        var_dump($this->Data, $this->Query, $this->getSequence());
    }


    /**
     * @param $name = Nome do método que está sendo chamado
     * @param $arguments = Parâmetros que serão passados
     * @return mixed = Retorna um objeto ou uma string
     */
    public function __call($name, $arguments)
    {
        if(in_array($name,$this->Orm) && !empty($arguments))
        {
            call_user_func([$this,'relation'], $name, $arguments);
            $data = $this->$name;
            return $data[0];
        }
        else
            echo $name;
    }


    /**
     * @method __set() = Método mágico responsável por interceptar as atribuições de propriedades do objeto.
     * @param $name = Nome da propriedade a ser criada
     * @param $val = Valor a ser atribuido
     */
    public function __set($name, $val)
    {
        if(in_array($name, array_keys($this->Data)))
            if(@strtolower($val)=='null')
                $this->Data[$name] = null;
            else
                $this->Data[$name] = $val;
        else
            $this->$name = $val;
    }


    /**
     * @method __get() = Método mágico responsável por interceptar a chamada de uma propriedade
     * @param $name = Propriedade a ser verificada
     * @return mixed = Retorna o valor da propriedade informada
     */
    public function __get($name)
    {
        if(in_array($name,$this->Orm))
            call_user_func([$this,'relation'], $name);

        if(array_key_exists($name,$this->Data))
        {
            return $this->Data[$name];
        }
        else
            return $this->$name;
    }

    public function __destruct()
    {
        $this->Places = null;
        $this->Stmt = null;
        $this->Data = [];
    }
}