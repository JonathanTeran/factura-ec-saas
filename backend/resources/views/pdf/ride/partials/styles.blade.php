<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Helvetica, sans-serif;
        font-size: 8px;
        color: #1a1a1a;
        padding: 22px 26px;
        line-height: 1.32;
    }
    .w-100 { width: 100%; }
    .muted { color: #555; }
    .bold { font-weight: bold; }
    .center { text-align: center; }
    .right { text-align: right; }
    .mono { font-family: Courier, monospace; }

    /* Cajas con borde (estilo oficial SRI) */
    .box {
        border: 0.9px solid #222;
        border-radius: 3px;
        padding: 9px 11px;
    }

    .label {
        font-size: 7.4px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #333;
    }

    .doc-title { font-size: 15px; font-weight: bold; letter-spacing: 0.5px; }
    .doc-number { font-size: 9.5px; }

    .kv { width: 100%; border-collapse: collapse; }
    .kv td { padding: 1.6px 0; vertical-align: top; }
    .kv td.k { color: #333; }

    /* Tabla de ítems */
    table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.items th {
        border: 0.9px solid #222;
        background: #f2f2f2;
        padding: 4px 5px;
        font-size: 7.2px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: bold;
    }
    table.items td {
        border: 0.9px solid #999;
        padding: 4px 5px;
        vertical-align: top;
    }

    /* Bloque inferior: info + totales */
    table.summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 8px -8px 0; }
    table.summary > tbody > tr > td { vertical-align: top; }

    table.totals { width: 100%; border-collapse: collapse; }
    table.totals td {
        border: 0.9px solid #999;
        padding: 3.8px 7px;
    }
    table.totals td.k { font-weight: bold; width: 60%; }
    table.totals tr.grand td {
        font-weight: bold;
        font-size: 9.5px;
        background: #f2f2f2;
    }

    table.pay { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.pay th, table.pay td {
        border: 0.9px solid #999;
        padding: 3.6px 6px;
    }
    table.pay th { background: #f2f2f2; font-size: 7.2px; text-transform: uppercase; }

    .section-title {
        font-size: 7.6px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        text-align: center;
        padding: 3px 0;
        background: #f2f2f2;
        border: 0.9px solid #999;
        border-bottom: none;
    }

    .watermark {
        position: fixed;
        top: 24%;
        left: 6%;
        transform: rotate(-27deg);
        font-size: 54px;
        font-weight: bold;
        color: rgba(190, 30, 30, 0.10);
        z-index: 0;
        white-space: nowrap;
    }
    .footer-note {
        margin-top: 12px;
        font-size: 7px;
        color: #888;
        text-align: center;
    }
    .access-key {
        font-family: Courier, monospace;
        font-size: 7.4px;
        word-break: break-all;
        letter-spacing: 0.2px;
    }
</style>
