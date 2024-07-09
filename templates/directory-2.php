<?php 
$output .= '[su_note note_color="#ffffff"]';
$output .= '<h4 style="text-align:center;background-color:#236cad;color:white;padding:5px;">'.$row['Nombre'].'</h4>';
$output .= '[su_row][su_column size="1/3" center="no" class=""]'.str_replace(array('width="600"','height="450"'),array('width="100%"','height="250"'),$row['Iframe']).'[/su_column]';
$output .= '[su_column size="2/3" center="no" class=""]';
$output .= '[su_service title="Web" icon="icon: globe" icon_color="#e5501b"]'.$row['Website'].'[/su_service]';
$output .= '[su_service title="Dirección" icon="icon: map-marker" icon_color="#e5501b"]'.str_replace(';',',',$row['Direccion']).'[/su_service]';
$output .= '[su_service title="Teléfono" icon="icon: phone" icon_color="#e5501b"]'.$row['Teléfono'].'[/su_service]';
$output .= '[/su_column][/su_row]';

           


