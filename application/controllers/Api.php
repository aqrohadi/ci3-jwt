<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/JWT.php';
require APPPATH . '/libraries/Key.php';
require APPPATH . '/libraries/ExpiredException.php';
require APPPATH . '/libraries/BeforeValidException.php';
require APPPATH . '/libraries/SignatureInvalidException.php';
require APPPATH . '/libraries/JWK.php';

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use \Firebase\JWT\ExpiredException;

class Api extends RestController {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

	private function configToken(){
        $cnf['exp'] = 3600; //milisecond
        $cnf['secretkey'] = '2212336221';
        return $cnf;        
    }

	public function getToken_post() {
		// Periksa X-API-KEY
		// $api_key = $this->input->request_headers()['X-API-KEY'] ?? null;
		// if ($api_key !== '123456789abcdefghi') {
		// 	$this->response([
		// 		'Status' => false,
		// 		'Message' => 'Invalid API Key'
		// 	], RestController::HTTP_UNAUTHORIZED);
		// 	return;
		// }

		// Ambil data Basic Auth dari request
		$auth_user = $this->input->server('PHP_AUTH_USER');
		$auth_pass = $this->input->server('PHP_AUTH_PW');

		// Ambil API Key dari Header
		$api_key = $this->input->get_request_header('X-API-KEY');
	
		// Cek kredensial (gantilah dengan data dari database jika perlu)
		$valid_users = [
			'admin' => '12345' // Contoh username & password
		];

		// Daftar API Key yang valid
		$valid_api_keys = [
			'123456789abcdefghi' // Contoh API Key
		];
	
	
		// Validasi Basic Auth
		if (!isset($valid_users[$auth_user])) {
			$this->response([
				'Status' => false,
				'Message' => 'Username tidak ditemukan'
			], RestController::HTTP_UNAUTHORIZED);
			return;
		}
	
		if ($valid_users[$auth_user] !== $auth_pass) {
			$this->response([
				'Status' => false,
				'Message' => 'Password salah'
			], RestController::HTTP_UNAUTHORIZED);
			return;
		}
	
		 // Validasi API Key
		 if (!in_array($api_key, $valid_api_keys)) {
			$this->response([
				'Status' => false,
				'Message' => 'Invalid API Key'
			], RestController::HTTP_UNAUTHORIZED);
			return;
		}
		
		// Jika kredensial valid, buat token
		$exp = time() + 3600;
		$token = array(
			"iss" => 'apprestservice',
			"aud" => 'pengguna',
			"iat" => time(),
			"nbf" => time() + 10,
			"exp" => $exp,
			"data" => array(
				"username" => $auth_user,
				"password" => $auth_pass
			)
		);
	
		$jwt = JWT::encode($token, $this->configToken()['secretkey'], 'HS256');
	
		$output = [
			'status' => 200,
			'message' => 'Berhasil login',
			"token" => $jwt,
			"expireAt" => $token['exp']
		];
	
		$data = array('kode' => '200', 'pesan' => 'token', 'data' => array('token' => $jwt, 'exp' => $exp));
		$this->response($data, 200);
	}

	// public function authtoken() {
	// 	// Periksa X-API-KEY
	// 	$api_key = $this->input->request_headers()['X-API-KEY'] ?? null;
	// 	if ($api_key !== '123456789abcdefghi') {
	// 		return 'salah';
	// 	}
	
	// 	$secret_key = $this->configToken()['secretkey'];
	// 	$token = null;
	// 	$authHeader = $this->input->request_headers()['Authorization'] ?? null;
	
	// 	if (!$authHeader) {
	// 		return 'salah';
	// 	}
	
	// 	$arr = explode(" ", $authHeader);
	// 	$token = $arr[1] ?? null;
	
	// 	if ($token) {
	// 		try {
	// 			$decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
	// 			if ($decoded) {
	// 				return 'benar';
	// 			}
	// 		} catch (\Exception $e) {
	// 			$result = array('pesan' => 'Kode Signature Tidak Sesuai');
	// 			return 'salah';
	// 		}
	// 	}
	
	// 	return 'salah';
	// }
	
	public function authtoken() {
		$secret_key = $this->configToken()['secretkey'];
		$token = null;
		$authHeader = $this->input->request_headers()['Authorization'];
		$arr = explode(" ", $authHeader);
		$token = $arr[1];
	
		if ($token) {
			try {
				$decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
				if ($decoded) {
					return 'benar';
				}
			} catch (\Exception $e) {
				$result = array('pesan' => 'Kode Signature Tidak Sesuai');
				return 'salah';
			}
		}
	}

	public function siswa_get() {
		// Jika X-API-KEY atau token JWT tidak valid, kembalikan respons unauthorized
		if ($this->authtoken() == 'salah') {
			return $this->response([
				'kode' => '401',
				'pesan' => 'Unauthorized: Invalid API Key or Token',
				'data' => []
			], RestController::HTTP_UNAUTHORIZED);
		}
	
		// Jika semua valid, ambil data siswa
		$this->db->select('*');
		$data = array('data' => $this->db->get('siswa')->result());
		$this->response($data, RestController::HTTP_OK);
	}

	public function siswa_post(){
        $isidata = array('nis'=>$this->input->post('nis'), 'namasiswa'=>$this->input->post('nama'));
        $this->db->insert('siswa', $isidata);
        $this->response(array("pesan"=>"berhasil"), 200);
    }

	public function siswa_put(){                
        $isidata = array('namasiswa'=>$this->put('nama'));
        $this->db->where(array('nis'=>$this->put('nis')));
        $this->db->Update('siswa', $isidata);
        $this->response(array("pesan"=>"Ubah Data Berhasil"), 200);        
    }

	public function siswa_delete(){                        
        $this->db->where('nis', $this->delete('nis'));
        $this->db->delete('siswa');
        $this->response(array("pesan"=>"data berhasil dihapus"), 200);        
    }

	// Example
    public function users_get()
    {
        // Users from a data store e.g. database
        $users = [
            ['id' => 0, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 1, 'name' => 'Jim', 'email' => 'jim@example.com'],
        ];

        $id = $this->get( 'id' );

        if ( $id === null )
        {
            // Check if the users data store contains users
            if ( $users )
            {
				// Set the response and exit
                $this->response( $users, 200 );
            }
            else
            {
                // Set the response and exit
                $this->response( [
                    'status' => false,
                    'message' => 'No users were found'
                ], 404 );
            }
        }
        else
        {
            if ( array_key_exists( $id, $users ) )
            {
                $this->response( $users[$id], 200 );
            }
            else
            {
                $this->response( [
                    'status' => false,
                    'message' => 'No such user found'
                ], 404 );
            }
        }
    }
}
