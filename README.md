# LaundryWise

**Intelligent Web-Based Laundry Time Booking and Shared-Resource Management System**

LaundryWise is a web-based prototype designed to help residents of shared accommodation view digital laundry-machine availability, make one-hour bookings, manage their reservations, and receive recommendations for alternative time slots when their preferred slot is unavailable.

> **Project scope:** LaundryWise works with digital booking records. It does not use IoT sensors, control physical washing machines, guarantee that a machine is physically empty, or measure an actual queue. Its recommendations are estimates based on historical booking patterns and the prototype's machine/time-slot records.

## Contents

- [Project goals](#project-goals)
- [Features](#features)
- [Recommendation system](#recommendation-system)
- [Technology stack](#technology-stack)
- [Requirements](#requirements)
- [Installation and setup](#installation-and-setup)
- [Configuration](#configuration)
- [Typical resident workflow](#typical-resident-workflow)
- [Project structure](#project-structure)
- [Testing](#testing)
- [Limitations](#limitations)
- [Security and privacy](#security-and-privacy)
- [Academic project](#academic-project)

## Project goals

LaundryWise aims to:

- Make digital laundry booking information easier for residents to check.
- Allow residents to request a machine and a one-hour time slot.
- Check booking conflicts before confirming a reservation.
- Suggest alternative machines or time slots when the requested option is unavailable.
- Provide resident and administrator workflows for managing accounts and booking information.
- Explore how historical booking patterns can support availability recommendations.

## Features

### Resident features

- Register an account and submit it for administrator approval.
- Receive a generated Resident ID after approval, subject to the configured email service.
- Log in using the application's configured resident credentials.
- View the resident dashboard and digital machine availability.
- Search for a preferred date, machine, and start time.
- Request one-hour booking slots within the configured opening hours.
- View recommendations when a requested slot conflicts with an existing confirmed booking.
- View personal reservations on **My Bookings**.
- Cancel eligible future confirmed bookings.
- Manage account settings and email-notification preferences.
- Access Booking Help and Policies & Rules pages.
- Use the floating LaundryWise assistant for help with system features.

### Administrator features

- Review resident registration requests.
- Approve resident registrations and support the Resident ID/account workflow.
- View resident and booking information.
- Manage machine records/status and digital availability information.
- Manage relevant system settings and email notifications, according to the implemented admin pages.

### Booking rules

- A booking represents a one-hour digital time slot.
- The configured operating window is 08:00–22:00; the latest valid start time is 21:00.
- The system checks confirmed bookings for overlapping intervals before saving.
- A past date or a start time that has already passed today should not be accepted.
- Recommendations are suggestions only. A resident must choose an option, and the booking is checked again before confirmation.
- A booking record does not prove that the physical machine is empty or that no resident is waiting.

## Recommendation system

LaundryWise includes a machine-learning-based booking-count prediction and recommendation prototype.

### Model approach

- The model uses calendar and historical booking features, including time/day information and previous booking counts.
- The tested model family includes Decision Tree, Random Forest, and Gradient Boosting.
- Gradient Boosting was selected from the tested models based on the reported evaluation results.
- The predicted booking count is used as an estimate for ranking available alternatives. Lower predicted booking counts are interpreted as **higher estimated availability**; they are not a measurement of a physical queue.
- The system first considers digitally available candidate slots, then ranks alternatives by time proximity and estimated availability.

### Reported prototype evaluation

The model was evaluated using simulated booking data. Reported test metrics:

| Model | MAE | RMSE | R² |
|---|---:|---:|---:|
| Decision Tree | 1.373 | 1.789 | 0.341 |
| Random Forest — historical features | 1.356 | 1.764 | 0.359 |
| Random Forest — calendar features only | 1.356 | 1.767 | 0.357 |
| Gradient Boosting | 1.340 | 1.745 | 0.373 |

These results describe the prototype's performance on simulated data. They do **not** establish real resident demand, real queue length, or a measured reduction in waiting time. Evaluation with real booking records would be needed for those conclusions.

## Technology stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Database administration:** phpMyAdmin
- **Local development environment:** XAMPP (Apache and MySQL)
- **Machine-learning prototype:** Python and scikit-learn; model prediction integrated with the PHP application through the project's prediction files/interface
- **Email:** PHPMailer with SMTP configuration, where enabled

## Requirements

Install and configure:

- XAMPP with Apache, PHP, and MySQL
- phpMyAdmin (usually included with XAMPP)
- A web browser
- Python and the project's required Python packages, if running or rebuilding the ML predictor
- Composer/PHPMailer dependencies, if they are not already included in the project
- SMTP credentials, only if you want to test registration or notification emails

Exact PHP/Python package versions depend on the versions used in the project files. Check the installed dependencies and configuration before deployment.

## Installation and setup

1. **Download or clone the repository** into your local machine.
2. Place the LaundryWise project folder inside XAMPP's `htdocs` directory. For example:

   ```text
   C:\xampp\htdocs\LaundryWise
   ```

3. Start **Apache** and **MySQL** from the XAMPP Control Panel.
4. Open phpMyAdmin, create the database expected by the project configuration, and import the project's database schema. If a schema file is included in the repository, use that file. Do not import a database dump containing real resident data or credentials into a public repository.
5. Configure the database connection in the local configuration file (see [Configuration](#configuration)).
6. Configure SMTP only if email functionality is required.
7. If the recommendation predictor requires Python, install the project's Python dependencies and verify the model/predictor files are in their expected locations.
8. Open the project in your browser. For a folder named `LaundryWise`, the local URL is typically:

   ```text
   http://localhost/LaundryWise/
   ```

9. Register a resident account and use the administrator approval workflow, or use a local test account created safely for development.
10. Test booking, conflict handling, recommendations, cancellation, and notifications before using the application with real residents.

> The exact database name, schema filename, admin-account setup, and Python command depend on the files included in the repository. Follow the project's actual configuration rather than assuming the example values above.

## Configuration

### Database

Set the local database host, database name, username, and password in the project's database configuration file. Keep the real configuration local and do not commit it.

### Email / SMTP

If registration approval or booking reminders use SMTP, configure the mail host, port, sender, username, and application-specific password in the local mail configuration. Never commit SMTP passwords, Gmail App Passwords, or other secrets.

### Machine-learning predictor

Ensure the trained model and prediction interface are present at the paths expected by the PHP application. The predictor should receive the expected calendar and historical features and return a booking-count estimate. If the model is unavailable, test the rest of the application separately and do not describe the recommendation feature as operational until it has been verified.

## Typical resident workflow

1. Resident submits registration details.
2. Administrator reviews and approves the request.
3. The system creates/activates the resident account and generates a Resident ID according to the configured workflow.
4. Resident logs in and opens the dashboard.
5. Resident selects a machine, date, and preferred start time.
6. LaundryWise checks digital booking conflicts and the booking rules.
7. If the requested slot is unavailable, the system presents alternatives when available.
8. Resident chooses an option; the system checks availability again and confirms the booking.
9. Resident can review or cancel eligible bookings from My Bookings.

## Project structure

The repository may contain files and folders such as:

```text
LaundryWise/
├── config/                 # Local database and mail configuration
├── css/                    # Application stylesheets
├── database/                # Schema or database-related files, if included
├── dashboard.php             # Resident dashboard and slot search
├── availability.php          # Availability view/search
├── book.php                 # Booking validation and confirmation
├── recommendations.php       # Alternative slot recommendations
├── my_bookings.php          # Resident booking list and cancellation links
├── booking_help.php         # Booking guidance
├── policies.php             # Policies and rules
├── settings.php             # Resident account/settings page
├── admin/                   # Administrator pages, if organized in this folder
├── assistant.php             # Assistant endpoint/page, if included
└── README.md
```

This is an illustrative overview. The exact structure may differ depending on the current repository version. Keep the README synchronized with the files actually committed.

## Testing

Before pushing or presenting the project, check:

- Resident registration and administrator approval
- Resident ID generation and login
- Dashboard and navigation links
- Valid one-hour booking
- Rejection of overlapping confirmed bookings
- Rejection of past dates and past start times
- Alternative recommendations when a requested slot is unavailable
- Rechecking availability before booking confirmation
- My Bookings list and cancellation behavior
- Email notifications, if SMTP is configured
- ML predictor output and recommendation integration
- PHP syntax and browser errors

Use test accounts and synthetic data. Do not test by exposing real residents' personal information.

## Limitations

- This is a web-based prototype using digital records; it is not connected to physical machine sensors.
- It cannot confirm whether a machine is physically occupied, whether a wash cycle has started, or whether a queue exists.
- The ML evaluation uses simulated booking data and should not be generalized to actual residents without real-world evaluation.
- Recommendations do not reserve a slot automatically; residents must select an option and pass the final availability check.
- Email functionality depends on valid local SMTP configuration and network access.
- Local XAMPP setup is intended for development/testing; production deployment requires additional security, hosting, backup, and privacy review.

## Security and privacy

- Do not commit `config/database.php`, `config/mail.php`, `.env` files, SMTP passwords, API keys, or other secrets.
- Do not commit real resident records, password hashes, session files, or database exports containing personal information.
- Keep local configuration files out of Git and provide a safe example configuration if needed.
- Use password hashing and server-side session checks as implemented by the application.
- Restrict administrator access and test authorization checks.
- Before deployment, review input validation, CSRF protection, session security, error reporting, database permissions, and data-retention practices.

## Academic project

LaundryWise is developed as a BSc Computer Science project exploring shared-resource booking and machine-learning-supported availability recommendations in residential accommodation. The prototype and its simulated-data evaluation should be interpreted within the scope and limitations described above.

---

**Maintainer:** Project author

**Status:** Academic prototype / local development
