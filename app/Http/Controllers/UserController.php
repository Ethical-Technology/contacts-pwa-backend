<?php

    namespace App\Http\Controllers;

    use App\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Validator;
    use JWTAuth;
    use Tymon\JWTAuth\Exceptions\JWTException;
    use Illuminate\Support\Facades\Storage;
    
    class UserController extends Controller
    {
        public function authenticate(Request $request)
        {
            $credentials = $request->only('email', 'password');

            try {
                if (! $token = JWTAuth::attempt($credentials)) {
                    return response()->json(['error' => 'Invalid Credentials'], 400);
                }
            } catch (JWTException $e) {
                return response()->json(['error' => 'could_not_create_token'], 500);
            }
            $user = auth()->user();
            $url = env("APP_URL");
            if($user->profile_image!='')
            {
                $user->profile_image = $url.'profile_images/'.$user->profile_image; 
            }
          

            return response()->json(compact('token','user'));
        }

            public function saveProfile(Request $request){

                $user_id = auth()->user()->id;
             
              
                $user = User::find($user_id);
                $user->name = $request->name;
                $user->email = $request->email;
                $user->address = $request->address;
                $user->about_me = $request->about_me;
                $user->plain_password =  $request->password;

                if($request->password!="")
                {
                    $user->password =  bcrypt($request->password);
                }
                if($user->save())
                {
                    $user = User::find($user_id);
                    $url = env("APP_URL");
                    if($user->profile_image!='')
                    {
                         $user->profile_image = $url.'profile_images/'.$user->profile_image; 
                    }
                    return response()->json(['success' =>true ,"msg"=>"saved successfully.", "user"=>$user], 200);
                }else {
                    return response()->json(['success' =>false ,"msg"=>"Error while saving profile."], 400);
                }
            }

            public function updateProfileImage(Request $request){

                $user_id = auth()->user()->id;
                $user = User::find($user_id);
             
               // return response()->json(['success' =>true ,"msg"=>"saved successfully.", "user"=>$user,'file'=>$_FILES['image']], 200);
                  $image = $request->image; 
                
                  $imageName = time().'.'. $image->getClientOriginalExtension();
                  
                  if($image->move("profile_images", $imageName))
                  {
            
                      $user_id = auth()->user()->id;
                      $save = User::where("id",$user_id)->update([
                          "profile_image"=> $imageName
                      ]);

                      if($save)
                      {
                          $user = User::find($user_id);
                          $url = env("APP_URL");
                          if($user->profile_image!='')
                          {
                              $user->profile_image = $url.'profile_images/'.$user->profile_image; 
                          }
                          return response()->json(['success' =>true ,"msg"=>"saved successfully.", "user"=>$user], 200);
                      }else {
                          return response()->json(['success' =>false ,"msg"=>"Error while saving profile."], 400);
                      }

                  }else{

                    return response()->json(['success' =>false ,"msg"=>"Error while saving profile."], 400);
                 
                }
                   
            }

        public function register(Request $request)
        {
                $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
            ]);

            if($validator->fails()){
                    return response()->json($validator->errors()->toJson(), 400);
            }

            $user = User::create([
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')),
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json(compact('user','token'),201);
        }

        public function logout()
        {
                // Pass true to force the token to be blacklisted "forever"
               // auth()->logout(true);
                JWTAuth::invalidate(JWTAuth::getToken());
                return response()->json(['success' => 'logout'], 200);
        } 

        public function getAuthenticatedUser()
            {
                    try {

                            if (! $user = JWTAuth::parseToken()->authenticate()) {
                                    return response()->json(['user_not_found'], 404);
                            }

                    } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

                            return response()->json(['token_expired'], $e->getStatusCode());

                    } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

                            return response()->json(['token_invalid'], $e->getStatusCode());

                    } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

                            return response()->json(['token_absent'], $e->getStatusCode());

                    }

                    return response()->json(compact('user'));
            }
    }