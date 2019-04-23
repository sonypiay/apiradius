<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\DB\Nas;
use App\Http\Controllers\Controller;

class NasController extends Controller
{
    public function index( Request $request, Response $response, Nas $nas )
	{
		$params = isset( $request->params ) ? $request->params : 'id';
		$orderby = isset( $request->orderby ) ? $request->orderby : 'asc';
		$rows = isset( $request->limit ) ? $request->limit : 10;
		$query = new $nas; 
		
		if( $params === '' )
		{
			$query = $query->orderBy($params, $orderby)->paginate( $rows );
		}
		else
		{
			$query = $query->orderBy($params, $orderby)
			->paginate( $rows );
		}
		
		$data = [
			'results' => $query
		];
		return response()->json( $data, 200 );
	}
}
