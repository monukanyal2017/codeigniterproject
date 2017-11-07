<?php 
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payments_pro extends CI_Controller 
{
	function __construct()
	{
		parent::__construct();

		// Load helpers
		$this->load->helper('url');
		$this->load->library('session');
	    $this->load->model('auth_model');               
	    $this->load->model('user_model');
	    $this->load->helper('security'); 
		// Load PayPal library
		$this->config->load('paypal');

		$config = array(
			'Sandbox' => $this->config->item('Sandbox'), 			// Sandbox / testing mode option.
			'APIUsername' => $this->config->item('APIUsername'), 	// PayPal API username of the API caller
			'APIPassword' => $this->config->item('APIPassword'), 	// PayPal API password of the API caller
			'APISignature' => $this->config->item('APISignature'), 	// PayPal API signature of the API caller
			'APISubject' => '', 									// PayPal API subject (email address of 3rd party user that has granted API permission for your app)
			'APIVersion' => $this->config->item('APIVersion')		// API version you'd like to use for your call.  You can set a default version in the class and leave this blank if you want.
		);

		// Show Errors
		if($config['Sandbox'])
		{
			error_reporting(E_ALL);
			ini_set('display_errors', '1');
		}
		
		$this->load->library('paypal/Paypal_pro', $config);	
	}
	


	
	function index()
	{
		// echo site_url();
		$data["title"] = "Home";
		$data["assets_path"] = base_url().$this->config->item('assets_path');
		$this->load->view('home/payments_pro',$data);
	}

	function Set_express_checkout()
	{
		error_reporting(E_ERROR | E_PARSE);
		$first_name = trim($this->input->post('first_name'));
		$email = trim($this->input->post('email'));
		$address = trim($this->input->post('address'));
		$password = trim($this->input->post('password'));
		$default_data['password_confirmation'] = trim($this->input->post('password_confirmation'));
		$sitecode = substr($address, 0, 2).mt_rand(1000,9999);

			//echo "hele";

			if($email !='' && $this->auth_model->validate_email($email) == false){
				$ReturnData['error']['email']="Please enter valid Email-Id";
			}

			if(($email !=''  && $this->auth_model->validate_email($email) == true) || $address !='')
			{
				//echo "here";
				$data_feed = array(
					'admin_email' => $email
				);
				//print_r($data_feed) ;

				$result = $this->user_model->get_user_byemail($data_feed);
			
				//$result1 = $this->user_model->get_user_byaddress(array('address' => $address));
				 $date = date('Y-m-d H:i:s');
				if (empty($result)) {
					$default_data = array("first_name" => $first_name,"address" => $address,"email" => $email,"password" => md5($password),"sitecode"=>$sitecode, "logtime"=>$date);
					$id_record=$this->user_model->insert_user($default_data, 'ci_admin');
				    if($id_record)
				    {
				    	$session_data = array(
							'admin_id' => $id_record,
							'admin_email' => $email,
							'user_type' => 'simpleuser',
						);
						$this->session->set_userdata('logged_in', $session_data);
						$ReturnData['success']="Valid User";
							redirect(site_url('dashboard'));

				    } 
				}
				else{
					if($result)
					{
						$ReturnData['user_exist']="User with this Email-Id already exists";
						// $default_data = array("first_name" => $first_name,"address" => $address,"email" => $email,"password" => md5($password), "logtime"=>date('Y-m-d H:i:s'));
						// 	$id_record=$this->user_model->insert_user($default_data, 'ci_admin');
						   	$session_data = array(
							'admin_id' => $result['id'],
							'admin_email' => $result['email'],
							'user_type' => 'simpleuser',
						);


						 $config = array();  
							$config['protocol'] = 'smtp';  
							$config['smtp_host'] = 'ssl://smtp.gmail.com'; 
							$config['smtp_user'] = 'mailadmin@yourday.io';  
							$config['smtp_pass'] = 'mailadmin@2704';  
							$config['smtp_port'] = 465;
							$config['mailtype'] = 'html';
							$config['smtp_crypto'] = 'ssl';  	

						$this->load->library('email', $config);	
				      $message = 'http://stage.yourday.io/index.php/Password_rest/';
				      //$this->load->library('email', $config);
				      $this->email->set_newline("\r\n");
				      $this->email->from('mailadmin@yourday.io'); // change it to yours
				      $this->email->to($email);// change it to yours
				      $this->email->subject('Please update your password click this link!');
				      $this->email->message($message);
				     

				      if($this->email->send())
					     {
					      echo 'Email sent.';
					     }
				     else
					    {
					     show_error($this->email->print_debugger());
					    }


						$this->session->set_userdata('logged_in', $session_data);
						$ReturnData['success']="Valid User";
						redirect(site_url('dashboard'));

						

					}
										// if($result1) 
					// {
						
					// 	//$ReturnData['error']['address'] = "This Property Name is already exist";	
					// $date = date('Y-m-d H:i:s');
					// $address = $address."_".rand(10, 99);			
					// $default_data = array("first_name" => $first_name,"address" => $address,"email" => $email,"password" => md5($password), "sitecode"=>$sitecode, "logtime"=>$date);
					// $id_record=$this->user_model->insert_user($default_data, 'ci_admin');
				 //   }
				 //    if($id_record)
				 //    {
				 //    	$session_data = array(
					// 		'admin_id' => $id_record,
					// 		'admin_email' => $email,
					// 		'user_type' => 'simpleuser',
					// 	);
					// 	$this->session->set_userdata('logged_in', $session_data);
					// 	$ReturnData['success']="Valid User";
					// }

					//    	$session_data = array(
					// 		'admin_id' => $id_record,
					// 		'admin_email' => $email,
					// 		'user_type' => 'simpleuser',
					// 	);
					// 	$this->session->set_userdata('logged_in', $session_data);
					// 	$ReturnData['success']="Valid User";
					
			  //  	}
				}
			}
	}

	//29 march function name was:Set_express_checkout changed to xyz
	function xyz()
	{

		$default_data['firstname'] = trim($this->input->post('first_name'));
		$default_data['Email'] = trim($this->input->post('email'));
		$default_data['address'] = trim($this->input->post('address'));
		$default_data['password'] = trim($this->input->post('password'));
		$default_data['password_confirmation'] = trim($this->input->post('password_confirmation'));
		$default_data['sitecode'] = substr($default_data['address'], 0, 2).mt_rand(100,999);
		//$sitecode = substr($default_data['address'], 0, 2).mt_rand(100,999);
		// print_r($_POST);
		// echo "<br>sitecode: ".$sitecode;
		// die;
		//if (isset($_POST['add_submit']) && $_POST['add_submit'] == 'Subscriber') {
		
			# code...
		/*$default_data['firstname'] = trim($this->input->post('firstname'));
		$default_data['middlename'] = trim($this->input->post('middlename'));
		$default_data['lastname'] = trim($this->input->post('lastname'));
		$default_data['Email'] = trim($this->input->post('Email'));
		$default_data['description'] = trim($this->input->post('description'));*/
		//http://www.yourday.io/index.php/payments_pro/Set_express_checkout
		$SECFields = array(
							'token' => '', 							   // A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
							'maxamt' => '500.00', 					// The expected maximum total amount the order will be, including S&H and sales tax.
							'returnurl' => 'http://www.yourday.io/index.php/Payments_pro/Do_express_checkout_payment', 							// Required.  URL to which the customer will be returned after returning from PayPal.  2048 char max.
							'cancelurl' => 'http://www.yourday.io/index.php/Payments_pro/', 							// Required.  URL to which the customer will be returned if they cancel payment on PayPal's site.
							'callback' => '', 							// URL to which the callback request from PayPal is sent.  Must start with https:// for production.
							'callbacktimeout' => '', 					// An override for you to request more or less time to be able to process the callback request and response.  Acceptable range for override is 1-6 seconds.  If you specify greater than 6 PayPal will use default value of 3 seconds.
							'callbackversion' => '56.0', 				// The version of the Instant Update API you're using.  The default is the current version.							
							'reqconfirmshipping' => '0', 				// The value 1 indicates that you require that the customer's shipping address is Confirmed with PayPal.  This overrides anything in the account profile.  Possible values are 1 or 0.
							'noshipping' => '1', 						// The value 1 indiciates that on the PayPal pages, no shipping address fields should be displayed.  Maybe 1 or 0.
							'allownote' => '0', 							// The value 1 indiciates that the customer may enter a note to the merchant on the PayPal page during checkout.  The note is returned in the GetExpresscheckoutDetails response and the DoExpressCheckoutPayment response.  Must be 1 or 0.
							'addroverride' => '', 						// The value 1 indiciates that the PayPal pages should display the shipping address set by you in the SetExpressCheckout request, not the shipping address on file with PayPal.  This does not allow the customer to edit the address here.  Must be 1 or 0.
							'localecode' => 'us', 						// Locale of pages displayed by PayPal during checkout.  Should be a 2 character country code.  You can retrive the country code by passing the country name into the class' GetCountryCode() function.
							'pagestyle' => '', 							// Sets the Custom Payment Page Style for payment pages associated with this button/link.  
							'hdrimg' => '', 							// URL for the image displayed as the header during checkout.  Max size of 750x90.  Should be stored on an https:// server or you'll get a warning message in the browser.
							'hdrbordercolor' => '', 					// Sets the border color around the header of the payment page.  The border is a 2-pixel permiter around the header space.  Default is black.  
							'hdrbackcolor' => '', 						// Sets the background color for the header of the payment page.  Default is white.  
							'payflowcolor' => '', 						// Sets the background color for the payment page.  Default is white.
							'skipdetails' => '', 						// This is a custom field not included in the PayPal documentation.  It's used to specify whether you want to skip the GetExpressCheckoutDetails part of checkout or not.  See PayPal docs for more info.
							'email' => $default_data['Email'], 								// Email address of the buyer as entered during checkout.  PayPal uses this value to pre-fill the PayPal sign-in page.  127 char max.
							'solutiontype' => 'Sole', 					// Type of checkout flow.  Must be Sole (express checkout for auctions) or Mark (normal express checkout)
							'landingpage' => 'Billing', 				// Type of PayPal page to display.  Can be Billing or Login.  If billing it shows a full credit card form.  If Login it just shows the login screen.
							'channeltype' => '', 						// Type of channel.  Must be Merchant (non-auction seller) or eBayItem (eBay auction)
							'giropaysuccessurl' => '', 					// The URL on the merchant site to redirect to after a successful giropay payment.  Only use this field if you are using giropay or bank transfer payment methods in Germany.
							'giropaycancelurl' => '', 					// The URL on the merchant site to redirect to after a canceled giropay payment.  Only use this field if you are using giropay or bank transfer methods in Germany.
							'banktxnpendingurl' => '',  				// The URL on the merchant site to transfer to after a bank transfter payment.  Use this field only if you are using giropay or bank transfer methods in Germany.
							'brandname' => 'Your Days', 							// A label that overrides the business name in the PayPal account on the PayPal hosted checkout pages.  127 char max.
							'customerservicenumber' => '', 				// Merchant Customer Service number displayed on the PayPal Review page. 16 char max.
							'giftmessageenable' => '', 					// Enable gift message widget on the PayPal Review page. Allowable values are 0 and 1
							'giftreceiptenable' => '', 					// Enable gift receipt widget on the PayPal Review page. Allowable values are 0 and 1
							'giftwrapenable' => '', 					// Enable gift wrap widget on the PayPal Review page.  Allowable values are 0 and 1.
							'giftwrapname' => '', 						// Label for the gift wrap option such as "Box with ribbon".  25 char max.
							'giftwrapamount' => '', 					// Amount charged for gift-wrap service.
							'buyeremailoptionenable' => '', 			// Enable buyer email opt-in on the PayPal Review page. Allowable values are 0 and 1
							'surveyquestion' => '', 					// Text for the survey question on the PayPal Review page. If the survey question is present, at least 2 survey answer options need to be present.  50 char max.
							'surveyenable' => '', 						// Enable survey functionality. Allowable values are 0 and 1
							'totaltype' => '', 							// Enables display of "estimated total" instead of "total" in the cart review area.  Values are:  Total, EstimatedTotal
							'notetobuyer' => '', 						// Displays a note to buyers in the cart review area below the total amount.  Use the note to tell buyers about items in the cart, such as your return policy or that the total excludes shipping and handling.  127 char max.							
							'buyerid' => '', 							// The unique identifier provided by eBay for this buyer. The value may or may not be the same as the username. In the case of eBay, it is different. 255 char max.
							'buyerusername' => '', 						// The user name of the user at the marketplaces site.
							'buyerregistrationdate' => '',  			// Date when the user registered with the marketplace.
							'allowpushfunding' => ''					// Whether the merchant can accept push funding.  0 = Merchant can accept push funding : 1 = Merchant cannot accept push funding.			
						);
		
		// Basic array of survey choices.  Nothing but the values should go in here.  
		$SurveyChoices = array('Choice 1', 'Choice2', 'Choice3', 'etc');
		
		// You can now utlize parallel payments (split payments) within Express Checkout.
		// Here we'll gather all the payment data for each payment included in this checkout 
		// and pass them into a $Payments array.  
		
		// Keep in mind that each payment will ahve its own set of OrderItems
		// so don't get confused along the way.
		$Payments = array();
		$Payment = array(
				'amt' => '1.0', 							// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
				'currencycode' => 'USD', 					// A three-character currency code.  Default is USD.
				'itemamt' => '1.00', 						// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.  
				'shippingamt' => '', 					// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
				'shipdiscamt' => '', 				// Shipping discount for this order, specified as a negative number.
				'insuranceoptionoffered' => '', 		// If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
				'handlingamt' => '', 					// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
				'taxamt' => '0.00', 						// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order. 
				'desc' => 'Reference site about Lorem Ipsum, giving information on its origins, as well as a random Lipsum generator.', // Description of items on the order.  127 char max.
				'custom' => '', 						// Free-form field for your own use.  256 char max.
				'invnum' => '', 						// Your own invoice or tracking number.  127 char max.
				'notifyurl' => '', 						// URL for receiving Instant Payment Notifications
				'shiptoname' => '', 					// Required if shipping is included.  Person's name associated with this address.  32 char max.
				'shiptostreet' => '', 					// Required if shipping is included.  First street address.  100 char max.
				'shiptostreet2' => '', 					// Second street address.  100 char max.
				'shiptocity' => '', 					// Required if shipping is included.  Name of city.  40 char max.
				'shiptostate' => '', 					// Required if shipping is included.  Name of state or province.  40 char max.
				'shiptozip' => '', 						// Required if shipping is included.  Postal code of shipping address.  20 char max.
				'shiptocountrycode' => '', 					// Required if shipping is included.  Country code of shipping address.  2 char max.
				'shiptophonenum' => '',  				// Phone number for shipping address.  20 char max.
				'notetext' => '', 						// Note to the merchant.  255 char max.  
				'allowedpaymentmethod' => '', 			// The payment method type.  Specify the value InstantPaymentOnly.
				'allowpushfunding' => '', 				// Whether the merchant can accept push funding:  0 - Merchant can accept push funding.  1 - Merchant cannot accept push funding.  This will override the setting in the merchant's PayPal account.
				'paymentaction' => '', 					// How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order. 
				'paymentrequestid' => '',  				// A unique identifier of the specific payment request, which is required for parallel payments. 
				'sellerid' => '', 						// The unique non-changing identifier for the seller at the marketplace site.  This ID is not displayed.
				'sellerusername' => '', 				// The current name of the seller or business at the marketplace site.  This name may be shown to the buyer.
				'sellerpaypalaccountid' => ''			// A unique identifier for the merchant.  For parallel payments, this field is required and must contain the Payer ID or the email address of the merchant.
				);
		
		// For order items you populate a nested array with multiple $Item arrays.  
		// Normally you'll be looping through cart items to populate the $Item array
		// Then push it into the $OrderItems array at the end of each loop for an entire 
		// collection of all items in $OrderItems.
				
		$PaymentOrderItems = array();
		$Item = array(
					'name' => 'Subscription plan one', 								// Item name. 127 char max.
					'desc' => 'Reference site about Lorem Ipsum, giving information on its origins, as well as a random Lipsum generator.',// Item description. 127 char max.
					'amt' => '1.00', 								// Cost of item.
					'number' => '1', 							// Item number.  127 char max.
					'qty' => '1', 								// Item qty on order.  Any positive integer.
					'taxamt' => '0.00', 						// Item sales tax
					'itemurl' => '', 							// URL for the item.
					'itemweightvalue' => '', 					// The weight value of the item.
					'itemweightunit' => '', 					// The weight unit of the item.
					'itemheightvalue' => '', 					// The height value of the item.
					'itemheightunit' => '', 					// The height unit of the item.
					'itemwidthvalue' => '', 					// The width value of the item.
					'itemwidthunit' => '', 						// The width unit of the item.
					'itemlengthvalue' => '', 					// The length value of the item.
					'itemlengthunit' => '',  					// The length unit of the item.
					'itemurl' => '', 							// URL for the item.
					'itemcategory' => '', 						// Must be one of the following values:  Digital, Physical
					'ebayitemnumber' => '', 					// Auction item number.  
					'ebayitemauctiontxnid' => '', 				// Auction transaction ID number.  
					'ebayitemorderid' => '',  					// Auction order ID number.
					'ebayitemcartid' => ''						// The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
					);
		array_push($PaymentOrderItems, $Item);
		
		// Now we've got our OrderItems for this individual payment, 
		// so we'll load them into the $Payment array
		$Payment['order_items'] = $PaymentOrderItems;
		
		// Now we add the current $Payment array into the $Payments array collection
		array_push($Payments, $Payment);
		
		$BuyerDetails = array(
								'buyerid' => $default_data['Email'], // The unique identifier provided by eBay for this buyer.  The value may or may not be the same as the username.  In the case of eBay, it is different.  Char max 255.
								//'buyerusername' => $default_data['firstname']."_".$default_data['lastname'], 			// The username of the marketplace site.
								'buyerusername' => $default_data['firstname'], 			// The username of the marketplace site.
								'buyerregistrationdate' => ''	// The registration of the buyer with the marketplace.
								);
								
		// For shipping options we create an array of all shipping choices similar to how order items works.
		$ShippingOptions = array();
		$Option = array(
						'l_shippingoptionisdefault' => '', 				// Shipping option.  Required if specifying the Callback URL.  true or false.  Must be only 1 default!
						'l_shippingoptionname' => '', 					// Shipping option name.  Required if specifying the Callback URL.  50 character max.
						'l_shippingoptionlabel' => '',  // Shipping option label.  Required if specifying the Callback URL.  50 character max.
						'l_shippingoptionamount' => '' 					// Shipping option amount.  Required if specifying the Callback URL.  
						);
		array_push($ShippingOptions, $Option);
			
		// For billing agreements we create an array similar to working with 
		// payments, order items, and shipping options.	
		$BillingAgreements = array();
		$Item = array(
					  'l_billingtype' => 'RecurringPayments', 							// Required.  Type of billing agreement.  For recurring payments it must be RecurringPayments.  You can specify up to ten billing agreements.  For reference transactions, this field must be either:  MerchantInitiatedBilling, or MerchantInitiatedBillingSingleSource
					  'l_billingagreementdescription' => 'Reference site about Lorem Ipsum, giving information on its origins, as well as a random Lipsum generator.', 			// Required for recurring payments.  Description of goods or services associated with the billing agreement.  
					  'l_paymenttype' => 'Any', 							// Specifies the type of PayPal payment you require for the billing agreement.  Any or IntantOnly
					  'l_billingagreementcustom' => ''					// Custom annotation field for your own use.  256 char max.
					  );
		array_push($BillingAgreements, $Item);
		
		$PayPalRequestData = array(
						'SECFields' => $SECFields, 
						'SurveyChoices' => $SurveyChoices, 
						'Payments' => $Payments, 
						'BuyerDetails' => $BuyerDetails, 
						'ShippingOptions' => $ShippingOptions, 
						'BillingAgreements' => $BillingAgreements
					);
		$PayPalResult = $this->paypal_pro->SetExpressCheckout($PayPalRequestData);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			$token = $PayPalResult['TOKEN'];
			$link = $PayPalResult['REDIRECTURL'];
			//echo "<br>redirect link :".$link;
			//-------29 march------
			redirect($link );
			//---------------------
			// Successful call.  Load view or whatever you need to do here.	
		}
		}
	//}
	
	
	function Get_express_checkout_details($token)
	{			
		$PayPalResult = $this->paypal_pro->GetExpressCheckoutDetails($token);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// Successful call.  Load view or whatever you need to do here.	
		}
	}
	

	
	function Create_recurring_payments_profile()
	{
		$token = $_GET['token'];
		$PayerID = $_GET['PayerID'];
		
		$PayPalResult = $this->paypal_pro->GetExpressCheckoutDetails($token);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{

		$CRPPFields = array(
					'token' => $token, 					// Token returned from PayPal SetExpressCheckout.  Can also use token returned from SetCustomerBillingAgreement.
						);
						
		$ProfileDetails = array(
							'subscribername' => $PayPalResult['FIRSTNAME'].''.$PayPalResult['LASTNAME'], 	// Full name of the person receiving the product or service paid for by the recurring payment.  32 char max.
							'profilestartdate' => gmdate("c"), 			// Required.  The date when the billing for this profiile begins.  Must be a valid date in UTC/GMT format.
							'profilereference' => '' // The merchant's own unique invoice number or reference ID.  127 char max.
						);
						

		$ScheduleDetails = array(
							'desc' => $PayPalResult['DESC'],	// Required.  Description of the recurring payment.  This field must match the corresponding billing agreement description included in SetExpressCheckout.
							'maxfailedpayments' => '1', 					// The number of scheduled payment periods that can fail before the profile is automatically suspended.  
							'autobilloutamt' => 'AddToNextBilling' 				// This field indiciates whether you would like PayPal to automatically bill the outstanding balance amount in the next billing cycle.  Values can be: NoAutoBill or AddToNextBilling
						);
						
		$BillingPeriod = array(
							'trialbillingperiod' => 'Month', 
							'trialbillingfrequency' => '1', 
							'trialtotalbillingcycles' => '1', 
							'trialamt' => '0.00', 
							'billingperiod' => 'Month', 				// Required.  Unit for billing during this subscription period.  One of the following: Day, Week, SemiMonth, Month, Year
							'billingfrequency' => '12', 				// Required.  Number of billing periods that make up one billing cycle.  The combination of billing freq. and billing period must be less than or equal to one year. 
							'totalbillingcycles' => '1', 				// the number of billing cycles for the payment period (regular or trial).  For trial period it must be greater than 0.  For regular payments 0 means indefinite...until canceled.  
							'amt' => '0.00', 							// Required.  Billing amount for each billing cycle during the payment period.  This does not include shipping and tax. 
							'currencycode' => 'USD', 					// Required.  Three-letter currency code.
							'shippingamt' => '', 						// Shipping amount for each billing cycle during the payment period.
							'taxamt' => '' 								// Tax amount for each billing cycle during the payment period.
						);
						
		$ActivationDetails = array(
							'initamt' => '10.00', 							// Initial non-recurring payment amount due immediatly upon profile creation.  Use an initial amount for enrolment or set-up fees.
							'failedinitamtaction' => 'CancelOnFailure', 				// By default, PayPal will suspend the pending profile in the event that the initial payment fails.  You can override this.  Values are: ContinueOnFailure or CancelOnFailure
						);
						
		$CCDetails = array(
							'creditcardtype' => '', 					// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
							'acct' => '', 					// Required.  Credit card number.  No spaces or punctuation.  
							'expdate' => '', 							// Required.  Credit card expiration date.  Format is MMYYYY
							'cvv2' => '', 								// Requirements determined by your PayPal account settings.  Security digits for credit card.
						// 	'startdate' => '062016', 							// Month and year that Maestro or Solo card was issued.  MMYYYY
						//	'issuenumber' => ''							// Issue number of Maestro or Solo card.  Two numeric digits max.
						);
				// $CCDetails = array(
				// 			'creditcardtype' => 'Visa', 					// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
				// 			'acct' => '4032030392586047', 					// Required.  Credit card number.  No spaces or punctuation.  
				// 			'expdate' => '062021', 							// Required.  Credit card expiration date.  Format is MMYYYY
				// 			'cvv2' => '165', 								// Requirements determined by your PayPal account settings.  Security digits for credit card.
				// 		// 	'startdate' => '062016', 							// Month and year that Maestro or Solo card was issued.  MMYYYY
				// 		//	'issuenumber' => ''							// Issue number of Maestro or Solo card.  Two numeric digits max.
				// 		);
						
						
		$PayerInfo = array(
							'email' => 'varsha@esferasoft.com', 		// Email address of payer.
							'payerid' => $PayerID, 							// Unique PayPal customer ID for payer.
							'payerstatus' => '', 						// Status of payer.  Values are verified or unverified
							'business' => '' 							// Payer's business name.
						);
						
		$PayerName = array(
							'salutation' => '', 						// Payer's salutation.  20 char max.
							'firstname' => 'Versha', 							// Payer's first name.  25 char max.
							'middlename' => '', 						// Payer's middle name.  25 char max.
							'lastname' => 'Thakur', 							// Payer's last name.  25 char max.
							'suffix' => ''								// Payer's suffix.  12 char max.
						);
						
		$BillingAddress = array(
								'street' => '123 Test Ave.',			// Required.  First street address.
								'street2' => '', 						// Second street address.
								'city' => 'Kansas City', 				// Required.  Name of City.
								'state' => 'MO', 						// Required. Name of State or Province.
								'countrycode' => 'US', 					// Required.  Country code.
								'zip' => '64111', 						// Required.  Postal code of payer.
								'phonenum' => '' 						// Phone Number of payer.  20 char max.
							);
							
		$ShippingAddress = array(
								'shiptoname' => '', 					// Required if shipping is included.  Person's name associated with this address.  32 char max.
								'shiptostreet' => '', 					// Required if shipping is included.  First street address.  100 char max.
								'shiptostreet2' => '', 					// Second street address.  100 char max.
								'shiptocity' => '', 					// Required if shipping is included.  Name of city.  40 char max.
								'shiptostate' => '', 					// Required if shipping is included.  Name of state or province.  40 char max.
								'shiptozip' => '', 						// Required if shipping is included.  Postal code of shipping address.  20 char max.
								'shiptocountry' => '', 				// Required if shipping is included.  Country code of shipping address.  2 char max.
								'shiptophonenum' => ''					// Phone number for shipping address.  20 char max.
								);
		$OrderItems = array();
			
		$Item	 = array(
							'l_name' => 'Bike', 						// Item Name.  127 char max.
							'l_desc' => 'Reference site about Lorem Ipsum, giving information on its origins, as well as a random Lipsum generator.', 						// Item description.  127 char max.
							'l_amt' => '1.00', 							// Cost of individual item.
							'l_number' => '1', 						// Item Number.  127 char max.
							'l_qty' => '1', 							// Item quantity.  Must be any positive integer.  
							'l_taxamt' => '10.00', 						// Item's sales tax amount.
							'l_ebayitemnumber' => '', 				// eBay auction number of item.
							'l_ebayitemauctiontxnid' => '', 		// eBay transaction ID of purchased item.
							'l_ebayitemorderid' => '' 				// eBay order ID for the item.
					);
		
		array_push($OrderItems, $Item);
		
		$PayPalRequestData = array(
							'CRPPFields' => $CRPPFields, 
							'ProfileDetails' => $ProfileDetails, 
							'ScheduleDetails' => $ScheduleDetails, 
							'BillingPeriod' => $BillingPeriod, 
							'ActivationDetails' => $ActivationDetails, 
							'CCDetails' => $CCDetails, 
							'PayerInfo' => $PayerInfo, 
							'PayerName' => $PayerName, 
							'BillingAddress' => $BillingAddress, 
							'ShippingAddress' => $ShippingAddress,
							'OrderItems' => $OrderItems
						);	

		
	
		$PayPalResult1 = $this->paypal_pro->CreateRecurringPaymentsProfile($PayPalRequestData);
	
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// echo "<pre>";
			// print_r($PayPalResult1);
			// echo "</pre>";
				// $token = $_GET['token'];
				// $PayerID = $_GET['PayerID'];
			redirect('/payments_pro/Do_express_checkout_payment?token='.$token.'&PayerID='.$PayerID.'');
			//redirect();
			// Successful call.  Load view or whatever you need to do here.	
		}	
		}
	}
	
		
	function Do_express_checkout_payment()
	{
		date_default_timezone_set("UTC");	
		if (!empty($_GET)) {
		$data["title"] = "Home";
		$data["assets_path"] = base_url().$this->config->item('assets_path');
			# code...
		$token = $_GET['token'];
		$PayerID = $_GET['PayerID'];

		$PayPalResult1 = $this->paypal_pro->GetExpressCheckoutDetails($token);
		

		if(!$this->paypal_pro->APICallSuccessful($PayPalResult1['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult1['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{

		$DECPFields = array(
							'token' => $token, 							// Required.  A timestamped token, the value of which was returned by a previous SetExpressCheckout call.
							'payerid' => $PayerID, 						// Required.  Unique PayPal customer id of the payer.  Returned by GetExpressCheckoutDetails, or if you used SKIPDETAILS it's returned in the URL back to your RETURNURL.
							'returnfmfdetails' => '', 					// Flag to indiciate whether you want the results returned by Fraud Management Filters or not.  1 or 0.
							'giftmessage' => '', 						// The gift message entered by the buyer on the PayPal Review page.  150 char max.
							'giftreceiptenable' => '', 					// Pass true if a gift receipt was selected by the buyer on the PayPal Review page. Otherwise pass false.
							'giftwrapname' => '', 						// The gift wrap name only if the gift option on the PayPal Review page was selected by the buyer.
							'giftwrapamount' => '', 					// The amount only if the gift option on the PayPal Review page was selected by the buyer.
							'buyermarketingemail' => '', 				// The buyer email address opted in by the buyer on the PayPal Review page.
							'surveyquestion' => '', 					// The survey question on the PayPal Review page.  50 char max.
							'surveychoiceselected' => '',  				// The survey response selected by the buyer on the PayPal Review page.  15 char max.
							'allowedpaymentmethod' => '' 				// The payment method type. Specify the value InstantPaymentOnly.
						);
						
		// You can now utlize parallel payments (split payments) within Express Checkout.
		// Here we'll gather all the payment data for each payment included in this checkout 
		// and pass them into a $Payments array.  
		
		// Keep in mind that each payment will ahve its own set of OrderItems
		// so don't get confused along the way.	
							
		$Payments = array();
		$Payment = array(
						'amt' => $PayPalResult1['AMT'], 							// Required.  The total cost of the transaction to the customer.  If shipping cost and tax charges are known, include them in this value.  If not, this value should be the current sub-total of the order.
						'currencycode' => $PayPalResult1['CURRENCYCODE'], 		// A three-character currency code.  Default is USD.
						'itemamt' => $PayPalResult1['ITEMAMT'], 						// Required if you specify itemized L_AMT fields. Sum of cost of all items in this order.  
						'shippingamt' => '', 					// Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
						'shipdiscamt' => '', 					// Shipping discount for this order, specified as a negative number.
						'insuranceoptionoffered' => '', 		// If true, the insurance drop-down on the PayPal review page displays the string 'Yes' and the insurance amount.  If true, the total shipping insurance for this order must be a positive number.
						'handlingamt' => '', 					// Total handling costs for this order.  If you specify HANDLINGAMT you mut also specify a value for ITEMAMT.
						'taxamt' => '', 						// Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order. 
						'desc' => $PayPalResult1['DESC'], 		// Description of items on the order.  127 char max.
						'custom' => '', 						// Free-form field for your own use.  256 char max.
						'invnum' => '', 						// Your own invoice or tracking number.  127 char max.
						'notifyurl' => '', 						// URL for receiving Instant Payment Notifications
						'shiptoname' => '', 					// Required if shipping is included.  Person's name associated with this address.  32 char max.
						'shiptostreet' => '', 					// Required if shipping is included.  First street address.  100 char max.
						'shiptostreet2' => '', 					// Second street address.  100 char max.
						'shiptocity' => '', 					// Required if shipping is included.  Name of city.  40 char max.
						'shiptostate' => '', 					// Required if shipping is included.  Name of state or province.  40 char max.
						'shiptozip' => '', 						// Required if shipping is included.  Postal code of shipping address.  20 char max.
						'shiptocountrycode' => '', 				// Required if shipping is included.  Country code of shipping address.  2 char max.
						'shiptophonenum' => '',  				// Phone number for shipping address.  20 char max.
						'notetext' => '', 						// Note to the merchant.  255 char max.  
						'allowedpaymentmethod' => '', 			// The payment method type.  Specify the value InstantPaymentOnly.
						'paymentaction' => '', 					// How you want to obtain the payment.  When implementing parallel payments, this field is required and must be set to Order. 
						'paymentrequestid' => '',  				// A unique identifier of the specific payment request, which is required for parallel payments. 
						'sellerid' => '', 						// The unique non-changing identifier for the seller at the marketplace site.  This ID is not displayed.
						'sellerusername' => '', 				// The current name of the seller or business at the marketplace site.  This name be shown to the buyer.
						'sellerregistrationdate' => '', 		// Date when the seller registered with the marketplace.
						'softdescriptor' => '', 				// A per transaction description of the payment that is passed to the buyer's credit card statement.
						'transactionid' => ''					// Tranaction identification number of the tranasction that was created.  NOTE:  This field is only returned after a successful transaction for DoExpressCheckout has occurred. 
						);
			
		// For order items you populate a nested array with multiple $Item arrays.  
		// Normally you'll be looping through cart items to populate the $Item array
		// Then push it into the $OrderItems array at the end of each loop for an entire 
		// collection of all items in $OrderItems.
					
		$PaymentOrderItems = array();
		$Item = array(
					'name' => $PayPalResult1['ORDERITEMS'][0]['L_NAME'], 								// Item name. 127 char max.
					'desc' => $PayPalResult1['ORDERITEMS'][0]['L_DESC'], 								// Item description. 127 char max.
					'amt' =>  $PayPalResult1['ORDERITEMS'][0]['L_AMT'], 								// Cost of item.
					'number' => $PayPalResult1['ORDERITEMS'][0]['L_NUMBER'] , 							// Item number.  127 char max.
					'qty' => $PayPalResult1['ORDERITEMS'][0]['L_QTY'], 								// Item qty on order.  Any positive integer.
					'taxamt' => '', 							// Item sales tax
					'itemurl' => '', 							// URL for the item.
					'itemweightvalue' => '', 					// The weight value of the item.
					'itemweightunit' => '', 					// The weight unit of the item.
					'itemheightvalue' => '', 					// The height value of the item.
					'itemheightunit' => '', 					// The height unit of the item.
					'itemwidthvalue' => '', 					// The width value of the item.
					'itemwidthunit' => '', 						// The width unit of the item.
					'itemlengthvalue' => '', 					// The length value of the item.
					'itemlengthunit' => '',  					// The length unit of the item.
					'itemurl' => '', 							// The URL for the item.
					'itemcategory' => '', 						// Must be one of the following:  Digital, Physical
					'ebayitemnumber' => '', 					// Auction item number.  
					'ebayitemauctiontxnid' => '', 				// Auction transaction ID number.  
					'ebayitemorderid' => '',  					// Auction order ID number.
					'ebayitemcartid' => ''						// The unique identifier provided by eBay for this order from the buyer. These parameters must be ordered sequentially beginning with 0 (for example L_EBAYITEMCARTID0, L_EBAYITEMCARTID1). Character length: 255 single-byte characters
					);
		array_push($PaymentOrderItems, $Item);
		
		// Now we've got our OrderItems for this individual payment, 
		// so we'll load them into the $Payment array
		$Payment['order_items'] = $PaymentOrderItems;
		
		// Now we add the current $Payment array into the $Payments array collection
		array_push($Payments, $Payment);
		
		$UserSelectedOptions = array(
									 'shippingcalculationmode' => '', 	// Describes how the options that were presented to the user were determined.  values are:  API - Callback   or   API - Flatrate.
									 'insuranceoptionselected' => '', 	// The Yes/No option that you chose for insurance.
									 'shippingoptionisdefault' => '', 	// Is true if the buyer chose the default shipping option.  
									 'shippingoptionamount' => '', 		// The shipping amount that was chosen by the buyer.
									 'shippingoptionname' => '', 		// Is true if the buyer chose the default shipping option...??  Maybe this is supposed to show the name..??
									 );
									 
		$PayPalRequestData = array(
							'DECPFields' => $DECPFields, 
							'Payments' => $Payments, 
							'UserSelectedOptions' => $UserSelectedOptions
						);
						
		$PayPalResult = $this->paypal_pro->DoExpressCheckoutPayment($PayPalRequestData);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
		 
	
			$first_name = $PayPalResult1['FIRSTNAME'];	
			$address = "My days users ";	
			$email =$PayPalResult1 ['EMAIL'];
			$password = 123456789;
			//echo "hele";

			if($email !='' && $this->auth_model->validate_email($email) == false){
				$ReturnData['error']['email']="Please enter valid Email-Id";
			}

			if(($email !=''  && $this->auth_model->validate_email($email) == true) || $address !='')
			{
				//echo "here";
				$data_feed = array(
					'admin_email' => $email
				);
				//print_r($data_feed) ;

				$result = $this->user_model->get_user_byemail($data_feed);
			
				$result1 = $this->user_model->get_user_byaddress(array('address' => $address));
				 $date = date('Y-m-d H:i:s');
				if (empty($result) && empty($result1)) {
					$default_data = array("first_name" => $first_name,"address" => $address,"email" => $email,"password" => md5($password), "logtime"=>$date);
					$id_record=$this->user_model->insert_user($default_data, 'ci_admin');
				    if($id_record)
				    {
				    	$session_data = array(
							'admin_id' => $id_record,
							'admin_email' => $email,
							'user_type' => 'simpleuser',
						);
						$this->session->set_userdata('logged_in', $session_data);
						$ReturnData['success']="Valid User";
						
				    } 
				}
				else{
					if($result)
					{
						$ReturnData['user_exist']="User with this Email-Id already exists";
						// $default_data = array("first_name" => $first_name,"address" => $address,"email" => $email,"password" => md5($password), "logtime"=>date('Y-m-d H:i:s'));
						// 	$id_record=$this->user_model->insert_user($default_data, 'ci_admin');
						   	$session_data = array(
							'admin_id' => $result['id'],
							'admin_email' => $result['email'],
							'user_type' => 'simpleuser',
						);


						 $config = array();  
							$config['protocol'] = 'smtp';  
							$config['smtp_host'] = 'ssl://smtp.gmail.com'; 
							$config['smtp_user'] = 'mailadmin@yourday.io';  
							$config['smtp_pass'] = 'mailadmin@2704';  
							$config['smtp_port'] = 465;
							$config['mailtype'] = 'html';
							$config['smtp_crypto'] = 'ssl';  	

						$this->load->library('email', $config);	
				      $message = 'http://www.yourday.io/index.php/Password_rest/';
				      //$this->load->library('email', $config);
				      $this->email->set_newline("\r\n");
				      $this->email->from('mailadmin@yourday.io'); // change it to yours
				      $this->email->to($email);// change it to yours
				      $this->email->subject('Please update your password click this link!');
				      $this->email->message($message);
				     

				      if($this->email->send())
					     {
					      echo 'Email sent.';
					     }
				     else
					    {
					     show_error($this->email->print_debugger());
					    }



						$this->session->set_userdata('logged_in', $session_data);
						$ReturnData['success']="Valid User";
						redirect(site_url('dashboard'));

					}
					if($result1) {
						
						//$ReturnData['error']['address'] = "This Property Name is already exist";	
					$date = date('Y-m-d H:i:s');
					$address = $address."_".rand(10, 99);			
					$default_data = array("first_name" => $first_name,"address" => $address,"email" => $email,"password" => md5($password), "logtime"=>$date);
					$id_record=$this->user_model->insert_user($default_data, 'ci_admin');
				   }
				    if($id_record)
				    {
				    	$session_data = array(
							'admin_id' => $id_record,
							'admin_email' => $email,
							'user_type' => 'simpleuser',
						);
						$this->session->set_userdata('logged_in', $session_data);
						$ReturnData['success']="Valid User";
					}

					   	$session_data = array(
							'admin_id' => $id_record,
							'admin_email' => $email,
							'user_type' => 'simpleuser',
						);
						$this->session->set_userdata('logged_in', $session_data);
						$ReturnData['success']="Valid User";
					
			   	}
			}

			//$this->load->view('home/thankyou',$data);
			// Successful call.  Load view or whatever you need to do here.	
		}
		}
	}	
	else{
		$data["title"] = "Home";
		$data["assets_path"] = base_url().$this->config->item('assets_path');
		//$this->load->view('home/thankyou',$data);
		$this->load->view('home/thankyou',$data);

	}
	
}	
	function Get_balance()
	{		
		$GBFields = array('returnallcurrencies' => '1');
		$PayPalRequestData = array('GBFields'=>$GBFields);
		$PayPalResult = $this->paypal_pro->GetBalance($PayPalRequestData);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// Successful call.  Load view or whatever you need to do here.
		}
	}

	function Get_recurring_payments_profile_details()
	{
		$GRPPDFields = array(
					   'profileid' => ''			// Profile ID of the profile you want to get details for.
					   );
					   
		$PayPalRequestData = array('GRPPDFields' => $GRPPDFields);
		
		$PayPalResult = $this->paypal_pro->GetRecurringPaymentsProfileDetails($PayPalRequestData);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// Successful call.  Load view or whatever you need to do here.	
		}	
	}
	
	
	function Update_recurring_payments_profile()
	{
		$URPPFields = array(
						   'profileid' => '', 							// Required.  Recurring payments ID.
						   'note' => '', 								// Note about the reason for the update to the profile.  Included in EC profile notification emails and in details pages.
						   'desc' => '', 								// Description of the recurring payment profile.
						   'subscribername' => '', 						// Full name of the person receiving the product or service paid for by the recurring payment profile.
						   'profilereference' => '', 					// The merchant's own unique reference or invoice number.
						   'additionalbillingcycles' => '', 			// The number of additional billing cycles to add to this profile.
						   'amt' => '', 								// Billing amount for each cycle in the subscription, not including shipping and tax.  Express Checkout profiles can only be updated by 20% every 180 days.
						   'shippingamt' => '', 						// Shipping amount for each billing cycle during the payment period.
						   'taxamt' => '',  							// Tax amount for each billing cycle during the payment period.
						   'outstandingamt' => '', 						// The current past-due or outstanding amount.  You can only decrease this amount.  
						   'autobilloutamt' => '', 						// This field indiciates whether you would like PayPal to automatically bill the outstanding balance amount in the next billing cycle.
						   'maxfailedpayments' => '', 					// The number of failed payments allowed before the profile is automatically suspended.  The specified value cannot be less than the current number of failed payments for the profile.
						   'profilestartdate' => ''						// The date when the billing for this profile begins.  UTC/GMT format.
						   );
		
		$BillingAddress = array(
							'street' => '', 						// Required.  First street address.
							'street2' => '', 						// Second street address.
							'city' => '', 							// Required.  Name of City.
							'state' => '', 							// Required. Name of State or Province.
							'countrycode' => '', 					// Required.  Country code.
							'zip' => '', 							// Required.  Postal code of payer.
							'phonenum' => '' 						// Phone Number of payer.  20 char max.
						);
		
		$ShippingAddress = array(
							'shiptoname' => '', 					// Required if shipping is included.  Person's name associated with this address.  32 char max.
							'shiptostreet' => '', 					// Required if shipping is included.  First street address.  100 char max.
							'shiptostreet2' => '', 					// Second street address.  100 char max.
							'shiptocity' => '', 					// Required if shipping is included.  Name of city.  40 char max.
							'shiptostate' => '', 					// Required if shipping is included.  Name of state or province.  40 char max.
							'shiptozip' => '', 						// Required if shipping is included.  Postal code of shipping address.  20 char max.
							'shiptocountry' => '', 				// Required if shipping is included.  Country code of shipping address.  2 char max.
							'shiptophonenum' => ''					// Phone number for shipping address.  20 char max.
							);
		
		$BillingPeriod = array(
						'trialbillingperiod' => '', 
						'trialbillingfrequency' => '', 
						'trialtotalbillingcycles' => '', 
						'trialamt' => '', 
						'billingperiod' => '', 						// Required.  Unit for billing during this subscription period.  One of the following: Day, Week, SemiMonth, Month, Year
						'billingfrequency' => '', 					// Required.  Number of billing periods that make up one billing cycle.  The combination of billing freq. and billing period must be less than or equal to one year. 
						'totalbillingcycles' => '', 				// the number of billing cycles for the payment period (regular or trial).  For trial period it must be greater than 0.  For regular payments 0 means indefinite...until canceled.  
						'amt' => '', 								// Required.  Billing amount for each billing cycle during the payment period.  This does not include shipping and tax. 
						'currencycode' => '', 						// Required.  Three-letter currency code.
					);
		
		$CCDetails = array(
						'creditcardtype' => '', 					// Required. Type of credit card.  Visa, MasterCard, Discover, Amex, Maestro, Solo.  If Maestro or Solo, the currency code must be GBP.  In addition, either start date or issue number must be specified.
						'acct' => '', 								// Required.  Credit card number.  No spaces or punctuation.  
						'expdate' => '', 							// Required.  Credit card expiration date.  Format is MMYYYY
						'cvv2' => '', 								// Requirements determined by your PayPal account settings.  Security digits for credit card.
						'startdate' => '', 							// Month and year that Maestro or Solo card was issued.  MMYYYY
						'issuenumber' => ''							// Issue number of Maestro or Solo card.  Two numeric digits max.
					);
		
		$PayerInfo = array(
						'email' => '', 								// Payer's email address.
						'firstname' => '', 							// Required.  Payer's first name.
						'lastname' => ''							// Required.  Payer's last name.
					);	
					
		$PayPalRequestData = array(
							'URPPFields' => $URPPFields, 
							'BillingAddress' => $BillingAddress, 
							'ShippingAddress' => $ShippingAddress, 
							'BillingPeriod' => $BillingPeriod, 
							'CCDetails' => $CCDetails, 
							'PayerInfo' => $PayerInfo
						);
						
		$PayPalResult = $this->paypal_pro->UpdateRecurringPaymentsProfile($PayPalRequestData);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// Successful call.  Load view or whatever you need to do here.	
		}	
	}
	

	
	function Set_auth_flow_param()
	{
		$SetAuthFlowParamFields = array(
										'ReturnURL' => '', 														// URL to which the customer's browser is returned after choosing to authenticate with PayPal
										'CancelURL' => '', 														// URL to which the customer is returned if they decide not to log in.
										'LogoutURL' => '', 														// URL to which the customer is returned after logging out from your site.
										'LocalCode' => '', 														// Local of pages displayed by PayPal during authentication.  AU, DE, FR, IT, GB, ES, US
										'PageStyle' => '', 														// Sets the custom payment page style of the PayPal pages associated with this button/link.
										'HDRIMG' => '', 														// URL for the iamge you want to appear at the top of the PayPal pages.  750x90.  Should be stored on a secure server.  127 char max.
										'HDRBorderColor' => '', 												// Sets the border color around the header on PayPal pages.HTML Hexadecimal value.
										'HDRBackColor' => '', 													// Sets the background color for PayPal pages.
										'PayFlowColor' => '', 													// Sets the background color for the payment page.
										'InitFlowType' => '', 													// The initial flow type, which is one of the following:  login  / signup   Default is login.
										'FirstName' => '', 														// Customer's first name.
										'LastName' => '',  														// Customer's last name.
										'ServiceName1' => 'Name', 
										'ServiceName2' => 'Email', 
										'ServiceDefReq1' => 'Required', 
										'ServiceDefReq2' => 'Required'
										);
		
		$ShippingAddress = array(
								'ShipToName' => '', 													// Persona's name associated with this address.
								'ShipToStreet' => '', 													// First street address.
								'ShipToStreet2' => '', 													// Second street address.
								'ShipToCity' => '', 													// Name of city.
								'ShipToState' => '', 													// Name of State or Province.
								'ShipToZip' => '', 														// US Zip code or other country-specific postal code.
								'ShipToCountryCode' => '' 												// Country code.
								 );
								 
		$PayPalRequestData = array(
							'SetAuthFlowParamFields' => $SetAuthFlowParamFields, 
							'ShippingAddress' => $ShippingAddress
						);	
						
		$PayPalResult = $this->paypal_pro->SetAuthFlowParam($PayPalRequestData);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// Successful call.  Load view or whatever you need to do here.	
		}	
	}
	
	function Update_access_permissions($payer_id)
	{
		$PayPalResult = $this->paypal_pro->UpdateAccessPermissions($payer_id);	
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// Successful call.  Load view or whatever you need to do here.	
		}
	}
	
	
	function Create_billing_agreement($token = "")
	{
		$PayPalResult = $this->paypal_pro->CreateBillingAgreement($token);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// Successful call.  Load view or whatever you need to do here.	
		}	
	}
	
	
	function Set_customer_billing_agreement()
	{
		// Prepare request arrays
		$SCBAFields = array(
							'returnurl' => '', 									// Required.  URL to which the customer's browser is returned after chooing to pay with PayPal.
							'cancelurl' => '', 									// Required.  URL to which the customer is returned if he does not approve the use of PayPal to pay you.
							'localcode' => '', 									// Local of pages displayed by PayPal during checkout.  
							'pagestyle' => '', 									// Sets the custom payment page style for payment pages associated with this button/link.
							'hdrimg' => '', 									// A URL for the image you want to appear at the top, left of the payment page.  Max size 750 x 90
							'hdrbordercolor' => '', 							// Sets the border color around the header of the payment page.
							'hdrbackcolor' => '', 								// Sets the background color for the header of the payments page.
							'payflowcolor' => '', 								// Sets the background color for the payment page.
							'email' => ''										// Email address of the buyer as entered during checkout.  Will be pre-filled if included.
							);	
							
		$BillingAgreements = array();
		$Item = array(
					  'l_billingtype' => '', 							// Required.  Type of billing agreement.  For recurring payments it must be RecurringPayments.  You can specify up to ten billing agreements.  For reference transactions, this field must be either:  MerchantInitiatedBilling, or MerchantInitiatedBillingSingleSource
					  'l_billingagreementdescription' => '', 			// Required for recurring payments.  Description of goods or services associated with the billing agreement.  
					  'l_paymenttype' => '', 							// Specifies the type of PayPal payment you require for the billing agreement.  Any or IntantOnly
					  'l_billingagreementcustom' => ''					// Custom annotation field for your own use.  256 char max.
					  );
		array_push($BillingAgreements, $Item);
		
		$PayPalRequestData = array(
								'SCBAFields' => $SCBAFields, 
								'BillingAgreements' => $BillingAgreements
								);
		
		// Pass data into class for processing with PayPal and load the response array into $PayPalResult
		$PayPalResult = $this->paypal_pro->SetCustomerBillingAgreement($PayPalRequestData);
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// Successful call.  Load view or whatever you need to do here.	
		}		
	}
	
	
	function Get_billing_agreement_customer_details($token = "")
	{
		$PayPalResult = $this->paypal_pro->GetBillingAgreementCustomerDetails($Token);	
		
		if(!$this->paypal_pro->APICallSuccessful($PayPalResult['ACK']))
		{
			$errors = array('Errors'=>$PayPalResult['ERRORS']);
			$this->load->view('paypal/samples/error',$errors);
		}
		else
		{
			// Successful call.  Load view or whatever you need to do here.	
		}	
	}
	  public function htmlmail()
	  {

	  	 $this->load->library('email');
            $config['protocol']    = 'smtp';
            $config['smtp_host']    = 'ssl://smtp.gmail.com';
            $config['smtp_port']    = '465';
            $config['smtp_timeout'] = '7';
            $config['smtp_user']    = 'mailadmin@yourday.io';
            $config['smtp_pass']    = 'mailadmin@2704';
            $config['charset']    = 'utf-8';
            $config['newline']    = "\r\n";
            $config['mailtype'] = 'text'; // or html
            $config['validation'] = TRUE; // bool whether to validate email or not      
            $this->email->initialize($config);
            $this->email->set_newline("\r\n");  
            $this->email->from('mailadmin@yourday.io', 'sender_name');
            $this->email->to('pankaj_joshi@esferasoft.com'); 
            $this->email->subject('Email Test');
            $this->email->message('Testing the email class.');  
            $this->email->send();
            echo $this->email->print_debugger();
  
    }
    //-------authorize.net test------------------
    public function pay_instant()
	{
		//error_reporting(E_ERROR | E_PARSE);
		// Authorize.net lib
		$this->load->library('Authorize_net');
		/*	The customer’s card code.The three- or four-digit number on the back of a credit card (on the front for American Express). */ 
		$amount=2;
		// $auth_net = array(
		// 	'x_card_num'			=> '370000000000002', // American express  without space
		// 	'x_exp_date'			=> '12/17',
		// 	'x_card_code'			=> '123',
		// 	'x_description'			=> 'A test transaction',
		// 	'x_amount'				=> $amount,
		// 	'x_first_name'			=> 'Aman',
		// 	'x_last_name'			=> 'Raj',
		// 	'x_address'				=> '123 Green St.',
		// 	'x_city'				=> 'Lexington',
		// 	'x_state'				=> 'KY',
		// 	'x_zip'					=> '40502',
		// 	'x_country'				=> 'US',
		// 	'x_phone'				=> '555-123-4567',
		// 	'x_email'				=> 'mk@example.com',
		// 	'x_customer_ip'			=> $this->input->ip_address(),
		// 	);
		//$auth_amount=$_POST['auth_amount'];
		$x_card_num=$_POST['x_card_num'];
		$x_exp_date=$_POST['x_exp_date'];
		$x_card_code=$_POST['x_card_code'];
		$x_first_name=$_POST['x_first_name'];
		$x_last_name=$_POST['x_last_name'];
		//$x_description=$_POST['x_description'];
		$x_address=$_POST['x_address'];
		$x_country=$_POST['x_country'];
		$x_state=$_POST['x_state'];
		$x_city=$_POST['x_city'];
		$x_zip=$_POST['x_zip'];
		$x_phone=$_POST['x_phone'];
		$x_email=$_POST['x_email'];

		$auth_net = array(
			'x_card_num'			=> $x_card_num, // American express  without space
			'x_exp_date'			=> $x_exp_date,
			'x_card_code'			=> $x_card_code,
			'x_description'			=> 'A test transaction',
			'x_amount'				=> $auth_amount,
			'x_first_name'			=> $x_first_name,
			'x_last_name'			=> $x_last_name,
			'x_address'				=> $x_address,
			'x_city'				=> $x_city,
			'x_state'				=> $x_state,
			'x_zip'					=> $x_zip,
			'x_country'				=> $x_country,
			'x_phone'				=> $x_phone,
			'x_email'				=> $x_email,
			'x_customer_ip'			=> $this->input->ip_address(),
			);
		$this->authorize_net->setData($auth_net);

		// Try to AUTH_CAPTURE
		if( $this->authorize_net->authorizeAndCapture() )
		{
			echo '<h2>Success!</h2>';
			echo '<p>Transaction ID: ' . $this->authorize_net->getTransactionId() . '</p>';
			echo '<p>Approval Code: ' . $this->authorize_net->getApprovalCode() . '</p>';
		}
		else
		{
			echo '<h2>Fail!</h2>';
			// Get error
			echo '<p>' . $this->authorize_net->getError() . '</p>';
			// Show debug data
			$this->authorize_net->debug();
		}
	}


    //-------------------------------------------
       
}


/* End of file Payments_pro.php */
/* Location: ./system/application/controllers/paypal/samples/Payments_pro.php */
