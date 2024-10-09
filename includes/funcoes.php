<?php
function InverteData($data) {
    $hora = '';
    $data = substr($data, 0, 10);
    if (strrpos($data, "/") != '') {
        $data_array = explode('/', $data);
        $novadata = $data_array[2] . '-' . $data_array[1] . '-' . $data_array[0];
        return $novadata . $hora;
    } else if (strrpos($data, "-") != '') {
        $data_array = explode('-', $data);
        $novadata = $data_array[2] . '/' . $data_array[1] . '/' . $data_array[0];
        return $novadata . $hora;
    }
}

function SoNumeros($VARIAVEL) {
	$VARIAVEL = preg_replace("/[^0-9]/", "", $VARIAVEL);
	return $VARIAVEL;
}
