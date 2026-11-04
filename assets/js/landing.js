/* ==========================================================================
   Solera - movimiento del sitio publico
   --------------------------------------------------------------------------
   Sin dependencias, a proposito. La politica de seguridad del proyecto
   declara `script-src 'self'`, asi que cualquier libreria desde un CDN
   quedaria bloqueada por el navegador; y vendorizar GSAP por tres efectos
   seria mas peso del que cuestan escritos a mano.

   Dos reglas que se respetan en todo el archivo:

   1. El contenido es visible por defecto. La clase `con-reveal` solo se
      anade al <html> cuando ya sabemos que podemos observar la pagina. Si
      algo falla antes, no se oculta nada y la pagina se lee entera.
   2. Todo lo que depende del scroll se calcula en un solo rAF compartido.
      Un listener por efecto multiplica el trabajo por cuadro y es lo que
      hace que estas paginas se sientan pesadas.
   ========================================================================== */
(() => {
  "use strict";

  const $ = (sel, raiz = document) => raiz.querySelector(sel);
  const $$ = (sel, raiz = document) => [...raiz.querySelectorAll(sel)];
  const limitar = (v, min, max) => Math.min(max, Math.max(min, v));

  const menosMovimiento = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ------------------------------------------------------------------------
     Bucle de scroll compartido
     ------------------------------------------------------------------------
     Cada efecto registra una funcion que recibe el scroll actual. Se ejecutan
     todas dentro del mismo cuadro, y solo cuando hay cuadro pendiente.
     ---------------------------------------------------------------------- */
  const tareas = [];
  let pendiente = false;

  function alDesplazar(fn) {
    tareas.push(fn);
    pedirCuadro();
  }

  function pedirCuadro() {
    if (pendiente) return;
    pendiente = true;
    requestAnimationFrame(() => {
      pendiente = false;
      const y = window.scrollY || window.pageYOffset || 0;
      for (const fn of tareas) {
        try { fn(y); } catch { /* un efecto roto no debe tumbar a los demas */ }
      }
    });
  }

  window.addEventListener("scroll", pedirCuadro, { passive: true });
  window.addEventListener("resize", pedirCuadro, { passive: true });

  /* ------------------------------------------------------------------------
     Navegacion
     ---------------------------------------------------------------------- */
  function iniciarNav() {
    const nav = $(".l-nav");
    if (!nav) return;

    // El umbral es la altura del heroe menos el nav: la barra se vuelve
    // solida justo cuando deja de haber foto detras.
    const hero = $(".l-hero");
    const umbral = () => (hero ? Math.max(120, hero.offsetHeight - nav.offsetHeight - 40) : 80);

    alDesplazar((y) => {
      nav.classList.toggle("l-nav--fija", y > umbral());
    });

    // Menu movil
    const boton = $(".l-nav__hamburguesa");
    const menu = $(".l-menu");
    if (boton && menu) {
      boton.addEventListener("click", () => {
        const abierto = document.body.classList.toggle("l-menu-abierto");
        boton.setAttribute("aria-expanded", String(abierto));
      });
      $$("a", menu).forEach((a) => a.addEventListener("click", () => {
        document.body.classList.remove("l-menu-abierto");
        boton.setAttribute("aria-expanded", "false");
      }));
    }

    // Marca la seccion visible en el menu.
    const enlaces = $$('.l-menu a[href*="#"]');
    const secciones = enlaces
      .map((a) => {
        const id = a.getAttribute("href").split("#")[1];
        return id ? { a, el: document.getElementById(id) } : null;
      })
      .filter((x) => x && x.el);

    if (secciones.length) {
      alDesplazar(() => {
        const corte = window.innerHeight * 0.35;
        let activa = null;
        for (const s of secciones) {
          if (s.el.getBoundingClientRect().top <= corte) activa = s;
        }
        for (const s of secciones) s.a.classList.toggle("activo", s === activa);
      });
    }
  }

  /* ------------------------------------------------------------------------
     Entradas al entrar en pantalla
     ------------------------------------------------------------------------
     Se usa IntersectionObserver, con tres redes de seguridad: si no existe,
     si no dispara, y un plazo maximo tras el cual se muestra todo.
     ---------------------------------------------------------------------- */
  function iniciarReveal() {
    const piezas = $$(".l-reveal, .l-parte");
    if (!piezas.length) return;

    const mostrar = (el) => el.classList.add("visible");

    if (menosMovimiento || !("IntersectionObserver" in window)) {
      piezas.forEach(mostrar);
      return;
    }

    partirTitulares();

    // A partir de aqui si podemos esconder: el observador existe.
    document.documentElement.classList.add("con-reveal");

    const observador = new IntersectionObserver((entradas) => {
      for (const entrada of entradas) {
        if (!entrada.isIntersecting) continue;
        mostrar(entrada.target);
        observador.unobserve(entrada.target);
      }
    }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });

    piezas.forEach((el) => observador.observe(el));

    // Red 1: lo que ya esta en pantalla al cargar o tras un salto de ancla.
    const rescate = () => {
      for (const el of piezas) {
        if (el.classList.contains("visible")) continue;
        const caja = el.getBoundingClientRect();
        if (caja.top < window.innerHeight && caja.bottom > 0) mostrar(el);
      }
    };
    rescate();
    alDesplazar(rescate);

    // Red 2: plazo maximo. Si algo quedo sin revelar, se deja de esconder.
    window.setTimeout(() => {
      if (piezas.some((el) => !el.classList.contains("visible"))) {
        document.documentElement.classList.remove("con-reveal");
      }
    }, 5000);
  }

  /* Parte los titulares marcados en caracteres, conservando el texto para
     lectores de pantalla con aria-label. Las palabras no se parten entre
     lineas porque cada una va en su propio contenedor. */
  function partirTitulares() {
    for (const titular of $$(".l-parte")) {
      if (titular.dataset.partido === "1") continue;
      const texto = titular.textContent.replace(/\s+/g, " ").trim();
      if (!texto || texto.length > 140) continue;

      titular.setAttribute("aria-label", texto);
      titular.textContent = "";
      let i = 0;

      for (const palabra of texto.split(" ")) {
        const caja = document.createElement("span");
        caja.className = "l-palabra";
        caja.setAttribute("aria-hidden", "true");
        caja.style.display = "inline-block";
        caja.style.whiteSpace = "nowrap";
        for (const letra of [...palabra]) {
          const span = document.createElement("span");
          span.className = "l-char";
          span.style.setProperty("--l-ci", String(i++));
          span.textContent = letra;
          caja.appendChild(span);
        }
        titular.appendChild(caja);
        const hueco = document.createElement("span");
        hueco.className = "l-char";
        hueco.setAttribute("aria-hidden", "true");
        hueco.style.setProperty("--l-ci", String(i++));
        hueco.innerHTML = "&nbsp;";
        titular.appendChild(hueco);
      }
      titular.dataset.partido = "1";
    }
  }

  /* ------------------------------------------------------------------------
     Parallax del collage
     ------------------------------------------------------------------------
     Se escribe un desplazamiento en --l-par. El CSS decide que hacer con el,
     y en pantallas angostas lo ignora.
     ---------------------------------------------------------------------- */
  function iniciarParallax() {
    const piezas = $$("[data-par]");
    if (!piezas.length || menosMovimiento) return;

    alDesplazar(() => {
      if (window.innerWidth < 940) {
        for (const el of piezas) el.style.removeProperty("--l-par");
        return;
      }
      const alto = window.innerHeight;
      for (const el of piezas) {
        const caja = el.getBoundingClientRect();
        if (caja.bottom < -200 || caja.top > alto + 200) continue;
        // -1 a 1 segun donde este la pieza respecto al centro de la pantalla.
        const centro = (caja.top + caja.height / 2 - alto / 2) / alto;
        const fuerza = parseFloat(el.dataset.par) || 0;
        el.style.setProperty("--l-par", (centro * fuerza * -100).toFixed(1) + "px");
      }
    });
  }

  /* ------------------------------------------------------------------------
     Fila de tipos de evento
     ------------------------------------------------------------------------
     Convierte el avance dentro de la pista anclada en el desplazamiento
     horizontal de la fila. Se mueve `scrollLeft` y no un transform, para que
     sin JS quede un carrusel que se arrastra en vez de una fila congelada.
     ---------------------------------------------------------------------- */
  function iniciarBaraja() {
    const baraja = $(".l-baraja");
    if (!baraja) return;
    const pista = $(".l-baraja__pista", baraja);
    const cartas = $(".l-baraja__cartas", baraja);
    if (!pista || !cartas) return;

    const barra = $(".l-baraja__barra", baraja);

    // Mientras el usuario arrastra la fila a mano, el scroll de la pagina no
    // debe pelearse con el: se cede el control hasta que suelte.
    let manual = false;
    let temporizador = 0;
    const marcarManual = () => {
      manual = true;
      window.clearTimeout(temporizador);
      temporizador = window.setTimeout(() => { manual = false; }, 900);
    };
    cartas.addEventListener("pointerdown", marcarManual);
    cartas.addEventListener("wheel", (ev) => { if (Math.abs(ev.deltaX) > 0) marcarManual(); }, { passive: true });
    cartas.addEventListener("keydown", marcarManual);

    const aplicar = () => {
      const recorribleX = cartas.scrollWidth - cartas.clientWidth;
      const avanceX = recorribleX > 0 ? cartas.scrollLeft / recorribleX : 0;

      // En angosto la seccion no se ancla: la barra sigue al carrusel y el
      // scroll de la pagina no toca nada.
      if (window.innerWidth < 940 || menosMovimiento) {
        if (barra) barra.style.setProperty("--l-prog", avanceX.toFixed(3));
        return;
      }

      const caja = pista.getBoundingClientRect();
      const recorrido = caja.height - window.innerHeight;
      const avance = recorrido > 0 ? limitar(-caja.top / recorrido, 0, 1) : 1;
      // El viaje termina en el 88% del recorrido, para que la ultima tarjeta
      // se vea quieta un momento antes de que la seccion se despegue.
      const prog = limitar(avance / 0.88, 0, 1);

      if (barra) barra.style.setProperty("--l-prog", prog.toFixed(3));
      if (!manual && recorribleX > 0) {
        cartas.scrollLeft = prog * recorribleX;
      }
    };

    cartas.addEventListener("scroll", () => {
      if (!barra) return;
      const recorribleX = cartas.scrollWidth - cartas.clientWidth;
      if (recorribleX > 0) barra.style.setProperty("--l-prog", (cartas.scrollLeft / recorribleX).toFixed(3));
    }, { passive: true });

    aplicar();
    alDesplazar(aplicar);
  }

  /* ------------------------------------------------------------------------
     Contadores
     ---------------------------------------------------------------------- */
  function iniciarContadores() {
    const numeros = $$("[data-contador]");
    if (!numeros.length) return;

    const pintar = (el, valor) => {
      const sufijo = el.dataset.sufijo || "";
      el.textContent = new Intl.NumberFormat("es-MX").format(Math.round(valor)) + sufijo;
    };

    if (menosMovimiento || !("IntersectionObserver" in window)) {
      numeros.forEach((el) => pintar(el, parseFloat(el.dataset.contador) || 0));
      return;
    }

    const observador = new IntersectionObserver((entradas) => {
      for (const entrada of entradas) {
        if (!entrada.isIntersecting) continue;
        const el = entrada.target;
        observador.unobserve(el);
        const destino = parseFloat(el.dataset.contador) || 0;
        const duracion = 1200;
        const inicio = performance.now();
        const paso = (ahora) => {
          const t = limitar((ahora - inicio) / duracion, 0, 1);
          // Salida suave: rapido al principio, asentandose al final.
          pintar(el, destino * (1 - Math.pow(1 - t, 3)));
          if (t < 1) requestAnimationFrame(paso);
        };
        requestAnimationFrame(paso);
      }
    }, { threshold: 0.4 });

    numeros.forEach((el) => { pintar(el, 0); observador.observe(el); });
  }

  /* ------------------------------------------------------------------------
     Marquesina
     ------------------------------------------------------------------------
     El contenido se duplica aqui y no en la plantilla: el bucle sin costuras
     necesita exactamente el doble, y calcularlo en PHP obligaria a repetir
     el marcado a mano.
     ---------------------------------------------------------------------- */
  function iniciarMarquesina() {
    for (const pista of $$(".l-marquesina__pista")) {
      if (pista.dataset.duplicada === "1") continue;
      const original = [...pista.children];
      if (!original.length) continue;
      for (const hijo of original) {
        const copia = hijo.cloneNode(true);
        copia.setAttribute("aria-hidden", "true");
        pista.appendChild(copia);
      }
      pista.dataset.duplicada = "1";
    }
  }

  /* ------------------------------------------------------------------------
     Detalles de formulario y avisos
     ---------------------------------------------------------------------- */
  /* ------------------------------------------------------------------------
     Disponibilidad en vivo del formulario de contacto
     ------------------------------------------------------------------------
     Antes, el visitante escribia una fecha a ciegas y descubria dias despues
     que el recinto estaba ocupado. Ahora se le responde mientras escribe, con
     la agenda real: la misma que ve el equipo en el panel.

     Si algo falla -sin red, el endpoint caido, JavaScript desactivado- el
     aviso simplemente no aparece y el formulario se envia igual. Nunca
     bloquea el envio: la fecha es una intencion, no una reserva.
     ---------------------------------------------------------------------- */
  function iniciarDisponibilidad() {
    const aviso = $("[data-dispo]");
    const fecha = $("#fecha_estimada");
    if (!aviso || !fecha) return;

    const aforo = $("#aforo");
    const base = document.body.dataset.base || "";
    let ultima = "";
    let temporizador = 0;

    const pintar = (estado, texto) => {
      aviso.textContent = texto;
      aviso.className = "l-dispo l-dispo--" + estado;
      aviso.hidden = false;
    };

    async function consultar() {
      const dia = fecha.value;
      if (!dia) { aviso.hidden = true; ultima = ""; return; }

      const personas = Math.max(0, parseInt(aforo && aforo.value, 10) || 0);
      const clave = dia + ":" + personas;
      if (clave === ultima) return;
      ultima = clave;

      pintar("cargando", "Consultando la agenda…");

      try {
        const r = await fetch(base + "/sitio/disponibilidad?inicio=" + encodeURIComponent(dia) + "&fin=" + encodeURIComponent(dia), {
          headers: { Accept: "application/json" },
        });
        if (!r.ok) throw new Error("respuesta " + r.status);
        const datos = await r.json();
        if (fecha.value !== dia) return;   // escribio otra fecha mientras tanto

        const libres = (datos.espacios || []).filter((e) => e.libre);
        const caben = personas > 0 ? libres.filter((e) => e.capacidad >= personas) : libres;

        if (caben.length > 0) {
          const nombres = caben.map((e) => e.nombre).join(", ");
          // Sin articulo a proposito: «el Galería Central» y «la Salón
          // Aurora» fallan con la mitad de los nombres del recinto.
          pintar("libre", caben.length === 1
            ? "Ese día tenemos libre " + nombres + "."
            : "Ese día tenemos " + caben.length + " espacios libres: " + nombres + ".");
        } else if (libres.length > 0 && personas > 0) {
          const mayor = Math.max(...libres.map((e) => e.capacidad));
          pintar("ajustado", "Ese día queda sitio, pero para " + mayor + " invitados como máximo. Escríbenos y buscamos la forma.");
        } else {
          pintar("ocupado", "Ese día lo tenemos comprometido. Mándanos la solicitud igual y te proponemos fechas cercanas.");
        }
      } catch {
        // Un aviso que no se puede dar no debe dejar un «Consultando…» colgado.
        aviso.hidden = true;
        ultima = "";
      }
    }

    const alEscribir = () => {
      window.clearTimeout(temporizador);
      temporizador = window.setTimeout(consultar, 350);
    };

    fecha.addEventListener("change", consultar);
    fecha.addEventListener("input", alEscribir);
    if (aforo) aforo.addEventListener("input", alEscribir);

    if (fecha.value) consultar();
  }

  /* ------------------------------------------------------------------------
     Reservar tocando el calendario

     Cada dia reservable es un enlace de verdad: `/?mes=...&fecha=...#solicitud`.
     Sin JavaScript el enlace recarga la portada con la fecha ya puesta en el
     formulario, asi que elegir un dia funciona igual. Con JavaScript nos
     ahorramos el viaje al servidor: rellenamos el campo, marcamos el dia y
     bajamos al formulario.
     ---------------------------------------------------------------------- */
  function iniciarCalendario() {
    const dias = $$("a.l-cal__dia[data-dia]");
    if (dias.length === 0) return;

    const campo = $("#fecha_estimada");
    // Sin el campo no hay nada que rellenar: se dejan los enlaces en paz y
    // el servidor hace el trabajo.
    if (!campo) return;

    const destino = $("#solicitud");

    for (const dia of dias) {
      dia.addEventListener("click", (ev) => {
        // Ctrl/Cmd/medio: el visitante quiere otra pestana. No se la quitamos.
        if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button !== 0) return;
        ev.preventDefault();

        campo.value = dia.dataset.dia;
        for (const otro of dias) {
          otro.classList.remove("es-elegido");
          otro.removeAttribute("aria-current");
        }
        dia.classList.add("es-elegido");
        dia.setAttribute("aria-current", "date");

        // Despierta la consulta de disponibilidad, que escucha `change`.
        campo.dispatchEvent(new Event("change", { bubbles: true }));

        if (destino) {
          destino.scrollIntoView({
            behavior: menosMovimiento ? "auto" : "smooth",
            block: "start",
          });
        }

        // El foco va al primer dato que falta, no al que acabamos de poner.
        const aforo = $("#aforo");
        const siguiente = aforo && !aforo.value ? aforo : $("#nombre") || campo;
        window.setTimeout(
          () => siguiente.focus({ preventScroll: true }),
          menosMovimiento ? 0 : 450
        );
      });
    }
  }

  function iniciarFormularios() {
    // Ninguna fecha de evento puede ser anterior a hoy.
    const hoy = new Date().toISOString().slice(0, 10);
    for (const campo of $$('input[type="date"][data-desde-hoy]')) {
      if (!campo.min) campo.min = hoy;
    }

    // Evita el doble envio, que en un formulario publico crea registros
    // duplicados antes de que llegue la respuesta.
    for (const form of $$("form[data-una-vez]")) {
      form.addEventListener("submit", () => {
        const boton = form.querySelector('[type="submit"]');
        if (!boton) return;
        window.setTimeout(() => {
          boton.disabled = true;
          boton.dataset.textoPrevio = boton.textContent;
          boton.textContent = "Enviando...";
        }, 0);
      });
    }
  }

  function iniciarAvisos() {
    for (const boton of $$(".l-flash .flash-cerrar")) {
      boton.addEventListener("click", () => boton.closest(".flash")?.remove());
    }
    window.setTimeout(() => {
      for (const aviso of $$(".l-flash .flash--ok, .l-flash .flash--info")) {
        aviso.style.transition = "opacity .4s, transform .4s";
        aviso.style.opacity = "0";
        aviso.style.transform = "translateY(-8px)";
        window.setTimeout(() => aviso.remove(), 400);
      }
    }, 6000);
  }

  /* ------------------------------------------------------------------------
     Arranque
     ---------------------------------------------------------------------- */
  const arrancar = () => {
    iniciarNav();
    iniciarMarquesina();
    iniciarParallax();
    iniciarBaraja();
    iniciarContadores();
    iniciarFormularios();
    iniciarDisponibilidad();
    iniciarCalendario();
    iniciarAvisos();
    iniciarReveal();
    pedirCuadro();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", arrancar);
  } else {
    arrancar();
  }
})();
