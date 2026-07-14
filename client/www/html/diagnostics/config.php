<html>
<head>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">
  
<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $('#data').DataTable({
      "lengthMenu": [[50,100,200,-1],[50, 100, 200, "All"]]
    });
} );
</script>
</head>
<body>
<?php
require_once("/var/www/lib/diagnostics/HFRNetwork.php");
?>

<?php
$rtvproc = new RTVProc();
$rows = $rtvproc->getAllSiteConfig();
print "<table id='data' class='display'><thead><tr><th>Network</th><th>Station</th><th title='This indicates the minute of the radial data timestamp selected during total processing.  Most systems producing radials with a center-time at the top of the hour have a corresponding value of zero.'>Radial Minute Used</th><th>Beam Pattern</th><th>Region</th><th>Resolution</th></tr></thead><tbody>";


foreach( $rows as $row ){
  printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>",$row['network'],$row['name'],$row['use_radial_minute'],$row['beampattern'],$row['description'],$row['res']);
}
print "</tbody></table>"
?>
</body>
</html>
