<?php

session_name();
session_start();

$dbhost="localhost";
$dbusuario="root";
$dbpassword="";
$db="seminario";
$conexion = mysqli_connect($dbhost,$dbusuario,$dbpassword,$db);	
	
if(!$conexion){
			?>
			<script >	
				alert('no se pudo conectar al sistema');
				location.href ='index.php';
	   		</script>
	   		<?php
}else{
			?>
			<script >	
				alert('Se conecto correctamente al sistema');
	   		</script>
	   		<?php
}

$sql = 'SELECT id AS CountryID, name AS CountryName FROM countries ORDER BY name';

?>

<html>
    <body>
        <form action="process.php" method="post">
            <select name="country" id="country">
                <option value="-1"></option>
                <?php
                foreach ($countryNames as $k => $countryName) {
                    ?>
                    <option value="<?php echo $countryIds[$k]; ?>"><?php echo $countryName; ?></option>
                    <?php
                }
                ?>
            </select>
            <select name="city" id="city"></select>
        </form>
    </body>
<script type="application/javascript">
    const cities = Array();
    <?php
    foreach($countryIds as $countryId) {
        $cities = array_values(array_filter($data, function($row) use ($countryId) {
            return $row['CountryID'] === $countryId;
        } ));
        ?>
    cities[<?php echo $countryId;?>] = [ <?php
        for ($i = 0; $i < count($cities) - 1; $i++ ) {
            ?>{ id: <?php echo $cities[$i]['CityID']; ?>, name: "<?php echo $cities[$i]['CityName']; ?>" }, <?php
        }
        ?>{ id: <?php echo $cities[$i]['CityID']; ?>, name: "<?php echo $cities[$i]['CityName']; ?>" } ];
    <?php
    }
    ?>

    document.getElementById('country').addEventListener('change', function(e) {
        let ownCities = cities[e.target.value];

        let cityDropdown = document.getElementById('city');
        cityDropdown.innerText = null;

        ownCities.forEach( function(c) {
            var option = document.createElement('option');
            option.text = c.name;
            option.value = c.id;
            cityDropdown.appendChild(option);
        } )
    });
</script>
</html>