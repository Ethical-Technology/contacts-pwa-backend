<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Setting;
use App\chatHistoryMsg;
use Auth;
use App\surveyMsgLog;
use App\Contact;
use Yajra\Datatables\Datatables;
use DB;
use Pusher\Pusher;

class TwilioController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    protected $TwilioSid,$TwilioToken,$user_id,$settings;
    protected $usa = "Y/m/d", $uk = "d/m/Y", $aus = "m/d/Y";
    
    public function __construct()
    {
        if(isset(Auth::User()->id))
        {
            $user_id = Auth::User()->id;
            
       
            $settings = Setting::where('user_id',$user_id)->first();
            if($settings)
            {
                $this->TwilioSid = $settings->twilio_sid;
                $this->TwilioToken = $settings->twilio_token;
            }
            
        }
    }
    
  

  
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function getNumbers()
    {
        
        $url = "https://$this->TwilioSid:$this->TwilioToken@api.twilio.com/2010-04-01/Accounts/$this->TwilioSid/IncomingPhoneNumbers.json?PageSize=1000";
        $res = $this->Curl($url,'','get');
        return $res;
    }
    public static function set_num_url_ci($val,$num_sid,$TwilioSid,$TwilioToken) //AT TIME OF CLIENT CREATION IN ADMIN SIDE
    {
      
        $obj = new TwilioController();
        
        $url = "https://$TwilioSid:$TwilioToken@api.twilio.com/2010-04-01/Accounts/$TwilioSid/IncomingPhoneNumbers/$num_sid.json";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$val);
         $response = curl_exec($ch);
        curl_close($ch);
        //return $response;
    }


    public function smsControlling(Request $request)
    {

         $Body 			= trim(strtolower($request->Body));
         $SmsSid			= $request->SmsMessageSid;
         $SmsStatus			= $request->SmsStatus;
         $From 			= $request->From;
         $To				= $request->To;

         $settings  = Setting::where("twilio_phone_number",$To)->first();
      
         if($settings)
         {

            $contact  =  Contact::where("number",$From)->where("user_id",$settings->user_id)->first();
            $camp_his = new chatHistoryMsg;
            $camp_his->send_to = $To;
            $camp_his->send_from = $From;
            $camp_his->body = $Body;
            $camp_his->contact_id = $contact->id;    
            $camp_his->user_id = $settings->user_id;     
            $camp_his->direction = 'inbound';
            $camp_his->status = $SmsStatus;
            $camp_his->sms_sid = $SmsSid;
            $camp_his->msg_type = 'chat';
            if($camp_his->save())
            {
                $this->sendNoti($Body,$camp_his->id,$To,$From);
                return $camp_his->id;
            }else
            {
                return 0;
            }

          }

    
        return ""; 
    }

 
    public function sendNoti($msg,$cc,$To,$From)
    {
        $options = array(
        'cluster' => 'mt1',
        'useTLS' => true
      );
      $pusher = new Pusher(
        '89f49a00e7b89d46f858',
        'e53ea015f4c1106b6a40',
        '1026478',
        $options
      );
    
      $data['message'] = $msg;
      $data['history_id'] = $cc;
      $data['To'] = $To;
      $data['From'] = $From;
      $pusher->trigger('incomming-channel', 'incomming-event', $data);
    }

    public function Curl($url,$body,$method)
    {
        
            if(is_array($body))
            $body=http_build_query($body); 
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url );
            //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);
            //curl_setopt($ch, CURLOPT_TIMEOUT, 100);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            //curl_setopt($ch, CURLOPT_PUT, true );
            if($method == "post")
            curl_setopt($ch, CURLOPT_POST, true );
            if($method == "get")
            curl_setopt($ch, CURLOPT_HTTPGET, true ); 
            if($method=="put")
            {
                 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            }     
            if($method=="delete")
            {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            }
            //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        
            //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        
            if($body!="")
        
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        
            //curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        
            $filecontents=curl_exec($ch);
        
            curl_close($ch);
        
            return $filecontents;

    }
    
  

    public function send_sms($To,$from,$body,$user_id="")
    {
       
                $settings = Setting::where('user_id',$user_id)->first();
        
        
        $url = "https://$settings->twilio_sid:$settings->twilio_token@api.twilio.com/2010-04-01/Accounts/$settings->twilio_sid/Messages";
        $val = array(
            "To"=>$To,
            "From"=>$from,
            "Body"=>$body
        );
        
        $ch = curl_init(); // Initialize the curl
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_URL, $url);  // set the opton for curl
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);// set the option to transfer output from script to curl
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $val);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function getNums(Request $request) //AT TIME OF CLIENT CREATION IN ADMIN SIDE
    {
        $used_num = array();
        $did_num = array();
        if($request->sid!="" && $request->token)
        {
            $url = "https://$request->sid:$request->token@api.twilio.com/2010-04-01/Accounts/$request->sid/IncomingPhoneNumbers.json?PageSize=100";
            $res = $this->Curl($url,'','get');
            $number = json_decode($res);
            ?>
            <option value=''>Select Number</option>
            <?php
             if(isset($number->incoming_phone_numbers))
                  {
                        foreach($number->incoming_phone_numbers as $num){
                       
                        if(!in_array($num->phone_number,$used_num))
                        {
                            if(in_array($num->phone_number,$did_num))
                            echo "<option value='$num->sid|$num->phone_number'>$num->phone_number(Dedicated)</option>";
                            else
                             echo "<option value='$num->sid|$num->phone_number'>$num->phone_number</option>";
                        }
                        
                     }
                }   
            return "";
        }
    }
    
  
    
}
