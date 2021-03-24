<?php

header("Content-Type: text/plain; charset=utf-8");

ini_set("display_errors", "1");

require_once(dirname(__FILE__).'/wp-load.php');
global $wpdb;	

echo "START importación ---------------------------". PHP_EOL;

//Conexión DB
$mysqli = mysqli_connect("localhost", "xxxxxxxxxxxx", "xxxxxxxxxxxxxxxx", "xxxxxxxxxxxxxxxx");
if (!$mysqli) {
    echo "Error: No se pudo conectar a MySQL.".PHP_EOL;
    echo "errno de depuración: ". mysqli_connect_errno().PHP_EOL;
    exit;
}
$mysqli->set_charset("utf8");
echo "Conexión DB OK\n";

//Sacamos el listado de proyectos
$sql = "SELECT b.id, b.titulo, b.contenido ".
	"FROM mod_tratamientos b ".
	"WHERE b.ocultar = 0 ".
	"ORDER BY b.id ASC "/*.
	"LIMIT 0, 3"*/; 
echo $sql.PHP_EOL;
$counter_imports = 0;
if ($result = $mysqli->query($sql)) {
  while ($row = $result->fetch_assoc()) {

	//Posts en general
	$args = array( 
		'post_type' => 'tratamiento', 
		'post_title' => mb_convert_encoding($row['titulo'], 'UTF-8', 'auto'), 
		//'post_content' => mb_convert_encoding($row['contenido'], 'UTF-8', 'auto'),
		//'post_date' => $row['fecha'],
		'post_status' => 'publish'
	);
	$post_id = wp_insert_post( $args, true );
	
	
	//Categorías
	$cats = array();
	$sql_cat = "SELECT categoria FROM `mod_tratamientos_categorias` WHERE `elemento` = ".$row['id']." AND categoria > 0"; 
	echo $sql_cat.PHP_EOL;
  	$result_cat = $mysqli->query($sql_cat);
  	while ($row_cat = $result_cat->fetch_assoc()) {
  		if ($row_cat['categoria'] == 1) $cats[] = 19;
  		if ($row_cat['categoria'] == 2) $cats[] = 18;
  		if ($row_cat['categoria'] == 3) $cats[] = 17;
  		if ($row_cat['categoria'] == 4) $cats[] = 16;
  		if ($row_cat['categoria'] == 5) $cats[] = 15;
  		if ($row_cat['categoria'] == 6) $cats[] = 14;
  		if ($row_cat['categoria'] == 7) $cats[] = 13;
 		if ($row_cat['categoria'] == 8) $cats[] = 12;
  		if ($row_cat['categoria'] == 9) $cats[] = 11;
  		if ($row_cat['categoria'] == 11) $cats[] = 10;
  		if ($row_cat['categoria'] == 12) $cats[] = 9;
  		if ($row_cat['categoria'] == 13) $cats[] = 8;
  		if ($row_cat['categoria'] == 14) $cats[] = 7;
  		if ($row_cat['categoria'] == 15) $cats[] = 6;
  		if ($row_cat['categoria'] == 16) $cats[] = 5;
  		if ($row_cat['categoria'] == 17) $cats[] = 4;
  	}
  	if (count($cats) > 0) wp_set_post_terms($post_id, $cats, 'tipo');
  	
  	//Imagenes principales
  	$sql_img = "SELECT name FROM `mod_tratamientos_images` WHERE `indice` = ".$row['id']." AND var = 0"; 
  	echo $sql_img.PHP_EOL;
  	echo mb_convert_encoding($row['titulo'], 'UTF-8', 'auto').PHP_EOL;
  	$result_img = $mysqli->query($sql_img);
  	while ($row_img = $result_img->fetch_assoc()) {
  		$url = "http://www.midominio.com/v0/files/tratamientos/_".$row['id']."/".$row_img['name'];
  		echo $url.PHP_EOL;
  		$attach_id = upload_file($url, $post_id);
  		set_post_thumbnail( $post_id, $attach_id );
  	}
  	
  	//Imagenes dentro del contenido
	$dom = new domDocument; 
	$dom->loadHTML($row['contenido']); 
	$dom->preserveWhiteSpace = false;
	$images = $dom->getElementsByTagName('img');

	foreach ($images as $image)  {   
	     $image_url = "http://www.midominio.com".$image->getAttribute('src').PHP_EOL;
	     $attach_id = upload_file($image_url, $post_id);
	     
	     $row['contenido'] = str_replace($image->getAttribute('src'), wp_get_attachment_url( $attach_id), $row['contenido']);
	}
	
	$args = array( 
		'ID' => $post_id, 
		'post_content' => mb_convert_encoding($row['contenido'], 'UTF-8', 'auto')
	);
	
	wp_update_post($args);

	//Info
	echo "IMPORTADO   -> ".$row['titulo']." (".$row['id'].")".PHP_EOL;
	echo "--------------------------------------------------".PHP_EOL;
	$counter_imports++;

  }
  $result->free_result();
}

echo "Proyectos importados: ".$counter_imports. PHP_EOL;



mysqli_close($mysqli);
echo "END importación ---------------------------". PHP_EOL;



function upload_file($image_url, $post_id) {
    $image = $image_url;
    $get = wp_remote_get($image);
    $type = wp_remote_retrieve_header($get, 'content-type');
    if (!$type) {
        return false;
    }
    $mirror = wp_upload_bits(basename($image), '', wp_remote_retrieve_body($get));
    $attachment = array(
        'post_title' => basename($image),
        'post_mime_type' => $type
    );
    $attach_id = wp_insert_attachment($attachment, $mirror['file'], $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $mirror['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);
    return $attach_id;
}
?>
