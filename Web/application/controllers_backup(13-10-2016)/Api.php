<?php
date_default_timezone_set('UTC');
 if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class API extends CI_Controller {	
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('api_model');
		$this->load->library('email');
		header('Access-Control-Allow-Credentials, True');
    	header('Access-Control-Allow-Origin, *');
        header ('Access-Control-Allow-Methods, POST, GET, OPTIONS, DELETE, PUT');
        header('Access-Control-Allow-Headers: Content-Type,X-Amz-Date,Authorization,X-Api-Key,x-requested-with');
        header ('Access-Control-Allow-Headers, x-requested-with, Content-Type, origin, authorization, accept, client-security-token');
    	header('Content-type: application/json');
	}
	
	/****************** First Hit API Function*************
	* Pre Settings
	*************************************/
	public function index()
	{			
		$data = file_get_contents("php://input");
		$data = preg_replace("/[\n\r]/","",$data); 
		$data = json_decode($data,true);
		//print_r($data); die;
		if(empty($data)){
			$data = $_REQUEST;
		}

		if(!empty($data) && is_array($data)) {
			foreach($data as $key=>$val){
				if(is_array($val))
					$data[$key]	= $val;
				else
					$data[$key]	= addslashes($val);
			}
			// convert to object.//
			//$tempdata = json_encode($data);
			//$data = json_decode($tempdata);
		}

		$functionName = '';
		$string	= array();

		if(isset($_GET['func'])){
			$functionName	= $_GET['func'];
		}

		if($functionName != ''){
			if((int)method_exists($this,$functionName) > 0 && isset($data['sec_token']) && $data['sec_token'] == "YHDgjy9Q7yuDFgTE")
				$string	= $this->$functionName($data);
			else{		
				$string[] = array('status'=>'Error','message'=>'Method not specifies in this web service');		
			}	
						
		}else{
			$string[] = array('status'=>'Error','message'=>'Please specify Method name');	
		}

		@header('content-type: application/json');
		echo json_encode($string);
		//die;	
	}	
	/********************************************************************************
	* function is used to set and get the password 
	*********************************************************************************/	
	function setPassword($password){
		if($password == "")
		{
			return "";
		}
		$password = base64_encode($password);
		$salt5 = mt_rand(10000,99999);		
		$salt3 = mt_rand(100,999);		
		$salt1 = mt_rand(0,25);
		$letter1 = range("a","z");
		$salt2 = mt_rand(0,25);
		$letter2 = range("A","Z");
		$password = base64_encode($letter2[$salt2].$salt5.$letter1[$salt1].$password.$letter1[$salt2].$salt3.$letter2[$salt1]);
		return str_replace("=", "#", $password);
	}
	
	function getPassword($password){
		$password = base64_decode(str_replace("#", "=", $password));
		return $password = base64_decode(substr($password,7,-5));				
	}

	/*************************************************************************** 
	 * function is used to check User exist or not By Email
	 * Params required: email.	 
	 * return true or false.
	 * used within class.
	 ****************************************************************************/
	function checkUserByEmail($email, $id_userapp){

		$where['email'] = $email;
		$where['id_userapp'] = $id_userapp;
		$arrAppUser=$this->AppCommon_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','usr_users'); // collecting Order list
		
		if(!empty($arrAppUser)) {		
			//if user with the user_name got found
			return true;
		}else{
			//if user with the user_name got not found
			return false;
		}
	}
	/*************************************************************************** 
	 * function is used to insert the record of app's installed devices 
	 * Params required: id_userapp, ud_id.	
	 * return status, message and id_user
	 ****************************************************************************/
	function appInstalled($data){
		//die($data);
		$response	= array();
		$requiredData['id_userapp'] = @trim($data['id_userapp']);
		$requiredData['imei_no'] = @trim($data['imei_no']);
		
		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}			
		}
		$where['imei_no'] = $requiredData['imei_no'];			
		$where['id_userapp'] = $requiredData['id_userapp'];			

		$arrInstalled=$this->AppCommon_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','usr_install'); // collecting Install devices
		
		if(!empty($arrInstalled)){
			
			$response[]	= array('status'=>'Success','message'=>'This device is already registered with this app.');	
			return $response;
		}else{			
			$requiredData['logtime'] = date('Y-m-d H:i:s');
			$idInsertDevice=$this->AppCommon_model->saveRecords($requiredData, 'usr_install');
			
			$response[]	= array('status'=>'Success','message'=>'Congrates!! New device registered successfully.');	
			return $response;
		}
	}
	/*************************************************************************** 
	 * function is used to login 
	 * Params required: email, password.	
	 * return status, message and id_user
	 ****************************************************************************/
	
	function staffLogin($data){
		//die($data);
		$response	= array();
		
		$requiredData['email'] = @trim($data['email']);
		$requiredData['password'] = @trim($data['password']);
		
		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}			
		}

		$where['email'] = $requiredData['email'];					

        $arrStaff=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_staff'); // collecting Order list

		if(!empty($arrStaff)){
			$password = $arrStaff[0]['password'];
			
			if(md5($requiredData['password']) == $password)
			{

				$response[]	= array('staff_id'=>$arrStaff[0]['id'],'org_id'=>$arrStaff[0]['admin_id'],'first_name'=>$arrStaff[0]['first_name'],'middle_name'=>$arrStaff[0]['middle_name'],'last_name'=>$arrStaff[0]['last_name'],'email'=>$requiredData['email'],'status'=>'Success','message'=>'User Login Successfully');						
				

				return $response;
			}
			else
			{
				$response[]	= array('status'=>'Error','message'=>'Invalid user email or password. Please try again');	
				return $response;					
			}

			//mail('ekta_vashisht@esferasoft.com', "$subject", $response);
		}else{
			$response[]	= array('status'=>'Error','message'=>'Invalid user email or password. Please try again');	
			return $response;

			//mail('ekta_vashisht@esferasoft.com', "$subject", $response);
		}
	}

	function getloginedUser($data){
		//die($data);
		$response	= array();
		
		$requiredData['userid'] = @trim($data['userid']);
		
		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}			
		}

		$userid = $requiredData['userid'];					

        $arruser=$this->api_model->getUserdata($userid); // collecting Order list

       // print_r($arruser);

		if(!empty($arruser)){
			$password = $arruser[0]['id'];
			$response[]	= array('status' => 'Success' ,'userid'=>$arruser[0]['id'],'org_id'=>$arruser[0]['admin_id'],'first_name'=>$arruser[0]['first_name'],'last_name'=>$arruser[0]['last_name'],'message'=>'User exists');						
			return $response;
		}
		else{
			$response[]	= array('status'=>'Error','message'=>'User doesnot not exist. Please try again');	
			return $response;
		}
	}
	


	/*************************************************************************** 
	 * function is used to login 
	 * Params required: mobilenum

	 * return status, message and id_user
	 ****************************************************************************/
	
	function userLogin($data){
		//die($data);
		$response	= array();
		
		$requiredData['mobilenum'] = @trim($data['mobilenum']);
		
		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}			
		}

		$where['mobile'] = $requiredData['mobilenum'];					

        $arrUser=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_user'); // collecting Order list

		// print_r($arrUser);
		// die();
		if(!empty($arrUser)){
			$response	= array('user_id'=>$arrUser[0]['id'],'org_id'=>$arrUser[0]['admin_id'],'username'=>$arrUser[0]['first_name']." ".$arrUser[0]['last_name'],'mobile'=>$requiredData['mobilenum'],'status'=>'Success','message'=>'User Login Successfully');						
			return $response;
			//mail('ekta_vashisht@esferasoft.com', "$subject", $response);
		}
		else{
			$response	= array('status'=>'Error','message'=>'Number Verify');	
			return $response;
			//mail('ekta_vashisht@esferasoft.com', "$subject", $response);
		}
	}


	/*************************************************************************** 
	 * function is used to register device 
	 * Params required: first_name,lase_name,phone,email,user_name,password.	 
	 * return status, message and user_id
	 ****************************************************************************/	
	function userRegistration($data){

		$requiredData['email'] = @trim($data['email']);
		$requiredData['user_name'] = @trim($data['user_name']);
		$requiredData['id_userapp'] = @trim($data['id_userapp']);
		$requiredData['ud_id'] = @trim($data['ud_id']);
		$requiredData['password'] = $this->setPassword(@trim($data['password']));
		
		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}
		}
		
		if($this->checkUserByEmail($requiredData['email'], $requiredData['id_userapp'])){
				$response[]	= array('status'=>'Error','message'=>'User already exists with this email '.$requiredData['email']);			
				return $response;
		}
		
		$requiredData['user_type'] = 1;
		$requiredData['inactive'] = 0;
		$requiredData['is_deleted'] = 0;
		$requiredData['auth_code'] = $data['password'];
		$requiredData['logtime'] = date('Y-m-d H:i:s');

		$idInsertUser=$this->AppCommon_model->saveRecords($requiredData, 'usr_users');

		$mail['user_name'] = $requiredData['user_name'];
		$mail['email'] = $requiredData['email'];
		$mail['password'] = $data['password'];

		$this->email->set_mailtype("html");							
		$this->email->set_newline("\r\n");		
		$email_body =$this->load->view('mails/store_registeration.tpl', $mail, true);
		$this->email->from('robin_garg@esferasoft.com', 'Esferasoft');
		$this->email->to($requiredData['email']);
		$this->email->subject('Welcome Mail');
		$this->email->message($email_body);							
		$this->email->send();

		$response[]	= array('id_user'=>$idInsertUser,'email'=>$requiredData['email'],'status'=>'Success','message'=>'New user registered successfully');						
		return $response;
	}

	/*************************************************************************** 
	 * function is used to get user detail
	 * Params required: id_user 
	 * return status, message and user_id
	 ****************************************************************************/	
	function getUserDetail($data){

		$requiredData['id_user'] = @trim($data['id_user']);

		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}
		}
		$arrAppUser=$this->AppCommon_model->getByID($requiredData['id_user'], $where='','usr_users'); // collecting product detail
				
		if(!empty($arrAppUser)){

			$user_details = array("name"=>$arrAppUser['user_name'], "email"=>$arrAppUser['email'], "address"=>isset($arrAppUser['address'])?$arrAppUser['address']:'');

			$response[]	= array('id_user'=>$requiredData['id_user'],'user_details'=>$user_details,'status'=>'Success','message'=>'Record Found');						
			return $response;
		}
		else
		{			
			$response[]	= array('id_user'=>$requiredData['id_user'],'status'=>'Error','message'=>'No Record Found');									
			return $response;

		}
	}
	/*************************************************************************** 
	 * function is used to edit user 
	 * Params required: id_user,lase_name,phone,email,user_name,password.	 
	 * return status, message and user_id
	 ****************************************************************************/	
	function editUser($data){

		$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['user_name'] = @trim($data['user_name']);
		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}
		}

		$requiredData['address'] = @trim($data['address']);

		$arrAppUser=$this->AppCommon_model->getByID($requiredData['id_user'], $where='','usr_users'); // collecting product detail
				
		if(!empty($arrAppUser)){

			$requiredData['id'] = $requiredData['id_user'];
			$requiredData['user_name'] = $requiredData['user_name'];
			$requiredData['address'] = $requiredData['address'];
			$requiredData['modifieddate'] = date('Y-m-d H:i:s');

			$idUpdateUser=$this->AppCommon_model->saveRecords($requiredData, 'usr_users');
			$response[]	= array('id_user'=>$requiredData['id_user'],'status'=>'Success','message'=>'User record update successfully');		
			return $response;
		}
		else
		{			
			$response[]	= array('id_user'=>$requiredData['id_user'],'status'=>'Error','message'=>'No Record Found');		
			return $response;
		}
	}

	/*************************************************************************** 
	 * function is used to generate unique token.
	 * Params required: token length.	 
	 * return randomKey.
	 ****************************************************************************/
	function generateRandomKey($l = 5) {
		$ctr = '0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';		
		$randomKey = '';
		for ($i = 0; $i < $l; $i++) {
			$randomKey .= $ctr[rand(0, strlen($ctr) - 1)];
		}
		return $randomKey;
	}
	/*************************************************************************** 
	 * function is used to send code to mail if user forgot Password
	 * Params required: email.	 
	 * return status, message
	 ****************************************************************************/
	
	function forgotPassword($data){
		$response	= array();				
		$requiredData['email'] = @trim($data['email']);	
		$requiredData['id_userapp'] = @trim($data['id_userapp']);	
		//$requiredData['user_type'] = @trim($data['user_type']);	
		
		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));
				return $response;
			}
		}
		// check user exist
		$where['email'] = $requiredData['email'];
		$where['id_userapp'] = $requiredData['id_userapp'];
		//$where['user_type'] = 1;		
        $arrAppUser=$this->AppCommon_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','usr_users'); // collecting user list

        if(!empty($arrAppUser))
		{
			$key = $this->generateRandomKey(5);

			$mail['key'] = $key;

			$this->email->set_mailtype("html");							
			$this->email->set_newline("\r\n");		
			$email_body =$this->load->view('mails/store_forgot_password.tpl', $mail, true);
			$this->email->from('robin_garg@esferasoft.com', 'Esferasoft');
			$this->email->to($requiredData['email']);
			$this->email->subject('Request of Forgot Password for AppPress account');
			$this->email->message($email_body);							
			if($this->email->send())
			{
				$updateData['id'] = $arrAppUser[0]['_id']->__toString();
				$updateData['token'] = $key;
				$updateData['modifieddate'] = date('Y-m-d H:i:s');

				$idUpdateUser=$this->AppCommon_model->saveRecords($updateData, 'usr_users');

				$response[]	= array('status'=>'Success','message'=>'Security code has been sent to your email id!');						
				return $response;
			
			}else{
				$response[]	= array('status'=>'Error','message'=>'Failed to send email please try again!');		
				return $response;
			}
			
		}else{
			$response[]	= array('status'=>'Error','message'=>'User does not exists with this email '.$requiredData['email']);				
			return $response;
		}		
	}

	/*************************************************************************** 
	 * function is used to reset if user forgot Password
	 * Params required: user_name.	 
	 * return status, message
	 ****************************************************************************/
	
	function passwordReset($data){
		//$response	= array();				
		$requiredData['email'] = @trim($data['email']);	
		$requiredData['password'] = $this->setPassword(@trim($data['password']));	
		$requiredData['id_userapp'] = @trim($data['id_userapp']);	
		$requiredData['token'] = @trim($data['token']);	
		
		foreach($requiredData AS $key=>$val){
			$val = trim($val);
			if($val == '' || $val == '0'){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}
		// check user exist
		$where['email'] = $requiredData['email'];
		$where['id_userapp'] = $requiredData['id_userapp'];		
		$where['token'] = $requiredData['token'];		
        $arrAppUser=$this->AppCommon_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','usr_users'); // collecting user list

        if(!empty($arrAppUser))
        {
			$updateData['id'] = $arrAppUser[0]['_id']->__toString();
			$updateData['password'] = $requiredData['password'];
			$updateData['auth_code'] = @trim($data['password']);
			$updateData['token'] = 0;
			$updateData['modifieddate'] = date('Y-m-d H:i:s');

			$idUpdateUser=$this->AppCommon_model->saveRecords($updateData, 'usr_users');

			$response[]	= array('id_user'=>$updateData['id'],'status'=>'Success','message'=>'Password has been reset successfully');						
			return $response;

		}else{
			$response[]	= array('status'=>'Error','message'=>'Wrong Email or Security token!');			
			return $response;
		}		
	}

	/*************************************************************************** 
	 * function is used to change Password
	 * Params required: user_id, password.	 
	 * return status, message
	 ****************************************************************************/
	
	function changePassword($data){			
		$requiredData['id_user'] = @trim($data['id_user']);	
		//$requiredData['old_password'] = @trim($data['old_password']);	
		$requiredData['new_password'] = $this->setPassword(@trim($data['new_password']));					
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}
		// check user exist
        $arrAppUser=$this->AppCommon_model->getByID($requiredData['id_user'], $where='','usr_users'); // collecting product detail
				
		if(!empty($arrAppUser)){
			//if($requiredData['old_password'] == $this->getPassword($arrAppUser['password'])){
				
			$mail['user_name'] = $arrAppUser['user_name'];
			$mail['email'] = $arrAppUser['email'];
			$mail['password'] = @trim($data['new_password']);


			$this->email->set_mailtype("html");							
			$this->email->set_newline("\r\n");		
			$email_body =$this->load->view('mails/change_password.tpl', $mail, true);
			$this->email->from('robin_garg@esferasoft.com', 'Esferasoft');
			$this->email->to($mail['email']);
			$this->email->subject('Change Password');
			$this->email->message($email_body);							
			if($this->email->send())
			{
				$updateData['id'] = $requiredData['id_user'];
				$updateData['password'] = $requiredData['new_password'];
				$updateData['auth_code'] = @trim($data['new_password']);
				$updateData['token'] = 0;
				$updateData['modifieddate'] = date('Y-m-d H:i:s');

				$idUpdateUser=$this->AppCommon_model->saveRecords($updateData, 'usr_users');

				$response[]	= array('id_user'=>$requiredData['id_user'],'status'=>'Success','message'=>'Password has been reset successfully');		
				return $response;
			
			}else{
				$response[]	= array('status'=>'Error','message'=>'Failed to send email please try again!');		
				return $response;
			}
				
			// }else{
			// 	$response[]	= array('status'=>'Success','message'=>'Wrong Old Password!');			
			// 	return $response;
			// }
		}else{
			$response[]	= array('status'=>'Success','message'=>'Wrong User ID!');			
			return $response;
		}		
	}
	/****************** List All Manage Events *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function listManageEvent($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['org_id'] = @trim($data['org_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
		$where['is_active'] = 1;		
        $arrEvents=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_event'); // collecting category list
        if(!empty($arrEvents))
        {
			foreach($arrEvents as $event)
			{
				$response[]	= array("id"=>$event["id"], "name"=>$event['name'], "description"=>$event['description'], "max_attendies"=>$event['max_attendies']);
			}
			return $response;			
		}
		else
		{
			$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
			return $response;
		}	
	}

	/****************** List All Events for Manage Activity *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function listEventsbydate($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['org_id'] = @trim($data['org_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}	
		
		$admin_id = $requiredData['org_id'];
        // $arrEvents=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_plan_event'); // collecting category list
		$where['admin_id'] = @trim($data['org_id']);
		$arrEvents = $this->api_model->get_allevent($where);

		if(!empty($arrEvents))
        {
        	foreach($arrEvents as $arrevent)
			{
				$rec_val = $arrevent['recurring'];
				$rec_explode = explode(',', $rec_val );
				$length = count($rec_explode);
				$recring = array();
				$db_days= array(
						'S' =>  'Sunday',
						'M' =>  'Monday',
						'T' =>  'Tuesday',
						'W' =>  'Wednesday',
						'Th' => 'Thursday',
						'F' =>  'Friday',
						'St' => 'Saturday',
					);
				foreach ($rec_explode  as $key2 => $day) 
				{
	                $recring[$day] = $db_days[$day];
	            }
	         
	            $meetup_date = $arrevent['meetup_date'];
				$end_date = $arrevent['end_date'];

				$range_date = array();
		  		// $begin =  new DateTime( $meetup_date);
      //      		$stop_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
      //      		$end = new DateTime( $stop_date  );
      //       	$daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);
				
				$event_id = $arrevent['event_id'];
				
    			$event_name=$this->api_model->getByID($event_id, $where ='','ci_event');
				$arrevent['event_name'] = $event_name['name'];

				$currentDateTime = $arrevent['meetup_date'] ." ". $arrevent['meetup_time'];
				$newDateTime = date('h:i A', strtotime($currentDateTime));
				$arrevent['event_time'] = $newDateTime;

				// print_r($arrevent);
				// die();
 				$today = date('Y-m-d');

				$expiry_event = $arrevent['end_date'];

				foreach ($recring as $key4 => $recring_val) 
	      		{ 
	      			$begin =  new DateTime( $meetup_date);
	           		$stop_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
	           		$end = new DateTime( $stop_date  );
	            	$daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);

					foreach($daterange as $key => $date)
		        	{
		        		$r_dates = $date->format("Y-m-d");
	        		 	if ($r_dates == $today) 
						{
						
							$timestamp = strtotime($r_dates);
		                    $r_day =  date("l", $timestamp);
		                    if ($r_dates == $meetup_date) {
		                    	$arrevent['event_date'] = date('l Y-m-d', strtotime($r_dates));
		                    	$response[$event_id] = $arrevent;
		                    }
		                    if (($r_day == $recring_val)) {
		                    	$arrevent['event_date'] = date('l Y-m-d', strtotime($r_dates));
		                    	$response[$event_id] = $arrevent;
		                    }
						}

						// else if ($r_dates >= $today) 
						// {
						// 	$response[] = array('status'=>'Error','message'=>'No Record Found');
						// }
						
					}
				}

			}
			if(!empty($response)){
			$responseArrayObject = new ArrayObject($response);
			$responseArrayObject->ksort();
			return array($responseArrayObject);
			}
			else
			{
				$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
				return $response;
			}
			



        }
		else
		{
			$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
			return $response;
		}

	
	}	
	/****************** Manage Meals *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function listMealbyday($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['org_id'] = @trim($data['org_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}			
		$admin_id = $requiredData['org_id'];
		$where['admin_id'] = $requiredData['org_id'];
		$query=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_plan_meal'); // collecting category list
			
		if (empty($query)) {
					$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
		return $response;
		}
			$db_days= array(
					'S' => 'Sunday',
					'M' => 'Monday',
					'T' => 'Tuesday',
					'W' => 'Wednesday',
					'Th' => 'Thursday',
					'F' => 'Friday',
					'St' => 'Saturday',
			);
			foreach($query as $key=>$meal)
			{
				$recurring_m = $meal['recurring'];
				$recurring_days = explode(",", $recurring_m);
				foreach($recurring_days as $key => $r_value) {

					$day = date('l');
					$today = date('l Y-m-d');
					// var n = d.getDay();
					if($db_days[$r_value] == $day){
					
					$location_id   = $meal['location_id'];
        			$location_name = $this->api_model->getByID($location_id, $where ='','ci_location');
					$response[$db_days[$r_value]][]	= array("id"=>$meal["id"], "name"=>$meal['name'], "meal_type"=>$meal['meal_type'], "description"=>$meal['description'], "location_id"=>$location_id, "location_name"=>$location_name['name'], "start_date"=>$meal['start_date'], "start_time"=>$meal['start_time'], "end_date"=>$meal['end_date'], "end_time"=>$meal['end_time'], "recurring"=>$meal['recurring'],"start_date" => $today);
				  }
				}	
			}	
			return array($response);	
	}



	/****************** List All Manage Events *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function listManageLocation($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['org_id'] = @trim($data['org_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0) {
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
		$where['is_active'] = 1;		
        $arrEvents=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_location'); // collecting category list
        if(!empty($arrEvents))
        {
			foreach($arrEvents as $event)
			{
				$response[]	= array("id"=>$event["id"], "name"=>$event['name']);
			}
			return $response;			
		}
		else
		{
			$response[]	= array('status'=>'Error','message'=>'No Records Found.');			
			return $response;
		}	
	}

	/****************** List All Users *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function getUserList($data)
	{			
		$requiredData['org_id'] = @trim($data['org_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
		$where['is_active'] = 1;		
        $arrUsers=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_user'); // collecting user list
        // print_r( $arrUsers);
        // die();
        if(!empty($arrUsers))
        {
			foreach($arrUsers as $user)
			{
				$response[]	= array("id"=>$user['id'], "name"=>$user['first_name'].' '.$user['last_name'], "mobile"=>$user['mobile']);
			}
			return $response;			
		}
		else
		{
			$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
			return $response;
		}	
	}

	private function getjoinedEventUsers($data)
	{			
		$requiredData['org_id'] = @trim($data['org_id']);
		$requiredData['event_id'] = @trim($data['event_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0) {
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}	
		
		$orgId = $requiredData['org_id'];
		$eventid = $requiredData['event_id'];
		// $where['is_active'] = 1;		
        $arrUsers=$this->api_model->getEventUsers($orgId,$eventid); // collecting user list
        // print_r( $arrUsers);
        // die();
        if(!empty($arrUsers))
        {
        	foreach ($arrUsers as $key => $user) {
	        	$user = $user['user_id'];
        		$arrUser=$this->api_model->getUserdata($user); // collecting user detail

				$response[] = array("id"=>$arrUser[0]['id'],"first_name"=>$arrUser[0]['first_name'],"last_name"=>$arrUser[0]['last_name'],"mobile"=>$arrUser[0]['mobile']);
	        }
			// foreach($arrUsers as $user)
			// {
			// 	$response[]	= array("id"=>$user['id'], "name"=>$user['first_name'].' '.$user['last_name']);
			// }
			return $response;			
		}
		else
		{
			$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
			return $response;
		}	
	}

	/****************** List Joined Meal Users *************
	* Params required: org_id.	 
	* return status, message
	*************************************/

	private function getjoinedMealUsers($data)
	{			
		$requiredData['org_id'] = @trim($data['org_id']);
		$requiredData['meal_id'] = @trim($data['meal_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0) {
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}	
		
		$orgId = $requiredData['org_id'];
		$mealid = $requiredData['meal_id'];
		// $where['is_active'] = 1;		
        $arrUsers=$this->api_model->getMealUsers($orgId,$mealid); // collecting user list
        // print_r( $arrUsers);
        // die();
        if(!empty($arrUsers))
        {
        	foreach ($arrUsers as $key => $user) {
	        	$user = $user['user_id'];
        		$arrUser=$this->api_model->getUserdata($user); // collecting user detail

				$response[] = array("id"=>$arrUser[0]['id'],"first_name"=>$arrUser[0]['first_name'],"last_name"=>$arrUser[0]['last_name']);
	        }
			// foreach($arrUsers as $user)
			// {
			// 	$response[]	= array("id"=>$user['id'], "name"=>$user['first_name'].' '.$user['last_name']);
			// }
			return $response;			
		}
		else
		{
			$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
			return $response;
		}	
	}

	/****************** List Joined Meal Users *************
	* Params required: org_id.	 
	* return status, message
	*************************************/


	private function attendEventUsers($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
        $requiredData['user_id'] = @trim($data['user_id']);
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
       	$arrEvent=$this->api_model->getByID($requiredData['id'], $where,'ci_plan_event'); // collecting event detail
       	$arrUser=$this->api_model->getByID($requiredData['user_id'], $where,'ci_user'); // collecting user detail
		// if(!empty($arrUser))        
		// {
			if(!empty($arrEvent))
			{ 
				
				$updateData['attend_users'] = $requiredData['user_id'];
		
				$updateData['id'] = $requiredData['id'];

				$id_record=$this->api_model->saveRecords($updateData, 'ci_plan_event');
	
				$response[]	= array('status'=>'Success','message'=>'Congrats!! You have been joined successfully.','id'=>$id_record);	

			}
			else
				$response[]	= array('status'=>'Error','message'=>'Records not found with this Event Id.');			
		// }
		// else
		// {
		// 	$updateData['attend_users'] = $requiredData['user_id'];
		
		// 	$updateData['id'] = $requiredData['id'];

		// 	$id_record=$this->api_model->saveRecords($updateData, 'ci_plan_event');
		// 	$response[]	= array('status'=>'Error','message'=>'Records not found with this User Id.');	
		// }
				
		return $response;
	}

	/****************** List Joined Meal Users *************
	* Params required: org_id.	 
	* return status, message
	*************************************/


	private function attendMealUsers($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
        $requiredData['user_id'] = @trim($data['user_id']);
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
       	$arrEvent=$this->api_model->getByID($requiredData['id'], $where,'ci_plan_meal'); // collecting event detail
       	$arrUser=$this->api_model->getByID($requiredData['user_id'], $where,'ci_user'); // collecting user detail
		// if(!empty($arrUser))        
		// {
			if(!empty($arrEvent))
			{ 
					$updateData['attend_users'] = $requiredData['user_id'];
						
					$updateData['id'] = $requiredData['id'];

					$id_record=$this->api_model->saveRecords($updateData, 'ci_plan_meal');
					
					$response[]	= array('status'=>'Success','message'=>'Congrats!! You have been joined successfully.','id'=>$id_record);
			}
			else
				$response[]	= array('status'=>'Error','message'=>'Records not found with this Event Id.');			
		// }
		// else
		// {
		// 	$response[]	= array('status'=>'Error','message'=>'Records not found with this User Id.');	
		// }
				
		return $response;
	}

	/****************** Delete Attend Users *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function deleteattendEventUsers($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
        $requiredData['user_id'] = @trim($data['user_id']);
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
       	$arrEvent=$this->api_model->getByID($requiredData['id'], $where,'ci_plan_event'); // collecting event detail
       	$arrUser=$this->api_model->getByID($requiredData['user_id'], $where,'ci_user'); // collecting user detail
		if(!empty($arrUser))        
		{
			if(!empty($arrEvent))
			{ 
				if($arrEvent['attend_users'] != '')
				{

					$arrEventUsers = explode(",",$arrEvent['attend_users']);
					if(in_array($requiredData['user_id'], $arrEventUsers))
					{
						// $event_name=$this->api_model->getrangeByID($where,'ci_plan_eventmeta');
						$where1 = array(
							'id' => $requiredData['id']
						);
				  //   	if (!empty($arrEventUsers)) 
				  //   	{
				  //   		$this->api_model->rangedelete($where1,'ci_plan_event');
				  //   	}
						// print_r($requiredData['user_id']);
						$arrUsers = array_diff($arrEventUsers, array($requiredData['user_id']));
						//$response[]	= array('status'=>'Error','message'=>'Sorry!! You have already joined this meal.','id'=>$requiredData['id'],'user_id'=>$requiredData['user_id']);
						$updateData['attend_users']  = implode(",",$arrUsers);
						$id_record=$this->api_model->update_range_data($where1,$updateData, 'ci_plan_event');

						$response[]	= array('status'=>'Success','message'=>'User deleted');	
						return $response;

					}
					
				}
				
			}
			else
				$response[]	= array('status'=>'Error','message'=>'Records not found with this Event Id.');			
		}
		else
		{
			$response[]	= array('status'=>'Error','message'=>'Records not found with this User Id.');	
		}
				
		return $response;
	}

	/****************** Delete Attend Meal Users *************
	* Params required: org_id.	 
	* return status, message
	*************************************/


	private function deleteattendMealUsers($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
        $requiredData['user_id'] = @trim($data['user_id']);
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
       	$arrEvent=$this->api_model->getByID($requiredData['id'], $where,'ci_plan_meal'); // collecting event detail
       	$arrUser=$this->api_model->getByID($requiredData['user_id'], $where,'ci_user'); // collecting user detail
		if(!empty($arrUser))        
		{
			if(!empty($arrEvent))
			{ 
				if($arrEvent['attend_users'] != '')
				{

					$arrEventUsers = explode(",",$arrEvent['attend_users']);
					if(in_array($requiredData['user_id'], $arrEventUsers))
					{
						// $event_name=$this->api_model->getrangeByID($where,'ci_plan_eventmeta');
						$where1 = array(
							'id' => $requiredData['id']
						);
				  //   	if (!empty($arrEventUsers)) 
				  //   	{
				  //   		$this->api_model->rangedelete($where1,'ci_plan_event');
				  //   	}
						// print_r($requiredData['user_id']);
						$arrUsers = array_diff($arrEventUsers, array($requiredData['user_id']));
						//$response[]	= array('status'=>'Error','message'=>'Sorry!! You have already joined this meal.','id'=>$requiredData['id'],'user_id'=>$requiredData['user_id']);
						$updateData['attend_users']  = implode(",",$arrUsers);
						$id_record=$this->api_model->update_range_data($where1,$updateData, 'ci_plan_meal');

						$response[]	= array('status'=>'Success','message'=>'User deleted');	
						return $response;

					}
					
				}
				
			}
			else
				$response[]	= array('status'=>'Error','message'=>'Records not found with this Event Id.');			
		}
		else
		{
			$response[]	= array('status'=>'Error','message'=>'Records not found with this User Id.');	
		}
				
		return $response;
	}

	/****************** Selected Event RangeDate *************
	* Params required: plan_meal_id,user_id.	 
	* return rangedate, message
	*************************************/
	private function selectedattendUsers($data)
	{			
		$requiredData['id'] = @trim($data['id']);
		// $requiredData['user_id'] = @trim($data['user_id']);

		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}

        $where = array(
				'id' => $requiredData['id'],
				// 'week_day ' => $week_day 
		);
		
		$event_plan_id = $requiredData['id'];
        $selected_data = $this->api_model->getrangeByID($where,'ci_plan_event');
        
		if(!empty($selected_data))
		{
			foreach ($selected_data as $key => $value){
				$arrAttend = explode(",",$value['attend_users']);
					// $userid = $user[]

				foreach($arrAttend as $userid)
				{
					$response['users'][] =  array("userid" => $userid);
				}
				$response[] = array("status" => "success","message" => "Record found");
			}
		}

	
	return $response;
	}

	private function selectedattendmealUsers($data)
	{			
		$requiredData['id'] = @trim($data['id']);
		// $requiredData['user_id'] = @trim($data['user_id']);

		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}

        $where = array(
				'id' => $requiredData['id'],
				// 'week_day ' => $week_day 
		);
		
		$event_plan_id = $requiredData['id'];
        $selected_data = $this->api_model->getrangeByID($where,'ci_plan_meal');
        
		if(!empty($selected_data))
		{
			foreach ($selected_data as $key => $value){
				$arrAttend = explode(",",$value['attend_users']);
					// $userid = $user[]

				foreach($arrAttend as $userid)
				{
					$response['users'][] =  array("userid" => $userid);
				}
				$response[] = array("status" => "success","message" => "Record found");
			}
		}

	
	return $response;
	}



	/****************** List All Events *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function listActivities($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['org_id'] = @trim($data['org_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}	
		
		$admin_id = $requiredData['org_id'];
        // $arrEvents=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_plan_event'); // collecting category list
		$where['admin_id'] = @trim($data['org_id']);
		$arrEvents = $this->api_model->get_allevent($where);
		if(!empty($arrEvents))
        {
        	foreach($arrEvents as $arrevent)
			{
				
				$rec_val = $arrevent['recurring'];
				$rec_explode = explode(',', $rec_val );
				$length = count($rec_explode);
				$recring = array();
				$db_days= array(
						'S' =>  'Sunday',
						'M' =>  'Monday',
						'T' =>  'Tuesday',
						'W' =>  'Wednesday',
						'Th' => 'Thursday',
						'F' =>  'Friday',
						'St' => 'Saturday',
					);
				foreach ($rec_explode  as $key2 => $day) 
				{
	                $recring[$day] = $db_days[$day];
	            }
	         
	            $meetup_date = $arrevent['meetup_date'];
				$end_date = $arrevent['end_date'];

				$range_date = array();
		  		// $begin =  new DateTime( $meetup_date);
      //      		$stop_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
      //      		$end = new DateTime( $stop_date  );
      //       	$daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);
				
				$event_id = $arrevent['event_id'];
				
    			$event_name=$this->api_model->getByID($event_id, $where ='','ci_event');
				$arrevent['event_name'] = $event_name['name'];

				$currentDateTime = $arrevent['meetup_date'] ." ". $arrevent['meetup_time'];
				$newDateTime = date('h:i A', strtotime($currentDateTime));
				$arrevent['event_time'] = $newDateTime;

				// print_r($arrevent);
				// die();
 				$today = date('Y-m-d');

				$expiry_event = $arrevent['end_date'];

				foreach ($recring as $key4 => $recring_val) 
	      		{ 
		      		$begin =  new DateTime( $meetup_date);
	           		$stop_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
	           		$end = new DateTime( $stop_date  );
	            	$daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);

					foreach($daterange as $key => $date)
		        	{
		        		$r_dates = $date->format("Y-m-d");
	        		 	if ($r_dates == $today) 
						{
						
							$timestamp = strtotime($r_dates);
		                    $r_day =  date("l", $timestamp);
		                    if ($r_dates == $meetup_date) {
		                    	$arrevent['event_date'] = date('l Y-m-d', strtotime($r_dates));
		                    	$response[$event_id] = $arrevent;
		                    }
		                    if ( ($r_day == $recring_val) ) {
		                    	$arrevent['event_date'] = date('l Y-m-d', strtotime($r_dates));
		                    	$response[$event_id] = $arrevent;
		                    }
	             
						}
					}
				}

			}
			
			$responseArrayObject = new ArrayObject($response);
			$responseArrayObject->ksort();

			return array($responseArrayObject);	

        }
		else
		{
			$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
			return $response;
		}

	}


	private function send_users_sms($data)
    {
        $this->load->library('plivo');
        
        $this->load->model('user_model');

        $userId   =  @trim($data['user_id']);
        $admin_id =  @trim($data['org_id']);
        $message   =  @trim($data['message']);

        // $admin_id = $this->session->userdata['logged_in']['admin_id'];
        $data['user_info']=$this->user_model->get_user(array("id" => $userId, "admin_id" => $admin_id));

        // print_r($data['user_info']);
        // die();
        $mobileNo = $this->input->post('Ph_number',TRUE);
        $site_url = base_url('index.php/applink');
        $resendsms_data = array(
            'src' => '+15125663933', //The phone number to use as the caller id (with the country code). E.g. For USA 15671234567
            'dst' => '+1'.$data['user_info']['mobile'], // The number to which the message needs to be send (regular phone numbers must be prefixed with country code but without the ‘+’ sign) E.g., For USA 15677654321.
            'text' => $message, // The text to send
            'type' => 'sms', //The type of message. Should be 'sms' for a text message. Defaults to 'sms'
            'url' => base_url() . 'index.php/plivo_test/receive_sms', // The URL which will be called with the status of the message.
            'method' => 'POST', // The method used to call the URL. Defaults to. POST
        );
 
        /*
         * look up available number groups
         */
        $response_array = $this->plivo->send_sms($resendsms_data);

        if ($response_array[0] == '200')
        {
            $data["response"] = json_decode($response_array[1], TRUE);
            // print_r($data["response"]);
            $this->session->set_flashdata('flash_message', 'Message Send successfully');
            redirect(site_url('user'));

            return  $data["response"];

        }
        else if($response_array[0] == '202'){
        	$data["response"] = json_decode($response_array[1], TRUE);

            $this->session->set_flashdata('flash_message', 'Message Send successfully');
            redirect(site_url('user'));

            return  $data["response"];

        }
        else
        {
            /*
             * the response wasn't good, show the error
             */
            $this->api_error($response_array);

            $response = array('status'=> 'Error','message'=> 'Message failed to sent');
        }   
    }

    private function send_activitysms($data)
    {
        $this->load->library('plivo');
        
        $this->load->model('user_model');

        $eventid   =  @trim($data['event_id']);
        $orgId =  @trim($data['org_id']);
        $message =  @trim($data['message']);

        // $data['user_info']=$this->user_model->get_user(array("id" => $userId, "admin_id" => $admin_id));
        $arrUsers=$this->api_model->getEventUsers($orgId,$eventid);
        

        if(!empty($arrUsers))
        {

        	// $mobileNo = "" ;
        	$count = 1;
        	foreach($arrUsers as $key => $user) {
	        	$user      = $user['user_id'];
        		$arrUser   = $this->api_model->getUserdata($user); // collecting user detail
        	
        		$result_count = count($arrUsers);

				$mobileNo .= '+1'.$arrUser[0]['mobile'];

		        if($count < $result_count) {
		                $mobileNo .= '<';
		        }
		        $count++;
		    }
		}
	        	
		   echo $mobileNo;
		   		
		

        // $site_url = base_url('index.php/applink');
        $sms_data = array(
            'src'    => '+15125663933', //The phone number to use as the caller id (with the country code). E.g. For USA 15671234567
            // 'src'    => '+917696568274',
            'dst'    =>  $mobileNo, // The number to which the message needs to be send (regular phone numbers must be prefixed with country code but without the ‘+’ sign) E.g., For USA 15677654321.
            'text'   =>  $message, // The text to send
            'type'   =>  'sms', //The type of message. Should be 'sms' for a text message. Defaults to 'sms'
            'url'    =>  base_url() . 'index.php/plivo_test/receive_sms', // The URL which will be called with the status of the message.
            'method' =>  'POST', // The method used to call the URL. Defaults to. POST
        );
       
 
        /*
         * look up available number groups
         */

        $response_array = $this->plivo->send_sms($sms_data);

        // print_r($response_array[1]);

        if ($response_array[0] == '200')
        {
            $data["response"] = json_decode($response_array[1], TRUE);
            return $data["response"];
        }
        else if($response_array[0] == '202'){
            $this->session->set_flashdata('flash_message', 'Message Send successfully');
            redirect(site_url('user'));
            $data["response"] = json_decode($response_array[1], TRUE);
            return $data["response"];
        }
        // else
        // {
        //     /*
        //      * the response wasn't good, show the error
        //      */
        //     $this->api_error($response_array[1]);
        //     $response = array('status'=> 'Error','message'=> 'Message failed to sent');

        // } 
           
    }


    private function send_allusers($data)
    {
        $this->load->library('plivo');
        
        $this->load->model('user_model');

        $orgId =  @trim($data['org_id']);
        $message =  @trim($data['message']);

        // $data['user_info']=$this->user_model->get_user(array("id" => $userId, "admin_id" => $admin_id));
        $arrUsers=$this->api_model->getUserById($orgId);
        // print_r($arrUsers);
        // die();
        

        if(!empty($arrUsers))
        {

        	// $mobileNo = "" ;
        	$count = 1;
        	foreach($arrUsers as $key => $user) {
	        	$mobileNo   .= '+1'.$user['mobile'];
        		$result_count = count($arrUsers);

		        if($count < $result_count) {
		             $mobileNo .= '<';
		        }
		        $count++;
		    }
		}		   		
		

        // $site_url = base_url('index.php/applink');
        $sms_data = array(
            'src'    => '+15125663933', //The phone number to use as the caller id (with the country code). E.g. For USA 15671234567
            'dst'    => $mobileNo, // The number to which the message needs to be send (regular phone numbers must be prefixed with country code but without the ‘+’ sign) E.g., For USA 15677654321.
            'text'   => $message, // The text to send
            'type'   => 'sms', //The type of message. Should be 'sms' for a text message. Defaults to 'sms'
            'url'    => base_url() . 'index.php/plivo_test/receive_sms', // The URL which will be called with the status of the message.
            'method' => 'POST', // The method used to call the URL. Defaults to. POST
        );
        
 
        /*
         * look up available number groups
         */

        $response_array = $this->plivo->send_sms($sms_data);

        // print_r($response_array[0]);

        if ($response_array[0] == '200')
        {
            $data["response"] = json_decode($response_array[1], TRUE);

             print_r($data["response"]);
        }
        else if($response_array[0] == '202'){
            $this->session->set_flashdata('flash_message', 'Message Send successfully');
            redirect(site_url('user'));
            $data["response"] = json_decode($response_array[1], TRUE);

            print_r($data["response"]);

        }
        else
        {
            /*
             * the response wasn't good, show the error
             */
            $this->api_error($response_array);
        } 
           
    }


	/****************** List All Events *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function listEvent($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['org_id'] = @trim($data['org_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}	
		
		$admin_id = $requiredData['org_id'];
        // $arrEvents=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_plan_event'); // collecting category list
		$where['admin_id'] = @trim($data['org_id']);
		$arrEvents = $this->api_model->get_allevent($where);

		//print_r($arrEvents);
		//exit;

		if(!empty($arrEvents))
        {
        	foreach($arrEvents as $arrevent)
			{
				$rec_val = $arrevent['recurring'];
				$rec_explode = explode(',', $rec_val );
				$length = count($rec_explode);
				$recring = array();
				$db_days= array(
						'S' =>  'Sunday',
						'M' =>  'Monday',
						'T' =>  'Tuesday',
						'W' =>  'Wednesday',
						'Th' => 'Thursday',
						'F' =>  'Friday',
						'St' => 'Saturday',
					);
				foreach ($rec_explode  as $key2 => $day) 
				{
	                $recring[$day] = $db_days[$day];
	            }
	        
	             $meetup_date = $arrevent['meetup_date'];
				 $end_date = $arrevent['end_date'];

				 $meetup_time = $arrevent['meetup_time'];

				 $range_date = array();

				// $UTC = new DateTimeZone("UTC");
				// $newTZ = new DateTimeZone("US/Pacific");
				// $date = new DateTime(date("Y-m-d H:i:s"), $UTC );
				// $date->setTimezone($newTZ);
				// $date->format('Y-m-d H:i:s');

				// $time = gmmktime();
                // echo date("Y-m-d H:i:s", $time); 

			
				$event_id = $arrevent['event_id'];
				
    			$event_name=$this->api_model->getByID($event_id, $where ='','ci_event');
				$arrevent['event_name'] = $event_name['name'];


				$currentDateTime = $arrevent['meetup_date'] ." ". $arrevent['meetup_time'];
				$newDateTime = date('h:i A', strtotime($currentDateTime));
				$arrevent['event_time'] = $newDateTime;

				//print_r($arrevent);
				//die();
 				 $today = date('Y-m-d');

				$expiry_event = $arrevent['end_date'];

				foreach ($recring as  $recring_val) 
	      		{ 
	      			
		  		$begin =  new DateTime( $meetup_date);
           		$stop_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
           		$end = new DateTime( $stop_date);
            	$event_daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);

					foreach($event_daterange as $event_dates)
		        	{
		        		// $dates = new DateTime($date);

		        		 $r_dates = $event_dates->format("Y-m-d");
		        		// echo $r_dates.'-';
	        		 	if($r_dates >= $today) 
						{
							//print_r($arrevent);
							 $timestamp = strtotime($r_dates);
		                     $r_day =  date("l", $timestamp);
		                   
		                    if ($r_dates == $meetup_date) {
		                    	$response[$r_dates][$meetup_time] = array($arrevent);
		                    }
		                    if ( ($r_day == $recring_val) ) {
		                    	$response[$r_dates][$meetup_time] = array($arrevent);
		                    }
						}
					}
				}
			}
			if (empty($response)) {
				# code...
				$response[]	= array('status'=>'Error','message'=>'No Record Found.');		
				return $response;
			}
			else{
				$responseArrayObject = new ArrayObject($response);
				$responseArrayObject->ksort();
				return array($responseArrayObject);	
			}
		

			

        }
		else
		{
			$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
			return $response;
		}

	}
	/****************** List All Meals *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function listMeal($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['org_id'] = @trim($data['org_id']);
		if(trim($requiredData['org_id']) == '' || trim($requiredData['org_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify Organisation Id');
			return $response;
		}			
		$admin_id = $requiredData['org_id'];
		$where['admin_id'] = $requiredData['org_id'];
		$query=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_plan_meal'); // collecting category list
			
		if (empty($query)) {
					$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
		return $response;
		}
			$db_days= array(
					'S' => 'Sunday',
					'M' => 'Monday',
					'T' => 'Tuesday',
					'W' => 'Wednesday',
					'Th' => 'Thursday',
					'F' => 'Friday',
					'St' => 'Saturday',
				);
			foreach($query as $key=>$meal)
			{
			
				$recurring_m = $meal['recurring'];
				$recurring_days = explode(",", $recurring_m);
				foreach ($recurring_days as $key => $r_value) {
					
					$location_id = $meal['location_id'];
        			$location_name=$this->api_model->getByID($location_id, $where ='','ci_location'); // collecting category list
					
					$response[$db_days[$r_value]][]	= array("id"=>$meal["id"], "name"=>$meal['name'], "meal_type"=>$meal['meal_type'], "description"=>$meal['description'], "location_id"=>$location_id, "location_name"=>$location_name['name'], "start_date"=>$meal['start_date'], "start_time"=>$meal['start_time'], "end_date"=>$meal['end_date'], "end_time"=>$meal['end_time'], "recurring"=>$meal['recurring']);
			
					}	
			}	
			return array($response);	
	}

	
	/****************** Single Event *************
	* Params required: org_id.
	* return status, message
	*************************************/
	private function singleEvent($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
	
		foreach($requiredData AS $key=>$val) {		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
       	$arrEvent=$this->api_model->getByID($requiredData['id'], $where,'ci_plan_event'); // collecting product detail
		if(!empty($arrEvent))        
		{   
			$event_id = $arrEvent['event_id'];
			$location_id = $arrEvent['location_id'];
			$event_name=$this->api_model->getByID($event_id, $where ='','ci_event'); // collecting category list
			$location_name=$this->api_model->getByID($location_id, $where ='','ci_location'); // collecting category list
			$response[] = array("id"=>$arrEvent["id"], "event_id"=>$event_id, "event_name"=>$event_name['name'], "description"=>$arrEvent['description'], "location_id"=>$location_id, "location_name"=>$location_name['name'], "max_attendies"=>$arrEvent['max_attendies'], "meetup_date"=>$arrEvent['meetup_date'], "meetup_time"=>$arrEvent['meetup_time'], "end_date"=>$arrEvent['end_date'], "end_time"=>$arrEvent['end_time'], "recurring"=>$arrEvent['recurring']);
		}	
		else
			$response[]	= array('status'=>'Error','message'=>'No Records Found.');			
				
		return $response;
	}
	/****************** Single Meal *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function singleMeal($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
       	$arrMeal=$this->api_model->getByID($requiredData['id'], $where,'ci_plan_meal'); // collecting product detail
		if(!empty($arrMeal))  
		{
			$location_id = $arrMeal['location_id'];
			$location_name=$this->api_model->getByID($location_id, $where ='','ci_location'); // collecting category list

			$response[]	= array("id"=>$arrMeal["id"], "name"=>$arrMeal['name'], "description"=>$arrMeal['description'], "location_id"=>$location_id, "location_name"=>$location_name['name'], "start_date"=>$arrMeal['start_date'], "start_time"=>$arrMeal['start_time'], "end_date"=>$arrMeal['end_date'], "end_time"=>$arrMeal['end_time'], "recurring"=>$arrMeal['recurring']); 
		}
		else
			$response[]	= array('status'=>'Error','message'=>'No Record Found.');			
				
		return $response;
	}
	/****************** Single Join Event User List *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function joinEventUserList($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
		$requiredData['singledate'] = @trim($data['singledate']);
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$response = array();
		$admin_id = $requiredData['org_id'];
		$where['admin_id'] = $requiredData['org_id'];
		$eventid  = $requiredData['id'];
		$evendate = $requiredData['singledate'];

       	$arrEvent = $this->api_model->getEventByID($eventid,$evendate); // collecting event detail

        if(!empty($arrEvent)){
	        foreach ($arrEvent as $key => $user) {
	        	$user = $user['user_id'];
        		$arrUser=$this->api_model->getUserdata($user); // collecting user detail

        		if($arrUser[0]['first_name'] !=null && $arrUser[0]['last_name'] != null)
                {
				   $response[] = array("id"=>$arrUser[0]['id'],"first_name"=>$arrUser[0]['first_name'],"last_name"=>$arrUser[0]['last_name']);
	            }
	        }
	    }
	    else{
			$response[]	= array('status'=>'Error','message'=>'No User Joined!!');	
	    }
	    return $response;

	}
	/****************** Single Join Event User List *************
	* Params required: org_id.	 
	* return status, message
	*************************************/
	private function joinMealUserList($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$response = array();
		$admin_id = $requiredData['org_id'];
		$where['admin_id'] = $requiredData['org_id'];
		$mealid  = $requiredData['id'];
       	$arrMeal = $this->api_model->getMealByID($mealid); // collecting event detail

        if(!empty($arrMeal)){
	        foreach ($arrMeal as $key => $user) {
	        	$user = $user['user_id'];
        		$arrUser=$this->api_model->getUserdata($user); // collecting user detail

				$response[] = array("id"=>$arrUser[0]['id'],"first_name"=>$arrUser[0]['first_name'],"last_name"=>$arrUser[0]['last_name']);
	        }
	    }
	    else{
			$response[]	= array('status'=>'Error','message'=>'No User Joined!!');	
	    }
	    return $response;
		
	}
	/****************** Join Event *************
	* Params required: org_id, event_id, user_id.	 
	* return status, message
	*************************************/
	private function joinEvent($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
		$requiredData['user_id'] = @trim($data['user_id']);
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
       	$arrEvent=$this->api_model->getByID($requiredData['id'], $where,'ci_plan_event'); // collecting event detail
       	$arrUser=$this->api_model->getByID($requiredData['user_id'], $where,'ci_user'); // collecting user detail
		if(!empty($arrUser))        
		{
			if(!empty($arrEvent))
			{ 
				if($arrEvent['list_users'] != '')
				{

					$arrEventUsers = explode(",",$arrEvent['list_users']);
					if(in_array($requiredData['user_id'], $arrEventUsers))
					{
						$response[]	= array('status'=>'Error','message'=>'Sorry!! You have already joined this event.','id'=>$requiredData['id'],'user_id'=>$requiredData['user_id']);
							return $response;
					}
					$updateData['list_users'] = $arrEvent['list_users'].','.$requiredData['user_id'];
				}
				else
					$updateData['list_users'] = $requiredData['user_id'];
					
				$updateData['id'] = $requiredData['id'];

				$id_record=$this->api_model->saveRecords($updateData, 'ci_plan_event');
				
				$response[]	= array('status'=>'Success','message'=>'Congrats!! You have been joined successfully.','id'=>$id_record);	
			}
			else
				$response[]	= array('status'=>'Error','message'=>'Records not found with this Event Id.');			
		}
		else
		{
			$response[]	= array('status'=>'Error','message'=>'Records not found with this User Id.');	
		}
				
		return $response;
	}
	/****************** Join Meal *************
	* Params required: org_id, id, user_id.	 
	* return status, message
	*************************************/
	private function joinMeal($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['org_id'] = @trim($data['org_id']);
		$requiredData['user_id'] = @trim($data['user_id']);
		
		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
		
		$where['admin_id'] = $requiredData['org_id'];
       	$arrMeal=$this->api_model->getByID($requiredData['id'], $where,'ci_plan_meal'); // collecting event detail
       	$arrUser=$this->api_model->getByID($requiredData['user_id'], $where,'ci_user'); // collecting user detail
		if(!empty($arrUser))        
		{
			if(!empty($arrMeal))
			{ 
				if($arrMeal['list_users'] != '')
				{

					$arrMealUsers = explode(",",$arrMeal['list_users']);
					if(in_array($requiredData['user_id'], $arrMealUsers))
					{
						$response[]	= array('status'=>'Error','message'=>'Sorry!! You have already joined this meal.','id'=>$requiredData['id'],'user_id'=>$requiredData['user_id']);
							return $response;
					}
					$updateData['list_users'] = $arrMeal['list_users'].', '.$requiredData['user_id'];
				}
				else
					$updateData['list_users'] = $requiredData['user_id'];
					
				$updateData['id'] = $requiredData['id'];

				$id_record=$this->api_model->saveRecords($updateData, 'ci_plan_meal');
				
				$response[]	= array('status'=>'Success','message'=>'Congrats!! You have been joined successfully.','id'=>$id_record);	
			}
			else
				$response[]	= array('status'=>'Error','message'=>'Records not found with this Meal Id.');			
		}
		else
		{
			$response[]	= array('status'=>'Error','message'=>'Records not found with this User Id.');	
		}
				
		return $response;
	}
	/*************************************************************************** 
	 * function is used to create Plan an Event 
	 * Params required: staff_id, org_id.	
	 * return status, message and event_id
	 ****************************************************************************/
	function addEvent($data){
		//die($data);
		$response	= array();
		$requiredData['admin_id'] = @trim($data['org_id']);
		$requiredData['staff_id'] = @trim($data['staff_id']);
		$requiredData['event_id'] = @trim($data['event_id']);
		$requiredData['location_id'] = @trim($data['location_id']);
		$requiredData['no_end_date'] = @trim($data['no_end_date']);
		$requiredData['meetup_date'] = @trim($data['meetup_date']);
		$requiredData['meetup_time'] = @trim($data['meetup_time']);
		$requiredData['end_time'] = @trim($data['end_time']);
		
		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}			
		}

		$requiredData['description'] = @trim($data['description']);
		$requiredData['recurring'] = @trim($data['recurring']);
		$requiredData['max_attendies'] = @trim($data['max_attendies']);
		$requiredData['end_date'] = @trim($data['end_date']);
		$requiredData['is_active'] = empty($data['is_active']) ? 1 : @trim($data['is_active']);		

		$where['admin_id'] = $requiredData['admin_id'];
		$where['id'] = $requiredData['staff_id'];
		$where['is_active'] = 1;		
        $arrEvent=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_staff'); // collecting category list
        if(!empty($arrEvent))
        {
			$requiredData['logtime'] = date('Y-m-d H:i:s');
			$id_record=$this->api_model->saveRecords($requiredData, 'ci_plan_event');
			
			$response[]	= array('status'=>'Success','message'=>'Congrats!! Event has been added successfully.','id'=>$id_record);	
			return $response;
		}else{	
			$response[]	= array('status'=>'Error','message'=>'Sorry!! No staff member found with this id.');	
			return $response;		
		}
	}
	/*************************************************************************** 
	 * function is used to create Plan an Meal 
	 * Params required: staff_id, org_id.	
	 * return status, message and event_id
	 ****************************************************************************/
	function addMeal($data){
		//die($data);
		$response	= array();
		$requiredData['admin_id'] = @trim($data['org_id']);
		$requiredData['staff_id'] = @trim($data['staff_id']);
		$requiredData['name'] = @trim($data['name']);
		$requiredData['location_id'] = @trim($data['location_id']);
		$requiredData['start_date'] = @trim($data['start_date']);
		$requiredData['start_time'] = @trim($data['start_time']);
		$requiredData['end_time'] = @trim($data['end_time']);
		
		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}			
		}

		$requiredData['description'] = @trim($data['description']);
		$requiredData['recurring'] = @trim($data['recurring']);
		$requiredData['end_date'] = @trim($data['end_date']);
		$requiredData['is_active'] = empty($data['is_active']) ? 1 : @trim($data['is_active']);		

		$where['admin_id'] = $requiredData['admin_id'];
		$where['id'] = $requiredData['staff_id'];
		$where['is_active'] = 1;		
        $arrMeal=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_staff'); // collecting category list
        if(!empty($arrMeal))
        {
			$requiredData['logtime'] = date('Y-m-d H:i:s');
			$id_record=$this->api_model->saveRecords($requiredData, 'ci_plan_meal');
			
			$response[]	= array('status'=>'Success','message'=>'Congrats!! Meal has been added successfully.','id'=>$id_record);	
			return $response;
		}else{	
			$response[]	= array('status'=>'Error','message'=>'Sorry!! No staff member found with this id.');	
			return $response;		
		}
	}


	function listUserdays($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['user_id'] = @trim($data['user_id']);
		if(trim($requiredData['user_id']) == '' || trim($requiredData['user_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify User Id');
			return $response;
		}	
		$response = array();
		$user_id = $requiredData['user_id'];

		$events = $this->api_model->get_my_events($user_id);
		$meals = $this->api_model->get_my_meals($user_id); 
   		
		$myday = array_merge($events,$meals);
	
		if(!empty($myday))
        {
        	if (!empty($events)) 
        	{
        		foreach ($myday as $value) 
	        	{
	        		$r_dates = $value['range_date'];
	        		$r_week_day = $value['week_day'];

				 	$today = date('Y-m-d');
				 	if ($r_dates >= $today ) 
					{        			
	        			$response1[$value['range_date']][] =$value; 
	        		}
	    		}
	    		if(!empty($response1)){
		    		$responseArrayObject = new ArrayObject($response1);
					$responseArrayObject->ksort();
					$response['events'] = array($responseArrayObject);
				}
				else{
        		$response['events'][] = array('status'=> 'Error', 'message'=> 'No Record Founds !!!');
        		}
		
        	}
        	else{
        		$response['events'][] = array('status'=> 'Error', 'message'=> 'No Record Founds !!!');
        	}
        	
        	$weeksdays = array();

			if (!empty($meals)) {
			
			foreach ($meals as $key1 => $meals1) {
				// print_r($meals1);
					$w_day = $meals1['week_day'].'-'.$meals1['name'];
					// $r_dates = $value['range_date'];
					// print_r($weeksdays);

				if (!in_array($w_day, $weeksdays)) {
					# code...
				
				$weeksdays[] = $meals1['week_day'].'-'.$meals1['name'];
        		$response2[$meals1['week_day']][] = $meals1;
        		} 
        		// print_r($response2);

    		}
	    		if(!empty($response2)){
						$mealObject = new ArrayObject($response2);
						$mealObject->ksort();
						$response['meals'] = array($mealObject);
				}
				else{
        		$response['meals'][] = array('status'=> 'Error', 'message'=> 'No Record Founds !!!');
        		}
			}else{
				$response['meals'][] = array('status'=> 'Error', 'message'=> 'No Record Found !!!'); 
			}
			
			$response[] = array('status' => 'success', "message" =>"Result Found !!");
        	return array($response);
        } else {
        	$response[]	= array('status'=>'Error','message'=>'No Record Found');			
			return $response;
        }

	}

	function listUserStatusdays($data)
	{			
		//$requiredData['id_user'] = @trim($data['id_user']);
		$requiredData['user_id'] = @trim($data['user_id']);
		if(trim($requiredData['user_id']) == '' || trim($requiredData['user_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify User Id');
			return $response;
		}	
		$response = array();
		$user_id = $requiredData['user_id'];
		
		$events = $this->api_model->get_my_events($user_id);
		$meals = $this->api_model->get_my_meals($user_id); 
   		
		$myday = array_merge($events,$meals);
	
		if(!empty($myday))
        {
        	if (!empty($events)) 
        	{
        		foreach ($myday as $value) 
	        	{
	        		$r_dates = $value['range_date'];
	        		$r_week_day = $value['week_day'];

				 	$today = date('Y-m-d');
				 	if ($r_dates >= $today ) 
					{        			
	        			$response1[] =$value; 
	        		}
	    		}
	    		if(!empty($response1)){
		    		$responseArrayObject = new ArrayObject($response1);
					$responseArrayObject->ksort();
					$response['events'] = array($responseArrayObject);
				}
				else{
        		$response['events'][] = array('status'=> 'Error', 'message'=> 'No Record Founds !!!');
        		}
		
        	}
        	else{
        		$response['events'][] = array('status'=> 'Error', 'message'=> 'No Record Founds !!!');
        	}
        	
        	$weeksdays = array();

			if (!empty($meals)) {
			
			foreach ($meals as $key1 => $meals1) {
				// print_r($meals1);
					$w_day = $meals1['week_day'].'-'.$meals1['name'];
					// $r_dates = $value['range_date'];
					// print_r($weeksdays);

				if (!in_array($w_day, $weeksdays)) {
					# code...
				
				$weeksdays[] = $meals1['week_day'].'-'.$meals1['name'];
        		$response2[$meals1['week_day']][] = $meals1;
        		} 
        		// print_r($response2);

    		}
	    		if(!empty($response2)){
						$mealObject = new ArrayObject($response2);
						$mealObject->ksort();
						$response['meals'] = array($mealObject);
				}
				else{
        		$response['meals'][] = array('status'=> 'Error', 'message'=> 'No Record Founds !!!');
        		}
			}else{
				$response['meals'][] = array('status'=> 'Error', 'message'=> 'No Record Found !!!'); 
			}
			
			$response[] = array('status' => 'success', "message" =>"Result Found !!");
        	return array($response);
        } else {
        	$response[]	= array('status'=>'Error','message'=>'No Record Found');			
			return $response;
        }

	}

	function singleMyDay($data){

       	$requiredData['eventdate'] = @trim($data['eventdate']);
       	$requiredData['user_id']   = @trim($data['user_id']);
       

		if(trim($requiredData['eventdate']) == '' || trim($requiredData['eventdate']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify date');
			return $response;
		}

		$response = array();
		$user_id = $requiredData['user_id'];
		$eventdate = $requiredData['eventdate'];

		$events = $this->api_model->get_my_events($user_id);
		$meals = $this->api_model->get_my_meals($user_id); 
   		// print_r($meals);
   		// die();
		$myday = array_merge($events,$meals);

		foreach ($events as $key => $value) {

			$r_dates = $value['range_date'];
			$r_week_day = $value['week_day'];

		 	$today = date('Y-m-d');

		 	if ($r_dates >= $today ) 
			{
				$response_e[$value['range_date']][] =$value; 
			}
			$wee_days = array();
			foreach ($meals as  $meals2) 
			{
				$we_day = $meals2['week_day'];

				if ($r_week_day == $meals2['week_day']) 
				{
					if(!in_array($we_day, $wee_days))
					{
						$wee_days[] =  $meals2['week_day'];
						$response_e[$value['range_date']][] =$meals2;
					} 
    			}
			}
			
		}
		$singleMyDay_response = $response_e[$eventdate];

		$timestamp = strtotime($eventdate);
        $r_day =  date("l", $timestamp);
    	$custom_days=array(
			'Sunday' => '1',
			'Monday' => '2',
			'Tuesday' => '3',
			'Wednesday' => '4',
			'Thursday' => '5',
			'Friday' => '6',
			'Saturday' => '7'
		);
	
    	if (!empty($singleMyDay_response)) {
		
			$response = array('datedetails' => $singleMyDay_response, 'eventdate'=>$eventdate, 'status' => 'success');
    		
    	}
    	else{
    		$weekdays = array();
        	foreach ($meals as $meals3) 
    		{
    			// $weekday = $meals3['week_day'];
    			if ($eventdate == $meals3['range_date']) 
				{
					// if(in_array($weekday, $weekdays)){

					// $weekdays[] = $meals3['week_day'];
    				$response_v[] = $meals3; 

    				// }
    			}
    		}
    	
    		if (!empty($response_v)) {
				$response = array('datedetails' => $response_v, 'eventdate'=>$eventdate, 'status' => 'success');
    			
    		}
    		else{
  				$response   = array('status'=>'Error','message'=>'Records not found.'); 
    		}
			
		}
		return array($response);
}
	
	/*************************************************************************** 
	 * function is used to get user detail
	 * Params required: id_user 
	 * return status, message and user_id
	 ****************************************************************************/	
	function getAppUser($data){	
		
			$requiredData['uuid'] = @trim($data['uuid']);
			$result = $this->api_model->get_allusers($requiredData);	
			
			$response = array();
			

			if(!empty($result))
			{
				$response['status'] = 'Success';
				$response['message'] = 'Congrats!! You app is successfully activated';
				$response['user_id'] = $result['id'];
				$response['org_id'] = $result['admin_id'];
				$response['username'] = $result['first_name'];
				return  array($response);
			}
			else
			{
				$response[]	= array('status'=>'Error','message'=>'User does not exist');	
				return $response;					
			}
			//mail($to,$subject,$response,$headers);
			

	}

	function active_user($data){

		$requiredData['user_phoneno'] =  @trim($data['user_phoneno']);
		foreach ($requiredData as $key => $val) {
			if (trim($val) == '') {
				$response[] = array('status' =>'Error','message'=>'Please Specify'. ucwords(str_replace("_", " ", $key)) );
				return $response;
			}
		}
			$where['mobile'] = "+".$requiredData['user_phoneno'];
		 	$arrUser = $this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='', 'ci_user' );
		 	print_r($arrUser);
		if (!empty($arrUser)) 
		{
			 $user_Phone_no = @trim($arrUser[0]['mobile']);
			if ($requiredData['user_phoneno'] == $user_Phone_no) 
			{
				$response[]	= array('user_id'=>$arrUser[0]['id'],'org_id'=>$arrUser[0]['admin_id'],'status'=>'Success','message'=>'User Login Successfully');

				// Update user app entry
				$update_data['user_id'] = $arrUser[0]['id'];
				$this->api_model->updateApp_status($update_data);
				
				return $response;
			}
			else
			{
				$response[]	= array('status'=>'Error','message'=>'Invalid mobile Number2. Please try again');	
				return $response;					
			}
		}else
		{
			$response[]	= array('status'=>'Error','message'=>'Invalid Mobile Number1. Please try again');	
			return $response;
		}
	}

	function activation_login($data)
    {
     
	$requiredData['activationcode'] =  @trim($data['activationcode']);

		foreach($requiredData AS $key=>$val){
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));	
				return $response;
			}			
		}

		$where['appcode'] = $requiredData['activationcode'];					

        $arrUser=$this->api_model->getAll($where_like='', $where, $limit='', $offset=0, $orderby='','ci_user'); // collecting Order list

		if(!empty($arrUser)){
			$appcode = $arrUser[0]['appcode'];
			
			if($requiredData['activationcode'] == $appcode)
			{
				$response[]	= array('user_id'=>$arrUser[0]['id'],'org_id'=>$arrUser[0]['admin_id'],'status'=>'Success','message'=>'User Login Successfully');

				// Update user app entry
				$update_data['user_id'] = $arrUser[0]['id'];
				$this->api_model->updateApp_status($update_data);
				
				return $response;
			}
			else
			{
				$response[]	= array('status'=>'Error','message'=>'Invalid code. Please try again');	
				return $response;					
			}
		}else{
			$response[]	= array('status'=>'Error','message'=>'Invalid code. Please try again');	
			return $response;
		}

	return $arrusersdata;
    }

    function getAddays($data){	
		
			$requiredData['user_id'] = @trim($data['user_id']);
		    $requiredData['org_id'] = @trim($data['org_id']);
			$result = $this->api_model->get_adusers($requiredData);
			// print_r($result);
			

			if(!empty($result))
			{
				$response['user_id'] = $result['id'];
				$response['org_id'] = $result['admin_id'];
				$response['ad_days'] = $result['ad_days'];
				$response['app_install'] = $result['app_install'];
				return  $response;
			}
			else
			{
				$response[]	= array('status'=>'Error','message'=>'Please try again');	
				return $response;					
			}
			//mail($to,$subject,$response,$headers);
			

	    }


    /****************** List Events dates *************
	* Params required: event_id.	 
	* return event dates between start date and end date
	*************************************/
	private function listEventdates($data)
	{			
		$requiredData['event_id'] = @trim($data['event_id']);
		$requiredData['event_date'] = @trim($data['singledate']);

		if(trim($requiredData['event_id']) == '' || trim($requiredData['event_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify Event Id');
			return $response;
		}	
		$event_id = $requiredData['event_id'];
		$sql="SELECT * from ci_plan_event WHERE id = $event_id and is_active = 1 group by meetup_date order by meetup_date ASC"; 	
		$arrEvents=$this->db->query($sql)->result_array();

			$rec_val = $arrEvents[0]['recurring'];
			$rec_explode = explode(',', $rec_val );
			$length = count($rec_explode);
			$recring = array();
			$db_days=array(
				'S' => 'Sunday',
				'M' => 'Monday',
				'T' => 'Tuesday',
				'W' => 'Wednesday',
				'Th' => 'Thursday',
				'F' => 'Friday',
				'St' => 'Saturday',
				);
			
	        foreach ($rec_explode  as $key2 => $day) {
	          
	                  $recring[$day] = $db_days[$day];
	            
	          }

	       	$meetup_date = $arrEvents[0]['meetup_date'];
			 $end_date = $arrEvents[0]['end_date'];
			
			$range_date = array();
		  	// $begin =  new DateTime( $meetup_date);
     //       	$stop_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
     //       	$end = new DateTime( $stop_date  );
     //        $daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);
			$current_event_date = $requiredData['event_date'];
    		$c_meetday = date("l", strtotime($current_event_date));

			if ($meetup_date == $end_date ) 
			{
	        	$str_date_day = date('l',strtotime($meetup_date));
	        	
	        	$range_date[] = array('day' => '8','meetup_day' => $str_date_day, 'meetup_dates' => $meetup_date);

	        }
	        else
	        {
	        	// if (date('Y-m-d') == $meetup_date) 
	        	// {
	        	// 	$rangedate[] = $range_date[] = array('day' => '0'); // Just today
	        	// }
	        	// else
	        	// {
	        		$rangedate[] = $range_date[] = array('day' => '8','meetup_day' => $c_meetday, 'meetup_dates' => $current_event_date);
	        	// }
	        	// $rangedate[] = $range_date[] = array('day' => 'Just End Day');
	        }

			$custom_days=array(
				'Sunday' => '1',
				'Monday' => '2',
				'Tuesday' => '3',
				'Wednesday' => '4',
				'Thursday' => '5',
				'Friday' => '6',
				'Saturday' => '7'
				);
	      	foreach ($recring as $key4 => $recring_val) 
	      	{
		      	$begin =  new DateTime( $meetup_date);
	           	$stop_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
	           	$end = new DateTime( $stop_date  );
	            $daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);

	        	foreach($daterange as $key => $date)
	        	{

	    		 	$r_dates = $date->format("Y-m-d");
	    		 	$timestamp = strtotime($r_dates);
	                $r_day =  date("l", $timestamp);
	        		
				 	if ( ($r_day == $recring_val) ) {
				 		if ($r_day == $c_meetday) {
				 		$range_date[] = array('day' => $custom_days[$r_day]);
				 			
				 		}

				 	}
	        	}
	        }
		return array_map("unserialize", array_unique(array_map("serialize", $range_date)));
	}

	/****************** Add Event Meta *************
	* Params required: org_id, event_id, user_id.	 
	* return status, message
	*************************************/
	private function addEventrange($data)
	{			
		/*print_r($data);
		exit;*/
		$requiredData['id'] = @trim($data['id']);
		$requiredData['user_id'] = @trim($data['user_id']);
		$requiredData['org_id'] = @trim($data['org_id']);
		$requiredData['user_option'] = @trim($data['user_option']);
		$requiredData['singledate'] = @trim($data['singledate']);
		
		foreach($requiredData AS $key=>$val) {		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	

	    $user_option = $requiredData['user_option'];
        
        // $option_length = count( $user_option );
        // $option_A = explode(',', $user_option);

		$event_plan_id = @trim($data['id']);
    	
        $sql="SELECT * from ci_plan_event WHERE id = $event_plan_id and is_active = 1 group by meetup_date order by meetup_date ASC"; 	
		
		$arrEvents=$this->db->query($sql)->result_array();
		$meetup_date = $arrEvents[0]['meetup_date'];
		$end_date = $arrEvents[0]['end_date'];

		$range_date = array();
	  	$begin =  new DateTime($meetup_date);
       	$stop_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
       	$end = new DateTime($stop_date);
        $daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);
		$usereventdate = array();

	
        foreach($daterange as $key => $date)
        {

		 	$r_dates = $date->format("Y-m-d");
		 	$timestamp = strtotime($r_dates);
          	$r_day =  date("l", $timestamp);

    		if ($r_day == 'Sunday') {
    			$sunday[] = $r_dates ;
    		}
    		if ($r_day == 'Monday') {
    			$monday[] = $r_dates ;
    		}
    		if ($r_day == 'Tuesday') {
    			$tuesday[] = $r_dates ;
    		}
    		if ($r_day == 'Wednesday') {
    			$wednesday[] = $r_dates ;
    		}
    		if ($r_day == 'Thursday') {
    			$thursday[] = $r_dates ;
    		}
    		if ($r_day == 'Friday') {
    			$friday[] = $r_dates ;
    		}
    		if ($r_day == 'Saturday') {
    			$saturday[] = $r_dates ;
    		}

			$range_date[] = array('dates' =>$r_dates,'day' => $r_day);		
        }

      
     	$default_data['range_date'] = "";

		// if (in_array("0", $option_A)) {
  //   		$default_data['range_date'] .= ",".$meetup_date;
  //   	}
		if ($user_option == "1") {
    		
    		$default_data['range_date'] .= ",".implode(',',$sunday);
    		$day_option = 1;
    		$status = 1;
    	}
    	if ($user_option == "2") {

    		$default_data['range_date'] .= ",".implode(',',$monday);
    		$day_option = 1;
    		$status = 1;
    	}
    	if ($user_option == "3") {
    		$default_data['range_date'] .= ",".implode(',',$tuesday);
    		$day_option = 1;
    		$status = 1;
    	}
    	if ($user_option == "4") {
    		$default_data['range_date'] .= ",".implode(',',$wednesday);
    		$day_option = 1;
    		$status = 1;
    	}
    	if ($user_option == "5") {
    		$default_data['range_date'] .= ",".implode(',',$thursday);
    		$day_option = 1;
    		$status = 1;
    	}
    	if ($user_option == "6") {
    		$default_data['range_date'] .= ",".implode(',',$friday);
    		$day_option = 1;
    		$status = 1;
    	}
    	if ($user_option == "7") {
    		$default_data['range_date'] .= ",".implode(',',$saturday);
    		$day_option = 1;
    		$status = 1;
    	}
		
		$custom_days=array(
			'Sunday' => '1',
			'Monday' => '2',
			'Tuesday' => '3',
			'Wednesday' => '4',
			'Thursday' => '5',
			'Friday' => '6',
			'Saturday' => '7'
		);
    	
    	$singledates = $requiredData['singledate'];
    	$r_day =  date("l", strtotime($singledates));

    	if ($user_option == "8") {
    		$default_data['range_date'] .= ",".$singledates;
    		$day_option = 0;
    		$status = 1;
    		$user_option = $custom_days[$r_day];
    	}

		$where = array(
			'plan_event_id' => $requiredData['id'],
			'user_id' => $requiredData['user_id'],
			'week_day'=>$user_option,
			'range_date' => $requiredData['singledate']
		);
        $event_name=$this->api_model->getrangeByID($where,'ci_plan_eventmeta');
   		// print_r($event_name);
   		// die();
	    	if (!empty($event_name)) 
	    	{
	    		$this->api_model->rangedelete($where,'ci_plan_eventmeta');
	    	}
    		
    		$data_arr = explode(",", $default_data['range_date']);
    		
    		$default_data['plan_event_id'] = $requiredData['id'];
			$default_data['user_id'] = $requiredData['user_id'];
			$default_data['org_id'] = $requiredData['org_id'];
			$default_data['week_day'] = $user_option;
			$default_data['day_option'] = $day_option;
			$default_data['status'] = $status;
			 

    		foreach ($data_arr as $keya => $date_value) {
    			 $default_data['range_date'] = $date_value;

	    		if (!empty($date_value)) {
	    			$id_record=$this->api_model->insert_range_data($default_data, 'ci_plan_eventmeta');
	    		}
    		}
			$response[]	= array('status'=>'Success','message'=>'Records Inserted','user_options'=>$requiredData['user_option'],'status'=>$status);

    		     		
		return $response;
	}

	/****************** Check Event Meta *************
	* Params required: plan_event_id	 
	* return status, message , counts number of rows
	*************************************/
	private function checkEventrange($data)
	{
		$requiredData['plan_event_id'] = @trim($data['plan_event_id']);
		
		$query="SELECT e.`max_attendies` , pe.`event_id`, pem.`plan_event_id` FROM `ci_plan_eventmeta` AS pem LEFT JOIN `ci_plan_event` AS pe ON pe.`id` = pem.`plan_event_id` LEFT JOIN `ci_event` AS e ON e.`id` = pe.`event_id` WHERE pem.`plan_event_id` = ".$requiredData['plan_event_id']; 	
		
		$count_rows=$this->db->query($query)->num_rows();	

		$rows=$this->db->query($query)->result_array();
		
		$max_attendies = $rows[0]['max_attendies'];

		if( $max_attendies > $count_rows)
		{
			$status = 'true';
		}
		else
		{
			$status = 'false';	
		}

		$response[]	= array('status'=>'Success','message'=>'You are allowed to join .' , 'count_rows' => $count_rows , 'max_attendies' => $max_attendies , 'attendie_status' => $status);
		return $response;
	}

	/****************** Delete Event Meta *************	 
	* return status, message
	*************************************/
	private function deleteEventrange($data)
	{			
		/*print_r($data);
		exit();*/
		$plan_event_id = @trim($data['id']);
		$user_id = @trim($data['user_id']);
		//$requiredData['singledate'] = @trim($data['singledate']);
		$singledates = @trim($data['singledate']);
    	$r_day =  date("l", strtotime($singledates));
    	
    	$custom_days=array(
			'Sunday' => '1',
			'Monday' => '2',
			'Tuesday' => '3',
			'Wednesday' => '4',
			'Thursday' => '5',
			'Friday' => '6',
			'Saturday' => '7'
		);

    	$numeric_day = $custom_days[$r_day];
    	
    	$sql="SELECT * FROM `ci_plan_eventmeta` WHERE plan_event_id = $plan_event_id AND user_id = $user_id AND week_day = $numeric_day"; 
		$arrEvents=$this->db->query($sql)->result_array();
		
		$find_status = $arrEvents[0]['status'];
		if($find_status == 1)
		{
			$where = array(
				'plan_event_id' => $plan_event_id ,
				'user_id' => $user_id ,
				'week_day' => $numeric_day
			);
			
			$update_data['status'] = 0;
			// $update_query = $this->api_model->rangedelete($where,'ci_plan_eventmeta');
			$update_query = $this->api_model->update_range_data($where,$update_data,'ci_plan_eventmeta');
		}
		$response[]	= array('status'=>'Success','message'=>'Records Deleted' , 'event_status' => $find_status);
		return $response;
	}

	/****************** Selected Event RangeDate *************
	* Params required: plan_meal_id,user_id.	 
	* return rangedate, message
	*************************************/
	private function selectedEventRange($data)
	{			
		//print_r($data);
		$requiredData['id'] = @trim($data['id']);
		$requiredData['user_id'] = @trim($data['user_id']);
		$requiredData['eventdate'] = @trim($data['eventdate']);

		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}
		$custom_days=array(
           		'today' => '0',
				'Sunday' => '1',
				'Monday' => '2',
				'Tuesday' => '3',
				'Wednesday' => '4',
				'Thursday' => '5',
				'Friday' => '6',
				'Saturday' => '7',
				'start_day' => '8'
			);	
		$eventdate = $requiredData['eventdate'];
    	$r_day =  date("l", strtotime($eventdate));
		$week_day = $custom_days[$r_day];
        $status = 1;
        $where = array(
				'plan_event_id' => $requiredData['id'],
				'user_id' => $requiredData['user_id'],
				'week_day ' => $week_day ,
				'status' => $status
			);
		
		
		$event_plan_id = $requiredData['id'];
        $selected_data = $this->api_model->getrangeByID($where,'ci_plan_eventmeta');

        //print_r($selected_data);
        
        $arr_days = array();
        foreach ($selected_data as $key => $value) {
        	if ($value['day_option'] == 1) {
        		array_push($arr_days, $value['week_day']);
        	}
        	else{
        		array_push($arr_days, '8');
        	}
        }

        
        $response[] = array('status'=>'Success','message'=>'Result Found..! ' , 'user_option' => array_unique($arr_days) , 'event_status' => $selected_data[0]['status']  );  
    	
		// $response[]	= array('status'=>'Success','message'=>'Result Found..! ');

	
	return $response;
	}

    /****************** List Meal dates *************
	* Params required: plan_meal_id.	 
	* return event dates between start date and end date
	*************************************/
	private function listMealdates($data)
	{			
		$requiredData['plan_meal_id'] = @trim($data['plan_meal_id']);
		

		if(trim($requiredData['plan_meal_id']) == '' || trim($requiredData['plan_meal_id']) == 0){
			$response[] = array('status'=>'Error','message'=>'Please Specify Plan Meal Id');
			return $response;
		}	
		
			$meal_id = $requiredData['plan_meal_id'];
			$sql="SELECT * from ci_plan_meal WHERE id = $meal_id and is_active = 1 group by start_date order by start_date ASC"; 	
			$arrMeal=$this->db->query($sql)->result_array();
		
			if (empty($arrMeal)) {
				$response[]	= array('status'=>'Error','message'=>'No data found');		
				return $response; 
			}
			 $s=$arrMeal[0]['start_time'];
			 $e = $arrMeal[0]['end_time'];
			 $startTime = new DateTime($s);
			 $end_time = new DateTime($e);

         	while ($startTime <= $end_time) {

          	    $time_interval = $startTime->format('H:i:s');
		     	// $time_interval = date("g:i A", strtotime($time_interval));
			    $response[] = array('time'=>$time_interval);
		     	$time_interval= $startTime->modify('+30 minutes')->format('H:i:s');

			}
		
		return $response;
	}

	/****************** Add Meal Meta *************
	* Params required: id, user_option, user_id,eventtime.	 
	* return status, message
	*************************************/
	private function addMealrange($data)
	{			
		$requiredData['id']          = @trim($data['id']);
		$requiredData['user_id']     = @trim($data['user_id']);
		$requiredData['org_id']      = @trim($data['org_id']);
		$requiredData['user_option'] = @trim($data['user_option']);
		$requiredData['meal_time']   = @trim($data['meal_time']);
		$requiredData['meal_date']   = @trim($data['meal_date']);

		echo "Test";
		exit();

		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
	
        $rangeData = $requiredData['user_option'];
      
		$meal_plan_id = @trim($data['id']);

       	$where = array(
			'plan_meal_id' => $meal_plan_id,
			'user_id' => $requiredData['user_id'],
			'week_day' =>  $rangeData
		);

        $meal_name = $this->api_model->getrangeByID($where,'ci_plan_mealmeta');
  
    	if (!empty($meal_name)) 
    	{
	    	$this->api_model->rangedelete($where,'ci_plan_mealmeta');
    	}
        
        $mealdats = $requiredData['meal_date'];
        $mealdats_a = explode(',', $mealdats);

        foreach ($mealdats_a as $key2 => $mealdats_ar) {
        	# code...
        	//print_r($mealdats_a);

	        $default_data['plan_meal_id'] = $requiredData['id'];
	        $default_data['user_id'] = $requiredData['user_id'];
	        $default_data['org_id'] = $requiredData['org_id'];
	        $default_data['week_day'] = $requiredData['user_option'];
	        $default_data['range_date'] = $mealdats_a[$key2];
	        $default_data['meal_time'] = $requiredData['meal_time'];
	        $default_data['status'] = 1;

	     	$id_record=$this->api_model->insert_range_data($default_data, 'ci_plan_mealmeta');
     	}
     	$response[]	= array('status'=>'Success','message'=>'Record has been added successfully');
		
		
		return $response;
	}


	/****************** Delete Meal Meta *************	 
	* return status, message
	*************************************/
	private function deleteMealrange($data)
	{			
		$requiredData['id']          = @trim($data['id']);
		$requiredData['user_id']     = @trim($data['user_id']);
		$requiredData['org_id']      = @trim($data['org_id']);
		$requiredData['user_option'] = @trim($data['user_option']);
		$requiredData['meal_time']   = @trim($data['meal_time']);


		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	
	
        $rangeData = $requiredData['user_option'];
      
		$meal_plan_id = @trim($data['id']);

       	$where = array(
			'plan_meal_id' => $meal_plan_id,
			'user_id' => $requiredData['user_id'],
			'week_day' =>  $rangeData
		);

        $meal_name = $this->api_model->getrangeByID($where,'ci_plan_mealmeta');
  
    	if (!empty($meal_name)) 
    	{
	    	$this->api_model->rangedelete($where,'ci_plan_mealmeta');
    	}
        
     	$response[]	= array('status'=>'Success','message'=>'Record has been deleted');
		
		
		return $response;
	}

	/****************** Selected Meal RangeDate *************
	* Params required: plan_meal_id,user_id.	 
	* return rangedate, message
	*************************************/

	private function selectedMealRange($data)
	{			
		$requiredData['id'] = @trim($data['id']);
		$requiredData['user_id'] = @trim($data['user_id']);

		foreach($requiredData AS $key=>$val){		
			if(trim($val) == ''){
				$response[]	= array('status'=>'Error','message'=>'Please Specify '.ucwords(str_replace("_"," ",$key)));		
				return $response;
			}
		}	

        $where = array(
			'plan_meal_id' => $requiredData['id'],
			'user_id' => $requiredData['user_id']	
		);
        $meal_name=$this->api_model->getrangeByID($where,'ci_plan_mealmeta');

     	if (empty($meal_name)) {
            $response[] = array('status'=>'Error','message'=>'No Records Found..');
            return $response;
        }
       // $mealtime=date("g:i A", strtotime($meal_name[0]['meal_time']));
        $mealtime = $meal_name[0]['meal_time'];
        $status   = $meal_name[0]['status'];
        $range_date = $meal_name[0]['range_date'];
	    $response[]	= array('time' => $mealtime,'range_date'=> $range_date,'meal_status'=> $status,'status'=>'Success','message'=>'Result Found..! ');

		return $response;
	}


	
	//Encode array into JSON
	private function json($data)
	{
		if(is_array($data)){
			return $data;
		}
		
	}
}

?>