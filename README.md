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
| `docker-compose.yml` | web (8080) + cron + db MySQL 8.4 (3307), том `mysql_data` |
| `php/` | Dockerfile (php 8.3-apache + ionCube Loader), `php.ini`, `apache.conf`, `crontab` (агенты Битрикса раз в минуту) |
| `www/` | корень сайта: страницы проекта, `local/` (шаблон `nlb_bootstrap`, обработчики), `db/` (миграции и проверки), `tools/`, `menu/`, `preview/` |
| `www/upload/iblock/` | картинки демо-контента (элементы инфоблоков) |
| `work/` | скрипты автоматизации установки, `stages.log`, `stand-fixes.sql` |
| `tools/` | гейт документации: `stand-facts.php` (факты из кода) и `check-stand-docs.mjs` |
| `log/apache/` | логи Apache (каталог; сами логи не коммитятся) |

**Не входит в репозиторий (см. `.gitignore`):**

- `www/bitrix/` — ядро Битрикса (~1 ГБ, лицензионный продукт). Ставится отдельно.
- `www/upload/main/`, `www/upload/tmp/` — системные иконки/смайлы и временные файлы установщика.
- `www/db/logs/`, `log/`, `work/stages/`, `work/bitrixsetup.php` — логи и артефакты установки.

## Команды

```bash
docker compose up -d --build      # поднять
docker compose logs -f web        # логи
docker compose logs -f cron       # агенты и очередь почты
docker compose exec web bash      # внутрь контейнера
docker compose down               # остановить (данные БД в томе mysql_data)
node tools/check-stand-docs.mjs   # документация: ссылки, бэклог, журнал, числа
php tools/stand-facts.php --json  # факты из кода для сверки чисел
```

## Cron

Сервис `cron` (контейнер `bitrix-cron`, тот же образ, что у `web`) держит демон
`cron` и раз в минуту запускает от `www-data`:

```bash
cd /var/www/html && flock -n /tmp/bitrix-cron.lock php -f bitrix/modules/main/tools/cron_events.php
```

Расписание лежит в репозитории — `php/crontab`, при сборке образа копируется
в `/etc/cron.d/bitrix`. Лог — `www/db/logs/cron.log` (каталог в `.gitignore`).
`flock` не даёт двум прогонам пересечься, если предыдущий не успел.

Так агенты Битрикса, очередь почты и рассылка `sender` работают без захода
посетителя. Проверка:

```bash
docker compose logs cron
docker compose exec -T db mysql -ubitrix -pbitrix_pass bitrix \
  -e "SELECT ID, LAST_EXEC, NEXT_EXEC FROM b_agent ORDER BY LAST_EXEC DESC LIMIT 5"
tail -n 40 www/db/logs/cron.log
```

Прогон вручную, когда не хочется ждать минуту:

```bash
docker compose exec -T -u www-data -w /var/www/html cron \
  php -f bitrix/modules/main/tools/cron_events.php
```

`CAgent::ExecuteAgents()` кеширует «до какого времени агентов нет»
(`CACHED_b_agent` = 3600), и ключ зависит от режима.
`bitrix/modules/main/include/constants.php:110-113` определяет
`BX_CRONTAB_SUPPORT = defined('BX_CRONTAB')`; `cron_events.php` определяет
`BX_CRONTAB` до `prolog_before.php`, поэтому `CAgent::OnCron()` возвращает
`true`, в SQL добавляется `IS_PERIOD='N'`, а ключ кэша становится
`agents AND IS_PERIOD='N' ` (на обычном заходе — `agents`). Пока метка этого
ключа в будущем, и демон, и ручной прогон молча ничего не делают, а чистка
`"agents"` не помогает — ключ другой. Сбросить оба ключа:

```bash
docker compose exec -T cron sh -c 'rm -rf /var/www/html/bitrix/managed_cache/MYSQL/agents/*'
```

Две оговорки по контейнеру. `docker compose logs cron` пуст: в образе нет
`/dev/log`, а Debian cron пишет в syslog, а не в stderr, поэтому признак работы
— `b_agent.LAST_EXEC` и `www/db/logs/cron.log`. И расписание считается по UTC:
`/etc/localtime` нет, `TZ` демон не наследует (зонд `date` в задании печатает
UTC при московском времени в шелле). Для `* * * * *` это неважно, для часового
расписания нужен `/etc/localtime` или `CRON_TZ`.

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
- Найденные на стенде дефекты перенесены в репозиторий (коммит `c7ed4b3`,
  origin/main) и стенд пересобран из git: `urlrewrite.php` с query-string,
  `GROUPS => [2]` в трёх формах, `PROPERTY[NAME][0]`, `CODE`/`XML_ID` у заявок
  не обязательны, почтовые события создаются миграциями 008/012. Подробности —
  в `Что сделано.txt`.
- Формы повторно проверены после пересборки реальными POST с капчей от гостя:
  заявка (`/feedback/`), бронь (`/services/observation-deck/`), заказ
  (`/books/aromatizator-v-mashinu-kniga/`). Все три отдают 302, элемент
  создаётся с автоматическим `CODE`/`XML_ID`, `?sent=1` открывает страницу
  (200), а не 404. Тестовые элементы после проверки удалены.
- Письма формируются и ставятся в очередь, но без SMTP не отправляются
  (`SUCCESS_EXEC = F`).
- Элементы, созданные формами, попадают в модерацию (`ACTIVE=N`).
- Cron работает отдельным сервисом `cron`: агенты выполняются без захода на
  сайт. Проверено при остановленном `web` — `b_agent.LAST_EXEC` сдвинулся
  с `07:17:29` на `07:48:03`, `NEXT_EXEC` — на `08:18:03` (раздел «Cron»).
- Крошки на детальных страницах: шаблон `breadcrumb/bootstrap` возвращает
  строку, поэтому из `.page-head` ушла «1», а крошки больше не выводятся до
  `<!DOCTYPE html>` (фикс шаблона, Stage 61; файлы на стенде и в
  `D:\nlbbootstrap-main` совпадают побайтово).

## Документация

Один канонический набор, остальное — история или черновик. Правила те же, что
в шаблоне (`D:\nlbbootstrap-main\AGENTS.md`, `D:\nlbbootstrap-main\docs\README.md`),
в масштабе стенда.

| Файл | Роль |
|---|---|
| `README.md` | запуск, доступы, состояние, восстановление и эта карта |
| `Что доделать.txt` | только открытые пункты стенда: P0 — блокеры, P1 — эксплуатация, P2 — развитие |
| `Что сделано.txt` | журнал: сверху свежее, формат `[ДД.ММ.ГГГГ] Тема` |
| `www/db/README.md` | документ шаблона, копия `D:\nlbbootstrap-main\db\README.md`; здесь не правится |
| `work/stand-fixes.sql`, `work/mail-events.php` | исторические обходы; на новом стенде не нужны, оставлены как справка |
| `tools/check-stand-docs.mjs`, `tools/stand-facts.php` | гейт документации и источник чисел |

Правила:

- Правило живёт в одном месте: эксплуатация — `README.md`, задачи —
  `Что доделать.txt`, история — `Что сделано.txt`. Новые правила в `.txt`
  не пишутся: `.txt` — это бэклог и журнал.
- Пункт бэклога без точного пути, команды или `file:line` не принимается.
- Закрытый пункт удаляется из бэклога целиком и уходит одной записью в журнал;
  строка с `✅` в бэклоге — ошибка гейта.
- Числа (инфоблоки, свойства, элементы, миграции, порты, версии) берутся из
  кода: `php tools/stand-facts.php --json`, а не перепечатываются из старых
  отчётов. Не сошлось — падает `node tools/check-stand-docs.mjs`.
- Снимки и чужие оценки — не правила; на них нельзя ссылаться как на источник.
- Секреты, реальные пароли и дампы БД в документацию не попадают.

Гейт проверяет: ссылки в корневых документах, карту (каждый `.md`/`.txt`
упомянут в README), отсутствие закрытых пунктов и наличие доказательства
у каждого пункта бэклога, формат и порядок записей журнала, числа против
`stand-facts.php`. Exit 0 — документация сходится с кодом.

## Восстановить стенд с нуля

1. Скачать дистрибутив «Стандарт» (`standard_encode.tar.gz`) с
   https://www.1c-bitrix.ru/download/cms.php и распаковать в `www/`.
   Дистрибутив кодирован ionCube — образ без ionCube Loader не подойдёт,
   в `php/Dockerfile` он уже установлен.
2. `docker compose up -d --build`, открыть http://localhost:8080/ и пройти мастер
   (БД: `db`, `bitrix`, `bitrix` / `bitrix_pass`), создать администратора.
3. Наложить файлы проекта: из репозитория шаблона запустить
   `tools\overlay-project.ps1 -Destination "D:\bitrix doker\www"` — он копирует
   страницы, `local/`, `db/`, `tools/`, `preview/` и не перезаписывает
   `bitrix/`, `upload/`, `.settings.php`, `dbconn.php`.
4. Выполнить миграции одной командой и привязать шаблон к сайту:
   ```bash
   docker compose exec -T -u www-data web php /var/www/html/db/run.php
   docker compose exec -T -u www-data web php /var/www/html/db/run.php --only=007 --set-template
   ```
   Повторный запуск безопасен: существующие объекты пропускаются. Шаг `007`
   без `--set-template` пропускается — он меняет настройки сайта.
5. Почтовые события создают сами миграции `008` и `012`: `CEventType::GetList()`
   получает фильтр первым аргументом, поэтому `NLB_NEW_REQUEST`,
   `NLB_NEW_BOOKING` и `NLB_BOOKING_CLIENT` появляются на чистой базе.
   `work/mail-events.php` — исторический обход для базы, испорченной старой
   версией миграции; для нового стенда он не нужен.
6. `CODE`/`XML_ID` у `requests`/`bookings`/`orders` снимает сама миграция
   `002` (`nlb_iblock_relax_code()`), в том числе при повторном запуске на
   существующей базе. `work/stand-fixes.sql` — исторический обход, не нужен.
7. Проверить данные: `docker compose exec -T -u www-data web php /var/www/html/db/check.php`
   (ожидается `OK: структура и демо-контент совпадают с db/config`).
8. Cron поднимается вместе с остальными сервисами — отдельного шага нет:
   `docker compose up -d --build` создаёт `bitrix-cron` с расписанием
   `php/crontab`. Проверка — раздел «Cron»: `docker compose logs cron` и
   `LAST_EXEC` в `b_agent` двигается без захода на сайт.

## Полезное

- CLI запускать от `www-data`: `docker compose exec -T -u www-data web php <файл>`
  (от root портятся права `db/logs` и `upload`).
- Повторные миграции — той же командой: `php db/run.php`; один шаг —
  `php db/run.php --only=003`.
- Прямой HTTP-доступ к `www/local/php_interface/` закрыт `.htaccess`;
  `/db/` и `/tools/` отдают 403 штатными `.htaccess` проекта.
