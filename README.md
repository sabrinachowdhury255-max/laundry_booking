# LaundryWise

**Intelligent Web-Based Laundry Time Booking and Shared-Resource Management System**

LaundryWise is a PHP/MySQL web application prototype for residents of shared accommodation. Residents can view digital laundry-machine availability, request one-hour bookings, manage their bookings, and receive suggested alternatives when a requested slot is unavailable. Administrators can review resident registrations and manage resident, booking, and machine information.

> **Scope:** The application uses digital booking records. It is not connected to physical machine sensors, does not control washing machines, and cannot confirm physical occupancy or queues. Recommendations are estimates based on the application's booking records and trained model.

## Features

### Resident
- Register and submit an account for administrator approval.
- Log in and access the resident dashboard.
- View/search digital machine availability by date and time.
- Request one-hour booking slots.
- Receive alternative machine/time-slot recommendations when a requested slot conflicts.
- View and cancel eligible bookings through **My Bookings**.
- Manage account settings and notification preferences.
- Access booking help, policies, and the LaundryWise assistant.

### Administrator
- Review and approve resident registration requests.
- View resident and booking information.
- Manage machine records/status and digital availability.
- Access administrator dashboard and relevant settings.

### Booking rules
- Each booking is a one-hour slot.
- Operating hours are configured as 08:00–22:00; the latest start time is 21:00.
- The application checks confirmed booking overlaps before saving.
- Past dates and elapsed start times should not be selected.
- Recommendations are advisory. The resident selects an alternative, and availability is checked again before confirmation.

## Technology

- PHP
- MySQL / MariaDB
- HTML, CSS, JavaScript
- XAMPP (Apache and MySQL)
- phpMyAdmin
- PHPMailer (SMTP email, when configured)
- PHP implementation of the Gradient Boosting predictor, using the included model JSON files

## Requirements

Install:
- XAMPP with Apache, MySQL/MariaDB, and PHP
- A modern web browser
- Composer only if you need to install/update the PHPMailer dependency

The project database configuration currently uses `localhost`, MySQL username `root`, an empty password, and database name `laundry_booking`. These are common local XAMPP defaults, not safe production credentials.

## Installation and setup (Windows / XAMPP)

1. **Install XAMPP.** Start the XAMPP Control Panel.
2. **Extract the project.** Copy the `laundry_booking` folder into XAMPP's `htdocs` directory. For example:

   ```text
   C:\xampp\htdocs\laundry_booking
   ```

   Make sure `README.md`, `index.php`, `config/`, `database/`, and `ml/` are directly inside that folder—not nested in an extra folder.

3. **Start services.** In XAMPP Control Panel, start **Apache** and **MySQL**.
4. **Create the database.** Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin), create a database named:

   ```text
   laundry_booking
   ```

   Use the same name and spelling as `config/database.php`.

5. **Import the schema.** Select the `laundry_booking` database in phpMyAdmin, choose **Import**, select:

   ```text
   database/laundry_booking (5).sql
   ```

   and run the import. The included SQL file contains database structure and sample records. Use it only for local testing; do not expose sample or personal records publicly.

6. **Check the database connection.** Open `config/database.php`. Its current local settings are:

   ```php
   $host = "localhost";
   $username = "root";
   $password = "";
   $database = "laundry_booking";
   ```

   If your MySQL username, password, or database name differs, update these values for your local environment. Do not commit real passwords.

7. **Check Composer dependencies.** PHPMailer is declared in `composer.json`. The project archive includes `vendor/`. If the dependency is missing or you need to reinstall it, open Command Prompt in the project folder and run:

   ```bash
   composer install
   ```

   If Composer is not installed, install it first or use the included `vendor/` folder when it is present.

8. **Open LaundryWise.** In your browser, visit:

   [http://localhost/laundry_booking/](http://localhost/laundry_booking/)

9. **Register and test.** Submit a resident registration and use the administrator approval flow. Use only local test accounts. Administrator access depends on the accounts/roles configured in the imported database and application; do not assume a default admin password.

## Email configuration (optional)

Registration and reminder emails require a working SMTP configuration. Review `config/mail.php` and configure the mail host, port, sender, username, and password for your own test account. Email functionality may not work until valid SMTP credentials are provided.

**Never commit SMTP passwords, app passwords, API keys, or other secrets.** Keep real credentials in your local configuration and remove them before sharing the project.

## Using the application

### Resident workflow
1. Open LaundryWise and submit the registration form.
2. Wait for administrator approval.
3. Log in with the approved account.
4. Open the dashboard and select the preferred date, machine, and start time.
5. Review the availability result. If the requested slot conflicts with another confirmed booking, review the suggested alternatives.
6. Select an alternative if appropriate. The system checks availability again before confirming the booking.
7. Open **My Bookings** to review or cancel an eligible future booking.

### Administrator workflow
1. Log in with an administrator account configured for the local application.
2. Open the administrator dashboard.
3. Review resident registration requests and approve or reject them as appropriate.
4. Review resident and booking information.
5. Manage machine records/status and related digital availability information.

## Machine-learning recommendation prototype

The application includes `ml/GradientBoostingPredictor.php` and trained model JSON files. The recommendation feature uses predicted booking counts to help rank digitally available alternatives. A lower predicted booking count is treated as a higher estimated availability; it is not a physical queue measurement.

The project also includes a simulated dataset and notebook under `ml/` for the model work. The PHP application uses the included predictor/model files; Python is not required for ordinary local use of the already-integrated recommendation feature. Python and the notebook's dependencies are needed only if you want to inspect or rebuild the model.

Reported evaluation on simulated data:

| Model | MAE | RMSE | R² |
|---|---:|---:|---:|
| Decision Tree | 1.373 | 1.789 | 0.341 |
| Random Forest — historical features | 1.356 | 1.764 | 0.359 |
| Random Forest — calendar features only | 1.356 | 1.767 | 0.357 |
| Gradient Boosting | 1.340 | 1.745 | 0.373 |

These metrics describe evaluation on simulated data only. They do not establish actual resident demand, physical queue length, or a real-world reduction in waiting time.

## Project structure

```text
laundry_booking/
├── config/
│   ├── database.php                 # MySQL connection
│   └── mail.php                     # SMTP configuration
├── css/                              # Application stylesheets
├── database/
│   └── laundry_booking (5).sql        # Database schema and sample records
├── ml/
│   ├── GradientBoostingPredictor.php  # PHP model predictor
│   ├── gradient_boosting_model.json   # Model data
│   ├── gradient_boosting_model_php.json
│   ├── laundry_booking_simulated_dataset.csv
│   └── laundry-booking (4).ipynb      # Model notebook
├── index.php                          # Entry page
├── register.php                        # Resident registration
├── login.php                           # Login
├── dashboard.php                       # Resident dashboard
├── availability.php                    # Availability search
├── book.php                            # Booking request
├── recommendations.php                  # Alternative recommendations
├── my_bookings.php                     # Resident bookings
├── admin_dashboard.php                 # Admin dashboard
├── admin_registration_requests.php      # Registration approvals
├── admin_residents.php                 # Resident management
├── admin_bookings.php                  # Booking management
├── admin_machines.php                  # Machine management
├── assistant.php                       # LaundryWise assistant
└── README.md
```

This list highlights the main files; additional PHP pages, tests, and dependencies are included in the project.

## Testing

For local testing, check:
- Registration submission and administrator approval
- Login and resident dashboard access
- Availability search
- Successful one-hour booking
- Rejection of overlapping bookings and invalid/past times
- Alternative recommendations for unavailable slots
- Availability recheck before confirmation
- Booking list and cancellation
- Administrator resident, booking, and machine pages
- Email notifications after SMTP is configured
- Recommendation predictor and model files

The project includes `system_test.php` and `test_ml.php` for application/model checks. Run them through the local PHP environment as appropriate, for example:

```text
http://localhost/laundry_booking/system_test.php
http://localhost/laundry_booking/test_ml.php
```

These test pages should be used locally only. Do not leave diagnostic/test pages publicly accessible in a production deployment.

## Limitations

- LaundryWise is a web prototype based on digital records, not physical machine sensors.
- It cannot verify whether a machine is physically occupied, whether a wash cycle has started, or whether residents are waiting.
- The ML model was evaluated with simulated booking data; real resident data and formal real-world evaluation are not included in those results.
- Recommendations are advisory and do not automatically reserve a slot.
- Email depends on SMTP configuration.
- XAMPP setup is for local development/testing. Public deployment requires additional security, privacy, hosting, and backup work.

## Security and privacy

- Do not upload real resident information, database exports, passwords, SMTP credentials, or secret configuration to GitHub.
- Use local test accounts and synthetic data.
- Restrict administrator access.
- Do not use the default XAMPP `root`/empty-password setup on a public server.
- Disable or remove test/diagnostic pages before public deployment.
- Review authentication, authorization, input validation, session security, and data protection before deployment.

## Academic project

LaundryWise is a BSc Computer Science project exploring shared-resource booking and machine-learning-supported recommendations. Its prototype and simulated-data results should be interpreted within the scope and limitations described above.

**Status:** Academic prototype / local XAMPP development
