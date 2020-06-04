<?php
	namespace App\Http\Controllers;

	use App\User;
	use Firebase\JWT\JWT;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Facades\Validator;
	use Laravel\Lumen\Routing\Controller as BaseController;

	class UserController extends BaseController
	{
		/**
		 * Create a new controller instance.
		 *
		 * @return void
		 */
		public function __construct()
		{
			//
		}

		/*
		|--------------------------------------------------------------------------
		| Api สมัครสมาชิก
		|--------------------------------------------------------------------------
		 */
		public function register(Request $request)
		{
			// validator
			$validator = Validator::make($request->all(), [
				'email' => 'required|email|unique:users',
				'password' => 'required',
				'name' => 'required',
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();

				return $this->responseRequestError($errors);
			} else {
				$user = new User();
				$user->email = $request->email;
				$user->name = $request->name;
				$user->password = Hash::make($request->password);

				if ($user->save()) {
					$token = $this->jwt($user);
					$user['api_token'] = $token;
					return $this->responseRequestSuccess($user);
				} else {
					return $this->responseRequestError('Cannot Register');
				}
			}
		}

		/*
		|--------------------------------------------------------------------------
		| Api เข้าสู่ระบบ
		|--------------------------------------------------------------------------
		 */
		public function login(Request $request)
		{
			$user = User::where('email', $request->email)
				->first();

			if (!empty($user) && Hash::check($request->password, $user->password)) {
				$token = $this->jwt($user);
				$user["api_token"] = $token;

				return $this->responseRequestSuccess($user);
			} else {
				return $this->responseRequestError("Username or password is incorrect");
			}
		}

		/*
		|--------------------------------------------------------------------------
		| ตัวเข้ารหัส JWT
		|--------------------------------------------------------------------------
		 */
		protected function jwt($user)
		{
			$payload = [
				'iss' => "lumen-jwt", // Issuer of the token
				'sub' => $user->user_id, // Subject of the token
				'iat' => time(), // Time when JWT was issued.
				'exp' => time() + env('JWT_EXPIRE_HOUR') * 60 * 60, // Expiration time
			];
			return JWT::encode($payload, env('JWT_SECRET'));
		}

		/*
		|--------------------------------------------------------------------------
		| response เมื่อข้อมูลส่งถูกต้อง
		|--------------------------------------------------------------------------
		 */
		protected function responseRequestSuccess($ret)
		{
			return response()->json(['status' => 'success', 'data' => $ret], 200);
		}

		/*
		|--------------------------------------------------------------------------
		| response เมื่อข้อมูลมีการผิดพลาด
		|--------------------------------------------------------------------------
		 */
		protected function responseRequestError($message = 'Bad request', $statusCode = 400)
		{
			return response()->json(['status' => 'error', 'error' => $message], $statusCode);
		}

		public function getMyVehicle(Request $request) {
			$user = $request->auth;
			$vehicles = \DB::table('vehicle_trackings as vt')
				->leftJoin('vehicles', 'vehicles.vehicle_id', 'vt.vehicle_id')
				->where('authority_id', $user->user_id)
				->select('vt.vt_id', 'vt.vehicle_id', 'vt.vehicle_id','vehicles.vehicle_image', 'vt.vt_plate', 'vt.vt_plate_prefix', 'vehicles.vehicle_type', 'vehicles.vehicle_color', 'vehicles.camera_id')->get();
			return response()->json(['status' => 'success', 'data' => $vehicles], 200);
		}

		public function addVehicle(Request $request) {
			$user = $request->auth;
			$platePrefix = $request->input('vehicle_plate_prefix');
			$plate = $request->input('vehicle_plate');
			$vehicles = \DB::table('vehicle_trackings')->insert(
				['authority_id' => $user->user_id, 'vehicle_id' => null, 'vt_plate' => $plate, 'vt_plate_prefix' => $platePrefix]
			);
			return response()->json(['status' => 'success'], 200);
		}

		public function me(Request $request) {
			return $this->responseRequestSuccess($request->auth);
		}

	}