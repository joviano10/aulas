<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;


//LISTA A TABELA
$app->get('/admin/users', function() {

    //VERIFICA SE USUARIO ESTA LOGADO
    User::verifyLogin();

    $users = User::listALL();

    $page = new PageAdmin();

    $page->setTpl("users", array(
        "users" => $users
    ));
});

//CRIA A TELA 
$app->get('/admin/users/create', function() {

    //VERIFICA SE USUARIO ESTA LOGADO
    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("users-create");
});

//METODO PARA DELETE TELA
$app->get('/admin/users/:iduser/delete', function($iduser) {

    //VERIFICA SE USUARIO ESTA LOGADO
    User::verifyLogin();

    $user = new User();

    $user->get((int) $iduser);

    $user->delete();

    header("Location: /admin/users");
    exit;
});

//UPADTE DA TELA
$app->get('/admin/users/:iduser', function($iduser) {

    //VERIFICA SE USUARIO ESTA LOGADO
    User::verifyLogin();

    $user = new User();

    $user->get((int) $iduser);

    $page = new PageAdmin();

    $page->setTpl("users-update", array(
        "user" => $user->getValues()
    ));
});

//FUNAO PARA SALVAR USUARIO ==ok
$app->post('/admin/users/create', function() {
    //VERIFICA SE USUARIO ESTA LOGADO
    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    $user->setData($_POST);

    $user->save();
    header("Location: /admin/users");
    exit;
});

//METODO PARA SALVAR EDIÇÃO TELA
$app->post('/admin/users/:iduser', function($iduser) {

    //VERIFICA SE USUARIO ESTA LOGADO
    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    $user->get((int) $iduser);

    $user->setData($_POST);

    $user->update();

    header("Location: /admin/users");
    exit;
});
