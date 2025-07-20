SELECT
CASE
WHEN l.iso_code = 'ES' THEN
CONCAT(
'https://www.a-alvarez.com/',
p.id_product,
'-', pl.link_rewrite,
IF(pa.id_product_attribute IS NOT NULL, CONCAT('?id_product_attribute=', pa.id_product_attribute), '')
)
ELSE
CONCAT(
'https://www.a-alvarez.com/',
l.iso_code, '/',
p.id_product, '-', pl.link_rewrite,
IF(pa.id_product_attribute IS NOT NULL, CONCAT('?id_product_attribute=', pa.id_product_attribute), '')
)
END AS image_url
FROM aalv_product p
LEFT JOIN aalv_product_attribute pa ON pa.id_product = p.id_product
INNER JOIN aalv_product_lang pl ON pl.id_product = p.id_product
INNER JOIN aalv_lang l ON l.id_lang = pl.id_lang
WHERE p.id_product IN (57, 55000);
