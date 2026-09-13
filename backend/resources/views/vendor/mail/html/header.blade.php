@props(['url'])
<tr>
<td class="header">
<table class="flag" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td width="34%" bgcolor="#FFCE00" style="height: 4px; font-size: 0; line-height: 0; padding: 0;">&nbsp;</td>
<td width="33%" bgcolor="#0653C6" style="height: 4px; font-size: 0; line-height: 0; padding: 0;">&nbsp;</td>
<td width="33%" bgcolor="#EF3340" style="height: 4px; font-size: 0; line-height: 0; padding: 0;">&nbsp;</td>
</tr>
</table>
<table class="header-band" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="left">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ rtrim(config('app.url'), '/') }}/marketing/email-logo.png" class="logo" alt="Facturón" width="158" height="48" style="display: block; border: 0;">
</a>
</td>
</tr>
</table>
</td>
</tr>
