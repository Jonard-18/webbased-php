<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../auth/Login.php");
    exit();
}
include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU-RESERVE Student Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-red: #8B0000;
            --accent-yellow: #FFD700;
            --light-gray: #f5f5f5;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: var(--light-gray);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--primary-red);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            color: var(--accent-yellow);
            text-align: center;
            padding: 15px;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-button {
            display: block;
            width: 85%;
            margin: 10px auto;
            padding: 12px 15px;
            background-color: var(--accent-yellow);
            border: none;
            border-radius: 5px;
            text-align: left;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .nav-button:hover {
            background-color: #FFC500;
            transform: translateX(5px);
        }

        /* Main Content Styles */
        /* Main Content Styles */
        .main-content {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
            background-color: var(--light-gray);
            overflow: auto;
        }

        .section-title {
            font-size: 1.75rem;
            color: var(--primary-red);
            margin-bottom: 20px;
            font-weight: 700;
            text-align: center;
        }

        /* Responsive Section Cards */
        .support-section {
            background-color: var(--secondary-bg);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }

        .faq-item h5 {
            font-weight: bold;
            color: var(--primary-red);
        }

        .faq-item p {
            font-size: 1rem;
            color: #555;
            margin-bottom: 10px;
        }

        /* Contact Card */
        .contact-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }


    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">EVSU-RESERVE - STUDENT</div>
            <a href="dashboard.php" class="nav-button"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="inventory.php" class="nav-button"><i class="fas fa-box"></i> Inventory</a>
            <a href="reservation.php" class="nav-button"><i class="fas fa-calendar-alt"></i> My Reservation</a>
            <a href="payment_history.php" class="nav-button"><i class="fas fa-money-bill-wave"></i> Payment History</a>
            <a href="support.php" class="nav-button"><i class="fas fa-headset"></i> Support</a>
            <a href="../auth/Logout.php" class="nav-button" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Exit</a>
        </div>

        <!-- Main Content -->
        <!-- Main Content -->
        <div class="main-content">
            <!-- Help Center Section -->
            <div class="support-section">
                <h2 class="section-title">Help Center</h2>
                <p class="text-muted">Welcome to the Help Center! Here, you’ll find resources and guidance on using our
                    inventory and reservation system.</p>
            </div>

            <div class="row">
                <!-- FAQ Section -->
                <div class="col-md-8">
                    <div class="support-section">
                        <h2 class="section-title">Frequently Asked Questions (FAQs)</h2>

                        <div class="accordion" id="faqAccordion">

                            <!-- FAQ 1 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        How do I make a reservation?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show"
                                    aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        To reserve an item, go to the Inventory page, select the item, and click
                                        “Reserve.” Fill out the necessary details and submit.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 2 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        What is the cancellation policy for reservations?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Reservations can be canceled before the scheduled date. Go to My Reservations,
                                        select your reservation, and choose “Cancel.”
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 3 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseThree" aria-expanded="false"
                                        aria-controls="collapseThree">
                                        Can I modify a reservation after it’s made?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse"
                                    aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Currently, modifications are not allowed. You can cancel and make a new
                                        reservation if necessary.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 4 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseFour" aria-expanded="false"
                                        aria-controls="collapseFour">
                                        Who can I contact for additional help?
                                    </button>
                                </h2>
                                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Please check the Contact Us section below for more support details.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 5 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseFive" aria-expanded="false"
                                        aria-controls="collapseFive">
                                        Is there a fee for making a reservation?
                                    </button>
                                </h2>
                                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        No, reservations are free. However, additional fees may apply depending on the
                                        item reserved and the duration.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 6 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingSix">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                        How far in advance can I reserve an item?
                                    </button>
                                </h2>
                                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Reservations can typically be made up to 30 days in advance. For special items
                                        or high-demand periods, please check specific availability.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 7 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingSeven">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseSeven" aria-expanded="false"
                                        aria-controls="collapseSeven">
                                        What happens if I don't pick up my reservation?
                                    </button>
                                </h2>
                                <div id="collapseSeven" class="accordion-collapse collapse"
                                    aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        If you do not pick up your reserved item within the scheduled time, your
                                        reservation may be canceled, and penalties may apply as per the terms.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 8 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingEight">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseEight" aria-expanded="false"
                                        aria-controls="collapseEight">
                                        How can I view my reservation history?
                                    </button>
                                </h2>
                                <div id="collapseEight" class="accordion-collapse collapse"
                                    aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        To view your past reservations, go to My Reservations and select the “History”
                                        tab to see a list of completed reservations.
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ 9 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingNine">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseNine" aria-expanded="false"
                                        aria-controls="collapseNine">
                                        Can someone else pick up my reserved item?
                                    </button>
                                </h2>
                                <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Yes, with prior authorization. During reservation, specify an alternative pickup
                                        person by providing their details in the relevant section.
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>


                <!-- Contact Us Section -->
                <div class="col-md-4">
                    <div class="contact-card p-4 rounded shadow-lg bg-light">
                        <h2 class="section-title mb-3 text-primary">Contact Us</h2>
                        <p class="text-muted mb-4">If you need further assistance, feel free to reach out!</p>

                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <strong>Email:</strong> <a href="mailto:support@example.com"
                                    class="text-decoration-none text-primary">support@example.com</a>
                            </li>
                            <li class="mb-3">
                                <strong>Phone:</strong> <span class="text-muted">+123-456-7890</span>
                            </li>
                            <li class="mb-3">
                                <strong>Live Chat:</strong> Available Mon-Fri, 9 AM - 5 PM
                            </li>
                        </ul>

                        <button class="btn btn-primary w-100 mt-3 py-2" onclick="window.FB.CustomerChat.show();">
                            <i class="fas fa-comments"></i> Start Live Chat
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div id="fb-root"></div>
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v12.0"
        nonce="YOUR_NONCE_VALUE"></script>
    <div class="fb-customerchat" attribution="setup_tool" page_id="YOUR_PAGE_ID" theme_color="#0084ff"
        logged_in_greeting="Hi! How can we help you?" logged_out_greeting="Hi! How can we help you?"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>