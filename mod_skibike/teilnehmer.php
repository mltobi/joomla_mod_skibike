<?php
// TODO:
// * add more comment

defined('_JEXEC') or die('Restricted access: It is not allowed to open this file directly!');

header("Content-Type: text/html;charset=UTF-8");

include 'funktionen.php';
include 'css_styles.php';


// open database
$db = JFactory::getDBO();
$db1 = JFactory::getDBO();


// get GET and POST variables
if( isset($_GET["eventPrm"]) ) {
  $eventEntry = $_GET["eventPrm"];
}
else {
  $eventEntry = isset($_POST['selectEvent'])?$_POST['selectEvent']:'';
}

// get date minus 5 months
$fivemonth = date("Y-m-d", strtotime("-5 months"));

// provide a select field for all products
echo "<form action='#' method='post' name='eventForm'>\n";
echo "  <select name='selectEvent' onchange='eventForm.submit()'>\n";
echo "    <option value=\"\">Bitte ausw&auml;hlen</option>\n";

$query = $db->getQuery(true)
            ->select("product_name, product_in_stock, product_ordered")
            ->from("joomla_virtuemart_products_de_de pdd, joomla_virtuemart_products p")
            ->where("p.published = '1' AND pdd.virtuemart_product_id = p.virtuemart_product_id")
            ->order("product_sku");
$db->setQuery($query);

// add option for each product to select field
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
echo "  <noscript><input type='submit' name='submitBtn' value='Ausw&auml;hlen'></noscript>\n";
echo "</form>\n";
echo "<br />\n";

// check if a product is selected
$event = str_replace("'", "\\'", $eventEntry); // escape "'"
if( $event != "" ) {

  echo "<p>Gew&auml;hlte Fahrt: <b>$event</b> <br />\n";

  // get number of avaiable and booked
  $query = $db->getQuery(true)
              ->select("product_in_stock, product_ordered")
              ->from("joomla_virtuemart_products_de_de pdd, joomla_virtuemart_products p")
              ->where("p.published = '1' "
                    . "AND pdd.virtuemart_product_id = p.virtuemart_product_id "
                    . "AND pdd.product_name = '$event'");
  $db->setQuery($query);

  foreach($db->loadObjectList() as $row)
  {
    $product_in_stock = $row->product_in_stock;
    echo "Verf&uuml;gbar: <b>$row->product_in_stock</b> <br />\n";
  }

  $query = $db->getQuery(true)
              ->select("SUM(product_quantity) as booked")
              ->from("joomla_virtuemart_order_items oi, joomla_virtuemart_orders o")
              ->where("oi.order_item_name = '$event' "
                    . "AND (oi.order_status = 'C' OR oi.order_status = 'F') "
                    . "AND (o.order_status = 'C' OR o.order_status = 'F') "
                    . "AND oi.virtuemart_order_id = o.virtuemart_order_id "
                    . "AND o.created_on > '$fivemonth 09:00:00'");
  $db->setQuery($query);

  foreach($db->loadObjectList() as $row)
  {
    $booked = $row->booked;
    echo "Gebucht: <b>$booked</b><br />\n";
  }
  $total = $product_in_stock + $booked;
  echo "Gesamt: <b>$total</b> <br />\n";
  echo "</p>\n";

  // start table
  echo "<table id='table' class='orderReport'>\n";

  // get product attribute of event
  $query = $db->getQuery(true)
              ->select("o.virtuemart_order_id, product_quantity, product_attribute, oi.order_status, product_final_price")
              ->from("joomla_virtuemart_order_items oi, joomla_virtuemart_orders o")
              ->where("oi.order_item_name = '$event' "
                    . "AND oi.virtuemart_order_id = o.virtuemart_order_id "
                    . "AND o.created_on > '$fivemonth 09:00:00'");
  $db->setQuery($query);
  $once = 0;

  // loop through all rows
  $emails = "";
  foreach($db->loadObjectList() as $row)
  {
    /* Replace }, and ", by }@ and "@ in order to keep "," within text string
    {
        "10": {
            "31": {
                "comment": "Werner M\u00fcller,Jadwiga"
            }
    }, -> }@
        "25": "158", -> "@
        "27": "160", -> "@
        "26": "163", -> "@
        "43": {
            "302": false
        }
    }*/
    $col = $row->product_attribute;
    $col = str_replace('},', '}@', $col);
    $col = str_replace('",', '"@', $col);
    $col = str_replace('],', ']@', $col);
    $attrs = explode("@", $col);

    // create rows
    $query = $db->getQuery(true)
                ->select("order_number, order_pass, order_status, created_on")
                ->from("joomla_virtuemart_orders j")
                ->where("virtuemart_order_id = '$row->virtuemart_order_id'");
    $db->setQuery($query);
    $oinfo = $db->loadObjectList();
    $oinfo = $oinfo[0];

    // create table header
    if($once == 0) {
      echo "  <tr class='orderReport'>\n";
      echo "    <th class='orderReport'>Bestellnr.</th>\n";
      echo "    <th class='orderReport'>Anzahl</th>\n";

      extract_product_attribute_header($attrs, $db);

      echo "<th class='orderReport'>Hinweis</th>\n";
      echo "<th class='orderReport'>Preis</th>\n";
      echo "<th class='orderReport'>Besteller</th>\n";
      echo "<th class='orderReport'>Telefon</th>\n";
      echo "<th class='orderReport'>E-Mail</th>\n";
      echo "<th class='orderReport'>Datum</th>\n";
      echo "<th class='orderReport'>Status</th>\n";
      echo "  </tr>\n";
    }
    $once = 1;

    if( ($row->order_status == "C" or $row->order_status == "F") and $oinfo->order_status != "D" ) {

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
      if ($row->order_status == "F") {
        $statusstyle = "style = \"background-color: lightgreen;\"";
      }
      else {
        $statusstyle = "";
      }
      echo "<tr>\n";
      echo "<td $statusstyle name='orderNumber' class='orderReport'><a href='$link'>$oinfo->order_number</a></td>\n";
      echo "<td class='orderReport'>$row->product_quantity</td>\n";

      extract_product_attributes($attrs, $db, -1);

      $query = $db->getQuery(true)
                  ->select("first_name, last_name, phone_1, email, customer_note")
                  ->from("joomla_virtuemart_order_userinfos j")
                  ->where("virtuemart_order_id = '$row->virtuemart_order_id'");
      $db->setQuery($query);
      $uinfo = $db->loadObjectList();
      $uinfo = $uinfo[0];
      echo "<td class='orderReport'>$uinfo->customer_note</td>\n";
      $total_price = floatval($row->product_quantity) * floatval($row->product_final_price);
      echo "<td class='orderReport'>$total_price</td>\n";
      echo "<td class='orderReport'>$uinfo->first_name $uinfo->last_name</td>\n";
      echo "<td class='orderReport'>$uinfo->phone_1</td>\n";
      if (strpos($emails, $uinfo->email) === false) {
        $emails = "$uinfo->email; $emails";
      }
      echo "<td class='orderReport'><a href='mailto:$uinfo->email'>$uinfo->email</a></td>\n";
      echo "<td class='orderReport'>$oinfo->created_on</td>\n";
      echo "<td class='orderReport'>$row->order_status / $oinfo->order_status</td>\n";

      echo "</tr>\n";
    }
  }
  echo "</table>\n";

  echo "<p>\n";
  echo "Mit diesem <b><a href='mailto:$emails'>Link</a></b> kannst Du eine E-Mail an alle E-Mail Adressen der ausgew&auml;hlten Fahrt senden.<br />\n";
  echo "<a href='mailto:$emails'><img style='width:50px;height:50px;' src='../../../modules/mod_skibike/sendmail.png'></a><br>\n";
  echo "</p>\n";

  echo "<p style='color:darkgrey;'>\n";
  echo "<b>Hinweis:</b><br />";
  echo "Zum Tabelle kopieren, diese mit der Maus markieren und mit Strg-c kopieren. <br />";
  echo "Anschlie&szlig;end kann diese dann mit Strg-v z.B. in Excel eingef&uuml;gt werden.\n";
  echo "</p>\n";
}


$orderNumber = isset($_POST['inputOrderNumber'])?$_POST['inputOrderNumber']:'';
$storno = isset($_POST['inputStorno'])?$_POST['inputStorno']:'';

echo "<form action='#' method='post' name='orderNumberForm'>\n";
echo "<h3>Bestellung Stornieren</h3>Bestellnummer: \n";
echo "<p style='color:darkgrey;'>\n";
echo "<b>Hinweis:</b><br />";
echo "Die zu stornierende Bestellnummer kann durch einfaches Klicken auf die Bestellnummer-Zelle der angezeigten Tabelle ins Eingabefeld kopiert werden.\n";
echo "Bitte nicht direkt auf die Bestellnummer selbst klicken, da dann die verlinkte Bestellung geöffnet wird und das Kopieren in das Eingabefeld nicht erfolgt!\n";
echo "</p>\n";
echo "  <input style='width:200px;' type='text' id='inpbox' name='inputOrderNumber' value='$orderNumber'>\n";
echo "  <input type='submit' name='submitBtn' value='Ausw&auml;hlen'>\n";
echo "</form>\n";

$ostatus = "C";
if( $orderNumber != '' ) {
  $query = $db->getQuery(true)
              ->select("oi.order_item_name, o.order_status")
              ->from("joomla_virtuemart_order_items as oi, joomla_virtuemart_orders as o")
              ->where("o.virtuemart_order_id = oi.virtuemart_order_id "
                    . "AND o.order_number = '$orderNumber'");
  $db->setQuery($query);

  echo "<ul>\n";
  foreach($db->loadObjectList() as $row)
  {
    $ostatus = $row->order_status;
    echo "<li>$row->order_item_name</li>\n";
  }
  echo "</ul>\n";

  echo "<form action='#' method='post' name='stornoForm'>\n";
  echo "  <input style='width:200px;' type='text' name='inputOrderNumber' value='$orderNumber'><br>\n";
  echo "  <input style='width:40px;' type='text' name='inputStorno' value='$storno' title='JA oder NEIN eingeben'> Eingeben: JA -> Stornieren oder NEIN -> nicht Stornieren<br>\n";
  echo "  <input type='submit' name='submitBtn' value='Stornieren'>\n";
  echo "</form>\n";
}

if( $storno == "JA" ) {

  // set complete order to denied
  $query = $db->getQuery(true)
              ->update("joomla_virtuemart_orders")
              ->set("order_status = 'D'")
              ->where("order_number = '$orderNumber'");
  $db->setQuery($query); $db->execute();
  $db->execute();

  // get order id of order to deny
  $query = $db->getQuery(true)
              ->select("virtuemart_order_id")
              ->from("joomla_virtuemart_orders o")
              ->where("order_number = '$orderNumber'");
  $db->setQuery($query);
  $orderid = $db->loadObjectList();
  $orderid = $orderid[0];

  // set all products of order to deny
  $query = $db->getQuery(true)
              ->update("joomla_virtuemart_order_items")
              ->set("order_status = 'D'")
              ->where("virtuemart_order_id = '$orderid->virtuemart_order_id'");
  $db->setQuery($query);
  $db->execute();

  // get all product SKUs
  $query = $db->getQuery(true)
              ->select("order_item_sku")
              ->from("joomla_virtuemart_order_items o")
              ->where("virtuemart_order_id = '$orderid->virtuemart_order_id'");
  $db->setQuery($query);

  // increase all stocks by one for each found product SKU
  $items = $db->loadObjectList();
  foreach($items as $sku)
  {
    // get current number in stock
    $query1 = $db1->getQuery(true)
                  ->select("product_in_stock")
                  ->from("joomla_virtuemart_products p")
                  ->where("product_sku = '$sku->order_item_sku'");
    $db1->setQuery($query1);
    $stockArr = $db->loadObjectList();
    $stock = $stockArr[0];

    // increase stock by one
    $stockNbr = $stock->product_in_stock + 1;

    // update stock in database
    $query1 = $db1->getQuery(true)
                ->update("joomla_virtuemart_products")
                ->set("product_in_stock = '$stockNbr'")
                ->where("product_sku = '$sku->order_item_sku'");
    $db1->setQuery($query1);
    $db1->execute();
  }

  echo "Storniert!";
}
else if ($storno == "NEIN" ) {

  echo "Nicht storniert!";

}

echo "<script type=\"text/javascript\">\n";
echo "var inpbox = document.getElementById('inpbox');\n";
echo "var table = document.getElementById('table');\n";
echo "// add one event handler to the table\n";
echo "table.onclick = function (e) {\n";
echo "  // normalize event\n";

echo "  e = e || window.event;\n"; 
echo "  // find out which element was clicked\n";
echo "  var el =  e.target || e.srcElement;\n";
echo "  // check if it's a table cell\n";
echo "  if (el.nodeName.toUpperCase() == \"TD\" && el.getAttribute('name') == 'orderNumber') {\n";
echo "    // append it's content to the inpbox\n";
echo "    inpbox.value = (el.textContent || el.innerText);\n";
echo "  }\n";
echo "}\n";
echo "</script>\n";

?>
