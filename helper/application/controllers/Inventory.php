<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inventory extends CI_Controller
{
	function __construct()
	{

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

		$this->load->helper(array('form', 'url'));
		$this->load->helper('cookie');
		$this->load->helper('file');
		parse_str($_SERVER['QUERY_STRING'], $this->get);
	}

	function index($dta=null)
	{


		$ok = true;
		if ($_POST) {
			if (isset($_POST['pphrase']) && $_POST['pphrase'] == 'stasia') {
				set_cookie("is_admin", "1", 99999999);
				$ok = 1;
			} else {
				die("Incorrect. Click the back button and try again");
			}
		}

		$data = array("ok" => $ok);
if ($dta) $data = array_merge($data, $dta);


		$q = "select * from jt_inv_holder order by date_created desc, complete asc ";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();
		$data['data'] = $r;
		$this->load->view('inventory-index', $data);
	}
	function parseData($theid)
	{

		$q="select * from jt_inv_holder where id=$theid";
		$therow=$this->db->query($q)->row();
		$file = $therow->file;
		$nothave = array();
		$csv =  $file;
		if (strpos($file, '/tmp/') === false) {
			die('incorrect file');
		}
		$handle = fopen($csv, "r");
		$octr = 0;
		$ct = 0;
		$prods = array();
		$firstignores = array(" ", "Vend", "Prod");


		$q = "select post_id, meta_value from wp_postmeta where meta_key='_sku'";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();

		$skus = array();
		foreach ($r as $row) {
			$skus[$row->meta_value] = $row->post_id;
		}
 
		while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
		{
			$first = false;




			$parts = preg_split('/  +/', $row[0]);
			if (strpos($row[0], 'VA10105') !== false) {
				//die("<h3>Output</h3><pre>".print_r($parts,1)."</pre>");

			}

			//echo ("<h3>".count($parts)."</h3><pre>" . print_r($parts, 1) . "</pre>");
			$numparts = count($parts);
			$first = $second = false;
			if ($numparts == 8 || $numparts == 9) {
				$prod = array();
				$first = true;

				$test = trim($parts[0]);
				if ($test != "SS" && $test != "MA") {
					// echo "<P>cont FROM...   $test</P>";
					continue;
				}
			} else if ($numparts == 11 || $numparts == 12 || $numparts == 13) {
				$second = true;
				if (!isset($prod['title'])) {
					//echo "<P>continueing $octr</P>";
					continue;
				}
			} else {
				continue;
			}



			//if ($octr > 80) break;
			$id = $sku = $title = $price = '';
			if ($first) {
				$prod['supplier'] = $parts[0] == "SS" ? "SLS" : "MAC";
				$testit = trim($parts[0]);
				if ($testit != "SS" && $testit != "MA") {
					echo ("<h3>OutputNOT</h3><pre>" . print_r($parts, 1) . "</pre>");
				}
				$prod['title'] = ucwords(strtolower($parts[2]));
				$prod['carries'] = intval($parts[5]) != 0;
				//	echo("<h3>Output</h3><pre>".print_r($parts,1)."</pre>");
				if ($prod['title'] == '0' || $prod['title'] == '1') {
					$t = explode(" ", $parts[1]);
					unset($t[0]);
					$t = implode(" ", $t);
					$t = ucwords(strtolower($t));
					$prod['title'] = $t;
				}

				if ($prod['title'] == '0' || $prod['title'] == '1') {
					//die("<h3>Output</h3><pre>" . print_r($parts, 1) . "</pre>");

				}
			} else if ($second) {
				$die = 0;
				//die("<h3>Output</h3><pre>".print_r($parts,1)."</pre>");

				$prod['sku'] = $parts[2];
				preg_match_all('!\d+\.*\d*!', $parts[5], $matches);
				@$prod['price'] = $matches[0][0]; //preg_replace("/[^A-Za-z ]/", '', $parts[5]);
				$carries = $prod['carries']; //


				//echo("<h3>Output</h3><pre>".print_r($parts,1)."</pre>");

				$prod['qoh'] = $parts[9];


				/* 
				

				if ($subarr[10] == "disc") {
					echo "<P>SKIPPING";
					$ct = 0;
					continue;
				}
 */
				/* $prod['sku'] = $subarr[1];
				$prod['upc'] = $subarr[2];
				$prod['qoh'] = $subarr[9]; */
				if (!$carries && intval($prod['qoh']) == 0) {
					continue;
				}
				if (!isset($skus[$prod['sku']])) {
					$nothave[] = $prod;
					continue;
				}
				@$prod['post_id'] = $skus[$prod['sku']];
				$prods[] = $prod;
			}


			if ($ct == 1) {
				//echo "\n" . $this->db->insert_string("jt_mac_data", $prod) . ";";
				//$prods[] = $prod;
			}
			$ct = $ct == 0 ? 1 : 0;
			$octr++;

			//print_r($row); //rows in array

			//here you can manipulate the values by accessing the array

		}
		//if ($test == 1) die("<h3>Output</h3><pre>" . print_r($prods, 1) . "</pre>");


		
		$i = array('date_created' => date("Y-m-d H:i:s"), 'data' => json_encode($prods));
		$this->db->update('jt_inv_holder', $i, array("id" => $theid));
		//echo json_encode(array('status' => 'ok', 'message' => count($prods) . " products uploaded"));
 $this->index(array('parsed' => $theid ));
		//echo "<h3>".count($nothave)." Missing Items</h3>";
		//foreach($nothave as $n) {
		//	echo "<br> ". $n['supplier'] . " - " . $n['title']. " SKU: ".$n['sku']." / $" . $n['price'] . " (q: ".$n['qoh'].")";
		//}
	}






	function sku_not_in()
	{



		$q = "select * from jt_inv_holder where complete = 0 order by date_created desc";
		$rq = $this->db->query($q);
		if ($rq->num_rows() == 0) {
			die(json_encode(array('error' => 'no data found')));
		}
		$r = $rq->row();
		$curexec = json_decode($r->exec);
		$incoming = array();
		foreach ($curexec as $p) {
			$incoming[] = $p->sku;
		}

		$not_here = array();

		$q = "SELECT * FROM `wp_postmeta`  where meta_key='_sku' and meta_value!=''";
		$rq = $this->db->query($q);
		$psku = $rq->result();
		$rq->free_result();
		$dbskus = array();
		foreach ($psku as $p) {
			$dbskus[] = $p->meta_value;
			if (!in_array($p->meta_value, $incoming)) {
				$not_here[] = $p->meta_value;
			}
		}



		echo ("dbskus:" . count($dbskus) . " /// " . " incoming:" . count($incoming) . " /// " . " not_here:" . count($not_here));
		die("<h3>Output</h3><pre>" . implode($not_here, ",") . "</pre>");
	}


	function checkInventory($id)
	{


		$show_missing = true;


		$q = "select * from jt_inv_holder where id=$id";
		$rq = $this->db->query($q);
		if ($rq->num_rows() == 0) {
			die(json_encode(array('error' => 'no data found')));
		}
		$r = $rq->row();
		$curexec = json_decode($r->exec);
		if (!$r->exec) {
			$curexec = array();
		}

		$curerrors = json_decode($r->errors);
		if (!$r->errors) {
			$curerrors = array();
		}

		$invID = $r->id;
		$last_num = $r->last_num;
		$rq->free_result();
		$data = json_decode($r->data);
		$errors = array();
		$curprice = array();
		$exec = array();
		$len = 15500;

		//if (count($data) <= $last_num) {
			$u = array('ready' => 1);
			$this->db->update('jt_inv_holder', $u, array("id" => $invID));
			//die(json_encode(array('status' => 'complete')));
		//}

		$newdata = $data;///rray_slice($data, $last_num, $len);
		//echo ("<h3>Output</h3><pre>" . print_r($newdata, 1) . "</pre>");

		$postids = array();
		foreach ($newdata as $d) {
			if (!$d->post_id || $d->post_id == '') {
				$errors[] = 'Sku: ' . $d->sku . ' was not in the database';
				continue;
			}
			$postids[] = $d->post_id;
		}
$out='';

		if (count($postids) > 0) {
			$postids = implode(",", $postids);

			// get all inventory quantity and price in memory...

			$q = "select post_id, meta_key, meta_value from wp_postmeta where (meta_key='_stock' or meta_key='_price' ) and post_id in ($postids)";
			$rq = $this->db->query($q);
			$r = $rq->result();
			$rq->free_result();
			$curstock = array();
			foreach ($r as $row) {
				if ($row->meta_key == '_price') {
					if (isset($curprice[$row->post_id])) {
						die('<p>price already set for ' . $row->post_id);
					}
					$curprice[$row->post_id] = $row->meta_value;
				} else {
					if (isset($curstock[$row->post_id])) {
						die('<p>stock already set for ' . $row->post_id);
					}
					$curstock[$row->post_id] = $row->meta_value;
				}
			}



			$q = "select ID, post_title from wp_posts where ID in ($postids)";
			$rq = $this->db->query($q);
			$r = $rq->result();
			$rq->free_result();
			$titles = array();
			foreach ($r as $row) {
				$titles[$row->ID] = $row->post_title;
			}



			foreach ($newdata as $d) {
				$curq = 0;
				if (isset($curstock[$d->post_id])) {
					$curq = $curstock[$d->post_id];
				}
				$curp = 0;

				if (isset($curprice[$d->post_id])) {
					$curp = $curprice[$d->post_id];
				}
				if (!$curp) {
					die('<p>no price for ' . $curp);
				}

				$d->curp = $curp;
				$d->curq = $curq;
				$d->title = $titles[$d->post_id];
				$exec[] = $d;
				if ($curp != $d->price || $curq != $d->qoh) {
					$out.= '<div class="resi">Item # ' . $d->sku;
				if ($curp != $d->price) {
					$diff = (floatval($d->price) - floatval($curp));
					
					if (abs($diff) > 5) {
						$diff = "<b style='color:#c00'>" . $diff . "</b>";
					}
					$out.= '<div class="smi">Updating price from $' . $curp  . ' to $' . $d->price . ' <br ><i>($$ diff: ' . $diff . ')</i></div> ';
					//continue;
				}


				if ($curq != $d->qoh) {
					//$curq = 0;
					$out .='<div class="smi">Updating quantity from '.$curq.' to <strong>'.$d->qoh.'</strong></div>';
				} else {
					$errors[] = 'The item: ' . $d->sku . ' did not change currently set to ' . $curq;
					//continue;
				}
				$out.="</div>";
			}


			}

	 
		} 
		
		if (!$out) {
			$out='<h4 class="text-danger">Completed</h4>';
			$out .="<p>No items needed to by updated...</p>";
			$da = date("Y-m-d H:i:s");

			$q = "update jt_inv_holder set approved_by='-', complete=1, date_approved='$da' where id=$id";
			$this->db->query($q);
			$xtra='done';
		} else {
			$out='<h4 class="text-danger">Items to be updated</h4>' . $out;
$xtra='';
	

		$curerrors = array_merge($curerrors, $errors);
		$curexec = array_merge($curexec, $exec);
		if ($show_missing) {

			foreach ($curerrors as $n) {
			}


			//die('doneeee');
			//die("<h3>Output</h3><pre>".print_r(,1)."</pre>");

		}
		//die("<h3>Output</h3><pre>Exec:".print_r(count($curexec),1)."  /// Ertrros:  ".print_r(count($curerrors),1)." </pre>");
		$u = array('last_num' => ($last_num + $len), 'errors' => json_encode($curerrors), 'exec' => json_encode($curexec));

		$this->db->update('jt_inv_holder', $u, array("id" => $invID));
	}
		echo json_encode(array('out' => $out,'xtra' => $xtra));
	}


	// UPDATE `jt_inv_holder` SET `exec` = '', `errors` = '', `last_num` = '0' WHERE `jt_inv_holder`.`id` = 1;





	function getUpdateData()
	{
		$q = "select * from jt_inv_holder where complete = 0 order by date_created desc limit 1";
		$rq = $this->db->query($q);
		if ($rq->num_rows() == 0) {
			die(json_encode(array('error' => 'no data found')));
		}
		$r = $rq->row();
		die(json_encode(array('id' => $r->id, 'exec' => $r->exec)));
	}

	function initInv($go = 0)
	{
		// get post ids
		$q = "SELECT * FROM `wp_postmeta`  where meta_key='_sku' and meta_value!=''";
		$rq = $this->db->query($q);
		$psku = $rq->result();
		$rq->free_result();
		$postids = array();
		foreach ($psku as $p) {
			$postids[] = $p->post_id;
		}
		echo "<p>" . count($postids) . " products to update</p>";
		$postids  = implode(",", $postids);



		$q = "update wp_postmeta set meta_value='yes' where meta_key='_manage_stock' and post_id in (" . $postids . ')';
		if (!$go) {
			echo "<P>$q</P>";
		} else {
			$uuup = $this->db->query($q);
			if (!$uuup) {
				die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
			}
		}



		$q = "update wp_postmeta set meta_value='0' where meta_key='_stock' and post_id in (" . $postids . ')';
		if (!$go) {
			echo "<P>$q</P>";
		} else {
			$uuup = $this->db->query($q);
			if (!$uuup) {
				die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
			}
		}


		$q = "update wp_postmeta set meta_value='notify' where meta_key='_backorders' and post_id in (" . $postids . ')';
		if (!$go) {
			echo "<P>$q</P>";
		} else {
			$uuup = $this->db->query($q);
			if (!$uuup) {
				die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
			}
		}
	}





function checkPriceReg() {
	$q="select * from `wp_postmeta` where meta_key='_price'";
	$rq=$this->db->query($q);
	$r = $rq->result();
	$rq->free_result();

	foreach ($r as $row) {
		$q="select * from `wp_postmeta` where meta_key='_regular_price' and post_id=". $row->post_id;
	$rqp=$this->db->query($q);
	if ($rqp->num_rows() == 0) continue;
	$reg = $rqp->row();
	$rqp->free_result();
	if ($reg->meta_value != $row->meta_value) {
		//echo "<P><i>_price: ".$row->meta_value." // _regular_price: ".$reg->meta_value." for POST ID: ".$reg->post_id."</i></P>";
		$qq = "update wp_postmeta set meta_value=" . $row->meta_value ." where meta_key='_regular_price' and meta_id=". $row->meta_id;
		$this->db->query($qq);
		//echo "<P>$qq</P><hr>";
	}

	}

}





	function applyInventory($id, $go = 0)
	{
		ini_set("memory_limit", "500M");

		$name = $this->get['name'];
		// first set manage stock and stock #'s 0 and backorders to 'notify' 
		$q = "SELECT * FROM `jt_inv_holder`  where id=$id";
		$rq = $this->db->query($q);
		$r = $rq->row();
		$rq->free_result();
		$exec = json_decode($r->exec);
		foreach ($exec as $row) {
			$up = array("meta_value" => $row->price);
			$uuup = $this->db->update_string("wp_postmeta", $up, array("meta_key" => "_price", "post_id" => $row->post_id));
			if (!$go) {
				echo ("<P>" . $uuup);
			} else {
				$done = $this->db->query($uuup);
				if (!$done) {
					die("<h3>Output</h3><pre>".print_r($uuup,1)."</pre>");
					
				}
			}

			$uuup = $this->db->update_string("wp_postmeta", $up, array("meta_key" => "_regular_price", "post_id" => $row->post_id));
			if (!$go) {
				echo ("<P>" . $uuup);
			} else {
				$done = $this->db->query($uuup);
				if (!$done) {
					die("<h3>Output</h3><pre>".print_r($uuup,1)."</pre>");
					
				}
			}




			$up = array("meta_value" => $row->qoh);
			$uuup = $this->db->update_string("wp_postmeta", $up, array("meta_key" => "_stock", "post_id" => $row->post_id));
			if (!$go) {
				echo ("<P>" . $uuup);
			} else {
				$done = $this->db->query($uuup);
				if (!$done) {
					die("<h3>Output</h3><pre>".print_r($uuup,1)."</pre>");
					
				}
			}
		}


			$da = date("Y-m-d H:i:s");
			$name = preg_replace("/[^A-Za-z0-9 ]/", '', $name);
			$q = "update jt_inv_holder set approved_by='$name', complete=1, date_approved='$da' where id=$id";
		if ($go) {
			$this->db->query($q);
		}else {
			echo "<P>$q</P>";
		}

		echo json_encode(array('status' => 'ok')) ;
	}

	function rununknowns($start)
	{
		die("--");
		$unknowns = null;//$this->getit();

		$sku = $unknowns[$start];
		$start++;



		$url = "https://www.macphersonart.com/cgi-bin/maclive/wam_tmpl/catalog_browse.p?site=MAC&layout=Responsive&page=catalog_browse&searchText=" . $sku;
		$res = array();
		$options = array(
			CURLOPT_RETURNTRANSFER => true, // return web page
			CURLOPT_HEADER => false, // do not return headers
			CURLOPT_FOLLOWLOCATION => true, // follow redirects
			CURLOPT_USERAGENT => "spider", // who am i
			CURLOPT_AUTOREFERER => true, // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
			CURLOPT_TIMEOUT => 120, // timeout on response
			CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
		);
		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		$content = curl_exec($ch);
		$err = curl_errno($ch);
		$errmsg = curl_error($ch);
		$header = curl_getinfo($ch);
		curl_close($ch);

		$machad = true;
		$res['content'] = $content;
		$res['content'] = strip_tags($content, "<body>");
		$res['url'] = $header['url'];

		$newurl = str_replace('document.location.replace("', "", $res['content']);
		$newurl = str_replace('");', "", $newurl);

		if (!$newurl || strpos($newurl, "Catalog Browse | MacPherson's") != FALSE) {
			$item['data'] = "NO URL";
			//$this->db->update("jt_mac_data", $item, array("id" => $row->id));
			$machad = false;
			$avail = false;
			//die("<h3>Output</h3><pre>" . print_r("no url", 1) . "</pre>");

		} else {
			$html = file_get_html($newurl);

			$h1s = $html->find('h1');
			$avail = true;
			foreach ($h1s as $h1) {
				if (strtolower(trim($h1->innerText)) == 'product not found') {
					$avail = false;
				}
			}
		}


		if ($start > count($unknowns)) {
			$a = 'done';
		} else {
			$a = $start + 1;
		}


		if (!$avail) {
			//get post id -set to draft

			$q = "select post_id from wp_postmeta where meta_key='_sku' and meta_value='$sku'";
			$rq = $this->db->query($q);
			$r = $rq->row();
			$rq->free_result();
			$post_id = $r->post_id;
			$u = array('post_status' => 'draft');
			$this->db->update('wp_posts', $u, array("ID" => $post_id));
		}

		echo json_encode(array('avail' => $avail, 'machad' => $machad,  'sku' => $sku,  'url' => $url, 'next' => $a));
	}






	public function uploadFile()
	{
			$config['upload_path']          = '/tmp/';
			$config['allowed_types']        = 'txt|csv';
			$config['max_size']             = 5048;
			
 
			$this->load->library('upload', $config);

			if ( ! $this->upload->do_upload('invfile'))
			{
					$error = array('error' => $this->upload->display_errors());

					die("<h1>Ooops</h1>".$this->upload->display_errors()." <p>Click the back button and try again.</p>");
					
			}
			else
			{

				$u = $this->upload->data();
				$q = "update jt_inv_holder set complete =1 where id>0";
				$this->db->query($q);
				$uu=array('file'=>$u['full_path']);
				$this->db->insert('jt_inv_holder', $uu);
				?>
<html><head>
<script>
window.location="/helper/inventory/parseData/<?php echo $this->db->insert_id()?>"
</script></head></html>
				<?php
die();				
					
			}
	}














	function getOrderLogData($post_id)
	{

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


				$q = "SELECT * FROM `wp_postmeta`  where meta_key='_sku' and post_id={$pp->product_id}";
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





	function index2($completed = 0)
	{
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


				$q = "SELECT * FROM `wp_postmeta`  where meta_key='_sku' and post_id={$pp->product_id}";
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

	function saveLogData()
	{
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
