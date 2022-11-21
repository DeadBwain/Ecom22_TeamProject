<?php
namespace app\controllers;

class Login extends \app\core\Controller{

	public function home(){
		if(isset($_POST['action'])){
			$user = new \app\models\Login();
			$user = $user->get($_POST['username']);
			if(password_verify($_POST['password'], $user->password_hash)){
				$_SESSION['user_id'] = $user->user_id;
				$_SESSION['username'] = $user->username;
				$_SESSION['role'] = $user->role;
				$_SESSION['secret_key'] = $user->secret_key;
				$seller = $user->getSeller();
				$buyer = $user->getBuyer();
				$_SESSION['buyer_id'] = $buyer->buyer_id;
				$_SESSION['seller_id'] = $seller->seller_id;
				header('location:/Login/account');
			}else{
				/*how to localise that?*/
				header('location:/Login/index?error='._('Wrong username/password combination!'));
			}
		}else{
			$this->view('Login/index');
		}
	}

	public function check2fa(){
		if(!isset($_SESSION['user_id'])) header('location/Login/index');

		if(isset($_POST['action'])){
			$currentcode = $_POST['currentcode'];
		 if(\app\core\TokenAuth6238::verify(
		 	$_SESSION['secret_key'],$currentcode)){
		 	$_SESSION['secret_key'] = null;
		 	header('location:/Login/account');
		 }
		}else{
			$this->view('Login/check2fa');
		}
	}

	#[\app\filters\Login]
	public function account(){
		//password modification
		if(isset($_POST['action'])){
			//check the old password
			$user = new \app\models\Login();
			$user = $user->get($_SESSION['username']);
			if(password_verify($_POST['old_password'],$user->password_hash)){
				if($_POST['password'] == $_POST['password_confirm']){
					$user->password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
					$user->updatePassword();
					/*and this*/
					header('location:/Login/account?message='._('Password changed successfully.'));
				}else{
					/*and that*/
					header('location:/Login/account?error='._('Passwords do not match.'));
				}
			}else{ 
				/*and this*/
				header('location:/Login/account?error='._('Wrong old password provided.'));
			}
		}else{
			$this->view('Login/account');
		}
	}

	public function logout(){
		session_destroy();
		header('location:/Login/index');
	}

	public function register(){
		if(isset($_POST['action'])){//form submitted

			if($_POST['password'] == $_POST['password_confirm']){//match
				$user = new \app\models\Login();//TODO
				$check = $user->get($_POST['username']);
				if(!$check){
					$user->username = $_POST['username'];
					$user->password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
					$user->role = $_POST['role'];
					$user->insert();
					header('location:/Login/index');
				}else{
					/*this aswell */
					header('location:/Login/register?error='._('The username "'.$_POST['username'].'" is already in use. Select another.'));
				}
			}else{ 
				/*that too*/
				header('location:/Login/register?error='._('Passwords do not match.'));
			}

		}else{
			$this->view('Login/register');
		}

	}

	// #[\app\filters\Admin]
	// public function admin(){
	// 	echo "Yay!";
	// }
	// Use: /Default/makeQRCode?data=protocol://address
	//http://localhost/User/makeQRCode?data=ABC
	// data encode data you provide it
	public function makeQRCode(){
		$data = $_GET['data'];
		\QRcode::png($data);
	}

	#[\app\filters\Login]
	public function setup2fa(){
		 if(isset($_POST['action'])){
		 	$currentcode = $_POST['currentCode'];
		 if(\app\core\TokenAuth6238::verify(
		 	$_SESSION['secretkey'],$currentcode)){
		//the user has verified their proper 2-factor authentication setup
			 $user = new \app\models\Login();
			 $user->user_id = $_SESSION['user_id'];
			 $user->secret_key = $_SESSION['secretkey'];
			 $user->update2fa();
		 	 header('location:/Login/account');
		 }else{
		 	/*and finally this one*/
		     header('location:/Login/setup2fa?error='._('token not verified!'));//reload
		 	}
		 }else{
			 $secretkey = \app\core\TokenAuth6238::generateRandomClue();
			 $_SESSION['secretkey'] = $secretkey;
			 $url = \App\core\TokenAuth6238::getLocalCodeUrl($_SESSION['username'],
			 'Awesome.com', $secretkey,'Awesome App');
			 $this->view('Login/twofasetup', $url);
		 }
	}
}