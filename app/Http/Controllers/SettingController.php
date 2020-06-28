<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Setting;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Validator;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        
    } 
     
    public function index()
    {
		$timezones  = TimeZoneController::getTimeZones();
        $twilio = new TwilioController();
        $number = $twilio->getNumbers();
      
        $obj    = Setting::where('user_id','=',auth()->user()->id)->first();
        

        $number = json_decode($number);
        $available_numbers = array();
         if(isset($number->incoming_phone_numbers)){
            foreach($number->incoming_phone_numbers as $num){
                
              
                    $available_numbers[] = $num->phone_number.'|'.$num->sid;
                
            
            }
        }

          return response()->json(['success' =>true ,"msg"=>"saved successfully.","settings"=> ['timezones'=>json_decode($timezones,true),"available_numbers"=>$available_numbers,"data"=>$obj]], 200);
          
    }
 

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		return redirect('/settings');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
     
    public function NumberFormat($number){
       $number = preg_replace('/\D+/', '', $number);

       if (preg_match('/^\d{10}$/', $number)) {
           
           $number =  '+1'.$number;
           // pass
           
       } else{
           
           $number =  '+'.$number;
           //fail
       }
       return $number;
   } 
     
    public function store(Request $request)
    {
     
        $obj = new Setting();
	    if(isset($request->id) && $request->id!='')
        {
            //updating
            $obj = $obj->findOrFail($request->id);
            
        }else{
            //creating new
            $obj->user_id = auth()->user()->id;
        }

        $obj = $obj->fill($request->all());
      
       if($request->twilio_number!="")
       {
               $num = explode('|',$request->twilio_number);
               $obj->twilio_number = $request->twilio_number;//$num[0];
               $obj->twilio_phone_number = $num[0] ;//$num[0];
       }else
       {
           $obj->twilio_number = '';
           $obj->twilio_phone_number = '';//$num[0];
       }

        if($obj->save())
        {       

         
            $cc = array("SmsUrl"=>url('/api/sms-controlling'));
            if($request->twilio_number!="")
            {
                TwilioController::set_num_url_ci($cc,$num[1],$request->twilio_sid,$request->twilio_token);
            }

            $timezones  = TimeZoneController::getTimeZones();
            $twilio = new TwilioController();
            $number = $twilio->getNumbers();
          
            $obj    = Setting::where('user_id','=',auth()->user()->id)->first();
            
    
            $number = json_decode($number);
            $available_numbers = array();
             if(isset($number->incoming_phone_numbers)){
                foreach($number->incoming_phone_numbers as $num){
                    
                  
                        $available_numbers[] = $num->phone_number.'|'.$num->sid;
                    
                
                }
            }
    
            return response()->json(['success' =>true ,"msg"=>"saved successfully.","settings"=> ['timezones'=>json_decode($timezones,true),"available_numbers"=>$available_numbers,"data"=>$obj]], 200);
              
            // return response()->json(['success' =>true ,"msg"=>"saved successfully.", "settings"=>$settings], 200);
          

        }else{

            return response()->json(['success' =>false ,"msg"=>"error while saving settings."], 400);
           
        } 
     
    }
    
    public function update_voice_url(Request $request)
    {
        $twilio = new TwilioController();
        if($request->sid!="" && $request->token)
        {
            $url = "https://$request->sid:$request->token@api.twilio.com/2010-04-01/Accounts/$request->sid/IncomingPhoneNumbers.json?PageSize=100";
            $res = $twilio->Curl($url,'','get');
            $number = json_decode($res);
            $cc = array("VoiceUrl"=>url('/voice/controlling'));
             if(isset($number->incoming_phone_numbers))
             {
                foreach($number->incoming_phone_numbers as $num)
                {
                    $set_url = $twilio->set_num_url_ci($cc,$num->sid,$request->sid,$request->token);
                }
             } 
             echo "1";
          
        }
    }
    
    public function voiceCon(Request $request)
    {
        header('content-type: text/xml');
        
        $setting = Setting::where('id','1')->value('forward_num');
        
        ?>
          
        <Response>
           <Dial CallerId='<?php $_REQUEST['From']?>'><?php echo $setting;?></Dial>
        </Response>
          
        <?php
        
        return '';
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
