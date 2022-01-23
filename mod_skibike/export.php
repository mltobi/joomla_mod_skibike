<?php
defined('_JEXEC') or die('Restricted access: It is not allowed to open this file directly!');

header("Content-Type: text/html;charset=UTF-8");

include 'funktionen.php';


// get post variables
$export = isset($_POST['exportCsv'])?$_POST['exportCsv']:'';
$nbrOfMonths = isset($_POST['nbrOfMonths'])?$_POST['nbrOfMonths']:'6';


// open database
$db = JFactory::getDBO();


// get custom field titles
$query = $db->getQuery(true)
            ->select("virtuemart_custom_id, custom_title")
            ->from("joomla_virtuemart_customs j");
$db->setQuery($query);

foreach( $db->loadObjectList() as $item) {
  $customs[$item->virtuemart_custom_id] = $item;
}


// get date minus 5 months
$fivemonth = date("Y-m-d", strtotime("-$nbrOfMonths months"));

// get all orders with user infos and items
$query = $db->getQuery(true)
            ->select("*")
            ->from("joomla_virtuemart_order_userinfos oui, joomla_virtuemart_order_items oi, joomla_virtuemart_orders o")
            ->where("oui.virtuemart_order_id = o.virtuemart_order_id "
                  . "AND oi.virtuemart_order_id = o.virtuemart_order_id "
                  . "AND o.created_on > '$fivemonth 09:00:00'");
$db->setQuery($query);

$rows = $db->loadObjectList();


// define order of main columns (other columns are added in the order of the returned SQL request)
$cols = array('order_number', 'order_item_name', 'last_name', 'first_name', 'product_quantity', 'product_item_price', 'order_total', 'order_status', 'phone_1', 'email', 'product_attribute', 'address_1', 'city', 'zip', 'created_on', 'modified_on', 'customer_note');


// get date minus 5 months
if ($export == '') {
  echo "<form action='#' method='post' name='eventForm'>\n";
  echo "  Die <input type='number' name='nbrOfMonths' style='width:50px;' value='$nbrOfMonths'/> letzten Monate werden exportiert.<br><br>\n";
  echo "  <input type='submit' name='submitBtn' value='CSV Export'><br>\n";
  echo "  Auswahl der zu exportierenden Attribute (Mehrfachauswahl):<br>";
  echo "  <select name='exportCsv[]' multiple size='15'>\n";
  // table head main columns
  $out = "";
  foreach ($cols as $col) {
    echo "    <option selected value='$col'>$col</option>\n";
  }

  // table head remaining columns
  foreach ($rows[0] as $key => $value) {
    if (in_array($key, $cols) == FALSE) {
      echo "    <option selected value='$key'>$key</option>\n";
    }
  }
  echo "  </select><br>\n";
  echo "</form>\n";
  echo "<br />\n";
}
else {
  $cols = $export;

  // table head main columns
  $out = "";
  foreach ($cols as $col) {
    $out = $out . "$col;";
  }

  $out = $out . "\n";

  // table rows
  foreach ($rows as $row)
  {
    // add main cells of current row
    foreach ($cols as $col) {
      $value = removeWhiteSpaces($row->{$col});
      // add "" to phone number that it is handled as text instead of value in the CSV
      if ($col == 'phone_1') {
        $out = $out . "'$value';";
      }
      // extent product attributes
      else if ($col == 'product_attribute') {
        $obj = json_decode($value);
        foreach ($obj as $key => $value) {
          if (is_object($value)) {
            foreach ($value as $subkey => $subvalue) {

              if (is_object($subvalue)) {
                foreach ($subvalue as $subsubkey => $subsubvalue) {
                  $vbl = $customs[$key]->custom_title;
                  $out = $out . "$vbl $subsubvalue, ";
                }
              }
              else {
                $vbl = $customs[$key]->custom_title;
                $out = $out . "$vbl $subvalue, ";
              }
            }
          }
        }
        // add ";" after last product attribute iteam
        $out = $out . ";";
      }
      // just add value for all other keys
      else {
        $out = $out . "$value;";
      }
    }

    $out = $out . "\n";
  }

  // save page as CSV file
  $now = new DateTime;
  $time = $now->format('Ymd_His');
  $filename = "order_export_$time.csv";

  header("Content-Type: text/plain");
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  header("Content-Length: " . strlen($out));
  echo $out;
  exit;
}
?>
