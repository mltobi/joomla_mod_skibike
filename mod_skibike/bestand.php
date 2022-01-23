<?php
defined('_JEXEC') or die('Restricted access: It is not allowed to open this file directly!');

// report all errors (debug)
ini_set('display_errors', 'On');
error_reporting(E_ALL);

header("Content-Type: text/html;charset=UTF-8");

$event = str_replace("'", "\\'", html_entity_decode($jumi[0])); // escape "'"
if( $event != "" ) {
  echo "<div style='position: relative;'>\n";
  echo "  <div style='position: absolute;top: 0px;right: 60px;'>\n";

  // open database
  $db = JFactory::getDBO();

  $query = $db->getQuery(true)
              ->select("product_in_stock")
              ->from("joomla_virtuemart_products_de_de pdd, joomla_virtuemart_products p")
              ->where("p.published = '1' AND pdd.virtuemart_product_id = p.virtuemart_product_id AND pdd.product_name = '$event'");
  $db->setQuery($query);
  $item = $db->loadObjectList();
  $row = $item[0];

  echo "    <span title='$event'>Noch <b>$row->product_in_stock</b> Pl&auml;tze verf&uuml;gbar.</span><br />\n";
  echo "    <span style='color:darkgrey;font-size=50%;'>&Uuml;berbuchen wird nicht akzeptiert!</span>\n";
  echo "  </div>\n";
  echo "</div><br><br>\n";
}
?>
