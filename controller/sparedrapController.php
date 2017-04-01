<?php
Class sparedrapController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 6 && $_SESSION['role_logined'] != 8) {
            $this->view->data['disable_control'] = 1;
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Quản lý thông tin vật tư';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'spare_part_code';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }
        
        $vehicle_model = $this->model->get('vehicleModel');
        $romooc_model = $this->model->get('romoocModel');

        $vehicles = $vehicle_model->getAllVehicle(array('order_by'=>'vehicle_number','order'=>'ASC'));
        $vehicle_data = array();
        foreach ($vehicles as $ve) {
            $vehicle_data[$ve->vehicle_id] = $ve->vehicle_number;
        }
        $this->view->data['vehicle_data'] = $vehicle_data;
        
        $romoocs = $romooc_model->getAllVehicle(array('order_by'=>'romooc_number','order'=>'ASC'));
        $romooc_data = array();
        foreach ($romoocs as $ro) {
            $romooc_data[$ro->romooc_id] = $ro->romooc_number;
        }
        $this->view->data['romooc_data'] = $romooc_data;

        $spare_model = $this->model->get('sparedrapModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $json = array('table'=>'spare_part, spare_vehicle','where'=>'spare_drap.spare_part = spare_part_id AND spare_vehicle = spare_vehicle_id');
        
        $tongsodong = count($spare_model->getAllStock(null,$json));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['limit'] = $limit;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );

        
        if ($keyword != '') {
            $search = ' AND ( spare_part_code LIKE "%'.$keyword.'%" 
                        OR spare_part_name LIKE "%'.$keyword.'%" 
                        OR spare_part_seri LIKE "%'.$keyword.'%" 
                        OR spare_part_brand LIKE "%'.$keyword.'%" )';
            $data['where'] .= $search;
        }
        
        $spares = $spare_model->getAllStock($data,$json);
        $this->view->data['spares'] = $spares;

        $spare_sub_model = $this->model->get('sparesubModel');

        $spare_part_types = array();
        foreach ($spares as $spare) {
            $spare_sub = "";
            $sts = explode(',', $spare->spare_part_type);
            foreach ($sts as $key) {
                $subs = $spare_sub_model->getStock($key);
                if ($subs) {
                    if ($spare_sub == "")
                        $spare_sub .= $subs->spare_sub_name;
                    else
                        $spare_sub .= ','.$subs->spare_sub_name;
                }
                
            }
            $spare_part_types[$spare->spare_drap_id] = $spare_sub;
        }
        
        $this->view->data['spare_part_types'] = $spare_part_types;

        $this->view->data['lastID'] = isset($spare_model->getLastStock()->spare_drap_id)?$spare_model->getLastStock()->spare_drap_id:0;
        
        $this->view->show('sparedrap/index');
    }

    public function getSub(){
        header('Content-type: application/json');
        $q = $_GET["search"];

        $sub_model = $this->model->get('sparesubModel');
        $data = array(
            'where' => 'spare_sub_name LIKE "%'.$q.'%"',
        );
        $subs = $sub_model->getAllStock($data);
        $arr = array();
        foreach ($subs as $sub) {
            $arr[] = $sub->spare_sub_name;
        }
        
        echo json_encode($arr);
    }


}
?>