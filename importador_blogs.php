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
$sql = "SELECT b.id, b.fecha, b.titulo, b.contenido ".
	"FROM mod_blog b ".
	"WHERE b.ocultar = 0 ".
	"ORDER BY b.fecha DESC "/*.
	"LIMIT 0, 3"*/; 
echo $sql.PHP_EOL;
$counter_imports = 0;
if ($result = $mysqli->query($sql)) {
  while ($row = $result->fetch_assoc()) {

	//Posts en general
	$args = array( 
		'post_type' => 'post', 
		'post_title' => mb_convert_encoding($row['titulo'], 'UTF-8', 'auto'), 
		//'post_content' => mb_convert_encoding($row['contenido'], 'UTF-8', 'auto'),
		'post_date' => $row['fecha'],
		'post_status' => 'publish'
	);
	$post_id = wp_insert_post( $args, true );
	
	
	//Categorías
	$cats = array();
	$sql_cat = "SELECT categoria FROM `mod_blog_categorias` WHERE `elemento` = ".$row['id']." AND categoria > 0"; 
  	$result_cat = $mysqli->query($sql_cat);
  	while ($row_cat = $result_cat->fetch_assoc()) {
  		if ($row_cat['categoria'] == 5) $cats[] = 2;
  		if ($row_cat['categoria'] == 6) $cats[] = 3;
  	}
  	if (count($cats) > 0) wp_set_post_categories($post_id, $cats);
  	
  	//Imagenes principales
  	//http://www.linaresnevadopsicologos.com/v0/files/blog/_119/comunicacion-infantil.png
  	$sql_img = "SELECT name FROM `mod_blog_images` WHERE `indice` = ".$row['id']." AND var = 0"; 
  	echo $sql_img.PHP_EOL;
  	$result_img = $mysqli->query($sql_img);
  	while ($row_img = $result_img->fetch_assoc()) {
  		$url = "http://www.midominio.com/v0/files/blog/_".$row['id']."/".$row_img['name'];
  		echo $url.PHP_EOL;
  		$attach_id = upload_file($url, $post_id);
  		set_post_thumbnail( $post_id, $attach_id );
  	}
  	
  	
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
	echo "IMPORTADO   -> ".$row['titulo']." (".$row['fecha'].")".PHP_EOL;
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
