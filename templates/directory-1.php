<?php 
$output .= '[su_note note_color="#ffffff"]';
$output .= '<h4 style="text-align:center;background-color:#236cad;color:white;padding:5px;">'.$count.' '.$row['Nombre'].'</h4>';
$output .= '[su_row][su_column size="1/2" center="no" class=""]<figure class="wp-block-image size-full" style="max-height:350px;overflow:hidden"><img src="'.$row['Imagen Url'].'"/></figure>[/su_column]';
$output .= '[su_column size="1/2" center="no" class=""]';
$output .= '[su_service title="Valoraciones" icon="icon: star" icon_color="#e5501b"]'.str_replace(';',',',$row['Rating']).' de 5 estrellas[/su_service]';
$output .= '[su_service title="Dirección" icon="icon: map-marker" icon_color="#e5501b"]'.str_replace(';',',',$row['Direccion']).'[/su_service]';
$output .= '[su_service title="Teléfono" icon="icon: phone" icon_color="#e5501b"]'.$row['Teléfono'].'[/su_service]';
$output .= '[su_service title="Horarios" icon="icon: table" icon_color="#e5501b"][su_list icon="icon: clock-o"][su_spoiler title="Ver horarios" style="fancy"]';
$output .= '<ul><li>Lunes:'.$row['lunes'].'</li>
<li>Martes:'.$row['martes'].'</li>
<li>Miercoles:'.$row['miércoles'].'</li>
<li>Jueves:'.$row['jueves'].'</li>
<li>Viernes:'.$row['viernes'].'</li>
<li>Sabado:'.$row['sábado'].'</li>
<li>Domingo:'.$row['domingo'].'</li>
</ul>[/su_spoiler][/su_list][/su_service]
            [/su_column][/su_row]';
$output.= '[su_row]'.str_replace(array('width="600"','height="450"'),array('width="100%"','height="300"'),$row['Iframe']).'[/su_row][/su_note]';

