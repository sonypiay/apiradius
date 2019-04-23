<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\DB\RadAcct;
use App\Http\Controllers\Controller;
use DateTime;
use DateInterval;
use DatePeriod;

class RadAccountingController extends Controller
{
	public function acctshowall( Request $request, RadAcct $radacct )
	{
		$sorting = isset( $request->sorting ) ? $request->sorting : 'asc' ;
		$orderby = isset( $request->orderby ) ? $request->orderby : 'radacctid';
		$rows = isset( $request->rows ) ? $request->rows : 10;
		
		$data_radacct = $radacct->orderBy( $orderby, $sorting )
		->groupBy('username')
		->paginate( $rows );
		$res = [
			'status' => 200,
			'total' => $data_radacct->total(),
			'results' => $data_radacct
		];
		return response()->json( $res );
	}
	
	public function bandwidthClientUsage( Request $request, RadAcct $radacct, $mac )
	{
		$filterdate = isset( $request->filterdate ) ? $request->filterdate : '7days';
		$mac_semicolon = $mac;
		$mac_dash = str_replace( ':','-', $mac );
		
		$totalUsage = $radacct->select(
			DB::raw('date_format(acctstarttime, "%Y-%m-%d") as date'),
			DB::raw('sum(acctinputoctets) as uploadusage'),
			DB::raw('sum(acctoutputoctets) as downloadusage')
		)
		->where('callingstationid', '=', $mac_semicolon)
		->orWhere('callingstationid', '=', $mac_dash)
		->groupBy('callingstationid')
		->get();
		
		$totalUsageUpload = 0;
		$totalUsageDownload = 0;
		$currentUsageUpload = 0;
		$currentUsageDownload = 0;
		
		$currentUsage = $radacct->select(
			DB::raw('date_format(acctstarttime, "%Y-%m-%d") as date'),
			DB::raw('sum(acctinputoctets) as uploadusage'),
			DB::raw('sum(acctoutputoctets) as downloadusage')
		)
		->where([
			['callingstationid', '=', $mac_semicolon],
			[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '=', date('Y-m-d')]
		])
		->orWhere([
			['callingstationid', '=', $mac_dash],
			[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '=', date('Y-m-d')]
		])
		->groupBy('callingstationid')
		->get();
		
		// sum total download and upload
		for( $i = 0; $i < count( $totalUsage ); $i++ )
		{
			$totalUsageDownload += $totalUsage[$i]['downloadusage'];
			$totalUsageUpload += $totalUsage[$i]['uploadusage'];
		}
		
		// sum current download and upload
		for( $i = 0; $i < count( $currentUsage ); $i++ )
		{
			$currentUsageDownload += $currentUsage[$i]['downloadusage'];
			$currentUsageUpload += $currentUsage[$i]['uploadusage'];
		}
		
		if( $filterdate == 'this_month' OR $filterdate == 'last_month' )
		{
			if( $filterdate == 'this_month' )
			{
				$currentMonth = new DateTime( 'first day of this month' );
				$endMonth = new DateTime( 'last day of this month' );
				$endMonth->modify('+1 day');
			}
			else
			{
				$currentMonth = new DateTime( 'first day of last month' );
				$endMonth = new DateTime( 'last day of last month' );
				$endMonth->modify('+1 day');
			}
			
			$interval = new DateInterval('P1D');
			$period = new DatePeriod( $currentMonth, $interval, $endMonth );
			$rangeDate = [];
			
			foreach( $period as $date )
			{
				$rangeDate[] = [
					'value' => $date->format('Y-m-d'),
					'text' => $date->format('M d, Y')
				];
			}
			
			$results = [];
			foreach( $rangeDate as $date ) 
			{
				$getusage = $radacct->select(
					DB::raw('sum(acctinputoctets) as uploadusage'),
					DB::raw('sum(acctoutputoctets) as downloadusage')
				)
				->where([
					['callingstationid', '=', $mac_dash],
					[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '=', $date['value']]
				])
				->orWhere([
					['callingstationid', '=', $mac_semicolon],
					[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '=', $date['value']]
				])
				->groupBy('callingstationid')
				->first();
				
				$results[] = [
					'date' => [
						'text' => $date['text'],
						'value' => $date['value']
					],
					'download' => $getusage['downloadusage'],
					'upload' => $getusage['uploadusage']
				];
			}
		}
		else
		{
			if( $filterdate == '7days' )
			{
				$previousDate = new DateTime('7 days ago');
			}
			else if( $filterdate == '14days' )
			{
				$previousDate = new DateTime('14 days ago');
			}
			else if( $filterdate == '28days' )
			{
				$previousDate = new DateTime('28 days ago');
			}
			else
			{
				$previousDate = new DateTime('30 days ago');
			}
			
			$currentDate = new DateTime('today');
			$interval = new DateInterval('P1D');
			$period = new DatePeriod( $previousDate, $interval, $currentDate );
			$rangeDate = [];
			
			foreach( $period as $date )
			{
				$rangeDate[] = [
					'value' => $date->format('Y-m-d'),
					'text' => $date->format('M d, Y')
				];
			}
			
			$results = [];
			foreach( $rangeDate as $date )
			{
				$getusage = $radacct->select(
					DB::raw('sum(acctinputoctets) as uploadusage'),
					DB::raw('sum(acctoutputoctets) as downloadusage')
				)
				->where([
					['callingstationid', '=', $mac_dash],
					[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '=', $date['value']]
				])
				->orWhere([
					['callingstationid', '=', $mac_semicolon],
					[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '=', $date['value']]
				])
				->first();
				
				$results[] = [
					'date' => [
						'text' => $date['text'],
						'value' => $date['value']
					],
					'download' => $getusage['downloadusage'],
					'upload' => $getusage['uploadusage']
				];
			}
		}
		
		$data = [
			'totalUsage' => [
				//'download' => $totalUsage[0]['downloadusage'] != null ? $totalUsage[0]['downloadusage'] : 0,
				//'upload' => $totalUsage[0]['uploadusage'] != null ? $totalUsage[0]['uploadusage'] : 0,
				'download' => $totalUsageDownload != 0 ? $totalUsageDownload : 0,
				'upload' => $totalUsageUpload != 0 ? $totalUsageUpload : 0
			],
			'currentUsage' => [
				//'download' => $getcurrentusage['downloadusage'] != null ? $getcurrentusage['downloadusage'] : 0,
				//'upload' => $getcurrentusage['uploadusage'] != null ? $getcurrentusage['uploadusage'] : 0,
				'download' => $currentUsageDownload != 0 ? $currentUsageDownload : 0,
				'upload' => $currentUsageUpload != 0 ? $currentUsageUpload : 0
			],
			'usagePerDay' => $results
		];
		
		return response()->json( $data );
	}
	
	public function total_bandwidth_usage( Request $request, RadAcct $radacct )
	{		
		$usage = [];
		$startDate = $request->startDate;
		$endDate = $request->endDate;
		
		$total_bandwidth_usage = $radacct->select(
			DB::raw('sum(acctinputoctets) as upload'),
			DB::raw('sum(acctoutputoctets) as download')
		)
		->whereBetween(DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), [$startDate, $endDate])
		->first();
				
		$freehotspot_total_usage = $radacct->select(
			DB::raw('sum(acctinputoctets) as upload'),
			DB::raw('sum(acctoutputoctets) as download')
		)
		->where([
			['username', '=', 'newhotspot'],
			[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '>=', $startDate],
			[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '<=', $endDate]
		])
		->orWhere([
			['username', '=', '6e6577686f7473706f74'],
			[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '>=', $startDate],
			[DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), '<=', $endDate]
		])
		->first();
					
		$subscriber_total_usage = $radacct->select(
			DB::raw('sum(acctinputoctets) as upload'),
			DB::raw('sum(acctoutputoctets) as download')
		)
		->where([
			['username', '!=', 'newhotspot'],
			['username', '!=', '6e6577686f7473706f74']
		])
		->whereBetween(DB::raw('date_format(acctstarttime, "%Y-%m-%d")'), [$startDate, $endDate])
		->first();
		
		$freehotspot_total_uploadusage = $freehotspot_total_usage->upload == null ? 0 : $freehotspot_total_usage->upload;
		$freehotspot_total_downloadusage = $freehotspot_total_usage->download == null ? 0 : $freehotspot_total_usage->download;
		$subs_total_uploadusage = $subscriber_total_usage->upload == null ? 0 : $subscriber_total_usage->upload;
		$subs_total_downloadusage = $subscriber_total_usage->download == null ? 0 : $subscriber_total_usage->download;
		$total_bandwidth_usage_upload = $total_bandwidth_usage->upload == null ? 0 : $total_bandwidth_usage->upload;
		$total_bandwidth_usage_download = $total_bandwidth_usage->download == null ? 0 : $total_bandwidth_usage->download;
		
		$usage = [
			'total_usage' => [
				'upload' => $total_bandwidth_usage_upload,
				'download' => $total_bandwidth_usage_download
			],
			'freehotspot' => [
				'total_usage' => [
					'upload' => $freehotspot_total_uploadusage,
					'download' => $freehotspot_total_downloadusage
				]
			],
			'subscribers' => [
				'total_usage' => [
					'upload' => $subs_total_uploadusage,
					'download' => $subs_total_downloadusage
				]
			]
		];
		
		return response()->json( $usage, 200 );
	}
}