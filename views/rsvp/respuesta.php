<?php
/** @var array $resultado */

$estado = (string) $resultado['estado'];
$config = match ($estado) {
    'confirmado'  => [
        'titulo'  => 'Asistencia confirmada',
        'icono'   => 'check',
        'mensaje' => 'Tu lugar está reservado. Preparamos tu mesa y tu menú con el detalle que nos compartiste.',
        'aviso'   => 'b-aviso--ok',
        'nota'    => 'Si necesitas cambiar tu menú o avisar una alergia, vuelve a abrir tu enlace de invitación.',
    ],
    'lista_espera' => [
        'titulo'  => 'Estás en lista de espera',
        'icono'   => 'reloj',
        'mensaje' => 'El aforo del evento está completo por ahora. Registramos tu interés y te avisaremos en cuanto se libere un lugar.',
        'aviso'   => '',
        'nota'    => 'El equipo de coordinación revisa la lista de espera conforme se actualizan las confirmaciones.',
    ],
    default => [
        'titulo'  => 'Respuesta registrada',
        'icono'   => 'corazon',
        'mensaje' => 'Lamentamos que no puedas acompañarnos. Gracias por avisarnos: tu lugar queda liberado para otro invitado.',
        'aviso'   => '',
        'nota'    => 'Si cambias de planes, puedes reabrir tu enlace de invitación y actualizar tu respuesta.',
    ],
};
?>
<div class="b-rsvp">
    <aside class="b-rsvp__visual">
        <img src="https://images.pexels.com/photos/1616113/pexels-photo-1616113.jpeg?auto=compress&cs=tinysrgb&w=1400"
             alt="Detalle de una celebración en el recinto">
        <div class="b-rsvp__leyenda">
            <span>ConectaEventos · Invitación</span>
            <h2>Gracias por<br>responder.</h2>
            <p>Cada confirmación nos ayuda a preparar una celebración más cuidada para todos.</p>
        </div>
    </aside>

    <section class="b-rsvp__panel">
        <div class="b-rsvp__caja">
            <div class="b-resultado">
                <div class="b-resultado__icono"><?= icono($config['icono']) ?></div>
                <p class="b-eyebrow b-eyebrow--centro"><?= e($resultado['evento']) ?></p>
                <h1><?= e($config['titulo']) ?></h1>
                <p><?= e($config['mensaje']) ?></p>

                <div class="b-rsvp__evento" style="text-align:left">
                    <?= icono($config['icono']) ?>
                    <div>
                        <b><?= e($resultado['nombre']) ?></b>
                        <span>Invitado · <?= e($resultado['evento']) ?></span>
                        <span>Aforo del evento: <?= (int) $resultado['aforo'] ?> personas</span>
                    </div>
                </div>

                <div class="b-aviso <?= e($config['aviso']) ?>" style="text-align:left">
                    <?= e($config['nota']) ?>
                </div>

                <a class="b-btn" href="<?= e(url('/boda')) ?>">Volver al sitio</a>
            </div>

            <p class="b-rsvp__pie">
                ¿Algo no coincide? Escribe a <a href="mailto:bodas@conectaeventos.mx">bodas@conectaeventos.mx</a>
                mencionando tu nombre y el evento.
            </p>
        </div>
    </section>
</div>
