<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\RestFul\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\MainModel;
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
        'lastname' => $json->lastname,
        'firstname' => $json->firstname,
        'gender' => $json->gender,
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
      // var_dump($data);
    }
}
