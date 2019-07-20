<?php
class DbConnection{

	public		$_connection;
	//	var $_host	= '192.168.1.106';
	protected	$_host		= '127.0.0.1';
	protected	$_dbName	= 'qrcode';
	protected	$_user		= 'root';
	protected	$_password	= '';
        

    function _openConnection(){		
        if(!$this->_connection = @mysqli_connect($this->_host , $this->_user , $this->_password , $this->_dbName)){
			die('Não foi possível conectar ao nosso servidor.\nPor favor, tente novamente mais tarde!');
		}
        mysqli_query($this->_connection,"SET NAMES 'utf8'");
        mysqli_query($this->_connection,'SET character_set_connection=utf8');
        mysqli_query($this->_connection,'SET character_set_client=utf8');
        mysqli_query($this->_connection,'SET character_set_results=utf8');         
    }

    function _closeConnection(){
		$this->_connection = @mysqli_close($this->_connection);

		if(!$this->_connection){
			die('N�o foi poss�vel conectar ao nosso servidor.\nPor favor, tente novamente mais tarde!');
        }
    }
	
	function gerar_log($aSqlQuery)
	{
		$texto 		= trim(strtoupper($aSqlQuery));
		$palavra   = substr($texto,0,6);
		if(trim($palavra)=="UPDATE")
		{
			$palavra 	= strtoupper("SET")										;
			$pos 		= strpos($texto, $palavra)								;			
			$comando 	= substr($texto,0,$pos)									; 
			$comando	= str_replace("UPDATE","",$comando)						;
			$obs		= "Atualizou a tabela".$comando		;
		}
		else if(trim($palavra)=="INSERT")
		{
			$palavra 	= strtoupper("(")										;
			$pos 		= strpos($texto, $palavra)								;			
			$comando 	= substr($texto,0,$pos)									; 
			$comando	= str_replace("INTO","",$comando)						;
			$comando	= str_replace("INSERT","",$comando)						;
			$obs		= "Inseriu dados na tabela".$comando					;			
		}		
		else if(trim($palavra)=="DELETE")
			$obs		= "Excluiu dados na tabela, usou o seguinte comando ".$aSqlQuery	;
		if(strtoupper(substr($aSqlQuery,0,6))!="SELECT")
		{
			$id_usuario=$_SESSION["id_usuario"];
			$this->_openConnection();
			@pg_set_client_encoding($this->_connection, "LATIN1");
			$aSqlQuery = str_replace("'","",$aSqlQuery);
			$observacao="$aSqlQuery";
			$sql="INSERT INTO public.log_sistema(id_usuario,observacao,dml)Values($id_usuario,'$obs','$observacao')";
			return @mysqli_query($this->_connection, $sql);
		}
	}

	function executarQuery($aSqlQuery){
		$this->_openConnection();
		//@pg_set_client_encoding($this->_connection, "LATIN1");
		//$this->gerar_log($aSqlQuery);
		//echo $aSqlQuery;
		return @mysqli_query($this->_connection, $aSqlQuery);
	}
	
	function executarQueryUF8($aSqlQuery){
		$this->_openConnection();
		@pg_set_client_encoding($this->_connection, "UTF8");
		//$this->gerar_log($aSqlQuery);
		//echo $aSqlQuery;
		return @mysqli_query($this->_connection, $aSqlQuery);
	}

    function result($aResultSelect, $aLine, $aColumn){
    	return @pg_fetch_result($aResultSelect, $aLine, $aColumn);
    }

    function numRows($aResultSelect){
    	return @mysqli_num_rows($aResultSelect);
    }

	function numCols($aResultSelect){		
		return  @mysqli_num_fields($aResultSelect);		
    }	
	function mysqliArray($aResultSelect){		
		return  @mysqli_fetch_array($aResultSelect);		
    }
    function mysqliAssoc($aResultSelect){		
		return  @mysqli_fetch_assoc($aResultSelect);		
    }
    function mysqliObject($aResultSelect){		
		return  @mysqli_fetch_object($aResultSelect);		
	}
	function mysqliLastId(){		
		return  mysqli_insert_id($this->_connection);		
	}
	
	 

    function setVar($aVarName, $aValue){
        $this->$aVarName = $aValue;
    }

    function getVar($aVarName){
    	return $this->$aVarName;
    }
}
?>