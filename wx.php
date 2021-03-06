<style type="text/css">

    .rawmetar {
        font-family: "Consolas", monospace;
        font-size: 36px;
    }

    .content {
        font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
        font-size: 36px;
    }

</style

<p>
    <p class = "content"><a href="fltsim_resource.html">&lt; Return to menu</a></p>
</p>

<p>&nbsp;</p>

<form method="post" action="">
    <input type="text" name="airport" style="text-transform: uppercase; font-size: 24">
    <input type="submit" value="Get METAR" style="font-size: 24">
</form>
<p>&nbsp;</p>

<!-- TODO: offload all the logic to metar.php -->

<?php

    include('metar.php');

    // suppress errors on inital load
    if (!isset($_POST['airport']))
        exit;

    $airport = $_POST['airport'];
    $metar_string = file_get_contents('http://metar.vatsim.net/metar.php?id=' . $airport);

    $airport_info_raw = file_get_contents('http://www.airport-data.com/api/ap_info.json?icao=' . $airport);
    $airport_info = json_decode($airport_info_raw, true);

    $airport_name = '';
    if ($airport_info['status'] === '200')
        $airport_name = $airport_info['name'];

    $pressure_setting = '';
    preg_match("/[AQ]\d\d\d\d/", $metar_string, $pressure_setting);
    $pressure_setting = implode($pressure_setting);

    // alternate data source, with no restrictions (NOAA): http://weather.noaa.gov/pub/data/observations/metar/stations/[ICAO].TXT

    // invalid airport code
    if ($metar_string === 'No METAR available for ' .  $airport or $metar_string === 'Please issue an ICAO code.') {
        echo 'invalid airport';
        exit;
    }

    // get issue time
    $issue_time = get_issue_time($metar_string);

    $iterate_on = str_split($metar_string);

    // get winds
    // P.S. I'm terribly sorry for this incredibly dirty way of doing things.... :(
    $wind_data = '';
    $curr_pos = 13;
    $curr = 13;
    while (!ctype_space($iterate_on[$curr]) ) {
        $wind_data[$curr_pos] = $iterate_on[$curr];
        if ($curr_pos == 16) {
            $wind_data[$curr_pos] = '&deg;';
            $curr--;
        }
        else if ($curr_pos == 17) {
            $wind_data[$curr_pos] = "/";
            $curr--;
        }
        $curr_pos++;
        $curr++;
    }

//    $wind_data = substr($wind_data,0,2) . '°/' . substr($wind_data,3,strlen($wind_data)-1);
    $winds = implode($wind_data);

    // determine if METAR is a North American one

    $airport = substr($metar_string, 0, 4);

    // display raw METAR
    echo '<hr/><p class = rawmetar>' . $metar_string . '</font></p><hr/>';

    // inches of mercury, or QNH?
    $press_type;
    if ($pressure_setting[0] === 'A')
        $press_type = 'inHg';
    else if($pressure_setting[0] === 'Q')
        $press_type = 'hPa';
    $pressure_setting = substr($pressure_setting, 1);

    if ($press_type === 'inHg')
        $pressure_setting = substr_replace($pressure_setting, '.', 2, 0);

    $units;
    if (is_metric($airport))
        $units = 'Metric';
    else $units = 'Imperial';

//    $press = preg_grep('~[AQ]\d{4}~', str_split($metar_string));
//    var_dump($press);
?>

<p>&nbsp;</p>

<table style="width:100%" class = content>
    <tr>
        <td><?php echo 'METAR for ' . $airport_info['name'] . ':'; ?></td>
    </tr>
    <tr>
        <td><p></p></td>
    </tr>
    <tr>
        <td><b>Issued</b></td>
        <td><b>Pressure setting</b></td>
        <td><b>Winds</b></td>
    </tr>
    <tr>
        <td><?php echo $issue_time; ?></td>
        <td><?php echo $pressure_setting . ' ' . $press_type; ?></td>
        <td><?php echo $winds ?></td>
    </tr>
</table>

<p class = content><b>Note:</b> <?php echo $units?> units in use.</p>