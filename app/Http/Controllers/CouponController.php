<?php

namespace Modules\Coupon\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Categories\app\Models\Categories;
use Modules\Coupon\app\Models\Coupon;
use Modules\GlobalSetting\app\Models\Language;
use Modules\Product\app\Models\Product;

class CouponController extends Controller
{
    public function index(): View
    {
        $currentRouteName = Route::currentRouteName();

        if ($currentRouteName == 'admin.coupon') {
            return view('coupon::admin.coupon_list');
        } else {
            return view('coupon::provider.coupon_list');
        }
    }

    public function couponList(Request $request): JsonResponse
    {
        try {
            $orderBy = $request->order_by ?? 'desc';
            $id = $request->user_id;
            $isValid = $request->is_valid ?? 1;
            
            $condition = $isValid == 1 ? '>=' : '<';
            $currentDate = Carbon::today();

            $data = Coupon::where(['created_by' => $id])->where('end_date', $condition, $currentDate)->orderBy('id', $orderBy)->get()->map(function ($coupon) {
                if ($coupon->product_id) {
                    $coupon->product_id = explode(',', $coupon->product_id);
                }
                if ($coupon->category_id) {
                    $coupon->category_id = explode(',', $coupon->category_id);
                }
                if ($coupon->subcategory_id) {
                    $coupon->subcategory_id = explode(',', $coupon->subcategory_id);
                }

                return $coupon;
            });

            return response()->json([
                'code' => 200,
                'message' => __('Coupon details retrieved successfully.'),
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => __('An error occurred while retrieving coupon.'),
            ], 500);
        }
    }

    public function create(Request $request): View
    {
        $languageId = 1;

        if (Auth::check()) {
            $languageId = Auth::user()->user_language_id;
        } elseif (Cookie::get('languageId')) {
            $languageId = Cookie::get('languageId');
        } else {
            $defaultLanguage = Language::select('id', 'code')->where('status', 1)->where('is_default', 1)->first();
            $languageId = $defaultLanguage ? $defaultLanguage->id : 1;
        }

        $categories = Categories::where(['language_id' => $languageId, 'parent_id' => 0, 'status' => 1])
            ->where('source_type', 'service')
            ->get(['id', 'name']);

        $subcategories = Categories::where('language_id', $languageId)
            ->where('parent_id', '!=', 0)
            ->where('status', 1)
            ->where('source_type', 'service')
            ->get(['id', 'name']);

        $products = '';

        if (Auth::user()->user_type == 1 || Auth::user()->user_type == 5) {
            $products = Product::where(['language_id' => $languageId, 'status' => 1])->get(['id', 'source_name']);
        } else {
            $userId = Auth::id();
            $products = Product::where(['language_id' => $languageId, 'status' => 1, 'user_id' => $userId])->get(['id', 'source_name']);
        }

        $currentRouteName = Route::currentRouteName();

        if ($currentRouteName == 'admin.create-coupon') {
            return view('coupon::admin.create_coupon', compact('categories', 'subcategories', 'products'));
        } else {
            return view('coupon::provider.create_coupon', compact('categories', 'subcategories', 'products'));
        }

    }

    public function edit(Request $request): View
    {
        $languageId = 1;

        if (Auth::check()) {
            $languageId = Auth::user()->user_language_id;
        } elseif (Cookie::get('languageId')) {
            $languageId = Cookie::get('languageId');
        } else {
            $defaultLanguage = Language::select('id', 'code')->where('status', 1)->where('is_default', 1)->first();
            $languageId = $defaultLanguage ? $defaultLanguage->id : 1;
        }

        $categories = Categories::where(['language_id' => $languageId, 'parent_id' => 0, 'status' => 1])
            ->where('source_type', 'service')
            ->get(['id', 'name']);

        $subcategories = Categories::where('language_id', $languageId)
            ->where('parent_id', '!=', 0)
            ->where('status', 1)
            ->where('source_type', 'service')
            ->get(['id', 'name']);

            if (Auth::user()->user_type == 1 || Auth::user()->user_type == 5) {
                $products = Product::where(['language_id' => $languageId, 'status' => 1])->get(['id', 'source_name']);
            } else {
                $userId = Auth::id();
                $products = Product::where(['language_id' => $languageId, 'status' => 1, 'user_id' => $userId])->get(['id', 'source_name']);
            }

        $data = Coupon::where('id', $request->id ?? '')->first();
        
        if (!empty($data)) {
            $data->product_id = explode(',', $data->product_id);
            $data->category_id = explode(',', $data->category_id);
            $data->subcategory_id = explode(',', $data->subcategory_id);
        }
        // dd($data);

        $currentRouteName = Route::currentRouteName();

        if ($currentRouteName == 'admin.edit-coupon') {
            return view('coupon::admin.edit_coupon', compact('categories', 'subcategories', 'products', 'data'));
        } else {
            return view('coupon::provider.edit_coupon', compact('categories', 'subcategories', 'products', 'data'));
        }

    }

    public function store(Request $request)
    {
        $id = $request->id ?? '';

        try {

            $data = [
                "code" => $request->code,
                "product_type" => $request->product_type,
                "coupon_type" => $request->coupon_type,
                "coupon_value" => $request->coupon_value,
                "quantity" => $request->quantity,
                "start_date" => $request->start_date,
                "end_date" => $request->end_date,
            ];

            if (!$id) {
                $data['created_by'] = Auth::id() ?? $request->created_by;
            }

            $quantityValue = $request->quantity_value ?? null;
            $productId = $request->product_id ?? null;
            $categoryId = $request->category_id ?? null;
            $subcategoryId = $request->subcategory_id ?? null;

            if($request->quantity == 'limited') {
                $data['quantity_value'] = $request->quantity_value;
            } else {
                $data['quantity_value'] = null;
            }

            if (is_array($productId) && $request->product_type == 'service') {
                $productId = implode(',', $productId);
                $data['product_id'] = $productId;
            } else {
                $data['product_id'] = null;
            }

            if (is_array($categoryId) && $request->product_type == 'category') {
                $categoryId = implode(',', $categoryId);
                $data['category_id'] = $categoryId;
            } else {
                $data['category_id'] = null;
            }

            if (is_array($subcategoryId) && $request->product_type == 'subcategory') {
                $subcategoryId = implode(',', $subcategoryId);
                $data['subcategory_id'] = $subcategoryId;
            } else {
                $data['subcategory_id'] = null;
            }


            $successMsg = __('coupon_create_success');
            $errorMsg = __('coupon_create_error');

            if (!empty($id)) {
                $successMsg = __('coupon_update_success');
                $errorMsg = __('coupon_update_error');
            }

            Coupon::updateOrCreate(['id' => $id], $data);


            return response()->json([
                'code' => 200,
                'message' => $successMsg,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $errorMsg,
            ], 500);
        }
        
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $langCode = $request->language_code ?? 'en';
        
        try {
            
            $coupon =  Coupon::where('id', $id)->delete();
            
            if (!$coupon) {
                return response()->json([
                    'code' => 200,
                    'message' => __('Coupon not found!', [], $langCode)
                ], 200);
            }

            return response()->json([
                'code' => 200,
                'message' => __('coupon_delete_success', [], $langCode)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => __('coupon_delete_error', [], $langCode),
            ], 500);
        }
    }

    public function checkUnique(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $rules = [];

        if ($request->has('code')) {
            $rules['code'] = [
                'required',
                Rule::unique('coupons', 'code')->ignore($id)->whereNull('deleted_at')
            ];
        }

        $validator = Validator::make($request->only(['code']), $rules);

        if ($validator->fails()) {
            return response()->json(false);
        }

        return response()->json(true);
    }

    public function changeCouponStatus(Request $request): JsonResponse
    {
        $id = $request->id;
        $status = $request->status;
        $langCode = $request->language_code ?? 'en';
        
        try {
            Coupon::where('id', $id)->update([
                'status' => $status
            ]);

            return response()->json([
                'code' => 200,
                'message' => __('coupon_status_success', [], $langCode)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => __('coupon_status_error', [], $langCode),
            ], 500);
        }
    }

}
