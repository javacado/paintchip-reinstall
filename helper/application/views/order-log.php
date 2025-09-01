<?php

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Order Log</title>
    <meta content="" name="descriptison">
    <meta content="" name="keywords">
    <!-- Favicons -->
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <!--  <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
 -->
    <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.20/datatables.min.css"/>

<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.20/datatables.min.js"></script>



<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.2/animate.min.css" />

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
</head>

<body>
    <a name='top' id='top'></a>

        <!--
        https://revrocket-static-aws.s3-us-west-2.amazonaws.com/system/assets/lib/js/iframe-resizer/js/iframeResizer.min.js
        -->
        <!--
        https://tinyurl.com/wfj6hw5
        -->



<?php
$ic = "<i class='fa fa-arrow-down'></i>";
$bnclass = $completed == "0" ? 'btn-link' : 'btn-info';
$bnicon = $completed == "0" ? $ic : "";

$boclass = $completed == "1" ? 'btn-link' : 'btn-info';
$boicon = $completed == "1" ? $ic : "";

$bmclass = $completed == 'waiting' ? 'btn-link' : 'btn-info';
$bmicon = $completed == 'waiting' ? $ic : "";

?>

<div class='container main-c'>

<h1 style='margin-bottom:25px;'>Paintchip Order Log</h1>

<div style='margin-bottom:15px;'>
<a class='btn <?php echo $bnclass ?>' href='/helper/order_log/index/0'>Uncompleted Orders <?php echo $bnicon ?></a> &nbsp;
<a class='btn <?php echo $boclass ?>' href='/helper/order_log/index/1'>Completed Orders <?php echo $boicon ?></a> &nbsp;
<a class='btn <?php echo $bmclass ?>' href='/helper/order_log/index/waiting'>Waiting For... <?php echo $bmicon ?></a>


</a>
</div>


<?php if ($completed == 'waiting') {
	?>


 <?php
foreach ($log as $row) {
		?>



<?php foreach ($row->products as $p) {
			$is = 0;
			if (isset($row->prod_data[$p->product_id])) {
				$is = $row->prod_data[$p->product_id]['in_store'] || $row->post_status == 'wc-completed';
				if ($is == "1") {
					continue;
				}

			}
			?>
<div class='litem'>
<h3><?php echo $p->order_item_name ?></h3>
For <?php echo $row->meta->_billing_first_name ?> <?php echo $row->meta->_billing_last_name ?>
<br /><i>
<a href='/helper/order_log/index/0#r<?php echo $row->ID ?>'>Order #<?php echo $row->ID ?></i></a>

</div>
    <?php }?>

        <?php }?>


<?php } else {
	?>

<table class='table'>
    <tr>
        <td>Order</td>
        <td>Products</td>
        <td style='width:60px;'>Employee</td>
        <td>Notes</td>
        <td style='width:150px;'>Picked Up</td>
<!--         <td style='width:50px;'>Complete</td>
 -->        <td></td>
 </tr>


 <?php
foreach ($log as $row) {
		?>

<tr rel="<?php echo $row->ID ?>" id="r<?php echo $row->ID ?>" class='bbot' data-logid="<?php echo $row->log_id ?>">

        <td>
<h3 style='margin-top:0;'><?php echo $row->meta->_billing_first_name ?> <?php echo $row->meta->_billing_last_name ?>
</h3>
<i>
Order #<?php echo $row->ID ?></i>

            <div class='small'><?php echo date("m/d/Y", strtotime($row->post_modified)) ?></div>
        </td>
        <td>
<!-- 
    here:
    <?php
    //print_r($row); 
    ?>
    
  -->
<table class='table text-center ptable' style='border-top:0;'>
    <tr  ><td style='text-align:left' >Product</td><td>In Store</td><td>Need to Order</td>
    <?php foreach ($row->products as $p) {
			$is = 0;
			if (isset($row->prod_data[$p->product_id])) {
				$is = $row->prod_data[$p->product_id]['in_store'];
			}
			?>
<tr  rel='<?php echo $p->product_id ?>' class='prods'><td style='text-align:left'><?php echo $p->order_item_name ?>
<br /><strong>
<?php echo $p->sku ?>
</strong></td>
    <td class='text-center'>
            <input type='radio' class=' '   rel='' onclick="" name='in_stock_<?php echo $p->product_id ?>' <?php if ($is) {echo "checked";}?> value='1' name="in_store" />
        </td>
    <td class='text-center'>
            <input type='radio' class=''   rel='' onclick="" <?php if (!$is) {echo "checked";}?> name='in_stock_<?php echo $p->product_id ?>' value='0' name="need_to_order" />

    </td>
</tr>

        <?php }?>
</table>
        </td>
         <!-- <td>
<div class='form-group'>
    <span class='input-group'>
        <span class='input-group-addon'>
            <input type='checkbox' rel='fdc' <?php if ($row->find_date != "") {
			echo 'checked';
		}
		?> onclick='std(this)' />
        </span>
<input class='form-control' rel='find_date' type='date' value="<?php echo $row->find_date ?>">
</span>
</div>


        </td>
        <td>
<input class='form-control' rel='need_to_order'  type='text' value="<?php echo $row->need_to_order ?>">
         </td> -->
        <td >
            <input class='form-control' rel='employee' type='text' value="<?php echo $row->employee ?>">
                    </td>
        <td><textarea rel='notes' class='form-control'><?php echo $row->notes ?></textarea></td>
        <td>

            <div class='form-group'>
    <span class='input-group'>
        <span class='input-group-addon'>
            <input type='checkbox' rel='fdc'  <?php if ($row->picked_up != "") {
			echo 'checked';
		}
		?>  onclick='std(this)' />
        </span>
<input class='form-control' rel='picked_up' type='date' value="<?php echo $row->picked_up ?>">
</span>
</div>

</td>
        <!-- <td>
            <input type='checkbox' rel='complete'  class='form-control' <?php if ($row->complete == 1) {
			echo 'checked';
		}
		?>   />

            </td> -->
        <td><button class='btn btn-success' onclick='savethis(this)'>Save</button><div class='savemsg text-success'></div></td>
 </tr>

    <?php
}
	?>


</table>
<?php }?>

<script>

function std(el) {

    var v="<?php echo date('m/d/Y') ?>";
    setTimeout(function() {
        if (!$(el).prop('checked')) v="";
    $(el).closest('.form-group').find('input[type="date"]').val(v);

    }, 10)
}


function savethis(el) {
    var row = $(el).closest('tr.bbot');
    var data = {};

    if (row.attr('data-logid')) data.log_id=row.attr('data-logid');
    data.post_id=row.attr('rel');
    data.employee = row.find('input[rel="employee"]').val();
    data.notes = row.find('textarea[rel="notes"]').val();
    data.picked_up = row.find('input[rel="picked_up"]').val();
    data.complete = row.find('input[rel="complete"]').prop('checked') ? 1 : 0 ;;
    var prod_data=[];
    row.find('tr.prods').each(function() {
             prod_data.push({
                prod_id: $(this).attr('rel'),
                in_store: $(this).find('input[value="1"]').prop('checked') ? 1 : 0
            })

    })

    data.prod_data = prod_data;
    console.log(data);



 $.ajax({
        url: "/helper/order_log/saveLogData",
        context: document.body,
        method: 'post',
        data:data,
    }).done(function(res) {

row.find('.savemsg').html("<i class='fa fa-check-circle'></i> Saved!") ;
setTimeout(function() {
    row.find('.savemsg').html("... saved recently") ;
}, 3000)
        if (data.complete==1) row.remove();


})

}
</script>





</div>







<style>
.savemsg{
    font-size:.8em;
    margin-top:15px;
}
body{
    padding-top:0px;
    font-family: 'Open Sans', sans-serif;
}

.dshow{

}
textarea{


    min-height:100px;
}


.bbot{
    border-bottom:1px dashed #000;
}

#lg-img{
    position:absolute;
    right:0;
    z-index:10000;
}


.ptable{
    border:1px solid #ccc;
}

img.simg{
    max-height:50px;
}
        </style>




</body>
</html>