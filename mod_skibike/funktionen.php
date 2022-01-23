<?php
defined('_JEXEC') or die('Restricted access: It is not allowed to open this file directly!');

// report all errors (debug)
ini_set('display_errors', 'On');
error_reporting(E_ALL);


function removeWhiteSpaces($value) {
  $rpld = preg_replace('/\s\s+/', ' ', $value);
  $rpld1 = str_replace("\n", " ", $rpld);
  $rpld2 = str_replace("\r", " ", $rpld1);
  $rpld3 = str_replace("&#10;", " ", $rpld2);
  $rpld4 = str_replace("&#13;", " ", $rpld3);
  $rpld5 = str_replace("&#38;", " ", $rpld4);
  $rpld6 = str_replace(";", " ", $rpld5);
  return $rpld6;
}


function conv_test($input) {
  $tab = array("UTF-8", "ASCII", "Windows-1252", "ISO-8859-15", "ISO-8859-1", "ISO-8859-6", "CP1256");
  $chain = "";
  foreach ($tab as $i)
  {
    foreach ($tab as $j)
    {
      $chain .= " $i$j ".iconv($i, $j, "$input");
    }
  }
  echo $chain;
}


function clean_product_attribute($attr) {
  $item = trim($attr);
  $item = trim($item, "{");
  $item = trim($item, "}");
  $item = trim($item, "\"");
  $item = trim($item);
  $item = trim($item, "\"");
  $item = trim($item);
  $item = str_replace(" }", "", $item);
  $item = trim($item);
  $item = trim($item, "\"");
  return $item;
}


function extract_product_attribute_header($attrs, $db) {

  foreach($attrs as $value) {
    $parts = explode(":", $value);
    $cnt = count($parts);

    for ($i = 0; $i < $cnt; $i++) {
      $parts[$i] = clean_product_attribute($parts[$i]);
    }

    // get header text
    $query = $db->getQuery(true)
                ->select("custom_title")
                ->from("joomla_virtuemart_customs j")
                ->where("virtuemart_custom_id = '$parts[0]'");
    $db->setQuery($query);
    $item = $db->loadObjectList();

    if(empty($item) == FALSE)
    {
      $a = $item[0];
      if(empty($a) == FALSE) {
        $i = $a->custom_title;
        if($i != "Termin:") {
          echo "<th class='orderReport'>$i</th>\n";
        }
      }
    }
  }
}


function extract_product_attributes($attrs, $db, $numOfCols) {
  $cntloop = 0;

  /* attributes array:
  0:
  {
      "10": {
          "31": {
              "comment": "Werner M\u00fcller,Jadwiga"
          }
  }
  1: "25": "158"
  2: "27": "160"
  3: "26": "163"
  4: "43": {
          "302": false
      }
  }*/

  foreach($attrs as $value) {
    $parts = explode(":", $value);
    $cnt = count($parts);

    // clean characters like: {}" and spaces
    for ($i = 0; $i < $cnt; $i++) {
      $parts[$i] = clean_product_attribute($parts[$i]);
    }

    /*
    0: "43"
    1: "302"
    2: false
    }*/
    if($cnt == 3 && is_numeric($parts[2]) == FALSE) {
      if($parts[0] != "43") {
        echo "<td class='orderReport'>$parts[2]</td>\n";
        $cntloop = $cntloop + 1;
      }
    }
    else {
      $idx = 0;
      foreach($parts as $part) {
        if($part != "") {
          /*
          0: "25"
          -> 1: "158"
          and
          0: "27"
          -> 1: "160"
          and
          0: "26"
          -> 1: "163"
          */
          if($idx == 1) {
            // look for customfield value in database
            $query = $db->getQuery(true)
                        ->select("customfield_value")
                        ->from("joomla_virtuemart_product_customfields j")
                        ->where("virtuemart_customfield_id = '$part'");
            $db->setQuery($query);
            $item = $db->loadObjectList();

            if(empty($item) == FALSE) {
              $tmp = $item[0];
              if(empty($tmp) == FALSE) {
                if($tmp->customfield_value != "textinput") {
                  echo "<td class='orderReport'>$tmp->customfield_value</td>\n";
                  $cntloop = $cntloop + 1;
                }
              }
            }
          }
          /*
          0: "10"
          1: "31"
          2: "comment"
          -> 3: "Werner M\u00fcller,Jadwiga"
          }*/
          if($idx == 3) {
            $tmp = replace_uft8_str($part);
            echo "<td class='orderReport'>$tmp</td>\n";
            $cntloop = $cntloop + 1;
          }
          $idx = $idx + 1;
        }
      }
    }

    if( $cntloop >= $numOfCols and $numOfCols != -1) {
      break;
    }
  }
}


function replace_uft8_str($string) {
  return utf8_encode(strtr($string, array(
                '\u00A0'    => ' ',
                '\u0026'    => '&',
                '\u003C'    => '<',
                '\u003E'    => '>',
                '\u00E4'    => 'ä',
                '\u00C4'    => 'Ä',
                '\u00F6'    => 'ö',
                '\u00D6'    => 'Ö',
                '\u00FC'    => 'ü',
                '\u00DC'    => 'Ü',
                '\u00DF'    => 'ß',
                '\u20AC'    => '€',
                '\u0024'    => '$',
                '\u00A3'    => '£',

                '\u00a0'    => ' ',
                '\u003c'    => '<',
                '\u003e'    => '>',
                '\u00e4'    => 'ä',
                '\u00c4'    => 'Ä',
                '\u00f6'    => 'ö',
                '\u00d6'    => 'Ö',
                '\u00fc'    => 'ü',
                '\u00dc'    => 'Ü',
                '\u00df'    => 'ß',
                '\u20ac'    => '€',
                '\u00a3'    => '£',
      )));
}

?>
