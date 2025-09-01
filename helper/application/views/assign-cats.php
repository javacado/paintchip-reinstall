<?php

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Cats</title>
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





<div class='container main-c'>
<?if (!is_dir("/var/www/html/paintchip")) {?>
<h1> LOCAL SITE</h1>
<hr>
<?}?>

<h1>Xtractor</h1>
<h2 id='title'></h2>
<h3 id='ctr'></h3>



 <!-- <button class='btn btn-default btn-sm' onclick='getNI()'>Fix/Retry Not Identified</button> -->




<div class='row ' style=''>

    <div class='col-md-6 ' >
<h3>Click'em</h3>
<ul>
<?
foreach ($keys as $p => $sub) {
	?>

<li>
<?=$p?>
<ul>
    <?foreach ($sub as $item) {?>
<li>
            <a href='javascript:void(0)' onclick='maccat(this)'><?=$item?></a>  <span class='ucl'></span>

</li>
        <?}?>
    </ul>
    </li>

    <?
}
?>









</ul>

</div>


 <div class='col-md-6 ' >
<h3>Cur Cat</h3>
<ul>
    <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='40'>MISC</a>

</li>

    <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='19'>Frames/Framing</a>

</li>

<li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='34'>Gifts</a>

</li>

 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='31'>Childrens</a>

</li>



 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='1181'>Curves</a>

</li>



 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='1182'>Rulers</a>

</li>


 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='1183'>Mailing Supplies</a>

</li>


 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='1184'>Craft tools</a>

</li>


 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='1185'>Floss</a>

</li>

 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='1186'>Misc crafting</a>

</li>
 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='1187'>Handmade Paper</a>

</li>
 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='1188'>Glitter Paint</a>

</li>

 <li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='1189'>Misc art access</a>

</li>

<hr>


<?

foreach ($cats as $set) {

	if (count($set['subs']) == 0) {
		continue;
	}

	?>

    <li><a href='javascript:void(0);' onclick='ocats(this)' rel='<?=$set['cat']->term_id?>'><?=$set['cat']->name?></a>

    <ul class='subs' style=''>
        <?

	foreach ($set['subs'] as $sub) {
		?>
<li>
    <a href='javascript:void(0);' onclick='choosecat(this)' rel='<?=$sub->term_id?>'><?=$sub->name?></a>
    </li>

    <?
	}
	?>

</ul>

</li>
    <?
}
?>
</ul>

</div>


</div>



<style>
.ta{
    min-height:100px;
}

.ta-td{
    min-height:100px;
}

.isel{
    background:#f00;
    }
</style>



        <script>

function ocats(el) {

    $(".subs").hide();
    $(el).parent().find('.subs').toggle();
}
var curcat='';
var curcatid='';


function maccat(el) {
curcat = $(el);
$(el).css("font-weight", "bold");
}


function choosecat(el) {


     var ucl = $(curcat).closest('li').find('.ucl')
    ucl.text($(el).text())
    ucl.attr('rel',$(el).attr('rel'))




 $.ajax({
        url: "/helper/extractor/assign?cat="+encodeURIComponent($(curcat).text())+"&id="+$(el).attr('rel'),
        context: document.body,
        method: 'get'

    }).done(function(res) {
        res = JSON.parse(res)


    })
}




function addcat(el, n) {
    var ucl = $(el).closest('li').find('.ucl')
    ucl.text(curcat)
    ucl.attr('rel',curcatid)
}

function mine() {


 $.ajax({
        url: "/helper/extractor/mine",
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
       if (res.complete==1) {
alert("DONE")
       } else {
setTimeout(function() {
    mine();

}, 500);
       }
    })
}



        </script>



        <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700&display=swap" rel="stylesheet">



















<style>

body{
    padding-top:0px;
    font-family: 'Open Sans', sans-serif;
}

.dshow{

}
textarea.input-upc.form-control[readonly]{
    padding-top:40px;
    border:0;
    background:none;

    min-height:100px;
}
body .container{
    width:90% !important;
    margin:0 0 0 100px;
}


#lg-img{
    position:absolute;
    right:0;
    z-index:10000;
}
        </style>





</body>

</html>