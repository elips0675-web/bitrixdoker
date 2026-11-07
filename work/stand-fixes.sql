-- DB-level fixes for this stand.
-- They are NOT part of the project migrations in www/db/migrations
-- (the source repository D:\nlbbootstrap-main is not modified).
--
-- requests (7), bookings (8), orders (9): migration 002 marks CODE and XML_ID
-- as required, but the public forms do not submit them. The stand relaxes the
-- requirement and generates CODE/XML_ID in OnBeforeIBlockElementAdd
-- (see www/local/php_interface/include/nlb_forms.php).
--
-- Apply inside the database container, for example:
--   docker compose exec -T db mysql -ubitrix -pbitrix_pass bitrix < work/stand-fixes.sql

UPDATE b_iblock_fields
SET IS_REQUIRED = 'N'
WHERE IBLOCK_ID IN (7, 8, 9)
  AND FIELD_ID IN ('CODE', 'XML_ID');
