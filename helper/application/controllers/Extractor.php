<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
class Extractor extends CI_Controller {
	function __construct() {

		parent::__construct();
			$this->load->model('wp_auth_model');
	$this->base_url = "http://paintchip-helper.test/";
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

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 *      http://example.com/index.php/welcome
	 *  - or -
	 *      http://example.com/index.php/welcome/index
	 *  - or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */

	public function index() {

		$ok = get_cookie("is_admin") == 1;
		if ($_POST) {
			if (isset($_POST['pphrase']) && $_POST['pphrase'] == 'stasia') {
				set_cookie("is_admin", "1", 99999999);
				$ok = 1;
			} else {
				die("Incorrect. Click the back button and try again");
			}
		}

		$data = array("ok" => $ok);

		$this->load->view('extractor-index', $data);
	}

	function rpeg($go = 0) {
		$q = "select * from wp_posts where post_title like '%- peggable'";
		$r = $this->db->query($q)->result();
		foreach ($r as $row) {
			$newtitle = str_replace(" - Peggable", "", $row->post_title);
			$u = array("post_title" => $newtitle);
			$up = $this->db->update_string('wp_posts', $u, array("ID" => $row->ID));

			echo "<P>$up ;";
		}
	}

	function getsku()
	{
		$q = 'SELECT meta_value FROM `wp_postmeta` where meta_key="_sku"';
		$r = $this->db->query($q)->result();
		$s = [];
		foreach ($r as $row) {
			$s[] = $row->meta_value;
		}
		echo implode(",", $s);
	}

	function newstuff() {
		// grab the adjusted master list

		
$raw = 'testsku,TT180S,TT151S,VA10517,VA10516,VA10514,VA10510,VA10117,VA10116,VA10110,VA10108,VA10105,VA10103,RC8693356,RC8591007,DEDS1073,VA40502,VA40517,DC110310,DC110302,SG667022,RC1180146,RC1180010,RC1172067,DC110322,BS523008,BS080316,BS080313,WFHT323,WFAL57,JR400153,CHKSTARTG,BS523716,FLFRE0317,SA1815007,SA1815010,SLST,GP136BP,GP114BJ,PB5950RC,PB5950FC,RYSVP5,RYSVP1,RYRCADSTH18,RS255510001,RS255500010,RS255500006,RS255500004,RS255500002,RS255500001,RS255410001,RS255400009,RS255400007,RS255400006,RS255400005,RS255400003,RS255400002,RS255400001,RS255300008,PB9100,PB9148,PB9144,PB9143,PB9140,PB9137,PB9131,PB9130,PB9118,PB9117,PB9104,LC245B,JR210531,ABKW903,PB5450F3,PB5450F2,PB5450F1,RYZ63T8,RYZ63T6,RYZ63T4,RYZ63T2,RYZ63T12,RYZ63T10,RYZ63T1,RYZ63R8,RYZ63R6,RYZ63R4,RYZ63R2,RYZ63R12,RYZ63R1,RYZ63FB6,RYZ63FB4,RYZ63FB2,RYZ63F8,RYZ63F6,RYZ63F4,RYZ63F2,RYZ63F12,RYZ63F10,RYZ63F1,RYZ63B6,RYZ63B2,RYZ63B12,RYZ63B10,RYZ63B1,RYZ63A8,RYZ63A6,RYZ63A4,RYZ63A2,RYZ63A10,RS255145006,RS255144001,RS255144002,RS255144004,RS255144006,RS255144008,RS255142001,RS255142002,RS255142006,RS255142008,RS255142010,RS255141006,RS255141008,RS255141010,RS255141001,RS255141004,PB6500SFB4,PB6500R6,PB6500F12,RYZ73WO34,RYZ73WO12,RYZ73WO1,RYZ73W34,RYZ73W1,RYZ73TW14,RYZ73TW12,RYZ73TC38,RYZ73TC14,RYZ73TC12,RYZ73T8,RYZ73T6,RYZ73T4,RYZ73T2,RYZ73T12,RYZ73T10,RYZ73ST34,RYZ73ST12,RYZ73ST1,RYZ73SP50,RYZ73SP30,RYZ73SP200,RYZ73SP100,RYZ73SL200,RYZ73SL1,RYZ73S8,RYZ73S6,RYZ73S4,RYZ73S2,RYZ73S16,RYZ73S12,RYZ73S0,RYZ73R8,RYZ73R6,RYZ73R5,RYZ73R4,RYZ73R3,RYZ73R2,RYZ73R12,RYZ73R10,RYZ73R1,RYZ73R0,RYZ73L200,RYZ73L2,RYZ73L1,RYZ73FW14,RYZ73FW12,RYZ73FB6,RYZ73FB4,RYZ73FB2,RYZ73CB8,RYZ73CB6,RYZ73CB4,RYZ73C34,RYZ73C14,RYZ73C12,RYZ73A58,RYZ73A38,RYZ73A34,RYZ73A14,RYZ73A12,RYZ73A1,PB3950LR4,PB3950FG037,PB3950FB4,PB3950FB6,PB3950FB8,PB3950CB4,PB3950CB6,PB3950CB8,PB3950AS012,PB3750TS100,PB3750MSP100,PB3750MSP200,PB3750SP50,PB3750SL100,PB3750SL180,PB3750SL20,PB3750SC100,PB3750SC2,PB3750SC20,PB3750RB12,PB3750RB6,PB3750MR200,PB3750R0,PB3750R2,PB3750R3,PB3750R4,PB3750R6,PB3750R8,PB3750R1,PB3750R10,PB3750R12,PB3750PF8,PB3750PF6,PB3750PF4,PB3750PF2,PB3750MM200,PB3750L2,PB3750L6,PB3750G025,PB3750FW050,PB3750FS10,PB3750FS12,PB3750FS6,PB3750FN2,PB3750FG037,PB3750FG075,PB3750BF2,YOKWB15,YOKWB09,PLFRHMBP,RYBK618,BS053515,YOCC6,YOCC3,YOCC1,RYZ83WRSM,RYZ83WRMD,RYZ83WRLG,RYZ83W34,RYZ83W1,RYZ83ST34,RYZ83SC8,RYZ83SC6,RYZ83SC2,RYZ83SC10,RYZ83R8,RYZ83R6,RYZ83R4,RYZ83R2,RYZ83PO12,RYZ83MW12,RYZ83MW1,RYZ83MB34,RYZ83MB12,RYZ83L1,RYZ83G38,RYZ83G14,RYZ83A14,RYZ83A12,SM31012,SM310209,SM31016,TF10691,GXIAB1624,WN6224258,WN6224109,WN6224264,WN6224270,WN6224278,WN6224252,TF49115,TF49118,TF49121,TF6016,AMFTHIN780808W,AMFTHIN781114B,AMFTHIN781620B,AMFTHIN781620M,AMFTHIN781620W,AMFTHIN151114M,HU0010312,HU0010306,HU0010304,HU0010303,HU0010305,AMAMPSK,...,BS575064';
$items = array_map('trim', explode(',', $raw));

// Example use:
//print_r($items);

die($_SERVER['DOCUMENT_ROOT']);
		$csv = $_SERVER['DOCUMENT_ROOT'] . "/dta/pc-inventory/Inventory_Master_Processed_excluding_MAC.csv";
		$handle = fopen($csv, "r");
		$octr = 0;
		$ct = 0;
		$prods = array();
		while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
		{
			$first = false;

			$parts = preg_split('/  +/', $row[0]);
			//echo "<br>".$parts[4];
			if (!in_array(trim($parts[4]), $items)) {
				echo $row[0]."<br>";
			}
		}
		// remove items in this array

	}
	function emails() {
		$q = "select * from wp_oses_emails order by email_created desc";
		$r = $this->db->query($q);
		$rr = $r->result();
		$r->free_result();
		$data = array("emails" => $rr);
		$this->load->view('emails-viewer', $data);
	}

	function show_email($id) {
		$q = "select * from wp_oses_emails where email_id=$id";
		$r = $this->db->query($q);
		$rr = $r->row();
		$r->free_result();
		die($rr->email_message);
	}

	function getMac() {
		$csv = $_SERVER['DOCUMENT_ROOT'] . "/dta/inventory-mac.csv";
		$handle = fopen($csv, "r");
		$octr = 0;
		$ct = 0;
		$prods = array();
		while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
		{
			$first = false;

			$parts = preg_split('/  +/', $row[0]);
			//echo ("<h3>Output</h3><pre>" . print_r($parts, 1) . "</pre>");
			if ($ct == 0) {
				$prod = array();
				$first = true;
			}
			$id = $sku = $title = $price = '';
			if ($first) {
				$prod['title'] = ucwords(strtolower($parts[2]));

				if ($prod['title'] == '0' || $prod['title'] == '1') {
					$t = explode(" ", $parts[1]);
					unset($t[0]);
					$t = implode(" ", $t);
					$t = ucwords(strtolower($t));
					$prod['title'] = $t;
				}

				if ($prod['title'] == '0' || $prod['title'] == '1') {
					die("<h3>Output</h3><pre>" . print_r($parts, 1) . "</pre>");

				}
			} else {
				$die = 0;
				$subarr = array();
				foreach ($parts as $p) {
					if ($p == "019538 MVBD20071000 689076764506") {
						$die = 1;
					}
					if (strpos($p, " ") !== false) {
						$p = explode(" ", $p);
						foreach ($p as $pp) {
							$subarr[] = $pp;
						}
						//die("<h3>Output</h3><pre>" . print_r($p, 1) . "</pre>");
					} else {
						$subarr[] = $p;
					}

					//$subarr = array_merge($subarr, $p);
				}

				//if (count($subarr) != 12) {
				//	echo ("<h3>Output</h3><pre>" . print_r($subarr, 1) . "</pre>");
				//}

				if ($subarr[10] == "disc") {
					echo "<P>SKIPPING";
					$ct = 0;
					continue;
				}

				$prod['sku'] = $subarr[1];
				$prod['upc'] = $subarr[2];
				$prod['price'] = str_replace("$", "", $subarr[4]);
				$prod['supplier'] = "mac";

			}

			if ($ct == 1) {
				echo "\n" . $this->db->insert_string("jt_mac_data", $prod) . ";";
				//$prods[] = $prod;
			}
			$ct = $ct == 0 ? 1 : 0;
			$octr++;

			//print_r($row); //rows in array

			//here you can manipulate the values by accessing the array

		}

		//die("<h3>Output</h3><pre>" . print_r("TOTAL: $octr", 1) . "</pre>");

	}

	function getRelatedSLSCat($main_cat, $cat) {
		$newcat = $cat;

		return $newcat;
	}

	function getMacCats() {
		$r = $this->db->query('select * from jt_mac_data where data="DONE"')->result();

		$cats = array();
		foreach ($r as $row) {
			if (!isset($cats[$row->main_category])) {
				$cats[$row->main_category] = array();
			}

			if (!in_array($row->category, $cats[$row->main_category])) {
				$cats[$row->main_category][] = $row->category;
			}

		}

		foreach ($cats as $maincat => $subs) {
			foreach ($subs as $sub) {
				$i = array("o_cat" => $maincat, "o_subcat" => $sub);

				//$this->db->insert("jt_mac_cats", $i);
			}

		}

		/**/

		/*foreach ($r as $row) {
				if (!isset($cats[$row->main_category])) {
					$cats[$row->main_category] = array("related" => $this->getRelatedCat($row->main_category), "sub" => array());
				}

				if (!in_array($row->category, $cats[$row->main_category]["sub"])) {
					$cats[$row->main_category]['sub'][$row->category] = array("cat" => $row->category, "related" => $this->getRelatedCat($row->main_category, $row->category));
				}

			}
		*/
		return $cats;
		die("<h3>Output</h3><pre>" . print_r($cats, 1) . "</pre>");
	}

	function doMac() {

	}

	function assign() {
		$u = array("category" => $this->get['id']);
		$done = $this->db->update("jt_mac_cats", $u, array("o_subcat" => urldecode($this->get['cat'])));
		//die("<h3>Output</h3><pre>" . print_r($done, 1) . "</pre>");
		echo json_encode(array("ok" => $done));
	}
	function mackey() {
		$r = $this->db->query('select * from jt_mac_cats where category="40" order by o_cat asc')->result();

		$cats = array();
		$us = array();
		foreach ($r as $row) {
			if (in_array($row->o_cat, $us)) {
				continue;
			}

			$us[] = $row->o_cat;
		}

		foreach ($us as $item) {
			$r = $this->db->query('select * from jt_mac_cats where o_cat="' . $item . '" and  category="40" order by o_subcat asc')->result();

			foreach ($r as $row) {
				$cats[$item][] = $row->o_subcat;
				/*if (in_array($cats['p'.$row->o_subcat], $cats)) {
					continue;
				*/
			}

//			$us[] = $row->o_cat;
		}

		$data['keys'] = $cats;

		$data['cats'] = $this->getSCats();

		//die("<h3>Output</h3><pre>" . print_r($data['cats'], 1) . "</pre>");

		$this->load->view('assign-cats', $data);

	}

	function getSCats() {

		$q = "select * from wp_terms ";
		$t = $this->db->query($q)->result();
		$names = array();
		foreach ($t as $tt) {
			$names['p' . $tt->term_id] = $tt;

		}

		$q = "select * from wp_wc_category_lookup ";
		$r = $this->db->query($q)->result();
		$cats = array();
		foreach ($r as $row) {
			if (!isset($cats['p' . $row->category_tree_id])) {
				$subs = array(
					$names['p' . $row->category_id],
				);
				if ($row->category_id == $row->category_tree_id) {
					$subs = array();
				}

				$cats['p' . $row->category_tree_id] = array(
					"cat" => $names['p' . $row->category_tree_id],
					"subs" => $subs,
				);

			} else {

				$cats['p' . $row->category_tree_id]['subs'][] = $names['p' . $row->category_id];
			}
		}

		//die("<h3>Output</h3><pre>" . print_r($cats, 1) . "</pre>");
		return $cats;
	}

	function getRelatedCat($parent, $cat = "") {

		return "";

	}

	function updateimg() {

		$item = array(
			"product_post_id" => $this->input->post('product_post_id'),
			"image_post_id" => $this->input->post('image_post_id'),
			"_wp_attachment_metadata_id" => $this->input->post('_wp_attachment_metadata_id'),
		);

		$img_title = null;

		$pp = $this->db->query('select * from wp_posts where ID=' . $item['product_post_id'])->row();
		if ($pp) {
			$img_title = $pp->post_title;
		}
		$img = $this->input->post('newimg');
		$img = explode("?", $img);
		$img = $img[0];

		$iname = explode("/", $img);
		$iname = $iname[count($iname) - 1];

		if (!$img_title) {
			$img_title = $iname;
		}

		$ipostname = explode(".", $iname);
		$ext = strtolower($ipostname[count($ipostname) - 1]);
		$ipostname = $ipostname[0];

		$iloc = "2020/06/" . $iname;
		$dest = $_SERVER['DOCUMENT_ROOT'] . "/wp-content/uploads/" . $iloc;
		$this->getImage($img, $dest);

		if (!file_exists($dest)) {
			die(json_encode(array("msg" => "something went wrong... the image did not upload")));

			//$ct++;
			die("<h3>Output</h3><pre>" . print_r($content, 1) . "</pre>");
		}
		$put[] = "downloaded: $iloc";

		$gurl = "https://thepaint-chip.com/wp-content/uploads/" . $iloc;
		$mime = "";
		if ($ext == "jpg" || $ext == "jpeg") {
			$mime = "image/jpeg";
		} else if ($ext == "png") {
			$mime = "image/png";

		} else if ($ext == "gif") {
			$mime = "image/gif";

		}

		@$sz = getimagesize($dest);

		if (!$sz) {
			die(json_encode(array("msg" => "something went wrong... we can't get the image size")));

		}

		$img_meta = array(

			"width" => $sz[0],
			"height" => $sz[1],
			"file" => $iloc,
			"sizes" => Array
			(
			),

			"image_meta" => Array
			(
				"aperture" => 0,
				"credit" => "",
				"camera" => "",
				"caption" => "",
				"created_timestamp" => 0,
				"copyright" => "",
				"focal_length" => 0,
				"iso" => 0,
				"shutter_speed" => 0,
				"title" => $img_title,
				"orientation" => 0,
				"keywords" => Array
				(
				),

			),
		);
		$img_meta = serialize($img_meta);

		$up = array("meta_value" => $img_meta);
		$uuup = $this->db->update("wp_postmeta", $up, array("meta_id" => $item['_wp_attachment_metadata_id']));
		if (!$uuup) {
			die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
		}

		$up = array("meta_value" => $iloc);
		$uuup = $this->db->update("wp_postmeta", $up, array("meta_key" => "_wp_attached_file", "post_id" => $item['image_post_id']));
		if (!$uuup) {
			die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
		}

		$up = array("post_title" => $img_title, "post_name" => $img_title, "guid" => $gurl, "post_mime_type" => $mime);
		$uuup = $this->db->update("wp_posts", $up, array("ID" => $item['image_post_id']));

		if (!$uuup) {
			die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
		}

		echo json_encode(array("msg" => "New Photo Updated!"));

	}

	function textsearch() {
		$str = strtolower($this->input->post('str'));
		$q = "SELECT * FROM wp_posts where lower(post_title) like '%{$str}%'";
#ct=1;
		$r = $this->db->query($q)->result();
		$out = array();
		$ct = 1;
		foreach ($r as $row) {

			$q = "SELECT * FROM `wp_postmeta`  where meta_key='_thumbnail_id' and post_id=" . $row->ID;
			@$t = $this->db->query($q)->row();
			@$ipost_id = $t->meta_value;
			if (!$ipost_id) {
				continue;
			}

			$r = $this->db->query("SELECT * FROM `wp_postmeta`  where meta_key='_wpm_gtin_code' and post_id=" . $row->ID);
			$pp = $r->row();

			$row->upc = $pp->meta_value;

			$q = "SELECT * FROM `wp_postmeta`  where meta_key='_wp_attached_file' and post_id=" . $ipost_id;
			$t = $this->db->query($q)->row();

			$row->img = $t->meta_value;

			$q = "SELECT * FROM `wp_postmeta`  where meta_key='_wp_attachment_metadata' and post_id=" . $ipost_id;
			$t = $this->db->query($q)->row();

			$row->_wp_attachment_metadata_id = $t->meta_id;

			$row->product_post_id = $row->ID;
			$row->image_post_id = $ipost_id;

			$out[] = $row;
			$ct++;
		}

		echo json_encode($out);
	}

	function fixd($go = 0) {
		$q = "select * from wp_posts where post_title like '%Van Gogh%'";
		$r = $this->db->query($q)->result();
		foreach ($r as $row) {
			echo "<P>" . $row->post_title;

			$q = "select * from wp_term_relationships where object_id={$row->ID} and term_taxonomy_id=1334";
			$rr = $this->db->query($q)->result();
			if (count($rr) > 0) {
				continue;
			}
			$q = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ('{$row->ID}', '1334', '0');";
			if ($go) {
				$done = $this->db->query($q);
				if (!$done) {
					die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
				}
			} else {
				echo "<P>$q";
			}

		}

	}
	function findimage($upc = null) {
		if (!$upc) {
			die(json_encode(array('img' => "none")));

		}

		$url = "https://api.barcodespider.com/v1/lookup?upc=" . $upc;
		$endpoint = "https://api.barcodespider.com/v1/lookup";
		$put[] = $url;

		$ch = curl_init();

		$headers = array(
			'token' => "f9ef1f0279e7b37de96b",
			'Host' => "api.barcodespider.com",
			'Accept-Encoding' => "gzip, deflate",
			'Connection' => "keep-alive",
			'cache-control' => "no-cache",
		);

		curl_setopt_array($ch, array(
			CURLOPT_URL => $endpoint . "?token=f9ef1f0279e7b37de96b&upc=" . $upc,
			CURLOPT_SSL_VERIFYHOST => 0, // do not return headers
			CURLOPT_SSL_VERIFYPEER => 0, // do not return headers
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_POSTFIELDS => "",
			CURLOPT_HTTPHEADER => $headers,
		));

		$content = curl_exec($ch);
		curl_close($ch);

		$json = json_decode($content);
		if ($json->item_response->code != 200) {

			$put[] = "Did not find page on api";
			$puts[] = $put;
			die("<h3>BAD response</h3><pre>" . print_r($json, 1) . "</pre>");
		}

		//die("<h3>Output</h3><pre>" . print_r($json, 1) . "</pre>");

		$imgs = array();
		$img = null;
		if ($json->item_attributes->image) {
			$imgs[] = $json->item_attributes->image;
		}

		if (isset($json->Stores)) {

			foreach ($json->Stores as $store) {
				if ($store->image) {
					if (strpos($store->store_name, "Amazon") !== false) {
						$img = $store->image;
					} else {
						$imgs[] = $store->image;
					}
				}
			}
		}

		if (!$img && count($imgs) > 0) {
			$img = $imgs[count($imgs) - 1];
		}

		if (!$img) {
			$put[] = "NO IMAGE at UPC";
			$puts[] = $put;

			die("<h3>NO IMAGE</h3><pre>" . print_r($content, 1) . "</pre>");
			$cont++;
		}

		die(json_encode(array('img' => $img)));

	}

	function getscrapeMacImg($last_id = 0) {
		$put = array();
		$puts = array();
		$q = "select * from jt_noimg where id>$last_id and imagetoget!='' and got=0 limit 1";
		$r = $this->db->query($q);
		if ($r->num_rows() == 0) {
			die("<h3>Output</h3><pre>" . print_r("DONE", 1) . "</pre>");
		}
		$row = $r->row();
		$r->free_result();
		$sku = $row->sku;
		$id = $row->id;

		$data = json_decode($row->data);
		$item = json_decode(json_encode($data->item), 1);

		$img_title = null;

		$pp = $this->db->query('select * from wp_posts where ID=' . $item['product_post_id'])->row();
		if ($pp) {
			$img_title = $pp->post_title;
		}
		$img = $row->imagetoget;
		$img = explode("?", $img);
		$img = $img[0];

		$iname = explode("/", $img);
		$iname = $iname[count($iname) - 1];

		if (!$img_title) {
			$img_title = $iname;
		}

		$ipostname = explode(".", $iname);
		$ext = strtolower($ipostname[count($ipostname) - 1]);
		$ipostname = $ipostname[0];

		$iloc = "2020/06/" . $iname;
		$dest = $_SERVER['DOCUMENT_ROOT'] . "/wp-content/uploads/" . $iloc;
		$this->getImage($img, $dest);

		if (!file_exists($dest)) {
			$put[] = "image did not download";
			$puts[] = $put;
			$write = array("item" => $item, "response" => $content, "url" => $endpoint . "?token=f9ef1f0279e7b37de96b&upc=" . $item['upc']);
			$aaa = array("data" => json_encode($write), "upc" => $item['upc']);
			//$this->db->insert("jt_noimg", $aaa);

			//$ct++;
			die("<h3>Output</h3><pre>" . print_r($content, 1) . "</pre>");
		}
		$put[] = "downloaded: $iloc";

		$gurl = "https://thepaint-chip.com/wp-content/uploads/" . $iloc;
		$mime = "";
		if ($ext == "jpg" || $ext == "jpeg") {
			$mime = "image/jpeg";
		} else if ($ext == "png") {
			$mime = "image/png";

		} else if ($ext == "gif") {
			$mime = "image/gif";

		}

		@$sz = getimagesize($dest);

		if (!$sz) {
			echo json_encode(array("this_id" => $id, "put" => $put, 'error' => 'no image'));
			die();
			die("<h3>Output</h3><pre>" . print_r($dest, 1) . "</pre>");
		}

		$img_meta = array(

			"width" => $sz[0],
			"height" => $sz[1],
			"file" => $iloc,
			"sizes" => Array
			(
			),

			"image_meta" => Array
			(
				"aperture" => 0,
				"credit" => "",
				"camera" => "",
				"caption" => "",
				"created_timestamp" => 0,
				"copyright" => "",
				"focal_length" => 0,
				"iso" => 0,
				"shutter_speed" => 0,
				"title" => $img_title,
				"orientation" => 0,
				"keywords" => Array
				(
				),

			),
		);
		$img_meta = serialize($img_meta);

		$up = array("meta_value" => $img_meta);
		$uuup = $this->db->update("wp_postmeta", $up, array("meta_id" => $item['_wp_attachment_metadata_id']));
		if (!$uuup) {
			die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
		}

		$up = array("meta_value" => $iloc);
		$uuup = $this->db->update("wp_postmeta", $up, array("meta_key" => "_wp_attached_file", "post_id" => $item['image_post_id']));
		if (!$uuup) {
			die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
		}

		$up = array("post_title" => $img_title, "post_name" => $img_title, "guid" => $gurl, "post_mime_type" => $mime);
		$uuup = $this->db->update("wp_posts", $up, array("ID" => $item['image_post_id']));

		if (!$uuup) {
			die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
		}

		$put[] = "did everything image: $iloc";
		$puts[] = $put;
		$this->db->update("jt_noimg", array("got" => 1), array("id" => $id));

		echo json_encode(array("this_id" => $id, "put" => $put));

	}

	function scrapeMacImg($last_id = 0) {

		$ct = 0;

		$q = "select * from jt_noimg where id>$last_id and supplier='MAC' and imagetoget='' limit 1";
		$r = $this->db->query($q);
		if ($r->num_rows() == 0) {
			die("<h3>Output</h3><pre>" . print_r("DONE", 1) . "</pre>");
		}
		$row = $r->row();
		$r->free_result();
		$sku = $row->sku;
		$id = $row->id;
		$url = "https://www.macphersonart.com/cgi-bin/maclive/wam_tmpl/catalog_browse.p?site=MAC&layout=Responsive&page=catalog_browse&searchText=" . $sku;
		//echo "<P> starting $ct";
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

		$res['content'] = $content;
		$res['content'] = strip_tags($content, "<body>");
		$res['url'] = $header['url'];

		$newurl = str_replace('document.location.replace("', "", $res['content']);
		$newurl = str_replace('");', "", $newurl);

		if (!$newurl || strpos($newurl, "Catalog Browse | MacPherson's") != FALSE) {
			$item['data'] = "NO URL";
			//$this->db->update("jt_mac_data", $item, array("id" => $row->id));
			die("<h3>Output</h3><pre>" . print_r("no url", 1) . "</pre>");

		}
		$html = file_get_html($newurl);
		//return $res;

		$item = array();

		//print_r(get_web_page("http://www.example.com/redirectfrom"));

		/*foreach ($d as $dd) {
					echo ("<h3>Output</h3><pre>" . print_r($dd->innerText, 1) . "</pre>");
				}
			*/
		$d = $html->find('#row' . $sku . ' td');
		if (count($d) == 0) {
			$ct++;

			//echo "<p>continuoing... <a href='$url' target='_blank'>$url</a>";

			die("<h3>Output</h3><pre>" . print_r("no data", 1) . "</pre>");
			//die("<h3>no data</h3><pre>" . print_r($newurl, 1) . print_r($row, 1) . "</pre>");
			$item['data'] = "NO DATA";
			$this->db->update("jt_mac_data", $item, array("id" => $row->id));

		}

		$de = $html->find('.prodDescription');
		$desc = "";
		foreach ($de as $des) {
			$desc .= $des->innerText;
		}

		$item['description'] = $desc;
		$imagetoget = "";

		$a = $html->find('img#mainProdImg1');
		if (count($a) > 0) {
			$imagetoget = "https://www.macphersonart.com" . $a->src;
			$this->db->update("jt_noimg", array("imagetoget" => $imagetoget), array("id" => $id));
			echo json_encode(array("this_id" => $id));
			return;
		} else {
			die("<h3>Output</h3><pre>" . print_r("nadad", 1) . "</pre>");
			$a = $d[0]->find('img');
			if (count($a) > 0) {
				$a = "https://www.macphersonart.com" . $a->src;
				if ($a) {
					$iname = explode("/", $a);
					$iname = $iname[count($iname) - 1];
					$imagetoget = "https://www.macphersonart.com" . $a;
				}
			}
		}

		if (!$imagetoget) {

			foreach ($d as $dd) {
				echo ("<h3>Output</h3><pre>" . print_r($dd->innerText, 1) . "</pre>");
			}

			die("<h3>NO IMG</h3><pre>" . print_r($d[0]->innerText, 1) . "</pre>");
		}
		die("<h3>Output</h3><pre>" . print_r($imagetoget, 1) . "</pre>");
		if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/helper/uploads/{$iname}")) {
			$up = $this->getImage($imagetoget, $iname);
			if (!$up) {
				die("<h3>Output</h3><pre>" . print_r("DID not get image", 1) . "</pre>");
			} else {
				$item['image'] = $iname;
			}
		} else {
			$item['image'] = $iname;

		}

		$tdata = $html->find('h2.prodCrumbDesc');
		$tdata = $tdata[0];
		$item['category'] = $tdata->innerText;

		$tdata = $html->find('.millDescription a');
		$tdata = $tdata[0];
		$item['brand'] = $tdata->innerText;

		$tdata = $html->find('a.prodCrumbLink');
		$tdata = $tdata[count($tdata) - 1];
		$item['main_category'] = $tdata->innerText;

		$tdata = $d[1]->find('div');
		//die("<h3>Output</h3><pre>" . print_r($d[1]->innerText, 1) . "</pre>");
		$tdata = $tdata[1];
		$item['title'] = str_replace("<br>", " ", $tdata->innerText);

		$tdata = $d[6]->find('div.qoRegPrice');
		$tdata = $tdata[0];
		$item['macprice'] = str_replace("$", "", $tdata->innerText);

		$item['data'] = "DONE";
		$this->db->update("jt_mac_data", $item, array("id" => $row->id));
		$act++;
		//echo ("<h3>Output</h3><pre>" . print_r($item, 1) . "</pre>");
		$ct++;
		//sleep(1);
		//die("<h3>Output</h3><pre>" . print_r($a, 1) . "</pre>");

	}

	function scrapeMac($sku) {
		$q = "select * from jt_mac_data where data='' limit 4";
		$r = $this->db->query($q);

		$rw = $r->result();
		if (count($rw) == 0) {
			echo json_encode(array("complete" => 1));
			die();
		}
		$ct = 0;
		$act = 0;
		foreach ($rw as $row) {
			$url = "https://www.macphersonart.com/cgi-bin/maclive/wam_tmpl/catalog_browse.p?site=MAC&layout=Responsive&page=catalog_browse&searchText=" . $row->sku;
			//echo "<P> starting $ct";
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

			$res['content'] = $content;
			$res['content'] = strip_tags($content, "<body>");
			$res['url'] = $header['url'];

			$newurl = str_replace('document.location.replace("', "", $res['content']);
			$newurl = str_replace('");', "", $newurl);

			if (!$newurl || strpos($newurl, "Catalog Browse | MacPherson's") != FALSE) {
				$item['data'] = "NO URL";
				$this->db->update("jt_mac_data", $item, array("id" => $row->id));

				continue;
			}
			$html = file_get_html($newurl);
			//return $res;

			$item = array();

			//print_r(get_web_page("http://www.example.com/redirectfrom"));

			/*foreach ($d as $dd) {
					echo ("<h3>Output</h3><pre>" . print_r($dd->innerText, 1) . "</pre>");
				}
			*/
			$d = $html->find('#row' . $row->sku . ' td');
			if (count($d) == 0) {
				$ct++;

				//echo "<p>continuoing... <a href='$url' target='_blank'>$url</a>";

				//die("<h3>no data</h3><pre>" . print_r($newurl, 1) . print_r($row, 1) . "</pre>");
				$item['data'] = "NO DATA";
				$this->db->update("jt_mac_data", $item, array("id" => $row->id));

				continue;
			}

			$de = $html->find('.prodDescription');
			$desc = "";
			foreach ($de as $des) {
				$desc .= $des->innerText;
			}

			$item['description'] = $desc;
			$imagetoget = "";

			$a = $d[0]->find('a');
			if (count($a) > 0) {
				$a = $a->href;
				if ($a) {
					$iname = explode("/", $a);
					$iname = $iname[count($iname) - 1];
					$imagetoget = "https://www.macphersonart.com" . $a;
				}
			} else {
				$a = $d[0]->find('img');
				if (count($a) > 0) {
					$a = $a->src;
					if ($a) {
						$iname = explode("/", $a);
						$iname = $iname[count($iname) - 1];
						$imagetoget = "https://www.macphersonart.com" . $a;
					}
				}
			}

			if (!$imagetoget) {

				foreach ($d as $dd) {
					echo ("<h3>Output</h3><pre>" . print_r($dd->innerText, 1) . "</pre>");
				}

				die("<h3>NO IMG</h3><pre>" . print_r($d[0]->innerText, 1) . "</pre>");
			}

			if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/helper/uploads/{$iname}")) {
				$up = $this->getImage($imagetoget, $iname);
				if (!$up) {
					die("<h3>Output</h3><pre>" . print_r("DID not get image", 1) . "</pre>");
				} else {
					$item['image'] = $iname;
				}
			} else {
				$item['image'] = $iname;

			}

			$tdata = $html->find('h2.prodCrumbDesc');
			$tdata = $tdata[0];
			$item['category'] = $tdata->innerText;

			$tdata = $html->find('.millDescription a');
			$tdata = $tdata[0];
			$item['brand'] = $tdata->innerText;

			$tdata = $html->find('a.prodCrumbLink');
			$tdata = $tdata[count($tdata) - 1];
			$item['main_category'] = $tdata->innerText;

			$tdata = $d[1]->find('div');
			//die("<h3>Output</h3><pre>" . print_r($d[1]->innerText, 1) . "</pre>");
			$tdata = $tdata[1];
			$item['title'] = str_replace("<br>", " ", $tdata->innerText);

			$tdata = $d[6]->find('div.qoRegPrice');
			$tdata = $tdata[0];
			$item['macprice'] = str_replace("$", "", $tdata->innerText);

			$item['data'] = "DONE";
			$this->db->update("jt_mac_data", $item, array("id" => $row->id));
			$act++;
			//echo ("<h3>Output</h3><pre>" . print_r($item, 1) . "</pre>");
			$ct++;
			//sleep(1);
			//die("<h3>Output</h3><pre>" . print_r($a, 1) . "</pre>");

		}

		echo json_encode(array("actual" => $act));

	}

	function macBrands($go = 0) {
		$newbrands = array();
		$newbrandsy = array();
		$a = $this->getBrands();
		foreach ($a as $aa) {
			$newbrands[$aa[1]] = $aa[0];
			$newbrandsy[] = $aa[1];

		}

		//die("<h3>Output</h3><pre>" . print_r($ex, 1) . "</pre>");
		$q = "select * from jt_mac_data where data='DONE'";
		$r = $this->db->query($q)->result();

		$brands = array();
		foreach ($r as $row) {
			if (!in_array($row->brand, $brands) && !in_array($row->brand, $newbrandsy)) {
				$brands[] = $row->brand;
			}

		}
		$ct = 1;
		foreach ($brands as $b) {

			$nt = strtolower($b);
			$nt = str_replace("& ", "", $nt);
			$nt = str_replace("&", "", $nt);
			$slug = str_replace(" ", "-", $nt);

			//	$b = htmlentities($b);

			$in = array("name" => $b, "slug" => $slug);
			$str = $this->db->insert_string("wp_terms", $in);

			echo "<P>" . $str;
			if ($go) {
				$this->db->query($str);
			}
			$id = $go ? $this->db->insert_id() : $ct;
			$newbrands[$b] = $id;

			// create the Taxonomy connex

			$in = array("term_id" => $id, "taxonomy" => "pwb-brand", "description" => "<h2>{$b}</h2>");
			$str = $this->db->insert_string("wp_term_taxonomy", $in);

			echo "<P>" . $str;
			if ($go) {
				$this->db->query($str);
			}
			///$id = $go ? $this->db->insert_id() : $ct;

			$ct++;

		}

		echo "<hr>";

		foreach ($r as $row) {
			$q = "select * from wp_posts where lower(post_title) = '" . addslashes(strtolower(trim(str_replace("\n", "", $row->title)))) . "'";
			$rr = $this->db->query($q)->result();

			if (count($rr) == 0) {
				//echo ("<h3>Output</h3>" . count($rr) . "<pre>" . print_r($q, 1) . "</pre>");
			} else {

				foreach ($rr as $rrow) {
					$ex = $this->db->query('select * from wp_term_relationships where object_id=' . $rrow->ID . " and term_taxonomy_id =" . $newbrands[$row->brand]);
					if ($ex->num_rows() > 0) {
						echo "<P>----------- ALREADY ASSIGNED IN DB - ({$row->post_title})";
						continue;

					}
					$ex->free_result();
					if (in_array($rrow->ID, $used)) {
						echo "<P>------------------------ ERROR - already assigned this post: {$row->post_title} for another brand, not {$newbrands[$row->brand]}";
					}
					$used[] = $rrow->ID;
					echo "<P>-- <strong>{$rrow->post_title}</strong> getting branded as <strong><i>{$row->brand}</i></strong>";
					if ($go) {
						$this->db->query("INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ('{$rrow->ID}', '{$newbrands[$row->brand]}', '0');");
					}
				}

				//echo ("<h3>Output</h3><pre>" . print_r($rr, 1) . "</pre>");
			}

		}
		die("<h3>Brands</h3><pre>" . print_r($brands, 1) . "</pre>");
	}

	function fixTags($go = 0) {

		$tags = array(
			array(1327, "Winsor", "Cotman"),
			array(1328, "Winsor", "Oil"),
			/*array(1319, "Georgian", "Water Mixable"),
				array(1320, "Gamblin", "Artist Oil Colors"),
				array(1321, "Golden", "Artist"),
				array(1322, "Golden", "Fluid"),
				array(1323, "Golden", "Heavy Body"),
				array(1324, "Golden", "High Flow"),
				array(1325, "Liquitex", "Basics"),
				array(1326, "Liquitex", "Heavy Body"),
				array(1329, "Winsor", "Galeria Acrylic"),
				array(1330, "Daniel Smith", "Watercolors"),
				array(1331, "Louvre", "Acrylic"),
			*/
		);

		foreach ($tags as $ar) {
			$str = $ar[1];
			if (isset($ar[2])) {
				$str2 = $ar[2];
			} else {
				$str2 = "";
			}

			$pt = "post_title like '%$str%'";
			if (isset($ar[2])) {
				$pt .= "and post_title like '%$str2%' ";
			}

			$q = "select * from wp_posts where post_type='product' and ($pt)";
			$r = $this->db->query($q)->result();
			foreach ($r as $row) {
				/*$ex = $this->db->query('select * from wp_term_relationships where object_id=' . $row->ID . " and term_taxonomy_id in ($terma)");
					if ($ex->num_rows() > 0) {
						echo "<P>-- ALREADY ASSIGNED IN DB - ({$row->post_title})";
						continue;

				*/
				/**/
				if (in_array($row->ID, $used)) {
					//echo "<P>-- ERROR - already assigned this post: {$row->post_title} for another brand, not {$ar[1]}";
					continue;
				}
				$used[] = $row->ID;
				//echo "<P>-- <strong>{$row->post_title}</strong> getting branded as <strong><i>{$ar[1]} - {$str2}</i></strong>";
				echo "<P>INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ('{$row->ID}', '{$ar[0]}', '0');";
			}

		}

	}

	function findImages() {

		$arr = array();
		$r = $this->db->query("SELECT * FROM `wp_postmeta`  where meta_value like '%width\";N%' ");
		$rr = $r->result();
		$r->free_result();

		foreach ($rr as $row) {

			// this post id is the image post
			// we'll test it to make sure there's an WP_POST image associated with it
			$q = $this->db->query("select * from wp_posts where ID=" . $row->post_id);
			$imgp = $q->result();
			$q->free_result();
			if (count($imgp) == 0) {
				echo "<p>-- continueing, no iamge post";
				continue;
			}

			// each of these is a broken image
			// find the post id so we can get the upc
			$r = $this->db->query("SELECT * FROM `wp_postmeta`  where meta_key='_thumbnail_id' and meta_value=" . $row->post_id);
			$pm = $r->result();
			$r->free_result();

			if (count($pm) == 0) {
				echo "<p>-- continuing, no _thumbnail_id ";
				continue;
			}

			// now we have the actual post ID, make sure there is a post
			$the_post_id = $pm[0]->post_id;

			$r = $this->db->query("SELECT * FROM `wp_postmeta`  where meta_key='_wpm_gtin_code' and post_id=" . $the_post_id);
			$pp = $r->row();

			$upc = $pp->meta_value;
			$r->free_result();
			foreach ($pm as $p) {

				$arr[] = array(
					"upc" => $upc,
					"meta_thumbnail_id" => $p->meta_id,
					"image_post_id" => $row->post_id,
					"product_post_id" => $the_post_id,
					"_wp_attachment_metadata_id" => $row->meta_id,
					"_wp_attached_file_id" => "FIND based on post_id value like 2020/05/images-tb-56555.jpg",
				);

/*

// update:::
get new image
-- update wp_postmeta _wp_attachment_metadata with values like
a:5:{s:5:"width";N;s:6:"height";N;s:4:"file";s:27:"2020/05/images-tb-56555.jpg";s:5:"sizes";a:0:{}s:10:"image_meta";a:12:{s:8:"aperture";i:0;s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";i:0;s:9:"copyright";s:0:"";s:12:"focal_length";i:0;s:3:"iso";i:0;s:13:"shutter_speed";i:0;s:5:"title";s:36:"DUAL BRUSH PEN REFLEX BLUE (ABT 493)";s:11:"orientation";i:0;s:8:"keywords";a:0:{}}}
-- update wp_postmeta _wp_attached_file with values like
2020/05/images-tb-56555.jpg

-- update wp_posts with
post_title like mc21120_t
post_name like  mc21120_t
guid like https://thepaint-chip.com/wp-content/uploads/2020/06/MC21120_t.png
post_mime_type like image/jpeg

 */

			}

			// get post UID
		}

		die("<h3>" . count($arr) . "</h3><pre>" . print_r($arr, 1) . "</pre>");

	}

	function getBrands() {
		$a = array(
			array(1167, "Gamblin"),
			array(1169, "Georgian"),
			array(1170, "Liquitex", "Basic"),
			array(1171, "Golden", "Artist Colors"),
			/*array(1172, "Golden Fluid "),
				array(1180, "Golden Heavy Body"),
			*/
			array(1174, "Winsor & Newton", "Acrylic"),
			/*array(1168, "Winsor", "Oil"),
			array(1177, "Winsor", "Water"),*/
			array(1175, "Liquitex", "Heavy Body"),
			array(1176, "Daniel Smith", "Watercolor"),
			array(1178, "LeFranc", "Gouache"),
			array(1179, "Louvre"),
		);

		return $a;
	}

	function makeTags($go = 0) {
		$tags = array(
			"Georgian Water Mixable Oils",
			"Gamblin Artist Oil Colors",
			"Golden Artist Colors",
			"Golden Fluid Acrylics",
			"Golden Heavy Body Acrylics",
			"Golden High Flow Acrylics",
			"Liquitex Basics",
			"Liquitex Processional Heavy Body Acrylic",
			"Winsor & Newton Cotman Water Colour",
			"Winsor & Newton Winton Oil Colour",
			"Winsor & Newton Galeria Acrylic",
			"Daniel Smith Extra-Fine Watercolors",
			"Louvre Acrylic",
			"LeFranc Guache",
		);

		$ct = 1;
		$ntags = array();

		foreach ($tags as $tag) {

			$nt = strtolower($tag);
			$nt = str_replace("& ", "", $nt);
			$nt = str_replace("&", "", $nt);
			$slug = str_replace(" ", "-", $nt);

			//	$b = htmlentities($b);

			$in = array("name" => $tag, "slug" => $slug);
			$str = $this->db->insert_string("wp_terms", $in);

			echo "<P>" . $str;
			if ($go) {
				$this->db->query($str);
			}
			$id = $go ? $this->db->insert_id() : $ct;
			$ntags[$tag] = $id;

			$in = array("term_id" => $id, "taxonomy" => "product_tag");
			$str = $this->db->insert_string("wp_term_taxonomy", $in);

			echo "<P>" . $str;
			if ($go) {
				$this->db->query($str);
			}

			$ct++;
		}

		die("<h3>Output</h3><pre>" . print_r($ntags, 1) . "</pre>");

	}

	/*

		Golden Artist Colors
		Golden Fluid Acrylics
		Golden Heavy Body Acrylics
		Golden High Flow Acrylics

		Liquitex Basics
		Liquitex Processional Heavy Body Acrylic

		Winsor & Newton Cotman Water Colour
		Winsor & Newton Winton Oil Colour
		Winsor & Newton Galeria Acrylic

	*/

	/*function addBrands($go = 0) {
		$brands = array(
			"Prismacolor",
		);

		foreach ($brands as $b) {

			$nt = strtolower($b);
			$slug = str_replace(" ", "-", $nt);

			$in = array("name" => $b, "slug" => $slug);
			$str = $this->db->insert_string("wp_terms", $in);

			//echo "<P>" . $str;
			if ($go) {
				$this->db->query($str);
			}
			$id = $go ? $this->db->insert_id() : $ct;

			echo "<br>array({$id}, '$b'),";
			//$newbrands[$b] = $id;

			$ct++;

		}
	}*/

	function fixBrands() {
		$a = $this->getBrands();
		$used = array();
		$terma = array();
		foreach ($a as $ar) {
			$terma[] = $ar[0];
		}

		$ct = 0;
		$terma = implode(",", $terma);
		foreach ($a as $ar) {
			$str = $ar[1];
			if (isset($ar[2])) {
				$str2 = $ar[2];
			} else {
				$str2 = "";
			}

			$pt = "post_title like '%$str%'";
			if (isset($ar[2])) {
				//$pt .= "and post_title like '%$str2%' ";
			}

			$q = "select * from wp_posts where post_type='product' and ($pt)";
			$r = $this->db->query($q)->result();
			foreach ($r as $row) {
				$ex = $this->db->query('select * from wp_term_relationships where object_id=' . $row->ID . " and term_taxonomy_id in ($terma)");
				if ($ex->num_rows() > 0) {
					//echo "<P>-- ALREADY ASSIGNED IN DB - ({$row->post_title})";
					continue;

				}
				$ex->free_result();
				if (in_array($row->ID, $used)) {
					//echo "<P>-- ERROR - already assigned this post: {$row->post_title} for another brand, not {$ar[1]}";
					continue;
				}
				$used[] = $row->ID;
				//echo "<P>-- <strong>{$row->post_title}</strong> getting branded as <strong><i>{$ar[1]} - {$str2}</i></strong>";
				echo "<P>INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ('{$row->ID}', '{$ar[0]}', '0');";
			}

		}

		die("<h3>INSERTED</h3><pre>" . print_r(count($used), 1) . "</pre>");
	}

	function getCSV($file) {

		ini_set("memory_limit", "500M");
		$file = $_SERVER['DOCUMENT_ROOT'] . "/helper/assets/{$file}.csv";

		$this->load->helper('file');

		$f = read_file($file);
		$f = str_replace("\r", "\n", $f);
		$lines = explode("\n", $f);
		$allitems = array();
		$noids = array();
		$replaces = array(
			'"' => '',
			"GPIKIT" => "GP1KIT",
			"SLSF" => "SLST",
			"5Y5" => "SYS",
		);
		$line_type = "";
		$itema = null;
		$lctr = 0;

		// first repair
		$nlines = array();
		$lctr = 0;
		//die("<h3>Output</h3><pre>" . print_r($lines, 1) . "</pre>");
		foreach ($lines as $line) {
			if (strpos($line, "$") === 0) {
				$nlines[$lctr - 1] .= "," . $line;
			} else {
				$nlines[] = $line;

				$lctr++;
			}
		}

		$lines = $nlines;

		foreach ($lines as $line) {

			foreach ($replaces as $find => $replace) {
				$line = str_replace($find, $replace, $line);
			}

			$line = preg_replace('/  +/', ',', $line);
			$strline = $line;

			$line = explode(",", $line);

			$line = array_values(array_filter($line));
			// $val = $this->decipher($item)

			$idd = false;
			if (!$line || count($line) == 0) {
				continue;
			}

			$firstline = $this->isFirstLine($line);

			if ($firstline) {
				$line_type = 'first';
				$dta = $this->getTitleAndCategory($line);

				//echo "\n - got title " . print_r($dta, 1);
				//die("<h3>Output</h3><pre>" . print_r($strline, 1) . "</pre>");
				$itema = array(
					"id" => "",
					"title" => $dta['title'],
					"price" => '0.00',
					"supplier" => "SS",
					"stock" => "0",
					"category" => $dta['cat'],

				);

				if (!$strline) {
					$strline = "";
				}

				$itema['odata'] = "LINE 1: " . json_encode($line);
				$itema['odata1'] = json_encode($line);

				if ($dta['title'] == '' && $dta['cat'] == '') {
					//echo ("<h3>Processing</h3><pre>" . print_r($line, 1) . print_r($dta, 1) . "</pre>");
					$noids[] = $itema;

					continue;
				}

			} else {
				$line_type = 'second';

				if (!$itema) {
					//echo "\n - NO arr started for second line ";
					$searchlines[] = $line;
					//echo "\n adding to search...  " . print_r($line, 1);

					continue;
				} else {

					//die("<h3>Outsssssput</h3><pre>" . print_r($line, 1) . print_r($itema, 1) . "</pre>");
				}

				$dta = $this->getIDAndPrice($line);
				//echo "\n dta " . print_r($dta, 1);

				$itema['theprice'] = $dta['theprice'];
				$itema['price'] = $dta['price'];
				$itema['id'] = $dta['id'];
				$itema['odata'] .= " // LINE 2: " . json_encode($line);
				$itema['odata2'] = json_encode($line);

				if ($dta['id']) {
					$allitems[] = $itema;
					//echo "\n ---- WRITTEN " . print_r($itema, 1);

				} else {
					//echo "\n ---- NOT " . print_r($itema, 1);

					$noids[] = $itema;
					//$searchlines[] = $line;
				}
				$itema = null;

			}
			$lctr++;
		}
		//die("<h3>Output</h3><pre>" . print_r($searchlines, 1) . "</pre>");
		/*foreach ($searchlines as $line) {
			$dta = $this->getIDAndPrice($line);
			//	echo ("<h3>Output</h3><pre>" . print_r($line, 1) . print_r($dta, 1) . "</pre>");
			$dta['odata'] = json_encode($line);

			$other[] = $dta;
		}*/
		$new_noids = array();
		foreach ($noids as $line) {
			//echo ("<h3>IN </h3><pre>" . print_r($line['odata1'], 1) . print_r($line['odata2'], 1) . "</pre>");
			if (isset($line['odata1'])) {
				$dta = $this->getIDAndPrice(json_decode($line['odata1']), 1);
				//echo ("<h3>-- Output 1</h3><pre>" . print_r($dta, 1) . "</pre>");
			}
			if ($dta['id'] == '' && isset($line['odata2'])) {
				$dta = $this->getIDAndPrice(json_decode($line['odata2']), 1);
				//	echo ("<h3>-- Output 2</h3><pre>" . print_r($dta, 1) . "</pre>");
			}

			if ($dta['id']) {
				$allitems[] = $dta;
			} else {
				$new_noids[] = $line;
			}

		}

		//die("<h3>Output</h3><pre>" . print_r($searchlines, 1) . "</pre>");
		$out = array("not" => $new_noids, "found" => $allitems);
		//	die("<h3>Output</h3><pre>" . print_r($out, 1) . "</pre>");
		echo json_encode($out);
		//echo ("<h3>Output- found: " . count($allitems) . " / not found: " . count($noids) . "</h3><pre>" . print_r($allitems, 1) . "</pre>");

	}

	function removeItem($sku) {
		$q = 'delete from jt_supplier_data where sku="' . $sku . '" and data=""';
		//die("<h3>Output</h3><pre>" . print_r($q, 1) . "</pre>");
		$this->db->query($q);
		echo "OK";
	}

	function isFirstLine($line = array()) {

		if (strtolower(trim($line[0])) == 'ss') {
			return true;
		}
		$test = preg_replace('/[0-9]+/', '', implode("", $line));
		if (strpos($test, "$") !== false) {
			return false;
		}

		$test = str_replace("Yes", "", $test);
		$test = str_replace("EA", "", $test);
		$test = str_replace("cleared", "", $test);

		if (strlen($test) > 8) {
			return true;
		}
		//	echo "\n Not first line  " . print_r($line, 1);
		return false;
	}
	function str_replace_first($from, $to, $content) {
		$from = '/' . preg_quote($from, '/') . '/';

		return preg_replace($from, $to, $content, 1);
	}
	function getIDAndPrice($lineitem = array(), $secondtimearound = false) {
		$id = "";
		$price = array();
		if (!$lineitem || !is_array($lineitem)) {
			return array("price" => 0, "theprice" => 0, "id" => "");

		}
		if (count($lineitem) > 2) {

			$lineitem[1] = str_replace(" ", "", $lineitem[1]);
			$lineitem[1] = str_replace("l", "1", $lineitem[1]);
			$lineitem[1] = preg_replace("/[^A-Za-z0-9 ]/", '', $lineitem[1]);
			if ($lineitem[1] . substr(0, 2) == '18') {
				$lineitem[1] = "TB" . $lineitem[1] . substr(2);
			}
		}
		$ctr = 0;
		foreach ($lineitem as $l) {
			$el = $this->decipher($l);
			//$num = preg_replace('/\d/', '', $el);

			$num = filter_var($el, FILTER_SANITIZE_NUMBER_INT);

			$str = preg_replace('/[0-9]+/', '', $el);
			$l = trim($l);
			$isID = (
				strpos($l, "Supplier") === false && // not a price
				strpos($l, " ") === false && // not a price
				strpos($l, "$") === false && // not a price
				$str != '' && // string part not empty
				strlen($str) > 0 && // string part longer than 1 letter
				strlen($el) > 3 && // original length > 4
				//(intval($num) > 0 || strpos($l, "I") !== false || strpos($l, "I") !== false) &&
				substr($el, 0, 2) != "00" &&
				intval(substr($el, 0, 2)) == 0// first 2 characters are not numbers
			);

			if (!$isID) {
				$isID = strpos($l, "3M") === 0;
			}

			if ($isID && $secondtimearound) {
				$isID = strpos($l, "SS") !== 0;
			}

			if ($isID && !$id) {

				$id = preg_replace("/[^A-Za-z0-9 ]/", '', $el);
				$id = $this->str_replace_first("I", "1", $id);

				//echo "<p>\n SET $id -- $l -- $el =  $word -- first: " . $firstchar;

//                echo "\npassing on $el";

				//continue;
			} else {
				if (strpos($el, "$") !== false) {
					$price[] = $el;
				} else {
					//echo "<p>passing on $el";
				}

			}

			$ctr++;

		}

		$theprice = 0.0;
		if ($price && is_array($price)) {
			//echo ("<h3>Output</h3><pre>" . print_r($price, 1) . "</pre>");
			foreach ($price as $p) {
				$p = preg_replace("/!\d!/", '', $p);
				$p = str_replace("$", "", $p);
				$p = floatval($p);
				//die("<h3>Output</h3><pre>" . print_r($p, 1) . "</pre>");
				$theprice = max($p, $theprice);
			}

			$theprice = number_format($theprice, 2);

		}

		return array("price" => $price, "theprice" => $theprice, "id" => $id);
	}

	function getTitleAndCategory($lineitem = array()) {
		//echo ("<h3>Outp33333ut</h3><pre>" . print_r($lineitem, 1) . "</pre>");
		$title = "";
		$cat = "";
		$trigger = 0;
		if (!$lineitem || !is_array($lineitem)) {
			return array("cat" => "", "title" => "");
		}

		foreach ($lineitem as $l) {
			$el = $this->decipher($l);
			$word = preg_replace('/\d/', '', $el);
			if ($l == "SM4001") {
				$trigger = 1;
			}

			if (strlen($word) > 4) {
				if ($title == '') {
					$title = ucwords(strtolower($el));
				} else if ($cat == '') {
					$cat = ucwords(strtolower($word));
				}

			}

		}
		$arr = array("cat" => $cat, "title" => $title);

		return $arr;
	}

	function decipher($str) {

		$str = preg_replace("/[^A-Za-z0-9 .$,]/", '', $str);
		$str = str_ireplace("�", "", $str);
		$str = str_ireplace("....", "", $str);
		$str = str_ireplace("b    ", "", $str);
		$str = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $str);
		$str = trim($str);
		if (trim($str) == "") {
			return false;
		}

		$ignores = array("Prop65");

		if ($this->strposa($str, $ignores) !== false) {
			return false;
		}
		return $str;

	}

	function strposa($haystack, $needles = array(), $offset = 0) {
		$chr = array();
		foreach ($needles as $needle) {
			$res = strpos($haystack, $needle, $offset);
			if ($res !== false) {
				$chr[$needle] = $res;
			}

		}
		if (empty($chr)) {
			return false;
		}

		return min($chr);
	}

	function getHTMLDataFrom($supplier, $id) {
		if ($supplier == "SS") {
			$url = "https://www.slsarts.com/viewitem.asp?slssku=${id}";
			$imgbase = "https://www.slsarts.com/";
		} else {
			die("<h3>Output</h3><pre>" . print_r("NO SUPPLIER", 1) . "</pre>");
		}

		$html = file_get_html($url);
		$test = $html->find('table', 0);
		if (!$test) {
			return null;
		}

		return $html;

	}

	function getfix() {

		$q = "select * from jt_supplier_data where data=''";
		$r = $this->db->query($q);
		$re = $r->result();
		$r->free_result();

		$out = array();
		foreach ($re as $row) {
			$row->tmp_data = json_decode($row->tmp_data);
			$out[] = $row;
		}

		die(json_encode($out));
	}

	function deletextra($sku) {
		$this->db->query('delete from jt_supplier_data where sku="' . $sku . '" and data=""');
		die("<h3>Output</h3><pre>" . print_r("ok", 1) . "</pre>");
	}

	function getSupplierData($supplier, $id) {
		//phpinfo();

		$tries = 1;

		$q = "select * from jt_supplier_data where sku='$id' order by data desc";
		$result = $this->db->query($q)->result();
		$exists = count($result) > 0;

		if ($exists) {
			$out = array();
			$out['id'] = $id;
			$out['exists'] = 1;
			if ($result[0]->data != '') {
				$out['origid'] = $this->input->post('origid');
			}

			if ($this->input->post('oneoff') == 1) {
				$this->db->query('delete from jt_supplier_data where sku="' . $this->input->post('origid') . '" and data=""');
			}

			die(json_encode($out));
		}

		$html = $this->getHTMLDataFrom($supplier, $id);

		if (!$html) {

			$out = array();
			$out['id'] = $id;
			$out['nodata'] = 1;

			if ($this->input->post('oneoff') == 1) {
				$this->db->query('delete from jt_supplier_data where sku="' . $this->input->post('origid') . '" and data=""');
			}

			/*$in = array(
					"sku" => $id,
					"data" => "",
					"created" => date("Y-m-d H:i:s"),
					"supplier" => $supplier,
				);

			*/
			die(json_encode($out));
		}

		//$this->db->query("delete from jt_supplier_data where sku='$id'");

		if ($supplier == "SS") {
			$url = "https://www.slsarts.com/viewitem.asp?slssku=${id}";
			$imgbase = "https://www.slsarts.com/";
		}
		/*die("<h3>Output</h3><pre>" . print_r($html, 1) . "</pre>");
		$html = file_get_html($url);*/

		$out = array();
		$out['id'] = $id;
		$t = $html->find('h3', 0);
		if ($t) {
			$t = $t->innertext;
		} else {
			$t = $html->find('td.gridbtns', 0);
			if (!$t) {
				$t = "";
			} else {
				$t = $t->innertext;
				$t = explode("<br>", $t);
				$t = ucwords($t[count($t) - 1]);
			}
		}
		$out['title'] = preg_replace('/[\x00-\x1F\x7F]/u', '', $t);

		$desc = $html->find('td.gridleft', 0)->innertext;
		$desc = str_replace("\r", "", $desc);
		$desc = str_replace("\n", "", $desc);
		$desc = preg_replace('#<h3>(.*?)</h3>#', '', $desc, 1);
		$desc = preg_replace('/(<font[^>]*>)|(<\/font>)/', '', $desc);
		$out['description'] = $desc;

		$img = $html->find('.gridcenter img')->src;
		@$imge = $html->find('.gridcenter img')->onerror;
		if ($imge && strpos($imge, "rimgsku(this") !== false) {
			//holy crap we gotta deal with this shit
			$imge = str_replace("rimgsku(this,'", "", $imge);
			$imge = str_replace("');", "", $imge);

			$imge = str_replace("rimgsku(this,'", "", $imge);
			$imge = str_replace("');", "", $imge);

			$img = "/images/" . $imge;
			$img = str_replace("/images/images/", "/images/", $img);
		}

		$img = str_replace("\\", "/", $img);
		$img = str_replace("./", "", $img);
		//$img = str_replace("/images/", "", $img);
		$img = str_replace("Regular Images", "Large Images", $img);

		$oimg = $img;
		if (strpos($oimg, "/") === 0) {
			$oimg = substr($img, 1);
		}

		$oimg = str_replace(" ", "", strtolower($oimg));
		$oimg = str_replace("/", "-", strtolower($oimg));
		$oimg = str_replace("productimages-", "", $oimg);
		$oimg = str_replace("largeimages-", "", $oimg);

		$img = $imgbase . $img;

		// test and save image

		$hasimg = $this->getImage($img, $oimg);

		if (!$hasimg) {
			$oimg = "";
		}

		$out['img'] = $oimg;
		$out['orig_img'] = $img;
		$out['linedata'] = $this->input->post('linedata');

		$d = $this->input->post('data_batch');
		if (!$d) {
			$d = 'retry';
		}

		$out = json_encode($out);
		$in = array(
			"sku" => $id,
			"data" => $out,
			"data_batch" => $d,
			"created" => date("Y-m-d H:i:s"),
			"supplier" => $supplier,
		);
		if ($this->input->post('title')) {
			$in['title'] = $this->input->post('title');
		}
		if ($this->input->post('price')) {
			$in['price'] = str_replace("$", "", $this->input->post('price'));
			$in['category'] = $this->input->post('category');
			$in['approved'] = 1;

		}

		if ($this->db->insert("jt_supplier_data", $in)) {
			if ($this->input->post('oneoff') == 1) {
				$this->db->query('delete from jt_supplier_data where sku="' . $this->input->post('origid') . '" and data=""');
			}
		}

		die($out);

	}

	function getMyCategoryFromLinkData($ldata) {

		$cats = $this->getLiveCats();
		$catref = array();
		foreach ($cats as $cat) {
			$catref[strtolower($cat->name)] = $cat->term_id;
		}

		$cat = $ldata->struc;
		foreach ($cats as $thecat) {
			if (strtolower($thecat->name) == strtolower($cat[1])) {
				return array("mycat" => $thecat->name, "mycatid" => $thecat->term_id);

			}
		}
		die("<h3>Output</h3><pre>" . print_r($ldata, 1) . "</pre>");
		return array();

	}
	function returnItemsFromLinkData($ldata, $catdata) {
		$mycat = $catdata['mycat'];
		$mycatid = $catdata['mycatid'];
		$ic = 0;
		$upp = array();
		$upps = array();
		foreach ($ldata->data as $item) {
			$ic++;

			if ($ic == 1) {
				$sku = $item;
			}

			if ($ic == 2) {
				$upp['title'] = $item;
			}

			if ($ic == 3) {
				$upp['upc'] = $item;
			}

			if ($ic % 7 == 0) {

				$upp['price'] = str_replace("$", "", $item);
				$upp['category'] = $mycat;
				$upp['cat_id'] = $mycatid;
				$upp['sku'] = $sku;
				$upps[] = $upp;
				/*$this->db->update('jt_supplier_data', $upp, array("sku" => $sku));
					echo ("<h3>updated</h3><pre>" . print_r($this->db->affected_rows(), 1) . "</pre>");
*/
				$upp = array();
				$sku = '';
				$ic = 0;
			}

		}
		return $upps;

	}

	function fixupcs() {
		$q = "select * from jt_supplier_data where upc='' and triedlink=0 and category!='' limit 3 ";

		$r = $this->db->query($q)->result();
		if (count($r) == 0) {
			die(json_encode(array("complete" => 1)));
		}
		foreach ($r as $row) {
			$q = "select * from linkys where data like '%" . $row->sku . "%'";
			//echo "<P>$q";
			$u = array('triedlink' => 1);
			$this->db->update('jt_supplier_data', $u, array("id" => $row->id));

			$rr = $this->db->query($q)->result();

			if (count($rr) > 0) {
				$lrow = $rr[0];
				$ldata = json_decode($lrow->data);

				//get category
				$category = $this->getMyCategoryFromLinkData($ldata);
				//$category = array("mycat" => $row->category, "mycatid" => $row->cat_id);
				$objects = $this->returnItemsFromLinkData($ldata, $category);

				foreach ($objects as $item) {

					//echo ("<h3>Output</h3><pre>" . print_r($item['upc'] . " == " . $row->upc, 1) . "</pre>");
					//continue;

					if ($item['sku'] != $row->sku) {
						continue;
					}

					/*$qq = "select * from jt_supplier_data where sku='{$item['sku']}'";
						$rrr = $this->db->query($qq)->result();
						if (count($rrr) > 0) {
							$u = array('triedlink' => 1);
							$this->db->update('jt_supplier_data', $u, array("id" => $row->id));
							die(json_encode(array("done" => 1, "exists" => 1)));

							//echo "<P>SKU Exists " . $item['sku'];
							//echo ("<h3>Output</h3><pre>" . print_r($rrr, 1) . "</pre>");
							continue;
					*/
					//echo ("<h3>Output</h3><pre>" . print_r($category, 1) . print_r($item, 1) . "</pre>");
					//$q = $this->db->

					$html = $this->getHTMLDataFrom("SS", $item['sku']);

					if (!$html) {

						echo "<P>----NO HTML " . print_r($item, 1);
						continue;
					}

					//$this->db->query("delete from jt_supplier_data where sku='$id'");

					$imgbase = "https://www.slsarts.com/";

					$t = $html->find('h3', 0);
					if ($t) {
						$t = $t->innertext;
					} else {
						$t = $html->find('td.gridbtns', 0);
						if (!$t) {
							$t = "";
						} else {
							$t = $t->innertext;
							$t = explode("<br>", $t);
							$t = ucwords($t[count($t) - 1]);
						}
					}
					//$out['title'] = preg_replace('/[\x00-\x1F\x7F]/u', '', $t);

					$desc = $html->find('td.gridleft', 0)->innertext;
					$desc = str_replace("\r", "", $desc);
					$desc = str_replace("\n", "", $desc);
					$desc = preg_replace('#<h3>(.*?)</h3>#', '', $desc, 1);
					$desc = preg_replace('/(<font[^>]*>)|(<\/font>)/', '', $desc);
					$item['description'] = $desc;

					$img = $html->find('.gridcenter img')->src;
					@$imge = $html->find('.gridcenter img')->onerror;
					if ($imge && strpos($imge, "rimgsku(this") !== false) {
						//holy crap we gotta deal with this shit
						$imge = str_replace("rimgsku(this,'", "", $imge);
						$imge = str_replace("');", "", $imge);

						$imge = str_replace("rimgsku(this,'", "", $imge);
						$imge = str_replace("');", "", $imge);

						$img = "/images/" . $imge;
						$img = str_replace("/images/images/", "/images/", $img);
					}

					$img = str_replace("\\", "/", $img);
					$img = str_replace("./", "", $img);
					//$img = str_replace("/images/", "", $img);
					$img = str_replace("Regular Images", "Large Images", $img);

					$oimg = $img;
					if (strpos($oimg, "/") === 0) {
						$oimg = substr($img, 1);
					}

					$oimg = str_replace(" ", "", strtolower($oimg));
					$oimg = str_replace("/", "-", strtolower($oimg));
					$oimg = str_replace("productimages-", "", $oimg);
					$oimg = str_replace("largeimages-", "", $oimg);

					$img = $imgbase . $img;

					// test and save image

					$hasimg = $this->getImage($img, $oimg);

					if (!$hasimg) {
						$oimg = "";
					}
					$item['image'] = $oimg;
					$item['orig_img'] = $img;
					$item['approved'] = 1;
					$item['data'] = $row->tmp_data;
					$item['triedlink'] = 1;
					//echo ("<h3>Output</h3><pre>" . print_r($item, 1) . "</pre>");
					//continue;

					$up = $this->db->update("jt_supplier_data", $item, array("id" => $row->id));
					if (!$up) {
						die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
					}
					//die("<h3>Output</h3><pre>" . print_r($item, 1) . print_r($row, 1) . "</pre>");

				}

			}
			//echo ("<h3>Output</h3><pre>" . print_r($rr, 1) . "</pre>");
			continue;

		}
		die(json_encode(array("done" => 1, "rowid" => $row->id)));

		die("<h3>Output</h3><pre>" . print_r("DONE", 1) . "</pre>");
	}

	function newupdate() {
		$q = "select * from jt_supplier_data where moved=0 and triedlink";
	}

	function updater() {
		$titles = array();
		$q = "select * from linkys where data!='' and mined=1 and moved=0 ";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();
		$a = array();

		$cats = $this->getLiveCats();
		$catref = array();
		foreach ($cats as $cat) {
			$catref[strtolower($cat->name)] = $cat->term_id;
		}

		foreach ($r as $row) {
			$data = json_decode($row->data);

			$thecat = $this->getMyCategoryFromLinkData($ldata);
			$mycat = $thecat['name'];
			$mycatid = $thecat['term_id'];

			//$mycatid = $catref[strtolower($mycat)];

			$pdata = $data->data;

			$ic = 0;
			$upp = array();
			foreach ($pdata as $item) {
				$ic++;

				if ($ic == 1) {
					$sku = $item;
				}

				if ($ic == 3) {
					$upp['upc'] = $item;
				}

				if ($ic % 7 == 0) {

					$upp['price'] = str_replace("$", "", $item);
					$upp['category'] = $mycat;
					$upp['cat_id'] = $mycatid;
					$this->db->update('jt_supplier_data', $upp, array("sku" => $sku));
					echo ("<h3>updated</h3><pre>" . print_r($this->db->affected_rows(), 1) . "</pre>");

					$upp = array();
					$sku = '';
					$ic = 0;
				}

			}

			//echo ("<h3>Output</h3><pre>" . print_r($catdata, 1) . print_r($pdata, 1) . "</pre>");
		}

		die("<h3>Output</h3><pre>" . print_r("DONE", 1) . "</pre>");

		foreach ($titles as $cat => $subcats) {
			$uid = $catref[strtolower($cat)];
			if (!$uid) {
				die("<h3>Output</h3><pre>" . print_r("NO UID", 1) . "</pre>");
			}
			$order = 0;
			$subids = array();
			foreach ($subcats as $sub) {
				$nt = strtolower($sub);
				$slug = str_replace(" ", "-", $nt);
				$nt = ucwords($nt);
				$nt = str_replace("And ", "and ", $nt);
				$in = array("name" => $nt, "slug" => $slug);
				$this->db->insert("wp_terms", $in);
				$term_id = $this->db->insert_id();
				$subids[] = $term_id;

				$in = array("term_id" => $term_id, "meta_key" => "order", "meta_value" => $order);
				$this->db->insert("wp_termmeta", $in);

				$in = array("term_id" => $term_id, "meta_key" => "display_type", "meta_value" => "products");
				$this->db->insert("wp_termmeta", $in);

				$in = array("term_id" => $term_id, "meta_key" => "thumbnail_id", "meta_value" => "0");
				$this->db->insert("wp_termmeta", $in);

				$in = array("term_id" => $term_id, "taxonomy" => "product_cat", "parent" => $uid);
				$this->db->insert("wp_term_taxonomy", $in);
				$order++;
			}

			$a[$uid] = $subids;

		}

		$a = serialize($a);
		$in = array("option_value" => $a);
		$this->db->update("wp_options", $in, array("option_id" => 104590));

		die("<h3>Output</h3><pre>" . print_r($titles, 1) . "</pre>");
	}

	function updatercats() {
		$titles = array();
		$q = "select * from linkys where data!='' and mined=1 and moved=0 ";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();
		$a = array();

		foreach ($r as $row) {
			$data = json_decode($row->data);
			$cat = $data->struc;

			if (!array_key_exists($cat[0], $titles)) {
				$titles[$cat[0]] = array($cat[1]);
			} else {
				if (!in_array($cat[1], $titles[$cat[0]])) {
					$titles[$cat[0]][] = $cat[1];
				}
			}

			continue;
			$pdata = $data->data;

			echo ("<h3>Output</h3><pre>" . print_r($catdata, 1) . print_r($pdata, 1) . "</pre>");
		}

		$cats = $this->getLiveCats();
		$catref = array();
		foreach ($cats as $cat) {
			$catref[strtolower($cat->name)] = $cat->term_id;
		}

		foreach ($titles as $cat => $subcats) {
			$uid = $catref[strtolower($cat)];
			if (!$uid) {
				die("<h3>Output</h3><pre>" . print_r("NO UID", 1) . "</pre>");
			}
			$order = 0;
			$subids = array();
			foreach ($subcats as $sub) {
				$nt = strtolower($sub);
				$slug = str_replace(" ", "-", $nt);
				$nt = ucwords($nt);
				$nt = str_replace("And ", "and ", $nt);

				$in = array("name" => $nt, "slug" => $slug);
				$this->db->insert("wp_terms", $in);
				$term_id = $this->db->insert_id();
				$subids[] = $term_id;

				$in = array("term_id" => $term_id, "meta_key" => "order", "meta_value" => $order);
				$this->db->insert("wp_termmeta", $in);

				$in = array("term_id" => $term_id, "meta_key" => "display_type", "meta_value" => "products");
				$this->db->insert("wp_termmeta", $in);

				$in = array("term_id" => $term_id, "meta_key" => "thumbnail_id", "meta_value" => "0");
				$this->db->insert("wp_termmeta", $in);

				$in = array("term_id" => $term_id, "taxonomy" => "product_cat", "parent" => $uid);
				$this->db->insert("wp_term_taxonomy", $in);
				$order++;
/**/
			}

			$a[$uid] = $subids;

		}

		$a = serialize($a);
		$in = array("option_value" => $a);

		//$this->db->update("wp_options", $in, array('option_name' => 'product_cat_children'));

		//$this->db->update("wp_options", array("option_value" => $d), array('option_name' => 'product_cat_children'));

		die("<h3>Output</h3><pre>" . print_r($titles, 1) . "</pre>");
	}

	/*&

		$r = $this->db->query("select * from linkys where mined=0 and link!='' and  tm='' limit 30 ")->result();
			if (count($r) == 0) {
				die(json_encode(array("complete" => 1)));
			}
			foreach ($r as $el) {
				$file = $el->link;
				$file = str_replace(" ", "%20", $file);
				//$file = str_replace("fright_itemlist.asp", "defaultFrame.asp", $file);
				$u = "https://www.slsarts.com/$file";

				$html = file_get_html($u);

				// get the cat structure
				$struc = array();
				$a = $html->find("a");
				foreach ($a as $alink) {
					$struc[] = trim(str_replace("\r\n", "", $alink->innertext));
				}

				$data = array();
				$cells = $html->find('table td');
				foreach ($cells as $cell) {
					$h = trim($cell->innertext);
					$h = trim(str_replace("\r\n", "", strip_tags($h)));
					if ($h != "") {
						$data[] = $h;

						if ($h == "MSRP") {
							$data = array();
						}

					}
				}

				$up = array("data" => json_encode(array("struc" => $struc, "data" => $data)), "mined" => 1);
				$this->db->update("linkys", $up, array("id" => $el->id));

			}
			die(json_encode(array("done" => 1)));

	*/

	/*a:12:{i:0;a:17:{i:0;i:866;i:1;i:867;i:2;i:868;i:3;i:869;i:4;i:870;i:5;i:871;i:6;i:872;i:7;i:873;i:8;i:874;i:9;i:875;i:10;i:876;i:11;i:877;i:12;i:878;i:13;i:879;i:14;i:880;i:15;i:881;i:16;i:882;}i:1;a:15:{i:0;i:883;i:1;i:884;i:2;i:885;i:3;i:886;i:4;i:887;i:5;i:888;i:6;i:889;i:7;i:890;i:8;i:891;i:9;i:892;i:10;i:893;i:11;i:894;i:12;i:895;i:13;i:896;i:14;i:897;}i:2;a:29:{i:0;i:898;i:1;i:899;i:2;i:900;i:3;i:901;i:4;i:902;i:5;i:903;i:6;i:904;i:7;i:905;i:8;i:906;i:9;i:907;i:10;i:908;i:11;i:909;i:12;i:910;i:13;i:911;i:14;i:912;i:15;i:913;i:16;i:914;i:17;i:915;i:18;i:916;i:19;i:917;i:20;i:918;i:21;i:919;i:22;i:920;i:23;i:921;i:24;i:922;i:25;i:923;i:26;i:924;i:27;i:925;i:28;i:926;}i:3;a:14:{i:0;i:927;i:1;i:928;i:2;i:929;i:3;i:930;i:4;i:931;i:5;i:932;i:6;i:933;i:7;i:934;i:8;i:935;i:9;i:936;i:10;i:937;i:11;i:938;i:12;i:939;i:13;i:940;}i:4;a:39:{i:0;i:941;i:1;i:942;i:2;i:943;i:3;i:944;i:4;i:945;i:5;i:946;i:6;i:947;i:7;i:948;i:8;i:949;i:9;i:950;i:10;i:951;i:11;i:952;i:12;i:953;i:13;i:954;i:14;i:955;i:15;i:956;i:16;i:957;i:17;i:958;i:18;i:959;i:19;i:960;i:20;i:961;i:21;i:962;i:22;i:963;i:23;i:964;i:24;i:965;i:25;i:966;i:26;i:967;i:27;i:968;i:28;i:969;i:29;i:970;i:30;i:971;i:31;i:972;i:32;i:973;i:33;i:974;i:34;i:975;i:35;i:976;i:36;i:977;i:37;i:978;i:38;i:979;}i:5;a:15:{i:0;i:980;i:1;i:981;i:2;i:982;i:3;i:983;i:4;i:984;i:5;i:985;i:6;i:986;i:7;i:987;i:8;i:988;i:9;i:989;i:10;i:990;i:11;i:991;i:12;i:992;i:13;i:993;i:14;i:994;}i:6;a:26:{i:0;i:995;i:1;i:996;i:2;i:997;i:3;i:998;i:4;i:999;i:5;i:1000;i:6;i:1001;i:7;i:1002;i:8;i:1003;i:9;i:1004;i:10;i:1005;i:11;i:1006;i:12;i:1007;i:13;i:1008;i:14;i:1009;i:15;i:1010;i:16;i:1011;i:17;i:1012;i:18;i:1013;i:19;i:1014;i:20;i:1015;i:21;i:1016;i:22;i:1017;i:23;i:1018;i:24;i:1019;i:25;i:1020;}i:7;a:49:{i:0;i:1021;i:1;i:1022;i:2;i:1023;i:3;i:1024;i:4;i:1025;i:5;i:1026;i:6;i:1027;i:7;i:1028;i:8;i:1029;i:9;i:1030;i:10;i:1031;i:11;i:1032;i:12;i:1033;i:13;i:1034;i:14;i:1035;i:15;i:1036;i:16;i:1037;i:17;i:1038;i:18;i:1039;i:19;i:1040;i:20;i:1041;i:21;i:1042;i:22;i:1043;i:23;i:1044;i:24;i:1045;i:25;i:1046;i:26;i:1047;i:27;i:1048;i:28;i:1049;i:29;i:1050;i:30;i:1051;i:31;i:1052;i:32;i:1053;i:33;i:1054;i:34;i:1055;i:35;i:1056;i:36;i:1057;i:37;i:1058;i:38;i:1059;i:39;i:1060;i:40;i:1061;i:41;i:1062;i:42;i:1063;i:43;i:1064;i:44;i:1065;i:45;i:1066;i:46;i:1067;i:47;i:1068;i:48;i:1069;}i:8;a:25:{i:0;i:1070;i:1;i:1071;i:2;i:1072;i:3;i:1073;i:4;i:1074;i:5;i:1075;i:6;i:1076;i:7;i:1077;i:8;i:1078;i:9;i:1079;i:10;i:1080;i:11;i:1081;i:12;i:1082;i:13;i:1083;i:14;i:1084;i:15;i:1085;i:16;i:1086;i:17;i:1087;i:18;i:1088;i:19;i:1089;i:20;i:1090;i:21;i:1091;i:22;i:1092;i:23;i:1093;i:24;i:1094;}i:9;a:23:{i:0;i:1095;i:1;i:1096;i:2;i:1097;i:3;i:1098;i:4;i:1099;i:5;i:1100;i:6;i:1101;i:7;i:1102;i:8;i:1103;i:9;i:1104;i:10;i:1105;i:11;i:1106;i:12;i:1107;i:13;i:1108;i:14;i:1109;i:15;i:1110;i:16;i:1111;i:17;i:1112;i:18;i:1113;i:19;i:1114;i:20;i:1115;i:21;i:1116;i:22;i:1117;}i:10;a:15:{i:0;i:1118;i:1;i:1119;i:2;i:1120;i:3;i:1121;i:4;i:1122;i:5;i:1123;i:6;i:1124;i:7;i:1125;i:8;i:1126;i:9;i:1127;i:10;i:1128;i:11;i:1129;i:12;i:1130;i:13;i:1131;i:14;i:1132;}i:11;a:22:{i:0;s:4:"1134";i:1;s:4:"1135";i:2;s:4:"1136";i:3;s:4:"1137";i:4;s:4:"1138";i:5;s:4:"1139";i:6;s:4:"1140";i:7;s:4:"1141";i:8;s:4:"1142";i:9;s:4:"1143";i:10;s:4:"1144";i:11;s:4:"1145";i:12;s:4:"1146";i:13;s:4:"1147";i:14;s:4:"1148";i:15;s:4:"1149";i:16;s:4:"1150";i:17;s:4:"1151";i:18;s:4:"1152";i:19;s:4:"1153";i:20;s:4:"1154";i:21;s:4:"1155";}}
			*/

	/*function fixone() {
		$q = "select * from wp_terms where term_id>1133";
		$r = $this->db->query($q)->result();
		$s = array('1133' => array());
		foreach ($r as $row) {
			$s['1133'][] = $row->term_id;
		}
		$q = "select * from wp_options where option_name='product_cat_children'";
		$o = $this->db->query($q)->row();
		$d = unserialize($o->option_value);
		$d['1133'] = $s['1133'];
		die("<h3>Output</h3><pre>" . print_r($d, 1) . "</pre>");
		$d = serialize($d);
		$this->db->update("wp_options", array("option_value" => $d), array('option_name' => 'product_cat_children'));

	}*/

	function mine() {
		$r = $this->db->query("select * from linkys where mined=0 and link!='' and  tm='' limit 30 ")->result();
		if (count($r) == 0) {
			//die(json_encode(array("donwithelinks" => 1)));
		}
		foreach ($r as $el) {
			$file = $el->link;
			$file = str_replace(" ", "%20", $file);
			//$file = str_replace("fright_itemlist.asp", "defaultFrame.asp", $file);
			$u = "https://www.slsarts.com/$file";

			$html = file_get_html($u);

			// get the cat structure
			$struc = array();
			$a = $html->find("a");
			foreach ($a as $alink) {
				$struc[] = trim(str_replace("\r\n", "", $alink->innertext));
			}

			$data = array();
			$cells = $html->find('table td');
			foreach ($cells as $cell) {
				$h = trim($cell->innertext);
				$h = trim(str_replace("\r\n", "", strip_tags($h)));
				if ($h != "") {
					$data[] = $h;

					if ($h == "MSRP") {
						$data = array();
					}

				}
			}

			$up = array("data" => json_encode(array("struc" => $struc, "data" => $data)), "mined" => 1);
			$this->db->update("linkys", $up, array("id" => $el->id));

		}
		// to get link...
		$r = $this->db->query("select * from linkys where mined=0 and link='' and  tm!=''")->result();
		foreach ($r as $el) {
			$file = $el->tm;
			$u = "https://www.slsarts.com/$file";
			//echo "<P>U: " . $u;
			$hstr = file_get_contents($u);
			//die("<h3>Output</h3><pre>" . print_r($html, 1) . "</pre>");
			$items = $this->getItemsFromStr($hstr);
			if (!$items || (!$items['items'] && !$items['links'])) {

				continue;
			}

			foreach ($items['items'] as $item) {
				$this->db->insert("linkys", array("tm" => $item));
			}
			foreach ($items['links'] as $item) {
				$this->db->insert("linkys", array("link" => $item));
			}

			$this->db->update("linkys", array("mined" => 1), array("id" => $el->id));
		}
		die(json_encode(array("done" => 1)));

		die("<h3>Output</h3><pre>" . print_r("DONE - items: " . count($items['items']) . " LInks:" . count($items['links']), 1) . "</pre>");
	}

	function grail() {

		$js = array();
		$links = array();
		$cats = $this->getLiveCats();
		foreach ($cats as $cat) {
			if ($cat->name != 'Books') {
				continue;
			}

			$cat->name = "CLAYS AND Accessories";
			// navigate...
			$ucat = urlencode(strtoupper($cat->name));
			$turl = "https://www.slsarts.com/fright.asp?level1=$ucat";
			echo "<P>$turl";
			$html = file_get_html($turl);
			//die("<h3>Output</h3><pre>" . print_r($html, 1) . "</pre>");

			if (!$html) {
				echo "<P>----NO HTML";
				continue;

			}
			$hstr = $html->plaintext;

			$items = $this->getItemsFromStr($hstr);
			//die("<h3>Output</h3><pre>" . print_r($items, 1) . print_r($hstr, 1) . "</pre>");
			if (!$items || (!$items['items'] && !$items['links'])) {
				continue;
			}

			foreach ($items['items'] as $item) {
				$this->db->insert("linkys", array("tm" => $item));
			}
			foreach ($items['links'] as $item) {
				$this->db->insert("linkys", array("link" => $item));
			}

			//	$links = array_merge($links, $items['links']);

		}

		return;

		//echo ("<h3>Output</h3><pre>" . print_r($js, 1) . "</pre>");
		$njs = array();
		foreach ($js as $file) {
			$u = "https://www.slsarts.com/$file";
			//echo "<P>U: " . $u;
			$hstr = file_get_contents($u);
			//die("<h3>Output</h3><pre>" . print_r($html, 1) . "</pre>");
			$items = $this->getItemsFromStr($hstr);
			if (!$items || (!$items['items'] && !$items['links'])) {

				continue;
			}

			foreach ($items['items'] as $item) {
				if (strpos($item, "tm/tm") !== false) {
					$njs[] = $item;
				}
			}
			$links = array_merge($links, $items['links']);

		}

		$nnjs = array();

		foreach ($njs as $file) {
			$u = "https://www.slsarts.com/$file";
			//echo "<P>U: " . $u;
			$hstr = file_get_contents($u);
			//die("<h3>Output</h3><pre>" . print_r($html, 1) . "</pre>");
			$items = $this->getItemsFromStr($hstr);
			if (!$items || (!$items['items'] && !$items['links'])) {

				continue;
			}

			foreach ($items['items'] as $item) {
				if (strpos($item, "tm/tm") !== false) {
					$nnjs[] = $item;
				}
			}
			$links = array_merge($links, $items['links']);

		}

		echo ("<h3>Output</h3><pre>" . print_r($links, 1) . "</pre>");
		echo ("<h3>Output</h3><pre>" . print_r($nnjs, 1) . "</pre>");

	}

	function getItemsFromStr($hstr) {
		$sch = "var tmenuItems = [";

		$n = explode($sch, $hstr);
		if (count($n) < 2) {
			return false;
		}

		$n = $n[1];
		$n = explode("];", $n);
		$n = $n[0];
		$items = explode('",', $n);
		$ni = array();
		$links = array();
		foreach ($items as $item) {
			$item = trim(str_replace('"', "", $item));
			if (strpos($item, "level2=") !== false) {
				$links[] = $item;
				//die("<h3>Output</h3><pre>" . print_r($items, 1) . "</pre>");

			} else if (strpos($item, "tm/tm") !== false) {
				$ni[] = $item;
			}
		}
		return array("items" => $ni, "links" => $links);

	}

	function getdupes($go = 0) {
		//$this->db->query("delete from jt_supplier_data where sku='A1ternate1D' or sku='disc' or sku='Fixed' or sku='Location' or sku='Multiplier' or sku='QCom' or sku='Supp1ier2'");
		$q = "SELECT id, title,  COUNT(title) as ttl FROM jt_supplier_data where approved=1 and title !='' GROUP BY title HAVING COUNT(title) > 1";
		$r = $this->db->query($q)->result();
		foreach ($r as $row) {
			echo "<p>#" . $row->ttl . " // id: " . $row->id . ": " . $row->title;
			$ttl = addslashes($row->title);
			$q = "select * from jt_supplier_data where title='$ttl' order by price desc";
			$rr = $this->db->query($q)->result();
			$theprice = '';
			foreach ($rr as $srow) {
				echo "<P>$" . $srow->price . " s:" . $srow->sku . ": " . $srow->title;

				$html = $this->getHTMLDataFrom("SS", $srow->sku);

				if (!$html) {
					echo "<P>No HTML";
					continue;
				}

				//$this->db->query("delete from jt_supplier_data where sku='$id'");
				$supplier = "SS";
				/*if ($supplier == "SS") {
					$url = "https://www.slsarts.com/viewitem.asp?slssku=${$rr->sku}";
					$imgbase = "https://www.slsarts.com/";
				}*/
				/*die("<h3>Output</h3><pre>" . print_r($html, 1) . "</pre>");
		$html = file_get_html($url);*/

				$out = array();

				$t = $html->find('td.gridbtns', 0);
				if (!$t) {
					$t = "";
				} else {
					$t = $t->innertext;
					$t = explode("<br>", $t);
					$t = ucwords($t[count($t) - 1]);
				}

				$title = preg_replace('/[\x00-\x1F\x7F]/u', '', $t);

				$title = str_replace("CARDED", "", $title);
				$up = array("title" => $title, "ttlchecked" => 1, "approved" => 0);
				if (strpos($title, "CANVAS") !== false) {
					$up['category'] = "Canvas and Surfaces";
					echo "<P>?-- changing category...";
				}

				if (strpos($title, "CLAY") !== false) {
					$up['category'] = "Clay and Accessories";
					echo "<P>?-- changing category...";
				}

				if (($srow->price != '' && $srow->price != '0')) {
					$up['approved'] = 1;
				}
				echo "<P>new title: " . $title . " -- (OLD: " . $srow->title . ") - $" . $srow->price . " - C:" . $srow->category . " - A:" . $up['approved'];
				if ($go) {
					$this->db->update("jt_supplier_data", $up, array("id" => $srow->id));
				}

			}

			//$out[] = $in;
		}

		die("<h3>Output</h3><pre>done</pre>");

		// DA160
		// DAl60

	}

	function saveForLater() {
		//die("<h3>Output</h3><pre>" . print_r($_POST, 1) . "</pre>");
		$id = $this->input->post('id');
		$this->db->query('update jt_supplier_data set do_later=1 where id=' . $id);
		echo "OK";

	}

	function saveApprove() {
		$id = $this->input->post('id');
		$price = $this->input->post('price');
		$title = $this->input->post('title');
		$category = $this->input->post('category');
		$row = $this->db->query("select * from jt_supplier_data where id=$id")->row();
		$data = json_decode($row->data);
		$data->price = $price;
		$data->category = $category;

		$up = array(
			"title" => $title,
			"price" => $price,
			"category" => $category,
			"approved" => 1,
		);
		$this->db->update("jt_supplier_data", $up, array("id" => $id));
		die(json_encode(array(
			"ok" => 1)));
		die("<h3>Output</h3><pre>" . print_r($data, 1) . "</pre>");
	}

	function getImage($url, $img) {
		$url = trim($url);
		$url = str_replace(" ", "%20", $url);
		$img = str_replace(" ", "-", $img);
		$saveto = $img; //$_SERVER['DOCUMENT_ROOT'] . "/helper/uploads/{$img}";
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

		$raw = curl_exec($ch);
		curl_close($ch);
		if ($raw !== false) {
			if (file_exists($saveto)) {
				unlink($saveto);
			}
			$fp = fopen($saveto, 'x');
			fwrite($fp, $raw);
			fclose($fp);
			return true;
		} else {
			return false;
		}
	}

	function doonce() {
		$q = "select * from jt_supplier_data where data!='' and approved=1 and (title='' or description='' or image='') order by category asc, suggestedprice desc, price asc ";
		$r = $this->db->query($q);
		$rr = $r->result();
		$r->free_result();
		$out = array();
		foreach ($rr as $row) {
			$new = array();
			$row->data = json_decode($row->data);
			if (!$row->title && $row->data->title != '') {
				$new['title'] = $row->data->title;
			}

			if (!$row->price && isset($row->data->price)) {
				$new['price'] = $row->data->price;
			}

			if (!$row->description && $row->data->description != '') {
				$new['description'] = $row->data->description;
			}

			if (!$row->image && isset($row->data->img) && $row->data->img != '') {

				// test and save image
				echo "<P>is file:" . is_file($this->temp_img_dir . $row->data->img);
				if (!is_file($this->temp_img_dir . $row->data->img)) {
					$hasimg = $this->getImage($row->data->orig_img, $row->data->img);
				}
				$new['image'] = $row->data->img;
				//$hasimg = $this->getImage($row->data->orig_img, $row->data->img);
				//die("<h3>Output</h3><pre>" . print_r($row->data->img . " -" . $row->data->orig_img, 1) . "</pre>");
			}

			/*if (!$row->category && $row->data->category != '') {
				$new['category'] = $row->data->category;
			}*/
			if (count($new) == 0) {
				continue;
			} else {
				$up = $this->db->update("jt_supplier_data", $new, array("id" => $row->id));
				if (!$up) {

					die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
				}
				echo "<hr>updated with: <br><pre>" . print_r($new, 1) . "</pre>";
			}

		}
	}

	function tryit($go = 0) {
		$t = array("Grumbacher");

		foreach ($t as $tt) {
			$r = $this->db->query("Select * from jt_supplier_data where trim(title) like'$tt%' and ttlchecked=0 limit 100")->result();
			foreach ($r as $rr) {

				$html = $this->getHTMLDataFrom("SS", $rr->sku);

				if (!$html) {
					echo "<P>No HTML";
					continue;
				}

				//$this->db->query("delete from jt_supplier_data where sku='$id'");
				$supplier = "SS";
				/*if ($supplier == "SS") {
					$url = "https://www.slsarts.com/viewitem.asp?slssku=${$rr->sku}";
					$imgbase = "https://www.slsarts.com/";
				}*/
				/*die("<h3>Output</h3><pre>" . print_r($html, 1) . "</pre>");
		$html = file_get_html($url);*/

				$out = array();

				$t = $html->find('td.gridbtns', 0);
				if (!$t) {
					$t = "";
				} else {
					$t = $t->innertext;
					$t = explode("<br>", $t);
					$t = ucwords($t[count($t) - 1]);
				}

				$title = preg_replace('/[\x00-\x1F\x7F]/u', '', $t);

				if (strpos(strtolower($title), strtolower($tt)) === FALSE) {
					$title = $tt . " " . $title;
				}
				$up = array("title" => $title, "ttlchecked" => 1, "approved" => 0);
				if (($rr->price != '' && $rr->price != '0') || $rr->approved == 1) {
					$up['approved'] = 1;
				}
				echo "<P>new title: " . $title . " C: " . $rr->category . " -- (" . $rr->title . ") $" . $rr->price . "  A:" . $up['approved'];
				if ($go) {
					$this->db->update("jt_supplier_data", $up, array("id" => $rr->id));
				}

			}

		}
	}

	function fixthecats() {
		$t = array("CHISEL");
	}

	function fixtheimages($go = 0) {
		// get array of image names

		$f = get_filenames($this->temp_img_dir);
		$wh = array();
		foreach ($f as $ff) {
			$wh[] = "'" . $ff . "'";
		}

		$q = "select * from jt_supplier_data where image!='' and image not in (" . implode($wh, ",") . ")";
		echo $q;
	}

	function titlecheck($go = 0) {

		$r = $this->db->query("Select * from jt_supplier_data where approved=0 and title!='' and ttlchecked=0 limit 200")->result();
		foreach ($r as $rr) {

			$html = $this->getHTMLDataFrom("SS", $rr->sku);

			if (!$html) {
				echo "<P>No HTML";
				continue;
			}

			//$this->db->query("delete from jt_supplier_data where sku='$id'");
			$supplier = "SS";
			/*if ($supplier == "SS") {
					$url = "https://www.slsarts.com/viewitem.asp?slssku=${$rr->sku}";
					$imgbase = "https://www.slsarts.com/";
				}*/
			/*die("<h3>Output</h3><pre>" . print_r($html, 1) . "</pre>");
		$html = file_get_html($url);*/

			$out = array();

			$t = $html->find('td.gridbtns', 0);
			if (!$t) {
				$t = "";
			} else {
				$t = $t->innertext;
				$t = explode("<br>", $t);
				$t = ucwords($t[count($t) - 1]);
			}

			$title = preg_replace('/[\x00-\x1F\x7F]/u', '', $t);

			$title = str_replace("CARDED", "", $title);
			$up = array("title" => $title, "ttlchecked" => 1, "approved" => 0);
			if (strpos($title, "CANVAS") !== false) {
				$up['category'] = "Canvas and Surfaces";
				echo "<P>?-- changing category...";
			}

			if (strpos($title, "CLAY") !== false) {
				$up['category'] = "Clay and Accessories";
				echo "<P>?-- changing category...";
			}

			if (($rr->price != '' && $rr->price != '0') || $rr->approved == 1) {
				$up['approved'] = 1;
			}
			echo "<P>new title: " . $title . "(" . $rr->title . ") - $" . $rr->price . " - C:" . $rr->category . " - A:" . $up['approved'];
			if ($go) {
				$this->db->update("jt_supplier_data", $up, array("id" => $rr->id));
			}

		}

	}

	function getGood() {
		$q = "select count(*) as ttl from jt_supplier_data where data!='' and approved != 1 ";
		$r = $this->db->query($q);
		$rr = $r->row();
		$r->free_result();
		$data['notapproved'] = $rr->ttl;

		$q = "select count(*) as ttl from jt_supplier_data where data!='' and approved = 1 ";
		$r = $this->db->query($q);
		$rr = $r->row();
		$r->free_result();
		$data['approved'] = $rr->ttl;

		$q = "select count(*) as ttl from jt_supplier_data where data!='' and approved != 1 and do_later=1";
		$r = $this->db->query($q);
		$rr = $r->row();
		$r->free_result();
		$data['do_later'] = $rr->ttl;

		$q = "select count(*) as ttl from jt_supplier_data where data!='' and category = '' ";
		$r = $this->db->query($q);
		$rr = $r->row();
		$r->free_result();
		$data['nocat'] = $rr->ttl;

		$q = "select count(*) as ttl from jt_supplier_data where data!='' and price = 0 ";
		$r = $this->db->query($q);
		$rr = $r->row();
		$r->free_result();
		$data['noprice'] = $rr->ttl;

		$q = "select * from jt_supplier_data where data!='' and approved != 1 and do_later!=1 order by sku asc, suggestedprice desc, price asc limit 100";
		$r = $this->db->query($q);
		$rr = $r->result();
		$r->free_result();
		$out = array();
		foreach ($rr as $row) {
			$row->data = json_decode($row->data);

			$theline = $row->data->linedata;
			$theline = explode("LINE 2:", $theline);
			$thefline = $theline[0];
			$thefline = str_replace("LINE 1:", "", $thefline);
			$thefline = trim(str_replace("//", "", $thefline));

			$theline = $theline[count($theline) - 1];
			$theline = json_decode($theline);
			$dta = $this->getIDAndPrice($theline);
			$row->data->price = $dta['theprice'];
			if ($thefline) {
				$thefline = json_decode($thefline);
				$dta = $this->getTitleAndCategory($thefline);
				if ($dta && is_array($dta) && isset($dta['category'])) {
					$row->data->category = $dta['category'];
				}

			}

			if (!isset($row->data->category)) {
				$row->data->category = "";
			}

			if (!isset($row->data->price)) {
				$row->data->price = "";
			}

			// test for image
			if ($row->data->img) {
				$local = $_SERVER['DOCUMENT_ROOT'] . "/helper/uploads/" . $row->data->img;
				if (!is_file($local)) {
					//	echo "NOT";
					$row->data->img = "";
				} else {
					//	echo "<P>" . $row->data->img;
				}

			}

			// guess category
			if (!$row->data->category) {
				$row->data->category = $this->guessCategory($row->data);

				/* 	$u = array(
					"title" => $row->data->title,
					"category" => $row->data->category,
					"description" => $row->data->description,
					"price" => $row->data->price,
					"image" => $row->data->img,
				);*/
				if ($row->data->category != "") {
					$u = array(
						"category" => $row->data->category,
					);

					$this->db->update("jt_supplier_data", $u, array("id" => $row->id));
				}
			}

			$out[] = $row;
		}

		$data['good'] = $out;

		die(json_encode($data));
	}

	function guessCategory($data = array()) {
		$title = strtolower($data->title);
		$painting = array(
			"acrylic",
			"liquitex",
			"brush",
			"weber",
			"canvas",
			"easel",
			"tray",
			"linseed",
			"testor",
			"linoleum",
			"plastalina",
			"gamblin",
			"emulsion",
			"gouache",
			"oil color",
			"tempera",
			"tube",
			"jacquard",
			"spray paint",
			"m. graham",
			"scribbles",
			"daniel smith",
			"mask",
			"retarder",
			"versatex",
			"gum arabic",
			"grumbacher",
			"golden",
			"varnish",
			"decoart",
			"filler",
			"gesso",

		);

		$ceramics = array(
			"sculpey",
			"carve",
			"clay",
			"ceramic",
			"pottery",
			"sculpt",
		);

		$frames = array(
			"frame",
			"stretcher",

		);

		$drawing = array(
			"prismacolor",
			"pastel",
			"artyfacts",
			"nibs",
			"pens",
			"pencils",
			"rubber cement",
			"pentel",
			"finish spray",
			"pencil",
			"crayola",
			"ink",
			"crayon",
			"wheel",
			"marker",
			"pitt",
			"graphite",
			"sharpie",
			"calligraphy",
			"squeegee",
			"artbin",
			"faber-castell",
			"koh-",
			"eraser",
			"pen fine",
			"gel pen",
			"pen set",
			"portfolio",
			"profolio",
			"palette",
			"fixative",

		);

		$paper = array(
			"sheet",
			"pads",
			"board",
			"tracing",
			"roll",
			"paper",
			"pages",
			"doodle",
			"glassine",
			"arches",
			"strathmore",
			"tru ray",

		);

		$books = array(
			"book",
			"learn to draw",
			"journal",
			"coloring",
			"drawing:",
			"moleskin",

		);

		$kids = array(
			"kids",
			"silly",
			"face paint",

		);

		$gifts = array(
			"trading cards",
			"greeting cards",

		);

		$print = array(
			"printing",
			"hinge",
			"essential tools",
			"intermediate kit",

		);

		$drafting = array(
			"x-acto",
			"drafting",
		);
		$adhesives = array(
			"spray mount",
			"adhesive",
			"glue",
			"mounting",

		);

		$crafts = array(
			"stain",
			"foamboard",
			"foamcore",
			"tape",
			"tattoo",
			"tie dye",
			"body art",
			"yarn",
			"felt",
			"balsa",
			"basswood",
			"clip",
			"metal leaf",
			"carving",
			"burnish",

		);

		$misc = array(
			"",
		);

		$cats = $this->getCats();

		foreach ($cats as $c => $n) {
			$arr = strtolower($c);
			if (!isset($$arr)) {
				continue;
			}

			foreach ($$arr as $test) {
				if ($test && strpos($title, $test) !== false) {
					return "$n";
				}

			}

		}

		return "";
	}

	function getCategories() {
		$cats = $this->getCats();
		$out = array();
		foreach ($cats as $c => $n) {
			$out[] = $n;
		}

		sort($out);
		$out[] = "Miscellaneous";
		echo json_encode($out);
	}

	function fixCats() {
		$fix = array("Kids Corner" => "Childrens Crafts",
			"Painting Supplies" => "Paints, Mediums and Finishes",
			"Paper Supplies" => "Paper and Pads",
			"Ceramics" => "Clays and Accessories",
			"Adhesives" => "Tapes and Adhesives",
			"Craft Supplies" => "Basic Craft Supplies",
		);
		foreach ($fix as $old => $new) {
			$q = "update jt_supplier_data set category='$new' where category='$old'";
			echo "<P>$q";
			$this->db->query($q);

		}
	}

	function fixCatsWithNew($go = 0) {
		$fix = array(
			"Paints, Mediums and Finishes" => array(
				"acrylic",
				"liquitex",
				"weber",
				"linseed",
				"testor",
				"linoleum",
				"plastalina",
				"gamblin",
				"emulsion",
				"gouache",
				"oil color",
				"tempera",
				"tube",
				"jacquard",
				"spray paint",
				"m. graham",
				"scribbles",
				"daniel smith",
				"mask",
				"retarder",
				"versatex",
				"gum arabic",
				"grumbacher",
				"golden",
				"varnish",
				"decoart",
				"filler",
				"gesso",
			),
			"Airbrush Supplies" => array(
				"airbrush ",

			),

			"Brushes and Brush Care" => array(
				"brush ",
				"brushes",

			),

			"Canvas and Surface" => array(
				"canvas",
				"board",
			),
			"Art Accessories" => array(
				"tray",
				"easel",
				"bin",
			),
			"Pastels" => array(
				"pastel",
			),
			"Pens and Markers" => array(
				"nibs",
				"pen ",
				"pens",
				"marker",
			),
		);
		$ids = array();
		foreach ($fix as $cat => $keywords) {
			$key = array();
			foreach ($keywords as $k) {
				$key[] = " lower(title) like  '%" . $k . "%' ";
			}

			$q = "select * from jt_supplier_data where " . implode(" OR ", $key);
			echo "<P>$q";
			$r = $this->db->query($q)->result();
			foreach ($r as $row) {
				if (in_array($row->id, $ids)) {
					continue;
				}

				$title = strip_tags($row->title);
				echo "<P> changing " . $title . " to $cat (from " . $row->category . ")";
				$q = "update jt_supplier_data set title='" . addslashes($title) . "', category='$cat' where id={$row->id}";
				echo "<P>$q";
				$ids[] = $row->id;
				if ($go) {
					$this->db->query($q);
				}

			}
		}

		/*

			"canvas",
				"easel",
				"tray",

		*/
	}

	function fixMacCatsWithNew($go = 0) {
		$fix = array(
			"Paints, Mediums and Finishes" => array(
				"acrylic",
				"liquitex",
				"weber",
				"linseed",
				"testor",
				"linoleum",
				"plastalina",
				"gamblin",
				"emulsion",
				"gouache",
				"oil color",
				"tempera",
				"tube",
				"jacquard",
				"spray paint",
				"m. graham",
				"scribbles",
				"daniel smith",
				"mask",
				"retarder",
				"versatex",
				"gum arabic",
				"grumbacher",
				"golden",
				"varnish",
				"decoart",
				"filler",
				"gesso",
			),
			"Airbrush Supplies" => array(
				"airbrush ",

			),

			"Brushes and Brush Care" => array(
				"brush ",
				"brushes",

			),

			"Canvas and Surface" => array(
				"canvas",
				"board",
			),
			"Art Accessories" => array(
				"tray",
				"easel",
				"bin",
			),
			"Pastels" => array(
				"pastel",
			),
			"Pens and Markers" => array(
				"nibs",
				"pen ",
				"pens",
				"marker",
			),
		);
		$ids = array();
		foreach ($fix as $cat => $keywords) {
			$key = array();
			foreach ($keywords as $k) {
				$key[] = " lower(title) like  '%" . $k . "%' ";
			}

			$q = "select * from jt_mac_data where data='DONE' and site_category='' and (" . implode(" OR ", $key) . ")";
			echo "<P>$q";
			$r = $this->db->query($q)->result();
			foreach ($r as $row) {
				if (in_array($row->id, $ids)) {
					continue;
				}

				$title = strip_tags($row->title);
				echo "<P> changing " . $title . " to $cat (from " . $row->category . ")";
				$q = "update jt_mac_data set site_category='$cat' where id={$row->id}";
				echo "<P>$q";
				$ids[] = $row->id;
				if ($go) {
					$this->db->query($q);
				}

			}
		}

		/*

			"canvas",
				"easel",
				"tray",

		*/
	}

	function getLiveCats() {
		$q = "select * from wp_terms where term_id>14 order by name asc";
		$res = $this->db->query($q);
		$result = $res->result();
		$res->free_result();
		return $result;
	}

	function removeProducts($go = 0) {

		$q = "update jt_supplier_data set moved=0 where id>0";
		$this->db->query($q);
		$q = "select * from wp_posts where post_type='product' order by id asc";
		$r = $this->db->query($q)->result();
		echo "<P>total product posts:" . count($r);

		$q = "Select count(*) as ttl from wp_posts where post_type='attachment' and id>1014";
		$rr = $this->db->query($q)->row();
		echo "<P>total img:" . $rr->ttl;
		$postids = array();
		foreach ($r as $row) {
			echo "<p>" . $row->post_title;
			$postids[] = $row->ID;

		}

		if ($go) {

			$this->db->query("delete from wp_posts where post_type='attachment' and id>1014");
			$this->db->query("delete from wp_posts where ID in (" . implode(",", $postids) . ")");
			$this->db->query("delete from wp_term_relationships where object_id in (" . implode(",", $postids) . ")");
		} else {

			echo ("<p>delete from wp_posts where post_type='attachment' and id>1014");
			echo ("<p>delete from wp_posts where ID in (" . implode(",", $postids) . ")");
			echo ("<p>delete from wp_term_relationships where object_id in (" . implode(",", $postids) . ")");
		}

	}

	function fixlivecats() {

		$target = "Paints, Mediums and Finishes";
		$subs = array(
			"brush" => 20,
			"watercolor" => 25,
			"oil" => 22,
			"acrylic" => 41,
			"gouache" => 43,
			"tempera" => 42,
			"enamel" => 44,

		);
		$category_id = $this->db->query("select * from wp_terms where name='$target'")->row()->term_id;
//23
		$r = $this->db->query("select * from wp_posts p left join wp_term_relationships r on r.object_id=p.ID where r.term_taxonomy_id=$category_id")->result();

		foreach ($r as $row) {
			$title = strtolower($row->post_title);
			foreach ($subs as $s => $sid) {
				if (strpos($title, $s) !== false) {
					echo "<p>Move into $s: $title";
					$up = array("term_taxonomy_id" => $sid);
					$this->db->update("wp_term_relationships", $up, array("object_id" => $row->ID));
					continue 2;
				}
			}
		}
	}

	function moveProducts($go = 0) {

		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		/*$str = 'a:5:{s:5:"width";i:225;s:6:"height";i:225;s:4:"file";s:19:"2020/03/images.jpeg";s:5:"sizes";a:0:{}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}';
		die("<h3>Output</h3><pre>" . print_r(unserialize($str), 1) . "</pre>");*/

		// $go = 0;
		$cats = $this->getLiveCats();

		foreach ($cats as $cat) {
			echo "<p>Using Cat " . print_r($cat->name, 1);

			$q = "select * from jt_supplier_data where category='{$cat->name}' and moved=0 and approved=1 order by image desc, id asc limit 500";
			echo "<P>$q";

			$rr = $this->db->query($q);
			$r = $rr->result();
			$rr->free_result();

			foreach ($r as $row) {
				//echo ("<p> going for it " . print_r($row, 1));

				$in = $this->getPostInsertA($row);
				echo "<p>" . $this->db->insert_string("wp_posts", $in);
				if ($go) {
					$done = $this->db->insert("wp_posts", $in);
					if (!$done) {
						die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
					}
					$post_id = $this->db->insert_id();
				} else {
					$post_id = "NEWPOSTID";
				}
				$row->post_id = $post_id;

				$ustr = $this->db->update_string("wp_posts", array("guid" => $this->base_url . "?post_type=product&#038;p=" . $post_id), array("id" => $post_id));

				if ($go) {
					$this->db->query($ustr);
				} else {
					echo "<P>$ustr";
				}
				$row->image_post_id = null;
				if ($row->image) {
					$in = $this->getImgInsertA($row);
					echo "<p>" . $this->db->insert_string("wp_posts", $in);
					if ($go) {
						$done = $this->db->insert("wp_posts", $in);
						if (!$done) {
							die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
						}
						$image_post_id = $this->db->insert_id();
					} else {
						$image_post_id = "NEW-IMAGE-POSTID";
					}

					echo "<P>moving " . $this->temp_img_dir . $row->image . " to " . $this->prod_img_dir . $row->image;
					if ($go) {
						copy($this->temp_img_dir . $row->image, $this->prod_img_dir . $row->image);
					}

					$row->image_post_id = $image_post_id;

					$sz = getimagesize($this->temp_img_dir . $row->image);

					$img_meta = array(

						"width" => $sz[0],
						"height" => $sz[1],
						"file" => "2020/06/" . $row->image,
						"sizes" => Array
						(
						),

						"image_meta" => Array
						(
							"aperture" => 0,
							"credit" => "",
							"camera" => "",
							"caption" => "",
							"created_timestamp" => 0,
							"copyright" => "",
							"focal_length" => 0,
							"iso" => 0,
							"shutter_speed" => 0,
							"title" => $row->title,
							"orientation" => 0,
							"keywords" => Array
							(
							),

						),
					);
					$img_meta = serialize($img_meta);
					$i = array();
					$i[] = array("post_id" => $image_post_id, "meta_key" => "_wp_attached_file", "meta_value" => $this->local_image_path . $row->image);
					$i[] = array("post_id" => $image_post_id, "meta_key" => "_wp_attachment_metadata", "meta_value" => $img_meta);

					if ($go) {
						$this->db->insert_batch("wp_postmeta", $i);
					}

					//echo "<p>post meta: " . print_r($i, 1);

				}

				$up = array("object_id" => $post_id, "term_taxonomy_id" => $cat->term_id);
				if ($go) {
					$this->db->insert("wp_term_relationships", $up);
				}

				echo "<P>wp_term_relationships: " . print_r($up, 1);

				// insert meta
				$in = $this->getPostMetaInsertA($row);

				if ($go) {
					$done = $this->db->insert_batch("wp_postmeta", $in);
					if (!$done) {
						die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
					}

					$upp = array("moved" => 1);
					$this->db->update("jt_supplier_data", $upp, array("id" => $row->id));
				} else {
					echo ("<h3>post meta</h3><pre>" . print_r($in, 1) . "</pre>");
				}

				//echo ("<h3>Output</h3><pre>" . print_r("DONE", 1) . "</pre>");

			}
		}

	}

	function popm() {
		$q = "select * from jt_mac_cats ";
		$r = $this->db->query($q)->result();
		foreach ($r as $row) {
			$u = array("site_category" => $row->category);
			$this->db->update('jt_mac_data', $u, array("category" => $row->o_subcat));
		}
	}

	/*function movei($go = 0) {
		$q = "select * from jt_mac_data where data='DONE' and approved=0 order by image desc, id asc limit 500";
		$rr = $this->db->query($q);
		$r = $rr->result();
		$rr->free_result();
		foreach ($r as $row) {

			echo "<P>moving " . $this->temp_img_dir . $row->image . " to " . $this->prod_img_dir . $row->image;
			echo "<P>exists? " . file_exists($this->prod_img_dir . $row->image);
			if ($go) {
				copy($this->temp_img_dir . $row->image, $this->prod_img_dir . $row->image);
			}

		}

	}*/

	function moveMacProducts($go = 0) {

		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		/*$str = 'a:5:{s:5:"width";i:225;s:6:"height";i:225;s:4:"file";s:19:"2020/03/images.jpeg";s:5:"sizes";a:0:{}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}';
		die("<h3>Output</h3><pre>" . print_r(unserialize($str), 1) . "</pre>");*/

		// $go = 0;
		$q = "select * from jt_mac_data where data='DONE' and approved=0 order by image desc, id asc limit 500, 100000";

		$rr = $this->db->query($q);
		$r = $rr->result();
		$rr->free_result();
		foreach ($r as $row) {

			//echo ("<p> going for it " . print_r($row, 1));

			$in = $this->getPostInsertA($row);
			echo "<p>" . $this->db->insert_string("wp_posts", $in);
			if ($go) {
				$done = $this->db->insert("wp_posts", $in);
				if (!$done) {
					die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
				}
				$post_id = $this->db->insert_id();
			} else {
				$post_id = "NEWPOSTID";
			}
			$row->post_id = $post_id;

			$ustr = $this->db->update_string("wp_posts", array("guid" => $this->base_url . "?post_type=product&#038;p=" . $post_id), array("id" => $post_id));

			if ($go) {
				$this->db->query($ustr);
			} else {
				echo "<P>$ustr";
			}
			$row->image_post_id = null;
			if ($row->image) {
				$in = $this->getImgInsertA($row);
				echo "<p>" . $this->db->insert_string("wp_posts", $in);
				if ($go) {
					$done = $this->db->insert("wp_posts", $in);
					if (!$done) {
						die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
					}
					$image_post_id = $this->db->insert_id();
				} else {
					$image_post_id = "NEW-IMAGE-POSTID";
				}

				echo "<P>moving " . $this->temp_img_dir . $row->image . " to " . $this->prod_img_dir . $row->image;
				if ($go) {
					copy($this->temp_img_dir . $row->image, $this->prod_img_dir . $row->image);
				}

				$row->image_post_id = $image_post_id;

				$sz = getimagesize($this->temp_img_dir . $row->image);

				$img_meta = array(

					"width" => $sz[0],
					"height" => $sz[1],
					"file" => "2020/06/" . $row->image,
					"sizes" => Array
					(
					),

					"image_meta" => Array
					(
						"aperture" => 0,
						"credit" => "",
						"camera" => "",
						"caption" => "",
						"created_timestamp" => 0,
						"copyright" => "",
						"focal_length" => 0,
						"iso" => 0,
						"shutter_speed" => 0,
						"title" => $row->title,
						"orientation" => 0,
						"keywords" => Array
						(
						),

					),
				);
				$img_meta = serialize($img_meta);
				$i = array();
				$i[] = array("post_id" => $image_post_id, "meta_key" => "_wp_attached_file", "meta_value" => $this->local_image_path . $row->image);
				$i[] = array("post_id" => $image_post_id, "meta_key" => "_wp_attachment_metadata", "meta_value" => $img_meta);

				if ($go) {
					$this->db->insert_batch("wp_postmeta", $i);
				}

				//echo "<p>post meta: " . print_r($i, 1);

			}

			$up = array("object_id" => $post_id, "term_taxonomy_id" => $row->site_category);
			if ($go) {
				$this->db->insert("wp_term_relationships", $up);
			}

			echo "<P>wp_term_relationships: " . print_r($up, 1);

			// insert meta
			$in = $this->getPostMetaInsertA($row);

			if ($go) {
				$done = $this->db->insert_batch("wp_postmeta", $in);
				if (!$done) {
					die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
				}

				$upp = array("moved" => 1);
				$this->db->update("jt_supplier_data", $upp, array("id" => $row->id));
			} else {
				echo ("<h3>post meta</h3><pre>" . print_r($in, 1) . "</pre>");
			}

			//echo ("<h3>Output</h3><pre>" . print_r("DONE", 1) . "</pre>");

		}

	}

	function fixsku() {
		$q = "select * from jt_supplier_data where moved=1;";
		$r = $this->db->query($q)->result();

		foreach ($r as $row) {
			$title = trim(strtolower(addslashes($row->title)));
			$q = "select * from wp_posts where trim(lower(post_title))='$title'";
			$t = $this->db->query($q);
			if ($t && $t->num_rows() != 1) {
				echo "<hr>$q";
				echo "<P>-- found 0 or 2+ title matches for $title --  #rows: " . $t->num_rows();
			}

		}
	}

	function getPostInsertA($row) {
		$title = $row->title;
		$title = ucwords(strtolower(trim($title)));
		$safetitle = strtolower($title);
		$safetitle = preg_replace('/[^a-z0-9]+/i', '-', $safetitle); # or...
		$safetitle = preg_replace('/[^a-z\d]+/i', '-', $safetitle);
		$safetitle = str_replace(" ", "-", $safetitle);

		//if (strtoupper($title) == $title) {
		//}
		$a = array(
			"post_author" => 1,
			"post_date" => date("Y-m-d H:i:s"),
			"post_date_gmt" => date("Y-m-d H:i:s"),
			"post_modified_gmt" => date("Y-m-d H:i:s"),
			"post_modified" => date("Y-m-d H:i:s"),
			"post_content" => $row->description,
			"post_title" => $title,
			"post_status" => "publish",
			"comment_status" => "open",
			"ping_status" => "open",
			"post_name" => $safetitle,
			"guid" => $this->base_url . "?post_type=product&#038;p=",
			"post_type" => "product",
			"menu_order" => 0,
		);

		return $a;
	}

	function getImgInsertA($row) {
		$safetitle = strtolower($row->image);
		$safetitle = explode(".", $safetitle);
		$safetitle = $safetitle[0];
		$safetitle = preg_replace('/[^a-z0-9]+ /i', '_', $safetitle); # or...
		$safetitle = preg_replace('/[^a-z\d]+ /i', '_', $safetitle);
		$safetitle = str_replace(" ", "-", $safetitle);
		$a = array(
			"post_author" => 1,
			"post_date" => date("Y-m-d H:i:s"),
			"post_date_gmt" => date("Y-m-d H:i:s"),
			"post_modified_gmt" => date("Y-m-d H:i:s"),
			"post_modified" => date("Y-m-d H:i:s"),
			"post_content" => "",
			"post_title" => $safetitle,
			"post_status" => "inherit",
			"comment_status" => "open",
			"ping_status" => "closed",
			"post_name" => $safetitle,
			"guid" => $this->base_url . $this->img_dir . $row->image,
			"post_type" => "attachment",
			"post_mime_type" => "image/jpeg",
			"menu_order" => 0,
		);

		return $a;
	}

	function getPostMetaInsertA($row) {

		$in = array();

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_regular_price",
			"meta_value" => $row->price,
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_sku",
			"meta_value" => $row->sku,
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_wpm_gtin_code",
			"meta_value" => $row->upc,
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_edit_last",
			"meta_value" => "1",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_edit_lock",
			"meta_value" => "",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "total_sales",
			"meta_value" => "0",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_tax_status",
			"meta_value" => "taxable",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_tax_class",
			"meta_value" => "",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_manage_stock",
			"meta_value" => "no",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_backorders",
			"meta_value" => "no",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_sold_individually",
			"meta_value" => "no",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_virtual",
			"meta_value" => "no",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_downloadable",
			"meta_value" => "no",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_download_limit",
			"meta_value" => "-1",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_download_expiry",
			"meta_value" => "-1",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_stock",
			"meta_value" => "NULL",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_stock_status",
			"meta_value" => "instock",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_wc_average_rating",
			"meta_value" => "0",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_wc_review_count",
			"meta_value" => "0",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_product_version",
			"meta_value" => "4.0.1",
		);

		$in[] = array(
			"post_id" => $row->post_id,
			"meta_key" => "_price",
			"meta_value" => $row->price,
		);

		if ($row->image_post_id) {
			/*$in[] = array(
				"post_id" => $row->post_id,
				"meta_key" => "_product_image_gallery",
				"meta_value" => $row->image_post_id,
			);*/

			$in[] = array(
				"post_id" => $row->post_id,
				"meta_key" => "_thumbnail_id",
				"meta_value" => $row->image_post_id,
			);
		}

		return $in;
	}

	/*
		//// PRODUCT-IFYING

		set up categories first (wp_terms)
		see how it influenced wp_termmeta

		insert main post into wp_posts (like id:17)
		insert image into wp_posts (like id:18)
		-- move image to correct location...

		update wp_term_relationships
		with object_id = post_id, term_taxonomy_id=category_id (from terms)

		insert into post_meta

		meta_id - auto increment
		post_id - from above
		meta_key, meta_value
		-------------------------
		meta_key: _edit_last
		meta_value: 1

		meta_key: _edit_lock
		meta_value: 1585691484:1

		meta_key: _regular_price
		meta_value: 50  // $VAR

		meta_key: total_sales
		meta_value: 0

		meta_key: _tax_status
		meta_value: taxable

		meta_key: _tax_class
		meta_value:
		meta_key: _manage_stock
		meta_value: no

		meta_key: _backorders
		meta_value: no

		meta_key: _sold_individually
		meta_value: no

		meta_key: _virtual
		meta_value: no

		meta_key: _downloadable
		meta_value: no

		meta_key: _download_limit
		meta_value: -1

		meta_key: _download_expiry
		meta_value: -1

		meta_key: _stock
		meta_value: NULL

		meta_key: _stock_status
		meta_value: instock

		meta_key: _wc_average_rating
		meta_value: 0

		meta_key: _wc_review_count
		meta_value: 0

		meta_key: _product_version
		meta_value: 4.0.1

		meta_key: _price
		meta_value: 50   // $VAR

		meta_key: _product_image_gallery
		meta_value: 20,18  // $VAR

		meta_key: _thumbnail_id
		meta_value: 19  // $VAR
		-------------------------

	*/

	function getMacCats2() {

		// with '// new' below update db on records before may 6
		$cats = array(
			"Crafts" => "Basic Craft Supplies",
			"Art Accessories" => "Art Accessories", // new
			"Airbrush Supplies" => "Airbrush Supplies", // new
			"Brushes and Brush Care" => "Brushes and Brush Care", // new
			"Canvas and Surface" => "Canvas and Surface", // new
			"Adhesives" => "Tapes and Adhesives",
			"Drafting" => "Drafting Supplies",
			"Print" => "Printmaking",
			"Gifts" => "Gifts",
			"Kids" => "Childrens Crafts",
			"Books" => "Books",
			"Bookmaking" => "Bookmaking",
			"Pastels" => "Pastels",
			"Paper" => "Paper and Pads",
			"Drawing" => "Drawing Supplies",
			"Pens and Markers" => "Pens and Markers", // new
			"Frames" => "Frames",
			"Ceramics" => "Clays and Accessories",
			"Painting" => "Paints, Mediums and Finishes");

		ksort($cats);

		return $cats;
	}

	function getCats() {

		// with '// new' below update db on records before may 6
		$cats = array(
			"Crafts" => "Basic Craft Supplies",
			"Art Accessories" => "Art Accessories", // new
			"Airbrush Supplies" => "Airbrush Supplies", // new
			"Brushes and Brush Care" => "Brushes and Brush Care", // new
			"Canvas and Surface" => "Canvas and Surface", // new
			"Adhesives" => "Tapes and Adhesives",
			"Drafting" => "Drafting Supplies",
			"Print" => "Printmaking",
			"Gifts" => "Gifts",
			"Kids" => "Childrens Crafts",
			"Books" => "Books",
			"Bookmaking" => "Bookmaking",
			"Pastels" => "Pastels",
			"Paper" => "Paper and Pads",
			"Drawing" => "Drawing Supplies",
			"Pens and Markers" => "Pens and Markers", // new
			"Frames" => "Frames",
			"Ceramics" => "Clays and Accessories",
			"Painting" => "Paints, Mediums and Finishes");

		ksort($cats);

		return $cats;
	}

	function saveTempSupplierData() {
		$json = array(
			"id" => $this->input->post('id'),
			"title" => $this->input->post('title'),
			"category" => $this->input->post('category'),
			"price" => $this->input->post('price'),
			"linedata" => $this->input->post('linedata'),
		);
		$in = array(
			"sku" => $this->input->post('id'),
			"tmp_data" => json_encode($json),
			"data_batch" => $this->input->post('data_batch'),
			"created" => date("Y-m-d H:i:s"),
			"supplier" => $this->input->post('supplier'),
		);

		$this->db->insert("jt_supplier_data", $in);
		$out = array("status" => "ok");
		die(json_encode($out));
	}

	function loopimages($startindex = 0) {

		$items = $this->getIarr();
		$ct = 0;
		$cont = 0;
		$num = 1;

		$puts = array();

		foreach ($items as $item) {
			$put = array();

			if ($ct < $startindex) {
				$ct++;
				continue;
			} else if ($ct == $startindex + $num || $ct == count($items)) {
				if ($ct == count($items)) {
					echo json_encode(array("startat" => "done", "puts" => $puts));
				} else {
					echo json_encode(array("startat" => $ct, "puts" => $puts));

				}
				die();
			}
			$ct++;
			/*if ($ct > 100) {
				die();
			}*/

			if (!$item['upc']) {
				$put[] = "NO UPC";
				$puts[] = $put;

				$cont++;
				continue;
			}

			$url = "https://www.upcitemdb.com/upc/" . $item['upc'];
			$url = "https://api.barcodespider.com/v1/lookup?upc=" . $item['upc'];
			$endpoint = "https://api.barcodespider.com/v1/lookup";
			$put[] = $url;
			//, false, stream_context_create($arrContextOptions));

			$ch = curl_init();

			$headers = array(
				'token' => "f9ef1f0279e7b37de96b",
				'Host' => "api.barcodespider.com",
				'Accept-Encoding' => "gzip, deflate",
				'Connection' => "keep-alive",
				'cache-control' => "no-cache",
			);

			curl_setopt_array($ch, array(
				CURLOPT_URL => $endpoint . "?token=f9ef1f0279e7b37de96b&upc=" . $item['upc'],
				CURLOPT_SSL_VERIFYHOST => 0, // do not return headers
				CURLOPT_SSL_VERIFYPEER => 0, // do not return headers
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_POSTFIELDS => "",
				CURLOPT_HTTPHEADER => $headers,
			));

			$content = curl_exec($ch);
			curl_close($ch);

			$json = json_decode($content);
			if ($json->item_response->code != 200) {
				$write = array("item" => $item, "response" => $content, "url" => $endpoint . "?token=f9ef1f0279e7b37de96b&upc=" . $item['upc']);
				$aaa = array("data" => json_encode($write), "upc" => $item['upc']);
				$this->db->insert("jt_noimg", $aaa);
				$put[] = "Did not find page on api";
				$puts[] = $put;
				continue;
				die("<h3>BAD response</h3><pre>" . print_r($json, 1) . "</pre>");
			}

			//die("<h3>Output</h3><pre>" . print_r($json, 1) . "</pre>");

			$imgs = array();
			$img = null;
			if ($json->item_attributes->image) {
				$imgs[] = $json->item_attributes->image;
			}

			if (isset($json->Stores)) {

				foreach ($json->Stores as $store) {
					if ($store->image) {
						if (strpos($store->store_name, "Amazon") !== false) {
							$img = $store->image;
						} else {
							$imgs[] = $store->image;
						}
					}
				}
			}

			if (!$img && count($imgs) > 0) {
				$img = $imgs[count($imgs) - 1];
			}

			if (!$img) {
				$put[] = "NO IMAGE at UPC";
				$puts[] = $put;

				$write = array("item" => $item, "response" => $content, "url" => $endpoint . "?token=f9ef1f0279e7b37de96b&upc=" . $item['upc']);
				$aaa = array("data" => json_encode($write), "upc" => $item['upc']);
				$this->db->insert("jt_noimg", $aaa);

				//die("<h3>NO IMAGE</h3><pre>" . print_r($content, 1) . "</pre>");
				//$ct++;
				$cont++;
				continue;
			}
			$put[] = "image: " . $img;

			$img_title = $json->item_attributes->title;
			/*
				$html = str_get_html($content);

				$d = $html->find('img.product');
				if (count($d) == 0) {
					die("<h3>Output</h3><pre>" . print_r($content, 1) . "</pre>");
					$put[] = "did not find image";
					if (strpos(strtolower($content), "slow down") !== false) {
						$put[] = "slowdown";
						echo json_encode(array("startat" => $ct));
						die();
					}
					//die("<h3>NO IMAGE</h3><pre>" . print_r($content, 1) . "</pre>");
					//$ct++;
					$cont++;
					continue;

				}
				$img = $d->src;

				$img = explode("?", $img);
				$img = $img[0];

			*/

			//foreach ($imgs as $img) {

			$iname = explode("/", $img);
			$iname = $iname[count($iname) - 1];

			if (!$img_title) {
				$img_title = $iname;
			}

			$ipostname = explode(".", $iname);
			$ext = strtolower($ipostname[count($ipostname) - 1]);
			$ipostname = $ipostname[0];

			$iloc = "2020/06/" . $iname;
			$dest = $_SERVER['DOCUMENT_ROOT'] . "/wp-content/uploads/" . $iloc;
			$this->getImage($img, $dest);

			if (!file_exists($dest)) {
				$put[] = "image did not download";
				$puts[] = $put;
				$write = array("item" => $item, "response" => $content, "url" => $endpoint . "?token=f9ef1f0279e7b37de96b&upc=" . $item['upc']);
				$aaa = array("data" => json_encode($write), "upc" => $item['upc']);
				$this->db->insert("jt_noimg", $aaa);

				//$ct++;
				continue;
				die("<h3>Output</h3><pre>" . print_r($content, 1) . "</pre>");
			}
			$put[] = "downloaded: $iloc";

			$gurl = "https://thepaint-chip.com/wp-content/uploads/" . $iloc;
			$mime = "";
			if ($ext == "jpg" || $ext == "jpeg") {
				$mime = "image/jpeg";
			} else if ($ext == "png") {
				$mime = "image/png";

			} else if ($ext == "gif") {
				$mime = "image/gif";

			}

			$sz = getimagesize($dest);

			$img_meta = array(

				"width" => $sz[0],
				"height" => $sz[1],
				"file" => $iloc,
				"sizes" => Array
				(
				),

				"image_meta" => Array
				(
					"aperture" => 0,
					"credit" => "",
					"camera" => "",
					"caption" => "",
					"created_timestamp" => 0,
					"copyright" => "",
					"focal_length" => 0,
					"iso" => 0,
					"shutter_speed" => 0,
					"title" => $img_title,
					"orientation" => 0,
					"keywords" => Array
					(
					),

				),
			);
			$img_meta = serialize($img_meta);

			$up = array("meta_value" => $img_meta);
			$uuup = $this->db->update("wp_postmeta", $up, array("meta_id" => $item['_wp_attachment_metadata_id']));
			if (!$uuup) {
				die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
			}

			$up = array("meta_value" => $iloc);
			$uuup = $this->db->update("wp_postmeta", $up, array("meta_key" => "_wp_attached_file", "post_id" => $item['image_post_id']));
			if (!$uuup) {
				die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
			}

			$up = array("post_title" => $img_title, "post_name" => $img_title, "guid" => $gurl, "post_mime_type" => $mime);
			$uuup = $this->db->update("wp_posts", $up, array("ID" => $item['image_post_id']));

			if (!$uuup) {
				die("<h3>Output</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
			}

			$put[] = "did everything image: $iloc";
			$puts[] = $put;
			//}

/*

// update:::
get new image
-- update wp_postmeta _wp_attachment_metadata with values like
a:5:{s:5:"width";N;s:6:"height";N;s:4:"file";s:27:"2020/05/images-tb-56555.jpg";s:5:"sizes";a:0:{}s:10:"image_meta";a:12:{s:8:"aperture";i:0;s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";i:0;s:9:"copyright";s:0:"";s:12:"focal_length";i:0;s:3:"iso";i:0;s:13:"shutter_speed";i:0;s:5:"title";s:36:"DUAL BRUSH PEN REFLEX BLUE (ABT 493)";s:11:"orientation";i:0;s:8:"keywords";a:0:{}}}
-- update wp_postmeta _wp_attached_file with values like
2020/05/images-tb-56555.jpg

-- update wp_posts with
post_title like mc21120_t
post_name like  mc21120_t
guid like https://thepaint-chip.com/wp-content/uploads/2020/06/MC21120_t.png
post_mime_type like image/jpeg

echo <<<EOT

array(
"upc" => "{$item['upc']}",
"meta_thumbnail_id" => "{$item['meta_thumbnail_id']}",
"image_post_id" => "{$item['image_post_id']}",
"product_post_id" => "{$item['product_post_id']}",
"_wp_attachment_metadata_id" => "{$item['_wp_attachment_metadata_id']}",
"new_image_url" => "$img",
"_wp_attached_file_id" => "",
),

EOT;

 */
			//echo "<br>" . '"' . $img . '",';

			//echo "<p>https://www.upcitemdb.com/upc/" . $item['upc'];

		}

		die("<h3>cont</h3><pre>" . print_r($cont, 1) . "</pre>");

	}

	function upnoimg() {
		$q = "select * from jt_noimg where sku=''";
		$r = $this->db->query($q)->result();
		foreach ($r as $row) {
			$qq = "select * from jt_mac_data where upc ='" . $row->upc . "'";
			$rr = $this->db->query($qq);
			if ($rr->num_rows() > 0) {
				$this->db->update('jt_noimg', array("supplier" => "MAC", "sku" => $rr->row()->sku), array("id" => $row->id));

			}
			$rr->free_result();
		}
	}

	function priceCheck() {
		$this->load->view('price-check');
	}

	function fixslugs() {
		$q = "SELECT * FROM `wp_posts` where post_name like '-%'";
		$r = $this->db->query($q)->result();
		foreach ($r as $row) {
			$p = $row->post_name;
			$p = str_replace("-x-", "x", $p);
			$p = explode("-", $p);
			$p = array_filter($p);
			$p = implode("-", $p);
			$u = array("post_name" => $p);
			$qq = $this->db->update_string("wp_posts", $u, array("ID" => $row->ID));
			echo "<P>$qq;";

		}

	}

	function getPriceList() {
		$q = "select *, price_pc-price_vendor as diff from jt_price_check where price_vendor>price_pc order by diff asc";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();
		echo json_encode($r);
	}

	function equalizePrice($id) {
		// set the PC price to the  vendor price

		$q = "select * from jt_price_check where id=$id";
		$rq = $this->db->query($q);
		$r = $rq->row();
		$rq->free_result();

		$q = "update wp_postmeta set meta_value='{$r->price_vendor}' where meta_key='_price' and post_id={$r->post_id}";

		$done = $this->db->query($q);
		if (!$done) {
			die("<h3>$q</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
		}

		$q = "update jt_price_check set price_pc={$r->price_vendor} where id={$r->id}";

		$done = $this->db->query($q);
		if (!$done) {
			die("<h3>$q</h3><pre>" . print_r($this->db->error(), 1) . "</pre>");
		}
		echo json_encode(array('status' => "OK"));

	}

	function checkPriceAjax($st = 0, $lim = 10) {
		$and = "";
		if ($st == 0) {
			$and = " and modified < '2020-02-02' ";
		}
		$q = "select * from jt_price_check where vendor='MAC' and  id>$st $and order by id asc limit $lim";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();

		foreach ($r as $row) {
			if ($row->vendor == "SS") {
				$price = $this->getSLSPrice($row);
			} else {
				$price = $this->getMacPrice($row);

			}

			$price = str_replace("$", "", $price);
			//die("<h3>Output</h3><pre>" . print_r("price" . $price, 1) . "</pre>");
			$up = array("price_vendor" => $price, "modified" => date("Y-m-d H:i:s"));
			$this->db->update('jt_price_check', $up, array('id' => $row->id));
		}
		if (count($r) < $lim) {
			$ret = array('complete' => 1);
		} else {
			$ret = array("nextstart" => ($row->id));
		}

		die(json_encode($ret));

	}
	function getMacPrice($row) {

		$url = "https://www.macphersonart.com/cgi-bin/maclive/wam_tmpl/catalog_browse.p?site=MAC&layout=Responsive&page=catalog_browse&searchText=" . $row->sku;
		//echo "<P> starting $ct";
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

		$res['content'] = $content;
		$res['content'] = strip_tags($content, "<body>");
		$res['url'] = $header['url'];

		$newurl = str_replace('document.location.replace("', "", $res['content']);
		$newurl = str_replace('");', "", $newurl);

		if (!$newurl || strpos($newurl, "Catalog Browse | MacPherson's") != FALSE) {
			//$item['data'] = "NO URL";
			//$this->db->update("jt_mac_data", $item, array("id" => $row->id));

			return 0;
		}
		$html = file_get_html($newurl);
		//return $res;

		$item = array();

		//print_r(get_web_page("http://www.example.com/redirectfrom"));

		/*foreach ($d as $dd) {
					echo ("<h3>Output</h3><pre>" . print_r($dd->innerText, 1) . "</pre>");
				}
			*/
		$d = $html->find('#row' . $row->sku . ' td');
		if (count($d) == 0) {
			//$ct++;

			//echo "<p>continuoing... <a href='$url' target='_blank'>$url</a>";

			//die("<h3>no data</h3><pre>" . print_r($newurl, 1) . print_r($row, 1) . "</pre>");
			return 0;

		}

		$tdata = $d[6]->find('div.qoRegPrice');
		$tdata = $tdata[0];
		$price = str_replace("$", "", $tdata->innerText);
		return $price;
	}

	function getSLSPrice($row) {

		$q = "select * from linkys where  data like '%{$row->sku}%' ";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();
		$price = 0;
		$msg = "";
		if (count($r) == 0) {
			$msg = "<p>Nothing for {$row->title}";
		} else if (count($r) > 1) {
			$msg = "<p>More than one found for {$row->title}";
		} else {
			$el = $r[0];
			$file = $el->link;
			$file = str_replace(" ", "%20", $file);
			//$file = str_replace("fright_itemlist.asp", "defaultFrame.asp", $file);
			$u = "https://www.slsarts.com/$file";

			$html = file_get_html($u);

			// get the cat structure
			$struc = array();
			$a = $html->find("a");
			foreach ($a as $alink) {
				$struc[] = trim(str_replace("\r\n", "", $alink->innertext));
			}

			$data = array();
			$cells = $html->find('table td');
			foreach ($cells as $cell) {
				$h = trim($cell->innertext);
				$h = trim(str_replace("\r\n", "", strip_tags($h)));
				if ($h != "") {
					$data[] = $h;

					if ($h == "MSRP") {
						$data = array();
					}

				}
			}

			// update last mined
			$up = array("data" => json_encode(array("struc" => $struc, "data" => $data)), "last_mined" => date("Y-m-d H:i:s"));
			$this->db->update("linkys", $up, array("id" => $el->id));
			$pricenext = false;
			foreach ($data as $d) {
				if (strtolower($d) == strtolower($row->sku)) {
					$pricenext = true;
				}

				if ($pricenext && strpos($d, "$") !== false && strpos($d, "$") == 0) {
					//$price = $d;
					return $d;
				}
			}
			//echo ("<h3>Output</h3><pre>" . print_r($data, 1) . "</pre>");

		}
		return $price;

	}

	function priceCheckCollect($st = 0, $lim = 10) {
		// do third
		$q = "select  sku from jt_supplier_data   ";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();

		foreach ($r as $row) {
			$q = "update jt_price_check set vendor='SS' where sku='{$row->sku}'";
			$this->db->query($q);
		}

		$q = "select  sku from jt_mac_data   ";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();

		foreach ($r as $row) {
			$q = "update jt_price_check set vendor='MAC' where sku='{$row->sku}'";
			$this->db->query($q);
		}

		return;

		// do second
		$q = "select  * from wp_postmeta   where meta_key='_price'  ";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();
		foreach ($r as $row) {
			$up = array("price_pc" => $row->meta_value);
			$this->db->update('jt_price_check', $up, array('post_id' => $row->post_id));
		}

		return;

		// do first
		$q = "select p.post_title, m.* from wp_postmeta m left join wp_posts p on m.post_id=p.ID where m.meta_key='_sku'  ";
		$rq = $this->db->query($q);
		$r = $rq->result();
		$rq->free_result();
		foreach ($r as $row) {
			$sku = $row->meta_value;
			$post_id = $row->post_id;
			$title = $row->post_title;
			if (!$title) {
				continue;
			}

			$in = array(
				"post_id" => $post_id,
				"title" => $title,
				"sku" => $sku,
			);
			$this->db->insert("jt_price_check", $in);
		}

	}
}
