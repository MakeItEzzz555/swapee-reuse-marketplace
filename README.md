# Swapee Reuse Marketplace

Academic PHP/MySQL reuse-marketplace prototype for listing items as swaps, donations or sales. It includes account registration/login, listing management, claim/transaction actions, an impact profile and administration pages.

## Run locally

Use PHP 8 with `mysqli` and MySQL or MariaDB. Start an isolated local database, then import `sql/swapee.sql` with your database client. The script creates `swapee` and resets its tables; do not import it into a database containing work you need to keep.

Check the local connection settings in `includes/db.php`. The original root/empty-password defaults are for an isolated development database only. From the repository root:

```sh
php -S 127.0.0.1:8000 -t .
```

Open `http://127.0.0.1:8000/public/index.php`. The project root must be served so sibling assets and action scripts remain accessible. Register a fictional account for exploration, or use the explicitly labeled SQL demo accounts with the documented demo password. No real account export is included.

## Validation and scope

PHP 8.2 syntax checks passed. The schema and demo records imported into a fresh MariaDB 10.4 database; both demo password hashes validated. Browser launch, demo login, authenticated marketplace/profile access and stylesheet loading passed basic checks.

This is a local coursework prototype, not a hardened deployment. Session, CSRF and state-changing action security need review before exposure to other users. Impact numbers are demonstration estimates. All original application files are preserved; no dependency upgrade or license has been inferred. External fonts and image URLs may require internet access.
