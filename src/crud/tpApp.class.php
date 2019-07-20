<?php

include_once "dbConnection.class.php";

class tpApp extends DbConnection {

    var $query;
    var $query_result;
    var $mes = array(null, "jan", "fev", "mar", "abr", "mai", "jun", "jul", "ago", "set", "out", "nov", "dez");
    var $num_solicitacao = 5;
    var $last_id;

    function __construct($TABLE = false, $VIEW = false) {

        $this->TABLE = $TABLE;
        // Se a view n�o for declarada os dados viram da tabela
        if (!$VIEW) {
            $this->VIEW = $this->TABLE;
        } else {
            $this->VIEW = $VIEW;
        }
    }

    function obterTodos($aFields = false, $aConditions = false, $anOrderBy = false, $aGroupby = false, $pagina = false, $limite = false) {

        $sqlTable = $this->VIEW;

        /* PAGINACAO */
        if (!$pagina) {
            $pagina = 1;
        }

        $offset = ($pagina - 1) * $limite;
        /* FIM PAGINACAO */

        if (!$aFields) {
            $aFields = "*";
        }
        $sqlQuery = "SELECT " . $aFields . " FROM " . $sqlTable;

        if (!empty($aConditions)) {
            $sqlQuery .= " WHERE " . $aConditions;
        }

        if (!empty($anOrderBy)) {
            $sqlQuery .= " ORDER BY " . $anOrderBy;
        }
        if (!empty($aGroupby)) {
            $sqlQuery .= " GROUP BY " . $aGroupby;
        }

        if ($pagina !== false and $limite !== false) {
            $pagina = intval($pagina);
            $limite = intval($limite);

            $sqlQuery .= " LIMIT $limite OFFSET $offset";
        }

        $this->query = $sqlQuery;
        //echo $sqlQuery."<br>";
        $this->query_result = $this->executarQuery($sqlQuery);
        //$this->_closeConnection();

        return $this->query_result;
    }

    public function row() {
        return @pg_fetch_object($this->query_result);
    }

    public function arr() {
        $array = array();
        $array = @pg_fetch_array($this->query_result);
        $array = @array_map("htmlspecialchars_decode", $array);
        return $array;
    }

    public function num() {
        if (!$this->query_result) {
            $this->query_result = $this->obterTodos();
        }
        return @pg_num_rows($this->query_result);
    }

    public function num_rows($aConditions = false) {
        $query_result = $this->obterTodos("", $aConditions);
        return @pg_num_rows($query_result);
    }

    public function exe_query($query) {
        $this->query_result = @pg_query($query);
        return $this->query_result;
    }

    function insert($array) {
        $conn = new DbConnection();

        $colunas = "";
        foreach ($array as $coluna => $valor) {
            if ($valor == 'null') {
                $colunas .= $coluna . ",
				";
                @$valores .= " " . $valor . ",
";
            } else {
                $colunas .= $coluna . ",
				";
                @$valores .= "'" . $valor . "',
"
                ;
            }
        }
        $colunas = substr(trim($colunas), 0, -1);
        $valores = substr(trim($valores), 0, -1);
        $querySql = "INSERT INTO {$this->TABLE}($colunas) VALUES($valores); ";
        $this->query = $querySql;
        //echo $querySql;
        $resultSql = $conn->executarQuery($querySql);
        $this->last_id = $conn->mysqliLastId();
        $conn->_closeConnection();

        return $resultSql;
    }

    function update($array, $cond = false) {

        $set = "";
        if ($cond) {
            $sqlCond = "WHERE " . $cond;
        }
        if (is_array($array)) {
            foreach ($array as $coluna => $valor) {
                if ($valor == 'null') {
                    $set .= $coluna . "=" . "" . $valor . ",";
                } else {
                    $set .= $coluna . "=" . "'" . $valor . "',";
                }
            }
        }

        $set = substr($set, 0, -1);

        $querySql = "UPDATE {$this->TABLE} SET {$set} {$sqlCond} ";
        $this->query = $querySql;
        //echo $querySql."<br>";
        $resultSql = $this->executarQuery($querySql);
        $this->_closeConnection();

        return $resultSql;
    }

    function delete($sqlConditions) {
        $conn = new DbConnection();
        $sqlTable = $this->TABLE;

        $querySql = "DELETE FROM " . $sqlTable . " WHERE " . $sqlConditions . ";";

        $this->query = $querySql;
        $resultSql = $conn->executarQuery($querySql);
        $conn->_closeConnection();

        return $resultSql;
    }

    // monta titulo dos menus
    function getTitle() {
        $urlname = explode("modulos", $_SERVER["REQUEST_URI"]);
        $filename = explode("?", $urlname[1]);
        $filename = str_replace("Frm", "Lst", $filename);
        return 'modulos' . $filename[0];
    }

    function titleMenu($url_destino) {
        if (is_string($url_destino)) {
            $cond = "url_destino='$url_destino'";
            $separador = false;
        } else {
            $separador = '<div class="separador_titulo"></div>';
            $cond = "id_menu=$url_destino";
        }

        $this->VIEW = 'public.vw_menu';
        $resultSql = $this->obterTodos("*", $cond);

        while ($array = $this->pgArray($resultSql)) {
            $url_destino = intval($array['id_menu_superior']);
            $this->titleMenu($url_destino);
            echo strtoupper($array['nome']) . $separador;
        }
    }

    // verifica dados contidos no post e file 
    function dataArray($post, $file = false) {

        foreach ($post as $key => $value) {
            if ($value) {

                $dados[$key] = htmlspecialchars($value);
                // tratamento para data
                if (strlen($value) == 10) {
                    $dateArray = explode("/", $value);
                    if (count($dateArray) == 3 and $dateArray[2] >= 1969) {
                        $dados[$key] = $dateArray[2] . '-' . $dateArray[1] . '-' . $dateArray[0];
                    }
                }
            }
        }

        if ($file) {
            foreach ($file as $chave => $valor) {
                if ($valor['name']) {
                    $dados[$chave] = htmlspecialchars(pg_escape_string($valor['name']));
                }
            }
        }

        $this->debug = $dados;
        //echo $dados;
        return $dados;
    }

    function data_array_nulls($post, $file = false) {

        foreach ($post as $key => $value) {
            if ($value) {

                $dados[$key] = htmlspecialchars($value);
                // tratamento para data
                if (strlen($value) == 10) {
                    $dateArray = explode("/", $value);
                    if (count($dateArray) == 3 and $dateArray[2] >= 1969) {
                        $dados[$key] = $dateArray[2] . '-' . $dateArray[1] . '-' . $dateArray[0];
                    }
                }
            } else {
                $dados[$key] = 'null';
            }
        }

        if ($file) {
            foreach ($file as $chave => $valor) {
                if ($valor['name']) {
                    $dados[$chave] = htmlspecialchars(pg_escape_string($valor['name']));
                }
            }
        }

        $this->debug = $dados;
        return $dados;
    }

    function paginacao($pg, $limite, $cond = false, $short = false) {

        // numeros de paginas que apareceram ao usuario
        if (!$short) {
            $short = 4;
        }
        // inicio
        if ($pg == 0) {
            $pg = 1;
        }
        // condicao de pagincao

        if ($cond) {
            $conditon = " WHERE " . $cond;
        }

        $sqlQuery = "SELECT * FROM " . $this->VIEW . $conditon;
        //PRINT $sqlQuery;
        $resultSql = $this->executarQuery($sqlQuery);
        $this->_closeConnection();
        $quantreg = $this->numRows($resultSql);

        $quant_pg = ceil($quantreg / $limite);
        $quant_pg++;

        // numeros de paginas que apareceram ao usuario
        if ($quant_pg < $short) {
            $short = -$quant_pg;
        }

        // ir a primeira pagina
        if ($pg > 1) {
            echo '<a href="?pg=1" class="a_pager">primeira </a>';
        } else {
            echo '<font class="f_pager">primeira </font>';
        }

        // Verifica se esta na primeira p�gina, se nao estiver ele libera o link para anterior
        if ($pg > 1) {
            echo '<a href="?pg=' . ($pg - 1) . '" class="a_pager"><b>&laquo; </b></a>';
            // deixa folga de 1 numero
            $inicio = $pg - 1;
        } else {
            $pg = 1;

            echo '<font class="f_pager">&laquo; </font>';

            // pagina inicia de 1
            $inicio = $pg;
        }

        // Faz aparecer os numeros das p�gina entre o ANTERIOR e PROXIMO
        for ($i_pg = $inicio; $i_pg < $quant_pg; $i_pg++) {
            // Verifica se a p�gina que o navegante esta e retira o link do n�mero para identificar visualmente
            if ($i_pg <= ($inicio + $short)) {
                if ($pg == $i_pg) {
                    echo '<a class="a_current">&nbsp;' . $i_pg . '&nbsp;</a>';
                } else {
                    $i_pg2 = $i_pg;
                    echo '&nbsp;<a href="?pg=' . $i_pg2 . '" class="a_pager">' . $i_pg . '</a>&nbsp;';
                }
            } else {
                continue;
            }
        }

        // Verifica se esta na ultima p�gina, se nao estiver ele libera o link para pr�xima
        if (( $pg + 1 ) < $quant_pg) {
            echo '<a href="?pg=' . ( $pg + 1 ) . '" class="a_pager"><b> &raquo;</b></a>';
        } else {
            echo '<font class="f_pager"> &raquo;</font>';
        }

        if (( $pg + 1 ) < $quant_pg) {
            echo '<a href="?pg=' . ( $quant_pg - 1 ) . '" class="a_pager"> �ltima</a>';
        } else {
            echo '<font class="f_pager"> �ltima </font>';
        }
    }

    function dataFormat($value, $format = false) {

        // numeric
        if (is_float($value + 1)) {

            $value = number_format($value, 2, ',', '.');
        }

        // is date
        if (strtotime($value)) {
            $time = strtotime($value);
            $value = date("$format", $time);
        }

        return $value;
    }

    public function dmy($value, $horas = false) {
        $time = strtotime($value);
        if ($horas) {
            return date("d/m/Y H:i:s", $time);
        } else {
            return date("d/m/Y", $time);
        }
    }

    /*
      $dias = $tpApp->dateDiff(
      "03/12/2012", Inicio
      "03/12/2012", Fim
      '/', Tipo de separador
      'days' tipo de retorno
      );

     */

    public function dateDiff($dateB, $dateS, $sep, $type = false) {
        $date1 = explode($sep, $dateB);
        $date2 = explode($sep, $dateS);

        switch ($type) {
            case 'years':
                $Q = 31104000;
                break;
            case 'months':
                $Q = 2592000;
                break;
            case 'days':
                $Q = 86400;
                break;
            case 'hours':
                $Q = 3600;
                break;
            case 'minutes':
                $Q = 60;
                break;
            default:
                $Q = 1;
        }

        $str = floor(((mktime(0, 0, 0, $date1[1], $date1[2], $date1[0]) - mktime(0, 0, 0, $date2[1], $date2[2], $date2[0])) / $Q));
        return $str;
    }

    /**
      | Informar o tempo entre duas datas
      |
      | @param $dataini data no formato dia/mes/ano
      | @param $datafim data no formato dia/mes/ano
      | @return object. propriedades do objeto days, hours,mins, secs
      |
     */
    public function time_interval($dataini, $datafim) {

        # Split para dia, mes, ano, hora, minuto e segundo da data inicial
        $_split_datehour = explode(' ', $dataini);
        $_split_data = explode("/", $_split_datehour[0]);
        $_split_hour = explode(":", $_split_datehour[1]);
        # Coloquei o parse (integer) caso o timestamp nao tenha os segundos, dai ele fica como 0
        $dtini = mktime($_split_hour[0], $_split_hour[1], (integer) $_split_hour[2], $_split_data[1], $_split_data[0], $_split_data[2]);

        # Split para dia, mes, ano, hora, minuto e segundo da data final
        $_split_datehour = explode(' ', $datafim);
        $_split_data = explode("/", $_split_datehour[0]);
        $_split_hour = explode(":", $_split_datehour[1]);
        $dtfim = mktime($_split_hour[0], $_split_hour[1], (integer) $_split_hour[2], $_split_data[1], $_split_data[0], $_split_data[2]);

        # Diminui a datafim que � a maior com a dataini
        $time = ($dtfim - $dtini);

        # Recupera os dias
        $days = floor($time / 86400);
        # Recupera as horas
        $hours = floor(($time - ($days * 86400)) / 3600);
        # Recupera os minutos
        $mins = floor(($time - ($days * 86400) - ($hours * 3600)) / 60);
        # Recupera os segundos
        $secs = floor($time - ($days * 86400) - ($hours * 3600) - ($mins * 60));

        # Monta o retorno no formato
        # 5d 10h 15m 20s
        # somente se os itens forem maior que zero
        $retorno = "";
        $retorno .= ($days > 0) ? $days . 'd ' : "";
        $retorno .= ($hours > 0) ? $hours . 'h ' : "";
        $retorno .= ($mins > 0) ? $mins . 'm ' : "";
        $retorno .= ($secs > 0) ? $secs . 's ' : "";

        # Se o dia for maior que 3 fica vermelho

        $time = array("days" => $days, "hours" => $hours, 'mins' => $mins, 'secs' => $secs);

        return (object) $time;
    }

    public function ext($name) {
        $n1 = substr($name, -5);
        $n2 = explode(".", $n1);
        return "." . $n2[1];
    }

    public function gerar_nome($key, $value, $num_solicitacao = false) {
        $hash = str_replace(array(" ", "."), "", microtime());

        $this->obterTodos("*", $key . "=" . $value);
        $rows = $this->row();

        $instApp = new tpApp("public.instituicao");
        $instApp->obterTodos("cod_instituicao_sapiens, id_instituicao", "id_instituicao=" . $rows->id_instituicao);
        $row = $instApp->row();

        if (!$num_solicitacao) {
            $num_solicitacao = $rows->num_solicitacao;
        }
        return $row->cod_instituicao_sapiens . "." . $num_solicitacao . "." . $hash;
    }

    public function get_modulo() {
        list($part1, $part2) = explode("sistemas/", $_SERVER["REQUEST_URI"]);
        list($modulo, $foo) = explode("/", $part2);
        return strtolower($modulo);
    }

}

?>
