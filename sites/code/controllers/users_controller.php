<?php
    require_once("controllers/base_controller.php");
    require_once("models/User.php");
    require_once("helpers/users_helper.php");
    require_once("config/csrf.php");

    class UsersController extends BaseController {
        public function __construct() {
            $this->folder = "users";
        }

        public function index() {
            $users = User::all();            
            $this->render("index", ["users" => $users]);
        }

        public function login() {
            /*  ユーザーがログイン情ほを記録しているかをチェックする    */
            if (!empty($_COOKIE["email"])) {
                $_POST["email"] = $_COOKIE["email"];
                $_POST["password"] = $_COOKIE["password"];
                $_POST["save"] = "on";
            }

            /*  入力したフォームをチェックする */
            if (!empty($_POST)) {
                if (!empty($_POST["email"]) && !empty($_POST["password"])) {
                    $logged_in_user = User::loginUser(
                        $_POST["email"],
                        $_POST["password"]
                    );
                    
                    if (!empty($logged_in_user->id)) {
                        /*  ログイン成功    */
                        $_SESSION["id"] = $logged_in_user->id;
                        $_SESSION["current_user"] = $logged_in_user->user_name;
                        $_SESSION["time"] = time();

                        /*  ログイン情報を記録する  */
                        if ($_POST["save"] === "on")
                        {
                            setcookie("email", $_POST["email"], time()+3600);
                            setcookie("password", $_POST["password"], time()+3600);
                        }

                        header("Location: /");
                        exit();
                    }
                    /*  ログイン失敗    */
                    else {
                        $error = ["error" => "* ログインに失敗しました。正しくご記入ください！"];
                    }
                }
                else {
                    $error = ["error" => "* メールアドレスとパスワードをご記入ください！"];
                }
            }

            if (isset($error)) {
                $this->render("login", $error);
            }

            $this->render("login");
        }

        public function logout() {
            /*  セッション情報を削除    */
            $_SESSION = [];
            if (ini_get("session.use_cookies")){
                $params = session_get_cookie_params();
                setcookie(session_name(), "", time() - 3600,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]);
            }

            session_destroy();

            /*  Cookie情報も削除    */
            setcookie("email", "", time() - 3600);
            setcookie("password", "", time() - 3600);

            header("Location: /users/login");
        }

        public function register() {
            if (!empty($_POST)) {
                /*  入力したフォームをチェックする  */
                $form_error = UserHelper::formValidate($_POST);
                
                if (empty($form_error)) {
                    $_SESSION["new_user"] = $_POST;
                    $data = ["data" => $_SESSION["new_user"]];

                    $this->render("check", $data);
                }
            }

            if (isset($form_error)) {
                $this->render("register", ["form_errors" => $form_error]);
            }
            
            $this->render("register");
        }

        public function thank() {
            if (!empty($_POST) && !empty($_SESSION["new_user"])) {
                User::create(
                    $_SESSION["new_user"]["name"],
                    $_SESSION["new_user"]["email"],
                    $_SESSION["new_user"]["address"],
                    $_SESSION["new_user"]["password"]
                );

                session_unset();

                $this->render("thank");
            }

            session_unset();
            $errors = ["errors" => ["＊　ユーザー登録失敗でした！"]];

            $this->render("register", $errors);
        }
    }
