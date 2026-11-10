<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">



    <title>LaundryWise | Smart Laundry Booking</title>



        <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --lavender: #F0EDFF;
            --lavender-hover: #E2D9FF;
            --purple: #51418B;
            --deep-purple: #35256F;
            --text: #28243A;
            --muted: #716D83;
            --border: #E4DFF4;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--lavender);
            color: var(--text);
            line-height: 1.6;
        }

        /* Header */
        header {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 26px;
            font-weight: bold;
            color: var(--purple);
            text-decoration: none;
        }

        nav {
            display: flex;
            gap: 12px;
        }

        nav a {
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 7px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .login-btn {
            color: var(--purple);
            border: 1px solid var(--purple);
            background: #ffffff;
        }

        .login-btn:hover {
            background: var(--lavender);
        }

        .register-btn {
            color: #ffffff;
            background: var(--purple);
        }

        .register-btn:hover,
        .hero-button:hover {
            background: var(--deep-purple);
        }

        /* Hero */
        .hero {
            text-align: center;
            padding: 75px 20px 65px;
            background: #ffffff;
        }

        .hero h1 {
            font-size: 46px;
            margin-bottom: 15px;
            color: #28213F;
        }

        .hero h1 span {
            color: var(--purple);
        }

        .hero p {
            max-width: 650px;
            margin: 0 auto 28px;
            color: var(--muted);
            font-size: 17px;
        }

        .hero-button {
            display: inline-block;
            background: var(--purple);
            color: #ffffff;
            text-decoration: none;
            padding: 13px 26px;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        /* Features */
        .features {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 55px 20px 70px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 35px;
        }

        .section-title h2 {
            font-size: 30px;
            color: var(--deep-purple);
            margin-bottom: 8px;
        }

        .section-title p {
            color: var(--muted);
        }

        .feature-grid {
            display: grid;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            align-items: stretch;
            justify-content: center;
        }

        .feature-grid > .feature-card {
            width: 100%;
            min-width: 0;
        }

        .feature-card {
            min-width: 0;
            min-height: 300px;
            background: #ffffff;
            padding: 30px 24px;
            border-radius: 12px;
            border: 1px solid var(--border);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(53, 37, 111, 0.10);
        }

        .feature-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 16px;
            background: var(--lavender);
            color: var(--purple);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }

        .feature-card h3 {
            font-size: 18px;
            margin-bottom: 9px;
            color: var(--deep-purple);
        }

        .feature-card p {
            width: 100%;
            max-width: 300px;
            margin: 18px auto 0;
            font-size: 14px;
            color: var(--muted);
            overflow-wrap: break-word;
        }

        /* How it works */
        .how-it-works {
            background: #ffffff;
            padding: 55px 20px;
            text-align: center;
        }

        .steps {
            display: grid;
            width: 100%;
            max-width: 900px;
            margin: 35px auto 0;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: start;
            justify-content: center;
            gap: 24px;
        }

        .steps > .step {
            width: 100%;
            min-width: 0;
        }

        .step {
            min-width: 0;
            padding: 18px 20px;
            text-align: center;
        }

        .step h3 {
            margin-top: 0;
        }

        .step p {
            max-width: 260px;
            margin: 14px auto 0;
            overflow-wrap: break-word;
        }

        .step-number {
            width: 40px;
            height: 40px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: var(--purple);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .step h3 {
            font-size: 17px;
            margin-bottom: 7px;
            color: var(--deep-purple);
        }

        .step p {
            font-size: 14px;
            color: var(--muted);
        }

        /* Footer */
        footer {
            background: var(--deep-purple);
            color: #ffffff;
            padding: 35px 7% 20px;
        }

        .footer-content {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            gap: 50px;
        }

        .footer-section {
            max-width: 480px;
        }

        .footer-section h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }

        .footer-section p {
            color: #E2DDF0;
            font-size: 14px;
        }

        .footer-section a {
            color: #E2D9FF !important;
            text-decoration: none;
        }

        .footer-section a:hover {
            color: #ffffff !important;
            text-decoration: underline;
        }

        .footer-bottom {
            max-width: 1100px;
            margin: 25px auto 0;
            padding-top: 18px;
            border-top: 1px solid #51418B;
            text-align: center;
            color: #D0C8E7;
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 850px) {
            .feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .steps {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .footer-content {
                flex-direction: column;
            }
        }

        @media (max-width: 600px) {
            header {
                padding: 15px 5%;
                gap: 12px;
            }

            nav {
                gap: 8px;
            }

            .logo {
                font-size: 22px;
            }

            nav a {
                padding: 8px 11px;
                font-size: 13px;
            }

            .hero {
                padding: 55px 20px;
            }

            .hero h1 {
                font-size: 36px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .steps {
                grid-template-columns: 1fr;
            }

            .feature-card {
                min-height: 0;
            }
        }
    </style>

</head>



<body>



    <!-- Header -->

    <header>



        <a href="index.php" class="logo">

            LaundryWise

        </a>



        <nav>

            <a href="login.php" class="login-btn">

                Login

            </a>



            <a href="register.php" class="register-btn">

                Register

            </a>

        </nav>



    </header>





    <!-- Welcome -->

    <section class="hero">



        <h1>

            Welcome to <span>LaundryWise</span>

        </h1>



        <p>

            A smarter way to manage shared laundry facilities.

            Check machine availability, reserve a suitable time slot,

            manage your bookings and receive intelligent alternatives

            when your preferred option is unavailable.

        </p>



        <a href="login.php" class="hero-button">

            Get Started

        </a>



    </section>





    <!-- Facilities -->

    <section class="features">



        <div class="section-title">



            <h2>

                What LaundryWise Offers

            </h2>



            <p>

                Simple tools for convenient shared laundry management.

            </p>



        </div>





        <div class="feature-grid">



            <div class="feature-card">



                <div class="feature-icon">

                    ✓

                </div>



                <h3>

                    Check Availability

                </h3>



                <p>

                    View available washing machines and dryers

                    and check available time slots before making

                    a reservation.

                </p>



            </div>





            <div class="feature-card">



                <div class="feature-icon">

                    B

                </div>



                <h3>

                    Easy Booking

                </h3>



                <p>

                    Select an available machine and time slot

                    and make your laundry reservation online.

                </p>



            </div>





            <div class="feature-card">



                <div class="feature-icon">

                    ↻

                </div>



                <h3>

                    Manage Bookings

                </h3>



                <p>

                    View your existing bookings and cancel

                    confirmed bookings when necessary.

                </p>



            </div>





            <div class="feature-card">



                <div class="feature-icon">

                    AI

                </div>



                <h3>

                    Smart Alternatives

                </h3>



                <p>

                    When your requested option is unavailable,

                    LaundryWise provides alternative machines

                    and time slots using historical demand

                    information.

                </p>



            </div>



        </div>



    </section>





    <!-- How It Works -->

    <section class="how-it-works">



        <div class="section-title">



            <h2>

                How It Works

            </h2>



            <p>

                Manage your laundry booking in three simple steps.

            </p>



        </div>





        <div class="steps">



            <div class="step">



                <div class="step-number">

                    1

                </div>



                <h3>

                    Check

                </h3>



                <p>

                    Check the availability of machines and

                    time slots.

                </p>



            </div>





            <div class="step">



                <div class="step-number">

                    2

                </div>



                <h3>

                    Book

                </h3>



                <p>

                    Select an available machine and time slot

                    and confirm your booking.

                </p>



            </div>





            <div class="step">



                <div class="step-number">

                    3

                </div>



                <h3>

                    Choose an Alternative

                </h3>



                <p>

                    If your preferred option is unavailable,

                    review the recommended alternatives.

                </p>



            </div>



        </div>



    </section>





    <!-- Footer -->

    <footer>



        <div class="footer-content">



            <div class="footer-section">



                <h3>

                    LaundryWise

                </h3>



                <p>

                    Intelligent web-based laundry booking and

                    shared-resource management for residential

                    laundry facilities.

                </p>



            </div>





            <div class="footer-section">



                <h3>

                    Contact Management

                </h3>



                <p>

    For account, booking or machine-related issues,

    please contact the LaundryWise administration.

    You can also email us at:

    <a href="mailto:info@thedailycampus.com"

       style="color: #93c5fd; text-decoration: none;">

        info@thedailycampus.com

    </a>

</p>



                <br>



                <h3>

                    Office Location

                </h3>



                <p>

                    The Daily Campus<br>

                    2nd Floor, Hasan Holdings<br>

                    52/1 New Eskaton Road<br>

                    Dhaka 1000, Bangladesh

                </p>



            </div>



        </div>





        <div class="footer-bottom">



            &copy; 2026 LaundryWise. All rights reserved.



        </div>



    </footer>



</body>

</html>
