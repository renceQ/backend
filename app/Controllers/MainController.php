<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\RestFul\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\MainModel;
use App\Models\ProductModel;
use App\Models\CategoryModel;

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
        $json = $this->request->getJSON();
        $category_id = $json->category_id;
        $prod_name = $json->prod_name;
        $stock = $json->stock;
        $price = $json->price;

        // Handle image upload
        $imagePath = ''; // Placeholder for the image path or filename

        if ($file = $this->request->getFile('image')) {
            // Process image upload
            $newName = $file->getRandomName();
            $file->move('./uploads/', $newName);
            $imagePath = 'uploads/' . $newName; // Adjust as needed based on your directory structure
        }

        $data = [
            'category_id' => $category_id,
            'image_path' => $imagePath, // Adjust column name as per your database structure
            'prod_name' => $prod_name,
            'stock' => $stock,
            'price' => $price,
        ];

        $productModel = new ProductModel();
        $insertedId = $productModel->insert($data); // Insert data into productlist table

        if ($insertedId) {
            return $this->respond(['message' => 'Product added successfully', 'id' => $insertedId], 200);
        } else {
            return $this->respond(['message' => 'Failed to add product'], 500);
        }
    }

    public function getDatas()
    {
      $produ = new ProductModel();
      $datas = $produ->findAll();
      return $this->respond($datas, 200);
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
}
