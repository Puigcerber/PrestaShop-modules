{**
 * Copyright (C) 2014  Pablo Villoslada Puigcerber
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *}
00_{$customer_id|escape:'html'}
01_{$order_name|escape:'html'}
02_{$lastname|escape:'html'} {$firstname|escape:'html'}
03_{$email|escape:'html'}
04_{$delivery_phone|escape:'html'}
05_{$invoice_company|escape:'html'}
06_{$invoice_dni|escape:'html'}
07_{$invoice_vat_number|escape:'html'}
08_{$invoice_address1|escape:'html'} {$invoice_address2|escape:'html'}
09_{$invoice_postal_code|escape:'html'}
10_{$invoice_city|escape:'html'}
11_{$invoice_country|escape:'html'}
12_{$delivery_address1|escape:'html'} {$delivery_address2|escape:'html'}
13_{$delivery_postal_code|escape:'html'}
14_{$delivery_city|escape:'html'}
15_{$delivery_country|escape:'html'}
16_{$payment|escape:'html'}
17_{$total_shipping_tax_excl|escape:'html'}
18_{$discount_pct|escape:'html'}
{foreach from=$products key=myId item=i}
A1_{$i.product_reference|escape:'html'}
A2_{$i.product_quantity|escape:'html'}
A3_{$i.unit_price_tax_excl|escape:'html'}
{/foreach}
C1_{$total_paid|escape:'html'}
C2_{$total_products|escape:'html'}
C3_{$total_discounts|escape:'html'}
C4_{$total_shipping|escape:'html'}