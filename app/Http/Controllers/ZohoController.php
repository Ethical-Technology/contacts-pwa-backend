<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Setting;
use App\User;
use App\userMeta;
use App\chatHistoryMsg;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Yajra\Datatables\Datatables;
use DB;
use Illuminate\Support\Facades\Hash;

class ZohoController extends Controller
{
    public function zohoAuth(Request $request)
    {
    
        if(isset($_REQUEST['error']) && $_REQUEST['error']=='access_denied')
        {

            return redirect("http://localhost:3000/pwa/AccessDenied");

        }else if(isset($_REQUEST['code']) && $_REQUEST['code']!=''){

             // print_r($request->All());

              $url = $_REQUEST['accounts-server']."/oauth/v2/token";
              $body = array(
                'grant_type'=>'authorization_code',
                'client_id'=>'1000.ENG991JA8H78X2IVWE7RKNHEM4VYHH',
                'client_secret'=>'f0d66c587edb5466a15087c1d5eaa6aad1603018d3',
                'redirect_uri'=>'https://sav.vin/pwa-backend/public/api/zoho-auth',
                'code'=>$_REQUEST['code']
              );  
              $headers="";

              $res = $this->Curl($url,$body,'post',$headers);
              $tokens = json_decode($res,true);
              if(!isset($tokens['error']))
              {

                // print_r($tokens);
                $url = $tokens['api_domain']."/crm/v2/org";
                $body = "";
                $headers = array(
                    "Authorization: Zoho-oauthtoken $tokens[access_token]"
                );
                
                $res = $this->Curl($url,$body,'get',$headers);
                if($res!='')
                {
                            $orgData = json_decode($res,true);
                            // echo "<pre>";
                            // print_r($orgData);
                            $password = $this->generateRandomString();

                          
                          //  $email = User::where('email', '=', $orgData['org'][0]['company_name'])->first();
                            $zoho_org = User::where('zoho_org_id', '=', $orgData['org'][0]['id'])->first();
                            
                            if($zoho_org)
                            {
                                //echo '{"error":"organization_already_exists"}';
                         
                               // $token = JWTAuth::fromUser($zoho_org);

                                $credentials = $zoho_org['email'].'|'.$zoho_org['plain_password'];
                                $token = base64_encode($credentials);
                                return redirect("http://localhost:3000/pwa/Login/$token");

                               die();     
                            }else{

                                $user = new User();
                                $user->name = $orgData['org'][0]['company_name'];
                                $user->zoho_org_id = $orgData['org'][0]['id'];
                                $user->email = $orgData['org'][0]['primary_email'];
                                $user->password = Hash::make($password);
                                $user->plain_password = $password;
                                $user->save();
                         
                            }

                            if($user)
                            {
                                    $userMeta1 = new userMeta();
                                    $userMeta1->user_id = $user->id;
                                    $userMeta1->meta_key = 'access_token';
                                    $userMeta1->meta_value = $tokens['access_token'];
                                    $userMeta1->save();


                                    $userMeta2 = new userMeta();
                                    $userMeta2->user_id = $user->id;
                                    $userMeta2->meta_key = 'refresh_token';
                                    $userMeta2->meta_value = $tokens['refresh_token'];
                                    $userMeta2->save();


                                    $userMeta3 = new userMeta();
                                    $userMeta3->user_id = $user->id;
                                    $userMeta3->meta_key = 'api_domain';
                                    $userMeta3->meta_value = $tokens['api_domain'];
                                    $userMeta3->save();

                                    $userMeta4 = new userMeta();
                                    $userMeta4->user_id = $user->id;
                                    $userMeta4->meta_key = 'accounts_url';
                                    $userMeta4->meta_value = $_REQUEST['accounts-server'];
                                    $userMeta4->save();

                            //    $newUser = User::where('id', '=', $user->id)->first();
                            
                                //$token = JWTAuth::fromUser($newUser);
                                $credentials = $user['email'].'|'.$user['plain_password'];
                                $token = base64_encode($credentials);
                                return redirect("http://localhost:3000/pwa/Login/$token");
                                //echo $token;
                             }

                        
                }else{

                    echo '{"error":"organization_not_found"}';

                }

              }else{

                   echo $res;

              }
             
        }

        die();

    }

    public function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

public function Curl($url,$body,$method,$headers)
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
    
        if($headers!='')
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $filecontents=curl_exec($ch);
    
        curl_close($ch);
    
        return $filecontents;



}
}