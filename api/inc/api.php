<?php
	/*main abstract class that will extend the main api with core functionality*/
	class API{
		public $_allow = array();
		public $_request = array();
		private $_method = "";	
		private $_code=200;
		
		public function __construct(){
			$this->inputs();
		}
		public function _response($data,$data_type="", $status){
			$this->_code = ($status)?$status:200;
			if($data_type == "JSON"){
				$this->_data_type = "application/json";
			}else{
				$this->_data_type = "text/html";
			}
			header("HTTP/1.1 " . $this->_code . " " . $this->_requestStatus());
			header("Content-Type:".$this->_data_type);
			echo $data;
			exit;
		}
		private function inputs(){
			switch($this->_method()){
				case "POST":
					$this->_request = $this->_sanitize($_POST);
					break;
				case "GET":
				$this->_request = $this->_sanitize($_GET);
					break;
				case "DELETE":
					parse_str(file_get_contents("php://input"),$this->_request);
					$this->_request = $this->_sanitize($this->_request);
					break;
				case "PUT":
					parse_str(file_get_contents("php://input"),$this->_request);
					$this->_request = $this->_sanitize($this->_request);
					break;
				default:
					$this->_response('',406);
					break;
			}
		}
		private function _sanitize($data){
			$clean_input = Array();
			if (is_array($data)) {
				foreach ($data as $k => $v) {
					$clean_input[$k] = $this->_sanitize($v);
				}
			} else {
				$clean_input = trim(strip_tags($data));
			}
			return $clean_input;
		}
		public function _method(){
			if(!empty($_SERVER['REQUEST_METHOD'])){
				return $_SERVER['REQUEST_METHOD'];
			}
		}
		public function _requestStatus(){
			$status = array(  
				200 => 'OK',
				404 => 'Not Found',   
				401 => 'Unauthorized',   
				400 => 'Bad Request',   
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				500 => 'Internal Server Error',
			); 
			return ($status[$this->_code])?$status[$this->_code]:$status[500]; 
		}
	}
?>