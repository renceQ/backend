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

class MainController extends ResourceController
{
    public function index()
    {
        //
    }


    public function save()
    {
      $json = $this->request->getJSON();
      $data = [
        'trackcode' => $json->trackcode,
        'firstname' => $json->firstname,
        'middlename' => $json->middlename,
        'lastname' => $json->lastname,
        'age' => $json->age,
      ];
        $main = new MainModel();
        $r = $main->save($data);
        return $this->respond($r, 200);
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


    public function sav()
    {
        try {
            $json = $this->request->getJSON();

            // Extracting data from the request
            $category_id = $json->category_id;
            $prod_name = $json->prod_name;
            $stock = $json->stock;
            $price = $json->price;
            $unit_price = $json->unit_price;
            $size_id = $json->size_id;

            // Placeholder for image path
            $imagePath = '';

            // Check if an image was uploaded
            if ($file = $this->request->getFile('image')) {
                // Move the uploaded file to the specified folder
                $newName = $file->getRandomName();
                $file->move('./frontend/src/assets/images/', $newName);
                $imagePath = base_url() . '/frontend/src/assets/images/' . $newName;
            }

            // Prepare data for insertion into the productlist table
            $data = [
                'category_id' => $category_id,
                'image_path' => $imagePath,
                'prod_name' => $prod_name,
                'stock' => $stock,
                'price' => $price,
                'unit_price' => $unit_price,
                'size_id' => $size_id,
            ];

            // Insert data into the productlist table
            $productModel = new ProductModel();
            $insertedId = $productModel->insert($data);

            // Respond based on the insertion result
            if ($insertedId) {
                return $this->respond(['message' => 'Product added successfully', 'id' => $insertedId], 200);
            } else {
                return $this->respond(['message' => 'Failed to add product'], 500);
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            log_message('error', 'Product insertion failed: ' . $e->getMessage());
            return $this->respond(['message' => 'An error occurred while adding the product'], 500);
        }
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
                return $this->respond(['msg' => 'okay', 'token' => $data['token']]);
            } else {
                return $this->respond(['msg' => 'error'], 200);
            }
        }
    }

}

//final copy
