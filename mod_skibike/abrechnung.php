<?php
// TODO:
// * add more comment

defined('_JEXEC') or die('Restricted access: It is not allowed to open this file directly!');

header("Content-Type: text/html;charset=UTF-8");

include 'funktionen.php';
include 'css_styles.php';

// open database
$db = JFactory::getDBO();

// get post varibales
$months = isset($_POST['inputMonths'])?$_POST['inputMonths']:'5';

// get date minus 5 months
$fivemonth = date("Y-m-d", strtotime("-$months months"));

echo "<form action='#' method='post' name='monthsForm'>\n";
echo "Buchungen der letzten \n";
echo "  <input style='width:50px;'; type='number' name='inputMonths' min='5' max='200' value='$months' onchange='monthsForm.submit()'>\n";
echo "  <noscript><input type='submit' name='submitBtn' value='Ausw&auml;hlen'></input></noscript>\n";
echo " Monate:\n";
echo "</form>\n";
echo "<br />\n";

if( is_numeric($months) ) {
  // start table
  echo "<table class='orderReport'>\n";

  // get product attribute of event
  $query = $db->getQuery(true)
            ->select("o.virtuemart_order_id, o.order_number as Bestellnummer, oui.last_name as Nachname, oui.first_name as Vorname, "
                   . "pdd.product_name as Fahrt, oi.product_item_price as Einzelpreis, oi.product_quantity as Anzahl, "
                   . "(oi.product_item_price * oi.product_quantity) as Preis, o.order_total as Gesamt")
            ->from("joomla_virtuemart_orders o, joomla_virtuemart_order_items oi, joomla_virtuemart_products_de_de pdd, "
                 . "joomla_virtuemart_products p, joomla_virtuemart_order_userinfos oui")
            ->where("o.virtuemart_order_id = oi.virtuemart_order_id "
                  . "AND oui.virtuemart_order_id = oi.virtuemart_order_id "
                  . "AND pdd.virtuemart_product_id = oi.virtuemart_product_id "
                  . "AND pdd.virtuemart_product_id = p.virtuemart_product_id "
                  . "AND o.created_on > '$fivemonth' "
                  . "AND (oi.order_status = 'C' OR oi.order_status = 'F') "
                  . "AND (o.order_status = 'C' OR o.order_status = 'F') "
                  . "AND p.published = '1'");
  $db->setQuery($query);

  $once = 0;

  // loop through all rows
  $emails = "";
  foreach ($db->loadObjectList() as $row) 
  {
    // create table header
    if($once == 0) {
      echo "  <tr class='orderReport'>\n";
      echo "    <th class='orderReport'>Bestellnummer</th>\n";
      echo "    <th class='orderReport'>Nachname</th>\n";
      echo "    <th class='orderReport'>Vorname</th>\n";
      echo "    <th class='orderReport'>Fahrt</th>\n";
      echo "    <th class='orderReport'>Einzelpreis</th>\n";
      echo "    <th class='orderReport'>Anzahl</th>\n";
      echo "    <th class='orderReport'>Preis</th>\n";
      echo "    <th class='orderReport'>Gesamt</th>\n";
      echo "  </tr>\n";
    }
    $once = 1;

    // create rows
    echo "  <tr class='orderReport'>\n";
    $link = "index.php?"
          . "option=com_virtuemart&amp;"
          . "view=invoice&amp;"
          . "layout=invoice&amp;"
          . "format=pdf&amp;"
          . "tmpl=component&amp;"
          . "virtuemart_order_id=$row->virtuemart_order_id&amp;"
          . "d=1";
    echo "<td class='orderReport'><a href='$link'>$row->Bestellnummer</a></td>\n";
    echo "<td class='orderReport'>$row->Nachname</td>\n";
    echo "<td class='orderReport'>$row->Vorname</td>\n";
    echo "<td class='orderReport'><a href=\"index.php/usermenue/abfragen/fahrtenbuchungen?eventPrm=$row->Fahrt\">$row->Fahrt</a></td>\n";
    echo "<td class='orderReport'>$row->Einzelpreis</td>\n";
    echo "<td class='orderReport'>$row->Anzahl</td>\n";
    echo "<td class='orderReport'>$row->Preis</td>\n";
    echo "<td class='orderReport'>$row->Gesamt</td>\n";

    echo "  </tr>\n";
  }
  echo "</table>\n";
}
?>
