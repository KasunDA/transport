<?php
Class adminController Extends baseController {
    public function index() {
    	$this->view->setLayout('admin');
    	if (!isset($_SESSION['role_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Dashboard';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
        }
        else{
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
        }

        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;

        $vehicle_model = $this->model->get('vehicleModel');

        $vehicles = $vehicle_model->getAllVehicle();
        $this->view->data['vehicles'] = $vehicles;

        $join = array('table'=>'customer, vehicle, cont_unit','where'=>'customer.customer_id = shipment.customer AND vehicle.vehicle_id = shipment.vehicle AND cont_unit=cont_unit_id');

        $shipment_model = $this->model->get('shipmentModel');

        $data = array(

            'where' => 'shipment_date >= '.strtotime($batdau).' AND shipment_date <= '.strtotime($ketthuc),

            );

        $shipments = $shipment_model->getAllShipment($data,$join);

        $doanhthu = 0;
        $sanluong = 0;
        $khachhang = 0;
        $donhang = 0;
        $sl = array();
        $old_cus = array();
        $vehicle_data = array();

        foreach ($shipments as $shipment) {
        	if (!in_array($shipment->customer,$old_cus)) {
        		$khachhang++;
                $old_cus[] = $shipment->customer;
            }
        	$donhang++;
        	$sanluong += $shipment->shipment_ton;
        	$doanhthu += $shipment->shipment_revenue+$shipment->shipment_charge_excess;

        	$vehicle_data[$shipment->vehicle]['ship'] = isset($vehicle_data[$shipment->vehicle]['ship'])?$vehicle_data[$shipment->vehicle]['ship']+1:1;
        	$vehicle_data[$shipment->vehicle]['ton'] = isset($vehicle_data[$shipment->vehicle]['ton'])?$vehicle_data[$shipment->vehicle]['ton']+$shipment->shipment_ton:$shipment->shipment_ton;
        	$vehicle_data[$shipment->vehicle]['revenue'] = isset($vehicle_data[$shipment->vehicle]['revenue'])?$vehicle_data[$shipment->vehicle]['revenue']+$shipment->shipment_revenue+$shipment->shipment_charge_excess:$shipment->shipment_revenue+$shipment->shipment_charge_excess;

        	$sl['ngay'][(int)date('d',$shipment->shipment_date)] = isset($sl['ngay'][(int)date('d',$shipment->shipment_date)])?$sl['ngay'][(int)date('d',$shipment->shipment_date)]+$shipment->shipment_revenue+$shipment->shipment_charge_excess:$shipment->shipment_revenue+$shipment->shipment_charge_excess;
            $sl['thang'][(int)date('m',$shipment->shipment_date)] = isset($sl['thang'][(int)date('m',$shipment->shipment_date)])?$sl['thang'][(int)date('m',$shipment->shipment_date)]+$shipment->shipment_revenue+$shipment->shipment_charge_excess:$shipment->shipment_revenue+$shipment->shipment_charge_excess;
        }

        $start = date('d',strtotime($batdau));
        $start_month = date('m',strtotime($batdau));
        $start_year = date('Y',strtotime($batdau));
        $end = date('d',strtotime($ketthuc));
        $end_month = date('m',strtotime($ketthuc));
        $end_year = date('Y',strtotime($ketthuc));
        $graph = array();
        if ($start_month == $end_month && $start_year == $end_year) {
            for ($i=$start; $i <= $end; $i++) { 
                $graph[]['y'] = $start_year.'-'.$start_month.'-'.$i;
                $graph[]['item1'] = isset($sl['ngay'][$i])?$sl['ngay'][$i]:0;
            }
        }
        elseif ($start_month != $end_month && $start_year == $end_year) {
            for ($i=$start_month; $i <= $end_month; $i++) { 
                $graph[]['y'] = $start_year.'-'.$i;
                $graph[]['item1'] = isset($sl['thang'][$i])?$sl['thang'][$i]:0;
            }
        }
        
        $graph = str_replace('"},{"i','","i',json_encode($graph));

        $this->view->data['doanhthu'] = $doanhthu;
        $this->view->data['sanluong'] = $sanluong;
        $this->view->data['khachhang'] = $khachhang;
        $this->view->data['donhang'] = $donhang;
        $this->view->data['graph'] = $graph;
        $this->view->data['vehicle_data'] = $vehicle_data;

           $this->view->show('admin/index');
    }

   public function checklockuser(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($_POST['data'] == 0) {
                echo 0;
            }
            else{
                $user_model = $this->model->get('userModel');
            
                $user = $user_model->getUserByWhere(array('user_id' => $_POST['data']));
                echo $user->user_lock;
            }
            
        }
    }
    public function customquery() {
    	$this->view->setLayout('admin');
        if ($_SESSION['role_logined'] != 1) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['title'] = 'Dịch vụ vận tải, xuất nhập khẩu, thủ tục hải quan, chỉnh sửa manifest';

        $road_model = $this->model->get('roadModel');
        $roads1 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM road order by start_time desc) as t WHERE t.police_cost > 0 AND t.status=1 group by t.road_from, t.road_to LIMIT 0,100');
        $roads2 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM road order by start_time desc) as t WHERE t.police_cost > 0 AND t.status=1 group by t.road_from, t.road_to LIMIT 100,100');
        
        if($roads1){
	        foreach ($roads1 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost,
	                'police_cost' => 0,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('04-06-2015'),
	                'end_time' => strtotime('04-06-2018'),
	                'status' => 1,
	        	);

	        	if ($road->police_cost > 300000) {
	        		$data['police_cost'] = $road->police_cost+100000;
	        	}
	        	elseif ($road->police_cost > 0 && $road->police_cost <= 300000) {
	        		$data['police_cost'] = $road->police_cost+50000;
	        	}

	        	$data_update = array(
	        		'end_time' => strtotime('03-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads2){
	        foreach ($roads2 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost,
	                'police_cost' => 0,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('04-06-2015'),
	                'end_time' => strtotime('04-06-2018'),
	                'status' => 1,
	        	);

	        	if ($road->police_cost > 300000) {
	        		$data['police_cost'] = $road->police_cost+100000;
	        	}
	        	elseif ($road->police_cost > 0 && $road->police_cost <= 300000) {
	        		$data['police_cost'] = $road->police_cost+50000;
	        	}

	        	$data_update = array(
	        		'end_time' => strtotime('03-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    


        $this->view->show('admin/customquery');
    }
    public function customquery2() {
    	$this->view->setLayout('admin');
        if ($_SESSION['role_logined'] != 1) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['title'] = 'Dịch vụ vận tải, xuất nhập khẩu, thủ tục hải quan, chỉnh sửa manifest';

        $road_model = $this->model->get('roadModel');
        $roads1 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "HCM%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "BMT%") group by t.road_from, t.road_to ');
        $roads2 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "BMT%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "HCM%") group by t.road_from, t.road_to ');
        $roads3 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "ĐN%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "BMT%") group by t.road_from, t.road_to ');
        $roads4 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "BMT%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "ĐN%") group by t.road_from, t.road_to ');
        $roads5 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "BR%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "BMT%") group by t.road_from, t.road_to ');
        $roads6 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "BMT%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "BR%") group by t.road_from, t.road_to ');
        $roads7 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "BMT%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "BD%") group by t.road_from, t.road_to ');
        $roads8 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "BD%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "BMT%") group by t.road_from, t.road_to ');
        $roads9 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "LA%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "BMT%") group by t.road_from, t.road_to ');
        $roads10 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "BMT%") and t.road_to in (select warehouse_id from warehouse where warehouse_name like "LA%") group by t.road_from, t.road_to ');
        
        if($roads1){
	        foreach ($roads1 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads2){
	        foreach ($roads2 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads3){
	        foreach ($roads3 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads4){
	        foreach ($roads4 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads5){
	        foreach ($roads5 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads6){
	        foreach ($roads6 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads7){
	        foreach ($roads7 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads8){
	        foreach ($roads8 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads9){
	        foreach ($roads9 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads10){
	        foreach ($roads10 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+100000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('15-06-2015'),
	                'end_time' => strtotime('15-06-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('14-06-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    


        $this->view->show('admin/customquery2');
    }

    public function customquery3() {
    	$this->view->setLayout('admin');
        if ($_SESSION['role_logined'] != 1) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['title'] = 'Dịch vụ vận tải, xuất nhập khẩu, thủ tục hải quan, chỉnh sửa manifest';

        $road_model = $this->model->get('roadModel');
        $roads1 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_to in (select warehouse_id from warehouse where warehouse_name like "BMT%") and t.road_to not in (select warehouse_id from warehouse where warehouse_name like "BMT - Đak Song%" or warehouse_name like "BMT - Đắc Song%")  and (way > 0 or (way = 0 and road_km>300) ) group by t.road_from, t.road_to ');
        $roads2 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "BMT%") and t.road_from not in (select warehouse_id from warehouse where warehouse_name like "BMT - Đak Song%" or warehouse_name like "BMT - Đắc Song%")  and (way > 0 or (way = 0 and road_km>300) ) group by t.road_from, t.road_to ');
        $roads3 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_to in (select warehouse_id from warehouse where warehouse_name like "GL - Minh Khoa%") and (way > 0 or (way = 0 and road_km>300) ) group by t.road_from, t.road_to ');
        $roads4 = $road_model->queryRoad('SELECT * FROM (SELECT * FROM `road` order by start_time desc) as t WHERE t.road_from in (select warehouse_id from warehouse where warehouse_name like "GL - Minh Khoa%") and (way > 0 or (way = 0 and road_km>300) ) group by t.road_from, t.road_to ');
        
        if($roads1){
	        foreach ($roads1 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+180000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('04-08-2015'),
	                'end_time' => strtotime('04-08-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('03-08-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads2){
	        foreach ($roads2 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+180000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('04-08-2015'),
	                'end_time' => strtotime('04-08-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('03-08-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads3){
	        foreach ($roads3 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+180000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('04-08-2015'),
	                'end_time' => strtotime('04-08-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('03-08-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    if($roads4){
	        foreach ($roads4 as $road) {
	        	$data = array(
	        		'road_from' => $road->road_from,
	        		'road_to' => $road->road_to,
	        		'road_oil' => $road->road_oil,
	                'road_time' => $road->road_time,
	                'road_km' => $road->road_km,
	                'way' => $road->way,
	                'bridge_cost' => $road->bridge_cost+180000,
	                'police_cost' => $road->police_cost,
	                'tire_cost' => $road->tire_cost,
	                'charge_add' => $road->charge_add,
	                'start_time' => strtotime('04-08-2015'),
	                'end_time' => strtotime('04-08-2018'),
	                'status' => 1,
	        	);

	        	
	        	$data_update = array(
	        		'end_time' => strtotime('03-08-2015'),
	                'status' => 0,
	        	);

	        	$road_model->updateRoad($data_update,array('road_id' => $road->road_id));

	        	$road_model->createRoad($data);
	        }
	    }
	    


        $this->view->show('admin/customquery3');
    }

    public function querytemp(){

    	$this->view->setLayout('admin');
        if ($_SESSION['role_logined'] != 1) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['title'] = 'Dịch vụ vận tải, xuất nhập khẩu, thủ tục hải quan, chỉnh sửa manifest';

        $shipment_model = $this->model->get('shipmentModel');

        $shipment_model->queryShipment('update `shipment` set commission=10000, commission_number=shipment_ton WHERE customer=132 and shipment_date >= 1427839200 and shipment_date <= 1435615200');

    }


}
?>