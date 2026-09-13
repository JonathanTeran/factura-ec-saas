@php($assetBase = rtrim(config('app.url'), '/'))
{{-- Bloque promocional al pie de TODOS los correos: Facturón + AmePhia. --}}
<table class="promo" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width: 570px; max-width: 100%; margin: 12px auto 32px;">
<tr>
<td style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 22px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td valign="middle" width="150" style="padding: 0 16px 12px 0;">
<a href="https://facturon.ec" style="text-decoration: none;"><img src="{{ $assetBase }}/marketing/email-logo-dark.png" alt="Facturón" width="126" height="38" style="display: block; border: 0; width: 126px; height: 38px;"></a>
</td>
<td valign="middle" style="padding: 0 0 12px;">
<p style="margin: 0; font-size: 13px; line-height: 1.5; color: #475569;"><strong style="color: #0b1220;">Facturación electrónica del Ecuador.</strong> Comprobantes autorizados por el SRI en segundos, desde $2.99 al mes y sin comisión por documento. <a href="https://facturon.ec" style="color: #2b54e4; font-weight: 700; text-decoration: none;">facturon.ec</a></p>
</td>
</tr>
<tr>
<td valign="middle" width="150" style="border-top: 1px solid #e2e8f0; padding: 12px 16px 0 0;">
<a href="https://amephia.com" style="text-decoration: none;"><img src="{{ $assetBase }}/marketing/amephia-logo.png" alt="AmePhia" width="118" style="display: block; border: 0; width: 118px; height: auto;"></a>
</td>
<td valign="middle" style="border-top: 1px solid #e2e8f0; padding: 12px 0 0;">
<p style="margin: 0; font-size: 13px; line-height: 1.5; color: #475569;">Facturón es un producto de <strong style="color: #0b1220;">AmePhia Systems</strong>. Desarrollamos software a medida, aplicaciones móviles y sistemas para empresas. <a href="https://amephia.com" style="color: #2b54e4; font-weight: 700; text-decoration: none;">amephia.com</a></p>
</td>
</tr>
</table>
</td>
</tr>
</table>
