<?php
Class usedController Extends baseController {
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

        $id = $this->registry->router->param_id;

        $spare_model = $this->model->get('sparepartModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => 'spare_part_id IN (SELECT spare_part FROM spare_vehicle)',
        );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND spare_part_id = '.$id;
        }

        $tongsodong = count($spare_model->getAllStock($data));
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
            'where' => 'spare_part_id IN (SELECT spare_part FROM spare_vehicle)',
            );

        if (isset($id) && $id > 0) {
            $data['where'] .= ' AND spare_part_id = '.$id;
        }
        
        if ($keyword != '') {
            $search = ' AND ( spare_part_code LIKE "%'.$keyword.'%" 
                        OR spare_part_name LIKE "%'.$keyword.'%" 
                        OR spare_part_seri LIKE "%'.$keyword.'%" 
                        OR spare_part_brand LIKE "%'.$keyword.'%" )';
            $data['where'] .= $search;
        }
        
        $spares = $spare_model->getAllStock($data);
        $this->view->data['spares'] = $spares;

        $shipment_model = $this->model->get('shipmentModel');
        $road_model = $this->model->get('roadModel');

        $spare_vehicle_model = $this->model->get('sparevehicleModel');
        $data_vehicle = array();

        foreach ($spares as $spare) {
           
            $data_im = array(
                'where' => 'spare_part = '.$spare->spare_part_id.' AND start_time > 0 ',
            );
            $stock_ims = $spare_vehicle_model->getAllStock($data_im);
            foreach ($stock_ims as $im) {
                $data_vehicle[$spare->spare_part_id]['import'] = $im->start_time;

                if ($im->vehicle > 0) {
                    $data_ship = array(
                        'where'=>'vehicle = '.$im->vehicle.' AND shipment_date >= '.$im->start_time,
                    );
                    $shipments = $shipment_model->getAllShipment($data_ship);
                    foreach ($shipments as $ship) {
                        $roads = $road_model->getAllRoad(array('where'=>'road_from = '.$ship->shipment_from.' AND road_to = '.$ship->shipment_to.' AND start_time <= '.$ship->shipment_date.' AND end_time >= '.$ship->shipment_date));
                        foreach ($roads as $road) {
                            $data_vehicle[$spare->spare_part_id]['km'] = isset($data_vehicle[$spare->spare_part_id]['km'])?$data_vehicle[$spare->spare_part_id]['km']+$road->road_km:$road->road_km;
                        }
                    }
                }
            }

            $data_ex = array(
                'where' => 'spare_part = '.$spare->spare_part_id.' AND end_time > 0 ',
            );
            $stock_exs = $spare_vehicle_model->getAllStock($data_ex);
            foreach ($stock_exs as $ex) {
                $data_vehicle[$spare->spare_part_id]['export'] = $ex->end_time;
            }

        }
        

        $this->view->data['data_vehicle'] = $data_vehicle;

        

        

        $this->view->data['lastID'] = isset($spare_model->getLastStock()->spare_part_id)?$spare_model->getLastStock()->spare_part_id:0;
        
        $this->view->show('used/index');
    }

   


}
?>