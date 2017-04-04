<?php
Class customershipController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 4 && $_SESSION['role_logined'] != 5) {
            $this->view->data['disable_control'] = 1;
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Tổng hợp sản lượng theo mặt hàng khách hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;

            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;

            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;

            $xe = isset($_POST['xe']) ? $_POST['xe'] : null;
        }

        else{

            $keyword = "";

            $xe = 0;

            $batdau = '01-'.date('m-Y');

            $ketthuc = date('t-m-Y'); //cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')).'-'.date('m-Y');

        }

        $vehicle_model = $this->model->get('vehicleModel');

        $vehicles = $vehicle_model->getAllVehicle();

        $this->view->data['vehicles'] = $vehicles;

        $contunit_model = $this->model->get('contunitModel');

        $this->view->data['cont_units'] = $contunit_model->getAllUnit();

        $customer_model = $this->model->get('customerModel');

        $customer_sub_model = $this->model->get('customersubModel');

        $data = array(
            'where' => '1=1',
        );

        if ($keyword != '') {
            $search = '(

                customer_name LIKE "%'.$keyword.'%" 

                    )';

            $data['where'] = $data['where']." AND ".$search;
        }

        $customers = $customer_model->getAllCustomer($data);
        $this->view->data['customers'] = $customers;

        $data_sub = array();
        $customer_subs = array();
        foreach ($customers as $customer) {
            $subs = $customer_sub_model->getAllCustomer(array('where'=>'customer_sub_id IN ('.$customer->customer_sub.')'));
            if ($subs) {
                foreach ($subs as $sub) {
                    $data_sub[$customer->customer_id]['name'][] = $sub->customer_sub_name;
                    $data_sub[$customer->customer_id]['id'][] = $sub->customer_sub_id;
                    $customer_subs[$sub->customer_sub_id] = $sub->customer_sub_name;
                }
            }
            else{
                $data_sub[$customer->customer_id]['name'][] = "";
                $data_sub[$customer->customer_id]['id'][] = "";
            }
            
        }
        $this->view->data['data_sub'] = $data_sub;
        $this->view->data['customer_subs'] = $customer_subs;

        $this->view->data['batdau'] = $batdau;

        $this->view->data['ketthuc'] = $ketthuc;

        $this->view->data['xe'] = $xe;

        $this->view->data['keyword'] = $keyword;

        $this->view->data['page'] = 1;
        $this->view->data['order_by'] = "";
        $this->view->data['order'] = "";


        $shipment_model = $this->model->get('shipmentModel');

        $data = array(

            'where' => 'shipment_date >= '.strtotime($batdau).' AND shipment_date <= '.strtotime($ketthuc),

            );


        if($xe != 0){

            $data['where'] = $data['where'].' AND vehicle = '.$xe;

        }

        $shipments = $shipment_model->getAllShipment($data);

        $datas = array();

        foreach ($shipments as $ship) {
            $types = explode(',', $ship->customer_type);
            foreach ($types as $key) {
                $datas[$ship->customer][$key]['total'] = isset($datas[$ship->customer][$key]['total'])?$datas[$ship->customer][$key]['total']+1:1;
                $datas[$ship->customer][$key][$ship->cont_unit_net]['receive'] = isset($datas[$ship->customer][$key][$ship->cont_unit_net]['receive'])?$datas[$ship->customer][$key][$ship->cont_unit_net]['receive']+$ship->shipment_ton_net:$ship->shipment_ton_net;
                $datas[$ship->customer][$key][$ship->cont_unit]['delivery'] = isset($datas[$ship->customer][$key][$ship->cont_unit]['delivery'])?$datas[$ship->customer][$key][$ship->cont_unit]['delivery']+$ship->shipment_ton:$ship->shipment_ton;
            }
            
        }

        $this->view->data['datas'] = $datas;

        $this->view->show('customership/index');
    }
    


}
?>