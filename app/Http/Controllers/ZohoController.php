<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


use Illuminate\Support\Facades\Log;
use function Sodium\add;

class ZohoController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    const CLIENT_ID = '1000.PKJP3ABOXKOJZ90ZSQ9HWDBCC004MA';
    const CLIENT_SECRET = '8729d8e8752107f55a076b135e2455e760d0a66aa6';
    const REDIRECT_URI = 'http://homestead.test/callback';
    const AR_URL_ENDPOINT = 'https://accounts.zoho.com/oauth/v2/auth';
    const ATR_URL_ENDPOINT = 'https://accounts.zoho.com/oauth/v2/token';


    const API_ENDPOINT = 'https://sandbox.zohoapis';

    const ATR_REQUEST_LOCATION = [
        "eu" =>"https://accounts.zoho.eu",
        "au" =>"https://accounts.zoho.com.au",
        "in" =>"https://accounts.zoho.in",
        "us" =>"https://accounts.zoho.com"
    ];

    public function index(Request $request)
    {

        return view('main');
    }


    public function authorization_request()
    {


        $query = http_build_query([
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'AaaServer.profile.READ,ZohoCRM.settings.ALL,ZohoCRM.modules.ALL',

        ]);

        Log::info(__METHOD__ . ' '. self::AR_URL_ENDPOINT.'?'.$query);


        return redirect(self::AR_URL_ENDPOINT.'?'.$query);
    }

    public function callback(Request $request)
    {
       if($request->get('code')) {

           Log::info(__METHOD__ . ' ' . $request->get('code'). ' ' . $request->get('location'));

           try {
               $result = $this->access_token_request($request->get('code'), $request->get('location'));
               $result['expired'] = (new \DateTimeImmutable())->add(new \DateInterval('PT'.$result['expires_in'].'S'));


               session()->put('token', $result);

               session()->flash('status_success', 'Token get success ' . $result['access_token']);

               return redirect('work');

           } catch (\Exception  $e) {
               session()->flash('status_error', $e->getMessage());
               return redirect('/');
           }


       }
    }


    public function access_token_request($code, $location)
    {
        $url = self::ATR_REQUEST_LOCATION[trim($location)] . '/oauth/v2/token';


        $response = Http::asForm()->
            //Http::withHeaders([
            //'Content-type' => 'application/x-www-form-urlencoded',
            //'User-agent' => 'curl/7.55.1',
            //'Content-Length' => 0,
            //])
          post($url, [
        //post('http://192.168.10.1:8888', [
            'client_id' => self::CLIENT_ID,
            'grant_type' => 'authorization_code',
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri' => self::REDIRECT_URI,
            'code' => $code

        ]);

        Log::info(__METHOD__ . ' ' . $url . implode(' ', $response->json()));

        if (!$response->successful()) {
          throw new \RuntimeException('Error from zoho server while getting token ' .$response->serverError());
        };


        return $response->json();

    }




    public function isTokenExpired(\DateTimeImmutable $token_expired_dt)
    {
        $now = new \DateTimeImmutable();

        if ($now >= $token_expired_dt) {
            Log::info(__METHOD__ . ' Token expired at ' .$token_expired_dt->format('Y-m-d H:i:s'). 'Try recreate token ');
            return true;
        }
    }


    //================================================================================================================

    public function work(Request $request)
    {
        $result = "";

        $token = session()->get('token');
        //$token = ['expired' => (new \DateTimeImmutable('16:00:00'))->add(new \DateInterval('PT3600S'))];



        if (!$token) {
            Log::info(__METHOD__ . ' Token is NULL Try recreate token' );
            session()->flash('status_error', 'Token is null. Must Recreate ' );
            return redirect('/');
            //return redirect('init');
        };



        if ($this->isTokenExpired($token['expired'])) {
            session()->now('status_error', 'Token expired. at '. ($token['expired'])->format('Y-m-d H:i:s') .'Must Recreate ' );
            //return redirect('/');
           //return redirect('init');
        };




        if ($request->get('method') == 'getModules') {
            $result = $this->get_modules();
        };
        if ($request->get('method') == 'getFieldsMetadata') {
            $result = $this->get_fields_metadatafor($request->get('module'));
        };
        if ($request->get('method') == 'postValue') {
            $result = $this->post_value($request->get('module'));
        };
        if ($request->get('method') == 'getAllRecords') {
            $result = $this->get_records($request->get('module'));
        };
        if ($request->get('method') == 'createDealWithTask') {
            $result = $this->createDealWithTask($request->get('module'));
        };




        $obj = json_decode($result);
        if (isset($obj->code)) {
          if ($obj->code == 'INVALID_TOKEN') {
              Log::info(__METHOD__ . ' Token is invalid ' .$result .'Try recreate token ');
              return redirect('init');
          }
        };


        return view('work', compact('result'));
    }



    public function   createDealWithTask($module = 'Deals')
    {
        $token = session()->get('token');

        $url = $token['api_domain'] . "/crm/v2/$module";

        //$url = "192.168.10.1:8888";


        $headers = [
            "Authorization: Zoho-oauthtoken {$token['access_token']}"
        ];



        //https://www.zoho.com/crm/developer/docs/api/v2/insert-records.html
        // Описание типов полей

        $d1 = new \StdClass();
        $d1->Deal_Name = "TestDial";
        $d1->Stage = "NeedsAnalysis";

        //
        $d1->Closing_Date = (new \DateTimeImmutable())->format('Y-m-d');


        $d2 = new \StdClass();
        $d2->Deal_Name = "TestDial";
        $d2->Stage = "Needs Analysis";
        $d2->Closing_Date = new \DateTimeImmutable();


        $data = new \StdClass();
        $data->data = [
           $d1,
           //$d2
        ];
        //dump(json_encode($data));
        //exit;

        /*
        $data = [
            [
                "Deal_Name" => "TestDial",
                "Stage" => 'Needs Analysis',
                "Closing_Date" => new \DateTimeImmutable(),
                //"Account_Name" =>
            ],
        ];
        */

        /*
        $task = new \StdClass();
        $task->Subject = "Subj1";

        $data = new \StdClass();
        $data->data = [
            $task
        ];
*/
        $data = json_encode($data);


        dump($data);
        exit;

        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

        $res=curl_exec( $ch );
        $error_ = curl_error($ch);
        $answer_http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        //dump($res);
        return $res;

    }


    public function get_records($module)
    {
        $token = session()->get('token');

        $url = $token['api_domain'] . "/crm/v2/$module";

        //$url = "192.168.10.1:8888";


        $headers = [
            "Authorization: Zoho-oauthtoken {$token['access_token']}"
        ];


        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, false );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        $res=curl_exec( $ch );
        $error_ = curl_error($ch);
        $answer_http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        return $res;
        //dump($res);

    }

    public function post_value($module)
    {
        $token = session()->get('token');

        $url = $token['api_domain'] . "/crm/v2/$module";

         //$url = "192.168.10.1:8888";


        $headers = [
            "Authorization: Zoho-oauthtoken {$token['access_token']}"
        ];


        $data = [
          [
            "Company" => "Zylker",
            "Last_Name" => "Daly",
            "First_Name" =>  "Paul",
            "Email" => "p.daly@zylker.com",
            "State" => "Texas"
          ],
            [
                "Company" => "Zylker1",
                "Last_Name" => "Daly",
                "First_Name" =>  "Paul",
                "Email" => "p.daly@zylker.com",
                "State" => "Texas"
            ],
        ];

        $data = json_encode($data);

        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

        $res=curl_exec( $ch );
        $error_ = curl_error($ch);
        $answer_http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        //dump($res);
        return $res;

    }

    public function get_fields_metadatafor($module)
    {
        $token = session()->get('token');

        $url = $token['api_domain'] . "/crm/v2/settings/fields?module=$module";

        // $url = "192.168.10.1:8888";


        $headers = [
            "Authorization: Zoho-oauthtoken {$token['access_token']}"
        ];


        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, false );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        $res=curl_exec( $ch );
        $error_ = curl_error($ch);
        $answer_http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        //dump($res);
        return $res;
    }


    public function get_modules()
    {
        $token = session()->get('token');

        $url = $token['api_domain'] . '/crm/v2/settings/modules';

       // $url = "192.168.10.1:8888";


        $headers = [
            "Authorization: Zoho-oauthtoken {$token['access_token']}"
        ];


        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, false );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        $res=curl_exec( $ch );
        $error_ = curl_error($ch);
        $answer_http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        //dump($res);
        return $res;
    }
}
