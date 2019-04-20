<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\User;
use \Hcode\Model\Address;

$app->get('/', function() {

    $products = Product::listALL();

    $page = new Page();

    $page->setTpl("index", [
        'products' => Product::checkList($products)
    ]);
});

$app->get("/categories/:idcategory", function($idcategory) {

    $page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;

    $category = new Category();

    $category->get((int) $idcategory);

    $pagination = $category->getProductsPage($page);

    $pages = [];

    for ($i = 1; $i <= $pagination['pages']; $i++) {
        array_push($pages, [
            'link' => '/categories/' . $category->getidcategory() . '?page=' . $i,
            'page' => $i
        ]);
    }

    $page = new Page();

    $page->setTpl("category", [
        'category' => $category->getValues(),
        'products' => $pagination["data"],
        'pages' => $pages
    ]);
});

$app->get("/products/:desural", function($desurl) {
    $product = new Product();

    $product->getFromURL($desurl);

    $page = new Page();

    $page->setTpl("product-detail", [
        'product' => $product->getValues(),
        'categories' => $product->getCategories()
    ]);
});

$app->get("/cart", function() {

    $cart = Cart::getFromSession();

    $page = new Page();

    $page->setTpl("cart", [
        'cart' => $cart->getValues(),
        'products' => $cart->getProducts(),
        'error' => Cart::getMsgError()
    ]);
});

$app->get("/cart/:idproduct/add", function($idproduct) {

    $product = new Product();

    $product->get((int) $idproduct);

    $cart = Cart::getFromSession();

    $qtd = (isset($_GET['qtd'])) ? (int) $_GET['qtd'] : 1;

    for ($i = 0; $i < $qtd; $i++) {
        $cart->addProduct($product);
    }

    header("Location: /cart");
    exit;
});

$app->get("/cart/:idproduct/minus", function($idproduct) {

    $product = new Product();

    $product->get((int) $idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product);

    header("Location: /cart");
    exit;
});

$app->get("/cart/:idproduct/remove", function($idproduct) {

    $product = new Product();

    $product->get((int) $idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product, true);

    header("Location: /cart");
    exit;
});


$app->post("/cart/freight", function() {

    $cart = Cart::getFromSession();

    $cart->setFreight($_POST['zipcode']);

    header("Location: /cart");
    exit;
});

$app->get("/checkout", function() {

    User::verifyLogin(false);

    $address = new Address();
    
    $cart = Cart::getFromSession();

    if (isset($_GET['zipcode'])) {
        $_GET['zipcode'] = $cart->getdeszipcode();
    }

    if (isset($_GET['zipcode'])) {

        $address->loadFromCEP($_GET['zipcode']);

        $cart->setdeszipcode($_GET['zipcode']);

        $cart->save();

        $cart->getCalculateTotal();
    }


    if (!$address->getdesaddress()) $address->setdesaddress('');
    if (!$address->getdescomplement()) $address->setdescomplement('');
    if (!$address->getdesdistrict()) $address->setdesdistrict('');
    if (!$address->getdescity()) $address->setdescity('');
    if (!$address->getdesstate()) $address->setdesstate('');
    if (!$address->getdescountry()) $address->setdescountry('');
    if (!$address->getdeszipcode()) $address->setdeszipcode('');


    $page = new Page();

    $page->setTpl("checkout", [
        'cart' => $cart->getValues(),
        'address' => $address->getValues(),
        'products' => $cart->getProducts(),
        'error' => Address::getMsgError()
    ]);
});

$app->post("/checkout", function() {
    User::verifyLogin(false);

    if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
        Address::setMsgErro("INFORME O CEP.");

        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
        Address::setMsgErro("INFORME O ENDEREÇO.");

        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
        Address:: setMsgErro("INFORME O BAIRRO.");

        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['descity']) || $_POST['descity'] === '') {
        Address::setMsgErro("INFORME A CIDADE.");

        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
        Address::setMsgErro("INFORME O ESTADO.");

        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['descounty']) || $_POST['descounty'] === '') {
        Address::setMsgErro("INFORME O PAIZ.");

        header("Location: /checkout");
        exit;
    }

    $user = User::getFromSession();

    $address = new Address();

    $_POST['deszipcode'] = $_POST['zipcode'];
    $_POST['idperson'] = $user->getidperson();


    $address->setData($_POST);

    $address->save();

    header("Location: /order");
    exit;
});

$app->get("/login", function() {

    $page = new Page();

    $page->setTpl("login", [
        'error' => User::getError(),
        'errorRegister' => User::getErrorRegister(),
        'registerValues' => (isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name' => '', 'email' => '', 'phone' => '']
    ]);


    //header("Location: /cart");
    //exit;
});

$app->post("/login", function() {

    try {
        User::login($_POST['login'], $_POST['password']);
    } catch (Exception $e) {

        User::setErro($e->getMessage());
    }

    //$page->setTpl("login");

    header("Location: /checkout");
    exit;
});

$app->get("/logout", function() {

    User::logout();

    header("Location: /login");
    exit;
});

$app->post("/register", function() {

    $_SESSION['registerValues'] = $_POST;

    if (!isset($_POST['name']) || $_POST['name'] == '') {
        User::setErrorRegister("PREENCHA O SEU NOME.");

        header("Location: /login");
        exit;
    }

    if (!isset($_POST['email']) || $_POST['email'] == '') {
        User::setErrorRegister("PREENCHA O SEU EMAIL.");

        header("Location: /login");
        exit;
    }

    if (!isset($_POST['password']) || $_POST['password'] == '') {
        User::setErrorRegister("PREENCHA A SENHA.");

        header("Location: /login");
        exit;
    }

    if (User::checkLoginExist($_POST['email']) === true) {
        User::setErrorRegister("ESSE ENDEREÇO DE EMAIL JA ESTA SENDO USAO.");

        header("Location: /login");
        exit;
    }

    $user = new User();
    $user->setData([
        'inadmin' => 0,
        'deslogin' => $_POST['email'],
        'desperson' => $_POST['name'],
        'desemail' => $_POST['email'],
        'despassword' => $_POST['password'],
        'nrphone' => $_POST['phone'],
    ]);

    $user->save();

    User::login($_POST['email'], $_POST['password']);

    header("Location: /checkout");
    exit;
});

$app->get("/profile", function() {

    User::verifyLogin(false);

    $user = User::getFromSession();

    $page = new Page();

    $page->setTpl("profile", [
        'user' => $user->getValues(),
        'profileMsg' => User::getSuccess(),
        'profileError' => User::getError()
    ]);
});

$app->post("/profile", function() {

    User::verifyLogin(false);

    if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
        User::setErro("PRENCHA SEU NOME");

        header("Location: /profile");
        exit;
    }

    if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
        User::setErro("PRENCHA SEU E-MAIL");

        header("Location: /profile");
        exit;
    }

    $user = User::getFromSession();

    if ($_POST['desemail'] !== $user->getdesemail()) {

        if (User::checkLoginExist($_POST['desemail']) === true) {

            User::setErro("ESTE E-MAIL JA ESTA CADASTRADO.");

            header("Location: /profile");
            exit;
        }
    }



    $_POST['inadmin'] = $user->getinadmin();
    $_POST['despassword'] = $user->getdespassword();
    $_POST['deslogin'] = $_POST['desemail'];

    $user->setData($_POST);

    $user->save();

    Hcode\Model\User::setSuccess("DADOS ALTERADOS COM SUCESSO.");

    header("Location: /profile");
    exit;
});
