<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

//Get All Customers

$app->get('/api/customers/cursos', function (Request $request, Response $response) {
    $objeto2 = new tpApp("cursos_processos_seletivos");
    $sql_objeto = $objeto2->obterTodos('*');
    while ($row = $objeto2->mysqliArray($sql_objeto)) {
        $resultado[] = $row;
    }
    echo json_encode($resultado);
});


$app->get('/api/customers/carregar_base_consultor', function (Request $request, Response $response) {
    $objeto2 = new tpApp("acesso_consultor");
    $sql_objeto = $objeto2->obterTodos('*');
    while ($row = $objeto2->mysqliArray($sql_objeto)) {
        $resultado[] = $row;
    }
    echo json_encode($resultado);
});

$app->get('/api/customers/cidade', function (Request $request, Response $response) {
    $objeto2 = new tpApp("app_cidade");
    $sql_objeto = $objeto2->obterTodos('*');
    while ($row = $objeto2->mysqliArray($sql_objeto)) {
        $resultado[] = $row;
    }
    echo json_encode($resultado);
});

$app->get('/api/customers/cursos_cidade', function (Request $request, Response $response) {
    $objeto2 = new tpApp("app_cursos");
    $sql_objeto = $objeto2->obterTodos('*');
    while ($row = $objeto2->mysqliArray($sql_objeto)) {
        $resultado[] = $row;
    }
    echo json_encode($resultado);
});

$app->get('/api/customers/processo_seletivo', function (Request $request, Response $response) {
    $objeto2 = new tpApp("app_processo_seletivo");
    $sql_objeto = $objeto2->obterTodos('id, nome, concurso_texto', "ativo=1");
    while ($row = $objeto2->mysqliArray($sql_objeto)) {
        $resultado[] = $row;
    }
    /*if($resultado == null){
		 $resultado = "Retornou vazio";
	 }*/
    echo json_encode($resultado);
});


//Get Single Customers
$app->get('/api/customers/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $objeto2 = new tpApp("acesso_consultor");
    $sql_objeto = $objeto2->obterTodos('*', "id=$id");
    while ($row = $objeto2->mysqliArray($sql_objeto)) {
        $resultado[] = $row;
    }
    echo json_encode($resultado);
});


//add  Customers
$app->post('/api/customers/criartabela', function (Request $request, Response $response) {
    $codigo_cliente = $request->getParam('codigo_cliente');
    $descricao_evento = $request->getParam('descricao_evento');
    $atributos = $request->getParam('atributos');

    $post = array(
        "codigo_cliente" => $codigo_cliente,
        "descricao_evento" => $descricao_evento
    );
    $msg = array(
        'msg' => "Dados gravados com sucesso!"
    );

    $objeto2 = new tpApp("tipo_evento_principal");
    $sql_objeto = $objeto2->insert($post);

    $id_tipo_evento = $objeto2->last_id;

    $objeto3 = new tpApp("atributos_tipo_evento");

    
    $array = array();
    $array['id_tipo_envento_principal'] =  $id_tipo_evento;
    foreach ($atributos as $key => $value) {
      $array['campo_atributo'] = $value; 
      $sql_objeto2 = $objeto3->insert($array);  
    }




    echo json_encode($msg);

    //echo json_encode($resultado);
});

$app->post('/api/customers/enviar', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    //$last_name = $request->getParam('category_name');

    $objeto = new tpApp("ingressos_vestibular");
    //$post['name'] = filter_var($data['name'], FILTER_SANITIZE_STRING);

    $c = 0;
    foreach ($data as $key => $val) {
        //$_POST['nome'] = $val['name'];


        $c += 1;
        unset($val['id']);
        unset($val['category_id']);
        unset($val['enviados']);
        $val['cidade'] = $val['cidade_nome'];
        unset($val['cidade_nome']);
        $val['processo_seletivo'] = $val['category_name'];
        unset($val['category_name']);
        $objeto->insert($val);
    }
    $s = $c > 1 ? 's' : '';

    $msg = array(
        'msg' => "Total de $c leed$s enviado$s"
    );




    echo json_encode($msg);

    // echo json_encode($resultado);
});

//Login
$app->post('/api/customers/auth/login', function (Request $request, Response $response) {
    $email = $request->getParam('email');
    $password = $request->getParam('password');


    $post = array(
        "email" => $email,
        "password" => $password
    );


    //$objeto2 = new tpApp("customers");

    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    $header = json_encode($header);
    $header = base64_encode($header);

    $payload = [
        'iss' => 'localhost',
        'password' => $password,
        'email' => $email
    ];

    $payload = json_encode($payload);
    $payload = base64_encode($payload);

    $signature = hash_hmac('sha256', "$header.$payload", 'minha-senha', true);
    $signature = base64_encode($signature);



    $msg = array(
        'msg' => "Dados gravados com sucesso!",
        'token' => $header . $payload . $signature
    );


    echo json_encode($msg);

    //echo json_encode($resultado);
});



//Update  Customers
$app->put('/api/customers/update/{id}', function (Request $request, Response $response) {

    $id = $request->getAttribute('id');
    $first_name = $request->getParam('first_name');
    $last_name = $request->getParam('last_name');

    $post = array(
        "first_name" => $first_name,
        "last_name" => $last_name
    );

    $objeto2 = new tpApp("customers");
    $sql_objeto = $objeto2->update($post, "id=" . $id);

    //echo json_encode($resultado);
});
$app->delete('/api/customers/delete/{id}', function (Request $request, Response $response) {

    $id = $request->getAttribute('id');



    $objeto2 = new tpApp("customers");
    $sql_objeto = $objeto2->delete("id=" . $id);

    //echo json_encode($resultado);
});
