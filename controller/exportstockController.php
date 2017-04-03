<?php
Class exportstockController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 6 && $_SESSION['role_logined'] != 8) {
            $this->view->data['disable_control'] = 1;
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Xuất kho';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'export_stock_date';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
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

        $export_model = $this->model->get('exportstockModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        $join = array('table'=>'user','where'=>'export_stock_user=user_id');

        $data = array(
            'where' => 'export_stock_date >= '.strtotime($batdau).' AND export_stock_date <= '.strtotime($ketthuc),
        );

        
        $tongsodong = count($export_model->getAllStock($data,$join));
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
            'where' => 'export_stock_date >= '.strtotime($batdau).' AND export_stock_date <= '.strtotime($ketthuc),
            );

       
        
        if ($keyword != '') {
            $search = ' AND ( export_stock_code LIKE "%'.$keyword.'%" 
                        OR username LIKE "%'.$keyword.'%" )';
            $data['where'] .= $search;
        }
        
        $this->view->data['exports'] = $export_model->getAllStock($data,$join);

        $this->view->data['lastID'] = isset($export_model->getLastStock()->export_stock_id)?$export_model->getLastStock()->export_stock_id:0;
        
        $this->view->show('exportstock/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 6 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $export_model = $this->model->get('exportstockModel');
            $stock_model = $this->model->get('sparestockModel');
            $spare_model = $this->model->get('sparepartModel');

            $data = array(
                        
                        'export_stock_code' => trim($_POST['export_stock_code']),
                        'export_stock_date' => strtotime($_POST['export_stock_date']),
                        'export_stock_user' => $_SESSION['userid_logined'],
                        );


            if ($_POST['yes'] != "") {
                if ($export_model->checkStock($_POST['yes'],trim($_POST['export_stock_code']))) {
                    echo "Thông tin này đã tồn tại";
                    return false;
                }
                else{
                    $export_model->updateStock($data,array('export_stock_id' => $_POST['yes']));
                    $id_export = $_POST['yes'];
                    /*Log*/
                    /**/
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|export_stock|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
                
                
            }
            else{

                if ($export_model->getStockByWhere(array('export_stock_code'=>$data['export_stock_code']))) {
                    echo "Thông tin này đã tồn tại";
                    return false;
                }
                else{
                    $export_model->createStock($data);
                    $id_export = $export_model->getLastStock()->export_stock_id;
                    /*Log*/
                    /**/

                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$export_model->getLastStock()->export_stock_id."|export_stock|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    }
                
                
            }

            $total_number = 0;
            $total_price = 0;

            $spare_part = $_POST['spare_part'];

            foreach ($spare_part as $v) {

                    if (isset($v['spare_part_id']) && $v['spare_part_id'] != "") {

                        $id_spare_part = $v['spare_part_id'];

                    }

                    else{

                        $data_spare_part = array(

                            'spare_part_name' => trim($v['spare_part_name']),

                            'spare_part_code' => trim($v['spare_part_code']),

                            'spare_part_seri' => trim($v['spare_part_seri']),

                            'spare_part_brand' => trim($v['spare_part_brand']),

                            'spare_part_date_manufacture' => strtotime($v['spare_part_date_manufacture']),

                        );

                        $spare_model->createStock($data_spare_part);

                        $id_spare_part = $spare_model->getLastStock()->spare_part_id;

                    }

                    $data_stock = array(

                        'export_stock' => $id_export,

                        'spare_part' => $id_spare_part,

                        'spare_stock_unit' => $v['spare_stock_unit'],

                        'spare_stock_number' => $v['spare_stock_number'],

                        'spare_stock_price' => trim(str_replace(',','',$v['spare_stock_price'])),
                        

                    );

                    if (!$stock_model->getStockByWhere(array('export_stock'=>$id_export,'spare_part'=>$id_spare_part))) {
                        $stock_model->createStock($data_stock);
                    }
                    else{
                        $id_stock = $stock_model->getStockByWhere(array('export_stock'=>$id_export,'spare_part'=>$id_spare_part))->spare_stock_id;
                        $stock_model->updateStock($data_stock,array('spare_stock_id'=>$id_stock));
                    }

                    $total_number += $data_stock['spare_stock_number'];
                    $total_price += $data_stock['spare_stock_price']*$data_stock['spare_stock_number'];
                }

                $export_model->updateStock(array('export_stock_total'=>$total_number,'export_stock_price'=>$total_price),array('export_stock_id'=>$id_export));
                    
        }
    }
    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 6 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $export_model = $this->model->get('exportstockModel');
            $stock_model = $this->model->get('sparestockModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $export_model->deleteStock($data);
                    $stock_model->query('DELETE FROM spare_stock WHERE export_stock = '.$data);
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|export_stock|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }

                /*Log*/
                    /**/

                return true;
            }
            else{
                $stock_model->query('DELETE FROM spare_stock WHERE export_stock = '.$_POST['data']);
                /*Log*/
                    /**/
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|export_stock|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $export_model->deleteStock($_POST['data']);
            }
            
        }
    }

    public function getSpare(){

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $spare_model = $this->model->get('sparepartModel');
            $spare_stock_model = $this->model->get('sparestockModel');

            if ($_POST['keyword'] == "*") {

                

                $list = $spare_model->getAllStock();

            }

            else{

                $data = array(

                'where'=>'( spare_part_name LIKE "%'.$_POST['keyword'].'%" )',

                );

                $list = $spare_model->getAllStock($data);

                if (!$list) {
                    $data = array(

                    'where'=>'( spare_part_code LIKE "%'.$_POST['keyword'].'%" )',

                    );

                    $list = $spare_model->getAllStock($data);
                }

            }

            

            foreach ($list as $rs) {

                // put in bold the written text
                

                $spare_name = '['.$rs->spare_part_code.']-'.$rs->spare_part_name;

                if ($_POST['keyword'] != "*") {

                    $spare_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', '['.$rs->spare_part_code.']-'.$rs->spare_part_name);

                }

                $stocks = $spare_stock_model->queryStock('SELECT * FROM spare_stock WHERE export_stock > 0 AND spare_part = '.$rs->spare_part_id.' ORDER BY spare_stock_id DESC LIMIT 1');
                if ($stocks) {
                    foreach ($stocks as $stock) {
                        echo '<li onclick="set_item_other(\''.$rs->spare_part_id.'\',\''.$rs->spare_part_name.'\',\''.$rs->spare_part_code.'\',\''.$rs->spare_part_seri.'\',\''.($rs->spare_part_date_manufacture>0?date('d-m-Y',$rs->spare_part_date_manufacture):null).'\',\''.$rs->spare_part_brand.'\',\''.$_POST['offset'].'\',\''.$stock->spare_stock_unit.'\',\''.$stock->spare_stock_price.'\')">'.$spare_name.'</li>';
                    }
                    
                }
                else{
                    echo '<li onclick="set_item_other(\''.$rs->spare_part_id.'\',\''.$rs->spare_part_name.'\',\''.$rs->spare_part_code.'\',\''.$rs->spare_part_seri.'\',\''.($rs->spare_part_date_manufacture>0?date('d-m-Y',$rs->spare_part_date_manufacture):null).'\',\''.$rs->spare_part_brand.'\',\''.$_POST['offset'].'\',\'\',\'\')">'.$spare_name.'</li>';
                }

                

            }

        }

    }
    public function deletespare(){

        if (isset($_POST['export_stock'])) {

            $spare_model = $this->model->get('sparestockModel');



            $spare_model->queryStock('DELETE FROM spare_stock WHERE export_stock = '.$_POST['export_stock'].' AND spare_part = '.$_POST['spare_part'].' AND spare_stock_price = '.$_POST['spare_stock_price'].' AND spare_stock_number = '.$_POST['spare_stock_number']);

            echo 'Đã xóa thành công';

        }

    }
    public function spare(){

        if(isset($_POST['export_stock'])){

            

            $spare_model = $this->model->get('sparestockModel');



            $join = array('table'=>'spare_part','where'=>'spare_stock.spare_part = spare_part.spare_part_id');

            $data = array(

                'where' => 'export_stock = '.$_POST['export_stock'],

            );

            $exports = $spare_model->getAllStock($data,$join);



            $str = "";

            if (!$exports) {

                $str .= '<tr class="'.$_POST['export_stock'].'">';

                $str .= '<td><input type="checkbox"  name="chk"></td>';

                $str .= '<td><table style="width: 100%">';

                $str .= '<tr class="'.$_POST['export_stock'] .'">';

                $str .= '<td>Tên sản phẩm</td>';

                $str .= '<td><input type="text" autocomplete="off" class="spare_part" name="spare_part[]" placeholder="Nhập tên hoặc * để chọn" >';

                $str .= '<ul class="name_list_id"></ul></td>';

                $str .= '<td>Mã sản phẩm</td>';

                $str .= '<td><input type="text" class="spare_part_code" name="spare_part_code[]"></td></tr>';

                $str .= '<tr><td>Nhà sản xuất</td>';

                $str .= '<td><input type="text" class="spare_part_brand" name="spare_part_brand[]"></td>';

                $str .= '<td>Số seri</td>';

                $str .= '<td><input type="text" class="spare_part_seri" name="spare_part_seri[]"></td></tr>';

                $str .= '<tr><td>Ngày sản xuất</td>';

                $str .= '<td><input type="text" class="spare_part_date_manufacture ngay" name="spare_part_date_manufacture[]"></td>';

                $str .= '<td>Đơn vị tính</td>';

                $str .= '<td><input type="text" class="spare_stock_unit" name="spare_stock_unit[]"></td></tr>';

                $str .= '<tr><td>Số lượng</td>';

                $str .= '<td><input type="text" class="spare_stock_number number" name="spare_stock_number[]"></td>';

                $str .= '<td>Đơn giá</td>';

                $str .= '<td><input type="text" class="spare_stock_price numbers" name="spare_stock_price[]"></td>';

                $str .= '</tr></table></td></tr>';

            }

            else{

                foreach ($exports as $v) {


                    $str .= '<tr class="'.$_POST['export_stock'].'">';

                    $str .= '<td><input type="checkbox" title="'.$v->spare_stock_number.'" alt="'.$v->spare_stock_price.'"  name="chk" tabindex="'.$v->spare_part.'" data="'.$v->export_stock.'" ></td>';

                    $str .= '<td><table style="width: 100%">';

                    $str .= '<tr class="'.$_POST['export_stock'] .'">';

                    $str .= '<td>Tên sản phẩm</td>';

                    $str .= '<td><input type="text" autocomplete="off" class="spare_part" name="spare_part[]" placeholder="Nhập tên hoặc * để chọn" value="'.$v->spare_part_name.'" data="'.$v->spare_part.'" >';

                    $str .= '<ul class="name_list_id"></ul></td>';

                    $str .= '<td>Mã sản phẩm</td>';

                    $str .= '<td><input type="text" class="spare_part_code" name="spare_part_code[]" value="'.$v->spare_part_code.'"></td></tr>';

                    $str .= '<tr><td>Nhà sản xuất</td>';

                    $str .= '<td><input type="text" class="spare_part_brand" name="spare_part_brand[]" value="'.$v->spare_part_brand.'"></td>';

                    $str .= '<td>Số seri</td>';

                    $str .= '<td><input type="text" class="spare_part_seri" name="spare_part_seri[]" value="'.$v->spare_part_seri.'"></td></tr>';

                    $str .= '<tr><td>Ngày sản xuất</td>';

                    $str .= '<td><input type="text" class="spare_part_date_manufacture ngay" name="spare_part_date_manufacture[]" value="'.($v->spare_part_date_manufacture>0?date('d-m-Y',$v->spare_part_date_manufacture):null).'"></td>';

                    $str .= '<td>Đơn vị tính</td>';

                    $str .= '<td><input type="text" class="spare_stock_unit" name="spare_stock_unit[]" value="'.$v->spare_stock_unit.'"></td></tr>';

                    $str .= '<tr><td>Số lượng</td>';

                    $str .= '<td><input type="text" class="spare_stock_number number" name="spare_stock_number[]" value="'.$v->spare_stock_number.'"></td>';

                    $str .= '<td>Đơn giá</td>';

                    $str .= '<td><input type="text" class="spare_stock_price numbers" name="spare_stock_price[]" value="'.$this->lib->formatMoney($v->spare_stock_price).'"></td>';

                    $str .= '</tr></table></td></tr>';

                }

            }



            echo $str;

        }

    }


}
?>