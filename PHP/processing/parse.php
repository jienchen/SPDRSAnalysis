<?php
	session_start();
	include('simple_html_dom.php');

	$ticker = strtoupper(stripslashes(trim(htmlspecialchars($_GET["ticker"]))));
	$_SESSION['ticker'] = $ticker;

	$html = file_get_html("https://www.spdrs.com/product/fund.seam?ticker=".$ticker);

	$connection = mysqli_connect('localhost','root','',etfcsv);
	if(!$connection){
		die("Could not connect ".mysql_error());
	}

	$result = $connection->query("SHOW TABLES LIKE '$ticker'");
	$tableExists = mysqli_num_rows($result) > 0;

	$sql = "CREATE TABLE IF NOT EXISTS $ticker
	(
  		id              int unsigned NOT NULL auto_increment,
  		type  		  varchar(1) NOT NULL,
  		name		  varchar(50) NOT NULL,
  		percentage	  DECIMAL(5,2) NOT NULL,
  		shares          int unsigned NOT NULL,

  		PRIMARY KEY     (id)
	)";
	$connection->query($sql);

	//ETF Name and Description
	$name = $html->find("h1",0)->plaintext;                                                                                                                                  
	echo "<h1>$name ($ticker)</h1>";
	$description = $html->find("div.objective",0)->first_child();
	if (is_null($description)){
		echo "Invalid ETF Code"."\n";
		echo "<a href='javascript:history.back(1);'>Try again</a>";
		$connection->close();
		die();
	}

	if(!$tableExists){
		$csvfile = fopen($ticker.".csv","w");
	}

	//Top 10 Holdings
	$holdingschart = $html->find("div[id=FUND_TOP_HOLDINGS]",0);
	$holdingdate = substr($html->find("div[id=FUND_TOP_HOLDINGS]",0)->first_child(), 32);
	echo "<div style ='float:left; width:50%'>";
	echo "<h2>Top 10 Holdings</h2>";
	echo "<table>";
	echo "<tr>";
	echo "<td>"."Name"."</td>";
	echo "<td align = \"right\">"."Weight"."</td>";
	echo "<td align = \"right\">"."Shares Held"."</td>"."</tr>";

	$holdings = $html->find("div[id=FUND_TOP_HOLDINGS]",0)->children(1);
	for ($i = 1; $i < 11; $i++){
		$holdingName = $holdings->children[$i]->find("td.label",0)->plaintext;
		$holdingPercent = $holdings->children[$i]->find("td.data",0)->plaintext;
		$holdingShares = $holdings->children[$i]->find("td.data",1)->plaintext;
		$holdingSharesREAL = str_replace(",", "", $holdingShares);
		echo "<tr>";
		echo "<td>".$holdingName."</td>"; //Name
		echo "<td align = \"right\">".$holdingPercent."</td>"; //Weight
		echo "<td align = \"right\">".$holdingShares."</td>"; //Shares Held
		echo "</tr>";
		if (!$tableExists){
			fwrite($csvfile, $holdings->children[$i]->find("td.label",0)->plaintext.", "
			.$holdings->children[$i]->find("td.data",0)->plaintext.", "
			.$holdingSharesREAL."\n");
			$sql = "\nINSERT INTO $ticker (type,name,percentage,shares) 
			VALUES ('h','$holdingName','$holdingPercent','$holdingSharesREAL')";
			$connection->query($sql);
		}
	}
	echo "</table>";
	echo "</div>";
	echo "<div style ='float:left; width:50%'>
	<h2>Fund Description</h2>$description<br><br><br>
	<h2>CSV Download Link</h2><a href='download.php'>$ticker.csv</a>
	</div>";

	//Sector Weight
	$sectorChart = $html->find("div[id=FUND_SECTOR]",0);
	$sectorDate = substr($sectorChart->find("div.asOf",0), 36);
	$sectorXMLtext = htmlspecialchars_decode($sectorChart->find("div[style=display: none]",0)->plaintext);
	$sectorXML = simplexml_load_string($sectorXMLtext);
	$sectorAttributes = $sectorXML->attributes[0];

	$total = 0.00;
	for ($j = 0; $j < $sectorAttributes->count(); $j++){
		$sectorName = $sectorXML->attributes[0]->attribute[$j]->label;
		$sectorPercent = floatval($sectorXML->attributes[0]->attribute[$j]->value);
		if (!$tableExists){
			fwrite($csvfile, $sectorName.",".$sectorPercent."\n");
			$sql = "\nINSERT INTO $ticker (type,name,percentage) 
			VALUES ('s','$sectorName','$sectorPercent')";
			$connection->query($sql);
			$total += $sectorPercent;
		}
	}

	if(!$tableExists){
		if($total != 100.00){
			$total = (100.00-$total);
			$sql = "\nINSERT INTO $ticker (type,name,percentage) 
				VALUES ('s','Other','$total')";
				$connection->query($sql);
		}
	}
?>

<style type="text/css">
	#holdingchartdiv {
		width		: 100%;
		height		: 500px;
		font-size	: 11px;
	}
	table{
		border-collapse: collapse;
		width: 85%;
	}
	td,th{
		padding: 5px;
		border-bottom: 1px solid #E6E6E6;
	}
	tr:nth-child(even){background-color: #E6E6E6}
</style>

<script src="http://www.amcharts.com/lib/3/amcharts.js"></script>
<script src="http://www.amcharts.com/lib/3/serial.js"></script>
<script src="http://www.amcharts.com/lib/3/pie.js"></script>
<script src="https://www.amcharts.com/lib/3/themes/light.js"></script>
<script src="http://www.amcharts.com/lib/3/plugins/dataloader/dataloader.min.js"></script>
<body>
  <div id="holdingchartdiv"></div>
   <script>   
	var chart = AmCharts.makeChart( "holdingchartdiv", {
		"titles": [
		{
			"text": "Top 10 Holdings",
			"size": 15
		}
		],
  		"type": "serial",
  		"theme": "light",
  		"fontFamily": "Arial Narrow",
  		"fontSize" : 12,
  		"dataLoader": {
    		"url": "holdingdata.php"
   		},
  		"valueAxes": [ {
    		"gridColor": "#FFFFFF",
    		"gridAlpha": 0.2,
    		"dashLength": 0,
    		"title" : "Shares"
  		} ],
  		"gridAboveGraphs": true,
  		"startDuration": 0.5,
  		"graphs": [ {
    		"balloonText": "[[category]]: <b>[[value]] ([[percentage]]%)</b>",
    		"fillAlphas": 0.9,
    		"lineAlpha": 0.3,
    		"type": "column",
    		"valueField": "shares"
  		} ],
  		"chartCursor": {
  			"categoryBalloonEnabled": false,
    		"cursorAlpha": 0,
    		"zoomable": false
  		},
  		"categoryField": "name",
  		"categoryAxis": {
  			"gridPosition": "start",
    		"gridAlpha": 0,
    		"tickPosition": "start",
    		"tickLength": 20,
    		"title": "Holdings",
    		labelFunction: function (value, valueText, categoryAxis) {
    			if(value.length >27 )
    				return value.substring(0,27).concat("\n").concat(value.substring(27,value.length));
            	else
            		return value;
            }
  		},
  		"export": {
    		"enabled": true
  		}
	});
</script>

<div id="sectorchartdiv"></div>
  	<script>   
	var sectorchart = AmCharts.makeChart("sectorchartdiv", {
		"titles": [
		{
			"text": "Sector Weight",
			"size": 15
		}
		],
 		"type": "pie",
 		"fontFamily": "Arial",
 	 	"startDuration": 0,
  		"addClassNames": true,
  		"legend":{
   			"position":"right",
    		"marginRight":50,
    		"autoMargins":false,
    		"valueText": "[[percentage]]%"
  		},
  		"innerRadius": "0%",
  		"defs": {
    		"filter": [{
      			"id": "shadow",
     			"width": "200%",
     			"height": "200%",
      			"feOffset": {
        		"result": "offOut",
        		"in": "SourceAlpha",
        		"dx": 0,
        		"dy": 0
      		},
      		"feGaussianBlur": {
        		"result": "blurOut",
        		"in": "offOut",
        		"stdDeviation": 5
      		},
      		"feBlend": {
        		"in": "SourceGraphic",
        		"in2": "blurOut",
        		"mode": "normal"
      		}
    	}]
  		},
  		"dataLoader": {
     		"url": "sectordata.php"
    	}, 
  		"valueField": "percentage",
  		"titleField": "name",
  		"balloonText": "[[name]]: <b>[[percentage]]%</b>",
  		"export": {
    		"enabled": true
  		}
});

sectorchart.addListener("init", handleInit);

sectorchart.addListener("rollOverSlice", function(e) {
  handleRollOver(e);
});

function handleInit(){
  sectorchart.legend.addListener("rollOverItem", handleRollOver);
}

function handleRollOver(e){
  var sectorwedge = e.dataItem.wedge.node;
  sectorwedge.parentNode.appendChild(sectorwedge);  
}
</script>

<?php
	//Country Weight	
	$countryChart = $html->find("div[id=FUND_COUNTRY_WEIGHTS]",0);
	if (is_null($countryChart)){
		$connection->close();
		fwrite($csvfile, "No Country Weight Provided");
		echo "No Country Weight Provided"."</body><br>";
		echo "Last updated: ".$holdingdate;
		die();
	}
	$countryDate = substr($countryChart->find("div.asOf", 0), 23);

	$total = 0;
	for ($k = 1; $k < count($countryChart->children[1]->find("tr")); $k++){
		$countryName = $countryChart->children[1]->children[$k]->find("td.label",0)->plaintext;
		$countryPercent = floatval($countryChart->children[1]->children[$k]->find("td.data",0)->plaintext);
		if(!$tableExists){
			fwrite($csvfile, $countryChart->children[1]->children[$k]->find("td.label",0)->plaintext.", " //Country
			.$countryChart->children[1]->children[$k]->find("td.data",0)->plaintext."\n"); //Weight in percentage
			$sql = "\nINSERT INTO $ticker (type,name,percentage) 
			VALUES ('c','$countryName','$countryPercent')";
			$connection->query($sql);
			$total += $countryPercent;	
		}
	}

	if(!$tableExists){
		if($total != 100){
			$total = (100-$total);
			$sql = "\nINSERT INTO $ticker (type,name,percentage) 
				VALUES ('c','Other','$total')";
				$connection->query($sql);
		}
	}

	$connection->close();
?>

<div id="countrychartdiv"></div>
  	<script>   
	var countrychart = AmCharts.makeChart("countrychartdiv", {
		"titles": [
		{
			"text": "Country Weight",
			"size": 15
		}
		],
 		"type": "pie",
 		"fontFamily": "Arial",
 	 	"startDuration": 0,
  		"addClassNames": true,
  		"legend":{
   			"position":"right",
    		"marginRight":100,
    		"autoMargins":false,
    		"valueText": "[[percentage]]%"
  		},
  		"innerRadius": "0%",
  		"defs": {
    		"filter": [{
      			"id": "shadow",
      			"width": "200%",
      			"height": "200%",
      		"feOffset": {
        		"result": "offOut",
        		"in": "SourceAlpha",
        		"dx": 0,
        		"dy": 0
      		},
      		"feGaussianBlur": {
        		"result": "blurOut",
        		"in": "offOut",
        		"stdDeviation": 5
      		},
      		"feBlend": {
        		"in": "SourceGraphic",
        		"in2": "blurOut",
        		"mode": "normal"
      		}
    		}]
  		},
  		"dataLoader": {
     		"url": "countrydata.php"
    	}, 
 		"valueField": "percentage",
  		"titleField": "name",
  		"balloonText": "[[name]]: <b>[[percentage]]%</b>",
  		"export": {
    		"enabled": true
  		}
});

countrychart.addListener("init", handleInit);

countrychart.addListener("rollOverSlice", function(e) {
  handleRollOver(e);
});

function handleInit(){
  countrychart.legend.addListener("rollOverItem", handleRollOver);
}

function handleRollOver(e){
  var countrywedge = e.dataItem.wedge.node;
  countrywedge.parentNode.appendChild(countrywedge);  
}
</script>
</body>

<?php
	echo "Last updated: ".$holdingdate;
?>