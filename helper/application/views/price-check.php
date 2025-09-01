<?php
$this->load->view('header', array('title' => 'Price Check'));
$ok = 1;
?>

    <a name='top' id='top'></a>

        <!--
        https://revrocket-static-aws.s3-us-west-2.amazonaws.com/system/assets/lib/js/iframe-resizer/js/iframeResizer.min.js
        -->
        <!--
        https://tinyurl.com/wfj6hw5
        -->





<div class='container main-c'>

<h1>Paintchip Price Check</h1>


<?php
if (!$ok) {?>

 <form method="POST" style='width:400px;'>
    <div class='input-group'>
        <label class='input-group-addon'>Passphrase</label>
        <input class='form-control input-lg' value='' name='pphrase' placeholder='Type in the Passphrase' />
        <span class='input-group-addon'><button type='submit' class='btn btn-primary'><i class='fa fa-arrow-right'></i> Login</span>
    </div>
</form>


<?php
} else {
	?>



<table class='dataTable table-striped vtop' id='priceTable'>




</table>




<style>
.ta{
    min-height:100px;
}

.ta-td{
    min-height:100px;
}

</style>



        <script>


 $(document).ready(function() {

 $.ajax({
        url: "/helper/extractor/getPriceList/",
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
       popTable(res)
    })
 })



function getPricesPC(st) {

var lim=2;

 $.ajax({
        url: "/helper/extractor/checkPriceAjax/"+st+"/"+lim,
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
       if (res.complete==1) {
alert("DONE")
       } else {
setTimeout(function() {
    getPricesPC(res.nextstart);

}, 500);
       }
    })
}


function popTable(result) {
    var itemList=[]
            $.each(result, function(key, obj){
                var o = [];
                o.push((obj.title+"<div class='small'>"+obj.vendor+" " +obj.sku+"</div>"))
                o.push(obj.price_pc)
                o.push(obj.price_vendor)
                var diff = Math.round(obj.diff * 100) /100; ;

                o.push(diff)
                var btns="<button class='btn btn-default btn-xs' data-postid='"+obj.post_id+"' data-id='"+obj.id+"' onclick='updatePrice(this)'>Update $</button>";
                btns+=" <a class='btn btn-default btn-xs' target='_blank' href='/wp-admin/post.php?post="+obj.post_id+"&action=edit'>Edit Product</a>"
                o.push(btns)
                itemList.push(o)
            })
            console.log("itemList, ", itemList);



             theTable = $('#priceTable').DataTable( {
        data: itemList,
        iDisplayLength:100,
        columns: [
            { title: "Product/Sku" },
            { title: "PC Price" },
            { title: "Vendor Price" },
            { title: "$Diff" },
            { title: "" }
         ]
        } );



}

function updatePrice(el) {
    var id=$(el).attr('data-id')
    var post_id=$(el).attr('data-post_id') ;




 $.ajax({
        url: "/helper/extractor/equalizePrice/"+id,
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
$(el).closest('tr').fadeOut();

    })



}

        </script>

        <?php
}?>
<?php
$this->load->view('footer', array());
?>
