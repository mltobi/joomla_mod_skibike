<?php
defined('_JEXEC') or die('Restricted access: It is not allowed to open this file directly!');

// report all errors (debug)
ini_set('display_errors', 'On');
error_reporting(E_ALL);

header("Content-Type: text/html;charset=UTF-8");

include 'funktionen.php';
include 'css_styles.php';


// open database
$db = JFactory::getDBO();

echo "<h3>Alle Fahrten</h3>\n";

// get date minus 5 months
$fivemonth = date("Y-m-d", strtotime("-5 months"));

// start table
echo "<table class='orderReport'>\n";

// create table header
echo "  <tr class='orderReport'>\n";
echo "    <th class='orderReport'>Fahrt</th>\n";
echo "    <th class='orderReport'>Gesamt</th>\n";
echo "    <th class='orderReport'>Gebucht</th>\n";
echo "    <th class='orderReport'>Verf&uuml;gbar</th>\n";
echo "    <th class='orderReport'>Anteil</th>\n";
echo "    <th class='orderReport'>Preis</th>\n";
echo "  </tr>\n";

// get product attribute of event
$query = $db->getQuery(true)
            ->select("pdd.product_name as event, p.product_in_stock as free, sum(oi.product_quantity) as booked, sum(oi.product_subtotal_with_tax) as price")
            ->from("joomla_virtuemart_order_items oi, joomla_virtuemart_products_de_de pdd, joomla_virtuemart_products p, joomla_virtuemart_orders o")
            ->where("oi.virtuemart_product_id = pdd.virtuemart_product_id "
                  . "AND p.virtuemart_product_id = pdd.virtuemart_product_id "
                  . "AND oi.virtuemart_order_id = o.virtuemart_order_id "
                  . "AND o.created_on > '$fivemonth 09:00:00' "
                  . "AND (oi.order_status = 'C' OR oi.order_status = 'F') "
                  . "AND (o.order_status = 'C' OR o.order_status = 'F') "
                  . "AND p.published = '1'")
            ->group("pdd.product_name");
$db->setQuery($query);

$sum = 0;
foreach($db->loadObjectList() as $row)
{
  // create rows
  echo "  <tr class='orderReport'>\n";
  echo "    <td class='orderReport'><a href=\"index.php/usermenue/abfragen/fahrtenbuchungen?eventPrm=$row->event\">$row->event</a></td>\n";
  $total = $row->booked+$row->free;
  echo "    <td class='orderReport'>$total</td>\n";
  echo "    <td class='orderReport'>$row->booked</td>\n";
  echo "    <td class='orderReport'>$row->free</td>\n";
  $part = 100 * $row->booked / $total;
  $part = number_format($part, 1, '.', '');
  echo "    <td class='orderReport'>$part%</td>\n";
  $price = number_format($row->price, 0, '.', '');
  $sum = $sum + $price;
  echo "    <td class='orderReport'>$price &euro;</td>\n";
  echo "  </tr>\n";
}
echo "  <tr class='orderReport'>\n";
echo "    <td class='orderReport'></td>\n";
echo "    <td class='orderReport'></td>\n";
echo "    <td class='orderReport'></td>\n";
echo "    <td class='orderReport'></td>\n";
echo "    <td class='orderReport'><b>Summe:</b></td>\n";
echo "    <td class='orderReport'><b>$sum &euro;</b></td>\n";
echo "  </tr>\n";

echo "</table>\n";


echo "<h3>Verf&uuml;gbarkeit &auml;ndern</h3>\n";

$eventEntry = isset($_POST['selectEvent'])?$_POST['selectEvent']:'';

echo "<form action='#' method='post' name='eventForm'>\n";
echo "  <select name='selectEvent' onchange='eventForm.submit()'>\n";
echo "    <option value=\"\">Bitte ausw&auml;hlen</option>\n";

$query = $db->getQuery(true)
            ->select("product_name, product_in_stock, product_ordered")
            ->from("joomla_virtuemart_products_de_de pdd, joomla_virtuemart_products p")
            ->where("p.published = '1' "
                  . "AND pdd.virtuemart_product_id = p.virtuemart_product_id");
$db->setQuery($query);

foreach($db->loadObjectList() as $row)
{
  $event = $row->product_name;
  if( $event == $eventEntry ) {
    echo "    <option selected=\"selected\" value=\"$event\">$event</option>\n";
  }
  else {
    echo "    <option value=\"$event\">$event</option>\n";
  }
}
echo "  </select>\n";

$event = str_replace("'", "\\'", $eventEntry); // escape "'"
$bestand = isset($_POST['inputBestand'])?$_POST['inputBestand']:'';

if( $bestand == '' ) {
  $query = $db->getQuery(true)
              ->select("product_in_stock")
              ->from("joomla_virtuemart_products_de_de pdd, joomla_virtuemart_products p")
              ->where("p.published = '1' "
                    . "AND pdd.virtuemart_product_id = p.virtuemart_product_id "
                    . "AND pdd.product_name = '$event'");
  $db->setQuery($query);
  foreach($db->loadObjectList() as $row)
  {
    $bestand = $row->product_in_stock;
  }

  echo "<br />Verf&uuml;gbarer Bestand: \n";
  echo "  <input style='width:50px;'; type='number' name='inputBestand' min='0' max='500' value='$bestand'>\n";
  echo "  <input type='submit' name='submitBtn' value='Ausw&auml;hlen'></input>\n";
}
else
{
  if( is_numeric($bestand) ) {
    $query = $db->getQuery(true)
                ->update("joomla_virtuemart_products as p, joomla_virtuemart_products_de_de as pdd")
                ->set("p.product_in_stock=$bestand")
                ->where("p.published = '1' "
                      . "AND pdd.virtuemart_product_id = p.virtuemart_product_id "
                      . "AND pdd.product_name = '$event'");
    $db->setQuery($query);
    $db->execute();

    header("refresh: 0;");
  }
}
echo "</form>\n";
echo "<br />\n";

?>
