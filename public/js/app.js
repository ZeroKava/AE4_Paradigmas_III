// app.js - SPA del Emporio Brillante (JS Vanilla, fetch + async/await)
// Todo dato dinámico entra al DOM con .textContent (nunca innerHTML) -> mitiga XSS (OWASP A03)
"use strict";

const API = "../api/";
const $ = (sel) => document.querySelector(sel); //
const dinero = (n) => Number(n).toLocaleString("es-AR");
let productos = [];   // cache para el formulario de compra
let descuento = 0;    // % de cupón validado

/* ---------- utilidades ---------- */
function aviso(texto, tipo) {
    const estado = $("#estado");
    estado.textContent = texto; //.textContent previene inyección de XSS
    estado.className = !texto ? "" : (tipo === "error" ? "mensaje-error" : "mensaje-exito");
}
//crea etiquetas, asigna texto plano y atributos sin usar innerHTML
function el(tag, texto, attrs) {
    const nodo = document.createElement(tag);
    if (texto !== undefined) nodo.textContent = texto;
    Object.entries(attrs || {}).forEach(([k, v]) => nodo.setAttribute(k, v));
    return nodo;
}
//realiza peticiones asíncronas JSON y normaliza la respuesta 
async function api(url, metodo = "GET", cuerpo) {
    const r = await fetch(url, {
        method: metodo,
        headers: { "Content-Type": "application/json" },
        body: cuerpo ? JSON.stringify(cuerpo) : undefined
    });
    const datos = await r.json().catch(() => ({})); // evita fallos si el servidor devuelve una respuesta vacía
    return { ok: r.ok, status: r.status, datos };
}

async function seguro(fn) {
    try { await fn(); }
    catch (err) { aviso("No pudimos conectar con el servidor. Probá de nuevo.", "error"); }
}

// Engancha un formulario: evita el envío nativo, bloquea el botón mientras espera
function formulario(sel, enviar) {
    const f = $(sel);
    const btn = f.querySelector("button[type='submit']");
    const textoOriginal = btn.textContent;
    f.addEventListener("submit", async (e) => {
        e.preventDefault(); // evita la recarga completa de la página
        btn.disabled = true; // evita envios duplicados mientras espera respuestas del servidor
        btn.textContent = "Enviando...";
        await seguro(() => enviar(f));
        btn.disabled = false;
        btn.textContent = textoOriginal;
    });
}

/* ---------- router por hash ---------- */
//vistas disponibles en el index
const vistas = ["inicio", "catalogo", "producto", "comprar", "contacto", "encuesta", "admin"];
const cargadores = { catalogo: cargarCatalogo, producto: cargarProducto, comprar: cargarCompra, admin: cargarAdmin };

// controla la navegación sin recarga leyendo
async function navegar() {
    const [ruta, param] = (location.hash.slice(1) || "inicio").split("/");
    const vista = vistas.includes(ruta) ? ruta : "inicio"; //en caso de que la ruta no exista, redirige a inicio
    vistas.forEach((v) => { $("#v-" + v).hidden = v !== vista; });
    document.querySelectorAll("nav a").forEach((a) => {
        if (a.getAttribute("href") === "#" + vista) a.setAttribute("aria-current", "page");
        else a.removeAttribute("aria-current");
    });
    aviso("");
    if (cargadores[vista]) await seguro(() => cargadores[vista](param));
    $("#v-" + vista + " h2").focus(); // el lector de pantalla anuncia la nueva "página"
}

/* ---------- catálogo / producto ---------- */
function filaProducto(p, admin) {
    const tr = el("tr");
    const tdImg = el("td");
    if (p.imagen) tdImg.append(el("img", undefined, { src: "imagenes/" + p.imagen, alt: p.nombre, width: "100" }));
    const acciones = el("td");
    if (admin) {
        // en el rol de administrador se genera botones de edicion y borrado
        const editar = el("button", "Editar", { type: "button", "aria-label": "Editar " + p.nombre });
        editar.addEventListener("click", () => editarProducto(p));
        const borrar = el("button", "Eliminar", { type: "button", "aria-label": "Eliminar " + p.nombre });
        borrar.addEventListener("click", () => seguro(() => eliminarProducto(p)));
        acciones.append(editar, borrar);
    } else {
        acciones.append(el("a", "Ver", { href: "#producto/" + p.id, "aria-label": "Ver " + p.nombre }));
    }
    tr.append(tdImg, el("td", p.nombre), el("td", p.categoria), el("td", p.descripcion),
              el("td", "$" + dinero(p.precio)), acciones);
    return tr;
}

// consulta catálogo en el backend y renderiza la tabla 
async function cargarCatalogo() {
    const r = await api(API + "productos.php?tipo=impresora");
    if (!r.ok) return aviso(r.datos.error || "No se pudo cargar el catálogo.", "error");
    $("#tbodyCatalogo").replaceChildren(...r.datos.map((p) => filaProducto(p, false)));
}
// carga el detalle de un producto, se valida que el id sea numerico
async function cargarProducto(id) {
    $("#prodNombre").textContent = "Producto";
    $("#prodPrecio").textContent = "";
    $("#prodDesc").textContent = "";
    $("#prodImg").hidden = true;
    if (!/^\d+$/.test(id || "")) return aviso("Elegí un producto del catálogo.", "error");

    const r = await api(API + "productos.php?id=" + id);
    if (!r.ok) return aviso(r.datos.error || "No se pudo cargar el producto.", "error");
    const p = r.datos;
    $("#prodNombre").textContent = p.nombre;
    $("#prodPrecio").textContent = "$" + dinero(p.precio);
    $("#prodDesc").textContent = p.descripcion;
    if (p.imagen) {
        $("#prodImg").src = "imagenes/" + p.imagen;
        $("#prodImg").alt = p.nombre;
        $("#prodImg").hidden = false;
    }
}

/* ---------- compra ---------- */
async function cargarCompra() {
    const r = await api(API + "productos.php");
    if (!r.ok) return aviso(r.datos.error || "No se pudo cargar la lista.", "error");
    productos = r.datos;
    const lista = $("#listaItems");
    lista.replaceChildren();
    productos.forEach((p) => {
        const id = "cant-" + p.id;
        lista.append(
            el("label", p.nombre + " ($" + dinero(p.precio) + ")", { for: id }),
            el("input", undefined, { type: "number", id: id, min: "0", max: "99", value: "0", "data-id": p.id })
        );
    });
    calcularTotal();
}
//recopila items que tengan una cantidad mayor a 0
function itemsElegidos() {
    return [...document.querySelectorAll("#listaItems input")]
        .map((i) => ({ id: Number(i.dataset.id), cantidad: parseInt(i.value, 10) || 0 }))
        .filter((i) => i.cantidad > 0);
}
//calcula monto total multiplicando cantidades por precio y aplicando descuento
function calcularTotal() {
    const total = itemsElegidos().reduce((suma, it) => {
        const p = productos.find((x) => Number(x.id) === it.id);
        return suma + it.cantidad * Number(p.precio);
    }, 0);
    $("#total").textContent = dinero(total * (1 - descuento / 100));
}
//vuelve a calcular el total cada vez que se cambia la cantidad de cualquier item
$("#listaItems").addEventListener("input", calcularTotal);
//valida código de descuento en el servidor php
$("#btnCupon").addEventListener("click", () => seguro(async () => {
    const codigo = $("#cupon").value.trim();
    descuento = 0;
    if (!codigo) { calcularTotal(); return aviso("Por favor, ingresá un código.", "error"); }
    const r = await api(API + "compras.php?cupon=" + encodeURIComponent(codigo));
    if (r.ok) {
        descuento = r.datos.descuento;
        aviso("Cupón aplicado: " + descuento + "% de descuento.");
    } else {
        aviso(r.datos.error || "Código inválido o vencido.", "error");
    }
    calcularTotal();
}));
//valida datos del cliente y envia orden de compra al backend
formulario("#formCompra", async () => {
    const datos = {
        nombre: $("#cp-nombre").value.trim(), direccion: $("#cp-direccion").value.trim(),
        telefono: $("#cp-telefono").value.trim(), email: $("#cp-email").value.trim(),
        pago: $("#cp-pago").value, cupon: descuento ? $("#cupon").value.trim() : "",
        items: itemsElegidos()
    };
    if (!datos.nombre || !datos.direccion || !datos.telefono || !datos.email || !datos.pago) {
        return aviso("Completá tus datos y el medio de pago.", "error");
    }
    if (!datos.items.length) return aviso("Elegí al menos un producto o servicio.", "error");
    const r = await api(API + "compras.php", "POST", datos);
    if (!r.ok) return aviso(r.datos.error || "No pudimos registrar la compra.", "error");
    aviso("Compra #" + r.datos.id + " registrada. Total: $" + dinero(r.datos.total));
    $("#formCompra").reset();
    descuento = 0;
    calcularTotal();
});

/* ---------- contacto ---------- */
//valida campos obligatorios y formato de email
formulario("#formContacto", async (f) => {
    const datos = {
        nombre: $("#ct-nombre").value.trim(), email: $("#ct-email").value.trim(),
        servicio: $("#ct-servicio").value, consulta: $("#ct-consulta").value.trim()
    };
    if (Object.values(datos).some((v) => v === "")) return aviso("Completá todos los campos antes de enviar.", "error");
    if (!/^\S+@\S+\.\S+$/.test(datos.email)) return aviso("Ingresá un e-mail válido.", "error");
    const r = await api(API + "contacto.php", "POST", datos);
    if (!r.ok) return aviso(r.datos.error || "No pudimos registrar tu consulta.", "error");
    aviso(r.datos.mensaje);
    f.reset();
});

/* ---------- encuesta ---------- */
const comentario = $("#comentario");
//actualiza el contador dinámico de caracteres disponibles
function actualizarContador() {
    const restantes = comentario.maxLength - comentario.value.length;
    $("#contador").textContent = restantes + " caracteres restantes";
    $("#contador").classList.toggle("poco", restantes <= 20);
}
comentario.addEventListener("input", actualizarContador);

function respuesta(nombre) {
    const sel = "#formEncuesta input[name='" + nombre + "']";
    const elegido = document.querySelector(sel + ":checked");
    document.querySelector(sel).closest("fieldset").classList.toggle("sin-responder", !elegido);
    return elegido;
}
document.querySelectorAll("#formEncuesta input[type='radio']").forEach((radio) => {
    radio.addEventListener("change", () => radio.closest("fieldset").classList.remove("sin-responder"));
});
//envía los resultados de satisfacción y recomendación 
formulario("#formEncuesta", async (f) => {
    const sat = respuesta("satisfaccion");
    const rec = respuesta("recomienda");
    if (!sat || !rec) return aviso("Respondé las preguntas marcadas en rojo antes de enviar.", "error");
    const r = await api(API + "encuesta.php", "POST",
        { satisfaccion: Number(sat.value), recomienda: rec.value, comentario: comentario.value.trim() });
    if (!r.ok) return aviso(r.datos.error || "No pudimos registrar tu respuesta.", "error");
    aviso(r.datos.mensaje + " (Satisfacción: " + sat.value + "/5)");
    f.reset();
    actualizarContador();
});

/* ---------- administración (requiere sesión) ---------- */
//alterna entre el formulario de login
function mostrarAdmin(autenticado, usuario) {
    $("#formLogin").hidden = autenticado;
    $("#adminPanel").hidden = !autenticado;
    $("#adminUsuario").textContent = usuario || "";
}

// Si el servidor responde 401, volvemos al login
function sesionVencida(r) {
    if (r.status !== 401) return false;
    mostrarAdmin(false);
    aviso(r.datos.error || "Tu sesión expiró.", "error");
    return true;
}
//carga todos los productos en la tabla de gestión del administrador
async function listarAdmin() {
    const r = await api(API + "productos.php");
    if (r.ok) $("#tbodyAdmin").replaceChildren(...r.datos.map((p) => filaProducto(p, true)));
}
//verifica contra $_SESSION en el backend si el usuario ya tiene sesion iniciada al abrir #admin
async function cargarAdmin() {
    const r = await api(API + "login.php");
    mostrarAdmin(r.datos.autenticado, r.datos.usuario);
    if (r.datos.autenticado) await listarAdmin();
}
//envía credenciales para autenticar en el servidor (POST) y limpia la contraseña del DOM al instante
formulario("#formLogin", async () => {
    const r = await api(API + "login.php", "POST",
        { usuario: $("#lg-usuario").value.trim(), password: $("#lg-password").value });
    $("#lg-password").value = "";
    if (!r.ok) return aviso(r.datos.error || "No se pudo iniciar sesión.", "error");
    aviso("Sesión iniciada.");
    mostrarAdmin(true, r.datos.usuario);
    await listarAdmin();
});
//destruye la sesión activa en el servidor mediante una petición DELETE
$("#btnLogout").addEventListener("click", () => seguro(async () => {
    await api(API + "login.php", "DELETE");
    mostrarAdmin(false);
    aviso("Sesión cerrada.");
}));
//carga los datos de un producto existo en el formulario y enfoca el primer campo
function editarProducto(p) {
    $("#pr-id").value = p.id;
    $("#pr-nombre").value = p.nombre;
    $("#pr-tipo").value = p.tipo;
    $("#pr-categoria").value = p.categoria;
    $("#pr-descripcion").value = p.descripcion;
    $("#pr-precio").value = p.precio;
    $("#pr-imagen").value = p.imagen;
    $("#pr-nombre").focus();
}
//elimina un producto por ID
async function eliminarProducto(p) {
    if (!confirm("¿Eliminar \"" + p.nombre + "\"?")) return;
    const r = await api(API + "productos.php?id=" + p.id, "DELETE");
    if (sesionVencida(r)) return;
    if (!r.ok) return aviso(r.datos.error || "No se pudo eliminar.", "error");
    aviso("Producto eliminado.");
    await listarAdmin();
}
//crea o actualiza un producto en MySQL según si existe un ID oculto
formulario("#formProducto", async (f) => {
    const id = $("#pr-id").value;
    const datos = {
        nombre: $("#pr-nombre").value.trim(), tipo: $("#pr-tipo").value,
        categoria: $("#pr-categoria").value.trim(), descripcion: $("#pr-descripcion").value.trim(),
        precio: $("#pr-precio").value, imagen: $("#pr-imagen").value.trim()
    };
    if (!datos.nombre || datos.precio === "") return aviso("Completá al menos nombre y precio.", "error");
    const r = id
        ? await api(API + "productos.php?id=" + id, "PUT", datos)
        : await api(API + "productos.php", "POST", datos);
    if (sesionVencida(r)) return;
    if (!r.ok) return aviso(r.datos.error || "No se pudo guardar.", "error");
    aviso("Producto guardado.");
    f.reset();
    await listarAdmin();
});

// registra un nuevo administrador encriptando su clave con BCRYPT en el servidor
formulario("#formNuevoUsuario", async (f) => {
    const datos = {
        usuario: $("#nu-usuario").value.trim(),
        password: $("#nu-password").value
    };
    $("#nu-password").value = "";
    if (!datos.usuario || datos.password.length < 8) {
        return aviso("Completá el usuario y una contraseña de al menos 8 caracteres.", "error");
    }
    const r = await api(API + "usuarios.php", "POST", datos);
    if (sesionVencida(r)) return;
    if (!r.ok) return aviso(r.datos.error || "No se pudo crear el administrador.", "error");
    aviso(r.datos.mensaje);
    f.reset();
});

/* ---------- arranque ---------- */
window.addEventListener("hashchange", navegar);
navegar();
