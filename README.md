# Bitrix + NLB: стенд на Docker

Локальный стенд «1С-Битрикс: Управление сайтом» (редакция «Стандарт», демо-лицензия)
с демо-проектом **NLB Bootstrap** (`D:\nlbbootstrap-main`), развёрнутый в Docker.

- Сайт: http://localhost:8080/
- Админка: http://localhost:8080/bitrix/admin/ — `admin` / `Bitrix!2026`
- Полный журнал работ: [`Что сделано.txt`](Что%20сделано.txt)
- Открытые задачи стенда: [`Что доделать.txt`](Что%20доделать.txt)

## Что в репозитории

| Путь | Содержимое |
|------|------------|
| `docker-compose.yml` | web (8080) + db MySQL 8.4 (3307), том `mysql_data` |
| `php/` | Dockerfile (php 8.3-apache + ionCube Loader), `php.ini`, `apache.conf` |
| `www/` | корень сайта: страницы проекта, `local/` (шаблон `nlb_bootstrap`, обработчики), `db/` (миграции и проверки), `tools/`, `menu/`, `preview/` |
| `www/upload/iblock/` | картинки демо-контента (элементы инфоблоков) |
| `work/` | скрипты автоматизации установки, `stages.log`, `stand-fixes.sql` |
| `log/apache/` | логи Apache (каталог; сами логи не коммитятся) |

**Не входит в репозиторий (см. `.gitignore`):**

- `www/bitrix/` — ядро Битрикса (~1 ГБ, лицензионный продукт). Ставится отдельно.
- `www/upload/main/`, `www/upload/tmp/` — системные иконки/смайлы и временные файлы установщика.
- `www/db/logs/`, `log/`, `work/stages/`, `work/bitrixsetup.php` — логи и артефакты установки.

## Команды

```bash
docker compose up -d --build      # поднять
docker compose logs -f web        # логи
docker compose exec web bash      # внутрь контейнера
docker compose down               # остановить (данные БД в томе mysql_data)
```

## Учётки

- MySQL root: `bitrix_root`, БД `bitrix`, пользователь `bitrix` / `bitrix_pass`
- MySQL снаружи: `127.0.0.1:3307`
- Админ Битрикса: `admin` / `Bitrix!2026`

Учётки локальные, демонстрационные — для публичного стенда менять обязательно.

## Состояние

- Установлен дистрибутив «Стандарт» (демо-лицензия), демо-контент Битрикса не ставился.
- Наложен проект `D:\nlbbootstrap-main`, миграции `db/` выполнены (3 типа инфоблоков,
  9 инфоблоков, 65 свойств, 12 разделов, 91 элемент), шаблон `nlb_bootstrap`
  привязан к сайту `s1`.
- Найденные дефекты проекта (9 штук) исправлены только в развёрнутой копии
  `www/`; исходный репозиторий не изменялся. Список и подробности — в
  `Что сделано.txt`.
- Формы проверены реальными POST с капчей: заявка (`/feedback/`), бронь
  (`/services/<code>/`), заказ (`/books/<code>/`).
- Письма формируются, но без SMTP не отправляются (`SUCCESS_EXEC = F`).
- Элементы, созданные формами, попадают в модерацию (`ACTIVE=N`).

## Восстановить стенд с нуля

1. Скачать дистрибутив «Стандарт» (`standard_encode.tar.gz`) с
   https://www.1c-bitrix.ru/download/cms.php и распаковать в `www/`.
   Дистрибутив кодирован ionCube — образ без ionCube Loader не подойдёт,
   в `php/Dockerfile` он уже установлен.
2. `docker compose up -d --build`, открыть http://localhost:8080/ и пройти мастер
   (БД: `db`, `bitrix`, `bitrix` / `bitrix_pass`), создать администратора.
3. Наложить файлы проекта: скрипт `work/overlay-project.ps1` копирует страницы,
   `local/`, `db/`, `tools/`, `menu/` из исходного репозитория, не перезаписывая
   `bitrix/`, `upload/`, `.settings.php`, `dbconn.php`.
4. Выполнить миграции по одной (общий прогон падает на 003 из-за кеша нулевого
   ID инфоблока):
   ```bash
   docker compose exec -T -u www-data web php /var/www/html/db/run.php --only=003
   # ... 004 ... 012
   docker compose exec -T -u www-data web php /var/www/html/db/run.php --only=007 --set-template
   ```
5. Создать типы почтовых событий: `work/mail-events.php` (запускать из контейнера),
   затем повторно прогнать `008` и `012` — создадутся шаблоны писем.
6. Применить `work/stand-fixes.sql` (снятие обязательности `CODE`/`XML_ID`
   у инфоблоков `requests`/`bookings`/`orders`).
7. Проверить данные: `docker compose exec -T -u www-data web php /var/www/html/db/check.php`
   (ожидается `OK: структура и демо-контент совпадают с db/config`).

## Полезное

- CLI запускать от `www-data`: `docker compose exec -T -u www-data web php <файл>`
  (от root портятся права `db/logs` и `upload`).
- Повторные миграции — по одной: `php db/run.php --only=003` и т.д.
- Прямой HTTP-доступ к `www/local/php_interface/` закрыт `.htaccess`;
  `/db/` и `/tools/` отдают 403 штатными `.htaccess` проекта.
