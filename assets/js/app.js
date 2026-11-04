/* ==========================================================================
   Solera · interacciones del panel
   ========================================================================== */
(function () {
  "use strict";

  const $ = (sel, ctx) => (ctx || document).querySelector(sel);
  const $$ = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));

  /* ---------------------------------------------------------------
   * Menú lateral móvil
   * --------------------------------------------------------------- */
  function iniciarSidebar() {
    const toggle = $("[data-sidebar-toggle]");
    if (!toggle) return;

    toggle.addEventListener("click", () => {
      document.body.classList.toggle("sidebar-abierta");
      toggle.setAttribute("aria-expanded", document.body.classList.contains("sidebar-abierta") ? "true" : "false");
    });

    document.addEventListener("click", (ev) => {
      if (!document.body.classList.contains("sidebar-abierta")) return;
      if (ev.target.closest(".sidebar") || ev.target.closest("[data-sidebar-toggle]")) return;
      document.body.classList.remove("sidebar-abierta");
    });

    document.addEventListener("keydown", (ev) => {
      if (ev.key === "Escape") document.body.classList.remove("sidebar-abierta");
    });
  }

  /* ---------------------------------------------------------------
   * Mensajes flash
   * --------------------------------------------------------------- */
  function iniciarFlash() {
    $$(".flash-cerrar").forEach((btn) => {
      btn.addEventListener("click", () => {
        const flash = btn.closest(".flash");
        if (!flash) return;
        flash.style.transition = "opacity .2s ease, transform .2s ease";
        flash.style.opacity = "0";
        flash.style.transform = "translateY(-4px)";
        setTimeout(() => flash.remove(), 220);
      });
    });
  }

  /* ---------------------------------------------------------------
   * Confirmaciones
   * --------------------------------------------------------------- */
  function iniciarConfirmaciones() {
    document.addEventListener("submit", (ev) => {
      const form = ev.target;
      if (!(form instanceof HTMLFormElement)) return;
      const mensaje = form.dataset.confirmar;
      if (mensaje && !window.confirm(mensaje)) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
      }
    });

    document.addEventListener("click", (ev) => {
      const enlace = ev.target.closest("a[data-confirmar]");
      if (enlace && !window.confirm(enlace.dataset.confirmar)) {
        ev.preventDefault();
      }
    });
  }

  /* ---------------------------------------------------------------
   * Filtros automáticos
   * --------------------------------------------------------------- */
  function iniciarFiltros() {
    $$("[data-autoenviar]").forEach((campo) => {
      campo.addEventListener("change", () => {
        const form = campo.closest("form");
        if (form) form.submit();
      });
    });
  }

  /* ---------------------------------------------------------------
   * Copiar al portapapeles
   * --------------------------------------------------------------- */
  function iniciarCopiar() {
    $$("[data-copiar]").forEach((btn) => {
      btn.addEventListener("click", async () => {
        const valor = btn.dataset.copiar || "";
        try {
          await navigator.clipboard.writeText(valor);
          const original = btn.dataset.textoOriginal || btn.textContent;
          btn.dataset.textoOriginal = original;
          btn.textContent = "Copiado";
          setTimeout(() => { btn.textContent = original; }, 1600);
        } catch (e) {
          window.prompt("Copia el enlace:", valor);
        }
      });
    });
  }

  /* ---------------------------------------------------------------
   * Imprimir
   * --------------------------------------------------------------- */
  function iniciarImpresion() {
    $$("[data-imprimir]").forEach((btn) => {
      btn.addEventListener("click", () => window.print());
    });
  }

  /* ---------------------------------------------------------------
   * Cotizador
   * --------------------------------------------------------------- */
  function iniciarCotizador() {
    const contenedor = $("[data-cotizador]");
    if (!contenedor) return;

    const cuerpo = $("[data-lineas]", contenedor);
    const plantilla = $("#plantilla-linea");
    const ivaPct = parseFloat(contenedor.dataset.iva || "16") || 0;
    const anticipoPct = (parseFloat(contenedor.dataset.anticipo || "30") || 0) / 100;
    const descuento = $("[data-descuento]", contenedor);

    function fmt(n) {
      return "$" + (Math.round(n * 100) / 100).toLocaleString("es-MX", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    function recalcular() {
      let subtotal = 0;
      $$(".linea-cotizacion", cuerpo).forEach((fila) => {
        const cant = parseFloat($('[data-campo="cantidad"]', fila)?.value || "0") || 0;
        const precio = parseFloat($('[data-campo="precio"]', fila)?.value || "0") || 0;
        const importe = cant * precio;
        const celda = $("[data-importe]", fila);
        if (celda) celda.textContent = fmt(importe);
        subtotal += importe;
      });

      const desc = Math.min(parseFloat(descuento?.value || "0") || 0, subtotal);
      const base = Math.max(0, subtotal - desc);
      const impuestos = base * (ivaPct / 100);
      const total = base + impuestos;
      const anticipo = total * anticipoPct;

      const set = (sel, valor) => {
        $$(sel, contenedor).forEach((el) => { el.textContent = fmt(valor); });
      };
      set("[data-subtotal]", subtotal);
      set("[data-descuento-vista]", desc);
      set("[data-impuestos]", impuestos);
      set("[data-total]", total);
      set("[data-anticipo]", anticipo);
    }

    function conectarFila(fila) {
      $$("input, select", fila).forEach((campo) => {
        campo.addEventListener("input", recalcular);
        campo.addEventListener("change", recalcular);
      });

      const selector = $("[data-concepto]", fila);
      if (selector) {
        selector.addEventListener("change", () => {
          const opcion = selector.options[selector.selectedIndex];
          if (!opcion || !opcion.value) return;
          const descripcion = $('[data-campo="descripcion"]', fila);
          const precio = $('[data-campo="precio"]', fila);
          const tipo = $('[data-campo="tipo"]', fila);
          const referencia = $('[data-campo="referencia"]', fila);
          if (descripcion && opcion.dataset.descripcion) descripcion.value = opcion.dataset.descripcion;
          if (precio && opcion.dataset.precio) precio.value = opcion.dataset.precio;
          if (tipo && opcion.dataset.tipo) tipo.value = opcion.dataset.tipo;
          if (referencia) referencia.value = opcion.dataset.referencia || "";
          recalcular();
        });
      }

      const quitar = $("[data-quitar-linea]", fila);
      if (quitar) {
        quitar.addEventListener("click", () => {
          if ($$(".linea-cotizacion", cuerpo).length <= 1) return;
          fila.remove();
          recalcular();
        });
      }
    }

    $$(".linea-cotizacion", cuerpo).forEach(conectarFila);

    $("[data-agregar-linea]", contenedor)?.addEventListener("click", () => {
      if (!plantilla) return;
      const nodo = plantilla.content.firstElementChild.cloneNode(true);
      cuerpo.appendChild(nodo);
      conectarFila(nodo);
      nodo.querySelector("select, input")?.focus();
      recalcular();
    });

    descuento?.addEventListener("input", recalcular);
    recalcular();
  }

  /* ---------------------------------------------------------------
   * Contadores simples del panel
   * --------------------------------------------------------------- */
  function iniciarBarras() {
    $$("[data-barra]").forEach((barra) => {
      const valor = Math.max(0, Math.min(100, parseFloat(barra.dataset.barra || "0")));
      requestAnimationFrame(() => { barra.style.width = valor + "%"; });
    });
  }

  /* ---------------------------------------------------------------
   * Confirmar check-in con Enter en el buscador
   * --------------------------------------------------------------- */
  function iniciarAtajos() {
    document.addEventListener("keydown", (ev) => {
      if (ev.key === "/" && !["INPUT", "TEXTAREA", "SELECT"].includes(document.activeElement.tagName)) {
        const buscador = $(".buscador-top input");
        if (buscador) {
          ev.preventDefault();
          buscador.focus();
        }
      }
    });
  }

  /* ---------------------------------------------------------------
   * Formularios que cambian de acción o de campos según una selección
   * --------------------------------------------------------------- */
  function basePanel() {
    return document.body.dataset.base || "";
  }

  function iniciarFormulariosDinamicos() {
    const tipoMov = $("[data-tipo-mov]");
    if (tipoMov) {
      const campos = {
        entrada: $('[data-campo="entrada"]'),
        ajuste: $('[data-campo="ajuste"]'),
      };
      const pintar = () => {
        Object.keys(campos).forEach((clave) => {
          if (campos[clave]) campos[clave].hidden = clave !== tipoMov.value;
        });
      };
      tipoMov.addEventListener("change", pintar);
      pintar();
    }

    const selectorEvento = $("[data-evento-insumo]");
    const formApartar = $("#form-apartar");
    if (selectorEvento && formApartar) {
      const actualizar = () => {
        formApartar.action = basePanel() + "/eventos/" + selectorEvento.value + "/insumos";
      };
      selectorEvento.addEventListener("change", actualizar);
      actualizar();
    }

    const formImportar = $("#form-importar");
    if (formImportar) {
      formImportar.addEventListener("submit", (ev) => {
        const evento = formImportar.querySelector("#id_evento");
        if (!evento || !evento.value) {
          ev.preventDefault();
          return;
        }
        formImportar.action = basePanel() + "/eventos/" + evento.value + "/invitados/importar";
      });
    }

    const tipoReserva = $("[data-tipo-reserva]");
    if (tipoReserva) {
      const paneles = {
        espacio: $('[data-panel="espacio"]'),
        recurso: $('[data-panel="recurso"]'),
      };
      const pintar = () => {
        const valor = tipoReserva.value === "recurso" ? "recurso" : "espacio";
        Object.keys(paneles).forEach((clave) => {
          if (!paneles[clave]) return;
          paneles[clave].hidden = clave !== valor;
          paneles[clave].querySelectorAll("select, input").forEach((campo) => {
            campo.disabled = clave !== valor;
          });
        });
      };
      tipoReserva.addEventListener("change", pintar);
      pintar();
    }
  }

  /**
   * Boton "Regresar" de las paginas de error.
   *
   * Antes era href="javascript:history.back()", que la CSP bloquea
   * (script-src 'self'): el boton existia pero no hacia nada. Si no hay
   * historial dentro del sitio, lleva al panel en vez de dejar al usuario
   * atrapado en la pagina de error.
   */
  function iniciarVolverAtras() {
    document.querySelectorAll("[data-atras]").forEach((boton) => {
      boton.addEventListener("click", () => {
        if (window.history.length > 1) {
          window.history.back();
          return;
        }
        const base = document.body.dataset.base || "";
        window.location.href = base + "/panel";
      });
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    iniciarVolverAtras();
    iniciarSidebar();
    iniciarFlash();
    iniciarConfirmaciones();
    iniciarFiltros();
    iniciarCopiar();
    iniciarImpresion();
    iniciarCotizador();
    iniciarBarras();
    iniciarAtajos();
    iniciarFormulariosDinamicos();
  });
})();
