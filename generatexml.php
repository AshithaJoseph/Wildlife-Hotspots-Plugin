<?php
function find_require($file,$folder=null) {
	if ($folder === null) {$folder = dirname(__FILE__);}
	$path = $folder.'/'.$file;
	if (file_exists($path)) {require($path); return $folder;}
	else {
		$upfolder = find_require($file,dirname($folder));
		if ($upfolder != '') {return $upfolder;}
	}
}
$configpath = find_require('wp-config.php');
require_once(ABSPATH . 'wp-config.php');

function parseToXML( $htmlStr ) {
	$xmlStr = str_replace( '<', '&lt;', $htmlStr );
	$xmlStr = str_replace( '>', '&gt;', $xmlStr );
	$xmlStr = str_replace( '"', '&quot;', $xmlStr );
	$xmlStr = str_replace( "'", '&#39;', $xmlStr );
	$xmlStr = str_replace( "&", '&amp;', $xmlStr );

	return $xmlStr;
}

// Opens a connection to a MySQL server
$connection = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD );
if ( ! $connection ) {
	die( 'Not connected : ' . mysqli_error() );
	//die();
}

// Set the active MySQL database
$db_selected = mysqli_select_db($connection,DB_NAME );
if ( ! $db_selected ) {
	die ( 'Can\'t use db : ' . mysqli_error() );
	//die();
}

// Select all the rows in the markers table
$query  = "SELECT * FROM hotspots WHERE 1";
global $resultArray;
$resultArray= array();
if ($result = mysqli_query( $connection, $query ) ) {
	while ( $row = $result->fetch_assoc() ) {
		array_push( $resultArray, $row );
	}
	if ( count( $resultArray ) ) {
		createXMLfile( $resultArray );
	}
	$result->free();
}

function createXMLfile($resultArray){
	$filePath='hotspots.xml';
	$dom     = new DOMDocument('1.0', 'utf-8');
	$node = $dom->createElement("markers");
	$parnode = $dom->appendChild($node);
	for($i=0; $i<count($resultArray) ; $i++){
		$hotspotId        =  $resultArray[$i]['id'];
		$scientificName   =  $resultArray[$i]['scientificName'];
		$vernacularName   =  $resultArray[$i]['vernacularName'];
		$latitude   =  $resultArray[$i]['latitude'];
		$longitude   =  $resultArray[$i]['longitude'];
		$count   =  $resultArray[$i]['count'];

		$node = $dom->createElement('marker');
		$newnode = $parnode->appendChild($node);
		$newnode->setAttribute('id', $hotspotId);
		$newnode->setAttribute('scientificName', $scientificName);
		$newnode->setAttribute('vernacularName', $vernacularName);
		$newnode->setAttribute('lat', $latitude);
		$newnode->setAttribute('long', $longitude);
		$newnode->setAttribute('count', $count);
	}
	//$dom->appendChild($node);
	$dom->save($filePath);
}


header( "Content-type: text/xml" );
// Start XML file, echo parent node
echo "<?xml version='1.0' ?>";
echo '<hotspots>';
$ind = 0;
// Iterate through the rows, printing XML nodes for each
while ( $row = @mysqli_fetch_assoc( $result ) ) {
	// Add to XML document node

	echo 'id="' . $row['id'] . '" ';
	echo 'scientificName="' . parseToXML( $row['scientificName'] ) . '" ';
	echo 'vernacularName="' . parseToXML( $row['vernacularName'] ) . '" ';
	echo 'latitude="' . $row['latitude'] . '" ';
	echo 'longitude="' . $row['longitude'] . '" ';
	echo 'count="' . $row['count'] . '" ';
	echo '/>';
	$ind = $ind + 1;
}
// End XML file
echo '</hotspots>';