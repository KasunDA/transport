<?php
Class receivableController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 3) {
            $this->view->data['disable_control'] = 1;
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Công nợ phải thu';

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
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'code';
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

        $this->view->data['batdau'] = $batdau;

        $this->view->data['ketthuc'] = $ketthuc;

        $this->view->data['vong'] = $vong;

        $this->view->data['trangthai'] = $trangthai;

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;


        $debit_model = $this->model->get('debitModel');
        $debit_pay_model = $this->model->get('debitpayModel');

        $data = array(
            'where'=>'check_debit = 1 AND debit_date < '.strtotime($batdau),
        );
        $debit_olds = $debit_model->getAllDebit($data);

        $data_old = array();
        foreach ($debit_olds as $debit) {
            $pay_money = 0;
            $pays = $debit_pay_model->getAllDebit(array('where'=>'debit = '.$debit->debit_id));
            foreach ($pays as $pay) {
                $pay_money += $pay->debit_pay_money;
            }

            if ($debit->customer > 0) {
                $data_old['customer'][$debit->customer] = isset($data_old['customer'][$debit->customer])?$data_old['customer'][$debit->customer]+($debit->money+$debit->money_vat_price-$pay_money):($debit->money+$debit->money_vat_price-$pay_money);
            }
            else if ($debit->staff > 0) {
                $data_old['staff'][$debit->staff] = isset($data_old['staff'][$debit->staff])?$data_old['staff'][$debit->staff]+($debit->money+$debit->money_vat_price-$pay_money):($debit->money+$debit->money_vat_price-$pay_money);
            }
            else if ($debit->steersman > 0) {
                $data_old['steersman'][$debit->steersman] = isset($data_old['steersman'][$debit->steersman])?$data_old['steersman'][$debit->steersman]+($debit->money+$debit->money_vat_price-$pay_money):($debit->money+$debit->money_vat_price-$pay_money);
            }
        }

        $this->view->data['data_old'] = $data_old;

        $data = array(
            'where'=>'check_debit = 1 AND debit_date >= '.strtotime($batdau).' AND debit_date < '.strtotime($ngayketthuc),
        );
        $debit_news = $debit_model->getAllDebit($data);

        $data_new = array();
        foreach ($debit_news as $debit) {
            $pay_money = 0;
            $pays = $debit_pay_model->getAllDebit(array('where'=>'debit = '.$debit->debit_id));
            foreach ($pays as $pay) {
                $pay_money += $pay->debit_pay_money;
            }

            if ($debit->customer > 0) {
                $data_new['customer'][$debit->customer]['no'] = isset($data_new['customer'][$debit->customer]['no'])?$data_new['customer'][$debit->customer]['no']+($debit->money+$debit->money_vat_price):($debit->money+$debit->money_vat_price);
                $data_new['customer'][$debit->customer]['co'] = isset($data_new['customer'][$debit->customer]['co'])?$data_new['customer'][$debit->customer]['co']+($pay_money):($pay_money);
            }
            else if ($debit->staff > 0) {
                $data_new['staff'][$debit->staff]['no'] = isset($data_new['staff'][$debit->staff]['no'])?$data_new['staff'][$debit->staff]['no']+($debit->money+$debit->money_vat_price):($debit->money+$debit->money_vat_price);
                $data_new['staff'][$debit->staff]['co'] = isset($data_new['staff'][$debit->staff]['co'])?$data_new['staff'][$debit->staff]['co']+($pay_money):($pay_money);
            }
            else if ($debit->steersman > 0) {
                $data_new['steersman'][$debit->steersman]['no'] = isset($data_new['steersman'][$debit->steersman]['no'])?$data_new['steersman'][$debit->steersman]['no']+($debit->money+$debit->money_vat_price):($debit->money+$debit->money_vat_price);
                $data_new['steersman'][$debit->steersman]['co'] = isset($data_new['steersman'][$debit->steersman]['co'])?$data_new['steersman'][$debit->steersman]['co']+($pay_money):($pay_money);
            }
        }

        $this->view->data['data_new'] = $data_new;


        $steersman_model = $this->model->get('steersmanModel');

        $steersmans = $steersman_model->getAllSteersman(array('order_by'=>'steersman_name ASC'));

        $this->view->data['steersmans'] = $steersmans;


        $staff_model = $this->model->get('staffModel');

        $staffs = $staff_model->getAllStaff(array('order_by'=>'staff_name ASC'));
        $this->view->data['staffs'] = $staffs;


        $customer_model = $this->model->get('customerModel');

        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name ASC'));

        $this->view->data['customers'] = $customers;
        
        $this->view->show('receivable/index');
    }

   


}
?>