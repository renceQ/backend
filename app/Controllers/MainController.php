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
use App\Models\OrderModel;


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
    ];

    // Handle barcode image update if the 'barcode_image' field is provided and changed in the request
    $barcodeImage = $this->request->getVar('barcode_image');
    $existingBarcodeImage = $existingData['barcode_image'];

    if (!empty($barcodeImage) && $barcodeImage !== $existingBarcodeImage) {
        // Decode the base64 barcode image string and save it to the server
        $decodedBarcodeImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $barcodeImage));
        $barcodeImagePath = 'uploads/barcode_' . $id . '.png'; // Assuming PNG format for barcode images

        file_put_contents(ROOTPATH . 'public/' . $barcodeImagePath, $decodedBarcodeImage);
        $data['barcode_image'] = base_url($barcodeImagePath);
    } else {
        // If 'barcode_image' is not provided or unchanged, retain the existing barcode image path
        $data['barcode_image'] = $existingBarcodeImage;
    }

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
        'image' => $data['image'], // Use the updated image path
        'category_id' => $data['category_id'], // Use updated category_id
        'prod_name' => $data['prod_name'], // Use updated prod_name
        'stock' => $data['stock'], // Use updated stock
        'price' => $data['price'], // Use updated price
        'unit_price' => $data['unit_price'], // Use updated unit_price
        'size_id' => $data['size_id'], // Use updated size_id
        'UPC' => $data['UPC'], // Use updated UPC
        'barcode_image' => $data['barcode_image'], // Use updated barcode image path
        'product_description' => $data['product_description'], // Use updated product_description
        'old_stock' => $oldStock, // Store the old stock in the audit trail
        'product_id' => $id, // Save the ID of the updated record from productlist into product_id in audit

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
            'barcode_image' => base_url() . 'uploads/' . $barcodeImageName . '.png', // Add barcode_image URL to the data array
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

      $productModel = new ProductModel();

        // Save data in the ProductModel and get the inserted ID
        $savedData = $productModel->save($data);
        $insertedProductId = $productModel->getInsertID(); // Get the ID of the inserted record

        // Prepare data for AuditModel with the inserted product ID
        $auditData['product_id'] = $insertedProductId;

        // Save data in the AuditModel
        $auditModel = new AuditModel();
        $auditModel->insert($auditData);

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

public function getaudith($productId)
{
    $audithmodel = new AuditModel();
    // Filter records by the passed $productId
    $data = $audithmodel->where('product_id', $productId)->findAll();
    return $this->respond($data, 200);
}


   // save order product
   public function placeOrder()
  {
    $json = $this->request->getJSON();
    $data = [
      'image' => $json->image,
      'prod_name' => $json->prod_name,
      'unit_price' => $json->unit_price,
      'size_id' => $json->size_id,
      'quantity' => $json->quantity,
      'address' => $json->address,
      'contact' => $json->contact,
      'other_info' => $json->other_info,
      'customerName' => $json->customerName,
    //   'status' => $json->status,
    ];
      $ordermodel = new OrderModel();
      $order = $ordermodel->save($data);
      return $this->respond($order, 200);
  }

  public function getOrder()
  {
    $ordermodel = new OrderModel();
    $data = $ordermodel->findAll();
    return $this->respond($data, 200);
  }

//update status
public function updateOrderStatus($id)
{
    $orderModel = new OrderModel();
    $existingOrder = $orderModel->find($id);

    if (!$existingOrder) {
        return $this->respond(['error' => 'Order not found.'], 404);
    }

    // Get the status from the request
    $newStatus = $this->request->getVar('status');

    // Validate the status - You can add more validation as needed
    if (!in_array($newStatus, ['approved', 'denied'])) {
        return $this->respond(['error' => 'Invalid status.'], 400);
    }

    // Update the status of the order
    $data = ['status' => $newStatus];
    $orderModel->set($data)->where('id', $id)->update();

    // Update product stock if the order is approved
    if ($newStatus === 'approved') {
        // Get order details
        $quantity = $existingOrder['quantity'];
        $productId = $existingOrder['id'];

        // Update product stock
        $productModel = new ProductModel();
        $productModel->updateProductStock($productId, $quantity);
    }

    return $this->respond(['message' => 'Order status updated successfully.'], 200);
}


public function updateProductStock($productId, $quantity)
{
    $product = $this->find($productId);

    if ($product) {
        // Get the current stock
        $currentStock = $product['stock'];

        // Calculate updated stock
        $updatedStock = $currentStock - $quantity;

        // Ensure stock doesn't go below 0
        $updatedStock = max(0, $updatedStock);

        // Update the stock in the productlist table
        $this->where('id', $productId)->set('stock', $updatedStock)->update();
    }
}

}

//final copy