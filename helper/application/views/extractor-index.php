<?php
$this->load->view('header', array('title' => 'Xtractor'));
?>

    <a name='top' id='top'></a>

        <!--
        https://revrocket-static-aws.s3-us-west-2.amazonaws.com/system/assets/lib/js/iframe-resizer/js/iframeResizer.min.js
        -->
        <!--
        https://tinyurl.com/wfj6hw5
        -->





<div class='container main-c'>

<h1>Paintchip Helper</h1>
<h2 id='title'></h2>
<h3 id='ctr'></h3>

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



<!-- <div class='alert alert-info fixers' style=''>
    </div> -->


<!--

<div class='update'>
    </div>
    <input type="text" class='form-control' id='delete_ids' />
    <div class='' style='margin-bottom:10px;'>
<?php
for ($x = 1; $x < 15; $x++) {?>
<label class='btn btn-xs btn-default' >
    <input type='radio' name='csv'  class='check-csv' value='data-<?=$x?>' /> data-<?=$x?>
</label>

<?php
}?>
</div> <button class='btn btn-info' onclick='extract()'>Load/Display CSV</button>
<button class='btn btn-default' onclick='getfix()'>Fix/Retry Failed</button>
<button class='btn btn-primary chks' disabled onclick='get_supplier_data(0)'>Check Supplier</button>






-->
<div class='form-group'>
<div class='input-group'>

<span class='input-group-addon'>Search for:</span>
 <input class='form-control'  id='searcht' placeholder='Text to search for'/>

<span class='input-group-addon'> <button class='btn btn-success  btn-xs'  onclick='textsearch()'><i class='fa fa-search'></i> Search</button></span>
</div>
</div>


<div class='prog'></div>


<div class='well' style='display:none;' id='ihtml'></div>
<!--
 <input class='form-control'  id='startat' placeholder='Start At what INDEX'/>

 <button class='btn btn-danger btn-sm pull-right' onclick='stoploop()'>Stop Image LLOOP</button>
 <button class='btn btn-success btn-sm pull-right' onclick='loopimages(0)'>Get Missing Images</button>




 <button class='btn btn-success btn-sm pull-right' onclick='runMac()'>Do the MAC</button>
 <button class='btn btn-danger btn-sm pull-right' onclick='upcfix()'>Run UPC fix</button>
 <button class='btn btn-danger btn-sm pull-right' onclick='mine()'>Run Mine</button>
 <button class='btn btn-default btn-sm' onclick='approvePrices()'>Approve Category/Prices</button>
 <button class='btn btn-default btn-sm' onclick='getNI()'>Fix/Retry Not Identified</button>

 -->


<div class='row approveprices' style='display: none'>

    <div class='col-md-6 ' >
<h3>Approve Prices</h3>
<div class='alert alert-info' id='apttl'>
</div><div id='lg-img' onclick="$(this).html('')"></div>

<div id='resultsp'></div>
</div>
    <div class='col-md-6 sls1 ' >
</div>
</div>


<div class='row nii' style='display: none'>
    <div class='col-md-6  '>
<h3>Not Identified</h3>
<!-- <a target='_sls' class='btn btn-sm btn-info' href='https://www.slsarts.com/defaultframe.asp'><i class='fa fa-external-link'></i> SLS Arts</a>
 -->
<div id='resultsn'></div>
</div>

    <div class='col-md-6  sls'>
<!--
 -->    </div>


   <!--  <div class='col-md-4'>
<h3>From CSV</h3>
    <button class='btn btn-default btn-xs' onclick='rep_pattern(1)'>Replace w/ second option</button>

<div id='results'></div>
</div>


 <div class='col-md-4'>
 <h3>Fix</h3>
<div id='fix'></div>
</div>





    <div class='col-md-4'>
 <h3>From Supplier</h3>
<div id='supplier_data'></div>
</div> -->
<!--
    <div class='col-md-3'>
<h3>Other</h3>
<div id='resultso'></div>
</div> -->
</div>
<table id="preview-data">
</table>
<!-- <div class='well' id='rep-log'></div>

</div>
 -->

<style>
.ta{
    min-height:100px;
}

.ta-td{
    min-height:100px;
}

</style>



        <script>
document.categories = []
$(document).ready(function() {


 $.ajax({
        url: "/helper/extractor/getCategories",
        context: document.body,
        method: 'get'
    }).done(function(res) {
        document.categories=JSON.parse(res);
    })
})




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



function textsearch() {
    $('#ihtml').show();
    var s=$('#searcht').val();
     $.ajax({
        url: "/helper/extractor/textsearch",
        context: document.body,
        method: 'post',
        data: {
            'str' : s
        }
    }).done(function(res) {
        res = JSON.parse(res)
      console.log(res);
      $('#ihtml').html('<div><button class="btn btn-xs btn-primary" onclick="findimgfromupc()">1. Find new images for the below products</button> -- <button class="btn btn-xs btn-success" onclick="saveimgfromupc()">2. Save all below</button></div>')
var row, html=''
      for(var x=0;x<res.length;x++) {
        row=res[x]
        html="<div> <button class='btn btn-xs btn-danger ' onclick='remove(this)'>x</button>";
        html+="<a class='btn btn-link btn-xs pull-right' target='_blank' href='https://thepaint-chip.com/wp-admin/post.php?post="+row.product_post_id+"&action=edit'>edit product</a>"
        html+="<input type='text'  placeholder='' value='"+row.post_title+"' style='width:440px;' /> - <img class='simg' src='https://thepaint-chip.com/wp-content/uploads/"+row.img+"'/> ";
        html+="<span class=''> <span class='btn-upc' data-upc='"+row.upc+"' ></span><input type='text' ";
        html +=" data-product_post_id='"+row.product_post_id+"'  ";
        html +="data-_wp_attachment_metadata_id='"+row._wp_attachment_metadata_id+"' data-image_post_id='"+row.image_post_id+"' class='newimg' placeholder='New image URL here' /> <button class='btn btn-xs btn-primary btn-update' onclick='updateimg(this)'>Update</button> </span>";
        html+="</div><hr style='clear:both'>"


        $('#ihtml').append(html);
      }
    })

}

function remove(el) {
    $(el).closest('div').remove();
}

function findimgfromupc(el) {
    $('.prog').text('working... find 1 image per 3 seconds')
var n=0;
    $('.btn-upc').each(function() {
       dothing($(this), el, n)
        n++;
        if (n >= $('.btn-upc').length) {
                $('.prog').text('done, save the images below')

        }
    })



}

function dothing(th, el, n) {
    setTimeout(function() {


     var upc = $(th).attr('data-upc');
     var product_post_id = $(th).attr('data-upc');

 $.ajax({
        url: "/helper/extractor/findimage/"+upc,
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
        console.log(res);
$(th).closest('div').find('.simg').attr('src', res.img)
$(th).closest('div').find('.newimg').val(res.img)

       // alert(res.msg)

    })
       }, (n*4000))
}

function updateimg(el,n=0) {
    setTimeout(function() {
var field = $(el).closest('div').find('.newimg');
if (field.val()=="") return;
    var data={
        "newimg" : field.val(),
        "product_post_id" : field.attr('data-product_post_id'),
        "image_post_id" : field.attr('data-image_post_id'),
        "_wp_attachment_metadata_id": field.attr('data-_wp_attachment_metadata_id')
    }
    $.ajax({
        url: "/helper/extractor/updateimg/",
        context: document.body,
        method: 'post',
        data: data
    }).done(function(res) {
        res = JSON.parse(res)
        console.log(res);
$(el).closest('div').find('.simg').attr('src', field.val())
$(el).closest('div').hide();
       // alert(res.msg)

    })
       }, (n*3000))
}

function saveimgfromupc() {
     $('.prog').text('working... saving')
var n=0;
    $('.btn-update').each(function() {
       updateimg($(this),n)
        n++;

        if (n >= $('.btn-upc').length) {
                $('.prog').text('ALL Saved!!')

        }

    })

}

function runMac() {


 $.ajax({
        url: "/helper/extractor/scrapeMac",
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
       if (res.complete==1) {
alert("DONE")
       } else {
setTimeout(function() {
    runMac();

}, 500);
       }
    })
}

var stopl=false

function stoploop(){
    stopl=true
}

function getscrapeMacImg(index) {
    if (!index) $index=0;
$.ajax({
        url: "/helper/extractor/getscrapeMacImg/"+index,
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
        console.log(res);
       if (res.this_id) {

setTimeout(function() {

    getscrapeMacImg(res.this_id);


}, 100);
       }
    })
}




function loopimages(index) {

if (!index || index==0) {
    index = $("#startat").val();
}

 $.ajax({
        url: "/helper/extractor/loopimages/"+index,
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
        console.log(res);
       if (res.startat=="done") {
alert("DONE")
       } else {
setTimeout(function() {
    if (stopl) {
        console.log("STOPPED");
    } else {
    loopimages(res.startat);
}

}, 2100);
       }
    })
}




function doMac() {


 $.ajax({
        url: "/helper/extractor/scrapeMac",
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
       if (res.complete==1) {
alert("DONE")
       } else {
setTimeout(function() {
    runMac();

}, 500);
       }
    })
}





function upcfix() {


 $.ajax({
        url: "/helper/extractor/fixupcs",
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
       if (res.complete==1) {
alert("DONE")
       } else {
setTimeout(function() {
    upcfix();

}, 500);
       }
    })
}


function rep_pattern(n) {
    if (n==1) {
        //replace DA with seceond option
        $(".csv-data").each(function() {
            var curid = $(this).attr('data-id');
            var linedata = $(this).find('.ta').val()
        if (linedata && linedata.indexOf("LINE 2")!=-1) {
        linedata = linedata.split("LINE 2:");
        linedata = JSON.parse(linedata[linedata.length-1])
        console.log("linedata", linedata);
        if (!linedata[1] || linedata[1].length<4 ) return;
        linedata[1] = linedata[1].toUpperCase();

    linedata[1] = linedata[1].replace(/\W/g, '');

        if (linedata[1]!=curid){
            var other =linedata[1]
             var firstcharstring = other.substr(0,1) != 0 && other.indexOf("$") == -1 && other.substr(0,1) != "0" && isNaN(parseInt(other.substr(0,1)))
        if (firstcharstring) {
        $(this).find('.id-h').append("<label class=''>" + linedata[1].trim() + "</label>")
        $(this).find('.id-h strong').css("text-decoration","line-through")
        $(this).find('.id-h strong').addClass("text-danger");
         $(this).attr('data-id', linedata[1].trim())

         $("#rep-log").append("<p>replaced "+curid + " with " + linedata[1].trim())

    }
            //var second =
        }
    }
})
    }
}


function getNI() {
    $('.nii').show()
    $('.approveprices').hide()
 $.ajax({
        url: "/helper/extractor/getfix",
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
        var html=""
        for(var x=0;x<res.length;x++) {
            html += getNIHTML(res[x].tmp_data, res[x].id)
        }
/*
html ="<button class='btn btn-xs btn-warning' onclick='switchids(this)'><i class='fa fa-reload'></i> Attempt Switch Ids</button> <button class='btn btn-xs btn-success' onclick='gsd(\".retry-csv-data\")'><i class='fa fa-arrow-right'></i> Run it</button> " + html*/
        $('#resultsn').append(html);

        $(".sls").html('<iframe src="https://www.slsarts.com/defaultframe.asp" style="border:0;width:100%;height:1000px" />')
    })
}


function approvePrices() {
    $('.approveprices').show()
    $('.nii').hide()

 $.ajax({
        url: "/helper/extractor/getGood",
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
        var html=""
        for(var x=0;x<res.good.length;x++) {
            html += getPriceHTML(res.good[x], res.good[x].id)
        }
/*
html ="<button class='btn btn-xs btn-warning' onclick='switchids(this)'><i class='fa fa-reload'></i> Attempt Switch Ids</button> <button class='btn btn-xs btn-success' onclick='gsd(\".retry-csv-data\")'><i class='fa fa-arrow-right'></i> Run it</button> " + html*/
        $('#apttl').html("Missing price: <strong>"+ res.noprice + "</strong> / Missing Category: <strong>"+ res.nocat+"</strong> / Approved: <strong>"+res.approved+"</strong> / Not Approved: <strong>"+res.notapproved+"</strong>")
        $('#resultsp').html(html)
        $(".sls1").html('<iframe src="https://www.slsarts.com/defaultframe.asp" style="border:0;width:100%;height:1000px" />')

    })
}




function getPriceFromLinedata(linedata) {

}

function getCategories() {
    var cats = [
    {
        name: "Paint",
        id:0
    },
    {
        name: "General Art Supplies",
        id:0
    }

    ]

    return cats
}

function setsugprice(el) {
    $(el).closest('.sup-data').find('.input-price').val($(el).attr('data-sp'))
}
function getPriceHTML(line, id) {



    if (!line.title)  line.title="";
    if (!line.price)  line.price="";
    if (!line.category)  line.category="";
    if (!line.suggestedprice)  line.suggestedprice="";


    var html="";
            html += "<div class='sup-data' data-id=" + id + "><hr>"
            html+=" <dd>"
         if (line.image) {
            html += " <img src='" + line.data.orig_img + "' onclick='showLargeImg(this)' class='pull-right' height='50px;'/>";
            }
            html +="<strong>ID: " + line.sku + "</strong><br>" + line.title + " <a class='small' href='javascript:void(0)' onclick='showdesc(this)'>(desc <i class='fa fa-arrow-down'></i>)</a></dd>" ;

            html+=" <dd class='dshow' style='display:none;'>" + line.description + "</dd>" ;

            html+=" <hr class='ttl-sep'>"



              if (line.suggestedprice != '') {
                html+=" <dd style='margin:5px 0'><a href='javascript:void(0);' onclick='setsugprice(this)' data-sp='" + line.suggestedprice + "' ><i class='fa fa-arrow-down'></i> Use price: $" + line.suggestedprice + "</a></dd>";

            }
              html+=" <dd style='margin:5px 0'><div class='input-group'>";
              html += "<label class='input-group-addon'>"
             html += "Title"
            html += "</label>" ;
            html +="<input class='form-control input-lg input-title' value=\""+line.title+"\" placeholder='Title' />"
            html += "<label class='input-group-addon'>"
             html += "$"
            html += "</label>" ;
            html +="<input class='form-control input-lg input-price' value='"+line.price+"' placeholder='0.00' />"
             html += "<label class='input-group-addon'>"
             html += "<i class='fa fa-list'></i>"
            html += "</label>" ;
              html +="<select class='catsel input-lg input-cat form-control'>";
                 html+="<option value=''>--</option>"

            for(var x=0;x<document.categories.length;x++) {
                var sel = document.categories[x] == line.category ? "selected='selected'":"";
                html+="<option "+sel+">"+document.categories[x]+"</option>"
            }
            html +="</select>";

            html +="</div></dd><hr>"
html +="<button class='btn btn-success  pull-right' onclick='saveApprove(this)'><i class='fa fa-floppy-o'></i> Save and Approve</button>";
html +="<button class='btn btn-default' onclick='saveForLater(this)'><i class='fa fa-times'></i> Do it Later</button>";

                         html +="</div>"

            return html


}
function saveForLater(el){
var container=$(el).closest(".sup-data");
 container.css('opacity', '.7')
 $("<div class='alert alert-warning'>Save for later</div>").insertAfter(container.find(".ttl-sep"))
    container.remove().appendTo("#resultsp")
     $("#lg-img").html("");
var data={
    id: container.attr('data-id'),
    price: container.find('.input-price').val(),
    category: container.find('.input-cat').val()

}
$.ajax({
        url: "/helper/extractor/saveForLater",
        context: document.body,
        method: 'post',
        data:data
    }).done(function(res) {
    container.css('opacity', '.3')
    container.remove().appendTo("#resultsp")
     $("#lg-img").html("");
    })


}

function saveApprove(el) {
var container=$(el).closest(".sup-data");

var data={
    id: container.attr('data-id'),
    price: container.find('.input-price').val(),
    title: container.find('.input-title').val(),
    category: container.find('.input-cat').val()

}
if (data.price=='' || data.category=='') {
    alert('Price or category is missing')
    return
}
console.log(data);

$.ajax({
        url: "/helper/extractor/saveApprove",
        context: document.body,
        method: 'post',
        data:data
    }).done(function(res) {
    container.css('opacity', '.3')
    container.remove().appendTo("#resultsp")
     $("#lg-img").html("");
    })

}

function showLargeImg(el) {
    $("#lg-img").remove().insertAfter($(el)).html("<img src='" + $(el).attr('src') + "' width='100%' />")

}

function getfix() {
 $.ajax({
        url: "/helper/extractor/getfix",
        context: document.body,
        method: 'get'
    }).done(function(res) {
        res = JSON.parse(res)
        var html=""
        for(var x=0;x<res.length;x++) {
            html += getFixHTML(res[x].tmp_data, res[x].id)
        }

html ="<button class='btn btn-xs btn-warning' onclick='switchids(this)'><i class='fa fa-reload'></i> Attempt Switch Ids</button> <button class='btn btn-xs btn-success' onclick='gsd(\".retry-csv-data\")'><i class='fa fa-arrow-right'></i> Run it</button> " + html
        $('#fix').append(html)
    })
}

function switchids(el) {
    $(".retry-csv-data").each(function() {
        var elem = $(this)
elem.attr('data-id', 'test')
          var curid = $(this).find('.input-retry-id').val();
        var linedata = $(this).find('.ta-td').val()
        if (linedata && linedata.indexOf("LINE 2")!=-1) {
        linedata = linedata.split("LINE 2:");
        linedata = JSON.parse(linedata[linedata.length-1])
        console.log("linedata", linedata);
         if (linedata[1])  other = linedata[1];
        else other =  curid;
    } else other = curid
other = retother(other)

       // elem.hide();//attr("data-id", other);//append('<p>suggest: '+other)



        $(elem).find(".input-retry-id").val(other);//append('<p>suggest: '+other)
        $(elem).attr("data-id", other);//append('<p>suggest: '+other)







    })
}

function retother(curid) {


var other = curid


             other = other.replace("IB","TB")
            other = other.replace(/l/g,"1");
            other = other.replace("SO2","SG2");


            other = other.replace(/S/g, function(str,index){
                console.log("S:",str,index);
                var out = (index>2) ? "5" : str
                console.log("OUT",out);
                return out;
            })

             other = other.replace(/I/g, function(str,index){
                console.log("I:",str,index);

                var out = (index>1) ? "1" : str
                console.log("OUT",out);
                return out;
            })

             other = other.replace("SETO1L", "SETOIL")
             other = other.replace("GP1KIT", "GPIKIT")

               var firstcharstring = other.substr(0,1) != 0 && other.indexOf("$") == -1 && other.indexOf(".") == -1 && other.substr(0,1) != "0" && isNaN(parseInt(other.substr(0,1)))

if (!firstcharstring) {
    console.log("NOT SR:",other);
    return curid
}

return other
}

function retry(el) {
    $(".try-me:checked").each(function() {
console.log("FIX ME "+$(this).parent().find('.input-retry-id').val());
    })
}

function getUPCFromLineData(linedata) {
 if (linedata && linedata.indexOf("LINE 2")!=-1) {
        linedata = linedata.split("LINE 2:");
        linedata = JSON.parse(linedata[linedata.length-1])
        console.log("linedata", linedata);
        for (var i = linedata.length - 1; i >= 0; i--) {
            var test = linedata[i]
            test = test.replace(/\D+/g, '');
            if (test.length==12) {
                return test
            }
        }
        }
        return ""
}



function copyThis(el) {
console.log("copythis");
$(el).removeClass('btn-info').addClass('btn-default')
$(el).find('.fa').removeClass('fa-clipboard').addClass('fa-check')

   var input=$(el).closest('.ni-csv-data').find('.input-upc');
    input = input[0]
console.log("input",input);
    input.select();
  input.setSelectionRange(0, 99999); /*For mobile devices*/

  /* Copy the text inside the text field */
  document.execCommand("copy");

  /* Alert the copied text */

}

function preflighttitle(title) {
    title = title.replace("V.g. Oll Pas6","Van Gogh Oil Pastel - ")
    title = title.replace("V.g. Oil Pas6","Van Gogh Oil Pastel - ")
    return title;
}

function getNIHTML(line, theid) {


     if (line.title=="Fixed")return ""
    console.log(line);
    if (!line.title)  line.title="";
    if (!line.price)  line.price="";
    if (!line.category)  line.category="";
    if (!line.linedata) {
        line.linedata=''
        return""

    }else {
        var upc = getUPCFromLineData(line.linedata);
        if (!upc) return "" ;
    }

    line.title = preflighttitle(line.title)
var stuff="";
     stuff = " class='ni-csv-data' data-id='" + line.id + "' data-origid='" + line.id + "' data-title='" + line.title + "' data-price='" + line.theprice + "' data-category='" + line.category + "' " ;
//https://www.slsarts.com/defaultframe.asp

    var html="";

            html += "<div  "+stuff+"><hr>"
            html+=" <dd><div class='input-group'>";
            html += "<label class='input-group-addon'>"
             html += "UPC code"
            html += "</label>" ;
            html+="<textarea class='form-control input-upc' style='' readonly >" + upc + "</textarea>"
            html += "<label class='input-group-addon'>"
             html += "<button class='btn btn-xs btn-info' onclick='copyThis(this)'><i class='fa fa-clipboard'></i></button> ";
html+="  "
             html+="  "
            html += "</label>" ;
            html += "<input class='form-control input-title' style=' '  placeholder='Paste Title here...' value='"+line.title+"' />";

            html += "<input class='form-control input-sku' style=' '  placeholder='Paste SKU here...' value='' />";



            html += "<input class='form-control input-price' style=' '  placeholder='Paste Price here...' value='' />";

             html +="<select class='catsel input-cat form-control'>";
                 html+="<option value=''>-- Choose Category --</option>"

            for(var x=0;x<document.categories.length;x++) {
                var sel = document.categories[x] == line.category ? "selected='selected'":"";
                html+="<option "+sel+">"+document.categories[x]+"</option>"
            }
            html +="</select>";




            html += "<span class='input-group-addon completion'><button class='btn btn-xs btn-success' onclick='tryNewSKU(this)'><i class='fa fa-arrow-right'></i></button></span>"
            html+= "</div></dd>" ;
            html+="<dd style='margin-top:10px;' class='small text-muted'><button class='btn btn-link btn-xs pull-right' onclick='removeItem(this)'><span class='text-danger'><i class='fa fa-trash-o '></i> remove</span></button> <span onclick='togdesc(this)'>"+line.title+"</span></div>"
          /*  html+=" <dd>Title: " + line.title + "</dd>" ;
            html+=" <dd>Category: " + line.category + "</dd>" ;
            html+=" <dd>Price: $"  + line.theprice + " <i>(" + line.price + ")</i></dd>" ;*/
            var style="";
             //style=" style='display:none;' " ;
         html+=" <dd style='height:0;visibility:hidden;overflow:hidden'>"
           html += "<textarea " + style + "  class='form-control ta-td'>" + line.linedata + "</textarea>"; /* */
           html += "</dd>" ;
            html +="</div>"

            return html


}

function togdesc(el) {
    $(el).parent().find('.nidesc').toggle()
}

function removeItem(el){
    var sku = $(el).closest('.ni-csv-data').attr('data-id');
    $.ajax({
        url: "/helper/extractor/removeItem/"+sku,
        context: document.body,
        method: 'get'
    }).done(function(res) {
    $(el).closest('.ni-csv-data').remove()

    })
}

function tryNewSKU(el) {
    var sku=$(el).closest('.ni-csv-data').find('.input-sku').val();
    if (!sku){
        alert("No sku, try again")
        return
    }

var updater = $(el).closest('.ni-csv-data').find('.completion');
var container = $(el).closest('.ni-csv-data');


    var sku = $(container).find('.input-sku').val()
    var price = $(container).find('.input-price').val()


    var title = $(container).find('.input-title').val()
    var category = $(container).find('.input-cat').val()
if (!sku || ! price || ! category || ! title) {
    alert("Please make sure the title, SKU, price and category are chosen")
    return
}

updater.html("<i class='fa fa-spin fa-cog'></i> Searching for: "+sku );
var res = getSupplierDataBySKU(sku, container)
}




 function getSupplierDataBySKU(sku, container) {

    sku = sku.replace(/\W/g, '');
    var updater = container.find('.completion')
     console.log("container",container);
 var origid = $(container).attr('data-origid')
   // var title = $(container).attr('data-title')
    var price = $(container).find('.input-price').val()
    var category = $(container).find('.input-cat').val()
    var title = $(container).find('.input-title').val()
    var linedata = $(container).find('textarea').val()

    var supplier = "SS";
    $.ajax({
        url: "/helper/extractor/getSupplierData/"+supplier+"/"+sku,
        context: document.body,
        method: 'post',
        data:{
             linedata:linedata,
            origid:origid,
            price:price,
            title:title,
            category:category,
            data_batch:'',
            oneoff:1
        }
    }).done(function(res) {
        res = JSON.parse(res);


if (res.exists==1) {
updater.html("<i class='fa fa-check'></i> Exists: "+sku );
} else if (res.nodata==1) {
updater.html("<i class='fa fa-ban'></i> No Data: "+sku );
} else {

updater.html("<i class='fa fa-check text-success animated bounceIn'></i> SAVED: "+sku );

        }


container.css('opacity', '.2')
    container.remove().appendTo("#resultsn")

})

}




function getFixHTML(line, theid){
    if (line.title=="Fixed")return ""
    console.log(line);
    if (!line.title)  line.title="";
    if (!line.price)  line.price="";
    if (!line.category)  line.category="";
    if (!line.linedata) {
        line.linedata=''

    }else {

    }
var stuff="";
     stuff = " class='retry-csv-data' data-id='" + line.id + "' data-origid='" + line.id + "' data-title='" + line.title + "' data-price='" + line.theprice + "' data-category='" + line.category + "' " ;


    var html="<hr>";

            html += "<div  "+stuff+">"
            html+=" <dd><div class='input-group'>";
            html += "<label class='input-group-addon'>"
            html += "<input type='checkbox' class='try-me pull-left' value='"+theid+"' />"
            html += "</label>" ;
            html += "<input class='form-control input-retry-id' style=' ' onblur='popid(this)' onfocus='$(this).parent().find(\".try-me\").prop(\"checked\",true)' value='" + line.id + "' /></div></dd>" ;
          /*  html+=" <dd>Title: " + line.title + "</dd>" ;
            html+=" <dd>Category: " + line.category + "</dd>" ;
            html+=" <dd>Price: $"  + line.theprice + " <i>(" + line.price + ")</i></dd>" ;*/
            var style="";
             //style=" style='display:none;' " ;
           html+=" <dd><button class='btn btn-sm btn-link' onclick='togit(this);'>raw data</button>"
           html += "<textarea " + style + "  class='form-control ta-td'>" + line.linedata + "</textarea>";
           html += "</dd>" ;
            html +="</div>"

            return html
}
function popid(el) {
    $(el).closest(".retry-csv-data").attr("data-id", $(el).val())
}
function getHTML(line, gooddata){
    if (!line.title)  line.title="";
    if (!line.price)  line.price="";
    if (!line.category)  line.category="";
var stuff="";
if (gooddata) {
    stuff = " class='csv-data' data-id='" + line.id + "' data-title='" + line.title + "' data-price='" + line.theprice + "' data-category='" + line.category + "' " ;
}

    var html="<hr>";

            html += "<div  "+stuff+">"
            html+=" <dd class='id-h'><strong>ID: " + line.id + "</strong></dd>" ;
            html+=" <dd>Title: " + line.title + "</dd>" ;
            html+=" <dd>Category: " + line.category + "</dd>" ;
            html+=" <dd>Price: $"  + line.theprice + " <i>(" + line.price + ")</i></dd>" ;
            var style="";
             //style=" style='display:none;' " ;
            html+=" <dd><button class='btn btn-sm btn-link' onclick='togit(this);'>raw data</button><textarea " + style + "  class='form-control ta'>" + line.odata + "</textarea></dd>" ;
            html +="</div>"

            return html
}


function getSupplierHTML(line) {

    if (!line.title)  line.title="";
    if (!line.price)  line.price="";
    if (!line.category)  line.category="";


    var html="<hr>";
            html += "<div class='sup-data' data-id=" + line.id + ">"
            html+=" <dd><strong>ID: " + line.id + "</strong></dd>" ;
            html+=" <dd>Title: " + line.title + "</dd>" ;
         if (line.img) {
            html+=" <dd>Image: <img src='" + line.orig_img + "' height='50px;'/></dd>" ;
        }
            html+=" <dd>Description: " + line.description + "</dd>" ;
            html+=" <dd>Category: " + line.category + "</dd>" ;
            html+=" <dd>Price: $"  + line.price + "</dd>" ;
                        html +="</div>"

            return html


}












function togit(el){
    $(el).parent().find('.ta').toggle();
}

 function extract() {
var file = $('.check-csv:checked').val()

       $.ajax({
        url: "/helper/extractor/getCSV/"+file,
        context: document.body,
        method: 'get'
    }).done(function(res) {
        console.log('done');
        res = JSON.parse(res)
        var line, out=""

        $("#title").html(res.found.length + " FOUND / " + res.not.length + " Unidentified")
        for(var x=0;x<res.found.length; x++) {
            line = res.found[x]
            console.log("LINE",line);


            out += getHTML(line, 1)
        }
            $("#results").html(out)



          /**/  var line, out=""
        for(var x=0;x<res.not.length; x++) {
            line = res.not[x]
            var html="<hr>";
            console.log("LINE",line);
            out += getHTML(line)

        }
            $("#resultsn").html(out)


/*
              var line, out=""
        for(var x=0;x<res.other.length; x++) {
            line = res.other[x]
            console.log("LINE other",line);
            out += getHTML(line)

        }
            $("#resultso").html(out)
*/


    });

    $(".chks").attr('disabled',false)
 }


var cur_class=".csv-data";
 function gsd(who) {
  cur_class=who
get_supplier_data(0)
 }


 var deletes=[]



 function get_supplier_data(ctr) {
    $(".chks").attr('disabled',true)

    var data = $(cur_class);
    if (!data[ctr]) {
    $(".update").html("<div class='alert alert-success'>Got em!</div>");

        return
    }
    data = data[ctr];
    var id = $(data).attr('data-id');
    id = id.replace(/\W/g, '');
    var origid = $(data).attr('data-origid')
    var title = $(data).attr('data-title')
    var price = $(data).attr('data-price')
    var category = $(data).attr('data-category')
    var linedata = $(data).find('textarea').val()

    $(".update").html("<div class='alert alert-info'><i class='fa fa-spin fa-cog'></i> Searching for: "+id+"</div>");
    var supplier = "SS";
    $.ajax({
        url: "/helper/extractor/getSupplierData/"+supplier+"/"+id,
        context: document.body,
        method: 'post',
        data:{
            linedata:linedata,
            origid:origid,
            data_batch:$('.check-csv:checked').val()
        }
    }).done(function(res) {
        res = JSON.parse(res);

        console.log('done',res);
if (res.exists==1) {
        $('#supplier_data').append("<hr><strong>EXISTS: "+res.id+"</strong>") ;
       if (res.origid && cur_class!='.csv-data') {


        var newval = $("#delete_ids").val() + ",'" + res.origid +"'";
        $("#delete_ids").val(newval)
   }
        } else if (res.nodata==1) {
        $('#supplier_data').append("<hr><strong>NO DATA for "+res.id+"</strong>") ;




        $.ajax({
        url: "/helper/extractor/saveTempSupplierData/"+supplier+"/"+id,
        context: document.body,
        method: 'post',
        data:{
            id: id,
            title: title,
            price: price,
            origid:null,

            linedata: linedata,
            supplier: supplier,
            data_batch:$('.check-csv:checked').val(),
            category:category
        }

        }).done(function(res2){

        $('#supplier_data').append(".. saved temp data for  "+res.id) ;


        })


        } else {

         if (!res.id) res.id=id;
        res.price=price;
        res.category=category;


        var tag = getSupplierHTML(res)
        $('#supplier_data').append(tag)



        }



                // html
        var timer = 1500 + Math.random() * 1000;
            $("#ctr").html(ctr + " processed")

        setTimeout(function() {
            ctr++;
/*if (ctr<15) */
get_supplier_data(ctr)
         }, timer)
})

}










function showdesc(el){
console.log($(el));
console.log($(el).closest('.sup-data'));
console.log($(el).closest('.sup-data').find('dd.dshow'));

$(el).closest('.sup-data').find('dd.dshow').toggle()
}

function popPreview(result) {
    result=JSON.parse(result)
            $.each(result, function(key, obj){
                itemList.push(obj)
            })
            console.log("itemList, ", itemList);



             theTable = $('#preview-table').DataTable( {
        data: itemList,
        iDisplayLength:100,
        columns: [
            { title: "Prod Code" },
            { title: "Desc" },
            { title: "UPC" },
            { title: "MSRP" }
         ]
        } );



}


        </script>

        <?php
}?>
<?php
$this->load->view('footer', array());
?>
