<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\RestFul\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\MainModel;
use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Models\SizeModel;
use App\Models\EventBookingModel;
use App\Models\UserModel;
use \Config\Services;
use \Firebase\JWT\JWT;
use App\Models\AuditModel;

class MainController extends ResourceController
{
    public function index()
    {

    }
    
//edit
public function updateItem($id)
{
    $productModel = new ProductModel();
    $existingData = $productModel->find($id);

    if (!$existingData) {
        return $this->respond(['error' => 'Item not found.'], 404);
    }

    // Save the existing stock before updating
    $oldStock = $existingData['stock'];

    $data = [
        'category_id' => $this->request->getVar('edit_category_id') ?? $existingData['category_id'],
        'size_id' => $this->request->getVar('edit_size_id') ?? $existingData['size_id'],
        'prod_name' => $this->request->getVar('edit_prod_name') ?? $existingData['prod_name'],
        'stock' => $this->request->getVar('edit_stock') ?? $existingData['stock'],
        'price' => $this->request->getVar('edit_price') ?? $existingData['price'],
        'unit_price' => $this->request->getVar('edit_unit_price') ?? $existingData['unit_price'],
        'UPC' => $this->request->getVar('edit_UPC') ?? $existingData['UPC'],
        'product_description' => $this->request->getVar('edit_product_description') ?? $existingData['product_description'],
        'barcode_image' => $this->request->getVar('barcode_image') ?? $existingData['barcode_image'],
    ];

    // Handle image update if the 'edit_image' field is provided and changed in the request
    $editImage = $this->request->getVar('edit_image');
    $existingImage = $existingData['image'];

    if (!empty($editImage) && $editImage !== $existingImage) {
        $base64Image = $editImage;
        
        // Extract the image extension (e.g., jpeg, png)
        $extension = explode('/', mime_content_type($base64Image))[1];
        $imageName = 'updated_image_' . time() . '.' . $extension; // Generate a unique name for the updated image
        $imagePath = 'uploads/' . $imageName; // Define the path to save the updated image

        // Decode the base64 image string and save it to the server
        $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        file_put_contents(ROOTPATH . 'public/' . $imagePath, $decodedImage);

        // Save the updated image path within baseURL
        $data['image'] = base_url($imagePath);
    } else {
        // If 'edit_image' is not provided or unchanged, retain the existing image path
        $data['image'] = $existingImage;
    }

    // Update the data in the database including stock
    $productModel->set($data)->where('ID', $id)->update();

    // Save the updated stock
    $updatedData = $productModel->find($id);
    $updatedStock = $updatedData['stock'];

    // Insert into audit table
    $auditModel = new AuditModel();
    $auditData = [
        'image' => $existingData['image'],
        'category_id' => $existingData['category_id'],
        'prod_name' => $existingData['prod_name'],
        'stock' => $updatedStock, // Save the updated stock after the update
        'price' => $existingData['price'],
        'unit_price' => $existingData['unit_price'],
        'size_id' => $existingData['size_id'],
        'UPC' => $existingData['UPC'],
        'barcode_image' => $existingData['barcode_image'],
        'product_description' => $existingData['product_description'],
        'old_stock' => $oldStock, // Store the old stock in the audit trail
    ];

    $auditModel->insert($auditData);

    return $this->respond(['message' => 'Item updated successfully.'], 200);
}

//save products............................................................................................

public function save()
{
    
    try {
        // Get barcode_image from POST data
        $barcodeImage = $this->request->getPost('barcode_image');

        // Handle barcode image upload
        $barcodeImageName = 'barcode_' . time(); // Generate a unique name for barcode image
        $barcodeImagePath = ROOTPATH . 'public/uploads/' . $barcodeImageName . '.png'; // Define the path to save the barcode image

        // Decode base64 encoded image and save it
        $barcodeImageBinary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $barcodeImage));
        file_put_contents($barcodeImagePath, $barcodeImageBinary);

        $image = $this->request->getFile('image');
        $prods = $image->getName();

        $data = [
            'category_id' => $this->request->getPost('category_id'),
            'size_id' => $this->request->getPost('size_id'),
            'prod_name' => $this->request->getPost('prod_name'),
            'stock' => $this->request->getPost('stock'),
            'price' => $this->request->getPost('price'),
            'unit_price' => $this->request->getPost('unit_price'),
            'UPC' => $this->request->getPost('UPC'),
            'product_description' => $this->request->getPost('product_description'),
            'image' => base_url() . $this->handleImageUpload($image, $prods),
            'barcode_image' => base_url() . 'public/uploads/' . $barcodeImageName . '.png', // Add barcode_image URL to the data array
        ];
        $auditData = [
            'image' => $data['image'],
            'category_id' => $data['category_id'],
            'size_id' => $data['size_id'],
            'prod_name' => $data['prod_name'],
            'stock' => $data['stock'],
            'price' => $data['price'],
            'unit_price' => $data['unit_price'],
            'UPC' => $data['UPC'],
            'barcode_image' => $data['barcode_image'],
            'product_description' => $data['product_description'],
            // Add other necessary fields as needed
        ];

        // Save data in the AuditModel
        $auditModel = new AuditModel();
        $auditModel->insert($auditData);
        $productModel = new ProductModel();
        $savedData = $productModel->save($data);

        return $this->respond($savedData, 200);
    } catch (\Exception $e) {
        log_message('error', 'Error saving data:' . $e->getMessage());
        return $this->failServerError('An error occurred while saving the data.');
    }
}


public function handleImageUpload($image, $prods)
{
    $image->move(ROOTPATH . 'public/uploads/', $prods);
    return 'uploads/' . $prods;
}


    public function del()
    {
      $json = $this->request->getJSON();
      $id = $json->id;
      $main = new MainModel();
      $r = $main->delete($id);
      return $this->respond($r, 200);
    }


    public function getData()
    {
      $main = new MainModel();
      $data = $main->findAll();
      return $this->respond($data, 200);
    }
    public function getDatas()
    {
      $produ = new ProductModel();
      $datas = $produ->findAll();
      return $this->respond($datas, 200);
    }


    public function getsize(){
    $siz = new SizeModel();
    $data = $siz->findAll();

    $sizes = [];
    foreach ($data as $size) {
      $sizes[] = [
        'size_id' => $size['size_id'],
        'item_size' => $size['item_size']
      ];
  }
  return $this->respond($sizes, 200);
}

  public function savecateg()
  {
    $json = $this->request->getJSON();
    $data = [
      'category_name' => $json->category_name,
    ];
      $cat = new CategoryModel();
      $catd = $cat->save($data);
      return $this->respond($catd, 200);
  }
  public function editcateg()
{
    try {
        $json = $this->request->getJSON();

        // Extracting data from the request
        $category_id = $json->category_id;
        $category_name = $json->category_name;

        // Find the category by ID
        $categoryModel = new CategoryModel();
        $category = $categoryModel->find($category_id);

        if ($category) {
            // Update the category name
            $category['category_name'] = $category_name;
            $categoryModel->update($category_id, $category);

            return $this->respond(['message' => 'Category updated successfully'], 200);
        } else {
            return $this->respond(['message' => 'Category not found'], 404);
        }
    } catch (\Exception $e) {
        // Log the error for debugging
        log_message('error', 'Category update failed: ' . $e->getMessage());
        return $this->respond(['message' => 'An error occurred while updating the category'], 500);
    }
}


  public function getcat()
{
    $cat = new CategoryModel();
    $data = $cat->findAll();

    $categories = []; // Initialize an array to hold formatted categories
    foreach ($data as $category) {
        $categories[] = [
            'id' => $category['id'],
            'category_name' => $category['category_name']
        ];
    }

    return $this->respond($categories, 200);
  }

  public function getevent()
  {
    $event = new EventBookingModel();
    $data = $event->findAll();
    return $this->respond($data, 200);
  }
  public function saveBooking()
  {
    $json = $this->request->getJSON();
    $data = [
      'event_title' => $json->event_title,
      'start_date' => $json->start_date,
      'end_date' => $json->end_date,
      'location' => $json->location,
      'event_description' => $json->event_description,
      'name' => $json->name,
      'email' => $json->email,
      'phone' => $json->phone,
    ];
      $event = new EventBookingModel();
      $eve = $event->save($data);
      return $this->respond($eve, 200);
  }


//login sign up

public function register()
    {
        $user = new UserModel();
        $token = $this->verification(50);
        $data = [
            'username' => $this->request->getVar('username'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'token' => $token,
            'status' => 'active',
            'role' => 'user',
        ];
        $u = $user->save($data);
        if ($u) {
            return $this->respond(['msg' => 'okay', 'token' => $token]);
        } else {
            return $this->respond(['msg' => 'failed']);
        }
    }

    public function verification($length)
    {
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($str_result), 0, $length);
    }

    public function login()
    {
        $user = new UserModel();
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');
        $data = $user->where('username', $username)->first();
        if ($data) {
            $pass = $data['password'];
            $authenticatePassword = password_verify($password, $pass);
            if ($authenticatePassword) {
                return $this->respond(['msg' => 'okay', 'token' => $data['token'], 'profile_picture' => $data['profile_picture'], 'address' => $data['address'], 'contact' => $data['contact'], 'other_info' => $data['other_info']]);
            } else {
                return $this->respond(['msg' => 'error'], 200);
            }
        }
    }


    
    // get products by category
    public function getProductsByCategory($categoryId)
{
    $productModel = new ProductModel();
    $products = $productModel->where('category_id', $categoryId)->findAll();
    return $this->response->setJSON($products);
}



//get user information
public function getUserData($token)
    {
        $user = new UserModel();
        $data = $user->where('token',$token)->findAll();
        return $this->respond($data, 200);
    }

//audit getdata
public function getaudith()
{
  $audithmodel = new AuditModel();
  $data = $audithmodel->findAll();
  return $this->respond($data, 200);
}


}

//final copy