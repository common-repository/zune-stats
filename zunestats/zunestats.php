<?php
/*
Plugin Name: ZuneStats
Plugin URI: http://www.zmastaa.com/zunestats
Description: Displays your Zune Stats on your sidebar, i.e. Recently Played Tracks, Most Played Artists, and Favourite Tracks
Version: 2.1.2
Author: Eugene zMastaa Agyeman
Author URI: http://www.zmastaa.com
*/
/*  Copyright 2008  Eugene Agyeman  (email : eugene@zmastaa.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
*/

function ZuneStats()
{
function xml2array($contents, $get_attributes=1) { 
	//$contents = file_get_contents('http://socialapi.zune.net/members/zMastaa%20US');
	if(!$contents) return array(); 

    if(!function_exists('xml_parser_create')) { 
        //print "'xml_parser_create()' function not found!"; 
        return array(); 
    } 
    //Get the XML parser of PHP - PHP must have this module for the parser to work 
    $parser = xml_parser_create(); 
    xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 ); 
    xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 ); 
    xml_parse_into_struct( $parser, $contents, $xml_values ); 
    xml_parser_free( $parser ); 

    if(!$xml_values) return;//Hmm... 

    //Initializations 
    $xml_array = array(); 
    $parents = array(); 
    $opened_tags = array(); 
    $arr = array(); 

    $current = &$xml_array; 

    //Go through the tags. 
    foreach($xml_values as $data) { 
        unset($attributes,$value);//Remove existing values, or there will be trouble 

        //This command will extract these variables into the foreach scope 
        // tag(string), type(string), level(int), attributes(array). 
        extract($data);//We could use the array by itself, but this cooler. 

        $result = ''; 
        if($get_attributes) {//The second argument of the function decides this. 
            $result = array(); 
            if(isset($value)) $result['value'] = $value; 

            //Set the attributes too. 
            if(isset($attributes)) { 
                foreach($attributes as $attr => $val) { 
                    if($get_attributes == 1) $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr' 
                    /**  :TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */ 
                } 
            } 
        } elseif(isset($value)) { 
            $result = $value; 
        } 

        //See tag status and do the needed. 
        if($type == "open") {//The starting of the tag '<tag>' 
            $parent[$level-1] = &$current; 

            if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag 
                $current[$tag] = $result; 
                $current = &$current[$tag]; 

            } else { //There was another element with the same tag name 
                if(isset($current[$tag][0])) { 
                    array_push($current[$tag], $result); 
                } else { 
                    $current[$tag] = array($current[$tag],$result); 
                } 
                $last = count($current[$tag]) - 1; 
                $current = &$current[$tag][$last]; 
            } 

        } elseif($type == "complete") { //Tags that ends in 1 line '<tag />' 
            //See if the key is already taken. 
            if(!isset($current[$tag])) { //New Key 
                $current[$tag] = $result; 

            } else { //If taken, put all things inside a list(array) 
                if((is_array($current[$tag]) and $get_attributes == 0)//If it is already an array... 
                        or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) { 
                    array_push($current[$tag],$result); // ...push the new element into that array. 
                } else { //If it is not an array... 
                    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value 
                } 
            } 

        } elseif($type == 'close') { //End of tag '</tag>' 
            $current = &$parent[$level-1]; 
        } 
    } 

    return($xml_array); 
    
} 
//$contents = file_get_contents('http://socialapi.zune.net/members/zMastaa%20US');//Or however you what it
//$result = xml2array($contents,0);
//print_r($result);
//print_r ($array['href']);
  $zune_options = get_option("widget_zunestats");
  //$apilink = 'http://socialapi.zune.net/members/' . $zune_options['tag'];
  $apiurl = urlencode($zune_options['tag']);
//$contents = file_get_contents('http://socialapi.zune.net/members/' . $apiurl);
$site_url = 'http://socialapi.zune.net/members/' . $apiurl;
$ch = curl_init();
$timeout = 5; // set to zero for no timeout
curl_setopt ($ch, CURLOPT_URL, $site_url);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

ob_start();
curl_exec($ch);
curl_close($ch);
$file_contents = ob_get_contents();
ob_end_clean();
$contents = $file_contents;


$result = xml2array($contents, 1);
for($i=0;$i<=0;++$i){
	echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">
	<link rel=\"stylesheet\" href=\"/wp-content/plugins/zunestats/zs.css\" />
	<div><div id=\"textright\">Zunetag: <a href=\"". $result['a:entry']['a:author']['a:uri']['value'] . "\">". $result['a:entry']['a:title']['value'].
"</a><br>Playcount: ". $result['a:entry']['playcount']['value'].
"<br></div><div id=\"zuneimg\"><a href=\"". $result['a:entry']['a:author']['a:uri']['value'] . "\"><img src=\"http://origin-tiles.zune.net/profile/usertile.ashx?type=user&tag=".$apiurl."\"> 
</a><br></div>
" ;
if ($zune_options['rt'] == '1') {
//$contents = file_get_contents($result['a:entry']['playlists']['link']['0']['attr']['href']);
$site_url = $result['a:entry']['playlists']['link']['0']['attr']['href'];
$ch = curl_init();
$timeout = 5; // set to zero for no timeout
curl_setopt ($ch, CURLOPT_URL, $site_url);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

ob_start();
curl_exec($ch);
curl_close($ch);
$file_contents = ob_get_contents();
ob_end_clean();
$contents = $file_contents;

$result2 = xml2array($contents, 1);
//Last 5 Songs
for($i=0;$i<=0;++$i){
	echo "<div><p><h2>Recently Played</h2> <b><hr>".
	 $result2['a:feed']['a:entry']['0']['c:track']['c:title']['value']. 
"</b> by <b>" . $result2['a:feed']['a:entry']['0']['c:track']['c:primaryArtist']['c:name']['value']."</b><br><hr><b>".
	
	$result2['a:feed']['a:entry']['1']['c:track']['c:title']['value']. 
"</b> by <b>" . $result2['a:feed']['a:entry']['1']['c:track']['c:primaryArtist']['c:name']['value']."</b><br><hr><b>".

	$result2['a:feed']['a:entry']['2']['c:track']['c:title']['value']. 
"</b> by <b>" . $result2['a:feed']['a:entry']['2']['c:track']['c:primaryArtist']['c:name']['value']."</b><br><hr><b>".

		$result2['a:feed']['a:entry']['3']['c:track']['c:title']['value']. 
"</b> by <b>" . $result2['a:feed']['a:entry']['3']['c:track']['c:primaryArtist']['c:name']['value']."</b><br><hr><b>".

		$result2['a:feed']['a:entry']['4']['c:track']['c:title']['value']. 
"</b> by <b>" . $result2['a:feed']['a:entry']['4']['c:track']['c:primaryArtist']['c:name']['value']."</b><br>	
	</p></div>" ;

}
}
if ($zune_options['mpa'] == '1') {
//top artists
//$contents = file_get_contents($result['a:entry']['playlists']['link']['1']['attr']['href']);
$site_url = $result['a:entry']['playlists']['link']['1']['attr']['href'];
$ch = curl_init();
$timeout = 5; // set to zero for no timeout
curl_setopt ($ch, CURLOPT_URL, $site_url);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

ob_start();
curl_exec($ch);
curl_close($ch);
$file_contents = ob_get_contents();
ob_end_clean();
$contents = $file_contents;
$result3 = xml2array($contents, 1);

for($i=0;$i<=0;++$i){
	echo "<div><p><h2>Most Played Artists</h2><b><hr>".
	 $result3['a:feed']['artists']['artist']['0']['name']['value']. 
	"<br><hr>".
	
	 $result3['a:feed']['artists']['artist']['1']['name']['value']. 
	"<br><hr>".
	
	 $result3['a:feed']['artists']['artist']['2']['name']['value']. 
	"<br><hr>".
	
	$result3['a:feed']['artists']['artist']['3']['name']['value']. 
	"<br><hr>".
	
	 $result3['a:feed']['artists']['artist']['4']['name']['value']. 
	"</b>	
	</p></div>
	" ;

}
}
if ($zune_options['ft'] == '1') {
//$contents = file_get_contents($result['a:entry']['playlists']['link']['2']['attr']['href']);
$site_url = $result['a:entry']['playlists']['link']['2']['attr']['href'];
$ch = curl_init();
$timeout = 5; // set to zero for no timeout
curl_setopt ($ch, CURLOPT_URL, $site_url);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

ob_start();
curl_exec($ch);
curl_close($ch);
$file_contents = ob_get_contents();
ob_end_clean();
$contents = $file_contents;
$result4 = xml2array($contents, 1);
//Last 5 Favourites
for($i=0;$i<=0;++$i){
	echo "<div><p><h2>Recent Favourites</h2><b><hr>".
	 $result4['a:feed']['a:entry']['0']['c:track']['c:title']['value']. 
	"</b> by <b>" . $result4['a:feed']['a:entry']['0']['c:track']['c:primaryArtist']['c:name']['value']."</b><br><hr><b>".
	
	$result4['a:feed']['a:entry']['1']['c:track']['c:title']['value']. 
"</b> by <b>" . $result4['a:feed']['a:entry']['1']['c:track']['c:primaryArtist']['c:name']['value']."</b><br><hr><b>".

	$result4['a:feed']['a:entry']['2']['c:track']['c:title']['value']. 
"</b> by <b>" . $result4['a:feed']['a:entry']['2']['c:track']['c:primaryArtist']['c:name']['value']."</b><br><hr><b>".

		$result4['a:feed']['a:entry']['3']['c:track']['c:title']['value']. 
"</b> by <b>" . $result4['a:feed']['a:entry']['3']['c:track']['c:primaryArtist']['c:name']['value']."</b><br><hr><b>".

		$result4['a:feed']['a:entry']['4']['c:track']['c:title']['value']. 
"</b> by <b>" . $result4['a:feed']['a:entry']['4']['c:track']['c:primaryArtist']['c:name']['value']."</b><br>	
	<br></p></div>" ;

}
}
}

}

function widget_zunestats($args) {
	  extract($args);

  $zune_options = get_option("widget_zunestats");
  if (!is_array( $zune_options ))
	{
		$zune_options = array(
      'title' => 'My Zune Stats', 'tag' => 'zMastaa US',
      'rt' => '1', 'mpa' => '0', 'ft' => '0'
      );
  }      
  extract($args);
  echo $before_widget;
   echo $before_title;
      echo $zune_options['title'];
    echo $after_title;
  ZuneStats();
  echo $after_widget;
}

function zunestats_control()
{
  $zune_options = get_option("widget_zunestats");

  if (!is_array( $zune_options ))
	{
		$zune_options = array(
      'title' => 'My Zune Stats', 'tag' => 'zMastaa US',
            'rt' => '1', 'mpa' => '0', 'ft' => '0'
      );
  }    
  if ($_POST['zunestats-Submit'])
  {
    $zune_options['title'] = htmlspecialchars($_POST['zunestats-WidgetTitle']);
    $zune_options['tag'] = htmlspecialchars($_POST['zunestats-zunetag']);
    $zune_options['rt'] = htmlspecialchars($_POST['zunestats-rt']);
    $zune_options['mpa'] = htmlspecialchars($_POST['zunestats-mpa']);
    $zune_options['ft'] = htmlspecialchars($_POST['zunestats-ft']);    
    update_option("widget_zunestats", $zune_options);
  }

?>
  <p>
    <label for="zunestats-WidgetTitle">Widget Title: </label>
    <input type="text" id="zunestats-WidgetTitle" name="zunestats-WidgetTitle" value="<?php echo $zune_options['title'];?>" /><br>
    
    <label for="zunestats-zunetag">Zune Tag: </label>
    <input type="text" id="zunestats-zunetag" name="zunestats-zunetag" value="<?php echo $zune_options['tag'];?>" /><hr>
Recent Plays:
<input type="checkbox" name="zunestats-rt" value="1" <?php if ($zune_options['rt'] == '1') echo "checked"; ?> >
<br>
Most Played Artists:
<input type="checkbox" name="zunestats-mpa" value="1" <?php if ($zune_options['mpa'] == '1') echo "checked"; ?>>
<br>
Favourite Tracks: 
<input type="checkbox" name="zunestats-ft" value="1" <?php if ($zune_options['ft'] == '1') echo "checked"; ?>>
    
    <input type="hidden" id="zunestats-Submit" name="zunestats-Submit" value="1" />
  </p>
<?php
}



function myZuneStats_init()
{
  register_sidebar_widget(__('ZuneStats'), 'widget_zunestats');
    register_widget_control(   'ZuneStats', 'zunestats_control', 300, 200 );
}
add_action("plugins_loaded", "myZuneStats_init");

?>
