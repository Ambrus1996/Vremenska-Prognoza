<?php

require_once 'model/City.php';

class CityController
{
    private $cityModel;

    public function __construct()
    {
        $this->cityModel = new City();
    }

    public function listCities()
    {
        $cities = $this->cityModel->getAllCities()->fetchAll();
    
        include 'views/list_cities.php';
    }    
}
