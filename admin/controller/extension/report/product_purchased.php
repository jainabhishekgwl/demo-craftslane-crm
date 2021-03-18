<?php
class ControllerExtensionReportProductPurchased extends Controller {
	public function index() {
		$this->load->language('extension/report/product_purchased');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('report_product_purchased', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/report/product_purchased', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/report/product_purchased', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true);

		if (isset($this->request->post['report_product_purchased_status'])) {
			$data['report_product_purchased_status'] = $this->request->post['report_product_purchased_status'];
		} else {
			$data['report_product_purchased_status'] = $this->config->get('report_product_purchased_status');
		}

		if (isset($this->request->post['report_product_purchased_sort_order'])) {
			$data['report_product_purchased_sort_order'] = $this->request->post['report_product_purchased_sort_order'];
		} else {
			$data['report_product_purchased_sort_order'] = $this->config->get('report_product_purchased_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/report/product_purchased_form', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/report/product_purchased')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
		
	public function report() {
		$this->load->language('extension/report/product_purchased');

		if (isset($this->request->get['filter_date_start'])) {
			$filter_date_start = $this->request->get['filter_date_start'];
		} else {
			$filter_date_start = '';
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filter_date_end = $this->request->get['filter_date_end'];
		} else {
			$filter_date_end = '';
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = 0;
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}
                
                 $data['customer_invoice'] = $this->url->link('extension/report/product_purchased/customer_invoice', 'user_token=' . $this->session->data['user_token'].'&filter_date_start='.$filter_date_start.'&filter_date_end='.$filter_date_end.'&filter_order_status_id='.$filter_order_status_id);

		$this->load->model('extension/report/product');

		$data['products'] = array();

		$filter_data = array(
			'filter_date_start'	     => $filter_date_start,
			'filter_date_end'	     => $filter_date_end,
			'filter_order_status_id' => $filter_order_status_id,
			'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                  => $this->config->get('config_limit_admin')
		);

		$product_total = $this->model_extension_report_product->getTotalPurchased($filter_data);

		$results = $this->model_extension_report_product->getPurchased($filter_data);

		foreach ($results as $result) {
			$data['products'][] = array(
				'name'     => $result['name'],
				'model'    => $result['model'],
				'quantity' => $result['quantity'],
				'total'    => $this->currency->format($result['total'], $this->config->get('config_currency'))
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$url = '';

		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('report/report', 'user_token=' . $this->session->data['user_token'] . '&code=product_purchased' . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_order_status_id'] = $filter_order_status_id;

		return $this->load->view('extension/report/product_purchased_info', $data);
	}
        
         public function customer_invoice() {
             
		$this->load->language('extension/report/product_purchased');

		if (isset($this->request->get['filter_date_start'])) {
			$filter_date_start = $this->request->get['filter_date_start'];
		} else {
			$filter_date_start = '';
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filter_date_end = $this->request->get['filter_date_end'];
		} else {
			$filter_date_end = '';
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = (int)$this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = 0;
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}


		$this->load->model('extension/report/product');

		$data['products'] = [];

		$filter_data = [
			'filter_date_start'	     => $filter_date_start,
			'filter_date_end'	     => $filter_date_end,
			'filter_order_status_id' => $filter_order_status_id,
			'start'                  => ($page - 1) * $this->config->get('config_pagination'),
			'limit'                  => $this->config->get('config_pagination')
		];

                if(isset($this->request->get['download']) && !empty($this->request->get['download'])){
                    $this->load->model('extension/report/sale');
                    $this->model_extension_report_sale->download($filter_data,'purchased_report','Purchased','purchased_report');
                }

		$product_total = $this->model_extension_report_product->getTotalPurchased($filter_data);

		$results = $this->model_extension_report_product->getInvoicePurchased($filter_data);
                
		foreach ($results as $result) {
                        $taxes = $this->model_extension_report_product->getTax($result['order_id']);
                        
                        $tax_IGST = '';
                        $tax_CGST = '';
                        $tax_SGST = '';
//                        echo '<pre>'; print_r($taxes); die;
                        foreach ($taxes as $tax){
                            if (strpos($tax['title'], 'CGST') !== false) {
                                $tax_CGST = str_replace('CGST', '', $tax['title']);
                            }else if(strpos($tax['title'], 'SGST') !== false){
                                $tax_SGST = str_replace('SGST', '', $tax['title']);
                            }else if(strpos($tax['title'], 'IGST') !== false){
                                $tax_IGST = str_replace('IGST', '', $tax['title']);
                            }
                        }
                        
                        $order_date = date_parse($result['date_added']);
                        $monthNum = sprintf("%02s", $order_date["month"]);
                        $monthName = date("M", strtotime($monthNum));
                        $invoice_date = date("d-M-y", strtotime($result['date_added']));
                        $year = $order_date["year"];
                        $customer_name = $result['firstname'].' '.$result['lastname'];
                        $company = $result['company'];
                        $gst = $result['gst'];
                        
                        if(!empty($result['payment_date'])){
                            $payment_date = date("d-M-y", strtotime($result['payment_date']));
                        }else{
                            $payment_date = '';
                        }
                        
                        
                        if($result['currency_code'] == 'INR'){
                            
                            $result['conversion_rate'] = '';
                            $transfer_currency_amount = '';
                            $base_usd = '';
                            $total_amount = $this->currency->format($result['total'], $this->config->get('config_currency'));
                            $cutoff_rate = '';
                            
                        }else{
                            
                            $transfer_currency_amount = $this->currency->format($result['total'], $this->config->get('config_currency'));
                            if(!empty($result['conversion_rate'])){
                                $total_amount = $result['total']*$result['conversion_rate'];
                            }else{
                                $total_amount = '';
                            }
                            
                            $cutoff_rate =  !empty($result['cutoff_rate'])?'-'.$result['cutoff_rate']:$result['cutoff_rate'];
                            $base_usd = $result['total']-$cutoff_rate;
                            $base_usd =  $this->currency->format($base_usd, $this->config->get('config_currency'));
                        }
                        
                        if($result['payment_method'] == 'Bank Transfer'){
                            $bank_name = 'HDFC';
                        }else{
                            $bank_name = '';
                        }
                        $acc_name = $this->config->get('config_name');
                    
			$data['products'][] = [
				'name'            => $result['name'],
                                'month'           => $monthName,
                                'year'            => $year,
                                'invoice_date'    => $invoice_date,
                                'invoice_no'      => $result['invoice_prefix'].$result['invoice_no'],
                                'customer_name'   => $customer_name,
                                'company'         => $company,
                                'gst'             => $gst,
                                'igst'            => !empty($tax_IGST)?$tax_IGST:'',
                                'cgst'            => !empty($tax_CGST)?$tax_CGST:'',
                                'sgst'            => !empty($tax_SGST)?$tax_SGST:'',
				'model'           => $result['model'],
				'quantity'        => $result['quantity'],
                                'currency'        => $result['currency_code'],
                                'exch_rate'       => $result['conversion_rate'],
			 	'transfer_total'  => $transfer_currency_amount,
                                'total'           => $total_amount,
                                'total_with_tax'  => $this->currency->format(($result['price']+$result['tax'])*$result['quantity'], $this->config->get('config_currency')),
                                'cutoff_rate'     => $cutoff_rate,
                                'base_usd'        => $base_usd,
                                'payment_date'    => $payment_date,
                                'part_of_inc'     => 'Yes',
                                'transaction'     => '',
                                'acc_name'        => $acc_name,
                                'bank_name'       => $bank_name,
                                'payment_method'  => $result['payment_method'],
			];
		}
                

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$url = '';

		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $product_total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination'),
			'url'   => $this->url->link('extension/report/product_purchased|report', 'user_token=' . $this->session->data['user_token'] . '&code=product_purchased' . $url . '&page={page}')
		]);

//		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_pagination')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination')) > ($product_total - $this->config->get('config_pagination'))) ? $product_total : ((($page - 1) * $this->config->get('config_pagination')) + $this->config->get('config_pagination')), $product_total, ceil($product_total / $this->config->get('config_pagination')));

		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_order_status_id'] = $filter_order_status_id;

		$this->response->setOutput($this->load->view('extension/report/customer_invoice', $data));
	}
}