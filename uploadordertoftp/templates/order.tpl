00_{$customer_id}
01_{$order_name}
02_{$lastname} {$firstname}
03_{$email}
04_{$delivery_phone}
05_{$invoice_company}
06_{$invoice_dni}
07_{$invoice_vat_number}
08_{$invoice_address1} {$invoice_address2}
09_{$invoice_postal_code}
10_{$invoice_city}
11_{$invoice_country}
12_{$delivery_address1} {$delivery_address2}
13_{$delivery_postal_code}
14_{$delivery_city}
15_{$delivery_country}
16_{$payment}
17_{$total_shipping_tax_excl}
18_{$discount_pct}
{foreach from=$products key=myId item=i}
A1_{$i.product_reference}
A2_{$i.product_quantity}
A3_{$i.unit_price_tax_excl}
{/foreach}
C1_{$total_paid}
C2_{$total_products}
C3_{$total_discounts}
C4_{$total_shipping}