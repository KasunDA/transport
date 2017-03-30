<?php
Class sparepartController Extends baseController {
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
        

        $spare_model = $this->model->get('sparepartModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        
        $tongsodong = count($place_model->getAllPlace());
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
        
        $this->view->data['spares'] = $spare_model->getAllStock($data);

        $this->view->data['lastID'] = isset($spare_model->getLastStock()->spare_part_id)?$spare_model->getLastStock()->spare_part_id:0;
        
        $this->view->show('sparepart/index');
    }

    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 6 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            $spare = $this->model->get('sparepartModel');
            $data = array(
                        
                        'spare_part_name' => trim($_POST['spare_part_name']),

                        'spare_part_code' => trim($_POST['spare_part_code']),

                        'spare_part_seri' => trim($_POST['spare_part_seri']),

                        'spare_part_brand' => trim($_POST['spare_part_brand']),

                        'spare_part_date_manufacture' => strtotime($_POST['spare_part_date_manufacture']),
                        );

            $spare_sub_model = $this->model->get('sparesubModel');

            $contributor = "";
            if(trim($_POST['spare_sub']) != ""){
                $support = explode(',', trim($_POST['spare_sub']));

                if ($support) {
                    foreach ($support as $key) {
                        $name = $spare_sub_model->getStockByWhere(array('spare_sub_name'=>trim($key)));
                        if ($name) {
                            if ($contributor == "")
                                $contributor .= $name->spare_sub_id;
                            else
                                $contributor .= ','.$name->spare_sub_id;
                        }
                        else{
                            $spare_sub_model->createStock(array('spare_sub_name'=>trim($key)));
                            if ($contributor == "")
                                $contributor .= $spare_sub_model->getLastStock()->spare_sub_id;
                            else
                                $contributor .= ','.$spare_sub_model->getLastStock()->spare_sub_id;
                        }
                        
                    }
                }

            }
            $data['spare_part_type'] = $contributor;


            if ($_POST['yes'] != "") {

                if ($spare->checkStock($_POST['yes'],trim($_POST['spare_part_code']))) {
                    echo "Thông tin này đã tồn tại";
                    return false;
                }
                else{
                    $spare->updateStock($data,array('spare_part_id' => $_POST['yes']));

                    /*Log*/
                    /**/
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|spare_part|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                    
                
                
            }
            else{

                if ($spare->getStockByWhere(array('spare_part_name'=>$data['spare_part_name']))) {
                    echo "Thông tin này đã tồn tại";
                    return false;
                }
                else if ($spare->getStockByWhere(array('spare_part_code'=>$data['spare_part_code']))) {
                    echo "Thông tin này đã tồn tại";
                    return false;
                }
                else{
                    $spare->createStock($data);

                    /*Log*/
                    /**/

                    echo "Thêm thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$spare->getLastStock()->spare_part_id."|spare_part|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }
                    
                
                
            }
                    
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
            $spare = $this->model->get('sparepartModel');
            
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    $spare->deleteStock($data);
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|spare_part|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                }

                /*Log*/
                    /**/

                return true;
            }
            else{
                /*Log*/
                    /**/
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|spare_part|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

                return $spare->deleteStock($_POST['data']);
            }
            
        }
    }

    public function getPlace($id){
        return $this->getByID($this->table,$id);
    }

    private function getUrl(){

    }


}
?>