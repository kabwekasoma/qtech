<?php
	include("inc/api.php");
	include("inc/functions.php");
	class WEB_SERVICE extends API {
		
		//PRODUCTION
        const DB_SERVER = "localhost";
		const DB_USER = "apollo90_tpa_dev";
		const DB_PASSWORD = "vgopRNXATS_AVh@=2";
		const DB = "apollo90_qtechstore";
		//LOCAL SERVER	 
          /*const DB_SERVER = "localhost";
		const DB_USER = "root";
		const DB_PASSWORD = "";
		const DB = "qtechdb";*/

		
		public function __construct() {
			parent::__construct();
			$this->dbConnect();
			//$this->dbConnect();
			/*      
			//SECURITY MODULE BEFORE API ACCESS
			
			$APIKey = new Models\APIKey();
			$User = new Models\User();
			if (!array_key_exists('apiKey', $this->request)) {
				throw new Exception('No API Key provided');
			} else if (!$APIKey->verifyKey($this->request['apiKey'], $origin)) {
				throw new Exception('Invalid API Key');
			} else if (array_key_exists('token', $this->request) &&
				 !$User->get('token', $this->request['token'])) {

				throw new Exception('Invalid User Token');
			} 
			$this->User = $User;
			*/
		}
		private function dbConnect(){
			$this->db = mysql_connect(self::DB_SERVER,self::DB_USER,self::DB_PASSWORD) or die("Could not connect to the server. Contact the development team for assistance.");
			mysql_select_db(self::DB,$this->db) or die("Could not open the database. Contact the development team for assistance.");
		}
		//process API parser for all requests within the system
		public function processAPI(){

			$func = explode("/",strtolower($_REQUEST['request']));
			//handle cals made to the users/ directory
			if($func[0] == "users"){
				if($func[1] == "login"){
					$this->login();
				}
				else{
					$this->_response('','',404);
				}
			}
			//requests aimed at the /quotation directory
			else if($func[0] == "quotation"){
				if($func[1] == "requests"){
					if($func[2] == "add"){
						$this->add_quotation_request();
						//$this->add_quotation_request_demo();
					}
					else if($func[2] == "get"){
						if($func[3] == "all"){
							$this->get_all_quotation_requests();
						}else if($func[3] == "id"){
							$this->get_quotation_request_by_id();
						}else{
							$this->_response('','',404);
						}
					}
					else{
						$this->_response('','',404);
					}
				}
				else if($func[1] == "categories"){
					if($func[2] == "list"){
						$this->get_all_quotatation_types();
					}
					else{
						$this->_response('','',404);//invalid quotation types
					}
						
				}else{
					$this->_response('','',404);
				}
			}

			else if($func[0] == "notifications"){
				if($func[1] == "quotation"){
					if($func[2] == "requests"){
						$this->get_request_notifications();
					}else if($func[2] == "replies"){
						$this->get_reply_notifications();
					}
					else{
						$this->_response('','',404);
					}
				}
				else{
					$this->_response('','',404);
				}
			}
			//if requested path is not found throw a not found response.
			else{
				$this->_response('','',404);
			}	
		}
		/**
		 * user login end point
		 */
		 public function login_user() {
			 
			if ($this->_method() != 'POST') {
				$this->_response('','',406);
			}
			$this->_response('Apollo, everything is okay!','',200);			
		 }


         //add quotation request
         public function add_quotation_request(){

			if($this->_method() != 'POST') {
				$this->_response('','',406);
			} 
			if(!isset($_REQUEST["email"]) || empty($_REQUEST["email"])) {
				$this->_response('Missing details: Email','',400);
			}		
			if(!isset($_REQUEST["fullname"]) || empty($_REQUEST["fullname"])) {
				$this->_response('Missing details: Fullname','',400);
			}		
			if(!isset($_REQUEST["request_details"]) || empty($_REQUEST["request_details"])) {
				$this->_response('Missing details: SUpply request details','',400);
			}		
			if(!isset($_REQUEST["request_source"]) || empty($_REQUEST["request_source"])) {
				$this->_response('Missing details: Request source','',400);
			}		

			if(!isset($_REQUEST["quotation_id"]) || empty($_REQUEST["quotation_id"])) {
				$this->_response('Missing details: Quotation type','',400);
			}
			//check whether the requested quote is valid
 			$validate_quote_type = mysql_query("
				SELECT `id` 
				FROM quotation
				WHERE
				`id`='".$_REQUEST["quotation_id"]."'
			")or die(mysql_error());

			if(mysql_num_rows($validate_quote_type)<1){
				$this->_response('Illegal parameter: quotation type.','',400);
			}

			$get_quotation_type_id = mysql_fetch_assoc($validate_quote_type);
			$quotation_type_id = $get_quotation_type_id["id"];
				$user_id = null;
				//Begin transactions
				$this->begin();
				//AUTO CREATE USER ACCOUNT OR GET EXISTING ID
				$check_returning_client = mysql_query("SELECT `id` FROM user
					WHERE `email_address`='".$_REQUEST["email"]."' LIMIT 1",$this->db) or die(mysql_error());

				if(mysql_num_rows($check_returning_client)<1){
					//split the fullname
					$fullname=explode(" ",$_REQUEST["fullname"]);
					$fname=$fullname[0];
					$lname=$fullname[1];
					//if not found create the \school
					$create_user_query = mysql_query("
					INSERT INTO user(
						`first_name`,
						`last_name`,
						`email_address`,
						`role`,
						`date_created`,
						`activated`
					) 
					VALUES (
					'".$fname."',
					'".$lname."',
					'".$_REQUEST["email"]."',
					2,
					NOW(),
					0)") or die(mysql_error()." on line number ".__LINE__);	
					//if ins$institution_typeert fails roolback and throw and error
					if(mysql_affected_rows()<1){
						$this->rollback();
						$this->_response('An error occurred.','',400);
					}
					$user_id = mysql_insert_id();
				}else{
					//if found get the id
					$client_details_row = mysql_fetch_assoc($check_returning_client);
					$user_id = $client_details_row["id"];
				}

				//INSERT INTO GENERAL QUOTATIONS
				$insert_quotation_request_sql = "
					INSERT INTO 
					`quotation_request`(
					 `date_created`,
					 `created_by`,
					 `details`, 
					 `type`, 
					 `activated`) 

					VALUES (
					NOW(),
					".$user_id.",
					'".$_REQUEST["request_details"]."',
					'".$quotation_type_id."',
					1)";
				$insert_quotation_request = mysql_query($insert_quotation_request_sql)
				 or die(mysql_error()." on line number ".__LINE__);	

				if(mysql_affected_rows()<1){
					$this->rollback();
					$this->_response('Could not process request.','',400);
				}
	
				//commit all the changes
				$this->commit();
				//make an email copy to all intrested companies
	            //send out email
				$headers  = "From: hello@mutalecharles.com \r\n";
				$headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
				$subject = "New Quotation Request";
				//$emsg="A new quotation was submitted. <br>";
				//$emsg .="Type: Motor Insurance <br>Details:<br>Vehicle Details<br/>";
				//$emsg .="Year :".$_REQUEST["year"]."<br>Model :".$_REQUEST["model"]."<br>Make : ".$_REQUEST["make"]."<br>Description : ".$_REQUEST["description"]."<br>Cover :".$_REQUEST["cover"]."<br>License type:".$_REQUEST["license_type"]."<br>Driver type : regular<br/><br/>Customer Information<br>Name: ".$_REQUEST["fullname"]."<br>Email:".$_REQUEST["email"]."<br>ID:".$_REQUEST["id"]."<br>Mobile: 
				//$emsg .=''.$_SESSION['user_name'].' '.$_SESSION['last_name'].' at '.$_SESSION['user_email'].'.';
				$emsg ="
		<body leftmargin='0' marginwidth='0' topmargin='0' marginheight='0' offset='0' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;margin: 0;padding: 0;background-color: #DEE0E2;height: 100% !important;width: 100% !important;'>
		<center>
   			 <table align='center' border='0' cellpadding='0' cellspacing='0' height='100%' width='100%' id='bodyTable' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;margin: 0;padding: 0;background-color: #DEE0E2;border-collapse: collapse !important;height: 100% !important;width: 100% !important;'>
      		  <tr>
            <td align='center' valign='top' id='bodyCell' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;margin: 0;padding: 20px;border-top: 4px solid #BBBBBB;height: 100% !important;width: 100% !important;'>
                <!-- BEGIN TEMPLATE // -->
                <table border='0' cellpadding='0' cellspacing='0' id='templateContainer' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;width: 600px;border: 1px solid #BBBBBB;border-collapse: collapse !important;'>
                    <tr>
                        <td align='center' valign='top' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;'>
                            <!-- BEGIN PREHEADER // -->
                            <table border='0' cellpadding='0' cellspacing='0' width='100%' id='templatePreheader' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;background-color: #F4F4F4;border-bottom: 1px solid #CCCCCC;border-collapse: collapse !important;'>
                                <tr>
                                    <td valign='top' class='preheaderContent' style='padding-top: 10px;padding-right: 20px;padding-bottom: 10px;padding-left: 20px;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;color: #808080;font-family: Helvetica;font-size: 10px;line-height: 125%;text-align: left;' mc:edit='preheader_content00'>

                                        Quotation request.

                                    </td>
                                    <!-- *|IFNOT:ARCHIVE_PAGE|* -->
                                    <td valign='top' width='180' class='preheaderContent' style='padding-top: 10px;padding-right: 20px;padding-bottom: 10px;padding-left: 0;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;color: #808080;font-family: Helvetica;font-size: 10px;line-height: 125%;text-align: left;' mc:edit='preheader_content01'>
                                        Email not displaying correctly?<br><a href='*|ARCHIVE|*' target='_blank' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;color: #606060;font-weight: normal;text-decoration: underline;'>View it in your browser</a>.
                                    </td>
                                    <!-- *|END:IF|* -->
                                </tr>
                            </table>
                            <!-- // END PREHEADER -->
                        </td>
                    </tr>
                    <tr>
                        <td align='center' valign='top' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;'>
                            <!-- BEGIN HEADER // -->
                            <table border='0' cellpadding='0' cellspacing='0' width='100%' id='templateHeader' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;background-color: #F4F4F4;border-top: 1px solid #FFFFFF;border-bottom: 1px solid #CCCCCC;border-collapse: collapse !important;'>
                                <tr>
                                    <td valign='top' class='headerContent' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;color: #505050;font-family: Helvetica;font-size: 20px;font-weight: bold;line-height: 100%;padding-top: 0;padding-right: 0;padding-bottom: 0;padding-left: 0;text-align: left;vertical-align: middle;'>
                                        <img src='http://gallery.mailchimp.com/2425ea8ad3/images/header_placeholder_600px.png' style='max-width: 600px;-ms-interpolation-mode: bicubic;border: 0;height: auto;line-height: 100%;outline: none;text-decoration: none;' id='headerImage' mc:label='header_image' mc:edit='header_image' mc:allowdesigner mc:allowtext>
                                    </td>
                                </tr>
                            </table>
                            <!-- // END HEADER -->
                        </td>
                    </tr>
                    <tr>
                        <td align='center' valign='top' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;'>
                            <!-- BEGIN BODY // -->
                            <table border='0' cellpadding='0' cellspacing='0' width='100%' id='templateBody' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;background-color: #F4F4F4;border-top: 1px solid #FFFFFF;border-bottom: 1px solid #CCCCCC;border-collapse: collapse !important;'>
                                <tr>
                                    <td valign='top' class='bodyContent' mc:edit='body_content' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;color: #505050;font-family: Helvetica;font-size: 14px;line-height: 150%;padding-top: 20px;padding-right: 20px;padding-bottom: 20px;padding-left: 20px;text-align: left;'>
                                        <h1 style='display: block;font-family: Helvetica;font-size: 26px;font-style: normal;font-weight: bold;line-height: 100%;letter-spacing: normal;margin-top: 0;margin-right: 0;margin-bottom: 10px;margin-left: 0;text-align: left;color: #202020 !important;'>A new quotation request was submitted.</h1>
                                        <h3 style='display: block;font-family: Helvetica;font-size: 16px;font-style: italic;font-weight: normal;line-height: 100%;letter-spacing: normal;margin-top: 0;margin-right: 0;margin-bottom: 10px;margin-left: 0;text-align: left;color: #606060 !important;'>Powered by QuotationTech</h3>
                                     ".$_REQUEST["request_details"]."<br>
                                        <br>
                                        <br>
                                    </td>
                                </tr>
                            </table>
                            <!-- // END BODY -->
                        </td>
                    </tr>
                    <tr>
                        <td align='center' valign='top' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;'>
                            <!-- BEGIN FOOTER // -->
                            <table border='0' cellpadding='0' cellspacing='0' width='100%' id='templateFooter' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;background-color: #F4F4F4;border-top: 1px solid #FFFFFF;border-collapse: collapse !important;'>
                                <tr>
                                    <td valign='top' class='footerContent' mc:edit='footer_content00' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;color: #808080;font-family: Helvetica;font-size: 16px;line-height: 150%;padding-top: 20px;padding-right: 20px;padding-bottom: 20px;padding-left: 20px;text-align: left;'>
                                        <a href='http://openmindlabs.co/qtech/company/index.html' style='-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;color: green;font-weight: bold;text-decoration: underline;'>Respond to request</a>&nbsp;
                                    </td>
                                </tr>
                            </table>
                            <!-- // END FOOTER -->
					                        </td>
					                    </tr>
					                </table>
					                <!-- // END TEMPLATE -->
					            </td>
					        </tr>
					    </table>
					</center>
					</body>";

					mail("mutalecharles@gmail.com",$subject,$emsg,$headers);
					mail("cmusonda944@gmail.com",$subject,$emsg,$headers);
					mail("kasomakabwe@gmail.com",$subject,$emsg,$headers);
		        //response about successful
				$this->_response('Quotation inquiry successfully processed','',200);		

		}
		//get all quotation categories
		public function get_all_quotatation_types(){

		   	if(!isset($_REQUEST["request_source"]) || empty($_REQUEST["request_source"])) {
				$this->_response('Missing details','',400);
			}		
				$get_basic_info_sql = mysql_query("
					SELECT 
					`id` AS type_identifier,
					`name` AS type_full_name,
					`instructions` AS type_instructions
					FROM
				 `quotation`
				",$this->db)or die(mysql_error());
				
				if(mysql_num_rows($get_basic_info_sql)>0){
					$results = array();
					$results[] = array("type_identifier"=>"0","type_full_name"=>"Select a service","type_instructions"=>""); 
					while($row=mysql_fetch_assoc($get_basic_info_sql)){			
						$results[]=$row;
					}
					$response_data = array();
					$response_data = $this->json_api_prepare("list","success","",$results);
					$this->_response(json_encode($response_data),'JSON',200);
				}
				//if not available output "not found"
				$this->_response('No quotation types found.','',404);

		}
		public function add_quotation_request_demo(){

		}
         ////////////////////////////////////////////////////////////////////////////////////

			//transactions sql functions
			public function begin(){
				mysql_query("BEGIN");
			}

			public function commit(){
				mysql_query("COMMIT");
			}

			public function rollback(){
				mysql_query("ROLLBACK");
			}
			public function json_api_prepare($kind,$code_statement,$message,$data){
				$payload = array();
			
				if($data){
					$payload =array("kind"=>$kind,
					"code"=>$code_statement,
					"message"=>$message,
					"response"=>$data
					);
				}else{
					$payload =array("kind"=>$kind,
					"code"=>$code_statement,
					"message"=>$message);
				}
				return $payload;
			}

		}
		if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
			$_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
		} 
		try {
			$API = new WEB_SERVICE($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
			$API->processAPI();
		}
		catch (Exception $e) {
			echo json_encode(Array('error' => $e->getMessage())); 
		}
?>