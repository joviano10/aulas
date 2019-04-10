<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Mailer;

class User extends Model {

    const SESSION = "User";
    //CONTANTE
    const SECRET = "HcodePhp7_Secret10";

    public static function getFromSession() {
        $user = new User();

        if (isset($_SESSION[User::SESSION]) && (int) $_SESSION[User::SESSION]['iduser'] . 0) {
            $user->setData($_SESSION[User::SESSION]);
        }
        return $user;
    }

    public static function checkLogin($inadmin = true) {
        if (
                !isset($_SESSION[User::SESSION]) ||
                !$_SESSION[User::SESSION] ||
                !(int) $_SESSION[User::SESSION]["iduser"] > 0
        ) {
            //NAÕ ESTA LOGADO
            return false;
        } else {
            if ($inadmin === true && $_SESSION[User::SESSION]['inadmin'] === true) {

                return true;
            } else if ($inadmin === false) {

                return true;
            } else {
                return false;
            }
        }
    }

    public static function login($login, $password) {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(":LOGIN" => $login));

        if (count($results) === 0) {
            throw new \Exception("USUARIO INEXISTENTE OU INVALIDO");
        }
        $data = $results[0];

        if (password_verify($password, $data["despassword"]) === true) {
            $user = new User();
            //$user->setiduser($data["iduser"]);
            $user->setData($data);

            // var_dump($user);
            // exit;

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
        } else {
            throw new \Exception("USUARIO INEXISTENTE OU INVALIDO");
        }
    }

    public static function verifyLogin($inadmin = true) {
        if (User::checkLogin($inadmin)) {

            header("Location: /admin/login");
            exit;
        }
    }

    public static function logout() {
        $_SESSION[User::SESSION] = NULL;
    }

    public static function listALL() {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    public function save() {
        $sql = new Sql();

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => $this->getdespassword(),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($results[0]);
        //$sql->select("");
    }

    public function get($iduser) {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser" => $iduser
        ));

        $this->setData($results[0]);
    }

    public function update() {
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser" => $this->getiduser(),
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => $this->getdespassword(),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($results[0]);
        //$sql->select("");
    }

    public function delete() {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser" => $this->getiduser()
        ));
    }

    // public function getForgot($email, $inadmin = true) 
    public static function getForgot($email, $inadmin = true) {
        $sql = new Sql();
        $results = $sql->select("
         SELECT *
         FROM tb_persons a
         INNER JOIN tb_users b USING(idperson)
         WHERE a.desemail = :email;
     ", array(
            ":email" => $email
        ));
        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha", 1);
        } else {
            $data = $results[0];
            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser" => $data["iduser"],
                ":desip" => $_SERVER["REMOTE_ADDR"]
            ));
            if (count($results2) === 0) {
                throw new \Exception("Não foi possível recuperar a senha");
            } else {
                $dataRecovery = $results2[0];
                $dataRecovery = $results2[0];
                $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                $code = openssl_encrypt($dataRecovery['idrecovery'], 'aes-256-cbc', User::SECRET, 0, $iv);
                $result = base64_encode($iv . $code);
                if ($inadmin === true) {
                    $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$result";
                } else {
                    $link = "http://www.hcodecommerce.com.br/forgot/reset?code=$result";
                }
                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha da Hcode Store", "forgot", array(
                    "name" => $data["desperson"],
                    "link" => $link
                ));
                $mailer->send();
                return $link;
            }
        }
    }

}
