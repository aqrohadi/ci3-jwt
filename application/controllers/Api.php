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
		// Generate Access Token
		$accessTokenExp = time() + 3600; // 1 jam
		$accessToken = array(
			"iss" => 'apprestservice',
			"aud" => 'pengguna',
			"iat" => time(),
			"nbf" => time() + 10,
			"exp" => $accessTokenExp,
			"data" => array(
				"username" => $this->input->post('username'),
				"password" => $this->input->post('password')
			)
		);
		$accessTokenJWT = JWT::encode($accessToken, $this->configToken()['secretkey'], 'HS256');
	
		// Generate Refresh Token
		$refreshTokenExp = time() + (7 * 24 * 3600); // 7 hari
		$refreshToken = array(
			"iss" => 'apprestservice',
			"aud" => 'pengguna',
			"iat" => time(),
			"exp" => $refreshTokenExp,
			"data" => array(
				"username" => $this->input->post('username')
			)
		);
		$refreshTokenJWT = JWT::encode($refreshToken, $this->configToken()['secretkey'], 'HS256');
	
		// Response
		$output = [
			'status' => 200,
			'message' => 'Berhasil login',
			"access_token" => $accessTokenJWT,
			"access_token_expire" => $accessTokenExp,
			"refresh_token" => $refreshTokenJWT,
			"refresh_token_expire" => $refreshTokenExp
		];
	
		$this->response($output, 200);
	}

	public function authtoken() {
		$secret_key = $this->configToken()['secretkey'];
		$token = null;
	
		// Ambil token dari header Authorization
		$authHeader = $this->input->request_headers()['Authorization'];
		$arr = explode(" ", $authHeader);
		$token = $arr[1];
	
		if ($token) {
			try {
				// Decode token
				$decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
	
				// Jika token valid, kembalikan data pengguna
				if ($decoded) {
					return $decoded->data; // Data pengguna dari token
				}
			} catch (\Exception $e) {
				// Jika token tidak valid
				$this->response(['status' => 401, 'message' => 'Token tidak valid atau kedaluwarsa'], 401);
			}
		} else {
			// Jika token tidak ditemukan
			$this->response(['status' => 400, 'message' => 'Token tidak ditemukan'], 400);
		}
	}

	public function refreshToken_post() {
		$secret_key = $this->configToken()['secretkey'];
		$refreshToken = $this->input->post('refresh_token');
	
		if ($refreshToken) {
			try {
				// Decode refresh token
				$decoded = JWT::decode($refreshToken, new Key($secret_key, 'HS256'));
	
				// Periksa apakah refresh token masih valid
				if ($decoded && $decoded->exp > time()) {
					// Generate access token baru
					$accessTokenExp = time() + 3600; // 1 jam
					$accessToken = array(
						"iss" => 'apprestservice',
						"aud" => 'pengguna',
						"iat" => time(),
						"nbf" => time() + 10,
						"exp" => $accessTokenExp,
						"data" => array(
							"username" => $decoded->data->username
						)
					);
					$accessTokenJWT = JWT::encode($accessToken, $secret_key, 'HS256');
	
					// Response
					$output = [
						'status' => 200,
						'message' => 'Berhasil memperbarui access token',
						"access_token" => $accessTokenJWT,
						"access_token_expire" => $accessTokenExp
					];
					$this->response($output, 200);
				} else {
					$this->response(['status' => 401, 'message' => 'Refresh token expired'], 401);
				}
			} catch (\Exception $e) {
				$this->response(['status' => 401, 'message' => 'Invalid refresh token'], 401);
			}
		} else {
			$this->response(['status' => 400, 'message' => 'Refresh token tidak ditemukan'], 400);
		}
	}

	public function siswa_get() {
		// Validasi access token
		$authResult = $this->authtoken();
	
		if ($authResult === 'salah') {
			// Jika token tidak valid atau kedaluwarsa
			return $this->response([
				'kode' => '401',
				'pesan' => 'Token tidak valid atau kedaluwarsa',
				'saran' => 'Gunakan refresh token untuk mendapatkan access token baru'
			], 401);
		}
	
		// Jika token valid, ambil data siswa
		$this->db->select('*');
		$data = array('data' => $this->db->get('siswa')->result());
		$this->response($data, 200);
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
