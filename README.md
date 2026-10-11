# Cocoon Vietnam — Shopping Cart & Order Backend

This project is a PHP/MySQL storefront intended to run locally with XAMPP. The shopping cart and checkout flow now use PHP sessions and server-side product data rather than trusting prices stored in the browser.

## Requirements

- XAMPP with Apache, PHP 8.1+ and MySQL/MariaDB.
- PHP extensions `pdo_mysql` and `mysqli` enabled in the XAMPP PHP configuration.
- A browser with JavaScript enabled.

## Run locally

1. Copy the `cocoon-website-main` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL from XAMPP Control Panel.
3. In phpMyAdmin, create a database named `cocoon_db`, select that database, then import `cocoon_db.sql`. The SQL file defines the tables and sample data but does not create the database itself. Do not import it a second time into a database that already contains these tables and records.
4. If your local MySQL settings differ from the XAMPP defaults, set these environment variables for Apache/PHP before starting the server:
   - `COCOON_DB_HOST` (default `localhost`)
   - `COCOON_DB_NAME` (default `cocoon_db`)
   - `COCOON_DB_USER` (default `root`)
   - `COCOON_DB_PASSWORD` (default empty string)
5. Open `http://localhost/cocoon-website-main/`.

The existing MySQLi connection used by older pages is left in place. The new cart and order backend uses the shared PDO connection in `backend/pdo_connect.php`.

## Shopping cart API

All cart mutations require a valid CSRF token from the current PHP session. The website's JavaScript client passes this token automatically.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `backend/cart/cart_get.php` | Get the current session cart, current database prices, stock, line totals and subtotal |
| `POST` | `backend/cart/cart_add.php` | Add quantity of a product |
| `POST` | `backend/cart/cart_update.php` | Update an existing item quantity |
| `POST` | `backend/cart/cart_remove.php` | Remove one item |
| `POST` | `backend/cart/cart_clear.php` | Clear the cart |
| `POST` | `backend/cart/cart_import.php` | One-time validated migration from the previous browser-stored cart |
| `POST` | `backend/orders/orders_create.php` | Validate checkout and create an order |

POST cart API requests accept JSON with the `X-CSRF-Token` header. Typical request body examples:

```json
{"product_id": 1, "quantity": 2}
```

For quantity updates:

```json
{"product_id": 1, "quantity": 3}
```

The endpoints return JSON in the form `{"success": true, "message": "...", "data": {...}}`. Prices, product names, stock, line totals and subtotal are obtained or calculated on the server.

## Order processing behavior

- Guest checkout is supported. Logged-in customers must choose one of their own saved addresses.
- The backend validates the CSRF token, one-time checkout token, shipping data, payment method and session cart.
- Product rows are locked with `SELECT ... FOR UPDATE` inside a transaction before current prices and stock are rechecked.
- A successful checkout inserts one row into `orders`, inserts one `order_items` row per product with the real `product_id` and purchase-time price, and decrements stock before committing.
- If any step fails, the transaction is rolled back and the cart is preserved.
- After a successful commit, the session cart is cleared and the checkout token is rotated to reduce duplicate submissions.
- Bank transfer is only recorded as the selected payment method. This project does not integrate or verify a real bank/payment gateway.

No database migration is required for this implementation. It uses the existing `products.stock`, `orders`, and `order_items` columns defined in `cocoon_db.sql`.

## Manual test checklist

1. Open `products.php`, add one product, then add the same product again. Check that the cart count increases by two and only one row for that product is shown in the cart.
2. Open `cart.php`, update the quantity and confirm the subtotal changes based on database prices.
3. Try a quantity above the product's stock; the backend should reject it.
4. Proceed to checkout as a guest and place an order with valid delivery details.
5. In phpMyAdmin, verify `orders.total_amount`, `order_items.product_id`, `order_items.price`, `order_items.quantity`, and the reduced `products.stock`.
6. Confirm that the order summary page displays the newly created order number.
7. Try submitting an empty cart or re-submitting a previous checkout token; it must not create another order.
8. Log in and verify the checkout address selector only accepts addresses owned by the current account.
9. Open `backend/orders/admin_ords.php` and confirm the new order appears.

## Notes / limitations

- The cart is session-based; it is not a persistent database cart and can expire when the PHP session expires.
- Stock is deducted when an order is successfully created. If cancellation/refund workflows are added later, they should restore stock transactionally when appropriate.
- Run the project through Apache/XAMPP rather than opening PHP files directly from the filesystem.
