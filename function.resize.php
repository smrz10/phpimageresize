<?php

require 'ImagePath.php';
require 'Configuration.php';
require 'Resizer.php';

function resize($imagePath,$opts=null){	
	try {
	    $configuration = new Configuration($opts);
	} catch (Exception $e) {
	    return 'cannot resize the image';
	}

	$path = new ImagePath($imagePath);	
	$resizer = new Resizer($path, $configuration);

    //privatizar las funciones que solo se usan desde la clase, crear una llamada __test**** para no rehacer los test existentes	
    //puedo calcular ObtainFilePath y ComposeNewPath en la construccion de ImagePath? necesitaria configuration
        //o calcularlos en el constructor de resize y guardarlos como variables privadas

	try {
		$cacheFilePath = $resizer->doResize();
	} catch (Exception $e) {
		//return 'cannot resize the image';
		return $e->getMessage();
	}

	return $cacheFilePath;	
}
