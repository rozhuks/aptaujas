# Aptaujas veidotājs

## 1. Projekta ideja

Projekta ideja **Aptaujas veidotājs un rezultātu vizualizators**.

Lietotājs var izveidot aptauju, nosūtīt to citiem ar saiti, apskatīt rezultātus diagrammās un lejupielādēt tos CSV formātā.

Jautājumu veidi: teksts, viena atbilde, vairākas atbildes, skala 1-5.

## 2. Izmantotie resursi

| Resurss | Kāpēc un kā izmantots |
|---|---|
| [Chart.js](https://www.chartjs.org/docs/latest/charts/bar.html) | Rezultātu diagrammām. Pielāgots: viena krāsa, bez leģendas, tikai veseli skaitļi (`assets/script.js`). |
| [Google Fonts](https://fonts.google.com) | Google Sans fonts, kā dizainā. |
| [PHP PDO](https://www.php.net/manual/en/pdo.prepared-statements.php) | Drošs savienojums ar datubāzi (`data/db.php`). |
| [password_hash](https://www.php.net/manual/en/function.password-hash.php) | Paroles tiek glabātas šifrētā veidā (`data/db.php`). |
| [filter_var](https://www.php.net/manual/en/filter.filters.validate.php) | E-pasta un skaitļu pārbaudei (`logic/logic.php`). |
| [fputcsv](https://www.php.net/manual/en/function.fputcsv.php) | CSV faila izveidei (`logic/logic.php`). |

## 3. Validācija

Dati tiek pārbaudīti pārlūkā (HTML) un serverī (`logic/logic.php`).

| Kas tiek pārbaudīts | Noteikums | Standarts / pamatojums |
|---|---|---|
| E-pasts | Pareizs formāts, līdz 254 simboliem | RFC 5322, RFC 5321 |
| Viens e-pasts | Var aizpildīt aptauju tikai vienu reizi | Novērš atkārtotu balsošanu |
| Termiņš | Nav pagātnē, ne tālāk par 1 gadu | ISO 8601 datuma formāts |
| Nosaukums | 3–100 simboli | Datubāzes lauka garums |
| Jautājums | 3–255 simboli | Datubāzes lauka garums |
| Atbilžu varianti | 2–10 varianti | Izvēlei vajag vismaz 2 |
| Skala | Skaitlis no 1 līdz 5 | `FILTER_VALIDATE_INT` |
| Parole | 8–64 simboli | NIST SP 800-63B |
| Rediģēšana un dzēšana | Tikai, ja nav atbilžu | Prasīts no uzdevuma |
| CSV fails | Pareizs formāts | RFC 4180 |
| Personas dati | Vāc tikai e-pastu, rezultātus redz tikai autors | VDAR (GDPR) |

## 4. Struktūra

```
aptaujas/
├── data/          datubāzes dati un SQL vaicājumi
├── logic/         validācija, pieteikšanās, rezultāti
├── views/         lapas galvene un kājene
├── assets/        CSS, JavaScript, logo
├── *.php          lapas (index, login, register, edit, fill, results)
└── database.sql   tabulu izveide
```

## 5. Palaišana

1. Ieslēdz XAMPP (Apache un MySQL).
2. Iekopē mapi `aptaujas` mapē `C:\xampp\htdocs\`.
3. HeidiSQL vai phpMyAdmin izveido datubāzi `aptaujas` un palaid `database.sql`.
4. Failā `data/config.php` ieraksti savus datubāzes datus.
5. Atver `http://localhost/aptaujas/`.