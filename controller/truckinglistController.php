<?php

Class truckinglistController Extends baseController {

    public function index() {

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 5) {
            $this->view->data['disable_control'] = 1;
        }

        $this->view->data['lib'] = $this->lib;

        $this->view->data['title'] = 'Bảng kê vận chuyển';



        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;

            $order = isset($_POST['order']) ? $_POST['order'] : null;

            $page = isset($_POST['page']) ? $_POST['page'] : null;

            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;

            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;

            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;

            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;

            $xe = isset($_POST['xe']) ? $_POST['xe'] : null;

            $kh = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
        }

        else{

            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'shipment_date ASC, ';

            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'vehicle_number ASC';

            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;

            $keyword = "";

            $limit = 50;

            $xe = 0;

            $kh = 0;

            $batdau = '01-'.date('m-Y');

            $ketthuc = date('t-m-Y'); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');

        }
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $contunit_model = $this->model->get('contunitModel');
        $cost_list_model = $this->model->get('costlistModel');

        $this->view->data['cont_units'] = $contunit_model->getAllUnit();
        $this->view->data['loan_units'] = $cost_list_model->getAllCost(array('where'=>'cost_list_type = 8'));


        $place_model = $this->model->get('placeModel');

        $place_data = array();

        $places = $place_model->getAllPlace();


        foreach ($places as $place) {

                $place_data['place_id'][$place->place_id] = $place->place_id;

                $place_data['place_name'][$place->place_id] = $place->place_name;

        }

        $this->view->data['place'] = $place_data;


        $vehicle_model = $this->model->get('vehicleModel');

        $vehicles = $vehicle_model->getAllVehicle();



        $this->view->data['vehicles'] = $vehicles;

        $customer_model = $this->model->get('customerModel');

        $customers = $customer_model->getAllCustomer();



        $this->view->data['customers'] = $customers;




        $join = array('table'=>'customer, vehicle, cont_unit','where'=>'customer.customer_id = shipment.customer AND vehicle.vehicle_id = shipment.vehicle AND cont_unit=cont_unit_id');



        $shipment_model = $this->model->get('shipmentModel');

        $sonews = $limit;

        $x = ($page-1) * $sonews;

        $pagination_stages = 2;



        $data = array(

            'where' => 'shipment_date >= '.strtotime($batdau).' AND shipment_date < '.strtotime($ngayketthuc),

            );


        if($xe != 0){

            $data['where'] = $data['where'].' AND vehicle = '.$xe;

        }

        if($kh > 0){

            $data['where'] = $data['where'].' AND customer = '.$kh;

        }


        /*if ($_SESSION['role_logined'] == 3) {

            $data['where'] = $data['where'].' AND shipment_create_user = '.$_SESSION['userid_logined'];

            

        }*/



        $tongsodong = count($shipment_model->getAllShipment($data,$join));

        $tongsotrang = ceil($tongsodong / $sonews);

        



        $this->view->data['page'] = $page;

        $this->view->data['order_by'] = $order_by;

        $this->view->data['order'] = $order;

        $this->view->data['keyword'] = $keyword;

        $this->view->data['limit'] = $limit;

        $this->view->data['pagination_stages'] = $pagination_stages;

        $this->view->data['tongsotrang'] = $tongsotrang;

        $this->view->data['sonews'] = $sonews;



        $this->view->data['batdau'] = $batdau;

        $this->view->data['ketthuc'] = $ketthuc;



        $this->view->data['xe'] = $xe;
        $this->view->data['kh'] = $kh;




        $data = array(

            'order_by'=>$order_by,

            'order'=>$order,

            'limit'=>$x.','.$sonews,

            'where' => 'shipment_date >= '.strtotime($batdau).' AND shipment_date < '.strtotime($ngayketthuc),

            );


        if($xe != 0){

            $data['where'] = $data['where'].' AND vehicle = '.$xe;

        }

        if($kh > 0){

            $data['where'] = $data['where'].' AND customer = '.$kh;

        }


        /*if ($_SESSION['role_logined'] == 3) {

            $data['where'] = $data['where'].' AND shipment_create_user = '.$_SESSION['userid_logined'];

            

        }*/



        if ($keyword != '') {

            $ngay = (strtotime(str_replace("/", "-", $keyword))!="")?(' OR shipment_date LIKE "%'.strtotime(str_replace("/", "-", $keyword)).'%"'):"";

            $search = '(

                    vehicle_number LIKE "%'.$keyword.'%"

                    OR customer_name LIKE "%'.$keyword.'%"

                    OR shipment_from in (SELECT place_id FROM place WHERE place_name LIKE "%'.$keyword.'%" ) 

                    OR shipment_to in (SELECT place_id FROM place WHERE place_name LIKE "%'.$keyword.'%" ) 

                    '.$ngay.'

                        )';

            $data['where'] = $data['where']." AND ".$search;

        }



        $road_model = $this->model->get('roadModel');

       

        $road_data = array();

        

        $datas = $shipment_model->getAllShipment($data,$join);



        $this->view->data['shipments'] = $datas;



        $this->view->data['lastID'] = isset($shipment_model->getLastShipment()->shipment_id)?$shipment_model->getLastShipment()->shipment_id:0;


        $customer_sub_model = $this->model->get('customersubModel');

        $export_stock_model = $this->model->get('exportstockModel');

        $shipment_cost_model = $this->model->get('shipmentcostModel');

        $customer_types = array();

        $export_stocks = array();

        $loan_shipment_data = array();

        $v = array();


        foreach ($datas as $ship) {

            $loans = $shipment_cost_model->getAllShipment(array('where'=>'shipment = '.$ship->shipment_id),array('table'=>'cost_list','where'=>'cost_list = cost_list_id AND cost_list_type = 8'));
            foreach ($loans as $loan) {
                $loan_shipment_data[$ship->shipment_id][$loan->cost_list] = isset($loan_shipment_data[$ship->shipment_id][$loan->cost_list])?$loan_shipment_data[$ship->shipment_id][$loan->cost_list]+$loan->cost:$loan->cost;
                
            }

                

            $customer_sub = "";
            $sts = explode(',', $ship->customer_type);
            foreach ($sts as $key) {
                $subs = $customer_sub_model->getCustomer($key);
                if ($subs) {
                    if ($customer_sub == "")
                        $customer_sub .= $subs->customer_sub_name;
                    else
                        $customer_sub .= ','.$subs->customer_sub_name;
                }
                
            }
            $customer_types[$ship->shipment_id] = $customer_sub;

            $export_sub = "";
            $sts = explode(',', $ship->export_stock);
            foreach ($sts as $key) {
                $subs = $export_stock_model->getStock($key);
                if ($subs) {
                    if ($export_sub == "")
                        $export_sub .= $subs->export_stock_code;
                    else
                        $export_sub .= ','.$subs->export_stock_code;
                }
                
            }
            $export_stocks[$ship->shipment_id] = $export_sub;

        }

        $this->view->data['customer_types'] = $customer_types;

        $this->view->data['export_stocks'] = $export_stocks;

        $this->view->data['loan_shipment_data'] = $loan_shipment_data;


        $this->view->show('truckinglist/index');

    }




}

?>