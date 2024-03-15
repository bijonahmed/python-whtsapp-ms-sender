<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Auth;
use Validator;
use Helper;
use App\Models\Product;
use App\Models\User;
use App\Models\Sliders;
use App\Models\SellerAds;
use App\Models\ProductCategory;
use App\Models\ProductAdditionalImg;
use App\Models\Categorys;
use App\Models\Setting;
use App\Models\HomeAroductSliderCategory;
use App\Models\User as ModelsUser;
use Illuminate\Support\Str;
use App\Rules\MatchOldPassword;
use Illuminate\Support\Facades\Hash;
use DB;
use File;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExampleEmail;
//use User;
use Workbench\App\Models\User as AppModelsUser;

use function Ramsey\Uuid\v1;




class UnauthenticatedController extends Controller
{
    protected $frontend_url;
    protected $userid;

    public function allCategory(Request $request)
    {
        $categories = Categorys::with('children.children.children.children.children')->where('parent_id', 0)->get();
        return response()->json($categories);
    }



    // Assuming you're making this request from a controller method
    public function sendMessage(Request $request)
    {

        //DD($request->all());
        // Retrieve form data from the request
        $phoneNumbers = $request->input('phone_number');
        $message = $request->input('message');


        //echo "$phoneNumbers----$message";exit;
        // Make a POST request to the Flask API endpoint
        $response = Http::withHeaders([
            'Content-Type' => 'application/json', // Set the Content-Type header
        ])->post('http://localhost:5000', [
            'phone_number' => $phoneNumbers,
            'message' => $message,
        ]);

        // Check if the request was successful
        if ($response->successful()) {
            // Messages sent successfully
            return redirect()->route('send-sms')->with('success', 'Messages sent successfully');
        } else {
            // Handle error
            return redirect()->route('send-sms')->with('error', 'Failed to send messages');
        }
    }


    public function limitedProducts()
    {

        $data = Product::orderBy('id', 'desc')->select('id', 'name', 'thumnail_img', 'slug')->limit(12)->get();
        //dd($data);
        $collection = collect($data);
        $modifiedCollection = $collection->map(function ($item) {
            return [
                'id'        => $item['id'],
                'name'      => substr($item['name'], 0, 20),
                'thumnail'  => !empty($item->thumnail_img) ? url($item->thumnail_img) : "",
                'slug'        => $item['slug'],
            ];
        });
        //dd($modifiedCollection);
        return response()->json($modifiedCollection, 200);
    }

    public function pagniatedProducts(Request $request)
    {

        $perPage = 12; // You can adjust the number of items per page as needed
        $products = Product::where('status', 1)
            ->select('id', 'discount', 'name as pro_name', 'description', 'price', 'thumnail_img', 'slug as pro_slug')
            ->orderBy('created_at', 'desc') // Or use the appropriate column
            ->paginate($perPage);

        $result = [];
        foreach ($products as $key => $v) {
            $result[] = [
                'id'           => $v->id,
                'product_id'   => $v->id,
                'product_name' => $v->pro_name,
                'category_id'  => $v->category_id,
                'discount'     => $v->discount,
                'price'        => number_format($v->price, 2),
                'thumnail_img' => url($v->thumnail_img),
                'pro_slug'     => $v->pro_slug,

            ];
        }

        $data['result']        = $result;
        $data['pro_count']     = count($result);
        return response()->json($data, 200);
    }

    public function topSellProducts()
    {
        $data = Product::orderBy('id', 'desc')->select('id', 'name', 'thumnail_img', 'slug')->limit(12)->get();
        foreach ($data as $v) {
            $result[] = [
                'id'   => $v->id,
                'name' => substr($v->name, 0, 12) . '...',
                'thumnail'  => !empty($v->thumnail_img) ? url($v->thumnail_img) : "",
                'slug'     => $v->slug,
            ];
        }

        // dd($result);
        return response()->json($result, 200);
    }

    public function slidersImages()
    {
        $data = Sliders::where('status', 1)->get();

        foreach ($data as $v) {
            $result[] = [
                'id'           => $v->id,
                'images'       => !empty($v->images) ? url($v->images) : "",
            ];
        }

        return response()->json($result, 200);
    }

    public function productCategory(Request $request)
    {


        $catIds = HomeAroductSliderCategory::where('status', 1)->pluck('category_id')->toArray();
        $commaSeparatedIds = implode(',', $catIds);
        // dd($commaSeparatedIds);
        $category_id  = $commaSeparatedIds; //"25,318,26";
        $category_ids = explode(',', $category_id);
        $categorys = ProductCategory::join('product', 'product.id', '=', 'produc_categories.product_id')
            ->join('categorys', 'categorys.id', '=', 'produc_categories.category_id')
            ->select('produc_categories.product_id', 'product.name', 'product.slug', 'product.thumnail_img', 'categorys.name as cate_name', 'categorys.slug as catslug')
            ->whereIn('produc_categories.category_id', $category_ids)
            ->orderByDesc('product.id')
            ->limit(10)
            ->get();
        // Group the results by category name
        $groupedCategories = $categorys->groupBy('cate_name');
        // Initialize the array for the final result
        $categories = [];
        // Iterate through the grouped categories
        foreach ($groupedCategories as $categoryName => $categoryGroup) {
            // Initialize the array for the products in each category
            $products = [];
            // Iterate through products in the category group
            foreach ($categoryGroup as $v) {
                $products[] = [
                    'product_id' => $v->product_id,
                    'name' => substr($v->name, 0, 12) . '...',
                    'thumnail' => !empty($v->thumnail_img) ? url($v->thumnail_img) : "",
                    'slug' => $v->slug,
                ];
            }
            // Add the category and its products to the final result
            $categories[] = [
                'name' => $categoryName,
                'slug' => $categoryGroup->first()->catslug, // Assuming the slug is the same for all products in the category
                'products' => $products,
            ];
        }
        $data['result']  = !empty($categories) ? $categories : "";
        return response()->json($data, 200);
    }

    public function filterCategory(Request $request)
    {
        $categories = Categorys::where('status', 1)->orderBy("name", "asc")->get();;
        return response()->json($categories);
    }

    public function getSellerCategoryFilter($category_id)
    {

        $allProducts = ProductCategory::join('product', 'produc_categories.product_id', '=', 'product.id')
            ->where('produc_categories.category_id', $category_id)
            ->select('product.id as product_id', 'product.name as product_name', 'product.thumnail_img', 'product.slug', 'product.price', 'product.discount', 'produc_categories.category_id')
            ->get();

        foreach ($allProducts as $v) {
            $products[] = [
                'id'           => $v->product_id,
                'name'         => substr($v->product_name, 0, 12) . '...',
                'thumnail'     => !empty($v->thumnail_img) ? url($v->thumnail_img) : "",
                'slug'         => $v->slug,
                'price'        => $v->price,
                'discount'     => $v->discount,
            ];
        }
        $data['products']                = !empty($products) ? $products : "";

        // dd($data['products']);
        return response()->json($data);
    }

    // Encryption function
    public function encryptText($plaintext, $key, $iv)
    {
        $cipher = "aes-256-cbc";
        $options = OPENSSL_RAW_DATA;
        $encrypted = openssl_encrypt($plaintext, $cipher, $key, $options, $iv);
        return base64_encode($encrypted);
    }

    // Decryption function
    public function decryptText($ciphertext, $key, $iv)
    {
        $cipher = "aes-256-cbc";
        $options = OPENSSL_RAW_DATA;
        $decrypted = openssl_decrypt(base64_decode($ciphertext), $cipher, $key, $options, $iv);
        return $decrypted;
    }




    public function sendEmail(Request $request)
    {

        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'name'    => 'required',
            'email'   => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $form_email = $request->email;
        $messages   = $request->messages;
        $to = "support@astute360corp.com";
        $subject = "Customer Query";
        $message = $messages;
        $headers = "From: $form_email\r\n";
        //$headers .= "Reply-To: sender@example.com\r\n";
        $headers .= "Content-type: text/html\r\n";
        mail($to, $subject, $message, $headers);
        // Send the email
        //$mailSuccess = mail($to, $subject, $message, $headers);
        // Check if the email was sent successfully
        // if ($mailSuccess) {
        //     echo "Email sent successfully!";
        // } else {
        //     echo "Failed to send email.";
        // }

        $response = [
            'message' => "Thank you!"
        ];
        return response()->json($response);
    }



    public function updatePassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'reset_token' => 'required',
            'password' => 'min:2|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'min:2'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        //  dd($request->all());
        $reset_token = $request->reset_token;
        $chkrem_token = User::where('remember_token', $reset_token)->first();

        if (!empty($chkrem_token)) {
            // dd($chkrem_token);
            $id  =  $chkrem_token->id;
            // echo $id; 
            $validator = Validator::make($request->all(), [
                'password' => 'min:2|required_with:password_confirmation|same:password_confirmation',
                'password_confirmation' => 'min:2'
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::find($id);
            $user->password = Hash::make($request->password);
            $user->show_password = $request->password;
            $user->save();
            $response = "Password successfully changed!";
            return response()->json($response);
        }
    }

    public function forgetpassword(Request $request)
    {

        $hostname = $request->hostname;
        $validator = Validator::make($request->all(), [
            // 'email'       => 'required',
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = $request->email;

        // Example usage
        $originalText = $email;
        $encryptionKey = "yourSecretKey"; // Replace with your actual encryption key
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("aes-256-cbc"));
        $encryptedText = $this->encryptText($originalText, $encryptionKey, $iv);
        $cleanedEncryptedText = str_replace(['\\', '/'], '', $encryptedText);
        //echo "Cleaned Encrypted Text: " . $cleanedEncryptedText . "\n";

        //echo $encryptedText;exit; 
        $resetlink = "$hostname/resetpassword/$cleanedEncryptedText";

        $user = User::where('email', $email)->first();
        if (!empty($user)) {
            // Update the email
            $user->update(['remember_token' => $cleanedEncryptedText]);
            // Optionally, you can retrieve the updated user data
            //$updatedUser = User::find($user->id);
        }

        // You can pass data to the email view if needed
        $to = $email;
        $subject = "Forget Password";

        // Concatenate the message with the reset link text
        $message = "We received a request to reset your password. Click the link below to reset it. If you didn't request this, you can safely ignore this email.\n\n";
        $message .= 'Reset Password: ' . $resetlink;
        //  echo $message;
        //  exit;

        // Set additional headers for HTML content
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

        // Send the email
        mail($to, $subject, $message, $headers);

        $response = [
            'message' => "Thank you! We've sent you an email with further instructions. Please check your inbox. If you don't find it there, kindly check your spam or junk folder."
        ];
        return response()->json($response);
    }

    public function getSeller($slug)
    {

        $row    = User::where('business_name_slug', $slug)->first();

        $sliderImg   = Product::where('seller_id', $row->id)->where('status', 1)->limit(12)->get();
        $allProducts = Product::where('seller_id', $row->id)->where('status', 1)->get();

        $findCategory = $allProducts;
        $categoryList = [];
        foreach ($findCategory as $v) {
            $category = ProductCategory::where('product_id', $v->id)->select('category_id')->first();
            if ($category) {
                $catName = Categorys::where('id', $category->category_id)->select('name')->first();
                if ($catName) {
                    $categoryList[] = [
                        'name' => $catName->name,
                        'id'   => $category->category_id,
                    ];
                }
            }
        }
        foreach ($sliderImg as $v) {
            $slidersImg[] = [
                'id'           => $v->id,
                'name'         => substr($v->name, 0, 12) . '...',
                'thumnail'     => !empty($v->thumnail_img) ? url($v->thumnail_img) : "",
                'slug'         => $v->slug,
                'price'        => $v->price,
                'discount'     => $v->discount,
            ];
        }

        foreach ($allProducts as $v) {
            $products[] = [
                'id'           => $v->id,
                'name'         => substr($v->name, 0, 12) . '...',
                'thumnail_img' => !empty($v->thumnail_img) ? url($v->thumnail_img) : "",
                'slug'         => $v->slug,
                'quantity'     => 1,
                'price'        => $v->price,
                'discount'     => $v->discount,
            ];
        }

        $businessLogog = !empty($row) ? url($row->business_logo) : "";
        $data['business_owner_name']     = !empty($row) ? $row->business_owner_name : "";
        $data['business_name']           = !empty($row) ? $row->business_name : "";
        $data['business_address']        = !empty($row) ? $row->business_address : "";
        $data['business_register_num']   = !empty($row) ? $row->business_register_num : "";
        $data['business_email']          = !empty($row) ? $row->business_email : "";
        $data['business_phone']          = !empty($row) ? $row->business_phone : "";
        $data['business_logo']           = $businessLogog;
        $data['slidersImg']              = !empty($slidersImg) ? $slidersImg : "";
        $data['products']                = !empty($products) ? $products : "";
        $data['categoryList']            = !empty($categoryList) ? $categoryList : "";

        //ads banner 
        $topBanner      = SellerAds::where('seller_id', $row->id)->where('position', 'top_banner_img')->first();
        $bannerAds_1    = SellerAds::where('seller_id', $row->id)->where('position', 'banner_1')->first();
        $bannerAds_2    = SellerAds::where('seller_id', $row->id)->where('position', 'banner_2')->first();
        $bannerAds_3    = SellerAds::where('seller_id', $row->id)->where('position', 'banner_3')->first();
        $bannerAds_4    = SellerAds::where('seller_id', $row->id)->where('position', 'banner_4')->first();
        $bannerAds_5    = SellerAds::where('seller_id', $row->id)->where('position', 'banner_5')->first();
        $youtube_ads    = SellerAds::where('seller_id', $row->id)->where('position', 'youtube_videos')->first();
        // dd($youtube_ads->file_name);
        $data['top_banner_img']       = !empty($topBanner) ? url($topBanner->file_name) : "";
        $data['banner1']              = !empty($bannerAds_1) ? url($bannerAds_1->file_name) : "";
        $data['banner2']              = !empty($bannerAds_2) ? url($bannerAds_2->file_name) : "";
        $data['banner3']              = !empty($bannerAds_3) ? url($bannerAds_3->file_name) : "";
        $data['banner4']              = !empty($bannerAds_4) ? url($bannerAds_4->file_name) : "";
        $data['banner5']              = !empty($bannerAds_5) ? url($bannerAds_5->file_name) : "";
        $data['file_name']            = !empty($youtube_ads) ? $youtube_ads->file_name : "";
        //END baner 
        return response()->json($data);
    }

    public function allsellers()
    {

        $find_sellers  = User::where('role_id', 3)->select('id', 'business_name', 'business_logo', 'business_name_slug')->orderBy('id', 'desc')->get();

        foreach ($find_sellers as $v) {
            $results[] = [
                'id'           => $v->id,
                'name'         => substr($v->business_name, 0, 12) . '...',
                'thumnail'     => !empty($v->business_logo) ? url($v->business_logo) : "",
                'slug'         => $v->business_name_slug,
            ];
        }


        return response()->json($results);
    }
    public function findProductSlug($slug)
    {

        $data['pro_row']  = Product::where('product.slug', $slug)
            ->select('product.id', 'product.id as product_id', 'product.name', 'product.slug as pro_slug', 'product.thumnail_img', 'description', 'product.price', 'product.discount', 'product.stock_qty', 'product.stock_mini_qty')
            ->first();

        $product_chk       = Product::where('product.slug', $slug)
            ->select('product.id', 'product.id as product_id', 'product.name', 'product.slug as pro_slug', 'product.thumnail_img', 'description', 'product.price', 'product.discount', 'product.stock_qty', 'product.stock_mini_qty')
            ->get();
        $products = [];
        foreach ($product_chk as $key => $v) {
            $products[] = [
                'id'           => $v->id,
                'product_id'   => $v->product_id,
                'product_name' => $v->pro_name,
                'discount'     => $v->discount,
                'price'        => number_format($v->price, 2),
                'thumnail_img' => url($v->thumnail_img),
                'pro_slug'     => $v->pro_slug,

            ];
        }
        $findproductrow   = $data['pro_row'];
        $data['att_img']  = ProductAdditionalImg::where('produc_img_history.product_id', $findproductrow->id)->get();
        foreach ($data['att_img'] as $v) {
            $mul_slider_img[] = [
                'product_id'   => $v->product_id,
                'thumnail'     => !empty($v->images) ? url($v->images) : "",
            ];
        }
        $data['slider_img']    = !empty($mul_slider_img) ? $mul_slider_img : "";
        $data['featuredImage'] = url($findproductrow->thumnail_img);
        $data['product']      = $products;
        return response()->json($data, 200);
    }

    public function findCategorys($slug)
    {

        $chkCategory   = Categorys::where('slug', $slug)->select('id', 'name')->first();
        $proCategorys  = ProductCategory::where('category_id', $chkCategory->id)
            ->select('product.id', 'product.discount', 'produc_categories.product_id', 'product.name as pro_name', 'produc_categories.category_id', 'description', 'price', 'thumnail_img', 'product.slug as pro_slug')
            ->join('product', 'product.id', '=', 'produc_categories.product_id')->get();

        $result = [];
        foreach ($proCategorys as $key => $v) {
            $result[] = [
                'id'           => $v->id,
                'product_id'   => $v->product_id,
                'product_name' => $v->pro_name,
                'category_id'  => $v->category_id,
                'discount'     => $v->discount,
                'price'        => number_format($v->price, 2),
                'thumnail_img' => url($v->thumnail_img),
                'pro_slug'     => $v->pro_slug,

            ];
        }

        $data['result']        = $result;
        $data['pro_count']     = count($result);
        $data['categoryname']  = $chkCategory->name;
        return response()->json($data, 200);
    }
}
