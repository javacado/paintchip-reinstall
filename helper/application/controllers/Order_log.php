<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order_log extends CI_Controller {
	function __construct() {

		parent::__construct();
			$this->load->model('wp_auth_model');
	$this->base_url = "http://paintchip.local/";
		$this->local_image_path = "2020/06/";
		$this->img_dir = "wp-content/uploads/" . $this->local_image_path;
		$this->temp_img_dir = $_SERVER['DOCUMENT_ROOT'] . "/helper/uploads/";
		$this->prod_img_dir = $_SERVER['DOCUMENT_ROOT'] . "/" . $this->img_dir;
		if (is_dir("/var/www/html/paintchip")) {
			$this->base_url = "https://thepaint-chip.com/";

		}
		$this->load->library('AdvancedHtmlBase');

		$this->load->helper('cookie');
		$this->load->helper('file');
		parse_str($_SERVER['QUERY_STRING'], $this->get);

	}

	function getOrderLogData($post_id) {

		$q = "select p.*, l.* from wp_posts p left join wp_order_log l on l.post_id=p.ID  where   ID=$post_id";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();
		$log = array();
		foreach ($r as $row) {
			$row->prod_data = json_decode($row->prod_data, true);

			$q = "select * from wp_postmeta where post_id={$row->ID}";
			$rq = $this->db->query($q);
			$meta = $rq->result();
			$rq->free_result();
			$m = new stdClass();
			foreach ($meta as $v) {
				$m->{$v->meta_key} = $v->meta_value;
			}
			$row->meta = $m;

			// get products ordered
			$q = "select * from wp_woocommerce_order_items where order_item_type='line_item' and  order_id={$row->ID}";
			$rq = $this->db->query($q);
			$products = $rq->result();
			$prod = array();
			foreach ($products as $pp) {
				
				$q = "select * from wp_woocommerce_order_itemmeta where order_item_id={$pp->order_item_id} and meta_key='_product_id'";
				$rq = $this->db->query($q);
				$pid = $rq->row();
				$rq->free_result();

				$pp->product_id = $pid->meta_value;


				$q ="SELECT * FROM `wp_postmeta`  where meta_key='_sku' and post_id={$pp->product_id}";
				$rq = $this->db->query($q);
				$psku = $rq->row();
				$rq->free_result();
				$pp->sku = $psku->meta_value;




				$prod[] = $pp;
			}

			$row->products = $prod;

		}

		if ($row) {
			$this->load->view('inside-order-log', array('row' => $row));
		} else {
			echo "";

		}

	}

	function index($completed = 0) {
		$waiting = 0;
		if ($completed == 'waiting') {
			$waiting = 1;
		}

		$status = $completed == 0 ? "wc-processing" : "wc-completed";
		$q = "select p.*, l.* from wp_posts p left join wp_order_log l on l.post_id=p.ID  where   post_type='shop_order' and post_status='$status'";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();
		$log = array();
		foreach ($r as $row) {
			$row->prod_data = json_decode($row->prod_data, true);

			$q = "select * from wp_postmeta where post_id={$row->ID}";
			$rq = $this->db->query($q);
			$meta = $rq->result();
			$rq->free_result();
			$m = new stdClass();
			foreach ($meta as $v) {
				$m->{$v->meta_key} = $v->meta_value;
			}
			$row->meta = $m;

			// get products ordered
			$q = "select * from wp_woocommerce_order_items where order_item_type='line_item' and  order_id={$row->ID}";
			$rq = $this->db->query($q);
			$products = $rq->result();
			$prod = array();
			foreach ($products as $pp) {

				$q = "select * from wp_woocommerce_order_itemmeta where order_item_id={$pp->order_item_id} and meta_key='_product_id'";
				$rq = $this->db->query($q);
				$pid = $rq->row();
				$rq->free_result();

				$pp->product_id = $pid->meta_value;


				$q ="SELECT * FROM `wp_postmeta`  where meta_key='_sku' and post_id={$pp->product_id}";
				//echo "\n\n<!-- $q -->";
				$rq = $this->db->query($q);
				$psku = $rq->row();
				$rq->free_result();
				$pp->sku = $psku->meta_value;




				$prod[] = $pp;
			}

			$row->products = $prod;

			$log[] = $row;
		}
		$this->load->view('order-log', array('log' => $log, 'completed' => $completed));
	}

	function saveLogData() {
		$p = $_POST['prod_data'];
		$pd = array();
		foreach ($p as $pp) {
			$pd[$pp['prod_id']] = array('in_store' => $pp['in_store']);
		}
		$prod_data = json_encode($pd);
		$in = array(
			'post_id' => $_POST['post_id'],
			'employee' => $_POST['employee'],
			'picked_up' => $_POST['picked_up'],
			'notes' => $_POST['notes'],
			'prod_data' => $prod_data,
		);
// test for it
		$q = "select * from wp_order_log where post_id=" . $_POST['post_id'];
		$rq = $this->db->query($q);
		$exists = $rq->result();
		$rq->free_result();
		if ($exists && count($exists) > 0) {
			$exists = $exists[0];
			$log_id = $exists->log_id;
			$this->db->update('wp_order_log', $in, array('log_id' => $log_id));
		} else {
			$this->db->insert('wp_order_log', $in);

		}

	}

}