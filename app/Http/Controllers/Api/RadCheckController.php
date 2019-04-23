<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\DB\Radcheck;
use App\DB\RadAcct;
use App\DB\Userinfo;
use App\Http\Controllers\Controller;

class RadCheckController extends Controller
{
    public function index( Request $request, Response $response, Radcheck $radcheck )
	{
		$params = isset( $request->params ) ? $request->params : 'id';
		$orderby = isset( $request->orderby ) ? $request->orderby : 'asc';
		$rows = isset( $request->limit ) ? $request->limit : 10;
		$keywords = isset( $request->keywords ) ? $request->keywords : ''; 
		$query = new $radcheck;
		
		if( $keywords === '' )
		{
			if( $params === '' )
			{
				$query = $query->orderBy($params, $orderby)->paginate( $rows );
			}
			else
			{
				$query = $query->orderBy($params, $orderby)
				->paginate( $rows );
			}
		}
		else
		{
			if( $params === '' )
			{
				$query = $query->where('username', 'like', '%' . $keywords .'%')
				->orderBy($params, $orderby)->paginate( $rows );
			}
			else
			{
				$query = $query->where('username', 'like', '%' . $keywords .'%')
				->orderBy($params, $orderby)
				->paginate( $rows );
			}
		}
		
		$data = [
			'results' => $query
		];
		
		return response()->json( $data, 200 )
		->header('Content-Type', 'application/json')
		->header('Accepts', 'application/json')
		->header('Access-Control-Allow-Origin', '*')
        ->header("Access-Control-Allow-Headers", "Access-Control-*, Origin, X-Requested-With, Content-Type, Accept");
	}
	
	public function createUser( Request $request, Radcheck $radcheck, Userinfo $userinfo )
	{
		$username = $request->username;
		$password = $request->password;
		$attribute = $request->attribute;
		$op = $request->op;
		$displayname = $request->displayname;
		
		if( ! empty( $username ) OR $username != null OR $username != '' )
		{
			$create = new $radcheck;
			$create->username = $username;
			$create->value = $password;
			$create->attribute = $attribute;
			$create->op = $op;
			$create->save();
			
			$user = new $userinfo;
			$userinfo->username = $username;
			$userinfo->firstname = $displayname;
			$userinfo->creationdate = date('Y-m-d H:i:s');
			$userinfo->updatedate = date('Y-m-d H:i:s');
			$userinfo->creationby = 'system';
			$userinfo->updateby = 'system';
			$userinfo->save();
			
			$data = [
				'status' => 200,
				'statusText' => 'success'
			];
		}
		else
		{
			$data = [
				'status' => 204,
				'statusText' => ''
			];
		}
	
		
		return response()->json( $data, $data['status'] )
		->header('Content-Type', 'application/json')
		->header('Accepts', 'application/json')
		->header('Access-Control-Allow-Origin', '*')
        ->header("Access-Control-Allow-Headers", "Access-Control-*, Origin, X-Requested-With, Content-Type, Accept");
	}
	
	public function updateUser( Request $request, Radcheck $radcheck, $id )
	{
		$username = $request->username;
		$password = $request->password;
		$attribute = $request->attribute;
		$op = $request->op;
		
		$update = $radcheck->where('id','=', $id)->first();
		$update->username = $username;
		$update->value = $password;
		$update->attribute = $attribute;
		$update->op = $op;
		$update->save();
		
		$data = [
			'status' => 200,
			'statusText' => 'success'
		];
		
		return response()->json( $data, $data['status'] )
		->header('Content-Type', 'application/json')
		->header('Accepts', 'application/json')
		->header('Access-Control-Allow-Origin', '*');
	}
	
	public function destroy( Request $request, Radcheck $radcheck, Userinfo $userinfo, RadAcct $radacct, $username )
	{
		$username_dash = str_replace( ':', '-', $username );
		$username_colon = $username;
		
		$check = $radcheck->where('username','=',$username_colon);		
		$check->delete();
		
		$userinfo = $userinfo->where('username', '=', $username_colon);
		$userinfo->delete();
		
		/*$accounting = $radacct->where('callingstationid', '=', $username_colon)
		->orWhere('callingstationid', '=', $username_dash);
		$accounting->delete();*/
		
		
		$res = [
			'status' => 200,
			'statusText' => $username . ' deleted.'
		];
		
		return response()->json( $res, $res['status'] )
		->header('Content-Type', 'application/json')
		->header('Accepts', 'application/json')
		->header('Access-Control-Allow-Origin', '*');
	}
}
