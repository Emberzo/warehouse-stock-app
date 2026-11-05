# Warehouse stock

Stock page for checking quantities and adding or subtracting them. No login.

## What it does

Products are stored in SQLite: item code, name, colour, size, quantity.

`/stock` lists them. There is a form to add stock and a form to subtract stock. If a subtraction would go below zero, it is rejected and the quantity stays as it is.

The same actions are available as JSON:

- `GET /api/products`
- `POST /api/stock/add`
- `POST /api/stock/subtract`

Post body: `{ "item_code": "RNA1", "quantity": 5 }`

Send `Accept: application/json`. A subtraction that is too big returns 422.

Opening stock comes from `database/data/sample-inventory.txt`. A line looks like:

```text
action: Added; quantity: 10; item_code: RNA1; product_name: Mens Organic T-shirt
```

`InventoryLineParser` turns that into an array. The seeder and `php artisan inventory:import` both use the parser, then write to SQLite.

Item codes are case-sensitive. `RNA1` and `rna1` are different.

## How to run

You need PHP 8.3+, Composer, and Node.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm i
npm run build
php artisan serve
```

Open http://localhost:8000/stock.

`migrate` creates `database/database.sqlite` if it is missing. Say yes if it asks. The sqlite file is gitignored.

While changing the Vue page, run `npm run dev` instead of `npm run build`.

```bash
php artisan test
```

Extra movements, after seeding:

```bash
php artisan inventory:import database/data/adjustments.example.txt
```

Do not import `sample-inventory.txt` again. Every line in that file is an addition, so the quantities would go up a second time.

## Tech choices

Laravel 13 with SQLite, so there is no separate database server. Vue 3 and Inertia for the page. Vite builds the frontend. Tailwind came with the Laravel starter.

`StockController` handles the page and the JSON routes. Quantity changes go through `StockService`, so both sides use the same check. The parser only splits the text. `InventoryImporter` creates a missing product and then adds or subtracts.

No login. The task is the stock page.

## Challenges

Going below zero. Setting the quantity to 0 would hide a mistake, so the service rejects the request and shows how many are actually in stock.

Seed data and imports share one line format. The parser does not know what `Added` means. The importer does.

If a line in an import file fails, the whole file is rolled back.

The add and subtract forms post the same field names. They are separate Inertia forms so an error stays on the form that submitted it.

## Future improvements

A log of each stock change, search, and login if more than one person used it. MySQL or Postgres if more than one process needed to write at the same time.
