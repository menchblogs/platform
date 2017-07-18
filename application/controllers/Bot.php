<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bot extends CI_Controller {
	
	function __construct() {
		parent::__construct();
		
		//Load our buddies:
		$this->output->enable_profiler(FALSE);
	}
	
	
	function t(){
		require( '/var/www/us/application/libraries/aws/aws-autoloader.php' );
		
	}
	
	function fetch_entity($apiai_id){
		header('Content-Type: application/json');
		echo json_encode($this->Apiai_model->fetch_entity($apiai_id));
	}
	
	function fetch_intent($apiai_id){
		header('Content-Type: application/json');
		echo json_encode($this->Apiai_model->fetch_intent($apiai_id));
	}
	
	function prep_intent($pid){
		header('Content-Type: application/json');
		echo json_encode($this->Apiai_model->prep_intent($pid));
	}
	
	
	
	
	
	
	
	function facebook_webhook(){
		
		/*
		 * 
		 * Used for all webhooks from facebook, including user messaging, delivery notifications, etc...
		 * 
		 * */
		
		
		//Facebook Webhook Authentication:
		$challenge = ( isset($_GET['hub_challenge']) ? $_GET['hub_challenge'] : null );
		$verify_token = ( isset($_GET['hub_verify_token']) ? $_GET['hub_verify_token'] : null );
		if ($verify_token == '722bb4e2bac428aa697cc97a605b2c5a') {
			echo $challenge;
		}
		
		//Fetch input data:
		$json_data = json_decode(file_get_contents('php://input'), true);
		
		//This is for local testing only:
		//$json_data = objectToArray(json_decode('{"object":"page","entry":[{"id":"1782774501750818","time":1500335488255,"messaging":[{"sender":{"id":"1371860399579444"},"recipient":{"id":"1782774501750818"},"timestamp":1500335488096,"message":{"mid":"mid.$cAAZVbLheHRBjhDwEYFdUva5X8KT_","seq":29782,"attachments":[{"type":"audio","payload":{"url":"https:\/\/cdn.fbsbx.com\/v\/t59.3654-21\/19359558_10158969505640587_4006997452564463616_n.aac\/audioclip-1500335487327-1590.aac?oh=5344e3d423b14dee5efe93edd432d245&oe=596FEA95"}}]}}]}],"api_ai":[]}'));
		
		//Do some basic checks:
		if(!isset($json_data['object']) || !isset($json_data['entry'])){
			log_error('Facebook webhook call missing either object/entry variables.',$json_data);
			return false;
		} elseif(!$json_data['object']=='page'){
			log_error('Facebook webhook call object value is not equal to [page], which is what was expected.',$json_data);
			return false;
		}
		
		
		//Loop through entries:
		foreach($json_data['entry'] as $entry){
			
			//check the page ID:
			if(!isset($entry['id']) || !fb_page_pid($entry['id'])){
				log_error('Facebook webhook call with unknown page id ['.$entry['id'].'].',$json_data);
				continue;
			} elseif(!isset($entry['messaging'])){
				log_error('Facebook webhook call without the Messaging Array().',$json_data);
				continue;
			}
			
			//loop though the messages:
			foreach($entry['messaging'] as $im){
				
				if(isset($im['read'])){
					
					//This callback will occur when a message a page has sent has been read by the user.
					//https://developers.facebook.com/docs/messenger-platform/webhook-reference/message-read
					//The watermark field is used to determine which messages were read.
					//It represents a timestamp indicating that all messages with a timestamp before watermark were read by the recipient.
					$this->Us_model->log_engagement(array(
							'action_pid' => 1026,
							'json_blob' => json_encode($json_data),
							'us_id' => $this->Us_model->put_fb_user($im['sender']['id']),
							'seq' => $im['read']['seq'], //Message sequence number
							'platform_pid' => fb_page_pid($im['recipient']['id']), //The facebook page
							'api_timestamp' => fb_time($im['read']['watermark']), //Messages sent before this time were read
					));
					
				} elseif(isset($im['delivery'])) {
					
					//This callback will occur when a message a page has sent has been delivered.
					//https://developers.facebook.com/docs/messenger-platform/webhook-reference/message-delivered
					$new = $this->Us_model->log_engagement(array(
							'action_pid' => 1027,
							'json_blob' => json_encode($json_data),
							'us_id' => $this->Us_model->put_fb_user($im['sender']['id']),
							'seq' => $im['delivery']['seq'], //Message sequence number
							'platform_pid' => fb_page_pid($im['recipient']['id']), //The facebook page
							'api_timestamp' => fb_time($im['delivery']['watermark']), //Messages sent before this time were delivered
					));
					
				} elseif(isset($im['referral']) || isset($im['postback'])) {
					
					if(isset($im['postback'])) {
						
						/*
						 * Postbacks occur when a the following is tapped:
						 *
						 * - Postback button
						 * - Get Started button
						 * - Persistent menu item
						 *
						 * Learn more:
						 * 
						 *
						 * */
						
						//The payload field passed is defined in the above places.
						$payload = $im['postback']['payload']; //Maybe do something with this later?
						
						if(isset($im['postback']['referral'])){
							/*
							 * https://developers.facebook.com/docs/messenger-platform/webhook-reference/postback
							 *
							 * This section is present only if:
							 *
							 * - The user entered the thread via an m.me link with a ref parameter and tapped the Get Started button.
							 * - The user entered the thread by scanning a parametric Messenger Code and tapped the Get Started button.
							 * - This is the first postback after user came from a Messenger Conversation Ad.
							 * - The user entered the thread via Discover tab and tapped the Get Started button. See here for more info.
							 *
							 * The information contained in this section follows that of the referral webhook.
							 *
							 * */
							$referral_array = $im['postback']['referral'];
						} else {
							//Postback without referral!
							$referral_array = null;
						}
						
					} elseif(isset($im['referral'])) {
						
						/*
						 * This callback will occur when the user already has a thread with the
						 * bot and user comes to the thread from:
						 *
						 *  - Following an m.me link with a referral parameter
						 *  - Clicking on a Messenger Conversation Ad
						 *  - Scanning a parametric Messenger Code.
						 *
						 *  Learn more:
						 *  https://developers.facebook.com/docs/messenger-platform/webhook-reference/referral
						 *
						 * */
						
						$referral_array = $im['referral'];
					}
					
					
					
					//General variables:
					$eng_data = array(
							'action_pid' => (isset($im['referral']) ? 1028 : 1029), //Either referral or postback
							'json_blob' => json_encode($json_data),
							'us_id' => $this->Us_model->put_fb_user($im['sender']['id']),
							'platform_pid' => fb_page_pid($im['recipient']['id']), //The facebook page
							'api_timestamp' => fb_time($im['timestamp']),
					);
					
					
					if($referral_array && isset($referral_array['ref']) && strlen($referral_array['ref'])>0){
						
						//We have referrer data, see what this is all about!
						//We expect two numbers in the format of 123_456
						//The first number is the intent_pid, where the second one is the referrer PID
						$ref_source = $referral_array['source'];
						$ref_type = $referral_array['type'];
						$ad_id = ( isset($referral_array['ad_id']) ? $referral_array['ad_id'] : null ); //Only IF user comes from the Ad
						
						//Decode ref variable:
						$ref_data = explode('_',$referral_array['ref'],2);
						$eng_data['intent_pid'] = fetch_grandchild($ref_data[0],3,$json_data);
						$eng_data['referrer_pid'] = fetch_grandchild($ref_data[1],1,$json_data);
						
						//Optional actions that may need to be taken on SOURCE:
						if(strtoupper($ref_source)=='ADS' && $ad_id){
							//Ad clicks
							
						} elseif(strtoupper($ref_source)=='SHORTLINK'){
							//Came from m.me short link click
							
						} elseif(strtoupper($ref_source)=='MESSENGER_CODE'){
							//Came from m.me short link click
							
						} elseif(strtoupper($ref_source)=='DISCOVER_TAB'){
							//Came from m.me short link click
							
						}
					}
					
					//Log engagement:
					$new = $this->Us_model->log_engagement($eng_data);
					
				} elseif(isset($im['optin'])) {
					
					/*
					 * This callback will occur when the Send to Messenger plugin has been tapped, 
					 * or when a user has accepted a message request using Customer Matching.
					 * 
					 * https://developers.facebook.com/docs/messenger-platform/webhook-reference/optins
					 * 
					 * 
					 * */
					
					//This parameter is set by the data-ref field on the "Send to Messenger" plugin.
					//This field can be used by the developer to associate a click event on the plugin with a callback.
					$eng_data = array(
							'action_pid' => 1030, //New Optin
							'json_blob' => json_encode($json_data),
							'us_id' => $this->Us_model->put_fb_user($im['sender']['id']),
							'platform_pid' => fb_page_pid($im['recipient']['id']), //The facebook page
							'api_timestamp' => fb_time($im['timestamp']),
					);
					
					//Decode ref variable:
					$ref_data = explode('_',$im['optin']['ref'],2);
					$eng_data['intent_pid'] = fetch_grandchild($ref_data[0],3,$json_data);
					$eng_data['referrer_pid'] = fetch_grandchild($ref_data[1],1,$json_data);
					
					//Log engagement:
					$new = $this->Us_model->log_engagement($eng_data);
					
				} elseif(isset($im['message'])) {
					
					/*
					 * Triggered for both incoming and outgoing messages on behalf of our team
					 * 
					 * */
					
					
					
					//Set variables:
					$sent_from_us = ( isset($im['message']['is_echo']) ); //Indicates the message sent from the page itself
					$user_id = ( $sent_from_us ? $im['recipient']['id'] : $im['sender']['id'] );
					$page_id = ( $sent_from_us ? $im['sender']['id'] : $im['recipient']['id'] );
					$from_helper = ( in_array($user_id,$this->config->item('human_helpers')) );
					
					$eng_data = array(
							'message' => ( isset($im['message']['text']) ? $im['message']['text'] : '' ),
							'external_id' => ( isset($im['message']['mid']) ? $im['message']['mid'] : 0 ),
							'action_pid' => ( $sent_from_us ? 1031 : 1032 ), //Define message direction
							'json_blob' => json_encode($json_data),
							'us_id' => $this->Us_model->put_fb_user($user_id),
							'seq' => ( isset($im['message']['seq']) ? $im['message']['seq'] : 0 ), //Message sequence number
							'platform_pid' => fb_page_pid($page_id), //The facebook page
							'api_timestamp' => fb_time($im['timestamp']), //Facebook timestamp
					);
					
					//Some that are not used yet:
					$app_id  = ( $sent_from_us ? $im['message']['app_id'] : null );
					$metadata = ( isset($im['message']['metadata']) ? $im['message']['metadata'] : null ); //Send API custom string [metadata field]
					
					if($metadata=='SKIP_ECHO_LOGGING'){
						//We've been asked to skip this error logging!
						continue;
					}
					
					//Do some checks:
					if(strlen($eng_data['external_id'])<1){
						log_error('Received message without Facebook Message ID!' , $json_data);
					}
					
					//It may also have an attachment
					//https://developers.facebook.com/docs/messenger-platform/webhook-reference/message
					//https://developers.facebook.com/docs/messenger-platform/webhook-reference/message-echo
					$new_file_url = null; //Would be updated IF message is a file
					if(isset($im['message']['attachments'])){
						//We have some attachments, lets loops through them:
						foreach($im['message']['attachments'] as $att){
							
							if(in_array($att['type'],array('image','audio','video','file'))){
								
								//Store to local DB:
								$new_file_url = save_file($att['payload']['url'],$json_data);
								
								//Message with image attachment
								$eng_data['message'] .= (strlen($eng_data['message'])>0?' ':'').'attachment:'.$att['type'].'::'.$new_file_url;
								
								//Reply:
								$this->Messenger_model->send_message(array(
										'recipient' => array(
												'id' => $user_id
										),
										'sender_action' => 'typing_on'
								));
								
								//Testing for now:
								$this->Messenger_model->send_message(array(
										'recipient' => array(
												'id' => $user_id
										),
										'message' => array(
												'text' => 'Got your messageand will get back to you soon!',
										),
										'notification_type' => 'REGULAR' //Can be REGULAR, SILENT_PUSH or NO_PUSH
								));
								
							} elseif($att['type']=='location'){
								
								//Message with location attachment
								//TODO test to make sure this works!
								$loc_lat = $att['payload']['coordinates']['lat'];
								$loc_long = $att['payload']['coordinates']['long'];
								$eng_data['message'] .= (strlen($eng_data['message'])>0?' ':'').'attachment:location::'.$loc_lat.','.$loc_long;
								
							} elseif($att['type']=='template'){
								
								//Message with template attachment, like a button or something...
								$eng_data['message'] .= (strlen($eng_data['message'])>0?' ':'').'attachment:template';
								
							} elseif($att['type']=='fallback'){
								
								//A fallback attachment is any attachment not currently recognized or supported by the Message Echo feature.
								//This should not happen, report to admin:
								log_error('Received message with [fallback] attachment type!' , $json_data);
								
							} else {
								//This should really not happen!
								log_error('Received Facebook message with unknown attachment type: '.$att['type'] , $json_data);
							}
						}
					}
					
					$quick_reply_pid = null;
					if(isset($im['message']['quick_reply']) && isset($im['message']['quick_reply']['payload']) && intval($im['message']['quick_reply']['payload'])>0){
						//This message has a quick reply in it:
						$quick_reply_pid = fetch_grandchild( $im['message']['quick_reply']['payload'] , 3 , $json_data);
						
						//Lets expand the scope of $eng_data['intent_pid'] & have it store this PID
						$eng_data['intent_pid'] = $quick_reply_pid;
						
						//TODO Lets see what type of pattern is this?
						//IF grandpa_id=3, then > $eng_data['intent_pid'] = $quick_reply_pid;
						
					}
					
					//Should we detect intents/entities with api.ai?
					//Only if not already set in quick_reply.payload & if we have an inbound text
					if(!$quick_reply_pid && strlen($eng_data['message'])>0 && (!$sent_from_us || substr_count($eng_data['message'],'search for')>0)){
						if(substr_count($eng_data['message'],'search for')>0){
							//User has a specific ask!
							$temp = explode('search for',$eng_data['message'],2);
							$search_for = $temp[1];
						} else {
							$search_for = $eng_data['message'];
						}
						
						//Send reuqest to api.ai
						$json_data['api_ai'] = array();
						//Update core variable to store result as well:
						$eng_data['json_blob'] = json_encode($json_data);
						
						//Update correlation:
						$eng_data['correlation'] = 1; //The score from api.ai
					}
					
					
					//Log incoming engagement:
					$this->Us_model->log_engagement($eng_data);
					
					
					
					
					//Should we start talking?!
					if(0 && !$sent_from_us && !isset($im['message']['attachments']) && strlen($eng_data['message'])>0){
						
						//TODO disabled for now, build later
						//Incoming text message, attempt to auto detect it:
						//$eng_data['gem_id'] = ''; //If intent was found, the update ID that was served
						
						//Indicate to the user that we're typing:
						
						
						if(isset($unsubscribed_gem['id'])){
							//Oho! This user is unsubscribed, Ask them if they would like to re-join us:
							$response = array(
								'text' => 'You had unsubscribed from Us. Would you like to re-join?',
							);
						} else {
							//Now figure out the response:
							$response = $this->Us_model->generate_response($eng_data['intent_pid'],$setting);
						}
						 
						//Send message back to user:
						$this->Messenger_model->send_message(array(
								'recipient' => array(
										'id' => $user_id
								),
								'message' => $response,
								'notification_type' => 'REGULAR' //Can be REGULAR, SILENT_PUSH or NO_PUSH
						));
						
						
						//TODO Log outgoing message:
						//$eng_data = array();
						//$new_out = $this->Us_model->log_engagement($eng_data);
					}
					
				} else {
					log_error('We received an unrecognized Facebook webhook call.',$json_data);
				}
			}
		}
	}
	
	
	
	function apiai_webhook(){
		
		//This is being retired in favour of the new design to intake directly from Facebook 
		exit;
		//The main function to receive user message.
		//Facebook Messenger send the data to api.ai, they attempt to detect #intents/@entities.
		//And then they send the results to Us here.
		//Data from api.ai
		//Shervin facebook User ID is 1344093838979504
		
		$json_data = json_decode(file_get_contents('php://input'), true);
		
		//See what we should respond to the user:
		$eng_data = array(
				'gem_id' => 0,
				'us_id' => 0, //Default api.ai API User, IF not with facebok
				'intent_pid' => ( substr_count($json_data['result']['action'],'pid')==1 ? intval(str_replace('pid','',$json_data['result']['action'])) : 0 ),
				'json_blob' => json_encode($json_data), //Dump all incoming variables
				'message' => $json_data['result']['resolvedQuery'],
				'seq' => 0, //No sequence if from api.ai
				'correlation' => ( isset($json_data['result']['score']) ? $json_data['result']['score'] : 1 ),
				'action_pid' => 928, //928 Read, 929 Write, 930 Subscribe, 931 Unsubscribe
				'session_id' => $json_data['sessionId'], //Always from api.ai
		);
		
		//Is this message coming from Facebook? (Instead of api.ai console)
		if(isset($json_data['originalRequest']['source']) 
		&& $json_data['originalRequest']['source']=='facebook'
		&& fb_page_pid($json_data['originalRequest']['data']['recipient']['id'])){
			
			//This is from Facebook Messenger
			$fb_user_id = $json_data['originalRequest']['data']['sender']['id'];
			
			//Update engagement variables:
			$eng_data['seq'] 			= $json_data['originalRequest']['data']['message']['seq']; //Facebook message sequence
			$eng_data['message'] 		= $json_data['originalRequest']['data']['message']['text']; //Facebook message content
			
			
			if(strlen($fb_user_id)>0){
				
				//Indicate to the user that we're typing:
				$this->Messenger_model->send_message(array(
						'recipient' => array(
								'id' => $fb_user_id
						),
						'sender_action' => 'typing_on'
				));
				
				//We have a sender ID, see if this is registered using Facebook PSID
				$matching_users = $this->Us_model->search_node($fb_user_id,1024);
				
				if(count($matching_users)>0){
					
					//Yes, we found them!
					$eng_data['us_id'] = $matching_users[0]['node_id'];
					
					//TODO Check to see if this user is unsubscribed:
					//$unsubscribed_gem = $this->Us_model->fetch_sandwich_node($eng_data['us_id'],845);
					
					
				} else {
					//This is a new user that needs to be registered!
					$eng_data['us_id'] = $this->Us_model->create_user_from_fb($fb_user_id);
					
					if(!$eng_data['us_id']){
						//There was an error fetching the user profile from Facebook:
						$eng_data['us_id'] = 765; //Use FB messenger
						//TODO Log error and look into this
					}
				}
				
				
				//Log incoming engagement:
				$new = $this->Us_model->log_engagement($eng_data);
				
				//Fancy:
				//sleep(1);
				
				if(isset($unsubscribed_gem['id'])){
					//Oho! This user is unsubscribed, Ask them if they would like to re-join us:
					$response = array(
							'text' => 'You had unsubscribed from Us. Would you like to re-join?',
					);
				} else {
					//Now figure out the response:
					$response = $this->Us_model->generate_response($eng_data['intent_pid'],$setting);
				}
				
				//TODO: Log response engagement
				
				//Send message back to user:
				$this->Messenger_model->send_message(array(
						'recipient' => array(
								'id' => $fb_user_id
						),
						'message' => $response,
						'notification_type' => 'REGULAR' //Can be REGULAR, SILENT_PUSH or NO_PUSH
				));
			}
			
		} else {
			//Log engagement:
			$new = $this->Us_model->log_engagement($eng_data);
			
			//most likely this is the api.ai console.
			header('Content-Type: application/json');
			$chosen_reply = 'Testing intents on api.ai, huh? Currently we programmed to only respond in Facebook messanger directly!';
			echo json_encode(array(
					'speech' => $chosen_reply,
					'displayText' => $chosen_reply,
					'data' => array(), //Its only a text response
					'contextOut' => array(),
					'source' => "webhook",
			));
		}
	}
}
