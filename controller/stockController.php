<?php
Class stockController Extends baseController {
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
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;

            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $vong = isset($_POST['vong']) ? $_POST['vong'] : null;

            $trangthai = isset($_POST['staff']) ? $_POST['staff'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'spare_part_code';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $batdau = '01-'.date('m-Y');

            $ketthuc = date('t-m-Y');

            $vong = (int)date('m',strtotime($batdau));

            $trangthai = date('Y',strtotime($batdau));
        }

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $vong = (int)date('m',strtotime($batdau));

        $trangthai = date('Y',strtotime($batdau));
        
        $id = $this->registry->router->param_id;

        $spare_model = $this->model->get('sparepartModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $data = array(
            'where' => '1=1',
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
        $this->view->data['batdau'] = $batdau;

        $this->view->data['ketthuc'] = $ketthuc;

        $this->view->data['vong'] = $vong;

        $this->view->data['trangthai'] = $trangthai;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
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

        $spare_stock_model = $this->model->get('sparestockModel');
        $data_stock = array();

        foreach ($spares as $spare) {
            

            $join_im = array('table'=>'import_stock','where'=>'import_stock = import_stock_id');
            $data_im = array(
                'where' => 'spare_part = '.$spare->spare_part_id.' AND import_stock_date >= '.strtotime($batdau).' AND import_stock_date <= '.strtotime($ketthuc),
            );
            $stock_ims = $spare_stock_model->getAllStock($data_im,$join_im);
            foreach ($stock_ims as $im) {
                $data_stock[$spare->spare_part_id]['import']['unit'] = $im->spare_stock_unit;
                $data_stock[$spare->spare_part_id]['import']['number'] = isset($data_stock[$spare->spare_part_id]['import']['number'])?$data_stock[$spare->spare_part_id]['import']['number']+$im->spare_stock_number:$im->spare_stock_number;
                $data_stock[$spare->spare_part_id]['import']['price'] = isset($data_stock[$spare->spare_part_id]['import']['price'])?$data_stock[$spare->spare_part_id]['import']['price']+($im->spare_stock_number*$im->spare_stock_price):($im->spare_stock_number*$im->spare_stock_price);
            }

            $join_ex = array('table'=>'export_stock','where'=>'export_stock = export_stock_id');
            $data_ex = array(
                'where' => 'spare_part = '.$spare->spare_part_id.' AND export_stock_date >= '.strtotime($batdau).' AND export_stock_date <= '.strtotime($ketthuc),
            );
            $stock_exs = $spare_stock_model->getAllStock($data_ex,$join_ex);
            foreach ($stock_exs as $ex) {
                $data_stock[$spare->spare_part_id]['export']['unit'] = $ex->spare_stock_unit;
                $data_stock[$spare->spare_part_id]['export']['number'] = isset($data_stock[$spare->spare_part_id]['export']['number'])?$data_stock[$spare->spare_part_id]['export']['number']+$ex->spare_stock_number:$ex->spare_stock_number;
                $data_stock[$spare->spare_part_id]['export']['price'] = isset($data_stock[$spare->spare_part_id]['export']['price'])?$data_stock[$spare->spare_part_id]['export']['price']+($ex->spare_stock_number*$ex->spare_stock_price):($ex->spare_stock_number*$ex->spare_stock_price);
            }

            ////
            $join_im = array('table'=>'import_stock','where'=>'import_stock = import_stock_id');
            $data_im = array(
                'where' => 'spare_part = '.$spare->spare_part_id.' AND import_stock_date < '.strtotime($batdau),
            );
            $stock_ims = $spare_stock_model->getAllStock($data_im,$join_im);
            foreach ($stock_ims as $im) {
                $data_stock[$spare->spare_part_id]['dauki']['unit'] = $im->spare_stock_unit;
                $data_stock[$spare->spare_part_id]['dauki']['number'] = isset($data_stock[$spare->spare_part_id]['dauki']['number'])?$data_stock[$spare->spare_part_id]['dauki']['number']+$im->spare_stock_number:$im->spare_stock_number;
                $data_stock[$spare->spare_part_id]['dauki']['price'] = isset($data_stock[$spare->spare_part_id]['dauki']['price'])?$data_stock[$spare->spare_part_id]['dauki']['price']+($im->spare_stock_number*$im->spare_stock_price):($im->spare_stock_number*$im->spare_stock_price);
            }

            $join_ex = array('table'=>'export_stock','where'=>'export_stock = export_stock_id');
            $data_ex = array(
                'where' => 'spare_part = '.$spare->spare_part_id.' AND export_stock_date < '.strtotime($batdau),
            );
            $stock_exs = $spare_stock_model->getAllStock($data_ex,$join_ex);
            foreach ($stock_exs as $ex) {
                $data_stock[$spare->spare_part_id]['dauki']['number'] = isset($data_stock[$spare->spare_part_id]['dauki']['number'])?$data_stock[$spare->spare_part_id]['dauki']['number']-$ex->spare_stock_number:(0-$ex->spare_stock_number);
                $data_stock[$spare->spare_part_id]['dauki']['price'] = isset($data_stock[$spare->spare_part_id]['dauki']['price'])?$data_stock[$spare->spare_part_id]['dauki']['price']-($ex->spare_stock_number*$ex->spare_stock_price):0-($ex->spare_stock_number*$ex->spare_stock_price);
            }

        }
        

        $this->view->data['data_stock'] = $data_stock;

        

        

        $this->view->data['lastID'] = isset($spare_model->getLastStock()->spare_part_id)?$spare_model->getLastStock()->spare_part_id:0;
        
        $this->view->show('stock/index');
    }

   


}
?>