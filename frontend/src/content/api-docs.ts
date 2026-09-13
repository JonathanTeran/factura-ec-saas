/**
 * Contenido de la documentación pública de la API (/docs/api).
 * La fuente de verdad de los esquemas es /docs/openapi.yaml; aquí va la guía
 * narrativa, la referencia resumida y los ejemplos.
 */

export const API_BASE_URL = "https://facturon.ec/api/v1/ext";
export const OPENAPI_PATH = "/docs/openapi.yaml";

export type HttpMethod = "GET" | "POST" | "PATCH";

export type ApiParam = {
  name: string;
  in: "path" | "query" | "header" | "body";
  type: string;
  required?: boolean;
  description: string;
};

export type ApiEndpoint = {
  id: string;
  method: HttpMethod;
  path: string;
  scope: string | null;
  summary: string;
  description?: string;
  params?: ApiParam[];
  request?: string;
  response?: string;
};

export type ApiSection = {
  id: string;
  title: string;
  intro: string;
  endpoints: ApiEndpoint[];
};

export const DOC_NAV = [
  { id: "introduccion", label: "Introducción" },
  { id: "autenticacion", label: "Autenticación" },
  { id: "alcances-limites", label: "Alcances y límites" },
  { id: "flujo", label: "Flujo para emitir" },
  { id: "idempotencia", label: "Idempotencia" },
  { id: "cuenta", label: "Cuenta y empresas" },
  { id: "documentos", label: "Documentos" },
  { id: "clientes", label: "Clientes" },
  { id: "productos", label: "Productos" },
  { id: "catalogos", label: "Catálogos" },
  { id: "errores", label: "Errores" },
  { id: "ejemplos", label: "Ejemplos de código" },
  { id: "preguntas", label: "Preguntas frecuentes" },
] as const;

export const SCOPES: Array<{ scope: string; description: string }> = [
  { scope: "documents:read", description: "Listar y ver documentos, estado ante el SRI, RIDE y XML." },
  { scope: "documents:write", description: "Crear y emitir documentos, reenviar al SRI, anular, reenviar correo." },
  { scope: "customers:read", description: "Listar, ver y buscar clientes." },
  { scope: "customers:write", description: "Crear y actualizar clientes." },
  { scope: "products:read", description: "Listar y ver productos y servicios." },
  { scope: "products:write", description: "Crear y actualizar productos y servicios." },
  { scope: "catalogs:read", description: "Catálogos del SRI. Incluido en toda llave." },
  { scope: "*", description: "Acceso total (equivale a todos los anteriores)." },
];

export const RATE_LIMITS: Array<{ plan: string; limit: string }> = [
  { plan: "Negocio", limit: "60 peticiones / minuto" },
  { plan: "Profesional", limit: "120 peticiones / minuto" },
  { plan: "Enterprise", limit: "300 peticiones / minuto" },
];

export const ERROR_CODES: Array<{ status: number; code: string; when: string }> = [
  { status: 401, code: "missing_api_key", when: "No enviaste la cabecera Authorization ni X-API-Key." },
  { status: 401, code: "invalid_api_key", when: "La llave no existe o fue desactivada." },
  { status: 401, code: "expired_api_key", when: "La llave caducó. Genera una nueva en el panel." },
  { status: 403, code: "tenant_inactive", when: "La cuenta está suspendida o inactiva." },
  { status: 403, code: "subscription_required", when: "La cuenta no tiene una suscripción vigente (incluye upgrade_url)." },
  { status: 403, code: "api_access_not_allowed", when: "El plan no incluye API (incluye upgrade_url)." },
  { status: 403, code: "insufficient_scope", when: "La llave no tiene el alcance necesario (incluye required_scope)." },
  { status: 403, code: "plan_limit_reached", when: "Se alcanzó el límite mensual de documentos del plan." },
  { status: 404, code: "not_found", when: "El recurso no existe o pertenece a otra cuenta." },
  { status: 404, code: "xml_not_available", when: "El XML aún no se generó (documento sin firmar/autorizar)." },
  { status: 409, code: "idempotency_key_reused", when: "Mismo Idempotency-Key con un cuerpo distinto." },
  { status: 409, code: "idempotency_in_progress", when: "Otra petición con el mismo Idempotency-Key está en curso." },
  { status: 409, code: "send_failed", when: "El documento se creó pero la empresa no está lista para emitir (firma, establecimientos)." },
  { status: 422, code: "validation_error", when: "Campos inválidos (errors por campo) o reglas del SRI (errors.sri)." },
  { status: 429, code: "rate_limit_exceeded", when: "Se superó el límite por minuto (incluye retry_after y Retry-After)." },
];

const DOCUMENT_EXAMPLE = `{
  "company_id": 1,
  "emission_point_id": 1,
  "customer_id": 25,
  "document_type": "01",
  "issue_date": "2026-09-13",
  "subtotal_15": 100.00,
  "total_tax": 15.00,
  "total": 115.00,
  "payment_methods": [{ "code": "01", "amount": 115.00 }],
  "items": [
    {
      "main_code": "SERV-001",
      "description": "Servicio de consultoría",
      "quantity": 1,
      "unit_price": 100.00,
      "discount": 0,
      "subtotal": 100.00,
      "tax_code": "2",
      "tax_percentage_code": "4",
      "tax_rate": 15,
      "tax_base": 100.00,
      "tax_value": 15.00
    }
  ],
  "additional_info": { "Pedido": "WEB-1001" }
}`;

const DOCUMENT_RESPONSE = `{
  "success": true,
  "message": "Documento creado y enviado al SRI. Consulta su estado en GET /documents/{id}/status.",
  "data": {
    "document": {
      "id": 812,
      "document_type": "01",
      "document_number": "001-001-000000042",
      "access_key": "1309202601179001234500110010010000000421234567811",
      "status": "processing",
      "total": 115,
      "customer": { "id": 25, "identification_number": "1790012345001", "name": "ACME S.A." },
      "items": [ { "main_code": "SERV-001", "description": "Servicio de consultoría", "quantity": 1 } ]
    }
  }
}`;

const STATUS_RESPONSE = `{
  "success": true,
  "data": {
    "status": "authorized",
    "status_label": "Autorizado",
    "authorization_number": "1309202601179001234500110010010000000421234567811",
    "authorization_date": "2026-09-13T15:04:11.000000Z",
    "sri_messages": [],
    "contingency_active": false,
    "contingency_message": null
  }
}`;

export const API_SECTIONS: ApiSection[] = [
  {
    id: "cuenta",
    title: "Cuenta y empresas",
    intro: "Verifica la llave y descubre los identificadores que necesitas para emitir.",
    endpoints: [
      {
        id: "get-me",
        method: "GET",
        path: "/me",
        scope: null,
        summary: "Identidad de la integración",
        description: "Cuenta, plan, límites, uso del período y datos de la llave. Úsalo para probar credenciales.",
        response: `{
  "success": true,
  "data": {
    "tenant": { "id": 12, "name": "ACME S.A.", "status": "active" },
    "plan": { "slug": "negocio", "name": "Negocio" },
    "limits": { "documents_per_month": 50, "effective_document_limit": 50, "unlimited": false, "rate_limit_per_minute": 60 },
    "usage": { "documents_this_period": 17, "period_start": "2026-09-01" },
    "api_key": { "name": "Tienda en línea", "key_prefix": "fec_a1B2c3D4", "scopes": ["documents:write", "customers:write"], "effective_rate_limit": 60, "expires_at": null }
  }
}`,
      },
      {
        id: "get-companies",
        method: "GET",
        path: "/companies",
        scope: "documents:read",
        summary: "Empresas emisoras con establecimientos y puntos de emisión",
        description: "Devuelve company_id y emission_point_id (con su serie 001-001) y el ambiente del SRI configurado en cada empresa.",
        response: `{
  "success": true,
  "data": {
    "companies": [
      {
        "id": 1, "ruc": "1790012345001", "business_name": "ACME S.A.", "sri_environment": "2", "sri_environment_label": "Producción",
        "branches": [
          { "id": 1, "code": "001", "name": "Matriz", "is_main": true,
            "emission_points": [ { "id": 1, "code": "001", "name": "Caja 1", "series": "001-001" } ] }
        ]
      }
    ]
  }
}`,
      },
    ],
  },
  {
    id: "documentos",
    title: "Documentos",
    intro:
      "Facturas (01), liquidaciones de compra (03), notas de crédito (04) y débito (05), guías de remisión (06) y retenciones (07). El mismo payload que usa el panel de Facturón.",
    endpoints: [
      {
        id: "post-documents",
        method: "POST",
        path: "/documents",
        scope: "documents:write",
        summary: "Crear y emitir un documento",
        description:
          "Crea el comprobante y, salvo send: false, lo valida contra las reglas del SRI y lo envía a firmar y autorizar. Responde 201 con el documento en processing; la autorización llega en segundos y se consulta en /status. Facturón no recalcula importes: envía subtotal, tax_base, tax_value y total ya calculados.",
        params: [
          { name: "Idempotency-Key", in: "header", type: "string ≤128", description: "Identificador único de la operación en tu sistema (ej. el id del pedido). Válido 24 h." },
          { name: "company_id", in: "body", type: "integer", required: true, description: "Empresa emisora (ver /companies)." },
          { name: "emission_point_id", in: "body", type: "integer", required: true, description: "Punto de emisión de la empresa (ver /companies)." },
          { name: "customer_id", in: "body", type: "integer", required: true, description: "Cliente (ver /customers y /customers/lookup)." },
          { name: "document_type", in: "body", type: "\"01\" | \"03\" | \"04\" | \"05\" | \"06\" | \"07\"", required: true, description: "Tipo de comprobante." },
          { name: "issue_date", in: "body", type: "date", description: "Fecha de emisión (por defecto hoy)." },
          { name: "subtotal_15 / subtotal_12 / subtotal_5 / subtotal_0 / subtotal_no_tax", in: "body", type: "number", description: "Subtotales por tarifa de IVA." },
          { name: "total_tax", in: "body", type: "number", description: "IVA total." },
          { name: "total_discount", in: "body", type: "number", description: "Descuento total." },
          { name: "total", in: "body", type: "number", required: true, description: "Importe total." },
          { name: "payment_methods[]", in: "body", type: "{ code, amount, term?, time_unit? }", description: "Formas de pago del SRI (ver catálogo)." },
          { name: "items[]", in: "body", type: "objeto", required: true, description: "main_code, description, quantity, unit_price, discount, subtotal, tax_code, tax_percentage_code, tax_rate, tax_base, tax_value. Obligatorio salvo en retenciones." },
          { name: "additional_info", in: "body", type: "objeto", description: "Pares nombre → valor impresos en el RIDE (máx. 300 caracteres cada uno)." },
          { name: "reference_document_id + modification_reason", in: "body", type: "integer + string", description: "Obligatorios en notas de crédito y débito." },
          { name: "withholding_details[]", in: "body", type: "objeto", description: "Obligatorio en retenciones (07): support_doc_*, tax_type (renta|iva), retention_code, tax_base, retention_rate, retained_value." },
          { name: "send", in: "body", type: "boolean", description: "true por defecto. Con false queda en borrador para enviarlo luego con /send." },
        ],
        request: DOCUMENT_EXAMPLE,
        response: DOCUMENT_RESPONSE,
      },
      {
        id: "get-documents",
        method: "GET",
        path: "/documents",
        scope: "documents:read",
        summary: "Listar documentos",
        description: "Paginado (máximo 100 por página), ordenado por fecha de emisión descendente.",
        params: [
          { name: "status", in: "query", type: "string", description: "draft, processing, authorized, rejected, failed, voided." },
          { name: "document_type", in: "query", type: "string", description: "01, 03, 04, 05, 06, 07." },
          { name: "company_id / customer_id", in: "query", type: "integer", description: "Filtra por empresa o cliente." },
          { name: "date_from / date_to", in: "query", type: "date", description: "Rango de fecha de emisión (YYYY-MM-DD)." },
          { name: "access_key", in: "query", type: "string (49)", description: "Clave de acceso exacta." },
          { name: "search", in: "query", type: "string", description: "Clave de acceso, número o cliente." },
          { name: "page / per_page", in: "query", type: "integer", description: "Paginación; per_page máximo 100." },
        ],
        response: `{
  "success": true,
  "data": [ { "id": 812, "document_number": "001-001-000000042", "status": "authorized", "total": 115, "customer": { "name": "ACME S.A." } } ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 },
  "links": { "first": "…", "last": "…", "prev": null, "next": null }
}`,
      },
      {
        id: "get-document",
        method: "GET",
        path: "/documents/{id}",
        scope: "documents:read",
        summary: "Ver un documento",
        description: "Detalle completo con ítems, cliente, empresa, mensajes del SRI y banderas has_ride / has_xml.",
      },
      {
        id: "get-document-status",
        method: "GET",
        path: "/documents/{id}/status",
        scope: "documents:read",
        summary: "Estado ante el SRI",
        description: "Si el documento sigue en proceso, consulta al SRI en vivo antes de responder. Sondea cada 5–10 segundos tras emitir hasta ver authorized o rejected.",
        response: STATUS_RESPONSE,
      },
      {
        id: "post-document-send",
        method: "POST",
        path: "/documents/{id}/send",
        scope: "documents:write",
        summary: "Enviar al SRI",
        description: "Para borradores (creados con send: false) y para reintentar documentos fallidos o rechazados tras corregir la causa.",
      },
      {
        id: "post-document-void",
        method: "POST",
        path: "/documents/{id}/void",
        scope: "documents:write",
        summary: "Marcar como anulado",
        description: "Solo documentos autorizados. Registra el motivo en Facturón; la anulación fiscal se hace en SRI en línea o emitiendo una nota de crédito.",
        params: [{ name: "reason", in: "body", type: "string ≤300", required: true, description: "Motivo de la anulación." }],
      },
      {
        id: "post-document-email",
        method: "POST",
        path: "/documents/{id}/email",
        scope: "documents:write",
        summary: "Reenviar el comprobante por correo",
        params: [{ name: "email", in: "body", type: "string", description: "Destinatario. Si se omite, el correo del cliente." }],
      },
      {
        id: "get-document-ride",
        method: "GET",
        path: "/documents/{id}/ride",
        scope: "documents:read",
        summary: "RIDE en PDF",
        description: "Responde el PDF (application/pdf). Con ?url=1 responde JSON con una URL temporal de 30 minutos, útil para enlazar desde tu sistema.",
      },
      {
        id: "get-document-xml",
        method: "GET",
        path: "/documents/{id}/xml",
        scope: "documents:read",
        summary: "XML firmado / autorizado",
        description: "Responde el XML (application/xml) o, con ?url=1, una URL temporal. Antes de la firma responde 404 xml_not_available.",
      },
    ],
  },
  {
    id: "clientes",
    title: "Clientes",
    intro: "Los comprobantes se emiten a un cliente registrado. Busca por identificación y crea solo si no existe.",
    endpoints: [
      {
        id: "get-customers-lookup",
        method: "GET",
        path: "/customers/lookup",
        scope: "customers:read",
        summary: "Buscar por identificación exacta",
        params: [{ name: "identification", in: "query", type: "string", required: true, description: "Cédula, RUC o pasaporte." }],
        response: `{ "success": true, "data": { "customer": { "id": 25, "identification_type": "04", "identification_number": "1790012345001", "name": "ACME S.A.", "email": "compras@acme.ec" } } }`,
      },
      {
        id: "post-customers",
        method: "POST",
        path: "/customers",
        scope: "customers:write",
        summary: "Crear cliente",
        params: [
          { name: "identification_type", in: "body", type: "\"04\" RUC | \"05\" cédula | \"06\" pasaporte | \"07\" consumidor final | \"08\" exterior", required: true, description: "Tipo de identificación del SRI." },
          { name: "identification_number", in: "body", type: "string ≤20", required: true, description: "Única por cuenta." },
          { name: "name", in: "body", type: "string ≤300", required: true, description: "Razón social o nombre." },
          { name: "email / additional_emails[] / phone / address", in: "body", type: "string", description: "Datos de contacto opcionales." },
        ],
        request: `{
  "identification_type": "04",
  "identification_number": "1790012345001",
  "name": "ACME S.A.",
  "email": "compras@acme.ec",
  "address": "Av. Amazonas N21-147, Quito"
}`,
      },
      { id: "get-customers", method: "GET", path: "/customers", scope: "customers:read", summary: "Listar clientes", params: [{ name: "search", in: "query", type: "string", description: "Nombre, identificación o correo." }] },
      { id: "get-customer", method: "GET", path: "/customers/{id}", scope: "customers:read", summary: "Ver cliente" },
      { id: "patch-customer", method: "PATCH", path: "/customers/{id}", scope: "customers:write", summary: "Actualizar cliente", description: "Envía el registro completo (mismos campos que al crear)." },
    ],
  },
  {
    id: "productos",
    title: "Productos y servicios",
    intro: "Opcional: los ítems de un documento pueden referenciar un product_id o describirse en línea.",
    endpoints: [
      { id: "get-products", method: "GET", path: "/products", scope: "products:read", summary: "Listar productos y servicios", params: [{ name: "search", in: "query", type: "string", description: "Código o nombre." }] },
      {
        id: "post-products",
        method: "POST",
        path: "/products",
        scope: "products:write",
        summary: "Crear producto o servicio",
        params: [
          { name: "code", in: "body", type: "string ≤50", required: true, description: "Código principal, único por cuenta." },
          { name: "sku", in: "body", type: "string ≤50", description: "Código auxiliar." },
          { name: "name", in: "body", type: "string ≤300", required: true, description: "Nombre." },
          { name: "type", in: "body", type: "\"product\" | \"service\"", required: true, description: "Bien o servicio." },
          { name: "unit_price", in: "body", type: "number", required: true, description: "Precio unitario sin IVA." },
          { name: "tax_percentage_code / tax_rate", in: "body", type: "string / number", description: "Tarifa de IVA (ej. \"4\" / 15)." },
          { name: "track_inventory / stock / min_stock", in: "body", type: "boolean / integer", description: "Control de inventario (planes con inventario)." },
        ],
        request: `{ "code": "CAM-001", "sku": "SKU-CAM-001", "name": "Camiseta", "type": "product", "unit_price": 12.50, "tax_percentage_code": "4", "tax_rate": 15 }`,
      },
      { id: "get-product", method: "GET", path: "/products/{id}", scope: "products:read", summary: "Ver producto" },
      { id: "patch-product", method: "PATCH", path: "/products/{id}", scope: "products:write", summary: "Actualizar producto", description: "Envía el registro completo (code, name, type y unit_price son obligatorios)." },
    ],
  },
  {
    id: "catalogos",
    title: "Catálogos del SRI",
    intro: "Códigos oficiales que necesitas al construir documentos. Disponibles con cualquier llave.",
    endpoints: [
      { id: "cat-identification-types", method: "GET", path: "/catalogs/identification-types", scope: null, summary: "Tipos de identificación" },
      { id: "cat-document-types", method: "GET", path: "/catalogs/document-types", scope: null, summary: "Tipos de comprobante" },
      { id: "cat-payment-methods", method: "GET", path: "/catalogs/payment-methods", scope: null, summary: "Formas de pago" },
      { id: "cat-tax-rates", method: "GET", path: "/catalogs/tax-rates", scope: null, summary: "Tarifas de IVA" },
      { id: "cat-retention-codes", method: "GET", path: "/catalogs/retention-codes", scope: null, summary: "Códigos de retención (IVA y renta)" },
    ],
  },
];

export const CODE_EXAMPLES: Array<{ id: string; label: string; language: string; code: string }> = [
  {
    id: "curl",
    label: "curl",
    language: "bash",
    code: `# 1) Verifica la llave
curl ${API_BASE_URL}/me \\
  -H "Authorization: Bearer fec_TU_LLAVE"

# 2) Emite una factura (idempotente por pedido)
curl -X POST ${API_BASE_URL}/documents \\
  -H "Authorization: Bearer fec_TU_LLAVE" \\
  -H "Content-Type: application/json" \\
  -H "Idempotency-Key: pedido-WEB-1001" \\
  -d @factura.json

# 3) Consulta el estado hasta que sea authorized
curl ${API_BASE_URL}/documents/812/status \\
  -H "Authorization: Bearer fec_TU_LLAVE"

# 4) Descarga el RIDE
curl -o factura.pdf ${API_BASE_URL}/documents/812/ride \\
  -H "Authorization: Bearer fec_TU_LLAVE"`,
  },
  {
    id: "javascript",
    label: "JavaScript (Node 18+)",
    language: "javascript",
    code: `const BASE = "${API_BASE_URL}";
const headers = {
  Authorization: \`Bearer \${process.env.FACTURON_API_KEY}\`,
  "Content-Type": "application/json",
};

async function emitirFactura(pedido) {
  const res = await fetch(\`\${BASE}/documents\`, {
    method: "POST",
    headers: { ...headers, "Idempotency-Key": \`pedido-\${pedido.id}\` },
    body: JSON.stringify(pedido.factura),
  });
  const json = await res.json();
  if (!res.ok) throw new Error(\`\${json.error}: \${json.message}\`);
  return json.data.document; // status: "processing"
}

async function esperarAutorizacion(id, intentos = 12) {
  for (let i = 0; i < intentos; i++) {
    const res = await fetch(\`\${BASE}/documents/\${id}/status\`, { headers });
    const { data } = await res.json();
    if (data.status === "authorized" || data.status === "rejected") return data;
    await new Promise((r) => setTimeout(r, 5000));
  }
  throw new Error("El SRI aún no responde; reintenta más tarde");
}`,
  },
  {
    id: "php",
    label: "PHP (Guzzle)",
    language: "php",
    code: `$client = new \\GuzzleHttp\\Client([
    'base_uri' => '${API_BASE_URL}/',
    'headers' => ['Authorization' => 'Bearer ' . getenv('FACTURON_API_KEY')],
]);

// Cliente: buscar o crear
try {
    $cliente = json_decode($client->get('customers/lookup', ['query' => ['identification' => '1790012345001']])->getBody(), true)['data']['customer'];
} catch (\\GuzzleHttp\\Exception\\ClientException $e) {
    if ($e->getResponse()->getStatusCode() !== 404) throw $e;
    $cliente = json_decode($client->post('customers', ['json' => [
        'identification_type' => '04', 'identification_number' => '1790012345001', 'name' => 'ACME S.A.',
    ]])->getBody(), true)['data']['customer'];
}

// Emitir
$factura = json_decode($client->post('documents', [
    'headers' => ['Idempotency-Key' => 'pedido-WEB-1001'],
    'json' => $payload + ['customer_id' => $cliente['id']],
])->getBody(), true)['data']['document'];

// Estado
$estado = json_decode($client->get("documents/{$factura['id']}/status")->getBody(), true)['data'];`,
  },
];

export const DOC_FAQS: Array<{ q: string; a: string }> = [
  {
    q: "¿Cómo pruebo sin emitir comprobantes reales?",
    a: "Configura la empresa emisora en ambiente de pruebas del SRI (Configuración → Datos del emisor). Los documentos se firman y autorizan contra el ambiente de pruebas del SRI con la misma API; cuando pases a producción, cambia el ambiente de la empresa y usa la misma llave.",
  },
  {
    q: "¿Cuánto tarda la autorización?",
    a: "Normalmente entre 2 y 15 segundos. La API responde de inmediato con el documento en processing; sondea /status. Si el SRI está caído, el documento entra en contingencia y Facturón reintenta automáticamente; el estado lo refleja con contingency_active.",
  },
  {
    q: "¿Qué pasa si mi sistema reintenta una petición?",
    a: "Envía siempre Idempotency-Key en POST /documents. Una repetición con el mismo cuerpo devuelve la misma respuesta (cabecera Idempotent-Replayed: true) y no crea otra factura.",
  },
  {
    q: "¿La API calcula el IVA?",
    a: "No. Tu sistema envía subtotales, impuestos y total ya calculados (como lo hace el panel). Antes de enviar al SRI, Facturón valida reglas como el tope de $50 a Consumidor Final o la validez de la identificación.",
  },
  {
    q: "¿Puedo recibir avisos cuando un documento se autoriza?",
    a: "Los webhooks están en desarrollo. Mientras tanto, sondea GET /documents/{id}/status o consulta GET /documents?status=authorized&date_from=… de forma periódica.",
  },
  {
    q: "¿Qué planes incluyen la API?",
    a: "Negocio, Profesional y Enterprise. Cada plan define el límite de peticiones por minuto; el límite mensual de documentos es el mismo del plan.",
  },
];
