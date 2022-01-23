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
$days = isset($_POST['inputDays'])?$_POST['inputDays']:'1';

echo "<form action='#' method='post' name='daysForm'>\n";
echo "Buchungen der letzten \n";
echo "  <input style='width:50px;'; type='number' name='inputDays' min='1' max='200' value='$days' onchange='daysForm.submit()'>\n";
echo "  <noscript><input type='submit' name='submitBtn' value='Ausw&auml;hlen'></input></noscript>\n";
echo " Tage:\n";
echo "</form>\n";
echo "<br />\n";

if( is_numeric($days) ) {
  // start table
  echo "<table class='orderReport'>\n";

  // get product attribute of event
  $query = $db->getQuery(true)
              ->select("o.created_on, pdd.product_name, oi.product_attribute, o.virtuemart_order_id, oi.product_quantity, oi.product_subtotal_with_tax")
              ->from("joomla_virtuemart_order_items oi, joomla_virtuemart_orders o, joomla_virtuemart_products p, joomla_virtuemart_products_de_de pdd")
              ->where("DATE(o.created_on) > (NOW() - INTERVAL $days DAY) "
                    . "AND oi.virtuemart_order_id = o.virtuemart_order_id "
                    . "AND oi.virtuemart_product_id = p.virtuemart_product_id "
                    . "AND pdd.virtuemart_product_id = p.virtuemart_product_id ")
              ->order("o.created_on DESC");
  $db->setQuery($query);

  $once = 0;

  // loop through all rows
  $emails = "";
  foreach ($db->loadObjectList() as $row) 
  {
    $col = $row->product_attribute;
    $attrs = explode(",", $col);

    // create table header
    if($once == 0) {
      echo "  <tr class='orderReport'>\n";
      echo "    <th class='orderReport'>Datum</th>\n";
      echo "    <th class='orderReport'>Fahrt</th>\n";
      echo "    <th class='orderReport'>Bestellnr.</th>\n";
      echo "    <th class='orderReport'>Anzahl</th>\n";
      echo "    <th class='orderReport'>Name</th>\n";
      echo "    <th class='orderReport'>Kategorie</th>\n";
      echo "    <th class='orderReport'>Preis</th>\n";
      echo "    <th class='orderReport'>Besteller</th>\n";
      echo "    <th class='orderReport'>Telefon</th>\n";
      echo "    <th class='orderReport'>E-Mail</th>\n";
      echo "  </tr>\n";
    }
    $once = 1;
    // create rows
    echo "  <tr class='orderReport'>\n";
    $query = $db->getQuery(true)
               ->select("order_number, order_pass, order_status, created_on")
               ->from("joomla_virtuemart_orders j")
               ->where("virtuemart_order_id = '$row->virtuemart_order_id'");
    $db->setQuery($query);
    $oinfo = $db->loadObjectList();
    $oinfo = $oinfo[0];

    if( $oinfo->order_status == "C" ) {
      echo "<td class='orderReport'>$oinfo->created_on</td>\n";
      echo "<td class='orderReport'><a href=\"index.php/usermenue/abfragen/fahrtenbuchungen?eventPrm=$row->product_name\">$row->product_name</a></td>\n";

      $link = "index.php?"
            . "option=com_virtuemart&amp;"
            . "view=invoice&amp;"
            . "layout=invoice&amp;"
            . "format=pdf&amp;"
            . "tmpl=component&amp;"
            . "virtuemart_order_id=$row->virtuemart_order_id&amp;"
            . "order_number=$oinfo->order_number&amp;"
            . "$oinfo->order_pass=$oinfo->order_pass;"
            . "d=1";
      echo "<td class='orderReport'><a href='$link'>$oinfo->order_number</a></td>\n";
      echo "<td class='orderReport'>$row->product_quantity</td>\n";

      extract_product_attributes($attrs, $db, 2);

      $price = number_format($row->product_subtotal_with_tax, 2, '.', '');
      echo "<td class='orderReport'>$price &euro;</td>\n";

      $query = $db->getQuery(true)
                  ->select("first_name, last_name, phone_1, email, customer_note")
                  ->from("joomla_virtuemart_order_userinfos j")
                  ->where("virtuemart_order_id = '$row->virtuemart_order_id'");
      $db->setQuery($query);
      $uinfo = $db->loadObjectList();
      $uinfo = $uinfo[0];

      echo "<td class='orderReport'>$uinfo->first_name $uinfo->last_name</td>\n";
      echo "<td class='orderReport'>$uinfo->phone_1</td>\n";
      if (strpos($emails, $uinfo->email) === false) {
        $emails = "$uinfo->email; $emails";
      }
      echo "<td class='orderReport'><a href='mailto:$uinfo->email'>$uinfo->email</a></td>\n";

      echo "  </tr>\n";
    }
  }
  echo "</table>\n";

  echo "<p>\n";
  echo "Mit diesem <b><a href='mailto:$emails'>Link</a></b> kannst Du eine E-Mail an alle E-Mail Adressen der angezeigten Tabelle senden.<br />\n";
  echo "<a href='mailto:$emails'><img style='width:50px;height:50px;' src='../../../modules/mod_skibike/sendmail.png'></a><br>\n";
  echo "</p>\n";
}
?>
