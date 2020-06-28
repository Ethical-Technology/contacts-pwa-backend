<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Setting;
use App\Contact;
use App\userMeta;
use App\chatHistoryMsg;
use JWTAuth;
use Yajra\Datatables\Datatables;
use DB;


class ContactController extends Controller
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
    
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		
		    //
    }
    
    public function send_sms2($To,$from,$body,$user_id,$contact_id,$direction,$msg_type)
    {              
        if($body!="")
        {
            if($user_id!="")
            {
                    $settings = Setting::where('user_id',$user_id)->first();
                    $TwilioSid = $settings->twilio_sid;
                    $TwilioToken = $settings->twilio_token;
                    //$from = $settings->twilio_number;
                
            }
         
       
             $url = "https://$TwilioSid:$TwilioToken@api.twilio.com/2010-04-01/Accounts/$TwilioSid/Messages";
             $val = array(
                "To"=>$To,
                "From"=>$from,
                "Body"=>$body,
               // "StatusCallback"=>url("/sms-status")
            );
    	
            $ch = curl_init(); // Initialize the curl
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_URL, $url);  // set the opton for curl
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);// set the option to transfer output from script to curl
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $val);
            $response = curl_exec($ch);
            curl_close($ch);
            $check = (array)simplexml_load_string($response);
            
            //print_r($check);
            if(!isset($check['RestException']))
            {
                $this->save_chat_history($To,$from,$body,$user_id,$contact_id,$direction,$check['Message']->Status,$check['Message']->Sid,"chat");
               //$this->campaign_history($To,$from,$body,$user_id,$camp_id,$pros_id,$direction,"delivered","sadsfdsdfSDF");
                return 'true';
            }else
            {
    			return $check['RestException']->Message;
                $this->save_chat_history($To,$from,$body,$user_id,$contact_id,$direction,"failed","","chat");
               // return 'false';
            }
        }else{
            return "Message Body is empty";
        }
        //return $response;
    }

    public function save_chat_history($To,$from,$body,$user_id,$contact_id,$direction,$status,$sms_sid,$msg_type)
    {
        $camp_his = new chatHistoryMsg;
        $camp_his->send_to = $To;
        $camp_his->send_from = $from;
        $camp_his->body = $body;
        $camp_his->contact_id = $contact_id;    
        $camp_his->user_id = $user_id;     
        $camp_his->direction = $direction;
        $camp_his->status = $status;
        $camp_his->sms_sid = $sms_sid;
        $camp_his->msg_type = $msg_type;
        if($camp_his->save())
        {
            return $camp_his->id;
        }else
        {
            return 0;
        }
        
    }



    public function ChatSend(Request $request)
    {
         try{
         
            $contact  =  Contact::where("id",$request->contact_id)->first();

            $smsbody =  $request->send_sms_text;
            
            // $smsbody = str_replace("%first_name%",$contact->first_name,$smsbody);
            // $smsbody = str_replace("%last_name%",$contact->last_name,$smsbody);
            // $smsbody = str_replace("%email%",$contact->email,$smsbody);
            // $smsbody = str_replace("%phone%",$contact->number,$smsbody);
            // $smsbody = str_replace("%physician%",$contact->primary_phy,$smsbody);
            $settings = Setting::where('user_id',auth()->user()->id)->first();
            $send_from = $settings->twilio_number;
            $send_from_no = explode('|',$send_from);             
           // $ch = $this->send_sms2($request->send_to,$request->send_from,$smsbody,$request->user_id,$request->contact_id,"outbound","chat"); 
            $ch = $this->send_sms2($request->send_to,$send_from_no[0],$smsbody,auth()->user()->id,$request->contact_id,"outbound","chat"); 
          if($settings)
          {

            if($send_from!='')
            {
                if($ch=='true')
                {
      
                  return response()->json(['success' =>true ,"msg"=>"message successfully sent."], 200);
      
                }else
                {
                  return response()->json(['success' =>false ,"msg"=>"Opps, ".$ch], 400);
                }
      
            }else{
                return response()->json(['success' =>false ,"msg"=>"Opps, from number not found in the settings"], 400);
            }

          }else{

                 return response()->json(['success' =>false ,"msg"=>"Opps, from number not found in the settings"], 400);
          }
          
      
        //   if($obj->save())
        //   {
        //       return response()->json(['success' =>true ,"msg"=>"Saved successfully."], 200);

        //   }else
        //   {
        //       return response()->json(['success' =>false ,"msg"=>"error while saving contact."], 400);
        //   }
          
        }catch(\Exception $e)
        {
            // echo "Error While Sending!";
            // echo $e->getMessage();
            return response()->json(['success' =>false ,"msg"=>"Opps, ".$e->getMessage()], 400);
        }
        
        return "";
    }


 

    public function historyUpdate(Request $request)
    {
        // $camp_history = CampaignHistory::find($request->id);
        // if($camp_history)
        // {
        //     $camp_history->is_read = "1";
        //     $camp_history->save();
        // }
        // return "";
    }

    public function contactsList()
	{
		$data = Contact::select(['id','first_name','last_name','email','number','status','created_at'])->where('user_id',auth()->user()->id);
         
		return Datatables::of($data)
		->addColumn('action',function($d){
	  
		//  $cc = '<button onclick="edit_contact()" class="mx-1 rounded-sm shadow-none hover-scale-sm d-40 border-0 p-0 d-inline-flex align-items-center justify-content-center btn btn-neutral-first"><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="edit" class="svg-inline--fa fa-edit fa-w-18 font-size-sm" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M402.3 344.9l32-32c5-5 13.7-1.5 13.7 5.7V464c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h273.5c7.1 0 10.7 8.6 5.7 13.7l-32 32c-1.5 1.5-3.5 2.3-5.7 2.3H48v352h352V350.5c0-2.1.8-4.1 2.3-5.6zm156.6-201.8L296.3 405.7l-90.4 10c-26.2 2.9-48.5-19.2-45.6-45.6l10-90.4L432.9 17.1c22.9-22.9 59.9-22.9 82.7 0l43.2 43.2c22.9 22.9 22.9 60 .1 82.8zM460.1 174L402 115.9 216.2 301.8l-7.3 65.3 65.3-7.3L460.1 174zm64.8-79.7l-43.2-43.2c-4.1-4.1-10.8-4.1-14.8 0L436 82l58.1 58.1 30.9-30.9c4-4.2 4-10.8-.1-14.9z"></path></svg></button>';
        //  $cc .= '<button onclick="delete_contact()" class="mx-1 rounded-sm shadow-none hover-scale-sm d-40 border-0 p-0 d-inline-flex align-items-center justify-content-center btn btn-neutral-danger"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times" class="svg-inline--fa fa-times fa-w-11 font-size-sm" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg></button>';   
           
            return $d->id;
		})
        
        ->editColumn('created_at',function($d){
          return $d->created_at;
        })
        ->editColumn('status',function($d){

                if($d->status=='0')
                {
                    return '<span class="text-dark badge badge-neutral-dark">Inactive</span>';

                }else if($d->status=='1'){

                    return '<span class="text-success badge badge-neutral-success">Active</span>';
                }
        })
        ->rawColumns(['status','action'])
        ->make(true);	
		
    }
    

    public function syncContacts()
	{
          
            $refresh_token = userMeta::where('user_id',auth()->user()->id)->where('meta_key','refresh_token')->first();
            $api_domain = userMeta::where('user_id',auth()->user()->id)->where('meta_key','api_domain')->first();
            $accounts_url = userMeta::where('user_id',auth()->user()->id)->where('meta_key','accounts_url')->first();
        
            // $refresh_token = userMeta::where('user_id',$user_id)->where('meta_key','refresh_token')->first();
            // $api_domain = userMeta::where('user_id',$user_id)->where('meta_key','api_domain')->first();
            // $accounts_url = userMeta::where('user_id',$user_id)->where('meta_key','accounts_url')->first();
        
        
            //  return response()->json(['success' =>false ,"msg"=>"invalid access_token.","refresh_token"=>$refresh_token], 400);
            if($refresh_token)
            {
           
                $zoho = new ZohoController();
                $url = $accounts_url->meta_value."/oauth/v2/token";
                $body = array(
                  'refresh_token'=>$refresh_token->meta_value,
                  'grant_type'=>'refresh_token',
                  'client_id'=>'1000.ENG991JA8H78X2IVWE7RKNHEM4VYHH',
                  'client_secret'=>'f0d66c587edb5466a15087c1d5eaa6aad1603018d3',
                );  
                $headers="";
                $res = $zoho->Curl($url,$body,'post',$headers);
                $tokens = json_decode($res,true);
                if(!isset($tokens['error']))
                {
                 //   echo $tokens['access_token'];
                    $page = 1;
                    while($page>0)
                    {
               
                        $url = $api_domain->meta_value."/crm/v2/Contacts?page=".$page."";
                        $body = "";
                        $headers = array(
                            "Authorization: Zoho-oauthtoken $tokens[access_token]"
                        );
                        $result = $zoho->Curl($url,$body,'get',$headers);
                 
                        $contacts = json_decode($result,true);
                       // print_r($contacts);
                        if(isset($contacts['data'])!='' && count($contacts['data'])>0)
                        {

                            foreach($contacts['data'] as $contact)
                            {
                                $obj = new Contact();
                                $number = Contact::where('number', '=', $contact['Phone'])->where('user_id', '=', auth()->user()->id)->first();
                                if($number==null)
                                {
                                    $obj->user_id = auth()->user()->id; //auth()->user()->id;
                                    $obj->number  = $contact['Phone'];
                                    $obj->email  =  $contact['Email'];
                                    $obj->first_name  =  $contact['First_Name'];
                                    $obj->last_name  =  $contact['Last_Name'];
                                    $obj->save();
                                   
                                }
                                
                            }

                            if($contacts['info']['more_records']=='')
                            {
                                $page = 0;
    
                            }else{
                                $page++;
                            }

                        }else{
                            $page = 0;
                        }
                      
                    
                    }    
                 
                         return response()->json(['success' =>true ,"msg"=>"Contacts successfully synced","contacts"=>'contacts'], 200);
                   
                    
                }else{

                    return response()->json(['success' =>false ,"msg"=>"invalid access_token."], 400);
              
                }

            }else{

                return response()->json(['success' =>false ,"msg"=>"You need to connect a ZOHO accout."], 400);
              
            }

            
  
	}



    public function fetch_chat(Request $request)
    {      
       
             $contact = Contact::where("id",$request->id)
            ->first();

            $user_id =  auth()->user()->id;


            $contacts = Contact::whereRaw("id in (select contact_id from chat_history_msgs) and user_id=$user_id")->get();
            $contactsData =[];
            if($contacts)
            {
                $contactsData = $contacts;
            }

            $contactData = json_encode([]);
            if($contact)
            {
                $contactData = $contact;
            }

             $chats = chatHistoryMsg::where('contact_id',$request->id)->get();

            //  return response()->json(['success' =>true,"chat"=>$chats,"user"=>$contact,"contactsList"=>[]], 200);
             return response()->json(['success' =>true ,"msg"=>"fetched successfully.","chat"=>$chats,"user"=>$contactData,"contactsList"=>$contactsData], 200);

     }
  

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
           $obj = new Contact();

    
            $number = Contact::where('number', '=', $request->number)->where('user_id', '=', auth()->user()->id)->first();
          
            if($request->id!='')
            {
                $obj = $obj->findOrFail($request->id);

                if($number)
                {
                    if($request->id != $number->id)
                    {
                        return response()->json(['success' =>false ,"msg"=>"Phone Number already exists."], 400);
                
                    }
                }
              

                    $obj->fill($request->all());
                    $obj->user_id      = auth()->user()->id;
                    $obj->number      = $request->number;

                    if($obj->save())
                    {
                        

                        return response()->json(['success' =>true ,"msg"=>"saved successfully."], 200);
              
                    }else
                    {
                        return response()->json(['success' =>false ,"msg"=>"error while saving contact."], 400);
              
                    }
                



            }else{

                if ($number=== null) {
                        // user doesn't exist
                        $obj->fill($request->all());
                        $obj->user_id      = auth()->user()->id;
                        $obj->number      = $request->number;

                        if($obj->save())
                        {
                            return response()->json(['success' =>true ,"msg"=>"Saved successfully."], 200);

                        }else
                        {
                            return response()->json(['success' =>false ,"msg"=>"error while saving contact."], 400);
                        }
                  
                }else{
                          return response()->json(['success' =>false ,"msg"=>"phone number already exists."], 400);
                }
                
            }

       
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
    public function edit(Request $request)
    {
        $data = Contact::where("id", $request->id)->first()->toArray();
        //print_r($data);
        //die();


        if($data)
        {
            return response()->json(['success' =>true ,"msg"=>"fetched successfully.","contactData"=>$data], 200);

        }else
        {
            return response()->json(['success' =>false ,"msg"=>"error while fetching contact."], 400);
        }

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
    
    public function delPros(Request $request)
	{
        $res = Contact::destroy($request->id);

        if($res)
        {
            return response()->json(['success' =>true ,"msg"=>"contact has been deleted successfully."], 200);

        }else
        {
            return response()->json(['success' =>false ,"msg"=>"error while deleting contact."], 400);
        }
	
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
