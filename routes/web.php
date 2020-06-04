<?php

	/** @var \Laravel\Lumen\Routing\Router $router */


	/*
	|--------------------------------------------------------------------------
	| Application Routes
	|--------------------------------------------------------------------------
	|
	| Here is where you can register all of the routes for an application.
	| It is a breeze. Simply tell Lumen the URIs it should respond to
	| and give it the Closure to call when that URI is requested.
	|
	*/


	use Carbon\Carbon;

	$router->group(['middleware' => 'cors'], function ($router) {

		$router->get('/api/vehicle/create', function () use ($router) {

			DB::table('vehicles')->insert([
				"camera_id" => 2,
				"vehicle_plate_prefix" => 'wd',
				"vehicle_plate" => '223',
				"vehicle_type" => 'car',
				"vehicle_color" => 'black',
				"vehicle_image" => '',
				"vehicle_datetime" => Carbon::now()
			]);
			return response()->json([
				'status' => 'success',
				'data' => 'create vehicle mock data'
			]);
		});

		$router->get('/api/cameras', function () use ($router) {

			$cameras = DB::table('cameras')->get();
			return response()->json([
				'status' => 'success',
				'data' => $cameras
			]);
		});

		$router->get('/api/vehicles', function () use ($router) {
			$vehicles = DB::table('vehicles')
				->where('vehicle_datetime', '>=', Carbon::now()->subSeconds(7))
				->select(DB::raw('*, LOWER(vehicle_type) as vehicle_type, LOWER(vehicle_color) as vehicle_color'))
				->orderBy('vehicle_id', 'desc')->get();
			return response()->json([
				'status' => 'success',
				'data' => $vehicles
			]);
		});

		$router->get('/api/vehicle', function (Illuminate\Http\Request $request) use ($router) {

			$textPrefix = $request->get('text-prefix') ? $request->get('text-prefix') : '' ;
			$text = $request->get('text') ? $request->get('text') : '' ;
			$type = $request->get('type') ? $request->get('type') : '' ;
			$camera = $request->get('camera') ? $request->get('camera') : '' ;
			$color = $request->get('color') ? $request->get('color') : '' ;

			$vehicles = DB::table('vehicles')
				->where('vehicle_plate', 'like', '%' . $text . '%')
				->where('vehicle_plate_prefix', 'like', '%' . $textPrefix . '%')
				->where('vehicle_type', 'like', '%' . ($type !== 'all' ? $type : '') . '%')
				->where('vehicle_color', 'like', '%' . ($color !== 'all' ? $color : '') . '%')
				->where('camera_id', 'like', '%' . ($camera != 0 ? $camera : '') . '%')
				->orderBy('vehicle_id', 'desc')->get();

			return response()->json([
				'status' => 'success',
				'data' => $vehicles
			]);
		});

		$router->group(['prefix' => 'api'], function ($router) {
			// Users
			$router->post('user/register', 'UserController@register');
			$router->post('user/login', ['uses' => 'UserController@login']);
		});

		$router->group(['prefix' => 'api', 'middleware' => 'jwt.auth'], function ($router) {
			$router->get('user/me', 'UserController@me');
			$router->get('vehicle/me', 'UserController@getMyVehicle');
			$router->post('vehicle/add', 'UserController@addVehicle');
		});
	});



